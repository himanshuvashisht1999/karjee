<?php
// Order editor helpers only. Retained function bodies preserve the supplied logic.
function getProductData($conn, $projectid, $shortby = "all", $shortbyvalue = null)
{
    $data = [];
    // Allowed filterable columns
    $allowedColumns = ["ProductID", "ProductName", "CategoryId", "SeedId", "ScionSeedId", "IsGrafted", "sku", "Status", "PriceOfProduct"];
    try {
        if (empty($projectid)) {
            throw new Exception("ProjectId is mandatory.");
        }
        $sql = "SELECT * FROM product_master WHERE lcase(Status) = 'active' AND ProjectId = ?";
        $params = [$projectid];
        $types = "s";
        if ($shortby !== "all" && in_array($shortby, $allowedColumns) && !empty($shortbyvalue)) {
            $sql .= " AND $shortby = ?";
            $params[] = $shortbyvalue;
            $types .= "s";
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching products: " . $e->getMessage());
        return [];
    }
    return $data;
}

function saveFreightCharges($conn, $projectid, $psono, $sono, $clientid, $freight_amount, $freight_discountpercent, $remarks, $created_by, $zerocharge = 'no')
{
    if (isset($conn) && $conn) {
        @$conn->set_charset("utf8mb4");
    }
    // compute payable with explicit float casting to prevent TypeError in PHP 8.x
    $fAmt = (float)$freight_amount;
    $fDisc = (float)$freight_discountpercent;
    $payable_freightamount = round($fAmt - (($fAmt * $fDisc) / 100), 2);
    $status = "Active";
    $zerocharge = (strtolower(trim((string)$zerocharge)) === 'yes' || $zerocharge === '1' || $zerocharge === 1) ? 'yes' : 'no';

    // check existing by psono OR sono (since they are in a UNIQUE key)
    // Typically for provisional orders we check by psono
    $checkSql = "SELECT id FROM krf_freightcharges WHERE psono = ? OR (sono = ? AND IFNULL(sono,'')!='') LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("ss", $psono, $sono);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // update
        $updateSql = "UPDATE krf_freightcharges SET 
            projectid = ?, sono = ?, clientid = ?, freight_amount = ?, freight_discountpercent = ?, 
            payable_freightamount = ?, remarks = ?, status = ?, created_by = ?, zerocharge = ?,
            updated_at = NOW()
            WHERE psono = ?";
        $uStmt = $conn->prepare($updateSql);
        $uStmt->bind_param(
            "issdddsssss",
            $projectid,
            $sono,
            $clientid,
            $freight_amount,
            $freight_discountpercent,
            $payable_freightamount,
            $remarks,
            $status,
            $created_by,
            $zerocharge,
            $psono
        );

        if ($uStmt->execute()) {
            return ["status" => "success", "message" => "Updated successfully"];
        } else {
            return ["status" => "error", "message" => $uStmt->error];
        }
    } else {
        // insert
        $insertSql = "INSERT INTO krf_freightcharges 
            (projectid, psono, sono, clientid, freight_amount, freight_discountpercent, payable_freightamount, remarks, status, created_by, zerocharge, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $iStmt = $conn->prepare($insertSql);
        $iStmt->bind_param(
            "isssdddssss",
            $projectid,
            $psono,
            $sono,
            $clientid,
            $freight_amount,
            $freight_discountpercent,
            $payable_freightamount,
            $remarks,
            $status,
            $created_by,
            $zerocharge
        );

        if ($iStmt->execute()) {
            return ["status" => "success", "message" => "Inserted successfully"];
        } else {
            return ["status" => "error", "message" => $iStmt->error];
        }
    }
}

function getSeasonFromDate($date)
{
    $month = (int) date('n', strtotime($date));
    if (in_array($month, [12, 1, 2])) {
        return 'Winter';
    } elseif (in_array($month, [3, 4, 5, 6])) {
        return 'Summer';
    } else {
        return 'Rainy';
    }
}

function getSowingDatesWithSessionCorrection($conn, $deliverydate, $projectid, $productid)
{
    $result = [];

    /* =====================================
       1️⃣ PRODUCT DETAILS
    ===================================== */
    $sqlProduct = "
        SELECT ProductID, ProductName, SeedId AS RootSeedId, ScionSeedId, IsGrafted FROM product_master
        WHERE ProjectId = ? 
          AND Status = 'Active'
          AND ProductID = ?
    ";

    $stmt = $conn->prepare($sqlProduct);
    $stmt->bind_param("ii", $projectid, $productid);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product)
        return false;

    /* =====================================
       2️⃣ GROWING DAYS (ROOT + SCION)
    ===================================== */
    $sqlSeed = "
        SELECT SeedID, growingdays
        FROM seed_master
        WHERE Status = 'Active'
          AND ProjectId = ?
          AND SeedID IN (?, ?)
    ";

    $stmt = $conn->prepare($sqlSeed);
    $stmt->bind_param(
        "iii",
        $projectid,
        $product['RootSeedId'],
        $product['ScionSeedId']
    );
    $stmt->execute();

    $growingDays = [];
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $growingDays[$row['SeedID']] = (int) $row['growingdays'];
    }

    /* =====================================
       3️⃣ SESSION CORRECTION DAYS (JSON)
    ===================================== */
    $sqlSession = "SELECT SessionalCorrectionDays FROM krf_growthstages WHERE projectid = ? AND productid = ?";
    $sessionDays = [];
    if ($stmt = $conn->prepare($sqlSession)) {
        $stmt->bind_param("ii", $projectid, $productid);
        $stmt->execute();
        $sessionRow = $stmt->get_result()->fetch_assoc();
        $sessionDays = $sessionRow && !empty($sessionRow['SessionalCorrectionDays'])
            ? json_decode($sessionRow['SessionalCorrectionDays'], true)
            : [];
        $stmt->close();
    }

    /* =====================================
       4️⃣ CALCULATE SOWING DATES
    ===================================== */
    foreach (['RootSeedId', 'ScionSeedId'] as $type) {

        $seedId = $product[$type];
        if (!isset($growingDays[$seedId]))
            continue;

        // Base sowing date
        $sowingDate = date(
            'Y-m-d',
            strtotime($deliverydate . " -{$growingDays[$seedId]} days")
        );

        // Detect season
        $season = getSeasonFromDate($sowingDate);
        $sessionAdjust = $sessionDays[$season] ?? 0;

        /* -------------------------------
           APPLY BUSINESS RULE
        -------------------------------- */
        $aftergraftingdays = 0;
        if ($product['IsGrafted'] === 'Grafted') {
            $aftergraftingdays = 20;
            // GRAFTED: subtract (20 + session days)
            $totalSubtract = $aftergraftingdays + $sessionAdjust;
            $sowingDate = date(
                'Y-m-d',
                strtotime($sowingDate . " -{$totalSubtract} days")
            );

        } else {
            $aftergraftingdays = 0;
            // NORMAL: add session days
            if ($sessionAdjust > 0) {
                $sowingDate = date(
                    'Y-m-d',
                    strtotime($sowingDate . " -{$sessionAdjust} days")
                );
            }
        }
        // echo $sowingDate.'---'.$growingDays[$seedId].'==='.$sessionAdjust;
        // die();

        $fullcycledays = $growingDays[$seedId] + $aftergraftingdays + $sessionAdjust;
        $result[$type] = [
            'seedid' => $seedId,
            'fullcycledays' => $fullcycledays,
            'growingdays' => $growingDays[$seedId],
            'aftergraftingdays' => $aftergraftingdays,
            'season' => $season,
            'sessiondays' => $sessionAdjust,
            'isgrafted' => $product['IsGrafted'],
            'deliverydate' => $deliverydate,
            'sowingdate' => $sowingDate
        ];
    }
    return $result;
}

