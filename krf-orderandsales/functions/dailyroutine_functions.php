<?php
// Functions for Daily Routine Plan module

if (!function_exists('fetchSubCategoriesAndSeeds')) {
    /**
     * Fetch all subcategories and their associated seeds for a project/category
     */
    function fetchSubCategoriesAndSeeds($conn, $projectId, $categoryId = null) {
        $subcategories = [];
        $where = "WHERE Status = 'Active'";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $where .= " AND (ProjectId = ? OR ProjectId IS NULL OR ProjectId = '')";
            $params[] = $projectId;
            $types .= "s";
        }
        if (!empty($categoryId)) {
            $where .= " AND CategoryID = ?";
            $params[] = $categoryId;
            $types .= "s";
        }

        $sql = "SELECT SubCategoryID, CategoryID, SubCategoryName, sku, Description FROM plant_subcategory_master $where ORDER BY SubCategoryName ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $subCatId = $row['SubCategoryID'];
                // Fetch seeds for this subcategory
                $seeds = fetchSeedsBySubCategoryIDs($conn, $projectId, [$subCatId]);
                $row['seeds'] = $seeds;
                $subcategories[] = $row;
            }
            $stmt->close();
        }
        return $subcategories;
    }
}

if (!function_exists('fetchSeedsBySubCategoryIDs')) {
    /**
     * Fetch active seeds matching one or more SubCategory IDs
     */
    function fetchSeedsBySubCategoryIDs($conn, $projectId, $subCategoryIdsArray = []) {
        $seeds = [];
        if (empty($subCategoryIdsArray)) {
            return $seeds;
        }

        $placeholders = implode(',', array_fill(0, count($subCategoryIdsArray), '?'));
        $sql = "SELECT SeedID, SeedName, VarietyofSeed, BrandName, CompanyName, CategoryID, SubCategoryID, sku, growingdays, WeightPerPacket, CountPerPackate 
                FROM seed_master 
                WHERE SubCategoryID IN ($placeholders) AND (Status = 'Active' OR Status IS NULL OR Status = '')";

        $types = str_repeat('s', count($subCategoryIdsArray));
        $params = array_values($subCategoryIdsArray);

        if (!empty($projectId)) {
            $sql .= " AND (ProjectId = ? OR ProjectId IS NULL OR ProjectId = '')";
            $types .= "s";
            $params[] = $projectId;
        }

        $sql .= " ORDER BY SeedName ASC, VarietyofSeed ASC";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $seeds[] = $row;
            }
            $stmt->close();
        }
        return $seeds;
    }
}

if (!function_exists('generateRoutinePlanCode')) {
    /**
     * Generate unique Routine Plan Code like DRP-2026-0001
     */
    function generateRoutinePlanCode($conn, $projectId = '') {
        $year = date('Y');
        $prefix = "DRP-" . $year . "-";
        $sql = "SELECT PlanCode FROM krf_dailyroutine_plan_master WHERE PlanCode LIKE ? ORDER BY RoutinePlanID DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $searchPrefix = $prefix . "%";
        $nextNum = 1;

        if ($stmt) {
            $stmt->bind_param('s', $searchPrefix);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $lastCode = $row['PlanCode'];
                $parts = explode('-', $lastCode);
                if (isset($parts[2]) && is_numeric($parts[2])) {
                    $nextNum = intval($parts[2]) + 1;
                }
            }
            $stmt->close();
        }
        return $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('createOrUpdateDailyRoutineMaster')) {
    /**
     * Insert or update Daily Routine Plan Master record
     */
    function createOrUpdateDailyRoutineMaster($conn, $data) {
        $routinePlanId = !empty($data['RoutinePlanID']) ? intval($data['RoutinePlanID']) : 0;
        $planCode = !empty($data['PlanCode']) ? trim($data['PlanCode']) : generateRoutinePlanCode($conn, $data['ProjectId'] ?? '');
        $planName = trim($data['PlanName'] ?? 'Daily Routine Plan');
        $categoryId = trim($data['CategoryID'] ?? '');
        $subCategoryId = !empty($data['SubCategoryID']) ? intval($data['SubCategoryID']) : 0;
        $totalPlanDays = !empty($data['TotalPlanDays']) ? intval($data['TotalPlanDays']) : 25;
        $description = trim($data['Description'] ?? '');
        $projectId = trim($data['ProjectId'] ?? '');
        $companyId = trim($data['CompanyId'] ?? '');
        $subCompanyId = trim($data['SubCompanyId'] ?? '');
        $status = !empty($data['Status']) ? $data['Status'] : 'Active';
        $userId = trim($data['CreatedBy'] ?? 'admin');

        // SubCategoryIDs_JSON and SeedIDs_JSON
        $subCategoryIdsJson = is_array($data['SubCategoryIDs_JSON'] ?? null) 
            ? json_encode($data['SubCategoryIDs_JSON'], JSON_UNESCAPED_UNICODE) 
            : ($data['SubCategoryIDs_JSON'] ?? '[]');

        $seedIdsJson = is_array($data['SeedIDs_JSON'] ?? null) 
            ? json_encode($data['SeedIDs_JSON'], JSON_UNESCAPED_UNICODE) 
            : ($data['SeedIDs_JSON'] ?? '[]');

        // If primary SubCategoryID not provided, extract first from JSON
        if (empty($subCategoryId)) {
            $decodedSubCats = json_decode($subCategoryIdsJson, true);
            if (!empty($decodedSubCats) && is_array($decodedSubCats)) {
                $subCategoryId = intval($decodedSubCats[0]);
            }
        }

        if ($routinePlanId > 0) {
            // Update
            $sql = "UPDATE krf_dailyroutine_plan_master SET 
                    PlanName = ?, 
                    CategoryID = ?, 
                    SubCategoryID = ?, 
                    SubCategoryIDs_JSON = ?, 
                    SeedIDs_JSON = ?, 
                    TotalPlanDays = ?, 
                    Description = ?, 
                    Status = ?, 
                    UpdatedBy = ?, 
                    UpdatedAt = NOW() 
                    WHERE RoutinePlanID = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['status' => false, 'message' => 'Prepare failed: ' . $conn->error];
            }
            $stmt->bind_param('ssississsi', $planName, $categoryId, $subCategoryId, $subCategoryIdsJson, $seedIdsJson, $totalPlanDays, $description, $status, $userId, $routinePlanId);
            $res = $stmt->execute();
            $stmt->close();
            return ['status' => $res, 'routine_plan_id' => $routinePlanId, 'plan_code' => $planCode, 'action' => 'update'];
        } else {
            // Insert
            $sql = "INSERT INTO krf_dailyroutine_plan_master 
                    (PlanCode, PlanName, CategoryID, SubCategoryID, SubCategoryIDs_JSON, SeedIDs_JSON, TotalPlanDays, Description, ProjectId, CompanyId, SubCompanyId, Status, CreatedBy, CreatedAt) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return ['status' => false, 'message' => 'Prepare failed: ' . $conn->error];
            }
            $stmt->bind_param('sssississssss', $planCode, $planName, $categoryId, $subCategoryId, $subCategoryIdsJson, $seedIdsJson, $totalPlanDays, $description, $projectId, $companyId, $subCompanyId, $status, $userId);
            $res = $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();
            return ['status' => $res, 'routine_plan_id' => $newId, 'plan_code' => $planCode, 'action' => 'insert'];
        }
    }
}

