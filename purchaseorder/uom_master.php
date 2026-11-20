<?php
// C:\xampp\htdocs\krf_amanaccounting\purchaseorder\uom_master.php

// 1. Include Database Connection
if (file_exists("../connection.php")) {
    include "../connection.php";
} else if (file_exists("../../connection.php")) {
    include "../../connection.php";
} else if (file_exists("connection.php")) {
    include "connection.php";
}

// Fallback db connection handling
$dbConn = null;
if (isset($conn) && $conn instanceof mysqli) {
    $dbConn = $conn;
} else if (isset($krfconn) && $krfconn instanceof mysqli) {
    $dbConn = $krfconn;
} else if (isset($mconn) && $mconn instanceof mysqli) {
    $dbConn = $mconn;
}

if (!$dbConn) {
    die("Database Connection Error. Please check connection.php.");
}

// 2. Auto-Create Table if not exists
$tableSql = "CREATE TABLE IF NOT EXISTS inventory_uom_master (
    uom_id INT AUTO_INCREMENT PRIMARY KEY,
    uom_code VARCHAR(20) NOT NULL UNIQUE,
    uom_name VARCHAR(100) NOT NULL,
    uom_symbol VARCHAR(20) NOT NULL,
    uom_type ENUM(
        'QUANTITY',
        'WEIGHT',
        'VOLUME',
        'LENGTH',
        'AREA',
        'TIME',
        'OTHER'
    ) NOT NULL,
    decimal_places TINYINT DEFAULT 2,
    status TINYINT DEFAULT 1,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_by INT NULL,
    updated_at DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

$dbConn->query($tableSql);

