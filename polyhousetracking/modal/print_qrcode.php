<?php
/** @var mysqli $krfconn */
/** @var mysqli $hrmconn */
error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');
require_once(__DIR__ . '/../../connection.php');

$projectid     = (int)($_REQUEST['projectid'] ?? 0);
$locationid    = trim($_REQUEST['locationid'] ?? '');
$polyhouseid   = (int)($_REQUEST['polyhouseid'] ?? 0);
$bed_no        = trim($_REQUEST['bed_no'] ?? '');
$stockorder_no = trim($_REQUEST['stockorder_no'] ?? $_REQUEST['stockorder'] ?? '');
$psono         = trim($_REQUEST['psono'] ?? '');
$productid     = trim($_REQUEST['productid'] ?? '');
$format        = trim($_REQUEST['format'] ?? 'html'); // 'html', 'json', 'tspl', 'trays'

if ($projectid <= 0) $projectid = 17;

$whereConditions = ["p.projectid = '{$projectid}'"];
if (!empty($locationid)) {
    $locEsc = $krfconn->real_escape_string($locationid);
    $whereConditions[] = "p.locationid = '{$locEsc}'";
}
if ($polyhouseid > 0) {
    $whereConditions[] = "CAST(p.polyhouseid AS UNSIGNED) = {$polyhouseid}";
}
if (!empty($bed_no)) {
    $bedEsc = $krfconn->real_escape_string($bed_no);
    $cleanBed = preg_replace('/^Bed[-\s]*/i', '', $bed_no);
    $cleanBedEsc = $krfconn->real_escape_string($cleanBed);
    $whereConditions[] = "(p.bed_no = '{$bedEsc}' OR p.bed_no = '{$cleanBedEsc}' OR p.bed_no = 'Bed-{$cleanBedEsc}')";
}
if (!empty($stockorder_no)) {
    $soEsc = $krfconn->real_escape_string($stockorder_no);
    $cleanSo = preg_replace('/^(RSO|OS)-/i', '', $stockorder_no);
    $cleanSoEsc = $krfconn->real_escape_string($cleanSo);
    $whereConditions[] = "(p.stockorder_no = '{$soEsc}' OR p.stockorder_no = '{$cleanSoEsc}' OR p.stockorder_no = 'RSO-{$cleanSoEsc}' OR p.stockorder_no = 'OS-{$cleanSoEsc}' OR p.psono = '{$soEsc}' OR p.psono = '{$cleanSoEsc}' OR p.bedqrcode = '{$soEsc}')";
}
if (!empty($productid)) {
    $prodEsc = $krfconn->real_escape_string($productid);
    $whereConditions[] = "p.productid = '{$prodEsc}'";
}
if (!empty($psono) && empty($stockorder_no)) {
    $psoEsc = $krfconn->real_escape_string($psono);
    $whereConditions[] = "p.psono = '{$psoEsc}'";
}

$whereConditions[] = "(p.tedit IS NULL OR p.tedit = '')";
$whereConditions[] = "p.barcode IS NOT NULL AND TRIM(p.barcode) <> ''";
$whereClause = implode(" AND ", $whereConditions);

$sql = "
    SELECT 
        p.polyhouseplantid,
        p.barcode,
        p.bed_no,
        p.polyhouseid,
        p.stockorder_no,
        p.psono,
        p.productid,
        p.productname,
        p.variety,
        p.qtypertray,
        p.tray_id,
        p.tray_code,
        p.status,
        p.workingstatus,
        p.growth_stage,
        p.bedqrcode,
        COALESCE(NULLIF(TRIM(p.lotno),''), NULLIF(TRIM(p.sowinglotno_internal),''), NULLIF(TRIM(p.batchno),''), '') AS seed_lot_no,
        COALESCE(p.sowingdate_internal, p.sowing_date) AS sowing_date,
        p.dispatchdate_internal,
        p.created_at
    FROM krf_polyhouse_plant_details p
    WHERE {$whereClause}
    ORDER BY p.polyhouseplantid ASC
