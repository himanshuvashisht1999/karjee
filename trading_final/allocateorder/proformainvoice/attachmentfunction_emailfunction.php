<?php 
error_reporting(E_ALL & ~E_WARNING); 
ini_set('display_errors', 1); 
require('../../../pdfroutines/tcpdf/tcpdf.php');
function createproformainvoice_new($itemswisedetails,$createdby,$orderdetails,$companyname,$advancepayment,$bankDetails ) {
    $order = $orderdetails; 
    $created_by = $createdby;
    $items = $itemswisedetails; 
    $maxReadyDate = null;
    foreach ($itemswisedetails as $row) {
        if (!empty($row['ready_date'])) {
            $dt = strtotime($row['ready_date']);
            if ($dt && ($maxReadyDate === null || $dt > $maxReadyDate)) {
                $maxReadyDate = $dt;
            }
        }
    }
    $maxformattedDate = $maxReadyDate? date('d-m-Y', $maxReadyDate): ''; 
    $ttlamount = 0; 
    $ttldiscount = 0;      
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(15, 5, 15);
    $pdf->AddPage();
    // --- Header: GSTIN on top ---
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L'); // L = left align
    $pdf->Ln(2); // small spacing after GSTIN
    // --- LOGO + COMPANY NAME ---
    $logoPath = "../../../img/logo/logo.png";
    if(file_exists($logoPath)){
        $pdf->Image($logoPath, 15, $pdf->GetY(), 30, 0, '', '', '', false, 300, '', false, false, 0);
    }
    // Company name on the right
    // Move cursor to the right for company name
    $pdf->SetY($pdf->GetY()); // ensure we are aligned vertically with logo
    $pdf->SetX(55); // right of logo
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, strtoupper($companyname), 0, 1, 'R'); // company name on right
    $pdf->SetFont('helvetica', '', 11);
    $cmpInfo = "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\n" .
           "Mobile No: 6261832409, 7879615628";
    // X-position for right side
    $pdf->SetX(110); // adjust according to page width and left cell
    $pdf->MultiCell(90, 5, $cmpInfo, 0, 'R', 0, 1); // 0=no border, 'R'=right align, 0=no fill, 1=move to next line    
    // Document title below
    $invtitle="REVISED PROFORMA INVOICE ". date("d-m-Y");
    $pdf->Ln(12); // adjust 6mm per line x 2 = 12mm spacing

    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, $invtitle, 0, 1, 'C');
    $pdf->Ln(7); // optional extra spacing after the title
    // --- ORDER & CUSTOMER INFO ---
    $pdf->SetFont('helvetica', '', 11);
    $rightInfo = "Customer: {$order['clientname']}\n".
                 "Email: ".($order['emailid']!='' ? $order['emailid'] : 'N/A')."\n".
                 "Mobile: ".($order['mobileno']!='' ? $order['mobileno'] : 'N/A')."\n".
                 "Address: ".($order['delivery_address']!='' ? $order['delivery_address'] : 'N/A'); 
    $pdf->MultiCell(93, 0, $rightInfo, 0, 'L', 0, 0);
    $g_pono="PO-".generate_uuid();
    $leftInfo = "Invoice No: {$g_pono}\n".
        "Date: " . date('d-M-Y', strtotime($order['created_at'])) . "\n".
        "Status: {$order['status']}\n".
        "Created By: ".strtoupper($created_by); 
    $pdf->MultiCell(98, 0, $leftInfo, 0, 'L', 0, 1);
    $pdf->Ln(7);
    // --- ITEMS TABLE ---
    $pdf->SetFont('helvetica', '', 10);
    $w = [10, 50, 20, 20, 20, 40, 25]; // column widths
    // Header
    $pdf->SetFillColor(242,242,242);
    $pdf->SetFont('', 'B');
    $pdf->Cell($w[0], 7, '#', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 7, 'Product', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 7, 'Qty', 1, 0, 'C', 1);
    $pdf->Cell($w[3], 7, 'Rate', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 7, 'Advance', 1, 0, 'C', 1);
    $pdf->Cell($w[5], 7, 'Est. Delivery Date', 1, 0, 'C', 1);
    $pdf->Cell($w[6], 7, 'Payable Amt', 1, 1, 'C', 1);
    $pdf->SetFont('', '');
    $pdf->SetFillColor(255,255,255);
    // Rows with proper row height
    $count = 1;
    // discount_percent,discount_amount
    $runningbal=0;
    $ttqty = 0;
    $seenPsono = [];
    foreach ($items as $item) {
        $base_expr = ((float)($item['rate'] ?? 0) * (int)($item['saleqty'] ?? 0));
        $discount_expr = ROUND(($item['discount_percent']  / 100) * $base_expr, 2);
        $taxable_expr = ROUND($base_expr - $discount_expr, 2);
        $cgst_amount =ROUND(($item['cgst_percent']  / 100) * ($base_expr - $discount_expr), 2);
        $sgst_amount =ROUND(($item['sgst_percent']  / 100) * ($base_expr - $discount_expr), 2);
        $igst_amount =ROUND(($item['igst_percent']  / 100) * ($base_expr - $discount_expr), 2);
        $taxamt=$cgst_amount +$sgst_amount +$igst_amount; 
        $finalamt=$taxable_expr+$taxamt;  
        $chkadvance=0;

        if (empty($item['psono'])) {
            continue;
        }
        $psono = $item['psono'];
        // Count advance ONLY once per psono
        if (!isset($seenPsono[$psono])) {
            $chkadvance  = (float)($item['advanceamount'] ?? 0);
            $seenPsono[$psono] = true;
        }
        $runningbal +=  $chkadvance -$finalamt;



        $ttlamount   += (float)($finalamt ?? 0);
        $ttldiscount += (float)($discount_expr ?? 0);        
        $ttqty += (int)($item['saleqty'] ?? 0);
        $cellHeights = [];
        $cellHeights[] = $pdf->getNumLines($count, $w[0]) * 6;
        $cellHeights[] = $pdf->getNumLines($item['productname'], $w[1]) * 6;
        $cellHeights[] = $pdf->getNumLines($item['saleqty'], $w[2]) * 6;
        $cellHeights[] = $pdf->getNumLines($item['rate'], $w[3]) * 6;
        $cellHeights[] = $pdf->getNumLines($chkadvance, $w[4]) * 6;
        $cellHeights[] = $pdf->getNumLines($maxformattedDate ?: 'N/A', $w[5]) * 6;

        $cellHeights[] = $pdf->getNumLines($runningbal , $w[6]) * 6;
        $maxHeight = max($cellHeights);

        $pdf->MultiCell($w[0], $maxHeight, $count, 1, 'C', 0, 0);
        $pdf->MultiCell($w[1], $maxHeight, $item['productname'], 1, 'L', 0, 0);
        $pdf->MultiCell($w[2], $maxHeight, $item['saleqty'], 1, 'C', 0, 0);
        $pdf->MultiCell($w[3], $maxHeight, $item['rate'], 1, 'C', 0, 0);
        $pdf->MultiCell($w[4], $maxHeight, $chkadvance, 1, 'C', 0, 0);
        $pdf->MultiCell($w[5], $maxHeight,$maxformattedDate , 1, 'C', 0, 0);
        $pdf->MultiCell($w[6], $maxHeight, $runningbal, 1, 'C', 0, 1);


        $count++;
    }  
    $balance = floatval($advancepayment) -floatval($ttlamount)  ;
    // --- TOTALS & BANK DETAILS ---
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Ln(7);
    $startY = $pdf->GetY();
    // Bank details left
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetX(15);
    $pdf->Cell(0, 8, "KARJEE FARM BANK DETAILS", 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(15);  
    $pdf->MultiCell(90, 6,
        "Bank: {$bankDetails['Bank Name']}\n".
        "Account No: {$bankDetails['Account No']}\n".
        "IFSC: {$bankDetails['IFSC Code']}\n".
        "Branch: {$bankDetails['Branch']}", 0, 'L');
    $pdf->SetY($startY);
    $pdf->SetX(130);
   /* $pdf->Cell(40, 6, 'Subtotal', 1, 0, 'R');
    $pdf->Cell(30, 6, number_format($ttlamount, 2), 1, 1, 'R');
    $pdf->SetX(130);
    $pdf->Cell(40, 6, 'Discount', 1, 0, 'R');
    $pdf->Cell(30, 6, number_format($ttldiscount, 2), 1, 1, 'R');
    $pdf->SetX(130);
    $pdf->Cell(40, 6, 'Received', 1, 0, 'R');
    $pdf->Cell(30, 6, number_format($advancepayment, 2), 1, 1, 'R');
    $pdf->SetX(130); 
    $pdf->Cell(40, 6, 'Balance', 1, 0, 'R');
    $pdf->Cell(30, 6, number_format($balance, 2), 1, 1, 'R');
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 11);*/
    /*$pdf->Cell(0, 6, 'Terms & Conditions:', 0, 1, 'L');
    $pdf->SetFont('helvetica', '', 10);
    $terms = "1. 50% deposit at order, balance before delivery.\n".
             "2. Nursery quality responsibility as per agreement.\n".
             "3. Transport damages not liable to company.\n".
             "4. Payments must be cleared as agreed.\n".
             "5. Karjee Farm reserves right to cancel if payment not received.";
    $pdf->MultiCell(0, 6, $terms, 0, 'L');*/

    // --- SIGNATURES AT BOTTOM ---
    $pdf->SetFont('helvetica', '', 11);
    $signWidth = 60; $signHeight = 20;

    // Move Y to bottom minus margins
    $pdf->SetY(-50);

    $pdf->Cell($signWidth, 6, 'Customer Signature:', 0, 0, 'L');
    $pdf->Cell($signWidth, 6, 'Farmer Signature:', 0, 0, 'C');
    $pdf->Cell($signWidth, 6, 'Authorized Signatory:', 0, 1, 'R');

    $pdf->Cell($signWidth, $signHeight, '', 'B', 0, 'L');
    $pdf->Cell($signWidth, $signHeight, '', 'B', 0, 'C');
    $pdf->Cell($signWidth, $signHeight, '', 'B', 1, 'R');
    $safeclient = safeFileName($order['clientname']);
    $pathDir = '../../../attachementfile/proformainv/' . $safeclient;
    if (!is_dir($pathDir)) {
        mkdir($pathDir, 0755, true);
    }  
    $filename = $safeclient . '_' . $g_pono . '.pdf';

    $savePath = $pathDir . '/' . $filename;

    // Save PDF to server
    $pdf->Output($savePath, 'F');
    if (ob_get_length()) {
        ob_end_clean();
    }  
    return [
        "proformainvno"       => $g_pono ?? '',
        "proformasavepath"   => $savePath
    ];   
}
function safeFileName(string $value): string {
    $value = trim($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[^A-Za-z0-9_-]/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return trim($value, '_');
}

function generate_uuid(): string
{
    return date('dmY_His') . '_' . random_int(0, 1000);
}


function sendemailfrommailer($sendto,$sendcc,$sendbcc,$m_subject,$m_msgbody,$attch = array())
{
    require_once "../../../PHPMailer/PHPMailerAutoload.php";
    $mail = new PHPMailer;
    //$mail->SMTPDebug = 2;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'amanaccounting.in';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'documents@amanaccounting.in';                     // SMTP username
    $mail->Password   = (require __DIR__ . '/../../../mail_config.php')['password'];                               // SMTP password
    $mail->SMTPSecure = 'tls';         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->Port       = 587;    
    
    $mail->From = "documents@amanaccounting.in";
    $mail->FromName = "Aman Accounting";
    
    foreach ($sendto as $receiver)
    {
        $mail->addAddress($receiver,$receiver);
    }
    
    if($sendcc){
        foreach ($sendcc as $ccreceiver)
        {
            $mail->addAddress($ccreceiver,$ccreceiver);
        }
    }
    
    if($sendbcc){
        foreach ($sendbcc as $bccreceiver)
        {
            $mail->addAddress($bccreceiver,$bccreceiver);
        }
    }
    if($attch)
    {
        foreach ($attch as $value)
        {
            $displayname=basename($value);
            $mail->AddAttachment($value,$displayname);
        }
    }
    
    $mail->isHTML(true);

    $mail->Subject = $m_subject;
    
    $mail->Body = $m_msgbody;
    if(!$mail->send()) 
    {
        return false;
    } 
    else 
    {
        return true;
    }
}
function freight_proforma($krfconn,$g_pono,$itemswisedetails,$createdby,$orderdetails, $companyname, $bankDetails) {
    $order      = $orderdetails;
    $items      = $itemswisedetails;
    $created_by = $createdby; 
    /* ================= PDF SETUP ================= */
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(15, 5, 15);
    $pdf->SetAutoPageBreak(true, 25);
    $pdf->AddPage();
    /* ================= HEADER ================= */
     $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
    $pdf->Ln(2);
    $logoPath = "../../../img/logo/logo.png";
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 15, $pdf->GetY(), 30);
    }
    $pdf->SetX(55);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, strtoupper($companyname), 0, 1, 'R');
    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(110);
    $pdf->MultiCell(
        90,
        5,
        "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\n".
        "Mobile No: 6261832409, 7879615628",
        0,
        'R'
    );
    /* ================= TITLE ================= */
    $pdf->Ln(8);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, "REVISED FREIGHT INVOICE " . date('d-m-Y'), 0, 1, 'C');
    $pdf->Ln(5);
    /* ================= CUSTOMER INFO ================= */
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(
        93,
        0,
        "Customer: {$order['clientname']}\n".
        "Email: ".($order['emailid'] ?: 'N/A')."\n".
        "Mobile: ".($order['mobileno'] ?: 'N/A')."\n".
        "Address: ".($order['delivery_address'] ?: 'N/A'),
        0,
        'L',
        0,
        0
    );

    $pdf->MultiCell(
        98,
        0,
        "Invoice No: {$g_pono}\n".
        "Date: ".date('d-M-Y', strtotime($order['created_at']))."\n".
        "Status: {$order['status']}\n".
        "Created By: ".strtoupper($created_by),
        0,
        'L'
    );
    $pdf->Ln(6);
    /* ================= TABLE ================= */
    // Total usable width = 180mm
    $w = [8, 25, 35, 20, 20, 22, 50]; // = 180
    $headers = [
        '#', 'Order No', 'Product',
        'Trays QTY', 'Plant QTY', 'Freight', 'Delivered Address'
    ];
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(242,242,242);
    foreach ($headers as $i => $h) {
        $pdf->Cell($w[$i], 7, $h, 1, 0, 'C', 1);
    }
    $pdf->Ln();
    $pdf->SetFont('helvetica', '', 10);
    $count        = 1;
    $totalFreight = 0;
    $totalTrays   = 0; 
    $totalPlants  = 0;
    foreach ($items as $item) {
        $locationaddress=nl2br(htmlspecialchars($item['locationaddress']));
        $sono         = (string)($item['sono'] ?? '');
        $productname  = (string)($item['productname'] ?? '');
        $delivery     = (string)($locationaddress ?? '');

        $saleQty   = max(0, (float)($item['saleqty'] ?? $item['quantity'] ?? $item['totalqty'] ?? $item['send_totalplantcount'] ?? 0)); 
        $trays     = (int)($item['nooftray'] ?? $item['traycount'] ?? $item['send_traycount'] ?? 0);

        $freight   = (float)($item['freight_charges'] ?? 0);

        $totalFreight += $freight;
        $totalTrays   += $trays;
        $totalPlants  += $saleQty;

        $rowHeight = min(
            max([
                $pdf->getNumLines($count, $w[0]),
                $pdf->getNumLines($sono, $w[1]),
                $pdf->getNumLines($productname, $w[2]),
                $pdf->getNumLines((string)$trays, $w[3]),
                $pdf->getNumLines((string)$saleQty, $w[4]),
                $pdf->getNumLines(number_format($freight,2), $w[5]),
                $pdf->getNumLines($delivery, $w[6])
            ]) * 6,
            60
        );

        $pdf->MultiCell($w[0], $rowHeight, $count, 1, 'C', 0, 0);
        $pdf->MultiCell($w[1], $rowHeight, $sono, 1, 'L', 0, 0);
        $pdf->MultiCell($w[2], $rowHeight, $productname, 1, 'L', 0, 0);
        $pdf->MultiCell($w[3], $rowHeight, $trays, 1, 'C', 0, 0);
        $pdf->MultiCell($w[4], $rowHeight, $saleQty, 1, 'C', 0, 0);
        $pdf->MultiCell($w[5], $rowHeight, number_format($freight,2), 1, 'R', 0, 0);
        $pdf->MultiCell($w[6], $rowHeight, $delivery, 1, 'L', 0, 1);

        $count++;
    }

    /* ================= TOTALS ================= */

    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 11);


    $pdf->Cell(150, 6, 'Total Trays', 1, 0, 'R');
    $pdf->Cell(30, 6, $totalTrays, 1, 1, 'R');
    $pdf->Cell(150, 6, 'Total Plants', 1, 0, 'R');
    $pdf->Cell(30, 6, $totalPlants, 1, 1, 'R');
    $pdf->Cell(150, 6, 'Total Freight Amount', 1, 0, 'R');
    $pdf->Cell(30, 6, number_format($totalFreight, 2), 1, 1, 'R');

    /* ================= BANK DETAILS ================= */

    $pdf->Ln(8);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, "KARJEE FARM BANK DETAILS", 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 11);
    
 

    $pdf->MultiCell(
        90,
        6,
        "Bank: ".($bankDetails['Bank Name'] ?? '')."\n".
        "Account No: ".($bankDetails['Account No'] ?? '')."\n".
        "IFSC: ".($bankDetails['IFSC Code'] ?? '')."\n".
        "Branch: ".($bankDetails['Branch'] ?? ''),
        0,
        'L'
    );

 
    /* ================= SIGNATURES ================= */

    $signWidth  = 60;
    $signHeight = 20;

    $pdf->SetY(-50);

    $pdf->SetFont('helvetica', '', 11);
    $pdf->Cell($signWidth, 6, 'Customer Signature:', 0, 0, 'L');
    $pdf->Cell($signWidth, 6, 'Farmer Signature:', 0, 0, 'C');
    $pdf->Cell($signWidth, 6, 'Authorized Signatory:', 0, 1, 'R');

    $pdf->Cell($signWidth, $signHeight, '', 'B', 0, 'L');
    $pdf->Cell($signWidth, $signHeight, '', 'B', 0, 'C');
    $pdf->Cell($signWidth, $signHeight, '', 'B', 1, 'R');

    /* ================= SAVE PDF ================= */

    $safeclient = safeFileName($order['clientname']);
    $pathDir = "../../../attachementfile/proformainv/{$safeclient}";

    if (!is_dir($pathDir)) {
        mkdir($pathDir, 0755, true);
    }

    $filename = "{$safeclient}_{$g_pono}_freight.pdf";
    $savePath = "{$pathDir}/{$filename}";

    $pdf->Output($savePath, 'F');

    return [
        'freightinvno'    => $g_pono,
        'freightsavepath' => $savePath
    ];
}



 
function locationwise_detail($krfconn,$hrmconn,$projectid,$g_pono,$itemswisedetails,$createdby,$orderdetails,$companyname,$bankDetails,$clientid) {

    $order   = $orderdetails;
    $items   = $itemswisedetails;
    $created_by = $createdby;  
    

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(15, 5, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();
    /* ---------------- HEADER ---------------- */
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
    $pdf->Ln(2);
     $logoPath = "../../../img/logo/logo.png"; 
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 15, $pdf->GetY(), 30);
    }
 
    $pdf->SetX(55);
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, strtoupper($companyname), 0, 1, 'R');

    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(110);
    $pdf->MultiCell(90, 5,
        "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\n".
        "Mobile No: 6261832409, 7879615628",
        0, 'R'
    );

    /* ---------------- TITLE ---------------- */
    $pdf->Ln(10);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, "Pickup Location Details " . date("d-m-Y"), 0, 1, 'C');
    $pdf->Ln(6);
    /* ---------------- CUSTOMER INFO ---------------- */
    $pdf->SetFont('helvetica', '', 11);
    $pdf->MultiCell(93, 0,
        "Customer: {$order['clientname']}\n".
        "Email: ".($order['emailid'] ?: 'N/A')."\n".
        "Mobile: ".($order['mobileno'] ?: 'N/A')."\n".
        "Address: ".($order['delivery_address'] ?: 'N/A'),
        0, 'L', 0, 0
    );

    $pdf->MultiCell(98, 0,
        "Date: ".date('d-M-Y', strtotime($order['created_at']))."\n".
        "Status: {$order['status']}\n".
        "Created By: ".strtoupper($created_by),
        0, 'L'
    );

    $pdf->Ln(6);

    /* ---------------- TABLE ---------------- */

    $pdf->SetFont('helvetica', 'B', 10);
    $w = [10, 45, 40, 55, 25];

    $pdf->SetFillColor(242,242,242);
    $pdf->Cell($w[0], 7, '#', 1, 0, 'C', 1);
    $pdf->Cell($w[1], 7, 'Order No', 1, 0, 'C', 1);
    $pdf->Cell($w[2], 7, 'Product', 1, 0, 'C', 1);
    $pdf->Cell($w[3], 7, 'Pickup Location', 1, 0, 'C', 1);
    $pdf->Cell($w[4], 7, 'No. of Trays', 1, 1, 'C', 1);
    $pdf->SetFont('helvetica', '', 10);
    $count = 1;
    $G_trayscount = 0;
    foreach ($items as $productitem) {
        $locationarray = getnooftrays_qty($krfconn, $hrmconn,$projectid, $clientid, $productitem['psono'], $productitem['stockorder_no'],$productitem['productid']);
        foreach ($locationarray as $item) { 
            $rowHeight = max([
                $pdf->getNumLines($count, $w[0]),
                $pdf->getNumLines($productitem['sono'], $w[1]),
                $pdf->getNumLines($productitem['productname'], $w[2]),
                $pdf->getNumLines(nl2br(htmlspecialchars($item['picklocationaddress'])), $w[3]),
                $pdf->getNumLines($item['location_nooftray'], $w[4])
            ]) * 6;

            $pdf->MultiCell($w[0], $rowHeight, $count, 1, 'C', 0, 0);
            $pdf->MultiCell($w[1], $rowHeight, $productitem['sono'], 1, 'L', 0, 0);
            $pdf->MultiCell($w[2], $rowHeight, $productitem['productname'], 1, 'L', 0, 0);
            $pdf->MultiCell($w[3], $rowHeight, nl2br(htmlspecialchars($item['picklocationaddress'])), 1, 'L', 0, 0);
            $pdf->MultiCell($w[4], $rowHeight, $item['location_nooftray'], 1, 'C', 0, 1);

            $G_trayscount += (int)$item['location_nooftray'];
            $count++;
        }
    }
    /* ---------------- TOTALS ---------------- */

    $pdf->Ln(6);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(150, 6, 'Total Trays', 1, 0, 'R');
    $pdf->Cell(30, 6, $G_trayscount, 1, 1, 'R');

    /* ---------------- SAVE PDF ---------------- */

    $safeclient = safeFileName($order['clientname']);
    $pathDir = "../../../attachementfile/proformainv/{$safeclient}";

    if (!is_dir($pathDir)) {
        mkdir($pathDir, 0755, true);
    }

    $g_pono = generate_uuid();
    $filename = "{$safeclient}_{$g_pono}_pickupdetails.pdf";
    $savePath = "{$pathDir}/{$filename}";

    $pdf->Output($savePath, 'F');

    return [
        'pickupinvno'   => $g_pono,
        'pickupsavepath'=> $savePath
    ];
}

