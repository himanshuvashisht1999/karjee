<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.']);
    exit;
}

require_once('../../connection.php');
require_once('../functions/hrm_FunctionMaster.php');
require_once('../functions/admin_functionmaster.php');

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';
$projectid = $_POST['projectid'] ?? '';
$companyid = $_POST['companyid'] ?? '';
$subcompanyid = $_POST['subcompanyid'] ?? '';

if (empty($projectid) && $action !== 'ApproveOT') {
    echo json_encode(['status' => 'error', 'message' => 'Project ID is required.']);
    exit;
}

// Helper: Convert HH:MM:SS to decimal hours
function parseOTHours($otStr) {
    if (empty($otStr)) return 0.0;
    if (strpos($otStr, ':') !== false) {
        $parts = explode(':', $otStr);
        $hrs = floatval($parts[0] ?? 0);
        $mins = floatval($parts[1] ?? 0);
        $secs = floatval($parts[2] ?? 0);
        return $hrs + ($mins / 60) + ($secs / 3600);
    }
    return floatval($otStr);
}

// Helper: Match punch-in time to the closest active shift
function isSameLocationMatch($scanLoc, $locId, $locName) {
    if (empty($scanLoc)) return false;
    
    $scan = strtolower(trim($scanLoc));
    $lid  = strtolower(trim($locId));
    $lname = strtolower(trim($locName));
    
    if ($scan === 'manual') return true;
    
    if ($scan === $lid || $scan === $lname) {
        return true;
    }
    
    // Compare numeric IDs (e.g. "1" vs "1", "LOC-01" vs "1", "LOC-1" vs "1")
    $scanNum = preg_replace('/[^0-9]/', '', $scan);
    $lidNum  = preg_replace('/[^0-9]/', '', $lid);
    if ($scanNum !== '' && $lidNum !== '' && intval($scanNum) === intval($lidNum)) {
        return true;
    }
    
    // Substring / name match
    if (!empty($lname) && strlen($lname) >= 3 && (strpos($scan, $lname) !== false || strpos($lname, $scan) !== false)) {
        return true;
    }
    if (!empty($lid) && strlen($lid) >= 3 && (strpos($scan, $lid) !== false || strpos($lid, $scan) !== false)) {
        return true;
    }
    
    return false;
}

function getMatchedShift($checkin_time, $shifts) {
    if (empty($shifts)) {
        return [
            'id' => 0,
            'name' => 'General Shift',
            'start_time' => '09:30:00',
            'end_time' => '18:30:00'
        ];
    }
    
    $punchTimeStr = date('H:i:s', strtotime($checkin_time));
    $punch = strtotime($punchTimeStr);
    $bestShift = null;
    $minDiff = null;
    
    foreach ($shifts as $s) {
        $start = strtotime($s['start_time']);
        $diff = abs($punch - $start);
        if ($minDiff === null || $diff < $minDiff) {
            $minDiff = $diff;
            $bestShift = $s;
        }
    }
    
    return [
        'id' => $bestShift['id'],
        'name' => $bestShift['working_hour_name'],
        'start_time' => $bestShift['start_time'],
        'end_time' => $bestShift['end_time']
    ];
}

if ($action == 'GetFilters') {
    // 1. Locations
    $locations = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "all", "all");
    if (empty($locations)) {
        $locations = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, "all", "all", "all");
    }

    // 2. Scan By Operators
    $operators = [];
    $sqlOperators = "
        SELECT DISTINCT checkinby_mn AS username FROM hrm_labourattendance WHERE projectid = ? AND checkinby_mn IS NOT NULL AND checkinby_mn != ''
        UNION
        SELECT DISTINCT checkinby_dp AS username FROM hrm_labourattendance WHERE projectid = ? AND checkinby_dp IS NOT NULL AND checkinby_dp != ''
        UNION
        SELECT DISTINCT checkoutby_mn AS username FROM hrm_labourattendance WHERE projectid = ? AND checkoutby_mn IS NOT NULL AND checkoutby_mn != ''
        UNION
        SELECT DISTINCT checkoutby_dp AS username FROM hrm_labourattendance WHERE projectid = ? AND checkoutby_dp IS NOT NULL AND checkoutby_dp != ''
    ";
    if ($stmtOp = $hrmconn->prepare($sqlOperators)) {
        $pid = intval($projectid);
        $stmtOp->bind_param("iiii", $pid, $pid, $pid, $pid);
        $stmtOp->execute();
        $resOp = $stmtOp->get_result();
        while ($row = $resOp->fetch_assoc()) {
            $u = trim($row['username']);
            if (!empty($u)) {
                $operators[] = $u;
            }
        }
        $stmtOp->close();
    }
    sort($operators);

    echo json_encode([
        'status' => 'success',
        'locations' => $locations,
        'operators' => $operators
    ]);
    exit;
}

