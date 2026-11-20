<?php 
ob_start();
@ini_set('display_errors', 0);
@ini_set('display_startup_errors', 0);
error_reporting(0);
require('../../connection.php');
require('../functions/admin_functionmaster.php');
require('../functions/comanphp_functions.php');
require('../functions/hrm_FunctionMaster.php');
require('../functions/hrm_insertupdatesalary_functions.php');
require('../pdfroutines/tcpdf/tcpdf.php');
@ini_set('display_errors', 0);
error_reporting(0);
$action=$_GET['action'];
if($action=="EmployeelistforCalculateAttendancehtml"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $salaryyearmonth = $_POST['salaryyearmonth'] ?? null;
    $salaryyearmonthexp = explode("-", $salaryyearmonth);
    $attyear = $salaryyearmonthexp[0];
    $attmonth = $salaryyearmonthexp[1];
    $emplist = fetchEmployeeDataForSalaryCalculation($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth);
    $emplt='';
    if(count($emplist)>0){
        $sno=1;
        $empname="";
        foreach ($emplist as $eemkey) {
            $empname = $eemkey['FirstName'].' '.$eemkey['MiddleName'].' '.$eemkey['LastName'];

            // employee salary details
            $empsl=fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $eemkey['EmployeeID'], $salaryyearmonth);
            $empsalary =0;
            if(count($empsl)>0){
                $emkkey = $empsl[0];
                $empsalary = floatval($emkkey['BasicSalary'] + $emkkey['HRA'] + $emkkey['ConveyanceAllowance'] + $emkkey['SpecialAllowance'] + $emkkey['Deductions']);
            }


            // check if already exist salary attendance
            $empatt=fetchEmployeeCalculatedAttendance($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth, "EmployeeID", $eemkey['EmployeeID']);
            $daysinmonth=0;
            $workingdaysinmonth =0;    
            $CalculatedPresentDays=0;
            $empHoliDays = 0;
            $empWeekOffcount = 0;
            if(count($empatt)>0){
                $CalculatedPresentDays =$empatt[0]['CalculatedPresentDays']; 
                // TotalDaysInMonth,WorkingDaysInMonth,WeekOffDays
                $daysinmonth = $empatt[0]['TotalDaysInMonth'];
                $workingdaysinmonth = $empatt[0]['WorkingDaysInMonth'];
                $empWeekOffcount = $empatt[0]['WeekOffDays'];
                $empHoliDays = $empatt[0]['HoliDays'];
            }else{
                // $daysinmonth=getDaysInMonthForSalary($attyear,$attmonth);
                // $workingdaysinmonth = getWorkingDaysInMonth($hrmconn, $companyid, $subcompanyid, $projectid, $eemkey['EmployeeID'], $attyear, $attmonth);
                // $empWeekOffcount = getEmployeeWeekOffCountInMonth($hrmconn, $companyid, $subcompanyid, $projectid, $eemkey['EmployeeID'], $attyear, $attmonth);
                // $empHoliDays = getHoliDaysInMonth($hrmconn, $companyid, $subcompanyid, $projectid, $eemkey['EmployeeID'], $attyear, $attmonth);
            }

            $emplt .= '<tr data-companyid="'.$companyid.'" data-subcompanyid="'.$subcompanyid.'" data-projectid="'.$projectid.'" data-employeeid="'.$eemkey['EmployeeID'].'" data-employeename="'.$empname.'" data-attendanceyear="'.$attyear.'" data-attendancemonth="'.$attmonth.'">
               <td>'.$sno.'</td>
               <td>'.$empname.'</td>
               <td>'.$empsalary.'</td>
               <td>'.$daysinmonth.'</td>
               <td>'.$empHoliDays.'</td>
               <td>'.$empWeekOffcount.'</td>
               <td>'.$workingdaysinmonth.'</td>
               <td><input type="number" class="form-control presentdays" value="'.$CalculatedPresentDays.'" /></td>
           </tr>';
            $sno++;
        }
    }else{
        $emplt .= '<tr><td colspan="8" class="text-center">No employees found for the selected salary month.</td></tr>';
    }
    echo json_encode($emplt);    
}else if($action=="EmployeeSalaryforpayslip"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $salaryyear = $_POST['salaryyear'];
    $salarymonth = $_POST['salarymonth'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    if($shortby=="all"){
        $shortby = "salarymonth";
        $shortbyvalue = $salarymonth; 
    }
    
    $empcldSl=getEmployeeCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, $salaryyear, $salarymonth, $shortby, $shortbyvalue);
    $payslipdata=[];
    if(count($empcldSl)>0){
        foreach ($empcldSl as $slckey) {
            // employee data
            $empdata = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $slckey['EmployeeID']);
            $EmployeeName = "";
            $DateOfBirth = "";
            $DateOfJoining = "";
            $DesignationID = "";
            $Email = "";
            $MobileNo = "";
            $PanNumber = "";
            $DOJDMY ="";
            $DesignationName ="";
            $DepartmentName="";
            if(count($empdata)>0){
                $EmployeeName = $empdata[0]['FullName'];
                $DateOfBirth = $empdata[0]['DateOfBirth'];
                if($empdata[0]['DateOfJoining']){
                    $DOJDMY = date('d-M-Y', strtotime($empdata[0]['DateOfJoining']));
                }
                $DateOfJoining = $empdata[0]['DateOfJoining'];
                if($empdata[0]['DesignationID']){
                    $DsN=fetchDesignationsMaster($hrmconn,$companyid, 'DesignationID', $eDkey[0]['DesignationID']);
                    $DesignationName = $DsN[0]['DesignationName']; 
                }
                $DesignationID = $empdata[0]['DesignationID'];
                if($empdata[0]['DepartmentID']){
                    $DpI=fetchDepartmentMaster($hrmconn,$companyid, 'DepartmentID', $empdata[0]['DepartmentID']);
                    $DepartmentName=$DpI[0]['DepartmentName'];
                }

                $Email = $empdata[0]['Email'];
                $MobileNo = $empdata[0]['MobileNo'];
                $PanNumber = $empdata[0]['PanNumber'];
            }

            // Employee Attendance Data
            $attdata = fetchEmployeeCalculatedAttendance($hrmconn, $companyid, $subcompanyid, $projectid, $salaryyear, $salarymonth, 'EmployeeID', $slckey['EmployeeID']);
            $TotalDaysInMonth = "";
            $WorkingDaysInMonth = "";
            $PresentDays = "";
            $AbsentDays = "";
            $CalculatedPresentDays = "";
            if(count($attdata)>0){
                $TotalDaysInMonth = $attdata[0]['TotalDaysInMonth'];
                $WorkingDaysInMonth = $attdata[0]['WorkingDaysInMonth'];
                $PresentDays = $attdata[0]['PresentDays'];
                $AbsentDays = $attdata[0]['AbsentDays'];
                $CalculatedPresentDays = !empty($attdata[0]['TotalPayableDays']) ? $attdata[0]['TotalPayableDays'] : $attdata[0]['CalculatedPresentDays'];
            } else {
                $CalculatedPresentDays = $slckey['CalculatedPresentDays'] ?? 0;
            }

            // Employee Deductions
            $ddctdata = getEmployeeSalaryDeductions($hrmconn, $companyid, $subcompanyid, $projectid, $slckey['EmployeeID'], $salaryyear, $salarymonth);
            $EPFDeduction=0;
            $ESICDeduction=0;
            $IncomeTaxDeduction=0;
            $otherDeduction=0;
            if(count($ddctdata)>0){
                foreach ($ddctdata as $ddctkey) {
                    if($ddctkey['DeductionId']==1){
                        $EPFDeduction = $ddctkey['DeductionAmount'];
                    }else if($ddctkey['DeductionId']==3){
                        $ESICDeduction = $ddctkey['DeductionAmount'];
                    }else if($ddctkey['DeductionId']==4){
                        $IncomeTaxDeduction = $ddctkey['DeductionAmount'];
                    }else{
                        $otherDeduction +=  $ddctkey['DeductionAmount'];
                    }
                }
            }

            $calculatedNetSalary = floatval(
                ($slckey['BasicSalary'] ?? 0) + 
                ($slckey['HRA'] ?? 0) + 
                ($slckey['Conveyance'] ?? 0) + 
                ($slckey['Allowances'] ?? 0) + 
                ($slckey['OvertimePay'] ?? 0) + 
                ($slckey['ArrearAmount'] ?? 0)
            ) - (
                floatval($EPFDeduction) + 
                floatval($ESICDeduction) + 
                floatval($IncomeTaxDeduction) + 
                floatval($otherDeduction) + 
                floatval($slckey['AdvanceDeduction'] ?? 0)
            );

            $finalNetSalary = number_format(max(0, $calculatedNetSalary), 2, '.', '');

            $payslipdata[]=array('EmployeeID'=>$slckey['EmployeeID'], 'EmployeeName'=>$EmployeeName, 'DateOfBirth'=>$DateOfBirth, 'DateOfJoining'=>$DateOfJoining, 'DesignationName'=>$DesignationName, 'DepartmentName'=>$DepartmentName, 'Email'=>$Email, 'MobileNo'=>$MobileNo, 'TotalDaysInMonth'=>$TotalDaysInMonth, 'PresentDays'=>$PresentDays, 'AbsentDays'=>$AbsentDays, 'CalculatedPresentDays'=>$CalculatedPresentDays, 'EPFDeduction'=>$EPFDeduction, 'ESICDeduction'=>$ESICDeduction, 'IncomeTaxDeduction'=>$IncomeTaxDeduction, 'otherDeduction'=>$otherDeduction, 'GrossSalary'=>$slckey['GrossSalary']??'0.00', 'BasicSalary'=>$slckey['BasicSalary']??'0.00', 'HRA'=>$slckey['HRA']??'0.00', 'Conveyance'=>$slckey['Conveyance']??'0.00', 'Allowances'=>$slckey['Allowances']??'0.00', 'NetSalary'=>$finalNetSalary, 'SalaryID'=>$slckey['SalaryID'], 'SalaryYear'=>$slckey['SalaryYear'], 'SalaryMonth'=>$slckey['SalaryMonth']);

        }
    }
    echo json_encode($payslipdata);
}else if($action=="fetchEmployeeSalaryFromMaster_callback"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $EmployeeId = $_POST['EmployeeId'];
    $empSalary=fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $EmployeeId);
    echo json_encode($empSalary);
}else if($action=="fetchEmployeeAdvanceFromMaster"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $empSalary=fetchEmployeeAdvanceMasterData($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
    echo json_encode($empSalary);
}else if($action=="fetchEmployeeAdvanceDetailsHistory"){
    try {
        $companyid = $_POST['companyid'] ?? '';
        $subcompanyid = $_POST['subcompanyid'] ?? '';
        $projectid = $_POST['projectid'] ?? '';
        $EmployeeID = $_POST['EmployeeID'] ?? '';

        // Allocations
        $allocations = [];
        $stmt1 = $hrmconn->prepare("SELECT AdvanceID, EmployeeID, AdvanceAmount, DeductionPerMonth, AdvanceDate, Remarks, CreatedAt, CreatedAt AS created_at FROM hrm_EmployeeAdvance WHERE EmployeeID = ? ORDER BY AdvanceID ASC");
        if($stmt1){
            $stmt1->bind_param("s", $EmployeeID);
            $stmt1->execute();
            $res1 = $stmt1->get_result();
            while($r = $res1->fetch_assoc()){
                $dateVal = (!empty($r['AdvanceDate']) && $r['AdvanceDate'] != '0000-00-00') ? $r['AdvanceDate'] : ($r['CreatedAt'] ?? '');
                $r['FormattedDate'] = !empty($dateVal) ? date('d-M-Y', strtotime($dateVal)) : '-';
                $r['AdvanceRemark'] = $r['Remarks'] ?? '';
                $allocations[] = $r;
            }
            $stmt1->close();
        }

        // Deductions
        $deductions = [];
        $stmt2 = $hrmconn->prepare("SELECT id, EmployeeID, TransactionDate, DebitAmount, CreditAmount, BalanceAmount, Description, MonthYearSalary, TransactionType, PaymentType, CreatedAt, CreatedAt AS created_at FROM hrm_EmployeeAdvanceSummary WHERE EmployeeID = ? ORDER BY id ASC");
        if($stmt2){
            $stmt2->bind_param("s", $EmployeeID);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            while($r = $res2->fetch_assoc()){
                $dateVal = !empty($r['TransactionDate']) ? $r['TransactionDate'] : (!empty($r['MonthYearSalary']) ? $r['MonthYearSalary'] : ($r['CreatedAt'] ?? ''));
                $r['FormattedDate'] = (!empty($dateVal) && $dateVal != '0000-00-00') ? date('d-M-Y', strtotime($dateVal)) : (!empty($r['MonthYearSalary']) ? $r['MonthYearSalary'] : '-');
                $deductions[] = $r;
            }
            $stmt2->close();
        }

        // Employee Data
        $empData = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, 'EmployeeID', $EmployeeID);
        if (empty($empData)) {
            $altStm = $hrmconn->prepare("SELECT em.*, CONCAT_WS(' ', IFNULL(em.TitleName, ''), IFNULL(em.FirstName, ''), IFNULL(em.MiddleName, ''), IFNULL(em.LastName, '')) AS FullName FROM hrm_EmployeesMaster em WHERE em.EmployeeID = ?");
            if ($altStm) {
                $altStm->bind_param("s", $EmployeeID);
                $altStm->execute();
                $altRes = $altStm->get_result();
                if ($altRow = $altRes->fetch_assoc()) {
                    $empData = [$altRow];
                }
                $altStm->close();
            }
        }
        $empName = !empty($empData) ? trim(($empData[0]['TitleName'] ?? '') . ' ' . ($empData[0]['FirstName'] ?? '') . ' ' . ($empData[0]['MiddleName'] ?? '') . ' ' . ($empData[0]['LastName'] ?? '')) : '';
        if (empty($empName) && !empty($empData[0]['FullName'])) {
            $empName = trim($empData[0]['FullName']);
        }

        echo json_encode([
            'status' => 'success',
            'EmployeeID' => $EmployeeID,
            'EmployeeName' => $empName,
            'allocations' => $allocations,
            'deductions' => $deductions
        ]);
    } catch (Throwable $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}else if($action=="InsertOrUpdateCalculatedAttendance_Payroll"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $salaryyearmonth = $_POST['salaryyearmonth'] ?? null;
    $salaryyearmonthexp = explode("-", $salaryyearmonth);
    $attyear = $salaryyearmonthexp[0];
    $attmonth = $salaryyearmonthexp[1];
    $empattdata = json_decode($_POST['empattdata'], true);
    $loginname = $_POST['loginname'];
    // print_r($empattdata);
    $returnmsg=[];
    if(count($empattdata)>0){
        foreach ($empattdata as $ematkey) {
            $fetcheatt=fetchEmployeeCalculatedAttendance($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['attendanceyear'], $ematkey['attendancemonth'], "EmployeeID", $ematkey['employeeid']);
            if(count($fetcheatt)>0){
                $daysinmonth=getDaysInMonthForSalary($ematkey['attendanceyear'], $ematkey['attendancemonth']);
                
                $workingdaysinmonth = getWorkingDaysInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);
                
                $ttlHolidays = getHoliDaysInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);
                
                $empWeekOffcount = getEmployeeWeekOffCountInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);

                // update claculated attendance
                $stmt = $hrmconn->prepare("UPDATE hrm_EmployeeCalculateAttendance SET WorkingDaysInMonth =?, WeekOffDays = ?, HoliDays = ?, PresentDays = ? WHERE CompanyID = ? AND ProjectID = ? AND EmployeeID = ? AND AttendanceYear = ? AND AttendanceMonth = ?");
                $stmt->bind_param("sssiiiiii", $workingdaysinmonth, $empWeekOffcount, $ttlHolidays, $ematkey['calculatepresentdays'], $ematkey['companyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);
                if ($stmt->execute()) {
                    $returnmsg[] = array("status"=>"Success", "message"=>"✅ Attendance updated successfully!", "employeeid"=>$ematkey['employeeid'], 'employeename'=>$ematkey['employeename']);
                } else {
                    $returnmsg[] = array("status"=>"Error", "message"=>"❌ Error updating attendance: " . $stmt->error, "employeeid"=>$ematkey['employeeid'], 'employeename'=>$ematkey['employeename']);
                }
                $stmt->close();
            }else{
                // insert calculated attendance
                $daysinmonth=getDaysInMonthForSalary($ematkey['attendanceyear'], $ematkey['attendancemonth']);
                
                $workingdaysinmonth = getWorkingDaysInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);
                
                $ttlHolidays = getHoliDaysInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);
                
                $empWeekOffcount = getEmployeeWeekOffCountInMonth($hrmconn, $companyid, $subcompanyid, $projectid, $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);
                
                // Prepare the SQL statement
                $stmt = $hrmconn->prepare("INSERT INTO hrm_EmployeeCalculateAttendance (EmployeeID, CompanyID, SubCompanyID, ProjectID, AttendanceYear, AttendanceMonth, TotalDaysInMonth, WorkingDaysInMonth, WeekOffDays, HoliDays, PresentDays, AbsentDays, TotalApproveLeave, HalfDays, LateArrivals, ExtraWorkingDays, Comments) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                $absentdays = 0;
                $TotalApproveLeave = 0; 
                $HalfDays = 0;
                $LateArrivals = 0;
                $extraDays = 0;
                $Comments = 'Attendance calculated on date '.date('Y-m-d').' by '.$loginname;
                $ttldays= floatval($workingdaysinmonth + $ttlHolidays + $empWeekOffcount);


                if($workingdaysinmonth>$ematkey['calculatepresentdays']){
                    $absentdays = $workingdaysinmonth - $ematkey['calculatepresentdays'];
                }else if($ematkey['calculatepresentdays']>$workingdaysinmonth){
                    $extraDays = $ematkey['calculatepresentdays'] - $workingdaysinmonth;
                }

                // Bind parameters (s = string, i = integer, d = decimal)
                $stmt->bind_param("isssiiiddddddddds", 
                    $ematkey['employeeid'], 
                    $ematkey['companyid'], 
                    $ematkey['subcompanyid'], 
                    $ematkey['projectid'], 
                    $ematkey['attendanceyear'], 
                    $ematkey['attendancemonth'], 
                    $daysinmonth, 
                    $workingdaysinmonth, 
                    $empWeekOffcount,
                    $ttlHolidays, 
                    $ematkey['calculatepresentdays'], 
                    $absentdays, 
                    $TotalApproveLeave, 
                    $HalfDays, 
                    $LateArrivals,
                    $extraDays, 
                    $Comments
                );
                if ($stmt->execute()) {
                    $returnmsg[] = array("status"=>"Success", "message"=>"✅  Attendance record inserted successfully!", "employeeid"=>$ematkey['employeeid'], 'employeename'=>$ematkey['employeename']);
                } else {
                    $returnmsg[] = array("status"=>"Error", "message"=>"❌ Error inserting record: " . $stmt->error, "employeeid"=>$ematkey['employeeid'], 'employeename'=>$ematkey['employeename']);
                }
                $stmt->close();
            }
        }
    }
    echo json_encode($returnmsg);
}else if($action=="checkPendingAttendanceAndLeave"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $yearmonth = $_POST['yearmonth'] ?? null;

    $pendingAtt = 0;
    $pendingLeave = 0;

    if (!empty($yearmonth) && !empty($projectid)) {
        $exp = explode("-", $yearmonth);
        $year = $exp[0];
        $month = sprintf("%02d", intval($exp[1]));
        $startDate = "$year-$month-01";
        $endDate = date('Y-m-t', strtotime($startDate));

        // 1. Check pending attendance requests
        $attSql = "SELECT COUNT(*) as cnt FROM hrm_employeeattendance 
                   WHERE ProjectID = ? 
                     AND (tedit IS NULL OR tedit = '') 
                     AND (ApprovalStatus = 'Pending' OR Status = 'Pending' OR Status = 'Edited') 
                     AND AttendanceDate BETWEEN ? AND ?";
        $stmtAtt = $hrmconn->prepare($attSql);
        if ($stmtAtt) {
            $stmtAtt->bind_param("sss", $projectid, $startDate, $endDate);
            $stmtAtt->execute();
            $resAtt = $stmtAtt->get_result();
            if ($r = $resAtt->fetch_assoc()) {
                $pendingAtt = intval($r['cnt']);
            }
            $stmtAtt->close();
        }

        // 2. Check pending leave applications
        $leaveSql = "SELECT COUNT(*) as cnt FROM hrm_employeeleaves 
                     WHERE ProjectID = ? 
                       AND Status = 'Pending' 
                       AND ((LeaveStartDate BETWEEN ? AND ?) 
                         OR (LeaveEndDate BETWEEN ? AND ?) 
                         OR (LeaveStartDate <= ? AND LeaveEndDate >= ?))";
        $stmtLeave = $hrmconn->prepare($leaveSql);
        if ($stmtLeave) {
            $stmtLeave->bind_param("sssssss", $projectid, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate);
            $stmtLeave->execute();
            $resLeave = $stmtLeave->get_result();
            if ($r = $resLeave->fetch_assoc()) {
                $pendingLeave = intval($r['cnt']);
            }
            $stmtLeave->close();
        }
    }

    echo json_encode([
        "status" => "success",
        "pendingAttendance" => $pendingAtt,
        "pendingLeaves" => $pendingLeave,
        "isReady" => ($pendingAtt == 0 && $pendingLeave == 0)
    ]);
}else if($action=="fetcheEmployeeCalculatedAttendance"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $yearmonth = explode("-", $_POST['yearmonth']);
    $year = $yearmonth[0];
    $month = $yearmonth[1];
    $calattt=calculateMonthlyAttendance_forSalary($hrmconn, $companyid, $subcompanyid, $projectid, $year, $month);
    echo $calattt;
}else if($action=="saveCalculatedAttendance"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $loginname = $_POST['loginname'];
    $loginid = $_POST['loginid'];
    $yearmonth = explode("-", $_POST['yearmonth']);
    $year = $yearmonth[0];
    $month = $yearmonth[1];
    $data = json_decode($_POST['attendanceData'], true);
    $result = saveCalculatedAttendance($hrmconn,$companyid,$subcompanyid,$projectid,$loginid,$loginname,$year,$month,$_POST['attendanceData']);
    
    // In addition to saving attendance, also compute & save initial calculated salary for all employees into hrm_EmployeeCalculatedSalary
    /*if(is_array($data) && count($data) > 0){
        $salaryyearmonth = sprintf("%04d-%02d", intval($year), intval($month));
        $attyear = intval($year);
        $attmonth = intval($month);
        $MonthYearSalary = $attyear . "-" . str_pad($attmonth, 2, "0", STR_PAD_LEFT);

        foreach ($data as $row) {
            $empId = $row['EmployeeID'] ?? null;
            if(empty($empId)) continue;

            $CalculatedPresentDays = (isset($row['TotalPayableDays']) && $row['TotalPayableDays'] !== '' && floatval($row['TotalPayableDays']) >= 0) 
                ? floatval($row['TotalPayableDays']) 
                : floatval($row['CalculatedPresentDays'] ?? 0);
            
            $daysinmonth = intval($row['TotalDaysInMonth'] ?? 0);
            if($daysinmonth <= 0) {
                $daysinmonth = intval(date("t", strtotime($salaryyearmonth . "-01")));
            }

            $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empId);
            if(count($emplist) > 0){
                $eemkey = $emplist[0];
                $empname = trim($eemkey['FirstName'].' '.$eemkey['MiddleName'].' '.$eemkey['LastName']);
                
                $empsl = fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empId, $salaryyearmonth);
                if(empty($empsl)){
                    $empsl = fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empId, null);
                }

                if(count($empsl) > 0){
                    $emkkey = $empsl[0];
                    $PaymentDate = $emkkey['PaymentDate'] ?? "";
                    $CurrencyCode = $emkkey['CurrencyCode'] ?? "INR";
                    $PaymentMethod = $emkkey['PaymentMethod'] ?? "Bank Transfer";

                    $calGross = calculateGrossSalary($emkkey['GrossSalary'], $daysinmonth, $CalculatedPresentDays);
                    $calBasic = calculateBasicSalary($emkkey['BasicSalary'], $daysinmonth, $CalculatedPresentDays);
                    $calHRA = calculateHRASalary($emkkey['HRA'], $daysinmonth, $CalculatedPresentDays);
                    $calConveyance = calculateConveyanceSalary($emkkey['ConveyanceAllowance'], $daysinmonth, $CalculatedPresentDays);
                    $calAllowance = calculateAllowanceSalary($emkkey['SpecialAllowance'], $daysinmonth, $CalculatedPresentDays);

                    // Advance deduction
                    $empAdv = fetchEmployeeAdvanceMasterData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empId);
                    $AdvdeductionPerMonth = floatval($empAdv[0]['DeductionPerMonth'] ?? 0);

                    // Employee deductions
                    $empddct = getEmployeeDeductionsComponents($hrmconn, $companyid, $subcompanyid, $projectid, $empId);
                    $calEmpPFContribution = 0;
                    $calEmpESICContribution = 0;
                    $calEmpTaxDeduction = 0;
                    $calEmpOtherDeduction = 0;
                    $calOvertimePay = 0;
                    $calArrearAmount = 0;
                    $EPFdata = [];
                    $ESICdata = [];
                    $deductionData = [];

                    if(count($empddct) > 0){
                        foreach ($empddct as $empddkey) {
                            $dedId = intval($empddkey['DeductionId']);
                            $deductionAmount = 0;

                            if($dedId == 1){ // PF
                                $pfDetails = calculatePFDetails_Salary($calBasic);
                                $calEmpPFContribution = floatval($pfDetails['employee_contribution'] ?? 0);
                                $deductionAmount = $calEmpPFContribution;
                                if($deductionAmount > 0){
                                    $EPFdata = [
                                        'companyid' => $companyid,
                                        'subcompanyid' => $subcompanyid,
                                        'projectid' => $projectid,
                                        'EmployeeId' => $empId,
                                        'DeductionId' => $dedId,
                                        'DeductionName' => $empddkey['DeductionName'] ?? 'EPF',
                                        'BasicSalary' => $calBasic,
                                        'EmployeePF' => $pfDetails['employee_contribution'],
                                        'EPS' => $pfDetails['eps_contribution'],
                                        'EmployerPF' => $pfDetails['employer_contribution'],
                                        'EDLI' => $pfDetails['edli_contribution'],
                                        'PFAdmin' => $pfDetails['epf_admin_charges'],
                                        'EDLIAdmin' => $pfDetails['edli_admin_charges'],
                                        'SalaryYear' => $attyear,
                                        'SalaryMonth' => $attmonth,
                                        'DeductedFromSalaryId' => ''
                                    ];
                                }
                            } else if($dedId == 3){ // ESIC
                                $esicDetails = calculateESIC_Salary($emkkey['GrossSalary'], $calBasic);
                                $calEmpESICContribution = floatval($esicDetails['employee_contribution'] ?? 0);
                                $deductionAmount = $calEmpESICContribution;
                                if($deductionAmount > 0){
                                    $ESICdata = [
                                        'companyid' => $companyid,
                                        'subcompanyid' => $subcompanyid,
                                        'projectid' => $projectid,
                                        'EmployeeId' => $empId,
                                        'DeductionId' => $dedId,
                                        'DeductionName' => $empddkey['DeductionName'] ?? 'ESIC',
                                        'GrossSalary' => $calGross,
                                        'EmployeeESIC' => $esicDetails['employee_contribution'],
                                        'EmployerESIC' => $esicDetails['employer_contribution'],
                                        'SalaryYear' => $attyear,
                                        'SalaryMonth' => $attmonth,
                                        'DeductedFromSalaryId' => ''
                                    ];
                                }
                            } else if($dedId == 4){ // Tax
                                $calEmpTaxDeduction = 0;
                                $dedType = strtolower($empddkey['EmployeeDeductionType'] ?? '');
                                $dedBase = strtolower($empddkey['EmployeeDeductionBase'] ?? '');
                                if($dedType == "" || $dedType == "fixed"){
                                    $calEmpTaxDeduction = floatval($empddkey['EmployeeDeductionValue'] ?? 0);
                                } else if($dedType == "percentage"){
                                    if($dedBase == "gross"){
                                        $calEmpTaxDeduction = round(($calGross * floatval($empddkey['EmployeeDeductionValue'])) / 100, 2);
                                    } else if($dedBase == "basic"){
                                        $calEmpTaxDeduction = round(($calBasic * floatval($empddkey['EmployeeDeductionValue'])) / 100, 2);
                                    }
                                }
                                $deductionAmount = $calEmpTaxDeduction;
                            } else { // Other
                                $dedBase = strtolower($empddkey['EmployeeDeductionBase'] ?? '');
                                if($dedBase == "gross"){
                                    $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calGross);
                                    $deductionAmount = calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calGross);
                                } else if($dedBase == "basic"){
                                    $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calBasic);
                                    $deductionAmount = calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calBasic);
                                } else {
                                    $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], "");
                                    $deductionAmount = calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], "");
                                }
                            }

                            if($deductionAmount > 0){
                                $deductionData[] = [
                                    'companyid' => $companyid,
                                    'subcompanyid' => $subcompanyid,
                                    'projectid' => $projectid,
                                    'EmployeeId' => $empId,
                                    'DeductionId' => $dedId,
                                    'DeductionName' => $empddkey['DeductionName'] ?? '',
                                    'DeductionType' => $empddkey['EmployeeDeductionType'] ?? '',
                                    'DeductionBase' => $empddkey['EmployeeDeductionBase'] ?? '',
                                    'DeductionValue' => $empddkey['EmployeeDeductionValue'] ?? 0,
                                    'DeductionAmount' => $deductionAmount,
                                    'SalaryYear' => $attyear,
                                    'SalaryMonth' => $attmonth,
                                    'DeductedFromSalaryId' => ''
                                ];
                            }
                        }
                    }

                    $ttldeduction = floatval($calEmpPFContribution + $calEmpESICContribution + $calEmpTaxDeduction + $calEmpOtherDeduction);
                    $grossEarnings = floatval($calBasic + $calHRA + $calConveyance + $calAllowance + $calOvertimePay + $calArrearAmount);
                    $netSalaryBeforeAdv = floatval($grossEarnings - $ttldeduction);

                    if (floatval($AdvdeductionPerMonth) > floatval($netSalaryBeforeAdv)) {
                        $AdvdeductionPerMonth = max(0, $netSalaryBeforeAdv);
                    }
                    $rawNetPayAmount = max(0, floatval($netSalaryBeforeAdv) - floatval($AdvdeductionPerMonth));
                    $roundedNetSalary = round($rawNetPayAmount);
                    $roundOffVal = round($roundedNetSalary - $rawNetPayAmount, 2);

                    $description = 'Salary Processed for the month '.$attyear.'-'.$attmonth;
                    $salaryData = [
                        'EmployeeID' => $empId,
                        'CompanyID' => $companyid,
                        'SubCompanyID' => $subcompanyid,
                        'ProjectID' => $projectid,
                        'SalaryYear' => $attyear,
                        'SalaryMonth' => $attmonth,
                        'GrossSalary' => $calGross,
                        'BasicSalary' => $calBasic,
                        'HRA' => $calHRA,
                        'Conveyance' => $calConveyance,
                        'Allowances' => $calAllowance,
                        'OvertimePay' => $calOvertimePay,
                        'ArrearAmount' => $calArrearAmount,
                        'Deductions' => $ttldeduction,
                        'AdvanceDeduction' => $AdvdeductionPerMonth,
                        'NetSalary' => $roundedNetSalary,
                        'RoundOff' => $roundOffVal,
                        'PaymentDate' => $PaymentDate,
                        'CurrencyCode' => $CurrencyCode,
                        'PaymentMethod' => $PaymentMethod,
                        'Comments' => 'Salary Processed on ' . date("Y-m-d") . ' for month ' . $attyear . '-' . $attmonth
                    ];

                    $jSalaryResult = insertOrUpdateEmployeeCalculatedSalary($hrmconn, $salaryData);
                    $SalaryResult = json_decode($jSalaryResult, true);
                    if(!empty($SalaryResult['SalaryID'])){
                        $salId = intval($SalaryResult['SalaryID']);
                        @$hrmconn->query("UPDATE hrm_EmployeeCalculatedSalary SET NetSalary = '{$roundedNetSalary}', RoundOff = '{$roundOffVal}' WHERE SalaryID = '{$salId}'");

                        if(!empty($deductionData)){
                            foreach ($deductionData as &$ded) {
                                $ded['DeductedFromSalaryId'] = $salId;
                            }
                            unset($ded);
                            insertOrUpdateEmployeeDeductionTransaction($hrmconn, $deductionData);
                        }

                        if(!empty($EPFdata)){
                            $EPFdata['DeductedFromSalaryId'] = $salId;
                            insertOrUpdateEmployeeEPF($hrmconn, $EPFdata);
                        }

                        if(!empty($ESICdata)){
                            $ESICdata['DeductedFromSalaryId'] = $salId;
                            insertOrUpdateEmployeeESIC($hrmconn, $ESICdata);
                        }

                        if($AdvdeductionPerMonth > 0){
                            try {
                                insertEmployeeAdvanceSummary($hrmconn, '', $projectid, $empId, date('Y-m-d'), $AdvdeductionPerMonth, '', '', '', $description, 'Debit', 'AdjustSalary', '', $MonthYearSalary, '', $salId);
                            } catch(Exception $ex) {}
                        }
                    }
                }
            }
        }
    }*/

    echo $result;
}else if($action=="EmplistforCalculateSalaryhtml"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $salaryyearmonth = $_POST['salaryyearmonth'] ?? null;
    $salaryyearmonthexp = explode("-", $salaryyearmonth);
    $attyear = $salaryyearmonthexp[0];
    $attmonth = $salaryyearmonthexp[1];
    // check if already exist salary attendance
    $empatt=fetchEmployeeCalculatedAttendance($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth, "all");
    $daysinmonth=0;
    $workingdaysinmonth =0;    
    $CalculatedPresentDays=0;
    $emplt="";
    $sno=1;
    if(count($empatt)>0){
        foreach ($empatt as $calatkey) {
            // TotalDaysInMonth,WorkingDaysInMonth,WeekOffDays,TotalPayableDays
            $CalculatedPresentDays = (isset($calatkey['TotalPayableDays']) && $calatkey['TotalPayableDays'] !== '' && floatval($calatkey['TotalPayableDays']) >= 0) ? floatval($calatkey['TotalPayableDays']) : floatval($calatkey['CalculatedPresentDays']);
            $daysinmonth = $calatkey['TotalDaysInMonth'];
            $workingdaysinmonth = $calatkey['WorkingDaysInMonth'];
            $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $calatkey['EmployeeID']);
            // print_r($emplist);
            if(count($emplist)>0){
                $empname="";
                foreach ($emplist as $eemkey) {
                    $empname = $eemkey['FirstName'].' '.$eemkey['MiddleName'].' '.$eemkey['LastName'];

                    // checkcalculated Salary
                    $caltdsal = getEmployeeCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth, "EmployeeID", $eemkey['EmployeeID']);
                    $calculatedstatus="";
                    $calst="";
                    if(count($caltdsal)>0){
                        $calculatedstatus="calculated";
                        $calst = "<span class='badge badge-pill badge-success'>calculated</span>";
                    }
                    // employee salary details
                    $empsl=fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $eemkey['EmployeeID'], $salaryyearmonth);
                    if(empty($empsl)){
                        $empsl=fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $eemkey['EmployeeID'], null);
                    }
                    $calGross = 0;
                    $calBasic = 0;
                    $calHRA = 0;
                    $calConveyance = 0;
                    $calAllowance = 0;
                    $calEmpPFContribution = 0;
                    $calEmpESICContribution = 0;
                    $calEmpTaxDeduction = 0;
                    $calEmpOtherDeduction = 0;
                    $calOvertimePay = 0;
                    $calArrearAmount = 0;
                    $ttldeduction =0;
                    $netPayAmount = 0;
                    $AdvdeductionPerMonth=0;
                    $grossSalaryMaster = 0;
                    if(count($empsl)>0){
                        $emkkey = $empsl[0];
                        $grossSalaryMaster = floatval($emkkey['GrossSalary'] ?? 0);
                        $calGross = calculateGrossSalary($emkkey['GrossSalary'], $daysinmonth, $CalculatedPresentDays);
                        $calBasic = calculateBasicSalary($emkkey['BasicSalary'], $daysinmonth, $CalculatedPresentDays);
                        $calHRA = calculateHRASalary($emkkey['HRA'], $daysinmonth, $CalculatedPresentDays);
                        $calConveyance = calculateConveyanceSalary($emkkey['ConveyanceAllowance'], $daysinmonth, $CalculatedPresentDays);
                        $calAllowance = calculateAllowanceSalary($emkkey['SpecialAllowance'], $daysinmonth, $CalculatedPresentDays);

                        // advance agains salary deduction
                        $empSalary=fetchEmployeeAdvanceMasterData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $eemkey['EmployeeID']);
                        
                        $AdvdeductionPerMonth = min($empSalary[0]['NetAdvance'],$empSalary[0]['DeductionPerMonth']);

                        // employee deduction
                        $empddct=getEmployeeDeductionsComponents($hrmconn, $companyid, $subcompanyid, $projectid, $eemkey['EmployeeID']);                            
                        $calEmpPFContribution = 0;
                        $calEmpESICContribution = 0;
                        $calEmpTaxDeduction = 0;
                        $calEmpOtherDeduction = 0;
                        $calOvertimePay = 0;
                        $calArrearAmount = 0;
                        $ttldeduction =0;
                        $netPayAmount = 0;
                        if(count($empddct)>0){
                            foreach ($empddct as $empddkey) {
                                if($empddkey['DeductionId']==1){
                                    $pfDetails = calculatePFDetails_Salary($calBasic);
                                    $calEmpPFContribution = $pfDetails['employee_contribution'];
                                }else if($empddkey['DeductionId']==3){
                                    $esicDetails = calculateESIC_Salary($emkkey['GrossSalary'], $calBasic);

                                    $calEmpESICContribution = $esicDetails['employee_contribution'];
                                }else if($empddkey['DeductionId']==4){
                                    $calEmpTaxDeduction = 0;
                                    if ($empddkey['EmployeeDeductionType'] == "" || strtolower($empddkey['EmployeeDeductionType']) == "fixed") {
                                        $calEmpTaxDeduction = $empddkey['EmployeeDeductionValue'] ?? 0;
                                    }else if($empddkey['EmployeeDeductionType']=="Percentage" || $empddkey['EmployeeDeductionType']=="percentage"){
                                        // echo "txtbse: ".$empddkey['EmployeeDeductionBase'];
                                        if(strtolower($empddkey['EmployeeDeductionBase'])=="gross"){
                                            $calEmpTaxDeduction = round(($calGross * $empddkey['EmployeeDeductionValue'])/100, 2) ?? 0;
                                        }else if(strtolower($empddkey['EmployeeDeductionBase'])=="basic"){
                                            $calEmpTaxDeduction = round(($calBasic * $empddkey['EmployeeDeductionValue'])/100, 2) ?? 0;
                                        }
                                    }
                                }else{
                                    if(strtolower($empddkey['EmployeeDeductionType'])=="gross"){
                                        $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'],$empddkey['EmployeeDeductionValue'],$calGross);
                                    }else if(strtolower($empddkey['EmployeeDeductionType'])=="basic"){
                                        $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'],$empddkey['EmployeeDeductionValue'],$calBasic);
                                    }
                                }
                            }
                        }
                        $ttldeduction = floatval($calEmpPFContribution + $calEmpESICContribution + $calEmpTaxDeduction + $calEmpOtherDeduction);
                        $grossEarnings = floatval($calBasic + $calHRA + $calConveyance + $calAllowance + $calOvertimePay + $calArrearAmount);
                        $netSalaryBeforeAdv = floatval($grossEarnings - $ttldeduction);

                        if (floatval($AdvdeductionPerMonth) > floatval($netSalaryBeforeAdv)) {
                            $AdvdeductionPerMonth = max(0, $netSalaryBeforeAdv);
                        }
                        $rawNetPayAmount = max(0, floatval($netSalaryBeforeAdv) - floatval($AdvdeductionPerMonth));
                        
                        $netPayAmount = round($rawNetPayAmount);
                        $roundOffVal = round($netPayAmount - $rawNetPayAmount, 2);

                    }                    

                    $empDashUrl = 'empPanel/index.php?empid='.$eemkey['EmployeeID'].'&accessby=adminu';
                    $empCombinedLink = '<a href="'.$empDashUrl.'" target="_blank" class="text-decoration-none" title="Click to open Employee Dashboard for attendance edit/correction"><span class="fw-bold text-danger me-1">'.$eemkey['EmployeeID'].'</span> - '.$empname.'</a> <sup>'.$calst.'</sup>';

                    $emplt .= '<tr data-companyid="'.$companyid.'" data-subcompanyid="'.$subcompanyid.'" data-projectid="'.$projectid.'" data-employeeid="'.$eemkey['EmployeeID'].'" data-employeename="'.$empname.'" data-attendanceyear="'.$attyear.'" data-attendancemonth="'.$attmonth.'" data-calculatedstatus="'.$calculatedstatus.'" data-netpayableamount="'.$netPayAmount.'" data-roundoff="'.$roundOffVal.'" data-advdeduction="'.$AdvdeductionPerMonth.'">
                       <td>'.$sno.'</td>
                       <td class="employeename text-start" style="text-align:left !important;">'.$empCombinedLink.'</td>
                       <td class="grosssalary fw-semibold text-dark">'.$grossSalaryMaster.'</td>
                       <td class="presentdays">'.$CalculatedPresentDays.'</td>
                       <td class="basic">'.$calBasic.'</td>
                       <td class="hra">'.$calHRA.'</td>
                       <td class="allowances">'.$calConveyance.'</td>
                       <td class="allowances">'.$calAllowance.'</td>
                       <td class="epfdeduction">'.$calEmpPFContribution.'</td>
                       <td class="esicdeduction">'.$calEmpESICContribution.'</td>
                       <td class="emptaxdeduction">'.$calEmpTaxDeduction.'</td>
                       <td class="advdeduction" contenteditable="true">'.$AdvdeductionPerMonth.'</td>
                       <td class="otherdeduction">'.$calEmpOtherDeduction.'</td>
                       <td class="netpayable">'.$netPayAmount.'</td>
                   </tr>';
                }
            }
            $sno++;
        }
    }else{
        $emplt .= '';
    }
    echo json_encode($emplt);   
}else if($action=="CalculatedSalaryData"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $salaryyearmonth = $_POST['salaryyearmonth'] ?? null;
    $salaryyearmonthexp = explode("-", $salaryyearmonth);
    $attyear = $salaryyearmonthexp[0];
    $attmonth = $salaryyearmonthexp[1];
    $calculatessalary = getEmployeeCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth, 'All', "");
    $calsallist=array();
    if(count($calculatessalary)>0){
        foreach ($calculatessalary as $calsalkey) {
            $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $calsalkey['EmployeeID']);
            if(count($emplist)>0){
                foreach ($emplist as $eemkey) {
                    $EmployeeID = $eemkey['EmployeeID'];
                    $paytoid = $eemkey['paytoid'];
                    $EmployeeName = $eemkey['FirstName'].' '.$eemkey['MiddleName'].' '.$eemkey['LastName'];
                }
            }
            $calsallist[]=array('EmployeeID'=>$EmployeeID, 'EmployeeName'=>$EmployeeName, 'SalaryID'=>$calsalkey['SalaryID'], 'SalaryYear'=>$calsalkey['SalaryYear'], 'SalaryMonth'=>$calsalkey['SalaryMonth'], 'GrossSalary'=>$calsalkey['GrossSalary'], 'BasicSalary'=>$calsalkey['BasicSalary'], 'HRA'=>$calsalkey['HRA'], 'Conveyance'=>$calsalkey['Conveyance'], 'Allowances'=>$calsalkey['Allowances'], 'NetSalary'=>$calsalkey['NetSalary'], 'ReferenceNo'=>$calsalkey['requestno']);
        }
    }
    echo json_encode($calsallist);
}else if($action=="PendingBookFromCalculatedSalaryData"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $salaryyearmonth = $_POST['salaryyearmonth'] ?? null;
    $salaryyearmonthexp = explode("-", $salaryyearmonth);
    $attyear = $salaryyearmonthexp[0];
    $attmonth = $salaryyearmonthexp[1];
    
    $calculatessalary = getEmployeeCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth, 'pendingforbook', "");
    $calsallist=array();
    if(count($calculatessalary)>0){
        foreach ($calculatessalary as $calsalkey) {
            $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $calsalkey['EmployeeID']);
            if(count($emplist)>0){
                foreach ($emplist as $eemkey) {
                    $EmployeeID = $eemkey['EmployeeID'];
                    $paytoid = $eemkey['paytoid'];
                    $EmployeeName = $eemkey['FirstName'].' '.$eemkey['MiddleName'].' '.$eemkey['LastName'];
                }
            }
            $attdata = fetchEmployeeCalculatedAttendance($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth, "EmployeeID", $calsalkey['EmployeeID']);
            $presentDays = $attdata[0]['TotalPayableDays'] ?? 0;

            $calsallist[]=array('EmployeeID'=>$EmployeeID, 'EmployeeName'=>$EmployeeName, 'SalaryID'=>$calsalkey['SalaryID'], 'SalaryYear'=>$calsalkey['SalaryYear'], 'SalaryMonth'=>$calsalkey['SalaryMonth'], 'payableDays'=>$presentDays, 'GrossSalary'=>$calsalkey['GrossSalary'], 'BasicSalary'=>$calsalkey['BasicSalary'], 'HRA'=>$calsalkey['HRA'], 'Conveyance'=>$calsalkey['Conveyance'], 'Allowances'=>$calsalkey['Allowances'], 'NetSalary'=>$calsalkey['NetSalary'], 'ReferenceNo'=>$calsalkey['requestno']);
        }
    }
    echo json_encode($calsallist);
}else if($action=="InsertOrUpdateCalculatedSalary_Payroll"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'] ?? '';
    $subprojectid = 0;
    $salaryyearmonth = $_POST['salaryyearmonth'] ?? null;
    $salaryyearmonthexp = explode("-", $salaryyearmonth);
    $attyear = $salaryyearmonthexp[0];
    $attmonth = $salaryyearmonthexp[1];
    $MonthYearSalary = $attyear . "-" . str_pad($attmonth, 2, "0", STR_PAD_LEFT);
    $empSalaryData = json_decode($_POST['empSalaryData'], true);

    if(is_array($empSalaryData) && count($empSalaryData) > 0){
        $uniqueEmpSalaryData = [];
        $seenEmployees = [];
        foreach ($empSalaryData as $item) {
            $empId = $item['employeeid'] ?? '';
            if (!in_array($empId, $seenEmployees)) {
                $seenEmployees[] = $empId;
                $uniqueEmpSalaryData[] = $item;
            }
        }
        $empSalaryData = $uniqueEmpSalaryData;
    }

    $SalaryResultMsg = [];
    if(is_array($empSalaryData) && count($empSalaryData) > 0){
        foreach ($empSalaryData as $empSalkey) {
            $currentEmpId = $empSalkey['employeeid'] ?? '';
            $currentEmpName = $empSalkey['employeename'] ?? '';

            try {
                $empatt = fetchEmployeeCalculatedAttendance($hrmconn, $companyid, $subcompanyid, $projectid, $attyear, $attmonth, "EmployeeID", $currentEmpId);
                if(empty($empatt)){
                    $SalaryResultMsg[] = [
                        'status' => 'error',
                        'message' => 'Attendance not calculated for this month',
                        'employeeid' => $currentEmpId,
                        'employeename' => $currentEmpName
                    ];
                    continue;
                }

                $calatkey = $empatt[0];
                $CalculatedPresentDays = (isset($calatkey['TotalPayableDays']) && $calatkey['TotalPayableDays'] !== '' && floatval($calatkey['TotalPayableDays']) >= 0) 
                    ? floatval($calatkey['TotalPayableDays']) 
                    : floatval($calatkey['CalculatedPresentDays'] ?? 0);
                
                $daysinmonth = intval($calatkey['TotalDaysInMonth'] ?? 0);
                if($daysinmonth <= 0){
                    $daysinmonth = intval(date("t", strtotime($salaryyearmonth . "-01")));
                }

                $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $currentEmpId);
                if(empty($emplist)){
                    $SalaryResultMsg[] = [
                        'status' => 'error',
                        'message' => 'Employee details not found',
                        'employeeid' => $currentEmpId,
                        'employeename' => $currentEmpName
                    ];
                    continue;
                }

                $eemkey = $emplist[0];
                $empname = trim($eemkey['FirstName'] . ' ' . $eemkey['MiddleName'] . ' ' . $eemkey['LastName']);
                if(empty($currentEmpName)) $currentEmpName = $empname;

                // Employee salary details (with fallback if dated version doesn't exist)
                $empsl = fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $currentEmpId, $salaryyearmonth);
                if(empty($empsl)){
                    $empsl = fetchEmployeeSalaryData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $currentEmpId, null);
                }

                if(empty($empsl)){
                    $SalaryResultMsg[] = [
                        'status' => 'error',
                        'message' => 'Salary structure missing in Employee Master',
                        'employeeid' => $currentEmpId,
                        'employeename' => $currentEmpName
                    ];
                    continue;
                }

                $emkkey = $empsl[0];
                $PaymentDate = $emkkey['PaymentDate'] ?? '';
                $CurrencyCode = $emkkey['CurrencyCode'] ?? 'INR';
                $PaymentMethod = $emkkey['PaymentMethod'] ?? 'Bank Transfer';

                $calGross = calculateGrossSalary($emkkey['GrossSalary'], $daysinmonth, $CalculatedPresentDays);
                $calBasic = calculateBasicSalary($emkkey['BasicSalary'], $daysinmonth, $CalculatedPresentDays);
                $calHRA = calculateHRASalary($emkkey['HRA'], $daysinmonth, $CalculatedPresentDays);
                $calConveyance = calculateConveyanceSalary($emkkey['ConveyanceAllowance'], $daysinmonth, $CalculatedPresentDays);
                $calAllowance = calculateAllowanceSalary($emkkey['SpecialAllowance'], $daysinmonth, $CalculatedPresentDays);

                $AdvanceDeduction = floatval($empSalkey['advanceagainstdeduction'] ?? 0);

                // Employee deductions
                $empddct = getEmployeeDeductionsComponents($hrmconn, $companyid, $subcompanyid, $projectid, $currentEmpId);
                $calEmpPFContribution = 0;
                $calEmpESICContribution = 0;
                $calEmpTaxDeduction = 0;
                $calEmpOtherDeduction = 0;
                $calOvertimePay = 0;
                $calArrearAmount = 0;
                $EPFdata = [];
                $ESICdata = [];
                $deductionData = [];

                if(count($empddct) > 0){
                    foreach ($empddct as $empddkey) {
                        $dedId = intval($empddkey['DeductionId']);
                        $deductionAmount = 0;

                        if($dedId == 1){ // PF
                            $pfDetails = calculatePFDetails_Salary($calBasic);
                            $calEmpPFContribution = floatval($pfDetails['employee_contribution'] ?? 0);
                            $deductionAmount = $calEmpPFContribution;
                            if($deductionAmount > 0){
                                $EPFdata = [
                                    'companyid' => $companyid,
                                    'subcompanyid' => $subcompanyid,
                                    'projectid' => $projectid,
                                    'EmployeeId' => $currentEmpId,
                                    'DeductionId' => $dedId,
                                    'DeductionName' => $empddkey['DeductionName'] ?? 'EPF',
                                    'BasicSalary' => $calBasic,
                                    'EmployeePF' => $pfDetails['employee_contribution'],
                                    'EPS' => $pfDetails['eps_contribution'],
                                    'EmployerPF' => $pfDetails['employer_contribution'],
                                    'EDLI' => $pfDetails['edli_contribution'],
                                    'PFAdmin' => $pfDetails['epf_admin_charges'],
                                    'EDLIAdmin' => $pfDetails['edli_admin_charges'],
                                    'SalaryYear' => $attyear,
                                    'SalaryMonth' => $attmonth,
                                    'DeductedFromSalaryId' => ''
                                ];
                            }
                        } else if($dedId == 3){ // ESIC
                            $esicDetails = calculateESIC_Salary($emkkey['GrossSalary'], $calBasic);
                            $calEmpESICContribution = floatval($esicDetails['employee_contribution'] ?? 0);
                            $deductionAmount = $calEmpESICContribution;
                            if($deductionAmount > 0){
                                $ESICdata = [
                                    'companyid' => $companyid,
                                    'subcompanyid' => $subcompanyid,
                                    'projectid' => $projectid,
                                    'EmployeeId' => $currentEmpId,
                                    'DeductionId' => $dedId,
                                    'DeductionName' => $empddkey['DeductionName'] ?? 'ESIC',
                                    'GrossSalary' => $calGross,
                                    'EmployeeESIC' => $esicDetails['employee_contribution'],
                                    'EmployerESIC' => $esicDetails['employer_contribution'],
                                    'SalaryYear' => $attyear,
                                    'SalaryMonth' => $attmonth,
                                    'DeductedFromSalaryId' => ''
                                ];
                            }
                        } else if($dedId == 4){ // Tax
                            $calEmpTaxDeduction = 0;
                            $dedType = strtolower($empddkey['EmployeeDeductionType'] ?? '');
                            $dedBase = strtolower($empddkey['EmployeeDeductionBase'] ?? '');
                            if($dedType == "" || $dedType == "fixed"){
                                $calEmpTaxDeduction = floatval($empddkey['EmployeeDeductionValue'] ?? 0);
                            } else if($dedType == "percentage"){
                                if($dedBase == "gross"){
                                    $calEmpTaxDeduction = round(($calGross * floatval($empddkey['EmployeeDeductionValue'])) / 100, 2);
                                } else if($dedBase == "basic"){
                                    $calEmpTaxDeduction = round(($calBasic * floatval($empddkey['EmployeeDeductionValue'])) / 100, 2);
                                }
                            }
                            $deductionAmount = $calEmpTaxDeduction;
                        } else { // Other
                            $dedBase = strtolower($empddkey['EmployeeDeductionBase'] ?? '');
                            if($dedBase == "gross"){
                                $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calGross);
                                $deductionAmount = calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calGross);
                            } else if($dedBase == "basic"){
                                $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calBasic);
                                $deductionAmount = calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], $calBasic);
                            } else {
                                $calEmpOtherDeduction += calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], "");
                                $deductionAmount = calculateOtherDeduction_Salary($empddkey['EmployeeDeductionType'], $empddkey['EmployeeDeductionValue'], "");
                            }
                        }

                        if($deductionAmount > 0){
                            $deductionData[] = [
                                'companyid' => $companyid,
                                'subcompanyid' => $subcompanyid,
                                'projectid' => $projectid,
                                'EmployeeId' => $currentEmpId,
                                'DeductionId' => $dedId,
                                'DeductionName' => $empddkey['DeductionName'] ?? '',
                                'DeductionType' => $empddkey['EmployeeDeductionType'] ?? '',
                                'DeductionBase' => $empddkey['EmployeeDeductionBase'] ?? '',
                                'DeductionValue' => $empddkey['EmployeeDeductionValue'] ?? 0,
                                'DeductionAmount' => $deductionAmount,
                                'SalaryYear' => $attyear,
                                'SalaryMonth' => $attmonth,
                                'DeductedFromSalaryId' => ''
                            ];
                        }
                    }
                }

                $ttldeduction = floatval($calEmpPFContribution + $calEmpESICContribution + $calEmpTaxDeduction + $calEmpOtherDeduction);
                $grossEarnings = floatval($calBasic + $calHRA + $calConveyance + $calAllowance + $calOvertimePay + $calArrearAmount);
                $netSalaryBeforeAdv = floatval($grossEarnings - $ttldeduction);

                if (floatval($AdvanceDeduction) > floatval($netSalaryBeforeAdv)) {
                    $AdvanceDeduction = max(0, $netSalaryBeforeAdv);
                }
                $rawNetPayAmount = max(0, floatval($netSalaryBeforeAdv) - floatval($AdvanceDeduction));
                $roundedNetSalary = round($rawNetPayAmount);
                $roundOffVal = round($roundedNetSalary - $rawNetPayAmount, 2);

                $description = 'Salary Processed for the month ' . $attyear . '-' . $attmonth;
                $salaryData = [
                    'EmployeeID' => $currentEmpId,
                    'CompanyID' => $companyid,
                    'SubCompanyID' => $subcompanyid,
                    'ProjectID' => $projectid,
                    'SalaryYear' => $attyear,
                    'SalaryMonth' => $attmonth,
                    'GrossSalary' => $calGross,
                    'BasicSalary' => $calBasic,
                    'HRA' => $calHRA,
                    'Conveyance' => $calConveyance,
                    'Allowances' => $calAllowance,
                    'OvertimePay' => $calOvertimePay,
                    'ArrearAmount' => $calArrearAmount,
                    'Deductions' => $ttldeduction,
                    'AdvanceDeduction' => $AdvanceDeduction,
                    'NetSalary' => $roundedNetSalary,
                    'RoundOff' => $roundOffVal,
                    'PaymentDate' => $PaymentDate,
                    'CurrencyCode' => $CurrencyCode,
                    'PaymentMethod' => $PaymentMethod,
                    'Comments' => 'Salary Processed for the month ' . $attyear . '-' . $attmonth
                ];

                $jSalaryResult = insertOrUpdateEmployeeCalculatedSalary($hrmconn, $salaryData);
                $SalaryResult = json_decode($jSalaryResult, true);

                if(!empty($SalaryResult['SalaryID'])){
                    $salId = intval($SalaryResult['SalaryID']);
                    @$hrmconn->query("UPDATE hrm_EmployeeCalculatedSalary SET NetSalary = '{$roundedNetSalary}', RoundOff = '{$roundOffVal}' WHERE SalaryID = '{$salId}'");

                    if(!empty($deductionData)){
                        foreach ($deductionData as &$ded) {
                            $ded['DeductedFromSalaryId'] = $salId;
                        }
                        unset($ded);
                        insertOrUpdateEmployeeDeductionTransaction($hrmconn, $deductionData);
                    }

                    if(!empty($EPFdata)){
                        $EPFdata['DeductedFromSalaryId'] = $salId;
                        insertOrUpdateEmployeeEPF($hrmconn, $EPFdata);
                    }

                    if(!empty($ESICdata)){
                        $ESICdata['DeductedFromSalaryId'] = $salId;
                        insertOrUpdateEmployeeESIC($hrmconn, $ESICdata);
                    }

                    if($AdvanceDeduction > 0){
                        try {
                            insertEmployeeAdvanceSummary($hrmconn, '', $projectid, $currentEmpId, date('Y-m-d'), $AdvanceDeduction, '', '', '', $description, 'Debit', 'AdjustSalary', '', $MonthYearSalary, '', $salId);
                        } catch(Exception $ex) {}
                    }
                }

                $SalaryResultMsg[] = [
                    'status' => $SalaryResult['status'] ?? 'success',
                    'message' => $SalaryResult['message'] ?? 'Salary processed successfully.',
                    'employeeid' => $currentEmpId,
                    'employeename' => $currentEmpName
                ];

            } catch (Throwable $e) {
                $SalaryResultMsg[] = [
                    'status' => 'error',
                    'message' => 'Exception: ' . $e->getMessage(),
                    'employeeid' => $currentEmpId,
                    'employeename' => $currentEmpName
                ];
            }
        }
    }

    echo json_encode($SalaryResultMsg);
}else if($action=="CreateBookFromCalculatedSalaryData"){
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'];
    $loginid = $_POST['loginid'];
    $subprojectid=0;
    $empSalaryData = json_decode($_POST['empSalaryData'], true);
    // print_r($empSalaryData);
    // die();
    $rrmsg=array();
    if(count($empSalaryData)>0){
        foreach ($empSalaryData as $sstkey) {
            $emplist = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $sstkey['employeeid']);
            $empname="";
            $paytoid="";
            if(count($emplist)>0){
                $breakdowndata=array();
                foreach ($emplist as $eemkey) {
                    $EmployeeID = $eemkey['EmployeeID'];
                    $EmpEmail = $eemkey['Email'];
                    $EmpMobileNo = $eemkey['MobileNo'];
                    $empname = $eemkey['FirstName'].' '.$eemkey['MiddleName'].' '.$eemkey['LastName'];
                    if($eemkey['paytoid']){
                        $paytoid = $eemkey['paytoid'];
                    }else{
                        $empBank=getEmployeeBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $sstkey['employeeid']);
                        $BankName = $empBank[0]['BankName']??'';
                        $AccountNumber = $empBank[0]['AccountNumber']??'';
                        $IFSCCode = $empBank[0]['IFSCCode']??'';
                        $SWIFTCode = $empBank[0]['SWIFTCode']??'';
                        $IBAN = $empBank[0]['IBAN']??'';
                        $SortCode = $empBank[0]['SortCode']??'';
                        $paytoin = savetodbnewpayto_hrm($conn,$empname,$AccountNumber,$BankName,$IFSCCode,$IBAN,$SWIFTCode,$SortCode,$companyid,$subcompanyid,$projectid,$EmpMobileNo,$EmpEmail,$loginname);
                        $paytoid = $paytoin[0]['paytoid'];
                        if($paytoid){
                            $hrmconn->query("UPDATE hrm_EmployeesMaster SET paytoid='".$paytoid."' WHERE (tedit IS NULL OR tedit = '') AND companyid = '".$companyid."' AND subcompanyid = '".$subcompanyid."' AND projectid = '".$projectid."' and EmployeeID='".$EmployeeID."'");
                        }
                    }
                }
            }

            $lastinserted = getEmployeeCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid,$sstkey['salaryyear'], $sstkey['salarymonth'], 'EmployeeID', $sstkey['employeeid']);
            // print_r($lastinserted);
            $NetSalary = $lastinserted[0]['NetSalary'];
            $SalaryID = $lastinserted[0]['SalaryID'];
            $TextRemark = $lastinserted[0]['Comments'];
            // provided code is 3.16
            // clientprovided_purposecode
            $ppt = getpurposenamewithcat_hrm($mconn,$companyid,$subcompanyid,$projectid,"clientprovided_purposecode","3.16");
            $purposecode = $ppt[0]['purposecode'];

            $payslipdoc="";
            $breakdowndata[]=array('beneficiaryname'=>$empname, 'beneficiaryamount'=>$NetSalary, 'purposecode'=>$purposecode??'', 'textremark'=>$TextRemark, 'document'=>$payslipdoc);
            // print_r($breakdowndata); 
            $transaction_type = "Online";
            if($NetSalary>0 && $empname){
                $sspp=reconstructBookFromSalary($mconn, $companyid, $subcompanyid, $projectid, $subprojectid, $NetSalary, $paytoid, $EmployeeID, $transaction_type, $breakdowndata, $loginname);
                if($sspp['status']=="success"){
                    $hrmconn->query("UPDATE hrm_EmployeeCalculatedSalary SET requestno='".$sspp['referenceno']."' where CompanyID='".$companyid."' and SubCompanyID='".$subcompanyid."' and ProjectID='".$projectid."' and EmployeeID='".$EmployeeID."' and SalaryYear='".$sstkey['salaryyear']."' and SalaryMonth='".$sstkey['salarymonth']."'");
                    $rrmsg[]=array('status'=>'success', 'message'=>'<br/>Salary book has been created for transfer the salary to the employee '.$empname);
                }else{
                    $rrmsg[]=array('status'=>'error', 'message'=>'<br/>Salary book was not created for the salary transfer of employee '.$empname);
                }
            }else{
                $rrmsg[]=array('status'=>'error', 'message'=>'<br/>Some details are missing or incorrect for creating a book entry to transfer the employee’s salary. '.$empname);
            }
        }
    }else{
        $rrmsg[]=array('status'=>'error', 'message'=>'Data not available');
    }
    echo json_encode($rrmsg);
}else if($action=="EmployeeSalarySlipCreat"){
    $SalaryId = $_POST['SalaryId'];
    $ESQ = "SELECT * FROM hrm_EmployeeCalculatedSalary WHERE SalaryID = ?";
    $esstm = $hrmconn->prepare($ESQ);
    $payableDays=0;
    $companyname ="";
    $projectname ="";
    $rtmsg=array();
    if ($esstm) {
        $esstm->bind_param('i', $SalaryId);
        if ($esstm->execute()) {
            $result = $esstm->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                // Attendance details
                $att=fetchEmployeeCalculatedAttendance($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['SalaryYear'], $row['SalaryMonth'], "EmployeeID", $row['EmployeeID']);
                $payableDays = (isset($att[0]['TotalPayableDays'])) ? str_replace(".00", "", $att[0]['TotalPayableDays']) : 0;
                // company details
                $cmpCompanyId = $row['CompanyID'] ?? '';
                $cmpprj = getprojectdetailwithcmpsubcmp($hrmconn, $row['CompanyID'], $row['SubCompanyID'], "withauthrizedprojects", $row['ProjectID']);
                $companyname = $cmpprj[0]['companyname'] ?? '';
                $projectname = $cmpprj[0]['projectname'] ?? '';

                // employeeInformation
                $eDkey = fetchEmployeeData($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], 'EmployeeID', $row['EmployeeID']);
                if(empty($eDkey)){
                    $altStm = $hrmconn->prepare("SELECT em.*, CONCAT_WS(' ', IFNULL(em.TitleName, ''), IFNULL(em.FirstName, ''), IFNULL(em.MiddleName, ''), IFNULL(em.LastName, '')) AS FullName FROM hrm_EmployeesMaster em WHERE em.EmployeeID = ?");
                    if ($altStm) {
                        $altStm->bind_param("s", $row['EmployeeID']);
                        $altStm->execute();
                        $altRes = $altStm->get_result();
                        if ($altRow = $altRes->fetch_assoc()) {
                            $eDkey = [$altRow];
                        }
                        $altStm->close();
                    }
                }
                $EmployeeName="";
                $GenderName="";
                $DateOfJoining="";
                $EmploymentTypeid="";
                $DesignationID="";
                $DepartmentID="";
                $Email="";
                if(count($eDkey)>0){
                    $EmployeeName=$eDkey[0]['TitleName'].' '.$eDkey[0]['FirstName'].' '.$eDkey[0]['MiddleName'].' '.$eDkey[0]['LastName'];
                    $GenderName=$eDkey[0]['Gender'] ?? '';
                    $DateOfJoining="N/A";
                    if(!empty($eDkey[0]['DateOfJoining'])){
                        $DateOfJoining=date('d-M-Y', strtotime($eDkey[0]['DateOfJoining']));
                    }
                    $EmploymentTypeid=$eDkey[0]['EmploymentTypeid'] ?? '';
                    $Email=$eDkey[0]['Email'] ?? '';
                    
                    $DesignationID=$eDkey[0]['DesignationID'] ?? '';
                    $DesignationName="N/A";
                    if(!empty($eDkey[0]['DesignationID'])){
                        $DsN=fetchDesignationsMaster($hrmconn, $cmpCompanyId, 'DesignationID', $eDkey[0]['DesignationID']);
                        $DesignationName = $DsN[0]['DesignationName'] ?? 'N/A'; 
                    }
                    $DepartmentID=$eDkey[0]['DepartmentID'] ?? '';
                    $DepartmentName="N/A";
                    if(!empty($eDkey[0]['DepartmentID'])){
                        $DpI=fetchDepartmentMaster($hrmconn, $cmpCompanyId, 'DepartmentID', $eDkey[0]['DepartmentID']);
                        $DepartmentName=$DpI[0]['DepartmentName'] ?? 'N/A';
                    }

                    $EmpDdct = getEmployeeSalaryDeductions($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['EmployeeID'], $row['SalaryYear'], $row['SalaryMonth']);
                    $EPF=0;
                    $ESIC=0;
                    $INCOMTAX=0;
                    $OtherDeduction=0;
                    if(count($EmpDdct)>0){
                        foreach ($EmpDdct as $ddRow) {
                            if($ddRow['DeductionId']==1){
                                $EPF = $ddRow['DeductionAmount'];
                            }else if($ddRow['DeductionId']==3){
                                $ESIC = $ddRow['DeductionAmount'];
                            }else if($ddRow['DeductionId']==4){
                                $INCOMTAX = $ddRow['DeductionAmount'];
                            }else{
                                $OtherDeduction += $ddRow['DeductionAmount'];
                            }
                        }
                    }

                    $EmployeeData = array('CompanyID'=>$row['CompanyID'], 'ProjectID'=>$row['ProjectID'], 'EmployeeID'=>$row['EmployeeID'], 'EmployeeName'=>$EmployeeName,'GenderName'=>$GenderName,'DateOfJoining'=>$DateOfJoining,'DesignationName'=>$DesignationName,'DepartmentName'=>$DepartmentName,'SalaryYear'=>$row['SalaryYear'], 'SalaryMonth'=>$row['SalaryMonth'], 'GrossSalary'=>$row['GrossSalary'], 'BasicSalary'=>$row['BasicSalary'], 'HRA'=>$row['HRA'], 'Conveyance'=>$row['Conveyance'], 'Allowances'=>$row['Allowances'], 'OvertimePay'=>$row['OvertimePay'], 'ArrearAmount'=>$row['ArrearAmount'], 'EPF'=>$EPF, 'ESIC'=>$ESIC, 'INCOMTAX'=>$INCOMTAX, 'OtherDeduction'=>$OtherDeduction, 'NetSalary'=>$row['NetSalary'], 'WorkedDays'=>$payableDays);
                    $outputFolder = "../TempPaySlip";
                    if (!is_dir($outputFolder)) {
                        mkdir($outputFolder, 0777, true);
                    }
                    $headerImage="";
                    $footerImage="";
                    $outputFilePath = $outputFolder."/".$row['ProjectID'].'_'.$row['EmployeeID'].'_'.$row['SalaryYear']."_".$row['SalaryMonth'].".pdf";
                    try {
                        createEmployeePaySlipWithHeaderFooter($outputFilePath,$companyname,$projectname,$EmployeeData);
                        $rtmsg = array('status'=>'Success', 'message'=>'Payslip generated', 'filepath'=>str_replace("../", "", $outputFilePath));
                    } catch (Exception $e) {
                        $rtmsg = array('status'=>'Error', 'message'=>'Failed to generate payslip PDF: ' . $e->getMessage(), 'filepath'=>'');
                    }
                } else {
                    $rtmsg = array('status'=>'Error', 'message'=>'Employee record not found for Employee ID: ' . $row['EmployeeID'], 'filepath'=>'');
                }
            } else {
                $rtmsg = array('status'=>'Error', 'message'=>'No salary record found for Salary ID: ' . $SalaryId, 'filepath'=>'');
            }
        } else {
            $rtmsg = array('status'=>'Error', 'message'=>"Query execution failed: " . $esstm->error, 'filepath'=>'');
        }
        $esstm->close(); // Close statement
    } else {
        $rtmsg = array('status'=>'Error', 'message'=>"Statement preparation failed: " . $hrmconn->error, 'filepath'=>'');
    }
    echo json_encode($rtmsg);
}else if($action=="RemoveFileFromPath"){
    if(isset($_POST['filePath'])) {
        $filePath = "../".$_POST['filePath'];
        if ($filePath && file_exists($filePath)) {
            if (unlink($filePath)) {
                echo json_encode(["status" => "success", "message" => "File deleted successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to delete file."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "File not found. =" .$filePath]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No file path provided."]);
    }
}else if($action=="GetEPFreporthtml"){
    $CompanyID = $_POST['companyid'];
    $SubCompanyID = $_POST['subcompanyid'];
    $ProjectID = $_POST['projectid'];
    $SalaryYear = $_POST['salaryyear'];
    $SalaryMonth = $_POST['salarymonth'];
    $html = getEPFReportHTML($conn, $hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
    echo $html; // or return $html in API response
}else if($action=="GetSalaryreporthtml"){
    $CompanyID = $_POST['companyid'];
    $SubCompanyID = $_POST['subcompanyid'];
    $ProjectID = $_POST['projectid'];
    $SalaryYear = $_POST['salaryyear'];
    $SalaryMonth = $_POST['salarymonth'];
    $html = getSalaryReportHTML($conn, $hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
    echo $html; // or return $html in API response
}else if($action=="GetEPFChallanText"){
    $CompanyID = $_POST['companyid'];
    $SubCompanyID = $_POST['subcompanyid'];
    $ProjectID = $_POST['projectid'];
    $SalaryYear = $_POST['salaryyear'];
    $SalaryMonth = $_POST['salarymonth'];

    $EPFTRAN = getEPFTransactions($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
    $lines = [];
    if (count($EPFTRAN) > 0) {
        foreach ($EPFTRAN as $epdkey) {
            $empId = $epdkey['EmployeeId'];
            $pf_uanno = getEmployeeDeductionRegId($hrmconn, $CompanyID, $ProjectID, $empId, 1);
            $EmpData = fetchEmployeeData($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, 'EmployeeID', $empId);
            $empName = !empty($EmpData) ? $EmpData[0]['FullName'] : '';

            $sldata = getEmployeeCalculatedSalary($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, 'employeeid', $empId);
            $payable_gross = $sldata[0]['GrossSalary'] ?? 0;
            $EPF_Wages = $epdkey['EmployeePF'] ?? 0;
            $EPS_Wages = $epdkey['EPS'] ?? 0;
            $EDLI_Wages = $epdkey['EDLI'] ?? 0;
            $EE_Share_Remitted = round($epdkey['EmployeePF'] ?? 0);
            $EPS_Contribution = round($epdkey['EPS'] ?? 0);
            $ER_Share_Remitted = round($epdkey['EmployerPF'] ?? 0);
            $NCP_Days = getNCPDays($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $empId, $SalaryYear, $SalaryMonth);
            $Refunds = 0;

            $lines[] = $pf_uanno . "#~#" . strtoupper($empName) . "#~#" . $payable_gross . "#~#" . $EPF_Wages . "#~#" . $EPS_Wages . "#~#" . $EDLI_Wages . "#~#" . $EE_Share_Remitted . "#~#" . $EPS_Contribution . "#~#" . $ER_Share_Remitted . "#~#" . $NCP_Days . "#~#" . $Refunds;
        }
    }

    echo json_encode([
        'filename' => "{$ProjectID}_{$SalaryYear}_{$SalaryMonth}_EPF_Challan.txt",
        'content' => implode("\r\n", $lines)
    ]);
    exit;
}else if($action == "AddUpdateAdvnceApprovalDetails"){
    try {
        // Start transaction (use hrmconn for hrm_EmployeeAdvance, main conn for request)
        $hrmconn->begin_transaction();
        $conn->begin_transaction();

        // Fetch POST data safely
        $CompanyID    = $_POST['companyid'] ?? null;
        $SubCompanyID = $_POST['subcompanyid'] ?? null;
        $ProjectID    = $_POST['projectid'] ?? null;
        $LoginId      = $_POST['loginid'] ?? null;
        $loginname    = $_POST['loginname'] ?? '';

        // Fetched Employee details
        $EmpD = fetchEmployeeData(
            $hrmconn,
            $CompanyID,
            $SubCompanyID,
            $ProjectID,
            "EmployeeID",
            $_POST['EmployeeID'] ?? null
        );
        $paytoid = $EmpD[0]['paytoid'] ?? null;

        // Get purpose code
        $ssqp = $conn->query("SELECT purposecode 
                              FROM purpose_code_master 
                              WHERE projectid='" . $conn->real_escape_string($ProjectID) . "' 
                              AND TRIM(provided_code) in ('42.00','42.01')");
        $purposecode = "";
        if ($ssqp && $ssqp->num_rows > 0) {
            $prow = $ssqp->fetch_assoc();
            $purposecode = $prow['purposecode'];
        }

        // Prepare advance data
        $advanceData = [
            "ProjectID"        => $ProjectID,
            "AdvanceID"        => $_POST['AdvanceID'] ?? null,
            "EmployeeID"       => $_POST['EmployeeID'] ?? null,
            "AdvanceDate"      => !empty($_POST['AdvanceDate']) ? $_POST['AdvanceDate'] : date('Y-m-d'),
            "AdvanceAmount"    => floatval($_POST['AdvanceAmount'] ?? 0),
            "DeductionPerMonth"=> floatval($_POST['DeductionPerMonth'] ?? 0),        
            "Purpose"          => $purposecode ?? '',
            "ApprovedBy"       => $LoginId,
            "Remarks"          => $_POST['AdvanceRemark'] ?? '',
            "Status"           => $_POST['Status'] ?? 'Pending'
        ];

        if ($purposecode) {
            // Save or update advance
            $result = saveEmployeeAdvance($hrmconn, $advanceData);

            if (!empty($result['status']) && strtolower($result['status']) === "success" && !empty($paytoid)) {

                $modeoftransfer = "Online";

                $reqadv = CreateRequestforEmployeeAdvance(
                    $conn,
                    $hrmconn,
                    $CompanyID,
                    $SubCompanyID,
                    $ProjectID,
                    "",
                    $paytoid,
                    $modeoftransfer,
                    $advanceData['AdvanceAmount'],
                    $advanceData['Remarks'],
                    $purposecode ?? '',
                    $loginname,
                    "",
                    "Pre-Payment",
                    $advanceData['AdvanceDate']
                );

                // ✅ If request fails -> rollback both
                if (empty($reqadv['status']) || strtolower($reqadv['status']) !== 'success') {
                    $hrmconn->rollback();
                    $conn->rollback();
                    echo json_encode([
                        "status" => "Error",
                        "message" => "Advance saved but request creation failed. Rolled back."
                    ]);
                    exit;
                }else{
                    if (!empty($reqadv['requestno']) && !empty($result['advanceid'])) {
                        $hrmconn->query("UPDATE hrm_EmployeeAdvance SET request_no='" . $hrmconn->real_escape_string($reqadv['requestno']) . "' WHERE AdvanceID='" . $hrmconn->real_escape_string($result['advanceid']) . "'");
                    }
                }

                // ✅ Both succeeded -> commit
                $hrmconn->commit();
                $conn->commit();

                echo json_encode($reqadv);
                exit;
            } else {
                // Advance insert/update failed
                $hrmconn->rollback();
                $conn->rollback();

                echo json_encode($result);
                exit;
            }
        } else {
            echo json_encode([
                "status" => "Error",
                "message" => "Advance Against Purpose not defined."
            ]);
            exit;
        }

    } catch (Exception $e) {
        // Rollback on fatal error
        if ($hrmconn && $hrmconn->errno) $hrmconn->rollback();
        if ($conn && $conn->errno) $conn->rollback();

        echo json_encode([
            "status" => "Error",
            "message" => "Transaction failed: " . $e->getMessage()
        ]);
        exit;
    }
} else if ($action == "getEmployeeDailyAttendanceDetail") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $employeeid = $_POST['employeeid'] ?? null;
    $yearmonth = $_POST['yearmonth'] ?? null;

    if (!$companyid || !$projectid || !$employeeid || !$yearmonth) {
        echo json_encode(["status" => "error", "message" => "Missing parameters"]);
        exit;
    }

    $ymParts = explode("-", $yearmonth);
    $year = $ymParts[0];
    $month = $ymParts[1];
    $firstDay = $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-01";
    $lastDay = date("Y-m-t", strtotime($firstDay));
    $TotalDaysInMonth = intval(date("t", strtotime($firstDay)));

    // Get employee name & scheduled shift InTime
    $empStmt = $hrmconn->prepare("SELECT FirstName, LastName, InTime FROM hrm_EmployeesMaster WHERE EmployeeID=?");
    $empStmt->bind_param("i", $employeeid);
    $empStmt->execute();
    $empRow = $empStmt->get_result()->fetch_assoc();
    $empName = trim(($empRow['FirstName'] ?? '') . " " . ($empRow['LastName'] ?? ''));
    $scheduledInTime = !empty($empRow['InTime']) ? date('H:i:s', strtotime($empRow['InTime'])) : '09:00:00';
    $empStmt->close();

    // Get shift hours
    $shiftData = getShiftHours($hrmconn, $employeeid);
    $FULL_DAY_HOURS = convertTimeToDecimal($shiftData['full']);
    $HALF_DAY_HOURS = convertTimeToDecimal($shiftData['half']);

    // Attendance records with check-in/out times
    $attSql = "SELECT AttendanceDate, WorkingHours, CheckInTime, 
               TIME_FORMAT(CheckInTime, '%H:%i') AS InTime, 
               TIME_FORMAT(CheckOutTime, '%H:%i') AS OutTime,
               CheckOutTime
               FROM hrm_EmployeeAttendance 
               WHERE EmployeeID=? AND ProjectID=? 
               AND (Status IN ('Present') OR ApprovalStatus = 'Approved') 
               AND (ApprovalStatus IS NULL OR ApprovalStatus != 'Rejected')
               AND AttendanceDate BETWEEN ? AND ?";
    $attStmt = $hrmconn->prepare($attSql);
    $attStmt->bind_param("iiss", $employeeid, $projectid, $firstDay, $lastDay);
    $attStmt->execute();
    $attRes = $attStmt->get_result();

    $attendance = [];
    $attTimes = [];
    $rawCheckIns = [];
    $hasMissingMarkout = [];
    while ($a = $attRes->fetch_assoc()) {
        $date = $a['AttendanceDate'];
        $attendance[$date] = ($attendance[$date] ?? 0) + convertTimeToDecimal($a['WorkingHours']);
        $attTimes[$date] = [
            "intime" => $a['InTime'] ?? '-',
            "outtime" => $a['OutTime'] ?? '-'
        ];
        if (!empty($a['CheckInTime'])) {
            $rawCheckIns[$date] = date('H:i:s', strtotime($a['CheckInTime']));
        }
        if (empty($a['CheckOutTime'])) {
            $hasMissingMarkout[$date] = true;
        }
    }
    $attStmt->close();

    // Leaves (Only Approved & Paid Leaves are counted)
    $leaveDates = [];
    $lSql = "SELECT el.LeaveStartDate, el.LeaveEndDate 
             FROM hrm_EmployeeLeaves el
             LEFT JOIN hrm_PayrollLeaveTypeMaster lt 
                    ON (el.LeaveType = lt.leave_code OR el.LeaveType = lt.leave_name) 
                   AND lt.companyid = el.CompanyID
             WHERE el.EmployeeID=? 
               AND el.Status='Approved' 
               AND (lt.status IS NULL OR lt.status != 'inactive') 
               AND (lt.tedit IS NULL OR lt.tedit = '')
               AND (LOWER(lt.leave_type) != 'unpaid' OR lt.leave_type IS NULL OR lt.leave_type = '')";
    $lStmt = $hrmconn->prepare($lSql);
    $lStmt->bind_param("i", $employeeid);
    $lStmt->execute();
    $lRes = $lStmt->get_result();
    while ($lv = $lRes->fetch_assoc()) {
        $start = strtotime($lv['LeaveStartDate']);
        $end = strtotime($lv['LeaveEndDate']);
        while ($start <= $end) {
            $leaveDates[] = date("Y-m-d", $start);
            $start = strtotime("+1 day", $start);
        }
    }
    $lStmt->close();

    // Holidays
    $holidays = [];
    $hSql = "SELECT HolidayDate FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit='') AND companyid=? AND subcompanyid=? AND projectid=?";
    $hStmt = $hrmconn->prepare($hSql);
    $hStmt->bind_param("iii", $companyid, $subcompanyid, $projectid);
    $hStmt->execute();
    $hRes = $hStmt->get_result();
    while ($h = $hRes->fetch_assoc()) {
        $holidays[] = $h['HolidayDate'];
    }
    $hStmt->close();

    // WeekOffs
    $weekOffDays = [];
    $wSql = "SELECT WeekOffDayNo FROM hrm_EmployeeWeekOff WHERE EmployeeID=? AND ProjectID=? AND lcase(Status)='active'";
    $wStmt = $hrmconn->prepare($wSql);
    $wStmt->bind_param("ii", $employeeid, $projectid);
    $wStmt->execute();
    $wRes = $wStmt->get_result();
    while ($w = $wRes->fetch_assoc()) {
        $weekOffDays[] = $w['WeekOffDayNo'];
    }
    $wStmt->close();

    // Build daily detail
    $dailyData = [];
    $summary = ["present" => 0, "half" => 0, "absent" => 0, "leave" => 0, "weekoff" => 0, "holiday" => 0];

    for ($d = strtotime($firstDay); $d <= strtotime($lastDay); $d = strtotime("+1 day", $d)) {
        $date = date("Y-m-d", $d);
        $dow = date("N", $d);
        $dayName = date("D", $d);

        $isLeave = in_array($date, $leaveDates);
        $isHoliday = in_array($date, $holidays);
        $isWeekoff = in_array($dow, $weekOffDays);
        $workedHours = $attendance[$date] ?? 0;
        $inTime = $attTimes[$date]['intime'] ?? '-';
        $outTime = $attTimes[$date]['outtime'] ?? '-';

        $status = "Absent";
        $statusClass = "danger";

        if ($isLeave) {
            $status = "Leave";
            $statusClass = "warning";
            $summary["leave"]++;
        } else if (isset($hasMissingMarkout[$date])) {
            $status = "Absent";
            $statusClass = "danger";
            $summary["absent"]++;
        } else if ($workedHours > 0) {
            if ($workedHours >= $FULL_DAY_HOURS) {
                $status = "Present";
                $statusClass = "success";
                $summary["present"]++;
            } else if ($workedHours >= $HALF_DAY_HOURS) {
                $status = "Half Day";
                $statusClass = "info";
                $summary["half"]++;
            } else {
                $status = "Absent";
                $statusClass = "danger";
                $summary["absent"]++;
            }
            if ($isHoliday) $summary["holiday"]++;
            if ($isWeekoff) $summary["weekoff"]++;
        } else if ($isHoliday) {
            $status = "Holiday";
            $statusClass = "primary";
            $inTime = "-";
            $outTime = "-";
            $summary["holiday"]++;
        } else if ($isWeekoff) {
            $status = "Week Off";
            $statusClass = "secondary";
            $inTime = "-";
            $outTime = "-";
            $summary["weekoff"]++;
        } else {
            $status = "Absent";
            $statusClass = "danger";
            $inTime = "-";
            $outTime = "-";
            $summary["absent"]++;
        }

        $rawIn = $rawCheckIns[$date] ?? '';
        $isLate = false;
        $lateByStr = '';
        if (!empty($rawIn) && $rawIn > $scheduledInTime) {
            $isLate = true;
            $diffSec = strtotime($rawIn) - strtotime($scheduledInTime);
            $mins = floor($diffSec / 60);
            if ($mins >= 60) {
                $hrs = floor($mins / 60);
                $remMins = $mins % 60;
                $lateByStr = $hrs . "h " . $remMins . "m late";
            } else {
                $lateByStr = $mins . " mins late";
            }
        }

        $dailyData[] = [
            "date" => date("d-M-Y", $d),
            "date_formatted" => date("d-M-Y", $d),
            "day" => $dayName,
            "day_name" => $dayName,
            "status" => $status,
            "statusclass" => $statusClass,
            "intime" => $inTime,
            "in_time" => $inTime,
            "outtime" => $outTime,
            "scheduled_in" => date('h:i A', strtotime($scheduledInTime)),
            "is_late" => $isLate,
            "late_by" => $lateByStr
        ];
    }

    echo json_encode([
        "status" => "success",
        "employeename" => $empName,
        "employeeid" => $employeeid,
        "monthyear" => date("F Y", strtotime($firstDay)),
        "summary" => $summary,
        "daily" => $dailyData
    ]);
    exit;
} else if ($action == "getEpfEsicApplicableEmployeeList") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $filterType = $_POST['filterType'] ?? 'applicable_any'; // applicable_any (default), epf_only, esic_only, both, all, not_applicable

    $sql = "SELECT 
                e.EmployeeID,
                CONCAT_WS(' ', IFNULL(e.TitleName, ''), IFNULL(e.FirstName, ''), IFNULL(e.MiddleName, ''), IFNULL(e.LastName, '')) AS FullName,
                e.DateOfJoining,
                e.Status,
                e.WorkLocation,
                e.PanNumber,
                e.AadharNumber,
                e.MobileNo,
                e.Email,
                desg.DesignationName,
                
                epf.id AS epf_id,
                epf.regid AS epf_regid,
                epf.EmployeeDeductionValue AS epf_emp_val,
                epf.EmployeeDeductionType AS epf_emp_type,
                epf.EmployerDeductionValue AS epf_empr_val,
                epf.EmployerDeductionType AS epf_empr_type,
                
                esic.id AS esic_id,
                esic.regid AS esic_regid,
                esic.EmployeeDeductionValue AS esic_emp_val,
                esic.EmployeeDeductionType AS esic_emp_type,
                esic.EmployerDeductionValue AS esic_empr_val,
                esic.EmployerDeductionType AS esic_empr_type

            FROM hrm_EmployeesMaster e

            LEFT JOIN hrm_DesignationsMaster desg 
                ON desg.DesignationID = e.DesignationID 
               AND (desg.tedit IS NULL OR desg.tedit = '')

            LEFT JOIN hrm_EmployeeDeductionsComponents epf 
                ON epf.EmployeeId = e.EmployeeID 
               AND epf.companyid = e.companyid 
               AND epf.projectid = e.projectid 
               AND (epf.DeductionId = 1 OR UPPER(epf.DeductionName) LIKE '%EPF%' OR UPPER(epf.DeductionName) LIKE '%PF%') 
               AND (epf.tedit IS NULL OR epf.tedit = '')

            LEFT JOIN hrm_EmployeeDeductionsComponents esic 
                ON esic.EmployeeId = e.EmployeeID 
               AND esic.companyid = e.companyid 
               AND esic.projectid = e.projectid 
               AND (esic.DeductionId = 3 OR UPPER(esic.DeductionName) LIKE '%ESIC%' OR UPPER(esic.DeductionName) LIKE '%ESI%') 
               AND (esic.tedit IS NULL OR esic.tedit = '')

            WHERE e.companyid = ? 
              AND e.subcompanyid = ? 
              AND e.projectid = ? 
              AND (e.tedit IS NULL OR e.tedit = '')
            GROUP BY e.EmployeeID
            ORDER BY e.EmployeeID ASC";

    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param("iii", $companyid, $subcompanyid, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $list = [];
    while ($row = $result->fetch_assoc()) {
        $hasEpf = !empty($row['epf_id']);
        $hasEsic = !empty($row['esic_id']);
        
        if ($filterType == 'epf_only' && !$hasEpf) continue;
        if ($filterType == 'esic_only' && !$hasEsic) continue;
        if ($filterType == 'both' && (!$hasEpf || !$hasEsic)) continue;
        if ($filterType == 'applicable_any' && (!$hasEpf && !$hasEsic)) continue;
        if ($filterType == 'not_applicable' && ($hasEpf || $hasEsic)) continue;

        $row['hasEpf'] = $hasEpf;
        $row['hasEsic'] = $hasEsic;
        $list[] = $row;
    }
    echo json_encode($list);
    exit;
} else if ($action == "updateEpfEsicRegNumbers") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $EmployeeID = $_POST['EmployeeID'] ?? '';
    $epf_regid = trim($_POST['epf_regid'] ?? '');
    $esic_regid = trim($_POST['esic_regid'] ?? '');

    if (empty($EmployeeID)) {
        echo json_encode(["status" => "error", "message" => "Invalid Employee ID."]);
        exit;
    }

    // 1. Update or Insert EPF regid (DeductionId = 1)
    $chkEpf = $hrmconn->prepare("SELECT id FROM hrm_EmployeeDeductionsComponents WHERE companyid = ? AND projectid = ? AND EmployeeId = ? AND DeductionId = 1 AND (tedit IS NULL OR tedit = '')");
    $chkEpf->bind_param("iii", $companyid, $projectid, $EmployeeID);
    $chkEpf->execute();
    $epfRes = $chkEpf->get_result();
    if ($epfRow = $epfRes->fetch_assoc()) {
        $upEpf = $hrmconn->prepare("UPDATE hrm_EmployeeDeductionsComponents SET regid = ? WHERE id = ?");
        $upEpf->bind_param("si", $epf_regid, $epfRow['id']);
        $upEpf->execute();
        $upEpf->close();
    } else if (!empty($epf_regid)) {
        $insEpf = $hrmconn->prepare("INSERT INTO hrm_EmployeeDeductionsComponents (companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, regid) VALUES (?, ?, ?, ?, 1, 'EPF', ?)");
        $insEpf->bind_param("iiiis", $companyid, $subcompanyid, $projectid, $EmployeeID, $epf_regid);
        $insEpf->execute();
        $insEpf->close();
    }
    $chkEpf->close();

    // 2. Update or Insert ESIC regid (DeductionId = 3)
    $chkEsic = $hrmconn->prepare("SELECT id FROM hrm_EmployeeDeductionsComponents WHERE companyid = ? AND projectid = ? AND EmployeeId = ? AND DeductionId = 3 AND (tedit IS NULL OR tedit = '')");
    $chkEsic->bind_param("iii", $companyid, $projectid, $EmployeeID);
    $chkEsic->execute();
    $esicRes = $chkEsic->get_result();
    if ($esicRow = $esicRes->fetch_assoc()) {
        $upEsic = $hrmconn->prepare("UPDATE hrm_EmployeeDeductionsComponents SET regid = ? WHERE id = ?");
        $upEsic->bind_param("si", $esic_regid, $esicRow['id']);
        $upEsic->execute();
        $upEsic->close();
    } else if (!empty($esic_regid)) {
        $insEsic = $hrmconn->prepare("INSERT INTO hrm_EmployeeDeductionsComponents (companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, regid) VALUES (?, ?, ?, ?, 3, 'ESIC', ?)");
        $insEsic->bind_param("iiiis", $companyid, $subcompanyid, $projectid, $EmployeeID, $esic_regid);
        $insEsic->execute();
        $insEsic->close();
    }
    $chkEsic->close();

    echo json_encode(["status" => "success", "message" => "EPF & ESIC Registration Numbers updated successfully!"]);
    exit;
} else if ($action == "GetBankSalaryreporthtml") {
    $CompanyID = $_POST['companyid'];
    $SubCompanyID = $_POST['subcompanyid'];
    $ProjectID = $_POST['projectid'];
    $SalaryYear = $_POST['salaryyear'];
    $SalaryMonth = $_POST['salarymonth'];
    $slrepthtml = getBankSalaryReportHTML($conn, $hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
    echo $slrepthtml;
    exit;
}

function createEmployeePaySlipWithHeaderFooter($outputFile, $companyname, $projectname, $EmployeeData, $headerImage = null, $footerImage = null, $a1 = 40, $a2 = 20, $b1 = 15, $b2 = 40) {
    global $hrmconn;

    if (!class_exists('CustomPDF')) {
        class CustomPDF extends TCPDF {
            public $companyname;
            public $projectname;
            public $headerImage;
            public $footerImage;
            public $a1;
            public $a2;
            public $b1;
            public $b2;

            public function setHeaderFooterData($headerImage, $footerImage, $companyname, $projectname, $a1, $a2, $b1, $b2) {
                $this->headerImage = $headerImage;
                $this->footerImage = $footerImage;
                $this->companyname = $companyname;
                $this->projectname = $projectname;
                $this->a1 = $a1;
                $this->a2 = $a2;
                $this->b1 = $b1;
                $this->b2 = $b2;
            }

            public function Header() {
                if ($this->headerImage) {
                    $this->Image($this->headerImage, 10, 10, $this->a1, $this->a2);
                }
                $this->SetFont('helvetica', 'B', 14);
                $this->SetXY(50, 10);
                $this->Cell(100, 10, 'PAY SLIP', 0, 1, 'C');

                if ($this->projectname) {
                    $this->SetFont('helvetica', '', 10);
                    $this->SetXY(50, 20);
                    $this->Cell(100, 10, $this->projectname, 0, 1, 'C');
                }
            }

            public function Footer() {
                if ($this->footerImage) {
                    $this->SetY(-$this->b1);
                    $this->Image($this->footerImage, 10, $this->GetY(), $this->b2);
                }
                $this->SetY(-15);
                $this->SetFont('helvetica', 'I', 6);
                $this->Cell(0, 6, 'This is system generated payslip ', 0, 1, 'C');
                $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
            }
        }
    }

    if (!empty($EmployeeData)) {
        // Create new PDF document
        $pdf = new CustomPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->setHeaderFooterData($headerImage, $footerImage, $companyname, $projectname, $a1, $a2, $b1, $b2);

        // Set document properties
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Employee Payslip');
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        // Employee Details
        $employeeName = $EmployeeData['EmployeeName'] ?? 'N/A';
        $payperiod = date('M-Y', strtotime(($EmployeeData['SalaryYear'] ?? date('Y')) . '-' . ($EmployeeData['SalaryMonth'] ?? date('m')) . '-01'));
        $employeeId = $EmployeeData['EmployeeID'] ?? 'N/A';
        $companyid = $_POST['companyid'] ?? $EmployeeData['CompanyID'] ?? 0;
        $projectid = $_POST['projectid'] ?? $EmployeeData['ProjectID'] ?? 0;
        $uanNo = getEmployeeDeductionRegId($hrmconn, $companyid, $projectid, $employeeId, 1);
        $esicNo = getEmployeeDeductionRegId($hrmconn, $companyid, $projectid, $employeeId, 3);
        $dateOfJoining = $EmployeeData['DateOfJoining'] ?? 'N/A';
        $DesignationName = $EmployeeData['DesignationName'] ?? 'N/A';
        $DepartmentName = $EmployeeData['DepartmentName'] ?? 'N/A';
        $WorkedDays = $EmployeeData['WorkedDays'] ?? 'N/A';

        $TotalEarning = number_format(floatval(($EmployeeData['BasicSalary'] ?? 0) + ($EmployeeData['HRA'] ?? 0) + ($EmployeeData['Conveyance'] ?? 0) + ($EmployeeData['Allowances'] ?? 0)), 2);

        $TotalDeduction = number_format(floatval(($EmployeeData['EPF'] ?? 0) + ($EmployeeData['ESIC'] ?? 0) + ($EmployeeData['INCOMTAX'] ?? 0) + ($EmployeeData['OtherDeduction'] ?? 0)), 2);
        $NetSalary = (($EmployeeData['BasicSalary'] ?? 0) + ($EmployeeData['HRA'] ?? 0) + ($EmployeeData['Conveyance'] ?? 0) + ($EmployeeData['Allowances'] ?? 0)) - (($EmployeeData['EPF'] ?? 0) + ($EmployeeData['ESIC'] ?? 0) + ($EmployeeData['INCOMTAX'] ?? 0) + ($EmployeeData['OtherDeduction'] ?? 0));

        // Employee Info Table
         $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(45, 7, "Employee Name:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $employeeName, 1, 0, 'L');
        $pdf->Cell(45, 7, "Employee ID:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $employeeId, 1, 1, 'L');

        $pdf->Cell(45, 7, "Designation:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $DesignationName, 1, 0, 'L');
        $pdf->Cell(45, 7, "UAN No:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $uanNo, 1, 1, 'L');
        
        $pdf->Cell(45, 7, "Date of Joining:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $dateOfJoining, 1, 0, 'L');
        $pdf->Cell(45, 7, "ESIC No:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $esicNo, 1, 1, 'L');

        $pdf->Cell(45, 7, "Pay Period:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $payperiod, 1, 0, 'L');
        $pdf->Cell(45, 7, "Worked Days:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $WorkedDays, 1, 1, 'L');

        // Salary Breakdown
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(90, 7, "Earnings Components", 1, 0, 'C', true);
        $pdf->Cell(90, 7, "Deductions Components", 1, 1, 'C', true);

         $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(60, 7, "Basic Salary", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['BasicSalary'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(60, 7, "PF", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['EPF'] ?? 0, 2), 1, 1, 'R');

        $pdf->Cell(60, 7, "House Rent Allowance", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['HRA'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(60, 7, "ESIC", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['ESIC'] ?? 0, 2), 1, 1, 'R');

        $pdf->Cell(60, 7, "Conveyance", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['Conveyance'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(60, 7, "Income Tax", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['INCOMTAX'] ?? 0, 2), 1, 1, 'R');

        $pdf->Cell(60, 7, "Allowances", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['Allowances'] ?? 0, 2), 1, 0, 'R');
        $pdf->Cell(60, 7, "Other Deduction", 1, 0, 'L');
        $pdf->Cell(30, 7, number_format($EmployeeData['OtherDeduction'] ?? 0, 2), 1, 1, 'R');

        $pdf->Cell(60, 4, "", 1, 0, 'L');
        $pdf->Cell(30, 4, "", 1, 0, 'R');
        $pdf->Cell(60, 4, "", 1, 0, 'L');
        $pdf->Cell(30, 4, "", 1, 1, 'R');

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(60, 7, "Total Earnings", 1, 0, 'L', true);
        $pdf->Cell(30, 7, $TotalEarning, 1, 0, 'R', true);
        $pdf->Cell(60, 7, "Total Deductions", 1, 0, 'L', true);
        $pdf->Cell(30, 7, $TotalDeduction, 1, 1, 'R', true);
        
        $pdf->Cell(60, 7, "", 1, 0, 'L', true);
        $pdf->Cell(30, 7, "", 1, 0, 'R', true);
        $pdf->Cell(60, 7, "Net Salary", 1, 0, 'L', true);
        $pdf->Cell(30, 7, number_format(($NetSalary), 2), 1, 1, 'R', true);

        // Footer Signature       
        $pdf->Ln(10);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell(90, 8, "Authorized Signatory", 0, 0, 'L');
        $pdf->Cell(90, 8, "Employee Signature", 0, 1, 'R');

        $pdf->Ln(5);
        $pdf->Cell(90, 8, "____________________", 0, 0, 'L');
        $pdf->Cell(90, 8, "____________________", 0, 1, 'R');

        // Save PDF
        $pdf->Output($outputFile, 'F');
    }
}

function getEPFReportHTML($conn, $hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth) {
    $total_payable_gross = 0;
    $total_EPF_Wages = 0;
    $total_EPS_Wages = 0;
    $total_EDLI_Wages = 0;
    $total_EE_Share_Remitted = 0;
    $total_EPS_Contribution = 0;
    $total_ER_Share_Remitted = 0;
    $total_NCP_Days = 0;
    $total_Refunds = 0;
    ob_start(); // Start capturing output
    ?>
    <table border="1" class="table2excel" id="EPFReporthtml" style="color:black;">
        <thead>
        <tr>
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
        $EPFTRAN = getEPFTransactions($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
        if (count($EPFTRAN) > 0) {
            foreach ($EPFTRAN as $epdkey) {
                $EmpData = fetchEmployeeData($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, 'EmployeeID', $epdkey['EmployeeId']);
                $EmployeeName = $EmpData[0]['FullName'] ?? '';
                $pf_uanno = getEmployeeDeductionRegId($hrmconn, $CompanyID, $ProjectID, $epdkey['EmployeeId'], 1);

                $sldata = getEmployeeCalculatedSalary($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, 'employeeid', $epdkey['EmployeeId']);

                $payable_gross = $sldata[0]['GrossSalary'] ?? 0;
                $EPF_Wages = $epdkey['EmployeePF'] ?? 0;
                $EPS_Wages = $epdkey['EPS'] ?? 0;
                $EDLI_Wages = $epdkey['EDLI'] ?? 0;
                $EE_Share_Remitted = round($epdkey['EmployeePF'] ?? 0);
                $EPS_Contribution = round($epdkey['EPS'] ?? 0);
                $ER_Share_Remitted = round($epdkey['EmployerPF'] ?? 0);
                $NCP_Days = getNCPDays($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $epdkey['EmployeeId'], $SalaryYear, $SalaryMonth);
                $Refunds = 0;
                ?>
                <tr>
                    <td style="text-align:center; mso-number-format:'\@';"><?php echo $pf_uanno; ?></td>
                    <td><?php echo strtoupper($EmployeeName); ?></td>
                    <td style="text-align:center"><?php echo $payable_gross; ?></td>
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
                $total_payable_gross += $payable_gross;
                $total_EPF_Wages += $EPF_Wages;
                $total_EPS_Wages += $EPS_Wages;
                $total_EDLI_Wages += $EDLI_Wages;
                $total_EE_Share_Remitted += $EE_Share_Remitted;
                $total_EPS_Contribution += $EPS_Contribution;
                $total_ER_Share_Remitted += $ER_Share_Remitted;
                $total_NCP_Days += $NCP_Days;
                $total_Refunds += $Refunds;
            }
            ?>
        </tbody>
        <tfoot>
        <tr style="font-weight:bold;">
            <td colspan="2">Total</td>
            <td style="text-align:center"><?php echo $total_payable_gross; ?></td>
            <td style="text-align:center"><?php echo $total_EPF_Wages; ?></td>
            <td style="text-align:center"><?php echo $total_EPS_Wages; ?></td>
            <td style="text-align:center"><?php echo $total_EDLI_Wages; ?></td>
            <td style="text-align:center"><?php echo $total_EE_Share_Remitted; ?></td>
            <td style="text-align:center"><?php echo $total_EPS_Contribution; ?></td>
            <td style="text-align:center"><?php echo $total_ER_Share_Remitted; ?></td>
            <td style="text-align:center"><?php echo $total_NCP_Days; ?></td>
            <td style="text-align:center"><?php echo $total_Refunds; ?></td>
        </tr>
        </tfoot>
        <?php
        } else {
            ?>
            <tr><td colspan="11" class="w3-center">No Data Available</td></tr>
            </tbody>
            <?php
        }
        ?>
    </table>
    <?php

    return ob_get_clean(); // Return the captured HTML
}

function getSalaryReportHTML($conn, $hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth) {
    $cmpprj=getprojectdetailwithcmpsubcmp($conn, $CompanyID, $SubCompanyID,"withauthrizedprojects",$ProjectID);
    $companyname = $cmpprj[0]['companyname'];
    $projectname = $cmpprj[0]['projectname'];

    $salaryyearmonth = date('M-Y', strtotime($SalaryYear.'-'.$SalaryMonth.'-01'));
    
    $SLL = getEmployeeCalculatedSalary($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, "salarymonth", $SalaryMonth);

    if (empty($SLL)) {
        return "<p>No salary data available.</p>";
    }

    // Initialize totals
    $ttl_gs = $ttl_bs = $ttl_hs = $ttl_as = $ttl_ditax = $ttl_dpf = $ttl_dsi = $ttl_dod = $ttl_ntpay = 0;

    ob_start(); // Start capturing HTML
    ?>
    <table id="SalaryReporthtml" style="color:black;">
        <thead>
            <tr><th colspan="16" style="font-weight:bold; font-size:16px; color: black;">Company Name: <?php echo $projectname; ?></th></tr>
            <tr><th colspan="16" style="font-weight:bold; font-size:16px; color: black;">Titile : Salary Sheet Report for the Month of <?php echo $salaryyearmonth; ?></th></tr>
            <tr>
                <th style="width:50px;">S. No.</th>
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
        $i = 1;
        foreach ($SLL as $row) {
            // Attendance
            $att = fetchEmployeeCalculatedAttendance($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['SalaryYear'], $row['SalaryMonth'], "EmployeeID", $row['EmployeeID']);
            $payableDays = !empty($att[0]['TotalPayableDays']) ? $att[0]['TotalPayableDays'] : (!empty($att[0]['CalculatedPresentDays']) ? $att[0]['CalculatedPresentDays'] : 0);
            $payableDays = str_replace(".00", "", (string)$payableDays);

            // Bank details
            $Ebnak = getEmployeeBankDetails($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['EmployeeID']);
            $EmpBankName = $EmpAccountNumber = $EmpBankIFSCCode = "";
            if (!empty($Ebnak)) {
                $EmpBankName = $Ebnak[0]['BankName'] ?? "";
                $EmpAccountNumber = $Ebnak[0]['AccountNumber'] ?? "";
                $EmpBankIFSCCode = $Ebnak[0]['IFSCCode'] ?? "";
            }

            // Employee information
            $eDkey = fetchEmployeeData($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], 'EmployeeID', $row['EmployeeID']);
            $EmployeeName = "";
            $INCOMTAX = $EPF = $ESIC = $OtherDeduction = 0;
            if (!empty($eDkey)) {
                $EmployeeName = trim(($eDkey[0]['TitleName'] ?? "") . " " . ($eDkey[0]['FirstName'] ?? "") . " " . ($eDkey[0]['MiddleName'] ?? "") . " " . ($eDkey[0]['LastName'] ?? ""));

                $EmpDdct = getEmployeeSalaryDeductions($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['EmployeeID'], $row['SalaryYear'], $row['SalaryMonth']);
                if (!empty($EmpDdct)) {
                    foreach ($EmpDdct as $ded) {
                        switch ($ded['DeductionId'] ?? 0) {
                            case 1: $EPF = $ded['DeductionAmount']; break;
                            case 5: $ESIC = $ded['DeductionAmount']; break;
                            case 6: $INCOMTAX = $ded['DeductionAmount']; break;
                            default: $OtherDeduction += $ded['DeductionAmount']; break;
                        }
                    }
                }
            }

            $calcNet = floatval(($row['BasicSalary'] ?? 0) + ($row['HRA'] ?? 0) + ($row['Conveyance'] ?? 0) + ($row['Allowances'] ?? 0) + ($row['OvertimePay'] ?? 0) + ($row['ArrearAmount'] ?? 0)) - (floatval($INCOMTAX + $EPF + $ESIC + $OtherDeduction) + floatval($row['AdvanceDeduction'] ?? 0));
            $netSalaryVal = (isset($row['NetSalary']) && floatval($row['NetSalary']) > 0) ? floatval($row['NetSalary']) : max(0, $calcNet);
            $netSalaryFormatted = number_format($netSalaryVal, 2, '.', '');
            ?>
            <tr>
                <td><center><?php echo $i++; ?></center></td>
                <td style="white-space: nowrap;"><?php echo strtoupper($EmployeeName); ?></td>
                <td style="mso-number-format:'\@';"><center><?php echo $payableDays; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $row['GrossSalary']; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $row['BasicSalary']; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $row['HRA']; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $row['Allowances']; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $INCOMTAX; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $EPF; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $ESIC; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $OtherDeduction; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $EmpBankName; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $EmpAccountNumber; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $EmpBankIFSCCode; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $netSalaryFormatted; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $row['requestno'] ?? ''; ?></center></td>
            </tr>
            <?php
            // Accumulate totals
            $ttl_gs += $row['GrossSalary'];
            $ttl_bs += $row['BasicSalary'];
            $ttl_hs += $row['HRA'];
            $ttl_as += $row['Allowances'];
            $ttl_ditax += $INCOMTAX;
            $ttl_dpf += $EPF;
            $ttl_dsi += $ESIC;
            $ttl_dod += $OtherDeduction;
            $ttl_ntpay += $netSalaryVal;
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
                <td></td>
            </tr>
        </tfoot>
    </table>
    <?php
    return ob_get_clean(); // Return the HTML as string
}

function getBankSalaryReportHTML($conn, $hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth) {
    $cmpprj = getprojectdetailwithcmpsubcmp($conn, $CompanyID, $SubCompanyID, "withauthrizedprojects", $ProjectID);
    $companyname = $cmpprj[0]['companyname'] ?? '';
    $projectname = $cmpprj[0]['projectname'] ?? '';

    $salaryyearmonth = date('M-Y', strtotime($SalaryYear.'-'.$SalaryMonth.'-01'));
    
    $SLL = getEmployeeCalculatedSalary($hrmconn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, "salarymonth", $SalaryMonth);

    if (empty($SLL)) {
        return "<p>No salary data available.</p>";
    }

    $ttl_ntpay = 0;

    ob_start();
    ?>
    <table id="BankSalaryReporthtml" style="color:black;">
        <thead>
            <tr><th colspan="6" style="font-weight:bold; font-size:16px; color: black;">Company Name: <?php echo $projectname; ?></th></tr>
            <tr><th colspan="6" style="font-weight:bold; font-size:16px; color: black;">Title : Salary Sheet for Bank for the Month of <?php echo $salaryyearmonth; ?></th></tr>
            <tr>
                <th style="width:50px;">S. No.</th>
                <th>Employee Name</th>
                <th>Bank Name</th>
                <th>Account Number</th>
                <th>IFSC Code</th>
                <th>Net Payable Amount</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $i = 1;
        foreach ($SLL as $row) {
            // Bank details
            $Ebnak = getEmployeeBankDetails($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['EmployeeID']);
            $EmpBankName = $EmpAccountNumber = $EmpBankIFSCCode = "";
            if (!empty($Ebnak)) {
                $EmpBankName = $Ebnak[0]['BankName'] ?? "";
                $EmpAccountNumber = $Ebnak[0]['AccountNumber'] ?? "";
                $EmpBankIFSCCode = $Ebnak[0]['IFSCCode'] ?? "";
            }

            // Employee name
            $eDkey = fetchEmployeeData($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], 'EmployeeID', $row['EmployeeID']);
            $EmployeeName = "";
            $INCOMTAX = $EPF = $ESIC = $OtherDeduction = 0;
            if (!empty($eDkey)) {
                $EmployeeName = trim(($eDkey[0]['TitleName'] ?? "") . " " . ($eDkey[0]['FirstName'] ?? "") . " " . ($eDkey[0]['MiddleName'] ?? "") . " " . ($eDkey[0]['LastName'] ?? ""));

                $EmpDdct = getEmployeeSalaryDeductions($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], $row['EmployeeID'], $row['SalaryYear'], $row['SalaryMonth']);
                if (!empty($EmpDdct)) {
                    foreach ($EmpDdct as $ded) {
                        switch ($ded['DeductionId'] ?? 0) {
                            case 1: $EPF = $ded['DeductionAmount']; break;
                            case 5: $ESIC = $ded['DeductionAmount']; break;
                            case 6: $INCOMTAX = $ded['DeductionAmount']; break;
                            default: $OtherDeduction += $ded['DeductionAmount']; break;
                        }
                    }
                }
            }

            $calcNet = floatval(($row['BasicSalary'] ?? 0) + ($row['HRA'] ?? 0) + ($row['Conveyance'] ?? 0) + ($row['Allowances'] ?? 0) + ($row['OvertimePay'] ?? 0) + ($row['ArrearAmount'] ?? 0)) - (floatval($INCOMTAX + $EPF + $ESIC + $OtherDeduction) + floatval($row['AdvanceDeduction'] ?? 0));
            $netSalaryVal = (isset($row['NetSalary']) && floatval($row['NetSalary']) > 0) ? floatval($row['NetSalary']) : max(0, $calcNet);
            $netSalaryFormatted = number_format($netSalaryVal, 2, '.', '');
            ?>
            <tr>
                <td><center><?php echo $i++; ?></center></td>
                <td style="white-space: nowrap;"><?php echo strtoupper($EmployeeName); ?></td>
                <td style="mso-number-format:'\@';"><center><?php echo $EmpBankName; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $EmpAccountNumber; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $EmpBankIFSCCode; ?></center></td>
                <td style="mso-number-format:'\@';"><center><?php echo $netSalaryFormatted; ?></center></td>
            </tr>
            <?php
            $ttl_ntpay += $netSalaryVal;
        }
        ?>
        </tbody>
        <tfoot>
            <tr style="font-weight:bold;">
                <td colspan="5">Total</td>
                <td><center><?php echo number_format($ttl_ntpay, 2, '.', ''); ?></center></td>
            </tr>
        </tfoot>
    </table>
    <?php
    return ob_get_clean();
}
?>