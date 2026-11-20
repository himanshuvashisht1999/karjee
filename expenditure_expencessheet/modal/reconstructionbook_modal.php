<?php 
 require('../../connection.php');
 require('../../functions/function_master.php');
 require('../../pdfroutines/pdfoverlay.php');
 ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
$action=$_GET['action'];
if($action=="reconstruction_book"){
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$valuedate=$_POST['valuedate'];
	$totalbankamount=$_POST['totalbankamount'];
	$bankdescription=$_POST['bankdescription'];
	$bblist=array();

	 $purposeinfomation=get_purpose_payto_keytabls($conn,$bankdescription);

	if ($purposeinfomation !="") 
	{
		$splitinfo=explode("@",$purposeinfomation);
		$inpayto=$splitinfo[0];
		$inpurpose=$splitinfo[1];
		$inpurpose_code=$splitinfo[2];
		$incompletpurpose=$inpurpose."(".$inpurpose_code.")";
		$intransferno=$splitinfo[3];
		$bblist[]=array('valuedate'=>$valuedate,'payto'=>$inpayto,'totalamount'=>$totalbankamount,'bookchequeno'=>$intransferno,'purpose'=>$incompletpurpose,'description'=>$bankdescription);
	}

	// if (strpos($bankdescription, "BSNL") || strpos($bankdescription, "vodafone") || strpos($bankdescription, "VAJRA")) 
	// {


	// 	$bblist[]=array('valuedate'=>$valuedate,'payto'=>'Pay to','totalamount'=>$totalbankamount,'bookchequeno'=>'Cheque no','purpose'=>'Purpose','description'=>$bankdescription);
	// }

	//return $bblist;
	echo json_encode($bblist);

}
else if ($action=="reconstruction_information") 
{
	$purposename="";
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$userpayto=$_POST['userpayto'];
	$userpurposecode=$_POST['userpurposecode'];
	$bankdescription=$_POST['bankdescription'];
	$bankamount=$_POST['bankamount'];
	$usercoments=$_POST['usercoments'];
	$splitkey1="";
	$splitkey2="";
	$splitkey3="";

	$descriptionsplitkey1="";
	$descriptionsplitkey2="";
	$descriptionsplitkey3="";

	$sqlsplit="Select split_key1,split_key2,split_key3 from tbl_descriptionsplit_keys";
	$resultsplit=$conn->query($sqlsplit);
	if ($resultsplit->num_rows>0) 
	{
		$rowsplit=$resultsplit->fetch_assoc();
		$splitkey1=$rowsplit['split_key1'];
		$splitkey2=$rowsplit['split_key2'];
		$splitkey3=$rowsplit['split_key3'];

		if (strpos($bankdescription, $splitkey1)!==false) 
		{
			
			$explode1=explode($splitkey1, $bankdescription);
			$descriptionsplitkey1=$explode1[1];
			if (strpos($descriptionsplitkey1, $splitkey2)!==false) 
			{
				$explode2=explode($splitkey2, $descriptionsplitkey1);
				$descriptionsplitkey2=$explode2[0];
			}
		}
	}



	$purposename=getpurposenamewithcat($conn,'purposecode',$userpurposecode);
	foreach ($purposename as $prjlist) {
		
	     $purposename=$prjlist['purpose'];
			            	
	}

	  $quryinsert="Insert into tbl_bookreconstruction_history(project_id,account_id,bank_description,bank_amount,payto_usergiven,purpose_usergiven,purpose_code_usergiven,comments_usergiven,key1,key2,key3)values('".$projectid."','".$accountid."','".$bankdescription."','".$bankamount."','".$userpayto."','".$purposename."','".$userpurposecode."','".$usercoments."','".$splitkey1."','".trim($descriptionsplitkey1)."','".trim($descriptionsplitkey2)."')";

	//mysqli_query($conn,$quryinsert);

}else if ($action=="reconstruction_historydata") 
{
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$bblist2=array();

	$selecthistory="Select bank_description,bank_amount,payto_usergiven,purpose_usergiven,purpose_code_usergiven,comments_usergiven from tbl_bookreconstruction_history where project_id='".$projectid."' and account_id='".$accountid."'";
	$resulthistory=$conn->query($selecthistory);
	if ($resulthistory->num_rows>0) 
	{
		while ($rowhis=$resulthistory->fetch_assoc()) 
		{
			 $compurpose=$rowhis['purpose_usergiven']."(".$rowhis['purpose_code_usergiven'].")";
			$bblist2[]=array('bankdescription'=>$rowhis['bank_description'],'bankamount'=>$rowhis['bank_amount'],'userpayto'=>$rowhis['payto_usergiven'],'userpurpose'=>$compurpose,'userpurpose_code'=>$rowhis['purpose_code_usergiven'],'userdescription'=>$rowhis['comments_usergiven']);
		}
	}

	echo json_encode($bblist2);
}else if ($action=="reconstruction_Matchdatabyhistorydata") 
{
	$accountid=$_POST['accountid'];
	$projectid=$_POST['projectid'];
	$bblist3=array();
	$selecthistory="Select bank_description,bank_amount,payto_usergiven,purpose_usergiven,purpose_code_usergiven,comments_usergiven,key1,key2,key3,key4,key5 from tbl_bookreconstruction_history where project_id='".$projectid."' and account_id='".$accountid."'";
	$resulthistory=$conn->query($selecthistory);
	if ($resulthistory->num_rows>0) 
	{
		while ($rowhis=$resulthistory->fetch_assoc()) 
		{

			$key1=$rowhis['key1'];
			$key2=$rowhis['key2'];
			$compurpose=$rowhis['purpose_usergiven']."(".$rowhis['purpose_code_usergiven'].")";
			$sqlfind="Select sno,valuedate,description,chequeno,debitamount from tbl_bankstatement where  projectid='".$projectid."' and account_id='".$accountid."' and description like('%".$key1."%')";
			$resultfind=$conn->query($sqlfind);
			if ($resultfind->num_rows>0) 
			{
				while ($rowfind=$resultfind->fetch_assoc()) 
				{
					$descriptionfind=$rowfind['description'];

					 if (strpos($descriptionfind, $key2) !== false)
					 {
					 	$bblist3[]=array('bankdescription'=>$rowhis['bank_description'],'bankamount'=>$rowhis['bank_amount'],'userpayto'=>$rowhis['payto_usergiven'],'userpurpose'=>$compurpose,'userpurpose_code'=>$rowhis['purpose_code_usergiven'],'userdescription'=>$rowhis['comments_usergiven'],'findbankdescription'=>$descriptionfind,'findbankamount'=>$rowfind['debitamount']);
					 }
					
						
						
				}
			}



		}
	}


			 // $compurpose=$rowhis['purpose_usergiven']."(".$rowhis['purpose_code_usergiven'].")";
			 // $bblist3[]=array('bankdescription'=>$rowhis['bank_description'],'bankamount'=>$rowhis['bank_amount'],'userpayto'=>$rowhis['payto_usergiven'],'userpurpose'=>$compurpose,'userpurpose_code'=>$rowhis['purpose_code_usergiven'],'userdescription'=>$rowhis['comments_usergiven']);
	echo json_encode($bblist3);
}else if ($action=="getpaytofrommaster") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $shortbyvalue=$_POST['shortbyvalue'];
	 $shortby=$_POST['shortby']; 
	 
	 // $shortby="subcategoryid";
	echo json_encode(getpaytoinfo($mconn,$companyid,$subcompanyid,$shortby,$shortbyvalue));
}else if ($action=="getbeneficerynamefrommaster") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $shortbyvalue=$_POST['shortbyvalue'];
	 $shortby=$_POST['shortby']; 
	 
	 // $shortby="subcategoryid";
	echo json_encode(getbeneficiaryinfodetails($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue));
}
else if ($action=="getdepositerfrommaster") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $shortbyvalue=$_POST['shortbyvalue'];
	 $shortby=$_POST['shortby']; 
	 
	 // $shortby="subcategoryid";
	echo json_encode(getdepositerinfo($mconn,$companyid,$subcompanyid,$shortby,$shortbyvalue));
}
else if ($action=="getpurposemaster") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $subcateid=$_POST['subcateid'];
	 $shortby=$_POST['shortby']; 
	 
	 // $shortby="subcategoryid";
	echo json_encode(getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,$shortby,$subcateid));
}else if ($action=="getpurposemastercategory") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $shortby='all';
	  
	 
	 $shortby="all";
	echo json_encode(GetPurposeCategoryfrommaster($mconn,$companyid,$subcompanyid,$projectid,$shortby,''));
}
else if ($action=="getpurposemastersubcategory") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $catid=$_POST['catid'];
	 $shortby='categoryid';
	  
	 
	 
	echo json_encode(GetPurposeSubCategoryfrommaster($mconn,$companyid,$subcompanyid,$projectid,$shortby,$catid));
}
else if ($action=="getpurposemasterdeposit") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $subcateid=$_POST['subcateid'];
	 $shortby=$_POST['shortby'];
	 
	//$shortby="all";
	echo json_encode(getcreditpurposedetails($mconn,$companyid,$subcompanyid,$shortby,$subcateid));
}
else if ($action=="getcatepurposemasterdeposit") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	  //$subcateid=$_POST['subcateid'];
	  
	 
	$shortby="all";
	echo json_encode(GetPurposeCategory_credit($mconn,$companyid,$subcompanyid,$projectid,$shortby,''));
}
else if ($action=="getpurposemastersubcategorydeposit") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $cateid=$_POST['catid'];
	 $shortby=$_POST['shortby'];
	 
	//$shortby="all";
	echo json_encode(GetPurposeSubCategory_credit($mconn,$shortby,$cateid));
}
else if ($action=="gethistoryofpayto") {
	$keylessdesc=$_POST['keylessdesc'];
	$keybakdesc=$_POST['keybankdesc'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$hislist=array();


     // $queryhistory="Select m.reference_no,m.transactionno as chequeno,m.payto,m.transaction_type,m.paymenttype,b.beneficiary_name,b.amount,b.purpose,b.purpose_code,b.description from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.payto like('%".$keylessdesc."%')";

     $queryhistory="Select date,bankdescription,expensesdescription,purpose,purposecode,payto,beneficeryname,paymenttype,key_lesdesc,bank_keydesc,status,transaction_type,amount,transactionno from tbl_historytransactiondetails where  bank_keydesc='".$keybakdesc."' and key_lesdesc='".$keylessdesc."'";
   $resulthistory=$mconn->query($queryhistory);
   if ($resulthistory->num_rows>0) {
   	while ($rowh=$resulthistory->fetch_assoc()) {
   		$hislist[]=array('referenceno'=>'','historytransactionno'=>$rowh['transactionno'],'payto'=>$rowh['payto'],'beneficiaryname'=>$rowh['beneficeryname'],'amount'=>$rowh['amount'],'purpose'=>$rowh['purpose'],'purposecode'=>$rowh['purposecode'],'description'=>$rowh['expensesdescription'],'transactiontype'=>$rowh['transaction_type'],'paymenttype'=>$rowh['paymenttype'],'date'=>$rowh['date']);
   	}
   }

   echo json_encode($hislist);
}
else if ($action=="gethistoryfromdeposit") {
	$keylessdesc=$_POST['keylessdesc'];
	$keybakdesc=$_POST['keybankdesc'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$hislist=array();


    

    $queryhistory="Select date,bankdescription,expensesdescription,purpose,purposecode,depositer,beneficeryname,paymenttype,key_lesdesc,bank_keydesc,status,transaction_type,amount,transactionno from tbl_historytransactiondetailsdepsit where  bank_keydesc='".$keybakdesc."' and key_lesdesc='".$keylessdesc."'";
   $resulthistory=$mconn->query($queryhistory);
   if ($resulthistory->num_rows>0) {
   	while ($rowh=$resulthistory->fetch_assoc()) {
   		$hislist[]=array('referenceno'=>'','historytransactionno'=>$rowh['transactionno'],'payto'=>$rowh['depositer'],'beneficiaryname'=>$rowh['beneficeryname'],'amount'=>$rowh['amount'],'purpose'=>$rowh['purpose'],'purposecode'=>$rowh['purposecode'],'description'=>$rowh['expensesdescription'],'transactiontype'=>$rowh['transaction_type'],'paymenttype'=>$rowh['paymenttype'],'date'=>$rowh['date']);
   	}
   }

   echo json_encode($hislist);
}
else if ($action=="getpurposesubcategorymaster") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $shortby=$_POST['category'];
	echo json_encode(GetPurposeCategoryInfofrommaster($mconn,$companyid,$subcompanyid,$projectid,$shortby));
}
else if ($action=="getpurposesubcategorymasterdeposit") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $shortby=$_POST['category'];
	echo json_encode(GetPurposeCategoryInfofrommasterdeposits($mconn,$companyid,$subcompanyid,$projectid,$shortby));
}

