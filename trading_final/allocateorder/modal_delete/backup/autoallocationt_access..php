<?php 
    date_default_timezone_set("Asia/Kolkata");
    require('../../../connection.php');   
    $action = isset($_GET['action']) ? $_GET['action'] : ''; 
    $projectid = isset($_POST['projectid']) ? trim($_POST['projectid']) : '';
    $traderid=gettraderid($krfconn,$projectid);
    if($traderid){
        echo "Trader id not found";
        exit;
    }
    $krfconn->begin_transaction();
    try{
        $sql = "SELECT  so.sono, so.psono ,so.projectid, so.clientid,so.created_at ,soi.expecteddeliverydate , soi.product_id, soi.quantity as orderqty,
            IFNULL((SELECT SUM(stk.qtypertray) FROM krf_readytosale_stocks stk WHERE stk.productid = soi.product_id and stk.assigntoclientid=soi.clientid   AND stk.psono = soi.psono), 0) AS readystock from  krf_sales_order so join krf_provisional_sales_order_items soi on soi.psono=so.psono and soi.clientid=so.clientid join krf_client_master client on client.client_id =so.clientid and client.projectid  =so.projectid where ifnull(client.is_trader,0)=0 and so.projectid={projectid} order by so.created_at ,soi.expecteddeliverydate"
        $res = $krfconn->query($sql);
        $data = [];
        while ($row = $res->fetch_assoc()) {
            $orderQty   = (int)$row['orderqty']; 
            if ($readyStock > $orderQty) { 
                accessStockAdjust($krfconn, $projectid,$row['clientid'],$row['psono'],$row['product_id'],$orderQty, $traderid );
            }
        }
        $krfconn->commit();
        echo "Stock adjustment completed";
    } catch (Exception $e) {
        $krfconn->rollback();
        echo "Error: " . $e->getMessage();
    } 
    function accessStockAdjust($conn, $projectid, $clientid, $psono, $productid, $orderqty, $assigntraderid) { 
        $stocksql = " SELECT id, qtypertray  FROM krf_readytosale_stocks  WHERE projectid = '{$projectid}'  AND clientid  = '{$clientid}'  AND psono     = '{$psono}'  AND productid = '{$productid}'  ORDER BY id ";
        $result = $conn->query($stocksql);
        if (!$result) {
            throw new Exception("Stock query failed: " . $conn->error);
        }
        while ($stk_row = $result->fetch_assoc()) { 
            if ($orderqty <= 0) {
                $updateqry = "
                    UPDATE krf_readytosale_stocks  SET assigntoclientid = '{$assigntraderid}'   WHERE id = " . (int)$stk_row['id'];

                if (!$conn->query($updateqry)) {
                    throw new Exception("Update stock failed: " . $conn->error);
                }
            } 
            $orderqty -= (int)$stk_row['qtypertray'];
        }
    }
    function gettraderid($conn, $projectid) {
        $clntqry = "  SELECT client_id  FROM krf_client_master  WHERE IFNULL(is_trader,0) = 1   AND projectid = '{$projectid}'  LIMIT 1  ";
        $clntresult = $conn->query($clntqry);
        if ($clntresult && $clntresult->num_rows > 0) {
            $clntrow = $clntresult->fetch_assoc();
            return $clntrow['client_id'];
        }
        return "";  
    }
?>
