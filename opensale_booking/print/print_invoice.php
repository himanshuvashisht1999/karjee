<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
date_default_timezone_set('Asia/Kolkata');

$invoiceno = trim($_GET['invoiceno'] ?? $_GET['invno'] ?? '');

if (!empty($invoiceno)) {
    require_once(__DIR__ . '/../../connection.php');
    $companyid = intval($_GET['companyid'] ?? 0);
    $subcompanyid = intval($_GET['subcompanyid'] ?? 0);
    $projectid = intval($_GET['projectid'] ?? 0);

    if ($companyid <= 0 || $subcompanyid <= 0) {
        $st = $krfconn->prepare("SELECT companyid, subcompanyid, projectid FROM sales_invoice WHERE invoiceno = ? OR psono = ? LIMIT 1");
        if ($st) {
            $st->bind_param("ss", $invoiceno, $invoiceno);
            $st->execute();
            $row = $st->get_result()->fetch_assoc();
            $st->close();
            if ($row) {
                if ($companyid <= 0 && !empty($row['companyid'])) $companyid = intval($row['companyid']);
                if ($subcompanyid <= 0 && !empty($row['subcompanyid'])) $subcompanyid = intval($row['subcompanyid']);
                if ($projectid <= 0 && !empty($row['projectid'])) $projectid = intval($row['projectid']);
            }
        }
    }
    if ($companyid <= 0) $companyid = intval($_SESSION['companyid'] ?? 19);
    if ($subcompanyid <= 0) $subcompanyid = intval($_SESSION['subcompanyid'] ?? 12);
    if ($projectid <= 0) $projectid = intval($_SESSION['projectid'] ?? 17);

    header("Location: ../../krf-orderandsales/modal/genrateInvoicePDF.php?invno=" . urlencode($invoiceno) . "&companyid=" . $companyid . "&subcompanyid=" . $subcompanyid . "&projectid=" . $projectid . "&mode=I");
    exit;
}

require_once(__DIR__ . '/../../connection.php');

if (empty($invoiceno)) {
    die("Invoice number is required.");
}