else if ($action=="saveinfoforbookreconstrution_old") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $accountid=$_POST['accountid'];
	 $banksno=$_POST['banksno'];
	 $valuedate=$_POST['valuedate'];
	 $bankdescription=$_POST['bankdescription'];
	 $withdrowamount=$_POST['withdrowamount'];
	 $bankcheque=$_POST['bankcheque'];
	 $paymenttype=$_POST['paymenttype'];
	 $adddescription=$_POST['adddescription'];
	 $paytoname=$_POST['paytoname'];
	 $beneficiaryname=$_POST['beneficiaryname'];
	 $beneficiaryamount=$_POST['beneficeryamount'];
	 $purposecode=$_POST['purposecode'];
	 $ispaytobnf=$_POST['ispaytobnf'];
	 $howmanybeneficiary=$_POST['howmanybeneficiary'];
	 $transactionmode=$_POST['transactionmode'];
	 $combindstring1=explode(",",$_POST['combindstring1']);
	 $combindstring2=explode(",",$_POST['combindstring2']);
	 $combindstring3=explode(",",$_POST['combindstring3']);
	 $combindstring4=explode(",",$_POST['combindstring4']);
	 
	 $tsno=maxtsno($mconn);
	 $referenceno="Ref-".$tsno;
	 $bankinfo=getbankinformation($mconn,$accountid);
	 $bank = explode("@", $bankinfo);
	 $accountname=$bank[0];
	 $accountno=$bank[1];
	 $bankname=$bank[2];

	 if ($ispaytobnf=="yes") {
	 	 $getpurposestring=getpurposedetails($mconn,$purposecode);
	 	 $pieces = explode("@", $getpurposestring);
	     $categoryid=$pieces[0];
	     $category=$pieces[1];
	     $subcategoryid=$pieces[2];
	     $subcategory=$pieces[3];
	     $purpose=$pieces[4];
	 	  $insertqury="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,payto,accountno,account_id,total_amount,paymenttype,purpose,purpose_code,description,remarks,reference_no,beneficiaryname,amount,tsno,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode)values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','".$paymenttype."','".$purpose."','".$purposecode."','".$bankdescription."','".$adddescription."','','".$beneficiaryname."','".$beneficiaryamount."','".$tsno."','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','".$banksno."','Web','".$transactionmode."')";
	 	$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	    	if ($resultinsert) {
	    		 $returnresult="Done";

	    		
	    	}
	 }else {
	 	
	 	for ($i=0; $i <$howmanybeneficiary ; $i++) { 
	 		 $beneficiaryname=$combindstring1[$i];
	 		 $beneficiaryamount=$combindstring2[$i];
	 		 $purposecode=$combindstring3[$i];
	 		 $adddescription=$combindstring4[$i];
	 		 $getpurposestring=getpurposedetails($mconn,$purposecode);
	 	 	$pieces1 = explode("@", $getpurposestring);
	     	 $categoryid=$pieces1[0];
	     	 $category=$pieces1[1];
	     	 $subcategoryid=$pieces1[2];
	     	 $subcategory=$pieces1[3];
	     	 $purpose=$pieces1[4];

	 		  $insertqury="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,payto,accountno,account_id,total_amount,paymenttype,purpose,purpose_code,description,remarks,reference_no,beneficiaryname,amount,tsno,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode) values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','".$paymenttype."','".$purpose."','".$purposecode."','".$bankdescription."','".$adddescription."','','".$beneficiaryname."','".$beneficiaryamount."','".$tsno."','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','".$banksno."','web','".$transactionmode."')";
	 		
	 		$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	    	if ($resultinsert) {
	    		 $returnresult="Done";
	    	}
	 	}

	 	
	 }

	  

