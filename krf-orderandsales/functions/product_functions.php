<?php
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

function getPlantCategoryData($conn, $projectid, $shortby = "all", $shortbyvalue = null)
{
    $data = [];
    // Allowed filterable columns (string vs int handled separately)
    $allowedColumns = ["CategoryID" => "i", "ProjectId" => "s", "CategoryName" => "s", "ParentCategoryID" => "i", "Status" => "s"];

    try {
        if (empty($projectid)) {
            throw new Exception("ProjectId is mandatory.");
        }
        $sql = "SELECT * FROM plant_category_master WHERE Status = 'Active' AND ProjectId = ?";
        $params = [$projectid];
        $types = "s";
        if ($shortby !== "all" && isset($allowedColumns[$shortby]) && !empty($shortbyvalue)) {
            $sql .= " AND $shortby = ?";
            $params[] = $shortbyvalue;
            $types .= $allowedColumns[$shortby]; // use correct type
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }
        // Bind params
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching plant categories: " . $e->getMessage());
        return [];
    }
    return $data;
}

function getSubCategoryData($conn, $projectid, $shortby = "all", $shortbyvalue = null)
{
    $data = [];
    // Allowed filterable columns (string vs int handled separately)
    $allowedColumns = ["SubCategoryID" => "i", "ProjectId" => "s", "CategoryID" => "s", "SubCategoryName" => "s", "Status" => "s", "CreatedAt" => "s", "CreatedBy" => "s", "UpdateAt" => "s"];
    try {
        if (empty($projectid)) {
            throw new Exception("ProjectId is mandatory.");
        }
        // Base query (only Active records by default)
        $sql = "SELECT * FROM plant_subcategory_master WHERE Status = 'Active' AND ProjectId = ?";
        $params = [$projectid];
        $types = "s";
        // Apply filter if not "all"
        if ($shortby !== "all" && isset($allowedColumns[$shortby]) && !empty($shortbyvalue)) {
            $sql .= " AND $shortby = ?";
            $params[] = $shortbyvalue;
            $types .= $allowedColumns[$shortby]; // append correct type
        }
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }
        // Bind parameters
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching plant types: " . $e->getMessage());
        return [];
    }
    return $data;
}

function saveSubCategory($conn, $SubCategoryName, $Description, $CategoryID, $ProjectId, $Status, $CreatedBy, $SubCategoryID = null)
{
    $timestamp = date('Y-m-d H:i:s');

    if (!empty($SubCategoryID)) {
        // UPDATE existing record
        $sql = "UPDATE `plant_subcategory_master`
                SET 
                    `SubCategoryName` = ?,
                    `Description` = ?,
                    `CategoryID` = ?,
                    `ProjectId` = ?,
                    `Status` = ?,
                    `UpdateAt` = ?
                WHERE `SubCategoryID` = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssi', $SubCategoryName, $Description, $CategoryID, $ProjectId, $Status, $timestamp, $SubCategoryID);

        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Subcategory updated successfully.'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to update subcategory: ' . $conn->error];
        }

    } else {
        // CHECK if SubCategoryName already exists
        $checkSql = "SELECT COUNT(*) AS cnt FROM `plant_subcategory_master` WHERE `SubCategoryName` = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $SubCategoryName);
        $checkStmt->execute();
        $result = $checkStmt->get_result()->fetch_assoc();

        if ($result['cnt'] > 0) {
            return ['status' => 'warning', 'message' => 'Subcategory name already exists.'];
        }

        // Generate unique SKU
        $sku = generateSubCategorySKU($conn, $SubCategoryName);

        // INSERT new record
        $sql = "INSERT INTO `plant_subcategory_master`
                (`sku`, `SubCategoryName`, `Description`, `CategoryID`, `ProjectId`, `Status`, `CreatedAt`, `CreatedBy`)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssss', $sku, $SubCategoryName, $Description, $CategoryID, $ProjectId, $Status, $timestamp, $CreatedBy);

        if ($stmt->execute()) {
            return ['status' => 'success', 'message' => 'Subcategory created successfully.'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to create subcategory: ' . $conn->error];
        }
    }
}

function generateProductSKU($conn, $ProjectId, $SeedID, $IsGraftedstatus)
{
    // Validate input
    if (empty(trim($SeedID))) {
        return [
            "status" => "error",
            "message" => "Seed ID is required."
        ];
    }

    // Fetch Seed SKU from seed_master
    $sql = "SELECT sku FROM seed_master WHERE SeedID = ? AND ProjectId = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $SeedID, $ProjectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $srow = $result->fetch_assoc();
    $stmt->close();

    if (!$srow) {
        return [
            "status" => "error",
            "message" => "Seed not found."
        ];
    }

    $seedsku = $srow['sku'];

    // Get base count of products
    $sql = "SELECT COUNT(ProductID) AS total FROM product_master";
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    $nextNumber = (int) $row['total'] + 1;

    do {
        // Format number as 3 digits (e.g., 001, 002, ..., 100)
        $skuNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);
        $sku = $seedsku . "-" . $skuNumber . "-" . strtoupper($IsGraftedstatus);

        // Check if SKU already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM product_master WHERE sku = ? AND ProjectId = ?");
        $stmt->bind_param("ss", $sku, $ProjectId);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count > 0) {
            $nextNumber++;
        }
    } while ($count > 0);

    return [
        "status" => "success",
        "sku" => $sku
    ];
}

function generatePlantCategorySKU($conn, $categoryName)
{
    $categoryName = trim($categoryName);

    if (empty($categoryName)) {
        return [
            "status" => "error",
            "message" => "Category name is required."
        ];
    }

    // Remove spaces and uppercase
    $cleanName = strtoupper(preg_replace('/\s+/', '', $categoryName));

    // Start with first 3 letters of category name
    $prefixLength = 3;
    $maxLength = strlen($cleanName);
    $attemptedPrefixes = [];

    // Loop to adjust prefix if SKU exists
    do {
        // If prefixLength exceeds name length, fallback to random number suffix
        if ($prefixLength > $maxLength) {
            $prefix = substr($cleanName, 0, 2) . rand(10, 99);
            break;
        }

        $prefix = substr($cleanName, 0, $prefixLength);

        // Format SKU as PREFIX + 2-digit number
        $sql = "SELECT COUNT(*) FROM plant_category_master WHERE sku = ?";
        $skuCandidate = $prefix . '01';

        // Prevent infinite loop
        if (in_array($skuCandidate, $attemptedPrefixes)) {
            $prefixLength++;
            continue;
        }

        $attemptedPrefixes[] = $skuCandidate;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $skuCandidate);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            // Found unique SKU
            return $skuCandidate;
        }

        // If SKU exists, increase prefix length and try again
        $prefixLength++;

    } while (true);
}


function generateSubCategorySKU($conn, $subCategoryName)
{
    $subCategoryName = trim($subCategoryName);

    if (empty($subCategoryName)) {
        return [
            "status" => "error",
            "message" => "Subcategory name is required."
        ];
    }

    // Remove spaces and convert to uppercase
    $cleanName = strtoupper(preg_replace('/\s+/', '', $subCategoryName));
    $nameLength = strlen($cleanName);

    $prefixLength = 3;
    $attemptedPrefixes = [];

    while (true) {
        // If prefixLength exceeds name length, fallback to random
        if ($prefixLength > $nameLength) {
            $baseSku = substr($cleanName, 0, 2) . rand(10, 99);
            $skuCandidate = $baseSku . '01';
        } else {
            $baseSku = substr($cleanName, 0, $prefixLength);
            $skuCandidate = $baseSku . '01';
        }

        // Prevent infinite loop
        if (in_array($skuCandidate, $attemptedPrefixes)) {
            $prefixLength++;
            continue;
        }

        $attemptedPrefixes[] = $skuCandidate;

        // Check if SKU exists
        $sql = "SELECT COUNT(*) FROM plant_subcategory_master WHERE sku = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $skuCandidate);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            return $skuCandidate;
        }
        // Increase prefix length and try again
        $prefixLength++;
    }
}

// Fetch Freight Charges
function getFreightChargesData($conn, $psono, $shortby = null, $shortbyvalue = null)
{
    if ($shortby === "withsono" && !empty($shortbyvalue)) {
        // Fetch by psono + sono
        $sql = "SELECT * FROM krf_freightcharges WHERE psono = ? AND sono = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $psono, $shortbyvalue);
    } else {
        // Default: fetch by psono only
        $sql = "SELECT * FROM krf_freightcharges WHERE psono = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $psono);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
    }
}


// Insert or Update Freight Charges (Enhanced with Vehicle Info & Zero Charges)
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



function getGraftedOrderDetailsWithProgress($conn, $projectid, $shortby = "", $shortbyvalue = "")
{
    // Base SQL
    $sql = "SELECT a.psono, a.stockorder_no, a.productid, a.status, a.quantity, a.stockorder_no, a.clientid, a.expected_delivery_date, p.ProductName, p.IsGrafted, IFNULL(( SELECT SUM(g.plantpertray) FROM krf_graftingdetails_daily g WHERE g.stockorder_no = a.stockorder_no AND g.productid = a.productid ), 0) AS graftinginprogress, (a.quantity - IFNULL(( SELECT SUM(g.plantpertray) FROM krf_graftingdetails_daily g WHERE g.stockorder_no = a.stockorder_no AND g.productid = a.productid ), 0)) AS balance FROM krf_production_stockorder a
        INNER JOIN product_master p ON a.productid = p.ProductID WHERE p.IsGrafted = 'Grafted' AND a.status = 'Confirmed' AND a.projectid = ?";
    // Apply Filtering
    if ($shortby === "psono") {
        $sql .= " AND a.psono = ?";
    } else if ($shortby === "stockorder_no" || $shortby === "stockorderno") {
        $sql .= " AND a.stockorder_no = ?";
    } else if ($shortby === "productid") {
        $sql .= " AND a.productid = ?";
    }
    $sql .= " GROUP BY a.stockorder_no, a.productid";
    // Prepare SQL
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["status" => "error", "message" => $conn->error];
    }
    // Bind parameters dynamically
    if ($shortby === "psono" || $shortby === "productid") {
        $stmt->bind_param("is", $projectid, $shortbyvalue);
    } else {
        $stmt->bind_param("i", $projectid);
    }
    // Execute
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function getProductionStockOrderYears($conn, $projectid)
{
    $sql = "SELECT DISTINCT YEAR(created_at) AS order_year 
            FROM krf_production_stockorder 
            WHERE projectid = ?
            ORDER BY order_year DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $result = $stmt->get_result();

    $years = [];
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['order_year'];
    }

    return $years;
}

function getProductionStockOrderMonths($conn, $projectid, $year)
{
    $sql = "SELECT DISTINCT MONTH(created_at) AS order_month 
            FROM krf_production_stockorder 
            WHERE projectid = ? AND YEAR(created_at) = ?
            ORDER BY order_month ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ii", $projectid, $year);
    $stmt->execute();
    $result = $stmt->get_result();

    $months = [];
    while ($row = $result->fetch_assoc()) {
        $months[] = $row['order_month'];
    }

    return $months;
}

