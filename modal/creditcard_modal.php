<?php 
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
$action = $_GET['action'];
if($action=="creditcardsmallexpenseentry"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];	
	$requesttype = $_POST['requesttypename'];
	$creditcarduserid = $_POST['creditcarduser'];
	$paytoname = $_POST['creditcardusername'];
	$creditcardno = $_POST['creditcardno'];
	$requestamount = $_POST['requestamount'];
	$purposetxt = $_POST['purposetxt'];
	$paidtowhom = $_POST['paidtowhom'];
	$textremark = $_POST['textremark'];
	$loginid = $_POST['loginid'];
	$exptype = $_POST['exptype'];
	$transactiondate=$_POST['transactiondate'];
	if ($transactiondate==''||$transactiondate==null) {
		$transactiondate=date('Y-m-d');
	}
	$rrtmsg=array();
	//creditcard user details
	$crduser=authorizedcreditcardperson($conn,$companyid,$subcompanyid,$projectid,"userid",$creditcarduserid);
	$userid="";
	$username="";
	if(count($crduser)>0){
		foreach ($crduser as $crdkey) {
			$userid=$crdkey['userid'];
			$username=$crdkey['username'];
		}
	}else{
		$rrtmsg[]=array('status'=>'Error', 'message'=>'Your are not authorized for credit card expense. Please contact to admin');
		echo json_encode($rrtmsg);
		die();
	}

	// Directory where the audio file will be saved
    $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/creditcardexpense/".date('Ymd')."/";
	if (isset($_FILES['audio']) && !$_FILES['audio']['error']) {
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        //file name
        $vlicefilename=basename($_FILES['audio']['name']);
        $filetype = $_FILES['audio']['type'];
        // File path
        $voiceuploadFile = $uploadDir . $vlicefilename;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['audio']['tmp_name'], $voiceuploadFile)) {
        	
        }
    }
	if (isset($_FILES['filepath']) && !$_FILES['filepath']['error']) 
	{
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }        
        //file name
        $temp = explode(".", $_FILES["filepath"]["name"]);
		$filename = round(microtime(true)) . '.' . end($temp);
        // File path
        $uploadFile = $uploadDir . $filename;
        // $filepath = str_replace("../", "", $uploadFile);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile);
    }

    $fromaccountid="";
	$fromaccountname="";
	$fromaccountno=$creditcardno;
	$frombankname="";
	$fromifsccode="";
	if($creditcardno){
		$facc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountno",$creditcardno);
		if(count($facc)>0){
			foreach ($facc as $faccv) {
				$fromaccountid=$faccv['accountid'];
				$fromaccountname=$faccv['accountname'];
				$fromaccountno=$faccv['accountno'];
				$frombankname=$faccv['bankname'];
				$fromifsccode=$faccv['ifsccode'];
			}
		}else{
			
		}
	}

	
	$insbyin=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	$instructedbyemailid ="";
	$loginname ="";
	if(count($insbyin)>0){
		foreach ($insbyin as $sddkey) {
			$loginemailid = $sddkey['emailid'];
			$loginname = $sddkey['loginname'];
		}
	}

	$ppdr=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,'purposecode',$purposetxt);
	$purposecategoryid="";
	$purposecategory="";
	$purposesubcategoryid="";
	$purposesubcategory="";
	$purposecode="";
	//$purpose=$purposetxt;
	$purpose="";
	if(count($ppdr)>0){
		foreach ($ppdr as $pprkey) {
			$purposecategoryid=$pprkey['pcategorycode'];
			$purposecategory=$pprkey['pcategory'];
			$purposesubcategoryid=$pprkey['psub_categoryid'];
			$purposesubcategory=$pprkey['psub_category'];
			$purposecode=$pprkey['purposecode'];
			$purpose=$pprkey['purpose'];
		}
	}
	if ($exptype=='credit') {
		$description="Credit card (".$creditcardno.") expense - ".$textremark.' created by '.$loginname;
			$conn->query("insert IGNORE into creditcard_account(companyid, subcompanyid, projectid, subprojectid, from_accountid, from_accountno, userid, username, request_no, debitamount, purposecategoryid, purposecategory, purposesubcategoryid, purposesubcategory, purposecode, purpose, beneficiaryname, documentpath, audiofilepath, description,idate,createdby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$projectid."','".$fromaccountid."','".$fromaccountno."','".$userid."','".$username."','','".$requestamount."','".$purposecategoryid."','".$purposecategory."','".$purposesubcategoryid."','".$purposesubcategory."','".$purposecode."','".$purpose."','".$paidtowhom."','".str_replace("../", "", $uploadFile)."','".str_replace("../", "", $voiceuploadFile)."','".$description."','".$transactiondate."', '".$loginid."')");

			$conn->query("Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,accountno,account_id,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo,subprojectid) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','','".$frombankname."','".$fromaccountno."','".$fromaccountid."','".$requestamount."','".$description."','".$paytoname."','".$paidtowhom."','".$purposecategoryid."','".$purposecategory."','".$purposesubcategoryid."','".$purposesubcategory."','".$purpose."','".$purposecode."','".$loginid."','".str_replace("../", "", $voiceuploadFile)."','".$projectid."');");

	}else if ($exptype=='cash') {
		$description="Cash expense - ".$textremark.' created by '.$loginname;
			$conn->query("insert IGNORE into smallexpcash_account(companyid, subcompanyid, projectid, subprojectid, from_accountid, from_accountno, userid, username, request_no, debitamount, purposecategoryid, purposecategory, purposesubcategoryid, purposesubcategory, purposecode, purpose, beneficiaryname, documentpath, audiofilepath, description,idate,createdby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$projectid."','".$fromaccountid."','".$fromaccountno."','".$userid."','".$username."','','".$requestamount."','".$purposecategoryid."','".$purposecategory."','".$purposesubcategoryid."','".$purposesubcategory."','".$purposecode."','".$purpose."','".$paidtowhom."','".str_replace("../", "", $uploadFile)."','".str_replace("../", "", $voiceuploadFile)."','".$description."','".$transactiondate."', '".$loginid."')");
	}
	

	if(mysqli_affected_rows($conn)>0){
	    $rrtmsg[] =array('status'=>'Success','message' =>'Your credit card '.$creditcardno.' expense save successfully');
	}else{
		$rrtmsg[]=array('status'=>'Error', 'message'=>'Credit card number '.$creditcardno.' expense did not save, due to somthing wrong. Try again');
	}    				
	echo json_encode($rrtmsg);
}else if ($action=="reportsmallexpenseentry") {
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$loginid = $_POST['loginid'];
	$userid = $_POST['userid'];
	$exptype = $_POST['exptype'];
	$datalist=array();
	if ($exptype=='credit') {
		 $qry="Select sno,idate,debitamount,purposecode,purpose,description,documentpath,createdby,beneficiaryname from creditcard_account where userid='".$userid."' ";
	}else if ($exptype=='cash') {
		 $qry="Select sno,idate,debitamount,purposecode,purpose,description,documentpath,createdby,beneficiaryname from smallexpcash_account where userid='".$userid."' and (request_status is null or request_status='')";
	}
	$resultqry=$conn->query($qry);
	if ($resultqry->num_rows>0) {
		while ($rowc=$resultqry->fetch_assoc()) {
			$datalist[]=array('date'=>date('d-m-Y',strtotime($rowc['idate'])),'amount'=>$rowc['debitamount'],'purpose'=>$rowc['purpose'],'purposecode'=>$rowc['purposecode'],'description'=>$rowc['description'],'documentpath'=>$rowc['documentpath'],'sno'=>$rowc['sno'],'beneficiaryname'=>$rowc['beneficiaryname']);
		}
	}

	echo json_encode($datalist);
}
?>