// echo json_encode($returnresult);
	 echo $returnresult;

}else if ($action=="saveinfoforbookreconstrutionXX") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $accountid=$_POST['accountid'];
	 $loginid=$_POST['loginid'];
	 $valuedate=$_POST['valuedate'];
	 $withdrowamount=$_POST['totalbeneficaryamount'];
	 $bankcheque=$_POST['bankcheque'];
	 $bankdescription=$_POST['adddescription'];
	 $paytoname=$_POST['paytoname'];
	 $transactionmode=$_POST['transactionmode'];
	 $combindstring1=explode(",",$_POST['combindstring1']);
	 $combindstring2=explode(",",$_POST['combindstring2']);
	 $combindstring3=explode(",",$_POST['combindstring3']);
	 $combindstring4=explode(",",$_POST['combindstring4']);
	 //$combindstring5=explode(",",$_POST['combindstring5']);
	 $howmanybeneficiary=$_POST['howmanybf'];
	 $filelength=$_POST['filelength'];

	 $tsno=maxtsno($mconn);
	 $referenceno="Ref-".$tsno;
	 $bankinfo=getbankinformation($mconn,$accountid);
	 $bank = explode("@", $bankinfo);
	 $accountname=$bank[0];
	 $accountno=$bank[1];
	 $bankname=$bank[2];
	 $categoryid=$pieces1[0];
	 $category="";
	 $subcategoryid="";
	 $subcategory="";
	 $purpose="";
	 $directory="";
	 if (isset($_FILES['files']) && !empty($_FILES['files'])){
	 	$no_files = count($_FILES["files"]['name']);
	 	for ($i = 0; $i < $no_files; $i++) 
      	{
      		$directory = "../../expencessheet_document/".$loginid."_".$referenceno.'/';
			if (!file_exists($directory)) {
				mkdir($directory, 0755, true);
			}
			$targetPath =  $directory . $_FILES['files']['name'][$i];
			move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetPath);
      	}
	 }
	 
	
	 	
	 	for ($i=1; $i <$howmanybeneficiary ; $i++) { 
	 		 $beneficiaryname=$combindstring1[$i-1];
	 		 $beneficiaryamount=$combindstring2[$i-1];
	 		 $purposecode=$combindstring3[$i-1];
	 		 $adddescription=$combindstring4[$i-1];
	 		 if ($purposecode) {
	 		 	$getpurposestring=getpurposedetails($mconn,$purposecode);
	 	 	 	$pieces1 = explode("@", $getpurposestring);
	     	 	$categoryid=$pieces1[0];
	     	 	$category=$pieces1[1];
	     	 	$subcategoryid=$pieces1[2];
	     	 	$subcategory=$pieces1[3];
	     	 	$purpose=$pieces1[4];
	 		 }
	 		 

	 		   $insertqury="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,payto,accountno,account_id,total_amount,paymenttype,purpose,purpose_code,description,remarks,reference_no,beneficiaryname,amount,tsno,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode,loginid,fileinfo) values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','','".$purpose."','".$purposecode."','".$bankdescription."','".$adddescription."','','".$beneficiaryname."','".$beneficiaryamount."','".$tsno."','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','','web','".$transactionmode."','".$loginid."','".$directory."')";
	 		
	 		$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	    	if ($resultinsert) {
	    		 $returnresult="Done";
	    	}
	 	}

	 	
	 

	  

