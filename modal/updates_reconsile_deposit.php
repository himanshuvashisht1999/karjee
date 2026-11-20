<?php 
require('../connection.php');
require('../functions/function_master.php');
require('../loginCell/functions/login_functions.php'); 
$action=$_GET['action']; 

if ($action=="updatepurpose_depositsinexcelsheet") {
	$selectedsnos = $_POST['selectedsnos'];
	$categoryid = $_POST['categoryid'];
	$category = $_POST['category'];
	$subcategoryid = $_POST['subcategoryid'];
	$subcategory = $_POST['subcategory'];
	$providedcode = $_POST['providedcode'];
	$purposecode = $_POST['purposecode'];
	$purpose = $_POST['purpose'];
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	$mconn->begin_transaction();
	$rst=array();
	try{	 
		$conn->query("update tbl_depositsinexcelsheet set category='".$category."',categoryid='".$categoryid."', sub_categoryid='".$subcategoryid."' ,sub_category='".$subcategory."',purpose='".$purpose."',purpose_code='".$purposecode."' ,provided_code='".$providedcode."' where sno in (".$selectedsnos.")");
		 $conn->commit();
		$rst=array('status'=>'Success', 'message'=>"Purpose Replaced Successfully");
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$rst=array('status'=>'Error', 'message'=>$e->getMessage());
	}
	echo json_encode($rst);

} else if ($action=="updatepayeename_deposit") {
	$selectedsnos = $_POST['selectedsnos']; 
	$client_name = $_POST['clientname'];
	$clientid = $_POST['clientid'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid']; 

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$mconn->begin_transaction();
	$rst=array();
	try{	  
		$conn->query("update tbl_depositsinexcelsheet set paytoid='".$clientid."' where sno in (".$selectedsnos.")");
		 $conn->commit();		
		$rst=array('status'=>'Success', 'message'=>"Payer Name Replaced Successfully"); 
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$rst=array('status'=>'Error', 'message'=>$e->getMessage());
	}
	echo json_encode($rst);

}else if ($action=="updatepurpose_customisedcomparison1") {
	$selectedsnos = $_POST['selectedsnos'];
	$categoryid = $_POST['categoryid'];
	$category = $_POST['category'];
	$subcategoryid = $_POST['subcategoryid'];
	$subcategory = $_POST['subcategory'];
	$providedcode = $_POST['providedcode'];
	$purposecode = $_POST['purposecode'];
	$purpose = $_POST['purpose'];
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	$mconn->begin_transaction();
	$rst=array();
	try{	 
		$conn->query("update tbl_overlap_depositsxlsheet set category='".$category."',categoryid='".$categoryid."', sub_categoryid='".$subcategoryid."' ,sub_category='".$subcategory."',purpose='".$purpose."',purpose_code='".$purposecode."' ,provided_code='".$providedcode."' where id in (".$selectedsnos.")");
		 $conn->commit();
		$rst=array('status'=>'Success', 'message'=>"Purpose Replaced Successfully");
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$rst=array('status'=>'Error', 'message'=>$e->getMessage());
	}
	echo json_encode($rst);

} else if ($action=="updatepayeename_customisedcomparison1") {
	$selectedsnos = $_POST['selectedsnos']; 
	$client_name = $_POST['clientname'];
	$clientid = $_POST['clientid'];
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid']; 

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$mconn->begin_transaction();
	$rst=array();
	try{	  
		$conn->query("update tbl_overlap_depositsxlsheet set paytoid='".$clientid."' where sno in (".$selectedsnos.")");
		 $conn->commit();		
		$rst=array('status'=>'Success', 'message'=>"Payer Name Replaced Successfully"); 
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$rst=array('status'=>'Error', 'message'=>$e->getMessage());
	}
	echo json_encode($rst);

}else if ($action=="updte_add_cust_comparison") { 
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid']; 	
	$expensedatadbsnoinfo = isset($_POST['expensedatadbsno']) ? json_decode($_POST['expensedatadbsno'], true) : [];

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	$mconn->begin_transaction();
	$rst=array();
	try{	
		foreach ($expensedatadbsnoinfo as $asspkey) {
			$dbsno = $asspkey['dbsno'] ?? null;
			$payeename = $asspkey['payeename'] ?? null;
			$_clid=addtoclientmaster($conn,$companyid,$subcompanyid,$projectid,$payeename);
			if($_clid){
				$conn->query("update tbl_overlap_depositsxlsheet set paytoid='".$_clid."' where sno in (".$dbsno.")");

			}

		 $conn->commit();		
		} 
		$rst=array('status'=>'Success', 'message'=>"Payer Name Replaced Successfully"); 
	}
	catch (mysqli_sql_exception $e) {
    	$conn->rollback();
    	$rst=array('status'=>'Error', 'message'=>$e->getMessage());
	}
	echo json_encode($rst);

}else if($action=="updte_allpurpose_cust_comparison"){
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

			 	}
			 	$conn->query("update tbl_overlap_depositsxlsheet set category='".$category."',categoryid='".$categoryid."', sub_categoryid='".$subcategoryid."' ,sub_category='".$subcategory."',purpose='".$purpose."',purpose_code='".$purposecode."' ,provided_code='".$providedcode."' where sno in (".$dbsno.")");
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
}else if($action=="tbl_creditadvice_c1"){
	$companyid = $_POST['companyid'] ?? null;
	$subcompanyid = $_POST['subcompanyid'] ?? null;
	$projectid = $_POST['projectid'] ?? null;
	  $loginname = $_POST['loginname'] ?? null;


	$actionfrom = $_POST['actionfrom'] ?? null;
	$expensedatadbsnoinfo = isset($_POST['expensedatadbsno']) ? json_decode($_POST['expensedatadbsno'], true) : [];

	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); 
	try { 
		foreach ($expensedatadbsnoinfo as $asspkey) {
			$trans_date = date('Y-m-d', strtotime($asspkey['date']));			 
			 $sql= "insert into tbl_creditadvice_c1 (id ,companyid ,subcompanyid ,projectid ,amount ,transactionno ,transactiondate ,clientid ,purposecode ,creditamount ,clienttype ,bankreceivedamt ,deposittype ,actualpayeeid,actionfrom,vatamount,invoiceno ,remarks) select COALESCE(MAX(id), 0) + 1 AS id, '".$companyid."' ,'".$subcompanyid."' ,'".$projectid."' ,'".$asspkey['amountwithouttax']."','".$asspkey['dbsno']."','".$trans_date."','".$asspkey['clientid']."' ,'".$asspkey['purposecode']."'  ,'".$asspkey['totalamount']."' ,'Direct','".$asspkey['totalamount']."','Deposit','".$asspkey['clientid']."','".$actionfrom."' ,'".$asspkey['tax']."' ,'".$asspkey['invoiceno']."' ,'".addslashes($asspkey['description'])."' from tbl_creditadvice_c1";	 
			$conn->query($sql);
			if($actionfrom=="sale invoice"){				
				$conn->query("update tbl_overlap_depositsxlsheet set status='done'  where id in (".$asspkey['dbsno'].")"); 

			}else{			
			$conn->query("update tbl_depositsinexcelsheet set status='done'  where sno in (".$asspkey['dbsno'].")"); 

			}

		}
		$conn->commit();
	  $unreassprpmsg = array('status' => 'Success', 'message' => 'Response Saved  Successfully');
		echo json_encode($unreassprpmsg);
	} catch (Exception $e) {
	    // Rollback transaction on error
	    $conn->rollback();
	    $unreassprpmsg = ['status' => 'Error', 'message' => $e->getMessage()];

    	echo json_encode($unreassprpmsg);
	}


}
 
