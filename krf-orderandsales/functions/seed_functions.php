<?php
function saveSeedMaster($conn, $data, $mainPhotoPath, $otherPhotosSerialized)
{
    $CategoryID = trim($data['SeedCategory']);
    $SubCategoryID = trim($data['SubSeedCategory']);
    $SeedID = $data['SeedID'] ?? null;
    if (!empty($data['SeedName'])) {
        $SeedName = trim($data['SeedName']);
    } else {
        $subCatName = '';
        $scQ = $conn->prepare("SELECT SubCategoryName FROM plant_subcategory_master WHERE SubCategoryID = ? LIMIT 1");
        $scQ->bind_param('s', $SubCategoryID);
        $scQ->execute();
        $scQ->bind_result($subCatName);
        $scQ->fetch();
        $scQ->close();
        $SeedName = trim(($subCatName ? $subCatName . ' ' : '') . ($data['VarietyName'] ?? ''));
        if (empty($SeedName)) {
            $SeedName = trim($data['BrandName'] ?? 'Seed');
        }
    }

    $TypeOfSeed = trim($data['TypeOfSeed']);
    $VarietyName = trim($data['VarietyName']);
    $BrandName = trim($data['BrandName'] ?? '');
    $CompanyName = trim($data['CompanyName'] ?? '');
    $WeightPerPacket = trim($data['SeedWeightPerPacket'] ?? '0');
    $CountPerPacket = trim($data['SeedCountPerPacket'] ?? '0');
    $growingdays = trim($data['growingdays'] ?? '0');


    $Description = trim($data['Description'] ?? '');
    $VendorID = trim($data['VendorID'] ?? '');
    $suggestedsku = trim($data['suggestedsku'] ?? '');
    $Status = $data['Status'] ?? 'Active';
    $CreatedBy = trim($data['loginid'] ?? '');
    $ProjectId = trim($data['projectid'] ?? '');

    if (empty($SeedName) || empty($CategoryID)) {
        return ["status" => "error", "message" => "Seed Name and Category are mandatory."];
    }

    $conn->begin_transaction();

    try {
        if (!empty($SeedID)) {
            // UPDATE seed
            $sql = "UPDATE seed_master SET 
                        SeedName = ?, 
                        CategoryID = ?, 
                        SubCategoryID = ?,
                        TypeOfSeed = ?, 
                        VarietyofSeed = ?, 
                        sku = ?, 
                        BrandName = ?, 
                        CompanyName = ?, 
                        VendorID = ?, 
                        Description = ?, 
                        Status = ?,
                        WeightPerPacket = ?, 
                        CountPerPackate = ?, 
                        ProjectId = ?, 
                        MainPhotoPath = ?, 
                        OtherPhotoPaths = ?,
                        growingdays = ?, 
                        UpdateAt = NOW() 
                    WHERE SeedID = ?";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssssssssss",
                $SeedName,
                $CategoryID,
                $SubCategoryID,
                $TypeOfSeed,
                $VarietyName,
                $suggestedsku,
                $BrandName,
                $CompanyName,
                $VendorID,
                $Description,
                $Status,
                $WeightPerPacket,
                $CountPerPacket,
                $ProjectId,
                $mainPhotoPath,
                $otherPhotosSerialized,
                $growingdays,
                $SeedID
            );

            $stmt->execute();
            $stmt->close();

            $conn->commit();
            return ["status" => "success", "message" => "Seed updated successfully.", "sku" => $suggestedsku];
        } else {
            // INSERT seed
            if ($suggestedsku) {
                // Verify uniqueness of suggestedsku
                $chkCount = 0;
                $chkStmt = $conn->prepare("SELECT COUNT(*) FROM seed_master WHERE sku = ?");
                $chkStmt->bind_param('s', $suggestedsku);
                $chkStmt->execute();
                $chkStmt->bind_result($chkCount);
                $chkStmt->fetch();
                $chkStmt->close();
                if ($chkCount > 0) {
                    $skuResult = generateSeedSKU($conn, $ProjectId, $CategoryID, $SubCategoryID, $VarietyName, $TypeOfSeed, $BrandName);
                    $sku = $skuResult['sku'];
                } else {
                    $sku = $suggestedsku;
                }
            } else {
                $skuResult = generateSeedSKU($conn, $ProjectId, $CategoryID, $SubCategoryID, $VarietyName, $TypeOfSeed, $BrandName);
                if ($skuResult['status'] === 'error') {
                    throw new Exception($skuResult['message']);
                }
                $sku = $skuResult['sku'];
            }

            $sql = "INSERT INTO seed_master 
                    (SeedName, CategoryID, SubCategoryID, TypeOfSeed, VarietyofSeed, sku, BrandName, CompanyName, VendorID, Description, Status, WeightPerPacket, CountPerPackate, CreatedBy, ProjectId, MainPhotoPath, OtherPhotoPaths,growingdays) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param(
                "ssssssssssssssssss",
                $SeedName,
                $CategoryID,
                $SubCategoryID,
                $TypeOfSeed,
                $VarietyName,
                $sku,
                $BrandName,
                $CompanyName,
                $VendorID,
                $Description,
                $Status,
                $WeightPerPacket,
                $CountPerPacket,
                $CreatedBy,
                $ProjectId,
                $mainPhotoPath,
                $otherPhotosSerialized,
                $growingdays
            );

            $stmt->execute();
            $newSeedID = $stmt->insert_id;
            $stmt->close();

            $conn->commit();
            return [
                "status" => "success",
                "message" => "Seed details saved successfully.",
                "SeedID" => $newSeedID,
                "sku" => $sku
            ];
        }
    } catch (Exception $e) {
        $conn->rollback();
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

// function getSeedData($conn, $filters = [], $sortby = null, $sortorder = "ASC", $limit = null, $offset = null)
// {
//     $data = [];

//     // Column types: s = string, i = int
//     $allowedColumns = [
//         "SeedID" => "i",
//         "growingdays" => "i",
//         "CategoryID" => "s",
//         "SubCategoryID" => "s",
//         "VarietyofSeed" => "s",
//         "sku" => "s",
//         "SeedName" => "s",
//         "BrandName" => "s",
//         "CompanyName" => "s",
//         "VendorID" => "s",
//         "Description" => "s",
//         "MainPhotoPath" => "s",
//         "OtherPhotoPaths" => "s",
//         "ProjectId" => "s",
//         "CreatedBy" => "s",
//         "Status" => "s"
//     ];

//     // Columns that support partial match (LIKE)
//     $likeColumns = ["SeedName", "BrandName", "CompanyName"];

//     try {
//         $sql = "SELECT s.*, v.VendorName FROM seed_master s 
//                 LEFT JOIN vendor_master v ON s.VendorID = v.VendorID 
//                 WHERE 1=1";
//         $params = [];
//         $types = "";

//         // Apply filters
//         foreach ($filters as $column => $value) {
//             if($column=="all" || $value=="all"){

//             }else if (isset($allowedColumns[$column]) && $value !== null) {
//                 if (in_array($column, $likeColumns)) {
//                     $sql .= " AND s.$column LIKE ?";
//                     $params[] = "%" . $value . "%";
//                     $types .= "s";
//                 } elseif ($column == "ProjectId") {
//                     $sql .= " AND s.ProjectId = ?";
//                     $params[] = $value;
//                     $types .= "s";
//                 } else {
//                     $sql .= " AND s.$column = ?";
//                     $params[] = $value;
//                     $types .= $allowedColumns[$column];
//                 }
//             }
//         }

//         // Sorting
//         if ($sortby && isset($allowedColumns[$sortby])) {
//             $sortorder = strtoupper($sortorder) === "DESC" ? "DESC" : "ASC";
//             $sql .= " ORDER BY s.$sortby $sortorder";
//         }

//         // Pagination
//         if ($limit !== null && is_numeric($limit)) {
//             $sql .= " LIMIT ?";
//             $params[] = intval($limit);
//             $types .= "i";

//             if ($offset !== null && is_numeric($offset)) {
//                 $sql .= " OFFSET ?";
//                 $params[] = intval($offset);
//                 $types .= "i";
//             }
//         }

//         $stmt = $conn->prepare($sql);
//         if (!$stmt) {
//             throw new Exception("SQL Prepare failed: " . $conn->error);
//         }

//         if (!empty($params)) {
//             $stmt->bind_param($types, ...$params);
//         }

//         $stmt->execute();
//         $result = $stmt->get_result();

//         while ($row = $result->fetch_assoc()) {
//             $data[] = $row;
//         }

//         $stmt->close();
//     } catch (Exception $e) {
//         error_log("Error fetching seed data: " . $e->getMessage());
//         return [];
//     }

//     return $data;
// }

function getSeedData($conn, $filters = [], $sortby = null, $sortorder = "ASC", $limit = null, $offset = null)
{
    $data = [];

    $allowedColumns = [
        "SeedID" => "i",
        "growingdays" => "i",
        "CategoryID" => "s",
        "SubCategoryID" => "s",
        "VarietyofSeed" => "s",
        "sku" => "s",
        "SeedName" => "s",
        "BrandName" => "s",
        "CompanyName" => "s",
        "VendorID" => "s",
        "Description" => "s",
        "MainPhotoPath" => "s",
        "OtherPhotoPaths" => "s",
        "ProjectId" => "s",
        "CreatedBy" => "s",
        "Status" => "s"
    ];

    $likeColumns = ["SeedName", "BrandName", "CompanyName"];

    try {

        $sql = "SELECT s.*, v.VendorName FROM seed_master s LEFT JOIN vendor_master v ON s.VendorID = v.VendorID
                WHERE 1=1"; $params = [];
        $types = "";

        // ✅ If filter is ['all' => 'all'], skip filtering
        if (!(count($filters) == 1 && isset($filters['all']) && $filters['all'] == 'all')) {

            foreach ($filters as $column => $value) {

                // Skip invalid or 'all' values
                if (!isset($allowedColumns[$column]) || $value === null || $value === "" || strtolower($value) === "all") {
                    continue;
                }

                if (in_array($column, $likeColumns)) {
                    $sql .= " AND s.$column LIKE ?";
                    $params[] = "%" . $value . "%";
                    $types .= "s";
                } else {
                    $sql .= " AND s.$column = ?";
                    $params[] = $value;
                    $types .= $allowedColumns[$column];
                }
            }
        }

        // ✅ Sorting
        if ($sortby && isset($allowedColumns[$sortby])) {
            $sortorder = strtoupper($sortorder) === "DESC" ? "DESC" : "ASC";
            $sql .= " ORDER BY s.$sortby $sortorder";
        }

        // ✅ Pagination
        if ($limit !== null && is_numeric($limit)) {
            $sql .= " LIMIT ?";
            $params[] = intval($limit);
            $types .= "i";

            if ($offset !== null && is_numeric($offset)) {
                $sql .= " OFFSET ?";
                $params[] = intval($offset);
                $types .= "i";
            }
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $conn->error);
        }

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

    } catch (Exception $e) {
        error_log("Error fetching seed data: " . $e->getMessage());
        return [];
    }

    return $data;
}

