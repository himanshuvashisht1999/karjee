<?php
// search_clients.php – searches client master database by phone substring and returns dynamic matched lists
header('Content-Type: application/json');
require_once "db_connect.php";

$query = $_GET['q'] ?? '';
$clients = [];

if (strlen($query) >= 5) {
    $searchTerm = "%" . $query . "%";
    $sql = "
        SELECT DISTINCT
            c.client_name,
            c.phones,
            c.statename,
            c.districtname,
            COALESCE(s.stateid, '') AS state_id,
            COALESCE(d.districtid, '') AS district_id,
            c.invoicing_address AS city
        FROM krf_client_master c
        LEFT JOIN master_states s ON LOWER(TRIM(c.statename)) = LOWER(TRIM(s.statename))
        LEFT JOIN master_districts d ON LOWER(TRIM(c.districtname)) = LOWER(TRIM(d.districtname)) 
            AND (s.stateid IS NULL OR d.stateid = s.stateid)
        WHERE c.phones LIKE ? 
           OR c.client_name LIKE ?
        LIMIT 10
    ";
    
    $stmt = $krfconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $clients[] = [
                'client_name'   => $row['client_name'],
                'phones'        => $row['phones'],
                'statename'     => $row['statename'],
                'districtname'  => $row['districtname'],
                'state_id'      => $row['state_id'],
                'district_id'   => $row['district_id'],
                'city'          => $row['city']
            ];
        }
        $stmt->close();
    }
}

echo json_encode($clients);
?>
