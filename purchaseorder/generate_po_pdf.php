<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . "/connection.php";
require_once __DIR__ . "/po_functions.php";

$tcpdfPath = dirname(__DIR__) . "/pdfroutines/tcpdf/tcpdf.php";
if (!file_exists($tcpdfPath)) {
    die("TCPDF library not found.");
}
require_once $tcpdfPath;

// Normalize DB connections
if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
    $krfconn = (isset($conn) && ($conn instanceof mysqli)) ? $conn : null;
}

$poId = intval($_GET['po_id'] ?? 0);
if ($poId <= 0) {
    die("Invalid Purchase Order ID specified.");
}

$po = getPurchaseOrderById($krfconn, $poId);
if (!$po) {
    die("Purchase Order not found.");
}

// Initialize TCPDF
$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('KRF Purchase Order System');
$pdf->SetAuthor('Karjee Farm');
$pdf->SetTitle('Purchase Order - ' . ($po['po_number'] ?? $poId));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(12, 10, 12);
$pdf->SetAutoPageBreak(true, 12);
$pdf->AddPage();

// Pure monochrome black drawing & text
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetTextColor(0, 0, 0);

// -------------------------------------------------------------
// 1. Header (Image 4)
// -------------------------------------------------------------
$pdf->SetFont('helvetica', 'B', 8.5);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 4.5, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
$pdf->Ln(1);

$headerStartY = $pdf->GetY();

// Logo on left
$logoCandidates = [
    dirname(__DIR__) . '/img/logo/logo.png',
    dirname(__DIR__) . '/krf-orderandsales/logo/logo.png',
    __DIR__ . '/../img/logo/logo.png'
];
$logoPath = '';
foreach ($logoCandidates as $cand) {
    if (file_exists($cand)) {
        $logoPath = $cand;
        break;
    }
}

if (!empty($logoPath)) {
    $pdf->Image($logoPath, 12, $headerStartY, 26, 0, '', '', '', false, 300, '', false, false, 0);
}

// Company details on right
$pdf->SetY($headerStartY);
$pdf->SetX(45);
$pdf->SetFont('helvetica', 'B', 15);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 6.5, 'KARJEE FARM', 0, 1, 'R');

$pdf->SetFont('helvetica', '', 8.5);
$pdf->SetTextColor(0, 0, 0);
$cmpInfo = "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\nMobile No: 6261832409, 7879615628";
$pdf->SetX(45);
$pdf->MultiCell(0, 4.2, $cmpInfo, 0, 'R', 0, 1);

// Move Y directly below logo and text
$logoBottomY = $headerStartY + 23;
$textBottomY = $pdf->GetY();
$titleY = max($logoBottomY, $textBottomY) + 1.0;

// Title: Purchase Draft Order (moved up, pure black)
$pdf->SetY($titleY);
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5.5, 'PURCHASE DRAFT ORDER', 0, 1, 'C');

// Generous space at the bottom before the PO Details banner
$pdf->Ln(4.5);

// -------------------------------------------------------------
// 2. PO Details Banner (Image 2) - Pure Black & White
// -------------------------------------------------------------
$status = !empty($po['status']) ? $po['status'] : 'Draft';
$poNumber = htmlspecialchars($po['po_number'] ?? 'N/A');
$vendorName = !empty($po['VendorName']) ? htmlspecialchars($po['VendorName']) : 'N/A';
$poDate = !empty($po['po_date']) && $po['po_date'] !== '0000-00-00' ? htmlspecialchars($po['po_date']) : date('Y-m-d');

// Fine, crisp thin hairline black border
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineWidth(0.08);

