<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
include('../SimpleXLSX.php');
include('../SimpleXLS.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];
if($action=="show"){
	$orgcompanyid = $_POST['orgcompanyid'];
	$orgsubcompanyid = $_POST['orgsubcompanyid'];
	$orgprojectid = $_POST['orgprojectid'];
	$loginname = $_POST['loginname'];

	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    	if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
    		$file_ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    		if ( $xlsx = $file_ext=="xlsx"?SimpleXLSX::parse( $_FILES['file']['tmp_name'] ):SimpleXLS::parse( $_FILES['file']['tmp_name'] ) ) {
    			$html = "";
    			$purposehead = "";
    			$exlpurpose = getnonEmptyRows($xlsx->rows());
    			$exlpurpose_cell0=$exlpurpose[0][0];
    			$exlpurpose_cell1=$exlpurpose[0][1];
    			if($exlpurpose[0][0]){
	    			$pquery=$conn->query("select companyid, subcompanyid, projectid, projectname FROM tbl_project_master where (tedit is null or tedit='') and companyid in ('".str_replace(",", "','", $orgcompanyid)."') and subcompanyid='".str_replace(",", "','", $orgsubcompanyid)."' and  lcase(projectname)='".strtolower(trim($exlpurpose[0][0]))."'");
	    			if($pquery->num_rows>0){
	    				while($mrow=$pquery->fetch_assoc()){
	    					if($mrow['projectid']!=$orgprojectid){
	    						echo "projectnamenotmatched";
	    						die();
	    					}
	    				}
	    			}else{
	    				$pquery=$conn->query("select companyid, subcompanyid, projectid, projectname FROM tbl_project_master where (tedit is null or tedit='') and companyid in ('".str_replace(",", "','", $orgcompanyid)."') and subcompanyid='".str_replace(",", "','", $orgsubcompanyid)."' and  lcase(projectname)='".strtolower(trim($exlpurpose[0][1]))."'");
		    			if($pquery->num_rows>0){
		    				while($mrow=$pquery->fetch_assoc()){
		    					if($mrow['projectid']!=$orgprojectid){
		    						echo "projectnamenotmatched";
		    						die();
		    					}
		    				}
		    			}else{
		    				echo "projectnamenotmatched";
		    				die();
		    			}
	    			}
    			}else{
    				$pquery=$conn->query("select companyid, subcompanyid, projectid, projectname FROM tbl_project_master where (tedit is null or tedit='') and companyid in ('".str_replace(",", "','", $orgcompanyid)."') and subcompanyid='".str_replace(",", "','", $orgsubcompanyid)."' and  lcase(projectname)='".strtolower(trim($exlpurpose[0][1]))."'");
	    			if($pquery->num_rows>0){
	    				while($mrow=$pquery->fetch_assoc()){
	    					if($mrow['projectid']!=$orgprojectid){
	    						echo "projectnamenotmatched";
	    						die();
	    					}else{
	    						echo "matched second";
	    					}
	    				}
	    			}else{
	    				echo "projectnamenotmatched";
	    				die();
	    			}
    			}
    			$header = detectHeaderRowFromArray($exlpurpose);
    			// echo $header;
    			// print_r($exlpurpose);
    			// die();
    			if($header<0){
    				die("Error: This sheet contains no valid header. Please correct the sheet and then try again.");
    			}

    			$j=0;
    			// print_r($exlpurpose);
    			for($i=$header;$i<count($exlpurpose);$i++){
    				if($i>$header){
    					if(stristr((string)$exlpurpose[$i][0],".")===false){

    						$purposehead = $exlpurpose[$i][1];
    						if(!str_starts_with((string)$exlpurpose[$i+1][0],$exlpurpose[$i][0].".")){
    							$html.="<tr class='".(int)$exlpurpose[$i][0]."'>";
								$html.="<td class=''>".++$j."</td><td class='clt_providedcode'>".$exlpurpose[$i][0].".01</td><td class='clt_subcategoryname' data-subcategoryid='".(int)$exlpurpose[$i][0]."' data-originalsubcategory='".$purposehead."'>".(int)$exlpurpose[$i][0]."-".$purposehead."</td><td class='clt_purposename'>".$exlpurpose[$i][1]."</td><td class='action'>New uploaded</td>";
    						}
    					}else{
	    					$html.="<tr class='".(int)$exlpurpose[$i][0]."'>";
							$html.="<td class=''>".++$j."</td><td class='clt_providedcode'>".$exlpurpose[$i][0]."</td><td class='clt_subcategoryname' data-subcategoryid='".(int)$exlpurpose[$i][0]."' data-originalsubcategory='".$purposehead."'>".(int)$exlpurpose[$i][0]."-".$purposehead."</td><td class='clt_purposename'>".$exlpurpose[$i][1]."</td><td class='action'>New uploaded</td>";
    					}
    				}
    			}
    			echo $html;
    		}
    		else{
				echo "Error: "+$file_ext=="xlsx"?SimpleXLS::parseError():SimpleXLSX::parseError();
			}
    	}
    }
}else if($action=="categories"){
	$type = isset($_GET["type"])?$_GET["type"]:'';
	if($type=='credit'){
		$res=$conn->query("SELECT categoryid,category FROM main_category_master_credit where ifnull(tedit,'')=''");
	}
	else{
		$res=$conn->query("SELECT categoryid,category FROM main_category_master where ifnull(tedit,'')=''");
	}
	if($res->num_rows>1){
		$html = "<option value='' selected>Choose Category</option>";
	}
	if($res->num_rows>0){
		$i=1;
		while($row=$res->fetch_assoc()){
			$html.="<option value='".$row["categoryid"]."'>".$row["category"]."</option>";
		}
	}
	echo $html;
}
elseif($action=="subcategories"){
	$companyid=$_POST["companyid"];
	$subcompanyid=$_POST["subcompanyid"];
	$projectid=$_POST["projectid"];
	$type=isset($_POST["type"])?$_POST["type"]:"";
	$categoryid = $_POST["catid"];
	$subcatvalue = strtolower($_POST["subcatvalue"]);
	$providedcode = isset($_POST["providedcode"])?$_POST["providedcode"]:"";
	if($type=="credit"){
		$qry="SELECT sub_categoryid,sub_category FROM purpose_category_master_credit where categoryid='".$categoryid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and  ifnull(tedit,'')=''";
	}
	else{
		$qry="SELECT sub_categoryid,sub_category FROM purpose_category_master where categoryid='".$categoryid."'and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and  ifnull(tedit,'')=''";
	}
	$res=$conn->query($qry);
	if($categoryid==1 || $categoryid==2){
		if(!$providedcode){
			$html = "<option value='' selected>Choose Sub-Category</option>";
		}else{
			$html = "<option value='' >Choose Sub-Category</option>";
		}
	}
	if($res->num_rows==1){
		while($row=$res->fetch_assoc()){
			$html.="<option value='".$row["sub_categoryid"]."' selected>".$row["sub_category"]."</option>";
		}
	}
	else if($res->num_rows>0){
		while($row=$res->fetch_assoc()){

			$html.="<option value='".$row["sub_categoryid"]."' ".((string)$providedcode==$row["sub_categoryid"]?"selected":"").">".$row["sub_category"]."</option>";
		}
	}
	echo $html;
}elseif($action=="purpose"){
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$companyid=$_POST["companyid"];
		$subcompanyid=$_POST["subcompanyid"];
		$projectid=$_POST["projectid"];
		$type=isset($_GET["type"])?$_GET["type"]:"";
		if($type=="credit"){
			$qry = "SELECT * from purpose_code_master_credit where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and ifnull(tedit,'')='' order by provided_code";
		}
		else{
			$qry = "SELECT * from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and ifnull(tedit,'')='' order by provided_code";
			}

		$res = $conn->query($qry);
		$purposedata=[];
		if($res->num_rows>0){
			while($row=$res->fetch_assoc()){
				$purposedata[]=array(
					"categoryid"=>$row["categoryid"],
					"category"=>$row["category"],
					"subcategoryid"=>$row["sub_categoryid"],
					"subcategory"=>$row["sub_category"],
					"purposecode"=>$row["purposecode"],
					"purposeid"=>$row["purposeid"],
					"purpose"=>$row["purpose"],
					"providedcode"=>$row["provided_code"],
					"sno"=>$row["sno"],
					"source"=>$row["source"]
				);
			}
		}
		echo json_encode($purposedata);
	}
}else if($action=="saveclientpurpose"){
	$type = $_POST["type"]!=""?$_POST["type"]:'';
	$loginname = $_POST['loginname'];
	$purposedata = json_decode($_POST['purposedata'],true);
	$updc=0;
	$insc=0;
	$returnmsg=array();
	// print_r($purposedata);
	$companyid="";
	$subcompanyid="";
	$projectid="";
	if ($purposedata!="" && count($purposedata)>0){
		foreach($purposedata as $purposeentry){
			if(stristr($purposeentry["subcategoryid"],"-")){
				$subcategoryid_client = explode("-",$purposeentry["subcategoryid"])[0];
			}
			else{
				$subcategoryid_client = $purposeentry["subcategoryid"];
			}
			$data=array('companyid' => $purposeentry["companyid"], 'subcompanyid' => $purposeentry["subcompanyid"], 'projectid' => $purposeentry["projectid"], 'sub_categoryid_client' => $subcategoryid_client, 'sub_category_client' => $purposeentry["subcategory"],
			    'provided_code' => $purposeentry["providedcode"], 'purpose'=>$purposeentry['purposename'], 'source' => $purposeentry["source"], 'addedby' => $loginname);

			$companyid = $purposeentry["companyid"];
			$subcompanyid = $purposeentry["subcompanyid"];
			$projectid = $purposeentry["projectid"];

			if($type=='credit'){
				$rrst = UpdateInsertPurposeCodeMasterClient_Credit($conn, $data);
			}else{
				$rrst = UpdateInsertPurposeCodeMasterClient($conn, $data);
			}
			if(count($rrst)>0){
				if($rrst['status']=="Success"){		    		
		    		$insc++;
					addupdatepurposedetailsIntoMaster($conn,$purposeentry["companyid"],$purposeentry["subcompanyid"],$purposeentry["projectid"],$subcategoryid_client,$purposeentry["subcategory"],$purposeentry["providedcode"],"1","21",$type,$purposeentry['purposename'],$loginname);
				}
			}
		}
		if($insc>0){
			insertdefaultdebitpurposecodes($conn,$companyid,$subcompanyid,$projectid);
			$returnmsg[] = array('status' => 'Success', 'message' => 'Purpose details updated successfully');
		}else{
			$returnmsg[] = array('status' => 'Error', 'message' => 'Error in updated or inserted purpose');
		}
	}else{
		$returnmsg[] = array('status' => 'Error', 'message' => 'Data not available for any action');
	}
	echo json_encode($returnmsg);
}else if($action=="purposecodeof_clientsidedata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$purposecategorytype = $_POST['purposecategorytype'];
	$ppdata=array();
	if($purposecategorytype=="credit" || $purposecategorytype=="deposit"){
		$ppdata=getPurposeCodeMasterClient_Credit($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
	}else{
		$ppdata=getPurposeCodeMasterClient($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
	}
	echo json_encode($ppdata);
}else if($action=="addnew_clientpurpose"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$providedcode_client = $_POST['providedcode'];
	$subcategoryid_client = $_POST['subcategoryid'];
	$subcategoryname_client = $_POST['subcategoryname'];
	$dbsno = $_POST['dbsno'];
	$purposename_client = $_POST['purposename'];
	$purposecategorytype = $_POST['purposecategorytype'];
	$loginname = $_POST['loginname'];
	$purposecategorytype = $_POST['purposecategorytype'];
	// die();
	$ppdata=array();
	$rrtmsg=array();
	if($purposecategorytype=="credit" || $purposecategorytype=="deposit"){
		$ppdata=getPurposeCodeMasterClient_Credit($conn, $companyid, $subcompanyid, $projectid, "providedcode", $providedcode_client);
	}else{
		$ppdata=getPurposeCodeMasterClient($conn, $companyid, $subcompanyid, $projectid, "providedcode", $providedcode_client);
	}
	if(count($ppdata)>0 && ($dbsno=="" || $dbsno==null || $dbsno=="undefined" || $dbsno=="null")){
		$msg="Already exist provided code (".$providedcode_client.") with purpose name: ".$ppdata['purpose'];
		$rrtmsg=array('status'=>'alreadyexist', 'message'=>$msg);
	}else{
		$data=array('companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'sub_categoryid_client' => $subcategoryid_client, 'sub_category_client' => $subcategoryname_client, 'provided_code' => $providedcode_client, 'purpose'=>$purposename_client, 'source' => "manual", 'addedby' => $loginname);
		if($purposecategorytype=="credit" || $purposecategorytype=="deposit"){
			$rrst = UpdateInsertPurposeCodeMasterClient_Credit($conn, $data);
		}else{
			$rrst = UpdateInsertPurposeCodeMasterClient($conn, $data);
		}
		// print_r($rrst);
		if(count($rrst)>0){
			if(strtolower($rrst['status'])=="success" && strtolower($rrst['doaction']=="inserted")){
				addupdatepurposedetailsIntoMaster($conn,$companyid,$subcompanyid,$projectid,$subcategoryid_client,$subcategoryname_client,$providedcode_client,"1","21",$purposecategorytype,$purposename_client,$loginname);
				$rrtmsg = array('status' => 'Success', 'message' => $rrst['message']);
			}else if(strtolower($rrst['status'])=="success" && strtolower($rrst['doaction']=="updated")){
				$rrtmsg = array('status' => 'Success', 'message' => $rrst['message']);
			}else{
				$rrtmsg = array('status' => 'Error', 'message' => $rrst['message']);
			}
		}else{
			$rrtmsg = array('status' => 'Error', 'message' => 'Purpose did not add in master, Due to something wrong');
		}
	}
	echo json_encode($rrtmsg);
}elseif($action=="checksubcategoryandproviedcode"){
	$rrsct=array();
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$companyid=$_POST["companyid"];
		$subcompanyid=$_POST["subcompanyid"];
		$projectid=$_POST["projectid"];
		$providedcode=$_POST["providedcode"];
		if (stripos($providedcode, ".") !== false) {
		    $providedcodeexp = explode(".", $providedcode);
		    $temp_subcategorycode=$providedcodeexp[0];
		    $temp_providedcode=$providedcodeexp[1];
		    $checksubcatcode = checkexistsubcategorycode($conn,$companyid,$subcompanyid,$projectid,$temp_subcategorycode);
		    if($checksubcatcode){
				$mxprcode=maxprovidecode($conn,$companyid,$subcompanyid,$projectid,$temp_subcategorycode);
			    $mxprcodeexp = explode(".", $mxprcode);
			    $newmaxprovidedcode=$temp_subcategorycode.'.'.($mxprcodeexp[1]+1);
			    if(($mxprcodeexp[1]+1)!=$temp_providedcode){
			    	$rrsct=array('satus'=>'Error', 'action'=>'', 'message'=>'Entered provided code is not in a sequence, It should be '.$newmaxprovidedcode);
			    	echo json_encode($rrsct);
			    	die();
			    }    
			}else{
				$mxsubcatcode = maxsubcategorycode($conn,$companyid,$subcompanyid,$projectid);
			    if(($mxsubcatcode+1)!=$temp_subcategorycode){
			    	$rrsct=array('satus'=>'Error', 'action'=>'', 'message'=>'Entered subcompanyid is not in a sequence, It should be '.($mxsubcatcode+1), 'newsubcategoryid'=>($mxsubcatcode+1));
			    	echo json_encode($rrsct);
			    	die();
			    }
			}
		} else {
			$checksubcatcode = checkexistsubcategorycode($conn,$companyid,$subcompanyid,$projectid,$providedcode);
		    if($checksubcatcode){
			   $rrsct=array('satus'=>'Error', 'action'=>'alreadyexist', 'message'=>'Entered subcategoryid is already exist in our master', 'newsubcategoryid'=>'');
			    	echo json_encode($rrsct);
			    	die();
			}else{
				$mxsubcatcode = maxsubcategorycode($conn,$companyid,$subcompanyid,$projectid);
			    if(($mxsubcatcode+1)!=$providedcode){
			    	$rrsct=array('satus'=>'Error', 'action'=>'addnewcategoryid', 'message'=>'Entered subcategoryid is a new sub-category id and it is not in a sequence, It should be '.($mxsubcatcode+1), 'newsubcategoryid'=>($mxsubcatcode+1));
			    	echo json_encode($rrsct);
			    	die();
			    }
			}
		}
	}
	echo json_encode($rrsct);
}else if($action=="updateORremovepurpose_fromclientsidedata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecategorytype = $_POST['purposecategorytype'];
	$actiontype = $_POST['actiontype'];
	$dbsno = $_POST['dbsno'];
	$loginname = $_POST['loginname'];
	$rrst=array();
	if($purposecategorytype!="" && (strtolower($purposecategorytype)=="credit") || strtolower($purposecategorytype)=="deposit"){
		if($actiontype=="rm"){
			$tedit_remark = "purpose not visible on date ".date('Y-m-d h:i:s')." by ".$loginname;
			$conn->query("UPDATE purpose_codemaster_client_credit SET activestatus='inactive', tedit_remark='".$tedit_remark."' where sno='".$dbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst=array('status'=>'Success', 'message'=>'Purpose inactive from master list successfully');
			}else{
				$rrst=array('status'=>'Error', 'message'=>'Purpose did not inactive, due to somthing wrong');
			}
		}
	}else{
		if($actiontype=="rm"){
			$tedit_remark = "purpose inactive on date ".date('Y-m-d h:i:s')." by ".$loginname;
			$conn->query("UPDATE purpose_codemaster_client SET activestatus='inactive', tedit_remark='".$tedit_remark."' where sno='".$dbsno."'");
			if(mysqli_affected_rows($conn)>0){
				$rrst=array('status'=>'Success', 'message'=>'Purpose removed from master list successfully');
			}else{
				$rrst=array('status'=>'Error', 'message'=>'Purpose did not remove, due to somthing wrong');
			}
		}
	}
	echo json_encode($rrst);
}else if($action=="savemappingofclientpurpoe"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecategorytype = $_POST['purposecategorytype'];
	$realpurposesubcategoryid = $_POST['realpurposesubcategoryid'];
	$rel_categoryid = "";
	$rel_subcategoryid = "";
	$rel_subcategoryname = "";
	$rrstmsg=array();
	if($realpurposesubcategoryid){
		$subcatinfo=getMainSubcategoryMaster($conn, 'subcategoryid', $realpurposesubcategoryid);
		// print_r($subcatinfo);
		if($subcatinfo){
			foreach ($subcatinfo as $rlsubcatkey) {
				$rel_categoryid=$rlsubcatkey['categoryid'];
				$rel_subcategoryid=$rlsubcatkey['subcategoryid'];
				$rel_subcategoryname=$rlsubcatkey['subcategory'];
			}
		}else{
			$rrstmsg = array('status'=>'Warning', 'message'=>'Sub-Category details did not get form master, Please contact to web adminstrator');
			echo json_encode($rrstmsg);
			die();
		}
	}else{
		$rrstmsg = array('status'=>'Warning', 'message'=>'Sub-Category for Assigning to Client Purpose is missing');
		echo json_encode($rrstmsg);
		die();
	}

	$loginname = $_POST['loginname'];
	$clientpurposedata = json_decode($_POST['clientpurposedata'], true);
	// print_r($clientpurposedata);
	// die();
	if(count($clientpurposedata)>0 && $rel_categoryid!=""){
		$addno=0;
		foreach ($clientpurposedata as $clpkey) {
			$nns=addupdatepurposedetailsIntoMaster($conn,$companyid,$subcompanyid,$projectid,$clpkey['cl_subcategoryid'],$clpkey['cl_subcategoryname'],$clpkey['cl_providedcode'],$rel_categoryid,$rel_subcategoryid,$purposecategorytype,$clpkey['cl_purposename'],$loginname);
			// print_r($nns);
			if($nns['status']=="Success"){
				$addno++;
			}
		}
		if((int)$addno>0){
			$rrstmsg = array('status'=>'Success', 'message'=>'Purpose count '.$addno.' are mapped with us');	
		}
	}else{
		$rrstmsg = array('status'=>'Warning', 'message'=>'Sub-Category for Assigning to Client Purpose is missing');
	}
	echo json_encode($rrstmsg);
}

function checkexistsubcategorycode($conn,$companyid,$subcompanyid,$projectid,$subcategoryid){
	$qry = $conn->query("SELECT sub_categoryid as maxcategoryid from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and sub_categoryid='".$subcategoryid."' and ifnull(tedit,'')='' limit 1");
	if($qry->num_rows>0){
		$msbrow=$qry->fetch_assoc();
		return $msbrow['maxcategoryid'];
	}
}

function maxsubcategorycode($conn,$companyid,$subcompanyid,$projectid){
	$qry = $conn->query("SELECT distinct max(sub_categoryid) as maxcategoryid from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and ifnull(tedit,'')=''");
	if($qry->num_rows>0){
		$msbrow=$qry->fetch_assoc();
		return $msbrow['maxcategoryid'];
	}
}

function maxprovidecode($conn,$companyid,$subcompanyid,$projectid,$subcategoryid){
	$qryp = $conn->query("SELECT distinct max(provided_code) as maxprovidedcode from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and sub_categoryid='".$subcategoryid."' and ifnull(tedit,'')=''");
	if($qryp->num_rows>0){
		$mprdrow=$qryp->fetch_assoc();
		return $mprdrow['maxprovidedcode'];
	}
}

?>