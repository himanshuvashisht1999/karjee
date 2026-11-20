<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Validate session
if (!isset($_SESSION['sessionid']) || $_SESSION['sessionid'] !== session_id()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'msg' => 'Session expired. Please login again.']);
    exit;
}

require_once(__DIR__ . '/../../connection.php');
require_once(__DIR__ . '/../functions/hrm_FunctionMaster.php');
require_once(__DIR__ . '/../functions/admin_functionmaster.php');
require_once(__DIR__ . '/../functions/comanphp_functions.php');
require_once(__DIR__ . '/../functions/hrm_loginFunctions.php');

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

/* ── FETCH (load all records) ──────────────────────────────────────────────── */
if ($action === 'fetch') {
    $projectid = trim($_POST['projectid'] ?? '');
    $where = "";
    if (!empty($projectid)) {
        $pid = mysqli_real_escape_string($hrmconn, $projectid);
        $where = " WHERE projectid = '$pid'";
    }

    $records = [];
    $res = mysqli_query($hrmconn, "SELECT * FROM hrm_attverify_locationsheets{$where} ORDER BY sno DESC");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $records[] = $row;
        }
    }

    $total    = count($records);
    $active   = count(array_filter($records, fn($r) => $r['status'] == 1));
    $inactive = $total - $active;
    $projects = count(array_unique(array_filter(array_column($records, 'projectid'))));

    echo json_encode([
        'success'  => true,
        'records'  => $records,
        'total'    => $total,
        'active'   => $active,
        'inactive' => $inactive,
        'projects' => $projects
    ]);
    exit;
}

/* ── ADD ───────────────────────────────────────────────────────────────────── */
if ($action === 'add') {
    $projectid    = mysqli_real_escape_string($hrmconn, trim($_POST['projectid']    ?? ''));
    $locationid   = mysqli_real_escape_string($hrmconn, trim($_POST['locationid']   ?? ''));
    $locationname = mysqli_real_escape_string($hrmconn, trim($_POST['locationname'] ?? ''));
    $gsid         = mysqli_real_escape_string($hrmconn, trim($_POST['googlesheetid'] ?? ''));
    $sheetname    = mysqli_real_escape_string($hrmconn, trim($_POST['sheetname']    ?? 'Sheet1'));
    $status       = (int) ($_POST['status'] ?? 1);

    if (empty($gsid) || empty($locationid)) {
        echo json_encode(['success' => false, 'msg' => 'Location ID and Google Sheet ID are required.']);
        exit;
    }

    // Check duplicate: 1 location can have only 1 google sheet
    $dupCheck = mysqli_query($hrmconn,
        "SELECT sno, googlesheetid FROM hrm_attverify_locationsheets
         WHERE locationid = '$locationid' AND projectid = '$projectid' LIMIT 1");
    if ($dupCheck && mysqli_num_rows($dupCheck) > 0) {
        $existing = mysqli_fetch_assoc($dupCheck);
        echo json_encode(['success' => false, 'msg' => 'This location already has a Google Sheet assigned (SNO #' . $existing['sno'] . '). Please edit the existing record instead.']);
        exit;
    }

    $sql = "INSERT INTO hrm_attverify_locationsheets
                (projectid, locationid, locationname, googlesheetid, sheetname, status)
            VALUES ('$projectid', '$locationid', '$locationname', '$gsid', '$sheetname', $status)";

    if (mysqli_query($hrmconn, $sql)) {
        echo json_encode(['success' => true, 'msg' => 'Record added successfully.', 'id' => mysqli_insert_id($hrmconn)]);
    } else {
        echo json_encode(['success' => false, 'msg' => 'DB Error: ' . mysqli_error($hrmconn)]);
    }
    exit;
}

/* ── EDIT ──────────────────────────────────────────────────────────────────── */
if ($action === 'edit') {
    $sno          = (int) ($_POST['sno']          ?? 0);
    $projectid    = mysqli_real_escape_string($hrmconn, trim($_POST['projectid']    ?? ''));
    $locationid   = mysqli_real_escape_string($hrmconn, trim($_POST['locationid']   ?? ''));
    $locationname = mysqli_real_escape_string($hrmconn, trim($_POST['locationname'] ?? ''));
    $gsid         = mysqli_real_escape_string($hrmconn, trim($_POST['googlesheetid'] ?? ''));
    $sheetname    = mysqli_real_escape_string($hrmconn, trim($_POST['sheetname']    ?? 'Sheet1'));
    $status       = (int) ($_POST['status'] ?? 1);

    if (!$sno || empty($gsid) || empty($locationid)) {
        echo json_encode(['success' => false, 'msg' => 'Missing required fields.']);
        exit;
    }

    // Check duplicate: 1 location can have only 1 google sheet (exclude current record)
    $dupCheck = mysqli_query($hrmconn,
        "SELECT sno FROM hrm_attverify_locationsheets
         WHERE locationid = '$locationid' AND projectid = '$projectid' AND sno != $sno LIMIT 1");
    if ($dupCheck && mysqli_num_rows($dupCheck) > 0) {
        $existing = mysqli_fetch_assoc($dupCheck);
        echo json_encode(['success' => false, 'msg' => 'Another record (SNO #' . $existing['sno'] . ') already uses this location. Please edit that record instead.']);
        exit;
    }

    $sql = "UPDATE hrm_attverify_locationsheets SET
                projectid     = '$projectid',
                locationid    = '$locationid',
                locationname  = '$locationname',
                googlesheetid = '$gsid',
                sheetname     = '$sheetname',
                status        = $status
            WHERE sno = $sno";

    if (mysqli_query($hrmconn, $sql)) {
        echo json_encode(['success' => true, 'msg' => 'Record updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'DB Error: ' . mysqli_error($hrmconn)]);
    }
    exit;
}

/* ── DELETE ─────────────────────────────────────────────────────────────────── */
if ($action === 'delete') {
    $sno = (int) ($_POST['sno'] ?? 0);
    if (!$sno) { echo json_encode(['success' => false, 'msg' => 'Invalid record ID.']); exit; }

    $sql = "DELETE FROM hrm_attverify_locationsheets WHERE sno = $sno";
    if (mysqli_query($hrmconn, $sql)) {
        echo json_encode(['success' => true, 'msg' => 'Record deleted successfully.']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'DB Error: ' . mysqli_error($hrmconn)]);
    }
    exit;
}

/* ── TOGGLE STATUS ─────────────────────────────────────────────────────────── */
if ($action === 'toggle_status') {
    $sno    = (int) ($_POST['sno']    ?? 0);
    $status = (int) ($_POST['status'] ?? 1);
    $sql = "UPDATE hrm_attverify_locationsheets SET status = $status WHERE sno = $sno";
    if (mysqli_query($hrmconn, $sql)) {
        echo json_encode(['success' => true, 'msg' => 'Status updated.']);
    } else {
        echo json_encode(['success' => false, 'msg' => 'DB Error: ' . mysqli_error($hrmconn)]);
    }
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Unknown action.']);
exit;
?>