function getProductionStockOrderYearMonthList($conn, $projectid)
{
    // Unique years from both created_at (order date) and expected_delivery_date (delivery/dispatch date)
    $sqlYears = "SELECT DISTINCT yr FROM (
        SELECT YEAR(created_at) AS yr 
        FROM krf_production_stockorder 
        WHERE projectid = ? 
          AND status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted')
          AND created_at IS NOT NULL AND created_at != '0000-00-00'
        UNION
        SELECT YEAR(expected_delivery_date) AS yr 
        FROM krf_production_stockorder 
        WHERE projectid = ? 
          AND status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted')
          AND expected_delivery_date IS NOT NULL AND expected_delivery_date != '0000-00-00'
    ) AS all_years 
    WHERE yr IS NOT NULL AND yr > 2000 
    ORDER BY yr DESC";

    $stmt = $conn->prepare($sqlYears);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $projectid, $projectid);
    $stmt->execute();
    $resYears = $stmt->get_result();

    $finalList = [];
    $allYears = [];
    while ($r = $resYears->fetch_assoc()) {
        $allYears[] = (int)$r['yr'];
    }
    $stmt->close();

    $monthNames = ["", "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    foreach ($allYears as $year) {
        // Order months (created_at)
        $sqlM1 = "SELECT DISTINCT MONTH(created_at) AS m 
                  FROM krf_production_stockorder 
                  WHERE projectid = ? AND YEAR(created_at) = ? 
                    AND status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted')
                  ORDER BY m ASC";
        $stmtM1 = $conn->prepare($sqlM1);
        $stmtM1->bind_param("ii", $projectid, $year);
        $stmtM1->execute();
        $resM1 = $stmtM1->get_result();
        $orderMonths = [];
        while ($rm = $resM1->fetch_assoc()) {
            $m = (int)$rm['m'];
            if ($m >= 1 && $m <= 12) {
                $orderMonths[] = [
                    "numeric" => $m,
                    "alpha" => $monthNames[$m] . "-" . $year
                ];
            }
        }
        $stmtM1->close();

        // Dispatch months (expected_delivery_date)
        $sqlM2 = "SELECT DISTINCT MONTH(expected_delivery_date) AS m 
                  FROM krf_production_stockorder 
                  WHERE projectid = ? AND YEAR(expected_delivery_date) = ? 
                    AND status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted')
                  ORDER BY m ASC";
        $stmtM2 = $conn->prepare($sqlM2);
        $stmtM2->bind_param("ii", $projectid, $year);
        $stmtM2->execute();
        $resM2 = $stmtM2->get_result();
        $dispatchMonths = [];
        while ($rm = $resM2->fetch_assoc()) {
            $m = (int)$rm['m'];
            if ($m >= 1 && $m <= 12) {
                $dispatchMonths[] = [
                    "numeric" => $m,
                    "alpha" => $monthNames[$m] . "-" . $year
                ];
            }
        }
        $stmtM2->close();

        $finalList[] = [
            "year" => $year,
            "monthlist" => $orderMonths,
            "dispatchmonthlist" => $dispatchMonths
        ];
    }

    return $finalList;
}
function getProductionStockOrdersBetweenDates($conn, $projectid, $datefrom, $dateto, $shortby = null, $shortbyvalue = null)
{
    // Mandatory filter
    $sql = "SELECT * FROM krf_production_stockorder WHERE status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted') and projectid = ? AND DATE(created_at) between ? AND  ?";
    $params = [$projectid, $datefrom, $dateto];
    $types = "iss"; // projectid is integer

    // Add dynamic filter if provided
    if (!empty($shortby) && !empty($shortbyvalue)) {
        // Whitelist allowed columns
        $allowedCols = [
            "id" => "i",
            "stockorder_no" => "s",
            "companyid" => "i",
            "subcompanyid" => "i",
            "projectid" => "i",
            "clientid" => "s",
            "productid" => "s",
            "product_name" => "s",
            "quantity" => "i",
            "expected_delivery_date" => "s",
            "created_by" => "s",
            "created_at" => "s"
        ];

        if (array_key_exists($shortby, $allowedCols)) {
            $sql .= " AND $shortby = ?";
            $params[] = $shortbyvalue;
            $types .= $allowedCols[$shortby];
        }
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    // echo debugQueryProduct($sql, $params, $types);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
    $stmt->close();
}

function getProductionStockOrdersDetails($conn, $projectid, $shortby = null, $shortbyvalue = null)
{
    // Mandatory filter
    $sql = "SELECT * FROM krf_production_stockorder WHERE status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted') and projectid = ?";
    $params = [$projectid];
    $types = "i"; // projectid is integer

    // Add dynamic filter if provided
    if (!empty($shortby) && !empty($shortbyvalue)) {
        // Whitelist allowed columns
        $allowedCols = [
            "id" => "i",
            "stockorder_no" => "s",
            "companyid" => "i",
            "subcompanyid" => "i",
            "projectid" => "i",
            "clientid" => "s",
            "productid" => "s",
            "product_name" => "s",
            "quantity" => "i",
            "expected_delivery_date" => "s",
            "created_by" => "s",
            "created_at" => "s"
        ];

        if (array_key_exists($shortby, $allowedCols)) {
            $sql .= " AND $shortby = ?";
            $params[] = $shortbyvalue;
            $types .= $allowedCols[$shortby];
        }
    }

    $sql .= " ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    // echo debugQueryProduct($sql, $params, $types);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
    $stmt->close();
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



// function calculateSowingDate($startDate, $fullCycleDays, $seasonalCorrectionJson)
// {
//     $seasonalDays = json_decode($seasonalCorrectionJson, true);
//     if (!is_array($seasonalDays)) {
//         $seasonalDays = [];
//     }
//     $season = getSeasonFromDate($startDate);
//     $correctionDays = $seasonalDays[$season] ?? 0;
//     $totalDays = (int) $fullCycleDays + (int) $correctionDays;
//     return date('Y-m-d', strtotime("+{$totalDays} days", strtotime($startDate)));
// }



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

function calculateSowingAndInternalDispatchDates($conn, $deliverydate, $projectid, $productid)
{
    $today = date('Y-m-d');
    $result = [
        'status' => 'success',
        'deliverydate' => $deliverydate,
        'dispatchedate_internal' => '',
        'isgrafted' => false,
        'RootSeed' => [],
        'ScionSeed' => []
    ];

    /* 1️⃣ PRODUCT DETAILS */
    $sqlProduct = "SELECT ProductID, ProductName, SeedId AS RootSeedId, ScionSeedId, IsGrafted 
                   FROM product_master 
                   WHERE (ProductID = ? OR sku = ?) AND (ProjectId = ? OR ProjectId = 0 OR ProjectId IS NULL) 
                   ORDER BY ProjectId DESC LIMIT 1";
    $stmt = $conn->prepare($sqlProduct);
    $stmt->bind_param("ssi", $productid, $productid, $projectid);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    if (!$product) {
        return ['status' => 'error', 'message' => 'Product not found'];
    }

    $gVal = $product['IsGrafted'] ?? '';
    $isGrafted = ($gVal === 'Grafted' || $gVal == 1 || strtolower((string)$gVal) === 'yes');
    $result['isgrafted'] = $isGrafted;
    $graftingDays = $isGrafted ? 20 : 0;

    // Calculate Internal Dispatch Date (deliverydate - 20 days if grafted, else deliverydate)
    $result['dispatchedate_internal'] = date('Y-m-d', strtotime($deliverydate . " -{$graftingDays} days"));

    /* 2️⃣ FETCH GROWING DAYS FROM SEED_MASTER PER SEED ID */
    $rootSeedId = $product['RootSeedId'];
    $scionSeedId = !empty($product['ScionSeedId']) ? $product['ScionSeedId'] : $rootSeedId;

    $seedsMap = [
        'RootSeed' => $rootSeedId,
        'ScionSeed' => $scionSeedId
    ];

    $seedData = [];
    $sqlSeed = "SELECT SeedID, VarietyofSeed, SeedName, growingdays FROM seed_master WHERE (SeedID = ? OR sku = ?) LIMIT 1";
    $stmtSeed = $conn->prepare($sqlSeed);

    foreach ([$rootSeedId, $scionSeedId] as $sId) {
        if (!empty($sId) && !isset($seedData[$sId])) {
            $stmtSeed->bind_param("ss", $sId, $sId);
            $stmtSeed->execute();
            $sRes = $stmtSeed->get_result()->fetch_assoc();
            if ($sRes) {
                $seedData[$sId] = [
                    'SeedID' => $sRes['SeedID'],
                    'SeedName' => $sRes['SeedName'],
                    'VarietyofSeed' => $sRes['VarietyofSeed'],
                    'growingdays' => (int) $sRes['growingdays']
                ];
            } else {
                $seedData[$sId] = [
                    'SeedID' => $sId,
                    'SeedName' => '',
                    'VarietyofSeed' => '',
                    'growingdays' => 30
                ];
            }
        }
    }

    /* 3️⃣ SESSIONAL CORRECTION DAYS FROM KRF_GROWTHSTAGES */
    $sqlSession = "SELECT SessionalCorrectionDays FROM krf_growthstages WHERE (productid = ? OR productid = (SELECT ProductID FROM product_master WHERE ProductID = ? OR sku = ? LIMIT 1)) AND (projectid = ? OR projectid = 0 OR projectid IS NULL) LIMIT 1";
    $sessionDaysMap = [];
    if ($stmtSess = $conn->prepare($sqlSession)) {
        $stmtSess->bind_param("sssi", $productid, $productid, $productid, $projectid);
        $stmtSess->execute();
        $sessionRow = $stmtSess->get_result()->fetch_assoc();
        $sessionDaysMap = $sessionRow && !empty($sessionRow['SessionalCorrectionDays']) ? json_decode($sessionRow['SessionalCorrectionDays'], true) : [];
        $stmtSess->close();
    }

    /* 4️⃣ CALCULATE SOWING DATE FOR EACH SEED TYPE */
    foreach ($seedsMap as $type => $seedId) {
        $seedInfo = $seedData[$seedId] ?? ['growingdays' => 30, 'SeedName' => ''];
        $seedGrowingDays = $seedInfo['growingdays'];

        // Formula: expdeliverydate - 20 - seedgrowingdays
        $baseSubtractDays = $graftingDays + $seedGrowingDays;
        $tempSowingDate = date('Y-m-d', strtotime($deliverydate . " -{$baseSubtractDays} days"));

        // Season & Session Correction
        $season = getSeasonFromDate($tempSowingDate);
        $sessionalCorrection = (int) ($sessionDaysMap[$season] ?? 0);

        if ($sessionalCorrection > 0) {
            $tempSowingDate = date('Y-m-d', strtotime($tempSowingDate . " -{$sessionalCorrection} days"));
        }

        // Buffer Days check: if (tempSowingDate - today) > 6 days, subtract 7 days buffer
        $diffFromToday = (strtotime($tempSowingDate) - strtotime($today)) / 86400;
        $bufferDaysApplied = 0;
        if ($diffFromToday > 6) {
            $bufferDaysApplied = 7;
            $finalSowingDate = date('Y-m-d', strtotime($tempSowingDate . " -7 days"));
        } else {
            $finalSowingDate = $tempSowingDate;
        }

        $formulaParts = [];
        if ($graftingDays > 0) $formulaParts[] = "{$graftingDays}d grafting";
        $formulaParts[] = "{$seedGrowingDays}d growing";
        if ($sessionalCorrection > 0) $formulaParts[] = "{$sessionalCorrection}d session";
        if ($bufferDaysApplied > 0) $formulaParts[] = "{$bufferDaysApplied}d buffer";

        $delivDisplay = date('d-m-Y', strtotime($deliverydate));
        $sowDisplay = date('d-m-Y', strtotime($finalSowingDate));
        $formulaStr = "{$delivDisplay} - " . implode(' - ', $formulaParts) . " = {$sowDisplay}";

        $result[$type] = [
            'seedid' => $seedId,
            'seedname' => $seedInfo['SeedName'] ?? '',
            'variety' => $seedInfo['VarietyofSeed'] ?? '',
            'growingdays' => $seedGrowingDays,
            'graftingdays' => $graftingDays,
            'sessiondays' => $sessionalCorrection,
            'bufferdays' => $bufferDaysApplied,
            'difffromtoday' => $diffFromToday,
            'sowingdate' => $finalSowingDate,
            'sowingdate_display' => $sowDisplay,
            'formula' => $formulaStr
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


function getProductionCycleSummary($conn, $productid, $projectid, $bufferdays = 7)
{
    $returnarray = [];

    // 🔹 Validate input
    if (empty($productid) || empty($projectid)) {
        return [];
    }

    /* =====================================
       1️⃣ GET EXPECTED DELIVERY DATE
    ===================================== */
    $rrs = getExpectedDeliveryDatesofProduct($conn, $projectid, $productid, $bufferdays);

    if (!$rrs || empty($rrs['deliverydate'])) {
        return [];
    }

    $expectedDeliveryDate = $rrs['deliverydate'];

    /* =====================================
       2️⃣ GET SOWING DATE
    ===================================== */
    $swgdate = getSowingDatesWithSessionCorrection($conn, $expectedDeliveryDate, $projectid, $productid);

    if (!$swgdate || !isset($swgdate['RootSeedId']['sowingdate'])) {
        return [];
    }

    $sowingdate = $swgdate['RootSeedId']['sowingdate'];

    /* =====================================
       3️⃣ CALCULATE FULL CYCLE DAYS
    ===================================== */
    $fullcycledays =
        (int)($rrs['growingdays'] ?? 0) +
        (int)($rrs['bufferdays'] ?? 0) +
        (int)($rrs['sessiondays'] ?? 0) +
        (int)($rrs['aftergraftingdays'] ?? 0);

    /* =====================================
       4️⃣ RETURN RESULT
    ===================================== */
    $returnarray[] = [
        'productid' => $productid,
        'projectid' => $projectid,
        'expecteddeliverydate' => $expectedDeliveryDate,
        'sowingdate' => $sowingdate,
        'fullcycledays' => $fullcycledays
    ];

    return $returnarray;
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

function getPendingSowingOfPSODataRootyTypewise($conn, $projectid, $sowingdatefrom, $showingdateto)
{
    /* ================= BASE WHERE ================= */

    $baseWhere = " projectid = ? AND status = 'Confirmed' AND (sowingstatus IN ('pending','active','started','start','done') OR scionsowingstatus IN ('pending','active','started' )) AND (tedit IS NULL OR tedit = '')";


    $sql = "
        /* SAME DATE → ONE ROOT ROW */
        SELECT id,companyid,subcompanyid,projectid, clientid, psono, stockorder_no, productid,
               expected_delivery_date, product_name, quantity,
               sowingdate AS sowing_date,
               'Root' AS sowing_type,
               sowingstatus AS sowing_status, sowinglocationid, roottrayid, sciontrayid
        FROM krf_production_stockorder
        WHERE $baseWhere
        AND sowingdate IS NOT NULL
        AND scionsowingdate IS NOT NULL
        AND sowingdate = scionsowingdate
        AND sowingdate>=? && sowingdate <= ?

        UNION ALL

        /* ROOT ROW (Scion missing OR different date) */
        SELECT id,companyid,subcompanyid,projectid, clientid, psono, stockorder_no, productid,
               expected_delivery_date, product_name, quantity,
               sowingdate AS sowing_date,
               'Root' AS sowing_type,
               sowingstatus AS sowing_status, sowinglocationid, roottrayid, sciontrayid
        FROM krf_production_stockorder
        WHERE $baseWhere
        AND sowingdate IS NOT NULL
        AND (scionsowingdate IS NULL OR sowingdate <> scionsowingdate)
        AND sowingdate>=? && sowingdate <= ?

        UNION ALL

        /* SCION ROW (Root missing OR different date) */
        SELECT id,companyid,subcompanyid,projectid, clientid, psono, stockorder_no, productid,
               expected_delivery_date, product_name, quantity,
               scionsowingdate AS sowing_date,
               'Scion' AS sowing_type,
               scionsowingstatus AS sowing_status, sowinglocationid, roottrayid, sciontrayid
        FROM krf_production_stockorder
        WHERE $baseWhere
        AND scionsowingdate IS NOT NULL
        AND (sowingdate IS NULL OR sowingdate <> scionsowingdate)
        AND scionsowingdate>=? && scionsowingdate <= ?

        ORDER BY stockorder_no, sowing_date
    ";

    /* ================= PARAM BINDING ================= */

    $bindParams = [];
    $bindTypes = "";

    /* --- SELECT 1 --- */
    $bindParams[] = $projectid;
    $bindParams[] = $sowingdatefrom;
    $bindParams[] = $showingdateto;
    $bindTypes .= "iss";

    /* --- SELECT 2 --- */
    $bindParams[] = $projectid;
    $bindParams[] = $sowingdatefrom;
    $bindParams[] = $showingdateto;
    $bindTypes .= "iss";

    /* --- SELECT 3 --- */
    $bindParams[] = $projectid;
    $bindParams[] = $sowingdatefrom;
    $bindParams[] = $showingdateto;
    $bindTypes .= "iss";

    /* ================= EXECUTION ================= */

    $stmt = $conn->prepare($sql);
    // echo debugQueryProduct($sql, $bindParams, $bindTypes);
    $stmt->bind_param($bindTypes, ...$bindParams);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

/*function getSowingScheduleDataFromTable($conn, $projectid, $sowingdatefrom, $showingdateto)
{
    $sql = "
        SELECT 
            sch.sowingschedulid,
            sch.projectid,
            sch.stockorder_no,
            sch.psono,
            sch.productid,
            sch.productname AS product_name,
            sch.sowing_date,
            sch.orderqty AS quantity,
            sch.orderqty,
            sch.failurebufferpercent,
            sch.sowingqty,
            sch.locationid AS sowinglocationid,
            sch.roottype AS sowing_type,
            sch.roottype,
            sch.seedid,
            sch.trayid,
            sch.traycavity,
            sch.status AS sowing_status,
            sch.status,
            sch.remarks,
            IFNULL(stk.companyid, '') AS companyid,
            IFNULL(stk.subcompanyid, '') AS subcompanyid,
            IFNULL(stk.clientid, '') AS clientid,
            IFNULL(stk.expected_delivery_date, '') AS expected_delivery_date,
            IFNULL(stk.roottrayid, sch.trayid) AS roottrayid,
            IFNULL(stk.sciontrayid, sch.trayid) AS sciontrayid,
            cl.client_name as clientname
        FROM krf_polyhouse_sowingschedule sch
        LEFT JOIN krf_production_stockorder stk 
            ON stk.projectid = sch.projectid AND stk.stockorder_no = sch.stockorder_no
        left join krf_client_master cl on cl.client_id=stk.clientid     
        WHERE stk.projectid = ?
          AND sch.sowing_date >= ? AND sch.sowing_date <= ?
          AND stk.status IN ('confirmed', 'Confirmed')
         
        GROUP BY sch.sowingschedulid
        ORDER BY sch.sowing_date ASC, sch.stockorder_no ASC
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $pidStr = (string)$projectid;
    $stmt->bind_param(
        "ssss",
        $pidStr,
        $pidStr,
        $sowingdatefrom,
        $showingdateto
    );
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}*/

function getSowingScheduleDataFromTable( $conn,  $authconn,  $projectid,  $sowingdatefrom = '', $showingdateto = '' ) {
    if ($showingdateto === '') {
        // Handle 4-argument call: ($conn, $projectid, $sowingdatefrom, $showingdateto)
        $showingdateto = $sowingdatefrom;
        $sowingdatefrom = $projectid;
        $projectid = $authconn;
        global $authconn;
    }
    $sql = " SELECT  
            sch.sowingschedulid,
            sch.projectid,
            sch.stockorder_no,
            sch.psono,
            sch.productid,
            sch.productname AS product_name,
            sch.sowing_date,
            sch.orderqty AS quantity,
            sch.orderqty,
            sch.failurebufferpercent,
            sch.sowingqty,
            sch.locationid AS sowinglocationid,
            sch.roottype AS sowing_type,
            sch.roottype,
            sch.seedid,
            sch.trayid,
            sch.traycavity,
            sch.status AS sowing_status,
            sch.status,
            sch.remarks,

            IFNULL(stk.companyid, '') AS companyid,
            IFNULL(stk.subcompanyid, '') AS subcompanyid,
            IFNULL(stk.clientid, '') AS clientid,
            IFNULL(stk.expected_delivery_date, '') AS expected_delivery_date,
            IFNULL(stk.roottrayid, sch.trayid) AS roottrayid,
            IFNULL(stk.sciontrayid, sch.trayid) AS sciontrayid,

            CASE
                WHEN stk.clientid = 'C00001' OR sch.psono = sch.stockorder_no OR sch.psono NOT LIKE 'KF-PS%'
                THEN IFNULL(NULLIF(sch.created_by, ''), stk.created_by)
                ELSE IFNULL(NULLIF(pv.created_by, ''), IFNULL(NULLIF(sch.created_by, ''), stk.created_by))
            END AS created_by,
            cl.client_name AS clientname
        FROM krf_polyhouse_sowingschedule sch

        LEFT JOIN krf_production_stockorder stk
            ON stk.projectid = sch.projectid
           AND stk.stockorder_no = sch.stockorder_no
           AND (stk.tedit IS NULL OR stk.tedit = '')

        LEFT JOIN krf_provisional_sales_order pv
            ON pv.projectid = sch.projectid 
           AND pv.psono = CONVERT(IFNULL(NULLIF(sch.psono, ''), stk.psono) USING latin1)
           AND pv.status in ('Draft','Confirmed','Pending Approval','Emergency Sowing')
           AND (pv.tedit IS NULL OR pv.tedit = '')

        LEFT JOIN krf_client_master cl
            ON cl.client_id = CONVERT(stk.clientid USING latin1)

        WHERE sch.projectid = ?
          AND stk.projectid = ?
          AND sch.sowing_date >= ?
          AND sch.sowing_date <= ?
          AND stk.status IN ('confirmed', 'Confirmed','done', 'edited') 
          AND sch.status IN('pending','inprogress','done', 'edited')
          AND (sch.tedit IS NULL OR sch.tedit = '')

        GROUP BY sch.sowingschedulid

        ORDER BY sch.sowing_date ASC,
                 sch.stockorder_no ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    $pidStr = (string)$projectid;
    $stmt->bind_param(
        "ssss",
        $pidStr,
        $pidStr,
        $sowingdatefrom,
        $showingdateto
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();


    /*
     * -------------------------------------
     * Get login users from MASTER connection
     * -------------------------------------
     */
    $loginMap = [];

    $loginSql = "SELECT sno, loginid, loginname, mobileno FROM login_user_master";
    $loginResult = $authconn->query($loginSql);
    if ($loginResult) {
        while ($loginRow = $loginResult->fetch_assoc()) {
            $name = trim($loginRow['loginname'] ?? '');
            if (!empty($loginRow['loginid'])) {
                $loginMap[strtolower(trim($loginRow['loginid']))] = $name;
                $loginMap[trim($loginRow['loginid'])] = $name;
            }
            if (!empty($loginRow['mobileno'])) {
                $loginMap[strtolower(trim($loginRow['mobileno']))] = $name;
                $loginMap[trim($loginRow['mobileno'])] = $name;
            }
            if (!empty($loginRow['sno'])) {
                $loginMap[(string)$loginRow['sno']] = $name;
            }
            if (!empty($name)) {
                $loginMap[strtolower($name)] = $name;
            }
        }
    }


    /*
     * -------------------------------------
     * Add creator login name from krf_provisional_sales_order
     * -------------------------------------
     */
    foreach ($data as &$row) {
        $createdBy = trim((string)($row['created_by'] ?? ''));
        $row['createdbyname'] = $loginMap[strtolower($createdBy)] ?? ($loginMap[$createdBy] ?? $createdBy);
    }

    unset($row);

    return $data;
}

function getUniqueSowingDates($conn, $projectid)
{
    $sql = "SELECT sowingdate, scionsowingdate, sowinglocationid, roottrayid, sciontrayid
            FROM krf_production_stockorder
            WHERE projectid = ?
              AND status = 'Confirmed'
              AND (sowingstatus IS NULL 
                   OR sowingstatus = '' 
                   OR sowingstatus IN ('pending', 'active', 'started','start','done'))
              AND (sowingdate IS NOT NULL OR scionsowingdate IS NOT NULL)
              AND (tedit IS NULL OR tedit = '')
            ORDER BY sowingdate ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }

    $stmt->bind_param("s", $projectid);
    $stmt->execute();
    $result = $stmt->get_result();

    $uniqueDates = []; // associative array to avoid duplicates

    while ($row = $result->fetch_assoc()) {

        $rootDate = $row['sowingdate'];
        $scionDate = $row['scionsowingdate'];

        // Case 1️⃣: Both dates exist and are SAME → add once
        if (!empty($rootDate) && $rootDate === $scionDate) {
            $uniqueDates[$rootDate] = true;
        }
        // Case 2️⃣: Dates are DIFFERENT → add both
        else {
            if (!empty($rootDate)) {
                $uniqueDates[$rootDate] = true;
            }
            if (!empty($scionDate)) {
                $uniqueDates[$scionDate] = true;
            }
        }
    }

    $stmt->close();

    // Build final formatted array
    $dates = [];
    foreach (array_keys($uniqueDates) as $date) {
        $dates[] = [
            "frsowingdate" => $date, // YYYY-mm-dd
            "rdsowingdate" => date("d-M-Y", strtotime($date))
        ];
    }

    // Sort final dates ascending
    usort($dates, function ($a, $b) {
        return strtotime($a['frsowingdate']) <=> strtotime($b['frsowingdate']);
    });

    return $dates;
}


function GetPendingAllocationGerminationdates($conn, $projectid)
{
    $sql = "SELECT boxopeningdate, COUNT(*) as pending_count FROM krf_polyhouse_germinated_boxes WHERE projectid = ? AND status IN ('PendingAllocation','ReceivedAfterSowing') AND boxopeningdate IS NOT NULL AND (tedit IS NULL OR tedit = '') GROUP BY boxopeningdate ORDER BY boxopeningdate DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("SQL Prepare Error: " . $conn->error);
    }
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $dates = [];
    while ($row = $result->fetch_assoc()) {
        $dbDate = $row['boxopeningdate']; // YYYY-mm-dd format
        // Convert to readable format
        $formatted = date("d-M-Y", strtotime($dbDate));
        $dates[] = [
            "fropendate" => $dbDate,     // YYYY-mm-dd
            "rdopendate" => $formatted,  // d-M-YYYY
            "pending_count" => (int)($row['pending_count'] ?? 0)
        ];
    }
    $stmt->close();
    return $dates;
}

function getFullProductionOrderDetailsWithSowingDetails($conn, $projectid, $datefrom, $dateto, $shortby = null, $shortbyvalue = null)
{

    // Allowed, safe columns for filtering
    $allowedCols = [
        "stockorder_no" => "s",
        "clientid" => "s",
        "productid" => "s",
        "product_name" => "s",
        "status" => "s",
        "companyid" => "i",
        "subcompanyid" => "i",
        "quantity" => "i"
    ];

    $sql = "
        SELECT 
            pso.id,
            pso.stockorder_no,
            pso.psono,
            pso.projectid,
            pso.companyid,
            pso.subcompanyid,
            pso.clientid,
            pso.productid,
            pso.product_name,
            pso.quantity,
            pso.sowingdate,
            pso.scionsowingdate,
            pso.expected_delivery_date,
            pso.remarks,
            pso.status,
            pso.created_by,
            pso.created_at,
            pso.sowinglocationid,
            pso.roottrayid,
            pso.sciontrayid,
            gs.SeedId,
            gs.ScionSeedId,
            gs.IsGrafted As producttype,
            COALESCE(
                CASE WHEN gs.IsGrafted = 'Grafted' AND psub_scion.SubCategoryName IS NOT NULL AND psub_scion.SubCategoryName != 'Rootstock' THEN psub_scion.SubCategoryName 
                     WHEN psub_root.SubCategoryName IS NOT NULL AND psub_root.SubCategoryName != 'Rootstock' THEN psub_root.SubCategoryName
                     ELSE NULL
                END,
                TRIM(SUBSTRING_INDEX(SUBSTRING_INDEX(pso.product_name, ',', 1), '-', -1))
            ) AS crop_subcategory,
            cm.client_name,
            cm.invoicing_address,
            cm.phones AS contact_numbers,
            cm.client_email,
            cm.is_trader,
            (SELECT COALESCE(SUM(consumed_qty), 0) FROM krf_productionorder_consumed WHERE stockorderno = pso.stockorder_no AND projectid = pso.projectid AND IFNULL(tedit, '') = '') as total_allocated_qty
        FROM krf_production_stockorder pso

        LEFT JOIN product_master gs 
            ON gs.ProductID = pso.productid 
            AND gs.projectid = pso.projectid

        LEFT JOIN seed_master sm_root
            ON sm_root.SeedID = gs.SeedId
            AND sm_root.ProjectId = pso.projectid

        LEFT JOIN plant_subcategory_master psub_root
            ON psub_root.SubCategoryID = sm_root.SubCategoryID
            AND psub_root.ProjectId = pso.projectid

        LEFT JOIN seed_master sm_scion
            ON sm_scion.SeedID = gs.ScionSeedId
            AND sm_scion.ProjectId = pso.projectid

        LEFT JOIN plant_subcategory_master psub_scion
            ON psub_scion.SubCategoryID = sm_scion.SubCategoryID
            AND psub_scion.ProjectId = pso.projectid

        LEFT JOIN krf_client_master cm
            ON cm.client_id = pso.clientid
            AND cm.ProjectId = pso.projectid

        WHERE pso.status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted') and pso.projectid = ?
          AND (
            (pso.created_at IS NOT NULL AND pso.created_at != '0000-00-00' AND DATE(pso.created_at) BETWEEN ? AND ?)
            OR
            (pso.expected_delivery_date IS NOT NULL AND pso.expected_delivery_date != '0000-00-00' AND DATE(pso.expected_delivery_date) BETWEEN ? AND ?)
          )
    ";

    // Params
    $params = [$projectid, $datefrom, $dateto, $datefrom, $dateto];
    $types = "issss";  // int, string, string, string, string

    // Apply optional filter
    if (!empty($shortby) && array_key_exists($shortby, $allowedCols)) {
        $sql .= " AND pso.$shortby = ? ";
        $params[] = $shortbyvalue;
        $types .= $allowedCols[$shortby];
    }

    $sql .= " ORDER BY pso.expected_delivery_date ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["error" => "Prepare failed: " . $conn->error];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];

    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
}
function getProductionStockAvailbilityAfterDate($conn, $projectid, $productid, $befordelevirydate)
{
    // Mandatory filter
    $sql = "SELECT productid,product_name,sum(quantity) as quantity, expected_delivery_date FROM krf_production_stockorder WHERE status NOT IN('Cancelled', 'removed', 'remove', 'delete', 'deleted') and projectid = ? AND DATE(expected_delivery_date) >= ?";
    $params = [$projectid, $befordelevirydate];
    $types = "iss"; // projectid is integer

    // Add dynamic filter if provided
    if (!empty($shortby) && !empty($shortbyvalue)) {
        // Whitelist allowed columns
        $allowedCols = [
            "id" => "i",
            "stockorder_no" => "s",
            "companyid" => "i",
            "subcompanyid" => "i",
            "projectid" => "i",
            "clientid" => "s",
            "productid" => "s",
            "product_name" => "s",
            "quantity" => "i",
            "expected_delivery_date" => "s",
            "created_by" => "s",
            "created_at" => "s"
        ];
    }

    $sql .= " ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);
    // echo debugQueryProduct($sql, $params, $types);
    $stmt->execute();
    $result = $stmt->get_result();

    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    return $orders;
    $stmt->close();
}

function getRootstockMasterData($conn, $projectid, $shortby = null, $shortbyvalue = null)
{
    $data = [];
    $conn->query("SET NAMES 'utf8mb4'");
    $sql = "SELECT * FROM krf_rootstock_master WHERE projectid = ?";
    $types = "i";
    $params = [$projectid];

    // Add extra filter if provided
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $sql .= " AND $shortby = ?";
        $types .= "s";
        $params[] = $shortbyvalue;
    }

    $sql .= " ORDER BY rootstock_name ASC";

    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        // echo debugQueryProduct($sql, $params, $types);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }

    return $data;
}

function insertRootstockMasterData($conn, $data)
{
    //     $newData = [
    //     "projectid" => "1",
    //     "rootstock_sku" => "RS001",
    //     "rootstock_name" => "Local Mango Seedling",
    //     "scientific_name" => "Mangifera indica",
    //     "rootstock_type" => "Seedling",
    //     "growth_vigor" => "Medium",
    //     "disease_resistance" => "Good tolerance to wilt",
    //     "climate_suitability" => "Tropical",
    //     "soil_preference" => "Loamy",
    //     "water_requirement" => "Medium",
    //     "propagation_method" => "Seed",
    //     "availability_status" => "Available",
    //     "supplier_name" => "Karjee Farm",
    //     "supplier_contact" => "9876543210",
    //     "compatible_scions" => "Alphonso, Dasheri",
    //     "planting_distance" => "10x10 ft",
    //     "expected_yield_influence" => "Improves fruit size",
    //     "lifespan" => 25,
    //     "remarks" => "Best for grafting",
    //     "created_by" => "Admin"
    // ];
    // First check for duplicate name
    $sql = "SELECT rootstock_id FROM krf_rootstock_master WHERE projectid = ? AND rootstock_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $data['projectid'], $data['rootstock_name']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ["status" => "error", "message" => "Rootstock name already exists in this project."];
    }
    $stmt->close();

    // Insert new record
    $sql = "INSERT INTO krf_rootstock_master 
            (projectid, rootstock_sku, rootstock_name, scientific_name, rootstock_type, growth_vigor, 
             disease_resistance, climate_suitability, soil_preference, water_requirement, 
             propagation_method, availability_status, supplier_name, supplier_contact, 
             compatible_scions, planting_distance, expected_yield_influence, lifespan, remarks, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssssssssssss",
        $data['projectid'],
        $data['rootstock_sku'],
        $data['rootstock_name'],
        $data['scientific_name'],
        $data['rootstock_type'],
        $data['growth_vigor'],
        $data['disease_resistance'],
        $data['climate_suitability'],
        $data['soil_preference'],
        $data['water_requirement'],
        $data['propagation_method'],
        $data['availability_status'],
        $data['supplier_name'],
        $data['supplier_contact'],
        $data['compatible_scions'],
        $data['planting_distance'],
        $data['expected_yield_influence'],
        $data['lifespan'],
        $data['remarks'],
        $data['created_by']
    );

    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        return ["status" => "success", "message" => "Rootstock inserted successfully.", "id" => $id];
    } else {
        $err = $stmt->error;
        $stmt->close();
        return ["status" => "error", "message" => $err];
    }
}

function updateRootstockMasterData($conn, $rootstock_id, $data)
{
    //     $newData = [
    //     "projectid" => "1",
    //     "rootstock_sku" => "RS001",
    //     "rootstock_name" => "Local Mango Seedling",
    //     "scientific_name" => "Mangifera indica",
    //     "rootstock_type" => "Seedling",
    //     "growth_vigor" => "Medium",
    //     "disease_resistance" => "Good tolerance to wilt",
    //     "climate_suitability" => "Tropical",
    //     "soil_preference" => "Loamy",
    //     "water_requirement" => "Medium",
    //     "propagation_method" => "Seed",
    //     "availability_status" => "Available",
    //     "supplier_name" => "Karjee Farm",
    //     "supplier_contact" => "9876543210",
    //     "compatible_scions" => "Alphonso, Dasheri",
    //     "planting_distance" => "10x10 ft",
    //     "expected_yield_influence" => "Improves fruit size",
    //     "lifespan" => 25,
    //     "remarks" => "Best for grafting",
    //     "created_by" => "Admin"
    // ];
    // First check for duplicate name (exclude current id)
    $sql = "SELECT rootstock_id FROM krf_rootstock_master 
            WHERE projectid = ? AND rootstock_name = ? AND rootstock_id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $data['projectid'], $data['rootstock_name'], $rootstock_id);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        return ["status" => "error", "message" => "Rootstock name already exists in this project."];
    }
    $stmt->close();

    // Update record
    $sql = "UPDATE krf_rootstock_master SET 
        projectid = ?, rootstock_sku = ?, rootstock_name = ?, scientific_name = ?, rootstock_type = ?, 
        growth_vigor = ?, disease_resistance = ?, climate_suitability = ?, soil_preference = ?, water_requirement = ?, 
        propagation_method = ?, availability_status = ?, supplier_name = ?, supplier_contact = ?, 
        compatible_scions = ?, planting_distance = ?, expected_yield_influence = ?, lifespan = ?, remarks = ?, created_by = ?
        WHERE rootstock_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssssssssssssi",
        $data['projectid'],
        $data['rootstock_sku'],
        $data['rootstock_name'],
        $data['scientific_name'],
        $data['rootstock_type'],
        $data['growth_vigor'],
        $data['disease_resistance'],
        $data['climate_suitability'],
        $data['soil_preference'],
        $data['water_requirement'],
        $data['propagation_method'],
        $data['availability_status'],
        $data['supplier_name'],
        $data['supplier_contact'],
        $data['compatible_scions'],
        $data['planting_distance'],
        $data['expected_yield_influence'],
        $data['lifespan'],
        $data['remarks'],
        $data['created_by'],
        $rootstock_id
    );

    if ($stmt->execute()) {
        $stmt->close();
        return ["status" => "success", "message" => "Rootstock updated successfully."];
    } else {
        $err = $stmt->error;
        $stmt->close();
        return ["status" => "error", "message" => $err];
    }
}

