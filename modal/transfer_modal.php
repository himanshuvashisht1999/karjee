<?php 
ini_set('display_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="pendingtrasferlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$ptnslist=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"peding_utrno_update_withprojectid",$projectid);
	echo json_encode($ptnslist);
}else if($action=="readytotransfersalarylist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$ptnslist = get_maintransactions_details($conn, $companyid, $subcompanyid, $projectid, "peding_utrno_update_withprojectid", $projectid);
	$filteredList = [];
	if (count($ptnslist) > 0) {
	    foreach ($ptnslist as $ptkey) {
	        $refNo = $ptkey['reference_no'];
	        $result = $authconn->query("SELECT requestno FROM hrmplus.hrm_EmployeeCalculatedSalary WHERE requestno = '".$authconn->real_escape_string($refNo)."'");
	        if ($result && $result->num_rows > 0) {
	            $filteredList[] = $ptkey; // Only include if match found
	        }
	    }
	}
	echo json_encode($filteredList);
}else if($action=="updateutrno"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$utrno = $_POST['utrno'];
	$utrdate = $_POST['utrdate'];
	$dbsno = $_POST['dbsno'];
	$ssq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE sno='".$dbsno."'");
	$rrst=array();
	if($ssq->num_rows==1){
		$ssqq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE transactionno='".addslashes($utrno)."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
		if($ssqq->num_rows>0){
			$rrst[]=array('status'=>'Error', 'message'=>'UTR number '.addslashes($utrno).' is already exist with other transation, Please check or enter valid urt number');
		}else{
			$conn->query("UPDATE tbl_maintransaction SET transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."' WHERE sno='".$dbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst[]=array('status'=>'Success', 'message'=>'UTR number update successfully');
			}else{
				$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update, Due invalid or already updated, Please refresh web page');
			}
		}
	}else if($ssq->num_rows==0){
		$rrst[]=array('status'=>'Error', 'message'=>'This transation not available in our data, Please refresh web page and try again');
	}else if($ssq->num_rows>1){
		$rrst[]=array('status'=>'Error', 'message'=>'Multiple entry available for This transation, Please contact to web admin');
	} 
	echo json_encode($rrst);
}else if($action=="updateutrnowithmultipleentry_old"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$payfromaccountid = $_POST['payfromaccountid'];
	$transferamount = $_POST['transferamount'];
	$loginname = $_POST['loginname'];
	$rrst=array();
	$frmaccount=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$payfromaccountid);
	$payfrmbnk="";
	if(count($frmaccount)>0){
		foreach ($frmaccount as $acctkey) {
			$payfrmbnk=",payfromaccountid='".$acctkey['accountid']."',payfromaccountno='".$acctkey['accountno']."',payfrombankname='".$acctkey['bankname']."'";
		}
	}

	$utrno = $_POST['utrno']!=""?$_POST['utrno']:date('Ymdhis')."_self";
	$utrdate = $_POST['utrdate'];
	$transactiontype = $_POST['transactiontype'];

	$ssqq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE transactionno='".addslashes($utrno)."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
	if($ssqq->num_rows>0){
		$rrst[]=array('status'=>'Error', 'message'=>'UTR number '.addslashes($utrno).' is already exist with other transation, Please check or enter valid urt number');
		echo json_encode($rrst);
		die();
	}

	$referencenodata = json_decode($_POST['referencenodata'],TRUE);
	$receiptpath="";
	if (isset($_FILES['transferreceiptpath']) && !$_FILES['transferreceiptpath']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = '../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/paymentreceipt/' . date('Ymd') . '/';

        
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
    }
	if(count($referencenodata)>1){
		$updq="";
		foreach ($referencenodata as $refkey) {
			$ssq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE sno='".$refkey['dbsno']."'");
			if($ssq->num_rows==1){
				$conn->query("UPDATE tbl_maintransaction SET transfered_amount='".addslashes($refkey['bookamount'])."',transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."', transaction_type='".strtolower($transactiontype)."', transferreceiptpath='".addslashes($receiptpath)."', transferedby='".$loginname."', uploadflag=null ".$payfrmbnk." WHERE sno='".$refkey['dbsno']."'");
				if(mysqli_affected_rows($conn)>0){
					$rrst[]=array('status'=>'Success', 'message'=>'UTR number updated successfully of reference no '.$refkey.'');
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update of reference no '.$refkey.', Due invalid or already updated, Please refresh web page');
				}
			}else if($ssq->num_rows==0){
				$rrst[]=array('status'=>'Error', 'message'=>'This transation not available  of reference no '.$refkey.' in our data, Please refresh web page and try again');
			}
		}
		
	}else if(count($referencenodata)==1){
		$updq="";
		foreach ($referencenodata as $refkey) {
			$ssq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE sno='".$refkey['dbsno']."'");
			if($ssq->num_rows==1){
				if($transferamount>0){
					$transferedamount = $transferamount;
				}else{
					$transferedamount = $refkey['bookamount'];
				}
				$conn->query("UPDATE tbl_maintransaction SET transfered_amount='".addslashes($transferedamount)."',transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."', transaction_type='".strtolower($transactiontype)."', transferreceiptpath='".addslashes($receiptpath)."', transferedby='".$loginname."', uploadflag=null ".$payfrmbnk." WHERE sno='".$refkey['dbsno']."'");
				if(mysqli_affected_rows($conn)>0){
					$rrst[]=array('status'=>'Success', 'message'=>'UTR number updated successfully of reference no '.$refkey.'');
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update of reference no '.$refkey.', Due invalid or already updated, Please refresh web page');
				}
			}else if($ssq->num_rows==0){
				$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available for reference no '.$refkey['referenceno'].' in our data, Please refresh web page and try again');
			}
		}
		
	}else{
		$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available, Please refresh web page and try again');
	}
	echo json_encode($rrst);
	updatepaidstatus_bills($conn, $companyid, $subcompanyid, $projectid);
}else if($action=="updateutrnowithmultipleentry___"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$payfromaccountid = $_POST['payfromaccountid'];
	$transferamount = $_POST['transferamount'];
	$loginname = $_POST['loginname'];
	$issplittransaction = $_POST['issplittransaction'];
	$rrst=array();
	$frmaccount=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$payfromaccountid);
	$payfrmbnk="";
	if(count($frmaccount)>0){
		foreach ($frmaccount as $acctkey) {
			$payfrmbnk=",payfromaccountid='".$acctkey['accountid']."',payfromaccountno='".$acctkey['accountno']."',payfrombankname='".$acctkey['bankname']."'";
		}
	}

	$utrno = $_POST['utrno']!=""?$_POST['utrno']:date('Ymdhis')."_self";
	$utrdate = $_POST['utrdate'];
	$transactiontype = $_POST['transactiontype'];

	$ssqq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE transactionno='".addslashes($utrno)."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
	if($ssqq->num_rows>0){
		$rrst[]=array('status'=>'Error', 'message'=>'UTR number '.addslashes($utrno).' is already exist with other transation, Please check or enter valid urt number');
		echo json_encode($rrst);
		die();
	}

	$referencenodata = json_decode($_POST['referencenodata'],TRUE);
	$receiptpath="";
	if (isset($_FILES['transferreceiptpath']) && !$_FILES['transferreceiptpath']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = '../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/paymentreceipt/' . date('Ymd') . '/';

        
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
    }
    

	if(count($referencenodata)>1){
		$updq="";
		foreach ($referencenodata as $refkey) {
			$ssq=$conn->query("SELECT sno, book_amount FROM tbl_maintransaction WHERE sno='".$refkey['dbsno']."'");
			if($ssq->num_rows==1){
				$conn->query("UPDATE tbl_maintransaction SET transfered_amount='".addslashes($refkey['bookamount'])."',transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."', transaction_type='".strtolower($transactiontype)."', transferreceiptpath='".addslashes($receiptpath)."', transferedby='".$loginname."', uploadflag=null ".$payfrmbnk." WHERE sno='".$refkey['dbsno']."'");
				if(mysqli_affected_rows($conn)>0){
					$rrst[]=array('status'=>'Success', 'message'=>'UTR number updated successfully of reference no '.$refkey.'');
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update of reference no '.$refkey.', Due invalid or already updated, Please refresh web page');
				}
			}else if($ssq->num_rows==0){
				$rrst[]=array('status'=>'Error', 'message'=>'This transation not available  of reference no '.$refkey.' in our data, Please refresh web page and try again');
			}
		}
		
	}else if(count($referencenodata)==1){
		$updq="";
		$bookamount=0;
		foreach ($referencenodata as $refkey) {
			$ssq=$conn->query("SELECT sno, book_amount, reference_no FROM tbl_maintransaction WHERE sno='".$refkey['dbsno']."'");
			if($ssq->num_rows==1){
				$mmrow=$ssq->fetch_assoc();
				$mainrefno = $mmrow['reference_no'];

				if($transferamount>0){
					$transferedamount = $transferamount;
					$bookamount = $mmrow['book_amount'];
				}else{
					$bookamount = $mmrow['book_amount'];
					$transferedamount = $refkey['bookamount'];
				}
				$bookamount = (float)$bookamount;
				$transferedamount = !empty($transferedamount) ? (float)$transferedamount : 0.0;
				$balanceamount = $bookamount - $transferedamount;
				if ($bookamount > 0 && $balanceamount > 100 && $issplittransaction=="yes") {
				    $copytr = copyTransaction($conn, $refkey['dbsno'], $transferedamount, $utrdate, $utrno, $loginname);
					if (!empty($copytr) && !empty($copytr['success']) && $copytr['success'] == 1) {
					    $ssq = $conn->query("SELECT sno, description FROM tbl_maintransaction_breakdown WHERE reference_no='" . $mainrefno . "'");

					    if ($ssq && $ssq->num_rows == 1) {
					        $bbrow = $ssq->fetch_assoc();
					        $newdescription = $bbrow['description'] . ' split with reference no ' . $copytr['newReferenceno'];

					        $conn->query("
					            UPDATE tbl_maintransaction_breakdown 
					            SET amount='" . addslashes($balanceamount) . "', 
					                description='" . $conn->real_escape_string($newdescription) . "', 
					                uploadflag=NULL 
					            WHERE sno='" . (int)$bbrow['sno'] . "'
					        ");
					    }

					    $conn->query("
					        UPDATE tbl_maintransaction 
					        SET book_amount='" . addslashes($balanceamount) . "', 
					            uploadflag=NULL 
					        WHERE sno='" . (int)$refkey['dbsno'] . "'
					    ");

					    if ($conn->affected_rows > 0) {
					        $rrst[] = [
					            'status'  => 'Success',
					            'message' => 'UTR number updated successfully of reference no ' . $mainrefno
					        ];
					    } else {
					        $rrst[] = [
					            'status'  => 'Error',
					            'message' => 'UTR number did not update of reference no ' . $mainrefno . ', Due to invalid or already updated, Please refresh web page'
					        ];
					    }
					}
				}else if ($bookamount > 0 && $balanceamount > 100) {
			    	$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update of reference no '.$refkey.', Due to bookamount and transfered amount is more than 100, If you want to split this amount than checked the split checkbox.');
				}else{
					$conn->query("UPDATE tbl_maintransaction SET transfered_amount='".addslashes($transferedamount)."',transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."', transaction_type='".strtolower($transactiontype)."', transferreceiptpath='".addslashes($receiptpath)."', transferedby='".$loginname."', uploadflag=null ".$payfrmbnk." WHERE sno='".$refkey['dbsno']."'");
					if(mysqli_affected_rows($conn)>0){
						$rrst[]=array('status'=>'Success', 'message'=>'UTR number updated successfully of reference no '.$refkey.'');
					}else{
						$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update of reference no '.$refkey.', Due invalid or already updated, Please refresh web page');
					}
				}
			}else if($ssq->num_rows==0){
				$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available for reference no '.$refkey['referenceno'].' in our data, Please refresh web page and try again');
			}
		}		
	}else{
		$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available, Please refresh web page and try again');
	}
	echo json_encode($rrst);
	updatepaidstatus_bills($conn, $companyid, $subcompanyid, $projectid);
}else if($action=="updateutrnowithmultipleentry"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$payfromaccountid = $_POST['payfromaccountid'];
	$transferamount = $_POST['transferamount'];
	$loginname = $_POST['loginname'];
	$issplittransaction = $_POST['issplittransaction'];
	$rrst=array();
	$frmaccount=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$payfromaccountid);
	$payfrmbnk="";
	if(count($frmaccount)>0){
		foreach ($frmaccount as $acctkey) {
			$payfrmbnk=",payfromaccountid='".$acctkey['accountid']."',payfromaccountno='".$acctkey['accountno']."',payfrombankname='".$acctkey['bankname']."'";
		}
	}

	$utrno = $_POST['utrno']!=""?$_POST['utrno']:date('Ymdhis')."_self";
	$utrdate = $_POST['utrdate'];
	$transactiontype = $_POST['transactiontype'];

	$ssqq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE transactionno='".addslashes($utrno)."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
	if($ssqq->num_rows>0){
		$rrst[]=array('status'=>'Error', 'message'=>'UTR number '.addslashes($utrno).' is already exist with other transation, Please check or enter valid urt number');
		echo json_encode($rrst);
		die();
	}

	$referencenodata = json_decode($_POST['referencenodata'],TRUE);
	$receiptpath="";
	if (isset($_FILES['transferreceiptpath']) && !$_FILES['transferreceiptpath']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = '../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/paymentreceipt/' . date('Ymd') . '/';

        
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
    }    

	if(count($referencenodata)>1){
		$updq="";
		foreach ($referencenodata as $refkey) {
			$ssq=$conn->query("SELECT sno, book_amount FROM tbl_maintransaction WHERE sno='".$refkey['dbsno']."'");
			if($ssq->num_rows==1){
				// $bbrow = $ssq->fetch_assoc();
				// if($bbrow['book_amount'])
				$conn->query("UPDATE tbl_maintransaction SET transfered_amount='".addslashes($refkey['bookamount'])."',transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."', transaction_type='".strtolower($transactiontype)."', transferreceiptpath='".addslashes($receiptpath)."', transferedby='".$loginname."', uploadflag=null ".$payfrmbnk." WHERE sno='".$refkey['dbsno']."'");
				if(mysqli_affected_rows($conn)>0){
					$rrst[]=array('status'=>'Success', 'message'=>'UTR number updated successfully of reference no '.$refkey.'');
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update of reference no '.$refkey.', Due invalid or already updated, Please refresh web page----multiple');
				}
			}else if($ssq->num_rows==0){
				$rrst[]=array('status'=>'Error', 'message'=>'This transation not available  of reference no '.$refkey.' in our data, Please refresh web page and try again');
			}
		}
		
	}else if(count($referencenodata)==1){
		$updq="";
		$bookamount=0;
		foreach ($referencenodata as $refkey) {
			$ssq=$conn->query("SELECT sno, book_amount, reference_no, beneficierie FROM tbl_maintransaction WHERE sno='".$refkey['dbsno']."'");
			if($ssq->num_rows==1){
				$mmrow=$ssq->fetch_assoc();
				
				$mainrefno = $mmrow['reference_no'];
				if($transferamount>0){
					$transferedamount = $transferamount;
					$bookamount = $mmrow['book_amount'];
				}else{
					$bookamount = $mmrow['book_amount'];
					$transferedamount = $refkey['bookamount'];
				}
				$bookamount = (float)$bookamount;
				$transferedamount = !empty($transferedamount) ? (float)$transferedamount : 0.0;
				$balanceamount = $bookamount - $transferedamount;
				if ($bookamount > 0 && $balanceamount > 100 && $issplittransaction=="yes") {
				    $copytr = copyTransaction($conn, $refkey['dbsno'], $transferedamount, $utrdate, $utrno, $loginname);
					if (!empty($copytr) && !empty($copytr['success']) && $copytr['success'] == 1) {
					    $ssq = $conn->query("SELECT sno, description FROM tbl_maintransaction_breakdown WHERE reference_no='" . $mainrefno . "'");
					    if ($ssq && $ssq->num_rows == 1) {
					        $bbrow = $ssq->fetch_assoc();
					        $newdescription = $bbrow['description'] . ' split with reference no ' . $copytr['newReferenceno'];

					        $conn->query("
					            UPDATE tbl_maintransaction_breakdown 
					            SET amount='" . addslashes($balanceamount) . "', 
					                description='" . $conn->real_escape_string($newdescription) . "', 
					                uploadflag=NULL 
					            WHERE sno='" . (int)$bbrow['sno'] . "'
					        ");
					    }

					    $conn->query("
					        UPDATE tbl_maintransaction 
					        SET book_amount='" . addslashes($balanceamount) . "', 
					            uploadflag=NULL 
					        WHERE sno='" . (int)$refkey['dbsno'] . "'
					    ");

					    if ($conn->affected_rows > 0) {
					        $rrst[] = [
					            'status'  => 'Success',
					            'message' => 'UTR number updated successfully of reference no ' . $mainrefno
					        ];
					    } else {
					        $rrst[] = [
					            'status'  => 'Error',
					            'message' => 'UTR number did not update of reference no ' . $mainrefno . ', Due to invalid or already updated, Please refresh web page'
					        ];
					    }
					}
				}else{
					if(strtolower($mmrow['beneficierie'])=="multiple" && $balanceamount>0){
						$rrst[] = ['status'  => 'Error', 'message' => 'This is multiple voucher reference no ' . $mainrefno . ', So Book amount and transfered should be same, Please refresh web page'];
						echo json_encode($rrst);
						die();
					}else{
						$conn->query("UPDATE tbl_maintransaction SET transfered_amount='".addslashes($transferedamount)."', book_amount='".addslashes($transferedamount)."',transactionno='".addslashes($utrno)."', transaction_date='".date('Y-m-d', strtotime($utrdate))."', transaction_type='".strtolower($transactiontype)."', transferreceiptpath='".addslashes($receiptpath)."', transferedby='".$loginname."', uploadflag=null ".$payfrmbnk." WHERE sno='".$refkey['dbsno']."'");
						if(mysqli_affected_rows($conn)>0){
							$conn->query("UPDATE tbl_maintransaction_breakdown SET amount='".addslashes($transferedamount)."' where reference_no='".$mainrefno."' LIMIT 1");
							$rrst[]=array('status'=>'Success', 'message'=>'UTR number updated successfully of reference no '.$refkey);
						}else{
							$rrst[]=array('status'=>'Error', 'message'=>'UTR number did not update of reference no '.$refkey.', Due invalid or already updated, Please refresh web page');
						}
					}
				}
			}else if($ssq->num_rows==0){
				$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available for reference no '.$refkey['referenceno'].' in our data, Please refresh web page and try again');
			}
		}		
	}else{
		$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available, Please refresh web page and try again');
	}
	echo json_encode($rrst);
	updatepaidstatus_bills($conn, $companyid, $subcompanyid, $projectid);
}else if($action=="maintransactiondetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$reference_no = $_POST['reference_no'];
	$mbrk=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$reference_no);
	echo json_encode($mbrk);
}else if($action=="maintransactionbreakdowndetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$reference_no = $_POST['reference_no'];
	$mbrk=get_maintransactionsbreakdown_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$reference_no);
	echo json_encode($mbrk);
}else if($action=="cancelpaymentsupdate"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$rejectremark = $_POST['rejectremark']." update by ".$loginname." on date ".date('Y-m-d h:i:s');
	$rrst=array();
	$referencenodata = json_decode($_POST['referencenodata'],TRUE);
	// print_r($referencenodata);
	// die();
	if(count($referencenodata)>0){
		foreach ($referencenodata as $refkey) {
			$ssq=$conn->query("SELECT sno FROM tbl_maintransaction WHERE sno='".$refkey['dbsno']."'");
			if($ssq->num_rows==1){
				$conn->query("UPDATE tbl_maintransaction SET transactionno='cancel',tedit='cancel on date ".date('Y-m-d')."',tedit_remark='".addslashes($rejectremark)."', transferedby='".$loginname."', uploadflag=null WHERE sno='".$refkey['dbsno']."'");
				if(mysqli_affected_rows($conn)>0){
					$rrst[]=array('status'=>'Success', 'message'=>'Transaction cancel updated successfully of reference no '.$refkey['referenceno'].'');
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>'Payment did not cancel of reference no '.$refkey['referenceno'].', Due invalid or already updated, Please refresh web page');
				}
			}else if($ssq->num_rows==0){
				$rrst[]=array('status'=>'Error', 'message'=>'Payment details did not available  of reference no '.$refkey['referenceno'].' in our data, Please refresh web page and try again');
			}
		}
		
	}else{
		$rrst[]=array('status'=>'Error', 'message'=>'The transaction details are not available, Please refresh web page and try again');
	}
	echo json_encode($rrst);
}else if($action=="confirmInterBankTransferedAndUpdate"){
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$paytoid = $_POST['paytoid'] ?? '';
	$purposecode = $_POST['purposecode'] ?? '';
	$referenceno = $_POST['referenceno'] ?? '';
	$isinterbank = strtolower(trim($_POST['isinterbank'] ?? ''));

	$mintrd = get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$referenceno);
	$paymenttype = $mintrd[0]['paymenttype'];
	$response = ['status' => 'error', 'message' => 'Unknown error'];

	// Basic validation
	if (empty($referenceno) || empty($paytoid)) {
	    $response['message'] = "Missing required fields: reference number or payto ID.";
	    echo json_encode($response);
	    exit;
	}

	// PayTo variables
	$paytoname = "";
	$paytoaccountid = "";
	$paytoaccountno = "";
	$paytoifsc = "";
	$paytobankname = "";
	$is_intermediate_transfer = "";

	// Purpose variables
	$purpose_category = "";
	$purpose_categoryid = "";
	$purpose_subcategory = "";
	$purpose_subcategoryid = "";
	$purpose = "";
	$purpose_code = "";

	try {
	    if ($isinterbank === "yes") {

	        // Fetch interbank payee info
	        $ssbnk = getaccountinformation($conn, $companyid, $subcompanyid, $projectid, "onlybankpaytoid", $paytoid);
	        if (empty($ssbnk)) {
	            throw new Exception("No interbank account information found for PayTo ID: $paytoid");
	        }

	        $paytoname = $ssbnk[0]['paytoname'];
	        $paytoaccountid = $ssbnk[0]['accountid'];
	        $paytoaccountno = $ssbnk[0]['accountno'];
	        $paytoifsc = $ssbnk[0]['ifsccode'];
	        $paytobankname = $ssbnk[0]['bankname'];
	        $is_intermediate_transfer = 'yes';

	    } elseif ($isinterbank === "no") {

	        // Fetch purpose info
	        if (!empty($purposecode)) {
	            $ppg = getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $purposecode);
	            if (count($ppg) === 1) {
	                $purpose_category = $ppg[0]['pcategory'];
	                $purpose_categoryid = $ppg[0]['pcategorycode'];
	                $purpose_subcategory = $ppg[0]['psub_category'];
	                $purpose_subcategoryid = $ppg[0]['psub_categoryid'];
	                $purpose = $ppg[0]['purpose'];
	                $purpose_code = $ppg[0]['purposecode'];
	                $paymenttype = $ppg[0]['pcategory'];
	            } else {
	                $response['status'] = 'error';
	                $response['message'] = "Purpose code missing, invalid, or duplicate entry found.";
	                echo json_encode($response);
	                exit;
	            }
	        } else {
	            $response['status'] = 'error';
	            $response['message'] = "Purpose code is missing or invalid.";
	            echo json_encode($response);
	            exit;
	        }

	        // Fetch payee info (non-interbank)
	        $ssbnk = getpaytoinfo($conn, $companyid, $subcompanyid, "paytoid", $paytoid);
	        if (empty($ssbnk)) {
	            throw new Exception("No payee information found for PayTo ID: $paytoid");
	        }

	        $paytoname = $ssbnk[0]['paytoname'];
	        $paytoaccountid = "";
	        $paytoaccountno = $ssbnk[0]['toaccountno'];
	        $paytoifsc = $ssbnk[0]['tobankifsc'];
	        $paytobankname = $ssbnk[0]['tobankname'];
	        $is_intermediate_transfer = null;

	    } else {
	        throw new Exception("Invalid value for isinterbank. Expected 'yes' or 'no'.");
	    }

	    // ✅ Update main transaction
	    $stmt = $conn->prepare("
	        UPDATE tbl_maintransaction 
	        SET payto=?, paytoid=?, paytoaccountno=?, paytobankname=?, paytoifsc=?, 
	            paytoaccountid=?, is_intermediate_transfer=?, paymenttype=?, uploadflag=NULL
	        WHERE reference_no=?
	    ");
	    $stmt->bind_param(
	        "sssssssss",
	        $paytoname,
	        $paytoid,
	        $paytoaccountno,
	        $paytobankname,
	        $paytoifsc,
	        $paytoaccountid,
	        $is_intermediate_transfer,
	        $paymenttype,
	        $referenceno
	    );
	    if ($stmt->execute()) {
	        // ✅ If non-interbank, update purpose breakdown
	        if ($isinterbank === "no") {
	            $stmt2 = $conn->prepare("
	                UPDATE tbl_maintransaction_breakdown 
	                SET purpose_category=?, purpose_categoryid=?, purpose_subcategory=?, purpose_subcategoryid=?, 
	                    purpose=?, purpose_code=?, uploadflag=NULL
	                WHERE reference_no=?
	            ");
	            $stmt2->bind_param(
	                "sssssss",
	                $purpose_category,
	                $purpose_categoryid,
	                $purpose_subcategory,
	                $purpose_subcategoryid,
	                $purpose,
	                $purpose_code,
	                $referenceno
	            );
	            $stmt2->execute();
	            $stmt2->close();
	        }
	        $response['status'] = 'success';
	        $response['message'] = 'Transaction updated successfully.';
	    } else {
	        $response['message'] = 'Failed to update transaction.';
	    }
	    $stmt->close();
	} catch (Exception $e) {
	    $response['status'] = 'error';
	    $response['message'] = $e->getMessage();
	}
	echo json_encode($response);
}
?>