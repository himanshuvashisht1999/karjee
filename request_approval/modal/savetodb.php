<?php
date_default_timezone_set("Asia/Calcutta");
require('../../connection.php');
require('../../functions/function_master.php');
// require('../../functions/request_fn_approval.php');
require('../../functions/function_savetodb.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$action = $_GET['action'];
if($action=="updatemodeoftransfer"){
	$companyid= $_POST['companyid'];
	$subcompanyid= $_POST['subcompanyid'];
	$projectid= $_POST['projectid'];
	$requestno = $_POST['requestno'];
	$paytoname = $_POST['paytoname'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$pbankname = $_POST['pbankname'];
	$pbankaccountno = $_POST['pbankaccountno'];
	$pbankifsccode = $_POST['pbankifsccode'];
	echo json_encode(updatemodeoftransfer($conn,$requestno,$paytoname,$modeoftransfer,$pbankname,$pbankaccountno,$pbankifsccode, $companyid, $subcompanyid, $projectid));

}else if($action=="updateeditedrequest"){
	$companyid= $_POST['companyid'];
	$subcompanyid= $_POST['subcompanyid'];
	$projectid= $_POST['projectid'];
	$ehiddenbsno = $_POST['ehiddenbsno'];
	$ebeneficiaryname = $_POST['ebeneficiaryname'];
	$einvoiceamount = $_POST['einvoiceamount'];
	$epurposecode = $_POST['epurposecode'];
	$einovoicetype = $_POST['einovoicetype'];
	$einvoiceno = $_POST['einvoiceno'];
	$einvoicedate = $_POST['einvoicedate'];
	$edescription = $_POST['edescription'];
	// $eattachment = $_POST['eattachment'];
	$requestno = $_POST['requestno'];

	$docupload="";
	$filepath ="";
	// $distpath='../../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/requestdocument/'.$requestno.'/';
	// if(isset($_FILES['eattachment']) && $_FILES['eattachment']['name']){
	// 	if ( !is_dir( $distpath ) ) {
	// 	    mkdir($distpath, 0777);       
	// 	}
	// 	$temp = explode(".", $_FILES["eattachment"]["name"]);
	// 	$extension=end($temp);
	// 	$newfilename = round(microtime(true)) .date('his'). '.' . end($temp);
    //     move_uploaded_file($_FILES["eattachment"]["tmp_name"], $distpath . $newfilename);
    //     $filepath = $distpath . $newfilename;
    //     $docupload=",filepath='".addslashes(str_replace('../', '',$filepath))."',extension='".$extension."', blobfileid='', blobfile_tblname=''";
	// }

	$distpath = '../../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/requestdocument/' . $requestno . '/';

	if (isset($_FILES['eattachment']) && !empty($_FILES['eattachment']['name'])) {
	    // Ensure destination directory exists
	    if (!is_dir($distpath)) {
	        mkdir($distpath, 0777, true); // 'true' allows recursive directory creation
	    }

	    // Sanitize and extract file extension
	    $originalName = basename($_FILES["eattachment"]["name"]);
	    $temp = explode(".", $originalName);
	    $extension = strtolower(end($temp));

	    // Generate a new unique filename
	    $newfilename = round(microtime(true)) . date('His') . '.' . $extension;
	    $fullPath = $distpath . $newfilename;

	    // Move uploaded file
	    if (move_uploaded_file($_FILES["eattachment"]["tmp_name"], $fullPath)) {
	        // Clean filepath for DB (remove relative '../')
	        $cleanPath = str_replace('../', '', $fullPath);
	        $filepath = addslashes($cleanPath);

	        // Prepare upload metadata for DB
	        $docupload = ", filepath='$filepath', extension='$extension', blobfileid='', blobfile_tblname=''";
	    } else {
	        // Handle upload failure
	        $docupload = ""; // or handle error appropriately
	        error_log("File upload failed for: " . $originalName);
	    }
	}

	//get purpose details
	$purposedetails=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$epurposecode);
	$purpose_category="";
	$purpose_categoryid="";
	$purpose_subcategory="";
	$purpose_subcategoryid="";
	$purpose="";
	$purpose_code="";
	$returnarray=array();

	// die();
	// ----------------------------------------------------------
	if(count($purposedetails)>0){
		foreach ($purposedetails as $prrow) {
			$purpose_category=$prrow['pcategory'];
			$purpose_categoryid=$prrow['pcategorycode'];
			$purpose_subcategory=$prrow['psub_category'];
			$purpose_subcategoryid=$prrow['psub_categoryid'];		
			$purpose=$prrow['purpose'];
			$purpose_code=$prrow['purposecode'];		
		}

		$gtreqbrk=getrequestbreakdowndetails($conn,$companyid,$subcompanyid,$projectid,"sno",$ehiddenbsno);
		$beforeeditamount=0;
		if(count($gtreqbrk)>0){
			foreach ($gtreqbrk as $gtreqbrkkey) {
				$beforeeditamount = $gtreqbrkkey['ammount'];
			}
			$updqry="UPDATE tbl_paymentrequest_breakdown SET beneficiery_name='".addslashes($ebeneficiaryname)."',ammount='".$einvoiceamount."',taxableamount='".$einvoiceamount."', before_editamount='".$beforeeditamount."', purpose_category='".addslashes($purpose_category)."', purpose_categoryid='".$purpose_categoryid."', purpose_subcategory='".addslashes($purpose_subcategory)."', purpose_subcategoryid='".$purpose_subcategoryid."', purpose='".addslashes($purpose)."', purpose_code='".$purpose_code."', description='".addslashes($edescription)."', invoice_type='".$einovoicetype."', invoice_no='".$einvoiceno."', invoicedate='".addslashes($einvoicedate)."',uploadflag=null ".$docupload." WHERE request_no='".$requestno."' and sno='".$ehiddenbsno."'";
			$updqry_rslt=$conn->query($updqry);
			$requested_amount=0;
			$totalrow=0;
			if($updqry_rslt){
				$ttlrqst=gettotalbreakdownamount($conn,$requestno,$companyid,$subcompanyid,$projectid);
				foreach ($ttlrqst as $bb) {
					$requested_amount=$bb['requested_amount'];
					$totalrow=$bb['totalrow'];
				}
				if($requested_amount>0){
					if(updaterequesttable($conn,$requestno,$requested_amount,$companyid,$subcompanyid,$projectid)=="updated"){
						$returnarray[]=array('status'=>'Success', 'message'=>'Edited Successfully', 'total_rqamount'=>$requested_amount,'beneficiery_name'=>$ebeneficiaryname,'beneficiery_ammount'=>$einvoiceamount,'purpose_category'=>$purpose_category,'purpose'=>$purpose,'purpose_code'=>$purpose_code,'description'=>$edescription,'invoice_no'=>$einvoiceno,'invoice_date'=>$einvoicedate,'invoice_type'=>$einovoicetype,'filepath'=>$filepath);
					}else{
						$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest');
					}
				}else{
					$returnarray[]=array('status'=>'Error', 'message'=>'requestno amount is zero (0)');
				}

			}else{
				$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest_breakdown');
			}
		}else{
			$returnarray[]=array('status'=>'Error', 'message'=>'Request brekdown details did not get');			
		}
	}else{
		$returnarray[]=array('status'=>'Error', 'message'=>'Purpose details did not matched our master table');
	}
	echo json_encode($returnarray);
}else if($action=="removeinvoicedetails"){
	$requestno=$_POST['requestno'];
	$rhiddenbsno=$_POST['rhiddenbsno'];
	$rdescription=$_POST['rdescription'];	
	$countryid= $_POST['countryid'];
	$companyid= $_POST['companyid'];
	$subcompanyid= $_POST['subcompanyid'];
	$projectid= $_POST['projectid'];
	$loginname= $_POST['loginname'];
	echo json_encode(removeinvoicedetails($conn,$requestno,$rhiddenbsno,$rdescription,$companyid,$subcompanyid,$projectid,$loginname));
}else if($action=="editremoveinvoicedetails"){
	$requestno=$_POST['requestno'];
	$rhiddenbsno=$_POST['rhiddenbsno'];
	$rdescription=$_POST['rdescription'];	
	$countryid= $_POST['countryid'];
	$companyid= $_POST['companyid'];
	$subcompanyid= $_POST['subcompanyid'];
	$projectid= $_POST['projectid'];
	$loginname= $_POST['loginname'];
	echo json_encode(removeinvoicedetailsfromEdit($conn,$requestno,$rhiddenbsno,$rdescription,$companyid,$subcompanyid,$projectid,$loginname));
}else if($action=="rejectrequest"){
	$countryid= $_POST['countryid'];
	$companyid= $_POST['companyid'];
	$subcompanyid= $_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$requestno=$_POST['requestno'];
	$rejectremark=$_POST['rejectremark'];
	$loginname=$_POST['loginname'];
	echo json_encode(rejectrequestfinal($conn,$loginname,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid));
}else if($action=="requestaddinwaitinglist"){
	$countryid= $_POST['countryid'];
	$companyid= $_POST['companyid'];
	$subcompanyid= $_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$requestno=$_POST['requestno'];
	$waitingremark=$_POST['waitingremark'];
	$loginname=$_POST['loginname'];
	echo json_encode(Request_Addwaitinglist($conn,$loginname,$requestno,$waitingremark,$companyid,$subcompanyid,$projectid));
}else if($action=="proceedtopayment"){
	$loginname=$_POST['loginname'];
	$requestno=$_POST['requestno'];
	$approvedremark=$_POST['approvedremark'];
	$countryid= $_POST['countryid'];
	$companyid= $_POST['companyid'];
	$subcompanyid= $_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$requesttype = $_POST['requesttype'];
	$invoiceinfo = json_decode($_POST['invoiceinfo'],TRUE);
	$resultlist=array();
	if($requesttype!=""){
		if(strtolower($requesttype)=="imprest adjustment"){
			echo json_encode(proceedtoimprestadjustment($conn,$companyid,$subcompanyid,$projectid,$loginname,$requestno,$approvedremark,$invoiceinfo));
		}else if(strtolower($requesttype)=="partial adjustment"){
			echo json_encode(proceedtopartialpaymentjustment($conn,$companyid,$subcompanyid,$projectid,$loginname,$requestno,$approvedremark,$invoiceinfo));
		}else{
			echo json_encode(proceedtopaymentaproved($conn,$companyid,$subcompanyid,$projectid,$loginname,$requestno,$approvedremark,$invoiceinfo));
		}
	}else{
		$resultlist[]=array('status'=>'Error', 'message'=>'Purpose type not defined');
		echo json_encode($resultlist);
	}
}else if($action=="updatepayfrombank_inrequest"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$requestdbsno = $_POST['requestdbsno'];
	$accinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
	$ssst=array();
	if(count($accinfo)==1){
		foreach ($accinfo as $accfkey) {
			$from_bankname = $accfkey['bankname'];
			$from_accountid = $accfkey['accountid'];
			$from_accountno = $accfkey['accountno'];
			$upd=$conn->query("UPDATE tbl_paymentrequest SET from_bankname='".$from_bankname."', from_accountid='".$from_accountid."', from_accountno='".$from_accountno."' WHERE sno='".$requestdbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$ssst[]=array('status'=>'Success', 'message'=>'Update pay from bank details successfully');
			}else{
				$ssst[]=array('status'=>'Error', 'message'=>'Update pay from bank details did not updat, due to technical issue of already updated');
			}
		}
	}else{
		$ssst[]=array('status'=>'Error', 'message'=>'Selected bank detail did available in our master table, Pleae choose the correct account');
	}
	echo json_encode($ssst);
}
//proceed to payment

function proceedtopaymentaproved($conn,$companyid,$subcompanyid,$projectid,$loginname,$requestno,$approvedremark,$invoiceinfo){
	$forvalidation = "select request_no from tbl_maintransaction where request_no='".$requestno."' and (Tedit is null or Tedit='')";
	$myresult1=$conn->query($forvalidation) or die($conn->error);
	$newreference_no ="";
	if($myresult1->num_rows>0)
	{
		$resultlist[]=array('status'=>'Error', 'message'=>'Your request no '.$requestno.' is already procceded');
		
	}else{
		$projectinfo=projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
		foreach($projectinfo as $proj) {
			$projectname=$proj['projectname'];
		}
		$requestinfo=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
		$paymentduedate ="";
		$is_intermediate_transfer="";
		$to_account_id = "";
		foreach($requestinfo as $reqrow) {
			$request_date = $reqrow['request_date'];
			$requested_by = $reqrow['requested_by'];
			$paytoid = $reqrow['paytoid'];
			$payto = $reqrow['payto'];
			$payment_mode = $reqrow['payment_mode'];
			$beneficiery_type = $reqrow['beneficiery_type'];
			$purpose_type = $reqrow['purpose_type'];
			$projectid = $reqrow['projectid'];
			$subprojectid = $reqrow['subprojectid'];
			$totalpayamount = $reqrow['request_amount'];
			$from_accountid = $reqrow['from_accountid'];
			$from_bankname = $reqrow['from_bankname'];
			$from_accountno = $reqrow['from_accountno'];
			$paytoaccountno = $reqrow['payto_accountno'];
			$paymentduedate = $reqrow['paymentduedate'];
			$is_purchaseforproduction = $reqrow['is_purchaseforproduction'];
			if($paytoaccountno){
				$paytobankname = $reqrow['payto_bankname'];
				$paytoaccountno = $reqrow['payto_accountno'];
				$paytoifsc = $reqrow['payto_bankifsccode'];
			}else{
				$pppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
				if(count($pppt)>0){
					foreach ($pppt as $pptkey) {
						$paytobankname = $pptkey['tobankname'];
						$paytoaccountno = $pptkey['toaccountno'];
						$paytoifsc = $pptkey['tobankifsc'];	
					}
				}else{
					$paytobankname ="";
					$paytoaccountno ="";
					$paytoifsc ="";					
				}
			}
			if($paytoaccountno){
				$chkinterbank = isInterBankTransfer($conn, $companyid, $projectid, $paytoaccountno);
				if($chkinterbank['status']=="success")
				$is_intermediate_transfer="yes";
				$to_account_id=$chkinterbank['accountid'];
			}else{
				$is_intermediate_transfer="";
				$to_account_id = "";
			}
			
			$tsno=GetMaxRefTempSno($conn);
			$newreference_no = getnewreferenceno($conn,$companyid,$subcompanyid,$projectid,$from_accountno);
		}
		if(count($invoiceinfo)>0 && $newreference_no!=""){
			$requestedamount="0";
			$tdspercent=0;
			$amounttopay=0;
			$tdsamount =0;
			$incometaxamount=0;
			foreach ($invoiceinfo as $dtrow) {
				if($dtrow['bsno']!=""){
					$requestedamount=$dtrow['requestedamount'];
					$tdscategory=$dtrow['tdscategory']??'';
					$tdspercent=$dtrow['tdspercent']??0;
					$incometaxamount=$dtrow['incometaxamount'];
					$amounttopay=$dtrow['amounttopay'];
					$tdsamount = $requestedamount - $amounttopay; 
					$shortby="sno";
					$shortbyvalue=$dtrow['bsno'];
					$requestinfo=getrequestbreakdowndetails($conn,$companyid,$subcompanyid,$projectid,"sno",$dtrow['bsno']);
					$totalpayamount=0;
					foreach ($requestinfo as $pbrow) {
						$ppj = getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$pbrow['purpose_code']);
						$pcategoryid ="";
						$pcategory ="";
						$sub_category ="";
						$sub_categoryid ="";
						foreach ($ppj as $ppjkey) {
							$pcategoryid = $ppjkey['pcategorycode'];
							$pcategory = $ppjkey['pcategory'];
							$sub_category = $ppjkey['psub_category'];
							$sub_categoryid = $ppjkey['psub_categoryid'];
						}

						//insert into tbl_maintransaction_breakdown
						$brk_dwn_insert="INSERT INTO tbl_maintransaction_breakdown(companyid,subcompanyid,projectid,subprojectid,request_no,reference_no,beneficiary_name,amount,beforeedit_amount,purpose_category,purpose_categoryid,purpose_subcategory,purpose_subcategoryid,purpose,purpose_code,invoice_type,invoice_no,description,tds_type,tds_percent,tds_amount,blobfile,blobfile_tblname,documentation,doctype,documentation_path,taxableamount,gstamount) VALUES ('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$requestno."','".$newreference_no."','".addslashes($pbrow['beneficiery_name'])."','".$amounttopay."','".$amounttopay."','".addslashes($pcategory)."','".$pcategoryid."','".addslashes($sub_category)."','".addslashes($sub_categoryid)."','".addslashes($pbrow['purpose'])."','".addslashes($pbrow['purpose_code'])."','".$pbrow['invoice_type']."','".addslashes($pbrow['invoice_no']??'')."','".addslashes($pbrow['description'])."','".$tdscategory."','".$tdspercent."','".$tdsamount."','".$pbrow['blobfileid']."','".$pbrow['blobfile_tblname']."','".$pbrow['documenttype']."','".$pbrow['extension']."','".$pbrow['filepath']."','".$pbrow['taxableamount']."','".$pbrow['gstamount']."')";
						$breakdown_result=$conn->query($brk_dwn_insert) or die($conn->error);
						if($breakdown_result){
							$ssq=$conn->query("SELECT sno, ifnull(book_amount,0) as book_amount FROM tbl_maintransaction WHERE (tedit is null or tedit='') and reference_no='".$newreference_no."'");
							if($ssq->num_rows>0){
								$mnrow=$ssq->fetch_assoc();
								$totalpayamount =$mnrow['book_amount'] + $amounttopay;
								$upmaind=$conn->query("UPDATE tbl_maintransaction SET book_amount='".$totalpayamount."', beneficierie='Multiple', uploadflag=null WHERE request_no='".$requestno."' and reference_no='".$newreference_no."' and (tedit is null or tedit='')");

							}else{
								$maintransqry="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, bookdate, book_amount, beforeedit_amount, transaction_type, paytoid, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, is_intermediate_transfer,paymentduedate,is_purchaseforproduction,approvedby,requestedby,requesteddate, paytoaccountid) VALUES('".$tsno."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$requestno."','".$newreference_no."','".date('Y-m-d')."','".$amounttopay."','".$amounttopay."','".$payment_mode."','".$paytoid."','".$payto."','".$from_bankname."','".$from_accountno."','".$from_accountid."','".addslashes($pbrow['purpose_category']??'')."', 'Individual', '".$paytoaccountno."','".$paytobankname."','".$paytoifsc."','".$is_intermediate_transfer."','".$paymentduedate."','".$is_purchaseforproduction."','".$loginname."','".$requested_by."','".$request_date."','".$to_account_id."')";
								$maintrans=$conn->query($maintransqry) or die($conn->error);
							}

							// income tax deduction
							if($incometaxamount>0){
								$description = "Income tax deduction";
								savetodbItaxDetails($conn, $companyid, $subcompanyid, $projectid, $paytoid, $payto, $requestno,$newreference_no, $incometaxamount, $amounttopay, $description);
							}

							//tds deduction
							if($tdspercent>0){
								tdsdeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$from_accountid,$pbrow['beneficiery_name'],$requestedamount,$amounttopay,$tdspercent,$tdscategory,$requestno,$newreference_no,$pbrow['invoice_no']);
							}
						}else{
							$resultlist[]=array('status'=>'Error', 'message'=>'Your request information is not updated  in breakdown_result', 'requestno'=>'', 'referenceno'=>'');
						}

					}	//end of foreach
				}  //end of if
			}	//end of foreach


			$selfupdate="update tbl_paymentrequest set approval_status='Payment Paid', status_by='".$loginname."', paid_for_ref='".$newreference_no."', uploadflag=null, approval_comment='".$approvedremark."', requestapprovedate='".date('Y-m-d H:i:s')."', status_cheque='pending', pending_description='Ready for Printing' where request_no = '".$requestno."'";
			$slf_upd_result=$conn->query($selfupdate);
			if($slf_upd_result){
				$resultlist[]=array('status'=>'Success', 'message'=>'Your request '.$requestno.' details save successfully for payment', 'requestno'=>$requestno, 'referenceno'=>$newreference_no);
			}else{
				$resultlist[]=array('status'=>'Error', 'message'=>'Your request information is not updated', 'requestno'=>'', 'referenceno'=>'');
			}
		} else{	
			$resultlist[]=array('status'=>'Error', 'message'=>'Your request information did not save in tabl_maintransaction', 'requestno'=>'', 'referenceno'=>'');
		}
	}
	return $resultlist;
}
// proceedtoimprestadjustment
function proceedtoimprestadjustment($conn,$companyid,$subcompanyid,$projectid,$loginname,$requestno,$approvedremark,$invoiceinfo){
	$countryid="";
	$requestinfo=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
	$resultlist=array();
	$paymentduedate="";
	foreach($requestinfo as $reqrow) {
		$request_date = $reqrow['request_date'];
		$requested_by = $reqrow['requested_by'];
		$paytoid = $reqrow['paytoid'];
		$payto = $reqrow['payto'];
		$paymentduedate = $reqrow['paymentduedate'];
		$imppayto=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"payto",$reqrow['payto']);
		if(count($imppayto)>0){
			foreach ($imppayto as $eeekey) {
				$to_account_id = $eeekey['imprestpaytoid'];
				$paytobankname = $eeekey['paytobankname'];
				$paytoaccountno = $eeekey['paytoaccountno'];
				$paytoifsc = $eeekey['paytoifsccode'];
			}
		}else{
			$paytobankname = $reqrow['payto_bankname'];
			$paytoaccountno = $reqrow['payto_accountno'];
			$paytoifsc = $reqrow['payto_bankifsccode'];
			$to_account_id = $reqrow['payto_accountid'];			
		}
		$payment_mode = $reqrow['payment_mode'];
		$projectid = $reqrow['projectid'];
		$subprojectid = $reqrow['subprojectid'];
		$totalpayamount = $reqrow['request_amount'];
		$from_accountid = $reqrow['from_accountid'];
		$from_bankname = $reqrow['from_bankname'];
		$from_accountno = $reqrow['from_accountno'];
		$newreference_no = "imprestadjustment";	
		$insqry="";	
		if(count($invoiceinfo)>0){
			$requestedamount=0;
			$tdspercent=0;
			$amounttopay=0;
			$tdsamount =0;
			$newimprestamount=0;
			$rqnext=0;
			$insqry="INSERT INTO imprest_account(idate, companyid, subcompanyid, projectid,subprojectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, imprest_payto, imprest_amount, adjustment_amount, adjustedamount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, beneficiary_name, document_status, invoice_type, invoice_no, invoice_date,documenttype, document_path, audiofilepath, voiceinstid, blobfile, blobfile_tblname, description,paytoid) VALUES";
			$insval="";			
			foreach ($invoiceinfo as $dtrow) {
				$restimprest_balance =0;
				$document_status ="";
				$invoice_type ="";
				$invoice_no ="";
				$invoice_date ="";
				$documenttype ="";
				$document_path ="";
				$audiofilepath ="";
				$voiceinstid ="";
				$blobfileid ="";
				$blobfile_tblname ="";
				$purpose_category ="";
				$purpose_categoryid ="";
				$purpose_subcategory ="";
				$purpose_subcategoryid ="";
				$purpose = "";
				$purpose_code ="";
				$requestedamount=$dtrow['requestedamount'];
				$tdscategory=$dtrow['tdscategory'];
				$tdspercent=$dtrow['tdspercent'];
				$amounttopay=$dtrow['amounttopay'];
				$tdsamount = $requestedamount - $amounttopay;
				//tds deduction
				if($tdspercent>0){
					tdsdeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$from_accountid,$dtrow['beneficiaryname'],$requestedamount,$amounttopay,$tdspercent,$tdscategory,$requestno,$newreference_no,$dtrow['invoiceno']);
				}
				
				$requestinfo=getrequestbreakdowndetails($conn,$companyid,$subcompanyid,$projectid,"sno",$dtrow['bsno']);
				foreach ($requestinfo as $pbrow){
					$purpose_category = $pbrow['purpose_category'];
					$purpose_categoryid = $pbrow['purpose_categoryid'];
					$purpose_subcategory = $pbrow['purpose_subcategory'];
					$purpose_subcategoryid = $pbrow['purpose_subcategoryid'];
					$purpose = $pbrow['purpose'];
					$purpose_code = $pbrow['purpose_code'];
					$document_status = $pbrow['document_status'];
					$invoice_type = $pbrow['invoice_type'];
					$invoice_no = $pbrow['invoice_no'];
					$invoice_date = $pbrow['invoice_date'];
					$documenttype = $pbrow['documenttype'];
					$document_path = $pbrow['filepath'];
					$audiofilepath = $pbrow['audiofilepath'];
					$voiceinstid = $pbrow['voiceinstid'];
					$blobfileid = $pbrow['blobfileid'];
					$blobfile_tblname = $pbrow['blobfile_tblname'];
					$description = $pbrow['description'];
								
					//imprest account
					if($insval){
						$insval.=",('".date('Y-m-d')."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountid."','".$from_accountno."','".$to_account_id."','".$paytoaccountno."','".$requestno."','".$newreference_no."','".$payto."', '0', '".$amounttopay."', '0', '".$purpose_categoryid."','".$purpose_category."','".$purpose_subcategoryid."','".$purpose_subcategory."','".$purpose."','".$purpose_code."','".addslashes($dtrow['beneficiaryname']??'')."','".$document_status."','".$invoice_type."','".$invoice_no."','".$invoice_date."','".$documenttype."','".addslashes($document_path??'')."','".$audiofilepath."','".$voiceinstid."','".addslashes($blobfileid??'')."','".addslashes($blobfile_tblname??'')."','".addslashes($pbrow['description']??'')."','".$paytoid."')";
					}else{
						$insval="('".date('Y-m-d')."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountid."','".$from_accountno."','".$to_account_id."','".$paytoaccountno."','".$requestno."','".$newreference_no."','".$payto."', '0', '".$amounttopay."', '0', '".$purpose_categoryid."','".$purpose_category."','".$purpose_subcategoryid."','".$purpose_subcategory."','".$purpose."','".$purpose_code."','".addslashes($dtrow['beneficiaryname']??'')."','".$document_status."','".$invoice_type."','".$invoice_no."','".$invoice_date."','".$documenttype."','".addslashes($document_path??'')."','".$audiofilepath."','".$voiceinstid."','".addslashes($blobfileid??'')."','".addslashes($blobfile_tblname??'')."','".addslashes($pbrow['description']??'')."','".$paytoid."')";
					}
				}
			}	//end of foreach
			if($insval){
				//echo $insqry.$insval;
				if ($conn->query($insqry.$insval)) {
					$imppayto = getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"payto",$payto);
					// print_r($imppayto);
					$imprestpaytoid ="";
					$paytobankname ="";
					$paytoaccountno ="";
					$paytoifsccode ="";
					if(count($imppayto)>0){
						foreach ($imppayto as $imppkey) {
							$imprestpaytoid = $imppkey['imprestpaytoid'];
							$paytobankname = $imppkey['paytobankname'];
							$paytoaccountno = $imppkey['paytoaccountno'];
							$paytoifsccode = $imppkey['paytoifsccode'];
						}
					}
					$imprestreqamount=GetRestImprestAmount($conn,$companyid,$subcompanyid,$projectid,$payto);
					if($imprestreqamount<0){
						$imprestreqamount=str_replace("-", "", $imprestreqamount);
						if($imprestreqamount>100){
							$fromaccountid="";
							$purposetype="";
							$modeoftransfer="online";
							$description="Auto genrated request after imprest adjustment";
							
							// $loginname="Auto";
							$filepath="";
							$audiouploadFile="";
							$voiceinstid="";
							$filepath_fromemail="";
							$attsno_fromemail="";
							$emailsno_fromemail="";
							$requestdatatype = "Pre-Payment";
							$invoicedate="";
							$invoiceno="";
							$invoicetype="";
							$invoicedata=array();
							$invoice_totalrowsamount="";
							$invoice_totalgstamount="";
							$invoice_totalrowsdiscount="";
							$invoice_otherdiscount="";
							$invoice_totalpayableamount="";
							$purposecategoryid ="";
							$purposesubcategoryid ="";
							$purposecode ="";
							$purposename ="";
							$ppinfo=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecategoryname","imprest");
							if(count($ppinfo)>0){
								foreach ($ppinfo as $impkey) {
									$purposecategoryid = $impkey['pcategorycode'];
									$purposesubcategoryid = $impkey['psub_categoryid'];
									$purposecode = $impkey['purposecode'];
									$purposename = $impkey['purpose'];
								}
							}
							$rrq=unapproved_saverequestdataforall($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$imprestpaytoid,$modeoftransfer,$imprestreqamount,$description,$purposecategoryid,$purposesubcategoryid,$purposecode,$purposename,$loginname,$filepath,$audiouploadFile,$voiceinstid,$filepath_fromemail,$attsno_fromemail,$emailsno_fromemail,$requestdatatype,$invoicedate,$invoiceno,$invoicetype,$invoicedata,$invoice_totalrowsamount, $invoice_totalgstamount, $invoice_totalrowsdiscount, $invoice_otherdiscount, $invoice_totalpayableamount);
							if(count($rrq)>0){
								foreach ($rrq as $krrey) {
									if($krrey['status']=="Success"){
										// $resultlist[]=array('status'=>'Success', 'message'=>'Imprest adjustment successfuly and new request genrated agains imprest request '.$krrey['requestno']);
										$selfupdate=$conn->query("update tbl_paymentrequest set approval_status='Payment Paid', status_by='".$loginname."', paid_for_ref='".$newreference_no."', uploadflag=null, approval_comment='".$approvedremark."', requestapprovedate='".date('Y-m-d H:i:s')."', status_cheque='pending', pending_description='Ready for Printing' where request_no = '".$requestno."'");
					
										if($selfupdate){
											$resultlist[]=array('status'=>'Success', 'message'=>'Your request '.$requestno.' details save successfully for payment');
										}else{
											$resultlist[]=array('status'=>'Error', 'message'=>'Your request information is not updated successfully');
										}
									}else{
										$resultlist[]=array('status'=>'Error', 'message'=>'After Imprest adjustment new requst did not create due to technical issue.');
									}
								}
							}else{
								$resultlist[]=array('status'=>'Error', 'message'=>'Error in new reqeust genration after Imprest adjustment');
							}
						}
					}else{
						$selfupdate=$conn->query("update tbl_paymentrequest set approval_status='Payment Paid', status_by='".$loginname."', paid_for_ref='".$newreference_no."', uploadflag=null, approval_comment='".$approvedremark."', requestapprovedate='".date('Y-m-d H:i:s')."', status_cheque='pending', pending_description='Ready for Printing' where request_no = '".$requestno."'");
					
						if($selfupdate){
							$resultlist[]=array('status'=>'Success', 'message'=>'Your request '.$requestno.' details save successfully for payment');
						}else{
							$resultlist[]=array('status'=>'Error', 'message'=>'Your request information is not updated successfully');
						}
					}
				}else{	
					$resultlist[]=array('status'=>'Error', 'message'=>'Your request information did not save, due to something wrong');
				}
			}else{
				$resultlist[]=array('status'=>'Error', 'message'=>'Query Failed Error: Unexpected. Please try again');
			}
		}else{	
			$resultlist[]=array('status'=>'Error', 'message'=>'Your request information did not save, due to something wrong');
		}
	}	
	return $resultlist;
}
//partial adjustment
function proceedtopartialpaymentjustment($conn,$companyid,$subcompanyid,$projectid,$loginname,$requestno,$approvedremark,$invoiceinfo){
	$countryid="";
	$requestinfo=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
	$resultlist=array();		
	foreach($requestinfo as $reqrow) {
		$request_date = $reqrow['request_date'];
		$requested_by = $reqrow['requested_by'];
		$paytoid = $reqrow['paytoid'];
		$payto = $reqrow['payto'];
		$paytobankname = $reqrow['payto_bankname'];
		$paytoaccountno = $reqrow['payto_accountno'];
		$paytoifsc = $reqrow['payto_bankifsccode'];
		$to_account_id = $reqrow['payto_accountid'];
		$payment_mode = $reqrow['payment_mode'];
		$projectid = $reqrow['projectid'];
		$subprojectid = $reqrow['subprojectid'];
		$totalpayamount = $reqrow['request_amount'];
		$from_accountid = $reqrow['from_accountid'];
		$from_bankname = $reqrow['from_bankname'];
		$from_accountno = $reqrow['from_accountno'];
		$newreference_no = "imprestadjustment";	
		$insqry="";	
		if(count($invoiceinfo)>0){
			$requestedamount=0;
			$tdspercent=0;
			$amounttopay=0;
			$tdsamount =0;
			$newimprestamount=0;
			$rqnext=0;
			$insqry="INSERT INTO partial_account(idate, companyid, subcompanyid, projectid, subprojectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, imprest_payto, imprest_amount, adjustment_amount, adjustedamount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, beneficiary_name, document_status, invoice_type, invoice_no, invoice_date,documenttype, document_path, audiofilepath, voiceinstid, blobfile, blobfile_tblname, description,paytoid) VALUES";
			$insval="";			
			foreach ($invoiceinfo as $dtrow) {
				$restimprest_balance =0;
				$document_status ="";
				$invoice_type ="";
				$invoice_no ="";
				$invoice_date ="";
				$documenttype ="";
				$document_path ="";
				$audiofilepath ="";
				$voiceinstid ="";
				$blobfileid ="";
				$blobfile_tblname ="";
				$purpose_category ="";
				$purpose_categoryid ="";
				$purpose_subcategory ="";
				$purpose_subcategoryid ="";
				$purpose = "";
				$purpose_code ="";
				$requestedamount=$dtrow['requestedamount'];
				$tdscategory=$dtrow['tdscategory'];
				$tdspercent=$dtrow['tdspercent'];
				$amounttopay=$dtrow['amounttopay'];
				$tdsamount = $requestedamount - $amounttopay;
				//tds deduction
				if($tdspercent>0){
					tdsdeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$from_accountid,$dtrow['beneficiaryname'],$requestedamount,$amounttopay,$tdspercent,$tdscategory,$requestno,$newreference_no,$dtrow['invoiceno']);
				}
				
				$requestinfo=getrequestbreakdowndetails($conn,$companyid,$subcompanyid,$projectid,"sno",$dtrow['bsno']);
				foreach ($requestinfo as $pbrow){
					$purpose_category = $pbrow['purpose_category'];
					$purpose_categoryid = $pbrow['purpose_categoryid'];
					$purpose_subcategory = $pbrow['purpose_subcategory'];
					$purpose_subcategoryid = $pbrow['purpose_subcategoryid'];
					$purpose = $pbrow['purpose'];
					$purpose_code = $pbrow['purpose_code'];
					$document_status = $pbrow['document_status'];
					$invoice_type = $pbrow['invoice_type'];
					$invoice_no = $pbrow['invoice_no'];
					$invoice_date = $pbrow['invoice_date'];
					$documenttype = $pbrow['documenttype'];
					$document_path = $pbrow['filepath'];
					$audiofilepath = $pbrow['audiofilepath'];
					$voiceinstid = $pbrow['voiceinstid'];
					$blobfileid = $pbrow['blobfileid'];
					$blobfile_tblname = $pbrow['blobfile_tblname'];
					$description = $pbrow['description'];
								
					//imprest account
					if($insval){
						$insval.=",('".date('Y-m-d')."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountid."','".$from_accountno."','".$to_account_id."','".$paytoaccountno."','".$requestno."','".$newreference_no."','".$payto."', '0', '".$amounttopay."', '0', '".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($dtrow['beneficiaryname']??'')."','".$document_status."','".$invoice_type."','".$invoice_no."','".$invoice_date."','".$documenttype."','".addslashes($document_path??'')."','".$audiofilepath."','".$voiceinstid."','".addslashes($blobfileid??'')."','".addslashes($blobfile_tblname??'')."','".addslashes($pbrow['description']??'')."','".$paytoid."')";
					}else{
						$insval="('".date('Y-m-d')."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountid."','".$from_accountno."','".$to_account_id."','".$paytoaccountno."','".$requestno."','".$newreference_no."','".$payto."', '0', '".$amounttopay."', '0', '".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($dtrow['beneficiaryname']??'')."','".$document_status."','".$invoice_type."','".$invoice_no."','".$invoice_date."','".$documenttype."','".addslashes($document_path??'')."','".$audiofilepath."','".$voiceinstid."','".addslashes($blobfileid??'')."','".addslashes($blobfile_tblname??'')."','".addslashes($pbrow['description']??'')."','".$paytoid."')";
					}
				}
			}	//end of foreach
			if($insval){
				//echo $insqry.$insval;
				if ($conn->query($insqry.$insval)) {
					$paytodd =getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
					if(count($paytodd)>0){
						foreach ($paytodd as $imppkey) {
							$paytoid = $imppkey['paytoid'];
							$paytobankname = $imppkey['tobankname'];
							$paytoaccountno = $imppkey['toaccountno'];
							$paytoifsccode = $imppkey['tobankifsc'];
						}
					}
					$restpartial=GetRestPartialAmount($conn,$companyid,$subcompanyid,$projectid,$payto);
					// print_r($restpartial);
					if($restpartial<0){
						$restpartial=str_replace("-", "", $restpartial);
						if($restpartial>0){
							$fromaccountid="";
							$purposetype="Partial Payment";
							$modeoftransfer="online";
							$description="Auto genrated request after partial adjustment";
							
							// $loginname="Auto";
							$filepath="";
							$audiouploadFile="";
							$voiceinstid="";
							$filepath_fromemail="";
							$attsno_fromemail="";
							$emailsno_fromemail="";
							$requestdatatype = "Pre-Payment";
							$invoicedate="";
							$invoiceno="";
							$invoicetype="";
							$invoicedata=array();
							$invoice_totalrowsamount="";
							$invoice_totalgstamount="";
							$invoice_totalrowsdiscount="";
							$invoice_otherdiscount="";
							$invoice_totalpayableamount="";
							$purposecategoryid ="";
							$purposesubcategoryid ="";
							$purposecode ="";
							$purposename ="";
							$ppinfo=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecategorynamelikely","partial");
							if(count($ppinfo)>0){
								foreach ($ppinfo as $impkey) {
									$purposecategoryid = $impkey['pcategorycode'];
									$purposesubcategoryid = $impkey['psub_categoryid'];
									$purposecode = $impkey['purposecode'];
									$purposename = $impkey['purpose'];
								}
							}
						
							$rrq=unapproved_saverequestdataforall($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$paytoid,$modeoftransfer,$restpartial,$description,$purposecategoryid,$purposesubcategoryid,$purposecode,$purposename,$loginname,$filepath,$audiouploadFile,$voiceinstid,$filepath_fromemail,$attsno_fromemail,$emailsno_fromemail,$requestdatatype,$invoicedate,$invoiceno,$invoicetype,$invoicedata,$invoice_totalrowsamount, $invoice_totalgstamount, $invoice_totalrowsdiscount, $invoice_otherdiscount, $invoice_totalpayableamount);
							if(count($rrq)>0){
								foreach ($rrq as $krrey) {
									if($krrey['status']=="Success"){
										// $resultlist[]=array('status'=>'Success', 'message'=>'Partial adjustment successfuly and new request genrated agains partial request '.$krrey['requestno']);
										$selfupdate=$conn->query("update tbl_paymentrequest set approval_status='Payment Paid', status_by='".$loginname."', paid_for_ref='".$newreference_no."', uploadflag=null, approval_comment='".$approvedremark."', requestapprovedate='".date('Y-m-d H:i:s')."', status_cheque='pending', pending_description='Ready for Printing' where request_no = '".$requestno."'");
					
										if($selfupdate){
											$resultlist[]=array('status'=>'Success', 'message'=>'Your request '.$requestno.' details save successfully for payment');
										}else{
											$resultlist[]=array('status'=>'Error', 'message'=>'Your request information is not updated successfully');
										}
									}else{
										$resultlist[]=array('status'=>'Error', 'message'=>'After partial adjustment new requst did not create due to technical issue.');
									}
								}
							}else{
								$resultlist[]=array('status'=>'Error', 'message'=>'Error in new reqeust genration after partial adjustment');
							}
						}
					}else{
						$selfupdate=$conn->query("update tbl_paymentrequest set approval_status='Payment Paid', status_by='".$loginname."', paid_for_ref='".$newreference_no."', uploadflag=null, approval_comment='".$approvedremark."', requestapprovedate='".date('Y-m-d H:i:s')."', status_cheque='pending', pending_description='Ready for Printing' where request_no = '".$requestno."'");
					
						if($selfupdate){
							$resultlist[]=array('status'=>'Success', 'message'=>'Your request '.$requestno.' details save successfully for payment');
						}else{
							$resultlist[]=array('status'=>'Error', 'message'=>'Your request information is not updated successfully');
						}
					}
				}else{	
					$resultlist[]=array('status'=>'Error', 'message'=>'Your request information did not save, due to something wrong');
				}
			}else{
				$resultlist[]=array('status'=>'Error', 'message'=>'Query Failed Error: Unexpected. Please try again');
			}
		}else{	
			$resultlist[]=array('status'=>'Error', 'message'=>'Your request information did not save, due to something wrong');
		}
	}	
	return $resultlist;
}
//tds duduction
function tdsdeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$beneficiaryname,$requestedamount,$amounttopay,$tdspercent,$tdscategory,$request_no,$newreference_no,$invoiceno){
	$tds_deducted = ($requestedamount*$tdspercent)/100;
	$tdsinsert = "insert into tds_account_summery(companyid, subcompanyid, projectid, subprojectid, bookdate, from_accountid, beneficiary_name, amount_paid, tds_deducted, tds_percent, tds_type, request_no, reference_no, bill_no) values('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".date('Y-m-d')."','".$accountid."','".$beneficiaryname."','".$amounttopay."','".$tds_deducted."','".$tdspercent."','".$tdscategory."','".$request_no."','".$newreference_no."','".$invoiceno."')";
	$tdsresult=$conn->query($tdsinsert) or die($conn->error);
}