// echo json_encode($returnresult);
	 echo $returnresult;

}else if ($action=="saveinfoforbookreconstrution") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $accountid=$_POST['accountid'];
	 $loginid=$_POST['loginid'];
	 $valuedate=$_POST['valuedate'];
	 $withdrowamount=$_POST['totalbeneficaryamount'];
	 $bankcheque=$_POST['bankcheque'];
	 $bankdescription=$_POST['adddescription'];
	 $paytoname=$_POST['paytoname'];
	 $transactionmode=$_POST['transactionmode'];
	 $combindstring1=explode(",",$_POST['combindstring1']);
	 $combindstring2=explode(",",$_POST['combindstring2']);
	 $combindstring3=explode(",",$_POST['combindstring3']);
	 $combindstring4=explode(",",$_POST['combindstring4']);
	// $combindstring5=explode(",",$_POST['combindstring5']);
	 $howmanybeneficiary=$_POST['howmanybf'];
	 $tsno=maxtsno($mconn);
	 $referenceno="Ref-".$tsno;
	 $bankinfo=getbankinformation($mconn,$accountid);
	 
	 $bank = explode("@", $bankinfo);
	 $accountname=$bank[0];
	 $accountno=$bank[1];
	 $bankname=$bank[2];
	 // $categoryid=$pieces1[0];
	  $categoryid="";
	 $category="";
	 $subcategoryid="";
	 $subcategory="";
	 $purpose="";
	 $directory="";
	
	
	
	 	
	 	for ($i=0; $i <$howmanybeneficiary ; $i++) { 
	 		 $beneficiaryname=$combindstring1[$i];
	 		 $beneficiaryamount=$combindstring2[$i];
	 		 $purposecode=$combindstring3[$i];
	 		 $adddescription=$combindstring4[$i];
	 		 if ($purposecode) {
	 		 	$getpurposestring=getpurposedetails($mconn,$purposecode);
	 	 	 	$pieces1 = explode("@", $getpurposestring);
	     	 	$categoryid=$pieces1[0];
	     	 	$category=$pieces1[1];
	     	 	$subcategoryid=$pieces1[2];
	     	 	$subcategory=$pieces1[3];
	     	 	$purpose=$pieces1[4];
	 		 }
	 		 
	 		//echo "#".$_FILES['files']['name'][$i];
	 		//$targetPath =  $dir . $_FILES['files']['name'][$i];
	 		 if (isset($_FILES['files']) && !empty($_FILES['files'])){
	 		 	$directory = "../../expencessheet_document/".$loginid."_".$referenceno.'/';
				if (!file_exists($directory)) {
					mkdir($directory, 0755, true);
				}
				 echo "@". $targetPath =  $directory . $_FILES['files']['name'][$i];
			    move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetPath);
	 		 }

	 		   $insertqury="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,payto,accountno,account_id,total_amount,paymenttype,purpose,purpose_code,description,remarks,reference_no,beneficiaryname,amount,tsno,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode,loginid,fileinfo) values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','','".$purpose."','".$purposecode."','".$bankdescription."','".$adddescription."','','".$beneficiaryname."','".$beneficiaryamount."','".$tsno."','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','','web','".$transactionmode."','".$loginid."','".$directory."')";
	 		
	 		$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	    	if ($resultinsert) {
	    		 $returnresult="Done";
	    	}
	 	}

	 echo $returnresult;

}else if ($action=="saveinfoforbookreconstrutionfordetauld") {
	$companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $accountid=$_POST['accountid'];
	 $loginid=$_POST['loginid'];
	 $valuedate=$_POST['valuedate'];
	 $withdrowamount=$_POST['totalbeneficaryamount'];
	 $bankcheque=$_POST['bankcheque'];
	 $bankdescription=$_POST['adddescription'];
	 $paytoname=$_POST['paytoname'];
	 $transactionmode=$_POST['transactionmode'];
	 $combindstring1=explode(",",$_POST['combindstring1']);
	 $combindstring2=explode(",",$_POST['combindstring2']);
	 $combindstring3=explode(",",$_POST['combindstring3']);
	 $combindstring4=explode(",",$_POST['combindstring4']);
	 $howmanybeneficiary=$_POST['howmanybf'];
	 $tsno=maxtsno($mconn);
	 $referenceno="Ref-".$tsno;
	 $bankinfo=getbankinformation($mconn,$accountid);
	
	 $bank = explode("@", $bankinfo);
	 $accountname=$bank[0];
	 $accountno=$bank[1];
	 $bankname=$bank[2];
	 $categoryid="";
	 $category="";
	 $subcategoryid="";
	 $subcategory="";
	 $purpose="";
	 $directory="";
	 $j=0;
	 $directory = "../../expencessheet_document/".$loginid."_".$referenceno.'/';
	 if (!file_exists($directory)) {
		mkdir($directory, 0755, true);
	}
	 	
	 	for ($i=0; $i <$howmanybeneficiary ; $i++) { 
	 		 $beneficiaryname=$combindstring1[$i];
	 		 $beneficiaryamount=$combindstring2[$i];
	 		 $purposecode=$combindstring3[$i];
	 		 $adddescription=$combindstring4[$i];
	 		 if ($purposecode) {
	 		 	$getpurposestring=getpurposedetails($mconn,$purposecode);
	 	 	 	$pieces1 = explode("@", $getpurposestring);
	     	 	$categoryid=$pieces1[0];
	     	 	$category=$pieces1[1];
	     	 	$subcategoryid=$pieces1[2];
	     	 	$subcategory=$pieces1[3];
	     	 	$purpose=$pieces1[4];
	 		 }
	 		 
	 		//echo "#".$_FILES['files'.$j]['name'][$i];

	 		 
	 		
	 		 if (isset($_FILES['files'.$j]) && !empty($_FILES['files'.$j])){
	 		 	$directory2 = $directory.$j.'/';
				if (!file_exists($directory2)) {
					mkdir($directory2, 0755, true);
				}
				$no_files = count($_FILES['files'.$j]['name']);
				for ($z = 0; $z < $no_files; $z++) 
      			{
      			//echo "@".$targetPath =  $directory2 . $_FILES['files'.$j]['name'][$z];
			    move_uploaded_file($_FILES['files'.$j]['tmp_name'][$z], $targetPath);
      			}
				
	 		 }
	 		 $insertqury="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,payto,accountno,account_id,total_amount,paymenttype,purpose,purpose_code,description,remarks,reference_no,beneficiaryname,amount,tsno,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode,loginid,fileinfo) values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','','".$purpose."','".$purposecode."','".$bankdescription."','".$adddescription."','','".$beneficiaryname."','".$beneficiaryamount."','".$tsno."','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','','web','".$transactionmode."','".$loginid."','".$directory2."')";
	 		
			$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	 		if ($resultinsert) {
	 			 $returnresult="Done";
	 		}

	 		  
	 		$j++;
	 	}

	 echo $returnresult;
}
else if ($action=="saveinfoforbookreconstrutiondeposit"){
	$companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $accountid=$_POST['accountid'];
	 $loginid=$_POST['loginid'];
	 $valuedate=$_POST['valuedate'];
	
	 $withdrowamount=$_POST['withdrowamount'];
	 $bankcheque=$_POST['bankcheque'];
	 $paymentmode=$_POST['transactionmode'];
	 $adddescription=$_POST['adddescription'];
	 $paytoname=$_POST['paytoname'];	 
	 $purposecode=$_POST['purposecode'];
	 
	 $tsno=maxtsnodeposit($mconn);
	 $referenceno="CH-".$tsno;
	 $bankinfo=getbankinformation($mconn,$accountid);
	 $bank = explode("@", $bankinfo);
	 $accountname=$bank[0];
	 $accountno=$bank[1];
	 $bankname=$bank[2];
	 $chk="";
	 
	 $getpurposestring=getpurposedetailsdeposit($mconn,$purposecode);
     $pieces1 = explode("@", $getpurposestring);
     $categoryid=$pieces1[0];
     $category=$pieces1[1];
     $subcategoryid=$pieces1[2];
     $subcategory=$pieces1[3];
     $purpose=$pieces1[4];

     $directory="";
	 if (isset($_FILES['files']) && !empty($_FILES['files'])){
	 	$no_files = count($_FILES["files"]['name']);
	 	for ($i = 0; $i < $no_files; $i++) 
      	{
      		$directory = "../../depositsheet_document/".$loginid."_".$referenceno.'/';
			if (!file_exists($directory)) {
				mkdir($directory, 0755, true);
			}
			$targetPath =  $directory . $_FILES['files']['name'][$i];
			if (move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetPath)) {
				$returnresult="Done";

			}else{
				$returnresult="Error";
			}

			//move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetPath);
			// $overlaytext="AnantXXXXXXXXXXXXXXXXXX";
			// $area="TOP_RIGHT";
			// $sourcedir=$targetPath;
			//echo "@".$overlaytext=basename($sourcedir,".pdf");
			//overlay($sourcedir,$sourcedir,$overlaytext,$area);
      	}
	 }

	$insertqury="Insert into tbl_depositsinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,depositto,accountno,account_id,amount,paymenttype,purpose,purpose_code,description,remarks,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode,loginid,fileinfo)values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','".$paymenttype."','".$purpose."','".$purposecode."','','".$adddescription."','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','','web','".$paymentmode."','".$loginid."','".$directory."')";
	 	$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	    if ($resultinsert) {
	    	$returnresult="Done";	
	    }

	     echo $returnresult;
}