if ($action == 'GetDashboardData' || $action == 'GetLocationDetails') {
    $date_from = $_POST['date_from'] ?? date('Y-m-d');
    $date_to = $_POST['date_to'] ?? date('Y-m-d');
    $locationid = $_POST['locationid'] ?? 'all';
    $qr_location = $_POST['qr_location'] ?? 'all';
    $scan_by = $_POST['scan_by'] ?? 'all';
    
    $departmentid = 'all';
    $shiftid = 'all';
    $paytoid = 'all';
    
    // Resolve polyhouse name if a specific polyhouse ID is filtered
    $filterPolyName = '';
    if (!empty($qr_location) && $qr_location !== 'all' && $qr_location !== 'main-gate') {
        $stmtPoly = $krfconn->prepare("SELECT polyhousename FROM krf_polyhouse_master WHERE polyhouseid = ? LIMIT 1");
        $stmtPoly->bind_param("s", $qr_location);
        $stmtPoly->execute();
        $resPoly = $stmtPoly->get_result()->fetch_assoc();
        if ($resPoly) {
            $filterPolyName = $resPoly['polyhousename'];
        }
        $stmtPoly->close();
    }
    
    // Generate dates array
    $dates = [];
    $start = strtotime($date_from);
    $end = strtotime($date_to);
    for ($current = $start; $current <= $end; $current = strtotime("+1 day", $current)) {
        $dates[] = date('Y-m-d', $current);
    }
    if (empty($dates)) {
        $dates[] = date('Y-m-d');
    }
    $latest_date = end($dates);
    
    // Load active shifts for the project
    $shifts = [];
    $sqlShifts = "SELECT id, working_hour_name, start_time, end_time FROM hrm_PayrollWorkingHourse_master WHERE ProjectID = ? AND status = 'active'";
    if ($stmtShifts = $hrmconn->prepare($sqlShifts)) {
        $stmtShifts->bind_param("i", $projectid);
        $stmtShifts->execute();
        $resShifts = $stmtShifts->get_result();
        while ($row = $resShifts->fetch_assoc()) {
            $shifts[] = $row;
        }
        $stmtShifts->close();
    }
    
    // Fetch workforce filter conditions
    $lmWhere = "lm.projectid = ? AND lm.Status = 'Active'";
    $lmParams = [$projectid];
    $lmTypes = "s";
    
    if ($departmentid !== 'all') {
        $lmWhere .= " AND lm.DepartmentID = ?";
        $lmParams[] = $departmentid;
        $lmTypes .= "i";
    }
    if ($locationid !== 'all' && $action !== 'GetLocationDetails') {
        $lmWhere .= " AND lm.locationid = ?";
        $lmParams[] = $locationid;
        $lmTypes .= "s";
    }
    // If drilling down to specific location
    if ($action == 'GetLocationDetails') {
        $target_loc = $_POST['target_locationid'] ?? '';
        $lmWhere .= " AND lm.locationid = ?";
        $lmParams[] = $target_loc;
        $lmTypes .= "s";
    }
    if ($paytoid !== 'all') {
        $lmWhere .= " AND lm.paytoid = ?";
        $lmParams[] = $paytoid;
        $lmTypes .= "s";
    }
    
    // 1. Fetch filtered active workforce
    $sqlWorkforce = "SELECT lm.EmployeeID, 
                            CONCAT_WS(' ', IFNULL(lm.FirstName,''), IFNULL(lm.MiddleName,''), IFNULL(lm.LastName,'')) AS FullName,
                            lm.locationid, lm.DepartmentID, lm.paytoid, lm.Gender, lm.MobileNo
                     FROM hrm_LabourMaster lm
                     WHERE $lmWhere";
                     
    $workers = [];
    if ($stmtWork = $hrmconn->prepare($sqlWorkforce)) {
        $stmtWork->bind_param($lmTypes, ...$lmParams);
        $stmtWork->execute();
        $resWork = $stmtWork->get_result();
        while ($row = $resWork->fetch_assoc()) {
            $row['FullName'] = trim(preg_replace('/\s+/', ' ', $row['FullName']));
            $workers[$row['EmployeeID']] = $row;
        }
        $stmtWork->close();
    }
    
    $workerIds = array_keys($workers);
    $attendanceData = []; // date -> labourid -> record
    
    if (!empty($workerIds)) {
        // Fetch attendance records for these workers and date range
        $placeholders = implode(',', array_fill(0, count($workerIds), '?'));
        $types = str_repeat('s', count($workerIds)) . "ss";
        
        $sqlAtt = "SELECT la.attendanceid, la.labourid, la.attendancedate, 
                          la.checkintime_mn, la.checkouttime_mn, la.workinghours_mn,
                          la.checkintime_dp, la.checkouttime_dp, la.workinghours_dp,
                          la.checkinscannerid_mn, la.checkoutscannerid_mn,
                          la.checkinscannerid_dp, la.checkoutscannerid_dp,
                          la.checkinby_mn, la.checkoutby_mn,
                          la.checkinby_dp, la.checkoutby_dp,
                          la.checkinlocation_mn, la.checkinlocation_dp,
                          la.status, la.leavetype, la.overtimehours, la.approvalstatus
                   FROM hrm_labourattendance la
                   WHERE la.labourid IN ($placeholders)
                     AND la.attendancedate BETWEEN ? AND ?
                     AND (la.tedit IS NULL OR la.tedit = '')";
        
        if ($stmtAtt = $hrmconn->prepare($sqlAtt)) {
            $params = array_merge($workerIds, [$date_from, $date_to]);
            $stmtAtt->bind_param($types, ...$params);
            $stmtAtt->execute();
            $resAtt = $stmtAtt->get_result();
            while ($row = $resAtt->fetch_assoc()) {
                $attendanceData[$row['attendancedate']][$row['labourid']] = $row;
            }
            $stmtAtt->close();
        }
    }
    
    // Process records day by day
    $dailyStats = []; // date -> metrics
    $latestStats = [
        'totalWorkforce' => count($workers),
        'present' => 0,
        'checkedIn' => 0,
        'earlyMarkout' => 0,
        'absent' => 0,
        'leave' => 0,
        'late' => 0,
        'totalOTHours' => 0.0
    ];
    
    $exceptions = [];
    $otSummaryMap = []; // labourid -> accumulated data
    foreach ($workers as $wid => $w) {
        $otSummaryMap[$wid] = [
            'labourid' => $wid,
            'name' => $w['FullName'],
            'regular_hours' => 0.0,
            'ot_hours' => 0.0,
            'total_hours' => 0.0,
            'pending_ot_id' => null,
            'has_pending' => false
        ];
    }
    
    $totalOTPeriod = 0.0;
    
    foreach ($dates as $d) {
        $presentCount = 0;
        $presentPunchingCount = 0;
        $presentManualCount = 0;
        $absentCount = 0;
        $lateCount = 0;
        $leaveCount = 0;
        $checkedInCount = 0;
        $checkedOutCount = 0;
        $earlyCount = 0;
        
        $dayAtt = $attendanceData[$d] ?? [];
        
        foreach ($workers as $wid => $w) {
            $att = $dayAtt[$wid] ?? null;
            
            // Extract check-in/out and matching times with QR Location filters
            $ci = null;
            $co = null;
            $wh = null;
            $ot = $att ? ($att['overtimehours'] ?? '00:00:00') : '00:00:00';
            $status_col = $att ? $att['status'] : null;
            
            if ($att) {
                $ci_mn   = $att['checkintime_mn'];
                $co_mn   = $att['checkouttime_mn'];
                $wh_mn   = $att['workinghours_mn'];
                $scan_mn = $att['checkinscannerid_mn'] ?? '';
                
                $ci_dp   = $att['checkintime_dp'];
                $co_dp   = $att['checkouttime_dp'];
                $wh_dp   = $att['workinghours_dp'];
                $scan_dp = $att['checkinscannerid_dp'] ?? '';
                
                if ($qr_location === 'main-gate') {
                    // Only Main-Gate scans
                    if (!empty($ci_mn) && $ci_mn !== '0000-00-00 00:00:00') {
                        $ci = $ci_mn;
                        $co = $co_mn;
                        $wh = $wh_mn;
                    }
                } elseif (!empty($filterPolyName)) {
                    // Only specific polyhouse scans (case-insensitive check)
                    if (!empty($ci_mn) && stripos($scan_mn, $filterPolyName) !== false) {
                        $ci = $ci_mn;
                        $co = $co_mn;
                        $wh = $wh_mn;
                    } elseif (!empty($ci_dp) && stripos($scan_dp, $filterPolyName) !== false) {
                        $ci = $ci_dp;
                        $co = $co_dp;
                        $wh = $wh_dp;
                    }
                } else {
                    // All locations (default behavior)
                    if (!empty($ci_mn) && $ci_mn !== '0000-00-00 00:00:00') {
                        $ci = $ci_mn;
                        $co = $co_mn;
                        $wh = $wh_mn;
                    } elseif (!empty($ci_dp) && $ci_dp !== '0000-00-00 00:00:00') {
                        $ci = $ci_dp;
                        $co = $co_dp;
                        $wh = $wh_dp;
                    }
                }
            }
            
            // Scan By filter
            if ($scan_by !== 'all' && $att) {
                $op_in_mn  = $att['checkinby_mn'] ?? '';
                $op_out_mn = $att['checkoutby_mn'] ?? '';
                $op_in_dp  = $att['checkinby_dp'] ?? '';
                $op_out_dp = $att['checkoutby_dp'] ?? '';
                
                $matchesOp = false;
                if ($ci === $att['checkintime_mn'] && ($op_in_mn === $scan_by || $op_out_mn === $scan_by)) {
                    $matchesOp = true;
                } elseif ($ci === $att['checkintime_dp'] && ($op_in_dp === $scan_by || $op_out_dp === $scan_by)) {
                    $matchesOp = true;
                }
                
                if (!$matchesOp) {
                    $ci = $co = $wh = $status_col = null;
                    $att = null;
                }
            }
            
            $status = 'Absent';
            $variance = 0;
            $expected_time = '';
            $punch_in = '';
            $isPresent = false;
            $isPunching = false;

            if ($att) {
                $scanInMN  = strtolower(trim($att['checkinscannerid_mn'] ?? ''));
                $scanOutMN = strtolower(trim($att['checkoutscannerid_mn'] ?? ''));
                $scanInDP  = strtolower(trim($att['checkinscannerid_dp'] ?? ''));
                $scanOutDP = strtolower(trim($att['checkoutscannerid_dp'] ?? ''));
                $srcInMN   = strtolower(trim($att['sourcein_mn'] ?? ''));
                $srcInDP   = strtolower(trim($att['sourcein_dp'] ?? ''));

                $isExplicitManual = ($scanInMN === 'manual' || $scanOutMN === 'manual' ||
                                     $scanInDP === 'manual' || $scanOutDP === 'manual' ||
                                     $srcInMN  === 'manual' || $srcInDP  === 'manual');

                if ($isExplicitManual) {
                    $isPunching = false;
                } elseif (in_array($srcInMN, ['qr_scanner', 'mobile_scanner', 'qr_scanner_ot', 'qr_scanner_app']) ||
                          in_array($srcInDP, ['qr_scanner', 'mobile_scanner', 'qr_scanner_ot', 'qr_scanner_app'])) {
                    $isPunching = true;
                } elseif (!empty($scanInMN) || !empty($scanInDP)) {
                    $isPunching = true;
                } elseif (!empty($ci)) {
                    $isPunching = true;
                }
            }
            
            if ($status_col === 'Leave' || ($att && !empty($att['leavetype']))) {
                $status = 'Leave';
                $leaveCount++;
                $absentCount++;
            } else if ($ci) {
                $punch_in = date('H:i:s', strtotime($ci));
                $mShift = getMatchedShift($ci, $shifts);
                $expected_time = $mShift['start_time'];
                
                // Variance calculation (Late Arrivals)
                $punchSec = strtotime($punch_in);
                $expectedSec = strtotime($expected_time);
                if ($punchSec > $expectedSec) {
                    $variance = round(($punchSec - $expectedSec) / 60);
                }
                
                if ($co) {
                    $checkedOutCount++;
                    $hours = 0.0;
                    if ($wh && $wh !== '00:00:00') {
                        $hours = parseOTHours($wh);
                    } else {
                        // fallback to diff
                        $hours = (strtotime($co) - strtotime($ci)) / 3600;
                    }
                    
                    if ($hours < 2) {
                        $status = 'Early Markout';
                        $earlyCount++;
                        $absentCount++; // counted as absent/early markout
                    } else {
                        if ($variance > 15) { // 15 mins grace period
                            $status = 'Late';
                            $lateCount++;
                        } else {
                            $status = 'Present';
                        }
                        $presentCount++;
                        $isPresent = true;
                    }
                } else {
                    $status = 'Checked-In';
                    $checkedInCount++;
                    $presentCount++; // currently present
                    $isPresent = true;
                }
            } else {
                if ($att && (strtolower($att['status'] ?? '') === 'present' || strtolower($att['status'] ?? '') === 'p')) {
                    $status = 'Present';
                    $presentCount++;
                    $isPresent = true;
                } else {
                    $status = 'Absent';
                    $absentCount++;
                }
            }

            if ($isPresent) {
                if ($isPunching) {
                    $presentPunchingCount++;
                } else {
                    $presentManualCount++;
                }
            }
            
            // Sum Overtime
            $otHours = 0.0;
            if ($att) {
                $otHours = parseOTHours($ot);
                $totalOTPeriod += $otHours;
                
                // Add to period summary
                $whHours = $wh ? parseOTHours($wh) : 0.0;
                $otSummaryMap[$wid]['regular_hours'] += max(0, $whHours - $otHours);
                $otSummaryMap[$wid]['ot_hours'] += $otHours;
                $otSummaryMap[$wid]['total_hours'] += $whHours;
                if ($att['approvalstatus'] == 'pending' && $otHours > 0) {
                    $otSummaryMap[$wid]['pending_ot_id'] = $att['attendanceid'];
                    $otSummaryMap[$wid]['has_pending'] = true;
                }
            }
            
            // If this is the latest date in the range, record stats and exceptions
            if ($d === $latest_date) {
                if ($status == 'Late' || $status == 'Early Markout' || $status == 'Checked-In') {
                    $exceptions[] = [
                        'labourid' => $wid,
                        'name' => $w['FullName'],
                        'shift' => $ci ? getMatchedShift($ci, $shifts)['name'] : 'General Shift',
                        'expected_time' => $expected_time ? date('h:i A', strtotime($expected_time)) : '09:30 AM',
                        'punch_in_time' => $ci ? date('h:i A', strtotime($ci)) : '—',
                        'variance' => $variance,
                        'status' => $status
                    ];
                }
            }
        }
        
        $dailyStats[$d] = [
            'date' => $d,
            'present' => $presentCount,
            'absent' => $absentCount
        ];
        
        if ($d === $latest_date) {
            $latestStats['present'] = $presentCount;
            $latestStats['presentPunching'] = $presentPunchingCount;
            $latestStats['presentManual'] = $presentManualCount;
            $latestStats['checkedIn'] = $checkedInCount;
            $latestStats['checkedOut'] = $checkedOutCount;
            $latestStats['earlyMarkout'] = $earlyCount;
            $latestStats['absent'] = $absentCount - $leaveCount - $earlyCount; // Pure absent
            $latestStats['leave'] = $leaveCount;
            $latestStats['late'] = $lateCount;
        }
    }
    
    $latestStats['totalOTHours'] = $totalOTPeriod;
    
    // Percentages
    $totalCount = count($workers);
    $latestStats['presentPct'] = $totalCount > 0 ? round(($latestStats['present'] / $totalCount) * 100, 1) : 0;
    $latestStats['absentPct'] = $totalCount > 0 ? round((($latestStats['absent'] + $latestStats['leave'] + $latestStats['earlyMarkout']) / $totalCount) * 100, 1) : 0;
    
    // Turnout by Location
    $locationCards = [];
    $locs = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, $projectid, "all", "all");
    if (empty($locs)) {
        $locs = getAttendanceLocationMaster($hrmconn, $companyid, $subcompanyid, "all", "all", "all");
    }
    $allLocs = [];
    $locAliases = [];
    foreach ($locs as $l) {
        $lId = strval($l['LocationId']);
        $lName = trim($l['LocationName'] ?? '');
        $allLocs[$lId] = $lName;

        $aliases = [
            strtolower($lId),
            strtolower($lName),
            'loc-' . sprintf('%02d', intval($lId)),
            'loc-' . intval($lId),
            'location ' . strtolower($lId)
        ];
        $locAliases[$lId] = array_values(array_filter(array_unique($aliases)));
    }
    
    // Add "Unassigned" or empty locations if workers have them
    foreach ($workers as $w) {
        $locid = $w['locationid'] ?? '';
        if (!empty($locid) && !isset($allLocs[$locid])) {
            $allLocs[$locid] = 'Location ' . $locid;
            $locAliases[$locid] = [strtolower($locid)];
        }
    }
    
    foreach ($allLocs as $locid => $locName) {
        // Filter workers for this location
        $locWorkers = array_filter($workers, fn($w) => isSameLocationMatch($w['locationid'] ?? '', $locid, $locName));
        $locTotal = count($locWorkers);
        
        if ($locTotal === 0 && $action !== 'GetLocationDetails') continue;
        
        $locTotalMale = 0;
        $locTotalFemale = 0;
        
        foreach ($locWorkers as $wid => $w) {
            $gender = strtolower(trim($w['Gender'] ?? ''));
            $isMale = ($gender === 'male' || $gender === 'm');
            $isFemale = ($gender === 'female' || $gender === 'f' || $gender === 'w' || $gender === 'women');
            if ($isMale) $locTotalMale++;
            else if ($isFemale) $locTotalFemale++;
        }

        $uniquePresentWorkers = [];
        $uniqueWorkingWorkers = [];
        $uniqueOtherLocWorkers = [];
        $uniqueLateWorkers = [];

        $sumAttTotal = 0;
        $sumAttMale = 0;
        $sumAttFemale = 0;

        $sumAbsentTotal = 0;
        $sumAbsentMale = 0;
        $sumAbsentFemale = 0;

        foreach ($attendanceData as $dStr => $dayAtt) {
            foreach ($workers as $wid => $w) {
                $homeLoc = strval($w['locationid'] ?? '');
                $isHomeWorker = isSameLocationMatch($homeLoc, $locid, $locName);

                $gender = strtolower(trim($w['Gender'] ?? ''));
                $isMale = ($gender === 'male' || $gender === 'm');
                $isFemale = ($gender === 'female' || $gender === 'f' || $gender === 'w' || $gender === 'women');

                $att = $dayAtt[$wid] ?? null;
                $ci = $att ? ($att['checkintime_mn'] ?? $att['checkintime_dp']) : null;

                if ($ci) {
                    $ci_mn = $att['checkintime_mn'] ?? '';
                    $ci_dp = $att['checkintime_dp'] ?? '';
                    $scanLoc = '';
                    if (!empty($ci_mn) && $ci_mn !== '0000-00-00 00:00:00') {
                        $scanLoc = $att['checkinlocation_mn'] ?? '';
                    } elseif (!empty($ci_dp) && $ci_dp !== '0000-00-00 00:00:00') {
                        $scanLoc = $att['checkinlocation_dp'] ?? '';
                    }

                    $isScanAtThisLoc = isSameLocationMatch($scanLoc, $locid, $locName);

                    if ($isHomeWorker) {
                        $sumAttTotal++;
                        if ($isMale) $sumAttMale++;
                        else if ($isFemale) $sumAttFemale++;

                        $uniquePresentWorkers[$wid] = $gender;

                        if (!$isScanAtThisLoc && !empty($scanLoc) && strtolower(trim($scanLoc)) !== 'manual') {
                            $uniqueOtherLocWorkers[$wid] = $gender;
                        }

                        // Check Late
                        $punch_in = date('H:i:s', strtotime($ci));
                        $mShift = getMatchedShift($ci, $shifts);
                        if (strtotime($punch_in) > strtotime($mShift['start_time']) + 900) {
                            $uniqueLateWorkers[$wid] = $gender;
                        }
                    }

                    if ($isScanAtThisLoc) {
                        $uniqueWorkingWorkers[$wid] = $gender;
                    }
                } else {
                    if ($isHomeWorker) {
                        $sumAbsentTotal++;
                        if ($isMale) $sumAbsentMale++;
                        else if ($isFemale) $sumAbsentFemale++;
                    }
                }
            }
        }

        $countUniqueByGender = function($map) {
            $total = count($map);
            $male = 0;
            $female = 0;
            foreach ($map as $g) {
                if ($g === 'male' || $g === 'm') $male++;
                else if ($g === 'female' || $g === 'f' || $g === 'w' || $g === 'women') $female++;
            }
            return [$total, $male, $female];
        };

        list($locPresent, $locPresentMale, $locPresentFemale) = $countUniqueByGender($uniquePresentWorkers);
        list($locWorkingCount, $locWorkingMale, $locWorkingFemale) = $countUniqueByGender($uniqueWorkingWorkers);
        list($locOtherLocationScanCount, $locOtherLocationScanMale, $locOtherLocationScanFemale) = $countUniqueByGender($uniqueOtherLocWorkers);
        list($locLate, $locLateMale, $locLateFemale) = $countUniqueByGender($uniqueLateWorkers);

        $turnout = $locTotal > 0 ? round(($locPresent / $locTotal) * 100, 1) : 0;

        $locationCards[] = [
            'locationid' => $locid,
            'locationName' => $locName,
            'totalWorkforce' => $locTotal,
            'totalWorkforceMale' => $locTotalMale,
            'totalWorkforceFemale' => $locTotalFemale,
            'presentCount' => $locPresent,
            'presentCountMale' => $locPresentMale,
            'presentCountFemale' => $locPresentFemale,
            'workingCount' => $locWorkingCount,
            'workingCountMale' => $locWorkingMale,
            'workingCountFemale' => $locWorkingFemale,
            'absentCount' => $sumAbsentTotal,
            'absentCountMale' => $sumAbsentMale,
            'absentCountFemale' => $sumAbsentFemale,
            'lateCount' => $locLate,
            'lateCountMale' => $locLateMale,
            'lateCountFemale' => $locLateFemale,
            'otherLocationCount' => $locOtherLocationScanCount,
            'otherLocationMale' => $locOtherLocationScanMale,
            'otherLocationFemale' => $locOtherLocationScanFemale,
            'attendanceCount' => $sumAttTotal,
            'attendanceCountMale' => $sumAttMale,
            'attendanceCountFemale' => $sumAttFemale,
            'turnoutRate' => $turnout
        ];
    }
    
    // Sort locations by turnout rate descending
    usort($locationCards, fn($a, $b) => $b['turnoutRate'] <=> $a['turnoutRate']);
    
    if ($action == 'GetLocationDetails') {
        $modalLogs = [];
        $targetLocName = $allLocs[$target_loc] ?? '';

        foreach ($attendanceData as $dStr => $dayAtt) {
            foreach ($workers as $wid => $w) {
                $assignedLocId = strval($w['locationid'] ?? '');
                $assignedLocName = $allLocs[$assignedLocId] ?? '';

                $att = $dayAtt[$wid] ?? null;
                $ci_mn = $att ? $att['checkintime_mn'] : null;
                $co_mn = $att ? $att['checkouttime_mn'] : null;
                $ci_dp = $att ? $att['checkintime_dp'] : null;
                $co_dp = $att ? $att['checkouttime_dp'] : null;
                $status_col = $att ? $att['status'] : null;

                $ci = $ci_mn ?? $ci_dp;
                $co = $co_mn ?? $co_dp;
                $wh = $att ? ($att['workinghours_mn'] ?? $att['workinghours_dp']) : '—';

                $scanLoc = '';
                if ($att) {
                    if (!empty($ci_mn) && $ci_mn !== '0000-00-00 00:00:00') {
                        $scanLoc = $att['checkinlocation_mn'] ?? '';
                    } elseif (!empty($ci_dp) && $ci_dp !== '0000-00-00 00:00:00') {
                        $scanLoc = $att['checkinlocation_dp'] ?? '';
                    }
                }

                // Check if worker is registered at target location or scanned at target location
                $isHomeLoc = isSameLocationMatch($assignedLocId, $target_loc, $targetLocName);
                $isScanLoc = isSameLocationMatch($scanLoc, $target_loc, $targetLocName);

                if (!$isHomeLoc && !$isScanLoc) continue;

                $status = 'Absent';
                if ($status_col === 'Leave' || ($att && !empty($att['leavetype']))) {
                    $status = 'Leave';
                } else if ($ci) {
                    if ($co) {
                        $hours = $att ? parseOTHours($att['workinghours_mn'] ?? $att['workinghours_dp']) : 0;
                        if ($hours < 2) $status = 'Early Markout';
                        else {
                            $punch_in = date('H:i:s', strtotime($ci));
                            $mShift = getMatchedShift($ci, $shifts);
                            if (strtotime($punch_in) > strtotime($mShift['start_time']) + 900) $status = 'Late';
                            else $status = 'Present';
                        }
                    } else {
                        $status = 'Checked-In';
                    }
                }

                // In multi-day view, skip pure Absent records with no punches to keep table relevant
                if ($date_from !== $date_to && $status === 'Absent') {
                    continue;
                }

                $isOtherLocationScan = false;
                $resolvedScanLocName = '';

                if (!empty($scanLoc)) {
                    if (isset($allLocs[$scanLoc])) {
                        $resolvedScanLocName = $allLocs[$scanLoc];
                    } else {
                        $resolvedScanLocName = $scanLoc;
                    }

                    $scanLocClean = strtolower(trim($scanLoc));
                    $assignedLocIdClean = strtolower(trim($assignedLocId));
                    $assignedLocNameClean = strtolower(trim($assignedLocName));

                    if ($scanLocClean !== 'manual' && $scanLocClean !== '') {
                        $matchId = ($scanLocClean === $assignedLocIdClean);
                        $matchName = (!empty($assignedLocNameClean) && strpos($scanLocClean, $assignedLocNameClean) !== false);
                        if (!$matchId && !$matchName) {
                            $isOtherLocationScan = true;
                        }
                    }
                }

                $modalLogs[] = [
                    'labourid' => $wid,
                    'name' => $w['FullName'],
                    'mobile' => $w['MobileNo'] ?? '—',
                    'att_date' => $dStr,
                    'checkin_mn' => $ci_mn ? date('h:i A', strtotime($ci_mn)) : '—',
                    'checkout_mn' => $co_mn ? date('h:i A', strtotime($co_mn)) : '—',
                    'checkin_dp' => $ci_dp ? date('h:i A', strtotime($ci_dp)) : '—',
                    'checkout_dp' => $co_dp ? date('h:i A', strtotime($co_dp)) : '—',
                    'working_hours' => $wh,
                    'status' => $status,
                    'is_other_location' => $isOtherLocationScan,
                    'scan_location_name' => $resolvedScanLocName,
                    'gender' => strtolower(trim($w['Gender'] ?? ''))
                ];
            }
        }

        echo json_encode([
            'status' => 'success',
            'locationName' => $allLocs[$target_loc] ?? 'Selected Location',
            'logs' => $modalLogs
        ]);
        exit;
    }
    
    // Charts formatting
    $chartDates = [];
    $chartPresent = [];
    $chartAbsent = [];
    foreach ($dailyStats as $ds) {
        $chartDates[] = date('d M', strtotime($ds['date']));
        $chartPresent[] = $ds['present'];
        $chartAbsent[] = $ds['absent'];
    }
    
    $distributionLabels = ["On Time", "Late", "Early Markout", "Checked-in", "Absent", "On Leave"];
    $distributionValues = [
        $latestStats['present'] - $latestStats['late'] - $latestStats['checkedIn'],
        $latestStats['late'],
        $latestStats['earlyMarkout'],
        $latestStats['checkedIn'],
        $latestStats['absent'],
        $latestStats['leave']
    ];
    
    // Clean negative numbers just in case
    foreach ($distributionValues as $idx => $val) {
        if ($val < 0) $distributionValues[$idx] = 0;
    }
    
    // Sort turnout for comparison
    $turnoutCategories = [];
    $turnoutValues = [];
    foreach ($locationCards as $lc) {
        $turnoutCategories[] = $lc['locationName'];
        $turnoutValues[] = $lc['turnoutRate'];
    }
    
    echo json_encode([
        'status' => 'success',
        'kpis' => $latestStats,
        'locationCards' => $locationCards,
        'charts' => [
            'trend' => [
                'dates' => $chartDates,
                'present' => $chartPresent,
                'absent' => $chartAbsent
            ],
            'distribution' => [
                'labels' => $distributionLabels,
                'values' => $distributionValues
            ],
            'turnout' => [
                'categories' => array_slice($turnoutCategories, 0, 10),
                'values' => array_slice($turnoutValues, 0, 10)
            ]
        ],
        'exceptions' => $exceptions,
        'otSummary' => array_values($otSummaryMap)
    ]);
    exit;
}

if ($action == 'ApproveOT') {
    $attendanceid = $_POST['attendanceid'] ?? '';
    $status = $_POST['status'] ?? 'approved'; // approved, rejected, pending
    
    if (empty($attendanceid)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing attendance id.']);
        exit;
    }
    
    $sql = "UPDATE hrm_labourattendance SET approvalstatus = ? WHERE attendanceid = ?";
    $stmt = $hrmconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $status, $attendanceid);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'OT status updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Update execution failed: ' . $hrmconn->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $hrmconn->error]);
    }
    exit;
}
