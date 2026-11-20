<?php
include "../combinepdf.php";
$coutput = combine_pdfs(array("pdf1.pdf","pdf2.pdf","pdf3.pdf","pdf4.pdf"),"../../RequestReport/createandcombinedpdf/impreststatement/AS-DB/chatursingh");
if($coutput)
{
	echo $coutput." PDFs Combined Successfully";
}
else
{
	echo "error";
}

?>