function getExpectedDeliveryDatesofProduct($conn, $projectid, $productid, $bufferdays = 7)
{
    $basedate = date('Y-m-d');
    /* =====================================
       1️⃣ PRODUCT DETAILS
    ===================================== */
    $sqlProduct = "
        SELECT ProductID, ProductName, SeedId AS RootSeedId, IsGrafted
        FROM product_master
        WHERE ProjectId = ?
          AND Status = 'Active'
          AND ProductID = ?
    ";

    $stmt = $conn->prepare($sqlProduct);
    $stmt->bind_param("ii", $projectid, $productid);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product)
        return false;

    /* =====================================
       2️⃣ GROWING DAYS (ROOT SEED)
    ===================================== */
    $sqlSeed = "
        SELECT SeedID, growingdays
        FROM seed_master
        WHERE Status = 'Active'
          AND ProjectId = ?
          AND SeedID = ?
    ";

    $stmt = $conn->prepare($sqlSeed);
    $stmt->bind_param("ii", $projectid, $product['RootSeedId']);
    $stmt->execute();
    $seedRow = $stmt->get_result()->fetch_assoc();

    if (!$seedRow)
        return false;

    $growingDays = (int) $seedRow['growingdays'];

    /* =====================================
       3️⃣ SESSION CORRECTION DAYS (JSON)
       👉 BASED ON BASE DATE SEASON
    ===================================== */
    $sqlSession = "
        SELECT SessionalCorrectionDays
        FROM krf_growthstages
        WHERE projectid = ?
          AND productid = ?
    ";

    $sessionDays = [];
    if ($stmt = $conn->prepare($sqlSession)) {
        $stmt->bind_param("ii", $projectid, $productid);
        $stmt->execute();
        $sessionRow = $stmt->get_result()->fetch_assoc();
        $sessionDays = $sessionRow && !empty($sessionRow['SessionalCorrectionDays'])
            ? json_decode($sessionRow['SessionalCorrectionDays'], true)
            : [];
        $stmt->close();
    }

    // Detect season from BASE DATE (today)
    $baseSeason = getSeasonFromDate($basedate);
    $sessionAdjust = (int) ($sessionDays[$baseSeason] ?? 0);

    /* =====================================
       4️⃣ CALCULATE EXPECTED DELIVERY DATE
    ===================================== */

    // Base formula: today + growing + buffer + session correction
    $totalDays = $growingDays + $bufferdays + $sessionAdjust;

    $deliveryDate = date(
        'Y-m-d',
        strtotime($basedate . " +{$totalDays} days")
    );

    /* -------------------------------
       APPLY GRAFTING RULE
    -------------------------------- */
    if ($product['IsGrafted'] === 'Grafted') {
        $aftergraftingdays = 20;

        $deliveryDate = date(
            'Y-m-d',
            strtotime($deliveryDate . " +{$aftergraftingdays} days")
        );
    }

    return [
        'deliverydate' => $deliveryDate,
        'basedate' => $basedate,
        'season' => $baseSeason,
        'growingdays' => $growingDays,
        'bufferdays' => $bufferdays,
        'sessiondays' => $sessionAdjust,
        'aftergraftingdays' => ($product['IsGrafted'] === 'Grafted') ? 20 : 0
    ];
}

