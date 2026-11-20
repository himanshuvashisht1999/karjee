<?php
ini_set('display_errors', 1);
set_time_limit(500);
error_reporting(E_ALL & ~E_WARNING & ~E_DEPRECATED & ~E_NOTICE);

// Created By Chatur Singh
function fetchCountryMaster($conn, $filterBy = null, $filterValue = null)
{
    $countryData = [];
    $condition = "";
    $params = [];
    $types = "";

    // Add condition based on filterBy
    if ($filterBy === "countryid") {
        $condition = " AND countryid = ?";
        $params[] = $filterValue;
        $types .= "s"; // `s` for string
    } elseif ($filterBy === "countrycode") {
        $condition = " AND countrycode = ?";
        $params[] = $filterValue;
        $types .= "s"; // `s` for string
    } elseif ($filterBy === "countryname") {
        $condition = " AND countryname = ?";
        $params[] = $filterValue;
        $types .= "s"; // `s` for string
    }
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT sno, countryid, countrycode, countryname FROM hrm_country_master WHERE (tedit IS NULL OR tedit = '') $condition";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters if any
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        // $countryData = $result->fetch_assoc();
        while ($row = $result->fetch_assoc()) {
            $countryData[] = $row;
        }
        $stmt->close();
    } else {
        return "Error in preparing statement: " . $conn->error;
    }
    return $countryData;
}
// Created By Chatur Singh
function fetchCurrencyMaster($conn, $filterBy = null, $filterValue = null)
{
    $currencyData = []; // Initialize the data array
    $condition = "";
    $params = [];
    $types = "";

    // Add condition based on filterBy
    if ($filterBy === "currencyid") {
        $condition = " AND CurrencyId = ?";
        $params[] = $filterValue;
        $types .= "i"; // `i` for integer (CurrencyId is usually an integer)
    } elseif ($filterBy === "currencycode") {
        $condition = " AND CurrencyCode = ?";
        $params[] = $filterValue;
        $types .= "s"; // `s` for string
    } elseif ($filterBy === "currencyname") {
        $condition = " AND CurrencyName = ?";
        $params[] = $filterValue;
        $types .= "s"; // `s` for string
    }

    $conn->query("SET NAMES 'UTF8'");
    // Default SQL query (if no filter is applied)
    $sql = "SELECT CurrencyId, CurrencyName, CurrencySymbol, CurrencyCode 
            FROM hrm_CurrencyMaster 
            WHERE (tedit IS NULL OR tedit = '') $condition order by CurrencyName";

    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $currencyData[] = $row;
        }
        $stmt->close();
    } else {
        return "Error in preparing statement: " . $conn->error;
    }
    return $currencyData;
}

// Created By Chatur Singh
function fetchGenderMaster($conn)
{
    $genderData = [];
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT id, GenderName FROM hrm_GenderMaster WHERE (tedit is null or tedit='')";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $genderData[] = $row;
        }
        $stmt->close();
    } else {
        return "Error in preparing statement: " . $conn->error;
    }
    return $genderData;
}
// Created By Chatur Singh
function fetchDepartmentMaster($conn, $companyid, $shortby, $shortbyvalue = null)
{
    $departmentData = [];
    $condition = "";
    $params = [];
    $types = "";
    if ($shortby === "id") {
        $condition = " AND DepartmentID = ?";
        $params[] = $shortbyvalue;
        $types .= "i"; // `i` for integer
    } elseif ($shortby === "name") {
        $condition = " AND DepartmentName = ?";
        $params[] = $shortbyvalue;
        $types .= "s"; // `s` for string
    }
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT DepartmentID, DepartmentName, ManagerID FROM hrm_DepartmentMaster WHERE (tedit IS NULL OR tedit = '') $condition";
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        // echo debugQuery($sql, $params, $types);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $departmentData[] = $row;
        }
        $stmt->close();
    } else {
        return "Error in preparing statement: " . $conn->error;
    }
    return $departmentData;
}


// Created By Chatur Singh
function fetchDesignationsMaster($conn, $companyid, $shortby = null, $shortbyvalue = null)
{
    $designationData = [];
    $where = [];
    $params = [];
    $types = "";

    // âœ… Base condition (active or unedited designations)
    $where[] = "(tedit IS NULL OR tedit = '')";

    // âœ… Filter by ID
    if (!empty($shortby) && ($shortby === "id" || $shortby === "DesignationID")) {
        $where[] = "DesignationID = ?";
        $params[] = (int) $shortbyvalue;
        $types .= "i";
    }
    // âœ… Filter by Name (case-insensitive)
    elseif (!empty($shortby) && $shortby === "name" && !empty($shortbyvalue)) {
        $where[] = "LOWER(DesignationName) = LOWER(?)";
        $params[] = $shortbyvalue;
        $types .= "s";
    }

    // âœ… Company filter (include null/empty + given companyid)
    if (!empty($companyid)) {
        $where[] = "(companyid IS NULL OR companyid = '' OR companyid = ?)";
        $params[] = $companyid;
        $types .= "s";
    } else {
        // If no companyid provided, still include null/empty
        $where[] = "(companyid IS NULL OR companyid = '')";
    }

    // âœ… Combine conditions
    $sql = "SELECT DesignationID, DesignationName, Level 
            FROM hrm_DesignationsMaster 
            WHERE " . implode(" AND ", $where) . " 
            ORDER BY DesignationName ASC";

    // âœ… Ensure UTF-8 encoding
    $conn->query("SET NAMES 'utf8mb4'");

    // âœ… Prepare and execute
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        // ðŸ” Debug (optional â€” comment out in production)
        // echo debugQuery($sql, $params, $types);

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $designationData[] = $row;
        }

        $stmt->close();
    } else {
        return "Error preparing statement: " . $conn->error;
    }

    return $designationData;
}



// Created By Chatur Singh
function fetchRelationships($conn, $shortby = null, $shortbyvalue = null)
{
    $condition = "";
    $params = [];
    $types = "";

    // Add conditions based on the `shortby` parameter
    if ($shortby === "relationshipid") {
        $condition = " WHERE RelationshipId = ?";
        $params[] = $shortbyvalue;
        $types .= "i"; // `i` for integer
    } elseif ($shortby === "relationshipname") {
        $condition = " WHERE RelationshipName = ?";
        $params[] = $shortbyvalue;
        $types .= "s"; // `s` for string
    } elseif ($shortby === "categoryid") {
        $condition = " WHERE Category = ?";
        $params[] = $shortbyvalue;
        $types .= "s"; // `s` for string
    }
    $conn->query("SET NAMES 'UTF8'");
    // Initialize the SQL query
    $query = "SELECT RelationshipId,RelationshipName,CategoryId,Description FROM hrm_RelationshipList" . $condition;

    // Prepare the SQL statement
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters if conditions are specified
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    // Execute the statement
    if (!$stmt->execute()) {
        die("Error executing statement: " . $stmt->error);
    }

    // Fetch the results
    $result = $stmt->get_result();
    $relationships = [];
    while ($row = $result->fetch_assoc()) {
        $relationships[] = $row;
    }

    // Close the statement
    $stmt->close();

    return $relationships;
}

// Created By Chatur Singh
function getIndustries($conn, $sortBy = null, $sortByValue = null)
{
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT IndustryId, IndustryName FROM hrm_JobIndustriesMaster WHERE (tedit IS NULL OR tedit = '')";
    if ($sortBy != "" && $sortByValue != "") {
        if ($sortBy === "IndustryId") {
            $sql .= " AND IndustryId = ?";
        } elseif ($sortBy === "IndustryName") {
            $sql .= " AND IndustryName LIKE ?";
            $sortByValue = "%$sortByValue%";
        }
    }
    $stmt = $conn->prepare($sql);
    if ($sortBy != "" && $sortByValue != "") {
        $stmt->bind_param("s", $sortByValue);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $industries = [];
    while ($row = $result->fetch_assoc()) {
        $industries[] = $row;
    }
    $stmt->close();
    return $industries;
}

// Created By Chatur Singh
function getJobTitles($conn, $sortBy = null, $sortByValue = null)
{
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT JobId, IndustryId, JobTitle, description FROM hrm_JobTitleMaster WHERE (tedit IS NULL OR tedit = '')";
    if (!empty($sortBy) && !empty($sortByValue)) {
        if ($sortBy === "IndustryId") {
            $sql .= " AND IndustryId = ?";
        } elseif ($sortBy === "JobId") {
            $sql .= " AND JobId = ?";
        } elseif ($sortBy === "JobTitle") {
            $sql .= " AND JobTitle LIKE ?";
            $sortByValue = "%$sortByValue%";
        }
    }
    $stmt = $conn->prepare($sql);
    if (!empty($sortBy) && !empty($sortByValue)) {
        $stmt->bind_param("s", $sortByValue);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $jobs = [];
    while ($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    $stmt->close();
    return $jobs;
}

// Created By Chatur Singh
function getEmploymentTypes($conn, $sortBy = null, $sortByValue = null)
{
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT EmploymentTypesId, EmploymentTypesName FROM hrm_EmploymentTypesMaster WHERE (tedit IS NULL OR tedit = '')";
    if (!empty($sortBy) && !empty($sortByValue)) {
        if ($sortBy === "id") {
            $sql .= " AND EmploymentTypesId = ?";
        } elseif ($sortBy === "name") {
            $sql .= " AND EmploymentTypesName = ?";
        } elseif ($sortBy === "namelike") {
            $sql .= " AND EmploymentTypesName LIKE ?";
            $sortByValue = "%$sortByValue%";
        }
    }
    $stmt = $conn->prepare($sql);
    if (!empty($sortBy) && !empty($sortByValue)) {
        if ($sortBy === "namelike" || $sortBy === "name") {
            $stmt->bind_param("s", $sortByValue);
        } elseif ($sortBy === "id") {
            $stmt->bind_param("i", $sortByValue);
        }
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $EmploymentTypes = [];
    while ($row = $result->fetch_assoc()) {
        $EmploymentTypes[] = $row;
    }
    $stmt->close();
    return $EmploymentTypes;
}

// Created By Chatur Singh
function getPayrollEarningMasterGlobalList($conn, $sortBy = null, $sortByValue = null)
{
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT * FROM hrm_PayrollEarnings_GlobalMaster WHERE (tedit IS NULL OR tedit = '')";
    $params = [];
    $types = "";

    if ($sortBy !== "" && $sortByValue !== null) {
        switch (strtolower($sortBy)) {
            case "id":
                $sql .= " AND id = ?";
                $types .= "i";
                $params[] = (int) $sortByValue;
                break;

            case "componentname":
                $sql .= " AND ComponentName = ?";
                $types .= "s";
                $params[] = $sortByValue;
                break;

            case "sortname":
                $sql .= " AND sortname = ?";
                $types .= "s";
                $params[] = $sortByValue;
                break;

            case "defaultforemployee":
                $sql .= " AND defaultforemployee = ?";
                $types .= "i";
                $params[] = 1;
                break;

            case "otherthandefault":
                $sql .= " AND defaultforemployee = ?";
                $types .= "i";
                $params[] = 0;
                break;
        }
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("SQL Prepare Error: " . $conn->error);
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $EarningMasterList = [];

    while ($row = $result->fetch_assoc()) {
        $EarningMasterList[] = $row;
    }

    $stmt->close();
    return $EarningMasterList;
}

function getPayrollEarningsMasterList($conn, $companyid, $sortBy = null, $sortByValue = null)
{
    $conn->query("SET NAMES 'UTF8'");
    // echo "=".$sortBy."==".$sortByValue;
    // Base SQL query
    $sql = "SELECT * FROM hrm_PayrollEarningsMaster WHERE (tedit IS NULL OR tedit = '') 
            AND companyid = ?";
    $params = [$companyid];
    $types = "i";  // 'i' -> Integer type for companyid

    // Apply sorting filters
    if ($sortBy != "") {
        switch ($sortBy) {
            case "id":
                $sql .= " AND id = ?";
                $types .= "i";
                $params[] = $sortByValue;
                break;

            case "componentid":
                $sql .= " AND ComponentId = ?";
                $types .= "s";
                $params[] = $sortByValue;
                break;

            case "ComponentId":
                $sql .= " AND ComponentId = ?";
                $types .= "s";
                $params[] = $sortByValue;
                break;

            case "name":
                $sql .= " AND ComponentName = ?";
                $types .= "s";
                $params[] = $sortByValue;
                break;

            case "namelike":
                $sql .= " AND ComponentName LIKE ?";
                $types .= "s";
                $params[] = "%$sortByValue%";
                break;

            case "defaultforemployee":
                $sql .= " AND defaultforemployee = ?";
                $types .= "s";
                $params[] = "1";
                break;
            case "otherthandefault":
                $sql .= " AND (defaultforemployee IS NULL OR defaultforemployee = '')";
                break;
        }
    }

    // Always order by vieworder to maintain consistency
    $sql .= " ORDER BY vieworder ASC";

    // Prepare the SQL statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return [];
    }

    // Bind parameters only if needed
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    // Execute the query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all results into an array
    $EarningsMasterList = [];
    while ($row = $result->fetch_assoc()) {
        $EarningsMasterList[] = $row;
    }
    // Close the statement
    $stmt->close();
    return $EarningsMasterList;
}

function getEmployeeEarningComponents($conn, $companyid, $subcompanyid, $projectid, $employeeId)
{
    // Prepare the SQL query
    $sql = "SELECT id, companyid, subcompanyid, projectid, EmployeeId, ComponentId, ComponentName, Frequency, EmployeeEarningType, EmployeeEarningValue, EmployeeEarningBase, EmployerEarningType, EmployerEarningValue, EmployerEarningBase, IsIncludedInCTC, IsIncludedInFBP, IsProRataBasis, description, created_at, updated_at FROM hrm_EmployeeEarningComponents WHERE companyid= ? AND subcompanyid= ? AND projectid= ? AND EmployeeId = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssi", $companyid, $subcompanyid, $projectid, $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    } else {
        return [];
    }
}

function getEmployeeEarningSalaryDetails($conn, $companyid, $subcompanyid, $projectid, $employeeId)
{
    // Prepare the SQL query
    // ConveyanceAllowance,SpecialAllowance
    // echo $companyid.'=='.$subcompanyid.'=='.$projectid.'=='.$employeeId;
    $sql = "SELECT e.id, e.EmployeeId, e.ComponentId, e.ComponentName, e.ComponentName as componentname, e.Frequency, e.EmployeeEarningType, e.EmployeeEarningValue, e.EmployeeEarningBase, e.EmployerEarningType, e.EmployerEarningValue, e.EmployerEarningBase, e.IsIncludedInCTC, e.IsIncludedInFBP, e.IsProRataBasis,s.CTCY,s.CTC,s.BasicSalary,s.HRA,s.SpecialAllowance, s.ConveyanceAllowance,s.Deductions,s.CurrencyCode, s.affectedfrom, s.validtilldate 
    FROM hrm_EmployeeEarningComponents e 
    RIGHT JOIN hrm_EmployeeSalary s ON e.EmployeeId=s.EmployeeID AND e.companyid=s.companyid AND e.projectid=s.projectid 
    WHERE e.companyid= ? AND e.subcompanyid= ? AND e.projectid= ? AND e.EmployeeId = ?
      AND s.SalaryID = (SELECT MAX(SalaryID) FROM hrm_EmployeeSalary WHERE EmployeeID = e.EmployeeId)";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssi", $companyid, $subcompanyid, $projectid, $employeeId);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
        return $data;
    } else {
        return [];
    }
}

// Created By Chatur Singh
function fetchEmployeeData($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue = null)
{
    $data = [];
    $condition = "";
    // Append conditions based on $shortby
    if ($shortby == "allactive") {
        $condition = " AND em.Status = 'Active'";
    } elseif ($shortby == "allresigned") {
        $condition = " AND em.Status = 'Resigned'";
    } elseif ($shortby == "EmployeeID") {
        $condition = " AND em.EmployeeID = ?";
    }
    $conn->query("SET NAMES 'UTF8'");

    // Construct the SQL query with a JOIN to get the official profile photo document if it exists
    $sql = "SELECT em.*, 
            CONCAT_WS(' ', IFNULL(em.TitleName, ''), IFNULL(em.FirstName, ''), IFNULL(em.MiddleName, ''), IFNULL(em.LastName, '')) AS FullName,
            ed.DocumentPath AS empPhotopath 
            FROM hrm_EmployeesMaster em 
            LEFT JOIN (
                SELECT d1.EmployeeID, d1.DocumentPath
                FROM hrm_EmployeeDocuments d1
                INNER JOIN (
                    SELECT EmployeeID, MAX(DocumentID) AS max_doc_id
                    FROM hrm_EmployeeDocuments
                    WHERE DocumentType IN ('ProfilePhoto', 'Profile Photo', 'profile_photo', 'photo', 'Profile')
                      AND DocumentPath IS NOT NULL 
                      AND DocumentPath != '' 
                      AND DocumentPath NOT LIKE '%No file%'
                      AND (tedit IS NULL OR tedit = '')
                    GROUP BY EmployeeID
                ) d2 ON d1.EmployeeID = d2.EmployeeID AND d1.DocumentID = d2.max_doc_id
            ) ed ON em.EmployeeID = ed.EmployeeID 
            WHERE " . ($shortby == "EmployeeID" ? "1=1" : "(em.tedit IS NULL OR em.tedit = '')") . " 
            AND em.companyid = ? AND em.subcompanyid = ? AND em.projectid = ? $condition
            GROUP BY em.EmployeeID";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters
        if ($shortby == "EmployeeID") {
            $stmt->bind_param("iiis", $companyid, $subcompanyid, $projectid, $shortbyvalue);
        } else {
            $stmt->bind_param("iii", $companyid, $subcompanyid, $projectid);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        // Fetch all rows as an associative array
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    } else {
        // Handle errors
        die("Error in SQL: " . $conn->error);
    }
    return $data;
}

function fetchEmployeeWeekOffData($hrmconn, $ProjectID, $EmployeeID, $shortby = '', $shortbyvalue = '')
{
    $data = [];

    // Base query
    $sql = "SELECT WeekOffID, EmployeeID, ProjectID, WeekOffDayNo, WeekOffdayName, Remarks, Status, created_at, created_by FROM hrm_EmployeeWeekOff
            WHERE Status='Active' and ProjectID = ? AND EmployeeID = ?";

    // Add optional filter
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $allowedColumns = ['WeekOffID', 'EmployeeID', 'WeekOffDayNo', 'WeekOffdayName', 'Status', 'created_by']; // whitelist
        if (in_array($shortby, $allowedColumns)) {
            $sql .= " AND $shortby = ?";
        } else {
            return ['status' => 'error', 'message' => 'Invalid filter column'];
        }
    }

    // Prepare query
    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Prepare failed: ' . $hrmconn->error];
    }

    // Bind parameters dynamically
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $stmt->bind_param("sis", $ProjectID, $EmployeeID, $shortbyvalue);
    } else {
        $stmt->bind_param("si", $ProjectID, $EmployeeID);
    }

    // Execute query
    if (!$stmt->execute()) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Execute failed: ' . $stmt->error];
    }

    // Fetch results
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    // Return response
    if (!empty($data)) {
        return ['status' => 'success', 'data' => $data];
    } else {
        return ['status' => 'error', 'message' => 'No records found'];
    }
}


