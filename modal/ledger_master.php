<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
//require('../automation/automation_functionmaster.php');
$action=$_GET['action'];
if($action=="getledgerlist"){
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$qry="Select ledger_id,ledger_name,table_name,report_base,report_base_table,report_base_field,report_base_fieldid,category,report_no,category_code from tbl_statementofledgers_master where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."'";
	$resultqry=$conn->query($qry);
	$ledlist= array();
	if ($resultqry->num_rows>0) {
		while ($rowl=$resultqry->fetch_assoc()) {
			$ledlist[]=array('ledger_id'=>$rowl['ledger_id'],'ledger_name'=>$rowl['ledger_name'],'table_name'=>$rowl['table_name'],'reportbase'=>$rowl['report_base'],'reportbasetable'=>$rowl['report_base_table'],'reportbasefield'=>$rowl['report_base_field'],'reportbasefieldid'=>$rowl['report_base_fieldid'],'category'=>$rowl['category'],'report_no'=>$rowl['report_no'],'category_code'=>$rowl['category_code']);
		}
	}
	echo json_encode($ledlist);
}else if ($action=="getledgerlistintable") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$ledlist= array();
	 // $qryc="Select distinct category_code,category from  tbl_statementofledgers_master where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."'";

	 $qryc="Select distinct category_code,category from  tbl_statementofledgers_master ";
	
	$resultcat=$conn->query($qryc);
	if ($resultcat->num_rows>0) {
		while ($rowcat=$resultcat->fetch_assoc()) {
			$ledlist[]=array('ledger_id'=>'','ledger_name'=>'','table_name'=>'','reportbase'=>'','reportbasetable'=>'','reportbasefield'=>'','reportbasefieldid'=>'','category'=>$rowcat['category'],'report_no'=>'','category_code'=>$rowcat['category_code']);

			  // $qry="Select ledger_id,ledger_name,ifnull(table_name,'') as table_name,report_base,report_base_table,report_base_field,report_base_fieldid,category,report_no,category_code,ifnull(reportfunction,'') as reportfunction, ifnull(customised,'No') as customised  from tbl_statementofledgers_master where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and category_code='".$rowcat['category_code']."'";
			 $qry="Select distinct ledger_id,ledger_name,ifnull(table_name,'') as table_name,report_base,report_base_table,report_base_field,report_base_fieldid,category,report_no,category_code,ifnull(reportfunction,'') as reportfunction, ifnull(customised,'No') as customised  from tbl_statementofledgers_master where  category_code='".$rowcat['category_code']."'";
			$resultqry=$conn->query($qry);
			
			if ($resultqry->num_rows>0) {
				while ($rowl=$resultqry->fetch_assoc()) {
					$ledlist[]=array('ledger_id'=>$rowl['ledger_id'],'ledger_name'=>$rowl['ledger_name'],'table_name'=>$rowl['table_name'],'reportbase'=>$rowl['report_base'],'reportbasetable'=>$rowl['report_base_table'],'reportbasefield'=>$rowl['report_base_field'],'reportbasefieldid'=>$rowl['report_base_fieldid'],'category'=>$rowl['category'],'report_no'=>$rowl['report_no'],'category_code'=>$rowl['category_code'],'functionname'=>$rowl['reportfunction'],'customised'=>$rowl['customised']);
				}
			}

		}
	}
	// $qry="Select ledger_id,ledger_name,table_name,report_base,report_base_table,report_base_field,report_base_fieldid,category,report_no,category_code from tbl_statementofledgers_master where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."'";
	// $resultqry=$conn->query($qry);
	// $ledlist= array();
	// if ($resultqry->num_rows>0) {
	// 	while ($rowl=$resultqry->fetch_assoc()) {
	// 		$ledlist[]=array('ledger_id'=>$rowl['ledger_id'],'ledger_name'=>$rowl['ledger_name'],'table_name'=>$rowl['table_name'],'reportbase'=>$rowl['report_base'],'reportbasetable'=>$rowl['report_base_table'],'reportbasefield'=>$rowl['report_base_field'],'reportbasefieldid'=>$rowl['report_base_fieldid'],'category'=>$rowl['category'],'report_no'=>$rowl['report_no'],'category_code'=>$rowl['category_code']);
	// 	}
	// }
	echo json_encode($ledlist);
}else if ($action=="savereportstypeinmaster") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$reportcategorycode=$_POST['reportcategory'];
	$reportcategoryname=$_POST['reportcategoryname'];
	$reportno=$_POST['reportno'];
	$reportname=$_POST['reportname'];
	$tblname=$_POST['tblname'];
	$maxid="";
	$qrym="Select max(ledger_id) as maxid from tbl_statementofledgers_master where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."'";
	$resultm=$conn->query($qrym);
	if($resultm->num_rows>0){
		$mrow = $resultm->fetch_assoc();
		if (is_null($mrow['maxid'])) {
			$maxid = 1;
		}else{
			$maxid = $mrow['maxid']+1;
		}
		
	}else{
		$maxid=1;
	}

	
	 $insertqry="Insert into tbl_statementofledgers_master(company_id,subcompany_id,project_id,ledger_id,category_code,category,report_no,ledger_name,table_name)values('".$companyid."','".$subcompanyid."','".$projectid."','".$maxid."','".$reportcategorycode."','".$reportcategoryname."','".$reportno."','".$reportname."','".$tblname."')";

	$result = $conn->query($insertqry) or die($conn->error);
	if ($result) {
		$status="Done";
	}

	if ($status=="Done") {
		$bnflist[]=array("Status"=>"Done",'message'=>'Saved Category');
	}else{
		$bnflist[]=array("Status"=>"Error",'message'=>'Error');
	}

	echo json_encode($bnflist);


}else if ($action=="getreportcategory") {
	$j=0;
  $qryt="Select category_code,categoryname from tbl_report_category";
  $resultt=$conn->query($qryt);
  if ($resultt->num_rows>0) {
  	while ($rowt=$resultt->fetch_assoc()) {
  		
  		if ($j==0) {
  			$option="<option selected disabled>Select Report Category </option>";
  			
  		}
  		$option.="<option value=".$rowt['category_code'].">".$rowt['categoryname']."</option>";
  		$j++;
  		
  	}
  }
  echo $option;
}else if ($action=="getcolumnfromtableforledger") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$ledgerid=$_POST['ledgerid'];
	$tablename=$_POST['tablename'];
	$clolist=array();

	// $qry="SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE  TABLE_NAME = '".$tablename."'";
	// $resultqry=$conn->query($qry);
	// if ($resultqry->num_rows>0) {
	// 	$orderno="";
	// 	$displayname="";
	// 	$connectedtable="";
	// 	$connectedfields="";
	// 	$whereconditionfields="";
	// 	$display="";
	// 	while ($rowcol=$resultqry->fetch_assoc()) {
	// 		 $qryd="Select orderlist,ifnull(displayname,'') as displayname,ifnull(connected_table,'') as connected_table ,ifnull(connected_fields,'') as connected_fields ,ifnull(where_conditionfields,'') as where_conditionfields, ifnull(display,'') as display from tbl_ledgers_information where company_id='".$companyid."'and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and table_name='".$tablename."' and column_name='".$rowcol['COLUMN_NAME']."'";
	// 		$resultd=$conn->query($qryd);
	// 		if ($resultd->num_rows>0) {
	// 			$orderno="";
	// 			$rowd=$resultd->fetch_assoc();
	// 			$orderno=$rowd['orderlist'];
	// 			$displayname=$rowd['displayname'];
	// 			$connectedtable=$rowd['connected_table'];
	// 			$connectedfields=$rowd['connected_fields'];
	// 			$whereconditionfields=$rowd['where_conditionfields'];
	// 			$display=$rowd['display'];
	// 		}else{
	// 			$orderno="";
	// 			$displayname="";
	// 			$connectedtable="";
	// 			$connectedfields="";
	// 			$whereconditionfields="";
	// 			$display="";
	// 		}
	// 		$clolist[]=array('columnname'=>$rowcol['COLUMN_NAME'],'orderlist'=>$orderno,'displayname'=>$displayname,'connectedtable'=>$connectedtable,'connectedfields'=>$connectedfields,'whereconditionfields'=>$whereconditionfields,'display'=>$display);
	// 	}
	// }

	 $qryd="Select orderlist,column_name,ifnull(displayname,'') as displayname,ifnull(connected_table,'') as connected_table ,ifnull(connected_fields,'') as connected_fields ,ifnull(where_conditionfields,'') as where_conditionfields, ifnull(display,'') as display from tbl_ledgers_information where company_id='".$companyid."'and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and  ledger_id='".$ledgerid."'";
				$resultd=$conn->query($qryd);
				if ($resultd->num_rows>0) {
					$orderno="";
					while ($rowd=$resultd->fetch_assoc()) {
						$orderno=$rowd['orderlist'];
						$displayname=$rowd['displayname'];
						$connectedtable=$rowd['connected_table'];
						$connectedfields=$rowd['connected_fields'];
						$whereconditionfields=$rowd['where_conditionfields'];
						$display=$rowd['display'];
						$clolist[]=array('columnname'=>$rowd['column_name'],'orderlist'=>$orderno,'displayname'=>$displayname,'connectedtable'=>$connectedtable,'connectedfields'=>$connectedfields,'whereconditionfields'=>$whereconditionfields,'display'=>$display);
					}
					
				}else{
					$orderno="";
					$displayname="";
					$connectedtable="";
					$connectedfields="";
					$whereconditionfields="";
					$display="";
					$clolist[]=array('columnname'=>$rowd['column_name'],'orderlist'=>$orderno,'displayname'=>$displayname,'connectedtable'=>$connectedtable,'connectedfields'=>$connectedfields,'whereconditionfields'=>$whereconditionfields,'display'=>$display);
				}
				

	echo json_encode($clolist);
}else if ($action=="getpaytolist") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$paytolist=getpaytoinfo($conn,$companyid,$subcompanyid,"all","");
	echo json_encode($paytolist);
}else if ($action=="getoptionlist") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$reportbasetable=$_POST['reportbasetable'];
	$reportbasefield=$_POST['reportbasefield'];
	$reportbasefieldid=$_POST['reportbasefieldid'];
	//$paytolist=getpaytoinfo($conn,$companyid,$subcompanyid,"all","");
	if ($reportbasetable=='tbl_payto_master') {
		$optionlist=getpaytoinfo($conn,$companyid,$subcompanyid,"all","");
	}else if ($reportbasetable=='tbl_beneficiaries_master') {
		$optionlist=getbeneficiaryinfodetails($conn,$companyid,$subcompanyid,$projectid,'','');
	}else if ($reportbasetable=='strs_billtypemaster_universal') {
		$optionlist=getbillmasterinfodetails($conn,$companyid,$subcompanyid,$projectid,'','');
		// $optionlist=getbillmasterandbilltypemasterinfodetails($conn,$companyid,$subcompanyid,$projectid,'','');
	}else if ($reportbasetable=='imprest_authorizedperson') {
		$optionlist=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,'all','');
	}else if ($reportbasetable=='cash_account') {
		$optionlist=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'cash','');
	}else if ($reportbasetable=='creditcard_account') {
		$optionlist=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'creditcard','');
	}else if ($reportbasetable=='tbl_loanaccount_summary') {
		$optionlist=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'loan','');
	}else if ($reportbasetable=='StatementofAllWithdrawalandDeposit') {
		$optionlist=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'companyid','');
	}
	
	echo json_encode($optionlist);
}else if ($action=="saveselectedledgerlist") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$ledgerid=$_POST['ledgerid'];
	$ledgername=$_POST['ledgername'];
	$tablename=$_POST['tablename'];
	$bnflist=array();
	$tablestructure = json_decode($_POST['tablestructure'],TRUE);
	$insertvalues="";
	$insertquery="insert into tbl_ledgers_information(company_id,subcompany_id,project_id,table_name,ledger_id,column_name,ledger_name,orderlist,connected_table,displayname,connected_fields,where_conditionfields)Values";

	foreach ($tablestructure as $cvkey) {
		
		
		$columnname=$cvkey['columnname'];
		$order=$cvkey['order'];
		$displayname=$cvkey['displayname'];
		if ($displayname=="") {
			$displayname=$columnname;
		}
		$connectedtbl=$cvkey['connectedtbl'];
		$connectedfields=$cvkey['connectedfields'];
		$whereconditionfields=$cvkey['whereconditionfields'];
		if ($columnname) {
			$sltqry="Select * from tbl_ledgers_information where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and table_name='".$tablename."' and ledger_id='".$ledgerid."' and column_name='".$columnname."'";
			$resultqry=$conn->query($sltqry);
			if ($resultqry->num_rows>0) {
				$rowl=$resultqry->fetch_assoc();
				$sno=$rowl['sno'];
				$updateqry="Update tbl_ledgers_information set orderlist='".$order."',connected_table='".$connectedtbl."',displayname='".$displayname."',connected_fields='".$connectedfields."',where_conditionfields='".$whereconditionfields."' where sno='".$sno."' ";
				$result = $conn->query($updateqry) or die($conn->error);
				if ($result) {
					$status="Done";
				}
			}else{

				 // $updateqry="insert into tbl_ledgers_information(company_id,subcompany_id,project_id,table_name,ledger_id,column_name,ledger_name,orderlist)Values('".$companyid."','".$subcompanyid."','".$projectid."','".$tablename."','".$ledgerid."','".$columnname."','".$ledgername."','".$order."') ";
				if ($insertvalues) {
					$insertvalues.=",('".$companyid."','".$subcompanyid."','".$projectid."','".$tablename."','".$ledgerid."','".$columnname."','".$ledgername."','".$order."','".$connectedtbl."','".$displayname."','".$connectedfields."','".$whereconditionfields."')";
				}else{
					$insertvalues="('".$companyid."','".$subcompanyid."','".$projectid."','".$tablename."','".$ledgerid."','".$columnname."','".$ledgername."','".$order."','".$connectedtbl."','".$displayname."','".$connectedfields."','".$whereconditionfields."')";
				}
				
			}
		}
		
			
		}
		if ($insertvalues) {
			$qryX=$insertquery.$insertvalues;
			$result = $conn->query($qryX) or die($conn->error);
			if ($result) {
				$status="Done";
			}
		}
		
		if ($status=="Done") {
			$bnflist[]=array("Status"=>"Done",'message'=>'Done');
		}else{
			$bnflist[]=array("Status"=>"Error",'message'=>'Error');
		}
	echo json_encode($bnflist);	
}else if ($action=="updatedisplayledgerlist") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$ledgerid=$_POST['ledgerid'];
	$ledgername=$_POST['ledgername'];
	$tablename=$_POST['tablename'];
	$bnflist=array();
	$tablestructure = json_decode($_POST['tablestructure'],TRUE);
	foreach ($tablestructure as $cvkey) {
		$columnname=$cvkey['columnname'];
		$display=$cvkey['display'];
		$order=$cvkey['order'];
		if ($columnname) {
			$updateqry="Update tbl_ledgers_information set display='".$display."',orderlist='".$order."',displayname='".$cvkey['displaycolumn']."' where column_name='".$columnname."' and ledger_id='".$ledgerid."' and project_id='".$projectid."'";
			$result = $conn->query($updateqry) or die($conn->error);
			if ($result) {
				$status="Done";
			}

		}
		if ($status=="Done") {
			$bnflist[]=array("Status"=>"Done",'message'=>'Done');
		}else{
			$bnflist[]=array("Status"=>"Error",'message'=>'Error');
		}
		
	}

	echo json_encode($bnflist);	

}else if ($action=="getreportsheader") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$ledgerid=$_POST['ledgerid'];
	$tablename=$_POST['tablename'];
	$optionid=$_POST['optionid'];
	$reportbase=$_POST['reportbase'];
	$reportbasetable=$_POST['reportbasetable'];
	$reportbasefield=$_POST['reportbasefield'];
	$reportbasefieldid=$_POST['reportbasefieldid'];
	if ($reportbase=='all') {
		$optionid="";
	}else{
		$optionfielname=$reportbasefield;
		$optionfielid=$reportbasefieldid;
	}
	$strfildesname="";
	$sshead=getcolumnsnamefrommaster($conn,$companyid,$subcompanyid,$projectid,$ledgerid);
	
		$tr= "<thead style='position: sticky; top: 0; z-index: 1000; background-color: white;'><tr><th>Sno</th>";
		foreach ($sshead as $hdkey) {
			if ($strfildesname) {
				$strfildesname.=",".$hdkey['column_name'];
			}else{
				
				$strfildesname=$hdkey['column_name'];
			}
			$tr.="<th>".$hdkey['displayname']."</th>";
		}
			
		$tr.="</tr></thead>";
		$valueoffield=getvaluetoseletedcolumn($conn,$companyid,$subcompanyid,$projectid,$optionid,$strfildesname,$tablename,$sshead,$ledgerid,$optionfielname,$optionfielid);
		//print_r($valueoffield);
		$tr.="<tbody>";
		foreach ($valueoffield as $hdkey) {
			$tr.=$hdkey['fieldvalue'];
		}
		
		$tr.="</tbody>";
	 echo $tr;
}else if ($action=="getreportbyfunction") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$ledgerid=$_POST['ledgerid'];
	$functionname=$_POST['functionname'];
	$extended=$_POST['extended'];
	$billcode=$_POST['billcode'];
	$selectoptionname=$_POST['selectoptionname'];
	$selectoptionid=$_POST['selectoptionid'];
	$d1=$_POST['d1'];
	$d2=$_POST['d2'];

	$finalreturn=$functionname($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcode);
	
	 //echo json_encode($finalreturn);
	echo $finalreturn;
}else if ($action=="saveheadernameinmaster") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcompanyid'];
	 $projectid=$_POST['projectid'];
	 $ledgerid=$_POST['ledgerid'];
	 $ledgername=$_POST['ladgername'];
	 $headerNames=$_POST['headerNames'];
	 $arrpand=explode(",", $headerNames);
	 for ($i=0; $i <count($arrpand) ; $i++) { 
	 	$qry="Select * from tbl_ledgers_information where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and ledger_id='".$ledgerid."' and displayname='".$arrpand[$i]."'";
	 	$resultw=$conn->query($qry);
	 	if ($resultw->num_rows>0) {
	 		
	 	}else{
	 		$qry="Insert into tbl_ledgers_information(company_id,subcompany_id,project_id,ledger_id,ledger_name,table_name,column_name,displayname,orderlist,display)values('".$companyid."','".$subcompanyid."','".$projectid."','".$ledgerid."','".$ledgername."','','$arrpand[$i]','$arrpand[$i]','".$i."','')";
	 		$resultw=$conn->query($qry);
	 	}
	 	
	 }
}else if ($action=="getreportbycustomtype") {
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcompanyid'];
	 $projectid=$_POST['projectid'];
	 $ledgerid=$_POST['ledgerid'];
	 $customtype=$_POST['customtype'];
	 $tr="";
	 if ($customtype=='all') {
	 	   $qrycm="Select column_name,displayname,orderlist from tbl_ledgers_information where company_id='".$companyid."'and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and ledger_id='".$ledgerid."' order by orderlist ";
	 	  //$qrycm="Select column_name,displayname,orderlist from tbl_ledgers_information where  ledger_id='".$ledgerid."' ";
	 }else{
	 	   $qrycm="Select column_name,displayname,orderlist from tbl_ledgers_information where company_id='".$companyid."'and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and ledger_id='".$ledgerid."' and display='Yes' order by orderlist";
	 	   // $qrycm="Select column_name,displayname,orderlist from tbl_ledgers_information where  ledger_id='".$ledgerid."' and display='Yes' order by orderlist";
	 }
	 $genrateddate=getgenrateddatetime($conn,$companyid,$subcompanyid,$projectid,$ledgerid,'strs_9_amacr1');
	 $getfromtoddate=getfromtodate($conn,$companyid,$subcompanyid,$projectid,$ledgerid,'strs_9_amacr1');
	 $makqry="";
	 $displaycolumname="";
	 $resultqry=$conn->query($qrycm);
	 if ($resultqry->num_rows>0) {
	 	$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='4' >Report No.:</th><th colspan='10' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='4'>Description:</th><th colspan='10' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='4' >Project:</th><th colspan='10' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='4' >Company:</th><th colspan='10' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='4' >From beginning to till date:</th><th colspan='10' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='4' >Generated on date & time:</th><th colspan='10' class='cl_datetime_2' >".$genrateddate."</th></tr>";
	 	$tr.= "<th style='position: sticky; top: 0; z-index: 1000; background-color: white;'><tr><th>Sno</th>";
	 	while ($rowcd=$resultqry->fetch_assoc()) {
	 		if ($makqry) {
	 			$makqry.=",".$rowcd['column_name'];
	 			
	 		}else{
	 			$makqry=$rowcd['column_name'];
	 			
	 		}

	 		$tr.="<th>".$rowcd['displayname']."</th>";
		
		  
	 	}

	 }
	  $tr.="</tr></thead>";
	  $tr.="<tbody>";
	  // echo "string".$makqry;
	 if ($makqry) {
	 	$arrfin=array();
	 	
	 	 $qrytu="Select ".$makqry." From strs_9_amacr1 where companyid='".$companyid."'and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ledger_id='".$ledgerid."'";
	 	$resultv=$conn->query($qrytu);
	 
	 	 $rows = $resultv->fetch_all(MYSQLI_ASSOC);
		 if ($resultv->num_rows>0) {
		 	$j=1;
		 	
		 	 for ($i=0; $i < count($rows); $i++) { 
		 	 	$td="";
		 	 	$tr.="<tr style='font-size:18px;' class='text-black'><td>".$j."</td>";
		 	 	
		 	 	foreach ($rows[$i] as $key => $value) {

		            if ($td) {
		 	 			
		 	 			// $td.="<td>".$key."</td>";
		 	 			$td.="<td>".$value."</td>";
		 	 		}else{
		 	 			  // $td="<td>".$key."</td>"; 	 			
		 	 			  $td="<td>".$value."</td>";	
		 	 		}


	 	 		
		 	 	
		 	 	}

		 	 	$tr.=$td."</tr>";
		 	 	$j++;
		 	 }

		 	}
	 }
	 $tr.="</tbody>";
	 echo $tr;
}
function getgenrateddatetime($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$tblname){
	$dattime="";
	$qry="Select edatetime from  ".$tblname."  where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ledger_id='".$ledgerid."'";
	$resultqw=$conn->query($qry);
	if ($resultqw->num_rows>0) {
		$rowd=$resultqw->fetch_assoc();
		$dattime=date('d-m-Y H:i:s',strtotime($rowd['edatetime']));
	}
	return $dattime;
}
function getfromtodate($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$tblname){
	$dattime="";
	  $qry="Select min(bookdate) as mindate,max(bookdate) as maxdate from  ".$tblname."  where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ledger_id='".$ledgerid."'";
	$resultqw=$conn->query($qry);
	if ($resultqw->num_rows>0) {
		$rowd=$resultqw->fetch_assoc();
		$mindate=date('d-m-Y',strtotime($rowd['mindate']));
		$maxdate=date('d-m-Y',strtotime($rowd['maxdate']));
		$dattime="From: ".$mindate." to: ".$maxdate;
	}
	return $dattime;
}

