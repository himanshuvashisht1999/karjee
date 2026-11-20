<?php

function getDataByCondition(mysqli $conn, string $projectid, string $shortby, string $shortbyvalue): array {
    $allowedColumns = [
        'CategoryId', 'ProductTypeID', 'sku', 'BatchNo', 'HSNCode',
        'SeedId', 'ScionSeedId', 'trayCategoryId', 'IsGrafted',
        'Status', 'CreatedAt', 'CreatedBy', 'UpdateAt'
    ];
    if (!in_array($shortby, $allowedColumns, true)) {
        return ['error' => 'Invalid column name supplied'];
    }
    $sql = "SELECT * FROM product_master WHERE ProjectId = ? AND $shortby = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return ['error' => $conn->error];
    }
    $stmt->bind_param('ss', $projectid, $shortbyvalue);
    $stmt->execute();
    $result = $stmt->get_result();
    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
    }
    $stmt->close();
    return $rows;
}

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

function getActiveProducts(mysqli $conn): array {
    $sql = "SELECT ProductID AS id, ProductName AS name FROM product_master WHERE Status = 'Active' ORDER BY ProductName";
    $result = $conn->query($sql);
    if ($result === false) {
        return ['error' => $conn->error];
    }
    $products = [];
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    return $products;
}
?>
