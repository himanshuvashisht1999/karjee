<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
//require('../automation/automation_functionmaster.php');
$action=$_GET['action'];
if($action=="tdstypefrommaster_callback"){
	$shortby = $_POST['shortby']??'all';
	$shortbyvalue = $_POST['shortbyvalue']??'';
	$tdstypelist=getTdsTypesMasterData($conn, $shortby, $shortbyvalue);
	echo json_encode($tdstypelist);
}else if($action=="modulesgrouplistonly"){
	$shortby=$_POST['shortby'];
	$shortbyvalue=$_POST['shortbyvalue'];
	$lgfmdl=grouptitleofmodule($conn,$shortby,$shortbyvalue);
	echo json_encode($lgfmdl);
}else if($action=="addupdate_groupname"){
	$groupmainsno = $_POST['groupmainsno'];
	$groupname = $_POST['groupname'];
	$loginname = $_POST['loginname'];
	$rrst=array();
	if($groupmainsno){

	}else{
		// Check if the group name already exists
		$checkDuplicate = $conn->prepare("SELECT COUNT(*) as count FROM login_modulesgroup_master WHERE groupname = ?");
		$checkDuplicate->bind_param("s", $groupname);
		$checkDuplicate->execute();
		$result = $checkDuplicate->get_result();
		$row = $result->fetch_assoc();

		if ($row['count'] > 0) {
		    $rrst = array('status' => 'Error', 'message' => 'Group name ' . $groupname . ' already exists');
		} else {
		    // Get the max group ID and increment it
		    $ssmm = $conn->query("SELECT IFNULL(MAX(groupid), 0) + 1 AS maxid FROM login_modulesgroup_master");
		    $smrow = $ssmm->fetch_assoc();
		    $maxgrpid = $smrow['maxid'];
		    
		    // Insert new group
		    $stmt = $conn->prepare("INSERT INTO login_modulesgroup_master (groupid, groupname) VALUES (?, ?)");
		    $stmt->bind_param("is", $maxgrpid, $groupname);
		    $stmt->execute();
		    
		    if ($stmt->affected_rows > 0) {
		        $rrst = array('status' => 'Success', 'message' => 'New group name ' . $groupname . ' saved successfully');
		    } else {
		        $rrst = array('status' => 'Error', 'message' => 'New group name ' . $groupname . ' did not save, please try again');
		    }
		    
		    $stmt->close();
		}
		$checkDuplicate->close();
	}
	echo json_encode($rrst);
}else if($action=="moduleslistonly"){
	$shortby=$_POST['shortby'];
	$shortbyvalue=$_POST['shortbyvalue'];
	$lgmdl=loginmodulesmasterlist($conn,$shortby,$shortbyvalue);
	echo json_encode($lgmdl);
}else if($action=="moduleslistonlywithCompanyAuthoriztion"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$shortby=$_POST['shortby'];
	$shortbyvalue=$_POST['shortbyvalue'];
	$lgmdl=loginmodulesmasterlist($conn,"all","all");
	// print_r($lgmdl);
	$authmodl=[];
	if(count($lgmdl)>0){
		foreach ($lgmdl as $lgmdlkey) {
			// main group name
			$mainggp=grouptitleofmodule($conn, "groupid", $lgmdlkey['groupid']);
			$maingroupname="";
			if(count($mainggp)>0){
				foreach ($mainggp as $mainggpkey) {
					$maingroupname = $mainggpkey['groupname'];
				}
			}
			$cmpmdl=company_loginmodulesmasterlist($conn,$companyid,$subcompanyid,$projectid,"moduleid",$lgmdlkey['moduleid']);
			if(count($cmpmdl)>0){
				foreach ($cmpmdl as $cmpmdlkey) {
					

					$ggp=grouptitleofmodule($conn, "groupid", $cmpmdlkey['groupid']);
					$groupname="";
					// print_r($ggp);
					if(count($ggp)>0){
						foreach ($ggp as $ggpkey) {
							$groupname = $ggpkey['groupname'];
						}
					}
					$authmodl[] = array('sno'=>$lgmdlkey['sno'], 'maingroupid'=>$lgmdlkey['groupid'], 'maingroupname'=>$maingroupname, 'authoprjsno'=>$cmpmdlkey['sno'], 'companyid'=>$cmpmdlkey['companyid'], 'subcompanyid'=>$cmpmdlkey['subcompanyid'], 'projectid'=>$cmpmdlkey['projectid'], 'groupid'=>$cmpmdlkey['groupid'], 'groupname'=>$groupname, 'moduleid'=>$cmpmdlkey['moduleid'], 'modulename'=>$cmpmdlkey['modulename'], 'modulefoldername'=>$cmpmdlkey['modulefoldername'], 'subaction'=>$cmpmdlkey['subaction'], 'frmid'=>$cmpmdlkey['frmid']);
				}
			}else{
				$authmodl[] = array('sno'=>$lgmdlkey['sno'], 'maingroupid'=>$lgmdlkey['groupid'], 'maingroupname'=>$maingroupname, 'authoprjsno'=>'', 'companyid'=>'', 'subcompanyid'=>'', 'projectid'=>'', 'groupid'=>'', 'groupname'=>'', 'moduleid'=>$lgmdlkey['moduleid'], 'modulename'=>$lgmdlkey['modulename'], 'modulefoldername'=>$lgmdlkey['modulefoldername'], 'subaction'=>$lgmdlkey['subaction'], 'frmid'=>$lgmdlkey['frmid']);
			}
		}
	}
	echo json_encode($authmodl);
}else if($action=="Removemodule_FromCompanyAuthoriztion"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$authsno = $_POST['authsno'];
	$conn->query("delete from login_modules_master_forcompany  where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and sno='".$authsno."'");
	// $conn->query("UPDATE login_modules_master_forcompany SET status='removed' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and sno='".$authsno."'");
	$rrms=[];
	if(mysqli_affected_rows($conn)>0){
		$rrms=array('status'=>'Success', 'message'=>'Removed grouping of module');
	}else{
		$rrms=array('status'=>'Error', 'message'=>'Somthing wrong to Removed grouping of module');
	}
	echo json_encode($rrms);
}else if($action=="companylist_frommaster"){
	$loginid=$_POST['loginid'];
	$lginfo=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	$loginroleid ="";
	if(count($lginfo)>0){
		foreach ($lginfo as $lgckey) {
			$loginroleid = $lgckey['loginroleid'];
		}
	}
	if($loginroleid==0){
		$authcompanylist=getcompanydetails($conn,"all","");;
	}else{
		$authcmp=allauthorizedcompanyid($conn,$loginid);
		$authcompanylist=getcompanydetails($conn,"companyid",$authcmp);
	}
	echo json_encode($authcompanylist);	
}else if($action=="subcompanylist_frommaster"){
	$loginid=$_POST['loginid'];
	$companyid=$_POST['companyid'];
	$lginfo=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	$loginroleid ="";
	if(count($lginfo)>0){
		foreach ($lginfo as $lgckey) {
			$loginroleid = $lgckey['loginroleid'];
		}
	}
	if($loginroleid==0){
		$authsubcompanylist=getsubcompanydetails($conn,$companyid,"","allsubcompanywithcopanyid",$companyid);
	}else{
		$authcmp=allauthorizedcompanyid($conn,$loginid);
		$authsbcmp=allauthorizedsubcompanyid($conn,$loginid);
		$authsubcompanylist=$authsubcompanylist=getsubcompanydetails($conn,$companyid,"","allsubcompanywithcopanyid",$companyid);
	}
	echo json_encode($authsubcompanylist);	
}else if($action=="projectlist_fordropdown"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$prjlist=getprojectdetails($mconn,$companyid,$subcompanyid,"all","");
	echo json_encode($prjlist);
}else if($action=="savepaytonew_12"){
	$loginname =$_POST['loginname'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$payto=$_POST['payto'];
	$resp = savetodbnewpayto_details($conn,$payto,'','','','','','',$companyid,$subcompanyid,$projectid,'','',$loginname);
	echo json_encode($resp);
}else if($action=="savepayernew"){
	$loginname =$_POST['loginname'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$payer=$_POST['payer'];
	$resp = savetodbnewpayer_details($conn,$payer,'','','','','','',$companyid,$subcompanyid,$projectid,$loginname);
	echo json_encode($resp);
}else if($action=="banklist"){
	$companyid = $_POST['companyid'];
	echo json_encode(getfunctionbanknamefrommaster($mconn));
}else if($action=="accountlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];	
	echo json_encode(getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,'projectid',$projectid));
}else if ($action=='getfunctionfrommasterpnl') {	
	$list=array();
	$qry="select subcategoryid,subcategory,rowno,type,functionname from pnl_reporting_defaultpurpose_master where (functionname is not null and functionname<>'') ";
	$rwsultqry=$conn->query($qry);
	if ($rwsultqry->num_rows>0) {
		while ($rowq=$rwsultqry->fetch_assoc()) {
			$list[]=array('categoryid'=>'','category'=>'','subcategoryid'=>$rowq['subcategoryid'],'subcategory'=>$rowq['subcategory'],'rowno'=>$rowq['rowno'],'type'=>$rowq['type'],'functionname'=>$rowq['functionname']);
		}
	}
	echo json_encode($list);
}else if($action=="accountlistwithcreditcard"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];	
	echo json_encode(getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,'allaccountofprojectonlybankandcreditcard',$projectid));
}else if ($action=="subproject_save") {
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectname = $_POST['subprojectname'];
	$emailid = $_POST['emailid'];
	echo json_encode(addnewsubproject($conn,$companyid,$subcompanyid,$projectid,$subprojectname,$emailid));
}else if($action=="subprojectdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$prjlist=subprojectdetails($mconn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($prjlist);
}else if($action=="replacenewpurposewithfromold"){
	// Input data
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$existingpurposecode = $_POST['oldpurposename'] ?? null;
	$newpurposecode = $_POST['newpurposename'] ?? null;
	$loginname = $_POST['loginname'] ?? null;
	$purposetype = strtolower($_POST['purposetype'] ?? '');
	$breakdwonsno = $_POST['breakdwonsnoarray'];

	if($breakdwonsno){
		$breakdwonsnoarray = explode(",", $breakdwonsno);
	}else{
		$breakdwonsnoarray=array();
	}
	
	// Validate input data
	if (!$companyid || !$subcompanyid || !$projectid || !$existingpurposecode || !$newpurposecode) {
	    echo json_encode(['status' => 'Error', 'message' => 'All required fields must be provided.']);
	    exit;
	}

	// Fetch purpose data based on type
	$ppct = ($purposetype === 'credit' || $purposetype === 'deposit') 
	    ? getcreditpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $newpurposecode) 
	    : getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "purposecode", $newpurposecode);

	if (empty($ppct)) {
	    echo json_encode(['status' => 'Error', 'message' => 'Purpose category not found. Please try again.=====replace']);
	    exit;
	}

	// Extract data from the fetched purpose
	$newcategory = $ppct[0]['pcategory'];
	$newcategoryid = $ppct[0]['pcategorycode'];
	$newsubcategory = $ppct[0]['psub_category'];
	$newsubcategoryid = $ppct[0]['psub_categoryid'];
	$newpurpose = $ppct[0]['purpose'];
	$newpurposesid = $ppct[0]['purposeid'];
	$newprovidedcode = $ppct[0]['provided_code'];
	$rrst=array();
	// Determine table prefix based on purpose type
	$tablePrefix = ($purposetype === 'credit' || $purposetype === 'deposit') ? '_credit' : '';
	// print_r($breakdwonsnoarray);
    if(count($breakdwonsnoarray)>0){
    	for($i=0; $i<=count($breakdwonsnoarray); $i++) {
    		$stmt = $conn->query("SELECT b.reference_no FROM tbl_maintransaction{$tablePrefix}_breakdown AS b JOIN tbl_maintransaction{$tablePrefix} AS t ON t.reference_no = b.reference_no WHERE t.companyid = '".$companyid."' AND t.subcompanyid = '".$subcompanyid."' AND t.projectid = '".$projectid."' AND b.sno= '".$breakdwonsnoarray[$i]."' AND IFNULL(t.audit_status, '') = ''");
    		if($stmt->num_rows>0){
			    while ($row = $result->fetch_assoc()) {
			        $referenceNo = $row['reference_no'];
			        // Update payment type in maintransaction
			        $stmtUpdateMain = $conn->query("UPDATE tbl_maintransaction{$tablePrefix}
			            SET paymenttype = '".$newcategory."' WHERE reference_no = '".$referenceNo."'");
			        $conn->query("UPDATE tbl_maintransaction{$tablePrefix}_breakdown AS b JOIN tbl_maintransaction{$tablePrefix} AS t ON t.reference_no = b.reference_no SET b.purpose_category = '".$newcategory."', b.purpose_categoryid = '".$newcategoryid."', b.purpose_subcategory = '".$newsubcategory."', b.purpose_subcategoryid = '".$newsubcategoryid."', 
			                b.purpose = '".$newpurpose."', b.purpose_code = '".$newpurposecode."', b.provided_code = '".$newprovidedcode."', b.purpose2 = '".$existingpurposecode."' WHERE b.reference_no = '".$row['reference_no']."' AND b.purpose_code = '".$existingpurposecode."'");
			    }
			    $rrst=array('status' => 'Success', 'message' => 'Purpose code updated successfully. 11');
			}else{
				$rrst=array('status' => 'Error', 'message' => 'Transaction failed: 11');
			}
    	}
    }else{
    	$stmt = $conn->query("SELECT b.reference_no FROM tbl_maintransaction{$tablePrefix}_breakdown AS b JOIN tbl_maintransaction{$tablePrefix} AS t ON t.reference_no = b.reference_no WHERE t.companyid = '".$companyid."' AND t.subcompanyid = '".$subcompanyid."' AND t.projectid = '".$projectid."' AND b.purpose_code= '".$existingpurposecode."' AND IFNULL(t.audit_status, '') = ''");
		if($stmt->num_rows>0){
		    while ($row = $result->fetch_assoc()) {
		        // Update payment type in maintransaction
		        $stmtUpdateMain = $conn->query("UPDATE tbl_maintransaction{$tablePrefix}
		            SET paymenttype = '".$newcategory."' WHERE reference_no = '".$row['reference_no']."'");
		        $conn->query("UPDATE tbl_maintransaction{$tablePrefix}_breakdown AS b JOIN tbl_maintransaction{$tablePrefix} AS t ON t.reference_no = b.reference_no SET b.purpose_category = '".$newcategory."', b.purpose_categoryid = '".$newcategoryid."', b.purpose_subcategory = '".$newsubcategory."', b.purpose_subcategoryid = '".$newsubcategoryid."', 
		                b.purpose = '".$newpurpose."', b.purpose_code = '".$newpurposecode."', b.provided_code = '".$newprovidedcode."', b.purpose2 = '".$existingpurposecode."' WHERE b.reference_no = '".$row['reference_no']."' AND b.purpose_code = '".$existingpurposecode."'");
		    }
			$rrst=array('status' => 'Success', 'message' => 'Purpose code updated successfully. 22');
		}else{
			$rrst=array('status' => 'Error', 'message' => 'Data not available for update to new purpose code ');
		}	    	
	}
	echo json_encode($rrst);
}else if($action=="addnewpurposewithprovidedcode"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$pcatid = $_POST['purposecategoryid'] ?? null;
	$psubcatid = $_POST['purposesubcategoryid'] ?? null;
	$purposename = $_POST['purposename'] ?? null;
	$purposecode = $_POST['purposecode'] ?? null;
	$providedcode = $_POST['providedcode'] ?? null;
	$visiblestatus = $_POST['visiblestatus'] ?? null;
	$purposetype = strtolower($_POST['purposetype'] ?? '');
	$loginname = $_POST['loginname'] ?? null;

	// addupdatepurposedetailsIntoMaster($conn,$companyid,$subcompanyid,$projectid,$clientsubcategoryid,$clientsubcategoryname,$clientprovidedcode,$asktcategoryid,$asktsubcategoryid,$purposetype,$purposename,$loginname);

	
	$rrp = [];
	// die();
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || !$purposename || !$providedcode ) {
	    echo json_encode(['status' => 'Error', 'message' => 'Invalid input data. All required fields must be provided.']);
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

    

	 

	// Fetch or validate Purpose Category
	if ($pcatid) {
	    $ppct = ($purposetype === 'credit' || $purposetype === 'deposit') 
	        ? getpurposecategorymainmaster_credit($conn, "categoryid", $pcatid) 
	        : getpurposecategorymainmaster($conn, "categoryid", $pcatid);

	    if (count($ppct)>0) {
	        $purposecategoryid = $ppct[0]['categoryid'];
	        $purposecategory = $ppct[0]['category'];
	    } else {
	        // echo json_encode(['status' => 'Error', 'message' => 'Purpose category not found. Please try again.']);
	        $rrstmsg[] = array('status' => 'Error', 'message' => 'Purpose category not found. Please try again.===add new with provided code');
	        echo json_encode($rrstmsg);
	        die();
	    }
	} else {
	    // echo json_encode(['status' => 'Error', 'message' => 'Purpose category not provided.']);
	    $rrstmsg[] = array('status' => 'Error', 'message' => 'Purpose category not provided. Please try again.===add new with provided code');
	    echo json_encode($rrstmsg);
	    die();
	}

	// Handle Purpose Subcategory
	$newsubcat = 0;
	if ($psubcatid) {
	    $ppsb = ($purposetype === 'credit' || $purposetype === 'deposit') 
	        ? GetPurposeSubCategoryfrommaster_credit($conn, $companyid, $subcompanyid, $projectid, "subcategoryid", $psubcatid) 
	        : GetPurposeSubCategoryfrommaster($conn, $companyid, $subcompanyid, $projectid, "subcategoryid", $psubcatid);

	    if (count($ppsb)>0) {
	        $purposesubcategoryid = $ppsb[0]['subpcategorycode'];
	        $purposesubcategory = $ppsb[0]['subpcategory'];
	    } else {
	        $maxsubcatid = ($purposetype === 'credit' || $purposetype === 'deposit') 
	            ? credit_maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid) 
	            : maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid);

	        if ($maxsubcatid>=0) {
	            $newsubcat = 1;
	            $purposesubcategoryid = $maxsubcatid + 1;
	            $purposesubcategory = $purposesubcategoryid . '-' . $psubcatid;
	        } else {
	            // echo json_encode(['status' => 'Error', 'message' => 'Purpose subcategory not found. Please try again.']);
	            $rrstmsg[] = array('status' => 'Error', 'message' => 'Purpose subcategory not found. Please try again.');
		        echo json_encode($rrstmsg);
	            die();
	        }
	    }
	}
	// purposecode
	// providedcode
	$ppcount = EnterSepratedStringToArray($purposename);
	// print_r($ppcount);
	$rrstmsg=[];
	if(count($ppcount)>0){
		foreach ($ppcount as $ppkey) {
			if(count($ppcount)>1){
				$providedcode="";
				$purposecode="";
			}
			$purposename = $ppkey;
			if($providedcode){
				$newmaxprovidedcode = $providedcode;
			}else{
				$maxProvidedCodeFunction = ($purposetype === 'credit' || $purposetype === 'deposit') 
				    ? 'credit_maxprovidecode_frm' 
				    : 'maxprovidecode_frm';
				$mxprcode = $maxProvidedCodeFunction($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);

				$newmaxprovidedcode = ($mxprcode) 
				    ? $purposesubcategoryid . '.' . addZeroBeforeTen(explode(".", $mxprcode)[1] + 1) 
				    : $purposesubcategoryid . '.01';
			}
			
			if($purposecode){
				$stmtcheck = $conn->prepare("SELECT purposeid FROM $pcodemaster WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purposecode = ?");
				$stmtcheck->bind_param("ssss", $companyid, $subcompanyid, $projectid, $purposecode);
				if (!$stmtcheck->execute()) {
				    // echo json_encode(['status' => 'Error', 'message' => 'Database error during purpose name check.']);
				    $rrstmsg[] = array('status' => 'Error', 'message' => 'Databse error during purpose name '.$purposename.' check. Please try again.');
			        // echo json_encode($rrstmsg);
				    // die();
				    continue;
				}

				$resultcheck = $stmtcheck->get_result();
				$rowcheck = $resultcheck->fetch_assoc();
				$purposeid = $rowcheck['purposeid'];
				
				$purposecode = createPurposeCodeEncoded($companyid, $subcompanyid, $projectid, $purposecategoryid, $purposesubcategoryid, $purposeid, $newmaxprovidedcode);
				$compaletpurposecode = $purposecode[0]["combinedCode"];
				$stmt_update = $conn->prepare("UPDATE $pcodemaster SET categoryid = ?, category = ?, sub_categoryid = ?, sub_category = ?, purpose = ?, purposecode = ?, provided_code = ?, visiblestatus = ? WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purposeid = ?");
				$stmt_update->bind_param("ssssssssssss",$purposecategoryid,addslashes($purposecategory),$purposesubcategoryid,addslashes($purposesubcategory),addslashes($purposename),$compaletpurposecode,$newmaxprovidedcode,$visiblestatus,$companyid,$subcompanyid,$projectid,$purposeid);
				if ($stmt_update->execute()) {
					
					// update client purpose master details
					$conn->query("UPDATE $pcodemasterclient SET purposename='".addslashes($purposename)."' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ms_purposecode='".$compaletpurposecode."' and (tedit is null or tedit='')");

				    $rrstmsg[] = array('status' => 'Success', 'message' => 'Purpose '.$purposename.' updated successfully', 'purposecode' => $compaletpurposecode, 'purposename' => $purposename);
				} else {
				    $rrstmsg[] = array('status' => 'Error', 'message' => "Error updating purpose ".$purposename.": " . $stmt_update->error, 'purposecode' =>$compaletpurposecode, 'purposename' => $purposename);
				}
			}
			else{
				// Check if Purpose Name already exists
				$stmt_check = $conn->prepare("SELECT COUNT(*) AS cnt FROM $pcodemaster WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purpose = ?");
				$stmt_check->bind_param("ssss", $companyid, $subcompanyid, $projectid, $purposename);

				if (!$stmt_check->execute()) {
				    echo json_encode(['status' => 'Error', 'message' => 'Database error during purpose name check.']);
				    // die();
				    continue;
				}

				$result_check = $stmt_check->get_result();
				$row_check = $result_check->fetch_assoc();

				if ($row_check['cnt'] > 0) {
				    $rrstmsg[]=array('status' => 'Error', 'message' => 'Purpose name ('.$purposename.') already exists. Please try a different name.', 'purposename' => $purposename);
				    continue;
				}
				// Generate new purpose code
				$purposeid = getpurposeid($conn, $companyid, $subcompanyid, $projectid, $purposetype);
				$purposecode = createPurposeCodeEncoded($companyid, $subcompanyid, $projectid, $purposecategoryid, $purposesubcategoryid, $purposeid, $newmaxprovidedcode);
				$compaletpurposecode = $purposecode[0]["combinedCode"];

				// Insert new purpose
				$stmt_insert = $conn->prepare("INSERT INTO $pcodemaster (cmp_id, subcmp_id, projectid, categoryid, category, sub_categoryid, sub_category, purpose, purposecode, provided_code, purposeid, visiblestatus, markbynote4, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
				$markbynote4 = 'temp';
				$source = 'manual by ' . $loginname;
				$stmt_insert->bind_param("ssssssssssssss", $companyid, $subcompanyid, $projectid, $purposecategoryid, addslashes($purposecategory), $purposesubcategoryid, addslashes($purposesubcategory), addslashes($purposename), $compaletpurposecode, $newmaxprovidedcode, $purposeid, $visiblestatus, $markbynote4, $source);
				if ($stmt_insert->execute()) {
				    if ($newsubcat === 1) {
				        $stmt_subcat = $conn->prepare("INSERT INTO $pcategorymaster (cmp_id, subcmp_id, projectid, categoryid, category, sub_categoryid, sub_category) VALUES (?, ?, ?, ?, ?, ?, ?)");
				        $stmt_subcat->bind_param("sssssss", $companyid, $subcompanyid, $projectid, $purposecategoryid, addslashes($purposecategory), $purposesubcategoryid, addslashes($purposesubcategory));
				        $stmt_subcat->execute();
				    }		   
				     insertnewpurposeinPNLmaster($conn,$companyid,$projectid,$purposesubcategoryid,$purposesubcategory,$purposename,$newmaxprovidedcode,$compaletpurposecode,$purposetype);
				     $rrstmsg[]=array('status' => 'Success', 'message' => 'New Purpose '.$purposename.' saved successfully.', 'purposecode' => $compaletpurposecode, 'purposename' => $purposename);
				} else {
					$rrstmsg[]=array('status' => 'Error', 'message' => 'Failed to save new Purpose '.$purposename.'. Please try again.');
				}
			}
		}
	}else{
		$rrstmsg[] = array('status' => 'Error', 'message' => 'Purpose name is not available. Please try again.');
	}
	echo json_encode($rrstmsg);
	
}else if($action=="addnewpurposewithother"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$pcatid = $_POST['purposecategoryid'] ?? null;
	$psubcatid = $_POST['purposesubcategoryid'] ?? null;
	$purposename = $_POST['purposename'] ?? null;
	$loginname = $_POST['loginname'] ?? null;
	$purposetype = strtolower($_POST['purposetype'] ?? '');

	$rrp = [];
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || !$purposename) {
	    echo json_encode(['status' => 'Error', 'message' => 'Invalid input data. All required fields must be provided.']);
	    die();
	}

	// Set table names based on purpose type
	$pcategorymaster = ($purposetype === 'credit' || $purposetype === 'deposit') 
	    ? 'purpose_category_master_credit' 
	    : 'purpose_category_master';
	$pcodemaster = ($purposetype === 'credit' || $purposetype === 'deposit') 
	    ? 'purpose_code_master_credit' 
	    : 'purpose_code_master';

	// Check if Purpose Name already exists
	$stmt_check = $conn->prepare("SELECT COUNT(*) AS cnt FROM $pcodemaster WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purpose = ?");
	$stmt_check->bind_param("ssss", $companyid, $subcompanyid, $projectid, $purposename);

	if (!$stmt_check->execute()) {
	    echo json_encode(['status' => 'Error', 'message' => 'Database error during purpose name check.']);
	    die();
	}

	$result_check = $stmt_check->get_result();
	$row_check = $result_check->fetch_assoc();

	if ($row_check['cnt'] > 0) {
	    echo json_encode(['status' => 'Error', 'message' => 'Purpose name already exists. Please try a different name.']);
	    die();
	}

	// Fetch or validate Purpose Category
	if ($pcatid) {
	    $ppct = ($purposetype === 'credit' || $purposetype === 'deposit') 
	        ? getpurposecategorymainmaster_credit($conn, "categoryid", $pcatid) 
	        : getpurposecategorymainmaster($conn, "categoryid", $pcatid);

	    if (count($ppct)>0) {
	        $purposecategoryid = $ppct[0]['categoryid'];
	        $purposecategory = $ppct[0]['category'];
	    } else {
	        echo json_encode(['status' => 'Error', 'message' => 'Purpose category not found. Please try again.===add new with other']);
	        die();
	    }
	} else {
	    echo json_encode(['status' => 'Error', 'message' => 'Purpose category not provided.']);
	    die();
	}

	// Handle Purpose Subcategory
	$newsubcat = 0;
	if ($psubcatid) {
	    $ppsb = ($purposetype === 'credit' || $purposetype === 'deposit') 
	        ? getMainSubcategoryMaster_Credit($conn,"subcategoryid", $psubcatid) 
	        : getMainSubcategoryMaster($conn,"subcategoryid", $psubcatid);

	    if (count($ppsb)>0) {
	        $purposesubcategoryid = $ppsb[0]['subcategoryid'];
	        $purposesubcategory = $ppsb[0]['subcategory'];
	    } else {
	        $maxsubcatid = ($purposetype === 'credit' || $purposetype === 'deposit') 
	            ? credit_maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid) 
	            : maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid);

	        if ($maxsubcatid>=0) {
	            $newsubcat = 1;
	            $purposesubcategoryid = $maxsubcatid + 1;
	            $purposesubcategory = $purposesubcategoryid . '-' . $psubcatid;
	        } else {
	            echo json_encode(['status' => 'Error', 'message' => 'Purpose subcategory not found. Please try again.']);
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

	$newmaxprovidedcode = ($mxprcode) 
	    ? $purposesubcategoryid . '.' . addZeroBeforeTen(explode(".", $mxprcode)[1] + 1) 
	    : $purposesubcategoryid . '.01';

	$purposecode = createPurposeCodeEncoded($companyid, $subcompanyid, $projectid, $purposecategoryid, $purposesubcategoryid, $purposeid, $newmaxprovidedcode);

	$compaletpurposecode = $purposecode[0]["combinedCode"];

	// Insert new purpose
	$stmt_insert = $conn->prepare("INSERT INTO $pcodemaster (cmp_id, subcmp_id, projectid, categoryid, category, sub_categoryid, sub_category, purpose, purposecode, provided_code, purposeid, markbynote4, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	$markbynote4 = 'temp';
	$source = 'manual by ' . $loginname;
	$stmt_insert->bind_param("sssssssssssss", $companyid, $subcompanyid, $projectid, $purposecategoryid, $purposecategory, $purposesubcategoryid, $purposesubcategory, $purposename, $compaletpurposecode, $newmaxprovidedcode, $purposeid, $markbynote4, $source);

	if ($stmt_insert->execute()) {
	    if ($newsubcat === 1) {
	        // $stmt_subcat = $conn->prepare("INSERT INTO $pcategorymaster (cmp_id, subcmp_id, projectid, categoryid, category, sub_categoryid, sub_category) VALUES (?, ?, ?, ?, ?, ?, ?)");
	        // $stmt_subcat->bind_param("sssssss", $companyid, $subcompanyid, $projectid, $purposecategoryid, $purposecategory, $purposesubcategoryid, $purposesubcategory);
	        // $stmt_subcat->execute();
	    }
	    echo json_encode(['status' => 'Success', 'message' => 'New Purpose saved successfully.', 'purposecode' => $compaletpurposecode, 'purposename' => $purposename]);
	} else {
	    echo json_encode(['status' => 'Error', 'message' => 'Failed to save new Purpose. Please try again.']);
	}
}else if($action=="updateeditedpurpose"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$pcatid = $_POST['purposecategoryid'] ?? null;
	$psubcatid = $_POST['purposesubcategoryid'] ?? null;
	$purposecode = $_POST['purposecode'] ?? null;
	$purposename = $_POST['purposename'] ?? null;
	$loginname = $_POST['loginname'] ?? null;
	$purposetype = strtolower($_POST['purposetype'] ?? '');

	$rrp = [];

	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || !$purposename || !$purposecode) {
	    echo json_encode(['status' => 'Error', 'message' => 'Invalid input data. All required fields must be provided.']);
	    die();
	}

	// Set table names based on purpose type
	$pcategorymaster = ($purposetype === 'credit' || $purposetype === 'deposit') 
	    ? 'purpose_category_master_credit' 
	    : 'purpose_category_master';
	$pcodemaster = ($purposetype === 'credit' || $purposetype === 'deposit') 
	    ? 'purpose_code_master_credit' 
	    : 'purpose_code_master';

	// Check if Purpose Name already exists
	$stmt_check = $conn->prepare("SELECT COUNT(*) AS cnt FROM $pcodemaster WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purpose = ?");
	$stmt_check->bind_param("ssss", $companyid, $subcompanyid, $projectid, $purposename);

	if (!$stmt_check->execute()) {
	    echo json_encode(['status' => 'Error', 'message' => 'Database error during purpose name check.']);
	    die();
	}

	$result_check = $stmt_check->get_result();
	$row_check = $result_check->fetch_assoc();

	if ($row_check['cnt'] > 0) {
	    echo json_encode(['status' => 'Error', 'message' => 'Purpose name already exists. Please try a different name.']);
	    die();
	}

	// Fetch or validate Purpose Category
	if ($pcatid) {
	    $ppct = ($purposetype === 'credit' || $purposetype === 'deposit') 
	        ? getpurposecategorymainmaster_credit($conn, "categoryid", $pcatid) 
	        : getpurposecategorymainmaster($conn, "categoryid", $pcatid);

	    if (count($ppct)>0) {
	        $purposecategoryid = $ppct[0]['categoryid'];
	        $purposecategory = $ppct[0]['category'];
	    } else {
	        echo json_encode(['status' => 'Error', 'message' => 'Deposit Purpose category not found. Please try again.']);
	        die();
	    }
	} else {
	    echo json_encode(['status' => 'Error', 'message' => 'Deposit Purpose category not provided.']);
	    die();
	}

	// Handle Purpose Subcategory
	$newsubcat = 0;
	if ($psubcatid) {
	    $ppsb = ($purposetype === 'credit' || $purposetype === 'deposit') 
	        ? GetPurposeSubCategoryfrommaster_credit($conn, $companyid, $subcompanyid, $projectid, "subcategoryid", $psubcatid) 
	        : GetPurposeSubCategoryfrommaster($conn, $companyid, $subcompanyid, $projectid, "subcategoryid", $psubcatid);

	    if (count($ppsb)>0) {
	        $purposesubcategoryid = $ppsb[0]['subpcategorycode'];
	        $purposesubcategory = $ppsb[0]['subpcategory'];
	    } else {
	        $maxsubcatid = ($purposetype === 'credit' || $purposetype === 'deposit') 
	            ? credit_maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid) 
	            : maxsubcategorycode_frm($conn, $companyid, $subcompanyid, $projectid);

	        if ($maxsubcatid>=0) {
	            $newsubcat = 1;
	            $purposesubcategoryid = $maxsubcatid + 1;
	            $purposesubcategory = $purposesubcategoryid . '-' . $psubcatid;
	        } else {
	            echo json_encode(['status' => 'Error', 'message' => 'Purpose subcategory not found. Please try again.']);
	            die();
	        }
	    }
	}

	// update edited purpose
	$stmt_update = $conn->prepare("UPDATE $pcodemaster SET purpose = ? WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND categoryid = ? AND sub_categoryid = ? AND purposecode = ?");
	$stmt_update->bind_param("sssssss",addslashes($purposename),$companyid,$subcompanyid,$projectid,$purposecategoryid,$purposesubcategoryid,$purposecode);
	if ($stmt_update->execute()) {
	    echo json_encode(['status' => 'Success','message' => 'Updated Purpose Name successfully.', 'purposecode' => $purposecode,'purposename' => $purposename]);
	} else {
	    echo json_encode(['status' => 'Error', 'message' => 'Failed to update Purpose. Please try again.']);
	}
}else if($action=="preview_newpurposedetails"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$pcatid = $_POST['purposecategoryid'] ?? null;
	$psubcatid = $_POST['purposesubcategoryid'] ?? null;
	$purposename = $_POST['purposename'] ?? null;
	$loginname = $_POST['loginname'] ?? null;
	$purposetype = $_POST['purposetype'] ?? null;

	if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		$pcategorymaster = "purpose_category_master_credit";
		$pcodemaster = "purpose_code_master_credit";
	}else{
		$pcategorymaster = "purpose_category_master";
		$pcodemaster = "purpose_code_master";
	}
	$rrp = [];
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || !$purposename) {
	    echo json_encode(['status' => 'Error', 'message' => 'Invalid input data']);
	    die();
	}

	// Check if Purpose Name already exists
	$stmt_check = $conn->prepare("SELECT COUNT(*) AS cnt FROM ".$pcodemaster." WHERE (tedit is null or tedit='') and cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purpose = ?");
	$stmt_check->bind_param("ssss", $companyid, $subcompanyid, $projectid, $purposename);
	$stmt_check->execute();
	$result_check = $stmt_check->get_result();
	$row_check = $result_check->fetch_assoc();

	if ($row_check['cnt'] > 0) {
	    $rrp[] = ['status' => 'Error', 'message' => 'Purpose name already exists. Please try a different name.', 'purposecode' => '', 'purposename' => ''];
	    echo json_encode($rrp);
	    die();
	}

	// Purpose Category
	if (isset($pcatid)) {
		if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		    $ppct = getpurposecategorymainmaster_credit($conn, "categoryid", $pcatid);
		}else{
		    $ppct = getpurposecategorymainmaster($conn, "categoryid", $pcatid);
		}
	    if (count($ppct)>0) {
	        $purposecategoryid = $ppct[0]['categoryid'];
	        $purposecategory = $ppct[0]['category'];
	    } else {	    	
	        $rrp[] = ['status' => 'Error', 'message' => 'Purpose category not found in master list. Please try again.', 'purposecode' => '', 'purposename' => ''];
	        echo json_encode($rrp);
	        die();
	    }
	} else {
	    $rrp[] = ['status' => 'Error', 'message' => 'Purpose category not provided.', 'purposecode' => '', 'purposename' => ''];
	    echo json_encode($rrp);
	    die();
	}

	// Purpose Subcategory
	if (isset($psubcatid)) {
		if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
			// credit side
		    $ppsb = GetPurposeSubCategoryfrommaster_credit($conn, $companyid, $subcompanyid, $projectid, "subcategoryid", $psubcatid);
		    if (count($ppsb)>0) {
		        $purposesubcategoryid = $ppsb[0]['subpcategorycode'];
		        $purposesubcategory = $ppsb[0]['subpcategory'];
		    } else {
		    	$maxsubcatid=credit_maxsubcategorycode_frm($conn,$companyid,$subcompanyid,$projectid);
		    	if($maxsubcatid>=0){
		    		$purposesubcategoryid = ($maxsubcatid +1);
		        	$purposesubcategory = $purposesubcategoryid.'-'.$psubcatid;
		    	}else{
			        $rrp[] = ['status' => 'Error', 'message' => 'Purpose subcategory not found in master list. Please try again.', 'purposecode' => '', 'purposename' => ''];
			        echo json_encode($rrp);
			        die();
			    }
		    } 
		}else{
			// withdrwan side
			$ppsb = GetPurposeSubCategoryfrommaster($conn, $companyid, $subcompanyid, $projectid, "subcategoryid", $psubcatid);
		    if (count($ppsb)>0) {
		        $purposesubcategoryid = $ppsb[0]['subpcategorycode'];
		        $purposesubcategory = $ppsb[0]['subpcategory'];
		    } else {
		    	$maxsubcatid=maxsubcategorycode_frm($conn,$companyid,$subcompanyid,$projectid);
		    	if($maxsubcatid>=0){
		    		$purposesubcategoryid = ($maxsubcatid +1);
		        	$purposesubcategory = $purposesubcategoryid.'-'.$psubcatid;
		    	}else{
			        $rrp[] = ['status' => 'Error', 'message' => 'Purpose subcategory not found in master list. Please try again.', 'purposecode' => '', 'purposename' => ''];
			        echo json_encode($rrp);
			        die();
			    }
		    }    
		}
	    
	} else {
	    $rrp[] = ['status' => 'Error', 'message' => 'Purpose sub-category not defined.', 'purposecode' => '', 'purposename' => ''];
	    echo json_encode($rrp);
	    die();
	}
	

	// Generate new purpose code
	$purposeid = getpurposeid($conn, $companyid, $subcompanyid, $projectid, $purposetype);
	
	if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		$mxprcode = credit_maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);
		if ($mxprcode) {
		    $mxprcodeexp = explode(".", $mxprcode);
		    $newmaxprovidedcode = $purposesubcategoryid . '.' . addZeroBeforeTen($mxprcodeexp[1] + 1);
		} else {
		    $newmaxprovidedcode = $purposesubcategoryid . '.01';
		} 
	}else{
		$mxprcode = maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);
		if ($mxprcode) {
		    $mxprcodeexp = explode(".", $mxprcode);
		    $newmaxprovidedcode = $purposesubcategoryid . '.' . addZeroBeforeTen($mxprcodeexp[1] + 1);
		} else {
		    $newmaxprovidedcode = $purposesubcategoryid . '.01';
		}    
	}	
	
	$purposecode = createPurposeCodeEncoded($companyid, $subcompanyid, $projectid, $purposecategoryid, $purposesubcategoryid, $purposeid, $newmaxprovidedcode);
	$compaletpurposecode = $purposecode[0]["combinedCode"];

	// preview the purpose using prepared statements
	$rrp[] = ['status' => 'Success', 'message' => 'New Purpose saved successfully','categoryid'=>$purposecategoryid,'category'=>$purposecategory,'subcategoryid'=>$purposesubcategoryid,'subcategory'=>$purposesubcategory, 'purposecode' => $compaletpurposecode, 'purposename' => $purposename, 'providedcode'=>$newmaxprovidedcode];
	echo json_encode($rrp);
}else if($action=="maxnew_providedcode"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$pcatid = $_POST['purposecategoryid'] ?? null;
	$purposesubcategoryid = $_POST['purposesubcategoryid'] ?? null;
	$purposetype = $_POST['purposetype'] ?? null;

	if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		$pcategorymaster = "purpose_category_master_credit";
		$pcodemaster = "purpose_code_master_credit";
	}else{
		$pcategorymaster = "purpose_category_master";
		$pcodemaster = "purpose_code_master";
	}
	$rrp = [];
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || !$purposesubcategoryid) {
	    echo json_encode(['status' => 'Error', 'message' => 'Invalid input data']);
	    die();
	}

	// Generate new purpose code
	if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		$mxprcode = credit_maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);
		if ($mxprcode) {
		    $mxprcodeexp = explode(".", $mxprcode);
		    $newmaxprovidedcode = $purposesubcategoryid . '.' . addZeroBeforeTen($mxprcodeexp[1] + 1);
		} else {
		    $newmaxprovidedcode = $purposesubcategoryid . '.01';
		} 
	}else{
		$mxprcode = maxprovidecode_frm($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);
		if ($mxprcode) {
		    $mxprcodeexp = explode(".", $mxprcode);
		    $newmaxprovidedcode = $purposesubcategoryid . '.' . addZeroBeforeTen($mxprcodeexp[1] + 1);
		} else {
		    $newmaxprovidedcode = $purposesubcategoryid . '.01';
		}    
	}

	// preview the purpose using prepared statements
	$rrp = ['status' => 'Success', 'providedcode'=>$newmaxprovidedcode];
	echo json_encode($rrp);
}else if($action=="maxnew_subcategoryid_client"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$purposetype = $_POST['purposetype'] ?? null;

	if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		$pcodemaster = "purpose_codemaster_client_credit";
	}else{
		$pcodemaster = "purpose_codemaster_client";
	}
	$rrp = [];
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid) {
	    echo json_encode(['status' => 'Error', 'message' => 'Invalid input data']);
	    die();
	}

	// Generate new purpose code
	$newmaxsubcatid = maxsubcategorycode_frm_client($conn,$companyid,$subcompanyid,$projectid,$purposetype);
	// preview the purpose using prepared statements
	$rrp = ['status' => 'Success', 'maxsubcatid'=>($newmaxsubcatid +1)];
	echo json_encode($rrp);
}else if($action=="maxnew_providedcode_client"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$purposesubcategoryid = $_POST['purposesubcategoryid'] ?? null;
	$purposetype = $_POST['purposetype'] ?? null;

	if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		$pcodemaster = "purpose_codemaster_client_credit";
	}else{
		$pcodemaster = "purpose_codemaster_client";
	}
	$rrp = [];
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || !$purposesubcategoryid) {
	    echo json_encode(['status' => 'Error', 'message' => 'Invalid input data']);
	    die();
	}

	// Generate new purpose code
	if(strtolower($purposetype)=="credit" || strtolower($purposetype)=="deposit"){
		$mxprcode = credit_maxprovidecode_frm_client($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);
		if ($mxprcode) {
		    $mxprcodeexp = explode(".", $mxprcode);
		    $newmaxprovidedcode = $purposesubcategoryid . '.' . addZeroBeforeTen($mxprcodeexp[1] + 1);
		} else {
		    $newmaxprovidedcode = $purposesubcategoryid . '.01';
		} 
	}else{
		$mxprcode = maxprovidecode_frm_client($conn, $companyid, $subcompanyid, $projectid, $purposesubcategoryid);
		if ($mxprcode) {
		    $mxprcodeexp = explode(".", $mxprcode);
		    $newmaxprovidedcode = $purposesubcategoryid . '.' . addZeroBeforeTen($mxprcodeexp[1] + 1);
		} else {
		    $newmaxprovidedcode = $purposesubcategoryid . '.01';
		}    
	}

	// preview the purpose using prepared statements
	$rrp = ['status' => 'Success', 'providedcode'=>$newmaxprovidedcode];
	echo json_encode($rrp);
}else if($action=="bankaccountlist_frommaster"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"allaccountofproject",$projectid);
	echo json_encode($accinfo);
}else if($action=="purposecodefrommaster_creditside"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	$subcategoryidval = $_POST['subcategoryidval'];
	
	if($categoryidval=="all" && $subcategoryidval=="all"){ 
		$creditprp=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	}else if($categoryidval!="" && $subcategoryidval!=""){
		$creditprp=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"subcategoryid",$subcategoryidval); 
	}else if($categoryidval!="" && $subcategoryidval==""){
		$creditprp=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecategoryid",$categoryidval); 
	} else{
		$creditprp=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	}
	echo json_encode($creditprp);
}else if($action=="purposecodefrommaster"){
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
}else if($action == "totalentriesfrombook"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecode = $_POST['purposecode'];
	$totalentries=gettotal_entries($conn,$companyid,$subcompanyid,$projectid,$purposecode);
	echo $totalentries;
}else if($action=="getclientmasterdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$sortby = $_POST['sortby']; 
	$sortbyvalue = $_POST['sortbyvalue'];
	$client_data=getclientdetils_info($conn,$companyid,$subcompanyid,$projectid,$sortby,$sortbyvalue);	 
	echo json_encode($client_data);
}else if($action=="getcountrynameprojectbased"){ 
	$projectid = $_POST['projectid'];  
	$countryname=getcountryname_project($conn, $projectid );	 
	echo  $countryname;
}else if($action=="getintermediatedata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$sortby = $_POST['sortby']; 
	$sortbyvalue = $_POST['sortbyvalue']; 	 
	$client_data=getintermediatedetails($conn,$companyid,$subcompanyid,$projectid,$sortby,$sortbyvalue);
	 
	echo json_encode($client_data);
}else if($action=="getpurposecategorylist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allcaterylist","");
	echo json_encode($pcat);
}else if($action=="getpurposecategorylistform_mainmaster"){
	if(isset($_POST['purposetype'])){
		$type = strtolower($_POST['purposetype']??'');
	}
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];

	if(strtolower($type)=="credit" || strtolower($type)=="deposit"){
		$pcat=getpurposecategorymainmaster_credit($conn,$shortby,$shortbyvalue);
	}else{
		$pcat=getpurposecategorymainmaster($conn,$shortby,$shortbyvalue);
	}
	echo json_encode($pcat);
}else if($action=="subcategorylistfrommastertbl"){
	$shortby=$_POST['shortby'];
	$shortbyvalue=$_POST['shortbyvalue'];
	$psubcat=getMainSubcategoryMaster($conn,$shortby,$shortbyvalue);
	echo json_encode($psubcat);
}else if($action=="subcategorylistwithcategoryid"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allsubcaterylist_withcategoryid",$categoryid);
	echo json_encode($pcat);
}else if($action=="subcategorylistwithcategoryid_credit"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allsubcaterylist_withcategoryid",$categoryid);
	echo json_encode($pcat);
}else if($action=="getpurposesubcategorylist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allsubcaterylist_withoutcategoryid","");
	echo json_encode($pcat);
}else if($action=="getallpurposewithprojectid_withstatus"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allwithoutvisiblestatus","");
	echo json_encode($pcat);
}else if($action=="getallpurposewithprojectid_creditwithstatus"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allwithoutvisiblestatus","");
	echo json_encode($pcat);
}else if($action=="getallpurposelistofproject"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($pcat);
}else if($action=="getallpurposelistofproject_credit"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($pcat);
}else if($action=="purposeliswithcategoryandsubcategory"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$subcategoryid=$_POST['subcategoryid'];
	$catsubcat=$categoryid."|".$subcategoryid;
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"withcategory_subcategory",$catsubcat);
	echo json_encode($pcat);
}else if($action=="purposelist_withcategoryid"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecategoryid",$categoryid);
	// print_r($pcat);
	echo json_encode($pcat);
}else if($action=="purposeliswithcategoryandsubcategory_credit"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$subcategoryid=$_POST['subcategoryid'];
	$catsubcat=$categoryid."|".$subcategoryid;
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"withcategory_subcategory",$catsubcat);
	echo json_encode($pcat);
}else if($action=="purposeliswithlikelysubcategory"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$likelysubcategoryname=$_POST['likelysubcategoryname'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposesubcategorynamelikely",$likelysubcategoryname);
	echo json_encode($pcat);
}else if($action=="subcategoryliswithlikelysubcategory"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$likelysubcategoryname=$_POST['likelysubcategoryname'];
	$shortbyvalue=$categoryid."|".$likelysubcategoryname;
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"subcatlistwithlikelysubcatnamewithcatid",$shortbyvalue);
	echo json_encode($pcat);
}else if($action=="clientsubcategoryliswithlikelysubcategory"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$categoryid=$_POST['categoryid'];
	$likelysubcategoryname=$_POST['likelysubcategoryname'];
	$shortbyvalue=$categoryid."|".$likelysubcategoryname;
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"clientsubcategoryliswithlikelysubcategory",$shortbyvalue);
	echo json_encode($pcat);
}else if($action=="purposelistwithcategory"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($pcat);
}else if($action=="purposelistwithsubcategory"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$subcategory=$_POST['purposesubcategory'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposesubcategory",$subcategory);
	echo json_encode($pcat);
}else if($action=="getcreditpurposecategorylist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allcaterylist","");
	echo json_encode($pcat);
}else if($action=="getcreditpurposesubcategorylist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"allsubcaterylist_withoutcategoryid","");
	echo json_encode($pcat);
}else if($action=="getallcreditpurposelistofproject"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($pcat);
}else if($action=="purposedataparts"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$purposeid=$_POST['purposeid'];
	$pcat=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposeid",$purposeid);
	echo json_encode($pcat);
}else if($action=="creditpurposedataparts"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$purposeid=$_POST['purposeid'];
	$pcat=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposeid",$purposeid);
	echo json_encode($pcat);
}else if($action=="save_editpurposedetailsnew") {
	$edittype=$_POST['edittype'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$newcategoryid=isset($_POST['newcategoryid'])?$_POST['newcategoryid']:0;
	$newcategory=$_POST['newcategory'];
	$newsubcategoryid=$_POST['newsubcategoryid']?$_POST['newsubcategoryid']:0;
	$newsubcategory=$_POST['newsubcategory'];
	$newpurposeid=$_POST['newpurposeid']?$_POST['newpurposeid']:0;
	$newpurpose=$_POST['newpurpose'];
	$existingprovidedcode=$_POST['existingprovidedcode'];
	$newprovidedcode=$_POST['newprovidedcode'];
	$existingpurposecode=$_POST['existingpurposecode'];
	$newpurposecode =$_POST['newpurposecode'];
	$paymenttype="";
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    $sser = array();
    try{
		switch($edittype){
			case "spelling": //change only purpose in purpose_code_master and other related tables. Other information will remain the same.
				$paymenttype=$newcategory;
				$conn->query("Update purpose_code_master set purpose='".$newpurpose."' where purposecode='".$existingpurposecode."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
				$getmain=$conn->query("Select b.reference_no from tbl_maintransaction_breakdown as b join tbl_maintransaction t on (t.reference_no=b.reference_no) where b.purpose_code='".$existingpurposecode."' and ifnull(t.audit_status,'')=''");
				if ($getmain->num_rows>0) {
		 			while ($mrow=$getmain->fetch_assoc()) {
		 				$conn->query("Update tbl_maintransaction set paymenttype='".$paymenttype."' where reference_no='".$mrow['reference_no']."'");
		 				$conn->query("Update tbl_maintransaction_breakdown b join tbl_maintransaction t on (t.reference_no=b.reference_no) set b.purpose_category='".$newcategory."',b.purpose_categoryid='".$newcategoryid."',b.purpose_subcategory='".$newsubcategory."',b.purpose_subcategoryid='".$newsubcategoryid."',b.purpose='".$newpurpose."',b.purpose_code='".$newpurposecode."' where b.reference_no='".$mrow['reference_no']."' ");
		 			}
				}
				break;
			case "existing": //Change the purpose_information in all related tables except purpose_code_master
				$paymenttype=$newcategory;
				$getmain=$conn->query("Select b.reference_no from tbl_maintransaction_breakdown as b join tbl_maintransaction t on (t.reference_no=b.reference_no) where b.purpose_code='".$existingpurposecode."' and ifnull(t.audit_status,'')=''");
				if ($getmain->num_rows>0) {
		 			while ($mrow=$getmain->fetch_assoc()) {
		 				$conn->query("Update tbl_maintransaction set paymenttype='".$paymenttype."' where reference_no='".$mrow['reference_no']."'");
		 				$conn->query("Update tbl_maintransaction_breakdown b join tbl_maintransaction t on (t.reference_no=b.reference_no) set b.purpose_category='".$newcategory."',b.purpose_categoryid='".$newcategoryid."',b.purpose_subcategory='".$newsubcategory."',b.purpose_subcategoryid='".$newsubcategoryid."',b.purpose='".$newpurpose."',b.purpose_code='".$newpurposecode."' where b.reference_no='".$mrow['reference_no']."' ");
		 			}
				}
				break;
			case "new": //change all purpose_information in purpose_code_master and other related tables. The older purpose_nformation will be flagged.
				if(!$newcategoryid){
					$qrymaxcatid="select max(cast(categoryid as unsigned))+1 as maxcatid FROM purpose_category_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result2=$conn->query($qrymaxcatid);
					if ($result2->num_rows>0) {
						$crow=$result2->fetch_assoc();
						$newcategoryid=$crow['maxcatid'];

						$conn->query("insert into purpose_category_master(categoryid,category,cmp_id,subcmp_id,projectid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."')");
					}
				}
				$paymenttype=$newcategory;

				if(!$newsubcategoryid){
					$getmaxsbid="select max(cast(sub_categoryid as unsigned))+1 as maxsubcatid FROM purpose_category_master where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result3=$conn->query($getmaxsbid);
					if ($result3->num_rows>0) {
						$rowsub=$result3->fetch_assoc();
						$newsubcategoryid=$rowsub['maxsubcatid'];
					}
				}
				$qrysn="select sno from purpose_category_master where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (sub_category is null or sub_category='')";
				$resultsno=$conn->query($qrysn);
				if ($resultsno->num_rows>0) {
					$rowsno=$resultsno->fetch_assoc();
					$snosubcat=$rowsno['sno'];
					$conn->query("Update purpose_category_master set sub_category='".$newsubcategory."',sub_categoryid='".$newsubcategoryid."' where sno='".$snosubcat."'");
				}else{
					$conn->query("insert into purpose_category_master(categoryid,category,cmp_id,subcmp_id,projectid,sub_category,sub_categoryid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."','".$newsubcategory."','".$newsubcategoryid."')");
				}

				if(!$newpurposeid){
					$getmaxpid="select ifnull(max(cast(purposeid as unsigned)),0)+1 as purposeid from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result4=$conn->query($getmaxpid);
					if ($result4->num_rows>0) {
						$rowp=$result4->fetch_assoc();
						$newpurposeid=$rowp['purposeid'];
					}
				}
				$codearray=createPurposeCodeEncoded($companyid,$subcompanyid,$projectid,$newcategoryid,$newsubcategoryid,$newpurposeid,$newprovidedcode);
				$newpurposecode=$codearray[0]["combinedCode"];
				$getpinfo=$conn->query("Select * from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and category='".$newcategory."' and sub_category='".$newsubcategory."' and purpose='".$newpurpose."' and ifnull(tedit,'')=''");
				if ($getpinfo->num_rows>0) {
				 	// code...
				}else{
				 	$conn->query("Insert into purpose_code_master(cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category,purposeid,purpose,purposecode,provided_code)values('".$companyid."','".$subcompanyid."','".$projectid."','".$newcategoryid."','".$newcategory."','".$newsubcategoryid."','".$newsubcategory."','".$newpurposeid."','".$newpurpose."','".$newpurposecode."','".$newprovidedcode."')");
				}
				$conn->query("Update purpose_code_master set tedit=now() where purposecode='".$existingpurposecode."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
				$getmain=$conn->query("Select b.reference_no from tbl_maintransaction_breakdown as b join tbl_maintransaction t on (t.reference_no=b.reference_no) where b.purpose_code='".$existingpurposecode."' and ifnull(t.audit_status,'')=''");
				if ($getmain->num_rows>0) {
			 		while ($mrow=$getmain->fetch_assoc()) {
			 			$conn->query("Update tbl_maintransaction set paymenttype='".$paymenttype."' where reference_no='".$mrow['reference_no']."'");
			 			$conn->query("Update tbl_maintransaction_breakdown b join tbl_maintransaction t on (t.reference_no=b.reference_no) set b.purpose_category='".$newcategory."',b.purpose_categoryid='".$newcategoryid."',b.purpose_subcategory='".$newsubcategory."',b.purpose_subcategoryid='".$newsubcategoryid."',b.purpose='".$newpurpose."',b.purpose_code='".$newpurposecode."' where b.reference_no='".$mrow['reference_no']."' ");
			 		}
				}
				break;
		}
	$conn->commit();
		$sser=array('status'=>'Success', 'message'=>'Update successfully');
  	}
  	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$sser=array('status'=>'Error', 'message'=>'Update is not successful, due to technical issue\n'.$e);
    }
	echo json_encode($sser);
}else if($action=="save_editcreditpurposedetailsnew") {
	$edittype=$_POST['edittype'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$newcategoryid=isset($_POST['newcategoryid'])?$_POST['newcategoryid']:0;
	$newcategory=$_POST['newcategory'];
	$newsubcategoryid=$_POST['newsubcategoryid']?$_POST['newsubcategoryid']:0;
	$newsubcategory=$_POST['newsubcategory'];
	$newpurposeid=$_POST['newpurposeid']?$_POST['newpurposeid']:0;
	$newpurpose=$_POST['newpurpose'];
	$existingprovidedcode=$_POST['existingprovidedcode'];
	$newprovidedcode=$_POST['newprovidedcode'];
	$existingpurposecode=$_POST['existingpurposecode'];
	$newpurposecode =$_POST['newpurposecode'];
	$paymenttype="";
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    $sser = array();
    try{
		switch($edittype){
			case "spelling": //change only purpose in purpose_code_master and other related tables. Other information will remain the same.
				$paymenttype=$newcategory;
				$conn->query("Update purpose_code_master_credit set purpose='".$newpurpose."' where purposecode='".$existingpurposecode."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
				$getmain=$conn->query("Select b.reference_no from tbl_maintransaction_credit_breakdown as b join tbl_maintransaction_credit t on (t.reference_no=b.reference_no) where b.purpose_code='".$existingpurposecode."' and ifnull(t.audit_status,'')=''");
				if ($getmain->num_rows>0) {
		 			while ($mrow=$getmain->fetch_assoc()) {
		 				$conn->query("Update tbl_maintransaction_credit set paymenttype='".$paymenttype."' where reference_no='".$mrow['reference_no']."'");
		 				$conn->query("Update tbl_maintransaction_credit_breakdown b join tbl_maintransaction_credit t on (t.reference_no=b.reference_no) set b.purpose_category='".$newcategory."',b.purpose_categoryid='".$newcategoryid."',b.purpose_subcategory='".$newsubcategory."',b.purpose_subcategoryid='".$newsubcategoryid."',b.purpose='".$newpurpose."',b.purpose_code='".$newpurposecode."' where b.reference_no='".$mrow['reference_no']."' ");
		 			}
				}
				break;
			case "existing": //Change the purpose_information in all related tables except purpose_code_master
				$paymenttype=$newcategory;
				$getmain=$conn->query("Select b.reference_no from tbl_maintransaction_credit_breakdown as b join tbl_maintransaction_credit t on (t.reference_no=b.reference_no) where b.purpose_code='".$existingpurposecode."' and ifnull(t.audit_status,'')=''");
				if ($getmain->num_rows>0) {
		 			while ($mrow=$getmain->fetch_assoc()) {
		 				$conn->query("Update tbl_maintransaction_credit set paymenttype='".$paymenttype."' where reference_no='".$mrow['reference_no']."'");
		 				$conn->query("Update tbl_maintransaction_credit_breakdown b join tbl_maintransaction_credit t on (t.reference_no=b.reference_no) set b.purpose_category='".$newcategory."',b.purpose_categoryid='".$newcategoryid."',b.purpose_subcategory='".$newsubcategory."',b.purpose_subcategoryid='".$newsubcategoryid."',b.purpose='".$newpurpose."',b.purpose_code='".$newpurposecode."' where b.reference_no='".$mrow['reference_no']."' ");
		 			}
				}
				break;
			case "new": //change all purpose_information in purpose_code_master and other related tables. The older purpose_nformation will be flagged.
				if(!$newcategoryid){
					$qrymaxcatid="select max(cast(categoryid as unsigned))+1 as maxcatid FROM purpose_category_master_credit where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result2=$conn->query($qrymaxcatid);
					if ($result2->num_rows>0) {
						$crow=$result2->fetch_assoc();
						$newcategoryid=$crow['maxcatid'];

						$conn->query("insert into purpose_category_master_credit(categoryid,category,cmp_id,subcmp_id,projectid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."')");
					}
				}
				$paymenttype=$newcategory;

				if(!$newsubcategoryid){
					$getmaxsbid="select max(cast(sub_categoryid as unsigned))+1 as maxsubcatid FROM purpose_category_master_credit where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result3=$conn->query($getmaxsbid);
					if ($result3->num_rows>0) {
						$rowsub=$result3->fetch_assoc();
						$newsubcategoryid=$rowsub['maxsubcatid'];
					}
				}
				$qrysn="select sno from purpose_category_master_credit where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (sub_category is null or sub_category='')";
				$resultsno=$conn->query($qrysn);
				if ($resultsno->num_rows>0) {
					$rowsno=$resultsno->fetch_assoc();
					$snosubcat=$rowsno['sno'];
					$conn->query("Update purpose_category_master_credit set sub_category='".$newsubcategory."',sub_categoryid='".$newsubcategoryid."' where sno='".$snosubcat."'");
				}else{
					$conn->query("insert into purpose_category_master_credit(categoryid,category,cmp_id,subcmp_id,projectid,sub_category,sub_categoryid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."','".$newsubcategory."','".$newsubcategoryid."')");
				}

				if(!$newpurposeid){
					$getmaxpid="select ifnull(max(cast(purposeid as unsigned)),0)+1 as purposeid from purpose_code_master_credit where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result4=$conn->query($getmaxpid);
					if ($result4->num_rows>0) {
						$rowp=$result4->fetch_assoc();
						$newpurposeid=$rowp['purposeid'];
					}
				}
				$codearray=createPurposeCodeEncoded($companyid,$subcompanyid,$projectid,$newcategoryid,$newsubcategoryid,$newpurposeid,$newprovidedcode);
				$newpurposecode=$codearray[0]["combinedCode"];
				$getpinfo=$conn->query("Select * from purpose_code_master_credit where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and category='".$newcategory."' and sub_category='".$newsubcategory."' and purpose='".$newpurpose."' and ifnull(tedit,'')=''");
				if ($getpinfo->num_rows>0) {
				 	// code...
				}else{
				 	$conn->query("Insert into purpose_code_master_credit(cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category,purposeid,purpose,purposecode,provided_code)values('".$companyid."','".$subcompanyid."','".$projectid."','".$newcategoryid."','".$newcategory."','".$newsubcategoryid."','".$newsubcategory."','".$newpurposeid."','".$newpurpose."','".$newpurposecode."','".$newprovidedcode."')");
				}
				$conn->query("Update purpose_code_master_credit set tedit=now() where purposecode='".$existingpurposecode."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
				$getmain=$conn->query("Select b.reference_no from tbl_maintransaction_credit_breakdown as b join tbl_maintransaction_credit t on (t.reference_no=b.reference_no) where b.purpose_code='".$existingpurposecode."' and ifnull(t.audit_status,'')=''");
				if ($getmain->num_rows>0) {
					//echo "hello";
			 		while ($mrow=$getmain->fetch_assoc()) {
			 			$conn->query("Update tbl_maintransaction_credit set paymenttype='".$paymenttype."' where reference_no='".$mrow['reference_no']."'");
			 			$conn->query("Update tbl_maintransaction_credit_breakdown b join tbl_maintransaction_credit t on (t.reference_no=b.reference_no) set b.purpose_category='".$newcategory."',b.purpose_categoryid='".$newcategoryid."',b.purpose_subcategory='".$newsubcategory."',b.purpose_subcategoryid='".$newsubcategoryid."',b.purpose='".$newpurpose."',b.purpose_code='".$newpurposecode."' where b.reference_no='".$mrow['reference_no']."' ");
			 		}
				}
				break;
		}
	$conn->commit();
		$sser=array('status'=>'Success', 'message'=>'Update successfully');
  	}
  	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$sser=array('status'=>'Error', 'message'=>'Update is not successful, due to technical issue\n'.$e);
    }
	echo json_encode($sser);
}else if($action=="save_editpurposedetailsnewsno") {
	$edittype=$_POST['edittype'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$existingpurposecode=$_POST['existingpurposecode'];
	$newcategoryid=$_POST['newcategoryid']?$_POST['newcategoryid']:0;
	$newcategory=$_POST['newcategory'];
	$newsubcategoryid=$_POST['newsubcategoryid']?$_POST['newsubcategoryid']:0;
	$newsubcategory=$_POST['newsubcategory'];
	$newpurposeid=$_POST['newpurposeid']?$_POST['newpurposeid']:0;
	$newpurpose=$_POST['newpurpose'];
	$existingprovidedcode=$_POST['existingprovidedcode'];
	$newprovidedcode=$_POST['newprovidedcode'];
	$newpurposecode=$_POST['newpurposecode'];
	$sno=$_POST['sno'];
	$referenceno=$_POST['referenceno'];
	$paymenttype="";
	// die();
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    $sser = array();
    try{
		switch($edittype){
			case "existing": //Change the purpose_information in all related tables except purpose_code_master
				$paymenttype=$newcategory;
 				$conn->query("Update tbl_maintransaction set paymenttype='".$paymenttype."' where reference_no='".$referenceno."'");
 				$conn->query("Update tbl_maintransaction_breakdown set purpose_category='".$newcategory."',purpose_categoryid='".$newcategoryid."',purpose_subcategory='".$newsubcategory."',purpose_subcategoryid='".$newsubcategoryid."',purpose='".$newpurpose."',purpose_code='".$newpurposecode."', provided_code='".$newprovidedcode."' where reference_no='".$referenceno."' and sno='".$sno."'");
				break;
			case "new": //change all purpose_information in purpose_code_master and other related tables. The older purpose_nformation will be flagged.
				if(!$newcategoryid){
					$qrymaxcatid="select max(cast(categoryid as unsigned))+1 as maxcatid FROM purpose_category_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result2=$conn->query($qrymaxcatid);
					if ($result2->num_rows>0) {
						$crow=$result2->fetch_assoc();
						$newcategoryid=$crow['maxcatid'];

						$conn->query("insert into purpose_category_master(categoryid,category,cmp_id,subcmp_id,projectid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."')");
					}
				}
				$paymenttype=$newcategory;

				if(!$newsubcategoryid){
					$getmaxsbid="select max(cast(sub_categoryid as unsigned))+1 as maxsubcatid FROM purpose_category_master where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result3=$conn->query($getmaxsbid);
					if ($result3->num_rows>0) {
						$rowsub=$result3->fetch_assoc();
						$newsubcategoryid=$rowsub['maxsubcatid'];
					}
				}

				$qrysn="select sno from purpose_category_master where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (sub_category is null or sub_category='')";
				$resultsno=$conn->query($qrysn);
				if ($resultsno->num_rows>0) {
					$rowsno=$resultsno->fetch_assoc();
					$snosubcat=$rowsno['sno'];
					$conn->query("Update purpose_category_master set sub_category='".$newsubcategory."',sub_categoryid='".$newsubcategoryid."' where sno='".$snosubcat."'");
				}else{
					$conn->query("insert into purpose_category_master(categoryid,category,cmp_id,subcmp_id,projectid,sub_category,sub_categoryid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."','".$newsubcategory."','".$newsubcategoryid."')");
				}

				if(!$newpurposeid){
					$getmaxpid="select ifnull(max(cast(purposeid as unsigned)),0)+1 as purposeid from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result4=$conn->query($getmaxpid);
					if ($result4->num_rows>0) {
						$rowp=$result4->fetch_assoc();
						$newpurposeid=$rowp['purposeid'];
					}
				}
				$codearray=createPurposeCodeEncoded($companyid,$subcompanyid,$projectid,$newcategoryid,$newsubcategoryid,$newpurposeid,$newprovidedcode);
				$newpurposecode=$codearray[0]["combinedCode"];

				$getpinfo=$conn->query("Select * from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and category='".$newcategory."' and sub_category='".$newsubcategory."' and purpose='".$newpurpose."' and ifnull(tedit,'')=''");
				if ($getpinfo->num_rows>0) {
				 	// code...
				}else{
				 	$conn->query("Insert into purpose_code_master(cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category,purposeid,purpose,purposecode,provided_code)values('".$companyid."','".$subcompanyid."','".$projectid."','".$newcategoryid."','".$newcategory."','".$newsubcategoryid."','".$newsubcategory."','".$newpurposeid."','".$newpurpose."','".$newpurposecode."','".$newprovidedcode."')");
				}
			 	$conn->query("Update tbl_maintransaction set paymenttype='".$paymenttype."' where reference_no='".$referenceno."'");
			 	$conn->query("Update tbl_maintransaction_breakdown set purpose_category='".$newcategory."',purpose_categoryid='".$newcategoryid."',purpose_subcategory='".$newsubcategory."',purpose_subcategoryid='".$newsubcategoryid."',purpose='".$newpurpose."',purpose_code='".$newpurposecode."' where reference_no='".$referenceno."' and sno='".$sno."'");
				break;
		}

	if (strtolower($newcategory)=='imprest' && strtolower($newcategory)=='imprest'){
		$impid="";
		$getdata=$conn->query("select m.reference_no,m.transactionno,m.bookdate,b.amount as book_amount,m.payto,m.paymenttype,m.referenceno_clb,b.purpose,b.beneficiary_name,m.payto,b.description,b.description_bank from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcmppanyid."' and m.projectid='".$projectid."' and b.sno='".$sno."' and (m.tedit is null or m.tedit='') and (b.tedit is null or b.tedit='') and m.payfromaccountid='".$accountid."'");
		if ($getdata->num_rows>0) {
		 	while ($rowim=$getdata->fetch_assoc()){
		 		$paytoimp=$rowim['payto'];
		 		$imp=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'Imprestwithname',$paytoimp);
		 		if (count($imp)>0) {
		 			foreach ($imp as $impkey) {
		 				$impid=$impkey['accountid'];
		 			}
		 		}else{
		 			$getmaxid=$conn->query("select ifnull(max(accountid)+1,7000) as accountid from tbl_master_account where lcase(account_type)='imprest'");
		 			if ($getmaxid->num_rows>0) {
		 				$rowid=$getmaxid->fetch_assoc();
		 				$impid=$rowid['accountid'];
		 			}
		 			$conn->query("Insert into tbl_master_account(cmp_id,subcmp_id,projectid,accountid,account_type,accountname,accountholdername)values('".$companyid."','".$subcompanyid."','".$projectid."','".$impid."','Imprest','".$paytoimp."','".$paytoimp."')");
		 		}
 				$des=$rowim['description']." ".$rowim['description_bank'];
 				$conn->query("Insert into imprest_account(idate,companyid,subcompanyid,projectid,from_accountid,to_accountid,reference_no,imprest_payto,imprest_amount,purpose_category,purpose_categoryid,purpose_subcategory,purpose_subcategoryid,purpose,purpose_code,beneficiary_name,description)values('".$rowim['bookdate']."','".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$impid."','".$referenceno."','".$paytoimp."','".$rowim['book_amount']."','".$newcategory."','".$newcategoryid."','".$newsubcategory."','".$newsubcategoryid."','".$newpurpose."','".$newpurposecode."','".$rowim['beneficiary_name']."','".$des."')");
		 	}
		}
	}elseif (strtolower($newcategory)=='cash' && strtolower($newcategory)=='cash'){
		$impid="";
		$getdata=$conn->query("select m.reference_no,m.transactionno,m.bookdate,m.book_amount,m.payto,m.paymenttype,m.referenceno_clb,b.purpose,b.beneficiary_name,m.payto,b.description,b.description_bank from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcmppanyid."' and m.projectid='".$projectid."' and  b.sno='".$sno."' and (m.tedit is null or m.tedit='') and (b.tedit is null or b.tedit='') and m.payfromaccountid='".$accountid."'");
		if ($getdata->num_rows>0) {
		 	while ($rowim=$getdata->fetch_assoc()) {
		 		$paytoimp=$rowim['payto'];
		 		$imp=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'cash','');
		 		if (count($imp>0)){
		 			foreach ($imp as $impkey) {
		 				$impid=$impkey['accountid'];
		 			}
		 		}else{
		 			$getmaxid=$conn->query("select ifnull(max(accountid)+1,6000) as maxid from tbl_master_account where lcase(account_type)='cash'");
		 			if ($getmaxid->num_rows>0) {
		 				$rowid=$getmaxid->fetch_assoc();
		 				$impid=$rowid['maxid'];
		 			}
		 			$conn->query("Insert into tbl_master_account(cmp_id,subcmp_id,projectid,accountid,account_type,accountname,accountholdername)values('".$companyid."','".$subcompanyid."','".$projectid."','".$impid."','Cash','Cash','Cash')");
		 		}
 				$des=$rowim['description']." ".$rowim['description_bank'];
		 		$conn->query("Insert into cash_account(idate,companyid,subcompanyid,projectid,from_accountid,to_accountid,reference_no,cash_payto,cash_amount,purpose_category,purpose_categoryid,purpose_subcategory,purpose_subcategoryid,purpose,purpose_code,beneficiary_name,description)values('".$rowim['bookdate']."','".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$impid."','".$referenceno."','".$paytoimp."','".$rowim['book_amount']."','".$newcategory."','".$newcategoryid."','".$newsubcategory."','".$newsubcategoryid."','".$newpurpose."','".$newpurposecode."','".$rowim['beneficiary_name']."','".$des."')");
		 	}
		}
	}
	$conn->commit();
		$sser[]=array('status'=>'Success', 'message'=>'Update successfully');
  	}
  	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$sser[]=array('status'=>'Error', 'message'=>'Update is not successful, due to technical issue\n'.$e);
    }
    echo json_encode($sser);
}else if($action=="save_editcreditpurposedetailsnewsno") {
	$edittype=$_POST['edittype'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$existingpurposecode=$_POST['existingpurposecode'];
	$newcategoryid=$_POST['newcategoryid']?$_POST['newcategoryid']:0;
	$newcategory=$_POST['newcategory'];
	$newsubcategoryid=$_POST['newsubcategoryid']?$_POST['newsubcategoryid']:0;
	$newsubcategory=$_POST['newsubcategory'];
	$newpurposeid=$_POST['newpurposeid']?$_POST['newpurposeid']:0;
	$newpurpose=$_POST['newpurpose'];
	$existingprovidedcode=$_POST['existingprovidedcode'];
	$newprovidedcode=$_POST['newprovidedcode'];
	$newpurposecode=$_POST['newpurposecode'];
	$sno=$_POST['sno'];
	$referenceno=$_POST['referenceno'];
	$paymenttype="";
	// die();
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    $sser = array();
    try{
		switch($edittype){
			case "existing": //Change the purpose_information in all related tables except purpose_code_master
				$paymenttype=$newcategory;
 				$conn->query("Update tbl_maintransaction_credit set paymenttype='".$paymenttype."' where reference_no='".$referenceno."'");
 				$conn->query("Update tbl_maintransaction_credit_breakdown set purpose_category='".$newcategory."',purpose_categoryid='".$newcategoryid."',purpose_subcategory='".$newsubcategory."',purpose_subcategoryid='".$newsubcategoryid."',purpose='".$newpurpose."',purpose_code='".$newpurposecode."', provided_code='".$newprovidedcode."' where reference_no='".$referenceno."' and sno='".$sno."'");
				break;
			case "new": //change all purpose_information in purpose_code_master and other related tables. The older purpose_nformation will be flagged.
				if(!$newcategoryid){
					$qrymaxcatid="select max(cast(categoryid as unsigned))+1 as maxcatid FROM purpose_category_master_credit where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result2=$conn->query($qrymaxcatid);
					if ($result2->num_rows>0) {
						$crow=$result2->fetch_assoc();
						$newcategoryid=$crow['maxcatid'];

						$conn->query("insert into purpose_category_master_credit(categoryid,category,cmp_id,subcmp_id,projectid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."')");
					}
				}
				$paymenttype=$newcategory;

				if(!$newsubcategoryid){
					$getmaxsbid="select max(cast(sub_categoryid as unsigned))+1 as maxsubcatid FROM purpose_category_master_credit where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result3=$conn->query($getmaxsbid);
					if ($result3->num_rows>0) {
						$rowsub=$result3->fetch_assoc();
						$newsubcategoryid=$rowsub['maxsubcatid'];
					}
				}

				$qrysn="select sno from purpose_category_master_credit where categoryid='".$newcategoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (sub_category is null or sub_category='')";
				$resultsno=$conn->query($qrysn);
				if ($resultsno->num_rows>0) {
					$rowsno=$resultsno->fetch_assoc();
					$snosubcat=$rowsno['sno'];
					$conn->query("Update purpose_category_master_credit set sub_category='".$newsubcategory."',sub_categoryid='".$newsubcategoryid."' where sno='".$snosubcat."'");
				}else{
					$conn->query("insert into purpose_category_master_credit(categoryid,category,cmp_id,subcmp_id,projectid,sub_category,sub_categoryid)values('".$newcategoryid."','".$newcategory."','".$companyid."','".$subcompanyid."','".$projectid."','".$newsubcategory."','".$newsubcategoryid."')");
				}

				if(!$newpurposeid){
					$getmaxpid="select ifnull(max(cast(purposeid as unsigned)),0)+1 as purposeid from purpose_code_master_credit where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' ";
					$result4=$conn->query($getmaxpid);
					if ($result4->num_rows>0) {
						$rowp=$result4->fetch_assoc();
						$newpurposeid=$rowp['purposeid'];
					}
				}
				$codearray=createPurposeCodeEncoded($companyid,$subcompanyid,$projectid,$newcategoryid,$newsubcategoryid,$newpurposeid,$newprovidedcode);
				$newpurposecode=$codearray[0]["combinedCode"];

				$getpinfo=$conn->query("Select * from purpose_code_master_credit where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and category='".$newcategory."' and sub_category='".$newsubcategory."' and purpose='".$newpurpose."' and ifnull(tedit,'')=''");
				if ($getpinfo->num_rows>0) {
				 	// code...
				}else{
				 	$conn->query("Insert into purpose_code_master_credit(cmp_id,subcmp_id,projectid,categoryid,category,sub_categoryid,sub_category,purposeid,purpose,purposecode,provided_code)values('".$companyid."','".$subcompanyid."','".$projectid."','".$newcategoryid."','".$newcategory."','".$newsubcategoryid."','".$newsubcategory."','".$newpurposeid."','".$newpurpose."','".$newpurposecode."','".$newprovidedcode."')");
				}
			 	$conn->query("Update tbl_maintransaction_credit set paymenttype='".$paymenttype."' where reference_no='".$referenceno."'");
			 	$conn->query("Update tbl_maintransaction_credit_breakdown set purpose_category='".$newcategory."',purpose_categoryid='".$newcategoryid."',purpose_subcategory='".$newsubcategory."',purpose_subcategoryid='".$newsubcategoryid."',purpose='".$newpurpose."',purpose_code='".$newpurposecode."' where reference_no='".$referenceno."' and sno='".$sno."'");
				break;
		}
	$conn->commit();
		$sser[]=array('status'=>'Success', 'message'=>'Update successfully');
  	}
  	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$sser[]=array('status'=>'Error', 'message'=>'Update is not successful, due to technical issue\n'.$e);
    }
    echo json_encode($sser);
}else if($action=="getaccountinfobyproject") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$shortby="projectid";
	$accountlist=array();
	$shortbyvalue=$projectid;
	$projectlist =getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	// print_r($projectlist);
	foreach ($projectlist as $reprojrow) 
	{
		$accountlist[]= array("accid"=>$reprojrow['accountid'], "accountid"=>$reprojrow['accountid'], "accname"=>$reprojrow['accountname'], "accountname"=>$reprojrow['accountname'],"accno"=>$reprojrow['accountno'], "accountno"=>$reprojrow['accountno'],"bankname"=>$reprojrow['bankname'],"bankid"=>$reprojrow['bankid']);
	}
	echo json_encode($accountlist);
}else if($action=="getwithdrawalcategories") {
	$catlist =getwithdrawalcategoriesinfo($mconn);
	echo json_encode($catlist);
}else if($action=="get11bsdata"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$html = get11bsdata($conn,$companyid,$subcompanyid,$projectid);
	echo $html;
}else if($action=="save11bsdata"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$fromDate=$_POST['fromDate'];
	$toDate=$_POST['toDate'];
	$tbldata = json_decode($_POST['tbldata'],true);
	$retval = save11bsdata($conn,$companyid,$subcompanyid,$projectid,$fromDate,$toDate,$tbldata);
	echo $retval;
}else if($action=="get11bsdatacredit"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$html = get11bsdatacredit($conn,$companyid,$subcompanyid,$projectid);
	echo $html;
}else if($action=="save11bsdatacredit"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$fromDate=$_POST['fromDate'];
	$toDate=$_POST['toDate'];
	$tbldata = json_decode($_POST['tbldata'],true);
	$retval = save11bsdatacredit($conn,$companyid,$subcompanyid,$projectid,$fromDate,$toDate,$tbldata);
	echo $retval;
}else if($action=="getaccountofonlybanks") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$shortby="allaccountofprojectonlybank";
	$accountlist=array();
	$shortbyvalue=$projectid;
	$projectlist =getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	foreach ($projectlist as $reprojrow) 
	{
		$accountlist[]= array("accid"=>$reprojrow['accountid'], "accname"=>$reprojrow['accountname'],"accno"=>$reprojrow['accountno'],"bankname"=>$reprojrow['bankname'],"bankid"=>$reprojrow['bankid']);
	}
	echo json_encode($accountlist);
}else if($action=="excelsheetfunctioninfo"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$functionstype = $_POST['functionstype'];
	$updsheetdata=uploadsalarysheet_functions($conn,$companyid,$subcompanyid,$projectid,"uploadtype",$functionstype);
	// print_r($updsheetdata);
	echo json_encode($updsheetdata);	
}else if($action=="payerlist_callback"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytolist=getpayerinfo($conn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($paytolist);
}else if($action=="ispaytohavedetails_callback"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$paytoid = $_POST['paytoid'];
	$ishavedetails = $_POST['ishavedetails'];
	$paytolist=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	// print_r($paytolist);
	$returnpaytoinfo=array();
	if(count($paytolist)>0){
		foreach ($paytolist as $ptyhkey) {
			if ($ishavedetails == "bankdetails") {
			    if (empty($ptyhkey['toaccountno'])) {
			        $returnpaytoinfo[] = array('status' => 'Error', 'paytoid'=>$paytoid, 'message' => 'Payto bank details are not available');
			    }else{
					$returnpaytoinfo = $paytolist;
				}
			}else if ($ishavedetails == "taxdetails") {
			    if (empty($ptyhkey['taxid'])) {
			        $returnpaytoinfo[] = array('status' => 'Error', 'paytoid'=>$paytoid, 'message' => 'Payto tax details are not available');
			    }else{
					$returnpaytoinfo = $paytolist;
				}
			}else{
				$returnpaytoinfo = $paytolist;
			}
		}
	}
	echo json_encode($returnpaytoinfo);
}else if($action=="paytolist_callback"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytolist=getpaytoinfo($conn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($paytolist);
}else if($action=="paytofulldetails_callback"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid=$_POST['paytoidval'];
	$paytolist=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	echo json_encode($paytolist);
}else if($action=="beneficiarydetailsfrommaster_callback"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$bnflist=getbeneficiaryinfodetails($conn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($bnflist);
}else if($action=="updatepaytodetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid = $_POST['paytoid'];
	$oldpaytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	$oldpaytoname="";
	if(count($oldpaytoinfo)>0){
		foreach ($oldpaytoinfo as $olpkey) {
			$oldpaytoname=$olpkey['paytoname'];
		}
	}
	$paytoname = $_POST['paytoname'];
	$paytobankname = $_POST['paytobankname'];
	$paytobankaccountno = $_POST['paytobankaccountno'];
	$paytobankifscocde = $_POST['paytobankifscocde'];
	$loginname = $_POST['loginname'];
	$prrst=array();
	$updateto="";
	if(isset($_POST['paytofor'])){
		$paytofor = $_POST['paytofor'];
		$updateto = explode(",", $paytofor);
	}
	$upd=0;
	if(count($updateto)>0){
		foreach ($updateto as $uppaytkey => $pval) {
			if($pval=="paytomaster"){
				$prrst=updatepaytomasterdetails($conn,$paytoname,$paytobankaccountno,$paytobankname,$paytobankifscocde,'','','',$companyid,$subcompanyid,$projectid,$paytoid,$loginname=null);

			}
			else if($pval=="transactionstable"){
				//find last audit date
				$ssq=$conn->query("SELECT bookdate FROM tbl_maintransaction where audit_status<>'' and (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' order by sno desc limit 1");
				$updafterdate="";
				if($ssq->num_rows){
					$lrow=$ssq->fetch_assoc();
					$updafterdate=$lrow['bookdate'];
				}

				//check maintransaction and update
				if($updafterdate){
					$bookdate = " and bookdate>'".$updafterdate."'";
				}
				$ssqm=$conn->query("SELECT sno,payto FROM tbl_maintransaction where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."' and (audit_status is null or audit_status='') ".$bookdate);
				if($ssqm->num_rows>0){
					$upd=1;
					while($pmrow=$ssqm->fetch_assoc()){
						$conn->query("UPDATE tbl_maintransaction SET payto='".strtoupper($paytoname)."' where sno='".$pmrow['sno']."'");

					}
				}				

				//check in imprest table and update it
				$mnbrkdate="";
				if($updafterdate){
					$mnbrkdate = " and edatetime>'".$updafterdate."'";
				}
				$ssqm=$conn->query("SELECT sno,beneficiary_name FROM tbl_maintransaction_breakdown where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."' ".$mnbrkdate);
				if($ssqm->num_rows>0){
					$upd=1;
					while($bpmrow=$ssqm->fetch_assoc()){
						$conn->query("UPDATE tbl_maintransaction_breakdown SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$bpmrow['sno']."'");
					}
				}


				//check in create request and update it
				$rqdate="";
				if($updafterdate){
					$rqdate = " and request_date>'".$updafterdate."'";
				}
				$ssqm=$conn->query("SELECT sno,payto FROM tbl_paymentrequest where (tedit is null or tedit='') and (approval_status is null or approval_status='' or approval_status='Payment Paid') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."' ".$rqdate);
				if($ssqm->num_rows>0){
					$upd=1;
					while($rqrow=$ssqm->fetch_assoc()){
						$conn->query("UPDATE tbl_paymentrequest SET payto='".strtoupper($paytoname)."' where sno='".$rqrow['sno']."'");
					}
				}

				//check in create request breakdown and update it
				$rqbdate="";
				if($updafterdate){
					$rqbdate = " and etdatetime>'".$updafterdate."'";
				}
				$rqbqm=$conn->query("SELECT sno,beneficiery_name FROM tbl_paymentrequest_breakdown where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiery_name)='".strtolower($oldpaytoname)."' ".$rqbdate);
				if($rqbqm->num_rows>0){
					$upd=1;
					while($rqbrow=$rqbqm->fetch_assoc()){
						$conn->query("UPDATE tbl_paymentrequest_breakdown SET beneficiery_name='".strtoupper($paytoname)."' where sno='".$rqbrow['sno']."'");
					}
				}

				//check in imprest account and update it
				$impdate="";
				if($updafterdate){
					$impdate = " and etdatetime>'".$updafterdate."'";
				}

				$impq=$conn->query("SELECT sno,imprest_payto FROM imprest_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(imprest_payto)='".strtolower($oldpaytoname)."' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($improw=$impq->fetch_assoc()){
						$conn->query("UPDATE imprest_account SET imprest_payto='".strtoupper($paytoname)."' where sno='".$improw['sno']."'");
					}
				}

				$impq=$conn->query("SELECT sno,beneficiary_name FROM imprest_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."'  and beneficiary_name<>'' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($improw=$impq->fetch_assoc()){
						$conn->query("UPDATE imprest_account SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$improw['sno']."'");
					}
				}


				//check in partial account and update it
				$impdate="";
				if($updafterdate){
					$impdate = " and etdatetime>'".$updafterdate."'";
				}
				$impq=$conn->query("SELECT sno,imprest_payto FROM partial_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(imprest_payto)='".strtolower($oldpaytoname)."' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($prow=$impq->fetch_assoc()){
						$conn->query("UPDATE partial_account SET imprest_payto='".strtoupper($paytoname)."' where sno='".$prow['sno']."'");
					}
				}

				$impq=$conn->query("SELECT sno,beneficiary_name FROM partial_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."' and beneficiary_name<>'' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($pbrow=$impq->fetch_assoc()){
						$conn->query("UPDATE partial_account SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$pbrow['sno']."'");
					}
				}

				//check in imprest authorization and update it
				$impaq=$conn->query("SELECT sno,payto FROM imprest_authorizedperson where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and lcase(payto)='".strtolower($oldpaytoname)."'");
				if($impaq->num_rows>0){
					$upd=1;
					while($improw=$impaq->fetch_assoc()){
						$conn->query("UPDATE imprest_authorizedperson SET payto='".strtoupper($paytoname)."' where sno='".$improw['sno']."'");
					}
				}

				//check in tds account summary and update it
				$tddate="";
				if($updafterdate){
					$tddate = " and etdatetime>'".$updafterdate."'";
				}
				$tdaq=$conn->query("SELECT sno,beneficiary_name FROM tds_account_summery where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."' ".$tddate." ");
				if($tdaq->num_rows>0){
					$upd=1;
					while($tdrow=$tdaq->fetch_assoc()){
						$conn->query("UPDATE tds_account_summery SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$tdrow['sno']."'");
					}
				}

				//check in tds beneficiary master and update it
				$tdbaq=$conn->query("SELECT sno,bnf_name FROM tds_bnf_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(bnf_name)='".strtolower($oldpaytoname)."'");
				if($tdbaq->num_rows>0){
					$upd=1;
					while($tdbrow=$tdbaq->fetch_assoc()){
						$conn->query("UPDATE tds_bnf_master SET bnf_name='".strtoupper($paytoname)."' where sno='".$tdbrow['sno']."'");
					}
				}

				//check in expensesheet data and update it
				$exsdate="";
				if($updafterdate){
					$exsdate = " and date>'".$updafterdate."'";
				}

				$tdbaq=$conn->query("SELECT sno,payto FROM tbl_expencesinexcelsheet where (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."' ".$exsdate." ");
				if($tdbaq->num_rows>0){
					$upd=1;
					while($tdbrow=$tdbaq->fetch_assoc()){
						$conn->query("UPDATE tbl_expencesinexcelsheet SET payto='".strtoupper($paytoname)."' where sno='".$tdbrow['sno']."'");
					}
				}

				//check in master account table and update it
				$tdbaq=$conn->query("SELECT sno,accountid FROM tbl_master_account where (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."'");
				if($tdbaq->num_rows>0){
					$upd=1;
					while($tdbrow=$tdbaq->fetch_assoc()){
						$conn->query("UPDATE tbl_master_account SET accountname='".strtoupper($paytoname)."', accountholdername='".strtoupper($paytoname)."' where sno='".$tdbrow['sno']."'");
					}
				}


				if($upd==1){
					$prrst[] = array("status"=> 'Success', 'message'=>"Payto details updated successfully updated across all relevant places", "paytoid"=>$maxid);
				}


			}
		}
	}
	echo json_encode($prrst);
}else if($action=="updatepayto_fulldetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid = $_POST['paytoid'];
	$oldpaytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	$oldpaytoname="";
	if(count($oldpaytoinfo)>0){
		foreach ($oldpaytoinfo as $olpkey) {
			$oldpaytoname=$olpkey['paytoname'];
		}
	}
	$paytoname = $_POST['paytoname'];
	$paytobankname = $_POST['paytobankname'];
	$paytobankaccountno = $_POST['paytobankaccountno'];
	$paytobankifscocde = $_POST['paytobankifscocde'];
	$bankiban = $_POST['paytobankiban'];
	$banswiftcocde = $_POST['paytobanswiftcocde'];
	$banksortcode = $_POST['paytobanksortcode'];
	$mobileno = $_POST['paytomobileno'];
	$emailaddress = $_POST['paytoemailaddress'];
	$address = $_POST['paytoaddress'];
	$cityaddress = $_POST['paytocityaddress'];
	$postalcodeaddress = $_POST['paytopostalcodeaddress'];
	$countryaddress = $_POST['paytocountryaddress'];
	$taxtype = $_POST['taxtype'];
	$gstorvalno = $_POST['gstorvalno'];

	$loginname = $_POST['loginname'];
	$prrst=array();
	$updateto="";
	if(isset($_POST['paytofor'])){
		$paytofor = $_POST['paytofor'];
		$updateto = explode(",", $paytofor);
	}
	$upd=0;
	if(count($updateto)>0){
		foreach ($updateto as $uppaytkey => $pval) {
			if($pval=="paytomaster"){
				$prrst=updatepaytomaster_fulldetails($conn,$companyid,$subcompanyid,$projectid,$paytoid,$paytoname,$paytobankaccountno,$paytobankname,$paytobankifscocde,$bankiban,$banswiftcocde,$banksortcode,$mobileno,$emailaddress,$address,$cityaddress,$postalcodeaddress,$countryaddress,$taxtype,$gstorvalno,$loginname=null);
			}else if($pval=="transactionstable"){
				//find last audit date
				$ssq=$conn->query("SELECT bookdate FROM tbl_maintransaction where audit_status<>'' and (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' order by sno desc limit 1");
				$updafterdate="";
				if($ssq->num_rows){
					$lrow=$ssq->fetch_assoc();
					$updafterdate=$lrow['bookdate'];
				}

				//check maintransaction and update
				if($updafterdate){
					$bookdate = " and bookdate>'".$updafterdate."'";
				}
				$ssqm=$conn->query("SELECT sno,payto FROM tbl_maintransaction where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."' and (audit_status is null or audit_status='') ".$bookdate);
				if($ssqm->num_rows>0){
					$upd=1;
					while($pmrow=$ssqm->fetch_assoc()){
						$conn->query("UPDATE tbl_maintransaction SET payto='".strtoupper($paytoname)."' where sno='".$pmrow['sno']."'");

					}
				}				

				//check in imprest table and update it
				$mnbrkdate="";
				if($updafterdate){
					$mnbrkdate = " and edatetime>'".$updafterdate."'";
				}
				$ssqm=$conn->query("SELECT sno,beneficiary_name FROM tbl_maintransaction_breakdown where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."' ".$mnbrkdate);
				if($ssqm->num_rows>0){
					$upd=1;
					while($bpmrow=$ssqm->fetch_assoc()){
						$conn->query("UPDATE tbl_maintransaction_breakdown SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$bpmrow['sno']."'");
					}
				}


				//check in create request and update it
				$rqdate="";
				if($updafterdate){
					$rqdate = " and request_date>'".$updafterdate."'";
				}
				$ssqm=$conn->query("SELECT sno,payto FROM tbl_paymentrequest where (tedit is null or tedit='') and (approval_status is null or approval_status='' or approval_status='Payment Paid') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."' ".$rqdate);
				if($ssqm->num_rows>0){
					$upd=1;
					while($rqrow=$ssqm->fetch_assoc()){
						$conn->query("UPDATE tbl_paymentrequest SET payto='".strtoupper($paytoname)."' where sno='".$rqrow['sno']."'");
					}
				}

				//check in create request breakdown and update it
				$rqbdate="";
				if($updafterdate){
					$rqbdate = " and etdatetime>'".$updafterdate."'";
				}
				$rqbqm=$conn->query("SELECT sno,beneficiery_name FROM tbl_paymentrequest_breakdown where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiery_name)='".strtolower($oldpaytoname)."' ".$rqbdate);
				if($rqbqm->num_rows>0){
					$upd=1;
					while($rqbrow=$rqbqm->fetch_assoc()){
						$conn->query("UPDATE tbl_paymentrequest_breakdown SET beneficiery_name='".strtoupper($paytoname)."' where sno='".$rqbrow['sno']."'");
					}
				}

				//check in imprest account and update it
				$impdate="";
				if($updafterdate){
					$impdate = " and etdatetime>'".$updafterdate."'";
				}

				$impq=$conn->query("SELECT sno,imprest_payto FROM imprest_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(imprest_payto)='".strtolower($oldpaytoname)."' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($improw=$impq->fetch_assoc()){
						$conn->query("UPDATE imprest_account SET imprest_payto='".strtoupper($paytoname)."' where sno='".$improw['sno']."'");
					}
				}

				$impq=$conn->query("SELECT sno,beneficiary_name FROM imprest_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."'  and beneficiary_name<>'' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($improw=$impq->fetch_assoc()){
						$conn->query("UPDATE imprest_account SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$improw['sno']."'");
					}
				}


				//check in partial account and update it
				$impdate="";
				if($updafterdate){
					$impdate = " and etdatetime>'".$updafterdate."'";
				}
				$impq=$conn->query("SELECT sno,imprest_payto FROM partial_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(imprest_payto)='".strtolower($oldpaytoname)."' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($prow=$impq->fetch_assoc()){
						$conn->query("UPDATE partial_account SET imprest_payto='".strtoupper($paytoname)."' where sno='".$prow['sno']."'");
					}
				}

				$impq=$conn->query("SELECT sno,beneficiary_name FROM partial_account where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."' and beneficiary_name<>'' ".$impdate." ");
				if($impq->num_rows>0){
					$upd=1;
					while($pbrow=$impq->fetch_assoc()){
						$conn->query("UPDATE partial_account SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$pbrow['sno']."'");
					}
				}

				//check in imprest authorization and update it
				$impaq=$conn->query("SELECT sno,payto FROM imprest_authorizedperson where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and lcase(payto)='".strtolower($oldpaytoname)."'");
				if($impaq->num_rows>0){
					$upd=1;
					while($improw=$impaq->fetch_assoc()){
						$conn->query("UPDATE imprest_authorizedperson SET payto='".strtoupper($paytoname)."' where sno='".$improw['sno']."'");
					}
				}

				//check in tds account summary and update it
				$tddate="";
				if($updafterdate){
					$tddate = " and etdatetime>'".$updafterdate."'";
				}
				$tdaq=$conn->query("SELECT sno,beneficiary_name FROM tds_account_summery where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(beneficiary_name)='".strtolower($oldpaytoname)."' ".$tddate." ");
				if($tdaq->num_rows>0){
					$upd=1;
					while($tdrow=$tdaq->fetch_assoc()){
						$conn->query("UPDATE tds_account_summery SET beneficiary_name='".strtoupper($paytoname)."' where sno='".$tdrow['sno']."'");
					}
				}

				//check in tds beneficiary master and update it
				$tdbaq=$conn->query("SELECT sno,bnf_name FROM tds_bnf_master where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(bnf_name)='".strtolower($oldpaytoname)."'");
				if($tdbaq->num_rows>0){
					$upd=1;
					while($tdbrow=$tdbaq->fetch_assoc()){
						$conn->query("UPDATE tds_bnf_master SET bnf_name='".strtoupper($paytoname)."' where sno='".$tdbrow['sno']."'");
					}
				}

				//check in expensesheet data and update it
				$exsdate="";
				if($updafterdate){
					$exsdate = " and date>'".$updafterdate."'";
				}

				$tdbaq=$conn->query("SELECT sno,payto FROM tbl_expencesinexcelsheet where (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."' ".$exsdate." ");
				if($tdbaq->num_rows>0){
					$upd=1;
					while($tdbrow=$tdbaq->fetch_assoc()){
						$conn->query("UPDATE tbl_expencesinexcelsheet SET payto='".strtoupper($paytoname)."' where sno='".$tdbrow['sno']."'");
					}
				}

				//check in master account table and update it
				$tdbaq=$conn->query("SELECT sno,accountid FROM tbl_master_account where (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(payto)='".strtolower($oldpaytoname)."'");
				if($tdbaq->num_rows>0){
					$upd=1;
					while($tdbrow=$tdbaq->fetch_assoc()){
						$conn->query("UPDATE tbl_master_account SET accountname='".strtoupper($paytoname)."', accountholdername='".strtoupper($paytoname)."' where sno='".$tdbrow['sno']."'");
					}
				}
				if($upd==1){
					$prrst[] = array("status"=> 'Success', 'message'=>"Payto details updated successfully updated across all relevant places", "paytoid"=>$maxid);
				}
			}
		}
	}
	echo json_encode($prrst);
}else if($action=="removedpaytodetailsfrommasterlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid = $_POST['paytoid'];
	$loginname = $_POST['loginname'];
	$oldpaytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	$rrpayto=array();
	if(count($oldpaytoinfo)>0){
		$tedit="removed on date ".date('Y-m-d')." by ".$loginname;
		$conn->query("UPDATE tbl_payto_master SET tedit='".$tedit."' where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and paytoid='".$paytoid."'");
		if(mysqli_affected_rows($conn)>0){
			$rrpayto[]=array('status'=>'Success', 'message'=>'Payto details removed successful');
		}else{
			$rrpayto[]=array('status'=>'Error', 'message'=>'Payto details did not remov, due to technical issuce');
		}
	}
	echo json_encode($rrpayto);
}else if($action=="checkinvoicenoexist_withrequest"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$invoiceno  =$_POST['invoiceno'];
	$chkinvq=$conn->query("SELECT beneficiery_name,ammount,invoice_no,invoicedate,filepath,purpose FROM tbl_paymentrequest_breakdown where ifnull(tedit,'')='' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and invoice_no='".$invoiceno."'");
	$chkresult=array();
	if($chkinvq->num_rows>0){
		while($ckrow=$chkinvq->fetch_assoc()){
			$chkresult[]=array('status'=>'exist','beneficiaryname'=>$ckrow['beneficiery_name'],'amount'=>$ckrow['ammount'],'invoiceno'=>$ckrow['invoice_no'],'invoicedate'=>$ckrow['invoicedate'],'filepath'=>$ckrow['filepath'],'purposename'=>$ckrow['purpose']);
		}
	}
	echo json_encode($chkresult);
}else if($action=="checktransactionnoexist_withaccountid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid  =$_POST['accountid'];
	$transactionno = $_POST['transactionno'];
	$chkinvq=$conn->query("SELECT reference_no, book_amount,transaction_date,transactionno FROM tbl_maintransaction where ifnull(tedit,'')='' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and payfromaccountid='".$accountid."' and transactionno='".$transactionno."'");
	$chkresult=array();
	if($chkinvq->num_rows>0){
		while($ckrow=$chkinvq->fetch_assoc()){
			$chkresult[]=array('status'=>'exist','reference_no'=>$ckrow['reference_no'],'book_amount'=>$ckrow['book_amount'],'transaction_date'=>$ckrow['transaction_date'],'transactionno'=>$ckrow['transactionno']);
		}
	}
	echo json_encode($chkresult);
}else if($action=="newclientsavetodb"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$client_name =  $_POST['client_name'];
	$invoicing_address =  $_POST['invoicing_address'];
	$phone =  $_POST['phone'];
	$email =  $_POST['email'];
	$vat =  $_POST['vat'];
	$comp_web =  $_POST['comp_web'];
	$name_forPO =  $_POST['name_forPO'];
	$email_forPO =  $_POST['email_forPO'];
	$contact_forPO =  $_POST['contact_forPO'];
	$client_bankname =  $_POST['client_bankname'];
	$client_accountno =  $_POST['client_accountno'];
	$client_bankifsc =  $_POST['client_bankifsc'];
	$client_bankiban =  $_POST['client_bankiban'];
	$client_bankswift =  $_POST['client_bankswift'];
	$client_banksortcode =  $_POST['client_banksortcode'];

	$conqry=" companyid ='".$companyid."' and subcompanyid ='".$subcompanyid."' and projectid = '".$projectid."' and ifnull(tedit,'')=''" ; 
	if(chkfornamereadyexist("client_master", "client_name", $client_name, $conn, $conqry)){
		$rrstat[]=array('status'=>'Duplicate', 'message'=>'Your intermediate name already exists ' .$client_name);
		echo json_encode($rrstat);	
		exit();
	}	
	//$getid = "select count(sno) as client_id from client_master";	
	$getid = "SELECT IFNULL(MAX(CAST(SUBSTRING(client_id, 2) AS UNSIGNED)), 0) + 1 AS client_id  FROM client_master WHERE ifnull(client_id ,'') != ''";
	$resq = $conn->query($getid);
	$resq->num_rows;
	if($resq->num_rows>0)
	{
		$row = $resq->fetch_assoc();
		//$client_id = ($row['client_id']+1);
		$client_id = ($row['client_id']);
		if(strlen($client_id)=="0" || strlen($client_id)=="1"){
			$clientid = 'C0000'.$client_id;
		}else if(strlen($client_id)=="2"){
			$clientid = 'C000'.$client_id;
		}else if(strlen($client_id)=="3"){
			$clientid = 'C00'.$client_id;
		}else if(strlen($client_id)=="4"){
			$clientid = 'C0'.$client_id;
		}else if(strlen($client_id)=="5"){
			$clientid = 'C'.$client_id;
		}
	}else{
		$clientid = 'C00001'; 
	}
	$insert = "INSERT into client_master (companyid,subcompanyid,projectid,client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website, name_forpo, contact_forpo, email_forpo, bankname, accountno, ifsccode, account_name, iban, swift, sort_code) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$clientid."','".$client_name."','".addslashes($invoicing_address)."','".addslashes($phone)."','".addslashes($email)."','".addslashes($vat)."','".date('Y-m-d H:i:s')."','".addslashes($comp_web)."','".addslashes($name_forPO)."','".addslashes($contact_forPO)."','".addslashes($email_forPO)."','".addslashes($client_bankname)."','".addslashes($client_accountno)."', '".addslashes($client_bankifsc)."', '".addslashes($client_name)."', '".addslashes($client_bankiban)."', '".addslashes($client_bankswift)."', '".addslashes($client_banksortcode)."')";
	$result = $conn->query($insert) or die($conn->error);
	if($result){
		$rrstat[]=array('status'=>'Success', 'message'=>'Client registration completed successfully.');
	}else{
		$rrstat[]=array('status'=>'Error', 'message'=>'Client registration did not save, due to technical issue');
	}
	echo json_encode($rrstat);	
}
else if($action=="newinvoicetypesavetodb"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$invoicetype_name =  $_POST['invoicetype_name'];
	
	$conqry=" companyid ='".$companyid."' and subcompanyid ='".$subcompanyid."' and projectid = '".$projectid."' and ifnull(tedit,'')=''" ; 
	if(chkfornamereadyexist("tbl_saleinvoicetypemaster", "invoicetype", $invoicetype_name, $conn, $conqry)){
		$rrstat[]=array('status'=>'Duplicate', 'message'=>'Your intermediate name already exists ' .$invoicetype_name);
		echo json_encode($rrstat);	
		exit();
	}	
	
	$getid = "SELECT MAX(id) + 1 AS id FROM tbl_saleinvoicetypemaster ";
	$resq = $conn->query($getid);
	$resq->num_rows;
	if($resq->num_rows>0)
	{
		$row = $resq->fetch_assoc();
		
		$id = $row['id'];
		
		$insert = "INSERT into tbl_saleinvoicetypemaster (companyid,subcompanyid,projectid,id, invoicetype) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$id."','".$invoicetype_name."')";
		$result = $conn->query($insert) or die($conn->error);
		if($result){
			$rrstat[]=array('status'=>'Success', 'message'=>'Your invoice type has been saved successfully.');
		}else{
			$rrstat[]=array('status'=>'Error', 'message'=>'Your invoice type registration did not save, due to technical issue');
		}
		echo json_encode($rrstat);	
	}
}
else if($action=="check_uploadexpensesheet_format"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$sqq=$conn->query("SELECT sno, companyid, subcompanyid, projectid, colp_accountno, colp_date, colp_name, colp_amount, colp_chequeno, colp_description, colp_purpose, colp_purposecode, colp_hrow from tbl_expensesheet_import_keys where (tedit is null or tedit='') and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
	$expsheetformat=array();
	if($sqq->num_rows>0){
		while($exrow=$sqq->fetch_assoc()){
			$expsheetformat[]=array('sno'=>$exrow['sno'], 'companyid'=>$exrow['companyid'], 'subcompanyid'=>$exrow['subcompanyid'], 'projectid'=>$exrow['projectid'], 'colp_accountno'=>$exrow['colp_accountno'], 'colp_date'=>$exrow['colp_date'], 'colp_name'=>$exrow['colp_name'], 'colp_amount'=>$exrow['colp_amount'], 'colp_chequeno'=>$exrow['colp_chequeno'], 'colp_description'=>$exrow['colp_description'], 'colp_purpose'=>$exrow['colp_purpose'], 'colp_purposecode'=>$exrow['colp_purposecode'], 'colp_hrow'=>$exrow['colp_hrow']);
		}
	}
	echo json_encode($expsheetformat);
}else if($action=="loginuserdetails_callback"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$lginfo=loginuserdetailswithauthproject($authconn,$companyid,$subcompanyid,$projectid,"","allwithprojectid","");
	echo json_encode($lginfo);
}else if($action=="newIntermediatesavetodb"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$client_name =  ($_POST['client_name']);
	$invoicing_address =  $_POST['invoicing_address'];
	$phone =  $_POST['phone'];
	$email =  $_POST['email'];
	$vat =  $_POST['vat'];
	$comp_web =  $_POST['comp_web']; 
	$client_bankname =  $_POST['client_bankname'];
	$client_accountno =  $_POST['client_accountno'];
	$client_bankifsc =  $_POST['client_bankifsc'];
	$client_bankiban =  $_POST['client_bankiban'];
	$client_bankswift =  $_POST['client_bankswift'];
	$client_banksortcode =  $_POST['client_banksortcode'];
	$conqry=" companyid ='".$companyid."' and subcompanyid ='".$subcompanyid."' and projectid = '".$projectid."' and ifnull(tedit,'')=''" ; 

	if(chkfornamereadyexist("intermediatemaster", "client_name", $client_name, $conn, $conqry)){
		$rrstat[]=array('status'=>'Duplicate', 'message'=>'Your intermediate name already exists ' .$client_name);
		echo json_encode($rrstat);	
		exit();
	}
	 
	$getid = "SELECT IFNULL(MAX(CAST(SUBSTRING(client_id, 2) AS UNSIGNED)), 0) + 1 AS client_id  FROM intermediatemaster WHERE ifnull(client_id ,'') != ''";
	$resq = $conn->query($getid);
	$resq->num_rows;
	if($resq->num_rows>0)
	{
		$row = $resq->fetch_assoc();
		$client_id = ($row['client_id']);
		if(strlen($client_id)=="0" || strlen($client_id)=="1"){
			$clientid = 'I0000'.$client_id;
		}else if(strlen($client_id)=="2"){
			$clientid = 'I000'.$client_id;
		}else if(strlen($client_id)=="3"){
			$clientid = 'I00'.$client_id;
		}else if(strlen($client_id)=="4"){
			$clientid = 'I0'.$client_id;
		}else if(strlen($client_id)=="5"){
			$clientid = 'I'.$client_id;
		}
	}else{
		$clientid = 'I00001'; 
	}
	$insert = "INSERT into intermediatemaster (companyid,subcompanyid,projectid,client_id, client_name, invoicing_address, phones, client_email, vat, date_time, comp_website,  bankname, accountno, ifsccode, account_name, iban, swift, sort_code) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$clientid."','".addslashes($client_name)."','".addslashes($invoicing_address)."','".addslashes($phone)."','".addslashes($email)."','".addslashes($vat)."','".date('Y-m-d H:i:s')."','".addslashes($comp_web)."', '".addslashes($client_bankname)."','".addslashes($client_accountno)."', '".addslashes($client_bankifsc)."', '".addslashes($client_name)."', '".addslashes($client_bankiban)."', '".addslashes($client_bankswift)."', '".addslashes($client_banksortcode)."')";
	$result = $conn->query($insert) or die($conn->error);
	if($result){
		$rrstat[]=array('status'=>'Success', 'message'=>'Your Intermediate registration save successfully');
	}else{
		$rrstat[]=array('status'=>'Error', 'message'=>'Your client Intermediate did not save, due to technical issue');
	}
	echo json_encode($rrstat);	
}else if($action=="Intermediatemasterupdate"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$client_name =  ($_POST['client_name']);
	$invoicing_address =  $_POST['invoicing_address'];
	$phone =  $_POST['phone'];
	$email =  $_POST['email'];
	$vat =  $_POST['vat'];
	$comp_web =  $_POST['comp_web']; 
	$client_bankname =  $_POST['client_bankname'];
	$client_accountno =  $_POST['client_accountno'];
	$client_bankifsc =  $_POST['client_bankifsc'];
	$client_bankiban =  $_POST['client_bankiban'];
	$client_bankswift =  $_POST['client_bankswift'];
	$client_banksortcode =  $_POST['client_banksortcode'];
	$client_id =  $_POST['clientid'];
	$conqry=" companyid ='".$companyid."' and subcompanyid ='".$subcompanyid."' and projectid = '".$projectid."' and ifnull(tedit,'')='' and client_id<>'".$client_id."'" ; 
	if(chkfornamereadyexist("intermediatemaster", "client_name", $client_name, $conn, $conqry)){
		$rrstat[]=array('status'=>'Duplicate', 'message'=>'Your intermediate name already exists ' .$client_name);
		echo json_encode($rrstat);	
		exit();
	}	 
	$update = "UPDATE intermediatemaster  SET 
	    client_name = '".addslashes($client_name)."',
	    invoicing_address = '".addslashes($invoicing_address)."',
	    phones = '".addslashes($phone)."',
	    client_email = '".addslashes($email)."',
	    vat = '".addslashes($vat)."',
	    date_time = '".date('Y-m-d H:i:s')."',
	    comp_website = '".addslashes($comp_web)."',
	    bankname = '".addslashes($client_bankname)."',
	    accountno = '".addslashes($client_accountno)."',
	    ifsccode = '".addslashes($client_bankifsc)."',
	    account_name = '".addslashes($client_name)."',
	    iban = '".addslashes($client_bankiban)."',
	    swift = '".addslashes($client_bankswift)."',
	    sort_code = '".addslashes($client_banksortcode)."'
		WHERE  companyid = '".$companyid."' and  subcompanyid = '".$subcompanyid."' and  projectid = '".$projectid."' and  client_id = '".$client_id."' "; 
	$result = $conn->query($update) or die($conn->error);
	if($result){
		$rrstat[]=array('status'=>'Update Success', 'message'=>'Your Intermediate registration update save successfully');
	}else{
		$rrstat[]=array('status'=>'Error', 'message'=>'Your client Intermediate did not save, due to technical issue');
	}
	echo json_encode($rrstat);	
}else if($action=="clientmasterupdate"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$client_name =  $_POST['client_name'];
	$invoicing_address =  $_POST['invoicing_address'];
	$phone =  $_POST['phone'];
	$email =  $_POST['email'];
	$vat =  $_POST['vat'];
	$comp_web =  $_POST['comp_web'];
	$name_forPO =  $_POST['name_forPO'];
	$email_forPO =  $_POST['email_forPO'];
	$contact_forPO =  $_POST['contact_forPO'];
	$client_bankname =  $_POST['client_bankname'];
	$client_accountno =  $_POST['client_accountno'];
	$client_bankifsc =  $_POST['client_bankifsc'];
	$client_bankiban =  $_POST['client_bankiban'];
	$client_bankswift =  $_POST['client_bankswift'];
	$client_banksortcode =  $_POST['client_banksortcode'];
	$client_id =  $_POST['clientid'];
	$conqry=" companyid ='".$companyid."' and subcompanyid ='".$subcompanyid."' and projectid = '".$projectid."' and ifnull(tedit,'')='' and client_id<>'".$client_id."'" ; 
 
	if(chkfornamereadyexist("client_master", "client_name", $client_name, $conn, $conqry)){
		$rrstat[]=array('status'=>'Duplicate', 'message'=>'Your intermediate name already exists ' .$client_name);
		echo json_encode($rrstat);	
		exit();
	}	
	 
	 $update = "UPDATE client_master  SET 
	    client_name = '".addslashes($client_name)."',
	    invoicing_address = '".addslashes($invoicing_address)."',
	    phones = '".addslashes($phone)."',
	    client_email = '".addslashes($email)."',
	    vat = '".addslashes($vat)."',
	    date_time = '".date('Y-m-d H:i:s')."',
	    comp_website = '".addslashes($comp_web)."',
	    name_forpo = '".addslashes($name_forPO)."',
	    contact_forpo = '".addslashes($contact_forPO)."',
	    email_forpo = '".addslashes($email_forPO)."',
	    bankname = '".addslashes($client_bankname)."',
	    accountno = '".addslashes($client_accountno)."',
	    ifsccode = '".addslashes($client_bankifsc)."',
	    account_name = '".addslashes($client_name)."',
	    iban = '".addslashes($client_bankiban)."',
	    swift = '".addslashes($client_bankswift)."',
	    sort_code = '".addslashes($client_banksortcode)."'
		WHERE 
    	client_id = '".$client_id."' and 
	    companyid = '".$companyid."' and
	    subcompanyid = '".$subcompanyid."' and
	    projectid = '".$projectid."' ";

	$result = $conn->query($update) or die($conn->error);
	if($result){
		$rrstat[]=array('status'=>'Update Success', 'message'=>'Your client details are Updated successfully');
	}else{
		$rrstat[]=array('status'=>'Error', 'message'=>'Your client registration did not save, due to technical issue');
	}
	echo json_encode($rrstat);	
}else if($action=="invoicetypemasterupdate"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$invoicetype_name =  $_POST['invoicetype_name'];
	
	$id =  $_POST['id'];
	$conqry=" companyid ='".$companyid."' and subcompanyid ='".$subcompanyid."' and projectid = '".$projectid."' and ifnull(tedit,'')='' and id<>'".$id."'" ; 
 
	if(chkfornamereadyexist("tbl_saleinvoicetypemaster", "invoicetype", $invoicetype_name, $conn, $conqry)){
		$rrstat[]=array('status'=>'Duplicate', 'message'=>'Your invoice typealready exists ' .$invoicetype_name);
		echo json_encode($rrstat);	
		exit();
	}	
	 
	 $update = "UPDATE tbl_saleinvoicetypemaster  SET 
	    invoicetype = '".addslashes($invoicetype_name)."'
		WHERE 
    	id = '".$id."' and 
	    companyid = '".$companyid."' and
	    subcompanyid = '".$subcompanyid."' and
	    projectid = '".$projectid."' ";

	$result = $conn->query($update) or die($conn->error);
	if($result){
		$rrstat[]=array('status'=>'Update Success', 'message'=>'Your invoice type Update save successfully');
	}else{
		$rrstat[]=array('status'=>'Error', 'message'=>'Your invoice type registration did not save, due to technical issue');
	}
	echo json_encode($rrstat);	
}
else if($action=="loanauthorized"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$imprest=loanauthorizeddetails($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($imprest);
}else if($action=="partialpaymentauthorized"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$imprest=getpartialpaymentpayto($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($imprest);
}else if($action=="supplierauthorizedlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$supplierlist=getsupplierpaymentpayto($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($supplierlist);
}else if($action=="cashpaymentauthorized"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$imprest=getcashpaymentpayto($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($imprest);
}else if($action=="checkimprestauthorized"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$imprest=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($imprest);
}else if($action=="checkexist_payto"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$imptlist=getpaytoinfo($conn,$companyid,$subcompanyid,"likelypayto",$paytoname);
	// print_r($imptlist)
	$imppthtml="";
	if(count($imptlist)>0){
		$paytohtml.="<p class='text-primary'>Already exist in payto master</p>";
		foreach ($imptlist as $imkey) {
			$paytohtml.="<label class='text-danger'><input type='radio' name='paytoradio' class='paytoradio' data-expaytoid='".$imkey['paytoid']."' data-expaytoname='".$imkey['payto']."' data-extobankname='".$imkey['tobankname']."' data-extoaccountno='".$imkey['toaccountno']."' data-extobankifsc='".$imkey['tobankifsc']."' data-extoiban='".$imkey['iban']."' data-extoswift='".$imkey['swift']."' data-extosortcode='".$imkey['sortcode']."' data-extomobileno='".$imkey['mobileno']."' data-extoemailaddress='".$imkey['emailaddress']."' /> &nbsp;&nbsp;&nbsp;".$imkey['payto']."</span></label></br/>";
		}
	}
	echo json_encode($paytohtml);
}else if($action=="checkexist_imprestpayto"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$imprestpaytoname = $_POST['imprestpaytoname'];
	$imptlist=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"paytolike",$imprestpaytoname);
	$imppthtml="";
	if(count($imptlist)>0){
		$imppthtml.="<p class='text-primary'>Already exist in imprest payto master</p>";
		foreach ($imptlist as $imkey) {
			$ppna=projectdetails($conn,$companyid,$subcompanyid,"projectid",$imkey['projectid']);
			$projectname="";
			foreach ($ppna as $ppnakey) {
				$projectname=$ppnakey['projectname'];
			}
			if($projectid===$imkey['projectid']){
				$imppthtml.="<label class='text-danger'><input type='radio' name='imprestpayradio' value='".$imkey['imprestpaytoid']."' data-imprestpaytoname='".$imkey['imprestpayto']."' data-authprojectid='".$imkey['projectid']."' data-authprojectname='".$projectname."' class='imprestpayradio' disabled /> &nbsp;&nbsp;&nbsp;".$imkey['imprestpayto']." &nbsp;&nbsp;<span class='text-success'>[Authorized for project : ".$projectname."]</span></label></br/>";
			}else{
				$imppthtml.="<label class='text-danger'><input type='radio' name='imprestpayradio' value='".$imkey['imprestpaytoid']."' data-imprestpaytoname='".$imkey['imprestpayto']."' data-authprojectid='".$imkey['projectid']."'  data-authprojectname='".$projectname."' class='imprestpayradio' /> &nbsp;&nbsp;&nbsp;".$imkey['imprestpayto']." &nbsp;&nbsp;<span class='text-success'>[Authorized for project : ".$projectname."]</span></label></br/>";
			}
		}
	}
	echo json_encode($imppthtml);
}else if($action=="addupdateprojectaccuntdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountname = $_POST['accountname'];
	$accountno = $_POST['bannkaccountno'];
	$ifsccode = $_POST['ifsccode'];
	$sbanno = $_POST['bannkaccountsban'];
	$swiftcode = $_POST['bannkaccountswift'];
	$sortcode = $_POST['bannkaccountsortcode'];
	$bannkname = $_POST['bannkname'];
	$bannkbranchname = $_POST['bannkbranchname'];
	$bannkbranchaddress = $_POST['bannkbranchaddress'];
	$banktype = $_POST['banktype'];
	$savebyname = $_POST['savebyname'];
	$readytoremove = $_POST['readytoremove'];
	$dbsno = $_POST['dbsno'];
	$loginname = $_POST['loginname'];
	$newiban="";
	$newswift="";
	$newsortcode="";
	$newmobileno="";
	$newemailaddress="";
	$rrst=array();
	if($projectid!="" && $accountno!=""){
		$accountname = $accountname.'-'.substr($accountno, 0, 5);
		$accin=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"checkexistwithaccountno",$accountno);
		if(count($accin)>0 && $readytoremove=="no"){
			$rrst[]=array('status'=>'Error', 'message'=>'Entered Bank accountno already exist in account master list. Please Re-check and Enter the correct account no.');
			/*foreach ($accin as $accinkey) {
				$conn->query("UPDATE tbl_master_account SET accountname='".$accountname."', accountno='".$accountno."',ifsccode='".$ifsccode."',bankname='".$bannkname."',branchname='".$bannkbranchname."',branchaddress='".$bannkbranchaddress."', account_type='".$banktype."', uploadflag=null where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and sno='".$accinkey['sno']."'");
				if(mysqli_affected_rows($conn)>0){
					updatepayto_details($conn,$companyid,$subcompanyid,$projectid,$accountname,$accountno,$bannkname,$ifsccode,$newiban,$newswift,$newsortcode,$newmobileno,$newemailaddress,$accinkey['accountid'],$loginname);
					$rrst[]=array('status'=>'Success', 'message'=>'Bank details update in account master of bank');
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>'Bank details did not update, due to technical issue');
				}
			}*/
		}else if(count($accin)>0 && $readytoremove=="yes"){
			$conn->query("UPDATE tbl_master_account SET tedit='removed',uploadflag=null where sno='".$dbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst[]=array('status'=>'Success', 'message'=>'Remove Bank details from account master of bank');
			}else{
				$rrst[]=array('status'=>'Error', 'message'=>'Bank details did not remove, due to technical issue');
			}
		}else{
			if(strtolower($banktype)=="imprest"){
				$authirzedprojects[]=array('companyid'=>$companyid,'subcompanyid'=>$subcompanyid,'projectid'=>$projectid);
				$rrst=saveimprestauthorization($conn,$companyid,$subcompanyid,$projectid,$accountname,$bannkname,$accountno,$ifsccode,$authirzedprojects,$savebyname);
			}else{
				// $chkaccin=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountno",$accountno);
				// if(count($chkaccin)>0){
					$paytoname = $accountname;
					$ppt=savetodbnewpayto_details($conn,$paytoname,$accountno,$bannkname,$ifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$newmobileno,$newemailaddress,$loginname);
					if(count($ppt)>0){
						foreach ($ppt as $pptkey) {
							$paytoid = $pptkey['paytoid'];
						}
					}
					if($paytoid){
						$maxsccid=getmaxaccountid_frmaccountmaster($conn);
						$bankid=getmaxbankidfrommaster($conn,$bannkname);
						if($maxsccid!="" && $bankid!=""){
							$conn->query("Insert into tbl_master_account(cmp_id,subcmp_id,projectid,paytoid,accountid,bankid,account_type,accountname,accountno,ifsccode,bankname,branchname,branchaddress) values('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".$maxsccid."','".$bankid."','".$banktype."','".$accountname."','".$accountno."','".$ifsccode."','".$bannkname."','".$bannkbranchname."','".$bannkbranchaddress."')");
							if(mysqli_affected_rows($conn)>0){
								$rrst[]=array('status'=>'Success', 'message'=>'Bank details update in account master of bank');
							}else{
								$rrst[]=array('status'=>'Error', 'message'=>'Bank details did not update, due to technical issue');
							}
						}
					}else{
						$rrst[]=array('status'=>'Error', 'message'=>'Id genration failed');
					}
				// }else{
				// 	$rrst[]=array('status'=>'Error', 'message'=>'Entered Bank accountno already exist in account master list. Please Re-check and Enter the correct account no.');
				// }
			}
		}
	}else{
		$rrst[]=array('status'=>'Error', 'message'=>'Bank account no missing');
	}
	echo json_encode($rrst);
}else if($action=="savetodbprojectFDaccountdetails_tomaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountname = $_POST['accountname'];
	$bannkname = $_POST['bannkname']??'';
	$accountno = $_POST['bannkaccountno']??'';
	$ifsccode = $_POST['ifsccode']??'';
	$banktype = $_POST['banktype'];
	$dbsno = $_POST['dbsno']??'';
	$loginname = $_POST['loginname'];
	$newiban="";
	$newswift="";
	$newsortcode="";
	$newmobileno="";
	$newemailaddress="";
	$rrst=array();
	if($projectid!="" && $accountno!=""){
		$accin=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountno",$accountno);
		if(count($accin)>0){
			foreach ($accin as $accinkey) {
				$conn->query("UPDATE tbl_master_account SET accountname='".$accountname."', accountno='".$accountno."',ifsccode='".$ifsccode."',bankname='".$bannkname."',branchname='".$bannkbranchname."',branchaddress='".$bannkbranchaddress."', account_type='".$banktype."', uploadflag=null where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and sno='".$accinkey['sno']."'");
				if(mysqli_affected_rows($conn)>0){
					updatepayto_details($conn,$companyid,$subcompanyid,$projectid,$accountname,$accountno,$bannkname,$ifsccode,$newiban,$newswift,$newsortcode,$newmobileno,$newemailaddress,$accinkey['accountid'],$loginname);
					$rrst[]=array('status'=>'Success', 'message'=>'Bank details update in account master of bank');
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>'Bank details did not update, due to technical issue');
				}
			}
		}else{
			$paytoname = $accountname.'-'.$bannkname.'('.substr($accountno, 0, 5).')';
			$ppt=savetodbnewpayto_details($conn,$paytoname,$accountno,$bannkname,$ifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$newmobileno,$newemailaddress,$loginname);
			if(count($ppt)>0){
				foreach ($ppt as $pptkey) {
					$paytoid = $pptkey['paytoid'];
				}
			}
			if($paytoid){
				$maxsccid=getmaxaccountid_frmaccountmaster($conn);
				$bankid=getmaxbankidfrommaster($conn,$bannkname);
				if($maxsccid!="" && $bankid!=""){
					$conn->query("Insert into tbl_master_account(cmp_id,subcmp_id,projectid,paytoid,accountid,bankid,account_type,accountname,accountno,ifsccode,bankname,branchname,branchaddress) values('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".$maxsccid."','".$bankid."','".$banktype."','".$accountname."','".$accountno."','".$ifsccode."','".$bannkname."','".$bannkbranchname."','".$bannkbranchaddress."')");
					if(mysqli_affected_rows($conn)>0){
						$rrst[]=array('status'=>'Success', 'message'=>'Bank details update in account master of bank');
					}else{
						$rrst[]=array('status'=>'Error', 'message'=>'Bank details did not update, due to technical issue');
					}
				}
			}else{
				$rrst[]=array('status'=>'Error', 'message'=>'Id genration failed');
			}
			
		}
	}else{
			$paytoname = $accountname.'-'.$bannkname.'('.substr($accountno, 0, 5).')';
			$ppt=savetodbnewpayto_details($conn,$paytoname,$accountno,$bannkname,$ifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$newmobileno,$newemailaddress,$loginname);
			if(count($ppt)>0){
				foreach ($ppt as $pptkey) {
					$paytoid = $pptkey['paytoid'];
				}
			}
			if($paytoid){
				$maxsccid=getmaxaccountid_frmaccountmaster($conn);
				$bankid=getmaxbankidfrommaster($conn,$bannkname);
				if($maxsccid!="" && $bankid!=""){
					$conn->query("Insert into tbl_master_account(cmp_id,subcmp_id,projectid,paytoid,accountid,bankid,account_type,accountname,accountno,ifsccode,bankname,branchname,branchaddress) values('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".$maxsccid."','".$bankid."','".$banktype."','".$accountname."','".$accountno."','".$ifsccode."','".$bannkname."','".$bannkbranchname."','".$bannkbranchaddress."')");
					if(mysqli_affected_rows($conn)>0){
						$rrst[]=array('status'=>'Success', 'message'=>'Bank details update in account master of bank');
					}else{
						$rrst[]=array('status'=>'Error', 'message'=>'Bank details did not update, due to technical issue');
					}
				}
			}else{
				$rrst[]=array('status'=>'Error', 'message'=>'Id genration failed');
			}
	}
	echo json_encode($rrst);
}else if($action=="supplierauthorization"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$authpaytoid = $_POST['authpaytoid'];
	$bankname = $_POST['bankname'];
	$accountnumber = $_POST['accountnumber'];
	$bankifsccode = $_POST['bankifsccode'];
	$panno = $_POST['panno'];
	$gstno = $_POST['gstno'];
	$addedby = $_POST['addedby'];
	$paytomobileno = $_POST['paytomobileno'];
	$paytoemailaddress = $_POST['paytoemailaddress'];
	$surethisnew="";
	if(isset($_POST['surethisnew'])){
		$surethisnew=$_POST['surethisnew'];
	}
	$restimp= savesupplierpaymentauthorization($conn,$companyid,$subcompanyid,$projectid,$paytoname,$bankname,$accountnumber,$bankifsccode,$panno,$gstno,$paytomobileno,$paytoemailaddress,$addedby,$authpaytoid);
	echo json_encode($restimp);
}else if($action=="cashpaymentauthorization"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$authpaytoid = $_POST['authpaytoid'];
	$bankname = isset($_POST['bankname']) && $_POST['bankname'] !== ''  ? $_POST['bankname'] : 'Cash Bank';
	$accountnumber = isset($_POST['accountnumber']) && $_POST['accountnumber'] !== ''  ? $_POST['accountnumber'] : random_int(100000000, 999999999);
	$bankifsccode = $_POST['bankifsccode'] ?? '';
	$addedby = $_POST['addedby'];
	$paytomobileno = $_POST['paytomobileno'] ?? '';
	$paytoemailaddress = $_POST['paytoemailaddress'] ?? '';
	$surethisnew="";
	if(isset($_POST['surethisnew'])){
		$surethisnew=$_POST['surethisnew'];
	}
	
	$restimp= savecashauthorization($conn,$companyid,$subcompanyid,$projectid,$paytoname,$bankname,$accountnumber,$bankifsccode,$paytomobileno,$paytoemailaddress,$addedby,$authpaytoid);
	if($restimp['status']=="Success" || $restimp['status']=="Alreadyexist"){
		if($paytomobileno){
			$chklg=loginuserdetailsfrommaster($authconn,"","mobileno",$paytomobileno);
			if(count($chklg)>0){
				foreach ($chklg as $chklgkey) {
					$loginid = $chklgkey['loginid'];
				}
				$conn->query("UPDATE cash_authorizedperson SET loginid='".$loginid."' WHERE paytoid='".$restimp['paytoid']."'");
			}else{
				//update user login basic details
				$conn->query("INSERT INTO login_user_master(loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive,departmentid) VALUES('".$paytoname."', '".$paytomobileno."', '".$paytomobileno."', '4', 'User', '".$paytomobileno."', '".$paytoemailaddress."', 'yes', '')");
				$authprojectid="";
				if(mysqli_affected_rows($conn)>0){
					$conn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$paytomobileno."', 'active')");
					if(mysqli_affected_rows($conn)>0){
						$conn->query("UPDATE cash_authorizedperson SET loginid='".$paytomobileno."' WHERE paytoid='".$restimp['paytoid']."'");
					}
				}
			}
		}
	}
	
	echo json_encode($restimp);
}else if($action=="imprestauthorizationwithproject"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$bankname = $_POST['bankname'];
	$accountnumber = $_POST['accountnumber'];
	$bankifsccode = $_POST['bankifsccode'];
	$loginid = $_POST['loginid'];
	$paytomobileno = $_POST['paytomobileno'];
	$paytoemailaddress = $_POST['paytoemailaddress'];
	$authirzedprojects = json_decode($_POST['authorizedproject'],TRUE);
	$surethisnew="";
	if(isset($_POST['surethisnew'])){
		$surethisnew=$_POST['surethisnew'];
	}
	// $paytoid = generateUniquePaytoId($conn, $companyid, $subcompanyid, $paytoname);
	$restimp= saveimprestauthorization($conn,$companyid,$subcompanyid,$projectid,$paytoname,$bankname,$accountnumber,$bankifsccode,$authirzedprojects,$paytomobileno,$paytoemailaddress,$loginid);
	if($restimp['status']=="Success" || $restimp['status']=="Alreadyexist"){
		$chklg=loginuserdetailsfrommaster($authconn,"","loginid",$paytomobileno);
		if(count($chklg)>0){
			$conn->query("UPDATE imprest_authorizedperson SET loginid='".$paytomobileno."' WHERE imprestpaytoid='".$restimp['paytoid']."'");
		}else{
			//update user login basic details
			$conn->query("INSERT INTO login_user_master(loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive,departmentid) VALUES('".$paytoname."', '".$paytomobileno."', '".$paytomobileno."', '4', 'User', '".$paytomobileno."', '".$paytoemailaddress."', 'yes', '')");
			$authprojectid="";
			if(mysqli_affected_rows($conn)>0){
				$conn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$paytomobileno."', 'active')");
				if(mysqli_affected_rows($conn)>0){
					$conn->query("UPDATE imprest_authorizedperson SET loginid='".$paytomobileno."' WHERE imprestpaytoid='".$restimp['paytoid']."'");
				}
			}
		}
	}
	
	echo json_encode($restimp);
}else if($action=="addupdateloanaccount"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loantype = $_POST['loantype'];
	$paytoid = $_POST['authpaytoid'];
	$accountname = $_POST['paytoname'];
	$bankname = $_POST['bankname'];
	$accountno = $_POST['accountnumber'];
	$ifsccode = $_POST['bankifsccode'];
	$mobileno = $_POST['paytomobileno'];
	$emailaddress = $_POST['paytoemailaddress'];
	$banktype = "Loan";
	$loginname = $_POST['loginname'];
	$newiban="";
	$newswift="";
	$newsortcode="";
	$newmobileno="";
	$rrst=array();
	if($accountname!="" && $accountno!=""){
		$accin=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountnowithloanaccount",$accountno);
		if(count($accin)>0){
			foreach ($accin as $accinkey) {
				$conn->query("UPDATE tbl_master_account SET accountname='".$accountname."', accountno='".$accountno."',ifsccode='".$ifsccode."',bankname='".$bankname."',branchname='".$bannkbranchname."',branchaddress='".$bannkbranchaddress."', paytoid='".$paytoid."', uploadflag=null where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and sno='".$accinkey['sno']."'");
				if(mysqli_affected_rows($conn)>0){
					updatepayto_details($conn,$companyid,$subcompanyid,$projectid,$accountname,$accountno,$bankname,$ifsccode,$newiban,$newswift,$newsortcode,$mobileno,$emailaddress,$accinkey['accountid'],$loginname);

					$remark = "loan Authorized by ".$loginname;
					$purposecode="";
					addOrUpdateLoanAuthorizationDetails($conn, $companyid, $subcompanyid, $projectid, $paytoid, $accinkey['accountid'], $loantype, $accountname, $mobileno, $emailaddress, $bankname, $accountno, $ifsccode, $purposecode, $remark, $loginname);

					$rrst=array('status'=>'Success', 'message'=>'Bank details update in account master of bank');
				}else{
					$rrst=array('status'=>'Error', 'message'=>'Bank details did not update, due to technical issue or already updated all details');
				}
			}
		}else if(count($accin)>0 && $paytoid!="" && $readytoremove=="yes"){
			$conn->query("UPDATE tbl_master_account SET tedit='removed',uploadflag=null where sno='".$dbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst=array('status'=>'Success', 'message'=>'Remove Bank details from account master of bank');
			}else{
				$rrst=array('status'=>'Error', 'message'=>'Bank details did not remove, due to technical issue');
			}
		}else{
			if($paytoid){
				$paytoname = $accountname;
			}else{
				$paytoname = $accountname;
				$ppt=savetodbnewpayto_details($conn,$paytoname,$accountno,$bankname,$ifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$mobileno,$emailaddress,$loginname);
				if(count($ppt)>0){
					foreach ($ppt as $pptkey) {
						$paytoid = $pptkey['paytoid'];
					}
				}
			}				
			if($paytoid){
				$maxsccid=getmaxaccountid_frmaccountmaster($conn);
				$bankid=getmaxbankidfrommaster($conn,$bankname);
				if($maxsccid!="" && $bankid!=""){
					$conn->query("Insert into tbl_master_account(cmp_id,subcmp_id,projectid,paytoid,accountid,bankid,account_type,accountname,accountno,ifsccode,bankname,branchname,branchaddress) values('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".$maxsccid."','".$bankid."','".$banktype."','".$accountname."','".$accountno."','".$ifsccode."','".$bankname."','".$bannkbranchname."','".$bannkbranchaddress."')");
					if(mysqli_affected_rows($conn)>0){
						$remark = "loan Authorized by ".$loginname;
						$purposecode="";
						addOrUpdateLoanAuthorizationDetails($conn, $companyid, $subcompanyid, $projectid, $paytoid, $maxsccid, $loantype, $accountname, $mobileno, $emailaddress, $bankname, $accountno, $ifsccode, $purposecode, $remark, $loginname);
						$rrst=array('status'=>'Success', 'message'=>'New loan account save successfully');
					}else{
						$rrst=array('status'=>'Error', 'message'=>'Bank details did not save, due to technical issue');
					}
				}
			}else{
				$rrst=array('status'=>'Error', 'message'=>'Id genration failed');
			}
			
		}
	}else{
		$rrst=array('status'=>'Error', 'message'=>'Bank account no missing');
	}
	echo json_encode($rrst);
}else if($action=="creditcardlistdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$ccm=creditcardmaster($conn,$companyid,$subcompanyid,$projectid,"allwithprojectid","");
	// print_r($ccm);
	$ccmarray=array();
	if(count($ccm)>0){
		foreach ($ccm as $ccmdkey) {
			$ccmarray[]=array('sno'=>$ccmdkey['sno'], 'bankname'=>$ccmdkey['bankname'], 'accountno'=>$ccmdkey['accountno'], 'ifsccode'=>$ccmdkey['ifsccode'], 'accountid'=>$ccmdkey['accountid'], 'ccid'=>$ccmdkey['ccid'], 'cardtype'=>$ccmdkey['cardtype'], 'cardholdername'=>$ccmdkey['cardholdername'], 'cardnumber'=>$ccmdkey['cardnumber'], 'expirymonth'=>$ccmdkey['expirymonth'], 'expiryyear'=>$ccmdkey['expiryyear'], 'cvv'=>$ccmdkey['cvv'], 'billing_address'=>$ccmdkey['billing_address']);
		}
	}
	echo json_encode($ccmarray);
}else if($action=="creditcarddetailsfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue=$_POST['shortbyvalue'];
	$ccm=creditcardmaster($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	// print_r($ccm);
	$ccmarray=array();
	if(count($ccm)>0){
		foreach ($ccm as $ccmdkey) {
			$authccd=authorizedcreditcardperson($conn,$companyid,$subcompanyid,$projectid,"ccid",$ccmdkey['ccid']);
			$paytoid="";
			$paytoname="";
			$paytonamebtn = '<span class="badge bg-warning me-2 updateauthccd" style="cursor:pointer;" onclick="openModalForAuthorizationCCD(\'hidden_routurl\', \'' . $paytoid . '\', \'' . $ccmdkey['ccid'] . '\', \'' . $ccmdkey['cardnumber'] . '\', \'' . $ccmdkey['cardtype'] . '\');">Missing Authorization</span>';
			if(count($authccd)>0){
				foreach ($authccd as $authccdkey) {
					$paytoid = $authccdkey['userid'];
					$paytoname = $authccdkey['username'];
					$paytonamebtn = $authccdkey['username'].'<br/><span class="badge bg-success me-2 updateauthccd" style="cursor:pointer;" onclick="openModalForAuthorizationCCD(\'hidden_routurl\', \'' . $paytoid . '\', \'' . $ccmdkey['ccid'] . '\', \'' . $ccmdkey['cardnumber'] . '\', \'' . $ccmdkey['cardtype'] . '\');">Update Authorization</span>';
				}
			}

			$ccmarray[]=array('sno'=>$ccmdkey['sno'], 'bankname'=>$ccmdkey['bankname'], 'accountno'=>$ccmdkey['accountno'], 'ifsccode'=>$ccmdkey['ifsccode'], 'accountid'=>$ccmdkey['accountid'], 'ccid'=>$ccmdkey['ccid'], 'cardtype'=>$ccmdkey['cardtype'], 'cardholdername'=>$ccmdkey['cardholdername'], 'cardnumber'=>$ccmdkey['cardnumber'], 'expirymonth'=>$ccmdkey['expirymonth'], 'expiryyear'=>$ccmdkey['expiryyear'], 'cvv'=>$ccmdkey['cvv'], 'billing_address'=>$ccmdkey['billing_address'],'paytoid'=>$paytoid, 'paytoname'=>$paytoname, 'paytonamebtn'=>$paytonamebtn);
		}
	}
	echo json_encode($ccmarray);
}else if($action=="addupdatecreditcarddetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$ifsccode = "";
	$bannkbranchname = "";
	$bannkbranchaddress = "";

	$ccretmsg=array();
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
	    $ccid = generateUniqueCreditCardId($conn, $companyid) ?? '';
	    // $accountid = $_POST['accountid'] ?? '';
	    $cardtype = $_POST['cardtype'] ?? '';

	    $cardholdername = $_POST['cardholdername'] ?? '';
	    $cardnumber = $_POST['cardnumber'] ?? '';
	    if (!validateCreditCardNumber($cardnumber)) {
	        echo json_encode(array('status'=>'Error', 'message'=>'Invalid credit card number. --'.$cardnumber.'==='.validateCreditCardNumber($cardnumber)));
	        die();
	    }
	    $expirymonth = $_POST['expirymonth'] ?? '';
	    $expiryyear = $_POST['expiryyear'] ?? '';
	    if (!validateExpiryDate($expirymonth,$expiryyear)) {
	        echo json_encode(array('status'=>'Error', 'message'=>'Invalid expiry month/year'));
	        die();
	    }
	    $cvv = $_POST['cvv'] ?? '';
	    if (!validateCVV($cvv,$cardtype)) {
	        echo json_encode(array('status'=>'Error', 'message'=>'Invalid CVV'));
	        die();
	    }
	    $bankname = $_POST['bankname'] ?? '';
	    if($_POST['accountno']){
			$accountno = $_POST['accountno'] ?? '';
	    }else{
			$accountno = $cardnumber;
	    }
		$billingaddress = $_POST['billingaddress'] ?? ''; 


	    // Validate input (basic example, customize as needed)
	    if (empty($ccid) || empty($cardtype) || empty($cardholdername) || empty($cardnumber) || empty($expirymonth) || empty($expiryyear) || empty($cvv)) {
	    	echo json_encode(array('status'=>'Error', 'message'=>'All fields are required.'));
	        die();

	    }

	    // Encrypt sensitive data (e.g., card number and CVV)
	    // $encrypted_cardnumber = base64_encode($cardnumber);
	    // $encrypted_cvv = base64_encode($cvv);

	    $encrypted_cardnumber = $cardnumber;
	    $encrypted_cvv = $cvv;

	    $accountname = $bankname.'-'.$cardtype.'('.substr($cardnumber, 0, 5).')';
		$banktype = "Credit Card";
		// Check if the record already exists in tbl_master_account
		$check_query = $conn->prepare("SELECT sno,accountid FROM tbl_master_account WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND account_type = ? AND accountno = ?");
		if (!$check_query) {
		    echo json_encode(array('status' => 'Error', 'message' => "Query preparation failed: " . $conn->error));
		    die();
		}
		$check_query->bind_param("sssss", $companyid, $subcompanyid, $projectid, $banktype, $accountno);
		$check_query->execute();
		$check_query->store_result();

		if ($check_query->num_rows > 0) {
		    // Bind the result to a variable
		    $check_query->bind_result($sno,$accountid);
		    $check_query->fetch();

		    // Update the existing record in tbl_master_account
		    $update_query = $conn->prepare("UPDATE tbl_master_account SET accountname = ?, accountholdername = ?, accountno = ?, ifsccode = ?, bankname = ?, branchname = ?, branchaddress = ? WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND account_type = ? AND sno = ?");
		    if (!$update_query) {
		        echo json_encode(array('status' => 'Error', 'message' => "Query preparation failed: " . $conn->error));
		        die();
		    }
		    $update_query->bind_param("sssssssssssi", $accountname,$cardholdername, $accountno, $ifsccode, $bankname, $bankbranchname, $bankbranchaddress, $companyid, $subcompanyid, $projectid, $banktype, $sno);
		    if ($update_query->execute()) {
		        echo json_encode(array('status' => 'Success', 'message' => "Account information updated successfully."));

		        // Check and update creditcard_master if the account exists
		        $cc_check_query = $conn->prepare("SELECT ccid FROM creditcard_master WHERE accountid = ?");
		        if (!$cc_check_query) {
		            echo json_encode(array('status' => 'Error', 'message' => "Query preparation failed: " . $conn->error));
		            die();
		        }
		        $cc_check_query->bind_param("s", $sno);
		        $cc_check_query->execute();
		        $cc_check_query->store_result();
		        if ($cc_check_query->num_rows > 0) {
		            // Update existing record in creditcard_master
		            $cc_update_query = $conn->prepare("UPDATE creditcard_master SET bankname = ?, accountid =?, accountno = ?, cardtype = ?, cardholdername = ?, cardnumber = ?, expirymonth = ?, expiryyear = ?, cvv = ?, billing_address = ? WHERE accountid = ?");
		            if (!$cc_update_query) {
		                echo json_encode(array('status' => 'Error', 'message' => "Query preparation failed: " . $conn->error));
		                die();
		            }
		            $cc_update_query->bind_param("sssssssssss", $bankname, $accountid, $accountno, $cardtype, $cardholdername, $encrypted_cardnumber, $expirymonth, $expiryyear, $encrypted_cvv, $billingaddress, $sno);
		            if ($cc_update_query->execute()) {
		                echo json_encode(array('status' => 'Success', 'message' => "Credit card information updated successfully."));
		            } else {
		                echo json_encode(array('status' => 'Error', 'message' => "Error: " . $cc_update_query->error));
		            }
		            $cc_update_query->close();
		        }
		        $cc_check_query->close();
		    } else {
		        echo json_encode(array('status' => 'Error', 'message' => "Error: " . $update_query->error));
		    }
		    $update_query->close();
		} else {
			
			$ppt=savetodbnewpayto_details($conn,$accountname,$accountno,$bankname,"","","","",$companyid,$subcompanyid,$projectid,"","",$loginname);
			if(count($ppt)>0){
				foreach ($ppt as $pptkey) {
					$paytoid = $pptkey['paytoid'];
				}
			}
							
			if($paytoid){
				$maxsccid=getmaxaccountid_frmaccountmaster($conn);
				$bankid=getmaxbankidfrommaster($conn,$bankname);
			    // Insert new record into tbl_master_account
			    $insert_query = $conn->prepare("INSERT INTO tbl_master_account (cmp_id, subcmp_id, projectid, paytoid, accountid, bankid, account_type, accountname, accountholdername, accountno, ifsccode, bankname, branchname, branchaddress) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			    if (!$insert_query) {
			        echo json_encode(array('status' => 'Error', 'message' => "Query preparation failed: " . $conn->error));
			        die();
			    }

			    $insert_query->bind_param("ssssssssssssss", $companyid, $subcompanyid, $projectid, $paytoid, $maxsccid, $bankid, $banktype, $accountname, $cardholdername, $accountno, $ifsccode, $bankname, $bankbranchname, $bankbranchaddress);
			    if ($insert_query->execute()) {
			    	// Insert new record into creditcard_master
			            $cc_insert_query = $conn->prepare("INSERT INTO creditcard_master (companyid, subcompanyid, projectid, ccid, bankname, accountno, accountid, cardtype, cardholdername, cardnumber, expirymonth, expiryyear, cvv, billing_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
			            if (!$cc_insert_query) {
			                echo json_encode(array('status' => 'Error', 'message' => "Query preparation failed: " . $conn->error));
			                die();
			            }
			            $cc_insert_query->bind_param("ssssssssssssss", $companyid, $subcompanyid, $projectid, $paytoid, $bankname, $accountno, $maxsccid, $cardtype, $cardholdername, $encrypted_cardnumber, $expirymonth, $expiryyear, $encrypted_cvv, $billingaddress);
			            if ($cc_insert_query->execute()) {
			                echo json_encode(array('status' => 'Success', 'message' => "Credit card information added successfully."));
			            } else {
			                echo json_encode(array('status' => 'Error', 'message' => "Error: " . $cc_insert_query->error));
			            }
			            $cc_insert_query->close();
			    } else {
			        echo json_encode(array('status' => 'Error', 'message' => "Error: " . $insert_query->error));
			    }
			    $insert_query->close();
			}else{
				echo json_encode(array('status'=>'Error', 'message'=>"Id genration failed"));
			}
		}
		$check_query->close();
	}else{
		echo json_encode(array('status'=>'Error', 'message'=>"data not get"));
	}
	$conn->close();
}else if($action=="addupdate_creditcarauthorization"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$authpersonname = $_POST['authpersonname'];
	$authmobileno = $_POST['authmobileno'];
	$authemailaddress = $_POST['authemailaddress'];
	$authbankname = $_POST['authbankname'];
	$authaccountno = $_POST['authaccountno'];
	$authbankifsccode = $_POST['authbankifsccode'];
	$authpaytoid = $_POST['authpaytoid'];
	$ccid = $_POST['authccid'];
	$loginname = $_POST['loginname'];
	$ccretmsg=array();
    // Validate input (basic example, customize as needed)
    if (empty($companyid) || empty($subcompanyid) || empty($projectid) || empty($authpersonname)) {
    	echo json_encode(array('status'=>'Error', 'message'=>'All fields are required.'));
        die();
    }
    if($authpaytoid){
	    $ppt=savetodbnewpayto_details($conn,$authpersonname,$authaccountno,$authbankname,"","","","",$companyid,$subcompanyid,$projectid,$authmobileno,$authemailaddress,$loginname,$authpaytoid);
    }else{
	    $ppt=savetodbnewpayto_details($conn,$authpersonname,$authaccountno,$authbankname,"","","","",$companyid,$subcompanyid,$projectid,$authmobileno,$authemailaddress,$loginname);
    }
	if(count($ppt)>0){
		foreach ($ppt as $pptkey) {
			$paytoid = $pptkey['paytoid'];
		}
	}
    $restimp=saveOrUpdateCreditCardAuthorizedPerson($conn, $companyid, $subcompanyid, $projectid, $ccid, $paytoid, $authpersonname);
    // print_r($restimp);
    if($restimp['status']=="Success" || $restimp['status']=="Alreadyexist"){
		$chklg=loginuserdetailsfrommaster($authconn,"","loginid",$authmobileno);
		if(count($chklg)>0){
			$conn->query("UPDATE creditcard_authorizedperson SET loginid='".$authmobileno."' WHERE userid='".$paytoid."'");
		}else{
			//update user login basic details
			$conn->query("INSERT INTO login_user_master(loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive,departmentid) VALUES('".$authpersonname."', '".$authmobileno."', '".$authmobileno."', '4', 'User', '".$authmobileno."', '".$authemailaddress."', 'yes', '')");
			$authprojectid="";
			if(mysqli_affected_rows($conn)>0){
				$conn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$authmobileno."', 'active')");
				if(mysqli_affected_rows($conn)>0){
					$conn->query("UPDATE creditcard_authorizedperson SET loginid='".$paytomobileno."' WHERE userid='".$paytoid."'");
				}
			}
		}
	}
    $conn->close();
	echo json_encode($restimp);
}else if($action=="projectwithsubcompajy_htmltableview"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$cmi=getcompanydetails($mconn,"companyid",$companyid);
	$sbcmi=getsubcompanydetails($mconn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
	$phtmlviw="";
	if(count($sbcmi)>0){
		//  All <br/><input type='checkbox' id='checkallprj' onclick='checkuncheck_checkbox(\"checkallprj\", \"checkallprjl\");'>
		$phtmlviw.="<h4 style='text-align:center; font-weight:bold;'>Project(s) for Imprest Authorizasion</h4>
		<table class='table table-all' style='width:100%;' border='1' id='imprestpayto_authorizationtbl'>
			<thead>
				<tr>
					<th style='width:100px; text-align:center;'>Selct</th>
					<th>Sub Company Name</th>
					<th>Project Name</th>
					<th style='display:none;'>companyid</th>
					<th style='display:none;'>subcompanyid</th>
					<th style='display:none;'>projectid</th>
				</tr>
			</thead>
			<tbody>";
		foreach ($sbcmi as $sbikey) {
			$prjl=getprojectdetails($mconn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
			if(count($prjl)>0){
				foreach ($prjl as $pkey) {
					if($projectid==$pkey['projectid']){
						$phtmlviw.="<tr>
							<td style='width:100px; text-align:center;'><input type='checkbox' class='checkallprjl' checked /></td>
							<td class='subcmpname_td'>".$sbikey['subcompanyname']."</td>
							<td class='projectname_td'>".$pkey['projectname']."</td>
							<td class='cmpid_td' style='display:none;'>".$sbikey['companyid']."</td>
							<td class='subcmpid_td' style='display:none;'>".$sbikey['subcompanyid']."</td>
							<td class='projectid_td' style='display:none;'>".$pkey['projectid']."</td>
						</tr>";
					}else{
						$phtmlviw.="<tr class='d-none'>
							<td style='width:100px; text-align:center;'><input type='checkbox' class='checkallprjl' /></td>
							<td class='subcmpname_td'>".$sbikey['subcompanyname']."</td>
							<td class='projectname_td'>".$pkey['projectname']."</td>
							<td class='cmpid_td' style='display:none;'>".$sbikey['companyid']."</td>
							<td class='subcmpid_td' style='display:none;'>".$sbikey['subcompanyid']."</td>
							<td class='projectid_td' style='display:none;'>".$pkey['projectid']."</td>
						</tr>";
					}
				}
			}
		}
			$phtmlviw.="</tbody>
		</table>";
	}else{
		$phtmlviw.="Data not available";
	}
	echo json_encode($phtmlviw);
}else if($action=="agencymasterlist_callback"){
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$agncylist=agencymasterlist($conn,$shortby,$shortbyvalue);
	echo json_encode($agncylist);
}else if($action=="addupdate_agencydetails"){
	$agencysno = $_POST['agencysno'];
	$agencyauthsno = $_POST['agencyauthsno'];
	$agencyname = $_POST['agencyname'];
	$responsibleperson = $_POST['authpersonname'];
	$mobileno = $_POST['authmobileno'];
	$whatsapno = $_POST['authwhatsappno'];
	$emailaddress = $_POST['authemailaddress'];
	$addedby  =$_POST['loginname'];
	$readytoremove = "";
	$rrst=array();
	$accin=agencymasterlist($conn,"sno",$agencysno);
	if(count($accin)>0){
		foreach ($accin as $accinkey) {
			$conn->query("UPDATE agency_master SET agencyname='".$agencyname."' where sno='".$accinkey['sno']."'");
			if(mysqli_affected_rows($conn)>0){
				$conn->query("UPDATE agency_authorization SET responsibleperson='".$responsibleperson."',mobileno='".$mobileno."',whatsapno='".$whatsapno."',emailaddress='".$emailaddress."' where sno='".$agencyauthsno."'");
				if(mysqli_affected_rows($conn)>0){
					$rrst=array('status'=>'Success', 'message'=>'Agencey details update in master.');
				}else{
					$rrst=array('status'=>'Error', 'message'=>'Agencey details did not update, due to technical issue or already updated all details');
				}
			}
		}
	}else if(count($accin)>0 && $readytoremove=="yes"){
		$conn->query("UPDATE agency_master SET tedit='removed' where sno='".$agencysno."'");
		if(mysqli_affected_rows($conn)>0){
			$rrst=array('status'=>'Success', 'message'=>'Agencey removed successfully.');
		}else{
			$rrst=array('status'=>'Error', 'message'=>'Agencey did not remove, due to technical issue');
		}
	}else{
		$ssq=$conn->query("SELECT sno FROM agency_master where lcase(agencyname)='".strtolower($agencyname)."'");
		if($ssq->num_rows>0){
			$rrst=array('status'=>'Error', 'message'=>'Agencey name already exist, Try with other name');
		}else{
			// create max agency id
			$mag=$conn->query("SELECT (COUNT(*) + 1) AS maxagid FROM agency_master");
			$mg=$mag->fetch_assoc();
			$agencyid=$mg['maxagid'];
			$conn->query("INSERT into agency_master(agencyid,agencyname,addedby) values('".$agencyid."','".$agencyname."','".$addedby."')");
			if(mysqli_affected_rows($conn)>0){
				
				$chklg=loginuserdetailsfrommaster($authconn,"","mobileno",$mobileno);
				// print_r($chklg);
				if(count($chklg)>0){
					foreach ($chklg as $chklgkey) {
						$loginid = $chklgkey['loginid'];
					}
					$conn->query("INSERT into agency_authorization(agencyid,loginid,responsibleperson,mobileno,whatsapno,emailaddress,addedby) values('".$agencyid."','".$loginid."','".$responsibleperson."','".$mobileno."','".$whatsapno."','".$emailaddress."','".$addedby."')");
					// $conn->query("UPDATE agency_authorization SET loginid='".$loginid."' where mobileno='".$mobileno."'");
					if(mysqli_affected_rows($conn)>0){
						$rrst=array('status'=>'Success', 'message'=>'New Agencey details save successfully with already registered login person');
					}
				}else{
					$conn->query("INSERT INTO login_user_master(loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive,departmentid) VALUES('".$responsibleperson."', '".$mobileno."', '".$mobileno."', '99', 'Agency Administrator', '".$mobileno."', '".$emailaddress."', 'yes', '')");
					if(mysqli_affected_rows($conn)>0){
						$rrst=array('status'=>'Success', 'message'=>'New Agencey details save successfully with new login authorization person');
					}
				}
			}else{
				$rrst=array('status'=>'Error', 'message'=>'Agencey details did not save, due to technical issue');
			}
		}
	}	
	echo json_encode($rrst);
}else if($action=="currencydetailsall"){ 
	$shortby = $_POST['shortby']; 
	$shortbyvalue = $_POST['shortbyvalue']; 
	$curinfo=getcurrenceyinfo($conn,$shortby,$shortbyvalue);	 
	echo json_encode($curinfo);	
}else if($action=="getsaleinvoicetype"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];  	 
	$client_data= getsaleinvoicetype($conn,$companyid,$subcompanyid,$projectid);	 
	echo json_encode($client_data);
}else if($action=="getdefaultinvoicetype"){ 	 
	$client_data= getdefaultinvoicetype($conn);	 
	echo json_encode($client_data); 
}else if($action=="getdefaultfinyr"){ 
	$client_data= getdefaultfinyr($conn);	 
	echo json_encode($client_data); 
}else if($action=="TransactionsFinancialYear"){ 
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$startmonthno = 4;
	$finyearlist= TransactionsFinancialYear($conn,$companyid,$subcompanyid,$projectid,$startmonthno);	 
	echo json_encode($finyearlist); 
}else if($action=="authorizedlogin_projectslist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$gpq = $authconn->query("SELECT GROUP_CONCAT(DISTINCT projectid) AS projectid_list FROM login_authorized_modules  WHERE status = 'active' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."'");
	$gpq = $authconn->query("SELECT GROUP_CONCAT(DISTINCT moduleid) AS moduleid_list FROM login_authorized_modules  WHERE status = 'active' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."'");
	$prjlist=array();
	if($gpq->num_rows>0){
		$mmrow=$gpq->fetch_assoc();
		$prjlist = getprojectdetails($conn,$companyid,$subcompanyid,"projectid",$mmrow['projectid_list']);
	}
	echo json_encode($prjlist);
}else if($action=="authorizedmodules_forprojects"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];

	$gpq = $authconn->query("SELECT GROUP_CONCAT(DISTINCT moduleid) AS moduleid_list FROM login_authorized_modules  WHERE status = 'active' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
	$mdlist=array();
	if($gpq->num_rows>0){
		$mmrow=$gpq->fetch_assoc();
		$mdlist = loginmodulesmasterlist($authconn,"moduleid",$mmrow['moduleid_list']);
	}
	echo json_encode($mdlist);
}else if($action=="authorizedloginmoduleid_ofprojects"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$moduleid = $_POST['moduleid'];
	$gpq = $authconn->query("SELECT sno,companyid,subcompanyid,projectid,loginid,moduleid,ispassword,ifnull(password,'') as password FROM login_authorized_modules  WHERE status = 'active' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and moduleid='".$moduleid."'");
	$mdlist=array();
	$authloginlist=array();
	if($gpq->num_rows>0){
		while($mrow=$gpq->fetch_assoc()){
			$lgm = loginuserdetailsfrommaster($authconn,"","loginid",$mrow['loginid']);
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
				$conn->query("UPDATE login_authorized_modules SET password='".$mdpasskey['mdlpass']."' where sno='".$mdpasskey['dbsno']."' and moduleid='".$moduleid."'");
				if(mysqli_affected_rows($conn)>0){
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
}else if($action=="requestapprovalpersonlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$ssqu=$conn->query("SELECT DISTINCT u.emailid as apprlemail,u.loginid,u.loginroleid,u.loginrolename  FROM login_user_master u join login_authorized_modules m on u.loginid=m.loginid WHERE lcase(u.isActive)='yes' and (m.status is null or status='' or m.status='active') and m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.moduleid='2'");
	$appprlist=array();
	if($ssqu->num_rows>0){
		while($rrw= $ssqu->fetch_assoc()){
			$appprlist[]=array('loginid'=>$rrw['loginid'], 'emailid'=>$rrw['apprlemail'], 'loginroleid'=>$rrw['loginroleid'], 'loginrolename'=>$rrw['loginrolename']);
		}
	}
	echo json_encode($appprlist);
}else if($action=="getdefaultcategorysubcategorylist"){
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$ggt=GetPurposeSubCategoryfromdefaultmaster($conn,$shortby,$shortbyvalue);
	echo json_encode($ggt);
}else if($action=="getidfromname"){
	header('Content-Type: application/json');
	$tablename = $_POST['tablename'] ?? '';
	$idcol = $_POST['idcol'] ?? '';
	$namecol = $_POST['namecol'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$namecolval = strtolower(trim($_POST['namecolval'] ?? ''));

	$tablename_safe = mysqli_real_escape_string($conn, $tablename);
	$idcol_safe = mysqli_real_escape_string($conn, $idcol);
	$namecol_safe = mysqli_real_escape_string($conn, $namecol);
	$namecolval_safe = mysqli_real_escape_string($conn, $namecolval);

	$query = "SELECT `$idcol_safe` FROM `$tablename_safe` WHERE projectid='$projectid' and lcase(trim(`$namecol_safe`)) = '$namecolval_safe' LIMIT 1";
	$result = mysqli_query($conn, $query);

	if ($row = mysqli_fetch_assoc($result)) {
	    echo json_encode(['status' => 'success', 'idcolval' => $row[$idcol]]);
	} else {
	    echo json_encode(['status' => 'error', 'message' => $query]);
	}
}else if($action=="savedigitizedinvoice"){
	global $conn;
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $paytoid = $_POST['paytoid'];
    $paytogstno = $_POST['paytogstno'];
    $invoiceno = $_POST['invoiceno'];
    $invoicedate = $_POST['invoicedate'];
    $invoicedata = $_POST['invoicedata'];
    $loginname = $_POST['loginname'];
    $resp = savetoinvoicedata($conn,$companyid,$subcompanyid,$projectid,"","",$invoicedate,$invoiceno,$invoicedata,$paytoid,$paytogstno,$loginname);
    echo json_encode($resp);
}
/*else if($action=="addnewpurpose_clientpurpose"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$providedcode = $_POST['providedcode'];
	$subcategoryname = $_POST['subcategoryname'];
	$purposename = $_POST['purposename'];
	$purposecategorytype = $_POST['purposecategorytype'];
	$loginname = $_POST['loginname'];
	$purposecategorytype = $_POST['purposecategorytype'];
	$ppdata=array();
	$rrtmsg=array();
	if($purposecategorytype=="credit" || $purposecategorytype=="deposit"){
		$ppdata=getPurposeCodeMasterClient_Credit($conn, $companyid, $subcompanyid, $projectid, "providedcode", $providedcode);
	}else{
		$ppdata=getPurposeCodeMasterClient($conn, $companyid, $subcompanyid, $projectid, "providedcode", $providedcode);
	}
	// print_r($ppdata);
	if(count($ppdata)>0){
		$msg="Already exist provided code (".$providedcode.") with purpose name: ".$ppdata[0]['purpose'];
		$rrtmsg=array('status'=>'alreadyexist', 'message'=>$msg);
	}else{
		if($purposecategorytype=="credit" || $purposecategorytype=="deposit"){
			$ppdata=getPurposeCodeMasterClient_Credit($conn, $companyid, $subcompanyid, $projectid, "providedcode", $providedcode);
		}else{
			$ppdata=getPurposeCodeMasterClient($conn, $companyid, $subcompanyid, $projectid, "providedcode", $providedcode);
		}
	}
	echo json_encode($rrtmsg);
}*/
else if($action=="savetodbbankchargeskey"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $bankaccountid = $_POST['bankaccountid'];
    $keyid=$_POST['keyid']??'';
    $bankchargeskey = $_POST['bankchargeskey'];
    $loginname = $_POST['loginname'];
    $data = ['companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'accountid' => $bankaccountid, 'bankdescription' => '', 'keyid' => $keyid, 'key1' => $bankchargeskey, 'loginname' => $loginname];
    $rrst=saveBankChargesKey($conn, $data);
    $rrtrn=array();
    if($rrst=="updated"){
    	$rrtrn=array('status'=>'Success', 'message'=>'Bank charges key update successfully');
    }else if($rrst=="inserted"){
    	$rrtrn=array('status'=>'Success', 'message'=>'Bank charges key inserted successfully');
    }else{
    	$rrtrn=array('status'=>'Error', 'message'=>'Bank charges key did not updated/inserted, due to Somthing wrong');
    }
    echo json_encode($rrtrn);
}else if($action=="BankChargesUpdatetoBankdescription"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $bankaccountid = $_POST['bankaccountid'];
    $loginname = $_POST['loginname'];
    $bkey=getBankChargesKeys($conn, $companyid, $subcompanyid, $projectid, "accountid", $bankaccountid);
    $rrst=array();
    $upclbook=0;
    if(count($bkey)>0){
    	$keytext = $bkey[0]['key1'];
    	// extractChargesAmount($description,$keywithamount);
    	$bankchargeamount = 0.00;
    	if($bkey[0]['key1']){
    		$clbkkk=$conn->query("SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='withdrawn' and account_id='".$bankaccountid."' and ifnull(total_amount,0)>0 and (bankcharge_amount is null or bankcharge_amount='') and description_bank<>'' order by banksno");
    		$actualtransferamount=0;
    		if($clbkkk->num_rows>0){
    			while($clbrow=$clbkkk->fetch_assoc()){
    				if($clbrow['description_bank']){
    					$upclbook++;
	    				$bankchargeamount = extractChargesAmount($clbrow['description_bank'],$keytext);
	    				$actualtransferamount = floatval($clbrow['total_amount']) - floatval($bankchargeamount);
	    				$conn->query("UPDATE tbl_closetobook SET bankcharge_amount='".$bankchargeamount."',actualtransfered_amount='".$actualtransferamount."' WHERE sno='".$clbrow['sno']."'");
    				}
    			}
    			if($upclbook>0){
	    			$rrst=array('status'=>'Success','message'=>'Bank charges have been successfully updated in the bank statement entries.');
    			}
    		}else{
    			$rrst=array('status'=>'Warning','message'=>'There are no bank statement entries pending update, or all entries have already been processed.');
    		}
    	}
    }else{
    	$rrst=array('status'=>'Warning','message'=>'No bank charges key found for the selected account.');
    }
    echo json_encode($rrst);
}else if($action=="GetBankChargeskey_callback"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $bankaccountid = $_POST['bankaccountid'];
    $bkey=getBankChargesKeys($conn, $companyid, $subcompanyid, $projectid, "accountid", $bankaccountid);
    echo json_encode($bkey);
}else if($action=="unreconstructedbanklist"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
}



function chkfornamereadyexist($tablename, $fldname, $txtstring, $conn,$condqry="1=1")
{
  $txtstring = mysqli_real_escape_string($conn, $txtstring); 
  $qry = "SELECT $fldname AS fldname FROM $tablename WHERE TRIM(REPLACE(REPLACE(REPLACE(REPLACE($fldname, '  ', ' '), ',', ''), '.', ''), '-', '')) = TRIM(REPLACE(REPLACE(REPLACE(REPLACE('$txtstring', '  ', ' '), ',', ''), '.', ''), '-', ''))   AND ". $condqry  ; 

	$rows1 = getarraydata($qry, $conn);
	if (count($rows1) > 0) { 

	    return $rows1[0]['fldname'];   
	} else {
	    return "";   
	}
}

// function countElementsWithEnter($string) {
//     $lines = array_filter(preg_split('/\r\n|\r|\n/', $string)); 
//     return count($lines);
// }

function EnterSepratedStringToArray($string) {
    return array_filter(preg_split('/\r\n|\r|\n/', $string)); 
}
?>