// Fetch invoice items
$itemStmt = $krfconn->prepare("
    SELECT * 
    FROM sales_invoice_items 
    WHERE invoiceno = ? 
    ORDER BY id ASC
");
$itemStmt->bind_param('s', $inv['invoiceno']);
$itemStmt->execute();
$itemsRes = $itemStmt->get_result();
$items = [];
while ($row = $itemsRes->fetch_assoc()) {
    $items[] = $row;
}
$itemStmt->close();

// Fetch payment transactions
$payStmt = $krfconn->prepare("
    SELECT * 
    FROM advance_payment_transactions 
    WHERE invoiceno = ? 
    ORDER BY sno ASC
");
$payStmt->bind_param('s', $inv['invoiceno']);
$payStmt->execute();
$payRes = $payStmt->get_result();
$payments = [];
while ($p = $payRes->fetch_assoc()) {
    $payments[] = $p;
}
$payStmt->close();

function numberToWords($number) {
    $no = floor($number);
    $point = round($number - $no, 2) * 100;
    $hundred = null;
    $digits_1 = array('', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen');
    $digits_2 = array('', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety');
    $digits_3 = array('', 'Thousand', 'Lakh', 'Crore');
    
    $str = array();
    $i = 0;
    while ($no > 0) {
        $divider = ($i == 2) ? 10 : 100;
        $number = floor($no % $divider);
        $no = floor($no / $divider);
        $i += ($divider == 10) ? 1 : 2;
        if ($number) {
            $plural = (($counter = count($str)) && $number > 9) ? 's' : '';
            $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;
            $str [] = ($number < 20) ? $digits_1[$number] . ' ' . $digits_3[$counter] . $plural . ' ' . $hundred
                : $digits_2[floor($number / 10)] . ' ' . $digits_1[$number % 10] . ' ' . $digits_3[$counter] . $plural . ' ' . $hundred;
        } else $str[] = null;
    }
    $str = array_reverse($str);
    $result = implode('', $str);
    $points = ($point) ? " and " . $digits_1[$point / 10] . " " . $digits_1[$point % 10] . " Paise" : '';
    return ($result ? $result . "Rupees " : "") . $points . " Only";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice #<?= htmlspecialchars($inv['invoiceno']) ?> | Karjee Farm</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  
  <style>
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #f8fafc;
      color: #1e293b;
      margin: 0;
      padding: 20px;
    }
    .invoice-card {
      max-width: 820px;
      margin: 0 auto;
      background: #fff;
      border: 1px solid #cbd5e1;
      border-radius: 12px;
      padding: 30px;
      box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
    }
    .inv-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #e2e8f0;
      padding-bottom: 20px;
      margin-bottom: 20px;
    }
    .brand-title {
      font-size: 1.6rem;
      font-weight: 800;
      color: #15803d;
      margin: 0;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .brand-sub {
      font-size: 0.82rem;
      color: #64748b;
      margin-top: 4px;
      line-height: 1.4;
    }
    .inv-badge-title {
      font-size: 1.15rem;
      font-weight: 800;
      color: #1e40af;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      text-align: right;
    }
    .meta-box {
      font-size: 0.82rem;
      color: #475569;
      text-align: right;
      margin-top: 4px;
    }
    .client-details-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
      background: #f8fafc;
      padding: 16px;
      border-radius: 8px;
      border: 1px solid #e2e8f0;
    }
    .info-label {
      font-size: 0.75rem;
      font-weight: 700;
      color: #64748b;
      text-transform: uppercase;
      margin-bottom: 4px;
    }
    .info-val {
      font-size: 0.92rem;
      font-weight: 700;
      color: #0f172a;
    }
    .info-sub {
      font-size: 0.82rem;
      color: #475569;
      margin-top: 2px;
      line-height: 1.4;
    }
    table.inv-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 20px;
      font-size: 0.85rem;
    }
    table.inv-table th {
      background: #f1f5f9;
      color: #334155;
      font-weight: 700;
      padding: 10px 12px;
      border-bottom: 2px solid #cbd5e1;
      text-align: left;
    }
    table.inv-table td {
      padding: 10px 12px;
      border-bottom: 1px solid #e2e8f0;
      vertical-align: middle;
    }
    .calc-box {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 20px;
    }
    .calc-table {
      width: 320px;
      font-size: 0.85rem;
    }
    .calc-table tr td {
      padding: 5px 0;
    }
    .calc-table tr td:last-child {
      text-align: right;
      font-weight: 600;
    }
    .grand-total-row {
      border-top: 2px solid #cbd5e1;
      border-bottom: 2px solid #cbd5e1;
      font-size: 1.05rem;
      font-weight: 800;
      color: #1e40af;
    }
    .payment-badge-paid {
      background: #dcfce7;
      color: #166534;
      font-weight: 700;
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-block;
      font-size: 0.8rem;
    }
    .payment-badge-bal {
      background: #fee2e2;
      color: #991b1b;
      font-weight: 700;
      padding: 4px 8px;
      border-radius: 6px;
      display: inline-block;
      font-size: 0.8rem;
    }
    .btn-print-bar {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-bottom: 15px;
      max-width: 820px;
      margin-left: auto;
      margin-right: auto;
    }
    .btn-print {
      background: #4f46e5;
      color: #fff;
      border: none;
      padding: 8px 16px;
      border-radius: 8px;
      font-size: 0.88rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    @media print {
      body {
        background: #fff;
        padding: 0;
      }
      .btn-print-bar {
        display: none !important;
      }
      .invoice-card {
        border: none;
        box-shadow: none;
        padding: 0;
      }
    }
  </style>
</head>
<body>

<div class="btn-print-bar">
  <button type="button" class="btn-print" onclick="window.print()">
    <i class="fa fa-print"></i> Print Invoice
  </button>
</div>

<div class="invoice-card">
  
  <!-- Header -->
  <div class="inv-header">
    <div>
      <h1 class="brand-title"><i class="fa fa-seedling"></i> KARJEE FARM</h1>
      <div class="brand-sub">
        Premium Agricultural Plants & Seedlings Nursery<br>
        Direct Open Sale Counter Sales Memo<br>
        Phone: +91 98765 43210 | info@karjeefarm.com
      </div>
    </div>
    <div>
      <div class="inv-badge-title">TAX INVOICE</div>
      <div class="meta-box">
        <div><strong>Invoice No:</strong> <?= htmlspecialchars($inv['invoiceno']) ?></div>
        <div><strong>Order No:</strong> <?= htmlspecialchars($inv['psono']) ?></div>
        <div><strong>Date:</strong> <?= date('d-M-Y', strtotime($inv['invoicedate'])) ?></div>
        <div><strong>Created By:</strong> <?= htmlspecialchars($inv['created_by']) ?></div>
      </div>
    </div>
  </div>

  <!-- Client & Supply Details -->
  <div class="client-details-grid">
    <div>
      <div class="info-label">Customer / Farmer Details</div>
      <div class="info-val"><?= htmlspecialchars($inv['clientname'] ?: 'Cash Customer') ?></div>
      <div class="info-sub">
        <?= !empty($inv['mobileno']) ? '<i class="fa fa-phone mr-1"></i> ' . htmlspecialchars($inv['mobileno']) . '<br>' : '' ?>
        <?= !empty($inv['invoicing_address']) ? htmlspecialchars($inv['invoicing_address']) . ', ' : '' ?>
        <?= htmlspecialchars($inv['districtname'] . ' ' . $inv['statename']) ?>
      </div>
    </div>
    <div>
      <div class="info-label">Dispatch & Vehicle Details</div>
      <div class="info-val"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $inv['pickuptype'] ?: 'Pickup by Client'))) ?></div>
      <div class="info-sub">
        <?= !empty($inv['vehicleno']) ? '<strong>Vehicle No:</strong> ' . htmlspecialchars($inv['vehicleno']) . '<br>' : '' ?>
        <?= !empty($inv['drivername']) ? '<strong>Driver:</strong> ' . htmlspecialchars($inv['drivername']) . ' (' . htmlspecialchars($inv['drivermobileno']) . ')<br>' : '' ?>
        <strong>Remarks:</strong> <?= htmlspecialchars($inv['remarks'] ?: 'Direct Open Sale') ?>
      </div>
    </div>
  </div>

  <!-- Items Table -->
  <table class="inv-table">
    <thead>
      <tr>
        <th style="width: 40px;">#</th>
        <th>Product Description & Variety</th>
        <th>Stock Ref</th>
        <th style="text-align: right;">Quantity (Plants)</th>
        <th style="text-align: right;">Rate (₹)</th>
        <th style="text-align: right;">Discount</th>
        <th style="text-align: right;">Amount (₹)</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $idx => $it): ?>
      <tr>
        <td><?= $idx + 1 ?></td>
        <td>
          <strong><?= htmlspecialchars($it['productname']) ?></strong>
          <div style="font-size:0.75rem; color:#64748b;"><?= htmlspecialchars($it['remarks']) ?></div>
        </td>
        <td><small class="text-muted"><?= htmlspecialchars($it['stockorder_no'] ?: 'OPEN_STOCK') ?></small></td>
        <td style="text-align: right; font-weight:700;"><?= number_format($it['quantity']) ?></td>
        <td style="text-align: right;">₹<?= number_format($it['rate'], 2) ?></td>
        <td style="text-align: right;"><?= (float)$it['discount_percent'] > 0 ? (float)$it['discount_percent'] . '%' : '-' ?></td>
        <td style="text-align: right; font-weight:700;">₹<?= number_format($it['amount'], 2) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Calculation Summary -->
  <div class="calc-box">
    <table class="calc-table">
      <tr>
        <td>Taxable Amount:</td>
        <td>₹<?= number_format((float)$inv['taxableamount'], 2) ?></td>
      </tr>
      <?php if ((float)$inv['otherdiscount'] > 0): ?>
      <tr>
        <td>Total Discount:</td>
        <td>-₹<?= number_format((float)$inv['otherdiscount'], 2) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ((float)$inv['tax_amount'] > 0): ?>
      <tr>
        <td>Tax / GST:</td>
        <td>₹<?= number_format((float)$inv['tax_amount'], 2) ?></td>
      </tr>
      <?php endif; ?>
      <?php if ((float)$inv['roundoffamount'] != 0): ?>
      <tr>
        <td>Round Off:</td>
        <td>₹<?= number_format((float)$inv['roundoffamount'], 2) ?></td>
      </tr>
      <?php endif; ?>
      <tr class="grand-total-row">
        <td>Total Payable:</td>
        <td>₹<?= number_format((float)$inv['invoiceamount'], 2) ?></td>
      </tr>
      <tr>
        <td>Paid Amount:</td>
        <td style="color: #15803d; font-weight: 700;">₹<?= number_format((float)$inv['paidamount'], 2) ?></td>
      </tr>
      <tr>
        <td>Balance Due:</td>
        <td style="color: <?= (float)$inv['balanceamount'] > 0 ? '#b91c1c' : '#15803d' ?>; font-weight: 700;">
          ₹<?= number_format((float)$inv['balanceamount'], 2) ?>
        </td>
      </tr>
    </table>
  </div>

  <div style="font-size:0.8rem; margin-bottom: 20px;">
    <strong>Amount in Words:</strong> <?= numberToWords((float)$inv['invoiceamount']) ?>
  </div>

  <!-- Payments Breakdown -->
  <?php if (!empty($payments)): ?>
  <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:20px; font-size:0.82rem;">
    <div style="font-weight:700; color:#334155; margin-bottom:6px;"><i class="fa fa-receipt mr-1"></i> Payment Transaction Records:</div>
    <?php foreach ($payments as $p): ?>
      <div style="display:flex; justify-content:space-between; margin-bottom:3px;">
        <span><?= htmlspecialchars($p['paymentdate']) ?> | <strong><?= htmlspecialchars($p['sourceofpayment']) ?></strong> <?= !empty($p['utrnumber']) ? '(Ref: ' . htmlspecialchars($p['utrnumber']) . ')' : '' ?></span>
        <strong style="color:#15803d;">₹<?= number_format((float)$p['advanceamount'], 2) ?></strong>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Terms & Signature -->
  <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-top:30px; padding-top:20px; border-top:1px dashed #cbd5e1; font-size:0.78rem; color:#64748b;">
    <div>
      <strong>Terms & Conditions:</strong><br>
      1. Plants/seedlings once sold and delivered cannot be returned.<br>
      2. Quality guaranteed at the time of delivery from Open Sale counter.<br>
      3. Subject to local jurisdiction.
    </div>
    <div style="text-align:center; min-width:180px;">
      <div style="margin-bottom:40px;">For Karjee Farm</div>
      <div style="border-top:1px solid #94a3b8; padding-top:4px; font-weight:700; color:#1e293b;">Authorized Signatory</div>
    </div>
  </div>

</div>

</body>
</html>
