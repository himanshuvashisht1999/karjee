<?php
require __DIR__ . '/../../connection.php';
header('Content-Type: application/json; charset=utf-8');
$stmt = null;
try {
    $projectid = trim((string)($_POST['projectid'] ?? ''));
    $clientid = trim((string)($_POST['clientid'] ?? ''));
    $exclude = trim((string)($_POST['exclude_psono'] ?? ''));
    $productid = trim((string)($_POST['productid'] ?? ''));
    if ($projectid === '' || $clientid === '') {
        echo json_encode(['status' => 'success', 'items' => []]);
        exit;
    }
    $sql = "SELECT p.psono, p.created_by, s.product_id, s.product_name, s.quantity, s.rate
            FROM krf_provisional_sales_order p
            JOIN krf_provisional_sales_order_items s ON s.psono=p.psono AND s.projectid=p.projectid
            WHERE p.projectid=? AND p.clientid=? AND p.status='Draft'";
    if ($exclude !== '') $sql .= ' AND p.psono<>?';
    if ($productid !== '') $sql .= ' AND s.product_id=?';
    $sql .= ' ORDER BY p.id DESC, s.id';
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) throw new RuntimeException($krfconn->error);
    if ($exclude !== '' && $productid !== '') $stmt->bind_param('ssss', $projectid, $clientid, $exclude, $productid);
    elseif ($exclude !== '') $stmt->bind_param('sss', $projectid, $clientid, $exclude);
    elseif ($productid !== '') $stmt->bind_param('sss', $projectid, $clientid, $productid);
    else $stmt->bind_param('ss', $projectid, $clientid);
    if (!$stmt->execute()) throw new RuntimeException($stmt->error);
    echo json_encode(['status' => 'success', 'items' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to fetch Draft orders.']);
} finally {
    if ($stmt) $stmt->close();
}
