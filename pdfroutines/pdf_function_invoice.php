<?php
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', 1);
DEFINE("_tcpdflocation",$_SERVER['DOCUMENT_ROOT'] . '/krf_amanaccounting/'); 
//require_once(_tcpdflocation.'tcpdf/tcpdf.php');
require_once('tcpdf/tcpdf.php');
include(_tcpdflocation.'createinvoice/function/functionmaster_inv.php');
 
   class CustomPDF extends TCPDF {
    public $headerImage;
    public $footerImage;
    public $companyname;
    public $A1;
    public $A2;
    public $B1;
    public $B2;

   /* public function setHeaderImage($headerImage, $compname, $A1, $A2) {
        $this->headerImage = $headerImage;
        $this->setCompanyName($compname);
        $this->setA1($A1);
        $this->setA2($A2);
    }*/
    public function setHeaderImage($headerImage, $compname, $A1, $A2) {
    // Check if the image URL is valid and accessible
    if (filter_var($headerImage, FILTER_VALIDATE_URL)) {
        $imageData = @file_get_contents($headerImage); // Try to fetch the image content
        if ($imageData === false) {
            echo "Error: Unable to fetch image from URL.";
            $this->headerImage = null;
        } else {
            $this->headerImage = $headerImage;
        }
    } else {
        echo "Invalid image URL: " . $headerImage;
        $this->headerImage = null;
    }
    
    $this->setCompanyName($compname);
    $this->setA1($A1);
    $this->setA2($A2);
}


    /*public function setFooterImage($footerImage, $B1, $B2) {
        // Validate if the provided headerImage is a valid URL
        if (filter_var($footerImage, FILTER_VALIDATE_URL)) {
            $this->footerImage = $footerImage; // Set the header image URL
        } else {
            // If it's not a valid URL, you can either set a default or handle the error
            echo "Invalid image URL: " . $headerImage;
            // Optionally, you could set a default image or leave it as null
            $this->footerImage = null; // Or set a default local path
        }
        
        $this->setA1($B1);
        $this->setA2($B2);
    }*/

    /*public function setFooterImage($footerImage, $B1, $B2) {
        $this->footerImage = $footerImage;
        $this->setB1($B1);
        $this->setB2($B2);
    }*/

    public function setCompanyName($compname) {
        $this->companyname = $compname;
    }

    public function setA1($A1) {
        $this->A1 = $A1;
    }

    public function setA2($A2) {
        $this->A2 = $A2;
    }

    public function setB1($B1) {
        $this->B1 = $B1;
    }

    public function setB2($B2) {
        $this->B2 = $B2;
    }

    public function Header() {
        if ($this->headerImage) {
            if (isset($this->A1) && isset($this->A2)) {
                $this->Image($this->headerImage, 0, 0, $this->A1, $this->A2); // Set image with custom width and height
            } else {
                // Default dimensions if A1 and A2 are not set
                $this->Image($this->headerImage, 0, 0, 210, 30); // Default to 210mm x 30mm
            }
        }
    }

    public function Footer() {
        if ($this->footerImage) {
            if (isset($this->B1) && isset($this->B2)) {
                $this->SetY($this->B1); // Position the footer image 30mm from the bottom
                $this->Image($this->footerImage, 0, $this->GetY(), $this->B2); // Adjust position and width for footer image
            } else {
                // Default positioning if B1 and B2 are not set
                $this->SetY(-15); // Set footer 15mm from the bottom
                $this->Image($this->footerImage, 0, $this->GetY(), 210); // Default to full width
            }
        }
    }
}