function getOnlySeedfullDetails($conn, $projectid, $seedid)
{
    $data = [];

    try {

        $sql = "SELECT s.*, v.VendorName 
                FROM seed_master s
                LEFT JOIN vendor_master v 
                    ON s.VendorID = v.VendorID
                WHERE s.ProjectId = ?
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



function generateSeedSKU($conn, $ProjectId, $CategoryID, $SubCategoryID, $VarietyName, $TypeOfSeed, $BrandName, $SeedID = null)
{
    // 1. Get Category SKU
    $catsku = null;
    $ctskuq = $conn->prepare("SELECT sku, CategoryName FROM plant_category_master WHERE CategoryID = ?");
    $ctskuq->bind_param('s', $CategoryID);
    $ctskuq->execute();
    $ctskuq->bind_result($c_sku, $c_name);
    if ($ctskuq->fetch()) {
        $catsku = !empty($c_sku) ? $c_sku : substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $c_name ?: $CategoryID)), 0, 5);
    }
    $ctskuq->close();

    if (empty($catsku)) {
        $catsku = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $CategoryID)), 0, 5);
    }

    // 2. Get Sub-Category SKU
    $subcatsku = null;
    $subName = '';
    // First try with SubCategoryID + CategoryID
    $subctskuq = $conn->prepare("SELECT sku, SubCategoryName FROM plant_subcategory_master WHERE SubCategoryID = ? AND CategoryID = ?");
    $subctskuq->bind_param('ss', $SubCategoryID, $CategoryID);
    $subctskuq->execute();
    $subctskuq->bind_result($sc_sku, $sc_name);
    if ($subctskuq->fetch()) {
        $subcatsku = $sc_sku;
        $subName = $sc_name;
    }
    $subctskuq->close();

    // Fallback: search by SubCategoryID alone (primary key)
    if (empty($subcatsku)) {
        $subctskuq2 = $conn->prepare("SELECT sku, SubCategoryName FROM plant_subcategory_master WHERE SubCategoryID = ?");
        $subctskuq2->bind_param('s', $SubCategoryID);
        $subctskuq2->execute();
        $subctskuq2->bind_result($sc_sku2, $sc_name2);
        if ($subctskuq2->fetch()) {
            $subcatsku = $sc_sku2;
            $subName = $sc_name2;
        }
        $subctskuq2->close();
    }

    // Fallback: search by SubCategoryName if SubCategoryID was passed as name
    if (empty($subcatsku)) {
        $subctskuq3 = $conn->prepare("SELECT sku, SubCategoryName FROM plant_subcategory_master WHERE SubCategoryName = ? LIMIT 1");
        $subctskuq3->bind_param('s', $SubCategoryID);
        $subctskuq3->execute();
        $subctskuq3->bind_result($sc_sku3, $sc_name3);
        if ($subctskuq3->fetch()) {
            $subcatsku = $sc_sku3;
            $subName = $sc_name3;
        }
        $subctskuq3->close();
    }

    if (empty($subcatsku)) {
        $cleanSub = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $subName ?: (string)$SubCategoryID));
        $subcatsku = substr($cleanSub, 0, 5);
        if (empty($subcatsku)) {
            $subcatsku = "SUB" . ($SubCategoryID ?: '01');
        }
    }

    // 3. Clean Variety SKU part
    $CleanVarietyName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $VarietyName));
    $VRsku = substr($CleanVarietyName, 0, 3);
    if (empty($VRsku)) {
        $VRsku = "VAR";
    }

    // 4. Clean Seed Type part
    $CleanTypeOfSeed = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $TypeOfSeed));
    $SDsku = substr($CleanTypeOfSeed, 0, 3);
    if (empty($SDsku)) {
        $SDsku = "HYB";
    }

    // 5. Clean Brand part
    $CleanBrandName = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $BrandName));
    $BNDsku = substr($CleanBrandName, 0, 3);

    // 6. Construct Base Seed SKU
    $baseSku = $catsku . "-" . $subcatsku . "-" . $VRsku . "-" . $SDsku;
    if (!empty($BNDsku)) {
        $baseSku .= "-" . $BNDsku;
    }

    // 7. Auto Ensure Uniqueness
    $finalSku = $baseSku;
    $suffix = 1;
    while (true) {
        if (!empty($SeedID)) {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM seed_master WHERE sku = ? AND SeedID != ?");
            $stmt->bind_param('si', $finalSku, $SeedID);
        } else {
            $stmt = $conn->prepare("SELECT COUNT(*) FROM seed_master WHERE sku = ?");
            $stmt->bind_param('s', $finalSku);
        }
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0) {
            break;
        }
        $finalSku = $baseSku . "-" . str_pad($suffix, 2, '0', STR_PAD_LEFT);
        $suffix++;
    }

    return [
        "status" => "success",
        "sku" => $finalSku
    ];
}

function deleteSeed($conn, $SeedID)
{
    if (empty($SeedID)) {
        return ["status" => "error", "message" => "Seed ID is required."];
    }

    try {
        $sql = "DELETE FROM seed_master WHERE SeedID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $SeedID);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                return ["status" => "success", "message" => "Seed deleted successfully."];
            } else {
                return ["status" => "error", "message" => "Seed not found or already deleted."];
            }
        } else {
            return ["status" => "error", "message" => "Database error: " . $stmt->error];
        }
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Error deleting seed: " . $e->getMessage()];
    }
}
?>