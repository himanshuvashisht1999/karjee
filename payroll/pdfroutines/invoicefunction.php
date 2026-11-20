<?php
require_once('tcpdf/tcpdf.php');

function createInvoiceWithoutHTML($invoiceData) {
    if (empty($invoiceData['invoice_number']) || empty($invoiceData['date']) || 
        empty($invoiceData['customer_name']) || empty($invoiceData['customer_address'])) {
        die('Error: Missing required invoice data.');
    }

    // Clear output buffer to prevent premature output
    if (ob_get_length()) {
        ob_end_clean();
    }

    $pdf = new TCPDF('P', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetTitle('Invoice');

    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 12);

    // Add content
    $pdf->Cell(0, 10, 'Invoice', 0, 1, 'C');
    $pdf->Cell(50, 10, 'Invoice Number:', 0, 0);
    $pdf->Cell(0, 10, $invoiceData['invoice_number'], 0, 1);
    $pdf->Output('invoice.pdf', 'I');
}

// Example usage
$invoiceData = [
    'invoice_number' => 'INV-12345',
    'date' => '2024-12-18',
    'customer_name' => 'John Doe',
    'customer_address' => '123 Main Street, City, Country',
    'items' => [
        ['name' => 'Product 1', 'quantity' => 2, 'unit_price' => 50.00],
        ['name' => 'Product 2', 'quantity' => 1, 'unit_price' => 150.00],
        ['name' => 'Product 3', 'quantity' => 3, 'unit_price' => 30.00],
    ],
];

createInvoiceWithoutHTML($invoiceData);
?>