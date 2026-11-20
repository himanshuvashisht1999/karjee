<?php 
date_default_timezone_set("Asia/Kolkata");
require('../../../connection.php');  
$action = isset($_GET['action']) ? $_GET['action'] : ''; 
// Validate POST
$projectid = isset($_POST['projectid']) ? trim($_POST['projectid']) : '';
if ($projectid === '') {
    echo json_encode([]);
    exit;
}
$projectid_int = is_numeric($projectid) ? intval($projectid) : $projectid;
 if ($action == "oderforsowing_nongrafted") {
    $projectid = $_POST['projectid']; 
    $qry = "SELECT  client.client_name,
    SUM(IFNULL(g.growingdays,0) + IFNULL(g.graftingdays,0) + IFNULL(g.seasonalcoveredtime,0)) AS growthperiod,
    p.productname, p.sku AS productsku,
    SUM(stk.sowingbalqty) AS balqty,  stk.productid, GROUP_CONCAT(DISTINCT stk.psono) AS psono,   GROUP_CONCAT(DISTINCT stk.stockorder_no) AS stockorder_no,  stk.expected_delivery_date,  DATE_SUB(
        stk.expected_delivery_date,
        INTERVAL (SUM(IFNULL(g.growingdays,0) 
               + IFNULL(g.graftingdays,0) 
               + IFNULL(g.seasonalcoveredtime,0)) + 7) DAY
    ) AS sowing_start_date,  WEEK(
        DATE_SUB(
            stk.expected_delivery_date,
            INTERVAL SUM(IFNULL(g.growingdays,0) 
                     + IFNULL(g.graftingdays,0) 
                     + IFNULL(g.seasonalcoveredtime,0)) DAY
        )
    ) AS sowing_week ,
-- Week start date (Monday)
    DATE_SUB(
        DATE_SUB(
            stk.expected_delivery_date,
            INTERVAL (SUM(IFNULL(g.growingdays,0) 
                         + IFNULL(g.graftingdays,0) 
                         + IFNULL(g.seasonalcoveredtime,0)) + 7) DAY
        ),
        INTERVAL WEEKDAY(
            DATE_SUB(
                stk.expected_delivery_date,
                INTERVAL (SUM(IFNULL(g.growingdays,0) 
                             + IFNULL(g.graftingdays,0) 
                             + IFNULL(g.seasonalcoveredtime,0)) + 7) DAY
            )
        ) DAY
    ) AS week_start,

    -- Week end date (Sunday)
    DATE_ADD(
        DATE_SUB(
            DATE_SUB(
                stk.expected_delivery_date,
                INTERVAL (SUM(IFNULL(g.growingdays,0) 
                             + IFNULL(g.graftingdays,0) 
                             + IFNULL(g.seasonalcoveredtime,0)) + 7) DAY
            ),
            INTERVAL WEEKDAY(
                DATE_SUB(
                    stk.expected_delivery_date,
                    INTERVAL (SUM(IFNULL(g.growingdays,0) 
                                 + IFNULL(g.graftingdays,0) 
                                 + IFNULL(g.seasonalcoveredtime,0)) + 7) DAY
                )
            ) DAY
        ),
        INTERVAL 6 DAY
    ) AS week_end

    FROM krf_production_stockorder stk
    JOIN krf_client_master client   ON client.projectid = stk.projectid   AND client.client_id = stk.clientid 
    JOIN product_master p   ON p.projectid = stk.projectid   AND p.productid = stk.productid
    LEFT JOIN krf_growingtime g    ON g.projectid = stk.projectid    AND g.productid = stk.productid WHERE stk.projectid = {$projectid}  AND stk.sowingbalqty > 0 AND stk.status = 'confirmed'  and p.IsGrafted='NonGrafted'  GROUP BY stk.productid, stk.clientid, stk.expected_delivery_date ORDER BY sowing_week ASC, p.productname ASC"; 
    $result=$krfconn->query($qry);      
    if ($result->num_rows>0) {
        while ($row=$result->fetch_assoc()) { 
            $returnarray[] = $row;                   
        }
    }
    echo  json_encode($returnarray);  

}
 
?>
