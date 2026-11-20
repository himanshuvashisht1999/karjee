<?php
function savesupplierpaymentauthorization($mconn,$companyid,$subcompanyid,$projectid,$paytoname,$bankname,$accountnumber,$bankifsccode,$panno,$gstno,$mobileno,$emailaddress,$addedby,$paytoid=null){
	$restimp=array();
	if($paytoid && $paytoid>0){
		$paytoid=$paytoid;
	}else{
		$ssp=savetodbnewpayto_details($mconn,$paytoname,$accountnumber,$bankname,$bankifsccode,"","","",$companyid,$subcompanyid,$projectid,$mobileno,$emailaddress,$addedby,$paytoid);
		if(count($ssp)>0){
			foreach ($ssp as $sspkey) {
				if($sspkey['status']=="Success" || $sspkey['status']=="alreadyupdated"){
					$paytoid =$sspkey['paytoid']; 
				}
			}
		}else{
			$restimp=array('status'=>'Error', 'message'=>$paytoname.' did not exist in our payto master. So somthing wrongh', 'paytolist'=>'');
			return $restimp;
			die();
		}
	}
	$chksupplier=getsupplierpaymentpayto($mconn,$companyid,$subcompanyid,$projectid,"paytoid",$paytoid);
	if(count($chksupplier)>0){
		foreach ($chksupplier as $iikey) {
			$paytoid=$iikey['paytoid'];
		}
		$restimp=array('status'=>'Alreadyexist', 'message'=>' Supplier already exist in our master.', 'paytoid'=>$paytoid);		
	}else{
		if($accountnumber){
			$mconn->query("INSERT INTO supplier_authorizedperson(companyid,subcompanyid,projectid,paytoid,paytoname,bankname,accountno,ifsccode,panno,gstno,mobileno,emailaddress,loginby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".addslashes(trim($paytoname))."', '".addslashes(trim($bankname))."','".$accountnumber."','".$bankifsccode."','".$panno."','".$gstno."','".$mobileno."','".$emailaddress."','".addslashes(trim($addedby))."')");
			if(mysqli_affected_rows($mconn)>0){
				$mconn->query("UPDATE tbl_payto_master SET uploadflag=null, taxid='".$gstno."', taxtype='GST', bankname='".addslashes(trim($bankname))."', accountno='".$accountnumber."', ifsccode='".$bankifsccode."' WHERE paytoid='".$paytoid."' and cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."'");
				$restimp=array('status'=>'Success', 'message'=>'Supplier authorization save successfully', 'paytolist'=>'', 'paytoid'=>$paytoid);
			}else{
				$restimp=array('status'=>'Error', 'message'=>'Supplier authorization did not save, due to somthing wrong.', 'paytolist'=>'', 'paytoid'=>'');
			}
		}else{
			$restimp=array('status'=>'Error', 'message'=>'Supplier authorization did not save, due to Supplier bank details not available.', 'paytolist'=>'', 'paytoid'=>'');
		}
	}
	return $restimp;
}

function savecashauthorization($mconn,$companyid,$subcompanyid,$projectid,$paytoname,$bankname,$accountnumber,$bankifsccode,$paytomobileno,$paytoemailaddress,$addedby,$paytoid=null){
	$restimp=array();
	if($paytoid && $paytoid>0){
		$paytoid=$paytoid;
	}else{
		$ssp=savetodbnewpayto_details($mconn,$paytoname,$accountnumber,$bankname,$bankifsccode,"","","",$companyid,$subcompanyid,$projectid,$paytomobileno,$paytoemailaddress,$addedby,$paytoid);
		if(count($ssp)>0){
			foreach ($ssp as $sspkey) {
				if($sspkey['status']=="Success" || $sspkey['status']=="alreadyupdated"){
					$paytoid =$sspkey['paytoid']; 
				}
			}
		}else{
			$restimp=array('status'=>'Error', 'message'=>$paytoname.' did not exist in our payto master. So somthing wrongh', 'paytolist'=>'');
			return $restimp;
		}
	}
	$chkimpaccount=getcashpaymentpayto($mconn,$companyid,$subcompanyid,$projectid,"payto",$paytoname);

	if(count($chkimpaccount)>0){
		foreach ($chkimpaccount as $iikey) {
			$paytoid=$iikey['paytoid'];
		}
		$restimp=array('status'=>'Alreadyexist', 'message'=>' Cash payto already exist in cash authorization master.', 'paytolist'=>$paytoid);		
	}else{
		if($accountnumber){
			$accountid=getmaxaccountid_frmaccountmaster($mconn);
			$bankid=getmaxbankidfrommaster($mconn,$bannkname);
			$mconn->query("INSERT INTO cash_authorizedperson(companyid,subcompanyid,projectid,accountid,paytoid,paytoname,loginby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$paytoid."','".addslashes(trim($paytoname))."','".addslashes(trim($addedby))."')");
			if(mysqli_affected_rows($mconn)>0){
				$mconn->query("INSERT INTO tbl_master_account(cmp_id, subcmp_id, projectid,paytoid, accountid,bankid, account_type, accountname, bankname, accountno, ifsccode, accountholdername) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$paytoid."','".$accountid."','".$bankid."', 'Cash', '".addslashes(trim($paytoname))."', '".addslashes(trim($bankname))."', '".$accountnumber."', '".$bankifsccode."', '".addslashes(trim($paytoname))."')");				
				$restimp=array('status'=>'Success', 'message'=>' Cash payto authorization save successfully', 'paytolist'=>'', 'paytoid'=>$paytoid);
			}else{
				$restimp=array('status'=>'Error', 'message'=>' Cash payto authorization did not save, due to somthing wrong.', 'paytolist'=>'');
			}
		}else{
			$restimp=array('status'=>'Error', 'message'=>' Cash payto authorization did not save, due to payto bank details not available.', 'paytolist'=>'');			
		}
	}
	return $restimp;
}

function saveimprestauthorization($mconn,$companyid,$subcompanyid,$projectid,$paytoname,$bankname,$accountnumber,$bankifsccode,$authirzedprojects,$paytomobileno,$paytoemailaddress,$loginid,$paytoid=null){
	$restimp=array();
	if($paytoid && $paytoid>0){
		$imprestpaytoid=$paytoid;
	}else{
		$ssp=savetodbnewpayto_details($mconn,$paytoname,$accountnumber,$bankname,$bankifsccode,"","","",$companyid,$subcompanyid,$projectid,$paytomobileno,$paytoemailaddress,$loginid,"");
		if(count($ssp)>0){
			foreach ($ssp as $sspkey) {
				if($sspkey['status']=="Success" || $sspkey['status']=="alreadyupdated"){
					$imprestpaytoid =$sspkey['paytoid']; 
				}
			}
		}else{
			$restimp=array('status'=>'Error', 'message'=>$paytoname.' did not exist in our payto master. So somthing wrongh', 'paytolist'=>'');
			return $restimp;
		}
	}
	$chkimpaccount=getimprestpayto($mconn,$companyid,$subcompanyid,$projectid,"payto",$paytoname);

	if(count($chkimpaccount)>0 && $surethisnew==""){
		foreach ($chkimpaccount as $iikey) {
			$imprestpaytoid=$iikey['imprestpaytoid'];
		}
		if(count($authirzedprojects)>0){
			if(projectexist_inprojectauth($mconn,$companyid,$subcompanyid,$projectid,$imprestpaytoid)=="notexist"){
				$insprjauth="INSERT INTO imprest_authorizedproject (imprestpaytoid,companyid,subcompanyid,projectid,loginby) VALUES";
				foreach ($authirzedprojects as $aupkey) {
					$insprjauth.="('".$imprestpaytoid."','".$aupkey['companyid']."','".$aupkey['subcompanyid']."','".$aupkey['projectid']."','".$loginid."'),";
				}
				$insprjauth = rtrim($insprjauth, ','); // Remove last comma
				$mconn->query($insprjauth) or die($mconn->error);
				$restimp=array('status'=>'Alreadyexist', 'message'=>$paytoname.' already exist in imprest payto master, but some project authorization update successfully.', 'paytolist'=>$chkimpaccount, 'paytoid'=>$imprestpaytoid);
			}else{
				$restimp=array('status'=>'Alreadyexist', 'message'=>$paytoname.' already exist in imprest payto master. Are you sure he/she is new person for imprest authorization', 'paytolist'=>$chkimpaccount, 'paytoid'=>$imprestpaytoid);
			}
		}		
		return $restimp;		
	}else{
		if($accountnumber){
			$maxsccid=getmaxaccountid_frmaccountmaster($mconn);
			$bankid=getmaxbankidfrommaster($mconn,$bannkname);
			$mconn->query("INSERT INTO imprest_authorizedperson(companyid,subcompanyid,projectid,imprest_accuntid,imprestpaytoid,payto,paytobankname,paytoaccountno,paytoifsccode,mobile_no,email_id,loginby) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$maxsccid."','".$imprestpaytoid."','".$paytoname."','".$bankname."','".$accountnumber."','".$bankifsccode."','".$paytomobileno."','".$paytoemailaddress."','".$loginid."')");
			if(mysqli_affected_rows($mconn)>0){
				if(count($authirzedprojects)>0){
					$insprjauth="INSERT INTO imprest_authorizedproject (imprestpaytoid,companyid,subcompanyid,projectid,loginby) VALUES";
					// print_r($authirzedprojects);
					foreach ($authirzedprojects as $aupkey) {
						$insprjauth.="('".$imprestpaytoid."','".$aupkey['companyid']."','".$aupkey['subcompanyid']."','".$aupkey['projectid']."','".$loginid."'),";
					}
					$insprjauth = rtrim($insprjauth, ','); // Remove last comma
					$mconn->query($insprjauth) or die($mconn->error);
				}
				$mconn->query("INSERT INTO tbl_master_account(cmp_id, subcmp_id, projectid,paytoid, accountid,bankid, account_type, accountname, bankname, accountno, ifsccode, accountholdername) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$imprestpaytoid."','".$maxsccid."','".$bankid."', 'Imprest', '".$paytoname."', '".$bankname."', '".$accountnumber."', '".$bankifsccode."', '".$paytoname."')");
				
				$restimp=array('status'=>'Success', 'message'=>' Imprest payto authorization save successfully', 'paytolist'=>'', 'paytoid'=>$imprestpaytoid);
			}else{
				$restimp=array('status'=>'Error', 'message'=>' Imprest payto authorization did not save, due to somthing wrong.', 'paytolist'=>'');
			}
		}else{
			$restimp=array('status'=>'Error', 'message'=>' Imprest payto authorization did not save, due to payto bank details not available.', 'paytolist'=>'');			
		}
	}
	return $restimp;
}

function saveOrUpdateCreditCardAuthorizedPerson($conn, $companyid, $subcompanyid, $projectid, $ccid, $paytoid, $authpersonname) {
    // Prepare the SELECT query to check if the record exists
    $check_query = $conn->prepare("SELECT sno FROM creditcard_authorizedperson WHERE companyid = ? AND subcompanyid = ? AND projectid = ? AND ccid = ? AND userid = ?");
    if (!$check_query) {
        return array('status' => 'Error', 'message' => "Query preparation failed: " . $conn->error);
    }
    $check_query->bind_param("sssss", $companyid, $subcompanyid, $projectid, $ccid, $paytoid);
    $check_query->execute();
    $check_query->store_result();

    // Check if the record exists
    if ($check_query->num_rows > 0) {
        // Bind the result to a variable
        $check_query->bind_result($sno);
        $check_query->fetch();
        // Record exists, perform an update
        $update_query = $conn->prepare("UPDATE creditcard_authorizedperson SET userid = ?, username = ? WHERE sno = ?");
        if (!$update_query) {
            $check_query->close();
            return array('status' => 'Error', 'message' => "Update statement preparation failed: " . $conn->error);
        }
        $update_query->bind_param("sss", $paytoid, $authpersonname, $sno);
        if ($update_query->execute()) {
            $result = array('status' => 'Success', 'message' => "Record updated successfully.", "paytoid"=>$paytoid);
        } else {
            $result = array('status' => 'Error', 'message' => "Error: " . $update_query->error);
        }
        $update_query->close();
    } else {
        // Record does not exist, perform an insert
        $insert_query = $conn->prepare("INSERT INTO creditcard_authorizedperson (companyid, subcompanyid, projectid, ccid, userid, username) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$insert_query) {
            $check_query->close();
            return array('status' => 'Error', 'message' => "Insert statement preparation failed: " . $conn->error);
        }
        $insert_query->bind_param("ssssss", $companyid, $subcompanyid, $projectid, $ccid, $paytoid, $authpersonname);
        if ($insert_query->execute()) {
            $result = array('status' => 'Success', 'message' => "Record inserted successfully.");
        } else {
            $result = array('status' => 'Error', 'message' => "Error: " . $insert_query->error);
        }
        $insert_query->close();
    }

    // Close the SELECT query
    $check_query->close();
    return $result;
}

function savetodbnewpayto_details($conn,$newname,$newaccountno,$newbankname,$newifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$newmobileno,$newemailaddress,$loginname,$maxempid=null){
	if($maxempid){
		$maxid=$maxempid;
	}else{
		$maxid = generateUniquePaytoId($conn, $companyid, $subcompanyid, $newname);
	}
	$paytoq = $conn->query("select paytoname,paytoid FROM tbl_payto_master where lcase(paytoname)='".strtolower(addslashes(trim($newname)))."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')") or die($conn->error) ;
	$message=array();
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		if($newbankname && $newaccountno){
			if($newemailaddress){
				$upemailaddress=", emailaddress='".addslashes($newemailaddress)."'";
			}
			if($newmobileno){
				$upmobileno=", mobileno='".addslashes($newmobileno)."'";
			}
			$conn->query("UPDATE tbl_payto_master SET bankname='".addslashes(trim($newbankname))."', accountno='".addslashes(trim($newaccountno))."', ifsccode='".addslashes(trim($newifsccode))."', iban='".addslashes($newiban)."', swift='".addslashes($newswift)."', sortcode='".addslashes($newsortcode)."', mobileno='".addslashes($newmobileno)."',  uploadflag=null ".$upemailaddress." ".$upmobileno." WHERE lcase(paytoname)='".strtolower(addslashes($newname))."'  and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
			if(mysqli_affected_rows($conn)>0){
				$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error);
				if($bnfq->num_rows>0){
					$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details updated successfully", "paytoid"=>$exrow['paytoid']);
					}
				}else{
					$conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error);
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details saved successfully", "paytoid"=>$exrow['paytoid']);
					}
				}
			// $message[] = array("status"=> 'Success', 'message'=>"Service Provider details updated successfully", "paytoid"=>$exrow['paytoid']);
			}else{
				$message[] = array("status"=> 'alreadyupdated', 'message'=>"Service Provider details did not update, due to somthing wrong", "paytoid"=>$exrow['paytoid']);
			}
		}else{
			$message[] = array("status"=> 'Success', 'message'=>"Already updated", "paytoid"=>$exrow['paytoid']);
		}
	}else{
		$conn->query("INSERT INTO tbl_payto_master (paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, cmp_id, subcmp_id, projectid, paytoaddby) VALUES('".$maxid."', '".strtoupper(addslashes(trim($newname)))."', '".addslashes(trim($newaccountno))."', '".strtoupper(addslashes(trim($newbankname)))."', '".strtoupper(addslashes(trim($newifsccode)))."', '".strtoupper(addslashes(trim($newiban)))."', '".strtoupper($newswift)."', '".strtoupper($newsortcode)."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$loginname."' ) ") or die($conn->error);
		if(mysqli_affected_rows($conn)>0){
			$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error);
				if($bnfq->num_rows>0){
					$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details updated successfully", "paytoid"=>$exrow['paytoid']);
					}
				}else{
					$conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error);
					if(mysqli_affected_rows($conn)>0){
						$message[] = array("status"=> 'Success', 'message'=>"Service Provider details added successfully", "paytoid"=>$maxid);
					}
				}
			// $message[] = array("status"=> 'Success', 'message'=>"Service Provider details added successfully", "paytoid"=>$maxid);
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Service Provider details did not update in master table, due to somthing wrong", "paytoid"=>"");
		}
	}
	return $message;
}
function savetodbnewpayerto_details($conn,$newname,$newaccountno,$newbankname,$newifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$newmobileno,$newemailaddress,$loginname,$maxempid=null){
	if($maxempid){
		$maxid=$maxempid;
	}else{
		$maxid = generateUniquePayertoId($conn, $companyid, $subcompanyid, $newname);
	}
	$paytoq = $conn->query("select payername,payerid FROM tbl_payer_master where lcase(payername)='".strtolower(addslashes(trim($newname)))."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')") or die($conn->error) ;
	$message=array();
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		if($newbankname && $newaccountno){
			if($newemailaddress){
				$upemailaddress=", emailaddress='".addslashes($newemailaddress)."'";
			}
			if($newmobileno){
				$upmobileno=", mobileno='".addslashes($newmobileno)."'";
			}
			$conn->query("UPDATE tbl_payer_master SET bankname='".addslashes(trim($newbankname))."', accountno='".addslashes(trim($newaccountno))."', ifsccode='".addslashes(trim($newifsccode))."', iban='".addslashes($newiban)."', swift='".addslashes($newswift)."', sortcode='".addslashes($newsortcode)."', mobileno='".addslashes($newmobileno)."',  uploadflag=null ".$upemailaddress." ".$upmobileno." WHERE lcase(payername)='".strtolower(addslashes($newname))."'  and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
			if(mysqli_affected_rows($conn)>0){
				$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error) ;
				if($bnfq->num_rows>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto bank details update successfully", "paytoid"=>$exrow['paytoid']);
				}else{
					$bnfqry = $conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('0', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error) ;
					if($bnfqry){
						$message[] = array("status"=> 'Success', 'message'=>"Payto & beneficiary master updated successfully", "paytoid"=>$exrow['paytoid']);
					}
				}
			}else{
				$message[] = array("status"=> 'alreadyupdated', 'message'=>"Payto bank detail did not update, due to somthing wrong", "paytoid"=>$exrow['paytoid']);
			}
		}else{
			$message[] = array("status"=> 'Success', 'message'=>"Already updated", "paytoid"=>$exrow['paytoid']);
		}
	}else{
		$conn->query("INSERT INTO tbl_payer_master (payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode, countryid, cmp_id, subcmp_id, projectid, payeraddby) VALUES('".$maxid."', '".strtoupper(addslashes(trim($newname)))."', '".addslashes(trim($newaccountno))."', '".strtoupper(addslashes(trim($newbankname)))."', '".strtoupper(addslashes(trim($newifsccode)))."', '".strtoupper(addslashes(trim($newiban)))."', '".strtoupper($newswift)."', '".strtoupper($newsortcode)."', '0', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$loginname."' ) ") or die($conn->error);
		if(mysqli_affected_rows($conn)>0){
			$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error);
			if($bnfq->num_rows>0){
				$message[] = array("status"=> 'Success', 'message'=>"Payto master updated successfully", "paytoid"=>$maxid);
			}else{
				$conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error);
				if(mysqli_affected_rows($conn)>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto & beneficiary master updated successfully", "paytoid"=>$maxid);
				}
			}
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Payto details did not update in master table, due to somthing wrong", "paytoid"=>"");
		}
	}
	return $message;
}
function updatepayto_details($conn,$companyid,$subcompanyid,$projectid,$newname,$newaccountno,$newbankname,$newifsccode,$newiban,$newswift,$newsortcode,$newmobileno,$newemailaddress,$paytoid,$loginname=null){
	$paytoq = $conn->query("select paytoid, paytoname, bankname, accountno, ifsccode, iban, swift, sortcode, paymentterms, mobileno, emailaddress, paytoaddby FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and lcase(paytoid)='".addslashes(trim($paytoid))."' and (tedit is null or tedit='')") or die($conn->error) ;
	$message=array();
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		if($newname && $newbankname && $newaccountno){
			$conn->query("UPDATE tbl_payto_master SET lcase(paytoname)='".strtolower(addslashes($newname))."', bankname='".addslashes(trim($newbankname))."', accountno='".addslashes(trim($newaccountno))."', ifsccode='".addslashes(trim($newifsccode))."', iban='".addslashes($newiban)."', swift='".addslashes($newswift)."', sortcode='".addslashes($newsortcode)."', mobileno='".addslashes($newmobileno)."', emailaddress='".addslashes($newemailaddress)."', uploadflag=null WHERE lcase(paytoname)='".strtolower(addslashes($newname))."'  and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
			if(mysqli_affected_rows($conn)>0){
					$message = array("status"=> 'Success', 'message'=>"Payto bank details update successfully", "paytoid"=>$exrow['paytoid']);
			}else{
				$message = array("status"=> 'alreadyupdated', 'message'=>"Payto bank detail did not update, due to somthing wrong", "paytoid"=>$exrow['paytoid']);
			}
		}else{
			$message = array("status"=> 'Error', 'message'=>"Some details are missing", "paytoid"=>$exrow['paytoid']);
		}
	}
	return $message;
}

