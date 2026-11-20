<?php
ini_set('display_errors', '0');
error_reporting(E_ALL);
require __DIR__.'/confirmed_access.php';
require __DIR__.'/confirmed_helpers.php';
require __DIR__.'/confirmed_sowing.php';
header('Content-Type: application/json; charset=utf-8');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $scope=confirmedAjaxContext(false);
    confirmedAuthorize(($_POST['mode'] ?? '') === 'address');
    $psono = trim((string)($_POST['orderno'] ?? ''));
    $scopeProject=$scope[2];
    if ($psono === '' || $scopeProject === '') throw new RuntimeException('PSO number and project are required.');
    $krfconn->set_charset('utf8mb4');
    $krfconn->begin_transaction();
    $snapshot=ceSnapshot($krfconn,$psono,$scopeProject,$scope[0],$scope[1]);
    $snapshotToken=ceToken($snapshot);
    $snapshot['header']['sono']=$snapshot['sale']['sono'];
    if (($_GET['action'] ?? '') === 'allocated_stock') {
        $stockRows = [];
        $seenStock = [];
        foreach ($snapshot['items'] as $item) {
            if ((string)$item['product_id'] !== (string)($_POST['productid'] ?? '')) continue;
            $key = $item['stockorder_no'] . '|' . $item['product_id'];
            if (isset($seenStock[$key])) continue;
            $seenStock[$key] = true;
            $stocks = editQuery($krfconn, 'SELECT * FROM krf_production_stockorder WHERE stockorder_no=? AND productid=? AND projectid=?', [$item['stockorder_no'], $item['product_id'], $scopeProject]);
            if (count($stocks) !== 1) throw new RuntimeException('Existing stock identity needs checking.');
            $stock = $stocks[0];
            if (trim((string)($stock['psono'] ?? '')) === $psono) continue;
            $totals = editQuery($krfconn, "SELECT COALESCE(SUM(CASE WHEN confirmstatus='Confirmed' THEN consumed_qty ELSE 0 END),0) AS confirmed, COALESCE(SUM(CASE WHEN confirmstatus='Confirmed' THEN 0 ELSE consumed_qty END),0) AS unconfirmed FROM krf_productionorder_consumed WHERE stockorderno=? AND product_id=? AND projectid=? AND LOWER(COALESCE(confirmstatus,'')) NOT IN ('cancel','cancelled')", [$item['stockorder_no'], $item['product_id'], $scopeProject]);
            $t = $totals[0];
            $stockRows[] = ['stksno' => $stock['id'], 'stockorder_no' => $item['stockorder_no'], 'productid' => $item['product_id'], 'productname' => $item['product_name'], 'stkqty' => $stock['quantity'], 'readytosale_qty' => $stock['Qty_from_readysale'] ?? 0, 'confirmed_allocated' => $t['confirmed'], 'unconfirmed_allocated' => $t['unconfirmed'], 'availableqty' => (float)$stock['quantity'] + (float)($stock['Qty_from_readysale'] ?? 0) - (float)$t['confirmed'], 'expected_delivery_date' => $stock['expected_delivery_date'], 'displaydate' => $stock['expected_delivery_date'], 'locationname' => 'Existing allocation'];
        }
        $krfconn->commit();
        echo json_encode(['status' => 'success', 'data' => $stockRows]);
        exit;
    }
    $client = editQuery($krfconn, 'SELECT * FROM krf_client_master WHERE client_id = ? AND projectid = ? LIMIT 1', [$snapshot['header']['clientid'], $scopeProject]);
    foreach ($snapshot['items'] as &$item) {
        $stocks = editQuery($krfconn, 'SELECT id FROM krf_production_stockorder WHERE stockorder_no = ? AND productid = ? AND projectid = ? ORDER BY id', [$item['stockorder_no'], $item['product_id'], $scopeProject]);
        if (count($stocks) > 1) throw new RuntimeException('Ambiguous production stock identity: ' . $item['stockorder_no']);
        // Missing historical production stock must not prevent the confirmed order from loading.
        // The read-only sowing summary will explicitly flag the missing record.
        $item['edit_stock_id'] = $stocks[0]['id'] ?? '';
    }
    unset($item);
    $states = editQuery($krfconn, 'SELECT s.stateid, s.statename, s.statename_hn, d.districtid, d.districtname, d.districtname_hn FROM master_states s LEFT JOIN master_districts d ON d.stateid = s.stateid ORDER BY s.statename, d.districtname');
    $products = editQuery($krfconn, "SELECT ProductID, sku, ProductName, IsGrafted, PriceOfProduct, Status FROM product_master WHERE ProjectId=? AND LOWER(TRIM(Status))='active' ORDER BY ProductName", [$scopeProject]);
    $sowing=confirmedSowingSummary($krfconn,$snapshot,$products);
    $krfconn->commit();
    echo json_encode(['sowing'=>$sowing,'status' => 'success', 'data' => $snapshot, 'snapshot_token'=>$snapshotToken, 'client' => $client[0] ?? [], 'states' => $states, 'products' => $products]);
} catch (Throwable $e) {
    if (isset($krfconn)) $krfconn->rollback();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