function getlocation($hrmplusconn, $projectid, $locationid){ 
    $projectid = intval($projectid);
    $locationid = $hrmplusconn->real_escape_string($locationid);
    $sqlqry = " SELECT locationName, LocationAddress FROM hrm_AttendanceLocationMaster  WHERE LocationId = '{$locationid}'  AND projectid = {$projectid}  LIMIT 1 ";
    $loc_result = $hrmplusconn->query($sqlqry);
    if ($loc_result && $loc_result->num_rows > 0) {
        $row = $loc_result->fetch_assoc();
        $loc_result->free();
        return [
            'locationname'    => $row['locationName'] ?? '',
            'locationaddress' => $row['LocationAddress'] ?? ''
        ];
    } else {
        return [ 
            'locationname'    => '',
            'locationaddress' => ''
        ];
    }
}
function getnooftrays_qty($krfconn, $hrmplusconn, $projectid,$assigntoclientid, $psono,
    $stockorder_no,$productid){
    $returnarray = [];
    $projectid         = (int)$projectid;
    $assigntoclientid  = $krfconn->real_escape_string($assigntoclientid);
    $psono             = $krfconn->real_escape_string($psono);
    $stockorder_no     = $krfconn->real_escape_string($stockorder_no);
    $productid         = (int)$productid;
    $sqlqry = " SELECT locationid, SUM(qtypertray) AS locationreadyqty, (SUM(qtypertray)/qtypertray) AS location_nooftray  FROM krf_readytosale_stocks  WHERE projectid = {$projectid}
          AND assigntoclientid = '{$assigntoclientid}' AND psono = '{$psono}' AND stockorder_no = '{$stockorder_no}'  AND productid = {$productid} GROUP BY locationid  "; 
    $result = $krfconn->query($sqlqry);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $locationarray = getlocation($hrmplusconn,$projectid, $row['locationid']
            );

            $returnarray[] = [
                'locationreadyqty'   => (int)$row['locationreadyqty'],
                'location_nooftray'  => (int)$row['location_nooftray'],
                'picklocationname'   => $locationarray['locationname'],
                'picklocationaddress'=> $locationarray['locationaddress']
            ];
        }
        $result->free();
    }

    return $returnarray;
}

?>