$infoHtml = '
<table border="1" cellpadding="5" cellspacing="0" style="width: 100%; background-color: #ffffff; border: 0.5px solid #000000; border-collapse: collapse;">
    <tr>
        <td width="15%" style="border: 0.5px solid #000000; vertical-align: middle;">
            <span style="font-size: 7pt; font-weight: bold; color: #000000; text-transform: uppercase;">STATUS</span><br>
            <span style="border: 0.5px solid #000000; background-color: #ffffff; color: #000000; font-size: 7.5pt; font-weight: bold; padding: 1px 6px;">&nbsp;' . htmlspecialchars($status) . '&nbsp;</span>
        </td>
        <td width="28%" style="border: 0.5px solid #000000; vertical-align: middle;">
            <span style="font-size: 7pt; font-weight: bold; color: #000000; text-transform: uppercase;">PO NUMBER</span><br>
            <span style="font-size: 9.5pt; font-weight: bold; color: #000000;">' . $poNumber . '</span>
        </td>
        <td width="37%" style="border: 0.5px solid #000000; vertical-align: middle;">
            <span style="font-size: 7pt; font-weight: bold; color: #000000; text-transform: uppercase;">VENDOR / SUPPLIER</span><br>
            <span style="font-size: 9pt; font-weight: bold; color: #000000;">' . $vendorName . '</span>
        </td>
        <td width="20%" style="border: 0.5px solid #000000; vertical-align: middle;">
            <span style="font-size: 7pt; font-weight: bold; color: #000000; text-transform: uppercase;">PO DATE</span><br>
            <span style="font-size: 9pt; color: #000000;">' . $poDate . '</span>
        </td>
    </tr>
</table>
';

$pdf->writeHTML($infoHtml, true, false, true, false, '');
$pdf->Ln(2.5);

// -------------------------------------------------------------
// 3. Items Table (Multi-Column Grid Layout) - Pure Black & White
// -------------------------------------------------------------
$tableHtml = '
<table border="1" cellpadding="4" cellspacing="0" style="width: 100%; border-collapse: collapse; border: 0.5px solid #000000; background-color: #ffffff;">
    <thead>
        <tr style="background-color: #ffffff;">
            <th width="6%" style="border: 0.5px solid #000000; font-size: 7.5pt; font-weight: bold; color: #000000; text-align: center; padding: 5px 4px;">
                #
            </th>
            <th width="40%" style="border: 0.5px solid #000000; font-size: 7.5pt; font-weight: bold; color: #000000; text-align: left; padding: 5px 8px;">
                ITEM / PRODUCT NAME
            </th>
            <th width="20%" style="border: 0.5px solid #000000; font-size: 7.5pt; font-weight: bold; color: #000000; text-align: center; padding: 5px 6px;">
                PACKAGING / SIZE
            </th>
            <th width="20%" style="border: 0.5px solid #000000; font-size: 7.5pt; font-weight: bold; color: #000000; text-align: center; padding: 5px 6px;">
                UOM &amp; TRACKING
            </th>
            <th width="14%" style="border: 0.5px solid #000000; font-size: 7.5pt; font-weight: bold; color: #000000; text-align: center; padding: 5px 6px;">
                ORDERED QTY
            </th>
        </tr>
    </thead>
    <tbody>';

$items = $po['items'] ?? [];
$totalQty = 0;
$idx = 0;

