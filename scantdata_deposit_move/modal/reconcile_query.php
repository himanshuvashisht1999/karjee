<?php
date_default_timezone_set("Asia/Calcutta");
require('../../connection.php');
require('../../functions/function_master.php');
$action=$_GET['action'];
//created by ajay on 09-07-2025
if($action=="creditadvice_data_scantdata"){
    	
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];  
    // $datefrom = $_POST['datefrom']; 
    // $dateto = $_POST['dateto']; 
    $returnarray=array();
    $qry = "SELECT c.id,c.sno,c.account_id, c.transactiondate, round(c.amount,2) as amount,c.transactionno, DATE_FORMAT(c.transactiondate,'%d-%m-%Y') as displaydate,c.remarks as bankdesciption ,c.clientid , c.purpose,c.purposecode,c.filename, c.voicepath,c.clientname ,  c.clienttype,   c.deposittype FROM  tbl_creditadvice c    WHERE   c.companyid = '".$companyid."'   AND c.subcompanyid = '".$subcompanyid."'   AND c.projectid ='".$projectid."'  and IFNULL(c.reconcile_refno, '') =''  and ifnull(c.tedit,'')=''  order by c.transactiondate,c.id, c.sno ";
    $result=$conn->query($qry); 
    if ($result->num_rows>0) { 
    while ($rowlist=$result->fetch_assoc()) {
        $returnarray[]=$rowlist ;
    }
    } 
    echo json_encode($returnarray);

}else if ($action == "bankdata_data") {
    $returnhtml = "";  
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];  
    $accountid = $_POST['accountid']; 
    $bankrefno = $_POST['bankref'];
    $chequeno = $_POST['transactionno'];
    $subqry = "";
    $subqry2 = "";
    if ($bankrefno && $chequeno) {
        $subqry = " AND (c.description_bank LIKE '%" . $bankrefno . "%' OR c.chequeno LIKE '%" . $chequeno . "%')";
    } elseif ($bankrefno) {
        $subqry = " AND c.description_bank LIKE '%" . $bankrefno . "%'";
    } elseif ($chequeno) {
        $subqry = " AND c.chequeno LIKE '%" . $chequeno . "%'";
    }
    if ($accountid) {
        $subqry2 = " AND c.account_id = '" . $accountid . "'";
    }
    $qry = "SELECT  b.bankname, acc.accountname, acc.accountno,   c.bankid, c.account_id, 
                DATE_FORMAT(c.date, '%d-%m-%Y') AS diplaydate,  c.date AS date, c.total_amount, c.description_bank,   IFNULL(c.chequeno,'') AS chequeno,  c.banksno, c.sno  
            FROM tbl_closetobook c  
            JOIN tbl_bank_master b ON b.id = c.bankid 
            JOIN tbl_master_account acc ON acc.accountid = c.account_id 
            WHERE lcase(c.amounttype) = 'deposit'   AND c.company_id = '" . $companyid . "' 
            AND c.subcompany_id = '" . $subcompanyid . "'   AND c.project_id = '" . $projectid . "' 
            AND IFNULL(c.reconciliation, '') = '' " . $subqry . " " . $subqry2 . "  ORDER BY c.date ASC";
    $resultlist = $conn->query($qry);
    if ($resultlist->num_rows <= 0) {
        $qry = "SELECT b.bankname, acc.accountname, acc.accountno, c.bankid, c.account_id,  DATE_FORMAT(c.date, '%d-%m-%Y') AS diplaydate,   c.date AS date, c.total_amount, c.description_bank, IFNULL(c.chequeno,'') AS chequeno,  c.banksno, c.sno   FROM tbl_closetobook c  
                JOIN tbl_bank_master b ON b.id = c.bankid 
                JOIN tbl_master_account acc ON acc.accountid = c.account_id 
                WHERE c.amounttype = 'deposit' 
                AND c.company_id = '" . $companyid . "' AND c.subcompany_id = '" . $subcompanyid . "'  AND c.project_id = '" . $projectid . "'  AND IFNULL(c.reconciliation, '') = '' " . $subqry2 . "  ORDER BY c.date ASC";
        $resultlist = $conn->query($qry);
    }
    $ss = 1;
    if ($resultlist->num_rows > 0) {
        while ($rowlist = $resultlist->fetch_assoc()) {
            $returnhtml .= "<tr 
                data-bankid='" . $rowlist['bankid'] . "' 
                data-accountid='" . $rowlist['account_id'] . "' 
                data-banksno='" . $rowlist['banksno'] . "' 
                data-dbsno='" . $rowlist['sno'] . "' 
                data-transactiondate='" . $rowlist['date'] . "'>
                    <td class='text-center'>" . $ss++ . "</td> 
                    <td class='row-bankcheckbox'>
                        <input type='checkbox' class='bankdataradio' name='bankdataradio' style='transform: scale(2);'>
                    </td>
                    <td>" . $rowlist['diplaydate'] . "</td> 
                    <td class ='bankamount' style='text-align:right;'>" . $rowlist['total_amount'] . "</td> 
                    <td class='description_bank'>" . $rowlist['description_bank'] . "</td>  
                    <td class='chequeno'>" . $rowlist['chequeno'] . "</td>  
                    <td class ='bankname'>" . $rowlist['bankname'] . "</td>
                    <td class ='accountname'>" . $rowlist['accountname'] . "</td>
                    <td class ='accountno'>" . $rowlist['accountno'] . "</td>   
                </tr>";
        }
    }

    echo $returnhtml;
}  
?>