function saveUpdateEmployeeWeekOff($conn, $EmployeeID, $ProjectID, $WeekOffDayNo, $Remarks, $LoginID)
{

    // Weekday name array (0 = Sunday, 1 = Monday .... 6 = Saturday)
    $weekDays = [
        1 => "Monday",
        2 => "Tuesday",
        3 => "Wednesday",
        4 => "Thursday",
        5 => "Friday",
        6 => "Saturday",
        7 => "Sunday"
    ];

    // Get Weekday Name
    $WeekOffDayName = isset($weekDays[$WeekOffDayNo]) ? $weekDays[$WeekOffDayNo] : "Unknown";

    // Check if already exists
    $check = $conn->prepare("
        SELECT WeekOffID 
        FROM hrm_EmployeeWeekOff 
        WHERE EmployeeID = ? AND WeekOffDayNo = ? AND ProjectID = ? AND Status = 'Active'
    ");
    $check->bind_param("iis", $EmployeeID, $WeekOffDayNo, $ProjectID);
    $check->execute();
    $result = $check->get_result();

    if ($result->num_rows > 0) {

        // UPDATE record
        $row = $result->fetch_assoc();
        $WeekOffID = $row['WeekOffID'];

        $update = $conn->prepare("
            UPDATE hrm_EmployeeWeekOff 
            SET WeekOffDayNo = ?, WeekOffdayName = ?, Remarks = ?, created_by = ?, created_at = NOW()
            WHERE WeekOffID = ?
        ");
        $update->bind_param("isssi", $WeekOffDayNo, $WeekOffDayName, $Remarks, $LoginID, $WeekOffID);

        if ($update->execute()) {
            return json_encode([
                "status" => "success",
                "message" => "Employee week off updated successfully."
            ]);
        } else {
            return json_encode([
                "status" => "error",
                "message" => $update->error
            ]);
        }

    } else {

        // INSERT new record
        $insert = $conn->prepare("
            INSERT INTO hrm_EmployeeWeekOff 
            (EmployeeID, ProjectID, WeekOffDayNo, WeekOffdayName, Remarks, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $insert->bind_param("isisss", $EmployeeID, $ProjectID, $WeekOffDayNo, $WeekOffDayName, $Remarks, $LoginID);

        if ($insert->execute()) {
            return json_encode([
                "status" => "success",
                "message" => "Employee week off added successfully."
            ]);
        } else {
            return json_encode([
                "status" => "error",
                "message" => $insert->error
            ]);
        }
    }
}

function saveUpdateEmployeeWeekOffWithArray($conn, $EmployeeID, $ProjectID, $WeekOffDayArray, $Remarks, $LoginID)
{

    // Weekday map (1 = Monday ... 7 = Sunday)
    $weekDays = [
        1 => "Monday",
        2 => "Tuesday",
        3 => "Wednesday",
        4 => "Thursday",
        5 => "Friday",
        6 => "Saturday",
        7 => "Sunday"
    ];

    // ==========================
    // STEP 1: Inactivate all existing week-offs
    // ==========================
    $inactive = $conn->prepare("
        UPDATE hrm_EmployeeWeekOff
        SET Status = 'Inactive'
        WHERE EmployeeID = ?
          AND ProjectID = ?
          AND Status = 'Active'
    ");
    $inactive->bind_param("is", $EmployeeID, $ProjectID);
    $inactive->execute();

    // ==========================
    // STEP 2: Loop selected weekdays
    // ==========================
    foreach ($WeekOffDayArray as $WeekOffDayNo) {

        if (!isset($weekDays[$WeekOffDayNo])) {
            continue; // skip invalid weekday
        }

        $WeekOffDayName = $weekDays[$WeekOffDayNo];

        // Check if record already exists
        $check = $conn->prepare("
            SELECT WeekOffID
            FROM hrm_EmployeeWeekOff
            WHERE EmployeeID = ?
              AND ProjectID = ?
              AND WeekOffDayNo = ?
        ");
        $check->bind_param("isi", $EmployeeID, $ProjectID, $WeekOffDayNo);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {

            // ==========================
            // UPDATE â†’ set Active
            // ==========================
            $row = $res->fetch_assoc();
            $WeekOffID = $row['WeekOffID'];

            $update = $conn->prepare("
                UPDATE hrm_EmployeeWeekOff
                SET Status = 'Active',
                    WeekOffdayName = ?,
                    Remarks = ?,
                    created_by = ?,
                    created_at = NOW()
                WHERE WeekOffID = ?
            ");
            $update->bind_param(
                "sssi",
                $WeekOffDayName,
                $Remarks,
                $LoginID,
                $WeekOffID
            );
            $update->execute();

        } else {

            // ==========================
            // INSERT â†’ Active
            // ==========================
            $insert = $conn->prepare("
                INSERT INTO hrm_EmployeeWeekOff
                (EmployeeID, ProjectID, WeekOffDayNo, WeekOffdayName, Status, Remarks, created_by, created_at)
                VALUES (?, ?, ?, ?, 'Active', ?, ?, NOW())
            ");
            $insert->bind_param(
                "isisss",
                $EmployeeID,
                $ProjectID,
                $WeekOffDayNo,
                $WeekOffDayName,
                $Remarks,
                $LoginID
            );
            $insert->execute();
        }
    }

    return json_encode([
        "status" => "success",
        "message" => "Employee week-off updated successfully."
    ]);
}


function getEmployeeBankDetails($conn, $companyID, $subCompanyID, $projectID, $employeeID)
{
    // SQL Query
    if (!empty($companyID) && !empty($subCompanyID) && !empty($projectID)) {
        $sql = "SELECT BankDetailID, EmployeeID, companyid, subcompanyid, projectid, BankName, AccountNumber, IFSCCode, SWIFTCode, IBAN, SortCode, CurrencyCode, CancelChequePath FROM hrm_EmployeeBankDetails WHERE EmployeeID = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ["error" => "SQL preparation failed: " . $conn->error];
        }
        $stmt->bind_param("siii", $employeeID, $companyID, $subCompanyID, $projectID);
    } else {
        $sql = "SELECT BankDetailID, EmployeeID, companyid, subcompanyid, projectid, BankName, AccountNumber, IFSCCode, SWIFTCode, IBAN, SortCode, CurrencyCode, CancelChequePath FROM hrm_EmployeeBankDetails WHERE EmployeeID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return ["error" => "SQL preparation failed: " . $conn->error];
        }
        $stmt->bind_param("s", $employeeID);
    }
    if (!$stmt->execute()) {
        return ["error" => "Execution failed: " . $stmt->error];
    }
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return !empty($data) ? $data : [];
}

function getEmployeeContactPersonDetails($conn, $companyID, $subCompanyID, $projectID, $employeeID)
{
    if (!empty($companyID) && !empty($subCompanyID) && !empty($projectID)) {
        $sql = "SELECT * FROM hrm_EmployeeContactPersonDetails WHERE EmployeeId = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("siii", $employeeID, $companyID, $subCompanyID, $projectID);
    } else {
        $sql = "SELECT * FROM hrm_EmployeeContactPersonDetails WHERE EmployeeId = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("s", $employeeID);
    }
    if (!$stmt->execute()) return [];
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function getEmployeeDocumentDetails($conn, $companyID, $subCompanyID, $projectID, $employeeID)
{
    $data = [];
    if (!empty($employeeID)) {
        $sql = "SELECT DocumentID, EmployeeID, companyid, subcompanyid, projectid, 
                    DocumentType, DocumentPath, UploadedDate FROM hrm_EmployeeDocuments 
                WHERE EmployeeID = ? ORDER BY DocumentID DESC";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $employeeID);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
        }
    }
    return $data;
}

function fetchEmployeeSalaryData($conn, $companyid, $subcompanyid, $projectid, $shortby = null, $shortbyvalue = null, $salaryYearMonth = null)
{
    $data = [];
    $condition = "";
    $paramTypes = "iii"; // Default types for companyid, subcompanyid, projectid
    $params = [$companyid, $subcompanyid, $projectid];

    // Append conditions based on $shortby
    if ($shortby == "EmployeeID" && $shortbyvalue != "") {
        $condition .= " AND EmployeeID = ?";
        $paramTypes .= "i"; // EmployeeID is an integer
        $params[] = $shortbyvalue;
    }

    // Filter by salary month effective date range if provided (e.g. "2026-06")
    if (!empty($salaryYearMonth) && preg_match('/^\d{4}-\d{2}$/', $salaryYearMonth)) {
        $monthStart = $salaryYearMonth . "-01";
        $monthEnd = date("Y-m-t", strtotime($monthStart));

        $condition .= " AND (affectedfrom IS NULL OR affectedfrom = '' OR affectedfrom = '0000-00-00' OR affectedfrom <= ?) 
                        AND (validtilldate IS NULL OR validtilldate = '' OR validtilldate = '0000-00-00' OR validtilldate >= ?)";
        $paramTypes .= "ss";
        $params[] = $monthEnd;
        $params[] = $monthStart;
    }

    $conn->query("SET NAMES 'UTF8'");

    // SELECT affectedfrom and validtilldate from hrm_EmployeeSalary
    $sql = "SELECT SalaryID, companyid, subcompanyid, projectid, EmployeeID, CTC, GrossSalary, BasicSalary, HRA, ConveyanceAllowance, SpecialAllowance, Deductions, NetSalary, PaymentDate, CurrencyCode, PaymentMethod, Comments, affectedfrom, validtilldate 
            FROM hrm_EmployeeSalary 
            WHERE companyid = ? AND subcompanyid = ? AND projectid = ? $condition 
            ORDER BY (CASE WHEN affectedfrom IS NULL OR affectedfrom='' OR affectedfrom='0000-00-00' THEN 0 ELSE 1 END) DESC, affectedfrom DESC, SalaryID DESC";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters dynamically
        $stmt->bind_param($paramTypes, ...$params);

        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch all rows as an associative array
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        // Handle errors
        die("SQL Error: " . $conn->error);
    }

    return $data;
}

function fetchEmployeeAdvanceMasterData($conn, $companyid, $subcompanyid, $projectid, $shortby = null, $shortbyvalue = null)
{
    $data = [];
    $condition = "";
    $paramTypes = "i"; // for projectid
    $params = [$projectid];

    // Optional filter by EmployeeID
    if ($shortby == "EmployeeID" && $shortbyvalue != "") {
        $condition = " AND e.EmployeeID = ?";
        $paramTypes .= "i";
        $params[] = $shortbyvalue;
    }

    // Ensure UTF-8 encoding
    $conn->query("SET NAMES 'utf8'");

    // âœ… Main query (only employees having advance data)
    $sql = "
        SELECT 
            e.EmployeeID,
            CONCAT_WS(' ', 
                IFNULL(e.TitleName, ''), 
                IFNULL(e.FirstName, ''), 
                IFNULL(e.MiddleName, ''), 
                IFNULL(e.LastName, '')
            ) AS FullName,
            IFNULL(SUM(a.AdvanceAmount), 0) AS AdvanceAmount,
            IFNULL((
                SELECT SUM(s.DebitAmount)
                FROM hrm_EmployeeAdvanceSummary s
                WHERE s.projectid = e.projectid
                  AND s.EmployeeID = e.EmployeeID
            ), 0) AS totaldebitadvance,
            IFNULL(SUM(a.DeductionPerMonth), 0) AS DeductionPerMonth,
            (IFNULL(SUM(a.AdvanceAmount), 0) - IFNULL((
                SELECT SUM(s.DebitAmount)
                FROM hrm_EmployeeAdvanceSummary s
                WHERE s.projectid = e.projectid
                  AND s.EmployeeID = e.EmployeeID
            ), 0)) AS NetAdvance,
            e.DateOfJoining,
            e.Email,
            e.MobileNo
        FROM hrm_EmployeesMaster e
        INNER JOIN hrm_EmployeeAdvance a 
            ON a.projectid = e.projectid 
            AND a.EmployeeID = e.EmployeeID
        WHERE (e.tedit IS NULL OR e.tedit = '')
          AND e.projectid = ?
          $condition
        GROUP BY 
            e.EmployeeID, e.TitleName, e.FirstName, e.MiddleName, e.LastName, 
            e.DateOfJoining, e.Email, e.MobileNo
    ";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($paramTypes, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        die("SQL Error: " . $conn->error);
    }

    return $data;
}

function fetchEmployeeDataForSalaryCalculation($conn, $companyid, $subcompanyid, $projectid, $salaryyear, $salarymonth)
{
    // Format first and last date of the given month
    $salarymonth = str_pad($salarymonth, 2, '0', STR_PAD_LEFT);

    $startOfMonth = "$salaryyear-$salarymonth-01";
    $endOfMonth = date("Y-m-t", strtotime($startOfMonth));
    $data = [];
    $conn->query("SET NAMES 'UTF8'");
    $sql = "
        SELECT * 
        FROM hrm_EmployeesMaster 
        WHERE (tedit IS NULL OR tedit = '')  
          AND Status = 'Active' 
          AND companyid = ? 
          AND subcompanyid = ? 
          AND projectid = ? 
          AND DateOfJoining IS NOT NULL 
          AND DateOfJoining <= ? 
          AND (
              DateOfResignation IS NULL
              OR DateOfResignation = ''
              OR DateOfResignation >= ?
          )
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $companyid, $subcompanyid, $projectid, $endOfMonth, $startOfMonth);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    return $data;
}


function getEmployeeCalculatedSalary($conn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth, $shortBy = null, $shortByValue = null)
{
    $sql = "SELECT *
            FROM hrm_EmployeeCalculatedSalary 
            WHERE CompanyID = ? 
            AND SubCompanyID = ? 
            AND ProjectID = ? 
            AND SalaryYear = ?
            AND SalaryMonth = ?";

    // Default parameter types (all int here)
    $params = [$CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth];
    $types = "iiiii";

    if ($shortBy === "employeeid" || $shortBy === "EmployeeID") {
        $sql .= " AND EmployeeID = ?";
        $params[] = $shortByValue;
        $types .= "i";  // EmployeeID is also integer
    } else if ($shortBy === "pendingforbook") {
        $sql .= " AND (requestno = '' OR requestno IS NULL)";
        // No param needed, donâ€™t push into $params
    }

    $sql .= " ORDER BY SalaryYear DESC, SalaryMonth DESC"; // show latest first

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["error" => "Statement preparation failed: " . $conn->error];
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        return ["error" => "Query execution failed: " . $stmt->error];
    }

    $result = $stmt->get_result();
    if (!$result) {
        return ["error" => "Failed to fetch result: " . $stmt->error];
    }

    // Fetch Data
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

function getEmployeeSalaryWithYear($conn, $ProjectID, $SalaryYear, $shortBy = null, $shortByValue = null)
{
    $sql = "SELECT *
            FROM hrm_EmployeeCalculatedSalary 
            WHERE ProjectID = ? 
            AND SalaryYear = ?";

    // Default parameter types (all int here)
    $params = [$ProjectID, $SalaryYear];
    $types = "ii";

    if ($shortBy === "employeeid" || $shortBy === "EmployeeID") {
        $sql .= " AND EmployeeID = ?";
        $params[] = $shortByValue;
        $types .= "i";  // EmployeeID is also integer
    } else if ($shortBy === "SalaryID") {
        $sql .= " AND SalaryID = ?";
        $params[] = $shortByValue;
        $types .= "i";
    }

    $sql .= " ORDER BY SalaryYear DESC"; // show latest first

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["error" => "âŒ Statement preparation failed: " . $conn->error];
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        return ["error" => "âŒ Query execution failed: " . $stmt->error];
    }

    $result = $stmt->get_result();
    if (!$result) {
        return ["error" => "âŒ Failed to fetch result: " . $stmt->error];
    }

    // Fetch Data
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

function EmployeeAttendanceYearMonth($conn, $ProjectID, $shortby = '', $shortbyvalue = '')
{
    // Base query
    $sql = "SELECT DISTINCT 
                YEAR(AttendanceDate) AS AttendanceYear, 
                MONTH(AttendanceDate) AS AttendanceMonth 
            FROM hrm_EmployeeAttendance 
            WHERE (tedit is null or tedit='') AND ProjectID = ?";

    // Optional filter
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $sql .= " AND $shortby = ?";
    }

    $sql .= " ORDER BY AttendanceYear DESC, AttendanceMonth DESC";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $stmt->bind_param('ss', $ProjectID, $shortbyvalue);
    } else {
        $stmt->bind_param('s', $ProjectID);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $month = $row['AttendanceMonth'];
        $year = $row['AttendanceYear'];

        $data[] = [
            'AttendanceYear' => $year,
            'AttendanceMonth' => $month,
            'MonthYear' => "$year-$month",
            'MonthYeardisp' => date('M-Y', strtotime("$year-$month-01"))
        ];
    }

    return !empty($data) ? $data : ["message" => "âš ï¸ No attendance records found."];
}

function getEmployeeAttendanceSummaryDetails($hrmconn, $projectid, $employeeid, $shortby = '', $shortbyvalue = '')
{
    // Validate required parameters
    if (empty($projectid) || empty($employeeid)) {
        return ['status' => 'error', 'message' => 'ProjectID and EmployeeID are required.'];
    }
    // Base query
    $query = "
        SELECT 
            a.EmployeeID,
            a.AttendanceDate,
            -- First Check-In record
            (SELECT CheckInTime FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckInTime ASC LIMIT 1) AS FirstCheckInTime,

            (SELECT SourceIn FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckInTime ASC LIMIT 1) AS FirstSourceIn,

            (SELECT CheckInPhoto FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckInTime ASC LIMIT 1) AS FirstCheckInPhoto,

            (SELECT CheckInLat FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckInTime ASC LIMIT 1) AS FirstCheckInLat,

            (SELECT CheckInLng FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckInTime ASC LIMIT 1) AS FirstCheckInLng,

            (SELECT CheckInAddress FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckInTime ASC LIMIT 1) AS FirstCheckInAddress,

            -- Last Check-Out record
            (SELECT ifnull(CheckOutTime, 'Missing') as CheckOutTime FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckOutTime DESC LIMIT 1) AS LastCheckOutTime,

            (SELECT SourceOut FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckOutTime DESC LIMIT 1) AS LastSourceOut,

            (SELECT CheckOutPhoto FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckOutTime DESC LIMIT 1) AS LastCheckOutPhoto,

            (SELECT CheckOutLat FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckOutTime DESC LIMIT 1) AS LastCheckOutLat,

            (SELECT CheckOutLng FROM hrm_EmployeeAttendance 
             WHERE (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckOutTime DESC LIMIT 1) AS LastCheckOutLng,

            (SELECT CheckOutAddress FROM hrm_EmployeeAttendance 
             WHERE  (tedit is null or tedit='') AND ProjectID = a.ProjectID AND EmployeeID = a.EmployeeID AND AttendanceDate = a.AttendanceDate 
             ORDER BY CheckOutTime DESC LIMIT 1) AS LastCheckOutAddress,

            ROUND(SUM(a.WorkingHours), 2) AS TotalWorkingHoursDecimal,
            CONCAT(
                FLOOR(SUM(a.WorkingHours)),
                ':',
                LPAD(ROUND((SUM(a.WorkingHours) - FLOOR(SUM(a.WorkingHours))) * 60), 2, '0')
            ) AS TotalWorkingHours,
            MAX(a.Status) AS Status,
            MAX(a.LeaveType) AS LeaveType,
            MAX(a.OvertimeHours) AS OvertimeHours,
            MAX(a.Remarks) AS Remarks,
            MAX(a.ApprovalStatus) AS ApprovalStatus,
            MAX(a.ApprovedBy) AS ApprovedBy,
            MAX(a.ApprovedDateTime) AS ApprovedDateTime,
            MAX(a.ApprovalRemarks) AS ApprovalRemarks
        FROM hrm_EmployeeAttendance a
        WHERE (a.tedit IS NULL OR a.tedit = '') AND a.ProjectID = ? AND a.EmployeeID = ?
    ";

    // Base parameters
    $params = [$projectid, $employeeid];
    $types = 'si';

    // Optional filter by month-year
    if ($shortby === "withmonthyear" && !empty($shortbyvalue)) {
        $shortbyvalueex = explode("-", $shortbyvalue);
        if (count($shortbyvalueex) === 2) {
            $attYear = (int) $shortbyvalueex[0];
            $attMonth = (int) $shortbyvalueex[1];
            $query .= " AND YEAR(a.AttendanceDate) = ? AND MONTH(a.AttendanceDate) = ?";
            $params[] = $attYear;
            $params[] = $attMonth;
            $types .= 'ii';
        }
    }

    $query .= " GROUP BY a.AttendanceDate ORDER BY a.AttendanceDate DESC";

    // Prepare and execute
    $stmt = $hrmconn->prepare($query);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Query preparation failed: ' . $hrmconn->error];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

// Attendance view in employee panel
function getEmployeeMonthlyAttendanceSummary($conn, $projectid, $employeeid, $attyear, $attmonth)
{

    /* ================= SHIFT HOURS ================= */
    $shiftData = getShiftHours($conn, $employeeid);
    $FULL_DAY_HOURS = convertTimeToDecimal($shiftData['full']);
    $HALF_DAY_HOURS = convertTimeToDecimal($shiftData['half']);


    $summary = [
        "EmployeeID" => $employeeid,
        "days_inmonth" => 0,
        "total_days" => 0,
        "present" => 0,
        "absent" => 0,
        "leave" => 0,
        "holiday" => 0,
        "weekoff" => 0,
        "mis" => 0,
        "full_days_hours" => $shiftData['full'],
        "half_days_hours" => $shiftData['half'],
        "full_days" => 0,
        "half_days" => 0,
        "working_hours_seconds" => 0,
        "working_hours_decimal" => 0,
        "working_hours_hhmm" => "00:00",
        "overtime_seconds" => 0,
        "details" => []
    ];

    $monthPadded = str_pad($attmonth, 2, '0', STR_PAD_LEFT);
    $startDate = "$attyear-$monthPadded-01";
    $endDate = date("Y-m-t", strtotime($startDate));

    $summary["days_inmonth"] = cal_days_in_month(
        CAL_GREGORIAN,
        (int) $attmonth,
        (int) $attyear
    );


    /* ================= ATTENDANCE ================= */
    $attendance = [];

    /*$sql = "
        SELECT  
            AttendanceDate,
            MIN(CheckInTime)  AS CheckInTime,
            MAX(CheckOutTime) AS CheckOutTime,
            MIN(CheckInTime)  AS FirstCheckInTime,
            MAX(CheckOutTime) AS LastCheckOutTime,
            TIME_FORMAT(
                SEC_TO_TIME(SUM(TIME_TO_SEC(IFNULL(WorkingHours,'00:00:00')))),
                '%H:%i:%s'
            ) AS TotalWorkingHours,
            ROUND(
                SUM(TIME_TO_SEC(IFNULL(WorkingHours,'00:00:00'))) / 3600,
                2
            ) AS TotalWorkingHoursDecimal,
            TIME_FORMAT(
                SEC_TO_TIME(SUM(TIME_TO_SEC(IFNULL(OvertimeHours,'00:00:00')))),
                '%H:%i:%s'
            ) AS OvertimeHours,
            MAX(Status) AS EditStatus,
            MAX(LeaveType) AS LeaveType,
            MAX(ApprovalStatus) AS ApprovalStatus
        FROM hrm_EmployeeAttendance
        WHERE ProjectID = ?
          AND EmployeeID = ?
          AND Status in ('Present','MarkOut Missing','Edited','Add Missing Attendance','Rejected','Mistake')
          AND AttendanceDate BETWEEN ? AND ?
        GROUP BY AttendanceDate";
    */

    $sql = "
    SELECT AttendanceID, EmployeeID, AttendanceDate, CheckInTime, CheckOutTime, WorkingHours, OvertimeHours, Status, ApprovalStatus, LeaveType
    FROM hrm_EmployeeAttendance
    WHERE (tedit is null or tedit='') AND ProjectID = ? AND EmployeeID = ?
      AND Status IN (
            'Present',
            'Edited',
            'Rejected',
            'Add Missing Attendance',
            'MarkOut Missing',
            'Mistake'
      )
      AND AttendanceDate BETWEEN ? AND ?
    ORDER BY AttendanceDate ASC, CheckInTime ASC, AttendanceID ASC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $projectid, $employeeid, $startDate, $endDate);
    $stmt->execute();
    $res = $stmt->get_result();

    $punchesByDate = [];
    while ($r = $res->fetch_assoc()) {
        $adate = $r['AttendanceDate'];
        if (!isset($punchesByDate[$adate])) {
            $punchesByDate[$adate] = [];
        }
        $punchesByDate[$adate][] = $r;
    }

    foreach ($punchesByDate as $adate => $pList) {
        $firstIn = $pList[0]['CheckInTime'];
        $lastOut = '';
        $totalSec = 0;
        $totalOtSec = 0;
        $allApproved = '';
        $hasMissingOut = false;
        $editStatus = 'Present';
        $leaveType = '';

        foreach ($pList as $p) {
            $checkOut = trim($p['CheckOutTime'] ?? '');
            if (empty($checkOut) || $checkOut === '0000-00-00 00:00:00' || strpos($checkOut, '00:00:00') !== false) {
                $hasMissingOut = true;
            } else {
                $lastOut = $checkOut;
            }

            if (!empty($p['WorkingHours'])) {
                $totalSec += strtotime("1970-01-01 {$p['WorkingHours']} UTC");
            }
            if (!empty($p['OvertimeHours'])) {
                $totalOtSec += strtotime("1970-01-01 {$p['OvertimeHours']} UTC");
            }

            $appStat = strtolower(trim($p['ApprovalStatus'] ?? ''));
            if ($appStat!="") {
                $allApproved = $appStat;
            }

            if (!empty($p['Status'])) {
                $editStatus = $p['Status'];
            }
            if (!empty($p['LeaveType'])) {
                $leaveType = $p['LeaveType'];
            }
        }

        $totalH = floor($totalSec / 3600);
        $totalM = floor(($totalSec % 3600) / 60);
        $totalS = $totalSec % 60;
        $formattedWorkingHours = sprintf('%02d:%02d:%02d', $totalH, $totalM, $totalS);
        $workingDecimal = round($totalSec / 3600, 2);

        $otH = floor($totalOtSec / 3600);
        $otM = floor(($totalOtSec % 3600) / 60);
        $otS = $totalOtSec % 60;
        $formattedOtHours = sprintf('%02d:%02d:%02d', $otH, $otM, $otS);

        $attendance[$adate] = [
            'AttendanceDate' => $adate,
            'CheckInTime' => $firstIn,
            'CheckOutTime' => $lastOut,
            'FirstCheckInTime' => $firstIn,
            'LastCheckOutTime' => $lastOut,
            'TotalWorkingHours' => $formattedWorkingHours,
            'TotalWorkingHoursDecimal' => $workingDecimal,
            'OvertimeHours' => $formattedOtHours,
            'EditStatus' => $editStatus,
            'ApprovalStatus' => $allApproved,
            'hasMissingOut' => $hasMissingOut,
            'LeaveType' => $leaveType,
            'punches' => $pList
        ];
    }


    /* ================= LEAVES ================= */
    $leaves = [];
    $sql = "SELECT LeaveID, LeaveType, LeaveStartDate, LeaveEndDate, TotalLeaveDays, Status, Remarks, approval_remark
            FROM hrm_EmployeeLeaves
            WHERE EmployeeID=? AND ProjectID=? AND Status NOT IN ('Removed', 'Cancelled')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $employeeid, $projectid);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $period = new DatePeriod(
            new DateTime($row['LeaveStartDate']),
            new DateInterval('P1D'),
            (new DateTime($row['LeaveEndDate']))->modify('+1 day')
        );
        foreach ($period as $d) {
            $dtStr = $d->format('Y-m-d');
            // Priority: Approved > Pending > Rejected
            if (!isset($leaves[$dtStr])) {
                $leaves[$dtStr] = $row;
            } else {
                $existingStatus = $leaves[$dtStr]['Status'];
                if ($row['Status'] === 'Approved') {
                    $leaves[$dtStr] = $row;
                } elseif ($row['Status'] === 'Pending' && $existingStatus !== 'Approved') {
                    $leaves[$dtStr] = $row;
                }
            }
        }
    }

    /* ================= HOLIDAYS ================= */
    $holidays = [];
    // Broaden search to include company-wide/global holidays (projectid=0)
    $sql = "SELECT DATE(HolidayDate) as HolidayDate FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit ='') AND projectid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $holidays[$row['HolidayDate']] = true;
    }

    /* ================= WEEK OFF ================= */
    $weekOffDays = [];
    $sql = "SELECT WeekOffDayNo FROM hrm_EmployeeWeekOff 
            WHERE EmployeeID=? AND ProjectID=? AND Status='Active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $employeeid, $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $weekOffDays[] = (int) $row['WeekOffDayNo'];
    }

    $todayDate = date('Y-m-d');
    $currentYear = (int) date('Y');
    $currentMonth = (int) date('m');
    $isCurrentMonth = ((int) $attyear === $currentYear && (int) $attmonth === $currentMonth);
    $isFutureMonth = ((int) $attyear > $currentYear || ((int) $attyear === $currentYear && (int) $attmonth > $currentMonth));

    if ($isCurrentMonth) {
        $summary["days_till_today"] = (int) date('d');
    } elseif ($isFutureMonth) {
        $summary["days_till_today"] = 0;
    } else {
        $summary["days_till_today"] = $summary["days_inmonth"];
    }

    /* ================= MAIN LOOP ================= */
    $dayStatus = [];

    $period = new DatePeriod(
        new DateTime($startDate),
        new DateInterval('P1D'),
        (new DateTime($endDate))->modify('+1 day')
    );

    foreach ($period as $dt) {
        $date = $dt->format('Y-m-d');
        $dbDayNo = ($dt->format('w') == 0) ? 7 : (int) $dt->format('w');
        $isFutureDate = ($date > $todayDate);

        $summary["total_days"]++;
        $isPresent = isset($attendance[$date]) && $attendance[$date]['ApprovalStatus'] !== 'Rejected';
        $isHoliday = isset($holidays[$date]);
        $isWeekOff = in_array($dbDayNo, $weekOffDays);

        $leaveRecord = isset($leaves[$date]) ? $leaves[$date] : null;
        $leaveStatus = $leaveRecord ? $leaveRecord['Status'] : '';
        $leaveType = $leaveRecord ? $leaveRecord['LeaveType'] : '';

        // Check if attendance row itself marks an approved leave
        if ($isPresent && !empty($attendance[$date]['LeaveType']) && strtolower($attendance[$date]['ApprovalStatus']) == 'approved') {
            $leaveStatus = 'Approved';
            $leaveType = $attendance[$date]['LeaveType'];
        }

        $isLeaveApproved = ($leaveStatus === 'Approved');
        $isLeavePending = ($leaveStatus === 'Pending');

        $status = "";
        $statusofday = "";

        /* ================= PRESENT (ATTENDANCE MARKED) ================= */
        if ($isPresent) {
            $row = $attendance[$date];
            $worked = (float) $row['TotalWorkingHoursDecimal'];
            $hasMissingOut = !empty($row['hasMissingOut']);

            if ($hasMissingOut) {
                $summary["mis"]++;
                $status = "MIS";
                $statusofday = "MIS";
            } else {
                if ($worked >= $FULL_DAY_HOURS) {
                    $summary["present"]++;
                    $summary["full_days"]++;
                    $realStatus = "Full";
                    $status = "Present";
                    $statusofday = $realStatus;
                } elseif ($worked >= $HALF_DAY_HOURS) {
                    $summary["present"] += 0.5;
                    $summary["half_days"]++;
                    $realStatus = "Half";
                    $status = "Present";
                    $statusofday = $realStatus;
                } else {
                    $summary["absent"]++;
                    $status = "Absent";
                    $statusofday = "Absent";
                }

                if ($status == "Present") {
                    if ($isHoliday) {
                        $summary["holiday"]++;
                        $statusofday = "Holiday + " . $statusofday;
                    }
                    if ($isWeekOff) {
                        $summary["weekoff"]++;
                        $statusofday = "WeekOff + " . $statusofday;
                    }
                } else {
                    if ($isHoliday) {
                        $summary["holiday"]++;
                        $status = "Holiday";
                        $statusofday = "Absent + Holiday";
                    } elseif ($isWeekOff) {
                        $summary["weekoff"]++;
                        $status = "WeekOff";
                        $statusofday = "Absent + WeekOff";
                    }
                }
            }

            $summary["working_hours_decimal"] += $worked;
            $summary["working_hours_seconds"] += strtotime("1970-01-01 {$row['TotalWorkingHours']} UTC");
            $summary["overtime_seconds"] += strtotime("1970-01-01 {$row['OvertimeHours']} UTC");

            $dayStatus[$date] = $statusofday;

            $summary["details"][] = [
                "date" => $date,
                "status" => $status,
                "statusofday" => $statusofday,
                "attendance" => true,
                "leave_status" => $leaveStatus,
                "leave_type" => $leaveType,
                "leave_info" => $leaveRecord
            ] + $row;

            continue;
        }

        /* ================= APPROVED / PENDING LEAVE (NO ATTENDANCE) ================= */
        if ($isLeaveApproved || $isLeavePending) {
            $summary["leave"]++;
            $status = "Leave";
            $statusofday = $isLeaveApproved ? "Leave" : "Leave (Pending)";

            $dayStatus[$date] = $statusofday;

            $summary["details"][] = [
                "date" => $date,
                "status" => $status,
                "statusofday" => $statusofday,
                "attendance" => false,
                "leave_status" => $leaveStatus,
                "leave_type" => $leaveType,
                "leave_info" => $leaveRecord
            ];
            continue;
        }

        /* ================= NON PRESENT (REJECTED LEAVE OR NO LEAVE) ================= */
        if ($isFutureDate) {
            // Future dates in current or future month without attendance/leave are marked as Upcoming
            $status = "Upcoming";
            $statusofday = "Upcoming";
        } elseif ($isHoliday) {
            $summary["holiday"]++;
            $status = "Holiday";
            $statusofday = "Holiday";
        } elseif ($isWeekOff) {
            $summary["weekoff"]++;
            $status = "WeekOff";
            $statusofday = "WeekOff";
        } else {
            $summary["absent"]++;
            $status = "Absent";
            $statusofday = "Absent";
        }

        $dayStatus[$date] = $statusofday;

        $summary["details"][] = [
            "date" => $date,
            "status" => $status,
            "statusofday" => $statusofday,
            "attendance" => false,
            "leave_status" => $leaveStatus,
            "leave_type" => $leaveType,
            "leave_info" => $leaveRecord
        ];
    }

    /* ================= SANDWICH RULE ================= */
    foreach ($summary["details"] as $d) {

        if ($d["statusofday"] == "Holiday" || $d["statusofday"] == "WeekOff") {

            $prevStr = date("Y-m-d", strtotime("-1 day", strtotime($d["date"])));
            $nextStr = date("Y-m-d", strtotime("+1 day", strtotime($d["date"])));

            if (
                isset($dayStatus[$prevStr]) &&
                isset($dayStatus[$nextStr]) &&
                in_array($dayStatus[$prevStr], ["Absent", "Half", "MIS (Absent)", "Absent By Sandwich"]) &&
                in_array($dayStatus[$nextStr], ["Absent", "Half", "MIS (Absent)", "Absent By Sandwich"])
            ) {
                if ($d["statusofday"] == "Holiday")
                    $summary["holiday"]--;
                if ($d["statusofday"] == "WeekOff")
                    $summary["weekoff"]--;

                $d["statusofday"] = "Absent By Sandwich";
                $d["status"] = "Absent";
                $summary["absent"]++;
                $dayStatus[$d["date"]] = "Absent By Sandwich";
            }
        }
    }

    /* ================= FINAL TIME ================= */
    $totalMinutes = floor($summary["working_hours_decimal"] * 60);
    $summary["working_hours_hhmm"] =
        str_pad(floor($totalMinutes / 60), 2, "0", STR_PAD_LEFT) . ":" .
        str_pad($totalMinutes % 60, 2, "0", STR_PAD_LEFT);

    return $summary;
}

