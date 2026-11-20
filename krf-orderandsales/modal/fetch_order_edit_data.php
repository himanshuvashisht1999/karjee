<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../connection.php';
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}

$psono     = trim($_REQUEST['psono'] ?? '');
$projectid = trim($_REQUEST['projectid'] ?? '');

if (empty($psono) || empty($projectid)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'PSONO and ProjectId are required.'
    ]);
    exit;
}

try {
    // 1. Fetch Header
    $stmtHdr = $krfconn->prepare("SELECT * FROM krf_provisional_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
    if (!$stmtHdr) throw new Exception("Prepare failed: " . $krfconn->error);
    $stmtHdr->bind_param("ss", $psono, $projectid);
    $stmtHdr->execute();
    $resHdr = $stmtHdr->get_result();
    $header = $resHdr->fetch_assoc();
    $stmtHdr->close();

    if (!$header) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Provisional sales order not found for: ' . $psono
        ]);
        exit;
    }

    // Check if sales order exists (Confirmed Order)
    $header['sono'] = '';
    $header['is_confirmed'] = false;
    $header['sales_order_status'] = '';
    $stmtSo = $krfconn->prepare("SELECT sono, id, status FROM krf_sales_order WHERE psono = ? AND (projectid = ? OR ? = 0) AND LOWER(TRIM(status)) NOT IN ('cancel','cancelled') LIMIT 1");
    if ($stmtSo) {
        $stmtSo->bind_param("sii", $psono, $projectid, $projectid);
        $stmtSo->execute();
        $soRow = $stmtSo->get_result()->fetch_assoc();
        $stmtSo->close();
        if ($soRow && !empty($soRow['sono'])) {
            $header['sono'] = $soRow['sono'];
            $header['is_confirmed'] = true;
            $header['sales_order_status'] = $soRow['status'];
        }
    }

    // 2. Fetch Client Info (details get from 'SELECT stateid,statename,districtid,districtname,tahseel,cityname FROM karjeefarm.krf_client_master' as per clientid)
    $client = null;
    $clientid = trim($header['clientid'] ?? '');
    if (!empty($clientid)) {
        $stmtCl = $krfconn->prepare("SELECT stateid, statename, districtid, districtname, tahseel, cityname, client_id, client_name, phones, client_email, invoicing_address, is_dealer FROM karjeefarm.krf_client_master WHERE client_id = ? LIMIT 1");
        if ($stmtCl) {
            $stmtCl->bind_param("s", $clientid);
            $stmtCl->execute();
            $client = $stmtCl->get_result()->fetch_assoc();
            $stmtCl->close();
        }
    }

    if (!$client && !empty($header['mobileno'])) {
        $stmtClPhone = $krfconn->prepare("SELECT stateid, statename, districtid, districtname, tahseel, cityname, client_id, client_name, phones, client_email, invoicing_address, is_dealer FROM karjeefarm.krf_client_master WHERE phones = ? AND (tedit IS NULL OR tedit = '' OR tedit = '0') LIMIT 1");
        if ($stmtClPhone) {
            $stmtClPhone->bind_param("s", $header['mobileno']);
            $stmtClPhone->execute();
            $client = $stmtClPhone->get_result()->fetch_assoc();
            $stmtClPhone->close();
        }
    }

    if ($client) {
        // Resolve stateid from master_states if missing
        if (empty($client['stateid']) && !empty($client['statename'])) {
            $stQ = $krfconn->prepare("SELECT stateid, statename FROM master_states WHERE LOWER(TRIM(statename)) = LOWER(TRIM(?)) LIMIT 1");
            if ($stQ) {
                $stQ->bind_param("s", $client['statename']);
                $stQ->execute();
                $stRes = $stQ->get_result()->fetch_assoc();
                if ($stRes) {
                    $client['stateid'] = $stRes['stateid'];
                    $client['statename'] = $stRes['statename'];
                }
                $stQ->close();
            }
        }
        // Resolve districtid from master_districts if missing
        if (empty($client['districtid']) && !empty($client['districtname'])) {
            if (!empty($client['stateid'])) {
                $dtQ = $krfconn->prepare("SELECT districtid, districtname FROM master_districts WHERE stateid = ? AND LOWER(TRIM(districtname)) = LOWER(TRIM(?)) LIMIT 1");
                if ($dtQ) {
                    $dtQ->bind_param("ss", $client['stateid'], $client['districtname']);
                    $dtQ->execute();
                    $dtRes = $dtQ->get_result()->fetch_assoc();
                    if ($dtRes) {
                        $client['districtid'] = $dtRes['districtid'];
                        $client['districtname'] = $dtRes['districtname'];
                    }
                    $dtQ->close();
                }
            }
            if (empty($client['districtid'])) {
                $dtQ = $krfconn->prepare("SELECT districtid, districtname, stateid FROM master_districts WHERE LOWER(TRIM(districtname)) = LOWER(TRIM(?)) LIMIT 1");
                if ($dtQ) {
                    $dtQ->bind_param("s", $client['districtname']);
                    $dtQ->execute();
                    $dtRes = $dtQ->get_result()->fetch_assoc();
                    if ($dtRes) {
                        $client['districtid'] = $dtRes['districtid'];
                        $client['districtname'] = $dtRes['districtname'];
                        if (empty($client['stateid'])) {
                            $client['stateid'] = $dtRes['stateid'];
                        }
                    }
                    $dtQ->close();
                }
            }
        }
    } else {
        $stName = $header['statename'] ?? '';
        $dtName = $header['districtname'] ?? '';
        $stId = '';
        $dtId = '';
        if (!empty($stName)) {
            $stQ = $krfconn->prepare("SELECT stateid, statename FROM master_states WHERE LOWER(TRIM(statename)) = LOWER(TRIM(?)) LIMIT 1");
            if ($stQ) {
                $stQ->bind_param("s", $stName);
                $stQ->execute();
                $stRes = $stQ->get_result()->fetch_assoc();
                if ($stRes) {
                    $stId = $stRes['stateid'];
                    $stName = $stRes['statename'];
                }
                $stQ->close();
            }
        }
        if (!empty($dtName)) {
            $dtQ = $krfconn->prepare("SELECT districtid, districtname FROM master_districts WHERE LOWER(TRIM(districtname)) = LOWER(TRIM(?)) LIMIT 1");
            if ($dtQ) {
                $dtQ->bind_param("s", $dtName);
                $dtQ->execute();
                $dtRes = $dtQ->get_result()->fetch_assoc();
                if ($dtRes) {
                    $dtId = $dtRes['districtid'];
                    $dtName = $dtRes['districtname'];
                }
                $dtQ->close();
            }
        }

        $client = [
            'client_id'         => $header['clientid'] ?? '',
            'client_name'       => $header['clientname'] ?? '',
            'phones'            => $header['mobileno'] ?? '',
            'client_email'      => $header['emailid'] ?? '',
            'invoicing_address' => $header['delivery_address'] ?? '',
            'statename'         => $stName,
            'districtname'      => $dtName,
            'cityname'          => '',
            'tahseel'           => '',
            'stateid'           => $stId,
            'districtid'        => $dtId,
            'is_dealer'         => 0
        ];
    }

    // 3. Fetch Items with Stock Info
    $items = [];
    $stmtItems = $krfconn->prepare("
        SELECT 
            i.*, 
            s.id AS stock_id, 
            s.clientid AS stock_clientid,
            s.Qty_from_readysale,
            s.status AS stock_status,
            s.expected_delivery_date AS stock_expected_date,
            s.sowingdate AS stock_sowingdate,
            s.scionsowingdate AS stock_scionsowingdate,
            s.sowingstatus AS stock_sowingstatus,
            s.workingstatus AS stock_workingstatus,
            COALESCE(cm.client_name, i_cm.client_name, '') AS stock_clientname,
            COALESCE(NULLIF(s.clientid, ''), NULLIF(i.clientid, ''), '') AS resolved_clientid,
            GREATEST(COALESCE(soi.dispatchedqty, 0), COALESCE(poc.dispatchedqty, 0)) AS dispatchedqty,
            soi.id AS sales_order_item_id,
            soi.status AS sales_order_item_status,
            (
                SELECT GROUP_CONCAT(DISTINCT LOWER(TRIM(sch.status)))
                FROM krf_polyhouse_sowingschedule sch
                WHERE (sch.stockorder_no = i.stockorder_no OR (sch.psono = i.psono AND sch.productid = i.product_id))
                  AND sch.projectid = i.projectid
                  AND LOWER(TRIM(sch.status)) NOT IN ('delete','removed','notrequired')
            ) AS sch_status_list
        FROM krf_provisional_sales_order_items i
        LEFT JOIN krf_production_stockorder s 
            ON s.stockorder_no = i.stockorder_no 
           AND s.productid = i.product_id 
           AND s.projectid = i.projectid
        LEFT JOIN krf_sales_order_items soi
            ON (soi.psono = i.psono AND (soi.stockorder_no = i.stockorder_no OR (soi.product_id = i.product_id AND COALESCE(i.stockorder_no,'') = '')) AND soi.projectid = i.projectid AND LOWER(TRIM(COALESCE(soi.status,''))) NOT IN ('cancel','cancelled'))
        LEFT JOIN krf_productionorder_consumed poc
            ON (poc.psono = i.psono AND poc.stockorderno = i.stockorder_no AND poc.projectid = i.projectid AND LOWER(TRIM(COALESCE(poc.status,''))) NOT IN ('cancel','cancelled') AND IFNULL(poc.tedit, '') = '')
        LEFT JOIN krf_client_master cm 
            ON s.clientid = cm.client_id
        LEFT JOIN krf_client_master i_cm 
            ON i.clientid = i_cm.client_id
        WHERE i.psono = ? AND i.projectid = ?
          AND (i.status IS NULL OR i.status != 'cancelled')
          AND (i.tedit IS NULL OR i.tedit NOT LIKE 'Removed%')
        ORDER BY i.id ASC
    ");
    if ($stmtItems) {
        $stmtItems->bind_param("ss", $psono, $projectid);
        $stmtItems->execute();
        $resItems = $stmtItems->get_result();
        while ($row = $resItems->fetch_assoc()) {
            $schList = !empty($row['sch_status_list']) ? explode(',', $row['sch_status_list']) : [];
            $schStatus = 'pending';
            if (!empty($schList)) {
                $nonPending = array_filter($schList, function($st) {
                    return trim($st) !== 'pending';
                });
                if (!empty($nonPending)) {
                    $schStatus = reset($nonPending);
                } else {
                    $schStatus = 'pending';
                }
            } else {
                $wStatus = strtolower(trim((string)($row['stock_workingstatus'] ?? '')));
                $swStatus = strtolower(trim((string)($row['stock_sowingstatus'] ?? '')));
                if (!empty($wStatus) && !in_array($wStatus, ['', 'pending'])) {
                    $schStatus = $wStatus;
                } elseif (!empty($swStatus) && $swStatus !== 'pending') {
                    $schStatus = $swStatus;
                } else {
                    $schStatus = 'pending';
                }
            }
            $row['sowing_schedule_status'] = $schStatus;

            // Check if item has its own production stock order and whether sowing has started
            $stkNo = trim($row['stockorder_no'] ?? '');
            $isSelfStock = false;
            $sowingStarted = false;
            $sowingQty = 0;
            $sowingStatusText = '';

            $isTrader = 0;
            $cId = trim($row['resolved_clientid'] ?: ($row['stock_clientid'] ?? ''));
            if ($cId === 'C00001') {
                $isTrader = 1;
            } elseif (!empty($cId)) {
                $trChk = $krfconn->query("SELECT is_trader FROM krf_client_master WHERE client_id = '{$krfconn->real_escape_string($cId)}' LIMIT 1");
                if ($trChk && $trRow = $trChk->fetch_assoc()) {
                    $isTrader = intval($trRow['is_trader'] ?? 0);
                }
            }

            if (!empty($stkNo)) {
                $stkStmt = $krfconn->prepare("SELECT psono, quantity, sowingstatus, workingstatus, sowingdate, scionsowingdate, clientid FROM krf_production_stockorder WHERE stockorder_no = ? AND (projectid = ? OR ? = 0) LIMIT 1");
                if ($stkStmt) {
                    $stkStmt->bind_param("sii", $stkNo, $projectid, $projectid);
                    $stkStmt->execute();
                    $stkData = $stkStmt->get_result()->fetch_assoc();
                    $stkStmt->close();
                    if ($stkData) {
                        $isSelfStock = (!empty($stkData['psono']) && strcasecmp(trim($stkData['psono']), $psono) === 0);
                        $sowingQty = (int)($stkData['quantity'] ?? 0);
                        $swStatus = strtolower(trim($stkData['sowingstatus'] ?? ''));
                        $wStatus = strtolower(trim($stkData['workingstatus'] ?? ''));
                        if (in_array($swStatus, ['start', 'sowed', 'done']) || in_array($wStatus, ['polyhouse', 'final dispatch'])) {
                            $sowingStarted = true;
                            $sowingStatusText = $stkData['sowingstatus'] ?: $stkData['workingstatus'];
                        }
                        if (empty($row['stock_clientid']) && !empty($stkData['clientid'])) {
                            $row['stock_clientid'] = $stkData['clientid'];
                        }
                        if ($isTrader !== 1) {
                            $stkCId = trim($stkData['clientid'] ?? '');
                            if ($stkCId === 'C00001') {
                                $isTrader = 1;
                            } elseif (!empty($stkCId)) {
                                $trChk2 = $krfconn->query("SELECT is_trader FROM krf_client_master WHERE client_id = '{$krfconn->real_escape_string($stkCId)}' LIMIT 1");
                                if ($trChk2 && $trRow2 = $trChk2->fetch_assoc()) {
                                    $isTrader = intval($trRow2['is_trader'] ?? 0);
                                }
                            }
                        }
                    }
                }
            }
            $row['is_trader'] = $isTrader;

            if (!empty($stkNo)) {
                $schStmt = $krfconn->prepare("SELECT status, sowingqty FROM krf_polyhouse_sowingschedule WHERE stockorder_no = ? AND (projectid = ? OR ? = 0) AND LOWER(TRIM(status)) NOT IN ('delete','removed','notrequired')");
                if ($schStmt) {
                    $schStmt->bind_param("sii", $stkNo, $projectid, $projectid);
                    $schStmt->execute();
                    $schRes = $schStmt->get_result();
                    while ($schRow = $schRes->fetch_assoc()) {
                        $schSt = strtolower(trim($schRow['status'] ?? ''));
                        if ($schSt !== 'pending') {
                            $sowingStarted = true;
                            if (empty($sowingStatusText)) $sowingStatusText = $schRow['status'];
                        }
                        if (!empty($schRow['sowingqty']) && (int)$schRow['sowingqty'] > 0) {
                            $sowingQty = max($sowingQty, (int)$schRow['sowingqty']);
                        }
                    }
                    $schStmt->close();
                }

                if (!$sowingStarted) {
                    $trStmt = $krfconn->prepare("SELECT COUNT(*) as sowed_cnt FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND (projectid = ? OR ? = 0) AND sowingdate_internal IS NOT NULL AND sowingdate_internal > '1970-01-01'");
                    if ($trStmt) {
                        $trStmt->bind_param("sii", $stkNo, $projectid, $projectid);
                        $trStmt->execute();
                        $trRow = $trStmt->get_result()->fetch_assoc();
                        $trStmt->close();
                        if (!empty($trRow['sowed_cnt']) && (int)$trRow['sowed_cnt'] > 0) {
                            $sowingStarted = true;
                            $sowingStatusText = 'Trays Sowed (' . $trRow['sowed_cnt'] . ')';
                        }
                    }
                }
            }

            if ($sowingQty <= 0) {
                $sowingQty = (int)($row['quantity'] ?? 0);
            }

            $row['is_self_stock'] = $isSelfStock;
            $row['sowing_started'] = $sowingStarted;
            $row['sowing_qty'] = $sowingQty;
            $row['sowing_status_text'] = $sowingStatusText;

            $items[] = $row;
        }
        $stmtItems->close();
    }

    // 4. Fetch Delivery Locations
    $delivery = [];
    $stmtDel = $krfconn->prepare("
        SELECT * 
        FROM krf_deliveryaddress_location 
        WHERE psono = ? AND projectid = ?
        ORDER BY id ASC
    ");
    if ($stmtDel) {
        $stmtDel->bind_param("ss", $psono, $projectid);
        $stmtDel->execute();
        $resDel = $stmtDel->get_result();
        while ($row = $resDel->fetch_assoc()) {
            $delivery[] = $row;
        }
        $stmtDel->close();
    }

    // 5. Fetch Freight Charges
    $freight = null;
    $stmtFr = $krfconn->prepare("
        SELECT * 
        FROM krf_freightcharges 
        WHERE psono = ? AND projectid = ?
        LIMIT 1
    ");
    if ($stmtFr) {
        $stmtFr->bind_param("ss", $psono, $projectid);
        $stmtFr->execute();
        $freight = $stmtFr->get_result()->fetch_assoc();
        $stmtFr->close();
    }

    // 6. Fetch Stock Consumed Records
    $consumed = [];
    $stmtCons = $krfconn->prepare("
        SELECT * 
        FROM krf_productionorder_consumed 
        WHERE psono = ? AND projectid = ? AND IFNULL(tedit, '') = ''
        ORDER BY id ASC
    ");
    if ($stmtCons) {
        $stmtCons->bind_param("ss", $psono, $projectid);
        $stmtCons->execute();
        $resCons = $stmtCons->get_result();
        while ($row = $resCons->fetch_assoc()) {
            $consumed[] = $row;
        }
        $stmtCons->close();
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'header'   => $header,
            'client'   => $client,
            'items'    => $items,
            'delivery' => $delivery,
            'freight'  => $freight,
            'consumed' => $consumed
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
