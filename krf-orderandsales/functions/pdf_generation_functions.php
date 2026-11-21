<?php
if (!function_exists('getIndianCurrencyInWords')) {
    function getIndianCurrencyInWords(float $number): string
    {
        $decimal = round($number - ($no = floor($number)), 2) * 100;
        $no = (int)$no;
        $point = (int)$decimal;
        $words = array(
            0 => '', 1 => 'One', 2 => 'Two',
            3 => 'Three', 4 => 'Four', 5 => 'Five', 6 => 'Six',
            7 => 'Seven', 8 => 'Eight', 9 => 'Nine',
            10 => 'Ten', 11 => 'Eleven', 12 => 'Twelve',
            13 => 'Thirteen', 14 => 'Fourteen', 15 => 'Fifteen',
            16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
            19 => 'Nineteen', 20 => 'Twenty', 30 => 'Thirty',
            40 => 'Forty', 50 => 'Fifty', 60 => 'Sixty',
            70 => 'Seventy', 80 => 'Eighty', 90 => 'Ninety'
        );

        if ($no === 0 && $point === 0) {
            return "Indian Rupees Zero Only";
        }

        $crore = floor($no / 10000000);
        $no -= $crore * 10000000;
        $lakh = floor($no / 100000);
        $no -= $lakh * 100000;
        $thousand = floor($no / 1000);
        $no -= $thousand * 1000;
        $hundred = floor($no / 100);
        $no -= $hundred * 100;
        $tens = (int)$no;

        $convert2Digits = function($num) use ($words) {
            if ($num == 0) return '';
            if ($num < 20) return $words[$num];
            $t = floor($num / 10) * 10;
            $u = $num % 10;
            return trim($words[$t] . ' ' . $words[$u]);
        };

        $result = '';
        if ($crore > 0) $result .= $convert2Digits($crore) . ' Crore ';
        if ($lakh > 0) $result .= $convert2Digits($lakh) . ' Lakh ';
        if ($thousand > 0) $result .= $convert2Digits($thousand) . ' Thousand ';
        if ($hundred > 0) $result .= $convert2Digits($hundred) . ' Hundred ';
        if ($tens > 0) {
            if (!empty($result)) $result .= 'and ';
            $result .= $convert2Digits($tens) . ' ';
        }

        $result = trim($result);
        $paiseStr = '';
        if ($point > 0) {
            $paiseStr = ' and ' . $convert2Digits($point) . ' Paise';
        }

        if (empty($result)) {
            return "Indian Rupees " . trim($paiseStr) . " Only";
        }
        return "Indian Rupees " . $result . $paiseStr . " Only";
    }
}

if (!function_exists('resolvePdfCreatedByName')) {
    function resolvePdfCreatedByName($authconn, $krfconn, $params = [], $order = null, $invoiceno = '', $psono = '', $sono = '') {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $targetPsono = !empty($psono) ? trim($psono) : trim($order['psono'] ?? ($params['psono'] ?? ''));
        $targetSono = !empty($sono) ? trim($sono) : trim($order['sono'] ?? ($params['sono'] ?? ''));

        // Helper to resolve a user ID/mobile/loginid to full login name from login_user_master
        $resolveFromMaster = function($val) use ($authconn) {
            $val = trim((string)$val);
            if (empty($val) || strtoupper($val) === 'SYSTEM' || strtoupper($val) === 'N/A') {
                return '';
            }

            // If it's a numeric ID or mobile number (or user code)
            if (is_numeric($val) || preg_match('/^\d+$/', $val)) {
                $stmt = $authconn->prepare("SELECT loginname FROM login_user_master WHERE (tedit IS NULL OR tedit = '') AND (loginid = ? OR mobileno = ? OR sno = ?) ORDER BY (CASE WHEN loginid = ? THEN 0 ELSE 1 END), sno DESC LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("ssss", $val, $val, $val, $val);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $stmt->close();
                        if (!empty($row['loginname']) && strtoupper(trim($row['loginname'])) !== 'SYSTEM') {
                            return trim($row['loginname']);
                        }
                    } else {
                        $stmt->close();
                    }
                }
            } else {
                // It's a text string: check if in user master for proper display or return as-is
                $stmt = $authconn->prepare("SELECT loginname FROM login_user_master WHERE (tedit IS NULL OR tedit = '') AND (loginname = ? OR loginid = ?) LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("ss", $val, $val);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($row = $res->fetch_assoc()) {
                        $stmt->close();
                        if (!empty($row['loginname'])) {
                            return trim($row['loginname']);
                        }
                    } else {
                        $stmt->close();
                    }
                }
                if (strlen($val) >= 2 && !is_numeric($val)) {
                    return $val;
                }
            }
            return '';
        };

        // 1. If order array has already-resolved created_by_name or loginname
        if (is_array($order)) {
            if (!empty($order['created_by_name']) && strtoupper(trim($order['created_by_name'])) !== 'SYSTEM') {
                $name = $resolveFromMaster($order['created_by_name']);
                if (!empty($name)) return $name;
            }
            if (!empty($order['loginname']) && strtoupper(trim($order['loginname'])) !== 'SYSTEM') {
                $name = $resolveFromMaster($order['loginname']);
                if (!empty($name)) return $name;
            }
        }

        // 2. Resolve from krf_provisional_sales_order if psono is available
        if (!empty($targetPsono)) {
            $stmtP = $krfconn->prepare("SELECT created_by, updated_by FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
            if ($stmtP) {
                $stmtP->bind_param("s", $targetPsono);
                $stmtP->execute();
                $pRow = $stmtP->get_result()->fetch_assoc();
                $stmtP->close();
                if ($pRow) {
                    $cb = trim((string)($pRow['created_by'] ?? ''));
                    if (!empty($cb)) {
                        $name = $resolveFromMaster($cb);
                        if (!empty($name)) return $name;
                    }
                    $ub = trim((string)($pRow['updated_by'] ?? ''));
                    if (!empty($ub)) {
                        $name = $resolveFromMaster($ub);
                        if (!empty($name)) return $name;
                    }
                }
            }
        }

        // 3. Resolve from krf_sales_order if sono is available
        if (!empty($targetSono)) {
            $stmtS = $krfconn->prepare("SELECT psono, created_by, updated_by FROM krf_sales_order WHERE sono = ? LIMIT 1");
            if ($stmtS) {
                $stmtS->bind_param("s", $targetSono);
                $stmtS->execute();
                $sRow = $stmtS->get_result()->fetch_assoc();
                $stmtS->close();
                if ($sRow) {
                    // If it links to a psono, check provisional order creator first (salesperson who booked order)
                    $linkedPsono = trim((string)($sRow['psono'] ?? ''));
                    if (!empty($linkedPsono) && $linkedPsono !== $targetPsono) {
                        $stmtP2 = $krfconn->prepare("SELECT created_by FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
                        if ($stmtP2) {
                            $stmtP2->bind_param("s", $linkedPsono);
                            $stmtP2->execute();
                            $p2Row = $stmtP2->get_result()->fetch_assoc();
                            $stmtP2->close();
                            if ($p2Row && !empty($p2Row['created_by'])) {
                                $name = $resolveFromMaster($p2Row['created_by']);
                                if (!empty($name)) return $name;
                            }
                        }
                    }
                    $cb = trim((string)($sRow['created_by'] ?? ''));
                    if (!empty($cb)) {
                        $name = $resolveFromMaster($cb);
                        if (!empty($name)) return $name;
                    }
                }
            }
        }

        // 4. Check order's direct created_by / updated_by / ammendedby
        if (is_array($order)) {
            foreach ([$order['created_by'] ?? '', $order['updated_by'] ?? '', $order['ammendedby'] ?? ''] as $candidate) {
                $candidate = trim((string)$candidate);
                if (!empty($candidate) && strtoupper($candidate) !== 'SYSTEM') {
                    $name = $resolveFromMaster($candidate);
                    if (!empty($name)) return $name;
                }
            }
        }

        // 5. Fallback to line items (krf_provisional_sales_order_items or krf_sales_order_items)
        if (!empty($targetPsono)) {
            $stmtItem = $krfconn->prepare("SELECT created_by FROM krf_provisional_sales_order_items WHERE psono = ? AND created_by != '' ORDER BY id ASC LIMIT 1");
            if ($stmtItem) {
                $stmtItem->bind_param("s", $targetPsono);
                $stmtItem->execute();
                $iRow = $stmtItem->get_result()->fetch_assoc();
                $stmtItem->close();
                if ($iRow && !empty($iRow['created_by'])) {
                    $name = $resolveFromMaster($iRow['created_by']);
                    if (!empty($name)) return $name;
                }
            }
        }

        if (!empty($targetSono)) {
            $stmtItem2 = $krfconn->prepare("SELECT created_by FROM krf_sales_order_items WHERE sono = ? AND created_by != '' ORDER BY id ASC LIMIT 1");
            if ($stmtItem2) {
                $stmtItem2->bind_param("s", $targetSono);
                $stmtItem2->execute();
                $iRow2 = $stmtItem2->get_result()->fetch_assoc();
                $stmtItem2->close();
                if ($iRow2 && !empty($iRow2['created_by'])) {
                    $name = $resolveFromMaster($iRow2['created_by']);
                    if (!empty($name)) return $name;
                }
            }
        }

        // 6. Explicit params
        if (!empty($params['created_by'])) {
            $name = $resolveFromMaster($params['created_by']);
            if (!empty($name)) return $name;
        }
        if (!empty($params['loginname'])) {
            $name = $resolveFromMaster($params['loginname']);
            if (!empty($name)) return $name;
        }

        // 7. Last fallback: Logged-in session user ONLY if order has no creator in database
        $sessCandidates = [
            $_SESSION['loginname'] ?? '',
            $_SESSION['username'] ?? '',
            $_SESSION['name'] ?? ''
        ];
        foreach ($sessCandidates as $sc) {
            $sc = trim((string)$sc);
            if (!empty($sc) && strtoupper($sc) !== 'SYSTEM') {
                return $sc;
            }
        }

        return 'SYSTEM';
    }
}