//imprest data save
function saveimprestdata($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $from_accountid, $from_accountno, $to_accountid, $to_accountno, $request_no, $reference_no, $paytoid, $imprest_payto, $imprest_amount, $adjustment_amount, $adjustedamount, $purpose_categoryid, $purpose_category, $purpose_subcategoryid, $purpose_subcategory, $purpose, $purpose_code, $beneficiary_name, $document_status, $document_name, $document_path, $invoice_type, $invoice_no, $description){
	$insqry="INSERT INTO imprest_account(idate, companyid, subcompanyid, projectid,subprojectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, imprest_payto, imprest_amount, adjustment_amount, adjustedamount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, beneficiary_name, document_status, document_name, document_path, invoice_type, invoice_no, description,paytoid) VALUES('".date('Y-m-d')."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountid."','".$from_accountno."','".$to_accountid."','".$to_accountno."','".$request_no."','".$reference_no."','".addslashes($imprest_payto??'')."','".$imprest_amount."','".$adjustment_amount."','".$adjustedamount."','".$purpose_categoryid."','".addslashes($purpose_category??'')."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory??'')."','".addslashes($purpose??'')."','".$purpose_code."','".addslashes($beneficiary_name??'')."','".$document_status."','".addslashes($document_name??'')."','".addslashes($document_path??'')."','".$invoice_type."','".$invoice_no."','".addslashes($description??'')."','".$paytoid."')";
	$insqry_result=$conn->query($insqry) or die($conn->error);
	if($insqry_result){
		$resutl="successfully";
	}else{
		$resutl="failed";
	}
	return $resutl;
}