function savetodbnewclient_details($conn,$client_name,$invoicing_address,$phone,$email,$vat,$client_bankname,$client_accountno,$client_bankifsc,$client_bankiban,$client_bankswift,$client_banksortcode,$companyid,$subcompanyid,$projectid,$loginname=null,$maxempid=null){
	if($maxempid){
		$clientid=$maxempid;
	}else{
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
	}
	$paytoq = $conn->query("select client_id,client_name FROM client_master where lcase(client_name)='".strtolower(addslashes(trim($client_name)))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')") or die($conn->error) ;
	$rrstat=array();
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		if($client_bankname && $client_accountno){
			$conn->query("UPDATE client_master SET bankname='".addslashes(trim($client_bankname))."', accountno='".addslashes(trim($client_accountno))."', ifsccode='".addslashes(trim($client_bankifsc))."' WHERE lcase(client_name)='".strtolower(addslashes($client_name))."'  and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
			if(mysqli_affected_rows($conn)>0){
				$rrstat[] = array("status"=> 'Success', 'message'=>"Client bank details update successfully", "paytoid"=>$exrow['client_id']);
				
			}else{
				$rrstat[] = array("status"=> 'alreadyupdated', 'message'=>"Client bank detail did not update, due to somthing wrong", "paytoid"=>$exrow['client_id']);
			}
		}else{
			$message[] = array("status"=> 'Success', 'message'=>"Already updated", "paytoid"=>$exrow['client_id']);
		}
	}else{	
		$insert = "INSERT into client_master (companyid,subcompanyid,projectid,client_id, client_name, invoicing_address, phones, client_email, vat, date_time, bankname, accountno, ifsccode, account_name, iban, swift, sort_code) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$clientid."','".$client_name."','".addslashes($invoicing_address)."','".addslashes($phone)."','".addslashes($email)."','".addslashes($vat)."','".date('Y-m-d H:i:s')."','".addslashes($client_bankname)."','".addslashes($client_accountno)."', '".addslashes($client_bankifsc)."', '".addslashes($client_name)."', '".addslashes($client_bankiban)."', '".addslashes($client_bankswift)."', '".addslashes($client_banksortcode)."')";
		$result = $conn->query($insert) or die($conn->error);
		if($result){
			$rrstat[]=array('status'=>'Success', 'message'=>'Your client registration save successfully');
		}else{
			$rrstat[]=array('status'=>'Error', 'message'=>'Your client registration did not save, due to technical issue');
		}
	}
	return $rrstat;
}
function savetodbnewpayer_details($conn,$newname,$newaccountno,$newbankname,$newifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$loginname=null,$maxempid=null){
	if($maxempid){
		$maxid=$maxempid;
	}else{
		$maxid = $conn->query("select ifnull(max(payerid)+1,1) as maxid FROM tbl_payer_master");
		if($maxid->num_rows>0){
			$mrow = $maxid->fetch_assoc();
			$maxid = $mrow['maxid'];
		}
	}
	$paytoq = $conn->query("select payername,payerid FROM tbl_payer_master where lcase(payername)='".strtolower(addslashes(trim($newname)))."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')") or die($conn->error) ;
	$message=array();
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		if($newbankname && $newaccountno){
			$conn->query("UPDATE tbl_payer_master SET bankname='".addslashes(trim($newbankname))."', accountno='".addslashes(trim($newaccountno))."', ifsccode='".addslashes(trim($newifsccode))."', uploadflag=null WHERE lcase(payername)='".strtolower(addslashes($newname))."'  and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
			if(mysqli_affected_rows($conn)>0){
				$message[] = array("status"=> 'Success', 'message'=>"Payer bank details update successfully", "payerid"=>$exrow['payerid']);
			}else{
				$message[] = array("status"=> 'alreadyupdated', 'message'=>"Payto bank detail did not update, due to something wrong");
			}
		}else{
			$message[] = array("status"=> 'Success', 'message'=>"Already updated", "payerid"=>$exrow['payerid']);
		}
	}else{
		$conn->query("INSERT INTO tbl_payer_master (payerid, payername, accountno, bankname, ifsccode, iban, swift, sortcode, countryid, cmp_id, subcmp_id, projectid, payeraddby) VALUES('".$maxid."', '".strtoupper(addslashes(trim($newname)))."', '".addslashes(trim($newaccountno))."', '".strtoupper(addslashes(trim($newbankname)))."', '".strtoupper(addslashes(trim($newifsccode)))."', '".strtoupper(addslashes(trim($newiban)))."', '".strtoupper($newswift)."', '".strtoupper($newsortcode)."', '0', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$loginname."' ) ") or die($conn->error);
		if(mysqli_affected_rows($conn)>0){
			$message[] = array("status"=> 'Success', 'message'=>"Payer master updated successfully", "payerid"=>$maxid);
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Payer details did not update in master table, due to something wrong");
		}
	}
	return $message;
}
function updatepaytomasterdetails($conn,$newname,$newaccountno,$newbankname,$newifsccode,$newiban,$newswift,$newsortcode,$companyid,$subcompanyid,$projectid,$paytoid,$loginname=null){
	$paytoq = $conn->query("select paytoname,paytoid FROM tbl_payto_master where lcase(paytoid)='".strtolower(addslashes(trim($paytoid)))."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')") or die($conn->error) ;
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		$oldpaytoname =$exrow['paytoname'];
		$nbankup ="";
		if($newbankname){
			$nbankup = ", bankname='".addslashes(trim($newbankname))."'";
		}
		$naccnoup ="";
		if($newaccountno){
			$naccnoup = ", accountno='".addslashes(trim($newaccountno))."'";
		}
		$nifscup ="";
		if($newifsccode){
			$nifscup = ", ifsccode='".addslashes(trim($newifsccode))."'";
		}
		$conn->query("UPDATE tbl_payto_master SET paytoname='".$newname."', uploadflag=null  ".$nbankup."  ".$naccnoup."  ".$nifscup." WHERE paytoid='".$exrow['paytoid']."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
		if(mysqli_affected_rows($conn)>0){
			$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error) ;
			if($bnfq->num_rows>0){
				$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
				if(mysqli_affected_rows($conn)>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto & beneficiary master updated successfully", "paytoid"=>$exrow['paytoid']);
				}
			}else{
				$bnfqry = $conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('0', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error) ;
				if($bnfqry){
					$message[] = array("status"=> 'Success', 'message'=>"Payto & beneficiary master updated successfully", "paytoid"=>$exrow['paytoid']);
				}
			}
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Payto bank detail did not update in payto master list, due to somthing wrong");
		}
	}else{
		$maxid = $conn->query("select ifnull(max(paytoid)+1,1) as maxid FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
		if($maxid->num_rows>0){
			$mrow = $maxid->fetch_assoc();
			$maxid = $projectid.$mrow['maxid'];
		}
		$conn->query("INSERT INTO tbl_payto_master (paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, cmp_id, subcmp_id, projectid, paytoaddby) VALUES('".$maxid."', '".strtoupper(addslashes(trim($newname)))."', '".addslashes(trim($newaccountno))."', '".strtoupper(addslashes(trim($newbankname)))."', '".strtoupper(addslashes(trim($newifsccode)))."', '".strtoupper(addslashes(trim($newiban)))."', '".strtoupper($newswift)."', '".strtoupper($newsortcode)."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$loginname."')") or die($conn->error);
		if(mysqli_affected_rows($conn)>0){
			$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error);
			if($bnfq->num_rows>0){
				$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
				if(mysqli_affected_rows($conn)>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto & beneficiary master updated successfully", "paytoid"=>$exrow['paytoid']);
				}
			}else{
				$conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error);
				if(mysqli_affected_rows($conn)>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto & beneficiary master updated successfully", "paytoid"=>$maxid);
				}
			}
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Payto details did not update in master table, due to somthing wrong");
		}
	}
	return $message;
}
function updatepaytomaster_fulldetails($conn,$companyid,$subcompanyid,$projectid,$paytoid,$newname,$newaccountno,$newbankname,$newifsccode,$newiban,$newswift,$newsortcode,$mobileno,$emailaddress,$address,$cityaddress,$postalcodeaddress,$countryaddress,$taxtype,$gstorvalno,$loginname=null){
	// $mobileno,$emailaddress,$address,$cityaddress,$postalcodeaddress,$countryaddress,$taxtype,$gstorvalno
	$paytoq = $conn->query("select paytoname,paytoid FROM tbl_payto_master where lcase(paytoid)='".strtolower(addslashes(trim($paytoid)))."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')") or die($conn->error) ;
	if($paytoq->num_rows>0){
		$exrow=$paytoq->fetch_assoc();
		$oldpaytoname =$exrow['paytoname'];
		$nbankup ="";
		if($newbankname){
			$nbankup = ", bankname='".addslashes(trim($newbankname))."'";
		}
		$naccnoup ="";
		if($newaccountno){
			$naccnoup = ", accountno='".addslashes(trim($newaccountno))."'";
		}
		$nifscup ="";
		if($newifsccode){
			$nifscup = ", ifsccode='".addslashes(trim($newifsccode))."'";
		}
		$dnewiban="";
		if($newiban){
			$dnewiban = ", iban='".addslashes(trim($newiban))."'";
		}
		$dnewswift="";
		if($newswift){
			$dnewswift = ", swift='".addslashes(trim($newswift))."'";
		}
		$dnewsortcode="";
		if($newsortcode){
			$dnewsortcode = ", sortcode='".addslashes(trim($newsortcode))."'";
		}
		$conn->query("UPDATE tbl_payto_master SET paytoname='".$newname."', uploadflag=null, projectid='".$projectid."', mobileno='".$mobileno."', emailaddress='".$emailaddress."', address='".$address."', city='".$cityaddress."', postalcode='".$postalcodeaddress."', country='".$countryaddress."', taxid='".$gstorvalno."', taxtype='".$taxtype."'  ".$nbankup."  ".$naccnoup."  ".$nifscup." ".$dnewiban." ".$dnewswift." ".$dnewsortcode." WHERE paytoid='".$exrow['paytoid']."' and  cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."'");
		if(mysqli_affected_rows($conn)>0){
			$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error) ;
			if($bnfq->num_rows>0){
				$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
				if(mysqli_affected_rows($conn)>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto master updated successfully", "paytoid"=>$exrow['paytoid']);
				}
			}else{
				$bnfqry = $conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('0', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error) ;
				if($bnfqry){
					$message[] = array("status"=> 'Success', 'message'=>"Payto master updated successfully", "paytoid"=>$exrow['paytoid']);
				}
			}
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Payto detail did not update in payto master list, due to somthing wrong");
		}
	}else{
		$maxid = $conn->query("select ifnull(max(paytoid)+1,1) as maxid FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."'");
		if($maxid->num_rows>0){
			$mrow = $maxid->fetch_assoc();
			$maxid = $projectid.$mrow['maxid'];
		}
		$conn->query("INSERT INTO tbl_payto_master (cmp_id, subcmp_id, projectid, paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode, mobileno, emailaddress, address, city, postalcode, country, taxtype, taxid, paytoaddby) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$maxid."', '".strtoupper(addslashes(trim($newname)))."', '".addslashes(trim($newaccountno))."', '".strtoupper(addslashes(trim($newbankname)))."', '".strtoupper(addslashes(trim($newifsccode)))."', '".strtoupper(addslashes(trim($newiban)))."', '".strtoupper($newswift)."', '".strtoupper($newsortcode)."','".strtoupper(addslashes(trim($mobileno)))."', '".strtoupper(addslashes(trim($emailaddress)))."', '".strtoupper(addslashes(trim($address)))."', '".strtoupper(addslashes(trim($cityaddress)))."', '".strtoupper(addslashes(trim($postalcodeaddress)))."', '".strtoupper(addslashes(trim($countryaddress)))."', '".strtoupper(addslashes(trim($taxtype)))."', '".strtoupper(addslashes(trim($gstorvalno)))."', '".$loginname."')") or die($conn->error);
		if(mysqli_affected_rows($conn)>0){
			$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error);
			if($bnfq->num_rows>0){
				$conn->query("UPDATE tbl_beneficiaries_master SET name='".$newname."' where lcase(name)='".strtolower(trim($oldpaytoname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'");
				if(mysqli_affected_rows($conn)>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto master updated successfully", "paytoid"=>$exrow['paytoid']);
				}
			}else{
				$conn->query("INSERT INTO tbl_beneficiaries_master (countryid,companyid,subcompanyid,projectid,name,addby) VALUES('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error);
				if(mysqli_affected_rows($conn)>0){
					$message[] = array("status"=> 'Success', 'message'=>"Payto master updated successfully", "paytoid"=>$maxid);
				}
			}
		}else{
			$message[] = array("status"=> 'Error', 'message'=>"Payto details did not update in master table, due to somthing wrong");
		}
	}
	return $message;
}
function newemployeefromsalarysheet($conn,$companyid,$subcompanyid,$projectid,$empname,$accountno,$bankname,$ifsccode,$monthlysalary,$designationname,$loginname=null){
	$sq=$conn->query("SELECT empid, empname, basicsalary, hra, conveyance, otherallowance, designationid, designationname, purposecode FROM payroll_employeemaster WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and accountno='".$accountno."'  and (resign is null or resign='') and (tedit is null or tedit='')");
	$empid="";
	$rrstt=array();
	if($sq->num_rows>0){
		while($prow=$sq->fetch_assoc()){
			$rrstt[]=array('status'=>'alreadyexist', 'message'=>'Employee already exist with account number '.$accountno, 'empid'=>$prow['empid'], 'empname'=>$prow['empname'], 'purposecode'=>$prow['purposecode']);
		}
	}else{
		$sqq="SELECT empid, empname, basicsalary, hra, conveyance, otherallowance, designationid, designationname, purposecode FROM payroll_employeemaster WHERE companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and lcase(empname)='".strtolower($empname)."'  and (resign is null or resign='') and (tedit is null or tedit='')";
		if($sqq->num_rows>0){
			while($pprow=$sqq->fetch_assoc()){
				$empname=$pprow['empname'];
				$empid=$pprow['empid'];
				$rrstt[]=array('status'=>'alreadyexist', 'message'=>'Employee already exist with name '.$empname, 'empid'=>$pprow['empid'], 'empname'=>$pprow['empname'], 'purposecode'=>$pprow['purposecode']);
			}
		}else{
			$empid = rand(1000, 9999);
			$conn->query("INSERT INTO payroll_employeemaster (companyid, subcompanyid, projectid, empname, empid, basicsalary, designationname, bankname, accountno, ifsccode,updateby,employmenttype) VALUES ('".$companyid."','".$subcompanyid."','".$projectid."','".addslashes(trim($empname))."','".$empid."','".$monthlysalary."','".addslashes(trim($designationname))."','".addslashes(trim($bankname))."','".addslashes(trim($accountno))."','".addslashes(trim($ifsccode))."','".addslashes(trim($loginname))."','regular')");
			if(mysqli_affected_rows($conn)>0){
				savetodbnewpayto_details($conn,$empname,$accountno,$bankname,$ifsccode,"","","",$companyid,$subcompanyid,$projectid,"","",$loginname,$empid);
				$rrstt[]=array('status'=>'Success', 'message'=>'New Employee details saved successfully', 'empid'=>$empid, 'empname'=>$empname, 'purposecode'=>'');
			}else{
				$rrstt[]=array('status'=>'Error', 'message'=>'New Employee details did not save, due to error in save', 'empid'=>'', 'empname'=>$empname, 'purposecode'=>'');
			}
		}
	}
	return $rrstt;
}
function savetodbnewbeneficiary_details($conn,$companyid,$subcompanyid,$projectid,$newname,$loginname=null){
	$bnfq = $conn->query("select name FROM tbl_beneficiaries_master where lcase(name)='".strtolower(trim($newname))."' and  companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'") or die($conn->error) ;
	if($bnfq->num_rows>0){
		$message = array("status"=> 'Error', 'message'=>"Beneficiary name already exist", "paytoid"=>"");
	}else{
		$bnfqry = $conn->query("INSERT INTO tbl_beneficiaries_master (companyid,subcompanyid,projectid,name,addby) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".strtoupper(addslashes(trim($newname)))."', '".$loginname."')") or die($conn->error) ;
		if($bnfqry){
			$message = array("status"=> 'Success', 'message'=>"beneficiary master also updated successfully", "paytoid"=>"");
		}
	}
	return $message;
}
function insert_closetobook_breakdown($mconn,$companyid, $subcompanyid, $projectid, $reference_no, $beneficiary_name, $amount, $tds_percent, $tds_amount, $purpose_category, $purpose_categoryid, $purpose_subcategory, $purpose_subcategoryid, $purpose, $purpose_code,$excelsheetsno,$description){
	$mconn->query("INSERT INTO tbl_closetobook_breakdown(companyid, subcompanyid, projectid, reference_no, beneficiary_name, amount, tds_percent, tds_amount, purpose_category, purpose_categoryid, purpose_subcategory, purpose_subcategoryid, purpose, purpose_code, expensedata_sno,description) VALUES('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$reference_no."', '".addslashes(trim($beneficiary_name))."', '".addslashes(trim($amount))."', '".addslashes(trim($tds_percent))."', '".addslashes(trim($tds_amount))."', '".addslashes(trim($purpose_category))."', '".addslashes(trim($purpose_categoryid))."', '".addslashes(trim($purpose_subcategory))."', '".addslashes(trim($purpose_subcategoryid))."', '".addslashes(trim($purpose))."', '".addslashes(trim($purpose_code))."','".addslashes(trim($excelsheetsno))."','".addslashes(trim($description))."')");
	if(mysqli_affected_rows($mconn)>0){
		// echo "0";
	}else{
		// echo "1";
	}
}

// not used it
// function saveloanregistrationdetails($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$paytoid,$loanto_name,$loanto_accountno,$loanto_bankname,$loanto_accountifsc,$loginname){	
// 	$ssq=$conn->query("select loanid FROM tbl_loanaccount_master WHERE loanto_accountno='".$loanto_accountno."'");
// 	if($ssq->num_rows>0){
// 		$lrow=$ssq->fetch_assoc();
// 		$loanid = $lrow['loanid']; 
// 	}else{
// 		$numberStr = (string)$loanto_accountno;
// 		$loanid = date('Ymd').substr($numberStr, -4);
// 		$conn->query("INSERT INTO tbl_loanaccount_master(loanid, loanto_name, loanto_accountno, loanto_bankname, loanto_accountifsc, companyid, subcompanyid, projectid,subprojectid,paytoid, addedby, edatetime) VALUES('".$loanid."', '".$loanto_name."', '".$loanto_accountno."', '".$loanto_bankname."', '".$loanto_accountifsc."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$paytoid."', '".$loginname."', '".date('Y-m-d h:i:s')."')");
// 		if(mysqli_affected_rows($conn)>0){
// 			$loanid = $loanid;
// 		}else{
// 			$loanid = "";
// 		}
// 	}
// 	return $loanid;
// }

function recunstructbookautomatic_new($mconn,$companyid,$subcompanyid,$projectid,$accountid,$paymentmode,$chequedate,$chequeno,$withdrownamount,$payto,$dbsno_bnkstatement,$description_bank,$bankid,$bankname,$accountname,$accountno,$beneficiarydeatilslist,$condition){
	$rst=array();
	$closetobook_referenceno = $dbsno_bnkstatement;
	$bbamt=0;
	foreach ($beneficiarydeatilslist as $bnkey) {
		$bbamt+=$bnkey['beneficaryamount'];
	}
	$diffamt=$withdrownamount - $bbamt; 
	
	if(count($beneficiarydeatilslist)==1){
		$beneficerytype="Individual";
	}else if(count($beneficiarydeatilslist)>1){
		$beneficerytype="Multiple";
	}
	
	if(count($beneficiarydeatilslist)>0){
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    	$mconn->begin_transaction();
    	try{
	    	$imprestexist ="";
	    	$imprestnew="";
	    	$tsno=0;
		    $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");		
			if($mtso->num_rows>0){
				$trow=$mtso->fetch_assoc();
				$tsno=$trow['maxtsno'];
			}
			$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
			
			$accqry="select accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='cash' and ifnull(tedit,'')=''";
			$rlt = $mconn->query($accqry);
			$cashaccountid ="";
			if($rlt->num_rows>0){
				$cashacrow = $rlt->fetch_assoc();
				$cashaccountid = $cashacrow["accountid"];
			}
			else{
				$accqry="select ifnull(max(accountid)+1,6000) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='cash' and ifnull(tedit,'')=''";
				$rlt = $mconn->query($accqry);
				if($rlt->num_rows>0){
					$cashacrow = $rlt->fetch_assoc();
					$cashaccountid = $cashacrow["accountid"];
					$newaccqry = "insert into tbl_master_account(cmp_id,subcmp_id,projectid,accountid,account_type,accountname) values('".$companyid."', '".$subcompanyid."', '".$projectid."','".$cashaccountid."','Cash','Cash')";
					$mconn->query($newaccqry);
				}
			}
			if(count($beneficiarydeatilslist)==1){	    	
			    $bnfcount=1;
			    $brkqry="";
			    $cashbrkqry="";
			    $purposecategory="";
			    $ttlamount=0;
			    $beneficeryname ="";
				$payto ="";
				$amount ="";
				$description ="";
				$dbsno_exp ="";
				$paymentmode ="";
				$ex_chequeno ="";
				$bookdate =""; 
				$providedcode =""; 
				$purposecode =""; 
				$purpose =""; 
				$pcatagoryname =""; 
				$pcatid =""; 
				$psubcatid =""; 
				$psubcat =""; 
				$paymenttype =""; 
				$insq="INSERT INTO tbl_maintransaction_breakdown(companyid, subcompanyid, projectid, request_no, reference_no, beneficiary_name, amount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, invoice_type, invoice_no, description, description_bank, blobfile, blobfile_tblname, tds_type, tds_percent, tds_amount, documentation, doctype, documentation_path,provided_code) VALUES";
				$cashinsqry = "INSERT INTO cash_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,cash_payto,cash_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid,mergewith) VALUES";
				$dbsno_exp="";
			    foreach ($beneficiarydeatilslist as $bnfkey) {
			    	$beneficeryname =$bnfkey['beneficaryname'];
			    	$payto =$bnfkey['payto'];
		            $amount =$bnfkey['beneficaryamount'];        
		            $description =$bnfkey['description'];                           
		            $providedcode =$bnfkey['providedcode'];
		            $purposecode =$bnfkey['purposecode'];
		            $purpose = $bnfkey['purpose'];
					$pcatagoryname = $bnfkey['pcatagoryname'];
					$pcatid=$bnfkey['pcatid'];
					$psubcatid=$bnfkey['psubcatid'];
					$psubcat=$bnfkey['psubcat'];
					$paymenttype=$bnfkey['paymenttype'];
		            if($dbsno_exp){
			            $dbsno_exp =$dbsno_exp.','.$bnfkey['dbsno_exp'];
		            }else{
		            	$dbsno_exp =$bnfkey['dbsno_exp'];
		            }
		            $paymentmode =$bnfkey['paymentmode'];
		            $ex_chequeno =$bnfkey['chequeno'];
		            $bookdate =$chequedate;
		            $finyeartype = getfinancialyeartype($mconn,$companyid);
					$finyear = getFinancialYear($bookdate,$finyeartype);
		            if($purposecode){
			            if($brkqry){
				            $brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($beneficeryname)."', '".$withdrownamount."', '".$pcatid."', '".addslashes($pcatagoryname)."', '".$psubcatid."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '', '', '".addslashes($description)."', '".addslashes($description_bank)."', '', '', '', '', 0, '', '', '','".$providedcode."')";
			            } 
			            else{
			            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($beneficeryname)."', '".$withdrownamount."', '".$pcatid."', '".addslashes($pcatagoryname)."', '".$psubcatid."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '', '', '".addslashes($description)."', '".addslashes($description_bank)."', '', '', '', '', 0, '', '', '','".$providedcode."')";
			            }
			            $ttlamount += $amount;
			            if(strtolower($paymenttype)<>'cash' && (stristr($description_bank, "cash") &&  stristr($description_bank, "self"))){
							if($cashbrkqry){
								$cashbrkqry = $cashbrkqry.",('".$chequedate."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($payto)."','0','".$withdrownamount."','".$withdrownamount."','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."','".$cashaccountid."', '".$newreference_no."')";
							}
							else{
								$cashbrkqry = "('".$chequedate."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($payto)."','0','".$withdrownamount."','".$withdrownamount."','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."','".$cashaccountid."', '".$newreference_no."')";
							}
			            }
			        }
		    	}
		    	if($brkqry){
		    		$brkqry1=$insq.$brkqry;
		    		$mconn->query($brkqry1);
		    		if(mysqli_affected_rows($mconn)>0){
		    			$mqr="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid, reference_no, transaction_date, bookdate,finyear, book_amount, beforeedit_amount,transfered_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, is_intermediate_transfer, refundable_projectid,transactionno,book_source,book_status,referenceno_clb,reconcilation_process) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$chequedate."', '".$chequedate."','".$finyear."', '".$withdrownamount."', '".$withdrownamount."', '".$withdrownamount."', '".$paymentmode."', '".addslashes($payto)."', '".$bankname."', '".$accountno."', '".$accountid."', '".$paymenttype."', '".$beneficerytype."', '', '', '', '', '','".$chequeno."','closetobook','completed','".$closetobook_referenceno."','".$condition."')";
		            	$mconn->query($mqr);
		            	if(mysqli_affected_rows($mconn)>0){
							
		            		if (trim(strtolower($paymenttype))=='imprest') {
		            			$getpurposestring=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecategoryname",'Imprest');
								if(count($getpurposestring)>0){
						            foreach ($getpurposestring as $keypc) {
										$purpose = $keypc['purpose'];
										$purposecode = $keypc['purposecode'];
										$pcatagoryname = $keypc['pcategory'];
										$pcatid=$keypc['pcategorycode'];
										$psubcatid=$keypc['psub_categoryid'];
										$psubcat=$keypc['psub_category'];
										$paymenttype=$keypc['pcategory'];
									}
						        }
						         //Check and insert data into tbl_master_account
								$accqry="select accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and lcase(accountname)='".strtolower(addslashes($payto))."' and ifnull(tedit,'')=''";
								$rlt = $mconn->query($accqry);
								$impaccountid ="";
								if($rlt->num_rows>0){
									$impacrow = $rlt->fetch_assoc();
									$impaccountid = $impacrow["accountid"];
									$imprestexist= "exist";
								}
								else{
									$accqry="select ifnull(max(accountid)+1,7000) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and ifnull(tedit,'')=''";
									$rlt = $mconn->query($accqry);
									if($rlt->num_rows>0){
										$impacrow = $rlt->fetch_assoc();
										$impaccountid = $impacrow["accountid"];
										$newaccqry = "insert into tbl_master_account(cmp_id,subcmp_id,projectid,accountid,account_type,accountname,accountholdername,status) values('".$companyid."', '".$subcompanyid."', '".$projectid."','".$impaccountid."','Imprest','".strtoupper(addslashes($payto))."','".strtoupper(addslashes($payto))."','unverified')";
										$mconn->query($newaccqry);
										$imprestnew= "new single";
									}
								}
						        //
		    					$imqr="Insert into imprest_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,imprest_payto,imprest_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid) values('".$chequedate."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($payto)."','".$ttlamount."','0','0','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."','".$impaccountid."')";
		    					$mconn->query($imqr);
		    				}else if (trim(strtolower($paymenttype))=='cash' || (stristr($description_bank, "cash") &&  stristr($description_bank, "self"))||trim(strtolower($description_bank))=='self') {
		    					$getpurposestring=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecategoryname",'Cash');
								if(count($getpurposestring)>0){
						            foreach ($getpurposestring as $keypc) {
										$purpose = $keypc['purpose'];
										$purposecode = $keypc['purposecode'];
										$pcatagoryname = $keypc['pcategory'];
										$pcatid=$keypc['pcategorycode'];
										$psubcatid=$keypc['psub_categoryid'];
										$psubcat=$keypc['psub_category'];
										$paymenttype=$keypc['pcategory'];
									}
						        }
								
		    					$cshqr="Insert into cash_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,cash_payto,cash_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description) values('".$chequedate."', '".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($payto)."','".$ttlamount."','0','0','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."')";
		    					$mconn->query($cshqr);
		    					if($cashbrkqry){
		    						$cashbrkqry1 = $cashinsqry.$cashbrkqry;
		    						$mconn->query($cashbrkqry1);
		    					}
		    				}
		            		reconciled_updatebankstatment_closetobook($mconn,$newreference_no,$chequedate,$dbsno_bnkstatement,$closetobook_referenceno);
		            		reconciled_updateexpensesheet($mconn,$newreference_no,$dbsno_exp,$closetobook_referenceno);
		            		$rst[]=array('status'=>'Success', 'message'=>'Your transaction update successfully. Imprest Exist: '. $imprestexist.' Imprest New: '. $imprestnew);
		            	}else{
		            		$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in maintransaction');
		            	}
		            }
		            else{
		            	$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in breakdown');
		            }
		    	}
		    }else if($diffamt==0){
				$insq="INSERT INTO tbl_maintransaction_breakdown(companyid, subcompanyid, projectid, request_no, reference_no, beneficiary_name, amount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, invoice_type, invoice_no, description, description_bank, blobfile, blobfile_tblname, tds_type, tds_percent, tds_amount, documentation, doctype, documentation_path,provided_code) VALUES";
				$cashinsqry = "INSERT INTO cash_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,cash_payto,cash_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid,mergewith) VALUES";
				$dbsno_exp="";
				$imprest_amount = 0;
				foreach ($beneficiarydeatilslist as $bnfkey) {
			    	$beneficeryname =$bnfkey['beneficaryname'];
		            $amount =$bnfkey['beneficaryamount'];        
		            $description =$bnfkey['description'];                           
		            $providedcode =$bnfkey['providedcode'];
		            $purposecode =$bnfkey['purposecode'];
		            $purpose = $bnfkey['purpose'];
					$pcatagoryname = $bnfkey['pcatagoryname'];
					$pcatid=$bnfkey['pcatid'];
					$psubcatid=$bnfkey['psubcatid'];
					$psubcat=$bnfkey['psubcat'];
					$paymenttype=$bnfkey['paymenttype'];
		            if($dbsno_exp){
			            $dbsno_exp =$dbsno_exp.','.$bnfkey['dbsno_exp'];
		            }else{
		            	$dbsno_exp =$bnfkey['dbsno_exp'];
		            }
		            $paymentmode =$bnfkey['paymentmode'];
		            $ex_chequeno =$bnfkey['chequeno'];
		            $bookdate =$chequedate;
					$finyeartype = getfinancialyeartype($mconn,$companyid);
					$finyear = getFinancialYear($bookdate,$finyeartype);
		            if($purposecode){
			            if($brkqry){
				            $brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($beneficeryname)."', '".$amount."', '".$pcatid."', '".addslashes($pcatagoryname)."', '".$psubcatid."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '', '', '".addslashes($description)."', '".addslashes($description_bank)."', '', '', '', '', 0, '', '', '','".$providedcode."')";
			            } 
			            else{
			            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($beneficeryname)."', '".$amount."', '".$pcatid."', '".addslashes($pcatagoryname)."', '".$psubcatid."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '', '', '".addslashes($description)."', '".addslashes($description_bank)."', '', '', '', '', 0, '', '', '','".$providedcode."')";
			            }
			            $ttlamount += $amount;
			            if (trim(strtolower($paymenttype))=='imprest') {
							$imprest_amount += $amount;
			            }
			            if(strtolower($paymenttype)<>'cash' && (stristr($description_bank, "cash") &&  stristr($description_bank, "self"))){
							if($cashbrkqry){
								$cashbrkqry = $cashbrkqry.",('".$chequedate."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($beneficeryname)."','0','".$amount."','".$amount."','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."','".$cashaccountid."', '".$newreference_no."')";
							}
							else{
								$cashbrkqry = "('".$chequedate."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($beneficeryname)."','0','".$amount."','".$amount."','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."','".$cashaccountid."', '".$newreference_no."')";
							}
			            }
			        }
		    	}
				if($brkqry){
		    		$brkqry1=$insq.$brkqry;
		    		$mconn->query($brkqry1);
		    		if(mysqli_affected_rows($mconn)>0){
		    			$mqr="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid, reference_no, transaction_date, bookdate,finyear, book_amount, beforeedit_amount,transfered_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, is_intermediate_transfer, refundable_projectid,transactionno,book_source,book_status,referenceno_clb,reconcilation_process) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$chequedate."', '".$chequedate."', '".$finyear."','".$withdrownamount."', '".$withdrownamount."','".$withdrownamount."', '".$paymentmode."', '".addslashes($payto)."', '".$bankname."', '".$accountno."', '".$accountid."', '".$paymenttype."', '".$beneficerytype."', '', '', '', '', '','".$chequeno."','closetobook','completed','".$closetobook_referenceno."','".$condition."')";	
		            	$mconn->query($mqr);
		            	if(mysqli_affected_rows($mconn)>0){
		            		if (trim(strtolower($paymenttype))=='imprest') {
		            			$getpurposestring=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecategoryname",'Imprest');
								if(count($getpurposestring)>0){
						            foreach ($getpurposestring as $keypc) {
										$purpose = $keypc['purpose'];
										$purposecode = $keypc['purposecode'];
										$pcatagoryname = $keypc['pcategory'];
										$pcatid=$keypc['pcategorycode'];
										$psubcatid=$keypc['psub_categoryid'];
										$psubcat=$keypc['psub_category'];
										$paymenttype=$keypc['pcategory'];
									}
						        }
						         //Check and insert data into tbl_master_account
								$accqry="select accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and lcase(accountname)='".strtolower(addslashes($payto))."' and ifnull(tedit,'')=''"; 
								$rlt = $mconn->query($accqry);
								$impaccountid ="";
								if($rlt->num_rows>0){
									$impacrow = $rlt->fetch_assoc();
									$impaccountid = $impacrow["accountid"];
									$imprestexist = "exist multiple";
								}
								else{
									$accqry="select ifnull(max(accountid)+1,7000) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and ifnull(tedit,'')=''";
									$rlt = $mconn->query($accqry);
									if($rlt->num_rows>0){
										$impacrow = $rlt->fetch_assoc();
										$impaccountid = $impacrow["accountid"];
										$newaccqry = "insert into tbl_master_account(cmp_id,subcmp_id,projectid,accountid,account_type,accountname,accountholdername,status) values('".$companyid."', '".$subcompanyid."', '".$projectid."','".$impaccountid."','Imprest','".strtoupper(addslashes($payto))."','".strtoupper(addslashes($payto))."','unverified')";
										$mconn->query($newaccqry);
										$imprestnew= "new multiple";
									}
								}
						        //
		    					$imqr="Insert into imprest_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,imprest_payto,imprest_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid) values('".$chequedate."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($payto)."','".$imprest_amount."','0','0','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."','".$impaccountid."')";
		    					$mconn->query($imqr);
		    				}else if (trim(strtolower($paymenttype))=='cash' || (stristr($description_bank, "cash") &&  stristr($description_bank, "self"))||trim(strtolower($description_bank))=='self') {
		    					$getpurposestring=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecategoryname",'Cash');
								if(count($getpurposestring)>0){
						            foreach ($getpurposestring as $keypc) {
										$purpose = $keypc['purpose'];
										$purposecode = $keypc['purposecode'];
										$pcatagoryname = $keypc['pcategory'];
										$pcatid=$keypc['pcategorycode'];
										$psubcatid=$keypc['psub_categoryid'];
										$psubcat=$keypc['psub_category'];
										$paymenttype=$keypc['pcategory'];
									}
						        }
				        		
		    					$cshqr="Insert into cash_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,cash_payto,cash_amount,adjustment_amount,adjustedamount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description) values('".$chequedate."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$accountid."','".$newreference_no."','".addslashes($payto)."','".$ttlamount."','0','0','".$pcatid."','".addslashes($pcatagoryname)."','".$psubcatid."','".addslashes($psubcat)."','".addslashes($purpose)."','".$purposecode."','".addslashes($description_bank)."')";
		    					$mconn->query($cshqr);
		    					if($cashbrkqry){
		    						$cashbrkqry1 = $cashinsqry.$cashbrkqry;
		    						$mconn->query($cashbrkqry1);
		    					}
		    				}
		            		reconciled_updatebankstatment_closetobook($mconn,$newreference_no,$chequedate,$dbsno_bnkstatement,$closetobook_referenceno);
		            		reconciled_updateexpensesheet($mconn,$newreference_no,$dbsno_exp,$closetobook_referenceno);
		            		
		            		$rst[]=array('status'=>'Success', 'message'=>'Your transaction update successfully. Imprest Exist: '. $imprestexist.' Imprest New: '. $imprestnew);
		            	}
		            	else
		            	{
		            		$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in maintransaction');
		            	}
		            }
		            else
		            {
		            	$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in breakdown');
		            }
		    	}
			}
		$mconn->commit();
    	}
    	catch (mysqli_sql_exception $e) {
        	$mconn->rollback();
        	$rst[]=array('status'=>'Error', 'Rollbackmessage'=>$e->getMessage());
    	}
    }
    else {
    	$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in breakdown');
    } 
    return $rst;
}
function recunstructbookautomatic_newC($mconn,$companyid,$subcompanyid,$projectid,$accountid,$paymentmode,$chequedate,$chequeno,$withdrownamount,$payto,$dbsno_bnkstatement,$description_bank,$bankid,$bankname,$accountname,$accountno,$depositdetails,$condition){
	$rst=array();
	$closetobook_referenceno = $dbsno_bnkstatement;
	$bbamt=0;
	foreach ($depositdetails as $bnkey) {
		$bbamt+=$bnkey['beneficaryamount'];
	}
	$diffamt=$withdrownamount - $bbamt; 
	
	if(count($depositdetails)==1){
		$beneficerytype="Individual";
	}else if(count($depositdetails)>1){
		$beneficerytype="Multiple";
	}
	if(count($depositdetails)>0){
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    	$mconn->begin_transaction();
    	try{
	    	$imprestexist ="";
	    	$imprestnew="";
	    	$tsno=0;
		    $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction_credit");		
			if($mtso->num_rows>0){
				$trow=$mtso->fetch_assoc();
				$tsno=$trow['maxtsno'];
			}
			$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
			
			if(count($depositdetails)==1){	    	
			    $bnfcount=1;
			    $brkqry="";
			    $cashbrkqry="";
			    $purposecategory="";
			    $ttlamount=0;
			    $beneficeryname ="";
				$payto ="";
				$amount ="";
				$description ="";
				$dbsno_exp ="";
				$paymentmode ="";
				$ex_chequeno ="";
				$bookdate =""; 
				$providedcode =""; 
				$purposecode =""; 
				$purpose =""; 
				$pcatagoryname =""; 
				$pcatid =""; 
				$psubcatid =""; 
				$psubcat =""; 
				$paymenttype =""; 
				$insq="INSERT INTO tbl_maintransaction_credit_breakdown(companyid, subcompanyid, projectid, request_no, reference_no, beneficiary_name, amount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, invoice_type, invoice_no, description, description_bank, blobfile, blobfile_tblname, tds_type, tds_percent, tds_amount, documentation, doctype, documentation_path,provided_code) VALUES";
				$dbsno_exp="";
			    foreach ($depositdetails as $bnfkey) {
			    	$beneficeryname =$bnfkey['beneficaryname'];
			    	$payto =$bnfkey['depositorname'];
		            $amount =$bnfkey['depositedamount'];        
		            $description =$bnfkey['description'];                           
		            $providedcode =$bnfkey['providedcode'];
		            $purposecode =$bnfkey['purposecode'];
		            $purpose = $bnfkey['purpose'];
					$pcatagoryname = $bnfkey['categoryname'];
					$pcatid=$bnfkey['categoryid'];
					$psubcatid=$bnfkey['subcategoryid'];
					$psubcat=$bnfkey['subcategoryname'];
					//$paymenttype=$bnfkey['paymenttype'];
		            if($dbsno_exp){
			            $dbsno_exp =$dbsno_exp.','.$bnfkey['depositsno'];
		            }else{
		            	$dbsno_exp =$bnfkey['depositsno'];
		            }
		            $paymentmode ="";//$bnfkey['paymentmode'];
		            $ex_chequeno =$bnfkey['chequeno'];
		            $bookdate =$chequedate;
					$finyeartype = getfinancialyeartype($mconn,$companyid);
					$finyear = getFinancialYear($bookdate,$finyeartype);
		            if($purposecode){
			            if($brkqry){
				            $brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($beneficeryname)."', '".$withdrownamount."', '".$pcatid."', '".addslashes($pcatagoryname)."', '".$psubcatid."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '', '', '".addslashes($description)."', '".addslashes($description_bank)."', '', '', '', '', 0, '', '', '','".$providedcode."')";
			            }else{
			            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($beneficeryname)."', '".$withdrownamount."', '".$pcatid."', '".addslashes($pcatagoryname)."', '".$psubcatid."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '', '', '".addslashes($description)."', '".addslashes($description_bank)."', '', '', '', '', 0, '', '', '','".$providedcode."')";
			            }
			            $ttlamount += $amount;
			        }
		    	}
		    	if($brkqry){
		    		$brkqry1=$insq.$brkqry;
		    		$mconn->query($brkqry1);
		    		if(mysqli_affected_rows($mconn)>0){
		    			$mqr="INSERT INTO tbl_maintransaction_credit(tsno, companyid, subcompanyid, projectid, reference_no, transaction_date, bookdate,finyear, book_amount, beforeedit_amount,transfered_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, is_intermediate_transfer, refundable_projectid,transactionno,book_source,book_status,referenceno_clb,reconcilation_process) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$chequedate."', '".$chequedate."','".$finyear."', '".$withdrownamount."', '".$withdrownamount."', '".$withdrownamount."', '".$paymentmode."', '".addslashes($payto)."', '".$bankname."', '".$accountno."', '".$accountid."', '".$paymenttype."', '".$beneficerytype."', '', '', '', '', '','".$chequeno."','closetobook','completed','".$closetobook_referenceno."','".$condition."')";
		            	$mconn->query($mqr);
		            	if(mysqli_affected_rows($mconn)>0){
		            		reconciled_updatedepositbankstatment_closetobook($mconn,$newreference_no,$chequedate,$dbsno_bnkstatement,$closetobook_referenceno);
		            		reconciled_updatedepositsheet($mconn,$newreference_no,$dbsno_exp,$closetobook_referenceno);
		            		$rst[]=array('status'=>'Success', 'message'=>'Your transaction update successfully. Imprest Exist: '. $imprestexist.' Imprest New: '. $imprestnew);
		            	}else{
		            		$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in maintransaction');
		            	}
		            }
		            else{
		            	$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in breakdown');
		            }
		    	}
		    }
		$mconn->commit();
    	}
    	catch (mysqli_sql_exception $e) {
        	$mconn->rollback();
        	$rst[]=array('status'=>'Error', 'Rollbackmessage'=>$e->getMessage());
    	}
    }
    else {
    	$rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in breakdown');
    } 
    return $rst;
}
function recunstructbook_fromexceldata($mconn,$companyid,$subcompanyid,$projectid,$accountid,$paymentmode,$chequeno,$chequedate,$withdrownamount,$paytoid,$paytoname,$paytobankname,$paytobankaccountno,$paytobankifsccode,$beneficiarydeatilslist,$loginname=null,$exceldatatype=null){
	$rst=array();
	$is_intermediate_transfer="";
	$payto_accountid="";
	$from_accountno="";
	$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="0000000000";
	$account_type ="";
	$accountname ="";
	$closetobook_referenceno="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
	}
	//payto details
	$paytoname=$paytoname;
	$payto_bankname=$paytobankname;
	$payto_accountno=$paytobankaccountno;
	$payto_bankifsccode=$paytobankifsccode;
	if(count($beneficiarydeatilslist)==1){
		$beneficerytype="Individual";
	}else if(count($beneficiarydeatilslist)>1){
		$beneficerytype="Multiple";
	}
	
	if(count($beneficiarydeatilslist)>0){
    	$tsno=0;
	    $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");		
		if($mtso->num_rows>0){
			$trow=$mtso->fetch_assoc();
			$tsno=$trow['maxtsno'];
		}
		$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
	    $bnfcount=1;
	    $brkqry="";
	    $beneficeryname ="";
		$amount ="";
		$description ="";
		$purposecode ="";
		$pcatid ="";
		$pcat ="";
		$paymenttype ="";
		$psubcatid ="";
		$psubcat ="";
		$purpose ="";
		$insq="INSERT INTO tbl_maintransaction_breakdown(companyid,subcompanyid,projectid,request_no,reference_no,beneficiary_name,amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description) VALUES";
		$dbsno_exp="";
		if($chequedate){
            $bookdate = $chequedate;
        }else{
        	$bookdate =date('Y-m-d');
        }
        $updslq="";
        $plslq="";
        $ttlamount=0;
        // print_r($beneficiarydeatilslist);
        $brkqry="";
        foreach ($beneficiarydeatilslist as $bnfkey) {
	    	$beneficiaryname =$bnfkey['beneficiaryname'];
	    	$expenseamount =$bnfkey['expenseamount'];        
            $purposecode =$bnfkey['purposecode'];
            $description =$bnfkey['description'];
            if($purposecode){
	            $getpurposestring=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
				if(count($getpurposestring)>0){
		            foreach ($getpurposestring as $keypc) {
						$pcat = $keypc['pcategory'];
						$pcatid=$keypc['pcategorycode'];
						$purpose = $keypc['purpose'];
						$purposecode = $keypc['purposecode'];
						$psubcatid=$keypc['psub_categoryid'];
						$psubcat=$keypc['psub_category'];
						$paymenttype=$keypc['pcategory'];
					}
		        }
		        $updslq.="update tbl_import_excelsheetdata set referenceno='".$newreference_no."',purposecode='".$purposecode."', uploadflag=null where sno='".$bnfkey['dbsno']."'";
		        if($purposecode!="" && $paytoid!=""){
		        	if($exceldatatype=="salaryexpense"){
		        		$plslq.="update payroll_employeemaster set purposecode='".$purposecode."', purpose='".$purpose."', uploadflag=null where empid='".$paytoid."'";
		        	}
		        }
		       	if($brkqry){
		            $brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes(trim($bnfkey['beneficiaryname']))."', '".addslashes(trim($bnfkey['expenseamount']))."', '".addslashes(trim($pcatid))."', '".addslashes(trim($pcat))."', '".addslashes(trim($psubcatid))."', '".addslashes(trim($psubcat))."', '".addslashes(trim($purpose))."', '".addslashes(trim($purposecode))."', '".addslashes(trim($bnfkey['description']))."')";
	            } else{
	            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes(trim($bnfkey['beneficiaryname']))."', '".addslashes(trim($bnfkey['expenseamount']))."', '".addslashes(trim($pcatid))."', '".addslashes(trim($pcat))."', '".addslashes(trim($psubcatid))."', '".addslashes(trim($psubcat))."', '".addslashes(trim($purpose))."', '".addslashes(trim($purposecode))."', '".addslashes(trim($bnfkey['description']))."')";
	            }
	            $ttlamount += $bnfkey['expenseamount'];
	        }else{
	        	$rst[]=array('status'=>'Error', 'message'=>'Purpose missing');
	        }
    	}
    	if($brkqry){
    		$brkqry1=$insq.$brkqry;
	    	$mconn->query($brkqry1);    		
    		if(mysqli_affected_rows($mconn)>0){
    			$booksource = "By app on date".date('Y-m-d h:i:s')." by ".$loginname;
    			$mqr="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid,  reference_no, transaction_date, bookdate, book_amount, beforeedit_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytobankname, paytoaccountno, paytoifsc,book_source,book_status) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$chequedate."', '".$bookdate."', '".$ttlamount."', '".$ttlamount."', '".$paymentmode."', '".addslashes(trim($paytoname))."', '".addslashes(trim($bankname))."', '".addslashes(trim($accountno))."', '".addslashes(trim($accountid))."', '".addslashes(trim($paymenttype))."', '".addslashes(trim($beneficerytype))."', '".addslashes(trim($payto_bankname))."', '".addslashes(trim($payto_accountno))."', '".addslashes(trim($payto_bankifsccode))."','".addslashes(trim($booksource))."','completed')";
            	$mconn->query($mqr);
            	if(mysqli_affected_rows($mconn)>0){
            		$mconn->query($updslq);
            		if($plslq){
		    			$mconn->query($plslq);
            		}
            		$rst[]=array('status'=>'Success', 'message'=>'Book created successfully  '.mysqli_info($mconn), 'referenceno'=>$newreference_no);
            	}else{
            		$rst[]=array('status'=>'Error', 'message'=>'Book did not created, Error in maintransaction');
            	}
            }else{
            	$rst[]=array('status'=>'Error', 'message'=>'Book did not created, Error in breakdown');
            }
    	}else {
	    	$rst[]=array('status'=>'Error', 'message'=>'Book did not created breakdown query, Error in breakdown');
	    }	   
    }else {
    	$rst[]=array('status'=>'Error', 'message'=>'Book did not created, Error in breakdown');
    }    
    return $rst;
}


function recunstructbook_credit($authconn,$mconn,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$paymentmode,$chequeno,$chequedate,$withdrownamount,$paytoid,$beneficiarydeatilslist,$loginname=null,$purposecategoryid=null,$purposesubcategoryid=null,$purposecode=null,$isforproduction=null){
	$rst=array();
	//payto details
	$paytoname="";
	$beneficiaryname="";
	$is_intermediate_transfer="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$payto_accountid="";
	$from_accountno="";
	$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="";
	$account_type ="";
	$accountname ="";
	$closetobook_referenceno="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
		$beneficiaryname=$brow['bankname']."-".$brow['accountno'];
	}
	/*if(strtolower($purposecategoryid)=="2" || strtolower($purposecategoryid)=="02" || strtolower($purposecategoryid)=="3" || strtolower($purposecategoryid)=="03" || strtolower($purposecategoryid)=="5" || strtolower($purposecategoryid)=="05" ||  strtolower($purposecategoryid)=="07" || strtolower($purposecategoryid)=="7"){
		$allqry=$mconn->query("select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountid in('".str_replace(",", "','", $paytoid)."')");
		if($allqry->num_rows>0){
			while ($pkey=$allqry->fetch_assoc()) {
				$paytoname=$pkey['accountname'].' ('.$pkey['accountno'].')';
				$beneficiaryname=$pkey['accountname'].' ('.$pkey['accountno'].')';
				$payto_bankname=$pkey['bankname'];
				$payto_accountno=$pkey['accountno'];
				$payto_bankifsccode=$pkey['ifsccode'];
				if(strtolower($purposecategoryid)=="2" || strtolower($purposecategoryid)=="02" ||  strtolower($purposecategoryid)=="07" || strtolower($purposecategoryid)=="7"){
						$is_intermediate_transfer="yes";
					}
				$payto_accountid=$pkey['accountid'];
			}
		}else{
			$pppd=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
			$default_beneficiary="";
			if(count($pppd)>0){
				foreach ($pppd as $pppdkey) {
					$default_beneficiary=$pppdkey['default_beneficiary'];
				}
			}
			if($default_beneficiary=="employeeloan"){
				$ppt=employeemaster($mconn,$companyid,$subcompanyid,$projectid,"employeeid",$paytoid);
				foreach ($ppt as $pkey) {
					$paytoname = $pkey['paytoname'];
					$beneficiaryname=$pkey['paytoname'];
					$payto_bankname=$pkey['bankname'];
					$payto_accountno=$pkey['accountno'];
					$payto_bankifsccode=$pkey['ifsccode'];
					$is_intermediate_transfer="";
					$payto_accountid=$paytoid;
				}
			}else{
				$rst[]=array('status'=>'Error', 'message'=>'Pay to details did not matched with our master table, Error in payto list');
				echo json_encode($rst);
				die();
			}
		}
	}else{
		$allqry = $mconn->query("select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and paytoname is not null and paytoname<>'' and paytoid='".$paytoid."' order by paytoname");
		if($allqry->num_rows>0){
			while ($pkey=$allqry->fetch_assoc()) {
				$paytoname=$pkey['paytoname'];
				$beneficiaryname=$pkey['paytoname'];
				$payto_bankname=$pkey['bankname'];
				$payto_accountno=$pkey['accountno'];
				$payto_bankifsccode=$pkey['ifsccode'];
			}
		}else{
			$rst[]=array('status'=>'Error', 'message'=>'Pay to details did not matched with our master table, Error in payto list');
			echo json_encode($rst);
			die();
		}
	}*/
	$payerinfo=getpayerinfo($mconn,$companyid,$subcompanyid,"payerid",$paytoid);
	if(count($payerinfo)>0){
		foreach ($payerinfo as $pkey) {
			$paytoname=$pkey['payername'];
			$payto_bankname=$pkey['tobankname'];
			$payto_accountno=$pkey['toaccountno'];
			$payto_bankifsccode=$pkey['tobankifsc'];
		}
	}else{
		$rst[]=array('status'=>'Error', 'message'=>'Pay to details did not matched with our master table, Error in payto list');
		echo json_encode($rst);
		die();
	}
	// -------------------------------------
	if(count($beneficiarydeatilslist)==1){
		$beneficerytype="Individual";
	}else if(count($beneficiarydeatilslist)>1){
		$beneficerytype="Multiple";
	}
	
	$bbamt=0;
	foreach ($beneficiarydeatilslist as $bnkey) {
		$bbamt+=(float)$bnkey['expenseamount'];
	}
	$diffamt=$withdrownamount - $bbamt;
	// print_r($beneficiarydeatilslist);
	// die();
	if(count($beneficiarydeatilslist)>0){
    	$tsno=0;
	    $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction_credit");		
		if($mtso->num_rows>0){
			$trow=$mtso->fetch_assoc();
			$tsno=$trow['maxtsno'];
		}
		$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
	    $bnfcount=1;
	    $brkqry="";
	    $purposecategory="";
	    $ttlamount=0;
	    $beneficeryname ="";
		$amount ="";
		$description ="";
		$purposecode ="";
		$pcatid ="";
		$pcat ="";
		$paymenttype ="";
		$psubcatid ="";
		$psubcat ="";
		$purpose ="";
		// invoicedate
		$insq="INSERT INTO tbl_maintransaction_credit_breakdown(companyid,subcompanyid,projectid,subprojectid,request_no,reference_no,beneficiary_name,amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,invoice_type,invoice_no,invoicedate,description,documentation_path,audiofilepath,voiceinstid,srm_attsno,srm_emailsno,taxableamount,gstamount,pono) VALUES";
		$dbsno_exp="";
		if($chequedate){
            $bookdate = $chequedate;
        }else{
        	$bookdate =date('Y-m-d');
        }
	    foreach ($beneficiarydeatilslist as $bnfkey) {
	    	$expenseamount =$bnfkey['expenseamount'];        
            $purposecode =$bnfkey['purposecode'];
            $description =$bnfkey['description'];                           
            $attachmentpath =$bnfkey['attachmentpath'];
            $taxableamount = $bnfkey['taxableamount'];
			$invoicegstamount = $bnfkey['invoicegstamount'];
			$ponumber = $_POST['ponumber'];
            
            $srm_attsno = $bnfkey['srm_attsno'];
            $srm_emailsno = $bnfkey['srm_emailsno'];
            if($bnfkey['voiceinstid']!=""){
            	$voiceinfo=voiceinstructiondetails($authconn,$mconn,$companyid,$subcompanyid,$projectid,"instructionid",$bnfkey['voiceinstid']);
            	if(count($voiceinfo)>0){
            		$voicefilepath="";
					$voicedocumentpath="";
            		foreach ($voiceinfo as $vvkey) {
            			$voicefilepath = $vvkey['filepath'];
            			$voicedocumentpath = $vvkey['filepath2'];
            		}
            	}
            }
            // if(count($bnfkey['invoicedata'])>0){
			// 	savetoinvoicedata($mconn,$companyid,$subcompanyid,$projectid,'',$newreference_no,$bnfkey['invoicedate'],$bnfkey['invoiceno'],$bnfkey['invoicedata'],$bnfkey['totalrowsamount'], $bnfkey['totalgstamount'], $bnfkey['totalrowsdiscount'], $bnfkey['otherdiscount'], $bnfkey['totalpayableamount']);
			// }
            if($purposecode){	            
	            $getpurposestring=getcreditpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
				if(count($getpurposestring)>0){
		            foreach ($getpurposestring as $keypc) {
						$pcat = $keypc['pcategory'];
						$pcatid=$keypc['pcategorycode'];
						$purpose = $keypc['purpose'];
						$purposecode = $keypc['purposecode'];
						$psubcatid=$keypc['psub_categoryid'];
						$psubcat=$keypc['psub_category'];
						$paymenttype=$keypc['pcategory'];
					}
		        }
		       	if($brkqry){
		            $brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$expenseamount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".$purpose."', '".$purposecode."', '".$bnfkey['invoicetype']."', '".$bnfkey['invoiceno']."', '".$bnfkey['invoicedate']."', '".addslashes($bnfkey['description'])."', '".addslashes(str_replace("../", "",$bnfkey['attachmentpath']))."', '".addslashes(str_replace("../", "",$bnfkey['audiofilepath']))."','".$bnfkey['voiceinstid']."', '".$srm_attsno."', '".$srm_emailsno."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	            } 
	            else{
	            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$expenseamount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".$purpose."', '".$purposecode."', '".$bnfkey['invoicetype']."', '".$bnfkey['invoiceno']."', '".$bnfkey['invoicedate']."', '".addslashes($bnfkey['description'])."', '".addslashes(str_replace("../", "",$bnfkey['attachmentpath']))."', '".addslashes(str_replace("../", "",$bnfkey['audiofilepath']))."','".$bnfkey['voiceinstid']."', '".$srm_attsno."', '".$srm_emailsno."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	            }
	            $ttlamount += $expenseamount;
	            
	        }
    	}
    	if($brkqry){
    		$brkqry1=$insq.$brkqry;
    		$mconn->query($brkqry1);
    		if(mysqli_affected_rows($mconn)>0){
    			$booksource = "By app on date".date('Y-m-d h:i:s')." by ".$loginname;
    			$mqr="INSERT INTO tbl_maintransaction_credit(tsno, companyid, subcompanyid, projectid,subprojectid,  reference_no, transaction_date, bookdate, book_amount, beforeedit_amount, transaction_type,paytoid, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytobankname, paytoaccountno, paytoifsc, is_intermediate_transfer, refundable_projectid,transactionno,book_source,book_status,is_purchaseforproduction) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '".$newreference_no."', '".$chequedate."', '".$bookdate."', '".$ttlamount."', '".$ttlamount."', '".$paymentmode."','".$paytoid."', '".addslashes($paytoname)."', '".$bankname."', '".$accountno."', '".$accountid."', '".$paymenttype."', '".$beneficerytype."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', '".$is_intermediate_transfer."', '','".$chequeno."','".$booksource."','completed','".$isforproduction."')";
            	$mconn->query($mqr);
            	if(mysqli_affected_rows($mconn)>0){
            		if($bnfkey['voiceinstid']){
            			$mconn->query("update tbl_paymentrequest_voiceinstruction set requestno='".$newreference_no."', requeststatus='done', requestedby='".$loginname."', requestdate='".date('Y-m-d')."', uploadflag=null where instructionid='".$bnfkey['voiceinstid']."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
							
							$mconn->query("update tbl_expencesinexcelsheet set request_no='".$newreference_no."', status='".$newreference_no."', closetobook_status='done by ".$loginname." with reference no ".$newreference_no." on date ".date('Y-m-d')."', uploadflag=null where voiceinstid='".$bnfkey['voiceinstid']."' and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and (tedit is null or tedit='')");
            		}
            		$rst[]=array('status'=>'Success', 'message'=>'Book entry of payment to '.addslashes($paytoname).' for the amount of Rs.'.$ttlamount.' has been created successfully.', 'dataupdatewith'=>'maintransactiononly', 'referenceno'=>$newreference_no, 'requestno'=>'');
            	}else{
            		$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment  did not created, Error in maintransaction', 'dataupdatewith'=>'');
            	}
            }else{
            	$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment did not created, Error in breakdown');
            }
    	}	   
    }else {
    	$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment did not update, Error in breakdown');
    }    
    return $rst;
}
function verifiedpaytoandpurposetobookdata_credit($conn,$companyid,$subcompanyid,$projectid,$referenceno,$isverifypayto,$isverifypurpose,$loginname){
	$vrymsg=array();
	if($referenceno){
		$conn->begin_transaction();
		try {
	
			// Prepare the query
			$verify = "verified on date ".date('Y-m-d H:i:s')." by ".$loginname;
			if($isverifypayto){
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET verifypayto =? WHERE reference_no = ?");		    
			    // Bind parameters
			    $stmt->bind_param("ss",$verify,$referenceno);
			    // Execute the prepared statement
			    $stmt->execute();
				// Commit transaction
				$conn->commit();
				// Close the prepared statement
				$stmt->close();
			}
			if($isverifypurpose){
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET verifypurpose=? WHERE reference_no = ?");		    
			    // Bind parameters
			    $stmt->bind_param("ss",$verify,$referenceno);
			    // Execute the prepared statement
			    $stmt->execute();
				// Commit transaction
				$conn->commit();
				// Close the prepared statement
				$stmt->close();
			}
			// echo "All records updated successfully.";
			$vrymsg=array('status'=>'Success', 'message'=>'Update and verified payto and purpose successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $vrymsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	return $vrymsg;
}
function recunstructbook($authconn,$mconn,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$paymentmode,$chequeno,$chequedate,$withdrownamount,$paytoid,$beneficiarydeatilslist,$loginname=null,$purposecategoryid=null,$purposesubcategoryid=null,$purposecode=null,$isforproduction=null,$remarks=null){
	$rst=array();
	$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="";
	$account_type ="";
	$accountname ="";
	$closetobook_referenceno="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
	}
	//payto details
	$paytoname="";
	$beneficiaryname="";
	$is_intermediate_transfer="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$payto_accountid="";
	$from_accountno="";
	if(intval($purposecategoryid)==2 ||  intval($purposecategoryid)==3  || intval($purposecategoryid)==5 || intval($purposecategoryid)==6 || intval($purposecategoryid)==7 || intval($purposecategoryid)==8){
		$acqry="select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and paytoid in('".str_replace(",", "','", $paytoid)."')";
		$allqry=$mconn->query($acqry);
		if($allqry->num_rows>0){
			while ($pkey=$allqry->fetch_assoc()) {
				$paytoname=$pkey['accountname'];
				$beneficiaryname=$pkey['accountname'];
				$payto_bankname=$pkey['bankname'];
				$payto_accountno=$pkey['accountno'];
				$payto_bankifsccode=$pkey['ifsccode'];
				$is_intermediate_transfer="yes";
				$payto_accountid=$pkey['accountid'];
			}
		}else{			
			$rst[]=array('status'=>'Error', 'message'=>'Pay to details did not matched with our master table, Error in payto list 11111');
			echo json_encode($rst);
			die();			
		}
	}else{
		$allqry = $mconn->query("select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payto_master where (tedit is null or tedit='') and cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and paytoname is not null and paytoname<>'' and paytoid='".$paytoid."' order by paytoname");
		if($allqry->num_rows>0){
			while ($pkey=$allqry->fetch_assoc()) {
				$paytoname=$pkey['paytoname'];
				// $beneficiaryname=$pkey['paytoname'];
				$payto_bankname=$pkey['bankname'];
				$payto_accountno=$pkey['accountno'];
				$payto_bankifsccode=$pkey['ifsccode'];
			}
		}else{
			$rst[]=array('status'=>'Error', 'message'=>'Pay to details did not matched with our master table, Error in payto list 33333-'.$purposecategoryid);
			echo json_encode($rst);
			die();
		}
	}

	// -------------------------------------
	if(count($beneficiarydeatilslist)==1){
		$beneficerytype="Individual";
	}else if(count($beneficiarydeatilslist)>1){
		$beneficerytype="Multiple";
	}
	
	$bbamt=0;
	foreach ($beneficiarydeatilslist as $bnkey) {
		$bbamt+=(float)$bnkey['expenseamount'];
	}
	$diffamt=$withdrownamount - $bbamt;

	
	if(count($beneficiarydeatilslist)>0){
    	$tsno=0;
	    $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");		
		if($mtso->num_rows>0){
			$trow=$mtso->fetch_assoc();
			$tsno=$trow['maxtsno'];
		}
		$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
	    $bnfcount=1;
	    $brkqry="";
	    $purposecategory="";
	    $ttlamount=0;
	    $beneficeryname ="";
		$amount ="";
		$description ="";
		$purposecode ="";
		$pcatid ="";
		$pcat ="";
		$paymenttype ="";
		$psubcatid ="";
		$psubcat ="";
		$purpose ="";
		// invoicedate
		$insq="INSERT INTO tbl_maintransaction_breakdown(companyid,subcompanyid,projectid,subprojectid,request_no,reference_no,beneficiary_name,amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,invoice_type,invoice_no,invoicedate,description,documentation_path,audiofilepath,voiceinstid,srm_attsno,srm_emailsno,taxableamount,gstamount,pono) VALUES";
		$dbsno_exp="";
		if($chequedate){
            $bookdate = $chequedate;
        }else{
        	$bookdate =date('Y-m-d');
        }
        $tg=0;
	    foreach ($beneficiarydeatilslist as $bnfkey) {
	    	$document_status ="";
	    	$extension="";
	    	$newfilename="";
	    	$distpath="";
	    	$newfilepath="";
	    	if($bnfkey['attachmentpath']){
		    	$document_status = "completed";
				$temp = explode(".", $bnfkey['attachmentpath']);
				$extension=end($temp);
				$newfilename = round(microtime(true)).'_'.$tg. '.' . end($temp);
				$tg++;
				$dir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$newreference_no."/";
				if ( !is_dir( $dir ) ) {
				    mkdir($dir, 0755);       
				}
				$distpath =$dir.$newfilename;
				if(rename($bnfkey['attachmentpath'], $distpath)){
					removeEmptyDirectory($bnfkey['attachmentpath']);
				}
				$newfilepath = str_replace("../", "", $distpath);
			}


	    	$beneficiaryname =$bnfkey['beneficiaryname'];
	    	$expenseamount =$bnfkey['expenseamount'];        
            $purposecode =$bnfkey['purposecode'];
            $description =$bnfkey['description'];                           
            $attachmentpath =$bnfkey['attachmentpath'];
            $taxableamount = $bnfkey['taxableamount'];
			$invoicegstamount = $bnfkey['invoicegstamount'];
			$ponumber = $_POST['ponumber'];
            
            $srm_attsno = $bnfkey['srm_attsno'];
            $srm_emailsno = $bnfkey['srm_emailsno'];
            if($bnfkey['voiceinstid']!=""){
            	$voiceinfo=voiceinstructiondetails($authconn,$mconn,$companyid,$subcompanyid,$projectid,"instructionid",$bnfkey['voiceinstid']);
            	if(count($voiceinfo)>0){
            		$voicefilepath="";
					$voicedocumentpath="";
            		foreach ($voiceinfo as $vvkey) {
            			$voicefilepath = $vvkey['filepath'];
            			$voicedocumentpath = $vvkey['filepath2'];
            		}
            	}
            }
            if($purposecode){	            
	            $getpurposestring=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
				if(count($getpurposestring)>0){
		            foreach ($getpurposestring as $keypc) {
						$pcat = $keypc['pcategory'];
						$pcatid=$keypc['pcategorycode'];
						$purpose = $keypc['purpose'];
						$purposecode = $keypc['purposecode'];
						$psubcatid=$keypc['psub_categoryid'];
						$psubcat=$keypc['psub_category'];
						$paymenttype=$keypc['pcategory'];
					}
		        }
		       	if($brkqry){
		            $brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$expenseamount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".addslashes($purpose)."', '".$purposecode."', '".$bnfkey['invoicetype']."', '".$bnfkey['invoiceno']."', '".$bnfkey['invoicedate']."', '".addslashes($bnfkey['description'])."', '".addslashes(str_replace("../", "",$newfilepath))."', '".addslashes(str_replace("../", "",$bnfkey['audiofilepath']))."','".$bnfkey['voiceinstid']."', '".$srm_attsno."', '".$srm_emailsno."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	            } 
	            else{
	            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$expenseamount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".addslashes($purpose)."', '".$purposecode."', '".$bnfkey['invoicetype']."', '".$bnfkey['invoiceno']."', '".$bnfkey['invoicedate']."', '".addslashes($bnfkey['description'])."', '".addslashes(str_replace("../", "",$newfilepath))."', '".addslashes(str_replace("../", "",$bnfkey['audiofilepath']))."','".$bnfkey['voiceinstid']."', '".$srm_attsno."', '".$srm_emailsno."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	            }
	            $ttlamount += $expenseamount;
	            
	        }
    	}
    	// echo "dd: ".$payto_accountid."', '".$is_intermediate_transfer;
    	// die();
    	if($brkqry){
    		$brkqry1=$insq.$brkqry;
    		$mconn->query($brkqry1);
    		if(mysqli_affected_rows($mconn)>0){
    			$booksource = "By app on date".date('Y-m-d h:i:s')." by ".$loginname;
    			$mqr="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid,subprojectid,  reference_no, bookdate, book_amount, beforeedit_amount, transaction_type,paytoid, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytobankname, paytoaccountno, paytoifsc, paytoaccountid, is_intermediate_transfer, refundable_projectid, transaction_date,transactionno,transfered_amount,book_source,book_status,is_purchaseforproduction,description) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '".$newreference_no."', '".$bookdate."', '".$ttlamount."', '".$ttlamount."', '".$paymentmode."','".$paytoid."', '".addslashes($paytoname)."', '".$bankname."', '".$accountno."', '".$accountid."', '".$paymenttype."', '".$beneficerytype."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', '".$payto_accountid."', '".$is_intermediate_transfer."', '', '".$chequedate."','".$chequeno."', '".$ttlamount."','".$booksource."','completed','".$isforproduction."','".addslashes($remarks)."')";
            	$mconn->query($mqr);
            	if(mysqli_affected_rows($mconn)>0){
            		if($bnfkey['voiceinstid']){
            			$mconn->query("update tbl_paymentrequest_voiceinstruction set requestno='".$newreference_no."', requeststatus='done', requestedby='".$loginname."', requestdate='".date('Y-m-d')."', uploadflag=null where instructionid='".$bnfkey['voiceinstid']."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
            				
							$mconn->query("update tbl_expencesinexcelsheet set request_no='".$newreference_no."', status='".$newreference_no."', closetobook_status='done by ".$loginname." with reference no ".$newreference_no." on date ".date('Y-m-d')."', uploadflag=null where voiceinstid='".$bnfkey['voiceinstid']."' and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and (tedit is null or tedit='')");
            		}
            		$rst[]=array('status'=>'Success', 'message'=>'Book entry of payment to '.addslashes($paytoname).' for the amount of Rs.'.$ttlamount.' has been created successfully.', 'dataupdatewith'=>'maintransactiononly', 'referenceno'=>$newreference_no, 'requestno'=>'');
            	}else{
            		$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment  did not created, Error in maintransaction', 'dataupdatewith'=>'');
            	}
            }else{
            	$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment did not created, Error in breakdown');
            }
    	}	   
    }else {
    	$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment did not update, Error in breakdown');
    }    
    return $rst;
}
function recunstructbook_taxInvoice($authconn,$mconn,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$paymentmode,$chequeno,$chequedate,$withdrownamount,$paytoid,$paytogstno,$beneficiarydeatilslist,$invoiceno,$invoicedate,$invoicerowdata,$isforproduction,$loginname){
	$rst=array();
	$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="";
	$account_type ="";
	$accountname ="";
	$closetobook_referenceno="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
	}
	//payto details
	$paytoname="";
	$beneficiaryname="";
	$is_intermediate_transfer="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$payto_accountid="";
	$from_accountno="";
	
	$allqry = $mconn->query("select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and paytoname is not null and paytoname<>'' and paytoid='".$paytoid."' order by paytoname");
	if($allqry->num_rows>0){
		while ($pkey=$allqry->fetch_assoc()) {
			$paytoname=$pkey['paytoname'];
			$beneficiaryname=$pkey['paytoname'];
			$payto_bankname=$pkey['bankname'];
			$payto_accountno=$pkey['accountno'];
			$payto_bankifsccode=$pkey['ifsccode'];
		}
	}else{
		$rst[]=array('status'=>'Error', 'message'=>'Pay to details did not matched with our master table, Error in payto list');
		echo json_encode($rst);
		die();
	}
	// -------------------------------------
	if(count($beneficiarydeatilslist)==1){
		$beneficerytype="Individual";
	}else if(count($beneficiarydeatilslist)>1){
		$beneficerytype="Multiple";
	}
	
	$bbamt=0;
	foreach ($beneficiarydeatilslist as $bnkey) {
		$bbamt+=(float)$bnkey['expenseamount'];
	}
	$diffamt=$withdrownamount - $bbamt;
	// print_r($beneficiarydeatilslist);
	// die();
	if(count($beneficiarydeatilslist)>0){
    	$tsno=0;
	    $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");		
		if($mtso->num_rows>0){
			$trow=$mtso->fetch_assoc();
			$tsno=$trow['maxtsno'];
		}
		$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
	    $bnfcount=1;
	    $brkqry="";
	    $purposecategory="";
	    $ttlamount=0;
	    $beneficeryname ="";
		$amount ="";
		$description ="";
		$purposecode ="";
		$pcatid ="";
		$pcat ="";
		$paymenttype ="";
		$psubcatid ="";
		$psubcat ="";
		$purpose ="";
		// invoicedate
		$insq="INSERT INTO tbl_maintransaction_breakdown(companyid,subcompanyid,projectid,subprojectid,request_no,reference_no,beneficiary_name,amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,invoice_type,invoice_no,invoicedate,description,documentation_path,audiofilepath,voiceinstid,srm_attsno,srm_emailsno,taxableamount,gstamount,pono) VALUES";
		$dbsno_exp="";
		if($chequedate){
            $bookdate = $chequedate;
        }else{
        	$bookdate =date('Y-m-d');
        }
	    foreach ($beneficiarydeatilslist as $bnfkey) {
	    	$beneficiaryname = $bnfkey['beneficiaryname'];
			$expenseamount = $bnfkey['expenseamount'];
	        $invoiceno = $bnfkey['invoiceno'];
	        $invoicedate = $bnfkey['invoicedate'];
	        $invoicetype = $bnfkey['invoicetype'];
	        $description = $bnfkey['description'];
	        $purposecode = $bnfkey['purposecode'];
	        $isforstock = $bnfkey['isforstock'];
			$attachmentpath =$bnfkey['attachmentpath'];
			
			$document_status = "completed";
			$temp = explode(".", $bnfkey['attachmentpath']);
			$extension=end($temp);
			$newfilename = round(microtime(true)).'_'.$tg. '.' . end($temp);
			$tg++;
			$dir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$newreference_no."/";
			if ( !is_dir( $dir ) ) {
			    mkdir($dir, 0755);       
			}
			$distpath =$dir.$newfilename;
			if(rename($bnfkey['attachmentpath'], $distpath)){
				removeEmptyDirectory($bnfkey['attachmentpath']);
			}
			$newfilepath = str_replace("../", "", $distpath);

			$audiofilepath =$dtrow['audiofilepath'];
			$voiceinstid =$dtrow['voiceinstid'];
	        $invoicegstamount = $bnfkey['invoicegstamount'];
			$taxableamount = $bnfkey['taxableamount'];
			$ponumber = $bnfkey['ponumber'];
			$srm_attsno = $bnfkey['srm_attsno'];
	        $srm_emailsno = $bnfkey['srm_emailsno'];
            if($bnfkey['voiceinstid']!=""){
            	$voiceinfo=voiceinstructiondetails($authconn,$mconn,$companyid,$subcompanyid,$projectid,"instructionid",$bnfkey['voiceinstid']);
            	if(count($voiceinfo)>0){
            		$voicefilepath="";
					$voicedocumentpath="";
            		foreach ($voiceinfo as $vvkey) {
            			$voicefilepath = $vvkey['filepath'];
            			$voicedocumentpath = $vvkey['filepath2'];
            		}
            	}
            }
            
            if($purposecode){	            
	            $getpurposestring=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
				if(count($getpurposestring)>0){
		            foreach ($getpurposestring as $keypc) {
						$pcat = $keypc['pcategory'];
						$pcatid=$keypc['pcategorycode'];
						$purpose = $keypc['purpose'];
						$purposecode = $keypc['purposecode'];
						$psubcatid=$keypc['psub_categoryid'];
						$psubcat=$keypc['psub_category'];
						$paymenttype=$keypc['pcategory'];
					}
		        }
		       	if($brkqry){
		            $brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$expenseamount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".$purpose."', '".$purposecode."', '".$bnfkey['invoicetype']."', '".$bnfkey['invoiceno']."', '".$bnfkey['invoicedate']."', '".addslashes($bnfkey['description'])."', '".addslashes(str_replace("../", "",$newfilepath))."', '".addslashes(str_replace("../", "",$bnfkey['audiofilepath']))."','".$bnfkey['voiceinstid']."', '".$srm_attsno."', '".$srm_emailsno."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	            } 
	            else{
	            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$expenseamount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".$purpose."', '".$purposecode."', '".$bnfkey['invoicetype']."', '".$bnfkey['invoiceno']."', '".$bnfkey['invoicedate']."', '".addslashes($bnfkey['description'])."', '".addslashes(str_replace("../", "",$newfilepath))."', '".addslashes(str_replace("../", "",$bnfkey['audiofilepath']))."','".$bnfkey['voiceinstid']."', '".$srm_attsno."', '".$srm_emailsno."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	            }
	            $ttlamount += $expenseamount;
	            
	        }
    	}
    	if($brkqry){
    		$brkqry1=$insq.$brkqry;
    		$mconn->query($brkqry1);
    		if(mysqli_affected_rows($mconn)>0){
    			$booksource = "By app on date".date('Y-m-d h:i:s')." by ".$loginname;
    			$mqr="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid,subprojectid,  reference_no, bookdate, book_amount, beforeedit_amount, transaction_type,paytoid, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytobankname, paytoaccountno, paytoifsc, is_intermediate_transfer, refundable_projectid, transaction_date,transactionno,transfered_amount,book_source,book_status,is_purchaseforproduction) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '".$newreference_no."', '".$bookdate."', '".$ttlamount."', '".$ttlamount."', '".$paymentmode."','".$paytoid."', '".addslashes($paytoname)."', '".$bankname."', '".$accountno."', '".$accountid."', '".$paymenttype."', '".$beneficerytype."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', '".$is_intermediate_transfer."', '', '".$chequedate."','".$chequeno."', '".$ttlamount."','".$booksource."','completed','".$isforproduction."')";
            	$mconn->query($mqr);
            	if(mysqli_affected_rows($mconn)>0){
            		if(count($invoicerowdata)>0){
            			$newrequestno='';
						savetoinvoicedata($mconn,$companyid,$subcompanyid,$projectid,$newrequestno,$newreference_no,$invoicedate,$invoiceno,$invoicerowdata,$paytoid,$paytogstno,$loginname);
					}
            		if($bnfkey['voiceinstid']){
            			$mconn->query("update tbl_paymentrequest_voiceinstruction set requestno='".$newreference_no."', requeststatus='done', requestedby='".$loginname."', requestdate='".date('Y-m-d')."', uploadflag=null where instructionid='".$bnfkey['voiceinstid']."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
							
							$mconn->query("update tbl_expencesinexcelsheet set request_no='".$newreference_no."', status='".$newreference_no."', closetobook_status='done by ".$loginname." with reference no ".$newreference_no." on date ".date('Y-m-d')."', uploadflag=null where voiceinstid='".$bnfkey['voiceinstid']."' and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and (tedit is null or tedit='')");
            		}
            		$rst[]=array('status'=>'Success', 'message'=>'Book entry of payment to '.addslashes($paytoname).' for the amount of Rs.'.$ttlamount.' has been created successfully.', 'dataupdatewith'=>'maintransactiononly', 'referenceno'=>$newreference_no, 'requestno'=>'');
            	}else{
            		$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment  did not created, Error in maintransaction', 'dataupdatewith'=>'');
            	}
            }else{
            	$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment did not created, Error in breakdown');
            }
    	}	   
    }else {
    	$rst[]=array('status'=>'Error', 'message'=>'Book entry of payment did not update, Error in breakdown');
    }    
    return $rst;
}
function reconstruct_requestbyapp_forother($mconn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid=null,$accountid,$paymentmode,$chequeno,$chequedate,$withdrownamount,$paytoid,$documenttype,$filepath_fromemail,$attsno_fromemail,$emailsno_fromemail,$beneficiarydeatilslist,$requesttypename,$loginname,$paymentduedate=null,$isforproduction=null,$purposetype=null){
	$rst=array();
	//payto details
	
	$allqry = $mconn->query("select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and paytoid in('".str_replace(",", "','", $paytoid)."')");
	if($allqry->num_rows>0){
		while ($pkey=$allqry->fetch_assoc()) {
			$paytoname=$pkey['accountname'];
			$payto_bankname=$pkey['bankname'];
			$payto_accountno=$pkey['accountno'];
			$payto_bankifsccode=$pkey['ifsccode'];
		}
	}else{
		$allqry = $mconn->query("select sno,paytoid, paytoname, accountno, bankname, ifsccode, iban, swift, sortcode FROM tbl_payto_master where cmp_id='".$companyid."' and subcmp_id = '".$subcompanyid."' and paytoid='".$paytoid."' and paytoname is not null and paytoname<>'' order by paytoname");
		if($allqry->num_rows>0){
			while ($pkey=$allqry->fetch_assoc()) {
				$paytoname=$pkey['paytoname'];
				$payto_bankname=$pkey['bankname'];
				$payto_accountno=$pkey['accountno'];
				$payto_bankifsccode=$pkey['ifsccode'];
			}
		}
		// $rst[]=array('status'=>'Error', 'message'=>'Your transaction did not update, Error in breakdown');
	}

	if(count($beneficiarydeatilslist)==1){
		$beneficerytype="Individual";
		$beneficiaryname_frq="";
	}else if(count($beneficiarydeatilslist)>1){
		$beneficerytype="Multiple";
		$beneficiaryname_frq="Multiple";
	}
	
	$bbamt=0;
	foreach ($beneficiarydeatilslist as $bnkey) {
		$bbamt+=floatval($bnkey['expenseamount']);
	}
	$diffamt=$withdrownamount - $bbamt;
	$maxsno=GetMaxTempSno_stdb($mconn);
	$newrequestno = getnewrequestno_stdb($mconn,$companyid,$subcompanyid,$projectid,$accountid);
	$insq="INSERT into tbl_paymentrequest_breakdown(countryid,companyid,subcompanyid,projectid,subprojectid,request_no, beneficiery_name, ammount, purpose_type, purpose_category, purpose_categoryid, purpose_subcategory, purpose_subcategoryid, purpose, purpose_code, invoice_type, invoice_no, invoicedate, description, documenttype, document_status, extension, filepath, blobfileid, blobfile_tblname,taxableamount,gstamount,pono) VALUES";
	$brkqry="";
	$ttlamount=0;
	$tg=0;
	// $purposetype="";
	foreach ($beneficiarydeatilslist as $dtrow) {
		$beneficiaryname = $dtrow['beneficiaryname'];
		$beneficiaryname_frq = $dtrow['beneficiaryname'];
		$invoiceamount = $dtrow['expenseamount'];
		// $ttlamount+ =$dtrow['expenseamount'];
		$attachmentpath =$dtrow['attachmentpath'];
        $description = $dtrow['description'];
		$srm_attsno = $dtrow['srm_attsno'];
        $srm_emailsno = $dtrow['srm_emailsno'];
        $invoiceno = $dtrow['invoiceno'];
        $invoicetype = $dtrow['invoicetype'];
        $invoicedate = $dtrow['invoicedate'];
        $invoicegstamount = $dtrow['invoicegstamount'];
		$taxableamount = $dtrow['taxableamount'];
		$ponumber = $dtrow['ponumber'];
		$blobfile="";
		$blobfile_tblname="";
        if($attachmentpath!=""){
			$document_status = "completed";
			$temp = explode(".", $attachmentpath);
			$extension=end($temp);
			$newfilename = round(microtime(true)).'_'.$tg. '.' . end($temp);
			// $distpath = $attachmentpath;
			$tg++;
			$dir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$newrequestno."/";
			if ( !is_dir( $dir ) ) {
			    mkdir($dir, 0777);       
			}
			$distpath =$dir.$newfilename;
			if(rename("../../".$attachmentpath, $distpath)){
				removeEmptyDirectory("../../".$attachmentpath);
			}
			$newfilepath = str_replace("../", "", $distpath);
		}else if($filepath_fromemail!=""){
			$newfilepath = $filepath_fromemail;
		}else{
			$document_status = "";
			$extension = "";
			$newfilepath = "";
		}
		
        if($beneficiaryname!="" && $invoiceamount!=""){
			$purposeinfo = getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$dtrow['purposecode']);
			foreach ($purposeinfo as $pcodrow) {
				$purpose_category=$pcodrow['pcategory'];
				$purposetype = $pcodrow['pcategory'];
				$pcategorycode=$pcodrow['pcategorycode'];				
				$psubcategory=$pcodrow['psub_category'];
				$psubcategorycode=$pcodrow['psub_categoryid'];
				$purposecode=$pcodrow['purposecode'];
				$purposewithcode=$pcodrow['purposewithcode'];
				$purpose = $pcodrow['purpose'];
			}
				
			// $newfilepath = $newfilepath.'.'.$extension;
			if($brkqry){
	            $brkqry= $brkqry.", ('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$newrequestno."', '".$beneficiaryname."', '".addslashes($invoiceamount)."', '".addslashes($purpose_category)."', '".addslashes($purpose_category)."', '".addslashes($pcategorycode)."', '".addslashes($psubcategory)."', '".addslashes($psubcategorycode)."', '".addslashes($purpose)."', '".addslashes($purposecode)."', '".$invoicetype."', '".addslashes($invoiceno)."', '".$invoicedate."', '".addslashes($description)."', '".$documenttype."', '".$document_status."', '".$extension."', '".str_replace("../", "", $newfilepath)."', '".addslashes($blobfile)."', '".addslashes($blobfile_tblname)."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	        }else{
	        	$brkqry= "('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$newrequestno."', '".$beneficiaryname."', '".addslashes($invoiceamount)."', '".addslashes($purpose_category)."', '".addslashes($purpose_category)."', '".addslashes($pcategorycode)."', '".addslashes($psubcategory)."', '".addslashes($psubcategorycode)."', '".addslashes($purpose)."', '".addslashes($purposecode)."', '".$invoicetype."', '".addslashes($invoiceno)."', '".$invoicedate."', '".addslashes($description)."', '".$documenttype."', '".$document_status."', '".$extension."', '".str_replace("../", "", $newfilepath)."', '".addslashes($blobfile)."', '".addslashes($blobfile_tblname)."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	        }
		}
	}
    if($brkqry){
		$brkqry1=$insq.$brkqry;
		$mconn->query($brkqry1);
		if(mysqli_affected_rows($mconn)>0){
			$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
			$from_bankname ="";
			$from_accountno ="";
			if($pfrmb->num_rows>0){
				$brow=$pfrmb->fetch_assoc();
				$from_bankname = $brow['bankname'];
				$from_accountno = $brow['accountno'];
			}
			$mconn->query("INSERT into tbl_paymentrequest(temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, purpose_type, from_accountid, from_bankname, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, software_version, status_cheque, pending_description, countryid, companyid, subcompanyid, projectid, subprojectid, requestdatatype,is_purchaseforproduction) value('".$maxsno."', '".$newrequestno."', '".date('Y-m-d')."', '".$paymentduedate."', '".ucwords(strtolower($loginname))."', '".$paytoid."', '".addslashes($paytoname)."', '".$paymentmode."', '".addslashes($beneficerytype)."', '".addslashes($beneficiaryname_frq)."', '".addslashes($bbamt)."', '".$purposetype."', '".$accountid."', '".$from_bankname."', '".$from_accountno."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', 'Web', 'Pending', 'Request not seen by Admin', '".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$requesttypename."','".$isforproduction."')");
			if(mysqli_affected_rows($mconn)>0){
        		$rst[]=array('status'=>'Success', 'message'=>'Your request details saved successfully with requestno '.$newrequestno, 'requestno'=>$newrequestno, 'referenceno'=>'');
        	}else{
        		$rst[]=array('status'=>'Error', 'message'=>'Your request did not save, Due to  technical issue', 'requestno'=>'');
        	}			
		}else{
    		$rst[]=array('status'=>'Error', 'message'=>'Breakdown details did not get, Please try again or contact ot web admin', 'requestno'=>'');
    	}	
	}else{
		$rst[]=array('status'=>'Error', 'message'=>'Breakdown details did not get, Please try again or contact ot web admin', 'requestno'=>'');
	}
    return $rst;
    
}
function savetowithdrawalhistory($conn, $companyid, $subcompanyid, $projectid, $groupaccountid,$purpose_code ,$groupbankdescription,$averageamountofgroup,$minamtofgroup,$maxamtofgroup,$countofgroup,$paytoid,$assignauto='0'){
		$unreassprpmsg=array();
			$qry = "select groupid from tbreconstructedhistory where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and accountid = '".$groupaccountid."' and description='".addslashes($groupbankdescription)."'";
			$res=$conn->query($qry);
			$groupid = "";
			if($res->num_rows>0){
				$row = $res->fetch_assoc();
				$groupid=$row["groupid"];
			}
			else{
				$qry = "select ifnull(max(groupid),0)+1 as groupid from tbreconstructedhistory where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and accountid = '".$groupaccountid."'";
				$res=$conn->query($qry);
				$groupid = "";
				if($res->num_rows>0){
					$row = $res->fetch_assoc();
					$groupid=$row["groupid"];
				}
			}
			if($groupid){
				 $insqry = "insert into tbreconstructedhistory(companyid,subcompanyid,projectid,accountid,groupid,description,purposecode,averageamount,minamt,maxamt,totalentries,paytoid,autoassign)values('".$companyid."','".$subcompanyid."','".$projectid."','".$groupaccountid."','".$groupid."','".addslashes($groupbankdescription)."','".$purpose_code."','".$averageamountofgroup."','".$minamtofgroup."','".$maxamtofgroup."','".$countofgroup."','".$paytoid."','".$assignauto."')";
				$conn->query($insqry);
			}
	        $unreassprpmsg = array('status' => 'Success', 'message' => 'Saved To History successfully');
	    return $unreassprpmsg;
}

function unapproved_saverequestdatafor_taxinvoice($mconn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$paymentmode,$chequeno,$chequedate,$withdrownamount,$paytoid,$paytogstno,$documenttype,$filepath_fromemail,$attsno_fromemail,$emailsno_fromemail,$requesttypename,$paymentduedate,$beneficiarydeatilslist,$invoiceno,$invoicedate,$invoicedata,$isforproduction,$loginname){
	$rst=array();
	//payto details
	$pptyo=getpaytoinfo($mconn,$companyid,$subcompanyid,"paytoid",$paytoid);
	if(count($pptyo)>0){
		foreach ($pptyo as $pkey) {
			$paytoname=$pkey['paytoname'];
			$payto_bankname=$pkey['tobankname'];
			$payto_accountno=$pkey['toaccountno'];
			$payto_bankifsccode=$pkey['tobankifsc'];
			$payto_taxid=$pkey['taxid'];
			$payto_taxtype=$pkey['taxtype'];
		}
	}
	if(count($beneficiarydeatilslist)==1){
		$beneficerytype="Individual";
		$beneficiaryname_frq="";
	}else if(count($beneficiarydeatilslist)>1){
		$beneficerytype="Multiple";
		$beneficiaryname_frq="Multiple";
	}
	
	$bbamt=0;
	foreach ($beneficiarydeatilslist as $bnkey) {
		$bbamt+=floatval($bnkey['expenseamount']);
	}
	$diffamt=$withdrownamount - $bbamt;
	$maxsno=GetMaxTempSno_stdb($mconn);
	$newrequestno = getnewrequestno_stdb($mconn,$companyid,$subcompanyid,$projectid,$accountid);
	$insq="INSERT into tbl_paymentrequest_breakdown(countryid,companyid,subcompanyid,projectid,subprojectid,request_no, beneficiery_name, ammount, purpose_type, purpose_category, purpose_categoryid, purpose_subcategory, purpose_subcategoryid, purpose, purpose_code, invoice_type, invoice_no, invoicedate, description, documenttype, document_status, extension, filepath,audiofilepath,voiceinstid, blobfileid, blobfile_tblname,taxableamount,gstamount,pono) VALUES";
	$brkqry="";
	$ttlamount=0;
	$purposetype="";
	// print_r($beneficiarydeatilslist);
	$tg=0;
	foreach ($beneficiarydeatilslist as $dtrow) {
		$beneficiaryname = $dtrow['beneficiaryname'];
		$invoiceamount = $dtrow['expenseamount'];
        $invoiceno = $dtrow['invoiceno'];
        $invoicedate = $dtrow['invoicedate'];
        $invoicetype = $dtrow['invoicetype'];
        $description = $dtrow['description'];
        $purposecode = $dtrow['purposecode'];
        $isforstock = $dtrow['isforstock'];
		$attachmentpath =$dtrow['attachmentpath'];
		$audiofilepath =$dtrow['audiofilepath'];
		$voiceinstid =$dtrow['voiceinstid'];
		$invoicegstamount = $dtrow['invoicegstamount'];
		$taxableamount = $dtrow['taxableamount'];
		$ponumber = $dtrow['ponumber'];
		$srm_attsno = $dtrow['srm_attsno'];
        $srm_emailsno = $dtrow['srm_emailsno'];
		$blobfile="";
		$blobfile_tblname="";
        if($attachmentpath!=""){
			$document_status = "completed";
			$temp = explode(".", $attachmentpath);
			$extension=end($temp);
			$newfilename = round(microtime(true)).'_'.$tg. '.' . end($temp);
			// $distpath = $attachmentpath;
			$tg++;
			$dir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$newrequestno."/";
			if ( !is_dir( $dir ) ) {
			    mkdir($dir, 0755);       
			}
			$distpath =$dir.$newfilename;
			if(rename($attachmentpath, $distpath)){
				removeEmptyDirectory($attachmentpath);
			}
			$newfilepath = str_replace("../", "", $distpath);
		}else if($filepath_fromemail!=""){
			$newfilepath = $filepath_fromemail;
		}else{
			$document_status = "";
			$extension = "";
			$newfilepath = "";
		}
		 // print_r($dtrow['invoicedata']);
		if($beneficiaryname!="" && $invoiceamount!=""){
			$purposeinfo = getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$dtrow['purposecode']);
			foreach ($purposeinfo as $pcodrow) {
				$purpose_category=$pcodrow['pcategory'];
				$purposetype = $pcodrow['pcategory'];
				$pcategorycode=$pcodrow['pcategorycode'];				
				$psubcategory=$pcodrow['psub_category'];
				$psubcategorycode=$pcodrow['psub_categoryid'];
				$purposecode=$pcodrow['purposecode'];
				$purposewithcode=$pcodrow['purposewithcode'];
				$purpose = $pcodrow['purpose'];
			}
				
			// $newfilepath = $newfilepath.'.'.$extension;
			if($brkqry){
	            $brkqry= $brkqry.", ('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$newrequestno."', '".$beneficiaryname."', '".addslashes($invoiceamount)."', '".addslashes($purpose_category)."', '".addslashes($purpose_category)."', '".addslashes($pcategorycode)."', '".addslashes($psubcategory)."', '".addslashes($psubcategorycode)."', '".addslashes($purpose)."', '".addslashes($purposecode)."', '".$invoicetype."', '".addslashes($invoiceno)."', '".$invoicedate."', '".addslashes($description)."', '".$documenttype."', '".$document_status."', '".$extension."', '".str_replace("../", "", $newfilepath)."','".$audiofilepath."','".$voiceinstid."', '".addslashes($blobfile)."', '".addslashes($blobfile_tblname)."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	        }else{
	        	$brkqry= "('".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$newrequestno."', '".$beneficiaryname."', '".addslashes($invoiceamount)."', '".addslashes($purpose_category)."', '".addslashes($purpose_category)."', '".addslashes($pcategorycode)."', '".addslashes($psubcategory)."', '".addslashes($psubcategorycode)."', '".addslashes($purpose)."', '".addslashes($purposecode)."', '".$invoicetype."', '".addslashes($invoiceno)."', '".$invoicedate."', '".addslashes($description)."', '".$documenttype."', '".$document_status."', '".$extension."', '".str_replace("../", "", $newfilepath)."','".$audiofilepath."','".$voiceinstid."', '".addslashes($blobfile)."', '".addslashes($blobfile_tblname)."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
	        }
		}
	}

    if($brkqry){
		$brkqry1=$insq.$brkqry;
		$mconn->query($brkqry1);
		if(mysqli_affected_rows($mconn)>0){
			$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
			$from_bankname ="";
			$from_accountno ="";
			if($pfrmb->num_rows>0){
				$brow=$pfrmb->fetch_assoc();
				$from_bankname = $brow['bankname'];
				$from_accountno = $brow['accountno'];
			}
			$mconn->query("INSERT into tbl_paymentrequest(temp_requestno, request_no, request_date, paymentduedate, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, purpose_type, from_accountid, from_bankname, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, software_version, status_cheque, pending_description, countryid, companyid, subcompanyid, projectid, subprojectid, requestdatatype,is_purchaseforproduction) value('".$maxsno."', '".$newrequestno."', '".date('Y-m-d')."', '".$paymentduedate."', '".ucwords(strtolower($loginname))."', '".$paytoid."', '".addslashes($paytoname)."', '".$paymentmode."', '".addslashes($beneficerytype)."', '".addslashes($beneficiaryname)."', '".addslashes($bbamt)."', '".$purposetype."', '".$accountid."', '".$from_bankname."', '".$from_accountno."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', 'Web', 'Pending', 'Request not seen by Admin', '".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$requesttypename."','".$isforproduction."')");
			if(mysqli_affected_rows($mconn)>0){
				if(count($invoicedata)>0){			
					savetoinvoicedata($mconn,$companyid,$subcompanyid,$projectid,$newrequestno,'',$invoicedate,$invoiceno,$invoicedata,$paytoid,$paytogstno,$loginname);
				}
        		$rst[]=array('status'=>'Success', 'message'=>'Your request details saved successfully', 'requestno'=>$newrequestno, 'referenceno'=>'');
        	}else{
        		$rst[]=array('status'=>'Error', 'message'=>'Your request did not save, Due to  technical issue', 'requestno'=>'');
        	}			
		}else{
    		$rst[]=array('status'=>'Error', 'message'=>'Breakdown details did not get, Please try again or contact ot web admin', 'requestno'=>'');
    	}	
	}else{
		$rst[]=array('status'=>'Error', 'message'=>'Breakdown details did not get, Please try again or contact ot web admin', 'requestno'=>'');
	}
    return $rst;    
}

function verifiedpaytoandpurposetobookdata($conn,$companyid,$subcompanyid,$projectid,$referenceno,$isverifypayto,$isverifypurpose,$loginname){
	$vrymsg=array();
	if($referenceno){
			// Prepare the query
			$verify = "verified on date ".date('Y-m-d H:i:s')." by ".$loginname;
			if($isverifypayto){
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET verifypayto =? WHERE reference_no = ?");		    
			    // Bind parameters
			    $stmt->bind_param("ss",$verify,$referenceno);
			    // Execute the prepared statement
			    $stmt->execute();
				// Commit transaction
				// Close the prepared statement
				$stmt->close();
			}
			if($isverifypurpose){
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET verifypurpose=? WHERE reference_no = ?");		    
			    // Bind parameters
			    $stmt->bind_param("ss",$verify,$referenceno);
			    // Execute the prepared statement
			    $stmt->execute();
				// Commit transaction
				// Close the prepared statement
				$stmt->close();
			}
			// echo "All records updated successfully.";
			$vrymsg=array('status'=>'Success', 'message'=>'Update and verified payto and purpose successfully');
		
	}
	return $vrymsg;
}
function unapproved_saverequestdataforsalary($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$purposetype,$paytoid,$modeoftransfer,$requestamount,$description,$purposecategoryid,$purposesubcategoryid,$purposecode,$salaryyear,$salarymonth,$salarydays,$loginname,$filepath,$audiouploadFile,$voiceinstid,$filepath_fromemail,$attsno_fromemail,$emailsno_fromemail,$requestdatatype,$paymentduedate,$taxableamount=null,$invoicegstamount=null){
	date_default_timezone_set('asia/kolkata');
	//purpose details
	$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(purposecode)='".$purposecode."'");
	$purpose_category = "";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	if($sltp->num_rows>0){
		while ($akey=$sltp->fetch_assoc()) {
			$purpose_category = $akey['category'];
			$purpose_categoryid = $akey['categoryid'];
			$purpose_subcategory = $akey['sub_category'];
			$purpose_subcategoryid = $akey['sub_categoryid'];
			$purpose = $akey['purpose'];
			$purpose_code = $akey['purposecode'];
			$default_beneficiary = $akey['default_beneficiary'];
		}
	}
	
	//payto details
	$payname="";
	$beneficiaryname="";
	$is_intermediate_transfer="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$payto_accountid="";
	$from_accountno="";
	
	$empinfo=employeemaster($conn,$companyid,$subcompanyid,$projectid,"employeeid",$paytoid);
	if(count($empinfo)>0){
		foreach ($empinfo as $empkey) {
			$paytoname=$empkey['empname'];
			$beneficiaryname=$empkey['empname'];
			$payto_bankname=$empkey['bankname'];
			$payto_accountno=$empkey['accountno'];
			$payto_bankifsccode=$empkey['ifsccode'];
			$is_intermediate_transfer="";
			$payto_accountid="";
		}
	}else{
		$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
		foreach ($ppt as $pptkey) {
			$paytoname = $pptkey['paytoname'];
			$beneficiaryname=$pptkey['paytoname'];
			$payto_bankname=$pptkey['tobankname'];
			$payto_accountno=$pptkey['toaccountno'];
			$payto_bankifsccode=$pptkey['tobankifsc'];
			$is_intermediate_transfer="";
			$payto_accountid="";
		}
	}
	
	//payfrom account details
	$accinq=$conn->query("select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountid in('".str_replace(",", "','", $fromaccountid)."') and accountname not like '%_FD'");
	if($accinq->num_rows>0){
		while ($accrow=$accinq->fetch_assoc()) {
			$from_bankid=$accrow['bankid'];
			$from_accountid=$accrow['accountid'];
			$from_bankname=$accrow['bankname'];
			$from_accountname=$accrow['accountname'];
			$from_accountno=$accrow['accountno'];
			$from_ifsccode=$accrow['ifsccode'];
		}
	}
	$prj=projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	foreach ($prj as $ppkey) {
		$documenttype = $ppkey['documenttype'];
	}
	$beneficierytype="Individual";
	$maxsno=GetMaxTempSno_stdb($conn);
	$newrequestno = getnewrequestno_stdb($conn,$companyid,$subcompanyid,$projectid);
	$resultarr=array();
	if($purpose_code){
		if($beneficiaryname){
			if($from_accountid){
				// is_intermediate_transfer, payto_bankname, payto_accountno, payto_bankifsccode, payto_accountid, from_accountno
				$insertdata = "INSERT INTO tbl_paymentrequest (temp_requestno, request_no, request_date,paymentduedate, requested_by,paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, purpose_type, from_accountid, from_bankname, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, payto_accountid, software_version, status_cheque, pending_description, countryid, companyid, subcompanyid, projectid,subprojectid, requestdatatype,is_intermediate_transfer) VALUES ('".$maxsno."', '".$newrequestno."', '".date('Y-m-d')."','".$paymentduedate."','".addslashes($loginname)."', '".$paytoid."', '".addslashes($paytoname)."', '".$modeoftransfer."', '".$beneficierytype."', '".addslashes($beneficiaryname)."', '".addslashes($requestamount)."', '".$purpose_category."', '".$from_accountid."', '".$from_bankname."', '".$from_accountno."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', '".$payto_accountid."', 'Web', 'Pending', 'Request not seen by Admin', '".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$requestdatatype."', '".$is_intermediate_transfer."')";
				if($conn->query($insertdata))
				{
					if($filepath!=""){
						$document_status = "completed";
						$temp = explode(".", $filepath);
						$extension=end($temp);
						$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
						$dir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$newrequestno."/";
						if ( !is_dir( $dir ) ) {
						    mkdir($dir, 0755);       
						}
						$distpath =$dir.$newfilename;
						if(rename($filepath, $distpath)){
							removeEmptyDirectory($filepath);
						}
        				$newfilepath = str_replace("../", "", $distpath);
					}else if($filepath_fromemail!=""){
						$newfilepath = $filepath_fromemail;
					}else{
						$document_status = "";
						$extension = "";
						$filepath = "";
					}
					$brkquery = "INSERT INTO tbl_paymentrequest_breakdown (request_no, beneficiery_name, ammount, purpose_type,purpose_category,purpose_categoryid,purpose_subcategory,purpose_subcategoryid, purpose, purpose_code, description,countryid,companyid,subcompanyid,projectid,subprojectid,document_status,documenttype,extension,filepath,audiofilepath,voiceinstid,paytoid,salaryyear,salarymonth,salarydays,srm_attsno,srm_emailsno,taxableamount,gstamount) VALUES ('".$newrequestno."', '".addslashes($beneficiaryname)."', '".addslashes($requestamount)."', '".$purpose_category."', '".$purpose_category."', '".$purpose_categoryid."', '".$purpose_subcategory."', '".$purpose_subcategoryid."', '".$purpose."', '".$purpose_code."', '".addslashes($description)."','".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$document_status."', '".$documenttype."', '".$extension."', '".$newfilepath."', '".$audiouploadFile."', '".$voiceinstid."', '".$paytoid."', '".$salaryyear."', '".$salarymonth."', '".$salarydays."', '".$attsno_fromemail."', '".$emailsno_fromemail."','".$taxableamount."','".$invoicegstamount."')";						
					if($conn->query($brkquery))
					{
						if($voiceinstid){
							$conn->query("update tbl_paymentrequest_voiceinstruction set requestno='".$newrequestno."', requeststatus='done', requestedby='".addslashes($loginname)."', requestdate='".date('Y-m-d')."' where instructionid='".$voiceinstid."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
						}
						$resultarr[]=array('status'=>'Success', 'message'=>'Your request number is '.$newrequestno, 'requestno'=>$newrequestno);
						$request_msg=$newrequestno;
					}else{
						$resultarr[]=array('status'=>'Error', 'message'=>'Request number incomplete');
						$request_msg="Technical issue";
					}
				}else{
					$resultarr[]=array('status'=>'Error', 'message'=>'Technical issue');
				}
			}else{
				$resultarr[]=array('status'=>'Error', 'message'=>'From account details wrong/missing');
			}
		}else{
			$resultarr[]=array('status'=>'Error', 'message'=>'beneficiaryname details wrong/missing');
		}		
	}else{
		$resultarr[]=array('status'=>'Error', 'message'=>'purpose details wrong/missing');
	}		
	return $resultarr;
}
function unapproved_saverequestdataforall($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid=null,$fromaccountid,$paytoid,$modeoftransfer,$requestamount,$description,$purposecategoryid,$purposesubcategoryid,$purposecode,$purposename,$loginname,$filepath,$audiouploadFile,$voiceinstid,$filepath_fromemail,$attsno_fromemail,$emailsno_fromemail,$requestdatatype,$invoicedate=null,$invoiceno=null,$invoicetype=null,$invoicedata=null,$invoice_totalrowsamount=null, $invoice_totalgstamount=null, $invoice_totalrowsdiscount=null, $invoice_otherdiscount=null, $invoice_totalpayableamount=null,$beneficiaryname=null,$paymentduedate=null,$isforproduction=null,$taxableamount=null,$invoicegstamount=null,$ponumber=null){
	date_default_timezone_set('asia/kolkata');
	$resultarr=array();
	if(isValidDate($paymentduedate,'Y-m-d')!=1){
		$paymentduedate = "";
	}
	$prj=projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	$documenttype ="physical";
	foreach ($prj as $ppkey) {
		$documenttype = $ppkey['documenttype'];
	}
	//purpose details	
	$purpose_category = "";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose =$purposename;
	$purpose_code ="";
	// $ppp=getpurposefulldetails($conn,$subcompanyid,$projectid,"purposecode",$purposecode);
	$ppp=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	if(count($ppp)>0){
		foreach ($ppp as $akey) {
			$purpose_category = $akey['pcategory'];
			$purpose_categoryid = $akey['pcategorycode'];
			$purpose_subcategory = $akey['psub_category'];
			$purpose_subcategoryid = $akey['psub_categoryid'];
			$purpose = $akey['purpose'];
			$purpose_code = $akey['purposecode'];
		}
	}else{
		$resultarr[]=array('status'=>'Error', 'message'=>'Purpose details wrong/missing===');
		return $resultarr;
		die();
	}
	//payto details
	$paytoname="";
	$is_intermediate_transfer="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$payto_accountid="";
	$from_accountno="";
	$paytoinfo=array();
	$paytoinfo=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"paytoid",$paytoid);
	if(count($paytoinfo)>0){
		foreach ($paytoinfo as $pkey) {
			if($pkey['accountno']){
				$paytoname=$pkey['accountname'];
				$beneficiaryname=$pkey['accountname'];
			}else{
				$paytoname=$pkey['accountname'];
				$beneficiaryname=$pkey['accountname'];
			}
			$payto_bankname=$pkey['bankname'];
			$payto_accountno=$pkey['accountno'];
			$payto_bankifsccode=$pkey['ifsccode'];
			$is_intermediate_transfer="yes";
			$payto_accountid=$pkey['accountid'];
		}
	}else{
		$paytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
		$paytoname="";
		$payto_bankname="";
		$payto_accountno="";
		$payto_bankifsccode="";
		if(count($paytoinfo)>0){
			foreach ($paytoinfo as $pkey) {
				$paytoname=$pkey['paytoname'];
				if($beneficiaryname!=""){
					$beneficiaryname=$beneficiaryname;
				}else{
					$beneficiaryname=$pkey['paytoname'];
				}
				$payto_bankname=$pkey['tobankname'];
				$payto_accountno=$pkey['toaccountno'];
				$payto_bankifsccode=$pkey['tobankifsc'];
			}
		}		
	}

	//payfrom account details
	$from_bankid="N/A";
	$from_accountid="N/A";
	$from_bankname="N/A";
	$from_accountname="N/A";
	$from_accountno="N/A";
	$from_ifsccode="N/A";
	$accinq=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$fromaccountid);
	if(count($accinq)>0){
		foreach ($accinq as $accrow) {
			$from_bankid=$accrow['bankid'];
			$from_accountid=$accrow['accountid'];
			$from_bankname=$accrow['bankname'];
			$from_accountname=$accrow['accountname'];
			$from_accountno=$accrow['accountno'];
			$from_ifsccode=$accrow['ifsccode'];
		}
	}
	$beneficierytype="Individual";
	$purposetype=$purpose_category;
	$maxsno=GetMaxTempSno_stdb($conn);
	$newrequestno = getnewrequestno_stdb($conn,$companyid,$subcompanyid,$projectid,$fromaccountid);
	if($beneficiaryname){
		// $isforproduction
		$insertdata = "INSERT INTO tbl_paymentrequest (temp_requestno, request_no, request_date, requested_by, paytoid, payto, payment_mode, beneficiery_type, beneficiery_name, request_amount, purpose_type, from_accountid, from_bankname, from_accountno, payto_bankname, payto_accountno, payto_bankifsccode, payto_accountid, software_version, status_cheque, pending_description, countryid, companyid, subcompanyid, projectid,subprojectid, requestdatatype,is_intermediate_transfer,paymentduedate,is_purchaseforproduction) VALUES ('".$maxsno."', '".$newrequestno."', '".date('Y-m-d')."', '".addslashes($loginname??'')."', '".addslashes($paytoid??'')."', '".addslashes($paytoname??'')."', '".$modeoftransfer."', '".$beneficierytype."', '".addslashes($beneficiaryname??'')."', '".addslashes($requestamount??'')."', '".$purposetype."', '".$from_accountid."', '".$from_bankname."', '".$from_accountno."', '".$payto_bankname."', '".$payto_accountno."', '".$payto_bankifsccode."', '".$payto_accountid."', 'Web', 'Pending', 'Request not seen by Admin', '".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$requestdatatype."', '".$is_intermediate_transfer."','".$paymentduedate."','".$isforproduction."')";
			if($conn->query($insertdata))
			{
				if($filepath!=""){
					$document_status = "completed";
					$temp = explode(".", $filepath);
					$extension=end($temp);
					$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
					$dir="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".$newrequestno."/";
					if ( !is_dir( $dir ) ) {
					    mkdir($dir, 0755);       
					}
					$distpath =$dir.$newfilename;
					if(rename($filepath, $distpath)){
						removeEmptyDirectory($filepath);
					}
    				$newfilepath = str_replace("../", "", $distpath);
				}else if($filepath_fromemail!=""){
					$newfilepath = $filepath_fromemail;
				}else{
					$newfilepath = "";
					$document_status = "";
					$extension = "";
					$filepath = "";
				}
				$brkquery = "INSERT INTO tbl_paymentrequest_breakdown (request_no, beneficiery_name, ammount, purpose_type,purpose_category,purpose_categoryid,purpose_subcategory,purpose_subcategoryid, purpose, purpose_code, description,countryid,companyid,subcompanyid,projectid,subprojectid,document_status,invoice_type,invoice_no,invoicedate,documenttype,extension,filepath,audiofilepath,srm_attsno,srm_emailsno,voiceinstid,taxableamount,gstamount,pono) VALUES ('".$newrequestno."', '".addslashes($beneficiaryname??'')."', '".addslashes($requestamount??'0')."', '".addslashes($purposetype)."', '".addslashes($purpose_category)."', '".$purpose_categoryid."', '".addslashes($purpose_subcategory)."', '".addslashes($purpose_subcategoryid)."', '".addslashes($purpose)."', '".addslashes($purpose_code)."', '".addslashes($description??'')."','".$countryid."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".$document_status."','".$invoicetype."','".$invoiceno."','".$invoicedate."','".$documenttype."', '".$extension."', '".addslashes($newfilepath)."', '".addslashes($audiouploadFile)."', '".$attsno_fromemail."', '".$emailsno_fromemail."','".$voiceinstid."','".$taxableamount."','".$invoicegstamount."','".$ponumber."')";
				if($conn->query($brkquery))
				{
					if($voiceinstid){
						$conn->query("update tbl_paymentrequest_voiceinstruction set requestno='".$newrequestno."', requeststatus='done', requestedby='".$loginname."', requestdate='".date('Y-m-d')."' where instructionid='".$voiceinstid."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");
					}
					// if(count($invoicedata)>0){
					// 	savetoinvoicedata($conn,$companyid,$subcompanyid,$projectid,$newrequestno,'',$invoicedate,$invoiceno,$invoicedata,$invoice_totalrowsamount, $invoice_totalgstamount, $invoice_totalrowsdiscount, $invoice_otherdiscount, $invoice_totalpayableamount);
					// }
					$resultarr[]=array('status'=>'Success', 'message'=>'(request)Your request number is '.$newrequestno, 'referenceno'=>'', 'requestno'=>$newrequestno);
				}else{
					$resultarr[]=array('status'=>'Error', 'message'=>'(request)Request number incomplete');
				}
			}else{
				$resultarr[]=array('status'=>'Error', 'message'=>'(request)Technical issue');
			}
		
	}else{
		$resultarr[]=array('status'=>'Error', 'message'=>'beneficiaryname details wrong/missing');
	}		
	return $resultarr;
}



function savetoinvoicedata($conn,$companyid,$subcompanyid,$projectid,$requestno,$referenceno,$invoicedate,$invoiceno,$invoicedata,$paytoid,$paytogstno,$loginname){
	$rrslst=array();
	$invoiceid ="";
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	if(count($invoicedata)>0){
		$conn->begin_transaction();
		try{
			$invoiceid = "INV".date('Ymdhis');
			$insq="INSERT INTO tbl_invoicespurchase_breakdown(companyid,subcompanyid,projectid,invoiceid,invoicedate,invoiceno,description,hsncode,mrp,rate,qty,cgstpercent,cgstamount,sgstpercent,sgstamount,igstpercent,igstamount,distpercent,distamount,taxableamount,ttlamount,payableamount,status,requestno,referenceno,isforstock,loginname) VALUES";
			$brkqry="";
			$ttltaxableamount=0;
			// print_r($invoicedata);
			foreach ($invoicedata as $invkey) {
				$payableamount=($invkey['item-amount'] + $invkey['item-cgst-amt'] + $invkey['item-sgst-amt'] + $invkey['item-igst-amt']);
				$invoice_totalrowsamount+=$invkey['item-amount'];
				$invoice_totalgstamount+=($invkey['item-cgst-amt'] + $invkey['item-sgst-amt'] + $invkey['item-igst-amt']);
				$invoice_totalrowsdiscount+=$invkey['item-dist-amt'];
				$ttltaxableamount+=$invkey['item-txablamount'];
				$invoice_totalpayableamount+=$payableamount;
				if($brkqry){
					$brkqry= $brkqry.",('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$invoiceid."', '".$invoicedate."', '".$invoiceno."', '".addslashes($invkey['item-description'])."', '".addslashes($invkey['item-hsbn'])."', '".addslashes($invkey['item-mrp'])."', '".addslashes($invkey['item-rate'])."', '".addslashes($invkey['item-qty'])."', '".addslashes($invkey['item-cgst-prt'])."', '".addslashes($invkey['item-cgst-amt'])."', '".addslashes($invkey['item-sgst-prt'])."', '".addslashes($invkey['item-sgst-amt'])."', '".addslashes($invkey['item-igst-prt'])."', '".addslashes($invkey['item-igst-amt'])."', '".addslashes($invkey['item-dist-prt'])."', '".addslashes($invkey['item-dist-amt'])."', '".addslashes($invkey['item-txablamount'])."', '".addslashes($invkey['item-amount'])."', '".addslashes($payableamount)."', 'readytorequest','".$requestno."','".$referenceno."', '".$invkey['item-isforstock']."','".$loginname."')";
	            }else{
	            	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."', '".$invoiceid."', '".$invoicedate."', '".$invoiceno."', '".addslashes($invkey['item-description'])."', '".addslashes($invkey['item-hsbn'])."', '".addslashes($invkey['item-mrp'])."', '".addslashes($invkey['item-rate'])."', '".addslashes($invkey['item-qty'])."', '".addslashes($invkey['item-cgst-prt'])."', '".addslashes($invkey['item-cgst-amt'])."', '".addslashes($invkey['item-sgst-prt'])."', '".addslashes($invkey['item-sgst-amt'])."', '".addslashes($invkey['item-igst-prt'])."', '".addslashes($invkey['item-igst-amt'])."', '".addslashes($invkey['item-dist-prt'])."', '".addslashes($invkey['item-dist-amt'])."', '".addslashes($invkey['item-txablamount'])."', '".addslashes($invkey['item-amount'])."', '".addslashes($payableamount)."', 'readytorequest','".$requestno."','".$referenceno."', '".$invkey['item-isforstock']."','".$loginname."')";
	            }
			}
			if($brkqry){
	    		$brkqry1=$insq.$brkqry;
	    		$conn->query($brkqry1);
	    		if(mysqli_affected_rows($conn)>0){
	    			$conn->query("INSERT INTO tbl_invoicespurchase_main(companyid, subcompanyid, projectid,paytoid,paytogstno, invoiceid, invoicedate, invoiceno, totalamount,totaltaxableamount, totalgstamount,totaldiscountamount, totalpayableamount, requestno,referenceno,loginname) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".$paytogstno."','".$invoiceid."','".$invoicedate."','".$invoiceno."','".$invoice_totalrowsamount."','".$ttltaxableamount."','".$invoice_totalgstamount."','".$invoice_totalrowsdiscount."','".$invoice_totalpayableamount."','".$requestno."', '".$referenceno."','".$loginname."')");
	    			$rrslst[]=array('status'=>'Success', 'message'=>'Invoice details save successfully', 'invoiceid'=>$invoiceid);
	    		}else{
	    			$rrslst[]=array('status'=>'Error', 'message'=>'Invoice details did not save, due to technical issue', 'invoiceid'=>$invoiceid);
	    		}
	    	}else{
				$rrslst[]=array('status'=>'Error', 'message'=>'Breakdown details did not available', 'invoiceid'=>$invoiceid);
			}
			$conn->commit();
		}catch(mysqli_sql_exception $ex){
			$conn->rollback();
		}
	}else{
		$rrslst[]=array('status'=>'Error', 'message'=>'Breakdown details did not available', 'invoiceid'=>$invoiceid);
	}
	return $rrslst;
}
function saveasimprestadjestment($mconn,$companyid,$subcompanyid,$projectid,$accountid,$imprestpayname,$invoiceinfo){
	$ppq=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	$purpose_category = "";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	foreach ($ppq as $akey) {
		$purpose_category = $akey['pcategory'];
		$purpose_categoryid = $akey['pcategorycode'];
		$purpose_subcategory = $akey['psub_category'];
		$purpose_subcategoryid = $akey['psub_categoryid'];
		$purpose = $akey['purpose'];
		$purpose_code = $akey['purposecode'];
	}
	$imprestaccountid="";
	$imprestbeneficery="";
	$pt=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"payto",$imprestpayname);
	if(count($pt)>0){
		foreach ($pt as $pkey) {
			$imprestaccountid=$pkey['imprest_accuntid'];
		}
	}
	else{
	 $imprestaccountid="unknown";
	 
	}
}
function reconciled_updatedepositbankstatment_closetobook($mconn,$referenceno,$valuedate,$dbsno_bnkstatement,$closetobook_referenceno,$reconcilationprocess=null){
	$reconcilationval="";
	if($reconcilationprocess){
		$reconcilationval=", reconcilation_process='".$reconcilationprocess."'";
	}
	$mconn->query("Update tbl_closetobook set status='Done', reconciliation='Done',book_status='completed', actule_referenceno='".$referenceno."',reference_no='".$closetobook_referenceno."' where sno='".$dbsno_bnkstatement."'");
	if(mysqli_affected_rows($mconn)>0){
		$mconn->query("UPDATE tbl_maintransaction_credit SET reconciliation='Done',reconciliation_banksno='".$dbsno_bnkstatement."', value_date='".$valuedate."' ".$reconcilationval." where reference_no='".$referenceno."' and (tedit is null or tedit='')");
		$bks=array('status'=>'Success', 'message'=>'Reconciled data update in bank statement');
	}else{
		$bks=array('status'=>'Error', 'message'=>'Reconciled data did not update in bank statement');
	}
	return $bks;
}
function reconciled_updatebankstatment_closetobook($mconn,$referenceno,$valuedate,$dbsno_bnkstatement,$closetobook_referenceno,$reconcilationprocess=null){
	$reconcilationval="";
	if($reconcilationprocess){
		$reconcilationval=", reconcilation_process='".$reconcilationprocess."'";
	}
	$mconn->query("Update tbl_closetobook set status='Done', reconciliation='Done',book_status='completed', actule_referenceno='".$referenceno."',reference_no='".$closetobook_referenceno."' where sno='".$dbsno_bnkstatement."'");
	if(mysqli_affected_rows($mconn)>0){
		$mconn->query("UPDATE tbl_maintransaction SET reconciliation='Done',reconciliation_banksno='".$dbsno_bnkstatement."', value_date='".$valuedate."' ".$reconcilationval." where reference_no='".$referenceno."' and (tedit is null or tedit='')");
		$bks=array('status'=>'Success', 'message'=>'Reconciled data update in bank statement');
	}else{
		$bks=array('status'=>'Error', 'message'=>'Reconciled data did not update in bank statement');
	}
	return $bks;
}


function reconciled_multiplebankentry_withonebookentury($conn,$companyid,$subcompanyid,$projectid,$banksnoarray,$booksnoarray,$reconcilationprocess=null){
	$chkbook=0;
	$referenceno ="";
	$booksno="";
	$ttlbookamount=0;
	$ttltransferedamount=0;
	$ttlactualtransferedamount=0;
	// print_r($booksnoarray);
	$banksno="";
	$valuedate ="";
	$transactonno ="";
	$returnmsg=array();
	if(count($banksnoarray)>1 && count($booksnoarray)==1){
		foreach ($booksnoarray as $bokkey) {
			$booksno=$bokkey['booksno'];
			$mnq=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"sno",$bokkey['booksno']);
			if(count($mnq)>0){
				$chkbook=1;
				foreach ($mnq as $mnqkey) {
					$referenceno = $mnqkey['reference_no'];
					$subprojectid = $mnqkey['subprojectid'];
					$paytoid=$mnqkey['paytoid'];
					$paytoname=$mnqkey['payto'];
					$listtransactionno=$mnqkey['transactionno'];
					$ttlbookamount=$mnqkey['book_amount'];
				}					
			}else{
				$returnmsg=array('status'=>'Error', 'message'=>'Book details are not available or something went wrong. Please try again.');
				return $returnmsg;
				die();
			}
		}
		

		$updclbook="";
		$sst=0;
		$valuedate ="";
		$transactonno ="";
		foreach ($banksnoarray as $bnkkey) {
			// check bank data
			if($sst==0){
				$banksno=$bnkkey['banksno'];
			}else{
				$banksno=$banksno.','.$bnkkey['banksno'];
			}

			$clbookinfo=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,"sno",$bnkkey['banksno']);
			// print_r($clbookinfo);

			if(count($clbookinfo)>0){
				$chkbank=1;
				foreach ($clbookinfo as $bbkkey) {
					$ttltransferedamount += $bbkkey['total_amount'];
					$ttlactualtransferedamount += $bbkkey['actualtransfered_amount'];
					$valuedate = $bbkkey['value_date'];
					$transactonno ="multiple";
					if($referenceno && $booksno){
						$updclbook.="Update tbl_closetobook set status='Done', reconciliation='Done', book_status='completed', actule_referenceno='".$referenceno."',reference_no='".$booksno."' where sno='".$bnkkey['banksno']."';";
					}
				}
			}
			$sst++;
		}
		$reconcilationval="";
		if($reconcilationprocess){
			$reconcilationval=", reconcilation_process='".$reconcilationprocess."'";
		}
		if($ttlbookamount==$ttlactualtransferedamount){
			if ($updclbook!=""){
				if(mysqli_multi_query($conn, $updclbook)){
					$conn->query("UPDATE tbl_maintransaction SET reconciliation='Done',reconciliation_banksno='".$banksno."', value_date='".date('Y-m-d', strtotime($valuedate))."' ".$reconcilationval." where sno='".$booksno."' and (tedit is null or tedit='')");
					if(mysqli_affected_rows($conn)>0){
						$returnmsg=array('status'=>'Success', 'message'=>'Reconciled data update in bank statement', 'bookamount'=>$ttlbookamount, 'transferedamount'=>$ttltransferedamount, 'referenceno'=>$referenceno,'banksno'=>$banksno, 'transactiondate'=>$valuedate, 'transactionno'=>$transactonno);
					}else{
						$returnmsg=array('status'=>'Error', 'message'=>'Error executing updates in book data: ' . mysqli_error($conn), 'bookamount'=>$ttlbookamount, 'transferedamount'=>$ttltransferedamount, 'referenceno'=>$referenceno,'banksno'=>$banksno, 'transactiondate'=>$valuedate, 'transactionno'=>$transactonno);						
					}
				}else{
					$returnmsg=array('status'=>'Error', 'message'=>'Error executing updates: ' . mysqli_error($conn), 'bookamount'=>$ttlbookamount, 'transferedamount'=>$ttltransferedamount, 'referenceno'=>$referenceno,'banksno'=>$banksno, 'transactiondate'=>$valuedate, 'transactionno'=>$transactonno);
				}
			}else{
				$returnmsg=array('status'=>'Error', 'message'=>'Error in executing updates query: ' . mysqli_error($conn), 'bookamount'=>$ttlbookamount, 'transferedamount'=>$ttltransferedamount, 'referenceno'=>$referenceno,'banksno'=>$banksno, 'transactiondate'=>$valuedate, 'transactionno'=>$transactonno);
			}
		}else{
			$returnmsg=array('status'=>'Error', 'message'=>'The booked amount('.$ttlbookamount.') does not match the actual transferred amount('.$ttlactualtransferedamount.'). Please verify and try again.', 'bookamount'=>$ttlbookamount, 'transferedamount'=>$ttltransferedamount, 'referenceno'=>$referenceno,'banksno'=>$banksno, 'transactiondate'=>$valuedate, 'transactionno'=>$transactonno);
		}
	}
	return $returnmsg;
}

function reconciled_updatebankstatment_closetobook_credit($mconn,$referenceno,$valuedate,$dbsno_bnkstatement,$closetobook_referenceno,$reconcilationprocess=null){
	$reconcilationval="";
	if($reconcilationprocess){
		$reconcilationval=", reconcilation_process='".$reconcilationprocess."'";
	}
	$mconn->query("Update tbl_closetobook set status='Done', reconciliation='Done',book_status='completed', actule_referenceno='".$referenceno."',reference_no='".$closetobook_referenceno."' where sno='".$dbsno_bnkstatement."'");
	if(mysqli_affected_rows($mconn)>0){
		$mconn->query("UPDATE tbl_maintransaction_credit SET reconciliation='Done',reconciliation_banksno='".$dbsno_bnkstatement."', value_date='".$valuedate."' ".$reconcilationval." where reference_no='".$referenceno."' and (tedit is null or tedit='')");
		$bks=array('status'=>'Success', 'message'=>'Reconciled data update in bank statement');
	}else{
		$bks=array('status'=>'Error', 'message'=>'Reconciled data did not update in bank statement');
	}
	return $bks;
}
function reconciled_updateexpensesheet($mconn,$referenceno,$dbsno_excelsheet,$banksno){
	$mconn->query("Update tbl_expencesinexcelsheet set status='Done',reference_no='".$referenceno."',banksno='".$banksno."' where sno in('".str_replace(",", "','", $dbsno_excelsheet)."')");
	if(mysqli_affected_rows($mconn)>0){
		$bks=array('status'=>'Success', 'message'=>'Reconciled data update in expense sheet');
	}else{
		$bks=array('status'=>'Error', 'message'=>'Reconciled data did not update in expense sheet');
	}
	return $bks;
}
function reconciled_updatedepositsheet($mconn,$referenceno,$dbsno_excelsheet,$banksno){
	$mconn->query("Update tbl_depositsinexcelsheet set status='Done',reference_no='".$referenceno."',banksno='".$banksno."' where sno in('".str_replace(",", "','", $dbsno_excelsheet)."')");
	if(mysqli_affected_rows($mconn)>0){
		$bks=array('status'=>'Success', 'message'=>'Reconciled data update in deposit sheet');
	}else{
		$bks=array('status'=>'Error', 'message'=>'Reconciled data did not update in deposit sheet');
	}
	return $bks;
}
function getnewrequestno_stdb($conn,$companyid,$subcompanyid,$projectid){
	$tempmaxno = GetMaxTempSno_stdb($conn);
	$requestno = "REQ-".$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.$tempmaxno;
	return $requestno;
}
function GetMaxTempSno_stdb($conn){
	$selectsno="select max(temp_requestno) as texp_sno from tbl_paymentrequest";
	$result_sno = $conn->query($selectsno);
	if ($result_sno->num_rows > 0)
	{
		$rowsno = $result_sno->fetch_array();
		$maxtempsno = $rowsno['texp_sno']+1;
	}
	return $maxtempsno;
}


function reconstructbookwith_unknown($mconn,$companyid,$subcompanyid,$projectid,$accountid,$transactiontype){
	$beneficerytype="";
	$freturns=array();
	$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="";
	$account_type ="";
	$accountname ="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
	}
	$pc=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,'purposecategoryname','Unknown');
	$purpose="";
	$purposecode=""; 
	$purposecategory=""; 
	$categoryid="";
	$sub_categoryid="";
	$sub_category="";
	foreach ($pc as $keypc) {
		$purpose = $keypc['purpose'];
		$purposecode = $keypc['purposecode'];
		$purposecategory = $keypc['pcategory'];
		$categoryid=$keypc['pcategorycode'];
		$sub_categoryid=$keypc['psub_categoryid'];
		$sub_category=$keypc['psub_category'];
	}
	$finyeartype = getfinancialyeartype($mconn,$companyid);
	$unreconsiledata=closetobookdetails($mconn,$companyid,$subcompanyid,$projectid,$transactiontype,'unreconcile_withaccountid',$accountid);
	$insbrkdwn = "Insert into tbl_maintransaction_breakdown(companyid, subcompanyid, projectid, reference_no, beneficiary_name, amount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, description, description_bank) VALUES";
	$insbrkdwnvals = "";
	$insmain = "Insert into tbl_maintransaction(tsno, companyid, subcompanyid, projectid,  reference_no, transaction_date, bookdate,finyear, book_amount, beforeedit_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, book_source,book_status,reconciliation,value_date,reconciliation_banksno,reconcilation_process) VALUES";
	$insmainvals = "";
	$tsno=0;
	$mtso=$mconn->query("SELECT ifnull(max(tsno),0) as maxtsno FROM tbl_maintransaction");		
	if($mtso->num_rows>0){
		$trow=$mtso->fetch_assoc();
		$tsno=$trow['maxtsno'];
	}
	$unknowns = array();
	foreach ($unreconsiledata as $impkey) {
		$valuedate = $impkey['value_date'];
		$banksno = $impkey['sno'];
		$financialYear = getFinancialYear($valuedate,$finyeartype);
		
		$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.(++$tsno);
		$unknowns[]=array("reference_no"=>$newreference_no,"banksno"=>$banksno);
		$total_unknown_amount = $impkey['total_amount'];
		if($insbrkdwnvals){
			$insbrkdwnvals = $insbrkdwnvals.",('".$companyid."','".$subcompanyid."','".$projectid."','".$newreference_no."','','".$total_unknown_amount."','".$categoryid."','".$purposecategory."','".$sub_categoryid."','".$sub_category."','".$purpose."','".$purposecode."','Marked Unknown transaction','')";
		}
		else{
			$insbrkdwnvals = "('".$companyid."','".$subcompanyid."','".$projectid."','".$newreference_no."','','".$total_unknown_amount."','".$categoryid."','".$purposecategory."','".$sub_categoryid."','".$sub_category."','".$purpose."','".$purposecode."','Marked Unknown transaction','')";
		}
		if($insmainvals){
			$insmainvals = $insmainvals. ",('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$valuedate."', '".$valuedate."', '".$financialYear."','".$total_unknown_amount."', '".$total_unknown_amount."', '', '', '".$bankname."', '".$accountno."', '".$accountid."', 'closetobook','Unknown','Done','".$valuedate."','".$banksno."','markunknown')";
		}
		else{
			$insmainvals = "('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$valuedate."', '".$valuedate."', '".$financialYear."','".$total_unknown_amount."', '".$total_unknown_amount."', '', '', '".$bankname."', '".$accountno."', '".$accountid."', 'closetobook','Unknown','Done','".$valuedate."','".$banksno."','markunknown')";
		}
	}
	$freturns=array();
	if($insbrkdwnvals && $insmainvals){
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	    $mconn->begin_transaction();
	    try {
			$bkdqry = $insbrkdwn.$insbrkdwnvals;
			$mainqry = $insmain.$insmainvals;
			$mconn->query($bkdqry);
			$mconn->query($mainqry);
			foreach($unknowns as $unknown){
				$mconn->query("Update tbl_closetobook set reconciliation='Unknown',book_status='Unknown', actule_referenceno='".$unknown["reference_no"]."',reference_no='".$unknown["reference_no"]."' where sno ='".$unknown["banksno"]."'");
			}
			$mconn->commit();
			$freturns=array('status'=>'Success', 'message'=>'Success');
		} catch (Exception $e) {
	        $mconn->rollback();
	        $freturns = array('status' => 'Error', 'message' => $e->getMessage());
	    }
	}
	return $freturns;
}

function reconstructbookwith_unknown_credit($mconn,$companyid,$subcompanyid,$projectid,$accountid,$transactiontype){
	$beneficerytype="";
	$freturns=array();
	$pfrmb=$mconn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$accountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="";
	$account_type ="";
	$accountname ="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
	}
	$finyeartype = getfinancialyeartype($mconn,$companyid);
	$unreconsiledata=closetobookdetails($mconn,$companyid,$subcompanyid,$projectid,$transactiontype,'unreconcile_withaccountid',$accountid);
	$fin_years = [];
	foreach ($unreconsiledata as $impkey) {
		if (isset($impkey['value_date'])) {
			$financialYear = getFinancialYear($impkey['value_date'],$finyeartype);
			if (!in_array($financialYear, $fin_years)) {
				$fin_years[] = $financialYear;
			}
		}
	}
	$data = [];
	foreach($fin_years as $fin_year){
		$total_unknown_amount = 0.0;
		$banksnocommaseparated = "";
		$valuedate = "";
		foreach ($unreconsiledata as $impkey) {
			$financialYear = getFinancialYear($impkey['value_date'],$finyeartype);
			if($financialYear==$fin_year){
				if(!$banksnocommaseparated){
					$banksnocommaseparated = $impkey['sno'];
				}
				else{
					$banksnocommaseparated = $banksnocommaseparated.", ".$impkey['sno'];
				}
				$total_unknown_amount += floatval($impkey['total_amount']);
				$valuedate = $impkey['value_date'];
			}
		}
		$data["accountid"] = $accountid;
		$data[$fin_year] = $total_unknown_amount;
		$pc=getcreditpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,'purposecategoryname','Unknown');
		$purpose="";
		$purposecode=""; 
		$purposecategory=""; 
		$categoryid="";
		$sub_categoryid="";
		$sub_category="";
		foreach ($pc as $keypc) {
			$purpose = $keypc['purpose'];
			$purposecode = $keypc['purposecode'];
			$purposecategory = $keypc['pcategory'];
			$categoryid=$keypc['pcategorycode'];
			$sub_categoryid=$keypc['psub_categoryid'];
			$sub_category=$keypc['psub_category'];
		}
		$tsno=0;
		$mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction_credit");		
		if($mtso->num_rows>0){
			$trow=$mtso->fetch_assoc();
			$tsno=$trow['maxtsno'];
		}
		$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
		$selqry = "select sno from tbl_maintransaction_credit where payfromaccountno='".$accountno."' and reconciliation='Done' and book_status='Unknown' and finyear='".$fin_year."'";
		$res = $mconn->query($selqry);
		if($res->num_rows>0){
			$flag= 0;
			$upqry = "update tbl_maintransaction_credit set book_amount ='".$total_unknown_amount."',beforeedit_amount='".$total_unknown_amount."',reconcilation_process='markunknown' where payfromaccountno='".$accountno."' and reconciliation='Done' and book_status='Unknown'  and finyear='".$fin_year."'";
			$mconn->query($upqry);
			if(mysqli_affected_rows($mconn)>0){
				$upqry = "update tbl_maintransaction_credit_breakdown set amount ='".$total_unknown_amount."' where reference_no=(select reference_no from tbl_maintransaction_credit where payfromaccountno='".$accountno."' and reconciliation='Done' and book_status='Unknown'  and finyear='".$fin_year."')";
				$flag=1;
				if($mconn->query($upqry)){
					$flag=1;
				}
				else{
					$flag=0;
				}
			}
		}
		else{
			$insq="Insert into tbl_maintransaction_credit_breakdown(companyid, subcompanyid, projectid, reference_no, beneficiary_name, amount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, description, description_bank) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$newreference_no."','','".$total_unknown_amount."','".$categoryid."','".$purposecategory."','".$sub_categoryid."','".$sub_category."','".$purpose."','".$purposecode."','Marked Unknown transaction','')";
			$mconn->query($insq);
			if(mysqli_affected_rows($mconn)>0){
				$mqr="Insert into tbl_maintransaction_credit(tsno, companyid, subcompanyid, projectid,  reference_no, transaction_date, bookdate,finyear, book_amount, beforeedit_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, is_intermediate_transfer, refundable_projectid,transactionno,book_source,book_status,referenceno_clb,reconciliation,value_date,reconciliation_banksno,reconcilation_process) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$valuedate."', '".$valuedate."', '".$fin_year."','".$total_unknown_amount."', '".$total_unknown_amount."', '', '', '".$bankname."', '".$accountno."', '".$accountid."', '".$paymenttype."', '".$beneficerytype."', '', '', '', '', '','','closetobook','Unknown','".$closetobook_referenceno."','Done','".$valuedate."','','markunknown')";
				$flag=1;
				if($mconn->query($mqr)){
					$flag=1;
				}
				else{
					$flag=0;
				}
			}
			else{
				$flag=0;
			}
		}
		if($flag==1){
			$mconn->query("Update tbl_closetobook set status='', reconciliation='Unknown',book_status='Unknown', actule_referenceno='".$newreference_no."',reference_no='".$closetobook_referenceno."' where sno in (".$banksnocommaseparated.")");
			$freturns[]=array('status'=>'Success', 'message'=>'Success', 'bnksno'=>'', 'chequeno'=>'', 'chequedate'=>'');
		}
		else{
			$freturns[]=array('status'=>'Error', 'message'=>'Error', 'bnksno'=>'', 'chequeno'=>'', 'chequedate'=>'');
		}
	}
	return $freturns;
}

