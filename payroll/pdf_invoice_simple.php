<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 1);
// include('../pdfroutines/htmltopdf.php');
require_once('../pdfroutines/tcpdf/tcpdf.php');
include('../connection.php');
include('../functions/function_master.php');
// include('layoutfunction.php');
$gstselection = "";
$action=$_REQUEST['action'];
$invoice_id = $_REQUEST['id'];
$companyid = $_POST['companyid'];
$companyname = $_POST['companyname'];
$subcompanyid = $_POST['subcompanyid'];
$projectid = $_POST['projectid'];
$invoiceno = $_POST['invoiceno'];
// $action="PDF_Simple";
// $invoice_id = "80";
// $companyid = "16";
// $companyname = "Active 4x4 Adventures Ltd";
// $subcompanyid = "9";
// $projectid = "20";
// $invoiceno = "SIN-16920-241220-1";

if ($action=="PDF_Simple") {
	$tablemain="";
    $tablebrk="";
    $invoicecategory="";

     $tablemain="tbl_invoices";
     $tablebrk="tbl_invoices_breakdown";
     $invoicecategory="Invoice";

    $folderName = "../InvoiceDocument";
    $folderNameinvoice = "../InvoiceDocument/".$invoiceno;
    if (!file_exists($folderName)) {
        if (mkdir($folderName, 0777)) {
           
        } 
    }
    if (!file_exists($folderNameinvoice)) {
        if (mkdir($folderNameinvoice, 0777)) {
           
        } 
    }
	$qryget="Select a1,a2,b1,b2 from tbl_definepositionofheaderfooter where projectid='".$projectid."'";
	$resulthr=$conn->query($qryget);
	if ($resulthr->num_rows>0) {
		$rowhr=	$resulthr->fetch_assoc();
		$a1=$rowhr['a1'];		
		$a2=$rowhr['a2'];		
		$b1=$rowhr['b1'];		
		$b2=$rowhr['b2'];			
	}	
	//$content=layoutA($conn,$companyid,$subcompanyid,$projectid,$invoice_id);
	//$content=layoutB($conn,$companyid,$subcompanyid,$projectid,$invoice_id);
	//$content=layout0($conn,$companyid,$subcompanyid,$projectid,$invoice_id);
			
				
	
	$headerImage="../createinvoice/companyheaderfooterforinvoice/".$companyid."/".$companyid."_".$subcompanyid."_".$projectid."_header.jpg";
	$footerImage="../createinvoice/companyheaderfooterforinvoice/".$companyid."/".$companyid."_".$subcompanyid."_".$projectid."_footer.jpg";
    if (file_exists($headerImage)) {
   	    $companyname="";
    }else{
   	    $headerImage="../createinvoice/companyheaderfooterforinvoice/header.jpg";
    }
    if (file_exists($footerImage)) {
    }else{
   	    $footerImage="../createinvoice/companyheaderfooterforinvoice/footer.jpg";
    }
	$returnmasg=array();
	$savepdfname=$invoicecategory."_".$invoice_no.".pdf";

    $invoiceinfo=getinvoicedetails($conn,$companyid,$subcompanyid,$projectid,"invoiceno",$invoiceno);
    $invoicebreakdowninfo = getinvoice_breakdowndetails($conn,"invoiceno",$invoiceno);
	
	createPDFWithHeaderFooter($headerImage, $footerImage, $folderNameinvoice."/".$savepdfname,$companyname,$invoiceinfo,$invoicebreakdowninfo,$a1,$a2,$b1,$b2);
}