function saveimprestadjustmentdata($conn, $companyid, $subcompanyid, $projectid, $from_accountid, $from_accountno, $to_accountid, $to_accountno, $request_no, $reference_no, $imprest_payto, $imprest_amount, $adjustment_amount, $adjustedamount, $purpose_categoryid, $purpose_category, $purpose_subcategoryid, $purpose_subcategory, $purpose, $purpose_code, $beneficiary_name, $document_status, $invoice_type, $invoice_no, $invoice_date, $documenttype, $document_path, $audiofilepath, $voiceinstid,$blobfileid, $blobfile_tblname, $description){
	$insqry="INSERT INTO imprest_account(idate, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, imprest_payto, imprest_amount, adjustment_amount, adjustedamount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, beneficiary_name, document_status, invoice_type, invoice_no, invoice_date,documenttype, document_path, audiofilepath, voiceinstid, blobfile, blobfile_tblname, description) VALUES('".date('Y-m-d')."','".$companyid."','".$subcompanyid."','".$projectid."','".$from_accountid."','".$from_accountno."','".$to_accountid."','".$to_accountno."','".$request_no."','".$reference_no."','".$imprest_payto."','".$imprest_amount."','".$adjustment_amount."','".$adjustedamount."','".$purpose_categoryid."','".$purpose_category."','".$purpose_subcategoryid."','".$purpose_subcategory."','".$purpose."','".$purpose_code."','".addslashes($beneficiary_name??'')."','".$document_status."','".$invoice_type."','".$invoice_no."','".$invoice_date."','".$documenttype."','".addslashes($document_path??'')."','".$audiofilepath."','".$voiceinstid."','".addslashes($blobfile??'')."','".addslashes($blobfile_tblname??'')."','".addslashes($description??'')."')";
	$insqry_result=$conn->query($insqry) or die($conn->error);
	if($insqry_result){
		$resutl="successfully";
	}else{
		$resutl="failed";
	}
	return $resutl;
}

