<?php
//modified by ajay 27-05-2025
ini_set('display_errors', 1);
set_time_limit(500);
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);
// Created By Chatur Singh
/*
function checkLoginStatus_old() {
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
*/
/*
function checkModuleLoginStatus_old() {
	// Start the session if not already started
	if (session_status() === PHP_SESSION_NONE) {
		session_start();
	}

	// Check if the user is logged in
	if (isset($_SESSION['modulesessionid'])) {
		return "loginid";
	} else {
		return "logout";
	}
}
*/

function sendemailfunction($mconn, $emailto, $subject, $message)
{
	$to = array($emailto);
	$result = Sendemailnew($to, array(), array(), $subject, $message);

	$msg = "";
	if (!$result) {
		$msg = "Error";

	} else {
		$msg = "Success";
	}

	return $msg;
}

function sendemailfunctionwithattachment($mconn, $emailto, $subject, $message, $attachment)
{
	$to = array($emailto);
	$result = Sendemailnew($to, array(), array(), $subject, $message, $attachment);

	$msg = "";
	if (!$result) {
		$msg = "Error";

	} else {
		$msg = "Success";
	}

	return $msg;
}

// Created By Chatur Singh
function grouptitleofmodule($conn, $shortby, $shortbyvalue)
{
	// Base query
	$baseQuery = "SELECT sno, groupid, groupname, vieworderby FROM login_modulesgroup_master WHERE IFNULL(tedit, '') = ''";
	// Add conditions based on $shortby
	$condition = "";
	$params = [];
	$paramTypes = "";
	if ($shortby === "groupid") {
		$condition = " AND groupid = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "s";
	} else if ($shortby === "sno") {
		$condition = " AND sno = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "i";
	} else if ($shortby === "all") {

	}

	// Final query
	$gtq = $baseQuery . $condition;
	$stmt = $conn->prepare($gtq);
	// Bind parameters if necessary
	if (!empty($params)) {
		$stmt->bind_param($paramTypes, ...$params);
	}
	// Execute query
	$stmt->execute();
	$result = $stmt->get_result();
	// Process results
	$gtllist = [];
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$gtllist[] = [
				'sno' => $row['sno'],
				'vieworderby' => $row['vieworderby'],
				'groupid' => $row['groupid'],
				'groupname' => $row['groupname']
			];
		}
	}
	// Close statement and return result
	$stmt->close();
	return $gtllist;
}
// Created By Chatur Singh
function modules_subgroupmaster($conn, $shortby, $shortbyvalue)
{
	$baseQ = "SELECT sno, subgroupid, subgroupname, activestatus FROM login_modules_subgroupmaster  WHERE (tedit is null or tedit='')";
	// Add conditions based on $shortby
	$condition = "";
	$params = [];
	$paramTypes = "";
	if ($shortby === "subgroupid") {
		$condition = " AND subgroupid = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "s";
	} elseif ($shortby === "sno") {
		$condition = " AND sno = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "i";
	}

	// Final query
	$gtq = $baseQ . $condition;
	$stmt = $conn->prepare($gtq);
	// Bind parameters if necessary
	if (!empty($params)) {
		$stmt->bind_param($paramTypes, ...$params);
	}
	// Execute query
	$stmt->execute();
	$result = $stmt->get_result();
	// Process results
	$sgtllist = [];
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$sgtllist[] = [
				'sno' => $row['sno'],
				'subgroupid' => $row['vieworderby'],
				'subgroupname' => $row['groupid'],
			];
		}
	}
	// Close statement and return result
	$stmt->close();
	return $sgtllist;

}
// Created By Chatur Singh
function modules_subsubgroupmaster($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ssgq = "SELECT sno, subsubgroupid, subsubgroupname, activestatus FROM login_modules_subsubgroupmaster  WHERE (tedit is null or tedit='')";
	} else if ($shortby == "subgroupid") {
		$ssgq = "SELECT sno, subsubgroupid, subsubgroupname, activestatus FROM login_modules_subsubgroupmaster  WHERE (tedit is null or tedit='')";
	}
	$ssgp = $ssgq->query($conn);
	$subsubgrouplist = "";
	if ($ssgp->num_rows > 0) {
		while ($ssgrow = $ssgp->fetch_assoc()) {
			$subsubgrouplist[] = array('sno' => $ssgrow['sno'], 'subsubgroupid' => $ssgrow['subsubgroupid'], 'subsubgroupname' => $ssgrow['subsubgroupname']);
		}
	}
}

// created by Chatur Singh
function loginmodulesmasterlist($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$lmd = "SELECT sno, vieworderby, groupid, subgroupid, moduleid, modulename, modulefoldername, subaction, status, frmid, ispasswordprotected FROM login_modules_master WHERE status='active'";
	} else if ($shortby == "sno") {
		$lmd = "SELECT sno, vieworderby, groupid, subgroupid, moduleid, modulename, modulefoldername, subaction, status, frmid, ispasswordprotected FROM login_modules_master WHERE status='active' and sno='" . $shortbyvalue . "'";
	} else if ($shortby == "moduleid") {
		$lmd = "SELECT sno, vieworderby, groupid, subgroupid, moduleid, modulename, modulefoldername, subaction, status, frmid, ispasswordprotected FROM login_modules_master WHERE status='active' and moduleid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	}
	$lmdr = $conn->query($lmd);
	$mdlist = array();
	if ($lmdr->num_rows > 0) {
		while ($mrow = $lmdr->fetch_assoc()) {
			$mdlist[] = array('sno' => $mrow['sno'], 'vieworderby' => $mrow['vieworderby'], 'groupid' => $mrow['groupid'], 'subgroupid' => $mrow['subgroupid'], 'moduleid' => $mrow['moduleid'], 'modulename' => $mrow['modulename'], 'modulefoldername' => $mrow['modulefoldername'], 'subaction' => $mrow['subaction'], 'status' => $mrow['status'], 'frmid' => $mrow['frmid'], 'ispasswordprotected' => $mrow['ispasswordprotected']);
		}
	}
	return $mdlist;
}

function Onlyproject_grouptitleofmodule($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	// Base query with placeholders
	$baseQuery = "SELECT p.sno, p.groupid, p.groupname, p.vieworderby 
                  FROM login_group_master_client p 
                  JOIN login_modules_master_client c ON p.groupid = c.groupid 
                  WHERE IFNULL(p.tedit, '') = '' 
                  AND c.companyid = ? 
                  AND c.subcompanyid = ? 
                  AND c.projectid = ?";

	// Conditions based on $shortby
	$condition = "";
	$params = [$companyid, $subcompanyid, $projectid]; // Initial parameters
	$paramTypes = "iii"; // Integers for IDs

	if ($shortby === "groupid") {
		$condition = " AND p.groupid = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "i";
	} elseif ($shortby === "sno") {
		$condition = " AND p.sno = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "i";
	}
	// "all" condition doesn't add any filter, so it remains as is

	// Append GROUP BY clause
	$finalQuery = $baseQuery . $condition . " GROUP BY p.groupid";

	// Prepare the statement
	$stmt = $conn->prepare($finalQuery);

	// Bind parameters dynamically if required
	if (!empty($params)) {
		$stmt->bind_param($paramTypes, ...$params);
	}

	// Execute query
	$stmt->execute();
	$result = $stmt->get_result();

	// Fetch results
	$gtllist = [];
	while ($row = $result->fetch_assoc()) {
		$gtllist[] = [
			'sno' => $row['sno'],
			'vieworderby' => $row['vieworderby'],
			'groupid' => $row['groupid'],
			'groupname' => $row['groupname']
		];
	}

	// Close statement and return result
	$stmt->close();
	return $gtllist;
}

function company_loginmodulesmasterlist($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$lmd = "SELECT sno, companyid, subcompanyid, projectid, vieworderby, groupid, subgroupid, moduleid, modulename, modulefoldername, subaction, status, frmid, ispasswordprotected FROM login_modules_master_forcompany WHERE status='active' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "moduleid") {
		$lmd = "SELECT sno, companyid, subcompanyid, projectid, vieworderby, groupid, subgroupid, moduleid, modulename, modulefoldername, subaction, status, frmid, ispasswordprotected FROM login_modules_master_forcompany WHERE status='active' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and moduleid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$lmd = "SELECT sno, companyid, subcompanyid, projectid, vieworderby, groupid, subgroupid, moduleid, modulename, modulefoldername, subaction, status, frmid, ispasswordprotected FROM login_modules_master_forcompany WHERE status='active' and sno='" . $shortbyvalue . "'";
	}
	$lmdr = $conn->query($lmd);
	$mdlist = array();
	if ($lmdr->num_rows > 0) {
		while ($mrow = $lmdr->fetch_assoc()) {
			$mdlist[] = array('sno' => $mrow['sno'], 'companyid' => $mrow['companyid'], 'subcompanyid' => $mrow['subcompanyid'], 'projectid' => $mrow['projectid'], 'vieworderby' => $mrow['vieworderby'], 'groupid' => $mrow['groupid'], 'subgroupid' => $mrow['subgroupid'], 'moduleid' => $mrow['moduleid'], 'modulename' => $mrow['modulename'], 'modulefoldername' => $mrow['modulefoldername'], 'subaction' => $mrow['subaction'], 'status' => $mrow['status'], 'frmid' => $mrow['frmid'], 'ispasswordprotected' => $mrow['ispasswordprotected']);
		}
	}
	return $mdlist;
}

// Created By Chatur Singh
function loginmodulesdetails_frommaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allactive") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' order by m.vieworderby asc, m.modulename asc";
	} else if ($shortby == "moduleid") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.moduleid in ('" . str_replace(",", "','", $shortbyvalue) . "') order by m.vieworderby, m.modulename";
	} else if ($shortby == "groupidwithmoduleid") {
		$shortbyvalexp = explode("|", $shortbyvalue);
		$groupid = $shortbyvalexp[0];
		$moduleids = $shortbyvalexp[1];
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.groupid='" . $groupid . "' and m.moduleid in ('" . str_replace(",", "','", $moduleids) . "') order by m.vieworderby, m.modulename";
	} else if ($shortby == "groupid") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.groupid in ('" . str_replace(",", "','", $shortbyvalue) . "') order by m.vieworderby, m.modulename";
	} else if ($shortby == "allactivegroup") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected, m.integrationtype FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' group by m.groupid";
	} else if ($shortby == "sno") {
		$lmd = "SELECT sno, vieworderby, groupid, moduleid, modulename, modulefoldername, subaction, status, integrationtype FROM login_modules_master_client WHERE sno='" . $shortbyvalue . "'";
	} else if ($shortby == "allactive_mainaccount") {
		$lmd = "SELECT g.groupname  as groupid, l.sno, ifnull(l.vieworderby,'') as vieworderby,  l.moduleid, l.modulename, l.modulefoldername, l.subaction, l.status, l.frmid, l.ispasswordprotected, l.integrationtype FROM login_modules_master_client l join login_group_master_client g  on g.groupid =l.groupid   WHERE ifnull(l.status,'')='active' and ifnull(l.groupid,'') <>'' and ifnull(l.modulename,'') <>'' and ifnull(l.modulefoldername,'') <>'' and ifnull(l.groupid,'') <>'' and l.companyid='" . $companyid . "' and l.subcompanyid='" . $subcompanyid . "' and l.projectid='" . $projectid . "' order by g.groupname, l.vieworderby, l.modulename";
	}

	$lmdr = $conn->query($lmd);
	$mdlist = array();
	if ($lmdr->num_rows > 0) {
		while ($mrow = $lmdr->fetch_assoc()) {
			$mdlist[] = array('sno' => $mrow['sno'], 'vieworderby' => $mrow['vieworderby'], 'mainmodule' => $mrow['groupid'], 'groupid' => $mrow['groupid'], 'groupname' => $mrow['groupname'], 'moduleid' => $mrow['moduleid'], 'modulename' => $mrow['modulename'], 'modulefoldername' => $mrow['modulefoldername'], 'subaction' => $mrow['subaction'], 'status' => $mrow['status'], 'frmid' => $mrow['frmid'], 'ispasswordprotected' => $mrow['ispasswordprotected'], 'integrationtype' => $mrow['integrationtype']);
		}
	}
	return $mdlist;
}

function OnlyProject_loginmodulesdetails_frommaster_old($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allactive") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' order by m.vieworderby, m.modulename";
	} else if ($shortby == "moduleid") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.moduleid in ('" . str_replace(",", "','", $shortbyvalue) . "') order by m.vieworderby, m.modulename";
	} else if ($shortby == "groupidwithmoduleid") {
		$shortbyvalexp = explode("|", $shortbyvalue);
		$groupid = $shortbyvalexp[0];
		$moduleids = $shortbyvalexp[1];
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.groupid='" . $groupid . "' and m.moduleid in ('" . str_replace(",", "','", $moduleids) . "') order by m.vieworderby, m.modulename";
	} else if ($shortby == "groupid") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.groupid in ('" . str_replace(",", "','", $shortbyvalue) . "') order by m.vieworderby, m.modulename";
	} else if ($shortby == "allactivegroup") {
		$lmd = "SELECT m.sno, m.vieworderby, m.groupid, m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, g.groupname, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g on m.groupid=g.groupid WHERE m.status='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' group by m.groupid";
	} else if ($shortby == "sno") {
		$lmd = "SELECT sno, vieworderby, groupid, moduleid, modulename, modulefoldername, subaction, status FROM login_modules_master_client WHERE sno='" . $shortbyvalue . "'";
	} else if ($shortby == "allactive_mainaccount") {
		$lmd = "SELECT g.groupname  as groupid, m.sno, ifnull(m.vieworderby,'') as vieworderby,  m.moduleid, m.modulename, m.modulefoldername, m.subaction, m.status, m.frmid, m.ispasswordprotected FROM login_modules_master_client m join login_group_master_client g  on g.groupid =m.groupid   WHERE ifnull(m.status,'')='active' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and ifnull(m.groupid,'') <>'' and ifnull(m.modulename,'') <>'' and ifnull(m.modulefoldername,'') <>'' and ifnull(m.groupid,'') <>'' order by g.groupname, m.modulename";
	}
	// echo "$shortby\n". $shortby;
	// echo " \n".$lmd;

	$lmdr = $conn->query($lmd);
	$mdlist = array();
	if ($lmdr->num_rows > 0) {
		while ($mrow = $lmdr->fetch_assoc()) {
			$mdlist[] = array('sno' => $mrow['sno'], 'vieworderby' => $mrow['vieworderby'], 'mainmodule' => $mrow['groupid'], 'groupid' => $mrow['groupid'], 'groupname' => $mrow['groupname'], 'moduleid' => $mrow['moduleid'], 'modulename' => $mrow['modulename'], 'modulefoldername' => $mrow['modulefoldername'], 'subaction' => $mrow['subaction'], 'status' => $mrow['status'], 'frmid' => $mrow['frmid'], 'ispasswordprotected' => $mrow['ispasswordprotected']);
		}
	}
	return $mdlist;
}

function checkModulehasPasswordwithLoginid($conn, $companyid, $subcompanyid, $projectid, $moduleid, $loginid)
{
	$gpq = $conn->query("SELECT sno,companyid,subcompanyid,projectid,loginid,moduleid,ispassword,ifnull(password,'') as password FROM login_authorized_modules  WHERE status = 'active' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and moduleid='" . $moduleid . "' and loginid='" . $loginid . "'");
	$mdlist = array();
	$authloginlist = array();
	if ($gpq->num_rows > 0) {
		while ($mrow = $gpq->fetch_assoc()) {
			$lgm = loginuserdetailsfrommasterold($conn, "", "loginid", $mrow['loginid']);
			$loginname = "";
			if (count($lgm) > 0) {
				foreach ($lgm as $lgmkey) {
					$loginname = $lgmkey['loginname'];
				}
			}
			if ($loginname) {
				$authloginlist[] = array('sno' => $mrow['sno'], 'loginname' => $loginname, "loginid" => $mrow['loginid'], 'authpass' => $mrow['password']);
			}
		}
	}
}


function autoreceivableinvoiceno($conn, $companyid, $subcompanyid, $projectid)
{
	$sql = $conn->query("select max(invoice_id) as maxid FROM tbl_invoices where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' order by invoice_id desc limit 1");
	if ($sql->num_rows > 0) {
		$row = $sql->fetch_assoc();
		$maxinvno = $row['maxid'] + 1;
	} else {
		$maxinvno = 1;
	}
	return $maxinvno;
}

function autoreceivableperformano($conn, $companyid, $subcompanyid, $projectid)
{
	$sql = $conn->query("select max(invoice_id) as maxid FROM tbl_invoices_performa where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' order by invoice_id desc limit 1");
	if ($sql->num_rows > 0) {
		$row = $sql->fetch_assoc();
		$maxinvno = $row['maxid'] + 1;
	} else {
		$maxinvno = 1;
	}
	return $maxinvno;
}

function updatereferenceinctb($conn, $companyid, $subcompanyid, $projectid)
{
	$acc = $conn->query("select accountid from tbl_master_account where cmp_id=" . $companyid . " and subcmp_id=" . $subcompanyid . " and projectid=" . $projectid . " and account_type='Bank'");
	if ($acc->num_rows > 0) {
		while ($accrow = $acc->fetch_assoc()) {
			$res = $conn->query("select sno,description_bank,chequeno from tbl_closetobook where  account_id='" . $accrow["accountid"] . "' and amounttype='withdrawn' and company_id=" . $companyid . " and subcompany_id=" . $subcompanyid . " and project_id=" . $projectid);
			if ($res->num_rows > 0) {
				while ($row = $res->fetch_assoc()) {
					$actual_refno = "";
					$likelypayto = "";
					$referenceno = "";
					$flag = "";
					if (strlen($row["chequeno"]) >= 40 && !stristr($row["description_bank"], "neft")) {
						$actual_refno = $row["chequeno"];
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["chequeno"]));
						$referenceno = $description_parts[0];
						$flag = "s";
					} elseif (strlen($row["chequeno"]) >= 40 && stristr($row["description_bank"], "neft")) {
						$actual_refno = $row["chequeno"];
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$referenceno = $description_parts[5];
						$flag = "s";
					} elseif (str_starts_with($row["description_bank"], "CMS")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = $description_parts[0] . "/" . $description_parts[1];
					} elseif (str_starts_with($row["description_bank"], "INET")) {
						$description_parts = explode("/", $row["description_bank"], 3);
						$referenceno = $description_parts[count($description_parts) - 1];
					} elseif (str_starts_with($row["description_bank"], "RTGS")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = $description_parts[1];
					} elseif (str_starts_with($row["description_bank"], "GIB")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = $description_parts[1];
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "WITHDRAWAL TRANSFER NEFT")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = $description_parts[2];
						$likelypayto = $description_parts[3];
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "WITHDRAWAL TRANSFER RTGS")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = $description_parts[2];
						$likelypayto = $description_parts[3];
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "TO TRANSFER/FOR CRN:")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = explode(" ", $description_parts[1])[1];
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "INB/NEFT")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = $description_parts[2];
						$likelypayto = $description_parts[3];
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "TO TRANSFER-INB NEFT UTR NO:")) {
						$description_parts = explode("--", $row["description_bank"]);
						$referenceno = explode(":", $description_parts[0])[1];
						$likelypayto = explode("/", explode(":", $description_parts[1])[1])[1];
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "TO TRANSFER-INB") && !stristr(strtoupper($row["description_bank"]), "NEFT")) {
						$description_parts = explode("--", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$referenceno = explode(" ", trim($description_parts[1]), 5)[0];
						$likelypayto = explode("/", trim(explode(" ", trim($description_parts[1]), 5)[4]))[0];
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "PAYPAL *")) {
						$description_parts = explode("*", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$likelypayto = trim(explode("-", trim($description_parts[1]))[0]);
					}
					if ($flag == "s") {
						$conn->query("update tbl_closetobook set chequeno='" . trim($referenceno) . "', description_bank= concat(description_bank,' ','" . $actual_refno . "' ) where sno=" . $row["sno"]);
					} else {
						if ($likelypayto) {
							$paytoname = "";
							$paytores = $conn->query("select paytoname from tbl_payto_master where ucase(paytoname) like '" . strtoupper(trim($likelypayto)) . "%'  and cmp_id=" . $companyid . " and subcmp_id=" . $subcompanyid . " and projectid=" . $projectid . " and ifnull(tedit,'')='' order by sno limit 1");
							if ($paytores->num_rows > 0) {
								$paytorow = $paytores->fetch_assoc();
								$paytoname = $paytorow["paytoname"];
							}
							if ($paytoname) {
								$conn->query("update tbl_closetobook set payto='" . trim($paytoname) . "',tsno=1 where sno=" . $row["sno"]);
							} else {
								$conn->query("update tbl_closetobook set payto='" . trim($likelypayto) . "' where sno=" . $row["sno"]);
							}
						}
						// else{
						// 	$conn->query("update tbl_closetobook set chequeno='".trim($referenceno)."' where sno=".$row["sno"]);
						// }
					}

				}
			}
		}
	}
}


// Created By Chatur Singh
function agencymasterlist($conn, $shortby, $shortbyvalue)
{
	// Base query
	$query = "SELECT a.sno as agsno, a.agencyid, a.agencyname, u.sno as authsno, u.responsibleperson, u.mobileno, u.whatsapno, u.emailaddress, u.addedby 
              FROM agency_master a join agency_authorization u on a.agencyid=u.agencyid
              WHERE (a.tedit IS NULL OR a.tedit = '') and (u.tedit IS NULL OR u.tedit = '')";

	// Additional condition based on $shortby
	if ($shortby === "agencyid") {
		$query .= " AND a.agencyid = ?";
	} elseif ($shortby === "sno") {
		$query .= " AND a.sno = ?";
	}

	// Prepare the statement
	$stmt = $conn->prepare($query);

	// Bind the parameter if needed
	if ($shortby === "agencyid" || $shortby === "sno") {
		$stmt->bind_param("s", $shortbyvalue);
	}

	// Execute the query
	$stmt->execute();

	// Fetch results
	$result = $stmt->get_result();

	// Collect data into an array
	$data = [];
	while ($row = $result->fetch_assoc()) {
		$data[] = $row;
	}

	// Close the statement
	$stmt->close();

	// Return the data
	return $data;
}


function getclientdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithprojectid") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,currency FROM client_master where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "'";
	} else if ($shortby == "clientname") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,currency FROM client_master where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and client_name='" . $shortbyvalue . "'";
	} else if ($shortby == "clientid") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,currency FROM client_master where  (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and client_id='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,currency FROM client_master where  (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and sno='" . $shortbyvalue . "'";
	}
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('sno' => $clrow['sno'], 'companyid' => $clrow['companyid'], 'subcompanyid' => $clrow['subcompanyid'], 'projectid' => $clrow['projectid'], 'client_id' => $clrow['client_id'], 'client_name' => $clrow['client_name'], 'invoicing_address' => $clrow['invoicing_address'], 'phones' => $clrow['phones'], 'client_email' => $clrow['client_email'], 'vat' => $clrow['vat'], 'date_time' => $clrow['date_time'], 'comp_website' => $clrow['comp_website'], 'name_forpo' => $clrow['name_forpo'], 'contact_forpo' => $clrow['contact_forpo'], 'email_forpo' => $clrow['email_forpo'], 'name_foraccpay' => $clrow['name_foraccpay'], 'email_foraccpay' => $clrow['email_foraccpay'], 'currency' => $clrow['currency']);
		}
	}
	return $clarray;
}


function updatedepositkeysinctb($conn, $companyid, $subcompanyid, $projectid)
{
	$acc = $conn->query("select accountid,accountname from tbl_master_account where cmp_id=" . $companyid . " and subcmp_id=" . $subcompanyid . " and projectid=" . $projectid . " and account_type='Bank'");
	if ($acc->num_rows > 0) {
		while ($accrow = $acc->fetch_assoc()) {
			$res = $conn->query("select sno,description_bank,ifnull(chequeno,'') as chequeno from tbl_closetobook where status='' and account_id='" . $accrow["accountid"] . "' and amounttype='deposit' and company_id=" . $companyid . " and subcompany_id=" . $subcompanyid . " and project_id=" . $projectid);
			if ($res->num_rows > 0) {
				while ($row = $res->fetch_assoc()) {
					$bank_shortkey = "";
					$referenceno = "";
					$from = "";
					$payment_name = "";
					$paymentmode = "";
					if (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER NEFT")) {
						$description_parts = explode("/", $row["description_bank"]);
						$bank_shortkey = $description_parts[1];
						$referenceno = $description_parts[2];
						$from = $description_parts[3];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER-NEFT")) {
						$description_parts = explode("*", $row["description_bank"]);
						$bank_shortkey = $description_parts[1];
						$referenceno = $description_parts[2];
						$from = $description_parts[3];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER/NEFT")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$referenceno = $description_parts[count($description_parts) - 1];
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]), 3);
						$from = str_replace($referenceno, "", $description_parts[2]);
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "NEFT CR")) {
						$description_parts = explode("-", $row["description_bank"]);
						$referenceno = $description_parts[count($description_parts) - 1];
						$from = $description_parts[2];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "NEFT-")) {
						$description_parts = explode("-", $row["description_bank"]);
						$referenceno = $description_parts[1];
						$from = $description_parts[2];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "NEFT/")) {
						$description_parts = explode("/", $row["description_bank"]);
						$from = $description_parts[1];
						$bank_shortkey = $description_parts[count($description_parts) - 1];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "NEFT_IN:")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = explode(":", $description_parts[0])[1];
						$from = $description_parts[count($description_parts) - 1];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER RTGS")) {
						$description_parts = explode("/", $row["description_bank"]);
						$bank_shortkey = $description_parts[1];
						$referenceno = $description_parts[2];
						$from = $description_parts[3];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER VAN :")) {
						$description_parts = explode("/", $row["description_bank"]);
						$bank_shortkey = $description_parts[1];
						$referenceno = $description_parts[2];
						$from = $description_parts[3];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER/RTGSE")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$referenceno = $description_parts[count($description_parts) - 1];
						$from = $description_parts[2] . " " . $description_parts[3] . " " . $description_parts[4];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER/RTGS")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$payment_name = str_replace("TRANSFER/RTGS", "", $description_parts[1]);
						$referenceno = $description_parts[count($description_parts) - 1];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "RTGS CR")) {
						$description_parts = explode("-", $row["description_bank"]);
						$bank_shortkey = $description_parts[1];
						$referenceno = $description_parts[count($description_parts) - 1];
						$from = $description_parts[2];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "RTGS FR")) {
						$description_parts = explode("/", $row["description_bank"]);
						$bank_shortkey = $description_parts[count($description_parts) - 1];
						$from = explode(":", $description_parts[0])[1];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER/IMPSP2A")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]), 3);
						$referenceno = str_replace("TRANSFER/IMPSP2A", "", $description_parts[1]);
						;
						$from = $description_parts[count($description_parts) - 1];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "IMPS-")) {
						$description_parts = explode("-", $row["description_bank"]);
						$bank_shortkey = $description_parts[3];
						$referenceno = $description_parts[1];
						$payment_name = $description_parts[2];
						$from = $description_parts[2];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY TRANSFER/UPI")) {
						$description_parts = explode("/", $row["description_bank"]);
						$referenceno = explode(" ", preg_replace('/\s+/', ' ', $description_parts[2]))[1];
						$payment_name = $description_parts[count($description_parts) - 1];
						$from = trim(str_replace("NA_", "", explode("|", $description_parts[count($description_parts) - 1])[1]));
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY CLEARING / CHEQUE-")) {
						$description_parts = explode("/", $row["description_bank"]);
						$desc_sub_parts = explode("-", $description_parts[1]);
						$bank_shortkey = explode(" ", preg_replace('/\s+/', ' ', $desc_sub_parts[1]))[0];
						$referenceno = explode(" ", preg_replace('/\s+/', ' ', $description_parts[2]))[0];
						$paymentmode = "cheque";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY CLEARING / CHEQUE/")) {
						$description_parts = explode("/", $row["description_bank"]);
						$bank_shortkey = explode(" ", preg_replace('/\s+/', ' ', $description_parts[2]))[1];
						$payment_name = explode(" ", preg_replace('/\s+/', ' ', $description_parts[count($description_parts) - 1]))[0];
						$from = explode(" ", preg_replace('/\s+/', ' ', $description_parts[count($description_parts) - 1]))[0];
						$paymentmode = "cheque";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "CLG/")) {
						$description_parts = explode("/", $row["description_bank"]);
						$from = $description_parts[1];
						$referenceno = $description_parts[2];
						$bank_shortkey = $description_parts[3];
						$paymentmode = "cheque";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "CHEQUE DEPO")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$referenceno = $description_parts[2];
						$bank_shortkey = $description_parts[count($description_parts) - 1];
						$paymentmode = "cheque";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "BY CHQ")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]), 4);
						$referenceno = $description_parts[2];
						$from_arr = explode(" ", $description_parts[3]);
						array_pop($from_arr);
						$from = implode(" ", $from_arr);
						$paymentmode = "cheque";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "GRS/")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]), 3);
						$referenceno = $description_parts[0];
						$from = $description_parts[count($description_parts) - 1];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "RTGS/")) {
						$description_parts = explode("/", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$referenceno = $description_parts[1];
						$from = $description_parts[2];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "RN") && str_ends_with(strtoupper($row["description_bank"]), "SP")) {
						$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]));
						$referenceno = $description_parts[0];
						$paymentmode = "online";
					} elseif (str_starts_with(strtoupper($row["description_bank"]), "CREDIT INTEREST") || (str_starts_with(strtoupper($row["description_bank"]), "SB:") && stristr(strtoupper($row["description_bank"]), "Int.Pd:"))) {
						$from = "Bank";
						$paymentmode = "online";
					}
					$verified = 0;
					if ($from) {
						if (str_starts_with(strtoupper($accrow["accountname"]), strtoupper($from))) {
							$from = strtoupper($accrow["accountname"]);
							$verified = 1;
						} elseif (isSimilarLevenshtein(strtoupper($accrow["accountname"]), strtoupper($from))) {
							$from = strtoupper($accrow["accountname"]);
							$verified = 1;
						} else {
							$newfrom = verifyfromclientmaster($conn, $companyid, $subcompanyid, $projectid, $from);
							if ($newfrom) {
								$from = $newfrom;
								$verified = 1;
							}
						}
					}
					if (trim($row["chequeno"]) == '' || trim($row["chequeno"]) == '-') {
						if ($verified) {
							$conn->query("update tbl_closetobook set chequeno='" . trim($referenceno) . "',payto='" . $from . "',paymentmode = '" . $paymentmode . "',tsno='" . $verified . "' where sno=" . $row["sno"]);
						} else {
							$conn->query("update tbl_closetobook set chequeno='" . trim($referenceno) . "',payto='" . $from . "',paymentmode = '" . $paymentmode . "',tsno=null where sno=" . $row["sno"]);
						}
					} else {
						if ($verified) {
							$conn->query("update tbl_closetobook set payto='" . $from . "',paymentmode = '" . $paymentmode . "' ,tsno='" . $verified . "'  where sno=" . $row["sno"]);
						} else {
							$conn->query("update tbl_closetobook set payto='" . $from . "',paymentmode = '" . $paymentmode . "' ,tsno=null  where sno=" . $row["sno"]);
						}
					}
				}
			}
		}
	}
}

function isSimilarLevenshtein($mainString, $otherString, $maxDistance = 3)
{
	$distance = levenshtein($mainString, $otherString);
	return $distance <= $maxDistance;
}
function isSimilarLevenshtein_new($mainString, $otherString, $maxDistance = 3)
{
	// Split strings into parts based on spaces or other delimiters
	$mainParts = explode(' ', $mainString);
	$otherParts = explode(' ', $otherString);

	// Iterate through each part and compare
	foreach ($mainParts as $mainPart) {
		foreach ($otherParts as $otherPart) {
			$distance = levenshtein($mainPart, $otherPart);
			if ($distance <= $maxDistance) {
				return true; // Found a match within the allowed distance
			}
		}
	}
	return false; // No matches found
}

function verifyfromclientmaster($conn, $companyid, $subcompanyid, $projectid, $payfrom)
{
	$sqry = "Select client_name from client_master where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and ucase(client_name) like '" . strtoupper($payfrom) . "%'";
	$ss_result = $conn->query($sqry);
	if ($ss_result->num_rows > 0) {
		$sbrow = $ss_result->fetch_assoc();
		return $sbrow["client_name"];
	} else {
		$payfromarr = explode(" ", $payfrom);
		$newpayfrom = "";
		for ($i = 0; $i < count($payfromarr); $i++) {
			if (strlen($payfromarr[$i]) > 2) {
				$newpayfrom = trim($newpayfrom . " " . $payfromarr[$i]);
			}
		}
		$sqry = "Select client_name from client_master where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and ucase(client_name) like '" . strtoupper($newpayfrom) . "%'";
		$ss_result = $conn->query($sqry);
		if ($ss_result->num_rows > 0) {
			$sbrow = $ss_result->fetch_assoc();
			return $sbrow["client_name"];
		} else {
			return "";
		}
	}
}


function createPurposeCodeEncoded($companyId, $subCompanyId, $projectId, $categoryId, $subCategoryId, $purposeid, $providedcode)
{
	$plistList = [];
	$cpp = "000000";
	$pprovidedcode = str_replace(".", "", $providedcode);
	if ($pprovidedcode) {
		if (strlen($pprovidedcode) < 3) {
			$pprovidedcode = str_pad($pprovidedcode, 3, '0', STR_PAD_LEFT);
		}
		$cpp = str_pad($pprovidedcode, 6, '0', STR_PAD_RIGHT);
	}

	$pcompanyId = str_pad($companyId, 2, '0', STR_PAD_LEFT);
	$psubCompanyId = str_pad($subCompanyId, 2, '0', STR_PAD_LEFT);
	$pprojectId = str_pad($projectId, 2, '0', STR_PAD_LEFT);
	$pcategoryId = str_pad($categoryId, 2, '0', STR_PAD_LEFT);
	$psubCategoryId = str_pad($subCategoryId, 2, '0', STR_PAD_LEFT);
	$ppurposeid = str_pad($purposeid, 3, '0', STR_PAD_LEFT);
	$combinedCode = $pcompanyId . $psubCompanyId . $pprojectId . $pcategoryId . $psubCategoryId . $ppurposeid . $cpp;

	$plistList[] = [
		"combinedCode" => $combinedCode,
		"categoryId" => $pcategoryId,
		"subCategoryId" => $psubCategoryId,
		"purposeId" => $ppurposeid,
		"providedcode" => $cpp,
	];

	return $plistList;
}

function getpurposeid($conn, $companyid, $subcompanyid, $projectid, $type = null)
{
	if ($type == 'credit') {
		$pm = "purpose_code_master_credit";
	} else {
		$pm = "purpose_code_master";
	}
	$qry = "select ifnull(max(cast(purposeid as unsigned)),0)+1 as purposeid from " . $pm . " where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and ifnull(tedit,'')=''";
	$res = $conn->query($qry);
	$purposeid = 1;
	if ($res->num_rows > 0) {
		$row = $res->fetch_assoc();
		$purposeid = $row["purposeid"];
	}
	return $purposeid;
}

// Created By Chatur Singh
function getcompanydetails($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$comq = "select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='')";
	} else if ($shortby == "companyid") {
		$comq = "select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='') and companyid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "sno") {
		$comq = "select sno, countryid, companyid, subcompanyid, companyname, stateid, districtid, pincode, address, contact_number, email_id, registration_no, logo, footer FROM tbl_company_master where (tedit is null or tedit='') and sno='" . $shortbyvalue . "'";
	}
	$comq_result = $conn->query($comq);
	$companylist = array();
	if ($comq_result->num_rows > 0) {
		while ($cmprow = $comq_result->fetch_assoc()) {
			$companylist[] = array('sno' => $cmprow['sno'], 'countryid' => $cmprow['countryid'], 'companyid' => $cmprow['companyid'], 'subcompanyid' => $cmprow['subcompanyid'], 'company_name' => $cmprow['companyname'], 'address' => $cmprow['address'], 'stateid' => $cmprow['stateid'], 'districtid' => $cmprow['districtid'], 'pincode' => $cmprow['pincode'], 'contact_number' => $cmprow['contact_number'], 'email' => $cmprow['email_id'], 'registration_no' => $cmprow['registration_no'], 'logo' => $cmprow['logo'], 'footer' => $cmprow['footer']);
		}
	}
	return $companylist;
}
// Created By Chatur Singh
function getsubcompanydetails($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ssbq = "select sno, registrationdate, countryid, companyid, subcompanyid, subcompanyname, stateid, districtid, financialyear_start, identifier, emailid as invoicing_emailid FROM tbl_company_submaster WHERE companyid='" . $companyid . "' and (tedit is null or tedit='')";
	} else if ($shortby == "subcompanyid") {
		$ssbq = "select sno, registrationdate, countryid, companyid, subcompanyid, subcompanyname, stateid, districtid, financialyear_start, identifier, emailid as invoicing_emailid FROM tbl_company_submaster WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "allsubcompanywithcopanyid") {
		$ssbq = "select sno, registrationdate, countryid, companyid, subcompanyid, subcompanyname, stateid, districtid, financialyear_start, identifier, emailid as invoicing_emailid FROM tbl_company_submaster WHERE (tedit is null or tedit='') and companyid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	}
	$ssbq_result = $conn->query($ssbq) or die($conn->error);
	$sblist = array();
	if ($ssbq_result->num_rows > 0) {
		while ($sbrow = $ssbq_result->fetch_assoc()) {
			$sblist[] = array('sno' => $sbrow['sno'], 'registrationdate' => $sbrow['registrationdate'], 'countryid' => $sbrow['countryid'], 'subcompanyid' => $sbrow['subcompanyid'], 'companyid' => $sbrow['companyid'], 'subcompanyname' => $sbrow['subcompanyname'], 'stateid' => $sbrow['stateid'], 'districtid' => $sbrow['districtid'], 'financialyear_start' => $sbrow['financialyear_start'], 'identifier' => $sbrow['identifier'], 'invoicing_emailid' => $sbrow['invoicing_emailid']);
		}
	}
	return $sblist;
}
// Created By Chatur Singh
function getprojectdetailwithcmpsubcmp($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname, cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL order by p.companyid, p.subcompanyid, p.projectid");
	} else if ($shortby == "withauthrizedcompanyid") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname, cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('" . str_replace(",", "','", $companyid) . "') order by p.companyid, p.subcompanyid, p.projectid");
	} else if ($shortby == "withauthrizedsubcompanyid") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname, cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('" . str_replace(",", "','", $companyid) . "')  and p.subcompanyid in('" . str_replace(",", "','", $subcompanyid) . "') order by p.companyid, p.subcompanyid, p.projectid");
	} else if ($shortby == "withauthrizedprojects") {
		$ppq = $conn->query("SELECT p.companyid, p.subcompanyid, p.projectid, p.projectname, cm.companyname, scm.subcompanyname, cm.companyfoldername FROM tbl_project_master p LEFT JOIN tbl_company_master cm ON p.companyid = cm.companyid AND (cm.tedit IS NULL OR cm.tedit = '') AND (p.tedit IS NULL OR p.tedit='') AND cm.companyname IS NOT NULL LEFT JOIN tbl_company_submaster scm ON p.companyid = scm.companyid AND p.subcompanyid = scm.subcompanyid AND (scm.tedit IS NULL OR scm.tedit = '') AND scm.subcompanyname IS NOT NULL WHERE p.companyid IS NOT NULL AND p.companyid <> '' AND p.subcompanyid IS NOT NULL AND p.subcompanyid <> '' AND cm.companyname IS NOT NULL AND scm.subcompanyname IS NOT NULL and p.companyid in('" . str_replace(",", "','", $companyid) . "')  and p.subcompanyid in('" . str_replace(",", "','", $subcompanyid) . "')  and p.projectid in('" . str_replace(",", "','", $shortbyvalue) . "') order by p.companyid, p.subcompanyid, p.projectid");
	}
	$pplist = array();
	if ($ppq->num_rows > 0) {
		while ($prow = $ppq->fetch_assoc()) {
			$pplist[] = array('companyid' => $prow['companyid'], 'subcompanyid' => $prow['subcompanyid'], 'projectid' => $prow['projectid'], 'projectname' => $prow['projectname'], 'companyname' => $prow['companyname'], 'subcompanyname' => $prow['subcompanyname'], 'companyfoldername' => $prow['companyfoldername']);
		}
	}
	return $pplist;
}

function getexpensessheetimportfunction($conn, $companyid, $subcompanyid, $projectid)
{
	$sqry = "Select * from tbl_expensessheet_importfunction where company_id='" . $companyid . "'";
	$ss_result = $conn->query($sqry) or die($conn->error);
	if ($ss_result->num_rows > 0) {
		while ($sbrow = $ss_result->fetch_assoc()) {
			$sblist = array('0' => $sbrow['PaymentDate'], '1' => $sbrow['ModeofPayment'], '2' => $sbrow['Chequeno'], '3' => $sbrow['Bank'], '4' => $sbrow['ExpenseDetails'], '5' => $sbrow['TotalAmount'], '6' => $sbrow['TDS'], '7' => $sbrow['Amount'], '8' => $sbrow['AfterTDSDeduction'], '9' => $sbrow['PurposeCode'], '10' => $sbrow['Narration'], '11' => $sbrow['SelfRemarks']);
		}
	}
	return $sblist;
}
function getdepositsheetimportfunction($conn, $companyid, $subcompanyid, $projectid)
{
	$sqry = "Select * from tbl_depositsheet_importfunction where company_id='" . $companyid . "'";
	$ss_result = $conn->query($sqry) or die($conn->error);
	if ($ss_result->num_rows > 0) {
		while ($sbrow = $ss_result->fetch_assoc()) {
			$sblist = array('0' => $sbrow['Sno'], '1' => $sbrow['Date'], '2' => $sbrow['ReceiptNo'], '3' => $sbrow['PurposeofDonation'], '4' => $sbrow['Receivedfrom'], '5' => $sbrow['PAN'], '6' => $sbrow['Modeofpayment'], '7' => $sbrow['Chequenumber'], '8' => $sbrow['Amount'], '9' => $sbrow['OurBank'], '10' => $sbrow['Address'], '11' => $sbrow['Narration']);
		}
	}
	return $sblist;
}
function getprojectdetailsemails($conn, $companyid, $subcompanyid, $projectid)
{
	$ssbq = "select group_concat(email) as email FROM tbl_companyemails WHERE company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "'";
	$ssbq_result = $conn->query($ssbq) or die($conn->error);
	$sblist = "";
	if ($ssbq_result->num_rows > 0) {
		$sbrow = $ssbq_result->fetch_assoc();
		$sblist = $sbrow['email'];
	}
	return $sblist;
}
function getcountryname_project($conn, $projectid)
{
	$countryname = "";
	$sql = "select distinct p.countryid , c.countryname ,c.countrycode from tbl_project_master p join tbl_country_master c on c.countryid=p.countryid where p.projectid ='" . $projectid . "'";
	$result = $conn->query($sql) or die($conn->error);
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		$countryname = $row['countryname'];
	}
	return $countryname;
}

function getclientdetils_info($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithprojectid") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,ifnull(currency,'') as currency FROM client_master where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "'";
	} else if ($shortby == "interdiatename") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay, ifnull(currency,'') as currency FROM client_master where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and client_name='" . $shortbyvalue . "'";
	} else if ($shortby == "intermediateid") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay, ifnull(currency,'') as currency FROM client_master where  (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and client_id='" . $shortbyvalue . "'";
	} else if ($shortby == "chkclientname") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay, ifnull(currency,'') as currency FROM client_master where  (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and lcase(trim(client_name))='" . strtolower($shortbyvalue) . "'";
	}
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('sno' => $clrow['sno'], 'companyid' => $clrow['companyid'], 'subcompanyid' => $clrow['subcompanyid'], 'projectid' => $clrow['projectid'], 'client_id' => $clrow['client_id'], 'client_name' => $clrow['client_name'], 'invoicing_address' => $clrow['invoicing_address'], 'phones' => $clrow['phones'], 'client_email' => $clrow['client_email'], 'vat' => $clrow['vat'], 'date_time' => $clrow['date_time'], 'comp_website' => $clrow['comp_website'], 'name_forpo' => $clrow['name_forpo'], 'contact_forpo' => $clrow['contact_forpo'], 'email_forpo' => $clrow['email_forpo'], 'name_foraccpay' => $clrow['name_foraccpay'], 'email_foraccpay' => $clrow['email_foraccpay'], 'currency' => $clrow['currency']);
		}
	}
	return $clarray;
}

function getintermediatedetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithprojectid") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,currency FROM intermediatemaster where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "'";
	} else if ($shortby == "interdiatename") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,currency FROM intermediatemaster where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and client_name='" . $shortbyvalue . "'";
	} else if ($shortby == "intermediateid") {
		$clinfo = "select sno, companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay,currency FROM intermediatemaster where  (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid='" . str_replace(",", "','", $projectid) . "' and client_id='" . $shortbyvalue . "'";
	}
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('sno' => $clrow['sno'], 'companyid' => $clrow['companyid'], 'subcompanyid' => $clrow['subcompanyid'], 'projectid' => $clrow['projectid'], 'client_id' => $clrow['client_id'], 'client_name' => $clrow['client_name'], 'invoicing_address' => $clrow['invoicing_address'], 'phones' => $clrow['phones'], 'client_email' => $clrow['client_email'], 'vat' => $clrow['vat'], 'date_time' => $clrow['date_time'], 'comp_website' => $clrow['comp_website'], 'name_forpo' => $clrow['name_forpo'], 'contact_forpo' => $clrow['contact_forpo'], 'email_forpo' => $clrow['email_forpo'], 'name_foraccpay' => $clrow['name_foraccpay'], 'email_foraccpay' => $clrow['email_foraccpay'], 'currency' => $clrow['currency']);
		}
	}
	return $clarray;
}

function getfricpurpose($conn)
{
	$clinfo = "Select purpose,purposecode from tbl_fric_purposemaster";
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('purpose' => $clrow['purpose'], 'purposecode' => $clrow['purposecode']);
		}
	}
	return $clarray;
}

function getfriccurrency($conn)
{
	$clinfo = "Select currency,currency_val from tbl_currency_master";
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('currency' => $clrow['currency'], 'currencyval' => $clrow['currency_val']);
		}
	}
	return $clarray;
}

function getcontractordetails($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$gcq = "select sno, companyid, subcompanyid, contractor_id, contractor_name, company_name, servicetype, office_address, office_email, office_phone, correspond_address, comp_registration_no, comp_taxt_reference, comp_taxt_district, ni_ssn_no, vat_registration_no, personto_contct, emailof_contct, faxno, emailfor_selfbill, dob, day_phnno, eve_phnno, mob_phnno, date_time, bankname, accountno, ifsccode, accountname, branchname, branchaddress, currency, sort_code, bsb, swift, iban, bic, tedit, stateid, statecode, districtid, districtcode, documenttype FROM contractor_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "'";
	} else if ($shortby == "contractorid" || $shortby == "projectid") {
		$gcq = "select sno, companyid, subcompanyid, contractor_id, contractor_name, company_name, servicetype, office_address, office_email, office_phone, correspond_address, comp_registration_no, comp_taxt_reference, comp_taxt_district, ni_ssn_no, vat_registration_no, personto_contct, emailof_contct, faxno, emailfor_selfbill, dob, day_phnno, eve_phnno, mob_phnno, date_time, bankname, accountno, ifsccode, accountname, branchname, branchaddress, currency, sort_code, bsb, swift, iban, bic, tedit, stateid, statecode, districtid, districtcode, documenttype FROM contractor_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and contractor_id in('" . $shortbyvalue . "')";
	} else if ($shortby == "contractorname") {
		$gcq = "select sno, companyid, subcompanyid, contractor_id, contractor_name, company_name, servicetype, office_address, office_email, office_phone, correspond_address, comp_registration_no, comp_taxt_reference, comp_taxt_district, ni_ssn_no, vat_registration_no, personto_contct, emailof_contct, faxno, emailfor_selfbill, dob, day_phnno, eve_phnno, mob_phnno, date_time, bankname, accountno, ifsccode, accountname, branchname, branchaddress, currency, sort_code, bsb, swift, iban, bic, tedit, stateid, statecode, districtid, districtcode, documenttype FROM contractor_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and contractor_name='" . $shortbyvalue . "'";
	} else if ($shortby == "companyname") {
		$gcq = "select sno, companyid, subcompanyid, contractor_id, contractor_name, company_name, servicetype, office_address, office_email, office_phone, correspond_address, comp_registration_no, comp_taxt_reference, comp_taxt_district, ni_ssn_no, vat_registration_no, personto_contct, emailof_contct, faxno, emailfor_selfbill, dob, day_phnno, eve_phnno, mob_phnno, date_time, bankname, accountno, ifsccode, accountname, branchname, branchaddress, currency, sort_code, bsb, swift, iban, bic, tedit, stateid, statecode, districtid, districtcode, documenttype FROM contractor_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and company_name='" . $shortbyvalue . "'";
	} else if ($shortby == "companywithsubcompany") {
		$gcq = "select sno, companyid, subcompanyid, contractor_id, contractor_name, company_name, servicetype, office_address, office_email, office_phone, correspond_address, comp_registration_no, comp_taxt_reference, comp_taxt_district, ni_ssn_no, vat_registration_no, personto_contct, emailof_contct, faxno, emailfor_selfbill, dob, day_phnno, eve_phnno, mob_phnno, date_time, bankname, accountno, ifsccode, accountname, branchname, branchaddress, currency, sort_code, bsb, swift, iban, bic, tedit, stateid, statecode, districtid, districtcode, documenttype FROM contractor_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "'";
	}
	$gcq_result = $conn->query($gcq) or die($conn->error);
	$contractorlist = array();
	if ($gcq_result->num_rows > 0) {
		while ($prjrow = $gcq_result->fetch_assoc()) {
			$contractorlist[] = array('sno' => $prjrow['sno'], 'companyid' => $prjrow['companyid'], 'subcompanyid' => $prjrow['subcompanyid'], 'projectid' => $prjrow['contractor_id'], 'projectname' => $prjrow['contractor_name'], 'contractor_id' => $prjrow['contractor_id'], 'contractor_name' => $prjrow['contractor_name'], 'company_name' => $prjrow['company_name'], 'servicetype' => $prjrow['servicetype'], 'office_address' => $prjrow['office_address'], 'office_email' => $prjrow['office_email'], 'office_phone' => $prjrow['office_phone'], 'correspond_address' => $prjrow['correspond_address'], 'comp_registration_no' => $prjrow['comp_registration_no'], 'comp_taxt_reference' => $prjrow['comp_taxt_reference'], 'comp_taxt_district' => $prjrow['comp_taxt_district'], 'ni_ssn_no' => $prjrow['ni_ssn_no'], 'vat_registration_no' => $prjrow['vat_registration_no'], 'personto_contct' => $prjrow['personto_contct'], 'emailof_contct' => $prjrow['emailof_contct'], 'faxno' => $prjrow['faxno'], 'emailfor_selfbill' => $prjrow['emailfor_selfbill'], 'dob' => $prjrow['dob'], 'day_phnno' => $prjrow['day_phnno'], 'eve_phnno' => $prjrow['eve_phnno'], 'mob_phnno' => $prjrow['mob_phnno'], 'date_time' => $prjrow['date_time'], 'bankname' => $prjrow['bankname'], 'accountno' => $prjrow['accountno'], 'ifsccode' => $prjrow['ifsccode'], 'accountname' => $prjrow['accountname'], 'branchname' => $prjrow['branchname'], 'branchaddress' => $prjrow['branchaddress'], 'currency' => $prjrow['currency'], 'sort_code' => $prjrow['sort_code'], 'bsb' => $prjrow['bsb'], 'swift' => $prjrow['swift'], 'iban' => $prjrow['iban'], 'bic' => $prjrow['bic'], 'stateid' => $prjrow['stateid'], 'statecode' => $prjrow['statecode'], 'districtid' => $prjrow['districtid'], 'districtcode' => $prjrow['districtcode'], 'documenttype' => $prjrow['documenttype']);
		}
	}
	return $contractorlist;
}

function getpurposeforemail($conn, $companyid, $subcompanyid)
{
	$pge = "select id,purpose from tbl_emailcontants_purposemaster where (tedit is null or tedit='')";
	$gge_result = $conn->query($pge) or die($conn->error);
	$purposelist = array();
	if ($gge_result->num_rows > 0) {
		while ($prorow = $gge_result->fetch_assoc()) {
			$purposelist[] = array('id' => $prorow['id'], 'purpose' => $prorow['purpose']);
		}

	}
	return $purposelist;
}

function getlistoflikebeneficery($conn, $companyid, $subcompanyid, $projectid, $descriptionbank)
{
	$pge = "select distinct m.payto, b.beneficiary_name,b.purpose,b.amount from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and (m.tedit is null or m.tedit='')";
	$gge_result = $conn->query($pge) or die($conn->error);
	$beneficerylist = array();
	if ($gge_result->num_rows > 0) {
		while ($prorow = $gge_result->fetch_assoc()) {
			$getbeneficery = strtolower($prorow['beneficiary_name']);
			// echo "#". $descriptionbank;
			if (strpos(strtolower($descriptionbank), $getbeneficery) !== false) {

				$beneficerylist[] = array('payto' => $prorow['payto'], 'beneficiaryname' => $prorow['beneficiary_name'], 'purpose' => $prorow['purpose'], 'amount' => $prorow['amount']);
			}
		}

	}
	return $beneficerylist;
}




function getcontractmasterinfo($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "contractid") {
		$conq = $conn->query("select srno,contract_id,client_id,contract_name,contract_no,invoicing_comp,client_contact,client_addrs,contractor_id,contractor_comp,contractor_addrs,contract_val,strt_date,end_date,currency,arCurrTo_client,apCurrTo_contractor,sales_comssion,perc_comssion,crt_comsion,crt_perc_comsion,ext_comsion,ext_perc_comsion,mngmnt_fee,mngmnt_fee_comsion,sales_owner,client_terms,client_vatrate,cndate_trmAP,candidate_vatrate,finalPayClause,mthodinvoicingto_client,timesheetappr_method,invemailto_client,emalforaprr,invccto_client,emailccto,billmon_end,inv_freq,weekstrt FROM contract_master where (tedit is null or tedit='') and contract_id='" . $shortbyvalue . "'");
	} else if ($shortby == "clientid") {
		$conq = $conn->query("select srno,contract_id,client_id,contract_name,contract_no,invoicing_comp,client_contact,client_addrs,contractor_id,contractor_comp,contractor_addrs,contract_val,strt_date,end_date,currency,arCurrTo_client,apCurrTo_contractor,sales_comssion,perc_comssion,crt_comsion,crt_perc_comsion,ext_comsion,ext_perc_comsion,mngmnt_fee,mngmnt_fee_comsion,sales_owner,client_terms,client_vatrate,cndate_trmAP,candidate_vatrate,finalPayClause,mthodinvoicingto_client,timesheetappr_method,invemailto_client,emalforaprr,invccto_client,emailccto,billmon_end,inv_freq,weekstrt FROM contract_master where (tedit is null or tedit='') and client_id='" . $shortbyvalue . "'");
	}
	$contactmasterlist = array();
	if ($conq->num_rows > 0) {
		while ($cntrow = $conq->fetch_assoc()) {
			$contactmasterlist[] = array('srno' => $cntrow['srno'], 'contract_id' => $cntrow['contract_id'], 'client_id' => $cntrow['client_id'], 'contract_name' => $cntrow['contract_name'], 'contract_no' => $cntrow['contract_no'], 'invoicing_comp' => $cntrow['invoicing_comp'], 'client_contact' => $cntrow['client_contact'], 'client_addrs' => $cntrow['client_addrs'], 'contractor_id' => $cntrow['contractor_id'], 'contractor_comp' => $cntrow['contractor_comp'], 'contractor_addrs' => $cntrow['contractor_addrs'], 'contract_val' => $cntrow['contract_val'], 'strt_date' => $cntrow['strt_date'], 'end_date' => $cntrow['end_date'], 'currency' => $cntrow['currency'], 'arCurrTo_client' => $cntrow['arCurrTo_client'], 'apCurrTo_contractor' => $cntrow['apCurrTo_contractor'], 'sales_comssion' => $cntrow['sales_comssion'], 'perc_comssion' => $cntrow['perc_comssion'], 'crt_comsion' => $cntrow['crt_comsion'], 'crt_perc_comsion' => $cntrow['crt_perc_comsion'], 'ext_comsion' => $cntrow['ext_comsion'], 'ext_perc_comsion' => $cntrow['ext_perc_comsion'], 'mngmnt_fee' => $cntrow['mngmnt_fee'], 'mngmnt_fee_comsion' => $cntrow['mngmnt_fee_comsion'], 'sales_owner' => $cntrow['sales_owner'], 'client_terms' => $cntrow['client_terms'], 'client_vatrate' => $cntrow['client_vatrate'], 'cndate_trmAP' => $cntrow['cndate_trmAP'], 'candidate_vatrate' => $cntrow['candidate_vatrate'], 'finalPayClause' => $cntrow['finalPayClause'], 'mthodinvoicingto_client' => $cntrow['mthodinvoicingto_client'], 'timesheetappr_method' => $cntrow['timesheetappr_method'], 'invemailto_client' => $cntrow['invemailto_client'], 'emalforaprr' => $cntrow['emalforaprr'], 'invccto_client' => $cntrow['invccto_client'], 'emailccto' => $cntrow['emailccto'], 'billmon_end' => $cntrow['billmon_end'], 'inv_freq' => $cntrow['inv_freq'], 'weekstrt' => $cntrow['weekstrt']);
		}
	}
	return $contactmasterlist;
}

function getstoocdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "requestno") {
		$itemqry = "select item_name,quantity,item_rate,itemgst,itemamount,isrefundable,itempurpose,itembnfname FROM tbl_stock where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_no='" . $shortbyvalue . "'";
	}
	$itemqry_result = $conn->query($itemqry);
	if ($itemqry_result->num_rows > 0) {
		while ($irow = $itemqry_result->fetch_assoc()) {
			$itemlist[] = array("item_name" => $irow['item_name'], "quantity" => $irow['quantity'], "item_rate" => $irow['item_rate'], "itemgst" => $irow['itemgst'], "itemamount" => $irow['itemamount'], "isrefundable" => $irow['isrefundable'], "itempurpose" => $irow['itempurpose'], "itembnfname" => $irow['itembnfname']);
		}
	} else {
		$itemlist = array();
	}
	return $itemlist;
}

function getpodetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithprojectid") {
		$gpo = "select sno, po_no, po_id, po_amount, strt_date, end_date, client_id, contract_id, contract_no, contractor_id FROM tbl_po_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "clientid") {
		$gpo = "select sno, po_no, po_id, po_amount, strt_date, end_date, client_id, contract_id, contract_no, contractor_id FROM tbl_po_master where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and client_id='" . $shortbyvalue . "'";
	} else if ($shortby == "ponumber") {
		$gpo = "select sno, po_no, po_id, po_amount, strt_date, end_date, client_id, contract_id, contract_no, contractor_id FROM tbl_po_master where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and po_no='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$gpo = "select sno, po_no, po_id, po_amount, strt_date, end_date, client_id, contract_id, contract_no, contractor_id FROM tbl_po_master where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$gpo_result = $conn->query($gpo) or die($conn->error);
	$gpolist = array();
	if ($gpo_result->num_rows > 0) {
		while ($gprow = $gpo_result->fetch_assoc()) {
			$gpolist[] = array('sno' => $gprow['sno'], 'po_no' => $gprow['po_no'], 'po_id' => $gprow['po_id'], 'po_amount' => $gprow['po_amount'], 'strt_date' => $gprow['strt_date'], 'end_date' => $gprow['end_date'], 'client_id' => $gprow['client_id'], 'contract_id' => $gprow['contract_id'], 'contract_no' => $gprow['contract_no'], 'contractor_id' => $gprow['contractor_id']);
		}
	}
	return $gpolist;
}

function getinvoice_breakdowndetails($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "invoiceno") {
		$invb = "select  sno, invoice_id, invoice_no, category_id, category_name, items_id, items_name, item_typeid, item_brandid, item_termtype, units, rate, taxable_amount, vat_percent, vat_amount, totalamount_excludingtax, totalamount_includetax, gst_percent, igst_percent, gst_amt, igst_amt, discount_percent, discount_amount, total_gstamt, referencenos FROM tbl_invoices_breakdown where (tedit is null or tedit='') and invoice_no ='" . $shortbyvalue . "' order by invoice_no";
	} else if ($shortby == "sno") {
		$invb = "select sno, invoice_id, invoice_no, category_id, category_name, items_id, items_name, item_typeid, item_brandid, item_termtype, units, rate, taxable_amount, vat_percent, vat_amount, totalamount_excludingtax, totalamount_includetax, gst_percent, igst_percent, gst_amt, igst_amt, discount_percent, discount_amount, total_gstamt, referencenos FROM tbl_invoices_breakdown where (tedit is null or tedit='') and sno='" . $shortbyvalue . "'";
	}
	$invb_result = $conn->query($invb) or die($conn->error);
	$invbrlist = array();
	if ($invb_result->num_rows > 0) {
		while ($invbrow = $invb_result->fetch_assoc()) {
			$invbrlist[] = array('sno' => $invbrow['sno'], 'invoice_id' => $invbrow['invoice_id'], 'invoice_no' => $invbrow['invoice_no'], 'category_id' => $invbrow['category_id'], 'category_name' => $invbrow['category_name'], 'items_id' => $invbrow['items_id'], 'items_name' => $invbrow['items_name'], 'item_typeid' => $invbrow['item_typeid'], 'item_brandid' => $invbrow['item_brandid'], 'item_termtype' => $invbrow['item_termtype'], 'units' => $invbrow['units'], 'rate' => $invbrow['rate'], 'taxable_amount' => $invbrow['taxable_amount'], 'vat_percent' => $invbrow['vat_percent'], 'vat_amount' => $invbrow['vat_amount'], 'totalamount_excludingtax' => $invbrow['totalamount_excludingtax'], 'totalamount_includetax' => $invbrow['totalamount_includetax'], 'gst_percent' => $invbrow['gst_percent'], 'igst_percent' => $invbrow['igst_percent'], 'gst_amt' => $invbrow['gst_amt'], 'igst_amt' => $invbrow['igst_amt'], 'discount_percent' => $invbrow['discount_percent'], 'discount_amount' => $invbrow['discount_amount'], 'total_gstamt' => $invbrow['total_gstamt'], 'referencenos' => $invbrow['referencenos']);
		}
	}
	return $invbrlist;
}

function getinvoicedetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allactive") {
		$ltq = "select sno, companyid, subcompanyid, projectid, invoice_id, invoice_type, invoice_date, invoice_no, cd_db_date, cd_db_no, creditnote_date, creditnote_no, transaction_type, location, client_id, client_name, po_no, invoice_description, currency, units, rate, due_date, invoice_amount, vat_percentage, vat_amount, open_balance, quick_book_refno, date_time, PrintOnCheckName, status, adjusted_po_no, whether_partial, partial_ref_id, from_address, from_phone, from_email, account_id, terms_days, export_no, export_date, is_confirm, is_deleted, is_invoice_reverse, suggestion, reason, APAccount, Class, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4, BillAddrCity, BillAddrState, BillAddrPostalCode, BillAddrCountry, PrivateNote, ExchangeRate, AmountsInclTax, Deposit, ToBePrinted, ToBeEmailed, AllowIPNPayment, AllowOnlineCreditCardPayment, AllowOnlineACHPayment, LineServiceDate, LineItem, LineQty, LineUnitPrice, LineAmount, LineClass, FactorYN, HSBCFactorRef, ArapInvNo, ExpenseAmount, ExpenseAccount, ExpenseDesc, ExpenseBillableStatus, ExpenseTaxCode, ExpenseClass, etdatetime, upload_filename, invoice_created_via, remittance_date, remittance_status, remittance_refno, remittance_emailid, remittance_adjusted_amount, remittance_open_amount, remittance_pay_type, comparewith,received_amount,balance_amount,comparewith_email,comparewith_bank, cr_reconciled_amount, cr_balance_amount FROM tbl_invoices";
	} else if ($shortby == "sno") {
		$ltq = "select sno, companyid, subcompanyid, projectid, invoice_id, invoice_type, invoice_date, invoice_no, cd_db_date, cd_db_no, creditnote_date, creditnote_no, transaction_type, location, client_id, client_name, po_no, invoice_description, currency, units, rate, due_date, invoice_amount, vat_percentage, vat_amount, open_balance, quick_book_refno, date_time, PrintOnCheckName, status, adjusted_po_no, whether_partial, partial_ref_id, from_address, from_phone, from_email, account_id, terms_days, export_no, export_date, is_confirm, is_deleted, is_invoice_reverse, suggestion, reason, APAccount, Class, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4, BillAddrCity, BillAddrState, BillAddrPostalCode, BillAddrCountry, PrivateNote, ExchangeRate, AmountsInclTax, Deposit, ToBePrinted, ToBeEmailed, AllowIPNPayment, AllowOnlineCreditCardPayment, AllowOnlineACHPayment, LineServiceDate, LineItem, LineQty, LineUnitPrice, LineAmount, LineClass, FactorYN, HSBCFactorRef, ArapInvNo, ExpenseAmount, ExpenseAccount, ExpenseDesc, ExpenseBillableStatus, ExpenseTaxCode, ExpenseClass, etdatetime, upload_filename, invoice_created_via, remittance_date, remittance_status, remittance_refno, remittance_emailid, remittance_adjusted_amount, remittance_open_amount, remittance_pay_type, comparewith,received_amount,balance_amount,comparewith_email,comparewith_bank, cr_reconciled_amount, cr_balance_amount FROM tbl_invoices where sno='" . $shortbyvalue . "'";
	} else if ($shortby == "invoiceno") {
		$ltq = "select sno, companyid, subcompanyid, projectid, invoice_id, invoice_type, invoice_date, invoice_no, cd_db_date, cd_db_no, creditnote_date, creditnote_no, transaction_type, location, client_id, client_name, po_no, invoice_description, currency, units, rate, due_date, invoice_amount, vat_percentage, vat_amount, open_balance, quick_book_refno, date_time, PrintOnCheckName, status, adjusted_po_no, whether_partial, partial_ref_id, from_address, from_phone, from_email, account_id, terms_days, export_no, export_date, is_confirm, is_deleted, is_invoice_reverse, suggestion, reason, APAccount, Class, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4, BillAddrCity, BillAddrState, BillAddrPostalCode, BillAddrCountry, PrivateNote, ExchangeRate, AmountsInclTax, Deposit, ToBePrinted, ToBeEmailed, AllowIPNPayment, AllowOnlineCreditCardPayment, AllowOnlineACHPayment, LineServiceDate, LineItem, LineQty, LineUnitPrice, LineAmount, LineClass, FactorYN, HSBCFactorRef, ArapInvNo, ExpenseAmount, ExpenseAccount, ExpenseDesc, ExpenseBillableStatus, ExpenseTaxCode, ExpenseClass, etdatetime, upload_filename, invoice_created_via, remittance_date, remittance_status, remittance_refno, remittance_emailid, remittance_adjusted_amount, remittance_open_amount, remittance_pay_type, comparewith,received_amount,balance_amount,comparewith_email,comparewith_bank, cr_reconciled_amount, cr_balance_amount FROM tbl_invoices where invoice_no like '%" . $shortbyvalue . "%' order by invoice_no";
		$ltq = "select sno, companyid, subcompanyid, projectid, invoice_id, invoice_type, invoice_date, invoice_no, cd_db_date, cd_db_no, creditnote_date, creditnote_no, transaction_type, location, client_id, client_name, po_no, invoice_description, currency, units, rate, due_date, invoice_amount, vat_percentage, vat_amount, open_balance, quick_book_refno, date_time, PrintOnCheckName, status, adjusted_po_no, whether_partial, partial_ref_id, from_address, from_phone, from_email, account_id, terms_days, export_no, export_date, is_confirm, is_deleted, is_invoice_reverse, suggestion, reason, APAccount, Class, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4, BillAddrCity, BillAddrState, BillAddrPostalCode, BillAddrCountry, PrivateNote, ExchangeRate, AmountsInclTax, Deposit, ToBePrinted, ToBeEmailed, AllowIPNPayment, AllowOnlineCreditCardPayment, AllowOnlineACHPayment, LineServiceDate, LineItem, LineQty, LineUnitPrice, LineAmount, LineClass, FactorYN, HSBCFactorRef, ArapInvNo, ExpenseAmount, ExpenseAccount, ExpenseDesc, ExpenseBillableStatus, ExpenseTaxCode, ExpenseClass, etdatetime, upload_filename, invoice_created_via, remittance_date, remittance_status, remittance_refno, remittance_emailid, remittance_adjusted_amount, remittance_open_amount, remittance_pay_type, comparewith,received_amount,balance_amount,comparewith_email,comparewith_bank, cr_reconciled_amount, cr_balance_amount FROM tbl_invoices where invoice_no ='" . $shortbyvalue . "' order by invoice_no";
	} else if ($shortby == "uncompareinvoiceno") {
		$ltq = "select sno, companyid, subcompanyid, projectid, invoice_id, invoice_type, invoice_date, invoice_no, cd_db_date, cd_db_no, creditnote_date, creditnote_no, transaction_type, location, client_id, client_name, po_no, invoice_description, currency, units, rate, due_date, invoice_amount, vat_percentage, vat_amount, open_balance, quick_book_refno, date_time, PrintOnCheckName, status, adjusted_po_no, whether_partial, partial_ref_id, from_address, from_phone, from_email, account_id, terms_days, export_no, export_date, is_confirm, is_deleted, is_invoice_reverse, suggestion, reason, APAccount, Class, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4, BillAddrCity, BillAddrState, BillAddrPostalCode, BillAddrCountry, PrivateNote, ExchangeRate, AmountsInclTax, Deposit, ToBePrinted, ToBeEmailed, AllowIPNPayment, AllowOnlineCreditCardPayment, AllowOnlineACHPayment, LineServiceDate, LineItem, LineQty, LineUnitPrice, LineAmount, LineClass, FactorYN, HSBCFactorRef, ArapInvNo, ExpenseAmount, ExpenseAccount, ExpenseDesc, ExpenseBillableStatus, ExpenseTaxCode, ExpenseClass, etdatetime, upload_filename, invoice_created_via, remittance_date, remittance_status, remittance_refno, remittance_emailid, remittance_adjusted_amount, remittance_open_amount, remittance_pay_type, comparewith,received_amount,balance_amount,comparewith_email,comparewith_bank, cr_reconciled_amount, cr_balance_amount FROM tbl_invoices where (comparewith is null or comparewith='') and invoice_no like '%" . $shortbyvalue . "%' order by invoice_no";
	} else if ($shortby == "unverifyed") {
		$ltq = "select sno, companyid, subcompanyid, projectid, invoice_id, invoice_type, invoice_date, invoice_no, cd_db_date, cd_db_no, creditnote_date, creditnote_no, transaction_type, location, client_id, client_name, po_no, invoice_description, currency, units, rate, due_date, invoice_amount, vat_percentage, vat_amount, open_balance, quick_book_refno, date_time, PrintOnCheckName, status, adjusted_po_no, whether_partial, partial_ref_id, from_address, from_phone, from_email, account_id, companyid, terms_days, export_no, export_date, is_confirm, is_deleted, is_invoice_reverse, suggestion, reason, APAccount, Class, BillAddrLine1, BillAddrLine2, BillAddrLine3, BillAddrLine4, BillAddrCity, BillAddrState, BillAddrPostalCode, BillAddrCountry, PrivateNote, ExchangeRate, AmountsInclTax, Deposit, ToBePrinted, ToBeEmailed, AllowIPNPayment, AllowOnlineCreditCardPayment, AllowOnlineACHPayment, LineServiceDate, LineItem, LineQty, LineUnitPrice, LineAmount, LineClass, FactorYN, HSBCFactorRef, ArapInvNo, ExpenseAmount, ExpenseAccount, ExpenseDesc, ExpenseBillableStatus, ExpenseTaxCode, ExpenseClass, etdatetime, upload_filename, invoice_created_via, remittance_date, remittance_status, remittance_refno, remittance_emailid, remittance_adjusted_amount, remittance_open_amount, remittance_pay_type, comparewith,received_amount,balance_amount,comparewith_email,comparewith_bank, cr_reconciled_amount, cr_balance_amount FROM tbl_invoices where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  and (comparewith is null or comparewith='')    order by invoice_no";
	}
	$ltq_result = $conn->query($ltq) or die($conn->error);
	$invdlist = array();
	if ($ltq_result->num_rows > 0) {
		while ($invrow = $ltq_result->fetch_assoc()) {
			$invdlist[] = array('sno' => $invrow['sno'], 'project_id' => $invrow['projectid'], 'subcmp_id' => $invrow['subcompanyid'], 'company_id' => $invrow['companyid'], 'invoice_id' => $invrow['invoice_id'], 'invoice_type' => $invrow['invoice_type'], 'invoice_date' => $invrow['invoice_date'], 'disinvoice_date' => date('d-M-Y', strtotime($invrow['invoice_date'])), 'invoice_no' => $invrow['invoice_no'], 'cd_db_date' => $invrow['cd_db_date'], 'cd_db_no' => $invrow['cd_db_no'], 'creditnote_date' => $invrow['creditnote_date'], 'creditnote_no' => $invrow['creditnote_no'], 'transaction_type' => $invrow['transaction_type'], 'location' => $invrow['location'], 'client_id' => $invrow['client_id'], 'client_name' => $invrow['client_name'], 'po_no' => $invrow['po_no'], 'invoice_description' => $invrow['invoice_description'], 'currency' => $invrow['currency'], 'units' => $invrow['units'], 'rate' => $invrow['rate'], 'due_date' => $invrow['due_date'], 'invoice_amount' => $invrow['invoice_amount'], 'vat_percentage' => $invrow['vat_percentage'], 'vat_amount' => $invrow['vat_amount'], 'open_balance' => $invrow['open_balance'], 'quick_book_refno' => $invrow['quick_book_refno'], 'date_time' => $invrow['date_time'], 'PrintOnCheckName' => $invrow['PrintOnCheckName'], 'status' => $invrow['status'], 'adjusted_po_no' => $invrow['adjusted_po_no'], 'whether_partial' => $invrow['whether_partial'], 'partial_ref_id' => $invrow['partial_ref_id'], 'from_address' => $invrow['from_address'], 'from_phone' => $invrow['from_phone'], 'from_email' => $invrow['from_email'], 'account_id' => $invrow['account_id'], 'terms_days' => $invrow['terms_days'], 'export_no' => $invrow['export_no'], 'export_date' => $invrow['export_date'], 'is_confirm' => $invrow['is_confirm'], 'is_deleted' => $invrow['is_deleted'], 'is_invoice_reverse' => $invrow['is_invoice_reverse'], 'suggestion' => $invrow['suggestion'], 'reason' => $invrow['reason'], 'APAccount' => $invrow['APAccount'], 'Class' => $invrow['Class'], 'BillAddrLine1' => $invrow['BillAddrLine1'], 'BillAddrLine2' => $invrow['BillAddrLine2'], 'BillAddrLine3' => $invrow['BillAddrLine3'], 'BillAddrLine4' => $invrow['BillAddrLine4'], 'BillAddrCity' => $invrow['BillAddrCity'], 'BillAddrState' => $invrow['BillAddrState'], 'BillAddrPostalCode' => $invrow['BillAddrPostalCode'], 'BillAddrCountry' => $invrow['BillAddrCountry'], 'PrivateNote' => $invrow['PrivateNote'], 'ExchangeRate' => $invrow['ExchangeRate'], 'AmountsInclTax' => $invrow['AmountsInclTax'], 'Deposit' => $invrow['Deposit'], 'ToBePrinted' => $invrow['ToBePrinted'], 'ToBeEmailed' => $invrow['ToBeEmailed'], 'AllowIPNPayment' => $invrow['AllowIPNPayment'], 'AllowOnlineCreditCardPayment' => $invrow['AllowOnlineCreditCardPayment'], 'AllowOnlineACHPayment' => $invrow['AllowOnlineACHPayment'], 'LineServiceDate' => $invrow['LineServiceDate'], 'LineItem' => $invrow['LineItem'], 'LineQty' => $invrow['LineQty'], 'LineUnitPrice' => $invrow['LineUnitPrice'], 'LineAmount' => $invrow['LineAmount'], 'LineClass' => $invrow['LineClass'], 'FactorYN' => $invrow['FactorYN'], 'HSBCFactorRef' => $invrow['HSBCFactorRef'], 'ArapInvNo' => $invrow['ArapInvNo'], 'ExpenseAmount' => $invrow['ExpenseAmount'], 'ExpenseAccount' => $invrow['ExpenseAccount'], 'ExpenseDesc' => $invrow['ExpenseDesc'], 'ExpenseBillableStatus' => $invrow['ExpenseBillableStatus'], 'ExpenseTaxCode' => $invrow['ExpenseTaxCode'], 'ExpenseClass' => $invrow['ExpenseClass'], 'etdatetime' => $invrow['etdatetime'], 'upload_filename' => $invrow['upload_filename'], 'invoice_created_via' => $invrow['invoice_created_via'], 'remittance_date' => $invrow['remittance_date'], 'remittance_status' => $invrow['remittance_status'], 'remittance_refno' => $invrow['remittance_refno'], 'remittance_emailid' => $invrow['remittance_emailid'], 'remittance_adjusted_amount' => $invrow['remittance_adjusted_amount'], 'remittance_open_amount' => $invrow['remittance_open_amount'], 'remittance_pay_type' => $invrow['remittance_pay_type'], 'comparewith' => $invrow['comparewith'], 'received_amount' => $invrow['received_amount'], 'balance_amount' => $invrow['balance_amount'], 'comparewith_email' => $invrow['comparewith_email'], 'comparewith_bank' => $invrow['comparewith_bank'], 'cr_reconciled_amount' => $invrow['cr_reconciled_amount'], 'cr_balance_amount' => $invrow['cr_balance_amount']);
		}
	}
	return $invdlist;
}

function checkinvoicenoexistornotinemailcontain($conn, $invoice_no)
{
	$returnstatus = "";
	$qurychk = "select invoice_no from tbl_invoices_emailcontent where invoice_no='" . $invoice_no . "' and paymentstatus='full'";
	$qurychk_result = $conn->query($qurychk);
	if ($qurychk_result->num_rows > 0) {
		$returnstatus = "found";
	} else {
		$returnstatus = "";
	}
	return $returnstatus;
}

function getqbreport_uploaddata($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$qbrrq = "select sno, invoicedate, invoiceno, invoicetype, transactiontype, invoiceto, location, description, duedate, amount, openingbalance, filename, edatetime FROM tbl_qbreport_importeddata order by invoiceno";
	} else if ($shortby == "sno") {
		$qbrrq = "select sno, invoicedate, invoiceno, invoicetype, transactiontype, invoiceto, location, description, duedate, amount, openingbalance, filename, edatetime FROM tbl_qbreport_importeddata where sno='" . $shortbyvalue . "'";
	} else if ($shortby == "invoiceno") {
		$qbrrq = "select sno, invoicedate, invoiceno, invoicetype, transactiontype, invoiceto, location, description, duedate, amount, openingbalance, filename, edatetime FROM tbl_qbreport_importeddata where (tedit is null or tedit='') and  invoiceno like '%" . $shortbyvalue . "%'";
	} else if ($shortby == "uncompared") {
		$qbrrq = "select sno, invoicedate, invoiceno, invoicetype, transactiontype, invoiceto, location, description, duedate, amount, openingbalance, filename, edatetime FROM tbl_qbreport_importeddata where (tedit is null or tedit='') and (status is null or status='') and transactiontype not in('Payment','Expense', 'Bill Payment (Cheque)') order by invoiceno";
	} else if ($shortby == "compared") {
		$qbrrq = "select sno, invoicedate, invoiceno, invoicetype, transactiontype, invoiceto, location, description, duedate, amount, openingbalance, filename, edatetime FROM tbl_qbreport_importeddata where (tedit is null or tedit='') and (status is not null and status<>'') and transactiontype not in('Payment','Expense', 'Bill Payment (Cheque)') order by invoiceno";
	}
	$qbrrq_result = $conn->query($qbrrq);
	$qblist = array();
	if ($qbrrq_result->num_rows > 0) {
		while ($qrow = $qbrrq_result->fetch_assoc()) {
			$qblist[] = array('sno' => $qrow['sno'], 'invoicedate' => $qrow['invoicedate'], 'invoiceno' => $qrow['invoiceno'], 'invoicetype' => $qrow['invoicetype'], 'transactiontype' => $qrow['transactiontype'], 'invoiceto' => $qrow['invoiceto'], 'location' => $qrow['location'], 'description' => $qrow['description'], 'duedate' => $qrow['duedate'], 'amount' => $qrow['amount'], 'openingbalance' => $qrow['openingbalance'], 'filename' => $qrow['filename'], 'etdatetime' => $qrow['edatetime']);
		}
	}
	return $qblist;
}

function getcurrenceyinfo($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$cqry = $conn->query("select sno, currency, currency_val,currencyid FROM tbl_currency_master where (tedit is null or tedit='') order by currency_val");
	} else if ($shortby == "sno") {
		$cqry = $conn->query("select sno, currency, currency_val ,currencyid FROM tbl_currency_master where (tedit is null or tedit='') and sno='" . $shortbyvalue . "'");
	}
	$currencylist = array();
	if ($cqry->num_rows > 0) {
		while ($qrow = $cqry->fetch_assoc()) {
			$currencylist[] = array('sno' => $qrow['sno'], 'currency' => $qrow['currency'], 'currency_val' => $qrow['currency_val'], 'currencyid' => $qrow['currencyid']);
		}
	}
	return $currencylist;
}

function getbankstatementdetails($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "creditpending_statement") {
		$bbnkqq = "select sno, bankid, account_id, accountno, bankname, valuedate, description, chequeno, reconcilchequeno, uchequeno, debitamount, creditamount, balance, statementdate, curncy_type, reconciliation, reconciliation2, status, cr_reconciled_amount, cr_balance_amount, Tedit FROM tbl_bankstatement where (Tedit is null or Tedit='') and (status is null or status='') and creditamount>0 and (reconciliation is null or reconciliation='')";
	} else if ($shortby == "sno") {
		$bbnkqq = "select sno, bankid, account_id, accountno, bankname, valuedate, description, chequeno, reconcilchequeno, uchequeno, debitamount, creditamount, balance, statementdate, curncy_type, reconciliation, reconciliation2, status, cr_reconciled_amount, cr_balance_amount, Tedit FROM tbl_bankstatement where (Tedit is null or Tedit='') and (status is null or status='') and creditamount>0 and sno='" . $shortbyvalue . "'";
	}
	$bbnkq = $conn->query($bbnkqq) or die($conn->error);
	$bnkstslist = array();
	if ($bbnkq->num_rows > 0) {
		while ($bnkrow = $bbnkq->fetch_assoc()) {
			$bnkstslist[] = array('sno' => $bnkrow['sno'], 'bankid' => $bnkrow['bankid'], 'account_id' => $bnkrow['account_id'], 'accountno' => $bnkrow['accountno'], 'bankname' => $bnkrow['bankname'], 'valuedate' => $bnkrow['valuedate'], 'dispvaluedate' => date('d-M-Y', strtotime($bnkrow['valuedate'])), 'description' => $bnkrow['description'], 'chequeno' => $bnkrow['chequeno'], 'reconcilchequeno' => $bnkrow['reconcilchequeno'], 'uchequeno' => $bnkrow['uchequeno'], 'debitamount' => $bnkrow['debitamount'], 'creditamount' => $bnkrow['creditamount'], 'balance' => $bnkrow['balance'], 'statementdate' => $bnkrow['statementdate'], 'curncy_type' => $bnkrow['curncy_type'], 'reconciliation' => $bnkrow['reconciliation'], 'reconciliation2' => $bnkrow['reconciliation2'], 'status' => $bnkrow['status'], 'cr_reconciled_amount' => $bnkrow['cr_reconciled_amount'], 'cr_balance_amount' => $bnkrow['cr_balance_amount'], 'vdate' => $bnkrow['valuedate']);
		}
	}
	return $bnkstslist;
}

function generateUniqueAccountId($conn)
{
	$uniqueId = null;
	do {
		$generatedId = str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
		$query = $conn->prepare("SELECT COUNT(*) AS count FROM tbl_master_account WHERE account_id = ?");
		$query->bind_param("s", $generatedId);
		$query->execute();
		$result = $query->get_result();
		$row = $result->fetch_assoc();
		if ($row['count'] == 0) {
			$uniqueId = $generatedId;
		}
	} while ($uniqueId === null);
	return $uniqueId;
}


function getmaxbankidfrommaster($conn, $bankname)
{
	$mbankid = 1;
	$qurytemp = $conn->query("select id from tbl_bank_master where lcase(bankname)='" . strtolower(trim($bankname)) . "'");
	if ($qurytemp->num_rows > 0) {
		$rowt = $qurytemp->fetch_assoc();
		$mbankid = $rowt['id'];
	} else {
		$qurytemp = $conn->query("select ifnull((max(id)+1),1) as mbnkid from tbl_bank_master");

		if ($qurytemp->num_rows > 0) {
			$trow = $qurytemp->fetch_assoc();
			$mbankid = $trow['mbnkid'];
		}
		if ($mbankid != "" && $bankname != "") {
			$conn->query("INSERT INTO tbl_bank_master (id, bankname) VALUES('" . $mbankid . "','" . strtoupper($bankname) . "')");
		}
	}
	return $mbankid;
}

function getfunctionbanknamefrommaster($conn)
{
	$banks = array();
	$qry = "Select id,bankname from tbl_bank_master";
	$resultqry = $conn->query($qry);
	if ($resultqry->num_rows > 0) {
		while ($qtrow = $resultqry->fetch_assoc()) {
			$banks[] = array('id' => $qtrow['id'], 'bankname' => $qtrow['bankname']);
		}
	}

	return $banks;
	//echo json_encode($banks);

}

function getbankstatementcreditsidedata($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$bnkstslist = array();
		$i = 1;
		$classA = "";
		$classG = "";
		$qurt = "Select distinct accountid from tbl_master_account";
		$result_qt = $conn->query($qurt);
		if ($result_qt->num_rows > 0) {
			while ($qtrow = $result_qt->fetch_assoc()) {
				$bbnkqq = "select sno, bankid, account_id, accountno, bankname, valuedate, description, chequeno, reconcilchequeno, uchequeno, debitamount, creditamount, balance, statementdate, curncy_type, reconciliation, reconciliation2, status, cr_reconciled_amount, cr_balance_amount, Tedit FROM tbl_bankstatement where (Tedit is null or Tedit='') and (status is null or status='') and creditamount>0 and (status is null or status='') and account_id='" . $qtrow['accountid'] . "'";
				$bbnkq = $conn->query($bbnkqq) or die($conn->error);

				if ($bbnkq->num_rows > 0) {
					$classG = 'Y';
					while ($bnkrow = $bbnkq->fetch_assoc()) {
						$classA = "classA_" . $i;

						$bnkstslist[] = array('sno' => $bnkrow['sno'], 'bankid' => $bnkrow['bankid'], 'account_id' => $bnkrow['account_id'], 'accountno' => $bnkrow['accountno'], 'bankname' => $bnkrow['bankname'], 'valuedate' => $bnkrow['valuedate'], 'dispvaluedate' => date('d-M-Y', strtotime($bnkrow['valuedate'])), 'description' => $bnkrow['description'], 'chequeno' => $bnkrow['chequeno'], 'reconcilchequeno' => $bnkrow['reconcilchequeno'], 'uchequeno' => $bnkrow['uchequeno'], 'debitamount' => $bnkrow['debitamount'], 'creditamount' => $bnkrow['creditamount'], 'balance' => $bnkrow['balance'], 'statementdate' => $bnkrow['statementdate'], 'curncy_type' => $bnkrow['curncy_type'], 'reconciliation' => $bnkrow['reconciliation'], 'reconciliation2' => $bnkrow['reconciliation2'], 'status' => $bnkrow['status'], 'cr_reconciled_amount' => $bnkrow['cr_reconciled_amount'], 'cr_balance_amount' => $bnkrow['cr_balance_amount'], 'vdate' => $bnkrow['valuedate'], 'classG' => $classG, 'classA' => $classA);
						$classG = '';
					}

					$i++;
				}

			}
		}


	}

	return $bnkstslist;
}
function getdepositerhistorygiveninexcel($conn, $companyid, $subcompanyid, $projectid, $accountid, $vdate, $shortby)
{

	$bbnkqq = "select sno,  account_id, accountno, bankname, date,receiptno, description, chequeno, payfrom, total_amount,purpose,paymentmode FROM tbl_depositinexcelsheet where (Tedit is null or Tedit='') and (status is null or status='')  and account_id='" . $accountid . "' and date<='" . $vdate . "'";
	$bbnkq = $conn->query($bbnkqq) or die($conn->error);

	if ($bbnkq->num_rows > 0) {

		while ($bnkrow = $bbnkq->fetch_assoc()) {


			$bnkstslist[] = array('sno' => $bnkrow['sno'], 'account_id' => $bnkrow['account_id'], 'accountno' => $bnkrow['accountno'], 'bankname' => $bnkrow['bankname'], 'valuedate' => $bnkrow['date'], 'description' => $bnkrow['description'], 'chequeno' => $bnkrow['chequeno'], 'depositamount' => $bnkrow['total_amount'], 'receiptno' => $bnkrow['receiptno'], 'payfrom' => $bnkrow['payfrom'], 'purpose' => $bnkrow['purpose'], 'paymentmode' => $bnkrow['paymentmode']);
		}


	}

	return $bnkstslist;

}

function getbankstatementdetailsforuser($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "creditpending_statement") {
		$bbnkqq = "select sno, bankid, account_id, accountno, bankname, valuedate, description, chequeno, reconcilchequeno, uchequeno, debitamount, creditamount, balance, statementdate, curncy_type, reconciliation, reconciliation2, status, cr_reconciled_amount, cr_balance_amount, Tedit FROM tbl_bankstatement where (Tedit is null or Tedit='') and (status is null or status='') and (status_email is null or status_email='') and creditamount>0";
	} else if ($shortby == "sno") {
		$bbnkqq = "select sno, bankid, account_id, accountno, bankname, valuedate, description, chequeno, reconcilchequeno, uchequeno, debitamount, creditamount, balance, statementdate, curncy_type, reconciliation, reconciliation2, status, cr_reconciled_amount, cr_balance_amount, Tedit FROM tbl_bankstatement where (Tedit is null or Tedit='') and (status is null or status='') and creditamount>0 and sno='" . $shortbyvalue . "'";
	}
	$bbnkq = $conn->query($bbnkqq) or die($conn->error);
	$bnkstslist = array();
	if ($bbnkq->num_rows > 0) {
		while ($bnkrow = $bbnkq->fetch_assoc()) {
			$bnkstslist[] = array('sno' => $bnkrow['sno'], 'bankid' => $bnkrow['bankid'], 'account_id' => $bnkrow['account_id'], 'accountno' => $bnkrow['accountno'], 'bankname' => $bnkrow['bankname'], 'valuedate' => $bnkrow['valuedate'], 'dispvaluedate' => date('d-M-Y', strtotime($bnkrow['valuedate'])), 'description' => $bnkrow['description'], 'chequeno' => $bnkrow['chequeno'], 'reconcilchequeno' => $bnkrow['reconcilchequeno'], 'uchequeno' => $bnkrow['uchequeno'], 'debitamount' => $bnkrow['debitamount'], 'creditamount' => $bnkrow['creditamount'], 'balance' => $bnkrow['balance'], 'statementdate' => $bnkrow['statementdate'], 'curncy_type' => $bnkrow['curncy_type'], 'reconciliation' => $bnkrow['reconciliation'], 'reconciliation2' => $bnkrow['reconciliation2'], 'status' => $bnkrow['status'], 'cr_reconciled_amount' => $bnkrow['cr_reconciled_amount'], 'cr_balance_amount' => $bnkrow['cr_balance_amount'], 'vdate' => $bnkrow['valuedate']);
		}
	}
	return $bnkstslist;
}

function getinfoprovidebyuserunreconcile($conn, $projectid)
{
	$infouserlist = array();
	$qryinfo = "select projectid,tbl_sno,reciveddate,bank_transaction_no,bank_description,bank_amount,user_creditername,credit_purpose,credit_purposecode,user_description,infoprovideddate from tbl_creditsidetransaction_informationprovidebyuse where projectid='" . $projectid . "' ";
	$resultinfo = $conn->query($qryinfo);
	if ($resultinfo->num_rows > 0) {
		while ($rowinf = $resultinfo->fetch_assoc()) {
			$infouserlist[] = array('projectid' => $rowinf['projectid'], 'sno' => $rowinf['tbl_sno'], 'valuedate' => $rowinf['reciveddate'], 'dispvaluedate' => date('d-M-Y', strtotime($rowinf['reciveddate'])), 'bankdescription' => $rowinf['bank_description'], 'chequeno' => $bnkrow['bank_transaction_no'], 'bankamount' => $rowinf['bank_amount'], 'creditername' => $rowinf['user_creditername'], 'creditpurpose' => $rowinf['credit_purpose'], 'creditpurposecode' => $rowinf['credit_purposecode'], 'userdescription' => $rowinf['user_description'], 'infoprovideddate' => $rowinf['infoprovideddate'], 'dispuseredate' => date('d-M-Y', strtotime($rowinf['infoprovideddate'])));
		}
	}

	return $infouserlist;
}


function getverifyemailcontent($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - verified_amount) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate,verifywithbank FROM tbl_invoices_emailcontent WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "'";
	} else if ($shortby == "withoutverifywithbank") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - verified_amount) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_invoices_emailcontent WHERE (tedit is null or tedit='')  and subcompanyid='" . $subcompanyid . "' and (verifywithbank is null or verifywithbank='' or verifywithbank='partial')";
	} else if ($shortby == "withoutverifywithbankofinvoiceno") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - ifnull(verified_amount,0)) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_invoices_emailcontent WHERE (tedit is null or tedit='')  and subcompanyid='" . $subcompanyid . "' and (verifywithbank is null or verifywithbank='' or verifywithbank='partial') and invoice_no='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - ifnull(verified_amount,0)) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_invoices_emailcontent WHERE sno='" . $shortbyvalue . "'";
	}
	$sscont_result = $conn->query($sscont);
	$emcontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($emcrow = $sscont_result->fetch_assoc()) {
			$verifiedamount = $emcrow['verified_amount'];
			$emcontent[] = array('sno' => $emcrow['sno'], 'email_contentid' => $emcrow['email_contentid'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'clientid' => $emcrow['clientid'], 'clientname' => $emcrow['clientname'], 'invoice_no' => $emcrow['invoice_no'], 'invoice_date' => $emcrow['invoice_date'], 'invoice_amount' => $emcrow['invoice_amount'], 'paymentstatus' => $emcrow['paymentstatus'], 'transferamount_againstinvoice' => $emcrow['transferamount_againstinvoice'], 'transferamount' => $emcrow['transferamount'], 'verified_amount' => $emcrow['verified_amount'], 'balaceverifiedamount' => $emcrow['balaceverifiedamount'], 'transactionno' => $emcrow['transactionno'], 'transactiondate' => $emcrow['transactiondate'], 'description' => $emcrow['description'], 'emailfrom' => $emcrow['emailfrom'], 'emaildate' => $emcrow['emaildate'], 'verifywithbank' => $emcrow['verifywithbank']);
		}
	}
	return $emcontent;
}

function getkeysdetails($conn, $accountid, $shortby, $shortbyvalue)
{
	if ($shortby == "onlykey1") {
		$sscont = "select distinct key1,key2,key3,paymenttype,paytokey,keyid from tbl_bankdescription_key WHERE  accountid='" . $accountid . "' group by key1";
	}
	$sscont_result = $conn->query($sscont);
	$emcontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($emcrow = $sscont_result->fetch_assoc()) {

			$emcontent[] = array('key1' => $emcrow['key1'], 'key2' => $emcrow['key2'], 'key3' => $emcrow['key3'], 'paymenttype' => $emcrow['paymenttype'], 'paytokey' => $emcrow['paytokey'], 'keyid' => $emcrow['keyid']);
		}
	}
	return $emcontent;
}

function getdigitizedemailscontent($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, ifnull(invoice_date,'') as invoice_date , invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - verified_amount) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate,verifywithbank,emailpurpose FROM tbl_digitization_emailcontent WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (reconstruct_flag is null or reconstruct_flag='')";
	} else if ($shortby == "withoutverifywithbank") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - verified_amount) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_digitization_emailcontent WHERE (tedit is null or tedit='')  and subcompanyid='" . $subcompanyid . "' and (verifywithbank is null or verifywithbank='' or verifywithbank='partial')";
	} else if ($shortby == "withoutverifywithbankofinvoiceno") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - ifnull(verified_amount,0)) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_digitization_emailcontent WHERE (tedit is null or tedit='')  and subcompanyid='" . $subcompanyid . "' and (verifywithbank is null or verifywithbank='' or verifywithbank='partial') and invoice_no='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$sscont = "select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - ifnull(verified_amount,0)) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_digitization_emailcontent WHERE sno='" . $shortbyvalue . "'";
	}
	$sscont_result = $conn->query($sscont);
	$emcontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($emcrow = $sscont_result->fetch_assoc()) {
			$emailbody = getemailbodybyid($conn, $emcrow['email_contentid']);
			$verifiedamount = $emcrow['verified_amount'];
			$emcontent[] = array('sno' => $emcrow['sno'], 'email_contentid' => $emcrow['email_contentid'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'clientid' => $emcrow['clientid'], 'clientname' => $emcrow['clientname'], 'invoice_no' => $emcrow['invoice_no'], 'invoice_date' => $emcrow['invoice_date'], 'invoice_amount' => $emcrow['invoice_amount'], 'paymentstatus' => $emcrow['paymentstatus'], 'transferamount_againstinvoice' => $emcrow['transferamount_againstinvoice'], 'transferamount' => $emcrow['transferamount'], 'verified_amount' => $emcrow['verified_amount'], 'balaceverifiedamount' => $emcrow['balaceverifiedamount'], 'transactionno' => $emcrow['transactionno'], 'transactiondate' => $emcrow['transactiondate'], 'description' => $emcrow['description'], 'emailfrom' => $emcrow['emailfrom'], 'emaildate' => $emcrow['emaildate'], 'verifywithbank' => $emcrow['verifywithbank'], 'emailpurpose' => $emcrow['emailpurpose'], 'emailbody' => $emailbody);
		}
	}
	return $emcontent;
}

function getbankdebitidetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$debitcontent = array();
		$i = 1;
		$qurt = "Select distinct accountid from tbl_master_account";
		$result_qt = $conn->query($qurt);
		if ($result_qt->num_rows > 0) {
			while ($qtrow = $result_qt->fetch_assoc()) {
				$sscont = "select sno, companyid, subcompanyid, projectid, accountno,account_id, bankname, valuedate, description, chequeno, debitamount FROM tbl_bankstatement WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (debitamount >0) and (reconciliation is null or reconciliation='') and account_id='" . $qtrow['accountid'] . "'";
				$sscont_result = $conn->query($sscont);

				if ($sscont_result->num_rows > 0) {

					while ($emcrow = $sscont_result->fetch_assoc()) {
						$classM = "classY_" . $i;
						$debitcontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'accountno' => $emcrow['accountno'], 'accountid' => $emcrow['account_id'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'bankclass' => $classM, 'accountclass' => 'accountclass_' . $qtrow['accountid']);
						$i++;
					}
				}

			}
		}


	}

	return $debitcontent;
}
function getbankdebitidetails22($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$debitcontent = array();
		$i = 1;
		$qurt = "Select distinct accountid from tbl_master_account";
		$result_qt = $conn->query($qurt);
		if ($result_qt->num_rows > 0) {
			while ($qtrow = $result_qt->fetch_assoc()) {
				$sscont = "select sno, companyid, subcompanyid, projectid, accountno,account_id, bankname, valuedate, description, chequeno, debitamount FROM tbl_bankstatement WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (debitamount >0) and (reconciliation is null or reconciliation='') and (status is null or status='') and account_id='" . $qtrow['accountid'] . "'";
				$sscont_result = $conn->query($sscont);

				if ($sscont_result->num_rows > 0) {

					while ($emcrow = $sscont_result->fetch_assoc()) {
						$classM = "classY_" . $i;
						$debitcontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'accountno' => $emcrow['accountno'], 'accountid' => $emcrow['account_id'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'bankclass' => $classM, 'accountclass' => 'accountclass_' . $qtrow['accountid']);
						$i++;
					}
				}
			}
		}
	}

	return $debitcontent;
}
function getbankdebitidetailsbyaccount($conn, $companyid, $subcompanyid, $projectid, $accountid)
{
	$debitcontent = array();
	$i = 1;
	$sscont = "select sno, companyid, subcompanyid, projectid, accountno,account_id, bankname, valuedate, description, chequeno, debitamount FROM tbl_bankstatement WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (debitamount >0) and (reconciliation is null or reconciliation='') and (status is null or status='') and account_id='" . $accountid . "'";
	$sscont_result = $conn->query($sscont);
	if ($sscont_result->num_rows > 0) {
		while ($emcrow = $sscont_result->fetch_assoc()) {
			$classM = "classY_" . $i;
			$debitcontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'accountno' => $emcrow['accountno'], 'accountid' => $emcrow['account_id'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'bankclass' => $classM, 'accountclass' => 'accountclass_' . $accountid);
			$i++;
		}
	}
	return $debitcontent;
}
function getexpensesdataforhelpmakebook($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='')";
	} else if ($shortby == "pendingbook_voiceinst") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and source='voiceinstruction' and (closetobook_status is null or closetobook_status='') and (closetobook_bankkeyid is null or closetobook_bankkeyid='')";
	} else if ($shortby == "pendingbookwithmissingpurpoe") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and (closetobook_status is null or closetobook_status='') and (purpose_code is null or purpose_code='')";
	} else if ($shortby == "missingdetails_forbook") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and (closetobook_status is null or closetobook_status='')";
	} else if ($shortby == "voiceinstid") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and source='voiceinstruction' and voiceinstid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and project_id='" . $projectid . "'  and sno='" . $shortbyvalue . "'";
	}
	$sscont_result = $conn->query($sscont);
	$expensescontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($exprow = $sscont_result->fetch_assoc()) {
			$expensescontent[] = array('sno' => $exprow['sno'], 'date' => $exprow['date'], 'dispdate' => $exprow['date'] != "" ? date('d-M-Y', strtotime($exprow['date'])) : "", 'country_id' => $exprow['country_id'], 'company_id' => $exprow['company_id'], 'subcompany_id' => $exprow['subcompany_id'], 'project_id' => $exprow['project_id'], 'accountname' => $exprow['accountname'], 'accountno' => $exprow['accountno'], 'account_id' => $exprow['account_id'], 'reference_no' => $exprow['reference_no'], 'request_no' => $exprow['request_no'], 'paymentmode' => $exprow['paymentmode'], 'chequeno' => $exprow['chequeno'], 'bankname' => $exprow['bankname'], 'paytoid' => $exprow['paytoid'], 'payto' => $exprow['payto'], 'beneficiaryname' => $exprow['beneficiaryname'], 'amount' => $exprow['amount'], 'tds' => $exprow['tds'], 'tds_amount' => $exprow['tds_amount'], 'total_amount' => $exprow['total_amount'], 'paymenttype' => $exprow['paymenttype'], 'categoryid' => $exprow['categoryid'], 'category' => $exprow['category'], 'sub_categoryid' => $exprow['sub_categoryid'], 'sub_category' => $exprow['sub_category'], 'purpose_code' => $exprow['purpose_code'], 'provided_code' => $exprow['provided_code'], 'provided_purpose' => $exprow['provided_purpose'], 'purpose' => $exprow['purpose'], 'description' => $exprow['description'], 'remarks' => $exprow['remarks'], 'tsno' => $exprow['tsno'], 'banksno' => $exprow['banksno'], 'status' => $exprow['status'], 'source' => $exprow['source'], 'loginid' => $exprow['loginid'], 'fileinfo' => $exprow['fileinfo'], 'audiofilepath' => $exprow['audiofilepath'], 'audiofilepath_digitized' => $exprow['audiofilepath_digitized'], 'filepath_digitized' => $exprow['filepath_digitized'], 'voiceinstid' => $exprow['voiceinstid'], 'filepath' => $exprow['filepath'], 'filepath2' => $exprow['filepath2'], 'srm_emailsno' => $exprow['srm_emailsno'], 'srm_attsno' => $exprow['srm_attsno'], 'closetobook_status' => $exprow['closetobook_status'], 'closetobook_bankkeyid' => $exprow['closetobook_bankkeyid'], 'tedit' => $exprow['tedit'], 'etdatetime' => $exprow['etdatetime'], 'remove_doc' => $exprow['remove_doc'], 'att_id' => $exprow['att_id']);
		}
	}
	return $expensescontent;
}
function getcompanyreplybydescription($conn, $companyid, $subcompanyid, $projectid, $accountid)
{
	$qry = "select CONCAT('Purpose: ', purpose, '<br> Person name: ', personname, '<br> Remarks: ', companyreply)  as companyreply, voiceremarkfile,ucase(groupdescription) as groupdescription,purpose,personname   from tblcompanyreplyunknown where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and account_id='" . $accountid . "' and source='company' order by sno desc";

	$result = $conn->query($qry);
	$returnval = array();
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$returnval[] = array("companyreply" => $row["companyreply"], "voiceremarkfile" => $row["voiceremarkfile"], "description" => $row["groupdescription"], "purpose" => $row["purpose"], "personname" => $row["personname"]);
		}
	}
	return $returnval;
}

function getbookdetails($conn, $companyid, $subcompanyid, $projectid, $accountid)
{
	$qry = "SELECT * FROM tbl_maintransaction  where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "'  and projectid='" . $projectid . "' and payfromaccountid='" . $accountid . "' and ifnull(reconciliation,'')='' and ifnull(reference_no,'')<>'' ";
	$result = $conn->query($qry);
	$returnval = array();
	if ($result->num_rows > 0) {
		$returnval = array("status" => "success");
	} else {
		$returnval = array("status" => "error");
	}
	return $returnval;
}

function getT3details($conn, $companyid, $subcompanyid, $projectid)
{
	$qry = "SELECT * FROM tbl_expencesinexcelsheet  where company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "'  and project_id='" . $projectid . "' and ifnull(reference_no,'')='' ";
	$result = $conn->query($qry);
	$returnval = array();
	if ($result->num_rows > 0) {
		$returnval = array("status" => "success");
	} else {
		$returnval = array("status" => "error");
	}
	return $returnval;
}

function getdepositdataforhelpmakebook($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_depositsinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='')";
	} else if ($shortby == "pendingbook_voiceinst") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_depositsinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and source='voiceinstruction' and (closetobook_status is null or closetobook_status='') and (closetobook_bankkeyid is null or closetobook_bankkeyid='')";
	} else if ($shortby == "pendingbookwithmissingpurpoe") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_depositsinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and (closetobook_status is null or closetobook_status='') and (purpose_code is null or purpose_code='')";
	} else if ($shortby == "missingdetails_forbook") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_depositsinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and (closetobook_status is null or closetobook_status='')";

		//  and (purpose_code is null or purpose_code='' or paytoid is null or paytoid='' or chequeno is null or chequeno='' or date is null or date='')

	} else if ($shortby == "voiceinstid") {
		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_depositsinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and source='voiceinstruction' and voiceinstid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {

		$sscont = "select sno, date, country_id, company_id, subcompany_id, project_id, accountname, accountno, account_id, reference_no, request_no, paymentmode, chequeno, bankname, ifnull(paytoid,'') as paytoid, payto, beneficiaryname, amount, tds, tds_amount, total_amount, paymenttype, categoryid, category, sub_categoryid, sub_category, purpose_code, provided_code, provided_purpose, purpose, description, remarks, tsno, banksno, status, source, loginid, fileinfo, audiofilepath, audiofilepath_digitized, filepath_digitized, voiceinstid, filepath, filepath2, srm_emailsno, srm_attsno, closetobook_status, closetobook_bankkeyid, tedit, etdatetime, remove_doc, att_id FROM tbl_depositsinexcelsheet WHERE (tedit is null or tedit='') and company_id = '" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "'  and sno='" . $shortbyvalue . "'";
	}
	$sscont_result = $conn->query($sscont);
	$expensescontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($exprow = $sscont_result->fetch_assoc()) {
			$expensescontent[] = array('sno' => $exprow['sno'], 'date' => $exprow['date'], 'dispdate' => date('d-M-Y', strtotime($exprow['date'])), 'country_id' => $exprow['country_id'], 'company_id' => $exprow['company_id'], 'subcompany_id' => $exprow['subcompany_id'], 'project_id' => $exprow['project_id'], 'accountname' => $exprow['accountname'], 'accountno' => $exprow['accountno'], 'account_id' => $exprow['account_id'], 'reference_no' => $exprow['reference_no'], 'request_no' => $exprow['request_no'], 'paymentmode' => $exprow['paymentmode'], 'chequeno' => $exprow['chequeno'], 'bankname' => $exprow['bankname'], 'paytoid' => $exprow['paytoid'], 'payto' => $exprow['payto'], 'beneficiaryname' => $exprow['beneficiaryname'], 'amount' => $exprow['amount'], 'tds' => $exprow['tds'], 'tds_amount' => $exprow['tds_amount'], 'total_amount' => $exprow['total_amount'], 'paymenttype' => $exprow['paymenttype'], 'categoryid' => $exprow['categoryid'], 'category' => $exprow['category'], 'sub_categoryid' => $exprow['sub_categoryid'], 'sub_category' => $exprow['sub_category'], 'purpose_code' => $exprow['purpose_code'], 'provided_code' => $exprow['provided_code'], 'provided_purpose' => $exprow['provided_purpose'], 'purpose' => $exprow['purpose'], 'description' => $exprow['description'], 'remarks' => $exprow['remarks'], 'tsno' => $exprow['tsno'], 'banksno' => $exprow['banksno'], 'status' => $exprow['status'], 'source' => $exprow['source'], 'loginid' => $exprow['loginid'], 'fileinfo' => $exprow['fileinfo'], 'audiofilepath' => $exprow['audiofilepath'], 'audiofilepath_digitized' => $exprow['audiofilepath_digitized'], 'filepath_digitized' => $exprow['filepath_digitized'], 'voiceinstid' => $exprow['voiceinstid'], 'filepath' => $exprow['filepath'], 'filepath2' => $exprow['filepath2'], 'srm_emailsno' => $exprow['srm_emailsno'], 'srm_attsno' => $exprow['srm_attsno'], 'closetobook_status' => $exprow['closetobook_status'], 'closetobook_bankkeyid' => $exprow['closetobook_bankkeyid'], 'tedit' => $exprow['tedit'], 'etdatetime' => $exprow['etdatetime'], 'remove_doc' => $exprow['remove_doc'], 'att_id' => $exprow['att_id']);
		}
	}
	return $expensescontent;
}


function getreconciledata($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sscont = "select sno,valuedate,chequeno,debitamount FROM tbl_bankstatement WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (debitamount >0) and (reconciliation is null or reconciliation='')";
	}
	$sscont_result = $conn->query($sscont);
	$expensescontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($emcrow = $sscont_result->fetch_assoc()) {
			$sscontbook = "select bookdate,transactionno,reference_no,book_amount FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and transactionno='" . $emcrow['chequeno'] . "' and book_amount='" . $emcrow['debitamount'] . "' and (reconciliation is null or reconciliation='')";
			$sscontbook_result = $conn->query($sscontbook);
			if ($sscontbook_result->num_rows > 0) {
				while ($emcrowbook = $sscontbook_result->fetch_assoc()) {
					$expensescontent[] = array('sno' => $emcrow['sno'], 'valuedate' => $emcrow['valuedate'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'bookdate' => $emcrowbook['bookdate'], 'transactionno' => $emcrowbook['transactionno'], 'referenceno' => $emcrowbook['reference_no'], 'bookamount' => $emcrowbook['book_amount']);
				}
			}
		}
	}
	return $expensescontent;
}
function Getreconcileddata($conn, $companyid, $subcompanyid, $projectid, $accountid, $shortby, $shortbyvalue)
{
	if ($shortby == "incomplet") {
		$qryr = "select tsno, companyid, subcompanyid, projectid,  reference_no, bookdate, book_amount, beforeedit_amount, transaction_type, payto, paymenttype, beneficierie, transactionno,reconciliation,reconciliation_banksno,value_date,groupkeyid,book_source,referenceno_clb,description from tbl_maintransaction where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and payfromaccountid='" . $shortbyvalue . "'";
	} else if ($shortby == "payto") {
		$qryr = "select tsno, companyid, subcompanyid, projectid,  reference_no, bookdate, book_amount, beforeedit_amount, transaction_type, payto, paymenttype, beneficierie, transactionno,reconciliation,reconciliation_banksno,value_date,groupkeyid,book_source,referenceno_clb,description from tbl_maintransaction where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and payto='" . $shortbyvalue . "' order by bookdate";
	} else if ($shortby == "unknown") {
		$qryr = "select tsno, companyid, subcompanyid, projectid,  reference_no, bookdate, book_amount, beforeedit_amount, transaction_type, payto, paymenttype, beneficierie, transactionno,reconciliation,reconciliation_banksno,value_date,groupkeyid,book_source,referenceno_clb,description from tbl_maintransaction where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and payfromaccountid='" . $shortbyvalue . "' and paymenttype='Unknown'";
	}
	$resultr = $conn->query($qryr);
	if ($resultr->num_rows > 0) {
		while ($rcrow = $resultr->fetch_assoc()) {
			$reconcileddata[] = array('tsno' => $rcrow['tsno'], 'reference_no' => $rcrow['reference_no'], 'bookdate' => date('d-m-Y', strtotime($rcrow['bookdate'])), 'book_amount' => $rcrow['book_amount'], 'transaction_type' => $rcrow['transaction_type'], 'transactionno' => $rcrow['transactionno'], 'payto' => $rcrow['payto'], 'paymenttype' => $rcrow['paymenttype'], 'beneficierie' => $rcrow['beneficierie'], 'reconciliationbanksno' => $rcrow['reconciliation_banksno'], 'valuedate' => $rcrow['value_date'], 'groupkeyid' => $rcrow['groupkeyid'], 'booksource' => $rcrow['book_source'], 'referenceno_clb' => $rcrow['referenceno_clb'], 'description' => $rcrow['description']);
		}

	}

	return $reconcileddata;
}

function Getpaytolistreconcileddata($conn, $companyid, $subcompanyid, $projectid, $accountid)
{
	$qryp = "Select distinct payto,count(payto) as paytocount  from tbl_maintransaction where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and payfromaccountid='" . $accountid . "' group by payto order by paytocount desc";
	$resultp = $conn->query($qryp);
	if ($resultp->num_rows > 0) {
		while ($pcrow = $resultp->fetch_assoc()) {
			$paytolist[] = array('payto' => $pcrow['payto']);
		}
	}

	return $paytolist;
}

function getexpensesdataforhelpmakebooksortbyname($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$expensescontent = array();
	$evenoodno = 1;
	$checkgroup = "";
	if ($shortby == "nameX") {
		$qrysort = "Select distinct payto from tbl_expencesinexcelsheet";
		$ssort_result = $conn->query($qrysort);
		if ($ssort_result->num_rows > 0) {
			while ($sortcrow = $ssort_result->fetch_assoc()) {
				$sscont = "select sno, company_id as companyid, subcompany_id as subcompanyid, project_id as projectid, accountno, bankname, date as valuedate, paymentmode,description, chequeno, payto, round(total_amount) as debitamount,purpose,purpose_code FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and payto='" . $sortcrow['payto'] . "'";
				$sscont_result = $conn->query($sscont);

				if ($sscont_result->num_rows > 0) {
					$checkgroup = "Y";
					while ($emcrow = $sscont_result->fetch_assoc()) {

						$expensescontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'paymentmode' => $emcrow['paymentmode'], 'payto' => $emcrow['payto'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'purpose' => $emcrow['purpose'], 'purposecode' => $emcrow['purpose_code'], 'evenoddclass' => 'class_' . $evenoodno, 'checkgroup' => $checkgroup, 'bankaccountno' => $emcrow['accountno']);
						$checkgroup = "";
					}
					$evenoodno = $evenoodno + 1;
					$checkgroup = "Y";
				}

			}

		}

	} else if ($shortby == "cheque") {

		$qrysort = "Select distinct chequeno,accountno from tbl_expencesinexcelsheet";
		$ssort_result = $conn->query($qrysort);
		if ($ssort_result->num_rows > 0) {
			while ($sortcrow = $ssort_result->fetch_assoc()) {
				$sscont = "select sno, company_id as companyid, subcompany_id as subcompanyid, project_id as projectid, accountno, bankname, date as valuedate, paymentmode,description, chequeno, payto, round(total_amount) as debitamount,purpose,purpose_code FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and chequeno='" . $sortcrow['chequeno'] . "' and accountno='" . $sortcrow['accountno'] . "'";
				$sscont_result = $conn->query($sscont);

				if ($sscont_result->num_rows > 0) {
					$checkgroup = "Y";
					while ($emcrow = $sscont_result->fetch_assoc()) {

						$expensescontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'paymentmode' => $emcrow['paymentmode'], 'payto' => $emcrow['payto'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'purpose' => $emcrow['purpose'], 'purposecode' => $emcrow['purpose_code'], 'evenoddclass' => 'class_' . $evenoodno, 'checkgroup' => $checkgroup, 'bankaccountno' => $emcrow['accountno']);
						$checkgroup = "";
					}
					$evenoodno = $evenoodno + 1;
					$checkgroup = "Y";
				}

			}

		}

	} else if ($shortby == "account") {
		$qrysort = "Select distinct accountno from tbl_expencesinexcelsheet";
		$ssort_result = $conn->query($qrysort);
		if ($ssort_result->num_rows > 0) {
			while ($sortcrow = $ssort_result->fetch_assoc()) {
				$qrysortX = "Select distinct chequeno from tbl_expencesinexcelsheet where accountno='" . $sortcrow['accountno'] . "'";
				$ssort_resultX = $conn->query($qrysortX);
				if ($ssort_resultX->num_rows > 0) {
					while ($sortcrowX = $ssort_resultX->fetch_assoc()) {
						$sscont = "select sno, company_id as companyid, subcompany_id as subcompanyid, project_id as projectid, accountno, bankname, date as valuedate, paymentmode,description, chequeno, payto, round(total_amount) as debitamount,purpose,purpose_code FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and chequeno='" . $sortcrowX['chequeno'] . "' and accountno='" . $sortcrow['accountno'] . "'";
						$sscont_result = $conn->query($sscont);

						if ($sscont_result->num_rows > 0) {
							$checkgroup = "Y";
							while ($emcrow = $sscont_result->fetch_assoc()) {
								$expensescontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'paymentmode' => $emcrow['paymentmode'], 'payto' => $emcrow['payto'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'purpose' => $emcrow['purpose'], 'purposecode' => $emcrow['purpose_code'], 'evenoddclass' => 'class_' . $evenoodno, 'checkgroup' => $checkgroup, 'bankaccountno' => $emcrow['accountno']);
								$checkgroup = "";
							}
							$evenoodno = $evenoodno + 1;
							$checkgroup = "Y";

						}
					}

				}
			}
		}
	} else if ($shortby == "name") {
		$qrysort = "Select distinct payto from tbl_expencesinexcelsheet";
		$ssort_result = $conn->query($qrysort);
		if ($ssort_result->num_rows > 0) {
			while ($sortcrow = $ssort_result->fetch_assoc()) {
				$qrysortX = "Select distinct accountno from tbl_expencesinexcelsheet where payto='" . $sortcrow['payto'] . "'";
				$ssort_resultX = $conn->query($qrysortX);
				if ($ssort_resultX->num_rows > 0) {
					while ($sortcrowX = $ssort_resultX->fetch_assoc()) {
						$qrysortZ = "Select distinct chequeno from tbl_expencesinexcelsheet where accountno='" . $sortcrowX['accountno'] . "' and payto='" . $sortcrow['payto'] . "'";
						$ssort_resultZ = $conn->query($qrysortZ);
						if ($ssort_resultZ->num_rows > 0) {
							while ($sortcrowZ = $ssort_resultZ->fetch_assoc()) {

								$sscont = "select sno, company_id as companyid, subcompany_id as subcompanyid, project_id as projectid, accountno, bankname, date as valuedate, paymentmode,description, chequeno, payto, round(total_amount) as debitamount,purpose,purpose_code FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and chequeno='" . $sortcrowZ['chequeno'] . "' and accountno='" . $sortcrowX['accountno'] . "'and payto='" . $sortcrow['payto'] . "'";
								$sscont_result = $conn->query($sscont);

								if ($sscont_result->num_rows > 0) {
									$checkgroup = "Y";
									while ($emcrow = $sscont_result->fetch_assoc()) {
										$expensescontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'paymentmode' => $emcrow['paymentmode'], 'payto' => $emcrow['payto'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'purpose' => $emcrow['purpose'], 'purposecode' => $emcrow['purpose_code'], 'evenoddclass' => 'class_' . $evenoodno, 'checkgroup' => $checkgroup, 'bankaccountno' => $emcrow['accountno']);
										$checkgroup = "";
									}
									$evenoodno = $evenoodno + 1;
									$checkgroup = "Y";

								}



							}
						}

					}

				}
			}
		}
	}

	return $expensescontent;
}

function getdatafromexpensetableforbook($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$sscont = "select sno, company_id as companyid, subcompany_id as subcompanyid, project_id as projectid, accountno, bankname, date as valuedate, paymentmode,description, chequeno, payto, round(total_amount) as debitamount,purpose,purpose_code FROM tbl_expencesinexcelsheet WHERE (tedit is null or tedit='') and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and (total_amount >0) and (status is null or status='') and chequeno='" . $shortby . "'";
	$sscont_result = $conn->query($sscont);
	if ($sscont_result->num_rows > 0) {
		$checkgroup = "Y";
		while ($emcrow = $sscont_result->fetch_assoc()) {
			$expensescontent[] = array('sno' => $emcrow['sno'], 'companyid' => $emcrow['companyid'], 'subcompanyid' => $emcrow['subcompanyid'], 'projectid' => $emcrow['projectid'], 'paymentmode' => $emcrow['paymentmode'], 'payto' => $emcrow['payto'], 'bankname' => $emcrow['bankname'], 'valuedate' => $emcrow['valuedate'], 'description' => $emcrow['description'], 'chequeno' => $emcrow['chequeno'], 'debitamount' => $emcrow['debitamount'], 'purpose' => $emcrow['purpose'], 'purposecode' => $emcrow['purpose_code'], 'bankaccountno' => $emcrow['accountno']);
		}
	}
	return $expensescontent;

}

function getfindstringforreconcile($conn, $companyid, $subcompanyid, $projectid, $accountid, $shortby, $value1, $value2, $value3)
{

	if ($shortby == 'chequeno') {
		$sscont = "select sno, bookdate , reference_no,transactionno,transaction_type,book_amount,payto,description,payfrombankname,payfromaccountno,payfromaccountid FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (book_amount >0) and (reconciliation is null or reconciliation='') and transactionno='" . $value1 . "'";
		$sscont22 = "select sno, bookdate , reference_no,transactionno,transaction_type,book_amount,payto,description,payfrombankname,payfromaccountno,payfromaccountid FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (book_amount >0) and (reconciliation is null or reconciliation='') and transactionno<>'" . $value1 . "'";
	} else if ($shortby == 'dateandamountanddescription') {
		$sscont = "select sno, bookdate , reference_no,transactionno,transaction_type,book_amount,payto,description,payfrombankname,payfromaccountno,payfromaccountid FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (book_amount >0) and (reconciliation is null or reconciliation='') and bookdate>='" . $value1 . "' and book_amount='" . $value2 . "'";
		$sscont22 = "select sno, bookdate , reference_no,transactionno,transaction_type,book_amount,payto,description,payfrombankname,payfromaccountno,payfromaccountid FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (book_amount >0) and (reconciliation is null or reconciliation='')";
	} else if ($shortby == 'dateandamount') {
		$sscont = "select sno, bookdate , reference_no,transactionno,transaction_type,book_amount,payto,description,payfrombankname,payfromaccountno,payfromaccountid FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (book_amount >0) and (reconciliation is null or reconciliation='') and bookdate>='" . $value1 . "' and book_amount='" . $value2 . "'";
		$sscont22 = "select sno, bookdate , reference_no,transactionno,transaction_type,book_amount,payto,description,payfrombankname,payfromaccountno,payfromaccountid FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (book_amount >0) and (reconciliation is null or reconciliation='')";
	}
	$sscont_result = $conn->query($sscont);
	$expensescontent = array();
	$i = 1;
	if ($sscont_result->num_rows > 0) {

		while ($emcrow = $sscont_result->fetch_assoc()) {
			$classM = "classX_" . $i;

			if ($shortby == 'chequeno') {
				$expensescontent[] = array('sno' => $emcrow['sno'], 'bookdate' => $emcrow['bookdate'], 'referenceno' => $emcrow['reference_no'], 'transactionno' => $emcrow['transactionno'], 'transactiontype' => $emcrow['transaction_type'], 'bookamount' => $emcrow['book_amount'], 'payto' => $emcrow['payto'], 'description' => $emcrow['description'], 'payfrombankname' => $emcrow['payfrombankname'], 'payfromaccountno' => $emcrow['payfromaccountno'], 'payfromaccountid' => $emcrow['payfromaccountid'], 'classM' => $classM, 'findvalue' => 'Y');
				$i = $i + 1;
			} else if ($shortby == 'dateandamountanddescription') {
				if (str_contains($value3, $emcrow['payto'])) {
					$expensescontent[] = array('sno' => $emcrow['sno'], 'bookdate' => $emcrow['bookdate'], 'referenceno' => $emcrow['reference_no'], 'transactionno' => $emcrow['transactionno'], 'transactiontype' => $emcrow['transaction_type'], 'bookamount' => $emcrow['book_amount'], 'payto' => $emcrow['payto'], 'description' => $emcrow['description'], 'payfrombankname' => $emcrow['payfrombankname'], 'payfromaccountno' => $emcrow['payfromaccountno'], 'payfromaccountid' => $emcrow['payfromaccountid'], 'classM' => $classM, 'findvalue' => 'Y');
					$i = $i + 1;
				}
			} else if ($shortby == 'dateandamount') {
				$expensescontent[] = array('sno' => $emcrow['sno'], 'bookdate' => $emcrow['bookdate'], 'referenceno' => $emcrow['reference_no'], 'transactionno' => $emcrow['transactionno'], 'transactiontype' => $emcrow['transaction_type'], 'bookamount' => $emcrow['book_amount'], 'payto' => $emcrow['payto'], 'description' => $emcrow['description'], 'payfrombankname' => $emcrow['payfrombankname'], 'payfromaccountno' => $emcrow['payfromaccountno'], 'payfromaccountid' => $emcrow['payfromaccountid'], 'classM' => $classM, 'findvalue' => 'Y');
				$i = $i + 1;
			}



		}
	}

	$sscont_result22 = $conn->query($sscont22);

	if ($sscont_result22->num_rows > 0) {
		$i = $i + 1;
		while ($emcrow22 = $sscont_result22->fetch_assoc()) {
			$classM = "classX_" . $i;
			$expensescontent[] = array('sno' => $emcrow22['sno'], 'bookdate' => $emcrow22['bookdate'], 'referenceno' => $emcrow22['reference_no'], 'transactionno' => $emcrow22['transactionno'], 'transactiontype' => $emcrow22['transaction_type'], 'bookamount' => $emcrow22['book_amount'], 'payto' => $emcrow22['payto'], 'description' => $emcrow22['description'], 'payfrombankname' => $emcrow22['payfrombankname'], 'payfromaccountno' => $emcrow22['payfromaccountno'], 'payfromaccountid' => $emcrow22['payfromaccountid'], 'classM' => $classM, 'findvalue' => '');
			$i = $i + 1;
		}
	}

	return $expensescontent;

}

function getreconstructbookcontent($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sscont = "select invoice_id, invoice_type, invoice_date, invoice_no, invoice_forcontractorid, invoice_description, invoice_amount,comparewith_email,client_name FROM tbl_invoices WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (reconciliation is null or reconciliation='')";
	}
	// else if($shortby=="withoutverifywithbank"){
	// 	$sscont="select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - verified_amount) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_digitization_emailcontent WHERE (tedit is null or tedit='')  and subcompanyid='".$subcompanyid."' and (verifywithbank is null or verifywithbank='' or verifywithbank='partial')";
	// }else if($shortby=="withoutverifywithbankofinvoiceno"){
	// 	$sscont="select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - ifnull(verified_amount,0)) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_digitization_emailcontent WHERE (tedit is null or tedit='')  and subcompanyid='".$subcompanyid."' and (verifywithbank is null or verifywithbank='' or verifywithbank='partial') and invoice_no='".$shortbyvalue."'";
	// }else if($shortby=="sno"){
	// 	$sscont="select sno, email_contentid, companyid, subcompanyid, projectid, clientid, clientname, invoice_no, invoice_date, invoice_amount, paymentstatus, ifnull(transferamunt_againstinvoice,0) as transferamount_againstinvoice, transferamount, ifnull(verified_amount,0) as verified_amount, (transferamount - ifnull(verified_amount,0)) as balaceverifiedamount, transactionno, transactiondate, description, emailfrom, emaildate, verifywithbank FROM tbl_digitization_emailcontent WHERE sno='".$shortbyvalue."'";
	// }
	$sscont_result = $conn->query($sscont);
	$emcontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($emcrow = $sscont_result->fetch_assoc()) {
			// $emailbody=getemailbodybyid($conn,$emcrow['email_contentid']);
			// $verifiedamount = $emcrow['verified_amount'];
			$getemailpurpose = getemailpurposefromdigitizaedemail($conn, $emcrow['comparewith_email']);
			$emcontent[] = array('invoice_id' => $emcrow['invoice_id'], 'invoice_type' => $emcrow['invoice_type'], 'invoice_no' => $emcrow['invoice_no'], 'invoice_forcontractorid' => $emcrow['invoice_forcontractorid'], 'invoice_description' => $emcrow['invoice_description'], 'invoice_amount' => $emcrow['invoice_amount'], 'date' => date('d-M-Y', strtotime($emcrow['invoice_date'])), 'emailpurpose' => $getemailpurpose, 'emailid' => $emcrow['comparewith_email'], 'clientname' => $emcrow['client_name']);
		}
	}
	return $emcontent;
}

function getemailtodbemail($conn, $sendtoemail, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "'";
	} else if ($shortby == "unverified") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "' and Status='0'";
	} else if ($shortby == "verifyed") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "' and Status='1'";
	} else if ($shortby == "invoiceunverified") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "' and Status='0' and (update_emailpurpose='" . $shortbyvalue . "' or update_emailpurpose is null or update_emailpurpose='' )";
	}
	$emq_result = $conn->query($emq);
	$emaillist = array();
	if ($emq_result->num_rows > 0) {
		while ($emrow = $emq_result->fetch_assoc()) {
			$emaillist[] = array('id' => $emrow['ID'], 'emailfrom' => $emrow['EmailFrom'], 'emailperson' => $emrow['EmailFromP'], 'toemail' => $emrow['EmailTo'], 'ccemail' => $emrow['Emailcc'], 'email_date' => $emrow['DateE'], 'DateDb' => $emrow['DateDb'], 'Status' => $emrow['Status'], 'Type' => $emrow['Type'], 'Del' => $emrow['Del'], 'email_subject' => $emrow['Subject'], 'email_body' => $emrow['Message'], 'email_body_html' => $emrow['Message_html']);
		}
	}
	return $emaillist;
}
function getemailtodbemailforsorting($conn, $sendtoemail, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html,update_emailpurpose, shortedfilename FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "'";
	} else if ($shortby == "unverified") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html,update_emailpurpose, shortedfilename FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "' and docsort_Status='0'";
	} else if ($shortby == "verifyed") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html,update_emailpurpose, shortedfilename FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "' and Status='1'";
	} else if ($shortby == "documentshort") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html,update_emailpurpose, shortedfilename FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "' and docsort_Status='1' and email_digitizationstatus=0";
	} else if ($shortby == "shortedpurpose") {
		$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html,update_emailpurpose, shortedfilename FROM emailtodb_email WHERE EmailTo='" . $sendtoemail . "' and update_emailpurpose='" . $shortbyvalue . "'";
	}
	$emq_result = $conn->query($emq);
	$emaillist = array();
	if ($emq_result->num_rows > 0) {
		while ($emrow = $emq_result->fetch_assoc()) {
			$emaillist[] = array('id' => $emrow['ID'], 'emailfrom' => $emrow['EmailFrom'], 'emailperson' => $emrow['EmailFromP'], 'toemail' => $emrow['EmailTo'], 'ccemail' => $emrow['Emailcc'], 'email_date' => $emrow['DateE'], 'DateDb' => $emrow['DateDb'], 'Status' => $emrow['Status'], 'Type' => $emrow['Type'], 'Del' => $emrow['Del'], 'email_subject' => $emrow['Subject'], 'email_body' => $emrow['Message'], 'email_body_html' => $emrow['Message_html'], 'update_emailpurpose' => $emrow['update_emailpurpose'], 'shortedfilename' => $emrow['shortedfilename']);
		}
	}
	return $emaillist;
}

//Rohit
function getemailtodbemailforsorting_new($conn, $companyid, $subcompanyid, $projectid)
{
	$emq = "select E.ID,E.EmailFromP,E.EmailFrom,E. EmailTo,E.Emailcc,date_format(E.DateE,'%d-%m-%Y') as DateE, date_format(E.DateDb,'%d-%m-%Y') as DateDb, E.Status, E.Type, E.Del,E.Subject,E.Message,E.Message_html,E.update_emailpurpose, E.shortedfilename,E.hasattach,ifnull(group_concat(concat(A.ID,'?',A.Filename) separator '|'),'') as attachments,E.DateRead,E.ID,E.Emailcc,E.EmailTo from emailtodb_email E LEFT JOIN emailtodb_attach A ON A.IDEmail=E.ID  WHERE Del=0 and ifnull(A.category,'')='' and ifnull(A.filestatus,'') ='' and E.companyid='" . $companyid . "' and E.subcompanyid='" . $subcompanyid . "' and E.projectid='" . $projectid . "' and ifnull(E.cat_status,'')='' group by E.EmailFrom,E.DateE,E.Subject,E.Message_html order by E.ID desc";
	$emq_result = $conn->query($emq);
	$emaillist = array();
	if ($emq_result->num_rows > 0) {
		while ($emrow = $emq_result->fetch_assoc()) {
			$emaillist[] = array('id' => $emrow['ID'], 'emailfrom' => $emrow['EmailFrom'], 'emailperson' => $emrow['EmailFromP'], 'toemail' => $emrow['EmailTo'], 'ccemail' => $emrow['Emailcc'], 'email_date' => $emrow['DateE'], 'DateDb' => $emrow['DateDb'], 'Status' => $emrow['Status'], 'Type' => $emrow['Type'], 'Del' => $emrow['Del'], 'email_subject' => $emrow['Subject'], 'email_body' => $emrow['Message'], 'email_body_html' => $emrow['Message_html'], 'update_emailpurpose' => $emrow['update_emailpurpose'], 'shortedfilename' => $emrow['shortedfilename'], 'hasattach' => $emrow['hasattach'], 'attachments' => $emrow['attachments']);
		}
	}
	return $emaillist;

}

//Rohit
function getemailtodbemailforsortingdigitization($conn, $companyid, $subcompanyid, $projectid, $shortedpurpose)
{
	$emq = "select e.ID as email_id,a.ID, e.EmailFrom, e.EmailFromP, e.EmailTo, e.Emailcc, date_format(e.DateE,'%d-%m-%Y') as DateE, date_format(e.DateDb,'%d-%m-%Y') as DateDb, e.Status, e.Type, e.Del, e.Subject, e.Message, e.Message_html,ifnull(e.hasattach,0) as hasattach,a.category as update_emailpurpose, a.filename as shortedfilename FROM emailtodb_email e left join emailtodb_attach a on e.ID=a.IDemail WHERE e.Del=0 and (a.category = '{$shortedpurpose}' or e.cat_status like '%{$shortedpurpose}%')  and e.companyid='{$companyid}' and e.subcompanyid='{$subcompanyid}' and e.projectid='{$projectid}'  and (case when ifnull(e.hasattach,0) =1 then ifnull(a.subcategory,'') ='' else e.email_digitizationstatus='0' end )";
	$emq_result = $conn->query($emq);
	$emaillist = array();
	if ($emq_result->num_rows > 0) {
		while ($emrow = $emq_result->fetch_assoc()) {
			$emaillist[] = array('eid' => $emrow['email_id'], 'id' => $emrow['ID'], 'emailfrom' => $emrow['EmailFrom'], 'emailperson' => $emrow['EmailFromP'], 'toemail' => $emrow['EmailTo'], 'ccemail' => $emrow['Emailcc'], 'email_date' => $emrow['DateE'], 'email_datedsp' => date('d-M-Y', strtotime($emrow['DateE'])), 'DateDb' => $emrow['DateDb'], 'Status' => $emrow['Status'], 'Type' => $emrow['Type'], 'Del' => $emrow['Del'], 'email_subject' => $emrow['Subject'], 'email_body' => $emrow['Message'], 'email_body_html' => $emrow['Message_html'], 'hasattach' => $emrow['hasattach'], 'update_emailpurpose' => $emrow['update_emailpurpose'], 'shortedfilename' => $emrow['shortedfilename']);
		}
	}
	return $emaillist;
}

//Rohit
function getemailsubcategorizeddigitization($conn, $companyid, $subcompanyid, $projectid, $shortedpurpose)
{
	$emq = "select e.ID as email_id,a.ID, e.EmailFrom, e.EmailFromP, e.EmailTo, e.Emailcc, date_format(e.DateE,'%d-%m-%Y') as DateE, date_format(e.DateDb,'%d-%m-%Y') as DateDb, e.Status, e.Type, e.Del, e.Subject, e.Message, e.Message_html,ifnull(e.hasattach,0) as hasattach,a.category as update_emailpurpose, a.filename as shortedfilename FROM emailtodb_email e left join emailtodb_attach a on e.ID=a.IDemail WHERE e.Del=0 and a.subcategory = '{$shortedpurpose}'  and e.companyid='{$companyid}' and e.subcompanyid='{$subcompanyid}' and e.projectid='{$projectid}'  and ifnull(a.subcategory,'') <>'' and ifnull(a.filestatus,'')=''";
	$emq_result = $conn->query($emq);
	$emaillist = array();
	if ($emq_result->num_rows > 0) {
		while ($emrow = $emq_result->fetch_assoc()) {
			$emaillist[] = array('eid' => $emrow['email_id'], 'id' => $emrow['ID'], 'emailfrom' => $emrow['EmailFrom'], 'emailperson' => $emrow['EmailFromP'], 'toemail' => $emrow['EmailTo'], 'ccemail' => $emrow['Emailcc'], 'email_date' => $emrow['DateE'], 'email_datedsp' => date('d-M-Y', strtotime($emrow['DateE'])), 'DateDb' => $emrow['DateDb'], 'Status' => $emrow['Status'], 'Type' => $emrow['Type'], 'Del' => $emrow['Del'], 'email_subject' => $emrow['Subject'], 'email_body' => $emrow['Message'], 'email_body_html' => $emrow['Message_html'], 'hasattach' => $emrow['hasattach'], 'update_emailpurpose' => $emrow['update_emailpurpose'], 'shortedfilename' => $emrow['shortedfilename']);
		}
	}
	return $emaillist;
}


//Rohit
function getemailtodbemailforsortingfordigitiz($conn, $companyid, $subcompanyid, $projectid)
{
	$emq = "select purpose,count(id) as countsno from(SELECT p.id,p.purpose,e.ID as IDEmail,e.companyid,e.subcompanyid,e.projectid,ifnull(e.hasattach,0) as hasattach, '' as subcategory,e.email_digitizationstatus FROM tbl_emailcontants_purposemaster p left join emailtodb_email e on (e.cat_status like concat('%',p.purpose,'%')) where e.Del=0 union all SELECT p.id,p.purpose,a.IDEmail as IDEmail,e.companyid,e.subcompanyid,e.projectid,1 as hasattach, ifnull(a.subcategory,'') as subcategory,'' as email_digitizationstatus FROM tbl_emailcontants_purposemaster p left join emailtodb_attach a on (a.category_id=p.id) join emailtodb_email e on a.IDemail=e.ID where e.Del=0)cats where IDEmail is not null and companyid='{$companyid}' and subcompanyid='{$subcompanyid}' and projectid='{$projectid}' and (case when ifnull(hasattach,0) =1 then ifnull(subcategory,'') ='' else email_digitizationstatus='0' end ) group by purpose";
	$emq_result = $conn->query($emq);
	$emaillist = array();
	if ($emq_result->num_rows > 0) {
		while ($emrow = $emq_result->fetch_assoc()) {
			$emaillist[] = array('emailpurpose' => $emrow['purpose'], 'countsno' => $emrow['countsno']);
		}
	}
	return $emaillist;
}

//Rohit
function getemailsubcategorized($conn, $companyid, $subcompanyid, $projectid)
{
	$emq = "select a.subcategory,count(a.id) as countsno from emailtodb_attach a join emailtodb_email e on a.IDemail=e.ID where ifnull(a.subcategory,'')<>'' and ifnull(a.filestatus,'')='' and e.companyid={$companyid} and e.subcompanyid={$subcompanyid} and e.projectid={$projectid} group by a.subcategory";
	$emq_result = $conn->query($emq);
	$emaillist = array();
	if ($emq_result->num_rows > 0) {
		while ($emrow = $emq_result->fetch_assoc()) {
			$emaillist[] = array('emailpurpose' => $emrow['subcategory'], 'countsno' => $emrow['countsno']);
		}
	}
	return $emaillist;
}

function getemailattachement($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "emailid") {
		$ematt = "select ID, IDEmail, FileNameOrg, Filename, status, page, filestatus FROM emailtodb_attach WHERE (status is null or status='') and IDEmail='" . $shortbyvalue . "'";
	}
	$ematt_result = $conn->query($ematt) or die($conn->error);
	$emattlist = array();
	if ($ematt_result->num_rows > 0) {
		while ($attrow = $ematt_result->fetch_assoc()) {
			$emattlist[] = array('id' => $attrow['ID'], 'emailid' => $attrow['IDEmail'], 'filename_org' => $attrow['FileNameOrg'], 'filename' => $attrow['Filename'], 'status' => $attrow['status'], 'page' => $attrow['page'], 'filestatus' => $attrow['filestatus']);
		}
	}
	return $emattlist;
}

function getreconciledentryinfo($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "unrequested") {
		$reconq = "select sno, countryid, cmp_id, subcmp_id, creditto_projectid, creditto_accountid, creditfrom_accountid, reciveddate, transactionno, creditamount, creditpurposecode, creditpurpose, bankdescription, description, beneficeryname, statmentsno, booksno, invoicesno, emailsno, invoiceno, requestno, requeststatus FROM tbl_credittransaction_reconciled WHERE (Tedit is null or Tedit='') and  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and (requeststatus is null or requeststatus='')";
	} else if ($shortby == "sno") {
		$reconq = "select sno, countryid, cmp_id, subcmp_id, creditto_projectid, creditto_accountid, creditfrom_accountid, reciveddate, transactionno, creditamount, creditpurposecode, creditpurpose, bankdescription, description, beneficeryname, statmentsno, booksno, invoicesno, emailsno, invoiceno, requestno, requeststatus FROM tbl_credittransaction_reconciled WHERE sno='" . $shortbyvalue . "'";
	}
	$reconq_result = $conn->query($reconq);
	$reclist = array();
	if ($reconq_result->num_rows > 0) {
		while ($recrow = $reconq_result->fetch_assoc()) {
			$reclist[] = array('sno' => $recrow['sno'], 'countryid' => $recrow['countryid'], 'cmp_id' => $recrow['cmp_id'], 'subcmp_id' => $recrow['subcmp_id'], 'creditto_projectid' => $recrow['creditto_projectid'], 'creditto_accountid' => $recrow['creditto_accountid'], 'creditfrom_accountid' => $recrow['creditfrom_accountid'], 'reciveddate' => $recrow['reciveddate'], 'transactionno' => $recrow['transactionno'], 'creditamount' => $recrow['creditamount'], 'creditpurposecode' => $recrow['creditpurposecode'], 'creditpurpose' => $recrow['creditpurpose'], 'bankdescription' => $recrow['bankdescription'], 'description' => $recrow['description'], 'beneficeryname' => $recrow['beneficeryname'], 'statmentsno' => $recrow['statmentsno'], 'booksno' => $recrow['booksno'], 'invoicesno' => $recrow['invoicesno'], 'emailsno' => $recrow['emailsno'], 'invoiceno' => $recrow['invoiceno'], 'requestno' => $recrow['requestno'], 'requeststatus' => $recrow['requeststatus']);
		}
	}
	return $reclist;
}

function getrequestdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithprojectid") {
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' order by sno desc";
	} else if ($shortby == "allunapprovedwithprojectid") {
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (approval_status is null or approval_status='' or approval_status='approved') order by sno desc";
	} else if ($shortby == "allunapprovedwaitingforapproval") {
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and approval_status='Added in Waiting' order by sno desc";
	} else if ($shortby == "allunapprovedwithsubcompanyid") {
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and (approval_status is null or approval_status='' or approval_status='approved') order by sno desc";
	} else if ($shortby == "allunapprovedwithcompanyid") {
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and (approval_status is null or approval_status='' or approval_status='approved') order by sno desc";
	} else if ($shortby == "requestno") {
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "betweendatewillall") {
		$dexp = explode("|", $shortbyvalue);
		$rdatefrom = $dexp[0];
		$rdateto = $dexp[1];
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, CASE WHEN approval_status IS NULL OR approval_status = '' THEN 'pending' WHEN approval_status = 'Payment Paid' or approval_status = 'payment paid' THEN 'approved' ELSE approval_status END AS approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_date>='" . date('Y-m-d', strtotime($rdatefrom)) . "' and request_date<='" . date('Y-m-d', strtotime($rdateto)) . "' order by request_date desc";
	} else if ($shortby == "betweendatewithrequestedby") {
		$dexp = explode("|", $shortbyvalue);
		$rdatefrom = $dexp[0];
		$rdateto = $dexp[1];
		$requestedby = $dexp[2];
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, CASE WHEN approval_status IS NULL OR approval_status = '' THEN 'pending' WHEN approval_status = 'Payment Paid' or approval_status = 'payment paid' THEN 'approved' ELSE approval_status END AS approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_date>='" . date('Y-m-d', strtotime($rdatefrom)) . "' and request_date<='" . date('Y-m-d', strtotime($rdateto)) . "' and lcase(requested_by) like '%" . strtolower($requestedby) . "%'";
	} else if ($shortby == "sno") {
		$rqqry = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, before_editamount, purpose_type, CASE WHEN approval_status IS NULL OR approval_status = '' THEN 'pending' WHEN approval_status = 'Payment Paid' or approval_status = 'payment paid' THEN 'approved' ELSE approval_status END AS approval_status, status_by, paid_for_ref, authorized_by, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountid, payto_accountno, payto_bankifsccode, status_cheque, pending_description, priority, is_intermediate_transfer, intermediate_transfer_type, refundable_projectid, software_version, reminder_mail_status, is_purchaseforproduction, paymenttypestatus FROM tbl_paymentrequest where  sno='" . $shortbyvalue . "'";
	}
	$rqqry_rst = $conn->query($rqqry);
	$reqlust = array();
	if ($rqqry_rst->num_rows > 0) {
		while ($rqrow = $rqqry_rst->fetch_assoc()) {
			$reqlust[] = array('sno' => $rqrow['sno'], 'countryid' => $rqrow['countryid'], 'companyid' => $rqrow['companyid'], 'subcompanyid' => $rqrow['subcompanyid'], 'projectid' => $rqrow['projectid'], 'subprojectid' => $rqrow['subprojectid'], 'temp_requestno' => $rqrow['temp_requestno'], 'request_no' => $rqrow['request_no'], 'request_date' => $rqrow['request_date'], 'paymentduedate' => $rqrow['paymentduedate'], 'requested_by' => $rqrow['requested_by'], 'paytoid' => $rqrow['paytoid'], 'payto' => $rqrow['payto'], 'payment_mode' => $rqrow['payment_mode'], 'beneficiery_type' => $rqrow['beneficiery_type'], 'beneficiery_name' => $rqrow['beneficiery_name'], 'request_amount' => $rqrow['request_amount'], 'before_editamount' => $rqrow['before_editamount'], 'purpose_type' => $rqrow['purpose_type'], 'approval_status' => $rqrow['approval_status'], 'status_by' => $rqrow['status_by'], 'paid_for_ref' => $rqrow['paid_for_ref'], 'authorized_by' => $rqrow['authorized_by'], 'from_bankname' => $rqrow['from_bankname'], 'from_accountid' => $rqrow['from_accountid'], 'from_accountno' => $rqrow['from_accountno'], 'payto_bankname' => $rqrow['payto_bankname'], 'payto_accountid' => $rqrow['payto_accountid'], 'payto_accountno' => $rqrow['payto_accountno'], 'payto_bankifsccode' => $rqrow['payto_bankifsccode'], 'status_cheque' => $rqrow['status_cheque'], 'pending_description' => $rqrow['pending_description'], 'priority' => $rqrow['priority'], 'is_intermediate_transfer' => $rqrow['is_intermediate_transfer'], 'intermediate_transfer_type' => $rqrow['intermediate_transfer_type'], 'reminder_mail_status' => $rqrow['reminder_mail_status'], 'refundable_projectid' => $rqrow['refundable_projectid'], 'is_purchaseforproduction' => $rqrow['is_purchaseforproduction'], 'paymenttypestatus' => $rqrow['paymenttypestatus']);
		}
	}
	return $reqlust;
}

function getrequestbreakdowndetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "requestno") {
		$reqbrk = "select sno, request_no, beneficiery_name, ammount, before_editamount, purpose_type, purpose_category, purpose_subcategory, purpose_categoryid, purpose_subcategoryid, purpose, purpose_code, description, document_status, invoice_type, invoice_no, invoicedate, taxableamount, gstamount, documenttype, extension, filepath, audiofilepath, voiceinstid, blobfileid, blobfile_tblname, countryid, companyid, subcompanyid, projectid, subprojectid FROM tbl_paymentrequest_breakdown WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$reqbrk = "select sno, request_no, beneficiery_name, ammount, before_editamount, purpose_type, purpose_category, purpose_subcategory, purpose_categoryid, purpose_subcategoryid, purpose, purpose_code, description, document_status, invoice_type, invoice_no, invoicedate,taxableamount, gstamount, documenttype, extension, filepath, audiofilepath, voiceinstid, blobfileid, blobfile_tblname, countryid, companyid, subcompanyid, projectid, subprojectid FROM tbl_paymentrequest_breakdown WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$reqbrk_reslt = $conn->query($reqbrk);
	$reqbrklist = array();
	if ($reqbrk_reslt->num_rows > 0) {
		while ($reqbrow = $reqbrk_reslt->fetch_assoc()) {
			if ($reqbrow['taxableamount'] > 0) {
				$taxableamount = $reqbrow['taxableamount'];
			} else {
				$taxableamount = $reqbrow['ammount'];
			}
			// if($reqbrow['invoice_no']){
			// 	$shortbyvalue=$reqbrow['request_no'].'|'.$reqbrow['invoice_no'];
			// 	$invdetails=purchaseinvoicemain($conn,$companyid,$subcompanyid,$projectid,"requestnowithinvoiceno",$shortbyvalue);
			// 	if(count($invdetails)>0){
			// 		foreach ($invdetails as $invmkey) {
			// 			$taxableamount=$invmkey['totaltaxableamount'];
			// 		}
			// 	}
			// }

			$reqbrklist[] = array('sno' => $reqbrow['sno'], 'request_no' => $reqbrow['request_no'], 'beneficiery_name' => $reqbrow['beneficiery_name'], 'ammount' => $reqbrow['ammount'], 'before_editamount' => $reqbrow['before_editamount'], 'purpose_type' => $reqbrow['purpose_type'], 'purpose_category' => $reqbrow['purpose_category'], 'purpose_subcategory' => $reqbrow['purpose_subcategory'], 'purpose_categoryid' => $reqbrow['purpose_categoryid'], 'purpose_subcategoryid' => $reqbrow['purpose_subcategoryid'], 'purpose' => $reqbrow['purpose'], 'purpose_code' => $reqbrow['purpose_code'], 'description' => $reqbrow['description'], 'document_status' => $reqbrow['document_status'], 'invoice_type' => $reqbrow['invoice_type'], 'invoice_no' => $reqbrow['invoice_no'], 'invoice_date' => $reqbrow['invoicedate'], 'taxableamount' => $taxableamount, 'gstamount' => $reqbrow['gstamount'], 'documenttype' => $reqbrow['documenttype'], 'extension' => $reqbrow['extension'], 'filepath' => $reqbrow['filepath'], 'audiofilepath' => $reqbrow['audiofilepath'], 'voiceinstid' => $reqbrow['voiceinstid'], 'blobfileid' => $reqbrow['blobfileid'], 'blobfile_tblname' => $reqbrow['blobfile_tblname'], 'countryid' => $reqbrow['countryid'], 'companyid' => $reqbrow['companyid'], 'subcompanyid' => $reqbrow['subcompanyid'], 'projectid' => $reqbrow['projectid'], 'subprojectid' => $reqbrow['subprojectid']);
		}
	}
	return $reqbrklist;
}

function purchaseinvoicemain($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "requestno") {
		$inmq = "SELECT sno, companyid, subcompanyid, projectid, invoiceid, invoicedate, invoiceno, totalamount, totaltaxableamount, totalgstamount, totaldiscountamount, otherdiscount, totalpayableamount, requestno, referenceno, status FROM tbl_invoicespurchase_main WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and requestno='" . $shortbyvalue . "'";
	} else if ($shortby == "requestnowithinvoiceno") {
		$reqwithinvno = explode("|", $shortbyvalue);
		$requestno = $reqwithinvno[0];
		$invoiceno = $reqwithinvno[1];
		$inmq = "SELECT sno, companyid, subcompanyid, projectid, invoiceid, invoicedate, invoiceno, totalamount, totaltaxableamount, totalgstamount, totaldiscountamount, otherdiscount, totalpayableamount, requestno, referenceno, status FROM tbl_invoicespurchase_main WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and requestno='" . $requestno . "' and invoiceno='" . $invoiceno . "'";
	} else if ($shortby == "invoiceid") {
		$inmq = "SELECT sno, companyid, subcompanyid, projectid, invoiceid, invoicedate, invoiceno, totalamount, totaltaxableamount, totalgstamount, totaldiscountamount, otherdiscount, totalpayableamount, requestno, referenceno, status FROM tbl_invoicespurchase_main WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and invoiceid='" . $shortbyvalue . "'";
	}
	$inmqr = $conn->query($inmq);
	$invmainlist = array();
	if ($inmqr->num_rows > 0) {
		while ($inmrow = $inmqr->fetch_assoc()) {
			$invmainlist[] = array('sno' => $inmrow['sno'], 'companyid' => $inmrow['companyid'], 'subcompanyid' => $inmrow['subcompanyid'], 'projectid' => $inmrow['projectid'], 'invoiceid' => $inmrow['invoiceid'], 'invoicedate' => $inmrow['invoicedate'], 'invoiceno' => $inmrow['invoiceno'], 'totalamount' => $inmrow['totalamount'], 'totaltaxableamount' => $inmrow['totaltaxableamount'], 'totalgstamount' => $inmrow['totalgstamount'], 'totaldiscountamount' => $inmrow['totaldiscountamount'], 'otherdiscount' => $inmrow['otherdiscount'], 'totalpayableamount' => $inmrow['totalpayableamount'], 'requestno' => $inmrow['requestno'], 'referenceno' => $inmrow['referenceno'], 'status' => $inmrow['status']);
		}
	}
	return $invmainlist;
}

function GetPurposeCategory($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pqury = "select distinct category,categoryid FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "purposecode") {
		$pqury = "select distinct category,categoryid FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and purposecode='" . $shortbyvalue . "'";
	} else if ($shortby == "onlyexpenditure") {
		$pqury = "select distinct category,categoryid FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "categoryname") {
		$pqury = "select distinct category,categoryid FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "categoryid") {
		$pqury = "select distinct category,categoryid FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(categoryid)='" . strtolower($shortbyvalue) . "'";
	} else {
		$pqury = "";
	}
	$purpose_category = array();
	if ($pqury) {
		$pqury_rslt = $conn->query($pqury);
		if ($pqury_rslt->num_rows > 0) {
			while ($prow = $pqury_rslt->fetch_assoc()) {
				$purpose_category[] = array('pcategory' => $prow['category'], 'pcategorycode' => $prow['categoryid']);
			}
		}
	}
	return $purpose_category;
}

function GetPurposeCategoryfrommaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and lcase(hasform)='yes' order by categoryid";
	} else if ($shortby == "purposecode") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and purposecode='" . $shortbyvalue . "' and (tedit is null or tedit='') and lcase(hasform)='yes' order by category";
	} else if ($shortby == "onlyexpenditure") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='') and lcase(hasform)='yes' order by category";
	} else if ($shortby == "categoryname") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='') and lcase(hasform)='yes' order by category";
	} else if ($shortby == "categoryid") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(categoryid)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='') and lcase(hasform)='yes' order by category";
	} else {
		$pqury = "";
	}
	$purpose_category = array();
	if ($pqury) {
		$pqury_rslt = $conn->query($pqury);
		if ($pqury_rslt->num_rows > 0) {
			while ($prow = $pqury_rslt->fetch_assoc()) {
				$purpose_category[] = array('pcategory' => strtoupper($prow['category']), 'pcategorycode' => $prow['categoryid']);
			}
		}
	}
	return $purpose_category;
}

function GetPurposeSubCategoryfrommaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='')";
	} else if ($shortby == "onlyexpenditure") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "categoryname") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "categoryid") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(categoryid)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "subcategoryid") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_categoryid)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "subcategoryname") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_category_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	}
	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('subpcategory' => strtoupper($prow['sub_category']), 'subpcategorycode' => $prow['sub_categoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}
function GetPurposeSubCategoryfrommaster_credit($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='')";
	} else if ($shortby == "purposecode") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and purposecode='" . $shortbyvalue . "' and (tedit is null or tedit='')";
	} else if ($shortby == "onlyexpenditure") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "categoryname") {
		$pqury = "select distinct category,categoryid FROM purpose_category_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "categoryid") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_category_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(categoryid)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "subcategoryid") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_category_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_categoryid)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "subcategoryname") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_category_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category)='" . strtolower($shortbyvalue) . "' and (tedit is null or tedit='')";
	}
	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('subpcategory' => strtoupper($prow['sub_category']), 'subpcategorycode' => $prow['sub_categoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}
function GetPurposeSubCategoryfromdefaultmaster($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "withdrawn") {
		$pqury = "select category,categoryid,subcategory,subcategoryid,purposename from main_defaultpurpose_master where type='" . $shortbyvalue . "'";
	} else if ($shortby == "deposit") {
		$pqury = "select category,categoryid,subcategory,subcategoryid,purposename from main_defaultpurpose_master where type='" . $shortbyvalue . "'";
	}
	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('category' => strtoupper($prow['subcategory']), 'categorycode' => $prow['subcategoryid'], 'subpcategory' => strtoupper($prow['subcategory']), 'subpcategorycode' => $prow['subcategoryid'], 'purposename' => strtoupper($prow['purposename']));
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}

function GetPurposeCategory_credit($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pqury = "select distinct category,categoryid FROM tbl_purpose_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "purposecode") {
		$pqury = "select distinct category,categoryid FROM tbl_purpose_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and purposecode='" . $shortbyvalue . "'";
	} else if ($shortby == "onlyexpenditure") {
		$pqury = "select distinct category,categoryid FROM tbl_purpose_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "categoryname") {
		$pqury = "select distinct category,categoryid FROM tbl_purpose_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "categoryid") {
		$pqury = "select distinct category,categoryid FROM tbl_purpose_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(categoryid)='" . strtolower($shortbyvalue) . "'";
	}
	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('pcategory' => $prow['category'], 'pcategorycode' => $prow['categoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}

function updatemarkedbynote4($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "markedasnull") {
		$uuq = $conn->query("UPDATE purpose_code_master SET markbynote4=null where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='')");
	} else if ($shortby == "markedaspurpose") {
		$uuq = $conn->query("UPDATE purpose_code_master SET markbynote4='markbynote4' where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and (markbynote4 is null or markbynote4='') and purposecode='" . $shortbyvalue . "'");
	}
	if ($uuq->num_rows > 0) {
		return "marked";
	} else {
		return "notmarked";
	}

}
function updatemarkedbynote4credit($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "markedasnull") {
		$uuq = $conn->query("UPDATE purpose_code_master_credit SET markbynote4=null where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='')");
	} else if ($shortby == "markedaspurpose") {
		$uuq = $conn->query("UPDATE purpose_code_master_credit SET markbynote4='markbynote4' where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and (markbynote4 is null or markbynote4='') and purposecode='" . $shortbyvalue . "'");
	}
	if ($uuq->num_rows > 0) {
		return "marked";
	} else {
		return "notmarked";
	}

}
function GetPurposeSubCategory($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_code_master";
	} else if ($shortby == "category") {
		$pqury = "select distinct sub_category,sub_categoryid FROM purpose_code_master where categoryid='" . $shortbyvalue . "'";
	}
	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('psubcategory' => $prow['sub_category'], 'psubcategorycode' => $prow['sub_categoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}

function GetPurposeSubCategory_credit($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pqury = "select distinct subcategory ,subcategoryid  FROM tbl_purpose_master_credit";
	} else if ($shortby == "category") {
		$pqury = "select distinct subcategory ,subcategoryid  FROM tbl_purpose_master_credit where categoryid='" . $shortbyvalue . "'";
	}
	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('psubcategory' => $prow['subcategory'], 'psubcategorycode' => $prow['subcategoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}


function getcompanyemailid($conn, $companyid)
{
	$getemail = "";
	$qry = "Select email_id from tbl_company_master where companyid='" . $companyid . "'";
	$result_qry = $conn->query($qry);
	if ($result_qry->num_rows > 0) {
		$iemai = $result_qry->fetch_assoc();
		$getemail = $iemai['email_id'];
	}

	return $getemail;
}
function get_unauditeddaterange($conn, $companyid, $subcompanyid, $projectid)
{
	$daterange = array();
	$qry = "select date(min(c.date)) as mintransdate,date(max(c.date)) as maxtransdate, date(now()) as today from tbl_closetobook c where c.company_id='" . $companyid . "' and c.subcompany_id='" . $subcompanyid . "' and c.project_id='" . $projectid . "' and ifnull(c.audit_status,'')='' and(date(date)<>'0000-00-00')";
	$result_qry = $conn->query($qry);
	if ($result_qry->num_rows > 0) {
		$row = $result_qry->fetch_assoc();
		$daterange[] = array("mindate" => $row["mintransdate"], "enddate" => $row["maxtransdate"], "maxdate" => $row["today"]);
	}
	return $daterange;
}

function getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{

	if ($shortby == "all") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'  and ifnull(tedit,'')='' and visiblestatus='yes' order by categoryid, sub_categoryid_client ,sub_categoryid, provided_code");
	} else if ($shortby == "checkpurpose_providedbyproject") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'  and ifnull(tedit,'')='' and visiblestatus='yes' and source<>'auto' order by categoryid, sub_categoryid_client ,sub_categoryid, provided_code");
	} else if ($shortby == "allwithoutvisiblestatus") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'  and ifnull(tedit,'')='' order by categoryid, sub_categoryid_client, sub_categoryid, provided_code");
	} else if ($shortby == "allmarkbynote4") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and markbynote4 is not null and markbynote4<>''  and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
	} else if ($shortby == "allmarkbynote4_withsubcategory") {
		$ppexp = explode("|", $shortbyvalue);
		$categoryid = $ppexp[0];
		$subcategoryid = $ppexp[1];
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and markbynote4 is not null and markbynote4<>'' and categoryid in('" . $categoryid . "', '" . str_pad($categoryid, 2, "0", STR_PAD_LEFT) . "') and sub_categoryid='" . $subcategoryid . "'  and ifnull(tedit,'')='' and visiblestatus='yes'");

	} else if ($shortby == "purposecode") {

		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and visiblestatus='yes' and purposecode='" . $shortbyvalue . "' order by purpose");
	} else if ($shortby == "purposecode_all") {

		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'   and purposecode='" . $shortbyvalue . "' order by purpose");

	} else if ($shortby == "purposeid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and ifnull(tedit,'')='' and purposeid='" . $shortbyvalue . "' order by purpose");
	} else if ($shortby == "purposename") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(purpose)='" . strtolower($shortbyvalue) . "'  and ifnull(tedit,'')='' order by purpose");
	} else if ($shortby == "purposecategory" || $shortby == "purposecategoryid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid='" . $shortbyvalue . "' and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
	} else if ($shortby == "purposesubcategory") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_category like'%" . $shortbyvalue . "' and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
	} else if ($shortby == "subcategoryid") {

		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid='" . $shortbyvalue . "' and ifnull(tedit,'')='' and visiblestatus='yes' order by purpose");
	} else if ($shortby == "purposecategoryname") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "purposecategorynamelikely") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category) like '%" . strtolower($shortbyvalue) . "%' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "purposesubcategoryname") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category)='" . strtolower($shortbyvalue) . "' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "purposesubcategorynamelikely") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category) like '%" . strtolower($shortbyvalue) . "%' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "clientcategorynamelikely") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category_client) like '%" . strtolower($shortbyvalue) . "%' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "subcatlistwithlikelysubcatnamewithcatid") {
		$ppexp = explode("|", $shortbyvalue);
		$categoryid = $ppexp[0];
		$liklysubcatname = $ppexp[1];
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid='" . $categoryid . "' and lcase(sub_category) like '%" . strtolower($liklysubcatname) . "%' and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
	} else if ($shortby == "clientsubcategoryliswithlikelysubcategory") {
		$ppexp = explode("|", $shortbyvalue);
		$categoryid = $ppexp[0];
		$liklysubcatname = $ppexp[1];
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid='" . $categoryid . "' and lcase(sub_category_client) like '%" . strtolower($liklysubcatname) . "%' and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
	} else if ($shortby == "purposesubcategoryname_notlikely") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category) not like '%" . strtolower($shortbyvalue) . "%' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "clientsubcategoryname_notlikely") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category_client) not like '%" . strtolower($shortbyvalue) . "%' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "provided_purposecode") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and provided_code='" . $shortbyvalue . "' and ifnull(tedit,'')='' and visiblestatus='yes'");
	} else if ($shortby == "provided_purposename") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(purpose)='" . strtolower($shortbyvalue ?? "") . "' and ifnull(tedit,'')=''");
	} else if ($shortby == "provided_purposenamelikly") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(purpose) like '%" . strtolower($shortbyvalue ?? "") . "%' and ifnull(tedit,'')=''");
	} else if ($shortby == "allcaterylist") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and ifnull(tedit,'')='' and visiblestatus='yes' group by categoryid");
	} else if ($shortby == "allsubcaterylist_withcategoryid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid in('" . $shortbyvalue . "', '" . str_pad($shortbyvalue, 2, "0", STR_PAD_LEFT) . "') and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
	} else if ($shortby == "allsubcaterylist_withoutcategoryid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary,visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and ifnull(tedit,'')='' and visiblestatus='yes' group by sub_categoryid");
	} else if ($shortby == "withcategory_subcategory") {
		$ppexp = explode("|", $shortbyvalue);
		$categoryid = $ppexp[0];
		$subcategoryid = $ppexp[1];
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid in('" . $categoryid . "', '" . str_pad($categoryid, 2, "0", STR_PAD_LEFT) . "') and sub_categoryid='" . $subcategoryid . "' and ifnull(tedit,'')='' and visiblestatus='yes'");
	}
	$sltplist = array();
	if ($sltp->num_rows > 0) {
		while ($imrow = $sltp->fetch_assoc()) {
			$comptpurpose = $imrow['purpose'] . '(' . $imrow['purposecode'] . ')';

			if ($imrow['provided_code_client']) {
				$purposewih_prov = $imrow['provided_code_client'] . ' - ' . $imrow['purpose'];
			} else {
				$purposewih_prov = $imrow['provided_code'] . ' - ' . $imrow['purpose'];
			}

			$sltplist[] = array('pcategory' => $imrow['category'], 'pcategorycode' => $imrow['categoryid'], 'psub_category' => $imrow['sub_category'], 'psub_categoryid' => $imrow['sub_categoryid'], 'purposeid' => $imrow['purposeid'], 'purpose' => $imrow['purpose'], 'purposecode' => $imrow['purposecode'], 'purposewithprovidedcode' => $purposewih_prov, 'purposewithcode' => $comptpurpose, 'isforstock' => $imrow['isforstock'], 'budget_permonth' => $imrow['budget_per_month'], 'total_peryear' => $imrow['total_per_year'], 'provided_code' => $imrow['provided_code'], 'default_beneficiary' => $imrow['default_beneficiary'], 'visiblestatus' => $imrow['visiblestatus'], 'sub_categoryid_client' => $imrow['sub_categoryid_client'], 'sub_category_client' => $imrow['sub_category_client'], 'provided_code_client' => $imrow['provided_code_client']);
		}
	}
	return $sltplist;

}

function getcreditpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' order by categoryid,sub_categoryid, provided_code, purpose");
	} else if ($shortby == "allwithoutvisiblestatus") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'  and ifnull(tedit,'')='' order by categoryid,sub_categoryid_client,sub_categoryid, provided_code, purpose");
	} else if ($shortby == "allmarkbynote4") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and markbynote4 is not null and markbynote4<>''  and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' order by purpose");
	} else if ($shortby == "allmarkbynote4_withsubcategory") {
		$ppexp = explode("|", $shortbyvalue);
		$categoryid = $ppexp[0];
		$subcategoryid = $ppexp[1];
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and markbynote4 is not null and markbynote4<>'' and categoryid in('" . $categoryid . "', '" . str_pad($categoryid, 2, "0", STR_PAD_LEFT) . "') and sub_categoryid='" . $subcategoryid . "'  and lcase(visiblestatus)='yes' and ifnull(tedit,'')=''");
	} else if ($shortby == "purposecode") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' and purposecode='" . $shortbyvalue . "' order by purpose");
	} else if ($shortby == "purposeid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' and purposeid='" . $shortbyvalue . "' order by purpose");
	} else if ($shortby == "purposename") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where  cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(purpose)='" . strtolower($shortbyvalue) . "' and lcase(visiblestatus)='yes'  and ifnull(tedit,'')='' order by purpose ");
	} else if ($shortby == "purposesubcategory") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid='" . $shortbyvalue . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' order by purpose");
	} else if ($shortby == "subcategoryid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid='" . $shortbyvalue . "'  and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' order by purpose");
	} else if ($shortby == "purposecategoryname") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(category)='" . strtolower($shortbyvalue) . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')=''");
	} else if ($shortby == "purposesubcategoryname") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(sub_category)='" . strtolower($shortbyvalue) . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')=''");
	} else if ($shortby == "provided_purposecode") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and provided_code='" . $shortbyvalue . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')=''");
	} else if ($shortby == "allcaterylist") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' group by categoryid");
	} else if ($shortby == "allsubcaterylist_withcategoryid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid in('" . $shortbyvalue . "', '" . str_pad($shortbyvalue, 2, "0", STR_PAD_LEFT) . "') and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' group by sub_categoryid");
	} else if ($shortby == "allsubcaterylist_withoutcategoryid") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')='' group by sub_categoryid");
	} else if ($shortby == "withcategory_subcategory") {
		$ppexp = explode("|", $shortbyvalue);
		$categoryid = $ppexp[0];
		$subcategoryid = $ppexp[1];
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid in('" . $categoryid . "', '" . str_pad($categoryid, 2, "0", STR_PAD_LEFT) . "') and sub_categoryid='" . $subcategoryid . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')=''");
	} else if ($shortby == "provided_purposename") {
		$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purposeid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary, visiblestatus, sub_categoryid_client, sub_category_client, provided_code_client FROM purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(purpose)='" . strtolower($shortbyvalue ?? "") . "' and lcase(visiblestatus)='yes' and ifnull(tedit,'')=''");
	}
	$sltplist = array();
	if ($sltp->num_rows > 0) {
		while ($imrow = $sltp->fetch_assoc()) {
			$comptpurpose = $imrow['purpose'] . '(' . $imrow['purposecode'] . ')';
			$purposewih_prov = $imrow['purpose'] . '(' . $imrow['provided_code'] . ')';
			$sltplist[] = array('pcategory' => $imrow['category'], 'pcategorycode' => $imrow['categoryid'], 'psub_category' => $imrow['sub_category'], 'psub_categoryid' => $imrow['sub_categoryid'], 'purposeid' => $imrow['purposeid'], 'purpose' => $imrow['purpose'], 'purposecode' => $imrow['purposecode'], 'purposewithprovidedcode' => $purposewih_prov, 'purposewithcode' => $comptpurpose, 'isforstock' => $imrow['isforstock'], 'budget_permonth' => $imrow['budget_per_month'], 'total_peryear' => $imrow['total_per_year'], 'provided_code' => $imrow['provided_code'], 'default_beneficiary' => $imrow['default_beneficiary'], 'visiblestatus' => $imrow['visiblestatus'], 'sub_categoryid_client' => $imrow['sub_categoryid_client'], 'sub_category_client' => $imrow['sub_category_client'], 'provided_code_client' => $imrow['provided_code_client']);
		}
	}
	return $sltplist;

}

function uploadsalarysheet_functions($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "salarysheet") {
		$keyquer = $conn->query("SELECT sno, companyid, subcompanyid, projectid, key1, key2, key3, key4, key5, key6, key7, key8, key9, key10, key11, key12, key13, key14, key15, hrow,htillcol, function_name FROM tbl_import_excelsheetdata_function WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and uploadtype='salarysheet'");
	} else if ($shortby == "otherthansalarysheet") {
		$keyquer = $conn->query("SELECT sno, companyid, subcompanyid, projectid, key1, key2, key3, key4, key5, key6, key7, key8, key9, key10, key11, key12, key13, key14, key15, hrow,htillcol, function_name FROM tbl_import_excelsheetdata_function WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and uploadtype='salarysheet'");
	} else if ($shortby == "uploadtype") {
		$keyquer = $conn->query("SELECT sno, companyid, subcompanyid, projectid, key1, key2, key3, key4, key5, key6, key7, key8, key9, key10, key11, key12, key13, key14, key15, hrow,htillcol, function_name FROM tbl_import_excelsheetdata_function WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and uploadtype='" . $shortbyvalue . "'");
	}
	$keyinformation = array();
	if ($keyquer->num_rows > 0) {
		$ups = $keyquer->fetch_assoc();
		$keyinformation[] = array('companyid' => $ups['companyid'], 'subcompanyid' => $ups['subcompanyid'], 'projectid' => $ups['projectid'], 'key1' => $ups['key1'], 'key2' => $ups['key2'], 'key3' => $ups['key3'], 'key4' => $ups['key4'], 'key5' => $ups['key5'], 'key6' => $ups['key6'], 'key7' => $ups['key7'], 'key8' => $ups['key8'], 'key9' => $ups['key9'], 'key10' => $ups['key10'], 'key11' => $ups['key11'], 'key12' => $ups['key12'], 'key13' => $ups['key13'], 'key14' => $ups['key14'], 'key15' => $ups['key15'], 'hrow' => $ups['hrow'], 'htillcol' => $ups['htillcol'], 'function_name' => $ups['function_name']);
	}
	return $keyinformation;
}

function uploadedexcelsheet_datafromsqltable($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "readyforbooksalary") {
		$rdq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname, monthofsalary, designation, actualsalary, salarydays, monthlysalary, bankname, accountno, ifsccode, advancededuction, pfdeduction, esicdeduction, otherdeduction, remark, transfersalary,purpose,purposecode,upload_datatype FROM tbl_import_excelsheetdata WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and upload_datatype='salaryexpense' and (referenceno is null or referenceno='')");
	} else if ($shortby == "readyforbookother") {
		$rdq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname, monthofsalary, designation, actualsalary, salarydays, monthlysalary, bankname, accountno, ifsccode, advancededuction, pfdeduction, esicdeduction, otherdeduction, remark, transfersalary,purpose,purposecode,upload_datatype FROM tbl_import_excelsheetdata WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and upload_datatype='otherexpense' and (referenceno is null or referenceno='')");
	} else if ($shortby == "sno") {
		$rdq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname, monthofsalary, designation, actualsalary, salarydays, monthlysalary, bankname, accountno, ifsccode, advancededuction, pfdeduction, esicdeduction, otherdeduction, remark, transfersalary,purpose,purposecode,upload_datatype FROM tbl_import_excelsheetdata WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'");
	}
	$slrowdata = array();
	if ($rdq->num_rows > 0) {
		while ($slrow = $rdq->fetch_assoc()) {
			$slrowdata[] = array('sno' => $slrow['sno'], 'companyid' => $slrow['companyid'], 'subcompanyid' => $slrow['subcompanyid'], 'projectid' => $slrow['projectid'], 'paytoid' => $slrow['paytoid'], 'paytoname' => $slrow['paytoname'], 'monthofsalary' => $slrow['monthofsalary'], 'designation' => $slrow['designation'], 'actualsalary' => $slrow['actualsalary'], 'salarydays' => $slrow['salarydays'], 'monthlysalary' => $slrow['monthlysalary'], 'bankname' => $slrow['bankname'], 'accountno' => $slrow['accountno'], 'ifsccode' => $slrow['ifsccode'], 'advancededuction' => $slrow['advancededuction'], 'pfdeduction' => $slrow['pfdeduction'], 'esicdeduction' => $slrow['esicdeduction'], 'otherdeduction' => $slrow['otherdeduction'], 'remark' => $slrow['remark'], 'transfersalary' => $slrow['transfersalary'], 'referenceno' => $slrow['referenceno'], 'purpose' => $slrow['purpose'], 'purposecode' => $slrow['purposecode'], 'uploaddatatype' => $slrow['upload_datatype']);
		}
	}
	return $slrowdata;
}

function createAssociativeArray($originalArray)
{
	$newArray = array();
	$keys = null;
	foreach ($originalArray as $index => $row) {
		if ($index === 0) {
			$keys = $row;
			// If the key is empty, assign a default value
			foreach ($keys as $keyIndex => $key) {
				if (empty($key)) {
					$keys[$keyIndex] = 'NoKey' . ($keyIndex + 1);
				}
			}
		} else {
			$newRow = array();
			foreach ($keys as $keyIndex => $key) {
				if (!empty($row[$keyIndex])) {
					$newRow[trim($key)] = $row[$keyIndex];
				}
			}
			if (!empty($newRow)) {
				$newArray[] = $newRow;
			}
		}
	}
	return $newArray;
}

function projectexist_inprojectauth($mconn, $companyid, $subcompanyid, $projectid, $imprestpaytoid)
{
	$mmq = $mconn->query("select sno from imprest_authorizedproject where imprestpaytoid='" . $imprestpaytoid . "' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'");
	if ($mmq->num_rows > 0) {
		return 'exist';
	} else {
		return 'notexist';
	}
}

function getpurposefulldetails($conn, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "projectid") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and c.projectid='" . $shortbyvalue . "' and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	} else if ($shortby == "purposecode") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and p.purposecode='" . $shortbyvalue . "' and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	} else if ($shortby == "providedcode") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and p.provided_code='" . $shortbyvalue . "' and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	} else if ($shortby == "purposecategoryid") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and c.categoryid='" . $shortbyvalue . "' and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	} else if ($shortby == "categoryname") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and lcase(c.category)='" . strtolower($shortbyvalue) . "' and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	} else if ($shortby == "subcategoryname") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and lcase(c.sub_category)='" . strtolower($shortbyvalue) . "' and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	} else if ($shortby == "purposename") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and p.purpose='" . $shortbyvalue . "' and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	} else if ($shortby == "likepurposename") {
		$ppq = "select c.cmp_id,c.subcmp_id,c.projectid,c.categoryid,c.category,c.sub_categoryid,c.sub_category,p.purposecode,p.purpose,p.isforstock,p.provided_code,p.visiblestatus FROM purpose_code_master p, purpose_category_master c  where c.subcmp_id=p.subcmp_id and c.projectid=p.projectid and c.sub_categoryid=p.sub_categoryid and c.subcmp_id='" . $subcompanyid . "' and p.purpose like('%" . $shortbyvalue . "%') and (p.tedit is null or p.tedit='') and p.visiblestatus='yes'";
	}
	$ppq_rslt = $conn->query($ppq) or die($conn->error);
	$plist = array();
	$comptpurpose = "";
	if ($ppq_rslt->num_rows > 0) {
		while ($prow = $ppq_rslt->fetch_assoc()) {
			$comptpurpose = $prow['purpose'] . '(' . $prow['purposecode'] . ')';
			$plist[] = array('cmp_id' => $prow['cmp_id'], 'subcmp_id' => $prow['subcmp_id'], 'projectid' => $prow['projectid'], 'categoryid' => $prow['categoryid'], 'category' => $prow['category'], 'sub_categoryid' => $prow['sub_categoryid'], 'sub_category' => $prow['sub_category'], 'subcmp_id' => $prow['subcmp_id'], 'purposecode' => $prow['purposecode'], 'purpose' => $prow['purpose'], 'isforstock' => $prow['isforstock'], 'purposewithcode' => $comptpurpose, 'provided_code' => $prow['provided_code'], 'visiblestatus' => $prow['visiblestatus']);
		}
	}
	return $plist;
}


function Getfinancialyearfromda_impresttatable($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "imprestaccountid") {
		$finyearfq = $conn->query("select CASE WHEN MONTH(idate)>=4 THEN CONCAT(YEAR(idate),'-',YEAR(idate)+1) ELSE CONCAT(YEAR(idate)-1,'-',YEAR(idate)) END AS financial_year FROM imprest_account where  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and to_accountid='" . $shortbyvalue . "' group by financial_year");
	}
	if ($finyearfq->num_rows > 0) {
		while ($frow = $finyearfq->fetch_assoc()) {
			$finyearlist[] = array('financialyear' => $frow['financial_year']);
		}
	} else {
		$finyearlist = array();
	}
	return $finyearlist;
}

function getpayerinfoworking($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == 'all') {
		$pyrqry = "select sno, payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payer_master where payername is not null and payername<>''  and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and (tedit is null or tedit='') order by payername";
	} else if ($shortby == 'payername') {
		$pyrqry = "select sno, payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payer_master where lcase(payername)='" . strtolower($shortbyvalue) . "' and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and (tedit is null or tedit='') and payername is not null and payername<>'' order by payername";
	} else if ($shortby == 'projectid') {
		$pyrqry = "select sno,payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payer_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and projectid='" . $shortbyvalue . "' and (tedit is null or tedit='') and payername is not null and payername<>'' order by payername";
	} else if ($shortby == 'likelypayto') {
		$pyrqry = "select sno,payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payer_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and match(payername) against ('" . $shortbyvalue . "') and (tedit is null or tedit='') and payername is not null and payername<>'' order by payername";
	} else if ($shortby == 'payerid') {
		$pyrqry = "select sno,payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payer_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and (tedit is null or tedit='') and payerid = '" . $shortbyvalue . "' and payername is not null and payername<>''";
	} else if ($shortby == 'distinctpayto') {
		$pyrqry = "select distinct payerid, payername,sno, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payer_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and projectid='" . $shortbyvalue . "' and (tedit is null or tedit='') and (payername is not null and payername<>'') order by payername";
	}

	$paytoqry = $conn->query($pyrqry);
	$paytoarray = array();
	if ($paytoqry->num_rows > 0) {
		while ($pyrow = $paytoqry->fetch_assoc()) {
			$paytoarray[] = array('psno' => $pyrow['sno'], 'payerid' => $pyrow['payerid'], 'payername' => strtoupper($pyrow['payername']), 'tobankname' => $pyrow['bankname'], 'toaccountno' => $pyrow['accountno'], 'tobankifsc' => $pyrow['ifsccode'], 'iban' => $pyrow['iban'], 'swift' => $pyrow['swift'], 'sortcode' => $pyrow['sortcode']);
		}
	}
	return $paytoarray;
}
function getpayerinfo($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	$baseQuery = "SELECT sno, payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payer_master WHERE cmp_id=? AND subcmp_id=? AND (tedit IS NULL OR tedit='') AND payername IS NOT NULL AND payername<>''";
	$conditions = "";
	$params = [$companyid, $subcompanyid];

	if ($shortby === 'payername') {
		$conditions = " AND LCASE(payername) = ?";
		$params[] = strtolower($shortbyvalue);
	} elseif ($shortby === 'projectid') {
		$conditions = " AND projectid = ?";
		$params[] = $shortbyvalue;
	} elseif ($shortby === 'likelypayto') {
		$conditions = " AND MATCH(payername) AGAINST (?)";
		$params[] = $shortbyvalue;
	} elseif ($shortby === 'payerid') {
		$conditions = " AND payerid = ?";
		$params[] = $shortbyvalue;
	} elseif ($shortby === 'distinctpayto') {
		$baseQuery = "SELECT DISTINCT payerid, payername, sno, accountno, bankname, ifsccode, iban, swift, sortcode 
                      FROM tbl_payer_master 
                      WHERE cmp_id=? AND subcmp_id=? AND (tedit IS NULL OR tedit='') AND payername IS NOT NULL AND payername<>''";
		$conditions = " AND projectid = ?";
		$params[] = $shortbyvalue;
	}

	$finalQuery = $baseQuery . $conditions . " ORDER BY payername";

	$stmt = $conn->prepare($finalQuery);
	$stmt->bind_param(str_repeat("s", count($params)), ...$params);
	$stmt->execute();
	$result = $stmt->get_result();

	$paytoarray = [];
	while ($row = $result->fetch_assoc()) {
		$paytoarray[] = [
			'psno' => $row['sno'],
			'payerid' => $row['payerid'],
			'payername' => strtoupper($row['payername']),
			'tobankname' => $row['bankname'],
			'toaccountno' => $row['accountno'],
			'tobankifsc' => $row['ifsccode'],
			'iban' => $row['iban'],
			'swift' => $row['swift'],
			'sortcode' => $row['sortcode']
		];
	}

	return $paytoarray;
}

function getpaytoinfo($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == 'all') {
		$allqry = "select sno, paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where paytoname is not null and paytoname<>''  and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and (tedit is null or tedit='') order by paytoname";
	} else if ($shortby == 'paytoname') {
		$allqry = "select sno, paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where  (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and lcase(paytoname)='" . strtolower($shortbyvalue) . "' and paytoname is not null and paytoname<>'' order by paytoname";
	} else if ($shortby == 'projectid') {
		$allqry = "select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and projectid='" . $shortbyvalue . "' and (tedit is null or tedit='') and paytoname is not null and paytoname<>'' order by paytoname";
	} else if ($shortby == 'likelypayto') {
		$allqry = "select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and match(paytoname) against ('" . $shortbyvalue . "') and (tedit is null or tedit='') and paytoname is not null and paytoname<>'' order by paytoname";
	} else if ($shortby == 'paytoid') {
		$allqry = "select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and (tedit is null or tedit='') and paytoid = '" . $shortbyvalue . "' and (paytoname is not null and paytoname<>'')";
	} else if ($shortby == 'distinctpayto') {
		$allqry = "select  distinct paytoname, sno, paytoid, accountno, bankname, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, address, city, state, postalcode, country, taxid, taxtype, taxrate FROM tbl_payto_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and projectid='" . $shortbyvalue . "' and (tedit is null or tedit='') and (paytoname is not null and paytoname<>'') order by paytoname";
	}

	$paytoqry = $conn->query($allqry);
	$paytoarray = array();
	if ($paytoqry->num_rows > 0) {
		while ($pyrow = $paytoqry->fetch_assoc()) {
			$paytoarray[] = array('psno' => $pyrow['sno'], 'paytoid' => $pyrow['paytoid'], 'payto' => strtoupper($pyrow['paytoname']), 'paytoname' => strtoupper($pyrow['paytoname']), 'tobankname' => $pyrow['bankname'], 'toaccountno' => $pyrow['accountno'], 'tobankifsc' => $pyrow['ifsccode'], 'iban' => $pyrow['iban'], 'swift' => $pyrow['swift'], 'sortcode' => $pyrow['sortcode'], 'paymentterms' => $pyrow['paymentterms'], 'mobileno' => $pyrow['mobileno'], 'emailaddress' => $pyrow['emailaddress'], 'address' => $pyrow['address'], 'city' => $pyrow['city'], 'state' => $pyrow['state'], 'postalcode' => $pyrow['postalcode'], 'country' => $pyrow['country'], 'taxid' => $pyrow['taxid'], 'taxtype' => $pyrow['taxtype'], 'taxrate' => $pyrow['taxrate'], 'status' => 'Success');
		}
	}
	return $paytoarray;
}

function getpaytoinfo_combineagentandclient_deposit($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	$allqry = "WITH combined_data AS ( SELECT 
		client_id AS paytoid, client_name AS paytoname,  'Direct' AS clienttype FROM client_master
		where ifnull (tedit,'')  ='' UNION ALL
		SELECT  client_id AS paytoid,  client_name AS paytoname,  'Intermediate' AS clienttype
		FROM intermediatemaster where ifnull (tedit,'')  ='' ),
	ranked_data AS (
		SELECT  paytoid,  paytoname,  clienttype,
		ROW_NUMBER() OVER (PARTITION BY paytoname ORDER BY CASE WHEN clienttype = 'Direct' THEN 1 ELSE 2 END) AS rn FROM combined_data )
	SELECT paytoid, paytoname,  clienttype FROM ranked_data WHERE rn = 1  order by paytoname";
	$paytoqry = $conn->query($allqry);
	$paytoarray = array();
	if ($paytoqry->num_rows > 0) {
		while ($pyrow = $paytoqry->fetch_assoc()) {
			$paytoarray[] = array('paytoid' => $pyrow['paytoid'], 'paytoname' => strtoupper($pyrow['paytoname']), 'clienttype' => $pyrow['clienttype'], 'status' => 'Success');
		}
	}
	return $paytoarray;
}
function getcreditcardauthorizedinfo($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == 'all') {
		$allqry = "select sno,userid,userloginid,username,mobileno,emailid FROM creditcard_authorizedperson where username is not null and username<>''  and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and (tedit is null or tedit='') order by username";
	} else if ($shortby == 'name') {
		$allqry = "select sno,userid,userloginid,username,mobileno,emailid FROM tbl_payto_master where lcase(username)='" . strtolower($shortbyvalue) . "' and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and (tedit is null or tedit='') and username is not null and username<>'' order by username";
	} else if ($shortby == 'projectid') {
		$allqry = "select sno,userid,userloginid,username,mobileno,emailid FROM tbl_payto_master where cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and projectid='" . $shortbyvalue . "' and (tedit is null or tedit='') and username is not null and username<>'' order by username";
	}

	$paytoqry = $conn->query($allqry);
	$paytoarray = array();
	if ($paytoqry->num_rows > 0) {
		while ($pyrow = $paytoqry->fetch_assoc()) {
			$paytoarray[] = array('psno' => $pyrow['sno'], 'userid' => $pyrow['userid'], 'userloginid' => strtoupper($pyrow['userloginid']), 'username' => strtoupper($pyrow['username']), 'mobileno' => $pyrow['mobileno'], 'emailid' => $pyrow['emailid']);
		}
	}
	return $paytoarray;
}
function getdepositerinfo($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == 'all') {

		$allqry = "select sno, depositerid, depositername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_depositer_master where depositername is not null and depositername<>''  and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' order by depositername";
	} else if ($shortby == 'depositername') {
		$allqry = "select sno, depositerid, depositername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_depositer_master where lcase(depositername)='" . strtolower($shortbyvalue) . "' and countryid = '" . $countryid . "' and cmp_id='" . $companyid . "' and subcmp_id = '" . $subcompanyid . "' and depositername is not null and depositername<>'' order by depositername";
	} else if ($shortby == 'projectid') {
		$allqry = "select sno,depositerid, depositername, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_depositer_master where  projectid='" . $shortbyvalue . "' and depositername is not null and depositername<>'' order by depositername";
	}

	$paytoqry = $conn->query($allqry);
	$paytoarray = array();
	if ($paytoqry->num_rows > 0) {
		while ($pyrow = $paytoqry->fetch_assoc()) {
			$paytoarray[] = array('psno' => $pyrow['sno'], 'depositername' => $pyrow['depositername'], 'tobankname' => $pyrow['bankname'], 'toaccountno' => $pyrow['accountno'], 'tobankifsc' => $pyrow['ifsccode'], 'depositerid' => $pyrow['depositerid'], 'iban' => $pyrow['iban'], 'swift' => $pyrow['swift'], 'sortcode' => $pyrow['sortcode']);
		}
	}
	return $paytoarray;
}

function checkentryallradyexist_inbreakdown($conn, $companyid, $subcompanyid, $projectid, $payto, $amount, $purposecode, $description, $transaction_type, $bookdate)
{
	$sltm = $conn->query("SELECT sno FROM tbl_maintransaction_breakdown_excelbook WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  and lcase(beneficiary_name)='" . addslashes(trim(strtolower($payto))) . "'and amount='" . trim($amount) . "' and purpose_code ='" . $purposecode . "' and description='" . addslashes(trim($description)) . "' and lcase(transaction_type)='" . addslashes(trim(strtolower($transaction_type))) . "' and bookdate='" . addslashes(trim($bookdate)) . "'");
	$returnsno = array();
	if ($sltm->num_rows > 0) {
		$rrts = "exist";
	} else {
		$rrts = "notexist";
	}
	return $rrts;
}

function modificationamountwhiletransaction($conn, $companyid, $subcompanyid, $projectid, $datefrom, $dateto)
{
	$ssqu = $conn->query("SELECT request_no,reference_no,bookdate,transaction_date,book_amount,transfered_amount,payto,payfrombankname,payfromaccountno FROM tbl_maintransaction where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and DATE(bookdate)>='" . $datefrom . "' and DATE(bookdate)<='" . $dateto . "' and CAST(book_amount AS DECIMAL(10,2)) != CAST(transfered_amount AS DECIMAL(10,2)) ");
	$mrow = array();
	if ($ssqu->num_rows > 0) {
		while ($mdrow = $ssqu->fetch_assoc()) {
			$mrow[] = $mdrow;
		}
	}
	return $mrow;
}

function checkexisttochequeno($conn, $companyid, $subcompanyid, $projectid, $payfromaccountno, $transactionno, $modeofpayment)
{
	$sltm = $conn->query("SELECT sno, tsno, reference_no,book_amount FROM tbl_maintransaction_excelbook WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  and payfromaccountno='" . $payfromaccountno . "' and transactionno='" . trim($transactionno) . "' and transaction_type='" . trim($modeofpayment) . "'");
	$returnsno = array();
	if ($sltm->num_rows > 0) {
		$tmrow = $sltm->fetch_assoc();
		$returnsno[] = array('sno' => $tmrow['sno'], 'tsno' => $tmrow['tsno'], 'reference_no' => $tmrow['reference_no'], 'book_amount' => $tmrow['book_amount']);
	}
	return $returnsno;
}

function getusernameofcreditcard($conn, $companyid, $subcompanyid, $projectid, $credicardno)
{
	$creditarr = array();
	$qry = "select a.paytoid,b.username from creditcard_authorizedproject as a join creditcard_authorizedperson as b on a.paytoid=b.userid where a.companyid='" . $companyid . "' and a.subcompanyid='" . $subcompanyid . "' and a.projectid='" . $projectid . "' and a.cardnumber='" . $credicardno . "' and (a.tedit is null or a.tedit='')";
	$resulqry = $conn->query($qry);
	if ($resulqry->num_rows > 0) {
		while ($rowcd = $resulqry->fetch_assoc()) {
			$creditarr[] = array('id' => $rowcd['paytoid'], 'username' => $rowcd['username']);
		}
	}
	return $creditarr;
}


function getwithdrawalsubcategoriesinfo($conn, $projectid)
{
	$catqry = "select distinct p.sub_categoryid,p.sub_category from main_category_master m join purpose_code_master p on (p.categoryid=m.categoryid) where p.projectid='" . $projectid . "'  group by p.sub_categoryid order by p.sub_categoryid";
	$catlist = array();
	$cat_rs = $conn->query($catqry);
	if ($cat_rs->num_rows > 0) {
		while ($accrow = $cat_rs->fetch_assoc()) {
			$catlist[] = array('subcategoryid' => $accrow['sub_categoryid'], 'sub_category' => $accrow['sub_category']);
		}
	}
	return $catlist;
}

function getdepositsubcategoriesinfo($conn, $projectid)
{
	$catqry = "select distinct p.sub_categoryid,p.sub_category from main_category_master_credit m join purpose_code_master_credit p on (p.categoryid=m.categoryid) where p.projectid='" . $projectid . "'  group by p.sub_categoryid order by p.sub_categoryid";
	$catlist = array();
	$cat_rs = $conn->query($catqry);
	if ($cat_rs->num_rows > 0) {
		while ($accrow = $cat_rs->fetch_assoc()) {
			$catlist[] = array('subcategoryid' => $accrow['sub_categoryid'], 'sub_category' => $accrow['sub_category']);
		}
	}

	return $catlist;
}

function getaccountinformation($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$accinq = "";
	if ($shortby == "projectid") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Bank' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' order by account_type, accountname";
	} else if ($shortby == "accountid") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "') and accountid in('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "paytoid") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "') and paytoid in('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "onlybankpaytoid") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and account_type='Bank' and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "') and paytoid in('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "accountno") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "') and accountno in('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "checkexistwithaccountno") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='')  and accountno in('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "accountnowithloanaccount") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "') and accountno in('" . str_replace(",", "','", $shortbyvalue) . "') and lcase(account_type)='loan'";
	} else if ($shortby == "fd_accountno") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "') and accountno in('" . str_replace(",", "','", $shortbyvalue) . "') and lcase(account_type)='fd'";
	} else if ($shortby == "onlyfd_accountlist") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "')  and lcase(account_type)='fd'";
	} else if ($shortby == "accountidW") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='')  and accountid in('" . str_replace(",", "','", $shortbyvalue) . "') and accountname not like '%_FD'";
	} else if ($shortby == "subcompanyid") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and accountname not like '%_FD'";
	} else if ($shortby == "subcompanyid_withoutproject") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $subcompanyid) . "') and projectid not in ('" . str_replace(",", "','", $projectid) . "') and accountname not like '%_FD'";
	} else if ($shortby == "companyid") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and account_type not like '%FD'";
	} else if ($shortby == "forlogin") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Bank' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid in('" . str_replace(",", "','", $shortbyvalue) . "') and accountname not like '%_FD'";
	} else if ($shortby == "bankname") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Bank' and (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' and lcase(bankname) like '%" . strtolower(trim($shortbyvalue)) . "%' and accountname not like '%_FD'";
	} else if ($shortby == "cash") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='cash' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "'";
	} else if ($shortby == "creditcard") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='credit card' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "'";
	} else if ($shortby == "supplieraccount") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='supplier' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "'";
	} else if ($shortby == "Imprest") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE lcase(account_type)='imprest' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "'";
	} else if ($shortby == "Imprestwithaccountid") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Imprest' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' and accountid='" . $shortbyvalue . "'";
	} else if ($shortby == "Imprestwithname") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE account_type='Imprest' and (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' and lcase(accountname)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "allaccountofproject") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' ";
	} else if ($shortby == "allaccountofprojectonlybank") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' and lcase(account_type)='bank'";
	} else if ($shortby == "allaccountofprojectonlybankandcreditcard") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' and (lcase(account_type)='bank' or lcase(account_type)='creditcard')";
	} else if ($shortby == "allloanaccount") {
		$accinq = "select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername,branchname,branchaddress,paytoid FROM tbl_master_account WHERE  (tedit is null or tedit='') and  cmp_id='" . $companyid . "' and subcmp_id= '" . $subcompanyid . "' and projectid ='" . $projectid . "' and lcase(account_type)='loan'";
	}
	$accountlist = array();
	// echo $accinq; 
	$accinq_rs = $conn->query($accinq);
	if ($accinq_rs->num_rows > 0) {
		while ($accrow = $accinq_rs->fetch_assoc()) {
			$accountlist[] = array('sno' => $accrow['sno'], 'countryid' => $accrow['countryid'], 'cmp_id' => $accrow['cmp_id'], 'subcmp_id' => $accrow['subcmp_id'], 'projectid' => $accrow['projectid'], 'accountid' => $accrow['accountid'], 'bankid' => $accrow['bankid'], 'account_type' => $accrow['account_type'], 'uploadsheet_formatid' => $accrow['uploadsheet_formatid'], 'accountname' => $accrow['accountname'], 'paytoname' => $accrow['accountname'], 'accountno' => $accrow['accountno'], 'ifsccode' => $accrow['ifsccode'] ? $accrow['ifsccode'] : "N/A", 'bankname' => $accrow['bankname'], 'accountholdername' => $accrow['accountholdername'], 'branchname' => $accrow['branchname'] ? $accrow['branchname'] : "N/A", 'branchaddress' => $accrow['branchaddress'] ? $accrow['branchaddress'] : "N/A", "paytoid" => $accrow['paytoid']);
		}
	}
	return $accountlist;
}


function generateUniquePaytoId($conn, $companyid, $subcompanyid, $paytoname)
{
	// Check if the paytoname already exists
	$stmt = $conn->prepare("SELECT paytoid FROM tbl_payto_master WHERE cmp_id = ? AND subcmp_id = ? AND paytoname = ?");
	$stmt->bind_param('iis', $companyid, $subcompanyid, $paytoname); // Bind parameters
	$stmt->execute();
	$stmt->bind_result($existingPaytoId);
	$stmt->fetch();
	$stmt->close();

	// If a matching paytoname is found, return the existing paytoid
	if (!empty($existingPaytoId)) {
		return $existingPaytoId;
	}

	// Generate a unique paytoid
	do {
		// Generate a random 5-digit number
		$uniqueId = mt_rand(10000, 99999);

		// Check if the uniqueId already exists in the database
		$stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_payto_master WHERE paytoid = ?");
		$stmt->bind_param('i', $uniqueId);
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();

		// Check if the ID exists
		$exists = $count > 0;
	} while ($exists); // Repeat if the ID already exists

	// Return the new unique ID
	return $uniqueId;
}

function generateUniquePayertoId($conn, $companyid, $subcompanyid, $payertoname)
{
	// Check if the paytoname already exists
	$stmt = $conn->prepare("SELECT payerid FROM tbl_payer_master WHERE cmp_id = ? AND subcmp_id = ? AND payername = ?");
	$stmt->bind_param('iis', $companyid, $subcompanyid, $payertoname); // Bind parameters
	$stmt->execute();
	$stmt->bind_result($existingPaytoId);
	$stmt->fetch();
	$stmt->close();

	// If a matching paytoname is found, return the existing paytoid
	if (!empty($existingPaytoId)) {
		return $existingPaytoId;
	}

	// Generate a unique paytoid
	do {
		// Generate a random 5-digit number
		$uniqueId = mt_rand(10000, 99999);

		// Check if the uniqueId already exists in the database
		$stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_payer_master WHERE payerid = ?");
		$stmt->bind_param('i', $uniqueId);
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();

		// Check if the ID exists
		$exists = $count > 0;
	} while ($exists); // Repeat if the ID already exists

	// Return the new unique ID
	return $uniqueId;
}

function generateUniqueCreditCardId($conn, $companyid)
{
	// Check if the paytoname already exists
	$stmt = $conn->prepare("SELECT ccid FROM creditcard_master WHERE companyid = ?");
	$stmt->bind_param('i', $companyid);
	$stmt->execute();
	$stmt->bind_result($existingPaytoId);
	$stmt->fetch();
	$stmt->close();

	// If a matching paytoname is found, return the existing paytoid
	if (!empty($existingPaytoId)) {
		return $existingPaytoId;
	}

	// Generate a unique paytoid
	do {
		// Generate a random 5-digit number
		$uniqueId = mt_rand(10000, 99999);

		// Check if the uniqueId already exists in the database
		$stmt = $conn->prepare("SELECT COUNT(*) FROM creditcard_master WHERE ccid = ?");
		$stmt->bind_param('i', $uniqueId);
		$stmt->execute();
		$stmt->bind_result($count);
		$stmt->fetch();
		$stmt->close();

		// Check if the ID exists
		$exists = $count > 0;
	} while ($exists); // Repeat if the ID already exists

	// Return the new unique ID
	return $uniqueId;
}

function validateCreditCardNumber($cardNumber)
{
	// Remove any non-digit characters (e.g., spaces or dashes)
	$cardNumber = preg_replace('/\D/', '', $cardNumber);

	// Check if the card number has the correct length (typically 13-19 digits)
	if (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
		return false;
	}

	// Luhn Algorithm for validation
	$sum = 0;
	$alt = false;
	for ($i = strlen($cardNumber) - 1; $i >= 0; $i--) {
		$n = intval($cardNumber[$i]);

		if ($alt) {
			$n *= 2;
			if ($n > 9) {
				$n -= 9;
			}
		}

		$sum += $n;
		$alt = !$alt;
	}

	// Valid if the sum is a multiple of 10
	return ($sum % 10 === 0);
}
function validateCVV($cvv, $cardType = null)
{
	// Remove any non-digit characters
	$cvv = preg_replace('/\D/', '', $cvv);

	// Determine the CVV length based on the card type
	// Common lengths: 3 digits for most cards (Visa, MasterCard), 4 digits for American Express
	$cvvLength = 3; // Default for most cards
	if ($cardType === "Amex" || $cardType === "American Express") {
		$cvvLength = 4;
	}

	// Check if the CVV has the correct length
	if (strlen($cvv) === $cvvLength) {
		return true;
	}

	return false;
}
function validateExpiryDate($expiryMonth, $expiryYear)
{
	// Current month and year
	$currentYear = date("Y");
	$currentMonth = date("m");

	// Ensure expiry month is between 1 and 12
	if ($expiryMonth < 1 || $expiryMonth > 12) {
		return false;
	}

	// Ensure expiry year is not in the past
	if ($expiryYear < $currentYear) {
		return false;
	}

	// If the expiry year is the current year, ensure the expiry month is not in the past
	if ($expiryYear == $currentYear && $expiryMonth < $currentMonth) {
		return false;
	}

	return true;
}


function verifybankbalance($conn, $accountid)
{
	$unverfiedlist = array();
	$qry2 = $conn->query("Select sno,valuedate,balance,debitamount,creditamount,description,bankname,accountno from tbl_bankstatement where account_id='" . $accountid . "' and ifnull(verify_balance,'')='' order by valuedate asc, sno asc");
	if ($qry2->num_rows > 0) {
		while ($row2 = $qry2->fetch_assoc()) {
			$retvals = getveryiedbalance($conn, $accountid);
			$retval = explode("|", $retvals);
			$opbal = (float) $retval[0];
			$openingdate = $retval[1];
			//echo "\nCredit: ". $row2['creditamount']."\nDebit: ". $row2['debitamount'];			
			$calulatebalnc = ($opbal + $row2['creditamount']) - $row2['debitamount'];
			//echo "\n Formula:  ". $calulatebalnc."-".$row2['balance'];
			//echo "\n Differ: ". round($calulatebalnc-$row2['balance'],2);
			//echo "\n opening date : ".date_format(date_create($openingdate),"Y-m-d");
			//echo "\n entry date : ".date_format(date_create($row2['valuedate']),"Y-m-d");
			if (date_format(date_create($openingdate), "Y-m-d") > date_format(date_create($row2['valuedate']), "Y-m-d")) {
				//echo "Update tbl_bankstatement set verify_balance=null, calculated_balance=null where valuedate>'".$row2['valuedate']."' and account_id='".$accountid."'";
				//die;
				$conn->query("Update tbl_bankstatement set verify_balance=null, calculated_balance=null where valuedate>'" . $row2['valuedate'] . "' and account_id='" . $accountid . "'");
				verifybankbalance($conn, $accountid);
			}
			//die;
			if (round($calulatebalnc - $row2['balance'], 2) == 0) {
				$conn->query("Update tbl_bankstatement set verify_balance='verifyed', calculated_balance='" . $calulatebalnc . "' where sno='" . $row2['sno'] . "' and account_id='" . $accountid . "'");
			} else {
				$unverfiedlist[] = array('status' => 'unverifyed', 'message' => '', 'valuedate' => $row2['valuedate'], 'bankname' => $row2['bankname'], 'accountno' => $row2['accountno'], 'description' => $row2['description'], 'debitamount' => $row2['debitamount'], 'creditamount' => $row2['creditamount'], 'balance' => $row2['balance']);
			}
			$calulatebalnc = 0;
		}
	}
	return $unverfiedlist;
}

function getveryiedbalance($conn, $accountid)
{
	//echo "SELECT balance as last_verified FROM tbl_bankstatement  WHERE verify_balance = 'verifyed' and account_id='".$accountid."'  ORDER BY valuedate DESC, sno DESC LIMIT 1";
	$qry = $conn->query("SELECT balance as last_verified,valuedate FROM tbl_bankstatement  WHERE verify_balance = 'verifyed' and account_id='" . $accountid . "'  ORDER BY valuedate DESC, sno DESC LIMIT 1");
	if ($qry->num_rows > 0) {
		$row = $qry->fetch_assoc();
		$verifyedbalance = $row['last_verified'];
		return $verifyedbalance . "|" . $row['valuedate'];
	} else {
		//echo "SELECT (balance+debitamount-creditamount) as balance FROM tbl_bankstatement  WHERE account_id='".$accountid."'  ORDER BY valuedate, sno LIMIT 1";
		$qry = $conn->query("SELECT (balance+debitamount-creditamount) as balance,valuedate FROM tbl_bankstatement  WHERE account_id='" . $accountid . "' ORDER BY valuedate, sno LIMIT 1");
		$row = $qry->fetch_assoc();
		$balance = $row['balance'];
		return $balance . "|" . $row['valuedate'];
	}
}

function getaccountdetailsinfo($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "accountid") {
		// $accid = str_replace(",", "','", $shortbyvalue);
		$accqry = "select accountid,cmp_id,subcmp_id,projectid,bankid,accountname,accountno,ifsccode,bankname,countryid,uploadsheet_formatid FROM tbl_master_account where cmp_id='" . str_replace(",", "','", $companyid) . "' and subcmp_id='" . str_replace(",", "','", $subcompanyid) . "' and accountid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "subcompanyid") {
		$accqry = "select accountid,cmp_id,subcmp_id,projectid,bankid,accountname,accountno,ifsccode,bankname,countryid,uploadsheet_formatid FROM tbl_master_account where cmp_id='" . str_replace(",", "','", $companyid) . "' and subcmp_id in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "projectid") {
		// $projectids = str_replace(",", "','", $shortbyvalue);
		$accqry = "select accountid,cmp_id,subcmp_id,projectid,bankid,accountname,accountno,ifsccode,bankname,countryid,uploadsheet_formatid FROM tbl_master_account where cmp_id='" . str_replace(",", "','", $companyid) . "' and subcmp_id='" . str_replace(",", "','", $subcompanyid) . "' and projectid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "accountid") {
		$accqry = "select accountid,cmp_id,subcmp_id,projectid,bankid,accountname,accountno,ifsccode,bankname,countryid,uploadsheet_formatid FROM tbl_master_account where cmp_id='" . str_replace(",", "','", $companyid) . "' and subcmp_id='" . str_replace(",", "','", $subcompanyid) . "' and accountid in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "accountno") {
		$accqry = "select accountid,cmp_id,subcmp_id,projectid,bankid,accountname,accountno,ifsccode,bankname,countryid,uploadsheet_formatid FROM tbl_master_account where cmp_id='" . str_replace(",", "','", $companyid) . "' and subcmp_id='" . str_replace(",", "','", $subcompanyid) . "' and accountno in ('" . str_replace(",", "','", $shortbyvalue) . "')";
	}

	$accinfoq = $conn->query($accqry);
	if ($accinfoq->num_rows > 0) {
		while ($accrow = $accinfoq->fetch_assoc()) {
			$accountlist[] = array('accountid' => $accrow['accountid'], 'cmp_id' => $accrow['cmp_id'], 'subcmp_id' => $accrow['subcmp_id'], 'projectid' => $accrow['projectid'], 'bankid' => $accrow['bankid'], 'accountname' => $accrow['accountname'], 'accountno' => $accrow['accountno'], 'ifsccode' => $accrow['ifsccode'], 'bankname' => $accrow['bankname'], 'countryid' => $accrow['countryid'], 'uploadsheet_formatid' => $accrow['uploadsheet_formatid']);
		}
	} else {
		$accountlist = array();
	}
	return $accountlist;
}

function getdataforimprestrequest($conn, $companyid, $subcompanyid, $projectid, $ashortby, $ashortbyvalue)
{
	if ($ashortby == "readytorequest_withemailid") {
		$qqr = "SELECT sno, companyid, subcompanyid, projectid, requestedby_emailid, requestno_temp, requestno, beneficieryname, beneficieryamount, purposecategoryid, purposesubcategoryid, purposecode, invoicetype, invoiceno, description, documentpath, docncode, blobfile, doctype, tedit, tedit_remark, blobfile_tblname  FROM tbl_paymentrequest_details_temp where (requestno is null or requestno='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and lcase(requestedby_emailid)='" . strtolower($ashortbyvalue) . "'";
	} else if ($ashortby == "requestno") {
		$qqr = "SELECT sno, companyid, subcompanyid, projectid, requestedby_emailid, requestno_temp, requestno, beneficieryname, beneficieryamount, purposecategoryid, purposesubcategoryid, purposecode, invoicetype, invoiceno, description, documentpath, docncode, blobfile, doctype, tedit, tedit_remark, blobfile_tblname  FROM tbl_paymentrequest_details_temp where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and lcase(requestno)='" . strtolower($ashortbyvalue) . "'";
	}

	$pdimp = $conn->query($qqr);
	$pendingimprestlist = array();
	if ($pdimp->num_rows > 0) {
		while ($pdrow = $pdimp->fetch_assoc()) {
			$pendingimprestlist[] = array('sno' => $pdrow['sno'], 'companyid' => $pdrow['companyid'], 'subcompanyid' => $pdrow['subcompanyid'], 'projectid' => $pdrow['projectid'], 'requestedby_emailid' => $pdrow['requestedby_emailid'], 'requestno_temp' => $pdrow['requestno_temp'], 'requestno' => $pdrow['requestno'], 'beneficieryname' => $pdrow['beneficieryname'], 'beneficieryamount' => $pdrow['beneficieryamount'], 'purposecategoryid' => $pdrow['purposecategoryid'], 'purposesubcategoryid' => $pdrow['purposesubcategoryid'], 'purposecode' => $pdrow['purposecode'], 'invoicetype' => $pdrow['invoicetype'], 'invoiceno' => $pdrow['invoiceno'], 'description' => $pdrow['description'], 'documentpath' => $pdrow['documentpath'], 'docncode' => $pdrow['docncode'], 'blobfile' => $pdrow['blobfile'], 'doctype' => $pdrow['doctype'], 'tedit' => $pdrow['tedit'], 'tedit_remark' => $pdrow['tedit_remark'], 'blobfile_tblname' => $pdrow['blobfile_tblname']);
		}
	}
	return $pendingimprestlist;
}


function GetMaxprojecidstdb($conn)
{
	$selectsno = "select max(projectid) as texp_sno from tbl_project_master";
	$result_sno = $conn->query($selectsno);
	if ($result_sno->num_rows > 0) {
		$rowsno = $result_sno->fetch_array();
		$maxtempsno = $rowsno['texp_sno'] + 1;
	}
	return $maxtempsno;
}

function getprojectdetails($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
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


function projectdetails($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$pquery = "select sno, companyid, subcompanyid, projectid, projectname, project_email, stateid, districtid, statecode, districtcode, documenttype FROM tbl_project_master where  companyid='" . str_replace(",", "','", $companyid) . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and (tedit is null or tedit='')";
	} else if ($shortby == "projectid") {
		$pquery = "select sno, companyid, subcompanyid, projectid, projectname, project_email, stateid, districtid, statecode, districtcode, documenttype FROM tbl_project_master where companyid='" . str_replace(",", "','", $companyid) . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and projectid in ('" . str_replace(",", "','", $shortbyvalue) . "') and (tedit is null or tedit='')";
	} else if ($shortby == "emailid") {
		$pquery = "select sno, companyid, subcompanyid, projectid, projectname, project_email, stateid, districtid, statecode, districtcode, documenttype FROM tbl_project_master where companyid='" . str_replace(",", "','", $companyid) . "' and subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and project_email in ('" . str_replace(",", "','", $shortbyvalue) . "') and (tedit is null or tedit='')";
	} else if ($shortby == "subcompanyid") {
		$pquery = "select sno, companyid, subcompanyid, projectid, projectname, project_email, stateid, districtid, statecode, districtcode, documenttype FROM tbl_project_master where companyid='" . str_replace(",", "','", $companyid) . "' and subcompanyid in ('" . str_replace(",", "','", $shortbyvalue) . "') and (tedit is null or tedit='')";
	} else if ($shortby == "companyid") {
		$pquery = "select sno, companyid, subcompanyid, projectid, projectname, project_email, stateid, districtid, statecode, districtcode, documenttype FROM tbl_project_master where  subcompanyid='" . str_replace(",", "','", $subcompanyid) . "' and companyid in ('" . str_replace(",", "','", $shortbyvalue) . "') and (tedit is null or tedit='')";
	}
	$project_rslt = $conn->query($pquery);
	$projectlist = array();
	if ($project_rslt->num_rows > 0) {
		while ($plist = $project_rslt->fetch_assoc()) {
			$projectlist[] = array('sno' => $plist['sno'], 'companyid' => $plist['companyid'], "subcompanyid" => $plist['subcompanyid'], 'projectid' => $plist['projectid'], 'projectname' => $plist['projectname'], 'projectemail' => $plist['project_email'], 'stateid' => $plist['stateid'], 'districtid' => $plist['districtid'], 'statecode' => $plist['statecode'], 'districtcode' => $plist['districtcode'], "documenttype" => $plist['documenttype']);
		}
	}
	return $projectlist;
}


function subprojectdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$ssbq = "";
	if ($shortby == "all") {
		$ssbq = "select sno, companyid, subcompanyid, projectid, subprojectid, subprojectname FROM tbl_projectsub_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='')";
	} else if ($shortby == "subprojectid") {
		$ssbq = "select sno, companyid, subcompanyid, projectid, subprojectid, subprojectname FROM tbl_projectsub_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "'  and subprojectid='" . $shortbyvalue . "' and (tedit is null or tedit='')";
	} else if ($shortby == "sno") {
		$ssbq = "select sno, companyid, subcompanyid, projectid, subprojectid, subprojectname FROM tbl_projectsub_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and sno='" . $shortbyvalue . "' and (tedit is null or tedit='')";
	}
	$sblist = array();
	if ($ssbq) {
		$ssbq_result = $conn->query($ssbq) or die($conn->error);
		if ($ssbq_result->num_rows > 0) {
			while ($sbrow = $ssbq_result->fetch_assoc()) {
				$sblist[] = array('sno' => $sbrow['sno'], 'companyid' => $sbrow['companyid'], 'subcompanyid' => $sbrow['subcompanyid'], 'projectid' => $sbrow['projectid'], 'subprojectid' => $sbrow['subprojectid'], 'subprojectname' => $sbrow['subprojectname']);
			}
		}
	}
	return $sblist;
}



function getnextdocument($conn, $senderemail, $sno)
{
	$email = $senderemail;
	$email_replace = str_replace(".", "dot", $email);
	$new_email = str_replace("@", "at", $email_replace);

	$getdocqry = "select sno,ID,base64,message FROM email_status where lcase(EmailID)='" . strtolower($new_email) . "' and (reqstatus is null or reqstatus='') and sno>$sno limit 1";
	$getdocqrslt = $conn->query($getdocqry);
	if ($getdocqrslt->num_rows > 0) {
		while ($docrow = $getdocqrslt->fetch_assoc()) {
			$doclist[] = array("sno" => $docrow['sno'], "docid" => $docrow['ID'], 'docbase64' => $docrow['base64'], 'docmessage' => $docrow['message']);
		}
	} else {
		$doclist = array();
	}
	return $doclist;
}

function getdocumentinfo($conn, $sno)
{
	$getdocqry = "select sno,ID,base64,message FROM email_status where lcase(sno)='" . strtolower($sno) . "' and (reqstatus is null or reqstatus='')";
	$getdocqrslt = $conn->query($getdocqry);
	if ($getdocqrslt->num_rows > 0) {
		while ($docrow = $getdocqrslt->fetch_assoc()) {
			$doclist1[] = array("sno" => $docrow['sno'], "docid" => $docrow['ID'], 'docbase64' => $docrow['base64'], 'docmessage' => $docrow['message']);
		}
	} else {
		$doclist1 = array();
	}
	return $doclist1;
}




function statemaster($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$cquery = "select state_id,state_code,state FROM state_master";
	} else if ($shortby == "statecode") {
		$cquery = "select state_id,state_code,state FROM state_master where state_code in ('" . $shortbyvalue . "')";
	} else if ($shortby == "stateid") {
		$cquery = "select state_id,state_code,state FROM state_master where state_id in ('" . $shortbyvalue . "')";
	}

	$sts_rslt = $conn->query($cquery);
	if ($sts_rslt->num_rows > 0) {
		while ($slist = $sts_rslt->fetch_assoc()) {
			$statelist[] = array('stateid' => $slist['state_id'], 'statecode' => $slist['state_code'], 'statename' => $slist['state']);
		}
	} else {
		$statelist = array();
	}
	return $statelist;
}

function districtmaster($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$cquery = "select district_id,district_code,district_name,state_id FROM district_master";
	} else if ($shortby == "districtcode") {
		$cquery = "select district_id,district_code,district_name,state_id FROM district_master where district_code in ('" . $shortbyvalue . "')";
	} else if ($shortby == "districtid") {
		$cquery = "select district_id,district_code,district_name,state_id FROM district_master where district_id in ('" . $shortbyvalue . "')";
	}

	$dist_rslt = $conn->query($cquery);
	if ($dist_rslt->num_rows > 0) {
		while ($dlist = $dist_rslt->fetch_assoc()) {
			$districtlist[] = array('districtid' => $dlist['district_id'], 'districtcode' => $dlist['district_code'], 'districtname' => $dlist['district_name'], 'stateid' => $dlist['state_id']);
		}
	} else {
		$districtlist = array();
	}
	return $districtlist;
}
function GetReportsPeriod($conn)
{
	$quryp = "select period from tbl_reports_period";
	$resultq = $conn->query($quryp);
	if ($resultq->num_rows > 0) {

		while ($rowp = $resultq->fetch_assoc()) {
			$period[] = array('period' => $rowp['period']);
		}
	} else {
		$period = array();
	}

	return $period;
}

function Getmonthsfromfinancialyearof_subcompny($conn, $subcompny, $financialyear, $periodselect)
{
	$quryp = "select financialyear_start from tbl_company_submaster";
	$resultq = $conn->query($quryp);
	if ($resultq->num_rows > 0) {
		while ($rowp = $resultq->fetch_assoc()) {
			$fyearmonthlist = $rowp['financialyear_start'];
		}

		$fyearmonthname = explode("-", $fyearmonthlist);
		$month1 = $fyearmonthname[0];
		$month2 = $fyearmonthname[1];
		$nomonth1 = date("m", strtotime($month1));
		$nomonth2 = date("m", strtotime($month2));

		$financilyearsplit = explode("-", $financialyear);
		$fyear1 = $financilyearsplit[0];
		$fyear2 = $financilyearsplit[1];

		if ($periodselect == 'Annual' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = $fyear1 . "-" . $nomonth1 . "-01";
			$getdate2 = $fyear2 . "-" . $nomonth2 . "-31";
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		} else if ($periodselect == '1st Six Month' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = $fyear1 . "-" . $nomonth1 . "-01";
			$getdate2 = $fyear1 . "-" . "09" . "-30";
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		} else if ($periodselect == '2nd Six Month' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = $fyear1 . "-" . "10" . "-01";
			$getdate2 = $fyear2 . "-" . $nomonth2 . "-31";
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		} else if ($periodselect == '1st Quarter' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = $fyear1 . "-" . $nomonth1 . "-01";
			$getdate2 = $fyear1 . "-" . "06" . "-30";
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		} else if ($periodselect == '2nd Quarter' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = $fyear1 . "-" . "07" . "-01";
			$getdate2 = $fyear1 . "-" . "09" . "-30";
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		} else if ($periodselect == '3rd Quarter' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = $fyear1 . "-" . "10" . "-01";
			$getdate2 = $fyear1 . "-" . "12" . "-31";
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		} else if ($periodselect == '4th Quarter' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = $fyear2 . "-" . "01" . "-01";
			$getdate2 = $fyear2 . "-" . $nomonth2 . "-31";
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		} else if ($periodselect == 'Date Wise' && $nomonth1 == 4 && $nomonth2 == 3) {
			$getdate1 = date("Y-m-d");
			$getdate2 = date("Y-m-d");
			$datefinded[] = array('getdate' => $getdate1 . "@" . $getdate2);
		}
		return $datefinded;
	}
	return $period;
}


function gettransectionfirstdate($conn, $projectid, $account_id)
{
	$sqlee = "select valuedate as cheque_date from tbl_bankstatement where account_id='" . $account_id . "' and (tedit is null or tedit='') order by valuedate asc limit 1";
	$resultda = $conn->query($sqlee);
	if ($resultda->num_rows > 0) {
		$rowd = $resultda->fetch_assoc();
		$firstdate = $rowd['cheque_date'];
	} else {
		$firstdate = "";
	}
	return $firstdate;
}
function Gettransactionalfinancialyear($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "accountid") {
		$finyearfq = $conn->query("SELECT CASE WHEN MONTH(m.bookdate)>=4 THEN CONCAT(YEAR(m.bookdate),'-',YEAR(m.bookdate)+1) ELSE CONCAT(YEAR(m.bookdate)-1,'-',YEAR(m.bookdate)) END AS financial_year FROM tbl_maintransaction m inner join tbl_maintransaction_breakdown b where m.reference_no=b.reference_no and (m.Tedit is null or m.Tedit='') and (b.Tedit is null or b.Tedit='')  and m.payfromaccountid='" . $shortbyvalue . "' group by financial_year");
	} else if ($shortby == "projectid") {
		$finyearfq = $conn->query("SELECT CASE WHEN MONTH(m.bookdate)>=4 THEN CONCAT(YEAR(m.bookdate),'-',YEAR(m.bookdate)+1) ELSE CONCAT(YEAR(m.bookdate)-1,'-',YEAR(m.bookdate)) END AS financial_year FROM tbl_maintransaction m inner join tbl_maintransaction_breakdown b where m.reference_no=b.reference_no and (m.Tedit is null or m.Tedit='') and (b.Tedit is null or b.Tedit='')  and m.projectid='" . $shortbyvalue . "' group by financial_year");
	} else if ($shortby == "all") {
		$finyearfq = $conn->query("SELECT CASE WHEN MONTH(m.bookdate)>=4 THEN CONCAT(YEAR(m.bookdate),'-',YEAR(m.bookdate)+1) ELSE CONCAT(YEAR(m.bookdate)-1,'-',YEAR(m.bookdate)) END AS financial_year FROM tbl_maintransaction m inner join tbl_maintransaction_breakdown b where m.reference_no=b.reference_no and (m.Tedit is null or m.Tedit='') and (b.Tedit is null or b.Tedit='') and (m.bookdate is not null) group by financial_year");
	}
	if ($finyearfq->num_rows > 0) {
		while ($frow = $finyearfq->fetch_assoc()) {
			$finyearlist[] = array('financialyear' => $frow['financial_year']);
		}
	} else {
		$finyearlist = array();
	}
	return $finyearlist;
}

function gettransectionfirstdateprojectwise($conn, $projectid)
{
	$sqlee = "select bookdate as cheque_date from tbl_maintransaction where  projectid='" . $projectid . "' and (tedit is null or tedit='') order by bookdate asc limit 1";
	$resultda = $conn->query($sqlee);
	if ($resultda->num_rows > 0) {
		$rowd = $resultda->fetch_assoc();
		$firstdate = $rowd['cheque_date'];
	} else {
		$firstdate = "";
	}
	return $firstdate;
}
function gettransectionfirstdatecompanywise($conn, $companyid)
{
	$sqlee = "select bookdate as cheque_date from tbl_maintransaction where  companyid='" . $companyid . "' and (tedit is null or tedit='') order by bookdate asc limit 1";
	$resultda = $conn->query($sqlee);
	if ($resultda->num_rows > 0) {
		$rowd = $resultda->fetch_assoc();
		$firstdate = $rowd['cheque_date'];
	} else {
		$firstdate = "";
	}
	return $firstdate;
}
function gettransectionfirstdatesubcompanywise($conn, $subcompanyid)
{

	$sqlee = "select bookdate as cheque_date from tbl_maintransaction where  subcompanyid='" . $subcompanyid . "' and (tedit is null or tedit='') order by bookdate asc limit 1";
	$resultda = $conn->query($sqlee);
	if ($resultda->num_rows > 0) {
		$rowd = $resultda->fetch_assoc();
		$firstdate = $rowd['cheque_date'];
	} else {
		$firstdate = "";
	}
	return $firstdate;
}
function getexpentamountofpurpose_financialyear($conn, $companyid, $subcompanyid, $projectid, $purposecode, $financialyear)
{
	$financialyearexp = explode("-", $financialyear);
	$yearfirst = $financialyearexp[0];
	$yearsecond = $financialyearexp[1];

	$sqlee = "select m.reference_no, ifnull(sum(b.amount),0) as amount,b.purpose_code  FROM tbl_maintransaction m right join tbl_maintransaction_breakdown b on m.reference_no=b.reference_no and m.companyid=b.companyid and b.subcompanyid=b.subcompanyid and m.projectid=b.projectid and b.purpose_code='" . $purposecode . "' and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and Year(m.bookdate)>='" . $yearfirst . "' and Year(m.bookdate)<='" . $yearsecond . "' and (m.tedit is null or m.tedit='') and (b.tedit is null or b.tedit='')";
	$resultda = $conn->query($sqlee);
	if ($resultda->num_rows > 0) {
		$rowd = $resultda->fetch_assoc();
		$returnamount = $rowd['amount'];
	} else {
		$returnamount = "0";
	}
	return $returnamount;
}
function getinvoicetype($conn)
{
	$inq = $conn->query("select type FROM tbl_invoicetype_master where (Tedit is null or Tedit='')");
	if ($inq->num_rows > 0) {
		while ($inrow = $inq->fetch_assoc()) {
			$invoicetype[] = array('invoicetype' => $inrow['type']);
		}
	} else {
		$invoicetype = array();
	}
	return $invoicetype;
}
function getsaleinvoicetype($conn, $companyid, $subcompanyid, $projectid)
{
	$clinfo = "select id,invoicetype  FROM tbl_saleinvoicetypemaster where ifnull(tedit ,'') ='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('id' => $clrow['id'], 'invoicetype' => $clrow['invoicetype']);
		}
	}
	return $clarray;
}
function getdefaultinvoicetype($conn)
{
	$clinfo = "SELECT     id,    invdemotype,    JSON_OBJECT(
        'html_functionname', ifnull(html_functionname,''),
        'defaultvalues', ifnull(defaultvalues,''),  'invpath', ifnull(invpath,'')
    ) AS array_details FROM tbl_invoice_formattype where ifnull(tedit,'')=''";
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('id' => $clrow['id'], 'invdemotype' => $clrow['invdemotype'], 'array_details' => $clrow['array_details']);
		}
	}
	return $clarray;
}
function getdefaultfinyr($conn)
{
	$clinfo = "SELECT id, finyr  FROM tbl_companyfinyr_master where ifnull(tedit,'')=''";
	$clinfo_result = $conn->query($clinfo);
	$clarray = array();
	if ($clinfo_result->num_rows > 0) {
		while ($clrow = $clinfo_result->fetch_assoc()) {
			$clarray[] = array('id' => $clrow['id'], 'finyr' => $clrow['finyr']);
		}
	}
	return $clarray;
}


function getbeneficiaryinfo($conn)
{
	$bnfqry = $conn->query("select sno,name FROM tbl_beneficiaries_master where name is not null and name<>'' order by name");
	if ($bnfqry->num_rows > 0) {
		while ($brow = $bnfqry->fetch_assoc()) {
			$bnfarray[] = array('bsno' => $brow['sno'], 'beneficiaryname' => strtoupper($brow['name']));
		}
	} else {
		$bnfarray = array();
	}
	return $bnfarray;
}

function getbeneficiaryinfodetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$bnfqry = $conn->query("select sno,name FROM tbl_beneficiaries_master where (name is not null and name<>'') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' order by name");
	if ($bnfqry->num_rows > 0) {
		while ($brow = $bnfqry->fetch_assoc()) {
			$bnfarray[] = array('bsno' => $brow['sno'], 'beneficiaryname' => $brow['name']);
		}
	} else {
		$bnfarray = array();
	}
	return $bnfarray;
}

function getbillmasterinfodetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{

	$bnfqry = $conn->query("select id,typename FROM strs_billtypemaster_universal where (typename is not null and typename<>'')   order by typename");
	if ($bnfqry->num_rows > 0) {
		while ($brow = $bnfqry->fetch_assoc()) {
			$bnfarray[] = array('id' => $brow['id'], 'typename' => $brow['typename']);
		}
	} else {
		$bnfarray = array();
	}
	return $bnfarray;
}

function getbillmasterandbilltypemasterinfodetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$bnfqry = $conn->query("select a.id,a.typename,b.purposecode from strs_billtypemaster_universal as a join strs_billmaster_universal as b on a.id=typeofbill where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' group by a.typename ");
	if ($bnfqry->num_rows > 0) {
		while ($brow = $bnfqry->fetch_assoc()) {
			$bnfarray[] = array('id' => $brow['id'], 'typename' => $brow['typename'], 'purposecode' => $brow['purposecode'], 'typeofbill' => $brow['typeofbill']);
		}
	} else {
		$bnfarray = array();
	}
	return $bnfarray;
}

function getbillmasterandbilltypemasterinfodetailsdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$bnfqry = $conn->query("select purposecode,paytoid,beneficiaryname,billingdate,referenceno from strs_bill_transactions where typeofbillid='" . $shortbyvalue . "' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (referenceno is not null and referenceno<>'')");
	if ($bnfqry->num_rows > 0) {
		while ($brow = $bnfqry->fetch_assoc()) {
			$bnfarray[] = array('purposecode' => $brow['purposecode'], 'paytoid' => $brow['paytoid'], 'beneficiaryname' => $brow['beneficiaryname'], 'billingdate' => $brow['billingdate'], 'referenceno' => $brow['referenceno']);
		}
	} else {
		$bnfarray = array();
	}
	return $bnfarray;
}

function modeoftransfer($conn)
{
	$tq = "select modeoftransfer FROM tbl_transfermode_master where (Tedit is null or Tedit='')";
	$tq_rslt = $conn->query($tq);
	$modeoftransferlist = array();
	if ($tq_rslt->num_rows > 0) {
		while ($trw = $tq_rslt->fetch_assoc()) {
			$modeoftransferlist[] = array('modeoftransfer' => $trw['modeoftransfer']);
		}
	}
	return $modeoftransferlist;
}

function getpartialpaymentpayto($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM partial_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='partial' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') order by c.paytoname";
	} else if ($shortby == "payto") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM partial_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='partial' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(c.paytoname)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "paytolike") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM partial_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='partial' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and match(c.paytoname) against ('" . $shortbyvalue . "')";
	} else if ($shortby == "paytoid") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM partial_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='partial' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(c.paytoid)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "loginid") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM partial_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='partial' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and c.loginid='" . $shortbyvalue . "'";
	}
	$impq_rslt = $conn->query($impq);
	$impaytolist = array();
	if ($impq_rslt->num_rows > 0) {
		while ($imrow = $impq_rslt->fetch_assoc()) {
			$impaytolist[] = array('sno' => $imrow['sno'], 'paytoid' => $imrow['paytoid'], 'paytoname' => $imrow['paytoname'], 'accountid' => $imrow['accountid'], 'loginid' => $imrow['loginid'], 'bankid' => $imrow['bankid'], 'account_type' => $imrow['account_type'], 'accountname' => $imrow['accountname'], 'bankname' => $imrow['bankname'], 'accountno' => $imrow['accountno'], 'ifsccode' => $imrow['ifsccode']);
		}
	}
	return $impaytolist;
}

function getcashpaymentpayto($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM cash_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='cash' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') order by c.paytoname";
	} else if ($shortby == "payto") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM cash_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='cash' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(c.paytoname)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "paytolike") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM cash_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='cash' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and match(c.paytoname) against ('" . $shortbyvalue . "')";
	} else if ($shortby == "paytoid") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM cash_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='cash' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(c.paytoid)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "loginid") {
		$impq = "SELECT c.sno, c.companyid, c.subcompanyid, c.projectid, c.accountid, c.loginid, c.paytoid, c.paytoname, c.edatetime,m.bankid,m.account_type,m.accountname,m.bankname,m.accountno,m.ifsccode FROM cash_authorizedperson c join tbl_master_account m on c.accountid=m.accountid and c.companyid=m.cmp_id and c.subcompanyid=m.subcmp_id and c.projectid=m.projectid WHERE (c.tedit is null or c.tedit='') and (m.tedit is null or m.tedit='') and lcase(m.account_type)='cash' and c.companyid='" . $companyid . "' and c.subcompanyid='" . $subcompanyid . "' and c.projectid in ('" . str_replace(",", "','", $projectid) . "') and c.loginid='" . $shortbyvalue . "'";
	}
	$impq_rslt = $conn->query($impq);
	$cashpaytolist = array();
	if ($impq_rslt->num_rows > 0) {
		while ($imrow = $impq_rslt->fetch_assoc()) {
			$cashpaytolist[] = array('sno' => $imrow['sno'], 'paytoid' => $imrow['paytoid'], 'paytoname' => $imrow['paytoname'], 'accountid' => $imrow['accountid'], 'loginid' => $imrow['loginid'], 'bankid' => $imrow['bankid'], 'account_type' => $imrow['account_type'], 'accountname' => $imrow['accountname'], 'bankname' => $imrow['bankname'], 'accountno' => $imrow['accountno'], 'ifsccode' => $imrow['ifsccode']);
		}
	}
	return $cashpaytolist;
}

function getsupplierpaymentpayto($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$impq = "SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname,bankname,accountno,ifsccode,panno,gstno,mobileno,emailaddress FROM supplier_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid in ('" . str_replace(",", "','", $projectid) . "') order by paytoname";
	} else if ($shortby == "payto") {
		$impq = "SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname,bankname,accountno,ifsccode,panno,gstno,mobileno,emailaddress FROM supplier_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(paytoname)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "paytolike") {
		$impq = "SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname,bankname,accountno,ifsccode,panno,gstno,mobileno,emailaddress FROM supplier_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid in ('" . str_replace(",", "','", $projectid) . "') and match(paytoname) against ('" . $shortbyvalue . "')";
	} else if ($shortby == "paytoid") {
		$impq = "SELECT sno, companyid, subcompanyid, projectid, paytoid, paytoname,bankname,accountno,ifsccode,panno,gstno,mobileno,emailaddress FROM supplier_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(paytoid)='" . strtolower($shortbyvalue) . "'";
	}
	$impq_rslt = $conn->query($impq);
	$cashpaytolist = array();
	if ($impq_rslt->num_rows > 0) {
		while ($imrow = $impq_rslt->fetch_assoc()) {
			$cashpaytolist[] = array('sno' => $imrow['sno'], 'paytoid' => $imrow['paytoid'], 'paytoname' => $imrow['paytoname'], 'bankname' => $imrow['bankname'], 'accountno' => $imrow['accountno'], 'ifsccode' => $imrow['ifsccode'], 'panno' => $imrow['panno'], 'gstno' => $imrow['gstno'], 'mobileno' => $imrow['mobileno'], 'emailaddress' => $imrow['emailaddress']);
		}
	}
	return $cashpaytolist;
}

function getimprestpayto($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$impq = "select p.sno, p.imprestpaytoid, p.payto, p.mobile_no, p.imprest_accuntid, p.email_id, p.paytobankname, p.paytoaccountno, p.paytoifsccode, p.loginid, prj.companyid, prj.subcompanyid, prj.projectid FROM imprest_authorizedperson p join imprest_authorizedproject prj on p.imprestpaytoid=prj.imprestpaytoid WHERE (p.tedit is null or p.tedit='') and prj.companyid='" . $companyid . "' and prj.subcompanyid='" . $subcompanyid . "' and prj.projectid in ('" . str_replace(",", "','", $projectid) . "') order by p.payto";
	} else if ($shortby == "payto") {
		$impq = "select p.sno, p.imprestpaytoid, p.payto, p.mobile_no, p.imprest_accuntid, p.email_id, p.paytobankname, p.paytoaccountno, p.paytoifsccode, p.loginid, prj.companyid, prj.subcompanyid, prj.projectid FROM imprest_authorizedperson p join imprest_authorizedproject prj on p.imprestpaytoid=prj.imprestpaytoid WHERE (p.tedit is null or p.tedit='') and prj.companyid='" . $companyid . "' and prj.subcompanyid='" . $subcompanyid . "' and prj.projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(p.payto)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "paytolike") {
		$impq = "select p.sno, p.imprestpaytoid, p.payto, p.mobile_no, p.imprest_accuntid, p.email_id, p.paytobankname, p.paytoaccountno, p.paytoifsccode, p.loginid, prj.companyid, prj.subcompanyid, prj.projectid FROM imprest_authorizedperson p join imprest_authorizedproject prj on p.imprestpaytoid=prj.imprestpaytoid WHERE (p.tedit is null or p.tedit='') and prj.companyid='" . $companyid . "' and prj.subcompanyid='" . $subcompanyid . "' and prj.projectid in ('" . str_replace(",", "','", $projectid) . "')  and match(p.payto) against ('" . $shortbyvalue . "')";
	} else if ($shortby == "paytoid") {
		$impq = "select p.sno, p.imprestpaytoid, p.payto, p.mobile_no, p.imprest_accuntid, p.email_id, p.paytobankname, p.paytoaccountno, p.paytoifsccode, p.loginid, prj.companyid, prj.subcompanyid, prj.projectid FROM imprest_authorizedperson p join imprest_authorizedproject prj on p.imprestpaytoid=prj.imprestpaytoid WHERE (p.tedit is null or p.tedit='') and prj.companyid='" . $companyid . "' and prj.subcompanyid='" . $subcompanyid . "' and prj.projectid in ('" . str_replace(",", "','", $projectid) . "') and lcase(p.imprestpaytoid)='" . strtolower($shortbyvalue) . "'";
	} else if ($shortby == "loginid") {
		$impq = "select p.sno, p.imprestpaytoid, p.payto, p.mobile_no, p.imprest_accuntid, p.email_id, p.paytobankname, p.paytoaccountno, p.paytoifsccode, p.loginid, prj.companyid, prj.subcompanyid, prj.projectid FROM imprest_authorizedperson p join imprest_authorizedproject prj on p.imprestpaytoid=prj.imprestpaytoid WHERE (p.tedit is null or p.tedit='') and prj.companyid='" . $companyid . "' and prj.subcompanyid='" . $subcompanyid . "' and prj.projectid in ('" . str_replace(",", "','", $projectid) . "') and p.loginid='" . $shortbyvalue . "'";
	} else if ($shortby == "paytoemail") {
		$impq = "select p.sno, p.imprestpaytoid, p.payto, p.mobile_no, p.imprest_accuntid, p.email_id, p.paytobankname, p.paytoaccountno, p.paytoifsccode, p.loginid, prj.companyid, prj.subcompanyid, prj.projectid FROM imprest_authorizedperson p join imprest_authorizedproject prj on p.imprestpaytoid=prj.imprestpaytoid WHERE (p.tedit is null or p.tedit='') and prj.companyid='" . $companyid . "' and prj.subcompanyid='" . $subcompanyid . "' and prj.projectid in ('" . str_replace(",", "','", $projectid) . "') and p.email_id='" . $shortbyvalue . "'";

	}
	$impq_rslt = $conn->query($impq);
	$impaytolist = array();
	if ($impq_rslt->num_rows > 0) {
		while ($imrow = $impq_rslt->fetch_assoc()) {
			$impaytolist[] = array('sno' => $imrow['sno'], 'imprestpaytoid' => $imrow['imprestpaytoid'], 'imprestpayto' => $imrow['payto'], 'imprestpaytomobileno' => $imrow['mobile_no'], 'imprestaccuntid' => $imrow['imprest_accuntid'], 'imprestpaytoemail' => $imrow['email_id'], 'centreid' => $imrow['projectid'], 'projectid' => $imrow['projectid'], 'paytobankname' => $imrow['paytobankname'], 'paytoaccountno' => $imrow['paytoaccountno'], 'paytoifsccode' => $imrow['paytoifsccode']);
		}
	}
	return $impaytolist;
}

function loanauthorizeddetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	// Initialize the base query
	if ($shortby == "allwithcompanyid") {
		$baseQuery = "SELECT sno, companyid, subcompanyid, projectid, loanpaytoid, loanaccuntid, loantype, loanpaytoname, mobileno, emailid, paytobankname, paytoaccountno, paytoifsccode, purposecode, remark, loginby, edatetime FROM loan_authorizedmaster WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $companyid . "'";
		// No additional conditions needed, as the query already handles this.
	} else if ($shortby == "allwithprojectid") {
		$baseQuery = "SELECT sno, companyid, subcompanyid, projectid, loanpaytoid, loanaccuntid, loantype, loanpaytoname, mobileno, emailid, paytobankname, paytoaccountno, paytoifsccode, purposecode, remark, loginby, edatetime FROM loan_authorizedmaster WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "allwithloantype") {
		$baseQuery = "SELECT sno, companyid, subcompanyid, projectid, loanpaytoid, loanaccuntid, loantype, loanpaytoname, mobileno, emailid, paytobankname, paytoaccountno, paytoifsccode, purposecode, remark, loginby, edatetime FROM loan_authorizedmaster WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $companyid . "' and loantype='" . $shortbyvalue . "'";
	} else if ($shortby == "paytoid") {
		$baseQuery = "SELECT sno, companyid, subcompanyid, projectid, loanpaytoid, loanaccuntid, loantype, loanpaytoname, mobileno, emailid, paytobankname, paytoaccountno, paytoifsccode, purposecode, remark, loginby, edatetime FROM loan_authorizedmaster WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loanpaytoid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$baseQuery = "SELECT sno, companyid, subcompanyid, projectid, loanpaytoid, loanaccuntid, loantype, loanpaytoname, mobileno, emailid, paytobankname, paytoaccountno, paytoifsccode, purposecode, remark, loginby, edatetime FROM loan_authorizedmaster WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $companyid . "' and sno='" . $shortbyvalue . "'";
	}
	$result = $conn->query($baseQuery);
	$data = [];
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}
	return $data;
}

function getimprestaccountdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "sno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	} else if ($shortby == "toaccountid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and to_accountid='" . $shortbyvalue . "'";

	} else if ($shortby == "summarywithtoaccountid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, sum(imprest_amount) as imprest_amount, sum(adjustment_amount) as adjustment_amount, adjustedamount, rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and to_accountid='" . $shortbyvalue . "' group by to_accountid";
	} else if ($shortby == "summarywithtopaytoid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, sum(imprest_amount) as imprest_amount, sum(adjustment_amount) as adjustment_amount, adjustedamount, rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and paytoid='" . $shortbyvalue . "' group by to_accountid";
	} else if ($shortby == "toaccountno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and to_accountno='" . $shortbyvalue . "'";
	} else if ($shortby == "fromaccountid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and from_ccountid='" . $shortbyvalue . "'";
	} else if ($shortby == "fromaccountno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and from_ccountno='" . $shortbyvalue . "'";
	} else if ($shortby == "requestno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "referenceno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and reference_no='" . $shortbyvalue . "'";
	} else if ($shortby == "imprestpayto") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and imprest_payto='" . $shortbyvalue . "'";
	} else if ($shortby == "imprestpaytoid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, paytoid, imprest_payto, ifnull(imprest_amount, 0) as imprest_amount, ifnull(adjustment_amount, 0) as adjustment_amount, ifnull(adjustedamount,0) as adjustedamount, ifnull(rest_imprest_amount,0) as rest_imprest_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM imprest_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and paytoid='" . $shortbyvalue . "'";
	}
	$impq_result = $conn->query($impq) or die($conn->error);
	$resultlist = array();
	if ($impq_result->num_rows > 0) {
		while ($improw = $impq_result->fetch_assoc()) {
			$resultlist[] = array('sno' => $improw['sno'], 'idate' => $improw['idate'], 'countryid' => $improw['countryid'], 'companyid' => $improw['companyid'], 'subcompanyid' => $improw['subcompanyid'], 'projectid' => $improw['projectid'], 'from_accountid' => $improw['from_accountid'], 'from_accountno' => $improw['from_accountno'], 'to_accountid' => $improw['to_accountid'], 'to_accountno' => $improw['to_accountno'], 'request_no' => $improw['request_no'], 'reference_no' => $improw['reference_no'], 'paytoid' => $improw['paytoid'], 'imprest_payto' => $improw['imprest_payto'], 'imprest_amount' => $improw['imprest_amount'], 'adjustment_amount' => $improw['adjustment_amount'], 'adjustedamount' => $improw['adjustedamount'], 'rest_imprest_amount' => $improw['rest_imprest_amount'], 'purpose_category' => $improw['purpose_category'], 'purpose_categoryid' => $improw['purpose_categoryid'], 'purpose' => $improw['purpose'], 'purpose_code' => $improw['purpose_code'], 'beneficiary_name' => $improw['beneficiary_name'], 'description' => $improw['description'], 'special_remark' => $improw['special_remark'], 'adjustment_status' => $improw['adjustment_status'], 'document_status' => $improw['document_status'], 'document_name' => $improw['document_name'], 'document_path' => $improw['document_path'], 'invoice_type' => $improw['invoice_type'], 'invoice_no' => $improw['invoice_no'], 'blobfile' => $improw['blobfile'], 'blobfile_tblname' => $improw['blobfile_tblname'], 'websno' => $improw['websno'], 'mergewith' => $improw['mergewith'], 'upd_b' => $improw['upd_b']);
		}
	}
	return $resultlist;
}

function getcashaccountdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "sno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	} else if ($shortby == "toaccountid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and to_accountid='" . $shortbyvalue . "'";
	} else if ($shortby == "summarywithtoaccountid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, sum(cash_amount) as cash_amount, sum(adjustment_amount) as adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and to_accountid='" . $shortbyvalue . "' group by to_accountid";
	} else if ($shortby == "toaccountno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and to_accountno='" . $shortbyvalue . "'";
	} else if ($shortby == "fromaccountid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and from_ccountid='" . $shortbyvalue . "'";
	} else if ($shortby == "fromaccountno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and from_ccountno='" . $shortbyvalue . "'";
	} else if ($shortby == "requestno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "referenceno") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and reference_no='" . $shortbyvalue . "'";
	} else if ($shortby == "imprestpayto") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and cash_payto='" . $shortbyvalue . "'";
	} else if ($shortby == "cashpaytoid") {
		$impq = "select sno, idate, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, request_no, reference_no, cash_payto, cash_amount, adjustment_amount, adjustedamount, rest_cash_amount, purpose_category, purpose_categoryid, purpose, purpose_code, beneficiary_name, description, special_remark, adjustment_status, document_status, document_name, document_path, invoice_type, invoice_no, blobfile, blobfile_tblname, websno, mergewith, upd_b FROM cash_account WHERE (Tedit is null or Tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and paytoid='" . $shortbyvalue . "'";
	}
	$impq_result = $conn->query($impq) or die($conn->error);
	$resultlist = array();
	if ($impq_result->num_rows > 0) {
		while ($improw = $impq_result->fetch_assoc()) {
			$resultlist[] = array('sno' => $improw['sno'], 'idate' => $improw['idate'], 'countryid' => $improw['countryid'], 'companyid' => $improw['companyid'], 'subcompanyid' => $improw['subcompanyid'], 'projectid' => $improw['projectid'], 'from_accountid' => $improw['from_accountid'], 'from_accountno' => $improw['from_accountno'], 'to_accountid' => $improw['to_accountid'], 'to_accountno' => $improw['to_accountno'], 'request_no' => $improw['request_no'], 'reference_no' => $improw['reference_no'], 'cash_payto' => $improw['cash_payto'], 'cash_amount' => $improw['cash_amount'], 'adjustment_amount' => $improw['adjustment_amount'], 'adjustedamount' => $improw['adjustedamount'], 'purpose_category' => $improw['purpose_category'], 'purpose_categoryid' => $improw['purpose_categoryid'], 'purpose' => $improw['purpose'], 'purpose_code' => $improw['purpose_code'], 'beneficiary_name' => $improw['beneficiary_name'], 'description' => $improw['description'], 'special_remark' => $improw['special_remark'], 'adjustment_status' => $improw['adjustment_status'], 'document_status' => $improw['document_status'], 'document_name' => $improw['document_name'], 'document_path' => $improw['document_path'], 'invoice_type' => $improw['invoice_type'], 'invoice_no' => $improw['invoice_no'], 'blobfile' => $improw['blobfile'], 'blobfile_tblname' => $improw['blobfile_tblname'], 'websno' => $improw['websno'], 'mergewith' => $improw['mergewith'], 'upd_b' => $improw['upd_b']);
		}
	}
	return $resultlist;
}

function GetRestImprestAmount($conn, $companyid, $subcompanyid, $projectid, $paytoname)
{
	$query = "select imprest_payto, coalesce(sum(imprest_amount), 0) as ttl_imprest, coalesce(sum(adjustment_amount), 0) as ttl_padjustment, (coalesce(sum(imprest_amount), 0)-coalesce(sum(adjustment_amount), 0)) as rstimprest FROM imprest_account where (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and imprest_payto='" . $paytoname . "'";
	$quryresult = $conn->query($query) or die($conn->error);
	if ($quryresult->num_rows > 0) {
		$crow = $quryresult->fetch_assoc();
		extract($crow);
		$restimprest = $rstimprest;
	} else {
		$restimprest = 0;
	}
	return $restimprest;
}

function GetRestImprestAmountWithPaytoId($conn, $companyid, $subcompanyid, $projectid, $paytoid)
{
	$query = "select imprest_payto, coalesce(sum(imprest_amount), 0) as ttl_imprest, coalesce(sum(adjustment_amount), 0) as ttl_padjustment, (coalesce(sum(imprest_amount), 0)-coalesce(sum(adjustment_amount), 0)) as rstimprest FROM imprest_account where (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and paytoid='" . $paytoid . "'";
	$quryresult = $conn->query($query) or die($conn->error);
	if ($quryresult->num_rows > 0) {
		$crow = $quryresult->fetch_assoc();
		extract($crow);
		$restimprest = $rstimprest;
	} else {
		$restimprest = 0;
	}
	return $restimprest;
}


function GetRestPartialAmount($conn, $companyid, $subcompanyid, $projectid, $paytoname)
{
	$query = "SELECT imprest_payto, coalesce(sum(imprest_amount), 0) as ttl_partial, coalesce(sum(adjustment_amount), 0) as ttl_padjustment, (sum(imprest_amount)-coalesce(sum(adjustment_amount), 0)) as restpartial FROM partial_account where (Tedit is null or Tedit='') and imprest_payto='" . $paytoname . "' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and imprest_payto='" . $paytoname . "'";
	$quryresult = $conn->query($query);
	if ($quryresult->num_rows > 0) {
		$crow = $quryresult->fetch_assoc();
		extract($crow);
		$rstpartial = $restpartial;
	} else {
		$rstpartial = '0';
	}
	return $rstpartial;
}

function GetRestPartialAmountWithPaytoId($conn, $companyid, $subcompanyid, $projectid, $paytoid)
{
	$query = "SELECT imprest_payto, coalesce(sum(imprest_amount), 0) as ttl_partial, coalesce(sum(adjustment_amount), 0) as ttl_padjustment, (sum(imprest_amount)-coalesce(sum(adjustment_amount), 0)) as restpartial FROM partial_account where (Tedit is null or Tedit='')  and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and paytoid='" . $paytoid . "'";
	$quryresult = $conn->query($query);
	if ($quryresult->num_rows > 0) {
		$crow = $quryresult->fetch_assoc();
		extract($crow);
		$rstpartial = $restpartial;
	} else {
		$rstpartial = '0';
	}
	return $rstpartial;
}


function getblobfileinfo($conn, $blobrefid, $dbtablename)
{
	if ($dbtablename == "" || $dbtablename == null) {
		$dbtablename = 'tbl_paymentrequest_documents';
	}
	$bbqry = "select sno,documentid,rdatetime,requestno, beneficiaryname, amount, documentstring, filetype, filesize FROM $dbtablename WHERE documentid='" . $blobrefid . "' and (tedit is null or tedit='') order by sno asc limit 1";
	$bqry = $conn->query($bbqry);
	$blfilelist = array();
	if ($bqry->num_rows > 0) {
		while ($blrow = $bqry->fetch_assoc()) {
			$blfilelist[] = array('sno' => $blrow['sno'], 'documentid' => $blrow['documentid'], 'rdatetime' => $blrow['rdatetime'], 'requestno' => $blrow['requestno'], 'beneficiaryname' => $blrow['beneficiaryname'], 'amount' => $blrow['amount'], 'documentstring' => $blrow['documentstring'], 'filetype' => $blrow['filetype'], 'filesize' => $blrow['filesize']);
		}
	}
	return $blfilelist;
}

function getPurposeCodeMasterClient($conn, $companyid, $subcompanyid, $projectid, $shortby = "", $shortbyvalue = "")
{
	$where = "WHERE (tedit is null or tedit='') AND companyid = '" . $conn->real_escape_string($companyid) . "' 
              AND subcompanyid = '" . $conn->real_escape_string($subcompanyid) . "' 
              AND projectid = '" . $conn->real_escape_string($projectid) . "'";

	if ($shortby == "allactive") {
		$where .= " AND activestatus='active'";
	} else if ($shortby == "sno") {
		$where .= "AND LCASE(sno) = '" . strtolower($conn->real_escape_string($shortbyvalue)) . "'";
	} else if ($shortby == "providedcode") {
		$where .= "AND LCASE(providedcode) = '" . strtolower($conn->real_escape_string($shortbyvalue)) . "'";
	} else if ($shortby == "activewithclientsubcategory") {
		$where .= "AND LCASE(subcategoryid) = '" . strtolower($conn->real_escape_string($shortbyvalue)) . "'";
	}

	$sql = "SELECT * FROM purpose_codemaster_client $where order by subcategoryid, providedcode";
	$result = $conn->query($sql);

	$data = array();
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}

	return $data;
}

function getPurposeCodeMasterClient_Credit($conn, $companyid, $subcompanyid, $projectid, $shortby = "", $shortbyvalue = "")
{
	$where = "WHERE (tedit is null or tedit='') AND companyid = '" . $conn->real_escape_string($companyid) . "' 
              AND subcompanyid = '" . $conn->real_escape_string($subcompanyid) . "' 
              AND projectid = '" . $conn->real_escape_string($projectid) . "'";

	if ($shortby == "allactive") {
		$where .= " AND activestatus='active'";
	} else if ($shortby == "sno") {
		$where .= "AND LCASE(sno) = '" . strtolower($conn->real_escape_string($shortbyvalue)) . "'";
	} else if ($shortby == "providedcode") {
		$where .= "AND LCASE(providedcode) = '" . strtolower($conn->real_escape_string($shortbyvalue)) . "'";
	} else if ($shortby == "activewithclientsubcategory") {
		$where .= "AND LCASE(subcategoryid) = '" . strtolower($conn->real_escape_string($shortbyvalue)) . "'";
	}

	$sql = "SELECT * FROM purpose_codemaster_client_credit $where";
	$result = $conn->query($sql);

	$data = array();
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}

	return $data;
}



function GetPurposeInfofrommaster($conn, $companyid, $shortbyvalue)
{
	$purposemasterlist = array();
	$cmq1 = "select  distinct category,categoryid FROM main_category_master";
	$lgmq_rslt1 = $conn->query($cmq1);
	if ($lgmq_rslt1->num_rows > 0) {
		while ($lmrow = $lgmq_rslt1->fetch_assoc()) {
			$purposemasterlist[] = array('category' => $lmrow['category'], 'categoryid' => $lmrow['categoryid'], 'sub_categoryid' => '', 'sub_category' => '', 'purpose' => '', 'purposecode' => '', 'select' => "no");
			$cgmq = "select  distinct sub_category,sub_categoryid,orderby FROM purpose_code_master where categoryid='" . $lmrow['categoryid'] . "'";
			$lcgmq_rslt = $conn->query($cgmq);
			if ($lcgmq_rslt->num_rows > 0) {
				while ($lmorow = $lcgmq_rslt->fetch_assoc()) {
					$purposemasterlist[] = array('category' => '', 'categoryid' => '', 'sub_categoryid' => $lmorow['sub_categoryid'], 'sub_category' => $lmorow['sub_category'], 'purpose' => '', 'purposecode' => '', 'select' => "no");
					$cgomq = "select  purpose,purposecode  FROM purpose_code_master where sub_category='" . $lmorow['sub_category'] . "'";
					$lgomq_rslt = $conn->query($cgomq);
					if ($lgomq_rslt->num_rows > 0) {
						while ($lmoprow = $lgomq_rslt->fetch_assoc()) {
							$purposemasterlist[] = array('category' => '', 'categoryid' => '', 'sub_categoryid' => '', 'sub_category' => '', 'purpose' => $lmoprow['purpose'], 'purposecode' => $lmoprow['purposecode'], 'select' => "yes");
						}
					}
				}

			}

		}
	}
	return $purposemasterlist;
}

function GetPurposeCategoryInfofrommaster($conn, $companyid, $subcompanyid, $projectid, $category)
{
	$purposemasterlist = array();
	$cmq1 = "select  distinct sub_category,sub_categoryid FROM purpose_category_master where lcase(category)='" . $category . "' and (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	$lgmq_rslt1 = $conn->query($cmq1);
	if ($lgmq_rslt1->num_rows > 0) {
		while ($lmrow = $lgmq_rslt1->fetch_assoc()) {
			$purposemasterlist[] = array('subcategoryid' => $lmrow['sub_categoryid'], 'subcategory' => $lmrow['sub_category']);


		}
	}
	return $purposemasterlist;
}
function GetPurposeCategoryInfofrommasterdeposits($conn, $companyid, $subcompanyid, $projectid, $category)
{
	$purposemasterlist = array();
	$cmq1 = "select  distinct sub_category,sub_categoryid FROM purpose_category_master_credit where lcase(category)='" . $category . "' and (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	$lgmq_rslt1 = $conn->query($cmq1);
	if ($lgmq_rslt1->num_rows > 0) {
		while ($lmrow = $lgmq_rslt1->fetch_assoc()) {
			$purposemasterlist[] = array('subcategoryid' => $lmrow['sub_categoryid'], 'subcategory' => $lmrow['sub_category']);


		}
	}
	return $purposemasterlist;
}


function getauthorizedaccountids($conn, $companyid, $subcompanyid, $projectid, $loginid)
{
	$ssq = $conn->query("select group_concat(accountid) as accountid FROM login_authorized_banks where cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $companyid) . "') and projectid in('" . str_replace(",", "','", $projectid) . "') and loginid='" . $loginid . "' group by loginid");
	$accountlist = "";
	if ($ssq->num_rows > 0) {
		$acrow = $ssq->fetch_assoc();
		$accountlist = $acrow['accountid'];
	}
	return $accountlist;
}

function getauthorizedmodules($conn, $companyid, $subcompanyid, $projectid, $loginid)
{
	$mssq = $conn->query("select group_concat(module) as module FROM login_authorized_module where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' and isactive='active' group by loginid");
	$modulelist = "";
	if ($mssq->num_rows > 0) {
		$mdrow = $mssq->fetch_assoc();
		$modulelist = $mdrow['module'];
	}
	return $modulelist;
}

function getauthorizedprojects_old($conn, $companyid, $subcompanyid, $projectid, $loginid, $shortby, $shortbyvalue)
{
	if ($shortby == "authorizedcompanylist") {
		$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and loginid='" . $loginid . "' and status='active' gruoup by companyid order by projectid");
	} else if ($shortby == "authorizedsubcompanylist") {
		$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and loginid='" . $loginid . "' and status='active' gruoup by subcompanyid order by projectid");
	} else if ($shortby == "authorizedprojectlist") {
		$prssq = $conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and loginid='" . $loginid . "' and status='active' gruoup by projectid order by projectid");
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

function allauthorizedcompanyid_old($conn, $loginid)
{
	$prssq = $conn->query("select distinct GROUP_CONCAT(companyid) as companyid FROM login_authorized_projects where loginid='" . $loginid . "' and status='active'");
	if ($prssq->num_rows > 0) {
		$crow = $prssq->fetch_assoc();
		return $crow['companyid'];
	}
}

function allauthorizedsubcompanyid_old($conn, $loginid)
{
	$prbssq = $conn->query("select distinct GROUP_CONCAT(subcompanyid) as subcompanyid FROM login_authorized_projects where loginid='" . $loginid . "' and status='active'");
	if ($prbssq->num_rows > 0) {
		$scrow = $prbssq->fetch_assoc();
		return $scrow['subcompanyid'];
	}
}

function allauthorizedprojectid_old($conn, $loginid)
{
	$prjq = $conn->query("select distinct GROUP_CONCAT(projectid) as projectid FROM login_authorized_projects where loginid='" . $loginid . "' and status='active'");
	if ($prjq->num_rows > 0) {
		$prrow = $prjq->fetch_assoc();
		return $prrow['projectid'];
	}
}

function allauthorizedmoduleid_old($conn, $companyid, $subcompanyid, $projectid, $loginid)
{
	$auldq = $conn->query("SELECT distinct GROUP_CONCAT(moduleid) as moduleid FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' order by moduleid");
	if ($auldq->num_rows > 0) {
		$mdrow = $auldq->fetch_assoc();
		return $mdrow['moduleid'];
	}
}


function check_maintransactionamount_ttlbreakdowntamount($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "requestno") {
		$bbnkqq = "select request_no, reference_no, bookdate, transaction_date, book_amount, transfered_amount, (SELECT sum(amount) FROM tbl_maintransaction_breakdown where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_no='" . $shortbyvalue . "') as ttlbreakdownamount FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "referenceno") {
		$bbnkqq = "select request_no, reference_no, bookdate, transaction_date, book_amount, transfered_amount, (SELECT sum(amount) FROM tbl_maintransaction_breakdown where (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and reference_no='" . $shortbyvalue . "') as ttlbreakdownamount FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and reference_no='" . $shortbyvalue . "'";
	}
	$bbnkqqr = $conn->query($bbnkqq);
	$chkarray = array();
	if ($bbnkqqr->num_rows > 0) {
		while ($ttrow = $bbnkqqr->fetch_assoc()) {
			$chkarray[] = array('request_no' => $ttrow['request_no'], 'reference_no' => $ttrow['reference_no'], 'bookdate' => $ttrow['bookdate'], 'transaction_date' => $ttrow['transaction_date'], 'book_amount' => $ttrow['book_amount'], 'transfered_amount' => $ttrow['transfered_amount'], 'ttlbreakdownamount' => $ttrow['ttlbreakdownamount']);
		}
	}
	return $chkarray;
}

function getbookdata($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sscont = "select request_no, reference_no, transactionno, bookdate, paymentduedate, finyear, transaction_date, book_amount, transfered_amount, payto, paytoid, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, book_source FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (book_amount >0) and (reconciliation is null or reconciliation='')";
	} else if ($shortby == "accountidwithnullaccountid") {
		$sscont = "select request_no, reference_no, transactionno, bookdate, paymentduedate, finyear, transaction_date, book_amount, transfered_amount, payto, paytoid, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, book_source FROM tbl_maintransaction WHERE (tedit is null or tedit='') and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and payfromaccountid in('" . $shortbyvalue . "', '') and (book_amount >0) and (reconciliation is null or reconciliation='')";
	}
	$sscont_result = $conn->query($sscont);
	$expensescontent = array();
	if ($sscont_result->num_rows > 0) {
		$i = 1;
		while ($emcrow = $sscont_result->fetch_assoc()) {
			$classM = "classX_" . $i;
			$expensescontent[] = array('finyear' => $emcrow['finyear'], 'request_no' => $emcrow['request_no'], 'reference_no' => $emcrow['reference_no'], 'transactionno' => $emcrow['transactionno'], 'paymentduedate' => $emcrow['paymentduedate'], 'transaction_date' => $emcrow['transaction_date'], 'bookdate' => $emcrow['bookdate'], 'book_amount' => $emcrow['book_amount'], 'transfered_amount' => $emcrow['transfered_amount'], 'payto' => $emcrow['payto'], 'paytoid' => $emcrow['paytoid'], 'payfrombankname' => $emcrow['payfrombankname'], 'payfromaccountno' => $emcrow['payfromaccountno'], 'payfromifsccode' => $emcrow['payfromifsccode'], 'payfromaccountid' => $emcrow['payfromaccountid'], 'payfrombankid' => $emcrow['payfrombankid'], 'paytoaccountno' => $emcrow['paytoaccountno'], 'paytobankname' => $emcrow['paytobankname'], 'paytoifsc' => $emcrow['paytoifsc'], 'paytoaccountid' => $emcrow['paytoaccountid'], 'reconciliation' => $emcrow['reconciliation'], 'book_source' => $emcrow['book_source'], 'classM' => $classM);
			$i = $i + 1;
		}
	}
	return $expensescontent;
}

function get_maintransactions_details($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "peding_utrno_update_withprojectid") {
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and (transactionno is null or transactionno='') order by YEAR(bookdate) DESC, MONTH(bookdate) DESC, DAY(bookdate) DESC";
	} else if ($shortby == "updated_utrno_withprojectid") {
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and (transactionno is not null and transactionno!='')";
	} else if ($shortby == "peding_utrno_update_withsubcompanyid") {
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and (transactionno is null or transactionno='')";
	} else if ($shortby == "sno") {
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	} else if ($shortby == "referenceno") {
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and reference_no='" . $shortbyvalue . "'";
	} else if ($shortby == "requestno") {
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "accountidwithnullaccountid") {
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  and payfromaccountid in('" . $shortbyvalue . "', '') and (book_amount >0) and (reconciliation is null or reconciliation='')";
	} else if ($shortby == "unreconcilieddata_accountid_withdates") {
		$dexp = explode("|", $shortbyvalue);
		$accountid = $dexp[0];
		$rdatefrom = $dexp[1];
		$rdateto = $dexp[2];
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and (reconciliation is null or reconciliation='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  and payfromaccountid in('" . $accountid . "', '', 'null') and bookdate>='" . $rdatefrom . "' and bookdate<='" . $rdateto . "' and (book_amount >0)";
	} else if ($shortby == "unreconcilieddata_withaccountid") {
		if (strtolower($shortbyvalue) == 'all') {
			$fromaccid_cd = "";
		} else {
			$fromaccid_cd = "and payfromaccountid in('" . $shortbyvalue . "', '', 'null')";
		}
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and (reconciliation is null or reconciliation='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  " . $fromaccid_cd . " and (book_amount >0)";
	} else if ($shortby == "betweendatewillall") {
		$dexp = explode("|", $shortbyvalue);
		$rdatefrom = $dexp[0];
		$rdateto = $dexp[1];
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and bookdate>='" . $rdatefrom . "' and bookdate<='" . $rdateto . "' order by transaction_date desc";
	} else if ($shortby == "pedingtransactionbetweendate") {
		$dexp = explode("|", $shortbyvalue);
		$rdatefrom = $dexp[0];
		$rdateto = $dexp[1];
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and bookdate>='" . $rdatefrom . "' and bookdate<='" . $rdateto . "' and (transactionno is null or transactionno='')";
	} else if ($shortby == "donetransactionbetweendate") {
		$dexp = explode("|", $shortbyvalue);
		$rdatefrom = $dexp[0];
		$rdateto = $dexp[1];
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and bookdate>='" . $rdatefrom . "' and bookdate<='" . $rdateto . "' and transactionno is not null and transactionno<>''";
	} else if ($shortby == "transferedpayment_betweendate") {
		$dexp = explode("|", $shortbyvalue);
		$rdatefrom = $dexp[0];
		$rdateto = $dexp[1];
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and transaction_date>='" . $rdatefrom . "' and transaction_date<='" . $rdateto . "' and transactionno is not null and transactionno<>'' and transaction_date is not null and transaction_date<>''";
	} else if ($shortby == "betweendatewillall") {
		$dexp = explode("|", $shortbyvalue);
		$rdatefrom = $dexp[0];
		$rdateto = $dexp[1];
		$bbnkqq = "select sno, countryid, companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, ifnull(transfered_amount, 0) AS transfered_amount, beforeedit_amount, taxableamount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction, is_intermediate_transfer,requesteddate,requestedby,approvedby,transferedby FROM tbl_maintransaction WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and bookdate>='" . $rdatefrom . "' and bookdate<='" . $rdateto . "' order by YEAR(transaction_date) DESC, MONTH(transaction_date) DESC, day(transaction_date) DESC";
	}

	$bbnkq = $conn->query($bbnkqq) or die($conn->error);
	$bnkstslist = array();
	if ($bbnkq->num_rows > 0) {
		while ($mtrow = $bbnkq->fetch_assoc()) {
			$paymentduedate = "N/A";
			if ($mtrow['paymentduedate']) {
				$paymentduedate = date('d-M-Y', strtotime($mtrow['paymentduedate']));
			}
			$rrf = get_maintransactionsbreakdown_details($conn, $companyid, $subcompanyid, $projectid, "referenceno", $mtrow['reference_no']);
			$purposename = "";
			$pcatid = "";
			$psubcatid = "";
			$ppcode = "";

			// purpose_category
			// purpose_categoryid
			// purpose_subcategory
			// purpose_subcategoryid
			// purpose
			// purpose_code

			if (count($rrf) > 0) {
				if (count($rrf) == 1) {
					foreach ($rrf as $rrfkey) {
						$purposename = $rrfkey['purpose'];
						$pcatid = $rrfkey['purpose_categoryid'];
						$psubcatid = $rrfkey['purpose_subcategoryid'];
						$ppcode = $rrfkey['purpose_code'];
					}
				} else {
					$purposename = "Multiple";
					$pcatid = "Multiple";
					$psubcatid = "Multiple";
					$ppcode = "Multiple";
				}
			}

			if (floatval($mtrow['transfered_amount']) <= 0) {
				$transacactionamount = $mtrow['book_amount'];
			} else {
				$transacactionamount = $mtrow['transfered_amount'];
			}

			$bnkstslist[] = array('sno' => $mtrow['sno'], 'countryid' => $mtrow['countryid'], 'companyid' => $mtrow['companyid'], 'subcompanyid' => $mtrow['subcompanyid'], 'projectid' => $mtrow['projectid'], 'subprojectid' => $mtrow['subprojectid'], 'request_no' => $mtrow['request_no'], 'reference_no' => $mtrow['reference_no'], 'transactionno' => $mtrow['transactionno'], 'bookdate' => $mtrow['bookdate'], 'bookdatedsp' => date('d-M-Y', strtotime($mtrow['bookdate'])), 'paymentduedate' => $paymentduedate, 'transaction_date' => $mtrow['transaction_date'], 'transaction_type' => $mtrow['transaction_type'], 'book_amount' => $mtrow['book_amount'], 'transfered_amount' => $transacactionamount, 'beforeedit_amount' => $mtrow['beforeedit_amount'], 'taxableamount' => $mtrow['taxableamount'], 'paytoid' => $mtrow['paytoid'], 'payto' => $mtrow['payto'], 'payfrombankname' => $mtrow['payfrombankname'], 'payfromaccountno' => $mtrow['payfromaccountno'], 'lastfourdigitfrmacno' => '***' . substr($mtrow['payfromaccountno'], -4), 'payfromifsccode' => $mtrow['payfromifsccode'], 'payfromaccountid' => $mtrow['payfromaccountid'], 'payfrombankid' => $mtrow['payfrombankid'], 'paymenttype' => $mtrow['paymenttype'], 'beneficierie' => $mtrow['beneficierie'], 'paytoaccountno' => $mtrow['paytoaccountno'], 'paytobankname' => $mtrow['paytobankname'], 'paytoifsc' => $mtrow['paytoifsc'], 'paytoaccountid' => $mtrow['paytoaccountid'], 'reconciliation' => $mtrow['reconciliation'], 'reconciliation_banksno' => $mtrow['reconciliation_banksno'], 'transferreceiptpath' => $mtrow['transferreceiptpath'], 'todaydate' => date('Y-m-d'), 'is_purchaseforproduction' => $mtrow['is_purchaseforproduction'], 'is_intermediate_transfer' => $mtrow['is_intermediate_transfer'], 'temppurposecategoryid' => $pcatid, 'temppurposesubcategoryid' => $psubcatid, 'temppurposecode' => $ppcode, 'temppurposename' => $purposename, 'requesteddate' => $mtrow['requesteddate'], 'requestedby' => $mtrow['requestedby'], 'approvedby' => $mtrow['approvedby'], 'transferedby' => $mtrow['transferedby']);

		}
	}
	return $bnkstslist;
}


function get_maintransactionsbreakdown_details($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "referenceno") {
		$bbnkqq = "SELECT b.sno, b.countryid, b.companyid, b.subcompanyid, b.projectid, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.tds_type, b.tds_percent, b.tds_amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code, b.provided_code, b.purpose2, b.purpose_code2, b.invoice_type, b.invoice_no, b.invoicedate, b.taxableamount, b.gstamount, b.description, b.description_bank, b.complet_purposcode, b.empid, b.documentation, b.documentation_path, b.audiofilepath, b.voiceinstid, b.srm_emailsno, b.srm_attsno, b.doctype, b.blobfile, b.blobfile_tblname, b.is_merger, b.sno_merger, b.u_reference_no, b.verifybook, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid FROM tbl_maintransaction_breakdown b join tbl_maintransaction m on b.reference_no=m.reference_no WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "' and b.projectid='" . $projectid . "' and b.reference_no='" . $shortbyvalue . "'";
	} else if ($shortby == "requestno") {
		$bbnkqq = "SELECT b.sno, b.countryid, b.companyid, b.subcompanyid, b.projectid, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.tds_type, b.tds_percent, b.tds_amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code, b.provided_code, b.purpose2, b.purpose_code2, b.invoice_type, b.invoice_no, b.invoicedate, b.taxableamount, b.gstamount, b.description, b.description_bank, b.complet_purposcode, b.empid, b.documentation, b.documentation_path, b.audiofilepath, b.voiceinstid, b.srm_emailsno, b.srm_attsno, b.doctype, b.blobfile, b.blobfile_tblname, b.is_merger, b.sno_merger, b.u_reference_no, b.verifybook, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid FROM tbl_maintransaction_breakdown b join tbl_maintransaction m on b.reference_no=m.reference_no WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "' and b.projectid='" . $projectid . "' and b.request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "unverifieddata") {
		$bbnkqq = "SELECT b.sno, b.countryid, b.companyid, b.subcompanyid, b.projectid, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.tds_type, b.tds_percent, b.tds_amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code, b.provided_code, b.purpose2, b.purpose_code2, b.invoice_type, b.invoice_no, b.invoicedate, b.taxableamount, b.gstamount, b.description, b.description_bank, b.complet_purposcode, b.empid, b.documentation, b.documentation_path, b.audiofilepath, b.voiceinstid, b.srm_emailsno, b.srm_attsno, b.doctype, b.blobfile, b.blobfile_tblname, b.is_merger, b.sno_merger, b.u_reference_no, b.verifybook, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid FROM tbl_maintransaction_breakdown b join tbl_maintransaction m on b.reference_no=m.reference_no WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "' and b.projectid='" . $projectid . "' and (b.verifybook is null or b.verifybook='')";
	}
	$bbnkq = $conn->query($bbnkqq) or die($conn->error);
	$mbrklist = array();
	if ($bbnkq->num_rows > 0) {
		while ($mbrow = $bbnkq->fetch_assoc()) {
			$documentpath = "";
			if (file_exists('../' . $mbrow['documentation_path'])) {
				$documentpath = $mbrow['documentation_path'];
			}

			$mbrklist[] = array('sno' => $mbrow['sno'], 'countryid' => $mbrow['countryid'], 'companyid' => $mbrow['companyid'], 'subcompanyid' => $mbrow['subcompanyid'], 'projectid' => $mbrow['projectid'], 'subprojectid' => $mbrow['subprojectid'], 'request_no' => $mbrow['request_no'], 'reference_no' => $mbrow['reference_no'], 'beneficiary_name' => $mbrow['beneficiary_name'], 'amount' => $mbrow['amount'], 'tds_type' => $mbrow['tds_type'], 'tds_percent' => $mbrow['tds_percent'], 'tds_amount' => $mbrow['tds_amount'], 'purpose_category' => $mbrow['purpose_category'], 'purpose_categoryid' => $mbrow['purpose_categoryid'], 'purpose_subcategory' => $mbrow['purpose_subcategory'], 'purpose_subcategoryid' => $mbrow['purpose_subcategoryid'], 'purpose' => $mbrow['purpose'], 'purpose_code' => $mbrow['purpose_code'], 'provided_code' => $mbrow['provided_code'], 'purposewithcode' => $mbrow['purpose'] . '(' . $mbrow['purpose_code'] . ')', 'purpose2' => $mbrow['purpose2'], 'purpose_code2' => $mbrow['purpose_code2'], 'invoice_type' => $mbrow['invoice_type'], 'invoice_no' => $mbrow['invoice_no'], 'invoicedate' => $mbrow['invoicedate'], 'taxableamount' => $mbrow['taxableamount'], 'gstamount' => $mbrow['gstamount'], 'description' => $mbrow['description'], 'description_bank' => $mbrow['description_bank'], 'complet_purposcode' => $mbrow['complet_purposcode'], 'empid' => $mbrow['empid'], 'documentation' => $mbrow['documentation'], 'documentation_path' => $documentpath, 'audiofilepath' => $mbrow['audiofilepath'], 'voiceinstid' => $mbrow['voiceinstid'], 'srm_emailsno' => $mbrow['srm_emailsno'], 'srm_attsno' => $mbrow['srm_attsno'], 'doctype' => $mbrow['doctype'], 'blobfile' => $mbrow['blobfile'], 'blobfile_tblname' => $mbrow['blobfile_tblname'], 'is_merger' => $mbrow['is_merger'], 'sno_merger' => $mbrow['sno_merger'], 'u_reference_no' => $mbrow['u_reference_no'], 'verifybook' => $mbrow['verifybook'], 'paytoid' => $mbrow['paytoid'], 'payto' => $mbrow['payto'], 'finyear' => $mbrow['finyear'], 'bookdate' => $mbrow['bookdate'], 'payfromaccountid' => $mbrow['payfromaccountid']);
		}
	}
	return $mbrklist;
}

function get_maintransactionsbreakdown_details_credit($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "referenceno") {
		$bbnkqq = "SELECT b.sno, b.countryid, b.companyid, b.subcompanyid, b.projectid, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.tds_type, b.tds_percent, b.tds_amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code, b.purpose2, b.purpose_code2, b.invoice_type, b.invoice_no, b.invoicedate, b.taxableamount, b.gstamount, b.description, b.description_bank, b.complet_purposcode, b.empid, b.documentation, b.documentation_path, b.audiofilepath, b.voiceinstid, b.srm_emailsno, b.srm_attsno, b.doctype, b.blobfile, b.blobfile_tblname, b.is_merger, b.sno_merger, b.u_reference_no, b.verifybook, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid FROM tbl_maintransaction_credit_breakdown b join tbl_maintransaction_credit m on b.reference_no=m.reference_no WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "' and b.projectid='" . $projectid . "' and b.reference_no='" . $shortbyvalue . "'";
	} else if ($shortby == "requestno") {
		$bbnkqq = "SELECT b.sno, b.countryid, b.companyid, b.subcompanyid, b.projectid, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.tds_type, b.tds_percent, b.tds_amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code, b.purpose2, b.purpose_code2, b.invoice_type, b.invoice_no, b.invoicedate, b.taxableamount, b.gstamount, b.description, b.description_bank, b.complet_purposcode, b.empid, b.documentation, b.documentation_path, b.audiofilepath, b.voiceinstid, b.srm_emailsno, b.srm_attsno, b.doctype, b.blobfile, b.blobfile_tblname, b.is_merger, b.sno_merger, b.u_reference_no, b.verifybook, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid FROM tbl_maintransaction_credit_breakdown b join tbl_maintransaction_credit m on b.reference_no=m.reference_no WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "' and b.projectid='" . $projectid . "' and b.request_no='" . $shortbyvalue . "'";
	} else if ($shortby == "unverifieddata") {
		$bbnkqq = "SELECT b.sno, b.countryid, b.companyid, b.subcompanyid, b.projectid, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.tds_type, b.tds_percent, b.tds_amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code, b.purpose2, b.purpose_code2, b.invoice_type, b.invoice_no, b.invoicedate, b.taxableamount, b.gstamount, b.description, b.description_bank, b.complet_purposcode, b.empid, b.documentation, b.documentation_path, b.audiofilepath, b.voiceinstid, b.srm_emailsno, b.srm_attsno, b.doctype, b.blobfile, b.blobfile_tblname, b.is_merger, b.sno_merger, b.u_reference_no, b.verifybook, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid FROM tbl_maintransaction_credit_breakdown b join tbl_maintransaction_credit m on b.reference_no=m.reference_no WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "' and b.projectid='" . $projectid . "' and (b.verifybook is null or b.verifybook='')";
	}
	$bbnkq = $conn->query($bbnkqq) or die($conn->error);
	$mbrklist = array();
	if ($bbnkq->num_rows > 0) {
		while ($mbrow = $bbnkq->fetch_assoc()) {
			$mbrklist[] = array('sno' => $mbrow['sno'], 'countryid' => $mbrow['countryid'], 'companyid' => $mbrow['companyid'], 'subcompanyid' => $mbrow['subcompanyid'], 'projectid' => $mbrow['projectid'], 'subprojectid' => $mbrow['subprojectid'], 'request_no' => $mbrow['request_no'], 'reference_no' => $mbrow['reference_no'], 'beneficiary_name' => $mbrow['beneficiary_name'], 'amount' => $mbrow['amount'], 'tds_type' => $mbrow['tds_type'], 'tds_percent' => $mbrow['tds_percent'], 'tds_amount' => $mbrow['tds_amount'], 'purpose_category' => $mbrow['purpose_category'], 'purpose_categoryid' => $mbrow['purpose_categoryid'], 'purpose_subcategory' => $mbrow['purpose_subcategory'], 'purpose_subcategoryid' => $mbrow['purpose_subcategoryid'], 'purpose' => $mbrow['purpose'], 'purpose_code' => $mbrow['purpose_code'], 'purposewithcode' => $mbrow['purpose'] . '(' . $mbrow['purpose_code'] . ')', 'purpose2' => $mbrow['purpose2'], 'purpose_code2' => $mbrow['purpose_code2'], 'invoice_type' => $mbrow['invoice_type'], 'invoice_no' => $mbrow['invoice_no'], 'invoicedate' => $mbrow['invoicedate'], 'taxableamount' => $mbrow['taxableamount'], 'gstamount' => $mbrow['gstamount'], 'description' => $mbrow['description'], 'description_bank' => $mbrow['description_bank'], 'complet_purposcode' => $mbrow['complet_purposcode'], 'empid' => $mbrow['empid'], 'documentation' => $mbrow['documentation'], 'documentation_path' => $mbrow['documentation_path'], 'audiofilepath' => $mbrow['audiofilepath'], 'voiceinstid' => $mbrow['voiceinstid'], 'srm_emailsno' => $mbrow['srm_emailsno'], 'srm_attsno' => $mbrow['srm_attsno'], 'doctype' => $mbrow['doctype'], 'blobfile' => $mbrow['blobfile'], 'blobfile_tblname' => $mbrow['blobfile_tblname'], 'is_merger' => $mbrow['is_merger'], 'sno_merger' => $mbrow['sno_merger'], 'u_reference_no' => $mbrow['u_reference_no'], 'verifybook' => $mbrow['verifybook'], 'paytoid' => $mbrow['paytoid'], 'payto' => $mbrow['payto'], 'finyear' => $mbrow['finyear'], 'bookdate' => $mbrow['bookdate'], 'payfromaccountid' => $mbrow['payfromaccountid']);
		}
	}
	return $mbrklist;
}

function getpendingreconcilebookstatement($conn, $toaccountid, $datefrom, $dateto, $purposecode, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$bbnkqq = "select m.sno,m.reference_no,m.etransaction_no,m.etdate,m.cheque_no,m.cheque_date,m.payto,m.total_amount,m.paybankname,m.payaccountno,m.paymenttype,m.transactiontype,m.bank_id,m.account_id,m.paytoaccount,m.paytobankname,m.paytoifsc,m.centre_id,b.amount,b.purpose,b.purpose_code,b.beneficierie_name,b.description,b.centreid,b.invoice_type,b.invoice_no,m.to_account_id FROM tbl_maintransaction m, tbl_maintransaction_breakdown b where m.reference_no=b.reference_no and m.cheque_date>='" . date('Y-m-d', strtotime($datefrom)) . "' and m.cheque_date<='" . date('Y-m-d', strtotime($dateto)) . "' and b.purpose_code in ('" . str_replace(",", "','", $purposecode) . "') and m.to_account_id='" . $toaccountid . "' and (creditflag is null or creditflag='') and (etransaction_no is not null and etransaction_no<>'')";
	} else if ($shortby == "sno") {
		$bbnkqq = "select m.sno,m.reference_no,m.etransaction_no,m.etdate,m.cheque_no,m.cheque_date,m.payto,m.total_amount,m.paybankname,m.payaccountno,m.paymenttype,m.transactiontype,m.bank_id,m.account_id,m.paytoaccount,m.paytobankname,m.paytoifsc,m.centre_id,b.amount,b.purpose,b.purpose_code,b.beneficierie_name,b.description,b.centreid,b.invoice_type,b.invoice_no,m.to_account_id FROM tbl_maintransaction m, tbl_maintransaction_breakdown b where m.reference_no=b.reference_no and m.cheque_date>='" . date('Y-m-d', strtotime($datefrom)) . "' and m.cheque_date<='" . date('Y-m-d', strtotime($dateto)) . "' and b.purpose_code in ('" . str_replace(",", "','", $purposecode) . "') and m.to_account_id='" . $toaccountid . "' and m.sno='" . $shortbyvalue . "'";
	} else if ($shortby == "equalamount") {
		$bbnkqq = "select m.sno,m.reference_no,m.etransaction_no,m.etdate,m.cheque_no,m.cheque_date,m.payto,m.total_amount,m.paybankname,m.payaccountno,m.paymenttype,m.transactiontype,m.bank_id,m.account_id,m.paytoaccount,m.paytobankname,m.paytoifsc,m.centre_id,b.amount,b.purpose,b.purpose_code,b.beneficierie_name,b.description,b.centreid,b.invoice_type,b.invoice_no,m.to_account_id FROM tbl_maintransaction m, tbl_maintransaction_breakdown b where m.reference_no=b.reference_no and m.cheque_date>='" . date('Y-m-d', strtotime($datefrom)) . "' and m.cheque_date<='" . date('Y-m-d', strtotime($dateto)) . "' and b.purpose_code in ('" . str_replace(",", "','", $purposecode) . "') and m.to_account_id='" . $toaccountid . "' and m.total_amount='" . $shortbyvalue . "' and (creditflag is not null and creditflag<>'') and (etransaction_no is null or etransaction_no='')";
	}
	$bbnkq = $conn->query($bbnkqq) or die($conn->error);
	$bnkstslist = array();
	if ($bbnkq->num_rows > 0) {
		while ($bnkrow = $bbnkq->fetch_assoc()) {
			$bnkstslist[] = array('sno' => $bnkrow['sno'], 'reference_no' => $bnkrow['reference_no'], 'etransaction_no' => $bnkrow['etransaction_no'], 'etdate' => $bnkrow['etdate'], 'cheque_no' => $bnkrow['cheque_no'], 'cheque_date' => $bnkrow['cheque_date'], 'payto' => $bnkrow['payto'], 'amount' => $bnkrow['total_amount'], 'paybankname' => $bnkrow['paybankname'], 'payaccountno' => $bnkrow['payaccountno'], 'bank_id' => $bnkrow['bank_id'], 'account_id' => $bnkrow['account_id'], 'paytoaccount' => $bnkrow['paytoaccount'], 'paytobankname' => $bnkrow['paytobankname'], 'paytoifsc' => $bnkrow['paytoifsc'], 'to_account_id' => $bnkrow['to_account_id'], 'centre_id' => $bnkrow['centre_id'], 'purpose' => $bnkrow['purpose'], 'purpose_code' => $bnkrow['purpose_code'], 'description' => $bnkrow['description'], 'invoice_type' => $bnkrow['invoice_type'], 'invoice_no' => $bnkrow['invoice_no']);
		}
	}
	return $bnkstslist;
}

function getcreditpurposedetails($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ccq = "select sno, cmp_id, subcmp_id, id, purpose, purpose_code, credit_purpose,  status, Crosponding_purpose_code FROM tbl_purpose_master_credit WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "'";
	} else if ($shortby == "purposecode") {
		$ccq = "select sno, cmp_id, subcmp_id, id, purpose, purpose_code, credit_purpose,  status, Crosponding_purpose_code FROM tbl_purpose_master_credit WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and purpose_code='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$ccq = "select sno, cmp_id, subcmp_id, id, purpose, purpose_code, credit_purpose,  status, Crosponding_purpose_code FROM tbl_purpose_master_credit WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and sno='" . $shortbyvalue . "'";
	} else if ($shortby == "crospondingpurposelist") {
		$ccq = "select sno, cmp_id, subcmp_id, id, purpose, purpose_code, credit_purpose,  status, Crosponding_purpose_code FROM tbl_purpose_master_credit WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and (Crosponding_purpose_code is not null and Crosponding_purpose_code<>'')";
	} else if ($shortby == "purposesubcategory") {
		$ccq = "select sno, cmp_id, subcmp_id, id, purpose, purpose_code, credit_purpose,  status, Crosponding_purpose_code FROM tbl_purpose_master_credit WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and subcategoryid='" . $shortbyvalue . "'";
	} else if ($shortby == "purposecategoryid") {
		$ccq = "select sno, cmp_id, subcmp_id, id, purpose, purpose_code, credit_purpose,  status, Crosponding_purpose_code FROM tbl_purpose_master_credit WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and categoryid='" . $shortbyvalue . "'";
	} else if ($shortby == "purposename") {
		$ccq = "select sno, cmp_id, subcmp_id, id, purpose, purpose_code, credit_purpose,  status,Crosponding_purpose_code,category,categoryid,subcategory,subcategoryid FROM tbl_purpose_master_credit WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and lcase(purpose)='" . strtolower($shortbyvalue) . "'";
	}
	$ccq_rslt = $conn->query($ccq) or die($conn->error);
	$plist = array();
	if ($ccq_rslt->num_rows > 0) {
		while ($crow = $ccq_rslt->fetch_assoc()) {
			$plist[] = array('sno' => $crow['sno'], 'id' => $crow['id'], 'purpose' => $crow['purpose'], 'purposecode' => $crow['purpose_code'], 'credit_purpose' => $crow['credit_purpose'], 'status' => $crow['status'], 'Crosponding_purpose_code' => $crow['Crosponding_purpose_code'], 'category' => $crow['category'], 'categoryid' => $crow['categoryid'], 'subcategory' => $crow['subcategory'], 'subcategoryid' => $crow['subcategoryid']);
		}
	}
	return $plist;
}

function getcreditenquirydetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allapprovedentry") {
		$aaenq = "select a.sno as appsno, a.countryid, a.cmp_id, a.subcmp_id, a.creditto_projectid, a.creditto_accountid, a.creditfrom_accountid, a.reciveddate, a.transactionno, a.amount, a.creditpurposecode, a.creditpurpose, a.bankdescription, a.description, a.sendername, a.sbankname, a.saccountno, a.sbankifsc, a.statmentsno, a.booksno, a.invoiceno, a.approvedate, a.approvalstatus, a.approvedby, s.sno as bnksno, s.projectid, s.bankid,s.account_id,s.accountno,s.bankname,s.valuedate, s.debitamount,s.creditamount FROM tbl_credit_approval_history a, tbl_bankstatement s WHERE a.statmentsno=s.sno and (a.Tedit is null or a.Tedit='') and a.approvalstatus='approved' and a.cmp_id='" . $companyid . "' and a.subcmp_id='" . $subcompanyid . "' and a.creditto_projectid in ('" . str_replace(",", "','", $projectid) . "')";
	} else if ($shortby == "allpendingentry") {
		$aaenq = "select a.sno as appsno, a.countryid, a.cmp_id, a.subcmp_id, a.creditto_projectid, a.creditto_accountid, a.creditfrom_accountid, a.reciveddate, a.transactionno, a.amount, a.creditpurposecode, a.creditpurpose, a.bankdescription, a.description, a.sendername, a.sbankname, a.saccountno, a.sbankifsc, a.statmentsno, a.booksno, a.invoiceno, a.approvedate, a.approvalstatus, a.approvedby, s.sno as bnksno, s.projectid,s.bankid,s.account_id,s.accountno,s.bankname,s.valuedate, s.debitamount,s.creditamount FROM tbl_credit_approval_history a, tbl_bankstatement s WHERE a.statmentsno=s.sno and (a.Tedit is null or a.Tedit='') and (a.approvalstatus is null or a.approvalstatus='') and a.cmp_id='" . $companyid . "' and a.subcmp_id='" . $subcompanyid . "' and a.creditto_projectid in ('" . str_replace(",", "','", $projectid) . "')";
	} else if ($shortby == "banksno") {
		$aaenq = "select a.sno as appsno, a.countryid, a.cmp_id, a.subcmp_id, a.creditto_projectid, a.creditto_accountid, a.creditfrom_accountid, a.reciveddate, a.transactionno, a.amount, a.creditpurposecode, a.creditpurpose, a.bankdescription, a.description, a.sendername, a.sbankname, a.saccountno, a.sbankifsc, a.statmentsno, a.booksno, a.invoiceno, a.approvedate, a.approvalstatus, a.approvedby, s.sno as bnksno, s.projectid,s.bankid,s.account_id,s.accountno,s.bankname,s.valuedate, s.debitamount,s.creditamount FROM tbl_credit_approval_history a, tbl_bankstatement s WHERE a.statmentsno=s.sno and (a.Tedit is null or a.Tedit='') and a.cmp_id='" . $companyid . "' and a.subcmp_id='" . $subcompanyid . "' and s.sno='" . $shortbyvalue . "' and a.statmentsno='" . $shortbyvalue . "'";
	}
	$aaenqrs = $conn->query($aaenq) or die($conn->error);
	$rrlist = array();
	if ($aaenqrs->num_rows > 0) {
		while ($crarow = $aaenqrs->fetch_assoc()) {
			$rrlist[] = array('appsno' => $crarow['appsno'], 'countryid' => $crarow['countryid'], 'cmp_id' => $crarow['cmp_id'], 'subcmp_id' => $crarow['subcmp_id'], 'creditto_projectid' => $crarow['creditto_projectid'], 'creditto_accountid' => $crarow['creditto_accountid'], 'creditfrom_accountid' => $crarow['creditfrom_accountid'], 'reciveddate' => $crarow['reciveddate'], 'transactionno' => $crarow['transactionno'], 'amount' => $crarow['amount'], 'creditpurposecode' => $crarow['creditpurposecode'], 'creditpurpose' => $crarow['creditpurpose'], 'bankdescription' => $crarow['bankdescription'], 'description' => $crarow['description'], 'sendername' => $crarow['sendername'], 'sbankname' => $crarow['sbankname'], 'saccountno' => $crarow['saccountno'], 'sbankifsc' => $crarow['sbankifsc'], 'statmentsno' => $crarow['statmentsno'], 'booksno' => $crarow['booksno'], 'invoiceno' => $crarow['invoiceno'], 'approvedate' => $crarow['approvedate'], 'approvalstatus' => $crarow['approvalstatus'], 'approvedby' => $crarow['approvedby'], 'bnksno' => $crarow['bnksno'], 'projectid' => $crarow['projectid'], 'statementbankid' => $crarow['bankid'], 'statement_accountid' => $crarow['account_id'], 'statement_accountno' => $crarow['accountno'], 'statement_bankname' => $crarow['bankname'], 'valuedate' => date('Y-m-d', strtotime($crarow['valuedate'])), 'bankstatement_date' => date('d-m-Y', strtotime($crarow['valuedate'])));
		}
	}
	return $rrlist;
}

function getsendermaster($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$sq = $conn->query("select sno, sendername, bankname, accountno, ifsccode FROM tbl_sender_master WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "'") or die($conn->error);
	} else if ($shortby == "sendername") {
		$sq = $conn->query("select sno, sendername, bankname, accountno, ifsccode FROM tbl_sender_master WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and sendername='" . $shortbyvalue . "'") or die($conn->error);
	} else if ($shortby == "sno") {
		$sq = $conn->query("select sno, sendername, bankname, accountno, ifsccode FROM tbl_sender_master WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and sno='" . $shortbyvalue . "'") or die($conn->error);
	}
	$senderlist = array();
	if ($sq->num_rows > 0) {
		while ($srow = $sq->fetch_assoc()) {
			$senderlist[] = array('sno' => $srow['sno'], 'sendername' => $srow['sendername'], 'bankname' => $srow['bankname'], 'accountno' => $srow['accountno'], 'ifsccode' => $srow['ifsccode']);
		}
	}
	return $senderlist;
}

function chktableexists($conn, $ndbname, $tblname, $structuretable)
{
	$status = "1";
	$strqry = "select TABLE_NAME from information_schema.TABLES where TABLE_SCHEMA='" . $ndbname . "' and TABLE_NAME ='" . $tblname . "' ";
	$result3 = $conn->query($strqry);
	if (is_string($result3)) {
		// if(strpos($result3,"error:")!==false) { 
		// 	$resp= 'stage 5 \n'.$result3  .'\n query string ' . $strqry;
		// 	writetofile($resp,$newlogpath,"information_schema");
		// 	 $status="0";	 		 
		// }
	} else {
		$sql = $conn->query("CREATE TABLE " . $tblname . "  like " . $structuretable);
		if ($sql) {
			$status = 0;
		}
	}
	return $status;
}

function storerequestdocument_global($conn, $projectid, $requestno, $beneficiaryname, $amount, $documentstring, $uploadfileextension, $dbtablename)
{
	$documentid = "";
	if ($amount > 0 && $beneficiaryname != "") {
		$ndbn = "select IF(sno is null,'1',(max(sno)+1)) as maxid from $dbtablename";
		$maxsnorslt = $conn->query($ndbn);
		$mrow = $maxsnorslt->fetch_assoc();
		$maxid = $mrow['maxid'];
		$documentid = $projectid . '-' . date('mY') . $maxid;
		$filesize = strlen($documentstring);
		if ($documentid != "") {
			$insqry = "INSERT INTO $dbtablename(documentid, requestno, beneficiaryname, amount, documentstring, filetype, filesize) VALUES('" . $documentid . "', '" . $requestno . "', '" . mysqli_real_escape_string($conn, $beneficiaryname) . "', '" . $amount . "', '" . mysqli_real_escape_string($conn, $documentstring) . "', '" . $uploadfileextension . "', '" . $filesize . "')";
			$stringresult = $conn->query($insqry) or die($conn->error);

		}
	} else {
		$documentid = "";
	}
	return $documentid;
}

function getdocumentstring($conn, $graphicid)
{
	$sql = "select documentstring,filetype,filesize FROM tbl_paymentrequest_documents where documentid='" . $graphicid . "' and (tedit is null or tedit='')";
	$insresult = $conn->query($sql) or die($conn->error);
	$grapic = array();
	if ($insresult->num_rows > 0) {
		while ($invrow = $insresult->fetch_assoc()) {
			$grapic[] = array('status' => 'Success', 'documentstring' => $invrow['documentstring'], 'filetype' => $invrow['filetype'], 'filesize' => $invrow['filesize']);
		}
	}
	return $grapic;
}

function getImtestpaytoattach($conn, $imprestpayemail, $shortby, $shortbyvalue)
{
	// $foldename=str_replace('.', 'dot', str_replace('@', 'at', $imprestpayemail));
	$foldename = strtolower(str_replace("-", "dash", str_replace("@", "at", str_replace(".", "dot", str_replace("_", "underscore", $imprestpayemail)))));
	if ($shortby == "allwithoutrequest") {
		$sltqry = "select ID,IDEmail,Filename,FileNameOrg FROM emailtodb_attach where Filename like '%" . $foldename . "%' and (filestatus is null or filestatus='')";
	} else if ($shortby == "fileid") {
		$sltqry = "select ID,IDEmail,Filename,FileNameOrg FROM emailtodb_attach where Filename like '%" . $foldename . "%' and ID='" . $shortbyvalue . "'";
	}
	$sltqry_rslt = $conn->query($sltqry);
	$filelist = array();
	if ($sltqry_rslt->num_rows > 0) {
		while ($irow = $sltqry_rslt->fetch_assoc()) {
			$filelist[] = array('filename' => $irow['Filename'], 'fileid' => $irow['ID'], 'emailid' => $irow['IDEmail'], 'filenameorg' => $irow['FileNameOrg']);
		}
	}
	return $filelist;
}

function gettdsdetails($conn, $companyid, $subcompanyid, $projectid, $datefrom, $dateto, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$tdq = "select sno, bookdate, countryid, companyid, subcompanyid, projectid, tds_type, request_no, reference_no, from_accountid, from_accountno, from_bankname, beneficiary_name, amount_paid, tds_deducted, tds_percent, tds_status, transactionno, transaction_date, bill_no, tds_challan_no, tds_challan_date FROM tds_account_summery WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "beneficiarynamewithdates") {
		$tdq = "select sno, bookdate, countryid, companyid, subcompanyid, projectid, tds_type, request_no, reference_no, from_accountid, from_accountno, from_bankname, beneficiary_name, amount_paid, tds_deducted, tds_percent, tds_status, transactionno, transaction_date, bill_no, tds_challan_no, tds_challan_date FROM tds_account_summery WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and DATE(bookdate)>='" . $datefrom . "' and DATE(bookdate)<='" . $dateto . "' and lcase(beneficiary_name)='" . strtolower(addslashes($shortbyvalue)) . "'";
	}
	$tdq_result = $conn->query($tdq);
	$tdslist = array();
	if ($tdq_result->num_rows > 0) {
		while ($tdrow = $tdq_result->fetch_assoc()) {
			$tdslist[] = array('sno' => $tdrow['sno'], 'bookdate' => $tdrow['bookdate'], 'bookdatedsp' => date('d-M-Y', strtotime($tdrow['bookdate'])), 'countryid' => $tdrow['countryid'], 'companyid' => $tdrow['companyid'], 'subcompanyid' => $tdrow['subcompanyid'], 'projectid' => $tdrow['projectid'], 'tds_type' => $tdrow['tds_type'], 'request_no' => $tdrow['request_no'], 'reference_no' => $tdrow['reference_no'], 'from_accountid' => $tdrow['from_accountid'], 'from_accountno' => $tdrow['from_accountno'], 'from_bankname' => $tdrow['from_bankname'], 'beneficiary_name' => $tdrow['beneficiary_name'], 'amount_paid' => $tdrow['amount_paid'], 'tds_deducted' => $tdrow['tds_deducted'], 'tds_percent' => $tdrow['tds_percent'], 'tds_status' => $tdrow['tds_status'], 'transactionno' => $tdrow['transactionno'], 'transaction_date' => $tdrow['transaction_date'], 'bill_no' => $tdrow['bill_no'], 'tds_challan_no' => $tdrow['tds_challan_no'], 'tds_challan_date' => $tdrow['tds_challan_date']);
		}
	}
	return $tdslist;
}

function get_tdstypefrommaster($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
	if ($shortby == "forall") {
		$tdq = "select tds_type, tds_percent FROM tds_type_master WHERE (tedit is null or tedit='')";
	} else if ($shortby == "all") {
		$tdq = "select tds_type, tds_percent FROM tds_type_master WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "'";
	} else if ($shortby == "sno") {
		$tdq = "select tds_type, tds_percent FROM tds_type_master WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and sno='" . $shortbyvalue . "'";
	} else if ($shortby == "tdstype") {
		$tdq = "select tds_type, tds_percent FROM tds_type_master WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and tds_type='" . $shortbyvalue . "'";
	}
	$tdsresult = $conn->query($tdq);
	$tdslist = array();
	if ($tdsresult->num_rows > 0) {
		while ($tdrow = $tdsresult->fetch_assoc()) {
			$tdslist[] = array('tds_type' => $tdrow['tds_type'], 'tds_percent' => $tdrow['tds_percent'], 'tdstype_withprecent' => $tdrow['tds_type'] . '(' . $tdrow['tds_percent'] . ' %)');
		}
	}
	return $tdslist;
}

function getTdsTypesMasterData($conn, $shortby = 'all', $shortbyvalue = '')
{
	$data = [];

	if ($shortby === 'all') {
		$sql = "SELECT id, section, nature_of_payment, deductor, deductee, tds_rate, threshold_limit FROM tds_types_master ORDER BY id ASC";
		$result = $conn->query($sql);

	} elseif ($shortby === 'id') {
		$sql = "SELECT id, section, nature_of_payment, deductor, deductee, tds_rate, threshold_limit FROM tds_types_master WHERE id = ?";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $shortbyvalue);
		$stmt->execute();
		$result = $stmt->get_result();

	} elseif ($shortby === 'percentvalue') {
		$sql = "SELECT id, section, nature_of_payment, deductor, deductee, tds_rate, threshold_limit FROM tds_types_master WHERE tds_rate LIKE ?";
		$stmt = $conn->prepare($sql);
		$likeVal = '%' . $shortbyvalue . '%';
		$stmt->bind_param("s", $likeVal);
		$stmt->execute();
		$result = $stmt->get_result();

	} else {
		// Invalid filter
		return [];
	}

	if (isset($result) && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}

	return $data;
}


function getdeductedtds_durationlist($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $shortby, $shortbyvalue)
{
	if ($shortby == "pendingforbook") {
		$tdq = "select distinct date_format(bookdate,'%M-%Y') as displaydate, date_format(bookdate, '%Y-%m-01') as valuedate  FROM tds_account_summery WHERE (tedit is null or tedit='') and (tds_status is null or tds_status='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and subprojectid='" . $subprojectid . "'";
	} else if ($shortby == "financialyearlist") {
		$tdq = "SELECT distinct CONVERT(CASE WHEN MONTH(bookdate)>=4 THEN concat(YEAR(bookdate), '-',YEAR(bookdate)+1) ELSE concat(YEAR(bookdate)-1,'-', YEAR(bookdate)) END using utf8) AS displaydate, CONVERT(CASE WHEN MONTH(bookdate)>=4 THEN concat(YEAR(bookdate), '-',YEAR(bookdate)+1) ELSE concat(YEAR(bookdate)-1,'-', YEAR(bookdate)) END using utf8) AS valuedate FROM tds_account_summery WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and subprojectid='" . $subprojectid . "' order by bookdate desc";
	} else {
		$tdq = "select distinct date_format(bookdate,'%M-%Y') as displaydate, date_format(bookdate, '%Y-%m-01') as valuedate  FROM tds_account_summery WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and subprojectid='" . $subprojectid . "'";
	}
	$tdqr = $conn->query($tdq) or die($conn->error);
	$yearmonth = array();
	if ($tdqr->num_rows > 0) {
		while ($trow = $tdqr->fetch_assoc()) {
			$yearmonth[] = array('displaydate' => $trow['displaydate'], 'valuedate' => $trow['valuedate']);
		}
	}
	return $yearmonth;
}

function deductedtds_monthlist($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $financialyear)
{
	$condition = "";
	if ($financialyear) {
		$finexp = explode("|", $financialyear);
		$datefrom = $finexp[0];
		$dateot = $finexp[1];
		$condition = "and bookdate>='" . $datefrom . "' and YEAR(bookdate)<='" . $dateot . "'";
	}
	$tdq = "SELECT DISTINCT DATE_FORMAT(bookdate, '%M-%Y') AS displaymonth, DATE_FORMAT(bookdate, '%Y-%m-01') AS firstdate, DATE_FORMAT(DATE_SUB(DATE_ADD(bookdate, INTERVAL 1 MONTH), INTERVAL DAY(DATE_ADD(bookdate, INTERVAL 1 MONTH)) DAY), '%Y-%m-%d') AS lastdate FROM tds_account_summery WHERE (tedit IS NULL OR tedit = '') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' " . $condition . " ";
	$tdqr = $conn->query($tdq) or die($conn->error);
	$monthlist = array();
	if ($tdqr->num_rows > 0) {
		while ($trow = $tdqr->fetch_assoc()) {
			$monthlist[] = array('firstdate' => $trow['firstdate'], 'lastdate' => $trow['lastdate'], 'displaymonth' => $trow['displaymonth']);
		}
	}
	return $monthlist;
}

function deductedtds_quarterlist($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $financialyear)
{
	$condition = "";
	if ($financialyear) {
		$finexp = explode("|", $financialyear);
		$datefrom = $finexp[0];
		$dateot = $finexp[1];
		$condition = "and bookdate>='" . $datefrom . "' and YEAR(bookdate)<='" . $dateot . "'";
	}
	$tdq = "SELECT convert(case when (month(bookdate)>=4 and month(bookdate)<= 6) then concat('1st Quarter ',year(bookdate),'-',year(bookdate)+1) when (month(bookdate)>=7 and month(bookdate)<= 9) then concat('2nd Quarter ',year(bookdate),'-',year(bookdate)+1)  when (month(bookdate)>=10 and month(bookdate)<= 12) then concat('3rd Quarter ',year(bookdate),'-',year(bookdate)+1)  when (month(bookdate)>=1 and month(bookdate)<= 3) then concat('4th Quarter ',year(bookdate)-1,'-',year(bookdate)) End USING utf8) as qrterdate,min(bookdate) as firstdate,max(bookdate) as lastdate from tds_account_summery where (tedit is null or tedit='') and (tds_status is null or tds_status='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' $condition group by qrterdate order by bookdate desc";
	$tdqr = $conn->query($tdq) or die($conn->error);
	$quarterlist = array();
	if ($tdqr->num_rows > 0) {
		while ($trow = $tdqr->fetch_assoc()) {
			$quarterlist[] = array('firstdate' => $trow['firstdate'], 'lastdate' => $trow['lastdate'], 'quartername' => $trow['qrterdate']);
		}
	}
	return $quarterlist;
}

function deductedtds_financialyearlist($conn, $companyid, $subcompanyid, $projectid, $subprojectid)
{
	$tdq = "SELECT DISTINCT CONVERT(CASE WHEN MONTH(bookdate) >= 4 THEN CONCAT(YEAR(bookdate), '-', YEAR(bookdate) + 1) ELSE CONCAT(YEAR(bookdate) - 1, '-', YEAR(bookdate))    END  USING utf8) AS financialyear,CASE  WHEN MONTH(bookdate) >= 4 THEN DATE_FORMAT(CONCAT(YEAR(bookdate), '-04-01'), '%Y-%m-%d') ELSE DATE_FORMAT(CONCAT(YEAR(bookdate) - 1, '-04-01'), '%Y-%m-%d')END AS firstdate,CASE  WHEN MONTH(bookdate) >= 4 THEN DATE_FORMAT(CONCAT(YEAR(bookdate) + 1, '-03-31'), '%Y-%m-%d') ELSE DATE_FORMAT(CONCAT(YEAR(bookdate), '-03-31'), '%Y-%m-%d') END AS lastdate FROM tds_account_summery WHERE (tedit IS NULL OR tedit = '') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' order by bookdate desc";

	$tdqr = $conn->query($tdq) or die($conn->error);
	$finyearlist = array();
	if ($tdqr->num_rows > 0) {
		while ($trow = $tdqr->fetch_assoc()) {
			$finyearlist[] = array('firstdate' => $trow['firstdate'], 'lastdate' => $trow['lastdate'], 'financialyear' => $trow['financialyear']);
		}
	}
	return $finyearlist;
}


function getdeducted_tdstypelist($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $yearmonth)
{
	if ($yearmonth) {
		if (strpos($yearmonth, '|') !== false) {
			$datesexp = explode("|", $yearmonth);
			$condition = "and YEAR(t.bookdate)='" . date('Y', strtotime($datesexp[0])) . "'";
		} else {
			$condition = "and YEAR(t.bookdate)='" . date('Y', strtotime($yearmonth)) . "' and MONTH(t.bookdate)='" . date('m', strtotime($yearmonth)) . "'";
		}
	}
	$tdq = "select distinct t.tds_type,t.tds_percent,tm.id,tm.section,tm.tds_rate FROM tds_account_summery t right join tds_types_master tm on t.tds_type=tm.id WHERE (t.tedit is null or t.tedit='') and (tm.tedit is null or tm.tedit='') and  t.companyid='" . $companyid . "' and t.subcompanyid='" . $subcompanyid . "' and t.projectid='" . $projectid . "'  $condition ";
	$tdqr = $conn->query($tdq) or die($conn->error);
	$tdstypelist = array();
	if ($tdqr->num_rows > 0) {
		while ($trow = $tdqr->fetch_assoc()) {
			$tdstypelist[] = array("tdstype" => $trow['section'] . " (" . $trow['tds_percent'] . " %)", 'tdsid' => $trow['id'], 'tdssection' => $trow['section']);
		}
	}
	return $tdstypelist;
}

function getdeductedtds_percentlist($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $yearmonth, $tdstype)
{
	if (strtolower($tdstype) == "all") {
		$tdq = "select distinct tds_percent FROM tds_account_summery WHERE (tedit is null or tedit='') and (tds_status is null or tds_status='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and YEAR(bookdate)='" . date('Y', strtotime($yearmonth)) . "' and MONTH(bookdate)='" . date('m', strtotime($yearmonth)) . "'";
	} else {
		$tdq = "select distinct tds_percent FROM tds_account_summery WHERE (tedit is null or tedit='') and (tds_status is null or tds_status='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and YEAR(bookdate)='" . date('Y', strtotime($yearmonth)) . "' and MONTH(bookdate)='" . date('m', strtotime($yearmonth)) . "' and lcase(tds_type)='" . strtolower($tdstype) . "'";
	}
	$tdqr = $conn->query($tdq) or die($conn->error);
	$tdsperlist = array();
	if ($tdqr->num_rows > 0) {
		while ($trow = $tdqr->fetch_assoc()) {
			$tdsperlist[] = array('tdspercent' => $trow['tds_percent']);
		}
	}
	return $tdsperlist;
}

function addnewsubproject($conn, $companyid, $subcompanyid, $projectid, $subprojectname, $emailid)
{

	$maxid = $conn->query("select (max(subprojectid)+1) as maxid FROM tbl_projectsub_master");
	if ($maxid->num_rows > 0) {
		$mrow = $maxid->fetch_assoc();
		if (is_null($mrow['maxid'])) {
			$maxid = 1;
		} else {
			$maxid = $mrow['maxid'];
		}

	} else {
		$maxid = 1;
	}

	$paytoq = $conn->query("select subprojectname FROM tbl_projectsub_master where lcase(subprojectname)='" . strtolower($subprojectname) . "' and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='')") or die($conn->error);
	if ($paytoq->num_rows > 0) {
		$message[] = array("status" => 'Error', 'message' => "Already exist of subproject details");
	} else {
		$paytoinsqry = $conn->query("INSERT INTO tbl_projectsub_master (companyid,subcompanyid,projectid,subprojectid,subprojectname,emailid) VALUES('" . $companyid . "', '" . $subcompanyid . "', '" . $projectid . "', '" . $maxid . "', '" . strtoupper($subprojectname) . "', '" . $emailid . "')") or die($conn->error);
		if ($paytoinsqry) {
			$message[] = array("status" => 'Success', 'message' => "Subproject details saved successfully");
		}
	}


	return $message;
}

function beneficiarykey($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$bnfkey = "SELECT sno, companyid, subcompanyid, projectid, keyid, keyname, beneficiaryname from tbl_beneficery_keys where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "keyname") {
		$bnfkey = "SELECT sno, companyid, subcompanyid, projectid, keyid, keyname, beneficiaryname from tbl_beneficery_keys where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and keyname='" . $shortbyvalue . "'";
	} else if ($shortby == "keyname") {
		$bnfkey = "SELECT sno, companyid, subcompanyid, projectid, keyid, keyname, beneficiaryname from tbl_beneficery_keys where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and keyid='" . $shortbyvalue . "'";
	}
	$bnfkeyr = $conn->query($bnfkey);
	$bnfkeylist = array();
	if ($bnfkeyr->num_rows > 0) {
		while ($bkrow = $bnfkeyr->fetch_assoc()) {
			$bnfkeylist[] = array('sno' => $bkrow['sno'], 'keyid' => $bkrow['keyid'], 'keyname' => $bkrow['keyname'], 'beneficiaryname' => $bkrow['beneficiaryname']);
		}
	}
	return $bnfkeylist;
}


function updatepaytoidinbook($conn)
{
	$ssq = $conn->query("SELECT sno,companyid,subcompanyid,projectid,payto FROM tbl_maintransaction where ifnull(paytoid,'')='' and ifnull(tedit,'')=''");
	if ($ssq->num_rows > 0) {
		while ($mprow = $ssq->fetch_assoc()) {
			$ssqpp = $conn->query("SELECT paytoid FROM tbl_payto_master where cmp_id='" . $mprow['companyid'] . "' and subcmp_id='" . $mprow['subcompanyid'] . "' and projectid='" . $mprow['projectid'] . "' and lcase(paytoname)='" . addslashes(strtolower($mprow['payto'])) . "' and ifnull(tedit,'')=''");
			if ($ssqpp->num_rows == 1) {
				$mmrow = $ssqpp->fetch_assoc();
				$conn->query("UPDATE tbl_maintransaction SET paytoid='" . $mmrow['paytoid'] . "' where sno='" . $mprow['sno'] . "'");
			}
		}
	}
}


function updatedocumentstatusMaster($conn, $docsno, $requestno)
{
	$upd = "UPDATE email_status SET reqstatus='" . $requestno . "' where sno='" . $docsno . "' and (reqstatus is null or reqstatus='')";
	$upstau = $conn->query($upd);
}

function create_directory($directory)
{
	$dir = explode("/", $directory);
	$newdir = "";
	for ($j = 0; $j < count($dir); $j++) {
		if ($dir[$j] != "") {
			if ($newdir == "") {
				$newdir = $dir[$j];
			} else {
				$newdir = $newdir . "/" . $dir[$j];
			}
			// echo "<br>==: ".$newdir;
			if (!is_dir("/" . $newdir)) {
				$oldmask = umask(0);
				mkdir("/" . $newdir, 0777, true);
				umask($oldmask);
			}
		}
	}
}

function get_uniquefilename()
{
	// Get the current time with microseconds
	$microtime = microtime(true);
	// Format the microtime to include milliseconds
	$milliseconds = sprintf("%03d", ($microtime - floor($microtime)) * 1000);
	// Create the filename using the current date and time
	$uniqueFilename = date("Ymd_His") . $milliseconds;
	return $uniqueFilename;
}

function uploadattachement($files, $directorypath)
{
	$returnpath = "";
	$fileName = $files['name'];
	$fileTmpName = $files['tmp_name'];
	$fileError = $files['error'];
	$file_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
	$destination = $subdir . "/" . get_uniquefilename() . '.' . $file_ext;
	if ($fileError === 0) {
		if (move_uploaded_file($fileTmpName, $destination)) {
			$returnpath = $destination;
		}
	}
	return $returnpath;
}

function removeEmptyDirectory($dir)
{
	// Check if directory exists
	if (is_dir($dir)) {
		// Scan the directory for any files or directories (ignoring "." and "..")
		$files = scandir($dir);
		if (count($files) === 2) { // Only "." and ".." present
			// Remove the empty directory
			if (rmdir($dir)) {
				return "Directory '$dir' was successfully removed.";
			} else {
				return "Failed to remove directory '$dir'. Check permissions.";
			}
		} else {
			return "Directory '$dir' is not empty.";
		}
	} else {
		return "The path '$dir' is not a directory.";
	}
}

function getfinancialyeartype($mconn, $companyid)
{
	$mmq = $mconn->query("select ifnull(finyr,'') as finyr from tbl_company_master where companyid='" . $companyid . "'");
	if ($mmq->num_rows > 0) {
		$row = $mmq->fetch_assoc();
		return $row['finyr'];
	} else {
		return '';
	}
}

function getcurrentfinancialyear($inputDate, $format = "Y", $finyeartype = "Apr-Mar")
{
	$financial_year = "";
	$date = date_create($inputDate);

	if (!$date) {
		return "Invalid Date";
	}

	$month = (int) date_format($date, "m");
	$year = (int) date_format($date, $format);

	switch ($finyeartype) {
		case "Apr-Mar":
		case "4":
			if ($month >= 4) {
				$financial_year = $year . '-' . ($year + 1);
			} else {
				$financial_year = ($year - 1) . '-' . $year;
			}
			break;

		case "Jan-Dec":
		case "1":
			$financial_year = $year;
			break;

		default:
			$financial_year = "Unsupported Financial Year Type";
			break;
	}

	return $financial_year;
}

function getCurrentFinancialYearByChaturSingh($inputDate, $startMonth = 4, $format = "Y")
{
	$date = date_create($inputDate);
	if (!$date) {
		return "Invalid Date";
	}

	$month = (int) date_format($date, "m");
	$year = (int) date_format($date, $format);

	// If the month is greater than or equal to the start month, current FY starts this year
	if ($month >= $startMonth) {
		$startYear = $year;
		$endYear = $year + 1;
	} else {
		$startYear = $year - 1;
		$endYear = $year;
	}

	return $startYear . '-' . $endYear;
}


function getfinancialyearlist($fromdate, $todate, $finyeartype = "Apr-Mar")
{
	$fromyear = date('Y', strtotime($fromdate));
	$frommonth = date('m', strtotime($fromdate));

	$toyear = date('Y', strtotime($todate));
	$tomonth = date('m', strtotime($todate));

	$dates = range($fromyear, $toyear);
	$finyear = array();
	switch ($finyeartype) {
		case "Apr-Mar":
			foreach ($dates as $date) {
				if (date('m', strtotime($date)) >= 04) {//Upto June
					$finyear[] = array('fyear' => $date . '-' . ($date + 1), 'fromdate' => $fromdate, 'todate' => $todate);
				} else {//After June
					$finyear[] = array('fyear' => ($date + 1) . '-' . $date, 'fromdate' => $fromdate, 'todate' => $todate);
				}
			}
			break;
		case "Jan-Dec":
			$finyear[] = array('fyear' => $date, 'fromdate' => $fromdate, 'todate' => $todate);
			break;
	}
	return $finyear;
}


function TransactionsFinancialYear($conn, $companyid, $subcompanyid, $projectid, $startmonthno)
{
	$finyearlist = array(); // Initialize the array
	$query = "SELECT CASE WHEN MONTH(m.bookdate) >= $startmonthno THEN CONCAT(YEAR(m.bookdate), '-', YEAR(m.bookdate) + 1) ELSE CONCAT(YEAR(m.bookdate) - 1, '-', YEAR(m.bookdate)) END AS financial_year FROM tbl_maintransaction m INNER JOIN tbl_maintransaction_breakdown b ON m.reference_no = b.reference_no 
        WHERE (m.tedit IS NULL OR m.tedit = '') AND (b.tedit IS NULL OR b.tedit = '') AND m.bookdate IS NOT NULL 
            AND m.companyid = '" . $conn->real_escape_string($companyid) . "' AND m.subcompanyid = '" . $conn->real_escape_string($subcompanyid) . "' AND m.projectid = '" . $conn->real_escape_string($projectid) . "' GROUP BY financial_year ORDER BY financial_year DESC";

	$finyearfq = $conn->query($query);

	if ($finyearfq && $finyearfq->num_rows > 0) {
		while ($frow = $finyearfq->fetch_assoc()) {
			$finyearlist[] = ['financialyear' => $frow['financial_year']];
		}
	}

	return $finyearlist;
}


function convertbase64intofile($codebase64, $filename)
{
	$bin = base64_decode($codebase64, true);
	$f = finfo_open();
	$mime_type = finfo_buffer($f, $bin, FILEINFO_MIME_TYPE);
	// $newfilename =$filename;
	if (stristr($mime_type, "pdf")) {
		$newfilename = $filename . ".pdf";
	} else if (stristr($mime_type, "mp3")) {
		$newfilename = $filename . ".mp3";
	} else if (stristr($mime_type, "mp4")) {
		$newfilename = $filename . ".mp4";
	} else if (stristr($mime_type, "wav")) {
		$newfilename = $filename . ".wav";
	} else if (stristr($mime_type, "jpeg")) {
		$newfilename = $filename . ".jpeg";
	} else if (stristr($mime_type, "jpg")) {
		$newfilename = $filename . ".jpg";
	} else if (stristr($mime_type, "png")) {
		$newfilename = $filename . ".png";
	} else if (stristr($mime_type, "html")) {
		$newfilename = $filename . ".html";
	} else if (stristr($mime_type, "htm")) {
		$newfilename = $filename . ".htm";
	} else if (stristr($mime_type, "xls")) {
		$newfilename = $filename . ".xls";
	} else if (stristr($mime_type, "xlsx")) {
		$newfilename = $filename . ".xlsx";
	} else if (stristr($mime_type, "doc")) {
		$newfilename = $filename . ".doc";
	} else if (stristr($mime_type, "docx")) {
		$newfilename = $filename . ".docx";
	} else if (stristr($mime_type, "gif")) {
		$newfilename = $filename . ".gif";
	} else {
		echo "Issue " . $filename;
		exit;
	}
	# Perform a basic validation to make sure that the result is a valid PDF file
	# Be aware! The magic number (file signature) is not 100% reliable solution to validate PDF files
	# Moreover, if you get Base64 from an untrusted source, you must sanitize the PDF contents
	/*if (strpos($bin, '%PDF') !== 0) {
	  throw new Exception('Missing the PDF file signature');
	}*/

	# Write the PDF contents to a local file
	// echo $filename;
	file_put_contents($newfilename, $bin);
	$x = 0;
	while ($x <= 5000) {
		// echo "The number is: $x <br>";
		$x++;
	}
	//sleep(3); 
	if (file_exists($newfilename)) {
		return "exists";
	} else {
		return "";
	}
}

function convertbase64intofile_withextn($codebase64, $filename, $ex)
{
	$bin = base64_decode($codebase64, true);
	// $f = finfo_open();
	// $mime_type = finfo_buffer($f, $bin, FILEINFO_MIME_TYPE);		
	$mime_type = $ex;
	if (stristr($mime_type, "pdf")) {
		$newfilename = $filename . ".pdf";
	} else if (stristr($mime_type, "mp3")) {
		$newfilename = $filename . ".mp3";
	} else if (stristr($mime_type, "mp4")) {
		$newfilename = $filename . ".mp4";
	} else if (stristr($mime_type, "wav")) {
		$newfilename = $filename . ".wav";
	} else if (stristr($mime_type, "jpeg")) {
		$newfilename = $filename . ".jpeg";
	} else if (stristr($mime_type, "jpg")) {
		$newfilename = $filename . ".jpg";
	} else if (stristr($mime_type, "png")) {
		$newfilename = $filename . ".png";
	} else if (stristr($mime_type, "html")) {
		$newfilename = $filename . ".html";
	} else if (stristr($mime_type, "htm")) {
		$newfilename = $filename . ".htm";
	} else if (stristr($mime_type, "xls")) {
		$newfilename = $filename . ".xls";
	} else if (stristr($mime_type, "xlsx")) {
		$newfilename = $filename . ".xlsx";
	} else if (stristr($mime_type, "doc")) {
		$newfilename = $filename . ".doc";
	} else if (stristr($mime_type, "docx")) {
		$newfilename = $filename . ".docx";
	} else if (stristr($mime_type, "gif")) {
		$newfilename = $filename . ".gif";
	} else {
		echo "Issue " . $filename;
		exit;
	}
	# Perform a basic validation to make sure that the result is a valid PDF file
	# Be aware! The magic number (file signature) is not 100% reliable solution to validate PDF files
	# Moreover, if you get Base64 from an untrusted source, you must sanitize the PDF contents
	/*if (strpos($bin, '%PDF') !== 0) {
	  throw new Exception('Missing the PDF file signature');
	}*/

	# Write the PDF contents to a local file
	// echo $filename;
	file_put_contents($newfilename, $bin);
	$x = 0;
	while ($x <= 5000) {
		// echo "The number is: $x <br>";
		$x++;
	}
	//sleep(3); 
	if (file_exists($newfilename)) {
		return "exists";
	} else {
		return "";
	}
}



function convert_base64_to_pdf($basestring, $outputpath, $filename)
{
	$b64 = $basestring;
	$bin = base64_decode($b64, true);

	if (strpos($bin, '%PDF') !== 0) {
		return false;
	}

	$pdffile = $outputpath . "/" . $filename . ".pdf";

	file_put_contents($pdffile, $bin);

	if (file_exists($pdffile)) {
		return $pdffile;
	} else {
		return false;
	}
}

// function getnewreferenceno($conn,$companyid,$subcompanyid,$projectid,$fromaccountno){
// 	$tsno=GetMaxRefTempSno($conn);
// 	if(strlen($fromaccountno)>5){
// 		$newreferenceno = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($fromaccountno, -4).'-'.$tsno;	
// 	}else{
// 		$newreferenceno = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.$tsno;	
// 	}
// 	return $newreferenceno;
// }

function getnewreferenceno($conn, $companyid, $subcompanyid, $projectid, $fromaccountno)
{
	$newreferenceno = '';
	$retryCount = 0;
	$maxRetries = 3;
	while ($retryCount < $maxRetries) {
		try {
			$uuid = bin2hex(random_bytes(6)); // 12 hex chars
			$suffix = strtoupper(substr($uuid, 0, 8));

			if (strlen($fromaccountno) > 5) {
				$newreferenceno = 'REF-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . substr($fromaccountno, -4) . '-' . $suffix;
			} else {
				$newreferenceno = 'REF-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $suffix;
			}

			$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_maintransaction WHERE reference_no = ?");
			if (!$stmt) {
				throw new Exception("SQL Prepare failed: " . $conn->error);
			}

			$stmt->bind_param("s", $newreferenceno);
			$stmt->execute();
			$result = $stmt->get_result()->fetch_assoc();
			$stmt->close();

			if ($result['total'] == 0) {
				return $newreferenceno; // Unique and ready
			}

		} catch (Exception $e) {
			error_log("Error generating reference: " . $e->getMessage());
			return null; // or return a fallback value
		}

		$retryCount++;
	}

	return null; // Failed to generate after maxRetries
}



function GetMaxRefTempSno($conn)
{
	$mtso = $conn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");
	$tsno = 1;
	if ($mtso->num_rows > 0) {
		$trow = $mtso->fetch_assoc();
		$tsno = $trow['maxtsno'];
	}
	return $tsno;
}


function getnewrequestno($conn, $companyid, $subcompanyid, $projectid, $fromaccountid)
{
	$tempmaxno = GetMaxTempSno($conn);
	$requestno = "REQ-" . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $fromaccountid . '-' . $tempmaxno;
	return $requestno;
}


function GetMaxTempSno($conn)
{
	$selectsno = "select max(temp_requestno) as texp_sno from tbl_paymentrequest";
	$result_sno = $conn->query($selectsno);
	if ($result_sno->num_rows > 0) {
		$rowsno = $result_sno->fetch_array();
		$maxtempsno = $rowsno['texp_sno'] + 1;
	}
	return $maxtempsno;
}


function getnewrequestno_temp($conn, $companyid, $subcompanyid, $projectid, $fromaccountid)
{
	$tempmaxno = GetMaxTempSno($conn);
	$requestno = $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $fromaccountid . '-' . $tempmaxno;
	return $requestno;
}

function GetMaxTempSno_temp($conn)
{
	$selectsno = "select count(sno) as texp_sno from tbl_paymentrequest_details_temp";
	$result_sno = $conn->query($selectsno);
	$maxtempsno = 1;
	if ($result_sno->num_rows > 0) {
		$rowsno = $result_sno->fetch_array();
		$maxtempsno = $rowsno['texp_sno'] + 1;
	}
	return $maxtempsno;
}

function Isrefundablepurchase($conn, $companyid, $subcompanyid, $projectid, $request_no, $paytoname, $beneficieryname, $beneficieryammount, $centrename, $centreid, $centerindentifire, $description, $invoicetype, $invoiceno, $purpose_catagory, $purpose, $forcentreid, $forcentrename)
{

	$refundentry = "insert into tbl_askt_refundablestock (countryid, companyid, subcompanyid, projectid, request_no, payto_name, bnf_name, bnf_amount, centre_name, from_centreid, from_centreidentifier, description, invoicetype, invoice_no, purpose_category, purpose, forcentreid, forcentrename) values('" . $countryid . "', '" . $companyid . "', '" . $subcompanyid . "', '" . $projectid . "', '" . $request_no . "', '" . addslashes($paytoname) . "', '" . addslashes($beneficieryname) . "', '" . $beneficieryammount . "', '" . $centrename . "', '" . $centreid . "', '" . addslashes($description) . "', '" . $invoicetype . "', '" . $invoiceno . "', '" . $purpose_catagory . "', '" . $purpose . "', '" . $forcentreid . "', '" . $forcentreidentifier . "', '" . $forcentrename . "')";
	$refundentry_rslt = $conn->query($refundentry);
	if ($refundentry_rslt) {
		//echo "<br> Refundable entery inserted";
	} else {
		//echo "<br> Refundable entery not inserted";

	}
}

function addnewitemforstock($conn, $newitemname, $purposecode)
{
	$stockq = "select forpupose,itemname FROM itemsstock_master where forpupose='" . $purposecode . "' and lcase(itemname)='" . strtolower($newitemname) . "'";
	$stockqrlst = $conn->query($stockq);
	if ($stockqrlst->num_rows > 0) {
		$result[] = array('status' => 'Error', 'message' => 'Already Exist.');
	} else {
		$insstoc = "INSERT INTO itemsstock_master (forpupose,itemname) VALUES('" . $purposecode . "','" . $newitemname . "')";
		$insstoc_rslt = $conn->query($insstoc);
		if ($insstoc_rslt) {
			$result[] = array('status' => 'Success', 'message' => 'Added Successfully.');
		}
	}
	return $result;
}

function IsitemPurchaseforStock($conn, $companyid, $subcompanyid, $projectid, $requestno, $fromprojectname, $itemstockarray, $isrefundable)
{
	$refundablecentrename = $fromprojectname;
	$refundablecentreid = $projectid;
	$itemdescription = "";
	foreach ($itemstockarray as $itemrow) {
		$pprj = explode("|", $itemrow['itempurpose']);
		$purposecode = $pprj[0];
		$purposename = $pprj[1];
		$fullpurpose = $purposename . '(' . $purposecode . ')';
		$itemname = $itemrow['itemname_inpt'];
		$itemcount = $itemrow['itemcount_inpt'];
		$amountperitem = $itemrow['amoutperitem'];
		$itemgst = $itemrow['itemgst'];
		$totalamount = $itemrow['itemamount_inpt'];
		$item_bnfname = $itemrow['item_bnfname'];

		//insert query for item purchage from main account to other centre
		$itmquery = "insert into tbl_stock (countryid,companyid,subcompanyid,projectid,request_no, amount, centre_name, centre_id, item_name, quantity, item_rate, itemgst, itemamount, itempurpose, itembnfname, fromcentreid, isrefundable, description) VALUES('" . $countryid . "', '" . $companyid . "', '" . $subcompanyid . "', '" . $projectid . "', '" . $requestno . "', '" . $totalamount . "', '" . $refundablecentrename . "', '" . $refundablecentreid . "', '" . $itemname . "', '" . $itemcount . "', '" . $amountperitem . "', '" . $itemgst . "', '" . $totalamount . "', '" . $fullpurpose . "', '" . $item_bnfname . "', '" . $refundablecentreid . "', '" . $isrefundable . "', '" . addslashes($itemdescription) . "')";
		$itmqueryrst = $conn->query($itmquery);
		if ($itmqueryrst) {
		} else {
		}
	}
}


function getloansummarydetails($conn, $companyid, $subcompanyid, $projectid, $loanid, $shortby, $shortbyvalue)
{
	if ($shortby == "loanid") {
		$lsqq = "select sno, idate, loanid, countryid, companyid, subcompanyid, projectid, from_accountno, to_accountno, requestno, referenceno, loan_payto, ifnull(loan_amount,0) as loan_amount, ifnull(loan_returnamount,0) as loan_returnamount, purpose, purposecode, description, document_status, document_name, document_path, blobfile, blobfile_tblname, transactiontype, transactionno, loanremark, loanreturn_remark FROM tbl_loanaccount_summary where (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loanid='" . $loanid . "'";
	} else if ($shortby == "summarywithloanid") {
		$lsqq = "select sno, idate, loanid, countryid, companyid, subcompanyid, projectid, from_accountid, from_accountno, to_accountid, to_accountno, requestno, referenceno, loan_payto, sum(loan_amount) as loan_amount, sum(loan_returnamount) as loan_returnamount, purpose, purposecode, description, document_status, document_name, document_path, blobfile, blobfile_tblname, transactiontype, transactionno, loanremark, loanreturn_remark FROM tbl_loanaccount_summary where (tedit is null or tedit='')  and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  and loanid='" . $loanid . "' group by loanid";
	}
	$lsq = $conn->query($lsqq);
	$loansummary = array();
	$ttlloanamount = 0;
	$ttlloanreturnamount = 0;
	$ttlloanbalanceamount = 0;
	if ($lsq->num_rows > 0) {
		while ($lsrow = $lsq->fetch_assoc()) {
			$ttlloanamount += (float) $lsrow['loan_amount'];
			$ttlloanreturnamount += (float) $lsrow['loan_returnamount'];
			$ttlloanbalanceamount = $ttlloanamount - $ttlloanreturnamount;
			$loansummary[] = array('sno' => $lsrow['sno'], 'idate' => $lsrow['idate'], 'loanid' => $lsrow['loanid'], 'countryid' => $lsrow['countryid'], 'companyid' => $lsrow['companyid'], 'subcompanyid' => $lsrow['subcompanyid'], 'projectid' => $lsrow['projectid'], 'from_accountid' => $lsrow['from_accountod'], 'from_accountno' => $lsrow['from_accountno'], 'to_accountid' => $lsrow['to_accountid'], 'to_accountno' => $lsrow['to_accountno'], 'requestno' => $lsrow['requestno'], 'referenceno' => $lsrow['referenceno'], 'loan_payto' => $lsrow['loan_payto'], 'loan_amount' => $lsrow['loan_amount'], 'loan_returnamount' => $lsrow['loan_returnamount'], 'loanbalance_amount' => $ttlloanbalanceamount, 'purpose' => $lsrow['purpose'], 'purposecode' => $lsrow['purposecode'], 'description' => $lsrow['description'], 'document_status' => $lsrow['document_status'], 'document_name' => $lsrow['document_name'], 'document_path' => $lsrow['document_path'], 'blobfile' => $lsrow['blobfile'], 'blobfile_tblname' => $lsrow['blobfile_tblname'], 'transactiontype' => $lsrow['transactiontype'], 'transactionno' => $lsrow['transactionno'], 'loanremark' => $lsrow['loanremark'], 'loanreturn_remark' => $lsrow['loanreturn_remark']);
		}
	}
	return $loansummary;
}

function getdetailsfromclosetobook($conn, $companyid, $subcompanyid, $projectid, $detailtype)
{
	if ($detailtype == "internaltransfers") {
		$qry = "select w.sno as sno,w.date as withdrawal_date,w.chequeno,w.total_amount as withdrawn_amount,w.payto,w.account_id as fromaccountid,w.description_bank as withdrawal_description,d.sno as dsno,d.date as deposit_date,d.account_id as toaccountid,d.total_amount as deposited_amount, (cast(w.total_amount as double)- cast(d.total_amount as double)) as bank_charge,d.description_bank as deposit_description from (select * from tbl_closetobook where amounttype='withdrawn' and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown')) w left join (select * from tbl_closetobook where amounttype='deposit' and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown')) d on(REGEXP_REPLACE(w.chequeno, '[^0-9]', '')=REGEXP_REPLACE(d.chequeno, '[^0-9]', '')) where w.amounttype='withdrawn' and cast(REGEXP_REPLACE(d.chequeno, '[^0-9]', '') as decimal)>0 and w.company_id='" . $companyid . "' and w.subcompany_id='" . $subcompanyid . "' and w.project_id='" . $projectid . "' and w.account_id<>d.account_id and ifnull(d.chequeno,'')<>''";
	} else if ($detailtype == "reverseentries") {
		$qry = "select w.sno as sno,w.date as withdrawal_date,w.chequeno,w.total_amount as withdrawn_amount,w.payto,w.account_id as fromaccountid,w.description_bank as withdrawal_description,d.sno as dsno,d.date as deposit_date,d.account_id as toaccountid,d.total_amount as deposited_amount, (cast(w.total_amount as double)- cast(d.total_amount as double)) as bank_charge,d.description_bank as deposit_description from (select * from tbl_closetobook where amounttype='withdrawn' and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown')) w left join (select * from tbl_closetobook where amounttype='deposit' and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown')) d on(REGEXP_REPLACE(w.chequeno, '[^0-9]', '')=REGEXP_REPLACE(d.chequeno, '[^0-9]', '')) where w.amounttype='withdrawn' and cast(REGEXP_REPLACE(d.chequeno, '[^0-9]', '') as decimal)>0 and w.company_id='" . $companyid . "' and w.subcompany_id='" . $subcompanyid . "' and w.project_id='" . $projectid . "' and w.account_id=d.account_id and ifnull(d.chequeno,'')<>''";
	}
	$clbkr = $conn->query($qry);
	$clbooklist = array();
	if ($clbkr->num_rows > 0) {
		while ($cbrow = $clbkr->fetch_assoc()) {
			$clbooklist[] = array('sno' => $cbrow['sno'], 'company_id' => $company_id, 'subcompany_id' => $subcompany_id, 'project_id' => $project_id, 'withdrawal_date' => $cbrow['withdrawal_date'], 'chequeno' => $cbrow['chequeno'], 'withdrawn_amount' => $cbrow['withdrawn_amount'], 'payto' => $cbrow['payto'], 'fromaccountid' => $cbrow['fromaccountid'], 'description_bank' => $crow['withdrawal_description'], 'dsno' => $cbrow['dsno'], 'deposit_date' => $cbrow['deposit_date'], 'toaccountid' => $cbrow['toaccountid'], 'deposited_amount' => $cbrow['deposited_amount'], 'bank_charge' => $cbrow['bank_charge'], 'ddescription_bank' => $crow['deposit_description']);
		}
	}
	return $clbooklist;
}

function closetobookdetails($conn, $companyid, $subcompanyid, $projectid, $transactiontype, $shortby, $shortbyvalue = "")
{
	$sortcondition = "";
	if ($shortbyvalue) {
		$sortcondition = "and account_id='" . $shortbyvalue . "'";
	}
	if ($shortby == "all_unreconcile_withaccountid") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' " . $sortcondition . " and ifnull(total_amount,0)>0 and (status is null or status='') and (book_status='' or book_status is null) order by banksno";

	} else if ($shortby == "unreconcile_withaccountid") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' " . $sortcondition . " and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (book_status='' or book_status is null) order by banksno";

	} else if ($shortby == "unreconcile_withaccountidwithdates") {
		$shorexp = explode("|", $shortbyvalue);
		$accountid = $shorexp[0];
		$datefrom = $shorexp[1];
		$dateto = $shorexp[2];
		if (detectDateTimeFormat($shorexp[1])) {
			$datefrom = convertDateToYMD($shorexp[1]);
		}
		if (detectDateTimeFormat($shorexp[2])) {
			$dateto = convertDateToYMD($shorexp[2]);
		}
		if (strtolower($accountid) == "all") {
			$accountid_cnd = "";
		} else {
			$accountid_cnd = "and account_id='" . $accountid . "'";
		}

		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' " . $accountid_cnd . " and date>='" . $datefrom . "' and date<='" . $dateto . "' and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (book_status='' or book_status is null) order by banksno";

	} else if ($shortby == "unreconstruct_withprojectid") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (reconstructstatus='' or reconstructstatus is null) order by banksno";

	} else if ($shortby == "unreconstruct_withaccountid") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' and account_id='" . $shortbyvalue . "' and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (reconstructstatus='' or reconstructstatus is null) order by banksno";

	} else if ($shortby == "unreconstruct_withaccountidwithdates") {
		$shorexp = explode("|", $shortbyvalue);
		$accountid = $shorexp[0];
		$datefrom = $shorexp[1];
		$dateto = $shorexp[2];
		if (detectDateTimeFormat($shorexp[1])) {
			$datefrom = convertDateToYMD($shorexp[1]);
		}
		if (detectDateTimeFormat($shorexp[2])) {
			$dateto = convertDateToYMD($shorexp[2]);
		}
		if (strtolower($accountid) == "all") {
			$accountid_cnd = "";
		} else {
			$accountid_cnd = "and account_id='" . $accountid . "'";
		}

		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' " . $accountid_cnd . " and date>='" . $datefrom . "' and date<='" . $dateto . "' and ifnull(total_amount,0)>0 and (status is null or status='') and (reconciliation is null or reconciliation='') and (reconstructstatus='' or reconstructstatus is null) order by banksno";

	} else if ($shortby == "unknown_withaccountid") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' " . $sortcondition . " and ifnull(total_amount,0)>0 and (status is null or status='') and (book_status='' or book_status is null  or book_status='Unknown') order by banksno";

	} else if ($shortby == "unreconcile_keys_withaccountid") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' " . $sortcondition . " and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown') group by keyid";
	} else if ($shortby == "keyid") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and keyid='" . $shortbyvalue . "' order by keyid";
	} else if ($shortby == "sno") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and (status is null or status='') and (book_status='' or book_status is null or book_status='Unknown') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and  sno='" . $shortbyvalue . "' order by keyid";
	} else if ($shortby == "unreconcile_forconciled") {
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' " . $sortcondition . " and (status is not null and status<>'') and (book_status='' or book_status is null or book_status='Unknown') order by keyid";
	} else if ($shortby == "unreconcilewithamount") {
		$minamount = $shortbyvalue - 10;
		$maxamount = $shortbyvalue + 10;
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' and total_amount>='" . $minamount . "' and total_amount<='" . $maxamount . "' and (status is not null and status<>'') and (book_status='' or book_status is null or book_status='Unknown') order by keyid";
	} else if ($shortby == "unreconcilewithamountanddate") {
		$shortbyvalueexp = explode("|", $shortbyvalue);
		$minamount = $shortbyvalueexp[0] - 10;
		$maxamount = $shortbyvalueexp[0] + 10;
		// echo detectDateTimeFormat($shortbyvalueexp[1]);
		$cdate = "";
		if (detectDateTimeFormat($shortbyvalueexp[1])) {
			$cdate = convertDateToYMD($shortbyvalueexp[1]);
		}
		$clbk = "SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, paytoid, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='" . $companyid . "' and subcompany_id='" . $subcompanyid . "' and project_id='" . $projectid . "' and lcase(amounttype)='" . strtolower($transactiontype) . "' and total_amount>='" . $minamount . "' and total_amount<='" . $maxamount . "' and date>='" . $cdate . "' and (book_status='' or book_status is null or book_status='Unknown') order by keyid";
	}
	$clbkr = $conn->query($clbk);
	$clbooklist = array();
	if ($clbkr->num_rows > 0) {
		while ($cbrow = $clbkr->fetch_assoc()) {
			$bnkinf = getaccountinformation($conn, $cbrow['company_id'], $cbrow['subcompany_id'], $cbrow['project_id'], "accountid", $cbrow['account_id']);
			$accountname = "";
			$accountno = "";
			$ifsccode = "";
			$bankname = "";
			$accountholdername = "";
			$accounttype = "";
			if (count($bnkinf) > 0) {
				foreach ($bnkinf as $bnkey) {
					$accountname = $bnkey['accountname'];
					$accountno = $bnkey['accountno'];
					$ifsccode = $bnkey['ifsccode'];
					$bankname = $bnkey['bankname'];
					$accountholdername = $bnkey['accountholdername'];
					$accounttype = $bnkey['account_type'];
				}
			}

			if ($cbrow['actualtransfered_amount'] > 0) {
				$actualtransfered_amount = $cbrow['actualtransfered_amount'];
			} else {
				$actualtransfered_amount = $cbrow['total_amount'];
			}

			$clbooklist[] = array('sno' => $cbrow['sno'], 'company_id' => $cbrow['company_id'], 'subcompany_id' => $cbrow['subcompany_id'], 'project_id' => $cbrow['project_id'], 'country_id' => $cbrow['country_id'], 'value_date' => $cbrow['date'], 'dspdate' => date('d-M-Y', strtotime($cbrow['date'])), 'paymentmode' => $cbrow['paymentmode'], 'chequeno' => $cbrow['chequeno'], 'payto' => $cbrow['payto'], 'paytoid' => $cbrow['paytoid'], 'beneficiaryname' => $cbrow['beneficiaryname'], 'bankname' => $cbrow['bankname'], 'accountname' => $cbrow['accountname'], 'accountno' => $cbrow['accountno'], 'account_id' => $cbrow['account_id'], 'accounttype' => $accounttype, 'bankid' => $cbrow['bankid'], 'tds' => $cbrow['tds'], 'tds_amount' => $cbrow['tds_amount'], 'total_amount' => $cbrow['total_amount'], 'bankcharge_amount' => $cbrow['bankcharge_amount'], 'actualtransfered_amount' => $actualtransfered_amount, 'amounttype' => $cbrow['amounttype'], 'paymenttype' => $cbrow['paymenttype'], 'purpose' => $cbrow['purpose'] ?? '', 'purpose_code' => $cbrow['purpose_code'] ?? '', 'categoryid' => $cbrow['categoryid'], 'sub_category' => $cbrow['sub_category'], 'category' => $cbrow['category'], 'sub_categoryid' => $cbrow['sub_categoryid'], 'description' => $cbrow['description'], 'description_bank' => $cbrow['description_bank'], 'remarks' => $cbrow['remarks'], 'balancefrombank' => $cbrow['balancefrombank'], 'tedit' => $cbrow['tedit'], 'tsno' => $cbrow['tsno'], 'reference_no' => $cbrow['reference_no'], 'banksno' => $cbrow['banksno'], 'status' => $cbrow['status'], 'source' => $cbrow['source'], 'request_no' => $cbrow['request_no'], 'loginid' => $cbrow['loginid'], 'fileinfo' => $cbrow['fileinfo'], 'key1' => $cbrow['key1'], 'key2' => $cbrow['key2'], 'key3' => $cbrow['key3'], 'keyid' => $cbrow['keyid'], 'accountname' => $accountname, 'accountno' => $accountno, 'bankname' => $bankname);
		}
	}
	return $clbooklist;
}




function bankstatementdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue = "")
{
	if ($shortby == "all_withaccountid") {
		$sortcondition = "and account_id='" . $shortbyvalue . "'";
		$clbk = "SELECT sno, companyid, subcompanyid, projectid, valuedate,postdate, chequeno, description, debitamount, creditamount, balance, account_id,ifnull(verify_balance,'') as verify_balance FROM tbl_bankstatement WHERE (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and account_id='" . $shortbyvalue . "' order by valuedate, sno";
	} else if ($shortby == "unchecked") {
		$clbk = "SELECT sno, companyid, subcompanyid, projectid, valuedate,postdate, chequeno, description, debitamount, creditamount, balance, account_id,ifnull(verify_balance,'') as verify_balance FROM tbl_bank_check WHERE (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and verify_insert='unchecked' order by sno";
	}
	$clbkr = $conn->query($clbk);
	$clbooklist = array();
	if ($clbkr->num_rows > 0) {
		while ($cbrow = $clbkr->fetch_assoc()) {
			$bnkinf = getaccountinformation($conn, $cbrow['companyid'], $cbrow['subcompanyid'], $cbrow['projectid'], "accountid", $cbrow['account_id']);
			$accountno = "";
			$bankname = "";
			$bankid = "";
			$branchname = "";
			if (count($bnkinf) > 0) {
				foreach ($bnkinf as $bnkey) {
					$accountno = $bnkey['accountno'];
					;
					$bankname = $bnkey['bankname'];
					$bankid = $bnkey['bankid'];
					$branchname = $bnkey['branchname'];
				}
			}
			$clbooklist[] = array('sno' => $cbrow['sno'], 'company_id' => $cbrow['companyid'], 'subcompany_id' => $cbrow['subcompanyid'], 'project_id' => $cbrow['projectid'], 'value_date' => $cbrow['valuedate'], 'post_date' => $cbrow['postdate'], 'chequeno' => $cbrow['chequeno'], 'description' => $cbrow['description'], 'debitamount' => $cbrow['debitamount'], 'creditamount' => $cbrow['creditamount'], 'balance' => $cbrow['balance'], 'account_id' => $cbrow['account_id'], 'account_no' => $accountno, 'bank_name' => $bankname, 'bank_id' => $bankid, 'branch_name' => $branchname, 'status' => $cbrow['verify_balance']);
		}
	}
	if ($shortby == "unchecked") {
		$arrayord = checkOrder($clbooklist, "value_date");
		if ($arrayord == "increasing") {
			$clbk = "SELECT sno, companyid, subcompanyid, projectid, valuedate,postdate, chequeno, description, debitamount, creditamount, balance, account_id,ifnull(verify_balance,'') as verify_balance FROM tbl_bank_check WHERE (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and verify_insert='unchecked' order by valuedate, sno";
		} else {
			$clbk = "SELECT sno, companyid, subcompanyid, projectid, valuedate,postdate, chequeno, description, debitamount, creditamount, balance, account_id,ifnull(verify_balance,'') as verify_balance FROM tbl_bank_check WHERE (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and verify_insert='unchecked' order by valuedate, sno desc";
		}
		//echo "Query: ".$clbk;
		$clbkr = $conn->query($clbk);
		$clbooklist = array();
		if ($clbkr->num_rows > 0) {
			while ($cbrow = $clbkr->fetch_assoc()) {
				$bnkinf = getaccountinformation($conn, $cbrow['companyid'], $cbrow['subcompanyid'], $cbrow['projectid'], "accountid", $cbrow['account_id']);
				$accountno = "";
				$bankname = "";
				$bankid = "";
				$branchname = "";
				if (count($bnkinf) > 0) {
					foreach ($bnkinf as $bnkey) {
						$accountno = $bnkey['accountno'];
						;
						$bankname = $bnkey['bankname'];
						$bankid = $bnkey['bankid'];
						$branchname = $bnkey['branchname'];
					}
				}
				$clbooklist[] = array('sno' => $cbrow['sno'], 'company_id' => $cbrow['companyid'], 'subcompany_id' => $cbrow['subcompanyid'], 'project_id' => $cbrow['projectid'], 'value_date' => $cbrow['valuedate'], 'post_date' => $cbrow['postdate'], 'chequeno' => $cbrow['chequeno'], 'description' => $cbrow['description'], 'debitamount' => $cbrow['debitamount'], 'creditamount' => $cbrow['creditamount'], 'balance' => $cbrow['balance'], 'account_id' => $cbrow['account_id'], 'account_no' => $accountno, 'bank_name' => $bankname, 'bank_id' => $bankid, 'branch_name' => $branchname, 'status' => $cbrow['verify_balance']);
			}
		}
	}
	return $clbooklist;
}

function bankstatementpdf($conn, $companyid, $subcompanyid, $projectid, $accountid, $datefrom, $dateto, $rooturl)
{
	$clbk = "SELECT filpathpdf FROM tbl_directuploads where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and accountid='" . $accountid . "' and date('" . $datefrom . "') between date(datefrom) and date(dateto) and date('" . $dateto . "') between date(datefrom) and date(dateto) order by udatetime";
	$clbkr = $conn->query($clbk);
	$fullpath = "";
	$clbkr->num_rows;
	if ($clbkr->num_rows > 0) {
		$cbrow = $clbkr->fetch_assoc();
		$fullpath = $rooturl . $cbrow['filpathpdf'];
	}
	return $fullpath;
}

// this function did not used
// function getloanmasterinfo($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue){
// 	if($shortby=="accountno"){
// 		$lq=$conn->query("select sno, loanid, loanto_bankname, loanto_accountno, loanto_accountifsc, loanto_name, mobileno, address, countryid, companyid, subcompanyid, projectid FROM tbl_loanaccount_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in('".str_replace(",", "','", $projectid)."') and loanto_accountno='".$shortbyvalue."'");
// 	}else if($shortby=="loanid"){
// 		$lq=$conn->query("select sno, loanid, loanto_bankname, loanto_accountno, loanto_accountifsc, loanto_name, mobileno, address, countryid, companyid, subcompanyid, projectid FROM tbl_loanaccount_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in('".str_replace(",", "','", $projectid)."') and loanid='".$shortbyvalue."'");
// 	}else if($shortby=="all_withoutexcludaccountno"){
// 		$lq=$conn->query("select sno, loanid, loanto_bankname, loanto_accountno, loanto_accountifsc, loanto_name, mobileno, address, countryid, companyid, subcompanyid, projectid FROM tbl_loanaccount_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in('".str_replace(",", "','", $projectid)."') and loanto_accountno<>'".$shortbyvalue."'");
// 	}else if($shortby=="all_withexcludaccountno"){
// 		$lq=$conn->query("select sno, loanid, loanto_bankname, loanto_accountno, loanto_accountifsc, loanto_name, mobileno, address, countryid, companyid, subcompanyid, projectid FROM tbl_loanaccount_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in('".str_replace(",", "','", $projectid)."')");
// 	}else if($shortby=="projectid"){
// 		$lq=$conn->query("select sno, loanid, loanto_bankname, loanto_accountno, loanto_accountifsc, loanto_name, mobileno, address, countryid, companyid, subcompanyid, projectid FROM tbl_loanaccount_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid in('".str_replace(",", "','", $projectid)."')");
// 	}else if($shortby=="subcompanyid"){
// 		$lq=$conn->query("select sno, loanid, loanto_bankname, loanto_accountno, loanto_accountifsc, loanto_name, mobileno, address, countryid, companyid, subcompanyid, projectid FROM tbl_loanaccount_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".str_replace(",", "','", $subcompanyid)."'");
// 	}else if($shortby=="companyid"){
// 		$lq=$conn->query("select sno, loanid, loanto_bankname, loanto_accountno, loanto_accountifsc, loanto_name, mobileno, address, countryid, companyid, subcompanyid, projectid FROM tbl_loanaccount_master where (tedit is null or tedit='') and companyid='".$companyid."'");
// 	}
// 	$loaninfo= array();
// 	if($lq->num_rows>0){
// 		while($lrow=$lq->fetch_assoc()){
// 			$loaninfo[]=array('sno'=>$lrow['sno'], 'loanid'=>$lrow['loanid'], 'loanto_bankname'=>$lrow['loanto_bankname'], 'loanto_accountno'=>$lrow['loanto_accountno'], 'loanto_accountifsc'=>$lrow['loanto_accountifsc'], 'loanto_name'=>$lrow['loanto_name'], 'mobileno'=>$lrow['mobileno'], 'address'=>$lrow['address'], 'countryid'=>$lrow['countryid'], 'companyid'=>$lrow['companyid'], 'subcompanyid'=>$lrow['subcompanyid'], 'projectid'=>$lrow['projectid']);
// 		}
// 	}
// 	return $loaninfo;
// }


function copypurposecategory($conn, $companyid, $subcompanyid, $projectid)
{
	$qry = "select categoryid,category,sub_categoryid,sub_category from purpose_category_master";
	$result_qry = $conn->query($qry);
	if ($result_qry->num_rows > 0) {
		while ($irow = $result_qry->fetch_assoc()) {

			$qryse = "select * from purpose_category_masterX where projectid='" . $projectid . "' and categoryid='" . $irow['categoryid'] . "' and sub_categoryid='" . $irow['sub_categoryid'] . "' and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "'";
			$result_qryse = $conn->query($qryse);
			if ($result_qryse->num_rows > 0) {
				# code...
			} else {
				$qryin = "Insert into purpose_category_masterX(categoryid,category,sub_categoryid,sub_category,cmp_id,subcmp_id,projectid)values('" . $irow['categoryid'] . "','" . $irow['category'] . "','" . $irow['sub_categoryid'] . "','" . $irow['sub_category'] . "','" . $companyid . "','" . $subcompanyid . "','" . $projectid . "')";
				$result_qurin = $conn->query($qryin);
			}

		}

	}
}

function copypurposmaster($conn, $companyid, $subcompanyid, $projectid)
{
	$qry = "select categoryid,category,sub_categoryid,sub_category,purpose,purposecode,orderby from purpose_code_master";
	$result_qry = $conn->query($qry);
	if ($result_qry->num_rows > 0) {
		while ($irow = $result_qry->fetch_assoc()) {

			$qryse = "select * from purpose_code_masterX where projectid='" . $projectid . "' and categoryid='" . $irow['categoryid'] . "' and sub_categoryid='" . $irow['sub_categoryid'] . "' and cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and purpose='" . $irow['purpose'] . "' and  purposecode='" . $irow['purposecode'] . "'";
			$result_qryse = $conn->query($qryse);
			if ($result_qryse->num_rows > 0) {
				# code...
			} else {
				$qryin = "Insert into purpose_code_masterX(categoryid,category,sub_categoryid,sub_category,cmp_id,subcmp_id,projectid,purpose,purposecode,orderby)values('" . $irow['categoryid'] . "','" . $irow['category'] . "','" . $irow['sub_categoryid'] . "','" . $irow['sub_category'] . "','" . $companyid . "','" . $subcompanyid . "','" . $projectid . "',purpose='" . $irow['purpose'] . "',purposecode='" . $irow['purposecode'] . "',orderby='" . $irow['orderby'] . "')";
				$result_qurin = $conn->query($qryin);
			}

		}

	}
}
function getpurposecatorsubcat($conn, $companyid, $subcompanyid, $projectid, $shortby)
{
	$pcatorsbucatlist = array();
	if ($shortby == "category") {
		$qry = "select distinct categoryid,category from purpose_category_masterX";
		$result_qry = $conn->query($qry);
		if ($result_qry->num_rows > 0) {
			while ($irow = $result_qry->fetch_assoc()) {
				$pcatorsbucatlist[] = array('categoryid' => $irow['categoryid'], 'category' => $irow['category']);

			}

		}

	} elseif ($shortby == "subcategory") {
		$qry = "select distinct sub_categoryid,sub_category from purpose_category_masterX";
		$result_qry = $conn->query($qry);
		if ($result_qry->num_rows > 0) {
			while ($irow = $result_qry->fetch_assoc()) {
				$pcatorsbucatlist[] = array('categoryid' => $irow['sub_categoryid'], 'category' => $irow['sub_category']);

			}

		}
	}
	return $pcatorsbucatlist;
}

function getmaxcategory($conn, $companyid, $subcompanyid, $projectid, $shortby)
{
	$maxid = "";
	if ($shortby == "category") {
		$qry = "select max(categoryid+1) as maxid from purpose_category_masterX where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'";
		$result_qry = $conn->query($qry);
		if ($result_qry->num_rows > 0) {
			$irow = $result_qry->fetch_assoc();
			$maxid = $irow['maxid'];
		}

	} elseif ($shortby == "subcategory") {
		$qry = "select max(sub_categoryid+1) from purpose_category_masterX where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "'";
		$result_qry = $conn->query($qry);
		if ($result_qry->num_rows > 0) {

			$irow = $result_qry->fetch_assoc();
			$maxid = $irow['maxid'];


		}
	}

	return $maxid;
}

function Getmimecode($ext)
{
	$suffix = "";
	if (strtolower($ext) == "doc") {
		$suffix = "@file/msword";
	} elseif (strtolower($ext) == "docx") {
		$suffix = "@file/vnd.openxmlformats-officedocument.wordprocessingml.document";
	} elseif (strtolower($ext) == "xls") {
		$suffix = "@file/vnd.ms-excel";
	} elseif (strtolower($ext) == "xlsx") {
		$suffix = "@file/vnd.openxmlformats-officedocument.spreadsheetml.sheet";
	} elseif (strtolower($ext) == "txt") {
		$suffix = "text/plain";
	} elseif (strtolower($ext) == "pdf") {
		$suffix = "application/pdf";
	} elseif (strtolower($ext) == "gif") {
		$suffix = "image/gif";
	} elseif (strtolower($ext) == "jpg") {
		$suffix = "image/jpeg";
	} elseif (strtolower($ext) == "jpeg") {
		$suffix = "image/jpeg";
	} elseif (strtolower($ext) == "png") {
		$suffix = "image/png";
	}
	return $suffix;
}

function GetPurposeInfofrommasterXXXX($conn, $projectid, $pcategory)
{

	$pqury = "select purposecode,purpose,category,sub_category,categoryid,sub_categoryid FROM purpose_code_master where  projectid='" . $projectid . "' and category='" . $pcategory . "'";

	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('pcategory' => $prow['category'], 'purpose' => $prow['purpose'], 'purposecode' => $prow['purposecode'], 'subcategory' => $prow['sub_category'], 'categoryid' => $prow['categoryid'], 'subcategoryid' => $prow['sub_categoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}

function GetPurposeInfofrommasterbycateorsubcate($conn, $projectid, $pcategoryid, $psubcategoryid)
{

	$pqury = "select purposecode,purpose,category,sub_category,categoryid,sub_categoryid FROM purpose_code_master where  projectid='" . $projectid . "' and categoryid='" . $pcategoryid . "' and sub_categoryid='" . $psubcategoryid . "' and (tedit is null or tedit='')";

	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('pcategory' => $prow['category'], 'purpose' => $prow['purpose'], 'purposecode' => $prow['purposecode'], 'subcategory' => $prow['sub_category'], 'categoryid' => $prow['categoryid'], 'subcategoryid' => $prow['sub_categoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}

function GetPurposeInfofrommasterbycateorsubcate_credit($conn, $projectid, $pcategoryid, $psubcategoryid)
{

	$pqury = "select purpose_code,purpose,category,subcategory,categoryid,subcategoryid FROM tbl_purpose_master_credit where  projectid='" . $projectid . "' and categoryid='" . $pcategoryid . "' and subcategoryid='" . $psubcategoryid . "'";

	$pqury_rslt = $conn->query($pqury);
	if ($pqury_rslt->num_rows > 0) {
		while ($prow = $pqury_rslt->fetch_assoc()) {
			$purpose_category[] = array('pcategory' => $prow['category'], 'purpose' => $prow['purpose'], 'purposecode' => $prow['purpose_code'], 'subcategory' => $prow['subcategory'], 'categoryid' => $prow['categoryid'], 'subcategoryid' => $prow['subcategoryid']);
		}
	} else {
		$purpose_category = array();
	}
	return $purpose_category;
}

function getitemmaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{

	if ($shortby == "projectid") {
		$itmq = "select sno, countryid, companyid, subcompanyid, projectid, itemid, itemname, itemtypeid, itembrandid, qrcode, hsncode, batchcode FROM tbl_item_master WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "subcompanyid") {
		$itmq = "select sno, countryid, companyid, subcompanyid, projectid, itemid, itemname, itemtypeid, itembrandid, qrcode, hsncode, batchcode FROM tbl_item_master WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "'";
	} else if ($shortby == "itemid") {
		$itmq = "select sno, countryid, companyid, subcompanyid, projectid, itemid, itemname, itemtypeid, itembrandid, qrcode, hsncode, batchcode FROM tbl_item_master WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and itemid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$itmq = "select sno, countryid, companyid, subcompanyid, projectid, itemid, itemname, itemtypeid, itembrandid, qrcode, hsncode, batchcode FROM tbl_item_master WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}

	$itmqresutl = $conn->query($itmq);
	$itemlist = array();
	if ($itmqresutl->num_rows > 0) {
		while ($irow = $itmqresutl->fetch_assoc()) {
			$itemlist[] = array('sno' => $irow['sno'], 'itemid' => $irow['itemid'], 'itemname' => $irow['itemname'], 'itemtypeid' => $irow['itemtypeid'], 'itembrandid' => $irow['itembrandid'], 'qrcode' => $irow['qrcode'], 'hsncode' => $irow['hsncode'], 'batchcode' => $irow['batchcode']);
		}
	}
	return $itemlist;
}

function getitemmasterfromreadytosellgoods($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue, $categoryid)
{
	$tablename = "strs_" . $projectid . "_readytosellgoods";

	if ($shortby == "projectid" && $categoryid != "") {
		$itmq = "select sno,id,itemname,itemcode,unitsell,unitmeasurable FROM " . $tablename . " WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and itemcategory='" . $categoryid . "'";
	} else if ($shortby == "subcompanyid" && $categoryid != "") {
		$itmq = "select sno,id,itemname,itemcode,unitsell,unitmeasurable FROM " . $tablename . " WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and itemcategory='" . $categoryid . "'";
	} else if ($shortby == "itemid" && $categoryid != "") {
		$itmq = "select sno,id,itemname,itemcode,unitsell,unitmeasurable FROM " . $tablename . " WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and id='" . $shortbyvalue . "' and itemcategory='" . $categoryid . "'";
	} else if ($shortby == "projectid" && $categoryid == "") {
		$itmq = "select sno,id,itemname,itemcode,unitsell,unitmeasurable FROM " . $tablename . " WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' ";
	} else if ($shortby == "subcompanyid" && $categoryid == "") {
		$itmq = "select sno,id,itemname,itemcode,unitsell,unitmeasurable FROM " . $tablename . " WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' ";
	} else if ($shortby == "itemid" && $categoryid == "") {
		$itmq = "select sno,id,itemname,itemcode,unitsell,unitmeasurable FROM " . $tablename . " WHERE (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and id='" . $shortbyvalue . "' ";
	}

	$itmqresutl = $conn->query($itmq);
	$itemlist = array();
	if ($itmqresutl->num_rows > 0) {
		while ($irow = $itmqresutl->fetch_assoc()) {
			$itemlist[] = array('sno' => $irow['sno'], 'itemid' => $irow['id'], 'itemname' => $irow['itemname'], 'itemtypeid' => "", 'itembrandid' => "", 'itemcode' => $irow['itemcode'], 'hsncode' => "", 'batchcode' => "", 'sellunit' => $irow['unitsell'], 'measurablunit' => $irow['unitmeasurable']);
		}
	}
	return $itemlist;
}
function getitemcategory($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	$table1 = "strs_" . $projectid . "_readytosellgoods";
	$table2 = "strs_" . $projectid . "_categorymaster";
	if ($shortby == "projectid") {
		$itmq = "select distinct m.itemcategory as categoryid,b.categoryname FROM " . $table1 . " as m join " . $table2 . " as b on b.id=m.itemcategory  WHERE (b.tedit is null or b.tedit='') and  m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "'";
	} else if ($shortby == "subcompanyid") {
		$itmq = "select distinct m.itemcategory as categoryid,b.categoryname FROM " . $table1 . " as m join " . $table2 . " as b on b.id=m.itemcategory WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "'";
	} else if ($shortby == "itemid") {
		$itmq = "select distinct m.itemcategory as categoryid,b.categoryname FROM " . $table1 . " as m join " . $table2 . " as b on b.id=m.itemcategory WHERE (b.tedit is null or b.tedit='') and  b.companyid='" . $companyid . "' and b.subcompanyid='" . $subcompanyid . "' and b.projectid='" . $projectid . "' and b.id='" . $shortbyvalue . "'";
	}

	$itmqresutl = $conn->query($itmq);
	$itemlist = array();
	if ($itmqresutl->num_rows > 0) {
		while ($irow = $itmqresutl->fetch_assoc()) {
			$itemlist[] = array('categoryid' => $irow['categoryid'], 'categoryname' => $irow['categoryname']);
		}
	}
	return $itemlist;
}

function getitempricemaster($conn, $companyid, $subcompanyid, $projectid, $clientid, $shortby, $shortbyvalue)
{
	if ($shortby == "itemid") {
		$itmprq = "select sno, countryid, companyid, subcompanyid, projectid, itemid, clientid, ifnull(unitprice,0) as unitprice, ifnull(vat,0) as vat, othercharg_term, ifnull(deleveredprice,0) as deleveredprice, cgst, sgst, igst from tbl_itemprice_master where (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and clientid='" . $clientid . "' and itemid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$itmprq = "select select sno, countryid, companyid, subcompanyid, projectid, itemid, clientid, ifnull(unitprice,0) as unitprice, ifnull(vat,0) as vat, othercharg_term, ifnull(deleveredprice,0) as deleveredprice, cgst, sgst, igst from tbl_itemprice_master where (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}

	$itmprqresutl = $conn->query($itmprq);
	$itempricelist = array();
	if ($itmprqresutl->num_rows > 0) {
		while ($itprow = $itmprqresutl->fetch_assoc()) {
			$itempricelist[] = array('sno' => $itprow['sno'], 'countryid' => $itprow['countryid'], 'companyid' => $itprow['companyid'], 'subcompanyid' => $itprow['subcompanyid'], 'projectid' => $itprow['projectid'], 'itemid' => $itprow['itemid'], 'clientid' => $itprow['clientid'], 'unitprice' => $itprow['unitprice'], 'vat' => $itprow['vat'], 'othercharg_term' => $itprow['othercharg_term'], 'deleveredprice' => $itprow['deleveredprice'], 'cgst' => $itprow['cgst'], 'sgst' => $itprow['sgst'], 'igst' => $itprow['igst']);
		}
	}
	return $itempricelist;
}

function getitemtypemaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithsubcompany") {
		$itmtype = "select sno, countryid, companyid, subcompanyid, projectid, itemtypeid, itemtypename from tbl_item_typemaster where (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "'";
	} else if ($shortby == "allwithproject") {
		$itmtype = "select sno, countryid, companyid, subcompanyid, projectid, itemtypeid, itemtypename from tbl_item_typemaster where (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "sno") {
		$itmtype = "select sno, countryid, companyid, subcompanyid, projectid, itemtypeid, itemtypename from tbl_item_typemaster where (tedit is null or tedit='') and sno='" . $shortbyvalue . "'";
	}
	$itmtyperesult = $conn->query($itmtype);
	$itmtypelist = array();
	if ($itmtyperesult->num_rows > 0) {
		while ($itprow = $itmtyperesult->fetch_assoc()) {
			$itmtypelist[] = array('sno' => $itprow['sno'], 'itemtypeid' => $itprow['itemtypeid'], 'itemtypename' => $itprow['itemtypename']);
		}
	}
	return $itmtypelist;
}

function getitembrandemaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithsubcompany") {
		$itmbrnd = "select sno, countryid, companyid, subcompanyid, projectid, itembrandid, itembrandname from tbl_item_brandemaster where (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "'";
	} else if ($shortby == "allwithproject") {
		$itmbrnd = "select sno, countryid, companyid, subcompanyid, projectid, itembrandid, itembrandname from tbl_item_brandemaster where (tedit is null or tedit='') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "sno") {
		$itmbrnd = "select sno, countryid, companyid, subcompanyid, projectid, itembrandid, itembrandname from tbl_item_brandemaster where (tedit is null or tedit='') and sno='" . $shortbyvalue . "'";
	}
	$itmbrndresult = $conn->query($itmbrnd);
	$itmbrndlist = array();
	if ($itmbrndresult->num_rows > 0) {
		while ($itprow = $itmbrndresult->fetch_assoc()) {
			$itmbrndlist[] = array('sno' => $itprow['sno'], 'itembrandid' => $itprow['itembrandid'], 'itembrandname' => $itprow['itembrandname']);
		}
	}
	return $itmbrndlist;
}

function employeemaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allactiveprojectemployee") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (resign is null or resign='') and (tedit is null or tedit='')";
	} else if ($shortby == "projectactive_regularemployee") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (resign is null or resign='') and (tedit is null or tedit='') and employmenttype='regular'";
	} else if ($shortby == "active_regularemployeeid") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and empid='" . $shortbyvalue . "'  and (resign is null or resign='') and (tedit is null or tedit='') and employmenttype='regular'";
	} else if ($shortby == "projectactive_parttimeemployee") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (resign is null or resign='') and (tedit is null or tedit='') and employmenttype='parttime'";
	} else if ($shortby == "active_parttimeemployeeid") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and empid='" . $shortbyvalue . "'  and (resign is null or resign='') and (tedit is null or tedit='') and employmenttype='parttime'";
	} else if ($shortby == "projectactive_traineeemployee") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (resign is null or resign='') and (tedit is null or tedit='') and employmenttype='trainee'";
	} else if ($shortby == "active_traineeemployeeid") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and empid='" . $shortbyvalue . "'  and (resign is null or resign='') and (tedit is null or tedit='') and employmenttype='trainee'";
	} else if ($shortby == "employeeid") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and empid='" . $shortbyvalue . "'  and (resign is null or resign='') and (tedit is null or tedit='')";
	} else if ($shortby == "employeename") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(empname)='" . strtolower($shortbyvalue) . "'  and (resign is null or resign='') and (tedit is null or tedit='')";
	} else if ($shortby == "employeeaccountno") {
		$sq = "SELECT sno, companyid, subcompanyid, projectid, empid, empname, gender, dob, religion, basicsalary, hra, conveyance, otherallowance, designationid, designationname, prv_designationid, presentaddress, permanentaddress, mobileno, emailid, bankname, accountno, ifsccode, nationality, mstatus, fathername, fatherage, mothername, motherage, spousename, spouseage, experiance, reference1, reference2, doj, resign, dor, dor_comment, pramoted_date, probationperiod, probationyr, dutyintime, dutyouttime, lunchperiod, weekend, pfsalary, pfcategory, payment_mode, leaveallow_permonth, minovertime, maxovertime, allowncespercentage, allownces_name, deduction_percentage, deduction_name, pancardno, adharcardno, empphoto, epfstatus, pf_uanno, esistatus, esic_in, itax, misc, advance, rds, lic, timeflexibility, workinghours, intime, outtime, noofpaidleave, relaxationtime, workingsundays, overtime, overtimeperday, overtimepermonth, upload_picture, specialisation, institution, highestqualificationn, documentpath, adhardoc, pandoc, bankbranch, upload_bank, upload_education, upload_resume, year, emp_type, outstation, updateby, updatedatetime, ignoretimeout, city, district, state, pincode, employmenttype, attendance_required, purposecode FROM payroll_employeemaster WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and accountno='" . $shortbyvalue . "'  and (resign is null or resign='') and (tedit is null or tedit='')";
	}
	$sqr = $conn->query($sq);
	$elist = array();
	$ttlsalary = 0;
	if ($sqr->num_rows > 0) {
		while ($erow = $sqr->fetch_assoc()) {
			$ttlsalary = $erow['basicsalary'] + $erow['hra'] + $erow['conveyance'] + $erow['otherallowance'];

			$elist[] = array('sno' => $erow['sno'], 'companyid' => $erow['companyid'], 'subcompanyid' => $erow['subcompanyid'], 'projectid' => $erow['projectid'], 'empid' => $erow['empid'], 'empname' => $erow['empname'], 'paytoname' => $erow['empname'], 'gender' => $erow['gender'], 'dob' => $erow['dob'], 'religion' => $erow['religion'], 'basicsalary' => $erow['basicsalary'], 'hra' => $erow['hra'], 'conveyance' => $erow['conveyance'], 'otherallowance' => $erow['otherallowance'], 'designationid' => $erow['designationid'], 'designationname' => $erow['designationname'], 'prv_designationid' => $erow['prv_designationid'], 'presentaddress' => $erow['presentaddress'], 'permanentaddress' => $erow['permanentaddress'], 'mobileno' => $erow['mobileno'], 'emailid' => $erow['emailid'], 'bankname' => $erow['bankname'], 'accountno' => $erow['accountno'], 'ifsccode' => $erow['ifsccode'], 'nationality' => $erow['nationality'], 'mstatus' => $erow['mstatus'], 'fathername' => $erow['fathername'], 'fatherage' => $erow['fatherage'], 'mothername' => $erow['mothername'], 'motherage' => $erow['motherage'], 'spousename' => $erow['spousename'], 'spouseage' => $erow['spouseage'], 'experiance' => $erow['experiance'], 'reference1' => $erow['reference1'], 'reference2' => $erow['reference2'], 'doj' => $erow['doj'], 'resign' => $erow['resign'], 'dor' => $erow['dor'], 'dor_comment' => $erow['dor_comment'], 'pramoted_date' => $erow['pramoted_date'], 'probationperiod' => $erow['probationperiod'], 'probationyr' => $erow['probationyr'], 'dutyintime' => $erow['dutyintime'], 'dutyouttime' => $erow['dutyouttime'], 'lunchperiod' => $erow['lunchperiod'], 'weekend' => $erow['weekend'], 'pfsalary' => $erow['pfsalary'], 'pfcategory' => $erow['pfcategory'], 'payment_mode' => $erow['payment_mode'], 'leaveallow_permonth' => $erow['leaveallow_permonth'], 'minovertime' => $erow['minovertime'], 'maxovertime' => $erow['maxovertime'], 'allowncespercentage' => $erow['allowncespercentage'], 'allownces_name' => $erow['allownces_name'], 'deduction_percentage' => $erow['deduction_percentage'], 'deduction_name' => $erow['deduction_name'], 'pancardno' => $erow['pancardno'], 'adharcardno' => $erow['adharcardno'], 'empphoto' => $erow['empphoto'], 'epfstatus' => $erow['epfstatus'], 'pf_uanno' => $erow['pf_uanno'], 'esistatus' => $erow['esistatus'], 'esic_in' => $erow['esic_in'], 'itax' => $erow['itax'], 'misc' => $erow['misc'], 'advance' => $erow['advance'], 'rds' => $erow['rds'], 'lic' => $erow['lic'], 'timeflexibility' => $erow['timeflexibility'], 'workinghours' => $erow['workinghours'], 'intime' => $erow['intime'], 'outtime' => $erow['outtime'], 'noofpaidleave' => $erow['noofpaidleave'], 'relaxationtime' => $erow['relaxationtime'], 'workingsundays' => $erow['workingsundays'], 'overtime' => $erow['overtime'], 'overtimeperday' => $erow['overtimeperday'], 'overtimepermonth' => $erow['overtimepermonth'], 'upload_picture' => $erow['upload_picture'], 'specialisation' => $erow['specialisation'], 'institution' => $erow['institution'], 'highestqualificationn' => $erow['highestqualificationn'], 'documentpath' => $erow['documentpath'], 'adhardoc' => $erow['adhardoc'], 'pandoc' => $erow['pandoc'], 'bankbranch' => $erow['bankbranch'], 'upload_bank' => $erow['upload_bank'], 'upload_education' => $erow['upload_education'], 'upload_resume' => $erow['upload_resume'], 'year' => $erow['year'], 'emp_type' => $erow['emp_type'], 'outstation' => $erow['outstation'], 'updateby' => $erow['updateby'], 'updatedatetime' => $erow['updatedatetime'], 'ignoretimeout' => $erow['ignoretimeout'], 'city' => $erow['city'], 'district' => $erow['district'], 'state' => $erow['state'], 'pincode' => $erow['pincode'], 'employmenttype' => $erow['employmenttype'], 'attendance_required' => $erow['attendance_required'], 'purposecode' => $erow['purposecode'], 'ttlsalary' => $ttlsalary);
		}
	}
	return $elist;
}

function getemailbodybyid($conn, $eid)
{
	$emq = "select ID, EmailFrom, EmailFromP, EmailTo, Emailcc, DateE, DateDb, Status, Type, Del, Subject, Message, Message_html FROM emailtodb_email WHERE ID='" . $eid . "'";
	$ebody_result = $conn->query($emq);
	if ($ebody_result->num_rows > 0) {
		$emrow = $ebody_result->fetch_assoc();
		$emaibodytext = $emrow['Message'];

	}
	return $emaibodytext;
}

function getemailpurposefromdigitizaedemail($conn, $eid)
{
	$emq = "select update_emailpurpose FROM emailtodb_email WHERE ID='" . $eid . "'";
	$ebody_result = $conn->query($emq);
	if ($ebody_result->num_rows > 0) {
		$emrow = $ebody_result->fetch_assoc();
		$emaipurpose = $emrow['update_emailpurpose'];

	}
	return $emaipurpose;
}

function getfinancialreporttypelist($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "reporttypelist") {
		$rrp = "SELECT reporttype, reportperiod FROM tbl_financialreporttype_master WHERE (activestatus is null or activestatus='' or activestatus='active') group by reporttype";
	} else if ($shortby == "reportperiodlist") {
		$rrp = "SELECT reporttype, reportperiod FROM tbl_financialreporttype_master WHERE (activestatus is null or activestatus='' or activestatus='active') group by reportperiod";
	}
	$rrpr = $conn->query($rrp);
	$reporttypelist = array();
	if ($rrpr->num_rows > 0) {
		while ($rtrow = $rrpr->fetch_assoc()) {
			$reporttypelist[] = array('reporttype' => $rtrow['reporttype'], 'reportperiod' => $rtrow['reportperiod']);
		}
	}
	return $reporttypelist;
}

function getfinancialyreportdetails($conn, $companyid, $subcompanyid, $projectid, $reporttype, $timeperiod, $datefrom, $dateto, $shortby, $shortbyvalue)
{
	if ($shortby == "allfinancialyearreport") {
		$fft = "SELECT sno, companyid, subcompanyid, projectid, projectname, financialyear, datefrom, dateto, reporttype, timeperiod, localfilepath, reportfilepath FROM tbl_financialreport_details WHERE (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and datefrom=>'" . $datefrom . "' and dateto=>'" . $dateto . "'";
	} else if ($shortby == "compaletfinancialyearreport") {
		$fft = "SELECT sno, companyid, subcompanyid, projectid, projectname, financialyear, datefrom, dateto, reporttype, timeperiod, localfilepath, reportfilepath FROM tbl_financialreport_details WHERE (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and lcase(reporttype)='" . strtolower($reporttype) . "' and lcase(timeperiod)='" . strtolower($timeperiod) . "' and datefrom=>'" . $datefrom . "' and dateto=>'" . $dateto . "'";
	} else if ($shortby == "financialyearlist") {
		$fft = "SELECT sno, companyid, subcompanyid, projectid, projectname, financialyear, datefrom, dateto, reporttype, timeperiod, localfilepath, reportfilepath FROM tbl_financialreport_details WHERE (Tedit is null or Tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'  group by financialyear";
	}
	$fftr = $conn->query($fft);
	$ftreportlist = array();
	if ($fftr->num_rows > 0) {
		while ($frow = $fftr->fetch_assoc()) {
			$ftreportlist[] = array('sno' => $frow['sno'], 'companyid' => $frow['companyid'], 'subcompanyid' => $frow['subcompanyid'], 'projectid' => $frow['projectid'], 'projectname' => $frow['projectname'], 'financialyear' => $frow['financialyear'], 'datefrom' => $frow['datefrom'], 'dateto' => $frow['dateto'], 'reporttype' => $frow['reporttype'], 'timeperiod' => $frow['timeperiod'], 'localfilepath' => $frow['localfilepath'], 'reportfilepath' => $frow['reportfilepath']);
		}
	}
	return $ftreportlist;
}


//Get Full Version Report Link
function GetFinancialReportFilepath($conn, $companyid, $subcompanyid, $projectid, $reporttype, $timeperiod, $datefrom, $dateto)
{
	if ($reporttype == 'audited') {
		$reporttype = 'AuditedFull';
	} else if ($reporttype == 'un-audited') {
		$reporttype = 'Un-AuditedFull';
	}
	$GetFVReport = "SELECT sno, reporttype, reportfilepath FROM tbl_financialreport_details where  and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and financialyear='" . $financialyear . "' and reporttype = '" . $reporttype . "' and timeperiod='" . $timeperiod . "' and datefrom='" . $datefrom . "' and dateto='" . $dateto . "' and (Tedit is null or Tedit='')";
	$GFVR = $conn->query($GetFVReport);
	$rplist = array();
	if ($GFVR->num_rows > 0) {
		while ($row = $GFVR->fetch_assoc()) {
			$rplist[] = array('sno' => $row['sno'], 'reporttype' => $row['reporttype'], 'reportfilepath' => $row['reportfilepath']);
		}
	}
	return $rplist;
}

function getlogindetailsfrommaster($conn, $companyid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithcompanyid") {
		$lgmq = "select sno, user_id,cmp_id, group_concat(subcmp_id) as subcmp_id, loginname, loginid, logintype, loginpassword, loginpassword_n, mobileno, emailid, isActive, isLogined, loginDatetime, address, comments FROM login_authorized_master WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "'";
	} else if ($shortby == "loginidwithcompanyid") {
		$lgmq = "select sno,user_id, cmp_id,  subcmp_id, loginname, loginid, logintype, loginpassword, loginpassword_n, mobileno, emailid, isActive, isLogined, loginDatetime, address, comments FROM login_authorized_master WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and loginid='" . $shortbyvalue . "' ";
	} else if ($shortby == "loginidwithsubcompanyid") {
		$lgmq = "select sno,user_id, cmp_id, group_concat(subcmp_id) as subcmp_id, loginname, loginid, logintype, loginpassword, loginpassword_n, mobileno, emailid, isActive, isLogined, loginDatetime, address, comments FROM login_authorized_master WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and subcmp_id in('" . str_replace(",", "','", $shortbyvalue) . "')";
	} else if ($shortby == "loginid") {
		$lgmq = "select sno,user_id, cmp_id, group_concat(subcmp_id) as subcmp_id, loginname, loginid, logintype, loginpassword, loginpassword_n, mobileno, emailid, isActive, isLogined, loginDatetime, address, comments FROM login_authorized_master WHERE (tedit is null or tedit='') and cmp_id='" . $companyid . "' and loginid='" . $shortbyvalue . "'";
	}
	$lgmq_rslt = $conn->query($lgmq);
	$loginmasterlist = array();
	if ($lgmq_rslt->num_rows > 0) {
		while ($lmrow = $lgmq_rslt->fetch_assoc()) {
			$loginmasterlist[] = array('sno' => $lmrow['sno'], 'userid' => $lmrow['user_id'], 'cmp_id' => $lmrow['cmp_id'], 'subcmp_id' => $lmrow['subcmp_id'], 'loginname' => $lmrow['loginname'], 'loginid' => $lmrow['loginid'], 'logintype' => $lmrow['logintype'], 'loginpassword' => $lmrow['loginpassword'], 'loginpassword_n' => $lmrow['loginpassword_n'], 'mobileno' => $lmrow['mobileno'], 'emailid' => $lmrow['emailid'], 'isActive' => $lmrow['isActive'], 'isLogined' => $lmrow['isLogined'], 'loginDatetime' => $lmrow['loginDatetime'], 'address' => $lmrow['address'], 'comments' => $lmrow['comments']);
		}
	}
	return $loginmasterlist;
}


// function loginauthorizedproject($conn,$companyid,$subcompanyid,$loginid,$shortby,$shortbyvalue){
// 	if($shortby=="allcompany"){
// 		$auprjq=$conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and loginid='".$loginid."'");
// 	}else if($shortby=="allsubcompany"){
// 		$auprjq=$conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='".$companyid."' and loginid='".$loginid."'");
// 	}else if($shortby=="allproject"){
// 		$auprjq=$conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and loginid='".$loginid."'");
// 	}else if($shortby=="sno"){
// 		$auprjq=$conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and sno='".$shortbyvalue."'");
// 	}
// 	$authorizeprjlist=array();
// 	if($auprjq->num_rows>0){
// 		while($auprow=$auprjq->fetch_assoc()){
// 			$authorizeprjlist[]=array('sno'=>$auprow['sno'], 'companyid'=>$auprow['companyid'], 'subcompanyid'=>$auprow['subcompanyid'], 'projectid'=>$auprow['projectid'], 'loginid'=>$auprow['loginid'], 'authrizedinfo'=>$auprow['authrizedinfo']);
// 		}
// 	}
// 	return $authorizeprjlist;
// }

function loginauthorizedmodules_old($conn, $companyid, $subcompanyid, $projectid, $loginid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithloginid") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' order by moduleid");
	} else if ($shortby == "allwithmoduleid") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $loginid . "' and moduleid='" . $shortbyvalue . "' order by moduleid");
	} else if ($shortby == "onlywithmoduleid") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and  moduleid='" . $shortbyvalue . "' order by moduleid");
	} else if ($shortby == "sno") {
		$auldq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, loginid, moduleid, status, authrizedinfo FROM login_authorized_modules WHERE (status is null or status='' or status='active') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "' order by moduleid");
	}
	$authorizemdlist = array();
	if ($auldq->num_rows > 0) {
		while ($aldrow = $auldq->fetch_assoc()) {
			$authorizemdlist[] = array('sno' => $aldrow['sno'], 'companyid' => $aldrow['companyid'], 'subcompanyid' => $aldrow['subcompanyid'], 'projectid' => $aldrow['projectid'], 'loginid' => $aldrow['loginid'], 'moduleid' => $aldrow['moduleid'], 'authrizedinfo' => $aldrow['authrizedinfo']);
		}
	}
	return $authorizemdlist;
}

function loginuserdetailsfrommasterold($conn, $loginroleid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$lguq = $conn->query("SELECT ifnull(departmentid,'') as departmentid,sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and isActive='yes'  and loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "loginid") {
		$lguq = $conn->query("SELECT  ifnull(departmentid,'') as departmentid ,sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and loginid='" . $shortbyvalue . "'");
	} else if ($shortby == "mobileno") {
		$lguq = $conn->query("SELECT  ifnull(departmentid,'') as departmentid ,sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and mobileno='" . $shortbyvalue . "'");
	} else if ($shortby == "sno") {
		$lguq = $conn->query("SELECT ifnull(departmentid,'') as departmentid,sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and sno='" . $shortbyvalue . "'");
	} else if ($shortby == "projectid") {

		$lguq = $conn->query("SELECT ifnull(departmentid,'') as departmentid,sno, loginname, loginid, loginpassword, loginpassword_n, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and projectid='" . $shortbyvalue . "' and isActive='yes' and (loginrolename ='Super Admin' or loginrolename='Admin')");
	} else if ($shortby == "checknamewithdepartment") {
		$result = explode("|", $shortbyvalue);
		$subqry = "";
		if ($result[4]) {
			$subqry = " and l.loginid <>'" . $result[4] . "'";
		}

		$lguq = $conn->query("SELECT ifnull(l.departmentid,'') as departmentid,l.sno, l.loginname, l.loginid, l.loginpassword, l.loginpassword_n, l.loginroleid, l.loginrolename, l.mobileno,l. emailid, l.isActive, l.isLogined, l.loginDatetime,l.comments FROM login_user_master l join login_authorized_projects a  on a.loginid=l.loginid WHERE  ifnull(l.tedit,'')=''  and l.loginname ='" . $result[0] . "' and l.departmentid='" . $result[1] . "' and a.companyid='" . $result[2] . "' and a.subcompanyid='" . $result[3] . "'" . $subqry);
	}
	$ullist = array();
	// echo $lguq->num_rows;
	if ($lguq->num_rows > 0) {
		while ($lurow = $lguq->fetch_assoc()) {
			$ullist[] = array('loginname' => $lurow['loginname'], 'loginid' => $lurow['loginid'], 'loginpassword' => $lurow['loginpassword'], 'loginroleid' => $lurow['loginroleid'], 'loginrolename' => $lurow['loginrolename'], 'mobileno' => ($lurow['mobileno'] ? $lurow['mobileno'] : "N/A"), 'emailid' => ($lurow['emailid'] ? $lurow['emailid'] : "N/A"), 'isactive' => ($lurow['isActive'] ? $lurow['isActive'] : "Active"), 'islogined' => ($lurow['isLogined'] ? ucwords($lurow['isLogined']) : "Logout"), 'logindatetime' => $lurow['loginDatetime'], 'departmentid' => $lurow['departmentid'], 'comments' => $lurow['comments']);
		}
	}
	return $ullist;
}

function loginuserdetailswithauthproject_old($conn, $companyid, $subcompanyid, $projectid, $loginroleid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join tbldepartmentmaster d on d.id=u.departmentid where u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and p.projectid='" . $projectid . "' and u.loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "allwithsubcompany") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join tbldepartmentmaster d on d.id=u.departmentid where u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and u.loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "allwithprojectid") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p on u.loginid=p.loginid left join tbldepartmentmaster d on d.id=u.departmentid where u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and p.projectid='" . $projectid . "'");
	} else if ($shortby == "projectwithdepartment") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join tbldepartmentmaster d on d.id=u.departmentid  where u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and u.loginroleid>'" . $loginroleid . "'");
	} else if ($shortby == "loginusername") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join tbldepartmentmaster d on d.id=u.departmentid  where u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and lcase(u.loginname)='" . strtolower(trim($shortbyvalue)) . "'");
	} else if ($shortby == "adminloginlist") {
		$lguq = $conn->query("SELECT ifnull(d.departmentname,'') as departmentname, u.loginname,u.loginid,u.loginroleid,u.loginrolename,u.mobileno,u.emailid,u.isactive,u.islogined,u.logindatetime,u.isActive,u.isLogined,u.loginDatetime,p.companyid,p.subcompanyid,p.projectid FROM login_user_master u join login_authorized_projects p  on u.loginid=p.loginid left join tbldepartmentmaster d on d.id=u.departmentid  where u.isActive='yes' and p.companyid='" . $companyid . "' and p.subcompanyid='" . $subcompanyid . "' and u.loginroleid<4 ");
	}
	$ullist = array();
	if ($lguq->num_rows > 0) {
		while ($lurow = $lguq->fetch_assoc()) {
			$ullist[] = array('departmentname' => $lurow['departmentname'], 'loginname' => $lurow['loginname'], 'loginid' => $lurow['loginid'], 'loginroleid' => $lurow['loginroleid'], 'loginrolename' => $lurow['loginrolename'], 'mobileno' => ($lurow['mobileno'] ? $lurow['mobileno'] : "N/A"), 'emailid' => ($lurow['emailid'] ? $lurow['emailid'] : ""), 'isactive' => ($lurow['isActive'] ? $lurow['isActive'] : "Active"), 'islogined' => ($lurow['isLogined'] ? ucwords($lurow['isLogined']) : "Logout"), 'logindatetime' => $lurow['loginDatetime'], 'companyid' => $lurow['companyid'], 'subcompanyid' => $lurow['subcompanyid'], 'projectid' => $lurow['projectid']);
		}
	}
	return $ullist;
}

function isLogin_old($conn, $loginid, $loginpassword)
{
	$clguq = $conn->query("SELECT companyid, projectid,subcompanyid, loginname, loginid, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and loginid='" . $loginid . "' and loginpassword='" . trim(addslashes($loginpassword)) . "'");
	$lginfo = array();
	if ($clguq->num_rows > 0) {
		while ($lgrow = $clguq->fetch_assoc()) {
			$lginfo[] = array('companyid' => $lgrow['companyid'], 'subcompanyid' => $lgrow['subcompanyid'], 'projectid' => $lgrow['projectid'], 'loginname' => $lgrow['loginname'], 'loginid' => $lgrow['loginid'], 'loginroleid' => $lgrow['loginroleid'], 'loginrolename' => $lgrow['loginrolename'], 'mobileno' => $lgrow['mobileno'], 'comments' => $lgrow['comments']);
		}
	}
	return $lginfo;
}


function login_rolefrommaster_old($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "allactive") {
		$lmd = "SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE status='active'";
	} else if ($shortby == "roleid") {
		$lmd = "SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE roleid='" . $shortbyvalue . "'";
	} else if ($shortby == "afterloginroleid") {
		$lmd = "SELECT roleid, rolename, rolename_display, rolelevel FROM login_role_master WHERE  status='active' and roleid>'" . $shortbyvalue . "'";
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

function voiceinstructiondetails($authconn, $conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ssq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, filepath3, requestno, payto, paytoid, requestamount, purposecode, purpose, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, description, requeststatus, requestedby, requestdate, requesttype, instructiontype, instructedby, transactiondate, transactionno, transferreceiptpath FROM tbl_paymentrequest_voiceinstruction WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') order by instructiondate desc, sno desc");
	} else if ($shortby == "allunapprove") {
		$ssq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, filepath3, requestno, payto, paytoid, requestamount, purposecode, purpose, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, description, requeststatus, requestedby, requestdate, requesttype, instructiontype, instructedby, transactiondate, transactionno, transferreceiptpath FROM tbl_paymentrequest_voiceinstruction WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and (requeststatus is null or requeststatus='' or requeststatus='pending')  order by instructiondate desc, sno desc");
	} else if ($shortby == "allunapprovewithAccountIDAndempty") {
		$ssq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, filepath3, requestno, payto, paytoid, requestamount, purposecode, purpose, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, description, requeststatus, requestedby, requestdate, requesttype, instructiontype, instructedby, transactiondate, transactionno, transferreceiptpath FROM tbl_paymentrequest_voiceinstruction WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (from_accountid = '" . $shortbyvalue . "' OR from_accountid = '' OR from_accountid IS NULL) and (tedit is null or tedit='') and (requeststatus is null or requeststatus='' or requeststatus='pending')  order by instructiondate desc, sno desc");
	} else if ($shortby == "allunapprovewithDateRange") {
		$datess = explode("|", $shortbyvalue);
		$dateFrom = $datess[0];
		$dateTo = $datess[1];
		$ssq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, filepath3, requestno, payto, paytoid, requestamount, purposecode, purpose, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, description, requeststatus, requestedby, requestdate, requesttype, instructiontype, instructedby, transactiondate, transactionno, transferreceiptpath FROM tbl_paymentrequest_voiceinstruction WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and date(instructiondate)>='" . $dateFrom . "' and date(instructiondate)<='" . $dateTo . "' and (tedit is null or tedit='') and (requeststatus is null or requeststatus='' or requeststatus='pending')  order by instructiondate desc, sno desc");
	} else if ($shortby == "allapprove") {
		$ssq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, filepath3, requestno, payto, paytoid, requestamount, purposecode, purpose, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, description, requeststatus, requestedby, requestdate, requesttype, instructiontype, instructedby, transactiondate, transactionno, transferreceiptpath FROM tbl_paymentrequest_voiceinstruction WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and (requeststatus='done' or requeststatus='approved') order by instructiondate desc, sno desc");
	} else if ($shortby == "instructionid") {
		$ssq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, filepath3, requestno, payto, paytoid, requestamount, purposecode, purpose, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, description, requeststatus, requestedby, requestdate, requesttype, instructiontype, instructedby, transactiondate, transactionno, transferreceiptpath FROM tbl_paymentrequest_voiceinstruction WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and instructionid='" . $shortbyvalue . "'");
	} else if ($shortby == "sno") {
		$ssq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, filepath3, requestno, payto, paytoid, requestamount, purposecode, purpose, from_bankname, from_accountid, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, description, requeststatus, requestedby, requestdate, requesttype, instructiontype, instructedby, transactiondate, transactionno, transferreceiptpath FROM tbl_paymentrequest_voiceinstruction WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (tedit is null or tedit='') and sno='" . $shortbyvalue . "'");
	}
	$voiceinst = array();
	if ($ssq->num_rows > 0) {
		while ($vrow = $ssq->fetch_assoc()) {
			$llg = loginuserdetailsfrommasterold($authconn, "", "loginid", $vrow['instructedby']);
			$instructedbyname = "";
			if (count($llg) > 0) {
				foreach ($llg as $ddkey) {
					$instructedbyname = $ddkey['loginname'];
				}
			}

			$ppt = getpaytoinfo($conn, $companyid, $subcompanyid, "paytoid", $vrow['paytoid']);
			$paytoname = "";
			$paytoid = "";
			if (count($ppt) > 0) {
				foreach ($ppt as $pptkey) {
					$paytoname = $pptkey['payto'];
					$paytoid = $pptkey['paytoid'];
				}
			} else {
				$paytoname = $vrow['payto'];
			}

			$ppj = getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $vrow['purposecode']);
			// print_r($ppj);
			$purposename = "";
			$purposecode = "";
			if (count($ppj) > 0) {
				foreach ($ppj as $ppjkey) {
					$purposename = $ppjkey['purpose'];
					$purposecode = $ppjkey['purposecode'];
				}
			} else {
				$purposename = $vrow['purpose'];
			}

			// accountinfo
			$acct = getaccountinformation($conn, $companyid, $subcompanyid, $projectid, 'accountid', $vrow['from_accountid']);
			$accountid = "";
			$accountname = "";
			$accountno = "";
			if (count($acct) > 0) {
				foreach ($acct as $acctkey) {
					$accountid = $acctkey['accountid'];
					$accountname = $acctkey['accountname'];
					$accountno = $acctkey['accountno'];
					$ifsccode = $acctkey['ifsccode'];
				}
			} else {
				$accountname = $vrow['from_accountid'];
			}
			$transactiondatedsp = "";
			if ($vrow['transactiondate']) {
				$transactiondatedsp = date('d-M-Y', strtotime($vrow['transactiondate']));
			}
			$voiceinst[] = array('sno' => $vrow['sno'], 'companyid' => $vrow['companyid'], 'subcompanyid' => $vrow['subcompanyid'], 'projectid' => $vrow['projectid'], 'subprojectid' => $vrow['subprojectid'], 'instructionid' => $vrow['instructionid'], 'instructiondate' => $vrow['instructiondate'], 'instructiondatedsp' => date('d-M-Y', strtotime($vrow['instructiondate'])), 'voicefilepath' => $vrow['filepath'], 'filepath' => $vrow['filepath'], 'filepath2' => $vrow['filepath2'], 'filepath3' => $vrow['filepath3'], 'requestno' => $vrow['requestno'], 'payto' => $paytoname, 'paytoid' => $paytoid, 'requestamount' => $vrow['requestamount'] ?? '0', 'transactiondate' => $vrow['transactiondate'], 'transactiondatedsp' => $transactiondatedsp, 'transactionno' => $vrow['transactionno'] ?? '', 'transferreceiptpath' => $vrow['transferreceiptpath'] ?? '', 'purpose' => $purposename ?? '', 'purposecode' => $purposecode ?? '', 'from_bankname' => $accountname, 'from_accountid' => $accountid, 'from_accountno' => $accountno, 'payto_bankname' => $vrow['payto_bankname'], 'payto_accountno' => $vrow['payto_accountno'], 'payto_bankifsccode' => $vrow['payto_bankifsccode'], 'description' => $vrow['description'] ?? '', 'requeststatus' => $vrow['requeststatus'], 'requestedby' => $vrow['requestedby'], 'requestdate' => $vrow['requestdate'], 'requesttype' => $vrow['requesttype'], 'instructiontype' => $vrow['instructiontype'], 'instructedby' => $vrow['instructedby'], 'instructedbyname' => $instructedbyname);
		}
	}
	return $voiceinst;
}

function standingordermasterlist($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allactive") {
		$stdq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, stdorderno, invoicetoid, invoiceto, toaddress, tophone, toemail, hsncode, rate, qty, cgstpercent, cgstamount, sgstpercent, sgstamount, igstpercent, igstamount, distpercent, distamount, invoiceamount, purpose, purposecode, invoiceterm, termdays, invoicedate, nextinvoicedate, foraccountnumber,remark FROM tbl_standingorder_master WHERE (isactive is null or isactive='' or isactive='yes') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'");
	} else if ($shortby == "allactiveformonth") {
		$stdq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, stdorderno, invoicetoid, invoiceto, toaddress, tophone, toemail, hsncode, rate, qty, cgstpercent, cgstamount, sgstpercent, sgstamount, igstpercent, igstamount, distpercent, distamount, invoiceamount, purpose, purposecode, invoiceterm, termdays, invoicedate, nextinvoicedate, foraccountnumber,remark FROM tbl_standingorder_master WHERE (isactive is null or isactive='' or isactive='yes') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and DATE(nextinvoicedate)='" . date('Y-m-d', strtotime($shortbyvalue)) . "'");
	} else if ($shortby == "standingorderno") {
		$stdq = $conn->query("SELECT sno, companyid, subcompanyid, projectid, stdorderno, invoicetoid, invoiceto, toaddress, tophone, toemail, hsncode, rate, qty, cgstpercent, cgstamount, sgstpercent, sgstamount, igstpercent, igstamount, distpercent, distamount, invoiceamount, purpose, purposecode, invoiceterm, termdays, invoicedate, nextinvoicedate, foraccountnumber,remark FROM tbl_standingorder_master WHERE (isactive is null or isactive='' or isactive='yes') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and stdorderno='" . $shortbyvalue . "'");
	}
	$storderlist = array();
	if ($stdq->num_rows > 0) {
		while ($sorow = $stdq->fetch_assoc()) {
			$storderlist[] = array('sno' => $sorow['sno'], 'companyid' => $sorow['companyid'], 'subcompanyid' => $sorow['subcompanyid'], 'projectid' => $sorow['projectid'], 'stdorderno' => $sorow['stdorderno'], 'invoicetoid' => $sorow['invoicetoid'], 'invoiceto' => $sorow['invoiceto'], 'toaddress' => $sorow['toaddress'], 'tophone' => $sorow['tophone'], 'toemail' => $sorow['toemail'], 'hsncode' => $sorow['hsncode'], 'rate' => $sorow['rate'], 'qty' => $sorow['qty'], 'cgstpercent' => $sorow['cgstpercent'], 'cgstamount' => $sorow['cgstamount'], 'sgstpercent' => $sorow['sgstpercent'], 'sgstamount' => $sorow['sgstamount'], 'igstpercent' => $sorow['igstpercent'], 'igstamount' => $sorow['igstamount'], 'distpercent' => $sorow['distpercent'], 'distamount' => $sorow['distamount'], 'invoiceamount' => $sorow['invoiceamount'], 'purpose' => $sorow['purpose'], 'purposecode' => $sorow['purposecode'], 'invoiceterm' => $sorow['invoiceterm'], 'termdays' => $sorow['termdays'], 'invoicedate' => $sorow['invoicedate'], 'nextinvoicedate' => $sorow['nextinvoicedate'], 'foraccountnumber' => $sorow['foraccountnumber'], 'remark' => $sorow['remark']);
		}
	}
	return $storderlist;
}



function standingordermasterlist_new($conn, $companyid, $subcompanyid, $projectid, $sortby, $sortbyvalue)
{
	if ($sortby == "allactive") {
		//echo "SELECT c.client_name,s.sno, s.stdorderno, s.invoicetoid, s.invoiceto,s.invoiceamount, s.purpose, s.purposecode,   s.termdays, date_format(s.invoicedate,'%d-%m-%Y') as standing_date, s.description,s.remark FROM tbl_standingorder_master s join client_master c on c.client_id=s.invoicetoid WHERE (ifnull(s.isactive ,'yes') or s.isactive='') and s.companyid='".$companyid."' and s.subcompanyid='".$subcompanyid."' and s.projectid='".$projectid."'";
		//exit;
		$stdq = $conn->query("SELECT c.client_name,s.sno, s.stdorderno, s.invoicetoid, s.invoiceto,s.invoiceamount, s.purpose, s.purposecode,   s.termdays, date_format(s.invoicedate,'%d-%m-%Y') as standing_date, s.description,s.remark FROM tbl_standingorder_master s join client_master c on c.client_id=s.invoicetoid WHERE (ifnull(s.isactive ,'yes')='yes' or s.isactive='') and s.companyid='" . $companyid . "' and s.subcompanyid='" . $subcompanyid . "' and s.projectid='" . $projectid . "'");
	} else {
		$storderlist[] = array('sno' => "Error in query conditions");
		return $storderlist;
	}
	$storderlist = array();
	if ($stdq->num_rows > 0) {
		while ($sorow = $stdq->fetch_assoc()) {
			$storderlist[] = array('sno' => $sorow['sno'], 'client_name' => $sorow['client_name'], 'stdorderno' => $sorow['stdorderno'], 'invoicetoid' => $sorow['invoicetoid'], 'invoiceamount' => $sorow['invoiceamount'], 'purpose' => $sorow['purpose'], 'purposecode' => $sorow['purposecode'], 'termdays' => $sorow['termdays'], 'standing_date' => $sorow['standing_date'], 'description' => $sorow['description'], 'remark' => $sorow['remark']);
		}
	}
	return $storderlist;
}
function get_maintransactions_creditside_details($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "peding_utrno_update_withprojectid") {
		$ckq = "SELECT sno, countryid, for_companyid, for_subcompanyid, forprojectid, reference_no, transactionno, bookdate, transactiondate, transactiontype, book_amount, transfered_amount, beforeedit_amount, payfromname, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, paytoid, paytoname, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, paymenttype, categoryid, subcategoryid, purposecode, purpose, description, documentpath, audiofilepath, voiceinstid, reconciliation, reconciliation_banksno, value_date, reconcilitionX, reverse_entry, is_intermediate_transfer, is_merger, chalan_refno, groupkeyid, book_source, referenceno_clb, book_status, final_status, audit_status, bookdoneby FROM tbl_maintransaction_creditside WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "sno") {
		$ckq = "SELECT sno, countryid, for_companyid, for_subcompanyid, forprojectid, reference_no, transactionno, bookdate, transactiondate, transactiontype, book_amount, transfered_amount, beforeedit_amount, payfromname, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, paytoid, paytoname, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, paymenttype, categoryid, subcategoryid, purposecode, purpose, description, documentpath, audiofilepath, voiceinstid, reconciliation, reconciliation_banksno, value_date, reconcilitionX, reverse_entry, is_intermediate_transfer, is_merger, chalan_refno, groupkeyid, book_source, referenceno_clb, book_status, final_status, audit_status, bookdoneby FROM tbl_maintransaction_creditside WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$ckqr = $conn->query($ckq) or die($conn->error);
	$bnkstslist = array();
	if ($ckqr->num_rows > 0) {
		while ($cmtrow = $ckqr->fetch_assoc()) {
			$bnkstslist[] = array('sno' => $cmtrow['sno'], 'countryid' => $cmtrow['countryid'], 'for_companyid' => $cmtrow['for_companyid'], 'for_subcompanyid' => $cmtrow['for_subcompanyid'], 'forprojectid' => $cmtrow['forprojectid'], 'reference_no' => $cmtrow['reference_no'], 'transactionno' => $cmtrow['transactionno'], 'bookdate' => $cmtrow['bookdate'], 'transactiondate' => $cmtrow['transactiondate'], 'transactiontype' => $cmtrow['transactiontype'], 'book_amount' => $cmtrow['book_amount'], 'transfered_amount' => $cmtrow['transfered_amount'], 'beforeedit_amount' => $cmtrow['beforeedit_amount'], 'payfromname' => $cmtrow['payfromname'], 'payfrombankname' => $cmtrow['payfrombankname'], 'payfromaccountno' => $cmtrow['payfromaccountno'], 'payfromifsccode' => $cmtrow['payfromifsccode'], 'payfromaccountid' => $cmtrow['payfromaccountid'], 'paytoid' => $cmtrow['paytoid'], 'paytoname' => $cmtrow['paytoname'], 'paytoaccountno' => $cmtrow['paytoaccountno'], 'paytobankname' => $cmtrow['paytobankname'], 'paytoifsc' => $cmtrow['paytoifsc'], 'paytoaccountid' => $cmtrow['paytoaccountid'], 'paymenttype' => $cmtrow['paymenttype'], 'categoryid' => $cmtrow['categoryid'], 'subcategoryid' => $cmtrow['subcategoryid'], 'purposecode' => $cmtrow['purposecode'], 'purpose' => $cmtrow['purpose'], 'description' => $cmtrow['description'], 'documentpath' => $cmtrow['documentpath'], 'audiofilepath' => $cmtrow['audiofilepath'], 'voiceinstid' => $cmtrow['voiceinstid'], 'reconciliation' => $cmtrow['reconciliation'], 'reconciliation_banksno' => $cmtrow['reconciliation_banksno'], 'value_date' => $cmtrow['value_date'], 'reconcilitionX' => $cmtrow['reconcilitionX'], 'reverse_entry' => $cmtrow['reverse_entry'], 'is_intermediate_transfer' => $cmtrow['is_intermediate_transfer'], 'is_merger' => $cmtrow['is_merger'], 'chalan_refno' => $cmtrow['chalan_refno'], 'groupkeyid' => $cmtrow['groupkeyid'], 'book_source' => $cmtrow['book_source'], 'referenceno_clb' => $cmtrow['referenceno_clb'], 'book_status' => $cmtrow['book_status'], 'final_status' => $cmtrow['final_status'], 'audit_status' => $cmtrow['audit_status'], 'bookdoneby' => $cmtrow['bookdoneby']);
		}
	}
	return $bnkstslist;
}


function invoice_purchase_tempdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ssqv = $conn->query("SELECT sno, companyid, subcompanyid, projectid, invoiceid, invoicedate, invoiceno, description, hsncode, mrp, rate, qty, taxableamount, cgstpercent, cgstamount, sgstpercent, sgstamount, igstpercent, igstamount, distpercent, distamount, payableamount, ttlamount, status, date_time FROM tbl_invoicespurchase_temp WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'");
	}
	$invpt = array();
	if ($ssqv->num_rows > 0) {
		while ($invtm = $ssqv->fetch_assoc()) {
			$invpt[] = array('sno' => $invtm['sno'], 'companyid' => $invtm['companyid'], 'subcompanyid' => $invtm['subcompanyid'], 'projectid' => $invtm['projectid'], 'invoiceid' => $invtm['invoiceid'], 'invoicedate' => $invtm['invoicedate'], 'invoiceno' => $invtm['invoiceno'], 'description' => $invtm['description'], 'hsncode' => $invtm['hsncode'], 'mrp' => $invtm['mrp'], 'rate' => $invtm['rate'], 'qty' => $invtm['qty'], 'taxableamount' => $invtm['taxableamount'], 'cgstpercent' => $invtm['cgstpercent'], 'cgstamount' => $invtm['cgstamount'], 'sgstpercent' => $invtm['sgstpercent'], 'sgstamount' => $invtm['sgstamount'], 'igstpercent' => $invtm['igstpercent'], 'igstamount' => $invtm['igstamount'], 'distpercent' => $invtm['distpercent'], 'distamount' => $invtm['distamount'], 'payableamount' => $invtm['payableamount'], 'ttlamount' => $invtm['ttlamount'], 'status' => $invtm['status'], 'date_time' => $invtm['date_time']);
		}
	}
	return $invpt;
}

function creditcardaccountdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allentrywith_accountno") {
		$crdsq = "SELECT sno, idate, countryid, companyid, subcompanyid, projectid, subprojectid, from_accountid, from_accountno, userid, username, request_no, reference_no, creditamount, debitamount, balanceamount, adjustedamount, purposecategoryid, purposecategory, purposesubcategoryid, purposesubcategory, purposecode, purpose, beneficiaryname, invoicetype, invoiceno, invoicedate, documentpath, audiofilepath, voiceinstid, description, special_remark, adjustment_status, blobfile, blobfile_tblname, websno, mergewith FROM creditcard_account WHERE (tedit is null or tedit='')  and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and from_accountno='" . $shortbyvalue . "'";
	}
	$crdsqr = $conn->query($crdsq);
	$creditdetails = array();
	if ($crdsqr->num_rows > 0) {
		while ($crsrow = $crdsqr->fetch_assoc()) {
			$creditdetails[] = array('sno' => $crsrow['sno'], 'idate' => $crsrow['idate'], 'countryid' => $crsrow['countryid'], 'companyid' => $crsrow['companyid'], 'subcompanyid' => $crsrow['subcompanyid'], 'projectid' => $crsrow['projectid'], 'subprojectid' => $crsrow['subprojectid'], 'from_accountid' => $crsrow['from_accountid'], 'from_accountno' => $crsrow['from_accountno'], 'userid' => $crsrow['userid'], 'username' => $crsrow['username'], 'request_no' => $crsrow['request_no'], 'reference_no' => $crsrow['reference_no'], 'creditamount' => $crsrow['creditamount'], 'debitamount' => $crsrow['debitamount'], 'balanceamount' => $crsrow['balanceamount'], 'adjustedamount' => $crsrow['adjustedamount'], 'purposecategoryid' => $crsrow['purposecategoryid'], 'purposecategory' => $crsrow['purposecategory'], 'purposesubcategoryid' => $crsrow['purposesubcategoryid'], 'purposesubcategory' => $crsrow['purposesubcategory'], 'purposecode' => $crsrow['purposecode'], 'purpose' => $crsrow['purpose'], 'beneficiaryname' => $crsrow['beneficiaryname'], 'invoicetype' => $crsrow['invoicetype'], 'invoiceno' => $crsrow['invoiceno'], 'invoicedate' => $crsrow['invoicedate'], 'documentpath' => $crsrow['documentpath'], 'audiofilepath' => $crsrow['audiofilepath'], 'voiceinstid' => $crsrow['voiceinstid'], 'description' => $crsrow['description'], 'special_remark' => $crsrow['special_remark'], 'adjustment_status' => $crsrow['adjustment_status'], 'blobfile' => $crsrow['blobfile'], 'blobfile_tblname' => $crsrow['blobfile_tblname'], 'websno' => $crsrow['websno'], 'mergewith' => $crsrow['mergewith']);
		}
	}
	return $creditdetails;
}

function creditcardmaster($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithprojectid") {
		$crdq = "SELECT sno, bankname, accountno, ifsccode, accountid, ccid, cardtype, cardholdername, cardnumber, expirymonth, expiryyear, cvv, billing_address, validfrom, validto, cardlimit, remark, edatetime, loginby FROM creditcard_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "creditcardid" || $shortby == "ccid") {
		$crdq = "SELECT sno, bankname, accountno, ifsccode, accountid, ccid, cardtype, cardholdername, cardnumber, expirymonth, expiryyear, cvv, billing_address, validfrom, validto, cardlimit, remark, edatetime, loginby FROM creditcard_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and ccid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$crdq = "SELECT sno, bankname, accountno, ifsccode, accountid, ccid, cardtype, cardholdername, cardnumber, expirymonth, expiryyear, cvv, billing_address, validfrom, validto, cardlimit, remark, edatetime, loginby FROM creditcard_master WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$crdqr = $conn->query($crdq);
	$creditcardlist = array();
	if ($crdqr->num_rows > 0) {
		while ($crmrow = $crdqr->fetch_assoc()) {
			$creditcardlist[] = array('sno' => $crmrow['sno'], 'bankname' => $crmrow['bankname'], 'accountno' => $crmrow['accountno'], 'ifsccode' => $crmrow['ifsccode'], 'accountid' => $crmrow['accountid'], 'ccid' => $crmrow['ccid'], 'cardtype' => $crmrow['cardtype'], 'cardholdername' => $crmrow['cardholdername'], 'cardnumber' => $crmrow['cardnumber'], 'expirymonth' => $crmrow['expirymonth'], 'expiryyear' => $crmrow['expiryyear'], 'cvv' => $crmrow['cvv'], 'billing_address' => $crmrow['billing_address'], 'validfrom' => $crmrow['validfrom'], 'validto' => $crmrow['validto'], 'cardlimit' => $crmrow['cardlimit'], 'remark' => $crmrow['remark'], 'edatetime' => $crmrow['edatetime'], 'loginby' => $crmrow['loginby']);
		}
	}
	return $creditcardlist;
}

function authorizedcreditcardperson($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$atcrq = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, loginid, ccid, userid, username, mobileno, emailid, address FROM creditcard_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "userid") {
		$atcrq = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, loginid, ccid, userid, username, mobileno, emailid, address FROM creditcard_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and userid='" . $shortbyvalue . "'";
	} else if ($shortby == "ccid") {
		$atcrq = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, loginid, ccid, userid, username, mobileno, emailid, address FROM creditcard_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and ccid='" . $shortbyvalue . "'";
	} else if ($shortby == "loginid") {
		$atcrq = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, loginid, ccid, userid, username, mobileno, emailid, address FROM creditcard_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and loginid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$atcrq = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, loginid, ccid, userid, username, mobileno, emailid, address FROM creditcard_authorizedperson WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$atcrqr = $conn->query($atcrq);
	$crpersonlist = array();
	if ($atcrqr->num_rows > 0) {
		while ($crprow = $atcrqr->fetch_assoc()) {
			$crpersonlist[] = array('sno' => $crprow['sno'], 'companyid' => $crprow['companyid'], 'subcompanyid' => $crprow['subcompanyid'], 'projectid' => $crprow['projectid'], 'subprojectid' => $crprow['subprojectid'], 'loginid' => $crprow['loginid'], 'ccid' => $crprow['ccid'], 'userid' => $crprow['userid'], 'username' => $crprow['username'], 'mobileno' => $crprow['mobileno'], 'emailid' => $crprow['emailid'], 'address' => $crprow['address']);
		}
	}
	return $crpersonlist;
}

function creditcardauthorizedproject($conn, $companyid, $subcompanyid, $projectid, $paytoid, $shortby, $shortbyvalue)
{
	if ($shortby == "allwithprojectid") {
		$crppq = "SELECT sno, paytoid, companyid, subcompanyid, projectid, subprojectid, cardnumber, remark, loginby, edatetime FROM creditcard_authorizedproject  WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "paytoid") {
		$crppq = "SELECT sno, paytoid, companyid, subcompanyid, projectid, subprojectid, cardnumber, remark, loginby, edatetime FROM creditcard_authorizedproject  WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and paytoid='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$crppq = "SELECT sno, paytoid, companyid, subcompanyid, projectid, subprojectid, cardnumber, remark, loginby, edatetime FROM creditcard_authorizedproject  WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$crppqr = $conn->query($crppq);
	$creditcardlist = array();
	if ($crppqr->num_rows > 0) {
		while ($crpprow = $crppqr->fetch_assoc()) {
			$creditcardlist[] = array('sno' => $crpprow['sno'], 'paytoid' => $crpprow['paytoid'], 'companyid' => $crpprow['companyid'], 'subcompanyid' => $crpprow['subcompanyid'], 'projectid' => $crpprow['projectid'], 'subprojectid' => $crpprow['subprojectid'], 'cardnumber' => $crpprow['cardnumber'], 'remark' => $crpprow['remark'], 'loginby' => $crpprow['loginby'], 'edatetime' => $crpprow['edatetime']);
		}
	}

	return $creditcardlist;
}

function directuploadfiledetails()
{
	if ($shortby == "allwithprojectid") {
		$ppqw = "SELECT sno, companyid, subcompanyid, projectid, filpath, category FROM tbl_directuploads WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "sno") {
		$ppqw = "SELECT sno, companyid, subcompanyid, projectid, filpath, category FROM tbl_directuploads WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$ppqwr = $conn->query($ppqw);
	$drlist = array();
	if ($ppqwr->num_rows > 0) {
		while ($drow = $ppqwr->fetch_assoc()) {
			$drlist[] = array('sno' => $drow['sno'], 'paytoid' => $drow['paytoid'], 'companyid' => $drow['companyid'], 'subcompanyid' => $drow['subcompanyid'], 'projectid' => $drow['projectid'], 'filpath' => $drow['filpath'], 'category' => $drow['category']);
		}
	}
}

function sendemail_forReconstructBankEntry($conn, $companyid, $subcompanyid, $projectid, $sendtoemail, $routurl, $key)
{
	// $to =array($sendtoemail);
	$to = array("chatursingh011@gmail.com");
	$subject = "Book Reconstruction of Bank Statement Transaction ";

	$url_vab = "$routurl" . "reconcilation_reconstruct_book/reconsutructed_bankdataByClient.php?k=" . $key;
	$message = "<html><body>
        <p>Hi,</p>
        <p>Please click the following link to view the Book Reconstruction. If the link does not work, copy the URL below and paste it into your browser's address bar.</p>
        <br>
        <p><a href='" . $url_vab . "' target='_blank'>Click here</a></p>
        <br>
        Copy the address:
        <br>
        " . $url_vab . "
        <br>
        <p>Thanks & Regards,</p>
        <p>Team Amanmovement</p>
        </body>
        </html>";
	// $result=Sendemailnew($to,array(),array(),$subject,$message);
	$sendtocc = array("chatursingh011@gmail.com", "rohitsafaya@gmail.com");
	$result = sendemailfrommailer($to, $sendtocc, array(), $subject, $message);
	$msg = "";
	if (!$result) {
		$msg = "Error";

	} else {
		$msg = "Success";
	}

	return $msg;
}

function sendemail_forunknownentry($conn, $companyid, $subcompanyid, $projectid, $sendtoemail, $routurl, $key)
{
	// $projdtls=projectdetails($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	// $getemailid =$projdtls[0]["projectemail"];
	//$to = array("anantkumar.rajput@gmail.com");
	$to = array($sendtoemail);
	$subject = "Book Reconstruction of Bank Statement Transaction ";

	$url_vab = "$routurl" . "webpages/unkonwreconstructionbook.php?k=" . $key;
	$message = "<html><body>
        <p>Dear Admin</p>
        <p>Please click on following Link For Book Reconstruction. If the link is not working, copy the given text below the link and paste it in the browser address bar.</p>
        <br>
        <p><a href='" . $url_vab . "' target='_blank'>Click here</a></p>
        <br>
        Copy the address:
        <br>
        " . $url_vab . "
        <br>
        <p>Thanks & Regards,</p>
        <p>Team Amanmovement</p>
        </body>
        </html>";
	// $result=Sendemailnew($to,array(),array(),$subject,$message);
	$sendtocc = array("chatursingh011@gmail.com", "rohitsafaya@gmail.com");
	$result = sendemailfrommailer($to, $sendtocc, array(), $subject, $message);
	$msg = "";
	if (!$result) {
		$msg = "Error";

	} else {
		$msg = "Success";
	}

	return $msg;
}

function sendgroupbyemaildeposits($mconn, $accountid, $groupinfo)
{
	$to = array("anantkumar.rajput@gmail.com");
	$subject = "Book Reconstruction of Selected Deposits Bank Statement Transaction ";
	// $url_vab = "https://amanaccounting.in/reconcilation/depositsidereconstructionbank_form.php?group=".addslashes(trim($groupinfo))."&accountid=".addslashes(trim($accountid))."";
	$url_vab = "https://amanaccounting.in/reconcilation/createinvoicefrombank_form.php?group=" . addslashes(trim($groupinfo)) . "&accountid=" . addslashes(trim($accountid)) . "";
	$message = "<html><body>
        <p>Dear Admin</p>
        <p>Please Click on following Link For Create Invoice of Deposits.</p>
        
        <br>
        <p><a href='" . $url_vab . "' target='_blank'>Click here</a></p>
        <br>
        <br>
        <p>Thanks & Regards,</p>
        <p>Team Amanmovement</p>
        </body>
        </html>";
	$result = Sendemailnew($to, array(), array(), $subject, $message);
	//$result=mail($to,$subject,$message,$headers);
	$msg = "";
	if (!$result) {
		$msg = "Error";

	} else {
		$msg = "Success";
	}

	return $msg;
}

function SendEmailToReminderforApprovalRequest($conn, $requestno, $sendtoemail, $emailcc, $emailsubject, $emailmessage, $emailSendername, $routurl, $key)
{
	$to = array("chatursingh011@gmail.com", $sendtoemail);
	// $to =array($sendtoemail);
	$subject = $emailsubject != "" ? $emailsubject : "Immediate Action Required: Request Approval";
	$outremark = "";
	if ($emailmessage) {
		$outremark = "<p><br><b>Remark: " . $emailmessage . "</p>";
	}

	$url_vab = "$routurl" . "request_approval/request_preview.php?requestno=" . base64_encode($requestno) . "&k=" . $key;
	$message = "<html><body>
        <p>Hi,</p>
        <p>This is a gentle reminder regarding the pending approval request [" . $requestno . "]. Your prompt attention to this matter would be greatly appreciated.</p>
        <p>Please review and approve it at your earliest convenience to avoid any delays.</p>
        <p>Let me know if you need any further information.</p>
        <br>
        " . $outremark . "
        <br>
        <p><a href='" . $url_vab . "' target='_blank'>Click here</a></p>
        <br>
        Copy the address:
        <br>
        " . $url_vab . "
        <br>
        <p>Thanks & Regards,</p>
        <p>" . $emailSendername . "</p>
        </body>
        </html>";
	// $result=Sendemailnew($to,array(),array(),$subject,$message);
	// $sendtocc = array("chatursingh011@gmail.com","rohitsafaya@gmail.com");
	$sendtocc = array($emailcc);
	$result = sendemailfrommailer($to, $sendtocc, array(), $subject, $message);
	$msg = "";
	if ($result) {
		$msg = "Success";
	} else {
		$msg = "Error";
	}

	return $msg;
}

function sendEmailAfterApprovalCompany($conn, $routurl, $loginid, $loginpassword, $loginname, $authorizedemail)
{
	if ($authorizedemail) {
		$to = array($authorizedemail);
		// $to =array($sendtoemail);
		$subject = "Company registration successfully";
		$weblink = $routurl . "loginCell/index.php";
		$message = "<html><body>
                <p>Hi, " . $loginname . "</p>
                <p>We are pleased to inform you that your application for company registration has been successfully approved.</p>
                <p><br/>Login URL: " . $weblink . "
                    <br/>Login ID: " . $loginid . "
                    <br/>Password: " . $loginpassword . "</p>
                <br>
                <p>Thanks & Regards,</p>
                <p>ASKT Team</p>
                </body>
                </html>";
		$sendtocc = array("chatursingh011@gmail.com", );
		$result = sendemailfrommailer($to, $sendtocc, array(), $subject, $message);
		$msg = "";
		if (!$result) {
			$msg = "Error";

		} else {
			$msg = "Success";
		}
	} else {
		$msg = "Error: Missing email address";
	}
	return $msg;
}


function sendemailrejectrequest($conn, $loginname, $requestno, $rejectcomment, $companyid, $subcompanyid, $projectid)
{
	$select_rjrqst = "SELECT * FROM tbl_paymentrequest WHERE request_no = ?";
	$stmt = $conn->prepare($select_rjrqst);
	$stmt->bind_param("s", $requestno);
	$stmt->execute();
	$query_result = $stmt->get_result();

	if ($query_result->num_rows === 0) {
		return 'No such request found.';
	}

	$row = $query_result->fetch_assoc();
	$requestername = $row['requested_by'];
	$sendtoemail = '';

	// Get requester email
	$sendtoinfo = loginuserdetailswithauthproject($conn, $companyid, $subcompanyid, $projectid, "", "loginusername", $requestername);
	if (count($sendtoinfo) > 0) {
		$sendtoemail = $sendtoinfo[0]['emailid'];
	}

	$rqst_detail = "
        <tr><td colspan='5'>
            <p><b>Payto Name:</b> {$row['payto']}</p>
            <p><b>Request Date:</b> " . date('d-M-Y', strtotime($row['request_date'])) . "</p>
            <p><b>Request No.:</b> {$row['request_no']}</p>
        </td></tr>";

	// Breakdown details
	$rqst_brkd = "
        SELECT * FROM tbl_paymentrequest_breakdown 
        WHERE request_no = ? 
          AND (tedit = '' OR tedit IS NULL OR tedit = '0') 
          AND companyid = ? AND subcompanyid = ? AND projectid = ?";
	$stmt2 = $conn->prepare($rqst_brkd);
	$stmt2->bind_param("siii", $requestno, $companyid, $subcompanyid, $projectid);
	$stmt2->execute();
	$rqst_brkd_result = $stmt2->get_result();

	$rqst_brk_detail = '';
	$sum_bnf_amount = 0;
	$sno = 1;

	while ($br = $rqst_brkd_result->fetch_assoc()) {
		$sum_bnf_amount += $br['ammount'];
		$rqst_brk_detail .= "
            <tr>
                <td>{$sno}</td>
                <td>{$br['beneficiery_name']}</td>
                <td>{$br['ammount']}</td>
                <td>{$br['description']}</td>
            </tr>";
		$sno++;
	}

	$rqst_brk_ft = "<tr><td colspan='2'><b>Requested Amount</b></td><td><b>{$sum_bnf_amount}</b></td><td></td></tr>";

	$m_msgbody = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 15px;
                color: #333;
            }
            .container {
                padding: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 14px;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 8px 12px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .note {
                margin-top: 20px;
                font-style: italic;
                color: #888;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <p>Dear <strong>' . htmlspecialchars($requestername) . '</strong>,</p>

            <p>We hope you are doing well.</p>

            <p>This is to inform you that your payment request (Request No: <strong>' . htmlspecialchars($requestno) . '</strong>) has been <strong style="color: #d9534f;">rejected</strong> for the following reason:</p>

            <blockquote style="background-color: #f9f9f9; padding: 10px 15px; border-left: 4px solid #d9534f;">
                ' . nl2br(htmlspecialchars($rejectcomment)) . '
            </blockquote>

            <p><strong>Request Details:</strong></p>

            <table>
                <tr>
                    <th colspan="2">Summary</th>
                </tr>
                <tr>
                    <td><strong>Payee Name</strong></td>
                    <td>' . htmlspecialchars($row['payto']) . '</td>
                </tr>
                <tr>
                    <td><strong>Request Date</strong></td>
                    <td>' . date('d-M-Y', strtotime($row['request_date'])) . '</td>
                </tr>
                <tr>
                    <td><strong>Request No.</strong></td>
                    <td>' . htmlspecialchars($row['request_no']) . '</td>
                </tr>
            </table>

            <br>

            <table>
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Beneficiary Name</th>
                        <th>Amount</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $rqst_brk_detail . '
                    ' . $rqst_brk_ft . '
                </tbody>
            </table>

            <p class="note">Note: If you have any questions regarding this rejection, please reach out to <strong>' . htmlspecialchars($loginname) . '</strong> for clarification or assistance.</p>

            <p>Thank you for your understanding.</p>

            <br>
            <p>Best regards,<br>
            <strong>' . htmlspecialchars($loginname) . '</strong><br>
            Accounts & Finance Team</p>
        </div>
    </body>
    </html>';

	// Recipient
	$sendto = [$sendtoemail ?: 'chatursingh011@gmail.com'];
	$sendcc = [];
	$sendbcc = [];
	$subject = 'Payment Request Rejected – Summary';

	// Optional email log
	// insertEmailLog($conn, $sendto, $sendcc, $sendbcc, $subject, $m_msgbody, '', 'Request Rejected');

	if (sendemailfrommailer($sendto, $sendcc, $sendbcc, $subject, $m_msgbody, [])) {
		return 'sent';
	} else {
		return 'notsend';
	}
}

function sendemailrejectImprestAdjestEntry($conn, $authconn, $companyid, $subcompanyid, $projectid, $rejectcomment, $requestdbsno, $loginid)
{
	$select_rjrqst = "SELECT  payto,total_amount,purpose,description,loginid FROM tbl_expencesinfoofotheraccount WHERE sno = ?";
	$stmt = $conn->prepare($select_rjrqst);
	$stmt->bind_param("s", $requestdbsno);
	$stmt->execute();
	$query_result = $stmt->get_result();
	if ($query_result->num_rows === 0) {
		return 'No such request found.';
	}

	$row = $query_result->fetch_assoc();
	$payto = $row['payto'];
	$total_amount = $row['total_amount'];
	$purpose = $row['purpose'];
	$description = $row['description'];
	$loginid = $row['loginid'];
	$sendtoemail = '';

	// Get requester email
	$clguq = $authconn->query("SELECT loginname, loginid, loginroleid, loginrolename, mobileno, emailid FROM login_user_master WHERE (tedit is null or tedit='') and (isActive='yes' or isActive='active') and loginid='" . $row['loginid'] . "'");
	$requestername = "";
	if (count($clguq) > 0) {
		$requestername = $clguq[0]['loginname'];
		$sendtoemail = $clguq[0]['emailid'];
	}

	$rqst_detail = "
        <tr><td colspan='5'>
            <p><b>Payto Name:</b> {$payto}</p>
            <p><b>Request Amount:</b> " . $total_amount . "</p>
            <p><b>Request No.:</b> {$purpose}</p>
            <p><b>Description.:</b> {$description}</p>
        </td></tr>";

	$rqst_brk_ft = "<tr><td colspan='2'><b>Requested Amount</b></td><td><b>{$sum_bnf_amount}</b></td><td></td></tr>";

	$m_msgbody = '
    <html>
    <head>
        <style>
            body {
                font-family: Arial, sans-serif;
                font-size: 15px;
                color: #333;
            }
            .container {
                padding: 20px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                font-size: 14px;
            }
            th, td {
                border: 1px solid #ccc;
                padding: 8px 12px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            .note {
                margin-top: 20px;
                font-style: italic;
                color: #888;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <p>Dear <strong>' . htmlspecialchars($requestername) . '</strong>,</p>

            <p>We hope you are doing well.</p>

            <p>This is to inform you that your payment adjustment request  has been <strong style="color: #d9534f;">rejected</strong> for the following reason:</p>

            <blockquote style="background-color: #f9f9f9; padding: 10px 15px; border-left: 4px solid #d9534f;">
                ' . nl2br(htmlspecialchars($rejectcomment)) . '
            </blockquote>

            <p><strong>Request Details:</strong></p>

            <table>
                ' . htmlspecialchars($rqst_detail) . '
            </table>
            <br>
            <p class="note">Note: If you have any questions regarding this rejection, please reach out to <strong>' . htmlspecialchars($loginname) . '</strong> for clarification or assistance.</p>

            <p>Thank you for your understanding.</p>

            <br>
            <p>Best regards,<br>
            <strong>' . htmlspecialchars($loginname) . '</strong><br>
            Accounts & Finance Team</p>
        </div>
    </body>
    </html>';

	// Recipient
	$sendto = [$sendtoemail ?: 'chatursingh011@gmail.com'];
	$sendcc = [];
	$sendbcc = [];
	$subject = 'Payment Request Rejected';

	// Optional email log
	// insertEmailLog($conn, $sendto, $sendcc, $sendbcc, $subject, $m_msgbody, '', 'Request Rejected');

	if (sendemailfrommailer($sendto, $sendcc, $sendbcc, $subject, $m_msgbody, [])) {
		return 'sent';
	} else {
		return 'notsend';
	}
}


function sendemailreject_voiceinstruction($conn, $vinstid, $vinstdate, $vinstfilepath, $vinstcreatedbyname, $vinstcreatedbyemail, $rejectcomment, $rejectbyemail, $rejectedbyname)
{
	$sendto = 'chatursingh011@gmail.com';
	// $sendto = $vinstcreatedbyemail;
	// $sendcc=$emailids;
	$subject = 'Voice Instruction Rejected';
	$m_msgbody = '
	<html>
		<body>
			<div class="container">
				<div class="row">
					<p>Dear ' . $vinstcreatedbyname . ',</p>
					<p>This is to inform your that your voice instruction id: ' . $vinstid . ' which is created on date ' . date('d-M-Y', strtotime($vinstdate)) . ' has been rejected due to the reason is mentioned in below.</p><br><p>Due to<br>' . $rejectcomment . '</p>
					<br>
					<audio controlsList="nodownload" src="' . $vinstfilepath . '" class="form-control" class="audioPlayback" controls></audio>
					<br/>
					<p>Thanks & Regards,</p>
					<p>' . $rejectedbyname . '</p>
				</div>
			</div>
		</body>
	</html>';
	// Always set content-type when sending HTML email
	$headers = "MIME-Version: 1.0" . "\r\n";
	$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

	// More headers
	$headers .= 'From: <info@amanmovement.org>' . "\r\n";
	$headers .= 'Cc: chatursingh011@gmail.com' . "\r\n";
	mail($sendto, $subject, $m_msgbody, $headers);
}

function sendemailfrommailer($sendto, $sendcc, $sendbcc, $m_subject, $m_msgbody, $attch = array())
{
	require_once "../PHPMailer/PHPMailerAutoload.php";
	$mail = new PHPMailer;
	//$mail->SMTPDebug = 2;                      // Enable verbose debug output
	$mail->isSMTP();                                            // Send using SMTP
	$mail->Host = 'amanaccounting.in';                    // Set the SMTP server to send through
	$mail->SMTPAuth = true;                                   // Enable SMTP authentication
	$mail->Username = 'documents@amanaccounting.in';                     // SMTP username
	$mail->Password = (require __DIR__ . '/../mail_config.php')['password'];                               // SMTP password
	$mail->SMTPSecure = 'tls';         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
	$mail->Port = 587;

	$mail->From = "documents@amanaccounting.in";
	$mail->FromName = "Aman Accounting";

	foreach ($sendto as $receiver) {
		$mail->addAddress($receiver, $receiver);
	}

	if ($sendcc) {
		foreach ($sendcc as $ccreceiver) {
			$mail->addAddress($ccreceiver, $ccreceiver);
		}
	}

	if ($sendbcc) {
		foreach ($sendbcc as $bccreceiver) {
			$mail->addAddress($bccreceiver, $bccreceiver);
		}
	}
	if ($attch) {
		foreach ($attch as $value) {
			$displayname = basename($value);
			$mail->AddAttachment($value, $displayname);
		}
	}

	$mail->isHTML(true);

	$mail->Subject = $m_subject;

	$mail->Body = $m_msgbody;
	if (!$mail->send()) {
		return false;
	} else {
		return true;
	}
}

function insertEmailLog($conn, $sendTo, $sendCc, $sendBcc, $subject, $msgBody, $attch, $mailtype)
{
	// Escape and prepare query
	$sendTo = $conn->real_escape_string($sendTo);
	$sendCc = $conn->real_escape_string($sendCc);
	$sendBcc = $conn->real_escape_string($sendBcc);
	$subject = $conn->real_escape_string($subject);
	$msgBody = $conn->real_escape_string($msgBody);
	$attch = $conn->real_escape_string($attch);
	$mailtype = $conn->real_escape_string($mailtype);

	$sql = "
        INSERT INTO emailsendby_cron 
        (sendTo, sendcc, sendBcc, m_subject, m_msgbody, attch, mailtype) 
        VALUES 
        ('$sendTo', '$sendCc', '$sendBcc', '$subject', '$msgBody', '$attch', '$mailtype')
    ";

	if ($conn->query($sql) === TRUE) {
		return true;
	} else {
		// Optional: log or return error
		echo "Error: " . $conn->error;
		return false;
	}
}


function GetfinancialyearlistfromBankstatment($conn, $companyid, $subcompanyid, $projectid)
{
	$finyeartype = getfinancialyeartype($conn, $companyid);
	if ($finyeartype == "Apr-Mar") {
		$finyearfq = $conn->query("SELECT CASE WHEN MONTH(valuedate)>=4 THEN CONCAT(YEAR(valuedate),'-',YEAR(valuedate)+1) ELSE CONCAT(YEAR(valuedate)-1,'-',YEAR(valuedate)) END AS financial_year FROM tbl_bankstatement where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' group by financial_year");
	} else if ($finyeartype == "Jan-Dec") {
		$finyearfq = $conn->query("SELECT YEAR(valuedate) financial_year FROM tbl_bankstatement where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' group by financial_year");
	} else {
		$finyearfq = $conn->query("SELECT CASE WHEN MONTH(valuedate)>=4 THEN CONCAT(YEAR(valuedate),'-',YEAR(valuedate)+1) ELSE CONCAT(YEAR(valuedate)-1,'-',YEAR(valuedate)) END AS financial_year FROM tbl_bankstatement where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' group by financial_year");
	}
	if ($finyearfq->num_rows > 0) {
		while ($frow = $finyearfq->fetch_assoc()) {
			if ($frow['financial_year'] == "-1-0") {
				$finyearlist = array();
			} else {
				$finyearlist[] = array('financialyear' => $frow['financial_year']);
			}

		}
	} else {
		$finyearlist = array();
	}
	return $finyearlist;
}
function removeduplicates($conn)
{
	$qry = "DELETE FROM tbl_bankstatement WHERE sno NOT IN (SELECT MIN(sno) FROM tbl_bankstatement GROUP BY balance, valuedate, projectid,account_id)";
	$conn->query($qry);
}
function makekeyautofrombankstatment($conn, $accountId)
{
	$qry = "SELECT * FROM tbl_bankstatement WHERE (key_status = '' OR key_status IS NULL) AND account_id = '$accountId'";

	$aryy = $conn->query($qry);
	if ($aryy && $aryy->num_rows > 0) {
		while ($rowc = $aryy->fetch_assoc()) {
			$qryf = "SELECT keyid, key1, key2, paymenttype, paytokey, key3 FROM tbl_bankdescription_key WHERE accountid = '$accountId'";
			$resultf = $conn->query($qryf);

			if ($resultf && $resultf->num_rows > 0) {
				$foundKey = false;
				while ($krow = $resultf->fetch_assoc()) {
					if (strstr(addslashes($rowc['description']), $krow['key1']) && strstr(addslashes($rowc['description']), $krow['key2'])) {
						$foundKey = true;
						processTransaction($conn, $rowc, $accountId, $krow);
						break;
					}
				}

				if (!$foundKey) {
					processTransaction($conn, $rowc, $accountId);
				}
			} else {
				processTransaction($conn, $rowc, $accountId);
			}
		}
	}
}

function processTransaction($conn, $rowc, $accountId, $krow = null)
{
	// Determine the amount and transaction type Corrected By Rohit
	$bnkamount = $rowc['debitamount'] ? $rowc['debitamount'] : $rowc['creditamount'];
	$transactiontype = $rowc['debitamount'] ? "withdrawn" : "deposit";

	// Check if an entry already exists
	$existingEntryQuery = "SELECT sno FROM tbl_closetobook 
                           WHERE account_id = ? 
                             AND date = ? 
                             AND balancefrombank = ? 
                             AND description_bank = ?";
	$stmt = $conn->prepare($existingEntryQuery);
	$stmt->bind_param(
		"ssss",
		$rowc['account_id'],
		$rowc['valuedate'],
		$rowc['balance'],
		$rowc['description']
	);
	$stmt->execute();
	$selqery = $stmt->get_result();

	if ($selqery && $selqery->num_rows == 0) {
		// Insert new entry if no existing entry is found
		$insertQuery = "INSERT INTO tbl_closetobook (
                            company_id, subcompany_id, project_id, payto, date, paymentmode, chequeno, 
                            account_id, bankid, total_amount, description_bank, banksno, key1, key2, 
                            key3, keyid, amounttype, balancefrombank
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
		$insertStmt = $conn->prepare($insertQuery);

		$payto = trim(addslashes($rowc['paytoname'] ? $rowc['paytoname'] : ($krow['paytokey'] ?? '')));
		$paymentmode = $krow['paymenttype'] ?? '';
		$chequeno = $rowc['chequeno'];
		$key1 = $krow['key1'] ?? '';
		$key2 = $krow['key2'] ?? '';
		$key3 = $krow['key3'] ?? '';
		$keyid = $krow['keyid'] ?? '';

		$insertStmt->bind_param(
			"ssssssssssssssssss",
			$rowc['companyid'],
			$rowc['subcompanyid'],
			$rowc['projectid'],
			$payto,
			$rowc['valuedate'],
			$paymentmode,
			$chequeno,
			$rowc['account_id'],
			$rowc['bankid'],
			$bnkamount,
			$rowc['description'],
			$rowc['sno'],
			$key1,
			$key2,
			$key3,
			$keyid,
			$transactiontype,
			$rowc['balance']
		);

		$insertStmt->execute();

		// Update tbl_bankstatement if $krow is provided
		if ($krow) {
			$updateQuery = "UPDATE tbl_bankstatement SET key_status = ? WHERE sno = ?";
			$updateStmt = $conn->prepare($updateQuery);
			$updateStmt->bind_param("ss", $keyid, $rowc['sno']);
			$updateStmt->execute();
		}
	}

	// Close the statement
	$stmt->close();
}




function base64url_encode($data)
{
	$b64 = base64_encode($data);

	if ($b64 === false) {
		return false;
	}
	$url = strtr($b64, '+/', '-_');
	return rtrim($url, '=');
}

function base64url_decode($data, $strict = false)
{
	$b64 = strtr($data, '-_', '+/');
	return base64_decode($b64, $strict);
}

function randomWithLength($length)
{
	$number = '';
	for ($i = 0; $i < $length; $i++) {
		$number .= rand(0, 9);
	}
	return (int) $number;
}

function remove_spaces($string)
{
	return preg_replace('/\s+/', ' ', $string);
}

function getNextDueDate($days)
{
	// Create a DateTime object for the current date
	$currentDate = new DateTime();

	// Add the specified number of days to the current date
	$currentDate->add(new DateInterval('P' . $days . 'D'));

	// Return the new date in the desired format (e.g., Y-m-d)
	return $currentDate->format('Y-m-d');
}

function getNextDueDateAfterDays($currentDate, $daysToAdd)
{
	// Create a DateTime object from the given date
	$date = new DateTime($currentDate);

	// Add the specified number of days
	$date->modify("+$daysToAdd days");

	// Return the new date in a desired format (e.g., 'Y-m-d')
	return $date->format('Y-m-d');
}

function getDateDifference($startDate, $endDate)
{
	// Create DateTime objects from the given dates
	$start = new DateTime($startDate);
	$end = new DateTime($endDate);

	// Calculate the difference between the two dates
	$interval = $start->diff($end);

	// Return the difference in days
	return $interval->days;
}

function getDaysDifference($date1, $date2)
{
	// Create DateTime objects
	$firstDate = new DateTime($date1);
	$secondDate = new DateTime($date2);

	// Calculate the difference
	$interval = $firstDate->diff($secondDate);

	// Return the difference in days, with a sign
	return ($firstDate < $secondDate) ? $interval->days : -$interval->days;
}

function convertDateToYMD($dateString)
{
	// Try to create a DateTime object from the input string
	$date = DateTime::createFromFormat(detectDateTimeFormat($dateString), trim($dateString));
	//$date = date_create($dateString);
	// If the date was successfully created, format it to 'Y-m-d'
	if ($date) {
		return $date->format('Y-m-d');
	} else {
		// Handle invalid date strings by returning null or an error message
		return null; // You can return 'Invalid date' or handle it as needed
	}
}

function detectDateTimeFormat($dateTimeString)
{
	$formats = [
		'd/m/y H:i:s' => '/^\d{2}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}$/', // 31/12/24 23:59:59
		'd-m-y H:i:s' => '/^\d{2}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', // 31-12-24 23:59:59
		'Y-m-d H:i:s' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', // 2024-12-31 23:59:59
		'Y-m-d H:i' => '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', // 2024-12-31 23:59
		'd-m-Y H:i:s' => '/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}:\d{2}$/', // 31-12-2024 23:59:59
		'd-m-Y H:i' => '/^\d{2}-\d{2}-\d{4} \d{2}:\d{2}$/', // 31-12-2024 23:59
		'd/m/Y H:i:s' => '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', // 31/12/2024 23:59:59
		'm/d/Y H:i:s' => '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}$/', // 12/31/2024 23:59:59
		'm/d/Y H:i' => '/^\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}$/', // 12/31/2024 23:59
		'Y/m/d H:i:s' => '/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2}$/', // 2024/12/31 23:59:59
		'Y/m/d H:i' => '/^\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}$/', // 2024/12/31 23:59
		'd M Y H:i:s' => '/^\d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2}:\d{2}$/', // 31 Dec 2024 23:59:59
		'dMY H:i:s' => '/^\d{2}[A-Za-z]{3}\d{4} \d{2}:\d{2}:\d{2}$/', // 31Dec2024 23:59:59
		'd M Y H:i' => '/^\d{2} [A-Za-z]{3} \d{4} \d{2}:\d{2}$/', // 31 Dec 2024 23:59
		'd F Y H:i:s' => '/^\d{2} [A-Za-z]+ \d{4} \d{2}:\d{2}:\d{2}$/', // 31 December 2024 23:59:59
		'd F Y H:i' => '/^\d{2} [A-Za-z]+ \d{4} \d{2}:\d{2}$/', // 31 December 2024 23:59
		'Ymd H:i:s' => '/^\d{8} \d{2}:\d{2}:\d{2}$/', // 20241231 23:59:59
		'Ymd H:i' => '/^\d{8} \d{2}:\d{2}$/', // 20241231 23:59
		'd/m/y' => '/^\d{2}\/\d{2}\/\d{2}$/', // 31/12/24
		'd-m-y' => '/^\d{2}-\d{2}-\d{2}$/', // 31-12-24
		'Y-m-d' => '/^\d{4}-\d{2}-\d{2}$/', // 2024-12-31
		'd-M-Y' => '/^\d{2}-[A-Za-z]{3}-\d{4}$/', // 31-Dec-2024
		'd-m-Y' => '/^\d{2}-\d{2}-\d{4}$/', // 31-12-2024
		'dMY' => '/^\d{2}[A-Za-z]{3}\d{4}$/', // 31Dec2024
		'd/m/Y' => '/^\d{2}\/\d{2}\/\d{4}$/', // 31/12/2024
		'm/d/Y' => '/^\d{2}\/\d{2}\/\d{4}$/', // 12/31/2024
	];

	foreach ($formats as $format => $regex) {
		if (preg_match($regex, $dateTimeString)) {
			return $format;
		}
	}
	return false; // No match found
}


function removeQuotes($string)
{
	return str_replace(['"', "'"], '', $string);
}

function formatToFloat($number)
{
	return number_format((float) $number, 2, '.', '');
}

function getCurrentFinancialYearwithDate($date, $finyeartype = "Apr-Mar")
{
	$dateTime = new DateTime($date);

	$year = (int) $dateTime->format('Y');
	$month = (int) $dateTime->format('m');
	if ($finyeartype == "Apr-Mar") {
		if ($month >= 4) {
			$startYear = $year;
			$endYear = $year + 1;
		} else {
			$startYear = $year - 1;
			$endYear = $year;
		}
		return $startYear . '-' . $endYear;
	} else if ($finyeartype == "Jan-Dec") {
		return $year;
	}
}

function isValidDate($idate, $format = 'Y-m-d H:i:s')
{
	$d = DateTime::createFromFormat($format, $idate);
	// Check if the date is valid than return 1 or empty and if it matches the provided format
	return $d && $d->format($format) === $idate;
}

function excelDateToDateTime($excelDate)
{
	// Excel date starts from January 1, 1900
	$startDate = \DateTime::createFromFormat('Y-m-d', '1900-01-01');

	// Add the number of days from Excel date (subtract 1 because January 1 is day 1)
	return $startDate->modify("+{$excelDate} days");
}


//Rohit
function standardizeKeys($array)
{
	$standardKeys = [];
	foreach ($array as $entry) {
		$standardKeys = array_merge($standardKeys, array_keys($entry));
	}
	foreach ($array as &$entry) {
		foreach ($standardKeys as $key) {
			if (!array_key_exists($key, $entry)) {
				$entry[$key] = null; // Add the missing key with a null value
			}
		}
	}
	return $array;
}
//Rohit

function constructAssociativeArray($originalArray)
{
	$newArray = array();
	$keys = null;
	foreach ($originalArray as $index => $row) {
		if ($index === 0) {
			$keys = $row;
			// If the key is empty, assign a default value
			foreach ($keys as $keyIndex => $key) {
				if (empty($key)) {
					$keys[$keyIndex] = 'NoKey' . ($keyIndex + 1);
				}
			}
		} else {
			$newRow = array();
			foreach ($keys as $keyIndex => $key) {
				if (!empty($row[$keyIndex])) {
					$newRow[trim($key)] = $row[$keyIndex];
				}
			}
			if (!empty($newRow)) {
				$newArray[] = $newRow;
			}
		}
	}
	return $newArray;
}

function detectheaderrow($exceldata)
{
	$rowlengths = array();
	foreach ($exceldata as $rowdata) {
		$totalelements = 0;
		for ($col = 0; $col < count($rowdata); $col++) {
			if ($rowdata[$col])
				$totalelements++;
		}
		array_push($rowlengths, $totalelements);
	}
	return array_search(max($rowlengths), $rowlengths);
}

function detectHeaderRowFromArray($data)
{
	foreach ($data as $index => $row) {
		$allFilled = true;
		$i = 0;
		foreach ($row as $cell) {

			if (empty($cell) && !is_string($cell)) {
				// echo $i."string \n";
				$allFilled = false;
				break;
			} else if (empty($cell) && $cell !== '0') {
				// echo $i."field\n";
				$allFilled = false;
				break;
			}
			$i++;
		}
		if ($allFilled) {
			// echo "final\n";
			return $index;
		}
	}
	return -1;
}

// function detectHeaderRowFromArray($data) {
//     print_r($data);
//     foreach ($data as $index => $row) {
//         $allFilled = true; // Assume the row is filled initially
//         foreach ($row as $cell) {
//             // Check for empty cells (allowing '0' as valid input)
//             if (empty($cell) && $cell !== '0' && $cell !== 0) {
//                 $allFilled = false;
//                 break;
//             }
//         }
//         if ($allFilled) {
//             return $index; // Return the index of the first fully filled row
//         }
//     }
//     return -1; // Return -1 if no valid header row is found
// }

function getnonEmptyRows($data)
{
	$nonEmptyRows = [];
	foreach ($data as $row) {
		$allEmpty = true;
		foreach ($row as $cell) {
			if (!empty($cell) || $cell === '0') {
				$allEmpty = false;
				break;
			}
		}
		if (!$allEmpty) {
			$nonEmptyRows[] = $row;
		}
	}

	return $nonEmptyRows;
}

function removeZeroBeforeTen($i)
{
	if ($i[0] === "0") {
		$i = substr($i, 1);
	}
	return $i;
}

function addZeroBeforeTen($i)
{
	if ($i < 10) {
		$i = "0" . $i;
	}
	return $i;
}
function checkOrder($data, $checkkey)
{
	$isIncreasing = true;
	$isDecreasing = true;
	//echo "Checkkey: ".$checkkey;
//print_r($data);
	for ($i = 0; $i < count($data) - 1; $i++) {
		$currDate = date_format(date_create($data[$i][$checkkey]), "Y-m-d");
		$nextDate = date_format(date_create($data[$i + 1][$checkkey]), "Y-m-d");
		// echo"\n".$currDate;
		// echo"\n".$nextDate;
		if ($currDate > $nextDate) {
			$isIncreasing = false;
			// echo"\n".$currDate;
			// echo"\n".$nextDate;
			break;
		}
		if ($currDate < $nextDate) {
			// echo"\n".$currDate;
			// echo"\n".$nextDate;
			$isDecreasing = false;
			break;
		}
	}
	if ($isIncreasing) {
		return "increasing";
	}
	if ($isDecreasing) {
		return "decreasing";
	}
}



/*function checkOrder($array, $checkkey) {
	print_r($array);
	$n = count($array);
	if ($n <= 1) {
		return 'increasing'; // An array with one or no elements is considered to be in order
	}

	$isIncreasing = true;
	$isDecreasing = true;

	for ($i = 1; $i < $n; $i++) {
		if (strtotime($array[$i][$checkkey]) < strtotime($array[$i - 1][$checkkey])) {
			$isIncreasing = false;
		} elseif (strtotime($array[$i][$checkkey]) > strtotime($array[$i - 1][$checkkey])) {
			$isDecreasing = false;
		}
	}

	if ($isIncreasing) {
		return 'increasing';
	} elseif ($isDecreasing) {
		return 'decreasing';
	} else {
		return 'unordered'; // In case the dates are neither strictly increasing nor decreasing
	}
}*/


function checkconsistency($array, $openingbalance = null, $closingbalance = null)
{
	$inconsistent12 = array();
	$calbalance = 0;
	$prvbal = 0;
	for ($j = 0; $j < count($array); $j++) {
		if ($j == 0) {
			$calbalance = ((float) $openingbalance + (float) $array[$j]['creditamount']) - (float) $array[$j]['debitamount'];
		} else {
			$calbalance = ((float) $prvbal + (float) $array[$j]['creditamount']) - (float) $array[$j]['debitamount'];
		}
		$prvbal = $calbalance;
	}
	$cb = $closingbalance;
	$epsilon = 0.00001;

	if (abs((float) $calbalance - (float) $closingbalance) > $epsilon) {
		return $inconsistent12 = array("status" => "1", "message" => "Inconsistent entries found in bankstatement. Calculated closing balance: " . $calbalance . " Given closing balance: " . $cb);
	} else {
		return [];
	}
}

function keymapping_byexpensesheet($conn, $companyid, $subcompanyid, $projectid)
{
	$keyquer = "SELECT sno, companyid, subcompanyid, projectid, colp_accountno, colp_date, colp_name, colp_amount, colp_chequeno, colp_description, colp_purpose, colp_purposecode, colp_hrow, colp_payfrombankname, colp_invoiceno, colp_invoicedate, colp_invoiceamount, colp_tdsamount, colp_taxableamount, colp_cgstamount, colp_sgstamount, colp_igstamount FROM tbl_expensesheet_import_keys WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	$resultkey = $conn->query($keyquer);
	$rrs = array();
	if ($resultkey->num_rows > 0) {
		$rowskey = $resultkey->fetch_assoc();
		$rrs = array('sno' => $rowskey['sno'], 'companyid' => $rowskey['companyid'], 'subcompanyid' => $rowskey['subcompanyid'], 'projectid' => $rowskey['projectid'], 'accountno' => $rowskey['colp_accountno'], 'date' => $rowskey['colp_date'], 'name' => $rowskey['colp_name'], 'amount' => $rowskey['colp_amount'], 'chequeno' => $rowskey['colp_chequeno'], 'description' => $rowskey['colp_description'], 'purpose' => $rowskey['colp_purpose'], 'purposecode' => $rowskey['colp_purposecode'], 'hrow' => $rowskey['colp_hrow'], 'payfrombankname' => $rowskey['colp_payfrombankname'], 'invoiceno' => $rowskey['colp_invoiceno'], 'invoicedate' => $rowskey['colp_invoicedate'], 'invoiceamount' => $rowskey['colp_invoiceamount'], 'tdsamount' => $rowskey['colp_tdsamount'], 'taxableamount' => $rowskey['colp_taxableamount'], 'cgstamount' => $rowskey['colp_cgstamount'], 'sgstamount' => $rowskey['colp_sgstamount'], 'igstamount' => $rowskey['colp_igstamount']);
	}
	return $rrs;
}
function keymapping_bydepositsheet($conn, $companyid, $subcompanyid, $projectid)
{
	$keyquer = "SELECT sno, companyid, subcompanyid, projectid, colp_accountno, colp_date, colp_name, colp_amount, colp_chequeno, colp_description, colp_purpose, colp_purposecode, colp_hrow FROM tbl_depositsheet_import_keys WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	$resultkey = $conn->query($keyquer);
	$rrs = array();
	if ($resultkey->num_rows > 0) {
		$rowskey = $resultkey->fetch_assoc();
		$rrs = array('sno' => $rowskey['sno'], 'companyid' => $rowskey['companyid'], 'subcompanyid' => $rowskey['subcompanyid'], 'projectid' => $rowskey['projectid'], 'accountno' => $rowskey['colp_accountno'], 'date' => $rowskey['colp_date'], 'name' => $rowskey['colp_name'], 'amount' => $rowskey['colp_amount'], 'chequeno' => $rowskey['colp_chequeno'], 'description' => $rowskey['colp_description'], 'purpose' => $rowskey['colp_purpose'], 'purposecode' => $rowskey['colp_purposecode'], 'hrow' => $rowskey['colp_hrow']);
	}
	return $rrs;
}


function departmentmaster($conn)
{
	$lguq = $conn->query("SELECT id,departmentname FROM  tbldepartmentmaster order by departmentname");
	$ullist = array();
	if ($lguq->num_rows > 0) {
		while ($lurow = $lguq->fetch_assoc()) {
			$ullist[] = $lurow;
		}
	}
	return $ullist;
}

function get_purposesubcategorywithorder($conn, $companyid, $subcompanyid, $projectid)
{
	$queryp = "SELECT distinct pcm.sub_category, pcm.sub_categoryid, COALESCE(pco.display_order, 9999) AS display_order FROM purpose_code_master pcm LEFT JOIN purpose_order_withdrawal pco  ON pcm.sub_categoryid = pco.sub_category_id AND pcm.projectid=pco.project_id where pcm.cmp_id='" . $companyid . "' and pcm.subcmp_id='" . $subcompanyid . "' and pcm.projectid='" . $projectid . "' and (pcm.Tedit is null or pcm.tedit='') ORDER BY pco.display_order, pcm.sub_categoryid";
	$resultp = $conn->query($queryp);
	$psubcatlist = array();
	if ($resultp->num_rows > 0) {
		while ($rowp = $resultp->fetch_assoc()) {
			$psubcatlist[] = array('sub_category' => $rowp['sub_category'], 'sub_categoryid' => $rowp['sub_categoryid']);
		}
	}
	return $psubcatlist;

}

function insertdefaultdebitpurposecodes($conn, $companyid, $subcompanyid, $projectid)
{
	if ((int) $companyid >= 0 && (int) $subcompanyid >= 0 && (int) $projectid >= 0) {
		$pcm = "purpose_category_master";
		$pm = "purpose_code_master";
		$qrydebit = "SELECT * FROM main_defaultpurpose_master where (tedit is null or tedit='') and type='withdrawn'";
		$res = $conn->query($qrydebit);
		$purposedata = array();
		$repsubcatid = "";
		$ss = 1;
		$oldmaxcode = "";
		if ($res->num_rows > 0) {
			while ($row = $res->fetch_assoc()) {
				if ($row['subcategoryid'] == $repsubcatid) {
					$ss += 1;
					$providedcode = $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $oldmaxcode)[1] + $ss);
				} else {
					$repsubcatid = $row['subcategoryid'];
					$mxprcode = maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $row['subcategoryid']);
					$oldmaxcode = $mxprcode;
					// $providedcode = ($mxprcode) ? $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $mxprcode)[1] + $ss) : $row['subcategoryid'] . '.01';
					$providedcode = $mxprcode > 0 ? $row['subcategoryid'] . '.' . addZeroBeforeTen($mxprcode + 1) : $row['subcategoryid'] . '.01';
				}
				$purposedata[] = array('companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'categoryid' => $row['categoryid'], 'category' => $row['category'], 'subcategoryid' => $row['subcategoryid'], 'subcategory' => ($row['subcategoryid'] . '-' . $row['subcategory']), 'purpose' => $row['purposename'], 'providedcode' => $providedcode, 'frompayto_sqltbl' => $row['frompayto_sqltbl'], 'frompayto_condition' => $row['frompayto_condition'], 'source' => 'auto');
			}
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$conn->begin_transaction();
		try {
			foreach ($purposedata as $purposeentry) {
				if (stristr($purposeentry["subcategoryid"], "-")) {
					$subcategoryid = explode("-", $purposeentry["subcategoryid"])[0];
				} else {
					$subcategoryid = $purposeentry["subcategoryid"];
				}
				$purposeid = "";
				$purposecode = "";
				$insertsubcategory = "";
				$qry = "select * from " . $pcm . " where cmp_id='" . $purposeentry["companyid"] . "' and subcmp_id='" . $purposeentry["subcompanyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and categoryid='" . $purposeentry["categoryid"] . "' and lcase(category)='" . addslashes(strtolower($purposeentry["category"])) . "' and lcase(sub_category)='" . addslashes(strtolower($purposeentry["subcategory"])) . "' and ifnull(tedit,'')='' limit 1";
				$res = $conn->query($qry);
				if ($res->num_rows > 0) {
					$insertsubcategory = "";
				} else {
					$insertsubcategory = "1";
				}
				$qry = "select ifnull(max(cast(purposeid as unsigned)),0)+1 as purposeid from " . $pm . " where cmp_id='" . $purposeentry["companyid"] . "' and subcmp_id='" . $purposeentry["subcompanyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and ifnull(tedit,'')=''";
				$res = $conn->query($qry);
				if ($res->num_rows > 0) {
					$row = $res->fetch_assoc();
					$purposeid = $row["purposeid"];
				}
				//insert statements here purpose category master.
				if ($insertsubcategory) {
					$ins = "insert into " . $pcm . " (cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category)values('" . $purposeentry["companyid"] . "','" . $purposeentry["subcompanyid"] . "','" . $purposeentry["projectid"] . "','" . $purposeentry["categoryid"] . "','" . addslashes(strtolower($purposeentry["category"])) . "','" . $subcategoryid . "','" . addslashes(strtoupper($purposeentry["subcategory"])) . "')";
					$res = $conn->query($ins);
				}

				$qry1 = "select * from " . $pm . " where cmp_id='" . $purposeentry["companyid"] . "' and subcmp_id='" . $purposeentry["subcompanyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and categoryid='" . $purposeentry["categoryid"] . "'  and sub_categoryid='" . addslashes($purposeentry["subcategoryid"]) . "' and lcase(purpose)='" . addslashes(strtolower($purposeentry["purpose"])) . "' and ifnull(tedit,'')='' limit 1";

				$res1 = $conn->query($qry1);
				if ($res1->num_rows > 0) {
					$insertpurpose = "";
				} else {
					$insertpurpose = "1";
				}

				if ($insertpurpose) {
					$purposecode = createPurposeCodeEncoded($purposeentry["companyid"], $purposeentry["subcompanyid"], $purposeentry["projectid"], $purposeentry["categoryid"], $subcategoryid, $purposeid, $purposeentry["providedcode"]);

					$ins = "insert into " . $pm . " (cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category,purposeid,purpose,provided_code,purposecode,frompayto_sqltbl,frompayto_condition,source,visiblestatus)values('" . $purposeentry["companyid"] . "','" . $purposeentry["subcompanyid"] . "','" . $purposeentry["projectid"] . "','" . $purposeentry["categoryid"] . "','" . addslashes(strtoupper($purposeentry["category"])) . "','" . $subcategoryid . "','" . addslashes(strtoupper($purposeentry["subcategory"])) . "','" . $purposeid . "','" . addslashes(strtoupper($purposeentry["purpose"])) . "','" . $purposeentry["providedcode"] . "','" . $purposecode[0]["combinedCode"] . "','" . addslashes($purposeentry["frompayto_sqltbl"]) . "','" . addslashes($purposeentry["frompayto_condition"]) . "','" . $purposeentry["source"] . "','yes')";
					$res = $conn->query($ins);
				}
			}
			$conn->commit();
		} catch (mysqli_sql_exception $e) {
			$conn->rollback();
			echo $e;
		}
	} else {

	}
}

function insertdefaultcreditpurposecodes($conn, $companyid, $subcompanyid, $projectid)
{
	$pcm = "purpose_category_master_credit";
	$pm = "purpose_code_master_credit";
	$qrydebit = "SELECT sno, categoryid, category, subcategoryid, subcategory, purposename, frompayto_sqltbl, frompayto_condition, type, tedit FROM main_defaultpurpose_master where (tedit is null or tedit='') and type in('deposit','credit')";
	$res = $conn->query($qrydebit);
	$purposedata = array();
	$ss = 1;
	$repsubcatid = "";
	$oldmaxcode = "";
	if ($res->num_rows > 0) {
		while ($row = $res->fetch_assoc()) {
			if ($row['subcategoryid'] == $repsubcatid) {
				$ss += 1;
				$providedcode = $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $oldmaxcode)[1] + $ss);
			} else {
				$repsubcatid = $row['subcategoryid'];
				$mxprcode = credit_maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $row['subcategoryid']);
				$oldmaxcode = $mxprcode;
				// $providedcode = ($mxprcode) ? $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $mxprcode)[1] + $ss) : $row['subcategoryid'] . '.01';

				$providedcode = $mxprcode > 0 ? $row['subcategoryid'] . '.' . addZeroBeforeTen($mxprcode + 1) : $row['subcategoryid'] . '.01';

			}

			$purposedata[] = array('companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'categoryid' => $row['categoryid'], 'category' => $row['category'], 'subcategoryid' => $row['subcategoryid'], 'subcategory' => ($row['subcategoryid'] . '-' . $row['subcategory']), 'purpose' => $row['purposename'], 'providedcode' => $providedcode, 'frompayto_sqltbl' => $row['frompayto_sqltbl'], 'frompayto_condition' => $row['frompayto_condition'], 'source' => 'auto');
		}
	}
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn->begin_transaction();
	try {
		foreach ($purposedata as $purposeentry) {
			if (stristr($purposeentry["subcategoryid"], "-")) {
				$subcategoryid = explode("-", $purposeentry["subcategoryid"])[0];
			} else {
				$subcategoryid = $purposeentry["subcategoryid"];
			}
			$purposeid = "";
			$purposecode = "";
			$insertsubcategory = "";
			$qry = "select * from " . $pcm . " where cmp_id='" . $purposeentry["companyid"] . "' and subcmp_id='" . $purposeentry["subcompanyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and categoryid='" . $purposeentry["categoryid"] . "' and ucase(category)='" . addslashes(strtoupper($purposeentry["category"])) . "' and ucase(sub_category)='" . addslashes(strtoupper($purposeentry["subcategory"])) . "' and ifnull(tedit,'')='' limit 1";
			$res = $conn->query($qry);
			if ($res->num_rows > 0) {
				$insertsubcategory = "";
			} else {
				$insertsubcategory = "1";
			}
			$qry = "select ifnull(max(cast(purposeid as unsigned)),0)+1 as purposeid from " . $pm . " where cmp_id='" . $purposeentry["companyid"] . "' and subcmp_id='" . $purposeentry["subcompanyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and ifnull(tedit,'')=''";
			$res = $conn->query($qry);
			if ($res->num_rows > 0) {
				$row = $res->fetch_assoc();
				$purposeid = $row["purposeid"];
			}
			//insert statements here.
			if ($insertsubcategory) {
				$ins = "insert into " . $pcm . " (cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category)values('" . $purposeentry["companyid"] . "','" . $purposeentry["subcompanyid"] . "','" . $purposeentry["projectid"] . "','" . $purposeentry["categoryid"] . "','" . addslashes(strtoupper($purposeentry["category"])) . "','" . $subcategoryid . "','" . addslashes(strtoupper($purposeentry["subcategory"])) . "')";
				$res = $conn->query($ins);
			}
			$qry1 = "select * from " . $pm . " where cmp_id='" . $purposeentry["companyid"] . "' and subcmp_id='" . $purposeentry["subcompanyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and categoryid='" . $purposeentry["categoryid"] . "'  and sub_categoryid='" . addslashes($purposeentry["subcategoryid"]) . "' and ucase(purpose)='" . addslashes(strtoupper($purposeentry["purpose"])) . "' and ifnull(tedit,'')='' limit 1";

			$res1 = $conn->query($qry1);
			if ($res1->num_rows > 0) {
				$insertpurpose = "";
			} else {
				$insertpurpose = "1";
			}
			if ($insertpurpose) {
				$purposecode = createPurposeCodeEncoded($purposeentry["companyid"], $purposeentry["subcompanyid"], $purposeentry["projectid"], $purposeentry["categoryid"], $subcategoryid, $purposeid, $purposeentry["providedcode"]);
				$ins = "insert into " . $pm . " (cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category,purposeid,purpose,provided_code,purposecode,frompayto_sqltbl,frompayto_condition,source,visiblestatus)values('" . $purposeentry["companyid"] . "','" . $purposeentry["subcompanyid"] . "','" . $purposeentry["projectid"] . "','" . $purposeentry["categoryid"] . "','" . addslashes(strtoupper($purposeentry["category"])) . "','" . $subcategoryid . "','" . addslashes(strtoupper($purposeentry["subcategory"])) . "','" . $purposeid . "','" . addslashes(strtoupper($purposeentry["purpose"])) . "','" . $purposeentry["providedcode"] . "','" . $purposecode[0]["combinedCode"] . "','" . addslashes($purposeentry["frompayto_sqltbl"]) . "','" . addslashes($purposeentry["frompayto_condition"]) . "','" . $purposeentry["source"] . "','yes')";
				$res = $conn->query($ins);
			}
		}
		$conn->commit();
	} catch (mysqli_sql_exception $e) {
		$conn->rollback();
		echo $e;
	}
}

function insertdefaultdebitpurposecodesinPNL($conn, $companyid, $subcompanyid, $projectid)
{
	$pcm = "purpose_category_master";
	$pm = "purpose_code_master";
	$pnl = "pnl_reporting_defaultpurpose_master";
	$qrydebit = "SELECT sno, categoryid, category, subcategoryid, subcategory, purposename, type,rowno FROM main_defaultpurpose_master where (tedit is null or tedit='') and type='withdrawn'";
	$res = $conn->query($qrydebit);
	$purposedata = array();
	$repsubcatid = "";
	$ss = 1;
	$oldmaxcode = "";
	if ($res->num_rows > 0) {
		while ($row = $res->fetch_assoc()) {
			if ($row['subcategoryid'] == $repsubcatid) {
				$ss += 1;
				$providedcode = $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $oldmaxcode)[1] + $ss);
			} else {
				$repsubcatid = $row['subcategoryid'];
				$mxprcode = maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $row['subcategoryid']);
				$oldmaxcode = $mxprcode;
				// $providedcode = ($mxprcode) ? $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $mxprcode)[1] + $ss) : $row['subcategoryid'] . '.01';
				$providedcode = $mxprcode > 0 ? $row['subcategoryid'] . '.' . addZeroBeforeTen($mxprcode + 1) : $row['subcategoryid'] . '.01';
			}
			$purposedata[] = array('companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'categoryid' => $row['categoryid'], 'category' => $row['category'], 'subcategoryid' => $row['subcategoryid'], 'subcategory' => ($row['subcategoryid'] . '-' . $row['subcategory']), 'purpose' => $row['purposename'], 'providedcode' => $providedcode, 'source' => 'auto', 'rowno' => $row['rowno']);
		}
	}
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn->begin_transaction();
	try {
		foreach ($purposedata as $purposeentry) {

			$purposeid = "";
			$purposecode = "";
			$insertsubcategory = "";



			$purposecode = createPurposeCodeEncoded($purposeentry["companyid"], $purposeentry["subcompanyid"], $purposeentry["projectid"], $purposeentry["categoryid"], $subcategoryid, $purposeid, $purposeentry["providedcode"]);

			$resltqry = $conn->query("Select * from " . $pnl . " where companyid='" . $purposeentry["companyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and purposecode='" . $purposecode[0]["combinedCode"] . "'");
			if ($resltqry->num_rows > 0) {
				// code...
			} else {
				$ins = "insert into " . $pnl . " (companyid,projectid,subcategoryid,subcategory,purposename,provided_code,purposecode,rowno,type,functionname)values('" . $purposeentry["companyid"] . "','" . $purposeentry["projectid"] . "','" . $purposeentry["subcategoryid"] . "','" . addslashes($purposeentry["subcategory"]) . "','" . addslashes($purposeentry["purpose"]) . "','" . $purposeentry["providedcode"] . "','" . $purposecode[0]["combinedCode"] . "','" . $purposeentry["rowno"] . "','withdrawn','functiongetpurposeamountpnl')";

				$res = $conn->query($ins);
			}




		}
		$conn->commit();
	} catch (mysqli_sql_exception $e) {
		$conn->rollback();
		echo $e;
	}
}

function insertdefaultcreditpurposecodesinPNL($conn, $companyid, $subcompanyid, $projectid)
{
	$pcm = "purpose_category_master";
	$pm = "purpose_code_master";
	$pnl = "pnl_reporting_defaultpurpose_master";
	$qrydebit = "SELECT sno, categoryid, category, subcategoryid, subcategory, purposename, type,rowno FROM main_defaultpurpose_master where (tedit is null or tedit='') and type='deposit'";
	$res = $conn->query($qrydebit);
	$purposedata = array();
	$repsubcatid = "";
	$ss = 1;
	$oldmaxcode = "";
	if ($res->num_rows > 0) {
		while ($row = $res->fetch_assoc()) {
			if ($row['subcategoryid'] == $repsubcatid) {
				$ss += 1;
				$providedcode = $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $oldmaxcode)[1] + $ss);
			} else {
				$repsubcatid = $row['subcategoryid'];
				$mxprcode = credit_maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $row['subcategoryid']);
				$oldmaxcode = $mxprcode;
				// $providedcode = ($mxprcode) ? $row['subcategoryid'] . '.' . addZeroBeforeTen(explode(".", $mxprcode)[1] + $ss) : $row['subcategoryid'] . '.01';

				$providedcode = $mxprcode > 0 ? $row['subcategoryid'] . '.' . addZeroBeforeTen($mxprcode + 1) : $row['subcategoryid'] . '.01';
			}
			$purposedata[] = array('companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'categoryid' => $row['categoryid'], 'category' => $row['category'], 'subcategoryid' => $row['subcategoryid'], 'subcategory' => ($row['subcategoryid'] . '-' . $row['subcategory']), 'purpose' => $row['purposename'], 'providedcode' => $providedcode, 'source' => 'auto', 'rowno' => $row['rowno']);
		}
	}
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn->begin_transaction();
	try {
		foreach ($purposedata as $purposeentry) {

			$purposeid = "";
			$purposecode = "";
			$insertsubcategory = "";



			$purposecode = createPurposeCodeEncoded($purposeentry["companyid"], $purposeentry["subcompanyid"], $purposeentry["projectid"], $purposeentry["categoryid"], $subcategoryid, $purposeid, $purposeentry["providedcode"]);

			$resltqry = $conn->query("Select * from " . $pnl . " where companyid='" . $purposeentry["companyid"] . "' and projectid='" . $purposeentry["projectid"] . "' and purposecode='" . $purposecode[0]["combinedCode"] . "'");
			if ($resltqry->num_rows > 0) {
				// code...
			} else {
				$ins = "insert into " . $pnl . " (companyid,projectid,subcategoryid,subcategory,purposename,provided_code,purposecode,rowno,type,functionname)values('" . $purposeentry["companyid"] . "','" . $purposeentry["projectid"] . "','" . $purposeentry["subcategoryid"] . "','" . addslashes($purposeentry["subcategory"]) . "','" . addslashes($purposeentry["purpose"]) . "','" . $purposeentry["providedcode"] . "','" . $purposecode[0]["combinedCode"] . "','" . $purposeentry["rowno"] . "','deposit','functiongetpurposeamountpnl')";

				$res = $conn->query($ins);
			}




		}
		$conn->commit();
	} catch (mysqli_sql_exception $e) {
		$conn->rollback();
		echo $e;
	}
}

function insertnewpurposeinPNLmaster($conn, $companyid, $projectid, $purposesubcategoryid, $purposesubcategory, $purposename, $newmaxprovidedcode, $compaletpurposecode, $purposetype)
{

	$getrowno = getrownofromdefaltpurposemaster($conn, $purposesubcategoryid);

	$stmt_pnl = $conn->query("insert into pnl_reporting_defaultpurpose_master (companyid,projectid,subcategoryid,subcategory,purposename,provided_code,purposecode,rowno,type,functionname) VALUES ('" . $companyid . "',  '" . $projectid . "',  '" . $purposesubcategoryid . "', '" . addslashes($purposesubcategory) . "', '" . addslashes($purposename) . "', '" . $newmaxprovidedcode . "', '" . $compaletpurposecode . "', '" . $getrowno . "', '" . $purposetype . "', 'functiongetpurposeamountpnl')");
}


function getrownofromdefaltpurposemaster($conn, $purposesubcategoryid)
{
	$rowno = "";
	$sql = $conn->query("Select rowno from main_defaultpurpose_master where subcategoryid='" . $purposesubcategoryid . "'");
	if ($sql->num_rows > 0) {
		$rowsn = $sql->fetch_assoc();
		$rowno = $rowsn['rowno'];
	}

	return $rowno;

}

function checkbeneficiarypaymentPaidorNot($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue, $requestno = null, $referenceno = null)
{
	$ref_cond = "";
	if ($referenceno) {
		$ref_cond = " and m.reference_no='" . $referenceno . "'";
	}

	$req_cond = "";
	if ($requestno) {
		$req_cond = " and m.request_no='" . $requestno . "'";
	}
	if ($shortby == "bypaytoid") {
		$ccckb = "SELECT b.beneficiary_name,m.request_no,m.reference_no,m.transactionno,m.transaction_date,m., transfered_amount,m.bookdate,m.paymentduedate,m.paytoid,b.purpose from tbl_maintransaction m join tbl_maintransaction_breakdown b on m.reference_no=b.reference_no and m.companyid=b.companyid and m.subcompanyid=b.subcompanyid and m.projectid=b.projectid and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.paytoid='" . $shortbyvalue . "' " . $req_cond . " " . $ref_cond . "  order by b.sno desc limit 1";
	} else if ($shortby == "bypaytoname") {
		$ccckb = "SELECT b.beneficiary_name,m.request_no,m.reference_no,m.transactionno,m.transaction_date,m.transfered_amount,m.bookdate,m.paymentduedate,m.paytoid,b.purpose from tbl_maintransaction m join tbl_maintransaction_breakdown b on m.reference_no=b.reference_no and m.companyid=b.companyid and m.subcompanyid=b.subcompanyid and m.projectid=b.projectid and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.paytoname='" . $shortbyvalue . "' " . $req_cond . " " . $ref_cond . " order by b.sno desc limit 1";
	} else if ($shortby == "bybeneficiaryname") {
		$ccckb = "SELECT b.beneficiary_name,m.request_no,m.reference_no,m.transactionno,m.transaction_date,m.transfered_amount,m.bookdate,m.paymentduedate,m.paytoid,b.purpose from tbl_maintransaction m join tbl_maintransaction_breakdown b on m.reference_no=b.reference_no and m.companyid=b.companyid and m.subcompanyid=b.subcompanyid and m.projectid=b.projectid and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and b.beneficiary_name='" . $shortbyvalue . "' " . $req_cond . " " . $ref_cond . " order by b.sno desc limit 1";
	} else if ($shortby == "requestno") {
		$ccckb = "SELECT b.beneficiary_name,m.request_no,m.reference_no,m.transactionno,m.transaction_date,m.transfered_amount,m.bookdate,m.paymentduedate,m.paytoid,b.purpose from tbl_maintransaction m join tbl_maintransaction_breakdown b on m.reference_no=b.reference_no and m.companyid=b.companyid and m.subcompanyid=b.subcompanyid and m.projectid=b.projectid and m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' and m.request_no='" . $shortbyvalue . "' order by b.sno desc limit 1";
	}
	$ccckbrr = $conn->query($ccckb);
	$sts = array();
	if ($ccckbrr->num_rows > 0) {
		while ($brow = $ccckbrr->fetch_assoc()) {
			$sts[] = array('beneficiaryname' => $brow['beneficiary_name'], 'requestno' => $brow['request_no'], 'referenceno' => $brow['reference_no'], 'transactionno' => $brow['transactionno'], 'transactiondate' => $brow['transaction_date'], 'bookdate' => $brow['bookdate'], 'paymentduedate' => $brow['paymentduedate'], 'paytoid' => $brow['paytoid'], 'purpose' => $brow['purpose']);
		}
	}
	return $sts;
}
function gettotal_entries($conn, $companyid, $subcompanyid, $projectid, $purposecode)
{
	$ssq = $conn->query("SELECT ifnull(count(1),0) as total FROM tbl_maintransaction_breakdown WHERE (tedit is null or tedit='') and purpose_code='" . $purposecode . "'");
	if ($ssq->num_rows > 0) {
		$row = $ssq->fetch_assoc();
		return $row["total"];
	} else {
		return 0;
	}
}
function getpurposecategorymainmaster($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ssq = $conn->query("SELECT categoryid, category FROM main_category_master WHERE (tedit is null or tedit='')");
	} else if ($shortby == "readyforrequest") {
		$ssq = $conn->query("SELECT categoryid, category FROM main_category_master WHERE (tedit is null or tedit='') and isrequestpanel=0");
	} else if ($shortby == "categoryid") {
		$ssq = $conn->query("SELECT categoryid, category FROM main_category_master WHERE (tedit is null or tedit='') and categoryid='" . $shortbyvalue . "'");
	}
	$pcatlist = array();
	if ($ssq->num_rows > 0) {
		while ($pcrow = $ssq->fetch_assoc()) {
			$pcatlist[] = array('categoryid' => $pcrow['categoryid'], 'category' => $pcrow['category']);
		}
	}
	return $pcatlist;
}

function getpurposecategorymainmaster_credit($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ssq = $conn->query("SELECT categoryid, category FROM main_category_master_credit WHERE (tedit is null or tedit='')");
	} else if ($shortby == "categoryid") {
		$ssq = $conn->query("SELECT categoryid, category FROM main_category_master_credit WHERE (tedit is null or tedit='') and categoryid='" . $shortbyvalue . "'");
	}
	$pcatlist = array();
	if ($ssq->num_rows > 0) {
		while ($pcrow = $ssq->fetch_assoc()) {
			$pcatlist[] = array('categoryid' => $pcrow['categoryid'], 'category' => $pcrow['category']);
		}
	}
	return $pcatlist;
}

function checkexistsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid, $subcategoryid)
{
	$qry = $conn->query("SELECT sub_categoryid as maxcategoryid from purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid='" . $subcategoryid . "' and ifnull(tedit,'')='' limit 1");
	if ($qry->num_rows > 0) {
		$msbrow = $qry->fetch_assoc();
		return $msbrow['maxcategoryid'];
	}
}

function maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid)
{
	$qry = $conn->query("SELECT distinct MAX(sub_categoryid) as maxcategoryid from purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and ifnull(tedit,'')=''");
	if ($qry->num_rows > 0) {
		$msbrow = $qry->fetch_assoc();
		return $msbrow['maxcategoryid'];
	}
}



function maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $subcategoryid)
{
	$qryp = $conn->query("SELECT count(sno) as maxprovidedcode from purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid='" . $subcategoryid . "' and ifnull(tedit,'')=''");
	if ($qryp->num_rows > 0) {
		$mprdrow = $qryp->fetch_assoc();
		return $mprdrow['maxprovidedcode'];
	}
}

function maxsubcategorycode_frm_client($conn, $companyid, $subcompanyid, $projectid, $type)
{
	if (strtolower($type) == "credit" || strtolower($type) == "deposit") {
		$qry = $conn->query("SELECT distinct ifnull(max(subcategoryid),0) as maxcategoryid from purpose_codemaster_client_credit where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and ifnull(tedit,'')=''");
	} else {
		$qry = $conn->query("SELECT distinct ifnull(max(subcategoryid),0) as maxcategoryid from purpose_codemaster_client where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and ifnull(tedit,'')=''");
	}
	if ($qry->num_rows > 0) {
		$msbrow = $qry->fetch_assoc();
		return $msbrow['maxcategoryid'];
	}
}

function maxprovidecode_frm_client($conn, $companyid, $subcompanyid, $projectid, $subcategoryid)
{
	$qryp = $conn->query("SELECT count(providedcode) as maxprovidedcode from purpose_codemaster_client where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and subcategoryid='" . $subcategoryid . "' and ifnull(tedit,'')=''");
	if ($qryp->num_rows > 0) {
		$mprdrow = $qryp->fetch_assoc();
		return $mprdrow['maxprovidedcode'];
	}
}

function credit_checkexistsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid, $subcategoryid)
{
	$qry = $conn->query("SELECT sub_categoryid as maxcategoryid from purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid='" . $subcategoryid . "' and ifnull(tedit,'')='' limit 1");
	if ($qry->num_rows > 0) {
		$msbrow = $qry->fetch_assoc();
		return $msbrow['maxcategoryid'];
	}
}

function credit_maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid)
{
	$qry = $conn->query("SELECT distinct max(sub_categoryid) as maxcategoryid from purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid<30 and ifnull(tedit,'')=''");
	if ($qry->num_rows > 0) {
		$msbrow = $qry->fetch_assoc();
		return $msbrow['maxcategoryid'];
	}
}

function getMainSubcategoryMaster($conn, $shortby = 'all', $shortbyvalue = '')
{
	// Base query
	$query = "SELECT categoryid, subcategoryid, subcategory FROM main_subcategory_master WHERE (tedit IS NULL OR tedit = '')";

	// Apply filters
	if ($shortby === 'categoryid' && $shortbyvalue !== '') {
		$shortbyvalue = $conn->real_escape_string($shortbyvalue);
		$query .= " AND categoryid = '{$shortbyvalue}'";
	} elseif ($shortby === 'subcategoryid' && $shortbyvalue !== '') {
		$shortbyvalue = $conn->real_escape_string($shortbyvalue);
		$query .= " AND subcategoryid = '{$shortbyvalue}'";
	} elseif ($shortby === 'sno' && $shortbyvalue !== '') {
		$shortbyvalue = (int) $shortbyvalue;
		$query .= " AND sno = {$shortbyvalue}";
	}

	$result = $conn->query($query);

	$data = [];
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}

	return $data;
}

function getMainPurposeListDefaultMaster($conn, $filterBy = 'all', $filterValue = '')
{
	// Base query
	$query = "SELECT sno, categoryid, subcategoryid, purposename 
              FROM main_purposelistdefault_master 
              WHERE (tedit IS NULL OR tedit = '')";

	// Apply filters based on provided filter type
	switch ($filterBy) {
		case 'categoryid':
			if ($filterValue !== '') {
				$filterValue = $conn->real_escape_string($filterValue);
				$query .= " AND categoryid = '{$filterValue}'";
			}
			break;
		case 'subcategoryid':
			if ($filterValue !== '') {
				$filterValue = $conn->real_escape_string($filterValue);
				$query .= " AND subcategoryid = '{$filterValue}'";
			}
			break;
		case 'sno':
			if ($filterValue !== '') {
				$filterValue = (int) $filterValue;
				$query .= " AND sno = {$filterValue}";
			}
			break;
		// 'all' means no filter
	}

	// Execute the query
	$result = $conn->query($query);
	$data = [];

	// Collect results
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}

	return $data;
}

function getUniquePurposeListForMapping($conn, $companyId, $subCompanyId, $projectId, $transactiontype)
{
	$companyId = (int) $companyId;
	$subCompanyId = (int) $subCompanyId;
	$projectId = (int) $projectId;

	// Determine correct master table based on transaction type
	$pcodemaster = in_array(strtolower($transactiontype), ['credit', 'deposit'])
		? 'purpose_code_master_credit'
		: 'purpose_code_master';

	// Query construction
	$query = "
        SELECT 
            pcm.categoryid,
            pcm.sub_categoryid AS subcategoryid,
            pcm.purpose AS purposename,
            pcm.purposecode,
            '' AS defaultsno
        FROM $pcodemaster pcm
        WHERE 
            pcm.cmp_id = $companyId AND
            pcm.subcmp_id = $subCompanyId AND
            pcm.projectid = $projectId AND
            IFNULL(pcm.tedit, '') = ''
        
        UNION
        
        SELECT 
            def.categoryid,
            def.subcategoryid,
            def.purposename,
            '' AS purposecode,
            def.sno AS defaultsno
        FROM main_purposelistdefault_master def
        WHERE 
            IFNULL(def.tedit, '') = '' AND
            NOT EXISTS (
                SELECT 1 
                FROM $pcodemaster pcm
                WHERE 
                    pcm.cmp_id = $companyId AND
                    pcm.subcmp_id = $subCompanyId AND
                    pcm.projectid = $projectId AND
                    pcm.purpose = def.purposename
            )
    ";

	// Fetch and return data
	$data = [];
	if ($result = $conn->query($query)) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}

	return $data;
}



// function getUniquePurposeListForMapping($conn, $companyId, $subCompanyId, $projectId) {
//     $companyId = (int)$companyId;
//     $subCompanyId = (int)$subCompanyId;
//     $projectId = (int)$projectId;

//     $query = "
//         SELECT categoryid, sub_categoryid AS subcategoryid, purpose AS purposename, purposecode, '' as defaultsno 
//         FROM purpose_code_master 
//         WHERE cmp_id = {$companyId} AND subcmp_id = {$subCompanyId} AND projectid = {$projectId} AND IFNULL(tedit, '') = ''

//         UNION

//         SELECT categoryid, subcategoryid, purposename, '' AS purposecode, sno as defaultsno
//         FROM main_purposelistdefault_master 
//         WHERE IFNULL(tedit, '') = ''
//     ";

//     $data = [];
//     if ($result = $conn->query($query)) {
//         while ($row = $result->fetch_assoc()) {
//             $data[] = $row;
//         }
//     }

//     return $data;
// }



function getMainSubcategoryMaster_Credit($conn, $shortby = 'all', $shortbyvalue = '')
{
	// Base query
	$query = "SELECT categoryid, subcategoryid, subcategory FROM main_subcategory_master_credit WHERE (tedit IS NULL OR tedit = '')";

	// Apply filters
	if ($shortby === 'categoryid' && $shortbyvalue !== '') {
		$shortbyvalue = $conn->real_escape_string($shortbyvalue);
		$query .= " AND categoryid = '{$shortbyvalue}'";
	} elseif ($shortby === 'subcategoryid' && $shortbyvalue !== '') {
		$shortbyvalue = $conn->real_escape_string($shortbyvalue);
		$query .= " AND subcategoryid = '{$shortbyvalue}'";
	} elseif ($shortby === 'sno' && $shortbyvalue !== '') {
		$shortbyvalue = (int) $shortbyvalue;
		$query .= " AND sno = {$shortbyvalue}";
	}

	$result = $conn->query($query);

	$data = [];
	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}
	}

	return $data;
}


function credit_maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $subcategoryid)
{
	$qryp = $conn->query("SELECT distinct max(provided_code) as maxprovidedcode from purpose_code_master_credit where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and sub_categoryid='" . $subcategoryid . "' and ifnull(tedit,'')=''");
	if ($qryp->num_rows > 0) {
		$mprdrow = $qryp->fetch_assoc();
		return $mprdrow['maxprovidedcode'];
	}
}

function credit_maxprovidecode_frm_client($conn, $companyid, $subcompanyid, $projectid, $subcategoryid)
{
	$qryp = $conn->query("SELECT distinct max(providedcode) as maxprovidedcode from purpose_codemaster_client_credit where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and subcategoryid='" . $subcategoryid . "' and ifnull(tedit,'')=''");
	if ($qryp->num_rows > 0) {
		$mprdrow = $qryp->fetch_assoc();
		return $mprdrow['maxprovidedcode'];
	}
}

function newpurposecodegenrate($conn, $companyid, $subcompanyid, $projectid, $categoryid, $subcategoryid)
{
	$qrymsno = "select (count(sub_categoryid)+1) as maxcode  from purpose_code_master where cmp_id='" . $companyid . "' and subcmp_id='" . $subcompanyid . "' and projectid='" . $projectid . "' and categoryid='" . $categoryid . "' and sub_categoryid='" . $subcategoryid . "'";
	$restsno = $conn->query($qrymsno);
	if ($restsno->num_rows > 0) {
		$rowsno = $restsno->fetch_assoc();
		if (is_null($rowsno['maxcode'])) {
			$maxsno = 1;
		} else {
			$maxsno = $rowsno['maxcode'];
		}
	}
	$compaletpurposecode = $companyid . $subcompanyid . $projectid . $catid . str_replace(".", "", $subcatid) . $maxsno;
	return $compaletpurposecode;
}
function uploadimages_toweb($files, $subdir)
{
	$returnpath = "";
	$fileName = $files['name'];
	$fileTmpName = $files['tmp_name'];
	$fileError = $files['error'];
	$file_ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
	$destination = $subdir . "/" . generateUniqueFilename_web() . '.' . $file_ext;
	if ($fileError === 0) {
		if (move_uploaded_file($fileTmpName, $destination)) {
			$returnpath = $destination;
		}
	}
	return $returnpath;
}
function generateUniqueFilename_web()
{
	// Get the current time with microseconds
	$microtime = microtime(true);

	// Format the microtime to include milliseconds
	$milliseconds = sprintf("%03d", ($microtime - floor($microtime)) * 1000);

	// Generate a random unique identifier
	$uniqueId = bin2hex(random_bytes(4)); // 8-character random string

	// Create the filename using the current date, time, and random ID
	$uniqueFilename = date("Ymd_His") . $milliseconds . "_" . $uniqueId;

	return $uniqueFilename;
}



function fetchSingleValue($data, $key)
{
	foreach ($data as $row) {
		return $row[$key] ?? '';
	}
	return '';
}

/**
 * Format date to 'd-M-Y'
 */
function formatDate($date)
{
	return $date ? date_format(date_create($date), 'd-m-Y') : '';
}

function get11bsdata($conn, $companyid, $subcompanyid, $projectid)
{
	$prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
	$projectname = "";
	foreach ($prj as $ppjkey) {
		$projectname = $ppjkey['projectname'];
	}
	$dates = get_unauditeddaterange($conn, $companyid, $subcompanyid, $projectid);
	$startdate = $dates[0]["mindate"];
	if (!$startdate) {
		return "";
	}
	$enddate = $dates[0]["enddate"];
	// Step 1: Get unique account IDs for the project
	$accountIds = [];
	$accountIds = getaccountinformation($conn, $companyid, $subcompanyid, $projectid, "allaccountofproject", "");

	$subcategories = getwithdrawalsubcategoriesinfo($conn, $projectid);

	$details = array();
	$html = '<thead data-fromdate="' . $startdate . '" data-todate="' . date("Y-m-d") . '">
				<tr class="bg-light border-0">
					<th colspan="3" class="text-dark border-0 p-1" style="font-size:22px;">11-BS (Withdrawals)</th>
				</tr>
				<tr class="bg-light border-0">
					<th colspan="3" class="text-dark border-0 p-1" style="font-size:16px;">Project Name: ' . $projectname . '</th>
				</tr>
				<tr class="bg-light border-0">
					<th colspan="3" class="text-dark border-0 p-1" style="font-size:16px;">From Date ' . formatDate($startdate) . ' To ' . date("d-m-Y") . '</th>
				</tr>
	  			<tr class="bg-info">
	                <th class="bg-info" style="width:60px; text-align:center;">S.No</th>
	                <th class="bg-info" style="' . $editstyle . '">Category</th>';
	foreach ($accountIds as $accdetail) {
		$html .= '<th data-accountid="' . $accdetail["accountid"] . '" class="bg-info">' . $accdetail["bankname"] . '(' . $accdetail["accountno"] . ')</th>';
	}
	$html .= '</tr>
		</thead>
			<tbody>';
	$i = 0;
	$html .= '<tr class="opening" data-category="opening"><td>' . (++$i) . '</td><td>Opening Balance (As on ' . formatDate($startdate) . ')</td>';
	foreach ($accountIds as $accdetail) {
		$op = getopeningbal($conn, $accdetail['accountid'], $startdate);
		$details[$accdetail['accountid']]["opening"] = $op;
		$html .= '<td class="' . $accdetail['accountid'] . '">' . $op . '</td>';
	}
	$html .= '</tr>';
	$html .= '<tr class="deposit" data-category="deposit"><td>' . (++$i) . '</td><td>Total Amount Deposited</td>';
	foreach ($accountIds as $accdetail) {
		$dp = getdeposited($conn, $accdetail['accountid'], $startdate);
		$details[$accdetail['accountid']]["deposit"] = $dp;
		$html .= '<td class="' . $accdetail['accountid'] . '">' . $dp . '</td>';
	}
	$html .= '</tr>';
	foreach ($subcategories as $subcategory) {
		$html .= '<tr class="' . $subcategory["subcategoryid"] . '" data-category="' . $subcategory["sub_category"] . '"><td>' . (++$i) . '</td><td>' . $subcategory["sub_category"] . '</td>';

		foreach ($accountIds as $accdetail) {
			$ttl = gettotalamount($conn, $startdate, $accdetail['accountid'], $subcategory["subcategoryid"]);
			$details[$accdetail['accountid']]["withdrawal"] = ($details[$accdetail['accountid']]["withdrawal"]) + $ttl;
			$html .= '<td class="' . $accdetail['accountid'] . '">' . $ttl . '</td>';
		}
		$html .= '</tr>';
	}

	$html .= '<tr class="closing" data-category="closing"><td>' . (++$i) . '</td><td>Closing Balance (As on ' . formatDate($enddate) . ')</td>';
	foreach ($accountIds as $accdetail) {
		$html .= '<td class="' . $accdetail['accountid'] . '">' . getclosingbal($conn, $accdetail['accountid'], $enddate) . '</td>';
	}
	$html .= '</tr>';

	$html .= '<tr class="calclosing" data-category="calclosing"><td>' . (++$i) . '</td><td>Calculated Closing Balance</td>';
	foreach ($accountIds as $accdetail) {
		$html .= '<td class="' . $accdetail['accountid'] . '">' . ($details[$accdetail['accountid']]["opening"] + $details[$accdetail['accountid']]["deposit"] - $details[$accdetail['accountid']]["withdrawal"]) . '</td>';
	}
	$html .= '</tr>';

	$html .= '</tbody>';
	return $html;
}

function save11bsdata($conn, $companyid, $subcompanyid, $projectid, $fromDate, $toDate, $tbldata)
{
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn->begin_transaction();
	try {
		$dropqry = "DROP TABLE IF EXISTS 11bsw";
		$conn->query($dropqry);
		$createqry = "CREATE TABLE 11bsw (
	  		sno bigint(20) NOT NULL AUTO_INCREMENT,
	  		company_id bigint(20) DEFAULT NULL,
	 		subcompany_id bigint(20) DEFAULT NULL,
	  		project_id bigint(20) DEFAULT NULL,
	  		account_id bigint(20) DEFAULT NULL,
	  		from_date date DEFAULT NULL,
	  		to_date date DEFAULT NULL,
	  		opening_balance FLOAT DEFAULT 0,
	  		amount_deposited FLOAT DEFAULT 0,
	  		closing_balance FLOAT DEFAULT 0,";
		foreach ($tbldata[0]["amount_withdrawals"] as $category) {
			$createqry .= "`" . $category["catid"] . "` FLOAT DEFAULT 0,";
		}

		$createqry .= "PRIMARY KEY (`sno`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

		$conn->query($createqry);

		foreach ($tbldata as $account) {
			$columns = ["company_id", "subcompany_id", "project_id", "account_id", "from_date", "to_date", "opening_balance", "amount_deposited", "closing_balance"];
			$values = [$companyid, $subcompanyid, $projectid, $account['accountid'], $fromDate, $toDate, $account['openingbal'], $account['amount_deposited'], $account['closing_balance']];

			foreach ($account["amount_withdrawals"] as $withdrawal) {
				$columns[] = "`" . $withdrawal["catid"] . "`";
				$values[] = $withdrawal["amount"];
			}

			$columnsList = implode(", ", $columns);
			$placeholders = implode(", ", array_fill(0, count($values), "?"));

			$insertQuery = "INSERT INTO 11bsw ($columnsList) VALUES ($placeholders)";

			$stmt = $conn->prepare($insertQuery);
			$stmt->bind_param(str_repeat("s", count($values)), ...$values);
			$stmt->execute();
		}

		$conn->commit();
		echo "success";
	} catch (mysqli_sql_exception $e) {
		$conn->rollback();
		echo $e;
	}
}

function get11bsdatacredit($conn, $companyid, $subcompanyid, $projectid)
{
	$prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
	$projectname = "";
	foreach ($prj as $ppjkey) {
		$projectname = $ppjkey['projectname'];
	}
	$dates = get_unauditeddaterange($conn, $companyid, $subcompanyid, $projectid);
	$startdate = $dates[0]["mindate"];
	if (!$startdate) {
		return "";
	}
	$enddate = $dates[0]["enddate"];
	// Step 1: Get unique account IDs for the project
	$accountIds = [];
	$accountIds = getaccountinformation($conn, $companyid, $subcompanyid, $projectid, "allaccountofproject", "");

	$subcategories = getdepositsubcategoriesinfo($conn, $projectid);

	$details = array();
	$html = '<thead data-fromdate="' . $startdate . '" data-todate="' . date("Y-m-d") . '">
				<tr class="bg-light border-0">
					<th colspan="3" class="sticky-top text-dark border-0 p-1" style="font-size:22px;">11-BS (Deposits)</th>
				</tr>
				<tr class="bg-light border-0">
					<th colspan="3" class="sticky-top text-dark border-0 p-1" style="font-size:16px;">Project Name: ' . $projectname . '</th>
				</tr>
				<tr class="bg-light border-0">
					<th colspan="3" class="sticky-top text-dark border-0 p-1" style="font-size:16px;">From Date ' . formatDate($startdate) . ' To ' . date("d-m-Y") . '</th>
				</tr>
	  			<tr class="bg-info">
	                <th class="bg-info sticky-top" style="width:60px; text-align:center;">S.No</th>
	                <th class="bg-info sticky-top" style="' . $editstyle . '">Category</th>';
	foreach ($accountIds as $accdetail) {
		$html .= '<th data-accountid="' . $accdetail["accountid"] . '" class="bg-info sticky-top">' . $accdetail["bankname"] . '(' . $accdetail["accountno"] . ')</th>';
	}
	$html .= '</tr>
		</thead>
			<tbody>';
	$i = 0;
	$html .= '<tr class="opening" data-category="opening"><td>' . (++$i) . '</td><td>Opening Balance (As on ' . formatDate($startdate) . ')</td>';
	foreach ($accountIds as $accdetail) {
		$op = getopeningbal($conn, $accdetail['accountid'], $startdate);
		$details[$accdetail['accountid']]["opening"] = $op;
		$html .= '<td class="' . $accdetail['accountid'] . '">' . $op . '</td>';
	}
	$html .= '</tr>';
	$html .= '<tr class="withdraw" data-category="withdraw"><td>' . (++$i) . '</td><td>Total Amount withdrawn</td>';
	foreach ($accountIds as $accdetail) {
		$dp = getwithdrawn($conn, $accdetail['accountid'], $startdate);
		$details[$accdetail['accountid']]["withdrawal"] = $dp;
		$html .= '<td class="' . $accdetail['accountid'] . '">' . $dp . '</td>';
	}
	$html .= '</tr>';

	foreach ($subcategories as $subcategory) {
		$html .= '<tr class="' . $subcategory["subcategoryid"] . '" data-category="' . $subcategory["sub_category"] . '"><td>' . (++$i) . '</td><td>' . $subcategory["sub_category"] . '</td>';

		foreach ($accountIds as $accdetail) {
			$ttl = gettotaldepamount($conn, $startdate, $accdetail['accountid'], $subcategory["subcategoryid"]);
			$details[$accdetail['accountid']]["deposit"] = ($details[$accdetail['accountid']]["deposit"]) + $ttl;
			$html .= '<td class="' . $accdetail['accountid'] . '">' . $ttl . '</td>';
		}
		$html .= '</tr>';
	}

	$html .= '<tr class="closing" data-category="closing"><td>' . (++$i) . '</td><td>Closing Balance (As on ' . formatDate($enddate) . ')</td>';
	foreach ($accountIds as $accdetail) {
		$html .= '<td class="' . $accdetail['accountid'] . '">' . getclosingbal($conn, $accdetail['accountid'], $enddate) . '</td>';
	}
	$html .= '</tr>';

	$html .= '<tr class="calclosing" data-category="calclosing"><td>' . (++$i) . '</td><td>Calculated Closing Balance</td>';
	foreach ($accountIds as $accdetail) {
		$html .= '<td class="' . $accdetail['accountid'] . '">' . ($details[$accdetail['accountid']]["opening"] + $details[$accdetail['accountid']]["deposit"] - $details[$accdetail['accountid']]["withdrawal"]) . '</td>';
	}
	$html .= '</tr>';

	$html .= '</tbody>';
	return $html;
}

function save11bsdatacredit($conn, $companyid, $subcompanyid, $projectid, $fromDate, $toDate, $tbldata)
{
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$conn->begin_transaction();
	try {
		$dropqry = "DROP TABLE IF EXISTS 11bswcredit";
		$conn->query($dropqry);
		$createqry = "CREATE TABLE 11bswcredit (
	  		sno bigint(20) NOT NULL AUTO_INCREMENT,
	  		company_id bigint(20) DEFAULT NULL,
	 		subcompany_id bigint(20) DEFAULT NULL,
	  		project_id bigint(20) DEFAULT NULL,
	  		account_id bigint(20) DEFAULT NULL,
	  		from_date date DEFAULT NULL,
	  		to_date date DEFAULT NULL,
	  		opening_balance FLOAT DEFAULT 0,
	  		amount_withdrawn FLOAT DEFAULT 0,
	  		closing_balance FLOAT DEFAULT 0,";
		foreach ($tbldata[0]["amount_deposits"] as $category) {
			$createqry .= "`" . $category["catid"] . "` FLOAT DEFAULT 0,";
		}

		$createqry .= "PRIMARY KEY (`sno`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci";

		$conn->query($createqry);

		foreach ($tbldata as $account) {
			$columns = ["company_id", "subcompany_id", "project_id", "account_id", "from_date", "to_date", "opening_balance", "amount_withdrawn", "closing_balance"];
			$values = [$companyid, $subcompanyid, $projectid, $account['accountid'], $fromDate, $toDate, $account['openingbal'], $account['amount_withdrawn'], $account['closing_balance']];

			foreach ($account["amount_deposits"] as $deposit) {
				$columns[] = "`" . $deposit["catid"] . "`";
				$values[] = $deposit["amount"];
			}

			$columnsList = implode(", ", $columns);
			$placeholders = implode(", ", array_fill(0, count($values), "?"));

			$insertQuery = "INSERT INTO 11bswcredit ($columnsList) VALUES ($placeholders)";

			$stmt = $conn->prepare($insertQuery);
			$stmt->bind_param(str_repeat("s", count($values)), ...$values);
			$stmt->execute();
		}

		$conn->commit();
		echo "success";
	} catch (mysqli_sql_exception $e) {
		$conn->rollback();
		echo $e;
	}
}
function getopeningbal($conn, $accountid, $startdate)
{
	$opbal = 0;
	// echo "select round(ifnull((balance+debitamount-creditamount),0),2) as opbal from tbl_bankstatement where account_id='".$accountid."' and date(valuedate)='".$startdate."' limit 1";
	$opres = $conn->query("select round(ifnull((balance+debitamount-creditamount),0),2) as opbal from tbl_bankstatement where account_id='" . $accountid . "' and date(valuedate)='" . $startdate . "' limit 1");
	if ($opres->num_rows > 0) {
		$row = $opres->fetch_assoc();
		$opbal = $row["opbal"];
	}
	return $opbal;
}

function getdeposited($conn, $accountid, $startdate, $enddate)
{
	$dep = 0;
	$depres = $conn->query("select round(ifnull(sum(creditamount),0),2) as deposited from tbl_bankstatement where account_id='" . $accountid . "' and date(valuedate) between '" . $startdate . "' and date('" . $enddate . "')");
	if ($depres->num_rows > 0) {
		$row = $depres->fetch_assoc();
		$dep = $row["deposited"];
	}
	return $dep;
}

function getwithdrawn($conn, $accountid, $startdate, $enddate)
{
	$dep = 0;

	$depres = $conn->query("select round(ifnull(sum(debitamount),0),2) as withdrawn from tbl_bankstatement where account_id='" . $accountid . "' and (date(valuedate) between '" . $startdate . "' and date('" . $enddate . "'))");
	if ($depres->num_rows > 0) {
		$row = $depres->fetch_assoc();
		$dep = $row["withdrawn"];
	}
	return $dep;
}

function getclosingbal($conn, $accountid, $enddate)
{
	$clbal = 0;
	$clres = $conn->query("select balance from tbl_bankstatement where account_id='" . $accountid . "' and date(valuedate)='" . $enddate . "' order by sno desc limit 1");
	if ($clres->num_rows > 0) {
		$row = $clres->fetch_assoc();
		$clbal = $row["balance"];
	}
	return $clbal;
}

function gettotalamount($conn, $startdate, $accountid, $categoryid)
{
	$total = 0;
	$n4res = $conn->query("select ifnull(sum(round(b.amount,2)),0) as amount from  tbl_maintransaction_breakdown b join tbl_maintransaction m on (m.reference_no=b.reference_no) join purpose_code_master p on (p.purposecode=b.purpose_code) where m.payfromaccountid=" . $accountid . " and (date(m.value_date) between '" . $startdate . "' and date(now())) and m.reconciliation='Done' and p.sub_categoryid='" . $categoryid . "'");
	if ($n4res->num_rows > 0) {
		$row = $n4res->fetch_assoc();
		$total = $row["amount"];
	}
	return $total;
}

function gettotaldepamount($conn, $startdate, $accountid, $categoryid)
{
	$total = 0;
	$n4res = $conn->query("select ifnull(sum(round(b.amount,2)),0) as amount from  t2c_creditadvice b join purpose_code_master_credit p on (p.purposecode=b.purposecode) where b.accountid=" . $accountid . " and (date(b.bank_date) between '" . $startdate . "' and date(now())) and b.reconciliation='Complete' and p.categoryid='" . $categoryid . "'");
	if ($n4res->num_rows > 0) {
		$row = $n4res->fetch_assoc();
		$total = $row["amount"];
	}
	return $total;
}

function getdepositdataforhelpmakebook_missingdetails($conn, $companyid, $subcompanyid, $projectid)
{

	$sscont = "select ifnull(c.client_name,'') as payeename, d.sno, d.date, d.country_id, d.company_id, d.subcompany_id, d.project_id, d.accountname, d.accountno, d.account_id, d.reference_no, d.request_no, d.paymentmode, d.chequeno, d.bankname, ifnull(d.paytoid,'') as paytoid, d.payto, d.beneficiaryname, d.amount, d.tds, d.tds_amount, d.total_amount, d.paymenttype, d.categoryid, d.category, d.sub_categoryid, d.sub_category, d.purpose_code, d.provided_code, d.provided_purpose, d.purpose, d.description, d.remarks, d.tsno, d.banksno, d.status, d.source, d.loginid, d.fileinfo, d.audiofilepath, d.voiceinstid, d.filepath, d.filepath2, d.srm_emailsno, d.srm_attsno, d.closetobook_status, d.closetobook_bankkeyid, d.tedit, d.etdatetime, d.remove_doc, d.att_id FROM tbl_depositsinexcelsheet d left join client_master c on c.client_id =d.paytoid WHERE (d.tedit is null or d.tedit='') and d.company_id = '" . $companyid . "' and d.subcompany_id='" . $subcompanyid . "' and d.project_id='" . $projectid . "' and (d.total_amount >0) and (d.status is null or d.status='') and (d.closetobook_status is null or d.closetobook_status='')";

	$sscont_result = $conn->query($sscont);
	$expensescontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($exprow = $sscont_result->fetch_assoc()) {
			$expensescontent[] = array('payeename' => $exprow['payeename'], 'sno' => $exprow['sno'], 'date' => $exprow['date'], 'dispdate' => date('d-M-Y', strtotime($exprow['date'])), 'country_id' => $exprow['country_id'], 'company_id' => $exprow['company_id'], 'subcompany_id' => $exprow['subcompany_id'], 'project_id' => $exprow['project_id'], 'accountname' => $exprow['accountname'], 'accountno' => $exprow['accountno'], 'account_id' => $exprow['account_id'], 'reference_no' => $exprow['reference_no'], 'request_no' => $exprow['request_no'], 'paymentmode' => $exprow['paymentmode'], 'chequeno' => $exprow['chequeno'], 'bankname' => $exprow['bankname'], 'paytoid' => $exprow['paytoid'], 'payto' => $exprow['payto'], 'beneficiaryname' => $exprow['beneficiaryname'], 'amount' => $exprow['amount'], 'tds' => $exprow['tds'], 'tds_amount' => $exprow['tds_amount'], 'total_amount' => $exprow['total_amount'], 'paymenttype' => $exprow['paymenttype'], 'categoryid' => $exprow['categoryid'], 'category' => $exprow['category'], 'sub_categoryid' => $exprow['sub_categoryid'], 'sub_category' => $exprow['sub_category'], 'purpose_code' => $exprow['purpose_code'], 'provided_code' => $exprow['provided_code'], 'provided_purpose' => $exprow['provided_purpose'], 'purpose' => $exprow['purpose'], 'description' => $exprow['description'], 'remarks' => $exprow['remarks'], 'tsno' => $exprow['tsno'], 'banksno' => $exprow['banksno'], 'status' => $exprow['status'], 'source' => $exprow['source'], 'loginid' => $exprow['loginid'], 'fileinfo' => $exprow['fileinfo'], 'audiofilepath' => $exprow['audiofilepath'], 'audiofilepath_digitized' => $exprow['audiofilepath_digitized'], 'filepath_digitized' => $exprow['filepath_digitized'], 'voiceinstid' => $exprow['voiceinstid'], 'filepath' => $exprow['filepath'], 'filepath2' => $exprow['filepath2'], 'srm_emailsno' => $exprow['srm_emailsno'], 'srm_attsno' => $exprow['srm_attsno'], 'closetobook_status' => $exprow['closetobook_status'], 'closetobook_bankkeyid' => $exprow['closetobook_bankkeyid'], 'tedit' => $exprow['tedit'], 'etdatetime' => $exprow['etdatetime'], 'remove_doc' => $exprow['remove_doc'], 'att_id' => $exprow['att_id']);
		}
	}
	return $expensescontent;
}
function getcustomisedcomparison1($conn, $companyid, $subcompanyid, $projectid)
{
	$sscont = "select ifnull(c.client_name,'') as payeename, d.invoiceno,  d.id as dbsno, d.likelypayto as likelypayto, d.paytoid, d.paymentdate, d.amoutwithoutax, d.taxamt, d.totalamt,   d.status, d.depositrefno,   d.categoryid, d.category, d.sub_categoryid, d.sub_category, d.purpose_code, d.provided_code, d.provided_purpose, d.purpose  FROM tbl_overlap_depositsxlsheet d left join client_master c on c.client_id =d.paytoid WHERE (d.tedit is null or d.tedit='') and d.companyid = '" . $companyid . "' and d.subcompanyid='" . $subcompanyid . "' and d.projectid='" . $projectid . "' and (d.totalamt >0) and (d.status is null or d.status='') ";
	$sscont_result = $conn->query($sscont);
	$expensescontent = array();
	if ($sscont_result->num_rows > 0) {
		while ($exprow = $sscont_result->fetch_assoc()) {
			$expensescontent[] = array('payeename' => $exprow['payeename'], 'payto' => $exprow['likelypayto'], 'dbsno' => $exprow['dbsno'], 'date' => $exprow['paymentdate'], 'dispdate' => date('d-M-Y', strtotime($exprow['paymentdate'])), 'paytoid' => $exprow['paytoid'], 'amoutwithoutax' => $exprow['amoutwithoutax'], 'taxamt' => $exprow['taxamt'], 'totalamt' => $exprow['totalamt'], 'depositrefno' => $exprow['depositrefno'], 'purpose_code' => $exprow['purpose_code'], 'provided_code' => $exprow['provided_code'], 'provided_purpose' => $exprow['provided_purpose'], 'invoiceno' => $exprow['invoiceno']);

		}
	}
	return $expensescontent;
}

function getmaxaccountid_frmaccountmaster($conn)
{
	$qurytemp = $conn->query("select (count(sno) + 1) as maxcount from  tbl_master_account");
	if ($qurytemp->num_rows > 0) {
		$rowt = $qurytemp->fetch_assoc();
		$msxid = $rowt['maxcount'];
	}
	return $msxid;
}

function deductedIncomeTax_monthlist($conn, $companyid, $subcompanyid, $projectid, $financialyear)
{
	$condition = "";
	if ($financialyear) {
		$finexp = explode("|", $financialyear);
		$datefrom = $finexp[0];
		$dateot = $finexp[1];
		$condition = "and bookdate>='" . $datefrom . "' and YEAR(bookdate)<='" . $dateot . "'";
	}
	$tdq = "SELECT DISTINCT DATE_FORMAT(bookdate, '%M-%Y') AS displaymonth, DATE_FORMAT(bookdate, '%Y-%m-01') AS firstdate, DATE_FORMAT(DATE_SUB(DATE_ADD(bookdate, INTERVAL 1 MONTH), INTERVAL DAY(DATE_ADD(bookdate, INTERVAL 1 MONTH)) DAY), '%Y-%m-%d') AS lastdate FROM itax_account_summery WHERE (tedit IS NULL OR tedit = '') and  companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' " . $condition . " ";
	$tdqr = $conn->query($tdq) or die($conn->error);
	$monthlist = array();
	if ($tdqr->num_rows > 0) {
		while ($trow = $tdqr->fetch_assoc()) {
			$monthlist[] = array('firstdate' => $trow['firstdate'], 'lastdate' => $trow['lastdate'], 'displaymonth' => $trow['displaymonth']);
		}
	}
	return $monthlist;
}

function getItaxpaytolist($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$ssq = $conn->query("SELECT distinct paytoid, paytoname FROM itax_account_summery WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' group by paytoid");
	} else if ($shortby == "pendingdataforrequest") {
		$ssq = $conn->query("SELECT distinct paytoid, paytoname FROM itax_account_summery WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (itaxstatus is null or itaxstatus='') group by paytoid");
	}
	$itaxdetails = [];
	if ($ssq->num_rows > 0) {
		while ($itrow = $ssq->fetch_assoc()) {
			$itaxdetails[] = $itrow;
		}
	}
	return $itaxdetails;
}

function getItaxdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "allpendingforrequest") {
		$ssq = $conn->query("SELECT sno, bookdate, countryid, companyid, subcompanyid, projectid, subprojectid, paytoid, paytoname, request_no, reference_no, from_accountid, from_accountno, from_bankname, itaxamount, paidamount, itaxstatus, itax_challan_no, itax_challan_date, description FROM itax_account_summery WHERE (tedit is null or tedit='') and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and (itaxstatus is null or itaxstatus='')");
	}
	$itaxdetails = [];
	if ($ssq->num_rows > 0) {
		while ($itrow = $ssq->fetch_assoc()) {
			$itaxdetails[] = $itrow;
		}
	}
	return $itaxdetails;
}

function updateInternalTransferAccountData($conn)
{
	$ssq = $conn->query("SELECT m.sno,m.companyid,m.subcompanyid,m.projectid,m.subprojectid,m.approvedby,m.request_no,m.reference_no,m.transactionno,m.bookdate,m.transaction_date,m.transfered_amount,m.payto,m.paytoid,m.payfromaccountid,m.paymenttype,b.beneficiary_name,b.amount,b.purpose_category,b.purpose_categoryid,b.purpose_subcategory,b.purpose_subcategoryid,b.purpose,b.purpose_code,b.description,b.documentation_path FROM tbl_maintransaction m join tbl_maintransaction_breakdown b on m.companyid=b.companyid and m.subcompanyid=b.subcompanyid and m.projectid=b.projectid and m.reference_no=b.reference_no where (m.tedit is null or m.tedit='') and (b.tedit is null or b.tedit='') and m.transactionno<>''and  m.paytoid<>'' and (m.is_itinaccount is null or m.is_itinaccount='') and lcase(m.paymenttype) in ('cash payment', 'cash', 'credit card payment', 'credit card','part payment','partial payment','partial','imprest payment','imprest','loan','loan payment')");
	if ($ssq->num_rows > 0) {
		$ss = 1;
		while ($uprow = $ssq->fetch_assoc()) {
			$ccq = $conn->query("SELECT accountid,paytoid,bankid,accountname,accountno FROM tbl_master_account where cmp_id='" . $uprow['companyid'] . "' and subcmp_id='" . $uprow['subcompanyid'] . "' and projectid='" . $uprow['projectid'] . "' and paytoid='" . $uprow['paytoid'] . "' and accountid<>'' and paytoid<>''");
			$toaccountid = "";
			if ($ccq->num_rows > 0) {
				$accrow = $ccq->fetch_assoc();
				$toaccountid = $accrow['accountid'];
				$toaccountno = $accrow['accountno'];
				if ($uprow['purpose_categoryid'] == "2") {
					// imprest_account
					$impq = $conn->query("SELECT sno From tbl_loanaccount_summary where companyid='" . $uprow['companyid'] . "' and subcompanyid='" . $uprow['subcompanyid'] . "' and projectid='" . $uprow['projectid'] . "' and referenceno='" . $uprow['reference_no'] . "'");
					if ($impq->num_rows > 0) {
						$conn->query("update tbl_maintransaction SET is_itinaccount='loan_account' WHERE sno='" . $uprow['sno'] . "'");
					} else {
						$conn->query("Insert into tbl_loanaccount_summary(idate,loanid,companyid,subcompanyid,projectid,from_accountid,to_accountid,to_accountno,requestno,referenceno,loan_payto,loan_amount,loan_returnamount,purpose,purposecode,description,document_path,transactionno,transactiondate,addedby) values('" . $uprow['transaction_date'] . "','" . $uprow['paytoid'] . "','" . $uprow['companyid'] . "', '" . $uprow['subcompanyid'] . "', '" . $uprow['projectid'] . "','" . $uprow['payfromaccountid'] . "','" . $toaccountid . "','" . $toaccountno . "','" . $uprow['request_no'] . "','" . $uprow['reference_no'] . "','" . addslashes($uprow['payto']) . "','" . $uprow['transfered_amount'] . "','0','" . addslashes($uprow['purpose']) . "','" . $uprow['purpose_code'] . "','" . addslashes($uprow['description']) . "','" . addslashes($uprow['documentation_path']) . "','" . $uprow['transactionno'] . "','" . $uprow['transaction_date'] . "','" . $uprow['approvedby'] . "')");
						if (mysqli_affected_rows($conn) > 0) {
							$conn->query("update tbl_maintransaction SET is_itinaccount='loan_account' WHERE sno='" . $uprow['sno'] . "'");
						}
					}

				} else if ($uprow['purpose_categoryid'] == "3") {
					// imprest_account
					$impq = $conn->query("SELECT sno From imprest_account where companyid='" . $uprow['companyid'] . "' and subcompanyid='" . $uprow['subcompanyid'] . "' and projectid='" . $uprow['projectid'] . "' and reference_no='" . $uprow['reference_no'] . "'");
					if ($impq->num_rows > 0) {
						$conn->query("update tbl_maintransaction SET is_itinaccount='imprest_account' WHERE sno='" . $uprow['sno'] . "'");
					} else {
						$conn->query("Insert into imprest_account(companyid,subcompanyid,projectid,from_accountid,reference_no,paytoid,imprest_payto,imprest_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid,idate) values('" . $uprow['companyid'] . "', '" . $uprow['subcompanyid'] . "', '" . $uprow['projectid'] . "','" . $uprow['payfromaccountid'] . "','" . $uprow['reference_no'] . "','" . $uprow['paytoid'] . "','" . addslashes($uprow['payto']) . "','" . $uprow['transfered_amount'] . "','0','0','" . $uprow['purpose_categoryid'] . "','" . addslashes($uprow['purpose_category']) . "','" . $uprow['purpose_subcategoryid'] . "','" . addslashes($uprow['purpose_subcategory']) . "','" . addslashes($uprow['purpose']) . "','" . $uprow['purpose_code'] . "','" . addslashes($uprow['description']) . "','" . $toaccountid . "','" . $uprow['transaction_date'] . "')");
						if (mysqli_affected_rows($conn) > 0) {
							$conn->query("update tbl_maintransaction SET is_itinaccount='imprest_account' WHERE sno='" . $uprow['sno'] . "'");
						}
					}

				} else if ($uprow['purpose_categoryid'] == "4") {
					// partial_account
					$prq = $conn->query("SELECT sno From partial_account where companyid='" . $uprow['companyid'] . "' and subcompanyid='" . $uprow['subcompanyid'] . "' and projectid='" . $uprow['projectid'] . "' and reference_no='" . $uprow['reference_no'] . "'");
					if ($prq->num_rows > 0) {
						$conn->query("update tbl_maintransaction SET is_itinaccount='partial_account' WHERE sno='" . $uprow['sno'] . "'");
					} else {
						$conn->query("Insert into partial_account(companyid,subcompanyid,projectid,from_accountid,reference_no,paytoid,imprest_payto,imprest_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid,idate) values('" . $uprow['companyid'] . "', '" . $uprow['subcompanyid'] . "', '" . $uprow['projectid'] . "','" . $uprow['payfromaccountid'] . "','" . $uprow['reference_no'] . "','" . $uprow['paytoid'] . "','" . addslashes($uprow['payto']) . "','" . $uprow['transfered_amount'] . "','0','0','" . $uprow['purpose_categoryid'] . "','" . addslashes($uprow['purpose_category']) . "','" . $uprow['purpose_subcategoryid'] . "','" . addslashes($uprow['purpose_subcategory']) . "','" . addslashes($uprow['purpose']) . "','" . $uprow['purpose_code'] . "','" . addslashes($uprow['description']) . "','" . $toaccountid . "','" . $uprow['transaction_date'] . "')");
						if (mysqli_affected_rows($conn) > 0) {
							$conn->query("update tbl_maintransaction SET is_itinaccount='partial_account' WHERE sno='" . $uprow['sno'] . "'");
						}
					}

				} else if ($uprow['purpose_categoryid'] == "5") {
					// Fixed Deposits ( Savings) 
				} else if ($uprow['purpose_categoryid'] == "6") {
					// cash_account
					$cshq = $conn->query("SELECT sno From cash_account where companyid='" . $uprow['companyid'] . "' and subcompanyid='" . $uprow['subcompanyid'] . "' and projectid='" . $uprow['projectid'] . "' and reference_no='" . $uprow['reference_no'] . "'");
					if ($cshq->num_rows > 0) {
						$conn->query("update tbl_maintransaction SET is_itinaccount='cash_account' WHERE sno='" . $uprow['sno'] . "'");
					} else {
						$conn->query("Insert into cash_account(companyid,subcompanyid,projectid,from_accountid,reference_no,paytoid,cash_payto,cash_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid,idate) values('" . $uprow['companyid'] . "', '" . $uprow['subcompanyid'] . "', '" . $uprow['projectid'] . "','" . $uprow['payfromaccountid'] . "','" . $uprow['reference_no'] . "','" . $uprow['paytoid'] . "','" . addslashes($uprow['payto']) . "','" . $uprow['transfered_amount'] . "','0','0','" . $uprow['purpose_categoryid'] . "','" . addslashes($uprow['purpose_category']) . "','" . $uprow['purpose_subcategoryid'] . "','" . addslashes($uprow['purpose_subcategory']) . "','" . addslashes($uprow['purpose']) . "','" . $uprow['purpose_code'] . "','" . addslashes($uprow['description']) . "','" . $toaccountid . "','" . $uprow['transaction_date'] . "')");
						if (mysqli_affected_rows($conn) > 0) {
							$conn->query("update tbl_maintransaction SET is_itinaccount='cash_account' WHERE sno='" . $uprow['sno'] . "'");
						}
					}
				} else if ($uprow['purpose_categoryid'] == "8") {
					// creditcard_account
					$ccdtq = $conn->query("SELECT sno From creditcard_account where companyid='" . $uprow['companyid'] . "' and subcompanyid='" . $uprow['subcompanyid'] . "' and projectid='" . $uprow['projectid'] . "' and reference_no='" . $uprow['reference_no'] . "'");
					if ($ccdtq->num_rows > 0) {
						$conn->query("update tbl_maintransaction SET is_itinaccount='creditcard_account' WHERE sno='" . $uprow['sno'] . "'");
					} else {
						$conn->query("Insert into creditcard_account(companyid,subcompanyid,projectid,from_accountid,reference_no,username,creditamount,debitamount,adjustedamount,purposecategoryid,purposecategory,purposesubcategoryid,purposesubcategory,purpose,purposecode,description,userid,idate) values('" . $uprow['companyid'] . "', '" . $uprow['subcompanyid'] . "', '" . $uprow['projectid'] . "','" . $uprow['payfromaccountid'] . "','" . $uprow['reference_no'] . "','" . addslashes($uprow['payto']) . "','" . $uprow['transfered_amount'] . "','0','0','" . $uprow['purpose_categoryid'] . "','" . addslashes($uprow['purpose_category']) . "','" . $uprow['purpose_subcategoryid'] . "','" . addslashes($uprow['purpose_subcategory']) . "','" . addslashes($uprow['purpose']) . "','" . $uprow['purpose_code'] . "','" . addslashes($uprow['description']) . "','" . $toaccountid . "','" . $uprow['transaction_date'] . "')");
						if (mysqli_affected_rows($conn) > 0) {
							$conn->query("update tbl_maintransaction SET is_itinaccount='creditcard_account' WHERE sno='" . $uprow['sno'] . "'");
						}
					}
				}
			}
			$ss++;
		}
	}
}

function updateEmployeeAdvancePaymentData($hrmconn, $conn)
{
	try {
		// 1️⃣ Fetch pending advances from HRM database
		$fetchAdvanceSQL = "
            SELECT AdvanceID, ProjectID, EmployeeID, Purpose, request_no, reference_no, Status
            FROM hrm_EmployeeAdvance
            WHERE (Status IS NULL OR Status = '' OR Status = 'Pending')
        ";
		$advanceResult = $hrmconn->query($fetchAdvanceSQL);

		if (!$advanceResult || $advanceResult->num_rows === 0) {
			return ["status" => "info", "message" => "No pending advance records found."];
		}

		$insertedCount = 0;
		$duplicateCount = 0;

		// 2️⃣ Loop through all advances
		while ($adrow = $advanceResult->fetch_assoc()) {
			$requestNo = strtolower(trim($adrow['request_no']));
			$projectid = $adrow['ProjectID'];

			// Fetch related transaction details from accounting DB
			$fetchTxnSQL = "
                SELECT m.reference_no, m.transactionno, m.bookdate, m.transaction_date, 
                       m.transfered_amount, m.transaction_type, b.description 
                FROM tbl_maintransaction m 
                JOIN tbl_maintransaction_breakdown b 
                    ON m.projectid = b.projectid 
                    AND m.reference_no = b.reference_no 
                    AND m.request_no = b.request_no 
                WHERE (m.tedit IS NULL OR m.tedit = '') 
                  AND (b.tedit IS NULL OR b.tedit = '') 
                  AND m.transactionno <> '' 
                  AND m.reference_no <> '' 
                  AND m.paytoid <> '' 
                  AND m.projectid = ? 
                  AND LOWER(m.request_no) = ?
            ";

			$txnStmt = $conn->prepare($fetchTxnSQL);
			$txnStmt->bind_param("ss", $projectid, $requestNo);
			$txnStmt->execute();
			$txnResult = $txnStmt->get_result();

			if ($txnResult && $txnResult->num_rows > 0) {
				// 3️⃣ Process each transaction
				while ($uprow = $txnResult->fetch_assoc()) {
					$AdvID = $adrow['AdvanceID'];
					$ProjectID = $adrow['ProjectID'];
					$EmployeeID = $adrow['EmployeeID'];
					$TransactionDate = $uprow['transaction_date'];
					$DebitAmount = 0.00;
					$CreditAmount = $uprow['transfered_amount'];
					$ReferenceNo = $uprow['reference_no'];
					$Description = $uprow['description'];
					$TransactionType = 'Credit';
					$PaymentType = $uprow['transaction_type'];
					$TransactionNo = $uprow['transactionno'];
					$MonthYearSalary = date('Y-m');

					// 3️⃣.1 Check if this transaction already exists
					$checkSQL = "
                        SELECT 1 FROM hrm_EmployeeAdvanceSummary
                        WHERE AdvID = ? AND ReferenceNo = ? AND TransactionNo = ?
                    ";
					$checkStmt = $hrmconn->prepare($checkSQL);
					$checkStmt->bind_param("iss", $AdvID, $ReferenceNo, $TransactionNo);
					$checkStmt->execute();
					$checkStmt->store_result();

					if ($checkStmt->num_rows > 0) {
						$duplicateCount++;
						$checkStmt->close();
						continue; // Skip duplicate entry
					}
					$checkStmt->close();

					// 3️⃣.2 Insert new record
					$insertSQL = "
                        INSERT INTO hrm_EmployeeAdvanceSummary
                        (AdvID, projectid, EmployeeID, TransactionDate, DebitAmount, CreditAmount, ReferenceNo, Description, TransactionType, PaymentType, TransactionNo, MonthYearSalary)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
					$insertStmt = $hrmconn->prepare($insertSQL);
					if (!$insertStmt) {
						throw new Exception("Insert prepare failed: " . $hrmconn->error);
					}

					$insertStmt->bind_param(
						"isissdssssss",
						$AdvID,
						$ProjectID,
						$EmployeeID,
						$TransactionDate,
						$DebitAmount,
						$CreditAmount,
						$ReferenceNo,
						$Description,
						$TransactionType,
						$PaymentType,
						$TransactionNo,
						$MonthYearSalary
					);

					if ($insertStmt->execute()) {
						$insertedCount++;

						// 4️⃣ Mark advance as approved
						$updateSQL = "UPDATE hrm_EmployeeAdvance SET Status='Approved', reference_no=? WHERE AdvanceID = ?";
						$updateStmt = $hrmconn->prepare($updateSQL);
						$updateStmt->bind_param("si", $ReferenceNo, $AdvID);
						$updateStmt->execute();
						$updateStmt->close();
					}

					$insertStmt->close();
				}
			}
			$txnStmt->close();
		}

		$msg = "Advance payment data updated successfully.";
		if ($duplicateCount > 0) {
			$msg .= " ($duplicateCount duplicate record(s) skipped.)";
		}

		return ["status" => "success", "message" => $msg, "inserted" => $insertedCount, "duplicates" => $duplicateCount];
	} catch (Exception $e) {
		return ["status" => "error", "message" => $e->getMessage()];
	}
}


function billtransactionsdetails($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	// Base query
	$queryBase = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, billid, typeofbillid, locationid, billdurationid, purposecode, paytoid, beneficiaryname, billamount, billno, billingdate, billduedate, billdescription, uploadreferencefiles, referenceno, paidstatus, paidremark, transactiondate, transactionno, transferedamount, responsablepursonid, requestedbyid FROM strs_bill_transactions WHERE (tedit IS NULL OR tedit = '') AND companyid = ? AND subcompanyid = ? AND projectid = ?";
	// Conditional logic
	$conditions = "";
	$params = [$companyid, $subcompanyid, $projectid];
	$types = "sss";
	if ($shortby === "pendingforrequest") {
		$conditions = " AND (referenceno IS NULL OR referenceno = '')";
	} elseif ($shortby === "historywithbillid") {
		$conditions = " AND billid = ? AND billamount<>'' AND billno<>''";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingbillwithbillid") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '') AND billid = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingpaymenttransferwithbillid") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '') AND referenceno<>'' AND billid = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingbillpayment") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '')";
	} elseif ($shortby === "onlypendingforpayment") {
		$conditions = " AND referenceno <> '' AND (paidstatus IS NULL OR paidstatus = '')";
	} elseif ($shortby === "sno") {
		$conditions = " AND sno = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "referenceno") {
		$conditions = " AND referenceno = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	}
	$finalQuery = $queryBase . $conditions . " order by YEAR(billingdate) DESC, MONTH(billingdate) DESC, DAY(billingdate) DESC";
	// Prepare statement
	$stmt = $conn->prepare($finalQuery);
	if (!$stmt) {
		die("Failed to prepare statement: " . $conn->error);
	}
	// Bind parameters
	$stmt->bind_param($types, ...$params);
	// Execute query
	$stmt->execute();
	$result = $stmt->get_result();
	$billtranslist = [];
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$billtranslist[] = $row;
		}
	}

	// Close statement and return results
	$stmt->close();
	return $billtranslist;
}

function billtransactionshistorywithdate($conn, $companyid, $subcompanyid, $projectid, $datefrom, $dateto, $shortby, $shortbyvalue)
{
	// Base query
	$queryBase = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, billid, typeofbillid, locationid, billdurationid, purposecode, paytoid, beneficiaryname, billamount, billno, billingdate, billduedate, billdescription, uploadreferencefiles, referenceno, paidstatus, paidremark, transactiondate, transactionno, transferedamount, responsablepursonid, requestedbyid FROM strs_bill_transactions WHERE (tedit IS NULL OR tedit = '') and date(billingdate)>='" . $datefrom . "' and date(billingdate)<='" . $dateto . "' AND companyid = ? AND subcompanyid = ? AND projectid = ?";
	// Conditional logic
	$conditions = "";
	$params = [$companyid, $subcompanyid, $projectid];
	$types = "sss";
	if ($shortby === "pendingforrequest") {
		$conditions = " AND (referenceno IS NULL OR referenceno = '')";
	} elseif ($shortby === "historywithbillid") {
		$conditions = " AND billid = ? AND billamount<>'' AND billno<>''";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingbillwithbillid") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '') AND billid = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingpaymenttransferwithbillid") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '') AND referenceno<>'' AND billid = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingbillpayment") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '')";
	} elseif ($shortby === "onlypendingforpayment") {
		$conditions = " AND referenceno <> '' AND (paidstatus IS NULL OR paidstatus = '')";
	} elseif ($shortby === "sno") {
		$conditions = " AND sno = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "referenceno") {
		$conditions = " AND referenceno = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	}
	$finalQuery = $queryBase . $conditions . " order by YEAR(billingdate) DESC, MONTH(billingdate) DESC, DAY(billingdate) DESC";
	// Prepare statement
	$stmt = $conn->prepare($finalQuery);
	if (!$stmt) {
		die("Failed to prepare statement: " . $conn->error);
	}
	// Bind parameters
	$stmt->bind_param($types, ...$params);
	// Execute query
	$stmt->execute();
	$result = $stmt->get_result();
	$billtranslist = [];
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$billtranslist[] = $row;
		}
	}

	// Close statement and return results
	$stmt->close();
	return $billtranslist;
}

function getFinancialYearList_FromUtilistBill($conn, $companyid, $subcompanyid, $projectid)
{
	$years = [];

	// Get all distinct billing dates or transaction dates
	$query = "SELECT DISTINCT billingdate FROM strs_bill_transactions WHERE billingdate IS NOT NULL ORDER BY billingdate ASC";
	$result = $conn->query($query);

	if ($result && $result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$date = $row['billingdate'];

			// Extract year and month
			$year = date('Y', strtotime($date));
			$month = date('m', strtotime($date));

			// Calculate financial year based on Indian FY (April–March)
			if ((int) $month < 4) {
				$fy = ($year - 1) . '-' . $year;
			} else {
				$fy = $year . '-' . ($year + 1);
			}

			if (!in_array($fy, $years)) {
				$years[] = $fy;
			}
		}
	}

	return $years;
}


function updatepaidstatus_billsInTransactions($conn, $companyid, $subcompanyid, $projectid)
{
	// Base query to fetch bill transactions
	$queryBase = "SELECT sno, referenceno FROM strs_bill_transactions 
                  WHERE (tedit IS NULL OR tedit = '') 
                  AND referenceno <> '' 
                  AND (paidstatus IS NULL OR paidstatus = '') 
                  AND companyid = ? 
                  AND subcompanyid = ? 
                  AND projectid = ?";
	$params = [$companyid, $subcompanyid, $projectid];
	$types = "sss";
	// Prepare the base statement
	$stmt = $conn->prepare($queryBase);
	if (!$stmt) {
		die("Failed to prepare statement: " . $conn->error);
	}
	// Bind parameters and execute
	$stmt->bind_param($types, ...$params);
	$stmt->execute();
	$result = $stmt->get_result();
	// Iterate through results
	while ($row = $result->fetch_assoc()) {
		// Query to check transactions in tbl_maintransaction
		$querySub = "SELECT book_amount, ifnull(transfered_amount,book_amount) as transfered_amount, transactionno, transaction_date FROM tbl_maintransaction 
                     WHERE (tedit IS NULL OR tedit = '') AND transactionno<>''
                     AND companyid = ? 
                     AND subcompanyid = ? 
                     AND projectid = ? 
                     AND request_no = ?";
		$paramsSub = [$companyid, $subcompanyid, $projectid, $row['referenceno']];
		$typesSub = "ssss";
		// Prepare the subquery
		$stmtSub = $conn->prepare($querySub);
		if (!$stmtSub) {
			die("Failed to prepare statement: " . $conn->error);
		}
		// Bind parameters and execute
		$stmtSub->bind_param($typesSub, ...$paramsSub);
		$stmtSub->execute();
		$resultSub = $stmtSub->get_result();
		// Check if a matching transaction exists
		if ($resultSub->num_rows > 0) {
			$transaction = $resultSub->fetch_assoc();
			$paidStatus = "Paid with Transaction no " . $transaction['transactionno'] . " on date " . $transaction['transaction_date'];
			// Update the paidstatus in strs_bill_transactions
			$updateQuery = "UPDATE strs_bill_transactions 
                            SET paidstatus = ?, billamount = ?, transactionno = ?, transactiondate = ?, transferedamount = ? 
                            WHERE sno = ?";
			$updateStmt = $conn->prepare($updateQuery);
			if (!$updateStmt) {
				die("Failed to prepare update statement: " . $conn->error);
			}
			$updateStmt->bind_param("sssss", $paidStatus, $transaction['transfered_amount'], $transaction['transactionno'], $transaction['transaction_date'], $transaction['transfered_amount'], $row['sno']);
			$updateStmt->execute();
			$updateStmt->close();
		}
		// Close subquery statement
		$stmtSub->close();
	}
	// Close base statement
	$stmt->close();
}

function billmasteruniversal_bychatur($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billdurationdays, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM strs_billmaster_universal WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "onlypending") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billdurationdays, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM strs_billmaster_universal WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "id") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billdurationdays, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM strs_billmaster_universal WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and id='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billdurationdays, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM strs_billmaster_universal WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$bllmqr = $conn->query($bllmq);
	$billmasterlist = array();
	if ($bllmqr->num_rows > 0) {
		while ($bmrow = $bllmqr->fetch_assoc()) {
			$reffilepath = "";
			if (file_exists("../" . $bmrow['uploadreferencefiles'])) {
				$reffilepath = $bmrow['uploadreferencefiles'];
			}
			$billmasterlist[] = array('sno' => $bmrow['sno'], 'companyid' => $bmrow['companyid'], 'subcompanyid' => $bmrow['subcompanyid'], 'projectid' => $bmrow['projectid'], 'id' => $bmrow['id'], 'typeofbill' => $bmrow['typeofbill'], 'typeofbillid' => $bmrow['typeofbill'], 'subproject' => $bmrow['subproject'], 'identifierlocation' => $bmrow['identifierlocation'], 'purposecode' => $bmrow['purposecode'], 'responsablepursonname' => $bmrow['responsablepursonname'], 'billdescription' => $bmrow['billdescription'], 'billduration' => $bmrow['billduration'], 'billdurationdays' => $bmrow['billdurationdays'], 'billduedate' => $bmrow['billduedate'], 'paytoid' => $bmrow['paytoid'], 'beneficiaryname' => $bmrow['beneficiaryname'], 'nextbilldate' => $bmrow['nextbilldate'], 'uploadreferencefiles' => $reffilepath, 'lastreferenceno' => $bmrow['last_reference_no'], 'lastpaidstatus' => $bmrow['last_paidstatus']);
		}
	}
	return $billmasterlist;
}

function billdurationmaster_bychatur($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$bldq = "SELECT sno, id, durationname, durationdays FROM strs_durationmaster_universal WHERE ifnull(tedit,'')=''";
	} else if ($shortby == "id") {
		$bldq = "SELECT sno, id, durationname, durationdays FROM strs_durationmaster_universal WHERE ifnull(tedit,'')='' and id='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$bldq = "SELECT sno, id, durationname, durationdays FROM strs_durationmaster_universal WHERE ifnull(tedit,'')='' and sno='" . $shortbyvalue . "'";
	}
	$bldmqr = $conn->query($bldq);
	$billdurationlist = array();
	if ($bldmqr->num_rows > 0) {
		while ($bdrow = $bldmqr->fetch_assoc()) {
			$billdurationlist[] = array('sno' => $bdrow['sno'], 'id' => $bdrow['id'], 'durationname' => $bdrow['durationname'], 'durationdays' => $bdrow['durationdays']);
		}
	}
	return $billdurationlist;
}

function identificationlocationmaster_bychatur($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$idlq = "SELECT sno, companyid, subcompanyid, projectid, id, departmentname, locationname FROM strs_idntlocation_master WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "id") {
		$idlq = "SELECT sno, companyid, subcompanyid, projectid, id, departmentname, locationname FROM strs_idntlocation_master WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and id='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$idlq = "SELECT sno, companyid, subcompanyid, projectid, id, departmentname, locationname FROM strs_idntlocation_master WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$idlqr = $conn->query($idlq);
	$locationlist = array();
	if ($idlqr->num_rows > 0) {
		while ($idlrow = $idlqr->fetch_assoc()) {
			$locationwithdepart = "";
			if ($idlrow['departmentname']) {
				$locationwithdepart = $idlrow['departmentname'] . '-' . $idlrow['locationname'];
			} else {
				$locationwithdepart = $idlrow['locationname'];
			}
			$locationlist[] = array('sno' => $idlrow['sno'], 'id' => $idlrow['id'], 'departmentname' => $idlrow['departmentname'], 'locationname' => $idlrow['locationname'], 'locationwithdepart' => $locationwithdepart);
		}
	}
	return $locationlist;
}

function billtypemasterlist_bychatur($conn, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$bltyq = "SELECT sno, id, typename FROM strs_billtypemaster_universal WHERE ifnull(tedit,'')=''";
	} else if ($shortby == "id") {
		$bltyq = "SELECT sno, id, typename FROM strs_billtypemaster_universal WHERE ifnull(tedit,'')='' and id='" . $shortbyvalue . "'";
	}
	$bltyqr = $conn->query($bltyq);
	$billtypelist = array();
	if ($bltyqr->num_rows > 0) {
		while ($btyrow = $bltyqr->fetch_assoc()) {
			$billtypelist[] = array('sno' => $btyrow['sno'], 'id' => $btyrow['id'], 'typename' => $btyrow['typename']);
		}
	}
	return $billtypelist;
}

function billmasteruniversal_bychatur_nonintigrated($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	if ($shortby == "all") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM bill_billmaster_universal_nint WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "onlypending") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM bill_billmaster_universal_nint WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "'";
	} else if ($shortby == "id") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM bill_billmaster_universal_nint WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and id='" . $shortbyvalue . "'";
	} else if ($shortby == "sno") {
		$bllmq = "SELECT sno, companyid, subcompanyid, projectid, id, typeofbill, subproject, identifierlocation, purposecode, responsablepursonname, billdescription, billduration, billduedate, paytoid, beneficiaryname, nextbilldate, uploadreferencefiles, last_reference_no, last_paidstatus FROM bill_billmaster_universal_nint WHERE ifnull(tedit,'')='' and companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and sno='" . $shortbyvalue . "'";
	}
	$bllmqr = $conn->query($bllmq);
	$billmasterlist = array();
	if ($bllmqr->num_rows > 0) {
		while ($bmrow = $bllmqr->fetch_assoc()) {
			$reffilepath = "";
			if (file_exists("../" . $bmrow['uploadreferencefiles'])) {
				$reffilepath = $bmrow['uploadreferencefiles'];
			}
			$billmasterlist[] = array('sno' => $bmrow['sno'], 'companyid' => $bmrow['companyid'], 'subcompanyid' => $bmrow['subcompanyid'], 'projectid' => $bmrow['projectid'], 'id' => $bmrow['id'], 'typeofbill' => $bmrow['typeofbill'], 'subproject' => $bmrow['subproject'], 'identifierlocation' => $bmrow['identifierlocation'], 'purposecode' => $bmrow['purposecode'], 'responsablepursonname' => $bmrow['responsablepursonname'], 'billdescription' => $bmrow['billdescription'], 'billduration' => $bmrow['billduration'], 'billduedate' => $bmrow['billduedate'], 'paytoid' => $bmrow['paytoid'], 'beneficiaryname' => $bmrow['beneficiaryname'], 'nextbilldate' => $bmrow['nextbilldate'], 'uploadreferencefiles' => $reffilepath, 'lastreferenceno' => $bmrow['last_reference_no'], 'lastpaidstatus' => $bmrow['last_paidstatus']);
		}
	}
	return $billmasterlist;
}

function billtransactionsdetails_nonintigrated($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue)
{
	// Base query
	$queryBase = "SELECT sno, companyid, subcompanyid, projectid, subprojectid, billid, typeofbillid, locationid, billdurationid, purposecode, paytoid, beneficiaryname, billno, billingdate, billduedate, billamount, billdescription, uploadreferencefiles, referenceno, paidstatus, paidremark, transactiondate, transactionno, transferedamount, paymentreceiptpath, responsablepursonid, requestedbyid FROM bill_biltransactions_nint WHERE (tedit IS NULL OR tedit = '') AND companyid = ? AND subcompanyid = ? AND projectid = ?";
	// Conditional logic
	$conditions = "";
	$params = [$companyid, $subcompanyid, $projectid];
	$types = "sss";
	if ($shortby === "pendingforrequest") {
		$conditions = " AND (referenceno IS NULL OR referenceno = '')";
	} elseif ($shortby === "historywithbillid") {
		$conditions = " AND billid = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingbillwithbillid") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '') AND billid = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingpaymenttransferwithbillid") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '') AND referenceno<>'' AND billid = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "pendingbillpayment") {
		$conditions = " AND (paidstatus IS NULL OR paidstatus = '')";
	} elseif ($shortby === "onlypendingforpayment") {
		$conditions = " AND referenceno <> '' AND (paidstatus IS NULL OR paidstatus = '')";
	} elseif ($shortby === "sno") {
		$conditions = " AND sno = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "referenceno") {
		$conditions = " AND referenceno = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	}
	$finalQuery = $queryBase . $conditions . " order by YEAR(billingdate) DESC, MONTH(billingdate) DESC, DAY(billingdate) DESC";
	// Prepare statement
	$stmt = $conn->prepare($finalQuery);
	if (!$stmt) {
		die("Failed to prepare statement: " . $conn->error);
	}
	// Bind parameters
	$stmt->bind_param($types, ...$params);
	// Execute query
	$stmt->execute();
	$result = $stmt->get_result();
	$billtranslist = [];
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$billtranslist[] = $row;
		}
	}

	// Close statement and return results
	$stmt->close();
	return $billtranslist;
}


function getclosestmatch($table, $col, $idcol, $string, $projectid, $companyid)
{
	global $conn;
	$data = [];
	//modified by ajay 27-05-2025  REGEXP_REPLACE is not working on previous version of mysql;
	// generate funtion ie remove_non_alphanum in mysql
	$subqry = " and company_id='" . $companyid . "' and project_id='" . $projectid . "'";
	$subqry_2 = " company_id='" . $companyid . "' and project_id='" . $projectid . "'";
	if ($table == "purpose_code_master" || $table == "tbl_payto_master") {
		$subqry = " and cmp_id='" . $companyid . "' and projectid='" . $projectid . "'";
		$subqry_2 = " cmp_id='" . $companyid . "' and projectid='" . $projectid . "'";
	} else {

	}

	$sql = "SELECT " . $idcol . ", " . $col . ", REGEXP_REPLACE(" . $col . ", '[^a-zA-Z0-9]', '') AS displaynamewithoutspch FROM " . $table . " WHERE REGEXP_REPLACE(" . $col . ", '[^a-zA-Z0-9]', '') = REGEXP_REPLACE('" . $string . "', '[^a-zA-Z0-9]', '')  " . $subqry;
	//$sql = "SELECT client_id, client_name,  remove_non_alphanum(client_name) AS displaynamewithoutspch FROM client_master WHERE  projectid='".$projectid."' and companyid ='".$companyid."' and  remove_non_alphanum(client_name) = remove_non_alphanum('".$string."')"; 
	$result = $conn->query($sql);
	if (!$result) {
		return ["error" => $conn->error];
	}
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$data[] = [
				"Name" => $row[$col],
				"Id" => $row[$idcol],
				"stage" => "stage1",
				"percentage" => "100",
				"originalname" => $string
			];

		}
	} else {
		$sql = "WITH versions AS ( SELECT " . $idcol . "," . $col . ", get_abbreviation(" . $col . ") AS v1, get_abbreviation_with_dots(" . $col . ") AS v2, get_abbreviation_with_spdots(" . $col . ") AS v3, REGEXP_REPLACE(" . $col . ", '[^a-zA-Z0-9]', '') AS v4, REGEXP_REPLACE(" . $col . ", '[^a-zA-Z0-9 ]', '') AS v5, LOWER(REGEXP_REPLACE(" . $col . ", '[^a-zA-Z0-9]', '')) AS v6, UPPER(REGEXP_REPLACE(" . $col . ", '[^a-zA-Z0-9]', '')) AS v7, get_abbreviation_no_suffix(" . $col . ") AS v8, REGEXP_REPLACE(" . $col . ", 'Inc$', '') AS v9 FROM " . $table . "  where " . $subqry_2 . " ) SELECT * FROM versions WHERE (lcase(" . $col . ") =lcase('" . $string . "')) or (" . $col . " LIKE '%" . $string . "%' OR v1 LIKE '%" . $string . "%' OR v2 LIKE '%" . $string . "%' OR v3 LIKE '%" . $string . "%' OR v4 LIKE '%" . $string . "%' OR v5 LIKE '%" . $string . "%' OR v6 LIKE '%" . $string . "%' OR v7 LIKE '%" . $string . "%' OR v8 LIKE '%" . $string . "%' OR v9 LIKE '%" . $string . "%' OR soundex(" . $col . ") LIKE CONCAT(SOUNDEX('" . $string . "'), '%') OR MATCH(" . $col . ") AGAINST ('" . $string . "'))";

		$result = $conn->query($sql);
		if (!$result) {
			return ["error" => $conn->error];
		}
		$data = [];
		while ($row = $result->fetch_assoc()) {
			similar_text(strtolower($string), strtolower($row[$col]), $percent);
			if ($percent > 40) {
				$data[] = [
					"Name" => $row[$col],
					"Id" => $row[$idcol],
					"stage" => "stage2",
					"percentage" => round($percent, 2),
					"originalname" => $string
				];

			}
		}
	}

	$result->free();
	if (count($data) > 0) {
		usort($data, function ($a, $b) {
			return $b['percentage'] <=> $a['percentage'];
		});

	}
	return $data;
}

function getClosestMatchV2($table, $col, $idcol, $string)
{
	$input = strtolower(trim($string));
	$closestMatch = '';
	$shortestDistance = -1;

	$sql = "SELECT " . $idcol . ", " . $col . " FROM " . $table;
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$name = strtolower($row[$col]);
			$distance = levenshtein($input, $name);

			// Update if this is the closest match so far
			if ($distance == 0) {
				$closestMatch = $row[$idcol] . "|" . $row[$col];
				$shortestDistance = 0;
				break;
			}

			if ($distance <= $shortestDistance || $shortestDistance < 0) {
				$closestMatch = $row[$idcol] . "|" . $row[$col];
				$shortestDistance = $distance;
			}
		}
	}

	if ($shortestDistance <= 5) {  // max allowed typo difference
		return explode("|", $closestMatch);
	} else {
		return false;
	}
}

function addupdatepurposedetailsIntoMaster($conn, $companyid, $subcompanyid, $projectid, $clientsubcategoryid, $clientsubcategoryname, $clientprovidedcode, $asktcategoryid, $asktsubcategoryid, $purposetype, $purposename, $loginname)
{

	$rrp = [];
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || !$purposename) {
		$rrp = array('status' => 'Error', 'message' => 'Invalid input data. All required fields must be provided.');
		return $rrp;
		die();
	}

	// Set table names based on purpose type
	$pcategorymaster = ($purposetype === 'credit' || $purposetype === 'deposit')
		? 'purpose_category_master_credit'
		: 'purpose_category_master';
	$pcodemaster = ($purposetype === 'credit' || $purposetype === 'deposit')
		? 'purpose_code_master_credit'
		: 'purpose_code_master';

	$pcodemasterclient = ($purposetype === 'credit' || $purposetype === 'deposit')
		? 'purpose_codemaster_client_credit'
		: 'purpose_codemaster_client';



	// Check if Purpose Name already exists
	$stmt_check = $conn->prepare("SELECT COUNT(*) AS cnt FROM $pcodemaster WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purpose = ?");
	$stmt_check->bind_param("ssss", $companyid, $subcompanyid, $projectid, $purposename);

	if (!$stmt_check->execute()) {
		$rrp = array('status' => 'Error', 'message' => 'Database error during purpose name check.');
		return $rrp;
		die();
	}

	$result_check = $stmt_check->get_result();
	$row_check = $result_check->fetch_assoc();

	if ($row_check['cnt'] > 0) {
		$rrp = array('status' => 'Error', 'message' => 'Purpose name already exists. Please try a different name.');
		return $rrp;
		die();
	}

	// Fetch or validate Purpose Category
	if ($asktcategoryid) {
		$ppct = ($purposetype === 'credit' || $purposetype === 'deposit')
			? getpurposecategorymainmaster_credit($conn, "categoryid", $asktcategoryid)
			: getpurposecategorymainmaster($conn, "categoryid", $asktcategoryid);


		if (count($ppct) > 0) {
			$purposecategoryid = $ppct[0]['categoryid'];
			$purposecategory = $ppct[0]['category'];
		} else {
			echo json_encode(['status' => 'Error', 'message' => 'Purpose category not found. Please try again.--------------------']);
			die();
		}
	} else {
		$rrp = array('status' => 'Error', 'message' => 'Purpose category not provided.');
		return $rrp;
		die();
	}

	// Handle Purpose Subcategory
	$newsubcat = 0;
	if ($asktsubcategoryid) {
		$ppsb = ($purposetype === 'credit' || $purposetype === 'deposit')
			? getMainSubcategoryMaster_Credit($conn, "subcategoryid", $asktsubcategoryid)
			: getMainSubcategoryMaster($conn, "subcategoryid", $asktsubcategoryid);

		if (count($ppsb) > 0) {
			$purposesubcategoryid = $ppsb[0]['subcategoryid'];
			$purposesubcategory = $ppsb[0]['subcategoryid'] . '-' . $ppsb[0]['subcategory'];

		} else {
			$maxsubcatid = ($purposetype === 'credit' || $purposetype === 'deposit')
				? credit_maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid)
				: maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid);

			if ($maxsubcatid >= 0) {
				$newsubcat = 1;
				$purposesubcategoryid = $maxsubcatid + 1;
				$purposesubcategory = $purposesubcategoryid . '-' . $asktsubcategoryid;
			} else {
				$rrp = array('status' => 'Error', 'message' => 'Purpose subcategory not found. Please try again.');
				return $rrp;
				die();
			}
		}
	}
	// Generate new purpose code
	$purposeid = getpurposeid($conn, $companyid, $subcompanyid, $projectid, $purposetype);
	$maxProvidedCodeFunction = ($purposetype === 'credit' || $purposetype === 'deposit')
		? 'credit_maxprovidecode_frm'
		: 'maxprovidecode_frm';
	$mxprcode = $maxProvidedCodeFunction($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);
	$newmaxprovidedcode = $mxprcode > 0
		? $purposesubcategoryid . '.' . addZeroBeforeTen($mxprcode + 1)
		: $purposesubcategoryid . '.01';

	$purposecode = createPurposeCodeEncoded($companyid, $subcompanyid, $projectid, $purposecategoryid, $purposesubcategoryid, $purposeid, $newmaxprovidedcode);

	$compaletpurposecode = $purposecode[0]["combinedCode"];
	// Insert new purpose
	$stmt_insert = $conn->prepare("INSERT INTO $pcodemaster (cmp_id, subcmp_id, projectid, categoryid, category, sub_categoryid, sub_category, purpose, purposecode, provided_code, purposeid, markbynote4, source, provided_code_client, sub_categoryid_client, sub_category_client) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$markbynote4 = 'temp';

	$source = 'manual by ' . $loginname;
	$stmt_insert->bind_param("ssssssssssssssss", $companyid, $subcompanyid, $projectid, $purposecategoryid, $purposecategory, $purposesubcategoryid, $purposesubcategory, $purposename, $compaletpurposecode, $newmaxprovidedcode, $purposeid, $markbynote4, $source, $clientprovidedcode, $clientsubcategoryid, $clientsubcategoryname);
	if ($stmt_insert->execute()) {

		$conn->query("UPDATE $pcodemasterclient SET ms_purposecode='" . $compaletpurposecode . "' where companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid='" . $projectid . "' and subcategoryid='" . $clientsubcategoryid . "' and providedcode='" . $clientprovidedcode . "' and (tedit is null or tedit='')");
		$rrp = array('status' => 'Success', 'message' => 'New Purpose saved successfully.', 'purposecode' => $compaletpurposecode, 'purposename' => $purposename);
	} else {
		$rrp = array('status' => 'Error', 'message' => 'Failed to save new Purpose. Please try again.');
	}
	return $rrp;
}

function getSyncedFilesByCondition($conn, $companyid, $subcompanyid, $projectid, $shortby = '', $shortbyvalue = '')
{
	// Base query
	$sql = "SELECT id, companyid, subcompanyid, projectid, filename, filepath, file_extension, file_datetime, filesize, created_at, digitized
            FROM tbl_synced_files
            WHERE companyid = ? AND subcompanyid = ? AND projectid = ?";

	// Initial parameter binding
	$params = [$companyid, $subcompanyid, $projectid];
	$types = "iii";

	// Add conditions based on $shortby
	if ($shortby === "filename" || $shortby === "file_extension") {
		$sql .= " AND `$shortby` = ?";
		$params[] = $shortbyvalue;
		$types .= "s";
	} elseif ($shortby === "digitized") {
		$sql .= " AND digitized = ?";
		$params[] = (int) $shortbyvalue;
		$types .= "i";
	} elseif ($shortby === "undigitized") {
		$sql .= " AND digitized = ?";
		$params[] = 0;
		$types .= "i";
	} elseif ($shortby === "id") {
		$sql .= " AND id = ?";
		$params[] = $shortbyvalue;
		$types .= "i";
	} elseif ($shortby === "undigitizedwithfilename") {
		$sql .= " AND digitized = ? AND filename = ?";
		$params[] = 0;
		$params[] = $shortbyvalue;
		$types .= "is";
	}

	$stmt = $conn->prepare($sql);
	if (!$stmt) {
		die("Prepare failed: " . $conn->error);
	}

	// Bind parameters
	$stmt->bind_param($types, ...$params);
	$stmt->execute();

	$result = $stmt->get_result();
	$files = [];
	while ($row = $result->fetch_assoc()) {
		$files[] = $row;
	}

	$stmt->close();
	return $files;
}

function getunrecounciledaccountlist($conn, $companyid, $subcompanyid, $projectid, $datefrom, $dateto, $transactiontype)
{
	$returnresult = array();
	$qry = "select count(m.sno) as totalentries , m.account_id , b.bankid,b.account_type, b.accountname, b.accountno, b.ifsccode, b.bankname, b.accountholdername   FROM tbl_closetobook m  join   tbl_master_account b on b.accountid =m.account_id and b.projectid=m.project_id where m.company_id='" . $companyid . "' and m.subcompany_id='" . $subcompanyid . "' and m.project_id='" . $projectid . "' and m.amounttype='" . $transactiontype . "'  and (m.tedit is null or m.tedit='') and (lcase(m.book_status)='unknown' or ifnull(m.book_status,'')='') and ifnull(m.reference_no,'') =''  and date(m.date)>='" . date('Y-m-d', strtotime($datefrom)) . "'  and date(m.date)<='" . date('Y-m-d', strtotime($dateto)) . "' group by m.account_id order by b.accountname, b.accountno , b.bankname";
	$result = $conn->query($qry);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$returnresult[] = $row;
		}
	}
	return $returnresult;
}



function getUnreconciledFinancialYears($conn, $companyid, $subcompanyid, $projectid, $transactiontype, $fy_start_month = 4)
{
	$financialYears = [];

	$qry = "SELECT DISTINCT m.date 
            FROM tbl_closetobook m  
            JOIN tbl_master_account b 
              ON b.accountid = m.account_id AND b.projectid = m.project_id 
            WHERE m.company_id = ? 
              AND m.subcompany_id = ? 
              AND m.project_id = ? 
              AND m.amounttype = ? 
              AND (m.tedit IS NULL OR m.tedit = '') 
              AND (LOWER(m.book_status) = 'unknown' OR IFNULL(m.book_status, '') = '') 
              AND IFNULL(m.reference_no, '') = ''";

	$stmt = $conn->prepare($qry);
	$stmt->bind_param("iiis", $companyid, $subcompanyid, $projectid, $transactiontype);
	$stmt->execute();
	$result = $stmt->get_result();

	while ($row = $result->fetch_assoc()) {
		$date = $row['date'];
		if ($date) {
			$fy = getFinancialYear($date, $fy_start_month);
			$financialYears[$fy] = true;
		}
	}

	$stmt->close();

	$yearsList = array_keys($financialYears);
	rsort($yearsList); // Sort from most recent to oldest
	return $yearsList;
}


function getFinancialYear($date, $startMonth = 4)
{
	$timestamp = strtotime($date);
	$year = (int) date('Y', $timestamp);
	$month = (int) date('n', $timestamp);
	if ($startMonth == 1) {
		$startYear = $year;
		$endYear = $year;
	} else {
		if ($month >= $startMonth) {
			$startYear = $year;
			$endYear = $year + 1;
		} else {
			$startYear = $year - 1;
			$endYear = $year;
		}
	}

	return $startYear . '-' . $endYear;
}

function getProjectFinancialYear($startMonth = 4)
{
	if ($startMonth == 1) {
		$startYear = $year;
		$endYear = $year;
	} else {
		$startYear = $year;
		$endYear = $year + 1;
	}
	return $startYear . '-' . $endYear;
}




function NotContainsKeywordforPaymentRequest($string)
{
	$keywords = ['imprest', 'loan', 'part', 'partial', 'cash', 'Investment', 'fd', 'payment on account'];
	// Convert the string to lowercase to make the search case-insensitive
	$string = strtolower($string);

	foreach ($keywords as $keyword) {
		if (strpos($string, strtolower($keyword)) !== false) {
			return true; // Found a keyword
		}
	}
	return false; // No keyword found
}

function getBankChargesKeys($conn, $companyid, $subcompanyid, $projectid, $shortby = null, $shortbyvalue = null)
{
	// Base query
	$query = "SELECT sno, companyid, subcompanyid, projectid, accountid, bankdescription, keyid, key1, loginname, edatetime 
              FROM tbl_bankdescription_bankchargeskey 
              WHERE (tedit IS NULL OR tedit = '') 
              AND companyid = ? AND subcompanyid = ? AND projectid = ?";

	$types = "sss";
	$params = [$companyid, $subcompanyid, $projectid];

	// Optional sorting/filtering
	$allowedSortColumns = ['accountid', 'keyid', 'sno']; // Whitelist
	if ($shortby && in_array($shortby, $allowedSortColumns)) {
		$query .= " AND $shortby = ?";
		$types .= "s";
		$params[] = $shortbyvalue;
	}
	// Prepare and execute
	$stmt = $conn->prepare($query);
	if ($types && $params) {
		$stmt->bind_param($types, ...$params);
	}
	$stmt->execute();
	$result = $stmt->get_result();

	$data = [];
	while ($row = $result->fetch_assoc()) {
		$data[] = $row;
	}

	return $data;
}

function extractChargesAmount($description, $keywithamount)
{
	$patterns = [generateRegexFromString($keywithamount)];
	foreach ($patterns as $pattern) {
		if (preg_match($pattern, $description, $matches)) {
			return floatval(str_replace(',', '', $matches[1]));
		}
	}
	return 0.00; // Default if no pattern matched
}


function generateRegexFromString($input)
{
	// Match fixed prefix and number at the end
	if (preg_match('/^(.*?)([\d,]+\.\d{2})$/', $input, $parts)) {
		$fixedText = preg_quote(trim($parts[1]), '/'); // Escape regex characters
		return '/' . $fixedText . '\s*([\d,]+\.\d{2})/i';   // Return dynamic pattern
	}
	return false; // Return false if input is not in expected format
}

function GetUnReconstructAccountlist($conn, $companyid, $subcompanyid, $projectid, $transactiontype)
{
	$returnresult = array();
	$accountype = "";
	if ($transactiontype) {
		$accountype = " and m.transactiontype='" . $transactiontype . "'";
	}
	$qry = "select b.accountid, b.bankid, b.account_type, b.accountname, b.accountno, b.bankname, b.accountholdername FROM tbl_closetobook_rectbyclient m  join tbl_master_account b on b.accountid =m.accountid and b.projectid=m.projectid where m.companyid='" . $companyid . "' and m.subcompanyid='" . $subcompanyid . "' and m.projectid='" . $projectid . "' " . $accountype . " and (b.tedit is null or b.tedit='') and (m.tedit is null or m.tedit='') and (lcase(m.status)='pending' or ifnull(m.status,'')='') group by m.accountid";
	$result = $conn->query($qry);
	if ($result->num_rows > 0) {
		while ($row = $result->fetch_assoc()) {
			$returnresult[] = $row;
		}
	}
	return $returnresult;
}

function getClientReconstructionData($conn, $companyid, $subcompanyid, $projectid, $transactiontype, $shortby = null, $shortbyvalue = null)
{
	$query = "  SELECT rec.sno, rec.banksno,  date_format(rec.valuedate,'%d-%m-%Y') as valuedate,  rec.transferedamount,  rec.bankdescription,   rec.accountid, CONCAT(b.bankname, ' (', acc.accountno, ')') AS bankinfo
        FROM tbl_closetobook_rectbyclient rec
        LEFT JOIN tbl_master_account acc ON acc.accountid = rec.accountid
        LEFT JOIN tbl_bank_master b ON b.id = acc.bankid
        WHERE rec.companyid = ? 
            AND rec.subcompanyid = ? 
            AND rec.projectid = ? 
            AND rec.transactiontype = ?
    ";

	$params = [$companyid, $subcompanyid, $projectid, $transactiontype];
	$types = "ssss";

	// Optional filtering
	switch ($shortby) {
		case "emailto":
			$query .= " AND rec.emailto = ?";
			$params[] = $shortbyvalue;
			$types .= "s";
			break;

		case "allwithaccountid":
			$query .= " AND rec.accountid = ?";
			$params[] = $shortbyvalue;
			$types .= "s";
			break;

		case "banksno":
			$query .= " AND rec.banksno = ?";
			$params[] = $shortbyvalue;
			$types .= "s";
			break;

		case "bankdescription":
			$query .= " AND rec.bankdescription = ?";
			$params[] = $shortbyvalue;
			$types .= "s";
			break;

		case "unreconstruct_withaccountid":
			$query .= " AND (LOWER(rec.status) = 'pending' OR rec.status IS NULL OR rec.status = '')
                        AND rec.accountid = ?";
			$params[] = $shortbyvalue;
			$types .= "s";
			break;

		case "allpendingentry":
			$query .= " AND (LOWER(rec.status) = 'pending' OR rec.status IS NULL OR rec.status = '')";
			break;

		case null:
		case "":
			// No additional filtering
			break;

		default:
			return ["status" => "error", "message" => "Invalid shortby value."];
	}

	$query .= " ORDER BY b.bankname, rec.entrydate";

	$stmt = $conn->prepare($query);
	if (!$stmt) {
		return ["status" => "error", "message" => "Prepare failed: " . $conn->error];
	}

	$stmt->bind_param($types, ...$params);
	$stmt->execute();

	$result = $stmt->get_result();
	$data = [];

	while ($row = $result->fetch_assoc()) {
		$data[] = $row;
	}

	$stmt->close();
	return $data;
}

function getReconstructed_WithdrawalHistory($conn, $companyid, $subcompanyid, $projectid, $shortby = null, $shortbyvalue = null)
{
	$query = "SELECT * FROM tbl_reconstructedhistory WHERE companyid = ? AND subcompanyid = ? AND projectid = ?";
	$params = [$companyid, $subcompanyid, $projectid];
	$types = "sss";

	// For all filters except "description_like", apply directly
	if ($shortby && $shortby !== "description_like" && $shortbyvalue !== null) {
		switch ($shortby) {
			case "accountid":
				$query .= " AND accountid = ?";
				$params[] = $shortbyvalue;
				$types .= "s";
				break;
			case "description":
				$query .= " AND lcase(description) = ?";
				$params[] = strtolower($shortbyvalue);
				$types .= "s";
				break;
			case "sno":
				$query .= " AND sno = ?";
				$params[] = $shortbyvalue;
				$types .= "i";
				break;
			case "paytoname":
				$query .= " AND lcase(paytoname) = ?";
				$params[] = strtolower($shortbyvalue);
				$types .= "s";
				break;
			case "purposename":
				$query .= " AND lcase(purposename) = ?";
				$params[] = strtolower($shortbyvalue);
				$types .= "s";
				break;
			case "amount":
				$query .= " AND amount = ?";
				$params[] = $shortbyvalue;
				$types .= "d";
				break;
			default:
				return ["status" => "error", "message" => "Invalid shortby filter"];
		}
	}

	$query .= " ORDER BY sno DESC";
	$stmt = $conn->prepare($query);
	if (!$stmt) {
		return ["status" => "error", "message" => "Prepare failed: " . $conn->error];
	}

	$stmt->bind_param($types, ...$params);
	$stmt->execute();
	$result = $stmt->get_result();

	$data = [];
	while ($row = $result->fetch_assoc()) {
		// If "description_like" is used, filter using PHP similarity
		if ($shortby === "description_like" && $shortbyvalue !== null) {
			similar_text(strtolower($row['description']), strtolower($shortbyvalue), $percent);
			if ($percent >= 90) {
				$data[] = $row;
			}
		} else {
			$data[] = $row;
		}
	}

	$stmt->close();
	return $data;
}

function getReconciledReconstructedData($conn, $companyid, $subcompanyid, $projectid, $transactiontype, $shortby = "", $shortbyvalue = "")
{
	$data = [];
	// Base query
	$query = "SELECT sno, id, transactiontype, paid, paidid, purposecode, purpose, banksno, accountid, 
                     transactiondate, bankdescription, bankamount, voiceinstruction, documentpath, 
                     remarkbyclient, referenceno_unique, bookstatus, loginid, history, historysno, 
                     autofilled_internaltransfer 
              FROM reconcile_reconstructed_tbl 
              WHERE (tedit IS NULL OR tedit = '') 
              AND companyid = ? AND subcompanyid = ? AND projectid = ? AND lcase(transactiontype) = ?";

	$params = [$companyid, $subcompanyid, $projectid, strtolower($transactiontype)];
	$paramTypes = "iiis";

	// Handle shortby filters
	if ($shortby === "sno") {
		$query .= " AND sno = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "i";
	} else if ($shortby === "paid") {
		$query .= " AND lcase(paid) = ?";
		$params[] = strtolower($shortbyvalue);
		$paramTypes .= "s";
	} else if ($shortby === "accountid") {
		$query .= " AND accountid = ?";
		$params[] = $shortbyvalue;
		$paramTypes .= "s";
	} else if ($shortby === "pendingpaytoid") {
		$query .= " AND (paidid IS NULL OR paidid = '')";
	} else if ($shortby === "pendingpurposecode") {
		$query .= " AND (purposecode IS NULL OR purposecode = '')";
	}

	// Apply GROUP BY only for pendingpurposecode
	if ($shortby === "pendingpurposecode") {
		$query .= " GROUP BY purpose";
	}

	$query .= " ORDER BY transactiondate DESC";

	// Prepare and execute
	// echo $query;
	if ($stmt = $conn->prepare($query)) {
		$stmt->bind_param($paramTypes, ...$params);
		$stmt->execute();
		$result = $stmt->get_result();

		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		$stmt->close();
	} else {
		return ['error' => 'Failed to prepare SQL statement.'];
	}

	return $data;
}

function getPurposeCodeKeysHistory($conn, $companyid, $subcompanyid, $projectid, $shortby = "", $shortbyvalue = "")
{
	$data = [];

	// Base query
	$query = "SELECT sno, companyid, subcompanyid, projectid, categoryid, subcategoryid, purposecode, purposename, keytext 
              FROM purpose_code_keyshistory 
              WHERE companyid = ? AND subcompanyid = ? AND projectid = ?";

	$params = [$companyid, $subcompanyid, $projectid];
	$paramTypes = "sss";

	// Apply optional filter
	if ($shortby && $shortbyvalue !== "") {
		$allowedShortby = ['categoryid', 'subcategoryid', 'purposecode', 'keytext']; // Add more if needed
		if (in_array($shortby, $allowedShortby)) {
			$query .= " AND {$shortby} = ?";
			$params[] = $shortbyvalue;
			$paramTypes .= is_numeric($shortbyvalue) ? "d" : "s"; // use float (d) for numeric, string (s) otherwise
		}
	}

	$query .= " ORDER BY sno DESC";

	// Prepare and execute
	if ($stmt = $conn->prepare($query)) {
		$stmt->bind_param($paramTypes, ...$params);
		$stmt->execute();
		$result = $stmt->get_result();

		while ($row = $result->fetch_assoc()) {
			$data[] = $row;
		}

		$stmt->close();
	} else {
		return ['error' => 'Failed to prepare SQL statement.'];
	}

	return $data;
}

function sortHtmlTableRowsByCell($html, $cellIndex, $isDate = false, $asc = true)
{
	libxml_use_internal_errors(true); // Avoid warnings for malformed HTML

	$dom = new DOMDocument();
	$dom->loadHTML('<table><tbody>' . $html . '</tbody></table>');
	$xpath = new DOMXPath($dom);
	$rows = $xpath->query('//tbody/tr');

	$rowData = [];

	foreach ($rows as $row) {
		$cells = $row->getElementsByTagName('td');
		$rowHtml = $dom->saveHTML($row);
		$compareValue = $cells->item($cellIndex)->textContent ?? '';

		$rowData[] = [
			'html' => $rowHtml,
			'value' => trim($compareValue)
		];
	}

	usort($rowData, function ($a, $b) use ($isDate, $asc) {
		$valA = $a['value'];
		$valB = $b['value'];

		if ($isDate) {
			$dateA = strtotime($valA);
			$dateB = strtotime($valB);
			return $asc ? $dateA <=> $dateB : $dateB <=> $dateA;
		} elseif (is_numeric($valA) && is_numeric($valB)) {
			return $asc ? $valA <=> $valB : $valB <=> $valA;
		} else {
			return $asc ? strcmp($valA, $valB) : strcmp($valB, $valA);
		}
	});

	$sortedHtml = '';
	foreach ($rowData as $row) {
		$sortedHtml .= $row['html'];
	}

	return $sortedHtml;
}

function updatePayToAccountId($conn, $companyid, $projectid)
{
	try {
		// ✅ Step 1: Clear invalid "yes" flags where no paytoaccountid exists
		$qry_reset = "
            UPDATE tbl_maintransaction 
            SET is_intermediate_transfer = NULL 
            WHERE companyid = ? 
              AND projectid = ? 
              AND is_intermediate_transfer = 'yes' 
              AND (paytoaccountid IS NULL OR paytoaccountid = '')
        ";

		$stmt_reset = $conn->prepare($qry_reset);
		if (!$stmt_reset) {
			throw new Exception("Prepare failed (reset): " . $conn->error);
		}
		$stmt_reset->bind_param("ii", $companyid, $projectid);
		if (!$stmt_reset->execute()) {
			throw new Exception("Execution failed (reset): " . $stmt_reset->error);
		}

		// ✅ Step 2: Update with correct mapping from master account
		$qry_update = "
            UPDATE tbl_maintransaction t1 
            JOIN tbl_master_account t2  
                ON t1.paytoaccountno = t2.accountno  
                AND t1.companyid = t2.cmp_id 
                AND t1.projectid = t2.projectid  
                AND (t2.tedit IS NULL OR t2.tedit = '') 
                AND t2.account_type = 'Bank'
            SET 
                t1.paytoaccountid = t2.accountid,
                t1.is_intermediate_transfer = 'yes'
            WHERE 
                (t1.paytoaccountid IS NULL OR t1.paytoaccountid = '') 
                AND IFNULL(t1.is_intermediate_transfer, '') <> 'yes'
                AND t1.companyid = ?
                AND t1.projectid = ?
        ";

		$stmt_update = $conn->prepare($qry_update);
		if (!$stmt_update) {
			throw new Exception("Prepare failed (update): " . $conn->error);
		}

		$stmt_update->bind_param("ii", $companyid, $projectid);
		if (!$stmt_update->execute()) {
			throw new Exception("Execution failed (update): " . $stmt_update->error);
		}

		// ✅ Return results
		return [
			"status" => "success",
			"reset_rows" => $stmt_reset->affected_rows,
			"updated_rows" => $stmt_update->affected_rows
		];

	} catch (Exception $e) {
		return [
			"status" => "error",
			"message" => $e->getMessage()
		];
	}
}

function isInterBankTransfer($conn, $companyid, $projectid, $accountno)
{
	try {
		// ✅ Step 1: Check account existence and get details
		$sql = "
            SELECT accountid, bankid, bankname 
            FROM tbl_master_account 
            WHERE cmp_id = ? 
              AND projectid = ? 
              AND accountno = ? 
              AND account_type = 'Bank' 
              AND (tedit IS NULL OR tedit = '')
            LIMIT 1
        ";

		$stmt = $conn->prepare($sql);
		if (!$stmt) {
			throw new Exception("Prepare failed: " . $conn->error);
		}

		$stmt->bind_param("iis", $companyid, $projectid, $accountno);
		$stmt->execute();
		$result = $stmt->get_result();

		// ✅ If not found
		if ($result->num_rows === 0) {
			return [
				"status" => "notfound",
				"message" => "No valid bank account found for this company and project.",
				"accountid" => null
			];
		}

		// ✅ If found
		$row = $result->fetch_assoc();

		return [
			"status" => "success",
			"message" => "Bank account found for the given project and company.",
			"accountid" => $row['accountid']
		];

	} catch (Exception $e) {
		// ✅ Handle any error
		return [
			"status" => "error",
			"message" => $e->getMessage(),
			"accountid" => null
		];
	}
}

function bookamount_bankchares_bankdesc($conn, $accountId)
{
	$qry = "SELECT sno, description_bank,total_amount FROM tbl_closetobook  WHERE description_bank REGEXP 'Txn Amt\\..*Charges\\..*Transfer'  AND account_id = '" . $accountId . "' AND  ifnull(bank_chargesupdate,'')=''";
	$aryy = $conn->query($qry);
	if ($aryy && $aryy->num_rows > 0) {
		while ($rowc = $aryy->fetch_assoc()) {
			$value1 = null;
			$value2 = null;
			$desc = $rowc['description_bank'];
			// Regex for Txn Amt. (case insensitive)
			if (preg_match('/Txn Amt\.\s*([\d,]+\.\d{2})/i', $desc, $match)) {
				$value1 = (float) str_replace(",", "", $match[1]);
			}

			// Regex for Charges. (case insensitive)
			if (preg_match('/Charges\.\s*([\d,]+\.\d{2})/i', $desc, $match)) {
				$value2 = (float) str_replace(",", "", $match[1]);
			}

			if ($value1 !== null) {
				// Prepare update query safely


				$amt1 = (float) $value1;
				$amt2 = ($value2 !== null ? (float) $value2 : 0);
				$total = (float) $rowc['total_amount'];

				$diff = round($total - ($amt1 + $amt2), 0);
				$validat = ($diff == 0 ? 'yes' : 'Amt diff is ' . $diff);

				$updateqry = "UPDATE tbl_closetobook 
                              SET bank_chargesupdate ='" . $validat . "' ,bookedamount_desc = " . $value1 . ",
                                  bankcharges_desc = " . ($value2 !== null ? $value2 : 0) . " 
                              WHERE sno = " . $rowc['sno'];
				$conn->query($updateqry);
			} else {
				$updateqry = "UPDATE tbl_closetobook   SET bank_chargesupdate ='notfind'  WHERE sno = " . $rowc['sno'];
				$conn->query($updateqry);

			}
		}
	}
}

function updateSingleBreakdownTransferedAmount($conn, $projectid)
{
	try {
		// ✅ Single-query update using JOIN and GROUP BY condition
		$sql = "UPDATE tbl_maintransaction_breakdown b JOIN tbl_maintransaction m ON b.reference_no = m.reference_no JOIN ( SELECT reference_no FROM tbl_maintransaction_breakdown GROUP BY reference_no HAVING COUNT(sno) = 1 ) single_ref ON b.reference_no = single_ref.reference_no SET b.amount = m.transfered_amount, m.book_amount = m.transfered_amount WHERE  m.projectid = ? AND (m.tedit IS NULL OR m.tedit = '') AND (b.tedit IS NULL OR b.tedit = '') AND m.beneficierie = 'Individual' AND m.book_amount != m.transfered_amount";
		$stmt = $conn->prepare($sql);
		$stmt->bind_param("i", $projectid);
		$stmt->execute();

		$affected = $stmt->affected_rows;
		$stmt->close();

		return [
			"status" => "success",
			"message" => "$affected breakdown record(s) updated successfully.",
			"updated_count" => $affected
		];

	} catch (Exception $e) {
		return [
			"status" => "error",
			"message" => "Error: " . $e->getMessage()
		];
	}
}

function updatereferenceinc_bank($conn, $companyid, $subcompanyid, $projectid, $accountid)
{
	$res = $conn->query("select sno,description_bank, chequeno from tbl_closetobook where  account_id='" . $accountid . "' and amounttype='withdrawn' and company_id=" . $companyid . " and subcompany_id=" . $subcompanyid . " and project_id=" . $projectid . " and ifnull(update_payto ,'') ='' ");
	if ($res->num_rows > 0) {
		while ($row = $res->fetch_assoc()) {
			$actual_refno = "";
			$likelypayto = "";
			$referenceno = "";
			$flag = "";
			if (strlen($row["chequeno"]) >= 40 && !stristr($row["description_bank"], "neft")) {
				$actual_refno = $row["chequeno"];
				$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["chequeno"]));
				$referenceno = $description_parts[0];
				$flag = "s";
			} elseif (strlen($row["chequeno"]) >= 40 && stristr($row["description_bank"], "neft")) {
				$actual_refno = $row["chequeno"];
				$description_parts = explode(" ", preg_replace('/\s+/', ' ', $row["description_bank"]));
				$referenceno = $description_parts[5];
				$flag = "s";
			} elseif (str_starts_with($row["description_bank"], "CMS")) {
				$description_parts = explode("/", $row["description_bank"]);
				$referenceno = $description_parts[0] . "/" . $description_parts[1];
			} elseif (str_starts_with($row["description_bank"], "INET")) {
				$description_parts = explode("/", $row["description_bank"], 3);
				$referenceno = $description_parts[count($description_parts) - 1];
			} elseif (str_starts_with($row["description_bank"], "RTGS")) {
				$description_parts = explode("/", $row["description_bank"]);
				$referenceno = $description_parts[1];
			} elseif (str_starts_with($row["description_bank"], "GIB")) {
				$description_parts = explode("/", $row["description_bank"]);
				$referenceno = $description_parts[1];
			} elseif (str_starts_with(strtoupper($row["description_bank"]), "WITHDRAWAL TRANSFER NEFT")) {
				$description_parts = explode("/", $row["description_bank"]);
				$referenceno = $description_parts[2];
				$likelypayto = $description_parts[3];
			} elseif (str_starts_with(strtoupper($row["description_bank"]), "WITHDRAWAL TRANSFER RTGS")) {
				$description_parts = explode("/", $row["description_bank"]);
				$referenceno = $description_parts[2];
				$likelypayto = $description_parts[3];
			} elseif (str_starts_with(strtoupper($row["description_bank"]), "TO TRANSFER/FOR CRN:")) {
				$description_parts = explode("/", $row["description_bank"]);
				$referenceno = explode(" ", $description_parts[1])[1];
			} elseif (str_starts_with(strtoupper($row["description_bank"]), "INB/NEFT")) {
				$description_parts = explode("/", $row["description_bank"]);
				$referenceno = $description_parts[2];
				$likelypayto = $description_parts[3];
			} elseif (str_starts_with(strtoupper($row["description_bank"]), "TO TRANSFER-INB NEFT UTR NO:")) {
				$description_parts = explode("--", $row["description_bank"]);
				$referenceno = explode(":", $description_parts[0])[1];
				$likelypayto = explode("/", explode(":", $description_parts[1])[1])[1];
			} elseif (str_starts_with(strtoupper($row["description_bank"]), "TO TRANSFER-INB") && !stristr(strtoupper($row["description_bank"]), "NEFT")) {
				$description_parts = explode("--", preg_replace('/\s+/', ' ', $row["description_bank"]));
				$referenceno = explode(" ", trim($description_parts[1]), 5)[0];
				$likelypayto = explode("/", trim(explode(" ", trim($description_parts[1]), 5)[4]))[0];
			} elseif (str_starts_with(strtoupper($row["description_bank"]), "PAYPAL *")) {
				$description_parts = explode("*", preg_replace('/\s+/', ' ', $row["description_bank"]));
				$likelypayto = trim(explode("-", trim($description_parts[1]))[0]);
			}
			if ($flag == "s") {
				$conn->query("update tbl_closetobook set chequeno_desc='" . trim($referenceno) . "' where sno=" . $row["sno"]);
				//, description= concat(description_bank,' ','".$actual_refno."' )
			} else {
				if ($likelypayto) {
					$paytoname = "";
					$paytores = $conn->query("select paytoname from tbl_payto_master where ucase(paytoname) like '" . strtoupper(trim($likelypayto)) . "%'  and cmp_id=" . $companyid . " and subcmp_id=" . $subcompanyid . " and projectid=" . $projectid . " and ifnull(tedit,'')='' order by sno limit 1");
					if ($paytores->num_rows > 0) {
						$paytorow = $paytores->fetch_assoc();
						$paytoname = $paytorow["paytoname"];
					}
					if ($paytoname) {
						$conn->query("update tbl_closetobook set payto='" . trim($paytoname) . "',tsno=1 where sno=" . $row["sno"]);
					} else {
						$conn->query("update tbl_closetobook set payto='" . trim($likelypayto) . "' where sno=" . $row["sno"]);
					}
				}

				$conn->query("update tbl_closetobook set update_payto='yes' where sno=" . $row["sno"]);

			}

		}

	}
}
?>