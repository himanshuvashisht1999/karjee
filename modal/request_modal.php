<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
require('../connection.php');
require('../functions/function_master.php');
require('../functions/function_savetodb.php');
require('../loginCell/functions/login_functions.php');
$action=$_GET['action'];

if($action=="beneficiarylist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$bnflist=getbeneficiaryinfodetails($mconn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
	echo json_encode($bnflist);
}else if($action=="clientlist_all"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid']; 
	$bnflist=getclientdetails($mconn,$companyid,$subcompanyid,$projectid,"allwithprojectid", "");
	echo json_encode($bnflist);
}else if($action=="getpaytofrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoinfo=getpaytoinfo($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($paytoinfo);
}else if($action=="getpayerfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	 $paytoinfo=getpayerinfoworking($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	// echo json_encode($paytoinfo);
}
else if($action=="getcombine_agentsandclient_deposit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoinfo=getpaytoinfo_combineagentandclient_deposit($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($paytoinfo);
}else if($action=="getcreditcardauthorizedfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoinfo=getcreditcardauthorizedinfo($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($paytoinfo);
}else if ($action=="updatecashsmallexpencstatus") {
		 $companyid = $_POST['companyid'];
		 $subcompanyid = $_POST['subcompanyid'];
		 $projectid = $_POST['projectid'];
		 $requestno = $_POST['requestno'];
		$cvoucherinfo = json_decode($_POST['expeninfo'],TRUE);
		if(count($cvoucherinfo)>0){
		foreach ($cvoucherinfo as $cvkey) {
			$sno=$cvkey['sno'];
			
			$resultqr=$conn->query("Update smallexpcash_account set request_no='".$requestno."',request_status='Done' where sno='".$sno."'");
			if ($resultqr) {
				$rtnmsg=array('status'=>'Success', 'message'=>'Done');
			}else{
				$rtnmsg=array('status'=>'Error', 'message'=>'Query data not available, Try again');
			}
	}
}

	echo json_encode($rtnmsg);
}
else if($action=="getloanpaytolist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecodeval = $_POST['purposecodeval'];
	$lpaytolist="";
	$pppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecodeval);
	// print_r($pppd);
	if(count($pppd)>0){
		foreach ($pppd as $pkey) {
			if(strtolower($pkey['default_beneficiary'])=="withinprojectbank"){
				$lpaytolist.='<label>Payto Name</label>';
				$lpaytolist.='<select id="paytoname" class="form-control">';
				$lpayto=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
				if(count($lpayto)>0){
					if(count($lpayto)>1){
						$lpaytolist.='<option value="" selected disabled>Select payto name</option>';
					}
					foreach ($lpayto as $lpkey) {
						$lpaytolist.='<option value="'.$lpkey['accountid'].'">'.$lpkey['accountname'].'('.$lpkey['accountno'].')</option>';	
					}
				}else{
					$lpaytolist.='<option value="">Not available</option>';
				}
				$lpaytolist.='</select>';
			}else if(strtolower($pkey['default_beneficiary'])=="withinsubcompanybank"){
				$lpayto=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"subcompanyid_withoutproject",$subcompanyid);
				$lpaytolist.='<label>Loan Payto Name</label>';
				$lpaytolist.='<select id="paytoname" class="form-control">';
				if(count($lpayto)>0){
					if(count($lpayto)>1){
						$lpaytolist.='<option value="" selected disabled>Select payto name</option>';
					}
					foreach ($lpayto as $lpkey) {
						$lpaytolist.='<option value="'.$lpkey['accountid'].'">'.$lpkey['accountname'].'('.$lpkey['accountno'].')</option>';
					}
				}else{
					$lpaytolist.='<option value="">Not available</option>';
				}
				$lpaytolist.='</select>';
			}else if(strtolower($pkey['default_beneficiary'])=="externalloan"){
				$lpaytolist.='<label>Payto Name</label>';
				$lpaytolist.='<i class="fa fa-plus float-right m-2" id="opennewpaytomodal_forexp" style="cursor: pointer; color: blue;"></i>
					<i class="fa fa-sync float-right m-2 refeshpaytolist" onclick=\'request_paytofrommaster_withpaytoid("hidden_baseurl","hidden_companyid","hidden_subcompanyid","hidden_projectid","paytoname")\' style="cursor: pointer; color: green;"></i>';
				$lpaytolist.='<select id="paytoname" class="form-control">';
				$lpayto=getpaytoinfo($conn,$companyid,$subcompanyid,"projectid",$projectid);
				if(count($lpayto)>0){
					if(count($lpayto)>1){
						$lpaytolist.='<option value="" selected disabled>Select payto name</option>';
					}
					foreach ($lpayto as $lpkey) {
						$lpaytolist.='<option value="'.$lpkey['paytoid'].'">'.$lpkey['payto'].'</option>';	
					}
				}else{
					$lpaytolist.='<option value="">Not available</option>';
				}
				$lpaytolist.='</select>';
			}
		}
	}
	echo json_encode($lpaytolist);
}else if($action=="getpaytolist_withhtml"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecodeval = $_POST['purposecodeval'];
	$lpaytolist="";
	
	$lpaytolist.='<label>Payto Name</label>';
	$lpaytolist.='<select id="paytoname" class="form-control">';
	$lpayto=getpaytoinfo($conn,$companyid,$subcompanyid,"projectid",$projectid);
	if(count($lpayto)>0){
		if(count($lpayto)>1){
			$lpaytolist.='<option value="" selected disabled>Select payto name</option>';
		}
		foreach ($lpayto as $lpkey) {
			$lpaytolist.='<option value="'.$lpkey['paytoid'].'">'.$lpkey['payto'].'</option>';	
		}
	}else{
		$lpaytolist.='<option value="">Not available</option>';
	}
	$lpaytolist.='</select>';
	echo json_encode($lpaytolist);
}else if($action=="employeelistforsalary_withhtml"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecodeval = $_POST['purposecodeval'];
	
	$lpaytolist="";
	$lpaytolist.='<label>Employee Name for Salary<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>';
	$lpaytolist.='<div style="padding:4px; background-color: #6DC9C9;">
		<table class="table" style="border:1px solid black; width: 100%;" border=1>';
			$lpaytolist.='<tr>
				<th>Action</th>
				<th style="display:none;">Company Id</th>
				<th style="display:none;">SubCompany Id</th>
				<th style="display:none;">Project Id</th>
				<th style="display:none;">Employee Id</th>
				<th>Employee Name</th>
				<th>Salary/Month</th>
				<th>Days for Salary</th>
				<th>Calculated Salary</th>
			</tr>';
			$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"projectactive_regularemployee",$projectid);
			if(count($lpayto)>0){
				foreach ($lpayto as $lpkey) {
					$lpaytolist.='<tr>
						<td style="width:50px;"><input type="checkbox" class="empaction" /></td>
						<td class="companyid_td" style="display:none;">'.$lpkey['companyid'].'</td>
						<td class="subcompanyid_td" style="display:none;">'.$lpkey['subcompanyid'].'</td>
						<td class="projectid_td" style="display:none;">'.$lpkey['projectid'].'</td>
						<td class="empid_td" style="display:none;">'.$lpkey['empid'].'</td>
						<td class="empname_td">'.$lpkey['empname'].'</td>
						<td class="ttlsalary_td clslintd">'.$lpkey['ttlsalary'].'</td>
						<td class="caldays_td clslintd" contenteditable="true" style="background-color: white; color:black; padding:2px;"></td>
						<td class="caldsalary_td"></td>
					</tr>';	
				}
			}else{
				$lpaytolist.='<tr>
					<td style="width:50px;"><input type="checkbox" class="empaction" /></td>
					<td class="companyid_td" style="display:none;">'.$companyid.'</td>
					<td class="subcompanyid_td" style="display:none;">'.$subcompanyid.'</td>
					<td class="projectid_td" style="display:none;">'.$projectid.'</td>
					<td class="empid_td" style="display:none;">'.$lpkey['empid'].'</td>
					<td class="empname_td" style="background-color: white; color:black; padding:2px;" contenteditable="true"></td>
					<td class="ttlsalary_td clslintd" style="background-color: white; color:black; padding:2px;" contenteditable="true"></td>
					<td class="caldays_td clslintd" style="background-color: white; color:black; padding:2px;" contenteditable="true"></td>
					<td class="caldsalary_td"></td>
				</tr>';
			}
		$lpaytolist.='</table>
	</div>';
	echo json_encode($lpaytolist);
}else if($action=="payto_salaryfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];	
	$paytoid = $_POST['paytoid'];
	$ees=employeemaster($conn,$companyid,$subcompanyid,$projectid,"employeeid",$paytoid);
	$eeht="";
	if(count($ees)>0){
		foreach ($ees as $eeskey) {
			$ttlsalary = $eeskey['basicsalary'] + $eeskey['hra'] + $eeskey['conveyance'] + $eeskey['otherallowance'];
			$eeht.="<div class='row'>";
					$eeht.="<div class='col-md-4'>
						<label>Total Salary/Month as per master Rs.: <b id='ttlsalary'>".$ttlsalary."</b></label>
					</div>";
					$eeht.="<div class='col-md-4'></div>";
					$eeht.="<div class='col-md-4'></div>";
			$eeht.="</div>";	
		}
	}
	echo json_encode($eeht);
}else if($action=="paytolist_investment"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecodeval = $_POST['purposecodeval'];
	$lpaytolist="";
	
	$lpaytolist.='<label>Payto Name for Investment</label>';
	$lpaytolist.='<select id="invest_paytoname" class="form-control">';
	$lpayto=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"onlyfd_accountlist","");
	if(count($lpayto)>0){
		if(count($lpayto)>1){
			$lpaytolist.='<option value="" selected disabled>Select payto name</option>';
		}
		foreach ($lpayto as $lpkey) {
			$lpaytolist.='<option value="'.$lpkey['accountid'].'">'.$lpkey['accountname'].'('.$lpkey['accountno'].')</option>';	
		}
	}else{
		$lpaytolist.='<option value="">Not available</option>';
	}
	$lpaytolist.='</select>';
	echo json_encode($lpaytolist);
}else if($action=="savetoodb_newpaytodetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];

	$paytoname = $_POST['paytoname'];
	$bankname = $_POST['bankname'];
	$accountnumber = $_POST['accountnumber'];
	$bankifsccode = $_POST['bankifsccode'];	
	$loginname = $_POST['loginname'];
	
	$restimp= savetodbnewpayto_details($conn,$paytoname,$accountnumber,$bankname,$bankifsccode,"","","",$companyid,$subcompanyid,$projectid,'','',$loginname);
	echo json_encode($restimp);
}else if($action=="checkexist_imprestpayto"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$imprestpaytoname = $_POST['imprestpaytoname'];
	$imptlist=getimprestpayto($mconn,$companyid,$subcompanyid,$projectid,"paytolike",$imprestpaytoname);
	$imppthtml="";
	if(count($imptlist)>0){
		$imppthtml.="<p>Already exist in imprest payto master</p>";
		foreach ($imptlist as $imkey) {
			$imppthtml.="<label><input type='radio' name='imprestpayradio' values='".$imkey['imprestpayto']."' /> &nbsp;&nbsp;&nbsp;".$imkey['imprestpayto']."</label></br/>";
		}
	}
	echo json_encode($imppthtml);
}else if($action=="checkexist_paytoname"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$paytolist=getpaytoinfo($conn,$companyid,$subcompanyid,"likelypayto",$paytoname);
	$pthtml="";
	if(count($paytolist)>0){
		$pthtml.="<p>Already exist in payto master</p>";
		foreach ($paytolist as $imkey) {
			$pthtml.="<label><input type='radio' name='payradio' values='".$imkey['payto']."' /> &nbsp;&nbsp;&nbsp;".$imkey['payto']."</label></br/>";
		}
	}
	echo json_encode($pthtml);
}else if($action=="projectwithsubcompajy_htmltableview"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$cmi=getcompanydetails($mconn,"companyid",$companyid);
	$sbcmi=getsubcompanydetails($mconn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
	$phtmlviw="";
	if(count($sbcmi)>0){
		$phtmlviw.="<h4 style='text-align:center; font-weight:bold;'>Project(s) for Imprest Authorizasion</h4>
		<table class='table table-all' style='width:100%;' border='1' id='imprestpayto_authorizationtbl'>
			<thead>
				<tr>
					<th style='width:100px; text-align:center;'>Selct All <br/><input type='checkbox' id='checkallprj' onclick='checkuncheck_checkbox(\"checkallprj\", \"checkallprjl\");'></th>
					<th>Sub Company Name</th>
					<th>Project Name</th>
					<th style='display:none;'>companyid</th>
					<th style='display:none;'>subcompanyid</th>
					<th style='display:none;'>projectid</th>
				</tr>
			</thead>
			<tbody>";
		foreach ($sbcmi as $sbikey) {
			$prjl=getprojectdetails($mconn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
			if(count($prjl)>0){
				foreach ($prjl as $pkey) {
					$phtmlviw.="<tr>
						<td style='width:100px; text-align:center;'><input type='checkbox' class='checkallprjl' /></td>
						<td class='subcmpname_td'>".$sbikey['subcompanyname']."</td>
						<td class='projectname_td'>".$pkey['projectname']."</td>
						<td class='cmpid_td' style='display:none;'>".$sbikey['companyid']."</td>
						<td class='subcmpid_td' style='display:none;'>".$sbikey['subcompanyid']."</td>
						<td class='projectid_td' style='display:none;'>".$pkey['projectid']."</td>
					</tr>";
				}
			}
		}
			$phtmlviw.="</tbody>
		</table>";
	}else{
		$phtmlviw.="Data not available";
	}
	echo json_encode($phtmlviw);
}else if($action=="purpose_categoryfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$pcategorylist=GetPurposeCategoryfrommaster($mconn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($pcategorylist);
}else if($action=="purpose_subcategoryfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	if($categoryidval=="" || $categoryidval==null || $categoryidval=="undefined"){
		$shortby = "all";
	}else{
		$shortby = "allsubcaterylist_withcategoryid";
	}
	$pscategorylist=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,$shortby,$categoryidval);
	echo json_encode($pscategorylist);
}else if($action=="unauditeddaterange"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$daterange = get_unauditeddaterange($mconn,$companyid,$subcompanyid,$projectid);
	echo json_encode($daterange);
}
else if($action=="purposecodefrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	$subcategoryidval = $_POST['subcategoryidval'];
	if($categoryidval!=""  && $categoryidval!="" ){
		$shortby = "withcategory_subcategory";
		$shortbyvalue = $categoryidval.'|'.$subcategoryidval;
	}else{
		$shortby = "all";
		$shortbyvalue =="all";
	}
	$pscategorylist=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($pscategorylist);
}else if($action=="purposecodelist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
}else if($action=="modeoftransferlist_todropdown"){
	$mdlist=modeoftransfer($mconn);
	echo json_encode($mdlist);
}else if($action=="getauthorized_accountofproject"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$tbllist=getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
	// print_r($tbllist);
	echo json_encode($tbllist);
}else if($action=="savetootherthanrequest"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$transactionamount = $_POST['transactionamount'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$balanceamountforreceipt = $_POST['balanceamountforreceipt'];	
	$loginid = $_POST['loginid'];
	$cvoucherinfo = json_decode($_POST['voucherinfo'],TRUE);
	$ttlbnfamount=0;
	$bnfar=[];
	$amar=[];
	$prpar=[];
	$dscar=[];
	if(count($cvoucherinfo)>0){
		foreach ($cvoucherinfo as $cvkey) {
			$ttlbnfamount += $cvkey['expenseamount'];
			if($cvkey['beneficiaryname']){
				array_push($bnfar,$cvkey['beneficiaryname']);
			}
			if($cvkey['expenseamount']){
				array_push($amar,$cvkey['expenseamount']);
			}
			if($cvkey['purposecode']){
				array_push($prpar,$cvkey['purposecode']);
			}
			if($cvkey['description']){
				array_push($dscar,$cvkey['description']);
			}
		}
	}
	$insertqury="";
	$rtnmsg=array();
	if($paytoid!="" && $transactionamount==$ttlbnfamount && $transactionamount>0 && count($bnfar)==count($cvoucherinfo) && count($amar)==count($cvoucherinfo) && count($prpar)==count($cvoucherinfo) && count($dscar)==count($cvoucherinfo)){
		recunstructbook($authconn,$mconn,$companyid,$subcompanyid,$projectid,$accountid,$modeoftransfer,$transactionno,$transactiondate,$transactionamount,$paytoid,$cvoucherinfo);

	}else{
		$voucherinfo=json_decode($_POST['voucherinfo'],TRUE);
		if(count($voucherinfo)>0){
			foreach ($voucherinfo as $vkey) {
				$ppg=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$vkey['purposecode']);
				if(count($ppg)>0){
					foreach ($ppg as $ppgr) {
						$category = $ppgr['pcategory'];
						$categorycode = $ppgr['pcategorycode'];
						$subcategory = $ppgr['psub_category'];
						$subcategorycode = $ppgr['psub_categoryid'];
						$purpose = $ppgr['purpose'];
						$purposecode = $ppgr['purposecode'];
					}
				}else{
					$category ="";
					$categorycode ="";
					$subcategory ="";
					$subcategorycode ="";
					$purpose ="";
					$purposecode ="";
				}
				$acc=getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
				$bankname ="";
				$accountno ="";
				if(count($acc)>0){
					foreach ($acc as $acckey) {
						$bankname = $acckey['bankname'];
						$accountno = $acckey['accountno'];
					}
				}
				$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,accountno,account_id,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$accountid."','".$vkey['expenseamount']."','".$vkey['description']."','".$paytoname."','".$vkey['beneficiaryname']."','".$categorycode."','".$category."','".$subcategorycode."','".$subcategory."','".$purpose."','".$purposecode."','".$loginid."','".str_replace("../", "", $vkey['attachmentpath'])."');";
			}
			if($insertqury){
				if ($mconn->multi_query($insertqury) === TRUE) {
					$rtnmsg=array('status'=>'Success', 'message'=>'Your receipt/voucher details save successfully');
				}else{
					$rtnmsg=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
				}
			}else{
				$rtnmsg=array('status'=>'Error', 'message'=>'Query data not available, Try again');
			}
		}
	}
	echo json_encode($rtnmsg);
}
else if($action=="savetovoiceinstruction"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$requesttypename = $_POST['requesttypename'];
	$accountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$transactionamount = $_POST['transactionamount'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$balanceamountforreceipt = $_POST['balanceamountforreceipt'];	
	$loginid = $_POST['loginid'];
	$cvoucherinfo = json_decode($_POST['voucherinfo'],TRUE);
	$ttlbnfamount=0;
	$bnfar=[];
	$amar=[];
	$prpar=[];
	$dscar=[];
	if(count($cvoucherinfo)>0){
		foreach ($cvoucherinfo as $cvkey) {
			$ttlbnfamount += $cvkey['expenseamount'];
			if($cvkey['beneficiaryname']){
				array_push($bnfar,$cvkey['beneficiaryname']);
			}
			if($cvkey['expenseamount']){
				array_push($amar,$cvkey['expenseamount']);
			}
			if($cvkey['purposecode']){
				array_push($prpar,$cvkey['purposecode']);
			}
			if($cvkey['description']){
				array_push($dscar,$cvkey['description']);
			}
		}
	}
	$insertqury="";
	$rtnmsg=array();
	if($paytoid!="" && $transactionamount==$ttlbnfamount && $transactionamount>0 && count($bnfar)==count($cvoucherinfo) && count($amar)==count($cvoucherinfo) && count($prpar)==count($cvoucherinfo) && count($dscar)==count($cvoucherinfo)){
		recunstructbook($authconn,$mconn,$companyid,$subcompanyid,$projectid,$accountid,$modeoftransfer,$transactionno,$transactiondate,$transactionamount,$paytoid,$cvoucherinfo);

	}else{
		$voucherinfo=json_decode($_POST['voucherinfo'],TRUE);
		if(count($voucherinfo)>0){
			foreach ($voucherinfo as $vkey) {
				$ppg=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,"purposecode",$vkey['purposecode']);
				if(count($ppg)>0){
					foreach ($ppg as $ppgr) {
						$category = $ppgr['pcategory'];
						$categorycode = $ppgr['pcategorycode'];
						$subcategory = $ppgr['psub_category'];
						$subcategorycode = $ppgr['psub_categoryid'];
						$purpose = $ppgr['purpose'];
						$purposecode = $ppgr['purposecode'];
					}
				}else{
					$category ="";
					$categorycode ="";
					$subcategory ="";
					$subcategorycode ="";
					$purpose ="";
					$purposecode ="";
				}
				$acc=getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,"accountid",$accountid);
				$bankname ="";
				$accountno ="";
				if(count($acc)>0){
					foreach ($acc as $acckey) {
						$bankname = $acckey['bankname'];
						$accountno = $acckey['accountno'];
					}
				}
				$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,accountno,account_id,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$accountid."','".$vkey['expenseamount']."','".$vkey['description']."','".$paytoname."','".$vkey['beneficiaryname']."','".$categorycode."','".$category."','".$subcategorycode."','".$subcategory."','".$purpose."','".$purposecode."','".$loginid."','".str_replace("../", "", $vkey['attachmentpath'])."');";
			}
			if($insertqury){
				if ($mconn->multi_query($insertqury) === TRUE) {
					$rtnmsg=array('status'=>'Success', 'message'=>'Your receipt/voucher details save successfully');
				}else{
					$rtnmsg=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
				}
			}else{
				$rtnmsg=array('status'=>'Error', 'message'=>'Query data not available, Try again');
			}
		}
	}
	echo json_encode($rtnmsg);
}else if($action=="employeelistforsalary"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecodeval = $_POST['purposecodeval'];
	$sltp = $conn->query("select category,categoryid,sub_category,sub_categoryid,purpose,purposecode,isforstock,ifnull(budget_per_month,0) as budget_per_month,ifnull(total_per_year,0) as total_per_year,provided_code,default_beneficiary FROM purpose_code_master where cmp_id='".$companyid."' and subcmp_id='".$subcompanyid."' and projectid='".$projectid."' and lcase(purposecode)='".$purposecodeval."'");
	$default_beneficiary="";
	if($sltp->num_rows>0){
		while ($akey=$sltp->fetch_assoc()) {
			$default_beneficiary = $akey['default_beneficiary'];
		}
	}
	$lpaytolist="";
	$lpaytolist.='<label>To Whome Employee<sup><i class="fa fa-star" style="font-size: 6px; color:red"></i></sup></label>';
	$lpaytolist.='<select id="salary_paytoid" class="form-control select2" onchange="payto_salaryfrommaster(\'hidden_baseurl\',\'hidden_companyid\',\'hidden_subcompanyid\',\'hidden_projectid\',\'salary_paytoid\',\'paytosalarypermonth\');">';

	if($default_beneficiary=="regular"){
		$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"projectactive_regularemployee",$projectid);
	}else if($default_beneficiary=="parttime"){
		$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"active_parttimeemployeeid",$projectid);
	}else if($default_beneficiary=="trainee"){
		$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"projectactive_traineeemployee",$projectid);
	}else{
		$lpayto=array();
	}
	if(count($lpayto)>0 && $default_beneficiary!=""){
		$lpaytolist.='<option value="" selected disabled>Select emplyee name</option>';
		foreach ($lpayto as $lpkey) {
			$lpaytolist.='<option value="'.$lpkey['empid'].'">'.$lpkey['empname'].'</option>';	
		}
	}else{
		$lpaytolist.='<option value="">Employee list not available</option>';
	}
	$lpaytolist.='</select>';
	echo json_encode($lpaytolist);
}else if($action=="savetoodb_requestdataforsalary"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];	
	$fromaccountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$requestamount = $_POST['paytoamount'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$purposecategoryid = $_POST['purposecategoryid'];
	$purposesubcategoryid = $_POST['purposesubcategoryid'];
	$purposecode = $_POST['purposecode'];
	$salaryyear = $_POST['salaryyear'];
	$salarymonth = $_POST['salarymonth'];
	$salarydays = $_POST['salarydays'];
	$requestdescription = $_POST['description'];
	$loginname = $_POST['loginname'];
	$requesttypename = $_POST['requesttypename'];

	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];

	$updatecategoriz = $_POST['updatecategoriz'];

	//paytodetails
	$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	foreach ($ppt as $pptkey) {
		$paytoname = $pptkey['paytoname'];
	}
	$empinfo=employeemaster($conn,$companyid,$subcompanyid,$projectid,"employeeid",$paytoid);
	foreach ($empinfo as $empkey) {
		$paytoname=$empkey['empname'];
		$payto_bankname=$empkey['bankname'];
		$payto_accountno=$empkey['accountno'];
		$payto_bankifsccode=$empkey['ifsccode'];
		$is_intermediate_transfer="";
		$payto_accountid="";
	}


	// $attachedfile = $_FILES['attachedfile']['name'];
	$filepath="";
	$distpath="../../requestdocument/";
	if($_FILES['attachedfile']['name']){
		if ( !is_dir( $distpath ) ) {
		    mkdir($distpath, 0755);       
		}
		$temp = explode(".", $_FILES["attachedfile"]["name"]);
		$extension=end($temp);
		$newfilename = round(microtime(true)) . '.' . end($temp);
        move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $distpath . $newfilename);
        $filepath = $distpath . $newfilename;
        $filepath_forother = $distpath . $newfilename;
	}else if($attpathfromemail!=""){
		if(strtolower($updatecategoriz)=="yes"){
			$filepath_fromemail = $attpathfromemail;
			$filepath_forother = $attpathfromemail;
			$qres = $conn->query("update emailtodb_attach set category='Digitized Without Categorization', category_id=0 where ID={$attsnofromemail}");
			if($qres){
				$catupdate = "update emailtodb_email set cat_status='categorized' where ID='{$emailsnofromemail}' and not exists (select ID from emailtodb_attach where emailtodb_attach.IDEmail='{$emailsnofromemail}' and category_id is null)";
				$cat_result=$conn->query($catupdate);
			}
		}
	}

	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}

	$voucherinfo[]=array('beneficiaryname'=>$paytoname,'expenseamount'=>$requestamount, 'voucherno'=>'', 'vouchertype'=>'', 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'attachmentpath'=>$filepath_forother, 'docsno'=>'','srm_attsno'=>$attsnofromemail,'srm_emailsno'=>$emailsnofromemail);
	$rrs=array();
	if(strtolower($requesttypename)=="pre-payment"){
		$rrs=unapproved_saverequestdataforsalary($conn,$countryid,$companyid,$subcompanyid,$projectid,$fromaccountid,$purposetype,$paytoid,$modeoftransfer,$requestamount,$requestdescription,$purposecategoryid,$purposesubcategoryid,$purposecode,$salaryyear,$salarymonth,$salarydays,$loginname,$filepath,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename);
	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid && $requestamount>0 && $modeoftransfer && $transactionno && $transactiondate && $fromaccountid && $purposecode && $requestdescription){
			$rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo);
		}else{
			if(count($voucherinfo)>0){
				foreach ($voucherinfo as $vkey) {
					$ppg=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$vkey['purposecode']);
					if(count($ppg)>0){
						foreach ($ppg as $ppgr) {
							$category = $ppgr['pcategory'];
							$categorycode = $ppgr['pcategorycode'];
							$subcategory = $ppgr['psub_category'];
							$subcategorycode = $ppgr['psub_categoryid'];
							$purpose = $ppgr['purpose'];
							$purposecode = $ppgr['purposecode'];
						}
					}else{
						$category ="";
						$categorycode ="";
						$subcategory ="";
						$subcategorycode ="";
						$purpose ="";
						$purposecode ="";
					}
					$acc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$fromaccountid);
					$bankname ="";
					$accountno ="";
					if(count($acc)>0){
						foreach ($acc as $acckey) {
							$bankname = $acckey['bankname'];
							$accountno = $acckey['accountno'];
						}
					}
					//T3
					$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,accountno,account_id,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo,srm_attsno,srm_emailsno) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$fromaccountid."','".$vkey['expenseamount']."','".addslashes($vkey['description'])."','".$paytoname."','".$vkey['beneficiaryname']."','".$categorycode."','".$category."','".$subcategorycode."','".$subcategory."','".$purpose."','".$purposecode."','".$loginname."','".str_replace("../", "", $vkey['attachmentpath'])."', '".$vkey['srm_attsno']."', '".$vkey['srm_emailsno']."');";
				}
				if($insertqury){
					if ($mconn->multi_query($insertqury) === TRUE) {
						$rrs[]=array('status'=>'Success', 'message'=>'Your receipt/voucher details save successfully');
					}else{
						$rrs[]=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
					}
				}else{
					$rrs[]=array('status'=>'Error', 'message'=>'Query data not available, Try again');
				}
			}
		}
	}
	echo json_encode($rrs);
}else if($action=="savetoodb_requestdataforall"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];	
	$fromaccountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$requestamount = $_POST['paytoamount'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$purposecategoryid = $_POST['purposecategoryid'];
	$purposesubcategoryid = $_POST['purposesubcategoryid'];
	$purposecode = $_POST['purposecode'];
	$requestdescription = $_POST['description'];
	$loginname = $_POST['loginname'];
	$requesttypename = $_POST['requesttypename'];

	$updatecategoriz = $_POST['updatecategoriz'];
	
	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];

	// $attachedfile = $_FILES['attachedfile']['name'];
	$filepath="";
	$filepath_fromemail="";
	$distpath="../../requestdocument/";
	if($_FILES['attachedfile']['name']){
		if ( !is_dir( $distpath ) ) {
		    mkdir($distpath, 0755);       
		}
		$temp = explode(".", $_FILES["attachedfile"]["name"]);
		$extension=end($temp);
		$newfilename = round(microtime(true)) . '.' . end($temp);
        move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $distpath . $newfilename);
        $filepath = $distpath . $newfilename;
        $filepath_forother = $distpath . $newfilename;
	}else if($attpathfromemail!=""){
		if(strtolower($updatecategoriz)=="yes"){
			$filepath_fromemail = $attpathfromemail;
			$filepath_forother = $attpathfromemail;
			$qres = $conn->query("update emailtodb_attach set category='Digitized Without Categorization', category_id=0 where ID={$attsnofromemail}");
			if($qres){
				$catupdate = "update emailtodb_email set cat_status='categorized' where ID='{$emailsnofromemail}' and not exists (select ID from emailtodb_attach where emailtodb_attach.IDEmail='{$emailsnofromemail}' and category_id is null)";
				$cat_result=$conn->query($catupdate);
			}
		}
	}

	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}
	//paytodetails
	
	if($purposecategoryid=="5" || $purposecategoryid=="2"){
		$ppt=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$paytoid);
	}else{
		$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	}
	// print_r($ppt);
	foreach ($ppt as $pptkey) {
		$paytoname = $pptkey['paytoname'];
	}
	$voucherinfo[]=array('beneficiaryname'=>$paytoname,'expenseamount'=>$requestamount, 'voucherno'=>'', 'vouchertype'=>'', 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'attachmentpath'=>$filepath_forother, 'docsno'=>'','srm_attsno'=>$attsnofromemail,'srm_emailsno'=>$emailsnofromemail);
	$rrs=array();
	if(strtolower($requesttypename)=="pre-payment"){
		$rrs=unapproved_saverequestdataforall($conn,$countryid,$companyid,$subcompanyid,$projectid,$fromaccountid,$purposetype,$paytoid,$modeoftransfer,$requestamount,$requestdescription,$purposecategoryid,$purposesubcategoryid,$purposecode,$loginname,$filepath,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename);
	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid && $requestamount>0 && $modeoftransfer && $transactionno && $transactiondate && $fromaccountid && $purposecode && $requestdescription){
			$rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo);
		}else{
			if(count($voucherinfo)>0){
				foreach ($voucherinfo as $vkey) {
					$ppg=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$vkey['purposecode']);
					if(count($ppg)>0){
						foreach ($ppg as $ppgr) {
							$category = $ppgr['pcategory'];
							$categorycode = $ppgr['pcategorycode'];
							$subcategory = $ppgr['psub_category'];
							$subcategorycode = $ppgr['psub_categoryid'];
							$purpose = $ppgr['purpose'];
							$purposecode = $ppgr['purposecode'];
						}
					}else{
						$category ="";
						$categorycode ="";
						$subcategory ="";
						$subcategorycode ="";
						$purpose ="";
						$purposecode ="";
					}
					$acc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$fromaccountid);
					$bankname ="";
					$accountno ="";
					if(count($acc)>0){
						foreach ($acc as $acckey) {
							$bankname = $acckey['bankname'];
							$accountno = $acckey['accountno'];
						}
					}
					//T3
					$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,accountno,account_id,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo,srm_attsno,srm_emailsno) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$fromaccountid."','".$vkey['expenseamount']."','".$vkey['description']."','".$paytoname."','".$vkey['beneficiaryname']."','".$categorycode."','".$category."','".$subcategorycode."','".$subcategory."','".$purpose."','".$purposecode."','".$loginname."','".str_replace("../", "", $vkey['attachmentpath'])."', '".$vkey['srm_attsno']."', '".$vkey['srm_emailsno']."');";
				}
				if($insertqury){
					if ($mconn->multi_query($insertqury) === TRUE) {
						$rrs[]=array('status'=>'Success', 'message'=>'Your receipt/voucher details save successfully');
					}else{
						$rrs[]=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
					}
				}else{
					$rrs[]=array('status'=>'Error', 'message'=>'Query data not available, Try again');
				}
			}
		}
	}else {
		$rrs[]=array('status'=>'Error', 'message'=>'Query data not available, Try again');
	}
	echo json_encode($rrs);
}else if($action=="imprestpaytolist_todropdown"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$imptlist=getimprestpayto($mconn,$companyid,$subcompanyid,$projectid,"all","");
	echo json_encode($imptlist);
}else if($action=="savetoodb_authorizedproject_forimprest"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];

	$paytoname = $_POST['paytoname'];
	$bankname = $_POST['bankname'];
	$accountnumber = $_POST['accountnumber'];
	$bankifsccode = $_POST['bankifsccode'];
	
	$loginid = $_POST['loginid'];
	$authirzedprojects = json_decode($_POST['authorizedproject'],TRUE);
	$surethisnew="";
	if(isset($_POST['surethisnew'])){
		$surethisnew=$_POST['surethisnew'];
	}
	$restimp= saveimprestauthorization($mconn,$companyid,$subcompanyid,$projectid,$paytoname,$bankname,$accountnumber,$bankifsccode,$authirzedprojects,$loginid);
	echo json_encode($restimp);
}else if($action=="savetoodb_imprestrequestdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];	
	$fromaccountid = $_POST['accountid'];
	$paytoid = $_POST['paytoname'];
	$requestamount = $_POST['paytoamount'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$purposecode = $_POST['purposecode'];
	$description = $_POST['description'];
	$loginname = $_POST['loginname'];
	$requesttypename = $_POST['requesttypename'];
	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];

	//payto details
	$empinfo=getimprestpayto($conn,$companyid,$subcompanyid,$projectid,"paytoid",$paytoid);
	foreach ($empinfo as $empkey) {
		$paytoname=$empkey['imprestpayto'];
		$payto_bankname=$empkey['paytobankname'];
		$payto_accountno=$empkey['paytoaccountno'];
		$payto_bankifsccode=$empkey['paytoifsccode'];
		$is_intermediate_transfer="";
		$payto_accountid="";
	}

	//beneficiary details
	$accinq=$conn->query("select sno, countryid, cmp_id, subcmp_id, projectid, accountid, bankid, account_type, uploadsheet_formatid, accountname, accountno, ifsccode, bankname, accountholdername FROM tbl_master_account WHERE (tedit is null or tedit='') and  cmp_id='".$companyid."' and subcmp_id in('".str_replace(",", "','", $subcompanyid)."') and projectid in('".str_replace(",", "','", $projectid)."') and accountid in('".str_replace(",", "','", $paytoid)."')");
	$beneficiaryname="";
	if($accinq->num_rows>0){
		while ($accrow=$accinq->fetch_assoc()) {
			$beneficiaryname=$accrow['accountname'];
		}
	}

	$attachedfile = $_FILES['attachedfile']['name'];
	$filepath="";
	$distpath="../../requestdocument/";
	if($_FILES['attachedfile']['name']){
		if ( !is_dir( $distpath ) ) {
		    mkdir($distpath, 0755, true);       
		}
        $temp = explode(".", $_FILES["attachedfile"]["name"]);
		$extension=end($temp);
		$newfilename = round(microtime(true)) . '.' . end($temp);
        move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $distpath . $newfilename);
        $filepath = $distpath . $newfilename;
        $filepath_forother = $distpath . $newfilename;
	}else if($attpathfromemail!=""){
		$filepath_fromemail = $attpathfromemail;
		$filepath_forother = $attpathfromemail;
		$qres = $conn->query("update emailtodb_attach set category='Digitized Without Categorization', category_id=0 where ID={$attsnofromemail}");
		if($qres){
			$catupdate = "update emailtodb_email set cat_status='categorized' where ID='{$emailsnofromemail}' and not exists (select ID from emailtodb_attach where emailtodb_attach.IDEmail='{$emailsnofromemail}' and category_id is null)";
			$cat_result=$conn->query($catupdate);
		}
	}

	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}
	
	$voucherinfo[]=array('beneficiaryname'=>$beneficiaryname,'expenseamount'=>$requestamount, 'voucherno'=>'', 'vouchertype'=>'', 'description'=>$description, 'purposecode'=>$purposecode, 'attachmentpath'=>$filepath_forother, 'docsno'=>'','srm_attsno'=>$attsnofromemail,'srm_emailsno'=>$emailsnofromemail);
	$rrs=array();
	if(strtolower($requesttypename)=="pre-payment"){
		$rrs=unapproved_saveimprestrequest($conn,$countryid,$companyid,$subcompanyid,$projectid,$fromaccountid,$purposetype,$paytoid,$modeoftransfer,$purposecode,$requestamount,$description,$loginname,$filepath,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename);
	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid && $requestamount>0 && $modeoftransfer && $transactionno && $transactiondate && $fromaccountid && $purposecode && $description){
			$rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo);
		}else{
			if(count($voucherinfo)>0){
				foreach ($voucherinfo as $vkey) {
					$ppg=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$vkey['purposecode']);
					if(count($ppg)>0){
						foreach ($ppg as $ppgr) {
							$category = $ppgr['pcategory'];
							$categorycode = $ppgr['pcategorycode'];
							$subcategory = $ppgr['psub_category'];
							$subcategorycode = $ppgr['psub_categoryid'];
							$purpose = $ppgr['purpose'];
							$purposecode = $ppgr['purposecode'];
						}
					}else{
						$category ="";
						$categorycode ="";
						$subcategory ="";
						$subcategorycode ="";
						$purpose ="";
						$purposecode ="";
					}
					$acc=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"accountid",$fromaccountid);
					$bankname ="";
					$accountno ="";
					if(count($acc)>0){
						foreach ($acc as $acckey) {
							$bankname = $acckey['bankname'];
							$accountno = $acckey['accountno'];
						}
					}
					//T3
					$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,date,chequeno,bankname,accountno,account_id,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo) values('".$companyid."','".$subcompanyid."','".$projectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$fromaccountid."','".$vkey['expenseamount']."','".addslashes($vkey['description'])."','".$paytoname."','".$vkey['beneficiaryname']."','".$categorycode."','".$category."','".$subcategorycode."','".$subcategory."','".$purpose."','".$purposecode."','".$loginname."','".str_replace("../", "", $vkey['attachmentpath'])."');";
				}
				if($insertqury){
					if ($mconn->multi_query($insertqury) === TRUE) {
						$rrs[]=array('status'=>'Success', 'message'=>'Your receipt/voucher details save successfully');
					}else{
						$rrs[]=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
					}
				}else{
					$rrs[]=array('status'=>'Error', 'message'=>'Query data not available, Try again');
				}
			}
		}
	}
	echo json_encode($rrs);
}else if($action=="uploadsalarysheet"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$lginfo=getlogindetailsfrommaster($conn,$companyid,"loginid",$loginid);
	$sendfromemail=$lginfo[0]['emailid'];
	// echo $_FILES['uploadsheet']['type'];
	$ext = pathinfo($_FILES['uploadsheet']['name'], PATHINFO_EXTENSION);

	if($sendfromemail && $ext!="csv" && $ext!="txt"){
		$from_email         = "system"."<".$sendfromemail.">"; //from mail, sender email address
	    $recipient_email = 'documents@amanaccounting.in'; //recipient email address
	    // $recipient_email = 'chatursingh011@gmail.com'; //recipient email address
	     
	    //Load POST data from HTML form
	    $sender_name = 'system'; //sender name
	    $reply_to_email = ""; //sender email, it will be used in "reply-to" header
	    $subject     = "Salary Sheet"; //subject for the email
	    $message     = "Please find the attached salary sheet"; //body of the email
	     
	    //Get uploaded file data using $_FILES array
	    $tmp_name = $_FILES['uploadsheet']['tmp_name']; // get the temporary file name of the file on the server
	    $name     = $_FILES['uploadsheet']['name']; // get the name of the file
	    $size     = $_FILES['uploadsheet']['size']; // get size of the file for size validation
	    $type     = $_FILES['uploadsheet']['type']; // get type of the file
	    $error     = $_FILES['uploadsheet']['error']; // get the error (if any)
	 
	    //validate form field for attaching the file
	    if($error > 0)
	    {
	        die('Upload error or No files uploaded');
	    }
	 
	    //read from the uploaded file & base64_encode content
	    $handle = fopen($tmp_name, "r"); // set the file handle only for reading the file
	    $content = fread($handle, $size); // reading the file
	    fclose($handle);                 // close upon completion
	 
	    $encoded_content = chunk_split(base64_encode($content));
	    $boundary = md5("random"); // define boundary with a md5 hashed value
	 
	    //header
	    $headers = "MIME-Version: 1.0\r\n"; // Defining the MIME version
	    $headers .= "From:".$from_email."\r\n"; // Sender Email
	    $headers .= "Reply-To: ".$reply_to_email."\r\n"; // Email address to reach back
	    $headers .= "Content-Type: multipart/mixed;"; // Defining Content-Type
	    $headers .= "boundary = $boundary\r\n"; //Defining the Boundary
	         
	    //plain text
	    $body = "--$boundary\r\n";
	    $body .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
	    $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
	    $body .= chunk_split(base64_encode($message));
	         
	    //attachment
	    $body .= "--$boundary\r\n";
	    $body .="Content-Type: $type; name=".$name."\r\n";
	    $body .="Content-Disposition: attachment; filename=".$name."\r\n";
	    $body .="Content-Transfer-Encoding: base64\r\n";
	    $body .="X-Attachment-Id: ".rand(1000, 99999)."\r\n\r\n";
	    $body .= $encoded_content; // Attaching the encoded file with email
	     
	    $sentMailResult = mail($recipient_email, $subject, $body, $headers);
	 	$rrs=array();
	    if($sentMailResult )
	    {
		    $rrs=array('status'=>'Success', 'message'=>'File uploaded Successfully');
	    }
	    else
	    {
	    	$rrs=array('status'=>'Error', 'message'=>'Sorry but the email could not be sent. Please go back and try again!');
	    }
	}else{
		$rrs=array('status'=>'Error', 'message'=>'This type of file '.$ext.' is not allowed');
	}
	echo json_encode($rrs);
}else if($action=="expensetypehtml"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	?>
	<div class="row">
			<div class="col-md-12 mt-2">
		        <label class="m-2"><input type="radio" name="purposecategory" value="salary" style="transform: scale(2);" /> Salary</label>
		    </div>
			<?php
			$ppg=GetPurposeCategoryfrommaster($mconn,$companyid,$subcompanyid,$projectid,"all","");
		        if(count($ppg)>0){
		          foreach ($ppg as $ppgkey) {
		            if($ppgkey['pcategorycode']>0 && $ppgkey['pcategorycode']<6 && $ppgkey['pcategorycode']!=1){
		        ?>
		        <div class="col-md-12 mt-2">
		        	<label class="m-2"><input type="radio" name="purposecategory" value="<?php echo $ppgkey['pcategorycode']; ?>" style="transform: scale(2);" /> <?php echo $ppgkey['pcategory']; ?></label>
		        </div>
		        <?php
		            }
		          }
		        }
		        ?>
	         <div class="col-md-12 mt-2">
	          <label class="m-2"><input type="radio" name="purposecategory" value="1-1" style="transform: scale(2);" /> Single Purpose Expenditure</label>
	        </div>
	        <div class="col-md-12 mt-2">
	          <label class="m-2"><input type="radio" name="purposecategory" value="1" style="transform: scale(2);" /> Other Than These</label>
	        </div>
	    </div>
    </div>
    <?php
}
else if($action=="getsubcategorywithorder"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subcat = get_purposesubcategorywithorder($conn,$companyid,$subcompanyid,$projectid);
	echo json_encode($subcat);
}
else if($action=="save_subcategoryorder"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	    $order = json_decode($_POST['order'], true); // Array of IDs in new order
	    // Loop through the received order and perform insert or update
	    foreach ($order as $sortOrder => $id) {
	        $stmt = $conn->prepare("
	            INSERT INTO purpose_order_withdrawal (companyid, subcompanyid,project_id, sub_category_id, display_order) 
	            VALUES (?,?,?,?,?) 
	            ON DUPLICATE KEY UPDATE display_order = VALUES(display_order)
	        ");
	        $stmt->bind_param("iiiii", $companyid, $subcompanyid,$projectid,$id, $sortOrder);
	        $stmt->execute();
	    }
	    echo "Order saved successfully!";
	    $conn->close();
	}
}
else if ($action=="getunreconcileclosebook") {
	 $projectid=$_POST['projectid'];
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcompanyid'];
	 $accountid=$_POST['accountid'];
	 $transactiontype=$_POST['transactiontype'];
	 if($transactiontype){
		 $unreconcileddata=closetobookdetails($mconn,$companyid,$subcompanyid,$projectid,$transactiontype,'unreconcile_withaccountid',$accountid);
	 }
	 else{
		 $unreconcileddata=closetobookdetails($mconn,$companyid,$subcompanyid,$projectid,$transactiontype,'all_unreconcile_withaccountid',$accountid);
		}
	echo json_encode($unreconcileddata);
}
else if ($action=="getbankstatement") {
	 $rooturl =$_POST['routurl'];
	 $projectid=$_POST['projectid'];
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcompanyid'];
	 $accountid=$_POST['accountid'];
	 $bankdata=bankstatementdetails($mconn,$companyid,$subcompanyid,$projectid,'all_withaccountid',$accountid);
	echo json_encode($bankdata);
}
else if($action=='deletecheckdata'){
	$accountid = $_POST["account_id"];
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

	$mconn->begin_transaction();
	try{
		$querchk=$conn->query("delete from tbl_bank_check where account_id='".$accountid."'");
		}
	catch (mysqli_sql_exception $e) {
    	$mconn->rollback();
    	$functioninfo=array('status'=>'Error', 'message'=>$e->getMessage());
	}
}
else if($action=='checkdata'){
	$uncheckdata = json_decode($_POST["uncheckdata"],true);
	$closingbalance = $_POST["closingbalance"];
	$openingbalance = $_POST["openingbalance"];
	$accountid = $_POST["account_id"];
	$functioninfo=array();
	$inconsistent=checkconsistency($uncheckdata,$openingbalance,$closingbalance);
	if (is_array($inconsistent) && !empty($inconsistent)) {
	    $functioninfo=array('status'=>'Error', 'message'=>$inconsistent['message']);
	} else {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    	$conn->begin_transaction();
    	try{
    		$insertquery="Insert into tbl_bankstatement(companyid,subcompanyid,projectid,bankid,account_id,accountno,bankname,valuedate,postdate,remitterbranch,description,chequeno,debitamount,creditamount,balance,debitamountnew,creditamountnew,balancenew,attach_id,paytoname)values";
			$insertvalues="";
			$newentry=0;
			$duplicate=0;
			$balance = 0;
			$prevbalance = 0;
    		foreach ($uncheckdata as $exlrow) {
				$querchk=$conn->query("select sno from tbl_bankstatement where balance='".$exlrow['balance']."'  and valuedate='".convertDateToYMD($exlrow['value_date'])."' and projectid='".$exlrow['project_id']."' and account_id='".$exlrow['account_id']."' and creditamount='".$exlrow['creditamount']."' and debitamount='".$exlrow['debitamount']."' and ifnull(Tedit,'')=''");
				if($querchk->num_rows==0){

					if($insertvalues){
						$balance = (float)$prevbalance + (float)$exlrow['creditamount'] - (float)$exlrow['debitamount'];
						$prevbalance = $balance;
						if(trim($exlrow['balance'])=="0"||trim($exlrow['balance'])==""){
							$bankbal = $balance;
						}
						else{
							$bankbal = trim(addslashes($exlrow['balance']??''));
						}
						$insertvalues.=",('".$exlrow['company_id']."','".$exlrow['subcompany_id']."','".$exlrow['project_id']."','".$exlrow['bank_id']."','".$exlrow['account_id']."','".$exlrow['account_no']."','".$exlrow['bank_name']."','".trim(addslashes($exlrow['value_date']??''))."','".trim(addslashes($exlrow['post_date']??''))."','".trim(addslashes($exlrow['branch_name']))."','".trim(addslashes($exlrow['description']??''))."','".trim(addslashes($exlrow['chequeno']??''))."','".trim(addslashes($exlrow['debitamount']??''))."','".trim(addslashes($exlrow['creditamount']??''))."','".$bankbal."','".trim(addslashes($exlrow['debitamount']??''))."','".trim(addslashes($exlrow['creditamount']??''))."','".$balance."','','')";
					}else{
						$balance = (float)$openingbalance + (float)$exlrow['creditamount'] - (float)$exlrow['debitamount'];
						$prevbalance = $balance;
						if(trim($exlrow['balance'])=="0"||trim($exlrow['balance'])==""){
							$bankbal = $balance;
						}
						else{
							$bankbal = trim(addslashes($exlrow['balance']??''));
						}
						$insertvalues="('".$exlrow['company_id']."','".$exlrow['subcompany_id']."','".$exlrow['project_id']."','".$exlrow['bank_id']."','".$exlrow['account_id']."','".$exlrow['account_no']."','".$exlrow['bank_name']."','".trim(addslashes($exlrow['value_date']??''))."','".trim(addslashes($exlrow['post_date']??''))."','".trim(addslashes($exlrow['branch_name']))."','".trim(addslashes($exlrow['description']??''))."','".trim(addslashes($exlrow['chequeno']??''))."','".trim(addslashes($exlrow['debitamount']??''))."','".trim(addslashes($exlrow['creditamount']??''))."','".$bankbal."','".trim(addslashes($exlrow['debitamount']??''))."','".trim(addslashes($exlrow['creditamount']??''))."','".$balance."','','')";
					}
					$newentry++;
				}
				else{
					$duplicate++;
				}
			}
			
				if($insertvalues){
					$qry=$insertquery.$insertvalues;
					$conn->query($qry);
					if(mysqli_affected_rows($conn)>0){
						$conn->query("delete from tbl_bank_check where account_id='".$accountid."'");
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
				}else{
					$functioninfo=array('status'=>'Error', 'message'=>'All entry already exist, Please try again with another sheet','accountid'=>$accid);
				}
				$conn->commit();
		}
		catch (mysqli_sql_exception $e) {
        	$conn->rollback();
        	$functioninfo=array('status'=>'Error', 'message'=>$e->getMessage());
    	}
	}
	if($functioninfo["status"]=="Success"){
		verifybankbalance($conn,$accountid);
		makekeyautofrombankstatment($conn,$accountid);
		reconstructbookwith_unknown($conn,$companyid,$subcompanyid,$projectid,$accountid,'withdrawn');
		reconstructbookwith_unknown_credit($conn,$companyid,$subcompanyid,$projectid,$accountid,'deposit');
	}
	echo json_encode($functioninfo);
}
else if ($action=="getbankstatementuncheck") {
	 $rooturl =$_POST['routurl'];
	 $projectid=$_POST['projectid'];
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcompanyid'];
	 $bankdata=bankstatementdetails($mconn,$companyid,$subcompanyid,$projectid,'unchecked','');
	echo json_encode($bankdata);
}
else if ($action=="getbankstatementpdf") {
	 $rooturl =$_POST['routurl'];
	 $projectid=$_POST['projectid'];
	 $companyid=$_POST['companyid'];
	 $subcompanyid=$_POST['subcompanyid'];
	 $accountid=$_POST['accountid'];
	 $datefrom=$_POST['datefrom'];
	 $dateto=$_POST['dateto'];
	 $bankdata=bankstatementpdf($mconn,$companyid,$subcompanyid,$projectid,$accountid,$datefrom,$dateto,$rooturl);
	 echo $bankdata;
}
else if ($action=="getaccountinfobyproject") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$shortby="projectid";
	$accountlist=array();
	$shortbyvalue=$projectid;
	$projectlist =getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	foreach ($projectlist as $reprojrow) 
	{
		$accountlist[]= array("accid"=>$reprojrow['accountid'], "accname"=>$reprojrow['accountname'],"accno"=>$reprojrow['accountno'],"bankname"=>$reprojrow['bankname'],"bankid"=>$reprojrow['bankid']);
	}
	echo json_encode($accountlist);
}else if ($action=="getaccinfoandgetstatusofstatement") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$fromdate=$_POST['fromdate'];
	$todate=$_POST['todate'];
	$shortby="projectid";
	$accountlist=array();
	$shortbyvalue=$projectid;
	$projectlist =getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	foreach ($projectlist as $reprojrow) 
	{
		$mindate="";
		$maxdate="";
		$status="";
		$verifiedstatus="";
		if ($fromdate) {

			$qry=$conn->query("select min(valuedate) as mindate,max(valuedate) as maxdate from tbl_bankstatement where account_id='".$reprojrow['accountid']."' and (valuedate between '".$fromdate."' and '".$todate."')  ");
		}else{
			$qry=$conn->query("select min(valuedate) as mindate,max(valuedate) as maxdate from tbl_bankstatement where account_id='".$reprojrow['accountid']."'  ");
		}
		
		if ($qry->num_rows>0) {
			$rowdat=$qry->fetch_assoc();
			if ($fromdate) {

				$qryverfid=$conn->query("Select ifnull(sno,'') as sno from tbl_bankstatement where account_id='".$reprojrow['accountid']."' and (verify_balance='' or verify_balance is null or verify_balance='unverifyed') and (valuedate between '".$fromdate."' and '".$todate."')");
			}else{
				$qryverfid=$conn->query("Select ifnull(sno,'') as sno from tbl_bankstatement where account_id='".$reprojrow['accountid']."' and (verify_balance='' or verify_balance is null or verify_balance='unverifyed')");
			}
			
			if ($qryverfid->num_rows>0) {
				$verifiedstatus="Unverified";
			}else{
				$verifiedstatus="Verified";
			}

			if (is_null($rowdat['mindate'])) {
				$mindate="Not Available";
				$status="Not Available";
				$verifiedstatus="NA";
			}else{
				$mindate=date('d-M-Y', strtotime($rowdat['mindate']));
				$status="Available";

			}

			 if (is_null($rowdat['maxdate'])) {
			 	$maxdate="Not Available";
			 	$status="Not Available";
			 }else{
			 	$maxdate=date('d-M-Y', strtotime($rowdat['maxdate']));
			 	$status="Available";
			 }
			
			
		}
		$accountlist[]= array("accid"=>$reprojrow['accountid'], "accname"=>$reprojrow['accountname'],"accno"=>$reprojrow['accountno'],"bankname"=>$reprojrow['bankname'],"bankid"=>$reprojrow['bankid'],"mindate"=>$mindate,"maxdate"=>$maxdate,'verifiedstatus'=>$verifiedstatus,'status'=>$status);
	}
	echo json_encode($accountlist);
}else if ($action=="getaccinfoandgetstatusofstatementpdf") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$fromdate=$_POST['fromdate'];
	$todate=$_POST['todate'];
	$accountid=$_POST['accountid'];
	$shortby="accountid";
	$accountlist=array();
	$shortbyvalue=$accountid;
	$projectlist =getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	foreach ($projectlist as $reprojrow) 
	{
		$mindate="";
		$maxdate="";
		$status="";
		$verifiedstatus="";
		
		

			$qryverfid=$conn->query("Select datefrom,dateto,ifnull(filpathpdf,'') as filpathpdf from tbl_directuploads where accountid='".$accountid."' and category='bankstatement'");
			
			if ($qryverfid->num_rows>0) {
				$verifiedstatus="Verified";
				while ($rowp=$qryverfid->fetch_assoc()) {
					$accountlist[]= array("accid"=>$reprojrow['accountid'], "accname"=>$reprojrow['accountname'],"accno"=>$reprojrow['accountno'],"bankname"=>$reprojrow['bankname'],"bankid"=>$reprojrow['bankid'],"mindate"=>$rowp['datefrom'],"maxdate"=>$rowp['dateto'],'verifiedstatus'=>$verifiedstatus,'status'=>"",'files'=>$rowp['filpathpdf']);
				}
			}else{
				$verifiedstatus="Unverified";
			}
			
			
		
		$accountlist[]= array("accid"=>$reprojrow['accountid'], "accname"=>$reprojrow['accountname'],"accno"=>$reprojrow['accountno'],"bankname"=>$reprojrow['bankname'],"bankid"=>$reprojrow['bankid'],"mindate"=>$mindate,"maxdate"=>$maxdate,'verifiedstatus'=>$verifiedstatus,'status'=>$status);
	}
	echo json_encode($accountlist);
}else if ($action=="getaccinfoandgetstatusofstatementpdfexcel") {
	$companyid=$_POST['companyid'];
	$subcompanyid=$_POST['subcompanyid'];
	$projectid=$_POST['projectid'];
	$filetype=$_POST['filetype'];
	$filelist=array();
	if ($filetype=='pdf') {
		//echo "Select datefrom,dateto,ifnull(filpathpdf,'') as filpath,accountid from tbl_directuploads where projectid='".$projectid."' and category='bankstatement'";
		$qryfile=$conn->query("Select datefrom,dateto,ifnull(filpathpdf,'') as filpath,accountid from tbl_directuploads where projectid='".$projectid."' and category='bankstatement' and filpathpdf is not null and filpathpdf<>''");
	}else if ($filetype=='excel') {
		$qryfile=$conn->query("Select datefrom,dateto,ifnull(filpathexcel,'') as filpath,accountid from tbl_directuploads where projectid='".$projectid."' and category='bankstatement' and filpathexcel is not null and filpathexcel<>''");
	}

	if ($qryfile->num_rows>0) {
		while ($rowf=$qryfile->fetch_assoc()) {
			$shortby="accountid";
			$shortbyvalue=$rowf['accountid'];
			$projectlist =getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
			$accountname="";
			$accountnumber="";
			$bankname="";
			$bankid="";
			foreach ($projectlist as $reprojrow){
				
				$accountname=$reprojrow['accountname'];
				$accountnumber=$reprojrow['accountno'];
				$bankname=$reprojrow['bankname'];
				$bankid=$reprojrow['bankid'];
			}

			$codevalue = basename($rowf['filpath'], ".pdf");

			$filelist[]= array("accid"=>$rowf['accountid'], "accname"=>$accountname,"accno"=>$accountnumber,"bankname"=>$bankname,"bankid"=>$bankid,"datefrom"=>date('d-m-Y', strtotime($rowf['datefrom'])),"dateto"=>date('d-m-Y', strtotime($rowf['dateto'])),'files'=>$rowf['filpath'],'filecode'=>$codevalue); 

		}
	}

	echo json_encode($filelist);
}
?>