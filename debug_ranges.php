<?php
require_once 'connection.php';
$projectid = $_GET['projectid'] ?? 1;

echo "<h3>All Freight Ranges for Project ID: $projectid</h3>";
$res = $krfconn->query("SELECT DISTINCT dist_min, dist_max FROM krf_freight_master WHERE projectid = $projectid ORDER BY dist_min ASC");
if ($res) {
    echo "<table border='1'><tr><th>Min</th><th>Max</th></tr>";
    while ($row = $res->fetch_assoc()) {
        echo "<tr><td>{$row['dist_min']}</td><td>{$row['dist_max']}</td></tr>";
    }
    echo "</table>";
} else {
    echo "Error: " . $krfconn->error;
}
?>