";

$res = $krfconn->query($sql);
$labels = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $sowDate = !empty($row['sowing_date']) ? date('d-M-Y', strtotime($row['sowing_date'])) : '';
        $dispDate = !empty($row['dispatchdate_internal']) ? date('d-M-Y', strtotime($row['dispatchdate_internal'])) : '';
        
        $labels[] = [
            'id' => $row['polyhouseplantid'],
            'barcode' => $row['barcode'],
            'bed_no' => $row['bed_no'] ?: 'Bed-?',
            'polyhouseid' => $row['polyhouseid'] ?: 'PH-?',
            'stockorder_no' => $row['stockorder_no'] ?: 'N/A',
            'psono' => $row['psono'] ?: 'N/A',
            'productid' => $row['productid'],
            'productname' => $row['productname'] ?: 'Plant',
            'variety' => $row['variety'] ?: '',
            'seed_lot_no' => $row['seed_lot_no'] ?: '',
            'bedqrcode' => trim($row['bedqrcode'] ?? ''),
            'qtypertray' => (int)($row['qtypertray'] ?: 209),
            'sowing_date' => $sowDate,
            'dispatch_date' => $dispDate,
            'status' => $row['status']
        ];
    }
}

// Resolve Polyhouse and Location IDs
$firstRow = $labels[0] ?? [];
$resolvedPolyhouseId = $polyhouseid > 0 ? $polyhouseid : (int)($firstRow['polyhouseid'] ?? 0);
$resolvedLocationId  = !empty($locationid) ? $locationid : trim($firstRow['locationid'] ?? '');

// Bed QR Code is the stockorder_no value
$bedQrCode = trim($firstRow['bedqrcode'] ?? '');
if (empty($bedQrCode)) {
    $bedQrCode = trim($firstRow['stockorder_no'] ?? '') ?: ($stockorder_no ?: trim($firstRow['psono'] ?? ''));
}

if (!empty($bedQrCode) && !empty($whereClause) && !empty($labels)) {
    $escapedBedQr = $krfconn->real_escape_string($bedQrCode);
    $cleanWhere = str_replace('p.', '', $whereClause);
    $updSql = "UPDATE krf_polyhouse_plant_details SET bedqrcode = '{$escapedBedQr}' WHERE {$cleanWhere} AND (bedqrcode IS NULL OR bedqrcode = '' OR bedqrcode != '{$escapedBedQr}')";
    @$krfconn->query($updSql);
}

// Fetch Polyhouse and Location names for metadata
$phName = "Polyhouse " . ($resolvedPolyhouseId ?: '1');
if ($resolvedPolyhouseId > 0) {
    $phStmt = $krfconn->prepare("SELECT polyhousename FROM krf_polyhouse_master WHERE polyhouseid = ? LIMIT 1");
    if ($phStmt) {
        $phStmt->bind_param("i", $resolvedPolyhouseId);
        $phStmt->execute();
        $phRes = $phStmt->get_result()->fetch_assoc();
        if ($phRes && !empty($phRes['polyhousename'])) {
            $phName = $phRes['polyhousename'];
        }
        $phStmt->close();
    }
}
$locName = $resolvedLocationId ?: 'Nursery Location';
if (!empty($resolvedLocationId) && isset($hrmconn) && $hrmconn instanceof mysqli) {
    $locStmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE locationid = ? LIMIT 1");
    if ($locStmt) {
        $locStmt->bind_param("s", $resolvedLocationId);
        $locStmt->execute();
        $locRes = $locStmt->get_result()->fetch_assoc();
        if ($locRes && !empty($locRes['locationname'])) {
            $locName = $locRes['locationname'];
        }
        $locStmt->close();
    }
}

// Extract Single Order Details
$rawProdName = trim($firstRow['productname'] ?? '');
$rawVariety = trim($firstRow['variety'] ?? '');

