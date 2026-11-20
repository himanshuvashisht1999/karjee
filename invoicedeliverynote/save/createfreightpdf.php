<?php
if (!class_exists('TCPDF')) {
    require_once(__DIR__ . '/../../krf-orderandsales/pdfroutines/tcpdf/tcpdf.php');
}

function generate_freight_invoice_pdf($krfconn, $projectid, $clientid, $psono, $amount, $discount, $payable, $clientData, $companyname, $drivername, $vehicleno,$trip_id="") {
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    
    // Set Document Information
    $pdf->SetCreator('Karjee Farms');
    $pdf->SetAuthor('Karjee Farms');
    $pdf->SetTitle('Freight Invoice - ' . $psono);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    $pdf->SetMargins(15, 10, 15);
    $pdf->AddPage();

    // Background for Header
    $pdf->SetFillColor(232, 245, 233); // Very light green
    $pdf->Rect(0, 0, 210, 35, 'F');

    // Logo & Header
    $logoPath = __DIR__ . "/../../img/logo/logo.png";
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 15, 8, 25);
    }

    $pdf->SetY(8);
    $pdf->SetX(50);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->SetTextColor(46, 125, 50); // Logo Green
    $pdf->Cell(0, 8, strtoupper($companyname), 0, 1, 'R');
    
    $pdf->SetX(50);
    $pdf->SetFont('helvetica', '', 9);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 5, 'Vill. Karjee Darima Ambikapur, Chhattisgarh, India', 0, 1, 'R');
    $pdf->SetX(50);
    $pdf->Cell(0, 5, 'GSTIN: 22BWJPG1381G1ZU | Mobile: 6261832409', 0, 1, 'R');
    
    $pdf->SetY(35);
    $pdf->Ln(5);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(5);

    // Title
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->SetTextColor(0);
    $pdf->Cell(0, 10, 'FREIGHT INVOICE', 0, 1, 'C');
    $pdf->Ln(3);

    // Order & Client Info
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 6, 'Order No:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(70, 6, $psono, 0, 0);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 6, 'Date:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, date('d-M-Y'), 0, 1);

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 6, 'Customer:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $clientData['clientname'], 0, 1);
    
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(30, 6, 'Address:', 0, 0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->MultiCell(0, 6, $clientData['delivery_address'], 0, 'L');

    $pdf->Ln(5);

    // Fetch Trip/Provisional Order details for items table
    $items = [];
    if(!$trip_id){
     $trip_res = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE psono = '$psono' OR stockorder_no = '$psono'");

    }else{

    $trip_res = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
    }
    if ($trip_res && $trip_res->num_rows > 0) {
        while ($row = $trip_res->fetch_assoc()) {
            if (!empty($row['psono'])) {
                $p_res = $krfconn->query("SELECT product_name FROM krf_provisional_sales_order_items WHERE product_id = '{$row['productid']}' AND psono = '{$row['psono']}' LIMIT 1");
                $p_data = $p_res ? $p_res->fetch_assoc() : null;
                $row['productname'] = $p_data['product_name'] ?? 'Product ID: '.$row['productid'];
            } else {
                $p_res = $krfconn->query("SELECT product_name FROM krf_production_stockorder WHERE stockorder_no = '{$row['stockorder_no']}' LIMIT 1");
                $p_data = $p_res ? $p_res->fetch_assoc() : null;
                $row['productname'] = $p_data['product_name'] ?? 'Internal Move: '.$row['stockorder_no'];
            }
            $items[] = $row;
        }
    }

    if (!empty($items)) {
        $pdf->SetFillColor(46, 125, 50); // Logo Green
        $pdf->SetTextColor(255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(15, 7, '#', 1, 0, 'C', 1);
        $pdf->Cell(90, 7, 'Product Description', 1, 0, 'L', 1);
        $pdf->Cell(25, 7, 'Trays QTY', 1, 0, 'C', 1);
        $pdf->Cell(25, 7, 'Plant QTY', 1, 0, 'C', 1);
        $pdf->Cell(25, 7, 'UOM', 1, 1, 'C', 1);

        $pdf->SetTextColor(0);
        $pdf->SetFont('helvetica', '', 10);
        $idx = 1;
        foreach ($items as $item) {
            $pdf->Cell(15, 7, $idx++, 1, 0, 'C');
            $pdf->Cell(90, 7, $item['productname'], 1, 0, 'L');
            $pdf->Cell(25, 7, $item['send_traycount'] ?? 0, 1, 0, 'C');
            $pdf->Cell(25, 7, $item['send_totalplantcount'], 1, 0, 'C');
            $pdf->Cell(25, 7, 'Pieces', 1, 1, 'C');
        }
        $pdf->Ln(5);
    }

    // Billing Table
    $pdf->SetFillColor(46, 125, 50); // Logo Green
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'Description', 1, 0, 'C', 1);
    $pdf->Cell(0, 7, 'Amount (INR)', 1, 1, 'C', 1);
    
    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(120, 10, 'Freight Charges for Provisional Order ' . $psono, 1, 0, 'L');
    $pdf->Cell(0, 10, number_format($amount, 2), 1, 1, 'R');

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'Total Before Discount', 1, 0, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, number_format($amount, 2), 1, 1, 'R');

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(120, 7, 'Discount Applied (' . $discount . '%)', 1, 0, 'R');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 7, '(-) ' . number_format($amount - $payable, 2), 1, 1, 'R');

    $pdf->SetFillColor(220, 230, 241);
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->Cell(120, 10, 'TOTAL PAYABLE FREIGHT AMOUNT', 1, 0, 'R', 1);
    $pdf->Cell(0, 10, '₹ ' . number_format($payable, 2), 1, 1, 'R', 1);

    $pdf->Ln(30);
    
    // Signatures
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(90, 6, 'Customer Signature', 0, 0, 'L');
    $pdf->Cell(0, 6, 'Authorized Signatory', 0, 1, 'R');
    $pdf->Ln(2);
    $pdf->Line(15, $pdf->GetY(), 65, $pdf->GetY());
    $pdf->SetX(145);
    $pdf->Line(145, $pdf->GetY(), 195, $pdf->GetY());

    // Footer
    $pdf->SetY(-20);
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->Cell(0, 10, 'Note: This is a computer generated invoice and does not require a physical signature.', 0, 0, 'C');

    // Save Path Management
    $safe_client_name = safeFileNameF($clientData['clientname']);
    $base_dir = "../../attachementfile/freight_invoice/{$safe_client_name}/";
    $full_save_dir = __DIR__ . "/" . $base_dir;
    
    if (!is_dir($full_save_dir)) mkdir($full_save_dir, 0777, true);
    
    $filename = "FREIGHT_" . str_replace("/", "_", $psono) . "_" . time() . ".pdf";
    $save_path = $base_dir . $filename;
    
    $pdf->Output($full_save_dir . $filename, 'F');
    return $save_path;
}

function safeFileNameF(string $value): string {
    $value = trim($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    $value = preg_replace('/[^A-Za-z0-9_-]/', '_', $value);
    $value = preg_replace('/_+/', '_', $value);
    return trim($value, '_');
}
?>