function generateRootStockSKU($conn, $ProjectId, $RootStockName)
{
    // Take first 3 letters of RootStockName in uppercase
    $prefix = strtoupper(substr(preg_replace('/\s+/', '', $RootStockName), 0, 3));

    // Get current max SKU number for this project + prefix
    $sql = "SELECT rootstock_sku 
            FROM krf_rootstock_master 
            WHERE ProjectId = ? AND rootstock_sku LIKE CONCAT(?, '-%')
            ORDER BY rootstock_id DESC 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ProjectId, $prefix);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastSku = $result->fetch_assoc()['rootstock_sku'] ?? null;
    $stmt->close();

    // Determine next number
    if ($lastSku) {
        // Extract number part after dash
        $parts = explode("-", $lastSku);
        $lastNumber = isset($parts[1]) ? (int) $parts[1] : 0;
        $nextNumber = $lastNumber + 1;
    } else {
        $nextNumber = 1;
    }

    // Format number as 3 digits
    $skuNumber = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

    return $prefix . "-" . $skuNumber;
}


// function saveRootStockOrder and getRootStockOrders have been removed as part of the ROOT STOCK ORDERS decomposition.


function fetchGraftingDetailsDaily($krfconn, $projectid, $datefrom, $dateto, $shortby = null, $shortbyvalue = null)
{
    $response = [];
    try {
        $sql = "SELECT * FROM krf_graftingdetails_daily WHERE 1=1";
        $params = [];
        $types = "";
        // ? Filter by project
        if (!empty($projectid)) {
            $sql .= " AND projectid = ?";
            $params[] = $projectid;
            $types .= "s";
        }

        // ? Filter by date range
        if (!empty($datefrom) && !empty($dateto)) {
            $sql .= " AND graftingdate BETWEEN ? AND ?";
            $params[] = $datefrom;
            $params[] = $dateto;
            $types .= "ss";
        }

        // ? Handle shortby filters
        if (!empty($shortby)) {
            switch ($shortby) {
                case "id":
                    $sql .= " AND id = ?";
                    $params[] = $shortbyvalue;
                    $types .= "i";
                    break;

                case "labour":
                    $sql .= " AND labourid = ?";
                    $params[] = $shortbyvalue;
                    $types .= "s";
                    break;

                case "product":
                    $sql .= " AND productid = ?";
                    $params[] = $shortbyvalue;
                    $types .= "s";
                    break;

                case "pendingforsubmission":
                    $sql .= " AND grafted_traycount = 0";
                    break;

                case "pendingforsubmissionwithlocationid":
                    $sql .= " AND locationid = ?";
                    $params[] = $shortbyvalue;
                    $types .= "s";
                    break;
            }
        }

        $sql .= " ORDER BY graftingdate DESC, id DESC";

        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare Error: " . $krfconn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $response[] = $row;
        }

        $stmt->close();
        return $response;

    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}

/*function saveGraftingTargetRange($krfconn, $hrmconn, $companyid, $subcompanyid, $projectid, $productid, $product_sku, $locationid, $start_date, $end_date, $trayid, $plantcountpertray, $target_traycount, $remarks, $created_by)
{
    $response = ["status" => "error", "message" => "Something went wrong"];
    $messages = [];
    try {
        // Prepare date range
        if (empty($end_date) || $end_date < $start_date) {
            $end_date = $start_date; // default single-day
        }

        $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            (new DateTime($end_date))->modify('+1 day')
        );

        // Get location list
        $locations = [];
        if ($locationid === "all") {
            $locations = productionunitlocationdetails($hrmconn, $companyid, $subcompanyid, $projectid, "all", "all");
        } else {
            $locations[] = ["LocationId" => $locationid]; // wrap in array
        }
        // print_r($locations);

        foreach ($locations as $loc) {
            $locId = $loc['LocationId'];
            foreach ($period as $date) {
                $target_date = $date->format("Y-m-d");
                // Check if already exists
                $checkSql = "SELECT id FROM krf_grafting_target_daily WHERE productid = ? AND target_date = ? AND locationid = ?";
                $stmt = $krfconn->prepare($checkSql);
                $stmt->bind_param("sss", $productid, $target_date, $locId);
                $stmt->execute();
                $result = $stmt->get_result();
                $targetplants = $plantcountpertray * $target_traycount;
                if ($result->num_rows > 0) {
                    // Update
                    $row = $result->fetch_assoc();
                    $updateSql = "UPDATE krf_grafting_target_daily 
                                  SET trayid = ?, target_plants = ?, plantpertray = ?, target_traycount = ?, remarks = ?, updated_at = NOW(), projectid=?, product_sku=?, locationid=?
                                  WHERE id = ?";
                    $updateStmt = $krfconn->prepare($updateSql);
                    $updateStmt->bind_param("siiissssi", $trayid, $targetplants, $plantcountpertray, $target_traycount, $remarks, $projectid, $product_sku, $locId, $row['id']);
                    $updateStmt->execute();

                    // $messages[] = "Updated target for $target_date (Location: $locId)";
                    $messages = "Updated grafting target successfully ";
                } else {
                    // Insert
                    $insertSql = "INSERT INTO krf_grafting_target_daily 
                                  (projectid, productid, product_sku, locationid, target_date, trayid, target_plants, plantpertray, target_traycount, remarks, created_by) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $insertStmt = $krfconn->prepare($insertSql);
                    $insertStmt->bind_param("ssssssiiiss", $projectid, $productid, $product_sku, $locId, $target_date, $trayid, $targetplants, $plantcountpertray, $target_traycount, $remarks, $created_by);
                    $insertStmt->execute();

                    // $messages[] = "Inserted target for $target_date (Location: $locId)";
                    $messages = "Inserted grafteing target successfully";
                }
            }
        }

        $response = ["status" => "success", "message" => $messages];

    } catch (Exception $e) {
        $response = ["status" => "error", "message" => $e->getMessage()];
    }

    return json_encode($response);
}*/

function saveGraftingTargetRange($krfconn, $hrmconn, $companyid, $subcompanyid, $projectid, $productid, $product_sku, $locationid, $start_date, $end_date, $trayid, $plantcountpertray, $target_traycount, $remarks, $created_by)
{
    $response = ["status" => "error", "message" => "Something went wrong"];
    $messages = []; 
    try {
        // Prepare date range
        if (empty($end_date) || $end_date <= $start_date) {
            $end_date = $start_date; // default single-day
        }
       $period = new DatePeriod(
            new DateTime($start_date),
            new DateInterval('P1D'),
            (new DateTime($end_date))->modify('+1 day')
        );
        // Get location list
        $locations = [];
        if ($locationid === "all") {
            $locations = productionunitlocationdetails($hrmconn, $companyid, $subcompanyid, $projectid, "all", "all");
        } else {
            $locations[] = ["LocationId" => $locationid]; // wrap in array
        }
        // print_r($locations);

        foreach ($locations as $loc) {
            $locId = $loc['LocationId'];
            foreach ($period as $date) {
                $target_date = $date->format("Y-m-d");
                // Check if already exists
                $checkSql = "SELECT id FROM krf_grafting_target_daily WHERE target_date = ? AND locationid = ?";
                $stmt = $krfconn->prepare($checkSql);
                $stmt->bind_param("ss",  $target_date, $locId);
                $stmt->execute();
                $result = $stmt->get_result();
                $targetplants = $plantcountpertray * $target_traycount;
                if ($result->num_rows > 0) {
                    // Update
                    $row = $result->fetch_assoc();
                    $updateSql = "UPDATE krf_grafting_target_daily 
                                  SET  target_traycount = ?, remarks = ?, updated_at = NOW(), projectid=?,  locationid=? ,update_by =?
                                  WHERE id = ?";
                    $updateStmt = $krfconn->prepare($updateSql);
                    $updateStmt->bind_param("isissi",  $target_traycount, $remarks, $projectid,   $locId,$created_by, $row['id']);
                    $updateStmt->execute();

                    // $messages[] = "Updated target for $target_date (Location: $locId)";
                    $messages = "Updated grafting target successfully ";
                } else {
                    // Insert
                      $insertSql = "INSERT INTO krf_grafting_target_daily 
                                  (projectid,   locationid, target_date,   target_traycount, remarks, created_by) 
                                  VALUES (?,?,?,?,?,?)";
                    $insertStmt = $krfconn->prepare($insertSql);
                    $insertStmt->bind_param("ississ", $projectid,   $locId, $target_date,  $target_traycount, $remarks, $created_by);
                    $insertStmt->execute();

                    // $messages[] = "Inserted target for $target_date (Location: $locId)";
                    $messages = "Inserted grafteing target successfully";
                }
            }
        }

        $response = ["status" => "success", "message" => $messages];

    } catch (Exception $e) {
        $response = ["status" => "error", "message" => $e->getMessage()];
    }

    return json_encode($response);
}

function productionunitlocationdetails($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
    $data = [];
    try {
        if ($shortby === "LocationId" && !empty($shortbyvalue) && $shortbyvalue !== "all") {
            $sql = "SELECT sno, LocationId, CompanyID, SubCompanyID, ProjectID, LocationName, LocationAddress, Comments, created_at FROM hrm_AttendanceLocationMaster WHERE LocationId = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $shortbyvalue);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $stmt->close();
            }
            return $data;
        }

        // Base query
        $sql = "SELECT sno, LocationId, CompanyID, SubCompanyID, ProjectID, LocationName, LocationAddress, Comments, created_at FROM hrm_AttendanceLocationMaster WHERE CompanyID = ? AND SubCompanyID = ? AND ProjectID = ?";
        // Add condition if shortby is not 'all'
        if ($shortby !== "all" && $shortbyvalue !== "all") {
            // Use a whitelist for allowed columns to avoid SQL injection
            $allowedColumns = ["LocationId", "LocationName", "sno"];
            if (in_array($shortby, $allowedColumns)) {
                $sql .= " AND $shortby = ?";
            } else {
                throw new Exception("Invalid shortby column.");
            }
        }
        $stmt = $conn->prepare($sql);
        // Bind params
        if ($shortby !== "all" && $shortbyvalue !== "all") {
            $stmt->bind_param("ssss", $companyid, $subcompanyid, $projectid, $shortbyvalue);
        } else {
            $stmt->bind_param("sss", $companyid, $subcompanyid, $projectid);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error in getAttendanceLocationMaster: " . $e->getMessage());
    }

    return $data;
}

function saveMultipleDailyGraftingSubmissionDetails($krfconn, $hrmconn, $projectid, $locationid, $graftingdate, $submitted_by, $labourData = [])
{
    $response = ["status" => "error", "message" => "No data provided", "saved" => 0];

    if (empty($labourData)) {
        return json_encode($response);
    }
    $savedCount = 0;
    foreach ($labourData as $row) {
        $productid = $row['productid'] ?? null;
        $product_sku = $row['product_sku'] ?? null;
        $labourid = $row['labourid'] ?? null;
        $target_traycount = (int) ($row['target_traycount'] ?? 0);
        $grafted_traycount = (int) ($row['grafted_traycount'] ?? 0);
        $remarks = $row['remarks'] ?? null;
        if ($target_traycount == 0) {
            $sq = $krfconn->query("SELECT IFNULL(target_traycount, 0) AS target_traycount FROM krf_grafting_target_daily WHERE projectid = '" . $projectid . "' AND productid = '" . intval($productid) . "' AND locationid = '" . intval($locationid) . "' AND target_date = '" . $graftingdate . "'");
            $sqrow = $sq->fetch_assoc();
            $target_traycount = $sqrow['target_traycount'];
        }

        if (!$productid || !$labourid) {
            continue; // skip incomplete rows
        }

        // Check if record exists
        $checkSql = "SELECT id FROM krf_graftingdetails_daily WHERE productid = ? AND labourid = ? AND graftingdate = ?";
        $stmt = $krfconn->prepare($checkSql);
        $stmt->bind_param("sss", $productid, $labourid, $graftingdate);
        $stmt->execute();
        $result = $stmt->get_result();
        $nowdatetime = date('Y-m-d H:i:s');
        if ($result->num_rows > 0) {
            // Update existing record
            $rowData = $result->fetch_assoc();
            $updateSql = "UPDATE krf_graftingdetails_daily 
                          SET grafted_traycount = ?, submitted_at = ?, submitted_by=?, remarks = ? 
                          WHERE id = ?";
            $updateStmt = $krfconn->prepare($updateSql);
            $updateStmt->bind_param("issss", $grafted_traycount, $nowdatetime, $submitted_by, $remarks, $rowData['id']);
            if ($updateStmt->execute()) {
                $savedCount++;
            }
        }
    }

    $response = ["status" => "success", "message" => "Saved $savedCount records", "saved" => $savedCount];
    return json_encode($response);
}

