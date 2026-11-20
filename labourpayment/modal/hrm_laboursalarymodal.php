<?php
ob_start();
@ini_set('display_errors', 1);
error_reporting(0);

require_once(__DIR__ . '/../../connection.php');
require_once(__DIR__ . '/../functions/admin_functionmaster.php');
require_once(__DIR__ . '/../functions/comanphp_functions.php');
require_once(__DIR__ . '/../functions/hrm_FunctionMaster.php');
require_once(__DIR__ . '/../functions/hrm_insertupdatesalary_functions.php');
require_once(__DIR__ . '/../pdfroutines/tcpdf/tcpdf.php');


// Ensure cache directory is writable for TCPDF (prevents WriteBlob errors on server)
if (defined('K_PATH_CACHE')) {
    if (!is_dir(K_PATH_CACHE)) {
        @mkdir(K_PATH_CACHE, 0777, true);
    } else if (!is_writable(K_PATH_CACHE)) {
        @chmod(K_PATH_CACHE, 0777);
    }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action == "CheckUploadedAttendanceWithLocation") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'];
    $locationId = $_POST['locationId'] ?? null;
    $locationName = $_POST['locationName'] ?? null;
    $dateFrom = $_POST['dateFrom'] ?? null;
    $dateTo = $_POST['dateTo'] ?? null;
    $checkatt = checkAttendanceExistWithLocationId($hrmconn, $companyid, $subcompanyid, $projectid, $dateFrom, $dateTo, $locationId);
    // print_r($checkatt);
    echo json_encode($checkatt);
} else if ($action == "RemovedAlreadyUploadedLabourAttendance") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'];
    $locationId = $_POST['locationId'] ?? null;
    $dateFrom = $_POST['dateFrom'] ?? null;
    $dateTo = $_POST['dateTo'] ?? null;
    $response = removeLabourCalculateAttendanceData($hrmconn, $projectid, $locationId, $dateFrom, $dateTo);
    echo $response;
} else if ($action == "LabourlistforCalculateSalaryhtml") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $attyearmonth = $_POST['attyearmonth'] ?? null;
    $daterange = $_POST['daterange'] ?? null;
    $locationid = $_POST['locationid'] ?? null;
    $attyrmnex = explode("-", $attyearmonth);
    $attYear = $attyrmnex[0] ?? date('Y');
    $attMonth = $attyrmnex[1] ?? date('m');

    $dateFrom = "";
    $dateTo = "";
    $filterBy = "";
    $filterV1 = "";
    $filterV2 = "";
    $filterV3 = "";

    if ($locationid == "all") {
        if ($daterange == "fullmonth") {
            $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
            $dateTo = date("Y-m-t", strtotime($dateFrom));
            $filterBy = "yearmonth";
            $filterV1 = $attYear;
            $filterV2 = $attMonth;
        } else {
            $daterangeex = explode("|", $daterange);
            $dateFrom = $daterangeex[0] ?? "";
            $dateTo = $daterangeex[1] ?? "";
            $filterBy = "datebetween";
            $filterV1 = $dateFrom;
            $filterV2 = $dateTo;
        }
    } else {
        if ($daterange == "fullmonth") {
            $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
            $dateTo = date("Y-m-t", strtotime($dateFrom));
            $filterBy = "yearmonthwithlocationid";
            $filterV1 = $attYear;
            $filterV2 = $attMonth;
            $filterV3 = $locationid;
        } else {
            $daterangeex = explode("|", $daterange);
            $dateFrom = $daterangeex[0] ?? "";
            $dateTo = $daterangeex[1] ?? "";
            $filterBy = "datebetweenwithlocationid";
            $filterV1 = $dateFrom;
            $filterV2 = $dateTo;
            $filterV3 = $locationid;
        }
    }

    $empatt = fetchLabourAttendanceWithGrouped($hrmconn, $companyid, $subcompanyid, $projectid, $filterBy, $filterV1, $filterV2, $filterV3);

    $returndata = array();
    $sno = 1;
    $emplt = "";
    $totalwagesamount = 0;
    $totalworkeddays = 0;
    $empcount = 0;
    $zerodayempcount = 0;
    // Initialize global counters
    $globalCalStatusCount = 0;
    $globalApprdStatusCount = 0;
    $globalStatus = "";
    // echo count($empatt);
    $zerodayslist=[];
    if (count($empatt) > 0) {
        foreach ($empatt as $calatkey) {
            $employeeID = $calatkey['EmployeeID'];
            $totalPresentDays = $calatkey['TotalPresentDays'];

            // Fetch Calculated Data
            $datess = $employeeID . "|" . $dateFrom . "|" . $dateTo . "|" . $locationid;
            $gsldata = getLabourCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, "datebetweenWithEmployeeIdlocationid", $datess);

            $PayPerDay = 0;
            $NetSalary = 0;
            $statusBadge = "";
            $rowStatusAttr = ""; // For data attribute
            $editPresentDay = "";
            $isRowCalculated = false;
            $isRowApproved = false;
            $BasicLabourpayment = 0;
            if (count($gsldata) > 0) {
                $PayPerDay = $gsldata[0]['PayPerDay'] ?? 0;
                $NetSalary = $gsldata[0]['NetSalary'] ?? 0;
                $BasicLabourpayment = $gsldata[0]['basiclabourpayment'] ?? 0;
                $statusBadge = '<span class="badge badge-success">Calculated</span>';
                $rowStatusAttr = 'Calculated';

                $isRowCalculated = true;
                $globalCalStatusCount++;

                if ($gsldata[0]['requestno'] != "") {
                    $isRowApproved = true;
                    $globalApprdStatusCount++;
                    $rowApprovedAttr = 'Approved';
                    // Approved: Edit button hidden?
                    $editPresentDay = "<span class='badge badge-success editPresentDays d-none' style='cursor:pointer; float:right;' header='Edit Days'><i class='fa fa-edit'></i> Edit</span>";
                } else {
                    $rowApprovedAttr = "";
                    $editPresentDay = "<span class='badge badge-success editPresentDays' style='cursor:pointer; float:right;' header='Edit Days'><i class='fa fa-edit'></i> Edit</span>";
                }
            } else {
                // Not Calculated yet, fetch Master Payment Data
                $editPresentDay = "<span class='badge badge-success editPresentDays' style='cursor:pointer; float:right;' header='Edit Days'><i class='fa fa-edit'></i> Edit</span>";
                $lbpayd = getLabourPaymentData($hrmconn, $projectid, "EmployeeID", $employeeID);
                if (count($lbpayd) > 0) {
                    $PayPerDay = $lbpayd[0]['PerDayPayment'];
                    $BasicLabourpayment = $PayPerDay * $totalPresentDays;
                    $NetSalary = $PayPerDay * $totalPresentDays;
                }
                $rowApprovedAttr = "";
            }

            // Fetch Employee Details
            $emplist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $employeeID);

            // Fetch Other Payments
            $EmpOtPayment = NotCalculatedLabourOtherPayment($hrmconn, $projectid, $employeeID, $dateFrom, $dateTo);
            $incentivegrafting = $EmpOtPayment[0]['incentivegrafting'] ?? 0;
            $incentiveseasional = $EmpOtPayment[0]['incentiveseasional'] ?? 0;
            $overtimepayment = $EmpOtPayment[0]['overtimepayment'] ?? 0;
            $otherpayment = $EmpOtPayment[0]['otherpayment'] ?? 0;

            $NetSalaryPayable = $BasicLabourpayment + $incentivegrafting + $incentiveseasional + $overtimepayment + $otherpayment;
            
            if (count($emplist) > 0 && $totalPresentDays > 0) {
                foreach ($emplist as $eemkey) {
                    $empcount++;
                    $totalwagesamount += $NetSalaryPayable;
                    $totalworkeddays += $totalPresentDays;

                    $empname = $eemkey['FirstName'] . ' ' . $eemkey['MiddleName'] . ' ' . $eemkey['LastName'];

                    // Fixed Variables: $overtimepayment and $otherpayment used correctly
                    $emplt .= '<tr data-companyid="' . $companyid . '" data-subcompanyid="' . $subcompanyid . '" data-projectid="' . $projectid . '" data-employeeid="' . $eemkey['EmployeeID'] . '" data-employeename="' . $empname . '" data-calstatus="' . $rowStatusAttr . '" data-apprdstatus="' . $rowApprovedAttr . '" data-presentdays="' . str_replace(".00", "", $totalPresentDays) . '" data-datefrom="' . $dateFrom . '" data-dateto="' . $dateTo . '" data-attyear="' . $attYear . '" data-attmonth="' . $attMonth . '" data-incentivegrafting="' . $incentivegrafting . '" data-incentiveseasional="' . $incentiveseasional . '" data-overtimepayment="' . $overtimepayment . '" data-otherpayment="' . $otherpayment . '" data-basiclabourpayment="' . $BasicLabourpayment . '" data-netpayable="' . $NetSalaryPayable . '" data-locationid="' . $locationid . '">
                        <td>' . $sno++ . '</td>
                        <td class="employeename">' . $eemkey['EmployeeID'] . ' - ' . $empname . ' <sup>' . $statusBadge . '</sup></td>
                        <td>
                           <input type="text" class="form-control payperdayinput" onkeypress="return isNumberKey(event)" value="' . $PayPerDay . '" readonly />
                        </td>
                        <td class="noexport">' . str_replace(".00", "", $totalPresentDays) . ' ' . $editPresentDay . '</td>
                        <td class="presentdays" style="display:none">' . str_replace(".00", "", $totalPresentDays) . '</td>
                        <td class="basicpayment">' . number_format($BasicLabourpayment, 2, '.', '') . '</td>
                        <td class="incentivegrafting">' . $incentivegrafting . '</td>
                        <td class="incentiveseasional">' . $incentiveseasional . '</td>
                        <td class="overtimepayment">' . $overtimepayment . '</td>
                        <td class="otherpayment">' . $otherpayment . '</td>
                        <td class="netpayable">' . number_format($NetSalaryPayable, 2, '.', '') . '</td>
                    </tr>';
                }
            }else{
                foreach ($emplist as $eemkey) {
                $zerodayempcount++;
                $firstName  = isset($eemkey['FirstName']) ? $eemkey['FirstName'] : '';
                $middleName = isset($eemkey['MiddleName']) ? $eemkey['MiddleName'] : '';
                $lastName   = isset($eemkey['LastName']) ? $eemkey['LastName'] : '';
                $labourname = trim($firstName . ' ' . $middleName . ' ' . $lastName);
                $zerodayslist[] = [
                    'labourid'   => $eemkey['EmployeeID'] ?? '',
                    'labourname' => $labourname,
                    'locationid' => $locationid,
                    'datefrom'   => $dateFrom,
                    'dateto'     => $dateTo
                ];
            }
            }
        }
    }

    // Determine overall status
    if ($globalCalStatusCount > 0)
        $globalStatus = "Calculated";
    if ($globalApprdStatusCount > 0)
        $globalStatus = "Approved"; // Override if any approved? or all? Logic depends on usage. 
    // Usually if ANY are calculated, we show save button state for 'Calculated'.

    if ($emplt) {
        $returndata = array(
            "status" => $globalStatus,
            "calstatuscount" => $globalCalStatusCount,
            "apprdstatuscount" => $globalApprdStatusCount,
            "data" => $emplt,
            "totalwagesamount" => $totalwagesamount,
            "ttllength" => $empcount,
            "totalworkeddays" => $totalworkeddays,
            "zerodaylength" => $zerodayempcount,
            "zerodayslist" => $zerodayslist
        );
    }
    echo json_encode($returndata);
} else if ($action == "InsertOrUpdateCalculatedAttendance_Payroll") {
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
    $returnmsg = [];
    if (count($empattdata) > 0) {
        foreach ($empattdata as $ematkey) {
            $fetcheatt = fetchEmployeeCalculatedAttendance($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['attendanceyear'], $ematkey['attendancemonth'], "EmployeeID", $ematkey['employeeid']);
            if (count($fetcheatt) > 0) {
                $daysinmonth = getDaysInMonthForSalary($ematkey['attendanceyear'], $ematkey['attendancemonth']);

                $workingdaysinmonth = getWorkingDaysInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);

                $ttlHolidays = getHoliDaysInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);

                $empWeekOffcount = getEmployeeWeekOffCountInMonth($hrmconn, $ematkey['companyid'], $ematkey['subcompanyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);

                // update claculated attendance
                $stmt = $hrmconn->prepare("UPDATE hrm_EmployeeCalculateAttendance SET WorkingDaysInMonth =?, WeekOffDays = ?, HoliDays = ?, PresentDays = ? WHERE CompanyID = ? AND ProjectID = ? AND EmployeeID = ? AND AttendanceYear = ? AND AttendanceMonth = ?");
                $stmt->bind_param("sssiiiiii", $workingdaysinmonth, $empWeekOffcount, $ttlHolidays, $ematkey['calculatepresentdays'], $ematkey['companyid'], $ematkey['projectid'], $ematkey['employeeid'], $ematkey['attendanceyear'], $ematkey['attendancemonth']);
                if ($stmt->execute()) {
                    $returnmsg[] = array("status" => "Success", "message" => "✅ Attendance updated successfully!", "employeeid" => $ematkey['employeeid'], 'employeename' => $ematkey['employeename']);
                } else {
                    $returnmsg[] = array("status" => "Error", "message" => "❌ Error updating attendance: " . $stmt->error, "employeeid" => $ematkey['employeeid'], 'employeename' => $ematkey['employeename']);
                }
                $stmt->close();
            } else {
                // insert calculated attendance
                $daysinmonth = getDaysInMonthForSalary($ematkey['attendanceyear'], $ematkey['attendancemonth']);

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
                $Comments = 'Attendance calculated on date ' . date('Y-m-d') . ' by ' . $loginname;
                $ttldays = floatval($workingdaysinmonth + $ttlHolidays + $empWeekOffcount);


                if ($workingdaysinmonth > $ematkey['calculatepresentdays']) {
                    $absentdays = $workingdaysinmonth - $ematkey['calculatepresentdays'];
                } else if ($ematkey['calculatepresentdays'] > $workingdaysinmonth) {
                    $extraDays = $ematkey['calculatepresentdays'] - $workingdaysinmonth;
                }

                // Bind parameters (s = string, i = integer, d = decimal)
                $stmt->bind_param(
                    "isssiiiddddddddds",
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
                    $returnmsg[] = array("status" => "Success", "message" => "✅  Attendance record inserted successfully!", "employeeid" => $ematkey['employeeid'], 'employeename' => $ematkey['employeename']);
                } else {
                    $returnmsg[] = array("status" => "Error", "message" => "❌ Error inserting record: " . $stmt->error, "employeeid" => $ematkey['employeeid'], 'employeename' => $ematkey['employeename']);
                }
                $stmt->close();
            }
        }
    }
    echo json_encode($returnmsg);
} else if ($action == "CalculatedSalaryData_Labour") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $attyearmonth = $_POST['attyearmonth'] ?? null;
    $daterange = $_POST['daterange'] ?? null;
    $locationid = $_POST['locationid'] ?? '';
    $attyrmnex = explode("-", $attyearmonth);
    $attYear = $attyrmnex[0];
    $attMonth = $attyrmnex[1];
    $dateFrom = "";
    $dateTo = "";

    if ($locationid == "all") {
        if ($daterange == "fullmonth") {
            $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
            $dateTo = date("Y-m-t", strtotime($dateFrom));
            $shortby = "datebetween";
            $shortbyvalue = $dateFrom . "|" . $dateTo;
        } else {
            $daterangeex = explode("|", $daterange);
            $dateFrom = trim($daterangeex[0]);
            $dateTo = trim($daterangeex[1]);
            $shortby = "datebetween";
            $shortbyvalue = $dateFrom . "|" . $dateTo;
        }
    } else {
        if ($daterange == "fullmonth") {
            $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
            $dateTo = date("Y-m-t", strtotime($dateFrom));
            $shortby = "datebetweenwithlocationid";
            $shortbyvalue = $dateFrom . "|" . $dateTo . "|" . $locationid;
        } else {
            $daterangeex = explode("|", $daterange);
            $dateFrom = trim($daterangeex[0]);
            $dateTo = trim($daterangeex[1]);
            $shortby = "datebetweenwithlocationid";
            $shortbyvalue = $dateFrom . "|" . $dateTo . "|" . $locationid;
        }
    }

    // echo "=====".$shortby."===".$shortbyvalue;
    // die();
    $calculatessalary = getLabourCalculatedSalaryOptimized($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue, 1000);
    $calsallist = array();
    if (count($calculatessalary) > 0) {
        foreach ($calculatessalary as $calsalkey) {
            $EmployeeID = $calsalkey['EmployeeID'];
            $paytoid = $calsalkey['paytoid'] ?? '';
            $EmployeeName = trim(($calsalkey['FirstName'] ?? '') . ' ' . ($calsalkey['MiddleName'] ?? '') . ' ' . ($calsalkey['LastName'] ?? ''));
            $CalculatedPresentDays = str_replace(".00", "", $calsalkey['CalculatedPresentDays'] ?? 0);
            $CalculatedoldPresentDays = $CalculatedPresentDays;

            $mmEmpd = [];
            $calsallist[] = array(
                'companyid' => $companyid,
                'subcompanyid' => $subcompanyid,
                'projectid' => $projectid,
                'EmployeeID' => $EmployeeID,
                'EmployeeName' => $EmployeeName,
                'SalaryID' => $calsalkey['SalaryID'],
                'SalaryYear' => $calsalkey['SalaryYear'],
                'SalaryMonth' => $calsalkey['SalaryMonth'],
                'DateFrom' => $calsalkey['DateFrom'],
                'DateTo' => $calsalkey['Dateto'],
                'PayPerDay' => $calsalkey['PayPerDay'],
                'CalculatedPresentDays' => $CalculatedPresentDays,
                'CalculatedoldPresentDays' => $CalculatedoldPresentDays,
                'PaymentStatus' => $calsalkey['PaymentStatus'],
                'PaymentDate' => $calsalkey['PaymentDate'],
                'PaymentReferenceFilepath' => $calsalkey['PaymentReferenceFilepath'],
                'ReferenceNo' => $calsalkey['requestno'],
                'incentivegrafting' => $calsalkey['incentivegrafting'],
                'incentiveseasional' => $calsalkey['incentiveseasional'],
                'overtimepayment' => $calsalkey['overtimepayment'],
                'otherpayment' => $calsalkey['otherpayment'],
                'basiclabourpayment' => $calsalkey['basiclabourpayment'],
                'NetSalary' => $calsalkey['NetSalary'],
                'PaidAmount' => $calsalkey['PaidAmount'],
                'locationid' => $calsalkey['LocationId'],
                'updated_by' => $calsalkey['updated_by'] ?? '',
                'updated_at' => $calsalkey['updated_at'] ?? '',
                'PaymentRemark' => $calsalkey['PaymentRemark'] ?? '',
                'bankname' => $calsalkey['bankname'] ?? '',
                'accountnumber' => $calsalkey['accountnumber'] ?? '',
                'ifsccode' => $calsalkey['ifsccode'] ?? '',
                'verifystatus' => $calsalkey['verifystatus'] ?? '',
                'PaymentMethod' => $calsalkey['PaymentMethod'] ?? 'Cash',
                'transactionno' => $calsalkey['transactionno'] ?? '',
                'otherinfo' => $mmEmpd
            );
        }
    }
    echo json_encode($calsallist);
} else if ($action == "CalculatedSalaryDataLabour_forreport") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $dateFrom = $_POST['datefrom'] ?? null;
    $dateTo = $_POST['dateto'] ?? null;
    $locationid = $_POST['locationid'] ?? '';

    if ($locationid == "all") {
        $shortby = "datebetween";
        $shortbyvalue = $dateFrom . "|" . $dateTo;
    } else {
        $shortby = "datebetweenwithlocationid";
        $shortbyvalue = $dateFrom . "|" . $dateTo . "|" . $locationid;
    }

    // echo "=====".$shortby."===".$shortbyvalue;
    // die();
    $shortbyvalue = $dateFrom . "|" . $dateTo . "|" . $locationid;
    $calculatessalary = getLabourCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
    $calsallist = array();
    if (count($calculatessalary) > 0) {
        foreach ($calculatessalary as $calsalkey) {
            $emplist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $calsalkey['EmployeeID']);
            if (count($emplist) > 0) {
                foreach ($emplist as $eemkey) {
                    $EmployeeID = $eemkey['EmployeeID'];
                    $paytoid = $eemkey['paytoid'];
                    $EmployeeName = $eemkey['FirstName'] . ' ' . $eemkey['MiddleName'] . ' ' . $eemkey['LastName'];
                }
            }

            // $mmEmpd = fetchLabourAttendanceifMultiplelocation($hrmconn, $companyid, $subcompanyid, $projectid, $calsalkey['EmployeeID'], $dateFrom, $dateTo);
            // print_r($mmEmpd);
            $mmEmpd = [];
            // echo "==".$filterValue =$dateFrom."|".$dateTo."|".$calsalkey['EmployeeID'];
            $attEmp = fetchLabourAttendanceWithGrouped($hrmconn, $companyid, $subcompanyid, $projectid, "datebetweenwithemployeeid", $dateFrom, $dateTo, $calsalkey['EmployeeID']);
            $CalculatedPresentDays = 0;
            $CalculatedoldPresentDays = 0;
            if (count($attEmp) > 0) {
                $CalculatedPresentDays = str_replace(".00", "", $attEmp[0]['TotalPresentDays']) ?? 0;
                $CalculatedoldPresentDays = str_replace(".00", "", $attEmp[0]['TotaloldPresentDays']) ?? 0;

            }
            // PaymentReferenceFilepath
            $calsallist[] = array('companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'EmployeeID' => $EmployeeID, 'EmployeeName' => $EmployeeName, 'SalaryID' => $calsalkey['SalaryID'], 'SalaryYear' => $calsalkey['SalaryYear'], 'SalaryMonth' => $calsalkey['SalaryMonth'], 'DateFrom' => $calsalkey['DateFrom'], 'DateTo' => $calsalkey['Dateto'], 'PayPerDay' => $calsalkey['PayPerDay'], 'CalculatedPresentDays' => $CalculatedPresentDays, 'CalculatedoldPresentDays' => $CalculatedoldPresentDays, 'PaymentStatus' => $calsalkey['PaymentStatus'], 'PaymentDate' => $calsalkey['PaymentDate'], 'PaymentReferenceFilepath' => $calsalkey['PaymentReferenceFilepath'], 'ReferenceNo' => $calsalkey['requestno'], 'incentivegrafting' => $calsalkey['incentivegrafting'], 'incentiveseasional' => $calsalkey['incentiveseasional'], 'overtimepayment' => $calsalkey['overtimepayment'], 'otherpayment' => $calsalkey['otherpayment'], 'basiclabourpayment' => $calsalkey['basiclabourpayment'], 'NetSalary' => $calsalkey['NetSalary'], 'locationid' => $calsalkey['LocationId'], 'otherinfo' => $mmEmpd);
        }
    }
    echo json_encode($calsallist);
} else if ($action == "PendingPaymentLabourWagesData") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $attyearmonth = $_POST['attyearmonth'] ?? null;
    $daterange = $_POST['daterange'] ?? null;
    $locationid = $_POST['locationid'] ?? '';
    $attyrmnex = explode("-", $attyearmonth);
    $attYear = $attyrmnex[0] ?? date('Y');
    $attMonth = $attyrmnex[1] ?? date('m');
    $dateFrom = "";
    $dateTo = "";

    if ($daterange == "fullmonth") {
        $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
        $dateTo = date("Y-m-t", strtotime($dateFrom));
    } else {
        $daterangeex = explode("|", $daterange);
        $dateFrom = trim($daterangeex[0] ?? '');
        $dateTo = trim($daterangeex[1] ?? '');
    }

    $params = [$companyid, $subcompanyid, $projectid, $dateFrom, $dateTo];
    $types = "sssss";
    $locClause = "";

    if ($locationid !== "all" && $locationid !== "") {
        $locClause = " AND s.LocationId = ? ";
        $params[] = $locationid;
        $types .= "s";
    }

    $sql = "SELECT 
                s.SalaryID, s.CompanyID, s.SubCompanyID, s.ProjectID, s.EmployeeID,
                s.SalaryYear, s.SalaryMonth, s.DateFrom, s.Dateto, s.PayPerDay,
                s.PresentDays, s.PaymentStatus, s.PaymentDate, s.requestno,
                s.incentivegrafting, s.incentiveseasional, s.overtimepayment, s.otherpayment,
                s.basiclabourpayment, s.NetSalary, s.PaidAmount, s.LocationId,
                CONCAT_WS(' ', TRIM(m.FirstName), NULLIF(TRIM(m.MiddleName), ''), TRIM(m.LastName)) AS EmployeeName
            FROM hrm_LabourCalculatedSalary s
            LEFT JOIN hrm_LabourMaster m 
                ON s.EmployeeID = m.EmployeeID 
               AND s.CompanyID = m.CompanyID 
               AND s.SubCompanyID = m.SubCompanyID 
               AND s.ProjectID = m.ProjectID
            WHERE ((s.PaymentStatus IS NULL OR s.PaymentStatus = '') OR ((s.PaymentStatus = 'Paid' OR s.PaymentStatus = 'paid' OR s.PaymentStatus = 'PAID') AND s.NetSalary > COALESCE(s.PaidAmount, 0)))
              AND s.CompanyID = ? AND s.SubCompanyID = ? AND s.ProjectID = ?
              AND s.DateFrom >= ? AND s.Dateto <= ?" . $locClause . "
            ORDER BY s.SalaryYear DESC, s.SalaryMonth DESC, m.FirstName ASC";

    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $calsallist = array();
    while ($row = $result->fetch_assoc()) {
        $empName = !empty(trim($row['EmployeeName'])) ? $row['EmployeeName'] : $row['EmployeeID'];
        $calcPresent = str_replace(".00", "", $row['PresentDays'] ?? "0");

        $calsallist[] = array(
            'companyid' => $row['CompanyID'], 
            'subcompanyid' => $row['SubCompanyID'], 
            'projectid' => $row['ProjectID'], 
            'EmployeeID' => $row['EmployeeID'], 
            'EmployeeName' => $empName, 
            'SalaryID' => $row['SalaryID'], 
            'SalaryYear' => $row['SalaryYear'], 
            'SalaryMonth' => $row['SalaryMonth'], 
            'DateFrom' => $row['DateFrom'], 
            'DateTo' => $row['Dateto'], 
            'PayPerDay' => $row['PayPerDay'], 
            'CalculatedPresentDays' => $calcPresent, 
            'PaymentStatus' => $row['PaymentStatus'], 
            'PaymentDate' => $row['PaymentDate'], 
            'ReferenceNo' => $row['requestno'], 
            'incentivegrafting' => $row['incentivegrafting'], 
            'incentiveseasional' => $row['incentiveseasional'], 
            'overtimepayment' => $row['overtimepayment'], 
            'otherpayment' => $row['otherpayment'], 
            'basiclabourpayment' => $row['basiclabourpayment'], 
            'NetSalary' => $row['NetSalary'], 
            'PaidAmount' => $row['PaidAmount'] ?? 0, 
            'otherinfo' => []
        );
    }
    $stmt->close();
    echo json_encode($calsallist);
} else if ($action == "PaidPaymentLabourWagesData") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $attyearmonth = $_POST['attyearmonth'] ?? null;
    $daterange = $_POST['daterange'] ?? null;
    $locationid = $_POST['locationid'] ?? '';
    $attyrmnex = explode("-", $attyearmonth);
    $attYear = $attyrmnex[0] ?? date('Y');
    $attMonth = $attyrmnex[1] ?? date('m');
    $dateFrom = "";
    $dateTo = "";

    if ($daterange == "fullmonth") {
        $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
        $dateTo = date("Y-m-t", strtotime($dateFrom));
    } else {
        $daterangeex = explode("|", $daterange);
        $dateFrom = trim($daterangeex[0] ?? '');
        $dateTo = trim($daterangeex[1] ?? '');
    }

    $params = [$companyid, $subcompanyid, $projectid, $dateFrom, $dateTo];
    $types = "sssss";
    $locClause = "";

    if ($locationid !== "all" && $locationid !== "") {
        $locClause = " AND s.LocationId = ? ";
        $params[] = $locationid;
        $types .= "s";
    }

    $sql = "SELECT 
                s.SalaryID, s.CompanyID, s.SubCompanyID, s.ProjectID, s.EmployeeID,
                s.SalaryYear, s.SalaryMonth, s.DateFrom, s.Dateto, s.PayPerDay,
                s.PresentDays, s.PaymentStatus, s.PaymentDate, s.requestno,
                s.incentivegrafting, s.incentiveseasional, s.overtimepayment, s.otherpayment,
                s.basiclabourpayment, s.NetSalary, s.PaidAmount, s.LocationId, s.updated_by, s.PaymentReferenceFilepath,
                CONCAT_WS(' ', TRIM(m.FirstName), NULLIF(TRIM(m.MiddleName), ''), TRIM(m.LastName)) AS EmployeeName
            FROM hrm_LabourCalculatedSalary s
            LEFT JOIN hrm_LabourMaster m 
                ON s.EmployeeID = m.EmployeeID 
               AND s.CompanyID = m.CompanyID 
               AND s.SubCompanyID = m.SubCompanyID 
               AND s.ProjectID = m.ProjectID
            WHERE (s.PaymentStatus = 'Paid' OR s.PaymentStatus = 'paid' OR s.PaymentStatus = 'PAID')
              AND s.CompanyID = ? AND s.SubCompanyID = ? AND s.ProjectID = ?
              AND s.DateFrom >= ? AND s.Dateto <= ?" . $locClause . "
            ORDER BY s.SalaryYear DESC, s.SalaryMonth DESC, m.FirstName ASC";

    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $calsallist = array();
    while ($row = $result->fetch_assoc()) {
        $empName = !empty(trim($row['EmployeeName'])) ? $row['EmployeeName'] : $row['EmployeeID'];
        $calcPresent = str_replace(".00", "", $row['PresentDays'] ?? "0");

        $calsallist[] = array(
            'companyid' => $row['CompanyID'], 
            'subcompanyid' => $row['SubCompanyID'], 
            'projectid' => $row['ProjectID'], 
            'EmployeeID' => $row['EmployeeID'], 
            'EmployeeName' => $empName, 
            'SalaryID' => $row['SalaryID'], 
            'SalaryYear' => $row['SalaryYear'], 
            'SalaryMonth' => $row['SalaryMonth'], 
            'DateFrom' => $row['DateFrom'], 
            'DateTo' => $row['Dateto'], 
            'PayPerDay' => $row['PayPerDay'], 
            'CalculatedPresentDays' => $calcPresent, 
            'PaymentStatus' => $row['PaymentStatus'], 
            'PaymentDate' => $row['PaymentDate'], 
            'PaidAmount' => $row['PaidAmount'] ?? 0, 
            'updated_by' => $row['updated_by'] ?? '', 
            'PaymentReferenceFilepath' => $row['PaymentReferenceFilepath'] ?? '', 
            'ReferenceNo' => $row['requestno'], 
            'incentivegrafting' => $row['incentivegrafting'], 
            'incentiveseasional' => $row['incentiveseasional'], 
            'overtimepayment' => $row['overtimepayment'], 
            'otherpayment' => $row['otherpayment'], 
            'basiclabourpayment' => $row['basiclabourpayment'], 
            'NetSalary' => $row['NetSalary'], 
            'otherinfo' => ""
        );
    }
    $stmt->close();
    echo json_encode($calsallist);
} else if ($action == "GetKPISummaryData") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $attyearmonth = $_POST['attyearmonth'] ?? null;
    $daterange = $_POST['daterange'] ?? null;
    $locationid = $_POST['locationid'] ?? '';
    
    $attyrmnex = explode("-", $attyearmonth);
    $attYear = $attyrmnex[0] ?? null;
    $attMonth = $attyrmnex[1] ?? null;
    $dateFrom = "";
    $dateTo = "";
    
    if ($daterange == "fullmonth") {
        $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
        $dateTo = date("Y-m-t", strtotime($dateFrom));
    } else {
        $daterangeex = explode("|", $daterange);
        $dateFrom = trim($daterangeex[0] ?? '');
        $dateTo = trim($daterangeex[1] ?? '');
    }

    $loc_sql = "";
    $params = [$companyid, $subcompanyid, $projectid, $dateFrom, $dateTo];
    $types = "sssss";
    
    if ($locationid !== "all" && $locationid !== "") {
        $loc_sql = " AND LocationId = ? ";
        $params[] = $locationid;
        $types .= "s";
    }

    $sql = "SELECT 
        COALESCE(SUM(CASE WHEN (PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') THEN PaidAmount ELSE 0 END), 0) AS paid_total,
        COALESCE(SUM(CASE WHEN (PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') THEN PresentDays ELSE 0 END), 0) AS paid_days,
        COUNT(DISTINCT CASE WHEN (PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') THEN EmployeeID END) AS paid_labour_count,

        COALESCE(SUM(CASE WHEN ((PaymentStatus IS NULL OR PaymentStatus = '') OR ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0))) THEN (NetSalary - COALESCE(PaidAmount, 0)) ELSE 0 END), 0) AS pending_total,
        COALESCE(SUM(CASE WHEN ((PaymentStatus IS NULL OR PaymentStatus = '') OR ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0))) THEN PresentDays ELSE 0 END), 0) AS pending_days,
        COUNT(DISTINCT CASE WHEN ((PaymentStatus IS NULL OR PaymentStatus = '') OR ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0))) THEN EmployeeID END) AS pending_labour_count,

        COALESCE(SUM(PresentDays), 0) AS total_days,
        COUNT(DISTINCT EmployeeID) AS total_labours,

        COALESCE(SUM(CASE WHEN ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0)) THEN PaidAmount ELSE 0 END), 0) AS partial_paid,
        COALESCE(SUM(CASE WHEN ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0)) THEN NetSalary ELSE 0 END), 0) AS partial_wages,
        COALESCE(SUM(CASE WHEN ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0)) THEN (NetSalary - COALESCE(PaidAmount, 0)) ELSE 0 END), 0) AS partial_pending,
        COALESCE(SUM(CASE WHEN ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0)) THEN PresentDays ELSE 0 END), 0) AS partial_days,
        COUNT(DISTINCT CASE WHEN ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0)) THEN EmployeeID END) AS partial_labours
    FROM hrm_LabourCalculatedSalary 
    WHERE CompanyID = ? AND SubCompanyID = ? AND ProjectID = ? AND DateFrom >= ? AND Dateto <= ?" . $loc_sql;

    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $response = [
        "grandTotalPaid" => (float)($res['paid_total'] ?? 0),
        "paidDays" => (float)($res['paid_days'] ?? 0),
        "paidLabourCount" => (int)($res['paid_labour_count'] ?? 0),
        
        "grandTotalPending" => (float)($res['pending_total'] ?? 0),
        "pendingDays" => (float)($res['pending_days'] ?? 0),
        "pendingLabourCount" => (int)($res['pending_labour_count'] ?? 0),
        
        "grandTotalWages" => (float)($res['paid_total'] ?? 0) + (float)($res['pending_total'] ?? 0),
        "grandTotalLabours" => (int)($res['total_labours'] ?? 0),
        "grandTotalDays" => (float)($res['total_days'] ?? 0),

        "partialTotalPaid" => (float)($res['partial_paid'] ?? 0),
        "partialTotalWages" => (float)($res['partial_wages'] ?? 0),
        "partialTotalPending" => (float)($res['partial_pending'] ?? 0),
        "partialTotalDays" => (float)($res['partial_days'] ?? 0),
        "partialTotalLabours" => (int)($res['partial_labours'] ?? 0)
    ];

    echo json_encode($response);
} else if ($action == "InsertOrUpdateCalculatedSalaryLabour_Payroll") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'];
    $attyearmonth = $_POST['attyearmonth'] ?? '';
    $daterange = $_POST['daterange'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    $attyrmnex = explode("-", $attyearmonth);
    $attYear = $attyrmnex[0];
    $attMonth = $attyrmnex[1];
    if ($daterange == "fullmonth" || $daterange == "all") {
        $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
        $dateTo = date("Y-m-t", strtotime($dateFrom));
    } else {
        $daterangeex = explode("|", $daterange);
        $dateFrom = $daterangeex[0];
        $dateTo = $daterangeex[1];
    }
    $empSalaryData = json_decode($_POST['empSalaryData'], true) ?? '';
    $SalaryResultMsg = [];
    if (count($empSalaryData) > 0) {
        foreach ($empSalaryData as $empSalkey) {
            $PayPerDay = $empSalkey['payperday'] ?? 0;
            $CalculatedPresentDays = $empSalkey['presentdays'] ?? 0;
            $incentivegrafting = $empSalkey['incentivegrafting'] ?? 0;
            $incentiveseasional = $empSalkey['incentiveseasional'] ?? 0;
            $overtimepayment = $empSalkey['overtimepayment'] ?? 0;
            $otherpayment = $empSalkey['otherpayment'] ?? 0;
            $basiclabourpayment = $empSalkey['basiclabourpayment'] ?? 0;
            $NetPaySalary = $empSalkey['netpayableamount'] ?? 0;
            $emplist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $empSalkey['employeeid']);
            // print_r($emplist);
            $deductionData = [];
            if (count($emplist) > 0) {
                $breakdowndata = array();
                $empname = "";
                $paytoid = "";
                foreach ($emplist as $eemkey) {
                    $EmployeeID = $eemkey['EmployeeID'];
                    $paytoid = $eemkey['paytoid'];
                    $empname = $eemkey['FirstName'] . ' ' . $eemkey['MiddleName'] . ' ' . $eemkey['LastName'];
                    $salaryData = [
                        'EmployeeID' => $empSalkey['employeeid'],
                        'LocationId' => $locationid,
                        'CompanyID' => $companyid,
                        'SubCompanyID' => $subcompanyid,
                        'ProjectID' => $projectid,
                        'SalaryYear' => $attYear,
                        'SalaryMonth' => $attMonth,
                        'dateFrom' => $dateFrom,
                        'dateTo' => $dateTo,
                        'PayPerDay' => $PayPerDay,
                        'PresentDays' => $CalculatedPresentDays,
                        'incentivegrafting' => $incentivegrafting,
                        'incentiveseasional' => $incentiveseasional,
                        'overtimepayment' => $overtimepayment,
                        'otherpayment' => $otherpayment,
                        'basiclabourpayment' => $basiclabourpayment,
                        'NetPaySalary' => $NetPaySalary,
                        'Comments' => 'Labour Payment Processed between ' . $dateFrom . ' and ' . $dateTo . ' for location ' . $LocationId
                    ];

                    $jSalaryResult = insertOrUpdateLabourCalculatedSalary($hrmconn, $locationid, $salaryData);
                    $SalaryResult = json_decode($jSalaryResult, true);
                    // print_r($salaryData);
                    $SalaryResultMsg[] = array('status' => $SalaryResult['status'], 'message' => $SalaryResult['message'], 'employeeid' => $empSalkey['employeeid'], 'employeename' => $empSalkey['employeename']);

                }
            } else {
                $SalaryResultMsg[] = array('status' => 'Error', 'message' => 'Details are not available from labour master  ' . $empSalkey['employeename'], 'employeeid' => $empSalkey['employeeid'], 'employeename' => $empSalkey['employeename']);
            }
        }
    }
    echo json_encode($SalaryResultMsg);
} else if ($action == "LabourAttendanceLocationList") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $sortBy = $_POST['shortby'];
    $sortByValue = $_POST['shortbyvalue'];
    $locationmaster = getLabourLocationList($hrmconn, $projectid, $sortBy, $sortByValue);
    echo json_encode($locationmaster);
} else if ($action == "LabourAttendanceMonthYearList") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $shortby = $_POST['shortby'] ?? '';
    $slyrmlist = getLabourAttendanceMonthYearList($hrmconn, $companyid, $subcompanyid, $projectid, $shortby);
    echo json_encode($slyrmlist);
} else if ($action == "LabourAttendanceDateRangeList") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $yearmonth = $_POST['yearmonth'] ?? null;
    $SalaryYear = null;
    $SalaryMonth = null;
    if (!empty($yearmonth) && strpos($yearmonth, '-') !== false) {
        $yearmonthexp = explode("-", $yearmonth);
        $SalaryYear = (int) $yearmonthexp[0];
        $SalaryMonth = (int) $yearmonthexp[1];
    }
    $sldtrange = getLabourAttendanceDateRange($hrmconn, $companyid, $subcompanyid, $projectid, $SalaryYear, $SalaryMonth);
    echo json_encode($sldtrange);
} else if ($action == "LabourSalaryMonthYearList") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $shortby = $_POST['shortby'] ?? '';
    $slyrmlist = getLabourSalaryMonthYearList($hrmconn, $companyid, $subcompanyid, $projectid, $shortby);
    echo json_encode($slyrmlist);
} else if ($action == "LabourSalaryDateRangeList") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $yearmonth = $_POST['yearmonth'] ?? null;
    $SalaryYear = null;
    $SalaryMonth = null;
    if (!empty($yearmonth) && strpos($yearmonth, '-') !== false) {
        $yearmonthexp = explode("-", $yearmonth);
        $SalaryYear = (int) $yearmonthexp[0];
        $SalaryMonth = (int) $yearmonthexp[1];
    }
    $sldtrange = getLabourSalaryDateRange($hrmconn, $companyid, $subcompanyid, $projectid, $SalaryYear, $SalaryMonth);
    echo json_encode($sldtrange);
} else if ($action == "getLabourSalaryLocationList") {
    $companyid = $_POST['companyid'] ?? 'all';
    $subcompanyid = $_POST['subcompanyid'] ?? 'all';
    $projectid = $_POST['projectid'] ?? 'all';
    $sortBy = $_POST['shortby'] ?? 'all';
    $sortByValue = $_POST['shortbyvalue'] ?? 'all';
    $locationmaster = getLabourSalaryLocationList($hrmconn, $projectid, $sortBy, $sortByValue);
    if (!is_array($locationmaster)) {
        $locationmaster = [];
    }
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($locationmaster);
    exit;
} else if ($action == "LabourPendingSalaryLocationList") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $yearmonth = $_POST['yearmonth'] ?? '';
    $daterange = $_POST['daterange'] ?? '';
    $locationmaster = getLabourPendingSalaryLocationList($hrmconn, $companyid, $subcompanyid, $projectid, $yearmonth, $daterange);
    if (!is_array($locationmaster)) {
        $locationmaster = [];
    }
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($locationmaster);
    exit;
} else if ($action == "LabourPendingBookFromCalculatedSalaryData") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $attyearmonth = $_POST['attyearmonth'] ?? '';
    $daterange = $_POST['daterange'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    $attyrmnex = explode("-", $attyearmonth);
    $attYear = $attyrmnex[0];
    $attMonth = $attyrmnex[1];
    $dateFrom = "";
    $dateTo = "";

    if ($locationid == "all") {
        if ($daterange == "fullmonth") {
            $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
            $dateTo = date("Y-m-t", strtotime($dateFrom));
            $filterBy = "yearmonth";
            $filterV1 = $attYear;
            $filterV2 = $attMonth;
            $filterV3 = "";
        } else {
            $daterangeex = explode("|", $daterange);
            $dateFrom = trim($daterangeex[0]);
            $dateTo = trim($daterangeex[1]);
            $filterBy = "datebetween";
            $filterV1 = $dateFrom;
            $filterV2 = $dateTo;
            $filterV3 = "";
        }
    } else {
        if ($daterange == "fullmonth") {
            $dateFrom = sprintf("%04d-%02d-01", $attYear, $attMonth);
            $dateTo = date("Y-m-t", strtotime($dateFrom));
            $filterBy = "yearmonthwithlocationid";
            $filterV1 = $attYear;
            $filterV2 = $attMonth;
            $filterV3 = $locationid;
        } else {
            $daterangeex = explode("|", $daterange);
            $dateFrom = trim($daterangeex[0]);
            $dateTo = trim($daterangeex[1]);
            $filterBy = "datebetweenwithlocationid";
            $filterV1 = $dateFrom;
            $filterV2 = $dateTo;
            $filterV3 = $locationid;
        }
    }

    // echo $filterBy.'=='.$filterV1.'=='.$filterV2.'=='.$filterV3;
    // Validate dates
    $lbcalculatessalary = array();
    if (!empty($dateFrom) && !empty($dateTo)) {
        $lbcalculatessalary = getLabourPendingSalaryForapproval($hrmconn, $companyid, $subcompanyid, $projectid, $filterBy, $filterV1, $filterV2, $filterV3);
    }
    // print_r($lbcalculatessalary);
    $calsallist = array();
    if (count($lbcalculatessalary) > 0) {
        foreach ($lbcalculatessalary as $calsalkey) {
            $emplist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $calsalkey['EmployeeID']);
            if (count($emplist) > 0) {
                foreach ($emplist as $eemkey) {
                    $EmployeeID = $eemkey['EmployeeID'];
                    $paytoid = $eemkey['paytoid'];
                    $EmployeeName = $eemkey['FirstName'] . ' ' . $eemkey['MiddleName'] . ' ' . $eemkey['LastName'];
                }
            }
            // $attEmp = fetchLabourAttendanceWithGrouped($hrmconn, $companyid, $subcompanyid, $projectid, "datebetweenwithemployeeid", $dateFrom, $dateTo, $calsalkey['EmployeeID']);

            // $CalculatedPresentDays = 0;
            // if (count($attEmp) > 0) {
            //     $CalculatedPresentDays = str_replace(".00", "", $attEmp[0]['TotalPresentDays']) ?? 0;
            // }

            $calsallist[] = array('companyid' => $companyid, 'subcompanyid' => $subcompanyid, 'projectid' => $projectid, 'EmployeeID' => $EmployeeID, 'EmployeeName' => $EmployeeName, 'SalaryID' => $calsalkey['SalaryID'], 'SalaryYear' => $calsalkey['SalaryYear'], 'SalaryMonth' => $calsalkey['SalaryMonth'], 'DateFrom' => $calsalkey['DateFrom'], 'DateTo' => $calsalkey['Dateto'], 'PayPerDay' => $calsalkey['PayPerDay'], 'NetSalary' => $calsalkey['NetSalary'], 'CalculatedPresentDays' => $calsalkey['PresentDays'], 'ReferenceNo' => $calsalkey['requestno'], 'LocationId' => $calsalkey['LocationId']);
        }
    }
    echo json_encode($calsallist);
} else if ($action == "LabourCreateBookFromCalculatedSalaryData") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'];
    $loginid = $_POST['loginid'];
    $subprojectid = 0;
    $empSalaryData = json_decode($_POST['empSalaryData'], true);
    $rrmsg = array();
    $totalcashamount = 0;
    $ttlsalary = 0;
    $salaryyear = "";
    $salarymonth = "";
    $datefrom = "";
    $dateto = "";
    $paytoinfo = getAccountDetails($conn, $companyid, $subcompanyid, $projectid, "cashaccountwithlikepayto", "labour payment");
    if (count($paytoinfo) == 1) {
        $paytoid = $paytoinfo[0]['paytoid'];
        $paytoaccountid = $paytoinfo[0]['accountid'];
        $paytoname = $paytoinfo[0]['paytoname'];
        $paytoaccountno = $paytoinfo[0]['accountno'];
        $paytobankname = $paytoinfo[0]['bankname'];
    } else {
        $rrmsg = array('status' => 'error', 'message' => 'Cash Account did not available for labour payment');
        echo json_encode($rrmsg);
        die();
    }
    $TextRemark = "";
    if (count($empSalaryData) > 0) {
        foreach ($empSalaryData as $sstkey) {
            $ttlsalary += $sstkey['netsalary'];
            $salaryyear = $sstkey['salaryyear'];
            $salarymonth = $sstkey['salarymonth'];
            $datefrom = $sstkey['datefrom'];
            $dateto = $sstkey['dateto'];
            $emplist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $sstkey['employeeid']);
            $empname = "";
            $emppaytoid = "";
            if (count($emplist) > 0) {
                $breakdowndata = array();
                foreach ($emplist as $eemkey) {
                    $EmployeeID = $eemkey['EmployeeID'];
                    $EmpEmail = $eemkey['Email'];
                    $EmpMobileNo = $eemkey['MobileNo'];
                    $empname = $eemkey['FirstName'] . ' ' . $eemkey['MiddleName'] . ' ' . $eemkey['LastName'];
                    if ($eemkey['paytoid']) {
                        $emppaytoid = $eemkey['paytoid'];
                    } else {
                        $BankName = '';
                        $AccountNumber = '';
                        $IFSCCode = '';
                        $SWIFTCode = '';
                        $IBAN = '';
                        $SortCode = '';
                        $paytoin = savetodbnewpayto_hrm($conn, $empname, $AccountNumber, $BankName, $IFSCCode, $IBAN, $SWIFTCode, $SortCode, $companyid, $subcompanyid, $projectid, $EmpMobileNo, $EmpEmail, $loginname);
                        $emppaytoid = $paytoin[0]['paytoid'];
                        if ($emppaytoid) {
                            $hrmconn->query("UPDATE hrm_LabourMaster SET paytoid='" . $emppaytoid . "' WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $companyid . "' AND subcompanyid = '" . $subcompanyid . "' AND projectid = '" . $projectid . "' and EmployeeID='" . $EmployeeID . "'");
                        }
                    }
                }
            }
            $daterangwithid = $sstkey['employeeid'] . "|" . $sstkey['datefrom'] . "|" . $sstkey['dateto'] . "|" . $sstkey['locationid'];
            $lastinserted = getLabourCalculatedSalary($hrmconn, $companyid, $subcompanyid, $projectid, 'datebetweenWithEmployeeIdlocationid', $daterangwithid);
            // print_r($lastinserted);
            $NetSalary = $lastinserted[0]['NetSalary'];
            $SalaryID = $lastinserted[0]['SalaryID'];
            $TextRemark = $lastinserted[0]['Comments'];
            $payslipdoc = "";
            if ($NetSalary > 0 && $empname) {
                $totalcashamount += $NetSalary;
            } else {
                $rrmsg = array('status' => 'error', 'message' => 'Some details are missing or incorrect for creating a book entry to transfer the labour wages. ' . $empname);
            }
        }

        if ($totalcashamount == $ttlsalary) {
            $purposedetails = getpurposenamewithcat_hrm($mconn, $companyid, $subcompanyid, $projectid, "purposecategoryid", '6');
            if (count($purposedetails) > 0) {
                $purposecategory = $purposedetails[0]['pcategory'];
                $purposecategoryid = $purposedetails[0]['pcategorycode'];
                $purposesubcategory = $purposedetails[0]['psub_category'];
                $purposesubcategoryid = $purposedetails[0]['psub_categoryid'];
                $purposename = $purposedetails[0]['purpose'];
                $purposecode = $purposedetails[0]['purposecode'];
                $paymenttype = $purposedetails[0]['pcategory'];
            }

            $payslipdoc = "";
            $breakdowndata[] = array('beneficiaryname' => $paytoname, 'beneficiaryamount' => $totalcashamount, 'purposecode' => $purposecode ?? '', 'textremark' => $TextRemark, 'document' => $payslipdoc);
            $transaction_type = "Cash";

            $sspp = reconstructBookFromSalary($conn, $companyid, $subcompanyid, $projectid, $subprojectid, $totalcashamount, $paytoid, '', $transaction_type, $breakdowndata, $loginname);
            if ($sspp['status'] == "success" && $sspp['referenceno']) {
                foreach ($empSalaryData as $sstkey) {
                    $hrmconn->query("UPDATE hrm_LabourCalculatedSalary SET requestno='" . $sspp['referenceno'] . "' where CompanyID='" . $companyid . "' and SubCompanyID='" . $subcompanyid . "' and ProjectID='" . $projectid . "' and EmployeeID='" . $sstkey['employeeid'] . "' and SalaryYear='" . $salaryyear . "' and SalaryMonth='" . $salarymonth . "' and DateFrom='" . $datefrom . "' and Dateto='" . $dateto . "'");
                }

                $data = [
                    "idate" => date('Y-m-d'),
                    "companyid" => $companyid,
                    "subcompanyid" => $subcompanyid,
                    "projectid" => $projectid,
                    "from_accountid" => "",
                    "to_accountid" => $paytoaccountid,
                    "request_no" => "",
                    "reference_no" => $sspp['referenceno'],
                    "paytoid" => $paytoid,
                    "paytoname" => $paytoname,
                    "cash_amount" => $totalcashamount,
                    "purpose_categoryid" => $purposecategoryid,
                    "purpose_category" => $purposecategory,
                    "purpose_subcategoryid" => $purposesubcategoryid,
                    "purpose_subcategory" => $purposesubcategory,
                    "purpose" => $purposename,
                    "purpose_code" => $purposecode,
                    "beneficiary_name" => $paytoname,
                    "description" => $TextRemark
                ];
                $result = insertLabourPaymentAccount($conn, $data);
                if ($result['status'] == "success") {
                    $rrmsg = array('status' => 'success', 'message' => 'Labour cash payment book created successfully');
                }
            } else {
                $rrmsg = array('status' => 'error', 'message' => 'Cash payment was not made for the transfer of labour wages. ' . $empname);
            }
        } else {
            $rrmsg = array('status' => 'error', 'message' => 'Total cash amount and total salary amount did not matched of. ' . $empname);
        }
    } else {
        $rrmsg = array('status' => 'error', 'message' => 'Data not available');
    }
    echo json_encode($rrmsg);

} else if ($action == "LabourRegistration") {
    @ini_set("display_errors", 0);
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $addedby = $_POST['addedby'] ?? null;

    $seriesid = $_POST['seriesid'] ?? null;
    $firstName = $_POST['firstName'] ?? null;
    $middleName = $_POST['middleName'] ?? null;
    $lastName = $_POST['lastName'] ?? null;
    $fatherName = $_POST['fatherName'] ?? null;
    $motherName = $_POST['motherName'] ?? null;
    $spouseName = $_POST['spouseName'] ?? null;
    $mobileNo = $_POST['mobileNo'] ?? null;
    $emergencyContact = $_POST['emergencyContact'] ?? null;
    $maritalStatus = $_POST['maritalStatus'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $workingLocation = $_POST['workingLocation'] ?? null;
    $stateid           = !empty($_POST['stateid']) ? $_POST['stateid'] : null;
    $districtid        = !empty($_POST['districtid']) ? $_POST['districtid'] : null;
    $tahseel           = !empty($_POST['tahseel']) ? $_POST['tahseel'] : null;
    $city              = !empty($_POST['city']) ? $_POST['city'] : null;
    $current_address   = $_POST['current_address'] ?? null;
    $permanent_address = $_POST['permanent_address'] ?? null;
    $address           = !empty($current_address) ? $current_address : ($_POST['address'] ?? null);
    $grafting_skill    = $_POST['grafting_skill'] ?? 'no';

    $bankName = $_POST['bankName'] ?? null;
    $accountNumber = $_POST['accountNumber'] ?? null;
    $ifscCode = $_POST['ifscCode'] ?? null;

    $distpath = "../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/";

    // 1. Handle Photo Upload Before Save
    $profilePhotoPath = null;
    if (isset($_FILES['labourPhoto']) && $_FILES['labourPhoto']['error'] == 0) {
        $profilePhotoPath = uploadFile('labourPhoto', $distpath . 'labour/photos/');
    }

    // 2. Handle ID Proof Upload Before Save
    $idProofPath = null;
    $idProofType = $_POST['idProofType'] ?? null;
    $idProofNo   = trim($_POST['idProofNo'] ?? '');
    if (isset($_FILES['idProofDoc']) && $_FILES['idProofDoc']['error'] == 0) {
        $idProofPath = uploadFile('idProofDoc', $distpath . 'labour/id_proofs/');
    }

    // 3. Handle Bank Document Upload Before Save
    $bankDocPath = null;
    if (isset($_FILES['bankDoc']) && $_FILES['bankDoc']['error'] == 0) {
        $bankDocPath = uploadFile('bankDoc', $distpath . 'labour/bank_docs/');
    }

    $employeeIDParam = $_POST['employeeID'] ?? null;
    $isUpdate = !empty($employeeIDParam);

    if (empty($bankDocPath) && $isUpdate) {
        // Query existing bank document path to avoid clearing it during updates
        $existBankQuery = "SELECT cancelchequepath FROM hrm_labourbankdetails WHERE labourid = ? AND (tedit IS NULL OR tedit = '') LIMIT 1";
        $stmtExistBank = $hrmconn->prepare($existBankQuery);
        if ($stmtExistBank) {
            $stmtExistBank->bind_param("s", $employeeIDParam);
            $stmtExistBank->execute();
            $resExistBank = $stmtExistBank->get_result()->fetch_assoc();
            if ($resExistBank) {
                $bankDocPath = $resExistBank['cancelchequepath'];
            }
            $stmtExistBank->close();
        }
        if (empty($bankDocPath)) {
            // Fallback to legacy hrm_EmployeeBankDetails table
            $existBankQueryOld = "SELECT CancelChequePath FROM hrm_EmployeeBankDetails WHERE EmployeeID = ? AND (tedit IS NULL OR tedit = '') LIMIT 1";
            $stmtExistBankOld = $hrmconn->prepare($existBankQueryOld);
            if ($stmtExistBankOld) {
                $stmtExistBankOld->bind_param("s", $employeeIDParam);
                $stmtExistBankOld->execute();
                $resExistBankOld = $stmtExistBankOld->get_result()->fetch_assoc();
                if ($resExistBankOld) {
                    $bankDocPath = $resExistBankOld['CancelChequePath'];
                }
                $stmtExistBankOld->close();
            }
        }
    }

    $employeeData = [
        "companyid" => $companyid,
        "subcompanyid" => $subcompanyid,
        "projectid" => $projectid,
        "seriesid" => $seriesid,
        "FirstName" => $firstName,
        "MiddleName" => $middleName,
        "LastName" => $lastName,
        "FatherName" => $fatherName,
        "MotherName" => $motherName,
        "SpouseName" => $spouseName,
        "MobileNo" => $mobileNo,
        "MaritalStatus" => $maritalStatus,
        "Gender" => $gender,
        "AddressLine1" => $current_address,
        "AddressLine2" => $permanent_address,
        "stateid" => $stateid,
        "districtid" => $districtid,
        "tahseel" => $tahseel,
        "city" => $city,
        "address" => $address,
        "graftingskill" => $grafting_skill,
        "locationid" => $workingLocation,
        "addedby" => $addedby,
        "EmployeeID" => $employeeIDParam,
    ];
    // Route idProofNo to correct column based on type (only when provided)
    if (!empty($idProofNo)) {
        if ($idProofType === 'Aadhar Card') {
            $employeeData['AadharNumber'] = $idProofNo;
        } elseif ($idProofType === 'PAN Card') {
            $employeeData['PanNumber'] = $idProofNo;
        }
    }

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    @ini_set("display_errors", 0);
    ob_start();
    $hrmconn->begin_transaction();
    try {
        $empsaverst = saveOrUpdateLabourEmployee($hrmconn, $employeeData);
        if ($empsaverst['success']) {
            $employeeID = $empsaverst['employeeID'];

            // Handle Profile Photo Upload
            if ($profilePhotoPath) {
                insertOrUpdateLabourDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'ProfilePhoto', $profilePhotoPath);
            }

            // Handle ID Proof Upload
            if ($idProofPath) {
                $idDocLabel = "IDProof_" . ($idProofType ?: "Doc");
                insertOrUpdateLabourDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $idDocLabel, $idProofPath);
            }

            // Handle Emergency Contact
            if ($emergencyContact) {
                insertOrUpdateLabourContactPersonDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'Emergency Contact', '', $emergencyContact, '', '', '', '', 'yes');
            }

            // Handle Bank Details
            if ($bankName || $accountNumber || $ifscCode || $bankDocPath) {
                insertOrUpdateBankDetails(
                    $hrmconn, $companyid, $subcompanyid, $projectid, $employeeID,
                    $bankName, $accountNumber, $ifscCode, '', '', '', 'INR', $bankDocPath
                );
            }

            // Handle Reference Persons
            $ref_names = $_POST['ref_name'] ?? [];
            $ref_relations = $_POST['ref_relation'] ?? [];
            $ref_mobiles = $_POST['ref_mobile'] ?? [];
            $ref_doc_types = $_POST['ref_doc_type'] ?? [];

            for ($i = 0; $i < count($ref_names); $i++) {
                if (!empty($ref_names[$i])) {
                    insertOrUpdateLabourContactPersonDetails(
                        $hrmconn, $companyid, $subcompanyid, $projectid, $employeeID,
                        $ref_names[$i], $ref_relations[$i], $ref_mobiles[$i],
                        '', '', '', '', ($i === 0 ? 'yes' : 'no')
                    );

                    // Handle Reference Document Upload
                    if (isset($_FILES['ref_doc_file']['name'][$i]) && $_FILES['ref_doc_file']['error'][$i] == 0) {
                        $refDir = $distpath . 'labour/reference_docs/';
                        if (!is_dir($refDir)) mkdir($refDir, 0777, true);
                        
                        $refFileName = uniqid() . "_" . $_FILES['ref_doc_file']['name'][$i];
                        $refDestPath = $refDir . $refFileName;
                        
                        if (move_uploaded_file($_FILES['ref_doc_file']['tmp_name'][$i], $refDestPath)) {
                            $savedRefPath = str_replace("../", "", $refDestPath);
                            $refDocTypeLabel = "Ref_" . ($ref_doc_types[$i] ?: "Doc") . "_" . $ref_names[$i];
                            insertOrUpdateLabourDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $refDocTypeLabel, $savedRefPath);
                        }
                    }
                }
            }

            $hrmconn->commit();

            // Check, create or update paytoid in tbl_payto_master and hrm_LabourMaster
            $empFullName = trim($firstName . ' ' . ($middleName ? $middleName . ' ' : '') . $lastName);
            if (!empty($empFullName)) {
                $paytoin = savetodbnewpayto_hrm($conn, $empFullName, $accountNumber, $bankName, $ifscCode, '', '', '', $companyid, $subcompanyid, $projectid, $mobileNo, '', $addedby);
                if (!empty($paytoin[0]['paytoid'])) {
                    $emppaytoid = $paytoin[0]['paytoid'];
                    $hrmconn->query("UPDATE hrm_LabourMaster SET paytoid='" . $conn->real_escape_string($emppaytoid) . "' WHERE EmployeeID='" . $hrmconn->real_escape_string($employeeID) . "'");
                }
            }

            // Generate ID Card (Wrapped in try-catch to avoid breaking the whole response)
            $idCardPath = "";
            try {
                $idCardPath = generateLabourIDCard($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $distpath);
            } catch (Exception $e_pdf) {
                // Log PDF error but don't fail registration
                error_log("PDF Generation Error: " . $e_pdf->getMessage());
            }

            $output = ob_get_clean();
            if (!empty($output) && strpos($output, 'TCPDF ERROR') !== false) {
                $rst = array('status' => 'Error', 'message' => ($isUpdate ? 'Labour details updated, but ID Card generation failed: ' : 'Labour registered, but ID Card generation failed: ') . strip_tags($output));
            } else {
                $rst = array('status' => 'Success', 'message' => ($isUpdate ? 'Labour details updated successfully!' : 'Labour registered successfully!'), 'empid' => $employeeID, 'id_card' => $idCardPath);
            }
        } else {
            $hrmconn->rollback();
            ob_end_clean();
            $rst = array('status' => 'Error', 'message' => $empsaverst['message']);
        }
    } catch (Exception $e) {
        $hrmconn->rollback();
        $err_msg = ob_get_clean();
        $rst = array('status' => 'Error', 'message' => $e->getMessage() . ($err_msg ? " | " . strip_tags($err_msg) : ""));
    }
    echo json_encode($rst);
} else if ($action == "LabourSalarySlipCreat") {
    $SalaryId = $_POST['SalaryId'];
    $ESQ = "SELECT * FROM hrm_LabourCalculatedSalary WHERE SalaryID = ?";
    $esstm = $hrmconn->prepare($ESQ);
    $payableDays = 0;
    $companyname = "";
    $projectname = "";
    $rtmsg = array();
    if ($esstm) {
        $esstm->bind_param('i', $SalaryId);
        if ($esstm->execute()) {
            $result = $esstm->get_result();
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                // Attendance details
                $filterValue = $row['DateFrom'] . "|" . $row['Dateto'] . "|" . $row['EmployeeID'];
                $daterangev = date('d-M-Y', strtotime($row['DateFrom'])) . " To " . date('d-M-Y', strtotime($row['Dateto']));
                $attEmp = fetchLabourAttendanceWithGrouped($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], "datebetweenwithemployeeid", $row['DateFrom'], $row['Dateto'], $row['EmployeeID']);

                $payableDays = 0;
                if (count($attEmp) > 0) {
                    $payableDays = str_replace(".00", "", $attEmp[0]['TotalPresentDays']) ?? 0;
                }

                // company details
                $cmpprj = getprojectdetailwithcmpsubcmp($conn, $row['CompanyID'], $row['SubCompanyID'], "withauthrizedprojects", $row['ProjectID']);
                $companyname = $cmpprj[0]['companyname'];
                $projectname = $cmpprj[0]['projectname'];

                // employeeInformation
                $eDkey = fetchLabourData($hrmconn, $row['CompanyID'], $row['SubCompanyID'], $row['ProjectID'], 'EmployeeID', $row['EmployeeID']);
                // print_r($eDkey);
                $EmployeeName = "";
                $GenderName = "";
                $DateOfJoining = "";
                $EmploymentTypeid = "";
                $DesignationID = "";
                $DepartmentID = "";
                $Email = "";
                if (count($eDkey) > 0) {
                    $EmployeeName = $eDkey[0]['TitleName'] . ' ' . $eDkey[0]['FirstName'] . ' ' . $eDkey[0]['MiddleName'] . ' ' . $eDkey[0]['LastName'];
                    $GenderName = $eDkey[0]['Gender'];
                    $DateOfJoining = "N/A";
                    $EmploymentTypeid = $eDkey[0]['EmploymentTypeid'];
                    $Email = $eDkey[0]['Email'];
                    $DesignationName = "Labour";
                    $DepartmentName = "";

                    $EmployeeData = array('EmployeeID' => $row['EmployeeID'], 'EmployeeName' => $EmployeeName, 'GenderName' => $GenderName, 'DateOfJoining' => $DateOfJoining, 'DesignationName' => $DesignationName, 'DepartmentName' => $DepartmentName, 'SalaryYear' => $row['SalaryYear'], 'SalaryMonth' => $row['SalaryMonth'], 'dateRange' => $daterangev, 'NetSalary' => $row['NetSalary'], 'WorkedDays' => $payableDays);
                    $outputFolder = "../TempPaySlip";
                    if (!is_dir($outputFolder)) {
                        mkdir($outputFolder, 0777, true);
                    }
                    $headerImage = "";
                    $footerImage = "";
                    $outputFilePath = $outputFolder . "/" . $row['ProjectID'] . '_' . $row['EmployeeID'] . '_' . $daterangev . ".pdf";
                    createLabourPaySlipWithHeaderFooter($outputFilePath, $companyname, $projectname, $EmployeeData);
                    $rtmsg = array('status' => 'Success', 'message' => 'Payslip genrated', 'filepath' => str_replace("../", "", $outputFilePath));
                }
            } else {
                $rtmsg = array('status' => 'Error', 'message' => 'No records found.', 'filepath' => '');
            }
        } else {
            $rtmsg = array('status' => 'Error', 'message' => "Query execution failed: " . $esstm->error, 'filepath' => '');
        }
        $esstm->close(); // Close statement
    } else {
        $rtmsg = array('status' => 'Error', 'message' => "Statement preparation failed: " . $hrmconn->error, 'filepath' => '');
    }
    echo json_encode($rtmsg);
} else if ($action == "RemoveFileFromPath") {
    if (isset($_POST['filePath'])) {
        $filePath = "../" . $_POST['filePath'];
        if ($filePath && file_exists($filePath)) {
            if (unlink($filePath)) {
                echo json_encode(["status" => "success", "message" => "File deleted successfully."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Failed to delete file."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "File not found. =" . $filePath]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "No file path provided."]);
    }
} else if ($action == "UpdatePayPerDayPayment") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $EmployeeId = $_POST['EmployeeId'];
    $loginname = $_POST['loginname'];
    $PayPerDayAmount = $_POST['PayPerDayAmount'];
    $cckpt = getLabourPaymentData($hrmconn, $projectid, 'EmployeeID', $EmployeeId);
    $rtmsg = [];
    if (count($cckpt) > 0) {
        if ($cckpt[0]['PerDayPayment'] == $PayPerDayAmount) {
            $rtmsg = ['status' => 'warning', 'message' => 'Already updated'];
        } else {
            $pptmsg = saveLabourPayment($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeId, $PayPerDayAmount);
            $rtmsg = $pptmsg;
        }
    } else {
        $rtmsg = saveLabourPayment($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeId, $PayPerDayAmount);
    }
    echo json_encode($rtmsg);
} else if ($action == "UpdatePayPerDayPaymentWithOtherDetails") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $EmployeeId = $_POST['EmployeeId'];
    $loginname = $_POST['loginname'];
    $PayPerDayAmount = $_POST['PayPerDayAmount'];
    $departmentid = $_POST['departmentid'] ?? '';
    $designationid = $_POST['designationid'] ?? '';
    $graftingskill = $_POST['graftingskill'] ?? '';
    $locationid = $_POST['locationid'];
    $cckpt = getLabourPaymentData($hrmconn, $projectid, 'EmployeeID', $EmployeeId);
    $rtmsg = [];
    if (count($cckpt) > 0) {
        if ($cckpt[0]['PerDayPayment'] == $PayPerDayAmount && $PayPerDayAmount > 0) {
            $rtmsg = ['status' => 'warning', 'message' => 'Already updated'];
            $hrmconn->query("UPDATE hrm_LabourMaster SET DesignationID='" . $designationid . "',DepartmentID='" . $departmentid . "',locationid='" . $locationid . "',graftingskill='" . $graftingskill . "' WHERE projectid='" . $projectid . "' and EmployeeID='" . $EmployeeId . "'");
            if ($hrmconn->affected_rows > 0) {
                $rtmsg = ['status' => 'success', 'message' => 'Labour details updated successfully'];
            }
        } else {
            $pptmsg = saveLabourPayment($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeId, $PayPerDayAmount);
            $hrmconn->query("UPDATE hrm_LabourMaster SET DesignationID='" . $designationid . "',DepartmentID='" . $departmentid . "',locationid='" . $locationid . "',graftingskill='" . $graftingskill . "' WHERE projectid='" . $projectid . "' and EmployeeID='" . $EmployeeId . "'");
            if ($hrmconn->affected_rows > 0) {
                $rtmsg = ['status' => 'success', 'message' => 'Labour details updated successfully'];
            } else {
                $rtmsg = $pptmsg;
            }
        }
    } else {
        $rtmsg = saveLabourPayment($hrmconn, $companyid, $subcompanyid, $projectid, $EmployeeId, $PayPerDayAmount);
    }
    echo json_encode($rtmsg);
} else if ($action == "LabourSearchfromMasterDataforaddatt") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $shortby = "LabourID";
    $shortbyvalue = $_POST['labourid'];
    $llbData = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
    echo json_encode($llbData);
} else if ($action == "UpdateMissingLabourAtt") {
    try {
        $companyid    = $_POST['companyid'] ?? null;
        $subcompanyid = $_POST['subcompanyid'] ?? null;
        $projectid    = $_POST['projectid'] ?? null;
        $labourid     = $_POST['labourid'] ?? '';
        $labourname   = $_POST['labourname'] ?? '';
        $attdatefrom  = $_POST['attdatefrom'] ?? '';
        $attdateto    = $_POST['attdateto'] ?? '';
        $attdays      = $_POST['attdays'] ?? 0;
        $locationid   = $_POST['locationid'] ?? ''; // ✅ FIXED
        $loginname    = $_POST['loginname'] ?? '';

        $srcloc= getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $locationid);
        $sltlocationname = $srcloc[0]['LocationName'] ?? '';
               


        if (!$labourid || !$attdatefrom || !$attdateto) {
            echo json_encode([
                "status" => "error",
                "message" => "Required fields missing"
            ]);
            exit;
        }
        // 🔍 Check Labour Exists
        $labourData = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "LabourID", $labourid);
        $attendanceInserted = "";
        $labourlocationname="";
        if (is_array($labourData) && count($labourData) > 0) {
            foreach ($labourData as $row) {
                $srloc= getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "LocationId", $row['locationid']);
                $lblocationname = $srloc[0]['LocationName'] ?? '';
                
                // 🔍 Check duplicate attendance
                $checkStmt = $hrmconn->prepare("
                    SELECT AttendanceID 
                    FROM hrm_labourcalculateattendance 
                    WHERE EmployeeID = ? AND CompanyID = ? AND SubCompanyID = ? 
                    AND ProjectID = ? AND DateFrom = ? AND DateTo = ? AND LocationId = ?
                ");

                $checkStmt->bind_param("sssssss",$labourid,$companyid,$subcompanyid,$projectid,$attdatefrom,$attdateto,$locationid);
                $checkStmt->execute();
                $checkStmt->store_result();
                if ($checkStmt->num_rows>0) {
                    $attendanceInserted = 1;
                }else{
                    insertOrUpdateLabourCalculatedAttendance(
                        $hrmconn,
                        $loginname,
                        $companyid,
                        $subcompanyid,
                        $projectid,
                        $labourid,
                        $attdatefrom,
                        $attdateto,
                        $locationid,
                        $attdays,
                        ""
                    );
                    $attendanceInserted = 2;
                }                
            }
        } else {
            // ➕ Insert new labour
            $parts = splitFullName($labourname);
            $insertSql = "
                INSERT INTO hrm_LabourMaster 
                (companyid, subcompanyid, projectid, EmployeeID, FirstName, MiddleName, LastName, addedby, locationid) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
            $stmt = $hrmconn->prepare($insertSql);
            $stmt->bind_param(
                "sssssssss",
                $companyid,
                $subcompanyid,
                $projectid,
                $labourid,
                $parts['first_name'],
                $parts['middle_name'],
                $parts['last_name'],
                $loginname,
                $locationid
            );
            if (!$stmt->execute()) {
                throw new Exception("Error inserting Labour: " . $stmt->error);
            }
            // ➕ Insert attendance
            insertOrUpdateLabourCalculatedAttendance(
                $hrmconn,
                $loginname,
                $companyid,
                $subcompanyid,
                $projectid,
                $labourid,
                $attdatefrom,
                $attdateto,
                $locationid,
                $attdays,
                ""
            );
            $attendanceInserted = 3;
        }
        // ✅ Final Response
        if ($attendanceInserted==1) {
            echo json_encode([
                "status" => "warning",
                "message" => "Attendance already exist of selected date range"
            ]);
        }else if ($attendanceInserted==2) {
            echo json_encode([
                "status" => "success",
                "message" => "Attendance update successfully"
            ]);
        }else if ($attendanceInserted==3) {
            echo json_encode([
                "status" => "success",
                "message" => "New Labour details and Attendance saved successfully"
            ]);
        } else {
            echo json_encode([
                "status" => "warning",
                "message" => "Something wrong or labour exist with other location"
            ]);
        }
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }  
} else if ($action == "LabourMasterData") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $lbData = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
    echo json_encode($lbData);
} else if ($action == "RemoveLabourFromMasterData") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginid = $_POST['loginid'] ?? null;
    $loginname = $_POST['loginname'] ?? null;
    $Labourid = $_POST['Labourid'] ?? null;
    $Remark = $_POST['Remark'] . " -- " . ("Removed on Date: " . date('Y-m-d') . " by " . $loginname);
    $response = [];
    // Validation
    if (empty($projectid) || empty($Labourid)) {
        echo json_encode(["status" => "Error", "message" => "ProjectID and LabourID are required."]);
        exit;
    }

    // SQL update (safe prepared statement)
    $stmt = $hrmconn->prepare("UPDATE hrm_LabourMaster SET Status = 'Removed', remark = ?, updated_at = NOW() WHERE projectid = ? AND EmployeeID = ?");
    $stmt->bind_param("sss", $Remark, $projectid, $Labourid);
    if ($stmt->execute()) {
        $response = [
            "status" => "Success",
            "message" => "Labour removed successfully.",
            "LabourID" => $Labourid
        ];
    } else {
        $response = ["status" => "Error", "message" => "Error updating Labour record: " . $stmt->error];
    }
    $stmt->close();
    echo json_encode($response);
} else if ($action == "ActivateLabourFromMasterData") {
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'] ?? null;
    $Labourid = $_POST['Labourid'] ?? null;
    $Remark = ($_POST['Remark'] ?? 'Activated') . " -- " . ("Activated on Date: " . date('Y-m-d') . " by " . $loginname);
    $response = [];
    if (empty($projectid) || empty($Labourid)) {
        echo json_encode(["status" => "Error", "message" => "ProjectID and LabourID are required."]);
        exit;
    }
    $stmt = $hrmconn->prepare("UPDATE hrm_LabourMaster SET Status = 'Active', remark = ?, updated_at = NOW() WHERE projectid = ? AND EmployeeID = ?");
    $stmt->bind_param("sss", $Remark, $projectid, $Labourid);
    if ($stmt->execute()) {
        $response = [
            "status" => "Success",
            "message" => "Labour activated successfully.",
            "LabourID" => $Labourid
        ];
    } else {
        $response = ["status" => "Error", "message" => "Error updating Labour record: " . $stmt->error];
    }
    $stmt->close();
    echo json_encode($response);
} else if ($action == "LabourMasterDataViewTablehtml") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $shortby = $_POST['shortby'] ?? null;
    $shortbyvalue = $_POST['shortbyvalue'] ?? null;

    $lbData = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
    $tbltr = "";

    if (count($lbData) > 0) {
        $lbsno = 1;

        // 🔹 Fetch masters once
        $locationmaster = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "all", "all");
        $deptlist = fetchDepartmentMaster($hrmconn, $companyid, "all", "all");
        $desiglist = fetchDesignationsMaster($hrmconn, $companyid, "all", "all");

        foreach ($lbData as $lbdkey) {

            // 🔹 Location options
            $locoption = "";
            if (!empty($locationmaster)) {
                if (count($locationmaster) > 1) {
                    $locoption .= "<option value=''>Select</option>";
                }
                foreach ($locationmaster as $lockey) {
                    $selected = ($lbdkey['locationid'] == $lockey['LocationId']) ? "selected" : "";
                    $locoption .= "<option value='" . $lockey['LocationId'] . "' $selected>" . $lockey['LocationName'] . "</option>";
                }
            }

            // 🔹 Department options
            $deptoption = "";
            if (!empty($deptlist)) {
                if (count($deptlist) > 1) {
                    $deptoption .= "<option value=''>Select</option>";
                }
                foreach ($deptlist as $deptkey) {
                    $selected = ($lbdkey['DepartmentID'] == $deptkey['DepartmentID']) ? "selected" : "";
                    $deptoption .= "<option value='" . $deptkey['DepartmentID'] . "' $selected>" . $deptkey['DepartmentName'] . "</option>";
                }
            }

            // 🔹 Designation options
            $desigoption = "";
            if (!empty($desiglist)) {
                if (count($desiglist) > 1) {
                    $desigoption .= "<option value=''>Select</option>";
                }
                foreach ($desiglist as $desigkey) {
                    $selected = ($lbdkey['DesignationID'] == $desigkey['DesignationID']) ? "selected" : "";
                    $desigoption .= "<option value='" . $desigkey['DesignationID'] . "' $selected>" . $desigkey['DesignationName'] . "</option>";
                }
            }

            // 🔹 Grafting skill
            $grfskloption = "
                <option value=''>Select</option>
                <option value='yes' " . ($lbdkey['graftingskill'] == "yes" ? "selected" : "") . ">YES</option>
                <option value='no' " . ($lbdkey['graftingskill'] == "no" ? "selected" : "") . ">NO</option>
            ";

            // 🔹 Build row
            $tbltr .= '
                <tr data-employeeid="' . $lbdkey['EmployeeID'] . '">
                    <td>' . $lbsno++ . '</td>
                    <td>' . $lbdkey['EmployeeID'] . '</td>
                    <td class="labournametd text-dark">' . $lbdkey['EmployeeID'] . ' - ' . $lbdkey['FullName'] . '</td>
                    <td class="perdaypaymenttd">
                        <input type="text" class="form-control labourPerDayPayment"
                               onkeypress="return isNumberKey(event)"
                               value="' . $lbdkey['PerDayPayment'] . '" readonly />
                    </td>
                    <td class="departmenttd">
                        <select class="form-control labourDepartment">' . $deptoption . '</select>
                    </td>
                    <td class="designationtd">
                        <select class="form-control labourDesignation">' . $desigoption . '</select>
                    </td>
                    <td class="graftingskilltd">
                        <select class="form-control labourGraftingSkill">' . $grfskloption . '</select>
                    </td>
                    <td class="locationtd">
                        <select class="form-control labourLocation">' . $locoption . '</select>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary editpaymentbtn">
                            <i class="fa fa-edit"></i> Edit Payment
                        </button>
                        <button class="btn btn-sm btn-primary updatepaymentbtn d-none"
                                data-employeeid="' . $lbdkey['EmployeeID'] . '">
                            <i class="fa fa-save"></i> Update
                        </button>
                    </td>
                </tr>';
        }
    }
    echo json_encode($tbltr);
} else if ($action == "GetIncompleteLaboursList") {
    $companyid    = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid    = $_POST['projectid'] ?? null;

    // 1. Fetch Location Map
    $locMap = [];
    $resLoc = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster");
    if ($resLoc) {
        while ($lRow = $resLoc->fetch_assoc()) {
            $locMap[$lRow['LocationId']] = $lRow['LocationName'];
        }
    }

    // 2. Fetch Active Labours in single fast query
    $sql = "SELECT 
        lm.EmployeeID,
        lm.seriesid,
        CONCAT_WS(' ', IFNULL(lm.TitleName,''), IFNULL(lm.FirstName,''), IFNULL(lm.LastName,'')) AS FullName,
        lm.FirstName, lm.LastName,
        lm.FatherName, lm.SpouseName, lm.Gender, lm.MaritalStatus,
        lm.MobileNo, lm.AddressLine1, lm.graftingskill,
        lm.idprooftype AS IdProofType,
        lm.idproofpath,
        lm.AadharNumber, lm.PanNumber, lm.PassportNumber,
        lm.locationid
    FROM hrm_LabourMaster lm
    WHERE lm.companyid = ? AND lm.subcompanyid = ? AND lm.projectid = ? AND lm.Status = 'Active'
    ORDER BY CAST(lm.EmployeeID AS UNSIGNED) ASC";

    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => "Error", "message" => $hrmconn->error]);
        exit;
    }
    $stmt->bind_param("sss", $companyid, $subcompanyid, $projectid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $idToEmpMap = [];
    $lookupIds = [];
    foreach ($rows as $r) {
        $eid = $r['EmployeeID'];
        $lookupIds[] = "'" . $hrmconn->real_escape_string($eid) . "'";
        $idToEmpMap[$eid] = $eid;

        if (!empty($r['seriesid'])) {
            $sid = strval($r['seriesid']);
            $lookupIds[] = "'" . $hrmconn->real_escape_string($sid) . "'";
            $idToEmpMap[$sid] = $eid;
        }

        $numOnly = preg_replace('/[^0-9]/', '', $eid);
        if (!empty($numOnly)) {
            $lookupIds[] = "'" . $hrmconn->real_escape_string($numOnly) . "'";
            $idToEmpMap[$numOnly] = $eid;
        }
    }
    $lookupIds = array_values(array_unique($lookupIds));

    $bankSet = [];
    $refSet = [];
    $emergMap = [];
    $photoMap = [];
    $idDocSet = [];

    if (!empty($lookupIds)) {
        $inClause = implode(",", $lookupIds);

        // 1. Bulk Bank Check in hrm_labourbankdetails
        $resBank = $hrmconn->query("SELECT DISTINCT labourid FROM hrm_labourbankdetails 
                                    WHERE labourid IN ($inClause) AND (tedit IS NULL OR tedit='') 
                                      AND TRIM(IFNULL(bankname, '')) != ''
                                      AND TRIM(IFNULL(accountnumber, '')) != ''
                                      AND TRIM(IFNULL(ifsccode, '')) != ''");
        if ($resBank) {
            while ($b = $resBank->fetch_assoc()) {
                $mappedEid = $idToEmpMap[$b['labourid']] ?? $b['labourid'];
                $bankSet[$mappedEid] = true;
            }
        }

        // 2. Bulk Bank Check in alternative/legacy hrm_EmployeeBankDetails
        $resBankOld = $hrmconn->query("SELECT DISTINCT EmployeeID FROM hrm_EmployeeBankDetails 
                                       WHERE EmployeeID IN ($inClause) AND (tedit IS NULL OR tedit='') 
                                         AND TRIM(IFNULL(BankName, '')) != ''
                                         AND TRIM(IFNULL(AccountNumber, '')) != ''
                                         AND TRIM(IFNULL(IFSCCode, '')) != ''");
        if ($resBankOld) {
            while ($b = $resBankOld->fetch_assoc()) {
                $mappedEid = $idToEmpMap[$b['EmployeeID']] ?? $b['EmployeeID'];
                $bankSet[$mappedEid] = true;
            }
        }

        // 3. Bulk Contacts & Emergency Check
        $resContact = $hrmconn->query("SELECT EmployeeId, PhoneNumber, IsPrimaryContact 
                                       FROM hrm_LabourContactPersonDetails 
                                       WHERE EmployeeId IN ($inClause)");
        if ($resContact) {
            while ($c = $resContact->fetch_assoc()) {
                $mappedEid = $idToEmpMap[$c['EmployeeId']] ?? $c['EmployeeId'];
                $refSet[$mappedEid] = true;
                if (($c['IsPrimaryContact'] === 'yes' || !empty($c['PhoneNumber'])) && !empty(trim($c['PhoneNumber']))) {
                    $emergMap[$mappedEid] = trim($c['PhoneNumber']);
                }
            }
        }

        // 4. Bulk Documents Check (Aadhar, PAN, Ration Card, IDCard, IDProof, etc.)
        $resDocs = $hrmconn->query("SELECT EmployeeID, DocumentType, DocumentPath 
                                    FROM hrm_labourdocuments 
                                    WHERE EmployeeID IN ($inClause)");
        if ($resDocs) {
            while ($d = $resDocs->fetch_assoc()) {
                $mappedEid = $idToEmpMap[$d['EmployeeID']] ?? $d['EmployeeID'];
                $docType = trim($d['DocumentType'] ?? '');
                $docPath = trim($d['DocumentPath'] ?? '');
                if ($docType === 'ProfilePhoto') {
                    $photoMap[$mappedEid] = $docPath;
                }
                if (!empty($docPath) && $docType !== 'ProfilePhoto') {
                    $idDocSet[$mappedEid] = true;
                }
            }
        }
    }

    $incompleteList = [];
    foreach ($rows as $lm) {
        $eid = $lm['EmployeeID'];
        $missing = [];

        $isMarried = (strtolower(trim($lm['MaritalStatus'] ?? '')) === 'married');
        $fatherName = trim($lm['FatherName'] ?? '');
        $spouseName = trim($lm['SpouseName'] ?? '');
        $gender = trim($lm['Gender'] ?? '');

        // Rule: If married -> check husband/spouse name; if unmarried -> check father name
        if ($isMarried) {
            if (empty($spouseName) && empty($fatherName)) {
                $missing[] = ($gender === 'Male') ? 'Wife Name' : 'Husband Name';
            }
        } else {
            if (empty($fatherName)) {
                $missing[] = 'Father Name';
            }
        }

        if (empty($gender)) $missing[] = 'Gender';
        if (empty(trim($lm['MaritalStatus'] ?? ''))) $missing[] = 'Marital Status';
        if (empty(trim($lm['MobileNo'] ?? ''))) $missing[] = 'Mobile No';
        if (!isset($emergMap[$eid]) || empty($emergMap[$eid])) $missing[] = 'Emergency No';
        
        // Rule: If any document (Aadhar, PAN, Ration Card, etc.) or id proof number is updated, ID proof type is satisfied
        $hasIdType = !empty(trim($lm['IdProofType'] ?? '')) || !empty(trim($lm['AadharNumber'] ?? '')) || !empty(trim($lm['PanNumber'] ?? '')) || !empty(trim($lm['PassportNumber'] ?? '')) || isset($idDocSet[$eid]) || !empty(trim($lm['idproofpath'] ?? ''));
        if (!$hasIdType) $missing[] = 'ID Proof Type';

        // ID Proof Number check
        $hasIdNo = !empty(trim($lm['AadharNumber'] ?? '')) || !empty(trim($lm['PanNumber'] ?? '')) || !empty(trim($lm['PassportNumber'] ?? ''));
        if (!$hasIdNo) $missing[] = 'ID Proof No';

        // Rule: If any document is uploaded (Aadhar, PAN, Ration Card, IDCard, etc.) or idproofpath in master, don't show ID Proof Document missing
        $hasIdDoc = isset($idDocSet[$eid]) || !empty(trim($lm['idproofpath'] ?? ''));
        if (!$hasIdDoc) $missing[] = 'ID Proof Document';

        if (empty(trim($lm['AddressLine1'] ?? ''))) $missing[] = 'Current Address';
        if (!isset($lm['graftingskill']) || trim($lm['graftingskill']) === '') $missing[] = 'Grafting Skill';
        if (!isset($bankSet[$eid])) $missing[] = 'Bank Details (Name/Acc/IFSC)';
        if (!isset($refSet[$eid])) $missing[] = 'Reference Person';

        if (!empty($missing)) {
            $incompleteList[] = [
                'EmployeeID'   => $eid,
                'FullName'     => trim($lm['FullName']),
                'FirstName'    => $lm['FirstName'],
                'profile_photo'=> $photoMap[$eid] ?? null,
                'LocationName' => $locMap[$lm['locationid']] ?? null,
                'locationid'   => $lm['locationid'],
                'missing'      => $missing,
                'missingCount' => count($missing),
            ];
        }
    }
    echo json_encode(["status" => "Success", "total" => count($incompleteList), "list" => $incompleteList]);

} else if ($action == "LoadLabourlist") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $shortby = $_POST['shortby'] ?? null;
    $shortbyvalue = $_POST['shortbyvalue'] ?? null;
    $only_masters = $_POST['only_masters'] ?? null;

    $response = [
        "labours" => ($only_masters === "true") ? [] : fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue),
        "locations" => (function($conn, $cid, $scid, $pid) {
            $locs = getAttendanceLocationMaster($conn, $cid, $scid, $pid, "all", "all");
            if (empty($locs)) {
                $locs = getAttendanceLocationMaster($conn, $cid, $scid, "all", "all", "all");
            }
            return $locs;
        })($hrmconn, $companyid, $subcompanyid, $projectid),
        "departments" => fetchDepartmentMaster($hrmconn, $companyid, "all", "all"),
        "designations" => fetchDesignationsMaster($hrmconn, $companyid, "all", "all")
    ];

    echo json_encode($response);
} else if ($action == "GetLabourFullDetails") {
    $employeeID = $_POST['employeeID'] ?? null;
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;

    $details = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $employeeID);
    if (is_array($details) && count($details) > 0) {
        $labour = $details[0];
        if ($labour['EmployeeID'] !== $employeeID) {
            echo json_encode(['status' => 'Error', 'message' => 'लेबर आईडी का मिलान नहीं हुआ।']);
            exit;
        }
        $locName = '—';
        if (!empty($labour['locationid'])) {
            $locQuery = "SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = ? LIMIT 1";
            $stmtLoc = $hrmconn->prepare($locQuery);
            $stmtLoc->bind_param("s", $labour['locationid']);
            $stmtLoc->execute();
            $resLoc = $stmtLoc->get_result()->fetch_assoc();
            if ($resLoc) {
                $locName = $resLoc['LocationName'];
            }
            $stmtLoc->close();
        }
        if ($locName === '—' && !empty($labour['WorkLocation'])) {
            $locName = $labour['WorkLocation'];
        }
        $details[0]['WorkingLocationName'] = $locName;
        $details[0]['PayPerDayAmount']     = $labour['PerDayPayment'] ?? 0;
        $details[0]['mobileNo']            = $labour['MobileNo'] ?? '—';
        $details[0]['grafting_skill']      = $labour['graftingskill'] ?? 'no';
        $details[0]['graftingskill']       = $labour['graftingskill'] ?? 'no';
    }

    $refQuery = "SELECT ContactName AS FirstName, Relationship AS Relation, PhoneNumber AS MobileNo FROM hrm_LabourContactPersonDetails WHERE EmployeeId = ?";
    $stmt = $hrmconn->prepare($refQuery);
    $stmt->bind_param("s", $employeeID);
    $stmt->execute();
    $references = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    $docs = [];
    $docQuery = "SELECT * FROM hrm_labourdocuments WHERE EmployeeID = ?";
    $stmt2 = $hrmconn->prepare($docQuery);
    $stmt2->bind_param("s", $employeeID);
    $stmt2->execute();
    $docs = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

    $bank = null;
    $bankQuery = "SELECT id, companyid, subcompanyid, projectid, labourid AS EmployeeID, bankname AS BankName, accountnumber AS AccountNumber, ifsccode AS IFSCCode, currencycode AS CurrencyCode, cancelchequepath AS CancelChequePath, tedit 
                  FROM hrm_labourbankdetails 
                  WHERE labourid = ? AND (tedit IS NULL OR tedit = '') 
                  ORDER BY id DESC LIMIT 1";
    $stmtBank = $hrmconn->prepare($bankQuery);
    if ($stmtBank) {
        $stmtBank->bind_param("s", $employeeID);
        $stmtBank->execute();
        $bank = $stmtBank->get_result()->fetch_assoc();
        $stmtBank->close();
    }
    
    if (!$bank && !empty($labour['seriesid'])) {
        $sid = strval($labour['seriesid']);
        $bankQuerySid = "SELECT id, companyid, subcompanyid, projectid, labourid AS EmployeeID, bankname AS BankName, accountnumber AS AccountNumber, ifsccode AS IFSCCode, currencycode AS CurrencyCode, cancelchequepath AS CancelChequePath, tedit 
                         FROM hrm_labourbankdetails 
                         WHERE labourid = ? AND (tedit IS NULL OR tedit = '') 
                         ORDER BY id DESC LIMIT 1";
        $stmtBankSid = $hrmconn->prepare($bankQuerySid);
        if ($stmtBankSid) {
            $stmtBankSid->bind_param("s", $sid);
            $stmtBankSid->execute();
            $bank = $stmtBankSid->get_result()->fetch_assoc();
            $stmtBankSid->close();
        }
    }

    if (!$bank) {
        $numEmpId = intval(preg_replace('/[^0-9]/', '', $employeeID));
        if ($numEmpId > 0) {
            $bankQueryOld = "SELECT BankDetailID AS id, companyid, subcompanyid, projectid, EmployeeID, BankName, AccountNumber, IFSCCode, CurrencyCode, CancelChequePath, tedit 
                             FROM hrm_EmployeeBankDetails 
                             WHERE EmployeeID = ? AND EmployeeID > 0 AND (tedit IS NULL OR tedit = '') 
                             ORDER BY BankDetailID DESC LIMIT 1";
            $stmtBankOld = $hrmconn->prepare($bankQueryOld);
            if ($stmtBankOld) {
                $stmtBankOld->bind_param("i", $numEmpId);
                $stmtBankOld->execute();
                $bank = $stmtBankOld->get_result()->fetch_assoc();
                $stmtBankOld->close();
            }
        }
    }

    echo json_encode([
        "status" => "Success",
        "data" => $details[0] ?? null,
        "references" => $references,
        "documents" => $docs,
        "bank" => $bank
    ]);
} else if ($action == "GetWorkingLocations") {
    $companyid    = $_POST['companyid'] ?? $_GET['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? $_GET['subcompanyid'] ?? null;
    $projectid    = $_POST['projectid'] ?? $_GET['projectid'] ?? null;

    $where = ["(tedit IS NULL OR tedit = '')"];
    if (!empty($projectid) && $projectid !== 'all') {
        $projEsc = $hrmconn->real_escape_string($projectid);
        $where[] = "(ProjectID = '{$projEsc}' OR ProjectID IS NULL OR ProjectID = '' OR ProjectID = '0')";
    }

    $whereClause = implode(" AND ", $where);
    $sql = "SELECT LocationId, LocationName, LocationAddress, shortname 
            FROM hrm_AttendanceLocationMaster 
            WHERE {$whereClause} 
            ORDER BY LocationName ASC";
    $res = $hrmconn->query($sql);
    $locations = [];
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $locations[] = [
                'LocationId'   => $row['LocationId'],
                'LocationName' => $row['LocationName'],
                'shortname'    => $row['shortname'] ?? ''
            ];
        }
    } else {
        $resAll = $hrmconn->query("SELECT LocationId, LocationName, LocationAddress, shortname FROM hrm_AttendanceLocationMaster WHERE (tedit IS NULL OR tedit = '') ORDER BY LocationName ASC");
        if ($resAll) {
            while ($row = $resAll->fetch_assoc()) {
                $locations[] = [
                    'LocationId'   => $row['LocationId'],
                    'LocationName' => $row['LocationName'],
                    'shortname'    => $row['shortname'] ?? ''
                ];
            }
        }
    }
    echo json_encode($locations);
    exit;
} else if ($action == "GetMasterStates") {
    $sql = "SELECT stateid, statename FROM master_states WHERE (status = 'Active' OR IFNULL(status, '') = '') ORDER BY statename ASC";
    $res = $krfconn->query($sql);
    $states = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $states[] = [
                'stateid'   => $row['stateid'],
                'statename' => $row['statename']
            ];
        }
    }
    echo json_encode(["status" => "Success", "data" => $states]);
    exit;
} else if ($action == "GetMasterDistricts") {
    $stateid = $_POST['stateid'] ?? $_GET['stateid'] ?? '';
    $districts = [];
    if (!empty($stateid)) {
        $stmt = $krfconn->prepare("SELECT districtid, districtname FROM master_districts WHERE (stateid = ? OR LOWER(TRIM(stateid)) = LOWER(TRIM(?))) AND (status = 'Active' OR IFNULL(status, '') = '') ORDER BY districtname ASC");
        if ($stmt) {
            $stmt->bind_param("ss", $stateid, $stateid);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $districts[] = [
                    'districtid'   => $row['districtid'],
                    'districtname' => $row['districtname']
                ];
            }
            $stmt->close();
        }
    }
    echo json_encode(["status" => "Success", "data" => $districts]);
    exit;
} else if ($action == "UploadLabourProfilePhoto") {
    $employeeID = $_POST['employeeID'] ?? null;
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;

    if (empty($employeeID)) {
        echo json_encode(["status" => "Error", "message" => "Employee ID is required."]);
        exit;
    }

    if (!isset($_FILES['profilePhoto']) || $_FILES['profilePhoto']['error'] !== 0) {
        echo json_encode(["status" => "Error", "message" => "No image file uploaded or file upload error."]);
        exit;
    }

    $distpath = "../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/";
    $profilePhotoPath = uploadFile('profilePhoto', $distpath . 'labour/photos/');

    if ($profilePhotoPath && strpos($profilePhotoPath, '.') !== false) {
        insertOrUpdateLabourDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'ProfilePhoto', $profilePhotoPath);
        echo json_encode(["status" => "Success", "message" => "Profile photo updated successfully.", "path" => $profilePhotoPath]);
    } else {
        echo json_encode(["status" => "Error", "message" => "Failed to upload image."]);
    }
    exit;
} else if ($action == "UpdateLabourSpecificDetails") {
    $employeeID = $_POST['employeeID'] ?? null;
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $section = $_POST['section'] ?? null;

    if (empty($employeeID)) {
        echo json_encode(["status" => "Error", "message" => "Employee ID is required."]);
        exit;
    }

    $now = date('Y-m-d H:i:s');

    if ($section === 'basic') {
        $firstName   = $_POST['firstName'] ?? '';
        $middleName  = $_POST['middleName'] ?? '';
        $lastName    = $_POST['lastName'] ?? '';
        $gender      = $_POST['gender'] ?? '';
        $dateOfBirth = !empty($_POST['dateOfBirth']) ? $_POST['dateOfBirth'] : null;
        $maritalStatus = $_POST['maritalStatus'] ?? '';
        $idProofType = $_POST['idProofType'] ?? '';
        $idProofNo   = trim($_POST['idProofNo'] ?? '');
        $fatherName  = $_POST['fatherName'] ?? '';
        $motherName  = $_POST['motherName'] ?? '';
        $spouseName  = $_POST['spouseName'] ?? '';

        if (empty($firstName)) {
            echo json_encode(["status" => "Error", "message" => "First Name is required."]);
            exit;
        }

        // Handle ID Proof document upload if attached
        $idProofPath = null;
        if (isset($_FILES['idProofDoc']) && $_FILES['idProofDoc']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['idProofDoc']['tmp_name'];
            $fileName = $_FILES['idProofDoc']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = 'idproof_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $employeeID) . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = '../../uploads/labour_docs/';
            if (!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $idProofPath = 'uploads/labour_docs/' . $newFileName;
            }
        }

        if ($idProofPath) {
            $sql = "UPDATE hrm_LabourMaster 
                    SET FirstName = ?, MiddleName = ?, LastName = ?, Gender = ?, DateOfBirth = ?, MaritalStatus = ?, idprooftype = ?, idproofpath = ?, FatherName = ?, MotherName = ?, SpouseName = ?, updated_at = ? 
                    WHERE EmployeeID = ?";
            $stmt = $hrmconn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssssssssss", $firstName, $middleName, $lastName, $gender, $dateOfBirth, $maritalStatus, $idProofType, $idProofPath, $fatherName, $motherName, $spouseName, $now, $employeeID);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $sql = "UPDATE hrm_LabourMaster 
                    SET FirstName = ?, MiddleName = ?, LastName = ?, Gender = ?, DateOfBirth = ?, MaritalStatus = ?, idprooftype = ?, FatherName = ?, MotherName = ?, SpouseName = ?, updated_at = ? 
                    WHERE EmployeeID = ?";
            $stmt = $hrmconn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ssssssssssss", $firstName, $middleName, $lastName, $gender, $dateOfBirth, $maritalStatus, $idProofType, $fatherName, $motherName, $spouseName, $now, $employeeID);
                $stmt->execute();
                $stmt->close();
            }
        }

        // Update AadharNumber, PanNumber or PassportNumber if idProofNo is provided
        if (!empty($idProofNo)) {
            if ($idProofType === 'Aadhar Card') {
                $sqlProof = "UPDATE hrm_LabourMaster SET AadharNumber = ? WHERE EmployeeID = ?";
            } elseif ($idProofType === 'PAN Card') {
                $sqlProof = "UPDATE hrm_LabourMaster SET PanNumber = ? WHERE EmployeeID = ?";
            } elseif ($idProofType === 'Passport') {
                $sqlProof = "UPDATE hrm_LabourMaster SET PassportNumber = ? WHERE EmployeeID = ?";
            } else {
                $sqlProof = "UPDATE hrm_LabourMaster SET AadharNumber = ? WHERE EmployeeID = ?";
            }
            if ($sqlProof) {
                $stmtProof = $hrmconn->prepare($sqlProof);
                if ($stmtProof) {
                    $stmtProof->bind_param("ss", $idProofNo, $employeeID);
                    $stmtProof->execute();
                    $stmtProof->close();
                }
            }
        }

        echo json_encode(["status" => "Success", "message" => "Basic information updated successfully."]);
        exit;
    } else if ($section === 'bank') {
        $bankName = strtoupper(trim($_POST['bankName'] ?? ''));
        $accountNumber = trim($_POST['accountNumber'] ?? '');
        $ifscCode = strtoupper(trim($_POST['ifscCode'] ?? ''));

        if (empty($bankName) || empty($accountNumber) || empty($ifscCode)) {
            echo json_encode(["status" => "Error", "message" => "All 3 bank fields (Bank Name, Account Number, and IFSC Code) are required."]);
            exit;
        }

        $bankDocPath = '';
        $existBankQuery = "SELECT cancelchequepath FROM hrm_labourbankdetails WHERE labourid = ? AND (tedit IS NULL OR tedit = '') ORDER BY id DESC LIMIT 1";
        $stmtExistBank = $hrmconn->prepare($existBankQuery);
        if ($stmtExistBank) {
            $stmtExistBank->bind_param("s", $employeeID);
            $stmtExistBank->execute();
            $resExistBank = $stmtExistBank->get_result()->fetch_assoc();
            if ($resExistBank && !empty($resExistBank['cancelchequepath'])) {
                $bankDocPath = $resExistBank['cancelchequepath'];
            }
            $stmtExistBank->close();
        }

        // Handle Bank Reference/Document file upload if attached
        if (isset($_FILES['bankDoc']) && $_FILES['bankDoc']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['bankDoc']['tmp_name'];
            $fileName = $_FILES['bankDoc']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = 'bankdoc_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $employeeID) . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = '../../uploads/labour_docs/';
            if (!is_dir($uploadFileDir)) {
                @mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $bankDocPath = 'uploads/labour_docs/' . $newFileName;
            }
        }
        
        insertOrUpdateBankDetails(
            $hrmconn, $companyid, $subcompanyid, $projectid, $employeeID,
            $bankName, $accountNumber, $ifscCode, '', '', '', 'INR', $bankDocPath
        );

        // Check & sync paytoid in tbl_payto_master and hrm_LabourMaster
        $empQuery = "SELECT FirstName, MiddleName, LastName, MobileNo, paytoid FROM hrm_LabourMaster WHERE EmployeeID = ? LIMIT 1";
        $stmtEmp = $hrmconn->prepare($empQuery);
        if ($stmtEmp) {
            $stmtEmp->bind_param("s", $employeeID);
            $stmtEmp->execute();
            $empRow = $stmtEmp->get_result()->fetch_assoc();
            $stmtEmp->close();
            if ($empRow) {
                $empFullName = trim(($empRow['FirstName'] ?? '') . ' ' . ($empRow['MiddleName'] ?? '') . ' ' . ($empRow['LastName'] ?? ''));
                $empMobile = $empRow['MobileNo'] ?? '';
                $paytoin = savetodbnewpayto_hrm($conn, $empFullName, $accountNumber, $bankName, $ifscCode, '', '', '', $companyid, $subcompanyid, $projectid, $empMobile, '', $loginname);
                if (!empty($paytoin[0]['paytoid'])) {
                    $newPaytoid = $paytoin[0]['paytoid'];
                    $hrmconn->query("UPDATE hrm_LabourMaster SET paytoid='" . $conn->real_escape_string($newPaytoid) . "' WHERE EmployeeID='" . $hrmconn->real_escape_string($employeeID) . "'");
                }
            }
        }

        echo json_encode([
            "status" => "Success",
            "message" => "Bank details updated successfully.",
            "bankDocPath" => $bankDocPath
        ]);
        exit;
    } else if ($section === 'contact') {
        $mobileNo = trim($_POST['mobileNo'] ?? '');
        $emergencycontactno = trim($_POST['emergencycontactno'] ?? '');

        if (empty($mobileNo)) {
            echo json_encode(["status" => "Error", "message" => "Mobile Number is required."]);
            exit;
        }

        $sql = "UPDATE hrm_LabourMaster 
                SET MobileNo = ?, emergencycontactno = ?, updated_at = ? 
                WHERE EmployeeID = ?";
        $stmt = $hrmconn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssss", $mobileNo, $emergencycontactno, $now, $employeeID);
            $stmt->execute();
            $stmt->close();
            echo json_encode(["status" => "Success", "message" => "Contact details updated successfully."]);
        } else {
            echo json_encode(["status" => "Error", "message" => $hrmconn->error]);
        }
        exit;
    } else if ($section === 'address') {
        $current_address   = $_POST['current_address'] ?? '';
        $permanent_address = $_POST['permanent_address'] ?? '';
        $stateid           = !empty($_POST['stateid']) ? $_POST['stateid'] : null;
        $districtid        = !empty($_POST['districtid']) ? $_POST['districtid'] : null;
        $tahseel           = !empty($_POST['tahseel']) ? $_POST['tahseel'] : null;
        $city              = !empty($_POST['city']) ? $_POST['city'] : null;
        $address           = !empty($current_address) ? $current_address : ($_POST['address'] ?? null);

        $sql = "UPDATE hrm_LabourMaster 
                SET AddressLine1 = ?, AddressLine2 = ?, stateid = ?, districtid = ?, tahseel = ?, city = ?, address = ?, updated_at = ? 
                WHERE EmployeeID = ?";
        $stmt = $hrmconn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sssssssss", $current_address, $permanent_address, $stateid, $districtid, $tahseel, $city, $address, $now, $employeeID);
            $stmt->execute();
            $stmt->close();
            echo json_encode(["status" => "Success", "message" => "Address details updated successfully."]);
        } else {
            echo json_encode(["status" => "Error", "message" => $hrmconn->error]);
        }
        exit;
    } else if ($section === 'skills') {
        $grafting_skill = $_POST['grafting_skill'] ?? 'no';
        $workingLocation = $_POST['workingLocation'] ?? '';
        $PayPerDayAmount = floatval($_POST['PayPerDayAmount'] ?? 0);

        // Auto-resolve companyid, subcompanyid, projectid from hrm_LabourMaster if not passed
        if (empty($companyid) || empty($subcompanyid) || empty($projectid)) {
            $empStmt = $hrmconn->prepare("SELECT companyid, subcompanyid, projectid FROM hrm_LabourMaster WHERE EmployeeID = ? LIMIT 1");
            if ($empStmt) {
                $empStmt->bind_param("s", $employeeID);
                $empStmt->execute();
                $empRow = $empStmt->get_result()->fetch_assoc();
                if ($empRow) {
                    if (empty($companyid) && !empty($empRow['companyid'])) $companyid = $empRow['companyid'];
                    if (empty($subcompanyid) && !empty($empRow['subcompanyid'])) $subcompanyid = $empRow['subcompanyid'];
                    if (empty($projectid) && !empty($empRow['projectid'])) $projectid = $empRow['projectid'];
                }
                $empStmt->close();
            }
        }

        // Fetch location name for WorkLocation text column
        $locName = '';
        if (!empty($workingLocation)) {
            $locQuery = "SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = ? LIMIT 1";
            $lStmt = $hrmconn->prepare($locQuery);
            if ($lStmt) {
                $lStmt->bind_param("s", $workingLocation);
                $lStmt->execute();
                $lRes = $lStmt->get_result()->fetch_assoc();
                if ($lRes && !empty($lRes['LocationName'])) {
                    $locName = $lRes['LocationName'];
                }
                $lStmt->close();
            }
        }

        // 1. Update hrm_LabourMaster with grafting skill, locationid and WorkLocation
        $sql = "UPDATE hrm_LabourMaster 
                SET graftingskill = ?, locationid = ?, WorkLocation = IF(? != '', ?, WorkLocation), updated_at = ? 
                WHERE EmployeeID = ?";
        $stmt = $hrmconn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssss", $grafting_skill, $workingLocation, $locName, $locName, $now, $employeeID);
            $stmt->execute();
            $stmt->close();
        }

        // 2. Insert or update amount in hrm_LabourPaymentMaster
        $chkPaymentSql = "SELECT sno FROM hrm_LabourPaymentMaster WHERE EmployeeID = ? LIMIT 1";
        $stmtChk = $hrmconn->prepare($chkPaymentSql);
        $paymentExists = false;
        if ($stmtChk) {
            $stmtChk->bind_param("s", $employeeID);
            $stmtChk->execute();
            $chkRes = $stmtChk->get_result();
            if ($chkRes && $chkRes->num_rows > 0) {
                $paymentExists = true;
            }
            $stmtChk->close();
        }

        if ($paymentExists) {
            $sqlWage = "UPDATE hrm_LabourPaymentMaster 
                        SET PerDayPayment = ? 
                        WHERE EmployeeID = ?";
            $stmtWage = $hrmconn->prepare($sqlWage);
            if ($stmtWage) {
                $stmtWage->bind_param("ds", $PayPerDayAmount, $employeeID);
                $stmtWage->execute();
                $stmtWage->close();
            }
        } else {
            $sqlWage = "INSERT INTO hrm_LabourPaymentMaster 
                        (companyid, subcompanyid, projectid, EmployeeID, PerDayPayment, CurrencyCode, PaymentMethod) 
                        VALUES (?, ?, ?, ?, ?, 'INR', 'Bank Transfer')";
            $stmtWage = $hrmconn->prepare($sqlWage);
            if ($stmtWage) {
                $stmtWage->bind_param("ssssd", $companyid, $subcompanyid, $projectid, $employeeID, $PayPerDayAmount);
                $stmtWage->execute();
                $stmtWage->close();
            }
        }

        echo json_encode(["status" => "Success", "message" => "Skills and assignment details updated successfully."]);
        exit;
    } else if ($section === 'refs') {
        $ref_names = $_POST['ref_name'] ?? [];
        $ref_relations = $_POST['ref_relation'] ?? [];
        $ref_mobiles = $_POST['ref_mobile'] ?? [];

        $deleteSql = "DELETE FROM hrm_LabourContactPersonDetails WHERE EmployeeId = ?";
        $delStmt = $hrmconn->prepare($deleteSql);
        if ($delStmt) {
            $delStmt->bind_param("s", $employeeID);
            $delStmt->execute();
            $delStmt->close();
        }

        for ($i = 0; $i < count($ref_names); $i++) {
            if (!empty($ref_names[$i])) {
                insertOrUpdateLabourContactPersonDetails(
                    $hrmconn, $companyid, $subcompanyid, $projectid, $employeeID,
                    $ref_names[$i], $ref_relations[$i], $ref_mobiles[$i],
                    '', '', '', '', ($i === 0 ? 'yes' : 'no')
                );
            }
        }
        echo json_encode(["status" => "Success", "message" => "Reference persons updated successfully."]);
        exit;
    }
} else if ($action == "GenerateIDCard") {
    $employeeID = $_POST['employeeID'] ?? null;
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    
    // Always regenerate to ensure updated info is captured
    
    $distpath = "../attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/";
    
    ob_start();
    try {
        $idCardPath = generateLabourIDCard($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $distpath);
        ob_get_clean();
        echo json_encode(["status" => "Success", "path" => $idCardPath]);
    } catch (Throwable $e) {
        ob_get_clean();
        echo json_encode(["status" => "Error", "message" => $e->getMessage()]);
    }
} else if ($action == "UpdatePresentDaysToCalculateAttendance") {
    $companyid = $_POST['companyid'] ?? 0;
    $subcompanyid = $_POST['subcompanyid'] ?? 0;
    $projectid = $_POST['projectid'] ?? 0;
    $loginname = $_POST['loginname'] ?? '';
    $NewPrestDays = $_POST['NewPrestDays'] ?? 0;
    $IncentiveGraftingamount = $_POST['IncentiveGraftingamount'] ?? 0;
    $IncentiveSeasionalamount = $_POST['IncentiveSeasionalamount'] ?? 0;
    $OvertimeAmount = $_POST['OvertimeAmount'] ?? 0;
    $Remark = $_POST['Remark'] ?? '';
    $EmployeeId = $_POST['EmployeeId'] ?? null;
    $DateFrom = $_POST['DateFrom'] ?? null;
    $DateTo = $_POST['DateTo'] ?? null;
    $LocationId = $_POST['LocationId'] ?? null;
    // NewPrestDays
    $response = ["status" => "error", "message" => "Unknown error"];

    $ssq = $hrmconn->query("SELECT * 
        FROM hrm_labourcalculateattendance 
        WHERE ProjectID='" . $hrmconn->real_escape_string($projectid) . "' 
          AND EmployeeID='" . $hrmconn->real_escape_string($EmployeeId) . "' 
          AND DateFrom='" . $hrmconn->real_escape_string($DateFrom) . "' 
          AND DateTo='" . $hrmconn->real_escape_string($DateTo) . "' AND LocationId ='" . $hrmconn->real_escape_string($LocationId) . "'");

    $updRemark = $Remark . ", BY " . $loginname . " ON DATE " . date('Y-m-d');
    if ($ssq && $ssq->num_rows == 1) {
        $drow = $ssq->fetch_assoc();
        $attendanceId = (int) $drow['AttendanceID'];
        $shouldUpdateDays = (isset($NewPrestDays) && $NewPrestDays !== '' && is_numeric($NewPrestDays) && (float)$NewPrestDays >= 0);
        if ($shouldUpdateDays) {
            $update = $hrmconn->query("UPDATE hrm_labourcalculateattendance SET UpdateRemark='" . $hrmconn->real_escape_string($updRemark) . "', PresentDays='" . (float)$NewPrestDays . "' WHERE AttendanceID=" . $attendanceId);

            // Synchronize PresentDays, basiclabourpayment and NetSalary in hrm_LabourCalculatedSalary if calculated salary record exists
            $salCheck = $hrmconn->query("SELECT SalaryID, PayPerDay FROM hrm_LabourCalculatedSalary WHERE ProjectID='" . $hrmconn->real_escape_string($projectid) . "' AND EmployeeID='" . $hrmconn->real_escape_string($EmployeeId) . "' AND DateFrom='" . $hrmconn->real_escape_string($DateFrom) . "' AND DateTo='" . $hrmconn->real_escape_string($DateTo) . "'");
            if ($salCheck && $salCheck->num_rows > 0) {
                while ($salRow = $salCheck->fetch_assoc()) {
                    $salaryId = (int)$salRow['SalaryID'];
                    $payPerDay = (float)($salRow['PayPerDay'] ?? 0);
                    $basiclabourpayment = (float)$NewPrestDays * $payPerDay;
                    $netSalary = $basiclabourpayment + (float)$IncentiveGraftingamount + (float)$IncentiveSeasionalamount + (float)$OvertimeAmount;
                    $hrmconn->query("UPDATE hrm_LabourCalculatedSalary SET PresentDays='" . (float)$NewPrestDays . "', basiclabourpayment='" . $basiclabourpayment . "', NetSalary='" . $netSalary . "', incentivegrafting='" . (float)$IncentiveGraftingamount . "', incentiveseasional='" . (float)$IncentiveSeasionalamount . "', overtimepayment='" . (float)$OvertimeAmount . "' WHERE SalaryID=" . $salaryId);
                }
            }
        } else {
            $update = $hrmconn->query("UPDATE hrm_labourcalculateattendance SET UpdateRemark='" . $hrmconn->real_escape_string($updRemark) . "' WHERE AttendanceID=" . $attendanceId);
        }

        $otherpayment = 0.00;
        $lbpresult = saveLabourOtherPayment($hrmconn, $projectid, $EmployeeId, $DateFrom, $DateTo, $IncentiveGraftingamount, $IncentiveSeasionalamount, $OvertimeAmount, $otherpayment, $updRemark);

        if ($update || $lbpresult['status']) {
            $response = ["status" => "success", "message" => "Attendance updated successfully."];
        } else {
            $response = ["status" => "error", "message" => "Failed to update attendance: " . $hrmconn->error];
        }
    } else if ($ssq && $ssq->num_rows > 1) {
        $data = [];
        while ($drow = $ssq->fetch_assoc()) {
            $data[] = $drow;
        }
        $response = ["status" => "morethan", "message" => "More than 1 matching attendance record found.", "data" => $data];
    } else {
        $response = ["status" => "error", "message" => "No matching attendance record found."];
    }

    echo json_encode($response);
} else if ($action == "UpdateFinalPaymentStatus") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $latitude = $_POST['latitude'] ?? null;
    $longitude = $_POST['longitude'] ?? null;
    $address = $_POST['address'] ?? '';
    $source = $_POST['source'] ?? '';
    $accuracyinmeter = $_POST['accuracy'] ?? null;
    $PaymentStatus = $_POST['PaymentStatus'] ?? '';

    $actualAmount = $_POST['actualAmount'] ?? 0;
    $paidAmount = $_POST['paidAmount'] ?? 0;
    $PaymentDate = $_POST['PaymentDate'] ?: date('Y-m-d');

    $LabourId = $_POST['LabourId'] ?? null;
    $SalaryId = $_POST['SalaryId'] ?? null;

    $transfer_mode = $_POST['transfer_mode'] ?? 'Cash';
    $utr_no = trim($_POST['utr_no'] ?? '');

    $requiredFields = [
        'companyid' => $companyid ?? null,
        'subcompanyid' => $subcompanyid ?? null,
        'projectid' => $projectid ?? null,
        'loginname' => $loginname ?? null
    ];

    $missing = [];
    foreach ($requiredFields as $field => $value) {
        if ($value === null || $value === '') {
            $missing[] = ucfirst($field);
        }
    }

    if (!empty($missing)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }

    if ($transfer_mode === 'Bank Transfer' && empty($utr_no)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'UTR No is mandatory for Bank Transfer.'
        ]);
        exit;
    }

    date_default_timezone_set('Asia/Kolkata');
    $updatedat = date('Y-m-d H:i:s');
    $Remark = ($_POST['Remark'] ?? '') .
        " UPDATE BY {$loginname} ON {$updatedat}";

    $uphotoPath = "";

    /* ==========================
       PHOTO UPLOAD (SAFE)
    ========================== */
    if (
        isset($_FILES['Photo']) &&
        $_FILES['Photo']['error'] === UPLOAD_ERR_OK
    ) {
        $uploadDir = __DIR__ . "/../attachementfile/".$companyid . "/".$subcompanyid . "/" . $projectid . "/labourpayment_photo/" . date('Ymd') . "/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $photoName = "lbps_{$LabourId}_" . time() . ".jpg";
        $photoPath = $uploadDir . $photoName;

        if (move_uploaded_file($_FILES['Photo']['tmp_name'], $photoPath)) {
            $uphotoPath = "attachementfile/".$companyid . "/".$subcompanyid . "/" . $projectid . "/labourpayment_photo/" . date('Ymd') . "/".$photoName;
        }
    }

    /* ==========================
       FETCH SALARY & LABOUR DATA
    ========================== */
    $salaryData = [];
    $salaryQuery = "SELECT * FROM hrm_LabourCalculatedSalary WHERE SalaryID = ? AND EmployeeID = ? LIMIT 1";
    $stmtSal = $hrmconn->prepare($salaryQuery);
    if ($stmtSal) {
        $stmtSal->bind_param("is", $SalaryId, $LabourId);
        $stmtSal->execute();
        $salaryData = $stmtSal->get_result()->fetch_assoc();
        $stmtSal->close();
    }

    $emplist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $LabourId);
    $empname = "";
    $emppaytoid = "";
    $EmpEmail = "";
    $EmpMobileNo = "";
    if (!empty($emplist)) {
        $empRow = $emplist[0];
        $empname = trim(($empRow['FirstName'] ?? '') . ' ' . ($empRow['MiddleName'] ?? '') . ' ' . ($empRow['LastName'] ?? ''));
        $emppaytoid = $empRow['paytoid'] ?? '';
        $EmpEmail = $empRow['Email'] ?? '';
        $EmpMobileNo = $empRow['MobileNo'] ?? '';
    }

    if (empty($emppaytoid) && !empty($empname)) {
        $paytoin = savetodbnewpayto_hrm($conn, $empname, '', '', '', '', '', '', $companyid, $subcompanyid, $projectid, $EmpMobileNo, $EmpEmail, $loginname);
        if (!empty($paytoin[0]['paytoid'])) {
            $emppaytoid = $paytoin[0]['paytoid'];
            $hrmconn->query("UPDATE hrm_LabourMaster SET paytoid='" . $conn->real_escape_string($emppaytoid) . "' WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $hrmconn->real_escape_string($companyid) . "' AND subcompanyid = '" . $hrmconn->real_escape_string($subcompanyid) . "' AND projectid = '" . $hrmconn->real_escape_string($projectid) . "' and EmployeeID='" . $hrmconn->real_escape_string($LabourId) . "'");
        }
    }

    /* ==========================
       PURPOSE CATEGORY DETAILS
    ========================== */
    $purposecategory = "Labour Payment";
    $purposecategoryid = "6";
    $purposesubcategory = "";
    $purposesubcategoryid = "";
    $purposename = "Labour Payment";
    $purposecode = "";

    $purposedetails = getpurposenamewithcat_hrm($conn, $companyid, $subcompanyid, $projectid, "purposecategoryid", '6');
    if (!empty($purposedetails)) {
        $purposecategory     = $purposedetails[0]['pcategory'] ?? 'Labour Payment';
        $purposecategoryid   = $purposedetails[0]['pcategorycode'] ?? '6';
        $purposesubcategory  = $purposedetails[0]['psub_category'] ?? '';
        $purposesubcategoryid= $purposedetails[0]['psub_categoryid'] ?? '';
        $purposename         = $purposedetails[0]['purpose'] ?? 'Labour Payment';
        $purposecode         = $purposedetails[0]['purposecode'] ?? '';
    }

    /* ==========================
       UPDATE hrm_LabourCalculatedSalary
    ========================== */
    try {
        $sql = "
            UPDATE hrm_LabourCalculatedSalary
            SET PaidAmount = COALESCE(PaidAmount, 0) + ?,
                PaymentStatus = ?,
                PaymentDate = ?,
                PaymentRemark = ?,
                PaymentReferenceFilepath = ?,
                PaymentMethod = ?,
                transactionno = ?,
                latitude = ?,
                longitude = ?,
                address = ?,
                source = ?,
                accuracyinmeter = ?,
                updated_at = ?,
                updated_by = ?
            WHERE SalaryID = ? AND EmployeeID = ?
        ";

        $stmt = $hrmconn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $hrmconn->error);
        }

        $stmt->bind_param(
            "dsssssssssssssis",
            $paidAmount,
            $PaymentStatus,
            $PaymentDate,
            $Remark,
            $uphotoPath,
            $transfer_mode,
            $utr_no,
            $latitude,
            $longitude,
            $address,
            $source,
            $accuracyinmeter,
            $updatedat,
            $loginname,
            $SalaryId,
            $LabourId
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();

        /* ==========================
           BOOK ENTRIES IN ACCOUNTING
        ========================== */
        if ($transfer_mode === 'Bank Transfer') {
            // Fetch labour bank details
            $paytoaccountno = '';
            $paytobankname = '';
            $paytoifsc = '';
            $bankQuery = "SELECT bankname, accountnumber, ifsccode FROM hrm_labourbankdetails WHERE labourid = ? AND (tedit IS NULL OR tedit = '') LIMIT 1";
            $stmtB = $hrmconn->prepare($bankQuery);
            if ($stmtB) {
                $stmtB->bind_param("s", $LabourId);
                $stmtB->execute();
                $bankRes = $stmtB->get_result()->fetch_assoc();
                $stmtB->close();
                if ($bankRes) {
                    $paytoaccountno = $bankRes['accountnumber'] ?? '';
                    $paytobankname = $bankRes['bankname'] ?? '';
                    $paytoifsc = $bankRes['ifsccode'] ?? '';
                }
            }
            if (empty($paytoaccountno)) {
                $numLabourId = intval(preg_replace('/[^0-9]/', '', $LabourId));
                if ($numLabourId > 0) {
                    $bankOldQuery = "SELECT BankName, AccountNumber, IFSCCode FROM hrm_EmployeeBankDetails WHERE EmployeeID = ? AND EmployeeID > 0 AND (tedit IS NULL OR tedit = '') ORDER BY BankDetailID DESC LIMIT 1";
                    $stmtBOld = $hrmconn->prepare($bankOldQuery);
                    if ($stmtBOld) {
                        $stmtBOld->bind_param("i", $numLabourId);
                        $stmtBOld->execute();
                        $bankOldRes = $stmtBOld->get_result()->fetch_assoc();
                        $stmtBOld->close();
                        if ($bankOldRes) {
                            $paytoaccountno = $bankOldRes['AccountNumber'] ?? '';
                            $paytobankname = $bankOldRes['BankName'] ?? '';
                            $paytoifsc = $bankOldRes['IFSCCode'] ?? '';
                        }
                    }
                }
            }

            $request_no = $salaryData['requestno'] ?? '';
            $newreference_no = getnewreferenceno_hrm($conn, $companyid, $subcompanyid, $projectid, $paytoaccountno);
            if (!$newreference_no) {
                $uuid = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                $newreference_no = 'REF-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $uuid;
            }

            $projectDetails = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
            $finyeartype    = $projectDetails[0]['finyr'] ?? '';
            $financialYear  = getFinancialYearHrm($PaymentDate, $finyeartype);
            $tsno           = GetMaxRefTempSnoHRM($conn);

            // Insert tbl_maintransaction
            $insMain = "INSERT INTO tbl_maintransaction (
                tsno, companyid, subcompanyid, projectid, finyear, request_no, reference_no,
                bookdate, book_amount, transactionno, paymentduedate, requesteddate, requestedby,
                transaction_date, transaction_type, transferedby, transfered_amount, beforeedit_amount,
                transferreceiptpath, payto, paytoid, beneficierie, description, empid,
                paytoaccountno, paytobankname, paytoifsc, paymenttype, book_source,
                book_status, final_status, bookdoneby, actual_trf_amt
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )";
            $stmtMain = $conn->prepare($insMain);
            if ($stmtMain) {
                $transaction_type = 'Bank Transfer';
                $beneficierie = 'Individual';
                $paymenttype = 'Payment';
                $book_source = 'SalaryModule';
                $book_status = 'completed';
                $final_status = 'Approved';
                $transDate = $PaymentDate;
                
                $stmtMain->bind_param(
                    "isssssssssssssssssssssssssssssssd",
                    $tsno, $companyid, $subcompanyid, $projectid, $financialYear, $request_no, $newreference_no,
                    $PaymentDate, $paidAmount, $utr_no, $PaymentDate, $PaymentDate, $loginname,
                    $transDate, $transaction_type, $loginname, $paidAmount, $paidAmount,
                    $uphotoPath, $empname, $emppaytoid, $beneficierie, $Remark, $LabourId,
                    $paytoaccountno, $paytobankname, $paytoifsc, $paymenttype, $book_source,
                    $book_status, $final_status, $loginname, $paidAmount
                );
                $stmtMain->execute();
                $stmtMain->close();
            }

            // Insert tbl_maintransaction_breakdown
            $insBrk = "INSERT INTO tbl_maintransaction_breakdown (
                companyid, subcompanyid, projectid, request_no, reference_no,
                beneficiary_name, amount, beforeedit_amount, purpose_category, purpose_categoryid,
                purpose_subcategory, purpose_subcategoryid, purpose, purpose_code,
                description, empid, documentation_path
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?
            )";
            $stmtBrk = $conn->prepare($insBrk);
            if ($stmtBrk) {
                $stmtBrk->bind_param(
                    "ssssssdssssssssss",
                    $companyid, $subcompanyid, $projectid, $request_no, $newreference_no,
                    $empname, $paidAmount, $paidAmount, $purposecategory, $purposecategoryid,
                    $purposesubcategory, $purposesubcategoryid, $purposename, $purposecode,
                    $Remark, $LabourId, $uphotoPath
                );
                $stmtBrk->execute();
                $stmtBrk->close();
            }

        } else if ($transfer_mode === 'Cash') {
            // Insert into cash_account
            $request_no = $salaryData['requestno'] ?? '';
            $ref_no = $request_no;
            if (empty($ref_no)) {
                $ref_no = getnewreferenceno_hrm($conn, $companyid, $subcompanyid, $projectid, '');
                if (!$ref_no) {
                    $uuid = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                    $ref_no = 'REF-CSH-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $uuid;
                }
            }

            $insCash = "INSERT INTO cash_account (
                idate, companyid, subcompanyid, projectid, request_no, reference_no,
                paytoid, cash_payto, beneficiary_name, cash_amount, adjustment_amount, adjustedamount,
                purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory,
                purpose, purpose_code, description, document_path
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, '0', 0,
                ?, ?, ?, ?,
                ?, ?, ?, ?
            )";
            $stmtCash = $conn->prepare($insCash);
            if ($stmtCash) {
                $stmtCash->bind_param(
                    "ssssssssssssssssss",
                    $PaymentDate, $companyid, $subcompanyid, $projectid, $request_no, $ref_no,
                    $emppaytoid, $empname, $empname, $paidAmount,
                    $purposecategoryid, $purposecategory, $purposesubcategoryid, $purposesubcategory,
                    $purposename, $purposecode, $Remark, $uphotoPath
                );
                $stmtCash->execute();
                $stmtCash->close();
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Payment status updated successfully."
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Exception: " . $e->getMessage()
        ]);
    }
} else if ($action == "ChangeLabourPaymentTransferMode") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'] ?? '';
    $SalaryId = $_POST['SalaryId'] ?? null;
    $LabourId = $_POST['LabourId'] ?? null;
    $new_transfer_mode = $_POST['transfer_mode'] ?? 'Cash';
    $new_utr_no = trim($_POST['utr_no'] ?? '');
    $user_remark = trim($_POST['remark'] ?? '');

    if (empty($SalaryId) || empty($LabourId)) {
        echo json_encode([
            "status" => "error",
            "message" => "Salary ID and Labour ID are required."
        ]);
        exit;
    }

    if ($new_transfer_mode === 'Bank Transfer' && empty($new_utr_no)) {
        echo json_encode([
            "status" => "error",
            "message" => "UTR No is mandatory for Bank Transfer."
        ]);
        exit;
    }

    // Fetch existing salary record
    $salaryQuery = "SELECT * FROM hrm_LabourCalculatedSalary WHERE SalaryID = ? AND EmployeeID = ? LIMIT 1";
    $stmtSal = $hrmconn->prepare($salaryQuery);
    if (!$stmtSal) {
        echo json_encode(["status" => "error", "message" => "Prepare failed: " . $hrmconn->error]);
        exit;
    }
    $stmtSal->bind_param("is", $SalaryId, $LabourId);
    $stmtSal->execute();
    $salaryData = $stmtSal->get_result()->fetch_assoc();
    $stmtSal->close();

    if (!$salaryData) {
        echo json_encode(["status" => "error", "message" => "Salary record not found."]);
        exit;
    }

    $old_mode = $salaryData['PaymentMethod'] ?: 'Cash';
    $old_utr = $salaryData['transactionno'] ?? '';
    $request_no = $salaryData['requestno'] ?? '';
    $PaymentDate = !empty($salaryData['PaymentDate']) ? $salaryData['PaymentDate'] : date('Y-m-d');
    $paidAmount = floatval($salaryData['PaidAmount'] > 0 ? $salaryData['PaidAmount'] : $salaryData['NetSalary']);
    $uphotoPath = $salaryData['PaymentReferenceFilepath'] ?? '';

    date_default_timezone_set('Asia/Kolkata');
    $updatedat = date('Y-m-d H:i:s');
    $auditRemark = " [Mode changed from {$old_mode} to {$new_transfer_mode}";
    if ($new_transfer_mode === 'Bank Transfer' && !empty($new_utr_no)) {
        $auditRemark .= " (UTR: {$new_utr_no})";
    }
    if (!empty($user_remark)) {
        $auditRemark .= " - Note: {$user_remark}";
    }
    $auditRemark .= " by {$loginname} on {$updatedat}]";

    try {
        // 1. Update hrm_LabourCalculatedSalary
        $updSql = "UPDATE hrm_LabourCalculatedSalary 
                   SET PaymentMethod = ?,
                       transactionno = ?,
                       PaymentRemark = CONCAT(COALESCE(PaymentRemark, ''), ?),
                       updated_at = ?,
                       updated_by = ?
                   WHERE SalaryID = ? AND EmployeeID = ?";
        $stmtUpd = $hrmconn->prepare($updSql);
        if (!$stmtUpd) {
            throw new Exception("Salary update prepare failed: " . $hrmconn->error);
        }
        $stmtUpd->bind_param("sssssis", $new_transfer_mode, $new_utr_no, $auditRemark, $updatedat, $loginname, $SalaryId, $LabourId);
        $stmtUpd->execute();
        $stmtUpd->close();

        // 2. Fetch labour & purpose details for accounting synchronization
        $emplist = fetchLabourData($hrmconn, $companyid, $subcompanyid, $projectid, "EmployeeID", $LabourId);
        $empname = "";
        $emppaytoid = "";
        $EmpEmail = "";
        $EmpMobileNo = "";
        if (!empty($emplist)) {
            $empRow = $emplist[0];
            $empname = trim(($empRow['FirstName'] ?? '') . ' ' . ($empRow['MiddleName'] ?? '') . ' ' . ($empRow['LastName'] ?? ''));
            $emppaytoid = $empRow['paytoid'] ?? '';
            $EmpEmail = $empRow['Email'] ?? '';
            $EmpMobileNo = $empRow['MobileNo'] ?? '';
        }

        if (empty($emppaytoid) && !empty($empname)) {
            $paytoin = savetodbnewpayto_hrm($conn, $empname, '', '', '', '', '', '', $companyid, $subcompanyid, $projectid, $EmpMobileNo, $EmpEmail, $loginname);
            if (!empty($paytoin[0]['paytoid'])) {
                $emppaytoid = $paytoin[0]['paytoid'];
                $hrmconn->query("UPDATE hrm_LabourMaster SET paytoid='" . $conn->real_escape_string($emppaytoid) . "' WHERE (tedit IS NULL OR tedit = '') AND companyid = '" . $hrmconn->real_escape_string($companyid) . "' AND subcompanyid = '" . $hrmconn->real_escape_string($subcompanyid) . "' AND projectid = '" . $hrmconn->real_escape_string($projectid) . "' and EmployeeID='" . $hrmconn->real_escape_string($LabourId) . "'");
            }
        }

        $purposecategory = "Labour Payment";
        $purposecategoryid = "6";
        $purposesubcategory = "";
        $purposesubcategoryid = "";
        $purposename = "Labour Payment";
        $purposecode = "";

        $purposedetails = getpurposenamewithcat_hrm($conn, $companyid, $subcompanyid, $projectid, "purposecategoryid", '6');
        if (!empty($purposedetails)) {
            $purposecategory     = $purposedetails[0]['pcategory'] ?? 'Labour Payment';
            $purposecategoryid   = $purposedetails[0]['pcategorycode'] ?? '6';
            $purposesubcategory  = $purposedetails[0]['psub_category'] ?? '';
            $purposesubcategoryid= $purposedetails[0]['psub_categoryid'] ?? '';
            $purposename         = $purposedetails[0]['purpose'] ?? 'Labour Payment';
            $purposecode         = $purposedetails[0]['purposecode'] ?? '';
        }

        $accountingRemark = "Mode changed to {$new_transfer_mode} by {$loginname} on {$updatedat}";
        if (!empty($user_remark)) {
            $accountingRemark .= " - " . $user_remark;
        }

        // 3. Accounting synchronization based on mode switch
        if ($old_mode !== 'Bank Transfer' && $new_transfer_mode === 'Bank Transfer') {
            // Cash -> Bank Transfer:
            // a) Void any existing cash entry
            if (!empty($request_no)) {
                $stmtVoidCash = $conn->prepare("UPDATE cash_account SET tedit = ?, special_remark = CONCAT(COALESCE(special_remark, ''), ' [Voided: Switched to Bank Transfer by ', ?, ' on ', ?, ']') WHERE request_no = ? AND (tedit IS NULL OR tedit = '')");
                if ($stmtVoidCash) {
                    $stmtVoidCash->bind_param("ssss", $updatedat, $loginname, $updatedat, $request_no);
                    $stmtVoidCash->execute();
                    $stmtVoidCash->close();
                }
            }

            // b) Insert into tbl_maintransaction & breakdown
            $paytoaccountno = '';
            $paytobankname = '';
            $paytoifsc = '';
            $bankQuery = "SELECT bankname, accountnumber, ifsccode FROM hrm_labourbankdetails WHERE labourid = ? AND (tedit IS NULL OR tedit = '') LIMIT 1";
            $stmtB = $hrmconn->prepare($bankQuery);
            if ($stmtB) {
                $stmtB->bind_param("s", $LabourId);
                $stmtB->execute();
                $bankRes = $stmtB->get_result()->fetch_assoc();
                $stmtB->close();
                if ($bankRes) {
                    $paytoaccountno = $bankRes['accountnumber'] ?? '';
                    $paytobankname = $bankRes['bankname'] ?? '';
                    $paytoifsc = $bankRes['ifsccode'] ?? '';
                }
            }

            $newreference_no = getnewreferenceno_hrm($conn, $companyid, $subcompanyid, $projectid, $paytoaccountno);
            if (!$newreference_no) {
                $uuid = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                $newreference_no = 'REF-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $uuid;
            }

            $projectDetails = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
            $finyeartype    = $projectDetails[0]['finyr'] ?? '';
            $financialYear  = getFinancialYearHrm($PaymentDate, $finyeartype);
            $tsno           = GetMaxRefTempSnoHRM($conn);

            $insMain = "INSERT INTO tbl_maintransaction (
                tsno, companyid, subcompanyid, projectid, finyear, request_no, reference_no,
                bookdate, book_amount, transactionno, paymentduedate, requesteddate, requestedby,
                transaction_date, transaction_type, transferedby, transfered_amount, beforeedit_amount,
                transferreceiptpath, payto, paytoid, beneficierie, description, empid,
                paytoaccountno, paytobankname, paytoifsc, paymenttype, book_source,
                book_status, final_status, bookdoneby, actual_trf_amt
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?
            )";
            $stmtMain = $conn->prepare($insMain);
            if ($stmtMain) {
                $transaction_type = 'Bank Transfer';
                $beneficierie = 'Individual';
                $paymenttype = 'Payment';
                $book_source = 'SalaryModule';
                $book_status = 'completed';
                $final_status = 'Approved';
                $stmtMain->bind_param(
                    "isssssssssssssssssssssssssssssssd",
                    $tsno, $companyid, $subcompanyid, $projectid, $financialYear, $request_no, $newreference_no,
                    $PaymentDate, $paidAmount, $new_utr_no, $PaymentDate, $PaymentDate, $loginname,
                    $PaymentDate, $transaction_type, $loginname, $paidAmount, $paidAmount,
                    $uphotoPath, $empname, $emppaytoid, $beneficierie, $accountingRemark, $LabourId,
                    $paytoaccountno, $paytobankname, $paytoifsc, $paymenttype, $book_source,
                    $book_status, $final_status, $loginname, $paidAmount
                );
                $stmtMain->execute();
                $stmtMain->close();
            }

            $insBrk = "INSERT INTO tbl_maintransaction_breakdown (
                companyid, subcompanyid, projectid, request_no, reference_no,
                beneficiary_name, amount, beforeedit_amount, purpose_category, purpose_categoryid,
                purpose_subcategory, purpose_subcategoryid, purpose, purpose_code,
                description, empid, documentation_path
            ) VALUES (
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                ?, ?, ?, ?,
                ?, ?, ?
            )";
            $stmtBrk = $conn->prepare($insBrk);
            if ($stmtBrk) {
                $stmtBrk->bind_param(
                    "ssssssdssssssssss",
                    $companyid, $subcompanyid, $projectid, $request_no, $newreference_no,
                    $empname, $paidAmount, $paidAmount, $purposecategory, $purposecategoryid,
                    $purposesubcategory, $purposesubcategoryid, $purposename, $purposecode,
                    $accountingRemark, $LabourId, $uphotoPath
                );
                $stmtBrk->execute();
                $stmtBrk->close();
            }

        } else if ($old_mode !== 'Cash' && $new_transfer_mode === 'Cash') {
            // Bank Transfer -> Cash:
            // a) Void any existing Bank Transfer entries
            if (!empty($request_no)) {
                $stmtVoidMain = $conn->prepare("UPDATE tbl_maintransaction SET tedit = ?, tedit_remark = CONCAT(COALESCE(tedit_remark, ''), ' [Voided: Switched to Cash by ', ?, ' on ', ?, ']') WHERE empid = ? AND request_no = ? AND (tedit IS NULL OR tedit = '')");
                if ($stmtVoidMain) {
                    $stmtVoidMain->bind_param("sssss", $updatedat, $loginname, $updatedat, $LabourId, $request_no);
                    $stmtVoidMain->execute();
                    $stmtVoidMain->close();
                }

                $stmtVoidBrk = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET tedit = ?, tedit_remark = CONCAT(COALESCE(tedit_remark, ''), ' [Voided: Switched to Cash by ', ?, ' on ', ?, ']') WHERE empid = ? AND request_no = ? AND (tedit IS NULL OR tedit = '')");
                if ($stmtVoidBrk) {
                    $stmtVoidBrk->bind_param("sssss", $updatedat, $loginname, $updatedat, $LabourId, $request_no);
                    $stmtVoidBrk->execute();
                    $stmtVoidBrk->close();
                }
            }

            // b) Insert into cash_account
            $ref_no = $request_no;
            if (empty($ref_no)) {
                $ref_no = getnewreferenceno_hrm($conn, $companyid, $subcompanyid, $projectid, '');
                if (!$ref_no) {
                    $uuid = strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
                    $ref_no = 'REF-CSH-' . $companyid . '-' . $subcompanyid . '-' . $projectid . '-' . $uuid;
                }
            }

            $insCash = "INSERT INTO cash_account (
                idate, companyid, subcompanyid, projectid, request_no, reference_no,
                paytoid, cash_payto, beneficiary_name, cash_amount, adjustment_amount, adjustedamount,
                purpose_categoryid, purpose_category, purpose_subcategoryid, purpose_subcategory,
                purpose, purpose_code, description, document_path
            ) VALUES (
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, '0', 0,
                ?, ?, ?, ?,
                ?, ?, ?, ?
            )";
            $stmtCash = $conn->prepare($insCash);
            if ($stmtCash) {
                $stmtCash->bind_param(
                    "ssssssssssssssssss",
                    $PaymentDate, $companyid, $subcompanyid, $projectid, $request_no, $ref_no,
                    $emppaytoid, $empname, $empname, $paidAmount,
                    $purposecategoryid, $purposecategory, $purposesubcategoryid, $purposesubcategory,
                    $purposename, $purposecode, $accountingRemark, $uphotoPath
                );
                $stmtCash->execute();
                $stmtCash->close();
            }

        } else if ($new_transfer_mode === 'Bank Transfer' && !empty($new_utr_no)) {
            // Already Bank Transfer, UTR updated:
            if (!empty($request_no)) {
                $stmtUpdMainUTR = $conn->prepare("UPDATE tbl_maintransaction SET transactionno = ? WHERE empid = ? AND request_no = ? AND (tedit IS NULL OR tedit = '')");
                if ($stmtUpdMainUTR) {
                    $stmtUpdMainUTR->bind_param("sss", $new_utr_no, $LabourId, $request_no);
                    $stmtUpdMainUTR->execute();
                    $stmtUpdMainUTR->close();
                }
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Transfer mode updated successfully to " . $new_transfer_mode . "."
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Exception: " . $e->getMessage()
        ]);
    }

} else if ($action == "UndoLabourPaymentStatus") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $loginname = $_POST['loginname'] ?? '';
    $SalaryId = $_POST['SalaryId'] ?? null;
    $LabourId = $_POST['LabourId'] ?? null;
    $undo_reason = trim($_POST['remark'] ?? '');

    if (empty($SalaryId) || empty($LabourId)) {
        echo json_encode([
            "status" => "error",
            "message" => "Salary ID and Labour ID are required."
        ]);
        exit;
    }

    // Fetch existing salary record
    $salaryQuery = "SELECT * FROM hrm_LabourCalculatedSalary WHERE SalaryID = ? AND EmployeeID = ? LIMIT 1";
    $stmtSal = $hrmconn->prepare($salaryQuery);
    if (!$stmtSal) {
        echo json_encode(["status" => "error", "message" => "Prepare failed: " . $hrmconn->error]);
        exit;
    }
    $stmtSal->bind_param("is", $SalaryId, $LabourId);
    $stmtSal->execute();
    $salaryData = $stmtSal->get_result()->fetch_assoc();
    $stmtSal->close();

    if (!$salaryData) {
        echo json_encode(["status" => "error", "message" => "Salary record not found."]);
        exit;
    }

    $request_no = $salaryData['requestno'] ?? '';
    date_default_timezone_set('Asia/Kolkata');
    $updatedat = date('Y-m-d H:i:s');
    $undoRemark = " [Payment Undone to Unpaid";
    if (!empty($undo_reason)) {
        $undoRemark .= " - Reason: {$undo_reason}";
    }
    $undoRemark .= " by {$loginname} on {$updatedat}]";

    try {
        // 1. Revert hrm_LabourCalculatedSalary to Unpaid
        $undoSql = "UPDATE hrm_LabourCalculatedSalary 
                    SET PaymentStatus = '',
                        PaidAmount = 0.00,
                        PaymentDate = NULL,
                        PaymentMethod = NULL,
                        transactionno = NULL,
                        PaymentReferenceFilepath = NULL,
                        latitude = NULL,
                        longitude = NULL,
                        address = NULL,
                        source = NULL,
                        accuracyinmeter = NULL,
                        PaymentRemark = CONCAT(COALESCE(PaymentRemark, ''), ?),
                        updated_at = ?,
                        updated_by = ?
                    WHERE SalaryID = ? AND EmployeeID = ?";
        $stmtUndo = $hrmconn->prepare($undoSql);
        if (!$stmtUndo) {
            throw new Exception("Salary undo prepare failed: " . $hrmconn->error);
        }
        $stmtUndo->bind_param("sssis", $undoRemark, $updatedat, $loginname, $SalaryId, $LabourId);
        $stmtUndo->execute();
        $stmtUndo->close();

        // 2. Void accounting entries in tbl_maintransaction, tbl_maintransaction_breakdown, cash_account
        if (!empty($request_no)) {
            $voidRemark = "Payment Undone to Unpaid by {$loginname} on {$updatedat}";
            if (!empty($undo_reason)) {
                $voidRemark .= " - " . $undo_reason;
            }

            $stmtVoidMain = $conn->prepare("UPDATE tbl_maintransaction SET tedit = ?, tedit_remark = CONCAT(COALESCE(tedit_remark, ''), ' [', ?, ']') WHERE empid = ? AND request_no = ? AND (tedit IS NULL OR tedit = '')");
            if ($stmtVoidMain) {
                $stmtVoidMain->bind_param("ssss", $updatedat, $voidRemark, $LabourId, $request_no);
                $stmtVoidMain->execute();
                $stmtVoidMain->close();
            }

            $stmtVoidBrk = $conn->prepare("UPDATE tbl_maintransaction_breakdown SET tedit = ?, tedit_remark = CONCAT(COALESCE(tedit_remark, ''), ' [', ?, ']') WHERE empid = ? AND request_no = ? AND (tedit IS NULL OR tedit = '')");
            if ($stmtVoidBrk) {
                $stmtVoidBrk->bind_param("ssss", $updatedat, $voidRemark, $LabourId, $request_no);
                $stmtVoidBrk->execute();
                $stmtVoidBrk->close();
            }

            $stmtVoidCash = $conn->prepare("UPDATE cash_account SET tedit = ?, special_remark = CONCAT(COALESCE(special_remark, ''), ' [', ?, ']') WHERE request_no = ? AND (tedit IS NULL OR tedit = '')");
            if ($stmtVoidCash) {
                $stmtVoidCash->bind_param("sss", $updatedat, $voidRemark, $request_no);
                $stmtVoidCash->execute();
                $stmtVoidCash->close();
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Payment status reverted from Paid to Unpaid successfully."
        ]);

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Exception: " . $e->getMessage()
        ]);
    }
} else if ($action == "AllUpdateFinalPaymentStatus") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $SalaryIds = $_POST['SalaryIds'] ?? null;
    $PaymentStatus = 'Paid';
    $PaymentDate = date('Y-m-d');
    $loginname = $_POST['loginname'] ?? '';
    $Remark = ($_POST['Remark'] ?? '') . " UPDATE BY " . $loginname . " on date " . date('Y-m-d');
    $ReferencFil = $_POST['ReferencFil'] ?? null;
    $FilePath = "";
    if (isset($_FILES['ReferencFil']) && $_FILES['ReferencFil']['error'] === UPLOAD_ERR_OK) {
        // File info
        $fileTmpPath = $_FILES['ReferencFil']['tmp_name'];
        $fileName = $_FILES['ReferencFil']['name'];
        $fileSize = $_FILES['ReferencFil']['size'];
        $fileType = $_FILES['ReferencFil']['type'];
        // Set upload folder
        $uploadFolder = "../attachementfile/{$companyid}/{$subcompanyid}/{$projectid}/LabourWagesPDF/{$PaymentDate}/";
        if (!is_dir($uploadFolder)) {
            mkdir($uploadFolder, 0777, true);
        }
        // Sanitize file name (optional but recommended)
        $fileNameClean = preg_replace("/[^a-zA-Z0-9\._-]/", "_", $fileName);
        $destPath = $uploadFolder . $fileNameClean;

        // Move uploaded file
        if (move_uploaded_file($fileTmpPath, $destPath)) {
            $FilePath = str_replace("../", "", $destPath);
        } else {
            $FilePath = "";
        }
    }

    // echo "file path: ".$FilePath;  


    try {
        if (!empty($SalaryIds)) {
            // Convert comma-separated IDs into unique integers
            $ids = array_unique(array_map('intval', explode(',', $SalaryIds)));

            if (empty($ids)) {
                echo json_encode([
                    "status" => "error",
                    "message" => "No valid SalaryIds provided."
                ]);
                exit;
            }

            // Begin transaction
            $hrmconn->begin_transaction();

            // Build placeholders
            $placeholders = implode(',', array_fill(0, count($ids), '?'));

            // Step 1: Fetch data from hrm_LabourCalculatedSalary
            $sqlSelect = "SELECT SalaryID, ProjectID, SalaryYear, SalaryMonth, DateFrom, DateTo, EmployeeID 
                          FROM hrm_LabourCalculatedSalary 
                          WHERE SalaryID IN ($placeholders)";
            $stmtSelect = $hrmconn->prepare($sqlSelect);

            $types = str_repeat('i', count($ids));
            $stmtSelect->bind_param(...array_merge([$types], $ids));
            $stmtSelect->execute();
            $result = $stmtSelect->get_result();

            if ($result->num_rows === 0) {
                echo json_encode([
                    "status" => "error",
                    "message" => "No matching SalaryIDs found."
                ]);
                $stmtSelect->close();
                $hrmconn->rollback();
                exit;
            }

            // Step 2: Update hrm_labourcalculateattendance based on fetched data
            $stmtUpdateAttendance = $hrmconn->prepare("
                UPDATE hrm_labourcalculateattendance
                SET SalaryID = ?
                WHERE ProjectID = ? AND AttendanceYear = ? AND AttendanceMonth = ? AND DateFrom = ? AND DateTo = ? AND EmployeeID = ?
            ");

            while ($row = $result->fetch_assoc()) {
                $stmtUpdateAttendance->bind_param(
                    'ssssssi',
                    $row['SalaryID'],
                    $row['ProjectID'],
                    $row['SalaryYear'],
                    $row['SalaryMonth'],
                    $row['DateFrom'],
                    $row['DateTo'],
                    $row['EmployeeID']
                );
                $stmtUpdateAttendance->execute();
            }

            $stmtUpdateAttendance->close();
            $stmtSelect->close();

            // Step 3: Update hrm_LabourCalculatedSalary with PaymentStatus, PaymentDate, PaymentRemark
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sqlUpdateSalary = "UPDATE hrm_LabourCalculatedSalary 
                                SET PaymentStatus = ?, PaymentDate = ?, PaymentRemark = ?, PaymentReferenceFilepath = ?, PaidAmount = NetSalary 
                                WHERE SalaryID IN ($placeholders)";
            $stmtUpdateSalary = $hrmconn->prepare($sqlUpdateSalary);

            $types = str_repeat('s', 4) . str_repeat('i', count($ids));
            $params = array_merge([$types, $PaymentStatus, $PaymentDate, $Remark, $FilePath], $ids);
            $stmtUpdateSalary->bind_param(...$params);

            if ($stmtUpdateSalary->execute()) {
                $hrmconn->commit();
                echo json_encode([
                    "status" => "success",
                    "message" => "Payment status updated and attendance records updated successfully."
                ]);
            } else {
                $hrmconn->rollback();
                echo json_encode([
                    "status" => "error",
                    "message" => "Failed to update payment status."
                ]);
            }

            $stmtUpdateSalary->close();
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "No SalaryIds provided."
            ]);
        }
    } catch (Exception $e) {
        $hrmconn->rollback();
        echo json_encode([
            "status" => "error",
            "message" => "Exception: " . $e->getMessage()
        ]);
    }
} else if ($action == "GetAttFileData") {
    $companyid = $_POST['companyid'] ?? null;
    $subcompanyid = $_POST['subcompanyid'] ?? null;
    $projectid = $_POST['projectid'] ?? null;
    $attfileid = $_POST['attfileid'] ?? '';
    $fileData = getAttendanceFiles($hrmconn, $projectid, "FileID", $attfileid);
    echo json_encode($fileData);
} else if ($action == "updatezerodaysatt") {
    $completed       = $_POST['completed'] ?? '';
    $subcompanyid    = $_POST['subcompanyid'] ?? '';
    $projectid       = $_POST['projectid'] ?? '';
    $loginid         = $_POST['loginid'] ?? '';
    $labourid        = $_POST['labourid'] ?? '';
    $dateFrom        = $_POST['dateFrom'] ?? '';
    $dateTo          = $_POST['dateTo'] ?? '';
    $locationid      = $_POST['locationid'] ?? '';
    $attendancedays  = $_POST['attendancedays'] ?? '';

    $response = ['status' => 'error', 'message' => 'Something went wrong'];

    // ✅ Validation
    if (!empty($labourid) && $attendancedays !== '' && is_numeric($attendancedays)) {

        $sql = "UPDATE hrm_labourcalculateattendance 
                SET PresentDays = ? 
                WHERE EmployeeID = ? 
                AND locationid = ? 
                AND DateFrom = ?
                AND DateTo = ?";

        if ($stmt = $hrmconn->prepare($sql)) {
            $stmt->bind_param("iiiss", 
                $attendancedays,  // int
                $labourid,        // int
                $locationid,      // int
                $dateFrom,        // string
                $dateTo           // string
            );

            if ($stmt->execute()) {

                if ($stmt->affected_rows > 0) {
                    $response = [
                        'status' => 'success',
                        'message' => 'Attendance updated successfully'
                    ];
                } else {
                    $response = [
                        'status' => 'warning',
                        'message' => 'No record updated (maybe same value or no match)'
                    ];
                }

            } else {
                $response['message'] = $stmt->error;
            }

            $stmt->close();

        } else {
            $response['message'] = $hrmconn->error;
        }

    } else {
        $response['message'] = 'Invalid input data';
    }

    echo json_encode($response);
} else if ($action == "labourworkingloactiondetails"){
    $companyid = $_POST['companyid'] ?? 'all';
    if ($companyid === 'undefined' || empty($companyid)) {
        $companyid = 'all';
    }
    $subcompanyid = $_POST['subcompanyid'] ?? 'all';
    if ($subcompanyid === 'undefined' || empty($subcompanyid)) {
        $subcompanyid = 'all';
    }
    $projectid = $_POST['projectid'] ?? 'all';
    if ($projectid === 'undefined' || empty($projectid)) {
        $projectid = 'all';
    }
    $shortby = $_POST['shortby'] ?? 'all';
    $shortbyvalue = $_POST['shortbyvalue'] ?? 'all';
    $locationlist = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, $shortby,$shortbyvalue);
    if (!is_array($locationlist)) {
        $locationlist = [];
    }
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json');
    echo json_encode($locationlist);
    exit;
} else if ($action == "GetPolyhousesByLocation") {
    $projectid  = $_POST['projectid']  ?? null;
    $locationid = $_POST['locationid'] ?? null;

    // krfconn connects to karjeefarm DB where krf_polyhouse_master lives
    $data = [];
    $sql  = "SELECT polyhouseid, polyhousename, physicallocation, locationid
             FROM krf_polyhouse_master
             WHERE projectid = ? AND status = 'Active'";
    $types  = "s";
    $params = [$projectid];

    if ($locationid && $locationid !== "all") {
        $sql   .= " AND locationid = ?";
        $types .= "s";
        $params[] = $locationid;
    }

    $sql .= " ORDER BY polyhousename";

    $stmt = $krfconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) { $data[] = $row; }
        $stmt->close();
    }
    echo json_encode($data);
} else if ($action == "SaveQRAttendance") {
    date_default_timezone_set('Asia/Kolkata');
    /*
     * ─── QR Attendance Save ───────────────────────────────────────────
     *  scannerType : "main-gate"  → writes *_mn columns
     *                anything else → writes *_dp columns
     *
     *  Logic:
     *   1. Resolve EmployeeID → labourid (int) via CAST
     *   2. Find today's attendance row for this labour
     *   3. No row                    → INSERT (check-in)
     *   4. Row exists, no check-in   → UPDATE checkin
     *   5. Row exists, check-in done, no checkout → UPDATE checkout + calc hours
     *   6. Both done                 → return "already_complete"
     * ─────────────────────────────────────────────────────────────────
     */
    $employeeID  = trim($_POST['employeeID']  ?? '');
    $projectid   = trim($_POST['projectid']   ?? '');
    $scannerType = trim($_POST['scannerType'] ?? '');   // "main-gate" | polyhouseid
    $scannerName = trim($_POST['scannerName'] ?? '');   // human-readable label
    $scannedBy   = trim($_POST['scannedBy']   ?? 'QR_SYSTEM');
    $locationId  = trim($_POST['locationId']  ?? '');
    $loginroleid = intval($_POST['loginroleid'] ?? $_SESSION['loginroleid'] ?? 99);
    $todayDate   = date('Y-m-d');
    $nowDT       = date('Y-m-d H:i:s');
    $source      = 'QR_SCANNER';

    // Determine suffix
    $isMN = (strtolower($scannerType) === 'main-gate');
    $sfx  = $isMN ? 'mn' : 'dp';

    if (empty($employeeID) || empty($projectid)) {
        echo json_encode(['status' => 'error', 'message' => 'मापदंड (Parameters) अनुपलब्ध हैं।']);
        exit;
    }

    // 1. Resolve labourid (varchar) and registered locationid
    $stmtLid = $hrmconn->prepare("SELECT EmployeeID AS lid, locationid FROM hrm_LabourMaster WHERE BINARY EmployeeID = ? AND projectid = ? LIMIT 1");
    $stmtLid->bind_param("ss", $employeeID, $projectid);
    $stmtLid->execute();
    $rowLid = $stmtLid->get_result()->fetch_assoc();
    $stmtLid->close();

    if (!$rowLid) {
        echo json_encode(['status' => 'error', 'message' => 'लेबर नहीं मिला।']);
        exit;
    }
    $labourid = $rowLid['lid'];
    $registeredLocationId = $rowLid['locationid'] ?? '';

    // Use registered location ID instead of scanned location ID
    $labourlocationid = !empty($registeredLocationId) ? $registeredLocationId : $locationId;

    // Check if the labour has already completed a session today with duration > 6 hours (excluding soft-deleted rows)
    $stmtCheck = $hrmconn->prepare(
        "SELECT workinghours_{$sfx} 
         FROM hrm_labourattendance 
         WHERE (tedit IS NULL OR tedit = '') AND labourid = ? AND projectid = ? AND attendancedate = ? AND checkouttime_{$sfx} IS NOT NULL"
    );
    $stmtCheck->bind_param("sss", $labourid, $projectid, $todayDate);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();
    $hasSessionOver6Hr = false;
    while ($cRow = $resCheck->fetch_assoc()) {
        $whStr = $cRow["workinghours_{$sfx}"];
        if (!empty($whStr)) {
            $p = explode(':', $whStr);
            $h = isset($p[0]) ? intval($p[0]) : 0;
            $m = isset($p[1]) ? intval($p[1]) : 0;
            $s = isset($p[2]) ? intval($p[2]) : 0;
            $duration = $h + ($m / 60.0) + ($s / 3600.0);
            if ($duration > 6.0) {
                $hasSessionOver6Hr = true;
                break;
            }
        }
    }
    $stmtCheck->close();

    $forceNewEntry = isset($_POST['forceNewEntry']) && $_POST['forceNewEntry'] === 'true';

    // 2. Fetch today's attendance row (order by DESC to get latest session if multiple exist)
    $row = null;
    if (!$forceNewEntry) {
        $stmtFetch = $hrmconn->prepare(
            "SELECT attendanceid, checkintime_{$sfx}, checkouttime_{$sfx}
             FROM hrm_labourattendance
             WHERE (tedit IS NULL OR tedit = '') AND  labourid = ? AND projectid = ? AND attendancedate = ?
             ORDER BY attendanceid DESC
             LIMIT 1"
        );
        $stmtFetch->bind_param("sss", $labourid, $projectid, $todayDate);
        $stmtFetch->execute();
        $row = $stmtFetch->get_result()->fetch_assoc();
        $stmtFetch->close();
    }

    $attendanceType = ''; // 'checkin' or 'checkout'

    if (!$row) {
        if ($hasSessionOver6Hr) {
            echo json_encode([
                'status' => 'error',
                'message' => 'उपस्थिति पहले ही दर्ज हो चुकी है। एक ही दिन में दोबारा उपस्थिति दर्ज नहीं की जा सकती।'
            ]);
            exit;
        }
        // Check if 1:00 PM (13:00) or later for loginroleid > 2
        if (intval(date('G')) >= 13 && $loginroleid > 2) {
            echo json_encode([
                'status' => 'error',
                'message' => 'दोपहर 1:00 बजे के बाद चेक-इन (Mark-in) करने की अनुमति नहीं है। केवल चेक-आउट (Mark-out) दर्ज किया जा सकता है।'
            ]);
            exit;
        }
        // ── 3. No row today (or forcing a new entry) → INSERT with check-in ──────────────────
        $attendanceType = 'checkin';
        $sql = "INSERT INTO hrm_labourattendance
                    (projectid, labourid, labourlocationid, attendancedate,
                     checkintime_{$sfx}, sourcein_{$sfx},
                     checkinscannerid_{$sfx}, checkinlocation_{$sfx},
                     checkinby_{$sfx}, status, updated_at, updated_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'MIS', ?, ?)";
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param(
            "sssssssssss",
            $projectid, $labourid, $labourlocationid, $todayDate,
            $nowDT, $source,
            $scannerName, $locationId,
            $scannedBy, $todayDate, $scannedBy
        );
        $ok = $stmt->execute();
        $stmt->close();

        if ($ok) {
            echo json_encode(['status' => 'success', 'type' => 'checkin', 'time' => $nowDT, 'message' => 'चेक-इन सफलतापूर्वक दर्ज हो गया है।']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DB error on check-in: ' . $hrmconn->error]);
        }

    } elseif (empty($row["checkintime_{$sfx}"])) {
        if ($hasSessionOver6Hr) {
            echo json_encode([
                'status' => 'error',
                'message' => 'अनुमति नहीं है: आप आज पहले ही 6 घंटे से अधिक की उपस्थिति दर्ज कर चुके हैं। उसी दिन दोबारा उपस्थिति दर्ज नहीं की जा सकती।'
            ]);
            exit;
        }
        // Check if 1:00 PM (13:00) or later for loginroleid > 2
        if (intval(date('G')) >= 13 && $loginroleid > 2) {
            echo json_encode([
                'status' => 'error',
                'message' => 'दोपहर 1:00 बजे के बाद चेक-इन (Mark-in) करने की अनुमति नहीं है। केवल चेक-आउट (Mark-out) दर्ज किया जा सकता है।'
            ]);
            exit;
        }
        // ── 4. Row exists but no check-in for this suffix ───────────
        $attendanceType = 'checkin';
        $sql = "UPDATE hrm_labourattendance SET
                    labourlocationid         = ?,
                    checkintime_{$sfx}       = ?,
                    sourcein_{$sfx}          = ?,
                    checkinscannerid_{$sfx}  = ?,
                    checkinlocation_{$sfx}   = ?,
                    checkinby_{$sfx}         = ?,
                    status = 'Present', updated_at = ?, updated_by = ?
                WHERE attendanceid = ?";
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param(
            "ssssssssi",
            $labourlocationid, $nowDT, $source, $scannerName, $locationId,
            $scannedBy, $todayDate, $scannedBy,
            $row['attendanceid']
        );
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode($ok
            ? ['status' => 'success', 'type' => 'checkin', 'time' => $nowDT, 'message' => 'चेक-इन सफलतापूर्वक दर्ज हो गया है।']
            : ['status' => 'error', 'message' => 'DB error: ' . $hrmconn->error]);
    } elseif (empty($row["checkouttime_{$sfx}"])) {
        // ── 5. Check-in done → record check-out + calc working hours ─
        $attendanceType = 'checkout';

        // Calculate working hours
        $timeIn  = strtotime($row["checkintime_{$sfx}"]);
        $timeOut = strtotime($nowDT);
        if (($timeOut - $timeIn) < 300) {
            echo json_encode([
                'status' => 'error',
                'message' => 'अनुमति नहीं है: आप पिछले स्कैन के 5 मिनट के भीतर दोबारा पंच-आउट नहीं कर सकते। कृपया 5 मिनट बाद प्रयास करें।'
            ]);
            exit;
        }

        $dtIn  = new DateTime($row["checkintime_{$sfx}"]);
        $dtOut = new DateTime($nowDT);
        $diff  = $dtIn->diff($dtOut);
        $wh    = sprintf('%02d:%02d:%02d', ($diff->days * 24) + $diff->h, $diff->i, $diff->s);

        // Check working hours:
        // Full day: >= 6 hr
        // Half day: >= 2 hr && < 6 hr
        // Below 2 hr: < 2 hr
        $totalHours = ($diff->days * 24) + $diff->h + ($diff->i / 60) + ($diff->s / 3600);
        $confirmEarlyCheckout = isset($_POST['confirmEarlyCheckout']) && $_POST['confirmEarlyCheckout'] === 'true';
        $confirmUnder8 = isset($_POST['confirmUnder8']) && $_POST['confirmUnder8'] === 'true';
        $confirmHalfDay = isset($_POST['confirmHalfDay']) && $_POST['confirmHalfDay'] === 'true';

        if ($totalHours >= 6.0) {
            $dayType = 'Full Day (पूरा दिन)';
            $dayTypeCode = 'FULL_DAY';
        } elseif ($totalHours >= 2.0) {
            $dayType = 'Half Day (आधा दिन)';
            $dayTypeCode = 'HALF_DAY';
        } else {
            $dayType = 'No Attendance (2 घंटे से कम - शून्य)';
            $dayTypeCode = 'NO_ATTENDANCE';
        }

        if ($totalHours < 2.0 && !$confirmEarlyCheckout) {
            echo json_encode([
                'status' => 'early_checkout_warning',
                'type' => 'checkout',
                'workinghours' => $wh,
                'total_hours_num' => round($totalHours, 2),
                'day_type' => $dayType,
                'day_type_code' => $dayTypeCode,
                'checkin_time' => $row["checkintime_{$sfx}"],
                'checkout_time' => $nowDT,
                'message' => 'कुल कार्य समय ' . $wh . ' है (2 घंटे से कम)। 2 घंटे से कम कार्य करने पर उपस्थिति मान्य नहीं होगी और भुगतान में शामिल नहीं होगी। क्या आप चेक-आउट जारी रखना चाहते हैं?'
            ]);
            exit;
        } elseif ($totalHours >= 2.0 && $totalHours < 6.0 && !$confirmUnder8 && !$confirmHalfDay && !$confirmEarlyCheckout) {
            echo json_encode([
                'status' => 'half_day_warning',
                'type' => 'checkout',
                'workinghours' => $wh,
                'total_hours_num' => round($totalHours, 2),
                'day_type' => $dayType,
                'day_type_code' => $dayTypeCode,
                'checkin_time' => $row["checkintime_{$sfx}"],
                'checkout_time' => $nowDT,
                'message' => 'कुल कार्य समय ' . $wh . ' है (6 घंटे से कम)। यह Half Day (आधा दिन: 2 से 6 घंटे) के रूप में दर्ज होगा। क्या आप चेक-आउट की पुष्टि करते हैं?'
            ]);
            exit;
        }

        $earlyCheckoutRemark = trim($_POST['earlyCheckoutRemark'] ?? '');
        if ($totalHours < 6.0 && empty($earlyCheckoutRemark)) {
            $earlyCheckoutRemark = ($totalHours >= 2.0) ? 'Half Day Checkout (< 6 hrs)' : 'Under 2 hrs Checkout';
        }

        $sql = "UPDATE hrm_labourattendance SET
                    checkouttime_{$sfx}      = ?,
                    sourceout_{$sfx}         = ?,
                    checkoutscannerid_{$sfx} = ?,
                    checkoutlocation_{$sfx}  = ?,
                    checkoutby_{$sfx}        = ?,
                    workinghours_{$sfx}      = ?,
                    remark_{$sfx}            = ?,
                    updated_at = ?, updated_by = ?
                WHERE attendanceid = ?";
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param(
            "sssssssssi",
            $nowDT, $source, $scannerName, $locationId,
            $scannedBy, $wh,
            $earlyCheckoutRemark,
            $todayDate, $scannedBy,
            $row['attendanceid']
        );
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode($ok
            ? [
                'status' => 'success',
                'type' => 'checkout',
                'time' => $nowDT,
                'checkin_time' => $row["checkintime_{$sfx}"],
                'workinghours' => $wh,
                'total_hours_num' => round($totalHours, 2),
                'day_type' => $dayType,
                'day_type_code' => $dayTypeCode,
                'message' => 'चेक-आउट सफलतापूर्वक दर्ज हो गया है। कुल कार्य समय: ' . $wh . ' (' . $dayType . ')'
              ]
            : ['status' => 'error', 'message' => 'DB error: ' . $hrmconn->error]);

    } else {
        // ── 6. Both check-in & checkout already done ─────────────────
        if ($hasSessionOver6Hr) {
            echo json_encode([
                'status' => 'error',
                'message' => 'अनुमति नहीं है: आप आज पहले ही 6 घंटे से अधिक की उपस्थिति दर्ज कर चुके हैं। उसी दिन दोबारा उपस्थिति दर्ज नहीं की जा सकती।'
            ]);
            exit;
        }
        $checkin  = $row["checkintime_{$sfx}"];
        $checkout = $row["checkouttime_{$sfx}"];
        echo json_encode([
            'status'   => 'already_complete',
            'type'     => 'already_complete',
            'checkin'  => $checkin,
            'checkout' => $checkout,
            'message'  => 'आज की उपस्थिति पहले से ही पूरी हो चुकी है।'
        ]);
    }
} else if ($action == "GetAttendanceViewData") {
    /*
     * ─── Labour Attendance View ─────────────────────────────────────
     *  Returns attendance records joined with labour master info,
     *  strictly filtered by selected location, QR location, date, and scanby.
     * ───────────────────────────────────────────────────────────────
     */
    $projectid    = $_POST['projectid']    ?? '';
    $companyid    = $_POST['companyid']    ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $locationid   = $_POST['locationid']   ?? 'all';
    $polyhouseid  = $_POST['polyhouseid']  ?? 'all';
    $attdate      = $_POST['attdate']      ?? date('Y-m-d');
    $scanby       = $_POST['scanby']       ?? 'all';

    if (empty($projectid) || empty($attdate)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    $sql = "SELECT 
                la.attendanceid,
                la.labourid AS EmployeeID,
                CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                lm.MobileNo,
                lm.locationid,
                IFNULL(lm.Gender, '') AS Gender,
                la.labourlocationid,
                la.attendancedate,
                la.checkintime_mn,
                la.checkouttime_mn,
                la.workinghours_mn,
                la.checkinlocation_mn,
                la.checkinscannerid_mn,
                la.checkoutlocation_mn,
                la.checkoutscannerid_mn,
                la.sourcein_mn,
                la.sourceout_mn,
                la.checkinby_mn,
                la.checkoutby_mn,
                la.remark_mn,
                la.checkintime_dp,
                la.checkouttime_dp,
                la.workinghours_dp,
                la.checkinlocation_dp,
                la.checkinscannerid_dp,
                la.checkoutlocation_dp,
                la.checkoutscannerid_dp,
                la.sourcein_dp,
                la.sourceout_dp,
                la.checkinby_dp,
                la.checkoutby_dp,
                la.status,
                la.leavetype,
                la.overtimehours,
                la.approvalstatus
            FROM hrm_labourattendance la
            INNER JOIN hrm_LabourMaster lm ON la.labourid = lm.EmployeeID AND la.projectid = lm.projectid
            WHERE la.projectid = ? 
              AND la.attendancedate = ?
              AND (la.tedit IS NULL OR la.tedit = '')";

    $types = "ss";
    $params = [$projectid, $attdate];

    // Filter by selected locationid matched with la.labourlocationid OR checkinlocation
    if ($locationid && $locationid !== 'all') {
        $sql .= " AND (la.labourlocationid = ? OR la.checkinlocation_mn = ? OR la.checkinlocation_dp = ?)";
        $types .= "sss";
        $params[] = $locationid;
        $params[] = $locationid;
        $params[] = $locationid;
    }

    // Filter by selected QR location matched with la.checkinlocation_mn
    if ($polyhouseid && $polyhouseid !== 'all') {
        $sql .= " AND la.checkinlocation_mn = ?";
        $types .= "s";
        $params[] = $polyhouseid;
    }

    // Filter by selected scanby matched with la.checkinby_mn
    if ($scanby && $scanby !== 'all') {
        $sql .= " AND la.checkinby_mn = ?";
        $types .= "s";
        $params[] = $scanby;
    }

    $sql .= " ORDER BY lm.FirstName, lm.LastName";

    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $hrmconn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $result = [];
    while ($row = $res->fetch_assoc()) {
        $row['FullName'] = trim(preg_replace('/\s+/', ' ', $row['FullName']));
        if (empty($row['status'])) {
            $row['status'] = 'Absent';
        }
        $result[] = $row;
    }
    $stmt->close();

    // Sort $result by check-in time descending (latest check-ins first). Non-check-ins are sorted alphabetically.
    usort($result, function($a, $b) {
        $a_ci_mn = $a['checkintime_mn'] ?? '';
        $a_ci_dp = $a['checkintime_dp'] ?? '';
        $a_ci = $a_ci_mn > $a_ci_dp ? $a_ci_mn : $a_ci_dp;
        
        $b_ci_mn = $b['checkintime_mn'] ?? '';
        $b_ci_dp = $b['checkintime_dp'] ?? '';
        $b_ci = $b_ci_mn > $b_ci_dp ? $b_ci_mn : $b_ci_dp;
        
        if (empty($a_ci) && empty($b_ci)) {
            return strcmp($a['FullName'], $b['FullName']);
        }
        if (empty($a_ci)) return 1;
        if (empty($b_ci)) return -1;
        
        return strcmp($b_ci, $a_ci); // Descending order
    });

    echo json_encode($result);
} else if ($action == "GetOTAttendanceViewData") {
    $projectid    = $_POST['projectid']    ?? '';
    $companyid    = $_POST['companyid']    ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $locationid   = $_POST['locationid']   ?? 'all';
    $polyhouseid  = $_POST['polyhouseid']  ?? 'all';
    $attdate      = $_POST['attdate']      ?? '';
    $datefrom     = $_POST['datefrom']     ?? '';
    $dateto       = $_POST['dateto']       ?? '';
    $scanby       = $_POST['scanby']       ?? 'all';

    if (empty($datefrom) && !empty($attdate)) {
        $datefrom = $attdate;
    }
    if (empty($dateto) && !empty($attdate)) {
        $dateto = $attdate;
    }
    if (empty($datefrom)) {
        $datefrom = date('Y-m-d');
    }
    if (empty($dateto)) {
        $dateto = date('Y-m-d');
    }

    if (empty($projectid)) {
        echo json_encode(['status' => 'error', 'message' => 'Project ID is required.']);
        exit;
    }

    $chkTable = @$hrmconn->query("SHOW TABLES LIKE 'hrm_labourattendance_forot'");
    if (!$chkTable || $chkTable->num_rows == 0) {
        @$hrmconn->query("CREATE TABLE `hrm_labourattendance_forot` LIKE `hrm_labourattendance`");
    }
    $chkCol = @$hrmconn->query("SHOW COLUMNS FROM `hrm_labourattendance_forot` LIKE 'purposeofot'");
    if ($chkCol && $chkCol->num_rows == 0) {
        @$hrmconn->query("ALTER TABLE `hrm_labourattendance_forot` ADD COLUMN `purposeofot` TEXT NULL AFTER `remark_mn`");
    }

    $sql = "SELECT 
                la.attendanceid,
                la.labourid AS EmployeeID,
                CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                IFNULL(lm.Gender, '') AS Gender,
                lm.MobileNo,
                lm.locationid,
                la.labourlocationid,
                la.attendancedate,
                la.checkintime_mn,
                la.checkouttime_mn,
                la.workinghours_mn,
                la.checkinlocation_mn,
                la.checkinscannerid_mn,
                la.checkoutlocation_mn,
                la.checkoutscannerid_mn,
                la.sourcein_mn,
                la.sourceout_mn,
                la.checkinby_mn,
                la.checkoutby_mn,
                la.remark_mn,
                la.purposeofot,
                la.checkintime_dp,
                la.checkouttime_dp,
                la.workinghours_dp,
                la.checkinlocation_dp,
                la.checkinscannerid_dp,
                la.checkoutlocation_dp,
                la.checkoutscannerid_dp,
                la.sourcein_dp,
                la.sourceout_dp,
                la.checkinby_dp,
                la.checkoutby_dp,
                la.status,
                la.leavetype,
                la.overtimehours,
                la.approvalstatus
            FROM hrm_labourattendance_forot la
            INNER JOIN hrm_LabourMaster lm ON la.labourid = lm.EmployeeID AND la.projectid = lm.projectid
            WHERE la.projectid = ? 
              AND la.attendancedate BETWEEN ? AND ?
              AND (la.tedit IS NULL OR la.tedit = '')";

    $types = "sss";
    $params = [$projectid, $datefrom, $dateto];

    if ($locationid && $locationid !== 'all') {
        $sql .= " AND (la.labourlocationid = ? OR la.checkinlocation_mn = ? OR la.checkinlocation_dp = ?)";
        $types .= "sss";
        $params[] = $locationid;
        $params[] = $locationid;
        $params[] = $locationid;
    }

    if ($polyhouseid && $polyhouseid !== 'all') {
        $sql .= " AND la.checkinlocation_mn = ?";
        $types .= "s";
        $params[] = $polyhouseid;
    }

    if ($scanby && $scanby !== 'all') {
        $sql .= " AND la.checkinby_mn = ?";
        $types .= "s";
        $params[] = $scanby;
    }

    $sql .= " ORDER BY lm.FirstName, lm.LastName";

    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $hrmconn->error]);
        exit;
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $locMap = [];
    $resLoc = $hrmconn->query("SELECT LocationId, LocationName, sno FROM hrm_AttendanceLocationMaster");
    if ($resLoc) {
        while ($lRow = $resLoc->fetch_assoc()) {
            if (!empty($lRow['LocationId'])) $locMap[$lRow['LocationId']] = $lRow['LocationName'];
            if (!empty($lRow['sno'])) $locMap[$lRow['sno']] = $lRow['LocationName'];
        }
    }

    $polyMap = [];
    $resPoly = $hrmconn->query("SELECT polyhouseid, polyhousename FROM hrm_polyhouse");
    if ($resPoly) {
        while ($pRow = $resPoly->fetch_assoc()) {
            if (!empty($pRow['polyhouseid'])) $polyMap[$pRow['polyhouseid']] = $pRow['polyhousename'];
        }
    }

    $result = [];
    while ($row = $res->fetch_assoc()) {
        $row['FullName'] = trim(preg_replace('/\s+/', ' ', $row['FullName']));
        if (empty($row['status'])) {
            $row['status'] = 'Absent';
        }

        $homeLocId = $row['labourlocationid'] ?: ($row['locationid'] ?? '');
        $row['HomeLocationName'] = $locMap[$homeLocId] ?? ($homeLocId ?: '-');

        $scanLocId = $row['checkinlocation_mn'] ?: ($row['checkinlocation_dp'] ?? '');
        if ($scanLocId === 'main-gate') {
            $row['ScanLocationName'] = 'Main Gate';
        } else if (!empty($scanLocId)) {
            $row['ScanLocationName'] = $polyMap[$scanLocId] ?? ($locMap[$scanLocId] ?? $scanLocId);
        } else {
            $row['ScanLocationName'] = '-';
        }

        $result[] = $row;
    }
    $stmt->close();

    usort($result, function($a, $b) {
        $a_ci_mn = $a['checkintime_mn'] ?? '';
        $a_ci_dp = $a['checkintime_dp'] ?? '';
        $a_ci = $a_ci_mn > $a_ci_dp ? $a_ci_mn : $a_ci_dp;
        
        $b_ci_mn = $b['checkintime_mn'] ?? '';
        $b_ci_dp = $b['checkintime_dp'] ?? '';
        $b_ci = $b_ci_mn > $b_ci_dp ? $b_ci_mn : $b_ci_dp;

        if (empty($a_ci) && empty($b_ci)) {
            return strcmp($a['FullName'], $b['FullName']);
        }
        if (empty($a_ci)) return 1;
        if (empty($b_ci)) return -1;
        
        return strcmp($b_ci, $a_ci);
    });

    echo json_encode($result);
    exit;
} else if ($action == "UpdateOTLabourCheckout") {
    @ob_clean();
    header('Content-Type: application/json');
    $attendanceid = isset($_POST['attendanceid']) ? intval($_POST['attendanceid']) : 0;
    $checkoutTime = isset($_POST['checkouttime']) ? trim($_POST['checkouttime']) : '';
    $remark       = isset($_POST['remark']) ? trim($_POST['remark']) : '';
    $loginname    = isset($_POST['loginname']) ? trim($_POST['loginname']) : 'Admin';

    if ($attendanceid <= 0 || empty($checkoutTime) || empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Attendance ID, check-out time, and remark are required.']);
        exit;
    }

    $stmtFetch = $hrmconn->prepare("SELECT attendancedate, checkintime_mn, checkintime_dp FROM hrm_labourattendance_forot WHERE attendanceid = ? LIMIT 1");
    if (!$stmtFetch) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $hrmconn->error]);
        exit;
    }
    $stmtFetch->bind_param("i", $attendanceid);
    $stmtFetch->execute();
    $attRow = $stmtFetch->get_result()->fetch_assoc();
    $stmtFetch->close();

    if (!$attRow) {
        echo json_encode(['status' => 'error', 'message' => 'OT Attendance record not found.']);
        exit;
    }

    $attDate = $attRow['attendancedate'];
    $checkoutDT = $attDate . ' ' . $checkoutTime . ':00';

    $wh_mn = null;
    if (!empty($attRow['checkintime_mn']) && $attRow['checkintime_mn'] !== '0000-00-00 00:00:00') {
        $tIn  = strtotime($attRow['checkintime_mn']);
        $tOut = strtotime($checkoutDT);
        if ($tOut > $tIn) {
            $diffSec = $tOut - $tIn;
            $h = floor($diffSec / 3600);
            $m = floor(($diffSec % 3600) / 60);
            $s = $diffSec % 60;
            $wh_mn = sprintf('%02d:%02d:%02d', $h, $m, $s);
        } else {
            $wh_mn = '00:00:00';
        }
    }

    $wh_dp = null;
    $checkoutDT_dp = null;
    if (!empty($attRow['checkintime_dp']) && $attRow['checkintime_dp'] !== '0000-00-00 00:00:00') {
        $tIn  = strtotime($attRow['checkintime_dp']);
        $tOut = strtotime($checkoutDT);
        if ($tOut > $tIn) {
            $diffSec = $tOut - $tIn;
            $h = floor($diffSec / 3600);
            $m = floor(($diffSec % 3600) / 60);
            $s = $diffSec % 60;
            $wh_dp = sprintf('%02d:%02d:%02d', $h, $m, $s);
            $checkoutDT_dp = $checkoutDT;
        } else {
            $wh_dp = '00:00:00';
        }
    }

    $sqlUpdate = "UPDATE hrm_labourattendance_forot SET
        checkouttime_mn      = ?,
        workinghours_mn     = ?,
        checkoutscannerid_mn = 'MANUAL',
        checkoutlocation_mn  = checkinlocation_mn,
        sourceout_mn         = 'web_manual',
        checkoutby_mn        = ?,
        remark_mn            = ?,
        checkouttime_dp      = IF(checkintime_dp IS NOT NULL AND checkintime_dp != '0000-00-00 00:00:00', ?, checkouttime_dp),
        workinghours_dp     = IF(checkintime_dp IS NOT NULL AND checkintime_dp != '0000-00-00 00:00:00', ?, workinghours_dp),
        checkoutscannerid_dp = IF(checkintime_dp IS NOT NULL AND checkintime_dp != '0000-00-00 00:00:00', 'MANUAL', checkoutscannerid_dp),
        checkoutlocation_dp  = IF(checkintime_dp IS NOT NULL AND checkintime_dp != '0000-00-00 00:00:00', checkinlocation_dp, checkoutlocation_dp),
        sourceout_dp         = IF(checkintime_dp IS NOT NULL AND checkintime_dp != '0000-00-00 00:00:00', 'web_manual', sourceout_dp),
        checkoutby_dp        = IF(checkintime_dp IS NOT NULL AND checkintime_dp != '0000-00-00 00:00:00', ?, checkoutby_dp),
        updated_at           = NOW(),
        updated_by           = ?
    WHERE attendanceid = ?";

    $stmtUp = $hrmconn->prepare($sqlUpdate);
    if (!$stmtUp) {
        echo json_encode(['status' => 'error', 'message' => 'Update prepare failed: ' . $hrmconn->error]);
        exit;
    }

    $stmtUp->bind_param(
        "ssssssssssssssi",
        $checkoutDT,
        $wh_mn,
        $loginname,
        $remark,
        $checkoutDT_dp, $checkoutDT_dp,
        $wh_dp, $wh_dp,
        $loginname, $loginname,
        $checkoutDT_dp,
        $checkoutDT_dp,
        $checkoutDT_dp,
        $loginname,
        $attendanceid
    );

    if ($stmtUp->execute()) {
        echo json_encode(['status' => 'Success', 'message' => 'OT Check-out updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $stmtUp->error]);
    }
    $stmtUp->close();
    exit;
} else if ($action == "RemoveOTAttendance") {
    $attendanceid = $_POST['attendanceid'] ?? '';
    $loginname    = $_POST['loginname']    ?? 'System';
    $localdatetime = $_POST['localdatetime'] ?? date('Y-m-d H:i:s');

    if (empty($attendanceid)) {
        echo json_encode(['status' => 'Error', 'message' => 'Missing attendance ID.']);
        exit;
    }

    $teditremark = "removed on date " . $localdatetime . " by " . $loginname;
    
    $stmt = $hrmconn->prepare("UPDATE hrm_labourattendance_forot SET tedit = ? WHERE attendanceid = ?");
    if ($stmt) {
        $stmt->bind_param("si", $teditremark, $attendanceid);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'Success', 'message' => 'OT Attendance removed successfully.']);
        } else {
            echo json_encode(['status' => 'Error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'Error', 'message' => $hrmconn->error]);
    }
    exit;
} else if ($action == "MarkManualOTAttendance") {
    $employeeID     = $_POST['employeeID'] ?? '';
    $projectid      = $_POST['projectid'] ?? '';
    $companyid      = $_POST['companyid'] ?? '';
    $subcompanyid   = $_POST['subcompanyid'] ?? '';
    $attendancedate = $_POST['attendancedate'] ?? date('Y-m-d');
    $checkin        = $_POST['checkin'] ?? '';
    $checkout       = $_POST['checkout'] ?? '';
    $locationId     = $_POST['locationId'] ?? '';
    $locationName   = $_POST['locationName'] ?? '';
    $purposeofot    = $_POST['purposeofot'] ?? 'General Overtime';
    $loginname      = $_POST['loginname'] ?? 'Admin';

    if (empty($employeeID) || empty($attendancedate) || empty($checkin)) {
        echo json_encode(['status' => 'error', 'message' => 'Labour, date and check-in time are required.']);
        exit;
    }

    $checkinDT  = $attendancedate . ' ' . $checkin . ':00';
    $checkoutDT = !empty($checkout) ? ($attendancedate . ' ' . $checkout . ':00') : null;
    
    $wh = null;
    if ($checkoutDT) {
        $tIn  = strtotime($checkinDT);
        $tOut = strtotime($checkoutDT);
        if ($tOut > $tIn) {
            $diffSec = $tOut - $tIn;
            $h = floor($diffSec / 3600);
            $m = floor(($diffSec % 3600) / 60);
            $s = $diffSec % 60;
            $wh = sprintf('%02d:%02d:%02d', $h, $m, $s);
        }
    }

    $stmtCh = $hrmconn->prepare("SELECT attendanceid FROM hrm_labourattendance_forot WHERE labourid = ? AND attendancedate = ? AND projectid = ? AND (tedit IS NULL OR tedit = '') LIMIT 1");
    $stmtCh->bind_param("sss", $employeeID, $attendancedate, $projectid);
    $stmtCh->execute();
    $existing = $stmtCh->get_result()->fetch_assoc();
    $stmtCh->close();

    if ($existing) {
        $attId = $existing['attendanceid'];
        $stmtUp = $hrmconn->prepare("UPDATE hrm_labourattendance_forot SET checkintime_mn = ?, checkouttime_mn = ?, workinghours_mn = ?, checkinlocation_mn = ?, checkinby_mn = ?, remark_mn = ?, purposeofot = ?, ismanual_mn = 1, updated_at = NOW() WHERE attendanceid = ?");
        $stmtUp->bind_param("sssssssi", $checkinDT, $checkoutDT, $wh, $locationId, $loginname, $purposeofot, $purposeofot, $attId);
        $stmtUp->execute();
        $stmtUp->close();
    } else {
        $stmtIns = $hrmconn->prepare("INSERT INTO hrm_labourattendance_forot (companyid, subcompanyid, projectid, labourid, labourlocationid, attendancedate, checkintime_mn, checkouttime_mn, workinghours_mn, checkinlocation_mn, checkinscannerid_mn, sourcein_mn, checkinby_mn, remark_mn, purposeofot, ismanual_mn, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'MANUAL', 'web_manual', ?, ?, ?, 1, 'Present', NOW())");
        $stmtIns->bind_param("ssssssssssss", $companyid, $subcompanyid, $projectid, $employeeID, $locationId, $attendancedate, $checkinDT, $checkoutDT, $wh, $locationId, $loginname, $purposeofot, $purposeofot);
        $stmtIns->execute();
        $stmtIns->close();
    }

    echo json_encode(['status' => 'Success', 'message' => 'OT Attendance recorded successfully.']);
    exit;
} else if ($action == "UpdateLabourCheckout") {
    @ob_clean();
    header('Content-Type: application/json');
    $attendanceid = isset($_POST['attendanceid']) ? intval($_POST['attendanceid']) : 0;
    $checkoutTime = isset($_POST['checkouttime']) ? trim($_POST['checkouttime']) : '';
    $remark       = isset($_POST['remark']) ? trim($_POST['remark']) : '';
    $loginname    = isset($_POST['loginname']) ? trim($_POST['loginname']) : 'Admin';

    if ($attendanceid <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Attendance ID is required.']);
        exit;
    }
    if (empty($checkoutTime)) {
        echo json_encode(['status' => 'error', 'message' => 'Check-out time is required.']);
        exit;
    }
    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Remark is required.']);
        exit;
    }

    // Ensure column `remark_mn` exists in `hrm_labourattendance`
    $chkCol = $hrmconn->query("SHOW COLUMNS FROM hrm_labourattendance LIKE 'remark_mn'");
    if ($chkCol && $chkCol->num_rows == 0) {
        @$hrmconn->query("ALTER TABLE hrm_labourattendance ADD COLUMN remark_mn VARCHAR(255) NULL AFTER checkoutby_mn");
    }

    // Fetch existing attendance record
    $stmtFetch = $hrmconn->prepare("SELECT attendancedate, checkintime_mn, checkintime_dp, checkouttime_mn, checkouttime_dp FROM hrm_labourattendance WHERE attendanceid = ? LIMIT 1");
    if (!$stmtFetch) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $hrmconn->error]);
        exit;
    }
    $stmtFetch->bind_param("i", $attendanceid);
    $stmtFetch->execute();
    $attRow = $stmtFetch->get_result()->fetch_assoc();
    $stmtFetch->close();

    if (!$attRow) {
        echo json_encode(['status' => 'error', 'message' => 'Attendance record not found.']);
        exit;
    }

    $attDate = $attRow['attendancedate'];
    $checkoutDT = $attDate . ' ' . $checkoutTime . ':00'; // YYYY-MM-DD HH:MM:00

    // Calculate workinghours_mn
    $wh_mn = null;
    if (!empty($attRow['checkintime_mn']) && $attRow['checkintime_mn'] !== '0000-00-00 00:00:00') {
        $tIn  = strtotime($attRow['checkintime_mn']);
        $tOut = strtotime($checkoutDT);
        if ($tOut > $tIn) {
            $diffSec = $tOut - $tIn;
            $h = floor($diffSec / 3600);
            $m = floor(($diffSec % 3600) / 60);
            $s = $diffSec % 60;
            $wh_mn = sprintf('%02d:%02d:%02d', $h, $m, $s);
        } else {
            $wh_mn = '00:00:00';
        }
    }

    // Calculate workinghours_dp if checkintime_dp exists
    $wh_dp = null;
    $checkoutDT_dp = null;
    if (!empty($attRow['checkintime_dp']) && $attRow['checkintime_dp'] !== '0000-00-00 00:00:00') {
        $checkoutDT_dp = $checkoutDT;
        $tInDP  = strtotime($attRow['checkintime_dp']);
        $tOutDP = strtotime($checkoutDT);
        if ($tOutDP > $tInDP) {
            $diffSec = $tOutDP - $tInDP;
            $h = floor($diffSec / 3600);
            $m = floor(($diffSec % 3600) / 60);
            $s = $diffSec % 60;
            $wh_dp = sprintf('%02d:%02d:%02d', $h, $m, $s);
        } else {
            $wh_dp = '00:00:00';
        }
    }

    $sqlUpdate = "UPDATE hrm_labourattendance SET
        checkouttime_mn      = ?,
        workinghours_mn      = IFNULL(?, workinghours_mn),
        checkoutby_mn        = ?,
        remark_mn            = ?,
        sourceout_mn         = IF(checkintime_mn IS NOT NULL AND checkintime_mn != '0000-00-00 00:00:00', IFNULL(sourcein_mn, 'Manual'), sourceout_mn),
        checkoutlocation_mn  = IF(checkintime_mn IS NOT NULL AND checkintime_mn != '0000-00-00 00:00:00', checkinlocation_mn, checkoutlocation_mn),
        checkoutscannerid_mn = IF(checkintime_mn IS NOT NULL AND checkintime_mn != '0000-00-00 00:00:00', 'Manual', checkoutscannerid_mn),
        checkouttime_dp      = IF(? IS NOT NULL, ?, checkouttime_dp),
        workinghours_dp      = IF(? IS NOT NULL, ?, workinghours_dp),
        checkoutby_dp        = IF(? IS NOT NULL, ?, checkoutby_dp),
        sourceout_dp         = IF(? IS NOT NULL, IFNULL(sourcein_dp, 'Manual'), sourceout_dp),
        checkoutlocation_dp  = IF(? IS NOT NULL, checkinlocation_dp, checkoutlocation_dp),
        checkoutscannerid_dp = IF(? IS NOT NULL, 'Manual', checkoutscannerid_dp),
        status               = 'Present',
        updated_at           = NOW(),
        updated_by           = ?
    WHERE attendanceid = ?";

    $stmtUp = $hrmconn->prepare($sqlUpdate);
    if (!$stmtUp) {
        echo json_encode(['status' => 'error', 'message' => 'Update prepare failed: ' . $hrmconn->error]);
        exit;
    }

    $stmtUp->bind_param(
        "ssssssssssssssi",
        $checkoutDT,
        $wh_mn,
        $loginname,
        $remark,
        $checkoutDT_dp, $checkoutDT_dp,
        $wh_dp, $wh_dp,
        $loginname, $loginname,
        $checkoutDT_dp,
        $checkoutDT_dp,
        $checkoutDT_dp,
        $loginname,
        $attendanceid
    );

    if ($stmtUp->execute()) {
        echo json_encode(['status' => 'Success', 'message' => 'Check-out updated successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database update failed: ' . $stmtUp->error]);
    }
    $stmtUp->close();
    exit;
} else if ($action == "CalculateWagesAttendance") {
    $projectid    = $_POST['projectid']    ?? '';
    $companyid    = $_POST['companyid']    ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $locationid   = $_POST['locationid']   ?? 'all';
    $dateFrom     = $_POST['dateFrom']     ?? '';
    $dateTo       = $_POST['dateTo']       ?? '';

    if (empty($projectid) || empty($dateFrom) || empty($dateTo)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    // Fetch active labours who have attendance marked in date range
    $lmWhere  = "lm.projectid = ? AND lm.Status = 'Active' AND lm.EmployeeID IN (
        SELECT DISTINCT labourid FROM hrm_labourattendance WHERE projectid = ? AND attendancedate BETWEEN ? AND ? AND (tedit IS NULL OR tedit = '')
    )";
    $lmTypes  = "ssss";
    $lmParams = [$projectid, $projectid, $dateFrom, $dateTo];

    if ($locationid && $locationid !== 'all') {
        $lmWhere  .= " AND lm.locationid = ?";
        $lmTypes  .= "s";
        $lmParams[] = $locationid;
    }

    $sqlLM = "SELECT
                lm.EmployeeID,
                CAST(lm.EmployeeID AS UNSIGNED) AS labourid_int,
                CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                lm.locationid
              FROM hrm_LabourMaster lm
              WHERE {$lmWhere}
              ORDER BY lm.FirstName, lm.LastName";

    $stmtLM = $hrmconn->prepare($sqlLM);
    if (!$stmtLM) {
        echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $hrmconn->error]);
        exit;
    }
    $stmtLM->bind_param($lmTypes, ...$lmParams);
    $stmtLM->execute();
    $resLM = $stmtLM->get_result();

    $labours = [];
    while ($row = $resLM->fetch_assoc()) {
        $row['FullName'] = trim(preg_replace('/\s+/', ' ', $row['FullName']));
        $labours[] = $row;
    }
    $stmtLM->close();

    if (empty($labours)) {
        echo json_encode([]);
        exit;
    }

    // Batch lookup bank details for all fetched labours
    $lookupIds = [];
    $idToEmpMap = [];
    foreach ($labours as $l) {
        $eid = trim($l['EmployeeID']);
        $lookupIds[] = "'" . $hrmconn->real_escape_string($eid) . "'";
        $idToEmpMap[$eid] = $eid;
        if (!empty($l['labourid_int'])) {
            $lookupIds[] = "'" . $hrmconn->real_escape_string($l['labourid_int']) . "'";
            $idToEmpMap[$l['labourid_int']] = $eid;
        }
    }

    $bankDetailsMap = [];
    if (!empty($lookupIds)) {
        $inClause = implode(",", array_unique($lookupIds));

        // 1. Check hrm_labourbankdetails
        $resBank = $hrmconn->query("SELECT id, labourid, bankname, accountnumber, ifsccode, verifystatus, verifyremark, cancelchequepath FROM hrm_labourbankdetails 
                                    WHERE labourid IN ($inClause) AND (tedit IS NULL OR tedit='') 
                                      AND TRIM(IFNULL(accountnumber, '')) != ''
                                      AND TRIM(IFNULL(ifsccode, '')) != ''
                                    ORDER BY id DESC");
        if ($resBank) {
            while ($b = $resBank->fetch_assoc()) {
                $mappedEid = $idToEmpMap[$b['labourid']] ?? $b['labourid'];
                if (!isset($bankDetailsMap[$mappedEid])) {
                    $bankDetailsMap[$mappedEid] = [
                        'BankId'           => $b['id'] ?? '',
                        'BankName'         => trim($b['bankname'] ?? ''),
                        'AccountNumber'    => trim($b['accountnumber'] ?? ''),
                        'IFSCCode'         => trim($b['ifsccode'] ?? ''),
                        'VerifyStatus'     => trim($b['verifystatus'] ?? 'Pending'),
                        'VerifyRemark'     => trim($b['verifyremark'] ?? ''),
                        'CancelChequePath' => trim($b['cancelchequepath'] ?? '')
                    ];
                }
            }
        }
        
    }

    // Fast batch fetch of all attendance records in range
    $sqlAtt = "SELECT labourid, checkintime_mn, checkouttime_mn, workinghours_mn,
                      checkintime_dp, checkouttime_dp, workinghours_dp
               FROM hrm_labourattendance
               WHERE projectid = ? AND attendancedate BETWEEN ? AND ?
                 AND (tedit IS NULL OR tedit = '')";
    $stmtAtt = $hrmconn->prepare($sqlAtt);
    $attByLabour = [];
    if ($stmtAtt) {
        $stmtAtt->bind_param("sss", $projectid, $dateFrom, $dateTo);
        $stmtAtt->execute();
        $attRes = $stmtAtt->get_result();
        while ($ar = $attRes->fetch_assoc()) {
            $lKey = trim($ar['labourid']);
            if (!isset($attByLabour[$lKey])) {
                $attByLabour[$lKey] = [];
            }
            $attByLabour[$lKey][] = $ar;
        }
        $stmtAtt->close();
    }

    $result = [];
    foreach ($labours as $l) {
        $lid = $l['EmployeeID'];
        $lInt = (string)($l['labourid_int'] ?? '');

        $records = $attByLabour[$lid] ?? ($lInt && isset($attByLabour[$lInt]) ? $attByLabour[$lInt] : []);

        $checkins = 0;
        $fullPresentDays = 0.0;
        $halfPresentDays = 0.0;
        $missingCheckouts = 0;

        foreach ($records as $dayRow) {
            $isMnCheckin = (!empty($dayRow['checkintime_mn']) && $dayRow['checkintime_mn'] !== '0000-00-00 00:00:00');
            $isDpCheckin = (!empty($dayRow['checkintime_dp']) && $dayRow['checkintime_dp'] !== '0000-00-00 00:00:00');

            if ($isMnCheckin || $isDpCheckin) {
                $checkins++;
            }

            $isMnCheckoutMissing = $isMnCheckin && (empty($dayRow['checkouttime_mn']) || $dayRow['checkouttime_mn'] === '0000-00-00 00:00:00');
            $isDpCheckoutMissing = $isDpCheckin && (empty($dayRow['checkouttime_dp']) || $dayRow['checkouttime_dp'] === '0000-00-00 00:00:00');

            if ($isMnCheckoutMissing || $isDpCheckoutMissing) {
                $missingCheckouts++;
            }

            $hrs = 0.0;
            if (!empty($dayRow['checkouttime_mn']) && !empty($dayRow['workinghours_mn'])) {
                $p = explode(':', $dayRow['workinghours_mn']);
                $hrs = max($hrs, (int)($p[0] ?? 0) + ((int)($p[1] ?? 0) / 60));
            }
            if (!empty($dayRow['checkouttime_dp']) && !empty($dayRow['workinghours_dp'])) {
                $p = explode(':', $dayRow['workinghours_dp']);
                $hrs = max($hrs, (int)($p[0] ?? 0) + ((int)($p[1] ?? 0) / 60));
            }

            if ($hrs > 2 && $hrs < 6) {
                $halfPresentDays += 1.0;
            } else if ($hrs >= 6) {
                $fullPresentDays += 1.0;
            }
        }

        $presentDays = $fullPresentDays + ($halfPresentDays * 0.5);
        $bankInfo = $bankDetailsMap[$lid] ?? (!empty($l['labourid_int']) ? ($bankDetailsMap[$l['labourid_int']] ?? null) : null);
        $hasBankDetails   = !empty($bankInfo) && !empty($bankInfo['AccountNumber']) && !empty($bankInfo['IFSCCode']);
        $bankId           = $hasBankDetails ? ($bankInfo['BankId'] ?? '') : '';
        $bankName         = $hasBankDetails ? ($bankInfo['BankName'] ?? '') : '';
        $accountNumber    = $hasBankDetails ? ($bankInfo['AccountNumber'] ?? '') : '';
        $ifscCode         = $hasBankDetails ? ($bankInfo['IFSCCode'] ?? '') : '';
        $verifyStatus     = $hasBankDetails ? ($bankInfo['VerifyStatus'] ?? 'Pending') : '';
        $verifyRemark     = $hasBankDetails ? ($bankInfo['VerifyRemark'] ?? '') : '';
        $cancelChequePath = $hasBankDetails ? ($bankInfo['CancelChequePath'] ?? '') : '';
        $remark           = '';

        $result[] = [
            'labourid'          => $lid,
            'EmployeeID'        => $l['EmployeeID'],
            'FullName'          => $l['FullName'],
            'TotalCheckins'     => $checkins,
            'PresentDays'       => $fullPresentDays,
            'HalfPresentDays'   => $halfPresentDays,
            'PayableDays'       => $presentDays,
            'MissingCheckouts'  => $missingCheckouts,
            'HasBankDetails'    => $hasBankDetails,
            'BankId'            => $bankId,
            'BankName'          => $bankName,
            'AccountNumber'     => $accountNumber,
            'IFSCCode'          => $ifscCode,
            'VerifyStatus'      => $verifyStatus,
            'VerifyRemark'      => $verifyRemark,
            'CancelChequePath'  => $cancelChequePath,
            'Remark'            => $remark
        ];
    }

    echo json_encode($result);
    exit;
} else if ($action == "GetMissingBankDetails") {
    $projectid    = $_POST['projectid']    ?? $_GET['projectid']    ?? '';
    $companyid    = $_POST['companyid']    ?? $_GET['companyid']    ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? $_GET['subcompanyid'] ?? '';
    $locationid   = $_POST['locationid']   ?? $_GET['locationid']   ?? 'all';
    $dateFrom     = $_POST['dateFrom']     ?? $_GET['dateFrom']     ?? '';
    $dateTo       = $_POST['dateTo']       ?? $_GET['dateTo']       ?? '';

    $lmWhere = "lm.Status = 'Active'";
    $lmTypes = "";
    $lmParams = [];

    if (!empty($projectid)) {
        $lmWhere .= " AND lm.projectid = ?";
        $lmTypes .= "s";
        $lmParams[] = $projectid;
    }

    if (!empty($dateFrom) && !empty($dateTo)) {
        $lmWhere .= " AND lm.EmployeeID IN (
            SELECT DISTINCT labourid FROM hrm_labourattendance WHERE attendancedate BETWEEN ? AND ? AND (tedit IS NULL OR tedit = '')
        )";
        $lmTypes .= "ss";
        $lmParams[] = $dateFrom;
        $lmParams[] = $dateTo;
    }

    if ($locationid && $locationid !== 'all') {
        $lmWhere .= " AND lm.locationid = ?";
        $lmTypes .= "s";
        $lmParams[] = $locationid;
    }

    $sqlLM = "SELECT
                lm.EmployeeID,
                CAST(lm.EmployeeID AS UNSIGNED) AS labourid_int,
                CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                lm.locationid,
                IFNULL(loc.LocationName, '') AS LocationName
              FROM hrm_LabourMaster lm
              LEFT JOIN hrm_attendancelocationmaster loc ON lm.locationid = loc.LocationId
              WHERE {$lmWhere}
              ORDER BY lm.FirstName, lm.LastName";

    $stmtLM = $hrmconn->prepare($sqlLM);
    if (!$stmtLM) {
        echo json_encode(['status' => 'error', 'message' => 'DB error: ' . $hrmconn->error]);
        exit;
    }
    if (!empty($lmTypes)) {
        $stmtLM->bind_param($lmTypes, ...$lmParams);
    }
    $stmtLM->execute();
    $resLM = $stmtLM->get_result();

    $labours = [];
    $lookupIds = [];
    $idToEmpMap = [];
    while ($row = $resLM->fetch_assoc()) {
        $eid = trim($row['EmployeeID']);
        $row['FullName'] = trim(preg_replace('/\s+/', ' ', $row['FullName']));
        $labours[] = $row;
        $lookupIds[] = "'" . $hrmconn->real_escape_string($eid) . "'";
        $idToEmpMap[$eid] = $eid;
        if (!empty($row['labourid_int'])) {
            $lookupIds[] = "'" . $hrmconn->real_escape_string($row['labourid_int']) . "'";
            $idToEmpMap[$row['labourid_int']] = $eid;
        }
    }
    $stmtLM->close();

    $bankSet = [];
    if (!empty($lookupIds)) {
        $inClause = implode(",", array_unique($lookupIds));

        $resBank = $hrmconn->query("SELECT DISTINCT labourid FROM hrm_labourbankdetails 
                                    WHERE labourid IN ($inClause) AND (tedit IS NULL OR tedit='') 
                                      AND TRIM(IFNULL(accountnumber, '')) != ''
                                      AND TRIM(IFNULL(ifsccode, '')) != ''");
        if ($resBank) {
            while ($b = $resBank->fetch_assoc()) {
                $mappedEid = $idToEmpMap[$b['labourid']] ?? $b['labourid'];
                $bankSet[$mappedEid] = true;
            }
        }

        $resBankOld = $hrmconn->query("SELECT DISTINCT EmployeeID FROM hrm_employeebankdetails 
                                       WHERE EmployeeID IN ($inClause) AND (tedit IS NULL OR tedit='') 
                                         AND TRIM(IFNULL(AccountNumber, '')) != ''
                                         AND TRIM(IFNULL(IFSCCode, '')) != ''");
        if ($resBankOld) {
            while ($b = $resBankOld->fetch_assoc()) {
                $mappedEid = $idToEmpMap[$b['EmployeeID']] ?? $b['EmployeeID'];
                $bankSet[$mappedEid] = true;
            }
        }
    }

    $missingLabours = [];
    foreach ($labours as $l) {
        $eid = trim($l['EmployeeID']);
        $hasBank = !empty($bankSet[$eid]) || (!empty($l['labourid_int']) && !empty($bankSet[$l['labourid_int']]));
        if (!$hasBank) {
            $missingLabours[] = [
                'EmployeeID'   => $eid,
                'FullName'     => $l['FullName'],
                'locationid'   => $l['locationid'],
                'LocationName' => $l['LocationName']
            ];
        }
    }

    echo json_encode([
        'status'          => 'success',
        'total_labours'   => count($labours),
        'missing_count'   => count($missingLabours),
        'missing_labours' => $missingLabours
    ]);
    exit;
} else if ($action == "saveconfirmedcalculatedattendance") {
    $projectid        = $_POST['projectid']        ?? '';
    $companyid        = $_POST['companyid']        ?? '';
    $subcompanyid     = $_POST['subcompanyid']     ?? '';
    $dateFrom         = $_POST['dateFrom']         ?? '';
    $dateTo           = $_POST['dateTo']           ?? '';
    $attendanceJson   = $_POST['calculatedattdata'] ?? '[]';
    $postLocId        = $_POST['locationid']       ?? '';

    if (empty($projectid) || empty($dateFrom) || empty($dateTo)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
        exit;
    }

    $attdata = json_decode($attendanceJson, true);
    if (!is_array($attdata) || empty($attdata)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid attendance data.']);
        exit;
    }

    $attYear = (int)date('Y', strtotime($dateFrom));
    $attMonth = (int)date('n', strtotime($dateFrom));

    $errors = 0;
    foreach ($attdata as $s) {
        $lid   = $s['labourid'] ?? '';
        $pDays = (float)($s['presentDays'] ?? 0); // Full Present Days
        $hDays = (float)($s['halfDays'] ?? 0);    // Half-Present Days
        $rem   = $s['remark'] ?? '';

        if (empty($lid)) continue;

        // Resolve locationid: use selected locationid or lookup from hrm_LabourMaster
        $locId = $postLocId;
        if (empty($locId) || $locId === 'all') {
            $stmtLoc = $hrmconn->prepare("SELECT locationid FROM hrm_LabourMaster WHERE EmployeeID = ? LIMIT 1");
            if ($stmtLoc) {
                $stmtLoc->bind_param("s", $lid);
                $stmtLoc->execute();
                $locRow = $stmtLoc->get_result()->fetch_assoc();
                $stmtLoc->close();
                $locId = $locRow['locationid'] ?? '';
            }
        }

        // Fallback to hrm_labourattendance if still empty
        if (empty($locId)) {
            $stmtAttLoc = $hrmconn->prepare("SELECT labourlocationid, checkinlocation_mn FROM hrm_labourattendance WHERE labourid = ? AND projectid = ? AND attendancedate BETWEEN ? AND ? LIMIT 1");
            if ($stmtAttLoc) {
                $stmtAttLoc->bind_param("ssss", $lid, $projectid, $dateFrom, $dateTo);
                $stmtAttLoc->execute();
                $attLocRow = $stmtAttLoc->get_result()->fetch_assoc();
                $stmtAttLoc->close();
                $locId = !empty($attLocRow['labourlocationid']) ? $attLocRow['labourlocationid'] : ($attLocRow['checkinlocation_mn'] ?? '');
            }
        }

        if (empty($locId)) {
            $errors++;
            continue;
        }

        // Check if record already exists
        $checkStmt = $hrmconn->prepare("SELECT AttendanceID FROM hrm_labourcalculateattendance WHERE EmployeeID = ? AND CompanyID = ? AND SubCompanyID = ? AND ProjectID = ? AND DateFrom = ? AND DateTo = ?");
        $checkStmt->bind_param("ssssss", $lid, $companyid, $subcompanyid, $projectid, $dateFrom, $dateTo);
        $checkStmt->execute();
        $checkRow = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($checkRow) {
            // Update existing record with mandatory locationid
            $stmtUpdate = $hrmconn->prepare(
                "UPDATE hrm_labourcalculateattendance SET
                    locationid     = ?,
                    PresentDays    = ?,
                    oldPresentDays = ?,
                    HalfDays       = ?,
                    Comments       = ?
                 WHERE AttendanceID = ?"
            );
            $stmtUpdate->bind_param(
                "sdddsi",
                $locId, $pDays, $pDays, $hDays, $rem, $checkRow['AttendanceID']
            );
            if (!$stmtUpdate->execute()) {
                $errors++;
            }
            $stmtUpdate->close();
        } else {
            // Insert new record with mandatory locationid
            $stmtInsert = $hrmconn->prepare(
                "INSERT INTO hrm_labourcalculateattendance
                    (CompanyID, SubCompanyID, ProjectID, EmployeeID, locationid,
                     AttendanceYear, AttendanceMonth, DateFrom, DateTo,
                     PresentDays, oldPresentDays, HalfDays, Comments)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtInsert->bind_param(
                "sssssiissddds",
                $companyid, $subcompanyid, $projectid, $lid, $locId,
                $attYear, $attMonth, $dateFrom, $dateTo,
                $pDays, $pDays, $hDays, $rem
            );
            if (!$stmtInsert->execute()) {
                $errors++;
            }
            $stmtInsert->close();
        }
    }

    if ($errors === 0) {
        echo json_encode(['status' => 'success', 'message' => 'Calculated Attendance saved successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Failed to save {$errors} record(s). Location ID is mandatory."]);
    }
} else if ($action == "SaveConfirmedAttendanceSheets") {
    $projectid    = $_POST['projectid']    ?? '';
    $companyid    = $_POST['companyid']    ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $dateFrom     = $_POST['dateFrom']     ?? '';
    $dateTo       = $_POST['dateTo']       ?? '';
    $sheetsJson   = $_POST['sheets']       ?? '[]';
    $postLocId    = $_POST['locationid']   ?? 'all';

    if (empty($projectid) || empty($dateFrom) || empty($dateTo)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    $sheets = json_decode($sheetsJson, true);
    if (!is_array($sheets)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid sheets format.']);
        exit;
    }

    $attYear = (int)date('Y', strtotime($dateFrom));
    $attMonth = (int)date('n', strtotime($dateFrom));

    $errors = 0;
    foreach ($sheets as $s) {
        $lid   = $s['labourid'];
        $pDays = (float)$s['presentDays']; // Full Present Days
        $hDays = (float)$s['halfDays'];    // Half-Present Days
        $rem   = $s['remark'] ?? '';

        // Get location ID
        $locId = $postLocId;
        if ($locId === 'all' || empty($locId)) {
            $stmtLoc = $hrmconn->prepare("SELECT locationid FROM hrm_LabourMaster WHERE EmployeeID = ? LIMIT 1");
            $stmtLoc->bind_param("s", $lid);
            $stmtLoc->execute();
            $locRow = $stmtLoc->get_result()->fetch_assoc();
            $stmtLoc->close();
            $locId = $locRow['locationid'] ?? null;
        }

        // Check if record already exists
        $checkStmt = $hrmconn->prepare("SELECT AttendanceID FROM hrm_labourcalculateattendance WHERE EmployeeID = ? AND CompanyID = ? AND SubCompanyID = ? AND ProjectID = ? AND DateFrom = ? AND DateTo = ?");
        $checkStmt->bind_param("ssssss", $lid, $companyid, $subcompanyid, $projectid, $dateFrom, $dateTo);
        $checkStmt->execute();
        $checkRow = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($checkRow) {
            // Update existing record
            $stmtUpdate = $hrmconn->prepare(
                "UPDATE hrm_labourcalculateattendance SET
                    locationid     = ?,
                    PresentDays    = ?,
                    oldPresentDays = ?,
                    HalfDays       = ?,
                    Comments       = ?
                 WHERE AttendanceID = ?"
            );
            $stmtUpdate->bind_param(
                "sdddsi",
                $locId, $pDays, $pDays, $hDays, $rem, $checkRow['AttendanceID']
            );
            if (!$stmtUpdate->execute()) {
                $errors++;
            }
            $stmtUpdate->close();
        } else {
            // Insert new record
            $stmtInsert = $hrmconn->prepare(
                "INSERT INTO hrm_labourcalculateattendance
                    (CompanyID, SubCompanyID, ProjectID, EmployeeID, locationid,
                     AttendanceYear, AttendanceMonth, DateFrom, DateTo,
                     PresentDays, oldPresentDays, HalfDays, Comments)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtInsert->bind_param(
                "sssssiissddds",
                $companyid, $subcompanyid, $projectid, $lid, $locId,
                $attYear, $attMonth, $dateFrom, $dateTo,
                $pDays, $pDays, $hDays, $rem
            );
            if (!$stmtInsert->execute()) {
                $errors++;
            }
            $stmtInsert->close();
        }
    }

    if ($errors === 0) {
        echo json_encode(['status' => 'success', 'message' => 'All attendance sheets saved successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => "Failed to save {$errors} records."]);
    }
} else if ($action == "GetLabourAttendanceDetails") {
    $projectid    = $_POST['projectid']    ?? '';
    $employeeID   = $_POST['employeeID']   ?? '';
    $dateFrom     = $_POST['dateFrom']     ?? '';
    $dateTo       = $_POST['dateTo']       ?? '';

    if (empty($projectid) || empty($employeeID) || empty($dateFrom) || empty($dateTo)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    $stmtDays = $hrmconn->prepare(
        "SELECT attendancedate, checkintime_mn, checkouttime_mn, workinghours_mn,
                checkintime_dp, checkouttime_dp, workinghours_dp
         FROM hrm_labourattendance
         WHERE labourid = ? AND projectid = ? AND attendancedate BETWEEN ? AND ?
           AND (tedit IS NULL OR tedit = '')
         ORDER BY attendancedate ASC"
    );
    $stmtDays->bind_param("ssss", $employeeID, $projectid, $dateFrom, $dateTo);
    $stmtDays->execute();
    $daysRes = $stmtDays->get_result();

    $records = [];
    while ($dayRow = $daysRes->fetch_assoc()) {
        $hrs = 0.0;
        if ($dayRow['checkouttime_mn'] && $dayRow['workinghours_mn']) {
            $p = explode(':', $dayRow['workinghours_mn']);
            $hrs = max($hrs, (int)($p[0] ?? 0) + ((int)($p[1] ?? 0) / 60));
        }
        if ($dayRow['checkouttime_dp'] && $dayRow['workinghours_dp']) {
            $p = explode(':', $dayRow['workinghours_dp']);
            $hrs = max($hrs, (int)($p[0] ?? 0) + ((int)($p[1] ?? 0) / 60));
        }

        $type = 'Absent';
        if ($hrs > 2 && $hrs < 6) {
            $type = 'Half Present';
        } else if ($hrs >= 6) {
            $type = 'Full Present';
        } else if (($dayRow['checkintime_mn'] && !$dayRow['checkouttime_mn']) || ($dayRow['checkintime_dp'] && !$dayRow['checkouttime_dp'])) {
            $type = 'Mark-out Missing';
        }

        $records[] = [
            'attendancedate'   => $dayRow['attendancedate'],
            'checkintime_mn'   => (!empty($dayRow['checkintime_mn']) && $dayRow['checkintime_mn'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($dayRow['checkintime_mn'])) : '—',
            'checkouttime_mn'  => (!empty($dayRow['checkouttime_mn']) && $dayRow['checkouttime_mn'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($dayRow['checkouttime_mn'])) : ((!empty($dayRow['checkintime_mn']) && $dayRow['checkintime_mn'] !== '0000-00-00 00:00:00') ? 'MIS' : '—'),
            'workinghours_mn'  => $dayRow['workinghours_mn'] ?: '—',
            'checkintime_dp'   => (!empty($dayRow['checkintime_dp']) && $dayRow['checkintime_dp'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($dayRow['checkintime_dp'])) : '—',
            'checkouttime_dp'  => (!empty($dayRow['checkouttime_dp']) && $dayRow['checkouttime_dp'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($dayRow['checkouttime_dp'])) : ((!empty($dayRow['checkintime_dp']) && $dayRow['checkintime_dp'] !== '0000-00-00 00:00:00') ? 'MIS' : '—'),
            'workinghours_dp'  => $dayRow['workinghours_dp'] ?: '—',
            'max_hours'        => $hrs,
            'type'             => $type
        ];
    }
    $stmtDays->close();

    echo json_encode(['status' => 'success', 'data' => $records]);
    exit;
} else if ($action == "GetDiscrepancyAttendanceList") {
    @ob_clean();
    header('Content-Type: application/json');
    $projectid  = $_POST['projectid']  ?? '';
    $locationid = $_POST['locationid'] ?? 'all';
    $dateFrom   = $_POST['dateFrom']   ?? '';
    $dateTo     = $_POST['dateTo']     ?? '';

    if (empty($projectid) || empty($dateFrom) || empty($dateTo)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    $whereLoc = "";
    $params = [$projectid, $dateFrom, $dateTo];
    $types = "sss";
    if ($locationid && $locationid !== 'all') {
        $whereLoc = " AND (lm.locationid = ? OR a.labourid IN (SELECT EmployeeID FROM hrm_LabourMaster WHERE locationid = ?))";
        $params[] = $locationid;
        $params[] = $locationid;
        $types .= "ss";
    }

    $sql = "SELECT a.attendanceid, a.labourid, a.attendancedate, 
                   a.checkintime_mn, a.checkouttime_mn, a.workinghours_mn,
                   a.checkintime_dp, a.checkouttime_dp, a.workinghours_dp,
                   CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                   lm.EmployeeID
            FROM hrm_labourattendance a
            LEFT JOIN hrm_LabourMaster lm ON lm.EmployeeID = a.labourid
            WHERE a.projectid = ? AND a.attendancedate BETWEEN ? AND ?
              AND (a.tedit IS NULL OR a.tedit = '')
              $whereLoc
            ORDER BY a.attendancedate DESC, lm.FirstName ASC";

    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => $hrmconn->error]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();

    $discrepancies = [];
    while ($row = $res->fetch_assoc()) {
        $hrs = 0.0;
        if ($row['checkouttime_mn'] && $row['workinghours_mn']) {
            $p = explode(':', $row['workinghours_mn']);
            $hrs = max($hrs, (int)($p[0] ?? 0) + ((int)($p[1] ?? 0) / 60));
        }
        if ($row['checkouttime_dp'] && $row['workinghours_dp']) {
            $p = explode(':', $row['workinghours_dp']);
            $hrs = max($hrs, (int)($p[0] ?? 0) + ((int)($p[1] ?? 0) / 60));
        }

        $type = 'Absent';
        if ($hrs > 2 && $hrs < 6) {
            $type = 'Half Present';
        } else if ($hrs >= 6) {
            $type = 'Full Present';
        } else if (($row['checkintime_mn'] && !$row['checkouttime_mn']) || ($row['checkintime_dp'] && !$row['checkouttime_dp'])) {
            $type = 'Mark-out Missing';
        }

        $isMissingCheckout = ($row['checkintime_mn'] && !$row['checkouttime_mn']) || ($row['checkintime_dp'] && !$row['checkouttime_dp']);
        if ($isMissingCheckout || $type === 'Mark-out Missing' || $hrs == 0) {
            $discrepancies[] = [
                'attendanceid'    => $row['attendanceid'],
                'labourid'        => $row['labourid'],
                'EmployeeID'      => $row['EmployeeID'] ?: $row['labourid'],
                'FullName'        => trim($row['FullName']) ?: $row['labourid'],
                'attendancedate'  => $row['attendancedate'],
                'checkintime_mn'  => (!empty($row['checkintime_mn']) && $row['checkintime_mn'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['checkintime_mn'])) : '—',
                'checkouttime_mn' => (!empty($row['checkouttime_mn']) && $row['checkouttime_mn'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['checkouttime_mn'])) : ((!empty($row['checkintime_mn']) && $row['checkintime_mn'] !== '0000-00-00 00:00:00') ? 'MIS' : '—'),
                'workinghours_mn' => $row['workinghours_mn'] ?: '—',
                'checkintime_dp'  => (!empty($row['checkintime_dp']) && $row['checkintime_dp'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['checkintime_dp'])) : '—',
                'checkouttime_dp' => (!empty($row['checkouttime_dp']) && $row['checkouttime_dp'] !== '0000-00-00 00:00:00') ? date('h:i A', strtotime($row['checkouttime_dp'])) : ((!empty($row['checkintime_dp']) && $row['checkintime_dp'] !== '0000-00-00 00:00:00') ? 'MIS' : '—'),
                'workinghours_dp' => $row['workinghours_dp'] ?: '—',
                'max_hours'       => $hrs,
                'type'            => $type
            ];
        }
    }
    $stmt->close();
    echo json_encode(['status' => 'success', 'data' => $discrepancies]);
    exit;
} else if ($action == "BatchRemoveAttendanceEntries") {
    @ob_clean();
    header('Content-Type: application/json');
    $ids       = $_POST['attendanceids'] ?? [];
    $remark    = trim($_POST['remark'] ?? '');
    $loginname = trim($_POST['loginname'] ?? 'Admin');

    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No entries selected.']);
        exit;
    }
    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Remark is required to remove entries.']);
        exit;
    }

    $idList = implode(',', array_map('intval', $ids));
    $sql = "UPDATE hrm_labourattendance 
            SET tedit = NOW(), remark_mn = CONCAT(IFNULL(remark_mn,''), ' [Removed: ', ?, ']'), updated_by = ? 
            WHERE attendanceid IN ($idList)";
    $stmt = $hrmconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $remark, $loginname);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => count($ids) . ' entry(s) removed successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => $hrmconn->error]);
    }
    exit;
} else if ($action == "BatchUpdateCheckoutTime") {
    @ob_clean();
    header('Content-Type: application/json');
    $ids          = $_POST['attendanceids'] ?? [];
    $checkoutTime = trim($_POST['checkouttime'] ?? '');
    $remark       = trim($_POST['remark'] ?? '');
    $loginname    = trim($_POST['loginname'] ?? 'Admin');

    if (empty($ids) || !is_array($ids)) {
        echo json_encode(['status' => 'error', 'message' => 'No entries selected.']);
        exit;
    }
    if (empty($checkoutTime)) {
        echo json_encode(['status' => 'error', 'message' => 'Check-out time is required.']);
        exit;
    }
    if (empty($remark)) {
        echo json_encode(['status' => 'error', 'message' => 'Remark is required.']);
        exit;
    }

    $successCount = 0;
    foreach ($ids as $attId) {
        $attId = intval($attId);
        if ($attId <= 0) continue;

        $stmtFetch = $hrmconn->prepare("SELECT attendancedate, checkintime_mn, checkintime_dp FROM hrm_labourattendance WHERE attendanceid = ? LIMIT 1");
        if (!$stmtFetch) continue;
        $stmtFetch->bind_param("i", $attId);
        $stmtFetch->execute();
        $attRow = $stmtFetch->get_result()->fetch_assoc();
        $stmtFetch->close();

        if (!$attRow) continue;

        $attDate = $attRow['attendancedate'];
        $checkoutDT = $attDate . ' ' . $checkoutTime . ':00';

        $wh_mn = null;
        if (!empty($attRow['checkintime_mn']) && $attRow['checkintime_mn'] !== '0000-00-00 00:00:00') {
            $tIn  = strtotime($attRow['checkintime_mn']);
            $tOut = strtotime($checkoutDT);
            $diffSec = max(0, $tOut - $tIn);
            $h = floor($diffSec / 3600);
            $m = floor(($diffSec % 3600) / 60);
            $s = $diffSec % 60;
            $wh_mn = sprintf('%02d:%02d:%02d', $h, $m, $s);
        }

        $wh_dp = null;
        $checkoutDT_dp = null;
        if (!empty($attRow['checkintime_dp']) && $attRow['checkintime_dp'] !== '0000-00-00 00:00:00') {
            $checkoutDT_dp = $checkoutDT;
            $tInDP  = strtotime($attRow['checkintime_dp']);
            $tOutDP = strtotime($checkoutDT);
            $diffSecDP = max(0, $tOutDP - $tInDP);
            $hDP = floor($diffSecDP / 3600);
            $mDP = floor(($diffSecDP % 3600) / 60);
            $sDP = $diffSecDP % 60;
            $wh_dp = sprintf('%02d:%02d:%02d', $hDP, $mDP, $sDP);
        }

        $sqlUpdate = "UPDATE hrm_labourattendance SET
            checkouttime_mn      = ?,
            workinghours_mn      = IFNULL(?, workinghours_mn),
            checkoutby_mn        = ?,
            remark_mn            = ?,
            sourceout_mn         = IF(checkintime_mn IS NOT NULL AND checkintime_mn != '0000-00-00 00:00:00', IFNULL(sourcein_mn, 'Manual'), sourceout_mn),
            checkoutlocation_mn  = IF(checkintime_mn IS NOT NULL AND checkintime_mn != '0000-00-00 00:00:00', checkinlocation_mn, checkoutlocation_mn),
            checkoutscannerid_mn = IF(checkintime_mn IS NOT NULL AND checkintime_mn != '0000-00-00 00:00:00', 'Manual', checkoutscannerid_mn),
            checkouttime_dp      = IF(? IS NOT NULL, ?, checkouttime_dp),
            workinghours_dp      = IF(? IS NOT NULL, ?, workinghours_dp),
            checkoutby_dp        = IF(? IS NOT NULL, ?, checkoutby_dp),
            sourceout_dp         = IF(? IS NOT NULL, IFNULL(sourcein_dp, 'Manual'), sourceout_dp),
            checkoutlocation_dp  = IF(? IS NOT NULL, checkinlocation_dp, checkoutlocation_dp),
            checkoutscannerid_dp = IF(? IS NOT NULL, 'Manual', checkoutscannerid_dp),
            status               = 'Present',
            updated_at           = NOW(),
            updated_by           = ?
        WHERE attendanceid = ?";

        $stmtUp = $hrmconn->prepare($sqlUpdate);
        if ($stmtUp) {
            $stmtUp->bind_param("ssssssssssssssi",
                $checkoutDT, $wh_mn, $loginname, $remark,
                $checkoutDT_dp, $checkoutDT_dp,
                $wh_dp, $wh_dp,
                $loginname, $loginname,
                $checkoutDT_dp,
                $checkoutDT_dp,
                $checkoutDT_dp,
                $loginname,
                $attId
            );
            if ($stmtUp->execute()) {
                $successCount++;
            }
            $stmtUp->close();
        }
    }

    echo json_encode(['status' => 'success', 'message' => "Successfully updated $successCount check-out record(s)."]);
    exit;
} else if ($action == "GetSuggestedLabourID") {
    $locationid = $_POST['locationid'] ?? '';
    $query = "SELECT IFNULL(MAX(seriesid), 0) + 1 AS next_series FROM hrm_LabourMaster WHERE locationid = ?";
    $stmt = $hrmconn->prepare($query);
    $stmt->bind_param("s", $locationid);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $next_series = $res['next_series'] ?? 1;
    $stmt->close();
    echo json_encode(['status' => 'Success', 'suggested_id' => $next_series]);
    exit;
}


function saveLabourPayment($conn, $companyid, $subcompanyid, $projectid, $EmployeeID, $PerDayPayment, $OtherPayment = 0.00, $Deductions = 0.00, $CurrencyCode = "INR", $PaymentMethod = "Cash", $Comments = null)
{
    $sql = "INSERT INTO hrm_LabourPaymentMaster (companyid, subcompanyid, projectid, EmployeeID, 
                PerDayPayment, OtherPayment, Deductions, CurrencyCode, PaymentMethod, Comments)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                PerDayPayment = VALUES(PerDayPayment),
                OtherPayment  = VALUES(OtherPayment),
                Deductions    = VALUES(Deductions),
                CurrencyCode  = VALUES(CurrencyCode),
                PaymentMethod = VALUES(PaymentMethod),
                Comments      = VALUES(Comments)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssddssss",
        $companyid,
        $subcompanyid,
        $projectid,
        $EmployeeID,
        $PerDayPayment,
        $OtherPayment,
        $Deductions,
        $CurrencyCode,
        $PaymentMethod,
        $Comments
    );

    if ($stmt->execute()) {
        return ["status" => "success", "message" => "Record saved successfully."];
    } else {
        return ["status" => "error", "message" => $stmt->error];
    }
}


function createLabourPaySlipWithHeaderFooter($outputFile, $companyname, $projectname, $EmployeeData, $headerImage = null, $footerImage = null, $a1 = 40, $a2 = 20, $b1 = 15, $b2 = 40)
{

    class CustomPDF extends TCPDF
    {
        public $companyname;
        public $projectname;
        public $headerImage;
        public $footerImage;
        public $a1;
        public $a2;
        public $b1;
        public $b2;

        public function setHeaderFooterData($headerImage, $footerImage, $companyname, $projectname, $a1, $a2, $b1, $b2)
        {
            $this->headerImage = $headerImage;
            $this->footerImage = $footerImage;
            $this->companyname = $companyname;
            $this->projectname = $projectname;
            $this->a1 = $a1;
            $this->a2 = $a2;
            $this->b1 = $b1;
            $this->b2 = $b2;
        }

        public function Header()
        {
            if ($this->headerImage) {
                $this->Image($this->headerImage, 10, 10, $this->a1, $this->a2);
            }
            $this->SetFont('helvetica', 'B', 14);
            $this->SetXY(50, 10);
            $this->Cell(100, 10, 'LABOUR PAY SLIP', 0, 1, 'C');

            if ($this->projectname) {
                $this->SetFont('helvetica', '', 10);
                $this->SetXY(50, 20);
                $this->Cell(100, 10, $this->projectname, 0, 1, 'C');
            }
        }

        public function Footer()
        {
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

    if (!empty($EmployeeData)) {
        // Create new PDF document
        $pdf = new CustomPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
        $pdf->setHeaderFooterData($headerImage, $footerImage, $companyname, $projectname, $a1, $a2, $b1, $b2);

        // Set document properties
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetTitle('Labour Payslip');
        $pdf->SetMargins(15, 30, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->AddPage();

        // Employee Details
        $employeeName = $EmployeeData['EmployeeName'] ?? 'N/A';
        $payperiod = date('M-Y', strtotime(($EmployeeData['SalaryYear'] ?? date('Y')) . '-' . ($EmployeeData['SalaryMonth'] ?? date('m')) . '-01'));
        $employeeId = $EmployeeData['EmployeeID'] ?? 'N/A';
        $dateOfJoining = $EmployeeData['DateOfJoining'] ?? 'N/A';
        $DesignationName = $EmployeeData['DesignationName'] ?? 'N/A';
        $DepartmentName = $EmployeeData['DepartmentName'] ?? 'N/A';
        $WorkedDays = $EmployeeData['WorkedDays'] ?? 'N/A';
        $NetSalary = $EmployeeData['NetSalary'];
        $dateRange = $EmployeeData['dateRange'] ?? '';

        // Employee Info Table
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->Cell(45, 7, "Employee Name:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $employeeName, 1, 0, 'L');
        $pdf->Cell(45, 7, "Employee ID:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $employeeId, 1, 1, 'L');

        $pdf->Cell(45, 7, "Designation:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $DesignationName, 1, 0, 'L');
        $pdf->Cell(45, 7, "Worked Days:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $WorkedDays, 1, 1, 'L');

        $pdf->Cell(45, 7, "Pay Period:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, $dateRange, 1, 0, 'L');
        $pdf->Cell(45, 7, "Labour Payment:", 1, 0, 'L', true);
        $pdf->Cell(45, 7, number_format(($NetSalary), 2), 1, 1, 'L');


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

if ($action == "GetPhysicalVerificationAttendance") {
        try {
            $projectid = $_POST['projectid'] ?? '';
            $locationid = $_POST['locationid'] ?? '';
            $polyhouseid = $_POST['polyhouseid'] ?? '';
            $attendancedate = $_POST['attendancedate'] ?? '';

            if (empty($projectid) || empty($locationid) || empty($polyhouseid) || empty($attendancedate)) {
                echo json_encode(["status" => "Error", "message" => "All parameters are required."]);
                exit;
            }

            $isMN = (strtolower($polyhouseid) === 'main-gate');
            $sfx = $isMN ? 'mn' : 'dp';
            
            $LocationName = "";
            $stmtLoc = $hrmconn->prepare("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = ? LIMIT 1");
            if ($stmtLoc) {
                $stmtLoc->bind_param("s", $locationid);
                $stmtLoc->execute();
                $resLoc = $stmtLoc->get_result()->fetch_assoc();
                if ($resLoc) {
                    $LocationName = $resLoc['LocationName'];
                }
                $stmtLoc->close();
            }

            $locLike = '%' . $LocationName . '%';
            $polyNameLike = '';

            if ($isMN==1) {
                $stmtPoly = $krfconn->prepare("SELECT polyhousename FROM krf_polyhouse_master WHERE polyhouseid = ? LIMIT 1");
                if ($stmtPoly) {
                    $stmtPoly->bind_param("s", $polyhouseid);
                    $stmtPoly->execute();
                    $resPoly = $stmtPoly->get_result()->fetch_assoc();
                    if ($resPoly) {
                        $polyNameLike = '%' . $resPoly['polyhousename'] . '%';
                    }
                    $stmtPoly->close();
                }
                if (empty($polyNameLike)) {
                    $polyNameLike = '%' . $polyhouseid . '%';
                }
            }

            if ($isMN==1) {
                $sql = "SELECT la.attendanceid, la.labourid, la.attendancedate, 
                               la.checkintime_mn as checkintime, la.checkouttime_mn as checkouttime, 
                               la.workinghours_mn as workinghours, la.status, la.approvalstatus, 
                               la.approvedby, la.approvedatetime, la.approvalremarks,
                               la.labourlocationid, lm.locationid,
                               lm.FirstName, lm.LastName, lm.MobileNo
                        FROM hrm_labourattendance la
                        JOIN hrm_LabourMaster lm ON la.labourid = lm.EmployeeID
                        WHERE la.projectid = ? AND (la.tedit IS NULL OR la.tedit = '') AND la.attendancedate = ? AND la.checkinlocation_mn = ?";
                
                $stmt = $hrmconn->prepare($sql);
                if (!$stmt) {
                    echo json_encode(["status" => "Error", "message" => $hrmconn->error]);
                    exit;
                }
                $stmt->bind_param("sss", $projectid, $attendancedate, $locationid);
            } else {
                $sql = "SELECT la.attendanceid, la.labourid, la.attendancedate, 
                               la.checkintime_dp as checkintime, la.checkouttime_dp as checkouttime, 
                               la.workinghours_dp as workinghours, la.status, la.approvalstatus, 
                               la.approvedby, la.approvedatetime, la.approvalremarks,
                               la.labourlocationid, lm.locationid,
                               lm.FirstName, lm.LastName, lm.MobileNo
                        FROM hrm_labourattendance la
                        JOIN hrm_LabourMaster lm ON la.labourid = lm.EmployeeID
                        WHERE la.projectid = ? 
                          AND la.attendancedate = ?
                          AND (la.tedit IS NULL OR la.tedit = '')
                          AND (la.checkinlocation_dp = ? OR la.checkoutlocation_dp = ? OR la.checkinlocation_dp LIKE ? OR la.checkoutlocation_dp LIKE ?)
                          AND (la.checkinscannerid_dp LIKE ? OR la.checkoutscannerid_dp LIKE ?)";
                
                $stmt = $hrmconn->prepare($sql);
                if (!$stmt) {
                    echo json_encode(["status" => "Error", "message" => $hrmconn->error]);
                    exit;
                }
                $stmt->bind_param("ssssssss", $projectid, $attendancedate, $locationid, $locationid, $locLike, $locLike, $polyNameLike, $polyNameLike);
            }

            $stmt->execute();
            $res = $stmt->get_result();
            $data = [];
            while ($row = $res->fetch_assoc()) {
                $row['FullName'] = trim(($row['FirstName'] ?? '') . ' ' . ($row['LastName'] ?? ''));
                $data[] = $row;
            }
            $stmt->close();

            echo json_encode(["status" => "Success", "data" => $data]);
            exit;
        } catch (Throwable $e) {
            echo json_encode(["status" => "Error", "message" => "Exception: " . $e->getMessage()]);
            exit;
        }
    } else if ($action == "RemoveAttendance") {
        $attendanceid = $_POST['attendanceid'] ?? '';
        $loginname    = $_POST['loginname']    ?? 'System';
        $localdatetime = $_POST['localdatetime'] ?? date('Y-m-d H:i:s');

        if (empty($attendanceid)) {
            echo json_encode(['status' => 'Error', 'message' => 'Missing attendance ID.']);
            exit;
        }

        $teditremark = "removed on date " . $localdatetime . " by " . $loginname;
        
        $stmt = $hrmconn->prepare("UPDATE hrm_labourattendance SET tedit = ? WHERE attendanceid = ?");
        if ($stmt) {
            $stmt->bind_param("si", $teditremark, $attendanceid);
            if ($stmt->execute()) {
                echo json_encode(['status' => 'Success', 'message' => 'Attendance removed successfully.']);
            } else {
                echo json_encode(['status' => 'Error', 'message' => $stmt->error]);
            }
            $stmt->close();
        } else {
            echo json_encode(['status' => 'Error', 'message' => $hrmconn->error]);
        }
        exit;
    } else if ($action == "VerifyLabourAttendance") {
        $attendanceids = $_POST['attendanceid'] ?? '';
        $approvalstatus = $_POST['approvalstatus'] ?? 'approved';
        $loginname = $_POST['loginname'] ?? 'SYSTEM';

        if (empty($attendanceids)) {
            echo json_encode(["status" => "Error", "message" => "Attendance ID is required."]);
            exit;
        }

        $now = date('Y-m-d H:i:s');
        
        // Handle multiple comma-separated IDs
        $ids = array_filter(array_map('intval', explode(',', $attendanceids)));
        if (empty($ids)) {
            echo json_encode(["status" => "Error", "message" => "Invalid Attendance ID list."]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE hrm_labourattendance 
                SET approvalstatus = ?, 
                    approvedby = ?, 
                    approvedatetime = ?, 
                    updated_at = ?, 
                    updated_by = ? 
                WHERE attendanceid IN ($placeholders)";
        
        $stmt = $hrmconn->prepare($sql);
        if ($stmt) {
            // Bind params dynamically
            $types = "sssss" . str_repeat("i", count($ids));
            $params = array($types, $approvalstatus, $loginname, $now, $now, $loginname);
            foreach ($ids as &$id) {
                $params[] = &$id;
            }
            call_user_func_array(array($stmt, 'bind_param'), $params);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                echo json_encode(["status" => "Success", "message" => "Attendance verification status updated successfully."]);
            } else {
                echo json_encode(["status" => "Error", "message" => $hrmconn->error]);
            }
        } else {
            echo json_encode(["status" => "Error", "message" => $hrmconn->error]);
        }
        exit;
    } else if ($action == "FetchGoogleSheet") {
        try {
            if (!function_exists('curl_init')) {
                throw new Exception("cURL PHP extension is not enabled on this server.");
            }

            $locationid     = $_POST['locationid'] ?? '';
            $projectid      = $_POST['projectid'] ?? '';
            $attendancedate = $_POST['attendancedate'] ?? '';

            if (empty($locationid) || empty($projectid)) {
                echo json_encode(["status" => "Error", "message" => "Location ID and Project ID are required to fetch Google Sheet."]);
                exit;
            }

            // Query database for configured google sheet for this location and project
            $stmt = $hrmconn->prepare("SELECT googlesheetid, sheetname FROM hrm_attverify_locationsheets WHERE projectid = ? AND locationid = ? AND status = 1 LIMIT 1");
            if (!$stmt) {
                echo json_encode(["status" => "Error", "message" => "DB Error: " . $hrmconn->error]);
                exit;
            }
            $stmt->bind_param("ss", $projectid, $locationid);
            $stmt->execute();
            $result = $stmt->get_result();
            $sheetRow = $result->fetch_assoc();
            $stmt->close();

            if (!$sheetRow || empty($sheetRow['googlesheetid'])) {
                echo json_encode([
                    "status" => "Error",
                    "message" => "No active Google Sheet configured for this Location and Project. Please add/enable a sheet entry in Attendance Verification -> Location Google Sheets."
                ]);
                exit;
            }

            $googleSheetId = trim($sheetRow['googlesheetid']);
            $defaultSheetName = trim($sheetRow['sheetname'] ?? '');

            $target_gid = '0';
            $sheet_name_found = !empty($defaultSheetName) ? $defaultSheetName : 'Sheet1';

            // Fetch the main HTML edit page of the spreadsheet to parse tabs and GIDs
            $edit_url = 'https://docs.google.com/spreadsheets/d/' . urlencode($googleSheetId) . '/edit';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $edit_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $html = curl_exec($ch);
            $html_http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($html_http_code == 200 && !empty($html)) {
                // Match: "[GID]",[{"1":[[0,0,"[TAB_NAME]"
                preg_match_all('/\\\\?"(\d+)\\\\?"\s*,\s*\[\s*\{\s*\\\\?"1\\\\?"\s*:\s*\[\s*\[\s*0\s*,\s*0\s*,\s*\\\\?"([^"]+)\\\\?"/i', $html, $matches);
                
                if (!empty($matches[1])) {
                    $clean_date_str = str_replace(['/', '.'], '-', trim($attendancedate));
                    $time = strtotime($clean_date_str);
                    if (!$time) {
                        $time = strtotime($attendancedate);
                    }
                    $formats = [];
                    if ($time) {
                        $d = date('d', $time);
                        $j = date('j', $time);
                        $m = date('m', $time);
                        $n = date('n', $time);
                        $Y = date('Y', $time);
                        $y = date('y', $time);
                        $M = date('M', $time);

                        $formats = array_unique([
                            "$d-$m-$y",   // 21-08-26
                            "$j-$n-$y",   // 21-8-26 or 1-8-26
                            "$d-$n-$y",   // 01-8-26
                            "$j-$m-$y",   // 1-08-26
                            "$d-$m-$Y",   // 21-08-2026
                            "$j-$n-$Y",   // 21-8-2026
                            "$d/$m/$y",   // 21/08/26
                            "$j/$n/$y",   // 21/8/26
                            "$d/$m/$Y",   // 21/08/2026
                            "$d.$m.$y",   // 21.08.26
                            "$d.$m.$Y",   // 21.08.2026
                            "$d-$M-$y",   // 21-Aug-26
                            "$d-$M-$Y",   // 21-Aug-2026
                            "$j-$M-$y",   // 1-Aug-26
                            "$d $M $y",   // 21 Aug 26
                            "$d $M $Y",   // 21 Aug 2026
                            "$Y-$m-$d",   // 2026-08-21
                            "$Y/$m/$d",   // 2026/08/21
                        ]);
                    }

                    $matchedByDate = false;
                    if (!empty($formats)) {
                        foreach ($matches[1] as $idx => $gid) {
                            $raw_name = $matches[2][$idx];
                            $name = trim(rtrim($raw_name, '\\ '));
                            // Normalize name by removing extra backslashes and whitespace
                            $clean_tab_name = trim(preg_replace('/[\\\\\/]+$/', '', $name));
                            
                            foreach ($formats as $format) {
                                if (stripos($clean_tab_name, $format) !== false || stripos($name, $format) !== false) {
                                    $target_gid = $gid;
                                    $sheet_name_found = $clean_tab_name;
                                    $matchedByDate = true;
                                    break 2;
                                }
                            }
                        }
                    }

                    if (!$matchedByDate && !empty($defaultSheetName)) {
                        foreach ($matches[1] as $idx => $gid) {
                            $raw_name = $matches[2][$idx];
                            $name = trim(rtrim($raw_name, '\\ '));
                            $clean_tab_name = trim(preg_replace('/[\\\\\/]+$/', '', $name));
                            if (strcasecmp($clean_tab_name, $defaultSheetName) === 0 || strcasecmp($name, $defaultSheetName) === 0) {
                                $target_gid = $gid;
                                $sheet_name_found = $clean_tab_name;
                                break;
                            }
                        }
                    }

                    if ($target_gid === '0' && !empty($matches[1][0])) {
                        $target_gid = $matches[1][0];
                        $sheet_name_found = trim(rtrim($matches[2][0], '\\ '));
                    }
                }
            }

            // Fetch the CSV for target GID
            $url = 'https://docs.google.com/spreadsheets/d/' . urlencode($googleSheetId) . '/export?format=csv&gid=' . $target_gid;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);

            $response = curl_exec($ch);
            if ($response === false) {
                throw new Exception(curl_error($ch));
            }
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code == 200) {
                $lines = explode("\n", str_replace("\r", "", $response));
                $data = [];
                foreach ($lines as $line) {
                    if (empty(trim($line))) continue;
                    $data[] = str_getcsv($line);
                }
                echo json_encode([
                    "status" => "Success", 
                    "data" => $data, 
                    "sheetName" => $sheet_name_found,
                    "gid" => $target_gid
                ]);
            } else {
                echo json_encode(["status" => "Error", "message" => "Failed to fetch Google Sheet (HTTP " . $http_code . "). Make sure Google Sheet sharing permissions are set to 'Anyone with link can view'."]);
            }
            exit;
        } catch (Throwable $e) {
            echo json_encode(["status" => "Error", "message" => "Server Error: " . $e->getMessage()]);
            exit;
        }
    } else if ($action == "getLabourAttendancePAReport") {
        $projectid = $_POST['projectid'] ?? '';
        $datefrom  = $_POST['datefrom']  ?? '';
        $dateto    = $_POST['dateto']    ?? '';
        $locationid= $_POST['locationid']?? 'all';

        if (empty($projectid) || empty($datefrom) || empty($dateto)) {
            echo json_encode(["status" => "error", "message" => "Project ID, From Date, and To Date are required."]);
            exit;
        }

        // Generate date list
        $dates = [];
        $period = new DatePeriod(
            new DateTime($datefrom),
            new DateInterval('P1D'),
            (new DateTime($dateto))->modify('+1 day')
        );
        foreach ($period as $dt) {
            $dates[] = [
                'date' => $dt->format('Y-m-d'),
                'disp' => $dt->format('d')
            ];
        }

        // Fetch locations map
        $locMap = [];
        $resLoc = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE ProjectID = '$projectid'");
        if ($resLoc) {
            while ($lRow = $resLoc->fetch_assoc()) {
                $locMap[$lRow['LocationId']] = $lRow['LocationName'];
            }
        }

        // Build labour query - ONLY include active labours who have attendance in this date range
        $lmWhere = "lm.projectid = ? AND lm.Status = 'Active' AND lm.EmployeeID IN (
            SELECT DISTINCT labourid 
            FROM hrm_labourattendance 
            WHERE projectid = ? AND attendancedate BETWEEN ? AND ? AND (tedit IS NULL OR tedit = '')
        )";
        $lmParams = [$projectid, $projectid, $datefrom, $dateto];
        $lmTypes = "ssss";
        if (!empty($locationid) && $locationid !== 'all') {
            $lmWhere .= " AND lm.locationid = ?";
            $lmParams[] = $locationid;
            $lmTypes .= "s";
        }

        $sqlLM = "SELECT lm.EmployeeID,
                         CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                         lm.locationid,
                         IFNULL(lm.Gender, '') AS Gender
                  FROM hrm_LabourMaster lm
                  WHERE {$lmWhere}
                  ORDER BY lm.FirstName, lm.LastName";
        
        $stmtLM = $hrmconn->prepare($sqlLM);
        $labours = [];
        if ($stmtLM) {
            $stmtLM->bind_param($lmTypes, ...$lmParams);
            $stmtLM->execute();
            $resLM = $stmtLM->get_result();
            while ($r = $resLM->fetch_assoc()) {
                $r['FullName'] = trim(preg_replace('/\s+/', ' ', $r['FullName']));
                $labours[] = $r;
            }
            $stmtLM->close();
        }

        // Fetch attendance records in date range
        $attSql = "SELECT labourid, attendancedate, checkintime_mn, checkouttime_mn, workinghours_mn, checkintime_dp, checkouttime_dp, workinghours_dp
                   FROM hrm_labourattendance
                   WHERE projectid = ? AND attendancedate BETWEEN ? AND ?
                     AND (tedit IS NULL OR tedit = '')";
        $stmtAtt = $hrmconn->prepare($attSql);
        $attMap = []; // [labourid][attendancedate] => ['hrs' => float, 'has_in' => bool, 'has_out' => bool]
        if ($stmtAtt) {
            $stmtAtt->bind_param("sss", $projectid, $datefrom, $dateto);
            $stmtAtt->execute();
            $resAtt = $stmtAtt->get_result();
            while ($aRow = $resAtt->fetch_assoc()) {
                $lid = $aRow['labourid'];
                $adate = $aRow['attendancedate'];

                $hasIn = (!empty($aRow['checkintime_mn']) && $aRow['checkintime_mn'] !== '0000-00-00 00:00:00') ||
                         (!empty($aRow['checkintime_dp']) && $aRow['checkintime_dp'] !== '0000-00-00 00:00:00');

                $hasOut = (!empty($aRow['checkouttime_mn']) && $aRow['checkouttime_mn'] !== '0000-00-00 00:00:00') ||
                          (!empty($aRow['checkouttime_dp']) && $aRow['checkouttime_dp'] !== '0000-00-00 00:00:00');

                $hrs = 0.0;
                if ($aRow['checkouttime_mn'] && $aRow['workinghours_mn']) {
                    $p = explode(':', $aRow['workinghours_mn']);
                    $h = isset($p[0]) ? intval($p[0]) : 0;
                    $m = isset($p[1]) ? intval($p[1]) : 0;
                    $s = isset($p[2]) ? intval($p[2]) : 0;
                    $hrs = max($hrs, $h + ($m / 60.0) + ($s / 3600.0));
                }
                if ($aRow['checkouttime_dp'] && $aRow['workinghours_dp']) {
                    $p = explode(':', $aRow['workinghours_dp']);
                    $h = isset($p[0]) ? intval($p[0]) : 0;
                    $m = isset($p[1]) ? intval($p[1]) : 0;
                    $s = isset($p[2]) ? intval($p[2]) : 0;
                    $hrs = max($hrs, $h + ($m / 60.0) + ($s / 3600.0));
                }

                if (!isset($attMap[$lid])) {
                    $attMap[$lid] = [];
                }
                
                $prevHrs = $attMap[$lid][$adate]['hrs'] ?? 0.0;
                $prevIn  = $attMap[$lid][$adate]['has_in'] ?? false;
                $prevOut = $attMap[$lid][$adate]['has_out'] ?? false;

                $attMap[$lid][$adate] = [
                    'hrs'     => max($prevHrs, $hrs),
                    'has_in'  => $prevIn || $hasIn,
                    'has_out' => $prevOut || $hasOut
                ];
            }
            $stmtAtt->close();
        }

        $reportData = [];
        foreach ($labours as $lab) {
            $lid = $lab['EmployeeID'];
            $locName = $locMap[$lab['locationid']] ?? $lab['locationid'];

            $attByDate = [];
            $totalP = 0;
            $totalHD = 0;
            $totalA = 0;
            $totalMIS = 0;

            foreach ($dates as $dObj) {
                $d = $dObj['date'];
                $attInfo = $attMap[$lid][$d] ?? null;

                if ($attInfo) {
                    $hrs    = $attInfo['hrs'];
                    $hasIn  = $attInfo['has_in'];
                    $hasOut = $attInfo['has_out'];

                    if ($hrs > 2.0 && $hrs < 6.0) {
                        $st = 'HD';
                        $totalHD++;
                    } else if ($hrs >= 6.0) {
                        $st = 'P';
                        $totalP++;
                    } else if ($hasIn && !$hasOut) {
                        $st = 'MIS';
                        $totalMIS++;
                    } else {
                        $st = 'A';
                        $totalA++;
                    }
                } else {
                    $st = 'A';
                    $totalA++;
                }

                $attByDate[$d] = $st;
            }

            // Only list labour if at least 1 day attendance (P, HD, or MIS) exists for selected date range and location
            if ($totalP == 0 && $totalHD == 0 && $totalMIS == 0) {
                continue;
            }

            $payableDays = $totalP + ($totalHD * 0.5);

            $reportData[] = [
                'labourid'    => $lid,
                'FullName'    => $lab['FullName'],
                'location'    => $locName,
                'Gender'      => $lab['Gender'] ?? '',
                'attendance'  => $attByDate,
                'total_p'     => $totalP,
                'total_hd'    => $totalHD,
                'total_mis'   => $totalMIS,
                'total_a'     => $totalA,
                'payable_days'=> $payableDays
            ];
        }

        echo json_encode(["status" => "success", "dates" => $dates, "data" => $reportData]);
        exit;

    } else if ($action == "getLabourAttendanceTimeReport") {
        $projectid = $_POST['projectid'] ?? '';
        $datefrom  = $_POST['datefrom']  ?? '';
        $dateto    = $_POST['dateto']    ?? '';
        $locationid= $_POST['locationid']?? 'all';

        if (empty($projectid) || empty($datefrom) || empty($dateto)) {
            echo json_encode(["status" => "error", "message" => "Project ID, From Date, and To Date are required."]);
            exit;
        }

        // Generate date list
        $dates = [];
        $period = new DatePeriod(
            new DateTime($datefrom),
            new DateInterval('P1D'),
            (new DateTime($dateto))->modify('+1 day')
        );
        foreach ($period as $dt) {
            $dates[] = [
                'date'      => $dt->format('Y-m-d'),
                'disp'      => $dt->format('d'),
                'full_disp' => $dt->format('d-M')
            ];
        }

        // Fetch locations map
        $locMap = [];
        $resLoc = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE ProjectID = '$projectid'");
        if ($resLoc) {
            while ($lRow = $resLoc->fetch_assoc()) {
                $locMap[$lRow['LocationId']] = $lRow['LocationName'];
            }
        }

        // Build labour query - ONLY include active labours who have attendance in this date range
        $lmWhere = "lm.projectid = ? AND lm.Status = 'Active' AND lm.EmployeeID IN (
            SELECT DISTINCT labourid 
            FROM hrm_labourattendance 
            WHERE projectid = ? AND attendancedate BETWEEN ? AND ? AND (tedit IS NULL OR tedit = '')
        )";
        $lmParams = [$projectid, $projectid, $datefrom, $dateto];
        $lmTypes = "ssss";

        if (!empty($locationid) && $locationid !== 'all') {
            $lmWhere .= " AND lm.locationid = ?";
            $lmParams[] = $locationid;
            $lmTypes .= "s";
        }

        $sqlLM = "SELECT lm.EmployeeID,
                         CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                         lm.locationid,
                         IFNULL(lm.Gender, '') AS Gender
                  FROM hrm_LabourMaster lm
                  WHERE {$lmWhere}
                  ORDER BY lm.FirstName, lm.LastName";
        
        $stmtLM = $hrmconn->prepare($sqlLM);
        $labours = [];
        if ($stmtLM) {
            $stmtLM->bind_param($lmTypes, ...$lmParams);
            $stmtLM->execute();
            $resLM = $stmtLM->get_result();
            while ($r = $resLM->fetch_assoc()) {
                $r['FullName'] = trim(preg_replace('/\s+/', ' ', $r['FullName']));
                $labours[] = $r;
            }
            $stmtLM->close();
        }

        // Fetch attendance records in date range
        $attSql = "SELECT attendanceid, labourid, attendancedate, checkintime_mn, checkouttime_mn, workinghours_mn, checkintime_dp, checkouttime_dp, workinghours_dp
                   FROM hrm_labourattendance
                   WHERE projectid = ? AND attendancedate BETWEEN ? AND ?
                     AND (tedit IS NULL OR tedit = '')";
        $stmtAtt = $hrmconn->prepare($attSql);
        $attMap = []; // [labourid][attendancedate] => obj
        if ($stmtAtt) {
            $stmtAtt->bind_param("sss", $projectid, $datefrom, $dateto);
            $stmtAtt->execute();
            $resAtt = $stmtAtt->get_result();
            while ($aRow = $resAtt->fetch_assoc()) {
                $lid = $aRow['labourid'];
                $adate = $aRow['attendancedate'];

                $inTimes = [];
                if (!empty($aRow['checkintime_mn']) && $aRow['checkintime_mn'] !== '0000-00-00 00:00:00') $inTimes[] = $aRow['checkintime_mn'];
                if (!empty($aRow['checkintime_dp']) && $aRow['checkintime_dp'] !== '0000-00-00 00:00:00') $inTimes[] = $aRow['checkintime_dp'];

                $outTimes = [];
                if (!empty($aRow['checkouttime_mn']) && $aRow['checkouttime_mn'] !== '0000-00-00 00:00:00') $outTimes[] = $aRow['checkouttime_mn'];
                if (!empty($aRow['checkouttime_dp']) && $aRow['checkouttime_dp'] !== '0000-00-00 00:00:00') $outTimes[] = $aRow['checkouttime_dp'];

                $hrs = 0.0;
                if ($aRow['checkouttime_mn'] && $aRow['workinghours_mn']) {
                    $p = explode(':', $aRow['workinghours_mn']);
                    $h = isset($p[0]) ? intval($p[0]) : 0;
                    $m = isset($p[1]) ? intval($p[1]) : 0;
                    $s = isset($p[2]) ? intval($p[2]) : 0;
                    $hrs = max($hrs, $h + ($m / 60.0) + ($s / 3600.0));
                }
                if ($aRow['checkouttime_dp'] && $aRow['workinghours_dp']) {
                    $p = explode(':', $aRow['workinghours_dp']);
                    $h = isset($p[0]) ? intval($p[0]) : 0;
                    $m = isset($p[1]) ? intval($p[1]) : 0;
                    $s = isset($p[2]) ? intval($p[2]) : 0;
                    $hrs = max($hrs, $h + ($m / 60.0) + ($s / 3600.0));
                }

                $inStr = !empty($inTimes) ? date('H:i', strtotime(min($inTimes))) : '';
                $hasOut = !empty($outTimes);
                $outStr = $hasOut ? date('H:i', strtotime(max($outTimes))) : 'MIS';

                $durStr = '';
                if ($hrs > 0) {
                    $h = floor($hrs);
                    $m = round(($hrs - $h) * 60);
                    $durStr = "({$h}h {$m}m)";
                }

                // Status Rules
                if ($hrs > 2.0 && $hrs < 6.0) {
                    $st = 'HD';
                } else if ($hrs >= 6.0) {
                    $st = 'P';
                } else if (!empty($inStr) && !$hasOut) {
                    $st = 'MIS';
                } else {
                    $st = 'A';
                }

                if (!isset($attMap[$lid])) {
                    $attMap[$lid] = [];
                }
                $attMap[$lid][$adate] = [
                    'in_time'  => $inStr,
                    'out_time' => $outStr,
                    'dur_str'  => $durStr,
                    'status'   => $st,
                    'hrs'      => $hrs
                ];
            }
            $stmtAtt->close();
        }

        $reportData = [];
        foreach ($labours as $lab) {
            $lid = $lab['EmployeeID'];
            $locName = $locMap[$lab['locationid']] ?? $lab['locationid'];

            $attByDate = [];
            $totalP = 0;
            $totalHD = 0;
            $totalMIS = 0;
            $totalA = 0;

            foreach ($dates as $dObj) {
                $d = $dObj['date'];
                $item = $attMap[$lid][$d] ?? null;

                if ($item) {
                    $st = $item['status'];
                    if ($st === 'P') $totalP++;
                    else if ($st === 'HD') $totalHD++;
                    else $totalA++;
                    $attByDate[$d] = $item;
                } else {
                    $totalA++;
                    $attByDate[$d] = [
                        'in_time'  => '',
                        'out_time' => '',
                        'dur_str'  => '',
                        'status'   => 'A',
                        'hrs'      => 0
                    ];
                }
            }

            if ($totalP == 0 && $totalHD == 0) {
                continue; // Only list labour if at least 1 day attendance exists
            }

            $payableDays = $totalP + ($totalHD * 0.5);

            $reportData[] = [
                'labourid'    => $lid,
                'FullName'    => $lab['FullName'],
                'location'    => $locName,
                'Gender'      => $lab['Gender'] ?? '',
                'attendance'  => $attByDate,
                'total_p'     => $totalP,
                'total_hd'    => $totalHD,
                'total_a'     => $totalA,
                'payable_days'=> $payableDays
            ];
        }

        echo json_encode(["status" => "success", "dates" => $dates, "data" => $reportData]);
        exit;
    } else if ($action == "getLabourAttendanceFixedDaysReport") {
        ob_start();
        header('Content-Type: application/json');

        try {
            $locationid = $_POST['locationid'] ?? 'all';
            $monthStr   = $_POST['month']      ?? date('Y-m'); // e.g. "2026-08"

            if (!preg_match('/^\d{4}-\d{2}$/', $monthStr)) {
                $monthStr = date('Y-m');
            }

            list($year, $month) = explode('-', $monthStr);
            $year  = intval($year);
            $month = intval($month);

            $startDate = sprintf('%04d-%02d-01', $year, $month);
            $daysInMonth = intval(date('t', strtotime($startDate)));
            $endDate   = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

            // Fetch location map with dual-case query fallback for Linux servers
            $locMap = [];
            $resLoc = $hrmconn->query("SELECT LocationId, LocationName, sno FROM hrm_AttendanceLocationMaster");
            if (!$resLoc) {
                $resLoc = $hrmconn->query("SELECT LocationId, LocationName, sno FROM hrm_AttendanceLocationMaster");
            }
            if ($resLoc) {
                while ($lRow = $resLoc->fetch_assoc()) {
                    $locMap[$lRow['LocationId']] = $lRow['LocationName'];
                    $locMap[$lRow['sno']] = $lRow['LocationName'];
                }
            }

            // Build robust location condition for labour master & attendance queries
            $locCondAtt = "";
            $locCondCalc = "";
            if (!empty($locationid) && $locationid !== 'all') {
                $escLoc = $hrmconn->real_escape_string($locationid);
                $matchingIds = [$escLoc];
                $qLocLookup = $hrmconn->query("SELECT LocationId, sno, LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = '$escLoc' OR sno = '$escLoc' OR LocationName = '$escLoc'");
                if (!$qLocLookup) {
                    $qLocLookup = $hrmconn->query("SELECT LocationId, sno, LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = '$escLoc' OR sno = '$escLoc' OR LocationName = '$escLoc'");
                }
                if ($qLocLookup && $qLocLookup->num_rows > 0) {
                    while ($lRow = $qLocLookup->fetch_assoc()) {
                        if (!empty($lRow['LocationId'])) $matchingIds[] = $hrmconn->real_escape_string($lRow['LocationId']);
                        if (!empty($lRow['sno'])) $matchingIds[] = $hrmconn->real_escape_string($lRow['sno']);
                        if (!empty($lRow['LocationName'])) $matchingIds[] = $hrmconn->real_escape_string($lRow['LocationName']);
                    }
                }
                $matchingIds = array_unique($matchingIds);
                $inClause = "'" . implode("','", $matchingIds) . "'";

                $locCondAtt  = " AND la.labourlocationid IN ($inClause) ";
                $locCondCalc = " AND ca.LocationId IN ($inClause) ";
            }

            // Query daily attendance records with working hours & checkin/checkout times
            $sqlAtt = "SELECT la.labourid, 
                              la.attendancedate,
                              COALESCE(NULLIF(la.checkinlocation_mn, ''), NULLIF(la.labourlocationid, ''), lm.locationid) as effective_location_id,
                              la.checkintime_mn, la.checkouttime_mn, la.workinghours_mn,
                              la.checkintime_dp, la.checkouttime_dp, la.workinghours_dp,
                              CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                              lm.locationid as master_location
                       FROM hrm_labourattendance la
                       LEFT JOIN hrm_LabourMaster lm ON la.labourid = lm.EmployeeID
                       WHERE la.attendancedate BETWEEN '$startDate' AND '$endDate'
                         AND (la.tedit IS NULL OR la.tedit = '')
                         {$locCondAtt}
                       ORDER BY lm.FirstName, lm.LastName";

            $resAtt = $hrmconn->query($sqlAtt);
            if (!$resAtt) {
                $sqlAttAlt = "SELECT la.labourid, 
                                  la.attendancedate,
                                  COALESCE(NULLIF(la.checkinlocation_mn, ''), NULLIF(la.labourlocationid, ''), lm.locationid) as effective_location_id,
                                  la.checkintime_mn, la.checkouttime_mn, la.workinghours_mn,
                                  la.checkintime_dp, la.checkouttime_dp, la.workinghours_dp,
                                  CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                                  lm.locationid as master_location
                           FROM hrm_LabourAttendance la
                           LEFT JOIN hrm_LabourMaster lm ON la.labourid = lm.EmployeeID
                           WHERE la.attendancedate BETWEEN '$startDate' AND '$endDate'
                             AND (la.tedit IS NULL OR la.tedit = '')
                             {$locCondAtt}
                           ORDER BY lm.FirstName, lm.LastName";
                $resAtt = $hrmconn->query($sqlAttAlt);
            }

            $labourMap = []; // [labourid] => ['id', 'name', 'master_loc', 'total_present', 'locations' => [locName => days]]

            if ($resAtt && $resAtt !== false && $resAtt->num_rows > 0) {
                while ($r = $resAtt->fetch_assoc()) {
                    $lid = $r['labourid'];
                    $name = trim(preg_replace('/\s+/', ' ', $r['FullName']));
                    if (empty($name)) $name = "Labour #$lid";

                    $locId = !empty($r['effective_location_id']) ? $r['effective_location_id'] : $r['master_location'];
                    $locName = $locMap[$locId] ?? ($locMap[$r['master_location']] ?? 'Main Location');

                    $hasIn = (!empty($r['checkintime_mn']) && $r['checkintime_mn'] !== '0000-00-00 00:00:00') ||
                             (!empty($r['checkintime_dp']) && $r['checkintime_dp'] !== '0000-00-00 00:00:00');

                    $hasOut = (!empty($r['checkouttime_mn']) && $r['checkouttime_mn'] !== '0000-00-00 00:00:00') ||
                              (!empty($r['checkouttime_dp']) && $r['checkouttime_dp'] !== '0000-00-00 00:00:00');

                    $hrs = 0.0;
                    if ($r['checkouttime_mn'] && $r['workinghours_mn']) {
                        $p = explode(':', $r['workinghours_mn']);
                        $h = isset($p[0]) ? intval($p[0]) : 0;
                        $m = isset($p[1]) ? intval($p[1]) : 0;
                        $s = isset($p[2]) ? intval($p[2]) : 0;
                        $hrs = max($hrs, $h + ($m / 60.0) + ($s / 3600.0));
                    }
                    if ($r['checkouttime_dp'] && $r['workinghours_dp']) {
                        $p = explode(':', $r['workinghours_dp']);
                        $h = isset($p[0]) ? intval($p[0]) : 0;
                        $m = isset($p[1]) ? intval($p[1]) : 0;
                        $s = isset($p[2]) ? intval($p[2]) : 0;
                        $hrs = max($hrs, $h + ($m / 60.0) + ($s / 3600.0));
                    }

                    $dayValue = 0.0;
                    if ($hrs >= 6.0) {
                        $dayValue = 1.0; // Full Present
                    } else if ($hrs > 2.0 && $hrs < 6.0) {
                        $dayValue = 0.5; // Half Day
                    } else {
                        $dayValue = 0.0; // Absent or Missing Checkout (MIS)
                    }

                    if (!isset($labourMap[$lid])) {
                        $labourMap[$lid] = [
                            'labourid' => $lid,
                            'name' => $name,
                            'total_present' => 0,
                            'locations' => []
                        ];
                    }

                    if ($dayValue > 0) {
                        $labourMap[$lid]['total_present'] += $dayValue;
                        $labourMap[$lid]['locations'][$locName] = ($labourMap[$lid]['locations'][$locName] ?? 0) + $dayValue;
                    }
                }
            } else {
                // Fallback to hrm_labourcalculateattendance if daily attendance is empty
                $sqlCalc = "SELECT ca.*, 
                                   CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                                   lm.locationid as master_location
                            FROM hrm_labourcalculateattendance ca
                            LEFT JOIN hrm_LabourMaster lm ON ca.EmployeeID = lm.EmployeeID
                            WHERE ca.AttendanceYear = $year AND ca.AttendanceMonth = $month {$locCondCalc}";
                $resCalc = $hrmconn->query($sqlCalc);
                if (!$resCalc) {
                    $sqlCalcAlt = "SELECT ca.*, 
                                       CONCAT(IFNULL(lm.FirstName,''), ' ', IFNULL(lm.MiddleName,''), ' ', IFNULL(lm.LastName,'')) AS FullName,
                                       lm.locationid as master_location
                                FROM hrm_labourcalculateattendance ca
                                LEFT JOIN hrm_LabourMaster lm ON ca.EmployeeID = lm.EmployeeID
                                WHERE ca.AttendanceYear = $year AND ca.AttendanceMonth = $month {$locCondCalc}";
                    $resCalc = $hrmconn->query($sqlCalcAlt);
                }
                if ($resCalc && $resCalc !== false) {
                    while ($r = $resCalc->fetch_assoc()) {
                        $lid = $r['EmployeeID'];
                        $name = trim(preg_replace('/\s+/', ' ', $r['FullName']));
                        if (empty($name)) $name = "Labour #$lid";

                        $locId = !empty($r['LocationId']) ? $r['LocationId'] : $r['master_location'];
                        $locName = $locMap[$locId] ?? ($locMap[$r['master_location']] ?? 'Main Location');
                        $days = (float)($r['PresentDays'] ?? 0);

                        if (!isset($labourMap[$lid])) {
                            $labourMap[$lid] = [
                                'labourid' => $lid,
                                'name' => $name,
                                'total_present' => 0,
                                'locations' => []
                            ];
                        }

                        $labourMap[$lid]['total_present'] += $days;
                        $labourMap[$lid]['locations'][$locName] = ($labourMap[$lid]['locations'][$locName] ?? 0) + $days;
                    }
                }
            }

            // Compute overall summary statistics
            $totalLabours = count($labourMap);
            $totalPresentSum = 0;
            $countLess26 = 0;
            $count26 = 0;
            $count27_29 = 0;
            $count30_31 = 0;

            $locationSummary = [];
            $labourListFinal = [];

            foreach ($labourMap as $lid => $item) {
                $totP = $item['total_present'];
                $totalPresentSum += $totP;

                // Fixed Days Categories
                if ($totP < 26) {
                    $countLess26++;
                    $cat = 'less_26';
                } else if ($totP == 26) {
                    $count26++;
                    $cat = 'exact_26';
                } else if ($totP >= 27 && $totP <= 29) {
                    $count27_29++;
                    $cat = 'range_27_29';
                } else {
                    $count30_31++;
                    $cat = 'range_30_31';
                }

                $absentDays = max(0, $daysInMonth - $totP);
                $attPercentage = ($daysInMonth > 0) ? round(($totP / $daysInMonth) * 100, 2) : 0;

                // Build location badges array
                $locBadges = [];
                foreach ($item['locations'] as $lName => $lDays) {
                    $locBadges[] = [
                        'location_name' => $lName,
                        'days' => $lDays
                    ];

                    // Location-wise summary aggregation
                    if (!isset($locationSummary[$lName])) {
                        $locationSummary[$lName] = [
                            'location_name' => $lName,
                            'total_labours' => 0,
                            'present_sum' => 0,
                            'less_26' => 0,
                            'exact_26' => 0,
                            'range_27_29' => 0,
                            'range_30_31' => 0
                        ];
                    }
                    $locationSummary[$lName]['total_labours']++;
                    $locationSummary[$lName]['present_sum'] += $lDays;
                    if ($totP < 26) $locationSummary[$lName]['less_26']++;
                    else if ($totP == 26) $locationSummary[$lName]['exact_26']++;
                    else if ($totP >= 27 && $totP <= 29) $locationSummary[$lName]['range_27_29']++;
                    else $locationSummary[$lName]['range_30_31']++;
                }

                $labourListFinal[] = [
                    'labourid'        => $lid,
                    'name'            => $item['name'],
                    'locations'       => $locBadges,
                    'total_days'      => $daysInMonth,
                    'total_present'   => $totP,
                    'total_absent'    => $absentDays,
                    'att_percentage'  => $attPercentage,
                    'category'        => $cat
                ];
            }

            $avgAttendance = ($totalLabours > 0) ? round($totalPresentSum / $totalLabours, 1) : 0;

            // Finalize location summary list with computed avg
            $locSummaryFinal = [];
            foreach ($locationSummary as $locKey => $lData) {
                $lData['avg_attendance'] = ($lData['total_labours'] > 0) ? round($lData['present_sum'] / $lData['total_labours'], 1) : 0;
                $locSummaryFinal[] = $lData;
            }

            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => 'success',
                'month'  => $monthStr,
                'days_in_month' => $daysInMonth,
                'kpi'    => [
                    'total_labour'   => $totalLabours,
                    'avg_attendance' => $avgAttendance,
                    'less_26'        => $countLess26,
                    'exact_26'       => $count26,
                    'range_27_29'    => $count27_29,
                    'range_30_31'    => $count30_31
                ],
                'location_summary' => $locSummaryFinal,
                'labours'          => $labourListFinal
            ]);
            exit;
        } catch (Exception $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => 'error',
                'message' => 'Database error: ' . $e->getMessage()
            ]);
            exit;
        } catch (Error $e) {
            if (ob_get_length()) ob_clean();
            echo json_encode([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ]);
            exit;
        }
    } else if ($action == "getallLocationList") {
        $locList = [];
        $resLoc = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster ORDER BY LocationName ASC");
        if ($resLoc) {
            while ($lRow = $resLoc->fetch_assoc()) {
                $locList[] = [
                    'LocationId'   => $lRow['LocationId'],
                    'LocationName' => $lRow['LocationName']
                ];
            }
        }
        echo json_encode(["status" => "success", "data" => $locList]);
        exit;
    } else if ($action == "GetLabourBankVerificationData") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $companyid = $_REQUEST['companyid'] ?? '';
        $subcompanyid = $_REQUEST['subcompanyid'] ?? '';
        $projectid = $_REQUEST['projectid'] ?? '';
        $statusFilter = trim($_REQUEST['status'] ?? 'all'); // 'all', 'pending', 'verified', 'rejected'
        $locationFilter = trim($_REQUEST['locationid'] ?? '');
        $docFilter = trim($_REQUEST['docstatus'] ?? 'all'); // 'all', 'with_doc', 'no_doc'
        $searchTerm = trim($_REQUEST['search'] ?? '');

        // 1. KPI Counts
        $summarySql = "SELECT 
            COUNT(*) as total_banks,
            SUM(CASE WHEN b.verifystatus IS NULL OR b.verifystatus = '' OR LOWER(b.verifystatus) = 'pending' THEN 1 ELSE 0 END) as pending_banks,
            SUM(CASE WHEN LOWER(b.verifystatus) = 'verified' THEN 1 ELSE 0 END) as verified_banks,
            SUM(CASE WHEN LOWER(b.verifystatus) = 'rejected' THEN 1 ELSE 0 END) as rejected_banks,
            SUM(CASE WHEN b.cancelchequepath IS NOT NULL AND b.cancelchequepath != '' THEN 1 ELSE 0 END) as with_doc_banks,
            SUM(CASE WHEN b.cancelchequepath IS NULL OR b.cancelchequepath = '' THEN 1 ELSE 0 END) as no_doc_banks
        FROM hrm_labourbankdetails b
        WHERE (b.tedit IS NULL OR b.tedit = '')";
        
        $summaryParams = [];
        $summaryTypes = "";
        if (!empty($projectid)) {
            $summarySql .= " AND (b.projectid = ? OR b.projectid = '' OR b.projectid IS NULL)";
            $summaryParams[] = $projectid;
            $summaryTypes .= "s";
        }
        
        $summaryStmt = $hrmconn->prepare($summarySql);
        if ($summaryParams) {
            $summaryStmt->bind_param($summaryTypes, ...$summaryParams);
        }
        $summaryStmt->execute();
        $summaryRes = $summaryStmt->get_result()->fetch_assoc();
        $summaryStmt->close();

        // 2. Fetch Detailed List
        $sql = "SELECT 
            b.id as bank_id,
            b.companyid as bank_companyid,
            b.subcompanyid as bank_subcompanyid,
            b.projectid as bank_projectid,
            b.labourid,
            b.bankname,
            b.accountnumber,
            b.ifsccode,
            b.currencycode,
            b.cancelchequepath,
            b.verifystatus,
            b.verifyremark,
            b.verifyby,
            b.verifyat,
            b.tedit,
            m.FirstName,
            m.MiddleName,
            m.LastName,
            m.MobileNo,
            m.WorkLocation,
            m.locationid,
            m.profilepath,
            m.Status as LabourStatus,
            loc.LocationName
        FROM hrm_labourbankdetails b
        LEFT JOIN hrm_LabourMaster m ON (b.labourid = m.EmployeeID OR b.labourid = CAST(m.seriesid AS CHAR))
        LEFT JOIN hrm_AttendanceLocationMaster loc ON (m.locationid = loc.LocationId OR m.WorkLocation = loc.LocationName)
        WHERE (b.tedit IS NULL OR b.tedit = '')";

        $params = [];
        $types = "";

        if (!empty($projectid)) {
            $sql .= " AND (b.projectid = ? OR b.projectid = '' OR b.projectid IS NULL)";
            $params[] = $projectid;
            $types .= "s";
        }

        // Status filter
        if ($statusFilter === 'pending') {
            $sql .= " AND (b.verifystatus IS NULL OR b.verifystatus = '' OR LOWER(b.verifystatus) = 'pending')";
        } else if ($statusFilter === 'verified') {
            $sql .= " AND LOWER(b.verifystatus) = 'verified'";
        } else if ($statusFilter === 'rejected') {
            $sql .= " AND LOWER(b.verifystatus) = 'rejected'";
        }

        // Location filter
        if (!empty($locationFilter) && $locationFilter !== 'all') {
            $sql .= " AND (m.locationid = ? OR loc.LocationId = ?)";
            $params[] = $locationFilter;
            $params[] = $locationFilter;
            $types .= "ss";
        }

        // Doc filter
        if ($docFilter === 'with_doc') {
            $sql .= " AND (b.cancelchequepath IS NOT NULL AND b.cancelchequepath != '')";
        } else if ($docFilter === 'no_doc') {
            $sql .= " AND (b.cancelchequepath IS NULL OR b.cancelchequepath = '')";
        }

        // Search filter
        if (!empty($searchTerm)) {
            $searchLike = "%" . $searchTerm . "%";
            $sql .= " AND (b.labourid LIKE ? OR b.bankname LIKE ? OR b.accountnumber LIKE ? OR b.ifsccode LIKE ? OR m.FirstName LIKE ? OR m.LastName LIKE ? OR m.MobileNo LIKE ?)";
            for ($i = 0; $i < 7; $i++) {
                $params[] = $searchLike;
                $types .= "s";
            }
        }

        $sql .= " ORDER BY b.id DESC";

        $stmt = $hrmconn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $vStatus = trim($row['verifystatus'] ?? '');
            if (empty($vStatus)) {
                $vStatus = 'Pending';
            }
            $row['normalized_status'] = ucfirst(strtolower($vStatus));
            $row['fullname'] = trim(($row['FirstName'] ?? '') . ' ' . ($row['MiddleName'] ?? '') . ' ' . ($row['LastName'] ?? ''));
            if (empty($row['fullname'])) {
                $row['fullname'] = 'Labour (' . $row['labourid'] . ')';
            }
            $row['location_display'] = !empty($row['LocationName']) ? $row['LocationName'] : (!empty($row['WorkLocation']) ? $row['WorkLocation'] : 'N/A');
            $data[] = $row;
        }
        $stmt->close();

        echo json_encode([
            "status" => "success",
            "summary" => [
                "total" => intval($summaryRes['total_banks'] ?? 0),
                "pending" => intval($summaryRes['pending_banks'] ?? 0),
                "verified" => intval($summaryRes['verified_banks'] ?? 0),
                "rejected" => intval($summaryRes['rejected_banks'] ?? 0),
                "with_doc" => intval($summaryRes['with_doc_banks'] ?? 0),
                "no_doc" => intval($summaryRes['no_doc_banks'] ?? 0),
            ],
            "data" => $data
        ]);
        exit;
    } else if ($action == "UpdateLabourBankVerifyStatus") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $ids = $_POST['ids'] ?? $_POST['id'] ?? null;
        if (empty($ids) && !empty($_POST['labourid'])) {
            $lid = trim($_POST['labourid']);
            $lidEsc = $hrmconn->real_escape_string($lid);
            $rL = $hrmconn->query("SELECT id FROM hrm_labourbankdetails WHERE (labourid='$lidEsc' OR labourid IN (SELECT id FROM hrm_labour WHERE EmployeeID='$lidEsc' OR id='$lidEsc')) AND (tedit IS NULL OR tedit='') ORDER BY id DESC LIMIT 1");
            if ($rL && $rowL = $rL->fetch_assoc()) {
                $ids = [$rowL['id']];
            }
        }
        $newStatus = trim($_POST['verifystatus'] ?? 'Verified');
        $verifyBy = trim($_POST['verifyby'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'Admin');
        $verifyRemark = trim($_POST['verifyremark'] ?? $_POST['reason'] ?? '');
        $now = date('Y-m-d H:i:s');

        if (empty($ids)) {
            echo json_encode(["status" => "error", "message" => "No bank record selected."]);
            exit;
        }

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        $cleanIds = array_filter(array_map('intval', $ids));

        if (empty($cleanIds)) {
            echo json_encode(["status" => "error", "message" => "Invalid bank record IDs."]);
            exit;
        }

        $inPlaceholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $types = "ssss" . str_repeat('i', count($cleanIds));

        if (strtolower($newStatus) === 'un-verify' || strtolower($newStatus) === 'unverify' || strtolower($newStatus) === 'pending') {
            $verifyStatusVal = 'Pending';
        } else if (strtolower($newStatus) === 'rejected') {
            $verifyStatusVal = 'Rejected';
        } else {
            $verifyStatusVal = 'Verified';
        }
        $stmt = $hrmconn->prepare("UPDATE hrm_labourbankdetails SET verifystatus = ?, verifyremark = ?, verifyby = ?, verifyat = ? WHERE id IN ($inPlaceholders) AND (tedit IS NULL OR tedit = '')");
        if ($stmt) {
            $params = array_merge([$verifyStatusVal, $verifyRemark, $verifyBy, $now], $cleanIds);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            echo json_encode([
                "status" => "success",
                "message" => count($cleanIds) > 1 ? "$affected bank records updated to $verifyStatusVal successfully." : "Bank verification status updated to $verifyStatusVal.",
                "updated_count" => $affected
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . $hrmconn->error]);
        }
        exit;
    } else if ($action == "SaveLabourBankDetailsFromVerification") {
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $bankId = intval($_POST['bank_id'] ?? 0);
        $employeeID = trim($_POST['labourid'] ?? '');
        $bankName = trim($_POST['bankname'] ?? '');
        $accountNumber = trim($_POST['accountnumber'] ?? '');
        $ifscCode = trim($_POST['ifsccode'] ?? '');
        $currencyCode = trim($_POST['currencycode'] ?? 'INR');
        $verifyStatus = trim($_POST['verifystatus'] ?? 'Verified');
        $verifyRemark = trim($_POST['verifyremark'] ?? '');
        $verifyBy = trim($_POST['verifyby'] ?? $_SESSION['loginname'] ?? $_SESSION['loginid'] ?? 'Admin');
        $companyid = $_POST['companyid'] ?? '';
        $subcompanyid = $_POST['subcompanyid'] ?? '';
        $projectid = $_POST['projectid'] ?? '';
        $now = date('Y-m-d H:i:s');

        if (empty($bankName) || empty($accountNumber) || empty($ifscCode)) {
            echo json_encode(["status" => "error", "message" => "Bank Name, Account Number, and IFSC Code are required."]);
            exit;
        }

        $bankDocPath = '';
        // Fetch existing doc path
        if ($bankId > 0) {
            $stmtEx = $hrmconn->prepare("SELECT cancelchequepath FROM hrm_labourbankdetails WHERE id = ? LIMIT 1");
            if ($stmtEx) {
                $stmtEx->bind_param("i", $bankId);
                $stmtEx->execute();
                $resEx = $stmtEx->get_result()->fetch_assoc();
                if ($resEx && !empty($resEx['cancelchequepath'])) {
                    $bankDocPath = $resEx['cancelchequepath'];
                }
                $stmtEx->close();
            }
        }

        // Handle file upload if provided
        if (isset($_FILES['bankDoc']) && $_FILES['bankDoc']['error'] == UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['bankDoc']['tmp_name'];
            $fileName = $_FILES['bankDoc']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'webp'];
            if (!in_array($fileExtension, $allowedExtensions)) {
                echo json_encode(["status" => "error", "message" => "Invalid file format. Allowed: JPG, PNG, PDF, WEBP."]);
                exit;
            }

            $newFileName = 'bankdoc_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $employeeID) . '_' . time() . '.' . $fileExtension;
            $uploadFileDir = '../../uploads/labour_docs/';
            if (!is_dir($uploadFileDir)) {
                @mkdir($uploadFileDir, 0777, true);
            }
            $dest_path = $uploadFileDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $bankDocPath = 'uploads/labour_docs/' . $newFileName;
            }
        }

        $verifyAt = ($verifyStatus === 'Verified' || $verifyStatus === 'Rejected') ? $now : null;

        if ($bankId > 0) {
            $stmtUp = $hrmconn->prepare("UPDATE hrm_labourbankdetails SET bankname = ?, accountnumber = ?, ifsccode = ?, currencycode = ?, cancelchequepath = ?, verifystatus = ?, verifyremark = ?, verifyby = ?, verifyat = ? WHERE id = ?");
            if ($stmtUp) {
                $stmtUp->bind_param("sssssssssi", $bankName, $accountNumber, $ifscCode, $currencyCode, $bankDocPath, $verifyStatus, $verifyRemark, $verifyBy, $verifyAt, $bankId);
                $stmtUp->execute();
                $stmtUp->close();
                echo json_encode([
                    "status" => "success",
                    "message" => "Bank details and verification status updated successfully.",
                    "bank_id" => $bankId,
                    "docPath" => $bankDocPath
                ]);
                exit;
            } else {
                echo json_encode(["status" => "error", "message" => "Database error: " . $hrmconn->error]);
                exit;
            }
        } else {
            insertOrUpdateBankDetails($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $bankName, $accountNumber, $ifscCode, '', '', '', $currencyCode, $bankDocPath, $verifyStatus, $verifyBy, $verifyAt, $verifyRemark);
            $empEsc = $hrmconn->real_escape_string($employeeID);
            $stmtLast = $hrmconn->query("SELECT id FROM hrm_labourbankdetails WHERE (labourid='$empEsc' OR labourid IN (SELECT id FROM hrm_LabourMaster WHERE EmployeeID='$empEsc')) AND (tedit IS NULL OR tedit='') ORDER BY id DESC LIMIT 1");
            $newBankId = ($stmtLast && $rowLast = $stmtLast->fetch_assoc()) ? intval($rowLast['id']) : 0;
            echo json_encode([
                "status" => "success",
                "message" => "Bank details saved and verified successfully.",
                "bank_id" => $newBankId,
                "docPath" => $bankDocPath
            ]);
            exit;
        }
    }

function generateLabourIDCard($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, $distpath)
{
    $sql = "SELECT lm.*, IFNULL(lpm.PerDayPayment, 0) as PerDayPayment 
            FROM hrm_LabourMaster lm 
            LEFT JOIN hrm_LabourPaymentMaster lpm ON lm.EmployeeID = lpm.EmployeeID AND lm.projectid = lpm.projectid
            WHERE lm.EmployeeID = ? AND lm.projectid = ?";
    $stmt = $hrmconn->prepare($sql);
    $stmt->bind_param("ss", $employeeID, $projectid);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$employee) return false;

    // 1.0 fetch locationo name
    
    $stmt = $hrmconn->prepare("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE LocationId = ?");
    $stmt->bind_param("s", $employee['locationid']);
    $stmt->execute();
    $result = $stmt->get_result();

    $LocationName = "";
    if ($row = $result->fetch_assoc()) {
        $LocationName = $row['LocationName'];
    }

    if (empty($LocationName) && !empty($employee['EmployeeID'])) {
        // Fallback: parse prefix from EmployeeID (e.g. "KFM-11076" -> "KFM")
        $parts = explode('-', $employee['EmployeeID']);
        $prefix = trim($parts[0] ?? '');
        if (!empty($prefix)) {
            $stmtFallback = $hrmconn->prepare("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE shortname = ? LIMIT 1");
            $stmtFallback->bind_param("s", $prefix);
            $stmtFallback->execute();
            $resFallback = $stmtFallback->get_result();
            if ($rowFallback = $resFallback->fetch_assoc()) {
                $LocationName = $rowFallback['LocationName'];
            }
            $stmtFallback->close();
        }
    }


    // 2. Fetch Photo
    $sqlPhoto = "SELECT DocumentPath FROM hrm_labourdocuments WHERE EmployeeID = ? AND DocumentType = 'ProfilePhoto' LIMIT 1";
    $stmtPhoto = $hrmconn->prepare($sqlPhoto);
    $stmtPhoto->bind_param("s", $employeeID);
    $stmtPhoto->execute();
    $photoResult = $stmtPhoto->get_result()->fetch_assoc();
    $stmtPhoto->close();

    $photoPathRaw = $photoResult ? $photoResult['DocumentPath'] : '';
    $photoPath = '';
    if ($photoPathRaw) {
        $normalizedPath = str_replace('../', '', $photoPathRaw);
        $fullPath = __DIR__ . '/../' . $normalizedPath;
        $photoPath = realpath($fullPath);
    }

    // 3. Fetch Project Name
    global $conn; // Use master connection for project details
    $projectName = "WORKFORCE";
    $sqlPrj = "SELECT projectname FROM tbl_project_master WHERE companyid = ? AND subcompanyid = ? AND projectid = ? LIMIT 1";
    if ($stmtPrj = $conn->prepare($sqlPrj)) {
        $stmtPrj->bind_param("sss", $companyid, $subcompanyid, $projectid);
        $stmtPrj->execute();
        $prjResult = $stmtPrj->get_result()->fetch_assoc();
        if ($prjResult) {
            $projectName = strtoupper($prjResult['projectname']);
        }
        $stmtPrj->close();
    }

    // 4. Setup PDF (Standard A4 for guaranteed visibility)
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(false);

    $pdf->AddPage();
    
    // Starting coordinates
    $startX = 10;
    $startY = 10;
    $cardW = 86;
    $cardH = 54;

    // --- FRONT SIDE (Top Card) ---
    // Background
    $pdf->SetFillColor(248, 250, 252);
    $pdf->Rect($startX, $startY, $cardW, $cardH, 'F');
    $pdf->Rect($startX, $startY, $cardW, $cardH, 'D'); // Border
    
    // Header Stripe - Green Gradient (Deep forest green to organic plant green)
    $colorStart = array(16, 92, 53); // #105c35
    $colorEnd = array(40, 167, 69);   // #28a745
    $pdf->LinearGradient($startX, $startY, $cardW, 12, $colorStart, $colorEnd);
    
    // Draw plant/leaf motif on the right side of the header
    $pdf->SetAlpha(0.20); // Subtle watermark
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetDrawColor(255, 255, 255);
    
    $leafCenterX = $startX + $cardW - 12;
    $leafCenterY = $startY + 6;
    
    // Draw a branch stem
    $pdf->SetLineWidth(0.3);
    $pdf->Line($leafCenterX - 8, $leafCenterY + 3, $leafCenterX + 5, $leafCenterY - 3);
    
    // Draw Leaf 1 (Top right)
    $lx = $leafCenterX + 2;
    $ly = $leafCenterY - 2;
    $pdf->Polygon(array(
        $lx, $ly,
        $lx + 1.5, $ly - 1,
        $lx + 3, $ly - 3,
        $lx + 2, $ly - 4.5,
        $lx, $ly - 5,
        $lx - 1, $ly - 3.5,
        $lx - 1, $ly - 2
    ), 'F');
    
    // Draw Leaf 2 (Middle left)
    $lx2 = $leafCenterX - 3;
    $ly2 = $leafCenterY;
    $pdf->Polygon(array(
        $lx2, $ly2,
        $lx2 - 2, $ly2 - 1.5,
        $lx2 - 3.5, $ly2 - 3.5,
        $lx2 - 2.5, $ly2 - 4.5,
        $lx2 - 0.5, $ly2 - 4,
        $lx2 + 0.5, $ly2 - 2.5,
        $lx2, $ly2 - 1
    ), 'F');

    // Draw Leaf 3 (Far right)
    $lx3 = $leafCenterX + 6;
    $ly3 = $leafCenterY - 3;
    $pdf->Polygon(array(
        $lx3, $ly3,
        $lx3 + 2, $ly3 - 0.5,
        $lx3 + 4, $ly3 - 2,
        $lx3 + 3, $ly3 - 3,
        $lx3 + 1, $ly3 - 2.5,
        $lx3, $ly3 - 1.5
    ), 'F');
    
    $pdf->SetAlpha(1.0); // Restore opacity
    
    // Add the Logo of Karjee Farm
    $logoPath = realpath(__DIR__ . '/../../img/logo/logo.png');
    if ($logoPath && file_exists($logoPath)) {
        // Draw a small white background badge for the logo to make it stand out
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($startX + 3, $startY + 1.5, 9, 9, 'F');
        $pdf->Image($logoPath, $startX + 3.5, $startY + 2, 8, 8, 'PNG');
    }
    
    // Header Text
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetXY($startX + 14, $startY + 2);
    $pdf->Cell(55, 5, $projectName, 0, 0, 'L');
    $pdf->SetFont('helvetica', '', 5.5);
    $pdf->SetXY($startX + 14, $startY + 7);
    $pdf->Cell(55, 3, 'WORKFORCE IDENTITY DOCUMENT', 0, 0, 'L');

    // Photo
    $photoX = $startX + 5;
    $photoY = $startY + 16;
    if ($photoPath && file_exists($photoPath)) {
        $pdf->Image($photoPath, $photoX, $photoY, 22, 28, '', '', '', false, 300, '', false, false, 0);
        $pdf->Rect($photoX, $photoY, 22, 28, 'D');
    } else {
        $pdf->SetFillColor(220, 220, 220);
        $pdf->Rect($photoX, $photoY, 22, 28, 'F');
        $pdf->SetTextColor(100, 100, 100);
        $pdf->SetFont('helvetica', 'B', 6);
        $pdf->SetXY($photoX, $photoY + 12);
        $pdf->Cell(22, 5, 'NO PHOTO', 0, 0, 'C');
    }

    // Details Section
    $detailX = $startX + 32;
    $detailY = $startY + 16;
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($detailX, $detailY);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(50, 5, strtoupper($employee['FirstName'] . ' ' . $employee['LastName']), 0, 1);

    $curY = $detailY + 6;
    $details = [
        'ID' => $employee['EmployeeID'],
        'MOBILE' => $employee['MobileNo'],
        'FATHER NAME' => $employee['FatherName'],
        'LOCATION' => substr($LocationName ?? '', 0, 20),
        'AADHAR NO' => $employee['AadharNumber']
    ];

    foreach ($details as $label => $val) {
        $pdf->SetXY($detailX, $curY);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->Cell(22, 4, $label . ':', 0, 0);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell(30, 4, $val ? $val : 'N/A', 0, 1);
        $curY += 5.0;
    }

    // Footer Stripe
    $pdf->LinearGradient($startX, $startY + $cardH - 2, $cardW, 2, $colorStart, $colorEnd);

    // --- FOLD / CUT LINE ---
    $pdf->SetLineStyle(array('width' => 0.1, 'dash' => '2,2', 'color' => array(200, 200, 200)));
    $pdf->Line($startX - 5, $startY + $cardH, $startX + $cardW + 5, $startY + $cardH);
    $pdf->SetLineStyle(array('width' => 0.1, 'dash' => 0, 'color' => array(0, 0, 0)));

    // --- BACK SIDE (Bottom Card) ---
    $backY = $startY + $cardH;
    // Background
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Rect($startX, $backY, $cardW, $cardH, 'F');
    $pdf->Rect($startX, $backY, $cardW, $cardH, 'D'); // Border
    
    // QR Code for scanning (generated locally via TCPDF to avoid network dependency)
    $qrData = $employee['EmployeeID'];
    try {
        $style = array(
            'border' => false,
            'vpadding' => 'auto',
            'hpadding' => 'auto',
            'fgcolor' => array(0,0,0),
            'bgcolor' => false, //transparent
            'position' => '' // Empty string so it uses absolute coordinates passed
        );
        $pdf->write2DBarcode($qrData, 'QRCODE,M', $startX + 26.5, $backY + 3, 33, 33, $style, 'N');
    } catch (Throwable $e_qr) {
        $pdf->SetXY($startX + 26.5, $backY + 15);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(255, 0, 0);
        $pdf->Cell(33, 10, 'QR PENDING', 1, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);
    }
    
    $pdf->SetTextColor(200, 200, 200);
    $pdf->SetFont('helvetica', 'B', 20);
    $pdf->SetXY($startX, $backY + 15);
    $pdf->Cell($cardW, 10, '', 0, 0, 'C');
    
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY($startX, $backY + 37.2);
    $pdf->Cell($cardW, 3.5, 'ID: ' . $employee['EmployeeID'], 0, 1, 'C');

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY($startX, $backY + 40.7);
    $pdf->Cell($cardW, 4, 'SCAN FOR ATTENDANCE', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', '', 6.2);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetXY($startX, $backY + 44.7);
    $pdf->Cell($cardW, 3.2, 'This is a digitally generated identity card.', 0, 1, 'C');

    $pdf->SetFont('helvetica', 'B', 6.8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY($startX, $backY + 47.9);
    $pdf->Cell($cardW, 3.2, 'Valid for ' . strtoupper($LocationName), 0, 1, 'C');

    // Save File
    $rootPath = realpath(__DIR__ . '/../');
    $fullUploadDir = $rootPath . DIRECTORY_SEPARATOR . 'attachementfile' . DIRECTORY_SEPARATOR . $companyid . DIRECTORY_SEPARATOR . $subcompanyid . DIRECTORY_SEPARATOR . $projectid . DIRECTORY_SEPARATOR . 'labour' . DIRECTORY_SEPARATOR . 'id_cards' . DIRECTORY_SEPARATOR;
    
    if (!is_dir($fullUploadDir)) {
        @mkdir($fullUploadDir, 0777, true);
    }
    
    $fileName = str_replace('-', '_', $employeeID) . "_ID_V4.pdf";
    $outputFile = $fullUploadDir . $fileName;
    if (file_exists($outputFile)) {
        @unlink($outputFile);
    }
    $pdf->Output($outputFile, 'F');
    // Record in Documents
    $dbPath = "attachementfile/" . $companyid . "/" . $subcompanyid . "/" . $projectid . "/labour/id_cards/" . $fileName;
    insertOrUpdateLabourDocument($hrmconn, $companyid, $subcompanyid, $projectid, $employeeID, 'IDCard', $dbPath);

    return $dbPath . "?t=" . time();
}
?>