//partial data save
function savepartialpaymentdata($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $from_accountid, $from_accountno, $to_accountno, $request_no, $reference_no, $paytoid, $imprest_payto, $imprest_amount, $adjustment_amount, $adjustedamount, $purpose_categoryid, $purpose_category, $purpose_subcategoryid, $purpose_subcategory, $purpose, $purpose_code, $beneficiary_name, $document_status, $document_name, $document_path, $invoice_type, $invoice_no, $description){
	$insqry="INSERT INTO partial_account(idate, companyid, subcompanyid, projectid, subprojectid, from_accountid, from_accountno, to_accountno, request_no, reference_no, imprest_payto, imprest_amount, adjustment_amount, adjustedamount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, beneficiary_name, document_status, document_name, document_path, invoice_type, invoice_no, description, paytoid) VALUES('".date('Y-m-d')."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountid."','".$from_accountno."','".$to_accountno."','".$request_no."','".$reference_no."','".$imprest_payto."','".$imprest_amount."','".$adjustment_amount."','".$adjustedamount."','".$purpose_categoryid."','".addslashes($purpose_category??'')."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory??'')."','".addslashes($purpose??'')."','".$purpose_code."','".addslashes($beneficiary_name)."','".$document_status."','".addslashes($document_name??'')."','".addslashes($document_path??'')."','".$invoice_type."','".$invoice_no."','".addslashes($description??'')."','".$paytoid."')";
	$insqry_result=$conn->query($insqry) or die($conn->error);
	if($insqry_result){
		$resutl="successfully";
	}else{
		$resutl="failed";
	}
	return $resutl;
}