function insertBillTransaction($conn, $data) {
    // Check if the record already exists
    $checkQuery = "SELECT 1 FROM strs_bill_transactions 
                   WHERE billid = ? AND MONTH(billingdate) = ? AND companyid = ? AND projectid = ?";
    $stmt = $conn->prepare($checkQuery);
    $rrst=[];
    if (!$stmt) {
    	$rrst=array('status'=>'Error', 'message'=>"Failed to prepare statement: " . $conn->error);
    	return $rrst;
        die();
    }

    // Bind parameters for the check query
    $stmt->bind_param("ssss", $data['billid'], date('m', strtotime($data['billingdate'])), $data['companyid'], $data['projectid']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Record exists, close statement and return a message
        $stmt->close();
        $rrst=array('status'=>'Error', 'message'=>"Duplicate record exists. Insert skipped.");
        return $rrst;
        die();
    }
    $stmt->close();

    // Proceed to insert if no duplicate exists
    $query = "INSERT INTO strs_bill_transactions (
                companyid, subcompanyid, projectid, subprojectid, billid, 
                typeofbillid, locationid, billdurationid, purposecode, paytoid, 
                beneficiaryname, billingdate, billduedate, billdescription, responsablepursonid
              ) VALUES (?,?,?,?,?,
                        ?,?,?,?,?, 
                        ?,?,?,?,?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $rrst=array('status'=>'Error', 'message'=>"Failed to prepare statement: " . $conn->error);
        return $rrst;
        die();
    }

    // Bind parameters for the insert query
    $stmt->bind_param(
        "sssssssssssssss", 
        $data['companyid'], 
        $data['subcompanyid'], 
        $data['projectid'], 
        $data['subprojectid'], 
        $data['billid'], 
        $data['typeofbillid'], 
        $data['locationid'], 
        $data['billdurationid'], 
        $data['purposecode'], 
        $data['paytoid'], 
        $data['beneficiaryname'], 
        $data['billingdate'], 
        $data['billduedate'], 
        $data['billdescription'], 
        $data['responsablepursonid']
    );

    // Execute insert query
    if ($stmt->execute()) {
        $insertedId = $stmt->insert_id; // Get last inserted ID
        $stmt->close();
        $rrst=array('status'=>'Success', 'message'=>"Save new record", 'insertid'=>$insertedId);
        return $rrst;
    } else {
        $stmt->close();
        $rrst=array('status'=>'Error', 'message'=>"Failed to execute query: " . $stmt->error, 'insertid'=>'');
        return $rrst;
    }
}