// include('../pdfroutines/htmltopdf.php');
// require_once('../pdfroutines/tcpdf/tcpdf.php');
// include('/function/functionmaster_inv.php'); 
// include('../connection.php');
// include('../functions/function_master.php');
// include('layoutfunction.php');
/*$gstselection = "";
$action=$_REQUEST['action'];

$invoice_id = $_POST['id'];
$companyid = $_POST['companyid'];
$companyname = $_POST['companyname'];
$subcompanyid = $_POST['subcompanyid'];
$projectid = $_POST['projectid'];
$invoiceno = $_POST['invoiceno'];
$invoicecategory= $_POST['invoicecategory']; 

$tablemain="tbl_invoices";
$tablebrk="tbl_invoices_breakdown";
$filterqry="invoiceno"; 

$filename=$subdir."/".$invoiceno.".pdf";
if ($invoicecategory=="proforma"){
    $tablemain="tbl_proformainvoices";
    $tablebrk="tbl_proformainvoices_breakdown";
    $filterqry="proformainvoiceno";
}
if ($action=="type2") {   
    $a1="";       
    $a2="";       
    $b1="";       
    $b2="";
    $footerImage="";   
    $headerImage="";
	$qryget="Select a1, a2 ,b1, b2 , pathoffooter, pathofheader, ifnull(taxtype,'GST') as taxtype from tbl_definepositionofheaderfooter where projectid='".$projectid."'";    
	$resulthr=$conn->query($qryget);
    $taxtype="";
	if ($resulthr->num_rows>0) {
		$rowhr=	$resulthr->fetch_assoc();
		$a1=$rowhr['a1'];		
		$a2=$rowhr['a2'];		
		$b1=$rowhr['b1'];		
		$b2=$rowhr['b2']; 
        $taxtype=$rowhr['taxtype'];   	
        $footerImage="../".$rowhr['pathoffooter'];     
        $headerImage="../".$rowhr['pathofheader'];  		
	}		 
    if (!file_exists($headerImage)) {
   	     $headerImage="../createinvoice/companyheaderfooterforinvoice/header.jpg";
    }
    if (!file_exists($footerImage)) {
         $footerImage="../createinvoice/companyheaderfooterforinvoice/footer.jpg";
    } 

    $invoiceinfo=getinvoicedetails($conn,$companyid,$subcompanyid,$projectid,$filterqry,$invoiceno);
    $invoicebreakdowninfo = getinvoice_breakdowndetails($conn,$filterqry,$invoiceno); 
    echo createPDFWithHeaderFooter($headerImage, $footerImage, $filename,$companyname,$invoiceinfo,$invoicebreakdowninfo,$a1,$a2,$b1,$b2,$taxtype,$invoicecategory);
}*/
function createPDF_type2_simple( $conn,$companyid,$subcompanyid,$projectid, $compname,$finyr,$invoicecategory,$invoiceno,$baseurl) {
    //include('functions/functionmaster_inv.php');


    $tablemain="tbl_invoices";
    //$tablebrk="tbl_invoices_breakdown";     
    if ($invoicecategory=="proforma"){
        $tablemain="tbl_proformainvoices";
        //$tablebrk="tbl_proformainvoices_breakdown";
         
    }
    $maindir=$baseurl."attachementfile";
   // $maindir=$baseurl"../attachementfile";
    createdir($maindir);
    $subdir=$maindir."/".$invoicecategory;
    createdir($subdir);
    $subdir=$subdir."/".$companyid;
    createdir($subdir);
    $subdir=$subdir."/".$subcompanyid;
    createdir($subdir);
    $subdir=$subdir."/".$projectid;
    createdir($subdir);
    $filename=$subdir."/".$invoiceno.".pdf";

    $a1="";       
    $a2="";       
    $b1="";       
    $b2="";
    $footerImage="";   
    $headerImage="";

    $qryget="Select a1, a2 ,b1, b2 , pathoffooter, pathofheader, ifnull(taxtype,'GST') as taxtype from tbl_definepositionofheaderfooter where projectid='".$projectid."'";    
    $resulthr=$conn->query($qryget);
    $taxtype="";
    if ($resulthr->num_rows>0) {
        $rowhr= $resulthr->fetch_assoc();
        $a1=$rowhr['a1'];       
        $a2=$rowhr['a2'];       
        $b1=$rowhr['b1'];       
        $b2=$rowhr['b2']; 
        $taxtype=$rowhr['taxtype'];       
        // $footerImage="../".$rowhr['pathoffooter'];     
        // $headerImage="../".$rowhr['pathofheader'];    

        $footerImage=$baseurl.$rowhr['pathoffooter'];       
        $headerImage=$baseurl.$rowhr['pathofheader'];  


        $server_footerImage= '../'.$rowhr['pathoffooter'];       
        $server_headerImage='../'.$rowhr['pathofheader'];      
    }        
    // if (!file_exists($server_headerImage)) {
    //    echo  $headerImage;
    //      $headerImage=$baseurl."createinvoice/companyheaderfooterforinvoice/header.jpg";
    // }
    // if (!file_exists($server_footerImage)) {
    //      $footerImage=$baseurl ."createinvoice/companyheaderfooterforinvoice/footer.jpg";
    // }   
    $invoiceinfo=getinvoicemaindetail($conn,$companyid,$subcompanyid,$projectid,$tablemain,$finyr,"invoiceno",$invoiceno);
    //$invoicebreakdowninfo = getinvoice_breakdowndetails($conn,$filterqry,$invoiceno);

    // Extend TCPDF class to create custom header and footer 

    $clientaddress="";
    $clientemail="";
    $invoiceno="";
    $invoicedate="";
    $currency="";
    $clientadd="";
    $clientphoneno="";
    $currencysy="";
    $clientname="";
    $itemname="";
    $referencetxt="";
    $vatpercent="";
    $gst_percent="";
    $igst_percent="";
    $ttl_itemamount_excludingtax="";
    $ttl_itemamount_includetax="";
    if(count($invoiceinfo)>0){
        foreach ($invoiceinfo as $invmkey) {  
            $invoicedate = $invmkey['dispinvoice_date'];
            $clientname = $invmkey['client_name'];
            $currencysy = $invmkey['currency'];
            $clientadd = $invmkey['BillAddrLine1'];
            $clientemail= $invmkey['emailid'];
            $clientphoneno= $invmkey['phoneno']; 
            $itemname=$invmkey['description'];
            $referencetxt=$invmkey['remarks'];
             $vatpercent=$invbkey['vatpercent'];
            $gst_percent=$invbkey['cgstpercent']; 
            $igst_percent=$invbkey['igstpercent'];
            $ttl_itemamount_excludingtax=$invbkey['totalamount_excludingtax'];
            $ttl_itemamount_includetax=$invbkey['totalamount_includetax'];
 
        }
    } else{
        return "No Data Found";

    }

    // if(count($invoicebreakdowninfo)>0){
    //     foreach ($invoicebreakdowninfo as $invbkey) {
    //         $itemname=$invbkey['items_name'];
    //         $qty=$invbkey['units'];
    //         $itemrate=$invbkey['rate'];
    //         $itemtaxableamount=$invbkey['taxable_amount'];
    //         $vatpercent=$invbkey['vat_percent'];
    //         $vatamount=$invbkey['vat_amount'];
    //         $ttl_itemamount_excludingtax=$invbkey['totalamount_excludingtax'];
    //         $ttl_itemamount_includetax=$invbkey['totalamount_includetax'];
    //         $referencetxt=$invbkey['referencenos'];
    //         $gst_percent=$invbkey['gst_percent']; 
    //         $igst_percent=$invbkey['igst_percent'];
    //     }
    // }        
 
    // // Create new PDF document
    // $pdf = new CustomPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
    // // set document information
    // $pdf->SetCreator(PDF_CREATOR);
    // $pdf->SetAuthor('Nicola Asuni');
    // $pdf->SetTitle('TCPDF Example 021');
    // $pdf->SetSubject('TCPDF Tutorial');
    // $pdf->SetKeywords('TCPDF, PDF, example, test, guide');

    // // Set header and footer images
    // $pdf->setHeaderImage($headerImage,$compname,$a1,$a2);
    // $pdf->setFooterImage($footerImage,$b1,$b2);

    // // Set reduced margins and auto page break
    // $pdf->SetMargins(5, 55, 5); // Increased top margin to create space for the header
    // $pdf->SetHeaderMargin(5);
    // $pdf->SetFooterMargin(5);
    // $pdf->SetAutoPageBreak(true, 20); 
    // // set image scale factor
    // $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        


    // set some language-dependent strings (optional)

   //  if (@file_exists(_tcpdflocation.'/lang/eng.php')) {
   //      require_once(_tcpdflocation.'/lang/eng.php');
   //      $pdf->setLanguageArray($l);
   // } 
   if (@file_exists('tcpdf/lang/eng.php')) {
        require_once('tcpdf/lang/eng.php');
        $pdf->setLanguageArray($l);
   } 
   //$pdf = new TCPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);

     $pdf = new CustomPDF('P', PDF_UNIT, 'A4', true, 'UTF-8', false);
    //$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    //$pdf->setPageOrientation('P');
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Nicola Asuni');
    $pdf->SetTitle('TCPDF Example 021');
    $pdf->SetSubject('TCPDF Tutorial');
    $pdf->SetKeywords('TCPDF, PDF, example, test, guide');
 
    // Set header and footer images
    //$pdf->setHeaderImage($headerImage,$compname,$a1,$a2);
    //$pdf->setFooterImage($footerImage,$b1,$b2);

    // Set reduced margins and auto page break
    $pdf->SetMargins(5, 55, 5); // Increased top margin to create space for the header
    $pdf->SetHeaderMargin(5);
    $pdf->SetFooterMargin(5);
    $pdf->SetAutoPageBreak(true, 20); 
    // set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    // set font
    $pdf->SetFont('helvetica', '', 9);
    // add a page
    $pdf->AddPage();
    // create invoice
    if ($invoicecategory=="proforma"){
        $html='PROFORMA';
    }else{
        $html='INVOICE';
    }   
    // Set the font
    $pdf->SetFont('helvetica', 'B', 22); // Font: Helvetica, Bold, Size: 16
    $pdf->SetXY(80, 30); // Move to X=50, Y=100
    $pdf->Cell(50, 10, $html, 0, 1, 'C');

    // Draw a horizontal line below the title
    $pdf->SetXY(50, 40); // Set Y to position of the line (a bit below the text)
    $pdf->Line(10, 42, 200, 42); // Draw line from X=50 to X=160 at Y=40 
    // Set the font

    $pdf->SetFont('times', '', 14); // Font: Helvetica, Bold, Size: 16
    $pdf->SetXY(30, 71); // Move to X=50, Y=100
    // Define the cell width and line height
    $cellWidth = 100; // Fixed cell width
    $lineHeight = 7; // Line height
    
    $pagewidth_exmargin=round($pdf->GetPageWidth(),2) -10; 
    $totalHeight_exhf=round($pdf->GetPageHeight(),2)  ; 
    $totalHeight_exhf=$totalHeight_exhf-30; 

    $startX=100;
    $endX =$pagewidth_exmargin;
    $startY= 50;
    $FontFamily='helvetica';
    $fontbold='';
    $fontSize='12';
    $border=0;
    $align='R';
    $fill=false;


    $textstring="Invoice: ".$invoiceno; 
   // $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily,$fontbold, $fontSize, $border, $align,   $fill) ;
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    $startY = floatval($result['ycoordinate'])+2; 
    $pdf = $result['pdf']; 
     exit();
    //$fontSize='12';
    //$border=0;
    $textstring="Date: ".$invoicedate ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }

    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    $startY =floatval($result['ycoordinate'])+10; 
    $pdf = $result['pdf'];  

    $startX=30;
    $endX =$pagewidth_exmargin-50;
    $align='L';
    $textstring=$clientname ;

    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    } 

    $startY =floatval($result['ycoordinate'])+2; 
    $pdf = $result['pdf'];   

    $textstring="Address: ".$clientadd ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }   
    $startY =floatval($result['ycoordinate'])+2; 
    $pdf = $result['pdf'];  

    $textstring="Phone No: ".$clientphoneno ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    } 

    $startY =floatval($result['ycoordinate'])+2; 
    $pdf = $result['pdf'];   
     
    $textstring="Email: ".$clientemail ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }

    $startY =floatval($result['ycoordinate'])+20; 
    $pdf = $result['pdf'];  

    $startX=30;
    $endX =$pagewidth_exmargin-100;
    $align='L';
    $textstring=$itemname ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    } 

    $startX=150;
    $endX =$pagewidth_exmargin;
    $align='R';
    $textstring=$currencysy ." " .round(floatval($ttl_itemamount_excludingtax),2) ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    } 
    $startY =floatval($result['ycoordinate'])+5; 
    $pdf = $result['pdf'];
    if(floatval($vatpercent)> 0 || floatval($gst_percent)>0  || floatval($igst_percent)>0){
        $startX=30;
        $align='L';
        $endX =$pagewidth_exmargin-100;
        if($taxtype=="VAT"){
            $textstring="VAT @ ".$vatpercent."%";
        }else{
            $textstring="GST @ " . (floatval($cgst) + floatval($igst)) . "%";
        }
        $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        }
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        }

        if($taxtype=="VAT"){
            $textstring=round(floatval($ttl_itemamount_excludingtax) * (floatval($vatpercent) / 100), 2);
        }else{
            $textstring= round(floatval($ttl_itemamount_excludingtax)*((floatval($igst_percent)+floatval($gst_percent))/100),2);
        }
        $startX=150;
        $align='R';
        $endX =$pagewidth_exmargin;
        $textstring=$currencysy ." " .$textstring ;
        $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        }
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        } 
    }  else{

        $startX=30;
        $endX =$pagewidth_exmargin-100;
        $align='L';
        if($taxtype=="VAT"){
            $textstring="VAT %";
        }else{
            $textstring="GST %" ;
        }
        $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        }
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        } 
        $align='R';
        $textstring= "NIL"; 
        $startX=150;
        $endX =$pagewidth_exmargin; 
        $textstring= $textstring ;
        $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        }
        if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
            return $result['ycoordinate'];
            exit();        
        } 
    }

    $startY =floatval($result['ycoordinate'])+20; 
    $pdf = $result['pdf'];  

    $startX=30;
    $endX =$pagewidth_exmargin-100;
    $align='C';
    $fontbold="B";
    $textstring="Total" ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    } 

    $startX=150;
    $endX =$pagewidth_exmargin;
    $align='R';
    $textstring=$currencysy ." " .round(floatval($ttl_itemamount_includetax),2) ;
    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ;   
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    } 


    $startY =floatval($result['ycoordinate'])+10; 
    $pdf = $result['pdf'];  

    $startX=30;
    $endX =$pagewidth_exmargin;
    $align='L';
    $textstring="Ref No. " .$referencetxt ;
    $fontbold="";

    $result=pdfwriteandgetposition_Y($pdf,$pagewidth_exmargin,$totalHeight_exhf,$startX,$endX,$startY, $textstring, $FontFamily, $fontbold, $fontSize, $border, $align,$fill) ; 


    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    }
    if($result['ycoordinate']=="X arguments are incorrect." ||$result['ycoordinate']=="You have reached the end of the page or the Y arguments are incorrect."){
        return $result['ycoordinate'];
        exit();        
    } 



    // echo "last ycoordinate ". $startY ;
    // $currentX = $pdf->GetX();
    // echo "kkk". $currentX ;
    //$startY =floatval($result['ycoordinate'])+5; 
    $pdf = $result['pdf'];
     $pdf->Output("deabc.pdf", 'f');
    //$pdf->Output($filename, 'f');   
    return "deabc.pdf";
}
function calculateWidthHeightUsingTCPDF($FontFamily, $fontSize, $fontbold, $text) {
    // Create a new TCPDF instance
    $pdf = new TCPDF();    
    // Set the font
    $pdf->SetFont($FontFamily, $fontbold, $fontSize);    
    // Get the width of the text
    $textWidth = $pdf->GetStringWidth($text);    
    // Ensure the text width is a float
    $textWidth = (float)$textWidth;    
    // Height for single-line text (font size in points is approximately the height in points)
    $textHeight = (float)$fontSize;    
    // Convert points to millimeters (1 point = 0.3527778 mm)
    $textWidthMM = $textWidth * 0.3527778;
    $textHeightMM = $textHeight * 0.3527778;
    
    // Return the width and height in millimeters
    return round($textWidthMM, 1) . "|" . round($textHeightMM, 1);
}
function calculateNextYAfterMultiCell($pdf, $currentX, $currentY, $width, $height, $text, $border = 0, $align = 'L', $fill = false) {
    // Set the initial position
    $pdf->SetXY($currentX, $currentY);

    // Write the MultiCell
    $pdf->MultiCell($width, $height, $text, $border, $align, $fill);

    // Get the new Y position after writing the MultiCell
    $nextY = $pdf->GetY();

    return $nextY ; 
} 
function pdfwriteandgetposition_Y($pdf, $maxX, $maxY, $startX, $endX, $startY, $textstring, $FontFamily, $fontbold, $fontsize, $border = 0, $align = 'L', $fill = false) {
    if($endX<=$startX)  {
        return array("ycoordinate" => "X arguments are incorrect.", "pdf" => $pdf);
        exit();
    }
    if($startY>=$maxY)  {
        return array("ycoordinate" => "You have reached the end of the page or the Y arguments are incorrect.", "pdf" => $pdf);
        exit();
    }

    $return_ycoordinate = ""; 
    $pdf->SetFont($FontFamily, $fontbold, $fontsize);
    $pdf->SetXY($startX, $startY);
    // Calculate width and height of the text
    $cal_hght_wdth = calculateWidthHeightUsingTCPDF($FontFamily, $fontsize, $fontbold, $textstring);    
    // Initialize text width and height
    $txtcellwidth = "";
    $txtcellhght = "";
    // Split the width and height
    if (count(explode("|", $cal_hght_wdth)) == 2) {
        $explode_String = explode("|", $cal_hght_wdth);
        $txtcellwidth = (float)$explode_String[0]; // Ensure it's a float
        $txtcellhght = (float)$explode_String[1];  // Ensure it's a float
    } 
    $act_cellwdth = $endX - $startX; 
    // Check if the text fits within the available width
    if (($act_cellwdth - $txtcellwidth) > 0) {
        // Text fits in a single line
        $pdf->Cell($act_cellwdth, $txtcellhght, $textstring, $border, 1, $align, $fill);
        $return_ycoordinate = $startY + $txtcellhght; // Update Y position
    } else {
        // Text doesn't fit, use MultiCell 
        $pdf->MultiCell($act_cellwdth, $txtcellhght, $textstring, $border, $align, $fill, 1);
        $return_ycoordinate = calculateNextYAfterMultiCell($pdf, $startX, $startY, $act_cellwdth, $txtcellhght, $textstring, $border, $align, $fill);
    } 
    return array("ycoordinate" => $return_ycoordinate, "pdf" => $pdf);
}
function get_heightofpage($pdf, $headerHeight, $footerHeight) {
    $totalHeight = $pdf->GetPageHeight();
    return $totalHeight - $headerHeight - $footerHeight;
}