if (!function_exists('saveDailyRoutineDayDetails')) {
    /**
     * Insert or update day-by-day routines for Day 1 to Day N
     */
    function saveDailyRoutineDayDetails($conn, $routinePlanId, $dayDetailsArray = []) {
        $routinePlanId = intval($routinePlanId);
        if ($routinePlanId <= 0 || empty($dayDetailsArray)) {
            return ['status' => false, 'message' => 'Invalid plan ID or empty day details'];
        }

        // Delete existing day details for this plan
        $delStmt = $conn->prepare("DELETE FROM krf_dailyroutine_plan_details WHERE RoutinePlanID = ?");
        if ($delStmt) {
            $delStmt->bind_param('i', $routinePlanId);
            $delStmt->execute();
            $delStmt->close();
        }

        $sql = "INSERT INTO krf_dailyroutine_plan_details 
                (RoutinePlanID, DayNumber, StageName, ActivityType, TaskTitle, ItemDetails, ApplicationMethod, TimeSlot, TargetPlantHeight, TargetHealthStatus, SpecialInstructions, Status, CreatedAt) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ['status' => false, 'message' => 'Prepare failed: ' . $conn->error];
        }

        $insertedCount = 0;
        foreach ($dayDetailsArray as $dayRow) {
            $dayNum = intval($dayRow['DayNumber'] ?? 1);
            $stageName = trim($dayRow['StageName'] ?? '');
            $activityType = trim($dayRow['ActivityType'] ?? '');
            $taskTitle = trim($dayRow['TaskTitle'] ?? '');
            $itemDetails = trim($dayRow['ItemDetails'] ?? '');
            $applicationMethod = trim($dayRow['ApplicationMethod'] ?? '');
            $timeSlot = trim($dayRow['TimeSlot'] ?? 'Morning');
            $targetHeight = trim($dayRow['TargetPlantHeight'] ?? '');
            $targetHealth = trim($dayRow['TargetHealthStatus'] ?? 'Healthy');
            $specialInst = trim($dayRow['SpecialInstructions'] ?? '');
            $status = !empty($dayRow['Status']) ? $dayRow['Status'] : 'Active';

            $stmt->bind_param('iissssssssss', 
                $routinePlanId, 
                $dayNum, 
                $stageName, 
                $activityType, 
                $taskTitle, 
                $itemDetails, 
                $applicationMethod, 
                $timeSlot, 
                $targetHeight, 
                $targetHealth, 
                $specialInst, 
                $status
            );
            if ($stmt->execute()) {
                $insertedCount++;
            }
        }
        $stmt->close();

        return ['status' => true, 'inserted_days_count' => $insertedCount];
    }
}

