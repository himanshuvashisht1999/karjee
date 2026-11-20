<?php
header('Content-Type: application/json; charset=utf-8');
error_reporting(E_ALL);
require('../../../connection.php');
require('../../../loginCell/functions/login_functions.php');
include('../../functions/orders_functions.php');

if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset('utf8mb4');
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function arr($key) {
    $v = $_POST[$key] ?? [];
    return is_array($v) ? $v : [];
}

function code2($value) {
    $s = trim((string)$value);
    if ($s !== '' && ctype_digit($s)) {
        return str_pad($s, 2, '0', STR_PAD_LEFT);
    }
    return $s;
}

$orderid   = trim($_POST['orderid'] ?? '');
$clientid  = trim($_POST['clientid'] ?? '');
$projectid = (int)($_POST['projectid'] ?? 0);
$loginid   = trim($_POST['loginid'] ?? ($_SESSION['loginid'] ?? ''));

if ($orderid === '' || $projectid <= 0) {
    respond('error', 'Invalid order details.');
}

$rowids      = arr('delivery_rowid');
$productids  = arr('delivery_productid');
$qtys        = arr('delivery_qty');
$distances   = arr('delivery_distance');
$latlongs    = arr('delivery_latlong');
$stateids    = arr('delivery_stateid');
$districtids = arr('delivery_districtid');
$locationids = arr('delivery_cityid'); // UI name retained; saved into locationid
$pincodes    = arr('delivery_pincode');
$addresses   = arr('delivery_fulladdress');

$deleted = array_values(array_filter(array_map(
    'intval',
    preg_split('/\s*,\s*/', trim($_POST['deleted_rowids'] ?? ''), -1, PREG_SPLIT_NO_EMPTY)
)));

$fAmounts   = arr('f_amount');
$fDiscounts = arr('f_discount');
$fPayables  = arr('f_payable');
$fRemarks   = trim($_POST['freight_remarks'] ?? '');

if (count($productids) === 0) {
    respond('error', 'Please add at least one delivery address row.');
}

$krfconn->begin_transaction();

