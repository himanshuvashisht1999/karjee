<?php
ini_set('display_errors', 1);
set_time_limit(500);
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
// Created By Chatur Singh
function checkLoginStatus()
{
	// Start the session if not already started
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// Check if the user is logged in
	if (isset($_SESSION['sessionid'])) {
		return "loginid";
	} else {
		return "logout";
	}
}

// function checkModuleLoginStatus() {
//     // Start the session if not already started
//     if (session_status() === PHP_SESSION_NONE) {
//         session_start();
//     }

//     // Check if the user is logged in
//     if (isset($_SESSION['modulesessionid'])) {
//         return "loginid";
//     } else {
//         return "logout";
//     }
// }

function base64url_encode_lg($data)
{
	$b64 = base64_encode($data);

	if ($b64 === false) {
		return false;
	}
	$url = strtr($b64, '+/', '-_');
	return rtrim($url, '=');
}

function base64url_decode_lg($data, $strict = false)
{
	$b64 = strtr($data, '-_', '+/');
	return base64_decode($b64, $strict);
}


function isLogin($conn, $loginid, $loginpassword)
{
	$clguq = $conn->query("SELECT loginname, loginid, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and (isActive='yes' or isActive='active') and loginid='" . $loginid . "' and loginpassword='" . trim(addslashes($loginpassword)) . "'");
	$lginfo = array();
	if ($clguq->num_rows > 0) {
		while ($lgrow = $clguq->fetch_assoc()) {
			$lginfo[] = array('companyid' => $lgrow['companyid'], 'subcompanyid' => $lgrow['subcompanyid'], 'projectid' => $lgrow['projectid'], 'loginname' => $lgrow['loginname'], 'loginid' => $lgrow['loginid'], 'loginroleid' => $lgrow['loginroleid'], 'loginrolename' => $lgrow['loginrolename'], 'mobileno' => $lgrow['mobileno'], 'comments' => $lgrow['comments']);
		}
	}
	return $lginfo;
}

function loginuserdetailswithauthproject($conn, $companyid, $subcompanyid, $projectid, $loginroleid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join login_departmentmaster d on d.id=u.departmentid where (u.tedit is null or u.tedit='') and u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and p.projectid='" . $projectid . "' and u.loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "allwithsubcompany") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join login_departmentmaster d on d.id=u.departmentid where (u.tedit is null or u.tedit='') and u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and u.loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "allwithprojectid") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p on u.loginid=p.loginid left join login_departmentmaster d on d.id=u.departmentid where (u.tedit is null or u.tedit='') and u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and p.projectid='" . $projectid . "'");
	} else if ($shortby == "projectwithdepartment") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join login_departmentmaster d on d.id=u.departmentid  where (u.tedit is null or u.tedit='') and u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and u.loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "loginusername") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join login_departmentmaster d on d.id=u.departmentid  where (u.tedit is null or u.tedit='') and u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and lcase(u.loginname)='" . strtolower(trim($shortbyvalue)) . "'");
	} else if ($shortby == "adminloginlist") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join login_departmentmaster d on d.id=u.departmentid  where (u.tedit is null or u.tedit='') and u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and u.loginroleid<4 ");
	}
	$ullist = array();
	if ($lguq->num_rows > 0) {
		while ($lurow = $lguq->fetch_assoc()) {
			$ullist[] = array('departmentname' => $lurow['departmentname'], 'loginname' => $lurow['loginname'], 'loginid' => $lurow['loginid'], 'loginroleid' => $lurow['loginroleid'], 'loginrolename' => $lurow['loginrolename'], 'mobileno' => ($lurow['mobileno'] ? $lurow['mobileno'] : "N/A"), 'emailid' => ($lurow['emailid'] ? $lurow['emailid'] : ""), 'isactive' => ($lurow['isActive'] ? $lurow['isActive'] : "Active"), 'islogined' => ($lurow['isLogined'] ? ucwords($lurow['isLogined']) : "Logout"), 'logindatetime' => $lurow['loginDatetime'], 'companyid' => $lurow['companyid'], 'subcompanyid' => $lurow['subcompanyid'], 'projectid' => $lurow['projectid']);
		}
	}
	return $ullist;
}