function createPDFWithHeaderFooter($headerImage, $footerImage, $outputFile,$compname,$invoiceinfo,$invoicebreakdowninfo,$a1,$a2,$b1,$b2) {
    // Extend TCPDF class to create custom header and footer

    
    class CustomPDF extends TCPDF {
        public $headerImage;
        public $footerImage;
        public $companyname;
        public $a1;
        public $a2;
        public $b1;
        public $b2;
       
        public function setHeaderImage($headerImage,$compname,$a1,$a2) {
            $this->headerImage = $headerImage;
            $this->setCompanyName($compname);
            $this->A1($a1);
            $this->A2($a2);
           
           
        }

        public function setFooterImage($footerImage,$b1,$b2) {
            $this->footerImage = $footerImage;
             $this->B1($b1);
            $this->B2($b2);
        }
        public function setCompanyName($compname) {
            $this->companyname = $compname;
        }

        public function A1($a1) {
            $this->A1 = $a1;
        }
        public function A2($a2) {
            $this->A2 = $a2;
        }
        public function B1($b1) {
            $this->B1 = $b1;
        }
        public function B2($b2) {
            $this->B2 = $b2;
        }
        public function Header() {
		    if ($this->headerImage) {
		        // Set image with custom width and height
		         $this->Image($this->headerImage, 0, 0, $this->A1, $this->A2); // Width: 210mm, Height: 30mm
		        // Set the position for the header text
		       // $this->SetY(0); // Adjust Y position to avoid overlapping with the image
		        //$this->SetFont('helvetica', 'B', 16); // Font style and size
		        // $this->SetTextColor(0, 0, 0); // Black text color
		        // $this->SetXY(80, 10); // Adjust text position relative to the image
		        // $this->Cell(0, 10, $this->companyname, 0, 0, 'L', false); // Add the text
		    }
		}

        // Page footer
        public function Footer() {
            if ($this->footerImage) {
                $this->SetY($this->B1); // Position the footer image 30mm from the bottom 

                // $this->Image($file, $x, $y, $w=0, $h=0, $type='', $link='', $align='', $resize=false, $dpi=300, $palign='', $ismask=false, $imgmask=false, $border=0, $fitbox=false, $hidden=false, $fitonpage=false);
                $this->Image($this->footerImage, 0, $this->GetY(), $this->B2); // Adjust position and width for footer image                
            }
            // Additional footer text or page number
            // $this->SetY(-15);
            // $this->SetFont('helvetica', 'I', 8);
            // $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
        }
    }

    $clientaddress="";
    $clientemail="";
    $invoiceno="";
    $invoicedate="";
    if(count($invoiceinfo)>0){
        foreach ($invoiceinfo as $invmkey) {
            $invoiceno = $invmkey['invoice_no'];
            $invoicedate = $invmkey['dispinvoice_date'];
            $clientname = $invmkey['client_name'];
        }
    }


    // Create new PDF document
    $pdf = new CustomPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 021');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

    // Set header and footer images
    $pdf->setHeaderImage($headerImage,$compname,$a1,$a2);
    $pdf->setFooterImage($footerImage,$b1,$b2);

    // Set reduced margins and auto page break
    $pdf->SetMargins(5, 55, 5); // Increased top margin to create space for the header
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(5);
    $pdf->SetAutoPageBreak(true, 20);

    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    // set some language-dependent strings (optional)
    if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
        require_once(dirname(__FILE__).'/lang/eng.php');
        $pdf->setLanguageArray($l);
    }

    // ---------------------------------------------------------

    // set font
    $pdf->SetFont('helvetica', '', 9);
    // add a page
    $pdf->AddPage();

    // create invoice
    $html='INVOICE';
    // Set the font
    $pdf->SetFont('helvetica', 'B', 22); // Font: Helvetica, Bold, Size: 16
    $pdf->SetXY(80, 30); // Move to X=50, Y=100
    $pdf->Cell(50, 10, $html, 0, 1, 'C');

    // Draw a horizontal line below the title
    $pdf->SetXY(50, 40); // Set Y to position of the line (a bit below the text)
    $pdf->Line(10, 42, 200, 42); // Draw line from X=50 to X=160 at Y=40


    // create invoice
    // $clientname='Athena Knitwer Ltd.';

    // Set the font
    $pdf->SetFont('times', '', 14); // Font: Helvetica, Bold, Size: 16
    $pdf->SetXY(30, 71); // Move to X=50, Y=100
    // Define the cell width and line height
    $cellWidth = 100; // Fixed cell width
    $lineHeight = 7; // Line height

    // Calculate the number of lines required
    $textWidth = $pdf->GetStringWidth($clientname);
    $lines = ceil($textWidth / $cellWidth); // Calculate number of lines

    // Calculate the total height
    $cellHeight = $lines * $lineHeight;

    // Draw the cell dynamically
    $pdf->MultiCell($cellWidth, $lineHeight, $clientname, 0, 'L');
    // $pdf->Cell(50, 10, $clientname, 0, 0, 'L');

    $clientaddress='Address:- House NO 689, Sector 23, Gali no56, Sub sector 256, Gurugram, Haryana ';
    if($clientaddress){
        // Set the font
        $pdf->SetFont('times', '', 12); // Font: Helvetica, Bold, Size: 16
        $pdf->SetXY(30, 77); // Move to X=50, Y=100
        // Define the cell width and line height
        $cellWidth = 100; // Fixed cell width
        $lineHeight = 7; // Line height

        // Calculate the number of lines required
        $textWidth = $pdf->GetStringWidth($clientaddress);
        $lines = ceil($textWidth / $cellWidth); // Calculate number of lines

        // Calculate the total height
        $cellHeight = $lines * $lineHeight;

        // Draw the cell dynamically
        $pdf->MultiCell($cellWidth, $lineHeight, $clientaddress, 0, 'L');
        // $pdf->Cell(50, 10, $clientaddress, 0, 1, 'L');
    }

    // $clientemail='Email:- Email@gmail.com';
    if($clientemail){
        // Set the font
        $pdf->SetFont('times', '', 12); // Font: Helvetica, Bold, Size: 16
        $pdf->SetXY(30, 71); // Move to X=50, Y=100
        // Define the cell width and line height
        $cellWidth = 100; // Fixed cell width
        $lineHeight = 7; // Line height

        // Calculate the number of lines required
        $textWidth = $pdf->GetStringWidth($clientemail);
        $lines = ceil($textWidth / $cellWidth); // Calculate number of lines

        // Calculate the total height
        $cellHeight = $lines * $lineHeight;

        // Draw the cell dynamically
        $pdf->MultiCell($cellWidth, $lineHeight, $clientemail, 0, 'L');
        // $pdf->Cell(50, 10, $clientemail, 0, 0, 'L');
    }

    if($invoiceno){
        // create invoice
        // $invoiceno='Invoice No: 25648855';
        // Set the font
        $pdf->SetFont('helvetica', 'B', 12); // Font: Helvetica, Bold, Size: 16
        $pdf->SetXY(150, 50); // Move to X=50, Y=100
        // Define the cell width and line height
        $cellWidth = 50; // Fixed cell width
        $lineHeight = 7; // Line height

        // Calculate the number of lines required
        $textWidth = $pdf->GetStringWidth($invoiceno);
        $lines = ceil($textWidth / $cellWidth); // Calculate number of lines

        // Calculate the total height
        $cellHeight = $lines * $lineHeight;

        // Draw the cell dynamically
        $pdf->MultiCell($cellWidth, $lineHeight, $invoiceno, 0, 'L');
        // $pdf->Cell(50, 10, $invoiceno, 0, 1, 'C');
    }

    if($invoicedate){
        // create invoice
        // $invoicedate='Date: 19-Dec-2024';
        // Set the font
        $pdf->SetFont('helvetica', 'B', 12); // Font: Helvetica, Bold, Size: 16
        $pdf->SetXY(150, 56); // Move to X=50, Y=100
        // Define the cell width and line height
        $cellWidth = 50; // Fixed cell width
        $lineHeight = 7; // Line height

        // Calculate the number of lines required
        $textWidth = $pdf->GetStringWidth($invoicedate);
        $lines = ceil($textWidth / $cellWidth); // Calculate number of lines

        // Calculate the total height
        $cellHeight = $lines * $lineHeight;

        // Draw the cell dynamically
        $pdf->MultiCell($cellWidth, $lineHeight, $invoicedate, 0, 'L');
        // $pdf->Cell(50, 10, $invoicedate, 0, 0, 'C');
    }


    
    // Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false, $link = '', $stretch = 0, $ignore_min_height = false, $calign = 'T', $valign = 'M')
    $itemname="";
    $qty=0;
    $itemrate=0;
    $itemtaxableamount=0;
    $vatpercent=0;
    $vatamount=0;
    $ttl_itemamount_excludingtax=0;
    $ttl_itemamount_includetax=0;
    $referencetxt="";

    if(count($invoicebreakdowninfo)>0){
        foreach ($invoicebreakdowninfo as $invbkey) {
            $itemname=$invbkey['items_name'];
            $qty=$invbkey['units'];
            $itemrate=$invbkey['rate'];
            $itemtaxableamount=$invbkey['taxable_amount'];
            $vatpercent=$invbkey['vat_percent'];
            $vatamount=$invbkey['vat_amount'];
            $ttl_itemamount_excludingtax=$invbkey['totalamount_excludingtax'];
            $ttl_itemamount_includetax=$invbkey['totalamount_includetax'];
            $referencetxt=$invbkey['referencenos'];


            // row 1
            $pdf->SetFont('times', '', 12); // Font: Helvetica, Bold, Size: 16
            $pdf->SetXY(30, 110); // Move to X=50, Y=100
            $pdf->Cell(110, 10, $itemname, 0, 0, 'L');
            $pdf->SetXY(140, 110); // Move to X=50, Y=100
            $pdf->Cell(50, 10, number_format($ttl_itemamount_excludingtax, 2, '.', ','), 0, 1, 'C');

            // row 2
            $pdf->SetXY(30, 120); // Move to X=50, Y=100
            $pdf->Cell(110, 10, 'VAT', 0, 0, 'L');

            $pdf->SetXY(140, 120); // Move to X=50, Y=100
            $pdf->Cell(50, 10, number_format($vatamount, 2, '.', ','), 0, 1, 'C');

            //total row
            $pdf->SetFont('times', 'B', 12);
            $pdf->SetXY(30, 130); // Move to X=50, Y=100
            $pdf->Cell(110, 10, 'Total', 1, 1, 'L');
            
            $pdf->SetXY(140, 130); // Move to X=50, Y=100
            $pdf->Cell(50, 10, number_format($ttl_itemamount_includetax, 2, '.', ','), 1, 1, 'C');
        }
    }



    // reference of develory
    // $referencetxt = 'Ref Delivery No:- `sno` bigint(20) NOT NULL AUTO_INCREMENT, `tsno` bigint(20) DEFAULT NULL, `countryid` varchar(45) DEFAULT NULL, `companyid` varchar(45) DEFAULT NULL, `subcompanyid` varchar(45) DEFAULT NULL, `projectid` varchar(45) DEFAULT NULL,';

    $pdf->SetFont('times', '', 12);
    $pdf->SetXY(30, -60);

    // Define the cell width and line height
    $cellWidth = 160; // Fixed cell width
    $lineHeight = 10; // Line height

    // Calculate the number of lines required
    $textWidth = $pdf->GetStringWidth($referencetxt);
    $lines = ceil($textWidth / $cellWidth); // Calculate number of lines

    // Calculate the total height
    $cellHeight = $lines * $lineHeight;

    // Draw the cell dynamically
    $pdf->MultiCell($cellWidth, $lineHeight, $referencetxt, 0, 'L');

    // output the HTML content
    // $pdf->writeHTML($html, true, 0, true, 0);

