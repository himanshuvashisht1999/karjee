<?php
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
require(__DIR__ . '/../../connection.php');
require(__DIR__ . '/../../loginCell/functions/login_functions.php');
include(__DIR__ . '/../functions/orders_functions.php');
include(__DIR__ . '/../functions/product_functions.php');
include(__DIR__ . '/../functions/client_functions.php');
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
                WHERE projectid = {$projectid}
                  AND productid = {$productid}

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
              AND LOWER(TRIM(COALESCE(stk.status, ''))) NOT IN ('cancel', 'cancelled', 'deleted', 'removed')

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
    if (!$swgdate || !isset($swgdate['RootSeedId']) && !isset($swgdate['ScionSeedId'])) {
        echo json_encode([]);
        exit;
    }

    $productRows = getProductData($krfconn,$projectid,'ProductID',$productid);
    $g = strtolower(trim((string)($productRows[0]['IsGrafted'] ?? '')));
    $types = in_array($g,['1','yes','grafted'],true) ? ['RootSeedId','ScionSeedId'] : ['ScionSeedId'];
    $validSowing = true;
    foreach ($types as $type) {
        if (empty($swgdate[$type]['sowingdate']) || (int)($swgdate[$type]['growingdays'] ?? 0) <= 0) $validSowing = false;
    }
    $rootsowingdate = $swgdate[$types[0]]['sowingdate'] ?? null;
    /* =====================================
       3️⃣ CALCULATE FULL CYCLE DAYS
    ===================================== */
    $fullcycledays = $rrs['growingdays'] + $rrs['bufferdays'] + $rrs['sessiondays'] + $rrs['aftergraftingdays'];

    /* =====================================
       4️⃣ FINAL RESPONSE
    ===================================== */
    $returnarray[] = [
        'valid_sowing' => $validSowing,
        'message' => $validSowing ? '' : 'Sowing date unavailable. Please check seed growing days and product settings.',
        'expecteddeliverydate' => $expectedDeliveryDate,
        'sowingdate' => $rootsowingdate,
        'rootsowingdate' => $rootsowingdate,
        'fullcycledays' => $fullcycledays,
        'growingdays' => $rrs['growingdays']
    ];
    echo json_encode($returnarray);
}
else if ($action === "get_stock_allocations") {
    header('Content-Type: application/json');
    $stockorder_no = trim($_POST['stockorder_no'] ?? $_GET['stockorder_no'] ?? '');
    $type = strtolower(trim($_POST['type'] ?? $_GET['type'] ?? 'all'));
    $product_id = intval($_POST['product_id'] ?? $_GET['product_id'] ?? 0);

    if (empty($stockorder_no)) {
        echo json_encode(['status' => false, 'message' => 'Stock Order No is required.', 'data' => []]);
        exit;
    }

    $escStock = $krfconn->real_escape_string($stockorder_no);
    $typeCond = "";
    if ($type === 'confirmed') {
        $typeCond = " AND LOWER(TRIM(COALESCE(c.confirmstatus, ''))) = 'confirmed' ";
    } else if ($type === 'unconfirmed') {
        $typeCond = " AND LOWER(TRIM(COALESCE(c.confirmstatus, ''))) <> 'confirmed' ";
    }

    $prodCond = ($product_id > 0) ? " AND c.product_id = $product_id " : "";

    $sql = "SELECT 
        c.id AS consumed_id,
        c.psono,
        c.stockorderno,
        c.product_id,
        c.product_name,
        c.consumed_qty,
        c.confirmstatus,
        c.status AS consumed_status,
        c.dispatchedqty,
        DATE_FORMAT(c.dispatchedat, '%d-%m-%Y %H:%i') AS dispatched_at,
        c.remark AS consumed_remark,
        c.created_at AS allocated_at,
        c.created_by AS allocated_by,
        COALESCE(pso.clientid, 'C00001') AS clientid,
        COALESCE(pso.clientname, cl.client_name, 'Karjee Farm') AS clientname,
        COALESCE(pso.mobileno, cl.phones, '-') AS mobileno,
        COALESCE(pso.status, IF(c.psono = 'KF-INTERNAL', 'Internal Consumption', 'Allocated')) AS pso_status,
        DATE_FORMAT(COALESCE(pso.created_at, c.created_at), '%d-%m-%Y') AS order_date,
        DATE_FORMAT(COALESCE(psoi.expecteddeliverydate, pso.expecteddelivery, c.created_at), '%d-%m-%Y') AS expected_delivery_date,
        psoi.id AS psoi_id,
        psoi.quantity AS order_qty,
        psoi.rate AS item_rate,
        psoi.amount AS item_amount,
        psoi.status AS item_status,
        psoi.remark AS item_remark,
        so.status AS so_status,
        so.sono,
        inv.invoicenos,
        inv.inv_status,
        inv.inv_dispatched_plants
    FROM krf_productionorder_consumed c
    LEFT JOIN krf_provisional_sales_order pso 
        ON pso.psono = c.psono
    LEFT JOIN krf_client_master cl 
        ON cl.client_id = pso.clientid
    LEFT JOIN krf_provisional_sales_order_items psoi 
        ON psoi.psono = c.psono 
       AND psoi.product_id = c.product_id 
       AND (psoi.status IS NULL OR psoi.status = '' OR psoi.status != 'Cancelled')
    LEFT JOIN krf_sales_order so 
        ON so.psono = c.psono
    LEFT JOIN (
        SELECT 
            sii.psono,
            sii.productid,
            GROUP_CONCAT(DISTINCT sii.invoiceno ORDER BY sii.id SEPARATOR ', ') AS invoicenos,
            CASE 
                WHEN SUM(CASE WHEN LOWER(TRIM(COALESCE(sii.status, ''))) = 'dispatched' OR LOWER(TRIM(COALESCE(si.status, ''))) = 'dispatched' THEN 1 ELSE 0 END) > 0 THEN 'dispatched'
                WHEN SUM(CASE WHEN LOWER(TRIM(COALESCE(sii.status, ''))) = 'readytodispatch' OR LOWER(TRIM(COALESCE(si.status, ''))) = 'readytodispatch' THEN 1 ELSE 0 END) > 0 THEN 'readytodispatch'
                WHEN SUM(CASE WHEN LOWER(TRIM(COALESCE(sii.status, ''))) = 'readytoload' OR LOWER(TRIM(COALESCE(si.status, ''))) = 'readytoload' THEN 1 ELSE 0 END) > 0 THEN 'readytoload'
                ELSE MAX(sii.status)
            END AS inv_status,
            SUM(COALESCE(sii.total_dispatch_plants, sii.quantity, 0)) AS inv_dispatched_plants
        FROM sales_invoice_items sii
        LEFT JOIN sales_invoice si ON si.id = sii.invoice_id
        WHERE (sii.status IS NULL OR LOWER(TRIM(sii.status)) != 'cancelled')
          AND (si.status IS NULL OR LOWER(TRIM(si.status)) != 'cancelled')
        GROUP BY sii.psono, sii.productid
    ) inv ON inv.psono = c.psono AND inv.productid = c.product_id
    WHERE c.stockorderno = '$escStock'
      AND c.status IN ('pending', 'allocated', 'dispatched', 'FullDispatch', 'PartialDispatch', 'readytoload', 'readytodispatch', 'loaded')
      $typeCond
      $prodCond
    ORDER BY c.id ASC";

    $res = $krfconn->query($sql);
    if (!$res) {
        echo json_encode(['status' => false, 'message' => $krfconn->error, 'data' => []]);
        exit;
    }

    $allocations = [];
    $totalQty = 0;
    while ($row = $res->fetch_assoc()) {
        $qty = (float)$row['consumed_qty'];
        $totalQty += $qty;
        $row['consumed_qty'] = $qty;
        $row['is_internal'] = ($row['psono'] === 'KF-INTERNAL');

        // Calculate standardized dispatch status
        $cSt = strtolower(trim((string)($row['consumed_status'] ?? '')));
        $soSt = strtolower(trim((string)($row['so_status'] ?? '')));
        $invSt = strtolower(trim((string)($row['inv_status'] ?? '')));
        $dispQty = (float)($row['dispatchedqty'] ?? 0);

        if ($row['is_internal']) {
            $row['dispatch_status'] = 'Internal Farm';
            $row['dispatch_badge_class'] = 'bg-info text-dark';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'dispatched' || $cSt === 'fulldispatch' || $soSt === 'fulldispatch' || $soSt === 'delivered' || $soSt === 'completed' || $invSt === 'dispatched' || ($dispQty > 0 && $dispQty >= $qty)) {
            $row['dispatch_status'] = 'Dispatched';
            $row['dispatch_badge_class'] = 'bg-success text-white';
            $row['is_dispatched'] = true;
        } else if ($cSt === 'partialdispatch' || $soSt === 'partialdispatch' || ($dispQty > 0 && $dispQty < $qty)) {
            $row['dispatch_status'] = 'Partial Dispatch';
            $row['dispatch_badge_class'] = 'bg-warning text-dark';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'readytodispatch' || $invSt === 'readytodispatch') {
            $row['dispatch_status'] = 'Ready to Dispatch';
            $row['dispatch_badge_class'] = 'bg-primary text-white';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'loaded') {
            $row['dispatch_status'] = 'Loaded';
            $row['dispatch_badge_class'] = 'bg-info text-dark';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'readytoload' || $invSt === 'readytoload') {
            $row['dispatch_status'] = 'Ready to Load';
            $row['dispatch_badge_class'] = 'bg-secondary text-white';
            $row['is_dispatched'] = false;
        } else if ($cSt === 'allocated') {
            $row['dispatch_status'] = 'Allocated';
            $row['dispatch_badge_class'] = 'bg-primary-subtle text-primary border border-primary-subtle';
            $row['is_dispatched'] = false;
        } else {
            $row['dispatch_status'] = 'Not Dispatched';
            $row['dispatch_badge_class'] = 'bg-light text-secondary border';
            $row['is_dispatched'] = false;
        }

        $allocations[] = $row;
    }

    echo json_encode([
        'status' => true,
        'stockorder_no' => $stockorder_no,
        'type' => $type,
        'total_qty' => $totalQty,
        'data' => $allocations
    ]);
    exit;
}
else if ($action === "adjust_stock_balance") {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    $user_role_id = (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '' && is_numeric($_SESSION['loginroleid']))
        ? intval($_SESSION['loginroleid'])
        : null;

    if ($user_role_id === null && !empty($_SESSION['loginid'])) {
        $uLoginId = $_SESSION['loginid'];
        $uStmt = $authconn->prepare("SELECT loginroleid FROM login_user_master WHERE loginid = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param("s", $uLoginId);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRow = $uRes->fetch_assoc()) {
                $user_role_id = intval($uRow['loginroleid']);
                $_SESSION['loginroleid'] = $user_role_id;
            }
            $uStmt->close();
        }
    }

    if ($user_role_id === null && isset($_POST['loginroleid']) && $_POST['loginroleid'] !== '' && is_numeric($_POST['loginroleid'])) {
        $user_role_id = intval($_POST['loginroleid']);
    }

    if ($user_role_id === null) {
        $user_role_id = 99;
    }

    if ($user_role_id > 3) {
        echo json_encode(['status' => false, 'message' => 'Access Denied: Only Admin roles (Role <= 3) can adjust stock balance.']);
        exit;
    }

    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $product_name = trim($_POST['product_name'] ?? '');
    $projectid = intval($_POST['projectid'] ?? 1);
    $current_balance = intval($_POST['current_balance'] ?? 0);
    $new_balance = intval($_POST['new_balance'] ?? 0);
    $remark = trim($_POST['remark'] ?? '');

    if (empty($stockorder_no)) {
        echo json_encode(['status' => false, 'message' => 'Stock Order No is required.']);
        exit;
    }
    if (empty($remark)) {
        echo json_encode(['status' => false, 'message' => 'Correction remark is mandatory.']);
        exit;
    }
    if ($new_balance < 0) {
        echo json_encode(['status' => false, 'message' => 'New balance cannot be negative.']);
        exit;
    }

    // Lookup product name if not provided
    if (empty($product_name) && $product_id > 0) {
        $pRes = $krfconn->query("SELECT ProductName FROM product_master WHERE ProductID = $product_id LIMIT 1");
        if ($pRes && $pRow = $pRes->fetch_assoc()) {
            $product_name = $pRow['ProductName'];
        }
    }

    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $esc_so = $krfconn->real_escape_string($stockorder_no);
    $esc_remark = $krfconn->real_escape_string($remark);
    $esc_login = $krfconn->real_escape_string($loginname);

    if ($new_balance === $current_balance) {
        // Balance verified without change - record remark and audit
        $krfconn->query("UPDATE krf_production_stockorder 
            SET editedqty = {$new_balance},
                editedremark = CONCAT(COALESCE(editedremark, ''), ' | Verified count {$new_balance}: {$esc_remark}'),
                editedby = '{$esc_login}',
                editedon = NOW()
            WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");

        echo json_encode([
            'status' => true,
            'message' => "Stock balance verified and recorded as " . number_format($new_balance) . " plants.",
            'diff_qty' => 0,
            'new_balance' => $new_balance
        ]);
        exit;
    }

    if ($new_balance < $current_balance) {
        // CASE A: REDUCING BALANCE
        $lessed_qty = $current_balance - $new_balance;
        $fullRemark = "Consumed as Karjee Farm (Balance Correction): " . $remark;

        $stmt = $krfconn->prepare("INSERT INTO krf_productionorder_consumed 
            (psono, stockorderno, product_id, product_name, consumed_qty, projectid, created_by, created_at, updated_at, confirmstatus, remark, status)
            VALUES ('KF-INTERNAL', ?, ?, ?, ?, ?, ?, NOW(), NOW(), 'Confirmed', ?, 'allocated')");

        if (!$stmt) {
            echo json_encode(['status' => false, 'message' => 'Prepare failed: ' . $krfconn->error]);
            exit;
        }

        $stmt->bind_param("sisisss", $stockorder_no, $product_id, $product_name, $lessed_qty, $projectid, $loginname, $fullRemark);
        if ($stmt->execute()) {
            $insertId = $stmt->insert_id;
            $stmt->close();

            // Traceability in krf_production_stockorder
            $krfconn->query("UPDATE krf_production_stockorder 
                SET editedqty = {$new_balance},
                    editedremark = CONCAT(COALESCE(editedremark, ''), ' | Reduced by {$lessed_qty} to {$new_balance}: {$esc_remark}'),
                    editedby = '{$esc_login}',
                    editedon = NOW()
                WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");

            echo json_encode([
                'status' => true,
                'message' => "Stock balance successfully updated from " . number_format($current_balance) . " to " . number_format($new_balance) . ". Difference of " . number_format($lessed_qty) . " plants consumed as Karjee Farm.",
                'inserted_id' => $insertId,
                'diff_qty' => -$lessed_qty,
                'new_balance' => $new_balance
            ]);
        } else {
            $err = $stmt->error;
            $stmt->close();
            echo json_encode(['status' => false, 'message' => 'Insert error: ' . $err]);
        }
        exit;
    } else {
        // CASE B: INCREASING BALANCE ($new_balance > $current_balance)
        $diff = $new_balance - $current_balance;
        $remaining_diff = $diff;

        // 1. If existing KF-INTERNAL consumption records exist for this stock order, release/reduce them first
        $kfRes = $krfconn->query("SELECT id, consumed_qty FROM krf_productionorder_consumed 
            WHERE stockorderno = '{$esc_so}' AND psono = 'KF-INTERNAL' AND status IN ('pending', 'allocated', 'dispatched')
            ORDER BY id DESC");

        if ($kfRes && $kfRes->num_rows > 0) {
            while ($kfRow = $kfRes->fetch_assoc()) {
                if ($remaining_diff <= 0) break;
                $kfId = intval($kfRow['id']);
                $cQty = intval($kfRow['consumed_qty']);

                if ($cQty <= $remaining_diff) {
                    $krfconn->query("DELETE FROM krf_productionorder_consumed WHERE id = {$kfId}");
                    $remaining_diff -= $cQty;
                } else {
                    $newConsumedQty = $cQty - $remaining_diff;
                    $krfconn->query("UPDATE krf_productionorder_consumed SET consumed_qty = {$newConsumedQty}, updated_at = NOW() WHERE id = {$kfId}");
                    $remaining_diff = 0;
                }
            }
        }

        // 2. Remaining increase is added directly to quantity and sowingbalqty in krf_production_stockorder
        if ($remaining_diff > 0) {
            $krfconn->query("UPDATE krf_production_stockorder 
                SET quantity = quantity + {$remaining_diff},
                    sowingbalqty = GREATEST(0, sowingbalqty + {$remaining_diff}),
                    editedqty = {$new_balance},
                    editedremark = CONCAT(COALESCE(editedremark, ''), ' | Increased by {$diff} to {$new_balance}: {$esc_remark}'),
                    editedby = '{$esc_login}',
                    editedon = NOW()
                WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");
        } else {
            $krfconn->query("UPDATE krf_production_stockorder 
                SET editedqty = {$new_balance},
                    editedremark = CONCAT(COALESCE(editedremark, ''), ' | Increased by {$diff} to {$new_balance} (reversed KF-INTERNAL): {$esc_remark}'),
                    editedby = '{$esc_login}',
                    editedon = NOW()
                WHERE stockorder_no = '{$esc_so}' AND projectid = {$projectid}");
        }

        echo json_encode([
            'status' => true,
            'message' => "Stock balance successfully updated from " . number_format($current_balance) . " to " . number_format($new_balance) . " (+ " . number_format($diff) . " plants).",
            'diff_qty' => $diff,
            'new_balance' => $new_balance
        ]);
        exit;
    }
}
else if ($action === "check_removal_impact") {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    $consumed_id = intval($_POST['consumed_id'] ?? 0);
    $psoi_id = intval($_POST['psoi_id'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');

    if ($psono === 'KF-INTERNAL' || ($consumed_id > 0 && empty($psono))) {
        echo json_encode([
            'status' => true,
            'is_internal' => true,
            'is_full_removal' => false,
            'total_paid' => 0
        ]);
        exit;
    }

    $escPsono = $krfconn->real_escape_string($psono);

    // 1. Check Dispatch Status
    if ($consumed_id > 0) {
        $chkDisp = $krfconn->query("SELECT status, dispatchedqty FROM krf_productionorder_consumed WHERE id = $consumed_id LIMIT 1");
        if ($chkDisp && $dRow = $chkDisp->fetch_assoc()) {
            $st = strtolower(trim((string)$dRow['status']));
            if (in_array($st, ['dispatched', 'fulldispatch']) || (float)($dRow['dispatchedqty'] ?? 0) > 0) {
                echo json_encode([
                    'status' => false,
                    'is_dispatched' => true,
                    'message' => "Cannot remove: This order allocation has already been dispatched."
                ]);
                exit;
            }
        }
    }

    // 2. Check Sowing Status
    if ($psoi_id > 0) {
        $itemQ = $krfconn->query("SELECT id, product_name, stockorder_no, psono FROM krf_provisional_sales_order_items WHERE id = $psoi_id LIMIT 1");
        if ($itemQ && $itemInfo = $itemQ->fetch_assoc()) {
            $chkStk = trim($itemInfo['stockorder_no'] ?? '');
            $orderPsono = trim($itemInfo['psono'] ?? $psono);
            if (!empty($chkStk)) {
                $stkQ = $krfconn->query("SELECT psono, quantity, sowingstatus, workingstatus FROM krf_production_stockorder WHERE stockorder_no = '{$krfconn->real_escape_string($chkStk)}' LIMIT 1");
                if ($stkQ && $sRow = $stkQ->fetch_assoc()) {
                    $isSelf = (!empty($sRow['psono']) && strcasecmp(trim($sRow['psono']), $orderPsono) === 0);
                    $swStatus = strtolower(trim($sRow['sowingstatus'] ?? ''));
                    $wStatus = strtolower(trim($sRow['workingstatus'] ?? ''));
                    if ($isSelf && (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch']))) {
                        echo json_encode([
                            'status' => false,
                            'is_locked' => true,
                            'message' => "Cannot remove product '{$itemInfo['product_name']}': Sowing has already started ({$sRow['sowingstatus']}). Sown items cannot be removed."
                        ]);
                        exit;
                    }
                }
            }
        }
    }

    // 3. Count active items in krf_provisional_sales_order_items
    $targetItemId = $psoi_id;
    if ($targetItemId <= 0 && !empty($psono) && $product_id > 0) {
        $tQ = $krfconn->query("SELECT id FROM krf_provisional_sales_order_items WHERE psono = '$escPsono' AND product_id = $product_id AND (status IS NULL OR status = '' OR status != 'Cancelled') LIMIT 1");
        if ($tQ && $tRow = $tQ->fetch_assoc()) {
            $targetItemId = (int)$tRow['id'];
        }
    }

    $activeQ = $krfconn->query("SELECT id, product_name, quantity, rate, amount FROM krf_provisional_sales_order_items WHERE psono = '$escPsono' AND (status IS NULL OR status = '' OR status != 'Cancelled')");
    $totalActiveItems = 0;
    $remainingActiveItems = 0;
    $targetItemName = '';
    $targetItemQty = 0;
    $targetItemAmount = 0;

    if ($activeQ) {
        while ($aRow = $activeQ->fetch_assoc()) {
            $totalActiveItems++;
            if ($targetItemId > 0 && (int)$aRow['id'] === $targetItemId) {
                $targetItemName = $aRow['product_name'];
                $targetItemQty = (float)$aRow['quantity'];
                $targetItemAmount = (float)$aRow['amount'];
            } else {
                $remainingActiveItems++;
            }
        }
    }

    $isFullRemoval = ($remainingActiveItems === 0);

    // 4. Fetch linked SO No
    $sono = '';
    $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '$escPsono' LIMIT 1");
    if ($soQ && $soRow = $soQ->fetch_assoc()) {
        $sono = trim($soRow['sono'] ?? '');
    }

    // 5. Calculate Payment Received
    $escSono = $krfconn->real_escape_string($sono);
    $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = '$escPsono' OR (invoiceno != '' AND invoiceno = '$escSono')) AND (tedit IS NULL OR tedit = '')");
    $totalPaid = ($payQ && $pRow = $payQ->fetch_assoc()) ? (float)$pRow['total_paid'] : 0.00;

    // 6. Fetch Order Header details
    $hdrQ = $krfconn->query("SELECT clientid, clientname, grandtotal, created_at FROM krf_provisional_sales_order WHERE psono = '$escPsono' LIMIT 1");
    $hdr = ($hdrQ && $hRow = $hdrQ->fetch_assoc()) ? $hRow : [];

    echo json_encode([
        'status' => true,
        'is_internal' => false,
        'psono' => $psono,
        'sono' => $sono,
        'clientid' => $hdr['clientid'] ?? '',
        'clientname' => $hdr['clientname'] ?? 'Client',
        'order_date' => !empty($hdr['created_at']) ? date('d-m-Y', strtotime($hdr['created_at'])) : '',
        'grandtotal' => (float)($hdr['grandtotal'] ?? 0),
        'total_paid' => $totalPaid,
        'total_active_items' => $totalActiveItems,
        'remaining_active_items' => $remainingActiveItems,
        'is_full_removal' => $isFullRemoval,
        'target_item_name' => $targetItemName,
        'target_item_qty' => $targetItemQty,
        'target_item_amount' => $targetItemAmount
    ]);
    exit;
}
else if ($action === "remove_stock_allocation") {
    header('Content-Type: application/json');
    if (session_status() === PHP_SESSION_NONE) { session_start(); }

    $user_role_id = (isset($_SESSION['loginroleid']) && $_SESSION['loginroleid'] !== '' && is_numeric($_SESSION['loginroleid']))
        ? intval($_SESSION['loginroleid'])
        : null;

    if ($user_role_id === null && !empty($_SESSION['loginid'])) {
        $uLoginId = $_SESSION['loginid'];
        $uStmt = $authconn->prepare("SELECT loginroleid FROM login_user_master WHERE loginid = ? LIMIT 1");
        if ($uStmt) {
            $uStmt->bind_param("s", $uLoginId);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            if ($uRow = $uRes->fetch_assoc()) {
                $user_role_id = intval($uRow['loginroleid']);
                $_SESSION['loginroleid'] = $user_role_id;
            }
            $uStmt->close();
        }
    }

    if ($user_role_id === null && isset($_POST['loginroleid']) && $_POST['loginroleid'] !== '' && is_numeric($_POST['loginroleid'])) {
        $user_role_id = intval($_POST['loginroleid']);
    }

    if ($user_role_id === null) {
        $user_role_id = 99;
    }

    if ($user_role_id > 3) {
        echo json_encode(['status' => false, 'message' => 'Access Denied: Only Admin roles (Role <= 3) can remove allocations.']);
        exit;
    }

    $consumed_id = intval($_POST['consumed_id'] ?? 0);
    $psoi_id = intval($_POST['psoi_id'] ?? 0);
    $psono = trim($_POST['psono'] ?? '');
    $product_id = intval($_POST['product_id'] ?? 0);
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $remark = trim($_POST['remark'] ?? '');
    $financial_action = trim($_POST['financial_action'] ?? 'credit_note');
    $projectid = intval($_POST['projectid'] ?? 1);

    if (empty($remark)) {
        echo json_encode(['status' => false, 'message' => 'Cancellation remark is mandatory.']);
        exit;
    }

    $loginname = $_SESSION['loginname'] ?? $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'Admin';
    $userForUpdate = substr((string)$loginname, 0, 45);
    $now = date('Y-m-d H:i:s');
    $curdate = date('Y-m-d');
    $escRemark = $krfconn->real_escape_string($remark);

    // Case 1: Internal Karjee Farm consumption
    if ($psono === 'KF-INTERNAL' || ($consumed_id > 0 && empty($psono))) {
        $cancelRemark = "Cancelled by " . $userForUpdate . " on " . $now . " - Reason: " . $remark;
        $escCancel = $krfconn->real_escape_string($cancelRemark);
        $res = $krfconn->query("UPDATE krf_productionorder_consumed 
            SET confirmstatus = 'cancelled', 
                status = 'Cancelled', 
                remark = IF(remark IS NULL OR remark = '', '$escCancel', CONCAT(remark, ' | ', '$escCancel')), 
                updated_at = NOW() 
            WHERE id = $consumed_id");

        if ($res) {
            echo json_encode(['status' => true, 'is_full_removal' => false, 'message' => 'Internal Karjee Farm consumption cancelled successfully. Balance restored.']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Failed to cancel internal consumption: ' . $krfconn->error]);
        }
        exit;
    }

    // Case 2: Regular provisional sales order item
    try {
        // DISPATCH VALIDATION: If allocation is already dispatched, block removal
        if ($consumed_id > 0) {
            $chkDisp = $krfconn->query("SELECT status, dispatchedqty FROM krf_productionorder_consumed WHERE id = $consumed_id LIMIT 1");
            if ($chkDisp && $dRow = $chkDisp->fetch_assoc()) {
                $st = strtolower(trim((string)$dRow['status']));
                if (in_array($st, ['dispatched', 'fulldispatch']) || (float)($dRow['dispatchedqty'] ?? 0) > 0) {
                    echo json_encode(['status' => false, 'message' => "Cannot remove product: This order allocation has already been dispatched."]);
                    exit;
                }
            }
        }

        // SOWING VALIDATION: If psoi_id exists, check if sowing started for own stock
        if ($psoi_id > 0) {
            $itemQ = $krfconn->query("SELECT id, product_name, stockorder_no, psono FROM krf_provisional_sales_order_items WHERE id = $psoi_id LIMIT 1");
            if ($itemQ && $itemInfo = $itemQ->fetch_assoc()) {
                $chkStk = trim($itemInfo['stockorder_no'] ?? '');
                $orderPsono = trim($itemInfo['psono'] ?? $psono);
                if (!empty($chkStk)) {
                    $stkQ = $krfconn->query("SELECT psono, quantity, sowingstatus, workingstatus FROM krf_production_stockorder WHERE stockorder_no = '{$krfconn->real_escape_string($chkStk)}' LIMIT 1");
                    if ($stkQ && $sRow = $stkQ->fetch_assoc()) {
                        $isSelf = (!empty($sRow['psono']) && strcasecmp(trim($sRow['psono']), $orderPsono) === 0);
                        $swStatus = strtolower(trim($sRow['sowingstatus'] ?? ''));
                        $wStatus = strtolower(trim($sRow['workingstatus'] ?? ''));
                        if ($isSelf && (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch']))) {
                            echo json_encode([
                                'status' => false,
                                'message' => "Cannot remove product '{$itemInfo['product_name']}': Sowing has already started ({$sRow['sowingstatus']}). Sown items cannot be removed."
                            ]);
                            exit;
                        }
                    }
                }
            }
        }

        $teditText = substr("Cancelled by " . $userForUpdate . " on " . $now . " - Reason: " . $remark, 0, 255);
        $escTedit = $krfconn->real_escape_string($teditText);
        $escPsono = $krfconn->real_escape_string($psono);
        $escStock = $krfconn->real_escape_string($stockorder_no);

        // 1. Update krf_provisional_sales_order_items if psoi_id available, or by psono + product_id
        if ($psoi_id > 0) {
            $krfconn->query("UPDATE krf_provisional_sales_order_items 
                SET status = 'Cancelled', 
                    tedit = '$escTedit', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    updatedat = '$curdate', 
                    updatedby = '$userForUpdate' 
                WHERE id = $psoi_id");
        } else if (!empty($psono) && $product_id > 0) {
            $krfconn->query("UPDATE krf_provisional_sales_order_items 
                SET status = 'Cancelled', 
                    tedit = '$escTedit', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    updatedat = '$curdate', 
                    updatedby = '$userForUpdate' 
                WHERE psono = '$escPsono' AND product_id = '$product_id'");
        }

        // 2. Update krf_productionorder_consumed
        $consumedWhere = ($consumed_id > 0) ? "id = $consumed_id" : "psono = '$escPsono' AND product_id = '$product_id' AND stockorderno = '$escStock'";
        $krfconn->query("UPDATE krf_productionorder_consumed 
            SET confirmstatus = 'cancelled', 
                status = 'Cancelled', 
                remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                updated_at = NOW() 
            WHERE $consumedWhere");

        // 3. Update krf_sales_order_items
        if (!empty($psono) && $product_id > 0) {
            $krfconn->query("UPDATE krf_sales_order_items 
                SET status = 'Cancelled', 
                    remark = IF(remark IS NULL OR remark = '', '$escRemark', CONCAT(remark, ' | Cancelled: ', '$escRemark')), 
                    ammendedby = '$userForUpdate', 
                    updateamended = NOW() 
                WHERE psono = '$escPsono' AND product_id = '$product_id'");
        }

        // 4. Update krf_polyhouse_sowingschedule
        if (!empty($psono) && $product_id > 0) {
            $krfconn->query("UPDATE krf_polyhouse_sowingschedule 
                SET status = 'removed', 
                    remarks = IF(remarks IS NULL OR remarks = '', '$escRemark', CONCAT(remarks, ' | Cancelled: ', '$escRemark')), 
                    updated_by = '$userForUpdate', 
                    updated_at = NOW() 
                WHERE psono = '$escPsono' AND productid = '$product_id'");
        }

        // 5. Check if all items in krf_provisional_sales_order_items are now cancelled
        $isFullOrderCancellation = false;
        $financialMessage = '';

        if (!empty($psono)) {
            $chk = $krfconn->query("SELECT COUNT(*) AS active_cnt FROM krf_provisional_sales_order_items WHERE psono = '$escPsono' AND (status IS NULL OR status = '' OR status != 'Cancelled')");
            $activeCnt = ($chk && $r = $chk->fetch_assoc()) ? (int)$r['active_cnt'] : 0;
            if ($activeCnt === 0) {
                $isFullOrderCancellation = true;

                // Check payment received against psono / sono
                $sono = '';
                $soQ = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '$escPsono' LIMIT 1");
                if ($soQ && $soRow = $soQ->fetch_assoc()) {
                    $sono = trim($soRow['sono'] ?? '');
                }
                $escSono = $krfconn->real_escape_string($sono);

                $amount_paid = 0.00;
                $payQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS total_paid FROM advance_payment_transactions WHERE (invoiceno = '$escPsono' OR (invoiceno != '' AND invoiceno = '$escSono')) AND (tedit IS NULL OR tedit = '')");
                if ($payQ && $payRow = $payQ->fetch_assoc()) {
                    $amount_paid = floatval($payRow['total_paid'] ?? 0);
                }

                if ($amount_paid > 0) {
                    $psoHdrRes = $krfconn->query("SELECT clientid, clientname, companyid, subcompanyid, projectid FROM krf_provisional_sales_order WHERE psono = '$escPsono' LIMIT 1");
                    $psoHdr = $psoHdrRes ? $psoHdrRes->fetch_assoc() : [];
                    $clId = $psoHdr['clientid'] ?? '';
                    $clName = $psoHdr['clientname'] ?? '';
                    $comp = (int)($psoHdr['companyid'] ?? 0);
                    $subcomp = (int)($psoHdr['subcompanyid'] ?? 0);
                    $proj = (int)($psoHdr['projectid'] ?? $projectid);

                    if ($financial_action === 'refund') {
                        // Return Cash / Refund
                        $refRemarks = "Return Cash against full cancellation of Provisional Order #" . $psono . " - Reason: " . $remark;
                        $negAmt = -$amount_paid;
                        $stmtRef = $krfconn->prepare("INSERT INTO advance_payment_transactions (
                            companyid, subcompanyid, projectid, clientid, clientname, advanceamount, paymentdate, purposeofpayment, invoiceno, sourceofpayment, receivedby, remarks, transaction_type, allocated_datetime, verification
                        ) VALUES (?, ?, ?, ?, ?, ?, CURDATE(), 'refund', ?, 'Cash', ?, ?, 'Refund', NOW(), 'verified')");
                        if ($stmtRef) {
                            $stmtRef->bind_param("iiissdsss", $comp, $subcomp, $proj, $clId, $clName, $negAmt, $psono, $userForUpdate, $refRemarks);
                            $stmtRef->execute();
                            $stmtRef->close();
                        }

                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'WRITE_OFF', ?, ?, ?, 0.00, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $psono, $refRemarks, $amount_paid);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Return Cash / Refund of ₹" . number_format($amount_paid, 2) . " has been recorded for the customer.";
                    } else {
                        // Issue Credit Note
                        $cnRes = $krfconn->query("SELECT MAX(creditnote_id) AS max_id FROM krf_creditnote");
                        $nextId = 1;
                        if ($cnRes && $cnRow = $cnRes->fetch_assoc()) {
                            $nextId = intval($cnRow['max_id'] ?? 0) + 1;
                        }
                        $creditnote_no = "CN-" . date('Y') . "-" . str_pad((string)$nextId, 5, '0', STR_PAD_LEFT);
                        $narration = "Credit Note of Rs. " . number_format($amount_paid, 2) . " (Full Cancellation) for Client: " . ($clName ?: $clId) . " against cancelled Provisional Order " . $psono . (!empty($sono) ? ", SO: " . $sono : "") . ". Advance payment credited to client ledger. Reason: " . $remark . ". Processed by " . $loginname . " on " . date('d-m-Y H:i:s') . ".";

                        $stmtCn = $krfconn->prepare("INSERT INTO krf_creditnote (
                            creditnote_no, creditnote_date, projectid, companyid, subcompanyid,
                            clientid, clientname, psono, sono, stockorder_no, amount_paid, credit_amount, narration, reason, status, created_by, created_at, updated_at
                        ) VALUES (?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW(), NOW())");
                        if ($stmtCn) {
                            $stmtCn->bind_param("siiisssssddsss", $creditnote_no, $proj, $comp, $subcomp, $clId, $clName, $psono, $sono, $stockorder_no, $amount_paid, $amount_paid, $narration, $remark, $loginname);
                            $stmtCn->execute();
                            $stmtCn->close();
                        }

                        $stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
                            project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
                        ) VALUES (?, ?, CURDATE(), 'CREDIT_NOTE', ?, ?, 0.00, ?, 0.00, NOW())");
                        if ($stmtStmt) {
                            $stmtStmt->bind_param("isssd", $proj, $clId, $creditnote_no, $narration, $amount_paid);
                            $stmtStmt->execute();
                            $stmtStmt->close();
                        }
                        $financialMessage = " Credit Note (" . $creditnote_no . ") of ₹" . number_format($amount_paid, 2) . " has been issued and credited to client ledger.";
                    }
                }

                $autoRemark = "All items cancelled: " . $remark . (!empty($financialMessage) ? " [" . trim($financialMessage) . "]" : "");
                $escAuto = $krfconn->real_escape_string($autoRemark);
                $krfconn->query("UPDATE krf_provisional_sales_order 
                    SET status = 'Cancelled', 
                        remarks = IF(remarks IS NULL OR remarks = '', '$escAuto', CONCAT(remarks, ' | ', '$escAuto')), 
                        updated_by = '$userForUpdate', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono'");

                $krfconn->query("UPDATE krf_sales_order 
                    SET status = 'Cancelled', 
                        remarks = IF(remarks IS NULL OR remarks = '', '$escAuto', CONCAT(remarks, ' | ', '$escAuto')), 
                        updated_by = '$userForUpdate', 
                        updated_at = NOW() 
                    WHERE psono = '$escPsono'");
            }
        }

        if ($isFullOrderCancellation) {
            echo json_encode([
                'status' => true,
                'is_full_removal' => true,
                'message' => "Provisional order $psono has been fully cancelled." . $financialMessage
            ]);
        } else {
            echo json_encode([
                'status' => true,
                'is_full_removal' => false,
                'message' => "Product allocation removed from order $psono successfully."
            ]);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
    exit;
}

?>