function loginuserinfo($conn, $loginroleid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and isActive='yes'  and loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "loginid") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and loginid='" . $shortbyvalue . "'");
	} else if ($shortby == "mobileno") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and mobileno='" . $shortbyvalue . "'");
	} else if ($shortby == "sno") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and sno='" . $shortbyvalue . "'");
	}
	$ullist = array();
	if ($lguq->num_rows > 0) {
		while ($lurow = $lguq->fetch_assoc()) {
			$ullist[] = array('loginname' => $lurow['loginname'], 'loginid' => $lurow['loginid'], 'loginpassword' => $lurow['loginpassword'], 'loginroleid' => $lurow['loginroleid'], 'loginrolename' => $lurow['loginrolename'], 'mobileno' => ($lurow['mobileno'] ? $lurow['mobileno'] : "N/A"), 'emailid' => ($lurow['emailid'] ? $lurow['emailid'] : "N/A"), 'isactive' => ($lurow['isActive'] ? $lurow['isActive'] : "Active"), 'islogined' => ($lurow['isLogined'] ? ucwords($lurow['isLogined']) : "Logout"), 'logindatetime' => $lurow['loginDatetime'], 'comments' => $lurow['comments']);
		}
	}
	return $ullist;
}

function loginuserdetailsfrommaster($conn, $loginroleid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime, comments, departmentid FROM login_user_master WHERE (tedit is null or tedit='') and isActive='yes'  and loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "loginid") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments, departmentid FROM login_user_master WHERE (tedit is null or tedit='') and loginid='" . $shortbyvalue . "'");
	} else if ($shortby == "mobileno") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments, departmentid FROM login_user_master WHERE (tedit is null or tedit='') and mobileno='" . $shortbyvalue . "'");
	} else if ($shortby == "sno") {
		$lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime, comments, departmentid FROM login_user_master WHERE (tedit is null or tedit='') and sno='" . $shortbyvalue . "'");
	}
	$ullist = array();
	if ($lguq->num_rows > 0) {
		while ($lurow = $lguq->fetch_assoc()) {
			$ullist[] = array('loginname' => $lurow['loginname'], 'loginid' => $lurow['loginid'], 'loginpassword' => $lurow['loginpassword'], 'loginroleid' => $lurow['loginroleid'], 'loginrolename' => $lurow['loginrolename'], 'mobileno' => ($lurow['mobileno'] ? $lurow['mobileno'] : "N/A"), 'emailid' => ($lurow['emailid'] ? $lurow['emailid'] : "N/A"), 'isactive' => ($lurow['isActive'] ? $lurow['isActive'] : "Active"), 'islogined' => ($lurow['isLogined'] ? ucwords($lurow['isLogined']) : "Logout"), 'logindatetime' => $lurow['loginDatetime'], 'comments' => $lurow['comments'], 'departmentid' => $lurow['departmentid']);
		}
	}
	return $ullist;
}

