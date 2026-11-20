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

$filename="{$ProjectID}_{$SalaryYear}_{$SalaryMonth}_SALARY_SHEET.xls";
header("Content-Disposition: attachment; filename=\"$filename\""); 
header("Content-Type: application/vnd.ms-excel");
$salaryyearmonth = date('M-Y', strtotime($SalaryYear.'-'.$SalaryMonth.'-01'));
// company details
$cmpprj=getprojectdetailwithcmpsubcmp($conn,$CompanyID, $SubCompanyID,"withauthrizedprojects",$ProjectID);
$companyname = $cmpprj[0]['companyname'];
$projectname = $cmpprj[0]['projectname'];

$SLL=getEmployeeCalculatedSalary($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, "salarymonth", $SalaryMonth);
if(count($SLL)>0)
{
?>
	<table border=1>
		<thead>
			<tr><td colspan="16" style="font-weight:bold; font-size:16px"><?php echo $projectname; ?></td></tr>
			<tr><td colspan="16" style="font-weight:bold; font-size:16px">Salary Month <?php echo $salaryyearmonth; ?></td></tr>
			<tr>
				<th>S. No.</th>
				<th>Employee Name</th>
				<th>Payable Days</th>
				<th>Gross Salary</th>
				<th>Basic Salary</th>
				<th>HRA</th>
				<th>Conveyance</th>
				<th>Itax Deduction</th>
				<th>EPF Deduction</th>
				<th>ESIC Deduction</th>
				<th>Other Deduction</th>
				<th>Bank Name</th>
				<th>Bank Account No</th>
				<th>Bank IFS Code</th>
				<th>Net Salary Payable</th>
				<th>Request No</th>
			</tr>
		</thead>
		<tbody>
		<?php
		$i=1;
		foreach ($SLL as $row) {
			// Attendance details
		    $att=fetchEmployeeCalculatedAttendance($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['SalaryYear'], $row['SalaryMonth'], "EmployeeID", $row['EmployeeID']);
		    $payableDays=str_replace(".00", "", $att[0]['CalculatedPresentDays']);

		    // Employee Bank Details
		    $Ebnak=getEmployeeBankDetails($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['EmployeeID']);
		    $EmpBankName ="";
			$EmpAccountNumber ="";
			$EmpBankIFSCCode ="";
		    if(count($Ebnak)>0){
		    	$EmpBankName = $Ebnak[0]['BankName'];
		    	$EmpAccountNumber = $Ebnak[0]['AccountNumber'];
		    	$EmpBankIFSCCode = $Ebnak[0]['IFSCCode'];
		    }

		    // employeeInformation
		    $eDkey = fetchEmployeeData($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], 'EmployeeID', $row['EmployeeID']);
		    // print_r($eDkey);
		    $EmployeeName="";
		    $GenderName="";
		    $DateOfJoining="";
		    $EmploymentTypeid="";
		    $DesignationID="";
		    $DepartmentID="";
		    $Email="";
		    if(count($eDkey)>0){
		        $EmployeeName=$eDkey[0]['TitleName'].' '.$eDkey[0]['FirstName'].' '.$eDkey[0]['MiddleName'].' '.$eDkey[0]['LastName'];
		        $GenderName=$eDkey[0]['Gender'];
		        $DateOfJoining=date('d-M-Y', strtotime($eDkey[0]['DateOfJoining']));
		        $EmploymentTypeid=$eDkey[0]['EmploymentTypeid'];
		        $Email=$eDkey[0]['Email'];
		        
		        $DesignationID=$eDkey[0]['DesignationID'];
		        $DesignationName="";
		        if($eDkey[0]['DesignationID']){
		            $DsN=fetchDesignationsMaster($hrmconn,$companyid, 'DesignationID', $eDkey[0]['DesignationID']);
		            $DesignationName = $DsN[0]['DesignationName']; 
		        }
		        $DepartmentID=$eDkey[0]['DepartmentID'];
		        $DepartmentName="";
		        if($eDkey[0]['DepartmentID']){
		            $DpI=fetchDepartmentMaster($hrmconn,$companyid, 'DepartmentID', $eDkey[0]['DepartmentID']);
		            $DepartmentName=$DpI[0]['DepartmentName'];
		        }
		        // $EPF = getDeductionAmount($conn, $companyId, $subCompanyId, $projectId, $employeeId, 1, $row['SalaryID']);

		        $EmpDdct = getEmployeeSalaryDeductions($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['EmployeeID'], $row['SalaryYear'], $row['SalaryMonth']);
		        $EPF=0;
		        $ESIC=0;
		        $INCOMTAX=0;
		        $LOAN=0;
		        $OtherDeduction=0;
		        if(count($EmpDdct)>0){
		            if($EmpDdct[0]['DeductionId']==1){
		                $EPF = $EmpDdct[0]['DeductionAmount'];
		            }else if($EmpDdct[0]['DeductionId']==5){
		                $ESIC = $EmpDdct[0]['DeductionAmount'];
		            }else if($EmpDdct[0]['DeductionId']==6){
		                $INCOMTAX = $EmpDdct[0]['DeductionAmount'];
		            }else{
		                $OtherDeduction += $EmpDdct[0]['DeductionAmount'];
		            }
		        }
		    }
		?>
			<tr>
				<td><center><?php echo $i++; ?></center></td>
				<td style="white-space: nowrap;"><?php echo strtoupper($EmployeeName); ?></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $payableDays; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $row['GrossSalary']; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $row['BasicSalary']; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $row['HRA']; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $row['Allowances']; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $INCOMTAX; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $EPF; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $ESIC; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $OtherDeduction; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $EmpBankName; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $EmpAccountNumber; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $EmpBankIFSCCode; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $row['NetSalary']; ?></center></td>
				<td  style="mso-number-format:'\@';"><center><?php echo $row['requestno']; ?></center></td>
			</tr>
		<?php
		$ttl_gs += $row['GrossSalary'];
		$ttl_bs += $row['BasicSalary'];
		$ttl_hs += $row['HRA'];
		$ttl_as += $row['Allowances'];
		$ttl_ditax += $INCOMTAX;
		$ttl_dpf += $EPF;
		$ttl_dsi += $ESIC;
		$ttl_dod += $OtherDeduction;
		$ttl_ntpay += $row['NetSalary'];
		}
		?>
		</tbody>
		<tfoot>
			<tr style="font-weight:bold;">
				<td colspan="3">Total</td>
				<td><center><?php echo $ttl_gs; ?></center></td>
				<td><center><?php echo $ttl_bs; ?></center></td>
				<td><center><?php echo $ttl_hs; ?></center></td>
				<td><center><?php echo $ttl_as; ?></center></td>
				<td><center><?php echo $ttl_ditax; ?></center></td>
				<td><center><?php echo $ttl_dpf; ?></center></td>
				<td><center><?php echo $ttl_dsi; ?></center></td>
				<td><center><?php echo $ttl_dod; ?></center></td>
				<td></td>
				<td></td>
				<td></td>
				<td><center><?php echo $ttl_ntpay; ?></center></td>
				<td><center></center></td>
			</tr>
		</tfoot>
	</table>
<?php
}
?>