//loan reqest data save in loan account
function saveloandata($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$loanid,$from_accountno,$to_accountno,$request_no,$reference_no,$paytoid,$loan_payto,$loan_amount,$loanreturn_amount,$purpose,$purpose_code,$document_path,$blobfile,$blobfile_tblname,$description,$loginname){
	$insqry="INSERT INTO tbl_loanaccount_summary(idate,loanid,companyid,subcompanyid,projectid,subprojectid,from_accountno,to_accountno,paytoid,loan_payto,loan_amount,loan_returnamount,purpose,purposecode,description,document_path,blobfile,blobfile_tblname,requestno,referenceno,addedby) VALUES('".date('Y-m-d')."','".$loanid."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountno."','".$to_accountno."','".$paytoid."','".addslashes($loan_payto??'')."','".$loan_amount."','".$loanreturn_amount."','".addslashes($purpose??'')."','".addslashes($purpose_code)."','".addslashes($description??'')."','".addslashes($document_path??'')."','".$blobfile."','".$blobfile_tblname."','".$request_no."','".$reference_no."','".addslashes($loginname??'')."')";
	$insqry_result=$conn->query($insqry) or die($conn->error);
	if($insqry_result){
		$resutl="successfully";
	}else{
		$resutl="failed";
	}
	return $resutl;
}

// //loan reqest data save in loan account
// function saveloandata($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$loanid,$from_accountno,$to_accountno,$request_no,$reference_no,$paytoid,$loan_payto,$loan_amount,$loanreturn_amount,$purpose,$purpose_code,$document_path,$blobfile,$blobfile_tblname,$description,$loginname){
// 	$insqry="INSERT INTO tbl_loanaccount_summary(idate,loanid,companyid,subcompanyid,projectid,subprojectid,from_accountno,to_accountno,paytoid,loan_payto,loan_amount,loan_returnamount,purpose,purposecode,description,document_path,blobfile,blobfile_tblname,requestno,referenceno,addedby) VALUES('".date('Y-m-d')."','".$loanid."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$from_accountno."','".$to_accountno."','".$paytoid."','".addslashes($loan_payto??'')."','".$loan_amount."','".$loanreturn_amount."','".addslashes($purpose??'')."','".addslashes($purpose_code)."','".addslashes($description??'')."','".addslashes($document_path??'')."','".$blobfile."','".$blobfile_tblname."','".$request_no."','".$reference_no."','".addslashes($loginname??'')."')";
// 	$insqry_result=$conn->query($insqry) or die($conn->error);
// 	if($insqry_result){
// 		$resutl="successfully";
// 	}else{
// 		$resutl="failed";
// 	}
// 	return $resutl;
// }
/*
function advancedeductionsummary($conn,$advancedeductamount,$requestno,$referenceno,$centreid,$centrename,$identifier,$account_id,$bankaccountno,$beneficiarynam,$monthofsalary){
	$select_adv_id ="select max(adv_id) as adv_id from advance_dedection_account_summery";
	$slct_adv=$conn->query($select_adv_id);
	if($slct_adv->num_rows>0)
	{
		$row_adv = $slct_adv->fetch_assoc();
		$max_adv_id = $row_adv['adv_id'];
		$new_adv_id = $max_adv_id + 1;
	}
	$date = date('Y-m-d');
	$adv_dd_insert = "insert into advance_dedection_account_summery(adv_id, date, emp_name, request_no, reference_no, centre_id, centre_name, centre_identifier, account_id, account_no, debit_amount, description, month_year_salary) values('".$new_adv_id."','".$date."','".$beneficiarynam."','".$requestno."','".$referenceno."','".$centreid."','".$centrename."','".$identifier."','".$account_id."','".$bankaccountno."','".$advancedeductamount."', 'Advance Deduction', '".$monthofsalary."')";
	$adv_dd_insert_result=$conn->query($adv_dd_insert);

}


function esicaccountsummary($conn,$esicpercent,$grosssalary,$basicsalarye,$amounttopay,$requestno,$referenceno,$centrename,$identifier,$account_id,$beneficiarynam,$ncepdays,$monthofsalary,$payableworking_days){
	$selectsno ="select max(esic_id) as esic_id from esic_account_summery";
	$slct_esic=$conn->query($selectsno);
	if($slct_esic->num_rows>0)
	{
		$row_esic = $slct_esic->fetch_assoc();
		$max_esic_id = $row_esic['esic_id'];
		$new_esic_id = $max_esic_id + 1;
	}
	
	$esic_deducted = ($grosssalary*$esicpercent)/100;
	$current_date =  date('Y-m-d');
	$esic_insert = "insert into esic_account_summery(esic_id, date, centre_name, centre_identifier, account_id, emp_name, payable_gross, payable_basic, amount_paid, payable_workingdays, esic_deducted, percent_deducted, reference_no, month_year_salary) values('".$new_esic_id."','".$current_date."','".$centrename."','".$identifier."','".$account_id."','".$beneficiarynam."','".$grosssalary."','".$basicsalarye."','".$amounttopay."','".$payableworking_days."','".$esic_deducted."','".$esicpercent."','".$referenceno."','".$monthofsalary."')";
	$esic_result=$conn->query($esic_insert);

}

function epfaccountsummary($conn,$epfpercent,$grosssalary,$basicsalarye,$amounttopay,$requestno,$referenceno,$centrename,$identifier,$account_id,$beneficiarynam,$ncepdays,$monthofsalary){
	$selectsno ="select max(epf_id) as epf_id from epf_account_summery";
	$select_epf_result=$conn->query($selectsno);
	if($select_epf_result->num_rows>0)
	{
		$row_epf = $select_epf_result->fetch_assoc();
		$max_epf_id = $row_epf['epf_id'];
		$new_epf_id = $max_epf_id + 1;
	}
	
	$epf_deducted = ($basicsalarye*$epfpercent)/100;
	
	$current_date =  date('Y-m-d');
	$epf_insert = "insert into epf_account_summery(epf_id, date, centre_name, centre_identifier, account_id, emp_name, payable_gross, payable_basic, amount_paid, epf_deducted, percent_deducted, reference_no, ncp_days, month_year_salary) values('".$new_epf_id."','".$current_date."','".$centrename."','".$identifier."','".$account_id."','".$beneficiarynam."','".$grosssalary."','".$basicsalarye."','".$amounttopay."','".$epf_deducted."','".$epfpercent."','".$referenceno."','".$ncepdays."','".$monthofsalary."')";
	$epf_result=$conn->query($epf_insert);
}

function securitydepositaccount($conn,$requestno,$newreference_no,$centreid,$identifier,$centrename,$payto,$accountid,$purpose,$purposecode,$completepurposecode,$beneficiaryname,$description,$amounttopay,$paymentmode,$invoiceno){
	$sltmax_id ="select max(id) as maxid from securitydeposit_account";
	$sltmax_id_result=$conn->query($sltmax_id);
	if($sltmax_id_result->num_rows>0)
	{
		$mxrow = $sltmax_id_result->fetch_assoc();
		$mxid = $mxrow['maxid'];
		$newmxid = $mxid + 1;
	}
	$current_date =  date('Y-m-d');
	$imprest_sql = "insert into securitydeposit_account (id, date, request_number, reference_number, centre_id, centre_identifier, centre_name, imprest_payto, imprest_amount, purpose, purpose_code, complete_purpose_code, beneficiary_name, description, transaction_type, account_id,invoice_no) values ('".$newmxid."','".date('Y-m-d')."','".$requestno."','".$newreference_no."','".$centreid."','".$identifier."','".$centrename."','".addslashes($payto)."','".$amounttopay."','".$purpose."','".$purposecode."','".$completepurposecode."','".addslashes($beneficiaryname)."','".addslashes($description)."','".$paymentmode."','".$accountid."','".$invoiceno."')";
	$inprest_result=$conn->query($imprest_sql);
	if($inprest_result){
		// echo "<br>inserted";
	}else{
		// echo "<br>not inserted";

	}
}*/

