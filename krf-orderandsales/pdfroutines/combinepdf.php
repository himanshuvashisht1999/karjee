<?php
//error_reporting(E_ALL &~ E_STRICT);
use PDFMerger\PDFMerger;
function combine_pdfs($pdfarray,$outputpath)
{
	try
	{
		require_once('tcpdf/PDFMerger.php');
print_r($pdfarray);
		$pdf = new PDFMerger;
		foreach ($pdfarray as $pdf_url) 
		{
			$pdf->addPDF($pdf_url, 'all');
		}
		$outputfile = $outputpath."/"."combined".date('mdYHisu').".pdf";
		$pdf->merge('file', $outputfile);
		// require "attachpagenostopdf.php";
		// $abc=setpagenumbers($outputfile);
		// if($abc==1){
		// 	return $outputfile;
		// }
		return $outputfile;
	}
	catch(Exception $e) 
	{
  		return $e;
	}
}
?>