<?php
/**
 * Standalone Order & Sales Product ID Mismatch Auditor & Sync Tool
 * Location: krf-orderandsales/temp/checkordersale.php
 * Only requires connection.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Establish database connection
if (file_exists(__DIR__ . '/../../connection.php')) {
    require_once __DIR__ . '/../../connection.php';
} elseif (file_exists('../../connection.php')) {
    require_once '../../connection.php';
} else {
    require_once 'connection.php';
}

// Select the database connection holding the tables (karjeefarm / krfconn)
$db = (isset($krfconn) && $krfconn instanceof mysqli) ? $krfconn : $conn;

// -------------------------------------------------------------------------
// AJAX / POST HANDLER FOR UPDATING PRODUCT ID & NAME
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $action = $_POST['action'];

    // Helper function to sync a single row
    function syncOrderProduct($db, $poc_id, $psoi_id, $soi_id, $target = 'both') {
        $poc_id = intval($poc_id);
        $psoi_id = intval($psoi_id);
        $soi_id = intval($soi_id);
        
        // 1. Fetch source of truth from krf_productionorder_consumed
        $pocStmt = $db->prepare("SELECT product_id, product_name FROM krf_productionorder_consumed WHERE id = ?");
        $pocStmt->bind_param("i", $poc_id);
        $pocStmt->execute();
        $pocRes = $pocStmt->get_result()->fetch_assoc();
        $pocStmt->close();
        
        if (!$pocRes) {
            return ['success' => false, 'message' => "Consumed stock record #$poc_id not found."];
        }

        $newProductId = $pocRes['product_id'];
        $newProductName = $pocRes['product_name'];
        $newProductSku = '';

        // 2. Fetch official name and SKU from product_master if exists
        $pmStmt = $db->prepare("SELECT ProductName, sku FROM product_master WHERE ProductID = ? LIMIT 1");
        $pmStmt->bind_param("i", $newProductId);
        $pmStmt->execute();
        $pmRes = $pmStmt->get_result()->fetch_assoc();
        $pmStmt->close();

        if ($pmRes) {
            if (!empty($pmRes['ProductName'])) {
                $newProductName = $pmRes['ProductName'];
            }
            if (!empty($pmRes['sku'])) {
                $newProductSku = $pmRes['sku'];
            }
        }

        $psoiUpdated = 0;
        $soiUpdated = 0;

        // 3. Update krf_provisional_sales_order_items if requested and valid
        if (($target === 'both' || $target === 'psoi') && $psoi_id > 0) {
            $stmt1 = $db->prepare("UPDATE krf_provisional_sales_order_items 
                                   SET product_id = ?, 
                                       product_name = ?, 
                                       product_sku = CASE WHEN ? != '' THEN ? ELSE product_sku END,
                                       updatedat = CURDATE(),
                                       remark = CONCAT(COALESCE(remark, ''), ' [PID synced with consumed stock #', ?, ']')
                                   WHERE id = ?");
            $stmt1->bind_param("ssssii", $newProductId, $newProductName, $newProductSku, $newProductSku, $poc_id, $psoi_id);
            if ($stmt1->execute()) {
                $psoiUpdated = $stmt1->affected_rows;
            }
            $stmt1->close();
        }

        // 4. Update krf_sales_order_items if requested and valid
        if (($target === 'both' || $target === 'soi') && $soi_id > 0) {
            $stmt2 = $db->prepare("UPDATE krf_sales_order_items 
                                   SET product_id = ?, 
                                       product_name = ?, 
                                       product_sku = CASE WHEN ? != '' THEN ? ELSE product_sku END,
                                       updateamended = NOW(),
                                       remark = CONCAT(COALESCE(remark, ''), ' [PID synced with consumed stock #', ?, ']')
                                   WHERE id = ?");
            $stmt2->bind_param("ssssii", $newProductId, $newProductName, $newProductSku, $newProductSku, $poc_id, $soi_id);
            if ($stmt2->execute()) {
                $soiUpdated = $stmt2->affected_rows;
            }
            $stmt2->close();
        }

        return [
            'success' => true,
            'psoi_updated' => $psoiUpdated,
            'soi_updated' => $soiUpdated,
            'new_product_id' => $newProductId,
            'new_product_name' => $newProductName
        ];
    }

    if ($action === 'sync_single') {
        $poc_id = isset($_POST['poc_id']) ? intval($_POST['poc_id']) : 0;
        $psoi_id = isset($_POST['psoi_id']) ? intval($_POST['psoi_id']) : 0;
        $soi_id = isset($_POST['soi_id']) ? intval($_POST['soi_id']) : 0;
        $target = isset($_POST['target']) ? trim($_POST['target']) : 'both';

        $res = syncOrderProduct($db, $poc_id, $psoi_id, $soi_id, $target);
        echo json_encode($res);
        exit;
    }

    if ($action === 'sync_bulk') {
        $itemsJson = isset($_POST['items']) ? $_POST['items'] : '[]';
        $items = json_decode($itemsJson, true);
        if (!is_array($items) || empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items selected for sync.']);
            exit;
        }

        $totalSuccess = 0;
        $errors = [];

        foreach ($items as $item) {
            $poc_id = isset($item['poc_id']) ? intval($item['poc_id']) : 0;
            $psoi_id = isset($item['psoi_id']) ? intval($item['psoi_id']) : 0;
            $soi_id = isset($item['soi_id']) ? intval($item['soi_id']) : 0;
            $target = isset($item['target']) ? $item['target'] : 'both';

            $result = syncOrderProduct($db, $poc_id, $psoi_id, $soi_id, $target);
            if (!empty($result['success'])) {
                $totalSuccess++;
            } else {
                $errors[] = "POC #$poc_id: " . ($result['message'] ?? 'Error');
            }
        }

        echo json_encode([
            'success' => true,
            'updated_count' => $totalSuccess,
            'errors' => $errors,
            'message' => "Successfully updated $totalSuccess item(s)!"
        ]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action.']);
    exit;
}

// -------------------------------------------------------------------------
// QUERY MISMATCHED ORDERS
// -------------------------------------------------------------------------
$sql = "SELECT 
    poc.id AS poc_id,
    poc.psono,
    poc.sono,
    poc.stockorderno,
    poc.original_stockorder_no,
    poc.allocated_stockorderno,
    poc.product_id AS poc_product_id,
    poc.product_name AS poc_product_name,
    poc.consumed_qty,
    poc.dispatchedqty,
    poc.status AS poc_status,
    poc.confirmstatus,
    poc.created_at AS poc_created_at,
    psoi.id AS psoi_id,
    psoi.product_id AS psoi_product_id,
    psoi.product_name AS psoi_product_name,
    psoi.quantity AS psoi_qty,
    psoi.status AS psoi_status,
    soi.id AS soi_id,
    soi.product_id AS soi_product_id,
    soi.product_name AS soi_product_name,
    soi.quantity AS soi_qty,
    soi.status AS soi_status,
    pm.sku AS target_sku
FROM krf_productionorder_consumed poc
LEFT JOIN krf_provisional_sales_order_items psoi 
    ON psoi.psono = poc.psono 
    AND (
        (NULLIF(TRIM(poc.stockorderno), '') IS NOT NULL AND psoi.stockorder_no = poc.stockorderno)
        OR (NULLIF(TRIM(poc.original_stockorder_no), '') IS NOT NULL AND psoi.stockorder_no = poc.original_stockorder_no)
        OR (NULLIF(TRIM(poc.allocated_stockorderno), '') IS NOT NULL AND psoi.stockorder_no = poc.allocated_stockorderno)
    )
    AND IFNULL(psoi.tedit, '') = ''
LEFT JOIN krf_sales_order_items soi 
    ON (soi.psono = poc.psono OR (NULLIF(TRIM(poc.sono), '') IS NOT NULL AND soi.sono = poc.sono))
    AND (
        (NULLIF(TRIM(poc.stockorderno), '') IS NOT NULL AND soi.stockorder_no = poc.stockorderno)
        OR (NULLIF(TRIM(poc.original_stockorder_no), '') IS NOT NULL AND soi.stockorder_no = poc.original_stockorder_no)
        OR (NULLIF(TRIM(poc.allocated_stockorderno), '') IS NOT NULL AND soi.stockorder_no = poc.allocated_stockorderno)
    )
    AND IFNULL(soi.tedit, '') = ''
LEFT JOIN product_master pm 
    ON pm.ProductID = poc.product_id
WHERE poc.confirmstatus = 'Confirmed'
  AND IFNULL(poc.tedit, '') = ''
  AND (
      (psoi.id IS NOT NULL AND CAST(psoi.product_id AS CHAR) != CAST(poc.product_id AS CHAR))
      OR (soi.id IS NOT NULL AND CAST(soi.product_id AS CHAR) != CAST(poc.product_id AS CHAR))
  )
ORDER BY poc.id DESC";

$result = $db->query($sql);
$mismatches = [];
$psoiMismatchCount = 0;
$soiMismatchCount = 0;

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $psoiDiff = ($row['psoi_id'] && (string)$row['psoi_product_id'] !== (string)$row['poc_product_id']);
        $soiDiff = ($row['soi_id'] && (string)$row['soi_product_id'] !== (string)$row['poc_product_id']);
        
        if ($psoiDiff) $psoiMismatchCount++;
        if ($soiDiff) $soiMismatchCount++;

        $row['psoi_diff'] = $psoiDiff;
        $row['soi_diff'] = $soiDiff;
        $mismatches[] = $row;
    }
}

// Total confirmed POC for context
$totalConfirmedRes = $db->query("SELECT COUNT(*) AS total FROM krf_productionorder_consumed WHERE confirmstatus = 'Confirmed' AND IFNULL(tedit, '') = ''");
$totalConfirmed = $totalConfirmedRes ? $totalConfirmedRes->fetch_assoc()['total'] : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order & Sales Product ID Mismatch Auditor & Sync</title>
    <!-- Fonts & Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- SweetAlert2 for polished dialogs -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-color: #0f172a;
            --panel-bg: #1e293b;
            --panel-border: #334155;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --primary: #3b82f6;
            --primary-hover: #2563eb;
            --success: #10b981;
            --success-bg: rgba(16, 185, 129, 0.12);
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.12);
            --warning: #f59e0b;
            --warning-bg: rgba(245, 158, 11, 0.12);
            --info: #06b6d4;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.3), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            padding: 24px 20px 60px;
        }

        .container {
            max-width: 1650px;
            margin: 0 auto;
        }

        /* Top Header */
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--panel-border);
            flex-wrap: wrap;
            gap: 16px;
        }

        .header-title-box {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .header-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
        }

        .header-title {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .header-subtitle {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            line-height: 1.2;
        }

        .btn-primary {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .btn-primary:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .btn-success {
            background: #059669;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }
        .btn-success:hover {
            background: #047857;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--panel-border);
        }
        .btn-outline:hover {
            background: var(--panel-bg);
            border-color: #475569;
        }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }

        .stat-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: var(--card-shadow);
            transition: var(--transition);
        }
        .stat-card:hover {
            border-color: #475569;
            transform: translateY(-2px);
        }

        .stat-info h4 {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-muted);
            margin-bottom: 6px;
        }

        .stat-info .stat-value {
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }

        .stat-icon.red { background: var(--danger-bg); color: var(--danger); }
        .stat-icon.blue { background: rgba(59, 130, 246, 0.15); color: var(--primary); }
        .stat-icon.yellow { background: var(--warning-bg); color: var(--warning); }
        .stat-icon.green { background: var(--success-bg); color: var(--success); }

        /* Filter & Bulk Bar */
        .action-bar {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            padding: 16px 20px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .search-box {
            display: flex;
            align-items: center;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 8px 14px;
            gap: 10px;
            width: 320px;
            max-width: 100%;
        }

        .search-box i {
            color: var(--text-muted);
            font-size: 14px;
        }

        .search-box input {
            background: transparent;
            border: none;
            outline: none;
            color: #ffffff;
            font-size: 13px;
            width: 100%;
        }

        .filter-tags {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .filter-btn {
            background: #0f172a;
            border: 1px solid #334155;
            color: var(--text-muted);
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-btn:hover, .filter-btn.active {
            background: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .bulk-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Table Card */
        .table-card {
            background: var(--panel-bg);
            border: 1px solid var(--panel-border);
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 13px;
        }

        thead {
            background: #111827;
            border-bottom: 2px solid var(--panel-border);
        }

        th {
            padding: 14px 16px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.05em;
            white-space: nowrap;
        }

        tbody tr {
            border-bottom: 1px solid #1e293b;
            background: #1e293b;
            transition: background 0.15s ease;
        }

        tbody tr:nth-child(even) {
            background: #182234;
        }

        tbody tr:hover {
            background: #233149;
        }

        td {
            padding: 14px 16px;
            vertical-align: middle;
            color: #cbd5e1;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .badge-danger {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .badge-success {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .badge-warning {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .badge-info {
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .badge-neutral {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        /* Cell content formatting */
        .order-info {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        .order-pso {
            font-weight: 700;
            color: #ffffff;
            font-size: 13px;
        }
        .order-sub {
            font-size: 11px;
            color: var(--text-muted);
        }

        .product-box {
            display: flex;
            flex-direction: column;
            gap: 4px;
            max-width: 320px;
        }

        .product-name {
            font-weight: 500;
            color: #e2e8f0;
            line-height: 1.35;
        }

        .product-meta {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 11px;
        }

        /* Checkbox */
        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--primary);
        }

        /* Empty state */
        .empty-state {
            padding: 60px 20px;
            text-align: center;
        }
        .empty-icon {
            font-size: 50px;
            color: #10b981;
            margin-bottom: 16px;
        }
        .empty-state h3 {
            font-size: 20px;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 8px;
        }
        .empty-state p {
            color: var(--text-muted);
            font-size: 14px;
            max-width: 480px;
            margin: 0 auto;
        }

        /* Pulse animation for mismatched badges */
        @keyframes subtlePulse {
            0% { opacity: 0.85; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.02); }
            100% { opacity: 0.85; transform: scale(1); }
        }

        .mismatch-highlight {
            animation: subtlePulse 2.5s infinite;
        }

        .action-cell {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <header class="header">
        <div class="header-title-box">
            <div class="header-icon">
                <i class="fa-solid fa-code-compare"></i>
            </div>
            <div>
                <h1 class="header-title">Order Product ID Mismatch Auditor</h1>
                <p class="header-subtitle">
                    Identifies client orders with mismatched <code>product_id</code> between 
                    <strong>krf_productionorder_consumed</strong> (Confirmed) and 
                    <strong>provisional / sales order items</strong>.
                </p>
            </div>
        </div>
        <div class="header-actions">
            <button class="btn btn-outline" onclick="location.reload();">
                <i class="fa-solid fa-arrows-rotate"></i> Refresh List
            </button>
            <button class="btn btn-success" id="btnBulkSync" onclick="handleBulkSync()" disabled>
                <i class="fa-solid fa-bolt"></i> Sync Selected (<span id="selectedCount">0</span>)
            </button>
        </div>
    </header>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-info">
                <h4>Total Confirmed Consumed</h4>
                <div class="stat-value"><?= number_format($totalConfirmed) ?></div>
            </div>
            <div class="stat-icon blue">
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>Total Product ID Mismatches</h4>
                <div class="stat-value" style="color: <?= count($mismatches) > 0 ? '#ef4444' : '#10b981' ?>;">
                    <?= number_format(count($mismatches)) ?>
                </div>
            </div>
            <div class="stat-icon red">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>Provisional Order Mismatches</h4>
                <div class="stat-value"><?= number_format($psoiMismatchCount) ?></div>
            </div>
            <div class="stat-icon yellow">
                <i class="fa-solid fa-file-lines"></i>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-info">
                <h4>Sales Order Mismatches</h4>
                <div class="stat-value"><?= number_format($soiMismatchCount) ?></div>
            </div>
            <div class="stat-icon green">
                <i class="fa-solid fa-receipt"></i>
            </div>
        </div>
    </div>

    <!-- Action & Filter Bar -->
    <div class="action-bar">
        <div class="search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="searchInput" placeholder="Search PSONO, SONO, Stock Order or Product..." onkeyup="filterRows()">
        </div>

        <div class="filter-tags">
            <button class="filter-btn active" onclick="setFilter('all', this)">All Mismatches (<?= count($mismatches) ?>)</button>
            <button class="filter-btn" onclick="setFilter('psoi', this)">PSOI Mismatches (<?= $psoiMismatchCount ?>)</button>
            <button class="filter-btn" onclick="setFilter('soi', this)">SOI Mismatches (<?= $soiMismatchCount ?>)</button>
            <button class="filter-btn" onclick="setFilter('both', this)">Both Mismatched</button>
        </div>

        <div class="bulk-actions">
            <label style="font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px; cursor: pointer; color: var(--text-muted);">
                <input type="checkbox" id="selectAllCheckbox" class="custom-checkbox" onchange="toggleSelectAll(this)">
                Select All Shown
            </label>
        </div>
    </div>

    <!-- Data Table -->
    <div class="table-card">
        <?php if (empty($mismatches)): ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-circle-check"></i></div>
                <h3>All Orders Fully Matched!</h3>
                <p>No product ID discrepancies found between confirmed production orders and sales orders. All entries are in sync.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table id="mismatchTable">
                    <thead>
                        <tr>
                            <th style="width: 40px; text-align: center;">
                                #
                            </th>
                            <th>Order Details</th>
                            <th>
                                <i class="fa-solid fa-warehouse" style="color: #10b981;"></i> 
                                Consumed Stock (Source)
                            </th>
                            <th>
                                <i class="fa-solid fa-file-invoice" style="color: #f59e0b;"></i> 
                                Provisional Order Item
                            </th>
                            <th>
                                <i class="fa-solid fa-receipt" style="color: #3b82f6;"></i> 
                                Sales Order Item
                            </th>
                            <th style="text-align: center;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mismatches as $idx => $m): 
                            $rowClass = '';
                            if ($m['psoi_diff'] && $m['soi_diff']) {
                                $rowClass = 'type-both';
                            } elseif ($m['psoi_diff']) {
                                $rowClass = 'type-psoi';
                            } elseif ($m['soi_diff']) {
                                $rowClass = 'type-soi';
                            }
                        ?>
                        <tr class="data-row <?= $rowClass ?>" id="row-<?= $m['poc_id'] ?>"
                            data-poc-id="<?= $m['poc_id'] ?>"
                            data-psoi-id="<?= intval($m['psoi_id']) ?>"
                            data-soi-id="<?= intval($m['soi_id']) ?>"
                            data-psoi-diff="<?= $m['psoi_diff'] ? '1' : '0' ?>"
                            data-soi-diff="<?= $m['soi_diff'] ? '1' : '0' ?>"
                            data-search="<?= htmlspecialchars(strtolower($m['psono'] . ' ' . $m['sono'] . ' ' . $m['stockorderno'] . ' ' . $m['poc_product_name'] . ' ' . $m['psoi_product_name'] . ' ' . $m['soi_product_name'])) ?>">
                            
                            <!-- Selection Checkbox -->
                            <td style="text-align: center;">
                                <input type="checkbox" class="custom-checkbox item-checkbox" 
                                       value="<?= $m['poc_id'] ?>" 
                                       data-poc-id="<?= $m['poc_id'] ?>"
                                       data-psoi-id="<?= intval($m['psoi_id']) ?>"
                                       data-soi-id="<?= intval($m['soi_id']) ?>"
                                       onchange="updateSelectionState()">
                            </td>

                            <!-- Order Details -->
                            <td>
                                <div class="order-info">
                                    <span class="order-pso"><?= htmlspecialchars($m['psono']) ?></span>
                                    <?php if (!empty($m['sono'])): ?>
                                        <span class="order-sub"><strong>SO:</strong> <?= htmlspecialchars($m['sono']) ?></span>
                                    <?php endif; ?>
                                    <span class="order-sub">
                                        <strong>Stock:</strong> 
                                        <span class="badge badge-neutral"><?= htmlspecialchars($m['stockorderno']) ?></span>
                                    </span>
                                    <span class="order-sub" style="margin-top: 4px;">
                                        <i class="fa-regular fa-clock"></i> POC #<?= $m['poc_id'] ?> 
                                        (<?= htmlspecialchars($m['confirmstatus']) ?>)
                                    </span>
                                </div>
                            </td>

                            <!-- Consumed Stock (Source) -->
                            <td>
                                <div class="product-box">
                                    <div class="product-meta">
                                        <span class="badge badge-success">
                                            <i class="fa-solid fa-tag"></i> ID: <?= htmlspecialchars($m['poc_product_id']) ?>
                                        </span>
                                        <?php if (!empty($m['target_sku'])): ?>
                                            <span class="badge badge-neutral">SKU: <?= htmlspecialchars($m['target_sku']) ?></span>
                                        <?php endif; ?>
                                        <span class="badge badge-info">Qty: <?= floatval($m['consumed_qty']) ?></span>
                                    </div>
                                    <div class="product-name" style="color: #6ee7b7; font-weight: 600;">
                                        <?= htmlspecialchars($m['poc_product_name']) ?>
                                    </div>
                                </div>
                            </td>

                            <!-- Provisional Sales Order Item -->
                            <td>
                                <?php if (!$m['psoi_id']): ?>
                                    <span class="badge badge-neutral">No Item Linked</span>
                                <?php elseif ($m['psoi_diff']): ?>
                                    <div class="product-box">
                                        <div class="product-meta">
                                            <span class="badge badge-danger mismatch-highlight">
                                                <i class="fa-solid fa-xmark"></i> Mismatch ID: <?= htmlspecialchars($m['psoi_product_id']) ?>
                                            </span>
                                            <span class="badge badge-neutral">Item #<?= $m['psoi_id'] ?></span>
                                        </div>
                                        <div class="product-name" style="color: #fca5a5; text-decoration: line-through;">
                                            <?= htmlspecialchars($m['psoi_product_name']) ?>
                                        </div>
                                        <div style="font-size: 11px; color: #10b981; margin-top: 2px;">
                                            <i class="fa-solid fa-arrow-right"></i> Will sync to: <strong>ID <?= htmlspecialchars($m['poc_product_id']) ?></strong>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="product-box">
                                        <span class="badge badge-success">
                                            <i class="fa-solid fa-check"></i> Matched (ID <?= htmlspecialchars($m['psoi_product_id']) ?>)
                                        </span>
                                        <div class="product-name" style="font-size: 12px; color: var(--text-muted);">
                                            <?= htmlspecialchars($m['psoi_product_name']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Sales Order Item -->
                            <td>
                                <?php if (!$m['soi_id']): ?>
                                    <span class="badge badge-neutral">No SO Item Linked</span>
                                <?php elseif ($m['soi_diff']): ?>
                                    <div class="product-box">
                                        <div class="product-meta">
                                            <span class="badge badge-danger mismatch-highlight">
                                                <i class="fa-solid fa-xmark"></i> Mismatch ID: <?= htmlspecialchars($m['soi_product_id']) ?>
                                            </span>
                                            <span class="badge badge-neutral">Item #<?= $m['soi_id'] ?></span>
                                        </div>
                                        <div class="product-name" style="color: #fca5a5; text-decoration: line-through;">
                                            <?= htmlspecialchars($m['soi_product_name']) ?>
                                        </div>
                                        <div style="font-size: 11px; color: #10b981; margin-top: 2px;">
                                            <i class="fa-solid fa-arrow-right"></i> Will sync to: <strong>ID <?= htmlspecialchars($m['poc_product_id']) ?></strong>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="product-box">
                                        <span class="badge badge-success">
                                            <i class="fa-solid fa-check"></i> Matched (ID <?= htmlspecialchars($m['soi_product_id']) ?>)
                                        </span>
                                        <div class="product-name" style="font-size: 12px; color: var(--text-muted);">
                                            <?= htmlspecialchars($m['soi_product_name']) ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <!-- Actions -->
                            <td style="text-align: center;">
                                <div class="action-cell" style="justify-content: center;">
                                    <button class="btn btn-primary btn-sm" 
                                            title="Update both Provisional and Sales Order Item to match Consumed Stock"
                                            onclick="syncSingle(<?= $m['poc_id'] ?>, <?= intval($m['psoi_id']) ?>, <?= intval($m['soi_id']) ?>, 'both', '<?= htmlspecialchars(addslashes($m['psono'])) ?>', '<?= htmlspecialchars(addslashes($m['poc_product_id'])) ?>', '<?= htmlspecialchars(addslashes($m['poc_product_name'])) ?>')">
                                        <i class="fa-solid fa-arrows-rotate"></i> Sync to Consumed
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    let currentFilter = 'all';

    // Filter table by text input
    function filterRows() {
        const query = document.getElementById('searchInput').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#mismatchTable tbody tr.data-row');

        rows.forEach(row => {
            const searchText = row.getAttribute('data-search') || '';
            const psoiDiff = row.getAttribute('data-psoi-diff') === '1';
            const soiDiff = row.getAttribute('data-soi-diff') === '1';

            let matchesFilter = true;
            if (currentFilter === 'psoi' && !psoiDiff) matchesFilter = false;
            if (currentFilter === 'soi' && !soiDiff) matchesFilter = false;
            if (currentFilter === 'both' && (!psoiDiff || !soiDiff)) matchesFilter = false;

            const matchesSearch = !query || searchText.includes(query);

            if (matchesFilter && matchesSearch) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
                const cb = row.querySelector('.item-checkbox');
                if (cb) cb.checked = false;
            }
        });

        updateSelectionState();
    }

    // Filter by type button
    function setFilter(type, btn) {
        currentFilter = type;
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        filterRows();
    }

    // Toggle select all
    function toggleSelectAll(masterCheckbox) {
        const rows = document.querySelectorAll('#mismatchTable tbody tr.data-row');
        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const cb = row.querySelector('.item-checkbox');
                if (cb) cb.checked = masterCheckbox.checked;
            }
        });
        updateSelectionState();
    }

    // Update selection count & bulk sync button state
    function updateSelectionState() {
        const checked = document.querySelectorAll('#mismatchTable tbody tr.data-row:not([style*="display: none"]) .item-checkbox:checked');
        const count = checked.length;
        document.getElementById('selectedCount').innerText = count;
        document.getElementById('btnBulkSync').disabled = (count === 0);
    }

    // Sync a single row
    function syncSingle(pocId, psoiId, soiId, target, psono, newPid, newPname) {
        Swal.fire({
            title: 'Confirm Product Sync',
            html: `
                <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                    <p><strong>Order:</strong> ${psono}</p>
                    <p><strong>Target Product ID:</strong> <span style="color: #10b981; font-weight: bold;">${newPid}</span></p>
                    <p><strong>Product Name:</strong> ${newPname}</p>
                    <hr style="margin: 12px 0; border: none; border-top: 1px solid #475569;">
                    <p style="color: #94a3b8; font-size: 13px;">
                        This will update <code>product_id</code> and <code>product_name</code> in:
                        <br>• <code>krf_provisional_sales_order_items</code> (Item #${psoiId || 'N/A'})
                        <br>• <code>krf_sales_order_items</code> (Item #${soiId || 'N/A'})
                        <br>to match <code>krf_productionorder_consumed</code>.
                    </p>
                </div>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: '<i class="fa-solid fa-arrows-rotate"></i> Yes, Sync Product Now',
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#475569',
            background: '#1e293b',
            color: '#ffffff'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Syncing Product...',
                    text: 'Please wait while the order items are being updated.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                    background: '#1e293b',
                    color: '#ffffff'
                });

                const formData = new FormData();
                formData.append('action', 'sync_single');
                formData.append('poc_id', pocId);
                formData.append('psoi_id', psoiId);
                formData.append('soi_id', soiId);
                formData.append('target', target);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Synced Successfully!',
                            text: `Product ID aligned to ${data.new_product_id} for order ${psono}.`,
                            timer: 1600,
                            showConfirmButton: false,
                            background: '#1e293b',
                            color: '#ffffff'
                        }).then(() => {
                            // Animate removal of resolved row
                            const row = document.getElementById(`row-${pocId}`);
                            if (row) {
                                row.style.transition = 'all 0.4s ease';
                                row.style.backgroundColor = 'rgba(16, 185, 129, 0.2)';
                                setTimeout(() => {
                                    row.remove();
                                    // If no rows left, reload to show empty state
                                    if (document.querySelectorAll('#mismatchTable tbody tr.data-row').length === 0) {
                                        location.reload();
                                    }
                                }, 400);
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Sync Failed',
                            text: data.message || 'An error occurred while updating.',
                            background: '#1e293b',
                            color: '#ffffff'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Server Error',
                        text: err.message,
                        background: '#1e293b',
                        color: '#ffffff'
                    });
                });
            }
        });
    }

    // Handle bulk sync
    function handleBulkSync() {
        const checkedBoxes = document.querySelectorAll('#mismatchTable tbody tr.data-row:not([style*="display: none"]) .item-checkbox:checked');
        if (checkedBoxes.length === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'No Items Selected',
                text: 'Please select at least one order to sync.',
                background: '#1e293b',
                color: '#ffffff'
            });
            return;
        }

        const items = [];
        checkedBoxes.forEach(cb => {
            items.push({
                poc_id: cb.getAttribute('data-poc-id'),
                psoi_id: cb.getAttribute('data-psoi-id'),
                soi_id: cb.getAttribute('data-soi-id'),
                target: 'both'
            });
        });

        Swal.fire({
            title: `Sync ${items.length} Selected Orders?`,
            text: 'All selected order items will be updated with the Product ID & Name from the confirmed consumed stock.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `<i class="fa-solid fa-bolt"></i> Yes, Sync All ${items.length} Items`,
            cancelButtonText: 'Cancel',
            confirmButtonColor: '#059669',
            cancelButtonColor: '#475569',
            background: '#1e293b',
            color: '#ffffff'
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Syncing Selected Orders...',
                    text: 'Please wait...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                    background: '#1e293b',
                    color: '#ffffff'
                });

                const formData = new FormData();
                formData.append('action', 'sync_bulk');
                formData.append('items', JSON.stringify(items));

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Bulk Sync Complete!',
                            text: data.message,
                            background: '#1e293b',
                            color: '#ffffff'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Bulk Sync Error',
                            text: data.message || 'Error occurred during bulk update.',
                            background: '#1e293b',
                            color: '#ffffff'
                        });
                    }
                })
                .catch(err => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Request Failed',
                        text: err.message,
                        background: '#1e293b',
                        color: '#ffffff'
                    });
                });
            }
        });
    }
</script>

</body>
</html>
