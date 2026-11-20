<?php
// districts.php – returns live active list of districts for a given state_id as JSON
header('Content-Type: application/json');
require_once "db_connect.php";

$stateId = $_GET['state_id'] ?? null;
$districts = [];

if ($stateId) {
    $stmt = $krfconn->prepare("SELECT districtid AS id, districtname AS name FROM master_districts WHERE stateid = ? AND status = 'Active' ORDER BY districtname");
    if ($stmt) {
        $stmt->bind_param('s', $stateId);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $districts[] = $row;
        }
        $stmt->close();
    }
}

echo json_encode($districts);
?>
