<?php
// Order editor helpers only. Retained function bodies preserve the supplied logic.
function getAdvancePaymentTransactions($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
    $data = [];
    $params = [$companyid, $subcompanyid, $projectid];
    $types = "iii";
    $where = " WHERE t.companyid = ? AND t.subcompanyid = ? AND t.projectid = ?";

    // Existing shortby logic
    if ($shortby != "all") {
        if($shortby == "unallocatedpayments") {
            $where .= " AND (t.invoiceno IS NULL OR t.invoiceno = '')";
        } else {
            $where .= " AND t.$shortby = ?";
            $params[] = $shortbyvalue;
            $types .= "s";
        }
    }

    // New Advanced Filters from GET/POST
    if (isset($_GET['filter_client']) && !empty($_GET['filter_client'])) {
        $where .= " AND TRIM(t.clientname) LIKE ?";
        $params[] = "%" . trim($_GET['filter_client']) . "%";
        $types .= "s";
    }

    if (isset($_GET['filter_utr']) && !empty($_GET['filter_utr'])) {
        $where .= " AND t.utrnumber LIKE ?";
        $params[] = "%" . trim($_GET['filter_utr']) . "%";
        $types .= "s";
    }

    if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
        if ($_GET['filter_status'] == 'allocated') {
            $where .= " AND (t.invoiceno IS NOT NULL AND t.invoiceno <> '')";
        } else if ($_GET['filter_status'] == 'unallocated') {
            $where .= " AND (t.invoiceno IS NULL OR t.invoiceno = '')";
        }
    }

    if (isset($_GET['filter_date_start']) && !empty($_GET['filter_date_start'])) {
        $where .= " AND t.paymentdate >= ?";
        $params[] = $_GET['filter_date_start'];
        $types .= "s";
    }

    if (isset($_GET['filter_date_end']) && !empty($_GET['filter_date_end'])) {
        $where .= " AND t.paymentdate <= ?";
        $params[] = $_GET['filter_date_end'];
        $types .= "s";
    }

    $sql = "SELECT t.*, p.expecteddelivery
            FROM advance_payment_transactions t
            LEFT JOIN krf_provisional_sales_order p ON t.invoiceno = p.psono
            $where
            GROUP BY t.sno
            ORDER BY t.paymentdate DESC, t.allocated_datetime DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function getNewProvisionalSalesOrderNo($conn, $companyid, $subcompanyid, $projectid) {
    $year = date('Y'); // Current year
    $prefix = "KF-PS-";
    $newNumber = 1;

    try {
        // Fetch last PSO number for this Project and Year
        $sql = "SELECT psono 
                FROM krf_provisional_sales_order 
                WHERE psono LIKE CONCAT(?, '%') 
                  AND companyid = ? 
                  AND subcompanyid = ? 
                  AND projectid = ?
                  AND YEAR(created_at) = ?  -- assuming you have a created_at or order_date column
                ORDER BY psono DESC 
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        // Bind parameters (siii + i for year)
        $stmt->bind_param("siiii", $prefix, $companyid, $subcompanyid, $projectid, $year);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $lastNo = $row['psono'];
            // Extract numeric part after the last dash
            $lastDigits = intval(substr($lastNo, strrpos($lastNo, '-') + 1));
            $newNumber = $lastDigits + 1;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error generating PSO number: " . $e->getMessage());
        return null;
    }

    // Format to 5 digits with leading zeros
    $formattedNumber = str_pad($newNumber, 5, "0", STR_PAD_LEFT);

    // Final PSO number
    return $prefix . $formattedNumber;
}

function getNewProductionStockOrderNo($conn, $companyid, $projectid) {
    $maxAttempts = 5; // Avoid infinite loops
    $attempt = 0;
    $newPrdStON ="";
    while ($attempt < $maxAttempts) {
        // Generate new stock order no
        $newPrdStON = 'KFP' . rand(1000, 9999);
        // Check if it already exists
        $sql = "SELECT stockorder_no 
                FROM krf_production_stockorder
                WHERE companyid = ?
                  AND projectid = ?
                  AND stockorder_no = ?
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("SQL Prepare Failed: " . $conn->error);
            return null;
        }
        $stmt->bind_param("iis", $companyid, $projectid, $newPrdStON);
        $stmt->execute();
        $stmt->store_result();
        // If NOT found → it's unique → return it
        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $newPrdStON;
        }
        // If exists → retry
        $stmt->close();
        $attempt++;
    }
    // If too many attempts failed, return null (duplicate issue)
    return $newPrdStON;
}