function pdfbottomalignment($pdf, $maxX, $maxY, $startX, $endX, $startY, $textstring, $FontFamily, $fontbold, $fontsize, $border = 0, $align = 'L', $fill = false) {
 
    $cal_hght_wdth = calculateWidthHeightUsingTCPDF($FontFamily, $fontsize, $fontbold, $textstring);    
    // Initialize text width and height
    $txtcellwidth = "";
    $txtcellhght = "";
    // Split the width and height
    if (count(explode("|", $cal_hght_wdth)) == 2) {
        $explode_String = explode("|", $cal_hght_wdth);
        $txtcellwidth = (float)$explode_String[0]; // Ensure it's a float
        $txtcellhght = (float)$explode_String[1];  // Ensure it's a float
    } 

    $act_cellwdth = $endX - $startX; 

    // Calculate the height of the text
    $textHeight = $pdf->getStringHeight($act_cellwdth, $textstring);

    // Calculate the vertical offset to align text at the bottom
    $verticalOffset = $txtcellhght - $textHeight;

    // Adjust the Y position of the cursor
    // echo "max y: ".$maxY. "  start y: ".$startY  ." verticalOffset" .$verticalOffset;
    // echo "final coord ". $yyc=($maxY-($verticalOffset+50));
    $pdf->SetXY($startX ,$yyc );

    // Now print the MultiCell
    $pdf->MultiCell($act_cellwdth, $textHeight, $textstring, 1, $align, $fill, 1);
    return array("ycoordinate" => $return_ycoordinate, "pdf" => $pdf);

}

 function createdir($dirpath){
   
    if (!is_dir($dirpath)) {
          mkdir($dirpath, 0777, true);
    }
 }
?>



