<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 1);
include('pdfroutines/htmltopdf.php');
include('../connection.php');
include('functions/admin_functionmaster.php');
$companyid   = $_REQUEST['companyid'] ?? '';
$subcompanyid = $_REQUEST['subcompanyid'] ?? '';
$projectid   = $_REQUEST['projectid'] ?? '';
$loginname   = $_REQUEST['loginname'] ?? 'test';
$DateFrom    = $_REQUEST['datefrom'] ?? '';
$DateTo      = $_REQUEST['dateto'] ?? '';
$exporttype  = $_REQUEST['exporttype'] ?? '';   /* paid/unpaid*/
$period      = date('d-M-Y', strtotime($DateFrom)) . " To " . date('d-M-Y', strtotime($DateTo));

$prjd = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
$projectname = $prjd[0]['projectname'] ?? 'Unknown Project';

if (!$hrmconn) die("Connection failed: " . mysqli_connect_error());

/* ---------------------------------
   FETCH LABOUR SALARY RECORDS
--------------------------------- */

$emp_sql = "
SELECT 
    s.SalaryID, s.EmployeeID, s.DateFrom, s.Dateto AS DateTo,
    s.NetSalary, s.PaidAmount, s.updated_at, s.updated_by, s.PaymentRemark,
    e.FirstName, e.MiddleName, e.LastName,
    b.bankname, b.accountnumber, b.ifsccode
FROM hrm_LabourCalculatedSalary s
LEFT JOIN hrm_LabourMaster e ON e.EmployeeID = s.EmployeeID
LEFT JOIN hrm_labourbankdetails b ON s.EmployeeID = b.labourid AND (b.tedit IS NULL OR b.tedit = '')
WHERE s.ProjectID = '{$projectid}'
  AND s.DateFrom = '{$DateFrom}'
  AND s.DateTo   = '{$DateTo}'
  AND (s.PaymentStatus IS NULL OR s.PaymentStatus = '' OR s.PaymentStatus != 'Paid')
ORDER BY s.EmployeeID
";

$emp_result = $hrmconn->query($emp_sql) or die($hrmconn->error);
if ($emp_result->num_rows === 0) {
    die("No pending labour records found.");
}

/* ---------------------------------
   BUILD TABLE ROWS
--------------------------------- */

$laborwagestr = "";
$sno = 1;

$totalNetSalary = 0;
$totalPaidAmount = 0;

while ($emp_row = $emp_result->fetch_assoc()) {
    $EmployeeID = $emp_row['EmployeeID'];
    $netPayable = (float)$emp_row['NetSalary'];
    $paid       = (float)$emp_row['PaidAmount'];
    $balance    = $netPayable - $paid;

    $updateAt   = ($emp_row['updated_at'] && $emp_row['updated_at'] !== '0000-00-00 00:00:00') ? $emp_row['updated_at'] : '-';
    $updateBy   = $emp_row['updated_by'] ?: '-';
    $remark     = $emp_row['PaymentRemark'] ?: '-';
    $bankName   = $emp_row['bankname'] ?: '-';
    $accNo      = $emp_row['accountnumber'] ?: '-';
    $ifsc       = $emp_row['ifsccode'] ?: '-';

    $employeeName = trim(
        ($emp_row['FirstName'] ?? '') . " " .
        ($emp_row['MiddleName'] ?? '') . " " .
        ($emp_row['LastName'] ?? '')
    );

    $laborwagestr .= '
    <tr>
        <td style="width:3%;">'.$sno.'</td>
        <td style="width:8%;">'.htmlspecialchars($EmployeeID).'</td>
        <td style="width:12%;">'.htmlspecialchars($employeeName).'</td>
        <td style="width:8%;">'.htmlspecialchars($bankName).'</td>
        <td style="width:10%;">'.htmlspecialchars($accNo).'</td>
        <td style="width:8%;">'.htmlspecialchars($ifsc).'</td>
        <td style="width:7%;">'.htmlspecialchars($emp_row['DateFrom']).'</td>
        <td style="width:7%;">'.htmlspecialchars($emp_row['DateTo']).'</td>
        <td style="width:8%;">'.number_format($netPayable, 2).'</td>
        <td style="width:7%;">'.number_format($paid, 2).'</td>
        <td style="width:7%;">'.number_format($balance, 2).'</td>
        <td style="width:7%;">'.htmlspecialchars($updateAt).'</td>
        <td style="width:6%;">'.htmlspecialchars($updateBy).'</td>
        <td style="width:8%;">'.htmlspecialchars($remark).'</td>
    </tr>';

    $totalNetSalary  += $netPayable;
    $totalPaidAmount += $paid;
    $sno++;
}

