<?php
/**
 * Abbreviates Grafted -> GR and NonGrafted -> NGR (Case-Insensitive)
 */
function formatGraftedName($name) {
    if (empty($name)) return '';
    // 1. Check NonGrafted / Non-Grafted / Nongrafted first
    if (preg_match('/non[\s_-]?grafted/i', $name)) {
        return preg_replace('/non[\s_-]?grafted/i', 'NGR', $name);
    }
    // 2. Check Grafted next
    elseif (preg_match('/grafted/i', $name)) {
        return preg_replace('/grafted/i', 'GR', $name);
    }
    // 3. Keep rest as-is
    return $name;
}

$stockordernolb = $_POST['productionorderno'] ?? '';
$productnamelb = formatGraftedName($_POST['seedname'] ?? '');
$traycountinaboxlb = $_POST['traycountinaboxlb'];
$totalboxcount = $_POST['totalboxcount'];
$qty=$totalboxcount.'X'.$traycountinaboxlb;
$pckdlb = "";
$opdlb = "";
$datelb = "";
if($_POST['packingdate'] && $_POST['openingdate']){
    $pckdlb = date('d-m', strtotime($_POST['packingdate']));
    $opdlb = date('d-m', strtotime($_POST['openingdate']));
    $datelb =$pckdlb.'/'.$opdlb; 
}else{
    echo "error";
}
$grcodelist = json_decode($_POST['grcodelist'], true);
$labels = [];
foreach ($grcodelist as $key => $value) {
    $row = [
        'qr' => $value['barcode'],
        'title' => $stockordernolb,
        'name' => $productnamelb,
        'qty' => $qty,
        'pod' => $datelb,
    ];
    array_push($labels, $row);
}
$tspl = generate_tspl($labels);
echo $tspl;


// =========================
// ?? TSPL GENERATOR
// =========================
function generate_tspl($labels)
{
    $tspl = [];

    // Total width = 1.3mm (L) + 38mm (Label 1) + 38mm (Label 2) + 1.3mm (R) = 78.6mm
    // Height = 12mm
    $tspl[] = "SIZE 78.6 mm, 12 mm";
    $tspl[] = "GAP 3 mm, 0";
    $tspl[] = "DENSITY 10"; 
    $tspl[] = "SPEED 2";
    $tspl[] = "DIRECTION 1";
    $tspl[] = "SET TEAR ON";
    $tspl[] = "REFERENCE 0, 10";

    $cols = 2;
    $x_step = 304;

    foreach ($labels as $i => $label) {
        $col = $i % $cols;

        if ($col == 0) {
            $tspl[] = "CLS";
        }

        $baseX = ($col * $x_step);

        $titlelb = substr($label['title'], 0, 20);
        $namelb = substr($label['name'], 0, 22);
        $qtylb = "QTY:" . $label['qty'];
        $podlb = "POD:" . $label['pod'];

        $qrContent = $label['qr'];

        // Centered vertically with a safe top margin of 20 dots (2.5mm) and a safe left margin of 30 dots (3.75mm)
        $tspl[] = "QRCODE " . ($baseX + 30) . ",20,L,3,A,0,M2,\"$qrContent\"";

        // Positioned text with a 112-dot offset (leaving a beautiful 19-dot whitespace gap after the QR code ends at X=93).
        // Vertically spaced lines uniformly (20, 38, 56, 74).
        $textX = $baseX + 112;
        $tspl[] = "TEXT " . $textX . ",20,\"1\",0,1,1,\"$titlelb\"";
        $tspl[] = "TEXT " . $textX . ",38,\"1\",0,1,1,\"$namelb\"";
        $tspl[] = "TEXT " . $textX . ",56,\"1\",0,1,1,\"$qtylb\"";
        $tspl[] = "TEXT " . $textX . ",74,\"1\",0,1,1,\"$podlb\"";

        if ($col == 1 || $i == count($labels) - 1) {
            $tspl[] = "PRINT 1,1";
        }
    }
    return implode("\n", $tspl) . "\n";
}