function OnlyProject_loginmodulesdetails_frommaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allactive") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE lcase(g.status)='active' and m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' order by g.vieworderby, m.vieworderby, m.modulename";
	} else if ($shortby == "moduleid") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE  lcase(g.status)='active' and m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.moduleid in ('" . str_replace(",", "','", $shortbyvalue) . "') order by g.vieworderby, m.vieworderby, m.modulename";
	} else if ($shortby == "groupidwithmoduleid") {
		$shortbyvalexp = explode("|", $shortbyvalue);
		$groupid = $shortbyvalexp[0];
		$moduleids = $shortbyvalexp[1];
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE lcase(g.status)='active' and m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.groupid='" . $groupid . "' and m.moduleid in ('" . str_replace(",", "','", $moduleids) . "') order by g.vieworderby, m.vieworderby, m.modulename";
	} else if ($shortby == "groupid") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE lcase(g.status)='active' and m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.groupid in ('" . str_replace(",", "','", $shortbyvalue) . "') order by g.vieworderby, m.vieworderby, m.modulename";
	} else if ($shortby == "allactivegroup") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE lcase(g.status)='active' and m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' group by m.groupid order by g.vieworderby";
	} else if ($shortby == "allactivemodules") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE lcase(g.status)='active' and m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' order by g.vieworderby, m.vieworderby";
	} else if ($shortby == "sno") {
		$lmd = "SELECT sno, vieworderby, groupid, moduleid, modulename, modulefoldername, subaction, status FROM login_modules_master_client WHERE sno='" . $shortbyvalue . "'";
	} else if ($shortby == "allactive_mainaccount") {
		$lmd = "SELECT m.groupid, g.groupname  as groupname, m.sno, ifnull(m.vieworderby,'') as vieworderby,  m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g  on g.groupid =m.groupid   WHERE lcase(g.status)='active' and ifnull(m.status,'')='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and ifnull(m.groupid,'') <>'' and ifnull(m.modulename,'') <>'' and ifnull(m.modulefoldername,'') <>'' and ifnull(m.groupid,'') <>'' order by g.vieworderby, m.vieworderby, g.groupname, m.modulename";
	} else if ($shortby == "allwithauth" || $shortby == "moduleid") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE  lcase(g.status)='active' and m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.moduleid in ('" . str_replace(",", "','", $shortbyvalue) . "') order by g.vieworderby, m.vieworderby, m.modulename";
	}
	// print_r($conn);

	if (empty($lmd)) {
		return array();
	}

	$lmdr = $conn->query($lmd);
	$mdlist = array();
	if ($lmdr->num_rows > 0) {
		while ($mrow = $lmdr->fetch_assoc()) {
			$mdlist[] = array('sno' => $mrow['sno'], 'vieworderby' => $mrow['vieworderby'], 'mainmodule' => $mrow['groupid'], 'groupid' => $mrow['groupid'], 'groupname' => $mrow['groupname'], 'moduleid' => $mrow['moduleid'], 'modulename' => $mrow['modulename'], 'modulefoldername' => $mrow['modulefoldername'], 'subaction' => $mrow['subaction'], 'status' => $mrow['status'], 'frmid' => $mrow['frmid'], 'ispasswordprotected' => $mrow['ispasswordprotected']);
		}
	}
	return $mdlist;
}

function loginauthorizedmodules($conn, $companyid, $subcompanyid, $projectid, $loginid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithloginid") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, password, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' order by moduleid");
	} else if ($shortby == "allwithmoduleid") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, password, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' and moduleid='" . $shortbyvalue . "' order by moduleid");
	} else if ($shortby == "onlywithmoduleid") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, password, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and  moduleid='" . $shortbyvalue . "' order by moduleid");
	} else if ($shortby == "sno") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, password, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "' order by moduleid");
	}
	$authorizemdlist = array();
	if ($auldq->num_rows > 0) {
		while ($aldrow = $auldq->fetch_assoc()) {
			$authorizemdlist[] = array('sno' => $aldrow['sno'], 'companyid' => $aldrow['companyid'], 'subcompanyid' => $aldrow['subcompanyid'], 'projectid' => $aldrow['projectid'], 'loginid' => $aldrow['loginid'], 'moduleid' => $aldrow['moduleid'], 'password' => $aldrow['password'], 'authrizedinfo' => $aldrow['authrizedinfo']);
		}
	}
	return $authorizemdlist;
}

function logindepartmentmaster($conn)
{
	$lguq = $conn->query("SELECT id,departmentname FROM  login_departmentmaster order by departmentname");
	$ullist = array();
	if ($lguq->num_rows > 0) {
		while ($lurow = $lguq->fetch_assoc()) {
			$ullist[] = $lurow;
		}
	}
	return $ullist;
}

