<?php
function convert_base64_to_pdf($basestring,$outputpath,$filename)
{
	$b64 = $basestring;
	$bin = base64_decode($b64, true);

	if (strpos($bin, '%PDF') !== 0) 
	{
  		return false;
	}

	$pdffile = $outputpath."/".$filename.".pdf";
	
	file_put_contents($pdffile, $bin);
	
	if(file_exists($pdffile))
	{
		return $pdffile;
	}
	else
	{
		return false;
	}
}
?>