if (strpos($rawProdName, ',') !== false) {
    $parts = explode(',', $rawProdName, 2);
    $prodNameOnly = trim($parts[0]);
    $varietyOnly = trim($parts[1]);
} else {
    $prodNameOnly = $rawProdName ?: 'Plant';
    $varietyOnly = (!empty($rawVariety) && $rawVariety !== $rawProdName) ? $rawVariety : '-';
}
if ((empty($varietyOnly) || $varietyOnly === '-') && !empty($rawVariety)) {
    $varietyOnly = $rawVariety;
}

$totalTrays = count($labels);
$qtyPerTray = (int)($firstRow['qtypertray'] ?? 209);
$totalPlants = $totalTrays * $qtyPerTray;
$billNo = !empty($firstRow['psono']) && $firstRow['psono'] !== 'N/A' ? $firstRow['psono'] : ($firstRow['stockorder_no'] ?: ($stockorder_no ?: 'N/A'));
$seedLotNo = !empty($firstRow['seed_lot_no']) ? $firstRow['seed_lot_no'] : '-';
$sowDate = $firstRow['sowing_date'] ?: '-';
$dispDate = $firstRow['dispatch_date'] ?: '-';
$bedNoDisplay = $bed_no ?: ($firstRow['bed_no'] ?? '-');

// 6-digit alphanumeric unique QR payload with suffix BD
$qrPayload = $bedQrCode;

// Function to generate TSPL command string if needed
function generate_tspl_output($labels) {
    $tspl = [];
    $tspl[] = "SIZE 78.6 mm, 12 mm";
    $tspl[] = "GAP 3 mm, 0";
    $tspl[] = "DENSITY 10";
    $tspl[] = "SPEED 2";
    $tspl[] = "DIRECTION 1";
    $tspl[] = "SET TEAR ON";
    $tspl[] = "REFERENCE 0, 10";

    $cols = 2;
    $x_step = 304;

    foreach ($labels as $i => $label) {
        $col = $i % $cols;
        if ($col == 0) {
            $tspl[] = "CLS";
        }
        $baseX = ($col * $x_step);
        $titlelb = substr($label['stockorder_no'] . ' (' . $label['bed_no'] . ')', 0, 20);
        $namelb  = substr($label['productname'], 0, 22);
        $qtylb   = "QTY:" . $label['qtypertray'];
        $sowShort  = !empty($label['sowing_date']) ? date('d-m', strtotime($label['sowing_date'])) : '';
        $dispShort = !empty($label['dispatch_date']) ? date('d-m', strtotime($label['dispatch_date'])) : '';
        $podlb     = "POD:" . ($sowShort && $dispShort ? $sowShort . '/' . $dispShort : ($sowShort ?: $dispShort ?: '-'));

        $qrContent = $label['barcode'];
        $tspl[] = "QRCODE " . ($baseX + 30) . ",20,L,3,A,0,M2,\"$qrContent\"";
        $textX = $baseX + 112;
        $tspl[] = "TEXT " . $textX . ",20,\"1\",0,1,1,\"$titlelb\"";
        $tspl[] = "TEXT " . $textX . ",38,\"1\",0,1,1,\"$namelb\"";
        $tspl[] = "TEXT " . $textX . ",56,\"1\",0,1,1,\"$qtylb\"";
        $tspl[] = "TEXT " . $textX . ",74,\"1\",0,1,1,\"$podlb\"";

        if ($col == 1 || $i == count($labels) - 1) {
            $tspl[] = "PRINT 1,1";
        }
    }
    return implode("\n", $tspl) . "\n";
}

