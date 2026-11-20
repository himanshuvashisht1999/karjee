<?php
ini_set('max_execution_time', 1500); 
ini_set('memory_limit','512M');
$base_unit = 4096;
$handle = @fopen("heavypdf.pdf", "r");
$data="";
if ($handle) {
    while (($buffer = fread($handle, $base_unit*3)) !== false) {
        $data .= $buffer; //chunk_split(base64_encode($buffer));
    }
    fclose($handle);
echo $data;
}
?>