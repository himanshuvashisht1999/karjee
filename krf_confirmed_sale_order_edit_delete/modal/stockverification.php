<?php
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
include("../functions/seed_functions.php");
include("../functions/tray_functions.php");
include("../functions/client_functions.php");
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === "chkstock") {
    $projectid   = (int)($_POST['projectid'] ?? 0);
    $productid   = (int)($_POST['productid'] ?? 0);
    $requiredqty = (int)($_POST['requiredqty'] ?? 0);
    $deliverydate = $_POST['deliverydate'] ?? '';
    $returnarray = [];
    $qry = "SELECT
            x.*, 
            (
                x.base_availableqty
                + x.readytosale_qty
            ) AS availableqty
        FROM
        (SELECT  stk.id AS stksno,  stk.projectid, stk.clientid,  stk.productid, stk.sowinglocationid,
                p.ProductName AS productname, stk.stockorder_no, DATE_FORMAT(
                    stk.expected_delivery_date,
                    '%d-%m-%Y'
                ) AS displaydate, stk.expected_delivery_date,

                /* ====== ORIGINAL STOCK ORDER QTY====== */
                COALESCE(stk.quantity, 0) AS stkqty,
                COALESCE(stk.Qty_from_readysale, 0) AS ordreadytosaleqty,
                /* Trader flag */
                COALESCE(cl.is_trader, 0) AS is_trader,
                /* ========== CONFIRMED ALLOCATION===== */
                COALESCE(c.confirmed_allocated, 0) AS confirmed_allocated,
                /* ======UNCONFIRMED ALLOCATION======= */
                COALESCE(c.unconfirmed_allocated, 0)  AS unconfirmed_allocated,

                /* ========BASE AVAILABLE Stock Qty - Confirmed Allocation
                   ====================================== */
                (
                    (COALESCE(stk.quantity, 0) + COALESCE(stk.Qty_from_readysale, 0))
                    - COALESCE(c.confirmed_allocated, 0)
                ) AS base_availableqty,

                /* ================  TOTAL READY-TO-SALE PHYSICAL QTY This is the complete ReadyToSale qty  for this exact: Project + Product + Stock Order ===== */
                COALESCE(rs.total_readytosale_qty, 0)  AS total_readytosale_qty,

                /* ============  Example:
                   Stock Qty       = 220000
                   ReadyToSale     = 220252
                   Extra ReadySale = 252
                   ====================================== */
                CASE

                    WHEN COALESCE(cl.is_trader, 0) = 1
                         AND COALESCE(
                                rs.total_readytosale_qty,
                                0
                             ) > (COALESCE(stk.quantity, 0) +COALESCE(stk.Qty_from_readysale, 0))

                    THEN
                        COALESCE(
                            rs.total_readytosale_qty,
                            0
                        )
                        -
                        (COALESCE(stk.quantity, 0) +COALESCE(stk.Qty_from_readysale, 0))

                    ELSE 0

                END AS readytosale_qty


            FROM krf_production_stockorder AS stk


            /* ==========================================
               CLIENT MASTER
               ========================================== */
            LEFT JOIN krf_client_master AS cl

                ON cl.projectid = stk.projectid
               AND cl.client_id = stk.clientid AND stk.clientid='C00001'


            /* ==========================================
               CONFIRMED / UNCONFIRMED ALLOCATION

               Existing consumed_qty logic remains same.
               ========================================== */
            LEFT JOIN
            (
                SELECT
                    stockorderno,

                    SUM(
                        CASE
                            WHEN LOWER(
                                TRIM(
                                    COALESCE(
                                        confirmstatus,
                                        ''
                                    )
                                )
                            ) = 'confirmed'

                            THEN COALESCE(
                                consumed_qty,
                                0
                            )

                            ELSE 0
                        END
                    ) AS confirmed_allocated,


                    SUM(
                        CASE
                            WHEN LOWER(
                                TRIM(
                                    COALESCE(
                                        confirmstatus,
                                        ''
                                    )
                                )
                            ) <> 'confirmed'

                            THEN COALESCE(
                                consumed_qty,
                                0
                            )

                            ELSE 0
                        END
                    ) AS unconfirmed_allocated


                FROM krf_productionorder_consumed

                GROUP BY stockorderno

            ) AS c

                ON c.stockorderno = stk.stockorder_no


            /* ==========================================
               READY-TO-SALE STOCK

               IMPORTANT:
               Only status ReadyToSale is counted.

               Grouped independently by:
               projectid
               + productid
               + stockorder_no

               This prevents one Stock Order from
               affecting another Stock Order.
               ========================================== */
            LEFT JOIN
            (
                SELECT
                    projectid,
                    productid,
                    stockorder_no,

                    SUM(
                        CASE

                            WHEN LOWER(
                                REPLACE(
                                    TRIM(
                                        COALESCE(
                                            status,
                                            ''
                                        )
                                    ),
                                    ' ',
                                    ''
                                )
                            ) = 'readytosale'

                            THEN COALESCE(
                                qtypertray,
                                0
                            )

                            ELSE 0

                        END
                    ) AS total_readytosale_qty


                FROM krf_readytosale_stocks

                GROUP BY
                    projectid,
                    productid,
                    stockorder_no

            ) AS rs

                ON rs.projectid = stk.projectid
               AND rs.productid = stk.productid
               AND rs.stockorder_no = stk.stockorder_no


            /* ==========================================
               PRODUCT MASTER
               ========================================== */
            LEFT JOIN product_master AS p

                ON p.ProductID = stk.productid


            /* ==========================================
               FILTER
               ========================================== */
            WHERE stk.projectid = {$projectid}

              AND stk.productid = {$productid}

              AND stk.clientid = 'C00001'

              AND stk.status IN ('Confirmed', 'confirmed')

        ) AS x


        /* ==============================================
           SHOW ONLY STOCK HAVING AVAILABLE QTY
           ============================================== */
        WHERE
            (
                x.base_availableqty
                + x.readytosale_qty
            ) > 0


        /* ==============================================
           SORT
           ============================================== */
        ORDER BY

            x.expected_delivery_date ASC,

            ABS(
                (
                    x.base_availableqty
                    + x.readytosale_qty
                )
                - {$requiredqty}
            ) ASC
    ";


    $result = $krfconn->query($qry);


    if (!$result) {

        echo json_encode([
            'status'  => false,
            'message' => $krfconn->error,
            'data'    => []
        ]);

        exit;
    }


    // Edit mode: stock created for the current PSO is not selectable again.
    $excludedStocks = [];
    $editPsono = trim((string)($_POST['edit_psono'] ?? ''));
    if ($editPsono !== '') {
        $excludeStmt = $krfconn->prepare('SELECT stockorder_no FROM krf_production_stockorder WHERE projectid=? AND psono=?');
        if (!$excludeStmt) throw new RuntimeException($krfconn->error);
        $excludeStmt->bind_param('is', $projectid, $editPsono);
        if (!$excludeStmt->execute()) throw new RuntimeException($excludeStmt->error);
        $excludeResult = $excludeStmt->get_result();
        while ($excluded = $excludeResult->fetch_assoc()) $excludedStocks[(string)$excluded['stockorder_no']] = true;
        $excludeStmt->close();
    }
    while ($row = $result->fetch_assoc()) {
        if (isset($excludedStocks[(string)$row['stockorder_no']])) continue;
        $row['stkqty'] =  (int)$row['stkqty'];
        $row['confirmed_allocated'] = (int)$row['confirmed_allocated'];
        $row['unconfirmed_allocated'] = (int)$row['unconfirmed_allocated'];
        $row['base_availableqty'] = (int)$row['base_availableqty'];
        $row['total_readytosale_qty'] =  (int)$row['total_readytosale_qty'];
        $row['readytosale_qty'] =  (int)$row['readytosale_qty'];
        $row['availableqty'] =  (int)$row['availableqty'];
        $row['is_trader'] =  (int)$row['is_trader'];
        $returnarray[] = $row;
    }

    // Fetch location names map from HRM connection
    $locMap = [];
    if (isset($hrmconn) && ($hrmconn instanceof mysqli)) {
        $locRes = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE projectid = '{$projectid}'");
        if ($locRes) {
            while ($lr = $locRes->fetch_assoc()) {
                $locMap[trim((string)$lr['LocationId'])] = trim((string)$lr['LocationName']);
            }
        }
    }

    // Fetch polyhouse master names from KRF connection
    $phMap = [];
    if (isset($krfconn) && ($krfconn instanceof mysqli)) {
        $phRes = $krfconn->query("SELECT polyhouseid, polyhousename, locationid FROM krf_polyhouse_master WHERE projectid = {$projectid}");
        if ($phRes) {
            while ($pr = $phRes->fetch_assoc()) {
                $phMap[trim((string)$pr['polyhouseid'])] = [
                    'name' => trim((string)$pr['polyhousename']),
                    'locationid' => trim((string)$pr['locationid'])
                ];
            }
        }
    }

    // Gather stock order numbers
    $stockOrderNos = [];
    foreach ($returnarray as $rItem) {
        if (!empty($rItem['stockorder_no'])) {
            $stockOrderNos[] = "'" . $krfconn->real_escape_string($rItem['stockorder_no']) . "'";
        }
    }

    $plantStats = [];
    $orderLocMap = [];
    if (!empty($stockOrderNos)) {
        $soList = implode(',', array_unique($stockOrderNos));

        // Location mapping per stockorder
        $locSql = "SELECT DISTINCT stockorder_no, locationid FROM krf_polyhouse_plant_details WHERE projectid = {$projectid} AND stockorder_no IN ({$soList}) AND status in ('Readytosale','Polyhouse')";
        $locRes = $krfconn->query($locSql);
        if ($locRes) {
            while ($lr = $locRes->fetch_assoc()) {
                $so = trim((string)$lr['stockorder_no']);
                $lId = trim((string)$lr['locationid']);
                if (!isset($orderLocMap[$so])) $orderLocMap[$so] = [];
                $orderLocMap[$so][] = $lId;
            }
        }

        // Only count trays and plants having status/growth_stage 'Readytosale' and healthy status
        $plSql = "SELECT 
                    stockorder_no,
                    locationid,
                    polyhouseid,
                    COUNT(*) AS tray_count,
                    SUM(GREATEST(0, COALESCE(qtypertray, 0) - COALESCE(failurecount, 0))) AS plant_count
                  FROM krf_polyhouse_plant_details
                  WHERE projectid = {$projectid}
                    AND stockorder_no IN ({$soList})
                    AND (
                        LOWER(TRIM(COALESCE(health_status, ''))) = 'healthy'
                        OR health_status IS NULL
                        OR TRIM(health_status) = ''
                    )
                    AND status  in ('Readytosale','Polyhouse')
                  GROUP BY stockorder_no, locationid, polyhouseid";
        $plRes = $krfconn->query($plSql);
        if ($plRes) {
            while ($plr = $plRes->fetch_assoc()) {
                $so = trim((string)$plr['stockorder_no']);
                $locId = trim((string)$plr['locationid']);
                $phId = trim((string)$plr['polyhouseid']);
                $trays = (int)$plr['tray_count'];
                $plants = (int)$plr['plant_count'];
                if (!isset($plantStats[$so])) {
                    $plantStats[$so] = [];
                }
                $plantStats[$so][] = [
                    'locationid' => $locId,
                    'polyhouseid' => $phId,
                    'tray_count' => $trays,
                    'plant_count' => $plants
                ];
            }
        }
    }

    // Attach location & polyhouse plant/tray breakdown to each return row
    foreach ($returnarray as &$rItem) {
        $so = trim((string)($rItem['stockorder_no'] ?? ''));
        $stats = $plantStats[$so] ?? [];
        $locNames = [];
        $polyhouseDetails = [];
        $totTrays = 0;
        $totPlants = 0;

        if (!empty($stats)) {
            foreach ($stats as $st) {
                $locId = $st['locationid'];
                $phId = $st['polyhouseid'];
                $locName = $locMap[$locId] ?? $locId;
                if ($locName && !in_array($locName, $locNames)) {
                    $locNames[] = $locName;
                }
                $phName = $phMap[$phId]['name'] ?? ($phId ? "PH-{$phId}" : 'Polyhouse');
                $polyhouseDetails[] = "{$phName}: <strong>{$st['tray_count']} Trays</strong> (" . number_format($st['plant_count']) . " Plants)";
                $totTrays += $st['tray_count'];
                $totPlants += $st['plant_count'];
            }
        }

        // Fallback for location name if no ready-to-sale trays yet
        if (empty($locNames)) {
            if (!empty($orderLocMap[$so])) {
                foreach ($orderLocMap[$so] as $lId) {
                    $lName = $locMap[$lId] ?? $lId;
                    if ($lName && !in_array($lName, $locNames)) $locNames[] = $lName;
                }
            } elseif (!empty($rItem['sowinglocationid'])) {
                $fallbackLocId = trim((string)$rItem['sowinglocationid']);
                $locNames[] = $locMap[$fallbackLocId] ?? $fallbackLocId;
            }
        }

        $rItem['locationname'] = !empty($locNames) ? implode(', ', $locNames) : 'N/A';
        $rItem['polyhouse_details'] = !empty($polyhouseDetails) ? implode('<br>', $polyhouseDetails) : '';
        $rItem['total_trays'] = $totTrays;
        $rItem['total_plants'] = $totPlants;
    }
    unset($rItem);

    echo json_encode($returnarray);
    exit;
}else if ($action == "fecthgrowingdate") {
    $productid = $_POST['productid'] ?? 0;
    $projectid = $_POST['projectid'] ?? 0;
    $bufferdays = 7;

    $returnarray = [];

    /* =====================================
       1️⃣ GET EXPECTED DELIVERY DATE
    ===================================== */ 
    $rrs = getExpectedDeliveryDatesofProduct($krfconn, $projectid, $productid, $bufferdays); 
    if (!$rrs || empty($rrs['deliverydate'])) {
        echo json_encode([]);
        exit;
    }
    $expectedDeliveryDate = $rrs['deliverydate']; 
    /* =====================================
       2️⃣ GET SOWING DATE
    ===================================== */
    $swgdate = getSowingDatesWithSessionCorrection($krfconn, $expectedDeliveryDate, $projectid, $productid);
    // print_r($swgdate);
    if (!$swgdate || !isset($swgdate['RootSeedId'])) {
        echo json_encode([]);
        exit;
    }

    $rootsowingdate = $swgdate['RootSeedId']['sowingdate'];
    /* =====================================
       3️⃣ CALCULATE FULL CYCLE DAYS
    ===================================== */
    $fullcycledays = $rrs['growingdays'] + $rrs['bufferdays'] + $rrs['sessiondays'] + $rrs['aftergraftingdays'];

    /* =====================================
       4️⃣ FINAL RESPONSE
    ===================================== */
    $returnarray[] = [
        'expecteddeliverydate' => $expectedDeliveryDate,
        'sowingdate' => $rootsowingdate,
        'rootsowingdate' => $rootsowingdate,
        'fullcycledays' => $fullcycledays,
        'growingdays' => $rrs['growingdays']
    ];
    echo json_encode($returnarray);
}

?>