// 3. Auto-Seed Initial UOM List if Table is Empty or upon request
function seedDefaultUOMs($conn) {
    $default_uoms = [
        // QUANTITY
        ['code' => 'NOS', 'name' => 'Numbers', 'symbol' => 'Nos', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'PCS', 'name' => 'Pieces', 'symbol' => 'Pcs', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'PKT', 'name' => 'Packet', 'symbol' => 'Pkt', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'BAG', 'name' => 'Bag', 'symbol' => 'Bag', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'BOX', 'name' => 'Box', 'symbol' => 'Box', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'CTN', 'name' => 'Carton', 'symbol' => 'Ctn', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'BDL', 'name' => 'Bundle', 'symbol' => 'Bdl', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'ROL', 'name' => 'Roll', 'symbol' => 'Roll', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'TRY', 'name' => 'Tray', 'symbol' => 'Tray', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'BTL', 'name' => 'Bottle', 'symbol' => 'Btl', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'CAN', 'name' => 'Can', 'symbol' => 'Can', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'DRM', 'name' => 'Drum', 'symbol' => 'Drum', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'PCH', 'name' => 'Pouch', 'symbol' => 'Pouch', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'SET', 'name' => 'Set', 'symbol' => 'Set', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'PAR', 'name' => 'Pair', 'symbol' => 'Pr', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'DZN', 'name' => 'Dozen', 'symbol' => 'Dzn', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'CRT', 'name' => 'Crate', 'symbol' => 'Crate', 'type' => 'QUANTITY', 'dec' => 0],
        ['code' => 'UNT', 'name' => 'Unit', 'symbol' => 'Unit', 'type' => 'QUANTITY', 'dec' => 0],

        // WEIGHT
        ['code' => 'KG', 'name' => 'Kilogram', 'symbol' => 'Kg', 'type' => 'WEIGHT', 'dec' => 2],
        ['code' => 'GM', 'name' => 'Gram', 'symbol' => 'Gm', 'type' => 'WEIGHT', 'dec' => 2],
        ['code' => 'MG', 'name' => 'Milligram', 'symbol' => 'mg', 'type' => 'WEIGHT', 'dec' => 2],
        ['code' => 'TON', 'name' => 'Metric Ton', 'symbol' => 'Ton', 'type' => 'WEIGHT', 'dec' => 3],
        ['code' => 'QTL', 'name' => 'Quintal', 'symbol' => 'Qtl', 'type' => 'WEIGHT', 'dec' => 2],

        // VOLUME
        ['code' => 'LTR', 'name' => 'Litre', 'symbol' => 'L', 'type' => 'VOLUME', 'dec' => 2],
        ['code' => 'ML', 'name' => 'Millilitre', 'symbol' => 'mL', 'type' => 'VOLUME', 'dec' => 2],
        ['code' => 'GAL', 'name' => 'Gallon', 'symbol' => 'Gal', 'type' => 'VOLUME', 'dec' => 2],
        ['code' => 'CC', 'name' => 'Cubic Centimeter', 'symbol' => 'cc', 'type' => 'VOLUME', 'dec' => 2],

        // LENGTH
        ['code' => 'MTR', 'name' => 'Meter', 'symbol' => 'm', 'type' => 'LENGTH', 'dec' => 2],
        ['code' => 'CM', 'name' => 'Centimeter', 'symbol' => 'cm', 'type' => 'LENGTH', 'dec' => 2],
        ['code' => 'MM', 'name' => 'Millimeter', 'symbol' => 'mm', 'type' => 'LENGTH', 'dec' => 2],
        ['code' => 'FT', 'name' => 'Feet', 'symbol' => 'ft', 'type' => 'LENGTH', 'dec' => 2],
        ['code' => 'INC', 'name' => 'Inch', 'symbol' => 'in', 'type' => 'LENGTH', 'dec' => 2],
        ['code' => 'YRD', 'name' => 'Yard', 'symbol' => 'yd', 'type' => 'LENGTH', 'dec' => 2],

        // AREA
        ['code' => 'SQF', 'name' => 'Square Feet', 'symbol' => 'Sq Ft', 'type' => 'AREA', 'dec' => 2],
        ['code' => 'SQM', 'name' => 'Square Meter', 'symbol' => 'Sq M', 'type' => 'AREA', 'dec' => 2],
        ['code' => 'ACR', 'name' => 'Acre', 'symbol' => 'Acre', 'type' => 'AREA', 'dec' => 2],
        ['code' => 'HEC', 'name' => 'Hectare', 'symbol' => 'Ha', 'type' => 'AREA', 'dec' => 2],

        // TIME
        ['code' => 'HRS', 'name' => 'Hours', 'symbol' => 'hrs', 'type' => 'TIME', 'dec' => 2],
        ['code' => 'DAY', 'name' => 'Days', 'symbol' => 'days', 'type' => 'TIME', 'dec' => 0],
        ['code' => 'MON', 'name' => 'Months', 'symbol' => 'mo', 'type' => 'TIME', 'dec' => 0],

        // OTHER
        ['code' => 'JOB', 'name' => 'Job / Work', 'symbol' => 'Job', 'type' => 'OTHER', 'dec' => 0],
        ['code' => 'SRV', 'name' => 'Service', 'symbol' => 'Srv', 'type' => 'OTHER', 'dec' => 0],
        ['code' => 'LOT', 'name' => 'Lot', 'symbol' => 'Lot', 'type' => 'OTHER', 'dec' => 0],
    ];

    $stmt = $conn->prepare("INSERT IGNORE INTO inventory_uom_master (uom_code, uom_name, uom_symbol, uom_type, decimal_places, status) VALUES (?, ?, ?, ?, ?, 1)");
    if ($stmt) {
        foreach ($default_uoms as $u) {
            $stmt->bind_param("ssssi", $u['code'], $u['name'], $u['symbol'], $u['type'], $u['dec']);
            $stmt->execute();
        }
        $stmt->close();
    }
}

// Auto seed if empty
$chkResult = $dbConn->query("SELECT COUNT(*) as cnt FROM inventory_uom_master");
if ($chkResult) {
    $row = $chkResult->fetch_assoc();
    if (isset($row['cnt']) && (int)$row['cnt'] === 0) {
        seedDefaultUOMs($dbConn);
    }
}