function insertBillTransaction_nonintigrated($conn, $data) {
    // Check if the record already exists
    $checkQuery = "SELECT 1 FROM bill_biltransactions_nint 
                   WHERE billid = ? AND MONTH(billingdate) = ? AND companyid = ? AND projectid = ?";
    $stmt = $conn->prepare($checkQuery);
    $rrst=[];
    if (!$stmt) {
    	$rrst=array('status'=>'Error', 'message'=>"Failed to prepare statement: " . $conn->error);
    	return $rrst;
        die();
    }

    // Bind parameters for the check query
    $stmt->bind_param("ssss", $data['billid'], date('m', strtotime($data['billingdate'])), $data['companyid'], $data['projectid']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Record exists, close statement and return a message
        $stmt->close();
        $rrst=array('status'=>'Error', 'message'=>"Duplicate record exists. Insert skipped.");
        return $rrst;
        die();
    }
    $stmt->close();

    // Proceed to insert if no duplicate exists
    $query = "INSERT INTO bill_biltransactions_nint (
                companyid, subcompanyid, projectid, subprojectid, billid, 
                typeofbillid, locationid, billdurationid, purposecode, paytoid, 
                beneficiaryname, billingdate, billduedate, billdescription, responsablepursonid
              ) VALUES (?,?,?,?,?,
                        ?,?,?,?,?, 
                        ?,?,?,?,?)";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        $rrst=array('status'=>'Error', 'message'=>"Failed to prepare statement: " . $conn->error);
        return $rrst;
        die();
    }

    // Bind parameters for the insert query
    $stmt->bind_param(
        "sssssssssssssss", 
        $data['companyid'], 
        $data['subcompanyid'], 
        $data['projectid'], 
        $data['subprojectid'], 
        $data['billid'], 
        $data['typeofbillid'], 
        $data['locationid'], 
        $data['billdurationid'], 
        $data['purposecode'], 
        $data['paytoid'], 
        $data['beneficiaryname'], 
        $data['billingdate'], 
        $data['billduedate'], 
        $data['billdescription'], 
        $data['responsablepursonid']
    );

    // Execute insert query
    if ($stmt->execute()) {
        $insertedId = $stmt->insert_id; // Get last inserted ID
        $stmt->close();
        $rrst=array('status'=>'Success', 'message'=>"Save new record", 'insertid'=>$insertedId);
        return $rrst;
    } else {
        $stmt->close();
        $rrst=array('status'=>'Error', 'message'=>"Failed to execute query: " . $stmt->error, 'insertid'=>'');
        return $rrst;
    }
}