else if($action=="saveinfoforbookreconstrution_sameunknownenter"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcmppanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$paymenttype=$_POST['paymenttype'];
	$paytoname=$_POST['payto'];
	$beneficiaryname=$_POST['beneficaryname'];
	$purposecode=$_POST['purposecode'];
	$bankinfo=getbankinformation($mconn,$accountid);
	 $bank = explode("@", $bankinfo);
	 $accountname=$bank[0];
	 $accountno=$bank[1];
	 $bankname=$bank[2];
	 $getpurposestring=getpurposedetails($mconn,$purposecode);
	 $pieces1 = explode("@", $getpurposestring);
	 $categoryid=$pieces1[0];
	 $category=$pieces1[1];
	 $subcategoryid=$pieces1[2];
	 $subcategory=$pieces1[3];
	 $purpose=$pieces1[4];
	
	$tableData_A=$_POST['pTableData_MRG'];
	$tableData_A = json_decode($tableData_A,TRUE);
	foreach($tableData_A as $skey)
	{
	 	$valuedate=$skey['valuedate'];
	 	$bankcheque=$skey['transactionno'];
	 	$withdrowamount=$skey['bankamount'];
	 	$description=$skey['description'];
	 	$banksno=$skey['banksno'];
	 	 $tsno=maxtsno($mconn);
	 	$insertqury="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,payto,accountno,account_id,total_amount,paymenttype,purpose,purpose_code,description,remarks,reference_no,beneficiaryname,amount,tsno,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode) values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','".$paymenttype."','".$purpose."','".$purposecode."','".$description."','','','".$beneficiaryname."','".$withdrowamount."','".$tsno."','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','".$banksno."','Bank Statement','Cheque')";
	 	$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	    	if ($resultinsert) {
	    		//$returnresult=$tsno;
	    		$updateunknontranstion="Update tbl_unknown_expenses set status='Done' where banksno='".$banksno."'";
	    		$resultinsertunkn = $mconn->query($updateunknontranstion) or die($mconn->error);
	    	}

	}

	echo json_encode($resultinsertunkn);

}else if($action=="saveinfoforbookreconstrution_sameunknownenterdeposit"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcmppanyid'];
	$projectid=$_POST['projectid'];
	$accountid=$_POST['accountid'];
	$paymenttype=$_POST['paymenttype'];
	$paytoname=$_POST['payto'];
	$beneficiaryname=$_POST['beneficaryname'];
	$purposecode=$_POST['purposecode'];
	$paymentmode=$_POST['paymentmode'];
	$bankinfo=getbankinformation($mconn,$accountid);
	 $bank = explode("@", $bankinfo);
	 $accountname=$bank[0];
	 $accountno=$bank[1];
	 $bankname=$bank[2];
	 $getpurposestring=getpurposedetailsdeposit($mconn,$purposecode);
	 $pieces1 = explode("@", $getpurposestring);
	 $categoryid=$pieces1[0];
	 $category=$pieces1[1];
	 $subcategoryid=$pieces1[2];
	 $subcategory=$pieces1[3];
	 $purpose=$pieces1[4];
	
	$tableData_A=$_POST['pTableData_MRG'];
	$tableData_A = json_decode($tableData_A,TRUE);
	foreach($tableData_A as $skey)
	{
	 	$valuedate=$skey['valuedate'];
	 	$bankcheque=$skey['transactionno'];
	 	$withdrowamount=$skey['bankamount'];
	 	$bankdescription=$skey['description'];
	 	$banksno=$skey['banksno'];
	 	$tsno=maxtsnodeposit($mconn);
	 	$referenceno="CH-".$banksno."-".$tsno;
	 	
	 	

	 	$insertqury="Insert into tbl_depositsinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,depositto,accountno,account_id,amount,paymenttype,purpose,purpose_code,description,remarks,categoryid,category,sub_categoryid,sub_category,accountname,banksno,source,paymentmode)values('".$companyid."','".$subcompanyid."','".$projectid."','".$valuedate."','".$bankcheque."','".$bankname."','".$paytoname."','".$accountno."','".$accountid."','".$withdrowamount."','".$paymenttype."','".$purpose."','".$purposecode."','".$bankdescription."','','".$categoryid."','".$category."','".$subcategoryid."','".$subcategory."','".$accountname."','".$banksno."','Bank Statement','".$paymentmode."')";

	 	$resultinsert = $mconn->query($insertqury) or die($mconn->error);
	    	if ($resultinsert) {
	    		$updateunknontranstion="Update tbl_unknown_deposits set status='Done' where banksno='".$banksno."'";
	    		$resultinsertunkn = $mconn->query($updateunknontranstion) or die($mconn->error);
	    	
	    			$updatechk="Update tbl_bankstatement set uchequeno='".$referenceno."' where sno='".$banksno."'";
	    		   $resultchk = $mconn->query($updatechk) or die($mconn->error);

	    		
	    	}

	    	$chk="";

	}

	echo json_encode($resultinsert);

}
else if ($action=="makeabook") {
	$returnmessage=array();
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $getaccountid=$_POST['accountid'];
    $dbsno_banksno = $_POST['dbsno_banksno'];
    $bankname = $_POST['bankname'];
    $accountno = $_POST['accountno'];
    $valuedate = $_POST['valuedate'];
    $paymentmode = $_POST['paymentmode'];
    $chequeno = $_POST['chequeno'];
    $withdrownamount = $_POST['withdrownamount'];
    $beneficerytype = $_POST['beneficerytype'];
    $payto = $_POST['txtpaytoname'];
    //$singlemultiple = $_POST['singlemultiple'];
    $paymenttype=$_POST['txtpaymenttype'];
    $expensessno=$_POST['expensessno'];
    $sendinfo=json_decode($_POST["sendinfo"],true);
    $source=$_POST['source'];
    $historysave=$_POST['historysave'];
    $bankdescription=$_POST['bankdescription'];
    $keylessdesctd=$_POST['keylessdesctd'];
    $bankkeytd=$_POST['bankkeytd'];
    $bookfrom=$source;
	$reconciliation_status="";
	$valuedate2="";
	if($bookfrom=='Bank')
	 {
		 $reconciliation_status='Done';
		 $valuedate2=$valuedate;
	 }else{
	 	$reconciliation_status="";
	 	$valuedate2="";
	 	$dbsno_banksno="";
	 }
     
     $tsno=0;
     $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");
		
	if($mtso->num_rows>0){
		$trow=$mtso->fetch_assoc();
		$tsno=$trow['maxtsno'];
	}
	 $newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
     if ($chequeno=='') {
         $chequeno=$newreference_no;
     }

  	$maintransqry="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid,  reference_no, bookdate, book_amount, beforeedit_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, is_intermediate_transfer, refundable_projectid,transactionno,reconciliation,reconciliation_banksno,value_date) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$valuedate."', '".$withdrownamount."', '".$withdrownamount."', '".$paymentmode."', '".$payto."', '".$bankname."', '".$accountno."', '".$getaccountid."', '".ucfirst($paymenttype)."', '".$beneficerytype."', '', '', '', '', '','".$chequeno."','".$reconciliation_status."','".$dbsno_banksno."','".$valuedate2."')";
   $maintrans=$mconn->query($maintransqry) or die($mconn->error);
    if($maintrans) {
        foreach($sendinfo as $row1)
        {
            $beneficeryname =$row1['beneficaryname'];
            $amount =$row1['beneficaryamount'];        
            $description =$row1['description'];                           
            $purposecode =$row1['purposecode'];
            $getpurposestring=getpurposedetails($mconn,$purposecode);
            $pieces1 = explode("@", $getpurposestring);
            $pcatid=$pieces1[0];
            $pcat=$pieces1[1];
            $psubcatid=$pieces1[2];
            $psubcat=$pieces1[3];
            $purpose=$pieces1[4];
            $brk_dwn_insert="INSERT INTO tbl_maintransaction_breakdown(companyid, subcompanyid, projectid, request_no, reference_no, beneficiary_name, amount, purpose_categoryid,purpose_category, purpose_subcategoryid, purpose_subcategory,  purpose, purpose_code, invoice_type, invoice_no, description, blobfile, blobfile_tblname, tds_type, tds_percent, tds_amount, documentation, doctype, documentation_path) VALUES ('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($beneficeryname)."', '".$amount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".$purpose."', '".$purposecode."', '', '', '".addslashes($description)."', '', '', '', '', 0, '', '', '')";
            	if ($historysave=='yes') {
            		$history_insert="INSERT INTO tbl_historytransactiondetails(companyid, subcomapnyid, projectid, account_id,bankdescription,expensesdescription,purpose,purposecode,payto,beneficeryname,paymenttype,status,key_lesdesc,bank_keydesc,transaction_type,amount,date,transactionno ) VALUES ('".$companyid."', '".$subcompanyid."', '".$projectid."','".$getaccountid."', '".addslashes($bankdescription)."','".addslashes($description)."','".$purpose."','".$purposecode."','".addslashes($payto)."','".addslashes($beneficeryname)."','".ucfirst($paymenttype)."','Manual','".$keylessdesctd."','".$bankkeytd."','".$paymentmode."','".$amount."','".$valuedate."','".$chequeno."')";
            			$history_result=$mconn->query($history_insert) or die($mconn->error);
            	}
            $breakdown_result=$mconn->query($brk_dwn_insert) or die($mconn->error);
            if ($breakdown_result) {
                $status="Done";
            }
        }
     	$updateexpenses="Update tbl_expencesinexcelsheet set status='Done',reference_no='".$newreference_no."' where ".$expensessno." ";
      	$updateexpenses_result=$mconn->query($updateexpenses) or die($mconn->error);
    	if ($bookfrom=="Bank") {
    		$updatebank="Update tbl_bankstatement set status='Done', reconciliation='Done', reference_no='".$newreference_no."' where sno='".$dbsno_banksno."'";
    		$updatebank_result=$mconn->query($updatebank) or die($mconn->error);
    	}
    	$returnmessage[]=array('Status'=>$status);
	}
 	echo json_encode($returnmessage);
}else if ($action=="makeabookfromhistory") {
	 $returnmessage=array();
     $companyid = $_POST['companyid'];
     $subcompanyid = $_POST['subcompanyid'];
     $projectid = $_POST['projectid'];
     $getaccountid=$_POST['accountid'];
     $dbsno_banksno = $_POST['banksno'];
     //$historyreferenceno = $_POST['historyreferenceno'];
     $bankname = $_POST['bankname'];
     $accountno = $_POST['accountno'];
     $historytransactiontype = $_POST['historytransactiontype'];
     $historypaymenttype = $_POST['historypaymenttype'];
     $valuedate = $_POST['valuedate'];
     $bankdescription = $_POST['bankdescription'];
     $totalamount = $_POST['bankamount'];
     $historypayto = $_POST['historypayto'];
     $historybeneficery = $_POST['historybeneficery'];
     $historypurposecode = $_POST['historypurposecode'];
     $chequeno = $_POST['bankchequeno'];
     $expensessno = $_POST['expensessno'];
     $beneficerytype ="Individual";
     $status="";

      $tsno=0;
     $mtso=$mconn->query("SELECT (ifnull(max(tsno),0) + 1) as maxtsno FROM tbl_maintransaction");
		
		if($mtso->num_rows>0){
			$trow=$mtso->fetch_assoc();
			$tsno=$trow['maxtsno'];
		}
		$newreference_no = 'REF-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;

		 $maintransqry="INSERT INTO tbl_maintransaction(tsno, companyid, subcompanyid, projectid,  reference_no, bookdate, book_amount, beforeedit_amount, transaction_type, payto, payfrombankname, payfromaccountno, payfromaccountid, paymenttype, beneficierie, paytoaccountno, paytobankname, paytoifsc, is_intermediate_transfer, refundable_projectid,transactionno,reconciliation,reconciliation_banksno,value_date) VALUES('".$tsno."', '".$companyid."', '".$subcompanyid."', '".$projectid."', '".$newreference_no."', '".$valuedate."', '".$totalamount."', '".$totalamount."', '".$historytransactiontype."', '".$historypayto."', '".$bankname."', '".$accountno."', '".$getaccountid."', '".$historypaymenttype."', '".$beneficerytype."', '', '', '', '', '','".$chequeno."','Done','".$dbsno_banksno."','".$valuedate."')";

		 	$maintrans=$mconn->query($maintransqry) or die($mconn->error);
               

            if ($maintrans) {
              $getpurposestring=getpurposedetails($mconn,$historypurposecode);
              $pieces1 = explode("@", $getpurposestring);
              $pcatid=$pieces1[0];
              $pcat=$pieces1[1];
              $psubcatid=$pieces1[2];
              $psubcat=$pieces1[3];
              $purpose=$pieces1[4];

              $brk_dwn_insert="INSERT INTO tbl_maintransaction_breakdown(companyid, subcompanyid, projectid, request_no, reference_no, beneficiary_name, amount, purpose_categoryid , purpose_category, purpose_subcategoryid, purpose_subcategory, purpose, purpose_code, invoice_type, invoice_no, description, blobfile, blobfile_tblname, tds_type, tds_percent, tds_amount, documentation, doctype, documentation_path) VALUES ('".$companyid."', '".$subcompanyid."', '".$projectid."', '', '".$newreference_no."', '".addslashes($historybeneficery)."', '".$totalamount."', '".$pcatid."', '".$pcat."', '".$psubcatid."', '".$psubcat."', '".$purpose."', '".$historypurposecode."', '', '', '".addslashes($bankdescription)."', '', '', '', '', 0, '', '', '')";
           	  $breakdown_result=$mconn->query($brk_dwn_insert) or die($mconn->error);
              if ($breakdown_result) {
                  $status="Done";
              }
            }

            if ($expensessno!="") {
            	$updateexpenses="Update tbl_expencesinexcelsheet set status='Done',reference_no='".$newreference_no."' where ".$expensessno." ";
                          $updateexpenses_result=$mconn->query($updateexpenses) or die($mconn->error);
            }
            

                      
             $updatebank="Update tbl_bankstatement set status='Done',reconciliation='Done',reference_no='".$newreference_no."' where sno='".$dbsno_banksno."'";
             $updatebank_result=$mconn->query($updatebank) or die($mconn->error);
                
            $returnmessage[]=array('Status'=>$status);

		echo json_encode($returnmessage);	      
}else if ($action=="makeabookfromhistorydeposit") {
	 $companyid = $_POST['companyid'];
     $subcompanyid = $_POST['subcompanyid'];
     $projectid = $_POST['projectid'];
     $getaccountid=$_POST['accountid'];
     $dbsno_banksno = $_POST['banksno'];
     $bankname = $_POST['bankname'];
     $accountno = $_POST['accountno'];
     $historytransactiontype = $_POST['historytransactiontype'];
     $historypaymenttype = $_POST['historypaymenttype'];
     $valuedate = $_POST['valuedate'];
     $bankdescription = $_POST['bankdescription'];
     $totalamount = $_POST['bankamount'];
     $historypayto = $_POST['historypayto'];
     $historypurposecode = $_POST['historypurposecode'];
     $chequeno = $_POST['bankchequeno']; 
     $uchequeno = $_POST['uchequeno']; 
     $mtso=$mconn->query("SELECT (ifnull(max(sno),0) + 1) as maxtsno FROM tbl_credit_transaction");
		
	if($mtso->num_rows>0){
		$trow=$mtso->fetch_assoc();
		$tsno=$trow['maxtsno'];
	}
	 $newreference_no = 'CRD-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
	 $getpurposestring=getpurposedetailsdeposit($mconn,$historypurposecode);
     $pieces1 = explode("@", $getpurposestring);
     $pcatid=$pieces1[0];
     $pcat=$pieces1[1];
     $psubcatid=$pieces1[2];
     $psubcat=$pieces1[3];
     $purpose=$pieces1[4];
	 	 $maintransqry="INSERT INTO tbl_credit_transaction(cmp_id,subcmp_id,creditto_projectid,creditto_accountid,accountno,reciveddate,reference_no,transactionno,amount,creditpurposecode,creditpurpose,providedpurpose,bankdescription,description,creditername,statmentsno,depositbooksno,category,categoryid,subcategoryid,subcategory,status)values('".$companyid."','".$subcompanyid."','".$projectid."','".$getaccountid."','".$accountno."','".$valuedate."','".$newreference_no."','".$chequeno."','".$totalamount."','".$historypurposecode."','".$purpose."','','".$bankdescription."','','".$historypayto."','".$dbsno_banksno."','','".$pcat."','".$pcatid."','".$psubcatid."','".$psubcat."','Done')";
	 	    	$maintrans=$mconn->query($maintransqry) or die($mconn->error);
    			if($maintrans) {
    				$updatebank="Update tbl_bankstatement set status='Done', reconciliation='Done', reference_no='".$newreference_no."' where sno='".$dbsno_banksno."'";
    				$updatebank_result=$mconn->query($updatebank) or die($mconn->error);
    				if ($uchequeno) {
    					$updatebank2="Update tbl_depositsinexcelsheet set status='Done' where chequeno='".$uchequeno."'";
    				$updatebank_result2=$mconn->query($updatebank2) or die($mconn->error);
    				}
    				 $status="Done";
    			}

    			$returnmessage[]=array('Status'=>$status);
				echo json_encode($returnmessage);
}
else if ($action=="makeabookdeposit") {
	$returnmessage=array();
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $getaccountid=$_POST['accountid'];
    $dbsno_banksno = $_POST['dbsno_banksno'];
    $bankname = $_POST['bankname'];
    $accountno = $_POST['accountno'];
    $valuedate = $_POST['valuedate'];
    $paymentmode = $_POST['paymentmode'];
    $chequeno = $_POST['chequeno'];
    $withdrownamount = $_POST['withdrownamount'];
    $beneficerytype = $_POST['beneficerytype'];
    $payto = $_POST['txtpaytoname'];
    $paymenttype=$_POST['txtpaymenttype'];
    $expensessno=$_POST['expensessno'];
    $sendinfo=json_decode($_POST["sendinfo"],true);
    $source=$_POST['source'];
    $historysave=$_POST['historysave'];
    $bankdescription=$_POST['bankdescription'];
    $keylessdesctd=$_POST['keylessdesctd'];
    $bankkeytd=$_POST['bankkeytd'];
    $bookfrom=$source;
	$reconciliation_status="";
	$providedpurpose=$_POST['providedpurpose'];
	$status="";
	$mtso=$mconn->query("SELECT (ifnull(max(sno),0) + 1) as maxtsno FROM tbl_credit_transaction");
		
	if($mtso->num_rows>0){
		$trow=$mtso->fetch_assoc();
		$tsno=$trow['maxtsno'];
	}
	 $newreference_no = 'CRD-'.$companyid.'-'.$subcompanyid.'-'.$projectid.'-'.substr($accountno, -4).'-'.$tsno;
	foreach($sendinfo as $row1)
    {
            $beneficeryname =$row1['beneficaryname'];
            $amount =$row1['beneficaryamount'];        
            $description =$row1['description'];                           
            $purposecode =$row1['purposecode'];
	 		$getpurposestring=getpurposedetailsdeposit($mconn,$purposecode);
     		$pieces1 = explode("@", $getpurposestring);
     		$pcatid=$pieces1[0];
     		$pcat=$pieces1[1];
     		$psubcatid=$pieces1[2];
     		$psubcat=$pieces1[3];
     		$purpose=$pieces1[4];
	 	    $maintransqry="INSERT INTO tbl_credit_transaction(cmp_id,subcmp_id,creditto_projectid,creditto_accountid,accountno,reciveddate,reference_no,transactionno,amount,creditpurposecode,creditpurpose,providedpurpose,bankdescription,description,creditername,statmentsno,depositbooksno,category,categoryid,subcategoryid,subcategory,status)values('".$companyid."','".$subcompanyid."','".$projectid."','".$getaccountid."','".$accountno."','".$valuedate."','".$newreference_no."','".$chequeno."','".$withdrownamount."','".$purposecode."','".$purpose."','".$providedpurpose."','".$bankdescription."','".$description."','".$payto."','".$dbsno_banksno."','".$expensessno."','".$pcat."','".$pcatid."','".$psubcatid."','".$psubcat."','Done')";
	 	    	$maintrans=$mconn->query($maintransqry) or die($mconn->error);
    			if($maintrans) {
    				$updatebank="Update tbl_bankstatement set status='Done', reconciliation='Done', reference_no='".$newreference_no."' where sno='".$dbsno_banksno."'";
    				$updatebank_result=$mconn->query($updatebank) or die($mconn->error);
    				$updateexpenses="Update tbl_depositsinexcelsheet set status='Done',reference_no='".$newreference_no."' where sno=".$expensessno." ";
      				$updateexpenses_result=$mconn->query($updateexpenses) or die($mconn->error);
      				$history_insert="INSERT INTO tbl_historytransactiondetailsdepsit(companyid, subcomapnyid, projectid, account_id,bankdescription,expensesdescription,purpose,purposecode,depositer,beneficeryname,paymenttype,status,key_lesdesc,bank_keydesc,transaction_type,amount,date,transactionno ) VALUES ('".$companyid."', '".$subcompanyid."', '".$projectid."','".$getaccountid."', '".addslashes($bankdescription)."','".addslashes($description)."','".$purpose."','".$purposecode."','".addslashes($payto)."','','".ucfirst($paymenttype)."','Manual','".$keylessdesctd."','".$bankkeytd."','".$paymentmode."','".$amount."','".$valuedate."','".$chequeno."')";
            			$history_result=$mconn->query($history_insert) or die($mconn->error);
      				   $status="Done";

    			}
	}
	$returnmessage[]=array('Status'=>$status);
	echo json_encode($returnmessage);
}else if ($action=="uploadbankstatement") {
	$companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcmppanyid'];
	 $projectid=$_POST['projectid'];
	 $loginid=$_POST['loginid'];

	$filelist=array();
	$returnstatus="";
	$message="";
	if (isset($_FILES['files']) && !empty($_FILES['files'])) 
	{
		$directory = "../../bankstatement_document/bankstatements_".$loginid.'/';
		if (!file_exists($directory)) {
			mkdir($directory, 0755, true);
		}

		$no_files = count($_FILES["files"]['name']);
		for ($i = 0; $i < $no_files; $i++) 
      	{
      		$targetPath =  $directory . $_FILES['files']['name'][$i];
      		if(file_exists($targetPath)==1)
		     {
		        
		      	
		      	 unlink($directory .$_FILES['files']['name'][$i]);
		      }
		      

		      	move_uploaded_file($_FILES['files']['tmp_name'][$i], $targetPath);
		      	
		      	 
		      	 $insertqury="Insert into statementtodb_attach(companyid,subcompanyid,projectid,loginid,FileNameOrg,Filename)values('".$companyid."','".$subcompanyid."','".$projectid."','".$loginid."','".$_FILES['files']['name'][$i]."','".$targetPath."')";
	 			$resultinsert = $mconn->query($insertqury) or die($mconn->error);

		      
		      	 $filelist[]= array("status"=>'Done', "filename"=>$_FILES['files']['name'][$i],"message"=>$message);
		      
      		
      	}

	}else{
		$filelist[]= array("status"=>"Error", "filename"=>$_FILES['files']['name'][$i],"message"=>"File not available");
	}
    echo json_encode($filelist);
}

