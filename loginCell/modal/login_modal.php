<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../functions/login_functions.php');
$action=$_GET['action'];
if($action=="entertologin"){
	// Start the session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
	$baseurl = $_POST['baseurl'];
	$loginid = $_POST['loginidval'];
	$loginpassword = $_POST['loginpasswordval'];
	$companyid = "";
	if(isset($_POST['companyid'])){
		$companyid = $_POST['companyid'];
	}
	$cheklogin=loginuserinfo($authconn,$loginid,"loginid",$loginid);
	$loginreturn=array();
	if(count($cheklogin)>0){
		$loginroleid = $cheklogin[0]['loginroleid'];

		if(count($cheklogin)>1){
			$loginreturn[]=array('status'=>'Error', 'message'=>'Your login id registered with more than 1 company, Please contact to web admin');
		}else{
			if($loginroleid>0){
				$authprj = getauthorizedprojectswithloginid($authconn,$loginid);
				if(count($authprj)==0){
					$loginreturn[]=array('status'=>'Error', 'message'=>'You did not have any authorized project for login, Please contact to web admin');
					echo json_encode($loginreturn);
					exit();
				}
			}
			$chekislogin=isLogin($authconn,$loginid,$loginpassword);
			if(count($chekislogin)>0){
				foreach ($chekislogin as $lgkey) {
					$_SESSION['loginid']=$lgkey['loginid'];
					$_SESSION['loginname'] = $lgkey['loginname'];
					$_SESSION['loginroleid'] = intval($lgkey['loginroleid']);
			        $_SESSION['sessionid'] = session_id(); // Unique session ID
					$loginreturn[]=array('status'=>'Success', 'message'=>'Login Successfully', 'loginname'=>$lgkey['loginname'], 'loginid'=>$lgkey['loginid'], 'loginroleid'=>$lgkey['loginroleid'], 'loginrolename'=>$lgkey['loginrolename'], 'companyid'=>$companyid, 'subcompanyid'=>'', 'projectid'=>'', 'sessionid'=>$_SESSION['sessionid'],'comments'=>$lgkey['comments']);
				}
			}else{
				$loginreturn[]=array('status'=>'Error', 'message'=>'Your loginid / password incorrect, Please try again with valid details');
			}
		}
	}else{
		$loginreturn[]=array('status'=>'Error', 'message'=>'You are not registered with us, So Please do not try to login or please contact to web admin');
	}
	echo json_encode($loginreturn);
}else if($action=="authorizedprojects_homehtmlview"){
	$loginid = $_POST['loginid'];
	$lgin=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	foreach ($lgin as $lgkey) {
		$loginroleid = $lgkey['loginroleid'];
	}
	$ccphtml="";
	if($loginroleid==0){
		$prj=getprojectdetailwithcmpsubcmpWithL($conn,"","","all","");
	}else{
		$allcmp=allauthorizedcompanyid($authconn,$loginid);
		$allsubcmp=allauthorizedsubcompanyid($authconn,$loginid);
		$allprj=allauthorizedprojectid($authconn,$loginid);
		$prj=getprojectdetailwithcmpsubcmpWithL($conn,$allcmp,$allsubcmp,"withauthrizedprojects",$allprj);
	}
	echo json_encode($prj);
}else if($action=="logindetails_htmltableview"){
	// Sanitize POST input to avoid injection vulnerabilities
	$companyid = isset($_POST['companyid']) ? htmlspecialchars($_POST['companyid']) : '';
	$subcompanyid = isset($_POST['subcompanyid']) ? htmlspecialchars($_POST['subcompanyid']) : '';
	$projectid = isset($_POST['projectid']) ? htmlspecialchars($_POST['projectid']) : '';
	$loginid = isset($_POST['loginid']) ? htmlspecialchars($_POST['loginid']) : '';

	// Get login details
	$lginfo = loginuserdetailsfrommaster($authconn, "", "loginid", $loginid);
	$loginroleid = "";

	if (count($lginfo) > 0) {
	    $loginroleid = $lginfo[0]['loginroleid'] ?? '';
	}

	// Fetch user details with authorization
	$logininfo = [];
	$logininfo = loginuserdetailswithauthproject($authconn, $companyid, $subcompanyid, $projectid, $loginroleid, "allwithprojectid", "");
	$lghtml = "";
	if (count($logininfo) > 0) {
	    $ss = 1;
	    foreach ($logininfo as $lginkey) {
	        // Fetch project details
			$pppj = getprojectdetailwithcmpsubcmpWithL($conn, $companyid, $subcompanyid, "projectid", $lginkey['projectid']);
	        $projectname = $pppj[0]['projectname'] ?? '';

	        $strikecss = ($lginkey['isactive'] == "Dactive") ? "striketxt_red" : "";

	        // Append user details to HTML
	        $lghtml .= "<tr data-lgcompanyid='".$lginkey['companyid']."' data-lgsubcompanyid='".$lginkey['subcompanyid']."' data-lgprojectid='".$lginkey['projectid']."' data-loginid='".$lginkey['loginid']."' data-loginroleid='".$lginkey['loginroleid']."'>
	            <td class='text-center'>" . $ss++ . "</td>
	            <td class='lgloginid_td text-center {$strikecss}'>" . htmlspecialchars($lginkey['loginid']) . "</td>
	            <td class='lgname_td {$strikecss}'>" . htmlspecialchars($lginkey['loginname']) . "</td>
	            <td class='lgrolename_td {$strikecss}'>" . htmlspecialchars($lginkey['loginrolename']) . "</td>
	            <td class='lgmobileno_td {$strikecss}'>" . htmlspecialchars($lginkey['mobileno']) . "</td>
	            <td class='lgemail_td {$strikecss}'>" . htmlspecialchars($lginkey['emailid']) . "</td>
	            <td class='lgedit_td text-center'>
	                <i class='fa fa-edit m-2 editlginfo' style='font-size: 28px; cursor:pointer;'></i>
	                <i class='fa fa-trash text-danger m-2 removelginfo' onclick=\"removeuserloginfrommasterhtml('hidden_baseurl', '" . htmlspecialchars($lginkey['loginid']) . "', '" . htmlspecialchars($lginkey['loginname']) . "')\" style='font-size: 28px; cursor:pointer;'></i>
	            </td>
	        </tr>";
	    }
	} else {
	    $lghtml .= "<tr><td colspan='9' style='text-align:center;'>Data not available</td></tr>";
	}

	// Output the HTML
	echo $lghtml;
}else if($action=="loginrolelistoption"){
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$loginid = $_POST['loginid'] ?? '';
	$loginroleid = $_POST['loginroleid'] ?? '';
	if($loginroleid === "" || $loginroleid === null){
		if(!empty($loginid)){
			$lg=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
			if(count($lg)>0){
				$loginroleid = $lg[0]['loginroleid'] ?? '';	
			}
		}
	}
	if($loginroleid === "0" || $loginroleid === 0 || (int)$loginroleid === 0 || $loginroleid === "" || $loginroleid === null){
		$loginrole=login_rolefrommaster($authconn,"allactive",$loginroleid);
	}else{
		$loginrole=login_rolefrommaster($authconn,"afterloginroleid",$loginroleid);
	}
	$dslist="";
	if(count($loginrole)>0){
		if(count($loginrole)>1){
			$dslist.="<option value='' disabled selected>Choose the login role</option>";
		}
		foreach ($loginrole as $ddkey) {
			$dslist.="<option value='".$ddkey['roleid']."'>".ucwords($ddkey['rolename'])."</option>";
		}
	}else{
		$dslist.="<option value='' disabled>No data available</option>";
	}
	echo $dslist;
}else if($action=="getdepartments"){ 
	$lginfo=logindepartmentmaster($authconn);
	echo json_encode($lginfo);	
}else if($action=="logindetailswithloginid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$lginfo=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	echo json_encode($lginfo);	
}else if($action=="projectlistforauthorizaton_html"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$prj=getprojectdetailsWithL($conn,$companyid,$subcompanyid,"all","");
	$prjht="";
	// print_r($prj);
	if(count($prj)>0){
		foreach ($prj as $prjkey) {
			$authprjlist=getauthorizedprojects($authconn,$prjkey['companyid'],$prjkey['subcompanyid'],$prjkey['projectid'],$loginid,"authorizedprojectid",$prjkey['projectid']);
			$checkprj="";
			if(count($authprjlist)>0){
				foreach ($authprjlist as $aupkey) {
					$auprojectid=$aupkey['projectid'];
					$checkprj="checked";
				}
			}
			if($projectid==$prjkey['projectid']){
				$prjht.="<tr>
					<td style='width: 50px; text-align: center;'><input type='checkbox' class='projectheckbox' checked /></td>
					<td class='projectname_td'>".strtoupper($prjkey['projectname'])."<input type='hidden' class='projectid_input' value='".$prjkey['projectid']."' /></td>
				</tr>";
			}else{
				$prjht.="<tr>
					<td style='width: 50px; text-align: center;'><input type='checkbox' class='projectheckbox' ".$checkprj." /></td>
					<td class='projectname_td'>".strtoupper($prjkey['projectname'])."<input type='hidden' class='projectid_input' value='".$prjkey['projectid']."' /></td>
				</tr>";
			}			
		}
	}else{
		$prjht.="<tr><td style='text-align:center;' colspan='2'>Data not available</td></tr>";
	}
	echo $prjht;
}else if($action=="modulelistforauthorizaton_html____"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$userloginid = $_POST['userloginid'];
	$loginedid = $_POST['loginedid'];
	$lginfo=loginuserdetailsfrommaster($authconn,$loginroleid,"loginid",$loginedid);
	$loginroleid="";
	if(count($lginfo)>0){
		$loginroleid = $lginfo[0]['loginroleid'];
	}

	$mmd=OnlyProject_loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"allactive_mainaccount","");
	$mdlht="";
	$prevmainhead ="";
	$authmdllist =array();
	if($userloginid){
		$authmdllist = loginauthorizedmodules($authconn,$companyid,$subcompanyid,$projectid,$userloginid,"allwithloginid","");
	} 
	if(count($mmd)>0){
		foreach ($mmd as $mdlkey) { 
			$exists="";
			$alreadyexist="";
			$passkeyval="";
			foreach ($authmdllist as $authmdl) {
			    if (isset($authmdl['moduleid']) && $authmdl['moduleid'] == $mdlkey['moduleid']) {
			    	$passkeyval = $authmdl['password'];
			        $exists = "checked";
			        $alreadyexist = "<i class='fa fa-check text-success'></i>";
			        break;
			    }
			}

			$passkey="";
			if($mdlkey['ispasswordprotected']=="yes"){
				$passkey  = "<i class='fa fa-key text-success'></i>";
			}

			if ($prevmainhead != $mdlkey['groupid']) {
		        $mdlht .= "<tr class='heading {$mdlkey['groupid']}' data-groupid={$mdlkey['groupid']}>
			            <td style='width:100px; text-align: center;' >
			                <input type='checkbox' class='modulecheckbox_main' id='main_{$mdlkey['groupid']}' 
			                       onclick='toggleSubCheckboxes(\"{$mdlkey['groupid']}\")' />
			            </td>
			            <td class='modulename_td'  style='font-weight: bold; font-size: large;'>{$mdlkey['groupname']} <input type='hidden' class='moduleid_input' value='' /></td>
			        </tr>";
		    }		   
		    $inputType = ($mdlkey['ispasswordprotected'] == "yes") ? "text" : "hidden";
		    $divaction = ($mdlkey['ispasswordprotected'] == "yes") ? "" : "d-none";
			$mdlht .= "<tr style='display:none;' data-ispasskey='{$mdlkey['ispasswordprotected']}' data-modulepasskey='{$passkeyval}' class='details {$mdlkey['groupid']}'>
			    <td style='width:100px; text-align: center;'>
			        <input type='checkbox' class='modulecheckbox sub_{$mdlkey['groupid']}'  
			               onchange='checkMainCheckbox(\"{$mdlkey['groupid']}\")' {$exists} /> 
			    </td>
			    <td class='modulename_td'>{$alreadyexist} {$mdlkey['modulename']} {$passkey}
			        <input type='hidden' class='moduleid_input' value='{$mdlkey['moduleid']}' />
			        <input type='hidden' class='ispasskey' value='{$mdlkey['ispasswordprotected']}' />
			        <div class='float-right {$divaction}'>
			        <label>Module password : </label>
			        <input type='{$inputType}' class='modulepasskey m-1' value='{$passkeyval}' placholder='Set module password' />
			        </div>
			    </td>
			</tr>";
	   	    $prevmainhead = $mdlkey['groupid'];	
		}
	}else{
		$mdlht.="<tr><td style='text-align:center;' colspan='2'>Module not available</td></tr>";
	}
	echo $mdlht;
}else if($action=="modulelistforauthorizaton_html"){
	$companyid     = $_POST['companyid'] ?? '';
	$subcompanyid  = $_POST['subcompanyid'] ?? '';
	$projectid     = $_POST['projectid'] ?? '';
	$userloginid   = $_POST['userloginid'] ?? '';
	$loginedid     = $_POST['loginedid'] ?? '';
	$loginroleid = "";
	$lginfo = loginuserdetailsfrommaster($authconn, "", "loginid", $loginedid);
	if (is_array($lginfo) && count($lginfo) > 0) {
	    $loginroleid = $lginfo[0]['loginroleid'];
	}
	$authmdllist = [];
	if ($userloginid) {
	    $authmdllist = loginauthorizedmodules($authconn,$companyid,$subcompanyid,$projectid,$userloginid,
	        "allwithloginid","");
	}
	$authorized = [];
	foreach ($authmdllist as $a) {
	    $authorized[$a['moduleid']] = $a['password'] ?? '';
	}
	$sql = "
	SELECT
	    g.groupid,
	    g.groupname,
	    m.moduleid,
	    m.modulename,
	    m.ispasswordprotected
	FROM login_group_master_client g
	INNER JOIN login_modules_master_client m
	    ON m.groupid = g.groupid
	WHERE
	    LOWER(g.status) = 'active'
	    AND LOWER(m.status) = 'active'
	    AND g.companyid = ?
	    AND g.subcompanyid = ?
	    AND g.projectid = ?
	    AND m.modulename IS NOT NULL
	    AND m.modulefoldername IS NOT NULL
	ORDER BY
	    g.vieworderby,
	    g.groupname,
	    m.vieworderby,
	    m.modulename
	";

	$stmt = $authconn->prepare($sql);
	$stmt->bind_param("iii", $companyid, $subcompanyid, $projectid);
	$stmt->execute();
	$result = $stmt->get_result();

	/* =======================
	   HTML OUTPUT
	======================= */
	$mdlht = "";
	$prevGroupId = "";

	if ($result->num_rows > 0) {

	    while ($row = $result->fetch_assoc()) {

	        $groupid   = $row['groupid'];
	        $groupname = $row['groupname'];
	        $moduleid  = $row['moduleid'];
	        $modulename = $row['modulename'];
	        $ispass    = $row['ispasswordprotected'];

	        /* Authorization */
	        $exists = "";
	        $alreadyexist = "";
	        $passkeyval = "";

	        if (isset($authorized[$moduleid])) {
	            $exists = "checked";
	            $alreadyexist = "<i class='fa fa-check text-success'></i>";
	            $passkeyval = $authorized[$moduleid];
	        }

	        $passkeyIcon = ($ispass === "yes")
	            ? "<i class='fa fa-key text-success'></i>"
	            : "";

	        /* =======================
	           GROUP ROW (INLINE CSS)
	        ======================= */
	        if ($prevGroupId !== $groupid) {
	            $mdlht .= "
	            <tr class='heading {$groupid}'
	                data-groupid='{$groupid}'
	                style='background-color:#e3f2fd;border-top:2px solid #90caf9;'>

	                <td style='width:100px;text-align:center;'>
	                    <input type='checkbox'
	                           class='modulecheckbox_main'
	                           id='main_{$groupid}'
	                           onclick='toggleSubCheckboxes(\"{$groupid}\")'>
	                </td>

	                <td class='modulename_td'
	                    style='font-weight:bold;font-size:large;'>
	                    {$groupname}
	                </td>
	            </tr>";
	        }

	        /* =======================
	           MODULE ROW (INLINE CSS)
	        ======================= */
	        $inputType = ($ispass === "yes") ? "text" : "hidden";
	        $divaction = ($ispass === "yes") ? "" : "d-none";

	        $mdlht .= "
	        <tr class='details {$groupid}'
	            style='display:none;background-color:#f9fbe7;'
	            data-ispasskey='{$ispass}'
	            data-modulepasskey='{$passkeyval}'>

	            <td style='width:100px;text-align:center;'>
	                <input type='checkbox'
	                       class='modulecheckbox sub_{$groupid}'
	                       onchange='checkMainCheckbox(\"{$groupid}\")'
	                       {$exists}>
	            </td>

	            <td class='modulename_td'>
	                {$alreadyexist} {$modulename} {$passkeyIcon}

	                <input type='hidden'
	                       class='moduleid_input'
	                       value='{$moduleid}'>

	                <input type='hidden'
	                       class='ispasskey'
	                       value='{$ispass}'>

	                <div class='float-right {$divaction}'>
	                    <label>Module password :</label>
	                    <input type='{$inputType}'
	                           class='modulepasskey m-1'
	                           value='{$passkeyval}'
	                           placeholder='Set module password'>
	                </div>
	            </td>
	        </tr>";

	        $prevGroupId = $groupid;
	    }

	} else {

	    $mdlht .= "
	    <tr>
	        <td colspan='2' style='text-align:center;'>
	            Module not available
	        </td>
	    </tr>";
	}
	echo $mdlht;
}else if($action=="addupdatelogindetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid']; 	 
	$username = $_POST['username'];
	$loginroleid = $_POST['userloginroleid'];
	$usermobileno = $_POST['usermobileno'];
	$useremail = $_POST['useremail'];
	$isactivestatus = $_POST['isactivestatus'];
	$userid = $_POST['userid'];
	$loginpassword = $_POST['loginpassword'];
	
	$savebyname = $_POST['savebyname'];
	$departmentofuser=$_POST['departmentofuser'];
	$authirzedprojects = json_decode($_POST['authorizedproject'],TRUE);
	$authorizedmodules = json_decode($_POST['authorizedmodule'],TRUE);
	$authresult=array();

	if($userid!=""){//update
		$chklg=loginuserdetailsfrommaster($authconn,"","loginid",$userid);
		if(count($chklg)>0){
			foreach ($chklg as $chkkey) {
				if($username=="" || $username=="undefined" || $username==null || $username=="null"){
					$username =$chkkey['loginname'];
				}
				// $loginroleid
				if($loginroleid=="" || $loginroleid=="undefined" || $loginroleid==null || $loginroleid=="null"){
					$loginroleid =$chkkey['loginroleid'];
				}
				// $usermobileno
				if($usermobileno=="" || $usermobileno=="undefined" || $usermobileno==null || $usermobileno=="null"){
					$usermobileno =$chkkey['mobileno'];
				}

				// $useremail
				if($useremail=="" || $useremail=="undefined" || $useremail==null || $useremail=="null"){
					$useremail =$chkkey['emailid'];
				}

				// $isactivestatus	
				if($isactivestatus=="" || $isactivestatus=="undefined" || $isactivestatus==null || $isactivestatus=="null"){
					$isactivestatus =$chkkey['isactive'];
				}
			}

			$chkdupname_dep=$username."|".$departmentofuser."|".$companyid."|".$subcompanyid."|".$userid ;

			$lroleinfo=loginuserdetailsfrommaster($authconn,"","checknamewithdepartment",$chkdupname_dep);
			if(count($lroleinfo)>0){
				$authresult[]=array('status'=>'Error', 'message'=>$username .' already exists with the same department');
				echo json_encode($authresult);
				exit;
			}

			$lroleinfo=login_rolefrommaster($authconn,"roleid",$loginroleid);
			$loginrolename="";
			foreach ($lroleinfo as $lrkey) {
				$loginrolename=$lrkey['rolename'];
			}
			//update user login basic details
			$authconn->query("UPDATE login_user_master SET loginname='".$username."', loginpassword='".$loginpassword."', loginroleid='".$loginroleid."', loginrolename='".$loginrolename."', mobileno='".$usermobileno."', emailid='".$useremail."', isActive='".$isactivestatus."' , departmentid='".$departmentofuser."'  where loginid='".$userid."'");
			if(mysqli_affected_rows($authconn)>0){
				$authresult[]=array('status'=>'Success', 'message'=>'User login details update successfully', 'userid'=>$userid);
			}
			if(count($authirzedprojects)>0){
				//add/update authorized project
				$authconn->query("UPDATE login_authorized_projects SET status='".date('Y-m-d h:i:s')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and loginid='".$userid."' and status='active'");

				foreach ($authirzedprojects as $auprjkey) {
					$authprojectid=$auprjkey['projectid'];
					$cq=$authconn->query("SELECT projectid from login_authorized_projects where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
					if($cq->num_rows>0){
						$authconn->query("UPDATE login_authorized_projects SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
					}else{
						if($auprjkey['projectid']!=""){
							$authconn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$auprjkey['projectid']."', '".$userid."', 'active')");
							if(mysqli_affected_rows($authconn)>0){
								$authresult[]=array('status'=>'Success', 'message'=>'Project authorization save successfully.');
							}
						}
					}

					//add or update modules
					if(count($authorizedmodules)>0 && $authprojectid!=""){
						$authconn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
						foreach ($authorizedmodules as $aupmdlkey) {
							$modulepassword="";
							if($aupmdlkey['modulepassword'] && $aupmdlkey['ismodulepasskey']=="yes"){
								$modulepassword = $aupmdlkey['modulepassword'];
							}else if($aupmdlkey['ismodulepasskey']=="yes"){
								$modulepassword = $loginpassword;
							}
							// moduleid
							$cq=$authconn->query("SELECT moduleid from login_authorized_modules where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
							if($cq->num_rows>0){
								$authconn->query("UPDATE login_authorized_modules SET status='active', password='".$modulepassword."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
							}else{
								if($aupmdlkey['moduleid']){
									$authconn->query("INSERT INTO login_authorized_modules(companyid,subcompanyid,projectid,moduleid,loginid,status,ispassword,password) VALUES('".$companyid."', '".$subcompanyid."', '".$authprojectid."', '".$aupmdlkey['moduleid']."', '".$userid."', 'active','1','".$modulepassword."')");
								}
							}
						}
						$authresult[]=array('status'=>'Success', 'message'=>'Authorized modules update successfully', 'userid'=>$userid);
					}else{
						$authconn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
					}
				}
			}else{
				$authresult[]=array('status'=>'Error', 'message'=>'Project authorization is missing.');
			}			
		}else{
			//new insert  
			$chkdup_loginid = loginuserdetailsfrommaster($authconn, "", "loginid", $userid);
			if (count($chkdup_loginid) > 0) {
				$authresult[] = array('status' => 'Error', 'message' => "Login ID '" . $userid . "' already exists. Please choose a different Login ID.");
				echo json_encode($authresult);
				exit;
			}

			// if (!empty($usermobileno)) {
			// 	$chkdup_mob = loginuserdetailsfrommaster($authconn, "", "mobileno", $usermobileno);
			// 	if (count($chkdup_mob) > 0) {
			// 		$authresult[] = array('status' => 'Error', 'message' => "Mobile number '" . $usermobileno . "' is already registered to user '" . $chkdup_mob[0]['loginname'] . "' (" . $chkdup_mob[0]['loginid'] . ").");
			// 		echo json_encode($authresult);
			// 		exit;
			// 	}
			// }

			$chkdupname_dep=$username."|".$departmentofuser."|".$companyid."|".$subcompanyid."|" ;
			$lroleinfo=loginuserdetailsfrommaster($authconn,"","checknamewithdepartment",$chkdupname_dep);
			if(count($lroleinfo)>0){
				$authresult[]=array('status'=>'Error', 'message'=>$username .' already exists with the same department');
				echo json_encode($authresult);
				exit;
			}

			$lroleinfo=login_rolefrommaster($authconn,"roleid",$loginroleid);
			$loginrolename="";
			foreach ($lroleinfo as $lrkey) {
				$loginrolename=$lrkey['rolename'];
			}

			//update user login basic details
			$authconn->query("INSERT INTO login_user_master(loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive,departmentid) VALUES('".$username."', '".$userid."', '".$loginpassword."', '".$loginroleid."', '".$loginrolename."', '".$usermobileno."', '".$useremail."', '".$isactivestatus."', '".$departmentofuser."')");
			$authprojectid="";
			if(mysqli_affected_rows($authconn)>0){
				$authresult[]=array('status'=>'Success', 'message'=>'User login details saved successfully', 'userid'=>$userid);
				if(count($authirzedprojects)>0){
					//add/update authorized project
					$authconn->query("UPDATE login_authorized_projects SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and loginid='".$userid."' and status='active'");
					foreach ($authirzedprojects as $auprjkey) {
						$authprojectid=$auprjkey['projectid'];
						$cq=$authconn->query("SELECT projectid from login_authorized_projects where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
						if($cq->num_rows>0){
							$authconn->query("UPDATE login_authorized_projects SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
						}else{
							if($authprojectid){
								$authconn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$auprjkey['projectid']."', '".$userid."', 'active')");
								if(mysqli_affected_rows($authconn)>0){
									$authresult[]=array('status'=>'Success', 'message'=>'Project authorization save successfully.');
								}
							}
						}

						//add or update modules
						if(count($authorizedmodules)>0 && $authprojectid!=""){
							$modulepassword="";
							if($aupmdlkey['modulepassword'] && $aupmdlkey['ismodulepasskey']=="yes"){
								$modulepassword = $aupmdlkey['modulepassword'];
							}else if($aupmdlkey['ismodulepasskey']=="yes"){
								$modulepassword = $loginpassword;
							}

							$authconn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
							foreach ($authorizedmodules as $aupmdlkey) {
								// moduleid
								$cq=$authconn->query("SELECT moduleid from login_authorized_modules where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
								if($cq->num_rows>0){
									$authconn->query("UPDATE login_authorized_modules SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
								}else{
									if($aupmdlkey['moduleid']){
										$authconn->query("INSERT INTO login_authorized_modules(companyid,subcompanyid,projectid,moduleid,loginid,status,ispassword,password) VALUES('".$companyid."', '".$subcompanyid."', '".$authprojectid."', '".$aupmdlkey['moduleid']."', '".$userid."', 'active','1','".$modulepassword."')");
									}
								}
							}
						}else{
							$authconn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
						}						
					}
				}else{
					$authresult[]=array('status'=>'Error', 'message'=>'Project authorization is missing.');
				}				
			}else{
				$authresult[]=array('status'=>'Error', 'message'=>'User login authorization did not save, due to technical issue.');
			}
		}
	}else{
		$authresult[]=array('status'=>'Error', 'message'=>'User login id is missing, Please enter user id', 'userid'=>'');
	}
	echo json_encode($authresult);
}else if($action=="getdetailsfor_removeuserlogin"){
	$userid = $_POST['userid'];
	$username = $_POST['username'];
	$chklg=loginuserdetailsfrommaster($authconn,"","loginid",$userid);
	$uhtml="";
	if(count($chklg)>0){
		$uhtml.="<div class='row'><div class='col-md-12'><label>User Name</label><input type='text' class='form-control' value='".$username."' disabled></div></div>";
		$prssq=$authconn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where loginid='".$userid."' and status='active' group by companyid,subcompanyid,projectid order by projectid");
		if($prssq->num_rows>0){
			$uhtml.="<div class='row mt-4'>
				<div class='col-md-12'>
					<label>Authorized Project</label>
					<table class=class='table table-light table-bordered' id='authorizedproject_rm'>
						<thead>
							<tr>
								<th>#</th>
								<th>Project Name</th>
								<th>Sub-Company Name</th>
								<th>Company Name</th>
							</tr>
						</thead>
						<tbody>";
						while($ttp=$prssq->fetch_assoc()){
							$ppin=getprojectdetailwithcmpsubcmpWithL($conn,$ttp['companyid'],$ttp['subcompanyid'],"withauthrizedprojects",$ttp['projectid']);
							foreach ($ppin as $ppinkey) {
							$uhtml.="<tr data-rmcompanyid='".$ttp['companyid']."' data-rmsubcompanyid='".$ttp['subcompanyid']."' data-rmprojectid='".$ttp['projectid']."'>
								<td><input type='checkbox' class='rmprojecttd' checked /></td>
								<td>".$ppinkey['projectname']."</td>
								<td>".$ppinkey['subcompanyname']."</td>
								<td>".$ppinkey['companyname']."</td>
							</tr>";
							}
						}
						$uhtml.="</tbody>
					</table>
				</div>
			</div>";
		}
		$uhtml.="<div class='row mt-4'><div class='col-md-12'><input type='checkbox' id='confirmremoveuser' / > Are you confirm, want to remove the details of user?</div></div>";
		$uhtml.="<div class='row mt-4'><div class='col-md-12 text-center'><button type='button' class='btn btn-deepblue' onclick=\"update_removeuserlogin('hidden_baseurl','" . htmlspecialchars($userid)."','authorizedproject_rm','confirmremoveuser')\" data-rmuserid='".$userid."'>Remove Authorization From Master</button></div></div>";
	}
	echo $uhtml;
}else if($action=="update_removeuserlogin"){
	$userid = $_POST['userid'];
	$loginname = $_POST['loginname'];
	$authirzedprojects = json_decode($_POST['authorizedproject'],TRUE);
	$chklg=loginuserdetailsfrommaster($authconn,"","loginid",$userid);
	$authresult=array();
	if(count($chklg)>0){
		if(count($authirzedprojects)>0){
			foreach ($authirzedprojects as $ppjkey) {
				$prssq=$authconn->query("UPDATE login_authorized_projects SET status='removed on date".date('Y-m-d')." by ".$loginname."' where loginid='".$userid."' and status='active' and companyid='".$ppjkey['companyid']."' and subcompanyid='".$ppjkey['subcompanyid']."' and projectid='".$ppjkey['projectid']."'");
			}
		}
	}
	$prssq=$authconn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where loginid='".$userid."' and status='active' group by companyid,subcompanyid,projectid order by projectid");
	if($prssq->num_rows>0){
		$authresult=array('status'=>'Success', 'message'=>'selected Project removed form authorized project list successfully');
	}else{
		$authconn->query("UPDATE login_user_master SET isActive='no', tedit='removed' where loginid='".$userid."' and isActive='yes'");
		if(mysqli_affected_rows($authconn)>0){
			$authresult=array('status'=>'Success', 'message'=>'Removed the login details successfully');
		}else{
			$authresult=array('status'=>'Error', 'message'=>'Login details did not remove, due to somthing wrong');
		}
	}
	echo json_encode($authresult);
}else if($action=="updatedomainsubdonainToCompanyMaster"){
	$companyid   = isset($_POST['companyid']) ? intval($_POST['companyid']) : 0;
	$domainname  = isset($_POST['domainname']) ? trim($_POST['domainname']) : '';
	if ($companyid <= 0) {
	    echo json_encode(["status" => "error", "message" => "Invalid company ID"]);
	    exit;
	}
	if ($domainname === '') {
	    echo json_encode(["status" => "error", "message" => "Domain name cannot be empty"]);
	    exit;
	}

	$stmt = $conn->prepare("UPDATE tbl_company_master SET companyfoldername = ? WHERE companyid = ?");
	$stmt->bind_param("si", $domainname, $companyid);

	if ($stmt->execute()) {
	    if ($stmt->affected_rows > 0) {
	        echo json_encode(["status" => "success", "message" => "Domain name updated successfully"]);
	    } else {
	        echo json_encode(["status" => "warning", "message" => "No changes made (maybe same domain name)"]);
	    }
	} else {
	    echo json_encode(["status" => "error", "message" => "Update failed: " . $conn->error]);
	}
	$stmt->close();
	$conn->close();
}
?>