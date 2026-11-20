<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
$abc=`gs -sPAPERSIZE=a4 -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -dEmbedAllFonts=true -dCompatibilityLevel="1.4" -dNOPAUSE -dBATCH -dSAFER -sOutputFile=/home/india/public_html/pdfroutines/examples/b.pdf /home/india/public_html/pdfroutines/examples/a.pdf`;
echo $abc;
?>