try {
    /* Lock header and get authoritative client */
    $st = $krfconn->prepare("SELECT clientid FROM krf_provisional_sales_order WHERE projectid=? AND psono=? LIMIT 1 FOR UPDATE");
    $st->bind_param('is', $projectid, $orderid);
    $st->execute();
    $order = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$order) {
        throw new Exception('Provisional order not found.');
    }
    if (!empty($order['clientid'])) {
        $clientid = (string)$order['clientid'];
    }

    /* Saved Step 2 quantities are authoritative */
    $ordered = [];
    $st = $krfconn->prepare("SELECT product_id, SUM(quantity) AS qty FROM krf_provisional_sales_order_items WHERE projectid=? AND psono=? GROUP BY product_id");
    $st->bind_param('is', $projectid, $orderid);
    $st->execute();
    $rs = $st->get_result();
    while ($r = $rs->fetch_assoc()) {
        $ordered[(string)$r['product_id']] = (float)$r['qty'];
    }
    $st->close();

    if (!$ordered) {
        throw new Exception('No saved order products found.');
    }

    /* Validate each row against location master and product qty */
    $allocated = [];
    $canonical = [];

    $masterStmt = $krfconn->prepare("\n        SELECT id, stateid, statename, districtid, districtname, location\n        FROM krf_boxpacking_freight_master\n        WHERE projectid=? AND id=? AND stateid=? AND districtid=?\n        LIMIT 1\n    ");

    foreach ($productids as $i => $pidRaw) {
        $pid = trim((string)$pidRaw);
        $qty = (float)($qtys[$i] ?? 0);
        $stateCode = code2($stateids[$i] ?? '');
        $districtCode = code2($districtids[$i] ?? '');
        $locationId = (int)($locationids[$i] ?? 0);
        $address = trim((string)($addresses[$i] ?? ''));

        if ($pid === '' || !isset($ordered[$pid])) {
            throw new Exception('Invalid product in delivery row ' . ($i + 1) . '.');
        }
        if ($qty <= 0) {
            throw new Exception('Delivery quantity must be greater than zero in row ' . ($i + 1) . '.');
        }
        if ($stateCode === '' || $districtCode === '' || $locationId <= 0 || $address === '') {
            throw new Exception('State, District, City/Location and Full Address are required in row ' . ($i + 1) . '.');
        }

        $masterStmt->bind_param('iiss', $projectid, $locationId, $stateCode, $districtCode);
        $masterStmt->execute();
        $master = $masterStmt->get_result()->fetch_assoc();

        if (!$master) {
            throw new Exception('Invalid State / District / Location combination in row ' . ($i + 1) . '.');
        }

        $canonical[$i] = $master;
        $allocated[$pid] = ($allocated[$pid] ?? 0) + $qty;
    }
    $masterStmt->close();

    foreach ($ordered as $pid => $orderedQty) {
        $got = (float)($allocated[$pid] ?? 0);
        if (abs($got - $orderedQty) > 0.0001) {
            throw new Exception("Product {$pid}: ordered {$orderedQty}, allocated {$got}. Delivery quantities must match the saved order quantity.");
        }
    }

    /* Update / insert product-wise delivery rows */
    foreach ($productids as $i => $pidRaw) {
        $rowId = (int)($rowids[$i] ?? 0);
        $pid = trim((string)$pidRaw);
        $qty = (int)round((float)($qtys[$i] ?? 0));
        $distance = (float)($distances[$i] ?? 0);
        $pincode = trim((string)($pincodes[$i] ?? ''));
        $address = trim((string)($addresses[$i] ?? ''));

        $master = $canonical[$i];
        $stateCode = code2($master['stateid']);
        $districtCode = code2($master['districtid']);
        $stateIdDb = (int)$stateCode;       // current table columns are INT
        $districtIdDb = (int)$districtCode; // current table columns are INT
        $stateName = trim((string)$master['statename']);
        $districtName = trim((string)$master['districtname']);
        $locationId = (int)$master['id'];
        $cityName = trim((string)$master['location']);

        $lat = null;
        $lng = null;
        $latlong = trim((string)($latlongs[$i] ?? ''));
        if ($latlong !== '' && strpos($latlong, ',') !== false) {
            [$latRaw, $lngRaw] = array_map('trim', explode(',', $latlong, 2));
            if (is_numeric($latRaw)) $lat = (float)$latRaw;
            if (is_numeric($lngRaw)) $lng = (float)$lngRaw;
        }

        if ($rowId > 0) {
            $own = $krfconn->prepare("SELECT id FROM krf_deliveryaddress_location WHERE id=? AND projectid=? AND (psono=? OR sono=?) AND (tedit IS NULL OR tedit='') LIMIT 1 FOR UPDATE");
            $own->bind_param('iiss', $rowId, $projectid, $orderid, $orderid);
            $own->execute();
            $exists = $own->get_result()->fetch_assoc();
            $own->close();

            if (!$exists) {
                throw new Exception('Invalid delivery row id ' . $rowId . '.');
            }

            $st = $krfconn->prepare("\n                UPDATE krf_deliveryaddress_location SET\n                    clientid=?,\n                    productid=?,\n                    deliveryqty=?,\n                    distance=?,\n                    stateid=?,\n                    dest_state=?,\n                    districtid=?,\n                    dest_district=?,\n                    locationid=?,\n                    dest_city=?,\n                    dest_pincode=?,\n                    deliveryaddress=?,\n                    dest_latitude=?,\n                    dest_longitude=?,\n                    updated_at=CURRENT_TIMESTAMP\n                WHERE id=? AND projectid=?\n            ");
            $st->bind_param(
                'ssidisisisssddii',
                $clientid,
                $pid,
                $qty,
                $distance,
                $stateIdDb,
                $stateName,
                $districtIdDb,
                $districtName,
                $locationId,
                $cityName,
                $pincode,
                $address,
                $lat,
                $lng,
                $rowId,
                $projectid
            );
            $st->execute();
            $st->close();
        } else {
            $transportType = 'Company Delivery';
            $deliveryStatus = 'Pending';
            $status = 'Pending';

            $st = $krfconn->prepare("\n                INSERT INTO krf_deliveryaddress_location (\n                    projectid, psono, clientid, productid, deliveryqty,\n                    deliverycharges, transporttype,\n                    dest_latitude, dest_longitude,\n                    dest_state, dest_district, dest_city, dest_pincode,\n                    deliveryaddress, distance, locationid, status, created_by,\n                    delivery_status, stateid, districtid\n                ) VALUES (?,?,?,?,?,0.00,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)\n            ");
            $st->bind_param(
                'isssiddsssssdsssssii',
                $projectid,
                $orderid,
                $clientid,
                $pid,
                $qty,
                $transportType,
                $lat,
                $lng,
                $stateName,
                $districtName,
                $cityName,
                $pincode,
                $address,
                $distance,
                $locationId,
                $status,
                $loginid,
                $deliveryStatus,
                $stateIdDb,
                $districtIdDb
            );
            $st->execute();
            $st->close();
        }
    }

    /* Soft-delete removed delivery rows */
    foreach ($deleted as $did) {
        if ($did <= 0) continue;
        $st = $krfconn->prepare("\n            UPDATE krf_deliveryaddress_location\n            SET tedit='deleted', updated_at=CURRENT_TIMESTAMP\n            WHERE id=? AND projectid=? AND (psono=? OR sono=?) AND (tedit IS NULL OR tedit='')\n        ");
        $st->bind_param('iiss', $did, $projectid, $orderid, $orderid);
        $st->execute();
        $st->close();
    }

    /* Consolidated Step 4 freight snapshot */
    $totalAmount = 0.00;
    $totalPayable = 0.00;

    foreach ($fAmounts as $i => $amtRaw) {
        $amt = max(0, (float)$amtRaw);
        $disc = (float)($fDiscounts[$i] ?? 0);
        if ($disc < 0 || $disc > 100) {
            throw new Exception('Freight discount must be between 0 and 100.');
        }
        $pay = (float)($fPayables[$i] ?? max(0, $amt - ($amt * $disc / 100)));
        $totalAmount += $amt;
        $totalPayable += $pay;
    }

    $discountAmount = max(0, $totalAmount - $totalPayable);
    $discountPercent = $totalAmount > 0 ? round(($discountAmount / $totalAmount) * 100, 2) : 0.00;
    $zerocharge = ($totalAmount <= 0 && $totalPayable <= 0) ? 'yes' : 'no';

    $st = $krfconn->prepare("\n        SELECT id, freight_invoice_no, freightinvoicepath\n        FROM krf_freightcharges\n        WHERE projectid=? AND (psono=? OR (sono=? AND IFNULL(sono,'')<>''))\n        ORDER BY id DESC LIMIT 1 FOR UPDATE\n    ");
    $st->bind_param('iss', $projectid, $orderid, $orderid);
    $st->execute();
    $fr = $st->get_result()->fetch_assoc();
    $st->close();

    if ($fr) {
        $freightId = (int)$fr['id'];
        $st = $krfconn->prepare("\n            UPDATE krf_freightcharges SET\n                clientid=?,\n                freight_amount=?,\n                freight_discountpercent=?,\n                freight_discountamount=?,\n                payable_freightamount=?,\n                remarks=?,\n                zerocharge=?,\n                status=COALESCE(NULLIF(status,''),'Active'),\n                updated_at=CURRENT_TIMESTAMP\n            WHERE id=? AND projectid=?\n        ");
        $st->bind_param(
            'sddddssii',
            $clientid,
            $totalAmount,
            $discountPercent,
            $discountAmount,
            $totalPayable,
            $fRemarks,
            $zerocharge,
            $freightId,
            $projectid
        );
        $st->execute();
        $st->close();
    } else {
        $status = 'Active';
        $st = $krfconn->prepare("\n            INSERT INTO krf_freightcharges (\n                projectid, psono, clientid,\n                freight_amount, freight_discountpercent, freight_discountamount,\n                payable_freightamount, remarks, status, created_by, zerocharge\n            ) VALUES (?,?,?,?,?,?,?,?,?,?,?)\n        ");
        $st->bind_param(
            'issddddssss',
            $projectid,
            $orderid,
            $clientid,
            $totalAmount,
            $discountPercent,
            $discountAmount,
            $totalPayable,
            $fRemarks,
            $status,
            $loginid,
            $zerocharge
        );
        $st->execute();
        $st->close();
    }

    $krfconn->commit();

    respond('success', 'Delivery address and freight details updated successfully.', [
        'orderid' => $orderid,
        'freight_amount' => round($totalAmount, 2),
        'freight_payable' => round($totalPayable, 2)
    ]);

} catch (Throwable $e) {
    $krfconn->rollback();
    respond('error', $e->getMessage());
}