function updatepaidstatus_bills($conn, $companyid, $subcompanyid, $projectid) {
    // Base query to fetch bill transactions
    $queryBase = "SELECT sno, referenceno FROM strs_bill_transactions 
                  WHERE (tedit IS NULL OR tedit = '') 
                  AND referenceno <> '' 
                  AND (paidstatus IS NULL OR paidstatus = '') 
                  AND companyid = ? 
                  AND subcompanyid = ? 
                  AND projectid = ?";
    $params = [$companyid, $subcompanyid, $projectid];
    $types = "sss";
    // Prepare the base statement
    $stmt = $conn->prepare($queryBase);
    if (!$stmt) {
        die("Failed to prepare statement: " . $conn->error);
    }
    // Bind parameters and execute
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    // Iterate through results
    while ($row = $result->fetch_assoc()) {
        // Query to check transactions in tbl_maintransaction
        $querySub = "SELECT book_amount, ifnull(transfered_amount,book_amount) as transfered_amount, transactionno, transaction_date FROM tbl_maintransaction 
                     WHERE (tedit IS NULL OR tedit = '') AND transactionno<>''
                     AND companyid = ? 
                     AND subcompanyid = ? 
                     AND projectid = ? 
                     AND request_no = ?";
        $paramsSub = [$companyid, $subcompanyid, $projectid, $row['referenceno']];
        $typesSub = "ssss";
        // Prepare the subquery
        $stmtSub = $conn->prepare($querySub);
        if (!$stmtSub) {
            die("Failed to prepare statement: " . $conn->error);
        }
        // Bind parameters and execute
        $stmtSub->bind_param($typesSub, ...$paramsSub);
        $stmtSub->execute();
        $resultSub = $stmtSub->get_result();
        // Check if a matching transaction exists
        if ($resultSub->num_rows > 0) {
            $transaction = $resultSub->fetch_assoc();
            $paidStatus = "Paid with Transaction no " . $transaction['transactionno'] . " on date " . $transaction['transaction_date'];
            // Update the paidstatus in strs_bill_transactions
            $updateQuery = "UPDATE strs_bill_transactions 
                            SET paidstatus = ?, billamount = ?, transactionno = ?, transactiondate = ?, transferedamount = ? 
                            WHERE sno = ?";
            $updateStmt = $conn->prepare($updateQuery);
            if (!$updateStmt) {
                die("Failed to prepare update statement: " . $conn->error);
            }
            $updateStmt->bind_param("ssssss", $paidStatus,$transaction['book_amount'],$transaction['transactionno'],$transaction['transaction_date'],$transaction['transfered_amount'], $row['sno']);
            $updateStmt->execute();
            $updateStmt->close();
        }
        // Close subquery statement
        $stmtSub->close();
    }
    // Close base statement
    $stmt->close();
}

