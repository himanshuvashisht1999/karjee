<?php
/**
 * Gate Pass Generator - Backend Controller & AJAX API
 * Path: /krf-orderandsales/modal/gatepass_modal.php
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', '0');
date_default_timezone_set('Asia/Kolkata');

require_once(__DIR__ . '/../../connection.php');

$loginid      = $_SESSION['loginid'] ?? $_SESSION['userid'] ?? 'admin';
$loginname    = $_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? 'Admin';
$loginroleid  = (int)($_SESSION['loginroleid'] ?? 4);
$companyid    = (int)($_SESSION['companyid'] ?? 1);
$subcompanyid = (int)($_SESSION['subcompanyid'] ?? 1);
$projectid    = (int)($_SESSION['projectid'] ?? 17);
if ($projectid <= 0) $projectid = 17;

$action = trim($_REQUEST['action'] ?? '');

function outJson($data) {
    if (ob_get_level() > 0) {
        @ob_clean();
    }
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// -------------------------------------------------------------
// Helper: Generate Gate Pass PDF using TCPDF (2 Copies on 1 Single A4 Page)
// -------------------------------------------------------------
function createGatePassPdfDoc($krfconn, $projectid, $psono, $sono, $gatepassNo, $clientDetails, $vehicleDetails, $items, $loginname, $dispatchDate = '') {
    $tcpdfPath = __DIR__ . '/../../pdfroutines/tcpdf/tcpdf.php';
    if (!file_exists($tcpdfPath)) {
        throw new Exception("TCPDF library not found.");
    }

    // Ensure TCPDF cache directory is set and guaranteed writable BEFORE TCPDF is initialized
    if (!defined('K_PATH_CACHE')) {
        $customCache = realpath(__DIR__ . '/../../pdfroutines/tcpdf') ? realpath(__DIR__ . '/../../pdfroutines/tcpdf') . '/cache/' : __DIR__ . '/../../pdfroutines/tcpdf/cache/';
        if (!is_dir($customCache)) {
            @mkdir($customCache, 0777, true);
        }
        $cacheWritable = false;
        if (is_dir($customCache)) {
            @chmod($customCache, 0777);
            $testFile = $customCache . 'test_' . uniqid() . '.tmp';
            if (@file_put_contents($testFile, '1') !== false) {
                $cacheWritable = true;
                @unlink($testFile);
            }
        }
        if (!$cacheWritable) {
            $sysTemp = sys_get_temp_dir();
            if (substr($sysTemp, -1) !== '/' && substr($sysTemp, -1) !== '\\') {
                $sysTemp .= '/';
            }
            define('K_PATH_CACHE', $sysTemp);
        } else {
            define('K_PATH_CACHE', $customCache);
        }
    }

    require_once($tcpdfPath);

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Karjee Farms');
    $pdf->SetAuthor('Karjee Farm');
    $pdf->SetTitle('Gate Pass - ' . $gatepassNo);
    $pdf->SetSubject('Outward Dispatch Gate Pass');
    
    // Remove default header/footer & disable auto page break so both copies fit on 1 page
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->SetMargins(10, 5, 10);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    // Find company logo (prefer JPG if available for faster native embedding without temp mask files)
    $logoPath = '';
    $logoCandidates = [
        __DIR__ . '/../../img/logo/logo.jpg',
        __DIR__ . '/../../krf-orderandsales/logo/logo.jpg',
        __DIR__ . '/../../img/logo/logo.png',
        __DIR__ . '/../../krf-orderandsales/logo/logo.png',
        __DIR__ . '/../../logo/logo.png',
        __DIR__ . '/../../dist/img/logo.png'
    ];
    foreach ($logoCandidates as $cand) {
        if (file_exists($cand)) {
            $logoPath = $cand;
            break;
        }
    }

    $nowDate = date('d-M-Y');
    $formattedDispatchDate = $dispatchDate ? (date('d-M-Y', strtotime($dispatchDate)) ?: $dispatchDate) : $nowDate;

    // Helper to render one copy of Gate Pass
    $renderSingleCopy = function($startY, $showQr = true) use ($pdf, $logoPath, $gatepassNo, $nowDate, $psono, $sono, $formattedDispatchDate, $loginname, $clientDetails, $vehicleDetails, $items) {
        $pdf->SetY($startY);

        // 1. Top Bar: GSTIN (Left) & Company Header (Right)
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(10, $startY);
        $pdf->Cell(60, 4.5, 'GSTIN/UIN: 22BWJPG1381G1ZU', 0, 0, 'L');

        // Company Details on right
        $pdf->SetXY(70, $startY);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->Cell(130, 5, 'KARJEE FARM', 0, 1, 'R');

        $pdf->SetXY(70, $startY + 5.2);
        $pdf->SetFont('helvetica', '', 7.8);
        $pdf->Cell(130, 3.6, 'Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India', 0, 1, 'R');

        $pdf->SetXY(70, $startY + 8.8);
        $pdf->Cell(130, 3.6, 'Mobile No: 6261832409, 7879615628', 0, 1, 'R');

        // Logo on left below GSTIN
        if ($logoPath !== '' && file_exists($logoPath)) {
            try {
                @$pdf->Image($logoPath, 10, $startY + 4.2, 19, 0, '', '', '', false, 300);
            } catch (Throwable $imgEx) {
                error_log("Gate pass logo render notice: " . $imgEx->getMessage());
            }
        }

        // 2. Document Title (Centered)
        $pdf->SetXY(10, $startY + 11.5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 5, 'GATE PASS', 0, 1, 'C');

        // 2b. Centered QR Code below GATE PASS heading with clear spacing
        $vNo = $vehicleDetails['vehicleno'] ?: '-';
        $tPlants = 0;
        $tTrays = 0;
        foreach ($items as $it) {
            $isPart = (strtolower($it['status'] ?? ($it['consumed_status'] ?? '')) === 'partialdispatch');
            if ($isPart && (int)($it['dispatchedqty'] ?? 0) > 0) {
                $p = (int)$it['dispatchedqty'];
            } else {
                $p = (int)($it['allocate_healthyplant'] ?: ($it['consumed_qty'] ?: ($it['quantity'] ?: 0)));
            }
            $tPlants += $p;
            if (!empty($it['trays']) && (int)$it['trays'] > 0) {
                $tTrays += (int)$it['trays'];
            } elseif (!empty($it['trays_count']) && (int)$it['trays_count'] > 0) {
                $tTrays += (int)$it['trays_count'];
            } else {
                $tTrays += (int)ceil($p / 126);
            }
        }

        $qrData = $gatepassNo;

        $qrSize = 17;
        $qrY = $startY + 20.5; // Spaced down nicely from 'GATE PASS' title
        if ($showQr) {
            $qrStyle = array(
                'border' => false,
                'vpadding' => 0,
                'hpadding' => 0,
                'fgcolor' => array(0, 0, 0),
                'bgcolor' => false,
                'module_width' => 1,
                'module_height' => 1
            );

            $qrX = (210 - $qrSize) / 2; // Centered horizontally
            if (method_exists($pdf, 'write2DBarcode')) {
                try {
                    $pdf->write2DBarcode($qrData, 'QRCODE,M', $qrX, $qrY, $qrSize, $qrSize, $qrStyle, 'N');
                } catch (Exception $e) {}
            }
        }

        // 3. Metadata Section (2 Columns)
        // In Top Copy with QR: Customer on Left (W=80), QR in Center, Order Details on Right (W=80)
        // In Bottom Copy without QR: standard 2-column W=92 layout
        $metaY = $startY + 20.5;
        $colW = $showQr ? 80 : 92;
        $rightColX = $showQr ? 120 : 108;

        // Left Column: Customer details
        $cName = $clientDetails['clientname'] ?: 'Customer';
        $cPhone = $clientDetails['mobileno'] ?: '-';
        $cLoc = trim(($clientDetails['districtname'] ?? '') . ($clientDetails['statename'] ? ', ' . $clientDetails['statename'] : ''), ', ');
        $cAddr = $clientDetails['delivery_address'] ?: ($clientDetails['address'] ?: ($cLoc ?: '-'));
        $cEmail = $clientDetails['email'] ?? '';

        $pdf->SetXY(10, $metaY);
        $pdf->SetFont('helvetica', '', 7.5);
        $leftText = "Customer: " . $cName . "\n" .
                    ($cEmail ? "Email: " . $cEmail . "\n" : "") .
                    "Mobile: " . $cPhone . "\n" .
                    "Delivery Address: " . $cAddr;
        $pdf->MultiCell($colW, 3.3, $leftText, 0, 'L', false, 1);
        $leftEndY = $pdf->GetY();

        // Right Column: Order details
        $pdf->SetXY($rightColX, $metaY);
        $rightText = "Gate Pass No: " . $gatepassNo . "\n" .
                     "Gate Pass Date: " . $nowDate . "\n" .
                     "Order No: " . ($psono ?: '-') . "\n" .
                     "Dispatch Date: " . $formattedDispatchDate;
        $pdf->MultiCell($colW, 3.3, $rightText, 0, 'R', false, 1);
        $rightEndY = $pdf->GetY();

        // Table Y is positioned immediately below metadata and QR code (shifting table up)
        $qrBottom = $showQr ? ($qrY + $qrSize) : 0;
        $tableY = max($leftEndY, $rightEndY, $qrBottom) + 2.0;

        // 4. Products Table (5 Columns with Cavity in Trays Dispatched)
        $pdf->SetXY(10, $tableY);
        $w = [8, 80, 30, 42, 30];
        $headers = ['#', 'Product & Variety', 'Stock Order No.', 'Trays Dispatched', 'Quantity (Plants)'];

        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);
        $pdf->SetFont('helvetica', 'B', 7.2);

        foreach ($headers as $i => $h) {
            $align = ($i === 1) ? 'L' : (($i === 4) ? 'R' : 'C');
            $pdf->Cell($w[$i], 4.8, $h, 1, 0, $align, true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica', '', 7.2);
        $totalPlants = 0;
        $totalTrays = 0;
        $rowNum = 1;

        foreach ($items as $it) {
            $pName = $it['product_name'] ?: ($it['productname'] ?: 'Item ' . $rowNum);
            $stkNo = $it['stockorderno'] ?: ($it['stockorder_no'] ?: '-');
            $cavity = !empty($it['tray_cavity']) ? $it['tray_cavity'] : (!empty($it['qtypertray']) ? ($it['qtypertray'] . ' Cavity') : '126 Cavity');
            $isPart = (strtolower($it['status'] ?? ($it['consumed_status'] ?? '')) === 'partialdispatch');
            if ($isPart && (int)($it['dispatchedqty'] ?? 0) > 0) {
                $plants = (int)$it['dispatchedqty'];
            } else {
                $plants = (int)($it['allocate_healthyplant'] ?: ($it['consumed_qty'] ?: ($it['quantity'] ?: 0)));
            }
            $cavityNum = (int)filter_var($cavity, FILTER_SANITIZE_NUMBER_INT) ?: 126;
            if (!empty($it['trays']) && (int)$it['trays'] > 0) {
                $trays = (int)$it['trays'];
            } elseif (!empty($it['trays_count']) && (int)$it['trays_count'] > 0) {
                $trays = (int)$it['trays_count'];
            } else {
                $trays = (int)ceil($plants / $cavityNum);
            }

            $totalPlants += $plants;
            $totalTrays += $trays;

            $traysDisplay = number_format($trays) . ' Trays (' . $cavity . ')';

            $pdf->Cell($w[0], 4.6, $rowNum, 1, 0, 'C');
            $pdf->Cell($w[1], 4.6, ' ' . substr($pName, 0, 52), 1, 0, 'L');
            $pdf->Cell($w[2], 4.6, $stkNo, 1, 0, 'C');
            $pdf->Cell($w[3], 4.6, $traysDisplay, 1, 0, 'C');
            $pdf->Cell($w[4], 4.6, number_format($plants) . ' Plants ', 1, 1, 'R');
            $rowNum++;
        }

        // Totals Row
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->Cell($w[0] + $w[1] + $w[2], 4.8, 'TOTAL DISPATCHED: ', 1, 0, 'R', true);
        $pdf->Cell($w[3], 4.8, number_format($totalTrays) . ' Trays', 1, 0, 'C', true);
        $pdf->Cell($w[4], 4.8, number_format($totalPlants) . ' Plants ', 1, 1, 'R', true);

        // 5. Freight / Transport Details Box
        $pdf->Ln(1.2);
        $pdf->SetFillColor(248, 248, 248);
        $pdf->SetFont('helvetica', 'B', 7.2);

        $tName = $vehicleDetails['transporter_name'] ?: 'Karjee Transport / Self';
        $dName = trim($vehicleDetails['drivername'] ?? '');
        $dPhone = trim($vehicleDetails['drivermobile'] ?? '');

        $driverDisplay = ($dName !== '' && $dName !== '-') ? $dName : 'Self / Transporter Driver';
        if ($dPhone !== '' && $dPhone !== '-') {
            $driverDisplay .= " (Mob: {$dPhone})";
        }

        $destDisplay = ($cAddr !== '' && $cAddr !== '-') ? $cAddr : ($cLoc ?: 'Destination');

        $headLine = " Transport Details | Vehicle No: {$vNo} | Transporter: {$tName}";
        $pdf->Cell(190, 3.8, $headLine, 1, 1, 'L', true);

        $pdf->SetFont('helvetica', '', 7);
        $detailLine = " Driver: {$driverDisplay} | Delivery Destination: {$destDisplay}";
        $pdf->Cell(190, 3.8, $detailLine, 1, 1, 'L');

        // 6. Signatures Row
        $pdf->Ln(1.8);
        $sigW = 63.3;

        $pdf->SetFont('helvetica', '', 6.8);
        $pdf->Cell($sigW, 3.5, "Driver / Receiver's Signature", 0, 0, 'C');
        $pdf->Cell($sigW, 3.5, "Security / Gate In-Charge", 0, 0, 'C');
        $pdf->Cell($sigW, 3.5, "Authorized Signatory (Karjee Farm)", 0, 1, 'C');

        return [
            'total_plants' => $totalPlants,
            'total_trays'  => $totalTrays,
            'qr_data'      => $qrData
        ];
    };

    // Render Copy 1: Top Half with larger QR code (starts at Y=5)
    $res1 = $renderSingleCopy(5, true);

    // Middle Cut Line (Scissors Divider at Y=141)
    $cutY = 141;
    $pdf->SetDrawColor(160, 160, 160);
    $pdf->SetLineStyle(['width' => 0.2, 'dash' => '3,2', 'color' => [160, 160, 160]]);
    $pdf->Line(10, $cutY, 200, $cutY);
    $pdf->SetLineStyle(['dash' => 0]); // Reset to solid line

    $pdf->SetFont('helvetica', 'B', 6.5);
    $pdf->SetTextColor(120, 120, 120);
    $pdf->SetXY(70, $cutY - 2);
    $pdf->Cell(70, 4, '✂ - - - - - - - - - - - - - CUT HERE - - - - - - - - - - - - - ✂', 0, 0, 'C');
    $pdf->SetTextColor(0, 0, 0);

    // Render Copy 2: Bottom Half shifted up cleanly (starts at Y=145)
    $res2 = $renderSingleCopy(145, false);

    // Save PDF to attachment folder
    $outDir = __DIR__ . '/../../attachementfile/gatepass';
    if (!is_dir($outDir)) {
        @mkdir($outDir, 0777, true);
    }
    @chmod($outDir, 0777);
    $safeFileName = preg_replace('/[^A-Za-z0-9_-]/', '_', $gatepassNo) . '.pdf';
    $saveFullPath = $outDir . '/' . $safeFileName;
    $relPath = 'attachementfile/gatepass/' . $safeFileName;

    $pdf->Output($saveFullPath, 'F');

    return [
        'full_path'    => $saveFullPath,
        'rel_path'     => $relPath,
        'filename'     => $safeFileName,
        'total_plants' => $res1['total_plants'],
        'total_trays'  => $res1['total_trays'],
        'qr_data'      => $res1['qr_data'] ?? ''
    ];
}

// -------------------------------------------------------------
// 1. ACTION: GET ORDERS LIST FOR GATEPASS DASHBOARD
// -------------------------------------------------------------
if ($action === 'get_orders') {
    $statusFilter = trim($_REQUEST['status'] ?? 'all');
    $search       = trim($_REQUEST['search'] ?? '');
    $fromDate     = trim($_REQUEST['from_date'] ?? '');
    $toDate       = trim($_REQUEST['to_date'] ?? '');

    // Common SQL Conditions:
    // 1. Stock Ready: loaded or ready in sales_invoice OR in rsa OR poc legacy ready
    $stockReadySql = "(
        COALESCE(si.is_readytodispatch, 0) = 1
        OR COALESCE(si.is_dispatched, 0) = 1 
        OR LOWER(COALESCE(si.invoice_status, '')) IN ('readytodispatch', 'dispatched')
        OR LOWER(poc.status) IN ('readytodispatch', 'fulldispatch', 'partialdispatch') 
        OR EXISTS (SELECT 1 FROM krf_readystock_allocation rsa WHERE (rsa.stockorder_no = poc.stockorderno OR rsa.stockorder_no = poc.allocated_stockorderno OR rsa.stockorder_no = poc.sono) AND rsa.status = 'readytodispatch')
    )";

    // 2. Vehicle Allocated: trip planned in vt OR vehicle assigned in vth, vt, dl, or si (Keep existing logic)
    $vehicleAllocSql = "(
        COALESCE(vt.status, '') = 'Planned' 
        OR COALESCE(NULLIF(TRIM(vth.vehicle_no), ''), NULLIF(TRIM(vt.vehicle_no), ''), NULLIF(TRIM(dl.vehicleno), ''), NULLIF(TRIM(si.si_vehicleno), ''), '') != ''
    )";

    // 3. Gate Pass Tracking flags
    $noGpSql  = "(gp.gatepass_no IS NULL OR gp.gatepass_no = '')";
    $hasGpSql = "(gp.gatepass_no IS NOT NULL AND gp.gatepass_no != '')";

    // 4. Dispatched: Strictly checked from sales_invoice status 'dispatched' OR gatepass tracking (NOT poc.status)
    $isInvoiceDispatchedSql = "(COALESCE(si.is_dispatched, 0) = 1)";

    // 1. Calculate Global KPIs (Independent of row-level status filter)
    // Ready To Dispatch = (Stock is Ready OR Invoice Dispatched) AND Vehicle Allocation completed AND Gate Pass not yet issued
    $kpiSql = "
        SELECT 
            COUNT(DISTINCT CASE WHEN (({$stockReadySql} AND {$vehicleAllocSql}) OR {$isInvoiceDispatchedSql} OR {$hasGpSql}) THEN poc.psono END) AS total_orders,
            COUNT(DISTINCT CASE WHEN ({$stockReadySql} AND {$vehicleAllocSql} AND {$noGpSql}) THEN poc.psono END) AS ready_to_dispatch,
            COUNT(DISTINCT CASE WHEN ({$isInvoiceDispatchedSql} OR {$hasGpSql}) THEN poc.psono END) AS dispatched,
            COUNT(DISTINCT CASE WHEN (NOT ({$stockReadySql} AND {$vehicleAllocSql}) AND NOT {$isInvoiceDispatchedSql} AND {$noGpSql}) THEN poc.psono END) AS pending_allocated,
            COALESCE(SUM(CASE WHEN ({$stockReadySql} AND {$vehicleAllocSql} AND {$noGpSql}) THEN (COALESCE(poc.allocate_healthyplant, 0) + COALESCE(poc.extraqtyforshifting, 0) + CASE WHEN COALESCE(poc.allocate_healthyplant, 0) = 0 THEN COALESCE(poc.consumed_qty, 0) ELSE 0 END) ELSE 0 END), 0) AS ready_plants
        FROM krf_productionorder_consumed poc
        LEFT JOIN (
            SELECT psono, gatepass_no FROM krf_gatepass_tracking GROUP BY psono
        ) gp ON poc.psono = gp.psono
        LEFT JOIN (
            SELECT 
                si1.psono,
                si1.sono,
                GROUP_CONCAT(DISTINCT si1.invoiceno SEPARATOR ', ') AS invoicenos,
                MAX(si1.invoicedate) AS latest_invoicedate,
                MAX(CASE WHEN LOWER(TRIM(si1.status)) = 'dispatched' THEN 1 ELSE 0 END) AS is_dispatched,
                MAX(CASE WHEN LOWER(TRIM(si1.status)) = 'readytodispatch' THEN 1 ELSE 0 END) AS is_readytodispatch,
                MAX(CASE 
                    WHEN LOWER(TRIM(si1.status)) = 'readytodispatch' THEN 'readytodispatch'
                    WHEN LOWER(TRIM(si1.status)) = 'dispatched' THEN 'dispatched'
                    WHEN LOWER(TRIM(si1.status)) = 'readytoload' THEN 'readytoload'
                    ELSE si1.status 
                END) AS invoice_status,
                MAX(NULLIF(TRIM(si1.vehicleno), '')) AS si_vehicleno,
                MAX(NULLIF(TRIM(si1.drivername), '')) AS si_drivername,
                MAX(NULLIF(TRIM(si1.drivermobileno), '')) AS si_drivermobile
            FROM sales_invoice si1
            WHERE LOWER(TRIM(COALESCE(si1.status, ''))) != 'cancelled'
            GROUP BY COALESCE(NULLIF(TRIM(si1.psono), ''), si1.sono)
        ) si ON (si.psono = poc.psono OR (COALESCE(poc.sono, '') != '' AND si.sono = poc.sono))
        LEFT JOIN (
            SELECT vth1.id, vth1.trip_id, vth1.psono, vth1.stockorder_no, vth1.vehicle_no
            FROM krf_vehicle_travel_history vth1
            INNER JOIN (
                SELECT MAX(id) as max_id 
                FROM krf_vehicle_travel_history 
                GROUP BY COALESCE(NULLIF(TRIM(psono), ''), stockorder_no)
            ) vth_max ON vth1.id = vth_max.max_id
        ) vth ON (vth.psono = poc.psono OR (COALESCE(poc.sono, '') != '' AND vth.stockorder_no = poc.sono))
        LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        LEFT JOIN (
            SELECT dl1.id, dl1.psono, dl1.sono, dl1.vehicleno
            FROM krf_deliveryaddress_location dl1
            INNER JOIN (
                SELECT MAX(id) as max_id 
                FROM krf_deliveryaddress_location 
                GROUP BY COALESCE(NULLIF(TRIM(psono), ''), sono)
            ) dl_max ON dl1.id = dl_max.max_id
        ) dl ON (dl.psono = poc.psono OR (COALESCE(poc.sono, '') != '' AND dl.sono = poc.sono))
        WHERE (poc.projectid = {$projectid} OR poc.projectid IS NULL OR poc.projectid = 0)
    ";
    $kRes = $krfconn->query($kpiSql);
    $kpiRow = $kRes ? $kRes->fetch_assoc() : [];

    $todaySql = "
        SELECT COUNT(DISTINCT psono) AS cnt 
        FROM krf_gatepass_tracking 
        WHERE DATE(created_at) = CURDATE() 
          AND (projectid = {$projectid} OR projectid IS NULL OR projectid = 0)
    ";
    $tRes = $krfconn->query($todaySql);
    $todayRow = $tRes ? $tRes->fetch_assoc() : ['cnt' => 0];

    $kpi = [
        'total_orders'      => (int)($kpiRow['total_orders'] ?? 0),
        'ready_to_dispatch' => (int)($kpiRow['ready_to_dispatch'] ?? 0),
        'dispatched'        => (int)($kpiRow['dispatched'] ?? 0),
        'pending_allocated' => (int)($kpiRow['pending_allocated'] ?? 0),
        'ready_plants'      => (int)($kpiRow['ready_plants'] ?? 0),
        'dispatched_today'  => (int)($todayRow['cnt'] ?? 0)
    ];

    // 2. Build Where Clauses for Tab & Search Filtering
    $whereClauses = ["1=1"];

    if ($projectid > 0) {
        $whereClauses[] = "(poc.projectid = {$projectid} OR poc.projectid IS NULL OR poc.projectid = 0)";
    }

    if ($statusFilter === 'readytodispatch') {
        // Show orders where stock is ready AND vehicle allocation has been completed (and no gate pass yet)
        $whereClauses[] = "({$stockReadySql} AND {$vehicleAllocSql} AND {$noGpSql})";
    } elseif ($statusFilter === 'dispatched') {
        // Dispatched strictly from sales_invoice status 'dispatched' OR gatepass tracking (NOT poc.status)
        $whereClauses[] = "({$isInvoiceDispatchedSql} OR {$hasGpSql})";
    } elseif ($statusFilter === 'pending') {
        // Orders not yet ready for gatepass (either stock growing/pending OR stock ready but awaiting vehicle allocation)
        $whereClauses[] = "(NOT ({$stockReadySql} AND {$vehicleAllocSql}) AND NOT {$isInvoiceDispatchedSql} AND {$noGpSql})";
    } elseif ($statusFilter === 'all' || $statusFilter === '') {
        // All active dispatch orders (ready with vehicle, loaded awaiting vehicle, or dispatched)
        $whereClauses[] = "({$stockReadySql} OR {$vehicleAllocSql} OR {$isInvoiceDispatchedSql} OR {$hasGpSql})";
    } else {
        $safeStatus = mysqli_real_escape_string($krfconn, $statusFilter);
        $whereClauses[] = "(poc.status = '{$safeStatus}' OR LOWER(COALESCE(si.invoice_status, '')) = LOWER('{$safeStatus}'))";
    }

    if (!empty($search)) {
        $safeSearch = mysqli_real_escape_string($krfconn, $search);
        $whereClauses[] = "(
            poc.psono LIKE '%{$safeSearch}%' 
            OR poc.sono LIKE '%{$safeSearch}%' 
            OR poc.product_name LIKE '%{$safeSearch}%'
            OR pso.clientname LIKE '%{$safeSearch}%'
            OR cm.client_name LIKE '%{$safeSearch}%'
            OR pso.mobileno LIKE '%{$safeSearch}%'
            OR cm.phones LIKE '%{$safeSearch}%'
            OR gp.vehicleno LIKE '%{$safeSearch}%'
            OR gp.gatepass_no LIKE '%{$safeSearch}%'
            OR vth.vehicle_no LIKE '%{$safeSearch}%'
            OR vt.vehicle_no LIKE '%{$safeSearch}%'
            OR si.invoicenos LIKE '%{$safeSearch}%'
            OR si.si_vehicleno LIKE '%{$safeSearch}%'
        )";
    }

    if (!empty($fromDate) && !empty($toDate)) {
        $safeFrom = mysqli_real_escape_string($krfconn, $fromDate);
        $safeTo   = mysqli_real_escape_string($krfconn, $toDate);
        $whereClauses[] = "(DATE(pso.created_at) BETWEEN '{$safeFrom}' AND '{$safeTo}' OR DATE(poc.created_at) BETWEEN '{$safeFrom}' AND '{$safeTo}' OR DATE(gp.created_at) BETWEEN '{$safeFrom}' AND '{$safeTo}' OR DATE(si.latest_invoicedate) BETWEEN '{$safeFrom}' AND '{$safeTo}')";
    }

    $whereSql = "WHERE " . implode(' AND ', $whereClauses);

    $sql = "
        SELECT 
            poc.id AS consumed_id,
            poc.psono,
            poc.sono,
            COALESCE(NULLIF(TRIM(poc.allocated_stockorderno), ''), poc.stockorderno) AS stockorderno,
            poc.product_id,
            poc.product_name,
            poc.consumed_qty,
            poc.allocate_healthyplant,
            poc.extraqtyforshifting,
            poc.dispatchedqty,
            poc.status AS consumed_status,
            poc.confirmstatus,
            poc.created_at AS consumed_created_at,
            pso.clientid,
            pso.expecteddelivery,
            pso.created_at AS pso_created_at,
            COALESCE(NULLIF(TRIM(pso.clientname), ''), NULLIF(TRIM(cm.client_name), ''), 'Unknown Client') AS client_name,
            COALESCE(NULLIF(TRIM(pso.mobileno), ''), NULLIF(TRIM(cm.phones), ''), '') AS client_phone,
            COALESCE(NULLIF(TRIM(pso.delivery_address), ''), NULLIF(TRIM(pso.address), ''), NULLIF(TRIM(cm.invoicing_address), ''), '') AS delivery_address,
            pso.districtname,
            pso.statename,
            gp.gatepass_no,
            gp.filepath AS gatepass_filepath,
            gp.vehicleno AS gp_vehicleno,
            gp.drivername AS gp_drivername,
            gp.drivermobile AS gp_drivermobile,
            gp.created_at AS gp_created_at,
            vt.trip_no,
            vt.status AS trip_status,
            COALESCE(NULLIF(TRIM(vth.vehicle_no), ''), NULLIF(TRIM(vt.vehicle_no), ''), NULLIF(TRIM(dl.vehicleno), ''), NULLIF(TRIM(si.si_vehicleno), ''), gp.vehicleno, '') AS allocated_vehicleno,
            COALESCE(NULLIF(TRIM(vm.vehiclename), ''), '') AS allocated_vehiclename,
            COALESCE(NULLIF(TRIM(vm.vehicleownername), ''), '') AS allocated_transporter,
            COALESCE(NULLIF(TRIM(vth.driver_name), ''), NULLIF(TRIM(vt.driver_name), ''), NULLIF(TRIM(dl.drivername), ''), NULLIF(TRIM(si.si_drivername), ''), gp.drivername, '') AS allocated_drivername,
            COALESCE(NULLIF(TRIM(vth.driver_mobileno), ''), NULLIF(TRIM(vt.driver_mobile_no), ''), NULLIF(TRIM(dl.drivercontactno), ''), NULLIF(TRIM(si.si_drivermobile), ''), gp.drivermobile, '') AS allocated_drivermobile,
            COALESCE(NULLIF(TRIM(vth.send_remark), ''), NULLIF(TRIM(vt.remarks), ''), NULLIF(TRIM(dl.remark), ''), '') AS allocated_remarks,
            si.invoicenos,
            si.latest_invoicedate,
            si.invoice_status,
            si.is_dispatched AS invoice_is_dispatched,
            si.is_readytodispatch AS invoice_is_readytodispatch
        FROM krf_productionorder_consumed poc
        LEFT JOIN krf_provisional_sales_order pso ON poc.psono = pso.psono
        LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
        LEFT JOIN (
            SELECT 
                psono, gatepass_no, filepath, vehicleno, drivername, drivermobile, created_at
            FROM krf_gatepass_tracking
            GROUP BY psono
        ) gp ON poc.psono = gp.psono
        LEFT JOIN (
            SELECT 
                si1.psono,
                si1.sono,
                GROUP_CONCAT(DISTINCT si1.invoiceno SEPARATOR ', ') AS invoicenos,
                MAX(si1.invoicedate) AS latest_invoicedate,
                MAX(CASE WHEN LOWER(TRIM(si1.status)) = 'dispatched' THEN 1 ELSE 0 END) AS is_dispatched,
                MAX(CASE WHEN LOWER(TRIM(si1.status)) = 'readytodispatch' THEN 1 ELSE 0 END) AS is_readytodispatch,
                MAX(CASE 
                    WHEN LOWER(TRIM(si1.status)) = 'readytodispatch' THEN 'readytodispatch'
                    WHEN LOWER(TRIM(si1.status)) = 'dispatched' THEN 'dispatched'
                    WHEN LOWER(TRIM(si1.status)) = 'readytoload' THEN 'readytoload'
                    ELSE si1.status 
                END) AS invoice_status,
                MAX(NULLIF(TRIM(si1.vehicleno), '')) AS si_vehicleno,
                MAX(NULLIF(TRIM(si1.drivername), '')) AS si_drivername,
                MAX(NULLIF(TRIM(si1.drivermobileno), '')) AS si_drivermobile
            FROM sales_invoice si1
            WHERE LOWER(TRIM(COALESCE(si1.status, ''))) != 'cancelled'
            GROUP BY COALESCE(NULLIF(TRIM(si1.psono), ''), si1.sono)
        ) si ON (si.psono = poc.psono OR (COALESCE(poc.sono, '') != '' AND si.sono = poc.sono))
        LEFT JOIN (
            SELECT vth1.id, vth1.trip_id, vth1.psono, vth1.stockorder_no, vth1.vehicle_id, vth1.vehicle_no, vth1.driver_name, vth1.driver_mobileno, vth1.send_remark
            FROM krf_vehicle_travel_history vth1
            INNER JOIN (
                SELECT MAX(id) as max_id 
                FROM krf_vehicle_travel_history 
                GROUP BY COALESCE(NULLIF(TRIM(psono), ''), stockorder_no)
            ) vth_max ON vth1.id = vth_max.max_id
        ) vth ON (vth.psono = poc.psono OR (COALESCE(poc.sono, '') != '' AND vth.stockorder_no = poc.sono))
        LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = COALESCE(vth.vehicle_id, vt.vehicle_id) OR UPPER(TRIM(vm.vehicleno)) = UPPER(TRIM(COALESCE(vth.vehicle_no, vt.vehicle_no))))
        LEFT JOIN (
            SELECT dl1.id, dl1.psono, dl1.sono, dl1.vehicleno, dl1.drivername, dl1.drivercontactno, dl1.remark
            FROM krf_deliveryaddress_location dl1
            INNER JOIN (
                SELECT MAX(id) as max_id 
                FROM krf_deliveryaddress_location 
                GROUP BY COALESCE(NULLIF(TRIM(psono), ''), sono)
            ) dl_max ON dl1.id = dl_max.max_id
        ) dl ON (dl.psono = poc.psono OR (COALESCE(poc.sono, '') != '' AND dl.sono = poc.sono))
        $whereSql
        ORDER BY poc.id DESC
    ";

    $res = $krfconn->query($sql);
    if (!$res) {
        outJson(['status' => 'error', 'message' => 'Query error: ' . $krfconn->error]);
    }

    $grouped = [];
    $seenConsumed = [];

    while ($r = $res->fetch_assoc()) {
        $pso = trim($r['psono'] ?: ('UN-' . $r['consumed_id']));
        $cid = (int)$r['consumed_id'];

        if (!isset($grouped[$pso])) {
            $grouped[$pso] = [
                'psono'                 => $r['psono'],
                'sono'                  => $r['sono'],
                'invoiceno'             => $r['invoicenos'] ?: '',
                'invoice_date'          => $r['latest_invoicedate'] ?: '',
                'invoice_status'        => $r['invoice_status'] ?: '',
                'client_name'           => $r['client_name'],
                'client_phone'          => $r['client_phone'],
                'delivery_address'      => $r['delivery_address'],
                'districtname'          => $r['districtname'],
                'statename'             => $r['statename'],
                'expecteddelivery'      => $r['expecteddelivery'],
                'created_at'            => $r['pso_created_at'] ?: $r['consumed_created_at'],
                'status'                => (!empty($r['invoice_status']) ? $r['invoice_status'] : ($r['consumed_status'] ?: 'pending')),
                'gatepass_no'           => $r['gatepass_no'] ?: '',
                'gatepass_filepath'     => $r['gatepass_filepath'] ?: '',
                'vehicleno'             => $r['gp_vehicleno'] ?: ($r['allocated_vehicleno'] ?: ''),
                'drivername'            => $r['gp_drivername'] ?: ($r['allocated_drivername'] ?: ''),
                'drivermobile'          => $r['gp_drivermobile'] ?: ($r['allocated_drivermobile'] ?: ''),
                'allocated_vehicleno'   => $r['allocated_vehicleno'] ?: ($r['gp_vehicleno'] ?: ''),
                'allocated_vehiclename' => $r['allocated_vehiclename'] ?: '',
                'allocated_transporter' => $r['allocated_transporter'] ?: '',
                'allocated_drivername'  => $r['allocated_drivername'] ?: ($r['gp_drivername'] ?: ''),
                'allocated_drivermobile'=> $r['allocated_drivermobile'] ?: ($r['gp_drivermobile'] ?: ''),
                'allocated_remarks'     => $r['allocated_remarks'] ?: '',
                'trip_no'               => $r['trip_no'] ?: '',
                'trip_status'           => $r['trip_status'] ?: '',
                'gp_created_at'         => $r['gp_created_at'] ?: '',
                'has_vehicle_allocated' => (!empty(trim($r['allocated_vehicleno'] ?? '')) || !empty(trim($r['gp_vehicleno'] ?? '')) || strtolower(trim($r['trip_status'] ?? '')) === 'planned'),
                'total_plants'          => 0,
                'total_trays'           => 0,
                'items'                 => []
            ];
            $seenConsumed[$pso] = [];
        } else {
            // Update vehicle details if previous was empty
            if (empty($grouped[$pso]['vehicleno']) && !empty($r['allocated_vehicleno'])) {
                $grouped[$pso]['vehicleno'] = $r['allocated_vehicleno'];
                $grouped[$pso]['allocated_vehicleno'] = $r['allocated_vehicleno'];
            }
            if (empty($grouped[$pso]['drivername']) && !empty($r['allocated_drivername'])) {
                $grouped[$pso]['drivername'] = $r['allocated_drivername'];
                $grouped[$pso]['allocated_drivername'] = $r['allocated_drivername'];
            }
            if (!empty(trim($r['allocated_vehicleno'] ?? '')) || strtolower(trim($r['trip_status'] ?? '')) === 'planned') {
                $grouped[$pso]['has_vehicle_allocated'] = true;
            }
        }

        if (!isset($seenConsumed[$pso][$cid])) {
            $seenConsumed[$pso][$cid] = true;

            $isPart = (strtolower($r['consumed_status'] ?? '') === 'partialdispatch');
            if ($isPart && (int)($r['dispatchedqty'] ?? 0) > 0) {
                $plants = (int)$r['dispatchedqty'];
            } else {
                $basePlants = (int)($r['allocate_healthyplant'] ?: ($r['consumed_qty'] ?: 0));
                $extraPlants = (int)($r['extraqtyforshifting'] ?? 0);
                $plants = $basePlants + $extraPlants;
            }
            $trays = (int)ceil($plants / 126);

            $grouped[$pso]['total_plants'] += $plants;
            $grouped[$pso]['total_trays'] += $trays;

            $grouped[$pso]['items'][] = [
                'consumed_id'           => $r['consumed_id'],
                'product_id'            => $r['product_id'],
                'product_name'          => $r['product_name'],
                'stockorderno'          => $r['stockorderno'] ?: '',
                'consumed_qty'          => $r['consumed_qty'],
                'allocate_healthyplant' => $r['allocate_healthyplant'],
                'extraqtyforshifting'   => $r['extraqtyforshifting'],
                'dispatchedqty'         => $r['dispatchedqty'],
                'trays'                 => $trays,
                'plants'                => $plants,
                'status'                => $r['consumed_status']
            ];
        }
    }

    $ordersList = array_values($grouped);

    // Sort: Recent creation in first place (newest created_at DESC, highest psono DESC)
    usort($ordersList, static function ($a, $b) {
        $timeA = !empty($a['created_at']) ? (strtotime($a['created_at']) ?: 0) : 0;
        $timeB = !empty($b['created_at']) ? (strtotime($b['created_at']) ?: 0) : 0;
        if ($timeA !== $timeB) {
            return $timeB <=> $timeA;
        }
        return strnatcasecmp((string)($b['psono'] ?? ''), (string)($a['psono'] ?? ''));
    });

    outJson([
        'status' => 'success',
        'kpi'    => $kpi,
        'orders' => $ordersList
    ]);
}

// -------------------------------------------------------------
// 2. ACTION: GET ORDER DETAILS FOR MODAL
// -------------------------------------------------------------
if ($action === 'get_order_details') {
    $psono = trim($_REQUEST['psono'] ?? '');
    if (empty($psono)) {
        outJson(['status' => 'error', 'message' => 'Provisional Order No is required.']);
    }

    $safePso = mysqli_real_escape_string($krfconn, $psono);

    $sql = "
        SELECT 
            poc.id AS consumed_id,
            poc.psono,
            poc.sono,
            COALESCE(NULLIF(TRIM(poc.allocated_stockorderno), ''), poc.stockorderno) AS stockorderno,
            poc.product_id,
            poc.product_name,
            poc.consumed_qty,
            poc.allocate_healthyplant,
            poc.extraqtyforshifting,
            poc.dispatchedqty,
            poc.status AS consumed_status,
            pso.clientid,
            pso.address,
            pso.delivery_address,
            pso.districtname,
            pso.statename,
            pso.expecteddelivery,
            (SELECT ppd.qtypertray FROM krf_polyhouse_plant_details ppd WHERE ppd.stockorder_no = poc.stockorderno AND ppd.qtypertray IS NOT NULL AND ppd.qtypertray != '' LIMIT 1) AS qtypertray,
            COALESCE(NULLIF(TRIM(pso.clientname), ''), NULLIF(TRIM(cm.client_name), ''), 'Unknown Client') AS client_name,
            COALESCE(NULLIF(TRIM(pso.mobileno), ''), NULLIF(TRIM(cm.phones), ''), '') AS client_phone
        FROM krf_productionorder_consumed poc
        LEFT JOIN krf_provisional_sales_order pso ON poc.psono = pso.psono
        LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
        WHERE poc.psono = '{$safePso}'
    ";

    $res = $krfconn->query($sql);
    if (!$res || $res->num_rows === 0) {
        outJson(['status' => 'error', 'message' => 'Order not found or has no consumed production records.']);
    }

    $items = [];
    $orderHeader = null;
    $canGenerate = true;
    $blockingReasons = [];
    $totalPlants = 0;
    $totalTrays = 0;

    while ($r = $res->fetch_assoc()) {
        if (!$orderHeader) {
            $orderHeader = [
                'psono'            => $r['psono'],
                'sono'             => $r['sono'],
                'client_name'      => $r['client_name'],
                'client_phone'     => $r['client_phone'],
                'delivery_address' => $r['delivery_address'] ?: ($r['address'] ?: 'N/A'),
                'districtname'     => $r['districtname'],
                'statename'        => $r['statename'],
                'expecteddelivery' => $r['expecteddelivery'],
                'consumed_status'  => $r['consumed_status']
            ];
        }

        if (strtolower($r['consumed_status'] ?? '') === 'cancelled') {
            $canGenerate = false;
            $blockingReasons[] = "Item '{$r['product_name']}' is Cancelled.";
        }

        $isPart = (strtolower($r['consumed_status'] ?? '') === 'partialdispatch');
        if ($isPart && (int)($r['dispatchedqty'] ?? 0) > 0) {
            $plants = (int)$r['dispatchedqty'];
        } else {
            $basePlants = (int)($r['allocate_healthyplant'] ?: ($r['consumed_qty'] ?: 0));
            $extraPlants = (int)($r['extraqtyforshifting'] ?? 0);
            $plants = $basePlants + $extraPlants;
        }
        $cavityStr = !empty($r['qtypertray']) ? ($r['qtypertray'] . ' Cavity') : '126 Cavity';
        $cavityNum = (int)filter_var($cavityStr, FILTER_SANITIZE_NUMBER_INT) ?: 126;
        $trays = (int)ceil($plants / $cavityNum);

        $totalPlants += $plants;
        $totalTrays += $trays;

        $items[] = [
            'consumed_id'           => $r['consumed_id'],
            'product_id'            => $r['product_id'],
            'product_name'          => $r['product_name'],
            'stockorderno'          => $r['stockorderno'],
            'tray_cavity'           => $cavityStr,
            'qtypertray'            => $r['qtypertray'] ?? '',
            'allocate_healthyplant' => $r['allocate_healthyplant'],
            'extraqtyforshifting'   => $r['extraqtyforshifting'],
            'consumed_qty'          => $r['consumed_qty'],
            'dispatchedqty'         => $r['dispatchedqty'],
            'status'                => $r['consumed_status'],
            'plants'                => $plants,
            'trays'                 => $trays
        ];
    }

    // Check if Gate Pass was already generated
    $chkGp = $krfconn->query("SELECT * FROM krf_gatepass_tracking WHERE psono = '{$safePso}' ORDER BY id DESC LIMIT 1");
    $existingGp = ($chkGp && $chkGp->num_rows > 0) ? $chkGp->fetch_assoc() : null;

    // Fetch allocated vehicle and driver details from krf_vehicle_travel_history & krf_vehicletrip
    $allocatedVehicle = [
        'vehicleno'        => '',
        'vehiclename'      => '',
        'transporter_name' => '',
        'drivername'       => '',
        'drivermobile'     => '',
        'remarks'          => '',
        'trip_no'          => '',
        'trip_status'      => ''
    ];

    $vSql = "
        SELECT 
            vth.trip_id,
            COALESCE(NULLIF(TRIM(vth.vehicle_no), ''), NULLIF(TRIM(vt.vehicle_no), ''), NULLIF(TRIM(dl.vehicleno), ''), '') AS vehicleno,
            COALESCE(NULLIF(TRIM(vm.vehiclename), ''), '') AS vehiclename,
            COALESCE(NULLIF(TRIM(vm.vehicleownername), ''), '') AS vehicleownername,
            COALESCE(NULLIF(TRIM(vth.driver_name), ''), NULLIF(TRIM(vt.driver_name), ''), NULLIF(TRIM(dl.drivername), ''), '') AS drivername,
            COALESCE(NULLIF(TRIM(vth.driver_mobileno), ''), NULLIF(TRIM(vt.driver_mobile_no), ''), NULLIF(TRIM(dl.drivercontactno), ''), '') AS drivermobile,
            COALESCE(NULLIF(TRIM(vth.send_remark), ''), NULLIF(TRIM(vt.remarks), ''), NULLIF(TRIM(dl.remark), ''), '') AS remarks,
            COALESCE(vt.status, vth.status, '') AS trip_status
        FROM krf_vehicle_travel_history vth
        LEFT JOIN krf_vehicletrip vt ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        LEFT JOIN krf_vehicle_master vm ON (vm.vehicleid = COALESCE(vth.vehicle_id, vt.vehicle_id) OR UPPER(TRIM(vm.vehicleno)) = UPPER(TRIM(COALESCE(vth.vehicle_no, vt.vehicle_no))))
        LEFT JOIN krf_deliveryaddress_location dl ON (dl.psono = '{$safePso}' OR (COALESCE(vth.stockorder_no, '') != '' AND dl.sono = vth.stockorder_no))
        WHERE (vth.psono = '{$safePso}' OR (COALESCE(vth.stockorder_no, '') != '' AND vth.stockorder_no = (SELECT sono FROM krf_productionorder_consumed WHERE psono = '{$safePso}' LIMIT 1)))
        ORDER BY vth.id DESC
        LIMIT 1
    ";
    $vRes = $krfconn->query($vSql);
    if ($vRes && $vRow = $vRes->fetch_assoc()) {
        $allocatedVehicle = [
            'vehicleno'        => $vRow['vehicleno'] ?? '',
            'vehiclename'      => $vRow['vehiclename'] ?? '',
            'transporter_name' => $vRow['vehicleownername'] ?? '',
            'drivername'       => $vRow['drivername'] ?? '',
            'drivermobile'     => $vRow['drivermobile'] ?? '',
            'remarks'          => $vRow['remarks'] ?? '',
            'trip_no'          => $vRow['trip_id'] ?? '',
            'trip_status'      => $vRow['trip_status'] ?? ''
        ];
    } else {
        // Fallback: check krf_deliveryaddress_location
        $dlSql = "SELECT vehicleno, drivername, drivercontactno, remark FROM krf_deliveryaddress_location WHERE psono = '{$safePso}' ORDER BY id DESC LIMIT 1";
        $dlRes = $krfconn->query($dlSql);
        if ($dlRes && $dlRow = $dlRes->fetch_assoc()) {
            $allocatedVehicle['vehicleno']    = $dlRow['vehicleno'] ?? '';
            $allocatedVehicle['drivername']   = $dlRow['drivername'] ?? '';
            $allocatedVehicle['drivermobile'] = $dlRow['drivercontactno'] ?? '';
            $allocatedVehicle['remarks']      = $dlRow['remark'] ?? '';
        }
    }

    // Fallback: Check vehicle in sales_invoice table if not in travel history or delivery location
    if (empty(trim($allocatedVehicle['vehicleno'] ?? ''))) {
        $siVehChk = $krfconn->query("SELECT vehicleno, drivername, drivermobileno, remarks FROM sales_invoice WHERE (psono = '{$safePso}' OR sono = '{$safePso}') AND vehicleno IS NOT NULL AND vehicleno != '' ORDER BY id DESC LIMIT 1");
        if ($siVehChk && $siVehRow = $siVehChk->fetch_assoc()) {
            $allocatedVehicle['vehicleno']    = $siVehRow['vehicleno'] ?? '';
            $allocatedVehicle['drivername']   = $siVehRow['drivername'] ?? '';
            $allocatedVehicle['drivermobile'] = $siVehRow['drivermobileno'] ?? '';
            $allocatedVehicle['remarks']      = $siVehRow['remarks'] ?? '';
        }
    }

    // If gate pass not yet generated, verify order readiness AND vehicle allocation
    // Readiness: check sales_invoice status 'readytodispatch' or 'dispatched' first, or legacy readytodispatch/fulldispatch/partialdispatch in poc/rsa
    $hasReadyStatus = in_array(strtolower($orderHeader['consumed_status'] ?? ''), ['readytodispatch', 'fulldispatch', 'partialdispatch', 'dispatched']);
    if (!$hasReadyStatus) {
        $chkSi = $krfconn->query("
            SELECT invoiceno, invoicedate, status 
            FROM sales_invoice 
            WHERE (psono = '{$safePso}' OR sono = '{$safePso}') 
              AND LOWER(TRIM(status)) IN ('readytodispatch', 'dispatched') 
            LIMIT 1
        ");
        if ($chkSi && $chkSi->num_rows > 0) {
            $hasReadyStatus = true;
        }
    }
    if (!$hasReadyStatus) {
        $sonoSafe = mysqli_real_escape_string($krfconn, $orderHeader['sono'] ?? '');
        $chkRsa = $krfconn->query("SELECT 1 FROM krf_readystock_allocation WHERE (stockorder_no = '{$safePso}' OR stockorder_no = '{$sonoSafe}' OR allocate_stockorder_no = '{$sonoSafe}') AND status = 'readytodispatch' LIMIT 1");
        if ($chkRsa && $chkRsa->num_rows > 0) {
            $hasReadyStatus = true;
        }
    }
    $hasAllocatedVehicle = (!empty(trim($allocatedVehicle['vehicleno'] ?? '')) || strtolower(trim($allocatedVehicle['trip_status'] ?? '')) === 'planned');

    if (empty($existingGp)) {
        if (!$hasReadyStatus) {
            $canGenerate = false;
            $blockingReasons[] = "Order trays have not been loaded or confirmed in Ready To Load.";
        }
        if (!$hasAllocatedVehicle) {
            $canGenerate = false;
            $blockingReasons[] = "Vehicle allocation has not been completed. Please allocate a vehicle in Vehicle Allocation before generating a Gate Pass.";
        }
    }

    outJson([
        'status'           => 'success',
        'order'            => $orderHeader,
        'items'            => $items,
        'total_plants'     => $totalPlants,
        'total_trays'      => $totalTrays,
        'can_generate'     => $canGenerate,
        'blocking_reasons' => $blockingReasons,
        'existing_gatepass'=> $existingGp,
        'allocated_vehicle'=> $allocatedVehicle
    ]);
}

// -------------------------------------------------------------
// 3. ACTION: GENERATE GATE PASS
// -------------------------------------------------------------
if ($action === 'generate_gatepass') {
    $psono           = trim($_POST['psono'] ?? '');
    $vehicleno       = trim($_POST['vehicleno'] ?? '');
    $transporterName = trim($_POST['transporter_name'] ?? '');
    $drivername      = trim($_POST['drivername'] ?? '');
    $drivermobile    = trim($_POST['drivermobile'] ?? '');
    $remarks         = trim($_POST['remarks'] ?? '');

    if (empty($psono)) {
        outJson(['status' => 'error', 'message' => 'Provisional Order Number is required.']);
    }
    if (empty($vehicleno)) {
        outJson(['status' => 'error', 'message' => 'Vehicle Number is required to generate a Gate Pass.']);
    }

    $safePso = mysqli_real_escape_string($krfconn, $psono);

    // 1. STRICT VALIDATION: Status must be 'dispatched' in sales_invoice OR 'readytodispatch'
    $chkSql = "
        SELECT poc.id, poc.psono, poc.sono, 
               COALESCE(NULLIF(TRIM(poc.allocated_stockorderno), ''), poc.stockorderno) AS stockorderno, 
               poc.product_id, poc.product_name, 
               poc.allocate_healthyplant, poc.extraqtyforshifting, poc.consumed_qty, poc.dispatchedqty, poc.status,
               (SELECT ppd.qtypertray FROM krf_polyhouse_plant_details ppd WHERE (ppd.stockorder_no = COALESCE(NULLIF(TRIM(poc.allocated_stockorderno), ''), poc.stockorderno) OR ppd.stockorder_no = poc.stockorderno) AND ppd.qtypertray IS NOT NULL AND ppd.qtypertray != '' LIMIT 1) AS qtypertray
        FROM krf_productionorder_consumed poc
        WHERE poc.psono = '{$safePso}'
    ";
    $chkRes = $krfconn->query($chkSql);
    if (!$chkRes || $chkRes->num_rows === 0) {
        outJson(['status' => 'error', 'message' => "Order {$psono} not found in consumed production orders."]);
    }

    $consumedItems = [];
    $invalidStatuses = [];
    $totalAllocatedQty = 0;

    // Check if vehicle trip is planned in krf_vehicletrip
    $hasPlannedTrip = false;
    $tripChk = $krfconn->query("
        SELECT vt.status 
        FROM krf_vehicletrip vt
        JOIN krf_vehicle_travel_history vth ON (vt.trip_no = vth.trip_id OR vt.trip_id = vth.trip_id)
        WHERE vth.psono = '{$safePso}' AND vt.status = 'Planned'
        LIMIT 1
    ");
    if ($tripChk && $tripChk->num_rows > 0) {
        $hasPlannedTrip = true;
    }

    $hasReadyStock = false;
    $rsaChk = $krfconn->query("SELECT 1 FROM krf_readystock_allocation rsa WHERE (rsa.stockorder_no = '{$safePso}' OR rsa.stockorder_no IN (SELECT sono FROM krf_productionorder_consumed WHERE psono = '{$safePso}') OR rsa.allocate_stockorder_no IN (SELECT sono FROM krf_productionorder_consumed WHERE psono = '{$safePso}')) AND rsa.status = 'readytodispatch' LIMIT 1");
    if ($rsaChk && $rsaChk->num_rows > 0) {
        $hasReadyStock = true;
    }

    // Check if invoice has been marked readytodispatch or dispatched in sales_invoice
    $hasSiDispatched = false;
    $chkSi = $krfconn->query("
        SELECT 1 FROM sales_invoice 
        WHERE (psono = '{$safePso}' OR sono = '{$safePso}') 
          AND LOWER(TRIM(status)) IN ('readytodispatch', 'dispatched') 
        LIMIT 1
    ");
    if ($chkSi && $chkSi->num_rows > 0) {
        $hasSiDispatched = true;
    }

    while ($row = $chkRes->fetch_assoc()) {
        $itemStatus = strtolower($row['status'] ?? '');
        if ($itemStatus === 'cancelled') {
            $invalidStatuses[] = "Item '{$row['product_name']}' is Cancelled";
        } elseif (!in_array($itemStatus, ['readytodispatch', 'fulldispatch', 'partialdispatch', 'dispatched']) && !$hasPlannedTrip && !$hasReadyStock && !$hasSiDispatched) {
            $invalidStatuses[] = "Item '{$row['product_name']}' is '{$row['status']}' (requires vehicle allocation Planned or status readytodispatch in sales_invoice)";
        }
        $row['tray_cavity'] = !empty($row['qtypertray']) ? ($row['qtypertray'] . ' Cavity') : '126 Cavity';
        $isPart = (strtolower($row['status'] ?? '') === 'partialdispatch');
        if ($isPart && (int)($row['dispatchedqty'] ?? 0) > 0) {
            $rowPlants = (int)$row['dispatchedqty'];
        } else {
            $basePlants = (int)($row['allocate_healthyplant'] ?: ($row['consumed_qty'] ?: 0));
            $extraPlants = (int)($row['extraqtyforshifting'] ?? 0);
            $rowPlants = $basePlants + $extraPlants;
        }
        $row['dispatchedqty'] = $rowPlants;
        $consumedItems[] = $row;
        $totalAllocatedQty += $rowPlants;
    }

    if (!empty($invalidStatuses)) {
        outJson([
            'status'  => 'error',
            'message' => 'Gate Pass cannot be generated! Found invalid items: ' . implode(', ', $invalidStatuses)
        ]);
    }

    // 2. Fetch Client Info
    $clientSql = "
        SELECT 
            pso.psono, pso.clientid, pso.clientname, pso.mobileno, pso.emailid, pso.address, 
            pso.delivery_address, pso.districtname, pso.statename, pso.created_at AS order_date,
            cm.client_name AS cm_name, cm.phones AS cm_phone, cm.client_email AS cm_email, 
            cm.invoicing_address AS cm_address, cm.statename AS cm_statename, cm.districtname AS cm_districtname, cm.cityname AS cm_cityname
        FROM krf_provisional_sales_order pso
        LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
        WHERE pso.psono = '{$safePso}'
        LIMIT 1
    ";
    $cRes = $krfconn->query($clientSql);
    $cRow = ($cRes && $cRes->num_rows > 0) ? $cRes->fetch_assoc() : [];

    $cLoc = trim(
        ($cRow['districtname'] ?: ($cRow['cm_districtname'] ?? '')) . 
        (($cRow['statename'] ?: ($cRow['cm_statename'] ?? '')) ? ', ' . ($cRow['statename'] ?: ($cRow['cm_statename'] ?? '')) : '')
    , ', ');

    $clientDetails = [
        'clientid'         => $cRow['clientid'] ?? '',
        'clientname'       => $cRow['clientname'] ?: ($cRow['cm_name'] ?: 'Customer'),
        'mobileno'         => $cRow['mobileno'] ?: ($cRow['cm_phone'] ?: ''),
        'email'            => $cRow['emailid'] ?: ($cRow['cm_email'] ?? ''),
        'address'          => $cRow['address'] ?: ($cRow['cm_address'] ?: ''),
        'delivery_address' => $cRow['delivery_address'] ?: ($cRow['address'] ?: ($cRow['cm_address'] ?: ($cLoc ?: ''))),
        'districtname'     => $cRow['districtname'] ?: ($cRow['cm_districtname'] ?? ''),
        'statename'        => $cRow['statename'] ?: ($cRow['cm_statename'] ?? '')
    ];

    $vehicleDetails = [
        'vehicleno'        => $vehicleno,
        'transporter_name' => $transporterName,
        'drivername'       => $drivername,
        'drivermobile'     => $drivermobile,
        'remarks'          => $remarks
    ];

    $sono = $consumedItems[0]['sono'] ?? '';
    $orderDate = $cRow['order_date'] ?? '';

    // Fetch invoice number associated with this provisional sales order (prefer sales_invoice table)
    $invoiceno = '';
    $invoiceDate = null;
    $chkSiRow = $krfconn->query("
        SELECT invoiceno, invoicedate 
        FROM sales_invoice 
        WHERE (psono = '{$safePso}' OR sono = '{$safePso}') 
          AND LOWER(TRIM(status)) != 'cancelled' 
        ORDER BY id DESC LIMIT 1
    ");
    if ($chkSiRow && $chkSiRow->num_rows > 0) {
        $siRow = $chkSiRow->fetch_assoc();
        $invoiceno = $siRow['invoiceno'] ?? '';
        if (!empty($siRow['invoicedate'])) {
            $invoiceDate = $siRow['invoicedate'];
        }
    }
    if (empty($invoiceno)) {
        $invSql = "SELECT invoiceno, proformainvno, invoicedate FROM krf_sales_order_items WHERE psono = '{$safePso}' AND ((invoiceno IS NOT NULL AND invoiceno != '') OR (proformainvno IS NOT NULL AND proformainvno != '')) LIMIT 1";
        $invRes = $krfconn->query($invSql);
        if ($invRes && $invRes->num_rows > 0) {
            $invRow = $invRes->fetch_assoc();
            $invoiceno = $invRow['invoiceno'] ?: ($invRow['proformainvno'] ?: '');
            if (!empty($invRow['invoicedate'])) {
                $invoiceDate = $invRow['invoicedate'];
            }
        }
    }

    // Generate Gatepass Number: Short unique 5 random digits (e.g. GP-58291)
    do {
        $rand5 = mt_rand(10000, 99999);
        $gatepassNo = 'GP-' . $rand5;
        $chkExists = $krfconn->query("SELECT id FROM krf_gatepass_tracking WHERE gatepass_no = '{$gatepassNo}' LIMIT 1");
        if (!$chkExists || $chkExists->num_rows === 0) {
            break;
        }
    } while (true);

    $krfconn->begin_transaction();

    try {
        // Normalize consumed items to ensure exact dispatch quantity and tray counts
        foreach ($consumedItems as &$ci) {
            $isPart = (strtolower($ci['status'] ?? '') === 'partialdispatch');
            if ($isPart && (int)($ci['dispatchedqty'] ?? 0) > 0) {
                $p = (int)$ci['dispatchedqty'];
            } else {
                $p = (int)($ci['allocate_healthyplant'] ?: ($ci['consumed_qty'] ?: 0));
            }
            $ci['allocate_healthyplant'] = $p;
            $ci['consumed_qty'] = $p;
            $ci['quantity'] = $p;
            $cavNum = (int)filter_var($ci['tray_cavity'] ?? '126 Cavity', FILTER_SANITIZE_NUMBER_INT) ?: 126;
            $ci['trays'] = (int)ceil($p / $cavNum);
            $ci['trays_count'] = $ci['trays'];
        }
        unset($ci);

        // 3. Render PDF document
        $pdfResult = createGatePassPdfDoc(
            $krfconn,
            $projectid,
            $psono,
            $sono,
            $gatepassNo,
            $clientDetails,
            $vehicleDetails,
            $consumedItems,
            $loginname,
            $dispatchDate
        );

        $clientIdStr = (string)($clientDetails['clientid'] ?? '');
        $totalPlants = (int)$pdfResult['total_plants'];
        $totalTrays  = (int)$pdfResult['total_trays'];
        $relPath     = $pdfResult['rel_path'];
        $qrDataStr   = $pdfResult['qr_data'] ?? '';
        $mainCavity  = !empty($consumedItems[0]['tray_cavity']) ? $consumedItems[0]['tray_cavity'] : '126 Cavity';

        // 4. Save record in NEW krf_gatepass_tracking table (with invoiceno & invoice_date)
        $insMasterStmt = $krfconn->prepare("
            INSERT INTO krf_gatepass_tracking (
                gatepass_no, projectid, psono, sono, invoiceno, invoice_date,
                clientid, clientname, clientphone, delivery_address, districtname,
                statename, vehicleno, transporter_name, drivername, drivermobile,
                total_trays, total_plants, tray_cavity, qr_code_data, filepath,
                remarks, status, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'generated', ?, NOW())
        ");
        if ($insMasterStmt) {
            $insMasterStmt->bind_param(
                'sissssssssssssssiisssss',
                $gatepassNo,
                $projectid,
                $psono,
                $sono,
                $invoiceno,
                $invoiceDate,
                $clientIdStr,
                $clientDetails['clientname'],
                $clientDetails['mobileno'],
                $clientDetails['delivery_address'],
                $clientDetails['districtname'],
                $clientDetails['statename'],
                $vehicleno,
                $transporterName,
                $drivername,
                $drivermobile,
                $totalTrays,
                $totalPlants,
                $mainCavity,
                $qrDataStr,
                $relPath,
                $remarks,
                $loginname
            );
            $insMasterStmt->execute();
            $newMasterId = $insMasterStmt->insert_id;
            $insMasterStmt->close();

            // 4b. Insert items into krf_gatepass_items table
            $insItemStmt = $krfconn->prepare("
                INSERT INTO krf_gatepass_items (
                    gatepass_id, gatepass_no, psono, product_id, product_name,
                    stockorderno, tray_cavity, trays_count, plant_qty, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'dispatched', NOW())
            ");
            if ($insItemStmt) {
                foreach ($consumedItems as $itemRow) {
                    $prodId    = (int)($itemRow['product_id'] ?? 0);
                    $prodName  = $itemRow['product_name'] ?? '';
                    $stkNo     = $itemRow['stockorderno'] ?? '';
                    $rowCavity = !empty($itemRow['tray_cavity']) ? $itemRow['tray_cavity'] : (!empty($itemRow['qtypertray']) ? ($itemRow['qtypertray'] . ' Cavity') : '126 Cavity');
                    $isPart    = (strtolower($itemRow['status'] ?? '') === 'partialdispatch');
                    if ($isPart && (int)($itemRow['dispatchedqty'] ?? 0) > 0) {
                        $rowPlants = (int)$itemRow['dispatchedqty'];
                    } else {
                        $basePlants = (int)($itemRow['allocate_healthyplant'] ?: ($itemRow['consumed_qty'] ?: 0));
                        $extraPlants = (int)($itemRow['extraqtyforshifting'] ?? 0);
                        $rowPlants = $basePlants + $extraPlants;
                    }
                    $cavityNum = (int)filter_var($rowCavity, FILTER_SANITIZE_NUMBER_INT) ?: 126;
                    $rowTrays  = !empty($itemRow['trays']) ? (int)$itemRow['trays'] : (int)ceil($rowPlants / $cavityNum);

                    $insItemStmt->bind_param(
                        'ississsii',
                        $newMasterId,
                        $gatepassNo,
                        $psono,
                        $prodId,
                        $prodName,
                        $stkNo,
                        $rowCavity,
                        $rowTrays,
                        $rowPlants
                    );
                    $insItemStmt->execute();
                }
                $insItemStmt->close();
            }
        }

        // 5 & 6. Check if order has remaining plants pending loading/dispatch (Partial Dispatch)
        $isPartialOrder = false;
        $chkPartial = $krfconn->query("
            SELECT 1 FROM krf_sales_order_items 
            WHERE psono = '{$safePso}' 
              AND (
                  LOWER(TRIM(status)) = 'partialdispatch' 
                  OR allocate_ordqty > 0
              )
              AND product_id != 'BOX_PACKING' 
              AND product_name NOT LIKE '%Box Packing Charges%'
            LIMIT 1
        ");
        if ($chkPartial && $chkPartial->num_rows > 0) {
            $isPartialOrder = true;
        }

        if ($isPartialOrder) {
            // Keep status = 'PartialDispatch' so remaining balance stays open in Ready To Load
            $updConsumed = $krfconn->prepare("
                UPDATE krf_productionorder_consumed 
                SET 
                    status = 'PartialDispatch',
                    dispatchedby = ?,
                    dispatchedat = NOW(),
                    updated_at = NOW()
                WHERE psono = ? AND (status != 'Cancelled' OR status IS NULL)
            ");
            if ($updConsumed) {
                $updConsumed->bind_param('ss', $loginname, $psono);
                $updConsumed->execute();
                $updConsumed->close();
            }

            // Update sales order items: Keep status as PartialDispatch for items with remaining balance
            $updSoi = $krfconn->prepare("
                UPDATE krf_sales_order_items 
                SET 
                    status = CASE WHEN allocate_ordqty > 0 THEN 'PartialDispatch' ELSE 'Dispatched' END,
                    actionstatus = CASE WHEN allocate_ordqty > 0 THEN 'PartialLoaded' ELSE 'Dispatched' END,
                    ammendedby = ?,
                    updateamended = NOW()
                WHERE psono = ?
            ");
            if ($updSoi) {
                $updSoi->bind_param('ss', $loginname, $psono);
                $updSoi->execute();
                $updSoi->close();
            }
        } else {
            // Full dispatch: All allocated items have been dispatched
            $updConsumed = $krfconn->prepare("
                UPDATE krf_productionorder_consumed 
                SET 
                    status = 'dispatched',
                    dispatchedqty = (COALESCE(allocate_healthyplant, 0) + COALESCE(extraqtyforshifting, 0) + CASE WHEN COALESCE(allocate_healthyplant, 0) = 0 THEN COALESCE(consumed_qty, 0) ELSE 0 END),
                    dispatchedby = ?,
                    dispatchedat = NOW(),
                    updated_at = NOW()
                WHERE psono = ? AND (status != 'Cancelled' OR status IS NULL)
            ");
            if ($updConsumed) {
                $updConsumed->bind_param('ss', $loginname, $psono);
                $updConsumed->execute();
                $updConsumed->close();
            }

            // Update krf_sales_order_items
            $updSoi = $krfconn->prepare("
                UPDATE krf_sales_order_items 
                SET 
                    status = 'Dispatched',
                    actionstatus = 'Dispatched',
                    ammendedby = ?,
                    updateamended = NOW()
                WHERE psono = ?
            ");
            if ($updSoi) {
                $updSoi->bind_param('ss', $loginname, $psono);
                $updSoi->execute();
                $updSoi->close();
            }
        }

        // 5b. Update krf_readystock_allocation: status -> 'dispatched'
        $updRsa = $krfconn->prepare("
            UPDATE krf_readystock_allocation 
            SET 
                status = 'dispatched',
                updated_at = NOW()
            WHERE (stockorder_no = ? OR stockorder_no = ? OR allocate_stockorder_no = ?) AND status != 'cancel'
        ");
        if ($updRsa) {
            $updRsa->bind_param('sss', $psono, $sono, $sono);
            $updRsa->execute();
            $updRsa->close();
        }

        // 5c. Update sales_invoice & sales_invoice_items: status -> 'dispatched'
        $escPso = mysqli_real_escape_string($krfconn, $psono);
        $escSono = mysqli_real_escape_string($krfconn, $sono);
        $escInv = mysqli_real_escape_string($krfconn, $invoiceno);

        $invWhereFinal = (!empty($escInv))
            ? "(invoiceno = '{$escInv}' OR psono = '{$escPso}' OR (sono = '{$escSono}' AND '{$escSono}' != ''))"
            : "(psono = '{$escPso}' OR (sono = '{$escSono}' AND '{$escSono}' != ''))";

        $krfconn->query("
            UPDATE sales_invoice 
            SET status = 'dispatched', updated_at = NOW() 
            WHERE {$invWhereFinal} AND status != 'cancelled'
        ");
        $krfconn->query("
            UPDATE sales_invoice_items 
            SET status = 'dispatched', updated_at = NOW() 
            WHERE {$invWhereFinal} AND status != 'cancelled'
        ");

        // 7. Link gatepass_id to krf_vehicletrip & krf_vehicle_travel_history (status remains Planned until Gate Exit Verification)
        $updVt = $krfconn->prepare("
            UPDATE krf_vehicletrip vt
            JOIN krf_vehicle_travel_history vth ON (vt.trip_no = vth.trip_id)
            SET vt.gatepass_id = ?,
                vt.updated_at = NOW(),
                vth.gatepass_id = ?,
                vth.updated_at = NOW()
            WHERE vth.psono = ?
        ");
        if ($updVt) {
            $updVt->bind_param('iis', $newMasterId, $newMasterId, $psono);
            $updVt->execute();
            $updVt->close();
        }

        $krfconn->commit();

        outJson([
            'status'       => 'success',
            'message'      => "Gate Pass {$gatepassNo} generated successfully!",
            'gatepass_no'  => $gatepassNo,
            'filepath'     => $relPath,
            'pdf_url'      => '../' . $relPath,
            'total_plants' => $totalPlants,
            'total_trays'  => $totalTrays
        ]);

    } catch (Exception $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => 'Failed to generate Gate Pass: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------
// 4. ACTION: GET ACTIVE VEHICLES LIST
// -------------------------------------------------------------
if ($action === 'get_vehicles') {
    $vSql = "SELECT vehicleid, vehicleno, vehiclename, vehicleownername, plantscapacity FROM krf_vehicle_master WHERE status = 'Active' ORDER BY vehiclename ASC";
    $vRes = $krfconn->query($vSql);
    $vehicles = [];
    if ($vRes) {
        while ($v = $vRes->fetch_assoc()) {
            $vehicles[] = $v;
        }
    }
    outJson(['status' => 'success', 'vehicles' => $vehicles]);
}

// -------------------------------------------------------------
// 5. ACTION: VIEW / STREAM LATEST GATE PASS PDF (AUTO-REGENERATES WITH LATEST LAYOUT)
// -------------------------------------------------------------
if ($action === 'view_pdf') {
    $psono = trim($_REQUEST['psono'] ?? '');
    $gpNo  = trim($_REQUEST['gatepass_no'] ?? '');

    $safePso = mysqli_real_escape_string($krfconn, $psono);
    $safeGp  = mysqli_real_escape_string($krfconn, $gpNo);

    $where = [];
    if (!empty($safeGp)) {
        $where[] = "gatepass_no = '{$safeGp}'";
    }
    if (!empty($safePso)) {
        $where[] = "psono = '{$safePso}'";
    }
    if (empty($where)) {
        die('Provisional Order Number or Gate Pass Number is required.');
    }

    $whereClause = implode(' OR ', $where);
    $gpRes = $krfconn->query("SELECT * FROM krf_gatepass_tracking WHERE {$whereClause} ORDER BY id DESC LIMIT 1");
    if (!$gpRes || $gpRes->num_rows === 0) {
        die('Gate Pass record not found in database.');
    }
    $gp = $gpRes->fetch_assoc();

    $actualPso  = $gp['psono'];
    $actualGpNo = $gp['gatepass_no'];
    $actualSono = $gp['sono'] ?? '';
    $projId     = (int)($gp['projectid'] ?? 17) ?: 17;
    $createdBy  = $gp['created_by'] ?: 'Admin';
    $orderDate  = $gp['created_at'];

    // Fetch Client Details
    $clientSql = "
        SELECT 
            pso.psono, pso.clientid, pso.clientname, pso.mobileno, pso.emailid, pso.address, 
            pso.delivery_address, pso.districtname, pso.statename, pso.created_at AS order_date,
            cm.client_name AS cm_name, cm.phones AS cm_phone, cm.client_email AS cm_email, 
            cm.invoicing_address AS cm_address, cm.statename AS cm_statename, cm.districtname AS cm_districtname, cm.cityname AS cm_cityname
        FROM krf_provisional_sales_order pso
        LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
        WHERE pso.psono = '{$actualPso}'
        LIMIT 1
    ";
    $cRes = $krfconn->query($clientSql);
    $cRow = ($cRes && $cRes->num_rows > 0) ? $cRes->fetch_assoc() : [];

    $cLoc = trim(
        ($cRow['districtname'] ?: ($cRow['cm_districtname'] ?? ($gp['districtname'] ?? ''))) . 
        (($cRow['statename'] ?: ($cRow['cm_statename'] ?? ($gp['statename'] ?? ''))) ? ', ' . ($cRow['statename'] ?: ($cRow['cm_statename'] ?? ($gp['statename'] ?? ''))) : '')
    , ', ');

    $clientDetails = [
        'clientid'         => $cRow['clientid'] ?? ($gp['clientid'] ?? ''),
        'clientname'       => $cRow['clientname'] ?: ($cRow['cm_name'] ?: ($gp['clientname'] ?: 'Customer')),
        'mobileno'         => $cRow['mobileno'] ?: ($cRow['cm_phone'] ?: ($gp['clientphone'] ?? '')),
        'email'            => $cRow['emailid'] ?: ($cRow['cm_email'] ?? ''),
        'address'          => $cRow['address'] ?: ($cRow['cm_address'] ?: ($gp['delivery_address'] ?? '')),
        'delivery_address' => $cRow['delivery_address'] ?: ($cRow['address'] ?: ($cRow['cm_address'] ?: ($gp['delivery_address'] ?: ($cLoc ?: '')))),
        'districtname'     => $cRow['districtname'] ?? ($gp['districtname'] ?? ''),
        'statename'        => $cRow['statename'] ?? ($gp['statename'] ?? '')
    ];

    $vehicleDetails = [
        'vehicleno'        => $gp['vehicleno'] ?: '',
        'transporter_name' => $gp['transporter_name'] ?: '',
        'drivername'       => $gp['drivername'] ?: '',
        'drivermobile'     => $gp['drivermobile'] ?: '',
        'remarks'          => $gp['remarks'] ?: ''
    ];

    // Try to fetch items from krf_gatepass_items
    $items = [];
    if (!empty($actualGpNo)) {
        $gpiRes = $krfconn->query("SELECT * FROM krf_gatepass_items WHERE gatepass_no = '{$actualGpNo}' ORDER BY id ASC");
        if ($gpiRes && $gpiRes->num_rows > 0) {
            while ($gpi = $gpiRes->fetch_assoc()) {
                $items[] = [
                    'product_name'          => $gpi['product_name'],
                    'stockorderno'          => $gpi['stockorderno'],
                    'tray_cavity'           => $gpi['tray_cavity'],
                    'allocate_healthyplant' => $gpi['plant_qty'],
                    'quantity'              => $gpi['plant_qty'],
                    'trays'                 => $gpi['trays_count']
                ];
            }
        }
    }

    // If not found in krf_gatepass_items, fetch from krf_productionorder_consumed
    if (empty($items)) {
        $itemSql = "
            SELECT poc.id, poc.psono, poc.sono, poc.stockorderno, poc.product_id, poc.product_name, 
                   poc.allocate_healthyplant, poc.extraqtyforshifting, poc.consumed_qty, poc.status,
                   (SELECT ppd.qtypertray FROM krf_polyhouse_plant_details ppd WHERE ppd.stockorder_no = poc.stockorderno AND ppd.qtypertray IS NOT NULL AND ppd.qtypertray != '' LIMIT 1) AS qtypertray
            FROM krf_productionorder_consumed poc
            WHERE poc.psono = '{$actualPso}'
        ";
        $iRes = $krfconn->query($itemSql);
        if ($iRes) {
            while ($it = $iRes->fetch_assoc()) {
                $it['tray_cavity'] = !empty($it['qtypertray']) ? ($it['qtypertray'] . ' Cavity') : '126 Cavity';
                $items[] = $it;
            }
        }
    }

    if (empty($items)) {
        $items[] = [
            'product_name'          => 'Plants Dispatch',
            'stockorderno'          => '-',
            'tray_cavity'           => $gp['tray_cavity'] ?? '126 Cavity',
            'allocate_healthyplant' => (int)($gp['total_plants'] ?? ($gp['deliveryqty'] ?? 0)),
            'expecteddelivery'      => $gp['deliverydate'] ?? ''
        ];
    }

    $actualOrderDate = !empty($cRow['order_date']) ? $cRow['order_date'] : $orderDate;

    $pdfResult = createGatePassPdfDoc(
        $krfconn,
        $projId,
        $actualPso,
        $actualSono,
        $actualGpNo,
        $clientDetails,
        $vehicleDetails,
        $items,
        $createdBy,
        $actualOrderDate
    );

    if (!file_exists($pdfResult['full_path'])) {
        die('Failed to locate generated PDF.');
    }

    if (ob_get_level() > 0) {
        @ob_clean();
    }
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . $pdfResult['filename'] . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    header('Content-Length: ' . filesize($pdfResult['full_path']));
    readfile($pdfResult['full_path']);
    exit;
}

// -------------------------------------------------------------
// 6. ACTION: SCAN LOOKUP (FOR GATE PASS SCANNER PAGE)
// -------------------------------------------------------------
if ($action === 'scan_lookup') {
    $scanRaw = trim($_REQUEST['qr_data'] ?? ($_REQUEST['gatepass_no'] ?? ($_REQUEST['psono'] ?? '')));
    if (empty($scanRaw)) {
        outJson(['status' => 'error', 'message' => 'Scanned QR data or Gate Pass / PSO Number is required.']);
    }

    $gpNo = '';
    $psoNo = '';

    // Parse structured QR data: GATEPASS:GP-XXXX|PSO:KF-PS-XXXX|...
    if (strpos($scanRaw, 'GATEPASS:') !== false) {
        $parts = explode('|', $scanRaw);
        foreach ($parts as $p) {
            $kv = explode(':', $p, 2);
            if (count($kv) === 2) {
                $k = strtoupper(trim($kv[0]));
                $v = trim($kv[1]);
                if ($k === 'GATEPASS') $gpNo = $v;
                if ($k === 'PSO') $psoNo = $v;
            }
        }
    }

    if (empty($gpNo) && stripos($scanRaw, 'GP-') === 0) {
        $gpNo = $scanRaw;
    }
    if (empty($psoNo) && stripos($scanRaw, 'KF-PS-') === 0) {
        $psoNo = $scanRaw;
    }
    if (empty($gpNo) && empty($psoNo)) {
        $gpNo = $scanRaw;
    }

    $safeGp  = mysqli_real_escape_string($krfconn, $gpNo);
    $safePso = mysqli_real_escape_string($krfconn, $psoNo);
    $safeRaw = mysqli_real_escape_string($krfconn, $scanRaw);

    $where = [];
    if (!empty($safeGp)) $where[] = "gatepass_no = '{$safeGp}'";
    if (!empty($safePso)) $where[] = "psono = '{$safePso}'";
    $where[] = "qr_code_data LIKE '%{$safeRaw}%'";

    $whereSql = implode(' OR ', $where);

    // 1. Fetch Master Gate Pass Record
    $gpRes = $krfconn->query("SELECT * FROM krf_gatepass_tracking WHERE {$whereSql} ORDER BY id DESC LIMIT 1");

    if (!$gpRes || $gpRes->num_rows === 0) {
        outJson([
            'status'  => 'error',
            'message' => "No Gate Pass record found matching '{$scanRaw}'. Please check the QR code or number."
        ]);
    }

    $gp = $gpRes->fetch_assoc();
    $masterId = (int)($gp['id'] ?? 0);
    $actualGpNo = $gp['gatepass_no'] ?? '';
    $actualPso = $gp['psono'] ?? '';
    $actualSono = $gp['sono'] ?? '';

    // 2. Fetch Client Info
    $clientSql = "
        SELECT 
            pso.psono, pso.clientid, pso.clientname, pso.mobileno, pso.emailid, pso.address, 
            pso.delivery_address, pso.districtname, pso.statename, pso.created_at AS order_date,
            cm.client_name AS cm_name, cm.phones AS cm_phone, cm.client_email AS cm_email, 
            cm.invoicing_address AS cm_address, cm.vat AS cm_gst, cm.statename AS cm_statename, cm.districtname AS cm_districtname, cm.cityname AS cm_cityname
        FROM krf_provisional_sales_order pso
        LEFT JOIN krf_client_master cm ON pso.clientid = cm.client_id
        WHERE pso.psono = '{$actualPso}'
        LIMIT 1
    ";
    $cRes = $krfconn->query($clientSql);
    $cRow = ($cRes && $cRes->num_rows > 0) ? $cRes->fetch_assoc() : [];

    $cLoc = trim(
        ($cRow['districtname'] ?: ($cRow['cm_districtname'] ?? ($gp['districtname'] ?? ''))) . 
        (($cRow['statename'] ?: ($cRow['cm_statename'] ?? ($gp['statename'] ?? ''))) ? ', ' . ($cRow['statename'] ?: ($cRow['cm_statename'] ?? ($gp['statename'] ?? ''))) : '')
    , ', ');

    $client = [
        'clientid'         => $cRow['clientid'] ?? ($gp['clientid'] ?? ''),
        'clientname'       => $cRow['clientname'] ?: ($cRow['cm_name'] ?: ($gp['clientname'] ?: 'Customer')),
        'mobileno'         => $cRow['mobileno'] ?: ($cRow['cm_phone'] ?: ($gp['clientphone'] ?? '-')),
        'email'            => $cRow['emailid'] ?: ($cRow['cm_email'] ?? ''),
        'gstin'            => $cRow['cm_gst'] ?? '-',
        'delivery_address' => $cRow['delivery_address'] ?: ($cRow['address'] ?: ($cRow['cm_address'] ?: ($gp['delivery_address'] ?: ($cLoc ?: 'Destination')))),
        'districtname'     => $cRow['districtname'] ?? ($gp['districtname'] ?? ''),
        'statename'        => $cRow['statename'] ?? ($gp['statename'] ?? '')
    ];

    // 3. Fetch Items
    $items = [];
    $totalTrays = 0;
    $totalPlants = 0;

    if (!empty($actualGpNo)) {
        $gpiRes = $krfconn->query("SELECT * FROM krf_gatepass_items WHERE gatepass_no = '{$actualGpNo}' ORDER BY id ASC");
        if ($gpiRes && $gpiRes->num_rows > 0) {
            while ($it = $gpiRes->fetch_assoc()) {
                $totalTrays += (int)$it['trays_count'];
                $totalPlants += (int)$it['plant_qty'];
                $items[] = [
                    'product_name' => $it['product_name'],
                    'stockorderno' => $it['stockorderno'],
                    'tray_cavity'  => $it['tray_cavity'] ?: '126 Cavity',
                    'trays'        => (int)$it['trays_count'],
                    'plants'       => (int)$it['plant_qty'],
                    'status'       => $it['status'] ?: 'Dispatched'
                ];
            }
        }
    }

    if (empty($items)) {
        $itemSql = "
            SELECT poc.id, poc.psono, poc.sono, poc.stockorderno, poc.product_id, poc.product_name, 
                   poc.allocate_healthyplant, poc.extraqtyforshifting, poc.consumed_qty, poc.status,
                   (SELECT ppd.qtypertray FROM krf_polyhouse_plant_details ppd WHERE ppd.stockorder_no = poc.stockorderno AND ppd.qtypertray IS NOT NULL AND ppd.qtypertray != '' LIMIT 1) AS qtypertray
            FROM krf_productionorder_consumed poc
            WHERE poc.psono = '{$actualPso}'
        ";
        $iRes = $krfconn->query($itemSql);
        if ($iRes && $iRes->num_rows > 0) {
            while ($it = $iRes->fetch_assoc()) {
                $cavityStr = !empty($it['qtypertray']) ? ($it['qtypertray'] . ' Cavity') : '126 Cavity';
                $cavityNum = (int)filter_var($cavityStr, FILTER_SANITIZE_NUMBER_INT) ?: 126;
                $basePlants = (int)($it['allocate_healthyplant'] ?: ($it['consumed_qty'] ?: 0));
                $extraPlants = (int)($it['extraqtyforshifting'] ?? 0);
                $plants = $basePlants + $extraPlants;
                $trays = (int)ceil($plants / $cavityNum);

                $totalTrays += $trays;
                $totalPlants += $plants;

                $items[] = [
                    'product_name' => $it['product_name'],
                    'stockorderno' => $it['stockorderno'],
                    'tray_cavity'  => $cavityStr,
                    'trays'        => $trays,
                    'plants'       => $plants,
                    'status'       => $it['status'] ?: 'Dispatched'
                ];
            }
        }
    }

    if (empty($items)) {
        $trays = (int)($gp['total_trays'] ?? ($gp['traycount'] ?? 0));
        $plants = (int)($gp['total_plants'] ?? ($gp['deliveryqty'] ?? 0));
        $totalTrays = $trays;
        $totalPlants = $plants;
        $items[] = [
            'product_name' => 'Plants Dispatch',
            'stockorderno' => '-',
            'tray_cavity'  => $gp['tray_cavity'] ?? '126 Cavity',
            'trays'        => $trays,
            'plants'       => $plants,
            'status'       => 'Dispatched'
        ];
    }

    // 4. Fetch Financials & Freight Details
    $safeInv = !empty($gp['invoiceno']) ? mysqli_real_escape_string($krfconn, $gp['invoiceno']) : '';
    if (!empty($safeInv)) {
        $finWhere = "invoiceno = '{$safeInv}'";
    } else {
        $finWhere = "psono = '{$actualPso}' AND (invoiceno IS NULL OR invoiceno = '')";
    }

    $finSql = "
        SELECT 
            SUM(CASE WHEN product_id != 'BOX_PACKING' AND product_name NOT LIKE '%Box Packing%' THEN taxableamount ELSE 0 END) AS total_plants_val,
            SUM(taxableamount) AS total_taxable,
            SUM(tax_amount) AS total_tax,
            SUM(amount) AS total_amount,
            SUM(freight_charges) AS total_freight
        FROM krf_sales_order_items
        WHERE {$finWhere}
    ";
    $finRes = $krfconn->query($finSql);
    $fin = ($finRes && $finRes->num_rows > 0) ? $finRes->fetch_assoc() : [];

    $freightCharges = (float)($gp['freight_charges'] ?? ($fin['total_freight'] ?? 0.00));
    $orderPlantsVal = (float)($fin['total_plants_val'] ?? ($fin['total_taxable'] ?? 0.00));
    $orderTax       = (float)($fin['total_tax'] ?? 0.00);
    $orderGrandTot  = (float)($fin['total_amount'] ?? 0.00) + $freightCharges;

    $pdfUrl = !empty($gp['filepath']) ? ('../' . $gp['filepath']) : ('modal/gatepass_modal.php?action=view_pdf&gatepass_no=' . urlencode($actualGpNo));

    $safePsoEsc = mysqli_real_escape_string($krfconn, $actualPso);
    $safeSoEsc  = mysqli_real_escape_string($krfconn, $actualSono);
    $tripRes = $krfconn->query("
        SELECT vt.trip_no, vt.status AS trip_status, vt.dispatch_time, vt.gatepass_id
        FROM krf_vehicletrip vt
        WHERE vt.gatepass_id = {$masterId}
           OR vt.trip_no IN (
               SELECT trip_id FROM krf_vehicle_travel_history 
               WHERE ((psono = '{$safePsoEsc}' AND '{$safePsoEsc}' != '') 
                   OR (stockorder_no = '{$safeSoEsc}' AND '{$safeSoEsc}' != ''))
           )
        ORDER BY vt.trip_id DESC LIMIT 1
    ");
    $tripRow = ($tripRes && $tripRes->num_rows > 0) ? $tripRes->fetch_assoc() : [];

    outJson([
        'status'    => 'success',
        'gatepass'  => [
            'id'                => $masterId,
            'gatepass_no'       => $actualGpNo,
            'psono'             => $actualPso,
            'sono'              => $actualSono,
            'invoiceno'         => $gp['invoiceno'] ?? '',
            'invoice_date'      => $gp['invoice_date'] ?? '',
            'vehicleno'         => $gp['vehicleno'] ?: '-',
            'transporter_name'  => $gp['transporter_name'] ?: 'Karjee Transport / Self',
            'drivername'        => $gp['drivername'] ?: 'Self / Transporter Driver',
            'drivermobile'      => $gp['drivermobile'] ?: '-',
            'remarks'           => $gp['remarks'] ?: '',
            'status'            => $gp['status'] ?: 'generated',
            'gate_status'       => $gp['gate_status'] ?: 'pending',
            'trip_no'           => $tripRow['trip_no'] ?? '',
            'trip_status'       => $tripRow['trip_status'] ?? '',
            'dispatch_time'     => !empty($tripRow['dispatch_time']) ? date('d-M-Y h:i A', strtotime($tripRow['dispatch_time'])) : '',
            'gate_verified_at'  => $gp['gate_verified_at'] ? date('d-M-Y h:i A', strtotime($gp['gate_verified_at'])) : null,
            'gate_verified_by'  => $gp['gate_verified_by'] ?? '',
            'created_by'        => $gp['created_by'] ?: 'Admin',
            'created_at'        => date('d-M-Y h:i A', strtotime($gp['created_at'])),
            'total_trays'       => $totalTrays,
            'total_plants'      => $totalPlants,
            'pdf_url'           => $pdfUrl
        ],
        'client'    => $client,
        'items'     => $items,
        'financials'=> [
            'freight_charges'   => $freightCharges,
            'plants_value'      => $orderPlantsVal,
            'tax_amount'        => $orderTax,
            'grand_total'       => $orderGrandTot
        ]
    ]);
}

// -------------------------------------------------------------
// 7. ACTION: MARK GATE VERIFIED / EXIT
// -------------------------------------------------------------
if ($action === 'mark_gate_verified') {
    $gpNo = trim($_POST['gatepass_no'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if (empty($gpNo)) {
        outJson(['status' => 'error', 'message' => 'Gate Pass Number is required.']);
    }

    $safeGp = mysqli_real_escape_string($krfconn, $gpNo);
    $safeRem = mysqli_real_escape_string($krfconn, $remarks);

    // 1. Fetch gate pass record to get id (gatepass_id), psono, sono, vehicleno
    $chk = $krfconn->query("SELECT id, psono, sono, vehicleno, gate_status FROM krf_gatepass_tracking WHERE gatepass_no = '{$safeGp}' LIMIT 1");
    if (!$chk || $chk->num_rows === 0) {
        outJson(['status' => 'error', 'message' => "Gate Pass '{$gpNo}' not found."]);
    }
    $gpRow = $chk->fetch_assoc();
    $gpId = (int)$gpRow['id'];
    $psono = trim($gpRow['psono'] ?? '');
    $sono = trim($gpRow['sono'] ?? '');
    $vehicleno = trim($gpRow['vehicleno'] ?? '');

    $safePso = mysqli_real_escape_string($krfconn, $psono);
    $safeSo  = mysqli_real_escape_string($krfconn, $sono);
    $safeVeh = mysqli_real_escape_string($krfconn, $vehicleno);

    $krfconn->begin_transaction();
    try {
        // 2. Update krf_gatepass_tracking
        $sql = "
            UPDATE krf_gatepass_tracking 
            SET 
                gate_status = 'passed_gate',
                gate_verified_at = NOW(),
                gate_verified_by = '{$loginname}',
                remarks = IF('{$safeRem}' != '', CONCAT(COALESCE(remarks, ''), ' | Gate: ', '{$safeRem}'), remarks),
                updated_at = NOW()
            WHERE gatepass_no = '{$safeGp}'
        ";
        if (!$krfconn->query($sql)) {
            throw new Exception("Failed to update gatepass tracking: " . $krfconn->error);
        }

        // 3. Update krf_vehicle_travel_history: link gatepass_id, status to Dispatched, dispatch_time to NOW()
        if (!empty($psono) || !empty($sono)) {
            $updVthSql = "
                UPDATE krf_vehicle_travel_history 
                SET 
                    gatepass_id = {$gpId},
                    status = 'Dispatched',
                    dispatch_time = COALESCE(dispatch_time, NOW()),
                    verified_at = NOW(),
                    updated_at = NOW()
                WHERE ((psono = '{$safePso}' AND '{$safePso}' != '') 
                   OR (stockorder_no = '{$safeSo}' AND '{$safeSo}' != '')
                   OR (stockorder_no = '{$safePso}' AND '{$safePso}' != ''))
            ";
            $krfconn->query($updVthSql);
        }

        // 4. Update krf_vehicletrip:
        // Update gatepass_id, status 'Planned' -> 'Dispatched', dispatch_time to NOW()
        // Link via trip_id from krf_vehicle_travel_history OR via vehicleno on today's delivery
        $updVtSql = "
            UPDATE krf_vehicletrip vt
            SET 
                vt.gatepass_id = {$gpId},
                vt.status = 'Dispatched',
                vt.dispatch_time = COALESCE(vt.dispatch_time, NOW()),
                vt.verified_at = NOW(),
                vt.updated_at = NOW()
            WHERE (
                vt.gatepass_id = {$gpId}
                OR vt.trip_no IN (
                    SELECT vth_sub.trip_id 
                    FROM (
                        SELECT trip_id 
                        FROM krf_vehicle_travel_history 
                        WHERE ((psono = '{$safePso}' AND '{$safePso}' != '') 
                            OR (stockorder_no = '{$safeSo}' AND '{$safeSo}' != ''))
                          AND trip_id IS NOT NULL AND trip_id != ''
                    ) AS vth_sub
                )
                OR (
                    '{$safeVeh}' != '' AND '{$safeVeh}' != '-' AND '{$safeVeh}' != 'No Vehicle'
                    AND UPPER(REPLACE(REPLACE(vt.vehicle_no, ' ', ''), '-', '')) = UPPER(REPLACE(REPLACE('{$safeVeh}', ' ', ''), '-', ''))
                    AND DATE(COALESCE(vt.delivery_date, vt.created_at)) = CURDATE()
                )
            )
        ";
        $krfconn->query($updVtSql);

        // 5. Update krf_deliveryaddress_location:
        // Update delivery_status to 'Dispatched', dispatch_datetime to NOW()
        if (!empty($psono) || !empty($sono)) {
            $updDlSql = "
                UPDATE krf_deliveryaddress_location 
                SET 
                    delivery_status = 'Dispatched',
                    status = 'Dispatched',
                    dispatch_datetime = NOW(),
                    updated_at = NOW()
                WHERE ((psono = '{$safePso}' AND '{$safePso}' != '') 
                   OR (sono = '{$safeSo}' AND '{$safeSo}' != ''))
            ";
            $krfconn->query($updDlSql);
        }

        // 6. Ensure sales_invoice & sales_invoice_items status is 'dispatched'
        if (!empty($psono) || !empty($sono)) {
            $krfconn->query("
                UPDATE sales_invoice 
                SET status = 'dispatched', updated_at = NOW() 
                WHERE ((psono = '{$safePso}' AND '{$safePso}' != '') OR (sono = '{$safeSo}' AND '{$safeSo}' != ''))
                  AND status != 'cancelled'
            ");
            $krfconn->query("
                UPDATE sales_invoice_items 
                SET status = 'dispatched', updated_at = NOW() 
                WHERE ((psono = '{$safePso}' AND '{$safePso}' != '') OR (sono = '{$safeSo}' AND '{$safeSo}' != ''))
                  AND status != 'cancelled'
            ");
        }

        $krfconn->commit();

        $dispatchTimeFormatted = date('d-M-Y h:i A');

        outJson([
            'status'        => 'success',
            'message'       => "Gate Pass {$gpNo} successfully marked as Gate Passed & Verified!",
            'gatepass_id'   => $gpId,
            'verified_at'   => $dispatchTimeFormatted,
            'dispatch_time' => $dispatchTimeFormatted,
            'trip_status'   => 'Dispatched',
            'verified_by'   => $loginname
        ]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        outJson(['status' => 'error', 'message' => 'Failed to process gate exit: ' . $e->getMessage()]);
    }
}

// -------------------------------------------------------------
// 8. ACTION: UPDATE GATE PASS TRANSPORT & FREIGHT DETAILS
// -------------------------------------------------------------
if ($action === 'update_gate_details') {
    $gpNo            = trim($_POST['gatepass_no'] ?? '');
    $vehicleno       = trim($_POST['vehicleno'] ?? '');
    $transporterName = trim($_POST['transporter_name'] ?? '');
    $drivername      = trim($_POST['drivername'] ?? '');
    $drivermobile    = trim($_POST['drivermobile'] ?? '');
    $freightCharges  = (float)($_POST['freight_charges'] ?? 0);
    $remarks         = trim($_POST['remarks'] ?? '');

    if (empty($gpNo)) {
        outJson(['status' => 'error', 'message' => 'Gate Pass Number is required.']);
    }

    $safeGp   = mysqli_real_escape_string($krfconn, $gpNo);
    $safeV    = mysqli_real_escape_string($krfconn, $vehicleno);
    $safeT    = mysqli_real_escape_string($krfconn, $transporterName);
    $safeD    = mysqli_real_escape_string($krfconn, $drivername);
    $safeM    = mysqli_real_escape_string($krfconn, $drivermobile);
    $safeR    = mysqli_real_escape_string($krfconn, $remarks);

    $sql = "
        UPDATE krf_gatepass_tracking 
        SET 
            vehicleno = '{$safeV}',
            transporter_name = '{$safeT}',
            drivername = '{$safeD}',
            drivermobile = '{$safeM}',
            freight_charges = {$freightCharges},
            remarks = '{$safeR}',
            updated_at = NOW()
        WHERE gatepass_no = '{$safeGp}'
    ";
    if ($krfconn->query($sql)) {
        outJson(['status' => 'success', 'message' => "Gate Pass {$gpNo} details updated successfully!"]);
    } else {
        outJson(['status' => 'error', 'message' => 'Failed to update details: ' . $krfconn->error]);
    }
}

// -------------------------------------------------------------
// 9. ACTION: GET RECENT SCANS / GATE PASSES
// -------------------------------------------------------------
if ($action === 'get_recent_scans') {
    $sql = "
        SELECT 
            id, gatepass_no, psono, sono, invoiceno, invoice_date, clientname, clientphone, vehicleno, 
            transporter_name, drivername, total_trays, total_plants, freight_charges,
            gate_status, gate_verified_at, gate_verified_by, filepath, created_at
        FROM krf_gatepass_tracking 
        WHERE gate_status = 'passed_gate'
        ORDER BY gate_verified_at DESC, id DESC 
        LIMIT 25
    ";
    $res = $krfconn->query($sql);
    $list = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $r['created_at_fmt'] = date('d-M-Y h:i A', strtotime($r['created_at']));
            $r['verified_at_fmt'] = !empty($r['gate_verified_at']) ? date('d-M-Y h:i A', strtotime($r['gate_verified_at'])) : '-';
            $list[] = $r;
        }
    }
    outJson(['status' => 'success', 'list' => $list]);
}

if (!empty($action)) {
    outJson(['status' => 'error', 'message' => 'Invalid action specified.']);
}

