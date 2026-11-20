<?php
$projectname="";
$salaryyearmonth="";
require('../../connection.php');
require('../functions/admin_functionmaster.php');
require('../functions/hrm_FunctionMaster.php');
$CompanyID = $_GET['companyid'];
$SubCompanyID = $_GET['subcompanyid'];
$ProjectID = $_GET['projectid'];
$SalaryYear = $_GET['salaryyear'];
$SalaryMonth = $_GET['salarymonth'];

$total_payable_gross=0;
$total_EPF_Wages=0;
$total_EPS_Wages=0;
$total_EDLI_Wages=0;
$total_EE_Share_Remitted=0;
$total_EPS_Contribution=0;
$total_ER_Share_Remitted=0;
$total_NCP_Days=0;
$total_Refunds=0;
$filename="{$ProjectID}_{$SalaryYear}_{$SalaryMonth}_EPF_SHEET.xls";
header("Content-Disposition: attachment; filename=\"$filename\""); 
header("Content-Type: application/vnd.ms-excel; charset=UTF-8;");

$salaryyearmonth = date('M-Y', strtotime($SalaryYear.'-'.$SalaryMonth.'-01'));
// company details
$cmpprj=getprojectdetailwithcmpsubcmp($conn,$CompanyID, $SubCompanyID,"withauthrizedprojects",$ProjectID);
$companyname = $cmpprj[0]['companyname'];
$projectname = $cmpprj[0]['projectname'];

?>
<table border=1  class="table2excel">
	<thead>
		<tr class="w3-light-grey">
			<th>UAN</th>
			<th>Name</th>
			<th>Gross Wages</th>
			<th>EPF Wages</th>
			<th>EPS Wages</th>
			<th>EDLI Wages</th>
			<th>EE_Share Remitted</th>
			<th>EPS Contribution</th>
			<th>ER Share Remitted</th>
			<th>NCP Days</th>
			<th>Refunds</th>
		</tr>
	</thead>
	<tbody>
	<?php
	$EPFTRAN=getEPFTransactions($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
	if(count($EPFTRAN)>0)
	{

		// EmployeeId, DeductionId, DeductionName, BasicSalary, EmployeePF, EPS, EmployerPF, EDLI, PFAdmin, EDLIAdmin, TotalEmployer, GrandTotal, SalaryYear, SalaryMonth
		$payable_gross = "";
		$EPF_Wages = "";
		$EPS_Wages = "";
		$EDLI_Wages = "";
		$EE_Share_Remitted = "";
		$EPS_Contribution = "";
		$ER_Share_Remitted = "";
		$NCP_Days = "";
		$Refunds = "";
		foreach ($EPFTRAN as $epdkey) {
			$EmpData = fetchEmployeeData($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, 'EmployeeID', $epdkey['EmployeeId']);
			$EmployeeName = $EmpData[0]['FullName'];
			$pf_uanno = getEmployeeDeductionRegId($hrmconn, $CompanyID, $ProjectID, $epdkey['EmployeeId'], 1);

			$sldata = getEmployeeCalculatedSalary($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, 'employeeid', $epdkey['EmployeeId']);

			$payable_gross = $sldata[0]['GrossSalary']??0;
			$EPF_Wages = $epdkey['EmployeePF']??0;
			$EPS_Wages = $epdkey['EPS']??0;
			$EDLI_Wages = $epdkey['EDLI']??0;
			$EE_Share_Remitted = round($epdkey['EmployeePF'])??0;
			$EPS_Contribution = round($epdkey['EPS'])??0;
			$ER_Share_Remitted = round($epdkey['EmployerPF'])??0;
			$NCP_Days = getNCPDays($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $epdkey['EmployeeId'], $SalaryYear, $SalaryMonth);
			$Refunds = 0;
	?>
		<tr>
			<td style="text-align:center; mso-number-format:'\@';"><?php echo $pf_uanno; ?></td>
			<td><?php echo strtoupper($EmployeeName); ?></td>
			<td style="text-align:center"><?php echo $payable_gross ; ?></td>
			<td style="text-align:center"><?php echo $EPF_Wages; ?></td>
			<td style="text-align:center"><?php echo $EPS_Wages; ?></td>
			<td style="text-align:center"><?php echo $EDLI_Wages; ?></td>
			<td style="text-align:center"><?php echo $EE_Share_Remitted; ?></td>
			<td style="text-align:center"><?php echo $EPS_Contribution; ?></td>
			<td style="text-align:center"><?php echo $ER_Share_Remitted; ?></td>
			<td style="text-align:center"><?php echo $NCP_Days; ?></td>
			<td style="text-align:center"><?php echo $Refunds; ?></td>
		</tr>

			<?php
			$total_payable_gross+=$payable_gross;
			$total_EPF_Wages+=$EPF_Wages;
			$total_EPS_Wages+=$EPS_Wages;
			$total_EDLI_Wages+=$EDLI_Wages;
			$total_EE_Share_Remitted+=$EE_Share_Remitted;
			$total_EPS_Contribution+=$EPS_Contribution;
			$total_ER_Share_Remitted+=$ER_Share_Remitted;
			$total_NCP_Days+=$NCP_Days;
			$total_Refunds+=$Refunds;
		}
		?>
	</tbody>
	<tfoot>
		<tr style="font-weight:bold;">
			<td colspan="2">Total</td>
			<td style="text-align:center"><?php echo $total_payable_gross ; ?></td>
			<td style="text-align:center"><?php echo $total_EPF_Wages; ?></td>
			<td style="text-align:center"><?php echo $total_EPS_Wages; ?></td>
			<td style="text-align:center"><?php echo $total_EDLI_Wages; ?></td>
			<td style="text-align:center"><?php echo $total_EE_Share_Remitted; ?></td>
			<td style="text-align:center"><?php echo $total_EPS_Contribution; ?></td>
			<td style="text-align:center"><?php echo $total_ER_Share_Remitted; ?></td>
			<td style="text-align:center"><?php echo $total_NCP_Days; ?></td>
			<td style="text-align:center"><?php echo $total_Refunds; ?></td>
		</tr>
	<?php 
	}else{
	?>
		<tr><td colspan="11" class="w3-center">No Data Avialable </td></tr>
	<?php
	}
	?>
	</tfoot>
</table>