function InsertAutodeduction_all($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$paymentmode,$transactionno,$transactiondate,$transferamount,$paytoid,$paytoname,$beneficiaryname,$purposecode,$description,$descriptionbank,$taxableamount,$taxamount,$reconciliedstatus,$banksno,$loginname=null,$documentationpath=""){
	$rst=array();
	$pfrmb=$conn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$fromaccountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="";
	$account_type ="";
	$accountname ="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
	}
	//payto details
	$beneficerytype="Individual";
	$getpurposestring=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode_all",$purposecode);
	if(count($getpurposestring)>0){
        foreach ($getpurposestring as $keypc) {
			$pcat = $keypc['pcategory'];
			$pcatid=$keypc['pcategorycode'];
			$purpose = $keypc['purpose'];
			$purposecode = $keypc['purposecode'];
			$psubcatid=$keypc['psub_categoryid'];
			$psubcat=$keypc['psub_category'];
			$paymenttype=$keypc['pcategory'];
		}
    }else{
    	$rst=array('status'=>'Error', 'message'=>'Purpose details missing/wrong, Please try again with purpose code');
    	return $rst;
    	die();
    }

	if($banksno>0){
    	$tsno=GetMaxRefTempSno($conn);
	    $newreference_no = getnewreferenceno($conn,$companyid,$subcompanyid,$projectid,$accountno);
		$insq="INSERT INTO tbl_maintransaction_breakdown(companyid,subcompanyid,projectid,subprojectid,reference_no,beneficiary_name,amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,description_bank,taxableamount,gstamount,documentation_path) VALUES";
		$dbsno_exp="";
		if($chequedate){
            $bookdate = $chequedate;
        }else{
        	$bookdate =date('Y-m-d');
        }
        $brkqry="";
	    if($paytoid!="" && $beneficiaryname!="" && $purposecode!="" && $transferamount>0) {
           	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$transferamount."', '".addslashes($pcatid)."', '".addslashes($pcat)."', '".addslashes($psubcatid)."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '".addslashes($description)."', '".addslashes($descriptionbank)."','".$taxableamount."','".$taxamount."','".$documentationpath."')";	            
    	}
    	if($brkqry){
    		$brkqry1=$insq.$brkqry;
    		$conn->query($brkqry1);
    		if(mysqli_affected_rows($conn)>0){
    			$booksource = "Auto deduct while reconciliation on date".date('Y-m-d h:i:s')." by ".$loginname;
    			if(strtolower($reconciliedstatus)=="done"){
    				$reconciliedstatus="Done";
    				$finalstatus="reconciled";
    			}else{
    				$reconciliedstatus="";
					$finalstatus="";
    			}
    			
    			$recproccess = "Manual reconciliation on date".date('Y-m-d h:i:s')." by ".$loginname;
    			$mqr="INSERT INTO tbl_maintransaction(tsno,companyid,subcompanyid,projectid,subprojectid,reference_no,bookdate,book_amount,beforeedit_amount,transaction_date,transfered_amount,transactionno,transaction_type,paytoid,payto,payfrombankname,payfromaccountno,payfromaccountid,paymenttype,beneficierie,reconciliation,reconciliation_banksno,value_date,book_source,book_status,final_status,reconcilation_process) VALUES('".$tsno."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$newreference_no."','".$transactiondate."','".$transferamount."','".$transferamount."','".$transactiondate."','".$transferamount."','".$transactionno."','online','".$paytoid."','".addslashes($paytoname)."','".addslashes($bankname)."','".$accountno."','".$accountid."','".$paymenttype."','".$beneficerytype."','".$reconciliedstatus."','".$banksno."','".$transactiondate."','".$booksource."','completed','".$finalstatus."','".$recproccess."')";
            	$conn->query($mqr);
            	if(mysqli_affected_rows($conn)>0){
            		$rst=array('status'=>'Success', 'message'=>'Auto-detection book entry saved successfully.', 'referenceno'=>$newreference_no, 'requestno'=>'');
            	}else{
            		$rst=array('status'=>'Error', 'message'=>'Auto-detection book entry did not save, Due to technical issue', 'referenceno'=>'');
            	}
            }else{
            	$rst=array('status'=>'Error', 'message'=>'Auto-detection book entry of payment did not created, Error in breakdown');
            }
    	}else{
    		$rst=array('status'=>'Error', 'message'=>'Some information is missing like payto/beneficiary name/purpose code / transfer amountError in query failed, Please try again');
    	}
    }else {
    	$rst=array('status'=>'Error', 'message'=>'Deduct from bank reference no is missing, Please try again with bank reference no');
    }    
    return $rst;
}

function InsertAutodeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$paymentmode,$transactionno,$transactiondate,$transferamount,$paytoid,$paytoname,$beneficiaryname,$purposecode,$description,$descriptionbank,$taxableamount,$taxamount,$reconciliedstatus,$banksno,$oldreferenceno=null,$loginname=null,$documentationpath=""){
	$rst=array();
	$transactionno!=""?$transactionno:"autoreconciled";
	$transferamount = number_format(floatval($transferamount), 2, '.', '');
	$pfrmb=$conn->query("SELECT accountid, bankid, bankname, accountno, account_type, accountname FROM tbl_master_account where accountid='".$fromaccountid."'");
	$bankid ="";
	$bankname ="";
	$accountno ="";
	$account_type ="";
	$accountname ="";
	if($pfrmb->num_rows>0){
		$brow=$pfrmb->fetch_assoc();
		$bankid = $brow['bankid'];
		$bankname = $brow['bankname'];
		$accountno = $brow['accountno'];
		$account_type = $brow['account_type'];
		$accountname = $brow['accountname'];
		$accountid = $brow['accountid'];
	}
	//payto details
	$beneficerytype="Individual";
	$getpurposestring=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	if(count($getpurposestring)>0){
        foreach ($getpurposestring as $keypc) {
			$pcat = $keypc['pcategory'];
			$pcatid=$keypc['pcategorycode'];
			$purpose = $keypc['purpose'];
			$purposecode = $keypc['purposecode'];
			$psubcatid=$keypc['psub_categoryid'];
			$psubcat=$keypc['psub_category'];
			$paymenttype=$keypc['pcategory'];
		}
    }else{
    	$rst=array('status'=>'Error', 'message'=>'Purpose details missing/wrong, Please try again with purpose code');
    	return $rst;
    	die();
    }

	if($banksno>0){
    	$tsno=GetMaxRefTempSno($conn);
	    $newreference_no = getnewreferenceno($conn,$companyid,$subcompanyid,$projectid,$accountno);
		$insq="INSERT INTO tbl_maintransaction_breakdown(companyid,subcompanyid,projectid,subprojectid,reference_no,beneficiary_name,amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,description_bank,taxableamount,gstamount,documentation_path) VALUES";
		$dbsno_exp="";
		if($chequedate){
            $bookdate = $chequedate;
        }else{
        	$bookdate =date('Y-m-d');
        }
        $brkqry="";
        // echo $paytoid."==".$beneficiaryname."==".$purposecode."==".$transferamount;
	    if($paytoid!="" && $beneficiaryname!="" && $purposecode!="" && $transferamount>0) {
           	$brkqry= "('".$companyid."', '".$subcompanyid."', '".$projectid."','".$subprojectid."', '".$newreference_no."', '".addslashes($beneficiaryname)."', '".$transferamount."', '".addslashes($pcatid)."', '".addslashes($pcat)."', '".addslashes($psubcatid)."', '".addslashes($psubcat)."', '".addslashes($purpose)."', '".$purposecode."', '".addslashes($description)."', '".addslashes($descriptionbank)."','".$taxableamount."','".$taxamount."','".addslashes($documentationpath)."')";	            
    	}
    	if($brkqry){
    		$brkqry1=$insq.$brkqry;
    		$conn->query($brkqry1);
    		if(mysqli_affected_rows($conn)>0){
    			$booksource = "Auto deduct while reconciliation on date".date('Y-m-d h:i:s')." by ".$loginname;
    			if(strtolower($reconciliedstatus)=="done"){
    				$reconciliedstatus="Done";
    				$finalstatus="reconciled";
    			}else{
    				$reconciliedstatus="";
					$finalstatus="";
    			}
    			$recproccess = "while reconciliation of referenceno: ".$oldreferenceno.", On date".date('Y-m-d h:i:s')." by ".$loginname;
    			$mqr="INSERT INTO tbl_maintransaction(tsno,companyid,subcompanyid,projectid,subprojectid,reference_no,bookdate,book_amount,beforeedit_amount,transaction_date,transfered_amount,transactionno,transaction_type,paytoid,payto,payfrombankname,payfromaccountno,payfromaccountid,paymenttype,beneficierie,reconciliation,reconciliation_banksno,value_date,book_source,book_status,final_status,reconcilation_process,requestedby) VALUES('".$tsno."','".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$newreference_no."','".$transactiondate."','".$transferamount."','".$transferamount."','".$transactiondate."','".$transferamount."','".$transactionno."','autodeduct','".$paytoid."','".addslashes($paytoname)."','".addslashes($bankname)."','".addslashes($accountno)."','".$accountid."','".$paymenttype."','".addslashes($beneficerytype)."','".addslashes($reconciliedstatus)."','".$banksno."','".$transactiondate."','".addslashes($booksource)."','completed','".$finalstatus."','".$recproccess."','auto genrate while reconciliation')";
            	$conn->query($mqr);
            	if(mysqli_affected_rows($conn)>0){
            		$rst=array('status'=>'Success', 'message'=>'Auto-detection book entry saved successfully.', 'referenceno'=>$newreference_no, 'requestno'=>'');
            	}else{
            		$rst=array('status'=>'Error', 'message'=>'Auto-detection book entry did not save, Due to technical issue', 'referenceno'=>'');
            	}
            }else{
            	$rst=array('status'=>'Error', 'message'=>'Auto-detection book entry of payment did not created, Error in breakdown');
            }
    	}else{
    		$rst=array('status'=>'Error', 'message'=>'Some information is missing like payto/beneficiary name/purpose code / transfer amountError in query failed, Please try again');
    	}
    }else {
    	$rst=array('status'=>'Error', 'message'=>'Deduct from bank reference no is missing, Please try again with bank reference no');
    }    
    return $rst;
}

