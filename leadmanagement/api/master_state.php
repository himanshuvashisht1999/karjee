<?php
// api/master_state.php – returns list of states from master_state table
include("../connection.php");

$sql = "SELECT StateID as id, StateName as name FROM master_state ORDER BY StateName";
$result = $krcconn->query($sql);
$states = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $states[] = ['id' => $row['id'], 'name' => $row['name']];
    }
}
header('Content-Type: application/json');
echo json_encode($states);
?>
