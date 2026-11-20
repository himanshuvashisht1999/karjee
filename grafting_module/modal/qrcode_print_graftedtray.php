<?php
    require('../../connection.php');
    $stockorder_no = $_POST['stockorder'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    $productid = $_POST['productid'] ?? '';
    $projectid = (int)$_POST['projectid'] ?? 0;
    $laborid = (int)$_POST['laborid'] ?? 0;
    if ($locationid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Location is required.'
        ]);
        exit;
    }
    if ($stockorder_no === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Order No is required.'
        ]);
        exit;
    }
    if ($projectid <= 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid project.'
        ]);
        exit;
    }
    $sql = " SELECT  barcode,  COUNT(*) OVER () AS cnt FROM krf_polyhouse_plant_details
        WHERE projectid = ?
          AND locationid = ?
          AND status = 'Grafted'
          AND stockorder_no = ?
          AND productid = ?  ";
    $params = [
        $projectid,
        $locationid,
        $stockorder_no,
        $productid
    ];
    $types = "isss";
    if (!empty($laborid)) {
        $sql .= " AND labourid = ? ";
        $params[] = $laborid;
        $types .= "s";
    }
    $sql .= " GROUP BY barcode ORDER BY barcode";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param(
        $types,
        ...$params
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $labels = []; 
    while ($row = $result->fetch_assoc()) {
           $row = [
            'qr' => $row['barcode'],
            'title' => $stockorder_no,
            'name' => "Grafted",
            'qty' =>$row['cnt'],
            'pod' => "N/A",
        ];
        array_push($labels, $row);
    }
    $stmt->close();      
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

 