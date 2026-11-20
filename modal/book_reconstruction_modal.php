<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../functions/function_savetodb_recBook.php');
$action=$_GET['action'];
if($action=="ReconstructionUpdateFromBankData"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype = $_POST['transactiontype'];
	
	$paytoid = $_POST['paytoid'];
	$paytonametxt = $_POST['paytonamemanual'];

	$purposecode = $_POST['purposecode'];
	$purposenametxt = $_POST['purpose_manual'];
	$banksno = $_POST['banksno'];
	$remarks = $_POST['remarktxt'];
	$loginid = $_POST['loginid'];
	// $closetobooksnoarray = json_decode($_POST['closetobooksno'], true);
	if (isset($_FILES['filepath']) && $_FILES['filepath']['error'] === UPLOAD_ERR_OK) {   
        $uploadDir2 = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/documnet/".date('Ymd')."/";        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir2)) {
            mkdir($uploadDir2, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["filepath"]["name"]);
        $filename  = generateUUID(). '.' . end($temp);

        // File path
        $uploadFile = $uploadDir2 . $filename;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile)){
        		$uploadFilename = str_replace("../", "", $uploadFile);

        } else {
            $uploadFilename="";
            $arrayresult[]=array('status'=>'Error','message' => 'Failed to move uploaded file', 'instructionid'=>'');
                 echo json_encode($arrayresult);
                exit; 
        }
    }

	if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        // Directory where the audio file will be saved 
         $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }        
        $filename=generateUUID().basename($_FILES['audio']['name']);
        $filetype = $_FILES['audio']['type'];
        // File path
        $audiofile = $uploadDir . $filename;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['audio']['tmp_name'], $audiofile)) {
            $uploadaudiofile=str_replace("../", "", $audiofile);
        } else {
            $uploadaudiofile="";
            $arrayresult[]=array('status'=>'Error','message' => 'Failed to move uploaded file', 'instructionid'=>'');
                 echo json_encode($arrayresult);
                exit; 
        }
    }
	// $messagarr=array();
	$messagarr = reconstructbookwith_manualBySimilarData($mconn,$companyid,$subcompanyid,$projectid,$paytoid,$paytonametxt,$purposecode,$purposenametxt,$remarks,$banksno,$uploadFilename,$uploadaudiofile,$loginid);
	echo json_encode($messagarr);
}else if($action=="ReconstructionUpdateFromBankDataByAdmin"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype = $_POST['transactiontype'];
	$paytoid = $_POST['paytoid'];
	$paytonametxt = $_POST['paytonamemanual'];	
	$purposecode = $_POST['purposecode'];
	$purposenametxt = $_POST['purposenamemanual'];
	
	$remarks = $_POST['remarktxt'];
	$loginid = $_POST['loginid'];
	$closetobooksnoarray = json_decode($_POST['closetobooksno'], true);
	// $messagarr=array();
	$messagarr = reconstructbookwith_manualBySimilarDataByAdmin($mconn,$companyid,$subcompanyid,$projectid,$paytoid,$paytonametxt,$purposecode,$purposenametxt,$remarks,$closetobooksnoarray,$loginid);
	echo json_encode($messagarr);
}else if($action=="CreateBook_FromReconstructedBankData"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$closetobooksnoarray = json_decode($_POST['closetobooksno'], true);
	$messagarr=array();
	if($closetobooksnoarray!=""){
		foreach ($closetobooksnoarray as $banksno) {
			$ddsd = reconstructbook_FromReconstructedData($mconn,$companyid,$subcompanyid,$projectid,$banksno,$loginid);
			$messagarr[] = $ddsd;
	    }
	}else{
		$messagarr[] = array('status'=>'Error', 'message'=>'Missing payto or purpose details');
	}
	echo json_encode($messagarr);
}else if($action=="sendtoemailaddress_adminlist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$lguq=$conn->query("SELECT u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid  where u.isActive='yes' and p.companyid='".$companyid."' and p.subcompanyid='".$subcompanyid."' and u.loginroleid<4  and u.emailid<>''");
	$ullist=array();
	if($lguq->num_rows>0){
		while($lurow=$lguq->fetch_assoc()){
			$ullist[]=array('loginname'=>$lurow['loginname'], 'loginid'=>$lurow['loginid'], 'loginroleid'=>$lurow['loginroleid'], 'loginrolename'=>$lurow['loginrolename'], 'mobileno'=>($lurow['mobileno']?$lurow['mobileno']:"N/A"), 'emailid'=>($lurow['emailid']?$lurow['emailid']:""));
		}
	}
	echo json_encode($ullist);
}else if($action=="sendemail_forreconstructionbankentry"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginname=$_POST['loginname'];
	$datefrom = $_POST['datefrom'];
	$dateto = $_POST['dateto'];
	$accountid = $_POST['accountid'];
	// $sendtodataarray=json_decode($_POST['sendtodataarray'],true);
	$dbsnolist= explode(",", $_POST['dbsnolist']);
	
	$sendtoemailloginid=$_POST['sendtoemailloginid'];
	$sendtoemailaddress=$_POST['sendtoemailaddress'];
	$routurl = $_POST['routurl'];

	$rrct=array();
	$lgp=loginuserdetailsfrommaster($authconn,"","loginid",$sendtoemailloginid);
	$qstring="";
	$lgemailid="";
	if(count($lgp)>0){
		foreach ($lgp as $lgpkey) {
			$lgemailid=$lgpkey['emailid'];
			$qstring = $lgpkey['loginid'] . "|" . $lgpkey['loginroleid'] . "|" . $companyid . "|" . $subcompanyid . "|" . $projectid . "|" . $lgpkey['loginrolename'];
		}
	}

	// $sendtoemailaddress = $sendtoemailaddress;
	// Encode the query string using base64
	$enc_qstring = base64_encode($qstring);

	// Concatenate the original query string with the encoded one
	$key = $qstring . "|" . $enc_qstring;

	// Encode the key using base64
	$enc_key = base64_encode($key);
	if(count($dbsnolist)>0){
		foreach ($dbsnolist as $itemvalue) {
			$dbsnoValue = trim($itemvalue, "\" \n\r\t"); // Remove quotes and whitespace
			$ccks=$conn->query("SELECT sno,amounttype as transactiontype,description_bank,total_amount,date FROM tbl_closetobook WHERE company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and account_id='".$accountid."' and sno='".$dbsnoValue."'");
			if($ccks->num_rows>0){
				$ccrow=$ccks->fetch_assoc();
				$ssinst=insertGroupedClientData($conn,$companyid,$subcompanyid,$projectid,$sendtoemailaddress,$datefrom,$dateto,$ccrow['description_bank'],$ccrow['transactiontype'],$accountid,$ccrow['sno'],$ccrow['date'],$ccrow['total_amount']);
				 //print_r($ssinst);
				if($ssinst['status']=="success" || $ssinst['status']=="Success"){
					$conn->query("UPDATE tbl_closetobook SET reconstructstatus='sendtoclient' WHERE sno='".$ccrow['sno']."'");
				}
			}else{
				$rrct=array('status'=>'Error', 'message'=>'bank data did not available in closetobook data');
			}
		}
	}else{
		$rrct=array('status'=>'Error', 'message'=>'bank data missing');
	}

	if(sendemail_forReconstructBankEntry($conn,$companyid,$subcompanyid,$projectid,$sendtoemailaddress,$routurl,$enc_key)){		

		$rrct=array('status'=>'Success', 'message'=>'Email sent successfully');
	}else{
		$rrct=array('status'=>'Error', 'message'=>'Email did not send, Please try again');
	}	
	echo json_encode($rrct);	
}else if($action=="getBookReconstructFinancialYears"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype = $_POST['transactiontype'];
	$ffn=getprojectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	// print_r($ffn);
	$finstartmonth=4;
	if(count($ffn)>0){
		$finstartmonth = $ffn[0]['finyr'];
	}
	$accinfo=getBookReconstructFinancialYears($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$finstartmonth);
	echo json_encode($accinfo);
}else if($action=="BookReconstructAccountlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$datefrom = $_POST['datefrom'];
	$dateto = $_POST['dateto'];
	$transactiontype = $_POST['transactiontype'];
	$accinfo=getunrecounciledaccountlist($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto,$transactiontype);
	echo json_encode($accinfo);
}else if($action=="Accountlist_UnReconstructDataByClient"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype = $_POST['transactiontype'];
	$accinfo=GetUnReconstructAccountlist($conn,$companyid,$subcompanyid,$projectid,$transactiontype);
	echo json_encode($accinfo);
}else if($action=="BookReconstructPurposeCodefrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	$subcategoryidval = $_POST['subcategoryidval'];	
	if($categoryidval =="Auto Deduction"  && $subcategoryidval=="purposesubcategory"){
		$creditprp=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposesubcategory",$categoryidval);
	}else if($categoryidval =="all"  && $subcategoryidval=="all"){
		$creditprp=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	}else if($categoryidval!="" && $subcategoryidval!=""){
		$creditprp=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"subcategoryid",$subcategoryidval);
	}else if($categoryidval!="" && $subcategoryidval==""){
		$creditprp=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecategoryid",$categoryidval);
	}else{
		$creditprp=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	}
	echo json_encode($creditprp);
}else if($action=="BookReconstructPayto"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytolist=getpaytoinfo($conn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($paytolist);
}else if($action=="UnreconstructwithAccountid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid=$_POST['accountid'];
	$transactiontype=$_POST['transactiontype'];
	$cldata=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,"unreconstruct_withaccountid",$accountid);
	echo json_encode($cldata);
}else if($action=="readyto_reconstructformaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype=$_POST['transactiontype'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$cldata=getReconciledReconstructedData($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$shortby,$shortbyvalue);
	echo json_encode($cldata);
}else if($action=="unreconstruct_closetobookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype=$_POST['transactiontype'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$cldata=closetobookdatadetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$shortby,$shortbyvalue);
	echo json_encode($cldata);
}else if($action=="UnReconstructDataForReconstructByClient"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype=$_POST['transactiontype'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$ccldata=getClientReconstructionData($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$shortby,$shortbyvalue);
	echo json_encode($ccldata);
}else if($action=="ReconstructedHistoryData_Callback"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype=$_POST['transactiontype'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	if($transactiontype=="deposit" || $transactiontype=="creditside"){

	}else{
		$ccldata=getReconstructed_WithdrawalHistory($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
	}
	echo json_encode($ccldata);
}else if($action=="SimilarPaytoText_MatchedwithMaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$ppty = getpaytoinfo($conn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($ppty);
}else if($action=="SimilarPurposeText_MatchedwithMaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype = $_POST['transactiontype'];
	$uupt = getUniquePurposeListForMapping($conn,$companyid,$subcompanyid,$projectid,$transactiontype);
	echo json_encode($uupt);
}else if($action=="UpdatePurposeText_WithMasterData"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposename = $_POST['purposename'];
	$purposecode = $_POST['purposecode'];
	$subcategoryid = $_POST['subcategoryid'];
	$loginname = $_POST['loginname'];
	$banksno = $_POST['banksno'];
	$transactiontype=$_POST['transactiontype'];
	$rresult=[];
	if($purposecode && $transactiontype=="deposit"){
		$pppcd=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
		// print_r($pppcd);
		if(count($pppcd)>0){
			$categoryid = $pppcd[0]['pcategorycode'];
			$categoryname = $pppcd[0]['pcategory'];
			$subcategoryid = $pppcd[0]['psub_categoryid'];
			$subcategoryname = $pppcd[0]['psub_category'];
			$purposecode = $pppcd[0]['purposecode'];
			$purposename = $pppcd[0]['purpose'];
		}
	}else if($purposecode && $transactiontype=="withdrawn"){
		$pppcd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
		if(count($pppcd)>0){
			$categoryid = $pppcd[0]['pcategorycode'];
			$categoryname = $pppcd[0]['pcategory'];
			$subcategoryid = $pppcd[0]['psub_categoryid'];
			$subcategoryname = $pppcd[0]['psub_category'];
			$purposecode = $pppcd[0]['purposecode'];
			$purposename = $pppcd[0]['purpose'];
		}
	}else{
		$sbcatinfo = getMainPurposeListDefaultMaster($conn, 'subcategoryid', $subcategoryid);
		if(count($sbcatinfo)>0){
			$categoryid = $sbcatinfo[0]['categoryid'];
			$subcategoryid = $sbcatinfo[0]['subcategoryid'];
		}
		
		$presult=addPurposeToMaster($conn, $companyid, $subcompanyid, $projectid, $categoryid, $subcategoryid, $purposename, $loginname, $transactiontype);
		// print_r($presult);
		if(count($presult)>0){
			if($presult['status']=="Success" || $presult['status']=="success"){
				$purposecode = $presult['purposecode'];
				$purposename = $presult['purposename'];
			}else if($presult['status']=="Duplicate" || $presult['status']=="duplicate"){
				$purposecode = $presult['purposecode'];
				$purposename = $presult['purposename'];
			}else{
				$rresult = array('status'=>'Error', 'message'=>$presult['message'].', Refresh the page and try again.');
				echo json_encode($rresult);
				die();
			}
		}
	}
	// echo $categoryid." && ".$subcategoryid." && ".$purposecode."==".$banksno;
	if($categoryid && $subcategoryid && $purposecode){
		// echo $companyid."==".$subcompanyid."==".$projectid."==".$transactiontype."==".$banksno;
		$chkdata=getReconciledReconstructedData($conn, $companyid, $subcompanyid, $projectid, $transactiontype, "sno", $banksno);
		// print_r($chkdata);
		if(count($chkdata)>0){
			$purposenametxt = $chkdata[0]['purpose'];
			$conn->query("UPDATE reconcile_reconstructed_tbl SET purposecode='".$purposecode."', purpose='".addslashes($purposename)."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(purpose)='".strtolower($chkdata[0]['purpose'])."' and (purposecode is null or purposecode='')");
			if(mysqli_affected_rows($conn)>0){
				insertPurposeCodeKeysHistory($conn, $companyid, $subcompanyid, $projectid, $categoryid, $subcategoryid, $purposecode, $purposename, $chkdata[0]['purpose'],$transactiontype);
				$rresult = array('status'=>'Success', 'message'=>'Purpose updated successfully');
			}else{
				$rresult = array('status'=>'Error', 'message'=>'Purpose did not updat, Due to something wrong.');
			}
		}else{
			$rresult = array('status'=>'Error', 'message'=>'Data not found.');
		}
	}else{
		$rresult = array('status'=>'Error', 'message'=>'Something missing categoryid or subcategoryid or purposecode');
	}
	echo json_encode($rresult);
}else if($action=="UpdatePurposeText_DirecttoMaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposename = $_POST['purposetext'];
	$banksno = $_POST['banksno'];
	$transactiontype = $_POST['transactiontype'];
	$loginname = $_POST['loginname'];
	$rresult=[];
	$categoryid ="";
	$subcategoryid ="";
	$purposecode ="";
	$sbcatinfo = getMainPurposeListDefaultMaster($conn, 'subcategoryid', '21');
	if(count($sbcatinfo)>0){
		$categoryid = $sbcatinfo[0]['categoryid'];
		$subcategoryid = $sbcatinfo[0]['subcategoryid'];
	}
	
	$presult=addPurposeToMaster($conn, $companyid, $subcompanyid, $projectid, $categoryid, $subcategoryid, $purposename, $loginname, $transactiontype);
	// print_r($presult);
	if(count($presult)>0){
		if($presult['status']=="Success" || $presult['status']=="success"){
			$purposecode = $presult['purposecode'];
			$purposename = $presult['purposename'];
		}else if($presult['status']=="Duplicate" || $presult['status']=="duplicate"){
			$purposecode = $presult['purposecode'];
			$purposename = $presult['purposename'];
		}else{
			$rresult = array('status'=>'Error', 'message'=>$presult['message'].', Refresh the page and try again.');
			echo json_encode($rresult);
			die();
		}
	}
	
	// echo $categoryid." && ".$subcategoryid." && ".$purposecode."==".$banksno;
	if($categoryid && $subcategoryid && $purposecode){
		// echo $companyid."==".$subcompanyid."==".$projectid."==".$transactiontype."==".$banksno;
		$chkdata=getReconciledReconstructedData($conn, $companyid, $subcompanyid, $projectid, $transactiontype, "sno", $banksno);
		// print_r($chkdata);
		if(count($chkdata)>0){
			$purposenametxt = $chkdata[0]['purpose'];
			$conn->query("UPDATE reconcile_reconstructed_tbl SET purposecode='".$purposecode."', purpose='".addslashes($purposename)."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(purpose)='".strtolower($chkdata[0]['purpose'])."' and (purposecode is null or purposecode='')");
			if(mysqli_affected_rows($conn)>0){
				insertPurposeCodeKeysHistory($conn, $companyid, $subcompanyid, $projectid, $categoryid, $subcategoryid, $purposecode, $purposename, $chkdata[0]['purpose'],$transactiontype);
				$rresult = array('status'=>'Success', 'message'=>'Purpose updated successfully');
			}else{
				$rresult = array('status'=>'Error', 'message'=>'Purpose did not updat, Due to something wrong.');
			}
		}else{
			$rresult = array('status'=>'Error', 'message'=>'Data not found.');
		}
	}else{
		$rresult = array('status'=>'Error', 'message'=>'Something missing categoryid or subcategoryid or purposecode');
	}
	echo json_encode($rresult);
}else if($action=="UnReconciliedActualBookByChatur"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
	$shortbyvalue = $accountid."|".$datefrom."|".$dateto;
	$pdqr=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"unreconcilieddata_withaccountid",$accountid);
	$unrecodata=array();
	if(count($pdqr)>0){
		foreach ($pdqr as $pdqrkey) {
			$refbrk=get_maintransactionsbreakdown_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$pdqrkey['reference_no']);
			$beneficiaryname = "";
			$purposename = "";
			$providedcode = "";
			$description = "";
			$descriptionbank = "";
			if(count($refbrk)>0){
				if(count($refbrk)==1){
					foreach ($refbrk as $ffkey) {
						$beneficiaryname = $ffkey['beneficiary_name'];
						$purposename = $ffkey['purpose'];
						$providedcode = $ffkey['provided_code'];
						$description = $ffkey['description'];
						$descriptionbank = $ffkey['description_bank']!=""?$ffkey['description_bank']:$ffkey['description'];
					}
				}else if(count($refbrk)>1){
					$beneficiaryname = "Multiple";
					$purposename = "Multiple";
					$providedcode = "Multiple";
					$description = "Multiple";
					$descriptionbank = "Multiple";
				}
			}
			$transactiondate = "";
			if($pdqrkey['transaction_date']){
				$transactiondate = date('d-m-Y', strtotime($pdqrkey['transaction_date']));
			}
			$dispbookdate = "";
			if($pdqrkey['bookdate']){
				$dispbookdate = date('d-m-Y', strtotime($pdqrkey['bookdate']));
			}
			$unrecodata[]=array('msno'=>$pdqrkey['sno'], 'referenceno'=>$pdqrkey['reference_no'], 'paytoid'=>$pdqrkey['paytoid'], 'payto'=>$pdqrkey['payto'], 'bookdate'=>$pdqrkey['bookdate'], 'dispbookdate'=>$dispbookdate, 'bookamount'=>$pdqrkey['book_amount'], 'transactionno'=>$pdqrkey['transactionno'], 'transactiondate'=>$transactiondate, 'transferedamount'=>$pdqrkey['transfered_amount']>0?$pdqrkey['transfered_amount']:$pdqrkey['book_amount'],'beneficiaryname'=>$beneficiaryname,'purposename'=>$purposename,'providedcode'=>$providedcode,'description'=>$description,'descriptionbank'=>$descriptionbank,'refbreakdataarray'=>$refbrk);
		}
	}
	echo json_encode($unrecodata);
}else if($action=="UnReconciliedData_FromPostPayment"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
	$shortbyvalue = $datefrom."|".$dateto;
	$rrrdata = voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"allunapprovewithDateRange",$shortbyvalue);
	
	$unrecodata=array();
	if(count($rrrdata)>0){
		foreach ($rrrdata as $rrdkey) {
			// $rrdkey['voicefilepath']
			$voicefilepath="";
			if (!empty($rrdkey['voicefilepath']) && file_exists("../".$rrdkey['voicefilepath'])) {
			    $voicefilepath =$rrdkey['voicefilepath']; 
			}
			$unrecodata[]=array('voicefilepath'=>$voicefilepath,
			'sno'=>$rrdkey['sno'],
			'transactiondatedsp'=>$rrdkey['transactiondatedsp'],
			'transactionno'=>$rrdkey['transactionno'],
			'requestamount'=>$rrdkey['requestamount'],
			'paytoid'=>$rrdkey['paytoid'],
			'payto'=>$rrdkey['payto'],
			'purposecode'=>$rrdkey['purposecode'],
			'purpose'=>$rrdkey['purpose'],
			'description'=>$rrdkey['description']);
		}
	}
	echo json_encode($unrecodata);
}else if($action=="ReconciliationmarkedtoPostpaymentAutoMatic"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginid=$_POST['loginid'];
	$reconstructaut=reconstructbook_FromPostPaymentDataAuto($mconn, $companyid, $subcompanyid, $projectid, $loginid);
	echo json_encode($reconstructaut);	
}else if($action=="ReconciliationmarkedtoPostpayment_manual"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	// $accountid=$_POST['accountid'];
	$loginname=$_POST['loginname'];
	$bookdatadbsno = json_decode($_POST['bookdatadbsno'], true);
	$bankdatadbsno = json_decode($_POST['bankdatadbsno'], true);
	$booksno=$bookdatadbsno[0]['booksno'];
	$banksno=$bankdatadbsno[0]['banksno'];
	$returnmsg=array();
	$returnmsg = reconstructbook_FromPostPaymentDataManual($mconn, $companyid, $subcompanyid, $projectid,$booksno, $banksno, $loginid);	
	echo json_encode($returnmsg);	
}else if($action=="ReconstructPostPaymentData"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$paytoid=$_POST['paytoid'];
	$purposecode=$_POST['purposecode'];
	$dbsno=$_POST['dbsno'];
	$ssst = reconstructbook_PostPaymentDataData($mconn, $companyid, $subcompanyid, $projectid, $paytoid, $purposecode, $dbsno);
	echo json_encode($ssst);
}else if($action=="getReferenceNoDetails"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$referenceno=$_POST['referenceno'];
	$tmainin = get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$referenceno);
	echo json_encode($tmainin);
}else if($action=="CopyReferenceDetailsWithNewReference"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$oldRefNo=$_POST['referenceno'];
	$statusVal=$_POST['reasonradioval'];
	$remarks=$_POST['remarks'].' update by'.$loginname;
	$loginname = $_POST['loginname'];
	$tmainin = get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$oldRefNo);
	$retstatus =[];
	if(count($tmainin)>0){
		$fromaccountno = $tmainin[0]['payfromaccountno'];
		$newRefNo=getnewreferenceno($conn,$companyid,$subcompanyid,$projectid,$fromaccountno);
	}
	if($newRefNo){
		$retstatus = duplicateTransactionWithBreakdowns($conn, $companyid, $subcompanyid,$projectid, $oldRefNo, $newRefNo,$statusVal, $remarks);
	}else{
		$retstatus = ['status'=>'error', 'message'=>'New Refernce no did created, Something wrong'];
	}
	echo json_encode($retstatus);
}