function getcolumnsnamefrommaster($conn,$companyid,$subcompanyid,$projectid,$ledgerid){
	
	$qry="select column_name,table_name,orderlist,displayname,connected_table,connected_fields from tbl_ledgers_information where ledger_id='".$ledgerid."' and (display is null or display='' or display='Yes') order by orderlist";
	$resultlw=$conn->query($qry);
	if ($resultlw->num_rows>0) {
		while ($rowq=$resultlw->fetch_assoc()) {
			// $ary[]=array('column_name'=>$rowq['column_name'],'orderlist'=>$rowq['orderlist']);
			$ary[]=array('column_name'=>$rowq['column_name'],'displayname'=>$rowq['displayname'],'connected_table'=>$rowq['connected_table'],'connected_fields'=>$rowq['connected_fields']);
		}
	}
	return $ary;
}

function getvaluetoseletedcolumn($conn,$companyid,$subcompanyid,$projectid,$optionid,$strfield,$tablename,$sshead,$ledgerid,$optionfielname,$optionfielid)
{
	$arr=array();
	
	  if ($optionid) {
	  	 //$qry="Select ".$strfield." from ". $tablename . " where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and paytoid='".$optionid."'";
	  	$stringmal="".$optionfielid."='".$optionid."'";
	  	  $qry="Select ".$strfield." from ". $tablename . " where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ".$stringmal ;
	  }else{
	  	  $qry="Select ".$strfield." from ". $tablename . " where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' ";
	  }
	  
	 $resultv=$conn->query($qry);
	 $makearry="";
	 $rows = $resultv->fetch_all(MYSQLI_ASSOC);
	 if ($resultv->num_rows>0) {
	 	$j=1;
	 	 for ($i=0; $i < count($rows); $i++) { 
	 	 	$tr="<tr style='font-size:18px;' class='text-black'><td>".$j."</td>";
	 	 	$td="";
	 	 	foreach ($rows[$i] as $key => $value) {
            // echo $key . " => " . $value . ", ";
	 	 		$infofroconnectedtable=getconnectedtabledata($conn,$ledgerid,$key,$value);
	 	 		if ($td) {
	 	 			if ($infofroconnectedtable) {
	 	 				$td.="<td>".$infofroconnectedtable."</td>";
	 	 			}else{
	 	 			  $td.="<td>".$value."</td>";
	 	 			}
	 	 			
	 	 		}else{
	 	 			if ($infofroconnectedtable) {
	 	 			  $td="<td>".$infofroconnectedtable."</td>";
	 	 			}else{
	 	 			  $td="<td>".$value."</td>";	
	 	 			}
	 	 			
	 	 		}
	 	 		
        	}
        	$tr.=$td."</td>";
        	$arr[]=array("fieldvalue"=>$tr);
        	$j++;
	 	 }
	 		 	
	 }

	 return $arr;
}

