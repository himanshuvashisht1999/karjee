<?php
// states.php – returns live active list of states as JSON
header('Content-Type: application/json');
require_once "db_connect.php";

$sql = "SELECT stateid AS id, statename AS name FROM master_states WHERE status = 'Active' ORDER BY statename";
$result = $krfconn->query($sql);
$states = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $states[] = $row;
    }
}
echo json_encode($states);
?>