function getBindTypesAndValues($row) {
    $types = '';
    $values = [];

    foreach ($row as $val) {
        if (is_int($val)) {
            $types .= 'i';
        } elseif (is_float($val)) {
            $types .= 'd';
        } elseif (is_null($val)) {
            $types .= 's';
            $val = null;
        } else {
            $types .= 's';
        }
        $values[] = $val;
    }

    return [$types, $values];
}

function duplicateTransactionWithBreakdowns($conn, $companyid, $subcompanyid, $projectid, $oldRefNo, $newRefNo, $statusVal, $remarks) {
    $conn->begin_transaction();

    try {
        // Step 1: Fetch original main transaction
        $queryMain = "SELECT * FROM tbl_maintransaction WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND reference_no = ?";
        $stmtMain = $conn->prepare($queryMain);
        if (!$stmtMain) throw new Exception("Prepare failed (main): " . $conn->error);
        $stmtMain->bind_param("ssss", $companyid, $subcompanyid, $projectid, $oldRefNo);
        $stmtMain->execute();
        $resultMain = $stmtMain->get_result();

        if ($mainRow = $resultMain->fetch_assoc()) {
            unset($mainRow['sno'], $mainRow['transactionno'], $mainRow['transaction_date'], $mainRow['final_status'], $mainRow['uploadflag'], $mainRow['edatetime']); // Remove auto-increment/unique fields
            $mainRow['reference_no'] = $newRefNo;

            $columns = array_keys($mainRow);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $colList = '`' . implode('`,`', $columns) . '`';
            list($types, $values) = getBindTypesAndValues($mainRow);

            $insertMain = "INSERT INTO tbl_maintransaction ($colList) VALUES ($placeholders)";
            $stmtInsert = $conn->prepare($insertMain);
            if (!$stmtInsert) throw new Exception("Prepare failed (insert main): " . $conn->error);
            $stmtInsert->bind_param($types, ...$values);
            if (!$stmtInsert->execute()) throw new Exception("Insert failed (main): " . $stmtInsert->error);

            // Update original main record
            $remark = "Copied to $newRefNo with remark: $remarks on " . date("Y-m-d H:i:s");
            $updateMain = "UPDATE tbl_maintransaction SET final_status = ?, tedit = NOW(), tedit_remark = ? WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND reference_no = ?";
            $stmtUpdate = $conn->prepare($updateMain);
            if (!$stmtUpdate) throw new Exception("Prepare failed (update main): " . $conn->error);
            $stmtUpdate->bind_param("ssssss", $statusVal, $remark, $companyid, $subcompanyid, $projectid, $oldRefNo);
            if (!$stmtUpdate->execute()) throw new Exception("Update failed (main): " . $stmtUpdate->error);
        } else {
            throw new Exception("Original main transaction not found.");
        }

        // Step 2: Fetch breakdowns
        $queryBreakdown = "SELECT * FROM tbl_maintransaction_breakdown WHERE reference_no = ?";
        $stmtBreakdown = $conn->prepare($queryBreakdown);
        if (!$stmtBreakdown) throw new Exception("Prepare failed (breakdown select): " . $conn->error);
        $stmtBreakdown->bind_param("s", $oldRefNo);
        $stmtBreakdown->execute();
        $resultBreakdown = $stmtBreakdown->get_result();

        while ($row = $resultBreakdown->fetch_assoc()) {
            unset($row['sno'], $row['uploadflag'], $row['edatetime']);
            $row['reference_no'] = $newRefNo;

            $columns = array_keys($row);
            $placeholders = implode(',', array_fill(0, count($columns), '?'));
            $colList = '`' . implode('`,`', $columns) . '`';
            list($types, $values) = getBindTypesAndValues($row);

            $insertBreakdown = "INSERT INTO tbl_maintransaction_breakdown ($colList) VALUES ($placeholders)";
            $stmtInsertBreak = $conn->prepare($insertBreakdown);
            if (!$stmtInsertBreak) throw new Exception("Prepare failed (insert breakdown): " . $conn->error);
            $stmtInsertBreak->bind_param($types, ...$values);
            if (!$stmtInsertBreak->execute()) throw new Exception("Insert failed (breakdown): " . $stmtInsertBreak->error);
        }

        // Update original breakdowns
        $updateBreak = "UPDATE tbl_maintransaction_breakdown SET tedit = NOW(), tedit_remark = ? WHERE reference_no = ?";
        $stmtUpdateBreak = $conn->prepare($updateBreak);
        if (!$stmtUpdateBreak) throw new Exception("Prepare failed (update breakdown): " . $conn->error);
        $stmtUpdateBreak->bind_param("ss", $remark, $oldRefNo);
        if (!$stmtUpdateBreak->execute()) throw new Exception("Update failed (breakdown): " . $stmtUpdateBreak->error);

        $conn->commit();
        return [
            'status' => 'success',
            'message' => "Data duplicated from $oldRefNo to $newRefNo",
            'oldref' => $oldRefNo,
            'newref' => $newRefNo
        ];

    } catch (Exception $ex) {
        $conn->rollback();
        return [
            'status' => 'error',
            'message' => $ex->getMessage()
        ];
    }
}




