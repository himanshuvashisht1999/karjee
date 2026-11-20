<?php
ini_set('display_errors','0');
require __DIR__ . '/../../connection.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $result = $krfconn->query('SELECT s.stateid,s.statename,s.statename_hn,d.districtid,d.districtname,d.districtname_hn FROM master_states s LEFT JOIN master_districts d ON d.stateid=s.stateid ORDER BY s.statename,d.districtname');
    if (!$result) throw new RuntimeException($krfconn->error);
    echo json_encode(['status'=>'success','states'=>$result->fetch_all(MYSQLI_ASSOC)]);
} catch(Throwable $error) {http_response_code(500);echo json_encode(['status'=>'error','message'=>'Unable to load states and districts.']);}
