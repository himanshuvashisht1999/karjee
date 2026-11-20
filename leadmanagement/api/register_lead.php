<?php
// register_lead.php – registers a new lead with multiple products in the database
header('Content-Type: application/json');
require_once "db_connect.php";

// Self-healing: create table if it doesn't exist (with token_no)
$createTableSql = "
CREATE TABLE IF NOT EXISTS krf_leads (
    leadid INT AUTO_INCREMENT PRIMARY KEY,
    token_no VARCHAR(50) DEFAULT NULL,
    name VARCHAR(255) NOT NULL,
    contact VARCHAR(20) NOT NULL,
    state_id INT NOT NULL,
    district_id INT NOT NULL,
    city VARCHAR(255) NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";
$krcconn->query($createTableSql);

// Double-check column to support dynamic self-healing upgrade of existing installations
$checkCol = $krcconn->query("SHOW COLUMNS FROM krf_leads LIKE 'token_no'");
if ($checkCol && $checkCol->num_rows == 0) {
    $krcconn->query("ALTER TABLE krf_leads ADD COLUMN token_no VARCHAR(50) DEFAULT NULL AFTER leadid");
}

// Receive JSON payload
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit;
}

$name = $data['name'] ?? '';
$contact = $data['contact'] ?? '';
$state_id = intval($data['state'] ?? 0);
$district_id = intval($data['district'] ?? 0);
$city = $data['city'] ?? '';
$remark = $data['remark'] ?? '';

// Single product fallbacks for legacy/direct submissions
$product_id = intval($data['product'] ?? 0);
$quantity = intval($data['quantity'] ?? 0);

$products = $data['products'] ?? [];
if (empty($products) && $product_id > 0 && $quantity > 0) {
    $products[] = [
        'product_id' => $product_id,
        'quantity' => $quantity
    ];
}

if (empty($name) || empty($contact) || empty($city) || $state_id <= 0 || $district_id <= 0 || empty($products)) {
    echo json_encode(['success' => false, 'message' => 'Missing or invalid fields. Please make sure to add at least one product.']);
    exit;
}

// Generate an elegant, highly-trackable tracking token for the follow-up process
$today = date('Ymd');
$random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 4));
$token_no = "ENQ-" . $today . "-" . $random;

$krcconn->begin_transaction();
try {
    $stmt = $krcconn->prepare("INSERT INTO krf_leads (token_no, name, contact, state_id, district_id, city, product_id, quantity, remark) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $krcconn->error);
    }
    
    foreach ($products as $p) {
        $p_id = intval($p['product_id'] ?? 0);
        $qty = intval($p['quantity'] ?? 0);
        if ($p_id <= 0 || $qty <= 0) {
            throw new Exception("Invalid Product Selection or Quantity in list.");
        }
        $stmt->bind_param("sssiisiis", $token_no, $name, $contact, $state_id, $district_id, $city, $p_id, $qty, $remark);
        if (!$stmt->execute()) {
            throw new Exception("Database execution failed: " . $stmt->error);
        }
    }
    $stmt->close();
    $krcconn->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Lead enquiry registered successfully', 
        'token_no' => $token_no
    ]);
} catch (Exception $e) {
    $krcconn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