// function duplicateTransactionWithBreakdowns($conn, $companyid, $subcompanyid, $projectid, $oldRefNo, $newRefNo, $statusVal, $remarks) {
//     $conn->begin_transaction();

//     try {
//         // Step 1: Fetch and duplicate maintransaction
//         $queryMain = "SELECT * FROM tbl_maintransaction WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND reference_no = ?";
//         $stmtMain = $conn->prepare($queryMain);
//         $stmtMain->bind_param("ssss", $companyid, $subcompanyid, $projectid, $oldRefNo);
//         $stmtMain->execute();
//         $resultMain = $stmtMain->get_result();

//         if ($mainRow = $resultMain->fetch_assoc()) {
//             if (isset($mainRow['id'])) unset($mainRow['id']);
//             $mainRow['reference_no'] = $newRefNo;

//             $columnsMain = array_keys($mainRow);
//             $placeholdersMain = implode(", ", array_fill(0, count($columnsMain), "?"));
//             $colListMain = "`" . implode("`, `", $columnsMain) . "`";
//             $typesMain = str_repeat("s", count($columnsMain));
//             $valuesMain = array_values($mainRow);

//             $insertMain = "INSERT INTO tbl_maintransaction ($colListMain) VALUES ($placeholdersMain)";
//             $stmtInsertMain = $conn->prepare($insertMain);
//             $stmtInsertMain->bind_param($typesMain, ...$valuesMain);
//             $stmtInsertMain->execute();