function getProvisionalSalesOrders($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
    global $authconn;
    $data = [];
    try {
        // Build user map from authconn login_user_master to resolve created_by ID to user name
        $userMap = [];
        if (isset($authconn) && $authconn) {
            $uRes = @$authconn->query("SELECT sno, loginid, loginname, mobileno FROM login_user_master");
            if ($uRes) {
                while ($uRow = $uRes->fetch_assoc()) {
                    $name = trim($uRow['loginname'] ?? '');
                    if (empty($name)) continue;

                    $sno = trim((string)$uRow['sno']);
                    $loginid = trim((string)$uRow['loginid']);
                    $mobile = trim((string)$uRow['mobileno']);

                    if ($sno !== '') $userMap[$sno] = $name;
                    if ($loginid !== '') {
                        $userMap[$loginid] = $name;
                        $userMap[strtolower($loginid)] = $name;
                    }
                    if ($mobile !== '') {
                        $userMap[$mobile] = $name;
                    }

                    $cleanMobile = preg_replace('/\D/', '', $mobile);
                    if (strlen($cleanMobile) >= 10) {
                        $userMap[substr($cleanMobile, -10)] = $name;
                    }

                    $cleanLogin = preg_replace('/\D/', '', $loginid);
                    if (strlen($cleanLogin) >= 10) {
                        $userMap[substr($cleanLogin, -10)] = $name;
                    }
                }
            }
        }

        // Base query with LEFT JOINs to fetch provisional orders, freight charges, and already paid advance payments
        $baseQuery = "
            SELECT 
                o.id, 
                o.psono, 
                o.companyid, 
                o.subcompanyid, 
                o.projectid, 
                o.clientid, 
                o.clientname, 
                o.emailid, 
                o.delivery_address, 
                o.districtname, 
                o.statename, 
                o.mobileno, 
                o.payment_terms, 
                o.remarks, 
                o.status, 
                o.pickupfrom,
                o.created_by, 
                o.created_at, 
                o.updated_at,
                IFNULL(p.paid_amount, 0) AS paid_amount,
                IFNULL(f.payable_freightamount, 0) AS freight_amount
            FROM krf_provisional_sales_order o
            LEFT JOIN (
                SELECT invoiceno, SUM(advanceamount) AS paid_amount 
                FROM advance_payment_transactions 
                WHERE invoiceno IS NOT NULL AND invoiceno != ''
                GROUP BY invoiceno
            ) p ON o.psono = p.invoiceno
            LEFT JOIN (
                SELECT psono, SUM(payable_freightamount) AS payable_freightamount 
                FROM krf_freightcharges 
                WHERE psono IS NOT NULL AND psono != ''
                GROUP BY psono
            ) f ON o.psono = f.psono
            WHERE (o.status='Pending Approval' or o.status='Confirmed' or o.status='Draft' or o.status='Emergency Sowing' or o.status='' or o.status is null or o.status='Cancelled') 
              AND o.companyid = '".$conn->real_escape_string($companyid)."'
              AND o.subcompanyid = '".$conn->real_escape_string($subcompanyid)."'
              AND o.projectid = '".$conn->real_escape_string($projectid)."'";

        // Conditions
        if ($shortby == "psono") {
            $baseQuery .= " AND o.psono = '".$conn->real_escape_string($shortbyvalue)."'";
        } else if ($shortby == "id" || $shortby == "sno") {
            $baseQuery .= " AND o.id = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "status") {
            $baseQuery .= " AND o.status = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "likelyclientname") {
            $baseQuery .= " AND o.clientname like '%".$conn->real_escape_string($shortbyvalue)."%'";
        } elseif ($shortby == "clientname") {
            $baseQuery .= " AND o.clientname = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "clientid") {
            $baseQuery .= " AND o.clientid = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlyconfirmedWithclientname") {
            $baseQuery .= " AND o.status='Confirmed' AND o.clientname = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlyconfirmedWithclientid") {
            $baseQuery .= " AND o.status='Confirmed' AND o.clientid = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlydraftWithclientname") {
            $baseQuery .= " AND o.status='Draft' AND o.clientname = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlydraftWithclientid") {
            $baseQuery .= " AND o.status='Draft' AND o.clientid = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlyconfirmedWithmobileno") {
            $baseQuery .= " AND o.status='Confirmed' AND o.mobileno = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "onlydraftWithmobileno") {
            $baseQuery .= " AND o.status='Draft' AND o.mobileno = '".$conn->real_escape_string($shortbyvalue)."'";
        } elseif ($shortby == "mobileno") {
            $baseQuery .= " AND o.mobileno = '".$conn->real_escape_string($shortbyvalue)."'";
        }
        $baseQuery .= " ORDER BY o.created_at DESC";

        $result = $conn->query($baseQuery);
        if ($result && $result->num_rows > 0) {
            $psonos = [];
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $row['items'] = [];
                $row['items_amount'] = 0;
                $row['total_amount'] = (float)$row['freight_amount'];
                $row['total_quantity'] = 0;
                $row['product_name'] = '';
                $row['quantity'] = 0;
                $row['expecteddeliverydate'] = '';
                $orders[$row['psono']] = $row;
                $psonos[] = "'" . $conn->real_escape_string($row['psono']) . "'";
            }

            // Fetch all line items for the matched provisional orders
            if (!empty($psonos)) {
                $itemSql = "SELECT * FROM krf_provisional_sales_order_items WHERE psono IN (" . implode(',', $psonos) . ") ORDER BY id ASC";
                $itemRes = $conn->query($itemSql);
                if ($itemRes && $itemRes->num_rows > 0) {
                    while ($iRow = $itemRes->fetch_assoc()) {
                        $p = $iRow['psono'];
                        if (isset($orders[$p])) {
                            $orders[$p]['items'][] = $iRow;
                            $orders[$p]['items_amount'] += (float)$iRow['amount'];
                            $orders[$p]['total_quantity'] += (float)$iRow['quantity'];
                            if (empty($orders[$p]['product_name'])) {
                                $orders[$p]['product_name'] = $iRow['product_name'];
                                $orders[$p]['quantity'] = $iRow['quantity'];
                                $orders[$p]['expecteddeliverydate'] = $iRow['expecteddeliverydate'];
                            }
                        }
                    }
                }
            }

            // Fetch delivery location & box packing charges for the matched provisional orders
            $locData = [];
            if (!empty($psonos)) {
                $locSql = "SELECT psono, pickuptype, noofbox, boxperpack, chargesperbox, deliverycharges, freightamt, totalamount, pickuppoint, deliveryaddress, dest_city, dest_district, dest_state 
                           FROM krf_deliveryaddress_location 
                           WHERE psono IN (" . implode(',', $psonos) . ") AND (tedit IS NULL OR tedit = '') 
                           ORDER BY id ASC";
                $locRes = $conn->query($locSql);
                if ($locRes && $locRes->num_rows > 0) {
                    while ($lRow = $locRes->fetch_assoc()) {
                        $p = $lRow['psono'];
                        if (!isset($locData[$p])) {
                            $locData[$p] = [
                                'box_packing_amount' => 0.0,
                                'delivered_freight' => 0.0,
                                'boxes' => 0,
                                'chargesperbox' => 0.0,
                                'boxperpack' => 0,
                                'pickuppoint' => '',
                                'deliveryaddress' => '',
                                'pickuptypes' => []
                            ];
                        }
                        $ptype = strtolower(trim($lRow['pickuptype'] ?? ''));
                        if ($ptype) {
                            $locData[$p]['pickuptypes'][] = $ptype;
                        }
                        if ($ptype === 'boxpacking') {
                            $bAmt = 0.0;
                            if ((float)($lRow['freightamt'] ?? 0) > 0) {
                                $bAmt = (float)$lRow['freightamt'];
                            } elseif ((float)($lRow['deliverycharges'] ?? 0) > 0) {
                                $bAmt = (float)$lRow['deliverycharges'];
                            } elseif ((int)($lRow['noofbox'] ?? 0) > 0 && (float)($lRow['chargesperbox'] ?? 0) > 0) {
                                $bAmt = (int)$lRow['noofbox'] * (float)$lRow['chargesperbox'];
                            }
                            $locData[$p]['box_packing_amount'] += $bAmt;
                            $locData[$p]['boxes'] += (int)($lRow['noofbox'] ?? 0);
                            if ($locData[$p]['chargesperbox'] <= 0 && (float)($lRow['chargesperbox'] ?? 0) > 0) {
                                $locData[$p]['chargesperbox'] = (float)$lRow['chargesperbox'];
                            }
                            if ($locData[$p]['boxperpack'] <= 0 && (int)($lRow['boxperpack'] ?? 0) > 0) {
                                $locData[$p]['boxperpack'] = (int)$lRow['boxperpack'];
                            }
                            if (empty($locData[$p]['pickuppoint']) && !empty($lRow['pickuppoint'])) {
                                $locData[$p]['pickuppoint'] = trim($lRow['pickuppoint']);
                            }
                            if (empty($locData[$p]['deliveryaddress'])) {
                                $addr = trim($lRow['deliveryaddress'] ?? '');
                                if (empty($addr)) {
                                    $parts = array_filter([$lRow['dest_city'] ?? '', $lRow['dest_district'] ?? '', $lRow['dest_state'] ?? '']);
                                    $addr = implode(', ', $parts);
                                }
                                $locData[$p]['deliveryaddress'] = $addr;
                            }
                        } elseif ($ptype === 'delivered') {
                            $dAmt = 0.0;
                            if ((float)($lRow['freightamt'] ?? 0) > 0) {
                                $dAmt = (float)$lRow['freightamt'];
                            } elseif ((float)($lRow['deliverycharges'] ?? 0) > 0) {
                                $dAmt = (float)$lRow['deliverycharges'];
                            }
                            $locData[$p]['delivered_freight'] += $dAmt;
                        }
                    }
                }
            }

            // Total amount = items total amount + freight charges amount + box packing charges amount
            foreach ($orders as $p => $ord) {
                $rawCreatedBy = trim((string)($orders[$p]['created_by'] ?? ''));
                $resolvedCreatedBy = $userMap[$rawCreatedBy] ?? ($userMap[strtolower($rawCreatedBy)] ?? '');
                if (empty($resolvedCreatedBy)) {
                    $cleanCreated = preg_replace('/\D/', '', $rawCreatedBy);
                    if (strlen($cleanCreated) >= 10 && isset($userMap[substr($cleanCreated, -10)])) {
                        $resolvedCreatedBy = $userMap[substr($cleanCreated, -10)];
                    } else if (!empty($rawCreatedBy)) {
                        $resolvedCreatedBy = $rawCreatedBy;
                    } else {
                        $resolvedCreatedBy = 'Admin';
                    }
                }
                $orders[$p]['created_by_raw'] = $rawCreatedBy;
                $orders[$p]['created_by'] = $resolvedCreatedBy;
                $orders[$p]['created_by_name'] = $resolvedCreatedBy;

                $pLoc = $locData[$p] ?? null;
                $boxAmt = $pLoc ? (float)$pLoc['box_packing_amount'] : 0.0;
                $hasBox = ($boxAmt > 0 || strtolower(trim($orders[$p]['pickupfrom'] ?? '')) === 'boxpacking');

                // If freight_amount from krf_freightcharges is 0, check delivered_freight from location
                if ((float)$orders[$p]['freight_amount'] <= 0 && $pLoc && (float)$pLoc['delivered_freight'] > 0) {
                    $orders[$p]['freight_amount'] = (float)$pLoc['delivered_freight'];
                }

                $orders[$p]['box_packing_amount'] = round($boxAmt, 2);
                $orders[$p]['has_box_packing'] = $hasBox;
                $orders[$p]['box_packing_details'] = [
                    'amount' => round($boxAmt, 2),
                    'noofbox' => $pLoc ? (int)$pLoc['boxes'] : 0,
                    'chargesperbox' => $pLoc ? (float)$pLoc['chargesperbox'] : 0.0,
                    'boxperpack' => $pLoc ? (int)$pLoc['boxperpack'] : 0,
                    'pickuppoint' => $pLoc ? $pLoc['pickuppoint'] : '',
                    'deliveryaddress' => $pLoc ? $pLoc['deliveryaddress'] : ''
                ];

                $orders[$p]['total_charges'] = round((float)$orders[$p]['freight_amount'] + $boxAmt, 2);
                $orders[$p]['total_amount'] = round((float)$orders[$p]['items_amount'] + (float)$orders[$p]['freight_amount'] + $boxAmt, 2);
                $orders[$p]['pending_balance'] = round(max(0, (float)$orders[$p]['total_amount'] - (float)$orders[$p]['paid_amount']), 2);
                $data[] = $orders[$p];
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching provisional sales orders: " . $e->getMessage());
        return [];
    }
    return $data;
}

function getProvisionalSalesOrderItems($conn, $psono) {
    $items = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE psono = ? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancelled', 'cancel') AND (tedit IS NULL OR tedit = '') AND quantity > 0");
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $psono);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching order items: " . $e->getMessage());
        return [];
    }

    return $items;
}

