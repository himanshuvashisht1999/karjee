<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('Asia/Kolkata');

require_once(__DIR__ . '/../../connection.php');

$gpNo = trim($_GET['gatepass_no'] ?? '');
$psono = trim($_GET['psono'] ?? '');
$invoiceno = trim($_GET['invoiceno'] ?? '');

if (empty($gpNo) && empty($psono) && empty($invoiceno)) {
    die("Gate pass number, provisional order (PSO) or invoice number is required.");
}

// 1. Fetch gatepass tracking record
$gp = null;
if (!empty($gpNo)) {
    $stmt = $krfconn->prepare("SELECT * FROM krf_gatepass_tracking WHERE gatepass_no = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $gpNo);
    $stmt->execute();
    $gp = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$gp && !empty($psono)) {
    $stmt = $krfconn->prepare("SELECT * FROM krf_gatepass_tracking WHERE psono = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $psono);
    $stmt->execute();
    $gp = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$gp && !empty($invoiceno)) {
    $stmt = $krfconn->prepare("SELECT * FROM krf_gatepass_tracking WHERE invoiceno = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $invoiceno);
    $stmt->execute();
    $gp = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// If no gatepass record yet, build from sales_invoice
if (!$gp) {
    $invQuery = !empty($invoiceno) ? $invoiceno : (!empty($psono) ? $psono : $gpNo);
    $stmt = $krfconn->prepare("SELECT * FROM sales_invoice WHERE invoiceno = ? OR psono = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ss', $invQuery, $invQuery);
    $stmt->execute();
    $inv = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$inv) {
        die("Gate pass / Invoice record not found.");
    }

    $gp = [
        'gatepass_no'      => 'GP-PASS',
        'psono'            => $inv['psono'] ?: '-',
        'sono'             => $inv['sono'] ?: '-',
        'invoiceno'        => $inv['invoiceno'],
        'invoice_date'     => $inv['invoicedate'],
        'clientname'       => $inv['clientname'],
        'clientphone'      => $inv['mobileno'],
        'delivery_address' => $inv['delivery_address'] ?: $inv['invoicing_address'],
        'districtname'     => $inv['districtname'],
        'statename'        => $inv['statename'],
        'vehicleno'        => $inv['vehicleno'] ?: 'Direct Client Pickup',
        'drivername'       => $inv['drivername'] ?: 'Self',
        'drivermobile'     => $inv['drivermobileno'] ?: '-',
        'transporter_name' => 'Direct Delivery',
        'tray_cavity'      => '126 Cavity',
        'remarks'          => $inv['remarks'],
        'status'           => 'dispatched',
        'created_by'       => $inv['created_by'],
        'created_at'       => $inv['created_at']
    ];
}

// 2. Fetch gatepass items
$items = [];
if (!empty($gp['gatepass_no']) && $gp['gatepass_no'] !== 'GP-PASS') {
    $itemStmt = $krfconn->prepare("SELECT * FROM krf_gatepass_items WHERE gatepass_no = ? ORDER BY id ASC");
    $itemStmt->bind_param('s', $gp['gatepass_no']);
    $itemStmt->execute();
    $res = $itemStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $itemStmt->close();
}

if (empty($items)) {
    // Fall back to sales_invoice_items
    $invNo = $gp['invoiceno'] ?: '';
    $itemStmt = $krfconn->prepare("SELECT productname as product_name, stockorder_no as stockorderno, '126 Cavity' as tray_cavity, CEIL(quantity / 126) as trays_count, quantity as plant_qty, status FROM sales_invoice_items WHERE invoiceno = ? AND productid != 'BOX_PACKING' ORDER BY id ASC");
    $itemStmt->bind_param('s', $invNo);
    $itemStmt->execute();
    $res = $itemStmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $items[] = $row;
    }
    $itemStmt->close();
}

$totalPlants = 0;
$totalTrays = 0;
foreach ($items as $it) {
    $totalPlants += (int)($it['plant_qty'] ?? 0);
    $totalTrays += (int)($it['trays_count'] ?? 0);
}
if ($totalTrays === 0 && $totalPlants > 0) {
    $totalTrays = (int)ceil($totalPlants / 126);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dispatch Gate Pass - <?= htmlspecialchars($gp['gatepass_no']) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #f1f5f9;
      color: #0f172a;
      margin: 0;
      padding: 20px;
    }
    .print-actions {
      max-width: 860px;
      margin: 0 auto 15px auto;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .btn-act {
      padding: 8px 16px;
      font-size: 0.85rem;
      font-weight: 700;
      border-radius: 8px;
      border: none;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      text-decoration: none;
    }
    .btn-print { background: #4f46e5; color: #fff; }
    .btn-print:hover { background: #4338ca; }
    .btn-close { background: #e2e8f0; color: #475569; }
    .btn-close:hover { background: #cbd5e1; }
    .btn-inv { background: #10b981; color: #fff; }
    .btn-inv:hover { background: #059669; }

    .gatepass-card {
      max-width: 860px;
      margin: 0 auto;
      background: #fff;
      border: 2px solid #0f172a;
      border-radius: 8px;
      padding: 24px 28px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06);
    }
    .gp-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #0f172a;
      padding-bottom: 12px;
      margin-bottom: 14px;
    }
    .gp-brand h1 {
      margin: 0;
      font-size: 1.55rem;
      font-weight: 800;
      color: #15803d;
      letter-spacing: -0.02em;
    }
    .gp-brand p {
      margin: 3px 0 0 0;
      font-size: 0.78rem;
      color: #475569;
      line-height: 1.35;
    }
    .gp-badge-box {
      text-align: right;
    }
    .gp-title-badge {
      display: inline-block;
      background: #0f172a;
      color: #fff;
      padding: 6px 14px;
      font-size: 0.92rem;
      font-weight: 800;
      letter-spacing: 0.05em;
      border-radius: 4px;
      text-transform: uppercase;
    }
    .gp-meta {
      margin-top: 6px;
      font-size: 0.82rem;
      color: #334155;
    }

    .gp-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
      margin-bottom: 16px;
    }
    .info-pane {
      background: #f8fafc;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      padding: 10px 14px;
    }
    .pane-title {
      font-size: 0.75rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #475569;
      border-bottom: 1px solid #e2e8f0;
      padding-bottom: 4px;
      margin-bottom: 6px;
    }
    .field-row {
      display: flex;
      font-size: 0.82rem;
      margin-bottom: 4px;
      line-height: 1.35;
    }
    .field-lbl {
      width: 125px;
      flex-shrink: 0;
      color: #64748b;
      font-weight: 600;
    }
    .field-val {
      color: #0f172a;
      font-weight: 700;
      flex: 1;
    }

    .gp-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 14px;
      font-size: 0.82rem;
    }
    .gp-table th {
      background: #0f172a;
      color: #fff;
      padding: 7px 10px;
      text-align: left;
      font-weight: 700;
      border: 1px solid #0f172a;
    }
    .gp-table td {
      padding: 7px 10px;
      border: 1px solid #cbd5e1;
      color: #1e293b;
    }
    .gp-table tr:nth-child(even) td {
      background: #f8fafc;
    }
    .total-row td {
      background: #f1f5f9 !important;
      font-weight: 800;
      border-top: 2px solid #0f172a;
    }

    .verification-box {
      border: 1px dashed #94a3b8;
      border-radius: 6px;
      padding: 8px 12px;
      margin-bottom: 20px;
      background: #fff;
      font-size: 0.78rem;
    }
    .verification-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 10px;
      margin-top: 6px;
    }
    .chk-item {
      display: flex;
      align-items: center;
      gap: 6px;
      color: #334155;
    }
    .chk-box {
      width: 14px;
      height: 14px;
      border: 1.5px solid #0f172a;
      display: inline-block;
    }

    .signatures-row {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-top: 36px;
      text-align: center;
      font-size: 0.76rem;
      color: #475569;
    }
    .sig-line {
      border-top: 1px solid #475569;
      padding-top: 4px;
      font-weight: 700;
      color: #0f172a;
    }

    @media print {
      body { background: #fff; padding: 0; }
      .print-actions { display: none !important; }
      .gatepass-card {
        border: 2px solid #000;
        box-shadow: none;
        padding: 16px;
        max-width: 100%;
        border-radius: 0;
      }
    }
  </style>
</head>
<body>

<div class="print-actions">
  <div>
    <button type="button" class="btn-act btn-print" onclick="window.print();">
      <i class="fa fa-print"></i> Print Gate Pass
    </button>
    <a href="../../krf-orderandsales/modal/genrateInvoicePDF.php?invno=<?= urlencode($gp['invoiceno']) ?>&companyid=19&subcompanyid=12&projectid=17&mode=I" target="_blank" class="btn-act btn-inv">
      <i class="fa fa-file-invoice"></i> View Invoice
    </a>
  </div>
  <button type="button" class="btn-act btn-close" onclick="window.close();">
    <i class="fa fa-xmark"></i> Close
  </button>
</div>

<div class="gatepass-card">

  <!-- Header -->
  <div class="gp-header">
    <div class="gp-brand">
      <h1>KARJEE FARM</h1>
      <p>
        Hi-Tech Commercial Nursery & Production Center<br>
        Ambikapur / Surguja, Chhattisgarh &bull; Mob: 6261894163 &bull; GSTIN: 22BWJPG1381G1ZU
      </p>
    </div>
    <div class="gp-badge-box">
      <div class="gp-title-badge">DISPATCH GATE PASS</div>
      <div class="gp-meta">
        <strong>Pass No:</strong> <span style="font-family:'JetBrains Mono',monospace; font-weight:800; color:#1e40af;"><?= htmlspecialchars($gp['gatepass_no']) ?></span><br>
        <strong>Date & Time:</strong> <?= date('d-M-Y h:i A', strtotime($gp['created_at'] ?: 'now')) ?>
      </div>
    </div>
  </div>

  <!-- Info Grid -->
  <div class="gp-info-grid">
    <!-- Left: Order & Invoice Reference -->
    <div class="info-pane">
      <div class="pane-title"><i class="fa fa-file-lines mr-1"></i> Order & Invoice Reference</div>
      <div class="field-row">
        <span class="field-lbl">Invoice No:</span>
        <span class="field-val" style="font-family:'JetBrains Mono',monospace; color:#0f172a;"><?= htmlspecialchars($gp['invoiceno'] ?: '-') ?></span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Provisional (PSO):</span>
        <span class="field-val" style="font-family:'JetBrains Mono',monospace;"><?= htmlspecialchars($gp['psono'] ?: '-') ?></span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Sales Order (SO):</span>
        <span class="field-val" style="font-family:'JetBrains Mono',monospace; color:#059669;"><?= htmlspecialchars($gp['sono'] ?: '-') ?></span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Dispatch Location:</span>
        <span class="field-val">Karjee Farm Open Sale Counter</span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Billed / Booked By:</span>
        <span class="field-val"><?= htmlspecialchars($gp['created_by'] ?: 'Admin') ?></span>
      </div>
    </div>

    <!-- Right: Transport & Client Details -->
    <div class="info-pane">
      <div class="pane-title"><i class="fa fa-truck mr-1"></i> Transport & Consignee</div>
      <div class="field-row">
        <span class="field-lbl">Client / Consignee:</span>
        <span class="field-val" style="color:#1e40af;"><?= htmlspecialchars($gp['clientname'] ?: 'Customer') ?></span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Mobile Number:</span>
        <span class="field-val"><?= htmlspecialchars($gp['clientphone'] ?: '-') ?></span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Destination / Addr:</span>
        <span class="field-val"><?= htmlspecialchars($gp['delivery_address'] ?: '-') ?></span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Vehicle Number:</span>
        <span class="field-val" style="font-family:'JetBrains Mono',monospace; color:#dc2626;"><?= htmlspecialchars($gp['vehicleno'] ?: 'Direct Client Pickup') ?></span>
      </div>
      <div class="field-row">
        <span class="field-lbl">Driver Name / Mob:</span>
        <span class="field-val"><?= htmlspecialchars($gp['drivername'] ?: 'Self') ?> <?= !empty($gp['drivermobile']) && $gp['drivermobile'] !== '-' ? '(' . htmlspecialchars($gp['drivermobile']) . ')' : '' ?></span>
      </div>
    </div>
  </div>

  <!-- Dispatched Items Table -->
  <table class="gp-table">
    <thead>
      <tr>
        <th style="width: 5%;">#</th>
        <th style="width: 45%;">Item / Plant Variety</th>
        <th style="width: 20%;">Stock Order / Batch</th>
        <th style="width: 15%; text-align: center;">Trays Count</th>
        <th style="width: 15%; text-align: right;">Plants Qty</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($items)): ?>
        <?php foreach ($items as $idx => $it): ?>
          <tr>
            <td><?= $idx + 1 ?></td>
            <td>
              <strong><?= htmlspecialchars($it['product_name']) ?></strong>
              <div style="font-size:0.74rem; color:#64748b;"><?= htmlspecialchars($it['tray_cavity'] ?: '126 Cavity') ?></div>
            </td>
            <td style="font-family:'JetBrains Mono',monospace;"><?= htmlspecialchars($it['stockorderno'] ?: 'OPEN_SALE') ?></td>
            <td style="text-align: center; font-weight:700;"><?= (int)($it['trays_count'] ?: ceil($it['plant_qty'] / 126)) ?></td>
            <td style="text-align: right; font-weight:800; color:#0f172a;"><?= number_format((int)$it['plant_qty']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr>
          <td colspan="5" style="text-align:center; color:#94a3b8; padding: 16px;">No item details available.</td>
        </tr>
      <?php endif; ?>
      <tr class="total-row">
        <td colspan="3" style="text-align: right;">TOTAL DISPATCHED:</td>
        <td style="text-align: center; color:#1e40af;"><?= number_format($totalTrays) ?> Trays</td>
        <td style="text-align: right; color:#15803d; font-size:0.92rem;"><?= number_format($totalPlants) ?> Plants</td>
      </tr>
    </tbody>
  </table>

  <!-- Gate Verification Checkpoints -->
  <div class="verification-box">
    <strong><i class="fa fa-shield-halved mr-1"></i> Physical Gate Verification & Security Checklist:</strong>
    <div class="verification-grid">
      <div class="chk-item"><span class="chk-box"></span> Physical Tray Count Verified</div>
      <div class="chk-item"><span class="chk-box"></span> Plant Health / Variety Checked</div>
      <div class="chk-item"><span class="chk-box"></span> Vehicle / Driver Verified</div>
    </div>
  </div>

  <?php if (!empty($gp['remarks'])): ?>
  <div style="font-size: 0.76rem; color:#64748b; margin-bottom: 12px;">
    <strong>Remarks / Notes:</strong> <?= htmlspecialchars($gp['remarks']) ?>
  </div>
  <?php endif; ?>

  <!-- Signatures -->
  <div class="signatures-row">
    <div>
      <div style="height: 32px;"></div>
      <div class="sig-line">Prepared By</div>
    </div>
    <div>
      <div style="height: 32px;"></div>
      <div class="sig-line">Store / Farm Incharge</div>
    </div>
    <div>
      <div style="height: 32px;"></div>
      <div class="sig-line">Driver / Client Signature</div>
    </div>
    <div>
      <div style="height: 32px;"></div>
      <div class="sig-line">Security Guard Signature</div>
    </div>
  </div>

</div>

</body>
</html>
