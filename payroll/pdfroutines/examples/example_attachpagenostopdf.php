<?php
include "../attachpagenostopdf.php";
echo setpagenumbers("/home/india/public_html/asktaccountsreport/createandcombinedpdf/ppwinvoices/JK/2020-04-01_2020-06-30/12501092021103336000000x.pdf");
/* gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -o /home/india/public_html/pdfroutines/examples/b.pdf /home/india/public_html/pdfroutines/examples/a.pdf 
 */
/* gs -sPAPERSIZE=a4 -sDEVICE=pdfwrite -dPDFSETTINGS=/prepress -dEmbedAllFonts=true -dCompatibilityLevel="1.4" -dNOPAUSE -dBATCH -dSAFER -sOutputFile=/home/india/public_html/pdfroutines/examples/b.pdf /home/india/public_html/pdfroutines/examples/a.pdf */
?>
 