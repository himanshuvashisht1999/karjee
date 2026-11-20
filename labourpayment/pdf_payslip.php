<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 1);
// include('../pdfroutines/htmltopdf.php');
require_once('../pdfroutines/tcpdf/tcpdf.php');
include('../connection.php');

$companyid = $_POST['companyid']??'14';
$companyname = $_POST['companyname']??'IDI';
$subcompanyid = $_POST['subcompanyid']??'7';
$projectid = $_POST['projectid']??'9';
$employeeid = $_POST['empid']??'1';
$salaryyear  = $_POST['salaryyear']??'2025';
$salarymonth  = $_POST['salarymonth']??'07';

$folderName = "attachementfile/".$companyid."/".$subcompanyid."/".$projectid."/PaySlip/".$empid."/".$salaryyear."/".$salarymonth."/";
if (!file_exists($folderName)) {
    mkdir($folderName, 0777);
}
// 1. Get employee name and designation
// Check DB connection
if (!$hrmconn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Prepare employee SQL
$emp_sql = "SELECT FirstName, MiddleName, LastName, DesignationID 
            FROM hrm_EmployeesMaster 
            WHERE companyid=? AND subcompanyid=? AND projectid=? AND EmployeeID=?";
$emp_stmt = $hrmconn->prepare($emp_sql);

// Check prepare success
if (!$emp_stmt) {
    die("Prepare failed: " . $hrmconn->error);
}

// Bind parameters and execute
$emp_stmt->bind_param("ssss", $companyid, $subcompanyid, $projectid, $employeeid);
$emp_stmt->execute();

// Fetch data
$emp_result = $emp_stmt->get_result();
$emp_row = $emp_result->fetch_assoc();

$employeename = trim($emp_row['FirstName'] . ' ' . $emp_row['MiddleName'] . ' ' . $emp_row['LastName']);
$designation = $emp_row['DesignationID'];

$emp_stmt->close();

// 2. Get salary data
$sal_sql = "
SELECT SalaryID, GrossSalary, BasicSalary, HRA, Allowances, OvertimePay, ArrearAmount, Deductions, NetSalary,
       requestno, PaymentDate, CurrencyCode, PaymentMethod, Comments
FROM hrm_EmployeeCalculatedSalary 
WHERE CompanyID = ? AND SubCompanyID = ? AND ProjectID = ? AND EmployeeID = ? AND SalaryYear = ? AND SalaryMonth = ?
";

$sal_stmt = $hrmconn->prepare($sal_sql);
if (!$sal_stmt) {
    die("Prepare failed for salary query: " . $hrmconn->error);
}

$sal_stmt->bind_param("iiiiii", $companyid, $subcompanyid, $projectid, $employeeid, $salaryyear, $salarymonth);
$sal_stmt->execute();

$sal_stmt->bind_result(
    $salaryid, $gross, $basic, $hra, $allowances, $overtime, $arrear, $deductions, $net,
    $requestno, $paydate, $currency, $paymethod, $comments
);
$sal_stmt->fetch();
$sal_stmt->close();

$monthname = date("F", mktime(0, 0, 0, $salarymonth, 1));

// 3. Generate PDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetTitle("Payslip - $employeename");
$pdf->SetMargins(10, 10, 10);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

// 4. Format
$html = <<<EOD
<style>
    table { border-collapse: collapse; width: 100%; }
    td, th { border: 1px solid #333; padding: 5px; font-size: 28px; }
</style>

<h2 align="center">Employee Payslip</h2>

<table>
    <tr>
        <td><strong>Name</strong></td><td>{$employeename}</td>
        <td><strong>Employee ID</strong></td><td>{$employeeid}</td>
    </tr>
    <tr>
        <td><strong>Designation</strong></td><td>{$designation}</td>
        <td><strong>Month</strong></td><td>{$monthname} {$salaryyear}</td>
    </tr>
    <tr>
        <td><strong>Request No</strong></td><td>{$requestno}</td>
        <td><strong>Payment Date</strong></td><td>{$paydate}</td>
    </tr>
</table>
<br/>

<h4>Earnings</h4>
<table>
    <tr><td>Basic Salary</td><td align="right">₹ {$basic}</td></tr>
    <tr><td>HRA</td><td align="right">₹ {$hra}</td></tr>
    <tr><td>Allowances</td><td align="right">₹ {$allowances}</td></tr>
    <tr><td>Overtime</td><td align="right">₹ {$overtime}</td></tr>
    <tr><td>Arrear</td><td align="right">₹ {$arrear}</td></tr>
    <tr><td><strong>Gross Salary</strong></td><td align="right"><strong>₹ {$gross}</strong></td></tr>
</table>
<br/>

<h4>Deductions</h4>
<table>
    <tr><td>Total Deductions</td><td align="right">₹ {$deductions}</td></tr>
</table>
<br/>

<h3>Net Salary Payable: ₹ {$net} ({$currency})</h3>
<br/>

<table>
    <tr><td><strong>Payment Method</strong></td><td>{$paymethod}</td></tr>
</table>
<br/>

<p><strong>Remarks:</strong> {$comments}</p>
<p style="font-size:11px;"><i>This is a system-generated document and does not require signature.</i></p>
EOD;

// Output
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("Payslip_{$employeeid}_{$salarymonth}_{$salaryyear}.pdf", 'I');

$conn->close();
?>



