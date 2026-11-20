<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/emailsend_request_functions.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="sendtoemail_personlist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$lgp=loginuserdetailswithauthproject($authconn,$companyid,$subcompanyid,$projectid,"","allwithprojectid",$projectid);
	echo json_encode($lgp);
}else if($action=="sendemail_unknownentiry_forreconstruction"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$loginname=$_POST['loginname'];
	// $sendtoemailaddress=$_POST['sendtoemailaddress'];
	$sendtoemailloginid=$_POST['sendtoemailloginid'];
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

	$sendtoemailaddress = $lgemailid;
	// Encode the query string using base64
	$enc_qstring = base64_encode($qstring);

	// Concatenate the original query string with the encoded one
	$key = $qstring . "|" . $enc_qstring;

	// Encode the key using base64
	$enc_key = base64_encode($key);
	if(sendemail_forunknownentry($conn,$companyid,$subcompanyid,$projectid,$sendtoemailaddress,$routurl,$enc_key)){
		$rrct=array('status'=>'Success', 'message'=>'Email sent successfully');
	}else{
		$rrct=array('status'=>'Error', 'message'=>'Email did not send, Please try again');
	}	
	echo json_encode($rrct);	
}else if($action=="SendEmailToReminder_ForUrgentApproval"){
	$routurl = $_POST['routurl'];
	$emailto = $_POST['emailto'];
	$emailcc = $_POST['emailcc'];
	$emailsubject = $_POST['emailsubject'];
	$emailmessage = $_POST['emailmessage'];
	$requestdbno = $_POST['requestdbno'];
	// $requestno = $_POST['requestno'];
	$emailSendername = $_POST['loginname'];
	$rrqst=getrequestdetails($conn,"","","","sno",$requestdbno);
	$companyid = "";
	$subcompanyid = "";
	$projectid = "";
	$requestno ="";
	$sentotemails="";
	$rrstmsg=[];
	if(count($rrqst)>0){
		foreach ($rrqst as $rrqstkey) {
			$requestno = $rrqstkey['request_no'];
			$companyid = $rrqstkey['companyid'];
			$subcompanyid = $rrqstkey['subcompanyid'];
			$projectid = $rrqstkey['projectid'];
			$ssqu=$conn->query("SELECT GROUP_CONCAT(DISTINCT u.emailid) apprlemail,u.loginid,u.loginroleid,u.loginrolename  FROM login_user_master u join login_authorized_modules m on u.companyid=m.companyid and  u.subcompanyid=m.subcompanyid and u.projectid=m.projectid and u.loginid=m.loginid WHERE lcase(u.isActive)='yes' and (m.status is null or status='' or m.status='active') and u.companyid='".$companyid."' and u.subcompanyid='".$subcompanyid."' and u.projectid='".$projectid."' and u.emailid='".$emailto."' and m.moduleid='2'");
			if($ssqu->num_rows>0){
				$rrw= $ssqu->fetch_assoc();				
				$qstring = $rrw['loginid'] . "|" . $rrw['loginroleid'] . "|" . $companyid . "|" . $subcompanyid . "|" . $projectid . "|" . $rrw['loginrolename'];

				$sendtoemail = $rrw['apprlemail'];
				// Encode the query string using base64
				$enc_qstring = base64_encode($qstring);
				$key = $qstring . "|" . $enc_qstring;
				$enc_key = base64_encode($key);
				if(SendEmailToReminderforApprovalRequest($conn,$requestno,$sendtoemail,$emailcc,$emailsubject,$emailmessage,$emailSendername,$routurl,$enc_key)){
					$rrstmsg=array('status'=>'Success', 'message'=>'Email sent successfully');
				}else{
					$rrstmsg=array('status'=>'Error', 'message'=>'Email did not send, Please try again');
				}
			}else{
				$rrstmsg = array('status'=>'Error', 'message'=>'No on authorized for payment request module, contact to admin');
			}
		}
	}else{
		$rrstmsg = array('status'=>'Error', 'message'=>'Request details did not available, contact to admin');
	}
	echo json_encode($rrstmsg);
}else if($action=="SendEmailAfterNewRequest_ForApproval"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$requestno = $_POST['requestno'];
	$loginname = $_POST['loginname'];
	$routurl = $_POST['routurl'];
	echo sendemail_AfterCreateRequestForApproval($authconn, $conn, $companyid, $subcompanyid, $projectid, $requestno, $routurl);
}else if($action=="sendemail_AfterApprovalRequestForPaymentUpdate"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$requestno = $_POST['requestno'];
	$loginname = $_POST['loginname'];
	$routurl = $_POST['routurl'];
	echo sendemail_AfterApprovalRequestForPaymentUpdate($authconn, $conn, $companyid, $subcompanyid, $projectid, $requestno, $routurl);
}
?>