function getauthorizedprojects($conn, $companyid, $subcompanyid, $projectid, $loginid, $shortby, $shortbyvalue)
{
	if ($shortby == "authorizedcompanylist") {
		$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and loginid='" . $loginid . "' and status='active' group by companyid order by projectid");
	} else if ($shortby == "authorizedsubcompanylist") {
		$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and loginid='" . $loginid . "' and status='active' group by subcompanyid order by projectid");
	} else if ($shortby == "authorizedprojectlist") {
		$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and loginid='" . $loginid . "' and status='active' group by projectid order by projectid");
	} else if ($shortby == "authorizedprojectid") {
		$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' and status='active' order by projectid");
	}
	$authorizedprjlist = array();
	if ($prssq->num_rows > 0) {
		while ($mdrow = $prssq->fetch_assoc()) {
			$authorizedprjlist[] = array('companyid' => $mdrow['companyid'], 'subcompanyid' => $mdrow['subcompanyid'], 'projectid' => $mdrow['projectid']);
		}
	}
	return $authorizedprjlist;
}

function getauthorizedprojectswithloginid($conn, $loginid)
{
	$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where loginid='" . $loginid . "' and status='active' group by companyid order by projectid");
	
	$authorizedprjlist = array();
	if ($prssq->num_rows > 0) {
		while ($mdrow = $prssq->fetch_assoc()) {
			$authorizedprjlist[] = array('companyid' => $mdrow['companyid'], 'subcompanyid' => $mdrow['subcompanyid'], 'projectid' => $mdrow['projectid']);
		}
	}
	return $authorizedprjlist;
}

function getprojectdetailsWithL($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	$ssbq = "";
	if ($shortby == "all") {
		$ssbq = "select companyid, subcompanyid, projectid,projectname, ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid, ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and (tedit is null or tedit='')";
	} else if ($shortby == "subcompanyid") {
		$ssbq = "select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and (tedit is null or tedit='')";
	} else if ($shortby == "projectid") {
		$ssbq = "select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid in ('" . str_replace(',', '","', $shortbyvalue) . "') and (tedit is null or tedit='')";
	}
	$sblist = array();
	if ($ssbq) {
		$ssbq_result = $conn->query($ssbq) or die($conn->error);
		if ($ssbq_result->num_rows > 0) {
			while ($sbrow = $ssbq_result->fetch_assoc()) {
				$sblist[] = array('companyid' => $sbrow['companyid'], 'subcompanyid' => $sbrow['subcompanyid'], 'projectid' => $sbrow['projectid'], 'projectname' => $sbrow['projectname'], 'finyr' => $sbrow['finyr'], 'taxtype' => $sbrow['taxtype'], 'invformat' => $sbrow['invformat'], 'proformaformat' => $sbrow['proformaformat'], 'currencyid' => $sbrow['currencyid'], 'creditformat' => $sbrow['creditformat'], 'emailid' => $sbrow['project_email']);
			}
		}
	}
	return $sblist;
}

function getprojectdetailwithcmpsubcmpWithL($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname,cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and cm.companyfoldername<>'' order by p.companyid, p.subcompanyid, p.projectid");
	} else if ($shortby == "withauthrizedcompanyid") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname,cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('" . str_replace(",", "','", $companyid) . "') order by p.companyid, p.subcompanyid, p.projectid");
	} else if ($shortby == "withauthrizedsubcompanyid") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname,cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('" . str_replace(",", "','", $companyid) . "')  and p.subcompanyid in('" . str_replace(",", "','", $subcompanyid) . "') order by p.companyid, p.subcompanyid, p.projectid");
	} else if ($shortby == "withauthrizedprojects") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname,cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('" . str_replace(",", "','", $companyid) . "')  and p.subcompanyid in('" . str_replace(",", "','", $subcompanyid) . "')  and p.projectid in('" . str_replace(",", "','", $shortbyvalue) . "') and cm.companyfoldername<>'' order by p.companyid, p.subcompanyid, p.projectid");
	}
	$pplist = array();
	if ($ppq->num_rows > 0) {
		while ($prow = $ppq->fetch_assoc()) {
			$pplist[] = array('companyid' => $prow['companyid'], 'subcompanyid' => $prow['subcompanyid'], 'projectid' => $prow['projectid'], 'projectname' => $prow['projectname'], 'companyname' => $prow['companyname'], 'subcompanyname' => $prow['subcompanyname'], 'companyfoldername' => $prow['companyfoldername']);
		}
	}
	return $pplist;
}

