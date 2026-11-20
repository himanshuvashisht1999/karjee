<?php 
date_default_timezone_set("Asia/Calcutta");
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);  
require('../../../connection.php');  
header('Content-Type: application/json; charset=utf-8');
$action = isset($_GET['action']) ? $_GET['action'] : ''; 
if ($action === "updateorder") { 
    if (!isset($_POST['allocations'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'No allocation data received'
        ]);
        exit;
    }
    $allocationsJson = $_POST['allocations'];
    $allocations = json_decode($allocationsJson, true); 
    if (!is_array($allocations) || empty($allocations)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid allocation data'
        ]);
        exit;
    }

    $conn->begin_transaction();
    try {
        $first = $allocations[0];        
        $allocate_total =0;
        $order = [
            'projectid'        => (int)$first['projectid'],
            'customerid'       => $first['customerid'],
            'productid'        => $first['productid'],
            'expecteddelivery' => $first['expecteddelivery'],
            'orderpsono'       => $first['orderpsono'],
            'ordersono'        => $first['ordersono'],
            'orderqty'         => (int)$first['orderqty'],
            'productname'      =>  $first['productname'], 
        ];
        $orderqty    = $order['orderqty'];
        $customer_id = $order['customerid']; 
        $stockItems = [];  // each: ['id'=>..., 'qty'=>..., 'readydate'=>...]
        foreach ($allocations as $row) {
            $stockid_group = isset($row['stockid']) ? $row['stockid'] : "";
            $stock_qtypertray_group = isset($row['stock_qtypertray']) ? $row['stock_qtypertray'] : "";
            $readydate  = isset($row['stock_readydate']) ? $row['stock_readydate'] : null;
            $ids  = explode(",", $stockid_group);
            $qtys = explode(",", $stock_qtypertray_group);
            $max = min(count($ids), count($qtys));
            for ($i = 0; $i < $max; $i++) {
                $sid = trim($ids[$i]);
                $qty = trim($qtys[$i]);
                if ($sid === '' || $qty === '') {
                    continue;
                }
                $stockItems[] = [
                    'id'        => (int)$sid,
                    'qty'       => (int)$qty,   // if decimal, convert appropriately
                    'readydate' => $readydate,
                ];
            }
        }
        if (empty($stockItems) || $orderqty <= 0) {
               echo json_encode([
                'status' => 'error',
                'message' => "Ordery qty  is empty "
            ]);
            exit; // nothing to allocate
        }
        $lotQty = array_column($stockItems, 'qty');
        $best    = findBestCombinationAtLeast($lotQty, $orderqty);
        $indices = $best['indices'];   // indices in $stockItems
        $sum     = $best['sum'];
        if (empty($indices)) { 
            echo json_encode([
                'status' => 'error',
                'message' => "findBestCombinationAtLeast not fount"
            ]);
            exit;
        }
        foreach ($indices as $idx) {
            $stkid   = $stockItems[$idx]['id'];
            $stk_qty = $stockItems[$idx]['qty'];
            $stkqry = " UPDATE krf_readytosale_stocks SET assigntoclientid = '{$customer_id}' , status='ReadyToDelivered'  WHERE id = {$stkid}"; 
            $krfconn->query($stkqry);   
            $sql_consumed = " INSERT INTO krf_productionorder_consumed (psono, sono, stockorderno, product_id, product_name,  consumed_qty, wastage_qty, projectid, confirmstatus) VALUES ('{$order['orderpsono']}', '{$order['ordersono']}', '{$stkid}',  '{$order['productid']}', '{$order['productname']}','{$stk_qty}',  '0',  '{$order['projectid']}',  'reserved' )";
            $krfconn->query($sql_consumed);
            $allocate_total += $stk_qty;
        }
        if($allocate_total){
          $orderqry = "UPDATE krf_sales_order_items SET reservedqty = IFNULL(reservedqty, 0) + {$allocate_total} WHERE sono = '{$order['ordersono']}' AND psono = '{$order['orderpsono']}' AND product_id = '{$order['productid']}' AND projectid = '{$order['projectid']}'";
            $krfconn->query($orderqry);
        }
        $conn->commit();
         echo json_encode([
            'status' => 'success',
            'message' => "data updated"
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    
}
 
function findBestCombinationAtLeast(array $lots, int $targetQty): array
{
    $n = count($lots);
    if ($n == 0 || $targetQty <= 0) {
        return ['indices' => [], 'sum' => 0];
    }

    $totalSum = array_sum($lots);
    if ($totalSum <= 0) {
        return ['indices' => [], 'sum' => 0];
    }

    // dp[sum] = index of lot used to reach this sum
    // -1 = unreachable, -2 = base 0
    $dp      = array_fill(0, $totalSum + 1, -1);
    $prevSum = array_fill(0, $totalSum + 1, -1);

    $dp[0] = -2; // base

    for ($i = 0; $i < $n; $i++) {
        $w = (int)$lots[$i];
        if ($w <= 0) {
            continue;
        } 
        for ($s = $totalSum - $w; $s >= 0; $s--) {
            if ($dp[$s] != -1 && $dp[$s + $w] == -1) {
                $dp[$s + $w]      = $i;
                $prevSum[$s + $w] = $s;
            }
        }
    }

    // find smallest reachable sum >= targetQty
    $bestSum = 0;
    for ($s = $targetQty; $s <= $totalSum; $s++) {
        if ($dp[$s] != -1) {
            $bestSum = $s;
            break;
        }
    }

    // If nothing >= target is reachable (very rare),
    // fall back to max reachable sum <= totalSum
    if ($bestSum == 0) {
        for ($s = $totalSum; $s >= 0; $s--) {
            if ($dp[$s] != -1 || $s == 0) {
                $bestSum = $s;
                break;
            }
        }
    }

    if ($bestSum == 0) {
        return ['indices' => [], 'sum' => 0];
    }

    // backtrack to get indices
    $indices = [];
    $s       = $bestSum;
    while ($s > 0 && $dp[$s] >= 0) {
        $i      = $dp[$s];
        $indices[] = $i;
        $s      = $prevSum[$s];
    }

    return [
        'indices' => $indices,
        'sum'     => $bestSum,
    ];
}


?>