function chkdataexits($tablename,$returnfld, $fldname, $txtstring, $conn,$condqry="1=1")
{
  $txtstring = mysqli_real_escape_string($conn, $txtstring); 
   $qry = "SELECT $returnfld AS returnval FROM $tablename WHERE TRIM(REPLACE(REPLACE(REPLACE(REPLACE($fldname, '  ', ' '), ',', ''), '.', ''), '-', '')) = TRIM(REPLACE(REPLACE(REPLACE(REPLACE('$txtstring', '  ', ' '), ',', ''), '.', ''), '-', ''))   AND ". $condqry  ; 
 
	$resq = $conn->query($qry);
	if($resq->num_rows>0){
		$row = $resq->fetch_assoc();
	  return $row['returnval'];   
	} else { 
	    return "";   
	} 
}

function maxclient_id($conn){
	$clientid="";
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

	return $clientid;
} 
function addtoclientmaster($conn,$companyid,$subcompanyid,$projectid,$stringclient_name){
	$conqry=" companyid ='".$companyid."' and subcompanyid ='".$subcompanyid."' and projectid = '".$projectid."' and ifnull(tedit,'')=''" ;
		$clientid=chkdataexits("client_master","client_id", "client_name", $stringclient_name, $conn, $conqry);
		if(!$clientid){
				$clientid=maxclient_id($conn);
				$insert = "INSERT into client_master (companyid,subcompanyid,projectid,client_id, client_name ) VALUES('".$companyid."','".$subcompanyid."','".$projectid."','".$clientid."','".addslashes(strtoupper($stringclient_name))."')"; 
				$conn->query($insert) ;		 

		}  
		return $clientid;
	}

?>