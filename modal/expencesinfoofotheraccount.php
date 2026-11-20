<?php 
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action = $_GET['action'];
if($action=="imprestexpences"){
	 $companyid = $_POST['companyid'];
	 $subcompanyid = $_POST['subcompanyid'];
	 $projectid = $_POST['projectid'];
	 $subprojectid = $_POST['subprojectid'];	
	 $requesttype = $_POST['requesttypename'];
	 $requestamount = $_POST['requestamount'];
	 $purposetxt = $_POST['purposetxt'];
	 // $paidtowhom = $_POST['paidtowhom'];
	 $textremark = $_POST['textremark'];
	 $loginid = $_POST['loginid'];
	 $beneficiarynamextn = $_POST['paidtowhom'];
	 $requestdate=$_POST['requestdate'];
	 $paytoaccountid=$_POST['paytoaccountid'];
	 $paytoacc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$paytoaccountid);
	 $paytoname="";
	 if(count($paytoacc)>0){
	 	foreach ($paytoacc as $paytoacckey) {
	 		$paytoname = $paytoacckey['paytoname'];
	 	}
	 }

	$uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/imprestexpense/".date('Ymd')."/";
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
        $uploadFile = $uploadDir . $filename;
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['filepath']['tmp_name'], $uploadFile);
    } 

	$conn->query("Insert into tbl_expencesinfoofotheraccount(company_id,subcompany_id,project_id,date,total_amount,description,payto,beneficiaryname,purpose,loginid,audiofilepath,subprojectid,type,filepath,account_id) values('".$companyid."','".$subcompanyid."','".$projectid."','".$requestdate."','".$requestamount."','".$textremark."','".$paytoname."','".$beneficiarynamextn."','".$purposetxt."','".$loginid."','".str_replace("../", "", $voiceuploadFile)."','".$projectid."','Imprest','".str_replace("../", "", $uploadFile)."','".$paytoaccountid."');");

	if(mysqli_affected_rows($conn)>0){
	    $rrtmsg[] =array('status'=>'Success','message' =>'Expense save successfully');
	}else{
		$rrtmsg[]=array('status'=>'Error', 'message'=>'Expense did not save, due to somthing wrong. Try again');
	}    				
	echo json_encode($rrtmsg);	
}else if ($action=="cashexpences") {
	$companyid = $_POST['companyid'];
	 $subcompanyid = $_POST['subcompanyid'];
	 $projectid = $_POST['projectid'];
	 $subprojectid = $_POST['subprojectid'];	
	 $requesttype = $_POST['requesttypename'];
	 $requestamount = $_POST['requestamount'];
	 $purposetxt = $_POST['purposetxt'];
	 $paidtowhom = $_POST['paidtowhom'];
	 $textremark = $_POST['textremark'];
	 $loginid = $_POST['loginid'];
	 $beneficiarynamextn = $_POST['beneficiarynamextn'];
	 $transactiondate=$_POST['transactiondate'];
	 $accid=$_POST['accid'];

	 $uploadDir = "../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/cashexpense/".date('Ymd')."/";
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


	

	$conn->query("Insert into tbl_expencesinfoofotheraccount(company_id,subcompany_id,project_id,date,total_amount,description,payto,beneficiaryname,purpose,loginid,audiofilepath,subprojectid,type,filepath,account_id) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','".$requestamount."','".$textremark."','".$paidtowhom."','".$beneficiarynamextn."','".$purposetxt."','".$loginid."','".str_replace("../", "", $voiceuploadFile)."','".$projectid."','Cash','".str_replace("../", "", $uploadFile)."','".$accid."');");

	if(mysqli_affected_rows($conn)>0){
	    $rrtmsg[] =array('status'=>'Success','message' =>'Expense save successfully');
	}else{
		$rrtmsg[]=array('status'=>'Error', 'message'=>'Expense did not save, due to somthing wrong. Try again');
	}    				
	echo json_encode($rrtmsg);	
}else if ($action=="creditcardexpences") {
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];	
	$requesttype = $_POST['requesttypename'];
	 // $imprestuserid = $_POST['creditcarduser'];
	 // $imprestusername = $_POST['creditcarduser'];
	 // $paytoname = $_POST['creditcardusername'];
	 $requestamount = $_POST['requestamount'];
	 $purposetxt = $_POST['purposetxt'];
	 $paidtowhom = $_POST['paidtowhom'];
	 $textremark = $_POST['textremark'];
	 $loginid = $_POST['loginid'];
	 $beneficiarynamextn = $_POST['beneficiarynamextn'];
	 $creditcard_id = $_POST['creditcard_id'];
	 $creditcard_no = $_POST['creditcard_no'];
	 $transactiondate=$_POST['transactiondate'];
	 

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

	$conn->query("Insert into tbl_expencesinfoofotheraccount(company_id,subcompany_id,project_id,date,total_amount,description,payto,beneficiaryname,purpose,loginid,audiofilepath,subprojectid,type,ccid,creditcardno,filepath,account_id) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','".$requestamount."','".$textremark."','".$paidtowhom."','".$beneficiarynamextn."','".$purposetxt."','".$loginid."','".str_replace("../", "", $voiceuploadFile)."','".$projectid."','Credit Card','".$creditcard_id."','".$creditcard_no."','".str_replace("../", "", $uploadFile)."','".$creditcard_id."');");

	if(mysqli_affected_rows($conn)>0){
	    $rrtmsg[] =array('status'=>'Success','message' =>'Expense save successfully');
	}else{
		$rrtmsg[]=array('status'=>'Error', 'message'=>'Expense did not save, due to somthing wrong. Try again');
	}    				
	echo json_encode($rrtmsg);	
}else if($action=="checkpaytohaveOtherTypeoAccount"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$requestno = $_POST['requestno'];
	$requestcategoryid = $_POST['requestcategoryid'];
	$rrqs=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
	// print_r($rrqs);
	$msg=array();
	if(count($rrqs)>0){
		$reqsb=getrequestbreakdowndetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
		if(count($reqsb)>0){
			if($requestcategoryid==2){
				$acccinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"allloanaccount","");
				if(count($acccinfo)>0){
					$ex=0;
					foreach ($acccinfo as $accinkey) {
						if($rrqs[0]['paytoid']==$accinkey['paytoid']){
							$ex=1;
						}
					}
					if($ex==0){
						$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not authorized for loan. Please authorize him/her for loan first.<br>Do you want to authorize for loan? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddLoanAccount(\"hidden_rooturl\");'>Authorize for Loan</button>");

					}else{
						$msg = array("status"=>"Success", "message"=>$rrqs[0]['payto'] . " registered our master as loan account");
					}
				}else{
					$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not authorized for loan. Please authorize him/her for loan first.<br>Do you want to authorize for loan? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddLoanAccount(\"hidden_rooturl\">Authorize for Loan</button>");

				}
			}else if($requestcategoryid==3){
				$acccinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"Imprest","");
				if(count($acccinfo)>0){
					$ex=0;
					foreach ($acccinfo as $accinkey) {
						if($rrqs[0]['paytoid']==$accinkey['paytoid']){
							$ex=1;
						}
					}
					if($ex==0){
						$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not authorized for imprest. Please authorize him/her for imprest first.<br>Do you want to authorize for imprest? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddImprestAccount(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'>Authorize for Imprest</button>");

					}else{
						$msg = array("status"=>"Success", "message"=>$rrqs[0]['payto'] . " registered our master as imprest account");
					}
				}else{
					$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not authorized for imprest. Please authorize him/her for imprest first.<br>Do you want to authorize for imprest? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddImprestAccount(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'>Authorize for Imprest</button>");

				}
			}else if($requestcategoryid==5){
				$acccinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"onlyfd_accountlist","");
				if(count($acccinfo)>0){
					$ex=0;
					foreach ($acccinfo as $accinkey) {
						if($rrqs[0]['paytoid']==$accinkey['paytoid']){
							$ex=1;
						}
					}
					if($ex==0){
						$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " does not have a fixed deposit account. Please add a fixed deposit account first.<br>Do you want to add a fixed deposit account? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddNewFDBank(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> Fixed Deposit Account</button>");

					}else{
						$msg = array("status"=>"Success", "message"=>$rrqs[0]['payto'] . " registered our master as fixed deposit account");
					}
				}else{
					$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " does not have a fixed deposit account. Please add a fixed deposit account first.<br>Do you want to add a fixed deposit account? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddNewFDBank(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> Fixed Deposit Account</button>");

				}
			}else if($requestcategoryid==6){
				$acccinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"cash","");
				if(count($acccinfo)>0){
					$ex=0;
					foreach ($acccinfo as $accinkey) {
						if($rrqs[0]['paytoid']==$accinkey['paytoid']){
							$ex=1;
						}
					}
					if($ex==0){
						$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " does not have a cash account. Please add a cash account first.<br>Do you want to add a cash account? <button class='btn btn-deepblue' onclick='openModalForAddCashAccount(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> Cash Account</button>");

					}else{
						$msg = array("status"=>"Success", "message"=>$rrqs[0]['payto'] . " registered our master as cash account");
					}
				}else{
					$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " does not have a cash account. Please add a cash account first.<br>Do you want to add a cash account? <button class='btn btn-deepblue' onclick='openModalForAddCashAccount(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> Cash Account</button>");

				}
			}else if($requestcategoryid==7){
				$acccinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"allaccountofprojectonlybank","");
				if(count($acccinfo)>0){
					$ex=0;
					foreach ($acccinfo as $accinkey) {
						if($rrqs[0]['paytoid']==$accinkey['paytoid']){
							$ex=1;
						}
					}
					if($ex==0){
						$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not an internal bank account of the project. Please add a bank account first.<br>Do you want to add an internal bank account for the project? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddNewBank(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> New Bank Account</button>");

					}else{
						$msg = array("status"=>"Success", "message"=>$rrqs[0]['payto'] . " registered our master as internal bank");
					}
				}else{
					$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not an internal bank account of the project. Please add a bank account first.<br>Do you want to add an internal bank account for the project? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddNewBank(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> New Bank Account</button>");

				}
			}else if($requestcategoryid==8){
				$acccinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"creditcard","");
				if(count($acccinfo)>0){
					$ex=0;
					foreach ($acccinfo as $accinkey) {
						if($rrqs[0]['paytoid']==$accinkey['paytoid']){
							$ex=1;
						}
					}
					if($ex==0){
						$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not a credit card account of the project. Please add a credit card account first.<br>Do you want to add a credit card account for the project? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddCreditCard(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> Credit Card</button>");

					}else{
						$msg = array("status"=>"Success", "message"=>$rrqs[0]['payto'] . " registered our master as credit card");
					}
				}else{
					$msg = array("status"=>"Error", "message"=>$rrqs[0]['payto'] . " is not a credit card account of the project. Please add a credit card account first.<br>Do you want to add a credit card account for the project? <button class='btn btn-deepblue' data-datapaytoname='" . $rrqs[0]['payto'] . "' data-datapaytoid='" . $rrqs[0]['paytoid'] . "' onclick='openModalForAddCreditCard(\"hidden_rooturl\", \"" . $rrqs[0]['payto'] . "\", \"" . $rrqs[0]['paytoid'] . "\");'><i class='fa fa-plus'></i> Credit Card</button>");

				}
			}else if($requestcategoryid==9){
				$msg=array("status"=>"NotChange", "message"=>" You can not change the request category");
			}else if($requestcategoryid==10){
				$msg=array("status"=>"NotChange", "message"=>" You can not change the request category");
			}else if($requestcategoryid==11){
				$msg=array("status"=>"NotChange", "message"=>" You can not change the request category");
			}else if($requestcategoryid==12){
				$msg=array("status"=>"NotChange", "message"=>" You can not change the request category");
			}else if($requestcategoryid=='doubtfull'){
				$msg = array("status"=>"doubtfull", "message"=>"It is a doubt full request category in this request.<br>Do you mark doubtfull request category?");
			}
		}else{
			$msg=array("status"=>"Error", "message"=>" Incomplete request details, Please reject this request");
		}
	}else{
		$msg=array("status"=>"Error", "message"=>" Request details did not available, Please reject this request");
	}
	echo json_encode($msg);
}else if($action=="savetodb_requestcategorytype"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$requestno = $_POST['requestno'];
	$requestcategoryid = $_POST['requestcategoryid'];
	$pppt=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecategoryid",$requestcategoryid);
	$ttttmsg=array();
	$purposecategoryid = "";
	$purposecategoryname = "";
	$purposesubcategoryid = "";
	$purposesubcategoryname = "";
	$purposecode = "";
	$purposename = "";
	if(count($pppt)==1 && $requestcategoryid!="" && $requestcategoryid!="doubtfull"){
		foreach ($pppt as $ppptkey) {
			$purposecategoryid = $ppptkey['pcategorycode'];
			$purposecategoryname = $ppptkey['pcategory'];
			$purposesubcategoryid = $ppptkey['psub_categoryid'];
			$purposesubcategoryname = $ppptkey['psub_category'];
			$purposecode = $ppptkey['purposecode'];
			$purposename = $ppptkey['purpose'];
		}
		$rrqs=getrequestdetails($conn,$companyid,$subcompanyid,$projectid,"requestno",$requestno);
		$msg=array();
		if(count($rrqs)>0 && $purposecategoryid!=""){
			$conn->begin_transaction();
			try {
			    $sql1 = "UPDATE tbl_paymentrequest_breakdown SET 
			                purpose_type = '$purposecategoryname',
			                purpose_categoryid = '$purposecategoryid',
			                purpose_category = '$purposecategoryname',
			                purpose_subcategoryid = '$purposesubcategoryid',
			                purpose_subcategory = '$purposesubcategoryname',
			                purpose_code = '$purposecode',
			                purpose = '$purposename'
			            WHERE 
			                (tedit IS NULL OR tedit = '') AND 
			                companyid = '$companyid' AND 
			                subcompanyid = '$subcompanyid' AND 
			                projectid = '$projectid' AND 
			                request_no = '$requestno'";

			    $sql2 = "UPDATE tbl_paymentrequest SET 
			                purpose_type = '$purposecategoryname', paymenttypestatus ='modify'
			            WHERE 
			                (tedit IS NULL OR tedit = '') AND 
			                companyid = '$companyid' AND 
			                subcompanyid = '$subcompanyid' AND 
			                projectid = '$projectid' AND 
			                request_no = '$requestno'";

			    if ($conn->query($sql1) && $conn->query($sql2)) {
			        $conn->commit();
			        $ttttmsg=array('status'=>'success', 'message'=>'Request category type Updated successfully');
			    } else {
			        // throw new Exception("One of the queries failed.");
			        $ttttmsg=array('status'=>'error', 'message'=>'One of the queries failed');
			    }
			} catch (Exception $e) {
			    $conn->rollback();
			    $ttttmsg=array('status'=>'error', 'message'=>'Updattion failed: ' . $e->getMessage());
			}
		}
	}else if(count($pppt)>1 && $requestcategoryid!="" && $requestcategoryid!="doubtfull"){
		$ttttmsg=array('status'=>'error', 'message'=>'More than 1 purpose exist in selected category type');
	}else if(count($pppt)==0 && $requestcategoryid!="" && $requestcategoryid!="doubtfull"){
		$conn->begin_transaction();
			try {
			    $sql3 = "UPDATE tbl_paymentrequest SET 
			                purpose_type = '$requestcategoryid', paymenttypestatus ='modify'
			            WHERE 
			                (tedit IS NULL OR tedit = '') AND 
			                companyid = '$companyid' AND 
			                subcompanyid = '$subcompanyid' AND 
			                projectid = '$projectid' AND 
			                request_no = '$requestno'";

			    if ($conn->query($sql3)) {
			        $conn->commit();
			        $ttttmsg=array('status'=>'success', 'message'=>'Request category type Updated successfully');
			    } else {
			        // throw new Exception("One of the queries failed.");
			        $ttttmsg=array('status'=>'error', 'message'=>'One of the queries failed');
			    }
			} catch (Exception $e) {
			    $conn->rollback();
			    $ttttmsg=array('status'=>'error', 'message'=>'Updattion failed: ' . $e->getMessage());
			}
	}else{
		if($requestcategoryid=="doubtfull"){
		 	$sql3 = "UPDATE tbl_paymentrequest SET paymenttypestatus = '$requestcategoryid'
		            WHERE 
		                (tedit IS NULL OR tedit = '') AND 
		                companyid = '$companyid' AND 
		                subcompanyid = '$subcompanyid' AND 
		                projectid = '$projectid' AND 
		                request_no = '$requestno'";
		    if ($conn->query($sql3)) {
		        $conn->commit();
		        $ttttmsg=array('status'=>'success', 'message'=>'Request category type status Updated successfully');
		    } else {
		        // throw new Exception("One of the queries failed.");
		        $ttttmsg=array('status'=>'error', 'message'=>'One of the queries failed');
		    }
		}else{
			$ttttmsg=array('status'=>'error', 'message'=>'missing purpose of selected category');
		}
	}
	echo json_encode($ttttmsg);
}
?>