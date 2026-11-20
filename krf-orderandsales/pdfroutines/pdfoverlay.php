<?php
use \setasign\Fpdi\Fpdi;
function overlay($inputpdfpath,$outputpdfpath,$overlaytext,$area)
{
	require_once('fpdf/fpdf.php');
	require_once('fpdi/autoload.php');
	try 
	{
		$pdf = new FPDI();
		$pdf->AddPage();

		$pagecount = $pdf->setSourceFile($inputpdfpath);

		$tpl = $pdf->importPage(1);
		$pdf->useTemplate($tpl, ['adjustPageSize' => true]);

		$pdf->SetFont('Arial','B',16);
		$pdf->SetDrawColor(0,0,0);
		$pdf->SetFillColor(255,255,255);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetAutoPageBreak(false);

		$pgw = $pdf->GetPageWidth();
		$pgh = $pdf->GetPageHeight();

		$txw = $pdf->GetStringWidth($overlaytext)+6;
		$xcord = $pgw - $txw;
		$ycord = $pgh - 12;

		if($area == "TOP_LEFT")
		{
			$pdf->SetXY(6,6);
			$pdf->cell($txw,6,$overlaytext,1,1,'C',true);
		}
		elseif($area == "TOP_RIGHT")
		{
			$pdf->SetXY($xcord,6);
			$pdf->cell($txw,6,$overlaytext,1,1,'C',true);
		}
		elseif($area == "TOP_MIDDLE")
		{
			$pdf->SetXY(($xcord /2),6);
			$pdf->cell($txw,6,$overlaytext,1,1,'C',true);
		}
		elseif($area == "BOTTOM_LEFT")
		{
			$pdf->SetXY(6,$ycord);
			$pdf->cell($txw,6,$overlaytext,1,0,'C',true);
		}
		elseif($area == "BOTTOM_RIGHT")
		{
			$pdf->SetXY($xcord,$ycord);
			$pdf->cell($txw,6,$overlaytext,1,0,'C',true);
		}
		elseif($area == "BOTTOM_MIDDLE")
		{
			$pdf->SetXY(($xcord /2),$ycord);
			$pdf->cell($txw,6,$overlaytext,1,0,'C',true);
		}
		else
		{
			$pdf->SetXY($xcord,6);
			$pdf->cell($txw,6,$overlaytext,1,1,'C',true);
		}

		if($pagecount>1) 
		{
			for($i=2;$i<=$pagecount;$i++) 
			{
				$tpl = $pdf->importPage($i);
				$pdf->AddPage();
				$pdf->useTemplate($tpl, ['adjustPageSize' => true]);
			}	
			
		}

		$pdf->Output($outputpdfpath, "F");
		return 1;
	}
	catch(Exception $e) 
	{
	  return 0;
	}
}
?>