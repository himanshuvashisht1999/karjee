<?php
// Prevent any implicit output
ob_start();

// Configure error handling
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);

header('Content-Type: application/json');

// Final shutdown handler to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    // Allow clean exit, but catch fatals
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        // discard current buffer
        ob_clean();
        echo json_encode([
            "status" => "error",
            "message" => "Critical Error: " . $error['message'] . " in " . basename($error['file']) . ":" . $error['line']
        ]);
    }
});

try {
    // 1. Establish Paths
    // Assuming file is in C:/xampp/htdocs/krf_amanaccounting/krf-orderandsales/modal/import_seed_excel.php
    // We want to reach C:/xampp/htdocs/krf_amanaccounting/

    // Calculate Project Root
    $currentDir = __DIR__; // .../modal
    $moduleDir = dirname($currentDir); // .../krf-orderandsales
    $projectRoot = dirname($moduleDir); // .../krf_amanaccounting

    $paths = [
        'connection' => $projectRoot . '/connection.php',
        'xlsx' => $projectRoot . '/SimpleXLSX.php',
        'xls' => $projectRoot . '/SimpleXLS.php',
        'seed_functions' => $moduleDir . '/functions/seed_functions.php'
    ];

    // 2. Validate & Include Dependencies
    foreach ($paths as $key => $path) {
        if (!file_exists($path)) {
            throw new Exception("Missing required file: " . basename($path));
        }
    }

    // Include Connection
    require_once $paths['connection'];
    if (!isset($krfconn) || !$krfconn) {
        throw new Exception("Database connection failed or not initialized.");
    }

    // Include Libraries
    require_once $paths['xlsx'];
    if (file_exists($paths['xls'])) {
        require_once $paths['xls'];
    }

    // Include Business Logic
    require_once $paths['seed_functions'];


    // 3. Helper Functions (Local Scoped)
    if (!function_exists('getCategoryIdFuzzyLocal')) {
        function getCategoryIdFuzzyLocal($conn, $nameValue, $projectId)
        {
            // Exact
            $sql = "SELECT CategoryID FROM plant_category_master WHERE CategoryName = ? AND ProjectId = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $nameValue, $projectId);
            $stmt->execute();
            $stmt->bind_result($id);
            if ($stmt->fetch()) {
                $stmt->close();
                return $id;
            }
            $stmt->close();

            // Fuzzy
            $searchTerm = "%" . $nameValue . "%";
            $sql = "SELECT CategoryID FROM plant_category_master WHERE CategoryName LIKE ? AND ProjectId = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $searchTerm, $projectId);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 1) {
                $stmt->bind_result($id);
                $stmt->fetch();
                $stmt->close();
                return $id;
            }
            $stmt->close();
            return null;
        }
    }

    if (!function_exists('getSubCategoryIdOrInsertLocal')) {
        function getSubCategoryIdOrInsertLocal($conn, $nameValue, $categoryId, $projectId, $createdBy)
        {
            if (empty($nameValue))
                return null;
            // Check Exist
            $sql = "SELECT SubCategoryID FROM plant_subcategory_master WHERE SubCategoryName = ? AND ProjectId = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $nameValue, $projectId);
            $stmt->execute();
            $stmt->bind_result($id);
            if ($stmt->fetch()) {
                $stmt->close();
                return $id;
            }
            $stmt->close();

            // Insert
            $cleanName = strtoupper(preg_replace('/\s+/', '', $nameValue));
            $sku = substr($cleanName, 0, 3) . rand(100, 999);

            $insertSql = "INSERT INTO plant_subcategory_master (sku, SubCategoryName, CategoryID, ProjectId, Status, CreatedAt, CreatedBy) VALUES (?, ?, ?, ?, 'Active', NOW(), ?)";
            $stmt = $conn->prepare($insertSql);
            $stmt->bind_param("sssss", $sku, $nameValue, $categoryId, $projectId, $createdBy);
            if ($stmt->execute()) {
                $newId = $stmt->insert_id;
                $stmt->close();
                return $newId;
            }
            $stmt->close();
            return null;
        }
    }

    if (!function_exists('getVendorIDByNameLocal')) {
        function getVendorIDByNameLocal($conn, $nameValue)
        {
            $id = null;
            $sql = "SELECT VendorID FROM vendor_master WHERE VendorName = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt)
                return null;
            $stmt->bind_param("s", $nameValue);
            $stmt->execute();
            $stmt->bind_result($id);
            $stmt->fetch();
            $stmt->close();
            return $id;
        }
    }

    // 4. Request Processing
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method.");
    }

    if (!isset($_FILES['excelFile']) || $_FILES['excelFile']['error'] !== 0) {
        throw new Exception("No file uploaded or upload error.");
    }

    $fileTmpPath = $_FILES['excelFile']['tmp_name'];
    $fileName = $_FILES['excelFile']['name'];
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    $dataRows = [];

    if ($ext === 'xlsx') {
        if (class_exists('SimpleXLSX')) {
            if ($xlsx = SimpleXLSX::parse($fileTmpPath)) {
                $rows = $xlsx->rows();
                $dataRows = array_slice($rows, 1);
            } else {
                throw new Exception("Parse error: " . SimpleXLSX::parseError());
            }
        } else {
            throw new Exception("SimpleXLSX class missing.");
        }
    } elseif ($ext === 'xls') {
        if (class_exists('SimpleXLS')) {
            if ($xls = SimpleXLS::parse($fileTmpPath)) {
                $rows = $xls->rows();
                $dataRows = array_slice($rows, 1);
            } else {
                throw new Exception("Parse error: " . SimpleXLS::parseError());
            }
        } else {
            throw new Exception("SimpleXLS class missing.");
        }
    } elseif ($ext === 'csv') {
        if (($handle = fopen($fileTmpPath, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $dataRows[] = $data;
            }
            fclose($handle);
            array_shift($dataRows); // Remove header
        } else {
            throw new Exception("Cannot read CSV.");
        }
    } else {
        throw new Exception("Invalid format. Allowed: xlsx, xls, csv");
    }

    if (empty($dataRows)) {
        throw new Exception("File is empty.");
    }

    // 5. Data Importer Logic
    $projectId = $_POST['projectid'] ?? '';
    $loginId = $_POST['loginid'] ?? '';

    $successCount = 0;
    $errors = [];
    $rowNum = 1;

    foreach ($dataRows as $row) {
        $rowNum++;

        // Map columns
        $categoryName = trim($row[0] ?? '');
        $subCategoryName = trim($row[1] ?? ''); // Crop
        $typeOfSeed = trim($row[2] ?? '');
        $varietyName = trim($row[3] ?? '');
        $brandName = trim($row[4] ?? '');
        $sku = trim($row[5] ?? '');
        $companyName = trim($row[6] ?? '');
        $vendorName = trim($row[7] ?? '');
        $weight = trim($row[8] ?? '');
        $count = trim($row[9] ?? '');
        $remark = trim($row[10] ?? '');
        $photo = trim($row[11] ?? '');

        if (empty($categoryName) && empty($varietyName))
            continue;

        // A. Category Lookup
        $catId = getCategoryIdFuzzyLocal($krfconn, $categoryName, $projectId);
        if (!$catId) {
            $errors[] = "Row $rowNum: Category '$categoryName' not found.";
            continue;
        }

        // B. Crop Lookup/Insert
        $subCatId = null;
        if (!empty($subCategoryName)) {
            $subCatId = getSubCategoryIdOrInsertLocal($krfconn, $subCategoryName, $catId, $projectId, $loginId);
            if (!$subCatId) {
                // If failed, log but maybe continue? 
                $errors[] = "Row $rowNum: Could not find/create Crop '$subCategoryName'.";
                continue;
            }
        }

        // C. Vendor
        $vendorId = null;
        if (!empty($vendorName)) {
            $vendorId = getVendorIDByNameLocal($krfconn, $vendorName);
        }

        // D. Save Seed
        // Check local SKU duplicate first
        $existingId = null;
        if (!empty($sku)) {
            $check = $krfconn->prepare("SELECT SeedID FROM seed_master WHERE sku = ? AND ProjectId = ?");
            if ($check) {
                $check->bind_param("ss", $sku, $projectId);
                $check->execute();
                $check->bind_result($existingId);
                $check->fetch();
                $check->close();
            }
        }

        if ($existingId) {
            // Update
            $upd = $krfconn->prepare("UPDATE seed_master SET CategoryID=?, SubCategoryID=?, TypeOfSeed=?, VarietyofSeed=?, BrandName=?, CompanyName=?, VendorID=?, WeightPerPacket=?, CountPerPackate=?, Description=?, MainPhotoPath=?, UpdateAt=NOW() WHERE SeedID=?");
            $upd->bind_param("ssssssssssss", $catId, $subCatId, $typeOfSeed, $varietyName, $brandName, $companyName, $vendorId, $weight, $count, $remark, $photo, $existingId);
            if ($upd->execute())
                $successCount++;
            else
                $errors[] = "Row $rowNum update failed: " . $upd->error;
            $upd->close();
        } else {
            // Insert
            // Check logical duplicate
            $dup = $krfconn->prepare("SELECT SeedID FROM seed_master WHERE CategoryID=? AND SubCategoryID=? AND VarietyofSeed=? AND TypeOfSeed=? AND BrandName=? AND ProjectId=? LIMIT 1");
            $dup->bind_param("ssssss", $catId, $subCatId, $varietyName, $typeOfSeed, $brandName, $projectId);
            $dup->execute();
            $dup->store_result();
            if ($dup->num_rows > 0) {
                $dup->close();
                $errors[] = "Row $rowNum: Duplicate seed skipped.";
                continue;
            }
            $dup->close();

            if (empty($sku)) {
                $sku = 'AUTO-' . time() . rand(100, 999);
            }
            $seedName = $subCategoryName . " " . $varietyName;

            $ins = $krfconn->prepare("INSERT INTO seed_master (CategoryID, SubCategoryID, TypeOfSeed, VarietyofSeed, sku, SeedName, BrandName, CompanyName, VendorID, WeightPerPacket, CountPerPackate, Description, MainPhotoPath, ProjectId, CreatedBy, Status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'Active')");
            $ins->bind_param("sssssssssssssss", $catId, $subCatId, $typeOfSeed, $varietyName, $sku, $seedName, $brandName, $companyName, $vendorId, $weight, $count, $remark, $photo, $projectId, $loginId);
            if ($ins->execute())
                $successCount++;
            else
                $errors[] = "Row $rowNum insert failed: " . $ins->error;
            $ins->close();
        }
    }

    // Output JSON
    $output = ob_get_clean(); // Discard any noise
    echo json_encode([
        "status" => "success",
        "message" => "Imported $successCount records.",
        "errors" => $errors
    ]);


} catch (Throwable $t) { // Catch Errors and Exceptions
    if (ob_get_length())
        ob_clean();
    echo json_encode([
        "status" => "error",
        "message" => $t->getMessage() . " in " . basename($t->getFile()) . ":" . $t->getLine()
    ]);
}
?>