function EmployeeAttendanceDetails($conn, $ProjectID, $EmployeeID, $shortby = '', $shortbyvalue = '')
{
    // Base query
    $sql = "SELECT * FROM hrm_EmployeeAttendance 
            WHERE (tedit is null or tedit='') AND ProjectID = ? AND EmployeeID = ?";

    // Optional filter
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $sql .= " AND $shortby = ?";
    }

    $sql .= " ORDER BY AttendanceID ASC";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $stmt->bind_param('sss', $ProjectID, $EmployeeID, $shortbyvalue);
    } else {
        $stmt->bind_param('ss', $ProjectID, $EmployeeID);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function FullAttendanceDetailsOfEmployee($conn, $ProjectID, $shortby = '', $shortbyvalue = '')
{

    // Base query
    $sql = "SELECT 
                a.*,
                e.EmployeeID,
                CONCAT(
                    e.FirstName, ' ',
                    COALESCE(e.MiddleName, ''), ' ',
                    e.LastName
                ) AS FullName
            FROM hrm_EmployeeAttendance a
            JOIN hrm_EmployeesMaster e 
                ON e.EmployeeID = a.EmployeeID
            WHERE (a.tedit is null or a.tedit='') AND a.ProjectID = ?";

    // Optional filter
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $sql .= " AND a.$shortby = ?";
    }

    // Set order â†’ AttendanceDate DESC
    $sql .= " ORDER BY a.AttendanceDate DESC";

    // Prepare
    $stmt = $conn->prepare($sql);

    // Bind parameters
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $stmt->bind_param('ss', $ProjectID, $shortbyvalue);
    } else {
        $stmt->bind_param('s', $ProjectID);
    }

    // Execute and fetch
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

function getDistinctSalaryYears($conn, $CompanyID, $SubCompanyID, $ProjectID)
{
    $sql = "SELECT DISTINCT SalaryYear FROM hrm_EmployeeCalculatedSalary WHERE CompanyID = ? AND SubCompanyID = ? 
            AND ProjectID = ?  ORDER BY SalaryYear DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('sss', $CompanyID, $SubCompanyID, $ProjectID);
    $stmt->execute();
    $result = $stmt->get_result();
    $years = [];
    while ($row = $result->fetch_assoc()) {
        $years[] = $row['SalaryYear'];
    }
    return !empty($years) ? $years : ["message" => "âš ï¸ No salary records found."];
}
function getPayrollDeductionsMasterList($conn, $companyid, $sortBy = null, $sortByValue = null)
{
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT * FROM hrm_PayrollDeductionsMaster WHERE (tedit IS NULL OR tedit = '') AND companyid = ?";
    $params = [$companyid];
    $types = "i";
    if (!empty($sortBy) && !empty($sortByValue)) {
        if ($sortBy === "id") {
            $sql .= " AND id = ?";
            $types .= "i";
            $params[] = $sortByValue;
        } else if ($sortBy === "DeductionId") {
            $sql .= " AND DeductionId = ?";
            $types .= "s";
            $params[] = $sortByValue;
        } else if ($sortBy === "name") {
            $sql .= " AND DeductionName = ?";
            $types .= "s";
            $params[] = $sortByValue;
        } else if ($sortBy === "namelike") {
            $sql .= " AND DeductionName LIKE ?";
            $types .= "s";
            $params[] = "%$sortByValue%";
        } else if ($sortBy === "defaultforemployee") {
            $sql .= " AND defaultforemployee = ?";
            $types .= "s";
            $params[] = "1";
        } else if ($sortBy === "otherthandefault") {
            $sql .= " AND (defaultforemployee = null or defaultforemployee='' or defaultforemployee='0')";
        }
    }
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    // print_r($stmt);
    $stmt->execute();
    $result = $stmt->get_result();
    $DeductionMasterList = [];

    while ($row = $result->fetch_assoc()) {
        $DeductionMasterList[] = $row;
    }
    $stmt->close();
    return $DeductionMasterList;
}
function getPayrollDeductionsMasterGlobalList($conn, $sortBy = null, $sortByValue = null)
{
    $conn->query("SET NAMES 'UTF8'");

    $sql = "SELECT * FROM hrm_PayrollDeductions_GlobalMaster WHERE (tedit IS NULL OR tedit = '')";
    $params = [];
    $types = "";

    if (!empty($sortBy) && $sortByValue !== null) {
        switch (strtolower($sortBy)) {
            case "id":
                $sql .= " AND id = ?";
                $types .= "i";
                $params[] = (int) $sortByValue;
                break;

            case "componentname":
                $sql .= " AND ComponentName = ?";
                $types .= "s";
                $params[] = $sortByValue;
                break;

            case "sortname":
                $sql .= " AND sortname = ?";
                $types .= "s";
                $params[] = $sortByValue;
                break;

            case "defaultforemployee":
                $sql .= " AND defaultforemployee = ?";
                $types .= "i";
                $params[] = 1;
                break;

            case "otherthandefault":
                $sql .= " AND defaultforemployee = ?";
                $types .= "i";
                $params[] = 0;
                break;
        }
    }

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        error_log("SQL Prepare Error: " . $conn->error);
        return [];
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $DeductionMasterList = [];

    while ($row = $result->fetch_assoc()) {
        $DeductionMasterList[] = $row;
    }

    $stmt->close();
    return $DeductionMasterList;
}

