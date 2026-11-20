<?php
include("../../connection.php");
include("../functions/module_functions.php");
$action=$_GET['action'];
if($action=="savetodbnewClientGroupdetails"){
	$companyid =  $_POST['companyid']??0;
	$subcompanyid =  $_POST['subcompanyid']??0;
	$projectid =  $_POST['projectid']??0;
	$groupId =  $_POST['groupId']??0;
  	$groupName =  $_POST['groupName'];
  	$data = [
	    'companyid' => $companyid,
	    'subcompanyid' => $subcompanyid,
	    'projectid' => $projectid,
	    'groupid' => $groupId,
	    'groupname' => $groupName,
	    'status' => 'active'
	];
	$result = saveOrUpdateClientGroup($authconn, $data);
	echo json_encode($result);
}else if($action=="DeactivClientGroupFromMaster"){
	$companyid     = $_POST['companyid'] ?? 0;
	$subcompanyid  = $_POST['subcompanyid'] ?? 0;
	$projectid     = $_POST['projectid'] ?? 0;
	$groupid      = $_POST['groupid'] ?? 0;
	$response = [];
	$sql = "UPDATE login_group_master_client SET status = 'deactive' 
	        WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND groupid = ?";
	$stmt = $authconn->prepare($sql);
	if (!$stmt) {
		$response = ['status' => 'error', 'message' => 'Prepare failed: ' . $authconn->error];
	} else {
	    $stmt->bind_param("ssss", $companyid, $subcompanyid, $projectid, $groupid);
	    if ($stmt->execute()) {
	        if ($stmt->affected_rows > 0) {
	        	$ustmt = $authconn->prepare("UPDATE login_modules_master_client SET status = 'groupdeactive'
			    WHERE groupid = ? AND companyid = ? AND projectid = ?? AND status = 'active'");
				$ustmt->bind_param("sss", $groupid, $companyid, $projectid);
				$ustmt->execute();

	            $response = ['status' => 'success', 'message' => 'Group deactivated successfully.'];
	        } else {
	            $response = ['status' => 'warning', 'message' => 'No group found or already deactivated.'];
	        }
	    } else {
	        $response = ['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error];
	    }
	    $stmt->close();
	}
	echo json_encode($response);
}else if($action=="ActivClientGroupFromMaster"){
	$companyid     = $_POST['companyid'] ?? 0;
	$subcompanyid  = $_POST['subcompanyid'] ?? 0;
	$projectid     = $_POST['projectid'] ?? 0;
	$groupid      = $_POST['groupid'] ?? 0;
	$response = [];
	$sql = "UPDATE login_group_master_client 
	        SET status = 'active' 
	        WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND groupid = ?";
	$stmt = $authconn->prepare($sql);
	if (!$stmt) {
	    $response = ['status' => 'error', 'message' => 'Prepare failed: ' . $authconn->error];
	} else {
	    $stmt->bind_param("ssss", $companyid, $subcompanyid, $projectid, $groupid);
	    if ($stmt->execute()) {
	        if ($stmt->affected_rows > 0) {
	        	$ustmt = $authconn->prepare("UPDATE login_modules_master_client SET status = 'active'
			    WHERE groupid = ? AND companyid = ? AND projectid = ? AND status = 'groupdeactive'");
				$ustmt->bind_param("sss", $groupid, $companyid, $projectid);
				$ustmt->execute();
	            $response = ['status' => 'success', 'message' => 'Group activated successfully.'];
	        } else {
	            $response = ['status' => 'warning', 'message' => 'No group found or already activated.'];
	        }
	    } else {
	        $response = ['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error];
	    }
	    $stmt->close();
	}
	echo json_encode($response);
}else if($action=="savetodbnewClientModuledetails"){
	$companyid =  $_POST['companyid']??0;
	$subcompanyid =  $_POST['subcompanyid']??0;
	$projectid =  $_POST['projectid']??0;
  	$moduleId =  $_POST['moduleId']??0;
  	$groupId = $_POST['groupId']??0;
  	$moduleName =  $_POST['moduleName'];
	$moduleURL =  $_POST['moduleURL'];
	$purposetext = $_POST['modulePurpose'];
	$moduleAlign = $_POST['moduleAlign'];
	$moduleSize = $_POST['moduleSize'];
	$ispasswordprotected = $_POST['ispasswordprotected'];
	$data = [
		'companyid' => $companyid,
	    'subcompanyid' => $subcompanyid,
	    'projectid' => $projectid,
	    'groupid' => $groupId,
	    'moduleid' => $moduleId,
	    'modulename' => $moduleName,
	    'modulefoldername' => $moduleURL,
	    'purposetext' => $purposetext,
	    'modulealign' => $moduleAlign,
	    'modulesize' => $moduleSize,
	    'ispasswordprotected' => $ispasswordprotected,
	    'integrationtype' => 'internal',
	    'status' => 'active'
	];
	$result = saveOrUpdateClientModule($authconn, $data);
	echo json_encode($result);
}else if($action=="DeactivClientModulesFromMaster"){
	$companyid     = $_POST['companyid'] ?? 0;
	$subcompanyid  = $_POST['subcompanyid'] ?? 0;
	$projectid     = $_POST['projectid'] ?? 0;
	$moduleid      = $_POST['moduleid'] ?? 0;
	$response = [];
	$sql = "UPDATE login_modules_master_client 
	        SET status = 'deactive' 
	        WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND moduleid = ?";
	$stmt = $authconn->prepare($sql);
	if (!$stmt) {
	    $response = ['status' => 'error', 'message' => 'Prepare failed: ' . $authconn->error];
	} else {
	    $stmt->bind_param("ssss", $companyid, $subcompanyid, $projectid, $moduleid);
	    if ($stmt->execute()) {
	        if ($stmt->affected_rows > 0) {
	            $response = ['status' => 'success', 'message' => 'Module deactivated successfully.'];
	        } else {
	            $response = ['status' => 'warning', 'message' => 'No module found or already deactivated.'];
	        }
	    } else {
	        $response = ['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error];
	    }
	    $stmt->close();
	}
	echo json_encode($response);
}else if($action=="ActivClientModulesFromMaster"){
	$companyid     = $_POST['companyid'] ?? 0;
	$subcompanyid  = $_POST['subcompanyid'] ?? 0;
	$projectid     = $_POST['projectid'] ?? 0;
	$moduleid      = $_POST['moduleid'] ?? 0;
	$response = [];
	$sql = "UPDATE login_modules_master_client 
	        SET status = 'active' 
	        WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND moduleid = ?";
	$stmt = $authconn->prepare($sql);
	if (!$stmt) {
	    $response = ['status' => 'error', 'message' => 'Prepare failed: ' . $authconn->error];
	} else {
	    $stmt->bind_param("ssss", $companyid, $subcompanyid, $projectid, $moduleid);
	    if ($stmt->execute()) {
	        if ($stmt->affected_rows > 0) {
	            $response = ['status' => 'success', 'message' => 'Module activated successfully.'];
	        } else {
	            $response = ['status' => 'warning', 'message' => 'No module found or already activated.'];
	        }
	    } else {
	        $response = ['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error];
	    }
	    $stmt->close();
	}
	echo json_encode($response);
}else if($action=="GetClientGroupsDetailsFromMaster"){
	$companyid = $_POST['companyid']??0;
	$subcompanyid = $_POST['subcompanyid']??0;
	$projectid = $_POST['projectid']??0;
	$modules = getAllClientGroups($authconn,$companyid,$subcompanyid,$projectid);
	echo json_encode($modules);
}else if($action=="GetModulesDetailsFromMaster"){
	$companyid = $_POST['companyid']??0;
	$subcompanyid = $_POST['subcompanyid']??0;
	$projectid = $_POST['projectid']??0;
	$modules = getAllClientModules($authconn,$companyid,$subcompanyid,$projectid);
	echo json_encode($modules);
}else if($action=="ModulesDetailsforAllocation"){
	$companyid = $_POST['companyid']??0;
	$subcompanyid = $_POST['subcompanyid']??0;
	$projectid = $_POST['projectid']??0;
	$modules = getAllClientModules($authconn,$companyid,$subcompanyid,$projectid);
	$mdlist = array();
	foreach ($modules as $mdkey) {
		$grpin = getAllClientGroups($authconn, $companyid, $subcompanyid, $projectid, "groupid", $mdkey['groupid']);
		
		$groupname = "";
		if(count($grpin)>0){
			if($mdkey['groupid']>0){
				$groupname = $grpin[0]['groupname'].'--'.$mdkey['groupid'];
			}
		}
		$mdlist[] = array('sno'=>$mdkey['sno'], 'groupid'=>$mdkey['groupid'], 'groupname'=>$groupname, 'moduleid'=>$mdkey['moduleid'], 'modulename'=>$mdkey['modulename'], 'modulefoldername'=>$mdkey['modulefoldername'], 'ispasswordprotected'=>$mdkey['ispasswordprotected'], 'integrationtype'=>$mdkey['integrationtype'], 'status'=>$mdkey['status']);
	}
	echo json_encode($mdlist);
}else if($action=="saveAllocationofGroup"){
	$companyid    = $_POST['companyid']    ?? 0;
	$subcompanyid = $_POST['subcompanyid'] ?? 0;
	$projectid    = $_POST['projectid']    ?? 0;
	$groupid      = $_POST['groupid']      ?? 0;
	$modulesJson  = $_POST['modulesid']    ?? '';
	$modulesArray = json_decode($modulesJson, true);
	if (is_array($modulesArray) && count($modulesArray) > 0) {
	    $sql = "UPDATE login_modules_master_client 
	            SET groupid = ?  WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND moduleid = ?";
	    $stmt = $authconn->prepare($sql);
	    if (!$stmt) {
	        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $authconn->error]);
	        exit;
	    }
	    $stmt->bind_param("sssss", $groupid, $companyid, $subcompanyid, $projectid, $moduleid);
	    $allSuccess = true;
	    foreach ($modulesArray as $module) {
	        $moduleid = $module['moduleid'] ?? '';
	        if (!$stmt->execute()) {
	            $allSuccess = false;
	            break;
	        }
	    }
	    $stmt->close();
	    if ($allSuccess) {
	        echo json_encode(['status' => 'success', 'message' => 'Group allocated to selected modules successfully.']);
	    } else {
	        echo json_encode(['status' => 'error', 'message' => 'Update failed for one or more modules.']);
	    }
	} else {
	    echo json_encode(['status' => 'error', 'message' => 'Invalid or empty module list.']);
	}
}else if($action=="authorizedlistFormodulepassword"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$moduleid = $_POST['moduleid'];
	$gpq = $authconn->query("SELECT sno,companyid,subcompanyid,projectid,loginid,moduleid,ispassword,ifnull(password,'') as password FROM login_authorized_modules  WHERE status = 'active' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and moduleid='".$moduleid."'");
	$mdlist=array();
	$authloginlist=array();
	if($gpq->num_rows>0){
		while($mrow=$gpq->fetch_assoc()){
			$lgm = loginuserinfoFromModule($authconn,"","loginid",$mrow['loginid']);
			$loginname="";
			if(count($lgm)>0){
				foreach ($lgm as $lgmkey) {
					$loginname = $lgmkey['loginname'];
				}
			}
			if($loginname){
				$authloginlist[]=array('sno'=>$mrow['sno'],'loginname'=>$loginname,"loginid"=>$mrow['loginid'],'authpass'=>$mrow['password']);
			}
		}
	}
	echo json_encode($authloginlist);
}else if($action=="updatepassword_inmoduleforuser"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$moduleid = $_POST['moduleid'];
	$modulepassword = json_decode($_POST['modulepassword'], true);
	$mdresult=array();
	if(count($modulepassword)>0){
		$m=0;
		foreach ($modulepassword as $mdpasskey) {
			if($mdpasskey['dbsno']!="" && $mdpasskey['loginid']!=""){
				$authconn->query("UPDATE login_authorized_modules SET password='".$mdpasskey['mdlpass']."' where sno='".$mdpasskey['dbsno']."' and moduleid='".$moduleid."'");
				if(mysqli_affected_rows($authconn)>0){
					$m=1;
				}
			}
		}
		if($m==1){
			$mdresult=array('status'=>'Success', 'message'=>'Module password is update successfully');
		}else{
			$mdresult=array('status'=>'Error', 'message'=>'Module password did not update, due to technical issue.');
		}
	}else{
		$mdresult=array('status'=>'Error', 'message'=>'Data not available for update password , try again.');
	}
	echo json_encode($mdresult);
}else if($action=="UpdateModulesViewOrderNo"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$vieworderno = $_POST['vieworderno'];
	$dbsno = $_POST['dbsno'];
	$mdresult=array();
	if($dbsno!=""){
		$m=0;
		$authconn->query("UPDATE login_modules_master_client SET vieworderby='".$vieworderno."' where sno='".$dbsno."'");
		if(mysqli_affected_rows($authconn)>0){
			$m=1;
		}
		if($m==1){
			$mdresult=array('status'=>'Success', 'message'=>'Module view order is update successfully');
		}else{
			$mdresult=array('status'=>'Error', 'message'=>'Module view order did not update, due to technical issue.');
		}
	}else{
		$mdresult=array('status'=>'Error', 'message'=>'Data not available for update password , try again.');
	}
	echo json_encode($mdresult);
}else if($action=="UpdateGroupViewOrderNo"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$vieworderno = $_POST['vieworderno'];
	$dbsno = $_POST['dbsno'];
	$mdresult=array();
	if($dbsno!=""){
		$m=0;
		$authconn->query("UPDATE login_group_master_client SET vieworderby='".$vieworderno."' where sno='".$dbsno."'");
		if(mysqli_affected_rows($authconn)>0){
			$m=1;
		}
		if($m==1){
			$mdresult=array('status'=>'Success', 'message'=>'Group view order is update successfully');
		}else{
			$mdresult=array('status'=>'Error', 'message'=>'Group view order did not update, due to technical issue.');
		}
	}else{
		$mdresult=array('status'=>'Error', 'message'=>'Data not available for update password , try again.');
	}
	echo json_encode($mdresult);
}else if($action=="UnGroupModule"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$dbsno = $_POST['dbsno'];
	$mdresult=array();
	if($dbsno!=""){
		$m=0;
		$authconn->query("UPDATE login_modules_master_client SET groupid='0' where sno='".$dbsno."'");
		if(mysqli_affected_rows($authconn)>0){
			$m=1;
		}
		if($m==1){
			$mdresult=array('status'=>'Success', 'message'=>'Your module is un-group successfully');
		}else{
			$mdresult=array('status'=>'Error', 'message'=>'Module did not update as un-group, due to technical issue.');
		}
	}else{
		$mdresult=array('status'=>'Error', 'message'=>'Data not available for update password , try again.');
	}
	echo json_encode($mdresult);
}else if($action=="checkmodulepasswordWithLoginid"){
	//Start the session if not already started
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
}else if($action=="checkmoduleHaspasswordProtected"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$loginroleid = $_POST['loginroleid'];
	$moduleid = $_POST['moduleid'];
	$mdlresponse = checkModuleLoginStatusWithmodule();
	$ssq=getAllClientModules($authconn, $companyid, $subcompanyid, $projectid,"moduleid",$moduleid);
	$moduleCheck = [
	  "ispasswordprotected" => ($ssq && count($ssq)>0) ? $ssq[0]['ispasswordprotected'] : "no",
	  "loginstatus" => $mdlresponse,
	  "loginid" => $loginid,
	  "loginroleid" => $loginroleid,
	  "mdid" => $moduleid
	];	
	echo json_encode($moduleCheck);
}
?>
