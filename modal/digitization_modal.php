<?php
date_default_timezone_set("Asia/Calcutta");
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
require('../sendemail.php');
$action = $_GET['action'];
if($action=="expensesheetdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$ppdt=getexpensesdataforhelpmakebook($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($ppdt);
}else if($action=="depositsheetdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	//$ppdt=getdepositdataforhelpmakebook($conn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	$ppdt=getdepositdataforhelpmakebook_missingdetails($conn,$companyid,$subcompanyid,$projectid); 

	echo json_encode($ppdt);
}else if($action=="customisedcomparison1"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue']; 
	$ppdt=getcustomisedcomparison1($conn,$companyid,$subcompanyid,$projectid); 

	echo json_encode($ppdt);
}else if($action=="companyreplydetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$compreply = getcompanyreplybydescription($conn,$companyid,$subcompanyid,$projectid,$accountid);
	echo json_encode($compreply);
}else if($action=="bookdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$bookdetails = getbookdetails($conn,$companyid,$subcompanyid,$projectid,$accountid);
	echo json_encode($bookdetails);
}else if($action=="bankentriesasunknown"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$response = reconstructbookwith_unknown($conn,$companyid,$subcompanyid,$projectid,$accountid,'withdrawn');
	echo json_encode($response);
}else if($action=="T3details"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$T3details = getT3details($conn,$companyid,$subcompanyid,$projectid);
	echo json_encode($T3details);
}else if($action=="unreconciledclosetobookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype=$_POST['transactiontype'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$cldata=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$shortby,$shortbyvalue);
	echo json_encode($cldata);
}else if($action=="unreconstruct_closetobookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype=$_POST['transactiontype'];
	$shortby = $_POST['shortby'];
	$shortbyvalue = $_POST['shortbyvalue'];
	$cldata=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$shortby,$shortbyvalue);
	echo json_encode($cldata);
}else if($action=="UnreconstructwithAccountid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid=$_POST['accountid'];
	$transactiontype=$_POST['transactiontype'];
	$cldata=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,"unreconstruct_withaccountid",$accountid);
	echo json_encode($cldata);
}else if ($action=="unreconciledclosetobookdatasummary") {
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $transactiontype = $_POST['transactiontype'];
    $resultlist=array();  
     $qryu="select count(m.sno) as countentry,m.account_id FROM tbl_closetobook m  where m.company_id='".$companyid."' and m.subcompany_id='".$subcompanyid."' and m.project_id='".$projectid."' and (m.tedit is null or m.tedit='') and (lcase(m.book_status)='unknown' or ifnull(m.book_status,'')='') and m.amounttype='".$transactiontype."'  group by m.account_id";
    $resulp=$conn->query($qryu);
    if ($resulp->num_rows>0) {
    	while ($improw=$resulp->fetch_assoc()) {
    		$bnkinf=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$improw['account_id']);
			$accountname="";
			$accountno="";
			$ifsccode="";
			$bankname="";
			$accountholdername="";
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
    		$resultlist[] = array('accountname'=>$accountname, 'accountnumber'=>$accountno, 'bankname'=>$bankname, 'accountid'=>$improw['account_id'], 'accounttype'=>$accounttype, 'unknownenteries'=>$improw['countentry']);
    	}
	}
	echo json_encode($resultlist);
}else if($action=="companycommunicationhistory"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $description = $_POST['description'];
    $transactiontype = $_POST['transactiontype'];
    $resultlist=array();
    $qryu="SELECT groupdescription,date,companyreply,voiceremarkfile,source FROM tblcompanyreplyunknown where account_id='".$accountid."' and ucase(groupdescription)='".addslashes(strtoupper($description))."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and  ifnull(transactiontype,'') ='".$transactiontype."'";
    $resulp=$conn->query($qryu);
    if ($resulp->num_rows>0) {
    	while ($improw=$resulp->fetch_assoc()) {
    		$bnkinf=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
			$accountname="";
			$accountno="";
			$ifsccode="";
			$bankname="";
			$accountholdername="";
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
    		$resultlist[] = array('accountname'=>$accountname, 'accountnumber'=>$accountno, 'bankname'=>$bankname, 'accountid'=>$improw['account_id'], 'accounttype'=>$accounttype, 'description'=>$improw['groupdescription'], 'date'=>$improw['date'],'companyreply'=>$improw['companyreply'],'voiceremarkfile'=>$improw['voiceremarkfile'],'source'=>$improw['source']);
    	}
	}
	echo json_encode($resultlist);
}else if($action=="checkreconstructionhistory"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $description = $_POST['description'];
    $resultlist=array();
    $qryu="SELECT h.description,h.purposecode,p.purpose,p.provided_code,h.paytoid ,r.paytoname,h.subprojectid,h.averageamount,h.totalentries,h.minamt,h.maxamt,h.autoassign FROM tbreconstructedhistory h left join purpose_code_master p on h.purposecode=p.purposecode left join tbl_payto_master r on r.paytoid=h.paytoid where h.accountid='".$accountid."' and trim(ucase(h.description))='".addslashes(trim(strtoupper($description)))."' and h.companyid='".$companyid."' and h.subcompanyid='".$subcompanyid."' and h.projectid='".$projectid."'";
    $resulp=$conn->query($qryu);
    if ($resulp->num_rows>0) {
    	while ($improw=$resulp->fetch_assoc()) {
    		$bnkinf=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
			$accountname="";
			$accountno="";
			$ifsccode="";
			$bankname="";
			$accountholdername="";
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
    		$resultlist[] = array('accountname'=>$accountname, 'accountnumber'=>$accountno, 'bankname'=>$bankname, 'accountid'=>$accountid, 'accounttype'=>$accounttype, 'description'=>$improw['description'], 'purposecode'=>$improw['purposecode'],'purpose'=>$improw['purpose'],'provided_code'=>$improw['provided_code'],'paytoid'=>$improw['paytoid'],'paytoname'=>$improw['paytoname'],'subprojectid'=>$improw['subprojectid'],'averageamount'=>$improw['averageamount'],'totalentries'=>$improw['totalentries'],'minamt'=>$improw['minamt'],'maxamt'=>$improw['maxamt'],'autoassign'=>$improw["autoassign"]);
    	}
	}
	echo json_encode($resultlist);
}else if($action=="checkreconstructionhistory_deposit"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $description = $_POST['description'];
    $resultlist=array(); 

    /*echo $qryu="select p.purpose,p.provided_code,c.purposecode, c.payerid as paytoid ,c.paidonbehalfid, c.clienttype, c.description, c.accountid  ,c.subprojectid,c.averageamount, c.totalentries,c.minamt,c.maxamt,c.autoassign ,
            CASE 
                WHEN c.clienttype = 'Intermediate'   THEN im.client_name
                    ELSE ic.client_name
                END AS actualpayee,    
            CASE 
                WHEN c.clienttype = 'Intermediate'   THEN Deposit_c.client_name
                ELSE Deposit_c.client_name
                END AS depositpayee  from
            tbreconstructedhistory_deposit c  left join purpose_code_master_credit p on c.purposecode=p.purposecode  LEFT JOIN  intermediatemaster im ON im.client_id = c.payerid   LEFT JOIN    client_master ic ON ic.client_id = c.payerid
            LEFT JOIN  intermediatemaster dep_i ON dep_i.client_id = c.paidonbehalfid
            LEFT JOIN  client_master Deposit_c ON Deposit_c.client_id = c.paidonbehalfid where c.accountid='".$accountid."' and trim(ucase(c.description)) like '%".addslashes(trim(strtoupper($description)))."%' and c.companyid='".$companyid."' and c.subcompanyid='".$subcompanyid."' and c.projectid='".$projectid."'";*/

              $qryu="select p.purpose,p.provided_code,c.purposecode, c.payerid as paytoid ,c.paidonbehalfid, c.clienttype, c.description, c.accountid  ,c.subprojectid,c.averageamount, c.totalentries,c.minamt,c.maxamt,c.autoassign ,
            CASE 
                WHEN c.clienttype = 'Intermediate'   THEN im.client_name
                    ELSE ic.client_name
                END AS actualpayee,    
            CASE 
                WHEN c.clienttype = 'Intermediate'   THEN Deposit_c.client_name
                ELSE Deposit_c.client_name
                END AS depositpayee  from
            tbreconstructedhistory_deposit c  left join purpose_code_master_credit p on c.purposecode=p.purposecode  LEFT JOIN  intermediatemaster im ON im.client_id = c.payerid   LEFT JOIN    client_master ic ON ic.client_id = c.payerid
            LEFT JOIN  intermediatemaster dep_i ON dep_i.client_id = c.paidonbehalfid
            LEFT JOIN  client_master Deposit_c ON Deposit_c.client_id = c.paidonbehalfid where c.accountid='".$accountid."'    and '".$description ."' like  concat('%',trim(ucase(c.description)),'%')  and c.companyid='".$companyid."' and c.subcompanyid='".$subcompanyid."' and c.projectid='".$projectid."'";

    $resulp=$conn->query($qryu);
    if ($resulp->num_rows>0) {
    	while ($improw=$resulp->fetch_assoc()) {
    		$bnkinf=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
			$accountname="";
			$accountno="";
			$ifsccode="";
			$bankname="";
			$accountholdername="";
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
    		$resultlist[] = array('accountname'=>$accountname, 'accountnumber'=>$accountno, 'bankname'=>$bankname, 'accountid'=>$accountid, 'accounttype'=>$accounttype, 'description'=>$improw['description'], 'purposecode'=>$improw['purposecode'],'purpose'=>$improw['purpose'],'provided_code'=>$improw['provided_code'],'paytoid'=>$improw['paytoid'],'paytoname'=>$improw['depositpayee'],'subprojectid'=>$improw['subprojectid'],'averageamount'=>$improw['averageamount'],'totalentries'=>$improw['totalentries'],'minamt'=>$improw['minamt'],'maxamt'=>$improw['maxamt'],'autoassign'=>$improw["autoassign"],'paidonbehalf'=>$improw["actualpayee"],'paidonbehalfid'=>$improw["paidonbehalfid"],'clienttype'=>$improw["clienttype"]);
    	}
	}
	echo json_encode($resultlist);
}else if ($action=="unreconciledclosetobookdatacompany") {
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $transactiontype = $_POST['transactiontype'];
    $resultlist=array();
    if($transactiontype=="all"){
       $qryu="select amounttype as transactiontype,description_bank,account_id,concat('[',group_concat(json_object('sno',sno,'amount',total_amount,'date',date,'chequeno',chequeno,'transactiontype',amounttype)),']') as details,avg(total_amount) as averageamount,min(total_amount) as minamt, max(total_amount) as maxamt,count(sno) as totalentries,userquery,userqueryfile FROM tbl_closetobook where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and (tedit is null or tedit='') and (lcase(book_status)='unknown' or ifnull(book_status,'')='')   and sent_status='company' group by description_bank order by description_bank";

    } else{
     $qryu="select amounttype as transactiontype,description_bank,account_id,concat('[',group_concat(json_object('sno',sno,'amount',total_amount,'date',date,'chequeno',chequeno,'transactiontype',amounttype)),']') as details,avg(total_amount) as averageamount,min(total_amount) as minamt, max(total_amount) as maxamt,count(sno) as totalentries,userquery,userqueryfile FROM tbl_closetobook where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and (tedit is null or tedit='') and (lcase(book_status)='unknown' or ifnull(book_status,'')='') and amounttype='".$transactiontype."' and sent_status='company' group by description_bank order by description_bank";
    	
    } 
    // echo  $qryu;

    $resulp=$conn->query($qryu);
    if ($resulp->num_rows>0) {
    	while ($improw=$resulp->fetch_assoc()) {
    		$bnkinf=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$improw['account_id']);
			$accountname="";
			$accountno="";
			$ifsccode="";
			$bankname="";
			$accountholdername="";
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
    		$resultlist[] = array('accountname'=>$accountname, 'accountnumber'=>$accountno, 'bankname'=>$bankname, 'accountid'=>$improw['account_id'], 'accounttype'=>$accounttype, 'description'=>$improw['description_bank'], 'details'=>$improw['details'],'averageamount'=>$improw['averageamount'],'minamount'=>$improw['minamt'],'maxamount'=>$improw['maxamt'],'totalentries'=>$improw['totalentries'],'userquery'=>$improw['userquery'],'userqueryfile'=>$improw['userqueryfile'],'transactiontype'=>$improw['transactiontype']);
    	}
	}
	echo json_encode($resultlist);
}else if($action=="savecompanyreply"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid = $_POST["accountid"];
	$companyreply = $_POST["companyreply"];
	$description = $_POST["description"];
	$otherdetails = $_POST["otherdetails"];
	$transactiontype = $_POST["transactiontype"];
	$purpose = $_POST["purpose"];
	$personname = $_POST["personname"];
	$voicereceiptpath = "";
	if (isset($_FILES['audioFile']) && !$_FILES['audioFile']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/companyreply/'.date('Ymd').'/';
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($receiptpathDir)) {
            mkdir($receiptpathDir, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["audioFile"]["name"]);
		$receiptname = round(microtime(true)) . '.' . end($temp);
        $uploadFile = $receiptpathDir . $receiptname;
        $voicereceiptpath = str_replace("../", "", $uploadFile);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['audioFile']['tmp_name'], $uploadFile);
    }
    $unreassprpmsg = array();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    $l=0;  
    try {
		$conn->query("insert into tblcompanyreplyunknown(companyid,subcompanyid,projectid,account_id,otherdetails,companyreply,voiceremarkfile,groupdescription,source,transactiontype,purpose, personname) values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$otherdetails."','".addslashes($companyreply)."','".$voicereceiptpath."','".addslashes($description)."','company','".$transactiontype."','".addslashes($purpose)."','".addslashes($personname)."')");
		$details = json_decode($otherdetails,true);
		
		foreach($details as $detail){
			$transactiontype=trim(strtolower($detail["transactiontype"]));
			if($transactiontype!="deposit"){
				$conn->query("insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,account_id,amount,total_amount,description,remarks,audiofilepath,date,chequeno,source)values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$detail["amount"]."','".$detail["amount"]."','".$description."','".$companyreply."','".$voicereceiptpath."','".$detail["date"]."','".$detail["chequeno"]."','company')");			

			}
			$conn->query("update tbl_closetobook set sent_status='replied' where sno='".$detail["sno"]."'");
		}
		$conn->commit();
	    $unreassprpmsg = array('status' => 'Success', 'message' => 'Response Saved  Successfully');
	} catch (Exception $e) {
        $conn->rollback();
        $unreassprpmsg = array('status' => 'Error', 'message' => $e->getMessage()." Level:".$l);
    }
    echo json_encode($unreassprpmsg);

}else if($action=="check_automaticeupdate_purpose_deposit"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$loginname = $_POST['loginname'] ?? null;
	$expensedatadbsnoinfo = isset($_POST['expensedatadbsno']) ? json_decode($_POST['expensedatadbsno'], true) : [];
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	try {

		foreach ($expensedatadbsnoinfo as $asspkey) {
			 $dbsno = $asspkey['dbsno'] ?? null;
			 $purpose = trim($asspkey['purpose']);
			$prpin = getcreditpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "provided_purposename", $purpose); 
			$category="";
			$categoryid="";
			$subcategory="";
			$subcategoryid="";
			$purposeid="";
			$purposecode="";
			$newpurpose="";
			$providedcode="";
			 if (count($prpin)==1 && $purpose && $dbsno) {
			 	foreach ($prpin as $ppkkey) {
					$category =$ppkkey['pcategory'];
					$categoryid=$ppkkey['pcategorycode'];
					$subcategory=$ppkkey['psub_category'];
					$subcategoryid=$ppkkey['psub_categoryid'];
					$purposeid=$ppkkey['purposeid'];
					$newpurpose=$ppkkey['purpose'];
					$purposecode=$ppkkey['purposecode'];
					$providedcode=$ppkkey['provided_code'];
	
				 	$conn->query("update tbl_depositsinexcelsheet set category='".$category."',categoryid='".$categoryid."', sub_categoryid='".$subcategoryid."' ,sub_category='".$subcategory."',purpose='".$purpose."',purpose_code='".$purposecode."' ,provided_code='".$providedcode."' where sno in (".$dbsno.")");
			 	}
			 }
		}
		$conn->commit();
	    $unreassprpmsg = array('status' => 'Success', 'message' => 'Response Saved  Successfully');
	
    	echo json_encode($unreassprpmsg);
	} catch (Exception $e) {
	    // Rollback transaction on error
	    $conn->rollback();
	    $assprpmsg = ['status' => 'Error', 'message' => $e->getMessage()];

    	echo json_encode($unreassprpmsg);
	}
}else if($action=="check_automaticeupdate_payee_deposit"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$loginname = $_POST['loginname'] ?? null;
	$expensedatadbsnoinfo = isset($_POST['expensedatadbsno']) ? json_decode($_POST['expensedatadbsno'], true) : [];
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	try {

		foreach ($expensedatadbsnoinfo as $asspkey) {
			 $dbsno = $asspkey['dbsno'] ?? null;
			 $payeename = trim($asspkey['payeename']); 
			$prpin =getclientdetils_info($conn,$companyid,$subcompanyid,$projectid,"chkclientname",$payeename);

 
			$clientid="";
			 if (count($prpin)==1 && $purpose && $dbsno) {
			 	foreach ($prpin as $ppkkey) {
					$clientid =$ppkkey['client_id']; 

			 	}
			 	$conn->query("update tbl_depositsinexcelsheet set paytoid='".$clientid."'  where sno in (".$dbsno.")");
			 }
		}
		$conn->commit();
	    $unreassprpmsg = array('status' => 'Success', 'message' => 'Response Saved  Successfully');
	
    	echo json_encode($unreassprpmsg);
	} catch (Exception $e) {
	    // Rollback transaction on error
	    $conn->rollback();
	    $assprpmsg = ['status' => 'Error', 'message' => $e->getMessage()];

    	echo json_encode($unreassprpmsg);
	}
} else if($action=="verifyprovidepurposename_addifmissing"){
	// Input data validation
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	$loginname = $_POST['loginname'] ?? null;
	$expensedatadbsnoinfo = isset($_POST['expensedatadbsno']) ? json_decode($_POST['expensedatadbsno'], true) : [];

	// print_r($expensedatadbsnoinfo);
	// die();
	// Validate input
	if (!$companyid || !$subcompanyid || !$projectid || count($expensedatadbsnoinfo)<1) {
	    echo json_encode(['status' => 'Error', 'message' => 'All required fields must be provided.']);
	    exit;
	}

	$assprpmsg = ['status' => 'Error', 'message' => 'An unknown error occurred.'];

	try {
	    // Start transaction
	    $conn->begin_transaction();
	    // Prepare the update query
	    $stmt = $conn->prepare("
	        UPDATE tbl_expencesinexcelsheet 
	        SET categoryid = ?, category = ?, sub_categoryid = ?, sub_category = ?, purpose_code = ?, purpose = ?, provided_code = ? 
	        WHERE sno = ?
	    ");
	    // print_r($expensedatadbsnoinfo);
	    foreach ($expensedatadbsnoinfo as $asspkey) {
	    	$dbsno = $asspkey['dbsno'] ?? null;
	        // Fetch expense data
	        $extd = getexpensesdataforhelpmakebook($conn, $companyid, $subcompanyid, $projectid, "sno", $dbsno);
	        if (count($extd)>0) {
	            foreach ($extd as $extdkey) {
	                if ($extdkey['provided_purpose']) {
	                    // Fetch purpose details
	                    $prpin = getpurposenamewithcat($conn, $companyid, $subcompanyid, $projectid, "provided_purposenamelikly", $extdkey['provided_purpose']);
	                    if (count($prpin)>0) {
	                        foreach ($prpin as $ppkkey) {
	                            // Bind parameters and execute update
	                            $stmt->bind_param(
	                                "ssssssss",
	                                $ppkkey['pcategorycode'],
	                                $ppkkey['pcategory'],
	                                $ppkkey['psub_categoryid'],
	                                $ppkkey['psub_category'],
	                                $ppkkey['purposecode'],
	                                $ppkkey['purpose'],
	                                $ppkkey['provided_code'],
	                                $dbsno
	                            );
	                            $stmt->execute();
	                        }
	                    }
	                }
	            }
	        }
	    }
	    // Commit transaction
	    $conn->commit();
	    // Update success message
	    $assprpmsg = ['status' => 'Success', 'message' => 'Purpose details updated successfully.'];
	} catch (Exception $e) {
	    // Rollback transaction on error
	    $conn->rollback();
	    $assprpmsg = ['status' => 'Error', 'message' => $e->getMessage()];
	} finally {
	    // Close the prepared statement if it exists
	    if (isset($stmt)) {
	        $stmt->close();
	    }
	}

	// Return response
	echo json_encode($assprpmsg);
}else if($action=="verifypaytotoexpensesheet_addifmissing"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	$aadd=0;
	$assprpmsg=array();
	// echo count($expensedatadbsnoinfo);
	// die();
	if(count($expensedatadbsnoinfo)>0){
		foreach ($expensedatadbsnoinfo as $asspkey) {
			$extd=getexpensesdataforhelpmakebook($conn,$companyid,$subcompanyid,$projectid,"sno",$asspkey['dbsno']);
			$paytoid ="";
			$paytoname ="";
			// print_r($extd);
			// die();
			if(count($extd)>0){
				foreach ($extd as $extdkey) {
					if($extdkey['payto']!=""){
						$prpin=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoname',$extdkey['payto']);
						if(count($prpin)>0){
							$aadd=1;
							foreach ($prpin as $ppkkey) {
								$paytoid = $ppkkey['paytoid'];
								$paytoname = $ppkkey['paytoname'];
								// Prepare the query
							    $stmt = $conn->prepare("UPDATE tbl_expencesinexcelsheet SET paytoid = ? WHERE sno = ?");		    
							    // Bind parameters
							    $stmt->bind_param("ss", $ppkkey['paytoid'], $asspkey['dbsno']);		    
							    // Execute the prepared statement
							    $stmt->execute();
							}
						}else{
							$sspy=savetodbnewpayto_details($conn,$extdkey['payto'],"","","","","","",$companyid,$subcompanyid,$projectid,"","",$loginname,"");
							if(count($sspy)>0){
								$aadd=1;
								foreach ($sspy as $sspykey) {
									if($sspykey['status']=="Success"){
										// Prepare the query
									    $stmt = $conn->prepare("UPDATE tbl_expencesinexcelsheet SET paytoid = ? WHERE sno = ?");		    
									    // Bind parameters
									    $stmt->bind_param("ss", $sspykey['paytoid'], $asspkey['dbsno']);		    
									    // Execute the prepared statement
									    $stmt->execute();
									}
								}
							}
						}
					}
					
				}
			}
		}
		// Commit transaction
		$conn->commit();
		$assprpmsg=array('status'=>'Success', 'message'=>'Payto details update successfully');		
	}
	echo json_encode($assprpmsg);
}else if($action=="verifypaytotoexpensesheet_addifmissing_credit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	if(count($expensedatadbsnoinfo)>0){
		$conn->begin_transaction();
		try {
			foreach ($expensedatadbsnoinfo as $asspkey) {
				// $extd=getexpensesdataforhelpmakebook($conn,$companyid,$subcompanyid,$projectid,"sno",$asspkey['dbsno']);
				$extd=getdepositdataforhelpmakebook($conn,$companyid,$subcompanyid,$projectid,"sno",$asspkey['dbsno']);
				$paytoid ="";
				$paytoname ="";
				if(count($extd)>0){
					foreach ($extd as $extdkey) {
						if(!$extdkey['paytoid']){
							// $prpin=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoname',$extdkey['payto']);
							$prpin=getclientdetails($conn,$companyid,$subcompanyid,$projectid,"clientname",$extdkey['payto']);
							$assprpmsg=array();
							if(count($prpin)>0){
								foreach ($prpin as $ppkkey) {
									$paytoid = $ppkkey['client_id'];
									$paytoname = $ppkkey['client_name'];
									// Prepare the query
								    $stmt = $conn->prepare("UPDATE tbl_depositsinexcelsheet SET paytoid = ? WHERE sno = ?");		    
								    // Bind parameters
								    $stmt->bind_param("ss", $ppkkey['client_id'], $asspkey['dbsno']);		    
								    // Execute the prepared statement
								    $stmt->execute();
								}
							}else{
								// $sspy=savetodbnewpayto_details($conn,$extdkey['payto'],"","","","","","",$companyid,$subcompanyid,$projectid,"","",$loginname,"");
								$sspy=savetodbnewclient_details($conn,$extdkey['payto'],"","","","","","","","","","",$companyid,$subcompanyid,$projectid);
								if(count($sspy)>0){
									foreach ($sspy as $sspykey) {
										if($sspykey['status']=="Success"){
											// Prepare the query
										    $stmt = $conn->prepare("UPDATE tbl_depositsinexcelsheet SET paytoid = ? WHERE sno = ?");		    
										    // Bind parameters
										    $stmt->bind_param("ss", $sspykey['paytoid'], $asspkey['dbsno']);		    
										    // Execute the prepared statement
										    $stmt->execute();
										}
									}
								}
							}
						}
						
					}
				}
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			// $stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Payto details update successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="verifypaytowithdepositdata_addifmissing"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	if(count($expensedatadbsnoinfo)>0){
		$conn->begin_transaction();
		try {
			foreach ($expensedatadbsnoinfo as $asspkey) {
				$extd=getexpensesdataforhelpmakebook($conn,$companyid,$subcompanyid,$projectid,"sno",$asspkey['dbsno']);
				$paytoid ="";
				$paytoname ="";
				if(count($extd)>0){
					foreach ($extd as $extdkey) {
						if(!$extdkey['paytoid']){
							$prpin=getpayerinfo($conn,$companyid,$subcompanyid,'payername',$extdkey['payto']);
							$assprpmsg=array();
							if(count($prpin)>0){
								foreach ($prpin as $ppkkey) {
									$paytoid = $ppkkey['payerid'];
									$paytoname = $ppkkey['payername'];
									// Prepare the query
								    $stmt = $conn->prepare("UPDATE tbl_depositsinexcelsheet SET paytoid = ? WHERE sno = ?");		    
								    // Bind parameters
								    $stmt->bind_param("ss", $ppkkey['payerid'], $asspkey['dbsno']);		    
								    // Execute the prepared statement
								    $stmt->execute();
								}
							}else{
								$sspy=savetodbnewpayto_details($conn,$extdkey['payto'],"","","","","","",$companyid,$subcompanyid,$projectid,"","",$loginname,"");
								if(count($sspy)>0){
									foreach ($sspy as $sspykey) {
										if($sspykey['status']=="Success"){
											// Prepare the query
										    $stmt = $conn->prepare("UPDATE tbl_depositsinexcelsheet SET paytoid = ? WHERE sno = ?");		    
										    // Bind parameters
										    $stmt->bind_param("ss", $sspykey['paytoid'], $asspkey['dbsno']);		    
										    // Execute the prepared statement
										    $stmt->execute();
										}
									}
								}
							}
						}
						
					}
				}
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Payto details update successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="assignpurposetodepositsheet"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecode = $_POST['purposecode'];
	$prpin=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	$purpose_category ="";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$purpose_category = $ppkkey['pcategory'];
			$purpose_categoryid = $ppkkey['pcategorycode'];
			$purpose_subcategory = $ppkkey['psub_category'];
			$purpose_subcategoryid = $ppkkey['psub_categoryid'];
			$purpose = $ppkkey['purpose'];
			$purpose_code = $ppkkey['purposecode'];
			$purpose_providedcode = $ppkkey['provided_code'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Purpose details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	if(count($expensedatadbsnoinfo)>0){
		$conn->begin_transaction();
		try {
			foreach ($expensedatadbsnoinfo as $asspkey) {
				// Prepare the query
			    $stmt = $conn->prepare("UPDATE tbl_depositsinexcelsheet SET categoryid = ?, category = ?, sub_categoryid = ?, sub_category = ?, purpose_code = ?, purpose = ? , provided_code = ? WHERE sno = ?");		    
			    // Bind parameters
			    $stmt->bind_param("issssssi", $purpose_categoryid, $purpose_category, $purpose_subcategoryid, $purpose_subcategory, $purpose_code, $purpose, $purpose_providedcode, $asspkey['dbsno']);		    
			    // Execute the prepared statement
			    $stmt->execute();
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Purpose details update successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="RemoveEntryFromexpesnsedata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	$teditremark="rm on date ".date('Y-m-d');
	if(count($expensedatadbsnoinfo)>0){
		$conn->begin_transaction();
		try {
			foreach ($expensedatadbsnoinfo as $asspkey) {
				if($asspkey['dbsno']!=""){
					// Prepare the query
				    $stmt = $conn->prepare("UPDATE tbl_expencesinexcelsheet SET tedit = ? WHERE sno = ?");		    
				    // Bind parameters
				    $stmt->bind_param("si", $teditremark, $asspkey['dbsno']);		    
				    // Execute the prepared statement
				    $stmt->execute();
				}
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Remove form expensesheet data successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
	
}else if($action=="assignpurposetoexpensesheet"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecode = $_POST['purposecode'];
	$prpin=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	$purpose_category ="";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	$purpose_providedcode ="";
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$purpose_category = $ppkkey['pcategory'];
			$purpose_categoryid = $ppkkey['pcategorycode'];
			$purpose_subcategory = $ppkkey['psub_category'];
			$purpose_subcategoryid = $ppkkey['psub_categoryid'];
			$purpose = $ppkkey['purpose'];
			$purpose_code = $ppkkey['purposecode'];
			$purpose_providedcode = $ppkkey['provided_code'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Purpose details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	if(count($expensedatadbsnoinfo)>0){
		$conn->begin_transaction();
		try {
			foreach ($expensedatadbsnoinfo as $asspkey) {
				if($asspkey['dbsno']!=""){
					// Prepare the query
				    $stmt = $conn->prepare("UPDATE tbl_expencesinexcelsheet SET categoryid = ?, category = ?, sub_categoryid = ?, sub_category = ?, purpose_code = ?, purpose = ?, provided_code =?, provided_purpose = ? WHERE sno = ?");		    
				    // Bind parameters
				    $stmt->bind_param("isssssssi", $purpose_categoryid, $purpose_category, $purpose_subcategoryid, $purpose_subcategory, $purpose_code, $purpose, $purpose_providedcode, $purpose, $asspkey['dbsno']);		    
				    // Execute the prepared statement
				    $stmt->execute();
				}
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Purpose details update successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
	
}else if($action=="assignpaytotoexpensesheet"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid = $_POST['paytoid'];
	$prpin=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoid',$paytoid);
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$paytoid = $ppkkey['paytoid'];
			$paytoname = $ppkkey['paytoname'];
			$tobankname = $ppkkey['tobankname'];
			$toaccountno = $ppkkey['toaccountno'];
			$tobankifsc = $ppkkey['tobankifsc'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Payto details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	if(count($expensedatadbsnoinfo)>0){
		$conn->begin_transaction();
		try {
			foreach ($expensedatadbsnoinfo as $asspkey) {
				// Prepare the query
			    $stmt = $conn->prepare("UPDATE tbl_expencesinexcelsheet SET paytoid = ?, payto = ? WHERE sno = ?");		    
			    // Bind parameters
			    $stmt->bind_param("sss", $paytoid, $paytoname, $asspkey['dbsno']);		    
			    // Execute the prepared statement
			    $stmt->execute();
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Payto details update successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="assignpaytotodepositsheet"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid = $_POST['paytoid'];
	$prpin=getpayerinfo($conn,$companyid,$subcompanyid,'payerid',$paytoid);
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$paytoname = $ppkkey['payername'];
			$tobankname = $ppkkey['tobankname'];
			$toaccountno = $ppkkey['toaccountno'];
			$tobankifsc = $ppkkey['tobankifsc'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Payto details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	if(count($expensedatadbsnoinfo)>0){
		$conn->begin_transaction();
		try {
			foreach ($expensedatadbsnoinfo as $asspkey) {
				// Prepare the query
			    $stmt = $conn->prepare("UPDATE tbl_depositsinexcelsheet SET paytoid = ?, payto = ? WHERE sno = ?");		    
			    // Bind parameters
			    $stmt->bind_param("sss", $paytoid, $paytoname, $asspkey['dbsno']);		    
			    // Execute the prepared statement
			    $stmt->execute();
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Payer details update successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="getUnreconciledFinancialYears"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$transactiontype = $_POST['transactiontype'];
	$ffn=getprojectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	$finstartmonth=4;
	if(count($ffn)>0){
		$finstartmonth = $ffn[0]['finyr'];
	}
	$accinfo=getUnreconciledFinancialYears($conn,$companyid,$subcompanyid,$projectid,$transactiontype,$finstartmonth);
	echo json_encode($accinfo);
}else if($action=="unreconciledaccountlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$datefrom = $_POST['datefrom'];
	$dateto = $_POST['dateto'];
	$transactiontype = $_POST['transactiontype'];
	$accinfo=getunrecounciledaccountlist($conn,$companyid,$subcompanyid,$projectid,$datefrom,$dateto,$transactiontype);
	echo json_encode($accinfo);
}else if($action=="unverifyedbookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$bbnkqq=$conn->query("SELECT b.sno as bsno, m.sno as msno, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code,b.provided_code, b.invoice_type, b.invoice_no, b.invoicedate, b.description, b.documentation, b.documentation_path, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid,m.reconciliation_banksno,c.sno as clsno,c.date as banktransactiondate,c.chequeno as banktransactionno,c.total_amount as banktransactionamount,ifnull(b.verifypurpose,'') as verifypurpose, ifnull(b.verifypayto,'') as verifypayto,c.description_bank as bankdescription FROM tbl_maintransaction_breakdown b join tbl_maintransaction m on b.reference_no=m.reference_no join tbl_closetobook c on c.sno=m.reconciliation_banksno  WHERE (b.tedit is null or b.tedit='') and  b.companyid='".$companyid."' and b.subcompanyid='".$subcompanyid."' and b.projectid='".$projectid."' and (ifnull(b.verifypurpose,'')='' or  ifnull(b.verifypayto,'')='')");
	$unvrydata=array();
	if($bbnkqq->num_rows>0){
		while($bbrow=$bbnkqq->fetch_assoc()){
			$subprj = subprojectdetails($conn, $companyid, $subcompanyid, $projectid, "subprojectid", $bbrow['subprojectid']);
			$subprojectname = "";
			if (is_array($subprj) && isset($subprj[0]['subprojectname'])) {
			    $subprojectname = $subprj[0]['subprojectname'];
			}

			$unvrydata[]=array('bsno'=>$bbrow['bsno'], 'msno'=>$bbrow['msno'], 'subprojectid'=>$bbrow['subprojectid'], 'subprojectname'=>$subprojectname, 'request_no'=>$bbrow['request_no'], 'reference_no'=>$bbrow['reference_no'], 'beneficiary_name'=>$bbrow['beneficiary_name'], 'amount'=>$bbrow['amount'], 'purpose_category'=>$bbrow['purpose_category'], 'purpose_categoryid'=>$bbrow['purpose_categoryid'], 'purpose_subcategory'=>$bbrow['purpose_subcategory'], 'purpose_subcategoryid'=>$bbrow['purpose_subcategoryid'], 'purpose'=>$bbrow['purpose'], 'purpose_code'=>$bbrow['purpose_code'], 'provided_code'=>$bbrow['provided_code'], 'invoice_type'=>$bbrow['invoice_type'], 'invoice_no'=>$bbrow['invoice_no'], 'invoicedate'=>$bbrow['invoicedate'], 'description'=>$bbrow['description'], 'payto'=>$bbrow['payto'],'finyear'=>$bbrow['finyear'],'bookdate'=>$bbrow['bookdate'],'paytoid'=>$bbrow['paytoid'],'payfromaccountid'=>$bbrow['payfromaccountid'],'reconciliation_banksno'=>$bbrow['reconciliation_banksno'],'clsno'=>$bbrow['clsno'], 'banktransactiondate'=>$bbrow['banktransactiondate'],'banktransactionno'=>$bbrow['banktransactionno'], 'banktransactionamount'=>$bbrow['banktransactionamount'],'bankdescription'=>$bbrow['bankdescription'],'verifypurpose'=>$bbrow['verifypurpose'],'verifypayto'=>$bbrow['verifypayto']);
		}
	}
	echo json_encode($unvrydata);
}else if($action=="verifiedpurpose_tobookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecode = $_POST['purposecode'];
	$loginname = $_POST['loginname'];
	$prpin=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	$purpose_category ="";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	$purpose_providedcode ="";
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$purpose_category = $ppkkey['pcategory'];
			$purpose_categoryid = $ppkkey['pcategorycode'];
			$purpose_subcategory = $ppkkey['psub_category'];
			$purpose_subcategoryid = $ppkkey['psub_categoryid'];
			$purpose = $ppkkey['purpose'];
			$purpose_code = $ppkkey['purposecode'];
			$purpose_providedcode = $ppkkey['provided_code'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Purpose details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	// brdbsno
	// maindbsno

	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	if(count($unverifyedbookdata)>0){
		$conn->begin_transaction();
		try {
			foreach ($unverifyedbookdata as $asspkey) {
				// Prepare the query
				$verifypurpose = "verified on date ".date('Y-m-d H:i:s')." by ".$loginname;
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET purpose_categoryid = ?, purpose_subcategory = ?, purpose_subcategoryid = ?, purpose_subcategory = ?, purpose_code = ?, purpose = ?, provided_code =?, verifypurpose=? WHERE sno = ?");		    
			    // Bind parameters
			    $stmt->bind_param("isssssssi",$purpose_categoryid,$purpose_category,$purpose_subcategoryid,$purpose_subcategory,$purpose_code,$purpose,$purpose_providedcode,$verifypurpose,$asspkey['brdbsno']);
			    // Execute the prepared statement
			    $stmt->execute();
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Update and verified purpose successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="verifiedpayto_tobookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid = $_POST['paytoid'];
	$loginname = $_POST['loginname'];
	$prpin=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoid',$paytoid);
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$paytoid = $ppkkey['paytoid'];
			$paytoname = $ppkkey['paytoname'];
			$tobankname = $ppkkey['tobankname'];
			$toaccountno = $ppkkey['toaccountno'];
			$tobankifsc = $ppkkey['tobankifsc'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Payto details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	if (count($unverifyedbookdata) > 0) {
	    $conn->begin_transaction();
	    try {
	        // Loop through each record in $unverifyedbookdata
	        foreach ($unverifyedbookdata as $asspkey) {
	            $verifypayto = "Verified on date " . date('Y-m-d H:i:s') . " by " . $loginname;

	            // Prepare the first query for tbl_maintransaction
	            $stmt = $conn->prepare("UPDATE tbl_maintransaction SET paytoid = ?, payto = ? WHERE sno = ?");
	            if (!$stmt) {
	                throw new Exception("Error preparing statement for tbl_maintransaction: " . $conn->error);
	            }
	            $stmt->bind_param("sss", $paytoid, $paytoname, $asspkey['maindbsno']);
	            $stmt->execute();
	            if ($stmt->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction: " . $stmt->error);
	            }

	            // Prepare the second query for tbl_maintransaction_breakdown
	            $stmt1 = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET verifypayto = ? WHERE sno = ?");
	            if (!$stmt1) {
	                throw new Exception("Error preparing statement for tbl_maintransaction_breakdown: " . $conn->error);
	            }
	            $stmt1->bind_param("si", $verifypayto, $asspkey['brdbsno']);
	            $stmt1->execute();
	            if ($stmt1->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction_breakdown: " . $stmt1->error);
	            }

	            // Close each statement after use
	            $stmt->close();
	            $stmt1->close();
	        }

	        // Commit the transaction
	        $conn->commit();

	        // Return success message
	        $assprpmsg = array('status' => 'Success', 'message' => 'Payto details updated and verified successfully');
	    } catch (Exception $e) {
	        // Rollback transaction in case of error
	        $conn->rollback();
	        $assprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
	    }
	}

	echo json_encode($assprpmsg);
}else if($action=="verifiedsubproject_tobookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$loginname = $_POST['loginname'];
	$assprpmsg=array();
	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	if (count($unverifyedbookdata) > 0) {
	    $conn->begin_transaction();
	    try {
	        // Loop through each record in $unverifyedbookdata
	        foreach ($unverifyedbookdata as $asspkey) {
	            // Prepare the first query for tbl_maintransaction
	            $stmt = $conn->prepare("UPDATE tbl_maintransaction SET subprojectid = ? WHERE sno = ?");
	            if (!$stmt) {
	                throw new Exception("Error preparing statement for tbl_maintransaction: " . $conn->error);
	            }
	            $stmt->bind_param("ss", $subprojectid, $asspkey['maindbsno']);
	            $stmt->execute();
	            if ($stmt->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction: " . $stmt->error);
	            }

	            // Prepare the second query for tbl_maintransaction_breakdown
	            $stmt1 = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET subprojectid = ? WHERE sno = ?");
	            if (!$stmt1) {
	                throw new Exception("Error preparing statement for tbl_maintransaction_breakdown: " . $conn->error);
	            }
	            $stmt1->bind_param("si", $subprojectid, $asspkey['brdbsno']);
	            $stmt1->execute();
	            if ($stmt1->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction_breakdown: " . $stmt1->error);
	            }

	            // Close each statement after use
	            $stmt->close();
	            $stmt1->close();
	        }

	        // Commit the transaction
	        $conn->commit();

	        // Return success message
	        $assprpmsg = array('status' => 'Success', 'message' => 'Sub-project details updated and verified successfully');
	    } catch (Exception $e) {
	        // Rollback transaction in case of error
	        $conn->rollback();
	        $assprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
	    }
	}

	echo json_encode($assprpmsg);
}else if($action=="verifiedpaytoandpurpose_tobookdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	$assprpmsg=array();
	if(count($unverifyedbookdata)>0){
		$conn->begin_transaction();
		try {
			foreach ($unverifyedbookdata as $asspkey) {
				// Prepare the query
				$verify = "verified on date ".date('Y-m-d H:i:s')." by ".$loginname;
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET verifypayto =?, verifypurpose=? WHERE sno = ?");		    
			    // Bind parameters
			    $stmt->bind_param("ssi",$verify,$verify,$asspkey['brdbsno']);
			    // Execute the prepared statement
			    $stmt->execute();
			}
			// Commit transaction
			$conn->commit();
			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Update and verified payto and purpose successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="unverifyedbookdata_credit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$bbnkqq=$conn->query("SELECT b.sno as bsno, m.sno as msno, b.subprojectid, b.request_no, b.reference_no, b.beneficiary_name, b.amount, b.purpose_category, b.purpose_categoryid, b.purpose_subcategory, b.purpose_subcategoryid, b.purpose, b.purpose_code,b.provided_code, b.invoice_type, b.invoice_no, b.invoicedate, b.description, b.documentation, b.documentation_path, m.payto,m.finyear,m.bookdate,m.paytoid,m.payfromaccountid,m.reconciliation_banksno,c.sno as clsno,c.date as banktransactiondate,c.chequeno as banktransactionno,c.total_amount as banktransactionamount,ifnull(b.verifypurpose,'') as verifypurpose, ifnull(b.verifypayto,'') as verifypayto,c.description_bank as bankdescription FROM tbl_maintransaction_credit_breakdown b join tbl_maintransaction_credit m on b.reference_no=m.reference_no join tbl_closetobook c on c.sno=m.reconciliation_banksno  WHERE (b.tedit is null or b.tedit='') and  b.companyid='".$companyid."' and b.subcompanyid='".$subcompanyid."' and b.projectid='".$projectid."' and (ifnull(b.verifypurpose,'')='' or  ifnull(b.verifypayto,'')='')");
	$unvrydata=array();
	if($bbnkqq->num_rows>0){
		while($bbrow=$bbnkqq->fetch_assoc()){
			$subprj = subprojectdetails($conn, $companyid, $subcompanyid, $projectid, "subprojectid", $bbrow['subprojectid']);
			$subprojectname = "";
			if (is_array($subprj) && isset($subprj[0]['subprojectname'])) {
			    $subprojectname = $subprj[0]['subprojectname'];
			}

			$unvrydata[]=array('bsno'=>$bbrow['bsno'], 'msno'=>$bbrow['msno'], 'subprojectid'=>$bbrow['subprojectid'], 'subprojectname'=>$subprojectname, 'request_no'=>$bbrow['request_no'], 'reference_no'=>$bbrow['reference_no'], 'beneficiary_name'=>$bbrow['beneficiary_name'], 'amount'=>$bbrow['amount'], 'purpose_category'=>$bbrow['purpose_category'], 'purpose_categoryid'=>$bbrow['purpose_categoryid'], 'purpose_subcategory'=>$bbrow['purpose_subcategory'], 'purpose_subcategoryid'=>$bbrow['purpose_subcategoryid'], 'purpose'=>$bbrow['purpose'], 'purpose_code'=>$bbrow['purpose_code'], 'provided_code'=>$bbrow['provided_code'], 'invoice_type'=>$bbrow['invoice_type'], 'invoice_no'=>$bbrow['invoice_no'], 'invoicedate'=>$bbrow['invoicedate'], 'description'=>$bbrow['description'], 'payto'=>$bbrow['payto'],'finyear'=>$bbrow['finyear'],'bookdate'=>$bbrow['bookdate'],'paytoid'=>$bbrow['paytoid'],'payfromaccountid'=>$bbrow['payfromaccountid'],'reconciliation_banksno'=>$bbrow['reconciliation_banksno'],'clsno'=>$bbrow['clsno'], 'banktransactiondate'=>$bbrow['banktransactiondate'],'banktransactionno'=>$bbrow['banktransactionno'], 'banktransactionamount'=>$bbrow['banktransactionamount'],'bankdescription'=>$bbrow['bankdescription'],'verifypurpose'=>$bbrow['verifypurpose'],'verifypayto'=>$bbrow['verifypayto']);
		}
	}
	echo json_encode($unvrydata);
}else if($action=="verifiedpurpose_tobookdata_credit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecode = $_POST['purposecode'];
	$loginname = $_POST['loginname'];
	$prpin=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	$purpose_category ="";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	$purpose_providedcode ="";
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$purpose_category = $ppkkey['pcategory'];
			$purpose_categoryid = $ppkkey['pcategorycode'];
			$purpose_subcategory = $ppkkey['psub_category'];
			$purpose_subcategoryid = $ppkkey['psub_categoryid'];
			$purpose = $ppkkey['purpose'];
			$purpose_code = $ppkkey['purposecode'];
			$purpose_providedcode = $ppkkey['provided_code'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Purpose details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	// brdbsno
	// maindbsno

	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	if(count($unverifyedbookdata)>0){
		$conn->begin_transaction();
		try {
			foreach ($unverifyedbookdata as $asspkey) {
				// Prepare the query
				$verifypurpose = "verified on date ".date('Y-m-d H:i:s')." by ".$loginname;
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET purpose_categoryid = ?, purpose_subcategory = ?, purpose_subcategoryid = ?, purpose_subcategory = ?, purpose_code = ?, purpose = ?, provided_code =?, verifypurpose=? WHERE sno = ?");		    
			    // Bind parameters
			    $stmt->bind_param("isssssssi",$purpose_categoryid,$purpose_category,$purpose_subcategoryid,$purpose_subcategory,$purpose_code,$purpose,$purpose_providedcode,$verifypurpose,$asspkey['brdbsno']);
			    // Execute the prepared statement
			    $stmt->execute();
			}

			// Commit transaction
			$conn->commit();

			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Update and verified purpose successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="verifiedpayto_tobookdata_credit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoid = $_POST['paytoid'];
	$loginname = $_POST['loginname'];
	$prpin=getpayerinfo($conn,$companyid,$subcompanyid,'payerid',$paytoid);
	$assprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$paytoname = $ppkkey['payername'];
			$tobankname = $ppkkey['tobankname'];
			$toaccountno = $ppkkey['toaccountno'];
			$tobankifsc = $ppkkey['tobankifsc'];
		}
	}else{
		$assprpmsg=array('status'=>'Error', 'message'=>'Payer details did not available in master list or somthing wrong');
		echo json_encode($assprpmsg);
		die();
	}
	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	if (count($unverifyedbookdata) > 0) {
	    $conn->begin_transaction();
	    try {
	        // Loop through each record in $unverifyedbookdata
	        foreach ($unverifyedbookdata as $asspkey) {
	            $verifypayto = "Verified on date " . date('Y-m-d H:i:s') . " by " . $loginname;

	            // Prepare the first query for tbl_maintransaction
	            $stmt = $conn->prepare("UPDATE tbl_maintransaction_credit SET paytoid = ?, payto = ? WHERE sno = ?");
	            if (!$stmt) {
	                throw new Exception("Error preparing statement for tbl_maintransaction: " . $conn->error);
	            }
	            $stmt->bind_param("sss", $paytoid, $paytoname, $asspkey['maindbsno']);
	            $stmt->execute();
	            if ($stmt->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction: " . $stmt->error);
	            }

	            // Prepare the second query for tbl_maintransaction_breakdown
	            $stmt1 = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET verifypayto = ? WHERE sno = ?");
	            if (!$stmt1) {
	                throw new Exception("Error preparing statement for tbl_maintransaction_breakdown: " . $conn->error);
	            }
	            $stmt1->bind_param("si", $verifypayto, $asspkey['brdbsno']);
	            $stmt1->execute();
	            if ($stmt1->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction_breakdown: " . $stmt1->error);
	            }
	            // Close each statement after use
	            $stmt->close();
	            $stmt1->close();
	        }
	        // Commit the transaction
	        $conn->commit();

	        // Return success message
	        $assprpmsg = array('status' => 'Success', 'message' => 'Payer details updated and verified successfully');
	    } catch (Exception $e) {
	        // Rollback transaction in case of error
	        $conn->rollback();
	        $assprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
	    }
	}

	echo json_encode($assprpmsg);
}else if($action=="verifiedsubproject_tobookdata_credit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$loginname = $_POST['loginname'];
	$assprpmsg=array();
	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	if (count($unverifyedbookdata) > 0) {
	    $conn->begin_transaction();
	    try {
	        // Loop through each record in $unverifyedbookdata
	        foreach ($unverifyedbookdata as $asspkey) {
	            // Prepare the first query for tbl_maintransaction
	            $stmt = $conn->prepare("UPDATE tbl_maintransaction_credit SET subprojectid = ? WHERE sno = ?");
	            if (!$stmt) {
	                throw new Exception("Error preparing statement for tbl_maintransaction: " . $conn->error);
	            }
	            $stmt->bind_param("ss", $subprojectid, $asspkey['maindbsno']);
	            $stmt->execute();
	            if ($stmt->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction: " . $stmt->error);
	            }

	            // Prepare the second query for tbl_maintransaction_breakdown
	            $stmt1 = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET subprojectid = ? WHERE sno = ?");
	            if (!$stmt1) {
	                throw new Exception("Error preparing statement for tbl_maintransaction_breakdown: " . $conn->error);
	            }
	            $stmt1->bind_param("si", $subprojectid, $asspkey['brdbsno']);
	            $stmt1->execute();
	            if ($stmt1->error) {
	                throw new Exception("Error executing statement for tbl_maintransaction_breakdown: " . $stmt1->error);
	            }

	            // Close each statement after use
	            $stmt->close();
	            $stmt1->close();
	        }

	        // Commit the transaction
	        $conn->commit();

	        // Return success message
	        $assprpmsg = array('status' => 'Success', 'message' => 'Sub-project details updated and verified successfully');
	    } catch (Exception $e) {
	        // Rollback transaction in case of error
	        $conn->rollback();
	        $assprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
	    }
	}

	echo json_encode($assprpmsg);
}else if($action=="verifiedpaytoandpurpose_tobookdata_credit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$unverifyedbookdata = json_decode($_POST['unverifyedbookdata'],TRUE);
	$assprpmsg=array();
	if(count($unverifyedbookdata)>0){
		$conn->begin_transaction();
		try {
			foreach ($unverifyedbookdata as $asspkey) {
				// Prepare the query
				$verify = "verified on date ".date('Y-m-d H:i:s')." by ".$loginname;
				$stmt = $conn->prepare("UPDATE tbl_maintransaction_credit_breakdown SET verifypayto =?, verifypurpose=? WHERE sno = ?");		    
			    // Bind parameters
			    $stmt->bind_param("ssi",$verify,$verify,$asspkey['brdbsno']);
			    // Execute the prepared statement
			    $stmt->execute();
			}
			// Commit transaction
			$conn->commit();
			// Close the prepared statement
			$stmt->close();
			// echo "All records updated successfully.";
			$assprpmsg=array('status'=>'Success', 'message'=>'Update and verified payer and purpose successfully');
		} catch (Exception $e) {
		    // Rollback transaction if any query fails
		    $conn->rollback();
		    // Return failure message
		    $assprpmsg=array('status'=>'Error', 'message'=>$e->getMessage());
		}
	}
	echo json_encode($assprpmsg);
}else if($action=="reconstructionunknowndata_tobook"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$paytoid = $_POST['paytoid'];
	$beneficiaryname = isset($_POST['beneficiaryname'])?$_POST['beneficiaryname']:"";
	$purposecode = $_POST['purposecode'];
	$loginname = $_POST['loginname'];
	$loginid = $_POST['loginid'];
	$savehistory = $_POST['savehistory'];
	$averageamountofgroup = isset($_POST['averageamountofgroup'])?$_POST['averageamountofgroup']:"";
	$minamtofgroup = isset($_POST['minamtofgroup'])?$_POST['minamtofgroup']:"";
	$maxamtofgroup = isset($_POST['maxamtofgroup'])?$_POST['maxamtofgroup']:"";
	$countofgroup = isset($_POST['countofgroup'])?$_POST['countofgroup']:"";
	$groupbankdescription = isset($_POST['seldescription'])?$_POST['seldescription']:"";
	$remarks = $_POST['remarks'];

	$prpin=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoid',$paytoid);
	$unreassprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$paytoname = $ppkkey['paytoname'];
			$tobankname = $ppkkey['tobankname'];
			$toaccountno = $ppkkey['toaccountno'];
			$tobankifsc = $ppkkey['tobankifsc'];
		}
	}else{
		$unreassprpmsg=array('status'=>'Error', 'message'=>'Payto details do not exist in the master');
		echo json_encode($unreassprpmsg);
		die();
	}

	$prpin=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	// print_r($prpin);
	$purpose_category ="";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	$purpose_providedcode ="";
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$purpose_category = $ppkkey['pcategory'];
			$purpose_categoryid = $ppkkey['pcategorycode'];
			$purpose_subcategory = $ppkkey['psub_category'];
			$purpose_subcategoryid = $ppkkey['psub_categoryid'];
			$purpose = $ppkkey['purpose'];
			$purpose_code = $ppkkey['purposecode'];
			$purpose_providedcode = $ppkkey['provided_code'];
		}
	}else{
		$unreassprpmsg=array('status'=>'Error', 'message'=>'Purpose details are not available in the master.');
		echo json_encode($unreassprpmsg);
		die();
	}

	if(in_array($purpose_categoryid, ["2", "3", "5", "7"])){
		$chkimp=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$paytoid);
		if(count($chkimp)>0){
			if(in_array($purpose_categoryid, ["3"])){
				$chkimp=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"payto",$paytoname);
				if(count($chkimp)>0){
					
				}else{
					$authirzedprojects[]=array('companyid'=>$companyid,'subcompanyid'=>$subcompanyid,'projectid'=>$projectid);
					$ssmppy=saveimprestauthorization($conn,$companyid,$subcompanyid,$projectid,$paytoname,$tobankname,$toaccountno,$tobankifsc,$authirzedprojects,$loginid,$paytoid);
					if(count($ssmppy)>0){
						foreach ($ssmppy as $ssmppykey) {
							if($ssmppy['status']=="Error"){
								$unreassprpmsg = array('status' => 'Error', 'message' =>$ssmppy['message']);
						        echo json_encode($unreassprpmsg);
						        exit();
							}
						}
					}
				}
			}		
		}else{
			// print_r($purpose_categoryid);
			if(in_array($purpose_categoryid, ["3"])){
				$chkimp=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"payto",$paytoname);
				if(count($chkimp)>0){
					
				}else{
					$authirzedprojects[]=array('companyid'=>$companyid,'subcompanyid'=>$subcompanyid,'projectid'=>$projectid);
					$ssmppy=saveimprestauthorization($conn,$companyid,$subcompanyid,$projectid,$paytoname,$tobankname,$toaccountno,$tobankifsc,$authirzedprojects,$loginid,$paytoid);
					if(count($ssmppy)>0){
						foreach ($ssmppy as $ssmppykey) {
							if($ssmppy['status']=="Error"){
								$unreassprpmsg = array('status' => 'Error', 'message' =>$ssmppy['message']);
						        echo json_encode($unreassprpmsg);
						        exit();
							}
						}
					}
				}
			}else{
				$unreassprpmsg = array('status' => 'Error', 'message' => 'Payto name is not available in the bank account master, so you cannot reconstruct as ' . $purpose);
		        echo json_encode($unreassprpmsg);
		        exit();
			}
		}
	}

	$loginname = $_POST['loginname'];
	$unknowbankndata = json_decode($_POST['unknowbankndata'],TRUE);
	
	$groupaccountid = $unknowbankndata[0]['unraccountid'];
	$unreassprpmsg = array('status' => 'Error', 'message' => 'No data to process.');
	if(!$beneficiaryname){
		$beneficiaryname=$paytoname;
	}
	if (count($unknowbankndata) > 0 && $purpose_categoryid) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	    $conn->begin_transaction();
	    try {
	        $invoicedata = array();
	        $voucherinfo = array();
	        // print_r($unknowbankndata);
	        foreach ($unknowbankndata as $asspkey) {
	            $voucherinfo[] = array(
	                'beneficiaryname' => $beneficiaryname,
	                'expenseamount' => $asspkey['unbankamount'],
	                'invoicedate' => '',
	                'invoiceno' => '',
	                'invoicetype' => '',
	                'description' => $asspkey['unbankdescription'],
	                'purposecode' => $purposecode,
	                'purposename' => $purpose,
	                'attachmentpath' => '',
	                'audiofilepath' => '',
	                'voiceinstid' => '',
	                'docsno' => '',
	                'srm_attsno' => '',
	                'srm_emailsno' => '',
	                'invoicedata' => $invoicedata,
	                'totalrowsamount' => 0,
	                'totalgstamount' => 0,
	                'totalrowsdiscount' => 0,
	                'otherdiscount' => 0,
	                'totalpayableamount' => 0,
	                'taxableamount' => $asspkey['unbankamount'],
	                'invoicegstamount' => 0,
	                'ponumber' => ''
	            );

	            if ($paytoid && $beneficiaryname && $asspkey['unbankamount'] > 0 && $asspkey['untransactiondate'] && $purposecode && $asspkey['unbankdescription']) {
	                $rrs = recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$subprojectid,$asspkey['unraccountid'],"online",$asspkey['untransactionno'],$asspkey['untransactiondate'],$asspkey['unbankamount'],$paytoid,$voucherinfo,$remarks,$loginname,$purpose_categoryid,$purpose_subcategoryid,$purposecode,"");
							
	                if (count($rrs) > 0) {
	                    foreach ($rrs as $rrskey) {
	                        reconciled_updatebankstatment_closetobook($conn, $rrskey['referenceno'], $asspkey['untransactiondate'], $asspkey['undbsno'], $rrskey['referenceno'], "unknowreconstruction");
	                        if ($rrskey['status'] == "Success" && $rrskey['referenceno']) {
	                            verifiedpaytoandpurposetobookdata($conn, $companyid, $subcompanyid, $projectid, $rrskey['referenceno'], "yes", "yes", $loginname);
	                        }
	                    }
	                }
	            }
	            $voucherinfo = array();
	        }
	        if($savehistory){
        		savetowithdrawalhistory($conn, $companyid, $subcompanyid, $projectid, $groupaccountid,$purpose_code ,$groupbankdescription,$averageamountofgroup,$minamtofgroup,$maxamtofgroup,$countofgroup,$paytoid);
        	}
        	reconstructbookwith_unknown($conn,$companyid,$subcompanyid,$projectid,$groupaccountid,'withdrawn');
	        $conn->commit();
	        $unreassprpmsg = array('status' => 'Success', 'message' => 'Update and verified payto and purpose successfully');
	    } catch (Exception $e) {
	        $conn->rollback();
	        $unreassprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
	    }
	}
	echo json_encode($unreassprpmsg);
}else if($action=="reconstruct_unknown_fromhistory"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$unknowbankndata = json_decode($_POST['unknowbankndata'],TRUE);
	$arrlength = count($unknowbankndata);
	$unreassprpmsg = array();
	if($arrlength > 0) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$conn->begin_transaction();
		try {
			foreach ($unknowbankndata as $asspkey) {
				$prpin=getpaytoinfo($mconn,$companyid,$subcompanyid,'paytoid',$asspkey['paytoid']);
				$unreassprpmsg=array();
				if(count($prpin)>0){
					foreach ($prpin as $ppkkey) {
						$paytoname = $ppkkey['paytoname'];
						$tobankname = $ppkkey['tobankname'];
						$toaccountno = $ppkkey['toaccountno'];
						$tobankifsc = $ppkkey['tobankifsc'];
					}
				}else{
					$unreassprpmsg=array('status'=>'Error', 'message'=>'Payto details do not exist in the master');
					echo json_encode($unreassprpmsg);
					die();
				}

				$prpin=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$asspkey['purposecode']);
				$purpose_category ="";
				$purpose_categoryid ="";
				$purpose_subcategory ="";
				$purpose_subcategoryid ="";
				$purpose ="";
				$purpose_code ="";
				$purpose_providedcode ="";
				if(count($prpin)>0){
					foreach ($prpin as $ppkkey) {
						$purpose_category = $ppkkey['pcategory'];
						$purpose_categoryid = $ppkkey['pcategorycode'];
						$purpose_subcategory = $ppkkey['psub_category'];
						$purpose_subcategoryid = $ppkkey['psub_categoryid'];
						$purpose = $ppkkey['purpose'];
						$purpose_code = $ppkkey['purposecode'];
						$purpose_providedcode = $ppkkey['provided_code'];
					}
				}else{
					$unreassprpmsg=array('status'=>'Error', 'message'=>'Purpose details are not available in the master.');
					echo json_encode($unreassprpmsg);
					die();
				}
				
				$beneficiaryname=$paytoname;
				

	            $conn->query("update tbl_maintransaction set payto='".$paytoname."',paytoid='".$paytoid."',description='Reconstructed From History',book_status='complete',reconcilation_process='ReconstructedFromHistory' where reconciliation_banksno='".$asspkey['undbsno']."'");
	            $conn->query("update tbl_maintransaction_breakdown set beneficiary_name='".$beneficiaryname."',purpose_category='".$purpose_category."',purpose_categoryid='".$purpose_categoryid."',purpose_subcategory='".$purpose_subcategory."',purpose_subcategoryid='".$purpose_subcategoryid."',purpose='".$purpose."',purpose_code='".$purpose_code."',provided_code='".$purpose_providedcode."',description='Reconstructed From History' where reference_no='".$asspkey['bookreference_no']."'");

				$conn->query("update tbl_closetobook set book_status='complete' where sno='".$asspkey['undbsno']."'");
				if($purpose_categoryid=="2"){ //Loan
					//...code to be written
				}else if($purpose_categoryid=="3"){ //Imprest
					$accqry="select accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and lcase(accountname)='".strtolower(addslashes($paytoname))."' and ifnull(tedit,'')=''";
					$rlt = $mconn->query($accqry);
					$impaccountid ="";
					if($rlt->num_rows>0){
						$impacrow = $rlt->fetch_assoc();
						$impaccountid = $impacrow["accountid"];
					}
					else{
						$accqry="select ifnull(max(accountid)+1,7000) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and ifnull(tedit,'')=''";
						$rlt = $mconn->query($accqry);
						if($rlt->num_rows>0){
							$impacrow = $rlt->fetch_assoc();
							$impaccountid = $impacrow["accountid"];

							$newaccqry = "insert into tbl_master_account(cmp_id,subcmp_id,projectid,accountid,account_type,accountname,accountholdername,status) values('".$companyid."', '".$subcompanyid."', '".$projectid."','".$impaccountid."','Imprest','".strtoupper(addslashes($paytoname))."','".strtoupper(addslashes($paytoname))."','unverified')";
							$conn->query($newaccqry);
						}
					}
					$imqr="Insert into imprest_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,paytoid,imprest_payto,imprest_amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid) values('".$asspkey['unvdate']."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$asspkey['unraccountid']."','".$asspkey['bookreference_no']."','".$paytoid."','".addslashes($paytoname)."','".$asspkey['unbankamount']."','".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($asspkey['unbankdescription'])."','".$impaccountid."')";
					$conn->query($imqr);
				}else if($purpose_categoryid=="4"){ //Partial Payment
					$ppqr="Insert into partial_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,paytoid,imprest_payto,imprest_amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountno) values('".$asspkey['unvdate']."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$asspkey['unraccountid']."','".$asspkey['bookreference_no']."','".$paytoid."','".addslashes($paytoname)."','".$asspkey['unbankamount']."','".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($asspkey['unbankdescription'])."','".$toaccountno."')";
					$conn->query($ppqr);
				}else if($purpose_categoryid=="6"){ //Cash
					$cshqr="Insert into cash_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,cash_payto,cash_amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description) values('".$asspkey['unvdate']."', '".$companyid."', '".$subcompanyid."', '".$projectid."','".$asspkey['unraccountid']."','".$asspkey['bookreference_no']."','".addslashes($paytoname)."','".$asspkey['unbankamount']."','".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($asspkey['unbankdescription'])."')";
			    	$mconn->query($cshqr);
				}
	        }
	    	$conn->commit();
		    $unreassprpmsg = array('status' => 'Success', 'message' => 'saved');
	    }
	    catch (Exception $e) {
	        $conn->rollback();
	        $unreassprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
    	}
    	echo json_encode($unreassprpmsg);	
	}
}else if($action=="reconstruct_unknown"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$paytoid = $_POST['paytoid'];
	$beneficiaryname = $_POST['beneficiaryname'];
	$purposecode = $_POST['purposecode'];
	$remarks = $_POST['remarks'];
	$savehistory = $_POST['savehistory'];
	$groupaccountid = $_POST['groupaccountid'];
	$seldescription = $_POST['seldescription'];
	$averageamountofgroup = $_POST['averageamountofgroup'];
	$totalentries = $_POST['countofgroup'];
	$minamt = $_POST['minamtofgroup'];
	$maxamt = $_POST['maxamtofgroup'];
	$autoassign = $_POST['autoassign'];
	$loginid = $_POST['loginid'];
	$loginname = $_POST['loginname'];
	$unknowbankndata = json_decode($_POST['unknowbankndata'],TRUE);
	$arrlength = count($unknowbankndata);
	$unreassprpmsg = array();
	if($arrlength > 0) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$conn->begin_transaction();
		try {
			foreach ($unknowbankndata as $asspkey) {
				$prpin=getpaytoinfo($mconn,$companyid,$subcompanyid,'paytoid',$paytoid);
				$unreassprpmsg=array();
				if(count($prpin)>0){
					foreach ($prpin as $ppkkey) {
						$paytoname = $ppkkey['paytoname'];
						$tobankname = $ppkkey['tobankname'];
						$toaccountno = $ppkkey['toaccountno'];
						$tobankifsc = $ppkkey['tobankifsc'];
					}
				}else{
					$unreassprpmsg=array('status'=>'Error', 'message'=>'Payto details do not exist in the master');
					echo json_encode($unreassprpmsg);
					die();
				}

				$prpin=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
				$purpose_category ="";
				$purpose_categoryid ="";
				$purpose_subcategory ="";
				$purpose_subcategoryid ="";
				$purpose ="";
				$purpose_code ="";
				$purpose_providedcode ="";
				if(count($prpin)>0){
					foreach ($prpin as $ppkkey) {
						$purpose_category = $ppkkey['pcategory'];
						$purpose_categoryid = $ppkkey['pcategorycode'];
						$purpose_subcategory = $ppkkey['psub_category'];
						$purpose_subcategoryid = $ppkkey['psub_categoryid'];
						$purpose = $ppkkey['purpose'];
						$purpose_code = $ppkkey['purposecode'];
						$purpose_providedcode = $ppkkey['provided_code'];
					}
				}else{
					$unreassprpmsg=array('status'=>'Error', 'message'=>'Purpose details are not available in the master.');
					echo json_encode($unreassprpmsg);
					die();
				}
				
	            $conn->query("update tbl_maintransaction set payto='".$paytoname."',paytoid='".$paytoid."',description='".$remarks."',reconcilation_process='Unknown Reconstruction' where reconciliation_banksno='".$asspkey['undbsno']."'");
	            $conn->query("update tbl_maintransaction_breakdown set beneficiary_name='".$beneficiaryname."',purpose_category='".$purpose_category."',purpose_categoryid='".$purpose_categoryid."',purpose_subcategory='".$purpose_subcategory."',purpose_subcategoryid='".$purpose_subcategoryid."',purpose='".$purpose."',purpose_code='".$purpose_code."',provided_code='".$purpose_providedcode."',description='".$remarks."' where reference_no='".$asspkey['bookreference_no']."'");
				$conn->query("update tbl_closetobook set book_status='complete' where sno='".$asspkey['undbsno']."'");
				if($purpose_categoryid=="2"){ //Loan
					//...code to be written
				}else if($purpose_categoryid=="3"){ //Imprest
					$accqry="select accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and lcase(accountname)='".strtolower(addslashes($paytoname))."' and ifnull(tedit,'')=''";
					$rlt = $mconn->query($accqry);
					$impaccountid ="";
					if($rlt->num_rows>0){
						$impacrow = $rlt->fetch_assoc();
						$impaccountid = $impacrow["accountid"];
					}
					else{
						$accqry="select ifnull(max(accountid)+1,7000) as accountid from tbl_master_account where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(account_type)='imprest' and ifnull(tedit,'')=''";
						$rlt = $mconn->query($accqry);
						if($rlt->num_rows>0){
							$impacrow = $rlt->fetch_assoc();
							$impaccountid = $impacrow["accountid"];

							$newaccqry = "insert into tbl_master_account(cmp_id,subcmp_id,projectid,accountid,account_type,accountname,accountholdername,status) values('".$companyid."', '".$subcompanyid."', '".$projectid."','".$impaccountid."','Imprest','".strtoupper(addslashes($paytoname))."','".strtoupper(addslashes($paytoname))."','unverified')";
							$conn->query($newaccqry);
						}
					}
					$imqr="Insert into imprest_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,paytoid,imprest_payto,imprest_amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountid) values('".$asspkey['unvdate']."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$asspkey['unraccountid']."','".$asspkey['bookreference_no']."','".$paytoid."','".addslashes($paytoname)."','".$asspkey['unbankamount']."','".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($asspkey['unbankdescription'])."','".$impaccountid."')";
					$conn->query($imqr);
				}else if($purpose_categoryid=="4"){ //Partial Payment
					$ppqr="Insert into partial_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,paytoid,imprest_payto,imprest_amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description,to_accountno) values('".$asspkey['unvdate']."','".$companyid."', '".$subcompanyid."', '".$projectid."','".$asspkey['unraccountid']."','".$asspkey['bookreference_no']."','".$paytoid."','".addslashes($paytoname)."','".$asspkey['unbankamount']."','".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($asspkey['unbankdescription'])."','".$toaccountno."')";
					$conn->query($ppqr);
				}else if($purpose_categoryid=="6"){ //Cash
					$cshqr="Insert into cash_account(idate,companyid,subcompanyid,projectid,from_accountid,reference_no,cash_payto,cash_amount,purpose_categoryid,purpose_category,purpose_subcategoryid,purpose_subcategory,purpose,purpose_code,description) values('".$asspkey['unvdate']."', '".$companyid."', '".$subcompanyid."', '".$projectid."','".$asspkey['unraccountid']."','".$asspkey['bookreference_no']."','".addslashes($paytoname)."','".$asspkey['unbankamount']."','".$purpose_categoryid."','".addslashes($purpose_category)."','".$purpose_subcategoryid."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".$purpose_code."','".addslashes($asspkey['unbankdescription'])."')";
			    	$mconn->query($cshqr);
				}
	        }
	        //savehistorycode here.
	        if($savehistory){
		        savetowithdrawalhistory($conn, $companyid, $subcompanyid, $projectid, $groupaccountid,$purposecode ,$seldescription,$averageamountofgroup,$minamt,$maxamt,$totalentries,$paytoid,$autoassign);
		    }
	    	$conn->commit();
		    $unreassprpmsg = array('status' => 'Success', 'message' => 'saved');
	    }
	    catch (Exception $e) {
	        $conn->rollback();
	        $unreassprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
    	}
    	echo json_encode($unreassprpmsg);	
	}
}else if($action=="reconstructiontobook_manuallyfrombankdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$paytoid = $_POST['paytoid'];
	$accountid = $_POST['accountid'];
	$purposecode = $_POST['purposecode'];
	$remarks = $_POST['remarks'];
	$loginid = $_POST['loginid'];
	$loginname = $_POST['loginname'];
	$banksno = $_POST['banksno'];
	/*$prpin=getpaytoinfo($mconn,$companyid,$subcompanyid,'paytoid',$paytoid);
	$unreassprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$paytoname = $ppkkey['paytoname'];
			$tobankname = $ppkkey['tobankname'];
			$toaccountno = $ppkkey['toaccountno'];
			$tobankifsc = $ppkkey['tobankifsc'];
		}
	}else{
		$unreassprpmsg=array('status'=>'Error', 'message'=>'Payto details do not exist in the master');
		echo json_encode($unreassprpmsg);
		die();
	}

	$prpin=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	$purposecategory ="";
	$categoryid ="";
	$sub_category ="";
	$sub_categoryid ="";
	$purpose ="";
	$providedcode ="";
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$categoryid = $ppkkey['pcategorycode'];
			$purposecategory = $ppkkey['pcategory'];
			$sub_categoryid = $ppkkey['psub_categoryid'];
			$sub_category = $ppkkey['psub_category'];
			$purposecode = $ppkkey['purposecode'];
			$purpose = $ppkkey['purpose'];
			$providedcode = $ppkkey['provided_code'];
		}
	}else{
		$unreassprpmsg=array('status'=>'Error', 'message'=>'Purpose details are not available in the master.');
		echo json_encode($unreassprpmsg);
		die();
	}
	
	// pay from account
	$pyfromacc = getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
	if(count($pyfromacc)>0){
		foreach ($pyfromacc as $pfrmkey) {
			$bankid = $pfrmkey['bankid'];
			$accountno = $pfrmkey['accountno'];
			$ifsccode = $pfrmkey['ifsccode'];
			$bankname = $pfrmkey['bankname'];
		}
	}

    $finyeartype = getfinancialyeartype($conn,$companyid);
    $transactiontype="";
	$unreconsiledata=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,'sno',$banksno);
	$insbrkdwn = "Insert into tbl_maintransaction_breakdown(companyid, subcompanyid, projectid, reference_no, beneficiary_name, amount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, provided_code, description, description_bank) VALUES";
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
			$insbrkdwnvals = $insbrkdwnvals.",('".$companyid."','".$subcompanyid."','".$projectid."','".$newreference_no."','','".$total_unknown_amount."','".$categoryid."','".$purposecategory."','".$sub_categoryid."','".$sub_category."','".$purpose."','".$purposecode."','".$providedcode."','Marked Unknown transaction','')";
		}
		else{
			$insbrkdwnvals = "('".$companyid."','".$subcompanyid."','".$projectid."','".$newreference_no."','','".$total_unknown_amount."','".$categoryid."','".$purposecategory."','".$sub_categoryid."','".$sub_category."','".$purpose."','".$purposecode."','".$providedcode."','Marked Unknown transaction','')";
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
	*/
}else if($action=="savedatatohistory"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $description = $_POST['groupdescription'];
    $purposecode = $_POST["purposecode"];
    $paytoid = $_POST["paytoid"];
    $groupaverageamt = $_POST["groupaverageamt"];
    $minamt = $_POST["minamt"];
    $maxamt = $_POST["maxamt"];
    $totalentries = $_POST["totalentries"];
    $autoassign = $_POST["autoassign"];
    $retval=savetowithdrawalhistory($conn, $companyid, $subcompanyid, $projectid, $accountid,$purposecode ,$description,$groupaverageamt,$minamt,$maxamt,$totalentries,$paytoid,$autoassign);
    echo json_encode($retval);
}else if($action=="sendunknowndatatocompany"){
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $description = $_POST['description'];
    $companyreply = $_POST['companyreply'];
    $otherdetails = $_POST['otherdetails'];
    $emailaddress = $_POST['emailaddress'];
    $Hloginid = $_POST['Hloginid'];
    $Hloginroleid = $_POST['Hloginroleid'];
    $Hloginrolename = $_POST['Hloginrolename'];
    $Hloginname = $_POST['Hloginname'];

    $transactiontype = $_POST['transactiontype'];
    $rooturl= $_POST['rooturl'];
    $form_url= $rooturl."reconcilation_desposit/companyresponseforunknown_withdrawn.php";
    $voicereceiptpath = "";
	if (isset($_FILES['audioFile']) && !$_FILES['audioFile']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/userquery/'.date('Ymd').'/';
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($receiptpathDir)) {
            mkdir($receiptpathDir, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["audioFile"]["name"]);
		$receiptname = round(microtime(true)) . '.' . end($temp);
        $uploadFile = $receiptpathDir . $receiptname;
        $voicereceiptpath = str_replace("../", "", $uploadFile);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['audioFile']['tmp_name'], $uploadFile);
    }

	$unreassprpmsg = array();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    $l=0;
	$curnow=date("Y-m-d h:m:s");
    try {
		$conn->query("insert into tblcompanyreplyunknown(companyid,subcompanyid,projectid,account_id,otherdetails,companyreply,voiceremarkfile,groupdescription,source,transactiontype) values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$otherdetails."','".$companyreply."','".$voicereceiptpath."','".$description."','user', '".$transactiontype."')");
		$l++;
		$details = json_decode($otherdetails,true);
		foreach($details as $detail){ 
			$conn->query("update tbl_closetobook set userquery='".addslashes($companyreply)."',userqueryfile='".$voicereceiptpath."' , sent_status='company' where sno='".$detail["sno"]."'");
		}
		// email sent 
		$emailid_array= loginuserdetailsfrommaster($authconn,"","projectid",$projectid); 

		foreach($emailid_array as $emaildetail){ 
			$emailid=$emaildetail['emailid']; 
			if($emailid){
				$loginname=$emaildetail['loginname'];
				$m_subject = "Unknown entries of withdrawn side in your bank account/".$projectid;
				 $qstring = $emaildetail['loginid']."|".$emaildetail['loginroleid']."|".$companyid."|".$subcompanyid."|".$projectid."|".$emaildetail['loginrolename'];
				 $enc_qstring = base64url_encode($qstring);
				$key = $qstring . "|" . $enc_qstring;
				$enc_key = base64url_encode($key);
				$weblink=$form_url."?k=".$enc_key; 
			    $sendTo = $emailid;
				$sendcc = "";
				$sendBcc = "";
				$attachments = ""; 
				$m_msgbody = "<p>Dear ".$loginname.",</p><br>We have come across uknown enteries in your bank account. Please click on the below link.<p></p>".$weblink;
				$sql = "INSERT INTO emailsendby_cron (sendTo, sendcc, sendBcc, m_subject, m_msgbody, attch, generateddate) VALUES ('".$sendTo."' ,'".$sendcc."' ,'".$sendBcc."' ,'".addslashes($m_subject)."' ,'".addslashes($m_msgbody)."' ,'".$attachments."' ,'".$curnow."')";
				$conn->query($sql);
			}
		}
		if($emailaddress){				
				$m_subject = "Unknown entries of withdrawn side in your bank account/".$projectid;
				 $qstring = $Hloginid."|".$Hloginroleid."|".$companyid."|".$subcompanyid."|".$projectid."|".$Hloginrolename;
				 $enc_qstring = base64url_encode($qstring);
				$key = $qstring . "|" . $enc_qstring;
				$enc_key = base64url_encode($key);
				$weblink=$form_url."?k=".$enc_key; 
			    $sendTo = $emailid;
				$sendcc = "";
				$sendBcc = "";
				$attachments = ""; 
				$m_msgbody = "<p>Dear ".$Hloginname.",</p><br>We have come across uknown enteries in your bank account. Please click on the below link.<p></p>".$weblink;
				$sql = "INSERT INTO emailsendby_cron (sendTo, sendcc, sendBcc, m_subject, m_msgbody, attch, generateddate) VALUES ('".$emailaddress."' ,'".$sendcc."' ,'".$sendBcc."' ,'".addslashes($m_subject)."' ,'".addslashes($m_msgbody)."' ,'".$attachments."' ,'".$curnow."')";
				$conn->query($sql);
			}

		$conn->commit();
	   $unreassprpmsg = array('status' => 'Success', 'message' => 'Query Sent Successfully');
	} catch (Exception $e) {
        $conn->rollback();
        $unreassprpmsg = array('status' => 'Error', 'message' => $e->getMessage()." Level:".$l);
    }
    echo json_encode($unreassprpmsg);
}else if($action=="sendunknowndatatocompany_deposit"){
 
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $description = $_POST['description'];
    $companyreply = $_POST['companyreply'];
    $otherdetails = $_POST['otherdetails'];
    $emailaddress = $_POST['emailaddress'];
    $Hloginid = $_POST['Hloginid'];
    $Hloginroleid = $_POST['Hloginroleid'];
    $Hloginrolename = $_POST['Hloginrolename'];
    $Hloginname = $_POST['Hloginname'];

    $transactiontype = $_POST['transactiontype'];
    $rooturl= $_POST['rooturl'];
    $form_url= $rooturl."reconcilation_desposit/companyresponseforunknown_new.php";
    $voicereceiptpath = "";
	if (isset($_FILES['audioFile']) && !$_FILES['audioFile']['error']) 
	{
        // Directory where the audio file will be saved
        $receiptpathDir = '../attachementfile/'.$companyid.'/'.$subcompanyid.'/'.$projectid.'/userquery/'.date('Ymd').'/';
        
        // Create the uploads directory if it doesn't exist
        if (!is_dir($receiptpathDir)) {
            mkdir($receiptpathDir, 0755, true);
        }
        
        //file name
        $temp = explode(".", $_FILES["audioFile"]["name"]);
		$receiptname = round(microtime(true)) . '.' . end($temp);
        $uploadFile = $receiptpathDir . $receiptname;
        $voicereceiptpath = str_replace("../", "", $uploadFile);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['audioFile']['tmp_name'], $uploadFile);
    }

	$unreassprpmsg = array();
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn->begin_transaction();
    $l=0;
	$curnow=date("Y-m-d h:m:s");
    try {
		$conn->query("insert into tblcompanyreplyunknown(companyid,subcompanyid,projectid,account_id,otherdetails,companyreply,voiceremarkfile,groupdescription,source,transactiontype) values('".$companyid."','".$subcompanyid."','".$projectid."','".$accountid."','".$otherdetails."','".$companyreply."','".$voicereceiptpath."','".$description."','user', '".$transactiontype."')");
		$l++;
		$details = json_decode($otherdetails,true);
		foreach($details as $detail){ 
			$conn->query("update tbl_closetobook set userquery='".addslashes($companyreply)."',userqueryfile='".$voicereceiptpath."' , sent_status='company' where sno='".$detail["sno"]."'");
		}
		// email sent 
		$emailid_array= loginuserdetailsfrommaster($authconn,"","projectid",$projectid); 
		//$emailid_array=getprojectdetails($conn,$companyid,$subcompanyid,'projectid',$projectid);
		
		foreach($emailid_array as $emaildetail){ 
			$emailid=$emaildetail['emailid']; 
			if($emailid){
				$loginname=$emaildetail['loginname'];
				$m_subject = "Unknown entries of deposit side in your bank account/".$projectid;
				 $qstring = $emaildetail['loginid']."|".$emaildetail['loginroleid']."|".$companyid."|".$subcompanyid."|".$projectid."|".$emaildetail['loginrolename'];
				 $enc_qstring = base64url_encode($qstring);
				$key = $qstring . "|" . $enc_qstring;
				$enc_key = base64url_encode($key);
				$weblink=$form_url."?k=".$enc_key; 
			    $sendTo = $emailid;
				$sendcc = "";
				$sendBcc = "";
				$attachments = ""; 
				$m_msgbody = "<p>Dear ".$loginname.",</p><br>We have come across uknown enteries in your bank account. Please click on the below link.<p></p>".$weblink;
				$sql = "INSERT INTO emailsendby_cron (sendTo, sendcc, sendBcc, m_subject, m_msgbody, attch, generateddate) VALUES ('".$sendTo."' ,'".$sendcc."' ,'".$sendBcc."' ,'".addslashes($m_subject)."' ,'".addslashes($m_msgbody)."' ,'".$attachments."' ,'".$curnow."')";
				$conn->query($sql);
			}
		} 

		if($emailaddress){
				
				$m_subject = "Unknown entries of deposit side in your bank account/".$projectid;
				 $qstring = $Hloginid."|".$Hloginroleid."|".$companyid."|".$subcompanyid."|".$projectid."|".$Hloginrolename;
				 $enc_qstring = base64url_encode($qstring);
				$key = $qstring . "|" . $enc_qstring;
				$enc_key = base64url_encode($key);
				$weblink=$form_url."?k=".$enc_key; 
			    $sendTo = $emailid;
				$sendcc = "";
				$sendBcc = "";
				$attachments = ""; 
				$m_msgbody = "<p>Dear ".$Hloginname.",</p><br>We have come across uknown enteries in your bank account. Please click on the below link.<p></p>".$weblink;
				$sql = "INSERT INTO emailsendby_cron (sendTo, sendcc, sendBcc, m_subject, m_msgbody, attch, generateddate) VALUES ('".$emailaddress."' ,'".$sendcc."' ,'".$sendBcc."' ,'".addslashes($m_subject)."' ,'".addslashes($m_msgbody)."' ,'".$attachments."' ,'".$curnow."')";
				$conn->query($sql);
			}
		$conn->commit();
	   $unreassprpmsg = array('status' => 'Success', 'message' => 'Query Sent Successfully');
	} catch (Exception $e) {
        $conn->rollback();
        $unreassprpmsg = array('status' => 'Error', 'message' => $e->getMessage()." Level:".$l);
    }
    echo json_encode($unreassprpmsg);
 
}else if ($action=="sendunknowndatatoautoriseduser") {
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $emailaddress = $_POST['emailaddress'];
    $rooturl= $_POST['rooturl'];
    $Hloginid = $_POST['Hloginid'];
    $Hloginroleid = $_POST['Hloginroleid'];
    $Hloginrolename = $_POST['Hloginrolename'];
    $Hloginname = $_POST['Hloginname'];

    $form_url= $rooturl."webpages/unkonwreconstructionbook.php";
    $m_subject="Create History of Withdrawals";
    $weblink="";
   
    
    	$emailid=$emailaddress;
    	if($emailid){
				$loginname=$emaildetail['loginname'];
				$m_subject = "Create history of withdrawn side enteries";
				 $qstring = $Hloginid."|".$Hloginroleid."|".$companyid."|".$subcompanyid."|".$projectid."|".$Hloginrolename;
				 $enc_qstring = base64url_encode($qstring);
				$key = $qstring . "|" . $enc_qstring;
				$enc_key = base64url_encode($key);
				$weblink=$form_url."?k=".$enc_key; 
			    $sendTo = $emailid;
				$sendcc = "";
				$sendBcc = "";
				$attachments = ""; 
				$m_msgbody = "<p>Dear ".$Hloginname.",</p><br>Create history of enteries in your bank account. Please click on the below link.<p></p>".$weblink;
				
			}
    	
		
	

    $response=sendemailfunction($conn,$emailaddress,$m_subject,$m_msgbody);

  

   
    if($response){ 
    	$unreassprpmsg = array('status' => 'Success', 'message' => 'Query Sent Successfully');
    }else{
    	$unreassprpmsg = array('status' => 'Error', 'message' => 'Error');
    }
    echo json_encode($unreassprpmsg);
 
}else if ($action=="sendunknowndatatoautoriseduser_creditside") {
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $accountid = $_POST['accountid'];
    $emailaddress = $_POST['emailaddress'];
    $Hloginid = $_POST['Hloginid'];
    $Hloginroleid = $_POST['Hloginroleid'];
    $Hloginrolename = $_POST['Hloginrolename'];
    $Hloginname = $_POST['Hloginname'];

    $rooturl= $_POST['rooturl'];
    $form_url= $rooturl."reconcilation_desposit/reconstruct_depositside_book_rententries_ski_v1.php";
    $m_subject="Create History of Deposit Side";
    $weblink="";
   
    	$emailid=$emailaddress;
    	if($emailid){
				$loginname=$emaildetail['loginname'];
				$m_subject = "Create history of deposit side enteries";
				 $qstring = $Hloginid."|".$Hloginroleid."|".$companyid."|".$subcompanyid."|".$projectid."|".$Hloginrolename;
				 $enc_qstring = base64url_encode($qstring);
				$key = $qstring . "|" . $enc_qstring;
				$enc_key = base64url_encode($key);
				$weblink=$form_url."?k=".$enc_key; 
			    $sendTo = $emailid;
				$sendcc = "";
				$sendBcc = "";
				$attachments = ""; 
				$m_msgbody = "<p>Dear ".$Hloginname.",</p><br>Create history of enteries in your bank account. Please click on the below link.<p></p>".$weblink;
				
			}
    	
		
		 
   

    $response=sendemailfunction($conn,$emailaddress,$m_subject,$m_msgbody);

  // $response=Sendemailsaprat('anantkumar.rajput@gmail.com',$sendcc,$sendBcc,$m_subject,$m_msgbody,$attch = array());

   
    if($response){ 
    	$unreassprpmsg = array('status' => 'Success', 'message' => 'Query Sent Successfully');
    }else{
    	$unreassprpmsg = array('status' => 'Error', 'message' => 'Error');
    }
    echo json_encode($unreassprpmsg);
 
}else if($action=="reconstructionunknowndata_tobookdeposit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$paytoid = $_POST['payerid'];
	$payername = $_POST['payername'];
	$beneficiaryname = $_POST['beneficiaryname'];
	$purposecode = $_POST['purposecode'];
	$loginname = $_POST['loginname'];
	$loginid = $_POST['loginid'];
	//$remarks = $_POST['remarks'];

	$ssp=savetodbnewpayer_details($mconn,$payername,"","","","","","",$companyid,$subcompanyid,$projectid,$loginid,"");
	if(count($ssp)>0){
		foreach ($ssp as $sspkey) {
			if($sspkey['status']=="Success" || $sspkey['status']=="alreadyupdated"){
				$paytoid =$sspkey['payerid']; 
			}
		}
	}else{
		$restimp=array('status'=>'Error', 'message'=>$payername.' did not exist in our payto master. So somthing wrongh', 'paytolist'=>'');
		return $restimp;
	}

	$prpin=getpayerinfo($conn,$companyid,$subcompanyid,'payerid',$paytoid);
	$unreassprpmsg=array();
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$paytoname = $ppkkey['payername'];
			$tobankname = $ppkkey['tobankname'];
			$toaccountno = $ppkkey['toaccountno'];
			$tobankifsc = $ppkkey['tobankifsc'];
		}
	}else{
		$unreassprpmsg=array('status'=>'Error', 'message'=>'Payto details did not available in master list or somthing wrong');
		echo json_encode($unreassprpmsg);
		die();
	}

	$prpin=getcreditpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	// print_r($prpin);
	$purpose_category ="";
	$purpose_categoryid ="";
	$purpose_subcategory ="";
	$purpose_subcategoryid ="";
	$purpose ="";
	$purpose_code ="";
	$purpose_providedcode ="";
	if(count($prpin)>0){
		foreach ($prpin as $ppkkey) {
			$purpose_category = $ppkkey['pcategory'];
			$purpose_categoryid = $ppkkey['pcategorycode'];
			$purpose_subcategory = $ppkkey['psub_category'];
			$purpose_subcategoryid = $ppkkey['psub_categoryid'];
			$purpose = $ppkkey['purpose'];
			$purpose_code = $ppkkey['purposecode'];
			$purpose_providedcode = $ppkkey['provided_code'];
		}
	}else{
		$unreassprpmsg=array('status'=>'Error', 'message'=>'Purpose details did not available in master list or somthing wrong');
		echo json_encode($unreassprpmsg);
		die();
	}

	/*if(in_array($purpose_categoryid, ["2", "3", "5", "7"])){
		$chkimp=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$paytoid);
		if(count($chkimp)>0){
			if(in_array($purpose_categoryid, ["3"])){
				$chkimp=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"payto",$paytoname);
				if(count($chkimp)>0){
					
				}else{
					$authirzedprojects[]=array('companyid'=>$companyid,'subcompanyid'=>$subcompanyid,'projectid'=>$projectid);
					$ssmppy=saveimprestauthorization($conn,$companyid,$subcompanyid,$projectid,$paytoname,$tobankname,$toaccountno,$tobankifsc,$authirzedprojects,$loginid,$paytoid);
					if(count($ssmppy)>0){
						foreach ($ssmppy as $ssmppykey) {
							if($ssmppy['status']=="Error"){
								$unreassprpmsg = array('status' => 'Error', 'message' =>$ssmppy['message']);
						        echo json_encode($unreassprpmsg);
						        exit();
							}
						}
					}
				}
			}		
		}else{
			// print_r($purpose_categoryid);
			if(in_array($purpose_categoryid, ["3"])){
				$chkimp=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"payto",$paytoname);
				if(count($chkimp)>0){
					
				}else{
					$authirzedprojects[]=array('companyid'=>$companyid,'subcompanyid'=>$subcompanyid,'projectid'=>$projectid);
					$ssmppy=saveimprestauthorization($conn,$companyid,$subcompanyid,$projectid,$paytoname,$tobankname,$toaccountno,$tobankifsc,$authirzedprojects,$loginid,$paytoid);
					if(count($ssmppy)>0){
						foreach ($ssmppy as $ssmppykey) {
							if($ssmppy['status']=="Error"){
								$unreassprpmsg = array('status' => 'Error', 'message' =>$ssmppy['message']);
						        echo json_encode($unreassprpmsg);
						        exit();
							}
						}
					}
				}
			}else{
				$unreassprpmsg = array('status' => 'Error', 'message' => 'Payto name is not available in the bank account master, so you cannot reconstruct as ' . $purpose);
		        echo json_encode($unreassprpmsg);
		        exit();
			}
		}
	}*/

	$loginname = $_POST['loginname'];
	$unknowbankndata = json_decode($_POST['unknowbankndata'],TRUE);
	$unreassprpmsg = array('status' => 'Error', 'message' => 'No data to process.');

	if (count($unknowbankndata) > 0 && $purpose_categoryid) {
	    $conn->begin_transaction();
	    try {
	        $invoicedata = array();
	        $voucherinfo = array();

	        foreach ($unknowbankndata as $asspkey) {
	            $voucherinfo[] = array(
	                'beneficiaryname' => $beneficiaryname,
	                'expenseamount' => $asspkey['unbankamount'],
	                'invoicedate' => '',
	                'invoiceno' => '',
	                'invoicetype' => '',
	                'description' => $asspkey['unbankdescription'],
	                'purposecode' => $purposecode,
	                'purposename' => $purpose,
	                'attachmentpath' => '',
	                'audiofilepath' => '',
	                'voiceinstid' => '',
	                'docsno' => '',
	                'srm_attsno' => '',
	                'srm_emailsno' => '',
	                'invoicedata' => $invoicedata,
	                'totalrowsamount' => 0,
	                'totalgstamount' => 0,
	                'totalrowsdiscount' => 0,
	                'otherdiscount' => 0,
	                'totalpayableamount' => 0,
	                'taxableamount' => $asspkey['unbankamount'],
	                'invoicegstamount' => 0,
	                'ponumber' => ''
	            );

	            if ($paytoid && $asspkey['unbankamount'] > 0 && $asspkey['untransactionno'] && $asspkey['untransactiondate'] && $asspkey['unraccountid'] && $purposecode && $asspkey['unbankdescription']) {
	                $rrs = recunstructbook_credit($authconn,$conn, $companyid, $subcompanyid, $projectid, $subprojectid, $asspkey['unraccountid'], "online", $asspkey['untransactionno'], $asspkey['untransactiondate'], $asspkey['unbankamount'], $paytoid, $voucherinfo, $loginname, $purpose_categoryid, $purpose_subcategoryid, $purposecode);

	                if (count($rrs) > 0) {
	                    foreach ($rrs as $rrskey) {
	                        reconciled_updatebankstatment_closetobook_credit($conn, $rrskey['referenceno'], $asspkey['untransactiondate'], $asspkey['undbsno'], $rrskey['referenceno'], "unknowreconstruction");
	                        if ($rrskey['status'] == "Success" && $rrskey['referenceno']) {
	                            verifiedpaytoandpurposetobookdata_credit($conn, $companyid, $subcompanyid, $projectid, $rrskey['referenceno'], "yes", "yes", $loginname);
	                        }
	                    }
	                }
	            }

	            $voucherinfo = array();
	        }

	        $conn->commit();
	        $unreassprpmsg = array('status' => 'Success', 'message' => 'Update and verified payto and purpose successfully');
	    } catch (Exception $e) {
	        $conn->rollback();
	        $unreassprpmsg = array('status' => 'Error', 'message' => $e->getMessage());
	    }
	}
	// header('Content-Type: application/json');
	echo json_encode($unreassprpmsg);
}else if($action=="sendinvoicedocument") {
	$companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $emailaddress = $_POST['emailaddress'];
    $invoiceno = $_POST['invoiceno'];
    $username = $_POST['username'];
    $rooturl= $_POST['rooturl'];
    $pdffilepath="";
    $qry=$conn->query("Select invoicepath from tbl_invoices where invoice_no='".$invoiceno."'");
    if ($qry->num_rows>0) {
    	$rowpdf=$qry->fetch_assoc();
    	$pdffilepath=$rowpdf['invoicepath'];
    }


   
    $m_subject="Invoice No.".$invoiceno;
    $weblink="";
   
    
    	$emailid[]=$emailaddress;
    	$ccemail[]="";
    	$bccemail[]="";
    	if($emailid){
				
				$m_msgbody = "<p>Dear ".$username." ,</p><br>Please find the  attachment of invoice  ".$invoiceno.".<p>";
				
			}
    	
	$pdffilepath=array("../".$pdffilepath);	
	


     $response=sendemailfunctionwithattachment($conn,$emailaddress,$m_subject,$m_msgbody,$pdffilepath);

  

   
    if($response){ 
    	$unreassprpmsg = array('status' => 'Success', 'message' => 'Query Sent Successfully');
    }else{
    	$unreassprpmsg = array('status' => 'Error', 'message' => 'Error');
    }
    echo json_encode($unreassprpmsg);
 
}else if($action=="createbookfromexpensedataT3"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	// parse subproject
	$subj=subprojectdetails($conn,$companyid,$subcompanyid,$projectid,"all","");
	$subprojectid="";
	if(count($subj)==1){
		foreach ($subj as $subjkey) {
			$subprojectid = $subjkey['subprojectid'];
		}
	}
	$expensedatadbsnoinfo = json_decode($_POST['expensedatadbsno'],TRUE);
	$unreassprpmsg=array();
	if(count($expensedatadbsnoinfo)>0){
		$parsedate="";
		$parsepayto="";
		$parseamount="";
		$parsepurpose="";
		$parsedescription="";
		$successentrycount=0;
		foreach ($expensedatadbsnoinfo as $asspkey) {
			// $asspkey['dbsno']=382;
			$extd=getexpensesdataforhelpmakebook($conn,$companyid,$subcompanyid,$projectid,"sno",$asspkey['dbsno']);
			$paytoid ="";
			$paytoname ="";
			if(count($extd)>0){
				foreach ($extd as $extdkey) {
					// check payto exist or not
					$prpin=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoid',$extdkey['paytoid']);
					$parsepayto="";
					$paytobankname ="";
					$paytoaccountno ="";
					$paytoaccountid ="";
					if($extdkey['paytoid']){
						if(count($prpin)>0){
							foreach ($prpin as $ppkkey) {
								$paytoname = $ppkkey['paytoname'];
								$paytobankname = $ppkkey['tobankname'];
								$paytoaccountno = $ppkkey['toaccountno'];
								$paytoifsc = $ppkkey['tobankifsc'];
							}
							if($paytoname){
								$parsepayto=1;
							}
						}
					}else{
						continue;
					}
					// check valid amount or not
					$parseamount ="";
					if(formatToFloat($extdkey['total_amount'])>0 && $extdkey['total_amount']){
						$parseamount = 1;
					}
					// parse date
					$parsedate ="";
					if($extdkey['date']){
						if(isValidDate(date('Y-m-d', strtotime($extdkey['date'])), 'Y-m-d')==1){
							$parsedate = 1;
						}else{
							$parsedate = '0';
						}
					}
					// parse purpose
					$prpin=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$extdkey['purpose_code']);
					$parsepurpose="";
					if(count($prpin)>0 && $extdkey['purpose_code']){
						$parsepurpose=1;
						foreach ($prpin as $ppkkey) {
							$purpose_category = $ppkkey['pcategory'];
							$purpose_categoryid = $ppkkey['pcategorycode'];
							$purpose_subcategory = $ppkkey['psub_category'];
							$purpose_subcategoryid = $ppkkey['psub_categoryid'];
							$purpose = $ppkkey['purpose'];
							$purpose_code = $ppkkey['purposecode'];
							$provided_code = $ppkkey['provided_code'];
						}
					}else{
						continue;
					}

					if($purpose_categoryid==7){
						$paytonameexp = explode("-", $paytoname);
						$tobnkname = $paytonameexp[1];
						if($tobnkname){
							$pfromac=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"bankname",$tobnkname);
							if(count($pfromac)==1){
								foreach ($pfromac as $pfrkey) {
									$paytobankname = $pfrkey['bankname'];
									$paytoaccountno = $pfrkey['accountno'];
									$paytoaccountid = $pfrkey['accountid'];			
								}
							}else{
								// continue;
							}
						}
					}

					// parse description
					$parsedescription="";
					if(strlen($extdkey['description'])>10 && $extdkey['description']){
						$parsedescription=1;
					}
					// payfrom account details
					$payfrombankname = ""; 
					$payfromaccountno = "";
					$payfromaccountid = "";
					if($extdkey['account_id']){
						$pfromac=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$extdkey['account_id']);
						if(count($pfromac)>0){
							foreach ($pfromac as $pfrkey) {
								$payfrombankname = $pfrkey['bankname'];
								$payfromaccountno = $pfrkey['accountno'];
								$payfromaccountid = $pfrkey['accountid'];			
							}
						}
					}

					$beneficiaryname="";
					if(strlen($extdkey['beneficiaryname'])>2){
						$beneficiaryname=$extdkey['beneficiaryname'];
					}else{
						$beneficiaryname=$paytoname;
					}

					$finyeartype = getfinancialyeartype($conn,$companyid);
					$fin_year = getFinancialYear($extdkey['date'],$finyeartype);
					if($fin_year){
					}else{
						$fin_year="";
					}
					// echo "==".$asspkey['dbsno'].":: ".isValidDate(date('Y-m-d', strtotime($extdkey['date'])), 'Y-m-d');
					// echo  "==".$asspkey['dbsno'].":: date: ".$parsedate.'-'. $extdkey['date']." payto=".$parsepayto." amount=".$parseamount." purpose=".$parsepurpose." description=".$parsedescription;
					
					if($parsedate==1 && $parsepayto==1 && $parseamount==1 && $parsepurpose==1 && $parsedescription==1){
						$successentrycount++;
						$tsno=GetMaxRefTempSno($conn);
						$newreference_no = getnewreferenceno($conn,$companyid,$subcompanyid,$projectid,$fromaccountno);

						$sourcedata="reconsutruc from expese data(t3) on date ".date('Y-m-d h:is')." by ".$loginname;

						$beneficerytype="Individual";
						$expdatasno=$asspkey['dbsno'];

						$insq="Insert into tbl_maintransaction_breakdown(companyid, subcompanyid, projectid, subprojectid, reference_no, beneficiary_name, amount, purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, provided_code, description, voiceinstid, documentation_path) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$newreference_no."','".addslashes($beneficiaryname)."','".addslashes($extdkey['total_amount'])."','".addslashes($purpose_categoryid)."','".addslashes($purpose_category)."','".addslashes($purpose_subcategoryid)."','".addslashes($purpose_subcategory)."','".addslashes($purpose)."','".addslashes($purpose_code)."','".addslashes($provided_code)."','".addslashes($extdkey['description'])."','".addslashes($extdkey['voiceinstid'])."','".addslashes($extdkey['filepath'])."')";
						$conn->query($insq);
						if(mysqli_affected_rows($conn)>0){
							$conn->query("Insert into tbl_maintransaction(tsno, companyid, subcompanyid, projectid, subprojectid, reference_no,transactionno,transaction_date, bookdate,finyear, book_amount, beforeedit_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, paytoaccountid, book_source) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$subprojectid."', '".addslashes($newreference_no)."','".addslashes($extdkey['chequeno'])."', '".$extdkey['date']."', '".$extdkey['date']."', '".addslashes($fin_year)."','".addslashes($extdkey['total_amount'])."', '".addslashes($extdkey['total_amount'])."', 'online', '".addslashes($paytoname)."', '".addslashes($payfrombankname)."', '".addslashes($payfromaccountno)."', '".addslashes($payfromaccountid)."', '".addslashes($purpose_category)."', '".addslashes($beneficerytype)."', '".addslashes($paytoaccountno)."', '".addslashes($paytobankname)."', '".addslashes($paytoifsc)."', '".$paytoaccountid."', '".addslashes($sourcedata)."')");
							if(mysqli_affected_rows($conn)>0){
								$conn->query("Update tbl_expencesinexcelsheet set status='".date('Y-m-d')."', closetobook_status='".$newreference_no."' where sno in (".$expdatasno.")");
							}
						}
					}
				}
			}
		}
		if($successentrycount==count($expensedatadbsnoinfo)){
			$unreassprpmsg=array('status'=>'Success', 'message'=>'Book created successfully of data.');
		}else if($successentrycount>0){
			$unreassprpmsg=array('status'=>'Success', 'message'=>'Book created successfully, rest of entries have some missing details =='.$successentrycount);
		}else{
			$unreassprpmsg=array('status'=>'Error', 'message'=>'Book did not create, due to some detail are missing/wrong --'.$successentrycount);
		}
	}else{
		$unreassprpmsg=array('status'=>'Error', 'message'=>'No data available for creating book');
	}
	echo json_encode($unreassprpmsg);
}else if($action=="getunreconcilieddatafrombook"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
	$shortbyvalue = $accountid."|".$datefrom."|".$dateto;
	// $pdqr=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"unreconcilieddata_accountid_withdates",$shortbyvalue);
	$pdqr=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"unreconcilieddata_withaccountid",$accountid);
	$unrecodata=array();
	if(count($pdqr)>0){
		foreach ($pdqr as $pdqrkey) {
			$refbrk=get_maintransactionsbreakdown_details($conn,$companyid,$subcompanyid,$projectid,"referenceno",$pdqrkey['reference_no']);
			$beneficiaryname = "";
			$purposename = "";
			$providedcode = "";
			$description = "";
			$descriptionbank = "";
			if(count($refbrk)>0){
				if(count($refbrk)==1){
					foreach ($refbrk as $ffkey) {
						$beneficiaryname = $ffkey['beneficiary_name'];
						$purposename = $ffkey['purpose'];
						$providedcode = $ffkey['provided_code'];
						$description = $ffkey['description'];
						$descriptionbank = $ffkey['description_bank']!=""?$ffkey['description_bank']:$ffkey['description'];
					}
				}else if(count($refbrk)>1){
					$beneficiaryname = "Multiple";
					$purposename = "Multiple";
					$providedcode = "Multiple";
					$description = "Multiple";
					$descriptionbank = "Multiple";
				}
			}
			$transactiondate = "";
			if($pdqrkey['transaction_date']){
				$transactiondate = date('d-m-Y', strtotime($pdqrkey['transaction_date']));
			}
			$dispbookdate = "";
			if($pdqrkey['bookdate']){
				$dispbookdate = date('d-m-Y', strtotime($pdqrkey['bookdate']));
			}
			$unrecodata[]=array('msno'=>$pdqrkey['sno'], 'referenceno'=>$pdqrkey['reference_no'], 'paytoid'=>$pdqrkey['paytoid'], 'payto'=>$pdqrkey['payto'], 'bookdate'=>$pdqrkey['bookdate'], 'dispbookdate'=>$dispbookdate, 'bookamount'=>$pdqrkey['book_amount'], 'transactionno'=>$pdqrkey['transactionno'], 'transactiondate'=>$transactiondate, 'transferedamount'=>$pdqrkey['transfered_amount']>0?$pdqrkey['transfered_amount']:$pdqrkey['book_amount'],'beneficiaryname'=>$beneficiaryname,'purposename'=>$purposename,'providedcode'=>$providedcode,'description'=>$description,'descriptionbank'=>$descriptionbank,'refbreakdataarray'=>$refbrk);
		}
	}
	echo json_encode($unrecodata);
}else if($action=="reconciliationmarkedtobook_manual"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$loginname=$_POST['loginname'];
	$bankdatadbsno = json_decode($_POST['bankdatadbsno'], true);
	$bookdatadbsno = json_decode($_POST['bookdatadbsno'], true);

	// payfrom bank details
	$pfb=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
	$prf_paytoid="";
	$prf_paytoname="";
	if(count($pfb)>0){
		foreach ($pfb as $pfbkey) {
			$prf_paytoid=$pfbkey['paytoid'];
			$prf_paytoname=$pfbkey['paytoname'];
		}
	}
	$bnkchargepurpose=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposename","Bank Charge");
	$bankchargepurpose="";
	if(count($bnkchargepurpose)>0){
		foreach ($bnkchargepurpose as $bbkkey) {
			$bankchargepurpose=$bbkkey['purposecode'];
		}
	}
	$returnmsg=array();
	$clb_valuedate = "";
	$clb_chequeno = "";
	$clb_accountid = "";
	$banktotalamount=0;
	$descriptionbank ="";
	$banksno = "";
	$diffamount =0;
	$booksnofirst="";
	$subprojectid ="";

	if(count($bankdatadbsno)==1 && count($bookdatadbsno)>0){
		if(count($bankdatadbsno)==1 && count($bookdatadbsno)==1){
			foreach ($bankdatadbsno as $bnkkey) {
				// check bank data
				$clbookinfo=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,"sno",$bnkkey['banksno']);
				$banksno=$bnkkey['banksno'];
				if(count($clbookinfo)>0){
					$chkbank=1;
					foreach ($clbookinfo as $bbkkey) {
						$banktotalamount = $bbkkey['total_amount']??0;
						$clb_valuedate = $bbkkey['value_date']??'';
						$clb_chequeno = $bbkkey['chequeno']??'autodeduct';
						$clb_accountid = $bbkkey['account_id'];
						$descriptionbank = $bbkkey['description_bank'];
					}
				}else{
					$returnmsg=array('status'=>'Error', 'message'=>'bank statement details did not get, due to somthing wrong try again');
				}
			}

			// check bookdata
			$chkbook=0;
			$referenceno ="";
			foreach ($bookdatadbsno as $bokkey) {
				$mnq=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"sno",$bokkey['booksno']);
				$booksno=$bokkey['booksno'];
				if(count($mnq)>0){
					$chkbook=1;
					foreach ($mnq as $mnqkey) {
						$referenceno = $mnqkey['reference_no'];
						$subprojectid = $mnqkey['subprojectid'];
						$paytoid=$mnqkey['paytoid'];
						$paytoname=$mnqkey['payto'];
						$listtransactionno=$mnqkey['transactionno'];
						$ttlmaintransactionamount=$mnqkey['transfered_amount'];
					}					
				}else{
					$returnmsg=array('status'=>'Error', 'message'=>'Book details did not available or somthing wrong try again');
				}
			}
			if($chkbank==1 && $chkbook==1){
				$type="Manual reconciliation by ".$loginname." of reference no ".$referenceno;
				$rrc=reconciled_updatebankstatment_closetobook($conn,$referenceno,$clb_valuedate,$banksno,$booksno,$type);
				if($rrc['status']=="Success"){
					// autdeduction
					// echo $banktotalamount."---".$ttlmaintransactionamount;
					$diffamount = floatval($banktotalamount) - floatval($ttlmaintransactionamount);
					// echo "dd: ".$diffamount;
					if(floatval($diffamount)>0){
						$description="Auto detduction by ".$loginname." of reference no ".$referenceno;
						$sssp=InsertAutodeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$clb_accountid,"online",$clb_chequeno,$clb_valuedate,$diffamount,$prf_paytoid,$prf_paytoname,$prf_paytoname,$bankchargepurpose,$description,$descriptionbank,$diffamount,"0","Done",$banksno,$referenceno,$loginname);
					}
					$returnmsg=array('status'=>'Success', 'message'=>'Reconciliation process done successfully');
				}else{
					$returnmsg=array('status'=>'Error', 'message'=>'Reconciliation process failed, due to technical issue');
				}
			}else{
				$returnmsg=array('status'=>'Error', 'message'=>'Something missing  required details');
			}
		}else{
			if(count($bookdatadbsno)>1){
				// bank details
				foreach ($bankdatadbsno as $bnkkey) {
					$clbookinfo=closetobookdetails($conn,$companyid,$subcompanyid,$projectid,$transactiontype,"sno",$bnkkey['banksno']);
					$banksno=$bnkkey['banksno'];
					if(count($clbookinfo)>0){
						foreach ($clbookinfo as $bbkkey) {
							$banktotalamount = $bbkkey['total_amount'];
							$clb_valuedate = $bbkkey['value_date'];
							$clb_chequeno = $bbkkey['chequeno'];
							$clb_accountid = $bbkkey['account_id'];
							$descriptionbank = $bbkkey['description_bank'];
						}
					}
				}
				$listofpaytoid=[];
				$listtransactionno=[];
				$ttlmaintransactionamount=0;
				$paytoid="";
				$paytoname="";
				foreach ($bookdatadbsno as $mbokkey) {
					$mmnq=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"sno",$mbokkey['booksno']);
					// print_r($mmnq);
					foreach ($mmnq as $mmkey) {
						$subprojectid = $mmkey['subprojectid'];
						$paytoid=$mmkey['paytoid'];
						$paytoname=$mmkey['payto'];
						$listofpaytoid[]=$mmkey['paytoid'];
						$listtransactionno[]=$mmkey['transactionno'];
						$ttlmaintransactionamount=$ttlmaintransactionamount + $mmkey['book_amount'];
					}
				}
				// multiple entry with same payto and transaction no
				if (count(array_unique($listofpaytoid)) === 1 && count(array_unique($listtransactionno)) === 1) {
			    	$diffamount = floatval($banktotalamount) - floatval($ttlmaintransactionamount);
			    	$ii=0;
					$requestnofirst="";
					$referencenofirst="";
					foreach ($bookdatadbsno as $mbokkey) {
						$mmnq=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"sno",$mbokkey['booksno']);
						foreach ($mmnq as $mmkey) {
							if($ii==0){
								$booksnofirst=$mmkey['sno'];
								$requestnofirst=$mmkey['request_no'];
								$referencenofirst=$mmkey['reference_no'];
								$updatemain=$conn->query("UPDATE tbl_maintransaction SET book_amount='".$ttlmaintransactionamount."',transfered_amount='".$ttlmaintransactionamount."',beneficierie='Multiple' where sno='".$mmkey['sno']."'");
							}else{
								$tremark="entry murge with other same transaction no entry by ".$loginname;
								$updatemain=$conn->query("UPDATE tbl_maintransaction SET tedit='".date('Y-m-d')."',tedit_remark='".$tremark."' where sno='".$mmkey['sno']."'");
								$updatemain=$conn->query("UPDATE tbl_maintransaction_breakdown SET request_no='".$requestnofirst."',reference_no='".$referencenofirst."' where reference_no='".$mmkey['reference_no']."'");
							}
							$ii++;
						}
					}
					if($banksno && $booksnofirst){
						$type="Manual reconciliation by ".$loginname;
						$rrc=reconciled_updatebankstatment_closetobook($conn,$referencenofirst,$clb_valuedate,$banksno,$booksnofirst,$type);
						if($rrc['status']=="Success"){
							// autdeduction
							if(floatval($diffamount)>0){
								$description="Auto detduction of ".$referencenofirst;
								InsertAutodeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$clb_accountid,"online",$clb_chequeno,$clb_valuedate,$diffamount,$prf_paytoid,$prf_paytoname,$prf_paytoname,$bankchargepurpose,$description,$descriptionbank,$diffamount,"0","Done",$banksno,$referencenofirst,$loginname);
							}
							$returnmsg=array('status'=>'Success', 'message'=>'Reconciliation process done successfully');
						}else{
							$returnmsg=array('status'=>'Error', 'message'=>'Reconciliation process failed, due to technical issue');
						}
					}else{
						$returnmsg=array('status'=>'Error', 'message'=>'Reconciliation process failed, due to technical issue for multiple entries');
					}
				} else {
					if (count(array_unique($listofpaytoid)) === 1){
						$diffamount = floatval($banktotalamount) - floatval($ttlmaintransactionamount);
						// multiple entry with diffrent payto or transaction no in book
						$referenceno ="";
						foreach ($bookdatadbsno as $mbokkey) {
							$mmnq=get_maintransactions_details($conn,$companyid,$subcompanyid,$projectid,"sno",$mbokkey['booksno']);
							$meu=0;
							foreach ($mmnq as $mmkey) {
								$type="Manual reconciliation by ".$loginname;
								$referenceno = $mmkey['reference_no'];
								$conn->query("UPDATE tbl_maintransaction SET reconciliation='Done',reconciliation_banksno='".$banksno."', value_date='".$clb_valuedate."', reconcilation_process= '".$type."' where sno='".$mmkey['sno']."' and (tedit is null or tedit='')");
								if(mysqli_affected_rows($conn)>0){
									$meu=1;
								}
							}
							if($meu==1){
								$conn->query("Update tbl_closetobook set status='Done', reconciliation='Done',book_status='completed', actule_referenceno='multiple',reference_no='multiple' where sno='".$banksno."'");
								if(mysqli_affected_rows($conn)>0){
									if(floatval($diffamount)>0){
										$description="Auto detduction with multiple entry with old reference no ".$referenceno;
										InsertAutodeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$clb_accountid,"online",$clb_chequeno,$clb_valuedate,$diffamount,$prf_paytoid,$prf_paytoname,$prf_paytoname,$bankchargepurpose,$description,$descriptionbank,$diffamount,"0","Done",$banksno,$referenceno,$loginname);
									}
								}
								$returnmsg=array('status'=>'Success', 'message'=>'Reconciliation process done successfully');
							}else{
								$returnmsg=array('status'=>'Error', 'message'=>'updatation failed in multiple payto reconciliation.');
							}
						}
					}else{
						$returnmsg=array('status'=>'Error', 'message'=>'multiple payto not allowed in single entry bank reconciliation.');
					}
				}
			}else{
				$returnmsg=array('status'=>'Error', 'message'=>'Not Multiple entry '.count($bookdatadbsno).' available in book');
			}
		}
	}else if(count($bankdatadbsno)>1 && count($bookdatadbsno)==1){
		$type="Manual reconciliation by ".$loginname;
		$rrc=reconciled_multiplebankentry_withonebookentury($conn,$companyid,$subcompanyid,$projectid,$bankdatadbsno,$bookdatadbsno,$type);
		if($rrc['status']=="Success"){
			// autdeduction
			$diffamount = floatval($rrc['transferedamount']) - floatval($rrc['bookamount']);
			if(floatval($diffamount)>0){
				$description="Auto detduction from reference no ".$rrc['referenceno'];
				$autddd=InsertAutodeduction($conn,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,"online",$rrc['transactionno'],$rrc['transactiondate'],$diffamount,$prf_paytoid,$prf_paytoname,$prf_paytoname,$bankchargepurpose,$description,'multiple bank description',$diffamount,"0","Done",$rrc['banksno'],$rrc['referenceno'],$loginname);
				// print_r($autddd);
			}
			$returnmsg=array('status'=>'Success', 'message'=>$rrc['message']);
		}else{
			$returnmsg=array('status'=>'Error', 'message'=>$rrc['message']);
		}
	}else{
		if(count($bankdatadbsno)<1){
			$returnmsg=array('status'=>'Error', 'message'=>'Bank entry selection is missing');
		}else if(count($bookdatadbsno)<1){
			$returnmsg=array('status'=>'Error', 'message'=>'Book entry selection is missing');
		}else{
			$returnmsg=array('status'=>'Error', 'message'=>'Something wrong of selection');
		}
	}
	echo json_encode($returnmsg);	
}else if($action=="reconciliationmarkedtobook_automatic"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$loginname=$_POST['loginname'];
	$processtype=$_POST['processtype'];
	$datefrom=$_POST['datefrom'];
	$dateto=$_POST['dateto'];
	$resultmsg=array();
	$reccount=0;
	if($processtype=="p1"){
		// date,transaction no and amount
		if(strtolower($accountid)=="all"){
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
		}else{
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
		}
		// print_r($accc);
		if(count($accc)>0){
			foreach ($accc as $accckey) {
				$reccount=reconcilation_condtion_1($conn,$companyid,$subcompanyid,$projectid,$accckey['accountid'],20,$datefrom,$dateto);
				// echo "cct: ".$reccount.'=='.$accckey['accountno'];
				if ($reccount == 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 1');
				} elseif ($reccount > 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 1');
				} else {
				    $resultmsg[] = array('status' => 'Error', 'message' => "No entries have been reconciled for the ".$accckey['accountno']." (".$accckey['bankname'].")  with prcess 1");
				}
			}
		}
	}else if($processtype=="p2"){
		// date,transaction no and amount
		// $accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
		if(strtolower($accountid)=="all"){
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
		}else{
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
		}
		if(count($accc)>0){
			foreach ($accc as $accckey) {
				$reccount=reconcilation_condtion_2($conn,$companyid,$subcompanyid,$projectid,$accckey['accountid'],20,$datefrom,$dateto);
				if ($reccount == 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 2');
				} elseif ($reccount > 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 2');
				} else {
				    $resultmsg[] = array('status' => 'Error', 'message' => "No entries have been reconciled for the ".$accckey['accountno']." (".$accckey['bankname'].")  with prcess 2");
				}
			}
		}
		// $resultmsg = array('status' => 'Success', 'message' => $msg);
	}else if($processtype=="p3"){
		// date,transaction no and amount
		// $accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
		// echo "account id: ".$accountid;
		if(strtolower($accountid)=="all"){
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
		}else{
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
		}
		if(count($accc)>0){
			foreach ($accc as $accckey) {
				$reccount=reconcilation_condtion_3($conn,$companyid,$subcompanyid,$projectid,$accckey['accountid'],20,$datefrom,$dateto);
				// print_r($reccount);

				if ($reccount == 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 3');
				} elseif ($reccount > 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 3');
				} else {
				    $resultmsg[] = array('status' => 'Error', 'message' => "No entries have been reconciled for the ".$accckey['accountno']." (".$accckey['bankname'].")  with prcess 3");
				}
			}
		}
		// $resultmsg = array('status' => 'Success', 'message' => $msg);
	}else if($processtype=="p4"){
		// date,transaction no and amount
		// $accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
		if(strtolower($accountid)=="all"){
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
		}else{
			$accc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
		}
		if(count($accc)>0){
			foreach ($accc as $accckey) {
				$reccount=reconcilation_condtion_4($conn,$companyid,$subcompanyid,$projectid,$accckey['accountid'],20,$datefrom,$dateto);
				// echo "cct: ".$reccount.':: '.$accckey['accountno'];
				if ($reccount == 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 4');
				} elseif ($reccount > 1) {
				    $resultmsg[] = array('status' => 'Success', 'message' => $reccount . ' Entries have been successfully reconciled for the '.$accckey['accountno'].' ('.$accckey['bankname'].')  with prcess 4');
				} else {
				    $resultmsg[] = array('status' => 'Error', 'message' => "No entries have been reconciled for the ".$accckey['accountno']." (".$accckey['bankname'].")  with prcess 4");
				}
			}
		}
		// $resultmsg = array('status' => 'Success', 'message' => $msg);
	}else{
		$resultmsg[] = array('status' => 'Error', 'message' => 'Your process type did not defined.');
	}
	echo json_encode($resultmsg);
}else if($action=="undegitizedfilesentry"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$dddt=getSyncedFilesByCondition($conn, $companyid, $subcompanyid, $projectid, 'undigitized', '0');
	echo json_encode($dddt);
}
?>