function getEmployeeDeductionsComponents($conn, $companyid, $subcompanyid, $projectid, $employeeId)
{
    // Prepare the SQL query
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT id, companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, Frequency, EmployeeDeductionType, EmployeeDeductionValue, EmployeeDeductionBase, EmployerDeductionType, EmployerDeductionValue, EmployerDeductionBase, IsIncludedInCTC, IsIncludedInFBP, IsProRataBasis, description FROM hrm_EmployeeDeductionsComponents WHERE (tedit is null or tedit='') AND companyid= ? AND subcompanyid= ? AND projectid= ? AND EmployeeId = ?";
    // Prepare statement
    if ($stmt = $conn->prepare($sql)) {
        // Bind the EmployeeId parameter
        $stmt->bind_param("sssi", $companyid, $subcompanyid, $projectid, $employeeId);
        // Execute the query
        $stmt->execute();
        // Get the result set
        $result = $stmt->get_result();
        // Fetch data as an associative array
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        // Close the statement
        $stmt->close();
        return $data;
    } else {
        return []; // Return empty array if query fails
    }
}

function getHolidayTypes($conn)
{
    $sql = "SELECT * FROM hrm_PayrollHolidaysTypeMaster";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        $types = [];
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
        return $types;
    } else {
        return [];
    }
}

function getLeaveTypeMasterList($conn, $companyid, $sortBy = null, $sortByValue = null)
{
    $sql = "SELECT id, companyid, leave_code, leave_name, leave_type, max_days, carry_forward, description, status, created_at, updated_at FROM hrm_PayrollLeaveTypeMaster WHERE companyid = ? AND (tedit is null or tedit = '')";

    // Initialize $types and $params for binding
    $types = "s"; // Start with 's' for companyid
    $params = [$companyid];

    if (!empty($sortBy) && !empty($sortByValue)) { // Use !empty() for cleaner check
        if ($sortBy === "leave_code") {
            $sql .= " AND leave_code = ?";
            $types .= "s";
            $params[] = $sortByValue;
        } elseif ($sortBy === "leave_name") {
            $sql .= " AND leave_name = ?";
            $types .= "s";
            $params[] = $sortByValue;
        } elseif ($sortBy === "leave_type") {
            $sql .= " AND leave_type = ?";
            $types .= "s";
            $params[] = $sortByValue;
        } else {  // Handle invalid $sortBy
            error_log("Invalid sortBy value: " . $sortBy);
            return []; // Return empty array
        }
    }

    $stmt = $conn->prepare($sql); // Prepare ONLY ONCE

    if ($stmt === false) {
        error_log("Error preparing statement: " . $conn->error);
        return [];
    }

    // Bind parameters using the collected $types and $params
    $stmt->bind_param($types, ...$params); // Use the spread operator (...)

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $leaveTypeList = [];
        while ($row = $result->fetch_assoc()) {
            $leaveTypeList[] = $row;
        }
        $stmt->close();
        return $leaveTypeList;
    } else {
        error_log("Error executing statement: " . $stmt->error);
        $stmt->close();
        return [];
    }
}

function getHolidaysFromMaster($conn, $holidayyear, $companyid = null, $subcompanyid = null, $projectid = null)
{
    // Base SQL Query
    $sql = "SELECT * FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit ='') AND YEAR(HolidayDate) = ?";
    $params = [$holidayyear]; // Parameters array for binding
    $types = "i"; // Type for prepared statement (integer)

    // Apply filters dynamically
    if (!empty($companyid)) {
        $sql .= " AND companyid = ?";
        $params[] = intval($companyid);
        $types .= "i";
    }
    if (!empty($subcompanyid)) {
        $sql .= " AND subcompanyid = ?";
        $params[] = intval($subcompanyid);
        $types .= "i";
    }
    if (!empty($projectid)) {
        $sql .= " AND projectid = ?";
        $params[] = intval($projectid);
        $types .= "i";
    }

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }

    // Bind parameters dynamically
    $stmt->bind_param($types, ...$params);

    // Execute query
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch results
    $holidays = [];
    while ($row = $result->fetch_assoc()) {
        $holidays[] = $row;
    }

    // Close statement
    $stmt->close();

    return $holidays;
}

function getHolidaysYearsFromMaster($conn, $projectid)
{
    $holidaysYears = [];

    try {
        // âœ… Base SQL Query
        $sql = "SELECT DISTINCT YEAR(HolidayDate) AS HolidayYear 
                FROM hrm_PayrollHolidaysMaster 
                WHERE (tedit is null or tedit ='') AND projectid = ?";

        // âœ… Prepare statement
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception("Error preparing statement: " . $conn->error);
        }

        // âœ… Bind parameter
        $stmt->bind_param("s", $projectid);

        // âœ… Execute and fetch
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $holidaysYears[] = $row['HolidayYear'];
        }

        $stmt->close();

        // âœ… If no data found, return current year
        if (empty($holidaysYears)) {
            $holidaysYears[] = date('Y');
        }

    } catch (Exception $e) {
        error_log("Error in getHolidaysYearsFromMaster: " . $e->getMessage());
        // Fallback in case of any exception
        $holidaysYears = [date('Y')];
    }

    return $holidaysYears;
}


function calculateSalary($employee_id, $basic_salary, $year, $month, $weekly_offs = ['Sunday'])
{
    // Database connection (assumed)
    $conn = new mysqli("localhost", "username", "password", "database");

    // Fetch holidays from holiday master
    $holiday_query = "SELECT holiday_date FROM holiday_master WHERE YEAR(holiday_date) = $year AND MONTH(holiday_date) = $month";
    $result = $conn->query($holiday_query);
    $holidays = [];
    while ($row = $result->fetch_assoc()) {
        $holidays[] = $row['holiday_date'];
    }

    // Total days in the month
    $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    // Calculate weekly offs
    $weekly_off_days = [];
    for ($day = 1; $day <= $total_days; $day++) {
        $date = date("Y-m-d", strtotime("$year-$month-$day"));
        $day_name = date("l", strtotime($date)); // Get day name
        if (in_array($day_name, $weekly_offs)) {
            $weekly_off_days[] = $date;
        }
    }

    // Total non-working days (holidays + weekly offs)
    $non_working_days = array_merge($holidays, $weekly_off_days);

    // Payable days calculation
    $payable_days = $total_days - count($non_working_days);

    // Calculate final salary
    $daily_wage = $basic_salary / $total_days;
    $final_salary = $daily_wage * $payable_days;

    return round($final_salary, 2);
}

function getEmployeeNextAttendanceAction($conn, $LoginID)
{
    $AttendanceDate = date('Y-m-d');

    // âœ… Get Employee and Today's Attendance (JOIN)
    $stmt = $conn->prepare("
        SELECT 
            e.EmployeeID, 
            e.ProjectID, 
            a.AttendanceID, 
            a.CheckInTime, 
            a.CheckOutTime
        FROM hrm_EmployeesMaster e
        LEFT JOIN hrm_EmployeeAttendance a 
            ON e.EmployeeID = a.EmployeeID 
            AND a.AttendanceDate = ?
        WHERE  (a.tedit is null or a.tedit='') AND e.LoginID = ?
    ");
    $stmt->bind_param("ss", $AttendanceDate, $LoginID);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    // âœ… If no employee found
    if (!$row) {
        return [
            'status' => 'error',
            'message' => 'Employee not found for LoginID: ' . $LoginID
        ];
    }

    // Extract details
    $EmployeeID = $row['EmployeeID'];
    $ProjectID = $row['ProjectID'];
    $AttendanceID = $row['AttendanceID'] ?? null;
    $CheckInTime = $row['CheckInTime'] ?? null;
    $CheckOutTime = $row['CheckOutTime'] ?? null;

    // âœ… Determine next action
    if (empty($AttendanceID) || empty($CheckInTime)) {
        $nextAction = 'MarkIn';
        $message = 'Mark In required.';
    } elseif (empty($CheckOutTime)) {
        $nextAction = 'MarkOut';
        $message = 'Mark Out required.';
    } else {
        $nextAction = 'MarkIn';
        $message = 'Previous session completed. New Mark In required.';
    }

    // âœ… Return structured data
    return [
        'status' => 'success',
        'EmployeeID' => $EmployeeID,
        'ProjectID' => $ProjectID,
        'AttendanceID' => $AttendanceID,
        'nextAction' => $nextAction,
        'message' => $message
    ];
}


function fetchEmployeeCalculatedAttendance($conn, $companyID, $subCompanyID, $projectID, $year, $month, $filterBy = null, $filterValue = null)
{
    $data = [];
    $condition = "";
    // Apply filters based on conditions
    if ($filterBy === "all") {
        $condition = "";  // No additional conditions
    } elseif ($filterBy === "EmployeeID") {
        $condition = " AND a.EmployeeID = ?";
    } elseif ($filterBy === "ExtraWorkingDays") {
        $condition = " AND a.ExtraWorkingDays > 0";
    } elseif ($filterBy === "AbsentDays") {
        $condition = " AND a.AbsentDays > 0";
    }

    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT a.* FROM hrm_EmployeeCalculateAttendance a JOIN hrm_EmployeesMaster e ON a.EmployeeID = e.EmployeeID WHERE a.CompanyID = ? AND a.SubCompanyID = ? AND a.ProjectID = ? AND a.AttendanceYear = ? AND a.AttendanceMonth = ? $condition";
    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters dynamically
        if ($filterBy === "EmployeeID" && !empty($filterValue)) {
            $stmt->bind_param("sssiii", $companyID, $subCompanyID, $projectID, $year, $month, $filterValue);
        } else {
            $stmt->bind_param("sssii", $companyID, $subCompanyID, $projectID, $year, $month);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch all records
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        die("Error in SQL: " . $conn->error);
    }

    return $data;
}

function getEmployeePaySchedule($conn, $companyid, $projectid, $sortBy = null, $sortByValue = null)
{
    // Allowed columns for sorting to prevent SQL injection
    $allowedSortBy = ['EmployeeId', 'projectid'];
    // Validate sortBy input
    if (!in_array($sortBy, $allowedSortBy)) {
        $sortBy = null;
    }
    // Base SQL query
    $sql = "SELECT id, companyid, projectid, EmployeeId, WorkingDaysPerWeek, Monday, 
                   Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday, 
                   PayScheduleType, PayDay, FirstPayDate, LastProcessedDate 
            FROM hrm_EmployeePaySchedule 
            WHERE (tedit IS NULL OR tedit='') AND companyid = ? AND projectid = ?";
    // Modify SQL query based on sortBy conditions
    if ($sortBy === 'EmployeeId' && $sortByValue != "") {
        $sql .= " AND EmployeeId = ?";
    }
    $sql .= " ORDER BY EmployeeId ASC";
    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $sql);
    // Bind parameters dynamically
    if ($sortBy === 'EmployeeId' && !empty($sortByValue)) {
        mysqli_stmt_bind_param($stmt, "iii", $companyid, $projectid, $sortByValue);
    } else {
        mysqli_stmt_bind_param($stmt, "ii", $companyid, $projectid);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    // Fetch data
    $data = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
    }
    return $data;
}

function getDeductionAmount($conn, $companyId, $subCompanyId, $projectId, $employeeId, $deductionId, $deductedFromSalaryId)
{
    try {
        $query = "SELECT DeductionAmount FROM hrm_EmployeeDeductionsTransactions 
                  WHERE companyid = :companyId 
                  AND subcompanyid = :subCompanyId 
                  AND projectid = :projectId 
                  AND EmployeeId = :employeeId 
                  AND DeductionId = :deductionId 
                  AND DeductedFromSalaryId = :deductedFromSalaryId";

        $stmt = $conn->prepare($query);
        $stmt->bindValue(':companyId', $companyId, PDO::PARAM_INT);
        $stmt->bindValue(':subCompanyId', $subCompanyId, PDO::PARAM_INT);
        $stmt->bindValue(':projectId', $projectId, PDO::PARAM_INT);
        $stmt->bindValue(':employeeId', $employeeId, PDO::PARAM_INT);
        $stmt->bindValue(':deductionId', $deductionId, PDO::PARAM_INT);
        $stmt->bindValue(':deductedFromSalaryId', $deductedFromSalaryId, PDO::PARAM_INT);

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ? floatval($result['DeductionAmount']) : 0;
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        return 0; // Return 0 in case of an error
    }
}

function getEmployeeSalaryDeductions($conn, $companyId, $subCompanyId, $projectId, $employeeId, $salaryYear, $salaryMonth)
{
    $query = "SELECT id, companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, DeductionType, DeductionBase, DeductionValue, DeductionAmount, SalaryYear, SalaryMonth, DeductedFromSalaryId, processed_at, created_at FROM hrm_EmployeeDeductionsTransactions 
              WHERE companyid = ? 
              AND subcompanyid = ? 
              AND projectid = ? 
              AND EmployeeId = ? 
              AND SalaryYear = ? 
              AND SalaryMonth = ?";
    if ($stmt = $conn->prepare($query)) {
        $stmt->bind_param("iiiiii", $companyId, $subCompanyId, $projectId, $employeeId, $salaryYear, $salaryMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        $deductions = [];
        while ($row = $result->fetch_assoc()) {
            $deductions[] = $row;
        }
        $stmt->close();
        return $deductions;
    } else {
        error_log("Database error: " . $conn->error);
        return []; // Return an empty array on failure
    }
}

function getEPFTransactions($hrmconn, $companyid, $subcompanyid, $projectid, $yearvalue, $monthvalue, $shortby = null, $shortbyvalue = null)
{
    $data = [];
    $sql = "SELECT id, companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName,
            BasicSalary, EmployeePF, EPS, EmployerPF, EDLI, PFAdmin, EDLIAdmin, TotalEmployer, GrandTotal,
            SalaryYear, SalaryMonth, DeductedFromSalaryId, processed_at, created_at FROM hrm_EmployeeEPFTransactions WHERE companyid = ? AND subcompanyid = ? AND projectid = ? 
            AND SalaryYear = ? AND SalaryMonth = ?";
    // Optional filter
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $allowedColumns = ['EmployeeId', 'DeductionId', 'DeductionName']; // whitelist
        if (in_array($shortby, $allowedColumns)) {
            $sql .= " AND `$shortby` = ?";
        } else {
            return ["status" => "error", "message" => "Invalid shortby column"];
        }
    }
    $stmt = $hrmconn->prepare($sql);
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $stmt->bind_param(
            "sssss" . (is_numeric($shortbyvalue) ? "i" : "s"),
            $companyid,
            $subcompanyid,
            $projectid,
            $yearvalue,
            $monthvalue,
            $shortbyvalue
        );
    } else {
        $stmt->bind_param("sssss", $companyid, $subcompanyid, $projectid, $yearvalue, $monthvalue);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function getEmployeeDeductionRegId($hrmconn, $companyid, $projectid, $employeeid, $deductionid) {
    $regid = "";
    if (empty($employeeid)) return $regid;
    $sql = "SELECT regid FROM hrm_EmployeeDeductionsComponents 
            WHERE companyid = ? AND projectid = ? AND EmployeeId = ? 
              AND (DeductionId = ? OR 
                   (? = 1 AND (UPPER(DeductionName) LIKE '%EPF%' OR UPPER(DeductionName) LIKE '%PF%')) OR 
                   (? = 3 AND (UPPER(DeductionName) LIKE '%ESIC%' OR UPPER(DeductionName) LIKE '%ESI%')))
              AND (tedit IS NULL OR tedit = '') 
            ORDER BY id DESC LIMIT 1";
    $stmt = $hrmconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("iiiiii", $companyid, $projectid, $employeeid, $deductionid, $deductionid, $deductionid);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $regid = $row['regid'] ?? "";
        }
        $stmt->close();
    }
    return $regid;
}

/**
 * Save EPF (DeductionId 1) / ESIC (DeductionId 3) registration no. into hrm_EmployeeDeductionsComponents.regid
 * - updates the active row if there is one
 * - else updates the latest inactive row (does NOT re-activate the deduction)
 * - else (no row yet, e.g. salary not set up) inserts a placeholder row marked 'Deactive';
 *   the salary save later updates this same row by DeductionId and keeps the regid
 */
