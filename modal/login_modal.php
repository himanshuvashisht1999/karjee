<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="entertologin"){
	// Start the session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
	$baseurl = $_POST['baseurl'];
	$loginid = $_POST['loginidval'];
	$loginpassword = $_POST['loginpasswordval'];
	//check userexist or not
	$cheklogin=loginuserdetailsfrommaster($authconn,$loginid,"loginid",$loginid);
	$loginreturn=array();
	if(count($cheklogin)>0){
		if(count($cheklogin)>1){
			$loginreturn[]=array('status'=>'Error', 'message'=>'Your login id registered with more than 1 company, Please contact to web admin');
		}else{
			$cheklogin=isLogin($authconn,$loginid,$loginpassword);
			if(count($cheklogin)>0){
				foreach ($cheklogin as $lgkey) {
					$_SESSION['loginid']=$lgkey['loginid'];
					$_SESSION['loginname'] = $lgkey['loginname'];
			        $_SESSION['sessionid'] = session_id(); // Unique session ID

					$loginreturn[]=array('status'=>'Success', 'message'=>'Login Successfully', 'loginname'=>$lgkey['loginname'], 'loginid'=>$lgkey['loginid'], 'loginroleid'=>$lgkey['loginroleid'], 'loginrolename'=>$lgkey['loginrolename'], 'companyid'=>$lgkey['companyid'], 'subcompanyid'=>$lgkey['subcompanyid'], 'projectid'=>$lgkey['projectid'], 'sessionid'=>$_SESSION['sessionid'],'comments'=>$lgkey['comments']);
				}
			}else{
				$loginreturn[]=array('status'=>'Error', 'message'=>'Your loginid / password incorrect, Please try again with valid details');
			}
		}
	}else{
		$loginreturn[]=array('status'=>'Error', 'message'=>'You are not registered with us, So Please do not try to login or please contact to web admin');
	}
	echo json_encode($loginreturn);
}else if($action=="checkmodulelogingwithpassword"){
	// Start the session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$moduleid = $_POST['moduleid'];
	$loginid = $_POST['loginid'];
	$modulepassword = $_POST['modulepassword'];
	
	
	$gpq = $authconn->query("SELECT sno,companyid,subcompanyid,projectid,loginid,moduleid,ispassword,ifnull(password,'') as password FROM login_authorized_modules  WHERE status = 'active' and password<>'' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and moduleid='".$moduleid."' and loginid='".$loginid."' and password='".$modulepassword."'");
	$mdsession=array();
	if($gpq->num_rows>0){
		 $_SESSION['modulesessionid'] = session_id();
		 $mdsession=array('status'=>'Success','message'=>'Module login password has been matched', 'mdlesessionid'=>$_SESSION['modulesessionid']);
	}else{
		$mdsession=array('status'=>'Error','message'=>'Module login password did not matche', 'mdlesessionid'=>'');
	}
	echo json_encode($mdsession);
}
?>