// 4. AJAX API Endpoints
if (isset($_REQUEST['action'])) {
    header('Content-Type: application/json');
    $action = $_REQUEST['action'];

    // FETCH UOM LIST
    if ($action === 'fetch_uoms') {
        $search = isset($_POST['search']) ? trim($_POST['search']) : '';
        $type = isset($_POST['type']) ? trim($_POST['type']) : '';
        $status = isset($_POST['status']) ? trim($_POST['status']) : '';

        $sql = "SELECT * FROM inventory_uom_master WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($search)) {
            $sql .= " AND (uom_code LIKE ? OR uom_name LIKE ? OR uom_symbol LIKE ?)";
            $searchTerm = "%{$search}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $types .= "sss";
        }

        if (!empty($type) && $type !== 'ALL') {
            $sql .= " AND uom_type = ?";
            $params[] = $type;
            $types .= "s";
        }

        if ($status !== '' && $status !== 'ALL') {
            $sql .= " AND status = ?";
            $params[] = (int)$status;
            $types .= "i";
        }

        $sql .= " ORDER BY uom_type ASC, uom_code ASC";

        $stmt = $dbConn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $list = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Calculate summary stats
        $statsRes = $dbConn->query("SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status=1 THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status=0 THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN uom_type='QUANTITY' THEN 1 ELSE 0 END) as qty_count,
            SUM(CASE WHEN uom_type='WEIGHT' THEN 1 ELSE 0 END) as weight_count,
            SUM(CASE WHEN uom_type='VOLUME' THEN 1 ELSE 0 END) as volume_count
            FROM inventory_uom_master");
        $stats = $statsRes ? $statsRes->fetch_assoc() : [];

        echo json_encode([
            'status' => 'success',
            'data' => $list,
            'stats' => $stats
        ]);
        exit;
    }

    // GET SINGLE UOM DETAILS FOR EDIT
    if ($action === 'get_uom') {
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 0;
        $stmt = $dbConn->prepare("SELECT * FROM inventory_uom_master WHERE uom_id = ?");
        $stmt->bind_param("i", $uom_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($res) {
            echo json_encode(['status' => 'success', 'data' => $res]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'UOM not found.']);
        }
        exit;
    }

    // SAVE / UPDATE UOM
    if ($action === 'save_uom') {
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 0;
        $uom_code = strtoupper(trim($_POST['uom_code'] ?? ''));
        $uom_name = trim($_POST['uom_name'] ?? '');
        $uom_symbol = trim($_POST['uom_symbol'] ?? '');
        $uom_type = trim($_POST['uom_type'] ?? 'QUANTITY');
        $decimal_places = isset($_POST['decimal_places']) ? (int)$_POST['decimal_places'] : 2;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
        $loginid = isset($_SESSION['loginid']) ? (int)$_SESSION['loginid'] : NULL;

        if (empty($uom_code) || empty($uom_name) || empty($uom_symbol)) {
            echo json_encode(['status' => 'error', 'message' => 'UOM Code, Name, and Symbol are required fields.']);
            exit;
        }

        // Check for duplicate UOM Code
        $dupStmt = $dbConn->prepare("SELECT uom_id FROM inventory_uom_master WHERE uom_code = ? AND uom_id != ?");
        $dupStmt->bind_param("si", $uom_code, $uom_id);
        $dupStmt->execute();
        $dupRes = $dupStmt->get_result();
        if ($dupRes->num_rows > 0) {
            $dupStmt->close();
            echo json_encode(['status' => 'error', 'message' => "UOM Code '{$uom_code}' already exists!"]);
            exit;
        }
        $dupStmt->close();

        if ($uom_id > 0) {
            // Update
            $uSql = "UPDATE inventory_uom_master SET uom_code=?, uom_name=?, uom_symbol=?, uom_type=?, decimal_places=?, status=?, updated_by=?, updated_at=NOW() WHERE uom_id=?";
            $uStmt = $dbConn->prepare($uSql);
            $uStmt->bind_param("ssssiiii", $uom_code, $uom_name, $uom_symbol, $uom_type, $decimal_places, $status, $loginid, $uom_id);
            if ($uStmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => "UOM '{$uom_code}' updated successfully."]);
            } else {
                echo json_encode(['status' => 'error', 'message' => "Failed to update UOM: " . $dbConn->error]);
            }
            $uStmt->close();
        } else {
            // Insert
            $iSql = "INSERT INTO inventory_uom_master (uom_code, uom_name, uom_symbol, uom_type, decimal_places, status, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $iStmt = $dbConn->prepare($iSql);
            $iStmt->bind_param("ssssiii", $uom_code, $uom_name, $uom_symbol, $uom_type, $decimal_places, $status, $loginid);
            if ($iStmt->execute()) {
                echo json_encode(['status' => 'success', 'message' => "UOM '{$uom_code}' added successfully."]);
            } else {
                echo json_encode(['status' => 'error', 'message' => "Failed to add UOM: " . $dbConn->error]);
            }
            $iStmt->close();
        }
        exit;
    }

    // TOGGLE STATUS
    if ($action === 'toggle_status') {
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 0;
        $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
        $loginid = isset($_SESSION['loginid']) ? (int)$_SESSION['loginid'] : NULL;

        $stmt = $dbConn->prepare("UPDATE inventory_uom_master SET status = ?, updated_by = ?, updated_at = NOW() WHERE uom_id = ?");
        $stmt->bind_param("iii", $status, $loginid, $uom_id);
        if ($stmt->execute()) {
            $statusText = $status === 1 ? 'Activated' : 'Deactivated';
            echo json_encode(['status' => 'success', 'message' => "UOM status updated to {$statusText}."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Failed to update status."]);
        }
        $stmt->close();
        exit;
    }

    // DELETE UOM
    if ($action === 'delete_uom') {
        $uom_id = isset($_POST['uom_id']) ? (int)$_POST['uom_id'] : 0;
        $stmt = $dbConn->prepare("DELETE FROM inventory_uom_master WHERE uom_id = ?");
        $stmt->bind_param("i", $uom_id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => "UOM deleted successfully."]);
        } else {
            echo json_encode(['status' => 'error', 'message' => "Failed to delete UOM."]);
        }
        $stmt->close();
        exit;
    }

    // RE-SEED DEFAULTS
    if ($action === 'reseed_defaults') {
        seedDefaultUOMs($dbConn);
        echo json_encode(['status' => 'success', 'message' => "All standard UOMs successfully seeded/restored."]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unit of Measurement (UOM) Master - Inventory Management</title>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            --glass-bg: rgba(255, 255, 255, 0.95);
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.06);
            --border-radius: 14px;
        }

        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background-color: #f4f6f9;
            color: #1e293b;
            font-size: 0.88rem;
            letter-spacing: -0.01em;
            padding-bottom: 3rem;
        }

        .font-monospace, .code-pill, .symbol-badge {
            font-family: 'JetBrains Mono', monospace !important;
        }

        .main-container {
            max-width: 1400px;
            margin: auto;
            padding: 1.25rem;
        }

        /* Top Header Card */
        .page-header-card {
            background: var(--primary-gradient);
            border-radius: var(--border-radius);
            padding: 1.25rem 1.75rem;
            color: white;
            box-shadow: 0 12px 25px rgba(30, 60, 114, 0.2);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-header-card h4 {
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }

        .header-icon-circle {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(8px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        /* Stats Cards */
        .kpi-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem 1.25rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #eef2f5;
            transition: all 0.3s ease;
            height: 100%;
        }

        .kpi-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.08);
        }

        .kpi-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .kpi-value {
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.2rem;
        }

        .kpi-label {
            font-size: 0.78rem;
            color: #6c757d;
            font-weight: 600;
            text-transform: uppercase;
        }

        /* Content Card */
        .content-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #eef2f5;
        }

        /* Filter Section */
        .filter-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.25rem;
        }

        /* Data Table Styling */
        .table-custom {
            margin-bottom: 0;
        }

        .table-custom th {
            background-color: #f1f5f9;
            color: #475569;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 0.85rem 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .table-custom td {
            padding: 0.85rem 1rem;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        .uom-code-badge {
            font-family: monospace;
            font-weight: 700;
            font-size: 0.85rem;
            padding: 0.35rem 0.65rem;
            border-radius: 6px;
            background: #1e293b;
            color: #f8fafc;
            letter-spacing: 0.5px;
        }

        /* Type Badges */
        .badge-type-QUANTITY { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
        .badge-type-WEIGHT { background-color: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; }
        .badge-type-VOLUME { background-color: #f3e8ff; color: #7e22ce; border: 1px solid #e9d5ff; }
        .badge-type-LENGTH { background-color: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; }
        .badge-type-AREA { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
        .badge-type-TIME { background-color: #ffe4e6; color: #be123c; border: 1px solid #fecdd3; }
        .badge-type-OTHER { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

        .type-badge {
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.3rem 0.65rem;
            border-radius: 8px;
        }

        /* Custom Switches */
        .form-switch .form-check-input {
            width: 2.5em;
            height: 1.3em;
            cursor: pointer;
        }

        .form-switch .form-check-input:checked {
            background-color: #10b981;
            border-color: #10b981;
        }

        /* Buttons */
        .btn-premium-primary {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            font-weight: 600;
            border-radius: 10px;
            padding: 0.5rem 1.25rem;
            border: none;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.25);
            transition: all 0.3s ease;
        }

        .btn-premium-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(13, 110, 253, 0.35);
            color: white;
        }

        /* Modal Customization */
        .modal-content-custom {
            border-radius: 18px;
            border: none;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .modal-header-custom {
            background: var(--primary-gradient);
            color: white;
            padding: 1.25rem 1.5rem;
            border: none;
        }

        .modal-header-custom .btn-close {
            filter: brightness(0) invert(1);
        }
    </style>
</head>

<body>

    <div class="main-container">

        <!-- Page Header -->
        <div class="page-header-card">
            <div class="d-flex align-items-center gap-3">
                <div class="header-icon-circle">
                    <i class="bi bi-rulers"></i>
                </div>
                <div>
                    <h4 class="mb-1">Unit of Measurement (UOM) Master</h4>
                    <p class="mb-0 opacity-75 small">Manage inventory units, measurement types, symbols & decimal precision</p>
                </div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <button class="btn btn-light btn-sm fw-600 rounded-3 py-2 px-3 shadow-sm" id="btnReseedDefaults" title="Restore / Add Default UOMs">
                    <i class="bi bi-arrow-counterclockwise text-primary me-1"></i> Seed Standard UOMs
                </button>
                <button class="btn btn-premium-primary d-flex align-items-center gap-2" id="btnAddNewUOM">
                    <i class="bi bi-plus-circle-fill"></i> Add New UOM
                </button>
            </div>
        </div>

        <!-- KPI Summary Cards -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-primary-subtle text-primary">
                        <i class="bi bi-boxes"></i>
                    </div>
                    <div class="kpi-value text-primary" id="kpiTotal">0</div>
                    <div class="kpi-label">Total UOM Records</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-success-subtle text-success">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <div class="kpi-value text-success" id="kpiActive">0</div>
                    <div class="kpi-label">Active Units</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-warning-subtle text-warning-emphasis">
                        <i class="bi bi-basket3-fill"></i>
                    </div>
                    <div class="kpi-value text-warning-emphasis" id="kpiQuantity">0</div>
                    <div class="kpi-label">Quantity & Package Units</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="kpi-card">
                    <div class="kpi-icon bg-purple-subtle text-purple" style="background:#f3e8ff; color:#7e22ce;">
                        <i class="bi bi-speedometer2"></i>
                    </div>
                    <div class="kpi-value" style="color:#7e22ce;" id="kpiWeight">0</div>
                    <div class="kpi-label">Weight & Volume Units</div>
                </div>
            </div>
        </div>

        <!-- Main Content Card -->
        <div class="content-card">

            <!-- Filter Section -->
            <div class="filter-box">
                <div class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-search"></i></span>
                            <input type="text" class="form-control border-start-0 ps-0" id="filterSearch" placeholder="Search by UOM Code, Name or Symbol...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="filterType">
                            <option value="ALL">All Measurement Types (ENUM)</option>
                            <option value="QUANTITY">QUANTITY (Count / Packages)</option>
                            <option value="WEIGHT">WEIGHT (Mass)</option>
                            <option value="VOLUME">VOLUME (Liquid / Capacity)</option>
                            <option value="LENGTH">LENGTH (Linear Dimensions)</option>
                            <option value="AREA">AREA (Surface)</option>
                            <option value="TIME">TIME (Duration)</option>
                            <option value="OTHER">OTHER (Specialized)</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="filterStatus">
                            <option value="ALL">All Statuses</option>
                            <option value="1" selected>Active Only</option>
                            <option value="0">Inactive Only</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table table-hover table-custom align-middle" id="uomTable">
                    <thead>
                        <tr>
                            <th style="width: 60px;"># ID</th>
                            <th style="width: 130px;">UOM Code</th>
                            <th>UOM Name</th>
                            <th style="width: 120px;">Symbol</th>
                            <th style="width: 160px;">UOM Type</th>
                            <th style="width: 130px;" class="text-center">Decimals</th>
                            <th style="width: 120px;" class="text-center">Status</th>
                            <th style="width: 130px;" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="uomTableBody">
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <div class="spinner-border text-primary spinner-border-sm me-2" role="status"></div>
                                Loading Unit of Measurement records...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- ADD / EDIT UOM MODAL -->
    <div class="modal fade" id="uomModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-custom">

                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-pencil-square me-2"></i>Add New UOM</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="uomForm">
                    <div class="modal-body p-4 bg-light">
                        <input type="hidden" id="uom_id" name="uom_id" value="0">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="uom_code" class="form-label fw-600 small text-uppercase">UOM Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control font-monospace fw-bold text-uppercase" id="uom_code" name="uom_code" required placeholder="e.g. KG, NOS, LTR" maxlength="20">
                                <div class="form-text" style="font-size: 0.75rem;">Unique code (e.g. KG, PKT)</div>
                            </div>

                            <div class="col-md-6">
                                <label for="uom_symbol" class="form-label fw-600 small text-uppercase">UOM Symbol <span class="text-danger">*</span></label>
                                <input type="text" class="form-control fw-bold" id="uom_symbol" name="uom_symbol" required placeholder="e.g. Kg, Nos, L, Pkt" maxlength="20">
                                <div class="form-text" style="font-size: 0.75rem;">Display symbol (e.g. Kg, Pkt)</div>
                            </div>

                            <div class="col-12">
                                <label for="uom_name" class="form-label fw-600 small text-uppercase">UOM Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="uom_name" name="uom_name" required placeholder="e.g. Kilogram, Packet, Litre" maxlength="100">
                            </div>

                            <div class="col-md-6">
                                <label for="uom_type" class="form-label fw-600 small text-uppercase">UOM Type (ENUM) <span class="text-danger">*</span></label>
                                <select class="form-select fw-600" id="uom_type" name="uom_type" required>
                                    <option value="QUANTITY">QUANTITY</option>
                                    <option value="WEIGHT">WEIGHT</option>
                                    <option value="VOLUME">VOLUME</option>
                                    <option value="LENGTH">LENGTH</option>
                                    <option value="AREA">AREA</option>
                                    <option value="TIME">TIME</option>
                                    <option value="OTHER">OTHER</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label for="decimal_places" class="form-label fw-600 small text-uppercase">Decimal Places</label>
                                <input type="number" class="form-control text-center fw-bold" id="decimal_places" name="decimal_places" value="2" min="0" max="4">
                                <div class="form-text" style="font-size: 0.75rem;">0 for count, 2 or 3 for weight</div>
                            </div>

                            <div class="col-12">
                                <label for="status" class="form-label fw-600 small text-uppercase">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="1" selected>Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer bg-white border-top-0 px-4 py-3">
                        <button type="button" class="btn btn-light px-4 rounded-3 fw-600" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 rounded-3 fw-600 shadow-sm" id="btnSaveUom">
                            <i class="bi bi-check2-circle me-1"></i> Save UOM Record
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        $(document).ready(function () {
            function getUomModal() {
                const modalEl = document.getElementById('uomModal');
                if (!modalEl) return null;
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    return bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                }
                return {
                    show: function () { $('#uomModal').modal('show'); },
                    hide: function () { $('#uomModal').modal('hide'); }
                };
            }

            // Initial load
            fetchUOMs();

            // Filter event listeners
            $("#filterSearch").on("keyup search", function () {
                fetchUOMs();
            });
            $("#filterType, #filterStatus").on("change", function () {
                fetchUOMs();
            });

            // Fetch UOMs
            function fetchUOMs() {
                const search = $("#filterSearch").val();
                const type = $("#filterType").val();
                const status = $("#filterStatus").val();

                $.ajax({
                    url: 'uom_master.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'fetch_uoms',
                        search: search,
                        type: type,
                        status: status
                    },
                    success: function (res) {
                        if (res.status === 'success') {
                            renderTable(res.data);
                            updateKPIs(res.stats);
                        } else {
                            $("#uomTableBody").html(`<tr><td colspan="8" class="text-center text-danger py-4">Failed to load data.</td></tr>`);
                        }
                    },
                    error: function () {
                        $("#uomTableBody").html(`<tr><td colspan="8" class="text-center text-danger py-4">Server error while loading UOM data.</td></tr>`);
                    }
                });
            }

            // Render Table Rows
            function renderTable(data) {
                let html = '';
                if (data.length === 0) {
                    html = `<tr><td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-50"></i>
                        No matching Unit of Measurement (UOM) records found.
                    </td></tr>`;
                } else {
                    data.forEach(item => {
                        const statusBadge = item.status == 1
                            ? `<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Active</span>`
                            : `<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Inactive</span>`;

                        const isChecked = item.status == 1 ? 'checked' : '';

                        html += `
                            <tr>
                                <td class="fw-bold text-muted">#${item.uom_id}</td>
                                <td><span class="uom-code-badge">${escapeHtml(item.uom_code)}</span></td>
                                <td class="fw-bold text-dark">${escapeHtml(item.uom_name)}</td>
                                <td class="fw-bold text-primary">${escapeHtml(item.uom_symbol)}</td>
                                <td>
                                    <span class="type-badge badge-type-${item.uom_type}">
                                        ${item.uom_type}
                                    </span>
                                </td>
                                <td class="text-center fw-bold">${item.decimal_places}</td>
                                <td class="text-center">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input status-toggle-switch" type="checkbox" data-id="${item.uom_id}" ${isChecked} title="Toggle Active Status">
                                    </div>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary px-2 py-1 me-1 edit-uom-btn" data-id="${item.uom_id}" title="Edit UOM">
                                        <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger px-2 py-1 delete-uom-btn" data-id="${item.uom_id}" data-code="${escapeHtml(item.uom_code)}" title="Delete UOM">
                                        <i class="fa-solid fa-trash-can me-1"></i> Delete
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $("#uomTableBody").html(html);
            }

            // Update KPI Cards
            function updateKPIs(stats) {
                if (!stats) return;
                $("#kpiTotal").text(stats.total || 0);
                $("#kpiActive").text(stats.active || 0);
                $("#kpiQuantity").text(stats.qty_count || 0);
                const weightVol = (parseInt(stats.weight_count || 0) + parseInt(stats.volume_count || 0));
                $("#kpiWeight").text(weightVol);
            }

            // Add New UOM Button Click
            $("#btnAddNewUOM").on("click", function () {
                $("#uomForm")[0].reset();
                $("#uom_id").val(0);
                $("#modalTitle").html('<i class="bi bi-plus-circle-fill me-2"></i>Add New UOM Record');
                $("#uom_code").prop("readonly", false);
                const modal = getUomModal();
                if (modal) modal.show();
            });

            // Edit UOM Click
            $(document).on("click", ".edit-uom-btn", function () {
                const uomId = $(this).data("id");
                $.ajax({
                    url: 'uom_master.php',
                    type: 'POST',
                    dataType: 'json',
                    data: { action: 'get_uom', uom_id: uomId },
                    success: function (res) {
                        if (res.status === 'success') {
                            const d = res.data;
                            $("#uom_id").val(d.uom_id);
                            $("#uom_code").val(d.uom_code);
                            $("#uom_name").val(d.uom_name);
                            $("#uom_symbol").val(d.uom_symbol);
                            $("#uom_type").val(d.uom_type);
                            $("#decimal_places").val(d.decimal_places);
                            $("#status").val(d.status);

                            $("#modalTitle").html('<i class="bi bi-pencil-square me-2"></i>Edit UOM Record (#' + d.uom_id + ')');
                            const modal = getUomModal();
                            if (modal) modal.show();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    }
                });
            });

            // Submit Form (Add / Edit)
            $("#uomForm").on("submit", function (e) {
                e.preventDefault();
                const formData = $(this).serialize() + '&action=save_uom';

                $("#btnSaveUom").prop("disabled", true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

                $.ajax({
                    url: 'uom_master.php',
                    type: 'POST',
                    dataType: 'json',
                    data: formData,
                    success: function (res) {
                        $("#btnSaveUom").prop("disabled", false).html('<i class="bi bi-check2-circle me-1"></i> Save UOM Record');
                        if (res.status === 'success') {
                            const modal = getUomModal();
                            if (modal) modal.hide();
                            Swal.fire({
                                icon: 'success',
                                title: 'Saved!',
                                text: res.message,
                                timer: 1500,
                                showConfirmButton: false
                            });
                            fetchUOMs();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                        }
                    },
                    error: function () {
                        $("#btnSaveUom").prop("disabled", false).html('<i class="bi bi-check2-circle me-1"></i> Save UOM Record');
                        Swal.fire('Error', 'Failed to communicate with server.', 'error');
                    }
                });
            });

            // Toggle Status Switch Click
            $(document).on("change", ".status-toggle-switch", function () {
                const uomId = $(this).data("id");
                const newStatus = $(this).is(":checked") ? 1 : 0;

                $.ajax({
                    url: 'uom_master.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'toggle_status',
                        uom_id: uomId,
                        status: newStatus
                    },
                    success: function (res) {
                        if (res.status === 'success') {
                            fetchUOMs();
                        } else {
                            Swal.fire('Error', res.message, 'error');
                            fetchUOMs();
                        }
                    }
                });
            });

            // Delete UOM Click
            $(document).on("click", ".delete-uom-btn", function () {
                const uomId = $(this).data("id");
                const code = $(this).data("code");

                Swal.fire({
                    title: 'Delete UOM Record?',
                    text: `Are you sure you want to delete '${code}'?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Yes, Delete It!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'uom_master.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { action: 'delete_uom', uom_id: uomId },
                            success: function (res) {
                                if (res.status === 'success') {
                                    Swal.fire('Deleted!', res.message, 'success');
                                    fetchUOMs();
                                } else {
                                    Swal.fire('Error', res.message, 'error');
                                }
                            }
                        });
                    }
                });
            });

            // Re-seed Standard UOMs Click
            $("#btnReseedDefaults").on("click", function () {
                Swal.fire({
                    title: 'Seed Standard UOMs?',
                    text: 'This will add all standard quantity, weight, volume, length & area UOM records if missing.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Seed Defaults'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'uom_master.php',
                            type: 'POST',
                            dataType: 'json',
                            data: { action: 'reseed_defaults' },
                            success: function (res) {
                                if (res.status === 'success') {
                                    Swal.fire('Seeded!', res.message, 'success');
                                    fetchUOMs();
                                }
                            }
                        });
                    }
                });
            });

            function escapeHtml(text) {
                if (!text) return '';
                return text
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
</body>

</html>