function saveEmployeeDeductionRegId($hrmconn, $companyid, $subcompanyid, $projectid, $employeeid, $deductionid, $deductionname, $regid)
{
    $regid = trim((string)$regid);
    if (empty($employeeid)) return false;

    $stmt = $hrmconn->prepare("SELECT id FROM hrm_EmployeeDeductionsComponents
        WHERE companyid = ? AND projectid = ? AND EmployeeId = ? AND DeductionId = ?
        ORDER BY (tedit IS NULL OR tedit = '') DESC, id DESC LIMIT 1");
    if (!$stmt) return false;
    $stmt->bind_param("iiii", $companyid, $projectid, $employeeid, $deductionid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($row) {
        $up = $hrmconn->prepare("UPDATE hrm_EmployeeDeductionsComponents SET regid = ? WHERE id = ?");
        if (!$up) return false;
        $up->bind_param("si", $regid, $row['id']);
        $ok = $up->execute();
        $up->close();
        return $ok;
    }
    if ($regid === '') return true; // nothing to store

    $ins = $hrmconn->prepare("INSERT INTO hrm_EmployeeDeductionsComponents (companyid, subcompanyid, projectid, EmployeeId, DeductionId, DeductionName, regid, tedit)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'Deactive')");
    if (!$ins) return false;
    $ins->bind_param("iiiiiss", $companyid, $subcompanyid, $projectid, $employeeid, $deductionid, $deductionname, $regid);
    $ok = $ins->execute();
    $ins->close();
    return $ok;
}

// Reg no. for the work-details form: active row first, otherwise the latest row of that DeductionId
function getEmployeeDeductionRegIdAny($hrmconn, $companyid, $projectid, $employeeid, $deductionid)
{
    $regid = getEmployeeDeductionRegId($hrmconn, $companyid, $projectid, $employeeid, $deductionid);
    if ($regid !== "" && $regid !== null) return $regid;
    $stmt = $hrmconn->prepare("SELECT regid FROM hrm_EmployeeDeductionsComponents
        WHERE companyid = ? AND projectid = ? AND EmployeeId = ? AND DeductionId = ? AND regid IS NOT NULL AND regid <> ''
        ORDER BY id DESC LIMIT 1");
    if (!$stmt) return "";
    $stmt->bind_param("iiii", $companyid, $projectid, $employeeid, $deductionid);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['regid'] ?? "";
}

// function getEmployeeLeavesData($hrmconn, $projectid, $shortby = '', $shortbyvalue = '') {
//     $data = [];

//     // âœ… Base SQL query
//     $sql = "SELECT LeaveID, EmployeeID, CompanyID, SubCompanyID, ProjectID, LeaveType, 
//                    LeaveStartDate, LeaveEndDate, TotalLeaveDays, attFilepath, Remarks, 
//                    Status, created_at, created_by, approved_at, approved_by 
//             FROM hrm_EmployeeLeaves 
//             WHERE ProjectID = ?";

//     // âœ… Add optional filters if provided
//     if (!empty($shortby) && !empty($shortbyvalue)) {
//         $allowedColumns = ['EmployeeID', 'Status', 'LeaveType', 'CompanyID', 'SubCompanyID'];
//         if($shortby=="statuswihdate"){
//             $sql .= " AND LeaveStartDate>=? AND LeaveStartDate<=? AND status = ?";
//         }else if (in_array($shortby, $allowedColumns)) {
//             $sql .= " AND $shortby = ?";
//         } else {
//             // Invalid column name safeguard
//             return ['status' => 'error', 'message' => 'Invalid filter column'];
//         }
//     }

//     // âœ… Order by created_at descending
//     $sql .= " ORDER BY created_at DESC";

//     // âœ… Prepare the SQL statement
//     $stmt = $hrmconn->prepare($sql);
//     if (!$stmt) {
//         return ['status' => 'error', 'message' => 'SQL preparation failed: ' . $hrmconn->error];
//     }

//     // âœ… Bind parameters dynamically
//     if (!empty($shortby) && !empty($shortbyvalue)) {
//         $stmt->bind_param("ss", $projectid, $shortbyvalue);
//     } else {
//         $stmt->bind_param("s", $projectid);
//     }

//     // âœ… Execute and fetch results
//     $stmt->execute();
//     $result = $stmt->get_result();

//     while ($row = $result->fetch_assoc()) {
//         $data[] = $row;
//     }

//     $stmt->close();

//     // âœ… Return structured data
//     return  $data;
// }

function getEmployeeLeavesData($hrmconn, $projectid, $shortby = '', $shortbyvalue = [])
{
    $data = [];
    $params = [];
    $types = "";

    // âœ… Base SQL
    $sql = "SELECT 
                LeaveID, EmployeeID, CompanyID, SubCompanyID, ProjectID, LeaveType,
                LeaveStartDate, LeaveEndDate, TotalLeaveDays, attFilepath, Remarks,
                Status, created_at, created_by, approved_at, approved_by
            FROM hrm_EmployeeLeaves
            WHERE ProjectID = ? AND Status NOT IN ('Removed', 'Cancelled')";

    $params[] = $projectid;
    $types .= "s";

    // âœ… Allowed columns for direct filtering
    $allowedColumns = ['EmployeeID', 'Status', 'LeaveType', 'CompanyID', 'SubCompanyID'];

    // ðŸ” Filters
    if (!empty($shortby)) {

        // âœ… Status + Date Range
        if ($shortby === "statuswithdate") {
            if ($shortbyvalue['status']) {
                $sql .= " AND LeaveStartDate >= ? AND LeaveStartDate <= ? AND Status = ?";
                $params[] = $shortbyvalue['from'];
                $params[] = $shortbyvalue['to'];
                $params[] = $shortbyvalue['status'];
                $types .= "sss";
            } else {
                $sql .= " AND LeaveStartDate >= ? AND LeaveStartDate <= ?";
                $params[] = $shortbyvalue['from'];
                $params[] = $shortbyvalue['to'];
                $types .= "ss";
            }
        }
        // âœ… Simple column filter
        else if (in_array($shortby, $allowedColumns) && !empty($shortbyvalue)) {

            $sql .= " AND $shortby = ?";
            $params[] = $shortbyvalue;
            $types .= "s";

        }
        // âŒ Invalid column
        else if (!empty($shortby)) {
            return ['status' => 'error', 'message' => 'Invalid filter column'];
        }
    }

    // ðŸ”½ Order
    $sql .= " ORDER BY created_at DESC";

    // âœ… Prepare
    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => $hrmconn->error];
    }

    // âœ… Bind dynamically
    $stmt->bind_param($types, ...$params);

    // âœ… Execute
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    return $data;
}


function getApprovedLeavesCount($hrmconn, $projectid, $EmployeeID, $year, $LeaveType = '', $shortby = '', $shortbyvalue = '')
{

    if (empty($projectid) || empty($year)) {
        return ['status' => 'error', 'message' => 'ProjectID and Year are required'];
    }

    $yearStart = $year . "-01-01";
    $yearEnd = $year . "-12-31";

    $sql = "SELECT 
                LeaveType,
                SUM(
                    CASE 
                        WHEN LeaveStartDate <= ? AND LeaveEndDate >= ?
                        THEN DATEDIFF(
                                LEAST(LeaveEndDate, ?),
                                GREATEST(LeaveStartDate, ?)
                             ) + 1
                        ELSE 0
                    END
                ) AS approvedDays
            FROM hrm_EmployeeLeaves
            WHERE ProjectID = ?
              AND EmployeeID = ?
              AND Status = 'Approved'
              AND LeaveEndDate >= ?
              AND LeaveStartDate <= ?
            ";

    $allowedColumns = ['CompanyID', 'SubCompanyID'];

    if (!empty($LeaveType)) {
        $sql .= " AND LeaveType = ?";
    }

    if (!empty($shortby) && !empty($shortbyvalue)) {
        if (in_array($shortby, $allowedColumns)) {
            $sql .= " AND $shortby = ?";
        } else {
            return ['status' => 'error', 'message' => 'Invalid filter column'];
        }
    }

    $sql .= " GROUP BY LeaveType";

    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'SQL preparation failed: ' . $hrmconn->error];
    }

    // Correct Param Order
    $params = [
        $yearEnd,   // LeaveStartDate <= ?
        $yearStart, // LeaveEndDate >= ?
        $yearEnd,   // LEAST(..., ?)
        $yearStart, // GREATEST(..., ?)
        $projectid,
        $EmployeeID,
        $yearStart, // AND LeaveEndDate >= ?
        $yearEnd    // AND LeaveStartDate <= ?
    ];

    $types = "sssssi ss"; // correct type string without spaces
    $types = "sssssi ss";
    $types = "sssssi ss";
    $types = "ssssssss"; // correct

    if (!empty($LeaveType)) {
        $params[] = $LeaveType;
        $types .= "s";
    }

    if (!empty($shortby) && !empty($shortbyvalue)) {
        $params[] = $shortbyvalue;
        $types .= "s";
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'LeaveType' => $row['LeaveType'],
            'ApprovedDays' => (int) $row['approvedDays']
        ];
    }

    $stmt->close();
    return $data;
}



function getApprovedLeavesFullDetails($hrmconn, $projectid, $EmployeeID, $year, $shortby = '', $shortbyvalue = '')
{

    if (empty($projectid) || empty($EmployeeID) || empty($year)) {
        return ['status' => 'error', 'message' => 'ProjectID, EmployeeID, and Year are required'];
    }

    // Year start & end date
    $yearStart = $year . "-01-01";
    $yearEnd = $year . "-12-31";

    /*
       Correct Overlap Logic:
       OverlapDays = DATEDIFF( LEAST(LeaveEndDate, yearEnd),
                              GREATEST(LeaveStartDate, yearStart) ) + 1
    */

    $sql = "SELECT 
                LeaveID,
                LeaveType,
                EmployeeID,
                CompanyID,
                SubCompanyID,
                ProjectID,
                LeaveStartDate,
                LeaveEndDate,
                TotalLeaveDays,
                attFilepath,
                Remarks,
                Status,
                created_at,
                created_by,
                approved_at,
                approved_by,

                -- Calculate correct overlapping days
                GREATEST(
                    DATEDIFF(
                        LEAST(LeaveEndDate, ?),
                        GREATEST(LeaveStartDate, ?)
                    ) + 1,
                0) AS ApprovedDaysInYear

            FROM hrm_EmployeeLeaves
            WHERE ProjectID = ?
              AND EmployeeID = ?
              AND LeaveEndDate >= ?
              AND LeaveStartDate <= ?
              AND Status NOT IN ('Removed', 'Cancelled')
            ";

    // Allowed filter columns
    $allowedColumns = ['CompanyID', 'SubCompanyID'];

    // Optional filter
    if (!empty($shortby) && !empty($shortbyvalue)) {
        if (in_array($shortby, $allowedColumns)) {
            $sql .= " AND $shortby = ?";
        } else {
            return ['status' => 'error', 'message' => 'Invalid filter column'];
        }
    }

    // Order results
    $sql .= " ORDER BY LeaveStartDate ASC";

    // Prepare
    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'SQL preparation failed: ' . $hrmconn->error];
    }

    // Base bind params
    $params = [
        $yearEnd,       // LEAST(LeaveEndDate, yearEnd)
        $yearStart,     // GREATEST(LeaveStartDate, yearStart)
        $projectid,
        $EmployeeID,
        $yearStart,     // LeaveEndDate >= yearStart
        $yearEnd        // LeaveStartDate <= yearEnd
    ];

    $types = "ssssss";

    // Add optional filter
    if (!empty($shortby) && !empty($shortbyvalue)) {
        $params[] = $shortbyvalue;
        $types .= "s";
    }

    // Bind
    $stmt->bind_param($types, ...$params);

    // Execute
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        // Correct negative case (safe guard)
        if ($row['ApprovedDaysInYear'] < 0) {
            $row['ApprovedDaysInYear'] = 0;
        }
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}





function uploadFile($fileKey, $uploadDir)
{
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true); // Ensure the directory is created if it doesn't exist
    }

    if (isset($_FILES[$fileKey])) {
        $fileError = $_FILES[$fileKey]['error'];
        if ($fileError === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES[$fileKey]['tmp_name'];
            $fileName = uniqid() . "_" . $_FILES[$fileKey]['name'];
            $destPath = $uploadDir . $fileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                return str_replace("../", "", $destPath);
            } else {
                return "";
            }
        } else {
            // Handle various file upload errors
            switch ($fileError) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    return "File size exceeds the allowed limit.";
                case UPLOAD_ERR_PARTIAL:
                    return "File was only partially uploaded.";
                case UPLOAD_ERR_NO_FILE:
                    return "";
                case UPLOAD_ERR_NO_TMP_DIR:
                    return "";
                case UPLOAD_ERR_CANT_WRITE:
                    return "";
                case UPLOAD_ERR_EXTENSION:
                    return "";
                default:
                    return "";
            }
        }
    }
    return null;
}

function getDaysInMonthForSalary(int $year, int $month): int|false
{
    // Validate month and year
    if ($month < 1 || $month > 12 || $year < 1) {
        return false; // Indicate an invalid month or year
    }
    // Get number of days in the given month and year
    return cal_days_in_month(CAL_GREGORIAN, $month, $year);
}

function calculateDaysBetweenDates($startDate, $endDate)
{
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $diff = $start->diff($end);
    return $diff->days + 1;
}

function getLastDateOfMonth($month, $year)
{
    return date("Y-m-t", strtotime("$year-$month-01"));
}

function getFirstDateOfMonth($month, $year)
{
    return date("Y-m-01", strtotime("$year-$month-01"));
}


function getWorkingDaysInMonth($conn, $companyid, $subcompanyid, $projectid, $employeeId, int $year, int $month): int
{
    $workingdaysinmonth = 0;

    // Validate month and year
    if ($month < 1 || $month > 12 || $year < 1) {
        return -1; // Indicate an invalid month or year
    }

    // employee info
    $empinfo = fetchEmployeeData($conn, $companyid, $subcompanyid, $projectid, "EmployeeID", $employeeId);
    $DateOfJoining = $empinfo[0]['DateOfJoining'];
    $dojyear = "";
    $dojmonth = "";
    if ($DateOfJoining) {
        $dojyear = date('Y', strtotime($DateOfJoining));
        $dojmonth = date('m', strtotime($DateOfJoining));
    }
    $DateOfResignation = $empinfo[0]['DateOfResignation'];
    $doryear = "";
    $dormonth = "";
    if ($DateOfResignation) {
        $doryear = date('Y', strtotime($DateOfResignation));
        $dormonth = date('m', strtotime($DateOfResignation));
    }

    if (($dojyear == $year) && ($dojmonth == $month)) {
        $DateOfJoining = date('Y-m-d', strtotime($DateOfJoining));
        $daysInMonth = calculateDaysBetweenDates($DateOfJoining, getLastDateOfMonth($month, $year));
    } else if (($doryear == $year) && ($dormonth == $month)) {
        $DateOfResignation = date('Y-m-d', strtotime($DateOfResignation));
        $daysInMonth = $daysInMonth = calculateDaysBetweenDates(getFirstDateOfMonth($month, $year), $DateOfResignation);
    } else {
        // Get total days in the month
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }
    // Get Holidays in this month
    $ttlHolidays = getHoliDaysInMonth($conn, $companyid, $subcompanyid, $projectid, $employeeId, $year, $month);

    // Get non-working days of the employee
    $ttlWeekOff = getEmployeeWeekOffCountInMonth($conn, $companyid, $subcompanyid, $projectid, $employeeId, $year, $month);

    // Calculate working days
    $workingdaysinmonth = $daysInMonth - ($ttlHolidays + $ttlWeekOff);

    return max($workingdaysinmonth, 0); // Ensure non-negative value
}

function getEmployeeWeekOffCountInMonth($conn, $companyid, $subcompanyid, $projectid, $employeeId, int $year, int $month)
{

    // employee info
    $empinfo = fetchEmployeeData($conn, $companyid, $subcompanyid, $projectid, "EmployeeID", $employeeId);
    $DateOfJoining = $empinfo[0]['DateOfJoining'];
    $DateOfResignation = $empinfo[0]['DateOfResignation'];

    $dojyear = "";
    $dojmonth = "";
    if ($DateOfJoining) {
        $dojyear = date('Y', strtotime($DateOfJoining));
        $dojmonth = date('m', strtotime($DateOfJoining));
    }

    $doryear = "";
    $dormonth = "";
    if ($DateOfResignation) {
        $doryear = date('Y', strtotime($DateOfResignation));
        $dormonth = date('m', strtotime($DateOfResignation));
    }


    // Get non-working days of the employee
    $notWorkDays = getEmployeeNotWorkingDaysInWeek($conn, $companyid, $projectid, $employeeId);
    $ttlWeekOff = 0;
    if ($notWorkDays != "") {
        foreach ($notWorkDays as $dayName) {
            if ($dayName != "") {
                if (($dojyear == $year) && ($dojmonth == $month)) {
                    $StartDate = date('Y-m-d', strtotime($DateOfJoining));
                    $EndDate = getLastDateOfMonth($month, $year);
                    $ttlWeekOff += countDayOccurrencesInMonthWithDates($dayName, $StartDate, $EndDate);
                } else if (($doryear == $year) && ($dormonth == $month)) {
                    $StartDate = getFirstDateOfMonth($month, $year);
                    $EndDate = date('Y-m-d', strtotime($DateOfResignation));
                    $ttlWeekOff += countDayOccurrencesInMonthWithDates($dayName, $StartDate, $EndDate);
                } else {
                    $StartDate = getFirstDateOfMonth($month, $year);
                    $EndDate = getLastDateOfMonth($month, $year);
                    $ttlWeekOff += countDayOccurrencesInMonthWithDates($dayName, $StartDate, $EndDate);
                }
            }
        }
    }
    return $ttlWeekOff;
}

function getHoliDaysInMonth($conn, $companyid, $subcompanyid, $projectid, $employeeId, int $year, int $month): int
{
    $workingdaysinmonth = 0;

    // Validate month and year
    if ($month < 1 || $month > 12 || $year < 1) {
        return -1; // Indicate an invalid month or year
    }

    // employee info
    $empinfo = fetchEmployeeData($conn, $companyid, $subcompanyid, $projectid, "EmployeeID", $employeeId);
    $DateOfJoining = $empinfo[0]['DateOfJoining'];
    $dojyear = "";
    $dojmonth = "";
    if ($DateOfJoining) {
        $dojyear = date('Y', strtotime($DateOfJoining));
        $dojmonth = date('m', strtotime($DateOfJoining));
    }
    $DateOfResignation = $empinfo[0]['DateOfResignation'];
    $doryear = "";
    $dormonth = "";
    if ($DateOfResignation) {
        $doryear = date('Y', strtotime($DateOfResignation));
        $dormonth = date('m', strtotime($DateOfResignation));
    }

    $ttlHolidays = 0;
    if (($dojyear == $year) && ($dojmonth == $month)) {
        $DateOfJoining = date('Y-m-d', strtotime($DateOfJoining));
        $sqlQry = $conn->prepare("SELECT COUNT(*) as ttldays FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit ='') AND YEAR(HolidayDate) = ? AND MONTH(HolidayDate) = ? AND companyid = ? AND projectid = ? AND HolidayDate >= ?");
        $sqlQry->bind_param('iiiss', $year, $month, $companyid, $projectid, $DateOfJoining);
        if ($sqlQry->execute()) {
            $resultQry = $sqlQry->get_result();
            if ($row = $resultQry->fetch_assoc()) {
                $ttlHolidays = (int) $row['ttldays']; // Ensure it's an integer
            }
        }
    } else if (($doryear == $year) && ($dormonth == $month)) {
        $DateOfResignation = date('Y-m-d', strtotime($DateOfResignation));
        $sqlQry = $conn->prepare("SELECT COUNT(*) as ttldays FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit ='') AND YEAR(HolidayDate) = ? AND MONTH(HolidayDate) = ? AND companyid = ? AND projectid = ? AND HolidayDate <= ?");
        $sqlQry->bind_param('iiiss', $year, $month, $companyid, $projectid, $DateOfResignation);
        if ($sqlQry->execute()) {
            $resultQry = $sqlQry->get_result();
            if ($row = $resultQry->fetch_assoc()) {
                $ttlHolidays = (int) $row['ttldays']; // Ensure it's an integer
            }
        }
    } else {
        $sqlQry = $conn->prepare("SELECT COUNT(*) as ttldays FROM hrm_PayrollHolidaysMaster WHERE (tedit is null or tedit ='') AND YEAR(HolidayDate) = ? AND MONTH(HolidayDate) = ? AND companyid = ? AND projectid = ?");
        $sqlQry->bind_param('iiii', $year, $month, $companyid, $projectid);
        if ($sqlQry->execute()) {
            $resultQry = $sqlQry->get_result();
            if ($row = $resultQry->fetch_assoc()) {
                $ttlHolidays = $row['ttldays'];
            }
        }
    }
    return max($ttlHolidays, 0); // Ensure non-negative value
}


function getEmployeeWorkingDaysInWeek($conn, $companyid, $projectid, $employeeId)
{
    // SQL query to fetch working days
    $sql = "SELECT Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday 
            FROM hrm_EmployeePaySchedule 
            WHERE companyid = ? AND projectid = ? AND EmployeeId = ? 
            LIMIT 1";

    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $companyid, $projectid, $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if data exists
    if ($row = mysqli_fetch_assoc($result)) {
        $workingDays = [];

        // Days mapping
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            if ($row[$day] == 1) {  // Only add days where value is 1
                $workingDays[] = $day;
            }
        }

        return $workingDays; // Return array of active working days
    }

    return []; // Return empty array if no record found
}

function findTotalDaysCell($exceldata, $findtextIndex)
{
    $maxRows = min(count($exceldata), 20);
    $maxCols = 50;

    for ($row = 0; $row < $maxRows; $row++) {
        if (!isset($exceldata[$row]) || !is_array($exceldata[$row]))
            continue;

        $cols = min(count($exceldata[$row]), $maxCols);
        for ($col = 0; $col < $cols; $col++) {
            $cellValue = trim(strtoupper((string) $exceldata[$row][$col]));
            $findtextIndex = trim(strtoupper((string) $findtextIndex));
            if ($cellValue === $findtextIndex || strpos($cellValue, $findtextIndex) !== false) {
                return [
                    'rowIndex' => $row,
                    'cellIndex' => $col,
                    'cellValue' => $exceldata[$row][$col]
                ];
            }
        }
    }

    return null; // Not found
}

function getEmployeeNotWorkingDaysInWeek($conn, $companyid, $projectid, $employeeId)
{
    // SQL query to fetch working days
    $sql = "SELECT Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday 
            FROM hrm_EmployeePaySchedule 
            WHERE companyid = ? AND projectid = ? AND EmployeeId = ? 
            LIMIT 1";

    // Prepare and execute query
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "iii", $companyid, $projectid, $employeeId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Check if data exists
    if ($row = mysqli_fetch_assoc($result)) {
        $workingDays = [];

        // Days mapping
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($days as $day) {
            if ($row[$day] == 0) {  // Only add days where value is 1
                $workingDays[] = $day;
            }
        }

        return $workingDays; // Return array of active working days
    }

    return []; // Return empty array if no record found
}