if (!empty($items)) {
    foreach ($items as $item) {
        $idx++;
        $itemName = htmlspecialchars($item['item_name'] ?? 'Item');
        $sku = !empty($item['sku']) ? htmlspecialchars($item['sku']) : "";
        $skuHtml = $sku ? '<br><span style="font-size: 7.5pt; color: #000000; font-family: courier, monospace;">' . $sku . '</span>' : '';
        
        // Packaging / Size
        $unit = !empty($item['unit']) ? htmlspecialchars($item['unit']) : '';
        $unitSize = !empty($item['unit_size']) ? htmlspecialchars($item['unit_size']) : '';
        if ($unit && $unitSize) {
            $packText = $unit . ' - ' . $unitSize;
        } else if ($unit) {
            $packText = $unit;
        } else if ($unitSize) {
            $packText = $unitSize;
        } else {
            $packText = '—';
        }

        // UOM Badges - Monochrome black border and black text
        $baseUomText = !empty($item['base_uom']) ? htmlspecialchars($item['base_uom']) : 'PKT';
        $purUomText = !empty($item['purchase_uom']) ? htmlspecialchars($item['purchase_uom']) : 'N/A';
        $isPurchaseMode = (!empty($item['uom_mode']) && $item['uom_mode'] === 'PURCHASE');
        $modeText = $isPurchaseMode ? "PURCHASE ({$purUomText})" : "BASE ({$baseUomText})";
        
        $badgesHtml = '<span style="border: 0.5px solid #000000; color: #000000; font-size: 7pt; font-weight: bold; padding: 1px 4px; background-color: #ffffff;">&nbsp;' . $modeText . '&nbsp;</span>';
        
        if (!empty($item['batch_required'])) {
            $badgesHtml .= '<br><span style="border: 0.5px solid #000000; color: #000000; font-size: 6.5pt; font-weight: bold; padding: 1px 3px; background-color: #ffffff;">&nbsp;Batch Req&nbsp;</span>';
        }
        if (!empty($item['expiry_required'])) {
            $badgesHtml .= '<br><span style="border: 0.5px solid #000000; color: #000000; font-size: 6.5pt; font-weight: bold; padding: 1px 3px; background-color: #ffffff;">&nbsp;Expiry Req&nbsp;</span>';
        }
        if (!empty($item['serial_required'])) {
            $badgesHtml .= '<br><span style="border: 0.5px solid #000000; color: #000000; font-size: 6.5pt; font-weight: bold; padding: 1px 3px; background-color: #ffffff;">&nbsp;Serial Req&nbsp;</span>';
        }

        $qty = (float)($item['quantity'] ?? 0);
        $totalQty += $qty;
        $qtyFormatted = number_format($qty, 2);

        $tableHtml .= '
        <tr style="background-color: #ffffff;">
            <td width="6%" valign="middle" align="center" style="border: 0.5px solid #000000; padding: 5px 2px;">
                <span style="font-size: 8pt; font-weight: bold; color: #000000;">' . $idx . '</span>
            </td>
            <td width="40%" valign="middle" style="border: 0.5px solid #000000; padding: 5px 8px;">
                <span style="font-size: 8.5pt; font-weight: bold; color: #000000;">' . $itemName . '</span>' . $skuHtml . '
            </td>
            <td width="20%" valign="middle" align="center" style="border: 0.5px solid #000000; padding: 5px 6px;">
                <span style="font-size: 8pt; color: #000000; font-weight: 500;">' . $packText . '</span>
            </td>
            <td width="20%" valign="middle" align="center" style="border: 0.5px solid #000000; padding: 5px 6px;">
                ' . $badgesHtml . '
            </td>
            <td width="14%" valign="middle" align="center" style="border: 0.5px solid #000000; padding: 5px 6px;">
                <span style="font-size: 9.5pt; font-weight: bold; color: #000000;">' . $qtyFormatted . '</span>
            </td>
        </tr>';
    }
} else {
    $tableHtml .= '
        <tr>
            <td colspan="5" style="border: 0.5px solid #000000; text-align: center; color: #000000; padding: 12px; font-size: 8.5pt;">
                No items recorded for this purchase order.
            </td>
        </tr>';
}

$tableHtml .= '
        <tr style="background-color: #ffffff;">
            <td colspan="4" style="border: 0.5px solid #000000; font-size: 8pt; font-weight: bold; color: #000000; text-align: right; padding: 6px 10px;">
                TOTAL ORDERED QUANTITY (' . count($items) . ' ITEMS):
            </td>
            <td style="border: 0.5px solid #000000; font-size: 9.5pt; font-weight: bold; color: #000000; text-align: center; padding: 6px 6px;">
                ' . number_format($totalQty, 2) . '
            </td>
        </tr>
    </tbody>
</table>';

$pdf->writeHTML($tableHtml, true, false, true, false, '');

// Clean any buffered output and output PDF
if (ob_get_length()) {
    ob_end_clean();
}

$filename = ($po['po_number'] ?? ('PO-' . $poId)) . '.pdf';
$pdf->Output($filename, 'I');
exit;