function getFinalSalesOrders($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
    $data = [];

    try {
        $params = [];
        $types = '';
        $where = " WHERE o.companyid = ? AND o.subcompanyid = ? AND o.projectid = ?";
        $params[] = $companyid;
        $params[] = $subcompanyid;
        $params[] = $projectid;
        $types .= 'iii';

        // Dynamic filtering
        if ($shortby != "all" && $shortbyvalue != "all") {
            switch ($shortby) {
                case "sono":
                    $where .= " AND o.sono = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;

                case "psono":
                    $where .= " AND o.psono = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;

                case "id":
                    $where .= " AND o.id = ?";
                    $params[] = $shortbyvalue;
                    $types .= 'i';
                    break;

                case "status":
                    $where .= " AND o.status = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;

                case "clientname":
                    $where .= " AND o.clientname = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;
                case "mobileno":
                    $where .= " AND o.mobileno = ?";
                    $params[] = $shortbyvalue;
                    $types .= 's';
                    break;
            }
        }

        // Final Query
        $sql = "
            SELECT 
                o.id,
                o.sono,
                o.psono,
                o.companyid,
                o.subcompanyid,
                o.projectid,
                o.clientid,
                o.clientname,
                o.emailid,
                o.delivery_address,
                o.districtname,
                o.statename,
                o.mobileno,
                o.payment_terms,
                o.remarks,
                o.total_amount,
                o.tax_amount,
                o.grand_total,
                o.status,
                o.created_by,
                o.created_at,
                o.updated_at,
                IFNULL(SUM(i.amount), 0) AS items_total_amount
            FROM krf_sales_order o
            LEFT JOIN krf_sales_order_items i ON o.sono = i.sono
            {$where}
            GROUP BY o.id, o.sono
            ORDER BY o.created_at DESC
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching sales orders: " . $e->getMessage());
    }

    return $data;
}

