<?php
// Order editor helpers only. Retained function bodies preserve the supplied logic.
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

    $lginfo = loginuserinfo($authconn, "", "loginid", $order['created_by']);
    $created_by = $lginfo[0]['loginname'] ?? 'System';

    $items = getProvisionalSalesOrderItems($krfconn, $psono);
    $items = array_values(array_filter($items, function($it) {
        $st = strtolower(trim($it['status'] ?? ''));
        $qty = floatval($it['quantity'] ?? 0);
        $tedit = trim($it['tedit'] ?? '');
        return !in_array($st, ['cancelled', 'cancel']) && $tedit === '' && $qty > 0;
    }));
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
    foreach ($paymentrcv as $p) {
        $ttlrcv += (float) ($p['advanceamount'] ?? 0);
    }
    $balance = floatval($ttlamount) - floatval($ttlrcv);

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
        require_once(__DIR__ . '/../../pdfroutines/tcpdf/tcpdf.php');
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
    $logoPath = __DIR__ . "/../../logo/logo.png";
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

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'PROVISIONAL ORDER', 0, 1, 'C');
    $pdf->Ln(3);

    // --- ORDER & CUSTOMER INFO ---
    $pdf->SetFont('freesans', '', 9);

    $invoiceAddress = trim($order['delivery_address'] ?? '');
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
        "Status: " . ($order['status'] ?? 'N/A') . "\n" .
        "Created By: " . strtoupper($created_by);

    // A4 usable width with 12 mm margins = 186 mm
    $startX = 12;
    $startY = $pdf->GetY();
    $columnWidth = 90;
    $columnGap = 6;

    // Customer information
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

    // Order information
    $pdf->SetXY($startX + $columnWidth + $columnGap, $startY);
    $pdf->MultiCell(
        $columnWidth,
        4.2,
        $orderInfo,
        0,
        'L',
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
    $pdf->Cell($w[5], 6, 'Delivery Date', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 6, 'Amount', 1, 1, 'C', 1);
    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $count = 1;
    $totalProductQty = 0;
    $totalProductAmount = 0;
    foreach ($items as $item) {
        $pQty = (float)($item['quantity'] ?? 0);
        $pRate = (float)($item['rate'] ?? 0);
        $pDisc = (float)($item['discount_amount'] ?? 0);
        $pAmt = (float)($item['amount'] ?? ($pQty * $pRate - $pDisc));

        $totalProductQty += $pQty;
        $totalProductAmount += $pAmt;

        $qtyStr = $pQty . ' Pcs';
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
    $pdf->Cell($w[2], 5.5, $totalProductQty . ' Pcs', 1, 0, 'C', 1);
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
    $transportType = 'Company Delivery';
    $stmtLoc = $krfconn->prepare("SELECT deliveryaddress, dest_city, dest_district, dest_state, totaldistance, freedistance, distance, remark, deliverycharges, freightamt, totalamount, transporttype, pickuptype, productid, delivery_datetime FROM krf_deliveryaddress_location WHERE psono = ? AND (tedit IS NULL OR tedit = '') ORDER BY id ASC");
    if ($stmtLoc) {
        $stmtLoc->bind_param("s", $psono);
        $stmtLoc->execute();
        $resLoc = $stmtLoc->get_result();
        while ($lRow = $resLoc->fetch_assoc()) {
            $deliveryLocs[] = $lRow;
            if (!empty($lRow['pickuptype'])) {
                $transportType = trim($lRow['pickuptype']);
                if($transportType=="delivered"){
                    $transportType = 'Company Delivery';
                }else if($transportType=="pickup"){
                    $transportType = 'Pickup Self';
                }else{
                    $transportType = '';
                }
            }
        }
        $stmtLoc->close();
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
            }
        }
    }

    $hasValidDeliveryAddress = !empty($groupedLocs);

    // Extra space before Freight Details section
    $pdf->Ln(3);

    // Section Header Row: Freight Details and Transport Type (no Amount text in this row)
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(242, 242, 242);
    $headerTitle = ' Freight Details  |  Transport Type: ' . $transportType;
    $pdf->Cell(186, 5.5, $headerTitle, 1, 1, 'L', 1);

    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    if (!$hasValidDeliveryAddress) {
        // Missing delivery address -> Advisory notice with 0.00 amount
        $missingNotice = "Freight charges will be applicable as per the distance to which the plants are to be delivered. Location to be provided by the farmer.";
        $fHeight = max($pdf->getNumLines($missingNotice, 158) * 4.2, 5.5);
        $pdf->MultiCell(158, $fHeight, $missingNotice, 1, 'L', 0, 0);
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->MultiCell(28, $fHeight, '0.00', 1, 'C', 0, 1);
        $pdf->SetFont('freesans', '', 8);
    } else {
        $hasFreightDiscount = false;
        $grpCount = count($groupedLocs);
        foreach ($groupedLocs as $grp) {
            $lAddr = $grp['address'];
            $lTotalDist = (float)($grp['totaldistance'] ?? 0);
            $lFreeDist = (float)($grp['freedistance'] ?? 0);
            $lPayableDist = (float)($grp['distance'] ?? 0);
            $lRemark = trim($grp['remark'] ?? '');

            if ($lFreeDist > 0 || !empty($lRemark)) {
                $hasFreightDiscount = true;
            }

            $distText = "";
            if ($lFreeDist > 0) {
                $tDist = $lTotalDist > 0 ? $lTotalDist : ($lPayableDist + $lFreeDist);
                $distText = " (Total Distance: " . number_format($tDist, 2) . " km | Free: " . number_format($lFreeDist, 2) . " km | Payable: " . number_format($lPayableDist, 2) . " km)";
            } elseif ($lPayableDist > 0 || $lTotalDist > 0) {
                $pDist = $lPayableDist > 0 ? $lPayableDist : $lTotalDist;
                $distText = " (Distance: " . number_format($pDist, 2) . " km)";
            }

            $routeText = "Karjee  →  " . $lAddr . $distText;
            if (!empty($lRemark)) {
                $routeText .= "\nNarration: " . $lRemark;
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
            $amtDisplay = number_format($locAmt, 2);

            $fHeight = max($pdf->getNumLines($routeText, 158) * 4.2, 5.5);
            $pdf->MultiCell(158, $fHeight, $routeText, 1, 'L', 0, 0);
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->MultiCell(28, $fHeight, $amtDisplay, 1, 'C', 0, 1);
            $pdf->SetFont('freesans', '', 8);
        }

        if ($hasFreightDiscount) {
            $discountNotice = "Note: Discounted freight is applicable only when the dispatched quantity and distance comply with the agreed freight rule. Any change in quantity or distance at dispatch will make the discount invalid, and freight will be charged at the applicable rate.";
            $pdf->SetFont('freesans', '', 7.5);
            $pdf->SetTextColor(180, 20, 20); // Dark Red note text
            $pdf->SetFillColor(245, 248, 255); // Soft blue-tinted fill
            $pdf->SetDrawColor(0, 102, 204);   // Blue border line
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
        $qrSize = 25;
        $qrX = 86;
        $imgType = (stripos($qrCodePath, '.png') !== false) ? 'PNG' : 'JPEG';
        $pdf->Image($qrCodePath, $qrX, $startY + 0.5, $qrSize, $qrSize, $imgType, '', '', false, 300, '', false, false, 0);
        $pdf->SetXY($qrX, $startY + 0.5 + $qrSize);
        $pdf->SetFont('freesans', 'B', 7);
        $pdf->Cell($qrSize, 3.5, 'Scan & Pay', 0, 0, 'C');
        $qrEndY = $startY + 0.5 + $qrSize + 3.5;
    }

    $pdf->SetY($startY);
    $pdf->SetFont('freesans', '', 8.5);
    $pdf->SetX(128);
    $pdf->Cell(30, 5, 'Subtotal', 1, 0, 'R');
    $pdf->Cell(28, 5, number_format($ttlamount, 2), 1, 1, 'R');
    $pdf->SetX(128);
    $pdf->Cell(30, 5, 'Discount', 1, 0, 'R');
    $pdf->Cell(28, 5, number_format($ttldiscount, 2), 1, 1, 'R');
    $pdf->SetX(128);
    $pdf->Cell(30, 5, 'Received', 1, 0, 'R');
    $pdf->Cell(28, 5, number_format($ttlrcv, 2), 1, 1, 'R');
    $pdf->SetX(128);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 5, 'Balance', 1, 0, 'R', 1);
    $pdf->Cell(28, 5, number_format($balance, 2), 1, 1, 'R', 1);
    $totalsEndY = $pdf->GetY();

    $maxEndY = max($bankEndY, $totalsEndY, $qrEndY);
    $pdf->SetY($maxEndY + 2.5);

    // Payable Amount in Words
    $payableInWords = getIndianCurrencyInWords(max(0, (float)$balance));
    $pdf->SetFont('freesans', 'B', 8);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->MultiCell(186, 5, "Payable Amount in Words: " . $payableInWords, 1, 'L', 1, 1);
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

    $lginfo = loginuserinfo($authconn, "", "loginid", $order['created_by']);
    $created_by = $lginfo[0]['loginname'] ?? 'System';

    // Fetch final sales order items
    $items = getFinalSalesOrderItems($krfconn, $sono);
    $items = array_values(array_filter($items, function($it) {
        $st = strtolower(trim($it['status'] ?? ''));
        $qty = floatval($it['quantity'] ?? 0);
        return !in_array($st, ['cancelled', 'cancel']) && $qty > 0;
    }));

    // Fetch payments linked to this order (using psono if available, or sono)
    $paymentrcv = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $order['psono']);

    $ttlamount = (float) ($order['total_amount'] ?? 0);
    $ttldiscount = 0; // Final order usually has totals pre-calculated
    $ttltax = (float) ($order['tax_amount'] ?? 0);
    $grandtotal = (float) ($order['grand_total'] ?? 0) + $ttlfreight;

    $ttlrcv = 0;
    foreach ($paymentrcv as $p) {
        $ttlrcv += (float) $p['advanceamount'];
    }
    $balance = $grandtotal - $ttlrcv;

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
        require_once(__DIR__ . '/../../pdfroutines/tcpdf/tcpdf.php');
    }

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(12, 5, 12);
    $pdf->SetAutoPageBreak(true, 5);
    $pdf->AddPage();

    // --- Header ---
    $pdf->SetFont('helvetica', 'B', 8.5);
    $pdf->Cell(0, 4.5, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
    $pdf->Ln(1);

    $logoPath = __DIR__ . "/../../logo/logo.png";
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

    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 6, 'SALES ORDER', 0, 1, 'C');
    $pdf->Ln(3);

    // --- INFO ---
    $pdf->SetFont('freesans', '', 9);
    $rightInfo = "Customer: {$order['clientname']}\n" .
        "Email: " . ($order['emailid'] != '' ? $order['emailid'] : 'N/A') . "\n" .
        "Mobile: " . ($order['mobileno'] != '' ? $order['mobileno'] : 'N/A') . "\n" .
        "Address: " . ($order['delivery_address'] != '' ? $order['delivery_address'] : 'N/A');

    $leftInfo = "Sales Order No: {$order['sono']}\n" .
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
    $pdf->MultiCell($columnWidth, 4.2, $leftInfo, 0, 'L', false, 1);
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
    $pdf->Cell($w[5], 6, 'Delivery Date', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 6, 'Amount', 1, 1, 'C', 1);
    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    $count = 1;
    $totalProductQty = 0;
    $totalProductAmount = 0;
    $totalProductDiscount = 0;
    foreach ($items as $item) {
        $pQty = (float)($item['quantity'] ?? 0);
        $pRate = (float)($item['rate'] ?? 0);
        $pDisc = (float)($item['discount_amount'] ?? 0);
        $pAmt = (float)($item['amount'] ?? ($pQty * $pRate - $pDisc));

        $totalProductQty += $pQty;
        $totalProductAmount += $pAmt;
        $totalProductDiscount += $pDisc;

        $qtyStr = $pQty . ' Pcs';
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
    $pdf->Cell($w[2], 5.5, $totalProductQty . ' Pcs', 1, 0, 'C', 1);
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
    $transportType = 'Company Delivery';
    $stmtLoc = $krfconn->prepare("SELECT deliveryaddress, dest_city, dest_district, dest_state, totaldistance, freedistance, distance, remark, deliverycharges, freightamt, totalamount, transporttype,pickuptype, productid, delivery_datetime FROM krf_deliveryaddress_location WHERE (sono = ? OR psono = ?) AND (tedit IS NULL OR tedit = '') ORDER BY id ASC");
    if ($stmtLoc) {
        $psonoVal = $order['psono'] ?? '';
        $stmtLoc->bind_param("ss", $sono, $psonoVal);
        $stmtLoc->execute();
        $resLoc = $stmtLoc->get_result();
        while ($lRow = $resLoc->fetch_assoc()) {
            $deliveryLocs[] = $lRow;
            
            if (!empty($lRow['pickuptype'])) {
                $transportType = trim($lRow['pickuptype']);
                if($transportType=="delivered"){
                    $transportType = 'Company Delivery';
                }else if($transportType=="pickup"){
                    $transportType = 'Pickup Self';
                }else{
                    $transportType = '';
                }
            }
        }
        $stmtLoc->close();
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
            }
        }
    }

    $hasValidDeliveryAddress = !empty($groupedLocs);

    // Extra space before Freight Details section
    $pdf->Ln(3);

    // Section Header Row: Freight Details and Transport Type (no Amount text in this row)
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(242, 242, 242);
    $headerTitle = ' Freight Details  |  Transport Type: ' . $transportType;
    $pdf->Cell(186, 5.5, $headerTitle, 1, 1, 'L', 1);

    $pdf->SetFont('freesans', '', 8);
    $pdf->SetFillColor(255, 255, 255);

    if (!$hasValidDeliveryAddress) {
        // Missing delivery address -> Advisory notice with 0.00 amount
        $missingNotice = "Freight charges will be applicable as per the distance to which the plants are to be delivered. Location to be provided by the farmer.";
        $fHeight = max($pdf->getNumLines($missingNotice, 158) * 4.2, 5.5);
        $pdf->MultiCell(158, $fHeight, $missingNotice, 1, 'L', 0, 0);
        $pdf->SetFont('freesans', 'B', 8);
        $pdf->MultiCell(28, $fHeight, '0.00', 1, 'C', 0, 1);
        $pdf->SetFont('freesans', '', 8);
    } else {
        $hasFreightDiscount = false;
        $grpCount = count($groupedLocs);
        foreach ($groupedLocs as $grp) {
            $lAddr = $grp['address'];
            $lTotalDist = (float)($grp['totaldistance'] ?? 0);
            $lFreeDist = (float)($grp['freedistance'] ?? 0);
            $lPayableDist = (float)($grp['distance'] ?? 0);
            $lRemark = trim($grp['remark'] ?? '');

            if ($lFreeDist > 0 || !empty($lRemark)) {
                $hasFreightDiscount = true;
            }

            $distText = "";
            if ($lFreeDist > 0) {
                $tDist = $lTotalDist > 0 ? $lTotalDist : ($lPayableDist + $lFreeDist);
                $distText = " (Total Distance: " . number_format($tDist, 2) . " km | Free: " . number_format($lFreeDist, 2) . " km | Payable: " . number_format($lPayableDist, 2) . " km)";
            } elseif ($lPayableDist > 0 || $lTotalDist > 0) {
                $pDist = $lPayableDist > 0 ? $lPayableDist : $lTotalDist;
                $distText = " (Distance: " . number_format($pDist, 2) . " km)";
            }

            $routeText = "Karjee  →  " . $lAddr . $distText;
            if (!empty($lRemark)) {
                $routeText .= "\nNarration: " . $lRemark;
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
            $amtDisplay = number_format($locAmt, 2);

            $fHeight = max($pdf->getNumLines($routeText, 158) * 4.2, 5.5);
            $pdf->MultiCell(158, $fHeight, $routeText, 1, 'L', 0, 0);
            $pdf->SetFont('freesans', 'B', 8);
            $pdf->MultiCell(28, $fHeight, $amtDisplay, 1, 'C', 0, 1);
            $pdf->SetFont('freesans', '', 8);
        }

        if ($hasFreightDiscount) {
            $discountNotice = "Note: Discounted freight is applicable only when the dispatched quantity and distance comply with the agreed freight rule. Any change in quantity or distance at dispatch will make the discount invalid, and freight will be charged at the applicable rate.";
            $pdf->SetFont('freesans', '', 7.5);
            $pdf->SetTextColor(180, 20, 20); // Dark Red note text
            $pdf->SetFillColor(245, 248, 255); // Soft blue-tinted fill
            $pdf->SetDrawColor(0, 102, 204);   // Blue border line
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
        $qrSize = 25;
        $qrX = 86;
        $imgType = (stripos($qrCodePath, '.png') !== false) ? 'PNG' : 'JPEG';
        $pdf->Image($qrCodePath, $qrX, $startY + 0.5, $qrSize, $qrSize, $imgType, '', '', false, 300, '', false, false, 0);
        $pdf->SetXY($qrX, $startY + 0.5 + $qrSize);
        $pdf->SetFont('freesans', 'B', 7);
        $pdf->Cell($qrSize, 3.5, 'Scan & Pay', 0, 0, 'C');
        $qrEndY = $startY + 0.5 + $qrSize + 3.5;
    }

    $pdf->SetY($startY);
    $pdf->SetFont('freesans', '', 8.5);
    $pdf->SetX(128);
    $pdf->Cell(30, 5, 'Subtotal', 1, 0, 'R');
    $pdf->Cell(28, 5, number_format($grandtotal - $ttltax, 2), 1, 1, 'R');
    $pdf->SetX(128);
    $pdf->Cell(30, 5, 'Tax', 1, 0, 'R');
    $pdf->Cell(28, 5, number_format($ttltax, 2), 1, 1, 'R');
    $pdf->SetX(128);
    $pdf->Cell(30, 5, 'Grand Total', 1, 0, 'R', 0, '', 0, false, 'T', 'M');
    $pdf->Cell(28, 5, number_format($grandtotal, 2), 1, 1, 'R');
    $pdf->SetX(128);
    $pdf->Cell(30, 5, 'Received', 1, 0, 'R');
    $pdf->Cell(28, 5, number_format($ttlrcv, 2), 1, 1, 'R');
    $pdf->SetX(128);
    $pdf->SetFont('freesans', 'B', 8.5);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->Cell(30, 5, 'Balance', 1, 0, 'R', 1);
    $pdf->Cell(28, 5, number_format($balance, 2), 1, 1, 'R', 1);
    $totalsEndY = $pdf->GetY();

    $maxEndY = max($bankEndY, $totalsEndY, $qrEndY);
    $pdf->SetY($maxEndY + 2.5);

    // Payable Amount in Words
    $payableInWords = getIndianCurrencyInWords(max(0, (float)$balance));
    $pdf->SetFont('freesans', 'B', 8);
    $pdf->SetFillColor(245, 245, 245);
    $pdf->MultiCell(186, 5, "Payable Amount in Words: " . $payableInWords, 1, 'L', 1, 1);
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
