<?php
$baseDir = "labourpayment/pdfroutines/tcpdf/include/barcodes/";
if (!is_dir($baseDir)) {
    mkdir($baseDir, 0777, true);
}

$files = [
    "qrcode.php" => "https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/barcodes/qrcode.php",
    "pdf417.php" => "https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/barcodes/pdf417.php",
    "datamatrix.php" => "https://raw.githubusercontent.com/tecnickcom/TCPDF/main/include/barcodes/datamatrix.php"
];

foreach ($files as $name => $url) {
    $dest = $baseDir . $name;
    echo "Downloading $url to $dest...\n";
    $content = @file_get_contents($url);
    if ($content === false) {
        echo "Failed to download $name!\n";
    } else {
        $bytes = file_put_contents($dest, $content);
        echo "Success! Wrote $bytes bytes.\n";
    }
}
?>