// 1. JSON Format Response
if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'status' => 'success',
        'count' => count($labels),
        'bed_no' => $bedNoDisplay,
        'stockorder_no' => $stockorder_no,
        'productid' => $productid,
        'order_summary' => [
            'productname' => $prodNameOnly,
            'variety' => $varietyOnly,
            'billno' => $billNo,
            'seed_lot_no' => $seedLotNo,
            'bedqrcode' => $bedQrCode,
            'sowing_date' => $sowDate,
            'dispatch_date' => $dispDate,
            'total_trays' => $totalTrays,
            'qtypertray' => $qtyPerTray,
            'total_plants' => $totalPlants,
            'bed_no' => $bedNoDisplay,
            'polyhousename' => $phName,
            'locationname' => $locName,
            'qr_text' => $qrPayload
        ],
        'labels' => $labels
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2. TSPL Format Response
if ($format === 'tspl') {
    header('Content-Type: text/plain; charset=utf-8');
    echo generate_tspl_output($labels);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>A4 Dispatch QR Code - <?php echo htmlspecialchars($billNo); ?> (<?php echo htmlspecialchars($prodNameOnly); ?>)</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

  <style>
    :root {
      --primary: #1e3a8a;
      --primary-dark: #0f172a;
      --accent: #10b981;
      --text-dark: #0f172a;
      --text-muted: #475569;
      --border-color: #cbd5e1;
      --bg-card: #ffffff;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #f1f5f9;
      color: var(--text-dark);
      padding: 16px 12px;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }

    /* Top Action Bar (hidden when printing) */
    .no-print {
      max-width: 210mm;
      margin: 0 auto 16px auto;
      background: #ffffff;
      padding: 12px 20px;
      border-radius: 10px;
      border: 1px solid #e2e8f0;
      box-shadow: 0 4px 12px rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      flex-wrap: wrap;
    }
    .no-print .info h2 {
      font-size: 1.1rem;
      font-weight: 800;
      color: #0f172a;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .no-print .info p {
      font-size: 0.84rem;
      color: #64748b;
      margin-top: 2px;
    }
    .no-print .actions {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 8px 18px;
      border-radius: 8px;
      font-size: 0.88rem;
      font-weight: 700;
      cursor: pointer;
      border: none;
      transition: all 0.2s;
      text-decoration: none;
    }
    .btn-primary {
      background: #2563eb;
      color: #ffffff;
      box-shadow: 0 2px 6px rgba(37,99,235,0.3);
    }
    .btn-primary:hover {
      background: #1d4ed8;
    }
    .btn-secondary {
      background: #f8fafc;
      color: #334155;
      border: 1px solid #cbd5e1;
    }
    .btn-secondary:hover {
      background: #e2e8f0;
    }

    /* A4 Page Container */
    .a4-page-container {
      width: 210mm;
      max-width: 210mm;
      min-height: 275mm;
      margin: 0 auto;
      background: #ffffff;
      padding: 10mm 14mm 8mm 14mm;
      box-shadow: 0 8px 24px rgba(0,0,0,0.06);
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      position: relative;
      border: 1px solid #e2e8f0;
      box-sizing: border-box;
    }

    /* Header Section */
    .slip-header {
      border-bottom: 2px solid #0f172a;
      padding-bottom: 8px;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .brand-title {
      font-size: 1.45rem;
      font-weight: 800;
      color: #0f172a;
      letter-spacing: -0.5px;
      text-transform: uppercase;
      line-height: 1.1;
    }
    .brand-subtitle {
      font-size: 0.84rem;
      font-weight: 700;
      color: #2563eb;
      margin-top: 2px;
      letter-spacing: 0.4px;
      text-transform: uppercase;
    }
    .header-badge {
      text-align: right;
    }
    .header-badge .tag {
      display: inline-block;
      background: #0f172a;
      color: #ffffff;
      padding: 4px 12px;
      font-size: 0.78rem;
      font-weight: 700;
      border-radius: 5px;
      letter-spacing: 0.5px;
    }
    .header-badge .date {
      font-size: 0.74rem;
      color: #64748b;
      margin-top: 3px;
      font-weight: 600;
    }

    /* Center QR Code Block */
    .qr-center-block {
      text-align: center;
      margin: 6px 0 10px 0;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
    }
    .qr-frame {
      padding: 10px;
      background: #ffffff;
      border: 2px solid #0f172a;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.04);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 6px;
      position: relative;
    }
    .qr-frame canvas, .qr-frame img {
      display: block;
      width: 165px !important;
      height: 165px !important;
    }
    .qr-caption {
      font-size: 0.82rem;
      font-weight: 700;
      color: #0f172a;
      letter-spacing: 0.4px;
      text-transform: uppercase;
      margin-top: 2px;
    }
    .qr-subcaption {
      font-size: 0.75rem;
      color: #64748b;
      font-weight: 500;
    }

    /* Data Table / Details Grid */
    .details-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
      border: 1.5px solid #0f172a;
      border-radius: 6px;
      overflow: hidden;
    }
    .details-table th, .details-table td {
      padding: 6.5px 12px;
      border: 1px solid #cbd5e1;
      font-size: 0.88rem;
      line-height: 1.25;
    }
    .details-table th {
      background: #f8fafc;
      font-weight: 700;
      color: #334155;
      text-transform: uppercase;
      font-size: 0.78rem;
      letter-spacing: 0.4px;
      width: 28%;
      vertical-align: middle;
    }
    .details-table td {
      font-weight: 600;
      color: #0f172a;
      width: 72%;
      vertical-align: middle;
    }
    .highlight-cell {
      font-size: 0.98rem !important;
      font-weight: 800 !important;
      color: #0f172a !important;
    }
    .highlight-green {
      color: #047857 !important;
      font-weight: 800 !important;
    }
    .highlight-blue {
      color: #1d4ed8 !important;
      font-weight: 800 !important;
    }

    /* Signatures Section */
    .signatures-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 16px;
      border-top: 1.5px dashed #94a3b8;
      padding-top: 14px;
      margin-top: 8px;
    }
    .sig-box {
      text-align: center;
    }
    .sig-line {
      border-bottom: 1.5px solid #0f172a;
      height: 32px;
      margin-bottom: 6px;
    }
    .sig-label {
      font-size: 0.78rem;
      font-weight: 700;
      color: #334155;
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }
    .sig-role {
      font-size: 0.70rem;
      color: #64748b;
      margin-top: 2px;
    }

    /* Footer Note */
    .slip-footer {
      border-top: 1px solid #e2e8f0;
      padding-top: 6px;
      margin-top: 8px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      font-size: 0.70rem;
      color: #64748b;
      font-weight: 500;
    }

    /* Print Specific Rules for Normal A4 Printer (Strictly 1 Page) */
    @media print {
      @page {
        size: A4 portrait;
        margin: 6mm 8mm;
      }
      html, body {
        width: 100% !important;
        height: 100% !important;
        max-height: 100% !important;
        background: #ffffff !important;
        padding: 0 !important;
        margin: 0 !important;
        color: #000000 !important;
        overflow: hidden !important;
      }
      .no-print {
        display: none !important;
      }
      .a4-page-container {
        width: 100% !important;
        max-width: 100% !important;
        min-height: auto !important;
        height: calc(100vh - 12mm) !important;
        max-height: 282mm !important;
        padding: 4mm 6mm !important;
        margin: 0 auto !important;
        box-shadow: none !important;
        border: 1.5px solid #0f172a !important;
        border-radius: 4px !important;
        page-break-inside: avoid !important;
        page-break-after: avoid !important;
        page-break-before: avoid !important;
        break-inside: avoid !important;
        break-after: avoid !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: space-between !important;
        box-sizing: border-box !important;
      }
      .details-table th {
        background: #f1f5f9 !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .header-badge .tag {
        background: #000000 !important;
        color: #ffffff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }
      .qr-frame {
        border: 2px solid #000000 !important;
        box-shadow: none !important;
      }
    }
  </style>
</head>
<body>

  <!-- No-Print Toolbar -->
  <div class="no-print">
    <div class="info">
      <h2><i class="fa fa-qrcode text-primary"></i> A4 Single QR Code Dispatch Slip</h2>
      <p>
        <strong>Bill No:</strong> <?php echo htmlspecialchars($billNo); ?> &nbsp;|&nbsp; 
        <strong>Product:</strong> <?php echo htmlspecialchars($prodNameOnly); ?> (<?php echo htmlspecialchars($varietyOnly); ?>) &nbsp;|&nbsp; 
        <strong>Trays:</strong> <?php echo $totalTrays; ?> (<?php echo number_format($totalPlants); ?> Plants)
      </p>
    </div>
    <div class="actions">
      <button type="button" class="btn btn-secondary" onclick="window.close()"><i class="fa fa-times"></i> Close</button>
      <button type="button" class="btn btn-primary" onclick="window.print()"><i class="fa fa-print"></i> Print (A4 Normal Printer)</button>
    </div>
  </div>

  <?php if (empty($labels)): ?>
    <div style="text-align:center; padding: 60px 20px; background:#fff; max-width:600px; margin:40px auto; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 4px 12px rgba(0,0,0,0.05);">
      <i class="fa fa-exclamation-triangle fa-3x text-warning" style="margin-bottom:15px;"></i>
      <h3 style="font-weight:800; color:#0f172a; margin-bottom:8px;">No Order Records Found</h3>
      <p style="color:#64748b; font-size:0.95rem;">No tray records found for the selected order in this polyhouse bed.</p>
    </div>
  <?php else: ?>
    <!-- Printable A4 Slip Content -->
    <div class="a4-page-container">
      
      <div>
        <!-- Slip Header -->
        <div class="slip-header">
          <div>
            <div class="brand-title">KARJEE FARM</div>
            <div class="brand-subtitle"><i class="fa fa-seedling me-1"></i> Polyhouse Order Dispatch & Quality Tag</div>
          </div>
          <div class="header-badge">
            <span class="tag">DISPATCH TAG</span>
            <div class="date"><i class="fa fa-calendar-alt me-1"></i> Printed: <span id="localPrintedTime"><?php echo date('d-M-Y h:i A'); ?></span></div>
          </div>
        </div>

        <!-- Centered Single QR Code -->
        <div class="qr-center-block">
          <div class="qr-frame">
            <div id="qrcodeCenter"></div>
          </div>
          <div class="qr-caption"><i class="fa fa-qrcode me-1"></i> Scan Bed QR Code</div>
          <div class="qr-subcaption">
            Bed QR Code: <strong class="font-monospace text-primary" style="font-size: 1.05rem; color:#4f46e5 !important;"><?php echo htmlspecialchars($bedQrCode); ?></strong>
            &nbsp;•&nbsp;
            <strong class="text-dark"><?php echo htmlspecialchars($phName); ?></strong>
            &nbsp;•&nbsp;
            <strong class="text-dark"><?php echo htmlspecialchars($bedNoDisplay); ?></strong>
          </div>
        </div>

        <!-- 8 Required Fields + Bed QR Code Table -->
        <table class="details-table">
          <tbody>
            <tr>
              <th><i class="fa fa-qrcode me-1 text-primary"></i> Bed QR Code</th>
              <td class="highlight-cell font-monospace" style="color: #4f46e5 !important; font-family:'JetBrains Mono',monospace; letter-spacing:1px; font-size:1.25rem !important;">
                <?php echo htmlspecialchars($bedQrCode); ?>
              </td>
            </tr>
            <tr>
              <th><i class="fa fa-leaf me-1 text-primary"></i> Product Name</th>
              <td class="highlight-cell"><?php echo htmlspecialchars($prodNameOnly); ?></td>
            </tr>
            <tr>
              <th><i class="fa fa-dna me-1 text-primary"></i> Variety</th>
              <td class="highlight-cell highlight-blue"><?php echo htmlspecialchars($varietyOnly); ?></td>
            </tr>
            <tr>
              <th><i class="fa fa-warehouse me-1 text-primary"></i> Polyhouse No</th>
              <td class="highlight-cell" style="font-size: 1.05rem !important; color: #0f172a !important;">
                <strong><?php echo htmlspecialchars($phName); ?></strong> 
                <span style="font-size: 0.9rem; font-weight: 500; color: #64748b;">(<?php echo htmlspecialchars($locName); ?>)</span>
              </td>
            </tr>
            <tr>
              <th><i class="fa fa-layer-group me-1 text-primary"></i> Bed No</th>
              <td class="highlight-cell" style="font-size: 1.05rem !important;">
                <strong><?php echo htmlspecialchars($bedNoDisplay); ?></strong>
              </td>
            </tr>
            <tr>
              <th><i class="fa fa-file-invoice me-1 text-primary"></i> Bill NO</th>
              <td class="highlight-cell font-monospace" style="font-family:'JetBrains Mono',monospace; letter-spacing:0.5px;"><?php echo htmlspecialchars($billNo); ?></td>
            </tr>
            <tr>
              <th><i class="fa fa-hashtag me-1 text-primary"></i> Seed Lot NO</th>
              <td class="highlight-cell" style="color:#b91c1c !important; font-family:'JetBrains Mono',monospace;"><?php echo htmlspecialchars($seedLotNo); ?></td>
            </tr>
            <tr>
              <th><i class="fa fa-calendar-check me-1 text-primary"></i> Sowing Date</th>
              <td><strong><?php echo htmlspecialchars($sowDate); ?></strong></td>
            </tr>
            <tr>
              <th><i class="fa fa-truck me-1 text-primary"></i> Dispatch Date</th>
              <td class="highlight-green"><strong><?php echo htmlspecialchars($dispDate); ?></strong></td>
            </tr>
            <tr>
              <th><i class="fa fa-boxes-stacked me-1 text-primary"></i> No of Trays with QTY</th>
              <td class="highlight-cell highlight-green">
                <strong><?php echo $totalTrays; ?> Trays</strong> 
                <span style="font-size: 0.95rem; font-weight: 600; color: #475569;">(<?php echo number_format($totalPlants); ?> Total Plants @ <?php echo $qtyPerTray; ?> Plants/Tray)</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Bottom Signature & Verification Section -->
      <div>
        <div class="signatures-grid">
          <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Prepared By</div>
            <div class="sig-role">Polyhouse Operator</div>
          </div>
          <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Quality Verified By</div>
            <div class="sig-role">Nursery Supervisor</div>
          </div>
          <div class="sig-box">
            <div class="sig-line"></div>
            <div class="sig-label">Dispatched / Received By</div>
            <div class="sig-role">Transport / Client Sign</div>
          </div>
        </div>

        <!-- Footer -->
        <div class="slip-footer">
          <div>Karjee Farm ERP System • Standard A4 Normal Printer Slip</div>
          <div>Order Tracking ID: <?php echo htmlspecialchars($stockorder_no ?: $billNo); ?></div>
        </div>
      </div>

    </div>
  <?php endif; ?>

  <script>
    function updateLocalPrintedTime() {
      try {
        const now = new Date();
        const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const day = String(now.getDate()).padStart(2, '0');
        const month = months[now.getMonth()];
        const year = now.getFullYear();
        let hours = now.getHours();
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12;
        hours = hours ? String(hours).padStart(2, '0') : '12';
        const formatted = `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
        const el = document.getElementById('localPrintedTime');
        if (el) el.textContent = formatted;
      } catch(e) {}
    }

    document.addEventListener("DOMContentLoaded", function() {
      updateLocalPrintedTime();
      window.addEventListener('beforeprint', updateLocalPrintedTime);

      const qrContainer = document.getElementById("qrcodeCenter");
      if (qrContainer) {
        qrContainer.innerHTML = "";
        const qrDataText = <?php echo json_encode($qrPayload, JSON_UNESCAPED_UNICODE); ?>;
        new QRCode(qrContainer, {
          text: qrDataText,
          width: 165,
          height: 165,
          colorDark: "#000000",
          colorLight: "#ffffff",
          correctLevel: QRCode.CorrectLevel.M
        });
      }

      <?php if (!empty($_GET['autoprint'])): ?>
      setTimeout(function() {
        window.print();
      }, 450);
      <?php endif; ?>
    });
  </script>
</body>
</html>
