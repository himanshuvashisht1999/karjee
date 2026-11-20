<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="getauto_receivableinvoiceno"){
    $countryid = $_POST['countryid'];
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $maxinvoiceid=autoreceivableinvoiceno($conn,$countryid,$companyid,$subcompanyid,$projectid);

    $newauto_invno="SIN-".$countryid.$companyid.$subcompanyid.$projectid.'-'.date('ymd').'-'.$maxinvoiceid;
    echo  json_encode($newauto_invno);
}else if($action=="savetodb_digitization_receivableinvoice"){
    $result=array();  
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
    $loginid = $_POST['loginid'];
    $clientid = $_POST['clientid'];
    $contractorid = $_POST['contractorid'];
    $invoiceno = $_POST['invoiceno'];
    $paymentpaystatus = $_POST['paymentpaystatus'];
    $modeofpayment = $_POST['modeofpayment'];
    $transferamount_againstinvoice = $_POST['transferamount_againstinvoice'];
    $transferamount = $_POST['transferamount'];
    $utrno = $_POST['utrno'];
    $transactiondate = $_POST['transactiondate'];
    $description = $_POST['description'];
    $emailstatus = $_POST['emailstatus'];
    $emaildbsno = isset($_POST['emaildbsno'])?$_POST['emaildbsno']:'';
    $emailshortedpurpose= $_POST['emailshoetedpurpose'];
 $sltchk=$mconn->query("SELECT sno from tbl_digitization_emailcontent where description='".addslashes($description)."' and companyid='".$companyid."' and projectid='".$contractorid."' and invoice_no='".$invoiceno."' and transferamount='".$transferamount."' and transactionno='".$utrno."'");
    $emailcontentid="";
    if($sltchk->num_rows>0){
        $result[]=array('status'=>'Error', 'message'=>'It is already exist');
    }
	else{
        $ccn=$mconn->query("SELECT ifnull(count(sno),0) as maxrow from tbl_digitization_emailcontent");
        $ccrow=$ccn->fetch_assoc();
        $emailcontentid=$ccrow['maxrow'] + 1;

        $insq="INSERT INTO tbl_digitization_emailcontent(email_contentid,companyid, subcompanyid, projectid, clientid, invoice_no, paymentstatus, transferamunt_againstinvoice, transferamount, transactionno, transactiondate, description, email_associat_id,emailpurpose) VALUES('".$emaildbsno."','".$companyid."', '".$subcompanyid."', '".$contractorid."', '".$clientid."', '".$invoiceno."', '".$paymentpaystatus."', '".$transferamount_againstinvoice."', '".$transferamount."', '".$utrno."', '".$transactiondate."', '".$description."','".$emaildbsno."','".$emailshortedpurpose."')";
        $insq_result=$mconn->query($insq) or die($mconn->error);
        if($mconn->affected_rows>0){
           $mconn->query("update emailtodb_email set email_digitizationstatus=1 where ID='".$emaildbsno."'");
            $result[]=array('status'=>'Success', 'message'=>'Dave save successfully');
        }else{
            $result[]=array('status'=>'Error', 'message'=>'Technical error');
        }
    }
    echo json_encode($result);
}
else if($action=="save_creditadvice_manual"){
    $result=array();
    $companyid=$_POST["companyid"];
    $subcompanyid=$_POST["subcompanyid"];
    $projectid=$_POST["projectid"];
    $subprojectid=$_POST["subprojectid"];
    $accountid=$_POST["accountid"]; 
    $clienttype=$_POST["clienttype"];
    $accountid=$_POST["accountid"];
    $clientid=$_POST["clientid"];
    $transdate=$_POST["transdate"];
    $bankrecamt=$_POST["bankrecamt"];
    $remittancenumber=$_POST["remittancenumber"];
    $bankrefno=$_POST["bankrefno"];
    $periodfrom=$_POST["periodfrom"];
    $periodto=$_POST["periodto"];
    $periodcovered="";
    if (!empty($periodfrom) && !empty($periodto)) {
     $periodcovered = $periodfrom . " To " . $periodto;
    }
    $arraydata=$_POST["arraydata"];
    $arraydata = json_decode($arraydata,TRUE);   
    $creditadvicefile = $_FILES["file"];
    $pathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/creditadvice/'.date('Ymd').'/';
    // Create the uploads directory if it doesn't exist
    if (!is_dir($pathDir)) {
        mkdir($pathDir, 0755, true);
    }
    $excelreceiptpath="";
    $file_ext = pathinfo($_FILES['file']['name'],PATHINFO_EXTENSION);
    $filename = round(microtime(true)) .date('His'). '.' . $file_ext;
    $uploadFile = $pathDir . $filename;
    $excelreceiptpath = str_replace("../", "", $uploadFile);
    //chk duplicate
     $bkqry = "select companyid from tbl_creditadvice where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and subprojectid='".$subprojectid."' and  account_id  ='".$accountid."' and clientid ='".$clientid."' and clienttype ='".$clienttype."' and transactionno='".addslashes($remittancenumber)."' and  bankreceivedamt ='".$bankrecamt."' and transactiondate ='".$transdate."' ";
        $bkres = $conn->query($bkqry); 
        if($bkres->num_rows>0  ){
            // $bkrow = $bkres->fetch_assoc(); 
             $result[]=array('status'=>'Duplicate Entry', 'message'=>'Duplicate Entry');
              echo json_encode($result);
             exit;
        }
    // Move the uploaded file to the specified directory
    if(move_uploaded_file($_FILES['file']['tmp_name'], $uploadFile)){
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $conn->begin_transaction();
        $insq="";
        try{ 
                $maxid=0;
                $bkqry = "select (ifnull(max(id ),0)+1) as maxid from tbl_creditadvice where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' ";//and subprojectid='".$subprojectid."'
                $bkres = $conn->query($bkqry);
                if($bkres->num_rows>0 && $bkres->num_rows<2){
                    $bkrow = $bkres->fetch_assoc();
                    $maxid=$bkrow['maxid'];

                }
            foreach ($arraydata as $tbkey) {
               $amt= $tbkey['deposittype']=="Deposit"? abs($tbkey['recamt']):0;
               $creditamt= $tbkey['deposittype']=="Deposit"? $bankrecamt:0;

               $insq="insert into tbl_creditadvice(id,`companyid`,`subcompanyid`,`projectid`,subprojectid,`account_id`,clienttype,clientid,`transactiondate`,`transactionno`,bankreceivedamt,bankrefno,`periodfrom`,`periodto`,periodcovered, filename, deposittype, purpose, purposecode, creditamount, debitamount, vatamount, actualpayeeid, remarks, amount) VALUES('".$maxid."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$accountid."','".$clienttype."','".$clientid."','".$transdate."',  '".addslashes($remittancenumber)."','".$bankrecamt."','".addslashes($bankrefno)."','".$periodfrom."','".$periodto."','".$periodcovered."' ,'".$excelreceiptpath."','".$tbkey['deposittype']."','".$tbkey['purpose']."','".$tbkey['purposecode']."', '".$creditamt."','".($tbkey['recamt']<0?abs($tbkey['recamt']):0)."','".abs($tbkey['vatamt'])."','".$tbkey['actualPayer']."','".addslashes($tbkey['remark'])."', '".$amt."')";
                $insq_result=$mconn->query($insq);
                if($tbkey['deposittype']=="Deposit"){
                    $insq = "insert into tbl_depositsinexcelsheet (account_id, company_id,subcompany_id,project_id,subprojectid,date,payto,amount,total_amount,description,chequeno,category,sub_category,provided_code,purpose,purpose_code,provided_purpose,accountno,source)values('".$accountid."','".$companyid."','".$subcompanyid."','".$projectid."' ,'".$subprojectid."' ,'".$transdate."','','".$bankrecamt."','".$bankrecamt."','".addslashes($tbkey['remark'])."','".addslashes($bankrefno)."','".$tbkey['category']."','".$tbkey['subcategory']."','".$tbkey['providedcode']."','".$tbkey['purpose']."','".$tbkey['purposecode']."','".$tbkey['purpose']."',(select accountno from tbl_master_account where accountid='".$accountid."'),'Credit Advice')";
                    $insq_result=$conn->query($insq);
                }else{
                    $newdeductamt=abs($tbkey['recamt'])  +abs($tbkey['vatamt']);
                    $insq = "insert into tbl_expencesinexcelsheet (remarks,account_id, company_id,subcompany_id,project_id,subprojectid,date,payto,amount,total_amount,description,chequeno,category,sub_category,provided_code,purpose,purpose_code,provided_purpose,accountno,source)values('autodeduction','".$accountid."','".$companyid."','".$subcompanyid."','".$projectid."' ,'".$subprojectid."' ,'".$transdate."','','".$newdeductamt."','".$newdeductamt."','".addslashes($tbkey['remark'])."','".addslashes($bankrefno)."','".$tbkey['category']."','".$tbkey['subcategory']."','".$tbkey['providedcode']."','".$tbkey['purpose']."','".$tbkey['purposecode']."','".$tbkey['purpose']."',(select accountno from tbl_master_account where accountid='".$accountid."'),'Credit Advice')";
                    $insq_result=$conn->query($insq);

                }
            } 
            $conn->commit();    
            $result[]=array('status'=>'Success', 'message'=>'Data saved successfully');
        }
        catch (mysqli_sql_exception $e) {
            $conn->rollback();
            $result[]=array('status'=>'Error', 'message'=>$insq.$e);
        }   
   }
    else{
        $result[]=array('status'=>'Error', 'message'=>$insq);
    }
    echo json_encode($result);
}else if ($action=="update_creditadvice") {
    $result=array();
    
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $sno = $_POST['sno'];
    $accountid=$_POST["accountid"];
    $amount = $_POST["amount"];
    $refno = $_POST["refno"];
    $refdate = $_POST["refdate"];
    $remarks = $_POST["remarks"];
    $remittancenumber = $_POST["remittancenumber"];
    $leasecode = $_POST["leasecode"];
    $purposecode = $_POST["purposecode"];
    $purpose = $_POST["purpose"];
    $expdate = $_POST["expdate"];
    $creditamt = $_POST["creditamt"];
    $paymethod = $_POST["paymethod"];
    $debitamt = $_POST["debitamt"];
    $periodcovered = $_POST["periodcovered"];
   
    $insq="update tbl_creditadvice set `amount`='".$amount."',`transactionno`='".$refno."',`transactiondate`='".$refdate."',`remarks`='".$remarks."',account_id='".$accountid."',remittance_no='".$remittancenumber."',leascode='".$leasecode."',purposecode='".$purposecode."',purpose='".$purpose."',creditamount='".$creditamt."',debitamount='".$debitamt."',paymethod='".$paymethod."',periodcovered='".$periodcovered."',verified_status='Done' where `sno`='".$sno."'";
    $insq_result=$mconn->query($insq);
    if ($insq_result) {
         $result[]=array('status'=>'Success', 'message'=>'Data update successfully');
    }else{
         $result[]=array('status'=>'Error', 'message'=>'Data not update successfully');
    }

     echo json_encode($result);
}
else if($action=="SIN"){
    $countryid = $_POST['countryid'];
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];

    // invoice date and invoiceno
    $invoice_no = $_POST['invoice_no'];
    $invoice_date = date('Y-m-d',strtotime($_POST['invoice_date']));

    // invoice to(client) information
    $client_id = $_POST['clientid'];
    $invoicefromprojectid=$_POST['inv_projectid'];
    $po_no = $_POST['get_po'];
    $inv_contractorid = $_POST['inv_contractorid'];    
    $terms_days = addslashes($_POST['terms_days']);

    //invoice for account
    $inv_accountno = addslashes($_POST['inv_accountno']);

    //item details
    $invtableData = $_POST['pTableData_Inv'];
    $invtableData = json_decode($invtableData,TRUE);


    $clintinfo=getclientdetails($conn,$companyid,$subcompanyid,$projectid,"clientid",$client_id);
    foreach ($clintinfo as $cntkey) {
        $client_name=$cntkey['client_name'];
        $clientaddress=$cntkey['invoicing_address'];
        $clientemail=$cntkey['client_email'];
        $clientphone=$cntkey['phones'];
    }
    // invoicing from project
    $prjdetails_forinvoice=projectdetails($conn,$companyid,$subcompanyid,"projectid",$invoicefromprojectid);
    $location="";
    $currency ="";
    foreach ($prjdetails_forinvoice as $cmkey) {
        $inv_projectid = $cmkey['projectid'];
        $invoice_fromname = $cmkey['projectname'];
        $inv_address = $cmkey['office_address'];
        $inv_email = $cmkey['office_email'];
        $inv_contactno = $cmkey['office_phone'];
        $companyregistrationno = $cmkey['comp_registration_no'];
        $currency = $cmkey['currency'];
    }

    // invoice from account details
    $inv_accountinfo = getaccountinformation($conn,$countryid,$companyid,$subcompanyid,$projectid,"accountno",$inv_accountno);
    foreach ($inv_accountinfo as $invacckey) {
        $inv_accountid = $invacckey['accountid'];
        $inv_bankid = $invacckey['bankid'];
        $inv_account_type = $invacckey['account_type'];
        $inv_accountname = $invacckey['accountname'];
        $inv_accountno = $invacckey['accountno'];
        $inv_ifsccode = $invacckey['ifsccode'];
        $inv_bankname = $invacckey['bankname'];
    }
    
    $due_date = date('Y-m-d', strtotime($invoice_date. ' +'.$terms_days.' days'));
    $entryno = "SELECT max(sno) as sno from tbl_invoices";
    $resultno = $conn->query($entryno);
    $entrysno = 1;
    if($resultno->num_rows>0)
    {
        $ent = $resultno->fetch_assoc();
        $entrysno = ($ent['sno']+1);
    }
   
    $invcreatedvia = "Created on web  at ".date('Y-m-d H:i:s');
    $insert = "INSERT INTO tbl_invoices (countryid, cmp_id, subcmp_id, project_id, invoice_id, invoice_type, invoice_date, invoice_no, invoice_forcontractorid, client_id, client_name, po_no, currency, due_date, invoice_amount, received_amount, balance_amount, vat_amount, invoice_fromname, from_bankname, from_accountno, from_ifsccode, from_accountname, from_address, from_phone, from_email, terms_days, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4,BillAddrCity,BillAddrState,BillAddrPostalCode,BillAddrCountry, invoice_created_via) VALUES ('".$countryid."','".$companyid."','".$subcompanyid."', '".$inv_projectid."','".$entrysno."', 'SIN', '".$invoice_date."', '".$invoice_no."', '".$inv_contractorid."','".$client_id."','".$client_name."','".$po_no."', '".$currency."', '".$due_date."', '0', '0', '0', '0', '".addslashes($invoice_fromname)."', '".addslashes($inv_bankname)."', '".addslashes($inv_accountno)."', '".addslashes($inv_ifsccode)."', '".addslashes($inv_accountname)."', '".addslashes($inv_address)."', '".addslashes($inv_contactno)."', '".addslashes($inv_email)."', '".addslashes($terms_days)."', '".addslashes($clientaddress)."', '".addslashes($clientemail)."', '".$clientphone."', '', '', '', '', '', '".$invcreatedvia."')";
    $result = $conn->query($insert) or die($conn->error);
    if(mysqli_affected_rows($conn)>0)
    {
        $rmremark="removed on date ".date('Y-m-d H:i:s');
        $sql = "UPDATE tbl_invoices_breakdown set tedit='".$rmremark."' WHERE invoice_id='".$entrysno."' and (tedit is null or tedit='')";
        $conn->query($sql) or die($conn->error);
        $total_units=0;
        $total_amounts=0;
        $total_vat_amount=0;
        foreach($invtableData as $invbrkkey)
        {
            $iteminfo =getitemmaster($conn,$countryid,$companyid,$subcompanyid,$projectid,"itemid",$invbrkkey['itemid']);
            $itemtypeid ="";
			$itembrandid ="";
			$hsncode ="";
            foreach ($iteminfo as $iitmkey) {
                $itemtypeid = $iitmkey['itemtypeid'];
                $itembrandid = $iitmkey['itembrandid'];
                $hsncode = $iitmkey['hsncode'];                
            }

            $ttlamount_withoutvat = $invbrkkey['unitcount']*$invbrkkey['unitprice'];
            $vat_percent = $invbrkkey['unitvat'];
            $vat_amount = $invbrkkey['unitvatamount'];
            $ttlamount_withvat = $ttlamount_withoutvat + $vat_amount;
            if($invbrkkey['itemid']!=""){
            	$insert = "INSERT INTO tbl_invoices_breakdown (invoice_id, invoice_no, item_typeid, item_brandid, items_id, items_name, units, rate, taxable_amount, vat_percent, vat_amount, totalamount_excludingtax, totalamount_includetax, item_termtype, discount_percent, discount_amount) VALUES ('".$entrysno."','".$invoice_no."','".$itemtypeid."', '".$itembrandid."','".$invbrkkey['itemid']."','".$invbrkkey['itemname']."','".$invbrkkey['unitcount']."','".$invbrkkey['unitprice']."', '".$ttlamount_withoutvat."', '".$vat_percent."', '".$vat_amount."', '".$ttlamount_withoutvat."', '".$ttlamount_withvat."', '".$invbrkkey['itemterm']."', '0', '0')";
            	$result = $conn->query($insert) or die($conn->error);
	            if($result){
	                $total_amounts = $total_amounts + $ttlamount_withvat;
	                $total_vat_amount = $total_vat_amount + $invbrkkey['unitvatamount'];
	            }
	            $update = "UPDATE tbl_invoices set invoice_amount='".$total_amounts."', LineAmount='".$total_amounts."', vat_amount='".$total_vat_amount."' where invoice_id='".$entrysno."'";
		        $conn->query($update) or die($conn->error);
	        }else {
	        	$arr = array("status"=>"Error", 'message'=>'Incomplete save the invoice information', 'id'=>'');
	        }
        }          
        $arr = array("status"=>'Success', 'message'=>'Form Has been filled Successfully','id'=>$entrysno);
    }else{
        $arr = array("status"=>"Error", 'message'=>'Data not Saved', 'id'=>'');
    }
    header('Content-type:application/json');
    echo json_encode($arr);
}else if($action=="SIN_BOOKRECON"){

    $countryid = $_POST['countryid'];
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];

    $credit_bankamount=$_POST['credit_bankamount'];
    $noofinvoice=$_POST['noofinvoice'];
    $banksno=$_POST['banksno'];
    $invoicdoc_status=$_POST['invoicdoc_status'];

    // invoice date and invoiceno
    $invoice_no = $_POST['invoice_no'];
    $invoice_date = date('Y-m-d',strtotime($_POST['invoice_date']));

    // invoice to(client) information
    $client_id = $_POST['clientid'];
    $invoicefromprojectid=$_POST['inv_projectid'];
    $po_no = $_POST['get_po'];
    $inv_contractorid = $_POST['inv_contractorid'];    
    $terms_days = addslashes($_POST['terms_days']);

    //invoice for account
    $inv_accountno = addslashes($_POST['inv_accountno']);

    //item details
    $invtableData = $_POST['pTableData_Inv'];
    $invtableData = json_decode($invtableData,TRUE);


    $clintinfo=getclientdetails($conn,$companyid,$subcompanyid,$projectid,"clientid",$client_id);
    foreach ($clintinfo as $cntkey) {
        $client_name=$cntkey['client_name'];
        $clientaddress=$cntkey['invoicing_address'];
        $clientemail=$cntkey['client_email'];
        $clientphone=$cntkey['phones'];
    }
    // invoicing from project
    $prjdetails_forinvoice=projectdetails($conn,$companyid,$subcompanyid,"projectid",$invoicefromprojectid);
    $location="";
    $currency ="";
    foreach ($prjdetails_forinvoice as $cmkey) {
        $inv_projectid = $cmkey['projectid'];
        $invoice_fromname = $cmkey['projectname'];
        $inv_address = $cmkey['office_address'];
        $inv_email = $cmkey['office_email'];
        $inv_contactno = $cmkey['office_phone'];
        $companyregistrationno = $cmkey['comp_registration_no'];
        $currency = $cmkey['currency'];
    }

    // invoice from account details
    $inv_accountinfo = getaccountinformation($conn,$countryid,$companyid,$subcompanyid,$projectid,"accountno",$inv_accountno);
    foreach ($inv_accountinfo as $invacckey) {
        $inv_accountid = $invacckey['accountid'];
        $inv_bankid = $invacckey['bankid'];
        $inv_account_type = $invacckey['account_type'];
        $inv_accountname = $invacckey['accountname'];
        $inv_accountno = $invacckey['accountno'];
        $inv_ifsccode = $invacckey['ifsccode'];
        $inv_bankname = $invacckey['bankname'];
    }

    
    $due_date = date('Y-m-d', strtotime($invoice_date. ' +'.$terms_days.' days'));
    $entryno = "SELECT max(sno) as sno from tbl_invoices";
    $resultno = $conn->query($entryno);
    $entrysno = 1;
    if($resultno->num_rows>0)
    {
        $ent = $resultno->fetch_assoc();
        $entrysno = ($ent['sno']+1);
    }
   
    $invcreatedvia = "Created on web  at ".date('Y-m-d H:i:s');
    $insert = "INSERT INTO tbl_invoices (countryid, cmp_id, subcmp_id, project_id, invoice_id, invoice_type, invoice_date, invoice_no, invoice_forcontractorid, client_id, client_name, po_no, currency, due_date, invoice_amount, received_amount, balance_amount, vat_amount, invoice_fromname, from_bankname, from_accountno, from_ifsccode, from_accountname, from_address, from_phone, from_email, terms_days, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4,BillAddrCity,BillAddrState,BillAddrPostalCode,BillAddrCountry, invoice_created_via,bank_sno) VALUES ('".$countryid."','".$companyid."','".$subcompanyid."', '".$inv_projectid."','".$entrysno."', 'SIN', '".$invoice_date."', '".$invoice_no."', '".$inv_contractorid."','".$client_id."','".$client_name."','".$po_no."', '".$currency."', '".$due_date."', '0', '0', '0', '0', '".addslashes($invoice_fromname)."', '".addslashes($inv_bankname)."', '".addslashes($inv_accountno)."', '".addslashes($inv_ifsccode)."', '".addslashes($inv_accountname)."', '".addslashes($inv_address)."', '".addslashes($inv_contactno)."', '".addslashes($inv_email)."', '".addslashes($terms_days)."', '".addslashes($clientaddress)."', '".addslashes($clientemail)."', '".$clientphone."', '', '', '', '', '', '".$invcreatedvia."','".$banksno."')";
    $result = $conn->query($insert) or die($conn->error);
    if(mysqli_affected_rows($conn)>0)
    {
        $rmremark="removed on date ".date('Y-m-d H:i:s');
        $sql = "UPDATE tbl_invoices_breakdown set tedit='".$rmremark."' WHERE invoice_id='".$entrysno."' and (tedit is null or tedit='')";
        $conn->query($sql) or die($conn->error);
        $total_units=0;
        $total_amounts=0;
        $total_vat_amount=0;
        foreach($invtableData as $invbrkkey)
        {
            $iteminfo =getitemmaster($conn,$countryid,$companyid,$subcompanyid,$projectid,"itemid",$invbrkkey['itemid']);
            $itemtypeid ="";
            $itembrandid ="";
            $hsncode ="";
            foreach ($iteminfo as $iitmkey) {
                $itemtypeid = $iitmkey['itemtypeid'];
                $itembrandid = $iitmkey['itembrandid'];
                $hsncode = $iitmkey['hsncode'];                
            }

            $ttlamount_withoutvat = $invbrkkey['unitcount']*$invbrkkey['unitprice'];
            $vat_percent = $invbrkkey['unitvat'];
            $vat_amount = $invbrkkey['unitvatamount'];
            $ttlamount_withvat = $ttlamount_withoutvat + $vat_amount;
            if($invbrkkey['itemid']!=""){
                $insert = "INSERT INTO tbl_invoices_breakdown (invoice_id, invoice_no, item_typeid, item_brandid, items_id, items_name, units, rate, taxable_amount, vat_percent, vat_amount, totalamount_excludingtax, totalamount_includetax, item_termtype, discount_percent, discount_amount) VALUES ('".$entrysno."','".$invoice_no."','".$itemtypeid."', '".$itembrandid."','".$invbrkkey['itemid']."','".$invbrkkey['itemname']."','".$invbrkkey['unitcount']."','".$invbrkkey['unitprice']."', '".$ttlamount_withoutvat."', '".$vat_percent."', '".$vat_amount."', '".$ttlamount_withoutvat."', '".$ttlamount_withvat."', '".$invbrkkey['itemterm']."', '0', '0')";
                $result = $conn->query($insert) or die($conn->error);
                if($result){
                    $total_amounts = $total_amounts + $ttlamount_withvat;
                    $total_vat_amount = $total_vat_amount + $invbrkkey['unitvatamount'];
                }
                $update = "UPDATE tbl_invoices set invoice_amount='".$total_amounts."', LineAmount='".$total_amounts."', vat_amount='".$total_vat_amount."' where invoice_id='".$entrysno."'";
                $conn->query($update) or die($conn->error);
            }else {
                $arr = array("status"=>"Error", 'message'=>'Incomplete save the invoice information', 'id'=>'');
            }
        }          
        $arr = array("status"=>'Success', 'message'=>'Form Has been filled Successfully','id'=>$entrysno);
    }else{
        $arr = array("status"=>"Error", 'message'=>'Data not Saved', 'id'=>'');
    }
    header('Content-type:application/json');
    echo json_encode($arr);
}
?>