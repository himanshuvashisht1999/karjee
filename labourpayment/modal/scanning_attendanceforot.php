<?php
@ini_set('display_errors', 0);
error_reporting(0);
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once(__DIR__ . '/../../connection.php');
require_once(__DIR__ . '/../functions/admin_functionmaster.php');
require_once(__DIR__ . '/../functions/comanphp_functions.php');
require_once(__DIR__ . '/../functions/hrm_FunctionMaster.php');
require_once(__DIR__ . '/../functions/hrm_insertupdatesalary_functions.php');

function sendJsonOutput($data) {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

try {
    // Ensure table `hrm_labourattendance_forot` exists as copy of `hrm_labourattendance` if not created yet
    $chkTable = @$hrmconn->query("SHOW TABLES LIKE 'hrm_labourattendance_forot'");
    if ($chkTable && $chkTable->num_rows == 0) {
        @$hrmconn->query("CREATE TABLE `hrm_labourattendance_forot` LIKE `hrm_labourattendance`");
    }

    // Ensure column `purposeofot` exists in `hrm_labourattendance_forot`
    $chkCol = @$hrmconn->query("SHOW COLUMNS FROM `hrm_labourattendance_forot` LIKE 'purposeofot'");
    if ($chkCol && $chkCol->num_rows == 0) {
        @$hrmconn->query("ALTER TABLE `hrm_labourattendance_forot` ADD COLUMN `purposeofot` TEXT NULL AFTER `remark_mn`");
    }

    // Ensure table `hrm_labourotpurpose_master` exists for managing OT purpose quick-select options
    $chkOtMaster = @$hrmconn->query("SHOW TABLES LIKE 'hrm_labourotpurpose_master'");
    if ($chkOtMaster && $chkOtMaster->num_rows == 0) {
        @$hrmconn->query("CREATE TABLE IF NOT EXISTS `hrm_labourotpurpose_master` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `companyid` VARCHAR(50) DEFAULT NULL,
      `purpose_name` VARCHAR(255) NOT NULL,
      `status` VARCHAR(20) DEFAULT 'Active',
      `created_by` VARCHAR(20) DEFAULT 'web',
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      UNIQUE KEY `uk_purpose` (`purpose_name`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $defaults = ['Extra Shift', 'Maintenance Work', 'Crop Harvesting', 'Packing & Sorting', 'Irrigation & Spray', 'General Overtime'];
        foreach ($defaults as $d) {
            $stmtD = $hrmconn->prepare("INSERT IGNORE INTO hrm_labourotpurpose_master (purpose_name, created_by) VALUES (?, 'web')");
            if ($stmtD) {
                $stmtD->bind_param('s', $d);
                $stmtD->execute();
                $stmtD->close();
            }
        }
    }
} catch (Throwable $e) {}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action == "getOTPurposes") {
    $companyid = $_POST['companyid'] ?? '';

    $purposes = [];
    try {
        $res = @$hrmconn->query("SELECT id, purpose_name FROM hrm_labourotpurpose_master WHERE status = 'Active' ORDER BY id ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $purposes[] = $r;
            }
        }
    } catch (Throwable $e) {}
    sendJsonOutput(['status' => 'success', 'data' => $purposes]);

} else if ($action == "addOTPurpose") {
    $companyid    = trim($_POST['companyid'] ?? '');
    $purpose_name = trim($_POST['purpose_name'] ?? '');
    $created_by   = trim($_POST['scannedBy'] ?? $_SESSION['loginname'] ?? 'web');

    if (empty($purpose_name)) {
        sendJsonOutput(['status' => 'error', 'message' => 'OT उद्देश्य खाली नहीं हो सकता।']);
    }

    try {
        $stmt = $hrmconn->prepare("INSERT INTO hrm_labourotpurpose_master (companyid, purpose_name, status, created_by) VALUES (?, ?, 'Active', ?) ON DUPLICATE KEY UPDATE status = 'Active'");
        $stmt->bind_param("sss", $companyid, $purpose_name, $created_by);
        $ok = $stmt->execute();
        $insertedId = $stmt->insert_id;
        $stmt->close();

        if ($ok) {
            sendJsonOutput([
                'status' => 'success',
                'message' => 'नया OT उद्देश्य सफलतापूर्वक जोड़ा गया।',
                'purpose_name' => $purpose_name,
                'id' => $insertedId
            ]);
        } else {
            sendJsonOutput(['status' => 'error', 'message' => 'डेटाबेस त्रुटि: ' . $hrmconn->error]);
        }
    } catch (Throwable $e) {
        sendJsonOutput(['status' => 'error', 'message' => 'त्रुटि: ' . $e->getMessage()]);
    }

} else if ($action == "labourworkingloactiondetails") {
    $companyid    = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid    = $_POST['projectid'] ?? '';
    $shortby      = $_POST['shortby'] ?? 'all';
    $shortbyvalue = $_POST['shortbyvalue'] ?? 'all';

    $locationlist = [];
    try {
        if (function_exists('getAttendanceLocationMaster')) {
            $locationlist = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
        }
    } catch (Throwable $e) {}
    sendJsonOutput($locationlist ?: []);

} else if ($action == "GetPolyhousesByLocation") {
    $projectid  = $_POST['projectid']  ?? null;
    $locationid = $_POST['locationid'] ?? null;

    $data = [];
    try {
        $sql  = "SELECT polyhouseid, polyhousename, physicallocation, locationid
                 FROM krf_polyhouse_master
                 WHERE status = 'Active'";
        if ($projectid) {
            $sql .= " AND projectid = '" . $krfconn->real_escape_string($projectid) . "'";
        }
        if ($locationid && $locationid !== "all") {
            $sql .= " AND locationid = '" . $krfconn->real_escape_string($locationid) . "'";
        }
        $res = @$krfconn->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $data[] = $row;
            }
        }
    } catch (Throwable $e) {}
    sendJsonOutput($data);

} else if ($action == "LoadLabourlist") {
    $companyid    = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid    = $_POST['projectid'] ?? '';

    $depts  = [];
    $desigs = [];

    try {
        $sqlDept = "SELECT DepartmentID, DepartmentName FROM hrm_DepartmentMaster WHERE 1=1";
        if (!empty($companyid)) {
            $sqlDept .= " AND companyid = '" . $hrmconn->real_escape_string($companyid) . "'";
        }
        $deptRes = @$hrmconn->query($sqlDept);
        if ($deptRes) {
            while ($r = $deptRes->fetch_assoc()) {
                $depts[] = $r;
            }
        }
    } catch (Throwable $e) {}

    try {
        $sqlDesig = "SELECT DesignationID, DesignationName FROM hrm_DesignationMaster WHERE 1=1";
        if (!empty($companyid)) {
            $sqlDesig .= " AND companyid = '" . $hrmconn->real_escape_string($companyid) . "'";
        }
        $desigRes = @$hrmconn->query($sqlDesig);
        if ($desigRes) {
            while ($r = $desigRes->fetch_assoc()) {
                $desigs[] = $r;
            }
        }
    } catch (Throwable $e) {}

    sendJsonOutput(['departments' => $depts, 'designations' => $desigs]);

} else if ($action == "GetLabourFullDetails") {
    $employeeID   = trim($_POST['employeeID'] ?? '');
    $companyid    = trim($_POST['companyid'] ?? '');
    $subcompanyid = trim($_POST['subcompanyid'] ?? '');
    $projectid    = trim($_POST['projectid'] ?? '');

    try {
        $stmt = $hrmconn->prepare("SELECT * FROM hrm_LabourMaster WHERE BINARY EmployeeID = ? AND projectid = ? LIMIT 1");
        $stmt->bind_param("ss", $employeeID, $projectid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $fn = trim($row['FirstName'] ?? '');
            $mn = trim($row['MiddleName'] ?? '');
            $ln = trim($row['LastName'] ?? '');

            $fullName = implode(' ', array_filter([$fn, $mn, $ln]));
            if (empty($fullName)) {
                $fullName = "ID " . $employeeID;
            }

            $row['FullName']  = $fullName;
            $row['FirstName'] = !empty($fn) ? $fn : $fullName;

            sendJsonOutput(['status' => 'Success', 'data' => $row]);
        } else {
            sendJsonOutput(['status' => 'Error', 'message' => 'लेबर आईडी नहीं मिली।']);
        }
        $stmt->close();
    } catch (Throwable $e) {
        sendJsonOutput(['status' => 'Error', 'message' => 'सर्वर त्रुटि: ' . $e->getMessage()]);
    }

} else if ($action == "SaveQRAttendanceOT" || $action == "SaveQRAttendance") {
    date_default_timezone_set('Asia/Kolkata');
    
    $employeeID   = trim($_POST['employeeID']   ?? '');
    $projectid    = trim($_POST['projectid']    ?? '');
    $scannerType  = trim($_POST['scannerType']  ?? '');   // "main-gate" | polyhouseid
    $scannerName  = trim($_POST['scannerName']  ?? '');   // human-readable label
    $scannedBy    = trim($_POST['scannedBy']    ?? 'QR_SYSTEM_OT');
    $locationId   = trim($_POST['locationId']   ?? '');
    $loginroleid  = intval($_POST['loginroleid'] ?? $_SESSION['loginroleid'] ?? 99);
    $purposeofot  = trim($_POST['purposeofot']  ?? $_POST['purposeOfOT'] ?? '');
    $todayDate    = date('Y-m-d');
    $nowDT        = date('Y-m-d H:i:s');
    $source       = 'QR_SCANNER_OT';

    // Validate OT allowed time window: 6:30 PM (18:30 = 1110 mins) to 7:00 AM (07:00 = 420 mins)
    $currentH = intval(date('G'));
    $currentM = intval(date('i'));
    $timeInMinutes = ($currentH * 60) + $currentM;

    if ($timeInMinutes > 420 && $timeInMinutes < 1110) {
        sendJsonOutput([
            'status'  => 'error',
            'message' => 'OT Allowed Between 6:30 PM To 7 AM only (ओ.टी. स्कैन केवल शाम 6:30 बजे से सुबह 7:00 बजे के बीच ही अनुमत है)।'
        ]);
    }

    // Determine suffix
    $isMN = (strtolower($scannerType) === 'main-gate');
    $sfx  = $isMN ? 'mn' : 'dp';

    if (empty($employeeID) || empty($projectid)) {
        sendJsonOutput(['status' => 'error', 'message' => 'आवश्यक मापदंड उपलब्ध नहीं हैं।']);
    }

    if (empty($purposeofot)) {
        sendJsonOutput(['status' => 'error', 'message' => 'आगे बढ़ने के लिए OT का उद्देश्य दर्ज करें।']);
    }

    try {
        // 1. Resolve labourid, locationid, and Labour Name
        $stmtLid = $hrmconn->prepare("SELECT * FROM hrm_LabourMaster WHERE BINARY EmployeeID = ? AND projectid = ? LIMIT 1");
        $stmtLid->bind_param("ss", $employeeID, $projectid);
        $stmtLid->execute();
        $rowLid = $stmtLid->get_result()->fetch_assoc();
        $stmtLid->close();

        if (!$rowLid) {
            sendJsonOutput(['status' => 'error', 'message' => 'लेबर की जानकारी नहीं मिली।']);
        }
        $labourid = $rowLid['EmployeeID'] ?? $employeeID;
        $registeredLocationId = $rowLid['locationid'] ?? '';
        $labourlocationid = !empty($registeredLocationId) ? $registeredLocationId : $locationId;

        $fn = trim($rowLid['FirstName'] ?? '');
        $mn = trim($rowLid['MiddleName'] ?? '');
        $ln = trim($rowLid['LastName'] ?? '');
        $resolvedName = implode(' ', array_filter([$fn, $mn, $ln]));
        if (empty($resolvedName)) {
            $resolvedName = "ID " . $employeeID;
        }

        $confirmCheckout = isset($_POST['confirmCheckout']) && $_POST['confirmCheckout'] === 'true';

        // 1b. Check main-gate markout (checkout) in `hrm_labourattendance` before marking OT attendance
        $stmtMainGate = $hrmconn->prepare(
            "SELECT attendanceid, checkintime_mn, checkouttime_mn 
             FROM hrm_labourattendance 
             WHERE (tedit IS NULL OR tedit = '') AND labourid = ? AND projectid = ? AND attendancedate = ? 
             ORDER BY attendanceid DESC 
             LIMIT 1"
        );
        $stmtMainGate->bind_param("sss", $labourid, $projectid, $todayDate);
        $stmtMainGate->execute();
        $rowMainGate = $stmtMainGate->get_result()->fetch_assoc();
        $stmtMainGate->close();

        // Only check for markout if markin exists for that date
        if ($rowMainGate && !empty($rowMainGate['checkintime_mn']) && $rowMainGate['checkintime_mn'] != '0000-00-00 00:00:00') {
            if (empty($rowMainGate['checkouttime_mn']) || $rowMainGate['checkouttime_mn'] == '0000-00-00 00:00:00') {
                sendJsonOutput([
                    'status'     => 'error',
                    'labourName' => $resolvedName,
                    'employeeID' => $employeeID,
                    'message'    => 'First punchout or markout your actual attendance in main-gate than mark attendance for ot attendance (कृपया पहले मेन-गेट (Main-Gate) पर अपनी वास्तविक उपस्थिति का पंचआउट/मार्कआउट करें, उसके बाद OT उपस्थिति दर्ज करें)।'
                ]);
            }
        }

        // 2. Fetch today's attendance row from `hrm_labourattendance_forot`
        $stmtFetch = $hrmconn->prepare(
            "SELECT attendanceid, checkintime_{$sfx}, checkouttime_{$sfx}, purposeofot
             FROM hrm_labourattendance_forot
             WHERE (tedit IS NULL OR tedit = '') AND labourid = ? AND projectid = ? AND attendancedate = ?
             ORDER BY attendanceid DESC
             LIMIT 1"
        );
        $stmtFetch->bind_param("sss", $labourid, $projectid, $todayDate);
        $stmtFetch->execute();
        $row = $stmtFetch->get_result()->fetch_assoc();
        $stmtFetch->close();

        if (!$row) {
            // ── 3. No row today → INSERT check-in into `hrm_labourattendance_forot` ──
            $sql = "INSERT INTO hrm_labourattendance_forot
                        (projectid, labourid, labourlocationid, attendancedate,
                         checkintime_{$sfx}, sourcein_{$sfx},
                         checkinscannerid_{$sfx}, checkinlocation_{$sfx},
                         checkinby_{$sfx}, purposeofot, status, updated_at, updated_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'MIS', ?, ?)";
            $stmt = $hrmconn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssss",
                $projectid, $labourid, $labourlocationid, $todayDate,
                $nowDT, $source,
                $scannerName, $locationId,
                $scannedBy, $purposeofot, $todayDate, $scannedBy
            );
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                sendJsonOutput([
                    'status' => 'success',
                    'type' => 'checkin',
                    'time' => $nowDT,
                    'labourName' => $resolvedName,
                    'employeeID' => $employeeID,
                    'purposeofot' => $purposeofot,
                    'message' => 'OT चेक-इन सफलतापूर्वक दर्ज हो गया है।'
                ]);
            } else {
                sendJsonOutput(['status' => 'error', 'message' => 'डेटाबेस सहेजना विफल: ' . $hrmconn->error]);
            }

        } elseif (empty($row["checkintime_{$sfx}"])) {
            // ── 4. Row exists but no check-in for this suffix ──
            $sql = "UPDATE hrm_labourattendance_forot SET
                        labourlocationid         = ?,
                        checkintime_{$sfx}       = ?,
                        sourcein_{$sfx}          = ?,
                        checkinscannerid_{$sfx}  = ?,
                        checkinlocation_{$sfx}   = ?,
                        checkinby_{$sfx}         = ?,
                        purposeofot              = IF(COALESCE(?, '') = '', purposeofot, ?),
                        status = 'Present', updated_at = ?, updated_by = ?
                    WHERE attendanceid = ?";
            $stmt = $hrmconn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssi",
                $labourlocationid, $nowDT, $source, $scannerName, $locationId,
                $scannedBy, $purposeofot, $purposeofot, $todayDate, $scannedBy,
                $row['attendanceid']
            );
            $ok = $stmt->execute();
            $stmt->close();

            sendJsonOutput($ok
                ? [
                    'status' => 'success',
                    'type' => 'checkin',
                    'time' => $nowDT,
                    'labourName' => $resolvedName,
                    'employeeID' => $employeeID,
                    'purposeofot' => $purposeofot,
                    'message' => 'OT चेक-इन सफलतापूर्वक दर्ज हो गया है।'
                  ]
                : ['status' => 'error', 'message' => 'डेटाबेस सहेजना विफल: ' . $hrmconn->error]);

        } elseif (empty($row["checkouttime_{$sfx}"])) {
            // ── 5. Check-in done → Check 15 minute scan cooldown rule ──
            $timeIn  = strtotime($row["checkintime_{$sfx}"]);
            $timeOut = strtotime($nowDT);
            $diffSeconds = max(0, $timeOut - $timeIn);

            if ($diffSeconds < 900) {
                sendJsonOutput([
                    'status'     => 'error',
                    'labourName' => $resolvedName,
                    'employeeID' => $employeeID,
                    'message'    => 'आप 15 मिनट के भीतर दोबारा स्कैन नहीं कर सकते। कृपया 15 मिनट बाद प्रयास करें।'
                ]);
            }

            // Calculate working hours
            $dtIn  = new DateTime($row["checkintime_{$sfx}"]);
            $dtOut = new DateTime($nowDT);
            $diff  = $dtIn->diff($dtOut);
            $wh    = sprintf('%02d:%02d:%02d', ($diff->days * 24) + $diff->h, $diff->i, $diff->s);

            // Check if worked time is less than 1 hour (3600 seconds)
            $isUnder1Hour = ($diffSeconds < 3600);
            $under1HourMsg = 'ध्यान दें: 1 घंटे से कम का OT समय वेतन गणना में शामिल नहीं किया जाएगा।';

            // If not confirmed yet, send confirmation prompt details to frontend
            if (!$confirmCheckout) {
                sendJsonOutput([
                    'status'        => 'confirm_checkout',
                    'type'          => 'checkout',
                    'workinghours'  => $wh,
                    'checkintime'   => $row["checkintime_{$sfx}"],
                    'checkouttime'  => $nowDT,
                    'labourName'    => $resolvedName,
                    'employeeID'    => $employeeID,
                    'purposeofot'   => !empty($purposeofot) ? $purposeofot : ($row['purposeofot'] ?? ''),
                    'isUnder1Hour'  => $isUnder1Hour,
                    'under1HourMsg' => $isUnder1Hour ? $under1HourMsg : '',
                    'message'       => 'कुल OT कार्य समय: ' . $wh . '। सहेजने की पुष्टि करें।'
                ]);
            }

            $earlyCheckoutRemark = trim($_POST['earlyCheckoutRemark'] ?? '');

            // UPDATE checkout into `hrm_labourattendance_forot`
            $sql = "UPDATE hrm_labourattendance_forot SET
                        checkouttime_{$sfx}      = ?,
                        sourceout_{$sfx}         = ?,
                        checkoutscannerid_{$sfx} = ?,
                        checkoutlocation_{$sfx}  = ?,
                        checkoutby_{$sfx}        = ?,
                        workinghours_{$sfx}      = ?,
                        remark_{$sfx}            = ?,
                        purposeofot              = IF(COALESCE(?, '') = '', purposeofot, ?),
                        updated_at = ?, updated_by = ?
                    WHERE attendanceid = ?";
            $stmt = $hrmconn->prepare($sql);
            $stmt->bind_param(
                "sssssssssssi",
                $nowDT, $source, $scannerName, $locationId,
                $scannedBy, $wh,
                $earlyCheckoutRemark,
                $purposeofot, $purposeofot,
                $todayDate, $scannedBy,
                $row['attendanceid']
            );
            $ok = $stmt->execute();
            $stmt->close();

            $checkoutMsg = 'OT चेक-आउट सफलतापूर्वक दर्ज हो गया है। कुल कार्य समय: ' . $wh;
            if ($isUnder1Hour) {
                $checkoutMsg .= "\n(" . $under1HourMsg . ")";
            }

            sendJsonOutput($ok
                ? [
                    'status'        => 'success',
                    'type'          => 'checkout',
                    'time'          => $nowDT,
                    'workinghours'  => $wh,
                    'labourName'    => $resolvedName,
                    'employeeID'    => $employeeID,
                    'purposeofot'   => !empty($purposeofot) ? $purposeofot : ($row['purposeofot'] ?? ''),
                    'isUnder1Hour'  => $isUnder1Hour,
                    'under1HourMsg' => $isUnder1Hour ? $under1HourMsg : '',
                    'message'       => $checkoutMsg
                  ]
                : ['status' => 'error', 'message' => 'डेटाबेस सहेजना विफल: ' . $hrmconn->error]);

        } else {
            // ── 6. Both check-in & checkout already done for OT today ──
            sendJsonOutput([
                'status'     => 'error',
                'type'       => 'already_complete',
                'checkin'    => $row["checkintime_{$sfx}"],
                'checkout'   => $row["checkouttime_{$sfx}"],
                'labourName' => $resolvedName,
                'employeeID' => $employeeID,
                'purposeofot' => $row['purposeofot'] ?? $purposeofot,
                'message'    => 'इस लेबर की आज की OT उपस्थिति (चेक-इन और चेक-आउट) पहले ही पूरी हो चुकी है।'
            ]);
        }
    } catch (Throwable $e) {
        sendJsonOutput(['status' => 'error', 'message' => 'सर्वर त्रुटि: ' . $e->getMessage()]);
    }
}
?>
