<?php 
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action = $_GET['action'];
if($action=="upload_paymentreceipt"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$updwithrefno = $_POST['updwithrefno'];
	$dataupdatewith = $_POST['dataupdatewith'];
	
	// $audio = $_POST['audio'];
	$instructedby = $_POST['loginid'];
	$rrslt=array();
	$receiptpath="";
	if (isset($_FILES['filepath']) && !$_FILES['filepath']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/paymentreceipt/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($receiptpathDir)) {
            mkdir($receiptpathDir, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["filepath"]["name"]);
		$receiptname = round(microtime(true)) .'_'.date('His'). '.' . end($temp);

        // $filetype = $_FILES['filepath']['type'];
        // File path
        $uploadFile = $receiptpathDir . $receiptname;
        $receiptpath = str_replace("../", "", $uploadFile);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile);
    }
    if($dataupdatewith=="expencesheetonly" && $receiptpath!=""){
    	$conn->query("UPDATE tbl_expencesinexcelsheet SET transferreceiptpath='".$receiptpath."' WHERE voiceinstid='".$updwithrefno."' and (tedit is null or tedit='')");
    	if(mysqli_affected_rows($conn)>0){
    		 $rrslt[] =array('status'=>'Success','message' => 'Payment receipt has been uploaded with expencesheetonly successfully');
    	}else{
    		 $rrslt[] =array('status'=>'Error','message' => 'Payment receipt did not upload, due to technical issue with expencesheet only');
    	}
    }else if($dataupdatewith=="maintransactiononly" && $receiptpath!=""){
    	$conn->query("UPDATE tbl_maintransaction SET transferreceiptpath='".$receiptpath."' WHERE reference_no in('".str_replace(",", "','", $updwithrefno)."') and (tedit is null or tedit='')");
    	if(mysqli_affected_rows($conn)>0){
    		 $rrslt[] =array('status'=>'Success','message' => 'Payment receipt has been uploaded with maintransactiononly successfully');
    	}else{
    		 $rrslt[] =array('status'=>'Error','message' => 'Payment receipt did not upload, due to technical issue with maintransaction only');
    	}
    }else{
		$rrslt[] =array('status'=>'Error','message' => 'Payment receipt missing for uploading or somthing wrong');    	
    }
	echo json_encode($rrslt);
}else if($action=="postpaymentpart1"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $subprojectid = $_POST['subprojectid'];
    $requesttype = $_POST['requesttypename'];
    $fromaccountid = $_POST['fromaccountid'];
    $transctiondate = $_POST['transctiondate'];
    $transactionno = $_POST['transactionno'];
    $istaxreceiptupstatus = $_POST['istaxreceiptup'];
    $requestamount = $_POST['requestamount'];
    $taxamount = $_POST['taxamount'];
    $paytonametxt = $_POST['paytonametxt'];
    $purposetxt = $_POST['purposetxt'];
    $description = $_POST['description'];

    $VoiceRemarkFile="";
    $paymentreceiptfile="";
    $vatinvoicefile=$istaxreceiptupstatus;
    $otherdocumentfile="";

    $fromaccountname="";
    $fromaccountno="";
    $frombankname="";
    $fromifsccode="";
    $rrslt=array();
    if($fromaccountid){
        $facc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$fromaccountid);
        if(count($facc)>0){
            foreach ($facc as $faccv) {
                $fromaccountname=$faccv['accountname'];
                $fromaccountno=$faccv['accountno'];
                $frombankname=$faccv['bankname'];
                $fromifsccode=$faccv['ifsccode'];
            }
        }else{
            $fromaccountid="";
        }
    }else{
        $fromaccountid="";
    }

    $instructedby = $_POST['loginid'];
    $insbyin=loginuserdetailsfrommaster($authconn,"","loginid",$instructedby);
    $instructedbyemailid ="";
    $loginname ="";
    if(count($insbyin)>0){
        foreach ($insbyin as $sddkey) {
            $instructedbyemailid = $sddkey['emailid'];
            $loginname = $sddkey['loginname'];
        }
    }
    
    $description=$description." --this post payment created by ".$loginname;

    // upload voice remark
    if (isset($_FILES['voiceremark']) && !$_FILES['voiceremark']['error']) {
        // Directory where the audio file will be saved
        $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        //file name
        $filename=basename($_FILES['voiceremark']['name']);
        $filetype = $_FILES['voiceremark']['type'];
        // File path
        $VoiceRemarkFile = $uploadDir . $filename;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['voiceremark']['tmp_name'], $VoiceRemarkFile)) {
            
        }
    }


    if (isset($_FILES['paymentreceiptfile']) && !$_FILES['paymentreceiptfile']['error']) 
    {
        // Directory where the audio file will be saved
        $uploadDir2 = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir2)) {
            mkdir($uploadDir2, 0755, true);
        }        
        //file name
        $temp = explode(".", $_FILES["paymentreceiptfile"]["name"]);
        $preceipt = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        // File path
        $uploadpaymentreceipt = $uploadDir2 . $preceipt;
        $paymentreceiptfile = str_replace("../", "", $uploadpaymentreceipt);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['paymentreceiptfile']['tmp_name'], $uploadpaymentreceipt);
    }

    if (isset($_FILES['vatinvoicefile']) && $_FILES['vatinvoicefile']['error'] === UPLOAD_ERR_OK) 
    {
        // Directory where the audio file will be saved
        $uploadDir3 = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir3)) {
            mkdir($uploadDir3, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["vatinvoicefile"]["name"]);
        $filename3 = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        
        // File path
        $uploadFile3 = $uploadDir3 . $filename3;
        $vatinvoicefile = str_replace("../", "", $uploadFile3);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['vatinvoicefile']['tmp_name'], $uploadFile3);
    }

    if (isset($_FILES['otherdocumentfile']) && $_FILES['otherdocumentfile']['error'] === UPLOAD_ERR_OK) 
    {
        // Directory where the audio file will be saved
        $uploadDir4 = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir4)) {
            mkdir($uploadDir4, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["otherdocumentfile"]["name"]);
        $filename4 = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        
        // File path
        $uploadFile4 = $uploadDir4 . $filename4;
        $otherdocumentfile = str_replace("../", "", $uploadFile4);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['otherdocumentfile']['tmp_name'], $uploadFile4);
    }
    if($fromaccountid && $requestamount && $paytonametxt && $purposetxt && $description){
        $conn->query("insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,subprojectid,date,chequeno,amount,total_amount,taxamount,description,source,audiofilepath,filepath,filepath2,transferreceiptpath,payto,purpose,accountname,accountno,account_id) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$transctiondate."','".addslashes($transactionno)."','".addslashes($requestamount)."','".addslashes($requestamount)."', '".$taxamount."','".addslashes($description)."','postpaymentpart1','".str_replace("../", "", addslashes($VoiceRemarkFile))."', '".str_replace("../", "", addslashes($otherdocumentfile))."', '".str_replace("../", "", addslashes($vatinvoicefile))."', '".str_replace("../", "", addslashes($paymentreceiptfile))."','".addslashes($paytonametxt)."','".addslashes($purposetxt)."','".addslashes($fromaccountname)."','".addslashes($fromaccountno)."','".addslashes($fromaccountid)."')");
        if(mysqli_affected_rows($conn)>0){
            $rrslt[] =array('status'=>'Success','message' => 'Post payment  save successfully', 'dataupdatewith'=>'expencesheetonly');
        }else{
            $rrslt[] =array('status'=>'Error','message' => 'Data did not save due '.$conn->error, 'instructionid'=>'', 'file' => '');
        }
    }else{
        $rrslt[] =array('status'=>'Error','message' => 'Missing information: Please include a record instruction or specify both the payee name and purpose.');
    }               
    echo json_encode($rrslt);
}else if($action=="upload_voicerecording"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$requesttype = $_POST['requesttypename'];
	$requestamount = $_POST['requestamount'];
	$paytonametxt = $_POST['paytonametxt'];
	$purposetxt = $_POST['purposenametext'];
	$instructedby = $_POST['loginid'];
	$rrslt=array();
	$filepath2="";
	if (isset($_FILES['filepath']) && $_FILES['filepath']['error'] === UPLOAD_ERR_OK) 
	{
        // Directory where the audio file will be saved
        $uploadDir2 = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir2)) {
            mkdir($uploadDir2, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["filepath"]["name"]);
		$filename2 = round(microtime(true)) .'_'.date('His'). '.' . end($temp);

        // File path
        $uploadFile2 = $uploadDir2 . $filename2;
        $filepath2 = str_replace("../", "", $uploadFile2);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile2);
    }
	
    if (isset($_FILES['audio']) && $_FILES['audio']['error'] === UPLOAD_ERR_OK) {
        // Directory where the audio file will be saved
        $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        //file name
        $filename=basename($_FILES['audio']['name']);
        $filetype = $_FILES['audio']['type'];
        // File path
        $uploadFile = $uploadDir . $filename;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['audio']['tmp_name'], $uploadFile)) {
        	$uploadFile=str_replace("../", "", $uploadFile);
        } else {
        	$uploadFile="";
            $rrslt[] =array('status'=>'Error','message' => 'Failed to move uploaded file', 'instructionid'=>'');
        }
    }else{
    	$uploadFile="";
    }
    $qqr=0;
    $voiceinstruction="";
    $msg="";
    if($uploadFile){
    	$qqr="1";
    	$voiceinstruction="withvoiceinstruction";
    	$msg="Your voice instruction for the amount of Rs. ".$requestamount." has been saved successfully.";
    }else if($paytonametxt!="" && $purposetxt!=""){
    	$qqr="1";
    	$voiceinstruction="withoutvoiceinstruction";
    	$msg="Your instruction for an amount of Rs. ".$requestamount." for ".$paytonametxt.",  with the purpose of ".$purposetxt.", has been saved successfully.";
    }
    if($qqr==1){
		$instructionid=$companyid.$subcompanyid.$projectid."_".randomWithLength('5');
    	$conn->query("insert IGNORE into tbl_paymentrequest_voiceinstruction(companyid, subcompanyid, projectid, subprojectid, instructionid, instructiondate, filepath, filepath2, requesttype, instructedby,instructiontype,requestamount,payto,purpose) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$instructionid."','".date('Y-m-d')."','".str_replace("../", "", $uploadFile)."', '".$filepath2."', '".$requesttype."', '".$instructedby."','".$voiceinstruction."', '".$requestamount."','".$paytonametxt."','".$purposetxt."')");
        $rrslt[] =array('status'=>'Success','message' => $msg, 'instructionid'=>$instructionid, 'file' => $uploadFile);
    }else{
    	$rrslt[] =array('status'=>'Error','message' => 'Missing information: Please include a record instruction or specify both the payee name and purpose.', 'instructionid'=>'', 'file' => '');
    }
	echo json_encode($rrslt);
}else if($action=="upload_voicerecordingpost"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];	
	$requesttype = $_POST['requesttypename'];
	$requestamount = $_POST['requestamount'];
	// $paytonametxt = $_POST['paytonametxt'];

    $paytoid = $_POST['paytoid'];
    $paytoname = $_POST['paytoname'];
	
    // $purposetxt = $_POST['purposenametext'];
    $purposecode = $_POST['purposecode'];
    $purposename = $_POST['purposename'];

	$fromaccountid = $_POST['fromaccountid'];
    $bankname = $_POST['bankname'];
	$transactiondate = $_POST['transactiondate']??date('Y-m-d');
    $transactionno = $_POST['transactionno'];

    $textremark = $_POST['textremark'];
	$transactionnotxt="";
	if($transactionno=="" || $transactionno==null || $transactionno=="undefined"){
		$transactionnotxt="";
	}else{
		$transactionnotxt=" and Transaction no is ".$transactionno;
	}

	$fromaccountname="";
	$fromaccountno="";
	$frombankname="";
	$fromifsccode="";
	if($fromaccountid){
		$facc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$fromaccountid);
		if(count($facc)>0){
			foreach ($facc as $faccv) {
				$fromaccountname=$faccv['accountname'];
				$fromaccountno=$faccv['accountno'];
				$frombankname=$faccv['bankname'];
				$fromifsccode=$faccv['ifsccode'];
			}
		}
	}else{
        $fromaccountname='';
        $fromaccountno='';
        $frombankname=$bankname;
        $fromifsccode='';
    }

	$instructedby = $_POST['loginid'];
	$insbyin=loginuserdetailsfrommaster($authconn,"","loginid",$instructedby);
    
	$instructedbyemailid ="";
	$loginname ="";
	if(count($insbyin)>0){
		foreach ($insbyin as $sddkey) {
			$instructedbyemailid = $sddkey['emailid'];
			$loginname = $sddkey['loginname'];
		}
	}
	$rrslt=array();
	$filepath2="";
	$filepath3="";
	$filepath4="";
	$description=$textremark." :- this post payment voice instruction by ".$loginname.$transactionnotxt;
    
	if (isset($_FILES['audio']) && !$_FILES['audio']['error']) {
        // Directory where the audio file will be saved
        $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        //file name
        $filename=basename($_FILES['audio']['name']);
        $filetype = $_FILES['audio']['type'];
        // File path
        $uploadFile = $uploadDir . $filename;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['audio']['tmp_name'], $uploadFile)) {
        	
        }
    }
	if (isset($_FILES['filepath']) && !$_FILES['filepath']['error']) 
	{
        // Directory where the audio file will be saved
        $uploadDir2 = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir2)) {
            mkdir($uploadDir2, 0755, true);
        }        
        //file name
        $temp = explode(".", $_FILES["filepath"]["name"]);
		$filename2 = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        // File path
        $uploadFile2 = $uploadDir2 . $filename2;
        $filepath2 = str_replace("../", "", $uploadFile2);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile2);
    }

    if (isset($_FILES['filepath2']) && $_FILES['filepath2']['error'] === UPLOAD_ERR_OK) 
	{
        // Directory where the audio file will be saved
        $uploadDir3 = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir3)) {
            mkdir($uploadDir3, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["filepath2"]["name"]);
		$filename3 = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
		
        // File path
        $uploadFile3 = $uploadDir3 . $filename3;
        $filepath3 = str_replace("../", "", $uploadFile3);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['filepath2']['tmp_name'], $uploadFile3);
    }

    
	$filepath4="";
	$qqr=0;
    $voiceinstruction="";
    $msg="";
    if($uploadFile){
    	$qqr="1";
    	$voiceinstruction="withvoiceinstruction";
    	$msg="Your voice instruction for the amount of Rs. ".$requestamount." has been saved successfully.";
    }else if($paytoname!="" && $purposename!=""){
    	$qqr="1";
    	$voiceinstruction="withoutvoiceinstruction";
    	$msg="Your instruction for an amount of Rs. ".$requestamount." for ".$paytoname.", with the purpose of ".$purposename.", has been saved successfully.";
    }
    if($qqr==1){
    	$instructionid=$companyid.$subcompanyid.$projectid."_".randomWithLength('5');
        $conn->query("insert IGNORE into tbl_paymentrequest_voiceinstruction(companyid, subcompanyid, projectid,subprojectid,instructionid,instructiondate, filepath, filepath2,filepath3,transferreceiptpath,filepath4,requesttype,instructiontype,description,requeststatus,requestdate, instructedby,requestamount,paytoid,payto,purposecode,purpose,from_bankname,from_accountid,from_accountno,transactiondate,transactionno,textremark) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$instructionid."','".date('Y-m-d')."','".addslashes(str_replace("../", "", $uploadFile))."', '".addslashes($filepath2)."', '".addslashes($filepath3)."', '".addslashes($filepath3)."', '".addslashes($filepath4)."', '".$requesttype."','".addslashes($voiceinstruction)."','".addslashes($description)."','','', '".addslashes($instructedby)."','".addslashes($requestamount)."','".addslashes($paytoid)."','".addslashes($paytoname)."','".addslashes($purposecode)."','".addslashes($purposename)."','".addslashes($frombankname)."','".$fromaccountid."','".addslashes($fromaccountno)."','".addslashes($transactiondate)."','".addslashes($transactionno)."','".addslashes($textremark)."')");
    	if(mysqli_affected_rows($conn)>0){

            // $voucherinfo[]=array('beneficiaryname'=>$beneficiaryname,'expenseamount'=>$requestamount, 'invoicedate'=>'', 'invoiceno'=>'', 'vouchertype'=>'', 'invoicetype'=>'', 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'purposename'=>$purposename, 'attachmentpath'=>'', 'audiofilepath'=>$voicefilepath, 'voiceinstid'=>$voiceinstid, 'docsno'=>'','srm_attsno'=>'','srm_emailsno'=>'','invoicedata'=>$invoicedata,'totalrowsamount'=>0,'totalgstamount'=>0,'totalrowsdiscount'=>0,'otherdiscount'=>0,'totalpayableamount'=>$requestamount,'taxableamount'=>$requestamount,'invoicegstamount'=>'','ponumber'=>'');  
            // $rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo,$loginname,$purposecategoryid,$purposesubcategoryid,$purposecode,'no',$requestdescription);
            // $rrst=array();
            // if(count($rrs)>0){
            //     foreach ($rrs as $rrskey) {
            //         if($rrskey['status']=="Success"){
            //             $requeststatus="Book Created with Reference No ".$rrskey['referenceno'];
            //             $conn->query("UPDATE tbl_paymentrequest_voiceinstruction SET payto='".$paytoid."',purpose='".$purposecode."',from_accountid='".$fromaccountid."',description='".$requestdescription."',requeststatus='".$requeststatus."',requestedby='".$loginname."',requestdate='".date('Y-m-d')."' WHERE instructionid='".$voiceinstid."'");
            //         }
            //     }
            // }
           
	       $rrslt[] =array('status'=>'Success','message' => $msg, 'dataupdatewith'=>'expencesheetonly', 'instructionid'=>$instructionid, 'file' => $uploadFile);
    	}
    }else{
    	$rrslt[] =array('status'=>'Error','message' => 'Missing information: Please include a record instruction or specify both the payee name and purpose.', 'instructionid'=>'', 'file' => '');
    }				
	echo json_encode($rrslt);
}else if($action=="voicerecordingdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$voiceinstlist=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($voiceinstlist);
}else if($action=="upload_voicerecordingsalarysheet"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $requesttype = $_POST['requesttypename'];
    $instructedby = $_POST['loginid'];
    $rrslt=array(); 
    if (isset($_FILES['filepath']) && $_FILES['filepath']['error'] === UPLOAD_ERR_OK) {
        // Directory where the audio file will be saved
        $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        //file name
        $filename=basename($_FILES['filepath']['name']);
        $filetype = $_FILES['filepath']['type'];
        // File path
        $uploadFile = $uploadDir . $filename;
        // Move the uploaded file to the specified directory
        if (move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile)) {
            $instructionid=$companyid.$subcompanyid.$projectid."_".randomWithLength('5');
            $conn->query("insert IGNORE into tbl_paymentrequest_voiceinstruction(companyid, subcompanyid, projectid, instructionid, instructiondate, filepath, requesttype, instructedby,instructiontype) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$instructionid."','".date('Y-m-d')."','".str_replace("../", "", $uploadFile)."', '".$requesttype."', '".$instructedby."', 'salarysheet')");
            $rrslt[] =array('status'=>'Success','message' => 'Attached file uploaded successfully', 'instructionid'=>$instructionid, 'file' => $uploadFile);
        } else {
            $rrslt[] =array('status'=>'Error','message' => 'Failed to move uploaded file', 'instructionid'=>'');
        }
    } else {
        $rrslt[] =array('status'=>'Error','message' => 'Audio recording file is not available', 'instructionid'=>'');
    }
    
    echo json_encode($rrslt);
}else if($action=="rejectvoiceinstruction"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$voiceinstid = $_POST['voiceinstid'];
	$rejectremark = $_POST['rejectremark'];
	$loginid = $_POST['loginid'];
	$rrst=array();
	$rjfrm=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	if(count($rjfrm)>0){
		foreach ($rjfrm as $rjkey) {
			$rejectedbyname = $rjkey['loginname'];
			$rejectedbyemail = $rjkey['emailid'];
		}
	}
	$instructedbyemailid ="";
	$voiceinstlist=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$voiceinstid);
	if(count($voiceinstlist)>0){
		$instructedbyid = "";
		$vinstcreatedbyname = "";
		$instructiondatedsp = "";
		$filepath = "";
		foreach ($voiceinstlist as $sskey) {
			$instructedbyid = $sskey['instructedby'];
			$vinstcreatedbyname = $sskey['instructedbyname'];
			$instructiondatedsp = $sskey['instructiondatedsp'];
			$filepath = $sskey['filepath'];
		}
		if($instructedbyid){
			$insbyin=loginuserdetailsfrommaster($authconn,"","loginid",$instructedbyid);
			if(count($insbyin)>0){
				foreach ($insbyin as $sddkey) {
					$instructedbyemailid = $sddkey['emailid'];
				}
			}
		}
		$rejectremark = $rejectremark." rejected by ".$rejectedbyname;
		$conn->query("UPDATE tbl_paymentrequest_voiceinstruction SET tedit='".date('Y-m-d h:i:s')."', tedit_remark='".$rejectremark."' where instructionid='".$voiceinstid."' and (tedit is null or tedit='')");
		if(mysqli_affected_rows($conn)>0){
			$rrst=array('status'=>'Success', 'message'=>'Instruction id '.$voiceinstid.' rejected successfully');
		}else{
			$rrst=array('status'=>'Error', 'message'=>'Instruction id '.$voiceinstid.' did not reject, due to technical issue or already update');
		}
		sendemailreject_voiceinstruction($conn,$voiceinstid,$instructiondatedsp,$filepath,$vinstcreatedbyname,$instructedbyemailid,$rejectremark,$rejectedbyemail,$rejectedbyname);
	}else{
		$rrst=array('status'=>'Error', 'message'=>'Instruction id '.$voiceinstid.' did not get from master table, due to technical issue or already update');
	}
	echo json_encode($rrst);
}else if($action=="digitizedtot3voiceinstruction"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$voiceinstid = $_POST['voiceinstid'];
	$remark = $_POST['remark'];
	$loginid = $_POST['loginid'];
	$rrst=array();
	$rjfrm=loginuserdetailsfrommaster($authconn,"","loginid",$loginid);
	$loginname = "";
	$loginbyemail = "";
	if(count($rjfrm)>0){
		foreach ($rjfrm as $rjkey) {
			$loginname = $rjkey['loginname'];
			$loginbyemail = $rjkey['emailid'];
		}
	}
	$webfilepath="";
	if (isset($_FILES['uploadfile']) && $_FILES['uploadfile']['error'] === UPLOAD_ERR_OK) 
	{
        $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $temp = explode(".", $_FILES["uploadfile"]["name"]);
		$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        $uploadFile = $uploadDir . $newfilename;
        $webfilepath = str_replace("../", "", $uploadFile);
        move_uploaded_file($_FILES['uploadfile']['tmp_name'], $uploadFile);
    }
	$voiceinstlist=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$voiceinstid);
	if(count($voiceinstlist)>0){
		$instructedbyid = "";
		$vinstcreatedbyname = "";
		$instructiondatedsp = "";
		$filepath = "";
		foreach ($voiceinstlist as $sskey) {
			$instructedbyid = $sskey['instructedby'];
			$vinstcreatedbyname = $sskey['instructedbyname'];
			$instructiondatedsp = $sskey['instructiondatedsp'];
			$filepath = $sskey['filepath'];		
			$description = $sskey['description']." updated by ".$loginname;

			$conn->query("insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,description,remarks,source,voiceinstid,filepath_digitized,audiofilepath,filepath,filepath2) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".date('Y-m-d')."','".$description."','".$remark."','voiceinstruction','".$voiceinstid."','".str_replace("../", "", $webfilepath)."','".str_replace("../", "", $sskey['filepath'])."', '".str_replace("../", "", $sskey['filepath2'])."', '".str_replace("../", "", $sskey['filepath3'])."')");
			if(mysqli_affected_rows($conn)>0){
				$conn->query("UPDATE tbl_paymentrequest_voiceinstruction SET requeststatus='update in t3 table', requestedby='".$loginname."',requestdate='".date('Y-m-d')."' where instructionid='".$voiceinstid."' and (tedit is null or tedit='')");
				$rrst=array('status'=>'Success', 'message'=>'Instruction id '.$voiceinstid.' update in T3 for reconcilation successfully');
			}else{
				$rrst=array('status'=>'Error', 'message'=>'Instruction id '.$voiceinstid.' did not update in t3, due to technical issue or already update');
			}
		}
	}else{
		$rrst=array('status'=>'Error', 'message'=>'Instruction id '.$voiceinstid.' did not get from master table, due to technical issue or already update');
	}
	echo json_encode($rrst);
}else if($action=="voicerecordingdetailswithshortby"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$voiceinstlist=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($voiceinstlist);
}else if($action=="minimumdigitizedot3voiceinstruction"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];	
	$paytoname = $_POST['paytoname'];
	$amount = $_POST['amount'];
	$purposetext = $_POST['purposetext'];
	$voiceinstid = $_POST['voiceinstid'];
	$loginname = $_POST['loginname'];
	$remark = $_POST['remark'].' update by '.$loginname;
	$audiofile = $_POST['audiofile'];
	$uploadfilepath="";
	if (isset($_FILES['uploadfile']) && $_FILES['uploadfile']['error'] === UPLOAD_ERR_OK) 
	{
        $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $temp = explode(".", $_FILES["uploadfile"]["name"]);
		$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        $uploadFile = $uploadDir . $newfilename;
        $uploadfilepath = str_replace("../", "", $uploadFile);
        move_uploaded_file($_FILES['uploadfile']['tmp_name'], $uploadFile);
    }
    $audioFilepath="";
    if(isset($_FILES['audiofile']) && $_FILES['audiofile']['error'] === UPLOAD_ERR_OK) {
        // Directory where the audio file will be saved
        $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        //file name
        $filename=basename($_FILES['audiofile']['name']);
        $filetype = $_FILES['audiofile']['type'];
        // File path
        $audioFile = $uploadDir . $filename;
        // Move the uploaded file to the specified directory
        $audioFilepath = str_replace("../", "", $audioFile);
        move_uploaded_file($_FILES['audiofile']['tmp_name'], $audioFile);
    }
    
    $voiceinstlist=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$voiceinstid);
	if(count($voiceinstlist)>0){
		$instructedbyid = "";
		$vinstcreatedbyname = "";
		$instructiondatedsp = "";
		$filepath = "";
		foreach ($voiceinstlist as $sskey) {
			$instructedbyid = $sskey['instructedby'];
			$vinstcreatedbyname = $sskey['instructedbyname'];
			$instructiondatedsp = $sskey['instructiondatedsp'];
			$filepath = $sskey['filepath'];		
			$description = $sskey['description'];
 			$conn->query("insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,payto,amount,total_amount,purpose,description,remarks,source,voiceinstid,audiofilepath,filepath,filepath2,audiofilepath_digitized,filepath_digitized) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".date('Y-m-d')."','".$paytoname."','".$amount."','".$amount."','".$purposetext."','".$description."','".$remark."','voiceinstruction','".$voiceinstid."','".str_replace("../", "", $sskey['filepath'])."', '".str_replace("../", "", $sskey['filepath2'])."', '".str_replace("../", "", $sskey['filepath3'])."', '".str_replace("../", "", $audioFilepath)."', '".str_replace("../", "", $uploadfilepath)."')");
			if(mysqli_affected_rows($conn)>0){
				$conn->query("UPDATE tbl_paymentrequest_voiceinstruction SET requeststatus='update in t3 table', requestedby='".$loginname."',requestdate='".date('Y-m-d')."' where instructionid='".$voiceinstid."' and (tedit is null or tedit='')");
				$rrst=array('status'=>'Success', 'message'=>'Instruction id '.$voiceinstid.' update in T3 for reconcilation successfully');
			}else{
				$rrst=array('status'=>'Error', 'message'=>'Instruction id '.$voiceinstid.' did not update in t3, due to technical issue or already update');
			}
		}
	}else{
		$rrst=array('status'=>'Error', 'message'=>'Instruction id '.$voiceinstid.' did not get from master table, due to technical issue or already update');
	}
	echo json_encode($rrst);
}
?>