function getvaluetoseletedcolumn_old($conn,$companyid,$subcompanyid,$projectid,$paytoid,$strfield,$tablename,$sshead)
{
	$arr=array();
	
	
	  $qry="Select ".$strfield." from ". $tablename . " where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and paytoid='".$paytoid."'";
	 $resultv=$conn->query($qry);
	 $makearry="";
	 if ($resultv->num_rows>0) {
	 	while ($rowv=$resultv->fetch_assoc()) {
	 		foreach ($sshead as $hdkey) {
	 			$columnname=$hdkey['column_name'];
	 			if ($makearry) {
	 				//$makearry.=",".$columnname."=>".$rowv[$columnname];
	 				$makearry.="<td>".$rowv[$columnname]."</td>";
	 			}else{
	 				//$columnname."=>".$rowv[$columnname];
	 				$makearry="<tr><td>".$rowv[$columnname]."</td>";
	 			}
	 			
	 		}

	 		

	 	}
	 }

	 return $makearry;
}
function getconnectedtabledata($conn,$ledgerid,$key,$id)
{
	$qryc="Select connected_table,connected_fields,where_conditionfields from tbl_ledgers_information where ledger_id='".$ledgerid."' and column_name='".$key."' and (connected_table is not null and connected_table<>'') and (connected_fields is not null and connected_fields<>'') and (where_conditionfields is not null and where_conditionfields<>'') "	;
	$resultc=$conn->query($qryc);
	if ($resultc->num_rows>0) {
		$makestringdisplay="";
		$rowq=$resultc->fetch_assoc();

		

		// $sltstring="Select ".$rowq['connected_fields']." from ".$rowq['connected_table']." where accountid='".$id."'" ;

		// $sltstring="Select ".$rowq['connected_fields']." from ".$rowq['connected_table']." where accountid='".$id."'" ;

		$sltstring="Select ".$rowq['connected_fields']." from ".$rowq['connected_table']." where ".$rowq['where_conditionfields']."='".$id."'";

		$resulty=$conn->query($sltstring);
		if ($resulty->num_rows>0) {
			$rowt=$resulty->fetch_assoc();
			if (preg_match('/,/', $rowq['connected_fields'])) {
				$arstrin=explode(",", $rowq['connected_fields']);
				for ($i=0; $i <count($arstrin) ; $i++) { 
					if ($makestringdisplay) {
						$makestringdisplay.="/".$rowt[$arstrin[$i]];
					}else{
						$makestringdisplay=$rowt[$arstrin[$i]];
					}
				}
			    
			} else {
			   $makestringdisplay=$rowt[$rowq['connected_fields']]; 
			}
		}

		return $makestringdisplay;
	}
}
function ChronologicaRegisteOfWithdrawals($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcode){
	
	$finalarray=array();
	$tr="";
	$j=1;
	$deltqry="Delete from strs_9_amacr1 where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and ledger_id='".$ledgerid."'";
	$resultdelt = $conn->query($deltqry) or die($conn->error);
	 $qrymain="select x.reference_no,x.bookdate,x.payto,x.beneficierie,y.beneficiary_name,x.book_amount,y.purpose_code,y.purpose,y.purpose_category,y.purpose_subcategory,x.transaction_date,x.payfromaccountno,x.transaction_type,x.transactionno,x.reconciliation,ifnull(y.documentation_path,'') as documentation_path ,ifnull(y.audiofilepath,'') as audiofilepath from tbl_maintransaction as x join tbl_maintransaction_breakdown as y on x.reference_no=y.reference_no where  x.companyid='".$companyid."' and x.subcompanyid='".$subcompanyid."' and x.projectid='".$projectid."' and (x.tedit is null or x.tedit='')   ";
	
	$strigforledgerinfo="referenceno,paidto,singlepurposeormultiplepurpose,finalbeneficiaryorbeneficiaries,amountapprovedforpayment,purpose,purposecode,category,subcategory,description,dateofpayment,actualamountpaid,paidfrombankaccountnumber,modeofpayment,chequenumberorbanktransactionnumber,paymentreceiptdocumentpath,reconciledwiththebank,bankstatementtransactionnumber,amountwithdrown,supportingdocumentpath,voicefileoftherequester,requestnumber,dateofrequest,requestpaidto,requestsinglepurposeormultiplepurpose,requestfinalbeneficiaryorbeneficiaries,totalamountrequestedtobepaid,subproject,approvalstatus,paidreferenceno,requestpurpose,requestpurposecode,requestcategory,requestsubcategory,requestdescription,dateofapproval,commentsbytheapprove,ledger_id,bookdate";
	
	$resultmain=$conn->query($qrymain);
		if ($resultmain->num_rows>0) {
			$insertquery="Insert into strs_9_amacr1(companyid,subcompanyid,projectid,referenceno,paidto,singlepurposeormultiplepurpose,finalbeneficiaryorbeneficiaries,amountapprovedforpayment,purpose,purposecode,category,subcategory,description,dateofpayment,actualamountpaid,paidfrombankaccountnumber,modeofpayment,chequenumberorbanktransactionnumber,paymentreceiptdocumentpath,reconciledwiththebank,bankstatementtransactionnumber,amountwithdrown,supportingdocumentpath,voicefileoftherequester,requestnumber,dateofrequest,requestpaidto,requestsinglepurposeormultiplepurpose,requestfinalbeneficiaryorbeneficiaries,totalamountrequestedtobepaid,subproject,approvalstatus,paidreferenceno,requestpurpose,requestpurposecode,requestcategory,requestsubcategory,requestdescription,dateofapproval,commentsbytheapprove,ledger_id,bookdate)values";
			//$insertquery="Insert into strs_9_amacr1(companyid,subcompanyid,projectid,referenceno,paidto)values";
			// $tr="<tr style='font-size:18px;'>";
			while ($rowmain=$resultmain->fetch_assoc()) {
				if ($tr) {
				  $tr.="<tr style='font-size:18px;'>";
				}else{
				  $tr="<tr style='font-size:18px;'>";
				}
				
				$transferedamtinbank=get_transferedamount($conn,$companyid,$subcompanyid,$projectid,$rowmain['reference_no']);
				// $providedcode=get_providedcode($conn,$companyid,$subcompanyid,$projectid,$rowmain['purpose_code']);	
				$providedcode="";
			 	$categoryname="";
			 	$subcategoryname="";
			 $pr=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$rowmain['purpose_code']);	
			 foreach ($pr as $keypr) {
			 	$providedcode=$keypr['provided_code'];
			 	$categoryname=$keypr['pcategory'];
			 	$subcategoryname=$keypr['psub_category'];
			 }
				 $qry_req="Select a.request_no,a.request_date,a.payto,beneficiery_type,a.beneficiery_name,a.request_amount,ifnull(a.subprojectid,'') as subprojectid,ifnull(a.approval_status,'') as approval_status,ifnull(a.paid_for_ref,'') as paid_for_ref,b.purpose,b.purpose_code,b.purpose_category,b.purpose_subcategory,b.description,b.audiofilepath,b.filepath,a.requestapprovedate,ifnull(a.approval_comment,'') as approval_comment from tbl_paymentrequest as a join tbl_paymentrequest_breakdown as b on a.request_no=b.request_no where a.companyid='".$companyid."' and a.subcompanyid='".$subcompanyid."' and a.projectid='".$projectid."' and (a.tedit is null or a.tedit='') and a.paid_for_ref='".$rowmain['reference_no']."'";
				
					  
					$result_req=$conn->query($qry_req);
				
					if ($result_req->num_rows>0) {
						while ($rowreq=$result_req->fetch_assoc()) {
							
						 //$providedcode2=get_providedcode($conn,$companyid,$subcompanyid,$projectid,$rowreq['purpose_code']);
							$providedcode2="";
						 	$categoryname2="";
						 	$subcategoryname2="";
							 $pr=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$rowreq['purpose_code']);	
							 foreach ($pr as $keypr) {
							 	$providedcode2=$keypr['provided_code'];
							 	$categoryname2=$keypr['pcategory'];
							 	$subcategoryname2=$keypr['psub_category'];
							 }

						  	$insertquery="Insert into strs_9_amacr1(companyid,subcompanyid,projectid,referenceno,paidto,singlepurposeormultiplepurpose,finalbeneficiaryorbeneficiaries,amountapprovedforpayment,purpose,purposecode,category,subcategory,description,dateofpayment,actualamountpaid,paidfrombankaccountnumber,modeofpayment,chequenumberorbanktransactionnumber,paymentreceiptdocumentpath,reconciledwiththebank,bankstatementtransactionnumber,amountwithdrown,supportingdocumentpath,voicefileoftherequester,requestnumber,dateofrequest,requestpaidto,requestsinglepurposeormultiplepurpose,requestfinalbeneficiaryorbeneficiaries,totalamountrequestedtobepaid,subproject,approvalstatus,paidreferenceno,requestpurpose,requestpurposecode,requestcategory,requestsubcategory,requestdescription,dateofapproval,commentsbytheapprove,ledger_id,bookdate)values('".$companyid."','".$subcompanyid."','".$projectid."','".$rowmain['reference_no']."','".addslashes($rowmain['payto'])."','".$rowmain['beneficierie']."','".addslashes($rowmain['beneficiary_name'])."','".$rowmain['book_amount']."','".addslashes($rowmain['purpose'])."','".$providedcode."','".addslashes($categoryname)."','".addslashes($subcategoryname)."','".addslashes($rowreq['description'])."','".$rowmain['transaction_date']."','".$rowmain['book_amount']."','".$rowmain['payfromaccountno']."','".$rowmain['transaction_type']."','".$rowmain['transactionno']."','','".$rowmain['reconciliation']."','".$rowmain['transactionno']."','".$transferedamtinbank."','".$rowmain['documentation_path']."','".$rowmain['audiofilepath']."','".$rowreq['request_no']."','".$rowreq['request_date']."','".addslashes($rowreq['payto'])."','".$rowreq['beneficiery_type']."','".$rowreq['beneficiery_name']."','".$rowreq['request_amount']."','".$rowreq['subprojectid']."','".$rowreq['approval_status']."','".$rowreq['paid_for_ref']."','".$rowreq['purpose']."','".$providedcode2."','".$categoryname2."','".$subcategoryname2."','".addslashes($rowreq['description'])."','".$rowreq['requestapprovedate']."','".addslashes($rowreq['approval_comment'])."','".$ledgerid."','".$rowmain['bookdate']."')";
						  	$result = $conn->query($insertquery) or die($conn->error);
							if ($result) {
								$status="Done";
							}

						 	
						}
					}else{

						$insertquery="Insert into strs_9_amacr1(companyid,subcompanyid,projectid,referenceno,paidto,singlepurposeormultiplepurpose,finalbeneficiaryorbeneficiaries,amountapprovedforpayment,purpose,purposecode,category,subcategory,description,dateofpayment,actualamountpaid,paidfrombankaccountnumber,modeofpayment,chequenumberorbanktransactionnumber,paymentreceiptdocumentpath,reconciledwiththebank,bankstatementtransactionnumber,amountwithdrown,supportingdocumentpath,voicefileoftherequester,requestnumber,dateofrequest,requestpaidto,requestsinglepurposeormultiplepurpose,requestfinalbeneficiaryorbeneficiaries,totalamountrequestedtobepaid,subproject,approvalstatus,paidreferenceno,requestpurpose,requestpurposecode,requestcategory,requestsubcategory,requestdescription,dateofapproval,commentsbytheapprove,ledger_id,bookdate)values('".$companyid."','".$subcompanyid."','".$projectid."','".$rowmain['reference_no']."','".addslashes($rowmain['payto'])."','".addslashes($rowmain['beneficierie'])."','".addslashes($rowmain['beneficiary_name'])."','".$rowmain['book_amount']."','".addslashes($rowmain['purpose'])."','".$providedcode."','".addslashes($categoryname)."','".addslashes($subcategoryname)."','".addslashes($rowreq['description'])."','".$rowmain['transaction_date']."','".$rowmain['book_amount']."','".$rowmain['payfromaccountno']."','".$rowmain['transaction_type']."','".$rowmain['transactionno']."','','".$rowmain['reconciliation']."','".$rowmain['transactionno']."','".$transferedamtinbank."','".$rowmain['documentation_path']."','".$rowmain['audiofilepath']."','','','','','','','','','','','','','','','','','".$ledgerid."','".$rowmain['bookdate']."')";
						
							$result = $conn->query($insertquery) or die($conn->error);
							if ($result) {
								$status="Done";
							}
						
						
					}

				
			}	
		}

		$explodedstring=explode(',', $strigforledgerinfo);
		// print_r($explodedstring);
		for ($i=0; $i <count($explodedstring) ; $i++) { 
			 $fildname=$explodedstring[$i];
			
			$qrysl=$conn->query("Select column_name from tbl_ledgers_information where company_id='".$companyid."' and subcompany_id='".$subcompanyid."' and project_id='".$projectid."' and ledger_id='".$ledgerid."' and column_name='". $fildname."'");
			if ($qrysl->num_rows>0) {
				// code...
			}else{
				$inssertled=$conn->query("Insert into tbl_ledgers_information(company_id,subcompany_id,project_id,ledger_name,ledger_id,column_name,displayname,orderlist,display)values('".$companyid."','".$subcompanyid."','".$projectid."','".$selectoptionname."','".$ledgerid."','".$fildname."','".$fildname."','0','Yes')");
			}
		}


		
		
		return $tr;
}