function saveMultipleDailyGraftingAllocationDetails($krfconn, $hrmconn, $projectid, $graftingdate, $locationid, $productid, $created_by, $labourData = [])
{
    $response = ["status" => "error", "message" => "No data provided", "saved" => 0];

    // Fetch product details
    $ssq = $krfconn->prepare("SELECT ProductID, CategoryId, ProductTypeID, sku, ProductName 
                              FROM product_master 
                              WHERE ProjectId = ? AND Status = 'Active' LIMIT 1");
    $ssq->bind_param("s", $projectid);
    $ssq->execute();
    $pprow = $ssq->get_result()->fetch_assoc();
    if (!$pprow) {
        $response['message'] = "Product not found";
        return json_encode($response);
    }
    $product_sku = $pprow['sku'];

    if (empty($labourData)) {
        return json_encode($response);
    }

    $savedCount = 0;
    foreach ($labourData as $row) {
        $labourid = $row['labourid'] ?? null;
        $target_traycount = (int) ($row['graftraytetarget'] ?? 0);
        $plantspertray = (int) ($row['plantspertray'] ?? 0);
        $allocationtime = $row['allocationtime'] ?? '';
        $targetplants = (int) ($target_traycount * $plantspertray ?? 0);
        $trayid = $row['trayid'] ?? null;
        $psono = $row['psono'] ?? null;
        $stockorder_no = $row['stockorder_no'] ?? null;
        $grafted_traycount = 0;
        $remarks = '';
        if ($target_traycount == 0) {
            // Fetch target from krf_grafting_target_daily safely
            $sq = $krfconn->prepare("SELECT IFNULL(target_traycount, 0) AS target_traycount 
                                     FROM krf_grafting_target_daily 
                                     WHERE projectid = ? AND productid = ? AND locationid = ? AND target_date = ?");
            $sq->bind_param("ssss", $projectid, $productid, $locationid, $graftingdate);
            $sq->execute();
            $sqrow = $sq->get_result()->fetch_assoc();
            $target_traycount = $sqrow['target_traycount'] ?? 0;
            $sq->close();
        }

        if (!$productid || !$labourid) {
            continue; // skip incomplete rows
        }

        // Check if record exists
        $checkSql = "SELECT id FROM krf_graftingdetails_daily WHERE productid = ? AND labourid = ? AND graftingdate = ?";
        $stmt = $krfconn->prepare($checkSql);
        $stmt->bind_param("sss", $productid, $labourid, $graftingdate);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing record
            $rowData = $result->fetch_assoc();
            $updateSql = "UPDATE krf_graftingdetails_daily 
                          SET target_traycount = ?, plantpertray=?, target_plants=?, allocationtime = ?, grafted_traycount = ?, remarks = ?, updated_at = NOW(), projectid = ?, product_sku = ?, locationid = ?, trayid = ?, psono = ?, stockorder_no 
                          WHERE id = ?";
            $updateStmt = $krfconn->prepare($updateSql);
            $updateStmt->bind_param("iiisisssssssi", $target_traycount, $plantspertray, $targetplants, $allocationtime, $grafted_traycount, $remarks, $projectid, $product_sku, $locationid, $trayid, $psono, $stockorder_no, $rowData['id']);
            if ($updateStmt->execute()) {
                $savedCount++;
            }
            $updateStmt->close();
        } else {
            // Insert new record
            // $insertSql = "INSERT INTO krf_graftingdetails_daily 
            //               (projectid, productid, product_sku, labourid, graftingdate, allocationtime, plantpertray, target_plants, target_traycount, grafted_traycount, remarks, created_by, locationid,trayid, psono, stockorder_no) 
            //               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            // $insertStmt = $krfconn->prepare($insertSql);
            // $insertStmt->bind_param("ssssssiiiissssss", $projectid, $productid, $product_sku, $labourid, $graftingdate, $allocationtime, $plantspertray, $targetplants, $target_traycount, $grafted_traycount, $remarks, $created_by, $locationid, $trayid, $psono, $stockorder_no);
            // if ($insertStmt->execute()) {
            //     $graftingid = $insertStmt->insert_id;
            //     $polyhouseid='';
            //     $batchno='';
            //     $productName='';
            //     $source_type = "Graft";
            //     $graftingDate=date('Y-m-d');
            //    $pps = insertPolyhousePlantDetails($krfconn, $graftingid, $labourid, $polyhouseid, $psono, $stockorder_no, $locationid, $projectid, $batchno, $productid, $productName, $source_type, $trayid, $created_by, $target_traycount, $graftingDate);
            //    $savedCount++;
            // }
            // $insertStmt->close();

            $insertSql = "INSERT INTO krf_graftingdetails_daily 
                (projectid, productid, product_sku, labourid, graftingdate, allocationtime, plantpertray,
                 target_plants, target_traycount, grafted_traycount, remarks, created_by, locationid, trayid,
                 psono, stockorder_no) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $insertStmt = $krfconn->prepare($insertSql);

            // Corrected bind_param types
            // i = integer, s = string
            $insertStmt->bind_param(
                "ssssssiiiissssss",
                $projectid,
                $productid,
                $product_sku,
                $labourid,
                $graftingdate,
                $allocationtime,
                $plantspertray,
                $targetplants,
                $target_traycount,
                $grafted_traycount,
                $remarks,
                $created_by,
                $locationid,
                $trayid,
                $psono,
                $stockorder_no
            );

            if ($insertStmt->execute()) {

                $graftingid = $insertStmt->insert_id;

                // Values passed to polyhouse function
                $polyhouseid = 0;
                $batchno = '';
                $productName = '';
                $source_type = 'Graft';
                $graftingDate = date('Y-m-d'); // today’s date

                // Call the function
                $pps = insertPolyhousePlantDetails(
                    $krfconn,
                    $graftingid,
                    $labourid,
                    $polyhouseid,
                    $psono,
                    $stockorder_no,
                    $locationid,
                    $projectid,
                    $batchno,
                    $productid,
                    $productName,
                    $source_type,
                    $trayid,
                    $created_by,
                    $target_traycount,   // grafted tray count
                    $graftingDate
                );
                $savedCount++;
            }

            $insertStmt->close();
        }
        $stmt->close();
    }
    $response = ["status" => "success", "message" => "Saved $savedCount records", "saved" => $savedCount];
    return json_encode($response);
}


function insertPolyhousePlantDetails($krfconn, $graftingid, $labourid, $polyhouseid, $psono, $stockorder_no, $locationid, $projectid, $batchno, $productid, $productName, $source_type, $trayid, $loginid, $graftedcount, $graftingDate)
{
    try {

        $krfconn->begin_transaction();

        // Step 1: Duplicate Check
        $checkSql = "SELECT polyhouseplantid 
                     FROM krf_polyhouse_plant_details 
                     WHERE graftingid = ? 
                       AND labourid = ? 
                       AND productid = ? 
                       AND DATE(created_at) = ? 
                     LIMIT 1";

        $stmtCheck = $krfconn->prepare($checkSql);
        $stmtCheck->bind_param("iiss", $graftingid, $labourid, $productid, $graftingDate);
        $stmtCheck->execute();
        $stmtCheck->store_result();

        if ($stmtCheck->num_rows > 0) {
            $stmtCheck->close();
            $krfconn->rollback();

            return [
                "status" => "ignored",
                "message" => "Data already exists for this grafting date."
            ];
        }
        $stmtCheck->close();


        // Step 2: Insert
        $insertSql = "
            INSERT INTO krf_polyhouse_plant_details
            (graftingid, labourid, polyhouseid, psono, stockorder_no, locationid, projectid, 
             batchno, productid, productname, source_type, tray_id, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $stmtInsert = $krfconn->prepare($insertSql);

        // ?? FIXED bind_param types
        $stmtInsert->bind_param(
            "iiisssisisisi",
            $graftingid,
            $labourid,
            $polyhouseid,
            $psono,
            $stockorder_no,
            $locationid,
            $projectid,
            $batchno,
            $productid,
            $productName,
            $source_type,
            $trayid,
            $loginid
        );

        for ($i = 0; $i < $graftedcount; $i++) {
            if (!$stmtInsert->execute()) {
                throw new Exception("Insert failed: " . $stmtInsert->error);
            }
        }

        $stmtInsert->close();
        $krfconn->commit();

        return [
            "status" => "success",
            "message" => "Polyhouse plant details inserted successfully."
        ];

    } catch (Exception $e) {
        $krfconn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}



function getGraftingTargetForDate($conn, $projectid, $productid, $locationid, $graftingdate)
{
    // Prepare the query safely
    $stmt = $conn->prepare("
        SELECT IFNULL(target_traycount, 0) AS target_traycount, IFNULL(plantpertray, 0) AS plantpertray, IFNULL(target_plants, 0) AS target_plants, trayid 
        FROM krf_grafting_target_daily 
        WHERE projectid = ? 
          AND productid = ? 
          AND locationid = ? 
          AND target_date = ?");
    if (!$stmt) {
        return 0; // or handle error
    }
    // Bind params (string, int, int, string)
    $stmt->bind_param("ssss", $projectid, $productid, $locationid, $graftingdate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}
function getGraftingTargetForDate_ajay($conn, $projectid,  $locationid, $graftingdate)
{
    // Prepare the query safely
    $stmt = $conn->prepare("
        SELECT IFNULL(target_traycount, 0) AS target_traycount, IFNULL(plantpertray, 0) AS plantpertray, IFNULL(target_plants, 0) AS target_plants, trayid 
        FROM krf_grafting_target_daily 
        WHERE projectid = ? 
          AND locationid = ? 
          AND target_date = ?");
    if (!$stmt) {
        return 0; // or handle error
    }
    // Bind params (string, int, int, string)
    $stmt->bind_param("sss", $projectid,  $locationid, $graftingdate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row;
}
 

function checkGraftingExistOnDate($conn, $projectid, $locationid, $productid, $labourid, $graftingdate)
{
    $sql = "SELECT target_traycount, grafted_traycount, graftdifference,plantpertray,target_plants, allocationtime, trayid 
            FROM krf_graftingdetails_daily 
            WHERE projectid = ? 
              AND locationid = ? 
              AND productid = ? 
              AND labourid = ? 
              AND graftingdate = ? 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return false; // query preparation failed
    }

    $stmt->bind_param("sssss", $projectid, $locationid, $productid, $labourid, $graftingdate);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();
    return $row ?: false;  // return full data or false
}

function fetchGraftingTargetDaily($conn, $projectid, $shortby = null, $shortbyvalue = null)
{
    $data = [];

    // Ensure projectid is mandatory
    if (empty($projectid)) {
        return $data;
    }
    $query = "SELECT * FROM krf_grafting_target_daily WHERE projectid = ?";
    $params = [$projectid];
    $types = "s";

    // Apply dynamic filtering
    if (!empty($shortby) && !empty($shortbyvalue)) {
        switch ($shortby) {
            case "id":
            case "productid":
            case "product_sku":
            case "locationid":
            case "target_date":
            case "created_by":
                $query .= " AND $shortby = ?";
                $params[] = $shortbyvalue;
                $types .= "s";
                break;

            case "month_year": // e.g. 2025-10
                $query .= " AND DATE_FORMAT(target_date, '%Y-%m') = ?";
                $params[] = $shortbyvalue;
                $types .= "s";
                break;

            case "year": // e.g. 2025
                $query .= " AND YEAR(target_date) = ?";
                $params[] = $shortbyvalue;
                $types .= "i";
                break;

            default:
                // invalid shortby value, skip filter
                break;
        }
    }

    $query .= " ORDER BY target_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    return $data; // returns an array of associative arrays
}

function getGraftingMonthYearList($conn, $projectid, $year = null)
{
    $monthYearList = [];

    // Ensure projectid is mandatory
    if (empty($projectid)) {
        return $monthYearList;
    }

    // Default to current year if not provided
    if (empty($year)) {
        $year = date('Y');
    }

    $query = "SELECT DISTINCT 
                    DATE_FORMAT(target_date, '%Y-%m') AS month_year_code,
                    DATE_FORMAT(target_date, '%M %Y') AS month_year_label
              FROM krf_grafting_target_daily
              WHERE projectid = ? AND YEAR(target_date) = ?
              ORDER BY target_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $projectid, $year); // 's' for string, 'i' for integer (year)
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $monthYearList[] = [
            'code' => $row['month_year_code'],   // e.g., 2025-10
            'label' => $row['month_year_label']  // e.g., October 2025
        ];
    }

    $stmt->close();

    return $monthYearList; // returns array of associative arrays
}

function getPolyhouseData($krfconn, $projectid, $shortby = null, $shortbyvalue = null)
{
    $data = [];
    try {
        // ?? Base Query
        $sql = "SELECT * FROM krf_polyhouse_master WHERE projectid = ?";

        // ?? Apply optional filters
        $params = [$projectid];
        $types = "s";

        if (!empty($shortby) && !empty($shortbyvalue)) {
            switch ($shortby) {
                case "polyhouseid":
                    $sql .= " AND polyhouseid = ?";
                    $params[] = $shortbyvalue;
                    $types .= "i";
                    break;

                case "locationid":
                    $sql .= " AND locationid = ?";
                    $params[] = $shortbyvalue;
                    $types .= "s";
                    break;

                case "polyhousename":
                    $sql .= " AND polyhousename LIKE ?";
                    $params[] = "%" . $shortbyvalue . "%";
                    $types .= "s";
                    break;

                case "type":
                    $sql .= " AND type = ?";
                    $params[] = $shortbyvalue;
                    $types .= "s";
                    break;

                case "status":
                    $sql .= " AND status = ?";
                    $params[] = $shortbyvalue;
                    $types .= "s";
                    break;

                default:
                    // ignore if shortby key is invalid
                    break;
            }
        }

        $sql .= " ORDER BY polyhouseid DESC";

        // ?? Prepare and execute
        $stmt = $krfconn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Failed to prepare statement: " . $krfconn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (strtolower($row['structure_type']) == "bed") {
                $noofbebsorrack = $row['no_of_beds'];
            } else if (strtolower($row['structure_type']) == "rack") {
                $noofbebsorrack = $row['no_of_racks'];
            } else {
                $noofbebsorrack = 0;
            }
            $data[] = [
                "polyhouseid" => $row["polyhouseid"],
                "locationid" => $row["locationid"],
                "projectid" => $row["projectid"],
                "polyhousename" => $row["polyhousename"],
                "physicallocation" => $row["physicallocation"],
                "area_sqft" => $row["area_sqft"],
                "type" => $row["type"],
                "structure_type" => $row["structure_type"],
                "noofbebsorrack" => $noofbebsorrack,
                "beddetails" => $row["beddetails"],
                "capacity" => $row["capacity"],
                "status" => $row["status"],
                "description" => $row["description"],
                "created_at" => $row["created_at"],
                "created_by" => $row["created_by"],
                "updated_at" => $row["updated_at"]
            ];
        }

        $stmt->close();
    } catch (Exception $e) {
        return [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }

    return $data;
}

function getPolyhousePlantDetails($krfconn, $projectid = null, $shortby = null, $shortbyvalue = null)
{
    try {
        $sql = "SELECT 
                    p.*,
                    ph.polyhousename,
                    ph.structure_type,
                    ph.no_of_beds,
                    ph.no_of_racks,
                    ph.beddetails
                    
                FROM krf_polyhouse_plant_details p
                LEFT JOIN krf_polyhouse_master ph 
                    ON p.polyhouseid = ph.polyhouseid
                WHERE 1=1";

        $params = [];
        $types = "";

        // ? Add filters dynamically
        if (!empty($projectid)) {
            $sql .= " AND p.projectid = ?";
            $params[] = $projectid;
            $types .= "s";
        }

        if ($shortby == "polyhouseplantid" && !empty($shortbyvalue)) {
            $sql .= " AND p.polyhouseplantid = ?";
            $params[] = $shortbyvalue;
            $types .= "i";
        } else if ($shortby == "allhealthyplantswithstockorderno" && !empty($shortbyvalue)) {
            $sql .= " AND health_status='Healthy' AND p.stockorder_no = ?";
            $params[] = $shortbyvalue;
            $types .= "s";
        } else if ($shortby == "labouridwithgraftingdate" && !empty($shortbyvalue)) {

            $shortbyvalueexp = explode("|", $shortbyvalue);
            $labourid = $shortbyvalueexp[0];
            $graftingdate = $shortbyvalueexp[1];

            $sql .= " AND p.labourid = ? AND p.graftingdate = ?";
            $params[] = $labourid;
            $params[] = $graftingdate;
            $types .= "is";

        } elseif ($shortby == "pendingGraftinglabouridwithdate" && !empty($shortbyvalue)) {

            $shortbyvalueexp = explode("|", $shortbyvalue);
            $labourid = $shortbyvalueexp[0];
            $graftingdate = $shortbyvalueexp[1];

            $sql .= " AND p.labourid = ? AND p.graftingdate = ? AND p.status = 'InProcess'";
            $params[] = $labourid;
            $params[] = $graftingdate;
            $types .= "is";

        } elseif (!empty($shortby) && !empty($shortbyvalue)) {

            $sql .= " AND p." . $shortby . " = ?";
            $params[] = $shortbyvalue;
            $types .= "s";
        }

        // ? Prepare statement
        $stmt = $krfconn->prepare($sql);

        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $krfconn->error);
        }

        // ? Bind parameters dynamically
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        // echo debugQueryProduct($sql, $params, $types);
        $stmt->execute();
        $result = $stmt->get_result();

        $plants = [];
        while ($row = $result->fetch_assoc()) {
            $plants[] = $row;
        }

        $stmt->close();
        return $plants;

    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}

function getPolyhouseSourceTypePlanting($krfconn, $projectid = null)
{
    $query = "";
    $params = [];
    $types = "";

    // Build query based on whether projectid is provided
    if ($projectid === null || $projectid === "") {
        $query = "SELECT sourcetypeid, projectid, sourcetype, status, description, created_at, created_by, updated_at 
                  FROM krf_polyhouse_sourcetypeplanting 
                  WHERE projectid IS NULL AND status = 'Active'";
    } else {
        $query = "SELECT sourcetypeid, projectid, sourcetype, status, description, created_at, created_by, updated_at 
                  FROM krf_polyhouse_sourcetypeplanting 
                  WHERE (projectid = ? OR projectid IS NULL) AND status = 'Active'";
        $params[] = $projectid;
        $types .= "s";
    }

    $stmt = $krfconn->prepare($query);
    if (!$stmt) {
        throw new Exception("Error preparing statement: " . $krfconn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function getCultivationTypeMaster($krfconn, $status = null, $cultivation_typeid = null)
{
    try {
        $sql = "SELECT cultivation_typeid, cultivation_typename, description, best_for, example_crops, status 
                FROM krf_polihouse_cultivation_typemaster 
                WHERE 1=1";
        $params = [];
        $types = "";

        // Apply filters dynamically
        if (!empty($status)) {
            $sql .= " AND status = ?";
            $params[] = $status;
            $types .= "s";
        }

        if (!empty($cultivation_typeid)) {
            $sql .= " AND cultivation_typeid = ?";
            $params[] = $cultivation_typeid;
            $types .= "i";
        }

        $sql .= " ORDER BY cultivation_typename ASC";

        $stmt = $krfconn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                "cultivation_typeid" => $row["cultivation_typeid"],
                "cultivation_typename" => $row["cultivation_typename"],
                "description" => $row["description"],
                "best_for" => $row["best_for"],
                "example_crops" => $row["example_crops"],
                "status" => $row["status"]
            ];
        }

        $stmt->close();
        return $data;

    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}

function GraftingDatesList($krfconn, $projectid, $locationid)
{
    $dates = [];
    try {
        $sql = "SELECT DISTINCT DATE(g.graftingdate) AS graftingdate FROM krf_graftingdetails_daily AS g JOIN krf_polyhouse_plant_details AS p ON g.id = p.graftingid AND g.projectid = p.projectid WHERE g.projectid = ? AND g.locationid = ? ORDER BY graftingdate DESC";
        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param("ss", $projectid, $locationid);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $dates[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching grafting dates: " . $e->getMessage());
    }
    return $dates;
}

function getGraftingDatesForMissingBarcode($krfconn, $projectid, $locationid)
{
    $dates = [];
    try {
        $sql = "SELECT DISTINCT DATE(g.submitted_at) AS submitted_date FROM krf_graftingdetails_daily AS g JOIN krf_polyhouse_plant_details AS p ON g.id = p.graftingid AND g.projectid = p.projectid WHERE g.projectid = ? AND g.locationid = ? ORDER BY submitted_date DESC";
        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param("ss", $projectid, $locationid);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $dates[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching grafting dates: " . $e->getMessage());
    }
    return $dates;
}

function GraftedByForListwithlocationandDate($krfconn, $projectid, $locationid, $grafteddate)
{
    $data = [];
    try {
        $sql = "SELECT DISTINCT p.labourid FROM krf_graftingdetails_daily AS g JOIN krf_polyhouse_plant_details AS p ON g.id = p.graftingid 
                    AND g.projectid = p.projectid WHERE g.projectid = ? AND g.locationid = ? AND DATE(g.graftingdate) = ? ORDER BY p.labourid ASC";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $krfconn->error);
        }
        $stmt->bind_param("sss", $projectid, $locationid, $grafteddate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching grafting data: " . $e->getMessage());
        echo "SQL Error: " . $e->getMessage(); // optional: remove in production
    }
    return $data;
}

function GraftedByForBarcode($krfconn, $projectid, $locationid, $grafteddate)
{
    $data = [];
    try {
        $sql = "SELECT DISTINCT p.labourid FROM krf_graftingdetails_daily AS g JOIN krf_polyhouse_plant_details AS p ON g.id = p.graftingid 
                    AND g.projectid = p.projectid WHERE g.projectid = ? AND g.locationid = ? AND DATE(g.graftingdate) = ? ORDER BY p.labourid ASC";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $krfconn->error);
        }
        $stmt->bind_param("sss", $projectid, $locationid, $grafteddate);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching grafting data: " . $e->getMessage());
        echo "SQL Error: " . $e->getMessage(); // optional: remove in production
    }
    return $data;
}

function GraftedBarcodeDetailsGoupByBatchno($krfconn, $projectid, $locationid, $grafteddate, $labourid)
{
    $data = [];
    try {
        $sql = "SELECT g.id,g.productid,g.locationid,g.graftingdate,g.allocationtime,g.target_traycount,g.plantpertray,g.grafted_traycount,g.submitted_at,p.polyhouseplantid,p.labourid,p.polyhouseid,p.batchno,p.barcode,p.tray_id FROM krf_graftingdetails_daily AS g JOIN krf_polyhouse_plant_details AS p ON g.id = p.graftingid 
                    AND g.projectid = p.projectid WHERE g.projectid = ? AND g.locationid = ? AND DATE(g.graftingdate) = ? and p.labourid = ? GROUP BY p.batchno";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $krfconn->error);
        }
        $stmt->bind_param("sssi", $projectid, $locationid, $grafteddate, $labourid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching grafting data: " . $e->getMessage());
        echo "SQL Error: " . $e->getMessage(); // optional: remove in production
    }
    return $data;
}

function GraftedPlantsByLabourForDatewithLocation($krfconn, $projectid, $locationid, $grafteddate, $labourid)
{
    $data = [];
    try {
        $sql = "SELECT g.id,g.productid,g.locationid,g.graftingdate,g.allocationtime,g.target_traycount,g.plantpertray,g.grafted_traycount,g.submitted_at,p.polyhouseplantid,p.labourid,p.polyhouseid,p.batchno,p.barcode,p.tray_id,p.tray_position,p.bed_no,p.rack_no,p.floar_no,p.row_no,p.column_no FROM krf_graftingdetails_daily AS g JOIN krf_polyhouse_plant_details AS p ON g.id = p.graftingid 
                    AND g.projectid = p.projectid WHERE g.projectid = ? AND g.locationid = ? AND DATE(g.graftingdate) = ? and p.labourid = ?";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $krfconn->error);
        }
        $stmt->bind_param("sssi", $projectid, $locationid, $grafteddate, $labourid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        error_log("Error fetching grafting data: " . $e->getMessage());
        echo "SQL Error: " . $e->getMessage(); // optional: remove in production
    }
    return $data;
}

// function getPolyhousePlantQCStatusData($krfconn, $projectid, $shortby = 'all', $shortbyvalue = null, $sowinglotno_internal=null)
// {
//     $data = [];

//     // Allowed columns to filter by — security whitelist
//     $allowedColumns = [
//         'polyhouseid',
//         'polyhouseplantid',
//         'trayid',
//         'barcode',
//         'qc_status',
//         'replaced_any_plant',
//         'replacefrom_trayid',
//         'updated_by'
//     ];

//     try {
//         // Base query
//         $sql = "SELECT 
//                     id, projectid, polyhouseid, polyhouseplantid, trayid, barcode, 
//                     qc_status, replaced_any_plant, replacefrom_polyhouseplantid, 
//                     replacefrom_trayid, replacefrom_tray_barcode, replaced_plantcount, 
//                     remarks, updated_by, updated_on
//                 FROM krf_polyhouse_plantqcstatus_tracking
//                 WHERE projectid = ?";

//         // Add condition if filtering by column
//         if ($shortby !== 'all' && in_array($shortby, $allowedColumns)) {
//             $sql .= " AND $shortby = ?";
//         }


//         $stmt = $krfconn->prepare($sql);

//         if ($shortby !== 'all' && in_array($shortby, $allowedColumns)) {
//             $stmt->bind_param("is", $projectid, $shortbyvalue);
//         } else {
//             $stmt->bind_param("i", $projectid);
//         }

//         $stmt->execute();
//         $result = $stmt->get_result();

//         while ($row = $result->fetch_assoc()) {
//             $data[] = $row;
//         }

//         $stmt->close();
//     } catch (Exception $e) {
//         $data = ['error' => $e->getMessage()];
//     }

//     return $data;
// }

function getPolyhousePlantQCStatusData($krfconn, $projectid, $shortby = 'all', $shortbyvalue = null, $sowinglotno_internal = null)
{
    $data = [];

    // Allowed columns to filter by
    $allowedColumns = [
        'polyhouseid',
        'polyhouseplantid',
        'trayid',
        'barcode',
        'qc_status',
        'replaced_any_plant',
        'replacefrom_trayid',
        'updated_by'
    ];

    try {

        $sql = "SELECT 
                    id,
                    projectid,
                    polyhouseid,
                    polyhouseplantid,
                    trayid,
                    barcode,
                    qc_status,
                    replaced_any_plant,
                    replacefrom_polyhouseplantid,
                    replacefrom_trayid,
                    replacefrom_tray_barcode,
                    replaced_plantcount,
                    remarks,
                    updated_by,
                    updated_on
                FROM krf_polyhouse_plantqcstatus_tracking
                WHERE projectid = ?";

        $types = "i";
        $params = [$projectid];

        // Optional sowing lot filter
        if (!empty($sowinglotno_internal)) {
            $sql .= " AND sowinglotno_internal = ?";
            $types .= "s";
            $params[] = $sowinglotno_internal;
        }

        // Optional column filter
        if ($shortby !== 'all' && in_array($shortby, $allowedColumns)) {
            $sql .= " AND {$shortby} = ?";
            $types .= "s";
            $params[] = $shortbyvalue;
        }

        $stmt = $krfconn->prepare($sql);

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $krfconn->error);
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }

    return $data;
}


function savePolyhousePlantQCStatusTracking($conn, $data)
{
    try {

        // Required fields
        $projectid = (int) $data['projectid'];
        $polyhouseid = $data['polyhouseid'] ?? null;
        $polyhouseplantid = (int) $data['polyhouseplantid'];
        $trayid = (int) $data['trayid'];
        $barcode = $data['barcode'];
        $qc_status = $data['qc_status'];
        $replaced_any_plant = $data['replaced_any_plant'] ?? 'No';
        $replacefrom_polyhouseplantid = $data['replacefrom_polyhouseplantid'] ?? null;
        $replacefrom_trayid = $data['replacefrom_trayid'] ?? null;
        $replacefrom_tray_barcode = $data['replacefrom_tray_barcode'] ?? null;
        $replaced_plantcount = (int) ($data['replaced_plantcount'] ?? 0);
        $remarks = $data['qcremarks'] ?? null;
        $updated_by = $data['updated_by'];
        $statustrackingid = (int) ($data['statustrackingid'] ?? 0);

        $status = ($qc_status === "ReadyToSale") ? "ReadyToSale" : "Polyhouse";

        /* ---------------------------
           CHECK EXISTING RECORD
        --------------------------- */
        $checkSql = "
            SELECT id FROM krf_polyhouse_plantqcstatus_tracking
            WHERE projectid = ? AND trayid = ? AND barcode = ?
            LIMIT 1
        ";
        $check = $conn->prepare($checkSql);
        if (!$check) {
            throw new Exception("Prepare failed (check): " . $conn->error);
        }

        $check->bind_param("iis", $projectid, $trayid, $barcode);
        $check->execute();
        $result = $check->get_result();
        $exists = ($result && $result->num_rows > 0);
        $row = $exists ? $result->fetch_assoc() : null;
        $check->close();

        /* ---------------------------
           UPDATE
        --------------------------- */
        if ($exists && $statustrackingid > 0) {

            $updateSql = "
                UPDATE krf_polyhouse_plantqcstatus_tracking
                SET
                    status = ?,
                    polyhouseid = ?,
                    polyhouseplantid = ?,
                    qc_status = ?,
                    replaced_any_plant = ?,
                    replacefrom_polyhouseplantid = ?,
                    replacefrom_trayid = ?,
                    replacefrom_tray_barcode = ?,
                    replaced_plantcount = ?,
                    remarks = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ?
            ";

            $stmt = $conn->prepare($updateSql);
            if (!$stmt) {
                throw new Exception("Prepare failed (update): " . $conn->error);
            }

            $stmt->bind_param(
                "siisssiisisi",
                $status,
                $polyhouseid,
                $polyhouseplantid,
                $qc_status,
                $replaced_any_plant,
                $replacefrom_polyhouseplantid,
                $replacefrom_trayid,
                $replacefrom_tray_barcode,
                $replaced_plantcount,
                $remarks,
                $updated_by,
                $statustrackingid
            );

            $stmt->execute();
            $stmt->close();

            return ['status' => 'success', 'message' => 'QC tracking updated successfully'];
        }

        /* ---------------------------
           INSERT
        --------------------------- */
        $insertSql = "
            INSERT INTO krf_polyhouse_plantqcstatus_tracking
            (
                projectid, polyhouseid, polyhouseplantid, trayid, barcode,
                qc_status, replaced_any_plant,
                replacefrom_polyhouseplantid, replacefrom_trayid, replacefrom_tray_barcode,
                replaced_plantcount, remarks, updated_by, status, updated_at
            ) VALUES (
                ?,?,?,?,?,?,
                ?,?,?,?,?,?,
                ?,?,NOW()
            )
        ";

        $stmt = $conn->prepare($insertSql);
        if (!$stmt) {
            throw new Exception("Prepare failed (insert): " . $conn->error);
        }

        $stmt->bind_param(
            "iiissssiisisss",
            $projectid,
            $polyhouseid,
            $polyhouseplantid,
            $trayid,
            $barcode,
            $qc_status,
            $replaced_any_plant,
            $replacefrom_polyhouseplantid,
            $replacefrom_trayid,
            $replacefrom_tray_barcode,
            $replaced_plantcount,
            $remarks,
            $updated_by,
            $status
        );

        $stmt->execute();
        $stmt->close();

        return ['status' => 'success', 'message' => 'QC tracking inserted successfully'];

    } catch (Exception $e) {
        return ['status' => 'error', 'message' => $e->getMessage()];
    }
}


// function saveReadyToSaleStock($krfconn, $projectid, $data = [])
// {
//     if (!$krfconn || empty($projectid)) {
//         return ["status" => false, "msg" => "Missing required parameters"];
//     }

//     // ensure numeric types
//     $projectid = intval($projectid);
//     $id = isset($data['id']) ? intval($data['id']) : 0;

//     // normalize all fields (provide defaults to avoid undefined index)
//     $psono = isset($data['psono']) ? $data['psono'] : null;
//     $clientid = isset($data['clientid']) ? $data['clientid'] : null;
//     $productid = isset($data['productid']) ? $data['productid'] : null;
//     $productname = isset($data['productname']) ? $data['productname'] : null;
//     $trayid = isset($data['trayid']) ? intval($data['trayid']) : 0;
//     $qtypertray = isset($data['qtypertray']) ? intval($data['qtypertray']) : 0;
//     $locationid = isset($data['locationid']) ? $data['locationid'] : null;
//     $locationname = isset($data['locationname']) ? $data['locationname'] : null;

//     $polyhouseplantid = isset($data['polyhouseplantid']) ? $data['polyhouseplantid'] : null;
//     $saleorderno = isset($data['saleorderno']) ? $data['saleorderno'] : null;
//     $deliverydate = isset($data['deliverydate']) ? $data['deliverydate'] : null; // string YYYY-MM-DD or null
//     $remarks = isset($data['remarks']) ? $data['remarks'] : null;
//     $status = isset($data['status']) ? $data['status'] : null;
//     $created_by = isset($data['created_by']) ? $data['created_by'] : null;

//     // ----------------------------------
//     // 1. CHECK IF RECORD EXISTS (projectid + id)
//     // ----------------------------------
//     $exists = false;
//     if ($id > 0) {
//         $checkSql = "SELECT id FROM krf_readytosale_stocks WHERE projectid = ? AND id = ? LIMIT 1";
//         $stmt = $krfconn->prepare($checkSql);
//         if ($stmt === false) {
//             return ["status" => false, "msg" => "Prepare failed (check): " . $krfconn->error];
//         }
//         $stmt->bind_param("ii", $projectid, $id);
//         if (!$stmt->execute()) {
//             $err = $stmt->error;
//             $stmt->close();
//             return ["status" => false, "msg" => "Execute failed (check): " . $err];
//         }
//         $result = $stmt->get_result();
//         $exists = ($result && $result->num_rows > 0);
//         $stmt->close();
//     }

//     // ----------------------------------
//     // 2. UPDATE
//     // ----------------------------------
//     if ($exists) {
//         $updateSql = "UPDATE krf_readytosale_stocks
//                       SET 
//                         psono = ?, clientid = ?, productid = ?, productname = ?, 
//                         trayid = ?, qtypertray = ?, locationid = ?, polyhouseplantid = ?, 
//                         saleorderno = ?, deliverydate = ?, remarks = ?, 
//                         status = ?, created_by = ?, locationname= ?
//                       WHERE id = ? AND projectid = ?";
//         $stmt2 = $krfconn->prepare($updateSql);
//         if ($stmt2 === false) {
//             return ["status" => false, "msg" => "Prepare failed (update): " . $krfconn->error];
//         }

//         $types = "ssssiissssssssii"; // final types string: 4s + 2i + 7s + 2i = 15 chars
//         // Bind parameters (order must match SQL placeholders)
//         $stmt2->bind_param($types, $psono, $productionclientid, $productid, $productname, $trayid, $qtypertray, $locationid, $polyhouseplantid, $saleorderno, $deliverydate, $remarks, $status, $created_by, $locationname, $id, $projectid);

//         if (!$stmt2->execute()) {
//             $err = $stmt2->error;
//             $stmt2->close();
//             return ["status" => false, "msg" => "Execute failed (update): " . $err];
//         }

//         $affected = $stmt2->affected_rows;
//         $stmt2->close();

//         return ["status" => true, "msg" => "Record updated successfully", "id" => $id, "affected_rows" => $affected];
//     }

//     // ----------------------------------
//     // 3. INSERT (id = 0 means new record)
//     // ----------------------------------
//     $insertSql = "INSERT INTO krf_readytosale_stocks 
//                   (psono, projectid, productionclientid, productid, productname, trayid, qtypertray, 
//                    locationid, polyhouseplantid, saleorderno, deliverydate, remarks, status, created_by, locationname) 
//                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
//     $stmt3 = $krfconn->prepare($insertSql);
//     if ($stmt3 === false) {
//         return ["status" => false, "msg" => "Prepare failed (insert): " . $krfconn->error];
//     }
//     $insertTypes = "sisssiissssssss";
//     $stmt3->bind_param($insertTypes, $psono, $projectid, $clientid, $productid, $productname, $trayid, $qtypertray, $locationid, $polyhouseplantid, $saleorderno, $deliverydate, $remarks, $status, $created_by, $locationname);

//     if (!$stmt3->execute()) {
//         $err = $stmt3->error;
//         $stmt3->close();
//         return ["status" => false, "msg" => "Execute failed (insert): " . $err];
//     }

//     $newId = $stmt3->insert_id;
//     $stmt3->close();

//     return ["status" => true, "msg" => "Record inserted successfully", "id" => $newId];
// }

// function saveReadyToSaleStock($krfconn, $projectid, $data = [])
// {
//     try {

//         if (!$krfconn || empty($projectid)) {
//             throw new Exception("Missing required parameters");
//         }

//         $projectid = (int)$projectid;
//         $id = (int)($data['id'] ?? 0);

//         $stockorder_no = $data['stockorder_no'] ?? null;
//         $psono = $data['psono'] ?? null;
//         $productionclientid = $data['productionclientid'] ?? null;
//         $productid = $data['productid'] ?? null;
//         $productname = $data['productname'] ?? null;
//         $trayid = (int)($data['trayid'] ?? 0);
//         $qtypertray = (int)($data['qtypertray'] ?? 0);
//         $locationid = $data['locationid'] ?? null;
//         $locationname = $data['locationname'] ?? null;
//         $polyhouseplantid = $data['polyhouseplantid'] ?? null;
//         $saleorderno = $data['saleorderno'] ?? null;
//         $deliverydate = $data['deliverydate'] ?? null;
//         $remarks = $data['remarks'] ?? null;
//         $status = $data['status'] ?? 'ReadyToSale';
//         $created_by = $data['created_by'] ?? null;

//         /* ---------------------------
//            INSERT ONLY (no duplicate ReadyToSale)
//         --------------------------- */
//         $insertSql = "
//             INSERT INTO krf_readytosale_stocks
//             (
//                 stockorder_no,psono, projectid, productionclientid, productid, productname,
//                 trayid, qtypertray, locationid, locationname,
//                 polyhouseplantid, saleorderno, deliverydate,
//                 remarks, status, created_by, created_at
//             ) VALUES (
//                 ?,?,?,?,?,?,?,
//                 ?,?,?,?,?,?,
//                 ?,?,?,NOW()
//             )
//         ";

//         $stmt = $krfconn->prepare($insertSql);
//         if (!$stmt) {
//             throw new Exception("Prepare failed: " . $krfconn->error);
//         }

//         $stmt->bind_param(
//             "ssisssiissssssss",
//             $stockorder_no,
//             $psono,
//             $projectid,
//             $productionclientid,
//             $productid,
//             $productname,
//             $trayid,
//             $qtypertray,
//             $locationid,
//             $locationname,
//             $polyhouseplantid,
//             $saleorderno,
//             $deliverydate,
//             $remarks,
//             $status,
//             $created_by
//         );

//         $stmt->execute();
//         $newId = $stmt->insert_id;
//         $stmt->close();

//         return [
//             "status" => true,
//             "msg" => "Ready-to-sale stock inserted successfully",
//             "id" => $newId
//         ];

//     } catch (Exception $e) {
//         return ["status" => false, "msg" => $e->getMessage()];
//     }
// }

function saveReadyToSaleStock($krfconn, $projectid, $data = [])
{
    try {

        if (!$krfconn || empty($projectid)) {
            throw new Exception("Missing required parameters");
        }

        $projectid = (int) $projectid;

        // Normalize inputs
        $stockorder_no = $data['stockorder_no'] ?? null;
        $psono = $data['psono'] ?? null;
        $productionclientid = $data['productionclientid'] ?? null;
        $productid = $data['productid'] ?? null;
        $productname = $data['productname'] ?? null;
        $trayid = (int) ($data['trayid'] ?? 0);
        $qtypertray = (int) ($data['qtypertray'] ?? 0);
        $locationid = $data['locationid'] ?? null;
        $locationname = $data['locationname'] ?? null;
        $polyhouseplantid = $data['polyhouseplantid'] ?? null;
        $saleorderno = $data['saleorderno'] ?? null;
        $deliverydate = $data['deliverydate'] ?? null;
        $remarks = $data['remarks'] ?? null;
        $status = $data['status'] ?? 'ReadyToSale';
        $created_by = $data['created_by'] ?? null;

        /* =====================================================
           1️⃣ CHECK IF READY-TO-SALE ALREADY EXISTS
           (projectid + polyhouseplantid + trayid)
        ===================================================== */
        $checkSql = "
            SELECT id 
            FROM krf_readytosale_stocks 
            WHERE projectid = ? 
              AND polyhouseplantid = ?
              AND trayid = ?
              AND status = 'ReadyToSale'
            LIMIT 1
        ";

        $checkStmt = $krfconn->prepare($checkSql);
        if (!$checkStmt) {
            throw new Exception("Prepare failed (check): " . $krfconn->error);
        }

        $checkStmt->bind_param(
            "iii",
            $projectid,
            $polyhouseplantid,
            $trayid
        );

        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result && $result->num_rows > 0) {
            // Already exists → skip insert
            $row = $result->fetch_assoc();
            $checkStmt->close();

            return [
                "status" => true,
                "msg" => "Ready-to-sale record already exists. Skipped insert.",
                "id" => $row['id']
            ];
        }

        $checkStmt->close();

        /* =====================================================
           2️⃣ INSERT NEW READY-TO-SALE RECORD
        ===================================================== */
        $insertSql = "
            INSERT INTO krf_readytosale_stocks
            (
                stockorder_no, psono, projectid, productionclientid,
                productid, productname, trayid, qtypertray,
                locationid, locationname, polyhouseplantid,
                saleorderno, deliverydate, remarks,
                status, created_by, created_at
            ) VALUES (
                ?,?,?,?,?,?,?,
                ?,?,?,?,?,?,
                ?,?,?,NOW()
            )
        ";

        $stmt = $krfconn->prepare($insertSql);
        if (!$stmt) {
            throw new Exception("Prepare failed (insert): " . $krfconn->error);
        }

        $stmt->bind_param(
            "ssisssiissssssss",
            $stockorder_no,
            $psono,
            $projectid,
            $productionclientid,
            $productid,
            $productname,
            $trayid,
            $qtypertray,
            $locationid,
            $locationname,
            $polyhouseplantid,
            $saleorderno,
            $deliverydate,
            $remarks,
            $status,
            $created_by
        );

        $stmt->execute();
        $newId = $stmt->insert_id;
        $stmt->close();

        return [
            "status" => true,
            "msg" => "Ready-to-sale stock inserted successfully",
            "id" => $newId
        ];

    } catch (Exception $e) {
        return [
            "status" => false,
            "msg" => $e->getMessage()
        ];
    }
}





function insertGrowthStage($conn, $data)
{
    $sql = "INSERT INTO krf_growthstages (
        projectid, productid, fullcycledays, growingdays,
        SeedGerminationdays,
        ScianSeedGerminationdays,
        EarlySeedlingdays,
        TrueLeafSeedlingdays,
        GraftingOperationdays,
        HealingChamberdays,
        Hardeningdays,
        ClipRemovaldays,
        SecondaryHardeningdays,
        SessionalCorrectionDays, `SessionalName`,
        created_by
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiisssssssssssss",
        $data["projectid"],
        $data["productid"],
        $data["fullcycledays"],
        $data["growingdays"],
        $data["SeedGerminationdays"],
        $data["ScianSeedGerminationdays"],
        $data["EarlySeedlingdays"],
        $data["TrueLeafSeedlingdays"],
        $data["GraftingOperationdays"],
        $data["HealingChamberdays"],
        $data["Hardeningdays"],
        $data["ClipRemovaldays"],
        $data["SecondaryHardeningdays"],
        $data["SessionalCorrectionDays"],
        $data["SessionalName"],
        $data["loginid"]
    );

    if ($stmt->execute()) {

        // GET LAST INSERT ID
        $lastInsertId = $conn->insert_id;

        // Update product_master
        $update = $conn->prepare("UPDATE product_master 
                                  SET ProductGrowthId=? 
                                  WHERE ProductID=? AND ProjectID=?");

        $update->bind_param("iii", $lastInsertId, $data["productid"], $data["projectid"]);
        $update->execute();

        echo json_encode(["status" => "success", "message" => "Growth Stage Saved Successfully"]);

    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
}


function updateGrowthStage($conn, $data, $id)
{
    $sql = "UPDATE krf_growthstages SET
        projectid=?, productid=?, fullcycledays=?, growingdays=?,
        SeedGerminationdays=?,
        ScianSeedGerminationdays=?,
        EarlySeedlingdays=?,
        TrueLeafSeedlingdays=?,
        GraftingOperationdays=?,
        HealingChamberdays=?,
        Hardeningdays=?,
        ClipRemovaldays=?,
        SecondaryHardeningdays=?,
        SessionalCorrectionDays=?, `SessionalName`=?,
        updated_by=?
        WHERE id=?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiisssssssssssssi",
        $data["projectid"],
        $data["productid"],
        $data["fullcycledays"],
        $data["growingdays"],
        $data["SeedGerminationdays"],
        $data["ScianSeedGerminationdays"],
        $data["EarlySeedlingdays"],
        $data["TrueLeafSeedlingdays"],
        $data["GraftingOperationdays"],
        $data["HealingChamberdays"],
        $data["Hardeningdays"],
        $data["ClipRemovaldays"],
        $data["SecondaryHardeningdays"],
        $data["SessionalCorrectionDays"],
        $data["SessionalName"],
        $data["loginid"],
        $id
    );

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Growth Stage Updated Successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
}

function getProductGrowthStages($krfconn, $projectid, $sortby = null, $sortbyvalue = null)
{
    if (empty($projectid)) {
        return [
            "status" => "error",
            "message" => "projectid is required"
        ];
    }

    // ✅ Allowed filter columns (table-qualified)
    $allowedSort = [
        "gs.id",
        "gs.projectid",
        "gs.productid",
        "gs.fullcycledays",
        "gs.growingdays",
        "gs.sowing_idealtemperature",
        "gs.SeedGerminationdays",
        "gs.EarlySeedlingdays",
        "gs.TrueLeafSeedlingdays",
        "gs.GraftingOperationdays",
        "gs.HealingChamberdays",
        "gs.Hardeningdays",
        "gs.ClipRemovaldays",
        "gs.SessionalName",
        "pm.ProductName",
        "pm.IsGrafted"
    ];

    // Base SQL with JOIN
    $sql = "
        SELECT 
            gs.*,
            pm.ProductName,
            pm.IsGrafted
        FROM krf_growthstages gs
        LEFT JOIN product_master pm 
            ON pm.ProductID = gs.productid
        WHERE gs.projectid = ?
    ";

    $types = "i";
    $params = [$projectid];

    // ✅ Optional filter
    if (!empty($sortby) && $sortbyvalue !== null) {

        if (!in_array($sortby, $allowedSort)) {
            return [
                "status" => "error",
                "message" => "Invalid filter column"
            ];
        }

        $sql .= " AND $sortby = ?";

        if (is_numeric($sortbyvalue)) {
            $types .= "i";
            $params[] = (int) $sortbyvalue;
        } else {
            $types .= "s";
            $params[] = $sortbyvalue;
        }
    }

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        return [
            "status" => "error",
            "message" => $krfconn->error
        ];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {

        // ✅ Decode JSON correction days
        if (!empty($row['SessionalCorrectionDays'])) {
            $decoded = json_decode($row['SessionalCorrectionDays'], true);
            $row['correctiondays'] =
                (json_last_error() === JSON_ERROR_NONE) ? $decoded : [];
        } else {
            $row['correctiondays'] = [];
        }
        unset($row['SessionalCorrectionDays']);
        $rows[] = $row;
    }

    return [
        "status" => "success",
        "data" => $rows
    ];
}


function getSowingRowCount($conn, $projectid, $stockorder_no, $roottype = "Root", $sowingbatchno="")
{
    $retval = 0;
    $sql = "SELECT COUNT(sowingplantid) AS rowcount FROM krf_polyhouse_sowingplant_details WHERE status in('Sowing','Germination','Tray Received At Germination' ,'Tray Allocated To Polyhouse') AND roottype=? AND projectid = ? AND stockorder_no = ? AND (tedit IS NULL OR tedit = '')";
    $params = [$roottype, (string)$projectid, $stockorder_no];
    $types = "sss";
    if (!empty($sowingbatchno)) {
        $sql .= " AND sowingbatchno = ?";
        $params[] = $sowingbatchno;
        $types .= "s";
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return $retval; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $retval = $row['rowcount'];
    }
    $stmt->close();
    return $retval;
}

function pendingsowingtraysforgerminationbox($conn, $projectid, $stockorder_no, $roottype = "Root", $sowingbatchno="")
{
    $retval = [];
    $sql = "SELECT * FROM krf_polyhouse_sowingplant_details WHERE status in('Sowing','Germination','Tray Received At Germination' ,'Tray Allocated To Polyhouse') AND workingstatus in('SowingInProcess') AND roottype=? AND projectid = ? AND stockorder_no = ? AND (tedit IS NULL OR tedit = '')";
    $params = [$roottype, (string)$projectid, $stockorder_no];
    $types = "sss";
    if (!empty($sowingbatchno)) {
        $sql .= " AND sowingbatchno = ?";
        $params[] = $sowingbatchno;
        $types .= "s";
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return $retval; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $retval[] = $row;
    }
    $stmt->close();
    return $retval;
}

function sowingtraysinthebox($conn, $projectid, $stockorder_no, $roottype = "Root", $sowingbatchno="")
{
    $retval = 0;
    $sql = "SELECT COUNT(sowingplantid) AS rowcount FROM krf_polyhouse_sowingplant_details WHERE status in('Sowing','Germination','Tray Received At Germination' ,'Tray Allocated To Polyhouse') AND workingstatus in('GerminationInProcess') AND roottype=? AND projectid = ? AND stockorder_no = ? AND (tedit IS NULL OR tedit = '')";
    $params = [$roottype, (string)$projectid, $stockorder_no];
    $types = "sss";
    if (!empty($sowingbatchno)) {
        $sql .= " AND sowingbatchno = ?";
        $params[] = $sowingbatchno;
        $types .= "s";
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return $retval; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $retval = $row['rowcount'];
    }
    $stmt->close();
    return $retval;
}

function pendingsowingtraysforgerminationbox_ajay($conn, $projectid, $stockorder_no, $roottype = "Root", $sowingbatchno="")
{
    $retval = [];
    $sql = "SELECT * FROM krf_polyhouse_sowingplant_details WHERE status in('Sowing','Germination','Tray Received At Germination' ,'Tray Allocated To Polyhouse') AND workingstatus in('SowingInProcess','WithoutGermination') AND roottype=? AND projectid = ? AND stockorder_no = ? AND (tedit IS NULL OR tedit = '')";
    $params = [$roottype, (string)$projectid, $stockorder_no];
    $types = "sss";
    if (!empty($sowingbatchno)) {
        $sql .= " AND sowingbatchno = ?";
        $params[] = $sowingbatchno;
        $types .= "s";
    }
    $stmt = $conn->prepare($sql);
    if (!$stmt) { return $retval; }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $retval[] = $row;
    }
    $stmt->close();
    return $retval;
}

function getTrayGroupedData($conn, $projectid, $stockorder_no, $roottype = "Root", $sowingbatchno="")
{
    $sql = "SELECT * FROM krf_polyhouse_sowingplant_details WHERE status in('Sowing','Germination', 'Polyhouse' ,'Tray Received At Germination' ,'Tray Allocated To Polyhouse') AND roottype = ? AND projectid = ? AND stockorder_no = ? AND (tedit IS NULL OR tedit = '')";
    $params = [$roottype, (string)$projectid, $stockorder_no];
    $types = "sss";

    if (!empty($sowingbatchno)) {
        $sql .= " AND sowingbatchno = ?";
        $params[] = $sowingbatchno;
        $types .= "s";
    }

    $sql .= " ORDER BY stockorder_no, lotno, tray_position, bed_no, row_no, column_no";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $grouped = [];
    $seedCache = [];
    while ($row = $res->fetch_assoc()) {
        $stockorder = $row["stockorder_no"];
        $batch = !empty($row["sowingbatchno"]) ? $row["sowingbatchno"] : $row["lotno"];
        $lot = $row["lotno"];
        $traycavity = $row["traycavity"];
        $orderqty = $row["orderqty"];
        $failurebufferpercent = $row['failurebufferpercent'];
        $totalsowingqty = $row['totalsowingqty'];
        $seedid = $row['seedid'];
        $sowing_date = $row['sowing_date'];

        $group_key = $batch . "_" . $lot;

        // Create the main group if not exists
        if (!isset($grouped[$group_key])) {
            if (!isset($seedCache[$seedid])) {
                $seedinfo = SeedDetailsForProduct($conn, $projectid, $seedid);
                $seedCache[$seedid] = (is_array($seedinfo) && count($seedinfo) > 0) ? $seedinfo['SeedName'] : "";
            }
            $seedname = $seedCache[$seedid];

            $grouped[$group_key] = [
                "sowing_date" => $sowing_date,
                "roottype" => $roottype,
                "stockorder_no" => $stockorder,
                "sowinglotno_internal" => $row["sowinglotno_internal"] ?? '',
                "sowingbatchno" => $row["sowingbatchno"],
                "sowingdate_internal" => $row["sowingdate_internal"] ?? '',
                "dispatchdate_internal" => $row["dispatchdate_internal"] ?? '',
                "lotno" => $lot,
                "traycavity" => $traycavity,
                "failurebufferpercent" => $failurebufferpercent,
                "totalsowingqty" => $totalsowingqty,
                "seedid" => $seedid,
                "seedname" => $seedname,
                "tray_count" => 0,
                "tray_details" => []
            ];
        }

        // Add tray detail
        $grouped[$group_key]["tray_details"][] = [
            "trayid" => $row["trayid"],
            "traycavity" => $row["traycavity"],
            "labourid" => $row["labourid"],
            "polyhouseid" => $row["polyhouseid"],
            "tray_position" => $row["tray_position"],
            "bed_no" => $row["bed_no"],
            "row_no" => $row["row_no"],
            "column_no" => $row["column_no"],
            "rack_no" => $row["rack_no"],
            "floar_no" => $row["floar_no"],
            "barcode" => $row["barcode"],
            "growth_stage" => $row["growth_stage"],
            "health_status" => $row["health_status"]
        ];

        // Update summary
        $grouped[$group_key]["tray_count"] += 1;
    }

    $stmt->close();
    // Convert associative keyed by batch/lot to numeric array
    return array_values($grouped);
}

function getUniqueSowingBatchNo($conn, $projectid, $stockorder_no, $roottype = "Root")
{
    $batches = [];
    $sql = "SELECT DISTINCT sowingbatchno 
            FROM krf_polyhouse_sowingplant_details 
            WHERE projectid = ? 
              AND stockorder_no = ? 
              AND roottype = ? 
              AND sowingbatchno IS NOT NULL 
              AND sowingbatchno <> '' 
            ORDER BY sowingbatchno ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $batches;
    }

    $projectid = (int)$projectid;
    $stmt->bind_param("iss", $projectid, $stockorder_no, $roottype);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $batches[] = [
            'sowingbatchno' => $row['sowingbatchno']
        ];
    }
    $stmt->close();
    return $batches;
}