function getsubmitdetailsbytsno($conn,$tsno){
	$qrytsno="Select date,chequeno,bankname,accountname,accountno,payto,beneficiaryname,amount,total_amount,paymenttype,purpose,purpose_code,description,remarks from tbl_expencesinexcelsheet where tsno='".$tsno."'";
	$resulttsno=$conn->query($qrytsno);
	if ($resulttsno->num_rows==1) {
		$tsnorow=$resulttsno->fetch_assoc();
		$bnfdiv.='<div class="main-container bg-white">
			    <div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Bank Details:</label>
						<input type="text" class="form-control bank_input" value="'.$tsnorow['accountname']."_".$tsnorow['bankname']."_".$tsnorow['accountno'].'" readonly/>
					</div>
				</div>
				<div class="main-container bg-white">
			    <div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Date:</label>
						<input type="text" class="form-control beneficiary_input" value="'.$tsnorow['date'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Name of the Payto</label>
						<input type="text" class="form-control beneficiary_input" id="submited_payto" value="'.$tsnorow['payto'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Name of the beneficiary</label>
						<input type="text" class="form-control beneficiary_input" id="submited_beneficiary" value="'.$tsnorow['beneficiaryname'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Amount of the beneficiary</label>
						<input type="text" class="form-control beneficiary_input" value="'.$tsnorow['total_amount'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Payment Type</label>
						<input type="text" class="form-control beneficiary_input" id="submited_paymenttype" value="'.$tsnorow['paymenttype'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Purpose</label>
						<input type="text" class="form-control purpose_input" value="'.$tsnorow['purpose'].'" readonly />
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Description</label>
						<input type="text" class="form-control description_input" value="'.$tsnorow['description']." -And ".$tsnorow['remarks'].'" readonly />
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Purpose</label>
						<input type="text" class="form-control purposecode_input" id="submited_purposecode" value="'.$tsnorow['purpose_code'].'" readonly />
					</div>
				</div>
			</div>';
	}else if ($resulttsno->num_rows==1) {
		$i=1;
		while ($tsnorow=$resulttsno->fetch_assoc()) {
			
		}
	}
	return $bnfdiv;
}
function getsubmitdetailsbytsnodeposit($conn,$tsno){
	$submtdiv="";
	$qrytsno="Select date,chequeno,bankname,accountname,accountno,depositto,amount,paymenttype,purpose,purpose_code,description,remarks,paymentmode from tbl_depositsinexcelsheet where banksno='".$tsno."'";
	$resulttsno=$conn->query($qrytsno);
	if ($resulttsno->num_rows==1) {
		$tsnorow=$resulttsno->fetch_assoc();
		$submtdiv.='<div class="main-container bg-white">
			    <div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Bank Details:</label>
						<input type="text" class="form-control bank_input" value="'.$tsnorow['accountname']."_".$tsnorow['bankname']."_".$tsnorow['accountno'].'" readonly/>
					</div>
				</div>
				<div class="main-container bg-white">
			    <div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Date:</label>
						<input type="text" class="form-control beneficiary_input" value="'.$tsnorow['date'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Name of the Payto</label>
						<input type="text" class="form-control beneficiary_input" id="submited_payto" value="'.$tsnorow['depositto'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Name of the beneficiary</label>
						<input type="text" class="form-control beneficiary_input" id="submited_beneficiary" value="'.$tsnorow['depositto'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Amount of the beneficiary</label>
						<input type="text" class="form-control beneficiary_input" value="'.$tsnorow['amount'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Payment Type</label>
						<input type="text" class="form-control beneficiary_input" id="submited_paymenttype" value="'.$tsnorow['paymenttype'].'" readonly/>
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Purpose</label>
						<input type="text" class="form-control purpose_input" value="'.$tsnorow['purpose'].'" readonly />
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Description</label>
						<input type="text" class="form-control description_input" value="'.$tsnorow['description']." -And ".$tsnorow['remarks'].'" readonly />
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Purpose</label>
						<input type="text" class="form-control purposecode_input" id="submited_purposecode" value="'.$tsnorow['purpose_code'].'" readonly />
					</div>
				</div>
				<div class="row p-2">
					<div class="col-md-12">
						<label class="lbl-text">Payment Type</label>
						<input type="text" class="form-control beneficiary_input" id="submited_paymentmode" value="'.$tsnorow['paymentmode'].'" readonly/>
					</div>
				</div>
			</div>';
	}

	return $submtdiv;
}
function get_purpose_payto_keytabls($conn,$bankdescription)
{
	$purposeinfomation="";
	$purpose="";
	$purpose_code="";
	$payto="";
	$sqlget="select key_name,payto,purpose,purpose_code,split_description_forutr_key from tbl_bookreconstruction_keys";
	$resultget=$conn->query($sqlget);
	if ($resultget->num_rows>0) 
	{

		while ($bnkunrow=$resultget->fetch_assoc()) {
			$keyname=$bnkunrow['key_name'];
			if(strpos($bankdescription,$keyname)!==false) 
			{
				$purpose=$bnkunrow['purpose'];
				$purpose_code=$bnkunrow['purpose_code'];
				$payto=$bnkunrow['payto'];
				$desc_splitkey=$bnkunrow['split_description_forutr_key'];

				$descrption_split=explode($desc_splitkey, $bankdescription);
				$str2=$descrption_split[1];

				if (strpos($str2, "/")!==false) 
				{
					$str3=explode("/", $str2);
					$intransferno=$str3[0];
				}else if (strpos($bankdescription, "IDIBH")) {
					$str3=explode(" ", $str2);
					$intransferno="IDIBH".$str3[0];
					
				}

				else{
					$intransferno=$str2;
				}

				//$intransfernostr=explode("/",$str2);

				

			    $purposeinfomation=$payto."@".$purpose."@".$purpose_code."@".$intransferno;
				
				break;
			}
			else {
				$purposeinfomation="$payto"."@"."$purpose"."@"."$purpose_code"."@"."$intransferno";
			}

		}
		

		return $purposeinfomation;
		
	}
}