if (!function_exists('fetchDailyRoutinePlansList')) {
    /**
     * Fetch list of Daily Routine Plans with Subcategory names and Seed counts
     */
    function fetchDailyRoutinePlansList($conn, $projectId, $filters = []) {
        $plans = [];
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $where = "WHERE (m.ProjectId = ? OR m.ProjectId IS NULL OR m.ProjectId = '')";
            $params[] = $projectId;
            $types .= "s";
        } else {
            $where = "WHERE 1=1";
        }

        if (!empty($filters['CategoryID'])) {
            $where .= " AND m.CategoryID = ?";
            $params[] = $filters['CategoryID'];
            $types .= "s";
        }
        if (!empty($filters['SubCategoryID'])) {
            $subCatId = intval($filters['SubCategoryID']);
            $where .= " AND (m.SubCategoryID = ? OR JSON_CONTAINS(m.SubCategoryIDs_JSON, '\"$subCatId\"') OR JSON_CONTAINS(m.SubCategoryIDs_JSON, '$subCatId'))";
            $params[] = $subCatId;
            $types .= "i";
        }
        if (!empty($filters['Status'])) {
            $where .= " AND m.Status = ?";
            $params[] = $filters['Status'];
            $types .= "s";
        }
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where .= " AND (m.PlanName LIKE ? OR m.PlanCode LIKE ? OR m.Description LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "sss";
        }

        $sql = "SELECT m.*, 
                       c.CategoryName, 
                       s.SubCategoryName AS PrimarySubCategoryName,
                       (SELECT COUNT(*) FROM krf_dailyroutine_plan_details d WHERE d.RoutinePlanID = m.RoutinePlanID) AS ConfiguredDaysCount
                FROM krf_dailyroutine_plan_master m
                LEFT JOIN plant_category_master c ON m.CategoryID = c.CategoryID
                LEFT JOIN plant_subcategory_master s ON m.SubCategoryID = s.SubCategoryID
                $where
                ORDER BY m.RoutinePlanID DESC";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                // Decode Subcategories
                $subCatIds = json_decode($row['SubCategoryIDs_JSON'] ?? '[]', true);
                if (empty($subCatIds) && !empty($row['SubCategoryID'])) {
                    $subCatIds = [intval($row['SubCategoryID'])];
                }
                $row['SubCategoryIDs'] = $subCatIds;
                
                // Get subcategory names
                $subCatNames = [];
                if (!empty($subCatIds) && is_array($subCatIds)) {
                    $inClause = implode(',', array_map('intval', $subCatIds));
                    if (!empty($inClause)) {
                        $subRes = $conn->query("SELECT SubCategoryID, SubCategoryName FROM plant_subcategory_master WHERE SubCategoryID IN ($inClause)");
                        while ($subR = $subRes->fetch_assoc()) {
                            $subCatNames[$subR['SubCategoryID']] = $subR['SubCategoryName'];
                        }
                    }
                }
                $row['SubCategoryNames'] = $subCatNames;

                // Decode Seeds
                $seedIds = json_decode($row['SeedIDs_JSON'] ?? '[]', true);
                $row['SeedIDs'] = is_array($seedIds) ? $seedIds : [];
                $row['SeedCount'] = count($row['SeedIDs']);

                // Seed Preview Details
                $seedDetails = [];
                if (!empty($row['SeedIDs'])) {
                    $seedInClause = implode(',', array_map('intval', $row['SeedIDs']));
                    if (!empty($seedInClause)) {
                        $seedRes = $conn->query("SELECT SeedID, SeedName, VarietyofSeed, BrandName FROM seed_master WHERE SeedID IN ($seedInClause)");
                        while ($seedR = $seedRes->fetch_assoc()) {
                            $seedDetails[] = $seedR;
                        }
                    }
                }
                $row['SeedDetails'] = $seedDetails;

                $plans[] = $row;
            }
            $stmt->close();
        }

        return $plans;
    }
}

if (!function_exists('fetchDailyRoutinePlanById')) {
    /**
     * Fetch complete routine plan by ID including master header and day-by-day details
     */
    function fetchDailyRoutinePlanById($conn, $routinePlanId) {
        $routinePlanId = intval($routinePlanId);
        $plan = null;

        $stmt = $conn->prepare("SELECT m.*, c.CategoryName, s.SubCategoryName AS PrimarySubCategoryName 
                                FROM krf_dailyroutine_plan_master m
                                LEFT JOIN plant_category_master c ON m.CategoryID = c.CategoryID
                                LEFT JOIN plant_subcategory_master s ON m.SubCategoryID = s.SubCategoryID
                                WHERE m.RoutinePlanID = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('i', $routinePlanId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                // Decode Subcategories
                $subCatIds = json_decode($row['SubCategoryIDs_JSON'] ?? '[]', true);
                if (empty($subCatIds) && !empty($row['SubCategoryID'])) {
                    $subCatIds = [intval($row['SubCategoryID'])];
                }
                $row['SubCategoryIDs'] = $subCatIds;

                // Subcategory Details
                $subCatDetails = [];
                if (!empty($subCatIds)) {
                    $inClause = implode(',', array_map('intval', $subCatIds));
                    if (!empty($inClause)) {
                        $subRes = $conn->query("SELECT SubCategoryID, SubCategoryName, sku FROM plant_subcategory_master WHERE SubCategoryID IN ($inClause)");
                        while ($subR = $subRes->fetch_assoc()) {
                            $subCatDetails[] = $subR;
                        }
                    }
                }
                $row['SubCategoryDetails'] = $subCatDetails;

                // Decode Seeds
                $seedIds = json_decode($row['SeedIDs_JSON'] ?? '[]', true);
                $row['SeedIDs'] = is_array($seedIds) ? $seedIds : [];

                // Seed details
                $seedDetails = [];
                if (!empty($row['SeedIDs'])) {
                    $seedInClause = implode(',', array_map('intval', $row['SeedIDs']));
                    if (!empty($seedInClause)) {
                        $seedRes = $conn->query("SELECT SeedID, SeedName, VarietyofSeed, BrandName, CompanyName, SubCategoryID FROM seed_master WHERE SeedID IN ($seedInClause)");
                        while ($seedR = $seedRes->fetch_assoc()) {
                            $seedDetails[] = $seedR;
                        }
                    }
                }
                $row['SeedDetails'] = $seedDetails;

                // Fetch day details
                $dayDetails = [];
                $dayStmt = $conn->prepare("SELECT * FROM krf_dailyroutine_plan_details WHERE RoutinePlanID = ? ORDER BY DayNumber ASC");
                if ($dayStmt) {
                    $dayStmt->bind_param('i', $routinePlanId);
                    $dayStmt->execute();
                    $dayRes = $dayStmt->get_result();
                    while ($dayRow = $dayRes->fetch_assoc()) {
                        $dayDetails[] = $dayRow;
                    }
                    $dayStmt->close();
                }
                $row['DayDetails'] = $dayDetails;
                $plan = $row;
            }
            $stmt->close();
        }

        return $plan;
    }
}