// reset pointer to the last page
$pdf->lastPage();

// ---------------------------------------------------------

//Close and output PDF document
$pdf->Output('example_021.pdf', 'I');
}
/*
function createPDFWithHeaderFooter_2($headerImage, $htmlContent, $footerImage, $outputFile,$compname) {
    // Extend TCPDF class to create custom header and footer
    
    class CustomPDF extends TCPDF {
        public $headerImage;
        public $footerImage;
        public $companyname;
       
        public function setHeaderImage($headerImage,$compname) {
            $this->headerImage = $headerImage;
            $this->setCompanyName($compname);
           
        }

        public function setFooterImage($footerImage) {
            $this->footerImage = $footerImage;
        }
        public function setCompanyName($compname) {
            $this->companyname = $compname;
        }
        

        // Page header
        // public function Header() {
        //     if ($this->headerImage) {
        //     	//$this->Image($this->headerImage, 0, 0, 215);
        //         $this->Image($this->headerImage, 0, 0, 215); // Set the width according to the page width
        //         $this->SetY(30); // Move the cursor further down to create space below the header

        //         $this->SetFont('helvetica', 'B', 16); // Set font style and size
	    //         $this->SetTextColor(0, 0, 0); // Set text color (white, for overlay on the image)
	    //         $this->SetXY(80, 10); // Set position (X: 10mm from left, Y: 10mm from top)
	    //         $this->Cell(0, 10, $this->companyname , 0, 0, 'L', false); // Add the text
	    //         $this->SetY(30); // Move the cursor further down for the content below the header

        //     }
        // }
        public function Header() {
		    if ($this->headerImage) {
		        // Set image with custom width and height

		         $this->Image($this->headerImage, 0, 0, 210, 30); // Width: 210mm, Height: 30mm

		       

		        // Set the position for the header text
		       // $this->SetY(0); // Adjust Y position to avoid overlapping with the image
		        //$this->SetFont('helvetica', 'B', 16); // Font style and size
		        // $this->SetTextColor(0, 0, 0); // Black text color
		        // $this->SetXY(80, 10); // Adjust text position relative to the image
		        // $this->Cell(0, 10, $this->companyname, 0, 0, 'L', false); // Add the text
		    }
		}

        // Page footer
        public function Footer() {
            if ($this->footerImage) {
                $this->SetY(-30); // Position the footer image 30mm from the bottom
                
                $this->Image($this->footerImage, 0, $this->GetY(), 210); // Adjust position and width for footer image
                
            }
            // Additional footer text or page number
            $this->SetY(-15);
            $this->SetFont('helvetica', 'I', 8);
            $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
        }
    }

    // Create new PDF document
    $pdf = new CustomPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Author Name');
    $pdf->SetTitle('PDF with Header and Footer');
    $pdf->SetSubject('Generated PDF');
    $pdf->SetKeywords('TCPDF, PDF, header, footer');

    // Set header and footer images
    $pdf->setHeaderImage($headerImage,$compname);
    $pdf->setFooterImage($footerImage);

    // Set reduced margins and auto page break
    $pdf->SetMargins(5, 25, 5); // Increased top margin to create space for the header
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(5);
    $pdf->SetAutoPageBreak(true, 20);

    // Add a page
    $pdf->AddPage();

    // Set font and add HTML content
    $pdf->SetFont('dejavusans', '', 10);
    $pdf->writeHTML($htmlContent, true, false, true, false, '');
    // echo $pdf;
    // Output PDF to file
    $pdf->Output($outputFile, 'F');
}*/
?>