function reconcilation_condtion_1($conn,$companyid,$subcompanyid,$projectid,$accountid,$diffamount,$datefrom="",$dateto=""){
	$shortbyvalue = $accountid."|".$datefrom."|".$dateto;

	$bnkentry = closetobookdetails($conn, $companyid, $subcompanyid, $projectid, "withdrawn", "unreconcile_withaccountidwithdates", $shortbyvalue);
	$updatedCount = 0;
	if (count($bnkentry) > 0) {
	    foreach ($bnkentry as $nmkey) {
	        if ($nmkey['chequeno']!="" && $nmkey['total_amount'] > 0 && $nmkey['value_date']!="") {

	           $bookAmount = number_format((float)$nmkey['total_amount'], 2, '.', '');
	           $bbnkqq = $conn->query("SELECT sno, reference_no FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '$companyid'  AND subcompanyid = '$subcompanyid' AND projectid = '$projectid'  AND payfromaccountid IN ('$accountid') AND transactionno = '" . addslashes(strtolower($nmkey['chequeno'])) . "'  AND DATE(transaction_date) <= '" . date('Y-m-d', strtotime($nmkey['value_date'])) . "' AND transfered_amount = '".str_replace(".00", "", $bookAmount)."'");
	            if ($bbnkqq->num_rows === 1) {
	                $mrow = $bbnkqq->fetch_assoc();
	                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mrow['reference_no'] . "', reference_no = '" . $mrow['sno'] . "' WHERE sno = '" . $nmkey['sno'] . "'");
	                if ($conn->affected_rows > 0) {
	                    $conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 1', reconciliation_banksno = '" . $nmkey['sno'] . "', value_date = '" . $nmkey['value_date'] . "' WHERE sno = '" . $mrow['sno'] . "' AND (tedit IS NULL OR tedit = '')");
	                    if ($conn->affected_rows > 0) {
	                        $updatedCount++;
	                    }
	                }
	            }else if ($bbnkqq->num_rows > 1) {
	            	/*$bbnkqq = $conn->query("SELECT sno, reference_no,payfromaccountid FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '$companyid'  AND subcompanyid = '$subcompanyid' AND projectid = '$projectid'  AND payfromaccountid IN ('$accountid')  AND DATE(transaction_date) <= '" . date('Y-m-d', strtotime($nmkey['value_date'])) . "' AND transfered_amount = '".str_replace(".00", "", $bookAmount)."'");

		            if ($bbnkqq->num_rows>0) {
		                while($mrow = $bbnkqq->fetch_assoc()){
			                if(extractNumbersFromTransactionNo($mrow['transactionno'])==extractNumbersFromTransactionNo($nmkey['chequeno'])){
				                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mrow['reference_no'] . "', reference_no = '" . $mrow['sno'] . "' WHERE sno = '" . $nmkey['sno'] . "'");
				                if ($conn->affected_rows > 0) {
				                	$conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 1', reconciliation_banksno = '" . $nmkey['sno'] . "', value_date = '" . $nmkey['value_date'] . "', payfromaccountid='".$nmkey['account_id']."' WHERE sno = '" . $mrow['sno'] . "' AND (tedit IS NULL OR tedit = '')");
				                    if ($conn->affected_rows > 0) {
				                        $updatedCount++;
				                    }
				                }
				                continue;
				            }
				        }
		            }*/
	            }
	        }else{
	        	$updatedCount = 00;
	        }
	    }
	}
	return $updatedCount;
}

function reconcilation_condtion_2($conn,$companyid,$subcompanyid,$projectid,$accountid,$diffamount,$datefrom="",$dateto=""){
	$shortbyvalue = $accountid."|".$datefrom."|".$dateto;
	
	$bnkentry = closetobookdetails($conn, $companyid, $subcompanyid, $projectid, "withdrawn", "unreconcile_withaccountidwithdates", $shortbyvalue);

	$bnkchargepurpose=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposename","Bank Charge");
	$bankchargepurpose="";
	if(count($bnkchargepurpose)>0){
		foreach ($bnkchargepurpose as $bbkkey) {
			$bankchargepurpose=$bbkkey['purposecode'];
		}
	}
	$updatedCount = 0;
	if (count($bnkentry) > 0) {
	    foreach ($bnkentry as $nmkey) {
	        if (!empty($nmkey['chequeno']) && $nmkey['total_amount'] > 0 && isValidDate($nmkey['value_date'],'Y-m-d')) {
	            $bookAmount = number_format((float)$nmkey['total_amount'], 2, '.', '');

	            $minAmount = str_replace(".00", "", $bookAmount) - $diffamount;
	            $maxAmount = str_replace(".00", "", $bookAmount) + $diffamount;
	            
	            $bbnkqq = $conn->query("SELECT sno,companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, transfered_amount, beforeedit_amount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '') AND payfromaccountid<>''  AND companyid = '$companyid'  AND subcompanyid = '$subcompanyid' AND projectid = '$projectid'  AND payfromaccountid IN ('$accountid') AND transactionno = '".addslashes(strtolower($nmkey['chequeno'])). "' AND transfered_amount>= '".$minAmount."' AND transfered_amount<= '".$maxAmount."'");
	            if ($bbnkqq->num_rows === 1) {
	                $mrow = $bbnkqq->fetch_assoc();
	                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mrow['reference_no'] . "', reference_no = '" . $mrow['sno'] . "' WHERE sno = '" . $nmkey['sno'] . "'");
	                if ($conn->affected_rows > 0) {
	                    $conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 2', reconciliation_banksno = '" . $nmkey['sno'] . "', value_date = '" . $nmkey['value_date'] . "' WHERE sno = '" . $mrow['sno'] . "' AND (tedit IS NULL OR tedit = '')");
	                    if ($conn->affected_rows > 0) {
	                        $updatedCount++;
	                        $exactDiffAmount = $bookAmount - $mrow['book_amount'];
			                if($exactDiffAmount>0){
			                	$autoddescription="Auto detduction with Adjust Amount (Transfered Amount is ".$bookAmount." and Book Amount is ".$mrow['book_amount'].")";
			                	InsertAutodeduction($conn, $mrow['companyid'],$mrow['subcompanyid'],$mrow['projectid'],$mrow['subprojectid'],$accountid,$mrow['transaction_type'],$mrow['transactionno'],$mrow['transaction_date'],round($exactDiffAmount,2),$mrow['paytoid'],$mrow['payto'],$mrow['payto'],$bankchargepurpose,$autoddescription,$nmkey['description_bank'],'','','done',$nmkey['sno'],$mrow['reference_no'],"Auto with Reconciliation","");
			                }
	                    }
	                }
	            }
	        }
	    }
	}
	return $updatedCount;
}

function reconcilation_condtion_3($conn,$companyid,$subcompanyid,$projectid,$accountid,$diffamount,$datefrom="",$dateto=""){
	$shortbyvalue = $accountid."|".$datefrom."|".$dateto;
	
	$bnkentry = closetobookdetails($conn, $companyid, $subcompanyid, $projectid, "withdrawn", "unreconcile_withaccountidwithdates", $shortbyvalue);
	$updatedCount = 0;
	if (count($bnkentry) > 0) {
	    foreach ($bnkentry as $nmkey) {
	        if ($nmkey['total_amount'] > 0 && $nmkey['value_date']!="") {
	        	if($nmkey['actualtransfered_amount']>0){
		            $bookAmount = number_format((float)$nmkey['actualtransfered_amount'], 2, '.', '');
	        	}else{
		            $bookAmount = number_format((float)$nmkey['total_amount'], 2, '.', '');
	        	}
	            $minAmount = str_replace(".00", "", $bookAmount) - $diffamount;
	            $maxAmount = str_replace(".00", "", $bookAmount) + $diffamount;
	            
	            $bbnkqq = $conn->query("SELECT sno, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, transfered_amount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '".$companyid."'  AND subcompanyid = '".$subcompanyid."' AND projectid = '".$projectid."'  AND payfromaccountid IN ('".$accountid."') AND DATE(transaction_date) = '" . date('Y-m-d', strtotime($nmkey['value_date'])) . "' AND transfered_amount>= ".$minAmount." AND transfered_amount<= ".$maxAmount." and lcase(payto) like '%".strtolower($nmkey['payto'])."%' order by sno desc");
	            if ($bbnkqq->num_rows>0) {
	                $mrow = $bbnkqq->fetch_assoc();
	                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mrow['reference_no'] . "', reference_no = '" . $mrow['sno'] . "' WHERE sno = '" . $nmkey['sno'] . "'");
	                if ($conn->affected_rows > 0) {
	                    $conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 3', reconciliation_banksno = '" . $nmkey['sno'] . "', value_date = '" . $nmkey['value_date'] . "' WHERE sno = '" . $mrow['sno'] . "' AND (tedit IS NULL OR tedit = '')");
	                    if ($conn->affected_rows > 0) {
	                        $updatedCount++;	                        
	                    }
	                }
	            }else{
	            	$bbnkqq = $conn->query("SELECT sno, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, transfered_amount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '".$companyid."'  AND subcompanyid = '".$subcompanyid."' AND projectid = '".$projectid."'  AND payfromaccountid IN ('".$accountid."') AND DATE(transaction_date) = '" . date('Y-m-d', strtotime($nmkey['value_date'])) . "' AND transfered_amount>= ".$minAmount." AND transfered_amount<= ".$maxAmount." order by sno desc");
		            if ($bbnkqq->num_rows>0) {
		                $mrow = $bbnkqq->fetch_assoc();
		                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mrow['reference_no'] . "', reference_no = '" . $mrow['sno'] . "' WHERE sno = '" . $nmkey['sno'] . "'");
		                if ($conn->affected_rows > 0) {
		                    $conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 3', reconciliation_banksno = '" . $nmkey['sno'] . "', value_date = '" . $nmkey['value_date'] . "' WHERE sno = '" . $mrow['sno'] . "' AND (tedit IS NULL OR tedit = '')");
		                    if ($conn->affected_rows > 0) {
		                        $updatedCount++;	                        
		                    }
		                }
		            }
	            }
	        }
	    }
	}
	return $updatedCount;
}

function reconcilation_condtion_3___changesasperrajsir____($conn,$companyid,$subcompanyid,$projectid,$accountid,$diffamount,$datefrom="",$dateto=""){
	$shortbyvalue = $accountid."|".$datefrom."|".$dateto;
	
	$bnkentry = closetobookdetails($conn, $companyid, $subcompanyid, $projectid, "withdrawn", "unreconcile_withaccountidwithdates", $shortbyvalue);
	$updatedCount = 0;
	if (count($bnkentry) > 0) {
	    foreach ($bnkentry as $nmkey) {
	        if ($nmkey['total_amount'] > 0 && $nmkey['value_date']!="") {
	        	if($nmkey['actualtransfered_amount']>0){
		            $bookAmount = number_format((float)$nmkey['actualtransfered_amount'], 2, '.', '');
	        	}else{
		            $bookAmount = number_format((float)$nmkey['total_amount'], 2, '.', '');
	        	}
	            $minAmount = str_replace(".00", "", $bookAmount) - $diffamount;
	            $maxAmount = str_replace(".00", "", $bookAmount) + $diffamount;
	            
	            $bbnkqq = $conn->query("SELECT sno, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, transfered_amount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '".$companyid."'  AND subcompanyid = '".$subcompanyid."' AND projectid = '".$projectid."'  AND payfromaccountid IN ('".$accountid."') AND DATE(transaction_date) = '" . date('Y-m-d', strtotime($nmkey['value_date'])) . "' AND transfered_amount>= ".$minAmount." AND transfered_amount<= ".$maxAmount."");
	            if ($bbnkqq->num_rows === 1) {
	                $mrow = $bbnkqq->fetch_assoc();
	                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mrow['reference_no'] . "', reference_no = '" . $mrow['sno'] . "' WHERE sno = '" . $nmkey['sno'] . "'");
	                if ($conn->affected_rows > 0) {
	                    $conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 3', reconciliation_banksno = '" . $nmkey['sno'] . "', value_date = '" . $nmkey['value_date'] . "' WHERE sno = '" . $mrow['sno'] . "' AND (tedit IS NULL OR tedit = '')");
	                    if ($conn->affected_rows > 0) {
	                        $updatedCount++;	                        
	                    }
	                }
	            }else if ($bbnkqq->num_rows> 1) {
	            	$shorexp = explode("|", $shortbyvalue);
			        $accountid = $shorexp[0];
			        $datefrom = $shorexp[1];
			        $dateto = $shorexp[2];
			        if(detectDateTimeFormat($shorexp[1])){
			            $datefrom=convertDateToYMD($shorexp[1]);
			        }
			        if(detectDateTimeFormat($shorexp[2])){
			            $dateto=convertDateToYMD($shorexp[2]);
			        }
			        // check multiple entry available same amount and date in bank and book
			        // If bank and book have same entry than mark reconciled with likely payto name
			        $clbkkk=$conn->query("SELECT sno, company_id, subcompany_id, project_id, country_id, date as date, date as value_date, paymentmode, chequeno, payto, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, bankcharge_amount, actualtransfered_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and lcase(amounttype)='withdrawn' and account_id='".$accountid."' and date>='".$datefrom."' and date<='".$dateto."' and ifnull(total_amount,0)>0  AND total_amount>= ".$minAmount." AND total_amount<= ".$maxAmount." and (status is null or status='') and (book_status='' or book_status is null) order by banksno");
	            	if($clbkkk->num_rows>0){
	            		// echo "cot:: ".$clbkkk->num_rows."==".$bbnkqq->num_rows;
	            		if($clbkkk->num_rows==$bbnkqq->num_rows){
	            			while($clrow=$clbkkk->fetch_assoc()){
	            				if($clrow['payto']){
	            					if($clrow['actualtransfered_amount']>0){
							            $bookAmount = number_format((float)$clrow['actualtransfered_amount'], 2, '.', '');
						        	}else{
							            $bookAmount = number_format((float)$clrow['total_amount'], 2, '.', '');
						        	}
		            				// $bookAmount_m = number_format((float)$clrow['total_amount'], 2, '.', '');
						            $minAmount_m = str_replace(".00", "", $bookAmount_m) - $diffamount;
						            $maxAmount_m = str_replace(".00", "", $bookAmount_m) + $diffamount;
						            $bbnkqq = $conn->query("SELECT sno, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, transfered_amount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '".$companyid."'  AND subcompanyid = '".$subcompanyid."' AND projectid = '".$projectid."'  AND payfromaccountid IN ('".$accountid."') AND DATE(transaction_date) = '" . date('Y-m-d', strtotime($clrow['value_date'])) . "' AND transfered_amount>= ".$minAmount_m." AND transfered_amount<= ".$maxAmount_m." and lcase(payto) like '%".strtolower($clrow['payto'])."%'");
						            if ($bbnkqq->num_rows>0) {
						                $mroww = $bbnkqq->fetch_assoc();
						                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mroww['reference_no'] . "', reference_no = '" . $mroww['sno'] . "' WHERE sno = '" . $clrow['sno'] . "'");
						                if ($conn->affected_rows > 0) {
						                    $conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 3 with multiple entry', reconciliation_banksno = '" . $clrow['sno'] . "', value_date = '" . $clrow['value_date'] . "' WHERE sno = '" . $mroww['sno'] . "' AND (tedit IS NULL OR tedit = '')");
						                    if ($conn->affected_rows > 0) {
						                        $updatedCount++;	                        
						                    }
						                }						            
						            }
						        }
					        }
	            		}
	            	}
	            }else{

	            }
	        }
	    }
	}
	return $updatedCount;
}

function reconcilation_condtion_4($conn,$companyid,$subcompanyid,$projectid,$accountid,$diffamount,$datefrom="",$dateto=""){
	$shortbyvalue = $accountid."|".$datefrom."|".$dateto;
	
	$bnkentry = closetobookdetails($conn, $companyid, $subcompanyid, $projectid, "withdrawn", "unreconcile_withaccountidwithdates", $shortbyvalue);
	
	
	$bnkchargepurpose=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposename","Bank Charge");
	$bankchargepurpose="";
	if(count($bnkchargepurpose)>0){
		foreach ($bnkchargepurpose as $bbkkey) {
			$bankchargepurpose=$bbkkey['purposecode'];
		}
	}
	$updatedCount = 0;
	if (count($bnkentry) > 0) {
	    foreach ($bnkentry as $nmkey) {
	        if ($nmkey['chequeno']!="" && $nmkey['total_amount'] > 0 && isValidDate($nmkey['value_date'],'Y-m-d')) {
	            $bookAmount = number_format((float)$nmkey['total_amount'], 2, '.', '');
	            $diffamount=0;
	            $ArrBank =[];
	            $ArrBook =[];
	            $ArrBank=getUnreconcilied_BankEntrywithAmoutDate($conn, $companyid, $subcompanyid, $projectid, $accountid, 'withdrawn', $nmkey['value_date'], $nmkey['total_amount']);
	            $ArrBook=getUnreconcilied_BookEntrywithAmoutDate($conn, $companyid, $subcompanyid, $projectid, $accountid, '', $nmkey['value_date'], $nmkey['total_amount']);
	            
	            if(count($ArrBank)==count($ArrBook)){
	            	$bbnkqq = $conn->query("SELECT sno,companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, transfered_amount, beforeedit_amount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '$companyid'  AND subcompanyid = '$subcompanyid' AND projectid = '$projectid'  AND payfromaccountid IN ('$accountid') AND transfered_amount= '".str_replace(".00", "", $bookAmount)."' AND DATE(bookdate) <= '" . date('Y-m-d', strtotime($nmkey['value_date'])) . "'");
		            if ($bbnkqq->num_rows>0) {
		                while($mrow = $bbnkqq->fetch_assoc()){
		                	// echo "-----".extractNumbersFromTransactionNo($mrow['transactionno'])."==".extractNumbersFromTransactionNo($nmkey['chequeno']);

		                	if(extractNumbersFromTransactionNo($mrow['transactionno'])==extractNumbersFromTransactionNo($nmkey['chequeno']) && $bbnkqq->num_rows==1){
				                $conn->query("UPDATE tbl_closetobook SET status = 'Done', reconciliation = 'Done', book_status = 'completed',  actule_referenceno = '" . $mrow['reference_no'] . "', reference_no = '" . $mrow['sno'] . "' WHERE sno = '" . $nmkey['sno'] . "'");
				                if ($conn->affected_rows > 0) {
				                    $conn->query("UPDATE tbl_maintransaction SET reconciliation = 'Done', reconcilation_process='Auto Process 4', reconciliation_banksno = '" . $nmkey['sno'] . "', value_date = '" . $nmkey['value_date'] . "' WHERE sno = '" . $mrow['sno'] . "' AND (tedit IS NULL OR tedit = '')");
				                    if ($conn->affected_rows > 0) {
				                        $updatedCount++;
				                    }
				                }
				            }
			            }
		            }
		        }else{
		        	// echo "==not count";
		        }
	        }
	    }
	}
	return $updatedCount;
}

function getUnreconcilied_BankEntrywithAmoutDate($conn, $companyid, $subcompanyid, $projectid, $accountid, $transactiontype, $transactiondate, $transferamount){
	$clbk="SELECT sno, company_id, subcompany_id, project_id, country_id, date, paymentmode, chequeno, payto, beneficiaryname, bankname, accountname, accountno, account_id, bankid, amount,amounttype, tds, tds_amount, total_amount, paymenttype, purpose, purpose_code, categoryid, sub_category, category, sub_categoryid, description, description_bank, remarks, balancefrombank, tedit, tsno, reference_no, banksno, status, source, request_no, loginid, fileinfo, key1, key2, key3, keyid FROM tbl_closetobook WHERE (tedit is null or tedit='') and company_id='".$companyid."' AND subcompany_id='".$subcompanyid."' AND project_id='".$projectid."' AND lcase(amounttype)='".strtolower($transactiontype)."' AND account_id='".$accountid."' AND DATE(date)='".date('Y-m-d', strtotime($transactiondate))."' AND total_amount='".$transferamount."' AND ifnull(total_amount,0)>0 AND (status is null or status='') AND (book_status='' or book_status is null) order by banksno";
	$clbkr=$conn->query($clbk);
	$clbooklist=array();
	if($clbkr->num_rows>0){
		while($cbrow=$clbkr->fetch_assoc()){
			$bnkinf=getaccountinformation($conn,$cbrow['company_id'],$cbrow['subcompany_id'],$cbrow['project_id'],"accountid",$cbrow['account_id']);
			$accountname="";
			$accountno="";
			$ifsccode="";
			$bankname="";
			$accountholdername="";
			$accounttype="";
			if(count($bnkinf)>0){
				foreach ($bnkinf as $bnkey) {
					$accountname=$bnkey['accountname'];
					$accountno=$bnkey['accountno'];
					$ifsccode=$bnkey['ifsccode'];
					$bankname=$bnkey['bankname'];
					$accountholdername=$bnkey['accountholdername'];
					$accounttype=$bnkey['account_type'];
				}
			}
			$clbooklist[]=array('sno'=>$cbrow['sno'], 'company_id'=>$cbrow['company_id'], 'subcompany_id'=>$cbrow['subcompany_id'], 'project_id'=>$cbrow['project_id'], 'country_id'=>$cbrow['country_id'], 'value_date'=>$cbrow['date'], 'dspdate'=>date('d-M-Y', strtotime($cbrow['date'])), 'paymentmode'=>$cbrow['paymentmode'], 'chequeno'=>$cbrow['chequeno'], 'payto'=>$cbrow['payto'], 'beneficiaryname'=>$cbrow['beneficiaryname'], 'bankname'=>$cbrow['bankname'], 'accountname'=>$cbrow['accountname'], 'accountno'=>$cbrow['accountno'], 'account_id'=>$cbrow['account_id'], 'accounttype'=>$accounttype, 'bankid'=>$cbrow['bankid'], 'tds'=>$cbrow['tds'], 'tds_amount'=>$cbrow['tds_amount'], 'total_amount'=>$cbrow['total_amount'], 'amounttype'=>$cbrow['amounttype'],'paymenttype'=>$cbrow['paymenttype'], 'purpose'=>$cbrow['purpose'], 'purpose_code'=>$cbrow['purpose_code'], 'categoryid'=>$cbrow['categoryid'], 'sub_category'=>$cbrow['sub_category'], 'category'=>$cbrow['category'], 'sub_categoryid'=>$cbrow['sub_categoryid'], 'description'=>$cbrow['description'], 'description_bank'=>$cbrow['description_bank'], 'remarks'=>$cbrow['remarks'],'balancefrombank'=>$cbrow['balancefrombank'], 'tedit'=>$cbrow['tedit'], 'tsno'=>$cbrow['tsno'], 'reference_no'=>$cbrow['reference_no'], 'banksno'=>$cbrow['banksno'], 'status'=>$cbrow['status'], 'source'=>$cbrow['source'], 'request_no'=>$cbrow['request_no'], 'loginid'=>$cbrow['loginid'], 'fileinfo'=>$cbrow['fileinfo'], 'key1'=>$cbrow['key1'], 'key2'=>$cbrow['key2'], 'key3'=>$cbrow['key3'], 'keyid'=>$cbrow['keyid'], 'accountname'=>$accountname, 'accountno'=>$accountno,'bankname'=>$bankname);
		}
	}
	return $clbooklist;
}

function getUnreconcilied_BookEntrywithAmoutDate($conn, $companyid, $subcompanyid, $projectid, $accountid, $transactiontype, $transactiondate, $transferamount){
	$BBQ="SELECT sno,companyid, subcompanyid, projectid, subprojectid, request_no, reference_no, transactionno, bookdate, paymentduedate, transaction_date, transaction_type, book_amount, transfered_amount, beforeedit_amount, paytoid, payto, payfrombankname, payfromaccountno, payfromifsccode, payfromaccountid, payfrombankid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, reconciliation, reconciliation_banksno, transferreceiptpath, is_purchaseforproduction FROM tbl_maintransaction WHERE  (tedit IS NULL OR tedit = '') AND transactionno<>'' AND (reconciliation IS NULL OR reconciliation = '') AND (reconciliation_banksno IS NULL OR reconciliation_banksno = '')  AND companyid = '$companyid'  AND subcompanyid = '$subcompanyid' AND projectid = '$projectid'  AND payfromaccountid IN ('$accountid', '') AND transfered_amount= '".str_replace(".00", "", $transferamount)."' AND DATE(bookdate) <= '" . date('Y-m-d', strtotime($transactiondate)) . "'";
	$bbnkqq = $conn->query($BBQ);
	$rowss=[];
    if ($bbnkqq->num_rows>0) {
        while($mrow = $bbnkqq->fetch_assoc()){
        	$rowss[]=$mrow;
        }
    }
    return $rowss;
}

function extractNumbersFromTransactionNo($string) {
    $parts = explode('/', $string); // Split by '/'
    if (isset($parts[1])) { 
        return preg_replace('/\D/', '', $parts[1]); // Extract numbers from the second part
    }
    return preg_replace('/\D/', '', $string); // Extract numbers from the full string if '/' is not found
}

function addOrUpdateLoanAuthorizationDetails($conn, $companyid, $subcompanyid, $projectid, $loanpaytoid, $loanaccuntid, $loantype, $loanpaytoname, $mobileno, $emailid, $paytobankname, $paytoaccountno, $paytoifsccode, $purposecode, $remark, $loginby) {
    // SQL query for INSERT or UPDATE
    $query = "INSERT INTO loan_authorizedmaster (companyid, subcompanyid, projectid, loanpaytoid, loanaccuntid, loantype, loanpaytoname, mobileno, emailid, paytobankname, paytoaccountno, paytoifsccode, purposecode, remark, loginby) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
            ) ON DUPLICATE KEY UPDATE
                companyid = VALUES(companyid),
                subcompanyid = VALUES(subcompanyid),
                projectid = VALUES(projectid),
                loanpaytoid = VALUES(loanpaytoid),
                loanaccuntid = VALUES(loanaccuntid),
                loantype = VALUES(loantype),
                loanpaytoname = VALUES(loanpaytoname),
                mobileno = VALUES(mobileno),
                emailid = VALUES(emailid),
                paytobankname = VALUES(paytobankname),
                paytoaccountno = VALUES(paytoaccountno),
                paytoifsccode = VALUES(paytoifsccode),
                purposecode = VALUES(purposecode);";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Query preparation failed: " . $conn->error);
    }
    // Bind parameters
    $stmt->bind_param("sssssssssssssss", $companyid, $subcompanyid, $projectid, $loanpaytoid, $loanaccuntid, $loantype, $loanpaytoname, $mobileno, $emailid, $paytobankname, $paytoaccountno, $paytoifsccode, $purposecode, $remark, $loginby);

    // Execute the query
    if (!$stmt->execute()) {
        die("Execution failed: " . $stmt->error);
    }

    // Close the statement
    $stmt->close();

    return true;
}


function insertSalarySheetUplosdFunction($conn, $companyId, $subCompanyId, $projectId) {
    // First, check if the row already exists
    $checkQuery = "SELECT 1 FROM tbl_import_excelsheetdata_function 
                   WHERE companyid = ? AND subcompanyid = ? AND projectid = ? 
                   AND function_name = 'salaryfunction' AND uploadtype = 'salarysheet' 
                   LIMIT 1";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("iii", $companyId, $subCompanyId, $projectId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // Insert if not exists
        $insertQuery = "INSERT INTO tbl_import_excelsheetdata_function (
            companyid, subcompanyid, projectid, 
            key1, key2, key3, key4, key5, key6, key7, key8, key9, key10, key11, key12, key13, 
            hrow, htillcol, function_name, uploadtype
        ) VALUES (
            ?, ?, ?, 
            'NAME OF EMPLOYEES', 'Month of Salary', 'Designation', 'Actual Salary', 'DAYS', 
            'Monthly Salary', 'BANK NAME/ BRANCH', 'A/c No.', 'IFS CODE', 
            'ADVANCE DEDUCTION', 'TOTAL SALARY BANK TRANSFER', 'PURPOSE', 'Remark', 
            3, 13, 'salaryfunction', 'salarysheet'
        )";

        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iii", $companyId, $subCompanyId, $projectId);

        if ($insertStmt->execute()) {
            // echo "Inserted successfully for CompanyID: $companyId, SubCompanyID: $subCompanyId, ProjectID: $projectId<br>";
        } else {
            // echo "Insert failed: " . $insertStmt->error . "<br>";
        }
        $insertStmt->close();
    } else {
        // echo "Entry already exists for CompanyID: $companyId, SubCompanyID: $subCompanyId, ProjectID: $projectId<br>";
    }

    $stmt->close();
}

function insertOtherThanSalarySheetUplosdFunction($conn, $companyId, $subCompanyId, $projectId) {
    // First, check if the row already exists
    $checkQuery = "SELECT 1 FROM tbl_import_excelsheetdata_function 
                   WHERE companyid = ? AND subcompanyid = ? AND projectid = ? 
                   AND function_name = 'salaryfunction' AND uploadtype = 'salarysheet' 
                   LIMIT 1";
    
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("iii", $companyId, $subCompanyId, $projectId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) {
        // Insert if not exists
        $insertQuery = "INSERT INTO tbl_import_excelsheetdata_function (
            companyid, subcompanyid, projectid, 
            key1, key2, key3, key4, key5, key6, key7, key8, 
            hrow, htillcol, function_name, uploadtype
        ) VALUES (
            ?, ?, ?, 
            'NAME OF PAYTO', 'PAYTO BANK NAME', 'PAYTO A/C NO.', 'PAYTO BANK IFS CODE', 'TOTAL AMOUNT TRANSFER', 'PROVIDED PURPOSE CODE', 'PROVIDED PURPOSE NAME', 'REMARK', 
            3, 13, 'otherthansalaryfunction', 'otherthansalarysheet')";

        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->bind_param("iii", $companyId, $subCompanyId, $projectId);

        if ($insertStmt->execute()) {
            // echo "Inserted successfully for CompanyID: $companyId, SubCompanyID: $subCompanyId, ProjectID: $projectId<br>";
        } else {
            // echo "Insert failed: " . $insertStmt->error . "<br>";
        }
        $insertStmt->close();
    } else {
        // echo "Entry already exists for CompanyID: $companyId, SubCompanyID: $subCompanyId, ProjectID: $projectId<br>";
    }

    $stmt->close();
}