function getSowingTrayDetails($conn, $projectid, $stockorder_no = null, $shortby = null, $shortbyvalue = null)
{
    // echo $projectid."--".$stockorder_no."--".$shortby."--".$shortbyvalue;
    $sql = "SELECT * FROM krf_polyhouse_sowingplant_details WHERE status<>'UnConfirmSowing' and projectid = ? AND (tedit IS NULL OR tedit = '')";
    $params = [$projectid];
    $types = "i";
    // ? Optional stock order filter
    if (!empty($stockorder_no)) {
        $sql .= " AND stockorder_no = ?";
        $params[] = $stockorder_no;
        $types .= "s";
    }
    // ?? Optional filter handling
    if (!empty($shortby) && !empty($shortbyvalue)) {
        if ($shortby === "qrcode") {
            $sql .= " AND barcode = ?";
            $params[] = $shortbyvalue;
            $types .= "s";
        } elseif ($shortby === "like_qrcode") {
            $sql .= " AND barcode LIKE ?";
            $params[] = "%" . $shortbyvalue . "%";
            $types .= "s";

        } else {
            // ? Generic fallback filter (string type)
            $sql .= " AND {$shortby} = ?";
            $params[] = $shortbyvalue;
            $types .= "s";
        }
    }
    $sql .= " ORDER BY sowingplantid DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}





function SeedDetailsForProduct($conn, $projectid, $seedid)
{
    $data = [];
    try {

        $sql = "SELECT s.*, v.VendorName 
                FROM seed_master s
                LEFT JOIN vendor_master v 
                    ON s.VendorID = v.VendorID
                WHERE lcase(s.Status)='active' and s.ProjectId = ?
                AND s.SeedID = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("si", $projectid, $seedid);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $data = $row;
        }

        $stmt->close();

    } catch (Exception $e) {
        error_log("Error fetching seed data: " . $e->getMessage());
        return [];
    }

    return $data;
}