function generateAndSaveEmployeePayslip($hrmconn, $conn, $SalaryId)
{
    $rtmsg = array();

    // Fetch salary record
    $ESQ = "SELECT * FROM hrm_EmployeeCalculatedSalary WHERE SalaryID = '" . mysqli_real_escape_string($hrmconn, $SalaryId) . "'";
    $result = query($hrmconn, $ESQ);

    if (!$result || $result->num_rows === 0) {
        return array('status' => 'Error', 'message' => 'No records found.', 'filepath' => '');
    }

    $row = $result->fetch_assoc();

    $companyid = $row['CompanyID'];
    $subcompanyid = $row['SubCompanyID'];
    $projectid = $row['ProjectID'];
    $employeeid = $row['EmployeeID'];

    // Attendance Details
    $att = fetchEmployeeCalculatedAttendance($hrmconn, $companyid, $subcompanyid, $projectid, $row['SalaryYear'], $row['SalaryMonth'], "EmployeeID", $employeeid);
    $payableDays = str_replace(".00", "", $att[0]['CalculatedPresentDays'] ?? 0);

    // Company and Project Details
    $cmpprj = getprojectdetailwithcmpsubcmp($conn, $companyid, $subcompanyid, "withauthrizedprojects", $projectid);
    $companyname = $cmpprj[0]['companyname'] ?? '';
    $projectname = $cmpprj[0]['projectname'] ?? '';

    // Employee Details
    $eDkey = fetchEmployeeData($hrmconn, $companyid, $subcompanyid, $projectid, 'EmployeeID', $employeeid);
    if (count($eDkey) == 0) {
        return array('status' => 'Error', 'message' => 'Employee details not found.', 'filepath' => '');
    }

    $e = $eDkey[0];
    $EmployeeName = trim($e['TitleName'] . ' ' . $e['FirstName'] . ' ' . $e['MiddleName'] . ' ' . $e['LastName']);
    $GenderName = $e['Gender'] ?? '';
    $DateOfJoining = (!empty($e['DateOfJoining'])) ? date('d-M-Y', strtotime($e['DateOfJoining'])) : 'N/A';
    $DesignationID = $e['DesignationID'];
    $DepartmentID = $e['DepartmentID'];

    // Designation and Department
    $DesignationName = 'N/A';
    if (!empty($DesignationID)) {
        $DsN = fetchDesignationsMaster($hrmconn, $companyid, 'DesignationID', $DesignationID);
        $DesignationName = $DsN[0]['DesignationName'] ?? 'N/A';
    }

    $DepartmentName = '';
    if (!empty($DepartmentID)) {
        $DpI = fetchDepartmentMaster($hrmconn, $companyid, 'DepartmentID', $DepartmentID);
        $DepartmentName = $DpI[0]['DepartmentName'] ?? '';
    }

    // Deductions
    $EmpDdct = getEmployeeSalaryDeductions($hrmconn, $companyid, $subcompanyid, $projectid, $employeeid, $row['SalaryYear'], $row['SalaryMonth']);
    $EPF = $ESIC = $INCOMTAX = $OtherDeduction = 0;

    foreach ($EmpDdct as $deduct) {
        switch ($deduct['DeductionId']) {
            case 1:
                $EPF = $deduct['DeductionAmount'];
                break;
            case 3:
                $ESIC = $deduct['DeductionAmount'];
                break;
            case 4:
                $INCOMTAX = $deduct['DeductionAmount'];
                break;
            default:
                $OtherDeduction += $deduct['DeductionAmount'];
                break;
        }
    }

    // Compile employee salary data
    $EmployeeData = array(
        'EmployeeID' => $employeeid,
        'EmployeeName' => $EmployeeName,
        'GenderName' => $GenderName,
        'DateOfJoining' => $DateOfJoining,
        'DesignationName' => $DesignationName,
        'DepartmentName' => $DepartmentName,
        'SalaryYear' => $row['SalaryYear'],
        'SalaryMonth' => $row['SalaryMonth'],
        'GrossSalary' => $row['GrossSalary'],
        'BasicSalary' => $row['BasicSalary'],
        'HRA' => $row['HRA'],
        'Conveyance' => $row['Conveyance'],
        'Allowances' => $row['Allowances'],
        'OvertimePay' => $row['OvertimePay'],
        'ArrearAmount' => $row['ArrearAmount'],
        'EPF' => $EPF,
        'ESIC' => $ESIC,
        'INCOMTAX' => $INCOMTAX,
        'OtherDeduction' => $OtherDeduction,
        'NetSalary' => $row['NetSalary'],
        'WorkedDays' => $payableDays
    );

    // File Save Path
    $outputFolder = "../{$companyid}/{$subcompanyid}/{$projectid}/{$employeeid}/PaySlip";
    if (!is_dir($outputFolder)) {
        mkdir($outputFolder, 0777, true);
    }

    $fileName = "{$projectid}_{$employeeid}_{$row['SalaryYear']}_{$row['SalaryMonth']}.pdf";
    $outputFilePath = $outputFolder . "/" . $fileName;

    // Generate PDF
    createEmployeePaySlipWithHeaderFooter($outputFilePath, $companyname, $projectname, $EmployeeData);

    return array(
        'status' => 'Success',
        'message' => 'Payslip generated successfully.',
        'filepath' => str_replace("../", "", $outputFilePath)
    );
}

function getLastInsertedRow($conn, $tableName, $primaryKey = 'id')
{
    // Basic SQL Injection Safety Check (for table/column names)
    $allowedPattern = '/^[a-zA-Z0-9_]+$/';
    if (!preg_match($allowedPattern, $tableName) || !preg_match($allowedPattern, $primaryKey)) {
        return ['status' => 'error', 'message' => 'Invalid table or column name.'];
    }

    $sql = "SELECT * FROM `$tableName` ORDER BY `$primaryKey` DESC LIMIT 1";
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        return ['status' => 'error', 'message' => 'Query failed: ' . mysqli_error($conn)];
    }

    $row = mysqli_fetch_assoc($result);
    if ($row) {
        return ['status' => 'success', 'message' => 'Last inserted row fetched.', 'data' => $row];
    } else {
        return ['status' => 'error', 'message' => 'No data found in table.'];
    }
}



function countDayOccurrencesInMonthWithDates(string $dayName, string $startdate, string $enddate): int
{
    // Convert day name to lowercase for case-insensitive comparison
    $dayName = strtolower($dayName);

    // Array of valid day names
    $validDays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];

    if (!in_array($dayName, $validDays)) {
        return 0; // Invalid day name, return 0 occurrences
    }

    // Convert start and end dates to DateTime objects
    $start = DateTime::createFromFormat('Y-m-d', $startdate);
    $end = DateTime::createFromFormat('Y-m-d', $enddate);

    // Validate date conversion
    if (!$start || !$end) {
        return 0; // Invalid date format
    }

    $count = 0;
    // Loop through the date range
    while ($start <= $end) {
        if (strtolower($start->format('l')) === $dayName) {
            $count++;
        }
        $start->modify('+1 day'); // Move to the next day
    }
    return $count; // Return count of occurrences
}

function calculateGrossSalary($ctcSalary, $daysInMonth, $calculatedDays)
{
    if ($ctcSalary <= 0 || $daysInMonth <= 0 || $calculatedDays < 0) {
        return 0; // Return 0 for invalid values
    }
    $calculatedDays = min($calculatedDays, $daysInMonth);
    return round(($ctcSalary / $daysInMonth) * $calculatedDays, 2);
}

function calculateBasicSalary($basicSalary, $daysInMonth, $calculatedDays)
{
    if ($basicSalary <= 0 || $daysInMonth <= 0 || $calculatedDays < 0) {
        return 0; // Return 0 for invalid values
    }
    $calculatedDays = min($calculatedDays, $daysInMonth);
    return round(($basicSalary / $daysInMonth) * $calculatedDays, 2);
}

function calculateHRASalary($hraSalary, $daysInMonth, $calculatedDays)
{
    if ($hraSalary <= 0 || $daysInMonth <= 0 || $calculatedDays < 0) {
        return 0; // Return 0 for invalid values
    }
    $calculatedDays = min($calculatedDays, $daysInMonth);
    return round(($hraSalary / $daysInMonth) * $calculatedDays, 2);
}

function calculateConveyanceSalary($conveyanceSalary, $daysInMonth, $calculatedDays)
{
    if ($conveyanceSalary <= 0 || $daysInMonth <= 0 || $calculatedDays < 0) {
        return 0; // Return 0 for invalid values
    }
    $calculatedDays = min($calculatedDays, $daysInMonth);
    return round(($conveyanceSalary / $daysInMonth) * $calculatedDays, 2);
}

function calculateAllowanceSalary($allowanceSalary, $daysInMonth, $calculatedDays)
{
    if ($allowanceSalary <= 0 || $daysInMonth <= 0 || $calculatedDays < 0) {
        return 0; // Return 0 for invalid values
    }
    $calculatedDays = min($calculatedDays, $daysInMonth);
    return round(($allowanceSalary / $daysInMonth) * $calculatedDays, 2);
}


function calculatePFDetails_Salary($basicSalary)
{
    if ($basicSalary <= 0) {
        return [
            'employee_contribution' => 0,
            'employer_contribution' => 0,
            'eps_contribution' => 0,
            'total_pf_contribution' => 0,
            'edli_contribution' => 0,
            'epf_admin_charges' => 0,
            'edli_admin_charges' => 0,
            'total_employer_cost' => 0,
            'grand_total' => 0
        ];
    }

    // Employee Contribution: 12% of Basic Salary
    $employeePF = round($basicSalary * 0.12, 2);

    // EPS Contribution (Max 8.33% of â‚¹15,000)
    $epsBase = min($basicSalary, 15000);
    $epsContribution = round($epsBase * 0.0833, 2);

    // Employer Contribution to EPF = 12% - EPS Contribution
    $employerPF = round(($basicSalary * 0.12) - $epsContribution, 2);

    // EDLI Contribution (0.5% of max â‚¹15,000)
    $edliContribution = round($epsBase * 0.005, 2);

    // EPF Admin Charges (0.5% of max â‚¹15,000)
    $epfAdminCharges = round($epsBase * 0.005, 2);

    // EDLI Admin Charges (0.01% of max â‚¹15,000)
    $edliAdminCharges = round($epsBase * 0.0001, 2);

    // Total Employer Cost (Employer PF + EPS + EDLI + Admin Charges)
    $totalEmployerCost = $employerPF + $epsContribution + $edliContribution + $epfAdminCharges + $edliAdminCharges;

    // Grand Total (Employee Contribution + Total Employer Cost)
    $grandTotal = $employeePF + $totalEmployerCost;

    return [
        'employee_contribution' => $employeePF,
        'employer_contribution' => $employerPF,
        'eps_contribution' => $epsContribution,
        'total_pf_contribution' => $employeePF + $employerPF,
        'edli_contribution' => $edliContribution,
        'epf_admin_charges' => $epfAdminCharges,
        'edli_admin_charges' => $edliAdminCharges,
        'total_employer_cost' => $totalEmployerCost,
        'grand_total' => $grandTotal
    ];
}


function calculateESIC_Salary($grossSalary, $calBasic)
{
    // ESIC is applicable only if Gross Salary is <= 21,000
    if ($grossSalary <= 0 || $grossSalary > 21000) {
        return [
            'employee_contribution' => 0,
            'employer_contribution' => 0,
            'total_esic_contribution' => 0
        ];
    }

    // Employee Contribution: 0.75% of Basic Salary
    $employeeESIC = round($calBasic * 0.0075, 2);

    // Employer Contribution: 3.25% of Basic Salary
    $employerESIC = round($calBasic * 0.0325, 2);

    // Total ESIC Contribution
    $totalESIC = $employeeESIC + $employerESIC;

    return [
        'employee_contribution' => $employeeESIC,
        'employer_contribution' => $employerESIC,
        'total_esic_contribution' => $totalESIC
    ];
}

function calculateOtherDeduction_Salary($deductiontype, $DeductionValue, $deductionbasevalue)
{
    $employeeOtherDeduction = 0;
    if ($deductiontype == "" || strtolower($deductiontype) == "fixed") {
        $employeeOtherDeduction = $DeductionValue;
    } else if ($deductiontype == "Percentage" || $deductiontype == "percentage") {
        $employeeOtherDeduction = round(($deductionbasevalue * $DeductionValue) / 100, 2);
    }
    return $employeeOtherDeduction;
}



function GetcalculateGrossSalary($ctc, $includePF = true, $includeESIC = true)
{
    // Constants for Employer Contributions
    $pfPercentage = 0.12; // Employer PF Contribution (12%)
    $esicPercentage = 0.0325; // Employer ESIC Contribution (3.25%) - If applicable
    $esicLimit = 21000; // ESIC applicable only if Gross â‰¤ 21,000

    // Estimate Gross Salary (Initial Approximation)
    $grossSalary = $ctc;

    // Deduct Employer PF Contribution
    if ($includePF) {
        $employerPF = round(($grossSalary * $pfPercentage), 2);
        $grossSalary -= $employerPF;
    } else {
        $employerPF = 0;
    }

    // Deduct Employer ESIC Contribution (Only if Gross Salary is within ESIC Limit)
    if ($includeESIC && $grossSalary <= $esicLimit) {
        $employerESIC = round(($grossSalary * $esicPercentage), 2);
        $grossSalary -= $employerESIC;
    } else {
        $employerESIC = 0;
    }

    return [
        'gross_salary' => round($grossSalary, 2),
        'employer_pf' => $employerPF,
        'employer_esic' => $employerESIC
    ];
}

function GetAnualSalaryBreakupFromCTC($ctc, $basicPercent = 0.40, $hraPercent = 0.40, $conveyanceFixed = 1600, $includePF = true, $includeESIC = true)
{
    // Constants
    $pfPercentage = 0.12;
    $esicPercentage = 0.0325;
    $esicLimit = 21000;

    // Start with gross
    $gross = $ctc;

    // Deduct Employer PF
    $employerPF = $includePF ? round($gross * $pfPercentage, 2) : 0;
    $grossAfterPF = $gross - $employerPF;

    // Deduct Employer ESIC
    if ($includeESIC && $grossAfterPF <= $esicLimit) {
        $employerESIC = round($grossAfterPF * $esicPercentage, 2);
    } else {
        $employerESIC = 0;
    }
    $finalGross = $grossAfterPF - $employerESIC;

    // Calculate Basic
    $basic = round($finalGross * $basicPercent, 2);

    // HRA (typically 40% of Basic)
    $hra = round($basic * $hraPercent, 2);

    // Conveyance (annual)
    $conveyance = $conveyanceFixed * 12;

    // Special Allowance = Gross - (Basic + HRA + Conveyance)
    $special = round($finalGross - ($basic + $hra + $conveyance), 2);

    return [
        'ctc' => round($ctc, 2),
        'gross_salary' => round($finalGross, 2),
        'basic_salary' => $basic,
        'hra' => $hra,
        'conveyance' => $conveyance,
        'special_allowance' => $special,
        'employer_pf' => $employerPF,
        'employer_esic' => $employerESIC,
    ];
}

function GetMonthlySalaryBreakupFromCTC(
    $ctc,
    $basicPercent = 0.40,
    $hraPercent = 0.40,
    $conveyanceFixed = 1600, // â‚¹1600 per month
    $includePF = true,
    $includeESIC = true
) {
    // Constants
    $pfPercentage = 0.12;
    $esicPercentage = 0.0325;
    $esicLimit = 21000;

    // Monthly CTC
    $monthlyCTC = round($ctc / 12, 2);

    // Deduct Employer PF
    $employerPF = $includePF ? round($monthlyCTC * $pfPercentage, 2) : 0;
    $grossAfterPF = $monthlyCTC - $employerPF;

    // Deduct Employer ESIC
    if ($includeESIC && $grossAfterPF <= $esicLimit) {
        $employerESIC = round($grossAfterPF * $esicPercentage, 2);
    } else {
        $employerESIC = 0;
    }
    $finalGross = $grossAfterPF - $employerESIC;

    // Calculate Basic
    $basic = round($finalGross * $basicPercent, 2);

    // HRA (typically 40% of Basic)
    $hra = round($basic * $hraPercent, 2);

    // Conveyance is already monthly
    $conveyance = $conveyanceFixed;

    // Special Allowance
    $special = round($finalGross - ($basic + $hra + $conveyance), 2);

    return [
        'monthly_ctc' => $monthlyCTC,
        'monthly_gross' => $finalGross,
        'basic_salary' => $basic,
        'hra' => $hra,
        'conveyance' => $conveyance,
        'special_allowance' => $special,
        'employer_pf' => $employerPF,
        'employer_esic' => $employerESIC,
    ];
}


function GetMonthlySalaryBreakupFromGross($grossSalary, $basicPercent = 0.40, $hraPercent = 0.40, $conveyanceFixed = 1600, $includePF = true, $includeESIC = true)
{
    // Constants
    $pfPercentage = 0.12;
    $esicPercentage = 0.0325;
    $esicLimit = 21000;

    // Start with actual gross
    $finalGross = $grossSalary;

    // Calculate Basic
    $basic = round($finalGross * $basicPercent, 2);

    // Calculate HRA
    $hra = round($basic * $hraPercent, 2);

    // Conveyance (fixed monthly)
    $conveyance = $conveyanceFixed;

    // Calculate Special Allowance
    $special = round($finalGross - ($basic + $hra + $conveyance), 2);

    // Employer PF
    $employerPF = $includePF ? round($grossSalary * $pfPercentage, 2) : 0;

    // Employer ESIC
    if ($includeESIC && $grossSalary <= $esicLimit) {
        $employerESIC = round($basic * $esicPercentage, 2);
    } else {
        $employerESIC = 0;
    }

    // Total monthly CTC
    $monthlyCTC = round($grossSalary + $employerPF + $employerESIC, 2);

    return [
        'monthly_ctc' => $monthlyCTC,
        'monthly_gross' => $grossSalary,
        'basic_salary' => $basic,
        'hra' => $hra,
        'conveyance' => $conveyance,
        'special_allowance' => $special,
        'employer_pf' => $employerPF,
        'employer_esic' => $employerESIC
    ];
}


function calculateIncomeTax($annualSalary, $isOldRegime = true)
{
    $taxableIncome = $annualSalary;
    $tax = 0;

    if ($isOldRegime) {
        // Old Tax Regime (With Deductions)
        if ($taxableIncome <= 250000) {
            $tax = 0;
        } elseif ($taxableIncome <= 500000) {
            $tax = ($taxableIncome - 250000) * 0.05;
        } elseif ($taxableIncome <= 1000000) {
            $tax = (250000 * 0.05) + (($taxableIncome - 500000) * 0.20);
        } else {
            $tax = (250000 * 0.05) + (500000 * 0.20) + (($taxableIncome - 1000000) * 0.30);
        }
    } else {
        // New Tax Regime (Without Deductions)
        if ($taxableIncome <= 250000) {
            $tax = 0;
        } elseif ($taxableIncome <= 500000) {
            $tax = ($taxableIncome - 250000) * 0.05;
        } elseif ($taxableIncome <= 750000) {
            $tax = (250000 * 0.05) + (($taxableIncome - 500000) * 0.10);
        } elseif ($taxableIncome <= 1000000) {
            $tax = (250000 * 0.05) + (250000 * 0.10) + (($taxableIncome - 750000) * 0.15);
        } elseif ($taxableIncome <= 1250000) {
            $tax = (250000 * 0.05) + (250000 * 0.10) + (250000 * 0.15) + (($taxableIncome - 1000000) * 0.20);
        } elseif ($taxableIncome <= 1500000) {
            $tax = (250000 * 0.05) + (250000 * 0.10) + (250000 * 0.15) + (250000 * 0.20) + (($taxableIncome - 1250000) * 0.25);
        } else {
            $tax = (250000 * 0.05) + (250000 * 0.10) + (250000 * 0.15) + (250000 * 0.20) + (250000 * 0.25) + (($taxableIncome - 1500000) * 0.30);
        }
    }

    // Apply 4% Health & Education Cess
    $tax += $tax * 0.04;

    // Monthly TDS Deduction
    $monthlyTDS = round($tax / 12, 2);

    return [
        'annual_tax' => round($tax, 2),
        'monthly_tds' => $monthlyTDS
    ];
}

function calculateWorkedDays($gross_salary, $payable_salary, $total_working_days = 30)
{
    if ($gross_salary <= 0)
        return 0;
    $present_days = ($payable_salary / $gross_salary) * $total_working_days;
    return round($present_days, 2);
}