//             // Prepare remark
//             $remark = "Copied to $newRefNo with remark: $remarks on " . date("Y-m-d H:i:s");

//             // Update original maintransaction
//             $updateMain = "UPDATE tbl_maintransaction SET final_status = ?, tedit = ?, tedit_remark = ? WHERE reference_no = ?";
//             $stmtUpdateMain = $conn->prepare($updateMain);
//             $stmtUpdateMain->bind_param("ssss", $statusVal, $statusVal, $remark, $oldRefNo);
//             $stmtUpdateMain->execute();
//         } else {
//             throw new Exception("Main transaction not found for reference: $oldRefNo");
//         }

//         // Step 2: Fetch and duplicate breakdowns
//         $queryBreak = "SELECT * FROM tbl_maintransaction_breakdown WHERE reference_no = ?";
//         $stmtBreak = $conn->prepare($queryBreak);
//         $stmtBreak->bind_param("s", $oldRefNo);
//         $stmtBreak->execute();
//         $resultBreak = $stmtBreak->get_result();

//         while ($breakRow = $resultBreak->fetch_assoc()) {
//             if (isset($breakRow['id'])) unset($breakRow['id']);
//             $breakRow['reference_no'] = $newRefNo;

//             $columnsBreak = array_keys($breakRow);
//             $placeholdersBreak = implode(", ", array_fill(0, count($columnsBreak), "?"));
//             $colListBreak = "`" . implode("`, `", $columnsBreak) . "`";
//             $typesBreak = str_repeat("s", count($columnsBreak));
//             $valuesBreak = array_values($breakRow);