function getSeedDetailspassingpassing($conn, $projectid, $productid, $roottype)
{
    $seedColumn = ($roottype == 'Root') ? 'SeedId' : 'ScionSeedId';

    $sql = "SELECT pm.ProductID,
                   pm.ProductName,
                   sm.*
            FROM product_master pm
            LEFT JOIN seed_master sm 
                ON sm.SeedID = pm.$seedColumn
            WHERE pm.ProjectId = ?
              AND pm.ProductID = ?
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $projectid, $productid);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function getBoxWiseGerminationSummaryWithTrayDetails($krfconn, $projectid, $stockorder_no)
{
    $sql = "
        SELECT 
            germination_qrcode,
            stockorder_no,
            productname,
            sowingplantid,
            trayid,
            traycavity,
            barcode,
            lotno,
            labourid,
            polyhouseid,
            tray_position,
            bed_no,
            row_no,
            column_no,
            rack_no,
            floar_no,
            growth_stage,
            health_status,
            packingdate_germbox,
            openingdate_germbox,
            roottype
        FROM krf_polyhouse_sowingplant_details
        WHERE projectid = ?
          AND stockorder_no = ?
          AND germination_qrcode IS NOT NULL
          AND germination_qrcode <> ''
          AND status<>'UnConfirmSowing'
        ORDER BY germination_qrcode, tray_position, bed_no, row_no, column_no
    ";

    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $projectid, $stockorder_no);
    $stmt->execute();
    $result = $stmt->get_result();

    $grouped = [];

    while ($row = $result->fetch_assoc()) {

        $qr = $row['germination_qrcode'];

        /* Create box (QR) group */
        if (!isset($grouped[$qr])) {
            $grouped[$qr] = [
                "germination_qrcode" => $qr,
                "stockorder_no" => $row["stockorder_no"],
                "productname" => $row["productname"],
                "packingdate" => $row["packingdate_germbox"],
                "openingdate" => $row["openingdate_germbox"],
                "traycount" => 0,
                "traydetails" => []
            ];
        }

        /* Add tray detail */
        $grouped[$qr]["traydetails"][] = [
            "sowingplantid" => $row["sowingplantid"],
            "lotno" => $row["lotno"],
            "trayid" => $row["trayid"],
            "traycavity" => $row["traycavity"],
            "barcode" => $row["barcode"],
            "labourid" => $row["labourid"],
            "polyhouseid" => $row["polyhouseid"],
            "tray_position" => $row["tray_position"],
            "bed_no" => $row["bed_no"],
            "row_no" => $row["row_no"],
            "column_no" => $row["column_no"],
            "rack_no" => $row["rack_no"],
            "floar_no" => $row["floar_no"],
            "growth_stage" => $row["growth_stage"],
            "health_status" => $row["health_status"]
        ];

        $grouped[$qr]["traycount"]++;
    }

    /* Convert associative array to indexed array */
    return array_values($grouped);
}

