<?php
@ini_set('display_errors', 0);
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
if ($action == "labourworkingloactiondetails"){
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    $locationlist = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, $shortby,$shortbyvalue);
    echo json_encode($locationlist);
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
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    // 1. Resolve labourid (varchar) and registered locationid
    $stmtLid = $hrmconn->prepare("SELECT EmployeeID AS lid, locationid FROM hrm_LabourMaster WHERE EmployeeID = ? AND projectid = ? LIMIT 1");
    $stmtLid->bind_param("ss", $employeeID, $projectid);
    $stmtLid->execute();
    $rowLid = $stmtLid->get_result()->fetch_assoc();
    $stmtLid->close();

    if (!$rowLid) {
        echo json_encode(['status' => 'error', 'message' => 'Labour not found.']);
        exit;
    }
    $labourid = $rowLid['lid'];
    $registeredLocationId = $rowLid['locationid'] ?? '';

    // Use registered location ID instead of scanned location ID
    $labourlocationid = !empty($registeredLocationId) ? $registeredLocationId : $locationId;

    // 2. Fetch today's attendance row
    $stmtFetch = $hrmconn->prepare(
        "SELECT attendanceid, checkintime_{$sfx}, checkouttime_{$sfx}
         FROM hrm_labourattendance
         WHERE (tedit IS NULL OR tedit = '') AND labourid = ? AND projectid = ? AND attendancedate = ?
         LIMIT 1"
    );
    $stmtFetch->bind_param("sss", $labourid, $projectid, $todayDate);
    $stmtFetch->execute();
    $row = $stmtFetch->get_result()->fetch_assoc();
    $stmtFetch->close();

    $attendanceType = ''; // 'checkin' or 'checkout'

    if (!$row) {
        // Check if 1:00 PM (13:00) or later for loginroleid > 2
        if (intval(date('G')) >= 13 && $loginroleid > 2) {
            echo json_encode([
                'status' => 'error',
                'message' => 'दोपहर 1:00 बजे के बाद चेक-इन (Mark-in) करने की अनुमति नहीं है। केवल चेक-आउट (Mark-out) दर्ज किया जा सकता है।'
            ]);
            exit;
        }
        // ── 3. No row today → INSERT with check-in ──────────────────
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
            echo json_encode(['status' => 'success', 'type' => 'checkin', 'time' => $nowDT, 'message' => 'Check-In recorded successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'DB error on check-in: ' . $hrmconn->error]);
        }

    } elseif (empty($row["checkintime_{$sfx}"])) {
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
            ? ['status' => 'success', 'type' => 'checkin', 'time' => $nowDT, 'message' => 'Check-In recorded successfully.']
            : ['status' => 'error', 'message' => 'DB error: ' . $hrmconn->error]);

    } elseif (empty($row["checkouttime_{$sfx}"])) {
        // ── 5. Check-in done → record check-out + calc working hours ─
        $attendanceType = 'checkout';

        // Calculate working hours
        $dtIn  = new DateTime($row["checkintime_{$sfx}"]);
        $dtOut = new DateTime($nowDT);
        $diff  = $dtIn->diff($dtOut);
        $wh    = sprintf('%02d:%02d:%02d', ($diff->days * 24) + $diff->h, $diff->i, $diff->s);

        $sql = "UPDATE hrm_labourattendance SET
                    checkouttime_{$sfx}      = ?,
                    sourceout_{$sfx}         = ?,
                    checkoutscannerid_{$sfx} = ?,
                    checkoutlocation_{$sfx}  = ?,
                    checkoutby_{$sfx}        = ?,
                    workinghours_{$sfx}      = ?,
                    updated_at = ?, updated_by = ?
                WHERE attendanceid = ?";
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param(
            "ssssssssi",
            $nowDT, $source, $scannerName, $locationId,
            $scannedBy, $wh,
            $todayDate, $scannedBy,
            $row['attendanceid']
        );
        $ok = $stmt->execute();
        $stmt->close();

        echo json_encode($ok
            ? ['status' => 'success', 'type' => 'checkout', 'time' => $nowDT, 'workinghours' => $wh, 'message' => 'Check-Out recorded. Working hours: ' . $wh]
            : ['status' => 'error', 'message' => 'DB error: ' . $hrmconn->error]);

    } else {
        // 6. Both check-in & checkout already done
        $checkin  = $row["checkintime_{$sfx}"];
        $checkout = $row["checkouttime_{$sfx}"];
        echo json_encode([
            'status'   => 'already_complete',
            'type'     => 'already_complete',
            'checkin'  => $checkin,
            'checkout' => $checkout,
            'message'  => 'Attendance already complete for today.'
        ]);
    }
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
