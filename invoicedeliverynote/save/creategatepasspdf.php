<?php
function generate_gate_pass_pdf(
    $krfconn,
    $projectid,
    $items,
    $clientid,
    $clientdetails,
    $created_by,
    $invoiceno,
    $psono,
    $drivername,
    $drivermobileno,
    $truckno,
    $delivery_date = ""
) {

    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetMargins(15, 8, 15);
    $pdf->SetAutoPageBreak(true, 20);
    $pdf->AddPage();

    /* ================= HEADER ================= */

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 6, "GSTIN/UIN: 22BWJPG1381G1ZU", 0, 1, 'L');
    $pdf->Ln(2);

    $logoPath = "../../../img/logo/logo.png";
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 15, $pdf->GetY(), 28);
    }

    $pdf->SetXY(55, $pdf->GetY());
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 8, strtoupper($clientdetails['companyname'] ?? 'COMPANY NAME'), 0, 1, 'R');

    $pdf->SetFont('helvetica', '', 11);
    $pdf->SetX(110);
    $pdf->MultiCell(
        90,
        5,
        "Address: Vill. Karjee Darima Ambikapur, Chhattisgarh, India\nMobile No: 6261832409, 7879615628",
        0,
        'R'
    );

    /* ================= TITLE ================= */

    $pdf->Ln(6);
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 8, "Gate Pass for Invoice No: {$invoiceno}", 0, 1, 'C');
    $pdf->Ln(4);

    /* ================= CLIENT DETAILS ================= */

    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Customer Details', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 11);
    $startY = $pdf->GetY();

    $first_item = $items[0] ?? [];
    $display_address = $first_item['deliveryaddress'] ?? $clientdetails['delivery_address'] ?? 'N/A';

    // Left column
    $pdf->MultiCell(
        93,
        6,
        "Customer: {$clientdetails['clientname']}\n" .
        "Email: " . ($clientdetails['emailid'] ?: 'N/A') . "\n" .
        "Mobile: " . ($clientdetails['mobileno'] ?: 'N/A') . "\n" .
        "Address: " . ($display_address ?: 'N/A'),
        0,
        'L',
        0,
        0
    );

    // Right column
    $pdf->SetXY(108, $startY);
    $pdf->MultiCell(
        98,
        6,
        "Date: " . (!empty($delivery_date) ? date('d-M-Y', strtotime($delivery_date)) : date('d-M-Y')) . "\n" .
        "Created By: " . strtoupper($created_by),
        0,
        'L'
    );

    $pdf->Ln(9);

    /* ================= TRUCK DETAILS ================= */

    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(0, 6, 'Truck & Driver Details', 0, 1, 'L');

    $pdf->SetFont('helvetica', '', 11);
    $truckY = $pdf->GetY();

    $pdf->MultiCell(
        93,
        6,
        "Truck No: " . ($truckno ?: 'N/A') . "\n" .
        "Driver Name: " . ($drivername ?: 'N/A'),
        0,
        'L',
        0,
        0
    );

    $pdf->SetXY(108, $truckY);
    $pdf->MultiCell(
        98,
        6,
        "Driver Mobile: " . ($drivermobileno ?: 'N/A'),
        0,
        'L'
    );

    $pdf->Ln(11);

    /* ================= ITEM TABLE ================= */

    $pdf->SetFont('helvetica', 'B', 10);
    $w = [10, 35, 30, 45, 25, 20];

    $pdf->SetFillColor(242, 242, 242);
    $headers = ['#', 'Order No', 'Product', 'Delivery Address', 'Trays QTY', 'Plant QTY'];

    foreach ($headers as $i => $h) {
        $pdf->Cell($w[$i], 7, $h, 1, 0, 'C', 1);
    }
    $pdf->Ln();

    $pdf->SetFont('helvetica', '', 10);

    $count = 1;
    $G_qty = 0;
    $G_traycount = 0;

    foreach ($items as $row) {
        $p_name = (string)($row['productname'] ?? '');
        $o_qty = (string)($row['orderqty'] ?? $row['totalqty'] ?? $row['quantity'] ?? $row['saleqty'] ?? $row['send_totalplantcount'] ?? '0');
        $t_count = (string)($row['traycount'] ?? $row['nooftray'] ?? $row['send_traycount'] ?? '0');

        $despatchaddress = getdespatchaddressg(
            $krfconn,
            $projectid,
            $psono,
            (int)($row['productid'] ?? 0)
        );

        $rowHeight = max([
            $pdf->getNumLines((string)$count, $w[0]),
            $pdf->getNumLines((string)$psono, $w[1]),
            $pdf->getNumLines($p_name, $w[2]),
            $pdf->getNumLines((string)$despatchaddress, $w[3]),
            $pdf->getNumLines($t_count, $w[4]),
            $pdf->getNumLines($o_qty, $w[5])
        ]) * 6;

        $pdf->MultiCell($w[0], $rowHeight, $count, 1, 'C', 0, 0);
        $pdf->MultiCell($w[1], $rowHeight, $psono, 1, 'L', 0, 0);
        $pdf->MultiCell($w[2], $rowHeight, $p_name, 1, 'L', 0, 0);
        $pdf->MultiCell($w[3], $rowHeight, $despatchaddress, 1, 'L', 0, 0);
        $pdf->MultiCell($w[4], $rowHeight, $t_count, 1, 'C', 0, 0);
        $pdf->MultiCell($w[5], $rowHeight, $o_qty, 1, 'C', 0, 1);

        $G_qty += (int)$o_qty;
        $G_traycount += (int)$t_count;
        $count++;
    }

    /* ================= TOTAL ROW ================= */

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(230, 230, 230);

    $pdf->Cell(array_sum(array_slice($w, 0, 4)), 7, 'TOTAL', 1, 0, 'R', 1);
    $pdf->Cell($w[4], 7, $G_traycount, 1, 0, 'C', 1);
    $pdf->Cell($w[5], 7, $G_qty, 1, 1, 'C', 1);

    /* ================= SAVE PDF ================= */

    $safeclient = safeFileNameG($clientdetails['clientname']);
    $safe_inv = safeFileNameG($invoiceno);
    $pathDir = "../../attachementfile/gatepass_invoice/{$safeclient}";

    if (!is_dir($pathDir)) {
        mkdir($pathDir, 0755, true);
    }

    $filename = "{$safeclient}_{$safe_inv}_gatepass.pdf";
    $savePath = "{$pathDir}/{$filename}";

    $pdf->Output($savePath, 'F');

    $gate_pth = str_replace("../../", "", $savePath);
    

    return [ 
        'deliverypath'=> $gate_pth
    ];
}

