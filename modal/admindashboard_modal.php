<?php 
//modified by ajay 31-05-2025
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="addupdateprojectdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$projectname = $_POST['projectname'];
	$projectemailaddress = $_POST['projectemailaddress'];
	$isactivestatus = $_POST['isactivestatus'];
	$issubproject = $_POST['issubproject'];
	$loginname = $_POST['loginname'];
	$orgprojectid = $_POST['orgprojectid'];

	$subprojectlist = json_decode($_POST['subprojectlist'],TRUE);
	// print_r($subprojectlist);
	// die();
	$rrprj=array();
	$updprj=0;
	$updsubprj=0;
	if($projectid){
		$prjc=projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
		if(count($prjc)>0){
			//update user login basic details
			$conn->query("UPDATE tbl_project_master SET projectname='".addslashes($projectname)."', project_email='".addslashes($projectemailaddress)."', addedby='".addslashes($loginname)."', uploadflag=null where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
			if(mysqli_affected_rows($conn)>0){
				$updprj=1;
				insertDefaultModulesToProject($conn, $companyid, $subcompanyid, $orgprojectid, $companyid, $subcompanyid, $projectid);
			}
		}
		if(strtolower($issubproject)=="yes" && count($subprojectlist)>0){
			$updsbp="editupdate on date ".date('Y-m-d')." by ".$loginname;
			$conn->query("UPDATE tbl_projectsub_master SET tedit='".$updsbp."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
			foreach ($subprojectlist as $sbpkey) {
				$updsubprj=1;
				if($sbpkey['subprojectid']!="" && $sbpkey['subprojectid']!=""){
					$conn->query("UPDATE tbl_projectsub_master SET subprojectname='".addslashes($sbpkey['subprojectname'])."', tedit=null where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and subprojectid='".$sbpkey['subprojectid']."'");
				}else{
					$sck=$conn->query("SELECT (count(subprojectid) + 1) as maxid FROM tbl_projectsub_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
					$subprojectid="";
					if($sck->num_rows>0){
						$nmr=$sck->fetch_assoc();
						$subprojectid=$nmr['maxid'];
						$conn->query("INSERT INTO tbl_projectsub_master(companyid,subcompanyid,projectid,subprojectid,subprojectname,addedby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".addslashes($sbpkey['subprojectname'])."','".addslashes($loginname)."')");
					}
				}
			}
		}else{
			$updsubprj=1;
			$conn->query("INSERT INTO tbl_projectsub_master(companyid,subcompanyid,projectid,subprojectid,subprojectname,addedby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','0','".addslashes($projectname)."','".addslashes($loginname)."')");
		}
		if($updprj==1 && $updsubprj==1){
			$rrprj[]=array('status'=>'Success', 'message'=>'Project and sub-project details update successfully','prjid'=>$projectid);
		}else if($updprj==1 && $updsubprj==0){
			$rrprj[]=array('status'=>'Success', 'message'=>'Project details update successfully','prjid'=>$projectid);
		}else if($updprj==0 && $updsubprj==1){
			$rrprj[]=array('status'=>'Success', 'message'=>'Sub-project details update successfully','prjid'=>$projectid);
		}else{
			$rrprj[]=array('status'=>'Error', 'message'=>'Project or Sub-project did not update, due to somthing wrong','prjid'=>$projectid);
		}
	}else{
		//update user login basic details
		$projectid=GetMaxprojecidstdb($conn);
		$conn->query("INSERT INTO tbl_project_master(companyid, subcompanyid, projectid, projectname, project_email,addedby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".addslashes($projectname)."','".addslashes($projectemailaddress)."', '".addslashes($loginname)."')");
		$authprojectid="";
		if(mysqli_affected_rows($conn)>0){
			//add default purpose for withdrawn and deposit
			// insertdefaultdebitpurposecodes($conn,$companyid,$subcompanyid,$projectid);
			// insertdefaultcreditpurposecodes($conn,$companyid,$subcompanyid,$projectid);
			insertDefaultModulesToProject($conn, $companyid, $subcompanyid, $orgprojectid, $companyid, $subcompanyid, $projectid);

			if(strtolower($issubproject)=="yes" && count($subprojectlist)>0){
				$updsbp="editupdate on date ".date('Y-m-d')." by ".$loginname;
				$conn->query("UPDATE tbl_projectsub_master SET tedit='".$updsbp."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
				foreach ($subprojectlist as $sbpkey) {
					if(isset($sbpkey['subprojectid']) && $sbpkey['subprojectid']!=""){
						$conn->query("UPDATE tbl_projectsub_master SET subprojectname='".addslashes($sbpkey['subprojectname'])."',tedit=null where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and subprojectid='".$sbpkey['subprojectid']."'");
					}else{
						$sck=$conn->query("SELECT (count(subprojectid) + 1) as maxid FROM tbl_projectsub_master WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
						$subprojectid="";
						if($sck->num_rows>0){
							$nmr=$sck->fetch_assoc();
							$subprojectid=$nmr['maxid'];
							$conn->query("INSERT INTO tbl_projectsub_master(companyid,subcompanyid,projectid,subprojectid,subprojectname,addedby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".addslashes($sbpkey['subprojectname'])."','".addslashes($loginname)."')");
						}
					}
				}
			}else{
				$conn->query("INSERT INTO tbl_projectsub_master(companyid,subcompanyid,projectid,subprojectid,subprojectname,addedby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','0','".$projectname."','".$loginname."')");
			}
			$rrprj[]=array('status'=>'Success', 'message'=>'New Project details saved successfully','prjid'=>$projectid);
		}else{
			$rrprj[]=array('status'=>'Error', 'message'=>'New project did not save, due to technical issue.','prjid'=>'');
		}
	}
	echo json_encode($rrprj);
}else if($action=="removeprojectdetailsfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$rrprj=array();
	if($projectid){
		$prjc=projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
		if(count($prjc)>0){
			//update user login basic details
			$conn->query("UPDATE tbl_project_master SET tedit='".date('Y-m-d').' by '.$loginname."', uploadflag=null where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
			if(mysqli_affected_rows($conn)>0){
				$rrprj[]=array('status'=>'Success', 'message'=>'Project details remove from master successfully');
			}else{
				$rrprj[]=array('status'=>'Error', 'message'=>'Project details did not remove, due to somthing wrong');
			}						
		}else{
			$rrprj[]=array('status'=>'Error', 'message'=>'Project details did not available from master or already removed');
		}
	}else{
		$rrprj[]=array('status'=>'Error', 'message'=>'Project details did not selected for remove from master');
	}
	echo json_encode($rrprj);
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
	// print_r($authirzedprojects);
	$authresult=array();
	//check userid exist


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

			$lroleinfo=login_rolefrommaster($conn,"roleid",$loginroleid);
			$loginrolename="";
			foreach ($lroleinfo as $lrkey) {
				$loginrolename=$lrkey['rolename'];
			}
			//update user login basic details
			$conn->query("UPDATE login_user_master SET loginname='".$username."', loginroleid='".$loginroleid."', loginrolename='".$loginrolename."', mobileno='".$usermobileno."', emailid='".$useremail."', isActive='".$isactivestatus."' , departmentid='".$departmentofuser."'  where loginid='".$userid."'");
			if(mysqli_affected_rows($conn)>0){
				$authresult[]=array('status'=>'Success', 'message'=>'User login details update successfully', 'userid'=>$userid);
			}
			if(count($authirzedprojects)>0){
				//add/update authorized project
				$conn->query("UPDATE login_authorized_projects SET status='".date('Y-m-d h:i:s')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and loginid='".$userid."' and status='active'");

				foreach ($authirzedprojects as $auprjkey) {
					$authprojectid=$auprjkey['projectid'];
					$cq=$conn->query("SELECT projectid from login_authorized_projects where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
					if($cq->num_rows>0){
						$conn->query("UPDATE login_authorized_projects SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
					}else{
						if($auprjkey['projectid']!=""){
							$conn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$auprjkey['projectid']."', '".$userid."', 'active')");
							if(mysqli_affected_rows($conn)>0){
								$authresult[]=array('status'=>'Success', 'message'=>'Project authorization save successfully.');
							}
						}
					}

					//add or update modules
					if(count($authorizedmodules)>0 && $authprojectid!=""){
						$conn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
						foreach ($authorizedmodules as $aupmdlkey) {
							// moduleid
							$cq=$conn->query("SELECT moduleid from login_authorized_modules where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
							if($cq->num_rows>0){
								$conn->query("UPDATE login_authorized_modules SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
							}else{
								if($aupmdlkey['moduleid']){
									$conn->query("INSERT INTO login_authorized_modules(companyid,subcompanyid,projectid,moduleid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$authprojectid."', '".$aupmdlkey['moduleid']."', '".$userid."', 'active')");
								}
							}
						}
						$authresult[]=array('status'=>'Success', 'message'=>'Authorized modules update successfully', 'userid'=>$userid);
					}else{
						$conn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
					}
				}
			}else{
				$authresult[]=array('status'=>'Error', 'message'=>'Project authorization is missing.');
			}			
		}else{
			//new insert  
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
			$conn->query("INSERT INTO login_user_master(loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive,departmentid) VALUES('".$username."', '".$userid."', '".$loginpassword."', '".$loginroleid."', '".$loginrolename."', '".$usermobileno."', '".$useremail."', '".$isactivestatus."', '".$departmentofuser."')");
			$authprojectid="";
			if(mysqli_affected_rows($conn)>0){
				$authresult[]=array('status'=>'Success', 'message'=>'User login details saved successfully', 'userid'=>$userid);
				if(count($authirzedprojects)>0){
					//add/update authorized project
					$conn->query("UPDATE login_authorized_projects SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and loginid='".$userid."' and status='active'");
					foreach ($authirzedprojects as $auprjkey) {
						$authprojectid=$auprjkey['projectid'];
						$cq=$conn->query("SELECT projectid from login_authorized_projects where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
						if($cq->num_rows>0){
							$conn->query("UPDATE login_authorized_projects SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$auprjkey['projectid']."' and loginid='".$userid."'");
						}else{
							if($authprojectid){
								$conn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$auprjkey['projectid']."', '".$userid."', 'active')");
								if(mysqli_affected_rows($conn)>0){
									$authresult[]=array('status'=>'Success', 'message'=>'Project authorization save successfully.');
								}
							}
						}

						//add or update modules
						if(count($authorizedmodules)>0 && $authprojectid!=""){
							$conn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
							foreach ($authorizedmodules as $aupmdlkey) {
								// moduleid
								$cq=$conn->query("SELECT moduleid from login_authorized_modules where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
								if($cq->num_rows>0){
									$conn->query("UPDATE login_authorized_modules SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and moduleid='".$aupmdlkey['moduleid']."' and loginid='".$userid."'");
								}else{
									if($aupmdlkey['moduleid']){
										$conn->query("INSERT INTO login_authorized_modules(companyid,subcompanyid,projectid,moduleid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$authprojectid."', '".$aupmdlkey['moduleid']."', '".$userid."', 'active')");
									}
								}
							}
						}else{
							$conn->query("UPDATE login_authorized_modules SET status='".date('Y-m-d')."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$authprojectid."' and loginid='".$userid."' and status='active'");
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
}else if($action=="authorizedprojects_homehtmlview"){
	$loginid = $_POST['loginid'];
	$lgin=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	foreach ($lgin as $lgkey) {
		$loginroleid = $lgkey['loginroleid'];
	}
	$ccphtml="";
	if($loginroleid==0){
		$prj=getprojectdetailwithcmpsubcmp($conn,"","","all","");
	}else{
		$allcmp=allauthorizedcompanyid($conn,$loginid);
		$allsubcmp=allauthorizedsubcompanyid($conn,$loginid);
		$allprj=allauthorizedprojectid($conn,$loginid);
		$prj=getprojectdetailwithcmpsubcmp($conn,$allcmp,$allsubcmp,"withauthrizedprojects",$allprj);
	}
	echo json_encode($prj);
}else if($action=="projectlistforauthorizaton_html"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$prj=getprojectdetails($conn,$companyid,$subcompanyid,"all","");
	$prjht="";
	// print_r($prj);
	if(count($prj)>0){
		foreach ($prj as $prjkey) {
			$authprjlist=getauthorizedprojects($conn,$prjkey['companyid'],$prjkey['subcompanyid'],$prjkey['projectid'],$loginid,"authorizedprojectid",$prjkey['projectid']);
			$checkprj="";
			if(count($authprjlist)>0){
				foreach ($authprjlist as $aupkey) {
					$auprojectid=$aupkey['projectid'];
					$checkprj="checked";
				}
			}
			// else{
			// 	$checkprj="checked";
			// }
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
}else if($action=="modulelistforauthorizaton_html"){
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
			foreach ($authmdllist as $authmdl) {
			    if (isset($authmdl['moduleid']) && $authmdl['moduleid'] == $mdlkey['moduleid']) {
			        $exists = "checked";
			        $alreadyexist = "<i class='fa fa-check text-success'></i>";
			        break;
			    }
			}
			$mainModuleClass = str_replace(' ', '_', $mdlkey['mainmodule']);
		    if ($prevmainhead != $mdlkey['mainmodule']) {
		        $mdlht .= "<tr class='heading {$mdlkey['mainmodule']}'>
			            <td style='width:100px; text-align: center;' >
			                <input type='checkbox' class='modulecheckbox_main' id='main_{$mainModuleClass}' 
			                       onclick='toggleSubCheckboxes(\"{$mainModuleClass}\")' />
			            </td>
			            <td class='modulename_td'  style='font-weight: bold; font-size: large;'>{$mdlkey['mainmodule']} <input type='hidden' class='moduleid_input' value='' /></td>
			        </tr>";
		    }

		    $mdlht .= "<tr style='display:none;' class='details {$mdlkey['mainmodule']}'>
                    <td style='width:100px; text-align: center;'>
                        <input type='checkbox' class='sub_{$mainModuleClass}'  
                               onchange='checkMainCheckbox(\"{$mainModuleClass}\")' {$exists} /> 
                    </td>
                    <td class='modulename_td'>{$alreadyexist} {$mdlkey['modulename']}
                        <input type='hidden' class='moduleid_input' value='{$mdlkey['moduleid']}' />
                    </td>
                </tr>";
	   	    $prevmainhead = $mdlkey['mainmodule'];	
		}
	}else{
		$mdlht.="<tr><td style='text-align:center;' colspan='2'>Data not available</td></tr>";
	}
	echo $mdlht;
}else if($action=="modulelist_frommaster_registercompany_html"){
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

	$mmd=loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"allactive_mainaccount","");
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
			foreach ($authmdllist as $authmdl) {
			    if (isset($authmdl['moduleid']) && $authmdl['moduleid'] == $mdlkey['moduleid']) {
			        $exists = "checked";
			        $alreadyexist = "<i class='fa fa-check text-success'></i>";
			        break;
			    }
			}
			$mainModuleClass = str_replace(' ', '_', $mdlkey['mainmodule']);
		    if ($prevmainhead != $mdlkey['mainmodule']) {
		        $mdlht .= "<tr class='heading {$mdlkey['mainmodule']}'>
			            <td style='width:100px; text-align: center;' >
			                <input type='checkbox' class='modulecheckbox_main' id='main_{$mainModuleClass}' 
			                       onclick='toggleSubCheckboxes(\"{$mainModuleClass}\")' />
			            </td>
			            <td class='modulename_td'  style='font-weight: bold; font-size: large;'>{$mdlkey['mainmodule']} <input type='hidden' class='moduleid_input' value='' /></td>
			        </tr>";
		    }

		    $mdlht .= "<tr style='display:none;' class='details {$mdlkey['mainmodule']}'>
                    <td style='width:100px; text-align: center;'>
                        <input type='checkbox' class='sub_{$mainModuleClass}'  
                               onchange='checkMainCheckbox(\"{$mainModuleClass}\")' {$exists} /> 
                    </td>
                    <td class='modulename_td'>{$alreadyexist} {$mdlkey['modulename']}
                        <input type='hidden' class='moduleid_input' value='{$mdlkey['moduleid']}' />
                    </td>
                </tr>";
	   	    $prevmainhead = $mdlkey['mainmodule'];	
		}
	}else{
		$mdlht.="<tr><td style='text-align:center;' colspan='2'>Data not available</td></tr>";
	}
	echo $mdlht;
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
		$loginrole=login_rolefrommaster($conn,"allactive",$loginroleid);
	}else{
		$loginrole=login_rolefrommaster($conn,"afterloginroleid",$loginroleid);
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
	        $pppj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $lginkey['projectid']);
	        $projectname = $pppj[0]['projectname'] ?? '';

	        $strikecss = ($lginkey['isactive'] == "Dactive") ? "striketxt_red" : "";

	        // Append user details to HTML
	        $lghtml .= "<tr data-lgcompanyid='".$lginkey['companyid']."' data-lgsubcompanyid='".$lginkey['subcompanyid']."' data-lgprojectid='".$lginkey['projectid']."' data-loginid='".$lginkey['loginid']."' data-loginroleid='".$lginkey['loginroleid']."'>
	            <td class='text-center'>" . $ss++ . "</td>
	            <td class='lgloginid_td text-center {$strikecss}'>" . htmlspecialchars($lginkey['loginid']) . "</td>
	            <td class='lgname_td {$strikecss}'>" . htmlspecialchars($lginkey['loginname']) . "</td>
	            <td class='lgname_td {$strikecss}'>" . htmlspecialchars($lginkey['loginrolename']) . "</td>
	            <td class='lgmobileno_td {$strikecss}'>" . htmlspecialchars($lginkey['mobileno']) . "</td>
	            <td class='lgemail_td {$strikecss}'>" . htmlspecialchars($lginkey['emailid']) . "</td>
	            <td class='lgauthprj_td {$strikecss}'>" . htmlspecialchars($projectname) . "</td>
	            <td class='lgemail_td {$strikecss}'>" . htmlspecialchars($lginkey['departmentname']) . "</td>
	            <td class='lgedit_td text-center'>
	                <i class='fa fa-edit m-2 editlginfo' style='font-size: 28px; cursor:pointer;'></i>
	                <i class='fa fa-trash text-danger m-2 removelginfo' onclick=\"removeuserloginfrommasterhtml('hidden_routurl', '" . htmlspecialchars($lginkey['loginid']) . "', '" . htmlspecialchars($lginkey['loginname']) . "')\" style='font-size: 28px; cursor:pointer;'></i>
	            </td>
	        </tr>";
	    }
	} else {
	    $lghtml .= "<tr><td colspan='9' style='text-align:center;'>Data not available</td></tr>";
	}

	// Output the HTML
	echo $lghtml;
}else if($action=="getdetailsfor_removeuserlogin"){
	$userid = $_POST['userid'];
	$username = $_POST['username'];
	$chklg=loginuserdetailsfrommaster($authconn,"","loginid",$userid);
	$uhtml="";
	if(count($chklg)>0){
		$uhtml.="<div class='row'><div class='col-md-12'><label>User Name</label><input type='text' class='form-control' value='".$username."' disabled></div></div>";
		$prssq=$conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where loginid='".$userid."' and status='active' group by companyid,subcompanyid,projectid order by projectid");
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
							$ppin=getprojectdetailwithcmpsubcmp($conn,$ttp['companyid'],$ttp['subcompanyid'],"withauthrizedprojects",$ttp['projectid']);
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
		$uhtml.="<div class='row mt-4'><div class='col-md-12 text-center'><button type='button' class='btn btn-deepblue' onclick=\"update_removeuserlogin('hidden_routurl','" . htmlspecialchars($userid)."','authorizedproject_rm','confirmremoveuser')\" data-rmuserid='".$userid."'>Remove Authorization From Master</button></div></div>";
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
				$prssq=$conn->query("UPDATE login_authorized_projects SET status='removed on date".date('Y-m-d')." by ".$loginname."' where loginid='".$userid."' and status='active' and companyid='".$ppjkey['companyid']."' and subcompanyid='".$ppjkey['subcompanyid']."' and projectid='".$ppjkey['projectid']."'");
			}
		}
	}
	$prssq=$conn->query("select  companyid, subcompanyid, projectid FROM login_authorized_projects where loginid='".$userid."' and status='active' group by companyid,subcompanyid,projectid order by projectid");
	if($prssq->num_rows>0){
		$authresult=array('status'=>'Success', 'message'=>'selected Project removed form authorized project list successfully');
	}else{
		$conn->query("UPDATE login_user_master SET isActive='no', tedit='removed' where loginid='".$userid."' and isActive='yes'");
		if(mysqli_affected_rows($conn)>0){
			$authresult=array('status'=>'Success', 'message'=>'Removed the login details successfully');
		}else{
			$authresult=array('status'=>'Error', 'message'=>'Login details did not remove, due to somthing wrong');
		}
	}
	echo json_encode($authresult);
}else if($action=="logindetailswithloginid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$lginfo=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	echo json_encode($lginfo);	
}else if($action=="projectdetails_htmltableview"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$lginfo=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	$loginroleid ="";
	if(count($lginfo)>0){
		foreach ($lginfo as $lgckey) {
			$loginroleid = $lgckey['loginroleid'];
		}
	}
	$logininfo=array();
	$ppj=projectdetails($conn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
	$lghtml="";
	if(count($ppj)>0){
		$ss=1;
		foreach ($ppj as $prjkey) {
			$subprojects=subprojectdetails($conn,$companyid,$subcompanyid,$prjkey['projectid'],"all","");
			$subprojectname="N/A";
			if(count($subprojects)==1){
				foreach ($subprojects as $sbprkey) {
					$subprojectname=$sbprkey['subprojectname'];
				}
			}else if(count($subprojects)>1){
				$subprojectname=count($subprojects)." Sub-project";
			}
			$lghtml.="<tr data-projectiprjtd='".$prjkey['projectid']."'>
				<td class='text-center'>".$ss++."</td>
				<td class='prjname_td'>".$prjkey['projectname']."</td>
				<td class='subprjname_td' style='cursor:pointer; color:blue;'>".$subprojectname."</td>
				<td class='prjemail_td'>".$prjkey['projectemail']."</td>
				<td style='display:none;' class='prjid_td'>".$prjkey['projectid']."</td>
				<td style='display:none;' class='prjcmpid_td'>".$prjkey['companyid']."</td>
				<td style='display:none;' class='prjsbcmpid_td'>".$prjkey['subcompanyid']."</td>
				<td class='prjedit_td text-center'><i class='fa fa-edit editprjinfo' style='font-size: 28px; cursor:pointer;'></i> <i class='fa fa-trash removeprjinfo' style='font-size: 28px; cursor:pointer;'></i></td>
			</tr>";			
		}
	}else{
		$lghtml.="<tr><td colspan='4' style='text-align:center;'>data not available</td></tr>";
	}
	echo $lghtml;
}else if($action=="standingorder_htmltableview"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$stordlist=standingordermasterlist_new($conn,$companyid,$subcompanyid,$projectid,"allactive","");
	$stordhtml="";
	if(count($stordlist)>0){
		$ss=1;  
		foreach ($stordlist as $stdormkey) {
			$stordhtml.="<tr>
				<td class='text-center'>".$ss++."</td>
				<td >".$stdormkey['client_name']."</td>
				<td >".$stdormkey['standing_date']."</td>
				<td>".$stdormkey['termdays']."</td>
				<td>".$stdormkey['description']."</td> 
				<td style='text-align:center;'>".$stdormkey['invoiceamount']."</td> 

				<td>".$stdormkey['Remarks']."</td> 
				<td>".$stdormkey['purpose'].' ('.$stdormkey['purposecode'].')'."</td>

				<td class='text-center'><i class='fa fa-trash removeprjinfo' style='font-size: 28px; cursor:pointer;'></i></td>
				<td style='display:none;' class='clientid'>".$stdormkey['invoicetoid']."</td>
				<td style='display:none;' class='dbsno'>".$stdormkey['sno']."</td>
				<td style='display:none;' class='tdorderno'>".$stdormkey['stdorderno']."</td>
			</tr>";			
		}
	}else{
		$stordhtml.="<tr><td colspan='9' style='text-align:center;'>data not available</td></tr>";
	}
	echo $stordhtml;
}else if($action=="addupdate_standingorder"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$stdordno = $_POST['stdordno'];
	$stdordid = $_POST['stdordid'];
	$stdordname = $_POST['stdordname'];
	$stdordamount = $_POST['stdordamount'];
	$stdordstarddate = $_POST['stdordstarddate'];
	$stdordtermdays = $_POST['stdordtermdays'];
	$stdordnextdueddate = $_POST['stdordnextdueddate'];
	$isactivestatus = $_POST['isactivestatus'];
	$purposename = $_POST['purposename'];
	$purposecode = $_POST['stdordpurpose'];
	$description = $_POST['stdorddesc'];
	$savebyname = $_POST['savebyname'];
	$taxamount = $_POST['taxamount'];
	$remarks = $_POST['remarks'];
	$vatpercentage = $_POST['vatpercentage'];
	$gstpercentage = $_POST['gstpercentage'];
	$currencyid = $_POST['currencyid'];
	$authresult=array();
	//check userid exist
	if($stdordno!=""){
		$chklg=standingordermasterlist($conn,$companyid,$subcompanyid,$projectid,"standingorderno",$stdordno);
		if(count($chklg)>0){
			//update user login basic details
			$conn->query("UPDATE tbl_standingorder_master SET currencyid= '".$currencyid."', cgstpercent='".$gstpercentage."'  ,vatpercentage ='".$vatpercentage."',invoicetoid='".$stdordid."',invoiceto='".$stdordname."', rate='".$stdordamount."', invoiceamount='".$stdordamount."', termdays='".$stdordtermdays."', nextinvoicedate='".$stdordnextdueddate."',purpose='".addslashes($purposename)."',purposecode='".$purposecode."',description= '".addslashes($description)."',remark= '".addslashes($remarks)."', isactive='".$isactivestatus."', addedby='".addslashes($savebyname)."', taxamt='".$taxamount."' where stdorderno='".$stdordno."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
			if(mysqli_affected_rows($conn)>0){
				$authresult[]=array('status'=>'Success', 'message'=>'Standing order details update successfully', 'stdordno'=>$stdordno);
			}						
		}
	}else{
		//update user login basic details
		$standingorderno=date('Ymdhis');
		$conn->query("INSERT INTO tbl_standingorder_master(cgstpercent, vatpercentage, companyid,subcompanyid, projectid, stdorderno, invoicetoid, invoiceto, qty, rate, invoiceamount,invoicedate, termdays, nextinvoicedate, purpose, purposecode, remark, isactive, addedby,description, taxamt,currencyid) VALUES('".$gstpercentage."','".$vatpercentage."','".$companyid."','".$subcompanyid."','".$projectid."','".$standingorderno."','".$stdordid."','".addslashes($stdordname)."', '1', '".addslashes($stdordamount)."', '".$stdordamount."', '".$stdordstarddate."', '".$stdordtermdays."', '".$stdordnextdueddate."', '".addslashes($purposename)."', '".$purposecode."', '".$remarks."', '".$isactivestatus."', '".addslashes($savebyname)."', '".addslashes($description)."', '".$taxamount."',  '".$currencyid."')");
		$authprojectid="";
		if(mysqli_affected_rows($conn)>0){
			$authresult[]=array('status'=>'Success', 'message'=>'Standing order details saved successfully', 'standingorderno'=>$standingorderno);
		}else{
			$authresult[]=array('status'=>'Error', 'message'=>'Standing order did not save, due to technical issue.');
		}
	}
	echo json_encode($authresult);
}else if ($action=="savetodbforeginexchangeservice") {
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$bankaccountname = $_POST['bankaccountname'];
	$bankaccountname_id = $_POST['bankaccountname_id'];
	$categoryofservice = $_POST['categoryofservice'];
	$hsnnumber = $_POST['hsnnumber'];
	$billnumber = $_POST['billnumber'];
	$currency = $_POST['currency'];
	$billamount = $_POST['billamount'];
	$invoicenumberinforeign = $_POST['invoicenumberinforeign'];
	$paymentstatus = $_POST['paymentstatus'];
	$eventdate = $_POST['eventdate'];
	$clientmaster = $_POST['clientmaster'];
	$additionalinfo = $_POST['additionalinfo'];
	$additionalinfo2 = $_POST['additionalinfo2'];
	$cgstwithdrawnamount = $_POST['cgstwithdrawnamount'];
	$sgstwithdrawnamount = $_POST['sgstwithdrawnamount'];
	$bankchargeinrupees = $_POST['bankchargeinrupees'];
	$bankaccountname2 = $_POST['bankaccountname2'];
	$bankaccountname2_id = $_POST['bankaccountname2_id'];
	$creditamountinforeign = $_POST['creditamountinforeign'];
	$exchangerate = $_POST['exchangerate'];
	$amountcreditinindianrupes = $_POST['amountcreditinindianrupes'];
	$editadvoiceno = $_POST['editadvoiceno'];
	$filepath="";
	$authresult=array();
	$maxsno=0;
	if ($editadvoiceno) {
		
		$updateqry=$conn->query("Update tbl_foreign_remittance_taxinvoice_creditdebitadvice set Receivedinaccount_id='".$bankaccountname_id."',Receivedinaccount_name='".$bankaccountname."',CategoryofService='".$categoryofservice."',HSNnumber='".$hsnnumber."',BillnumberOrRemittanceID='".$billnumber."',event_date='".$eventdate."',event_type='".$paymentstatus."',Currency='".$currency."',BillAmount='".$billamount."',Invoicenumberinforeigncurrency='".$invoicenumberinforeign."',InwardRemittancereceivedfromclient='".$clientmaster."',Additionalinformation_1='".$additionalinfo."',Additionalinformation_2='".$additionalinfo2."',CGSTwithdrawnAmount='".$cgstwithdrawnamount."',SGSTWithdrawnAmount='".$sgstwithdrawnamount."',BankChargesinRupees='".$bankchargeinrupees."',WithdrawnfromBankAccount_id='".$bankaccountname2_id."',WithdrawnfromBankAccount_name='".$bankaccountname2."',CreditAmountinforeigncurrency='".$creditamountinforeign."',ExchangeRate='".$exchangerate."',AmountCreditedinIndianRupees='".$amountcreditinindianrupes."' where crdr_adviceno='".$editadvoiceno."'");
			if(mysqli_affected_rows($conn)>0){
				$authresult[]=array('status'=>'Success', 'message'=>'Advice details update successfully', 'advice'=>$editadvoiceno);
				}else{
					$authresult[]=array('status'=>'Error', 'message'=>'Advice details did not save, due to technical issue.');
			}

	}else{

		if (isset($_FILES['pdffiles']) && !$_FILES['pdffiles']['error']) 
		{
	        
	        $receiptpathDir = '../ForeignRemittanceTaxInvoice/CreditDebitAdvice/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/'.date('Ymd').'/';
	        
	        // Create the uploads directory if it doesn't exist
	        if (!is_dir($receiptpathDir)) {
	            mkdir($receiptpathDir, 0755, true);
	        }
	        
	        //file name
	        $pdftemp = explode(".", $_FILES["pdffiles"]["name"]);
			$pdfreceiptname = round(microtime(true)) . '.' . end($pdftemp);
	        $pdfuploadFile = $receiptpathDir . $pdfreceiptname;
	        $pdfreceiptpath = str_replace("../", "", $pdfuploadFile);
	        $filepath=$pdfreceiptpath;
	        move_uploaded_file($_FILES['pdffiles']['tmp_name'], $pdfuploadFile);
		    
	    }
		$qrymxsno=$conn->query("Select max(sno+1) as maxsno from tbl_foreign_remittance_taxinvoice_creditdebitadvice");
		if ($qrymxsno->num_rows>0) {
			$rowm=$qrymxsno->fetch_assoc();
			if (is_null($rowm['maxsno'])) {
				$maxsno=1;
			}else{
				$maxsno=$rowm['maxsno'];
			}
		}
		$crdradviceno=$companyid."_".$subcompanyid."_".$projectid."_adviceno_".$maxsno;
	
		$insertqry=$conn->query("Insert into tbl_foreign_remittance_taxinvoice_creditdebitadvice(companyid,subcompanyid,projectid,crdr_adviceno,ReceivingCompany,Receivedinaccount_id,Receivedinaccount_name,CategoryofService,HSNnumber,BillnumberOrRemittanceID,event_date,event_type,Currency,BillAmount,Invoicenumberinforeigncurrency,InwardRemittancereceivedfromclient,Additionalinformation_1,Additionalinformation_2,CGSTwithdrawnAmount,SGSTWithdrawnAmount,BankChargesinRupees,WithdrawnfromBankAccount_id,WithdrawnfromBankAccount_name,CreditAmountinforeigncurrency,ExchangeRate,AmountCreditedinIndianRupees,filepath)values('".$companyid."','".$subcompanyid."','".$projectid."','".$crdradviceno."','".$companyid."','".$bankaccountname_id."','".$bankaccountname."','".$categoryofservice."','".$hsnnumber."','".$billnumber."','".$eventdate."','".$paymentstatus."','".$currency."','".$billamount."','".$invoicenumberinforeign."','".$clientmaster."','".$additionalinfo."','".$additionalinfo2."','".$cgstwithdrawnamount."','".$sgstwithdrawnamount."','".$bankchargeinrupees."','".$bankaccountname2_id."','".$bankaccountname2."','".$creditamountinforeign."','".$exchangerate."','".$amountcreditinindianrupes."','".$filepath."')");

			if(mysqli_affected_rows($conn)>0){
			$authresult[]=array('status'=>'Success', 'message'=>'Advice details saved successfully', 'advice'=>$crdradviceno);
			}else{
				$authresult[]=array('status'=>'Error', 'message'=>'Advice details did not save, due to technical issue.');
			}


	}
	


	echo json_encode($authresult);	
} else if($action=="getdepartments"){ 
	$lginfo=departmentmaster($conn);
	echo json_encode($lginfo);	
 }else if($action=="delete_standingorder_htmltableview"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$stdordno = $_POST['stdordno'];
	$dbsno = $_POST['dbsno'];
	$clientid = $_POST['clientid'];
	$sql="update tbl_standingorder_master set isactive='delete' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'  and stdorderno='".$stdordno."'  and invoicetoid='".$clientid."'  and sno='".$dbsno."' ";
	$conn->query($sql);
}else if($action=="groupoftitlehtml"){
	$gptt=grouptitleofmodule($conn, "all", "");
	$uhtml="";
	if(count($gptt)>0){
		$uhtml.="<div class='row'>
			<div class='col-md-12 text-center'>
				<h4>Group Titles of Modules</h4>
			</div>
		</div>";
		$uhtml.="<div class='row mt-4'>
			<div class='col-md-12'>
				<label>Authorized Project</label>
				<table class=class='table-light table-bordered' id='grouptitleviewtbl'>
					<thead>
						<tr>
							<th style='width:50px; text-align:center;'>S.No</th>
							<th>Group Title Name</th>
							<th style='width:100px; text-align:center;'>Action</th>
						</tr>
					</thead>
					<tbody>";
					$sno=1;
					foreach ($gptt as $gpttkey) {
						$uhtml.="<tr data-groupttlid='".$gpttkey['groupid']."' data-groupttlname='".$gpttkey['groupname']."' data-groupttlsno='".$gpttkey['sno']."'>
							<td style='width:50px; text-align:center;'>".$sno++."</td>
							<td><input type='text' class='form-control grouptitltd' value='".$gpttkey['groupname']."' disabled /></td>
							<td style='width:100px; text-align:center;'>
				                <i class='fa fa-edit m-2 editgrpttl' style='font-size: 28px; cursor:pointer;'></i>
				                <i class='fa fa-trash text-danger m-2 removegrpttl' onclick=\"addupdate_grpttl('hidden_routurl', '" . htmlspecialchars($gpttkey['groupid']) . "', 'remove')\" style='font-size: 28px; cursor:pointer;'></i>
				            </td>
						</tr>";
						}
					$uhtml.="</tbody>
					<tfoot>
						<tr class='mt-2'>
							<td colspan='3'><button class='btn btn-sm btn-deepblue pull-right' onclick=\"addRow('grouptitleviewtbl')\"><i class='fa fa-plus'></i>New Group Title</button></td>
						</tr>
					</tfoot>
				</table>
			</div>
		</div>";
	}
	echo $uhtml;
}else if($action=="addupdate_grpttlhtml"){
	$grouptitleid = $_POST['grouptitleid'];
	$action = $_POST['action'];
	$actionLabels = [
	    "new" => "New Group Title Name",
	    "edit" => "Edit of Group Title Name",
	    "remove" => "Remove of Group Title Name"
	];

	$gttilelbl = $actionLabels[$action] ?? "Unknown Action";
	$gptt=grouptitleofmodule($conn, "groupid", $grouptitleid);
	$uhtml="";
	if(count($gptt)>0){
		foreach ($gptt as $gpttkey) {
		$uhtml.="<div class='row'>
			<div class='col-md-12 text-center'>
				<h4>".$gttilelbl." :- ".$gpttkey['groupname']."</h4>
			</div>
		</div>";
		$uhtml.="<div class='row mt-2'>
			<div class='col-md-12'>
				<label>Active Group Title With Modules</label>
				<input type='text'
				<table class=class='table-light table-bordered' id='activegrpttl_rm'>
					<thead>
						<tr>
							<th style='width:50px; text-align:center;'>S.No</th>
							<th>Module Name</th>
							<th>#</th>
						</tr>
					</thead>
					<tbody>";
					$actmdl=loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"groupid",$grouptitleid);
					$sno=1;
					foreach ($actmdl as $actmdlr) {					

						$uhtml.="<tr data-groupttlsno='".$gpttkey['sno']."' data-groupttlid='".$gpttkey['groupid']."' data-groupmoduleid='".$actmdlr['moduleid']."'>
							<td style='width:50px; text-align:center;'>".$sno++."</td>
							<td>".$actmdlr['modulename']."</td>
							<td>".$actmdlr['modulefoldername']."</td>
						</tr>";
						}
					$uhtml.="</tbody>
				</table>
			</div>
		</div>";
		$uhtml.="<div class='row mt-4'><div class='col-md-12'><input type='checkbox' id='confirmremovegrpttl' / > Are you confirm, want to remove the details of group title?</div></div>";
		$uhtml.="<div class='row mt-4'><div class='col-md-12 text-center'><button type='button' class='btn btn-deepblue' onclick=\"addupdate_grpttl('hidden_routurl','" . htmlspecialchars($grouptitleid)."','confirmremovegrpttl')\" data-rmuserid='".$userid."'>Remove Authorization From Master</button></div></div>";
		}
	}
	echo $uhtml;
}else if($action=="moduleswithgrouphtml"){
	$gptt=grouptitleofmodule($conn, "all", "");
	// print_r($gptt);
	$tbltr="";
	$sno=1;
	if (count($gptt) > 0) {
	    $sno = 1;
	    foreach ($gptt as $grpkey) {
	        $lgmd = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid, "groupid", $grpkey['groupid']);
	        if (count($lgmd) > 0) {
	            foreach ($lgmd as $lgmkey) {
	                $groption = "";
	                if (count($gptt) > 1) {
	                    $groption .= "<option value='' selected disabled>select group</option>";
	                }
	                foreach ($gptt as $ggkey) {
	                    $selected = $ggkey['groupid'] === $grpkey['groupid'] ? "selected" : "";
	                    $groption .= "<option value='" . $ggkey['groupid'] . "' $selected>" . $ggkey['groupname'] . "</option>";
	                }

	                $tbltr .= "<tr data-grpid='".$lgmkey['groupid']."' data-mldid='".$lgmkey['moduleid']."' data-mldsno='".$lgmkey['sno']."'>
	                    <td>" . $sno . "</td>
	                    <td><input type='text' class='form-control emodulename' value='" . $lgmkey['modulename'] . "' disabled /></td>
	                    <td><input type='text' class='form-control emodulefilepath' value='" . $lgmkey['modulefoldername'] . "' disabled /></td>
	                    <td>
	                        <select class='form-control egroupname'  disabled>" . $groption . "</select>
	                    </td>
	                    <td class='text-center'>
	                        <label><input type='radio' class='typeofIntegration m-2' name='typeofIntegration_".$lgmkey['moduleid']."' value='integration'  " . (($lgmkey['integrationtype'] != 'nonintegration') ? 'checked' : '') . " /> Yes</label>
	                        <label><input type='radio' class='typeofIntegration m-2' name='typeofIntegrationtypeofIntegration_".$lgmkey['moduleid']."' value='nonintegration' " . (($lgmkey['integrationtype'] == 'nonintegration') ? 'checked' : '') . " /> No</label>

	                    </td>
	                    <td>
	                    	<i class='fa fa-trash removemodule m-2' style='font-size:22px; color: red; cursor:pointer;'></i>
	                    	<i class='fa fa-edit editmodule m-2' style='font-size:22px; color: blue; cursor:pointer;'></i>
	                    	<i class='fa fa-plus-circle addblackrowmodule m-2' style='font-size:24px; color: green; cursor:pointer;'></i>
	                    	
	                    	<i class='fa fa-save editsavemodule m-2' style='font-size:24px; color: green; cursor:pointer; display:none;'></i>
	                    	<i class='fa fa-undo undomodule m-2' style='font-size:22px; color: red; cursor:pointer; display:none;'></i>
	                    </td>
	                </tr>";

	                $sno++; // Increment serial number
	            }
	        }
	    }
	}
	echo  $tbltr;
}else if($action=="addupdate_moduleswithgroup"){
	$moduleid = $_POST['moduleid'];
	$modulename = $_POST['modulename'];
	$modulefoldername = $_POST['modulefilepath'];
	$groupid = $_POST['groupid'];
	$actionfordo = $_POST['actionfordo'];
	$integrationtype = $_POST['integrationtype'];
	

	// PAYMENT TRANSFER REPORT
	$message = [];
	$filepathcheck="../".$modulefoldername;
	// Check if the file exists
	if (!file_exists($filepathcheck) && $actionfordo!=="remove" && $integrationtype!=="nonintegration") {
	    echo json_encode(array("status" => "Error", "message" => "The specified module file path does not exist."));
	    exit; // Exit to prevent further processing
	}

	// Fetch existing module information
	$mdinf = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid, "moduleid", $moduleid);
	// If module ID exists and is not empty, update the module
	$tedit ="";
	$msg = "";
	if (count($mdinf) > 0 && $moduleid!="") {
		if($actionfordo=="edit"){
			$tedit = "active";
			$msg = "Module details updated successfully.";
		}else if($actionfordo=="remove"){
			$tedit = 'deactive';
			$msg = "Module details removed successfully.";
		}

	    $stmt = $conn->prepare("UPDATE login_modules_master SET groupid = ?, modulename = ?, modulefoldername = ?, status = ? WHERE moduleid = ?");
	    $stmt->bind_param("sssss", $groupid, addslashes($modulename), addslashes($modulefoldername), $tedit, $moduleid);
	    $stmt->execute();
	    if ($stmt->affected_rows > 0) {
	    	$stmt1 = $conn->prepare("UPDATE login_modules_master_forcompany SET modulename = ?, modulefoldername = ?, status = ?, integrationtype=? WHERE moduleid = ?");
		    $stmt1->bind_param("sssss", addslashes($modulename), addslashes($modulefoldername), $tedit, $integrationtype, $moduleid);
		    $stmt1->execute();

	        $message = array("status" => "Success", "message" => $msg);
	    }else {
	        $message = array("status" => "Error", "message" => "No changes were made. Either the data is already updated, or an error occurred during the update.--".$moduleid.'--'.$tedit);
	    }
	    $stmt->close();
	} else {
	    // Check if the module name already exists (case-insensitive)
	    $check_stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_modules_master WHERE LCASE(modulename) = ?");
	    $modulename_lower = strtolower($modulename);
	    $check_stmt->bind_param("s", $modulename_lower);
	    $check_stmt->execute();
	    $result = $check_stmt->get_result();
	    $row = $result->fetch_assoc();
	    $check_stmt->close();

	    if ($row['cnt'] > 0) {
	        echo json_encode(array("status" => "Error", "message" => "The module name already exists. Please choose a different name."));
	        exit;
	    }

	    // Fetch the next available module ID
	    $ssm = $conn->query("SELECT (IFNULL(MAX(moduleid), 0) + 1) AS maxmodid FROM login_modules_master");
	    $mrow = $ssm->fetch_assoc();
	    $newmoduleid = $mrow['maxmodid'];

	    // Insert the new module
	    $stmt = $conn->prepare("INSERT INTO login_modules_master (groupid, moduleid, modulename, modulefoldername, status, integrationtype) VALUES (?, ?, ?, ?, ?, ?)");
	    $status = 'active';
	    $stmt->bind_param("ssssss", $groupid, $newmoduleid, addslashes($modulename), addslashes($modulefoldername), $status, $integrationtype);
	    $stmt->execute();

	    if ($stmt->affected_rows > 0) {
	        $message = array("status" => "Success", "message" => "New Module details saved successfully.");
	    } else {
	        $message = array("status" => "Error", "message" => "Failed to save the new module details. Please try again.");
	    }
	    $stmt->close();
	}
	echo json_encode($message);
}else if($action=="grouptitlelist_callback"){
	$gptt=grouptitleofmodule($conn, "all", "");
	echo json_encode($gptt);
}else if($action=="savemodulewithgroupforcompany"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$groupid = $_POST['groupid'];
	$subgroupid ="";
	$authmdsno = json_decode($_POST['authmdsno'],true);
	$response = [];
	if(count($authmdsno)>0){
		foreach ($authmdsno as $kaaey) {
			$lgmd=loginmodulesmasterlist($conn,"sno",$kaaey['sno']);
			if(count($lgmd)>0){
				foreach ($lgmd as $lgmdkey) {
					$response = saveModuleDetailsToCompany($conn, $companyid, $subcompanyid, $projectid, $lgmdkey['vieworderby'], $groupid, $subgroupid, $lgmdkey['moduleid'], $lgmdkey['modulename'], $lgmdkey['modulefoldername'], $lgmdkey['subaction'], $lgmdkey['status'], $lgmdkey['frmid'], $lgmdkey['ispasswordprotected']);
				}
			}
		}
		echo json_encode($response);
	}
}else if($action=="onlyprojectmoduleswithgrouphtml"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	// $gptt=grouptitleofmodule($conn, "all", "");
	$gptt=Onlyproject_grouptitleofmodule($conn,$companyid,$subcompanyid,$projectid,"all","all");
	// print_r($gptt);
	$tbltr="";
	$sno=1;
	if (count($gptt) > 0) {
	    $sno = 1;
	    foreach ($gptt as $grpkey) {
	        $lgmd = OnlyProject_loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"groupid", $grpkey['groupid']);
	        if (count($lgmd) > 0) {
	            foreach ($lgmd as $lgmkey) {
	                $groption = "";
	                if (count($gptt) > 1) {
	                    $groption .= "<option value='' selected disabled>select group</option>";
	                }
	                foreach ($gptt as $ggkey) {
	                    $selected = $ggkey['groupid'] === $grpkey['groupid'] ? "selected" : "";
	                    $groption .= "<option value='" . $ggkey['groupid'] . "' $selected>" . $ggkey['groupname'] . "</option>";
	                }
	                $tbltr .= "<tr data-grpid='".$lgmkey['groupid']."' data-mldid='".$lgmkey['moduleid']."' data-mldsno='".$lgmkey['sno']."'>
	                    <td>" . $sno . "</td>
	                    <td><input type='text' class='form-control emodulename' value='" . $lgmkey['modulename'] . "' disabled /></td>
	                    <td><input type='text' class='form-control emodulefilepath' value='" . $lgmkey['modulefoldername'] . "' disabled /></td>
	                    <td>
	                        <select class='form-control egroupname'  disabled>" . $groption . "</select>
	                    </td>
	                    <td>
	                    	<i class='fa fa-trash removemodule m-2' style='font-size:22px; color: red; cursor:pointer;'></i>
	                    	<i class='fa fa-edit editmodule m-2' style='font-size:22px; color: blue; cursor:pointer;'></i>
	                    	<i class='fa fa-plus-circle addblackrowmodule m-2' style='font-size:24px; color: green; cursor:pointer;'></i>
	                    	
	                    	<i class='fa fa-save editsavemodule m-2' style='font-size:24px; color: green; cursor:pointer; display:none;'></i>
	                    	<i class='fa fa-undo undomodule m-2' style='font-size:22px; color: red; cursor:pointer; display:none;'></i>
	                    </td>
	                </tr>";

	                $sno++; // Increment serial number
	            }
	        }
	    }
	}
	echo  $tbltr;
}

function saveModuleDetailsToCompany($conn, $companyid, $subcompanyid, $projectid, $vieworderby, $groupid, $subgroupid, $moduleid, $modulename, $modulefoldername, $subaction, $status, $frmid, $ispasswordprotected) {
    // Check if module ID already exists for the given company, subcompany, and project
    $checkSql = "SELECT COUNT(*) FROM login_modules_master_forcompany 
                 WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND moduleid = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("iiii", $companyid, $subcompanyid, $projectid, $moduleid);
    $checkStmt->execute();
    $checkStmt->bind_result($count);
    $checkStmt->fetch();
    $checkStmt->close();
    if ($count > 0) {
        // Update existing record
        $sql = "UPDATE login_modules_master_forcompany 
                SET vieworderby = ?, groupid = ?, subgroupid = ?, 
                    modulename = ?, modulefoldername = ?, subaction = ?, status = ?, frmid = ?, ispasswordprotected =?
                WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND moduleid = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiissssssssii", 
            $vieworderby, $groupid, $subgroupid, 
            $modulename, $modulefoldername, $subaction, $status, $frmid, $ispasswordprotected, 
            $companyid, $subcompanyid, $projectid, $moduleid
        );

        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Record updated successfully!'];
        } else {
            return ['status' => 'error', 'message' => 'Error updating record: ' . $stmt->error];
        }
    } else {
        // Insert new record
        $sql = "INSERT INTO login_modules_master_forcompany 
                (companyid, subcompanyid, projectid, vieworderby, groupid, subgroupid, moduleid, 
                modulename, modulefoldername, subaction, status, frmid, ispasswordprotected) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiissssssss", 
            $companyid, $subcompanyid, $projectid, $vieworderby, $groupid, $subgroupid, $moduleid, 
            $modulename, $modulefoldername, $subaction, $status, $frmid, $ispasswordprotected
        );

        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Record inserted successfully!'];
        } else {
            return ['status' => 'error', 'message' => 'Error inserting record: ' . $stmt->error];
        }
    }
}

function login_nonintegrated($conn){
	 
	 $lmd="SELECT     id as moduleid, m.modulename, m.loginusertablename as loginusertablename, 'Non Intergated System' as groupname FROM login_nonintegrated_modules_master m  WHERE m.status='active' order by m.vieworderby";	
	$lmdr=$conn->query($lmd);
	$mdlist=array();
	if($lmdr->num_rows>0){
		while($mrow=$lmdr->fetch_assoc()){
			$mdlist[]=array('groupname'=>$mrow['groupname'], 'moduleid'=>$mrow['moduleid'], 'modulename'=>$mrow['modulename'], 'loginusertablename'=>$mrow['loginusertablename']);
		}
	}
	return $mdlist;
}

?>