function getNCPDays($conn, $CompanyID, $SubCompanyID, $ProjectID, $EmployeeID, $Year, $Month)
{
    $sql = "SELECT TotalDaysInMonth, TotalPayableDays, CalculatedPresentDays
            FROM hrm_EmployeeCalculateAttendance
            WHERE CompanyID = ? 
              AND SubCompanyID = ? 
              AND ProjectID = ? 
              AND EmployeeID = ? 
              AND AttendanceYear = ? 
              AND AttendanceMonth = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return 0;
    }

    $stmt->bind_param("sssiii", $CompanyID, $SubCompanyID, $ProjectID, $EmployeeID, $Year, $Month);
    $stmt->execute();
    $stmt->bind_result($totalDays, $payableDays, $presentDays);

    if ($stmt->fetch()) {
        $effectivePayable = (!empty($payableDays) || $payableDays === 0 || $payableDays === "0") ? floatval($payableDays) : floatval($presentDays);
        return max(0, floatval($totalDays) - $effectivePayable);
    } else {
        return 0;
    }
}

function getSalaryMonthYearList($conn, $CompanyID, $SubCompanyID, $ProjectID, $filter = null)
{
    $data = [];
    $sql = "SELECT DISTINCT SalaryYear, SalaryMonth
            FROM hrm_CalculatedSalary
            WHERE CompanyID = ?
              AND SubCompanyID = ?
              AND ProjectID = ?";
    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    // Apply filter for pending approvals
    if ($filter === "pendingforapproval") {
        $sql .= " AND (requestno = '' OR requestno IS NULL)";
    }

    $sql .= " ORDER BY SalaryYear DESC, SalaryMonth DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Optional: add MonthName for display
            $row['MonthName'] = date("M", mktime(0, 0, 0, $row['SalaryMonth'], 1));
            $data[] = $row;
        }

        $stmt->close();
    } else {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }
    return $data;
}

function getAttendanceLocationMaster($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all")
{
    $data = [];

    try {
        // Base query
        $sql = "SELECT * FROM hrm_AttendanceLocationMaster
                WHERE (tedit is null or tedit='') AND CompanyID = ? AND SubCompanyID = ? AND ProjectID = ?";

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

function getEmployeeExemptions($hrmconn, $EmployeeID, $ProjectID = "all", $ShortBy = "all", $ShortByValue = "all")
{
    // Base SQL
    $sql = "SELECT * FROM hrm_EmployeeAttLocationExemption WHERE EmployeeID=?";
    $params = [$EmployeeID];
    $types = "s";

    // Filter by Project
    if ($ProjectID !== "all") {
        $sql .= " AND ProjectID=?";
        $params[] = $ProjectID;
        $types .= "s";
    }

    // Additional Filters
    if ($ShortBy !== "all" && $ShortByValue !== "all") {
        if ($ShortBy == "LocationId") {
            $sql .= " AND LocationId=?";
            $params[] = $ShortByValue;
            $types .= "s";
        }

        if ($ShortBy == "AttendanceDate") {
            $sql .= " AND AttendanceDate=?";
            $params[] = $ShortByValue;
            $types .= "s";
        }
    }

    // Order by date (DESC)
    $sql .= " ORDER BY AttendanceDate DESC";

    // Prepare Statement
    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        return ["status" => "error", "message" => $hrmconn->error];
    }

    // Bind params dynamically
    $stmt->bind_param($types, ...$params);
    $stmt->execute();

    $res = $stmt->get_result();
    $data = [];

    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    return $data;
}

function getEmployeeExemptedLocationsDetailsData($hrmconn, $ProjectID, $ExemptYear, $sortby = "all", $sortbyvalue = "all")
{
    try {
        // Mandatory validations
        if (!$ProjectID) {
            return ["status" => "error", "message" => "ProjectID is mandatory"];
        }
        if (!$ExemptYear) {
            return ["status" => "error", "message" => "Exempt year is mandatory"];
        }

        // Allowed fields to avoid SQL injection
        $allowedFilters = [
            "EmployeeID",
            "LocationId",
            "AttendanceDate",
            "sno",
            "created_by"
        ];

        // Base SQL
        $sql = "
            SELECT 
                eale.sno,
                eale.ProjectID,
                eale.LocationId,
                eale.EmployeeID,
                eale.AttendanceDate,
                eale.Comments,
                eale.created_at,
                eale.created_by,
                alm.LocationName AS ExemptedLocationName,
                alm.LocationAddress AS ExemptedLocationAddress,
                
                -- Employee Full Name
                CONCAT(
                    COALESCE(em.TitleName, ''), ' ',
                    COALESCE(em.FirstName, ''), ' ',
                    COALESCE(em.MiddleName, ''), ' ',
                    COALESCE(em.LastName, '')
                ) AS EmployeeFullName

            FROM hrm_EmployeeAttLocationExemption eale
            INNER JOIN hrm_AttendanceLocationMaster alm
                ON alm.LocationId = eale.LocationId

            INNER JOIN hrm_EmployeesMaster em
                ON em.EmployeeID = eale.EmployeeID
                AND em.projectid = eale.ProjectID

            WHERE eale.ProjectID = ? 
              AND YEAR(eale.AttendanceDate) = ?
        ";

        $params = [$ProjectID, $ExemptYear];
        $types = "si";

        // Add filter only if provided
        if ($sortby !== "all" && $sortbyvalue !== "all") {

            if (!in_array($sortby, $allowedFilters)) {
                return ["status" => "error", "message" => "Invalid filter field"];
            }

            $sql .= " AND eale." . $sortby . " = ? ";
            $params[] = $sortbyvalue;
            $types .= "s";
        }

        $sql .= " ORDER BY eale.AttendanceDate DESC ";

        // Prepare & execute
        $stmt = $hrmconn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $data = [];
        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }

        return $data;

    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function getEmployeeSummaryWithDetails($conn, $companyid, $subcompanyid, $projectid)
{
    $data = [
        "Active" => ["count" => 0, "list" => []],
        "Resigned" => ["count" => 0, "list" => []],
        "New Joiner" => ["count" => 0, "list" => []],
        "Total" => ["count" => 0, "list" => []]
    ];

    $sql = "
        SELECT 
            e.EmployeeID,
            CONCAT_WS(' ',
                IFNULL(e.TitleName,''),
                IFNULL(e.FirstName,''),
                IFNULL(e.MiddleName,''),
                IFNULL(e.LastName,'')
            ) AS FullName,

            e.Gender,
            e.DateOfBirth,
            e.Status,
            e.DateOfJoining,
            e.DateOfResignation,
            e.remark,

            e.DepartmentID,
            e.DesignationID,
            e.WorkLocation AS LocationId,
            e.Email,
            e.MobileNo,
            e.FatherName,
            e.attendanceType,
            e.TitleName,
            e.FirstName,
            e.MiddleName,
            e.LastName,

            e.InTime,
            e.OutTime,
            e.WorkHourseId,

            /* Bank (latest row) */
            (SELECT b.BankName FROM hrm_EmployeeBankDetails b WHERE b.EmployeeID = e.EmployeeID AND b.projectid = e.projectid ORDER BY b.BankDetailID DESC LIMIT 1) AS BankName,
            (SELECT b.AccountNumber FROM hrm_EmployeeBankDetails b WHERE b.EmployeeID = e.EmployeeID AND b.projectid = e.projectid ORDER BY b.BankDetailID DESC LIMIT 1) AS AccountNumber,
            (SELECT b.IFSCCode FROM hrm_EmployeeBankDetails b WHERE b.EmployeeID = e.EmployeeID AND b.projectid = e.projectid ORDER BY b.BankDetailID DESC LIMIT 1) AS IFSCCode,

            /* EPF UAN (DeductionId 1) / ESIC IP (DeductionId 3): active row first, else latest row with a value */
            (SELECT d.regid FROM hrm_EmployeeDeductionsComponents d WHERE d.EmployeeId = e.EmployeeID AND d.projectid = e.projectid AND d.DeductionId = 1 AND d.regid IS NOT NULL AND d.regid <> ''
               ORDER BY (d.tedit IS NULL OR d.tedit = '') DESC, d.id DESC LIMIT 1) AS EpfRegId,
            (SELECT d.regid FROM hrm_EmployeeDeductionsComponents d WHERE d.EmployeeId = e.EmployeeID AND d.projectid = e.projectid AND d.DeductionId = 3 AND d.regid IS NOT NULL AND d.regid <> ''
               ORDER BY (d.tedit IS NULL OR d.tedit = '') DESC, d.id DESC LIMIT 1) AS EsicRegId,

            loc.LocationName,
            dept.DepartmentName,
            desig.DesignationName,
            desig.Level,

            sal.CTC,
            sal.GrossSalary,
            sal.BasicSalary,
            sal.HRA,
            sal.ConveyanceAllowance,
            sal.SpecialAllowance,
            sal.Deductions,
            IFNULL(NULLIF(sal.NetSalary, 0), IFNULL(NULLIF(sal.GrossSalary, 0), (IFNULL(sal.BasicSalary, 0) + IFNULL(sal.HRA, 0) + IFNULL(sal.ConveyanceAllowance, 0) + IFNULL(sal.SpecialAllowance, 0) - IFNULL(sal.Deductions, 0)))) AS NetSalary,

            MAX(doc.DocumentPath) AS ProfilePhoto

        FROM hrm_EmployeesMaster e
        LEFT JOIN hrm_AttendanceLocationMaster loc 
            ON loc.LocationId = e.WorkLocation
            AND loc.ProjectID = e.projectid
            AND (loc.tedit IS NULL OR loc.tedit = '')

        LEFT JOIN hrm_DepartmentMaster dept 
            ON dept.DepartmentID = e.DepartmentID
            AND (dept.tedit IS NULL OR dept.tedit = '')

        LEFT JOIN hrm_DesignationsMaster desig 
            ON desig.DesignationID = e.DesignationID
            AND (desig.tedit IS NULL OR desig.tedit = '')

        LEFT JOIN hrm_EmployeeSalary sal 
            ON sal.SalaryID = (
                SELECT MAX(s2.SalaryID) 
                FROM hrm_EmployeeSalary s2 
                WHERE s2.EmployeeID = e.EmployeeID
            )

        LEFT JOIN hrm_EmployeeDocuments doc
            ON doc.EmployeeID = e.EmployeeID
            AND doc.projectid = e.projectid
            AND doc.DocumentType = 'ProfilePhoto'

        WHERE 
            e.companyid = ?
            AND e.subcompanyid = ?
            AND e.projectid = ?

        GROUP BY e.EmployeeID
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $companyid, $subcompanyid, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();

    $currentMonth = date('m');
    $currentYear = date('Y');

    while ($row = $result->fetch_assoc()) {

        $status = strtolower(trim($row['Status'] ?? ''));

        // RESIGNED
        if ($status === 'resigned' || !empty($row['DateOfResignation'])) {
            $data["Resigned"]["count"]++;
            $data["Resigned"]["list"][] = $row;
            continue;
        }

        // NEW JOINER (current month only)
        if (!empty($row['DateOfJoining'])) {
            $joinMonth = date('m', strtotime($row['DateOfJoining']));
            $joinYear = date('Y', strtotime($row['DateOfJoining']));

            if ($joinMonth == $currentMonth && $joinYear == $currentYear) {
                $data["New Joiner"]["count"]++;
                $data["New Joiner"]["list"][] = $row;
                continue; // IMPORTANT: do not count as Active
            }
        }

        // ACTIVE (excluding new joiners)
        if ($status === 'active') {
            $data["Active"]["count"]++;
            $data["Active"]["list"][] = $row;
        }
    }

    // âœ… FINAL TOTAL (Correct & Guaranteed)
    $data["Total"]["count"] =
        $data["Active"]["count"]
        + $data["Resigned"]["count"]
        + $data["New Joiner"]["count"];

    $data["Total"]["list"] = array_merge(
        $data["Active"]["list"],
        $data["Resigned"]["list"],
        $data["New Joiner"]["list"]
    );

    $stmt->close();
    return $data;
}


function getAttendanceFiles($hrmconn, $projectid, $shortby = "all", $shortbyvalue = null)
{
    $data = [];

    try {
        // âœ… Validate projectid
        if (empty($projectid)) {
            throw new Exception("Project ID is required.");
        }

        // âœ… Allowed filter columns
        $allowedColumns = ["FileID", "locationId", "status", "dateFrom", "dateTo"];

        // âœ… Base SQL
        $sql = "SELECT 
                    FileID, companyid, subcompanyid, projectid, locationId, 
                    dateFrom, dateTo, original_filename, stored_filename, 
                    file_path, uploaded_by, uploaded_at, status
                FROM hrm_AttendanceFiles
                WHERE projectid = ?";

        $types = "s"; // for projectid
        $params = [$projectid];

        // âœ… Add filtering if valid column & value are provided
        if ($shortby !== "all" && in_array($shortby, $allowedColumns) && !empty($shortbyvalue)) {
            $sql .= " AND $shortby = ?";
            $types .= "s";
            $params[] = $shortbyvalue;
        }

        $sql .= " ORDER BY uploaded_at DESC";

        // âœ… Prepare statement
        $stmt = $hrmconn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $hrmconn->error);
        }

        // âœ… Bind parameters dynamically
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } catch (Exception $e) {
        error_log("Error in getAttendanceFiles: " . $e->getMessage());
    }

    return $data;
}

function ensureLateDeductionColumnsExist($conn) {
    static $done = false;
    if ($done) return;
    try {
        $colCheck = $conn->query("SHOW COLUMNS FROM hrm_EmployeeCalculateAttendance LIKE 'latedeductiondays'");
        if ($colCheck && $colCheck->num_rows == 0) {
            $conn->query("ALTER TABLE hrm_EmployeeCalculateAttendance ADD COLUMN latedeductiondays DECIMAL(5,2) DEFAULT 0");
        }
        $colCheck2 = $conn->query("SHOW COLUMNS FROM hrm_EmployeeCalculateAttendance LIKE 'latedeductionremark'");
        if ($colCheck2 && $colCheck2->num_rows == 0) {
            $conn->query("ALTER TABLE hrm_EmployeeCalculateAttendance ADD COLUMN latedeductionremark TEXT NULL");
        }
    } catch (Exception $ex) {}
    $done = true;
}

//*************
function saveCalculatedAttendance(
    $conn,
    $companyid,
    $subcompanyid,
    $projectid,
    $loginid,
    $loginname,
    $year,
    $month,
    $attendanceData
) {
    header("Content-Type: application/json");

    // Validate year & month
    if (!is_numeric($year) || !is_numeric($month)) {
        echo json_encode(["status" => "error", "message" => "Invalid year or month"]);
        return;
    }

    // Ensure table has late deduction columns
    ensureLateDeductionColumnsExist($conn);

    // Decode attendance JSON
    $data = json_decode($attendanceData, true);
    if (!is_array($data) || empty($data)) {
        echo json_encode(["status" => "error", "message" => "Invalid attendance JSON"]);
        return;
    }

    // Start transaction
    $conn->begin_transaction();

    $response = [];
    try {
        foreach ($data as $row) {

            if (!isset($row["EmployeeID"]) || empty($row["EmployeeID"])) {
                continue;
            }

            $EmployeeID = $row["EmployeeID"];
            $CompanyID = $companyid;
            $SubCompanyID = $subcompanyid;
            $ProjectID = $projectid;

            $Comments = "Salary Calculated on " . date("Y-m-d") . " by " . $loginname;
            $LateArrivals = floatval($row["LateArrivals"] ?? 0);
            $LateDeductionDays = floatval($row["LateDeductionDays"] ?? 0);
            $LateDeductionRemark = $row["LateDeductionRemark"] ?? "";

            // -------------------------
            // PARAMETER LIST (Matches Table)
            // -------------------------
            $params = [
                $row["TotalDaysInMonth"],
                $row["WorkingDaysInMonth"],
                $row["WeekOffs"],
                $row["HoliDays"],
                $row["PresentDays"],
                $row["AbsentDays"],
                $row["Leaves"],
                $row["HalfDays"],
                $LateArrivals,
                $row["ExtraHoursWorked"],
                $row["SandwichConverted"],
                $row["CalculatedPresentDays"],
                $row["LOP"],
                $row["TotalPayableDays"],
                $row["ExtraWorkingDays"] ?? 0,
                $LateDeductionDays,
                $LateDeductionRemark,
                $Comments,
                $loginid
            ];

            // -------------------------
            // BIND STRING FOR 19 VALUES
            // -------------------------
            $valuesBind = "ddddddddsdddddddsss";

            // -------------------------
            // CHECK IF ENTRY EXISTS
            // -------------------------
            $check = $conn->prepare("
                SELECT AttendanceID 
                FROM hrm_EmployeeCalculateAttendance 
                WHERE EmployeeID=? 
                  AND CompanyID=? 
                  AND SubCompanyID=? 
                  AND ProjectID=? 
                  AND AttendanceYear=? 
                  AND AttendanceMonth=?
            ");

            if (!$check) {
                throw new Exception("Prepare Error (CHECK): " . $conn->error);
            }

            $check->bind_param(
                "isssii",
                $EmployeeID,
                $CompanyID,
                $SubCompanyID,
                $ProjectID,
                $year,
                $month
            );

            $check->execute();
            $result = $check->get_result();

            // ==================================================================
            // UPDATE EXISTING ENTRY
            // ==================================================================
            if ($result && $result->num_rows > 0) {

                $AttendanceID = $result->fetch_assoc()["AttendanceID"];

                $sql = "
                    UPDATE hrm_EmployeeCalculateAttendance SET
                        TotalDaysInMonth=?, WorkingDaysInMonth=?, WeekOffDays=?, 
                        HoliDays=?, PresentDays=?, AbsentDays=?, TotalApproveLeave=?, 
                        HalfDays=?, LateArrivals=?, ExtraHoursWorked=?, SandwichConverted=?, 
                        CalculatedPresentDays=?, LOP=?, TotalPayableDays=?, ExtraWorkingDays=?, 
                        latedeductiondays=?, latedeductionremark=?, Comments=?, 
                        created_by=?
                    WHERE AttendanceID=?
                ";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare Error (UPDATE): " . $conn->error);
                }

                $updateParams = array_merge($params, [$AttendanceID]);
                $stmt->bind_param($valuesBind . "i", ...$updateParams);

                $stmt->execute();

                $response[] = ["EmployeeID" => $EmployeeID, "status" => "updated", 'message' => 'Salary details updated for the month of ' . $month . '-' . $year];
            }

            // ==================================================================
            // INSERT NEW ENTRY
            // ==================================================================
            else {

                $sql = "
                    INSERT INTO hrm_EmployeeCalculateAttendance
                    (
                        EmployeeID, CompanyID, SubCompanyID, ProjectID, AttendanceYear, AttendanceMonth,
                        TotalDaysInMonth, WorkingDaysInMonth, WeekOffDays, HoliDays, PresentDays,
                        AbsentDays, TotalApproveLeave, HalfDays, LateArrivals, ExtraHoursWorked,
                        SandwichConverted, CalculatedPresentDays, LOP, TotalPayableDays, ExtraWorkingDays,
                        latedeductiondays, latedeductionremark, Comments, created_by
                    )
                    VALUES (?,?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?,?, ?,?,?,?)
                ";

                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare Error (INSERT): " . $conn->error);
                }

                $insertBind = "isssii" . $valuesBind;

                $insertParams = array_merge(
                    [$EmployeeID, $CompanyID, $SubCompanyID, $ProjectID, $year, $month],
                    $params
                );

                $stmt->bind_param($insertBind, ...$insertParams);
                $stmt->execute();

                $response[] = ["EmployeeID" => $EmployeeID, "status" => "inserted", 'message' => 'Salary inserted for the month of ' . $month . '-' . $year];
            }
        }
        // Commit transaction
        $conn->commit();
        return json_encode(["status" => "success", "result" => $response]);
    } catch (Exception $e) {
        $conn->rollback();
        return json_encode([
            "status" => "error",
            "message" => $e->getMessage(),
            "line" => $e->getLine()
        ]);
    }
}