function allauthorizedcompanyid($conn, $loginid)
{
	$prssq = $conn->query("select distinct GROUP_CONCAT(companyid) as companyid FROM login_authorized_projects where loginid='" . $loginid . "' and status='active'");
	if ($prssq->num_rows > 0) {
		$crow = $prssq->fetch_assoc();
		return $crow['companyid'];
	}
}

function allauthorizedsubcompanyid($conn, $loginid)
{
	$prbssq = $conn->query("select distinct GROUP_CONCAT(subcompanyid) as subcompanyid FROM login_authorized_projects where loginid='" . $loginid . "' and status='active'");
	if ($prbssq->num_rows > 0) {
		$scrow = $prbssq->fetch_assoc();
		return $scrow['subcompanyid'];
	}
}

function allauthorizedprojectid($conn, $loginid)
{
	$prjq = $conn->query("select distinct GROUP_CONCAT(projectid) as projectid FROM login_authorized_projects where loginid='" . $loginid . "' and status='active'");
	if ($prjq->num_rows > 0) {
		$prrow = $prjq->fetch_assoc();
		return $prrow['projectid'];
	}
}

function allauthorizedmoduleid($conn, $companyid, $subcompanyid, $projectid, $loginid)
{
	$auldq = $conn->query("SELECT distinct GROUP_CONCAT(moduleid) as moduleid FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' order by moduleid");
	if ($auldq->num_rows > 0) {
		$mdrow = $auldq->fetch_assoc();
		return $mdrow['moduleid'];
	}
}

function login_rolefrommaster($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "allactive") {
		$lmd = "SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE status='active' order by roleid";
	} else if ($shortby == "roleid") {
		$lmd = "SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE roleid='" . $shortbyvalue . "' order by roleid";
	} else if ($shortby == "afterloginroleid") {
		$lmd = "SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE  status='active' and roleid>'" . $shortbyvalue . "' order by roleid";
	}
	$lmdr = $conn->query($lmd);
	$mdlist = array();
	if ($lmdr->num_rows > 0) {
		while ($mrow = $lmdr->fetch_assoc()) {
			$mdlist[] = array('roleid' => $mrow['roleid'], 'rolename' => $mrow['rolename'], 'rolelevel' => $mrow['rolelevel'], 'rolename_display' => $mrow['rolename_display']);
		}
	}
	return $mdlist;
}

function getcompanydetailswithlogin($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$comq = "select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='') and companyfoldername<>''";
	} else if ($shortby == "companyid") {
		$comq = "select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='') and companyid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "sno") {
		$comq = "select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='') and sno='" . $shortbyvalue . "'";
	}
	$comq_result = $conn->query($comq);
	$companylist = array();
	if ($comq_result->num_rows > 0) {
		while ($cmprow = $comq_result->fetch_assoc()) {
			$companylist[] = array('sno' => $cmprow['sno'], 'countryid' => $cmprow['countryid'], 'companyid' => $cmprow['companyid'], 'subcompanyid' => $cmprow['subcompanyid'], 'companyname' => $cmprow['companyname'], 'address' => $cmprow['address'], 'stateid' => $cmprow['stateid'], 'districtid' => $cmprow['districtid'], 'pincode' => $cmprow['pincode'], 'contact_number' => $cmprow['contact_number'], 'email' => $cmprow['email_id'], 'registration_no' => $cmprow['registration_no'], 'logo' => $cmprow['logo'], 'footer' => $cmprow['footer']);
		}
	}
	return $companylist;
}


?>