/* ---------- TOTAL ROW ---------- */

$laborwagestr .= '
<tr style="font-weight:bold; background-color:#f2f2f2;">
    <td colspan="8" style="width:63%; text-align:right;">Total</td>
    <td style="width:8%;">'.number_format($totalNetSalary, 2).'</td>
    <td style="width:7%;">'.number_format($totalPaidAmount, 2).'</td>
    <td style="width:7%;">'.number_format($totalNetSalary - $totalPaidAmount, 2).'</td>
    <td style="width:7%;"></td>
    <td style="width:6%;"></td>
    <td style="width:8%;"></td>
</tr>';

/* ---------------------------------
   TCPDF CLASS
--------------------------------- */

class MyPDF extends TCPDF {
    protected $projectname;
    protected $generatedDate;
    protected $loginname;
    protected $period;

    public function setProjectName($name) { $this->projectname = $name; }
    public function setGeneratedDate($date) { $this->generatedDate = $date; }
    public function setLoginName($lgname) { $this->loginname = $lgname; }
    public function setDateRange($daterange) { $this->period = $daterange; }

    public function Header() {
        $this->SetFont('helvetica', 'B', 10);
        $this->SetY(6);
        $this->Cell(0, 8, 'Firm Name: '.$this->projectname, 0, 0, 'L');
        $this->Cell(0, 8, 'Page '.$this->getAliasNumPage().'/'.$this->getAliasNbPages(), 0, 1, 'R');
        $this->SetY(12);
        $this->Cell(0, 8, 'Period: '.$this->period, 0, 1, 'L');
    }

    public function Footer() {
        $this->SetY(-10);
        $this->SetFont('helvetica', 'I', 9);
        $text = 'Confidential | Generated on '.$this->generatedDate;
        if ($this->loginname) {
            $text .= ' | By '.$this->loginname;
        }
        $this->Cell(0, 10, $text, 0, 0, 'C');
    }
}

/* ---------------------------------
   GENERATE PDF
--------------------------------- */

$pdf = new MyPDF('L', 'mm', 'A4', true, 'UTF-8', false);
$pdf->setProjectName($projectname);
$pdf->setLoginName($loginname);
$pdf->setGeneratedDate(date('d-M-Y'));
$pdf->setDateRange($period);

$pdf->SetMargins(8, 22, 8);
$pdf->setCellHeightRatio(1.4);
$pdf->SetCellPadding(2);
$pdf->AddPage();

$html = '
<h3 style="text-align:center;">'.htmlspecialchars($projectname).'<br/>LABOUR WAGES PENDING REPORT</h3>

<style>
table { border-collapse: collapse; width: 100%; }
th { border:1px solid #333; background:#f2f2f2; font-size:7.5pt; font-weight:bold; padding:4px; text-align:center; }
td { border:1px solid #333; font-size:7pt; padding:4px; text-align:center; vertical-align:middle; }
</style>

<table>
<thead>
<tr>
<th style="width:3%;">S.No</th>
<th style="width:8%;">Labour ID</th>
<th style="width:12%;">Labour Name</th>
<th style="width:8%;">Bank Name</th>
<th style="width:10%;">A/C No</th>
<th style="width:8%;">IFSC</th>
<th style="width:7%;">DateFrom</th>
<th style="width:7%;">DateTo</th>
<th style="width:8%;">Net Payable</th>
<th style="width:7%;">Paid</th>
<th style="width:7%;">Balance</th>
<th style="width:7%;">Update At</th>
<th style="width:6%;">Update By</th>
<th style="width:8%;">Update Remark</th>
</tr>
</thead>
<tbody>'.$laborwagestr.'</tbody>
</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output("LabourWagesPending_{$DateFrom}_{$DateTo}.pdf", "D");

$hrmconn->close();
?>