function getFinalSalesOrderItems($conn, $sono) {
    $items = [];
    try {
        $stmt = $conn->prepare("SELECT * FROM krf_sales_order_items WHERE sono = ? AND LOWER(TRIM(COALESCE(status,''))) NOT IN ('cancelled', 'cancel') AND quantity > 0");
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $sono);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching order items: " . $e->getMessage());
        return [];
    }

    return $items;
}

function createSowingScheduleForNewStockOrder($krfconn, $projectid, $stockorder_no, $productid, $product_name, $quantity, $sowingdate = null, $scionsowingdate = null, $created_by = null, $locationid = null, $trayid = null, $roottype_filter = null, $psono = null, $creation_source = 'new_provisional', $created_by_name = '', $change_reason = '')
{
    if (empty($stockorder_no) || empty($productid) || $quantity <= 0) {
        return;
    }

    $products = getProductData($krfconn,$projectid,'ProductID',$productid);
    if (!$products) throw new RuntimeException('Active product not found for sowing.');
    $g = strtolower(trim((string)$products[0]['IsGrafted']));
    $required = in_array($g,['1','yes','grafted'],true) ? ['SeedId'=>$sowingdate,'ScionSeedId'=>$scionsowingdate] : ['ScionSeedId'=>$scionsowingdate];
    foreach ($required as $field=>$date) {
        if (!$date || !preg_match('/^\d{4}-\d{2}-\d{2}$/',(string)$date) || $date==='0000-00-00') throw new RuntimeException('Sowing date unavailable. Please check seed growing days and product settings.');
        $seed = $krfconn->prepare("SELECT growingdays FROM seed_master WHERE ProjectId=? AND SeedID=? AND LOWER(TRIM(Status))='active'");
        $seedId=$products[0][$field]; $seed->bind_param('ss',$projectid,$seedId); $seed->execute(); $seedRow=$seed->get_result()->fetch_assoc(); $seed->close();
        if (!$seedRow || (int)$seedRow['growingdays']<=0) throw new RuntimeException('Active seed and positive growing days are required for sowing.');
    }

    try {
        // 1. Fetch seed details from product_master using getProductData
        $rootSeedId = '';
        $scionSeedId = '';
        $isGrafted = false;
        $rootTrayId = !empty($trayid) ? (int)$trayid : null;
        $scionTrayId = !empty($trayid) ? (int)$trayid : null;
        $rootTrayCavity = 209;
        $scionTrayCavity = 209;

        if (function_exists('getProductData')) {
            $pcd = getProductData($krfconn, $projectid, "ProductID", $productid);
            if (!empty($pcd) && isset($pcd[0])) {
                $pRow = $pcd[0];
                $rootSeedId = !empty($pRow['SeedId']) ? trim($pRow['SeedId']) : '';
                $scionSeedId = !empty($pRow['ScionSeedId']) ? trim($pRow['ScionSeedId']) : $rootSeedId;
                $gVal = $pRow['IsGrafted'] ?? 0;
                $isGrafted = ($gVal == 1 || strtolower(trim((string)$gVal)) === 'yes' || strtolower(trim((string)$gVal)) === 'grafted');

                if (empty($rootTrayId) && !empty($pRow['RootTrayId'])) $rootTrayId = (int)$pRow['RootTrayId'];
                if (empty($scionTrayId) && !empty($pRow['ScionTrayId'])) $scionTrayId = (int)$pRow['ScionTrayId'];
                if (empty($rootTrayId) && !empty($pRow['TrayId'])) $rootTrayId = (int)$pRow['TrayId'];
            }
        }

        // Direct fallback query if getProductData returned empty
        if (empty($rootSeedId) && empty($scionSeedId)) {
            $stmt_pm = $krfconn->prepare("SELECT ProductID, ProductName, SeedId, ScionSeedId, IsGrafted FROM product_master WHERE (ProductID = ? OR sku = ?) LIMIT 1");
            if ($stmt_pm) {
                $stmt_pm->bind_param("ss", $productid, $productid);
                $stmt_pm->execute();
                $pm = $stmt_pm->get_result()->fetch_assoc();
                $stmt_pm->close();

                if ($pm) {
                    $rootSeedId = !empty($pm['SeedId']) ? trim($pm['SeedId']) : '';
                    $scionSeedId = !empty($pm['ScionSeedId']) ? trim($pm['ScionSeedId']) : $rootSeedId;
                    $gVal = $pm['IsGrafted'] ?? 0;
                    $isGrafted = ($gVal == 1 || strtolower(trim((string)$gVal)) === 'yes' || strtolower(trim((string)$gVal)) === 'grafted');
                }
            }
        }

        // 2. Setup dates - strictly use the actual dates mentioned in krf_production_stockorder
        $todayStr = date('Y-m-d');
        if (empty($sowingdate) || $sowingdate === '0000-00-00') {
            $sowingdate = $todayStr;
        }
        if (empty($scionsowingdate) || $scionsowingdate === '0000-00-00') {
            $scionsowingdate = $sowingdate;
        }

        // 3. Quantity math with 30% germination buffer
        $orderqty = (int)$quantity;
        $bufferPercent = 30.00;
        $sowingqty = (int)ceil($orderqty * 1.30);
        $status = 'pending';
        $sourceLabels = [
            'new_provisional'=>'Created from new provisional order',
            'edit_provisional'=>'Created during provisional order modification',
            'edit_confirmed'=>'Created during confirmed order modification',
        ];
        $sourceLabel=$sourceLabels[$creation_source]??'Created with new production stock order';
        $actorLabel=trim((string)$created_by_name)!=='' ? trim((string)$created_by_name).' ('.$created_by.')' : (string)$created_by;
        $remarks=$sourceLabel.' | PSO: '.($psono?:$stockorder_no).' | Stock: '.$stockorder_no.' | Production quantity: '.$orderqty.' | Planned sowing quantity: '.$sowingqty.' | By: '.$actorLabel.' | On: '.(new DateTimeImmutable('now',new DateTimeZone('Asia/Calcutta')))->format('d-m-Y H:i:s');
        if(trim((string)$change_reason)!=='')$remarks.=' | Reason: '.trim((string)$change_reason);
        $effective_psono = !empty($psono) ? $psono : $stockorder_no;

        $ins_sql = "INSERT INTO krf_polyhouse_sowingschedule 
                    (projectid, stockorder_no, psono, productid, productname, sowing_date, orderqty, failurebufferpercent, sowingqty, locationid, roottype, seedid, trayid, traycavity, status, remarks, created_at, created_by, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";

        $stmt_ins = $krfconn->prepare($ins_sql);
        if ($stmt_ins) {
            if (!empty($roottype_filter)) {
                // Specific roottype single entry creation
                $rt_str = (!$isGrafted || strtolower(trim($roottype_filter)) === 'scion') ? 'Scion' : 'Root';
                $s_id = ($rt_str === 'Scion') ? (!empty($scionSeedId) ? $scionSeedId : $rootSeedId) : (!empty($rootSeedId) ? $rootSeedId : $scionSeedId);
                $t_id = ($rt_str === 'Scion') ? $scionTrayId : $rootTrayId;
                $t_cav = ($rt_str === 'Scion') ? $scionTrayCavity : $rootTrayCavity;
                $s_date = ($rt_str === 'Scion') ? $scionsowingdate : $sowingdate;

                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $s_date, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $rt_str, $s_id, $t_id, $t_cav, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();
            } else if ($isGrafted) {
                // Grafted Product -> Add Root AND Scion rows
                
                // 1) Root Row
                $roottype_root = 'Root';
                $seed_root = !empty($rootSeedId) ? $rootSeedId : $scionSeedId;
                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $sowingdate, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $roottype_root, $seed_root, $rootTrayId, $rootTrayCavity, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();

                // 2) Scion Row
                $roottype_scion = 'Scion';
                $seed_scion = !empty($scionSeedId) ? $scionSeedId : $rootSeedId;
                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $scionsowingdate, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $roottype_scion, $seed_scion, $scionTrayId, $scionTrayCavity, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();
            } else {
                // Non-grafted plants require one Scion schedule.
                $roottype_nongrafted = 'Scion';
                $seed_nongrafted = !empty($scionSeedId) ? $scionSeedId : $rootSeedId;
                $eff_trayid = !empty($scionTrayId) ? $scionTrayId : $rootTrayId;
                $eff_cavity = !empty($scionTrayCavity) ? $scionTrayCavity : $rootTrayCavity;
                $eff_sowingdate = !empty($sowingdate) ? $sowingdate : $scionsowingdate;

                $stmt_ins->bind_param("ssssssidisssiisss", 
                    $projectid, $stockorder_no, $effective_psono, $productid, $product_name, 
                    $eff_sowingdate, $orderqty, $bufferPercent, $sowingqty, $locationid,
                    $roottype_nongrafted, $seed_nongrafted, $eff_trayid, $eff_cavity, 
                    $status, $remarks, $created_by
                );
                $stmt_ins->execute();
            }
            $stmt_ins->close();
        }
    } catch (Exception $e) {
        error_log("Error in createSowingScheduleForNewStockOrder: " . $e->getMessage());
    }
}