if (!function_exists('adjustRoutinePlanDaysCount')) {
    /**
     * Dynamically update total days in routine plan master and add/trim day details
     */
    function adjustRoutinePlanDaysCount($conn, $routinePlanId, $newTotalDays) {
        $routinePlanId = intval($routinePlanId);
        $newTotalDays = max(1, intval($newTotalDays));

        $stmt = $conn->prepare("UPDATE krf_dailyroutine_plan_master SET TotalPlanDays = ?, UpdatedAt = NOW() WHERE RoutinePlanID = ?");
        if ($stmt) {
            $stmt->bind_param('ii', $newTotalDays, $routinePlanId);
            $stmt->execute();
            $stmt->close();
        }

        // If newTotalDays is smaller, remove excess days
        $delStmt = $conn->prepare("DELETE FROM krf_dailyroutine_plan_details WHERE RoutinePlanID = ? AND DayNumber > ?");
        if ($delStmt) {
            $delStmt->bind_param('ii', $routinePlanId, $newTotalDays);
            $delStmt->execute();
            $delStmt->close();
        }

        return ['status' => true, 'total_days' => $newTotalDays];
    }
}

if (!function_exists('duplicateDailyRoutinePlan')) {
    /**
     * Duplicate an existing Routine Plan for a new set of Subcategories / Seeds
     */
    function duplicateDailyRoutinePlan($conn, $sourcePlanId, $targetSubCategoryIds = [], $targetSeedIds = [], $newPlanName = '', $userId = 'admin', $projectId = '') {
        $sourcePlan = fetchDailyRoutinePlanById($conn, $sourcePlanId);
        if (!$sourcePlan) {
            return ['status' => false, 'message' => 'Source plan not found'];
        }

        $newPlanCode = generateRoutinePlanCode($conn, $projectId ?: $sourcePlan['ProjectId']);
        $planName = !empty($newPlanName) ? $newPlanName : ($sourcePlan['PlanName'] . ' (Copy)');
        $subCatIds = !empty($targetSubCategoryIds) ? $targetSubCategoryIds : $sourcePlan['SubCategoryIDs'];
        $seedIds = !empty($targetSeedIds) ? $targetSeedIds : $sourcePlan['SeedIDs'];
        $primarySubCatId = !empty($subCatIds) ? intval($subCatIds[0]) : intval($sourcePlan['SubCategoryID']);

        $masterData = [
            'PlanCode' => $newPlanCode,
            'PlanName' => $planName,
            'CategoryID' => $sourcePlan['CategoryID'],
            'SubCategoryID' => $primarySubCatId,
            'SubCategoryIDs_JSON' => $subCatIds,
            'SeedIDs_JSON' => $seedIds,
            'TotalPlanDays' => intval($sourcePlan['TotalPlanDays']),
            'Description' => $sourcePlan['Description'],
            'ProjectId' => $projectId ?: $sourcePlan['ProjectId'],
            'CompanyId' => $sourcePlan['CompanyId'],
            'SubCompanyId' => $sourcePlan['SubCompanyId'],
            'Status' => 'Active',
            'CreatedBy' => $userId
        ];

        $saveMasterRes = createOrUpdateDailyRoutineMaster($conn, $masterData);
        if (!$saveMasterRes['status']) {
            return $saveMasterRes;
        }

        $newPlanId = $saveMasterRes['routine_plan_id'];

        // Clone day details
        if (!empty($sourcePlan['DayDetails'])) {
            saveDailyRoutineDayDetails($conn, $newPlanId, $sourcePlan['DayDetails']);
        }

        return ['status' => true, 'new_routine_plan_id' => $newPlanId, 'new_plan_code' => $newPlanCode];
    }
}

if (!function_exists('deleteDailyRoutinePlan')) {
    /**
     * Delete or soft-deactivate Daily Routine Plan
     */
    function deleteDailyRoutinePlan($conn, $routinePlanId, $hardDelete = true) {
        $routinePlanId = intval($routinePlanId);
        if ($routinePlanId <= 0) {
            return ['status' => false, 'message' => 'Invalid plan ID'];
        }

        if ($hardDelete) {
            $stmt1 = $conn->prepare("DELETE FROM krf_dailyroutine_plan_details WHERE RoutinePlanID = ?");
            if ($stmt1) {
                $stmt1->bind_param('i', $routinePlanId);
                $stmt1->execute();
                $stmt1->close();
            }

            $stmt2 = $conn->prepare("DELETE FROM krf_dailyroutine_plan_master WHERE RoutinePlanID = ?");
            if ($stmt2) {
                $stmt2->bind_param('i', $routinePlanId);
                $res = $stmt2->execute();
                $stmt2->close();
                return ['status' => $res, 'message' => 'Routine plan deleted successfully'];
            }
        } else {
            $stmt = $conn->prepare("UPDATE krf_dailyroutine_plan_master SET Status = 'Inactive', UpdatedAt = NOW() WHERE RoutinePlanID = ?");
            if ($stmt) {
                $stmt->bind_param('i', $routinePlanId);
                $res = $stmt->execute();
                $stmt->close();
                return ['status' => $res, 'message' => 'Routine plan deactivated successfully'];
            }
        }

        return ['status' => false, 'message' => 'Deletion failed'];
    }
}

if (!function_exists('fetchRoutineDropdownOptions')) {
    /**
     * Fetch all dynamic dropdown options grouped by OptionGroup from single table
     */
    function fetchRoutineDropdownOptions($conn, $projectId = '') {
        $result = [
            'growth_stage' => [],
            'activity_type' => [],
            'application_method' => [],
            'time_slot' => []
        ];

        $where = "WHERE 1=1";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $where .= " AND (ProjectId = ? OR ProjectId IS NULL OR ProjectId = '')";
            $params[] = $projectId;
            $types .= "s";
        }

        $sql = "SELECT OptionGroup, GroupName, Options_JSON FROM krf_dailyroutine_options_master $where ORDER BY OptionID ASC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $grpKey = $row['OptionGroup'];
                $options = json_decode($row['Options_JSON'] ?? '[]', true);
                if (!is_array($options)) $options = [];
                $result[$grpKey] = $options;
            }
            $stmt->close();
        }

        return $result;
    }
}