// Function to generate Provisional Sales Order PDF
// $mode: 'D' (Download to browser), 'F' (Save to file), 'S' (Return as string)
function createProvisionalOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $psono, $mode = 'D', $save_path = '')
{
    $logFile = __DIR__ . '/../debug_email_pdf.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - generating PDF for psono: $psono\n", FILE_APPEND);
    // --- Fetch Data ---
    $cmpinfo = getcompanydetailswithlogin($conn, "companyid", $companyid);
    $companyname = $cmpinfo[0]['companyname'] ?? "Karjee Farms";

    $order = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono)[0] ?? null;
    if (!isset($order) || !$order) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: Order not found for psono: $psono\n", FILE_APPEND);
        if ($mode == 'S')
            return "Error: Order not found.";
        return false;
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Order found. Client: " . ($order['clientname'] ?? 'Unknown') . "\n", FILE_APPEND);

    $rawItems = getProvisionalSalesOrderItems($krfconn, $psono);
    $items = [];
    foreach ($rawItems as $it) {
        $st = strtolower(trim($it['status'] ?? ''));
        $q = floatval($it['quantity'] ?? 0);
        $tedit = trim($it['tedit'] ?? '');
        if ($st === 'cancelled' || $st === 'cancel' || $q <= 0 || !empty($tedit)) {
            continue;
        }
        $items[] = $it;
    }
    $paymentrcv = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $psono);

    // --- Fetch Freight ---
    $ttlfreight = 0;
    $fRes = $krfconn->query("SELECT payable_freightamount FROM krf_freightcharges WHERE psono = '" . $krfconn->real_escape_string($psono) . "' LIMIT 1");
    if ($fRes && $fRow = $fRes->fetch_assoc()) {
        $ttlfreight = (float) $fRow['payable_freightamount'];
    }

    
    

    $ttlamount = 0;
    $ttldiscount = 0;
    foreach ($items as $item) {
        $ttlamount += (float) ($item['amount'] ?? 0);
        $ttldiscount += (float) ($item['discount_amount'] ?? 0);
    }
    
    // Add freight to total
    $ttlamount += $ttlfreight;

    $ttlrcv = 0;
    $cashReceived = 0.0;
    $chequeReceived = 0.0;
    $upiBankReceived = 0.0;
    $totalOtherDiscount = 0.0;
    foreach ($paymentrcv as $p) {
        $pAmt = (float) ($p['advanceamount'] ?? 0);
        $pDisc = (float) ($p['otherdiscount'] ?? 0);
        $totalOtherDiscount += $pDisc;
        $pSrc = strtolower(trim($p['sourceofpayment'] ?? ''));
        if ($pSrc === 'cash') {
            $cashReceived += $pAmt;
        } elseif (in_array($pSrc, ['cheque', 'check'])) {
            $chequeReceived += $pAmt;
        } else {
            $upiBankReceived += $pAmt;
        }
        $ttlrcv += $pAmt;
    }
    $balance = max(0.00, floatval($ttlamount) - floatval($totalOtherDiscount) - floatval($ttlrcv));

    $bankDetails = [
        "Bank Name" => "HDFC",
        "Account No" => "50200092846879",
        "IFSC Code" => "HDFC0000917",
        "Branch" => "AMBIKAPUR",
        "UPI ID:" => "8319620998-6@ybl",
    ];

    // --- Init PDF Cache ---
    if (!defined('K_PATH_CACHE')) {
        $cacheDir = realpath(__DIR__ . '/../modal') . DIRECTORY_SEPARATOR . 'tcpdf_cache' . DIRECTORY_SEPARATOR;
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        // Fallback to system temp if not writable
        if (!is_writable($cacheDir)) {
            $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcpdf_cache' . DIRECTORY_SEPARATOR;
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }
        }
        define('K_PATH_CACHE', $cacheDir);
    }

    // require_once needs to be relative to this file or absolute.
    if (!class_exists('TCPDF')) {
        require_once(__DIR__ . '/../pdfroutines/tcpdf/tcpdf.php');
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - TCPDF loaded.\n", FILE_APPEND);

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(12, 5, 12);
    $pdf->SetAutoPageBreak(true, 5);
    $pdf->AddPage();

    // --- Header: GSTIN on top ---
    $pdf->SetFont('helvetica', 'B', 8.5);
    $pdf->Cell(0, 4.5, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
    $pdf->Ln(1);

    // --- LOGO + COMPANY NAME ---
    $logoPath = __DIR__ . "/../logo/logo.png";
    $hasDependencies = extension_loaded('gd') || extension_loaded('imagick');

    if (file_exists($logoPath) && $hasDependencies) {
        $pdf->Image($logoPath, 12, $pdf->GetY(), 24, 0, '', '', '', false, 300, '', false, false, 0);
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Info: Logo skipped (missing GD/Imagick or file not found).\n", FILE_APPEND);
    }

    $pdf->SetY($pdf->GetY());
    $pdf->SetX(45);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 7, strtoupper($companyname), 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 9);
    $cmpInfo = "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\n" .
        "Mobile No: 6261832409, 7879615628";

    $pdf->SetX(90);
    $pdf->MultiCell(108, 4, $cmpInfo, 0, 'R', 0, 1);

    $pdf->Ln(4);

    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 6, 'Provisional Sales Order', 0, 1, 'C');
    $pdf->Ln(3);

    // --- Dynamic Invoice No & Invoice Date Resolution ---
    $invoiceNo = trim($_GET['invoiceno'] ?? '');
    if (empty($invoiceNo)) {
        if (!empty($order['dinvoiceno'])) {
            $invoiceNo = $order['dinvoiceno'];
        } else if (function_exists('generateDispatchInvoiceNo')) {
            $invoiceNo = generateDispatchInvoiceNo($krfconn, $psono, '');
        } else if (function_exists('getNewDispatchInvoiceNumber')) {
            $invoiceNo = getNewDispatchInvoiceNumber($krfconn, $psono, '');
        } else {
            $fyStr = date('y') . '-' . (date('y') + 1);
            $cntStmt = $krfconn->query("SELECT COUNT(*) AS total_dinv FROM krf_sales_order WHERE invoicetype = 'dispatchinvoice' OR (dinvoiceno IS NOT NULL AND dinvoiceno != '')");
            $cntRow = $cntStmt ? $cntStmt->fetch_assoc() : null;
            $dinvCount = ($cntRow ? intval($cntRow['total_dinv']) : 0) + 1;
            $invoiceNo = "KF/" . $fyStr . "/" . $dinvCount;
        }
    }

    $reqInvoiceDate = trim($_GET['invoicedate'] ?? '');
    if (!empty($reqInvoiceDate)) {
        $invoiceDateFormatted = date('d-M-Y', strtotime($reqInvoiceDate));
    } else {
        $invoiceDateFormatted = date('d-M-Y');
    }

    // --- ORDER & CUSTOMER INFO ---
    $pdf->SetFont('freesans', '', 9);

    // Fetch Invoicing Address from krf_client_master (NOT delivery address)
    $clientMasterInvAddr = '';
    $clientIdVal = $order['clientid'] ?? '';
    if (!empty($clientIdVal)) {
        $cmStmt = $krfconn->prepare("SELECT invoicing_address, statename, districtname, cityname FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($cmStmt) {
            $cmStmt->bind_param("s", $clientIdVal);
            $cmStmt->execute();
            $cmRow = $cmStmt->get_result()->fetch_assoc();
            $cmStmt->close();
            if ($cmRow) {
                $clientMasterInvAddr = trim($cmRow['invoicing_address'] ?? '');
                if (empty($clientMasterInvAddr)) {
                    $parts = array_filter([$cmRow['cityname'] ?? '', $cmRow['districtname'] ?? '', $cmRow['statename'] ?? '']);
                    $clientMasterInvAddr = implode(', ', $parts);
                }
            }
        }
    }
    if (empty($clientMasterInvAddr)) {
        $clientMasterInvAddr = trim($order['address'] ?? '');
    }
    $invoiceAddress = $clientMasterInvAddr;
    // Convert tabs and other control characters to normal spaces
    $invoiceAddress = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $invoiceAddress);
    // Remove duplicate spaces
    $invoiceAddress = preg_replace('/\s+/u', ' ', $invoiceAddress);

    if ($invoiceAddress === '') {
        $invoiceAddress = 'N/A';
    }

    $customerInfo =
        "Customer: " . ($order['clientname'] ?? 'N/A') . "\n" .
        "Email: " . (!empty($order['emailid']) ? $order['emailid'] : 'N/A') . "\n" .
        "Mobile: " . (!empty($order['mobileno']) ? $order['mobileno'] : 'N/A') . "\n" .
        "Invoice Address: " . $invoiceAddress;

    $orderInfo =
        "Order No: " . ($order['psono'] ?? 'N/A') . "\n" .
        "Order Date: " .
            (!empty($order['created_at'])
                ? date('d-M-Y', strtotime($order['created_at']))
                : 'N/A') . "\n" .
        "Created By: " . strtoupper($created_by);

    // A4 usable width with 12 mm margins = 186 mm
    $startX = 12;
    $startY = $pdf->GetY();
    $columnWidth = 90;
    $columnGap = 6;

    // Customer information (Left Aligned)
    $pdf->SetXY($startX, $startY);
    $pdf->MultiCell(
        $columnWidth,
        4.2,
        $customerInfo,
        0,
        'L',
        false,
        1
    );

    $customerEndY = $pdf->GetY();

    // Invoice & Order information (Right Aligned to match header and table right border)
    $pdf->SetXY($startX + $columnWidth + $columnGap, $startY);
    $pdf->MultiCell(
        $columnWidth,
        4.2,
        $orderInfo,
        0,
        'R',
        false,
        1
    );

    $orderEndY = $pdf->GetY();

    // Continue below whichever column is taller
    $pdf->SetY(max($customerEndY, $orderEndY) + 4);
    $pdf->SetX($startX);

    // --- ITEMS TABLE ---
    $pdf->SetFont('freesans', '', 8.5);
    $w = [8, 52, 20, 20, 20, 38, 28]; // total = 186 mm

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell($w[0], 6, '#', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 6, 'Product', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 6, 'Qty', 1, 0, 'C', 1);
    $pdf->Cell($w[3], 6, 'Rate', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 6, 'Discount', 1, 0, 'C', 1);
    $pdf->Cell($w[5], 6, 'Est. Delivery Date', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 6, 'Amount', 1, 1, 'C', 1);
    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $count = 1;
    $totalProductQty = 0;
    $totalProductAmount = 0;
    $itmsuffix=" Pcs";
    foreach ($items as $item) {

        $producttypeid = $krfconn->query("SELECT ProductTypeID FROM karjeefarm.product_master WHERE ProductID = '".$krfconn->real_escape_string($item['product_id'])."' LIMIT 1")->fetch_assoc()['ProductTypeID'] ?? '';
        if($producttypeid=="46"){
            $itmsuffix = " Tray";
        }

        $st = strtolower(trim($item['status'] ?? ''));
        $pQty = (float)($item['quantity'] ?? 0);
        $tedit = trim($item['tedit'] ?? '');
        if ($st === 'cancelled' || $st === 'cancel' || $pQty <= 0 || !empty($tedit)) {
            continue;
        }
        $pRate = (float)($item['rate'] ?? 0);
        $pDisc = (float)($item['discount_amount'] ?? 0);
        $pAmt = (float)($item['amount'] ?? ($pQty * $pRate - $pDisc));

        $totalProductQty += $pQty;
        $totalProductAmount += $pAmt;

        $qtyStr = $pQty . $itmsuffix;
        $delDateStr = !empty($item['expecteddeliverydate']) ? date('d-M-Y', strtotime($item['expecteddeliverydate'])) : 'N/A';
        $rateStr = number_format($pRate, 2);
        $discStr = number_format($pDisc, 2);
        $amtStr = number_format($pAmt, 2);

        $cellHeights = [];
        $cellHeights[] = $pdf->getNumLines($count, $w[0]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($item['product_name'], $w[1]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($qtyStr, $w[2]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($rateStr, $w[3]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($discStr, $w[4]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($delDateStr, $w[5]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($amtStr, $w[6]) * 4.8;
        $maxHeight = max(5.5, max($cellHeights));

        $pdf->MultiCell($w[0], $maxHeight, $count, 1, 'C', 0, 0);
        $pdf->MultiCell($w[1], $maxHeight, $item['product_name'], 1, 'L', 0, 0);
        $pdf->MultiCell($w[2], $maxHeight, $qtyStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[3], $maxHeight, $rateStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[4], $maxHeight, $discStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[5], $maxHeight, $delDateStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[6], $maxHeight, $amtStr, 1, 'C', 0, 1);
        $count++;
    }

    // --- Total Row for Products ---
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell($w[0] + $w[1], 5.5, 'Total', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 5.5, $totalProductQty . $itmsuffix, 1, 0, 'C', 1);
    $pdf->Cell($w[3], 5.5, '', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 5.5, ($ttldiscount > 0 ? number_format($ttldiscount, 2) : '0.00'), 1, 0, 'C', 1);
    $pdf->Cell($w[5], 5.5, '', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 5.5, number_format($totalProductAmount, 2), 1, 1, 'C', 1);

    // --- FREIGHT DETAILS (TABULAR FORMAT) ---
    $productDateMap = [];
    foreach ($items as $idx => $it) {
        $pId = (string)($it['product_id'] ?? '');
        $dDate = !empty($it['expecteddeliverydate']) ? date('Y-m-d', strtotime($it['expecteddeliverydate'])) : '';
        if ($pId !== '') {
            $productDateMap[$pId] = $dDate;
        }
        $productDateMap['idx_' . $idx] = $dDate;
    }

    $deliveryLocs = [];
    $transportType = 'Farm Delivery';
    $rawPickupType = 'delivered';
    $rawTransportType = '';
    $stmtLoc = $krfconn->prepare("SELECT deliveryaddress, dest_city, dest_district, dest_state, totaldistance, freedistance, distance, remark, deliverycharges, freightamt, totalamount, transporttype, pickuptype, productid, delivery_datetime, noofbox, boxperpack, chargesperbox, vehicleno, '' as vehiclename, '' as vehicleownername, drivername, drivercontactno, '' as trip_id FROM krf_deliveryaddress_location WHERE psono = ? AND (tedit IS NULL OR tedit = '') ORDER BY id ASC");
    if ($stmtLoc) {
        $stmtLoc->bind_param("s", $psono);
        $stmtLoc->execute();
        $resLoc = $stmtLoc->get_result();
        while ($lRow = $resLoc->fetch_assoc()) {
            $deliveryLocs[] = $lRow;
            if (!empty($lRow['pickuptype'])) {
                $rawPickupType = trim($lRow['pickuptype']);
            }
            if (!empty($lRow['transporttype'])) {
                $rawTransportType = trim($lRow['transporttype']);
            }
        }
        $stmtLoc->close();
    }

    if ($rawPickupType === 'boxpacking') {
        $boxMode = !empty($rawTransportType) ? $rawTransportType : 'Bus';
        $transportType = 'Box Packing (' . $boxMode . ')';
    } else if ($rawPickupType === 'pickup' || $rawPickupType === 'client_pickup' || $rawPickupType === 'pickupbyclient') {
        $transportType = 'Self Pickup By Farmer/Client';
    } else {
        $nonVehicles = ['Bus', 'Train', 'Courier', 'Air', 'Railway', 'Airport', 'Transport', 'Company Delivery', 'Pickup Self'];
        if (!empty($rawTransportType) && !in_array($rawTransportType, $nonVehicles)) {
            $transportType = 'Farm Delivery (' . $rawTransportType . ')';
        } else {
            $transportType = 'Farm Delivery';
        }
    }

    // Determine destination address validity and group by (address + delivery date)
    $hasValidDeliveryAddress = false;
    $destAddr = '';
    $dist = 0;

    $groupedLocs = [];
    if (!empty($deliveryLocs)) {
        foreach ($deliveryLocs as $lIdx => $loc) {
            $lAddr = trim($loc['deliveryaddress'] ?? '');
            if (empty($lAddr)) {
                $parts = array_filter([$loc['dest_city'] ?? '', $loc['dest_district'] ?? '', $loc['dest_state'] ?? '']);
                $lAddr = implode(', ', $parts);
            }
            if (empty($destAddr) && !empty($lAddr)) {
                $destAddr = $lAddr;
                $dist = (float)($loc['distance'] ?? 0);
            }
            $cleanKey = strtoupper(trim(preg_replace('/[\s,.-]+/', '', $lAddr)));
            if ($cleanKey === '' || $cleanKey === 'NA' || $cleanKey === 'N/A' || $cleanKey === 'NULL') {
                continue;
            }

            $pId = (string)($loc['productid'] ?? '');
            $locDate = $productDateMap[$pId] ?? ($productDateMap['idx_' . $lIdx] ?? '');
            if (empty($locDate) && !empty($loc['delivery_datetime'])) {
                $locDate = date('Y-m-d', strtotime($loc['delivery_datetime']));
            }
            $groupKey = $cleanKey . '___' . $locDate;

            $rowTotalDist = (float)($loc['totaldistance'] ?? 0);
            $rowFreeDist = (float)($loc['freedistance'] ?? 0);
            $rowPayableDist = (float)($loc['distance'] ?? 0);
            $rowRemark = trim($loc['remark'] ?? '');

            if ($rowTotalDist <= 0 && $rowPayableDist > 0) {
                $rowTotalDist = $rowPayableDist + $rowFreeDist;
            }

            if (!isset($groupedLocs[$groupKey])) {
                $groupedLocs[$groupKey] = [
                    'address' => $lAddr,
                    'totaldistance' => $rowTotalDist,
                    'freedistance' => $rowFreeDist,
                    'distance' => $rowPayableDist,
                    'remark' => $rowRemark,
                    'freightamt' => (float)($loc['freightamt'] ?? 0),
                    'deliverycharges' => (float)($loc['deliverycharges'] ?? 0),
                    'totalamount' => (float)($loc['totalamount'] ?? 0),
                    'transporttype' => trim($loc['transporttype'] ?? ''),
                    'pickuptype' => trim($loc['pickuptype'] ?? $rawPickupType),
                    'noofbox' => (int)($loc['noofbox'] ?? 0),
                    'boxperpack' => (int)($loc['boxperpack'] ?? 0),
                    'chargesperbox' => (float)($loc['chargesperbox'] ?? 0),
                    'vehicleno' => trim($loc['vehicleno'] ?? ''),
                    'vehiclename' => trim($loc['vehiclename'] ?? ''),
                    'vehicleownername' => trim($loc['vehicleownername'] ?? ''),
                    'drivername' => trim($loc['drivername'] ?? ''),
                    'drivercontactno' => trim($loc['drivercontactno'] ?? ''),
                    'trip_id' => trim($loc['trip_id'] ?? ''),
                    'deliverydate' => $locDate
                ];
            } else {
                $groupedLocs[$groupKey]['freightamt'] += (float)($loc['freightamt'] ?? 0);
                $groupedLocs[$groupKey]['deliverycharges'] += (float)($loc['deliverycharges'] ?? 0);
                $groupedLocs[$groupKey]['totalamount'] += (float)($loc['totalamount'] ?? 0);
                if ($groupedLocs[$groupKey]['totaldistance'] <= 0 && $rowTotalDist > 0) {
                    $groupedLocs[$groupKey]['totaldistance'] = $rowTotalDist;
                }
                if ($groupedLocs[$groupKey]['freedistance'] <= 0 && $rowFreeDist > 0) {
                    $groupedLocs[$groupKey]['freedistance'] = $rowFreeDist;
                }
                if ($groupedLocs[$groupKey]['distance'] <= 0 && $rowPayableDist > 0) {
                    $groupedLocs[$groupKey]['distance'] = $rowPayableDist;
                }
                if (empty($groupedLocs[$groupKey]['remark']) && !empty($rowRemark)) {
                    $groupedLocs[$groupKey]['remark'] = $rowRemark;
                }
                if ($groupedLocs[$groupKey]['noofbox'] <= 0 && (int)($loc['noofbox'] ?? 0) > 0) {
                    $groupedLocs[$groupKey]['noofbox'] = (int)$loc['noofbox'];
                }
                if ($groupedLocs[$groupKey]['boxperpack'] <= 0 && (int)($loc['boxperpack'] ?? 0) > 0) {
                    $groupedLocs[$groupKey]['boxperpack'] = (int)$loc['boxperpack'];
                }
                if ($groupedLocs[$groupKey]['chargesperbox'] <= 0 && (float)($loc['chargesperbox'] ?? 0) > 0) {
                    $groupedLocs[$groupKey]['chargesperbox'] = (float)$loc['chargesperbox'];
                }
                if (empty($groupedLocs[$groupKey]['vehicleno']) && !empty($loc['vehicleno'])) {
                    $groupedLocs[$groupKey]['vehicleno'] = trim($loc['vehicleno']);
                }
                if (empty($groupedLocs[$groupKey]['vehiclename']) && !empty($loc['vehiclename'])) {
                    $groupedLocs[$groupKey]['vehiclename'] = trim($loc['vehiclename']);
                }
                if (empty($groupedLocs[$groupKey]['vehicleownername']) && !empty($loc['vehicleownername'])) {
                    $groupedLocs[$groupKey]['vehicleownername'] = trim($loc['vehicleownername']);
                }
                if (empty($groupedLocs[$groupKey]['drivername']) && !empty($loc['drivername'])) {
                    $groupedLocs[$groupKey]['drivername'] = trim($loc['drivername']);
                }
                if (empty($groupedLocs[$groupKey]['drivercontactno']) && !empty($loc['drivercontactno'])) {
                    $groupedLocs[$groupKey]['drivercontactno'] = trim($loc['drivercontactno']);
                }
                if (empty($groupedLocs[$groupKey]['trip_id']) && !empty($loc['trip_id'])) {
                    $groupedLocs[$groupKey]['trip_id'] = trim($loc['trip_id']);
                }
            }
        }
    }

    $hasValidDeliveryAddress = !empty($groupedLocs);

    // Extra space before Freight Details section
    $pdf->Ln(3);

    // Determine if any freight discount rule is applied
    $appliedDiscountRule = '';
    $freightDiscountAmt = 0.0;
    $freightDiscountPct = 0.0;
    $freightFreeDist = 0.0;

    if (!empty($params['freight_rule'])) {
        $appliedDiscountRule = trim($params['freight_rule']);
    } elseif (!empty($params['freight_discount_plan'])) {
        $appliedDiscountRule = trim($params['freight_discount_plan']);
    }

    if (!empty($groupedLocs)) {
        foreach ($groupedLocs as $g) {
            $fd = floatval($g['freedistance'] ?? $g['free_distance'] ?? 0);
            $dp = floatval($g['discount_percent'] ?? $g['discPct'] ?? 0);
            $da = floatval($g['discount_amount'] ?? $g['discAmt'] ?? 0);
            $r = trim($g['remark'] ?? $g['remarks'] ?? '');

            if ($fd > $freightFreeDist) $freightFreeDist = $fd;
            if ($dp > $freightDiscountPct) $freightDiscountPct = $dp;
            if ($da > $freightDiscountAmt) $freightDiscountAmt = $da;

            if (empty($appliedDiscountRule)) {
                if ($dp > 0) {
                    $appliedDiscountRule = number_format($dp, 0) . "% Off" . ($da > 0 ? " (-Rs " . number_format($da, 2) . ")" : "");
                } elseif ($da > 0) {
                    $appliedDiscountRule = "Discount -Rs " . number_format($da, 2);
                } elseif ($fd > 0) {
                    $appliedDiscountRule = "Free Distance: " . number_format($fd, 2) . " KM";
                } elseif (!empty($r) && (stripos($r, 'discount') !== false || stripos($r, 'free freight') !== false || stripos($r, '% off') !== false) && stripos($r, '@@@') === false && stripos($r, 'Trip 1: Amt Rs') === false) {
                    $appliedDiscountRule = $r;
                }
            }
        }
    }

    if (empty($appliedDiscountRule) && (!empty($sono) || !empty($psono))) {
        $chkFc = $krfconn->prepare("SELECT remarks, freight_discountpercent, freight_discountamount FROM krf_freightcharges WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
        if ($chkFc) {
            $chkFc->bind_param("ssss", $psono, $psono, $sono, $sono);
            $chkFc->execute();
            $fcRes = $chkFc->get_result()->fetch_assoc();
            $chkFc->close();
            if ($fcRes) {
                $fcRem = trim($fcRes['remarks'] ?? '');
                $fcPct = floatval($fcRes['freight_discountpercent'] ?? 0);
                $fcAmt = floatval($fcRes['freight_discountamount'] ?? 0);
                if ($fcPct > $freightDiscountPct) $freightDiscountPct = $fcPct;
                if ($fcAmt > $freightDiscountAmt) $freightDiscountAmt = $fcAmt;

                if ($fcPct > 0) {
                    $appliedDiscountRule = number_format($fcPct, 0) . "% Off" . ($fcAmt > 0 ? " (-Rs " . number_format($fcAmt, 2) . ")" : "");
                } elseif ($fcAmt > 0) {
                    $appliedDiscountRule = "Discount -Rs " . number_format($fcAmt, 2);
                } elseif (!empty($fcRem) && (stripos($fcRem, 'discount') !== false || stripos($fcRem, 'free freight') !== false || stripos($fcRem, '% off') !== false) && stripos($fcRem, '@@@') === false && stripos($fcRem, 'Trip 1: Amt Rs') === false) {
                    $appliedDiscountRule = $fcRem;
                }
            }
        }
    }

    $hasRealFreightDiscount = !empty($appliedDiscountRule) && ($freightDiscountAmt > 0 || $freightDiscountPct > 0 || $freightFreeDist > 0 || !empty($params['freight_rule']) || !empty($params['freight_discount_plan']));

    // Section Header Row: Freight Details and Transport Type (no discount text in this top row!)
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(242, 242, 242);
    $headerTitle = ' Freight Details  |  Transport Type: ' . $transportType;
    $pdf->Cell(186, 5.5, $headerTitle, 1, 1, 'L', 1);

    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $totalFreightRendered = 0;
    if (!$hasValidDeliveryAddress) {
        // Missing delivery address -> Advisory notice with 0.00 amount
        $missingNotice = "Freight charges will be applicable as per the distance to which the plants are to be delivered. Location to be provided by the farmer.";
        $fHeight = max($pdf->getNumLines($missingNotice, 158) * 4.2, 5.5);
        $pdf->MultiCell(158, $fHeight, $missingNotice, 1, 'L', 0, 0);
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->MultiCell(28, $fHeight, '0.00', 1, 'C', 0, 1);
        $pdf->SetFont('freesans', '', 8);
    } else {
        $hasFreightDiscount = !empty($appliedDiscountRule);
        $grpCount = count($groupedLocs);
        foreach ($groupedLocs as $grp) {
            $lAddr = $grp['address'];
            $lTotalDist = (float)($grp['totaldistance'] ?? 0);
            $lFreeDist = (float)($grp['freedistance'] ?? 0);
            $lPayableDist = (float)($grp['distance'] ?? 0);
            $lRemark = trim($grp['remark'] ?? '');
            $grpPickupType = trim($grp['pickuptype'] ?? $rawPickupType);

            if ($grpPickupType === 'pickup' || $grpPickupType === 'client_pickup' || $grpPickupType === 'pickupbyclient') {
                $routeText = "Self Pickup (Ex-Nursery / Farm Gate)";
            } else if ($grpPickupType === 'boxpacking') {
                $boxPerPack = (int)($grp['boxperpack'] ?? 0);
                if ($boxPerPack <= 0) $boxPerPack = 500;
                $chargesPerBox = (float)($grp['chargesperbox'] ?? 0);
                if ($chargesPerBox <= 0) $chargesPerBox = 70.00;
                $boxCount = (int)($grp['noofbox'] ?? 0);
                if ($boxCount <= 0 && $totalProductQty > 0) {
                    $boxCount = ceil($totalProductQty / $boxPerPack);
                }
                if ($boxCount <= 0) $boxCount = 1;

                $boxInfoText = "Boxes: " . $boxCount . " (" . $boxPerPack . " plants/box) @ Rs " . number_format($chargesPerBox, 2) . "/box";
                $routeText = "Karjee  →  " . $lAddr . "\n" . $boxInfoText;
            } else {
                $distText = "";
                if ($lFreeDist > 0) {
                    $tDist = $lTotalDist > 0 ? $lTotalDist : ($lPayableDist + $lFreeDist);
                    $distText = " (Total Distance: " . number_format($tDist, 2) . " km | Free: " . number_format($lFreeDist, 2) . " km | Payable: " . number_format($lPayableDist, 2) . " km)";
                } elseif ($lPayableDist > 0 || $lTotalDist > 0) {
                    $pDist = $lPayableDist > 0 ? $lPayableDist : $lTotalDist;
                    $distText = " (Distance: " . number_format($pDist, 2) . " km)";
                }
                $routeText = "Karjee  →  " . $lAddr . $distText;
            }

            $extraMeta = [];
            $vNo = trim($grp['vehicleno'] ?? '');
            $vName = trim($grp['vehiclename'] ?? '');
            $tName = trim($grp['vehicleownername'] ?? '');
            $dName = trim($grp['drivername'] ?? '');
            $dMobile = trim($grp['drivercontactno'] ?? '');
            $tId = trim($grp['trip_id'] ?? '');

            if (!empty($vName) && !empty($vNo)) {
                $extraMeta[] = "Vehicle: " . $vName . " (" . $vNo . ")";
            } elseif (!empty($vNo)) {
                $extraMeta[] = "Vehicle: " . $vNo;
            } elseif (!empty($vName)) {
                $extraMeta[] = "Vehicle: " . $vName;
            }
            if (!empty($tName)) {
                $extraMeta[] = "Transporter: " . $tName;
            }
            if (!empty($dName)) {
                $extraMeta[] = "Driver: " . $dName . (!empty($dMobile) ? " ({$dMobile})" : "");
            }
            if (!empty($tId)) {
                $extraMeta[] = "Trip: " . $tId;
            }

            if (!empty($extraMeta)) {
                $routeText .= "\n" . implode('  |  ', $extraMeta);
            }

            if (!empty($lRemark) && stripos($lRemark, '@@@') === false && stripos($lRemark, 'Trip 1: Amt Rs') === false && stripos($lRemark, 'Freight Discount Applied') === false) {
                $routeText .= "\nRemark: " . $lRemark;
            }

            $locAmt = 0;
            if ((float)($grp['freightamt'] ?? 0) > 0) {
                $locAmt = (float)$grp['freightamt'];
            } elseif ((float)($grp['deliverycharges'] ?? 0) > 0) {
                $locAmt = (float)$grp['deliverycharges'];
            } elseif ((float)($grp['totalamount'] ?? 0) > 0) {
                $locAmt = (float)$grp['totalamount'];
            } elseif ($grpCount === 1) {
                $locAmt = $ttlfreight;
            }
            $totalFreightRendered += $locAmt;
            $amtDisplay = number_format($locAmt, 2);

            $fHeight = max($pdf->getNumLines($routeText, 158) * 4.2, 5.5);
            $pdf->MultiCell(158, $fHeight, $routeText, 1, 'L', 0, 0);
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->MultiCell(28, $fHeight, $amtDisplay, 1, 'C', 0, 1);
            $pdf->SetFont('freesans', '', 8);
        }

        if ($hasRealFreightDiscount && !empty($appliedDiscountRule)) {
            // Freight Discount row displayed AFTER freight details box
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->SetFillColor(245, 248, 255);
            $pdf->SetTextColor(30, 64, 175);
            $discountBoxText = '  Freight Discount: ' . $appliedDiscountRule;
            if ($freightDiscountAmt > 0) {
                $discountBoxText .= ' (Savings: Rs. ' . number_format($freightDiscountAmt, 2) . ')';
            }
            $dHeight = max($pdf->getNumLines($discountBoxText, 186) * 3.8, 5.2);
            $pdf->MultiCell(186, $dHeight, $discountBoxText, 1, 'L', 1, 1);
            $pdf->SetTextColor(0, 0, 0);

            // Informational Note (Neutral dark text, NOT RED!)
            $discountNotice = "Note: Discounted freight is applicable only when the dispatched quantity and distance comply with the agreed freight rule. Any change in quantity or distance at dispatch will make the discount invalid, and freight will be charged at the applicable rate.";
            $pdf->SetFont('freesans', '', 7.2);
            $pdf->SetTextColor(70, 70, 70);    // Neutral dark grey text
            $pdf->SetFillColor(250, 250, 250); // Soft grey fill
            $pdf->SetDrawColor(210, 210, 210); // Subtle border
            $nHeight = max($pdf->getNumLines($discountNotice, 186) * 3.5, 4.8);
            $pdf->MultiCell(186, $nHeight, $discountNotice, 1, 'L', 1, 1);
            // Reset colors
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('freesans', '', 8);
        }
    }

    $bankDetails = [
        "Bank Name" => "HDFC",
        "Account No" => "50200092846879",
        "IFSC Code" => "HDFC0000917",
        "Branch" => "AMBIKAPUR",
        "UPI ID" => "8319620998-6@ybl",
    ];

    $pdf->Ln(3);
    $startY = $pdf->GetY();

    $pdf->SetFont('freesans', 'B', 9);
    $pdf->SetX(12);
    $pdf->Cell(62, 4.5, "KARJEE FARM BANK DETAILS", 0, 1, 'L');
    $pdf->SetFont('freesans', '', 7.5);
    $pdf->SetX(12);
    $pdf->MultiCell(
        62,
        3.8,
        "Bank: {$bankDetails['Bank Name']}\nAccount No: {$bankDetails['Account No']}\nIFSC: {$bankDetails['IFSC Code']}\nBranch: {$bankDetails['Branch']}\nUPI ID: {$bankDetails['UPI ID']}",
        0,
        'L'
    );
    $bankEndY = $pdf->GetY();

    // QR Code Image near Bank Details
    $qrCodePath = __DIR__ . "/../../img/karjeefarmqrcode.png";
    if (!file_exists($qrCodePath)) {
        $qrCodePath = __DIR__ . "/../../img/karjeefarmqrcode.jpeg";
    }
    if (!file_exists($qrCodePath)) {
        $qrCodePath = dirname(dirname(__DIR__)) . "/img/karjeefarmqrcode.jpeg";
    }
    $qrEndY = $startY;
    if (file_exists($qrCodePath)) {
        $qrSize = 24;
        $qrX = 79;
        $imgType = (stripos($qrCodePath, '.png') !== false) ? 'PNG' : 'JPEG';
        $pdf->Image($qrCodePath, $qrX, $startY, $qrSize, $qrSize, $imgType, '', '', false, 300, '', false, false, 0);
        $pdf->SetXY($qrX, $startY + $qrSize + 0.5);
        $pdf->SetFont('freesans', 'B', 7);
        $pdf->Cell($qrSize, 3.5, 'Scan & Pay', 0, 0, 'C');
        $qrEndY = $startY + $qrSize + 4;
    }

    $finalSubtotal = (float)$totalProductAmount + (float)$totalFreightRendered;
    $netPayable = max(0.00, round($finalSubtotal - $totalOtherDiscount, 2));
    $excessReceived = round($ttlrcv - $netPayable, 2);
    $hasAdvance = ($excessReceived > 10.00);
    $advanceAmount = $hasAdvance ? $excessReceived : 0.00;
    $balance = max(0.00, round($netPayable - $ttlrcv, 2));

    $totalsX = 110;
    $lblW = 54;
    $valW = 34;
    $totW = $lblW + $valW;

    $pdf->SetY($startY);
    $pdf->SetFont('freesans', '', 8.5);
    $pdf->SetX($totalsX);
    $pdf->Cell($lblW, 5, 'Subtotal', 1, 0, 'R');
    $pdf->Cell($valW, 5, number_format($finalSubtotal, 2), 1, 1, 'R');

    if ($totalOtherDiscount > 0.00) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', '', 8.5);
        $pdf->Cell($lblW, 5, 'Special Discount', 1, 0, 'R');
        $pdf->Cell($valW, 5, '-' . number_format($totalOtherDiscount, 2), 1, 1, 'R');
    }

    $pdf->SetX($totalsX);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell($lblW, 5, 'Total Received', 1, 0, 'R');
    $pdf->Cell($valW, 5, number_format($ttlrcv, 2), 1, 1, 'R');

    if ($hasAdvance) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', 'B', 8.5);
        $pdf->SetTextColor(21, 128, 61);
        $pdf->SetFillColor(240, 253, 244);
        $pdf->Cell($lblW, 5, 'Advance (Excess Amount)', 1, 0, 'R', 1);
        $pdf->Cell($valW, 5, number_format($advanceAmount, 2), 1, 1, 'R', 1);
        $pdf->SetTextColor(0, 0, 0);
    }

    $pdf->SetX($totalsX);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($lblW, 5, 'Balance', 1, 0, 'R', 1);
    $pdf->Cell($valW, 5, number_format($balance, 2), 1, 1, 'R', 1);

    // Received Amount Breakdown (displayed after Balance row)
    $breakdownParts = [];
    if (floatval($cashReceived) > 0) {
        $breakdownParts[] = "Cash => " . number_format($cashReceived, 2);
    }
    if (floatval($chequeReceived) > 0) {
        $breakdownParts[] = "Check => " . number_format($chequeReceived, 2);
    }
    if (floatval($upiBankReceived) > 0) {
        $breakdownParts[] = "UPI/Bank => " . number_format($upiBankReceived, 2);
    }

    if (!empty($breakdownParts)) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', '', 7.2);
        $pdf->SetFillColor(248, 249, 250);
        $breakdownText = implode('  |  ', $breakdownParts);
        $pdf->Cell($totW, 4.5, $breakdownText, 1, 1, 'C', 1);
    }
    $totalsEndY = $pdf->GetY();

    $maxEndY = max($bankEndY, $totalsEndY, $qrEndY);
    $pdf->SetY($maxEndY + 2.5);

    // Payable Amount in Words
    $payableInWords = getIndianCurrencyInWords(max(0, (float)$balance));
    $wordsText = "Payable Amount in Words: " . $payableInWords;
    if ($hasAdvance) {
        $wordsText .= "  (Advance: Rs. " . number_format($advanceAmount, 2) . ")";
    }
    $pdf->SetFont('freesans', 'B', 8);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->MultiCell(186, 5, $wordsText, 1, 'L', 1, 1);
    $pdf->SetFont('freesans', '', 8);

    $pdf->Ln(2);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell(0, 4.5, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->Ln(0.5);
    $pdf->SetFont('freesans', '', 6.8);
    $terms = "1. 50% advance payment is mandatory at the time of placing the order, and the remaining 50% amount must be fully cleared before plant delivery.\n" .
             "2. Karjee Farm shall not be held responsible for any damage, loss, or deterioration of plants during transit or transportation.\n" .
             "3. If the customer receives plants after the scheduled delivery time, Karjee Farm will not be responsible for plant quality deterioration thereafter.\n" .
             "4. Crates supplied with the plant delivery must be returned immediately in the same transport vehicle.\n" .
             "5. Plant delivery will be scheduled after full payment is received, subject to vehicle availability and designated delivery location.\n" .
             "6. Karjee Farm reserves the right to cancel the order if payments are not made within the agreed timeframe.\n" .
             "7. All payments must be made strictly in accordance with the mutually agreed payment terms.\n" .
             "8. Provisional sales orders are valid for 3 days only. If confirmation and advance payment are not received within 3 days, this order will be automatically cancelled.\n" .
             "9. Orders cannot be cancelled under any circumstances once seed sowing has been initiated.\n" .
             "10. Estimated delivery dates may vary by 4-5 days due to weather conditions or other unforeseen circumstances.";

    $pdf->MultiCell(0, 3.2, $terms, 0, 'L');

    if ($mode == 'F' && !empty($save_path)) {
        $pdf->Output($save_path, 'F');
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - PDF saved to $save_path\n", FILE_APPEND);
        return $save_path;
    } elseif ($mode == 'S') {
        return $pdf->Output("$psono.pdf", 'S');
    } elseif ($mode == 'I') {
        $pdf->Output("$psono.pdf", 'I');
    } else {
        $pdf->Output("$psono.pdf", 'D');
    }
}


function createSalesOrderPDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $sono, $mode = 'D', $save_path = '')
{
    $logFile = __DIR__ . '/../debug_email_pdf.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - generating FINAL SALES ORDER PDF for sono: $sono\n", FILE_APPEND);

    // --- Fetch Data ---
    $cmpinfo = getcompanydetailswithlogin($conn, "companyid", $companyid);
    $companyname = $cmpinfo[0]['companyname'] ?? "Karjee Farms";

    // Fetch final sales order details
    $order = getFinalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "sono", $sono)[0] ?? null;

    if (!isset($order) || !$order) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - Error: Final Sales Order not found for sono: $sono\n", FILE_APPEND);
        if ($mode == 'S')
            return "Error: Order not found.";
        return false;
    }
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - Final Order found. Client: " . ($order['clientname'] ?? 'Unknown') . "\n", FILE_APPEND);

    // Fetch Freight (Try by sono first, then by psono if available)
    $ttlfreight = 0;
    $fRes = $krfconn->query("SELECT payable_freightamount FROM krf_freightcharges WHERE sono = '" . $krfconn->real_escape_string($sono) . "' LIMIT 1");
    if (!$fRes || $fRes->num_rows == 0) {
        $fRes = $krfconn->query("SELECT payable_freightamount FROM krf_freightcharges WHERE psono = '" . $krfconn->real_escape_string($order['psono'] ?? '') . "' LIMIT 1");
    }

    if ($fRes && $fRow = $fRes->fetch_assoc()) {
        $ttlfreight = (float) $fRow['payable_freightamount'];
    }

    $created_by = resolvePdfCreatedByName($authconn, $krfconn, [], $order, '', $order['psono'] ?? '', $sono);

    // Fetch final sales order items (excluding cancelled items)
    $rawItems = getFinalSalesOrderItems($krfconn, $sono);
    $items = [];
    foreach ($rawItems as $it) {
        $st = strtolower(trim($it['status'] ?? ''));
        $q = floatval($it['quantity'] ?? 0);
        if ($st === 'cancelled' || $st === 'cancel' || $q <= 0) {
            continue;
        }
        $items[] = $it;
    }

    // Fetch payments linked to this order (using psono if available, or sono)
    $paymentrcv = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $order['psono']);

    $ttlamount = (float) ($order['total_amount'] ?? 0);
    $ttldiscount = 0; // Final order usually has totals pre-calculated
    $ttltax = (float) ($order['tax_amount'] ?? 0);
    $grandtotal = (float) ($order['grand_total'] ?? 0) + $ttlfreight;

    $ttlrcv = 0;
    $cashReceived = 0.0;
    $chequeReceived = 0.0;
    $upiBankReceived = 0.0;
    $totalOtherDiscount = 0.0;
    foreach ($paymentrcv as $p) {
        $pAmt = (float) ($p['advanceamount'] ?? 0);
        $pDisc = (float) ($p['otherdiscount'] ?? 0);
        $totalOtherDiscount += $pDisc;
        $pSrc = strtolower(trim($p['sourceofpayment'] ?? ''));
        if ($pSrc === 'cash') {
            $cashReceived += $pAmt;
        } elseif (in_array($pSrc, ['cheque', 'check'])) {
            $chequeReceived += $pAmt;
        } else {
            $upiBankReceived += $pAmt;
        }
        $ttlrcv += $pAmt;
    }
    $balance = max(0.00, $grandtotal - $totalOtherDiscount - $ttlrcv);

    $bankDetails = [
        "Bank Name" => "HDFC",
        "Account No" => "50200092846879",
        "IFSC Code" => "HDFC0000917",
        "Branch" => "AMBIKAPUR",
        "UPI ID" => "8319620998-6@ybl",
    ];

    // --- Init PDF Cache ---
    if (!defined('K_PATH_CACHE')) {
        $cacheDir = realpath(__DIR__ . '/../modal') . DIRECTORY_SEPARATOR . 'tcpdf_cache' . DIRECTORY_SEPARATOR;
        if (!file_exists($cacheDir)) {
            @mkdir($cacheDir, 0777, true);
        }
        // Fallback to system temp if not writable
        if (!is_writable($cacheDir)) {
            $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcpdf_cache' . DIRECTORY_SEPARATOR;
            if (!file_exists($cacheDir)) {
                @mkdir($cacheDir, 0777, true);
            }
        }
        define('K_PATH_CACHE', $cacheDir);
    }

    if (!class_exists('TCPDF')) {
        require_once(__DIR__ . '/../pdfroutines/tcpdf/tcpdf.php');
    }

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(12, 5, 12);
    $pdf->SetAutoPageBreak(true, 5);
    $pdf->AddPage();

    // --- Header ---
    $pdf->SetFont('helvetica', 'B', 8.5);
    $pdf->Cell(0, 4.5, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
    $pdf->Ln(1);

    $logoPath = __DIR__ . "/../logo/logo.png";
    if (file_exists($logoPath) && (extension_loaded('gd') || extension_loaded('imagick'))) {
        $pdf->Image($logoPath, 12, $pdf->GetY(), 24, 0, '', '', '', false, 300, '', false, false, 0);
    }

    $pdf->SetY($pdf->GetY());
    $pdf->SetX(45);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 7, strtoupper($companyname), 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 9);
    $cmpInfo = "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\n" .
        "Mobile No: 6261832409, 7879615628";

    $pdf->SetX(90);
    $pdf->MultiCell(108, 4, $cmpInfo, 0, 'R', 0, 1);

    $pdf->Ln(4);

    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 6, 'Sales Order', 0, 1, 'C');
    $pdf->Ln(3);

    // --- Dynamic Invoice No & Invoice Date Resolution ---
    $invoiceNo = trim($_GET['invoiceno'] ?? '');
    if (empty($invoiceNo)) {
        if (!empty($order['dinvoiceno'])) {
            $invoiceNo = $order['dinvoiceno'];
        } else if (function_exists('generateDispatchInvoiceNo')) {
            $invoiceNo = generateDispatchInvoiceNo($krfconn, $order['psono'] ?? '', $sono);
        } else if (function_exists('getNewDispatchInvoiceNumber')) {
            $invoiceNo = getNewDispatchInvoiceNumber($krfconn, $order['psono'] ?? '', $sono);
        } else {
            $fyStr = date('y') . '-' . (date('y') + 1);
            $cntStmt = $krfconn->query("SELECT COUNT(*) AS total_dinv FROM krf_sales_order WHERE invoicetype = 'dispatchinvoice' OR (dinvoiceno IS NOT NULL AND dinvoiceno != '')");
            $cntRow = $cntStmt ? $cntStmt->fetch_assoc() : null;
            $dinvCount = ($cntRow ? intval($cntRow['total_dinv']) : 0) + 1;
            $invoiceNo = "KF/" . $fyStr . "/" . $dinvCount;
        }
    }

    $reqInvoiceDate = trim($_GET['invoicedate'] ?? '');
    if (!empty($reqInvoiceDate)) {
        $invoiceDateFormatted = date('d-M-Y', strtotime($reqInvoiceDate));
    } else {
        $invoiceDateFormatted = date('d-M-Y');
    }

    // --- INFO ---
    $pdf->SetFont('freesans', '', 9);

    // Fetch Invoicing Address from krf_client_master (NOT delivery address)
    $clientMasterInvAddr = '';
    $clientIdVal = $order['clientid'] ?? '';
    if (!empty($clientIdVal)) {
        $cmStmt = $krfconn->prepare("SELECT invoicing_address, statename, districtname, cityname FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($cmStmt) {
            $cmStmt->bind_param("s", $clientIdVal);
            $cmStmt->execute();
            $cmRow = $cmStmt->get_result()->fetch_assoc();
            $cmStmt->close();
            if ($cmRow) {
                $clientMasterInvAddr = trim($cmRow['invoicing_address'] ?? '');
                if (empty($clientMasterInvAddr)) {
                    $parts = array_filter([$cmRow['cityname'] ?? '', $cmRow['districtname'] ?? '', $cmRow['statename'] ?? '']);
                    $clientMasterInvAddr = implode(', ', $parts);
                }
            }
        }
    }
    if (empty($clientMasterInvAddr)) {
        $clientMasterInvAddr = trim($order['address'] ?? '');
    }
    $invoiceAddress = $clientMasterInvAddr;
    $invoiceAddress = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $invoiceAddress);
    $invoiceAddress = preg_replace('/\s+/u', ' ', $invoiceAddress);
    if ($invoiceAddress === '') {
        $invoiceAddress = 'N/A';
    }

    $rightInfo = "Customer: {$order['clientname']}\n" .
        "Email: " . ($order['emailid'] != '' ? $order['emailid'] : 'N/A') . "\n" .
        "Mobile: " . ($order['mobileno'] != '' ? $order['mobileno'] : 'N/A') . "\n" .
        "Invoice Address: " . $invoiceAddress;

    $leftInfo = "Invoice No: " . $invoiceNo . "\n" .
        "Invoice Date: " . $invoiceDateFormatted . "\n" .
        "Sales Order No: {$order['sono']}\n" .
        "Ref Prov. No: {$order['psono']}\n" .
        "Order Date: " . date('d-M-Y', strtotime($order['created_at'])) . "\n" .
        "Created By: " . strtoupper($created_by);

    $startX = 12;
    $startY = $pdf->GetY();
    $columnWidth = 90;
    $columnGap = 6;

    $pdf->SetXY($startX, $startY);
    $pdf->MultiCell($columnWidth, 4.2, $rightInfo, 0, 'L', false, 1);
    $rightEndY = $pdf->GetY();

    $pdf->SetXY($startX + $columnWidth + $columnGap, $startY);
    $pdf->MultiCell($columnWidth, 4.2, $leftInfo, 0, 'R', false, 1);
    $leftEndY = $pdf->GetY();

    $pdf->SetY(max($rightEndY, $leftEndY) + 4);
    $pdf->SetX($startX);

    // --- TABLE ---
    $pdf->SetFont('freesans', '', 8.5);
    $w = [8, 52, 20, 20, 20, 38, 28]; // total = 186 mm

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell($w[0], 6, '#', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 6, 'Product', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 6, 'Qty', 1, 0, 'C', 1);
    $pdf->Cell($w[3], 6, 'Rate', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 6, 'Discount', 1, 0, 'C', 1);
    $pdf->Cell($w[5], 6, 'Est. Delivery Date', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 6, 'Amount', 1, 1, 'C', 1);
    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $count = 1;
    $totalProductQty = 0;
    $totalProductAmount = 0;
    $totalProductDiscount = 0;
    $itmsuffix = " Pcs";
    foreach ($items as $item) {$producttypeid = $krfconn->query("SELECT ProductTypeID FROM karjeefarm.product_master WHERE ProductID = '".$krfconn->real_escape_string($item['product_id'])."' LIMIT 1")->fetch_assoc()['ProductTypeID'] ?? '';
        if($producttypeid=="46"){
            $itmsuffix = " Tray";
        }

        $st = strtolower(trim($item['status'] ?? ''));
        $pQty = (float)($item['quantity'] ?? 0);
        if ($st === 'cancelled' || $st === 'cancel' || $pQty <= 0) {
            continue;
        }
        $pRate = (float)($item['rate'] ?? 0);
        $pDisc = (float)($item['discount_amount'] ?? 0);
        $pAmt = (float)($item['amount'] ?? ($pQty * $pRate - $pDisc));

        $totalProductQty += $pQty;
        $totalProductAmount += $pAmt;
        $totalProductDiscount += $pDisc;

        $qtyStr = $pQty . $itmsuffix;
        $delDateStr = !empty($item['deliverydate']) ? date('d-M-Y', strtotime($item['deliverydate'])) : 'N/A';
        $rateStr = number_format($pRate, 2);
        $discStr = number_format($pDisc, 2);
        $amtStr = number_format($pAmt, 2);

        $cellHeights = [];
        $cellHeights[] = $pdf->getNumLines($count, $w[0]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($item['product_name'], $w[1]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($qtyStr, $w[2]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($rateStr, $w[3]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($discStr, $w[4]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($delDateStr, $w[5]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($amtStr, $w[6]) * 4.8;
        $maxHeight = max(5.5, max($cellHeights));

        $pdf->MultiCell($w[0], $maxHeight, $count, 1, 'C', 0, 0);
        $pdf->MultiCell($w[1], $maxHeight, $item['product_name'], 1, 'L', 0, 0);
        $pdf->MultiCell($w[2], $maxHeight, $qtyStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[3], $maxHeight, $rateStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[4], $maxHeight, $discStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[5], $maxHeight, $delDateStr, 1, 'C', 0, 0);
        $pdf->MultiCell($w[6], $maxHeight, $amtStr, 1, 'C', 0, 1);
        $count++;
    }

    // --- Total Row for Products ---
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell($w[0] + $w[1], 5.5, 'Total', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 5.5, $totalProductQty . $itmsuffix, 1, 0, 'C', 1);
    $pdf->Cell($w[3], 5.5, '', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 5.5, ($totalProductDiscount > 0 ? number_format($totalProductDiscount, 2) : '0.00'), 1, 0, 'C', 1);
    $pdf->Cell($w[5], 5.5, '', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 5.5, number_format($totalProductAmount, 2), 1, 1, 'C', 1);

    // --- FREIGHT DETAILS (TABULAR FORMAT) ---
    $productDateMap = [];
    foreach ($items as $idx => $it) {
        $pId = (string)($it['product_id'] ?? '');
        $dDate = !empty($it['deliverydate']) ? date('Y-m-d', strtotime($it['deliverydate'])) : '';
        if ($pId !== '') {
            $productDateMap[$pId] = $dDate;
        }
        $productDateMap['idx_' . $idx] = $dDate;
    }

    $deliveryLocs = [];
    $transportType = 'Farm Delivery';
    $rawPickupType = 'delivered';
    $rawTransportType = '';
    $stmtLoc = $krfconn->prepare("SELECT deliveryaddress, dest_city, dest_district, dest_state, totaldistance, freedistance, distance, remark, deliverycharges, freightamt, totalamount, transporttype, pickuptype, productid, delivery_datetime, noofbox, boxperpack, chargesperbox, vehicleno, '' as vehiclename, '' as vehicleownername, drivername, drivercontactno, '' as trip_id FROM krf_deliveryaddress_location WHERE (sono = ? OR psono = ?) AND (tedit IS NULL OR tedit = '') ORDER BY id ASC");
    if ($stmtLoc) {
        $psonoVal = $order['psono'] ?? '';
        $stmtLoc->bind_param("ss", $sono, $psonoVal);
        $stmtLoc->execute();
        $resLoc = $stmtLoc->get_result();
        while ($lRow = $resLoc->fetch_assoc()) {
            $deliveryLocs[] = $lRow;
            if (!empty($lRow['pickuptype'])) {
                $rawPickupType = trim($lRow['pickuptype']);
            }
            if (!empty($lRow['transporttype'])) {
                $rawTransportType = trim($lRow['transporttype']);
            }
        }
        $stmtLoc->close();
    }

    if ($rawPickupType === 'boxpacking') {
        $boxMode = !empty($rawTransportType) ? $rawTransportType : 'Bus';
        $transportType = 'Box Packing (' . $boxMode . ')';
    } else if ($rawPickupType === 'pickup' || $rawPickupType === 'client_pickup' || $rawPickupType === 'pickupbyclient') {
        $transportType = 'Self Pickup By Farmer/Client';
    } else {
        $nonVehicles = ['Bus', 'Train', 'Courier', 'Air', 'Railway', 'Airport', 'Transport', 'Company Delivery', 'Pickup Self'];
        if (!empty($rawTransportType) && !in_array($rawTransportType, $nonVehicles)) {
            $transportType = 'Farm Delivery (' . $rawTransportType . ')';
        } else {
            $transportType = 'Farm Delivery';
        }
    }

    // Determine destination address validity and group by (address + delivery date)
    $hasValidDeliveryAddress = false;
    $destAddr = '';
    $dist = 0;

    $groupedLocs = [];
    if (!empty($deliveryLocs)) {
        foreach ($deliveryLocs as $lIdx => $loc) {
            $lAddr = trim($loc['deliveryaddress'] ?? '');
            if (empty($lAddr)) {
                $parts = array_filter([$loc['dest_city'] ?? '', $loc['dest_district'] ?? '', $loc['dest_state'] ?? '']);
                $lAddr = implode(', ', $parts);
            }
            if (empty($destAddr) && !empty($lAddr)) {
                $destAddr = $lAddr;
                $dist = (float)($loc['distance'] ?? 0);
            }
            $cleanKey = strtoupper(trim(preg_replace('/[\s,.-]+/', '', $lAddr)));
            if ($cleanKey === '' || $cleanKey === 'NA' || $cleanKey === 'N/A' || $cleanKey === 'NULL') {
                continue;
            }

            $pId = (string)($loc['productid'] ?? '');
            $locDate = $productDateMap[$pId] ?? ($productDateMap['idx_' . $lIdx] ?? '');
            if (empty($locDate) && !empty($loc['delivery_datetime'])) {
                $locDate = date('Y-m-d', strtotime($loc['delivery_datetime']));
            }
            $groupKey = $cleanKey . '___' . $locDate;

            $rowTotalDist = (float)($loc['totaldistance'] ?? 0);
            $rowFreeDist = (float)($loc['freedistance'] ?? 0);
            $rowPayableDist = (float)($loc['distance'] ?? 0);
            $rowRemark = trim($loc['remark'] ?? '');

            if ($rowTotalDist <= 0 && $rowPayableDist > 0) {
                $rowTotalDist = $rowPayableDist + $rowFreeDist;
            }

            if (!isset($groupedLocs[$groupKey])) {
                $groupedLocs[$groupKey] = [
                    'address' => $lAddr,
                    'totaldistance' => $rowTotalDist,
                    'freedistance' => $rowFreeDist,
                    'distance' => $rowPayableDist,
                    'remark' => $rowRemark,
                    'freightamt' => (float)($loc['freightamt'] ?? 0),
                    'deliverycharges' => (float)($loc['deliverycharges'] ?? 0),
                    'totalamount' => (float)($loc['totalamount'] ?? 0),
                    'transporttype' => trim($loc['transporttype'] ?? ''),
                    'pickuptype' => trim($loc['pickuptype'] ?? $rawPickupType),
                    'noofbox' => (int)($loc['noofbox'] ?? 0),
                    'boxperpack' => (int)($loc['boxperpack'] ?? 0),
                    'chargesperbox' => (float)($loc['chargesperbox'] ?? 0),
                    'vehicleno' => trim($loc['vehicleno'] ?? ''),
                    'vehiclename' => trim($loc['vehiclename'] ?? ''),
                    'vehicleownername' => trim($loc['vehicleownername'] ?? ''),
                    'drivername' => trim($loc['drivername'] ?? ''),
                    'drivercontactno' => trim($loc['drivercontactno'] ?? ''),
                    'trip_id' => trim($loc['trip_id'] ?? ''),
                    'deliverydate' => $locDate
                ];
            } else {
                $groupedLocs[$groupKey]['freightamt'] += (float)($loc['freightamt'] ?? 0);
                $groupedLocs[$groupKey]['deliverycharges'] += (float)($loc['deliverycharges'] ?? 0);
                $groupedLocs[$groupKey]['totalamount'] += (float)($loc['totalamount'] ?? 0);
                if ($groupedLocs[$groupKey]['totaldistance'] <= 0 && $rowTotalDist > 0) {
                    $groupedLocs[$groupKey]['totaldistance'] = $rowTotalDist;
                }
                if ($groupedLocs[$groupKey]['freedistance'] <= 0 && $rowFreeDist > 0) {
                    $groupedLocs[$groupKey]['freedistance'] = $rowFreeDist;
                }
                if ($groupedLocs[$groupKey]['distance'] <= 0 && $rowPayableDist > 0) {
                    $groupedLocs[$groupKey]['distance'] = $rowPayableDist;
                }
                if (empty($groupedLocs[$groupKey]['remark']) && !empty($rowRemark)) {
                    $groupedLocs[$groupKey]['remark'] = $rowRemark;
                }
                if ($groupedLocs[$groupKey]['noofbox'] <= 0 && (int)($loc['noofbox'] ?? 0) > 0) {
                    $groupedLocs[$groupKey]['noofbox'] = (int)$loc['noofbox'];
                }
                if ($groupedLocs[$groupKey]['boxperpack'] <= 0 && (int)($loc['boxperpack'] ?? 0) > 0) {
                    $groupedLocs[$groupKey]['boxperpack'] = (int)$loc['boxperpack'];
                }
                if ($groupedLocs[$groupKey]['chargesperbox'] <= 0 && (float)($loc['chargesperbox'] ?? 0) > 0) {
                    $groupedLocs[$groupKey]['chargesperbox'] = (float)$loc['chargesperbox'];
                }
                if (empty($groupedLocs[$groupKey]['vehicleno']) && !empty($loc['vehicleno'])) {
                    $groupedLocs[$groupKey]['vehicleno'] = trim($loc['vehicleno']);
                }
                if (empty($groupedLocs[$groupKey]['vehiclename']) && !empty($loc['vehiclename'])) {
                    $groupedLocs[$groupKey]['vehiclename'] = trim($loc['vehiclename']);
                }
                if (empty($groupedLocs[$groupKey]['vehicleownername']) && !empty($loc['vehicleownername'])) {
                    $groupedLocs[$groupKey]['vehicleownername'] = trim($loc['vehicleownername']);
                }
                if (empty($groupedLocs[$groupKey]['drivername']) && !empty($loc['drivername'])) {
                    $groupedLocs[$groupKey]['drivername'] = trim($loc['drivername']);
                }
                if (empty($groupedLocs[$groupKey]['drivercontactno']) && !empty($loc['drivercontactno'])) {
                    $groupedLocs[$groupKey]['drivercontactno'] = trim($loc['drivercontactno']);
                }
                if (empty($groupedLocs[$groupKey]['trip_id']) && !empty($loc['trip_id'])) {
                    $groupedLocs[$groupKey]['trip_id'] = trim($loc['trip_id']);
                }
            }
        }
    }

    $hasValidDeliveryAddress = !empty($groupedLocs);

    // Extra space before Freight Details section
    $pdf->Ln(3);

    // Determine if any freight discount rule is applied
    $appliedDiscountRule = '';
    $freightDiscountAmt = 0.0;
    $freightDiscountPct = 0.0;
    $freightFreeDist = 0.0;

    if (!empty($params['freight_rule'])) {
        $appliedDiscountRule = trim($params['freight_rule']);
    } elseif (!empty($params['freight_discount_plan'])) {
        $appliedDiscountRule = trim($params['freight_discount_plan']);
    }

    if (!empty($groupedLocs)) {
        foreach ($groupedLocs as $g) {
            $fd = floatval($g['freedistance'] ?? $g['free_distance'] ?? 0);
            $dp = floatval($g['discount_percent'] ?? $g['discPct'] ?? 0);
            $da = floatval($g['discount_amount'] ?? $g['discAmt'] ?? 0);
            $r = trim($g['remark'] ?? $g['remarks'] ?? '');

            if ($fd > $freightFreeDist) $freightFreeDist = $fd;
            if ($dp > $freightDiscountPct) $freightDiscountPct = $dp;
            if ($da > $freightDiscountAmt) $freightDiscountAmt = $da;

            if (empty($appliedDiscountRule)) {
                if ($dp > 0) {
                    $appliedDiscountRule = number_format($dp, 0) . "% Off" . ($da > 0 ? " (-Rs " . number_format($da, 2) . ")" : "");
                } elseif ($da > 0) {
                    $appliedDiscountRule = "Discount -Rs " . number_format($da, 2);
                } elseif ($fd > 0) {
                    $appliedDiscountRule = "Free Distance: " . number_format($fd, 2) . " KM";
                } elseif (!empty($r) && (stripos($r, 'discount') !== false || stripos($r, 'free freight') !== false || stripos($r, '% off') !== false) && stripos($r, '@@@') === false && stripos($r, 'Trip 1: Amt Rs') === false) {
                    $appliedDiscountRule = $r;
                }
            }
        }
    }

    if (empty($appliedDiscountRule) && (!empty($sono) || !empty($psono))) {
        $chkFc = $krfconn->prepare("SELECT remarks, freight_discountpercent, freight_discountamount FROM krf_freightcharges WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
        if ($chkFc) {
            $chkFc->bind_param("ssss", $psono, $psono, $sono, $sono);
            $chkFc->execute();
            $fcRes = $chkFc->get_result()->fetch_assoc();
            $chkFc->close();
            if ($fcRes) {
                $fcRem = trim($fcRes['remarks'] ?? '');
                $fcPct = floatval($fcRes['freight_discountpercent'] ?? 0);
                $fcAmt = floatval($fcRes['freight_discountamount'] ?? 0);
                if ($fcPct > $freightDiscountPct) $freightDiscountPct = $fcPct;
                if ($fcAmt > $freightDiscountAmt) $freightDiscountAmt = $fcAmt;

                if ($fcPct > 0) {
                    $appliedDiscountRule = number_format($fcPct, 0) . "% Off" . ($fcAmt > 0 ? " (-Rs " . number_format($fcAmt, 2) . ")" : "");
                } elseif ($fcAmt > 0) {
                    $appliedDiscountRule = "Discount -Rs " . number_format($fcAmt, 2);
                } elseif (!empty($fcRem) && (stripos($fcRem, 'discount') !== false || stripos($fcRem, 'free freight') !== false || stripos($fcRem, '% off') !== false) && stripos($fcRem, '@@@') === false && stripos($fcRem, 'Trip 1: Amt Rs') === false) {
                    $appliedDiscountRule = $fcRem;
                }
            }
        }
    }

    $hasRealFreightDiscount = !empty($appliedDiscountRule) && ($freightDiscountAmt > 0 || $freightDiscountPct > 0 || $freightFreeDist > 0 || !empty($params['freight_rule']) || !empty($params['freight_discount_plan']));

    // Section Header Row: Freight Details and Transport Type (no discount text in this top row!)
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(242, 242, 242);
    $headerTitle = ' Freight Details  |  Transport Type: ' . $transportType;
    $pdf->Cell(186, 5.5, $headerTitle, 1, 1, 'L', 1);

    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $totalFreightRendered = 0;
    if (!$hasValidDeliveryAddress) {
        // Missing delivery address -> Advisory notice with 0.00 amount
        $missingNotice = "Freight charges will be applicable as per the distance to which the plants are to be delivered. Location to be provided by the farmer.";
        $fHeight = max($pdf->getNumLines($missingNotice, 158) * 4.2, 5.5);
        $pdf->MultiCell(158, $fHeight, $missingNotice, 1, 'L', 0, 0);
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->MultiCell(28, $fHeight, '0.00', 1, 'C', 0, 1);
        $pdf->SetFont('freesans', '', 8);
    } else {
        $hasFreightDiscount = !empty($appliedDiscountRule);
        $grpCount = count($groupedLocs);
        foreach ($groupedLocs as $grp) {
            $lAddr = $grp['address'];
            $lTotalDist = (float)($grp['totaldistance'] ?? 0);
            $lFreeDist = (float)($grp['freedistance'] ?? 0);
            $lPayableDist = (float)($grp['distance'] ?? 0);
            $lRemark = trim($grp['remark'] ?? '');
            $grpPickupType = trim($grp['pickuptype'] ?? $rawPickupType);

            if ($grpPickupType === 'pickup' || $grpPickupType === 'client_pickup' || $grpPickupType === 'pickupbyclient') {
                $routeText = "Self Pickup (Ex-Nursery / Farm Gate)";
            } else if ($grpPickupType === 'boxpacking') {
                $boxPerPack = (int)($grp['boxperpack'] ?? 0);
                if ($boxPerPack <= 0) $boxPerPack = 500;
                $chargesPerBox = (float)($grp['chargesperbox'] ?? 0);
                if ($chargesPerBox <= 0) $chargesPerBox = 70.00;
                $boxCount = (int)($grp['noofbox'] ?? 0);
                if ($boxCount <= 0 && $totalProductQty > 0) {
                    $boxCount = ceil($totalProductQty / $boxPerPack);
                }
                if ($boxCount <= 0) $boxCount = 1;

                $boxInfoText = "Boxes: " . $boxCount . " (" . $boxPerPack . " plants/box) @ Rs " . number_format($chargesPerBox, 2) . "/box";
                $routeText = "Karjee  →  " . $lAddr . "\n" . $boxInfoText;
            } else {
                $distText = "";
                if ($lFreeDist > 0) {
                    $tDist = $lTotalDist > 0 ? $lTotalDist : ($lPayableDist + $lFreeDist);
                    $distText = " (Total Distance: " . number_format($tDist, 2) . " km | Free: " . number_format($lFreeDist, 2) . " km | Payable: " . number_format($lPayableDist, 2) . " km)";
                } elseif ($lPayableDist > 0 || $lTotalDist > 0) {
                    $pDist = $lPayableDist > 0 ? $lPayableDist : $lTotalDist;
                    $distText = " (Distance: " . number_format($pDist, 2) . " km)";
                }
                $routeText = "Karjee  →  " . $lAddr . $distText;
            }

            $extraMeta = [];
            $vNo = trim($grp['vehicleno'] ?? '');
            $vName = trim($grp['vehiclename'] ?? '');
            $tName = trim($grp['vehicleownername'] ?? '');
            $dName = trim($grp['drivername'] ?? '');
            $dMobile = trim($grp['drivercontactno'] ?? '');
            $tId = trim($grp['trip_id'] ?? '');

            if (!empty($vName) && !empty($vNo)) {
                $extraMeta[] = "Vehicle: " . $vName . " (" . $vNo . ")";
            } elseif (!empty($vNo)) {
                $extraMeta[] = "Vehicle: " . $vNo;
            } elseif (!empty($vName)) {
                $extraMeta[] = "Vehicle: " . $vName;
            }
            if (!empty($tName)) {
                $extraMeta[] = "Transporter: " . $tName;
            }
            if (!empty($dName)) {
                $extraMeta[] = "Driver: " . $dName . (!empty($dMobile) ? " ({$dMobile})" : "");
            }
            if (!empty($tId)) {
                $extraMeta[] = "Trip: " . $tId;
            }

            if (!empty($extraMeta)) {
                $routeText .= "\n" . implode('  |  ', $extraMeta);
            }

            if (!empty($lRemark) && stripos($lRemark, '@@@') === false && stripos($lRemark, 'Trip 1: Amt Rs') === false && stripos($lRemark, 'Freight Discount Applied') === false) {
                $routeText .= "\nRemark: " . $lRemark;
            }

            $locAmt = 0;
            if ((float)($grp['freightamt'] ?? 0) > 0) {
                $locAmt = (float)$grp['freightamt'];
            } elseif ((float)($grp['deliverycharges'] ?? 0) > 0) {
                $locAmt = (float)$grp['deliverycharges'];
            } elseif ((float)($grp['totalamount'] ?? 0) > 0) {
                $locAmt = (float)$grp['totalamount'];
            } elseif ($grpCount === 1) {
                $locAmt = $ttlfreight;
            }
            $totalFreightRendered += $locAmt;
            $amtDisplay = number_format($locAmt, 2);

            $fHeight = max($pdf->getNumLines($routeText, 158) * 4.2, 5.5);
            $pdf->MultiCell(158, $fHeight, $routeText, 1, 'L', 0, 0);
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->MultiCell(28, $fHeight, $amtDisplay, 1, 'C', 0, 1);
            $pdf->SetFont('freesans', '', 8);
        }

        if ($hasRealFreightDiscount && !empty($appliedDiscountRule)) {
            // Freight Discount row displayed AFTER freight details box
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->SetFillColor(245, 248, 255);
            $pdf->SetTextColor(30, 64, 175);
            $discountBoxText = '  Freight Discount: ' . $appliedDiscountRule;
            if ($freightDiscountAmt > 0) {
                $discountBoxText .= ' (Savings: Rs. ' . number_format($freightDiscountAmt, 2) . ')';
            }
            $dHeight = max($pdf->getNumLines($discountBoxText, 186) * 3.8, 5.2);
            $pdf->MultiCell(186, $dHeight, $discountBoxText, 1, 'L', 1, 1);
            $pdf->SetTextColor(0, 0, 0);

            // Informational Note (Neutral dark text, NOT RED!)
            $discountNotice = "Note: Discounted freight is applicable only when the dispatched quantity and distance comply with the agreed freight rule. Any change in quantity or distance at dispatch will make the discount invalid, and freight will be charged at the applicable rate.";
            $pdf->SetFont('freesans', '', 7.2);
            $pdf->SetTextColor(70, 70, 70);    // Neutral dark grey text
            $pdf->SetFillColor(250, 250, 250); // Soft grey fill
            $pdf->SetDrawColor(210, 210, 210); // Subtle border
            $nHeight = max($pdf->getNumLines($discountNotice, 186) * 3.5, 4.8);
            $pdf->MultiCell(186, $nHeight, $discountNotice, 1, 'L', 1, 1);
            // Reset colors
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('freesans', '', 8);
        }
    }

    $pdf->Ln(3);
    $startY = $pdf->GetY();

    $pdf->SetFont('freesans', 'B', 9);
    $pdf->SetX(12);
    $pdf->Cell(62, 4.5, "KARJEE FARM BANK DETAILS", 0, 1, 'L');
    $pdf->SetFont('freesans', '', 7.5);
    $pdf->SetX(12);
    $pdf->MultiCell(
        62,
        3.8,
        "Bank: {$bankDetails['Bank Name']}\nAccount No: {$bankDetails['Account No']}\nIFSC: {$bankDetails['IFSC Code']}\nBranch: {$bankDetails['Branch']}\nUPI ID: {$bankDetails['UPI ID']}",
        0,
        'L'
    );
    $bankEndY = $pdf->GetY();

    // QR Code Image near Bank Details
    $qrCodePath = __DIR__ . "/../../img/karjeefarmqrcode.png";
    if (!file_exists($qrCodePath)) {
        $qrCodePath = __DIR__ . "/../../img/karjeefarmqrcode.jpeg";
    }
    if (!file_exists($qrCodePath)) {
        $qrCodePath = dirname(dirname(__DIR__)) . "/img/karjeefarmqrcode.jpeg";
    }
    $qrEndY = $startY;
    if (file_exists($qrCodePath)) {
        $qrSize = 24;
        $qrX = 79;
        $imgType = (stripos($qrCodePath, '.png') !== false) ? 'PNG' : 'JPEG';
        $pdf->Image($qrCodePath, $qrX, $startY, $qrSize, $qrSize, $imgType, '', '', false, 300, '', false, false, 0);
        $pdf->SetXY($qrX, $startY + $qrSize + 0.5);
        $pdf->SetFont('freesans', 'B', 7);
        $pdf->Cell($qrSize, 3.5, 'Scan & Pay', 0, 0, 'C');
        $qrEndY = $startY + $qrSize + 4;
    }

    $effectiveFreight = ($totalFreightRendered > 0) ? $totalFreightRendered : $ttlfreight;
    $grandtotal = (float) ($order['grand_total'] ?? 0) + $effectiveFreight;
    $netPayable = max(0.00, round($grandtotal - $totalOtherDiscount, 2));
    $excessReceived = round($ttlrcv - $netPayable, 2);
    $hasAdvance = ($excessReceived > 10.00);
    $advanceAmount = $hasAdvance ? $excessReceived : 0.00;
    $balance = max(0.00, round($netPayable - $ttlrcv, 2));

    $totalsX = 110;
    $lblW = 54;
    $valW = 34;
    $totW = $lblW + $valW;

    $pdf->SetY($startY);
    $pdf->SetFont('freesans', '', 8.5);
    $pdf->SetX($totalsX);
    $pdf->Cell($lblW, 5, 'Subtotal', 1, 0, 'R');
    $pdf->Cell($valW, 5, number_format($grandtotal - $ttltax, 2), 1, 1, 'R');
    if ($ttltax > 0) {
        $pdf->SetX($totalsX);
        $pdf->Cell($lblW, 5, 'Tax', 1, 0, 'R');
        $pdf->Cell($valW, 5, number_format($ttltax, 2), 1, 1, 'R');
    }
    $pdf->SetX($totalsX);
    $pdf->Cell($lblW, 5, 'Grand Total', 1, 0, 'R', 0, '', 0, false, 'T', 'M');
    $pdf->Cell($valW, 5, number_format($grandtotal, 2), 1, 1, 'R');

    if ($totalOtherDiscount > 0.00) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', '', 8.5);
        $pdf->Cell($lblW, 5, 'Special Discount', 1, 0, 'R');
        $pdf->Cell($valW, 5, '-' . number_format($totalOtherDiscount, 2), 1, 1, 'R');
    }

    $pdf->SetX($totalsX);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell($lblW, 5, 'Total Received', 1, 0, 'R');
    $pdf->Cell($valW, 5, number_format($ttlrcv, 2), 1, 1, 'R');

    if ($hasAdvance) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', 'B', 8.5);
        $pdf->SetTextColor(21, 128, 61);
        $pdf->SetFillColor(240, 253, 244);
        $pdf->Cell($lblW, 5, 'Advance (Excess Amount)', 1, 0, 'R', 1);
        $pdf->Cell($valW, 5, number_format($advanceAmount, 2), 1, 1, 'R', 1);
        $pdf->SetTextColor(0, 0, 0);
    }

    $pdf->SetX($totalsX);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($lblW, 5, 'Balance', 1, 0, 'R', 1);
    $pdf->Cell($valW, 5, number_format($balance, 2), 1, 1, 'R', 1);

    // Received Amount Breakdown (displayed after Balance row)
    $breakdownParts = [];
    if (floatval($cashReceived) > 0) {
        $breakdownParts[] = "Cash => " . number_format($cashReceived, 2);
    }
    if (floatval($chequeReceived) > 0) {
        $breakdownParts[] = "Check => " . number_format($chequeReceived, 2);
    }
    if (floatval($upiBankReceived) > 0) {
        $breakdownParts[] = "UPI/Bank => " . number_format($upiBankReceived, 2);
    }

    if (!empty($breakdownParts)) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', '', 7.2);
        $pdf->SetFillColor(248, 249, 250);
        $breakdownText = implode('  |  ', $breakdownParts);
        $pdf->Cell($totW, 4.5, $breakdownText, 1, 1, 'C', 1);
    }
    $totalsEndY = $pdf->GetY();

    $maxEndY = max($bankEndY, $totalsEndY, $qrEndY);
    $pdf->SetY($maxEndY + 2.5);

    // Payable Amount in Words
    $payableInWords = getIndianCurrencyInWords(max(0, (float)$balance));
    $wordsText = "Payable Amount in Words: " . $payableInWords;
    if ($hasAdvance) {
        $wordsText .= "  (Advance: Rs. " . number_format($advanceAmount, 2) . ")";
    }
    $pdf->SetFont('freesans', 'B', 8);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->MultiCell(186, 5, $wordsText, 1, 'L', 1, 1);
    $pdf->SetFont('freesans', '', 8);

    $pdf->Ln(2);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell(0, 4.5, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->Ln(0.5);
    $pdf->SetFont('freesans', '', 6.8);
    $terms = "1. 50% advance payment is mandatory at the time of placing the order, and the remaining amount must be fully cleared before nursery delivery.\n" .
             "2. Karjee Farm shall not be held responsible for any damage, loss, or deterioration of plants during transit or transportation.\n" .
             "3. If the customer receives plants after the scheduled delivery time, Karjee Farm will not be responsible for plant quality deterioration thereafter.\n" .
             "4. Crates supplied with the nursery delivery must be returned immediately in the same transport vehicle.\n" .
             "5. Nursery delivery will be scheduled after full order payment is received, subject to vehicle availability and designated delivery location.\n" .
             "6. Karjee Farm reserves the right to cancel the order if the customer fails to make timely payments as per agreed terms.\n" .
             "7. All payments must be made strictly in accordance with the mutually agreed payment terms.\n" .
             "8. Provisional sales orders are valid for 3 days only. If confirmation and advance payment are not received within 3 days, this order will be automatically cancelled.\n" .
             "9. Orders cannot be cancelled under any circumstances once seed sowing has been initiated.\n" .
             "10. Estimated delivery dates may vary by 4-5 days due to weather conditions or other unforeseen circumstances.";
    $pdf->MultiCell(0, 3.2, $terms, 0, 'L');

    if ($mode == 'F' && !empty($save_path)) {
        $pdf->Output($save_path, 'F');
        return $save_path;
    } elseif ($mode == 'S') {
        return $pdf->Output("$sono.pdf", 'S');
    } elseif ($mode == 'I') {
        $pdf->Output("$sono.pdf", 'I');
    } else {
        $pdf->Output("$sono.pdf", 'D');
    }
}


/**
 * Generate Dispatch Invoice PDF for specific dispatched items, extra non-shifting plants, and assigned freight/vehicle
 * 
 * @param mysqli $conn
 * @param mysqli $authconn
 * @param mysqli $krfconn
 * @param array  $params
 * @param string $mode 'I' (Inline/Browser), 'D' (Download), 'F' (Save file), 'S' (String)
 * @param string $save_path
 * @return mixed
 */
function createDispatchInvoicePDF($conn, $authconn, $krfconn, $params, $mode = 'I', $save_path = '')
{
    $logFile = __DIR__ . '/../debug_email_pdf.log';

    $psono = trim($params['psono'] ?? '');
    $sono = trim($params['sono'] ?? '');
    $companyid = intval($params['companyid'] ?? 0);
    $subcompanyid = intval($params['subcompanyid'] ?? 0);
    $projectid = intval($params['projectid'] ?? 0);

    // 1. Fetch Order Header from DB if needed
    $order = null;
    if (!empty($sono)) {
        $order = getFinalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "sono", $sono)[0] ?? null;
    }
    if (!$order && !empty($psono)) {
        $order = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono)[0] ?? null;
    }

    $companyname = "Karjee Farms";
    if ($companyid > 0) {
        $cmpinfo = getcompanydetailswithlogin($conn, "companyid", $companyid);
        $companyname = $cmpinfo[0]['companyname'] ?? "Karjee Farms";
    }

    $createdByName = resolvePdfCreatedByName($authconn, $krfconn, $params, $order, $invoiceNo, $psono, $sono);

    // 2. Resolve Client Details
    $clientName = trim($params['clientname'] ?? $params['client_info']['clientname'] ?? $order['clientname'] ?? 'N/A');
    $emailId = trim($params['emailid'] ?? $params['client_info']['emailid'] ?? $order['emailid'] ?? 'N/A');
    $mobileNo = trim($params['mobileno'] ?? $params['client_info']['mobileno'] ?? $order['mobileno'] ?? 'N/A');
    $invAddress = trim($params['invoicing_address'] ?? $params['address'] ?? $params['client_info']['invoicing_address'] ?? '');

    if (empty($invAddress) && !empty($order['clientid'])) {
        $cmStmt = $krfconn->prepare("SELECT invoicing_address, statename, districtname, cityname FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($cmStmt) {
            $cmStmt->bind_param("s", $order['clientid']);
            $cmStmt->execute();
            $cmRow = $cmStmt->get_result()->fetch_assoc();
            $cmStmt->close();
            if ($cmRow) {
                $invAddress = trim($cmRow['invoicing_address'] ?? '');
                if (empty($invAddress)) {
                    $parts = array_filter([$cmRow['cityname'] ?? '', $cmRow['districtname'] ?? '', $cmRow['statename'] ?? '']);
                    $invAddress = implode(', ', $parts);
                }
            }
        }
    }
    if (empty($invAddress)) {
        $invAddress = trim($order['address'] ?? 'N/A');
    }
    $invAddress = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $invAddress);
    $invAddress = preg_replace('/\s+/u', ' ', $invAddress);
    if ($invAddress === '') $invAddress = 'N/A';

    // 3. Resolve Invoice Number and Date
    $invoiceNo = trim($params['invoiceno'] ?? $_GET['invoiceno'] ?? $order['dinvoiceno'] ?? '');
    if (empty($invoiceNo)) {
        if (function_exists('generateDispatchInvoiceNo')) {
            $invoiceNo = generateDispatchInvoiceNo($krfconn, $psono, $sono);
        } else if (function_exists('getNewDispatchInvoiceNumber')) {
            $invoiceNo = getNewDispatchInvoiceNumber($krfconn, $psono, $sono);
        } else {
            $fyStr = date('y') . '-' . (date('y') + 1);
            $cntStmt = $krfconn->query("SELECT COUNT(*) AS total_dinv FROM krf_sales_order WHERE invoicetype = 'dispatchinvoice' OR (dinvoiceno IS NOT NULL AND dinvoiceno != '')");
            $cntRow = $cntStmt ? $cntStmt->fetch_assoc() : null;
            $dinvCount = ($cntRow ? intval($cntRow['total_dinv']) : 0) + 1;
            $invoiceNo = "KF/" . $fyStr . "/" . $dinvCount;
        }
    }

    $rawInvDate = trim($params['invoicedate'] ?? $_GET['invoicedate'] ?? date('Y-m-d'));
    $invoiceDateFormatted = (!empty($rawInvDate) && strtotime($rawInvDate)) ? date('d-M-Y', strtotime($rawInvDate)) : date('d-M-Y');

    // 4. Resolve Items Table
    $rawItems = $params['items'] ?? [];
    if (empty($rawItems)) {
        if (!empty($invoiceNo)) {
            $siiStmt = $krfconn->prepare("SELECT *, productname AS product_name, productid AS product_id, isshifted AS is_shifted, percentextrashifting AS percent_extra_shifting, shiftingqty AS extra_shifting_qty FROM sales_invoice_items WHERE invoiceno = ? ORDER BY id ASC");
            if ($siiStmt) {
                $siiStmt->bind_param("s", $invoiceNo);
                $siiStmt->execute();
                $rawItems = $siiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $siiStmt->close();
            }
        }
        if (empty($rawItems) && !empty($invoiceNo)) {
            $invSoiStmt = $krfconn->prepare("SELECT * FROM krf_sales_order_items WHERE invoiceno = ? ORDER BY id ASC");
            if ($invSoiStmt) {
                $invSoiStmt->bind_param("s", $invoiceNo);
                $invSoiStmt->execute();
                $rawItems = $invSoiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $invSoiStmt->close();
            }
        }
        if (empty($rawItems) && !empty($sono)) {
            $rawItems = getFinalSalesOrderItems($krfconn, $sono);
        }
        if (empty($rawItems) && !empty($psono)) {
            $soiPsonoStmt = $krfconn->prepare("SELECT * FROM krf_sales_order_items WHERE psono = ? ORDER BY id ASC");
            if ($soiPsonoStmt) {
                $soiPsonoStmt->bind_param("s", $psono);
                $soiPsonoStmt->execute();
                $rawItems = $soiPsonoStmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $soiPsonoStmt->close();
            }
        }
        if (empty($rawItems) && !empty($psono)) {
            $rawItems = getProvisionalSalesOrderItems($krfconn, $psono);
        }
    }

    // Build flattened render items list: includes only selected items, plus extra non-shifting row (rate=0)
    $renderItems = [];
    $totalProductQty = 0;
    $totalProductAmount = 0;
    $totalProductDiscount = 0;

    foreach ($rawItems as $it) {
        $isIncluded = true;
        if (isset($it['is_included'])) {
            $isIncluded = ($it['is_included'] === true || $it['is_included'] === 1 || $it['is_included'] === '1' || $it['is_included'] === 'true');
        } elseif (isset($it['include_in_invoice'])) {
            $isIncluded = ($it['include_in_invoice'] === true || $it['include_in_invoice'] === 1 || $it['include_in_invoice'] === '1');
        }

        if (!$isIncluded) {
            continue; // Exclude unselected items
        }

        $pName = trim($it['clean_product_name'] ?? $it['product_name'] ?? 'Crop Item');
        $pQty = floatval($it['quantity'] ?? $it['billable_qty'] ?? $it['allocate_ordqty'] ?? 0);
        $pRate = floatval($it['rate'] ?? 0);
        $pDisc = floatval($it['discount_amount'] ?? 0);
        $pAmt = floatval($it['amount'] ?? ($pQty * $pRate - $pDisc));

        $rawDelDate = trim($it['deliverydate'] ?? $it['expecteddeliverydate'] ?? $invoiceDateFormatted);
        $delDateStr = (!empty($rawDelDate) && strtotime($rawDelDate)) ? date('d-M-Y', strtotime($rawDelDate)) : $invoiceDateFormatted;

        $totalProductQty += $pQty;
        $totalProductAmount += $pAmt;
        $totalProductDiscount += $pDisc;

        $pId = strval($it['product_id'] ?? '');
        $isBoxPacking = !empty($it['is_box_packing']) || ($pId === 'BOX_PACKING') || stripos($pName, 'Box Packing') !== false;

        // Main Billable Item Row
        $renderItems[] = [
            'product_id' => $pId,
            'product_name' => $pName,
            'quantity' => $pQty,
            'rate' => $pRate,
            'discount_amount' => $pDisc,
            'delivery_date' => $delDateStr,
            'amount' => $pAmt,
            'is_extra' => false,
            'is_box_packing' => $isBoxPacking
        ];

        // Check for Extra Plants Offer (crops only)
        if (!$isBoxPacking) {
            $isShifted = (isset($it['is_shifted']) ? ($it['is_shifted'] === 'yes' || $it['is_shifted'] === 1 || $it['is_shifted'] === '1') : (isset($it['isshifted']) ? ($it['isshifted'] === 'yes' || $it['isshifted'] === 1 || $it['isshifted'] === '1') : true));
            $extraQty = intval($it['extra_qty'] ?? $it['shiftingqty'] ?? $it['extraqtyforshifting'] ?? 0);
            $extraPct = floatval($it['extra_pct'] ?? $it['percentextrashifting'] ?? 0);

            if ($isShifted) {
                if ($extraQty <= 0 && $pQty > 0) {
                    $extraQty = intval(ceil($pQty / 125));
                }
                $bonusLabel = !empty($it['extra_reason']) ? $it['extra_reason'] : "Free Bonus Plants (1 Plant/Tray)";
                $bonusName = $pName . " - " . $bonusLabel;
            } else {
                if ($extraQty <= 0 && $extraPct > 0 && $pQty > 0) {
                    $extraQty = intval(round($pQty * ($extraPct / 100)));
                }
                $pctText = ($extraPct > 0) ? " ({$extraPct}%)" : "";
                $bonusName = $pName . " - Free Non-Shifted Plants" . $pctText;
            }

            // If not passed in params, try fetching info from krf_productionorder_consumed
            if ($extraQty <= 0 && (!empty($it['product_id']) || !empty($it['stockorder_no']))) {
                $stk = trim($it['stockorder_no'] ?? '');
                $pocStmt = $krfconn->prepare("
                    SELECT isshifted, percentextrashifting, extraqtyforshifting 
                    FROM krf_productionorder_consumed 
                    WHERE ((psono = ? AND ? != '' AND product_id = ?) 
                       OR (sono = ? AND ? != '' AND (stockorderno = ? OR product_id = ?)) 
                       OR (stockorderno = ? AND ? != ''))
                      AND IFNULL(tedit, '') = '' 
                    ORDER BY id DESC LIMIT 1
                ");
                if ($pocStmt) {
                    $pocStmt->bind_param("sssssssss", $psono, $psono, $pId, $sono, $sono, $stk, $pId, $stk, $stk);
                    $pocStmt->execute();
                    $pocR = $pocStmt->get_result()->fetch_assoc();
                    $pocStmt->close();
                    if ($pocR) {
                        $dbShifted = ($pocR['isshifted'] == 1 || $pocR['isshifted'] === 'yes' || $pocR['isshifted'] === '1');
                        $extraQty = intval($pocR['extraqtyforshifting'] ?? 0);
                        if ($dbShifted) {
                            if ($extraQty <= 0 && $pQty > 0) $extraQty = intval(ceil($pQty / 125));
                            $bonusName = $pName . " - Free Bonus Plants (1 Plant/Tray)";
                        } else {
                            $extraPct = floatval($pocR['percentextrashifting'] ?? 30);
                            if ($extraQty <= 0 && $extraPct > 0 && $pQty > 0) {
                                $extraQty = intval(round($pQty * ($extraPct / 100)));
                            }
                            $bonusName = $pName . " - Free Non-Shifted Plants" . ($extraPct > 0 ? " ({$extraPct}%)" : "");
                        }
                    }
                }
            }

            if ($extraQty > 0) {
                $renderItems[] = [
                    'product_id' => '',
                    'product_name' => $bonusName,
                    'quantity' => $extraQty,
                    'rate' => 0.00,
                    'discount_amount' => 0.00,
                    'delivery_date' => $delDateStr,
                    'amount' => 0.00,
                    'is_extra' => true,
                    'is_box_packing' => false
                ];
                $totalProductQty += $extraQty;
            }
        }
    }

    // 5. Resolve Freight Details & Assigned Vehicle Metadata
    $rawFreightGroups = $params['freight_groups'] ?? $params['all_freight_groups'] ?? [];
    $rawSingleFreight = $params['freight'] ?? [];

    $resolvedFreightGroups = [];
    $transportType = '';

    if (!empty($rawFreightGroups) && is_array($rawFreightGroups)) {
        foreach ($rawFreightGroups as $grp) {
            $grpTransportType = trim($grp['transport_type'] ?? '');
            if (empty($transportType) && !empty($grpTransportType)) {
                $transportType = $grpTransportType;
            }
            $resolvedFreightGroups[] = [
                'transport_type' => $grpTransportType ?: 'Farm Delivery',
                'delivery_address' => trim($grp['delivery_address'] ?? $grp['address'] ?? ''),
                'distance_km' => floatval($grp['distance_km'] ?? $grp['distance'] ?? $grp['payable_distance'] ?? 0),
                'total_distance' => floatval($grp['total_distance'] ?? $grp['totaldistance'] ?? 0),
                'free_distance' => floatval($grp['free_distance'] ?? $grp['freedistance'] ?? 0),
                'payable_distance' => floatval($grp['payable_distance'] ?? $grp['distance_km'] ?? $grp['distance'] ?? 0),
                'freight_amount' => floatval($grp['freight_amount'] ?? $grp['freightamt'] ?? 0),
                'payable_freight_amount' => floatval($grp['payable_freight_amount'] ?? $grp['payable_freight'] ?? $grp['deliverycharges'] ?? $grp['freightamt'] ?? 0),
                'vehicle_no' => trim($grp['vehicle_no'] ?? $grp['vehicleno'] ?? ''),
                'vehicle_name' => trim($grp['vehicle_name'] ?? $grp['vehiclename'] ?? ''),
                'transporter_name' => trim($grp['transporter_name'] ?? $grp['vehicleownername'] ?? ''),
                'driver_name' => trim($grp['driver_name'] ?? $grp['drivername'] ?? ''),
                'driver_mobile' => trim($grp['driver_mobile'] ?? $grp['driver_mobile_no'] ?? $grp['drivercontactno'] ?? ''),
                'trip_id' => trim($grp['trip_id'] ?? $grp['trip_no'] ?? ''),
                'remark' => trim($grp['remark'] ?? $grp['remarks'] ?? ''),
                'boxperpack' => intval($grp['boxperpack'] ?? 0),
                'noofbox' => intval($grp['noofbox'] ?? 0),
                'chargesperbox' => floatval($grp['chargesperbox'] ?? 0)
            ];
        }
    } elseif (!empty($rawSingleFreight) && is_array($rawSingleFreight)) {
        $transportType = trim($rawSingleFreight['transport_type'] ?? '');
        $resolvedFreightGroups[] = [
            'transport_type' => $transportType ?: 'Farm Delivery',
            'delivery_address' => trim($rawSingleFreight['delivery_address'] ?? $rawSingleFreight['address'] ?? ''),
            'distance_km' => floatval($rawSingleFreight['distance_km'] ?? $rawSingleFreight['distance'] ?? 0),
            'total_distance' => floatval($rawSingleFreight['total_distance'] ?? $rawSingleFreight['totaldistance'] ?? 0),
            'free_distance' => floatval($rawSingleFreight['free_distance'] ?? $rawSingleFreight['freedistance'] ?? 0),
            'payable_distance' => floatval($rawSingleFreight['payable_distance'] ?? $rawSingleFreight['distance_km'] ?? $rawSingleFreight['distance'] ?? 0),
            'freight_amount' => floatval($rawSingleFreight['freight_amount'] ?? $rawSingleFreight['freightamt'] ?? 0),
            'payable_freight_amount' => floatval($rawSingleFreight['payable_freight_amount'] ?? $rawSingleFreight['payable_freight'] ?? $rawSingleFreight['deliverycharges'] ?? $rawSingleFreight['freightamt'] ?? 0),
            'vehicle_no' => trim($rawSingleFreight['vehicle_no'] ?? $rawSingleFreight['vehicleno'] ?? ''),
            'vehicle_name' => trim($rawSingleFreight['vehicle_name'] ?? $rawSingleFreight['vehiclename'] ?? ''),
            'transporter_name' => trim($rawSingleFreight['transporter_name'] ?? $rawSingleFreight['vehicleownername'] ?? ''),
            'driver_name' => trim($rawSingleFreight['driver_name'] ?? $rawSingleFreight['drivername'] ?? ''),
            'driver_mobile' => trim($rawSingleFreight['driver_mobile'] ?? $rawSingleFreight['driver_mobile_no'] ?? $rawSingleFreight['drivercontactno'] ?? ''),
            'trip_id' => trim($rawSingleFreight['trip_id'] ?? $rawSingleFreight['trip_no'] ?? ''),
            'remark' => trim($rawSingleFreight['remark'] ?? $rawSingleFreight['remarks'] ?? ''),
            'boxperpack' => intval($rawSingleFreight['boxperpack'] ?? 0),
            'noofbox' => intval($rawSingleFreight['noofbox'] ?? 0),
            'chargesperbox' => floatval($rawSingleFreight['chargesperbox'] ?? 0)
        ];
    }

    // Fallback: fetch from krf_deliveryaddress_location if no groups provided
    if (empty($resolvedFreightGroups)) {
        $deliveryLocs = [];
        $rawPickupType = 'delivered';
        $rawTransportType = '';
        $stmtLoc = $krfconn->prepare("SELECT deliveryaddress, dest_city, dest_district, dest_state, totaldistance, freedistance, distance, remark, deliverycharges, freightamt, totalamount, transporttype, pickuptype, productid, delivery_datetime, noofbox, boxperpack, chargesperbox, vehicleno, '' as vehiclename, '' as vehicleownername, drivername, drivercontactno, '' as trip_id FROM krf_deliveryaddress_location WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id ASC");
        if ($stmtLoc) {
            $stmtLoc->bind_param("ssss", $psono, $psono, $sono, $sono);
            $stmtLoc->execute();
            $resLoc = $stmtLoc->get_result();
            while ($lRow = $resLoc->fetch_assoc()) {
                $deliveryLocs[] = $lRow;
                if (!empty($lRow['pickuptype'])) $rawPickupType = trim($lRow['pickuptype']);
                if (!empty($lRow['transporttype'])) $rawTransportType = trim($lRow['transporttype']);
            }
            $stmtLoc->close();
        }

        if ($rawPickupType === 'boxpacking') {
            $transportType = 'Box Packing (' . ($rawTransportType ?: 'Bus') . ')';
        } elseif ($rawPickupType === 'pickup' || $rawPickupType === 'client_pickup' || $rawPickupType === 'pickupbyclient') {
            $transportType = 'Self Pickup By Farmer/Client';
        } else {
            $nonVehicles = ['Bus', 'Train', 'Courier', 'Air', 'Railway', 'Airport', 'Transport', 'Company Delivery', 'Pickup Self'];
            if (!empty($rawTransportType) && !in_array($rawTransportType, $nonVehicles)) {
                $transportType = 'Farm Delivery (' . $rawTransportType . ')';
            } else {
                $transportType = 'Farm Delivery';
            }
        }

        if (!empty($deliveryLocs)) {
            foreach ($deliveryLocs as $lIdx => $loc) {
                $lAddr = trim($loc['deliveryaddress'] ?? '');
                if (empty($lAddr)) {
                    $parts = array_filter([$loc['dest_city'] ?? '', $loc['dest_district'] ?? '', $loc['dest_state'] ?? '']);
                    $lAddr = implode(', ', $parts);
                }
                $cleanKey = strtoupper(trim(preg_replace('/[\s,.-]+/', '', $lAddr)));
                if ($cleanKey === '' || $cleanKey === 'NA' || $cleanKey === 'N/A' || $cleanKey === 'NULL') {
                    continue;
                }

                $locDate = !empty($loc['delivery_datetime']) ? date('Y-m-d', strtotime($loc['delivery_datetime'])) : '';
                $groupKey = $cleanKey . '___' . $locDate;

                $rowTotalDist = (float)($loc['totaldistance'] ?? 0);
                $rowFreeDist = (float)($loc['freedistance'] ?? 0);
                $rowPayableDist = (float)($loc['distance'] ?? 0);
                $rowRemark = trim($loc['remark'] ?? '');

                if ($rowTotalDist <= 0 && $rowPayableDist > 0) {
                    $rowTotalDist = $rowPayableDist + $rowFreeDist;
                }

                $pAmt = (float)($loc['freightamt'] ?? $loc['deliverycharges'] ?? $loc['totalamount'] ?? 0);

                if (!isset($resolvedFreightGroups[$groupKey])) {
                    $resolvedFreightGroups[$groupKey] = [
                        'transport_type' => $transportType,
                        'delivery_address' => $lAddr,
                        'total_distance' => $rowTotalDist,
                        'free_distance' => $rowFreeDist,
                        'payable_distance' => $rowPayableDist,
                        'distance_km' => $rowPayableDist,
                        'remark' => $rowRemark,
                        'freight_amount' => (float)($loc['freightamt'] ?? 0),
                        'payable_freight_amount' => $pAmt,
                        'vehicleno' => trim($loc['vehicleno'] ?? ''),
                        'vehiclename' => trim($loc['vehiclename'] ?? ''),
                        'vehicleownername' => trim($loc['vehicleownername'] ?? ''),
                        'drivername' => trim($loc['drivername'] ?? ''),
                        'drivercontactno' => trim($loc['drivercontactno'] ?? ''),
                        'trip_id' => trim($loc['trip_id'] ?? ''),
                        'boxperpack' => (int)($loc['boxperpack'] ?? 0),
                        'noofbox' => (int)($loc['noofbox'] ?? 0),
                        'chargesperbox' => (float)($loc['chargesperbox'] ?? 0),
                        'pickuptype' => trim($loc['pickuptype'] ?? $rawPickupType)
                    ];
                } else {
                    $resolvedFreightGroups[$groupKey]['payable_freight_amount'] += $pAmt;
                    if ($resolvedFreightGroups[$groupKey]['total_distance'] <= 0 && $rowTotalDist > 0) $resolvedFreightGroups[$groupKey]['total_distance'] = $rowTotalDist;
                    if ($resolvedFreightGroups[$groupKey]['free_distance'] <= 0 && $rowFreeDist > 0) $resolvedFreightGroups[$groupKey]['free_distance'] = $rowFreeDist;
                    if ($resolvedFreightGroups[$groupKey]['payable_distance'] <= 0 && $rowPayableDist > 0) {
                        $resolvedFreightGroups[$groupKey]['payable_distance'] = $rowPayableDist;
                        $resolvedFreightGroups[$groupKey]['distance_km'] = $rowPayableDist;
                    }
                    if (empty($resolvedFreightGroups[$groupKey]['remark']) && !empty($rowRemark)) $resolvedFreightGroups[$groupKey]['remark'] = $rowRemark;
                    if (empty($resolvedFreightGroups[$groupKey]['vehicleno']) && !empty($loc['vehicleno'])) $resolvedFreightGroups[$groupKey]['vehicleno'] = trim($loc['vehicleno']);
                    if (empty($resolvedFreightGroups[$groupKey]['vehiclename']) && !empty($loc['vehiclename'])) $resolvedFreightGroups[$groupKey]['vehiclename'] = trim($loc['vehiclename']);
                    if (empty($resolvedFreightGroups[$groupKey]['vehicleownername']) && !empty($loc['vehicleownername'])) $resolvedFreightGroups[$groupKey]['vehicleownername'] = trim($loc['vehicleownername']);
                    if (empty($resolvedFreightGroups[$groupKey]['drivername']) && !empty($loc['drivername'])) $resolvedFreightGroups[$groupKey]['drivername'] = trim($loc['drivername']);
                    if (empty($resolvedFreightGroups[$groupKey]['drivercontactno']) && !empty($loc['drivercontactno'])) $resolvedFreightGroups[$groupKey]['drivercontactno'] = trim($loc['drivercontactno']);
                    if (empty($resolvedFreightGroups[$groupKey]['trip_id']) && !empty($loc['trip_id'])) $resolvedFreightGroups[$groupKey]['trip_id'] = trim($loc['trip_id']);
                }
            }
        }
    }

    if (empty($transportType)) {
        $transportType = 'Farm Delivery';
    }

    // 6. Resolve Payments & Breakdown (Cash, Check/Cheque, UPI/Bank, Other/Special Discount)
    $cashReceived = isset($params['cash_received']) && is_numeric($params['cash_received']) ? floatval($params['cash_received']) : 0.0;
    $chequeReceived = isset($params['cheque_received']) && is_numeric($params['cheque_received']) ? floatval($params['cheque_received']) : 0.0;
    $upiBankReceived = isset($params['upi_bank_received']) && is_numeric($params['upi_bank_received']) ? floatval($params['upi_bank_received']) : 0.0;
    $totalReceived = isset($params['total_received']) && is_numeric($params['total_received']) ? floatval($params['total_received']) : 0.0;
    $otherDiscount = isset($params['other_discount']) && is_numeric($params['other_discount']) ? floatval($params['other_discount']) : (isset($params['special_discount']) && is_numeric($params['special_discount']) ? floatval($params['special_discount']) : 0.0);

    // If breakdown was not passed or both are 0, or if otherDiscount is 0, query advance_payment_transactions directly
    if (($cashReceived <= 0 && $chequeReceived <= 0 && $upiBankReceived <= 0) || $otherDiscount <= 0) {
        if (!empty($psono) || !empty($sono) || !empty($invoiceNo)) {
            $payStmt = $krfconn->prepare("
                SELECT advanceamount, sourceofpayment, otherdiscount, roundoffamount 
                FROM advance_payment_transactions 
                WHERE ((invoiceno = ? AND ? != '') OR (invoiceno = ? AND ? != '') OR (invoiceno = ? AND ? != ''))
                  AND (tedit IS NULL OR tedit = '')
                ORDER BY paymentdate ASC, sno ASC
            ");
            if ($payStmt) {
                $payStmt->bind_param("ssssss", $psono, $psono, $sono, $sono, $invoiceNo, $invoiceNo);
                $payStmt->execute();
                $pRes = $payStmt->get_result();
                $dbCash = 0.0;
                $dbCheque = 0.0;
                $dbUpi = 0.0;
                $dbTotal = 0.0;
                $dbDiscount = 0.0;
                while ($pRow = $pRes->fetch_assoc()) {
                    $pAmt = floatval($pRow['advanceamount'] ?? 0);
                    $pDisc = floatval($pRow['otherdiscount'] ?? 0);
                    $dbDiscount += $pDisc;
                    $pSrc = strtolower(trim($pRow['sourceofpayment'] ?? ''));
                    if ($pSrc === 'cash') {
                        $dbCash += $pAmt;
                    } elseif (in_array($pSrc, ['cheque', 'check'])) {
                        $dbCheque += $pAmt;
                    } else {
                        $dbUpi += $pAmt;
                    }
                    $dbTotal += $pAmt;
                }
                $payStmt->close();
                if ($dbTotal > 0 && ($cashReceived <= 0 && $chequeReceived <= 0 && $upiBankReceived <= 0)) {
                    $cashReceived = $dbCash;
                    $chequeReceived = $dbCheque;
                    $upiBankReceived = $dbUpi;
                    $totalReceived = $dbTotal;
                }
                if ($dbDiscount > 0) {
                    $otherDiscount = max($otherDiscount, $dbDiscount);
                }
            }
        }
    }

    // --- Init TCPDF ---
    if (!defined('K_PATH_CACHE')) {
        $cacheDir = realpath(__DIR__ . '/../modal') . DIRECTORY_SEPARATOR . 'tcpdf_cache' . DIRECTORY_SEPARATOR;
        if (!file_exists($cacheDir)) @mkdir($cacheDir, 0777, true);
        if (!is_writable($cacheDir)) {
            $cacheDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'tcpdf_cache' . DIRECTORY_SEPARATOR;
            if (!file_exists($cacheDir)) @mkdir($cacheDir, 0777, true);
        }
        define('K_PATH_CACHE', $cacheDir);
    }

    if (!class_exists('TCPDF')) {
        require_once(__DIR__ . '/../pdfroutines/tcpdf/tcpdf.php');
    }

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(12, 5, 12);
    $pdf->SetAutoPageBreak(true, 5);
    $pdf->AddPage();

    // Header GSTIN
    $pdf->SetFont('helvetica', 'B', 8.5);
    $pdf->Cell(0, 4.5, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
    $pdf->Ln(1);

    // Company Logo & Header
    $logoPath = __DIR__ . "/../logo/logo.png";
    if (file_exists($logoPath) && (extension_loaded('gd') || extension_loaded('imagick'))) {
        $pdf->Image($logoPath, 12, $pdf->GetY(), 24, 0, '', '', '', false, 300, '', false, false, 0);
    }

    $pdf->SetY($pdf->GetY());
    $pdf->SetX(45);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 7, strtoupper($companyname), 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 9);
    $cmpInfo = "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\nMobile No: 6261832409, 7879615628";
    $pdf->SetX(90);
    $pdf->MultiCell(108, 4, $cmpInfo, 0, 'R', 0, 1);

    $pdf->Ln(4);
    $pdf->SetFont('helvetica', 'B', 13);
    $pdf->Cell(0, 6, 'INVOICE', 0, 1, 'C');
    $pdf->Ln(3);

    // 2-Column Info Header
    $pdf->SetFont('freesans', '', 9);
    $customerInfo =
        "Customer: " . $clientName . "\n" .
        "Email: " . $emailId . "\n" .
        "Mobile: " . $mobileNo . "\n" .
        "Invoice Address: " . $invAddress;

    $orderNoRef = !empty($psono) ? $psono : ($sono ?: 'N/A');
    $orderInfo =
        "Invoice No: " . $invoiceNo . "\n" .
        "Invoice Date: " . $invoiceDateFormatted . "\n" .
        "Order No: " . $orderNoRef . "\n" .
        "Order Date: " . (!empty($order['created_at']) ? date('d-M-Y', strtotime($order['created_at'])) : $invoiceDateFormatted) . "\n" .
        "Created By: " . strtoupper($createdByName);

    $startX = 12;
    $startY = $pdf->GetY();
    $columnWidth = 90;
    $columnGap = 6;

    $pdf->SetXY($startX, $startY);
    $pdf->MultiCell($columnWidth, 4.2, $customerInfo, 0, 'L', false, 1);
    $custEndY = $pdf->GetY();

    $pdf->SetXY($startX + $columnWidth + $columnGap, $startY);
    $pdf->MultiCell($columnWidth, 4.2, $orderInfo, 0, 'R', false, 1);
    $ordEndY = $pdf->GetY();

    $pdf->SetY(max($custEndY, $ordEndY) + 4);
    $pdf->SetX($startX);

    // --- ITEMS TABLE ---
    $pdf->SetFont('freesans', '', 8.5);
    $w = [8, 52, 20, 20, 20, 38, 28]; // total = 186 mm

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell($w[0], 6, '#', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 6, 'Product', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 6, 'Qty', 1, 0, 'C', 1);
    $pdf->Cell($w[3], 6, 'Rate', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 6, 'Discount', 1, 0, 'C', 1);
    $pdf->Cell($w[5], 6, 'Est. Delivery Date', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 6, 'Amount', 1, 1, 'C', 1);
    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $count = 1;
    foreach ($renderItems as $rit) {
        $pQty = $rit['quantity'];
        $pRate = $rit['rate'];
        $pDisc = $rit['discount_amount'];
        $pAmt = $rit['amount'];
        $isExtra = $rit['is_extra'];

        $isBoxPackingItem = !empty($rit['is_box_packing']) || ($rit['product_id'] ?? '') === 'BOX_PACKING' || stripos($rit['product_name'] ?? '', 'Box Packing') !== false;
        $qtyStr = $pQty . ($isBoxPackingItem ? ' Boxes' : ' Pcs');
        $rateStr = number_format($pRate, 2);
        $discStr = number_format($pDisc, 2);
        $amtStr = number_format($pAmt, 2);

        $cellHeights = [];
        $cellHeights[] = $pdf->getNumLines($count, $w[0]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($rit['product_name'], $w[1]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($qtyStr, $w[2]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($rateStr, $w[3]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($discStr, $w[4]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($rit['delivery_date'], $w[5]) * 4.8;
        $cellHeights[] = $pdf->getNumLines($amtStr, $w[6]) * 4.8;
        $maxHeight = max(5.5, max($cellHeights));

        // Highlight extra plant rows with light tint
        if ($isExtra) {
            $pdf->SetFillColor(250, 253, 250);
            $pdf->SetTextColor(4, 120, 87); // Greenish text for free offer
            $pdf->MultiCell($w[0], $maxHeight, $count, 1, 'C', 1, 0);
            $pdf->MultiCell($w[1], $maxHeight, $rit['product_name'], 1, 'L', 1, 0);
            $pdf->MultiCell($w[2], $maxHeight, $qtyStr, 1, 'C', 1, 0);
            $pdf->MultiCell($w[3], $maxHeight, '0.00', 1, 'C', 1, 0);
            $pdf->MultiCell($w[4], $maxHeight, '0.00', 1, 'C', 1, 0);
            $pdf->MultiCell($w[5], $maxHeight, $rit['delivery_date'], 1, 'C', 1, 0);
            $pdf->MultiCell($w[6], $maxHeight, '0.00', 1, 'C', 1, 1);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
        } else {
            $pdf->MultiCell($w[0], $maxHeight, $count, 1, 'C', 0, 0);
            $pdf->MultiCell($w[1], $maxHeight, $rit['product_name'], 1, 'L', 0, 0);
            $pdf->MultiCell($w[2], $maxHeight, $qtyStr, 1, 'C', 0, 0);
            $pdf->MultiCell($w[3], $maxHeight, $rateStr, 1, 'C', 0, 0);
            $pdf->MultiCell($w[4], $maxHeight, $discStr, 1, 'C', 0, 0);
            $pdf->MultiCell($w[5], $maxHeight, $rit['delivery_date'], 1, 'C', 0, 0);
            $pdf->MultiCell($w[6], $maxHeight, $amtStr, 1, 'C', 0, 1);
        }
        $count++;
    }

    // --- Total Row for Products ---
    $totalCropQtyOnly = 0;
    foreach ($renderItems as $rit) {
        if (empty($rit['is_box_packing']) && stripos($rit['product_name'] ?? '', 'Box Packing') === false) {
            $totalCropQtyOnly += $rit['quantity'];
        }
    }
    $totalDisplayQty = ($totalCropQtyOnly > 0) ? (number_format($totalCropQtyOnly) . ' Pcs') : (number_format($totalProductQty) . ' Pcs');

    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->Cell($w[0] + $w[1], 5.5, 'Total', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 5.5, $totalDisplayQty, 1, 0, 'C', 1);
    $pdf->Cell($w[3], 5.5, '', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 5.5, ($totalProductDiscount > 0 ? number_format($totalProductDiscount, 2) : '0.00'), 1, 0, 'C', 1);
    $pdf->Cell($w[5], 5.5, '', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 5.5, number_format($totalProductAmount, 2), 1, 1, 'C', 1);

    // --- FREIGHT DETAILS SECTION ---
    $pdf->Ln(3);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(242, 242, 242);

    // Detect if any freight discount rule is applied
    $appliedDiscountRule = '';
    $freightDiscountAmt = 0.0;
    $freightDiscountPct = 0.0;
    $freightFreeDist = 0.0;

    if (!empty($params['freight_rule'])) {
        $appliedDiscountRule = trim($params['freight_rule']);
    } elseif (!empty($params['freight_discount_plan'])) {
        $appliedDiscountRule = trim($params['freight_discount_plan']);
    }

    if (!empty($resolvedFreightGroups)) {
        foreach ($resolvedFreightGroups as $g) {
            $fd = floatval($g['free_distance'] ?? $g['freedistance'] ?? 0);
            $dp = floatval($g['discount_percent'] ?? $g['discPct'] ?? 0);
            $da = floatval($g['discount_amount'] ?? $g['discAmt'] ?? 0);
            $r = trim($g['remark'] ?? $g['remarks'] ?? '');

            if ($fd > $freightFreeDist) $freightFreeDist = $fd;
            if ($dp > $freightDiscountPct) $freightDiscountPct = $dp;
            if ($da > $freightDiscountAmt) $freightDiscountAmt = $da;

            if (empty($appliedDiscountRule)) {
                if ($dp > 0) {
                    $appliedDiscountRule = number_format($dp, 0) . "% Off" . ($da > 0 ? " (-Rs " . number_format($da, 2) . ")" : "");
                } elseif ($da > 0) {
                    $appliedDiscountRule = "Discount -Rs " . number_format($da, 2);
                } elseif ($fd > 0) {
                    $appliedDiscountRule = "Free Distance: " . number_format($fd, 2) . " KM";
                } elseif (!empty($r) && (stripos($r, 'discount') !== false || stripos($r, 'free freight') !== false || stripos($r, '% off') !== false) && stripos($r, '@@@') === false && stripos($r, 'Trip 1: Amt Rs') === false) {
                    $appliedDiscountRule = $r;
                }
            }
        }
    }

    if (empty($appliedDiscountRule) && (!empty($sono) || !empty($psono))) {
        $chkFc = $krfconn->prepare("SELECT remarks, freight_discountpercent, freight_discountamount FROM krf_freightcharges WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
        if ($chkFc) {
            $chkFc->bind_param("ssss", $psono, $psono, $sono, $sono);
            $chkFc->execute();
            $fcRes = $chkFc->get_result()->fetch_assoc();
            $chkFc->close();
            if ($fcRes) {
                $fcRem = trim($fcRes['remarks'] ?? '');
                $fcPct = floatval($fcRes['freight_discountpercent'] ?? 0);
                $fcAmt = floatval($fcRes['freight_discountamount'] ?? 0);
                if ($fcPct > $freightDiscountPct) $freightDiscountPct = $fcPct;
                if ($fcAmt > $freightDiscountAmt) $freightDiscountAmt = $fcAmt;

                if ($fcPct > 0) {
                    $appliedDiscountRule = number_format($fcPct, 0) . "% Off" . ($fcAmt > 0 ? " (-Rs " . number_format($fcAmt, 2) . ")" : "");
                } elseif ($fcAmt > 0) {
                    $appliedDiscountRule = "Discount -Rs " . number_format($fcAmt, 2);
                } elseif (!empty($fcRem) && (stripos($fcRem, 'discount') !== false || stripos($fcRem, 'free freight') !== false || stripos($fcRem, '% off') !== false) && stripos($fcRem, '@@@') === false && stripos($fcRem, 'Trip 1: Amt Rs') === false) {
                    $appliedDiscountRule = $fcRem;
                }
            }
        }
    }

    $hasRealFreightDiscount = !empty($appliedDiscountRule) && ($freightDiscountAmt > 0 || $freightDiscountPct > 0 || $freightFreeDist > 0 || !empty($params['freight_rule']) || !empty($params['freight_discount_plan']));

    // Section Header Row: Freight Details and Transport Type (no discount text in this top row!)
    $freightHeaderTitle = ' Freight Details  |  Transport Type: ' . $transportType;
    $pdf->Cell(186, 5.5, $freightHeaderTitle, 1, 1, 'L', 1);

    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $totalFreightRendered = 0;
    $hasValidDeliveryAddress = !empty($resolvedFreightGroups);

    if (!$hasValidDeliveryAddress) {
        $missingNotice = "Freight charges will be applicable as per the distance to which the plants are to be delivered. Location to be provided by the farmer.";
        $fHeight = max($pdf->getNumLines($missingNotice, 158) * 4.2, 5.5);
        $pdf->MultiCell(158, $fHeight, $missingNotice, 1, 'L', 0, 0);
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->MultiCell(28, $fHeight, '0.00', 1, 'C', 0, 1);
        $pdf->SetFont('freesans', '', 8);
    } else {
        $hasFreightDiscount = !empty($appliedDiscountRule);
        foreach ($resolvedFreightGroups as $grp) {
            $lAddr = trim($grp['delivery_address'] ?? $grp['address'] ?? '');
            $lTotalDist = floatval($grp['total_distance'] ?? $grp['totaldistance'] ?? 0);
            $lFreeDist = floatval($grp['free_distance'] ?? $grp['freedistance'] ?? 0);
            $lPayableDist = floatval($grp['payable_distance'] ?? $grp['distance_km'] ?? $grp['distance'] ?? 0);
            $lRemark = trim($grp['remark'] ?? $grp['remarks'] ?? '');
            $grpPickupType = trim($grp['pickuptype'] ?? $grp['transport_type'] ?? $transportType);

            if (stripos($grpPickupType, 'pickup') !== false) {
                $routeText = "Self Pickup (Ex-Nursery / Farm Gate)";
            } elseif (stripos($grpPickupType, 'box') !== false) {
                $boxPerPack = intval($grp['boxperpack'] ?? 0);
                if ($boxPerPack <= 0) $boxPerPack = 500;
                $chargesPerBox = floatval($grp['chargesperbox'] ?? 0);
                if ($chargesPerBox <= 0) $chargesPerBox = 70.00;
                $boxCount = intval($grp['noofbox'] ?? 0);
                if ($boxCount <= 0 && $totalProductQty > 0) {
                    $boxCount = ceil($totalProductQty / $boxPerPack);
                }
                if ($boxCount <= 0) $boxCount = 1;

                $boxInfoText = "Boxes: " . $boxCount . " (" . $boxPerPack . " plants/box) @ Rs " . number_format($chargesPerBox, 2) . "/box";
                $routeText = "Karjee  →  " . ($lAddr ?: 'Designated City Destination') . "\n" . $boxInfoText;
            } else {
                $distText = "";
                if ($lFreeDist > 0) {
                    $tDist = $lTotalDist > 0 ? $lTotalDist : ($lPayableDist + $lFreeDist);
                    $distText = " (Total Distance: " . number_format($tDist, 2) . " km | Free: " . number_format($lFreeDist, 2) . " km | Payable: " . number_format($lPayableDist, 2) . " km)";
                } elseif ($lPayableDist > 0 || $lTotalDist > 0) {
                    $pDist = $lPayableDist > 0 ? $lPayableDist : $lTotalDist;
                    $distText = " (Distance: " . number_format($pDist, 2) . " km)";
                }
                $routeText = "Karjee  →  " . ($lAddr ?: 'Client Delivery Destination') . $distText;
            }

            $extraMeta = [];
            $vNo = trim($grp['vehicle_no'] ?? $grp['vehicleno'] ?? '');
            $vName = trim($grp['vehicle_name'] ?? $grp['vehiclename'] ?? '');
            $tName = trim($grp['transporter_name'] ?? $grp['vehicleownername'] ?? '');
            $dName = trim($grp['driver_name'] ?? $grp['drivername'] ?? '');
            $dMobile = trim($grp['driver_mobile'] ?? $grp['driver_mobile_no'] ?? $grp['drivercontactno'] ?? '');
            $tId = trim($grp['trip_id'] ?? $grp['trip_no'] ?? '');

            if (!empty($vName) && !empty($vNo)) {
                $extraMeta[] = "Vehicle: " . $vName . " (" . $vNo . ")";
            } elseif (!empty($vNo)) {
                $extraMeta[] = "Vehicle: " . $vNo;
            } elseif (!empty($vName)) {
                $extraMeta[] = "Vehicle: " . $vName;
            }
            if (!empty($tName)) {
                $extraMeta[] = "Transporter: " . $tName;
            }
            if (!empty($dName)) {
                $extraMeta[] = "Driver: " . $dName . (!empty($dMobile) ? " ({$dMobile})" : "");
            }
            if (!empty($tId)) {
                $extraMeta[] = "Trip: " . $tId;
            }

            if (!empty($extraMeta)) {
                $routeText .= "\n" . implode('  |  ', $extraMeta);
            }

            if (!empty($lRemark) && stripos($lRemark, '@@@') === false && stripos($lRemark, 'Trip 1: Amt Rs') === false && stripos($lRemark, 'Freight Discount Applied') === false) {
                $routeText .= "\nRemark: " . $lRemark;
            }

            $locAmt = floatval($grp['payable_freight_amount'] ?? $grp['freight_amount'] ?? 0);
            $totalFreightRendered += $locAmt;
            $amtDisplay = number_format($locAmt, 2);

            $fHeight = max($pdf->getNumLines($routeText, 158) * 4.2, 5.5);
            $pdf->MultiCell(158, $fHeight, $routeText, 1, 'L', 0, 0);
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->MultiCell(28, $fHeight, $amtDisplay, 1, 'C', 0, 1);
            $pdf->SetFont('freesans', '', 8);
        }

        if ($hasRealFreightDiscount && !empty($appliedDiscountRule)) {
            // Freight Discount row displayed AFTER freight details box
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->SetFillColor(245, 248, 255);
            $pdf->SetTextColor(30, 64, 175);
            $discountBoxText = '  Freight Discount: ' . $appliedDiscountRule;
            if ($freightDiscountAmt > 0) {
                $discountBoxText .= ' (Savings: Rs. ' . number_format($freightDiscountAmt, 2) . ')';
            }
            $dHeight = max($pdf->getNumLines($discountBoxText, 186) * 3.8, 5.2);
            $pdf->MultiCell(186, $dHeight, $discountBoxText, 1, 'L', 1, 1);
            $pdf->SetTextColor(0, 0, 0);

            // Informational Note (Neutral dark text, NOT RED!)
            $discountNotice = "Note: Discounted freight is applicable only when the dispatched quantity and distance comply with the agreed freight rule. Any change in quantity or distance at dispatch will make the discount invalid, and freight will be charged at the applicable rate.";
            $pdf->SetFont('freesans', '', 7.2);
            $pdf->SetTextColor(70, 70, 70);    // Neutral dark grey text
            $pdf->SetFillColor(250, 250, 250); // Soft grey fill
            $pdf->SetDrawColor(210, 210, 210); // Subtle border
            $nHeight = max($pdf->getNumLines($discountNotice, 186) * 3.5, 4.8);
            $pdf->MultiCell(186, $nHeight, $discountNotice, 1, 'L', 1, 1);
            // Reset colors
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetFont('freesans', '', 8);
        }
    }

    // --- TOTALS & BANK DETAILS ---
    $bankDetails = [
        "Bank Name" => "HDFC",
        "Account No" => "50200092846879",
        "IFSC Code" => "HDFC0000917",
        "Branch" => "AMBIKAPUR",
        "UPI ID" => "8319620998-6@ybl",
    ];

    $pdf->Ln(3);
    $startY = $pdf->GetY();

    $pdf->SetFont('freesans', 'B', 9);
    $pdf->SetX(12);
    $pdf->Cell(62, 4.5, "KARJEE FARM BANK DETAILS", 0, 1, 'L');
    $pdf->SetFont('freesans', '', 7.5);
    $pdf->SetX(12);
    $pdf->MultiCell(
        62,
        3.8,
        "Bank: {$bankDetails['Bank Name']}\nAccount No: {$bankDetails['Account No']}\nIFSC: {$bankDetails['IFSC Code']}\nBranch: {$bankDetails['Branch']}\nUPI ID: {$bankDetails['UPI ID']}",
        0,
        'L'
    );
    $bankEndY = $pdf->GetY();

    // QR Code
    $qrCodePath = __DIR__ . "/../../img/karjeefarmqrcode.png";
    if (!file_exists($qrCodePath)) $qrCodePath = __DIR__ . "/../../img/karjeefarmqrcode.jpeg";
    if (!file_exists($qrCodePath)) $qrCodePath = dirname(dirname(__DIR__)) . "/img/karjeefarmqrcode.jpeg";

    $qrEndY = $startY;
    if (file_exists($qrCodePath)) {
        $qrSize = 25;
        $qrX = 78;
        $imgType = (stripos($qrCodePath, '.png') !== false) ? 'PNG' : 'JPEG';
        $pdf->Image($qrCodePath, $qrX, $startY + 0.5, $qrSize, $qrSize, $imgType, '', '', false, 300, '', false, false, 0);
        $pdf->SetXY($qrX, $startY + 0.5 + $qrSize);
        $pdf->SetFont('freesans', 'B', 7);
        $pdf->Cell($qrSize, 3.5, 'Scan & Pay', 0, 0, 'C');
        $qrEndY = $startY + 0.5 + $qrSize + 3.5;
    }

    $finalSubtotal = (float)$totalProductAmount + (float)$totalFreightRendered;
    $netPayable = max(0.00, round($finalSubtotal - $otherDiscount, 2));
    $excessReceived = round($totalReceived - $netPayable, 2);
    $hasAdvance = ($excessReceived > 10.00);
    $advanceAmount = $hasAdvance ? $excessReceived : 0.00;
    $balance = max(0.00, round($netPayable - $totalReceived, 2));

    $totalsX = 110;
    $lblW = 54;
    $valW = 34;
    $totW = $lblW + $valW; // 88mm

    $pdf->SetY($startY);
    $pdf->SetFont('freesans', '', 8.5);

    // Row 1: Subtotal
    $pdf->SetX($totalsX);
    $pdf->Cell($lblW, 5, 'Subtotal', 1, 0, 'R');
    $pdf->Cell($valW, 5, number_format($finalSubtotal, 2), 1, 1, 'R');

    // Row 1b: Special Discount (if > 0)
    if ($otherDiscount > 0.00) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', '', 8.5);
        $pdf->Cell($lblW, 5, 'Special Discount', 1, 0, 'R');
        $pdf->Cell($valW, 5, '-' . number_format($otherDiscount, 2), 1, 1, 'R');
    }

    // Row 2: Total Received
    $pdf->SetX($totalsX);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell($lblW, 5, 'Total Received', 1, 0, 'R');
    $pdf->Cell($valW, 5, number_format($totalReceived, 2), 1, 1, 'R');

    // Row 3: Advance (if extra amount > 10 RS)
    if ($hasAdvance) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', 'B', 8.5);
        $pdf->SetTextColor(21, 128, 61); // Green #15803d
        $pdf->SetFillColor(240, 253, 244); // Light green #f0fdf4
        $pdf->Cell($lblW, 5, 'Advance (Excess Amount)', 1, 0, 'R', 1);
        $pdf->Cell($valW, 5, number_format($advanceAmount, 2), 1, 1, 'R', 1);
        $pdf->SetTextColor(0, 0, 0); // Reset color
    }

    // Row 4: Balance
    $pdf->SetX($totalsX);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell($lblW, 5, 'Balance', 1, 0, 'R', 1);
    $pdf->Cell($valW, 5, number_format($balance, 2), 1, 1, 'R', 1);

    // Row 5: Breakdown (Cash => ..., Check => ..., UPI/Bank => ...) displayed after Balance row
    $breakdownParts = [];
    if (floatval($cashReceived) > 0) {
        $breakdownParts[] = "Cash => " . number_format($cashReceived, 2);
    }
    if (floatval($chequeReceived) > 0) {
        $breakdownParts[] = "Check => " . number_format($chequeReceived, 2);
    }
    if (floatval($upiBankReceived) > 0) {
        $breakdownParts[] = "UPI/Bank => " . number_format($upiBankReceived, 2);
    }
    if ($otherDiscount > 0.00) {
        $breakdownParts[] = "Discount => " . number_format($otherDiscount, 2);
    }

    if (!empty($breakdownParts)) {
        $pdf->SetX($totalsX);
        $pdf->SetFont('freesans', '', 7.2);
        $pdf->SetFillColor(248, 249, 250);
        $breakdownText = implode('  |  ', $breakdownParts);
        $pdf->Cell($totW, 4.5, $breakdownText, 1, 1, 'C', 1);
    }
    $totalsEndY = $pdf->GetY();

    $maxEndY = max($bankEndY, $totalsEndY, $qrEndY);
    $pdf->SetY($maxEndY + 2.5);

    // Payable Amount in Words
    $payableInWords = getIndianCurrencyInWords(max(0, (float)$balance));
    $wordsText = "Payable Amount in Words: " . $payableInWords;
    if ($hasAdvance) {
        $wordsText .= "  (Advance: Rs. " . number_format($advanceAmount, 2) . ")";
    }
    $pdf->SetFont('freesans', 'B', 8);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->MultiCell(186, 5, $wordsText, 1, 'L', 1, 1);
    $pdf->SetFont('freesans', '', 8);

    $pdf->Ln(2);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->Cell(0, 4.5, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->Ln(0.5);
    $pdf->SetFont('freesans', '', 6.8);
    $terms = "1. 50% advance payment is mandatory at the time of placing the order, and the remaining 50% amount must be fully cleared before plant delivery.\n" .
             "2. Karjee Farm shall not be held responsible for any damage, loss, or deterioration of plants during transit or transportation.\n" .
             "3. If the customer receives plants after the scheduled delivery time, Karjee Farm will not be responsible for plant quality deterioration thereafter.\n" .
             "4. Crates supplied with the plant delivery must be returned immediately in the same transport vehicle.\n" .
             "5. Plant delivery will be scheduled after full payment is received, subject to vehicle availability and designated delivery location.\n" .
             "6. Karjee Farm reserves the right to cancel the order if payments are not made within the agreed timeframe.\n" .
             "7. All payments must be made strictly in accordance with the mutually agreed payment terms.\n" .
             "8. Provisional sales orders are valid for 3 days only. If confirmation and advance payment are not received within 3 days, this order will be automatically cancelled.\n" .
             "9. Orders cannot be cancelled under any circumstances once seed sowing has been initiated.\n" .
             "10. Estimated delivery dates may vary by 4-5 days due to weather conditions or other unforeseen circumstances.";
    $pdf->MultiCell(0, 3.2, $terms, 0, 'L');

    $docTitle = !empty($invoiceNo) ? str_replace('/', '_', $invoiceNo) : (!empty($sono) ? $sono : $psono);

    if ($mode == 'F' && !empty($save_path)) {
        $pdf->Output($save_path, 'F');
        return $save_path;
    } elseif ($mode == 'S') {
        return $pdf->Output("{$docTitle}.pdf", 'S');
    } elseif ($mode == 'I') {
        $pdf->Output("{$docTitle}.pdf", 'I');
    } else {
        $pdf->Output("{$docTitle}.pdf", 'D');
    }
}




