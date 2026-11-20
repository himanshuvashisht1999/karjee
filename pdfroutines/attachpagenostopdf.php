<?php
use \setasign\Fpdi\Fpdi;
function setpagenumbers($inputpdfpath)
{
	require_once('fpdf/fpdf.php');
	require_once('fpdi/autoload.php');
	try 
	{
		$pdf = new FPDI();
		$pagecount = $pdf->setSourceFile($inputpdfpath);
		
		$pdf->AddPage();
		
		for($i=1;$i<=$pagecount;$i++) 
		{
			$tpl = $pdf->importPage($i);
			$pdf->useTemplate($tpl, ['adjustPageSize' => true]);
			$overlaytext = "Page No ".$pdf->PageNo()." of ".$pagecount ;
			$pdf->SetFont('Arial','B',14);
			$pdf->SetDrawColor(0,0,0);
			$pdf->SetFillColor(211,211,211);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetAutoPageBreak(false);

			$pgw = $pdf->GetPageWidth();
			$pgh = $pdf->GetPageHeight();

			$txw = $pdf->GetStringWidth($overlaytext)+6;
			$xcord = $pgw - $txw;
			$ycord = $pgh - 12;

			$pdf->SetXY($xcord,$ycord);
			$pdf->cell($txw,6,$overlaytext,1,0,'C',true);

			if ($i != $pagecount) 
			{
        		$pdf->AddPage();
    		}
		}	
		$pdf->Output($inputpdfpath, "F");
		return 1;
	}
	catch(Exception $e) 
	{
	  return $e;
	}
}
?>