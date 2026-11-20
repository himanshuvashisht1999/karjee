<?php
error_reporting(E_ALL & ~E_WARNING); 
ini_set('display_errors', 1); 

require('../../pdfroutines/tcpdf/tcpdf.php');
require('../../connection.php');

$action = $_POST['action'] ?? '';

if ($action == 'generate_pdf') {
    $type = $_POST['type'] ?? ''; // gate_pass or delivery_note
    $trip_id = $_POST['trip_id'] ?? '';
    $projectid = $_POST['projectid'] ?? 1;
    $loginname = $_POST['loginname'] ?? 'System';

    if (empty($trip_id) || empty($type)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing parameters.']);
        exit;
    }

    // Fetch Trip details
    $trip_res = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id'");
    $items = [];
    while ($row = $trip_res->fetch_assoc()) {
        if (!empty($row['psono'])) {
            // Fetch product name from PSO items
            $p_res = $krfconn->query("SELECT product_name FROM krf_provisional_sales_order_items WHERE product_id = '{$row['productid']}' AND psono = '{$row['psono']}' LIMIT 1");
            $p_data = $p_res->fetch_assoc();
            $row['productname'] = $p_data['product_name'] ?? 'Product ID: '.$row['productid'];
        } else {
            // Fetch product name from Production Order
            $p_res = $krfconn->query("SELECT product_name FROM krf_production_stockorder WHERE stockorder_no = '{$row['stockorder_no']}' LIMIT 1");
            $p_data = $p_res->fetch_assoc();
            $row['productname'] = $p_data['product_name'] ?? 'Internal Move: '.$row['stockorder_no'];
        }
        $items[] = $row;
    }

    if (empty($items)) {
        echo json_encode(['status' => 'error', 'message' => 'Trip items not found.']);
        exit;
    }

    $first = $items[0];
    
    // Fetch Consignee/Destination Details
    $consignee_name = "Internal Destination";
    $consignee_address = "Karjee Farm Internal Location";

    if (!empty($first['psono'])) {
        $client_query = "SELECT c.* FROM krf_provisional_sales_order p 
                         JOIN krf_client_master c ON p.clientid = c.client_id 
                         WHERE p.psono = '{$first['psono']}' LIMIT 1";
        $c_res = $krfconn->query($client_query);
        if ($client = $c_res->fetch_assoc()) {
            $consignee_name = $client['client_name'];
            $consignee_address = $client['invoicing_address'];
        }
    } else {
        $consignee_name = "INTERNAL MOVEMENT";
        $consignee_address = "Shift for Production / Grafting / Location Transfer";
    }

    // Initialize PDF
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Aman Accounting');
    $pdf->SetAuthor($loginname);
    $pdf->SetTitle(($type == 'gate_pass' ? 'Gate Pass' : 'Delivery Note'));
    $pdf->SetMargins(15, 10, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->AddPage();

    // Background for Header
    $pdf->SetFillColor(232, 245, 233); // Very light green
    $pdf->Rect(0, 0, 210, 35, 'F');

    // Logo & Header
    $logoPath = "../../img/logo/logo.png";
    if (file_exists($logoPath)) {
        $pdf->Image($logoPath, 15, 8, 25);
    }

    $pdf->SetY(8);
    $pdf->SetX(50);
    $pdf->SetFont('helvetica', 'B', 15);
    $pdf->SetTextColor(46, 125, 50); // Logo Green
    $pdf->Cell(0, 8, 'KARJEE FARMS', 0, 1, 'R');
    
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
    $pdf->Cell(0, 10, ($type == 'gate_pass' ? 'CONSOLIDATED GATE PASS' : 'DELIVERY NOTE'), 0, 1, 'C');
    $pdf->Ln(3);

    // Info Grid
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(35, 7, 'Trip ID:', 0, 0); $pdf->SetFont('helvetica', 'B', 10); $pdf->Cell(60, 7, $trip_id, 0, 0);
    $pdf->SetFont('helvetica', '', 10); $pdf->Cell(35, 7, 'Date:', 0, 0); $pdf->Cell(0, 7, date('d-M-Y H:i'), 0, 1);
    
    $pdf->Cell(35, 7, 'Vehicle No:', 0, 0); $pdf->SetFont('helvetica', 'B', 10); $pdf->Cell(60, 7, $first['vehicle_no'], 0, 0);
    $pdf->SetFont('helvetica', '', 10); $pdf->Cell(35, 7, 'Driver:', 0, 0); $pdf->Cell(0, 7, $first['driver_name'], 0, 1);
    
    $ref_id = !empty($first['psono']) ? $first['psono'] : $first['stockorder_no'];
    $ref_label = !empty($first['psono']) ? 'PSO #:' : 'Prod Order:';
    
    $pdf->Cell(35, 7, $ref_label, 0, 0); $pdf->SetFont('helvetica', 'B', 10); $pdf->Cell(60, 7, $ref_id, 0, 0);
    $pdf->SetFont('helvetica', '', 10); $pdf->Cell(35, 7, 'Driver Mobile:', 0, 0); $pdf->Cell(0, 7, $first['driver_mobileno'], 0, 1);
    
    $pdf->Ln(5);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(0, 7, 'Consignee / Destination Details:', 0, 1);
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 6, $consignee_name, 0, 1);
    $pdf->MultiCell(0, 6, $consignee_address, 0, 'L');
    $pdf->Ln(5);

    // Items Table
    $pdf->SetFillColor(46, 125, 50); // Logo Green
    $pdf->SetTextColor(255);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(15, 8, '#', 1, 0, 'C', 1);
    $pdf->Cell(85, 8, 'Product Description', 1, 0, 'L', 1);
    $pdf->Cell(25, 8, 'Trays QTY', 1, 0, 'C', 1);
    $pdf->Cell(30, 8, 'Plant QTY', 1, 0, 'C', 1);
    $pdf->Cell(25, 8, 'UOM', 1, 1, 'C', 1);

    $pdf->SetTextColor(0);
    $pdf->SetFont('helvetica', '', 10);
    $i = 1;
    $total_qty = 0;
    $total_trays = 0;
    foreach ($items as $item) {
        $pdf->Cell(15, 8, $i++, 1, 0, 'C');
        $pdf->Cell(85, 8, $item['productname'], 1, 0, 'L');
        $pdf->Cell(25, 8, $item['send_traycount'] ?? 0, 1, 0, 'C');
        $pdf->Cell(30, 8, $item['send_totalplantcount'], 1, 0, 'C');
        $pdf->Cell(25, 8, 'Pieces', 1, 1, 'C');
        $total_qty += $item['send_totalplantcount'];
        $total_trays += ($item['send_traycount'] ?? 0);
    }

    // Totals
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(100, 8, 'TOTAL QUANTITY', 1, 0, 'R');
    $pdf->Cell(25, 8, $total_trays, 1, 0, 'C');
    $pdf->Cell(30, 8, $total_qty, 1, 0, 'C');
    $pdf->Cell(25, 8, '', 1, 1, 'C');
    $pdf->Ln(15);

    // Signatures
    $pdf->SetY($pdf->GetY() + 10);
    $pdf->Cell(60, 6, '_________________', 0, 0, 'C');
    $pdf->Cell(60, 6, '_________________', 0, 0, 'C');
    $pdf->Cell(60, 6, '_________________', 0, 0, 'C');
    $pdf->Cell(60, 6, 'Gate Keeper', 0, 0, 'C');
    $pdf->Cell(60, 6, 'Driver Signature', 0, 0, 'C');
    $pdf->Cell(60, 6, 'Authorized Signatory', 0, 1, 'C');

    // Save PDF
    $dir = '../../attachementfile/dispatch/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $filename = $type . '_' . $trip_id . '.pdf';
    $filepath = 'attachementfile/dispatch/' . $filename;
    $pdf->Output('../../' . $filepath, 'F');

    // Save filepath to respective database table (krf_invoice_gatepass or krf_invoice_deliverynote)
    $table_name = ($type == 'gate_pass') ? 'krf_invoice_gatepass' : 'krf_invoice_deliverynote';
    
    $client_id = 0;
    if (!empty($first['psono'])) {
        $c_res = $krfconn->query("SELECT clientid FROM krf_provisional_sales_order WHERE psono = '{$first['psono']}' LIMIT 1");
        if ($c_res && $c_res->num_rows > 0) {
            $client_id = (int)$c_res->fetch_assoc()['clientid'];
        }
    }

    foreach ($items as $item) {
        $item_ref_id = !empty($item['psono']) ? $item['psono'] : $item['stockorder_no'];
        $item_pid = $item['productid'];
        $item_qty = $item['send_totalplantcount'];
        $item_proj = $item['projectid'] ?? 1;
        $delivery_date = !empty($item['dispatch_time']) ? date('Y-m-d', strtotime($item['dispatch_time'])) : date('Y-m-d');
        $item_pid_val = !empty($item_pid) && is_numeric($item_pid) ? "'$item_pid'" : "NULL";
        
        // Check if record already exists for this psono, productid, and filepath
        $chk = $krfconn->query("SELECT id FROM $table_name WHERE psono = '$item_ref_id' AND productid = $item_pid_val AND filepath = '" . $krfconn->real_escape_string($filepath) . "' LIMIT 1");
        if ($chk && $chk->num_rows > 0) {
            $row_id = $chk->fetch_assoc()['id'];
            $krfconn->query("UPDATE $table_name SET 
                projectid = '$item_proj',
                clientid = " . ($client_id > 0 ? "'$client_id'" : "NULL") . ",
                deliveryqty = '$item_qty',
                deliverydate = '$delivery_date'
                WHERE id = '$row_id'");
        } else {
            $krfconn->query("INSERT INTO $table_name (
                projectid, psono, clientid, productid, deliveryqty, deliverydate, filepath
            ) VALUES (
                '$item_proj', '$item_ref_id', " . ($client_id > 0 ? "'$client_id'" : "NULL") . ", $item_pid_val, '$item_qty', '$delivery_date', '" . $krfconn->real_escape_string($filepath) . "'
            )");
            $row_id = $krfconn->insert_id;
        }

        if ($row_id) {
            $col_to_update = ($type == 'gate_pass') ? 'gatepass_id' : 'deliverynote_id';
            $krfconn->query("UPDATE krf_vehicle_travel_history SET $col_to_update = '$row_id' WHERE id = '{$item['id']}'");
        }
    }

    echo json_encode(['status' => 'success', 'filepath' => $filepath]);
}

if ($action == 'generate_freight_pdf') {
    require_once('../../invoicedeliverynote/save/createfreightpdf.php');
    $trip_id = $_POST['trip_id'] ?? '';
    
    $trip_res = $krfconn->query("SELECT * FROM krf_vehicle_travel_history WHERE trip_id = '$trip_id' LIMIT 1");
    $trip = $trip_res->fetch_assoc();
    
    if (!$trip) {
        echo json_encode(['status' => 'error', 'message' => 'Trip not found.']);
        exit;
    }

    // Handle internal vs client dispatch details
    $ref_no = !empty($trip['psono']) ? $trip['psono'] : $trip['stockorder_no'];
    $client = [];
    $client_id = 0;
    
    if (!empty($trip['psono'])) {
        // Fetch Client info from PSO to match expected keys (clientname, delivery_address)
        $pso_res = $krfconn->query("SELECT clientname, delivery_address, mobileno, emailid FROM krf_provisional_sales_order WHERE psono = '{$trip['psono']}' LIMIT 1");
        $pso_data = $pso_res ? $pso_res->fetch_assoc() : null;
        
        $c_res = $krfconn->query("SELECT * FROM krf_client_master WHERE client_id = (SELECT clientid FROM krf_provisional_sales_order WHERE psono = '{$trip['psono']}' LIMIT 1)");
        $client_db = $c_res ? $c_res->fetch_assoc() : null;
        
        if ($client_db) {
            $client = $client_db;
        }
        $client_id = $client['client_id'] ?? 0;
        $client['clientname'] = ($pso_data['clientname'] ?? '') ?: ($client['client_name'] ?? '');
        $client['delivery_address'] = ($pso_data['delivery_address'] ?? '') ?: ($client['invoicing_address'] ?? '');
        $client['mobileno'] = ($pso_data['mobileno'] ?? '') ?: ($client['phones'] ?? '');
        $client['emailid'] = ($pso_data['emailid'] ?? '') ?: ($client['client_email'] ?? '');
    } else {
        $client['client_id'] = 0;
        $client['clientname'] = 'INTERNAL MOVEMENT';
        $client['delivery_address'] = 'Shift for Production / Grafting / Location Transfer';
        $client['mobileno'] = '';
        $client['emailid'] = '';
    }

    $save_path = generate_freight_invoice_pdf(
        $krfconn, 
        $trip['projectid'], 
        $client_id, 
        $ref_no, 
        $trip['freight_amount'], 
        $trip['freight_discountpercent'], 
        $trip['payable_freightamount'], 
        $client, 
        'KARJEE FARMS', 
        $trip['driver_name'], 
        $trip['vehicle_no'],
        $trip_id 
    );

    // generate_freight_invoice_pdf returns path relative to its own location, need to adjust
    // It returns "../../attachementfile/..."
    $clean_path = str_replace('../../', '', $save_path);

    // Save freight invoice path to database table krf_freightcharges
    $freight_pso = !empty($trip['psono']) ? $trip['psono'] : $trip['stockorder_no'];
    $client_id_val = "NULL";
    if (!empty($client['client_id'])) {
        $client_id_val = "'" . $krfconn->real_escape_string($client['client_id']) . "'";
    }
    
    $f_amt = (float)($trip['freight_amount'] ?? 0);
    $f_disc = (float)($trip['freight_discountpercent'] ?? 0);
    $f_payable = (float)($trip['payable_freightamount'] ?? 0);
    
    // Check if a freight record exists for this psono/stockorder_no
    $chk_f = $krfconn->query("SELECT id FROM krf_freightcharges WHERE psono = '$freight_pso' LIMIT 1");
    if ($chk_f && $chk_f->num_rows > 0) {
        $krfconn->query("UPDATE krf_freightcharges SET 
            freightinvoicepath = '" . $krfconn->real_escape_string($clean_path) . "',
            freight_amount = '$f_amt',
            freight_discountpercent = '$f_disc',
            payable_freightamount = '$f_payable'
            WHERE psono = '$freight_pso'");
    } else {
        $krfconn->query("INSERT INTO krf_freightcharges (
            projectid, psono, clientid, freight_amount, freight_discountpercent, payable_freightamount, freightinvoicepath
        ) VALUES (
            '{$trip['projectid']}', '$freight_pso', $client_id_val, '$f_amt', '$f_disc', '$f_payable', '" . $krfconn->real_escape_string($clean_path) . "'
        )");
    }

    echo json_encode(['status' => 'success', 'filepath' => $clean_path]);
}

if ($action == 'generate_final_invoice_pdf') {
    require_once('../../invoice/invoicepdf/createfinalinv_pdf.php');
    $trip_id = $_POST['trip_id'] ?? '';
    
    $trip_res = $krfconn->query("SELECT t.*, p.product_name FROM krf_vehicle_travel_history t LEFT JOIN krf_provisional_sales_order_items p ON t.psono = CONVERT(p.psono USING utf8mb4) AND t.productid = CONVERT(p.product_id USING utf8mb4) WHERE t.trip_id = '$trip_id'");
    $items = [];
    $psono = '';
    while($row = $trip_res->fetch_assoc()) {
        $psono = $row['psono'];
        // Fetch rate and tax from PSO items
        $rate_res = $krfconn->query("SELECT rate, discount_percent, cgst_percent, sgst_percent, igst_percent FROM krf_provisional_sales_order_items WHERE psono = '{$row['psono']}' AND product_id = '{$row['productid']}' LIMIT 1");
        $rate_row = $rate_res->fetch_assoc();
        
        $items[] = [
            'productname' => $row['product_name'],
            'saleqty' => $row['send_totalplantcount'],
            'rate' => $rate_row['rate'] ?? 0,
            'discount_percent' => $rate_row['discount_percent'] ?? 0,
            'cgst_percent' => $rate_row['cgst_percent'] ?? 0,
            'sgst_percent' => $rate_row['sgst_percent'] ?? 0,
            'igst_percent' => $rate_row['igst_percent'] ?? 0
        ];
    }

    // Map client fields from PSO for compatibility with PDF functions
    $pso_res = $krfconn->query("SELECT clientname, delivery_address, mobileno, emailid FROM krf_provisional_sales_order WHERE psono = '$psono' LIMIT 1");
    $pso_data = $pso_res->fetch_assoc();
    
    $client_res = $krfconn->query("SELECT * FROM krf_client_master WHERE client_id = (SELECT clientid FROM krf_provisional_sales_order WHERE psono = '$psono' LIMIT 1)");
    $client = $client_res->fetch_assoc();
    $client['clientname'] = $pso_data['clientname'] ?? $client['client_name'];
    $client['delivery_address'] = $pso_data['delivery_address'] ?? $client['invoicing_address'];
    $client['mobileno'] = $pso_data['mobileno'] ?? $client['phones'];
    $client['emailid'] = $pso_data['emailid'] ?? $client['client_email'];

    $bank = [
        'Bank Name' => 'IDFC FIRST BANK',
        'Account No' => '10086254783',
        'IFSC Code' => 'IDFB0060201',
        'Branch' => 'AMBIKAPUR'
    ];

    $res = createinvoicepdf($items, $loginname, $client, 'KARJEE FARMS', 0, $bank, 'INV-'.$trip_id, 'invoice', $psono);
    
    $clean_path = str_replace('../../', '', $res['proformasavepath']);
    echo json_encode(['status' => 'success', 'filepath' => $clean_path]);
}
?>
