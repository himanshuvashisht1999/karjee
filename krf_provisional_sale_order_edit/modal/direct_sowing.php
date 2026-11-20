<?php
require_once __DIR__.'/../../connection.php';
date_default_timezone_set('Asia/Kolkata');
$action=$_GET['action']??'';
if ($action === "getstate_district") { 
    $stmt = $krfconn->prepare("
        SELECT s.stateid, s.statename,s.statename_hn,
           d.districtid, d.districtname,d.districtname_hn
        FROM master_states s join  
           master_districts d
         on s.stateid =d.stateid
        WHERE s.statename IS NOT NULL
          AND s.statename <> ''
        ORDER BY s.statename, d.districtname ASC
    ");
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Prepare failed: ' . $krfconn->error,
            'data'    => []
        ]);
        exit;
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $states = [];
    while ($row = $result->fetch_assoc()) {
        $states[] = $row;
    }
    $stmt->close();
    echo json_encode([
        'status'  => 'success',
        'message' => 'States fetched successfully',
        'data'    => $states
    ]);

    exit;
}


