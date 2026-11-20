<?php
// function/state_functions.php – helper functions to fetch state and district data

/**
 * Retrieve all active states.
 *
 * @param mysqli $conn Active DB connection.
 * @return array       Array of states (id, name) or ['error'=>...]
 */
function getAllStates(mysqli $conn): array {
    $sql = "SELECT stateid AS id, statename AS name FROM master_states WHERE status = 'Active' ORDER BY statename";
    $result = $conn->query($sql);
    if ($result === false) {
        return ['error' => $conn->error];
    }
    $states = [];
    while ($row = $result->fetch_assoc()) {
        $states[] = $row;
    }
    return $states;
}

/**
 * Retrieve districts for a given state id.
 *
 * @param mysqli $conn    Active DB connection.
 * @param string $stateId State identifier (as stored in master_states).
 * @return array           Array of districts (id, name) or ['error'=>...]
 */
function getDistrictsByState(mysqli $conn, string $stateId): array {
    $stmt = $conn->prepare(
        "SELECT districtid AS id, districtname AS name FROM master_districts WHERE stateid = ? AND status = 'Active' ORDER BY districtname"
    );
    if ($stmt === false) {
        return ['error' => $conn->error];
    }
    $stmt->bind_param('s', $stateId);
    $stmt->execute();
    $result = $stmt->get_result();
    $districts = [];
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    $stmt->close();
    return $districts;
}
?>
