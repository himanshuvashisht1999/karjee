<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="createdrequestdetailswithcallback"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$rrct=array();
	if($loginroleid<=3){
		$shortbyvalue=$reqdatefrom.'|'.$reqdateto;
		$reqlist=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"betweendatewillall",$shortbyvalue);
		if(count($reqlist)>0){
			foreach ($reqlist as $rrqkey) {
				$mnt=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$rrqkey['paid_for_ref']);
				$bookdate ="N/A";
				$transactiondate ="N/A";
				$transactiontype ="";
				$bookamount ="0";
				$transferedamount ="0";
				$transactionno="pending";
				$transactionstatus="<span class='text-warning'><i class='fa fa-clock'></i> In Process";
				if(count($mnt)>0){
					foreach ($mnt as $mntkey) {
						$bookdate = isValidDate($mntkey['bookdate'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['bookdate'])):'N/A';
						$transactiondate = isValidDate($mntkey['transaction_date'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['transaction_date'])):'N/A';
						$transactiontype = $mntkey['transaction_type'];
						$bookamount = $mntkey['book_amount'];
						$transferedamount = $mntkey['transfered_amount'];
						$transactionno = $mntkey['transactionno']!=''?$mntkey['transactionno']:'pending';
						$transactionstatus = $mntkey['transactionno']!=""?"<span class='text-success'><i class='fa fa-check-circle'></i> ".$mntkey['transactionno']."</span>":"<span class='text-warning'><i class='fa fa-clock'></i> In Process</span>";

					}
				}
				$rrct[]=array('rqdbsno'=>$rrqkey['sno'],'requestno'=>$rrqkey['request_no'], 'requestdate'=>date('d-M-Y', strtotime($rrqkey['request_date'])), 'paytoname'=>strtoupper($rrqkey['payto']), 'requestamount'=>$rrqkey['request_amount'], 'requeststatus'=>$rrqkey['approval_status'], 'requestapproveby'=>$rrqkey['status_by'], 'referenceno'=>$rrqkey['paid_for_ref'], 'bookdate'=>$bookdate,'transactiondate'=>$transactiondate,'transferedamount'=>$transferedamount,'transactionno'=>$transactionno,'transactionstatus'=>$transactionstatus);
			}
		}
	}else{
		$shortbyvalue=$reqdatefrom.'|'.$reqdateto.'|'.$loginname;
		$reqlist=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"betweendatewithrequestedby",$shortbyvalue);
		if(count($reqlist)>0){
			foreach ($reqlist as $rrqkey) {
				$mnt=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$rrqkey['paid_for_ref']);
				$bookdate ="N/A";
				$transactiondate ="N/A";
				$transactiontype ="";
				$bookamount ="0";
				$transferedamount ="0";
				$transactionno="pending";
				$transactionstatus="pending";
				if(count($mnt)>0){
					foreach ($mnt as $mntkey) {
						$bookdate = isValidDate($mntkey['bookdate'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['bookdate'])):'N/A';
						$transactiondate = isValidDate($mntkey['transaction_date'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['transaction_date'])):'N/A';
						$transactiontype = $mntkey['transaction_type'];
						$bookamount = $mntkey['book_amount'];
						$transferedamount = $mntkey['transfered_amount'];
						$transactionno = $mntkey['transactionno']!=''?$mntkey['transactionno']:'pending';
						$transactionstatus = $mntkey['transactionno']!=''?$mntkey['transactionno']:'pending';
					}
				}
				$rrct[]=array('rqdbsno'=>$rrqkey['sno'],'requestno'=>$rrqkey['request_no'], 'requestdate'=>date('d-M-Y', strtotime($rrqkey['request_date'])), 'paytoname'=>strtoupper($rrqkey['payto']), 'requestamount'=>$rrqkey['request_amount'], 'requeststatus'=>$rrqkey['approval_status'], 'requestapproveby'=>$rrqkey['status_by'], 'referenceno'=>$rrqkey['paid_for_ref'], 'bookdate'=>$bookdate,'transactiondate'=>$transactiondate,'transferedamount'=>$transferedamount,'transactionno'=>$transactionno,'transactionstatus'=>$transactionstatus);
			}
		}
	}
	echo json_encode($rrct);	
}else if($action=="requestbreakdowndetailscallback"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$requestno=$_POST['requestnoval'];
	$mnt=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
	$refbrklist=array();
	if(count($mnt)>0){
		foreach ($mnt as $mntkey) {
			$refdetails=getrequestbreakdowndetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
			if(count($refdetails)>0){
				foreach ($refdetails as $rffbkey) {
					$documentpath ="";
					if (file_exists('../'.$rffbkey['filepath'])) {
					    $documentpath = $rffbkey['filepath'];
					}
					$refbrklist[]=array('requestno'=>$rffbkey['request_no'],'beneficiaryname'=>$rffbkey['beneficiery_name'],'beneficiaryamount'=>$rffbkey['ammount'], 'purpose_categoryid'=>$rffbkey['purpose_categoryid'], 'purpose_category'=>$rffbkey['purpose_category'], 'purpose_subcategoryid'=>$rffbkey['purpose_subcategoryid'], 'purpose_subcategory'=>$rffbkey['purpose_subcategory'], 'purpose'=>$rffbkey['purpose'], 'purpose_code'=>$rffbkey['purpose_code'], 'purposewithcode'=>$rffbkey['purpose'].' ('.$rffbkey['purpose_code'].')','invoicetype'=>$rffbkey['invoice_type'],'invoiceno'=>$rffbkey['invoice_no'],'invoicedate'=>$rffbkey['invoice_date'], 'description'=>$rffbkey['description'],'documentpath'=>$documentpath,'audiofilepath'=>$rffbkey['audiofilepath'],'voiceinstid'=>$rffbkey['voiceinstid']);
				}
			}
		}
	}
	echo json_encode($refbrklist);
}else if($action=="maintransactiondetailscallback"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$datafiltertxt=$_POST['datafiltertxt'];
	$rrct=array();
	$shortbyvalue=$reqdatefrom.'|'.$reqdateto;
	$mnt=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,$datafiltertxt,$shortbyvalue);
	$bookdate ="N/A";
	$transactiondate ="N/A";
	$transactiontype ="";
	$bookamount ="0";
	$transferedamount ="0";
	$transactionno="pending";
	$transactionstatus="<span class='text-warning'><i class='fa fa-clock'></i> pending";
	if(count($mnt)>0){
		foreach ($mnt as $mntkey) {
			$bookdate = isValidDate($mntkey['bookdate'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['bookdate'])):'N/A';
			$transactiondate = isValidDate($mntkey['transaction_date'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['transaction_date'])):'N/A';
			$transactiontype = $mntkey['transaction_type'];
			$bookamount = $mntkey['book_amount'];
			$transferedamount = $mntkey['transfered_amount'];
			$transactionno = $mntkey['transactionno']!=''?$mntkey['transactionno']:'<span class=\'text-warning\'><i class=\'fa fa-clock\'></i> pending</span>';

			$transactionstatus = $mntkey['transactionno']!=""?"<span class='text-success'><i class='fa fa-check-circle'></i> Done</span>":"<span class='text-warning'><i class='fa fa-clock'></i> pending</span>";

			$paidfrombankname = $mntkey['transactionno']!=""?$mntkey['payfrombankname']:"N/A";
			$paidfromaccountno = $mntkey['transactionno']!=""?$mntkey['lastfourdigitfrmacno']:"";
			$filepathname = '../'.$mntkey['transferreceiptpath'];
			$filepath="";
			if (file_exists($filename)) {
			    $filepath=$mntkey['transferreceiptpath'];
			} else {
			    $filepath='N/A';
			}			

			$rrct[]=array('requestno'=>$mntkey['request_no'], 'referenceno'=>$mntkey['reference_no'], 'bookdate'=>$bookdate, 'paytoname'=>strtoupper($mntkey['payto']), 'bookamount'=>$bookamount,'transactionstatus'=>$transactionstatus,'transactiondate'=>$transactiondate,'transferedamount'=>$transferedamount,'transactionno'=>$transactionno,'payfromaccountno'=>$paidfromaccountno,'payfrombankname'=>$paidfrombankname, 'paytoaccountno' => $mntkey['paytoaccountno'], 'paytobankname' => $mntkey['paytobankname'], 'paytoifsc' => $mntkey['paytoifsc'], 'transferreceiptpath'=>$filepath);
		}
	}
	
	echo json_encode($rrct);	
}else if($action=="paymenttransferdetailscallback"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$datafiltertxt=$_POST['datafiltertxt'];
	$rrct=array();
	$shortbyvalue=$reqdatefrom.'|'.$reqdateto;
	$mnt=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,$datafiltertxt,$shortbyvalue);
	$bookdate ="N/A";
	$transactiondate ="N/A";
	$transactiontype ="";
	$bookamount ="0";
	$transferedamount ="0";
	$transactionno="pending";
	$transactionstatus="<span class='text-warning'><i class='fa fa-clock'></i> pending";
	if(count($mnt)>0){
		foreach ($mnt as $mntkey) {
			$bookdate = isValidDate($mntkey['bookdate'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['bookdate'])):'N/A';
			$transactiondate = isValidDate($mntkey['transaction_date'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['transaction_date'])):'N/A';
			$transactiontype = $mntkey['transaction_type'];
			$bookamount = $mntkey['book_amount'];
			if($mntkey['transfered_amount']>0){
				$transferedamount = $mntkey['transfered_amount'];
			}else if($mntkey['transfered_amount']==0 || $mntkey['transfered_amount']==null || $mntkey['transfered_amount']=='' || $mntkey['transactionno']!=""){
				$transferedamount = $mntkey['book_amount'];
			}else{
				$transferedamount = 'N/A';
			}
			$transactionno = $mntkey['transactionno']!=''?$mntkey['transactionno']:'<span class=\'text-warning\'><i class=\'fa fa-clock\'></i> N/A</span>';

			$transactionstatus = $mntkey['transactionno']!=""?"<span class='text-success'><i class='fa fa-check-circle'></i> Done</span>":"<span class='text-warning'><i class='fa fa-clock'></i> N/A</span>";

			$paidfrombankname = $mntkey['transactionno']!=""?$mntkey['payfrombankname']:"N/A";
			$paidfromaccountno = $mntkey['transactionno']!=""?$mntkey['lastfourdigitfrmacno']:"";

			$filepathname = $mntkey['transferreceiptpath']!=""?'../'.$mntkey['transferreceiptpath']:"";
			$filepath="";
			$filestatus="";
			if (file_exists($filepathname)) {
				$filestatus="<span class='text-success'><i class='fa fa-file'></i> Uploaded</span>";
			    $filepath=$mntkey['transferreceiptpath'];
			} else {
				$filestatus="<span class='text-danger'><i class='fa fa-clock'></i> No Uploaded</span>";
			    $filepath='';
			}			

			$rrct[]=array('sno'=>$mntkey['sno'], 'requestno'=>$mntkey['request_no'], 'referenceno'=>$mntkey['reference_no'], 'bookdate'=>$bookdate, 'paytoname'=>strtoupper($mntkey['payto']), 'bookamount'=>$bookamount,'transactionstatus'=>$transactionstatus,'transactiondate'=>$transactiondate,'transferedamount'=>$transferedamount,'transactionno'=>$transactionno,'payfromaccountno'=>$paidfromaccountno,'payfrombankname'=>$paidfrombankname, 'paytoaccountno' => $mntkey['paytoaccountno'], 'paytobankname' => $mntkey['paytobankname'], 'paytoifsc' => $mntkey['paytoifsc'],'transferfilestatus'=>$filestatus, 'transferreceiptpath'=>$filepath);
		}
	}
	
	echo json_encode($rrct);	
}else if($action=="referencebreakdowndetailscallback"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$referencenoval=$_POST['referencenoval'];
	$mnt=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$referencenoval);
	$refbrklist=array();
	if(count($mnt)>0){
		foreach ($mnt as $mntkey) {
			$refdetails=get_maintransactionsbreakdown_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$referencenoval);
			if(count($refdetails)>0){
				foreach ($refdetails as $rffbkey) {
					$refbrklist[]=array('referenceno'=>$rffbkey['reference_no'],'beneficiaryname'=>$rffbkey['beneficiary_name'],'beneficiaryamount'=>$rffbkey['amount'],'tdstype'=>$rffbkey['tds_type'],'tdspercent'=>$rffbkey['tds_percent'],'tdsamount'=>$rffbkey['tds_amount'], 'purpose_categoryid'=>$rffbkey['purpose_categoryid'], 'purpose_subcategory'=>$rffbkey['purpose_subcategory'], 'purpose_subcategoryid'=>$rffbkey['purpose_subcategoryid'], 'purpose'=>$rffbkey['purpose'], 'purpose_code'=>$rffbkey['purpose_code'], 'purposewithcode'=>$rffbkey['purposewithcode'].' ('.$rffbkey['purpose_code'].')','invoicetype'=>$rffbkey['invoice_type'],'invoiceno'=>$rffbkey['invoice_no'],'invoicedate'=>$rffbkey[''], 'description'=>$rffbkey['description'],'documentpath'=>$rffbkey['documentation_path'],'audiofilepath'=>$rffbkey['audiofilepath'],'voiceinstid'=>$rffbkey['voiceinstid']);
				}
			}
		}
	}
	echo json_encode($refbrklist);
}else if($action=="editupdate_utrno"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$dbsno = $_POST['dbsno'];
	$utrno = $_POST['utrno'];
	$utrdate = $_POST['utrdate'];
	$receiptpath="";
	$upreceiptpath="";
	$rrst=array();
	if (isset($_FILES['transferreceiptpath']) && !$_FILES['transferreceiptpath']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/paymentreceipt/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($receiptpathDir)) {
            mkdir($receiptpathDir, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["transferreceiptpath"]["name"]);
		$receiptname = round(microtime(true)) . '.' . end($temp);

        // $filetype = $_FILES['filepath']['type'];
        // File path
        $uploadFile = $receiptpathDir . $receiptname;
        $receiptpath = str_replace("../", "", $uploadFile);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['transferreceiptpath']['tmp_name'], $uploadFile);
        $upreceiptpath=", transferreceiptpath='".addslashes($receiptpath)."'";
    }
	$ssq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE sno='".$dbsno."'");
	if($ssq->num_rows==1){
		$ssqq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE transactionno='".addslashes($utrno)."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
		if($ssqq->num_rows>0){
			// $rrst[]=array('status'=>'Error', 'message'=>'UTR number '.addslashes($utrno).' is already exist with other transation, Please check or enter valid urt number');
			$conn->query("UPDATE tbl_maintransaction SET uploadflag=null, transferreceiptpath='".addslashes($receiptpath)."' WHERE sno='".$dbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst[]=array('status'=>'Success', 'message'=>'UTR receipt updated successfully');
			}else{
				$rrst[]=array('status'=>'Error', 'message'=>'UTR number '.addslashes($utrno).' is already exist with other transation, Please check or enter valid urt number');
			}
		}else{
			$conn->query("UPDATE tbl_maintransaction SET transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."', uploadflag=null ".$upreceiptpath." WHERE sno='".$dbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst[]=array('status'=>'Success', 'message'=>'UTR number updated successfully');
			}else{
				$rrst[]=array('status'=>'Error', 'message'=>'UTR number has not been updated, Due to invalid or already updated, Please refresh web page');
			}
		}
	}else if($ssq->num_rows==0){
		$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available, Please refresh web page and try again');
	}
	echo json_encode($rrst);
}else if($action=="imprestsummarydetails"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$paytoid=$_POST['imprestpaytoid'];
	$rrct=array();
	$balance=0;
	if ($paytoid) {		
		$simp=getimprestaccountdetails($conn,$companyid,$subcompanyid,$projectid,'summarywithtopaytoid',$paytoid);
		foreach ($simp as $rrqkey) {
		 	$balance=$balance+$rrqkey['imprest_amount']-$rrqkey['adjustment_amount'];
		 	$rrct[]=array('imprestaccid'=>$rrqkey['to_accountid'],  'imprestpaytoname'=>strtoupper($rrqkey['imprest_payto']), 'imprestamount'=>$rrqkey['imprest_amount'], 'imprestadjustmentamount'=>$rrqkey['adjustment_amount'],'purpose'=>$rrqkey['purpose']."(".$rrqkey['purpose_code'].")",'balance'=>$balance,'idate'=>$rrqkey['idate'],'paytoid'=>$rrqkey['paytoid']);
	 	}
	}else{
		$imprestaccounts=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'Imprest','');
		foreach ($imprestaccounts as $keyimp) {
			 $keyimp['accountid'];
			 $simp=getimprestaccountdetails($conn,$companyid,$subcompanyid,$projectid,'summarywithtoaccountid',$keyimp['accountid']);
			 foreach ($simp as $rrqkey) {
			 	$balance=$rrqkey['imprest_amount']-$rrqkey['adjustment_amount'];
			 	$rrct[]=array('imprestaccid'=>$rrqkey['to_accountid'],  'imprestpaytoname'=>strtoupper($rrqkey['imprest_payto']), 'imprestamount'=>$rrqkey['imprest_amount'], 'imprestadjustmentamount'=>$rrqkey['adjustment_amount'],'balance'=>$balance,'paytoid'=>$rrqkey['paytoid']);
			 	$balance=0;
			 }
		}
	}
	echo json_encode($rrct);
}else if($action=="impreststatementdetails") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$paytoid=$_POST['imprestpaytoid'];
	$rrct=array();
	$balance=0;
	$simp=getimprestaccountdetails($conn,$companyid,$subcompanyid,$projectid,'imprestpaytoid',$paytoid);
	$imtmpsno=0;
	foreach ($simp as $rrqkey) {
	 	$balance=$balance+$rrqkey['imprest_amount']-$rrqkey['adjustment_amount'];
	 	$rrct[]=array('imptempsno'=>$imtmpsno,'imprestaccid'=>$rrqkey['to_accountid'],  'imprestpaytoname'=>strtoupper($rrqkey['imprest_payto']), 'imprestamount'=>$rrqkey['imprest_amount'], 'imprestadjustmentamount'=>$rrqkey['adjustment_amount'],'purpose'=>$rrqkey['purpose'],'balance'=>$balance,'idate'=>date('d-M-Y', strtotime($rrqkey['idate'])),'paytoid'=>$rrqkey['paytoid']);
	 	$imtmpsno++;
 	}
 	usort($rrct, function($a, $b) {
	    return $b['imptempsno'] <=> $a['imptempsno'];  // Sort in ascending order
	});
	// print_r($rrct);
	echo json_encode($rrct);	
}else if($action=="loanstatementdetails") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$paytoid=$_POST['paytoid'];
	$rrct=array();
	$balance=0;
	$simp=getloansummarydetails($conn,$companyid,$subcompanyid,$projectid,$paytoid,"loanid",$paytoid);
	// print_r($simp);
	$imtmpsno=0;
	foreach ($simp as $rrqkey) {
	 	$rrct[]=array('dbsno'=>$rrqkey['sno'],'loanaccid'=>$rrqkey['to_accountid'],  'paytoname'=>strtoupper($rrqkey['loan_payto']), 'loanamount'=>$rrqkey['loan_amount'], 'loanreturnamount'=>$rrqkey['loan_returnamount'],'loanbalanceamount'=>$rrqkey['loanbalance_amount'],'purpose'=>$rrqkey['purpose'],'idate'=>date('d-M-Y', strtotime($rrqkey['idate'])),'paytoid'=>$rrqkey['loanid'],'loanid'=>$rrqkey['loanid']);
	 	$imtmpsno++;
 	}
 	usort($rrct, function($a, $b) {
	    return $b['dbsno'] <=> $a['dbsno'];  // Sort in ascending order
	});
	echo json_encode($rrct);	
}else if($action=="cashstatementdetails") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$paytoid=$_POST['cashpaytoid'];
	$rrct=array();
	$balance=0;
	$simp=getcashaccountdetails($conn,$companyid,$subcompanyid,$projectid,'cashpaytoid',$paytoid);
	$imtmpsno=0;
	foreach ($simp as $rrqkey) {
	 	$balance=($balance+$rrqkey['cash_amount'])-$rrqkey['adjustment_amount'];
	 	$rrct[]=array('dbsno'=>$imtmpsno,'cashaccid'=>$rrqkey['to_accountid'],  'paytoname'=>strtoupper($rrqkey['cash_payto']), 'cashamount'=>$rrqkey['cash_amount'], 'adjustmentamount'=>$rrqkey['adjustment_amount'],'purpose'=>$rrqkey['purpose'],'description'=>$rrqkey['description'],'beneficiaryname'=>$rrqkey['beneficiary_name'],'balance'=>$balance,'idate'=>date('d-M-Y', strtotime($rrqkey['idate'])));
	 	$imtmpsno++;
 	}
 	usort($rrct, function($a, $b) {
	    return $b['dbsno'] <=> $a['dbsno'];  // Sort in ascending order
	});
	// print_r($rrct);
	echo json_encode($rrct);	
}else if($action=="missingdocumentreferencedetails"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];
	$loginroleid=$_POST['loginroleid'];
	$reqdatefrom=$_POST['datefrom'];
	$reqdateto=$_POST['dateto'];
	$bdatecond="";
	if($reqdatefrom && $reqdateto){
		$bdatecond=" and m.bookdate>='".$reqdatefrom."' and m.bookdate<='".$reqdateto."'";
	}
	$rrct=array();
	$shortbyvalue=$reqdatefrom.'|'.$reqdateto;
	$mmtq=$conn->query("SELECT m.bookdate, m.book_amount, m.payto, b.reference_no, b.beneficiary_name, b.amount, b.purpose, b.purpose_code, b.invoice_type, b.invoice_no, b.invoicedate, b.description, b.documentation_path, b.sno FROM tbl_maintransaction_breakdown b RIGHT JOIN tbl_maintransaction m ON m.companyid = b.companyid AND m.subcompanyid = b.subcompanyid AND m.projectid = b.projectid AND m.reference_no = b.reference_no WHERE (m.tedit IS NULL OR m.tedit = '') AND (b.tedit IS NULL OR b.tedit = '') AND (b.documentation_path IS NULL OR b.documentation_path = '') AND m.companyid='".$companyid."' AND m.subcompanyid='".$subcompanyid."' AND m.projectid = '".$projectid."' $bdatecond ");
	$bookdate ="N/A";
	$bookamount ="0";
	$transactionstatus="<span class='text-warning'><i class='fa fa-clock'></i> pending";
	if($mmtq->num_rows>0){
		while ($mntkey =$mmtq->fetch_assoc()) {
			$bookdate = isValidDate($mntkey['bookdate'],'Y-m-d')==1?date('d-M-Y', strtotime($mntkey['bookdate'])):'N/A';
			$bookamount = $mntkey['book_amount'];
			$filepathname = $mntkey['documentation_path']!=""?'../'.$mntkey['documentation_path']:"";
			$filepath="";
			$filestatus="";
			if (file_exists($filepathname)) {
				$filestatus="<span class='text-success'><i class='fa fa-file'></i> uploaded</span>";
			    $filepath=$mntkey['documentation_path'];
			} else {
				$filestatus="<span class='text-warning'><i class='fa fa-clock'></i> Missing</span>";
			    $filepath='';
			}			

			$rrct[]=array('bsno'=>$mntkey['sno'], 'referenceno'=>$mntkey['reference_no'], 'bookdate'=>$bookdate, 'bookamount'=>$bookamount, 'paytoname'=>strtoupper($mntkey['payto']), 'beneficiaryname'=>$mntkey['beneficiary_name'], 'description'=>$mntkey['description'], 'filestatus'=>$filestatus, 'filepath'=>$filepath);
		}
	}
	
	echo json_encode($rrct);	
}else if($action=="updatemissingdocumentwithsno"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$dbsnoval=$_POST['dbsnoval'];
	$loginid=$_POST['loginid'];
	$loginname=$_POST['loginname'];	
	$mmtq=$conn->query("SELECT m.bookdate, m.book_amount, m.payto, b.reference_no, b.beneficiary_name, b.amount, b.purpose, b.purpose_code, b.invoice_type, b.invoice_no, b.invoicedate, b.description, b.documentation_path, b.sno FROM tbl_maintransaction_breakdown b RIGHT JOIN tbl_maintransaction m ON m.companyid = b.companyid AND m.subcompanyid = b.subcompanyid AND m.projectid = b.projectid AND m.reference_no = b.reference_no WHERE (m.tedit IS NULL OR m.tedit = '') AND (b.tedit IS NULL OR b.tedit = '') AND (b.documentation_path IS NULL OR b.documentation_path = '') AND m.companyid='".$companyid."' AND m.subcompanyid='".$subcompanyid."' AND m.projectid = '".$projectid."' and b.sno='".$dbsnoval."'");
	$rrst=array();
	if($mmtq->num_rows>0){
		while ($mntkey =$mmtq->fetch_assoc()) {
			$receiptpath ="";
			if (isset($_FILES['filepath']) && !$_FILES['filepath']['error']) 
			{
		        // Directory where the audio file will be saved
		        $receiptpathDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$mntkey['reference_no']."/";
		        
		        // Create the uploads directory if it doesn't exist
		        if (!is_dir($receiptpathDir)) {
		            mkdir($receiptpathDir, 0755, true);
		        }
		        
		        //file name
		        $temp = explode(".", $_FILES["filepath"]["name"]);
				$receiptname = round(microtime(true)) .date('His'). '.' . end($temp);

		        // $filetype = $_FILES['filepath']['type'];
		        // File path
		        $uploadFile = $receiptpathDir . $receiptname;
		        $receiptpath = str_replace("../", "", $uploadFile);
		        // Move the uploaded file to the specified directory
		        move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile);
		    }
		}
		if($receiptpath){
			$conn->query("UPDATE tbl_maintransaction_breakdown SET documentation_path='".addslashes($receiptpath)."' WHERE sno='".$dbsnoval."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst=array('status'=>'Success', 'message'=>'Missing file uploaded successfully');	
			}
		}else{
	    	$rrst=array('status'=>'Error', 'message'=>'File did not upload due source file path is incorrect, Please try again');
	    }
	}else{
		$rrst=array('status'=>'Error', 'message'=>'File did not upload due to somthing wrong, Please try again');
	}	
	echo json_encode($rrst);	
}else if($action=="proceedingforstockentry"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortbydata = $_POST['shortbydata'];
	if($shortbydata=="pending"){
		$shortbyvalue=" and is_purchaseforproduction='yes'";
	}else if($shortbydata=="pending"){
		$shortbyvalue=" and is_purchaseforproduction='completed'";
	}else if($shortbydata=="all"){
		$shortbyvalue="";
	}
	$bbnkqq=$conn->query("select sno, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, paytoid, payto FROM tbl_maintransaction WHERE  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='') and is_purchaseforproduction<>'' ".$shortbyvalue." ");
	$blhtml="";
	if($bbnkqq->num_rows>0){
		while($pprow=$bbnkqq->fetch_assoc()){
			$blhtml.="<tr data-prcdbsno_tr='".$pprow['sno']."'><td data-label='Select'><input type='radio' name='pfpreqradio' value='".$pprow['sno']."' /></td><td data-label='Date' class='pfpbookdate_btd'>".date('d-M-Y', strtotime($pprow['bookdate']))."</td><td data-label='Reference No' class='referenceno_btd'>".$pprow['reference_no']."</td><td data-label='Payto Name' class='paytoname_btd' data-pfppaytoid='".$pprow['paytoid']."'>".$pprow['payto']."</td><td data-label='Request Amount' class='pfpbookamount_btd'>".$pprow['book_amount']."</td></tr>";
		}
	}else{
		$blhtml.="<tr><td colspan='5' style='text-align:center;'>Data not available</td></tr>";
	}
	echo $blhtml;
}else if($action=="advancepayment_stock"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortbydata = $_POST['shortbydata'];
	$purposecode = $_POST['purposecode'];
	if($shortbydata=="pending"){
		$shortbyvalue=" and is_purchaseforproduction='yes'";
	}else if($shortbydata=="pending"){
		$shortbyvalue=" and is_purchaseforproduction='completed'";
	}else if($shortbydata=="all"){
		$shortbyvalue="";
	}
	$bbnkqq=$conn->query("SELECT t.reference_no,  t.bookdate,ifnull(t.transaction_date,''), t.payto,b.amount,b.taxableamount, b.sno,b.description,ifnull(b.documentation_path,'') as doc_path,b.purpose_category,b.purpose_subcategory, b.purpose , b.invoice_type,b.invoice_no FROM tbl_maintransaction t join tbl_maintransaction_breakdown b on t.reference_no=b.reference_no   WHERE  t.companyid='".$companyid."' and t.subcompanyid='".$subcompanyid."' and t.projectid='".$projectid."' and (t.tedit is null or t.tedit='') and t.is_purchaseforproduction<>'' ".$shortbyvalue );
	$blhtml="";
	if($bbnkqq->num_rows>0){
		while($pprow=$bbnkqq->fetch_assoc()){
			$blhtml.="<tr data-docpath='".$pprow['doc_path']."'><td data-label='Select'><input type='radio' name='pfpreqradio' value='".$pprow['sno']."' /></td><td data-label='Date' class='pfpbookdate_btd'>".date('d-M-Y', strtotime($pprow['bookdate']))."</td><td data-label='Payto Name' class='paytoname_btd' data-pfppaytoid='".$pprow['paytoid']."'>".$pprow['payto']."</td><td data-label='Invoice No' class='referenceno_btd'>".$pprow['invoice_no']."</td><td data-label='Request Amount' class='pfptaxamount_btd'>".$pprow['taxableamount']."</td><td data-label='Reference No' class='reference_no'>".$pprow['reference_no']."</td></tr>";
		}
	}else{
		$blhtml.="<tr><td colspan='6' style='text-align:center;'>Data not available</td></tr>";
	}
	echo $blhtml;
}else if($action=="tellyformattransactions_htmlview"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$datefrom = $_POST['datefrom'];
	$dateto = $_POST['dateto'];
	// $datefrom = "2024-10-01";
	// $dateto = "2024-12-31";
	$dateval = $datefrom.'|'.$dateto;
	$miand=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"betweendatewillall",$dateval);
	// print_r($miand);
	$tlfrow="";
	if(count($miand)>0){
		foreach ($miand as $miandkey) {
			$interbanktransfertoname="";
			$interbanktransferamount=0;
			$transferedamount=0;
			$transactiondate = "";
			$paymentpaystatus="";
			if(strtolower($miandkey['is_intermediate_transfer'])=="yes" && strtolower($miandkey['is_intermediate_transfer'])!="imprest"){
				$transferedamount=0;
				$interbanktransferamount = $miandkey['transfered_amount'];
				$interbanktransfertoname = $miandkey['paytobankname'];
			}else{
				$transferedamount=$miandkey['transfered_amount'];
				$interbanktransferamount = 0;
				$interbanktransfertoname="";
			}
			$datetr ="";
			if($miandkey['transactionno']){
				$paymentpaystatus="PAID";
				$trbgcolor="#29d664";
				if($miandkey['transaction_date']){
					$datetr = date('Y-m-d', strtotime($miandkey['transaction_date']));
					$transactiondate = date('d-M-Y', strtotime($miandkey['transaction_date']));
				}
			}else{
				$trbgcolor="";
				$datetr = date('Y-m-d', strtotime($miandkey['bookdate']));
				if($miandkey['bookdate']){
					$transactiondate = date('d-M-Y', strtotime($miandkey['bookdate']));
				}
				$paymentpaystatus="PAY";
			}

			$minbrk=get_maintransactionsbreakdown_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$miandkey['reference_no']);
			if(count($minbrk)==1){
				foreach ($minbrk as $minbrkkey) {
					$invoicedate=$minbrkkey['invoicedate']!=""?date('d-M-Y', strtotime($minbrkkey['invoicedate'])):"";
					$tdsamount = $minbrkkey['tds_amount']!=""?$minbrkkey['tds_amount']:0;
					$amountwithtds = round($minbrkkey['amount'] + $tdsamount, 2);					
					$remark="";
					$cgst ="";
					$sgst ="";
					$igst ="";
					$total ="";
					$tlfrow.="<tr data-datetr=".$datetr." style='background-color: ".$trbgcolor."'>
						<td class='d-none noexport'>".$datetr."</td>
						<td>".$transactiondate."</td>
						<td>Payment</td>
						<td>".$minbrkkey['invoice_no']."</td>
						<td>".$invoicedate."</td>
						<td>".$miandkey['payfrombankname']."</td>
						<td>".$minbrkkey['provided_code']."</td>
						<td>".$minbrkkey['purpose']."</td>
						<td>".$miandkey['payto']."</td>
						<td>".$amountwithtds."</td>
						<td>".$tdsamount."</td>
						<td>".$transferedamount."</td>
						<td>".$interbanktransferamount."</td>
						<td>".$interbanktransfertoname."</td>
						<td>".$minbrkkey['description']."</td>
						<td>".$paymentpaystatus."</td>
						<td>".$miandkey['transactionno']."</td>
						<td>".$remark."</td>
						<td>".$miandkey['taxableamount']."</td>
						<td></td>
						<td>".$cgst."</td>
						<td>".$sgst."</td>
						<td>".$igst."</td>
						<td>".$total."</td>
					</tr>";
				}
			}else if(count($minbrk)>1){
				$bkc=0;
				$descriptioncombined="";
				$tdsamount=0;
				$amountwithtds=0;
				$remark="";
				$cgst=0;
				$sgst=0;
				$igst=0;
				$total=0;
				foreach ($minbrk as $minbrkkey) {
					$invoicedate=$minbrkkey['invoicedate']!=""?date('d-M-Y', strtotime($minbrkkey['invoicedate'])):"";
					$tdsamount = $minbrkkey['tds_amount']!=""?$minbrkkey['tds_amount']:0;
					$amountwithtds = round($minbrkkey['amount'] + $tdsamount, 2);					
					$tlfrow.="<tr data-datetr=".$datetr." style='background-color: ".$trbgcolor."'>
						<td class='d-none noexport'>".$datetr."</td>
						<td>".$transactiondate."</td>
						<td>Payment</td>
						<td>".$minbrkkey['invoice_no']."</td>
						<td>".$invoicedate."</td>
						<td>".$miandkey['payfrombankname']."</td>
						<td>".$minbrkkey['provided_code']."</td>
						<td>".$minbrkkey['purpose']."</td>
						<td>".$miandkey['payto']."</td>
						<td>".$minbrkkey['amount']."</td>
						<td>".$tdsamount."</td>
						<td>0</td>
						<td>".$interbanktransferamount."</td>
						<td>".$interbanktransfertoname."</td>
						<td>".$minbrkkey['description']."</td>
						<td>".$paymentpaystatus."</td>
						<td>".$miandkey['transactionno']."</td>
						<td>".$remark."</td>
						<td>".$miandkey['taxableamount']."</td>
						<td></td>
						<td>".$cgst."</td>
						<td>".$sgst."</td>
						<td>".$igst."</td>
						<td>".$total."</td>
					</tr>";
					$bkc++;
					$descriptioncombined .=$minbrkkey['description'];
				}
				if($bkc==count($minbrk)){
					$tlfrow.="<tr style='background-color: ".$trbgcolor."'>
						<td>".$transactiondate."</td>
						<td>Payment</td>
						<td></td>
						<td></td>
						<td>".$miandkey['payfrombankname']."</td>
						<td></td>
						<td></td>
						<td>".$miandkey['payto']."</td>
						<td>0</td>
						<td>0</td>
						<td>0</td>
						<td>".$miandkey['transfered_amount']."</td>
						<td></td>
						<td>".$descriptioncombined."</td>
						<td>".$paymentpaystatus."</td>
						<td>".$miandkey['transactionno']."</td>
						<td>".$remark."</td>
						<td>".$miandkey['taxableamount']."</td>
						<td></td>
						<td>".$cgst."</td>
						<td>".$sgst."</td>
						<td>".$igst."</td>
						<td>".$total."</td>
					</tr>";
				}
			}
		}
	}else{
		$tlfrow.="<tr><td colspan='23' class='text-center'>No data available</td></tr>";
	}
	// echo $tlfrow;
	echo sortHtmlTableRowsByCell($tlfrow, 0, true, false);
}else if($action=="getadvicedata") {
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$advicelist=array(); 
	$qryslt=$conn->query("Select crdr_adviceno,ReceivingCompany,Receivedinaccount_id,Receivedinaccount_name,CategoryofService,HSNnumber,BillnumberOrRemittanceID,event_date,event_type,Currency,BillAmount,Invoicenumberinforeigncurrency,InwardRemittancereceivedfromclient,Additionalinformation_1,Additionalinformation_2,CGSTwithdrawnAmount,SGSTWithdrawnAmount,BankChargesinRupees,WithdrawnfromBankAccount_id,WithdrawnfromBankAccount_name,CreditAmountinforeigncurrency,ExchangeRate,AmountCreditedinIndianRupees,filepath , credit_amount_json, clientid ,ifnull(reconciliation,'') as reconciliationstatus from tbl_foreign_remittance_taxinvoice_creditdebitadvice where  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (status ='' or status is null)");
 
	if ($qryslt->num_rows>0) {
		while ($rowm=$qryslt->fetch_assoc()) {
			$advicelist[]=array('crdr_adviceno'=>$rowm['crdr_adviceno'],'ReceivingCompany'=>$rowm['ReceivingCompany'],'Receivedinaccount_id'=>$rowm['Receivedinaccount_id'],'Receivedinaccount_name'=>$rowm['Receivedinaccount_name'],'CategoryofService'=>$rowm['CategoryofService'],'HSNnumber'=>$rowm['HSNnumber'],'BillnumberOrRemittanceID'=>$rowm['BillnumberOrRemittanceID'],'event_date'=>$rowm['event_date'],'event_type'=>$rowm['event_type'],'Currency'=>$rowm['Currency'],'BillAmount'=>$rowm['BillAmount'],'Invoicenumberinforeigncurrency'=>$rowm['Invoicenumberinforeigncurrency'],'InwardRemittancereceivedfromclient'=>$rowm['InwardRemittancereceivedfromclient'],'Additionalinformation_1'=>$rowm['Additionalinformation_1'],'Additionalinformation_2'=>$rowm['Additionalinformation_2'],'CGSTwithdrawnAmount'=>$rowm['CGSTwithdrawnAmount'],'SGSTWithdrawnAmount'=>$rowm['SGSTWithdrawnAmount'],'BankChargesinRupees'=>$rowm['BankChargesinRupees'],'WithdrawnfromBankAccount_id'=>$rowm['WithdrawnfromBankAccount_id'],'WithdrawnfromBankAccount_name'=>$rowm['WithdrawnfromBankAccount_name'],'CreditAmountinforeigncurrency'=>$rowm['CreditAmountinforeigncurrency'],'ExchangeRate'=>$rowm['ExchangeRate'],'AmountCreditedinIndianRupees'=>$rowm['AmountCreditedinIndianRupees'],'filepath'=>$rowm['filepath'] ,'credit_amount_json'=>$rowm['credit_amount_json'] ,'clientid'=>$rowm['clientid'] ,'reconciliationstatus'=>$rowm['reconciliationstatus'] );
		}
	}

	echo json_encode($advicelist);
}else if($action=="tdshistoryofbeneficiary"){
	$companyid  = $_POST['companyid'];
	$subcompanyid  = $_POST['subcompanyid'];
	$projectid  = $_POST['projectid'];
	$datefrom  = $_POST['datefrom'];
	$dateto  = $_POST['dateto'];
	$shortby  = $_POST['shortby'];
	$shortbyvalue  = $_POST['shortbyvalue'];
	$tdsdata=gettdsdetails($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto,$shortby,$shortbyvalue);
	echo json_encode($tdsdata);
}else if($action=="postpaymenthistorywetweendate"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$datefrom = $_POST['datefrom'];
	$dateto = $_POST['dateto'];
	$sscont = $conn->query("SELECT sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, IFNULL(paytoid, '') AS paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_expencesinexcelsheet WHERE (tedit IS NULL OR tedit = '') AND (status IS NULL OR status = '') AND company_id = '$companyid' AND subcompany_id = '$subcompanyid' AND project_id = '$projectid' AND total_amount > 0 AND DATE(date) BETWEEN '$datefrom' AND '$dateto'");
	$rrst=array();
	if($sscont->num_rows>0){
		while($rowr=$sscont->fetch_assoc()){
			$rrst[]=$rowr;
		}
	}
	echo json_encode($rrst);
}
?>