//             $insertBreak = "INSERT INTO tbl_maintransaction_breakdown ($colListBreak) VALUES ($placeholdersBreak)";
//             $stmtInsertBreak = $conn->prepare($insertBreak);
//             $stmtInsertBreak->bind_param($typesBreak, ...$valuesBreak);
//             $stmtInsertBreak->execute();
//         }

//         // Step 3: Update original breakdowns
//         $updateBreakdown = "UPDATE tbl_maintransaction_breakdown SET tedit = NOW(), tedit_remark = ? WHERE reference_no = ?";
//         $stmtUpdateBreak = $conn->prepare($updateBreakdown);
//         $stmtUpdateBreak->bind_param("ss", $remark, $oldRefNo);
//         $stmtUpdateBreak->execute();

//         $conn->commit();

//         return [
//             "status" => "success",
//             "message" => "Transaction and breakdowns duplicated successfully. Old reference marked as duplicated.",
//             "old_reference" => $oldRefNo,
//             "new_reference" => $newRefNo
//         ];
//     } catch (Exception $e) {
//         $conn->rollback();
//         return [
//             "status" => "error",
//             "message" => "Duplication failed: " . $e->getMessage()
//         ];
//     }
// }

function closetobookdatadetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$shortby,$shortbyvalue=""){
	$sortcondition = "";
	if($shortbyvalue){
		$sortcondition = "and account_id='".$shortbyvalue."'";
	}
	if($shortby=="all_unreconcile_withaccountid"){
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' ".$sortcondition." and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (book_status='' or book_status is null) order by sno";
		  // unreconcile_bitweendates
	}else if($shortby=="unreconcile_withaccountid"){
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' ".$sortcondition." and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (book_status='' or book_status is null) order by sno";
	}else if($shortby=="unreconcile_withaccountidwithdates"){
        $shorexp = explode("|", $shortbyvalue);
        $accountid = $shorexp[0];
        $datefrom = $shorexp[1];
        $dateto = $shorexp[2];
        if(detectDateTimeFormat($shorexp[1])){
            $datefrom=convertDateToYMD($shorexp[1]);
        }
        if(detectDateTimeFormat($shorexp[2])){
            $dateto=convertDateToYMD($shorexp[2]);
        }
        if(strtolower($accountid)=="all"){
            $accountid_cnd="";
        }else{
            $accountid_cnd="and account_id='".$accountid."'";
        }
        $clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' ".$accountid_cnd." and date>='".$datefrom."' and date<='".$dateto."' and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (book_status='' or book_status is null) order by sno";
    }else if($shortby=="unreconstruct_withaccountid"){
        $clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' and account_id='".$shortbyvalue."' and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (reconstructstatus='' or reconstructstatus is null) order by sno";
    }else if($shortby=="unreconstruct_readytobook"){
        $clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' and purpose_code<>'' and paytoid<>'' and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') order by banksno";
    }else if($shortby=="reconstruct_paytounknown"){
        $clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' and payto<>'' and  (paytoid is null or paytoid='' or paytoid='undefined') and ifnull(total_amount,0)>0 and (reconciliation is null or reconciliation='') and reconstructstatus<>'' order by sno";
    }else if($shortby=="reconstruct_purposeunknown"){
        $clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' and purpose<>'' and  (purpose_code is null or purpose_code='' or purpose_code='undefined') and ifnull(total_amount,0)>0 and (reconciliation is null or reconciliation='') and reconstructstatus<>'' order by sno";
    }else if($shortby=="unreconstruct_withaccountidwithdates"){
        $shorexp = explode("|", $shortbyvalue);
        $accountid = $shorexp[0];
        $datefrom = $shorexp[1];
        $dateto = $shorexp[2];
        if(detectDateTimeFormat($shorexp[1])){
            $datefrom=convertDateToYMD($shorexp[1]);
        }
        if(detectDateTimeFormat($shorexp[2])){
            $dateto=convertDateToYMD($shorexp[2]);
        }
        if(strtolower($accountid)=="all"){
            $accountid_cnd="";
        }else{
            $accountid_cnd="and account_id='".$accountid."'";
        }

        $clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' ".$accountid_cnd." and date>='".$datefrom."' and date<='".$dateto."' and ifnull(total_amount,0)>0 and (reconciliation is null or reconciliation='') order by sno";

    }else if($shortby=="unknown_withaccountid"){
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' ".$sortcondition." and ifnull(total_amount,0)>0 and (status is null or status='') and (book_status='' or book_status is null  or book_status='Unknown') order by banksno";

	}else if($shortby=="unreconcile_keys_withaccountid"){
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' ".$sortcondition." and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown') group by keyid";
	}else if($shortby=="keyid"){
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and keyid='".$shortbyvalue."' order by keyid";
	}else if($shortby=="sno"){
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and  sno='".$shortbyvalue."' order by keyid";
	}else if($shortby=="unreconcile_forconciled"){
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' ".$sortcondition." and (status is not null and status<>'') and (book_status='' or book_status is null or book_status='Unknown') order by keyid";
	}else if($shortby=="unreconcilewithamount"){
		$minamount=$shortbyvalue-10;
		$maxamount=$shortbyvalue+10;
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' and total_amount>='".$minamount."' and total_amount<='".$maxamount."' and (status is not null and status<>'') and (book_status='' or book_status is null or book_status='Unknown') order by keyid";
	}else if($shortby=="unreconcilewithamountanddate"){
		$shortbyvalueexp = explode("|", $shortbyvalue);
		$minamount=$shortbyvalueexp[0]-10;
		$maxamount=$shortbyvalueexp[0]+10;
		// echo detectDateTimeFormat($shortbyvalueexp[1]);
		$cdate="";
		if(detectDateTimeFormat($shortbyvalueexp[1])){
			$cdate=convertDateToYMD($shortbyvalueexp[1]);
		}
		$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid, reconstructstatus FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='".strtolower($transactiontype)."' and total_amount>='".$minamount."' and total_amount<='".$maxamount."' and date>='".$cdate."' and (book_status='' or book_status is null or book_status='Unknown') order by keyid";
	}
	$clbkr=$conn->query($clbk);
	$clbooklist=array();
	if($clbkr->num_rows>0){
		while($cbrow=$clbkr->fetch_assoc()){
			$bnkinf=getaccountinformation($conn,$cbrow['company_id'],$cbrow['subcompany_id'],$cbrow['project_id'],"accountid",$cbrow['account_id']);
			$accountname="";
			$accountno="";
			$ifsccode="";
			$bankname="";
			$accountholdername="";
			$accounttype="";
			if(count($bnkinf)>0){
				foreach ($bnkinf as $bnkey) {
					$accountname=$bnkey['accountname'];
					$accountno=$bnkey['accountno'];
					$ifsccode=$bnkey['ifsccode'];
					$bankname=$bnkey['bankname'];
					$accountholdername=$bnkey['accountholdername'];
					$accounttype=$bnkey['account_type'];
				}
			}

            if($cbrow['actualtransfered_amount']>0){
                $actualtransfered_amount = $cbrow['actualtransfered_amount'];
            }else{
                $actualtransfered_amount = $cbrow['total_amount'];
            }

			$clbooklist[]=array('sno'=>$cbrow['sno'], 'company_id'=>$cbrow['company_id'], 'subcompany_id'=>$cbrow['subcompany_id'], 'project_id'=>$cbrow['project_id'], 'country_id'=>$cbrow['country_id'], 'value_date'=>$cbrow['date'], 'dspdate'=>date('d-M-Y', strtotime($cbrow['date'])), 'paymentmode'=>$cbrow['paymentmode'], 'chequeno'=>$cbrow['chequeno'], 'payto'=>$cbrow['payto'], 'paytoid'=>$cbrow['paytoid'], 'beneficiaryname'=>$cbrow['beneficiaryname'], 'bankname'=>$cbrow['bankname'], 'accountname'=>$cbrow['accountname'], 'accountno'=>$cbrow['accountno'], 'account_id'=>$cbrow['account_id'], 'accounttype'=>$accounttype, 'bankid'=>$cbrow['bankid'], 'tds'=>$cbrow['tds'], 'tds_amount'=>$cbrow['tds_amount'], 'total_amount'=>$cbrow['total_amount'], 'bankcharge_amount'=>$cbrow['bankcharge_amount'], 'actualtransfered_amount'=>$actualtransfered_amount, 'amounttype'=>$cbrow['amounttype'],'paymenttype'=>$cbrow['paymenttype'], 'purpose'=>$cbrow['purpose']??'', 'purpose_code'=>$cbrow['purpose_code']??'', 'categoryid'=>$cbrow['categoryid'], 'sub_category'=>$cbrow['sub_category'], 'category'=>$cbrow['category'], 'sub_categoryid'=>$cbrow['sub_categoryid'], 'description'=>$cbrow['description'], 'description_bank'=>$cbrow['description_bank'], 'remarks'=>$cbrow['remarks'],'balancefrombank'=>$cbrow['balancefrombank'], 'tedit'=>$cbrow['tedit'], 'tsno'=>$cbrow['tsno'], 'reference_no'=>$cbrow['reference_no'], 'banksno'=>$cbrow['banksno'], 'status'=>$cbrow['status'], 'source'=>$cbrow['source'], 'request_no'=>$cbrow['request_no'], 'loginid'=>$cbrow['loginid'], 'fileinfo'=>$cbrow['fileinfo'], 'key1'=>$cbrow['key1'], 'key2'=>$cbrow['key2'], 'key3'=>$cbrow['key3'], 'keyid'=>$cbrow['keyid'], 'accountname'=>$accountname, 'accountno'=>$accountno,'bankname'=>$bankname,'reconstructstatus'=>$cbrow['reconstructstatus']);
		}
	}
	return $clbooklist;
}

function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
?>