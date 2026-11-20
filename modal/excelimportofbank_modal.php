<?php 
ini_set('display_startup_errors', 1);
 ini_set('display_errors', 1);
 error_reporting(E_ALL);
require('../connection.php');
include('../SimpleXLSX.php');
include('../SimpleXLS.php');
require('../functions/function_master.php');
require('../loginCell/functions/login_functions.php');
 $action=$_GET['action'];
if($action=="uploadbankstatementmanual"){
	$companyid = $_POST["companyid"];
	$subcompanyid = $_POST["subcompanyid"];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$accountid = $_POST['accountid'];
	$accountno = $_POST['accountno'];
	$bankid = $_POST['bankid'];
	$bankname = $_POST['bankname'];
	$rrr=getbanknamefromfile($conn,$_FILES['files'],$_POST["companyid"],$_POST["subcompanyid"],$_POST['projectid'],$accountid,$accountno,$bankid,$bankname,'','');

    $pdfreceiptpath ="";
    $excelreceiptpath="";
    if($rrr['status']=="Success"){
	    if (isset($_FILES['files']) && !$_FILES['files']['error']) 
		{
	        // Directory where the audio file will be saved
	        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/bankstatement/'.date('Ymd').'/';
	        
	        // Create the uploads directory if it doesn't exist
	        if (!is_dir($receiptpathDir)) {
	            mkdir($receiptpathDir, 0755, true);
	        }
	        
	        //file name
	        $temp = explode(".", $_FILES["files"]["name"]);
			$receiptname = round(microtime(true)) . '.' . end($temp);
	        $uploadFile = $receiptpathDir . $receiptname;
	        $excelreceiptpath = str_replace("../", "", $uploadFile);
	        // Move the uploaded file to the specified directory
	        move_uploaded_file($_FILES['files']['tmp_name'], $uploadFile);
	    }
	    
	    if($excelreceiptpath!=""){
		    $conn->query("INSERT INTO tbl_directuploads(companyid, subcompanyid, projectid, filpathexcel,filpathpdf, category,accountid,loginname) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$excelreceiptpath."','".$pdfreceiptpath."','bankstatement','".$rrr['accountid']."','".$loginname."')");
	    }
    }
	echo json_encode($rrr);
}else if($action=="uploadbankstatementpdf"){
	$companyid = $_POST["companyid"];
	$subcompanyid = $_POST["subcompanyid"];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$loginname = $_POST['loginname'];
	if (isset($_FILES['pdffiles']) && !$_FILES['pdffiles']['error']) 
	{
        $startdate = $_POST["startdate"];
        $enddate = $_POST["enddate"];
        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/bankstatement/'.date('Ymd').'/';
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($receiptpathDir)) {
            mkdir($receiptpathDir, 0755, true);
        }
        
        //file name
        $pdftemp = explode(".", $_FILES["pdffiles"]["name"]);
		$pdfreceiptname = round(microtime(true)) . '.' . end($pdftemp);
        $pdfuploadFile = $receiptpathDir . $pdfreceiptname;
        $pdfreceiptpath = str_replace("../", "", $pdfuploadFile);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['pdffiles']['tmp_name'], $pdfuploadFile);
	    if($pdfreceiptpath!=""){
	    	$qry = "INSERT INTO tbl_directuploads(companyid, subcompanyid, projectid, filpathpdf, category,accountid,datefrom,dateto,loginname) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$pdfreceiptpath."','bankstatement','".$accountid."','".$startdate."','".$enddate."','".$loginname."')";
		    if($conn->query($qry)){
		    	$retval=array("status"=>"success","message"=>"PDF Statement Uploaded Successfully.");
		    }
		    else{
		    	$retval=array("status"=>"error","message"=>"Error Uploading PDF Statement.");
		    }
		    echo json_encode($retval);
	    }
    }
}else if($action=="uploadbankstatementmanual_sync"){
	$companyid = $_POST["companyid"];
	$subcompanyid = $_POST["subcompanyid"];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$accountid = $_POST['accountid'];
	$filedbid = $_POST['filedbid'];
	$rooturl = $_POST['rooturl'];
	$bankid ="";
	$accountno ="";
	$bankname ="";
	$rrr=array();
	$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
	if(count($accc)>0){
		foreach ($accc as $accckey) {
			$bankid = $accckey['bankid'];
			$accountno = $accckey['accountno'];
			$bankname = $accckey['bankname'];
		}
	}else{
		$rrr=array('status'=>'Success', 'message'=>'Account selection is missing or Incorrect');
		echo json_encode($rrr);
		die();
	}
	$syncfilepath ="";
	if($filedbid){
		$ffl=getSyncedFilesByCondition($conn, $companyid, $subcompanyid, $projectid, 'id', $filedbid);
		if(count($ffl)>0){
			// $syncfilepath = $rooturl.$ffl[0]['filepath'];
			$syncfilepath = $ffl[0]['filepath'];
		}
		if($syncfilepath){
			$rrr=getbanknamefromfile($conn,$syncfilepath,$_POST["companyid"],$_POST["subcompanyid"],$_POST['projectid'],$accountid,$accountno,$bankid,$bankname,'','');
		}
	}
	// echo $syncfilepath;
	// print_r($rrr);
    $pdfreceiptpath ="";
    if($rrr['status']=="Success"){
	    if($syncfilepath!=""){
		    $conn->query("INSERT INTO tbl_directuploads(companyid, subcompanyid, projectid, filpathexcel,filpathpdf, category,accountid,loginname) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$syncfilepath."','".$pdfreceiptpath."','bankstatement','".$accountid."','".$loginname."')");
		    $conn->query("UPDATE tbl_synced_files SET digitized='1' WHERE id='".$filedbid."'");
	    }
    }
	echo json_encode($rrr);
}else if($action=="uploadbankstatementpdf_sync"){
	$companyid = $_POST["companyid"];
	$subcompanyid = $_POST["subcompanyid"];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$loginname = $_POST['loginname'];
	$filedbid = $_POST['filedbid'];
	$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
	if(count($accc)>0){
		foreach ($accc as $accckey) {
			$bankid = $accckey['bankid'];
			$accountno = $accckey['accountno'];
			$bankname = $accckey['bankname'];
		}
	}else{
		$rrr=array('status'=>'Success', 'message'=>'Account selection is missing or Incorrect');
		echo json_encode($rrr);
		die();
	}
	$syncfilepath ="";
	if($filedbid){
		$ffl=getSyncedFilesByCondition($conn, $companyid, $subcompanyid, $projectid, 'id', $filedbid);
		if(count($ffl)>0){
			$syncfilepath = $ffl[0]['filepath'];
		}
	}
	if($syncfilepath!=""){
    	$qry = "INSERT INTO tbl_directuploads(companyid, subcompanyid, projectid, filpathpdf, category,accountid,datefrom,dateto,loginname) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$syncfilepath."','bankstatement','".$accountid."','".$startdate."','".$enddate."','".$loginname."')";
	    if($conn->query($qry)){
	    	$conn->query("UPDATE tbl_synced_files SET digitized='1' WHERE id='".$filedbid."'");
	    	$retval=array("status"=>"success","message"=>"PDF Statement Uploaded Successfully.");
	    }
	    else{
	    	$retval=array("status"=>"error","message"=>"Error Uploading PDF Statement.");
	    }
	    echo json_encode($retval);
    }
}

function getbanknamefromfile($conn,$fname,$companyid,$subcompanyid,$projectid,$selaccountid,$selaccountno,$selbankid,$selbankname,$attachid,$emailid)
{
	$accountnumber="";
	$stringvalue="";
	$accountfinddonestr="";
	$bankname="";
	$AccountID="";
	$countryid="";
	$returninfo=array();
	$file_arrayX=array();
	$keys_array=array();
	$allkeys="";
	$headrrow="";
	$getfunctionname="";
	$findflag="";
	$bankid="";
	// if (!file_exists($fname) || !is_readable($fname)) {
	//     die("File not found or not readable: $fname");
	// }
	if(is_array($fname)){
		$file_ext = pathinfo($fname['name'],PATHINFO_EXTENSION);
		$file_path=$fname['tmp_name'];
	}else{
		$file_ext = pathinfo($fname,PATHINFO_EXTENSION);
		$file_path = $fname;
	}

    if ($objectX = $file_ext=="xlsx"?SimpleXLSX::parse($file_path):SimpleXLS::parse($file_path)) {
    	// echo "<br>1st level<br>";
		//  print_r($objectX->rows());
		//  die();
       foreach ($objectX->rows() as $worksheet) //$worksheet=excel row
		{
			// print_r($worksheet);
			for ($col=0; $col <count($worksheet) ; $col++) 
			{ 
		 		$stringvalue=$worksheet[$col];
				if ($findflag=="") 
				{
					$accountfinddonestr=getFindAccountnoinstring($conn,$selaccountid,$selaccountno,$selbankid,$selbankname,$stringvalue,$companyid,$subcompanyid,$projectid);
					if(stristr($accountfinddonestr,"@F")){
						$file_arrayX=explode("_", $accountfinddonestr);
						$findflag=$file_arrayX[0];
						$bankname=$file_arrayX[1];
						$accountnumber=$file_arrayX[2];
						$AccountID=$file_arrayX[3];
						$countryid=$file_arrayX[4];
						$bankid=$file_arrayX[5];
						$accounttype=$file_arrayX[6];
					   $keymapval=keymappingbyaccount($conn,$AccountID,$companyid,$subcompanyid,$projectid);
					   if(count($keymapval)>0){
					    		$rturninfo=uploadmapedbankstatementmanual($conn,$file_arrayX,$companyid,$subcompanyid,$projectid,$keymapval,$objectX->rows(),$attachid,$emailid);
					    	print_r($rturninfo);
					    	if(count($rturninfo)>0){
					    		if(strtolower($rturninfo['status'])=="success"){
					    			$returninfo=$rturninfo;
					    		}else{
					    			$returninfo=$rturninfo;
					    		}
					    	}else{
						    	$returninfo=array('status'=>'Error', 'message'=>'Mapping not found');
						    }
					    }else{
					    	$returninfo=array('status'=>'Error', 'message'=>'Bank statement did not mapped with our system');
					    }
					}else{
						$returninfo=array('status'=>'Error', 'message'=>'Attached bank statement account is not mapped with our project banks');
					}
				} 
			}
		}
		return $returninfo;
	}else {
		return ($file_ext=="xls")?SimpleXLS::parseError():SimpleXLSX::parseError();
	}
}
//11
function getFindAccountnoinstring($conn,$selaccountid,$selaccountno,$selbankid,$selbankname,$stringvalue,$companyid,$subcompanyid,$projectid)
{	
	$accountfinddone="";
	$countryid="";	
	if($stringvalue){
		if(stristr($stringvalue,$selaccountno)){
			$accountfinddone="@F_".$selbankname."_".$selaccountno."_".$selaccountid."_".$countryid."_".$selbankid."_Bank";
		}
	}	
	return $accountfinddone;
}

//22
function keymappingbyaccount($conn,$accountid,$companyid,$subcompanyid,$projectid)
{
	$keyquer="SELECT valuedate, postdate, description, chequeno, transactiondetails, branch,amount, creditamount, debitamount, drcr, balanceamount, hrow, sortbydata, payername, paytoname,inconsistencychk FROM tbl_bankstatement_import_keys WHERE ifnull(tedit,'')='' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and accountid='".$accountid."'";
	$resultkey=$conn->query($keyquer);
	$rrs=array();
	if($resultkey->num_rows>0)
	{
		$rowskey=$resultkey->fetch_assoc();
		$rrs=array('valuedate'=>$rowskey['valuedate'],'postdate'=>$rowskey['postdate'],'description'=>$rowskey['description'],'chequeno'=>$rowskey['chequeno'],'transactiondetails'=>$rowskey['transactiondetails'],'branch'=>$rowskey['branch'],'amount'=>$rowskey['amount'],'creditamount'=>$rowskey['creditamount'],'debitamount'=>$rowskey['debitamount'],'drcr'=>$rowskey['drcr'],'balanceamount'=>$rowskey['balanceamount'],'hrow'=>$rowskey['hrow'], 'sortbydata'=>$rowskey['sortbydata'], 'payername'=>$rowskey['payername'], 'paytoname'=>$rowskey['paytoname'],'inconsistencychk'=>$rowskey['inconsistencychk']);
	}
	return $rrs;
}

//33 Function correced By Rohit
function uploadmapedbankstatementmanual($conn,$accarr,$companyid,$subcompanyid,$projectid,$keymapval,$exceldata,$attachid,$emailid)
{
	// echo "==".$closingbalance;
	$dttheaderrow=detectheaderrow($exceldata) + 1;
	$excelimport=array();
	$functioninfo=array();
	
	$duplicate=0;
	$chk_valuedate="";
	$chk_postdate="";
	$chk_description="";
	$chk_chequeno="";
	$chk_chequeno="";
	$chk_branch="";
	$chk_creditamount="";
	$chk_debitamount="";
	$chk_balance="";
	$chk_openingbalance="";
	$paytoname ="";

	$bankname=$accarr[1];
	$accountnumber=$accarr[2];
	$accid=$accarr[3];
	$bankid=$accarr[5];
	$key_valuedate=$keymapval['valuedate'];
	$key_postdate=$keymapval['postdate'];
	$key_description=($keymapval['description']?$keymapval['description']:'');
	$key_chequeno=($keymapval['chequeno']?$keymapval['chequeno']:'');
	$key_transactiondetails=($keymapval['transactiondetails']?$keymapval['transactiondetails']:'');
	$key_branch=($keymapval['branch']?$keymapval['branch']:'');
	$key_amount=($keymapval['amount']?$keymapval['amount']:'');
	$key_creditamount=($keymapval['creditamount']?$keymapval['creditamount']:'');
	$key_debitamount=($keymapval['debitamount']?$keymapval['debitamount']:'');
	$key_drcr=($keymapval['drcr']?$keymapval['drcr']:'');
	$key_balanceamount=($keymapval['balanceamount']?$keymapval['balanceamount']:'');
	$key_hrow=$keymapval['hrow'];
	$key_sortbydata=$keymapval['sortbydata'];
	$key_payername=$keymapval['payername'];
	$key_paytoname=$keymapval['paytoname'];
	
	$key_inconsistencychk=$keymapval['inconsistencychk'];
	if($dttheaderrow!=$key_hrow){
		$functioninfo=array('status'=>'Error', 'message'=>'Incorrect header row of bankstatement, Please check and upload again with correct bank statement '.$dttheaderrow.'=='.$key_hrow,'accountid'=>$accid);
		return $functioninfo;
		die();
	}

	$i=0;
	$j=0;
	$calbalance=0;
	$firstbalance=0;
	$prvbal=0;
/*	print_r($exceldata);
	die();*/
	foreach ($exceldata as $excelrow) {
		if($i<$key_hrow){
			$i++;
		}else{
			$chk_balance=(!$excelrow[$key_balanceamount])?0:$excelrow[$key_balanceamount];
			if (stristr($chk_balance, "CR")) {
				$chk_balance = str_replace("CR","",$chk_balance);
			}else if (stristr($chk_balance, "DR")) {
				$chk_balance = str_replace("DR","",$chk_balance);
			}
			$proceed=0;
			if ($key_inconsistencychk){
				$proceed = ((trim($chk_balance)!=='')?1:0);
			}
			else{
				$proceed=1;
			}

			if ($proceed) {
				$chk_valuedate=$key_valuedate>=0?removeQuotes($excelrow[$key_valuedate]):'';
			/*	 echo $chk_valuedate;
				 echo " ".detectDateTimeFormat(trim($chk_valuedate));
				 echo "x ".isValidDate(trim($chk_valuedate),detectDateTimeFormat(trim($chk_valuedate)));*/
//Ammended by Rohit Safaya on 01-05-2025
				 
				 if(!trim($chk_valuedate)){
				 	$proceed=0;
				 }
				 else{
					if(!isValidDate(trim($chk_valuedate),detectDateTimeFormat(trim($chk_valuedate)))){
						$proceed=0;
					}
					else{
						//echo $chk_valuedate."\n";
						$chk_valuedate=convertDateToYMD(trim($chk_valuedate));
						$proceed = 1;
					}
				} 
			}
			//echo $j;
			 //echo trim($chk_valuedate);//."S".detectDateTimeFormat(trim($chk_valuedate));
			 //echo " ".$proceed;
			 //die();
			/* if($j==2)
			 die();*/
			if ($proceed) {
				if(trim($chk_valuedate) && !isValidDate(trim($chk_valuedate),detectDateTimeFormat(trim($chk_valuedate)))){
					$chk_valuedate=excelDateToDateTime(trim($chk_valuedate))->format('Y-m-d H:i:s');
				}
				$chk_branch=$key_branch>=0?$excelrow[$key_branch]:'';
				$chk_description=$key_description>=0?$excelrow[$key_description]:($key_transactiondetails?$excelrow[$key_transactiondetails]:'');
				$chk_chequeno=$key_chequeno>=0?removeQuotes($excelrow[$key_chequeno]):'';
				if($key_drcr){
					if(strtolower($excelrow[$key_drcr])=="cr"||strtolower($excelrow[$key_drcr])=="cr."){
						$chk_creditamount=(!$excelrow[$key_amount])?0:$excelrow[$key_amount];
						$chk_debitamount=0;
					}else if(strtolower($excelrow[$key_drcr])=="dr"||strtolower($excelrow[$key_drcr])=="dr."){
						$chk_creditamount=0;
						$chk_debitamount=(!$excelrow[$key_amount])?0:$excelrow[$key_amount];
					}
				}else if($key_creditamount && $key_debitamount){
					$chk_creditamount=(!$excelrow[$key_creditamount])?0:$excelrow[$key_creditamount];
					$chk_debitamount=(!$excelrow[$key_debitamount])?0:$excelrow[$key_debitamount];
				}
				else{
					if($excelrow[$key_amount]>0){
						$chk_creditamount=(!$excelrow[$key_amount])?0:$excelrow[$key_amount];
						$chk_debitamount=0;
					}else if($excelrow[$key_amount]<0){
						$chk_creditamount=0;
						$chk_debitamount=abs((!$excelrow[$key_amount])?0:$excelrow[$key_amount]);
					}
				}

				$paytoname ="";
				if($key_payername){
					$paytoname = (!$excelrow[$key_payername])?'':$excelrow[$key_payername];
				}

				if($key_paytoname){
					$paytoname = (!$excelrow[$key_paytoname])?'':$excelrow[$key_paytoname];
				}

				$excelimport[]=array('companyid'=>$companyid, 'subcompanyid'=>$subcompanyid,'projectid'=>$projectid, 'accountid'=>$accid, 'accountno'=>$accountnumber,'bankname'=>$bankname, 'bankid'=>$bankid,'chk_valuedate'=>$chk_valuedate, 'chk_postdate'=>$chk_postdate, 'chk_description'=>$chk_description, 'chk_chequeno'=>$chk_chequeno, 'chk_chequeno'=>$chk_chequeno, 'chk_branch'=>$chk_branch, 'chk_creditamount'=>str_replace(",","",$chk_creditamount), 'chk_debitamount'=>str_replace(",","",$chk_debitamount), 'chk_balance'=>str_replace(",","",$chk_balance), 'paytoname'=>$paytoname);
			}
			$j++;
		}
	}
/*	 print_r($excelimport);
	 die();*/
	$opbalentery=array();
	$insertquery="Insert into tbl_bank_check(companyid,subcompanyid,projectid,bankid,account_id,accountno,bankname,valuedate,postdate,remitterbranch,description,chequeno,debitamount,creditamount,balance,debitamountnew,creditamountnew,balancenew,attach_id,paytoname,verify_insert)values";
	$insertvalues="";
	$newentry=0;
	$duplicate=0;
		if(count($excelimport)){
			$excelimportnew = getnonEmptyRows($excelimport);
			foreach ($excelimportnew as $exlrow) {
					if($insertvalues){
						$insertvalues.=",('".$exlrow['companyid']."','".$exlrow['subcompanyid']."','".$exlrow['projectid']."','".$exlrow['bankid']."','".$exlrow['accountid']."','".$exlrow['accountno']."','".$exlrow['bankname']."','".trim(addslashes($exlrow['chk_valuedate']??''))."','".trim(addslashes($exlrow['chk_postdate']??''))."','".trim(addslashes($exlrow['chk_branch']))."','".trim(addslashes($exlrow['chk_description']??''))."','".trim(addslashes($exlrow['chk_chequeno']??''))."','".trim(addslashes($exlrow['chk_debitamount']??''))."','".trim(addslashes($exlrow['chk_creditamount']??''))."','".trim(addslashes($exlrow['chk_balance']??''))."','".trim(addslashes($exlrow['chk_debitamount']??''))."','".trim(addslashes($exlrow['chk_creditamount']??''))."','".trim(addslashes($exlrow['chk_balance']??''))."','".trim(addslashes($attachid??''))."','".trim(addslashes($exlrow['paytoname']))."','unchecked')";
					}else{
							$insertvalues="('".$exlrow['companyid']."','".$exlrow['subcompanyid']."','".$exlrow['projectid']."','".$exlrow['bankid']."','".$exlrow['accountid']."','".$exlrow['accountno']."','".trim(addslashes($exlrow['bankname']))."','".trim(addslashes($exlrow['chk_valuedate']??''))."','".trim(addslashes($exlrow['chk_postdate']??''))."','".trim(addslashes($exlrow['chk_branch']??''))."','".trim(addslashes($exlrow['chk_description']??''))."','".trim(addslashes($exlrow['chk_chequeno']??''))."','".trim(addslashes($exlrow['chk_debitamount']??''))."','".trim(addslashes($exlrow['chk_creditamount']??''))."','".trim(addslashes($exlrow['chk_balance']??''))."','".trim(addslashes($exlrow['chk_debitamount']??''))."','".trim(addslashes($exlrow['chk_creditamount']??''))."','".trim(addslashes($exlrow['chk_balance']??''))."','".trim(addslashes($attachid??''))."','".trim(addslashes($exlrow['paytoname']))."','unchecked')";
					}
					$newentry++;
			}
		}else{
			$functioninfo=array('status'=>'Error', 'message'=>'No rows to import, Please try again with another sheet','accountid'=>$accid);
		}
		if($insertvalues){
			$qry=$insertquery.$insertvalues;
			$conn->query($qry);
			if(mysqli_affected_rows($conn)>0){
				if($attachid && $emailid){
					$qres = $conn->query("update emailtodb_attach set filestatus='1',account_id='".$accid."' where ID={$attachid}");
					if($qres){
						$catupdate = "update emailtodb_email set email_digitizationstatus='1' where ID='{$emailid}' and not exists (select ID from emailtodb_attach where emailtodb_attach.IDEmail='{$emailid}' and filestatus is null)";
						$cat_result=$conn->query($catupdate);
					}
				}
				if($duplicate>0 && $newentry>0){
					$functioninfo=array('status'=>'Success', 'message'=>'Update '.$newentry.' and ignore duplicate '.$duplicate.' entry of Bank statement successfully','accountid'=>$accid);
				}else if($duplicate==0 && $newentry>0){
					$functioninfo=array('status'=>'Success', 'message'=>'Update '.$newentry.' entry of Bank statement successfully','accountid'=>$accid);
				}else if($duplicate>0 && $newentry==0){
					$functioninfo=array('status'=>'Error', 'message'=>'No new entry available for update','accountid'=>$accid);
				}else{
					$functioninfo=array('status'=>'Error', 'message'=>'Something going the wrong way, Please try again with correct data sheet','accountid'=>$accid);
				}
			}
	}
	else{
		$functioninfo=array('status'=>'Error', 'message'=>'No rows to import, Please try again with another sheet','accountid'=>$accid);
	}	
	return $functioninfo;
}
?>