function updatestocktable($conn,$requestno,$newreference_no,$projectid){
	//check stock available or not
	$stockq=$conn->query("SELECT centre_name FROM tbl_stock where request_no='".$requestno."' and centre_id='".$projectid."' and (Tedit is null or Tedit='')");
	if($stockq->num_rows>0){
		$strow=$stockq->fetch_assoc();
		$stockcentrename=$strow['centre_name'];
		$updstock=$conn->query("UPDATE tbl_stock SET reference_no='".$newreference_no."' where request_no='".$requestno."'and centre_id='".$projectid."' and (Tedit is null or Tedit='')");
	}else{
		$stockcentrename="";
	}
	return $stockcentrename;
}

function rejectrequestfinal($conn,$loginname,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid,$status='Request Rejected'){
	$rrqry="UPDATE tbl_paymentrequest SET approval_status='".$status."', tedit='".date('Y-m-d H:i:s')."', status_by='".$loginname."', tedit_remark='".$rejectremark."' where request_no='".$requestno."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
	$rejqry=$conn->query($rrqry) or die($conn->error);
	if($rejqry){
		$rslt=$conn->query("SELECT sno,beneficiery_name,invoice_no, ammount FROM tbl_paymentrequest_breakdown WHERE request_no='".$requestno."' and (tedit is null or tedit='')");
		if($rslt->num_rows>0){
			$beneficieryname="";
			$invoiceno="";
			$beneficieryamount="";
			$sno="";
			while($bsrow=$rslt->fetch_assoc()){
				$beneficieryname=$bsrow['beneficiery_name'];
				$invoiceno=$bsrow['invoice_no'];
				$beneficieryamount=$bsrow['ammount'];
				$sno=$bsrow['sno'];
				updatebrequestbreakdown($conn,$sno,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid);
				updateTeditInvoicetable($conn,$requestno,$companyid,$subcompanyid,$projectid,$rejectremark,$invoiceno,$loginname);
				// updateTeditrefundabletable($conn,$requestno,$beneficieryname,$invoiceno,$rejectremark,$companyid,$subcompanyid,$projectid);
			}
		}
		$emialmsg = sendemailrejectrequest($conn,$authconn,$loginname,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid);
		$resultarray[]=array('status'=>'Success', 'message'=>'Request Rejected Successfully');

	}else{
		$resultarray[]=array('status'=>'Error', 'message'=>'Request not Rejected due to error in updation query');
	}
	return $resultarray;
}

function Request_Addwaitinglist($conn,$loginname,$requestno,$waitremark,$companyid,$subcompanyid,$projectid,$status='Added in Waiting'){
	$waitremark = $waitremark.' on date '.date('Y-m-d');
	$rrqry="UPDATE tbl_paymentrequest SET approval_status='".$status."', approval_comment='".$waitremark."', status_by='".$loginname."' where (tedit is null or tedit='') and request_no='".$requestno."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
	$rejqry=$conn->query($rrqry) or die($conn->error);
	if($rejqry){
		$resultarray[]=array('status'=>'Success', 'message'=>'Request Added in Waiting list Successfully');

	}else{
		$resultarray[]=array('status'=>'Error', 'message'=>'Request did not add in waiting list, due to error in updation query');
	}
	return $resultarray;
}



function removeinvoicedetails($conn,$requestno,$rhiddenbsno,$rdescription,$companyid,$subcompanyid,$projectid,$loginname){
	$description = $rdescription.' on date '.date('Y-m-d H:i:sa').' by '.$loginname;
	$rslt=$conn->query("SELECT sno,beneficiery_name,invoice_no,ammount FROM tbl_paymentrequest_breakdown WHERE request_no='".$requestno."' and sno='".$rhiddenbsno."'");
	$beneficieryname="";
	$invoiceno="";
	$sno="";
	$beneficieryamount="";
	$returnarray=array();
	if($rslt->num_rows>0){
		$bsrow=$rslt->fetch_assoc();
		$beneficieryname=$bsrow['beneficiery_name'];
		$invoiceno=$bsrow['invoice_no'];
		$beneficieryamount=$bsrow['ammount'];
		$sno=$bsrow['sno'];
		$rupd="UPDATE tbl_paymentrequest_breakdown SET tedit='".$requestno."', tedit_remark='Remove due to : ".addslashes($description)."', uploadflag=null WHERE request_no='".$requestno."' and sno='".$sno."'";
		$removeentry=$conn->query($rupd);
		if($removeentry){
			$ttlrqst=gettotalbreakdownamount($conn,$requestno,$companyid,$subcompanyid,$projectid);
			// print_r($ttlrqst);
			if(count($ttlrqst)>0){
				foreach ($ttlrqst as $bb) {
					$requested_amount=$bb['requested_amount'];
					$totalrow=$bb['totalrow'];
				}

				if((int)$requested_amount>0){
					if(updaterequesttable($conn,$requestno,$requested_amount,$companyid,$subcompanyid,$projectid)=="updated"){

						updateTeditInvoicetable($conn,$requestno,$companyid,$subcompanyid,$projectid,$rdescription,$invoiceno,$loginname);
						$returnarray[]=array('status'=>'Success', 'message'=>'Remove Successfully');

					}else{
						$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest');
					}
				}else{
					$rejectremark="All bredown entry remove";
					$rrs=rejectrequestfinal($conn,$loginname,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid,'Remove While Invoice');
					$returnarray=$rrs;
				}
			}else{
				$rejectremark="All bredown entry remove";
				$rrs=rejectrequestfinal($conn,$loginname,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid,'Remove While Invoice');
				$returnarray=$rrs;
			}
		}else{
			$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest_breakdown');

		}
	}else{
		$returnarray[]=array('status'=>'Error', 'message'=>'this information not availasble in tbl_paymentrequest_breakdown');
	}
	return $returnarray;
}

function removeinvoicedetailsfromEdit($conn,$requestno,$rhiddenbsno,$rdescription,$companyid,$subcompanyid,$projectid,$loginname){
	$description = $rdescription.' on date '.date('Y-m-d H:i:sa').' by '.$loginname;
	$rslt=$conn->query("SELECT sno,beneficiery_name,invoice_no,ammount FROM tbl_paymentrequest_breakdown WHERE request_no='".$requestno."' and sno='".$rhiddenbsno."'");
	$beneficieryname="";
	$invoiceno="";
	$sno="";
	$beneficieryamount="";
	$returnarray=array();
	if($rslt->num_rows==1){
		$bsrow=$rslt->fetch_assoc();
		$beneficieryname=$bsrow['beneficiery_name'];
		$invoiceno=$bsrow['invoice_no'];
		$beneficieryamount=$bsrow['ammount'];
		$sno=$bsrow['sno'];
		$rupd="UPDATE tbl_paymentrequest_breakdown SET tedit='".$requestno."', tedit_remark='Remove due to : ".addslashes($description)."', uploadflag=null WHERE request_no='".$requestno."' and sno='".$sno."'";
		$removeentry=$conn->query($rupd);
		if($removeentry){
			$ttlrqst=gettotalbreakdownamount($conn,$requestno,$companyid,$subcompanyid,$projectid);
			// print_r($ttlrqst);
			if(count($ttlrqst)>0){
				foreach ($ttlrqst as $bb) {
					$requested_amount=$bb['requested_amount']??0;
					$totalrow=$bb['totalrow'];
				}
				if((int)$requested_amount>0){
					if(updaterequesttable($conn,$requestno,$requested_amount,$companyid,$subcompanyid,$projectid)=="updated"){

						updateTeditInvoicetable($conn,$requestno,$companyid,$subcompanyid,$projectid,$rdescription,$invoiceno,$loginname);
						$returnarray[]=array('status'=>'Success', 'message'=>'Remove Successfully');

					}else{
						$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest');
					}
				}else{
					$rejectremark=$description;
					$rrs=rejectrequestfinal($conn,$loginname,$requestno,$description,$companyid,$subcompanyid,$projectid,'Removed');
					$returnarray=$rrs;
				}
			}else{
				$rrs=rejectrequestfinal($conn,$loginname,$requestno,$description,$companyid,$subcompanyid,$projectid,'Removed');
				$returnarray=$rrs;
			}
		}else{
			$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest_breakdown');

		}
	}else if($rslt->num_rows>1){
		$bsrow=$rslt->fetch_assoc();
		$beneficieryname=$bsrow['beneficiery_name'];
		$invoiceno=$bsrow['invoice_no'];
		$beneficieryamount=$bsrow['ammount'];
		$sno=$bsrow['sno'];
		$rupd="UPDATE tbl_paymentrequest_breakdown SET tedit='".$requestno."', tedit_remark='Remove due to : ".addslashes($description)."', uploadflag=null WHERE request_no='".$requestno."' and sno='".$sno."'";
		$removeentry=$conn->query($rupd);
		if($removeentry){
			$ttlrqst=gettotalbreakdownamount($conn,$requestno,$companyid,$subcompanyid,$projectid);
			// print_r($ttlrqst);
			if(count($ttlrqst)>0){
				foreach ($ttlrqst as $bb) {
					$requested_amount=$bb['requested_amount'];
					$totalrow=$bb['totalrow'];
				}
				if((int)$requested_amount>0){
					if(updaterequesttable($conn,$requestno,$requested_amount,$companyid,$subcompanyid,$projectid)=="updated"){

						updateTeditInvoicetable($conn,$requestno,$companyid,$subcompanyid,$projectid,$rdescription,$invoiceno,$loginname);
						$returnarray[]=array('status'=>'Success', 'message'=>'Remove Successfully');

					}else{
						$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest');
					}
				}else{
					$rejectremark="All bredown entry remove";
					$rrs=rejectrequestfinal($conn,$loginname,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid,'Remove While Invoice');
					$returnarray=$rrs;
				}
			}else{
				$rejectremark="All bredown entry remove";
				$rrs=rejectrequestfinal($conn,$loginname,$requestno,$rejectremark,$companyid,$subcompanyid,$projectid,'Remove While Invoice');
				$returnarray=$rrs;
			}
		}else{
			$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest_breakdown');

		}
	}else{
		$returnarray[]=array('status'=>'Error', 'message'=>'this information not availasble in tbl_paymentrequest_breakdown');
	}
	return $returnarray;
}

