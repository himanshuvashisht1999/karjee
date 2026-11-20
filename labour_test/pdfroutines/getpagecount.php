<?php
use \setasign\Fpdi\Fpdi;
function gettotalpages($inputpdfpath)
{
	require_once('fpdf/fpdf.php');
	require_once('fpdi/autoload.php');
	try 
	{
		$pdf = new FPDI();
		$pagecount = $pdf->setSourceFile($inputpdfpath);
		return $pagecount;
	}
	catch(Exception $e) 
	{
	  return $e;
	}
}
?>