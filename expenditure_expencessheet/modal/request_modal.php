<?php 
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../../functions/function_master.php');
// require('../../automation/automation_functionmaster.php');
require('../../functions/function_savetodb.php');
require('../../functions/emailsend_request_functions.php');
include('../../SimpleXLSX.php');
include('../../SimpleXLS.php');
$action=$_GET['action'];
if($action=="beneficiarylist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$bnflist=getbeneficiaryinfodetails($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
	echo json_encode($bnflist);
}else if($action=="paytofrommaster_incometax"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,"likelypayto","income tax");
	echo json_encode($paytoinfo);
}else if($action=="getpaytofrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoinfo=getpaytoinfo($conn,$companyid,$subcompanyid,"projectid",$projectid);
	echo json_encode($paytoinfo);
}else if($action=="getpayerfrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoinfo=getpayerinfoworking($mconn,$companyid,$subcompanyid,"projectid",$projectid);
	
	echo json_encode($paytoinfo);
}else if($action=="getloanpaytolist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subcategoryid = $_POST['subcategoryid'];
	$pppd=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"allloanaccount","");
	echo json_encode($pppd);
}else if($action=="getinvoicetypelist"){
	$invlicelist="";
	$invl=getinvoicetype($mconn);
	if(count($invl)>0){
		if(count($invl)>1){
			$invlicelist.='<option value="" selected disabled>Choose Invoice Type</option>';
		}
		foreach ($invl as $invkey) {			
			$invlicelist.='<option value="'.$invkey['invoicetype'].'">'.$invkey['invoicetype'].'</option>';	
		}
	}else{
		$invlicelist.='<option>Invoice type not available</option>';
	}
	echo $invlicelist;
}else if($action=="getpaytolist_withhtml"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecodeval = $_POST['purposecodeval'];
	$lpaytolist="";
	$lpaytolist.='<label>Payto Name</label>';
	$lpaytolist .= '<i class="fa fa-plus-circle m-2" id="opennewpaytomodal_forexp" style="cursor: pointer; font-size:22px;"></i>';
	$lpaytolist .= '<i class="fa fa-sync float-right m-2 mr-4 ml-4 refeshpaytolist" onclick=\'request_paytofrommaster_withpaytoid("hidden_baseurl", "hidden_companyid", "hidden_subcompanyid", "hidden_projectid", "paytoname", "", "");\' style="cursor: pointer; color: green;"></i>';
	$lpaytolist.='<select id="paytoname" class="form-control select2">';
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
}else if($action=="getinternaltransferpaytolist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$lpaytolist="";
	$lpayto=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"allaccountofprojectonlybank",$projectid);
	if(count($lpayto)>0){
		if(count($lpayto)==1){
			$lpaytolist.='<option value="" selected disabled>Not available internal bank</option>';
		}else{
			if(count($lpayto)>1){
				$lpaytolist.='<option value="" selected disabled>Select payto name</option>';
			}
			foreach ($lpayto as $lpkey) {
				$lpaytolist.='<option value="'.$lpkey['paytoid'].'" data-rqaccountid="'.$lpkey['accountid'].'">'.$lpkey['accountname'].'('.$lpkey['accountno'].')</option>';	
			}
		}
	}else{
		$lpaytolist.='<option value="" selected disabled style="color:red;">Not available internal bank</option>';
	}
	// echo $lpaytolist;
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
}else if($action=="paytolist_fixeddeposit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$purposecodeval = $_POST['purposecodeval'];
	$lpaytolist="";
	$lpayto=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"onlyfd_accountlist","");
	if(count($lpayto)>0){
		if(count($lpayto)>1){
			$lpaytolist.='<option value="" selected disabled>Select fixed deposit account</option>';
		}
		foreach ($lpayto as $lpkey) {
			$lpaytolist.='<option value="'.$lpkey['paytoid'].'" data-rqaccountid="'.$lpkey['accountid'].'">'.$lpkey['accountname'].'(****'.substr($lpkey['accountno'], -4).')</option>';
		}
	}else{
		$lpaytolist.='<option value="">Not available of fixed deposit account</option>';
	}
	echo json_encode($lpaytolist);
}else if($action=="paytolist_cashpayment"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$lpaytolist="";
	$lpayto=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"cash",$projectid);
	// print_r($lpayto);
	if(count($lpayto)>0){
		if(count($lpayto)>1){
			$lpaytolist.='<option value="" selected disabled>Select cash account</option>';
		}
		foreach ($lpayto as $lpkey) {
			$lpaytolist.='<option value="'.$lpkey['paytoid'].'" data-rqaccountid="'.$lpkey['accountid'].'">'.$lpkey['accountname'].'(*******'.substr($lpkey['accountno'], -5).')</option>';	
		}
	}else{
		$lpaytolist.='<option value="" selected disabled style="color:red;">Not available of cash account</option>';
	}
	// echo $lpaytolist;
	echo json_encode($lpaytolist);
}else if($action=="paytolist_creditcard"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$lpaytolist="";
	$lpayto=getaccountinformation($conn,$companyid,$subcompanyid,$projectid,"creditcard",$projectid);
	// print_r($lpayto);
	if(count($lpayto)>0){
		if(count($lpayto)>1){
			$lpaytolist.='<option value="" selected disabled>Select credit card account</option>';
		}
		foreach ($lpayto as $lpkey) {
			$lpaytolist.='<option value="'.$lpkey['paytoid'].'" data-rqaccountid="'.$lpkey['accountid'].'">'.$lpkey['accountname'].'(*******'.substr($lpkey['accountno'], -5).')</option>';	
		}
	}else{
		$lpaytolist.='<option value="" selected disabled style="color:red;">Not available of cash account</option>';
	}
	// echo $lpaytolist;
	echo json_encode($lpaytolist);
}else if($action=="paytolist_supplier"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$lpaytolist="";
	$lpayto=getsupplierpaymentpayto($mconn,$companyid,$subcompanyid,$projectid,"all","");
	// print_r($lpayto);
	if(count($lpayto)>0){
		if(count($lpayto)>1){
			$lpaytolist.='<option value="" selected disabled>Select supplier name</option>';
		}
		foreach ($lpayto as $lpkey) {
			$lpaytolist.='<option value="'.$lpkey['paytoid'].'">'.$lpkey['paytoname'].'</option>';	
		}
	}else{
		$lpaytolist.='<option value="" selected disabled style="color:red;">Not available of registered supplier</option>';
	}
	// echo $lpaytolist;
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
}else if($action=="savetoodb_newpayertodetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$bankname = $_POST['bankname'];
	$accountnumber = $_POST['accountnumber'];
	$bankifsccode = $_POST['bankifsccode'];	
	$loginname = $_POST['loginname'];	
	$restimp= savetodbnewpayerto_details($conn,$paytoname,$accountnumber,$bankname,$bankifsccode,"","","",$companyid,$subcompanyid,$projectid,'','',$loginname);
	echo json_encode($restimp);
}else if($action=="updatepaytobankinfo_withreference"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$paytobankname = $_POST['bankname'];
	$paytoaccountnumber = $_POST['accountnumber'];
	$paytobankifsccode = $_POST['bankifsccode'];
	$requestno = $_POST['requestno'];
	$referenceno = $_POST['referenceno'];
	$uud=0;
	if($requestno){
		$conn->query("UPDATE tbl_paymentrequest SET payto_bankname='".$paytobankname."',payto_accountno='".$paytoaccountnumber."',payto_bankifsccode='".$paytobankifsccode."' where request_no in('".str_replace(",", "','", $requestno)."')");
		if(mysqli_affected_rows($conn)>0){
			$uud=1;
		}
	}
	if($referenceno){
		$conn->query("UPDATE tbl_maintransaction SET paytobankname='".$paytobankname."',paytoaccountno='".$paytoaccountnumber."',paytoifsc='".$paytobankifsccode."' where reference_no in('".str_replace(",", "','", $referenceno)."')");
		if(mysqli_affected_rows($conn)>0){
			$uud=1;
		}
	}
	$rrst=array();
	if($uud==1){
		$rrst[]=array('status'=>'Success', 'message'=>'Payto Bank details update successfully');
	}else{
		$rrst[]=array('status'=>'Error', 'message'=>'Payto Bank details did not update');
	}
	echo json_encode($rrst);
}else if($action=="savetoodb_newbeneficiaryname"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$beneficiaryname = $_POST['beneficiaryname'];
	$loginname = $_POST['loginname'];
	$btnmsg= savetodbnewbeneficiary_details($conn,$companyid,$subcompanyid,$projectid,$beneficiaryname,$loginname);
	echo json_encode($btnmsg);
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
}else if($action=="checkexist_payertoname"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$paytoname = $_POST['paytoname'];
	$paytolist=getpayerinfoworking($conn,$companyid,$subcompanyid,"likelypayto",$paytoname);
	$pthtml="";
	if(count($paytolist)>0){
		$pthtml.="<p>Already exist in payto master</p>";
		foreach ($paytolist as $imkey) {
			$pthtml.="<label><input type='radio' name='payradio' values='".$imkey['payto']."' /> &nbsp;&nbsp;&nbsp;".$imkey['payto']."</label></br/>";
		}
	}
	echo json_encode($pthtml);
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
	$shortbyvalue = $_POST['shortbyvalue'];
	if($categoryidval=="" || $categoryidval==null || $categoryidval=="undefined" || $categoryidval=="all"){
		$shortby = "allsubcaterylist_withoutcategoryid";
		$shortbyvalue =$categoryidval; 
	}else if($categoryidval=="onlysalary"){
		$shortby = "purposesubcategorynamelikely";
		$shortbyvalue ="salar"; 
	}else if($categoryidval=="onlysalaryofclientsubcategory"){
		$shortby = "clientcategorynamelikely";
		$shortbyvalue ="salar"; 
	}else{
		$shortby = "allsubcaterylist_withcategoryid";
		$shortbyvalue =$categoryidval;
	}
	$pscategorylist=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($pscategorylist);
}else if($action=="purpose_subcategoryfrommaster_credit"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	if($categoryidval=="" || $categoryidval==null || $categoryidval=="undefined" || $categoryidval=="all"){
		$shortby = "allsubcaterylist_withoutcategoryid";
		$shortbyvalue =$categoryidval; 
	}else{
		$shortby = "allsubcaterylist_withcategoryid";
		$shortbyvalue =$categoryidval;
	}
	$pscategorylist=getcreditpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	echo json_encode($pscategorylist);
}else if($action=="purposecodefrommaster"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	$subcategoryidval = $_POST['subcategoryidval'];
	if($categoryidval!=""  && $subcategoryidval=="only_tds_subcateory"){
		$shortby = "purposesubcategorynamelikely";
		$shortbyvalue = "tax";
	}else if($categoryidval!=""  && $subcategoryidval==""){
		$shortby = "purposecategory";
		$shortbyvalue = $categoryidval;
	}else if($categoryidval!=""  && $subcategoryidval!="" ){
		$shortby = "withcategory_subcategory";
		$shortbyvalue = $categoryidval.'|'.$subcategoryidval;
	}else if($subcategoryidval=="only_loan_subcateory" ){
		$shortby = "purposesubcategorynamelikely";
		$shortbyvalue = "loan";
	}else{
		// $shortby = "all";
		$shortby = "allmarkbynote4";
		$shortbyvalue =="all";
	}
	// echo "sss: ".$shortbyvalue;
	$pscategorylist=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	// print_r($pscategorylist);
	echo json_encode($pscategorylist);
}else if($action=="purposecodefrommaster_withclientdata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	$subcategoryidval = $_POST['subcategoryidval'];
	if($categoryidval!=""  && $subcategoryidval=="onlysalaryofclientsubcategory"){
		$shortby = "clientcategorynamelikely";
		$shortbyvalue = "salar";
	}else{
		$shortby = "allmarkbynote4";
		$shortbyvalue =="all";
	}
	// echo "sss: ".$shortbyvalue;
	$pscategorylist=getpurposenamewithcat($mconn,$companyid,$subcompanyid,$projectid,$shortby,$shortbyvalue);
	// print_r($pscategorylist);
	echo json_encode($pscategorylist);
}else if($action=="purposecodefrommaster_markfromnote4"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$categoryidval = $_POST['categoryidval'];
	$subcategoryidval = $_POST['subcategoryidval'];
	if($categoryidval!=""  && $subcategoryidval!="" ){
		$shortby = "allmarkbynote4_withsubcategory";
		$shortbyvalue = $categoryidval.'|'.$subcategoryidval;
	}else{
		$shortby = "allmarkbynote4";
		$shortbyvalue =="markbynote4";
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
	echo json_encode($tbllist);
}else if($action=="imprestaccountdetails"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$tbllist=getaccountinformation($mconn,$companyid,$subcompanyid,$projectid,"Imprest","");
	// print_r($tbllist);
	echo json_encode($tbllist);
}else if($action=="employeelistforsalary"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$employeetype = $_POST['employeetype'];
	
	$lpaytolist="";
	if($employeetype=="regular"){
		$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"projectactive_regularemployee",$projectid);
	}else if($employeetype=="parttime"){
		$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"active_parttimeemployeeid",$projectid);
	}else if($employeetype=="trainee"){
		$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"projectactive_traineeemployee",$projectid);
	}else{
		$lpayto=employeemaster($conn,$companyid,$subcompanyid,$projectid,"allactiveprojectemployee",$projectid);
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
}else if($action=="savetoodb_billpaymentrequest"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$billid = $_POST['billid'];
	$billdbsno = $_POST['billdbsno'];
	$loginid = $_POST['loginid'];
	$purposecode="";
	$billdetails=billmasteruniversal_bychatur($conn,$companyid,$subcompanyid,$projectid,"id",$billid);
	// print_r($billdetails);
	// die();
	$billdurationid ="";
	if(count($billdetails)>0){
		foreach ($billdetails as $billdkey) {
			$billid = $billdkey['id'];
			$typeofbillid = $billdkey['typeofbill'];
			$locationid = $billdkey['identifierlocation'];
			$subprojectid = $billdkey['subproject'];
			$paytoid = $billdkey['paytoid'];
			$beneficiaryname = $billdkey['beneficiaryname'];
			$purposecode = $billdkey['purposecode'];
			$billdescriptionfrmtbl = $billdkey['billdescription'];
			$billdurationid = $billdkey['billduration'];
			$billdurationdays = $billdkey['billdurationdays'];
			$responsablepursonname = $billdkey['responsablepursonname'];
		}
	}	
	$accountid = "";
	$requestamount = $_POST['billamount'];
	$billremark = $_POST['billremark'];
	$modeoftransfer = "online";
	$transactionno = "";
	$transactiondate = "";
	$purposecategoryid = "";
	$purposesubcategoryid = "";
	$purposename = "";
	if($purposecode){
		$ppg=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
		if(count($ppg)>0){
			foreach ($ppg as $ppgr) {
				$purposecategoryid = $ppgr['pcategorycode'];
				$purposesubcategoryid = $ppgr['psub_categoryid'];
				$purposename = $ppgr['purpose'];
			}
		}else{
			$returnmsg=array('status'=>'Error', 'message'=>'Purpose details did not matched with our purpose maste, contact to web admin to update correct purpose in utility master table.');
			echo json_encode($returnmsg);
			die();
		}
	}

	$requestdescription = $billremark;
	$loginname = $_POST['loginname'];
	$requesttypename = "pre-payment";

	$updatecategoriz = "";	
	$attpathfromemail = "";
	$attsnofromemail = "";
	$emailsnofromemail = "";
	$audiofile = "";
	$voiceinstid = "";
	$invoicedata=array();
	$invoice_totalrowsamount=0;
	$invoice_totalgstamount=0;
	$invoice_totalrowsdiscount=0;
	$invoice_otherdiscount=0;
	$invoice_totalpayableamount=0;
	$invoicetype = $_POST['billtype'];
	$invoiceno = $_POST['billno'];
	$invoicedate = $_POST['billdate'];
	$paymentduedate = $_POST['paymentduedate'];
	if($billdurationdays=="" || $billdurationdays==null || $billdurationdays=="null" || $billdurationdays=="undefined" || $billdurationdays=="0" ){
		if($billdurationid){
			$billdurationinfo=billdurationmaster_bychatur($conn,"id",$billdurationid);
			$billdurationdays=0;
			if(count($billdurationinfo)>0){
				foreach ($billdurationinfo as $bldkey) {
					$billdurationdays=$bldkey['durationdays'];
				}
			}
		}
	}

	$isforproduction ="";
	
	
	$distpath="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".date('Ymd').'/';
	$filepath_fromemail="";
	$filepath="";
	$audiouploadFile="";
	if($_FILES['billattachedfile']['name']){
		if ( !is_dir( $distpath ) ) {
		    mkdir($distpath, 0755);       
		}
		$temp = explode(".", $_FILES["billattachedfile"]["name"]);
		$extension=end($temp);
		$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        move_uploaded_file($_FILES["billattachedfile"]["tmp_name"], $distpath . $newfilename);
        $filepath = $distpath . $newfilename;
        // $filepath = str_replace("../", "", $filepath);
	}
	// echo "file: ".$filepath;

	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}
	//paytodetails	
	$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	foreach ($ppt as $pptkey) {
		$paytoname = $pptkey['paytoname'];
	}
	if($beneficiaryname==null || $beneficiaryname=="" || $beneficiaryname=="undefined"){
		$beneficiaryname = $paytoname;
	}

	// echo "bill no: ".$billdbsno;
	if($billdbsno=="" || $billdbsno==null || $billdbsno=="undefined"){
		$data0 = ['companyid' => $companyid,'subcompanyid' => $subcompanyid,'projectid' => $projectid,'subprojectid' => $subprojectid,'billid' => $billid,'typeofbillid' => $typeofbillid,'locationid' => $locationid,'billdurationid' => $billdurationid,'purposecode' => $purposecode,'paytoid' => $paytoid,'beneficiaryname' => $beneficiaryname,'billingdate' => $invoicedate,'billduedate' => $paymentduedate,'billdescription' => $requestdescription,'responsablepursonid' => $responsablepursonname,'billamount'=>$requestamount,'billno'=>$invoiceno];
		
		$nxtbill0 = insertBillTransaction($conn, $data0);
		$billdbsno=$nxtbill0['insertid'];
	}
	$nextbillingdate=getNextDueDateAfterDays($invoicedate, $billdurationdays);
	$datediffdays = getDateDifference($invoicedate, $paymentduedate);
	$returnmsg=array();
	if(isValidDate($nextbillingdate,'Y-m-d')==1){
		$billduedate=getNextDueDateAfterDays($nextbillingdate, $datediffdays);
		$data = ['companyid' => $companyid,'subcompanyid' => $subcompanyid,'projectid' => $projectid,'subprojectid' => $subprojectid,'billid' => $billid,'typeofbillid' => $typeofbillid,'locationid' => $locationid,'billdurationid' => $billdurationid,'purposecode' => $purposecode,'paytoid' => $paytoid,'beneficiaryname' => $beneficiaryname,'billingdate' => $nextbillingdate,'billduedate' => $billduedate,'billdescription' =>'','responsablepursonid' => $responsablepursonname,'billamount'=>$requestamount,'billno'=>$invoiceno];
		
		// print_r($data);
		// die();
		$nxtbill = insertBillTransaction($conn, $data);

		// print_r($nxtbill);
		if($nxtbill['status']=="Success"){
			if(strtolower($requesttypename)=="pre-payment"){
				$purposetype="";
				$rrs=unapproved_saverequestdataforall($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$paytoid,$modeoftransfer,$requestamount,$requestdescription,$purposecategoryid,$purposesubcategoryid,$purposecode,$purposename,$loginname,$filepath,$filepath,$voiceinstid,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename,$invoicedate,$invoiceno,$invoicetype,$invoicedata,$invoice_totalrowsamount,$invoice_totalgstamount,$invoice_totalrowsdiscount,$invoice_otherdiscount,$invoice_totalpayableamount,$beneficiaryname,$paymentduedate,$isforproduction);
				// print_r($rrs);
				if(count($rrs)>0){
					foreach ($rrs as $rrskey) {
						if($rrskey['status']=="Success"){
							if($billdbsno){
								$conn->query("update strs_bill_transactions set uploadreferencefiles='".$filepath."',referenceno='".$rrskey['requestno']."', requestedbyid='".$loginid."', billdescription='".$requestdescription."',billingdate='".$invoicedate."',billduedate='".$paymentduedate."',billno='".$invoiceno."', billamount='".$requestamount."' where sno='".$billdbsno."' and (referenceno is null or referenceno='')");								
								if(mysqli_affected_rows($conn)>0){
									$returnmsg=array('status'=>'Success', 'message'=>$rrskey['message']);
								}else{
									$returnmsg=array('status'=>'Error', 'message'=>'final transaction table updation is faild');
								}
							}
						}else{
							if($nxtbill['insertid']){
								$conn->query("delete from strs_bill_transactions where sno='".$nxtbill['insertid']."'");
							}
							$returnmsg=array('status'=>'Error', 'message'=>'Request genration is failed due to'.$rrskey['message']);
						}
					}
				}else{
					$returnmsg=array('status'=>'Error', 'message'=>'Record not available while request creating');
				}
			}
			else {
				$returnmsg=array('status'=>'Error', 'message'=>'Request type not defined');
			}
		}else{
			$returnmsg=array('status'=>'Error', 'message'=>$nxtbill['message']);
		}
	}else{
		$returnmsg=array('status'=>'Error', 'message'=>'Invalid billing date');
	}
	echo json_encode($returnmsg);
}else if($action=="savetootherthanrequest"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$accountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$paytoamount = $_POST['paytoamount'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$purposecategoryid = $_POST['purposecategoryid'];
	$purposesubcategoryid = ""; 
	$balanceamountforreceipt = $_POST['balanceamountforreceipt'];	
	$requesttypename = $_POST['requesttypename'];
	// $loginid = $_POST['loginid'];
	$loginname = $_POST['loginname'];
	$updatecategoriz = $_POST['updatecategoriz'];

	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];
	$paymentduedate = $_POST['paymentduedate'];
	if(isset($_POST['isforproduction'])=="yes"){
		$isforproduction = $_POST['isforproduction'];
	}else{
		$isforproduction ="";
	}	
	$categoryofrequest = $_POST['categoryofrequest'];


	$filepath="";
	$filepath_fromemail="";
	// $distpath="../../attachementfile/requestdocument/";
	$distpath="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".date('Ymd').'/';
	if($attpathfromemail!=""){
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

	$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	$paytoname="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$is_intermediate_transfer="";
	$payto_accountid="";
	foreach ($ppt as $pptkey) {
		$paytoname= $pptkey['paytoname'];
		$payto_bankname=$pptkey['tobankname'];
		$payto_accountno=$pptkey['toaccountno'];
		$payto_bankifsccode=$pptkey['tobankifsc'];
		$is_intermediate_transfer="";
		$payto_accountid="";
	}
	$projectinfo = projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	foreach ($projectinfo as $projrow) {
		$projectname=$projrow['projectname'];
		$documentsavetype=$projrow['documenttype'];
	}
	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}

	$cvoucherinfo = json_decode($_POST['voucherinfo'],TRUE);
	// print_r($cvoucherinfo);
	
	$ttlbnfamount=0;
	$bnfar=[];
	$amar=[];
	$prpar=[];
	$dscar=[];
	if(count($cvoucherinfo)>0){
		foreach ($cvoucherinfo as $cvkey) {
			$ttlbnfamount += floatval($cvkey['expenseamount']);
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

	if(count($cvoucherinfo)>1){
		$remark="multipal";
	}else{
		$remark=$dscar;
	}
	$insertqury="";
	$rtnmsg=array();
	if(strtolower($requesttypename)=="pre-payment"){
		$rtnmsg=reconstruct_requestbyapp_forother($mconn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$paymentmode,$chequeno,$chequedate,$withdrownamount,$paytoid,$documentsavetype,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$cvoucherinfo,$requesttypename,$loginname,$paymentduedate,$isforproduction,$categoryofrequest);
	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid!="" && $paytoamount==$ttlbnfamount && $paytoamount>0 && count($bnfar)==count($cvoucherinfo) && count($amar)==count($cvoucherinfo) && count($prpar)==count($cvoucherinfo) && count($dscar)==count($cvoucherinfo)){
			$rtnmsg=recunstructbook($authconn,$mconn,$companyid,$subcompanyid,$projectid,$subprojectid,$accountid,$modeoftransfer,$transactionno,$transactiondate,$paytoamount,$paytoid,$cvoucherinfo,$loginname,$purposecategoryid,$purposesubcategoryid,$purposecode,$isforproduction,$remark);
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
					$accountname ="";
					if(count($acc)>0){
						foreach ($acc as $acckey) {
							$bankname = $acckey['bankname'];
							$accountname = $acckey['accountname'];
							$accountno = $acckey['accountno'];
						}
					}
					$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,subprojectid,date,chequeno,bankname,accountname,accountno,account_id,amount,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo,is_purchaseforproduction) values('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$transactiondate."','".$transactionno."','".addslashes($bankname)."','".addslashes($accountname)."','".addslashes($accountno)."','".addslashes($accountid)."','".addslashes($vkey['expenseamount'])."','".addslashes($vkey['expenseamount'])."','".addslashes($vkey['description'])."','".addslashes($paytoname)."','".addslashes($vkey['beneficiaryname'])."','".addslashes($categorycode)."','".addslashes($category)."','".addslashes($subcategorycode)."','".addslashes($subcategory)."','".addslashes($purpose)."','".addslashes($purposecode)."','".$loginid."','".str_replace("../", "", $vkey['attachmentpath'])."','".$isforproduction."');";
				}
				if($insertqury){
					if ($mconn->multi_query($insertqury) === TRUE) {
						$rtnmsg[]=array('status'=>'Success', 'message'=>'Your receipt/voucher details save successfully', 'referenceno'=>'', 'requestno'=>'');
					}else{
						$rtnmsg[]=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
					}
				}else{
					$rtnmsg[]=array('status'=>'Error', 'message'=>'Query data not available, Try again other 111');
				}
			}
		}
	
	}else {
		$rtnmsg[]=array('status'=>'Error', 'message'=>'Query data not available, Try again other 22');
	}
	echo json_encode($rtnmsg);
}else if($action=="savetodb_requestfor_taxinvoicedata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$requesttypename = $_POST['requesttypename'];
	$fromaccountid = $_POST['fromaccountid'];
	$paytoamount = $_POST['paytoamount'];
	$paytoid = $_POST['paytoid'];
	$paytogstno = $_POST['paytogstnumber'];
	$invoicetype = $_POST['invoicetype'];
	$invoiceno = $_POST['invoiceno'];
	$invoicedate = $_POST['invoicedate'];
	$isforproduction = $_POST['isforproduction'];
	$invoicerowdata = json_decode($_POST['invoicerowdata'], true);	
	// print_r($invoicerowdata);

	// closetobook_bankdbsno

	$invoice_totalrowsamount = $_POST['totalrowsamount'];
	$invoice_totalgstamount = $_POST['totalgstamount'];
	$invoice_totalrowsdiscount = $_POST['totalrowsdiscount'];
	// $invoice_otherdiscount = $_POST['otherdiscount'];
	$invoice_totalpayableamount = $_POST['totalpayableamount'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$filepath="";
	$filepath_fromemail="";
	if(isset($_POST['bankdescription'])){
		if($_POST['bankdescription']!="" && $_POST['bankdescription']!="undefined"){
			$requestdescription = $_POST['description'].", Bank description is :- ".$_POST['bankdescription'];
		}else{
			$requestdescription = $_POST['description'];
		}
	}else{
		$requestdescription = $_POST['description'];
	}

	if ($_FILES['attachedfile']['name']) {
	    $distpath = "../../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/requestdocument/" . date('Ymd') . '/';
	    
	    // Check if directory exists and create if it doesn't, with recursive flag
	    if (!is_dir($distpath)) {
	        mkdir($distpath, 0755, true);       
	    }
	    
	    $temp = explode(".", $_FILES["attachedfile"]["name"]);
	    $extension = end($temp);
	    $newfilename = round(microtime(true)) . '_' . date('His') . '.' . $extension;
	    
	    $filepath = $distpath . $newfilename;
	    $filepath_forother =$filepath;	
	    // Move uploaded file and check if successful
	    if (move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $filepath)) {

	    } else {
	    	$filepath="";
	        $rrs[]=array('status'=>'Error', 'message'=>"Your attached file did not uload due to :- ".$_FILES['attachedfile']['error'], 'dataupdatewith'=>'');
	        echo json_encode($rrs);
	        die();
	    }
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
	$audiouploadFile="";
	if (isset($_FILES['audiofile']) && $_FILES['audiofile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename=basename($_FILES['audiofile']['name']);
        $filetype = $_FILES['audiofile']['type'];
        $audiouploadFileup = $uploadDir . $filename;
        $audiouploadFile=str_replace("../", "", $audiouploadFileup);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['audiofile']['tmp_name'], $audiouploadFileup);

	}

	$updatecategoriz = $_POST['updatecategoriz'];	
	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];
	$audiofile = $_POST['audiofile'];
	$voiceinstid = $_POST['voiceinstid'];
	$paymentduedate = $_POST['paymentduedate'];
	$ponumber = $_POST['ponumber'];
	$loginname = $_POST['loginname'];

	$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	$paytoname="";
	$payto_bankname="";
	$payto_accountno="";
	$payto_bankifsccode="";
	$is_intermediate_transfer="";
	$payto_accountid="";
	foreach ($ppt as $pptkey) {
		$paytoname= $pptkey['paytoname'];
		$payto_bankname=$pptkey['tobankname'];
		$payto_accountno=$pptkey['toaccountno'];
		$payto_bankifsccode=$pptkey['tobankifsc'];
		$is_intermediate_transfer="";
		$payto_accountid="";
	}
	$projectinfo = projectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
	foreach ($projectinfo as $projrow) {
		$projectname=$projrow['projectname'];
		$documentsavetype=$projrow['documenttype'];
	}
	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}

	$ttlbnfamount=0;
	$bnfar=[];
	$amar=[];
	$prpar=[];
	$dscar=[];
	$breakdowndata=[];
	
	if(count($invoicerowdata)>0){
		foreach ($invoicerowdata as $cvkey) {
			$breakdowndata[]=array('beneficiaryname' => $paytoname,
			'expenseamount' => floatval(($cvkey['item-amount'] + $cvkey['item-cgst-amt'] + $cvkey['item-sgst-amt'] + $cvkey['item-igst-amt'])),
			'invoiceno' => $invoiceno,
			'invoicedate' => $invoicedate,
			'invoicetype' => $invoicetype,
			'description' => $requestdescription.' : '.$cvkey['item-description'],
			'purposecode' => $cvkey['item-purposecode'],
			'isforstock' => $cvkey['item-isforstock'],
			'attachmentpath' => $filepath_forother,
			'audiofilepath'=>$audiouploadFile,
			'voiceinstid'=>$voiceinstid,
			'invoicegstamount' => floatval(($cvkey['item-cgst-amt'] + $cvkey['item-sgst-amt'] + $cvkey['item-igst-amt'])),
			'taxableamount' => $cvkey['item-txablamount'],
			'ponumber' => $ponumber,
			'docsno' => '',
			'srm_attsno' => '',
			'srm_emailsno' => '');
		}
	}
	$insertqury="";
	$rtnmsg=array();
	if(strtolower($requesttypename)=="pre-payment"){
		$rtnmsg=unapproved_saverequestdatafor_taxinvoice($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$paytoamount,$paytoid,$paytogstno,$documentsavetype,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename,$paymentduedate,$breakdowndata,$invoiceno,$invoicedate,$invoicerowdata,$isforproduction,$loginname);

	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid && $paytoamount && count($invoicerowdata)>0){
			$rtnmsg=recunstructbook_taxInvoice($authconn,$conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$paytoamount,$paytoid,$paytogstno,$breakdowndata,$invoiceno,$invoicedate,$invoicerowdata,$isforproduction,$loginname);
		}else{
			$rtnmsg[]=array('status'=>'Error', 'message'=>'Somthing wrong,Please try again');
		}
	}else {
		$rtnmsg[]=array('status'=>'Error', 'message'=>'Query data not available, Try again');
	}
	echo json_encode($rtnmsg);
}else if($action=="DigitizeVoiceInstructionintoBookEntry"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$fromaccountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$requestamount = $_POST['paytoamount'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$purposecode = $_POST['purposecode'];
	$purposename = $_POST['purposename'];
	$requestdescription = $_POST['description'];
	$voiceinstid = $_POST['voiceinstid'];
	$requesttypename = $_POST['requesttypename'];
	$loginname = $_POST['loginname'];
	$purposecategoryid ="";
	$purposesubcategoryid ="";
	$rrs=array();
	$getpurposestring=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	if(count($getpurposestring)>0){
        foreach ($getpurposestring as $keypc) {
			$purposecategory=$keypc['pcategory'];
			$purposecategoryid=$keypc['pcategorycode'];
			$purposesubcategory=$keypc['psub_category'];
			$purposesubcategoryid=$keypc['psub_categoryid'];
			$purposename = $keypc['purpose'];
			$purposecode = $keypc['purposecode'];
		}
    }
	
	if($purposecategoryid=="" || $purposecategoryid=="undefined" || $purposecategoryid==null){
		$rrs[]=array('status'=>'Error', 'message'=>"Purpose deails missing/wrong");
	        echo json_encode($rrs);
	        die();
	}
	
	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}
	
	$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	$beneficiaryname ="";
	foreach ($ppt as $pptkey) {
		$paytoid = $pptkey['paytoid'];
		$paytoname = $pptkey['paytoname'];
		$tobankname = $pptkey['tobankname'];
		$toaccountno = $pptkey['toaccountno'];
		$tobankifsc = $pptkey['tobankifsc'];
		$beneficiaryname = $paytoname;
	}

	if($paytoid=="" || $paytoid=="undefined" || $paytoid==null){
		$rrs[]=array('status'=>'Error', 'message'=>"Payto deails missing/wrong");
	        echo json_encode($rrs);
	        die();
	}

	if($voiceinstid!=""){
    	$voiceinfo=voiceinstructiondetails($authconn,$conn,$companyid,$subcompanyid,$projectid,"instructionid",$voiceinstid);
		$voicefilepath="";
		$voicedocumentpath="";
    	if(count($voiceinfo)>0){
    		foreach ($voiceinfo as $vvkey) {
    			$voicefilepath = $vvkey['filepath'];
    			$voicedocumentpath = $vvkey['filepath2'];
    		}
    	}
    }

	

	$invoicedata=array();
	$voucherinfo[]=array('beneficiaryname'=>$beneficiaryname,'expenseamount'=>$requestamount, 'invoicedate'=>'', 'invoiceno'=>'', 'vouchertype'=>'', 'invoicetype'=>'', 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'purposename'=>$purposename, 'attachmentpath'=>'', 'audiofilepath'=>$voicefilepath, 'voiceinstid'=>$voiceinstid, 'docsno'=>'','srm_attsno'=>'','srm_emailsno'=>'','invoicedata'=>$invoicedata,'totalrowsamount'=>0,'totalgstamount'=>0,'totalrowsdiscount'=>0,'otherdiscount'=>0,'totalpayableamount'=>$requestamount,'taxableamount'=>$requestamount,'invoicegstamount'=>'','ponumber'=>'');	
	$rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo,$loginname,$purposecategoryid,$purposesubcategoryid,$purposecode,'no',$requestdescription);
	$rrst=array();
	if(count($rrs)>0){
		foreach ($rrs as $rrskey) {
			if($rrskey['status']=="Success"){
				$requeststatus="Book Created with Reference No ".$rrskey['referenceno'];
				$conn->query("UPDATE tbl_paymentrequest_voiceinstruction SET payto='".$paytoid."',purpose='".$purposecode."',from_accountid='".$fromaccountid."',description='".$requestdescription."',requeststatus='".$requeststatus."',requestedby='".$loginname."',requestdate='".date('Y-m-d')."' WHERE instructionid='".$voiceinstid."'");
			}
		}
	}	
	echo json_encode($rrs);
}else if($action=="savetoodb_requestdataforall"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$fromaccountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$beneficiaryname = $_POST['beneficiaryname'];
	$requestamount = $_POST['paytoamount'];
	$taxableamount = $_POST['taxableamount'];
	$invoicegstamount = $_POST['invoicegstamount'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$paymentduedate = $_POST['paymentduedate'];
	// $purposecategoryid = $_POST['purposecategoryid'];
	// $purposesubcategoryid = $_POST['purposesubcategoryid'];
	$purposecode = $_POST['purposecode'];
	$purposetype = "";
	$purposename = $_POST['purposename'];
	$ponumber = $_POST['ponumber'];
	$purposecategoryid ="";
	$purposesubcategoryid ="";
	$rrs=array();
	$getpurposestring=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposecode",$purposecode);
	// print_r($getpurposestring);
	if(count($getpurposestring)>0){
        foreach ($getpurposestring as $keypc) {
			$purposecategoryid=$keypc['pcategorycode'];
			$purposesubcategoryid=$keypc['psub_categoryid'];
			$purposename = $keypc['purpose'];
			$purposecode = $keypc['purposecode'];
		}
    }
	
	if($purposecategoryid=="" || $purposecategoryid=="undefined" || $purposecategoryid==null){
		$rrs[]=array('status'=>'Error', 'message'=>"Purpose deails missing/wrong-");
	        echo json_encode($rrs);
	        die();
	}
	
	if(isset($_POST['isforproduction'])=="yes"){
		$isforproduction = $_POST['isforproduction'];
	}else{
		$isforproduction ="";
	}

	if(isset($_POST['bankdescription'])){
		if($_POST['bankdescription']!="" && $_POST['bankdescription']!="undefined"){
			$requestdescription = $_POST['description'].", Bank description is :- ".$_POST['bankdescription'];
		}else{
			$requestdescription = $_POST['description'];
		}
	}else{
		$requestdescription = $_POST['description'];
	}
	$loginname = $_POST['loginname'];
	$requesttypename = $_POST['requesttypename'];
	$updatecategoriz = $_POST['updatecategoriz'];	
	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];
	$audiofile = $_POST['audiofile'];
	$voiceinstid = $_POST['voiceinstid'];
	$invoicetype = $_POST['invoicetype'];
	$invoiceno = $_POST['invoiceno'];
	$invoicedate = $_POST['invoicedate'];

	$invoice_totalrowsamount = $_POST['totalrowsamount'];
	$invoice_totalgstamount = $_POST['totalgstamount'];
	$invoice_totalrowsdiscount = $_POST['totalrowsdiscount'];
	$invoice_otherdiscount = $_POST['otherdiscount'];
	$invoice_totalpayableamount = $_POST['totalpayableamount'];
	$invoicedata=json_decode($_POST['invoicedata'],TRUE);
	if(!$invoicedata){
		$invoicedata=array();
	}
	$filepath="";
	$filepath_fromemail="";
	
	$audiouploadFile="";
	if (isset($_FILES['audiofile']) && $_FILES['audiofile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename=basename($_FILES['audiofile']['name']);
        $filetype = $_FILES['audiofile']['type'];
        $audiouploadFileup = $uploadDir . $filename;
        $audiouploadFile=str_replace("../", "", $audiouploadFileup);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['audiofile']['tmp_name'], $audiouploadFileup);

	}	

	if ($_FILES['attachedfile']['name']) {
	    $distpath = "../../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/requestdocument/" . date('Ymd') . '/';
	    
	    // Check if directory exists and create if it doesn't, with recursive flag
	    if (!is_dir($distpath)) {
	        mkdir($distpath, 0755, true);       
	    }
	    
	    $temp = explode(".", $_FILES["attachedfile"]["name"]);
	    $extension = end($temp);
	    $newfilename = round(microtime(true)) . '_' . date('His') . '.' . $extension;
	    
	    $filepath = $distpath . $newfilename;
	    $filepath_forother =$filepath;	
	    // Move uploaded file and check if successful
	    if (move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $filepath)) {

	    } else {
	    	$filepath="";
	        $rrs[]=array('status'=>'Error', 'message'=>"Your attached file did not uload due to :- ".$_FILES['attachedfile']['error'], 'dataupdatewith'=>'');
	        echo json_encode($rrs);
	        die();
	    }
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
	
	$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	foreach ($ppt as $pptkey) {
		$paytoname = $pptkey['paytoname'];
	}
	// echo "\nfirst: ".$beneficiaryname;
	if($beneficiaryname==null || $beneficiaryname=="" || $beneficiaryname=="undefined"){
		$beneficiaryname = $paytoname;
	}

	
	$voucherinfo[]=array('beneficiaryname'=>$beneficiaryname,'expenseamount'=>$requestamount, 'voucherdate'=>$invoicedate, 'invoicedate'=>$invoicedate, 'voucherno'=>$invoiceno, 'invoiceno'=>$invoiceno, 'vouchertype'=>$invoicetype, 'invoicetype'=>$invoicetype, 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'purposename'=>$purposename, 'attachmentpath'=>$filepath_forother, 'audiofilepath'=>$audiouploadFile, 'voiceinstid'=>$voiceinstid, 'docsno'=>'','srm_attsno'=>$attsnofromemail,'srm_emailsno'=>$emailsnofromemail,'invoicedata'=>$invoicedata,'totalrowsamount'=>$invoice_totalrowsamount,'totalgstamount'=>$invoice_totalgstamount,'totalrowsdiscount'=>$invoice_totalrowsdiscount,'otherdiscount'=>$invoice_otherdiscount,'totalpayableamount'=>$invoice_totalpayableamount,'taxableamount'=>$taxableamount,'invoicegstamount'=>$invoicegstamount,'ponumber'=>$ponumber);
	// print_r($voucherinfo);
	// die();
	
	if(strtolower($requesttypename)=="pre-payment"){
		// $isforproduction
		$categoryofrequest ="";
		$rrs=unapproved_saverequestdataforall($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$paytoid,$modeoftransfer,$requestamount,$requestdescription,$purposecategoryid,$purposesubcategoryid,$purposecode,$purposename,$loginname,$filepath,$audiouploadFile,$voiceinstid,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename,$invoicedate,$invoiceno,$invoicetype,$invoicedata,$invoice_totalrowsamount,$invoice_totalgstamount,$invoice_totalrowsdiscount,$invoice_otherdiscount,$invoice_totalpayableamount,$beneficiaryname,$paymentduedate,$isforproduction,$taxableamount,$invoicegstamount,$ponumber);
		

	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid && $requestamount>0 && $transactionno && $transactiondate && $fromaccountid && $purposecode && $requestdescription){
			$rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo,$loginname,$purposecategoryid,$purposesubcategoryid,$purposecode,$isforproduction,$requestdescription);
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
					if(count($vkey['invoicedata'])>0){
						savetoinvoicedata($mconn,$companyid,$subcompanyid,$projectid,'T3-Source-Table',$newreference_no,$vkey['invoicedate'],$vkey['invoiceno'],$vkey['invoicedata'],$vkey['totalrowsamount'], $vkey['totalgstamount'], $vkey['totalrowsdiscount'], $vkey['otherdiscount'], $vkey['totalpayableamount']);
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
					$sourceby="app form on date ".date('Y-m-d h:i:s')." by ".$loginname;
					$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,subprojectid,date,chequeno,bankname,accountno,account_id,amount,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo,audiofilepath,voiceinstid,srm_attsno,srm_emailsno,source, is_purchaseforproduction) values('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$fromaccountid."','".$vkey['expenseamount']."','".$vkey['expenseamount']."','".addslashes($vkey['description'])."','".addslashes($paytoname)."','".addslashes($vkey['beneficiaryname'])."','".addslashes($categorycode)."','".addslashes($category)."','".addslashes($subcategorycode)."','".addslashes($subcategory)."','".addslashes($purpose)."','".addslashes($purposecode)."','".addslashes($loginname)."','".addslashes(str_replace("../", "", $vkey['attachmentpath']))."','".addslashes(str_replace("../", "", $vkey['audiofilepath']))."', '".$voiceinstid."', '".$vkey['srm_attsno']."', '".$vkey['srm_emailsno']."','".$sourceby."','".$isforproduction."');";
				}
				if($insertqury){
					if ($mconn->multi_query($insertqury) === TRUE) {
						if($voiceinstid){
							$conn->query("update tbl_paymentrequest_voiceinstruction set requestno='expensesheet', requeststatus='done', requestedby='".$loginname."', requestdate='".date('Y-m-d')."', uploadflag=null where instructionid='".$voiceinstid."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");

						}						
						$rrs[]=array('status'=>'Success', 'message'=>'Your receipt/voucher details save as expense successfully', 'dataupdatewith'=>'expencesheetonly', 'referenceno'=>$newreference_no);
						
						// , 'dataupdatewith'=>'maintransactiononly'
					}else{
						$rrs[]=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
					}
				}else{
					$rrs[]=array('status'=>'Error', 'message'=>'Query data not available, Try again 11', 'dataupdatewith'=>'');
				}				
			}
		}
	}else {
		$rrs[]=array('status'=>'Error', 'message'=>'Request type not defined');
	}
	echo json_encode($rrs);
}else if($action=="savetoodb_requestdatafor_itax"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$fromaccountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$beneficiaryname = $_POST['beneficiaryname'];
	$requestamount = $_POST['paytoamount'];
	$taxableamount = $_POST['taxableamount'];
	$invoicegstamount = $_POST['invoicegstamount'];
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$paymentduedate = $_POST['paymentduedate'];
	$purposecategoryid = $_POST['purposecategoryid'];
	$purposesubcategoryid = $_POST['purposesubcategoryid'];
	$purposecode = $_POST['purposecode'];
	$purposetype = "";
	$purposename = $_POST['purposename'];
	$ponumber = $_POST['ponumber'];
	
	if(isset($_POST['isforproduction'])=="yes"){
		$isforproduction = $_POST['isforproduction'];
	}else{
		$isforproduction ="";
	}

	if(isset($_POST['bankdescription'])){
		if($_POST['bankdescription']!="" && $_POST['bankdescription']!="undefined"){
			$requestdescription = $_POST['description'].", Bank description is :- ".$_POST['bankdescription'];
		}else{
			$requestdescription = $_POST['description'];
		}
	}else{
		$requestdescription = $_POST['description'];
	}
	$loginname = $_POST['loginname'];
	$requesttypename = $_POST['requesttypename'];
	$updatecategoriz = $_POST['updatecategoriz'];	
	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];
	$audiofile = $_POST['audiofile'];
	$voiceinstid = $_POST['voiceinstid'];
	$invoicetype = $_POST['invoicetype'];
	$invoiceno = $_POST['invoiceno'];
	$invoicedate = $_POST['invoicedate'];

	$invoice_totalrowsamount = $_POST['totalrowsamount'];
	$invoice_totalgstamount = $_POST['totalgstamount'];
	$invoice_totalrowsdiscount = $_POST['totalrowsdiscount'];
	$invoice_otherdiscount = $_POST['otherdiscount'];
	$invoice_totalpayableamount = $_POST['totalpayableamount'];
	$invoicedata=json_decode($_POST['invoicedata'],TRUE);
	if(!$invoicedata){
		$invoicedata=array();
	}
	$filepath="";
	$filepath_fromemail="";
	
	$audiouploadFile="";
	if (isset($_FILES['audiofile']) && $_FILES['audiofile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename=basename($_FILES['audiofile']['name']);
        $filetype = $_FILES['audiofile']['type'];
        $audiouploadFileup = $uploadDir . $filename;
        $audiouploadFile=str_replace("../", "", $audiouploadFileup);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['audiofile']['tmp_name'], $audiouploadFileup);

	}
	

	if ($_FILES['attachedfile']['name']) {
	    $distpath = "../../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/requestdocument/" . date('Ymd') . '/';
	    
	    // Check if directory exists and create if it doesn't, with recursive flag
	    if (!is_dir($distpath)) {
	        mkdir($distpath, 0755, true);       
	    }
	    
	    $temp = explode(".", $_FILES["attachedfile"]["name"]);
	    $extension = end($temp);
	    $newfilename = round(microtime(true)) . '_' . date('His') . '.' . $extension;
	    
	    $filepath = $distpath . $newfilename;
	    $filepath_forother =$filepath;	
	    // Move uploaded file and check if successful
	    if (move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $filepath)) {

	    } else {
	    	$filepath="";
	        $rrs[]=array('status'=>'Error', 'message'=>"Your attached file did not uload due to :- ".$_FILES['attachedfile']['error'], 'dataupdatewith'=>'');
	        echo json_encode($rrs);
	        die();
	    }
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
	
	$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$paytoid);
	foreach ($ppt as $pptkey) {
		$paytoname = $pptkey['paytoname'];
	}
	// echo "\nfirst: ".$beneficiaryname;
	if($beneficiaryname==null || $beneficiaryname=="" || $beneficiaryname=="undefined"){
		$beneficiaryname = $paytoname;
	}
	
	$voucherinfo[]=array('beneficiaryname'=>$beneficiaryname,'expenseamount'=>$requestamount, 'voucherdate'=>$invoicedate, 'invoicedate'=>$invoicedate, 'voucherno'=>$invoiceno, 'invoiceno'=>$invoiceno, 'vouchertype'=>$invoicetype, 'invoicetype'=>$invoicetype, 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'purposename'=>$purposename, 'attachmentpath'=>$filepath_forother, 'audiofilepath'=>$audiouploadFile, 'voiceinstid'=>$voiceinstid, 'docsno'=>'','srm_attsno'=>$attsnofromemail,'srm_emailsno'=>$emailsnofromemail,'invoicedata'=>$invoicedata,'totalrowsamount'=>$invoice_totalrowsamount,'totalgstamount'=>$invoice_totalgstamount,'totalrowsdiscount'=>$invoice_totalrowsdiscount,'otherdiscount'=>$invoice_otherdiscount,'totalpayableamount'=>$invoice_totalpayableamount,'taxableamount'=>$taxableamount,'invoicegstamount'=>$invoicegstamount,'ponumber'=>$ponumber);
	$rrs=array();
	if(strtolower($requesttypename)=="pre-payment"){
		// $isforproduction
		$categoryofrequest ="";
		$rrs=unapproved_saverequestdataforall($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$paytoid,$modeoftransfer,$requestamount,$requestdescription,$purposecategoryid,$purposesubcategoryid,$purposecode,$purposename,$loginname,$filepath,$audiouploadFile,$voiceinstid,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename,$invoicedate,$invoiceno,$invoicetype,$invoicedata,$invoice_totalrowsamount,$invoice_totalgstamount,$invoice_totalrowsdiscount,$invoice_otherdiscount,$invoice_totalpayableamount,$beneficiaryname,$paymentduedate,$isforproduction,$taxableamount,$invoicegstamount,$ponumber);
	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid && $requestamount>0 && $transactionno && $transactiondate && $fromaccountid && $purposecode && $requestdescription){
			$rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo,$loginname,$purposecategoryid,$purposesubcategoryid,$purposecode,$isforproduction,$requestdescription);
		}else{
			$rrs[]=array('status'=>'Error', 'message'=>'Some required information is missing');
			/*if(count($voucherinfo)>0){
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
					if(count($vkey['invoicedata'])>0){
						savetoinvoicedata($mconn,$companyid,$subcompanyid,$projectid,'T3-Source-Table',$newreference_no,$vkey['invoicedate'],$vkey['invoiceno'],$vkey['invoicedata'],$vkey['totalrowsamount'], $vkey['totalgstamount'], $vkey['totalrowsdiscount'], $vkey['otherdiscount'], $vkey['totalpayableamount']);
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
					$sourceby="app form on date ".date('Y-m-d h:i:s')." by ".$loginname;
					$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,subprojectid,date,chequeno,bankname,accountno,account_id,amount,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo,audiofilepath,voiceinstid,srm_attsno,srm_emailsno,source, is_purchaseforproduction) values('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$fromaccountid."','".$vkey['expenseamount']."','".$vkey['expenseamount']."','".addslashes($vkey['description'])."','".addslashes($paytoname)."','".addslashes($vkey['beneficiaryname'])."','".addslashes($categorycode)."','".addslashes($category)."','".addslashes($subcategorycode)."','".addslashes($subcategory)."','".addslashes($purpose)."','".addslashes($purposecode)."','".addslashes($loginname)."','".addslashes(str_replace("../", "", $vkey['attachmentpath']))."','".addslashes(str_replace("../", "", $vkey['audiofilepath']))."', '".$voiceinstid."', '".$vkey['srm_attsno']."', '".$vkey['srm_emailsno']."','".$sourceby."','".$isforproduction."');";
				}
				if($insertqury){
					if ($mconn->multi_query($insertqury) === TRUE) {
						if($voiceinstid){
							$conn->query("update tbl_paymentrequest_voiceinstruction set requestno='expensesheet', requeststatus='done', requestedby='".$loginname."', requestdate='".date('Y-m-d')."', uploadflag=null where instructionid='".$voiceinstid."' and companyid='".$companyid."' and subcompanyid='".$subcompanyid."' and projectid='".$projectid."' and (tedit is null or tedit='')");

						}						
						$rrs[]=array('status'=>'Success', 'message'=>'Your receipt/voucher details save as expense successfully', 'dataupdatewith'=>'expencesheetonly', 'referenceno'=>$newreference_no);
						
						// , 'dataupdatewith'=>'maintransactiononly'
					}else{
						$rrs[]=array('status'=>'Error', 'message'=>'Your receipt/voucher details did not save, due to technical issue, Try again');
					}
				}else{
					$rrs[]=array('status'=>'Error', 'message'=>'Query data not available, Try again 11', 'dataupdatewith'=>'');
				}				
			}*/
		}
	}else {
		$rrs[]=array('status'=>'Error', 'message'=>'Request type not defined');
	}
	echo json_encode($rrs);
}else if($action=="markedItaxRequestCreated"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$requestno = $_POST['requestno'];
	$itaxsnoarray = json_decode($_POST['itaxsnoarray'], true);
	if (count($itaxsnoarray)>0 && !empty($requestno)) {
	    $successCount = 0;
	    $errorCount = 0;
	    foreach ($itaxsnoarray as $iitxkey) {
	        $stmt = $conn->prepare("UPDATE itax_account_summery SET itaxstatus = ? WHERE sno = ?");
	        if ($stmt) {
	            $stmt->bind_param("si", $requestno, $iitxkey);
	            if ($stmt->execute()) {
	                $successCount++;
	            } else {
	                $errorCount++;
	            }
	            $stmt->close();
	        } else {
	            $errorCount++;
	        }
	    }
	    if ($successCount > 0 && $errorCount == 0) {
	        $response = array('status' => 'success', 'message' => "$successCount record(s) updated successfully.");
	    } elseif ($successCount > 0 && $errorCount > 0) {
	        $response = array('status' => 'partial_success', 'message' => "$successCount record(s) updated, but $errorCount failed.");
	    } else {
	        $response = array('status' => 'error', 'message' => "Failed to update records.");
	    }
	} else {
	    $response = array('status' => 'error', 'message' => "Invalid input: request number or records missing.");
	}
	// Return response
	echo json_encode($response);
}else if($action=="savetoodb_requestdataforsalary"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subprojectid = $_POST['subprojectid'];
	$fromaccountid = $_POST['accountid'];
	$paytoid = $_POST['paytoid'];
	$requestamount = $_POST['paytoamount'];
	$taxableamount = $_POST['paytoamount'];
	$invoicegstamount = 0;
	$modeoftransfer = $_POST['modeoftransfer'];
	$transactionno = $_POST['transactionno'];
	$transactiondate = $_POST['transactiondate'];
	$purposecategoryid = $_POST['purposecategoryid'];
	$purposesubcategoryid = $_POST['purposesubcategoryid'];
	$purposecode = $_POST['purposecode'];
	$purposename = $_POST['purposename'];
	$paymentduedate = $_POST['paymentduedate'];
	$isforproduction="";

	$salaryyear = $_POST['salaryyear'];
	$salarymonth = $_POST['salarymonth'];
	$salarydays = $_POST['salarydays'];
	$requestdescription = $_POST['description'];
	// $bankdescription = $_POST['bankdescription'];
	if(isset($_POST['bankdescription'])){
		if($_POST['bankdescription']!=""){
			$requestdescription = $_POST['description'].", Bank description is :- ".$_POST['bankdescription'];
		}
	}else{
		$requestdescription = $_POST['description'];
	}
	$loginname = $_POST['loginname'];
	$requesttypename = $_POST['requesttypename'];

	$attpathfromemail = $_POST['attpathfromemail'];
	$attsnofromemail = $_POST['attsnofromemail'];
	$emailsnofromemail = $_POST['emailsnofromemail'];
	$updatecategoriz = $_POST['updatecategoriz'];

	$audiofile = $_POST['audiofile'];
	$voiceinstid = $_POST['voiceinstid'];
	$invoicedata=array();

	//paytodetails
	
	$empinfo=employeemaster($conn,$companyid,$subcompanyid,$projectid,"employeeid",$paytoid);
	if(count($empinfo)>0){
		foreach ($empinfo as $empkey) {
			$paytoname=$empkey['empname'];
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
			$payto_bankname=$pptkey['tobankname'];
			$payto_accountno=$pptkey['toaccountno'];
			$payto_bankifsccode=$pptkey['tobankifsc'];
			$is_intermediate_transfer="";
			$payto_accountid="";
		}
	}


	// $attachedfile = $_FILES['attachedfile']['name'];
	$filepath="";
	$distpath="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".date('Ymd').'/';
	if($_FILES['attachedfile']['name']){
		if ( !is_dir( $distpath ) ) {
		    mkdir($distpath, 0755);       
		}
		$temp = explode(".", $_FILES["attachedfile"]["name"]);
		$extension=end($temp);
		$newfilename = round(microtime(true)).'_'.date('His'). '.' . end($temp);
        move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $distpath . $newfilename);
        $filepath = $distpath . $newfilename;
        // $filepath = str_replace("../", "", $filepath);
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

	$filepath_paymentreceipt="";
	if($_FILES['paymentreceipt']['name']){
		if ( !is_dir( $distpath ) ) {
		    mkdir($distpath, 0755);       
		}
		$temp = explode(".", $_FILES["paymentreceipt"]["name"]);
		$extension=end($temp);
		$newfilename = round(microtime(true)).'_'.date('His'). '.' . end($temp);
        move_uploaded_file($_FILES["paymentreceipt"]["tmp_name"], $distpath . $newfilename);
        $filepath = $distpath . $newfilename;
        // $filepath = str_replace("../", "", $filepath);
        $filepath_paymentreceipt = $distpath . $newfilename;
	}

	$audiouploadFile="";
	if (isset($_FILES['audiofile']) && $_FILES['audiofile']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/voicerecoding/".date('Ymd')."/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename=basename($_FILES['audiofile']['name']);
        $filetype = $_FILES['audiofile']['type'];
        $audiouploadFileup = $uploadDir . $filename;
        $audiouploadFile=str_replace("../", "", $audiouploadFileup);
        // Move the uploaded file to the specified directory
        move_uploaded_file($_FILES['audiofile']['tmp_name'], $audiouploadFileup);

	}

	$cci=getcompanydetails($conn,"companyid",$companyid);
	$countryid="";
	foreach ($cci as $cckey) {
		$countryid=$cckey['countryid'];
	}

	$voucherinfo[]=array('beneficiaryname'=>$paytoname,'expenseamount'=>$requestamount, 'voucherdate'=>'', 'invoicedate'=>'', 'voucherno'=>'', 'invoiceno'=>'', 'vouchertype'=>'', 'invoicetype'=>'', 'description'=>$requestdescription, 'purposecode'=>$purposecode, 'purposename'=>$purposename, 'attachmentpath'=>$filepath_forother, 'audiofilepath'=>$audiouploadFile, 'voiceinstid'=>$voiceinstid, 'docsno'=>'','srm_attsno'=>$attsnofromemail,'srm_emailsno'=>$emailsnofromemail, 'invoicedata'=>$invoicedata,'totalrowsamount'=>'','totalgstamount'=>'','totalrowsdiscount'=>'','otherdiscount'=>'','totalpayableamount'=>'','taxableamount'=>$taxableamount,'invoicegstamount'=>$invoicegstamount);

	$rrs=array();
	if(strtolower($requesttypename)=="pre-payment"){
		$rrs=unapproved_saverequestdataforsalary($conn,$countryid,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$purposetype,$paytoid,$modeoftransfer,$requestamount,$requestdescription,$purposecategoryid,$purposesubcategoryid,$purposecode,$salaryyear,$salarymonth,$salarydays,$loginname,$filepath,$audiouploadFile,$voiceinstid,$filepath_fromemail,$attsnofromemail,$emailsnofromemail,$requesttypename,$paymentduedate,$taxableamount,$invoicegstamount);
	}else if(strtolower($requesttypename)=="post-payment"){
		if($paytoid && $requestamount>0 && $transactionno && $transactiondate && $fromaccountid && $purposecode && $requestdescription){
			$rrs=recunstructbook($authconn,$conn,$companyid,$subcompanyid,$projectid,$subprojectid,$fromaccountid,$modeoftransfer,$transactionno,$transactiondate,$requestamount,$paytoid,$voucherinfo,$loginname,$purposecategoryid,$purposesubcategoryid,$purposecode,$isforproduction,$requestdescription);
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
					$sourceby="app form on date ".date('Y-m-d h:i:s')." by ".$loginname;
					$insertqury.="Insert into tbl_expencesinexcelsheet(company_id,subcompany_id,project_id,subprojectid,date,chequeno,bankname,accountno,account_id,total_amount,description,payto,beneficiaryname,categoryid,category,sub_categoryid,sub_category,purpose,purpose_code,loginid,fileinfo,audiofilepath,voiceinstid,srm_attsno,srm_emailsno,source,is_purchaseforproduction) values('".$companyid."','".$subcompanyid."','".$projectid."','".$subprojectid."','".$transactiondate."','".$transactionno."','".$bankname."','".$accountno."','".$fromaccountid."','".$vkey['expenseamount']."','".addslashes($vkey['description'])."','".addslashes($paytoname)."','".addslashes($vkey['beneficiaryname'])."','".$categorycode."','".addslashes($category)."','".$subcategorycode."','".addslashes($subcategory)."','".addslashes($purpose)."','".$purposecode."','".addslashes($loginname)."','".str_replace("../", "", $vkey['attachmentpath'])."','".str_replace("../", "", $vkey['audiofilepath'])."', '".$voiceinstid."', '".$vkey['srm_attsno']."', '".$vkey['srm_emailsno']."','".$sourceby."','".$isforproduction."');";

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
}else if($action=="imprestpaytolist_todropdown"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$loginid = $_POST['loginid'];
	$loginroleid = $_POST['loginroleid'];
	if($loginroleid<4){
		$imptlist=getimprestpayto($mconn,$companyid,$subcompanyid,$projectid,"all","");
	}else{
		$imptlist=getimprestpayto($mconn,$companyid,$subcompanyid,$projectid,"loginid",$loginid);

	}
	echo json_encode($imptlist);
}else if($action=="imprestpaytowithloginid"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginid = $_POST['loginid'];
	$imptlist=getimprestpayto($mconn,$companyid,$subcompanyid,$projectid,"loginid",$loginid);	
	echo json_encode($imptlist);
}else if($action=="creditcardholderlist"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$loginid = $_POST['loginid'];
	$loginroleid = $_POST['loginroleid'];
	if($loginroleid>3){
		$crdlist=authorizedcreditcardperson($mconn,$companyid,$subcompanyid,$projectid,"userloginid",$loginid);
	}else{
		$crdlist=authorizedcreditcardperson($conn,$companyid,$subcompanyid,$projectid,"all","");

	}
	echo json_encode($crdlist);
}else if($action=="creditcarddetial"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$loginid = $_POST['loginid'];
	$paytoid = $_POST['paytoid'];
	// $crddst=creditcardauthorizedproject($mconn,$companyid,$subcompanyid,$projectid,$paytoid,"paytoid",$paytoid);
	$crddst=creditcardmaster($mconn,$companyid,$subcompanyid,$projectid,"ccid",$paytoid);	
	// print_r($crddst);
	echo json_encode($crddst);
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
}else if($action=="upldotherthan_salaryforbookreconstruct"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$distpath="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/otherthansalarysheetuploaded/".date('Ymd').'/';
	$rrst=array();
	if($_FILES['otherthansalarysheet']['name']){
		$updsheetdata=uploadsalarysheet_functions($conn,$companyid,$subcompanyid,$projectid,"uploadtype","otherthansalarysheet");
		// print_r($updsheetdata);
		// die();
		if(count($updsheetdata)>0){
			$rwcheck=0;
			$htillcol=0;
			$xx=0;
			$insqq="INSERT IGNORE INTO tbl_import_excelsheetdata(companyid, subcompanyid, projectid, paytoid, paytoname, bankname, accountno, ifsccode, transfersalary, remark, purpose, purposecode, upload_datatype) VALUES";

			for($a=0; $a<count($updsheetdata); $a++) {
				$rwcheck=$updsheetdata[$a]['hrow'];
				$htillcol=$updsheetdata[$a]['htillcol'];
				$fuction_name=$updsheetdata[$a]['function_name'];	

				if ( !is_dir( $distpath ) ) {
				    mkdir($distpath, 0755, true);       
				}
		        $temp = explode(".", $_FILES["otherthansalarysheet"]["name"]);
				$extension=end($temp);
				$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
		        move_uploaded_file($_FILES["otherthansalarysheet"]["tmp_name"], $distpath . $newfilename);
		        $filepath = $distpath . $newfilename;
		        // $filepath = str_replace("../", "", $filepath);
				$file_ext = pathinfo($_FILES["otherthansalarysheet"]["tmp_name"],PATHINFO_EXTENSION);
				
				if ( $xlsx = $file_ext=="xlsx"?SimpleXLSX::parse($filepath):SimpleXLS::parse($filepath))
				{
					$exceldata = $xlsx->rows();
					// print_r($exceldata);
					$insqv="";
					$updq=0;
					for($b=($rwcheck-1); $b<count($exceldata); $b++){
						if($b==($rwcheck-1)){
							$z=0;
							for($j=1; $j<=$htillcol; $j++){
								// echo "\n eq: ".$updsheetdata[$a]['key'.$j].'=='.$exceldata[$b][$j];
								if(strtolower(trim($updsheetdata[$a]['key'.$j]))!=strtolower(trim($exceldata[$b][$j]))){
									$z=1;
									break;
								}
							}
							if($z==1){
								$xx=1;
								break;
							}
						}else
						{
							if(trim($updsheetdata[$a]['function_name'])=="otherthansalaryfunction"){
								$paytoname="";
								$monthofsalary="";
								$designationname="";
								$actualsalary="";
								$salarydays="";
								$monthlysalary="";
								$bankname="";
								$accountno="";
								$ifsccode="";
								$advancededuction="";
								$transferamount="";
								$remark="";
								for($j=1; $j<=$htillcol; $j++){
									if($j==1){
										$paytoname=trim($exceldata[$b][$j]);
									}else if($j==2){
										$bankname=trim($exceldata[$b][$j]);
									}else if($j==3){
										$accountno=trim($exceldata[$b][$j]);
									}else if($j==4){
										$ifsccode=trim($exceldata[$b][$j]);
									}else if($j==5){
										$transferamount=trim($exceldata[$b][$j]);
									}else if($j==6){
										$providedpurposecode=trim($exceldata[$b][$j]);
									}else if($j==7){
										$providedpurposetext=trim($exceldata[$b][$j]);
									}else if($j==8){
										$remark=trim($exceldata[$b][$j]);
									}
								}
							}else{
								$rrst[]=array('status'=>'Error', 'message'=>'The Excel sheet format is not defined for this project. Please contact the administrator.');
								echo json_encode($rrst);
								die();
							}
							if($paytoname!="" && $transferamount!=""){
								$purposecode="";
								$purpose="";
								$paytoid="";
								$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoname",$paytoname);
								if(count($ppt)>0){
									foreach ($ppt as $pptkey) {
										$paytoid = $pptkey['paytoid'];
										// $bankname=$pptkey['tobankname'];
										// $accountno=$pptkey['toaccountno'];
										// $ifsccode=$pptkey['tobankifsc'];
									}
								}else{
									$npyto=savetodbnewpayto_details($conn,$paytoname,$accountno,$bankname,$ifsccode,"","","",$companyid,$subcompanyid,$projectid,'','',$loginname,"");
									if(count($npyto)>0){
										foreach ($npyto as $nptokey) {
											$paytoid = $nptokey['paytoid'];
										}
									}

								}
								// $ppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"provided_purposecode",$providedpurposecode);
								// if(count($ppd)>0){
								// 	foreach ($ppd as $ppdkey) {
								// 		$purpose=$ppdkey['purpose'];
								// 		$purposecode=$ppdkey['purposecode'];
								// 	}
								// }else{
								// 	$ppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposename",$providedpurposetext);
								// 	if(count($ppd)>1){
								// 		foreach ($ppd as $ppdkey) {
								// 			$purpose=$ppdkey['purpose'];
								// 			$purposecode=$ppdkey['purposecode'];
								// 		}
								// 	}	
								// }
								// echo "==".$providedpurposetext;
								$ppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposename",$providedpurposetext);
								// print_r($ppd);
								if(count($ppd)>0){
									foreach ($ppd as $ppdkey) {
										$purpose=$ppdkey['purpose'];
										$purposecode=$ppdkey['purposecode'];
									}
								}									
									
								// if(strtolower(trim($providedpurposetext))!==strtolower(trim($purpose))){
								// 	$purpose="";
								// }
								// echo "<br>----".$paytoid."===".$purposecode;
								// die();
								if($paytoid!="" && $purposecode!=""){
									$insqv.="('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".addslashes(trim($paytoname))."','".addslashes(trim($bankname))."','".addslashes(trim($accountno))."','".addslashes(trim($ifsccode))."','".addslashes(trim($transferamount))."','".addslashes(trim($remark))."','".addslashes(trim($purpose))."','".addslashes(trim($purposecode))."','otherexpense'),";
								}else{
									$updq++;
								}
							}
						}
					}
					// echo "xx: ".$xx;
					if($xx==1){
						$rrst[]=array('status'=>'wrongfileformat', 'message'=>'The Excel sheet format is not defined for this project. Please contact the administrator.');
						break;
					}
					// echo "sss: ".$insqv;
					// die();
					if($insqv){
						$insqv = rtrim($insqv, ','); // Remove last comma
						$conn->query($insqq.$insqv) or die($conn->error);
						if(mysqli_affected_rows($conn)>0){
							$rrst[]=array('status'=>'Success', 'message'=>'Excel Sheet Data update successfully '.mysqli_info($conn));
						}else if(mysqli_affected_rows($conn)==0 && mysqli_warning_count($conn)>0){
							$rrst[]=array('status'=>'Error', 'message'=>'All entries are duplicate');
						}else{
							$rrst[]=array('status'=>'Error', 'message'=>'Somthing wrong, try again');
						}
					}else{
						$rrst[]=array('status'=>'Error', 'message'=>'Payto name and purpose details is not available, try again after correction of purpose name or payto name');
					}
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>"xlsx"?SimpleXLS::parseError():SimpleXLSX::parseError());
				}
			}
		}else{
			$rrst[]=array('status'=>'missingfunction', 'message'=>'The Excel sheet format is not defined for this project. Please contact the administrator.');
		}
	}
	else 
	{
		$rrst[]=array('status'=>'Error', 'message'=>'Uploaded sheet is not available.');
	}
	echo json_encode($rrst);	
}else if($action=="getuploadexceldata_otherthansalary"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$pddata=uploadedexcelsheet_datafromsqltable($conn,$companyid,$subcompanyid,$projectid,"readyforbookother","");
	$pdsllist="";
	// print_r($pddata);
	if(count($pddata)>0){
		$psno=1;
		foreach ($pddata as $ppdkey) {
			$pppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"clientsubcategoryname_notlikely","salar");
			$ppplist="";
			$ppwarningtext="";
			$ppplist.="<select class='slarypurpose form-control select2' style='width:100%;'>";
			if(count($pppd)>0){
				if(count($pppd)>1){
					$ppplist.="<option value='' selected disabled>Select Purpose</option>";
				}
				$isprp=0;
				foreach ($pppd as $pppdkey) {
					if($ppdkey['purposecode']==$pppdkey['purposecode']){
						$ppplist.="<option value='".$pppdkey['purposecode']."' selected>".$pppdkey['purpose']."</option>";
						$isprp=1;
					}else{
						$ppplist.="<option value='".$pppdkey['purposecode']."'>".$pppdkey['purpose']."</option>";
					}
				}
				if($isprp==0){
					$ppwarningtext="<span style='font-size:10px; color:red;'>It is not available in other than salary purpose list. It may be in a salary/other category.</span>";
				}
			}
			$ppplist.="</select>";

			$pdsllist.="<tr>
				<td class='dbsno_sl'>".$psno++." <i class='fa fa-trash' <i class='fa fa-trash' onclick=\"remove_exceldataentry('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginname','".$ppdkey['sno']."','removedotherthansalarydata');\" style='font-size:18px; margin-left:8px; color:red; cursor:pointer;' ></i> <input type='hidden' class='sdbsno' value='".$ppdkey['sno']."'></td>
				<td class='paytoname_sl'>".$ppdkey['paytoname']."</td>
				<td class='bankname_sl' style='display:none;'>".$ppdkey['bankname']."</td>
				<td class='accountno_sl' style='display:none;'>".$ppdkey['accountno']."</td>
				<td class='ifsccode_sl' style='display:none;'>".$ppdkey['ifsccode']."</td>
				<td class='transfersalary_sl'>".$ppdkey['transfersalary']."</td>
				<td class='providepurpose_sl'>".$ppdkey['purpose']."<br/>".$ppwarningtext."</td>
				<td class='udatatype_sl' style='display:none;'>".$ppdkey['uploaddatatype']."</td>
				<td class='purpose_sl'>".$ppplist."</td>
			</tr>";
		}
	}
	echo $pdsllist;
}else if($action=="upldsalaryforbookreconstruct"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	// $salarysheet = $_FILES['uploadsalarysheet']['name'];
	$distpath="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/salarysheetuploaded/".date('Ymd').'/';
	$rrst=array();
	if($_FILES['uploadsalarysheet']['name']){
		$updsheetdata=uploadsalarysheet_functions($conn,$companyid,$subcompanyid,$projectid,"salarysheet","");
		// print_r($updsheetdata);
		if(count($updsheetdata)>0){
			$rwcheck=0;
			$htillcol=0;
			// print_r($updsheetdata);
			$xx=0;
			$insqq="INSERT IGNORE INTO tbl_import_excelsheetdata(companyid, subcompanyid, projectid, paytoid, paytoname, monthofsalary, designation, actualsalary, salarydays, monthlysalary, bankname, accountno, ifsccode, advancededuction, transfersalary, remark, purpose, purposecode, upload_datatype) VALUES";

			for($a=0; $a<count($updsheetdata); $a++) {
				$rwcheck=$updsheetdata[$a]['hrow'];
				$htillcol=$updsheetdata[$a]['htillcol'];
				$fuction_name=$updsheetdata[$a]['function_name'];	

				if ( !is_dir( $distpath ) ) {
				    mkdir($distpath, 0755, true);       
				}
		        $temp = explode(".", $_FILES["uploadsalarysheet"]["name"]);
				$extension=end($temp);
				$newfilename = round(microtime(true)).'_'.date('His').'.' . end($temp);
		        move_uploaded_file($_FILES["uploadsalarysheet"]["tmp_name"], $distpath . $newfilename);
		        $filepath = $distpath . $newfilename;
		        // $filepath = str_replace("../", "", $filepath);
				$file_ext = pathinfo($_FILES["uploadsalarysheet"]["tmp_name"],PATHINFO_EXTENSION);
				// if ( $xlsx = $file_ext=="xlsx"?SimpleXLSX::parse($_FILES["uploadsalarysheet"]["tmp_name"] ):SimpleXLS::parse($_FILES["uploadsalarysheet"]["tmp_name"] ) )
				if ( $xlsx = $file_ext=="xlsx"?SimpleXLSX::parse($filepath):SimpleXLS::parse($filepath))
				{
					$exceldata = $xlsx->rows();
					// print_r($exceldata);
					$insqv="";
					for($b=($rwcheck-1); $b<count($exceldata); $b++){
						if($b==($rwcheck-1)){
							$z=0;
							for($j=1; $j<=$htillcol; $j++){
								// echo "eq: ".$updsheetdata[$a]['key'.$j].'=='.$exceldata[$b][$j];
								if(strtolower(trim($updsheetdata[$a]['key'.$j]))!=strtolower(trim($exceldata[$b][$j]))){
									$z=1;
									break;
								}
							}
							if($z==1){
								$xx=1;
								break;
							}
						}else{
							$paytoname="";
							$monthofsalary="";
							$designationname="";
							$actualsalary="";
							$salarydays="";
							$monthlysalary="";
							$bankname="";
							$accountno="";
							$ifsccode="";
							$advancededuction="";
							$transfersalary="";
							$remark="";
							for($j=1; $j<=$htillcol; $j++){
								if($j==1){
									$paytoname=trim($exceldata[$b][$j]);
								}else if($j==2){
									$monthofsalary=trim($exceldata[$b][$j]);
								}else if($j==3){
									$designationname=trim($exceldata[$b][$j]);
								}else if($j==4){
									$actualsalary=trim($exceldata[$b][$j]);
								}else if($j==5){
									$salarydays=trim($exceldata[$b][$j]);
								}else if($j==6){
									$monthlysalary=trim($exceldata[$b][$j]);
								}else if($j==7){
									$bankname=trim($exceldata[$b][$j]);
								}else if($j==8){
									$accountno=trim($exceldata[$b][$j]);
								}else if($j==9){
									$ifsccode=trim($exceldata[$b][$j]);
								}else if($j==10){
									$advancededuction=trim($exceldata[$b][$j]);
								}else if($j==11){
									$transfersalary=trim($exceldata[$b][$j]);
								}else if($j==12){
									$purposetxt=trim($exceldata[$b][$j]);
								}else if($j==13){
									$remark=trim($exceldata[$b][$j]);
								}
							}
						}
						if($updsheetdata[$a]['function_name']=="salaryfunction"){
							if($paytoname!="" && $transfersalary!=""){
								$eemp=newemployeefromsalarysheet($conn,$companyid,$subcompanyid,$projectid,$paytoname,$accountno,$bankname,$ifsccode,$monthlysalary,$designationname,$loginname="");
								$purposecode="";
								$purpose="";
								$paytoid="";
								if(count($eemp)==1){
									foreach ($eemp as $eekey) {
										$purposecode= $eekey['purposecode'];
										$purpose= $eekey['purpose'];
										$paytoid= $eekey['empid'];
									}
									if(!$purpose){
										$ppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposename",$purposetxt);
										if(count($ppd)>0){
											foreach ($ppd as $ppdkey) {
												$purpose=$ppdkey['purpose'];
												$purposecode=$ppdkey['purposecode'];
											}
										}
									}

								}
								else{
									$ppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"purposename",$purposetxt);
									if(count($ppd)>0){
										foreach ($ppd as $ppdkey) {
											$purpose=$ppdkey['purpose'];
											$purposecode=$ppdkey['purposecode'];
										}
									}
								}
								if(strtolower(trim($purposetxt))!==strtolower(trim($purpose))){
									$purpose=$purposetxt;
									$purposecode="";
								}
								
								$insqv.="('".$companyid."','".$subcompanyid."','".$projectid."','".$paytoid."','".addslashes(trim($paytoname))."','".addslashes(trim($monthofsalary))."','".addslashes(trim($designationname))."','".addslashes(trim($actualsalary))."','".addslashes(trim($salarydays))."','".addslashes(trim($monthlysalary))."','".addslashes(trim($bankname))."','".addslashes(trim($accountno))."','".addslashes(trim($ifsccode))."','".addslashes(trim($advancededuction))."','".addslashes(trim($transfersalary))."','".addslashes(trim($remark))."','".addslashes(trim($purpose))."','".addslashes(trim($purposecode))."','salaryexpense'),";
							}
						}
					}
					// echo "xx: ".$xx;
					if($xx==1){
						$rrst[]=array('status'=>'wrongfileformat', 'message'=>'File format not correct');
						break;
					}
					if($insqv){
						$insqv = rtrim($insqv, ','); // Remove last comma
						// echo $insqq.$insqv;
						// die();
						$conn->query($insqq.$insqv) or die($conn->error);
						if(mysqli_affected_rows($conn)>0){
							$rrst[]=array('status'=>'Success', 'message'=>'Salary Sheet Data update successfully '.mysqli_info($conn));
						}else if(mysqli_affected_rows($conn)==0 && mysqli_warning_count($conn)>0){
							$rrst[]=array('status'=>'Error', 'message'=>'All entries are duplicate');
						}else{
							$rrst[]=array('status'=>'Error', 'message'=>'Sql query failed to execute, try again');
						}
					}else{
						$rrst[]=array('status'=>'Error', 'message'=>'Sql query failed not available, try again');
					}
				}else{
					$rrst[]=array('status'=>'Error', 'message'=>"xlsx"?SimpleXLS::parseError():SimpleXLSX::parseError());
				}
			}
		}else{
			$rrst[]=array('status'=>'missingfunction', 'message'=>'The salary Excel sheet format is not defined for this project. Please contact the administrator.');
		}
	}
	else 
	{
		$rrst[]=array('status'=>'Error', 'message'=>'uploaded sheet is not available.');
	}
	echo json_encode($rrst);	
}else if($action=="getsalarydataforreconstructbook"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$pddata=uploadedexcelsheet_datafromsqltable($conn,$companyid,$subcompanyid,$projectid,"readyforbooksalary","");
	$pdsllist="";
	// print_r($pddata);
	if(count($pddata)>0){
		$psno=1;
		foreach ($pddata as $ppdkey) {
			$ppplist="";
			$ppwarningtext="";
			$pppd=getpurposenamewithcat($conn,$companyid,$subcompanyid,$projectid,"clientcategorynamelikely","salar");
			$ppplist.="<select class='slarypurpose form-control select2' style='width:100%;'>";
			if(count($pppd)>0){
				if(count($pppd)>1){
					$ppplist.="<option value='' selected disabled>Select Purpose</option>";
				}
				$isprp=0;
				foreach ($pppd as $pppdkey) {
					if($ppdkey['purposecode']==$pppdkey['purposecode']){
						$ppplist.="<option value='".$pppdkey['purposecode']."' selected>".$pppdkey['purpose']."</option>";
						$isprp=1;
					}else{
						$ppplist.="<option value='".$pppdkey['purposecode']."'>".$pppdkey['purpose']."</option>";
					}
				}
				if($isprp==0){
					$ppwarningtext="<span style='font-size:10px; color:red;'>It is not available in the salary purpose list. It may be in a different category.</span>";

				}
			}
			$ppplist.="</select>";

			$pdsllist.="<tr>
				<td class='dbsno_sl'>".$psno++." <i class='fa fa-trash' onclick=\"remove_exceldataentry('hidden_baseurl','hidden_companyid','hidden_subcompanyid','hidden_projectid','hidden_loginname','".$ppdkey['sno']."','removedsalarydata');\" style='font-size:18px; margin-left:8px; color:red; cursor:pointer;' ></i>  <input type='hidden' class='sdbsno' value='".$ppdkey['sno']."'></td>
				<td class='paytoname_sl'>".$ppdkey['paytoname']."</td>
				<td class='monthofsalary_sl'>".date('M-Y', strtotime($ppdkey['monthofsalary']))."</td>
				<td class='designation_sl'>".$ppdkey['designation']."</td>
				<td class='actualsalary_sl'>".$ppdkey['actualsalary']."</td>
				<td class='salarydays_sl'>".$ppdkey['salarydays']."</td>
				<td class='monthlysalary_sl' style='display:none;'>".$ppdkey['monthlysalary']."</td>
				<td class='bankname_sl' style='display:none;'>".$ppdkey['bankname']."</td>
				<td class='accountno_sl' style='display:none;'>".$ppdkey['accountno']."</td>
				<td class='ifsccode_sl' style='display:none;'>".$ppdkey['ifsccode']."</td>
				<td class='advancededuction_sl' style='display:none;'>".$ppdkey['advancededuction']."</td>
				<td class='transfersalary_sl'>".$ppdkey['transfersalary']."</td>
				<td class='providepurpose_sl'>".$ppdkey['purpose']."<br/>".$ppwarningtext."</td>
				<td class='udatatype_sl' style='display:none;'>".$ppdkey['uploaddatatype']."</td>
				<td class='purpose_sl'>".$ppplist."</td>
			</tr>";
		}
	}
	echo $pdsllist;
}else if($action=="remove_exceldataentry"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$dbsno = $_POST['dbsno'];
	$rdq=$conn->query("SELECT sno FROM tbl_import_excelsheetdata WHERE (tedit is null or tedit='') and sno='".$dbsno."'");
	$rmstatus=array();
	if($rdq->num_rows>0){
		$teditremark="removed on date ".date('Y-m-d H:is')." by ".$loginname;
		$conn->query("UPDATE tbl_import_excelsheetdata SET tedit='".$teditremark."' where sno='".$dbsno."'");
		if(mysqli_affected_rows($conn)>0){
			$rmstatus=array('status'=>'Success', 'message'=>'Entry removed successfully');
		}else{
			$rmstatus=array('status'=>'Error', 'message'=>'Entry did not remove or already removed, please refresh web page');
		}
	}else{
		$rmstatus=array('status'=>'Error', 'message'=>'Entry did not remove or already removed, please refresh web page');
	}
	echo json_encode($rmstatus);
}else if($action=="createbookwith_exceldata"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$loginname = $_POST['loginname'];
	$exceldata = json_decode($_POST['exceldata'], true);
	$rrst=array();
	$rrsttt=array();
	if(count($exceldata)>0){
		foreach ($exceldata as $ssskey) {
			if($ssskey['purposecode']!=""){
				$pddata=uploadedexcelsheet_datafromsqltable($conn,$companyid,$subcompanyid,$projectid,"sno",$ssskey['dbsno']);
				if(count($pddata)>0){
					$beneficiarydeatilslist=array();
					foreach ($pddata as $pdkey) {
						if($pdkey['uploaddatatype']=="salaryexpense"){
							$eem=employeemaster($conn,$companyid,$subcompanyid,$projectid,"employeeid",$pdkey['paytoid']);
							$emppurposecode="";
							$emppurpose="";
							if(count($eem)>0){
								foreach ($eem as $eemkey) {
									$emppurposecode=$eemkey['purposecode'];
									$emppurpose=$eemkey['purpose'];
								}
							}
							$remark = "Salary for the month of ".date('M-Y', strtotime($pdkey['monthofsalary'])). ' with Remark '.$pdkey['remark'].' salary days is '.$pdkey['salarydays'];
						}else{
							$ppt=getpaytoinfo($conn,$companyid,$subcompanyid,"paytoid",$pdkey['paytoid']);
							if(count($ppt)>0){
								foreach ($ppt as $pptkey) {
									$paytoid = $pptkey['paytoid'];
								}
								$emppurposecode=$pdkey['purposecode'];
								$emppurpose=$pdkey['purpose'];
							}
						}

						$beneficiarydeatilslist[]=array('beneficiaryname'=>$pdkey['paytoname'], 'paytoid'=>$pdkey['paytoid'],'expenseamount'=>$pdkey['transfersalary'], 'invoicetype'=>'', 'description'=>$remark, 'purposecode'=>$ssskey['purposecode'], 'purposename'=>"", 'dbsno'=>$ssskey['dbsno']);
						$accountid="";
						$paymentmode="online";
						$chequeno="";
						$chequedate="";

						$rrsttt=recunstructbook_fromexceldata($conn,$companyid,$subcompanyid,$projectid,$accountid,$paymentmode,$chequeno,$chequedate,$pdkey['transfersalary'],$pdkey['paytoid'],$pdkey['paytoname'],$pdkey['bankname'],$pdkey['accountno'],$pdkey['ifsccode'],$beneficiarydeatilslist,$loginname,$pdkey['uploaddatatype']);
						// print_r($rrsttt);
						$rrst=$rrsttt; 
					}
				}
			}			
		}
	}
	echo json_encode($rrst);
}else if($action=="uploadrequest_attachement"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$attachedfile = $_FILES['attachedfile']['name'];
	$filepath="";
	// $distpath="../../requestdocument/";
	$distpath="../../attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/requestdocument/".date('Ymd').'/';
	$rtnmsg=array();
	if($_FILES['attachedfile']['name']){
		if ( !is_dir( $distpath ) ) {
		    mkdir($distpath, 0755, true);       
		}
        $temp = explode(".", $_FILES["attachedfile"]["name"]);
		$extension=end($temp);
		$newfilename = round(microtime(true)) .'_'.date('His'). '.' . end($temp);
        move_uploaded_file($_FILES["attachedfile"]["tmp_name"], $distpath . $newfilename);
        $filepath = $distpath . $newfilename;
        $filepath = str_replace("../", "", $filepath);
        $rtnmsg = array('status'=>'Success', 'message'=>'File uploaded Successfully', 'filepath'=>$filepath);
	}else{
		$rtnmsg = array('status'=>'Error', 'message'=>'File did not upload, due to technical issue', 'filepath'=>'');
	}
	echo json_encode($rtnmsg);
}else if($action=="expensetypehtml"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$subaction = $_POST['subaction'];
	if($subaction=="prepayment"){
		$postpaymentdivhide="none";
	}
	?>
	<div class="row">
		<div class="col-md-12 mt-2  <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>" >
	        <label class="m-2"><input type="radio" name="purposecategory" value="salary" style="transform: scale(2);" /> Salary</label>
	    </div>
	    <div class="col-md-12 mt-2 <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
	        <label class="m-2"><input type="radio" name="purposecategory" value="otherthansalary" style="transform: scale(2);" /> UPLOAD EXPENSE OTHER THAN SALARY</label>
	    </div>
	    <!-- <div class="col-md-12 mt-2 <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
	        <label class="m-2"><input type="radio" name="purposecategory" value="purchaseforproduction" style="transform: scale(2);" /> PURCHASE FOR PRODUCTION</label>
	    </div> -->
	    <div class="col-md-12 mt-2 <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
	        <label class="m-2"><input type="radio" name="purposecategory" value="vendortds" style="transform: scale(2);" /> TDS REQUST</label>
	    </div>
		<?php
		$ppg=getpurposecategorymainmaster($mconn,"readyforrequest","");
	    if(count($ppg)>0){
	       	$categoryname="";
	        foreach ($ppg as $ppgkey) {
	            if($ppgkey['categoryid']>1){
            		$categoryname=$ppgkey['category'];
	        ?>
	        <div class="col-md-12 mt-2">
	        	<label class="m-2"><input type="radio" name="purposecategory" value="<?php echo $ppgkey['categoryid']; ?>" style="transform: scale(2);" /> <?php echo $categoryname; ?></label>
	        </div>
	        <?php
	        	}
	        }
	    }
	    // request_billpayment
	    $billlist=billmasteruniversal($conn,$companyid,$subcompanyid,$projectid,"all","");
	    if(count($billlist)>0){
	    ?>
	    <div class="col-md-12 mt-2 <?php echo ($subaction == 'prepayment') ? '' : 'd-none'; ?>">
	        <label class="m-2"><input type="radio" name="purposecategory" value="billrequest" style="transform: scale(2);" /> Bill Payment Request</label>
	    </div>
	    <?php
	    }
	    ?>
	    <!-- <div class="col-md-12 mt-2">
          <label class="m-2"><input type="radio" name="purposecategory" value="1-2" style="transform: scale(2);" /> Internal Bank Transfer</label>
      	</div> -->
      	<div class="col-md-12 mt-2">
          <label class="m-2"><input type="radio" name="purposecategory" value="taxinvoice" style="transform: scale(2);" /> Request for Tax Invoice</label>
      	</div>
        <div class="col-md-12 mt-2">
          <label class="m-2"><input type="radio" name="purposecategory" value="1-1" style="transform: scale(2);" /> Other Than The Above Single Purpose</label>
      	</div>
    </div>
    <div class="row" style="display: block;">
        <div class="col-md-12 mt-2">
          <label class="m-2" id="otherthantheselbl"><input type="radio" name="purposecategory" value="1" style="transform: scale(2);" /> Other Than The Above Multiple Purpose</label>
        </div>
    </div>
    <?php
}else if($action=="getImtestpaytoattach"){
	$imprestpayemail =$_REQUEST['imprestpayemail'];
	$worshortby="allwithoutrequest";
	$worshortbyvalue="";
	$filedata=getImtestpaytoattach($conn,$imprestpayemail,$worshortby,$worshortbyvalue);
	echo json_encode($filedata);
}else if($action=="getdataforImtestrequest"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$imprestpayemail = $_POST['imprestpayemail'];
	$rdipq=getdataforimprestrequest($conn,$companyid,$subcompanyid,$projectid,"readytorequest_withemailid",$imprestpayemail);
	echo json_encode($rdipq);
}else if($action=="markautoreconcilationmanual"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$accountid = $_POST['accountid'];
	$closetobookdbsno = $_POST['closetobookdbsno'];
	$transactiondate = $_POST['transactiondate'];
	$transactionno = $_POST['transactionno'];
	$referenceno = $_POST['referenceno'];
	$rrct=reconciled_updatebankstatment_closetobook($conn,$referenceno,$transactiondate,$closetobookdbsno,$closetobookdbsno,"unknownpanel");
	echo json_encode($rrct);
}
?>