function updatebrequestbreakdown($conn,$sno,$requestno,$rdescription,$companyid,$subcompanyid,$projectid){
	$description = $rdescription.' '.date('Y-m-d H:i:sa');
	$rupd=$conn->query("UPDATE tbl_paymentrequest_breakdown SET tedit='".$requestno."', tedit_remark='".addslashes($description)."', uploadflag=null WHERE request_no='".$requestno."' and sno='".$sno."'");
}

function updateTeditrefundabletable($conn,$requestno,$beneficieryname,$invoiceno,$rdescription,$companyid,$subcompanyid,$projectid){
	$description = $rdescription.' '.date('Y-m-d H:i:sa');
	$refunupd=$conn->query("UPDATE tbl_askt_refundablestock SET Tedit='".$requestno."', Tedit_remark='".addslashes($description)."', uploadflag=null WHERE request_no='".$requestno."' and bnf_name='".$beneficieryname."' and invoice_no='".$invoiceno."'  and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
}

function updateTeditstocktable($conn,$requestno,$beneficieryname,$invoiceamount,$rdescription,$companyid,$subcompanyid,$projectid){
	$description = $rdescription.' '.date('Y-m-d H:i:sa');
	$stockq="UPDATE tbl_stock SET Tedit='".date('Y-m-d H:i:sa')."', uploadflag=null WHERE request_no='".$requestno."' and itembnfname='".$beneficieryname."' and amount='".$invoiceamount."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
	$refunupd=$conn->query($stockq);
}

function updateTeditInvoicetable($conn,$requestno,$companyid,$subcompanyid,$projectid,$rdescription,$invoiceno,$loginname){
	$description = $rdescription.' '.date('Y-m-d H:i:sa'). ' '.$loginname;
	$stockq="UPDATE tbl_invoicespurchase_breakdown SET tedit='".$description."', uploadflag=null WHERE requestno='".$requestno."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and invoiceno='".$invoiceno."'";
	$conn->query($stockq);
	if(mysqli_affected_rows($conn)>0){
		$conn->query("UPDATE tbl_invoicespurchase_main SET tedit='".$description."', uploadflag=null WHERE requestno='".$requestno."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and invoiceno='".$invoiceno."'");		
	}
}



function updateApprovedInvoicetable($conn,$invoiceno,$requestno,$referenceno,$companyid,$subcompanyid,$projectid){
	$description = 'Approved on date '.date('Y-m-d H:i:sa');
	$stockq="UPDATE tbl_invoicespurchase_breakdown SET referenceno='".$referenceno."', status='".$description."', uploadflag=null WHERE requestno='".$requestno."' and invoiceno='".$invoiceno."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
	$refunupd=$conn->query($stockq);
}


function updateeditedrequest($conn,$requestno,$ehiddenbsno,$ebeneficiaryname,$einvoiceamount,$epurposecode,$einovoicetype,$einvoiceno,$edescription,$eattachment,$companyid,$subcompanyid,$projectid){
	$projectinfo = projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	$projectname="";
	$documentsavetype="";
	$returnarray=array();
	foreach ($projectinfo as $projrow) {
		$projectname=$projrow['projectname'];
		$documentsavetype=$projrow['documenttype'];
	}
	$emaildocsno="";
	$extension="";
	$docupload="";
	$att=1;
	if($emaildocsno!=""){
		if($documentsavetype=="physical" || $documentsavetype=="" || $documentsavetype==null){
			$newfilepath="";
			if($eattachment!=""){
				$base64 = $eattachment;
				list($type, $data) = explode(';', $eattachment);
				$local_file_name_type= $type;
				list(, $extension) = explode('/', $local_file_name_type);
				list(, $datastring)      = explode(',', $data);
				
				$directory = '../../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/requestdocument/'.$requestno.'/';
				if (!file_exists($directory)) {
					mkdir($directory, 0755, true);
				}
				$newfilepath=$directory.$requestno."_".$att.$ehiddenbsno;
				$att++;

				if (stristr($base64,"data:application/pdf;base64,"))
				{			 
					$base_new64 = str_replace("data:application/pdf;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'pdf');		 
					//$newfilepath =$filename.".pdf";	 		 
				}
				else if (stristr($base64,"data:image/jpeg;base64,"))		 
				{			 
					$base_new64 = str_replace("data:image/jpeg;base64,","",$base64);		
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'jpeg');
					//$newfilepath =$filename.".jpeg";	
				}
				else if (stristr($base64,"data:image/png;base64,"))		 
				{			 
					$base_new64 = str_replace("data:image/png;base64,","",$base64);		
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'png');
					//$newfilepath =$filename.".png";		 
				}
				else if (stristr($base64,"data:@file/msword;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/msword;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'msword');	 
					//$newfilepath =$filename.".doc";	 		 
				}
				else if (stristr($base64,"data:@file/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'document');		 
					// $newfilepath =$filename.".docx"; 		 
				}
				else if (stristr($base64,"data:@file/vnd.ms-excel;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/vnd.ms-excel;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'excel');	 
					//$newfilepath =$filename.".xls";	 		 
				}				 
				else if (stristr($base64,"data:@file/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'spreadsheetml.sheet');		 
					// $newfilepath =$filename.".xlsx"; 		 
				}
				else if (stristr($base64,"data:text/plain;base64,"))
				{			 
					$base_new64 = str_replace("data:text/plain;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'plain');		 
					// $newfilepath =$filename.".txt";	 		 
				}				 
				else if (stristr($base64,"data:audio/mpeg;base64,"))
				{			 
					$base_new64 = str_replace("data:audio/mpeg;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'mpeg');	 
					// $newfilepath =$filename.".mp3";	 		 
				}		
				else if (stristr($base64,"data:audio/wav;base64,"))
				{			 
					$base_new64 = str_replace("data:audio/wav;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'wav');	 
					//$newfilepath =$filename.".wav";	 		 
				}
				else if (stristr($base64,"data:text/html;base64,"))
				{			 
					$base_new64 = str_replace("data:text/html;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'html');		 
					//$newfilepath =$filename.".html";	 		 
				}		 
				else if (stristr($base64,"data:image/gif;base64,"))
				{			 
					$base_new64 = str_replace("data:image/gif;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'gif');	 
					//$newfilepath =$filename.".gif";	 		 
				}
			}
			$newfilepath = $newfilepath.'.'.$extension;
			$docupload=",filepath='".addslashes(str_replace('../', '',$newfilepath))."',extension='".$extension."', blobfileid='', blobfile_tblname=''";
		}else if($documentsavetype=="binarytodatabase"){
			$docinfo = getdocumentinfo($conn,$emaildocsno);
			$eattachment ="";
			foreach ($docinfo as $docrow) {
				$eattachment = $docrow['docbase64'];
			}
			list($type, $data) = explode(';', $eattachment);
			$local_file_name_type= $type;
			list(, $extension) = explode('/', $local_file_name_type);
			list(, $datastring)      = explode(',', $data);
			$filecontent=base64_decode($datastring);
			if($filecontent!=""){
				$rrr= $conn->query("Select database() as newdbname");
				$ndbname="";
				if($rrr->num_rows>0){
					$rrrw=$rrr->fetch_assoc();
					$ndbname=$rrrw['newdbname'];
				}

				// $ndbname="fms_amanmovement";
				$newdbtblname="tbl_paymentrequest_documents_".date('mY');
				$structuretable="tbl_paymentrequest_documents";
				chktableexists($conn,$ndbname,$newdbtblname,$structuretable);
				$blobfile=storerequestdocument_global($conn,$projectid,$requestno,$ebeneficiaryname,$einvoiceamount,$filecontent,$extension,$newdbtblname);
				$blobfile_tblname = $newdbtblname;
			}
			$docupload=",filepath='',extension='".$extension."', blobfileid='".$blobfile."', blobfile_tblname='".$blobfile_tblname."'";
		}
	}else{
		if($documentsavetype=="physical" || $documentsavetype==""){
			$newfilepath="";
			if($eattachment!=""){
				$base64 = $eattachment;
				list($type, $data) = explode(';', $eattachment);
				$local_file_name_type= $type;
				list(, $extension) = explode('/', $local_file_name_type);
				list(, $datastring)      = explode(',', $data);
				
				$directory = '../../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/requestdocument/'.$requestno.'/';
				if (!file_exists($directory)) {
					mkdir($directory, 0755, true);
				}

				$newfilepath=$directory.$requestno."_".$att.$ehiddenbsno;
				$att++;

				if (stristr($base64,"data:application/pdf;base64,"))
				{			 
					$base_new64 = str_replace("data:application/pdf;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'pdf');		 
					//$newfilepath =$filename.".pdf";	 		 
				}
				else if (stristr($base64,"data:image/jpeg;base64,"))		 
				{			 
					$base_new64 = str_replace("data:image/jpeg;base64,","",$base64);		
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'jpeg');
					//$newfilepath =$filename.".jpeg";	
				}
				else if (stristr($base64,"data:image/png;base64,"))		 
				{			 
					$base_new64 = str_replace("data:image/png;base64,","",$base64);		
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'png');
					//$newfilepath =$filename.".png";		 
				}
				else if (stristr($base64,"data:@file/msword;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/msword;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'msword');	 
					//$newfilepath =$filename.".doc";	 		 
				}
				else if (stristr($base64,"data:@file/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'document');		 
					// $newfilepath =$filename.".docx"; 		 
				}
				else if (stristr($base64,"data:@file/vnd.ms-excel;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/vnd.ms-excel;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'excel');	 
					//$newfilepath =$filename.".xls";	 		 
				}				 
				else if (stristr($base64,"data:@file/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,"))
				{			 
					$base_new64 = str_replace("data:@file/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'spreadsheetml.sheet');		 
					// $newfilepath =$filename.".xlsx"; 		 
				}
				else if (stristr($base64,"data:text/plain;base64,"))
				{			 
					$base_new64 = str_replace("data:text/plain;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'plain');		 
					// $newfilepath =$filename.".txt";	 		 
				}				 
				else if (stristr($base64,"data:audio/mpeg;base64,"))
				{			 
					$base_new64 = str_replace("data:audio/mpeg;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'mpeg');	 
					// $newfilepath =$filename.".mp3";	 		 
				}		
				else if (stristr($base64,"data:audio/wav;base64,"))
				{			 
					$base_new64 = str_replace("data:audio/wav;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'wav');	 
					//$newfilepath =$filename.".wav";	 		 
				}
				else if (stristr($base64,"data:text/html;base64,"))
				{			 
					$base_new64 = str_replace("data:text/html;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'html');		 
					//$newfilepath =$filename.".html";	 		 
				}		 
				else if (stristr($base64,"data:image/gif;base64,"))
				{			 
					$base_new64 = str_replace("data:image/gif;base64,","",$base64);
					$codebase64 =convertbase64intofile_withextn($base_new64,$newfilepath,'gif');	 
					//$newfilepath =$filename.".gif";	 		 
				}
			}
			$newfilepath = $newfilepath.'.'.$extension;
			$docupload=",filepath='".addslashes(str_replace('../', '',$newfilepath))."',extension='".$extension."', blobfileid='', blobfile_tblname=''";
		}else if($documentsavetype=="binarytodatabase"){
			list($type, $data) = explode(';', $eattachment);
			$local_file_name_type= $type;
			list(, $extension) = explode('/', $local_file_name_type);
			list(, $datastring)      = explode(',', $data);
			// echo "dd: ".$datastring;
			$requestno=null;
			$filecontent=base64_decode($datastring);
			if($filecontent!=""){
				$rrr= $conn->query("Select database() as newdbname");
				$ndbname="";
				if($rrr->num_rows>0){
					$rrrw=$rrr->fetch_assoc();
					$ndbname=$rrrw['newdbname'];
				}

				// $ndbname="fms_amanmovement";
				$newdbtblname="tbl_paymentrequest_documents_".date('mY');
				$structuretable="tbl_paymentrequest_documents";
				chktableexists($conn,$ndbname,$newdbtblname,$structuretable);
				$blobfile=storerequestdocument_global($conn,$projectid,$requestno,$ebeneficiaryname,$einvoiceamount,$filecontent,$extension,$newdbtblname);
				$blobfile_tblname = $newdbtblname;
				$docupload=",filepath='',extension='".$extension."', blobfileid='".$blobfile."', blobfile_tblname='".$blobfile_tblname."'";
			}
		}		
	}
	
	//get purpose details
	$purposedetails=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$epurposecode);
	$purpose_category="";
	$purpose_categoryid="";
	$purpose_subcategory="";
	$purpose_subcategoryid="";
	$purpose="";
	$purpose_code="";
	if(count($purposedetails)>0){
		foreach ($purposedetails as $prrow) {
			$purpose_category=$prrow['pcategory'];
			$purpose_categoryid=$prrow['pcategorycode'];
			$purpose_subcategory=$prrow['psub_category'];
			$purpose_subcategoryid=$prrow['psub_categoryid'];		
			$purpose=$prrow['purpose'];
			$purpose_code=$prrow['purposecode'];		
		}
		$updqry="UPDATE tbl_paymentrequest_breakdown SET beneficiery_name='".$ebeneficiaryname."',ammount='".$einvoiceamount."', purpose_category='".$purpose_category."', purpose_categoryid='".$purpose_categoryid."', purpose_subcategory='".$purpose_subcategory."', purpose_subcategoryid='".$purpose_subcategoryid."', purpose='".$purpose."', purpose_code='".$purpose_code."', description='".$edescription."', invoice_no='".$einvoiceno."', invoice_type='".$einovoicetype."',uploadflag=null ".$docupload." WHERE request_no='".$requestno."' and sno='".$ehiddenbsno."'";
		$updqry_rslt=$conn->query($updqry);
		$requested_amount=0;
		$totalrow=0;
		if($updqry_rslt){
			$ttlrqst=gettotalbreakdownamount($conn,$requestno,$companyid,$subcompanyid,$projectid);
			foreach ($ttlrqst as $bb) {
				$requested_amount=$bb['requested_amount'];
				$totalrow=$bb['totalrow'];
			}

			if($requested_amount>0){
				if(updaterequesttable($conn,$requestno,$requested_amount,$companyid,$subcompanyid,$projectid)=="updated"){
					$returnarray[]=array('status'=>'Success', 'message'=>'Edited Successfully', 'total_rqamount'=>$requested_amount,'beneficiery_name'=>$ebeneficiaryname,'beneficiery_ammount'=>$einvoiceamount,'purpose_category'=>$purpose_category,'purpose'=>$purpose,'purpose_code'=>$purpose_code,'description'=>$edescription,'invoice_no'=>$einvoiceno,'invoice_type'=>$einovoicetype);
				}else{
					$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest');
				}
			}else{
				$returnarray[]=array('status'=>'Error', 'message'=>'requestno amount is zero (0)');
			}

		}else{
			$returnarray[]=array('status'=>'Error', 'message'=>'Not update in tbl_paymentrequest_breakdown');
		}
	}else{
		$returnarray[]=array('status'=>'Error', 'message'=>'Purpose details did not matched our master table');
	}
	return $returnarray;
}

function updaterequesttable($conn,$requestno,$requested_amount,$companyid,$subcompanyid,$projectid){
	$ssq=$conn->query("SELECT request_amount FROM tbl_paymentrequest WHERE request_no='".$requestno."' and (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
	if($ssq->num_rows>0){
		$rrw=$ssq->fetch_assoc();
		$uprqs=$conn->query("UPDATE tbl_paymentrequest SET request_amount='".$requested_amount."', before_editamount='".$rrw['request_amount']."', uploadflag=null WHERE request_no='".$requestno."' and (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
		if($uprqs){
			$updstatus="updated";
		}else{
			$updstatus="notupdated";
		}
	}else{
		$updstatus="notavailable";
	}
	return $updstatus;

}

function gettotalbreakdownamount($conn,$requestno,$companyid,$subcompanyid,$projectid){
	$slt="SELECT SUM(CAST(ammount AS DECIMAL(10,2))) as requested_amount, count(request_no) as totalrow FROM tbl_paymentrequest_breakdown WHERE (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and  request_no='".$requestno."'";
	$slt_qry=$conn->query($slt);
	$requestdetails=array();
	if($slt_qry->num_rows>0){
		$brow=$slt_qry->fetch_assoc();
		$requestdetails[]=array('requested_amount'=>$brow['requested_amount'], 'totalrow'=>$brow['totalrow']);
	}
	return $requestdetails;
}

function updatemodeoftransfer($conn,$requestno,$paytoname,$modeoftransfer,$pbankname,$pbankaccountno,$pbankifsccode, $companyid, $subcompanyid, $projectid){
	// echo "UPDATE tbl_paymentrequest SET payment_mode='".strtolower($modeoftransfer)."', payto_bankname='".$pbankname."', payto_accountno='".$pbankaccountno."', payto_bankifsccode='".$pbankifsccode."', uploadflag=null where request_no='".$requestno."' and lcase(payto)='".strtolower($paytoname)."'";

	$updqry = $conn->query("UPDATE tbl_paymentrequest SET payment_mode='".strtolower($modeoftransfer)."', payto_bankname='".$pbankname."', payto_accountno='".$pbankaccountno."', payto_bankifsccode='".$pbankifsccode."', uploadflag=null where request_no='".$requestno."' and lcase(payto)='".strtolower($paytoname)."'");
	if($updqry){
		$result[] =array('status'=>'Success', 'message'=>'Update mode of transfer successfully');
		// update bank details in payto master
		if($modeoftransfer!="Cheque"){
			updatepaytobankdetails($conn,$companyid, $subcompanyid, $projectid,$paytoname,$pbankname,$pbankaccountno,$pbankifsccode);
		}
	}else{
		$result[] =array('status'=>'Error', 'message'=>'Missing some information.');
	}	
	return $result;
}

function updatepaytobankdetails($conn,$companyid, $subcompanyid, $projectid,$paytoname,$pbankname,$pbankaccountno,$pbankifsccode){
	$bnkq=$conn->query("SELECT paytoname, accountno, bankname, ifsccode FROM tbl_payto_master where lcase(paytoname)='".strtolower(addslashes($paytoname))."' and (tedit is null or tedit='')");
	$updptarray=array();
	if($bnkq->num_rows>0){
		$pbrow=$bnkq->fetch_assoc();
		if($pbrow['accountno']==$pbankaccountno){
			$updptarray[]=array('status'=>'already', 'message'=>'Bank details already updated');
		}else{
			$updpt = $conn->query("UPDATE tbl_payto_master set accountno='".$pbankaccountno."', bankname='".$pbankname."', ifsccode='".$pbankifsccode."', uploadflag=null WHERE paytoname='".$paytoname."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
			if($updpt){
				$updptarray[]=array('status'=>'Success', 'message'=>'Bank details updated successfully');
			}else{
				$updptarray[]=array('status'=>'Error', 'message'=>'Bank details not updated');
			}
		}
	}else{
		$maxid = $conn->query("SELECT (max(paytoid)+1) as maxid FROM tbl_payto_master");
		if($maxid->num_rows>0){
			$mrow = $maxid->fetch_assoc();
			$maxid = $mrow['maxid'];
		}else{
			$maxid=1;
		}
		$paytoinsqry = $conn->query("INSERT INTO tbl_payto_master (paytoid, paytoname, accountno, bankname, ifsccode,cmp_id, subcmp_id, projectid) VALUES('".$maxid."','".$paytoname."','".$pbankaccountno."','".$pbankname."','".$pbankifsccode."','".$companyid."','".$subcompanyid."','".$projectid."')");
		if($paytoinsqry){
			$updptarray[]=array('status'=>'Success', 'message'=>'Bank details updated successfully');
		}else{
			$updptarray[]=array('status'=>'Error', 'message'=>'You went go wrong');
		}
	}
	return $updptarray;
}

function savetodbItaxDetails($conn, $companyid, $subcompanyid, $projectid, $paytoid, $paytoname, $request_no, 
    $reference_no, $itaxamount, $paidamount, $description) {    

    $bookdate = date('Y-m-d'); // Set bookdate before queries
    $rrmsg = array();

    // Check if the record already exists to prevent duplicates
    $checkSql = "SELECT COUNT(*) FROM itax_account_summery 
                 WHERE companyid = ? AND subcompanyid = ? AND projectid = ? 
                 AND paytoid = ? AND itaxamount = ? AND paidamount = ? AND description = ?";

    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt === false) {
        return array('status' => 'error', 'message' => 'SQL Prepare Error: ' . $conn->error);
    }
    
    $checkStmt->bind_param("iiisdds", $companyid, $subcompanyid, $projectid, $paytoid, $itaxamount, $paidamount, $description);
    $checkStmt->execute();
    $checkStmt->bind_result($exists);
    $checkStmt->fetch();
    $checkStmt->close();

    if ($exists > 0) {
        return array('status' => 'alreadyexist', 'message' => 'Duplicate entry exists. Record not inserted.');
    }

    // Insert if no duplicate is found
    $sql = "INSERT INTO itax_account_summery 
            (bookdate, companyid, subcompanyid, projectid, paytoid, paytoname, request_no, reference_no, itaxamount, paidamount, description) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return array('status' => 'error', 'message' => 'SQL Prepare Error: ' . $conn->error);
    }

    // Bind parameters
    $itaxamount = number_format((float)$itaxamount, 2, '.', '');
    $paidamount = number_format((float)$paidamount, 2, '.', '');
    $stmt->bind_param("siisssssdds", $bookdate, $companyid, $subcompanyid, $projectid, $paytoid, $paytoname, $request_no, $reference_no, $itaxamount, $paidamount, $description);

    if ($stmt->execute()) {
        $rrmsg = array('status' => 'success', 'message' => 'Record inserted successfully.');
    } else {
        $rrmsg = array('status' => 'error', 'message' => 'Error inserting record: ' . $stmt->error);
    }

    $stmt->close();
    return $rrmsg;
}


?>