function getpurposedetails($mconn,$purposecode)
{
	$purposestring="";
	 $sqlp="Select categoryid,category,sub_categoryid,sub_category,purpose from purpose_code_master where purposecode='".$purposecode."'";
	$resulp=$mconn->query($sqlp);
	if ($resulp->num_rows>0) {
		$rowp=$resulp->fetch_assoc();
		$purposestring=$rowp['categoryid']."@".$rowp['category']."@".$rowp['sub_categoryid']."@".$rowp['sub_category']."@".$rowp['purpose'];
	}

	return $purposestring;
}

function getpurposedetailsdeposit($mconn,$purposecode)
{
	$purposestring="";
	 $sqlp="Select categoryid,category,subcategoryid,subcategory,purpose from tbl_purpose_master_credit where purpose_code='".$purposecode."'";
	$resulp=$mconn->query($sqlp);
	if ($resulp->num_rows>0) {
		$rowp=$resulp->fetch_assoc();
		$purposestring=$rowp['categoryid']."@".$rowp['category']."@".$rowp['subcategoryid']."@".$rowp['subcategory']."@".$rowp['purpose'];
	}

	return $purposestring;
}

function maxtsno($mconn)
{
	$maxtsno=0;
	$sqltsno="Select max(tsno+1) as tsno from tbl_expencesinexcelsheet";
	$resulttsno=$mconn->query($sqltsno);
	$rowt=$resulttsno->fetch_assoc();
	if (is_null($rowt['tsno'])) {
		$maxtsno=1;
	}else {
		$maxtsno=$rowt['tsno'];
	}
	return $maxtsno;
}
function maxtsnodeposit($mconn)
{
	$maxtsno=0;
	$sqltsno="Select max(sno+1) as tsno from tbl_depositsinexcelsheet";
	$resulttsno=$mconn->query($sqltsno);
	$rowt=$resulttsno->fetch_assoc();
	if (is_null($rowt['tsno'])) {
		$maxtsno=1;
	}else {
		$maxtsno=$rowt['tsno'];
	}
	return $maxtsno;
}
function getbankinformation($mconn,$accountid){
	$qurban="Select accountname,accountno,bankname from tbl_master_account where accountid='".$accountid."'";
	$resultb=$mconn->query($qurban);
	$rowb=$resultb->fetch_assoc();
	return $rowb['accountname']."@".$rowb['accountno']."@".$rowb['bankname'];
}

 ?>