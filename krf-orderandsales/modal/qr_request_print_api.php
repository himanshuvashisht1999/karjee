<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (session_status() === PHP_SESSION_NONE) session_start();
require('../../connection.php');

function qrRespond(array $payload, int $status = 200): void {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($payload, JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function qrDate(string $value, bool $optional = false): bool {
    if ($optional && $value === '') return true;
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) return false;
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    return $dt !== false && $dt->format('Y-m-d') === $value;
}
function qrRows(mysqli $db, string $sql, string $types, array $params): array {
    $stmt = $db->prepare($sql);
    if (!$stmt) throw new RuntimeException('Unable to prepare query.');
    try {
        if ($types !== '' && !$stmt->bind_param($types, ...$params)) throw new RuntimeException('Unable to bind query.');
        if (!$stmt->execute()) throw new RuntimeException('Unable to execute query.');
        $result = $stmt->get_result();
        if (!$result) throw new RuntimeException('Unable to read query result.');
        return $result->fetch_all(MYSQLI_ASSOC);
    } finally { $stmt->close(); }
}
function qrIdentity(): array {
    $values = [];
    foreach (['locationid','stockorder_no','sowinglotno_internal','sowingdate_internal','requested_date'] as $key) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
        if ($values[$key] === '') throw new InvalidArgumentException('Missing request identity: ' . $key);
    }
    if (!qrDate($values['sowingdate_internal']) || !qrDate($values['requested_date'])) {
        throw new InvalidArgumentException('Invalid request or sowing date.');
    }
    return $values;
}
function qrDetailRows(mysqli $db, string $project, array $id): array
{
      $sql = "
        SELECT
            d.lotno,
            sd.seedname,
            d.barcode,
            d.productname,
            d.polyhouseid,
            d.bedrackname,
            d.bed_no,
            d.qtypertray,
            d.qrcode_request,
            d.qrcode_requestedby,
            d.qr_printdate,
            d.qr_printby,
            p.polyhousename,
            d.dispatchdate_internal,
            (
                SELECT COUNT(d2.lotno)
                FROM krf_polyhouse_plant_details d2
                WHERE d2.projectid = d.projectid
                  AND d2.locationid = d.locationid
                  AND d2.stockorder_no = d.stockorder_no
                  AND d2.sowinglotno_internal = d.sowinglotno_internal
                  AND d2.sowingdate_internal = d.sowingdate_internal
                   
                  AND d2.barcode IS NOT NULL
                  AND TRIM(d2.barcode) <> ''
            ) AS countlotno
        FROM krf_polyhouse_plant_details d
        LEFT JOIN krf_polyhouse_master p
            ON p.polyhouseid = d.polyhouseid
           AND p.locationid = d.locationid
        LEFT JOIN product_master pr
            ON pr.productid = d.productid
           AND pr.projectid = d.projectid
        LEFT JOIN seed_master sd
            ON sd.seedid = pr.seedid
           AND sd.projectid = pr.projectid
        WHERE d.projectid = ?
          AND d.locationid = ?
          AND d.stockorder_no = ?
          AND d.sowinglotno_internal = ?
          AND d.sowingdate_internal = ?
          AND d.qrcode_request >= ?
          AND d.qrcode_request < DATE_ADD(?, INTERVAL 1 DAY)
          AND d.barcode IS NOT NULL
          AND TRIM(d.barcode) <> ''
        ORDER BY d.qrcode_request ASC, d.barcode ASC
    ";

    return qrRows($db, $sql, 'sssssss', [ 
        $project,
        $id['locationid'],
        $id['stockorder_no'],
        $id['sowinglotno_internal'],
        $id['sowingdate_internal'],
        $id['requested_date'],
        $id['requested_date']
    ]);
}
function qrSafe($value, int $limit): string {
    $value = preg_replace('/[\x00-\x1F\x7F"\\\\]/', ' ', (string)$value);
    return substr($value, 0, $limit);
}
/** Existing two-column thermal printer layout; one PRINT for each pair. */
function generate_tspl(array $labels): string {
    $tspl = ['SIZE 78.6 mm, 12 mm', 'GAP 3 mm, 0', 'DENSITY 10', 'SPEED 2',
             'DIRECTION 1', 'SET TEAR ON', 'REFERENCE 0, 10'];
    $count = count($labels);
    foreach ($labels as $i => $label) {
        $col = $i % 2;
        if ($col === 0) $tspl[] = 'CLS';
        $baseX = $col * 304;
        $title = qrSafe($label['title'], 20);
        $name  = qrSafe($label['name'], 22);
        $qty   = qrSafe('QTY:' . $label['qty'], 22);
        $pod   = qrSafe('POD:' . $label['pod'], 22);
        $qr    = qrSafe($label['qr'], 120);
        $tspl[] = 'QRCODE ' . ($baseX + 30) . ',20,L,3,A,0,M2,"' . $qr . '"';
        $textX = $baseX + 112;
        $tspl[] = 'TEXT ' . $textX . ',20,"1",0,1,1,"' . $title . '"';
        $tspl[] = 'TEXT ' . $textX . ',38,"1",0,1,1,"' . $name . '"';
        $tspl[] = 'TEXT ' . $textX . ',56,"1",0,1,1,"' . $qty . '"';
        $tspl[] = 'TEXT ' . $textX . ',74,"1",0,1,1,"' . $pod . '"';
        if ($col === 1 || $i === $count - 1) $tspl[] = 'PRINT 1,1';
    }
    return implode("\n", $tspl) . "\n";
}
try {
    if (!isset($krfconn) || !($krfconn instanceof mysqli)) throw new RuntimeException('Database unavailable.');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new InvalidArgumentException('POST is required.');
    $project = trim((string)($_POST['projectid'] ?? ''));
    if ($project === '') throw new InvalidArgumentException('Project is required.');
    // Integrate project authorization with your authenticated header/session middleware.
    $action = (string)($_POST['action'] ?? '');

    // Location choices must originate in actual requested trays, not the entire HRM master.
    if ($action === 'locations') {
        $requested = qrRows($krfconn,
            "SELECT DISTINCT d.locationid AS locationid
             FROM krf_polyhouse_plant_details d
             WHERE d.projectid=? AND d.qrcode_request IS NOT NULL
               AND d.locationid IS NOT NULL AND TRIM(d.locationid)<>''
             ORDER BY d.locationid", 's', [$project]);
        if (!isset($hrmconn) || !($hrmconn instanceof mysqli)) {
            throw new RuntimeException('HRM connection unavailable.');
        }
        $names = qrRows($hrmconn,
            "SELECT LocationId AS locationid, LocationName AS location_name
             FROM hrm_AttendanceLocationMaster WHERE ProjectId=?", 's', [$project]);
        $map=[];
        foreach ($names as $item) $map[(string)$item['locationid']] = $item['location_name'];
        $result=[];
        foreach ($requested as $item) {
            $id=(string)$item['locationid'];
            $result[]=['locationid'=>$id,'location_name'=>$map[$id] ?? $id];
        }
        usort($result, static fn($a,$b)=>strnatcasecmp($a['location_name'],$b['location_name']));
        qrRespond(['status'=>true,'data'=>$result]);
    }
    if ($action === 'filter_options') {
        // Cascading filters: all options are limited to requested trays.
        $loc=trim((string)($_POST['locationid']??''));
        $client=trim((string)($_POST['client']??''));
        $stock=trim((string)($_POST['stock']??''));
        $from=trim((string)($_POST['date_from']??''));
        $to=trim((string)($_POST['date_to']??''));
        if (!qrDate($from,true)||!qrDate($to,true)||($from!==''&&$to!==''&&$from>$to))
            throw new InvalidArgumentException('Invalid sowing date range.');
        $base="d.projectid=? AND d.qrcode_request IS NOT NULL";
        $types='s'; $args=[$project];
        if($loc!=='') {$base.=' AND d.locationid=?';$types.='s';$args[]=$loc;}
        if($from!=='') {$base.=' AND d.sowingdate_internal>=?';$types.='s';$args[]=$from;}
        if($to!=='') {$base.=' AND d.sowingdate_internal<=?';$types.='s';$args[]=$to;}
        $clientWhere=$base;
        $clientTypes=$types; $clientArgs=$args;
        if($stock!=='') {$clientWhere.=' AND d.stockorder_no=?';$clientTypes.='s';$clientArgs[]=$stock;}
        $clients=qrRows($krfconn,
            "SELECT DISTINCT cl.client_name AS value
             FROM krf_polyhouse_plant_details d
             JOIN krf_production_stockorder s ON s.projectid=d.projectid AND s.stockorder_no=d.stockorder_no
             JOIN krf_client_master cl ON cl.projectid=s.projectid AND cl.client_id=s.clientid
             WHERE $clientWhere AND cl.client_name IS NOT NULL AND TRIM(cl.client_name)<>''
             ORDER BY cl.client_name",$clientTypes,$clientArgs);
        $stockWhere=$base; $stockTypes=$types; $stockArgs=$args;
        if($client!=='') {
            $stockWhere.=" AND EXISTS (SELECT 1 FROM krf_production_stockorder cs
                JOIN krf_client_master cc ON cc.projectid=cs.projectid AND cc.client_id=cs.clientid
                WHERE cs.projectid=d.projectid AND cs.stockorder_no=d.stockorder_no AND cc.client_name=?)";
            $stockTypes.='s';$stockArgs[]=$client;
        }
        $stocks=qrRows($krfconn,
            "SELECT DISTINCT d.stockorder_no AS value FROM krf_polyhouse_plant_details d
             WHERE $stockWhere AND d.stockorder_no IS NOT NULL AND TRIM(d.stockorder_no)<>''
             ORDER BY d.stockorder_no",$stockTypes,$stockArgs);
        qrRespond(['status'=>true,'data'=>['clients'=>$clients,'stocks'=>$stocks]]);
    }
    if ($action === 'summary') {
        $loc = trim((string)($_POST['locationid'] ?? ''));
        $client = trim((string)($_POST['client'] ?? ''));
        $stock = trim((string)($_POST['stock'] ?? ''));
        $from = trim((string)($_POST['date_from'] ?? ''));
        $to = trim((string)($_POST['date_to'] ?? ''));
        if (!qrDate($from, true) || !qrDate($to, true) || ($from !== '' && $to !== '' && $from > $to)) {
            throw new InvalidArgumentException('Invalid sowing date range.');
        }
        $where = 'd.projectid = ? AND d.qrcode_request IS NOT NULL';
        $types = 's'; $params = [$project];
        if ($loc !== '') { $where .= ' AND d.locationid = ?'; $types .= 's'; $params[] = $loc; }
        if ($stock !== '') { $where .= ' AND d.stockorder_no = ?'; $types .= 's'; $params[] = $stock; }
        if ($from !== '') { $where .= ' AND d.sowingdate_internal >= ?'; $types .= 's'; $params[] = $from; }
        if ($to !== '') { $where .= ' AND d.sowingdate_internal <= ?'; $types .= 's'; $params[] = $to; }
        if ($client !== '') {
            $where .= " AND EXISTS (
                SELECT 1 FROM krf_production_stockorder cs
                JOIN krf_client_master cc ON cc.projectid = cs.projectid AND cc.client_id = cs.clientid
                WHERE cs.projectid = d.projectid AND cs.stockorder_no = d.stockorder_no
                  AND cc.client_name = ?
            )";
            $types .= 's'; $params[] = $client;
        }
           $sql = "SELECT x.*,
                       DATE_FORMAT(x.sowingdate_internal, '%d-%m-%Y') AS sowing_display,
                       DATE_FORMAT(x.requested_date, '%d-%m-%Y') AS requested_display,
                       (SELECT cc.client_name FROM krf_production_stockorder cs
                         JOIN krf_client_master cc ON cc.projectid=cs.projectid AND cc.client_id=cs.clientid
                         WHERE cs.projectid=x.projectid AND cs.stockorder_no=x.stockorder_no
                         LIMIT 1) AS client_name
                FROM (
                    SELECT d.projectid, d.locationid, d.stockorder_no, d.sowinglotno_internal,
                           d.sowingdate_internal, DATE(d.qrcode_request) AS requested_date,
                           COUNT(*) AS requested_qty,
                           SUM(CASE WHEN d.qr_printdate IS NOT NULL THEN 1 ELSE 0 END) AS printed_qty
                    FROM krf_polyhouse_plant_details d
                    WHERE $where
                    GROUP BY d.projectid, d.locationid, d.stockorder_no,
                             d.sowinglotno_internal, d.sowingdate_internal, DATE(d.qrcode_request)
                ) x
                ORDER BY x.requested_date DESC, x.sowingdate_internal DESC, x.stockorder_no ASC";

                
        $rows = qrRows($krfconn, $sql, $types, $params);
        if (!isset($hrmconn) || !($hrmconn instanceof mysqli)) {
            throw new RuntimeException('HRM connection unavailable.');
        }
        $locRows = qrRows($hrmconn,
            "SELECT LocationId AS locationid, LocationName AS location_name
             FROM hrm_AttendanceLocationMaster WHERE ProjectId=?", 's', [$project]);
        $locationMap = [];
        foreach ($locRows as $lr) $locationMap[(string)$lr['locationid']] = $lr['location_name'];
        foreach ($rows as &$r) {
            $r['location_name'] = $locationMap[(string)$r['locationid']] ?? (string)$r['locationid'];
        }
        unset($r);
        qrRespond(['status'=>true, 'data'=>$rows]);
    }

    if (!in_array($action, ['details', 'print_tspl', 'mark_printed'], true)) {
        throw new InvalidArgumentException('Invalid action.');
    }
    $id = qrIdentity();
    $rows = qrDetailRows($krfconn, $project, $id);
    if ($action === 'details') {
         
        qrRespond(['status'=>true, 'data'=>$rows]);
    }

    $selected = json_decode((string)($_POST['barcodes'] ?? '[]'), true);
    if (!is_array($selected) || count($selected) < 1 || count($selected) > 500 || array_values($selected) !== $selected) {
        throw new InvalidArgumentException('Select 1 to 500 barcodes per batch.');
    }
    $selected = array_map('strval', $selected);
    if (in_array('', $selected, true) || count($selected) !== count(array_unique($selected))) {
        throw new InvalidArgumentException('Empty or duplicate barcode in selection.');
    }
    $byCode = [];
    foreach ($rows as $row) {
        $barcode = (string)$row['barcode'];
        if (isset($byCode[$barcode])) throw new RuntimeException('Duplicate barcode in this request: ' . $barcode);
        $byCode[$barcode] = $row;
    }
    $chosen = [];
    foreach ($selected as $barcode) {
        if (!isset($byCode[$barcode])) throw new InvalidArgumentException('Barcode does not belong to this request: ' . $barcode);
        $chosen[] = $byCode[$barcode];
    }

    if ($action === 'print_tspl') {
        $labels = [];
        foreach ($chosen as $row) {
            $labels[] = [
                'qr' => $row['barcode'], 'title' => $id['stockorder_no'],
                'name' => $row['productname'], 'qty' => $row['qtypertray'],
                'pod' => date('d-m', strtotime($id['sowingdate_internal']))
            ];
        }
        $tspl = generate_tspl($labels);
        while (ob_get_level() > 0) ob_end_clean();
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        echo $tspl;
        exit;
    }

    // Mark only after operator confirms successful physical printing.
    // IMPORTANT: replace fallback with your application's authenticated session variable.
    $printedBy = trim((string)($_SESSION['loginname'] ?? $_SESSION['username'] ?? ''));
    if ($printedBy === '') throw new RuntimeException('Authenticated printer user is missing.');
    if (mb_strlen($printedBy) > 200) throw new InvalidArgumentException('Printer name is too long.');

    $krfconn->begin_transaction();
    try {
        $sql = "UPDATE krf_polyhouse_plant_details
                SET qr_printdate = NOW(), qr_printby = ?
                WHERE projectid = ? AND locationid = ? AND stockorder_no = ?
                  AND sowinglotno_internal = ? AND sowingdate_internal = ? AND barcode = ?
                  AND qrcode_request >= ? AND qrcode_request < DATE_ADD(?, INTERVAL 1 DAY)";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) throw new RuntimeException('Unable to prepare print update.');
        $updated = 0;
        try {
            foreach ($selected as $barcode) {
                $stmt->bind_param('sssssssss', $printedBy, $project, $id['locationid'],
                    $id['stockorder_no'], $id['sowinglotno_internal'], $id['sowingdate_internal'],
                    $barcode, $id['requested_date'], $id['requested_date']);
                if (!$stmt->execute()) throw new RuntimeException('Print update failed.');
                if ($stmt->affected_rows !== 1) throw new RuntimeException('Barcode no longer uniquely matches the request: ' . $barcode);
                $updated++;
            }
        } finally { $stmt->close(); }
        $krfconn->commit();
        qrRespond(['status'=>true, 'message'=>$updated.' barcode(s) marked printed.',
                   'barcodes'=>$selected, 'printdate'=>date('Y-m-d H:i:s'), 'printedby'=>$printedBy]);
    } catch (Throwable $e) {
        $krfconn->rollback();
        throw $e;
    }
} catch (Throwable $e) {
    error_log('QR print API: ' . $e->getMessage());
    $code = $e instanceof InvalidArgumentException ? 400 : 500;
    qrRespond(['status'=>false, 'message'=>$e->getMessage(), 'data'=>[]], $code);
}