if (!function_exists('addRoutineDropdownOption')) {
    /**
     * Add a single new option item into the JSON options list for a group
     */
    function addRoutineDropdownOption($conn, $optionGroup, $optionName, $projectId = '', $userId = 'admin') {
        $optionGroup = trim($optionGroup);
        $optionName = trim($optionName);

        if (empty($optionGroup) || empty($optionName)) {
            return ['status' => false, 'message' => 'Option group and name are required.'];
        }

        // Fetch current row
        $stmt = $conn->prepare("SELECT OptionID, Options_JSON FROM krf_dailyroutine_options_master WHERE OptionGroup = ? AND (ProjectId = ? OR ProjectId IS NULL OR ProjectId = '') LIMIT 1");
        $stmt->bind_param("ss", $optionGroup, $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        $options = [];
        $optionId = 0;
        if ($row) {
            $optionId = intval($row['OptionID']);
            $options = json_decode($row['Options_JSON'] ?? '[]', true);
            if (!is_array($options)) $options = [];
        }

        // Check if already exists
        foreach ($options as $opt) {
            if (strcasecmp(trim($opt['name'] ?? ''), $optionName) === 0) {
                return ['status' => false, 'message' => 'Option already exists.'];
            }
        }

        // Generate next ID
        $maxId = 0;
        foreach ($options as $opt) {
            if (isset($opt['id']) && intval($opt['id']) > $maxId) {
                $maxId = intval($opt['id']);
            }
        }
        $newItem = [
            'id' => $maxId + 1,
            'name' => $optionName,
            'order' => count($options) + 1
        ];
        $options[] = $newItem;
        $jsonStr = json_encode($options, JSON_UNESCAPED_UNICODE);

        if ($optionId > 0) {
            $upd = $conn->prepare("UPDATE krf_dailyroutine_options_master SET Options_JSON = ?, UpdatedBy = ?, UpdatedAt = NOW() WHERE OptionID = ?");
            $upd->bind_param("ssi", $jsonStr, $userId, $optionId);
            $success = $upd->execute();
            $upd->close();
        } else {
            $grpName = ucwords(str_replace('_', ' ', $optionGroup));
            $ins = $conn->prepare("INSERT INTO krf_dailyroutine_options_master (OptionGroup, GroupName, Options_JSON, ProjectId, UpdatedBy) VALUES (?, ?, ?, ?, ?)");
            $ins->bind_param("sssss", $optionGroup, $grpName, $jsonStr, $projectId, $userId);
            $success = $ins->execute();
            $ins->close();
        }

        return [
            'status' => $success,
            'message' => $success ? 'Option added successfully' : 'Failed to add option',
            'new_item' => $newItem,
            'options' => $options
        ];
    }
}

if (!function_exists('deleteRoutineDropdownOption')) {
    /**
     * Delete an option from JSON array by ID or Name
     */
    function deleteRoutineDropdownOption($conn, $optionGroup, $itemIdentifier, $projectId = '', $userId = 'admin') {
        $optionGroup = trim($optionGroup);

        $stmt = $conn->prepare("SELECT OptionID, Options_JSON FROM krf_dailyroutine_options_master WHERE OptionGroup = ? AND (ProjectId = ? OR ProjectId IS NULL OR ProjectId = '') LIMIT 1");
        $stmt->bind_param("ss", $optionGroup, $projectId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return ['status' => false, 'message' => 'Option group not found'];
        }

        $optionId = intval($row['OptionID']);
        $options = json_decode($row['Options_JSON'] ?? '[]', true);
        if (!is_array($options)) $options = [];

        $filtered = [];
        $found = false;
        foreach ($options as $opt) {
            if ((isset($opt['id']) && strval($opt['id']) === strval($itemIdentifier)) || (isset($opt['name']) && strcasecmp($opt['name'], strval($itemIdentifier)) === 0)) {
                $found = true;
                continue; // Skip/delete
            }
            $filtered[] = $opt;
        }

        if (!$found) {
            return ['status' => false, 'message' => 'Option not found'];
        }

        // Re-index order
        $reindexed = [];
        $idx = 1;
        foreach ($filtered as $f) {
            $f['order'] = $idx++;
            $reindexed[] = $f;
        }

        $jsonStr = json_encode($reindexed, JSON_UNESCAPED_UNICODE);
        $upd = $conn->prepare("UPDATE krf_dailyroutine_options_master SET Options_JSON = ?, UpdatedBy = ?, UpdatedAt = NOW() WHERE OptionID = ?");
        $upd->bind_param("ssi", $jsonStr, $userId, $optionId);
        $success = $upd->execute();
        $upd->close();

        return [
            'status' => $success,
            'message' => $success ? 'Option deleted successfully' : 'Failed to delete option',
            'options' => $reindexed
        ];
    }
}

if (!function_exists('fetchPolyhouseBedRoutineGrid')) {
    /**
     * Fetch Polyhouse Bed-Wise Daily Routine Activity grid grouped by:
     * sowinglotno_internal, stockorder_no, roottype, locationid, bed_no
     */
    function fetchPolyhouseBedRoutineGrid($conn, $projectId = '', $filters = []) {
        $data = [];
        $where = "WHERE (p.workingstatus = 'Polyhouse' OR p.status = 'Polyhouse' OR p.status IS NULL)";
        $params = [];
        $types = "";

        if (!empty($projectId)) {
            $where .= " AND (p.projectid = ? OR p.projectid IS NULL OR p.projectid = '')";
            $params[] = $projectId;
            $types .= "s";
        }
        if (!empty($filters['polyhouseid'])) {
            $where .= " AND p.polyhouseid = ?";
            $params[] = $filters['polyhouseid'];
            $types .= "s";
        }
        if (!empty($filters['bed_no'])) {
            $where .= " AND p.bed_no = ?";
            $params[] = $filters['bed_no'];
            $types .= "s";
        }
        if (!empty($filters['search'])) {
            $search = '%' . trim($filters['search']) . '%';
            $where .= " AND (p.sowinglotno_internal LIKE ? OR p.stockorder_no LIKE ? OR p.productname LIKE ? OR p.bed_no LIKE ?)";
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
            $types .= "ssss";
        }

        $sql = "SELECT 
                    p.sowinglotno_internal,
                    p.stockorder_no,
                    p.roottype,
                    p.locationid,
                    p.bed_no,
                    p.polyhouseid,
                    p.projectid,
                    p.productid,
                    p.productname,
                    p.planted_date,
                    p.sowing_date,
                    p.sowingdate_internal,
                    p.growth_stage,
                    p.health_status,
                    p.status AS plant_status,
                    COUNT(p.polyhouseplantid) AS total_trays,
                    SUM(COALESCE(p.qtypertray, 0)) AS total_plants,
                    SUM(COALESCE(p.failurecount, 0)) AS total_failed,
                    pr.SeedId,
                    pr.ScionSeedId,
                    pr.CategoryId,
                    sm.SeedName,
                    sm.VarietyofSeed,
                    sm.BrandName,
                    sm.CompanyName AS SeedCompany,
                    sm.SubCategoryID
                FROM krf_polyhouse_plant_details p
                LEFT JOIN product_master pr ON p.productid = pr.ProductID
                LEFT JOIN seed_master sm ON (
                    CASE 
                        WHEN p.roottype = 'Rootstock' AND pr.SeedId IS NOT NULL AND pr.SeedId != '' THEN pr.SeedId 
                        WHEN pr.ScionSeedId IS NOT NULL AND pr.ScionSeedId != '' THEN pr.ScionSeedId 
                        ELSE pr.SeedId 
                    END
                ) = sm.SeedID
                $where
                GROUP BY p.sowinglotno_internal, p.stockorder_no, p.roottype, p.locationid, p.bed_no
                ORDER BY p.planted_date DESC, p.bed_no ASC";

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $res = $stmt->get_result();

            // Cache location map from hrmconn or fallback
            global $hrmconn;
            $locationMap = [];
            if (isset($hrmconn) && $hrmconn instanceof mysqli && @mysqli_ping($hrmconn)) {
                $locRes = $hrmconn->query("SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster");
                if ($locRes) {
                    while ($lr = $locRes->fetch_assoc()) {
                        $locationMap[$lr['LocationId']] = $lr['LocationName'];
                    }
                }
            }
            if (empty($locationMap)) {
                $locRes2 = $conn->query("SELECT DISTINCT from_location_id, from_location_name FROM krf_vehicle_travel_history WHERE from_location_id != ''");
                if ($locRes2) {
                    while ($lr2 = $locRes2->fetch_assoc()) {
                        $locationMap[$lr2['from_location_id']] = $lr2['from_location_name'];
                    }
                }
            }

            // Cache plans to optimize lookups (including SubCategoryIDs and configured details count)
            $allPlansRes = $conn->query("SELECT m.RoutinePlanID, m.PlanCode, m.PlanName, m.TotalPlanDays, m.SeedIDs_JSON, m.SubCategoryIDs_JSON, m.Status,
                                                (SELECT COUNT(*) FROM krf_dailyroutine_plan_details d WHERE d.RoutinePlanID = m.RoutinePlanID) AS DetailsCount
                                         FROM krf_dailyroutine_plan_master m 
                                         WHERE m.Status = 'Active' 
                                         ORDER BY DetailsCount DESC, m.RoutinePlanID DESC");
            $allPlans = [];
            if ($allPlansRes) {
                while ($pr = $allPlansRes->fetch_assoc()) {
                    $pr['SeedIDs'] = json_decode($pr['SeedIDs_JSON'] ?? '[]', true) ?: [];
                    $pr['SubCategoryIDs'] = json_decode($pr['SubCategoryIDs_JSON'] ?? '[]', true) ?: [];
                    $pr['DetailsCount'] = intval($pr['DetailsCount'] ?? 0);
                    $allPlans[] = $pr;
                }
            }

            while ($row = $res->fetch_assoc()) {
                $seedId = !empty($row['SeedId']) ? intval($row['SeedId']) : (!empty($row['ScionSeedId']) ? intval($row['ScionSeedId']) : 0);
                $subCatId = !empty($row['SubCategoryID']) ? intval($row['SubCategoryID']) : 0;
                
                // 1. Match by exact SeedID
                $matchedPlan = null;
                if ($seedId > 0) {
                    foreach ($allPlans as $pl) {
                        if (in_array($seedId, $pl['SeedIDs'])) {
                            $matchedPlan = $pl;
                            break;
                        }
                    }
                }

                // 2. If no exact seed match, match by Crop / SubCategoryID (e.g. Tomato, Brinjal)
                if (!$matchedPlan && $subCatId > 0) {
                    foreach ($allPlans as $pl) {
                        if (in_array($subCatId, $pl['SubCategoryIDs'])) {
                            $matchedPlan = $pl;
                            break;
                        }
                    }
                }

                // 3. Fallback: Pick any active plan that actually has configured day details
                if (!$matchedPlan && count($allPlans) > 0) {
                    foreach ($allPlans as $pl) {
                        if ($pl['DetailsCount'] > 0) {
                            $matchedPlan = $pl;
                            break;
                        }
                    }
                    if (!$matchedPlan) {
                        $matchedPlan = $allPlans[0];
                    }
                }

                $totalPlanDays = $matchedPlan ? intval($matchedPlan['TotalPlanDays']) : 25;
                $routinePlanId = $matchedPlan ? intval($matchedPlan['RoutinePlanID']) : 0;

                // Calculate Day Age based on bed planting / entry date
                $targetDate = !empty($filters['routine_date']) ? $filters['routine_date'] : (!empty($filters['date']) ? $filters['date'] : date('Y-m-d'));
                $startDate = !empty($row['planted_date']) ? $row['planted_date'] : (!empty($row['sowingdate_internal']) ? $row['sowingdate_internal'] : $row['sowing_date']);
                $currentDayNumber = 1;
                if (!empty($startDate)) {
                    $diffDays = (int)((strtotime($targetDate) - strtotime($startDate)) / 86400);
                    $currentDayNumber = max(1, $diffDays + 1);
                }

                // Location name
                $locId = $row['locationid'] ?? '';
                $row['location_name'] = $locationMap[$locId] ?? ($locId ? $locId : 'Greenhouse Main');

                // Fetch Today's Scheduled Activity from Details
                $todayActivity = null;
                if ($routinePlanId > 0) {
                    // Cap lookup day to totalPlanDays for activity lookup
                    $lookupDay = min($totalPlanDays, $currentDayNumber);
                    $actStmt = $conn->prepare("SELECT * FROM krf_dailyroutine_plan_details WHERE RoutinePlanID = ? AND DayNumber = ? LIMIT 1");
                    if ($actStmt) {
                        $actStmt->bind_param("ii", $routinePlanId, $lookupDay);
                        $actStmt->execute();
                        $todayActivity = $actStmt->get_result()->fetch_assoc();
                        $actStmt->close();
                    }
                }

                // Fetch Last Action Taken from krf_polyhouse_routine_action_logs
                $lastAction = null;
                $lotNo = trim($row['sowinglotno_internal'] ?? '');
                $bedNo = trim($row['bed_no'] ?? '');
                $stockNo = trim($row['stockorder_no'] ?? '');
                $roottype = trim($row['roottype'] ?? '');
                $locId = trim($row['locationid'] ?? '');

                $logSql = "SELECT * FROM krf_polyhouse_routine_action_logs 
                           WHERE bed_no = ? ";
                $logParams = [$bedNo];
                $logTypes = "s";

                if (!empty($lotNo)) {
                    $logSql .= " AND (sowinglotno_internal = ? OR sowinglotno_internal = '' OR sowinglotno_internal IS NULL)";
                    $logParams[] = $lotNo;
                    $logTypes .= "s";
                }
                if (!empty($stockNo)) {
                    $logSql .= " AND (stockorder_no = ? OR stockorder_no = '' OR stockorder_no IS NULL)";
                    $logParams[] = $stockNo;
                    $logTypes .= "s";
                }
                if (!empty($roottype)) {
                    $logSql .= " AND (roottype = ? OR roottype = '' OR roottype IS NULL)";
                    $logParams[] = $roottype;
                    $logTypes .= "s";
                }

                $logSql .= " ORDER BY LogID DESC LIMIT 1";

                $logStmt = $conn->prepare($logSql);
                if ($logStmt) {
                    $logStmt->bind_param($logTypes, ...$logParams);
                    $logStmt->execute();
                    $lastAction = $logStmt->get_result()->fetch_assoc();
                    $logStmt->close();
                }

                $row['SeedID'] = $seedId;
                $row['MatchedPlan'] = $matchedPlan;
                $row['RoutinePlanID'] = $routinePlanId;
                $row['TotalPlanDays'] = $totalPlanDays;
                $row['CurrentDayNumber'] = $currentDayNumber;
                $row['StartDate'] = $startDate;
                $row['TodayActivity'] = $todayActivity;
                $row['LastAction'] = $lastAction;

                $data[] = $row;
            }
            $stmt->close();
        }

        return $data;
    }
}

if (!function_exists('logBedRoutineAction')) {
    /**
     * Insert or Update action log for polyhouse bed (Completed, Rescheduled, or Not Required)
     * Prevents duplicate rows on the same date/timeslot for the same bed lot.
     */
    function logBedRoutineAction($conn, $data) {
        $lotNo = trim($data['sowinglotno_internal'] ?? '');
        $stockOrderNo = trim($data['stockorder_no'] ?? '');
        $roottype = trim($data['roottype'] ?? 'Scion');
        $locationid = trim($data['locationid'] ?? '');
        $bedNo = trim($data['bed_no'] ?? '');
        $polyhouseid = trim($data['polyhouseid'] ?? '');
        $projectId = trim($data['projectid'] ?? '');
        $routinePlanId = intval($data['RoutinePlanID'] ?? 0);
        $dayNumber = intval($data['DayNumber'] ?? 1);
        $executionStatus = trim($data['ExecutionStatus'] ?? 'Completed');
        $rescheduleDate = !empty($data['RescheduleDate']) ? $data['RescheduleDate'] : null;
        $actionType = trim($data['ActionType'] ?? 'Routine Activity');
        $actionTitle = trim($data['ActionTitle'] ?? 'Completed Daily Activity');
        $itemDetails = trim($data['ItemDetails'] ?? '');
        $method = trim($data['ApplicationMethod'] ?? '');
        $timeSlot = trim($data['TimeSlot'] ?? 'Morning');
        $remarks = trim($data['Remarks'] ?? '');
        $actionDate = !empty($data['ActionTakenDate']) ? $data['ActionTakenDate'] : date('Y-m-d');
        $userId = trim($data['ActionTakenBy'] ?? 'admin');

        if ($executionStatus === 'Not Required' && empty($remarks)) {
            return ['status' => false, 'message' => 'Please provide a mandatory remark explaining why this plan/activity is Not Required.'];
        }

        if ($executionStatus === 'Rescheduled' && empty($rescheduleDate)) {
            return ['status' => false, 'message' => 'Please select the Reschedule Date.'];
        }

        // Check if a log already exists for this bed + lot/order + action date + timeslot
        $existingLogId = 0;
        $chkSql = "SELECT LogID FROM krf_polyhouse_routine_action_logs 
                   WHERE bed_no = ? 
                     AND (sowinglotno_internal = ? OR stockorder_no = ?) 
                     AND ActionTakenDate = ? 
                     AND (TimeSlot = ? OR TimeSlot = '' OR TimeSlot IS NULL)
                   ORDER BY LogID DESC LIMIT 1";
        $chkStmt = $conn->prepare($chkSql);
        if ($chkStmt) {
            $chkStmt->bind_param("sssss", $bedNo, $lotNo, $stockOrderNo, $actionDate, $timeSlot);
            $chkStmt->execute();
            $chkRes = $chkStmt->get_result()->fetch_assoc();
            if ($chkRes) {
                $existingLogId = intval($chkRes['LogID']);
            }
            $chkStmt->close();
        }

        if ($existingLogId > 0) {
            // Update existing log to prevent duplicate entries
            $stmt = $conn->prepare("UPDATE krf_polyhouse_routine_action_logs SET 
                roottype = ?, locationid = ?, polyhouseid = ?, projectid = ?, RoutinePlanID = ?, 
                DayNumber = ?, ExecutionStatus = ?, RescheduleDate = ?, ActionType = ?, ActionTitle = ?, 
                ItemDetails = ?, ApplicationMethod = ?, TimeSlot = ?, Remarks = ?, ActionTakenAt = NOW(), ActionTakenBy = ? 
                WHERE LogID = ?");
            if (!$stmt) {
                return ['status' => false, 'message' => $conn->error];
            }
            $stmt->bind_param("ssssiisssssssssi", 
                $roottype, $locationid, $polyhouseid, $projectId, $routinePlanId,
                $dayNumber, $executionStatus, $rescheduleDate, $actionType, $actionTitle,
                $itemDetails, $method, $timeSlot, $remarks, $userId, $existingLogId
            );
            $res = $stmt->execute();
            $stmt->close();
            $logId = $existingLogId;
        } else {
            // Insert new log
            $stmt = $conn->prepare("INSERT INTO krf_polyhouse_routine_action_logs 
                (sowinglotno_internal, stockorder_no, roottype, locationid, bed_no, polyhouseid, projectid, RoutinePlanID, DayNumber, ExecutionStatus, RescheduleDate, ActionType, ActionTitle, ItemDetails, ApplicationMethod, TimeSlot, Remarks, ActionTakenDate, ActionTakenBy) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            if (!$stmt) {
                return ['status' => false, 'message' => $conn->error];
            }

            $stmt->bind_param("sssssssiissssssssss", 
                $lotNo, 
                $stockOrderNo, 
                $roottype, 
                $locationid, 
                $bedNo, 
                $polyhouseid, 
                $projectId, 
                $routinePlanId, 
                $dayNumber, 
                $executionStatus, 
                $rescheduleDate, 
                $actionType, 
                $actionTitle, 
                $itemDetails, 
                $method, 
                $timeSlot, 
                $remarks, 
                $actionDate, 
                $userId
            );

            $res = $stmt->execute();
            $logId = $stmt->insert_id;
            $stmt->close();
        }

        return [
            'status' => $res,
            'message' => $res ? "Action logged as '{$executionStatus}' successfully!" : 'Failed to record action',
            'log_id' => $logId,
            'log' => [
                'LogID' => $logId,
                'sowinglotno_internal' => $lotNo,
                'stockorder_no' => $stockOrderNo,
                'roottype' => $roottype,
                'locationid' => $locationid,
                'bed_no' => $bedNo,
                'polyhouseid' => $polyhouseid,
                'projectid' => $projectId,
                'RoutinePlanID' => $routinePlanId,
                'DayNumber' => $dayNumber,
                'ExecutionStatus' => $executionStatus,
                'RescheduleDate' => $rescheduleDate,
                'ActionType' => $actionType,
                'ActionTitle' => $actionTitle,
                'ItemDetails' => $itemDetails,
                'ApplicationMethod' => $method,
                'TimeSlot' => $timeSlot,
                'Remarks' => $remarks,
                'ActionTakenDate' => $actionDate,
                'ActionTakenAt' => date('Y-m-d H:i:s'),
                'ActionTakenBy' => $userId
            ],
            'execution_status' => $executionStatus,
            'action_date' => $actionDate,
            'action_title' => $actionTitle
        ];
    }
}

if (!function_exists('fetchBedActionHistory')) {
    /**
     * Fetch action history logs for a specific bed and lot without duplicates per date/timeslot
     */
    function fetchBedActionHistory($conn, $lotNo, $bedNo, $stockOrderNo = '') {
        $logs = [];
        $stmt = $conn->prepare("SELECT * FROM krf_polyhouse_routine_action_logs 
                                WHERE (sowinglotno_internal = ? OR stockorder_no = ?) AND bed_no = ? 
                                AND LogID IN (
                                    SELECT MAX(LogID) FROM krf_polyhouse_routine_action_logs 
                                    WHERE (sowinglotno_internal = ? OR stockorder_no = ?) AND bed_no = ? 
                                    GROUP BY ActionTakenDate, TimeSlot
                                )
                                ORDER BY ActionTakenDate DESC, LogID DESC");
        if ($stmt) {
            $stmt->bind_param("ssssss", $lotNo, $stockOrderNo, $bedNo, $lotNo, $stockOrderNo, $bedNo);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($r = $res->fetch_assoc()) {
                $logs[] = $r;
            }
            $stmt->close();
        }
        return $logs;
    }
}
?>
