<?php
require('../../connection.php');
require('../functions/admin_functionmaster.php');
require('../functions/hrm_FunctionMaster.php');
$CompanyID = $_GET['companyid'];
$SubCompanyID = $_GET['subcompanyid'];
$ProjectID = $_GET['projectid'];
$SalaryYear = $_GET['salaryyear'];
$SalaryMonth = $_GET['salarymonth'];

$filename="{$ProjectID}_{$SalaryYear}_{$SalaryMonth}_EPF_Challan.txt";
header("Content-Disposition: attachment; filename=\"$filename\""); 
header('Content-type:  text/plain');

$salaryyearmonth = date('M-Y', strtotime($SalaryYear.'-'.$SalaryMonth.'-01'));

// company details
$cmpprj=getprojectdetailwithcmpsubcmp($conn,$CompanyID, $SubCompanyID,"withauthrizedprojects",$ProjectID);
$companyname = $cmpprj[0]['companyname'];
$projectname = $cmpprj[0]['projectname'];

$SLL=getEmployeeCalculatedSalary($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, "salarymonth", $SalaryMonth);
if(count($SLL)>0)
{
	$i=0;
	foreach ($SLL as $row) {
		$payable_gross = $row['GrossSalary'];
		$EPF_Wages = $row['epfwages'];
		$EPS_Wages = $row['epswages'];
		$EDLI_Wages = $row['eldiwages'];
		$EE_Share_Remitted = round($row['eeshareremitted']);
		$EPS_Contribution = round($row['epscont']);
		$ER_Share_Remitted = round($row['ershareremitted']);
		$NCP_Days = 0;
		$Refunds = 0;
		
		if(count($SLL)==$i){
		echo $row['pf_uanno']."#~#".strtoupper($row['empname'])."#~#".$payable_gross."#~#".$EPF_Wages."#~#".$EPS_Wages."#~#".$EDLI_Wages."#~#".$EE_Share_Remitted."#~#".$EPS_Contribution."#~#".$ER_Share_Remitted."#~#".$NCP_Days."#~#".$Refunds;

		}else{
		echo $row['pf_uanno']."#~#".strtoupper($row['empname'])."#~#".$payable_gross."#~#".$EPF_Wages."#~#".$EPS_Wages."#~#".$EDLI_Wages."#~#".$EE_Share_Remitted."#~#".$EPS_Contribution."#~#".$ER_Share_Remitted."#~#".$NCP_Days."#~#".$Refunds."\r\n";
		}
		$i++;
	}
}

?>
