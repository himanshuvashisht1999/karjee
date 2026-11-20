<?php 
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../loginCell/functions/login_functions.php');
include('../SimpleXLSX.php');
include('../SimpleXLS.php');
$action = $_GET['action'];
if($action=="uploadexpensesheet"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$keymapval=keymapping_byexpensesheet($conn,$companyid,$subcompanyid,$projectid);
	$returninfo=array();
    if(count($keymapval)>0){
    	$key_accountno=$keymapval['accountno'];
		$key_date=$keymapval['date'];
		$key_name=$keymapval['name'];
		$key_amount=$keymapval['amount'];
		$key_chequeno=($keymapval['chequeno']?$keymapval['chequeno']:'');
		$key_description=($keymapval['description']?$keymapval['description']:'');
		$key_purpose=($keymapval['purpose']?$keymapval['purpose']:'');
		$key_purposecode=($keymapval['purposecode']?$keymapval['purposecode']:'');
		$key_hrow=($keymapval['hrow']?$keymapval['hrow']:'');
		
		$key_payfrombankname=$keymapval['payfrombankname'];
		$key_invoiceno=$keymapval['invoiceno'];
		$key_invoicedate=$keymapval['invoicedate'];
		$key_invoiceamount=($keymapval['invoiceamount']?$keymapval['invoiceamount']:0);
		$key_tdsamount=$keymapval['tdsamount'];
		$key_taxableamount=$keymapval['taxableamount'];
		$key_cgstamount=$keymapval['cgstamount'];
		$key_sgstamount=$keymapval['sgstamount'];
		$key_igstamount=$keymapval['igstamount'];

    	if (isset($_FILES['files']) && !empty($_FILES['files'])) 
		{
			$file_ext = pathinfo($_FILES['files']['name'],PATHINFO_EXTENSION);
			$file_name = pathinfo($actual_attach,PATHINFO_FILENAME);
			if($xlsx = $file_ext=="xlsx"?SimpleXLSX::parse( $_FILES['files']['tmp_name']):SimpleXLS::parse( $_FILES['files']['tmp_name'] ) ) {			
				$exceldata = getnonEmptyRows($xlsx->rows());
				$newexceldata = array();
				$isvaliddate=0;
				for($row=$key_hrow;$row<count($exceldata);$row++){
					$name = addslashes(preg_replace('/\s+/', ' ', $exceldata[$row][$key_name]));
					$amount = preg_replace('/\s+/', ' ', removeQuotes($exceldata[$row][$key_amount]));
					$date = preg_replace('/\s+/', ' ', $exceldata[$row][$key_date]);
					$debitaccountno = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_accountno])?$exceldata[$row][$key_accountno]:''));
					$chequeno = preg_replace('/\s+/', ' ', removeQuotes($exceldata[$row][$key_chequeno]));
					$purpose = addslashes(preg_replace('/\s+/', ' ', $exceldata[$row][$key_purpose]));
					$purposecode = preg_replace('/\s+/', ' ', $exceldata[$row][$key_purposecode]);
					$Description = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_description])?$exceldata[$row][$key_description]:""));

					$payfrombankname = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_payfrombankname])?$exceldata[$row][$key_payfrombankname]:""));
					$invoiceno = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_invoiceno])?$exceldata[$row][$key_invoiceno]:""));
					$invoicedate = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_invoicedate])?$exceldata[$row][$key_invoicedate]:""));
					$invoiceamount = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_invoiceamount])?$exceldata[$row][$key_invoiceamount]:""));
					$tdsamount = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_tdsamount])?$exceldata[$row][$key_tdsamount]:""));
					$taxableamount = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_taxableamount])?$exceldata[$row][$key_taxableamount]:""));
					$cgstamount = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_cgstamount])?$exceldata[$row][$key_cgstamount]:""));
					$sgstamount = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_sgstamount])?$exceldata[$row][$key_sgstamount]:""));
					$igstamount = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_igstamount])?$exceldata[$row][$key_igstamount]:""));

					$invoiceamount = $invoiceamount<=0?$amount:$invoiceamount;

					if($date){
						if(isValidDate(date('Y-m-d', strtotime($date)), 'Y-m-d')==1){
							$isvaliddate = 1;
						}else{
							$isvaliddate++;
						}
					}else{
						$isvaliddate++;
					}
					
					$newexceldata[]=array("Date"=>$date,"Name"=>$name,"Amount"=>$amount,"ChequeNo"=>$chequeno,"Description"=>$Description,"Purpose"=>$purpose,"PurposeCode"=>$purposecode, "debitaccountno"=>$debitaccountno,'payfrombankname'=>$payfrombankname, 'invoiceno'=>$invoiceno, 'invoicedate'=>$invoicedate, 'invoiceamount'=>$invoiceamount, 'tdsamount'=>$tdsamount, 'taxableamount'=>$taxableamount, 'cgstamount'=>$cgstamount, 'sgstamount'=>$sgstamount, 'igstamount'=>$igstamount);
					
				}
				if($isvaliddate>0){
					$returninfo=array('status'=>'Error', 'message'=>'Invalid/Missing Date: All entries should have a date.');
					echo json_encode($returninfo);
					die();
				}
				$insqry = "insert ignore into tbl_expencesinexcelsheet (company_id,subcompany_id,project_id,date,payto,amount,total_amount,description,chequeno,purpose,purpose_code,provided_purpose,provided_code,att_id,accountno, bankname,invoiceno,invoicedate,tds_amount,cgst,sgst,igst)values";
				$insvalues="";
				foreach($newexceldata as $excelrow){
					$descr = addslashes(preg_replace('/\s+/', ' ', $excelrow['Description']));
					$name = addslashes(preg_replace('/\s+/', ' ', $excelrow['Name']));
					$amount = preg_replace('/\s+/', ' ', removeQuotes($excelrow['Amount']));
					$date = convertDateToYMD(preg_replace('/\s+/', ' ', $excelrow['Date']));
					$chequeno = preg_replace('/\s+/', ' ', removeQuotes($excelrow['ChequeNo']));
					$purpose = addslashes(preg_replace('/\s+/', ' ', $excelrow['Purpose']));
					$purposecode = preg_replace('/\s+/', ' ', $excelrow['PurposeCode']);
					$debitaccountno = preg_replace('/\s+/', ' ', $excelrow['debitaccountno']);

					$payfrombankname = preg_replace('/\s+/', ' ', $excelrow['payfrombankname']);
					$invoiceno = preg_replace('/\s+/', ' ', $excelrow['invoiceno']);
					$invoicedate = preg_replace('/\s+/', ' ', $excelrow['invoicedate']);
					$invoiceamount = preg_replace('/\s+/', ' ', $excelrow['invoiceamount']);
					$tdsamount = preg_replace('/\s+/', ' ', $excelrow['tdsamount']);
					$taxableamount = preg_replace('/\s+/', ' ', $excelrow['taxableamount']);
					$cgstamount = preg_replace('/\s+/', ' ', $excelrow['cgstamount']);
					$sgstamount = preg_replace('/\s+/', ' ', $excelrow['sgstamount']);
					$igstamount = preg_replace('/\s+/', ' ', $excelrow['igstamount']);
					if($amount){
						if($insvalues==""){
							$insvalues="('{$companyid}','{$subcompanyid}','{$projectid}','{$date}','{$name}','{$invoiceamount}','{$amount}','{$descr}','{$chequeno}','{$purpose}','{$purposecode}','{$purpose}','{$purposecode}','{$attachid}','{$debitaccountno}','{$payfrombankname}','{$invoiceno}','{$invoicedate}','{$tdsamount}','{$cgstamount}','{$sgstamount}','{$igstamount}')";
						}
						else{
							$insvalues=$insvalues.","."('{$companyid}','{$subcompanyid}','{$projectid}','{$date}','{$name}','{$invoiceamount}','{$amount}','{$descr}','{$chequeno}','{$purpose}','{$purposecode}','{$purpose}','{$purposecode}','{$attachid}','{$debitaccountno}','{$payfrombankname}','{$invoiceno}','{$invoicedate}','{$tdsamount}','{$cgstamount}','{$sgstamount}','{$igstamount}')";
						}
					}
				}
			 	if($insvalues){
					$insertqry = $insqry.$insvalues; 
					if($conn->query($insertqry)){
						// $retval="done";
						$returninfo=array('status'=>'Success', 'message'=>'Expense sheet uploaded');
						// Directory where the audio file will be saved
				        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/expensesheet/'.date('Ymd').'/';
				        
				        // Create the uploads directory if it doesn't exist
				        if (!is_dir($receiptpathDir)) {
				            mkdir($receiptpathDir, 0755, true);
				        }
				        
				        //file name
				        $temp = explode(".", $_FILES["files"]["name"]);
						$receiptname = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
				        $uploadFile = $receiptpathDir . $receiptname;
				        $excelreceiptpath = str_replace("../", "", $uploadFile);
				        // Move the uploaded file to the specified directory
				        move_uploaded_file($_FILES['files']['tmp_name'], $uploadFile);
						if($excelreceiptpath!=""){
						    $conn->query("INSERT INTO tbl_directuploads(companyid, subcompanyid, projectid, filpathexcel,category,loginname) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$excelreceiptpath."','expensestatement','".$loginname."')");
					    }
						if($attachid && $emailid){
							$qres = $conn->query("update emailtodb_attach set filestatus='1', reformatted_filename = 'formatted_attachments/{$_FILES['file']['name']}' where ID={$attachid}");
							if($qres){
								$catupdate = "update emailtodb_email set email_digitizationstatus='1' where ID='{$emailid}' and not exists (select ID from emailtodb_attach where emailtodb_attach.IDEmail='{$emailid}' and filestatus is null)";
								$cat_result=$conn->query($catupdate);
							}
						}
					}
				}
			}else{
				$err_msg=($file_ext=="xlsx"?SimpleXLS::parseError():SimpleXLSX::parseError());
				$returninfo=array('status'=>'Error', 'message'=>$err_msg);
			}
		}
    }else{
    	$returninfo=array('status'=>'Error', 'message'=>'Mapping not found');
    }
	
	echo json_encode($returninfo);
}else if($action=="uploaddepositsheet"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$keymapval=keymapping_bydepositsheet($conn,$companyid,$subcompanyid,$projectid);
	$returninfo=array();
    if(count($keymapval)>0){
    	$key_accountno=$keymapval['accountno'];
		$key_date=$keymapval['date'];
		$key_name=$keymapval['name'];
		$key_amount=$keymapval['amount'];
		$key_chequeno=($keymapval['chequeno']?$keymapval['chequeno']:'');
		$key_description=($keymapval['description']?$keymapval['description']:'');
		$key_purpose=($keymapval['purpose']?$keymapval['purpose']:'');
		$key_purposecode=($keymapval['purposecode']?$keymapval['purposecode']:'');
		$key_hrow=($keymapval['hrow']?$keymapval['hrow']:'');

    	if (isset($_FILES['files']) && !empty($_FILES['files'])) 
		{
			$file_ext = pathinfo($_FILES['files']['name'],PATHINFO_EXTENSION);
			$file_name = pathinfo($actual_attach,PATHINFO_FILENAME);
			if($xlsx = $file_ext=="xlsx"?SimpleXLSX::parse( $_FILES['files']['tmp_name']):SimpleXLS::parse( $_FILES['files']['tmp_name'] ) ) {			
				$exceldata = getnonEmptyRows($xlsx->rows());
				$newexceldata = array();
				for($row=$key_hrow;$row<count($exceldata);$row++){
					$name = addslashes(preg_replace('/\s+/', ' ', $exceldata[$row][$key_name]));
					$amount = preg_replace('/\s+/', ' ', removeQuotes($exceldata[$row][$key_amount]));
					$date = preg_replace('/\s+/', ' ', $exceldata[$row][$key_date]);
					$debitaccountno = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_accountno])?$exceldata[$row][$key_accountno]:''));
					$chequeno = preg_replace('/\s+/', ' ', removeQuotes($exceldata[$row][$key_chequeno]));
					$purpose = addslashes(preg_replace('/\s+/', ' ', $exceldata[$row][$key_purpose]));
					$purposecode = preg_replace('/\s+/', ' ', $exceldata[$row][$key_purposecode]);
					$Description = preg_replace('/\s+/', ' ', (isset($exceldata[$row][$key_description])?$exceldata[$row][$key_description]:""));
					
					$newexceldata[]=array("Date"=>$date,"Name"=>$name,"Amount"=>$amount,"ChequeNo"=>$chequeno,"Description"=>$Description,"Purpose"=>$purpose,"PurposeCode"=>$purposecode, "debitaccountno"=>$debitaccountno);
					
				}
				$insqry = "insert ignore into tbl_depositsinexcelsheet (company_id,subcompany_id,project_id,date,payto,amount,total_amount,description,chequeno,purpose,purpose_code,provided_purpose,provided_code,att_id,accountno)values";
				$insvalues="";
				foreach($newexceldata as $excelrow){
					$descr = addslashes(preg_replace('/\s+/', ' ', $excelrow['Description']));
					$name = addslashes(preg_replace('/\s+/', ' ', $excelrow['Name']));
					$amount = preg_replace('/\s+/', ' ', removeQuotes($excelrow['Amount']));
					$date = convertDateToYMD(preg_replace('/\s+/', ' ', $excelrow['Date']));
					$chequeno = preg_replace('/\s+/', ' ', removeQuotes($excelrow['ChequeNo']));
					$purpose = addslashes(preg_replace('/\s+/', ' ', $excelrow['Purpose']));
					$purposecode = preg_replace('/\s+/', ' ', $excelrow['PurposeCode']);
					$debitaccountno = preg_replace('/\s+/', ' ', $excelrow['debitaccountno']);
					if($amount){
						if($insvalues==""){
							$insvalues="('{$companyid}','{$subcompanyid}','{$projectid}','{$date}','{$name}','{$amount}','{$amount}','{$descr}','{$chequeno}','{$purpose}','{$purposecode}','{$purpose}','{$purposecode}','{$attachid}','{$debitaccountno}')";
						}
						else{
							$insvalues=$insvalues.","."('{$companyid}','{$subcompanyid}','{$projectid}','{$date}','{$name}','{$amount}','{$amount}','{$descr}','{$chequeno}','{$purpose}','{$purposecode}','{$purpose}','{$purposecode}','{$attachid}','{$debitaccountno}')";
						}
					}
				}
			 	if($insvalues){
					$insertqry = $insqry.$insvalues; 
					if($conn->query($insertqry)){
						$uppur = $conn->query("update tbl_depositsinexcelsheet e left join  purpose_code_master_credit p on e.provided_code=p.provided_code and p.projectid=e.project_id set e.purpose_code=p.purposecode,e.purpose=p.purpose where ifnull(e.purpose_code,'')='' and ifnull(e.provided_code,'')<>'' and ifnull(p.tedit,'')='' and e.project_id='".$projectid."' and  e.company_id='".$companyid."' and e.subcompany_id='".$subcompanyid."'");
						// $retval="done";
						$returninfo=array('status'=>'Success', 'message'=>'Deposits sheet uploaded');
						// Directory where the audio file will be saved
				        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/depositsheet/'.date('Ymd').'/';
				        
				        // Create the uploads directory if it doesn't exist
				        if (!is_dir($receiptpathDir)) {
				            mkdir($receiptpathDir, 0755, true);
				        }
				        
				        //file name
				        $temp = explode(".", $_FILES["files"]["name"]);
						$receiptname = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
				        $uploadFile = $receiptpathDir . $receiptname;
				        $excelreceiptpath = str_replace("../", "", $uploadFile);
				        // Move the uploaded file to the specified directory
				        move_uploaded_file($_FILES['files']['tmp_name'], $uploadFile);
						if($excelreceiptpath!=""){
						    $conn->query("INSERT INTO tbl_directuploads(companyid, subcompanyid, projectid, filpathexcel,category,loginname) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$excelreceiptpath."','depositstatement','".$loginname."')");
					    }
						if($attachid && $emailid){
							$qres = $conn->query("update emailtodb_attach set filestatus='1', reformatted_filename = 'formatted_attachments/{$_FILES['file']['name']}' where ID={$attachid}");
							if($qres){
								$catupdate = "update emailtodb_email set email_digitizationstatus='1' where ID='{$emailid}' and not exists (select ID from emailtodb_attach where emailtodb_attach.IDEmail='{$emailid}' and filestatus is null)";
								$cat_result=$conn->query($catupdate);
							}
						}
					}
				}
			}else{
				$err_msg=($file_ext=="xlsx"?SimpleXLS::parseError():SimpleXLSX::parseError());
				$returninfo=array('status'=>'Error', 'message'=>$err_msg);
			}
		}
    }else{
    	$returninfo=array('status'=>'Error', 'message'=>'Mapping not found');
    }
	
	echo json_encode($returninfo);
}else if($action=="uploadnewlogindetails_excelfile"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	// excelFile
	$returninfo=array();
	if (isset($_FILES['excelFile']) && !empty($_FILES['excelFile']))
	{
		$file_ext = pathinfo($_FILES['excelFile']['name'],PATHINFO_EXTENSION);
		$file_name = pathinfo($actual_attach,PATHINFO_FILENAME);
		if($xlsx = $file_ext=="xlsx"?SimpleXLSX::parse( $_FILES['excelFile']['tmp_name']):SimpleXLS::parse( $_FILES['excelFile']['tmp_name'] ) ) {			
			$exceldata = $xlsx->rows();
			$header = detectHeaderRowFromArray($xlsx->rows());
			// echo "header: ".$header.":: excacout: ".count($exceldata);
			if($header<0){
				die("Error: This sheet contains no valid header. Please correct the sheet and then try again.");
			}

			$newexceldata = array();
			$j=($header+1);
			for($row=$j;$row<count($exceldata);$row++){
				$loginid = $exceldata[$row][0]??"";
				$loginpassword = $exceldata[$row][1]??"";
				$username = $exceldata[$row][2]??"";
				$usermobile = $exceldata[$row][3]??"";
				$useremailaddress = addslashes(preg_replace('/\s+/', ' ', $exceldata[$row][4]));
				
				$newexceldata[]=array("loginid"=>$loginid,"loginpassword"=>$loginpassword,"username"=>$username,"usermobile"=>$usermobile,"useremailaddress"=>$useremailaddress);
				
			}			
			$userqry = "INSERT INTO login_user_master(loginid, loginpassword, loginname, mobileno, emailid, loginroleid, loginrolename, isActive) VALUES";
			$ulgq="";
			foreach($newexceldata as $excelrow){
				if($excelrow['loginid'] && $excelrow['loginpassword'] && $excelrow['username']){
					$sscq=$conn->query("SELECT sno FROM login_user_master where loginid='".$loginid."'");
					if($sscq->num_rows>0){
						$returninfo[]=array('status'=>'Already Exist', 'message'=>'Login id already exist, Please tyr agnain with other login id', 'loginid'=>$loginid, 'loginpassword'=>$loginpassword, 'username'=>$username, 'usermobile'=>$usermobile, 'useremailaddress'=>$useremailaddress);
					}else{
						$loginid = addslashes(preg_replace('/\s+/', ' ', $excelrow['loginid']));
						$loginpassword = addslashes(preg_replace('/\s+/', ' ', $excelrow['loginpassword']));
						$username = preg_replace('/\s+/', ' ', removeQuotes($excelrow['username']));
						$usermobile = convertDateToYMD(preg_replace('/\s+/', ' ', $excelrow['usermobile']));
						$useremailaddress = preg_replace('/\s+/', ' ', removeQuotes($excelrow['useremailaddress']));
						$loginroleid=4;
						$loginrolename="User";
						$isactive="yes";
						if($ulgq==""){
							$ulgq="('{$loginid}','{$loginpassword}','{$username}','{$usermobile}','{$useremailaddress}','{$loginroleid}','{$loginrolename}','{$isactive}')";
						}
						else{
							$ulgq=$ulgq.","."('{$loginid}','{$loginpassword}','{$username}','{$usermobile}','{$useremailaddress}','{$loginroleid}','{$loginrolename}','{$isactive}')";
						}
						$returninfo[]=array('status'=>'Success', 'message'=>'Login details uploaded successfully', 'loginid'=>$loginid, 'loginpassword'=>$loginpassword, 'username'=>$username, 'usermobile'=>$usermobile, 'useremailaddress'=>$useremailaddress);
						//add authorized project
						$cq=$conn->query("SELECT projectid from login_authorized_projects where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and loginid='".$loginid."'");
						if($cq->num_rows>0){
							$conn->query("UPDATE login_authorized_projects SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and loginid='".$loginid."'");
						}else{
							$conn->query("INSERT INTO login_authorized_projects(companyid,subcompanyid,projectid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$loginid."', 'active')");
						}

						//authorized module by default
						$cq=$conn->query("SELECT moduleid from login_authorized_modules where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and moduleid='0' and loginid='".$loginid."'");
						if($cq->num_rows>0){
							$conn->query("UPDATE login_authorized_modules SET status='active' where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and moduleid='0' and loginid='".$loginid."'");
						}else{
							if($aupmdlkey['moduleid']){
								$conn->query("INSERT INTO login_authorized_modules(companyid,subcompanyid,projectid,moduleid,loginid,status) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '0', '".$loginid."', 'active')");
							}
						}
					}
				}else{
					$returninfo[]=array('status'=>'Warning', 'message'=>'Login details did not upload, due to some details are missing (login id/login passwore/ user name)', 'loginid'=>$loginid, 'loginpassword'=>$loginpassword, 'username'=>$username, 'usermobile'=>$usermobile, 'useremailaddress'=>$useremailaddress);
				}
			}
		 	if($ulgq){
				$userlginsertqry = $userqry.$ulgq; 
				if($conn->query($userlginsertqry)){
					// Directory where the audio file will be saved
			        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/logininfosheet/'.date('Ymd').'/';
			        
			        // Create the uploads directory if it doesn't exist
			        if (!is_dir($receiptpathDir)) {
			            mkdir($receiptpathDir, 0755, true);
			        }
			        
			        //file name
			        $temp = explode(".", $_FILES["excelFile"]["name"]);
					$receiptname = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
			        $uploadFile = $receiptpathDir . $receiptname;
			        $excelreceiptpath = str_replace("../", "", $uploadFile);
			        // Move the uploaded file to the specified directory
			        move_uploaded_file($_FILES['excelFile']['tmp_name'], $uploadFile);
					
				}
			}
		}else{
			$err_msg=($file_ext=="xlsx"?SimpleXLS::parseError():SimpleXLSX::parseError());
			$returninfo[]=array('status'=>'Error', 'message'=>$err_msg, 'loginid'=>'', 'loginpassword'=>'', 'username'=>'', 'usermobile'=>'', 'useremailaddress'=>'');
		}
	}
	echo json_encode($returninfo);
}
?>