function calculateMonthlyAttendance_forSalary($conn, $companyid, $subcompanyid, $projectid, $year, $month)
{
    ensureLateDeductionColumnsExist($conn);

    $firstDay = $year . "-" . str_pad($month, 2, "0", STR_PAD_LEFT) . "-01";
    $lastDay = date("Y-m-t", strtotime($firstDay));

    $TotalDaysInMonth = intval(date("t", strtotime($firstDay)));

    // Fetch all existing saved calculated attendance for this month
    $attYearInt = intval($year);
    $attMonthInt = intval($month);
    $attMonthPadded = str_pad($month, 2, "0", STR_PAD_LEFT);
    $savedAttMap = [];
    $savedAttSql = "SELECT * FROM hrm_EmployeeCalculateAttendance 
                    WHERE CompanyID=? AND SubCompanyID=? AND ProjectID=? 
                      AND (AttendanceYear=? OR AttendanceYear=?) 
                      AND (AttendanceMonth=? OR AttendanceMonth=?)";
    $stmtSaved = $conn->prepare($savedAttSql);
    if ($stmtSaved) {
        $stmtSaved->bind_param("iisssss", $companyid, $subcompanyid, $projectid, $year, $attYearInt, $attMonthPadded, $attMonthInt);
        $stmtSaved->execute();
        $savedRes = $stmtSaved->get_result();
        while ($sRow = $savedRes->fetch_assoc()) {
            $savedAttMap[$sRow['EmployeeID']] = $sRow;
        }
        $stmtSaved->close();
    }

    // Fetch all existing saved calculated salary for this month as secondary check
    $savedSalMap = [];
    $savedSalSql = "SELECT EmployeeID FROM hrm_EmployeeCalculatedSalary 
                    WHERE CompanyID=? AND SubCompanyID=? AND ProjectID=? 
                      AND (SalaryYear=? OR SalaryYear=?) 
                      AND (SalaryMonth=? OR SalaryMonth=?)";
    $stmtSal = $conn->prepare($savedSalSql);
    if ($stmtSal) {
        $stmtSal->bind_param("iisssss", $companyid, $subcompanyid, $projectid, $year, $attYearInt, $attMonthPadded, $attMonthInt);
        $stmtSal->execute();
        $salRes = $stmtSal->get_result();
        while ($slRow = $salRes->fetch_assoc()) {
            $savedSalMap[$slRow['EmployeeID']] = true;
        }
        $stmtSal->close();
    }

    // Fetch employee salary master for Gross Salary display
    $empSalMap = [];
    $salaryYearMonth = sprintf("%04d-%02d", intval($year), intval($month));
    $allSalaries = fetchEmployeeSalaryData($conn, $companyid, $subcompanyid, $projectid, null, null, $salaryYearMonth);
    if (!empty($allSalaries)) {
        foreach ($allSalaries as $salRow) {
            if (!isset($empSalMap[$salRow['EmployeeID']])) {
                $empSalMap[$salRow['EmployeeID']] = floatval($salRow['GrossSalary'] ?? 0);
            }
        }
    }
    $allSalariesFallback = fetchEmployeeSalaryData($conn, $companyid, $subcompanyid, $projectid, null, null, null);
    if (!empty($allSalariesFallback)) {
        foreach ($allSalariesFallback as $salRow) {
            if (!isset($empSalMap[$salRow['EmployeeID']])) {
                $empSalMap[$salRow['EmployeeID']] = floatval($salRow['GrossSalary'] ?? 0);
            }
        }
    }

    $emp_sql = "SELECT EmployeeID, FirstName, LastName, InTime FROM hrm_EmployeesMaster
        WHERE companyid=? AND subcompanyid=? AND projectid=? AND lcase(Status)='active'";

    $stmt = $conn->prepare($emp_sql);
    $stmt->bind_param("iii", $companyid, $subcompanyid, $projectid);
    $stmt->execute();
    $employees = $stmt->get_result();
    $final = [];
    while ($emp = $employees->fetch_assoc()) {
        $EmployeeID = $emp['EmployeeID'];
        $EmployeeName = trim($emp['FirstName'] . " " . $emp['LastName']);
        $GrossSalary = isset($empSalMap[$EmployeeID]) ? $empSalMap[$EmployeeID] : 0;
        $scheduledInTime = !empty($emp['InTime']) ? date('H:i:s', strtotime($emp['InTime'])) : '09:00:00';

        $isCalculated = (isset($savedAttMap[$EmployeeID]) || isset($savedSalMap[$EmployeeID])) ? 1 : 0;

        $shiftData = getShiftHours($conn, $EmployeeID);
        $FULL_DAY_HOURS = convertTimeToDecimal($shiftData['full']);
        $HALF_DAY_HOURS = convertTimeToDecimal($shiftData['half']);

        $PresentDays = 0;
        $HalfDays = 0;
        $Leaves = 0;
        $Holidays = 0;
        $WeekOffs = 0;
        $AbsentDays = 0;
        $SandwichDays = 0;
        $extraHours = 0;

        $attendance = [];
        $dayStatus = [];

        // ---------------------------------------------------
        // LEAVES (Only Approved & Paid Leaves are counted)
        // ---------------------------------------------------
        $leaveDates = [];
        $l_sql = "SELECT el.LeaveStartDate, el.LeaveEndDate
                  FROM hrm_EmployeeLeaves el
                  LEFT JOIN hrm_PayrollLeaveTypeMaster lt 
                         ON (el.LeaveType = lt.leave_code OR el.LeaveType = lt.leave_name) 
                        AND lt.companyid = el.CompanyID
                  WHERE el.EmployeeID=? 
                    AND el.Status='Approved' 
                    AND (lt.status IS NULL OR lt.status != 'inactive') 
                    AND (lt.tedit IS NULL OR lt.tedit = '')
                    AND (LOWER(lt.leave_type) != 'unpaid' OR lt.leave_type IS NULL OR lt.leave_type = '')";
        $stmt3 = $conn->prepare($l_sql);
        $stmt3->bind_param("i", $EmployeeID);
        $stmt3->execute();
        $l_res = $stmt3->get_result();
        while ($lv = $l_res->fetch_assoc()) {
            $start = strtotime($lv['LeaveStartDate']);
            $end = strtotime($lv['LeaveEndDate']);
            while ($start <= $end) {
                $leaveDates[] = date("Y-m-d", $start);
                $start = strtotime("+1 day", $start);
            }
        }

        // ---------------------------------------------------
        // ATTENDANCE & LATE CHECK
        // ---------------------------------------------------
        $p_sql = "SELECT AttendanceDate, WorkingHours, CheckInTime, CheckOutTime FROM hrm_EmployeeAttendance WHERE (tedit is null or tedit='') AND EmployeeID=? AND ProjectID=? AND (Status in ('Present') OR ApprovalStatus = 'Approved') AND (ApprovalStatus IS NULL OR ApprovalStatus != 'Rejected') AND AttendanceDate BETWEEN ? AND ?";

        $stmt2 = $conn->prepare($p_sql);
        $stmt2->bind_param("iiss", $EmployeeID, $projectid, $firstDay, $lastDay);
        $stmt2->execute();
        $p_res = $stmt2->get_result();

        $hasMissingMarkout = [];
        $lateDates = [];
        while ($p = $p_res->fetch_assoc()) {
            $date = $p['AttendanceDate'];
            if (empty($p['CheckOutTime'])) {
                $hasMissingMarkout[$date] = true;
            }
            $attendance[$date] = ($attendance[$date] ?? 0) + convertTimeToDecimal($p['WorkingHours']);

            if (!empty($p['CheckInTime'])) {
                $actualCheckIn = date('H:i:s', strtotime($p['CheckInTime']));
                if ($actualCheckIn > $scheduledInTime) {
                    $lateDates[$date] = date('d-M-Y', strtotime($date));
                }
            }
        }

        // ---------------------------------------------------
        // HOLIDAYS
        // ---------------------------------------------------
        $holidays = [];
        $h_sql = "SELECT HolidayDate FROM hrm_PayrollHolidaysMaster
                  WHERE (tedit is null or tedit ='') AND companyid=? AND subcompanyid=? AND projectid=?";
        $stmt4 = $conn->prepare($h_sql);
        $stmt4->bind_param("iii", $companyid, $subcompanyid, $projectid);
        $stmt4->execute();
        $h_res = $stmt4->get_result();
        while ($h = $h_res->fetch_assoc()) {
            $holidays[] = $h['HolidayDate'];
        }

        // ---------------------------------------------------
        // WEEKOFF
        // ---------------------------------------------------
        $weekOffDays = [];
        $w_sql = "SELECT WeekOffDayNo FROM hrm_EmployeeWeekOff
                  WHERE EmployeeID=? AND ProjectID=? AND lcase(Status)='active'";
        $stmt5 = $conn->prepare($w_sql);
        $stmt5->bind_param("ii", $EmployeeID, $projectid);
        $stmt5->execute();
        $w_res = $stmt5->get_result();
        while ($w = $w_res->fetch_assoc()) {
            $weekOffDays[] = $w['WeekOffDayNo'];
        }

        // ---------------------------------------------------
        // MAIN LOOP
        // ---------------------------------------------------
        for ($d = strtotime($firstDay); $d <= strtotime($lastDay); $d = strtotime("+1 day", $d)) {

            $date = date("Y-m-d", $d);
            $dow = date("N", $d);

            $isLeave = in_array($date, $leaveDates);
            $isHoliday = in_array($date, $holidays);
            $isWeekoff = in_array($dow, $weekOffDays);
            $workedHours = $attendance[$date] ?? 0;

            if ($isLeave) {
                $Leaves++;
                $dayStatus[$date] = "leave";
                continue;
            }

            // 1. if markout is missing than consider as absent
            if (isset($hasMissingMarkout[$date])) {
                $AbsentDays++;
                $dayStatus[$date] = "absent";
                continue;
            }

            if ($workedHours > 0) {
                if ($workedHours >= $FULL_DAY_HOURS) {
                    $PresentDays++;
                    $extraHours += max(0, $workedHours - $FULL_DAY_HOURS);
                    $dayStatus[$date] = "present";
                } elseif ($workedHours >= $HALF_DAY_HOURS) {
                    $HalfDays++;
                    $dayStatus[$date] = "half";
                } else {
                    $AbsentDays++;
                    $dayStatus[$date] = "absent";
                }

                if ($isHoliday)
                    $Holidays++;
                if ($isWeekoff)
                    $WeekOffs++;

                continue;
            }

            if ($isHoliday) {
                $Holidays++;
                $dayStatus[$date] = "holiday";
                continue;
            }

            if ($isWeekoff) {
                $WeekOffs++;
                $dayStatus[$date] = "weekoff";
                continue;
            }

            $AbsentDays++;
            $dayStatus[$date] = "absent";
        }

        // ---------------------------------------------------
        // SANDWICH RULE
        // ---------------------------------------------------
        foreach ($dayStatus as $date => $status) {

            if ($status == "holiday" || $status == "weekoff") {

                $prev = date("Y-m-d", strtotime("-1 day", strtotime($date)));
                $next = date("Y-m-d", strtotime("+1 day", strtotime($date)));

                if (
                    isset($dayStatus[$prev]) &&
                    isset($dayStatus[$next]) &&
                    in_array($dayStatus[$prev], ["absent"]) &&
                    in_array($dayStatus[$next], ["absent"])
                ) {

                    $SandwichDays++;

                    if ($status == "holiday")
                        $Holidays--;
                    if ($status == "weekoff")
                        $WeekOffs--;

                    $AbsentDays++;
                }
            }
        }

        // ---------------------------------------------------
        // LATE DEDUCTION CALCULATION (3 Late Days = 1 Day Deduction)
        // ---------------------------------------------------
        $lateCount = count($lateDates);
        $lateDeductionDays = floor($lateCount / 3);
        $lateDeductionRemark = "";
        if ($lateCount > 0) {
            $lateDeductionRemark = "Late on (" . $lateCount . " days): " . implode(", ", array_values($lateDates)) . " (Deducted: " . $lateDeductionDays . " day" . ($lateDeductionDays != 1 ? "s" : "") . ")";
        }

        // ---------------------------------------------------
        // FINAL CALCULATION
        // ---------------------------------------------------
        $CalculatedPresentDays = $PresentDays + ($HalfDays * 0.5);

        $TotalPayableDays =
            $CalculatedPresentDays +
            $Leaves +
            $Holidays +
            $WeekOffs -
            $lateDeductionDays;

        $TotalPayableDays = max(0, min($TotalDaysInMonth, $TotalPayableDays));

        $LOP = max(0, $TotalDaysInMonth - $TotalPayableDays);

        $workingdaysinmonth = max(0, $TotalDaysInMonth - ($Holidays + $WeekOffs));
        $ExtraWorkingDays = 0;
        if ($CalculatedPresentDays > $workingdaysinmonth) {
            $ExtraWorkingDays = $CalculatedPresentDays - $workingdaysinmonth;
        }

        // Check if saved calculated record exists in database
        if (isset($savedAttMap[$EmployeeID])) {
            $savedRow = $savedAttMap[$EmployeeID];
            if (isset($savedRow['TotalPayableDays']) && $savedRow['TotalPayableDays'] !== '') {
                $TotalPayableDays = floatval($savedRow['TotalPayableDays']);
            }
            if (isset($savedRow['CalculatedPresentDays']) && $savedRow['CalculatedPresentDays'] !== '') {
                $CalculatedPresentDays = floatval($savedRow['CalculatedPresentDays']);
            }
            if (isset($savedRow['PresentDays']) && $savedRow['PresentDays'] !== '') {
                $PresentDays = floatval($savedRow['PresentDays']);
            }
            if (isset($savedRow['TotalApproveLeave']) && $savedRow['TotalApproveLeave'] !== '') {
                $Leaves = floatval($savedRow['TotalApproveLeave']);
            }
            if (isset($savedRow['HoliDays']) && $savedRow['HoliDays'] !== '') {
                $Holidays = floatval($savedRow['HoliDays']);
            }
            if (isset($savedRow['WeekOffDays']) && $savedRow['WeekOffDays'] !== '') {
                $WeekOffs = floatval($savedRow['WeekOffDays']);
            }
            if (isset($savedRow['AbsentDays']) && $savedRow['AbsentDays'] !== '') {
                $AbsentDays = floatval($savedRow['AbsentDays']);
            }
            if (isset($savedRow['SandwichConverted']) && $savedRow['SandwichConverted'] !== '') {
                $SandwichDays = floatval($savedRow['SandwichConverted']);
            }
            if (isset($savedRow['LOP']) && $savedRow['LOP'] !== '') {
                $LOP = floatval($savedRow['LOP']);
            }
            if (isset($savedRow['ExtraWorkingDays']) && $savedRow['ExtraWorkingDays'] !== '') {
                $ExtraWorkingDays = floatval($savedRow['ExtraWorkingDays']);
            }
            if (isset($savedRow['latedeductiondays']) && $savedRow['latedeductiondays'] !== '') {
                $lateDeductionDays = floatval($savedRow['latedeductiondays']);
            }
            if (!empty($savedRow['latedeductionremark'])) {
                $lateDeductionRemark = $savedRow['latedeductionremark'];
            }
        }

        // ---------------------------------------------------
        // REQUIRED OUTPUT FORMAT
        // ---------------------------------------------------
        $final[] = [
            "EmployeeID" => $EmployeeID,
            "EmployeeName" => $EmployeeName,
            "GrossSalary" => $GrossSalary,
            "TotalDaysInMonth" => $TotalDaysInMonth,
            "PresentDays" => $PresentDays,
            "HalfDays" => $HalfDays,
            "CalculatedPresentDays" => $CalculatedPresentDays,
            "Leaves" => $Leaves,
            "Holidays" => $Holidays,
            "WeekOffs" => $WeekOffs,
            "AbsentDays" => $AbsentDays,
            "SandwichConverted" => $SandwichDays,
            "ExtraHoursWorked" => convertHoursToReadable($extraHours),
            "LateArrivals" => $lateCount,
            "LateDeductionDays" => $lateDeductionDays,
            "computedLateDeductionDays" => $computedLateDeductionDays,
            "LateDeductionRemark" => $lateDeductionRemark,
            "LOP" => $LOP,
            "ExtraWorkingDays" => $ExtraWorkingDays,
            "TotalPayableDays" => $TotalPayableDays,
            "isCalculated" => $isCalculated
        ];
    }

    return json_encode($final, JSON_PRETTY_PRINT);
}

// GET SHIFT HOURS (FULL & HALF DAY HOURS) FROM Employee Master

function getShiftHours($conn, $EmployeeID)
{
    $sql = "SELECT InTime, OutTime 
            FROM hrm_EmployeesMaster 
            WHERE EmployeeID=? 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $EmployeeID);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();

    // Fallback if shift not found
    if (!$res || empty($res['InTime']) || empty($res['OutTime'])) {
        return [
            "total" => "08:00",
            "half" => "03:12", // 40% of 8 hrs
            "full" => "07:12"  // 90% of 8 hrs
        ];
    }

    $shiftIn = strtotime($res['InTime']);
    $shiftOut = strtotime($res['OutTime']);

    // Handle night shift (example: 10PM to 6AM)
    if ($shiftOut <= $shiftIn) {
        $shiftOut += 86400; // Add 1 day
    }

    $totalSeconds = $shiftOut - $shiftIn;

    if ($totalSeconds <= 0) {
        return [
            "total" => "08:00",
            "half" => "03:12",
            "full" => "07:12"
        ];
    }

    // Calculate percentages
    $halfSeconds = floor($totalSeconds * 0.40); // 40%
    $fullSeconds = floor($totalSeconds * 0.90); // 90%

    return [
        "total" => formatSecondsToTime($totalSeconds),
        "half" => formatSecondsToTime($halfSeconds),
        "full" => formatSecondsToTime($fullSeconds)
    ];
}

function convertTimeToDecimal($time)
{
    if (empty($time))
        return 0;
    $parts = explode(":", $time);
    $hours = (isset($parts[0])) ? (float) $parts[0] : 0;
    $minutes = (isset($parts[1])) ? (float) $parts[1] : 0;
    return round($hours + ($minutes / 60), 2);
}

function convertHoursToReadable($hours)
{
    $totalMinutes = round($hours * 60);
    $h = floor($totalMinutes / 60);
    $m = $totalMinutes % 60;

    return sprintf("%02d:%02d", $h, $m);
}


/* Helper function */
function formatSecondsToTime($seconds)
{
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);

    return sprintf("%02d:%02d", $hours, $minutes);
}

function getEmployeeWorkingHoursFromMaster($conn, $ProjectID = null, $id = null, $returnJson = true)
{

    $where = "";
    $params = [];
    $types = "";

    // WHERE conditions
    if (!empty($ProjectID)) {
        $where .= " AND ProjectID = ?";
        $params[] = $ProjectID;
        $types .= "i";
    }

    if (!empty($id)) {
        $where .= " AND id = ?";
        $params[] = $id;
        $types .= "i";
    }

    // Final SQL
    $sql = "SELECT id, ProjectID, working_hour_name, start_time, end_time, 
                   total_hours, status, created_at
            FROM hrm_PayrollWorkingHourse_master
            WHERE 1=1 $where
            ORDER BY id DESC";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [
            "status" => "error",
            "message" => "Prepare Failed: " . $conn->error,
            "sql" => $sql
        ];
    }

    // Bind params if available
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    if ($returnJson) {
        return json_encode([
            "status" => "success",
            "data" => $data
        ]);
    } else {
        return $data;
    }
}



//**************
function debugQuery($sql, $params, $types)
{
    $output = $sql;
    $i = 0;
    foreach (str_split($types) as $type) {
        if (!isset($params[$i])) {
            continue;
        }
        $val = $params[$i];
        // Format values based on type
        if ($type === 'i') {
            $replacement = (int) $val;
        } elseif ($type === 'd') {
            $replacement = (float) $val;
        } else {
            $replacement = "'" . addslashes($val) . "'";
        }
        // Replace first occurrence of "?" with value
        $output = preg_replace('/\?/', $replacement, $output, 1);
        $i++;
    }
    return $output;
}

function deleteLeaveType($hrmconn, $leaveid)
{
    $deleted_at = date('Y-m-d H:i:s');
    $tedit = "Deleted on " . $deleted_at;
    $sql = "UPDATE hrm_PayrollLeaveTypeMaster SET tedit = ? WHERE id = ?";
    $stmt = $hrmconn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("si", $tedit, $leaveid);
        if ($stmt->execute()) {
            return ['status' => 'Success', 'message' => 'Leave type removed successfully.'];
        } else {
            return ['status' => 'Error', 'message' => $stmt->error];
        }
    } else {
        return ['status' => 'Error', 'message' => $hrmconn->error];
    }
}