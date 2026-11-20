<?php
include 'image_compression.php';
// Include the main TCPDF library (search for installation path).
require_once('tcpdf/tcpdf.php');
function convert_image_to_pdf($imagepath,$outputpdfpath)
{
	try
	{
		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// add a page
		$pdf->AddPage();

		// set JPEG quality
		$pdf->setJPEGQuality(75);

		$cimage = compressImage($imagepath, 50);
		
		if($cimage)
		{
			$x = PDF_MARGIN_LEFT;
			$y = PDF_MARGIN_TOP;
			$w = $pdf->GetPageWidth()-PDF_MARGIN_LEFT-PDF_MARGIN_RIGHT;
			$h = $pdf->GetPageHeight()-PDF_MARGIN_TOP-PDF_MARGIN_BOTTOM;
			$fitbox = 'C';

			$fitbox[1] = 'T';
			$pdf->Rect($x, $y, $w, $h, 'F', array(), array(255,255,255));
			$pdf->Image($cimage, $x, $y, $w, $h, '', '', '', false, 75, '', false, false, 0, $fitbox, false, false);

			//Close and output PDF document
			$pdf->Output($outputpdfpath, 'f');
			return true;
		}
		else
		{
			return false;
		}
	}
	catch(Exception $e) 
	{
  		return false;
	}
}

function convert_imagestring_to_pdf($base64image,$outputpdfpath)
{
	try
	{
		// create new PDF document
		$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

		// set default monospaced font
		$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

		// set margins
		$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
		$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
		$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
		$pdf->setPrintHeader(false);
		$pdf->setPrintFooter(false);
		// set auto page breaks
		$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

		// set image scale factor
		$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

		// add a page
		$pdf->AddPage();

		// set JPEG quality
		$pdf->setJPEGQuality(75);

		$cimage = compressImageString($base64image, 50);
		

		if($cimage)
		{
			$imgdata = base64_decode($cimage);
			$im = imagecreatefromstring($imgdata);
			$temppath = "/home/india".tempnam(sys_get_temp_dir(), 'IMG').".jpg";
			imagejpeg($im, $temppath,30);
			$x = PDF_MARGIN_LEFT;
			$y = PDF_MARGIN_TOP;
			$w = $pdf->GetPageWidth()-PDF_MARGIN_LEFT-PDF_MARGIN_RIGHT;
			$h = $pdf->GetPageHeight()-PDF_MARGIN_TOP-PDF_MARGIN_BOTTOM;
			$fitbox = 'C';

			$fitbox[1] = 'T';
			$pdf->Rect($x, $y, $w, $h, 'F', array(), array(255,255,255));
			$pdf->Image($temppath, $x, $y, $w, $h, '', '', '', false, 75, '', false, false, 0, $fitbox, false, false);
			unlink($temppath);
			//Close and output PDF document
			$pdf->Output($outputpdfpath, 'f');
			return true;
		}
		else
		{
			return false;
		}
	}
	catch(Exception $e) 
	{
  		return false;
	}
}
?>