// ---------------------
function safeFileNameG(string $value): string {
    $value = trim($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[^A-Za-z0-9_-]/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return trim($value, '_');
}
function getdespatchaddressg($krfconn, $projectid, $psono,$productid){
   
    $deliveryaddress="NA";
    $projectid         = (int)$projectid; 
    $psono             = $krfconn->real_escape_string($psono); 
    $productid         = (int)$productid;
      $sqlqry = " SELECT ifnull(deliveryaddress,'') as deliveryaddress     FROM krf_deliveryaddress_location  WHERE projectid = {$projectid}
          AND  psono = '{$psono}'  AND productid = {$productid} GROUP BY id desc  "; 
    $result = $krfconn->query($sqlqry);
    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc() ;
               $deliveryaddress=$row['deliveryaddress'];

             
        ;
        $result->free();
    }

    return $deliveryaddress;
}
function gatepass_invoice($krfconn, $invoiceno) {

    $items = [];

    $sql = "
        SELECT
            productid,
            productname,
            stockorder_no,
            SUM(qtypertray) AS totalqty,
            COUNT(*) AS traycount
        FROM krf_readytosale_stocks
        WHERE invoiceno = ?
        GROUP BY productid, productname, stockorder_no
        ORDER BY productname
    ";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $krfconn->error);
    }

    $stmt->bind_param("s", $invoiceno);
    $stmt->execute();

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    return $items;
}

function clientdetails($krfconn,$clientid){
    $clientarray=array();
    $sql = "SELECT client_id, client_name as clientname,  ifnull(invoicing_address ,'' )  as delivery_address, ifnull(phones ,'' ) as mobileno, ifnull(client_email  ,'' ) as emailid, ifnull(statename ,'' ) as statename, ifnull(districtname  ,'' ) as  districtname FROM krf_client_master   where  ifnull(client_id,'') =?  "; 
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $krfconn->error);
    }
    $stmt->bind_param("s", $clientid);
    $stmt->execute();
    $result = $stmt->get_result();
    return ($result && $result->num_rows > 0)
        ? $result->fetch_assoc()
        : [];
}
     
?>