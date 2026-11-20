<?php
require_once('tcpdf/tcpdf.php');
function generatepdf($html,$outputfilepath,$orient='L',$imglogo='',$signature='') 
{
    $saff="";
    try
    {
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->setPageOrientation($orient);
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);//PDF_MARGIN_TOP
            $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);//PDF_MARGIN_HEADER
            $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
            $pdf->SetFont('dejavusans', '', 10);
			$pdf->setPrintHeader(false);
			//$pdf->setPrintFooter(false);
			// set image scale factor
			$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

            $pdf->AddPage();
			$pdf->setJPEGQuality(75);
            
			if($imglogo){
				$pgw = $pdf->GetPageWidth();

				$xcord = $pgw - 35;
		
				$x = $xcord /2;
				// $x = $pgw /2;
				$y = 5;
				$w = 50;
				$h = 20;
				$fitbox = 'C';

				$fitbox[1] = 'T';
				$pdf->Rect($x, $y, $w, $h, 'F', array(), array(255,255,255));
				$pdf->Image($imglogo, $x, $y, $w, $h, '', '', '', false, 75, '', false, false, 0, $fitbox, false, false);
			}
			
			$pdf->writeHTML($html, true, false, true, false, '');
			
            $pdf->lastPage();

			if($signature){
				$pgw = $pdf->GetPageWidth();
				$pgh = $pdf->GetPageHeight();
		
				$ycord = $pgh - 50;
				$xcord = $pgw - 80;
				// echo "<script>alert('".$xc."')</script>"
				// $x = $xcord /2;
				$x = 30;
				$y = $ycord;
				$w = 80;
				$h = 30;
				$fitbox = 'C';

				$fitbox[1] = 'T';
				$pdf->Rect($x, $y, $w, $h/2, 'D', array(), array(255,255,255));
				$pdf->Image($signature, $x, $y, $w, $h, '', '', '', false, 75, '', false, false, 0, $fitbox, false, false);
			}
            $pdf->Output($outputfilepath, 'F');
        return true;
    }
    catch(Exception $e) 
    {
        return false;
    } 
}
?>