function getFullProductionStockOrdersDetails($conn, $projectid, $shortby = null, $shortbyvalue = null)
{
    // Base query
    // Parameter list
 
    $sql = "SELECT * FROM krf_production_stockorder WHERE lcase(status) IN ('confirmed') and projectid = ?"; 
    // Parameter list
    $params = [$projectid];
    $types = "i";  // projectid is integer

    // Only allowed columns can be filtered
    $allowedCols = [
        "id" => "i",
        "stockorder_no" => "s",
        "companyid" => "i",
        "subcompanyid" => "i",
        "clientid" => "i",
        "productid" => "i",
        "product_name" => "s",
        "quantity" => "i",
        "expected_delivery_date" => "s",
        "sowingdate" => "s",
        "scionsowingdate" => "s",
        "created_by" => "s",
        "created_at" => "s"
    ];

    // FILTER 1: Sowing date between two dates
    if ($shortby == "sowingbetweendates" && !empty($shortbyvalue)) {
        $sowdates = explode("|", $shortbyvalue);
        if (count($sowdates) == 2 && !empty($sowdates[0]) && !empty($sowdates[1])) {
            $dateFrom = $sowdates[0];
            $dateTo = $sowdates[1];
            $sql .= " AND (sowingdate >= ? AND sowingdate <= ?)";
            $params[] = $dateFrom;
            $params[] = $dateTo;
            $types .= "ss";
        }

    } else if ($shortby == "allpendingtillsowingdate" && !empty($shortbyvalue)) {
        $sowdates = explode("|", $shortbyvalue);
        if (count($sowdates) == 2 && !empty($sowdates[0]) && !empty($sowdates[1])) {
            $dateFrom = $sowdates[0];
            $dateTo = $sowdates[1];
            $sql .= " AND  sowingdate <= ?";
            $params[] = $dateTo;
            $types .= "s";
        }

    }
    // ---------------------------------------------
    // FILTER 2: Generic WHERE equals (column = value)
    // ---------------------------------------------
    else if (!empty($shortby) && !empty($shortbyvalue) && isset($allowedCols[$shortby])) {

        $safeCol = $shortby;  // safe because checked in whitelist

        $sql .= " AND $safeCol = ?";
        $params[] = $shortbyvalue;

        $types .= $allowedCols[$shortby];
    }

    // ---------------------------------------------
    // SORT latest first
    // ---------------------------------------------
    $sql .= " ORDER BY created_at DESC";

    // ---------------------------------------------
    // Prepare
    // ---------------------------------------------
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    // Bind parameters
     // echo debugQueryProduct($sql, $params, $types);
     // die;
    $stmt->bind_param($types, ...$params);

    // Execute
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }

    $stmt->close();
    return $orders;
}

function debugQueryProduct($sql, $params, $types)
{
    $output = $sql;
    $i = 0;
    foreach (str_split($types) as $type) {
        if (!isset($params[$i])) {
            continue;
        }
        $val = $params[$i];
        if ($type === 'i') {
            $replacement = (int) $val;
        } elseif ($type === 'd') {
            $replacement = (float) $val;
        } else {
            $replacement = "'" . addslashes($val) . "'";
        }
        $output = preg_replace('/\?/', $replacement, $output, 1);
        $i++;
    }
    return $output;
}