function GetPendingTrayAllocationofGBoxes($conn, $projectid)
{
    $sql = "SELECT boxid, projectid, stockorderno, productid, productname, qrcode, traycountinbox, ifnull(allocated_traycount,0) as allocated_traycount, boxpackingdate, boxopeningdate, polyhouseid, status
        FROM krf_polyhouse_germinated_boxes
        WHERE projectid = ?
          AND status = 'ReceivedAtPolyhouse'
          AND COALESCE(traycountinbox, 0) <> COALESCE(allocated_traycount, 0)
        ORDER BY stockorderno ASC, boxid ASC
    ";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

 function getGerminationBoxQRCodeDetailsAfterSowing($conn, $projectid, $roottype = null, $shortby = null, $shortbyvalue = null)
{
    $sql = "SELECT * FROM krf_polyhouse_germinated_boxes WHERE lcase(status)='PendingAllocation' and projectid = ? AND (tedit IS NULL OR tedit = '')";
    $params = [(string)$projectid];
    $types = "s";

    // Optional roottype filter
    if (!empty($roottype)) {
        $sql .= " AND roottype = ?";
        $params[] = $roottype;
        $types .= "s";
    }

    // Allowed dynamic filters
    if (!empty($shortby) && $shortbyvalue !== null) {
        $allowedFilters = [
            "boxid" => "i",
            "qrcode" => "s",
            "stockorderno" => "s",
            "status" => "s",
            "polyhouseid" => "i"
        ];

        if (isset($allowedFilters[$shortby])) {
            $sql .= " AND {$shortby} = ?";
            $params[] = $shortbyvalue;
            $types .= $allowedFilters[$shortby];
        }
    }

    $sql .= " ORDER BY boxid DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getGerminationBoxQRCodeDetails($conn, $projectid, $roottype = null, $shortby = null, $shortbyvalue = null)
{
    $sql = "SELECT * FROM krf_polyhouse_germinated_boxes WHERE projectid = ? AND (tedit IS NULL OR tedit = '')";
    $params = [(string)$projectid];
    $types = "s";

    // Optional roottype filter
    if (!empty($roottype)) {
        $sql .= " AND roottype = ?";
        $params[] = $roottype;
        $types .= "s";
    }

    // Allowed dynamic filters
    if (!empty($shortby) && $shortbyvalue !== null) {
        $allowedFilters = [
            "boxid" => "i",
            "qrcode" => "s",
            "stockorderno" => "s",
            "status" => "s",
            "polyhouseid" => "i"
        ];

        if (isset($allowedFilters[$shortby])) {
            $sql .= " AND {$shortby} = ?";
            $params[] = $shortbyvalue;
            $types .= $allowedFilters[$shortby];
        }
    }

    $sql .= " ORDER BY boxid DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    // echo debugQueryProduct($sql, $params, $types);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function AllocatedGerminationBoxDetails($conn, $projectid, $roottype = null, $shortby = null, $shortbyvalue = null)
{
    $sql = "SELECT * FROM krf_polyhouse_germinated_boxes WHERE status='Allocated' AND projectid = ?";
    $params = [$projectid];
    $types = "i";

    // Optional roottype filter
    if (!empty($roottype)) {
        $sql .= " AND roottype = ?";
        $params[] = $roottype;
        $types .= "s";
    }

    // Allowed dynamic filters
    if (!empty($shortby) && $shortbyvalue !== null) {
        $allowedFilters = [
            "boxid" => "i",
            "qrcode" => "s",
            "stockorderno" => "s",
            "status" => "s",
            "polyhouseid" => "i"
        ];

        if (isset($allowedFilters[$shortby])) {
            $sql .= " AND {$shortby} = ?";
            $params[] = $shortbyvalue;
            $types .= $allowedFilters[$shortby];
        }
    }

    $sql .= " ORDER BY boxid DESC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


function getAllocationForGerminatedBoxesGrouped($conn, $fromDate, $toDate, $projectid)
{
    global $authconn;
    $sql = "SELECT boxid, roottype, projectid, stockorderno, productid, productname, qrcode, traycountinbox, boxpackingdate, boxopeningdate, polyhouseid, status, sowinglotno_internal, created_by, received_by FROM krf_polyhouse_germinated_boxes
        WHERE projectid = ? AND boxopeningdate >= ? AND boxopeningdate <= ?  AND status IN ('PendingAllocation','ReceivedAfterSowing')  ORDER BY stockorderno ASC, boxid ASC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iss", $projectid, $fromDate, $toDate);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    $userIds = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        if (!empty($row['created_by'])) {
            $userIds[] = (int)$row['created_by'];
        }
        if (!empty($row['received_by'])) {
            $userIds[] = (int)$row['received_by'];
        }
    }
    $stmt->close();

    $userMap = [];
    if (!empty($userIds) && isset($authconn) && $authconn instanceof mysqli) {
        $userIds = array_unique($userIds);
        $placeholders = implode(',', array_fill(0, count($userIds), '?'));
        $types = str_repeat('i', count($userIds));
        $userSql = "SELECT loginid, loginname FROM login_user_master WHERE loginid IN ($placeholders)";
        $userStmt = $authconn->prepare($userSql);
        if ($userStmt) {
            $userStmt->bind_param($types, ...$userIds);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            while ($uRow = $userResult->fetch_assoc()) {
                $userMap[$uRow['loginid']] = $uRow['loginname'];
            }
            $userStmt->close();
        }
    }

    $groupedData = [];
    foreach ($rows as $row) {
        $received_by_name = $userMap[$row['created_by']] ?? '';
        $allocated_by_name = $userMap[$row['received_by']] ?? '';

        $key = $row['stockorderno'] . '_' . $row['roottype'] . '_' . $row['productid'] . '_' . $row['sowinglotno_internal'];
        if (!isset($groupedData[$key])) {
            $groupedData[$key] = [
                "stockorderno" => $row['stockorderno'],
                "roottype" => $row['roottype'],
                "productid" => $row['productid'],
                "sowinglotno_internal" => $row['sowinglotno_internal'],
                "projectid" => $row['projectid'],
                "productname" => $row['productname'],
                "boxpackingdate" => $row['boxpackingdate'],
                "boxopeningdate" => $row['boxopeningdate'],
                "polyhouseid" => $row['polyhouseid'],
                "traycountinbox" => $row['traycountinbox'],
                "noofboxes" => 0,
                "allocatedboxes" => 0,
                "pendingallocation" => 0,
                "received_by" => [],
                "allocated_by" => [],
                "boxes" => []
            ];
        }
        if ($row['status'] == "Allocated") {
            $groupedData[$key]['allocatedboxes']++;
        } else {
            $groupedData[$key]['pendingallocation']++;
        }
        $groupedData[$key]['noofboxes']++;
        $groupedData[$key]['boxes'][] = [
            "boxid" => $row['boxid'],
            "qrcode" => $row['qrcode'],
            "traycountinbox" => $row['traycountinbox']
        ];
        if (!empty($received_by_name) && !in_array($received_by_name, $groupedData[$key]['received_by'])) {
            $groupedData[$key]['received_by'][] = $received_by_name;
        }
        if (!empty($allocated_by_name) && !in_array($allocated_by_name, $groupedData[$key]['allocated_by'])) {
            $groupedData[$key]['allocated_by'][] = $allocated_by_name;
        }
    }
    foreach ($groupedData as &$data) {
        $data['received_by'] = implode(", ", $data['received_by']);
        $data['allocated_by'] = implode(", ", $data['allocated_by']);
    }
    return array_values($groupedData);
}

function getPendingAllocationBoxesGroupedByDateDesc($conn, $projectid, $dateFrom = null, $dateTo = null)
{
    global $authconn;

    try {
        $dateFilterSql = "";
        $params = [$projectid];
        $types = "i";

        if (!empty($dateFrom) && $dateFrom !== 'all') {
            if (!empty($dateTo) && $dateTo !== $dateFrom && $dateTo !== 'all') {
                $dateFilterSql = " AND gb.boxopeningdate >= ? AND gb.boxopeningdate <= ? ";
                $params[] = $dateFrom;
                $params[] = $dateTo;
                $types .= "ss";
            } else {
                $dateFilterSql = " AND gb.boxopeningdate = ? ";
                $params[] = $dateFrom;
                $types .= "s";
            }
        }

        $sql = "SELECT gb.boxid, gb.roottype, gb.projectid, gb.stockorderno, gb.productid, gb.productname, 
                       gb.qrcode, gb.traycountinbox, gb.boxpackingdate, gb.boxopeningdate, gb.polyhouseid, 
                       gb.status, gb.sowinglotno_internal, gb.created_by, gb.received_by, gb.received_at
                FROM krf_polyhouse_germinated_boxes gb
                WHERE gb.projectid = ? 
                  AND gb.status IN ('PendingAllocation', 'ReceivedAfterSowing')
                  AND (gb.tedit IS NULL OR gb.tedit = '')
                  $dateFilterSql
                ORDER BY gb.boxopeningdate DESC, gb.stockorderno ASC, gb.boxid ASC";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $pendingRows = [];
        $stockOrderList = [];
        $userIds = [];

        while ($row = $res->fetch_assoc()) {
            $pendingRows[] = $row;
            if (!empty($row['stockorderno'])) {
                $stockOrderList[] = $row['stockorderno'];
            }
            if (!empty($row['created_by']) && is_numeric($row['created_by'])) {
                $userIds[] = (int)$row['created_by'];
            }
            if (!empty($row['received_by']) && is_numeric($row['received_by'])) {
                $userIds[] = (int)$row['received_by'];
            }
        }
        $stmt->close();

        if (empty($pendingRows)) {
            return [];
        }

        // Fetch all allocated boxes for these stock orders
        $stockOrderList = array_values(array_filter(array_unique($stockOrderList)));
        $allocatedByOrder = [];

        if (!empty($stockOrderList)) {
            $placeholders = implode(',', array_fill(0, count($stockOrderList), '?'));
            $soTypes = "i" . str_repeat('s', count($stockOrderList));

            $allocSql = "SELECT gb.boxid, gb.roottype, gb.projectid, gb.stockorderno, gb.productid, gb.productname, 
                                gb.qrcode, gb.traycountinbox, gb.boxpackingdate, gb.boxopeningdate, gb.polyhouseid, 
                                gb.status, gb.sowinglotno_internal, gb.created_by, gb.received_by, gb.received_at,
                                ph.polyhousename, ph.physicallocation
                         FROM krf_polyhouse_germinated_boxes gb
                         LEFT JOIN krf_polyhouse_master ph ON gb.polyhouseid = ph.polyhouseid
                         WHERE gb.projectid = ?
                           AND gb.stockorderno IN ($placeholders)
                           AND gb.status IN ('Allocated', 'ReceivedAtPolyhouse')
                           AND (gb.tedit IS NULL OR gb.tedit = '')
                         ORDER BY gb.boxopeningdate DESC, gb.boxid ASC";

            $allocStmt = $conn->prepare($allocSql);
            if ($allocStmt) {
                $allocParams = array_merge([$projectid], $stockOrderList);
                $allocStmt->bind_param($soTypes, ...$allocParams);
                $allocStmt->execute();
                $allocRes = $allocStmt->get_result();

                while ($aRow = $allocRes->fetch_assoc()) {
                    $so = $aRow['stockorderno'];
                    $rt = $aRow['roottype'] ?? '';
                    $key = $so . '_' . $rt;
                    if (!isset($allocatedByOrder[$key])) {
                        $allocatedByOrder[$key] = [];
                    }
                    $allocatedByOrder[$key][] = $aRow;
                    if (!empty($aRow['created_by']) && is_numeric($aRow['created_by'])) {
                        $userIds[] = (int)$aRow['created_by'];
                    }
                    if (!empty($aRow['received_by']) && is_numeric($aRow['received_by'])) {
                        $userIds[] = (int)$aRow['received_by'];
                    }
                }
                $allocStmt->close();
            }
        }

        // Map user names
        $userMap = [];
        $userIds = array_values(array_filter(array_unique($userIds)));
        if (!empty($userIds) && isset($authconn) && $authconn instanceof mysqli) {
            $uPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
            $uTypes = str_repeat('i', count($userIds));
            $uSql = "SELECT loginid, loginname, mobileno FROM login_user_master WHERE loginid IN ($uPlaceholders)";
            $uStmt = $authconn->prepare($uSql);
            if ($uStmt) {
                $uStmt->bind_param($uTypes, ...$userIds);
                $uStmt->execute();
                $uRes = $uStmt->get_result();
                while ($uRow = $uRes->fetch_assoc()) {
                    $userMap[$uRow['loginid']] = $uRow['loginname'];
                    $userMap[$uRow['mobileno']] = $uRow['loginname'];
                }
                $uStmt->close();
            }
        }

        // Group pending rows by opening date, order, and lot
        $groupedData = [];
        foreach ($pendingRows as $row) {
            $opDate = $row['boxopeningdate'] ?? '';
            $groupKey = $opDate . '_' . $row['stockorderno'] . '_' . ($row['roottype'] ?? '') . '_' . ($row['sowinglotno_internal'] ?? '');
            $orderKey = $row['stockorderno'] . '_' . ($row['roottype'] ?? '');

            $rcvName = $userMap[$row['created_by']] ?? $row['created_by'];
            $alcName = $userMap[$row['received_by']] ?? $row['received_by'];

            if (!isset($groupedData[$groupKey])) {
                $rawAllocated = $allocatedByOrder[$orderKey] ?? [];
                $formattedAllocated = [];
                $allocPolyhouses = [];
                $allocDates = [];

                foreach ($rawAllocated as $abx) {
                    $abxAllocByName = $userMap[$abx['received_by']] ?? $abx['received_by'];
                    $phName = !empty($abx['polyhousename']) ? $abx['polyhousename'] : 'PH #' . ($abx['polyhouseid'] ?? '0');
                    if (!in_array($phName, $allocPolyhouses)) {
                        $allocPolyhouses[] = $phName;
                    }
                    if (!empty($abx['boxopeningdate']) && !in_array($abx['boxopeningdate'], $allocDates)) {
                        $allocDates[] = $abx['boxopeningdate'];
                    }
                    $formattedAllocated[] = [
                        "boxid" => $abx['boxid'],
                        "qrcode" => $abx['qrcode'],
                        "traycountinbox" => (int)$abx['traycountinbox'],
                        "boxopeningdate" => $abx['boxopeningdate'],
                        "boxopeningdate_dsp" => !empty($abx['boxopeningdate']) ? date("d-M-Y", strtotime($abx['boxopeningdate'])) : 'N/A',
                        "polyhouseid" => $abx['polyhouseid'],
                        "polyhousename" => $phName,
                        "physicallocation" => $abx['physicallocation'] ?? '',
                        "status" => $abx['status'],
                        "allocated_by" => $abxAllocByName ?: 'N/A',
                        "received_at" => !empty($abx['received_at']) ? date("d-M-Y h:i A", strtotime($abx['received_at'])) : 'N/A'
                    ];
                }

                $opDateDsp = !empty($opDate) ? date("d-M-Y", strtotime($opDate)) : 'No Date';
                $opDateDay = !empty($opDate) ? date("l", strtotime($opDate)) : '';
                $pkDateDsp = !empty($row['boxpackingdate']) ? date("d-M-Y", strtotime($row['boxpackingdate'])) : 'N/A';

                $groupedData[$groupKey] = [
                    "groupkey" => $groupKey,
                    "boxopeningdate" => $opDate,
                    "boxopeningdate_dsp" => $opDateDsp,
                    "boxopeningdate_day" => $opDateDay,
                    "boxpackingdate" => $row['boxpackingdate'],
                    "boxpackingdate_dsp" => $pkDateDsp,
                    "stockorderno" => $row['stockorderno'],
                    "roottype" => $row['roottype'] ?? '',
                    "productid" => $row['productid'],
                    "productname" => $row['productname'],
                    "sowinglotno_internal" => $row['sowinglotno_internal'] ?? '',
                    "projectid" => $row['projectid'],
                    "traycountinbox" => (int)$row['traycountinbox'],
                    "pending_boxes_count" => 0,
                    "pending_trays_count" => 0,
                    "allocated_boxes_count" => count($formattedAllocated),
                    "allocated_trays_count" => array_sum(array_column($formattedAllocated, 'traycountinbox')),
                    "allocated_polyhouses" => implode(", ", $allocPolyhouses),
                    "allocated_dates" => implode(", ", array_map(function($d){ return date("d-M-Y", strtotime($d)); }, $allocDates)),
                    "has_multiple_dates" => (count($allocDates) > 1 || (count($allocDates) === 1 && $allocDates[0] !== $opDate)),
                    "received_by" => [],
                    "allocated_by" => [],
                    "boxes" => [],
                    "allocated_boxes" => $formattedAllocated
                ];
            }

            $groupedData[$groupKey]['pending_boxes_count']++;
            $groupedData[$groupKey]['pending_trays_count'] += (int)$row['traycountinbox'];
            $groupedData[$groupKey]['boxes'][] = [
                "boxid" => $row['boxid'],
                "qrcode" => $row['qrcode'],
                "traycountinbox" => (int)$row['traycountinbox'],
                "status" => $row['status'],
                "boxopeningdate" => $row['boxopeningdate']
            ];

            if (!empty($rcvName) && !in_array($rcvName, $groupedData[$groupKey]['received_by'])) {
                $groupedData[$groupKey]['received_by'][] = $rcvName;
            }
            if (!empty($alcName) && !in_array($alcName, $groupedData[$groupKey]['allocated_by'])) {
                $groupedData[$groupKey]['allocated_by'][] = $alcName;
            }
        }

        foreach ($groupedData as &$data) {
            $data['total_boxes_count'] = $data['pending_boxes_count'] + $data['allocated_boxes_count'];
            $data['noofboxes'] = $data['total_boxes_count'];
            $data['allocatedboxes'] = $data['allocated_boxes_count'];
            $data['received_by'] = implode(", ", $data['received_by']);
            $data['allocated_by'] = implode(", ", $data['allocated_by']);
        }

        return array_values($groupedData);
    } catch (Throwable $e) {
        error_log("Error in getPendingAllocationBoxesGroupedByDateDesc: " . $e->getMessage());
        return [];
    }
}

function getBoxAllocationCounts($conn, $projectid, $stockorderno, $roottype = null)
{
    $roottypeSql = "";
    if (!empty($roottype) && $roottype !== 'N/A') {
        $roottypeSql = " AND roottype = ? ";
    }

    $sql = "
        SELECT 
            COUNT(*) AS total_boxes,
            SUM(CASE WHEN status = 'Allocated' THEN 1 ELSE 0 END) AS allocated_boxes,
            SUM(CASE WHEN status <> 'Allocated' THEN 1 ELSE 0 END) AS unallocated_boxes
        FROM krf_polyhouse_germinated_boxes
        WHERE projectid = ?
          AND stockorderno = ?
          $roottypeSql
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [
            "total_boxes" => 0,
            "allocated_boxes" => 0,
            "unallocated_boxes" => 0
        ];
    }

    if (!empty($roottype) && $roottype !== 'N/A') {
        $stmt->bind_param("iss", $projectid, $stockorderno, $roottype);
    } else {
        $stmt->bind_param("is", $projectid, $stockorderno);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    return [
        "total_boxes" => (int) ($data['total_boxes'] ?? 0),
        "allocated_boxes" => (int) ($data['allocated_boxes'] ?? 0),
        "unallocated_boxes" => (int) ($data['unallocated_boxes'] ?? 0)
    ];
}

// function getBoxReceivedAtPolyhouseCounts($conn, $projectid, $stockorderno,$roottype,$sowinglotno=null)
// {
//     $sql = "
//         SELECT 
//             COUNT(*) AS total_boxes,
//             SUM(CASE WHEN status = 'ReceivedAtPolyhouse' THEN 1 ELSE 0 END) AS allocated_boxes,
//             SUM(CASE WHEN status <> 'ReceivedAtPolyhouse' THEN 1 ELSE 0 END) AS unallocated_boxes
//         FROM krf_polyhouse_germinated_boxes
//         WHERE projectid = ? AND roottype = ?
//           AND stockorderno = ?
//     ";

//     $stmt = $conn->prepare($sql);
//     if (!$stmt) {
//         return [
//             "total_boxes" => 0,
//             "allocated_boxes" => 0,
//             "unallocated_boxes" => 0
//         ];
//     }

//     $stmt->bind_param("iss", $projectid,$roottype,$stockorderno);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $data = $result->fetch_assoc();
//     $stmt->close();

//     return [
//         "total_boxes" => (int) ($data['total_boxes'] ?? 0),
//         "allocated_boxes" => (int) ($data['allocated_boxes'] ?? 0),
//         "unallocated_boxes" => (int) ($data['unallocated_boxes'] ?? 0)
//     ];
// }

function getBoxReceivedAtPolyhouseCounts($conn, $projectid, $stockorderno, $roottype, $sowinglotno = null)
{
    $sql = "SELECT COUNT(qrcode) AS total_boxes, sum(traycountinbox) as traysinboxes, SUM(CASE WHEN status IN ('Allocated', 'ReceivedAtPolyhouse') THEN 1 ELSE 0 END) AS allocated_boxes, SUM(CASE WHEN status = 'PendingAllocation' THEN 1 ELSE 0 END) AS unallocated_boxes FROM krf_polyhouse_germinated_boxes WHERE projectid = ? AND roottype = ? AND stockorderno = ? AND (tedit IS NULL OR tedit = '')";

    $types = "iss";
    $params = [$projectid, $roottype, $stockorderno];

    if (!empty($sowinglotno)) {
        $sql .= " AND sowinglotno_internal = ?";
        $types .= "s";
        $params[] = $sowinglotno;
    }

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            "total_boxes" => 0,
            "total_trays" => 0,
            "allocated_boxes" => 0,
            "unallocated_boxes" => 0
        ];
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $result = $stmt->get_result();
    $data = $result->fetch_assoc();

    $stmt->close();

    return [
        "total_boxes" => (int)($data['total_boxes'] ?? 0),
        "total_trays" => (int)($data['traysinboxes'] ?? 0),
        "allocated_boxes" => (int)($data['allocated_boxes'] ?? 0),
        "unallocated_boxes" => (int)($data['unallocated_boxes'] ?? 0)
    ];
}


function getPolyhouseTrayUsageDetails(
    $conn,
    $locationid,
    $polyhouseid,
    $projectid
) {
    // Mode 1: Fetch ALL polyhouses if no specific ID is provided
    if (empty($polyhouseid)) {
        $sqlAll = "
            SELECT 
                pm.polyhouseid,
                pm.polyhousename,
                pm.structure_type,
                pm.beddetails
            FROM krf_polyhouse_master pm
            WHERE pm.projectid = ?
        ";
        if (!empty($locationid)) {
            $sqlAll .= " AND pm.locationid = ? ";
        }

        $stmt = $conn->prepare($sqlAll);
        if (!empty($locationid)) {
            $stmt->bind_param("ss", $projectid, $locationid);
        } else {
            $stmt->bind_param("s", $projectid);
        }
        $stmt->execute();
        $res = $stmt->get_result();

        $allResults = [];
        while ($ph = $res->fetch_assoc()) {
            $allResults[] = getPolyhouseTrayUsageDetails($conn, $locationid, $ph['polyhouseid'], $projectid);
        }
        return $allResults;
    }

    // Mode 2: Single Polyhouse Detailed Logic
    // 1. Get Summary & Bed Config
    $sqlSummary = "
        SELECT pm.polyhouseid, pm.polyhousename, pm.structure_type, pm.beddetails
        FROM krf_polyhouse_master pm
        WHERE pm.polyhouseid = ? AND pm.projectid = ?
    ";
    $stmt = $conn->prepare($sqlSummary);
    $stmt->bind_param("is", $polyhouseid, $projectid);
    $stmt->execute();
    $ph = $stmt->get_result()->fetch_assoc();
    if (!$ph)
        return null;

    // 2. Get Usage Grouped by Bed and Product
    $sqlUsage = "
        SELECT bed_no, productname, COUNT(polyhouseplantid) as tray_count
        FROM krf_polyhouse_plant_details
        WHERE polyhouseid = ? AND projectid = ? AND status = 'Polyhouse'
        GROUP BY bed_no, productname
    ";
    $ustmt = $conn->prepare($sqlUsage);
    $ustmt->bind_param("is", $polyhouseid, $projectid);
    $ustmt->execute();
    $uRes = $ustmt->get_result();
    $usageMap = [];
    while ($u = $uRes->fetch_assoc()) {
        $node = $u['bed_no'];
        if (!isset($usageMap[$node])) {
            $usageMap[$node] = ['total' => 0, 'products' => []];
        }
        $usageMap[$node]['total'] += (int) $u['tray_count'];
        $usageMap[$node]['products'][] = [
            'productname' => $u['productname'],
            'count' => (int) $u['tray_count']
        ];
    }

    // 3. Enrich Bed Details
    $bedDetails = json_decode($ph['beddetails'] ?? '[]', true);
    if (!is_array($bedDetails))
        $bedDetails = [];

    $totalCapacity = 0;
    $totalUsed = 0;
    $mappedBeds = [];

    foreach ($bedDetails as $bed) {
        // Use bedname if bedno/bed_no is missing
        $bedNo = $bed['bedno'] ?? $bed['bed_no'] ?? $bed['bedname'] ?? null;
        if ($bedNo === null)
            continue;

        // Handle complex traycapacity structure or simple integer
        $rawCapacity = 0;
        if (isset($bed['traycapacity']) && is_array($bed['traycapacity'])) {
            // Pick first tray type's capacity as representative total
            $firstTray = reset($bed['traycapacity']);
            $rawCapacity = (int) ($firstTray['capacity'] ?? 0);
        } else {
            $rawCapacity = (int) ($bed['traycapacity'] ?? 0);
        }

        $capacity = $rawCapacity * (int) ($bed['layercount'] ?? 1);

        $bedUsage = $usageMap[$bedNo] ?? ['total' => 0, 'products' => []];
        $used = $bedUsage['total'];
        $remaining = max(0, $capacity - $used);

        $totalCapacity += $capacity;
        $totalUsed += $used;

        $mappedBeds[] = [
            "bed_no" => $bedNo,
            "total_capacity" => $capacity,
            "used_trays" => $used,
            "remaining_trays" => $remaining,
            "products" => $bedUsage['products'],
            "traycapacity" => $bed['traycapacity'] ?? 0,
            "layercount" => $bed['layercount'] ?? 1
        ];
    }

    // 4. Get Allocated but not Received Trays
    $sqlAllocated = "
        SELECT SUM(traycountinbox) as allocated_count
        FROM krf_polyhouse_germinated_boxes
        WHERE polyhouseid = ? AND projectid = ? AND status = 'Allocated'
    ";
    $astmt = $conn->prepare($sqlAllocated);
    $astmt->bind_param("is", $polyhouseid, $projectid);
    $astmt->execute();
    $aRes = $astmt->get_result()->fetch_assoc();
    $allocatedCount = (int) ($aRes['allocated_count'] ?? 0);
    $astmt->close();

    return [
        "polyhouseid" => (int) $ph['polyhouseid'],
        "polyhousename" => $ph['polyhousename'],
        "structure_type" => $ph['structure_type'],
        "total_tray_capacity" => $totalCapacity,
        "total_tray_exists" => $totalUsed,
        "allocated_not_received" => $allocatedCount,
        "remaining_total_tray" => max(0, $totalCapacity - $totalUsed - $allocatedCount),
        "bed_wise_details" => $mappedBeds
    ];
}



function saveOrUpdatePolyhouseAllocation($conn, $data)
{
    $conn->begin_transaction();
    try {
        foreach ($data['tableData'] as $row) {
            $projectid = $data['projectid'];
            $stockorderno = $data['stockorderno'];
            $polyhouseid = $row['polyhouseid'];
            $qrcode = $row['qrcode'];
            $traycount = $row['traycountinbox'];
            $user = $data['created_by'];
            // ?? Check duplicate QR allocation
            $check = $conn->prepare("
                SELECT boxid FROM krf_polyhouse_germinated_boxes
                WHERE qrcode = ? AND status != 'Returned'
            ");
            $check->bind_param("s", $qrcode);
            $check->execute();
            $res = $check->get_result();
            if ($res->num_rows > 0) {
                throw new Exception("QR Code $qrcode is already allocated.");
            }
            // ? Insert allocation
            $stmt = $conn->prepare("
                INSERT INTO krf_polyhouse_germinated_boxes
                (projectid, stockorderno, qrcode, traycountinbox, polyhouseid,
                 status, created_by, received_by)
                VALUES (?,?,?,?,?,'PendingAllocation',?,?)
            ");
            $stmt->bind_param(
                "issiiis",
                $projectid,
                $stockorderno,
                $qrcode,
                $traycount,
                $polyhouseid,
                $user,
                $user
            );
            if (!$stmt->execute()) {
                throw new Exception("Insert failed for QR $qrcode");
            }
        }
        $conn->commit();
        return [
            "status" => "success",
            "message" => "Polyhouse allocation saved successfully"
        ];
    } catch (Exception $e) {
        $conn->rollback();
        return [
            "status" => "error",
            "message" => $e->getMessage()
        ];
    }
}

function reassignPolyhouse($conn, $boxid, $newPolyhouseId, $user)
{
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("
            UPDATE krf_polyhouse_germinated_boxes
            SET polyhouseid = ?, received_by = ?, status = 'Allocated'
            WHERE boxid = ?
        ");
        $stmt->bind_param("isi", $newPolyhouseId, $user, $boxid);
        if (!$stmt->execute()) {
            throw new Exception("Failed to reassign polyhouse");
        }
        $conn->commit();
        return ["status" => "success", "message" => "Polyhouse reassigned successfully"];
    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

function generateUniqueQRCode_DB($conn, $table, $column, $prefix = 'KRF')
{
    do {
        $date = date('Ymd');
        $random = strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
        $qrcode = "{$prefix}{$random}";

        $stmt = $conn->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->bind_param("s", $qrcode);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

    } while ($count > 0);

    return $qrcode;
}

function generateUniqueBoxBatchNo($conn)
{
    do {
        $batchNo = (string)random_int(100000, 999999);

        $stmt = $conn->prepare("SELECT COUNT(*) FROM krf_polyhouse_germinated_boxes WHERE boxbatchno = ?");
        $stmt->bind_param("s", $batchNo);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

    } while ($count > 0);

    return $batchNo;
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

function generate4DigitFromUUID()
{
    $uuid = bin2hex(random_bytes(16)); // UUID-like random string
    $digits = preg_replace('/\D/', '', $uuid);
    return str_pad(substr($digits, 0, 4), 4, '0', STR_PAD_RIGHT);
}



function updateProductionOrderSowingLocation($conn, $stockorder_no, $sowinglocationid, $projectid, $updated_by, $roottype = null, $sowingschedulid = null)
{
    try {
        if (empty($stockorder_no) || empty($projectid)) {
            throw new Exception("Stock Order No and Project ID are required.");
        }

        // 1. Update krf_production_stockorder
        $sql = "UPDATE krf_production_stockorder 
                SET sowinglocationid = ?, updated_at = NOW(), created_by = ? 
                WHERE stockorder_no = ? AND projectid = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ssss", $sowinglocationid, $updated_by, $stockorder_no, $projectid);
        $exec = $stmt->execute();
        $stmt->close();

        if (!$exec) {
            throw new Exception("Execute failed: " . $conn->error);
        }

        // 2. Update locationid in krf_polyhouse_sowingschedule as per sowingschedulid / roottype / stockorder_no
        if (!empty($sowingschedulid) && intval($sowingschedulid) > 0) {
            $upd_sched_sql = "UPDATE krf_polyhouse_sowingschedule 
                              SET locationid = ?, updated_by = ?, updated_at = NOW() 
                              WHERE sowingschedulid = ? AND projectid = ?";
            $stmt_sched = $conn->prepare($upd_sched_sql);
            if ($stmt_sched) {
                $sched_id = intval($sowingschedulid);
                $stmt_sched->bind_param("ssii", $sowinglocationid, $updated_by, $sched_id, $projectid);
                $stmt_sched->execute();
                $stmt_sched->close();
            }
        } elseif (!empty($roottype)) {
            $upd_sched_sql = "UPDATE krf_polyhouse_sowingschedule 
                              SET locationid = ?, updated_by = ?, updated_at = NOW() 
                              WHERE stockorder_no = ? AND projectid = ? AND LOWER(IFNULL(roottype,'root')) = LOWER(?)";
            $stmt_sched = $conn->prepare($upd_sched_sql);
            if ($stmt_sched) {
                $stmt_sched->bind_param("sssss", $sowinglocationid, $updated_by, $stockorder_no, $projectid, $roottype);
                $stmt_sched->execute();
                $stmt_sched->close();
            }
        } else {
            $upd_sched_sql = "UPDATE krf_polyhouse_sowingschedule 
                              SET locationid = ?, updated_by = ?, updated_at = NOW() 
                              WHERE stockorder_no = ? AND projectid = ?";
            $stmt_sched = $conn->prepare($upd_sched_sql);
            if ($stmt_sched) {
                $stmt_sched->bind_param("ssss", $sowinglocationid, $updated_by, $stockorder_no, $projectid);
                $stmt_sched->execute();
                $stmt_sched->close();
            }
        }

        return ["status" => "success", "message" => "Sowing location updated successfully."];
    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    }
}


function createResowingProductionOrder(
    $conn,
    $companyid,
    $subcompanyid,
    $projectid,
    $productid,
    $quantity,
    $clientid,
    $loginid,
    $bufferdays = 7,
    $roottype = null,
    $seedid = null,
    $resowingagainst_stockorderno = null
) {

    // 🔹 1. Validate
    if (empty($projectid) || empty($productid) || $quantity <= 0) {
        return [
            "status" => "error",
            "message" => "Invalid input data"
        ];
    }

    // 🔹 2. Get cycle summary
    $cycle = getProductionCycleSummary($conn, $productid, $projectid, $bufferdays);

    if (empty($cycle)) {
        return [
            "status" => "error",
            "message" => "Unable to calculate production cycle"
        ];
    }

    $expecteddelivery = $cycle[0]['expecteddeliverydate'];

    // 🔹 3. Get product details
    $productName = "";

    if (!empty($seedid)) {

        if (strtolower($roottype) == "root") {
            $sql = "SELECT ProductID, ProductName 
                    FROM product_master 
                    WHERE ProjectId=? AND SeedId=? LIMIT 1";
        } else {
            $sql = "SELECT ProductID, ProductName 
                    FROM product_master 
                    WHERE ProjectId=? AND ScionSeedId=? LIMIT 1";
        }

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $projectid, $seedid);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $productid = $row['ProductID'];
            $productName = $row['ProductName'];
        }
    }

    // 🔹 4. Prepare remarks
    $remarkstr = "Auto Resowing Order";
    if ($roottype) $remarkstr .= " | RootType: $roottype";
    if ($seedid) $remarkstr .= " | SeedID: $seedid";
    if ($resowingagainst_stockorderno) $remarkstr .= " | Against: $resowingagainst_stockorderno";

    // 🔹 5. Check existing resowing order
    $id = "";
    $newstockorderno = "";

    if (!empty($resowingagainst_stockorderno)) {

        $stmt = $conn->prepare("
            SELECT id, newstockorderno 
            FROM krf_resowing_production_order 
            WHERE projectid=? AND resowingagainst_stockorderno=? 
            LIMIT 1
        ");

        $stmt->bind_param("ss", $projectid, $resowingagainst_stockorderno);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $id = $row['id'];
            $newstockorderno = $row['newstockorderno'];
        }
    }

    // 🔹 6. Generate new stock order if not found
    if (empty($newstockorderno)) {
        $newstockorderno = !empty($production_stockorder_no)
            ? $production_stockorder_no
            : getNewProductionStockOrderNo($conn, $companyid, $projectid);
    }

    // 🔹 7. Prepare data
    // echo $productid;

    $data = [
        "id" => $id,
        "resowingagainst_stockorderno" => $resowingagainst_stockorderno,
        "newstockorderno" => $newstockorderno,
        "projectid" => $projectid,
        "companyid" => $companyid,
        "subcompanyid" => $subcompanyid,
        "clientid" => $clientid,
        "productid" => $productid,
        "product_name" => $productName,
        "roottype" => $roottype,
        "seedid" => $seedid,
        "quantity" => (float)$quantity,
        "expected_delivery_date" => $expecteddelivery,
        "remarks" => $remarkstr,
        "status" => "Confirmed",
        "created_by" => $loginid
    ];

    // 🔹 8. Save
    $svdata = saveProductionStockOrderForResowing($conn, $data, false);
    // 🔹 9. Return response
    return [
        "status" => "success",
        "message" => "Resowing production order created",
        "stockorder_no" => $newstockorderno,
        "data" => $svdata
    ];
}

function getModifiedDate($date, $days = 0, $type = 'add') {
    // $type add/subtract
    // Validate date
    if (!$date || !strtotime($date)) {
        return false;
    }

    // Create DateTime object
    $dt = new DateTime($date);

    // Modify date based on type
    if ($type === 'add') {
        $dt->modify("+$days days");
    } elseif ($type === 'subtract') {
        $dt->modify("-$days days");
    } else {
        return false;
    }

    // Return formatted date (Y-m-d)
    return $dt->format('Y-m-d');
}

function getGerminatedBoxesQRCodeListByBatch($conn, $projectid, $stockorderno, $roottype)
{
    $retval = [];
    $sql = "SELECT boxid, roottype, boxbatchno, boxbatchdate, productid, qrcode, boxpackingdate, boxopeningdate, traycountinbox, status 
            FROM krf_polyhouse_germinated_boxes 
            WHERE projectid = ? AND stockorderno = ? AND roottype = ? AND (tedit IS NULL OR tedit = '')";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return $retval;
    }

    $projectid = (int)$projectid;
    $stmt->bind_param("iss", $projectid, $stockorderno, $roottype);
    $stmt->execute();
    $result = $stmt->get_result();

    $grouped = [];
    while ($row = $result->fetch_assoc()) {
        $batchNo = !empty($row['boxbatchno']) ? $row['boxbatchno'] : 'unbatched';
        $batchDate = $row['boxbatchdate'] ?? '';
        $productId = $row['productid'] ?? '';
        $qrcode = $row['qrcode'] ?? '';
        $packingDate = $row['boxpackingdate'] ?? '';
        $openingDate = $row['boxopeningdate'] ?? '';
        $trayCount = $row['traycountinbox'] ?? '';
        $boxStatus = trim((string)($row['status'] ?? ''));
        $groupKey = $batchNo . "_" . $batchDate;
        $seeddetails = getSeedDetailspassingpassing($conn, $projectid, $row['productid'], $roottype);
        $seedId = $seeddetails['SeedID'];
        $SeedName = $seeddetails['SeedName'];

        if (!isset($grouped[$groupKey])) {
            $grouped[$groupKey] = [
                "boxbatchno" => $batchNo,
                "boxbatchdate" => $batchDate,
                "packingdate" => $packingDate,
                "openingdate" => $openingDate,
                "productid" => $productId,
                "seedId" => $seedId,
                "SeedName" => $SeedName,
                "roottype" => $row['roottype'] ?? $roottype,
                "can_remove" => ($batchNo !== 'unbatched'),
                "statuses" => [],
                "boxes" => []
            ];
        }

        if (!empty($boxStatus)) {
            $grouped[$groupKey]["statuses"][] = $boxStatus;
            $boxStatusClean = strtolower(str_replace([' ', '_', '-'], '', $boxStatus));
            // Only allow removal if box is in PendingAllocation or ReceivedAfterSowing
            if (!in_array($boxStatusClean, ['pendingallocation', 'receivedaftersowing'])) {
                $grouped[$groupKey]["can_remove"] = false;
            }
        }

        if (!empty($qrcode)) {
            $grouped[$groupKey]["boxes"][] = [
                "boxid" => $row['boxid'] ?? null,
                "qrcode" => $qrcode,
                "boxpackingdate" => $packingDate,
                "boxopeningdate" => $openingDate,
                "traycountinbox" => $trayCount,
                "status" => $boxStatus
            ];
        }
    }

    foreach ($grouped as &$g) {
        if (empty($g['boxes']) || $g['boxbatchno'] === 'unbatched') {
            $g['can_remove'] = false;
        }
        $uniqueStatuses = array_unique($g['statuses']);
        $g['batch_status'] = !empty($uniqueStatuses) ? implode(', ', $uniqueStatuses) : 'PendingAllocation';
    }
    unset($g);

    $stmt->close();
    return array_values($grouped);
}
?>