function sendEmailAfterApprovalCompany_cron($conn,$routurl,$loginid,$loginpassword,$loginname,$authorizedemail){
		if($authorizedemail){ 
			$m_subject = "Company registration successfully";
			$weblink=$routurl."loginCell/index.php";			
			$sendTo = $authorizedemail;
			$sendcc = "chatursingh011@gmail.com";
			$sendBcc = "";
			$attachments = ""; 
			$attch="";
			$m_msgbody = "<p>Hi ".$loginname.",</p><br> <p>We are pleased to inform you that your application for company registration has been successfully approved.</p><br><h3>Login Details<h3>
			<p><br/>Login URL: ".$weblink."
			<br/>Login ID: ".$loginid."
			<br/>Password: ".$loginpassword."</p>";
			$curnow = date('Y-m-d');
			$sql = "INSERT INTO emailsendby_cron (sendTo, sendcc, sendBcc, m_subject, m_msgbody, attch, generateddate,mailtype) VALUES ('".$sendTo."' ,'".$sendcc."' ,'".$sendBcc."' ,'".addslashes($m_subject)."' ,'".addslashes($m_msgbody)."' ,'".$attch."' ,'".$curnow."' ,'Company Registration')";
			$conn->query($sql);
			if(mysqli_affected_rows($conn)>0){
				return 'Send email successfully';
			}else{
				return 'Error in  saving email cron data';
			}
		}else{
			return 'email missing';
		}
}

function insertDefaultModulesToProject($conn, $frm_companyid, $frm_subcompanyid, $frm_projectid, $to_companyid, $to_subcompanyid, $to_projectid) {
    $inserted = 0;
    $updated = 0;
    $lmd = $conn->query("SELECT vieworderby, groupid, subgroupid, moduleid, modulename, modulefoldername, subaction, status, frmid, ispasswordprotected FROM login_modules_master_forcompany WHERE status='active' AND companyid='{$frm_companyid}' AND subcompanyid='{$frm_companyid}' AND projectid='{$frm_companyid}'");

    if ($lmd && $lmd->num_rows > 0) {
        while ($pmdlkey = $lmd->fetch_assoc()) {
            $checkSql = "
                SELECT 1 FROM login_modules_master_forcompany 
                WHERE companyid = '{$to_companyid}' 
                AND subcompanyid = '{$to_subcompanyid}' 
                AND projectid = '{$to_projectid}' 
                AND moduleid = '{$pmdlkey['moduleid']}'
            ";
            $checkResult = $conn->query($checkSql);

            if ($checkResult && $checkResult->num_rows > 0) {
                // Update
                $updateSql = "
                    UPDATE login_modules_master_forcompany SET
                        vieworderby = '{$pmdlkey['vieworderby']}',
                        groupid = '{$pmdlkey['groupid']}',
                        subgroupid = '{$pmdlkey['subgroupid']}',
                        modulename = '{$pmdlkey['modulename']}',
                        modulefoldername = '{$pmdlkey['modulefoldername']}',
                        subaction = '{$pmdlkey['subaction']}',
                        ispasswordprotected = '{$pmdlkey['ispasswordprotected']}',
                        frmid = '{$pmdlkey['frmid']}',
                        status = '{$pmdlkey['status']}'
                    WHERE companyid = '{$to_companyid}' 
                    AND subcompanyid = '{$to_subcompanyid}' 
                    AND projectid = '{$to_projectid}' 
                    AND moduleid = '{$pmdlkey['moduleid']}'
                ";
                if ($conn->query($updateSql)) {
                    $updated++;
                }
            } else {
                // Insert
                $insertSql = "
                    INSERT INTO login_modules_master_forcompany (
                        companyid, subcompanyid, projectid, vieworderby, groupid, subgroupid,
                        moduleid, modulename, modulefoldername, subaction, ispasswordprotected, status, frmid
                    ) VALUES (
                        '{$to_companyid}', '{$to_subcompanyid}', '{$to_projectid}',
                        '{$pmdlkey['vieworderby']}', '{$pmdlkey['groupid']}', '{$pmdlkey['subgroupid']}',
                        '{$pmdlkey['moduleid']}', '{$pmdlkey['modulename']}', '{$pmdlkey['modulefoldername']}',
                        '{$pmdlkey['subaction']}', '{$pmdlkey['ispasswordprotected']}', '{$pmdlkey['status']}', '{$pmdlkey['frmid']}'
                    )
                ";
                if ($conn->query($insertSql)) {
                    $inserted++;
                }
            }
        }
    }

    return "Modules inserted: {$inserted}, updated: {$updated}";
}

function UpdateInsertPurposeCodeMasterClient($conn, $data) {
    // Required unique fields to identify an existing record
    $companyid = $conn->real_escape_string($data['companyid']);
    $subcompanyid = $conn->real_escape_string($data['subcompanyid']);
    $projectid = $conn->real_escape_string($data['projectid']);
    $provided_code = $conn->real_escape_string($data['provided_code']);

    // Escape all fields
    foreach ($data as $key => $val) {
        $data[$key] = $conn->real_escape_string($val);
    }

    // Check if record exists
    $check = $conn->query("SELECT sno, subcategoryid, subcategoryname, providedcode, ms_purposecode FROM purpose_codemaster_client WHERE companyid='$companyid' AND subcompanyid='$subcompanyid' 
        AND projectid='$projectid' AND providedcode='$provided_code'");
    $rrsst=array();
    if ($check && $check->num_rows > 0) {
        // Update
        $row = $check->fetch_assoc();
        $sno = $row['sno'];

        $update = "UPDATE purpose_codemaster_client SET 
            subcategoryname='{$data['sub_category_client']}',
            providedcode='{$data['provided_code']}',
            purposename='{$data['purpose']}',
            addedby='{$data['addedby']}'
            WHERE sno='$sno'";        
        if ($conn->query($update)) {
        	$conn->query("UPDATE purpose_code_master SET purpose='".$data['purpose']."' WHERE sub_categoryid_client='".$data['sub_category_client']."' and provided_code_client='".$data['provided_code']."'");

            $rrsst = array('status'=>'Success', 'message'=>'Purpose ('.$data['purpose'].') updated successfully', 'doaction'=>'updated');
        } else {
        	$rrsst = array('status'=>'Error', 'message'=>'Update Failed: ' . $conn->error);
        }

    } else {
        // Insert
        $insert = "INSERT INTO purpose_codemaster_client (
            companyid, subcompanyid, projectid, subcategoryid, subcategoryname,
            providedcode, purposename, source, addedby
        ) VALUES (
            '{$data['companyid']}', '{$data['subcompanyid']}', '{$data['projectid']}', '{$data['sub_categoryid_client']}', '{$data['sub_category_client']}',
            '{$data['provided_code']}', '{$data['purpose']}', '{$data['source']}', '{$data['addedby']}'
        )";

        if ($conn->query($insert)) {
            $rrsst = array('status'=>'Success', 'message'=>'Purpose ('.$data['purpose'].') Inserted successfully', 'doaction'=>'inserted');
        } else {
        	$rrsst = array('status'=>'Error', 'message'=>'Insert Failed: ' . $conn->error);
        }
    }
    return $rrsst;
}


function UpdateInsertPurposeCodeMasterClient_Credit($conn, $data) {
    // Required unique fields to identify an existing record
    $companyid = $conn->real_escape_string($data['companyid']);
    $subcompanyid = $conn->real_escape_string($data['subcompanyid']);
    $projectid = $conn->real_escape_string($data['projectid']);
    $provided_code = $conn->real_escape_string($data['provided_code']);

    // Escape all fields
    foreach ($data as $key => $val) {
        $data[$key] = $conn->real_escape_string($val);
    }

    // Check if record exists
    $check = $conn->query("SELECT sno, subcategoryid, subcategoryname, providedcode, ms_purposecode FROM purpose_codemaster_client_credit WHERE companyid='$companyid' AND subcompanyid='$subcompanyid' AND projectid='$projectid' AND providedcode='$provided_code'");
    if ($check && $check->num_rows > 0) {
        $row = $check->fetch_assoc();
        $sno = $row['sno'];

        $update = "UPDATE purpose_codemaster_client_credit SET 
            subcategoryname='{$data['sub_category_client']}',
            providedcode='{$data['provided_code']}',
            purposename='{$data['purpose']}',
            addedby='{$data['addedby']}'
            WHERE sno='$sno'";
        
        if ($conn->query($update)) {
        	$conn->query("UPDATE purpose_code_master_credit SET purpose='".$data['purpose']."' WHERE sub_categoryid_client='".$data['sub_category_client']."' and provided_code_client='".$data['provided_code']."'");

            $rrsst = array('status'=>'Success', 'message'=>'Purpose ('.$data['purpose'].') updated successfully', 'doaction'=>'updated');
        } else {
        	$rrsst = array('status'=>'Error', 'message'=>'Update Failed: ' . $conn->error);
        }

    } else {
        // Insert
        $insert = "INSERT INTO purpose_codemaster_client_credit (companyid, subcompanyid, projectid, subcategoryid, subcategoryname, providedcode, purposename, source, addedby
        ) VALUES (
            '{$data['companyid']}', '{$data['subcompanyid']}', '{$data['projectid']}', '{$data['sub_categoryid_client']}', '{$data['sub_category_client']}',
            '{$data['provided_code']}', '{$data['purpose']}', '{$data['source']}', '{$data['addedby']}'
        )";

        if ($conn->query($insert)) {
            $rrsst = array('status'=>'Success', 'message'=>'Purpose ('.$data['purpose'].') Inserted successfully', 'doaction'=>'inserted');
        } else {
        	$rrsst = array('status'=>'Error', 'message'=>'Insert Failed: ' . $conn->error);
        }
    }
     return $rrsst;
}

// function saveOrUpdateBillLocation($conn, $companyid, $subcompanyid, $projectid, $locationid, $departmentname, $locationname, $loginid) {
//     $tedit = date('Y-m-d H:i:s');

//     // 1. Check for duplicate department and location
//     $checkDup = $conn->prepare("SELECT sno FROM strs_idntlocation_master WHERE (tedit is null or tedit='') and departmentname = ? AND locationname = ?");
//     $checkDup->bind_param("ss", $departmentname, $locationname);
//     $checkDup->execute();
//     $checkDup->store_result();

//     if ($checkDup->num_rows > 0) {
//         return ['status' => 'error', 'message' => 'Department and Location already exist.'];
//         die();
//     }

//     // 2. Check if record with given `id` exists
//     $checkId = $conn->prepare("SELECT sno FROM strs_idntlocation_master WHERE (tedit is null or tedit='') and id = ?");
//     $checkId->bind_param("i", $locationid);
//     $checkId->execute();
//     $result = $checkId->get_result();

//     if ($result->num_rows > 0) {
//         // Update
//         $stmt = $conn->prepare("UPDATE strs_idntlocation_master SET departmentname = ?, locationname = ?, loginid = ? WHERE (tedit is null or tedit='') and id = ?");
//         $stmt->bind_param("sssi", $departmentname, $locationname, $loginid, $locationid);
//         $stmt->execute();
//         return ['status' => 'success', 'message' => 'Location updated successfully.'];

//     } else {
//         // Insert
//         $stmt = $conn->prepare("INSERT INTO strs_idntlocation_master (companyid, subcompanyid, projectid, id, departmentname, locationname, loginid) VALUES (?, ?, ?, ?, ?, ?, ?)");
//         $stmt->bind_param("iiiisss", $companyid, $subcompanyid, $projectid, $locationid, $departmentname, $locationname, $loginid);
//         $stmt->execute();
//         return ['status' => 'success', 'message' => 'New location inserted successfully.'];
//     }
// }

function saveOrUpdateBillLocation($conn, $companyid, $subcompanyid, $projectid, $locationid, $departmentname, $locationname, $loginid) {
    $tedit = date('Y-m-d H:i:s');

    // 1. Check for duplicate department and location
    $checkDup = $conn->prepare("SELECT sno FROM strs_idntlocation_master WHERE (tedit IS NULL OR tedit = '') AND departmentname = ? AND locationname = ?");
    $checkDup->bind_param("ss", $departmentname, $locationname);
    $checkDup->execute();
    $checkDup->store_result();

    if ($checkDup->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Department and Location already exist.'];
    }

    // If locationid is empty, generate a new one
    if ($locationid=="" || $locationid=="null" || $locationid==null || $locationid=="undefined") {
        $result = $conn->query("SELECT MAX(id) AS maxid FROM strs_idntlocation_master");
        $row = $result->fetch_assoc();
        $locationid = $row['maxid'] ? $row['maxid'] + 1 : 1;
    }

    // 2. Check if record with given `id` exists
    $checkId = $conn->prepare("SELECT sno FROM strs_idntlocation_master WHERE (tedit IS NULL OR tedit = '') AND id = ?");
    $checkId->bind_param("i", $locationid);
    $checkId->execute();
    $result = $checkId->get_result();

    if ($result->num_rows > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE strs_idntlocation_master SET departmentname = ?, locationname = ?, loginid = ? WHERE (tedit IS NULL OR tedit = '') AND id = ?");
        $stmt->bind_param("sssi", $departmentname, $locationname, $loginid, $locationid);
        $stmt->execute();
        return ['status' => 'success', 'message' => 'Location updated successfully.'];

    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO strs_idntlocation_master (companyid, subcompanyid, projectid, id, departmentname, locationname, loginid) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiisss", $companyid, $subcompanyid, $projectid, $locationid, $departmentname, $locationname, $loginid);
        $stmt->execute();
        return ['status' => 'success', 'message' => 'New location inserted successfully.'];
    }
}


function saveOrUpdateBillType($conn, $companyid, $subcompanyid, $projectid, $billtypeid, $billtypename, $billtypedescription, $loginid) {
    // 1. Check for duplicate department and location
    $checkDup = $conn->prepare("SELECT sno FROM strs_billtypemaster_universal WHERE (tedit is null or tedit='') and typename = ? AND description = ?");
    $checkDup->bind_param("ss", $billtypename, $billtypedescription);
    $checkDup->execute();
    $checkDup->store_result();

    if ($checkDup->num_rows > 0) {
        return ['status' => 'error', 'message' => 'Bill type already exist.'];
        die();
    }

    // If locationid is empty, generate a new one
    if ($billtypeid=="" || $billtypeid=="null" || $billtypeid==null || $billtypeid=="undefined") {
        $result = $conn->query("SELECT MAX(id) AS maxid FROM strs_billtypemaster_universal");
        $row = $result->fetch_assoc();
        $billtypeid = $row['maxid'] ? $row['maxid'] + 1 : 1;
    }

    // 2. Check if record with given `id` exists
    $checkId = $conn->prepare("SELECT sno FROM strs_billtypemaster_universal WHERE (tedit is null or tedit='') and id = ?");
    $checkId->bind_param("i", $billtypeid);
    $checkId->execute();
    $result = $checkId->get_result();

    if ($result->num_rows > 0) {
        // Update
        $stmt = $conn->prepare("UPDATE strs_billtypemaster_universal SET typename = ?, description = ?, loginid = ? WHERE (tedit is null or tedit='') and id = ?");
        $stmt->bind_param("sssi", $billtypename, $billtypedescription, $loginid, $billtypeid);
        $stmt->execute();
        return ['status' => 'success', 'message' => 'Bill type updated successfully.'];

    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO strs_billtypemaster_universal (id, typename, description, loginid) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $billtypeid, $billtypename, $billtypedescription, $loginid);
        $stmt->execute();
        return ['status' => 'success', 'message' => 'New Bill type inserted successfully.'];
    }
}

function saveBankChargesKey($conn, $data) {
    $keyid         = $data['keyid'] ?: 'KEY_' . uniqid();
    $key1          = $data['key1'];
    $companyid     = $data['companyid'];
    $subcompanyid  = $data['subcompanyid'];
    $projectid     = $data['projectid'];
    $accountid     = intval($data['accountid']);
    $bankdesc      = $data['bankdescription'];
    $loginname     = $data['loginname'];

    // Check if record exists for this key and account
    $checkQuery = "SELECT sno FROM tbl_bankdescription_bankchargeskey 
                   WHERE keyid = ? AND companyid = ? AND subcompanyid = ? AND projectid = ? AND accountid = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ssssi", $keyid, $companyid, $subcompanyid, $projectid, $accountid);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        // Update existing record
        $updateQuery = "UPDATE tbl_bankdescription_bankchargeskey 
                        SET bankdescription = ?, key1 = ?, loginname = ?, edatetime = NOW()
                        WHERE keyid = ? AND companyid = ? AND subcompanyid = ? AND projectid = ? AND accountid = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssssi", $bankdesc, $key1, $loginname, $keyid, $companyid, $subcompanyid, $projectid, $accountid);
        if ($stmt->execute()) {
            return "updated";
        }
    } else {
        $stmt->close();
        // Insert new record
        $insertQuery = "INSERT INTO tbl_bankdescription_bankchargeskey 
                        (companyid, subcompanyid, projectid, accountid, bankdescription, keyid, key1, loginname) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("sssissss", $companyid, $subcompanyid, $projectid, $accountid, $bankdesc, $keyid, $key1, $loginname);
        if ($stmt->execute()) {
            return "inserted";
        }
    }

    return "failed";
}

function addPurposeToMaster($conn, $companyid, $subcompanyid, $projectid, $categoryid, $subcategoryid, $purposename, $loginname, $purposetype) {
    $rrp = [];

    // Basic Validation
    if (!$companyid || !$subcompanyid || !$projectid || !$purposename || !$categoryid) {
        return ['status' => 'Error', 'message' => 'Missing required input fields.'];
    }

    $purposetype = strtolower($purposetype ?? '');

    // Set table names
    $pcategorymaster = in_array($purposetype, ['credit', 'deposit'])
        ? 'purpose_category_master_credit'
        : 'purpose_category_master';

    $pcodemaster = in_array($purposetype, ['credit', 'deposit'])
        ? 'purpose_code_master_credit'
        : 'purpose_code_master';

    // Check duplicate purpose
    $stmt_check = $conn->prepare("SELECT COUNT(*) AS cnt, purpose, purposecode FROM $pcodemaster WHERE cmp_id = ? AND subcmp_id = ? AND projectid = ? AND purpose = ?");
	$stmt_check->bind_param("ssss", $companyid, $subcompanyid, $projectid, $purposename);
	$stmt_check->execute();
	$result_check = $stmt_check->get_result();
	$row_check = $result_check->fetch_assoc();

	if ($row_check['cnt'] > 0) {
	    return [
	        'status' => 'Duplicate',
	        'message' => 'Purpose name already exists.',
	        'purposecode' => $row_check['purposecode'],
	        'purposename' => $row_check['purpose']
	    ];
	}

    // Fetch Category Info
    $categoryData = in_array($purposetype, ['credit', 'deposit'])
        ? getpurposecategorymainmaster_credit($conn, "categoryid", $categoryid)
        : getpurposecategorymainmaster($conn, "categoryid", $categoryid);

    if (empty($categoryData)) {
        return ['status' => 'Error', 'message' => 'Purpose category not found.'];
    }

    $purposecategory = $categoryData[0]['category'];
    $purposecategoryid = $categoryData[0]['categoryid'];

    // Subcategory Handling
    $newsubcat = 0;
    if ($subcategoryid) {
        $subcatData = in_array($purposetype, ['credit', 'deposit'])
            ? getMainSubcategoryMaster_Credit($conn, "subcategoryid", $subcategoryid)
            : getMainSubcategoryMaster($conn, "subcategoryid", $subcategoryid);

        if (!empty($subcatData)) {
            $purposesubcategory = $subcatData[0]['subcategory'];
        } else {
            // Create new subcategory code
            $maxSubCatFunc = in_array($purposetype, ['credit', 'deposit']) 
                ? 'credit_maxsubcategorycode_frm' 
                : 'maxsubcategorycode_frm';

            $maxsubcatid = $maxSubCatFunc($conn, $companyid, $subcompanyid, $projectid);
            if ($maxsubcatid >= 0) {
                $newsubcat = 1;
                $subcategoryid = $maxsubcatid + 1;
                $purposesubcategory = $subcategoryid . '-' . $subcategoryid;
            } else {
                return ['status' => 'Error', 'message' => 'Failed to generate new subcategory.'];
            }
        }
    } else {
        return ['status' => 'Error', 'message' => 'Subcategory ID is required.'];
    }

    // Generate purpose ID and code
    $purposeid = getpurposeid($conn, $companyid, $subcompanyid, $projectid);

    $maxProvidedCodeFunction = in_array($purposetype, ['credit', 'deposit']) 
        ? 'credit_maxprovidecode_frm' 
        : 'maxprovidecode_frm';

    $mxprcode = $maxProvidedCodeFunction($conn, $companyid, $subcompanyid, $projectid, $subcategoryid);

    $newmaxprovidedcode = ($mxprcode) 
        ? $subcategoryid . '.' . addZeroBeforeTen(explode(".", $mxprcode)[1] + 1) 
        : $subcategoryid . '.01';

    $purposecode = createPurposeCodeEncoded(
        $companyid, $subcompanyid, $projectid,
        $purposecategoryid, $subcategoryid, $purposeid, $newmaxprovidedcode
    );

    $finalpurposecode = $purposecode[0]['combinedCode'];
    $markbynote4 = 'temp';
    $source = 'manual by ' . $loginname;

    // Insert into master table
    $stmt_insert = $conn->prepare("INSERT INTO $pcodemaster (cmp_id, subcmp_id, projectid, categoryid, category, sub_categoryid, sub_category, purpose, purposecode, provided_code, purposeid, markbynote4, source) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_insert->bind_param("sssssssssssss", 
        $companyid, $subcompanyid, $projectid,
        $purposecategoryid, $purposecategory,
        $subcategoryid, $purposesubcategory,
        $purposename, $finalpurposecode,
        $newmaxprovidedcode, $purposeid,
        $markbynote4, $source
    );

    if ($stmt_insert->execute()) {
        return [
            'status' => 'Success',
            'message' => 'Purpose saved successfully.',
            'purposecode' => $finalpurposecode,
            'purposename' => $purposename
        ];
    } else {
        return ['status' => 'Error', 'message' => 'Failed to insert purpose.'];
    }
}

function insertPurposeCodeKeysHistory($conn, $companyid, $subcompanyid, $projectid, $categoryid, $subcategoryid, $purposecode, $purposename, $keytext, $transactiontype) {
    // Step 1: Check for duplicate keytext
    $checkQuery = "
        SELECT sno FROM purpose_code_keyshistory 
        WHERE LOWER(TRIM(keytext)) = LOWER(TRIM(?))
          AND companyid = ? AND subcompanyid = ? AND projectid = ?
        LIMIT 1
    ";

    if ($checkStmt = $conn->prepare($checkQuery)) {
        $checkStmt->bind_param("ssss", $keytext, $companyid, $subcompanyid, $projectid);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $checkStmt->close();
            return [
                'status' => false,
                'message' => 'Duplicate entry found. This keytext already exists and was not inserted.'
            ];
        }
        $checkStmt->close();
    } else {
        return [
            'status' => false,
            'message' => 'Failed to prepare duplicate check query: ' . $conn->error
        ];
    }

    // Step 2: Proceed with insert
    $insertQuery = "
        INSERT INTO purpose_code_keyshistory (
            companyid, subcompanyid, projectid,
            categoryid, subcategoryid, purposecode,
            purposename, keytext, transactiontype
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    if ($stmt = $conn->prepare($insertQuery)) {
        $stmt->bind_param(
            "sssddssss",
            $companyid,
            $subcompanyid,
            $projectid,
            $categoryid,
            $subcategoryid,
            $purposecode,
            $purposename,
            $keytext,
            $transactiontype
        );

        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();
            return [
                'status' => true,
                'message' => 'Data inserted successfully.',
                'insert_id' => $insertId
            ];
        } else {
            return [
                'status' => false,
                'message' => 'Insert failed: ' . $stmt->error
            ];
        }
    } else {
        return [
            'status' => false,
            'message' => 'Failed to prepare insert query: ' . $conn->error
        ];
    }
}

function copyTransaction($conn, $dbsno, $newTransferedAmount, $newTransactionDate, $TransactionNo, $loginname,$payfromaccountid,$transferreceiptpath=null) {
    $status = ["success" => false, "message" => "", "newsno" => null];

    $frmaccount=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$payfromaccountid);
	$payfrmbnk="";
	$payfromaccountno="";
	$payfrombankname="";
	$payfromifsccode="";
	if(count($frmaccount)>0){
		foreach ($frmaccount as $acctkey) {
			$payfromaccountid=$acctkey['accountid'];
			$payfromaccountno=$acctkey['accountno'];
			$payfrombankname=$acctkey['bankname'];
			$payfromifsccode=$acctkey['ifsccode'];
		}
	}

    // Start transaction
    $conn->begin_transaction();
    try {
        // 1. Fetch maintransaction row
        $stmt = $conn->prepare("SELECT * FROM tbl_maintransaction WHERE sno = ?");
        $stmt->bind_param("i", $dbsno);
        $stmt->execute();
        $result = $stmt->get_result();
        $mainRow = $result->fetch_assoc();
        $stmt->close();

        if (!$mainRow) {
            throw new Exception("No transaction found with sno: " . $dbsno);
        }

        // Generate new tsno
        $tsno = GetMaxRefTempSno($conn);

        // 2. Generate new reference number
        $newReference = $mainRow['reference_no'] . "-COPY-" . time();

        // 3. Insert new maintransaction
        $insertQuery = "
            INSERT INTO tbl_maintransaction (
                tsno, countryid, companyid, subcompanyid, projectid, subprojectid, finyear,
                request_no, reference_no, approvedby, bookdate, book_amount, transactionno,
                paymentduedate, requesteddate, requestedby, transaction_date, transaction_type,
                transferedby, transfered_amount, beforeedit_amount, transferreceiptpath,
                is_itinaccount, payto, paytoid, payfrombankname, payfromaccountno, payfromifsccode,
                payfromaccountid, payfrombankid, paymenttype, paymenttypestatus, beneficierie,
                description, empid, paytoaccountno, paytobankname, paytoifsc, paytoaccountid,
                reconciliation, reconciliation_banksno, reconcilation_process, value_date,
                reconcilitionX, reverse_entry, refundable_projectid, is_purchaseforproduction,
                is_intermediate_transfer, is_merger, chalan_refno, groupkeyid, book_source,
                referenceno_clb, book_status, final_status, audit_status, bookdoneby,
                uploadflag, sourcecentreid, sourcecentresno, tedit, tedit_remark,
                taxableamount, gstamount, deposit_reconcilationbanksno
            )
            SELECT 
                ?, countryid, companyid, subcompanyid, projectid, subprojectid, finyear,
                request_no, ?, approvedby, bookdate, ?, ?,
                paymentduedate, requesteddate, requestedby, ?, transaction_type,
                ?, ?, beforeedit_amount, ?,
                is_itinaccount, payto, paytoid, ?, ?, ?,
                ?, payfrombankid, paymenttype, paymenttypestatus, beneficierie,
                description, empid, paytoaccountno, paytobankname, paytoifsc, paytoaccountid,
                reconciliation, reconciliation_banksno, reconcilation_process, value_date,
                reconcilitionX, reverse_entry, refundable_projectid, is_purchaseforproduction,
                is_intermediate_transfer, is_merger, chalan_refno, groupkeyid, book_source,
                referenceno_clb, book_status, final_status, audit_status, bookdoneby, 
                uploadflag, sourcecentreid, sourcecentresno, tedit, tedit_remark,
                taxableamount, gstamount, deposit_reconcilationbanksno
            FROM tbl_maintransaction
            WHERE sno = ?
        ";

        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param(
            "isssssssssssi", 
            $tsno, 
            $newReference, 
            $newTransferedAmount,
            $TransactionNo,
            $newTransactionDate, 
            $loginname, 
            $newTransferedAmount, 
            $transferreceiptpath,
            $payfrombankname,
            $payfromaccountno,
            $payfromifsccode,
            $payfromaccountid,
            $dbsno
        );
        $stmt->execute();

        $newMainSno = $conn->insert_id; // New main transaction sno
        $stmt->close();

        if (!$newMainSno) {
            throw new Exception("Failed to insert new maintransaction.");
        }

        // 4. Copy breakdown records
        $insertBreakdown = "
            INSERT INTO tbl_maintransaction_breakdown (
                countryid, companyid, subcompanyid, projectid, subprojectid,
                request_no, reference_no, beneficiary_name, amount, tds_type,
                tds_percent, tds_amount, purpose_category, purpose_categoryid,
                purpose_subcategory, purpose_subcategoryid, purpose, purpose_code,
                provided_code, purpose2, purpose_code2, invoice_type, invoice_no,
                invoicedate, taxableamount, gstamount, pono, isforstock, invoiceduedate,
                description, description_bank, complet_purposcode, empid, documentation,
                documentation_path, audiofilepath, voiceinstid, srm_emailsno, srm_attsno,
                doctype, blobfile, blobfile_tblname, verifybook, verifypayto, verifypurpose,
                is_merger, sno_merger, u_reference_no, uploadflag, sourcecentreid,
                sourcecentresno, tedit, tedit_remark, stkentrydone
            )
            SELECT
                countryid, companyid, subcompanyid, projectid, subprojectid,
                request_no, ?, beneficiary_name, ?, tds_type,
                tds_percent, tds_amount, purpose_category, purpose_categoryid,
                purpose_subcategory, purpose_subcategoryid, purpose, purpose_code,
                provided_code, purpose2, purpose_code2, invoice_type, invoice_no,
                invoicedate, taxableamount, gstamount, pono, isforstock, invoiceduedate,
                description, description_bank, complet_purposcode, empid, documentation,
                documentation_path, audiofilepath, voiceinstid, srm_emailsno, srm_attsno,
                doctype, blobfile, blobfile_tblname, verifybook, verifypayto, verifypurpose,
                is_merger, sno_merger, u_reference_no, uploadflag, sourcecentreid,
                sourcecentresno, tedit, tedit_remark, stkentrydone
            FROM tbl_maintransaction_breakdown
            WHERE reference_no = ?
        ";

        $stmt = $conn->prepare($insertBreakdown);
        $stmt->bind_param("sss", $newReference, $newTransferedAmount, $mainRow['reference_no']);
        $stmt->execute();
        $stmt->close();

        // 5. Commit transaction
        $conn->commit();

        $status["success"] = true;
        $status["message"] = "Transaction copied successfully.";
        $status["newReferenceno"] = $newReference;
        $status["newsno"] = $newMainSno;

    } catch (Exception $e) {
        $conn->rollback();
        $status["message"] = "Error: " . $e->getMessage();
    }
    return $status;
}

function base64url_encode_em($data)
{
  $b64 = base64_encode($data);
  
  if ($b64 === false) {
    return false;
  }
  $url = strtr($b64, '+/', '-_');
  return rtrim($url, '=');
}

function base64url_decode_em($data, $strict = false)
{
  $b64 = strtr($data, '-_', '+/');
  return base64_decode($b64, $strict);
}
?>