function ChronologicaRegisteOfWithdrawals_json($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcode){
	
	$finalarray=array();
	$tr="";
	$qrymain="select x.reference_no,x.bookdate,x.payto,x.beneficierie,y.beneficiary_name,x.book_amount,y.purpose_code,y.purpose,y.purpose_category,y.purpose_subcategory,x.transaction_date,x.payfromaccountno,x.transaction_type,x.transactionno,x.reconciliation,ifnull(y.documentation_path,'') as documentation_path ,ifnull(y.audiofilepath,'') as audiofilepath from tbl_maintransaction as x join tbl_maintransaction_breakdown as y on x.reference_no=y.reference_no where  x.companyid='".$companyid."' and x.subcompanyid='".$subcompanyid."' and x.projectid='".$projectid."' and (x.tedit is null or x.tedit='')";
	
	
	
	$resultmain=$conn->query($qrymain);
		if ($resultmain->num_rows>0) {
			while ($rowmain=$resultmain->fetch_assoc()) {
				
				
				$transferedamtinbank=get_transferedamount($conn,$companyid,$subcompanyid,$projectid,$rowmain['reference_no']);	
				$qry_req="Select a.request_no,a.request_date,a.payto,beneficiery_type,a.beneficiery_name,a.request_amount,ifnull(a.subprojectid,'') as subprojectid,ifnull(a.approval_status,'') as approval_status,ifnull(a.paid_for_ref,'') as paid_for_ref,b.purpose,b.purpose_code,b.purpose_category,b.purpose_subcategory,b.description,b.audiofilepath,b.filepath,a.requestapprovedate,ifnull(a.approval_comment,'') as approval_comment from tbl_paymentrequest as a join tbl_paymentrequest_breakdown as b on a.request_no=b.request_no where a.companyid='".$companyid."' and a.subcompanyid='".$subcompanyid."' and a.projectid='".$projectid."' and (a.tedit is null or a.tedit='') and a.paid_for_ref='".$rowmain['reference_no']."'";
				
					  
					$result_req=$conn->query($qry_req);
				
					if ($result_req->num_rows>0) {
			
						while ($rowreq=$result_req->fetch_assoc()) {
							
						 $finalarray[]=array('x_reference_no'=>$rowmain['reference_no'],'a_request_no'=>$rowreq['request_no'],'a_request_date'=>$rowreq['request_date'],'a_payto'=>$rowreq['payto'],'a_beneficiery_type'=>$rowreq['beneficiery_type'],'a_beneficiery_name'=>$rowreq['beneficiery_name'],'a_request_amount'=>$rowreq['request_amount'],'a_subprojectid'=>$rowreq['subprojectid'],'a_approval_status'=>$rowreq['approval_status'],'a_paid_for_ref'=>$rowreq['paid_for_ref'],'a_purpose'=>$rowreq['purpose'],'a_purpose_code'=>$rowreq['purpose_code'],'a_purpose_category'=>$rowreq['purpose_category'],'a_purpose_subcategory'=>$rowreq['purpose_subcategory'],'a_description'=>$rowreq['description'],'a_audiofilepath'=>$rowreq['audiofilepath'],'a_filepath'=>$rowreq['filepath'],'a_requestapprovedate'=>$rowreq['requestapprovedate'],'x_bookdate'=>$rowmain['bookdate'],'x_payto'=>$rowmain['payto'],'x_beneficierie'=>$rowmain['beneficierie'],'x_beneficiary_name'=>$rowmain['beneficiary_name'],'x_book_amount'=>$rowmain['book_amount'],'x_purpose_code'=>$rowmain['purpose_code'],'x_purpose'=>$rowmain['purpose'],'x_purpose_category'=>$rowmain['purpose_category'],'x_purpose_subcategory'=>$rowmain['purpose_subcategory'],'a_approval_comment'=>$rowreq['approval_comment'],'x_transaction_date'=>$rowmain['transaction_date'],'x_amountpaid'=>$rowmain['book_amount'],'x_payfromaccountno'=>$rowmain['payfromaccountno'],'x_transaction_type'=>$rowmain['transaction_type'],'x_transactionno'=>$rowmain['transactionno'],'x_reconciliation'=>$rowmain['reconciliation'],'x_amountwithdrawn'=>$transferedamtinbank,'x_documentation_path'=>$rowmain['documentation_path'],'x_audiofilepath'=>$rowmain['audiofilepath']);

						 
						}
					}else{
						$finalarray[]=array('x_reference_no'=>$rowmain['reference_no'],'a_request_no'=>'','a_request_date'=>'','a_payto'=>'','a_beneficiery_type'=>'','a_beneficiery_name'=>'','a_request_amount'=>'','a_subprojectid'=>'','a_approval_status'=>'','a_paid_for_ref'=>'','a_purpose'=>'','a_purpose_code'=>'','a_purpose_category'=>'','a_purpose_subcategory'=>'','a_description'=>'','a_audiofilepath'=>'','a_filepath'=>'','a_requestapprovedate'=>'','approval_comment'=>'','x_bookdate'=>$rowmain['bookdate'],'x_payto'=>$rowmain['payto'],'x_beneficierie'=>$rowmain['beneficierie'],'x_beneficiary_name'=>$rowmain['beneficiary_name'],'x_book_amount'=>$rowmain['book_amount'],'x_purpose_code'=>$rowmain['purpose_code'],'x_purpose'=>$rowmain['purpose'],'x_purpose_category'=>$rowmain['purpose_category'],'x_purpose_subcategory'=>$rowmain['purpose_subcategory'],'a_approval_comment'=>'','x_transaction_date'=>$rowmain['transaction_date'],'x_amountpaid'=>$rowmain['book_amount'],'x_payfromaccountno'=>$rowmain['payfromaccountno'],'x_transaction_type'=>$rowmain['transaction_type'],'x_transactionno'=>$rowmain['transactionno'],'x_reconciliation'=>$rowmain['reconciliation'],'x_amountwithdrawn'=>$transferedamtinbank,'x_documentation_path'=>$rowmain['documentation_path'],'x_audiofilepath'=>$rowmain['audiofilepath']);

						
					}

					
			}
			
		}

   return $finalarray;		
}

function paytostatementfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcode){
	$tr="";
	

	if ($d1==""||$d2=="") {
		 $qry="Select m.reference_no,m.transactionno,m.bookdate,m.book_amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath  from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.paytoid='".$selectoptionid."' and (m.tedit is null or m.tedit='')";
	}else{
		$qry="Select m.reference_no,m.transactionno,m.bookdate,m.book_amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.paytoid='".$paytoid."' and (m.tedit is null or m.tedit='') and (m.bookdate between '".$d1."' and '".$d2."')";
	}

	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(bookdate) as mindate,max(bookdate) as maxdate from tbl_maintransaction where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}

	}else{			$getfromtoddate="";
		 $mindate=date('d-m-Y',strtotime($d1));
		 $maxdate=date('d-m-Y',strtotime($d2));
		 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	$resltpto=$conn->query($qry);
	if ($resltpto->num_rows>0) {
		$j=1;
		if ($extended) {
				$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='8' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='8' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='8' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='8' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='8' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='8' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of Transaction :</th><th colspan='8' class='' >".$selectoptionname."</th></tr>";


		  		$tr.="<th>Sno</th><th width='200px;'>Book Date</th><th width='200px;'>Reference No</th><th width='200px;'>Transaction No.</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='200px;'>Purpose</th><th width='300px;'>Pay From Account</th><th>Request Description</th><th>Supporting Documents</th><th>Payment Documents</th></thead>";
		  		$tr.="<tbody>";
			   while ($rowpto=$resltpto->fetch_assoc()) {
				$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
				$tr.="<tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['transactionno']."</td><td>".$rowpto['book_amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$accinfo."</td><td>".$rowpto['description']."</td><td>".$rowpto['documentation_path']."</td><td>".$rowpto['transferreceiptpath']."</td>";
				$tr.="</tr>";
				$j++;
			}
			$tr.="</tbody>";

		}else{
			$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='5' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='5' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='5' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='5' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='5' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='5' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of Transaction :</th><th colspan='5' class='' >".$selectoptionname."</th></tr>";


		  		$tr.="<th>Sno</th><th width='250px;'>Book Date</th><th width='250px;'>Reference No</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='250px;'>Purpose</th><th width='350px;'>Pay From Account</th><th>Request Description</th></thead>";
		  		$tr.="<tbody>";
			   while ($rowpto=$resltpto->fetch_assoc()) {
				$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
				$tr.="<tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['book_amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$accinfo."</td><td>".$rowpto['description']."</td>";
				$tr.="</tr>";
				$j++;
			}
			$tr.="</tbody>";
		}
		

		
	}

	return $tr;

}

function beneficiarystatementfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcode){
	$tr="";
	if ($d1==""||$d2=="") {
		$qry="Select m.reference_no,m.transactionno,m.bookdate,b.amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and b.beneficiary_name='".$selectoptionname."' and (m.tedit is null or m.tedit='')";
	}else{
		$qry="Select m.reference_no,m.transactionno,m.bookdate,b.amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and b.beneficiary_name='".$selectoptionname."' and (m.tedit is null or m.tedit='') and (m.bookdate between '".$d1."' and '".$d2."')";
	}
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(bookdate) as mindate,max(bookdate) as maxdate from tbl_maintransaction where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	

	$resltpto=$conn->query($qry);
	if ($resltpto->num_rows>0) {
		$j=1;

		if ($extended) {
			$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='8' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='8' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='8' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='8' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='8' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='8' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of :</th><th colspan='8' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th>Sno</th><th width='200px;'>Book Date</th><th width='200px;'>Reference No</th><th width='200px;'>Transaction No.</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='200px;'>Purpose</th><th width='400px;'>Description</th><th width='300px;'>Pay From Account</th><th>Supporting Documents</th><th>Payment Documents</th></thead>";
			while ($rowpto=$resltpto->fetch_assoc()) {
				$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
				$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['transactionno']."</td><td>".$rowpto['amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$rowpto['description']."</td><td>".$accinfo."</td><td>".$rowpto['documentation_path']."</td><td>".$rowpto['transferreceiptpath']."</td>";
				$tr.="</tr>";
		}
		$tr.="</tbody>";

		}else{

			$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='7' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='7' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='7' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='7' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='7' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='7' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of :</th><th colspan='7' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th>Sno</th><th width='200px;'>Book Date</th><th width='200px;'>Reference No</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='200px;'>Purpose</th><th width='400px;'>Description</th><th width='300px;'>Pay From Account</th></thead>";
			while ($rowpto=$resltpto->fetch_assoc()) {
				$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
				$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$rowpto['description']."</td><td>".$accinfo."</td>";
				$tr.="</tr>";
			}
			$tr.="</tbody>";
		}
	}

	return $tr;

}  

function paytoandbeneficeryfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcode){

	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(bookdate) as mindate,max(bookdate) as maxdate from tbl_maintransaction where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}


	$paytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoname',$selectoptionname);
	if (count($paytoinfo)>0) {
		foreach ($paytoinfo as $keyp) {
			$paytoid=$keyp['paytoid'];
			$paytoname=$keyp['payto'];

			if ($d1==""||$d2=="") {
		 		 $qry="Select m.reference_no,m.transactionno,m.bookdate,m.book_amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath  from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.paytoid='".$paytoid."' and (m.tedit is null or m.tedit='')";
			}else{
				$qry="Select m.reference_no,m.transactionno,m.bookdate,m.book_amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and m.paytoid='".$paytoid."' and (m.tedit is null or m.tedit='') and (m.bookdate between '".$d1."' and '".$d2."')";
			}

			$resltpto=$conn->query($qry);
			if ($resltpto->num_rows>0) {
				$j=1;
				if ($extended) {
						$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='8' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='8' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='8' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='8' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='8' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='8' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of Transaction :</th><th colspan='8' class='' >".$selectoptionname."</th></tr>";


				  		$tr.="<th>Sno</th><th width='200px;'>Book Date</th><th width='200px;'>Reference No</th><th width='200px;'>Transaction No.</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='200px;'>Purpose</th><th width='300px;'>Pay From Account</th><th>Request Description</th><th>Supporting Documents</th><th>Payment Documents</th></thead>";
				  		$tr.="<tbody>";
					   while ($rowpto=$resltpto->fetch_assoc()) {
						$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
						$tr.="<tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['transactionno']."</td><td>".$rowpto['book_amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$accinfo."</td><td>".$rowpto['description']."</td><td>".$rowpto['documentation_path']."</td><td>".$rowpto['transferreceiptpath']."</td>";
						$tr.="</tr>";
						$j++;
					}
					$tr.="</tbody>";

				}else{
					$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='5' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='5' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='5' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='5' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='5' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='5' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of Transaction :</th><th colspan='5' class='' >".$selectoptionname."</th></tr>";


				  		$tr.="<th>Sno</th><th width='250px;'>Book Date</th><th width='250px;'>Reference No</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='250px;'>Purpose</th><th width='350px;'>Pay From Account</th><th>Request Description</th></thead>";
				  		$tr.="<tbody>";
					   while ($rowpto=$resltpto->fetch_assoc()) {
						$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
						$tr.="<tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['book_amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$accinfo."</td><td>".$rowpto['description']."</td>";
						$tr.="</tr>";
						$j++;
					}
					$tr.="</tbody>";
				}
			}
		}	
	}else{

		if ($d1==""||$d2=="") {
		$qry="Select m.reference_no,m.transactionno,m.bookdate,b.amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and b.beneficiary_name='".$selectoptionname."' and (m.tedit is null or m.tedit='')";
	}else{
		$qry="Select m.reference_no,m.transactionno,m.bookdate,b.amount,m.payfrombankname,m.payfromaccountno,m.paymenttype,b.purpose, b.description,ifnull(b.documentation_path,'') as documentation_path,ifnull(m.transferreceiptpath,'') as transferreceiptpath from tbl_maintransaction as m join tbl_maintransaction_breakdown as b on m.reference_no=b.reference_no where m.companyid='".$companyid."' and m.subcompanyid='".$subcompanyid."' and m.projectid='".$projectid."' and b.beneficiary_name='".$selectoptionname."' and (m.tedit is null or m.tedit='') and (m.bookdate between '".$d1."' and '".$d2."')";
	}
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(bookdate) as mindate,max(bookdate) as maxdate from tbl_maintransaction where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	

	$resltpto=$conn->query($qry);
	if ($resltpto->num_rows>0) {
		$j=1;

		if ($extended) {
			$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='8' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='8' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='8' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='8' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='8' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='8' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of :</th><th colspan='8' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th>Sno</th><th width='200px;'>Book Date</th><th width='200px;'>Reference No</th><th width='200px;'>Transaction No.</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='200px;'>Purpose</th><th width='400px;'>Description</th><th width='300px;'>Pay From Account</th><th>Supporting Documents</th><th>Payment Documents</th></thead>";
			while ($rowpto=$resltpto->fetch_assoc()) {
				$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
				$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['transactionno']."</td><td>".$rowpto['amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$rowpto['description']."</td><td>".$accinfo."</td><td>".$rowpto['documentation_path']."</td><td>".$rowpto['transferreceiptpath']."</td>";
				$tr.="</tr>";
		}
		$tr.="</tbody>";

		}else{

			$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='3' >Report No.:</th><th colspan='7' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='3'>Description:</th><th colspan='7' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Project:</th><th colspan='7' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Company:</th><th colspan='7' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >From beginning to till date:</th><th colspan='7' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='3' >Generated on date & time:</th><th colspan='7' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='3' >Statement Of :</th><th colspan='7' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th>Sno</th><th width='200px;'>Book Date</th><th width='200px;'>Reference No</th><th width='200px;'>Paid Amount</th><th width='200px;'>Recived Amount</th><th width='200px;'>Purpose</th><th width='400px;'>Description</th><th width='300px;'>Pay From Account</th></thead>";
			while ($rowpto=$resltpto->fetch_assoc()) {
				$accinfo=$rowpto['payfromaccountno']."/".$rowpto['payfrombankname'];
				$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>".date('d-m-Y',strtotime($rowpto['bookdate']))."</td><td>".$rowpto['reference_no']."</td><td>".$rowpto['amount']."</td><td></td><td>".$rowpto['purpose']."</td><td>".$rowpto['description']."</td><td>".$accinfo."</td>";
				$tr.="</tr>";
			}
			$tr.="</tbody>";
		}
	}

	}

	return $tr;
}

function UtilityBillsCategoryfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(bookdate) as mindate,max(bookdate) as maxdate from tbl_maintransaction where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;
	

	if ($selectoptionid=='all') {
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='' >Report No.:</th><th colspan='4' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan=''>Description:</th><th colspan='4' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Project:</th><th colspan='4' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Company:</th><th colspan='4' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >From beginning to till date:</th><th colspan='4' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Generated on date & time:</th><th colspan='4' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >Statement Of :</th><th colspan='4' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Type of Bill</th><th width='200px;'>Total amount paid</th><th width='200px;'>Any refunds</th><th width='200px;'>Net payment</th></thead>";

		$infobillmaster=getbillmasterandbilltypemasterinfodetails($conn,$companyid,$subcompanyid,$projectid,'all','');

		if ($d1==""||$d2=="") {
			foreach ($infobillmaster as $billkey) {
				$billcode=$billkey['purposecode'];
				$billname=$billkey['typename'];
				$id=$billkey['id'];
				$totalamt=gettotalamountofbill($conn,$companyid,$subcompanyid,$projectid,$billkey['id'],$d1,$d2);
				// $qry="select sum(amount) as totalamount  from tbl_maintransaction_breakdown where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and purpose_code='".$billcode."'";
				// $resultb=$conn->query($qry);
				// if ($resultb->num_rows>0) {
				// 	$rowb=$resultb->fetch_assoc();
				// 	$tr.="<tbody><tr class='clickme ".$id."' ><td>".$j."</td><td>".$billname."</td><td>".$rowb['totalamount']."</td><td></td><td>".$rowb['totalamount']."</td>";
				// 	$tr.="</tr>";
				// 	$j++;
				// }
				$tr.="<tbody><tr class='clickme ".$id." text-light bg-dark' ><td>".$j."</td><td>".$billname."</td><td>".$totalamt."</td><td></td><td>".$totalamt."</td>";
					$tr.="</tr>";
					$j++;
				$tr.="</tbody>";
				
			}

		}else{
			foreach ($infobillmaster as $billkey) {
				$billcode=$billkey['purposecode'];
				$billname=$billkey['typename'];
				$id=$billkey['id'];
			 $qry="select a.reference_no, sum(b.amount) as totalamount  from tbl_maintransaction as a join tbl_maintransaction_breakdown as b  on a.reference_no=b.reference_no where a.companyid='".$companyid."' and a.subcompanyid='".$subcompanyid."' and a.projectid='".$projectid."' and b.purpose_code='".$billcode."' and (a.bookdate between '".$d1."' and '".$d2."') ";
			$resultb=$conn->query($qry);
				if ($resultb->num_rows>0) {
					$rowb=$resultb->fetch_assoc();
					$tr.="<tbody><tr class='clickme  text-light bg-dark' ><td>".$j."</td><td>".$billname."</td><td>".$rowb['totalamount']."</td><td></td><td>".$rowb['totalamount']."</td>";
					$tr.="</tr>";
					$j++;
				}
			}
				$tr.="</tbody>";
					
		}
	}
	// else{
	// 	$billcode="";
	// 	$billname="";
	// 	$id="";
	// 	$typeofbill="";
	// 	$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='' >Report No.:</th><th colspan='4' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan=''>Description:</th><th colspan='4' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Project:</th><th colspan='4' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Company:</th><th colspan='4' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >From beginning to till date:</th><th colspan='4' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Generated on date & time:</th><th colspan='4' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >Statement Of :</th><th colspan='4' class='' >".$selectoptionname."</th></tr>";
	// 		$tr.="<th width='200px;'>Sno</th><th width='400px;'>Company Name</th><th width='200px;'>Date of Bill raised</th><th width='200px;'>Bill No</th><th width='200px;'>Amount demanded</th><th width='200px;'>Payment date</th><th width='200px;'>Amount paid</th><th width='200px;'>Amount returned</th><th width='200px;'>Balance</th><th width='200px;'>Status</th><th width='200px;'>Document</th></thead>";
	// 	$infobillmaster=getbillmasterandbilltypemasterinfodetailsdetails($conn,$companyid,$subcompanyid,$projectid,'byid',$selectoptionid);


	// 		foreach ($infobillmaster as $billkey) {
	// 			 $billcode=$billkey['purposecode'];
	// 			 $paytoid=$billkey['paytoid'];
	// 			 $beneficiaryname=$billkey['beneficiaryname'];
	// 			 $billingdate=$billkey['billingdate'];
	// 			 $referenceno=$billkey['referenceno'];
	// 			 $paytoascompanyname="";
	// 			 $paytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoid',$paytoid);
	// 			 foreach ($paytoinfo as $keyp) {
	// 			 	$paytoascompanyname=$keyp['paytoname'];
	// 			 }

	// 			if ($d1==""||$d2=="") {

			 

	// 		 	  echo $qry="select a.reference_no, b.amount as totalamount  from tbl_maintransaction as a join tbl_maintransaction_breakdown as b on a.reference_no=b.reference_no where a.companyid='".$companyid."' and a.subcompanyid='".$subcompanyid."' and a.projectid='".$projectid."' and b.purpose_code='".$billcode."' and b.reference_no='".$referenceno."' ";
	// 			}else{
	// 				echo $qry="select a.reference_no, b.amount as totalamount  from tbl_maintransaction as a join tbl_maintransaction_breakdown as b on a.reference_no=b.reference_no where a.companyid='".$companyid."' and a.subcompanyid='".$subcompanyid."' and a.projectid='".$projectid."' and b.purpose_code='".$billcode."' and (a.bookdate between '".$d1."' and '".$d2."') and b.reference_no='".$referenceno."' ";
	// 			}

	// 			$resultb=$conn->query($qry);
	// 			if ($resultb->num_rows>0) {
	// 				$rowb=$resultb->fetch_assoc();
	// 				$tr.="<tbody><tr><td>".$j."</td><td>".$paytoascompanyname."</td><td>".$billingdate."</td><td></td><td>".$rowb['totalamount']."</td>";
	// 				$tr.="</tr>";
	// 				$j++;
	// 			}
	// 			$tr.="</tbody>";

	// 		}

		
				
	// }

	return $tr;
	
}

function impreststatementsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");
	$totalimprestamount=0;
	$totalimprestadjamount=0;

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(idate) as mindate,max(idate) as maxdate from imprest_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;
	

	if ($selectoptionid=='all') {
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='' >Report No.:</th><th colspan='4' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan=''>Description:</th><th colspan='4' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Project:</th><th colspan='4' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Company:</th><th colspan='4' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >From beginning to till date:</th><th colspan='4' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Generated on date & time:</th><th colspan='4' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >Statement Of :</th><th colspan='4' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Imprest Paid to</th><th width='200px;'>Total Amount Paid</th><th width='200px;'>Tortal Imprest adjusted</th><th width='200px;'>Balance</th></thead>";

		$infoimprest=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,'all','');

		if ($d1==""||$d2=="") {
			foreach ($infoimprest as $impkey) {
				
				$impname=$impkey['imprestpayto'];
				$id=$impkey['imprestpaytoid'];
				$totalamt=gettotalamountofimprestpayto($conn,$companyid,$subcompanyid,$projectid,$impkey['imprestpaytoid'],$d1,$d2);
				$imprestamount=0;
				$imprestadjamount=0;
				$imprestbalanceamount=0;
				foreach ($totalamt as $keyimp) {
					$imprestamount=$keyimp['imprestamount'];
					$imprestadjamount=$keyimp['imprestadjamount'];
					$imprestbalanceamount=$keyimp['imprestbalanceamount'];
				}
				$totalimprestamount=$totalimprestamount+$imprestamount;
				$totalimprestadjamount=$totalimprestadjamount+$imprestadjamount;
				$tr.="<tbody><tr class='clickme ".$id."  text-light bg-dark' ><td>".$j."</td><td>".$impname."</td><td>".$imprestamount."</td><td>".$imprestadjamount."</td><td>".$imprestbalanceamount."</td>";
					$tr.="</tr>";
					$j++;
				
			}
			$totalbalanceamt=$totalimprestamount-$totalimprestadjamount;
			$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>Total</td><td>".$totalimprestamount."</td><td>".$totalimprestadjamount."</td><td>".$totalbalanceamt."</td>";
					$tr.="</tr>";

			$tr.="</tbody>";

		}else{
			foreach ($infoimprest as $impkey) {
				$impname=$impkey['imprestpayto'];
				$id=$impkey['imprestpaytoid'];
			$totalamt=gettotalamountofimprestpayto($conn,$companyid,$subcompanyid,$projectid,$impkey['imprestpaytoid'],$d1,$d2);

			$resultb=$conn->query($qry);
				foreach ($totalamt as $keyimp) {
					$imprestamount=$keyimp['imprestamount'];
					$imprestadjamount=$keyimp['imprestadjamount'];
					$imprestbalanceamount=$keyimp['imprestbalanceamount'];
				}

				
				
				$tr.="<tbody><tr class='clickme ".$id."  text-light bg-dark' ><td>".$j."</td><td>".$impname."</td><td>".$imprestamount."</td><td>".$imprestadjamount."</td><td>".$imprestbalanceamount."</td>";
					$tr.="</tr>";
					$j++;
				
			}
				$tr.="</tbody>";

		}
	}
	
	return $tr;
	
}

function cashstatementsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");
	$totalimprestamount=0;
	$totalimprestadjamount=0;

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(idate) as mindate,max(idate) as maxdate from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;
	

	if ($selectoptionid=='all') {
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='' >Report No.:</th><th colspan='4' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan=''>Description:</th><th colspan='4' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Project:</th><th colspan='4' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Company:</th><th colspan='4' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >From beginning to till date:</th><th colspan='4' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Generated on date & time:</th><th colspan='4' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >Statement Of :</th><th colspan='4' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Cash Paid to</th><th width='200px;'>Total Amount Paid</th><th width='200px;'>Total Cash adjusted</th><th width='200px;'>Balance</th></thead>";

		$infoimprest=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'cash','');

		if ($d1==""||$d2=="") {
			foreach ($infoimprest as $impkey) {
				
				$impname=$impkey['accountname'];
				$id=$impkey['accountid'];
				$totalamt=gettotalamountofcashpayto($conn,$companyid,$subcompanyid,$projectid,$impkey['accountid'],$d1,$d2);
				$imprestamount=0;
				$imprestadjamount=0;
				$imprestbalanceamount=0;
				foreach ($totalamt as $keyimp) {
					$imprestamount=$keyimp['imprestamount'];
					$imprestadjamount=$keyimp['imprestadjamount'];
					$imprestbalanceamount=$keyimp['imprestbalanceamount'];
				}
				$totalimprestamount=$totalimprestamount+$imprestamount;
				$totalimprestadjamount=$totalimprestadjamount+$imprestadjamount;
				$tr.="<tbody><tr class='clickme ".$id."  text-light bg-dark' ><td>".$j."</td><td>".$impname."</td><td>".$imprestamount."</td><td>".$imprestadjamount."</td><td>".$imprestbalanceamount."</td>";
					$tr.="</tr>";
					$j++;
				
			}
			$totalbalanceamt=$totalimprestamount-$totalimprestadjamount;
			$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>Total</td><td>".$totalimprestamount."</td><td>".$totalimprestadjamount."</td><td>".$totalbalanceamt."</td>";
					$tr.="</tr>";

			$tr.="</tbody>";

		}else{
			foreach ($infoimprest as $impkey) {
				$impname=$impkey['accountname'];
				$id=$impkey['accountid'];
			$totalamt=gettotalamountofimprestpayto($conn,$companyid,$subcompanyid,$projectid,$impkey['accountid'],$d1,$d2);

			$resultb=$conn->query($qry);
				foreach ($totalamt as $keyimp) {
					$imprestamount=$keyimp['imprestamount'];
					$imprestadjamount=$keyimp['imprestadjamount'];
					$imprestbalanceamount=$keyimp['imprestbalanceamount'];
				}

				
				
				$tr.="<tbody><tr class='clickme ".$id."  text-light bg-dark' ><td>".$j."</td><td>".$impname."</td><td>".$imprestamount."</td><td>".$imprestadjamount."</td><td>".$imprestbalanceamount."</td>";
					$tr.="</tr>";
					$j++;
				
			}
				$tr.="</tbody>";

		}
	}
	
	return $tr;
	
}

function creditcardstatementsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");
	$totalimprestamount=0;
	$totalimprestadjamount=0;

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(idate) as mindate,max(idate) as maxdate from creditcard_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;
	

	if ($selectoptionid=='all') {
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='' >Report No.:</th><th colspan='4' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan=''>Description:</th><th colspan='4' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Project:</th><th colspan='4' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Company:</th><th colspan='4' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >From beginning to till date:</th><th colspan='4' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Generated on date & time:</th><th colspan='4' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >Statement Of :</th><th colspan='4' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Credit Card No.</th><th width='400px;'>Credit Card User</th><th width='200px;'>Total Expenses Amount </th></thead>";

		$infoimprest=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'creditcard','');

		if ($d1==""||$d2=="") {
			foreach ($infoimprest as $impkey) {
				
				$credicardno=$impkey['accountno'];
				$id=$impkey['accountid'];
				$creditcarduserinfo=getusernameofcreditcard($conn,$companyid,$subcompanyid,$projectid,$credicardno);
				foreach ($creditcarduserinfo as $keycd) {
					$userid=$keycd['id'];
					$username=$keycd['username'];
					$totalamt=gettotalamountofcreditcard($conn,$companyid,$subcompanyid,$projectid,$impkey['accountid'],$d1,$d2,$userid);
					$imprestamount=0;
					$imprestadjamount=0;
					$imprestbalanceamount=0;
					foreach ($totalamt as $keyimp) {
						$debitamount=$keyimp['debitamount'];
						
					}
				}

				
				$totalimprestamount=$totalimprestamount+$debitamount;
				
				$tr.="<tbody><tr class='clickme ".$userid."  text-light bg-dark' ><td>".$j."</td><td>".$credicardno."</td><td>".$username."</td><td>".$debitamount."</td>";
					$tr.="</tr>";
					$j++;
				
			}
			$totalbalanceamt=$totalimprestamount-$totalimprestadjamount;
			$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>Total</td><td></td><td>".$totalimprestamount."</td>";
					$tr.="</tr>";

			$tr.="</tbody>";

		}else{
			foreach ($infoimprest as $impkey) {
				$credicardno=$impkey['accountno'];
				$id=$impkey['accountid'];
			$creditcarduserinfo=getusernameofcreditcard($conn,$companyid,$subcompanyid,$projectid,$credicardno);
				foreach ($creditcarduserinfo as $keycd) {
					$userid=$keycd['id'];
					$username=$keycd['username'];
					$totalamt=gettotalamountofcreditcard($conn,$companyid,$subcompanyid,$projectid,$impkey['accountid'],$d1,$d2,$userid);
					$imprestamount=0;
					$imprestadjamount=0;
					$imprestbalanceamount=0;
					foreach ($totalamt as $keyimp) {
						$debitamount=$keyimp['debitamount'];
						
					}
				}

				
				
				$tr.="<tbody><tr class='clickme ".$userid."  text-light bg-dark' ><td>".$j."</td><td>".$credicardno."</td><td></td><td>".$debitamount."</td>";
					$tr.="</tr>";
					$j++;
				
			}
				
				$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>Total</td><td></td><td>".$debitamount."</td>";
						$tr.="</tr>";

				$tr.="</tbody>";

		}
	}
	
	return $tr;
	
}

function loanstatementsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");
	$totalimprestamount=0;
	$totalimprestadjamount=0;

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(idate) as mindate,max(idate) as maxdate from tbl_loanaccount_summary where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;
	

	if ($selectoptionid=='all') {
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='' >Report No.:</th><th colspan='5' class='cl_reportno_2'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan=''>Description:</th><th colspan='5' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Project:</th><th colspan='5' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Company:</th><th colspan='5' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >From beginning to till date:</th><th colspan='5' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='' >Generated on date & time:</th><th colspan='5' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='' >Statement Of :</th><th colspan='5' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Loan From</th><th width='400px;'>Loan Given To</th><th width='200px;'>Total Loan Amount </th><th width='200px;'>Total Loan Returned </th><th width='200px;'>Balance</th></thead>";

		$infoimprest=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'loan','');

		if ($d1==""||$d2=="") {
			foreach ($infoimprest as $impkey) {
				
				$impname=$impkey['accountname'];
				$id=$impkey['accountid'];
				$totalamt=gettotalamountofloan($conn,$companyid,$subcompanyid,$projectid,$impkey['accountid'],$d1,$d2);
				$imprestamount=0;
				$imprestadjamount=0;
				$imprestbalanceamount=0;
				foreach ($totalamt as $keyimp) {
					$imprestamount=$keyimp['imprestamount'];
					$imprestadjamount=$keyimp['imprestadjamount'];
					$imprestbalanceamount=$keyimp['imprestbalanceamount'];
				}
				$totalimprestamount=$totalimprestamount+$imprestamount;
				$totalimprestadjamount=$totalimprestadjamount+$imprestadjamount;
				$tr.="<tbody><tr class='clickme ".$id." text-light bg-dark' ><td>".$j."</td><td>".$impname."</td><td></td><td>".$imprestadjamount."</td><td>".$imprestbalanceamount."</td><td></td>";
					$tr.="</tr>";
					$j++;
				
			}
			$totalbalanceamt=$totalimprestamount-$totalimprestadjamount;
			$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>Total</td><td></td><td>".$totalimprestadjamount."</td><td>".$totalbalanceamt."</td><td></td>";
					$tr.="</tr>";

			$tr.="</tbody>";

		}else{
			foreach ($infoimprest as $impkey) {
				$impname=$impkey['accountname'];
				$id=$impkey['accountid'];
			$totalamt=gettotalamountofimprestpayto($conn,$companyid,$subcompanyid,$projectid,$impkey['accountid'],$d1,$d2);

			$resultb=$conn->query($qry);
				foreach ($totalamt as $keyimp) {
					$imprestamount=$keyimp['imprestamount'];
					$imprestadjamount=$keyimp['imprestadjamount'];
					$imprestbalanceamount=$keyimp['imprestbalanceamount'];
				}

				
				
				$tr.="<tbody><tr class='clickme ".$id."  text-light bg-dark' ><td>".$j."</td><td>".$impname."</td><td></td><td>".$imprestamount."</td><td>".$imprestadjamount."</td><td>".$imprestbalanceamount."</td><td></td>";
					$tr.="</tr>";
					$j++;
				
			}
				$totalbalanceamt=$totalimprestamount-$totalimprestadjamount;
				$tr.="<tbody><tr class='text-light bg-dark'><td>".$j."</td><td>Total</td><td>".$totalimprestamount."</td><td>".$totalimprestadjamount."</td><td>".$totalbalanceamt."</td><td></td>";
					$tr.="</tr>";

			$tr.="</tbody>";  
			

		}
	}
	
	return $tr;
	
}

function UtilityBillsCategorydetailsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected){
	$totalamount=0;
	$totalamounttransfer=0;
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(bookdate) as mindate,max(bookdate) as maxdate from tbl_maintransaction where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;

		
		$billcode="";
		$billname="";
		$id="";
		$typeofbill="";
		$STATUS="";
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='2' >Report No.:</th><th colspan='9' class='cl_reportno_3'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='2'>Description:</th><th colspan='9' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Project:</th><th colspan='9' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Company:</th><th colspan='9' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >From beginning to till date:</th><th colspan='9' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Generated on date & time:</th><th colspan='9' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >Statement Of :</th><th colspan='9' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Company Name</th><th width='200px;'>Date of Bill raised</th><th width='200px;'>Bill No</th><th width='200px;'>Amount demanded</th><th width='200px;'>Payment date</th><th width='200px;'>Amount paid</th><th width='200px;'>Amount returned</th><th width='200px;'>Balance</th><th width='200px;'>Status</th><th width='200px;'>Document</th></thead>";
			$tr.="<tbody>";
		$infobillmaster=getbillmasterandbilltypemasterinfodetailsdetails($conn,$companyid,$subcompanyid,$projectid,'byid',$selectoptionid);


			foreach ($infobillmaster as $billkey) {
				 $billcode=$billkey['purposecode'];
				 $paytoid=$billkey['paytoid'];
				 $beneficiaryname=$billkey['beneficiaryname'];
				 $billingdate=date('d-m-Y',strtotime($billkey['billingdate']));
				 $referenceno=$billkey['referenceno'];
				 $paytoascompanyname="";
				 $paytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,'paytoid',$paytoid);
				 foreach ($paytoinfo as $keyp) {
				 	$paytoascompanyname=$keyp['paytoname'];
				 }

				if ($d1==""||$d2=="") {

			 

			 	     $qry="select a.reference_no, b.amount as totalamount,b.invoice_no,ifnull(a.transaction_date,'') as transaction_date, ifnull(a.transfered_amount,0) as transfered_amount,b.documentation_path  from tbl_maintransaction as a join tbl_maintransaction_breakdown as b on a.reference_no=b.reference_no where a.companyid='".$companyid."' and a.subcompanyid='".$subcompanyid."' and a.projectid='".$projectid."' and b.purpose_code='".$billcode."' and b.request_no='".$referenceno."' ";
				}else{
					 $qry="select a.reference_no, b.amount as totalamount,b.invoice_no,ifnull(a.transaction_date,'') as transaction_date,ifnull(a.transfered_amount,0) as a.transfered_amount ,b.documentation_path  from tbl_maintransaction as a join tbl_maintransaction_breakdown as b on a.reference_no=b.reference_no where a.companyid='".$companyid."' and a.subcompanyid='".$subcompanyid."' and a.projectid='".$projectid."' and b.purpose_code='".$billcode."' and (a.bookdate between '".$d1."' and '".$d2."') and b.request_no='".$referenceno."' ";
				}

				$resultb=$conn->query($qry);
				if ($resultb->num_rows>0) {
					$rowb=$resultb->fetch_assoc();
					if ($rowb['transaction_date']) {
						$rowb['transaction_date']=date('d-m-Y',strtotime($rowb['transaction_date']));
					}

					$totalamount=$totalamount+$rowb['totalamount'];
					$totalamounttransfer=$totalamounttransfer+$rowb['transfered_amount'];

					$balanceamt=$rowb['totalamount']-$rowb['transfered_amount'];
					if ($rowb['transfered_amount']>=$rowb['totalamount'] && $rowb['transfered_amount']>0) {
						$STATUS="Cleard";
					}else if($rowb['transfered_amount']<$rowb['totalamount'] && $rowb['transfered_amount']>0){
						$STATUS="Partially Paid";
					}else if ($rowb['transfered_amount']==0) {
						$STATUS="No Paid";
					}

					$tr.="<tr class='text-light bg-dark'><td>".$j."</td><td>".$paytoascompanyname."</td><td>".$billingdate."</td><td>".$rowb['invoice_no']."</td><td>".$rowb['totalamount']."</td><td>".$rowb['transaction_date']."</td><td>".$rowb['transfered_amount']."</td><td></td><td>".$balanceamt."</td><td>".$STATUS."</td><td>".$rowb['documentation_path']."</td>";
					$tr.="</tr>";

					$returnamountifo=getpaidbyinfo($conn,$companyid,$subcompanyid,$projectid,$paytoid,$d1,$d2);
					if (count($returnamountifo)>0) {
						$j++;
						foreach ($returnamountifo as $keyc) {
							$tr.="<tr class='text-light bg-dark'><td>".$j."</td><td>".$paytoascompanyname."</td><td></td><td></td><td></td><td>".$keyc['transaction_date']."</td><td></td><td></td><td>".$keyc['amount']."</td><td></td><td></td>";
							$tr.="</tr>";
						}
					}
					$j++;
				}



			}
			// $tr.="<tr><td>".$j."</td><td></td><td></td><td>Total</td><td>".$totalamount."</td><td></td><td>".$totalamounttransfer."</td><td></td><td></td><td></td><td></td>";
			// 		$tr.="</tr>";

			// 	$tr.="</tbody>";

		
				
	return $tr;


}

function Imprestindivisdulpaytodetailsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$totalamount=0;
	$totalamounttransfer=0;
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");
	$balancecal=0;

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(idate) as mindate,max(idate) as maxdate from imprest_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;

		
		$billcode="";
		$billname="";
		$id="";
		$typeofbill="";
		$STATUS="";
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='2' >Report No.:</th><th colspan='9' class='cl_reportno_3'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='2'>Description:</th><th colspan='9' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Project:</th><th colspan='9' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Company:</th><th colspan='9' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >From beginning to till date:</th><th colspan='9' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Generated on date & time:</th><th colspan='9' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >Statement Of :</th><th colspan='9' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Imprest Date</th><th width='200px;'>Imprest Amount</th><th width='200px;'>Imprest adjusted</th><th width='200px;'>Purpose</th><th width='200px;'>Description</th><th width='200px;'>Balance</th><th width='200px;'>Document</th></thead>";
			$tr.="<tbody>";
		
		$infobillmaster=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,'paytoid',$selectoptionid);


			foreach ($infobillmaster as $billkey) {
				 
				 $paytoid=$billkey['imprestpaytoid'];
				 $paytoname=$billkey['imprestpayto'];
				 
				
				if ($d1=="" || $d2=="") {
				 	 $qry="select idate, imprest_amount, adjustment_amount,purpose,description from imprest_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$paytoid."' and (tedit is null or tedit='') order by idate";
				}else{
		 			$qry="select idate, imprest_amount, adjustment_amount,purpose,description from imprest_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$paytoid."' and (idate between '".$d1."' and '".$d2."') and (tedit is null or tedit='') order by idate";
				}


				$resultb=$conn->query($qry);
				if ($resultb->num_rows>0) {
					while($rowb=$resultb->fetch_assoc()){
						if ($rowb['idate']) {
						$rowb['idate']=date('d-m-Y',strtotime($rowb['idate']));
						}

						$balancecal=$balancecal+($rowb['imprest_amount']-$rowb['adjustment_amount']);

						if ($rowb['imprest_amount']>0) {
							$tr.="<tr class='bg-light text-primary'><td>".$j."</td><td>".$rowb['idate']."</td><td>".$rowb['imprest_amount']."</td><td>0</td><td>".$rowb['purpose']."</td><td>".$rowb['description']."</td><td>".$balancecal."</td><td></td>";
						}else if ($rowb['adjustment_amount']>0) {
							$tr.="<tr class='bg-light text-primary'><td>".$j."</td><td>".$rowb['idate']."</td><td>0</td><td>".$rowb['adjustment_amount']."</td><td>".$rowb['purpose']."</td><td>".$rowb['description']."</td><td>".$balancecal."</td><td></td>";
						}

						
						$tr.="</tr>";

						
						
						$j++;

					}
					
				}



			}


			// $tr.="<tr><td>".$j."</td><td></td><td></td><td>Total</td><td>".$totalamount."</td><td></td><td>".$totalamounttransfer."</td><td></td><td></td><td></td><td></td>";
			// 		$tr.="</tr>";

			// 	$tr.="</tbody>";

		
				
	return $tr;
}

function Cashindivisdulpaytodetailsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$totalamount=0;
	$totalamounttransfer=0;
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");
	$balancecal=0;

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(idate) as mindate,max(idate) as maxdate from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;

		
		$billcode="";
		$billname="";
		$id="";
		$typeofbill="";
		$STATUS="";
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='2' >Report No.:</th><th colspan='9' class='cl_reportno_3'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='2'>Description:</th><th colspan='9' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Project:</th><th colspan='9' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Company:</th><th colspan='9' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >From beginning to till date:</th><th colspan='9' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Generated on date & time:</th><th colspan='9' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >Statement Of :</th><th colspan='9' class='' >".$selectoptionname."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Cash Date</th><th width='200px;'>Cash Amount</th><th width='200px;'>Cash adjusted</th><th width='200px;'>Purpose</th><th width='200px;'>Description</th><th width='200px;'>Balance</th><th width='200px;'>Document</th></thead>";
			$tr.="<tbody>";
		
		$infobillmaster=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,'accountid',$selectoptionid);


			foreach ($infobillmaster as $billkey) {
				 
				 $paytoid=$billkey['accountid'];
				 $paytoname=$billkey['accountname'];
				 
				
				if ($d1=="" || $d2=="") {
				 	 $qry="select idate, cash_amount, adjustment_amount,purpose,description from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$paytoid."' and (tedit is null or tedit='') order by idate";
				}else{
		 			$qry="select idate, cash_amount, adjustment_amount,purpose,description from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$paytoid."' and (idate between '".$d1."' and '".$d2."') and (tedit is null or tedit='') order by idate";
				}


				$resultb=$conn->query($qry);
				if ($resultb->num_rows>0) {
					while($rowb=$resultb->fetch_assoc()){
						if ($rowb['idate']) {
						$rowb['idate']=date('d-m-Y',strtotime($rowb['idate']));
						}

						$balancecal=$balancecal+($rowb['cash_amount']-$rowb['adjustment_amount']);

						if ($rowb['cash_amount']>0) {
							$tr.="<tr><td>".$j."</td><td>".$rowb['idate']."</td><td>".$rowb['cash_amount']."</td><td>0</td><td>".$rowb['purpose']."</td><td>".$rowb['description']."</td><td>".$balancecal."</td><td></td>";
						}else if ($rowb['adjustment_amount']>0) {
							$tr.="<tr><td>".$j."</td><td>".$rowb['idate']."</td><td>0</td><td>".$rowb['adjustment_amount']."</td><td>".$rowb['purpose']."</td><td>".$rowb['description']."</td><td>".$balancecal."</td><td></td>";
						}

						
						$tr.="</tr>";

						
						
						$j++;

					}
					
				}



			}


			

		
				
	return $tr;
}
function creditcardindivisdulpaytodetailsfunction($conn,$companyid,$subcompanyid,$projectid,$ledgerid,$selectoptionname,$selectoptionid,$d1,$d2,$extended,$billcodeselected)
{
	$totalamount=0;
	$totalamounttransfer=0;
	$tr="";
	$qry="";
	$getfromtoddate="";
	$genrateddate=date("d-m-Y H:i:s");
	$balancecal=0;
	$creditcarduserinfo=getusernameofcreditcard($conn,$companyid,$subcompanyid,$projectid,$selectoptionname);
		foreach ($creditcarduserinfo as $keycd) {
			$userid=$keycd['id'];
			$username=$keycd['username'];
		}			

	if ($d1=="" && $d2=="") {
		$getfromtoddate="";
		 $qrymax="Select min(idate) as mindate,max(idate) as maxdate from creditcard_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."'";
		$resultmax=$conn->query($qrymax);
		if ($resultmax->num_rows>0) {
			$rowmax=$resultmax->fetch_assoc();
			$mindate=date('d-m-Y',strtotime($rowmax['mindate']));
			$maxdate=date('d-m-Y',strtotime($rowmax['maxdate']));
			$getfromtoddate="From: ".$mindate." to: ".$maxdate;
		}
	}else{
			$getfromtoddate="";
			 $mindate=date('d-m-Y',strtotime($d1));
			 $maxdate=date('d-m-Y',strtotime($d2));
			 $getfromtoddate="From: ".$mindate." to: ".$maxdate;
	}

	
	$j=1;

		
		$billcode="";
		$billname="";
		$id="";
		$typeofbill="";
		$STATUS="";
		$tr= "<thead><tr style='font-size:18px; background-color: white;'><th colspan='2' >Report No.:</th><th colspan='9' class='cl_reportno_3'></th></tr>  <tr style='font-size:18px; background-color: white;'><th colspan='2'>Description:</th><th colspan='9' class='cl_description_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Project:</th><th colspan='9' class='cl_project_2' ></th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Company:</th><th colspan='9' class='cl_company_2' ></th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >From beginning to till date:</th><th colspan='9' class='cl_datefromto'>".$getfromtoddate."</th></tr> <tr style='font-size:18px; background-color: white;'><th colspan='2' >Generated on date & time:</th><th colspan='9' class='cl_datetime_2' >".$genrateddate."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >Statement Of :</th><th colspan='9' class='' >".$selectoptionname."</th></tr><tr style='font-size:18px; background-color: white;'><th colspan='2' >Credit Card User :</th><th colspan='9' class='' >".$username."</th></tr>";
			$tr.="<th width='200px;'>Sno</th><th width='400px;'>Date</th><th width='200px;'>Expenses Amount</th><th width='200px;'>Purpose</th><th width='200px;'>Description</th><th width='200px;'>Document</th></thead>";
			$tr.="<tbody>";
		
		
				
				if ($d1=="" || $d2=="") {
					  $qry="select idate,ifnull(debitamount,0) as debitamount,purpose,description,documentpath from creditcard_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and from_accountno='".$selectoptionname."' and userid='".$selectoptionid."' and (tedit is null or tedit='')";
				}else{
					 $qry="select idate,ifnull(debitamount,0) as debitamount,purpose,description,documentpath from creditcard_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and from_accountno='".$selectoptionname."' and userid='".$selectoptionid."' and (tedit is null or tedit='') and (idate between '".$d1."' and '".$d2."') ";
				}

				$resultb=$conn->query($qry);
				if ($resultb->num_rows>0) {
					while($rowb=$resultb->fetch_assoc()){
						if ($rowb['idate']) {
						$rowb['idate']=date('d-m-Y',strtotime($rowb['idate']));
						}

						$tr.="<tr><td>".$j."</td><td>".$rowb['idate']."</td><td>".$rowb['debitamount']."</td><td>".$rowb['purpose']."</td><td>".$rowb['description']."</td><td></td>";

						$tr.="</tr>";

						
						
						$j++;

					}
					
				}



			
	return $tr;
}

function gettotalamountofbill($conn,$companyid,$subcompanyid,$projectid,$billid,$d1,$d2){
	$toatlamount=0;
	$amount=0;
	if ($d1=="" || $d2=="") {
		$qrybil="select referenceno,purposecode FROM strs_bill_transactions where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and typeofbillid='".$billid."' and  (referenceno is not null and referenceno<>'') ";
	}else{
		$qrybil="select referenceno,purposecode FROM strs_bill_transactions where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and typeofbillid='".$billid."' and  (referenceno is not null and referenceno<>'') and (bookdate between '".$d1."' and '".$d2."')";
	}
	
	$resultbil=$conn->query($qrybil);
	if ($resultbil->num_rows>0) {
		while ($rowbil=$resultbil->fetch_assoc()) {
			$qrym="select sum(amount) as totalamount  from tbl_maintransaction_breakdown where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and purpose_code='".$rowbil['purposecode']."' and request_no='".$rowbil['referenceno']."'";
			$resultm=$conn->query($qrym);
			if ($resultm->num_rows>0) {
				$rowm=$resultm->fetch_assoc();
				$amount=$amount+$rowm['totalamount'];
			}
			$toatlamount=$toatlamount+$amount;
		}
	}

	return $toatlamount;
}

function gettotalamountofimprestpayto($conn,$companyid,$subcompanyid,$projectid,$impid,$d1,$d2){
	$imprestamount=0;
	$imprestadjamount=0;
	$imprestbalanceamount=0;

	if ($d1=="" || $d2=="") {
		 $qrybil="select ifnull(sum(imprest_amount),0) as imprestamount,ifnull(sum(adjustment_amount),0) as adjestamount,ifnull((sum(imprest_amount)-sum(adjustment_amount)),0) as balance from imprest_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$impid."' and (tedit is null or tedit='')";
	}else{
		 $qrybil="select ifnull(sum(imprest_amount),0) as imprestamount,ifnull(sum(adjustment_amount),0) as adjestamount,ifnull((sum(imprest_amount)-sum(adjustment_amount)),0) as balance from imprest_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$impid."' and (idate between '".$d1."' and '".$d2."') and (tedit is null or tedit='')";
	}
	$resultbil=$conn->query($qrybil);
	if ($resultbil->num_rows>0) {
		$rowbil=$resultbil->fetch_assoc() ;
			
			$imprestamount=$rowbil['imprestamount'];
			$imprestadjamount=$rowbil['adjestamount'];
			$imprestbalanceamount=$rowbil['balance'];
			
			$toatlamount[]=array("imprestamount"=>$imprestamount,"imprestadjamount"=>$imprestadjamount,"imprestbalanceamount"=>$imprestbalanceamount);
		
	}else{
		$toatlamount[]=array("imprestamount"=>0,"imprestadjamount"=>0,"imprestbalanceamount"=>0);
	}

	return $toatlamount;
}

function gettotalamountofcashpayto($conn,$companyid,$subcompanyid,$projectid,$impid,$d1,$d2){
	$imprestamount=0;
	$imprestadjamount=0;
	$imprestbalanceamount=0;

	if ($d1=="" || $d2=="") {
		  $qrybil="select ifnull(sum(cash_amount),0) as imprestamount,ifnull(sum(adjustment_amount),0) as adjestamount,ifnull((sum(cash_amount)-sum(adjustment_amount)),0) as balance from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$impid."' and (tedit is null or tedit='')";
	}else{
		 $qrybil="select ifnull(sum(cash_amount),0) as imprestamount,ifnull(sum(adjustment_amount),0) as adjestamount,ifnull((sum(cash_amount)-sum(adjustment_amount)),0) as balance from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$impid."' and (idate between '".$d1."' and '".$d2."') and (tedit is null or tedit='')";
	}
	$resultbil=$conn->query($qrybil);
	if ($resultbil->num_rows>0) {
		$rowbil=$resultbil->fetch_assoc() ;
			
			$imprestamount=$rowbil['imprestamount'];
			$imprestadjamount=$rowbil['adjestamount'];
			$imprestbalanceamount=$rowbil['balance'];
			
			$toatlamount[]=array("imprestamount"=>$imprestamount,"imprestadjamount"=>$imprestadjamount,"imprestbalanceamount"=>$imprestbalanceamount);
		
	}else{
		$toatlamount[]=array("imprestamount"=>0,"imprestadjamount"=>0,"imprestbalanceamount"=>0);
	}

	return $toatlamount;
}

function gettotalamountofloan($conn,$companyid,$subcompanyid,$projectid,$impid,$d1,$d2){
	$imprestamount=0;
	$imprestadjamount=0;
	$imprestbalanceamount=0;

	if ($d1=="" || $d2=="") {
		  $qrybil="select ifnull(sum(cash_amount),0) as imprestamount,ifnull(sum(adjustment_amount),0) as adjestamount,ifnull((sum(cash_amount)-sum(adjustment_amount)),0) as balance from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$impid."' and (tedit is null or tedit='')";
	}else{
		 $qrybil="select ifnull(sum(cash_amount),0) as imprestamount,ifnull(sum(adjustment_amount),0) as adjestamount,ifnull((sum(cash_amount)-sum(adjustment_amount)),0) as balance from cash_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and to_accountid='".$impid."' and (idate between '".$d1."' and '".$d2."') and (tedit is null or tedit='')";
	}
	$resultbil=$conn->query($qrybil);
	if ($resultbil->num_rows>0) {
		$rowbil=$resultbil->fetch_assoc() ;
			
			$imprestamount=$rowbil['imprestamount'];
			$imprestadjamount=$rowbil['adjestamount'];
			$imprestbalanceamount=$rowbil['balance'];
			
			$toatlamount[]=array("imprestamount"=>$imprestamount,"imprestadjamount"=>$imprestadjamount,"imprestbalanceamount"=>$imprestbalanceamount);
		
	}else{
		$toatlamount[]=array("imprestamount"=>0,"imprestadjamount"=>0,"imprestbalanceamount"=>0);
	}

	return $toatlamount;
}

function gettotalamountofcreditcard($conn,$companyid,$subcompanyid,$projectid,$impid,$d1,$d2,$userid){
	$imprestamount=0;
	$imprestadjamount=0;
	$imprestbalanceamount=0;

	if ($d1=="" || $d2=="") {
		  $qrybil="select ifnull(sum(debitamount),0) as debitamount from creditcard_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and from_accountid='".$impid."' and userid='".$userid."' and (tedit is null or tedit='')";
	}else{
		 $qrybil="select ifnull(sum(debitamount),0) as debitamount from creditcard_account where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and from_accountid='".$impid."' and userid='".$userid."' and (tedit is null or tedit='') and (idate between '".$d1."' and '".$d2."') ";
	}
	$resultbil=$conn->query($qrybil);
	if ($resultbil->num_rows>0) {
		$rowbil=$resultbil->fetch_assoc() ;
			
			$debitamount=$rowbil['debitamount'];
						
			$toatlamount[]=array("debitamount"=>$debitamount);
		
	}else{
		$toatlamount[]=array("debitamount"=>0);
	}

	return $toatlamount;
}

function get_transferedamount($conn,$companyid,$subcompanyid,$projectid,$referenceno){
	$debitamount=0;
	   $sqlt="Select debitamount from tbl_bankstatement where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and reference_no='".$referenceno."'";
	$resultt=$conn->query($sqlt);
	if ($resultt->num_rows>0) {
		$rrowt=$resultt->fetch_assoc();
		$debitamount=$rrowt['debitamount'];
	}
	return $debitamount;
}
function get_providedcode($conn,$companyid,$subcompanyid,$projectid,$purposecode){
	$providedcode="";
	 $qry="Select provided_code from purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and purposecode='".$purposecode."'";
	$resultqw=$conn->query($qry);
	if ($resultqw->num_rows>0) {
		$rowr=$resultqw->fetch_assoc();
		$providedcode=$rowr['provided_code'];
	}

	return $providedcode;
} 
function getpaidbyinfo($conn,$companyid,$subcompanyid,$projectid,$id,$d1,$d2){
	$refund=array();
	if ($d1==""||$d2=="") {
		$qryc="Select amount,bank_date from t2c_creditadvice where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and paidby_id='".$id."'";
	}else{
		$qryc="Select amount,bank_date from t2c_creditadvice where companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and paidby_id='".$id."' and (bank_date between '".$d1."' and '".$d2."')";
	}
	$resultc=$conn->query($qryc);
	if ($resultc->num_rows>0) {
		while ($rowc=$resultc->fetch_assoc()) {
			$refund[]=array('amount'=>$rowc['amount'],'bank_date'=>$rowc['bank_date']);
		}
	}
	return $refund;
}
?>