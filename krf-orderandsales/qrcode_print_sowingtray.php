<?php
require('../connection.php');

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

$productionorderno = $_POST['productionorderno'] ?? '';
$seedname = $_POST['seedname'] ?? '';
$lotno = $_POST['lotno'] ?? '';
$traycountinlot = $_POST['traycountinlot'] ?? '';
$sowindateinternal = $_POST['sowindateinternal'] ?? '';
$dispatchdateinternal = $_POST['dispatchdateinternal'] ?? '';
$traycavity = $_POST['traycavity'] ?? '';
$sowingtraycount = $_POST['sowingtraycount'] ?? '';
$swlb = "";
$delvlb = "";
$datelb = "";
$lotnolb = $lotno;
$seedname = formatGraftedName($seedname) . "(" . $traycountinlot . "X" . $traycavity . ")";

$swlb = date('d-M', strtotime($sowindateinternal));
$delvlb = date('d-M', strtotime($dispatchdateinternal));
if($swlb===$delvlb){
    $datelb = $swlb;
}else{
    $datelb = $swlb . '/' . $delvlb;
}
$grcodelist = json_decode($_POST['grcodelist'], true);

// print_r($grcodelist);
// die();
$labels = [];
foreach ($grcodelist as $key) {
    // $row = [
    //     'qr' => $key['qrcode'],
    //     'title' => $productionorderno."(".$key['qrcode'].")",
    //     'name' => $seedname,
    //     'qty' => $lotnolb,
    //     'pod' => $datelb,
    // ];
    $row = [
        'qr'    => substr($key['qrcode'], 0, 19),
        'title' => substr($productionorderno, 0, 8)."(".substr($key['qrcode'], -8).")",
        'name'  => substr($seedname, 0, 19),
        'qty'   => substr($lotnolb, 0, 19),
        'pod'   => substr($datelb, 0, 19),
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

        $titlelb = substr($label['title'], 0, 24);
        $namelb = substr($label['name'], 0, 22);
        $qtylb = "Lt." . $label['qty'];
        $podlb = "Dt." . $label['pod'];

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

function getModifiedDate($date, $days = 0, $type = 'add') {
    // $type add/subtract
    // Validate date
    if (!$date || !strtotime($date)) {
        return false;
    }

    // Create DateTime object
    $dt = new DateTime($date);

    // Modify date based on type
    if ($type === 'add') {
        $dt->modify("+$days days");
    } elseif ($type === 'subtract') {
        $dt->modify("-$days days");
    } else {
        return false;
    }

    // Return formatted date (Y-m-d)
    return $dt->format('Y-m-d');
}