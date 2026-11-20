<?php
@ini_set('display_errors', 0);
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
                    return "No file uploaded.";
                case UPLOAD_ERR_NO_TMP_DIR:
                    return "Missing temporary folder.";
                case UPLOAD_ERR_CANT_WRITE:
                    return "Failed to write file to disk.";
                case UPLOAD_ERR_EXTENSION:
                    return "File upload stopped by extension.";
                default:
                    return "Unknown file upload error.";
            }
        }
    }
    return null;
}


function getAttendanceLocationMaster($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all")
{
    $data = [];
    if (!$conn) {
        return $data;
    }

    try {
        // Build dynamic WHERE clause
        $whereClauses = [];
        $params = [];
        $types = "";

        if ($companyid !== "all" && !empty($companyid)) {
            $whereClauses[] = "CompanyID = ?";
            $params[] = $companyid;
            $types .= "s";
        }
        if ($subcompanyid !== "all" && !empty($subcompanyid)) {
            $whereClauses[] = "SubCompanyID = ?";
            $params[] = $subcompanyid;
            $types .= "s";
        }
        if ($projectid !== "all" && !empty($projectid)) {
            $whereClauses[] = "ProjectID = ?";
            $params[] = $projectid;
            $types .= "s";
        }

        // Add condition if shortby is not 'all'
        if ($shortby !== "all" && $shortbyvalue !== "all" && !empty($shortby) && !empty($shortbyvalue)) {
            $allowedColumns = ["LocationId", "LocationName", "sno"];
            if (in_array($shortby, $allowedColumns)) {
                $whereClauses[] = "$shortby = ?";
                $params[] = $shortbyvalue;
                $types .= "s";
            }
        }

        $sql = "SELECT sno, LocationId, CompanyID, SubCompanyID, ProjectID, 
                       LocationName, LocationAddress, Comments, created_at, tedit
                FROM hrm_AttendanceLocationMaster";
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(" AND ", $whereClauses);
        }

        $stmt = $conn->prepare($sql);
        if ($stmt) {
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            $stmt->close();
        }
    } catch (Exception $e) {
        error_log("Error in getAttendanceLocationMaster: " . $e->getMessage());
    }

    return $data;
}

function fetchLabourData($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue = null)
{
    $data = [];
    $condition = "";
    $types = "sss"; // default types (companyid, subcompanyid, projectid)
    $params = [$companyid, $subcompanyid, $projectid];

    // Apply filters
    if ($shortby == "EmployeeID" || $shortby == "LabourID") {
        $sql = "SELECT 
                    lm.EmployeeID, 
                    lm.seriesid,
                    lm.companyid, 
                    lm.subcompanyid, 
                    lm.projectid, 
                    lm.TitleName, 
                    IFNULL(lm.FirstName, '') AS FirstName, 
                    IFNULL(lm.MiddleName, '') AS MiddleName, 
                    IFNULL(lm.LastName, '') AS LastName, 
                    CONCAT_WS(' ', IFNULL(lm.TitleName, ''), IFNULL(lm.FirstName, ''), IFNULL(lm.MiddleName, ''), IFNULL(lm.LastName, '')) AS FullName, 
                    lm.FatherName, 
                    lm.MotherName, 
                    lm.MaritalStatus, 
                    lm.SpouseName, 
                    lm.Gender, 
                    lm.DateOfBirth, 
                    lm.DateOfJoining, 
                    lm.EmploymentTypeid, 
                    lm.DesignationID, 
                    lm.DepartmentID, 
                    lm.DateOfResignation, 
                    lm.Email, 
                    lm.MobileNo, 
                    lm.emergencycontactno,
                    lm.PanNumber, 
                    lm.AadharNumber, 
                    lm.PassportNumber, 
                    lm.VisaStatus, 
                    lm.Nationality, 
                    lm.WorkLocation, 
                    lm.CountryCode, 
                    lm.AddressLine1, 
                    lm.AddressLine2, 
                    lm.AddressLine3, 
                    lm.AddressLine4, 
                    lm.PostalCode, 
                    lm.CountryName,
                    lm.stateid,
                    lm.districtid,
                    lm.tahseel,
                    lm.city,
                    lm.address,
                    lm.graftingskill, 
                    lm.locationid, 
                    lm.DesignationID, 
                    lm.DepartmentID, 
                    lm.Status, 
                    lm.remark, 
                    lm.paytoid,
                    lm.idprooftype AS IdProofType,
                    lm.idproofpath AS idproofpath,
                    IFNULL(lpm.PerDayPayment, 0) AS PerDayPayment,
                    (SELECT DocumentPath FROM hrm_labourdocuments WHERE EmployeeID = lm.EmployeeID AND DocumentType = 'ProfilePhoto' LIMIT 1) as profile_photo
                FROM hrm_LabourMaster lm
                LEFT JOIN hrm_LabourPaymentMaster lpm 
                    ON lm.EmployeeID = lpm.EmployeeID
                WHERE lm.EmployeeID = ?";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $shortbyvalue);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }
            $stmt->close();
        }
        return $data;
    } elseif ($shortby == "allactivebutperdaypaymentmissing") {
        $condition = " AND lm.Status = 'Active' AND (lpm.PerDayPayment IS NULL OR lpm.PerDayPayment <= 0)";
    }

    $conn->query("SET NAMES 'utf8'");

    $sql = "SELECT 
                lm.EmployeeID, 
                lm.seriesid,
                lm.companyid, 
                lm.subcompanyid, 
                lm.projectid, 
                lm.TitleName, 
                IFNULL(lm.FirstName, '') AS FirstName, 
                IFNULL(lm.MiddleName, '') AS MiddleName, 
                IFNULL(lm.LastName, '') AS LastName, 
                CONCAT_WS(' ', IFNULL(lm.TitleName, ''), IFNULL(lm.FirstName, ''), IFNULL(lm.MiddleName, ''), IFNULL(lm.LastName, '')) AS FullName, 
                lm.FatherName, 
                lm.MotherName, 
                lm.MaritalStatus, 
                lm.SpouseName, 
                lm.Gender, 
                lm.DateOfBirth, 
                lm.DateOfJoining, 
                lm.EmploymentTypeid, 
                lm.DesignationID, 
                lm.DepartmentID, 
                lm.DateOfResignation, 
                lm.Email, 
                lm.MobileNo, 
                lm.emergencycontactno, 
                lm.PanNumber, 
                lm.AadharNumber, 
                lm.PassportNumber, 
                lm.VisaStatus, 
                lm.Nationality, 
                lm.WorkLocation, 
                lm.CountryCode, 
                lm.AddressLine1, 
                lm.AddressLine2, 
                lm.AddressLine3, 
                lm.AddressLine4, 
                lm.PostalCode, 
                lm.CountryName,
                lm.stateid,
                lm.districtid,
                lm.tahseel,
                lm.city,
                lm.address,
                lm.graftingskill, 
                lm.locationid, 
                lm.DesignationID, 
                lm.DepartmentID, 
                lm.Status, 
                lm.remark, 
                lm.paytoid,
                lm.idprooftype AS IdProofType,
                lm.idproofpath AS idproofpath,
                IFNULL(lpm.PerDayPayment, 0) AS PerDayPayment,
                (SELECT DocumentPath FROM hrm_labourdocuments WHERE EmployeeID = lm.EmployeeID AND DocumentType = 'ProfilePhoto' LIMIT 1) as profile_photo
            FROM hrm_LabourMaster lm
            LEFT JOIN hrm_LabourPaymentMaster lpm 
                ON lm.companyid = lpm.companyid 
               AND lm.subcompanyid = lpm.subcompanyid 
               AND lm.projectid = lpm.projectid 
               AND lm.EmployeeID = lpm.EmployeeID
            WHERE lm.companyid = ? 
              AND lm.subcompanyid = ? 
              AND lm.projectid = ? 
              $condition ORDER BY CAST(lm.EmployeeID AS UNSIGNED) ASC";

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        die("Error in SQL: " . $conn->error);
    }

    return $data;
}


function getLabourPaymentData($conn, $projectid, $shortby = null, $shortbyvalue = null)
{
    // Base query
    $sql = "SELECT sno, companyid, subcompanyid, projectid, EmployeeID, 
                   PerDayPayment, OtherPayment, Deductions, CurrencyCode, 
                   PaymentMethod, tedit, Comments
            FROM hrm_LabourPaymentMaster
            WHERE (tedit is null or tedit='') AND projectid = ?";

    $params = [$projectid];
    $types = "s";

    // If extra filter provided (shortby + shortbyvalue)
    if (!empty($shortby) && !empty($shortbyvalue)) {
        // Only allow safe columns (avoid SQL injection)
        $allowedColumns = ["companyid", "subcompanyid", "EmployeeID", "PaymentMethod", "CurrencyCode"];
        if (in_array($shortby, $allowedColumns)) {
            $sql .= " AND $shortby = ?";
            $params[] = $shortbyvalue;
            $types .= "s"; // treat as string; will still work for int
        }
    }

    $stmt = $conn->prepare($sql);
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

function fetchLabourCalculatedAttendance($conn, $companyID, $subCompanyID, $projectID, $datefrom, $dateto, $filterBy = null, $filterValue = null)
{
    $data = [];
    $condition = "";
    $params = [];
    $types = "iiiss"; // Default types: CompanyID, SubCompanyID, ProjectID, DateFrom, DateTo

    // Apply filters
    if ($filterBy === "all") {
        $condition = "";
    } elseif ($filterBy === "EmployeeID") {
        $condition = " AND a.EmployeeID = ?";
        $types .= "s";  // EmployeeID is string
        $params[] = (string)$filterValue;
    } elseif ($filterBy === "ExtraWorkingDays") {
        $condition = " AND a.ExtraWorkingDays > 0";
    } elseif ($filterBy === "AbsentDays") {
        $condition = " AND a.AbsentDays > 0";
    }

    $conn->query("SET NAMES 'utf8'");

    $sql = "SELECT a.* 
            FROM hrm_labourcalculateattendance a 
            JOIN hrm_LabourMaster e ON a.EmployeeID = e.EmployeeID 
            WHERE a.CompanyID = ? 
              AND a.SubCompanyID = ? 
              AND a.ProjectID = ? 
              AND a.DateFrom >= ? 
              AND a.DateTo <= ? 
              $condition";

    if ($stmt = $conn->prepare($sql)) {
        // Merge default parameters
        $params = array_merge([$companyID, $subCompanyID, $projectID, $datefrom, $dateto], $params);

        // Bind parameters dynamically
        $stmt->bind_param($types, ...$params);

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        die("Error in SQL: " . $conn->error);
    }

    return $data;
}


function fetchLabourAttendanceWithGrouped($conn, $companyID, $subCompanyID, $projectID, $filterBy = null, $filterValue1 = null, $filterValue2 = null, $filterValue3 = null)
{
    $data = [];
    $params = [$companyID, $subCompanyID, $projectID];
    $types = "sss"; // company, subcompany, project are strings

    $conn->query("SET NAMES 'utf8'");

    // ✅ Base SQL
    $sql = "SELECT 
            a.EmployeeID, 
            e.FirstName, 
            e.MiddleName, 
            e.LastName, 
            CONCAT(
                TRIM(e.FirstName), 
                ' ', 
                COALESCE(NULLIF(TRIM(e.MiddleName), ''), ''), 
                CASE 
                    WHEN e.MiddleName IS NOT NULL AND e.MiddleName <> '' THEN ' ' 
                    ELSE '' 
                END, 
                TRIM(e.LastName)
            ) AS EmployeeName,
            SUM(a.oldPresentDays) AS TotaloldPresentDays,
            SUM(a.CalculatedPresentDays) AS TotalPresentDays,
            COUNT(DISTINCT a.EmployeeID) AS UniqueEmployeeCount
        FROM hrm_labourcalculateattendance a
        JOIN hrm_LabourMaster e 
            ON a.EmployeeID = e.EmployeeID
        WHERE a.CompanyID = ? 
          AND a.SubCompanyID = ? 
          AND a.ProjectID = ?";

    // ✅ Apply filters BEFORE grouping
    if ($filterBy === "yearmonth" && $filterValue1 && $filterValue2) {
        $sql .= " AND a.AttendanceYear = ? AND a.AttendanceMonth = ?";
        $types .= "ii";
        $params[] = (int) $filterValue1; // Year
        $params[] = (int) $filterValue2; // Month

    } elseif ($filterBy === "yearmonthwithlocationid" && $filterValue1 && $filterValue2) {
        $sql .= " AND a.AttendanceYear = ? AND a.AttendanceMonth = ? AND a.LocationId = ?";
        $types .= "iis";
        $params[] = (int) $filterValue1; // Year
        $params[] = (int) $filterValue2; // Month
        $params[] = $filterValue3; // locationid
    } elseif ($filterBy === "datebetween" && $filterValue1 && $filterValue2) {
        $sql .= " AND a.DateFrom >= ? AND a.DateTo <= ?";
        $types .= "ss";
        $params[] = $filterValue1;
        $params[] = $filterValue2;
    } elseif ($filterBy === "datebetweenwithlocationid" && $filterValue1 && $filterValue2) {
        $sql .= " AND a.DateFrom >= ? AND a.DateTo <= ? AND a.LocationId = ?";
        $types .= "sss";
        $params[] = $filterValue1;
        $params[] = $filterValue2;
        $params[] = $filterValue3;
    } elseif ($filterBy === "yearmonthwithemployeeid" && $filterValue1 && $filterValue2 && $filterValue3) {
        $sql .= " AND a.AttendanceYear = ? AND a.AttendanceMonth = ? AND a.EmployeeID = ?";
        $types .= "iii";
        $params[] = (int) $filterValue1;
        $params[] = (int) $filterValue2;
        $params[] = (int) $filterValue3;
    } elseif ($filterBy === "datebetweenwithemployeeid" && $filterValue1 && $filterValue2 && $filterValue3) {
        $sql .= " AND a.DateFrom >= ? AND a.DateTo <= ? AND a.EmployeeID = ?";
        $types .= "sss";
        $params[] = $filterValue1;
        $params[] = $filterValue2;
        $params[] = (int) $filterValue3;
    } elseif ($filterBy === "allforsalarycalculation") {
        $sql .= " AND (a.SalaryID IS NULL OR a.SalaryID = '')";
    }

    // ✅ Grouping & ordering
    $sql .= " GROUP BY a.EmployeeID";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error in SQL prepare: " . $conn->error . " | SQL: " . $sql);
    }

    $stmt->bind_param($types, ...$params);
    // echo debugQuery($sql, $params, $types);
    if (!$stmt->execute()) {
        die("Error in SQL execute: " . $stmt->error);
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function fetchLabourAttendanceifMultiplelocation($conn, $companyID, $subCompanyID, $projectID, $EmployeeID, $DateFrom, $DateTo)
{
    $data = [];
    $conn->query("SET NAMES 'utf8'");

    // ✅ Use prepared statement for safety
    $sql = "SELECT 
                a.EmployeeID,
                a.LocationId,
                a.CalculatedPresentDays AS TotalPresentDays,
                a.WeekOffDays,
                a.HoliDays,
                a.PresentDays,
                a.AbsentDays,
                a.TotalApproveLeave,
                a.HalfDays,
                a.LateArrivals,
                a.WorkingDaysInMonth,
                a.ExtraWorkingDays,
                a.CalculatedPresentDays,
                a.Comments,
                a.attFileid,
                a.UpdateRemark,
                a.created_at
            FROM hrm_labourcalculateattendance a
            JOIN hrm_LabourMaster e ON a.EmployeeID = e.EmployeeID
            WHERE a.CompanyID = ?
              AND a.SubCompanyID = ?
              AND a.ProjectID = ?
              AND a.EmployeeID = ?
              AND a.DateFrom >= ?
              AND a.DateTo <= ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Error in SQL prepare: " . $conn->error . " | SQL: " . $sql);
    }

    // ✅ Bind parameters (all treated as strings safely)
    $stmt->bind_param("ssssss", $companyID, $subCompanyID, $projectID, $EmployeeID, $DateFrom, $DateTo);

    if (!$stmt->execute()) {
        die("Error in SQL execute: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    $stmt->close();
    return $data;
}


function checkAttendanceExistWithLocationId($conn, $companyID, $subCompanyID, $projectID, $DateFrom, $DateTo, $LocationId)
{
    $response = [
        "status" => "error",
        "message" => "Unknown error"
    ];

    $types = "ssssss";
    $params = [$companyID, $subCompanyID, $projectID, $DateFrom, $DateTo, $LocationId];

    $conn->query("SET NAMES 'utf8'");

    $sql = "SELECT 1 
            FROM hrm_labourcalculateattendance 
            WHERE CompanyID = ? 
              AND SubCompanyID = ? 
              AND ProjectID = ? 
              AND DateFrom >= ? 
              AND DateTo <= ? 
              AND LocationId = ? 
            LIMIT 1";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [
            "status" => "error",
            "message" => "Error in SQL prepare: " . $conn->error
        ];
    }

    $stmt->bind_param($types, ...$params);
    // $eex = debugQuery($sql, $params, $types);
    if (!$stmt->execute()) {
        return [
            "status" => "error",
            "message" => "Error in SQL execute: " . $stmt->error
        ];
    }
    // $rrr = $DateFrom.'=='.$DateTo.'=='.$LocationId;
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $response = [
            "status" => "success",
            "message" => "Attendance already exists for the given criteria."
        ];
    } else {
        $response = [
            "status" => "not_found",
            "message" => "No attendance found for the given criteria."
        ];
    }

    $stmt->close();
    return $response;
}

function removeLabourCalculateAttendanceData($conn, $projectID, $locationId, $dateFrom, $dateTo)
{
    // Validate required parameters
    if (empty($projectID) || empty($dateFrom) || empty($dateTo)) {
        return json_encode([
            'status' => 'error',
            'message' => 'Project ID, Date From, and Date To are required.'
        ]);
    }

    // Step 1: Fetch attFileid list
    $sqlFetch = "
        SELECT attFileid 
        FROM hrmplus.hrm_labourcalculateattendance 
        WHERE ProjectID = ? 
          AND DateFrom >= ? 
          AND DateTo <= ? 
          AND LocationId = ?
    ";

    $stmtFetch = $conn->prepare($sqlFetch);
    if (!$stmtFetch) {
        return json_encode([
            'status' => 'error',
            'message' => 'Database prepare error (fetch): ' . $conn->error
        ]);
    }

    $stmtFetch->bind_param("ssss", $projectID, $dateFrom, $dateTo, $locationId);
    $stmtFetch->execute();
    $result = $stmtFetch->get_result();

    $fileIDs = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['attFileid'])) {
            $fileIDs[] = $row['attFileid'];
        }
    }
    $stmtFetch->close();

    // If no records found, return early
    if (empty($fileIDs)) {
        return json_encode([
            'status' => 'info',
            'message' => 'No attendance records found for the selected criteria.'
        ]);
    }

    // Start transaction to ensure atomicity
    $conn->begin_transaction();

    try {
        // Step 2: Delete from hrm_labourcalculateattendance
        $sqlDeleteAttendance = "
            DELETE FROM hrmplus.hrm_labourcalculateattendance 
            WHERE ProjectID = ? 
              AND DateFrom >= ? 
              AND DateTo <= ? 
              AND LocationId = ? 
              AND (SalaryID IS NULL OR SalaryID = '')
        ";
        $stmtDelAtt = $conn->prepare($sqlDeleteAttendance);
        $stmtDelAtt->bind_param("ssss", $projectID, $dateFrom, $dateTo, $locationId);
        $stmtDelAtt->execute();
        $deletedAttendance = $stmtDelAtt->affected_rows;
        $stmtDelAtt->close();

        // Step 3: Delete related records from hrm_AttendanceFiles
        $inClause = implode(',', array_fill(0, count($fileIDs), '?'));
        $sqlDeleteFiles = "
            DELETE FROM hrmplus.hrm_AttendanceFiles 
            WHERE FileID IN ($inClause)
        ";

        $stmtDelFiles = $conn->prepare($sqlDeleteFiles);

        // Dynamically bind parameters
        $types = str_repeat('i', count($fileIDs));
        $stmtDelFiles->bind_param($types, ...$fileIDs);
        $stmtDelFiles->execute();
        $deletedFiles = $stmtDelFiles->affected_rows;
        $stmtDelFiles->close();

        // ✅ Commit transaction
        $conn->commit();

        return json_encode([
            'status' => 'success',
            'message' => "$deletedAttendance attendance record(s) and $deletedFiles file record(s) removed successfully."
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        return json_encode([
            'status' => 'error',
            'message' => 'Transaction failed: ' . $e->getMessage()
        ]);
    }
}

function getLabourCalculatedSalary(
    $conn,
    $CompanyID,
    $SubCompanyID,
    $ProjectID,
    $shortBy = null,
    $shortByValue = null,
    $limit = 2000,        // default 2000 rows
    $offset = 0          // default start
) {

    $sql = "SELECT * FROM hrm_LabourCalculatedSalary WHERE CompanyID = ? AND SubCompanyID = ? AND ProjectID = ?";

    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    if ($shortBy === "employeeid" || $shortBy === "EmployeeID") {
        $sql .= " AND EmployeeID = ?";
        $params[] = (string) $shortByValue;
        $types .= "s";
    } elseif ($shortBy === "datebetweenWithEmployeeId" || $shortBy === "withindates") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $EmployeeID = (string) $expdate[0];
            $DateFrom = $expdate[1];
            $DateTo = $expdate[2];
            $sql .= " AND EmployeeID = ? AND DateFrom >= ? AND Dateto <= ?";
            $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo]);
            $types .= "sss";
        }
    } elseif ($shortBy === "datebetweenWithEmployeeIdlocationid" || $shortBy === "withindateswithlocationid") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 4) {
            $EmployeeID = (string) $expdate[0];
            $DateFrom = $expdate[1];
            $DateTo = $expdate[2];
            $LocationId = $expdate[3];
            if ($LocationId == "all") {
                $sql .= " AND EmployeeID = ? AND DateFrom >= ? AND Dateto <= ?";
                $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo]);
                $types .= "sss";
            } else {
                $sql .= " AND EmployeeID = ? AND DateFrom >= ? AND Dateto <= ? AND LocationId = ?";
                $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo, $LocationId]);
                $types .= "ssss";
            }
        }
    } elseif ($shortBy === "datebetween" || $shortBy === "withindates") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 2) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $sql .= " AND DateFrom >= ? AND Dateto <= ?";
            $params = array_merge($params, [$DateFrom, $DateTo]);
            $types .= "ss";
        }
    } elseif ($shortBy === "datebetweenwithlocationid" || $shortBy === "withindateswithlocationid") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $LocationId = $expdate[2];
            $sql .= " AND DateFrom >= ? AND Dateto <= ? AND LocationId = ?";
            $params = array_merge($params, [$DateFrom, $DateTo, $LocationId]);
            $types .= "sss";
        }
    } elseif ($shortBy === "pendingforBookWithdatesEmployeeId") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $EmployeeID = (string) $expdate[0];
            $DateFrom = $expdate[1];
            $DateTo = $expdate[2];
            $sql .= " AND (requestno = '' OR requestno IS NULL)
                      AND EmployeeID = ? AND DateFrom >= ? AND Dateto <= ?";
            $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo]);
            $types .= "sss";
        }
    } elseif ($shortBy === "pendingforBookWithdateRange") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 2) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $sql .= " AND (requestno = '' OR requestno IS NULL)
                      AND DateFrom >= ? AND Dateto <= ?";
            $params = array_merge($params, [$DateFrom, $DateTo]);
            $types .= "ss";
        }
    } elseif ($shortBy === "pendingforbook") {
        $sql .= " AND (requestno = '' OR requestno IS NULL)";
    }

    $sql .= " ORDER BY SalaryYear DESC, SalaryMonth DESC";
    // echo debugQuery($sql, $params, $types);
    // die();
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["error" => "❌ Statement preparation failed: " . $conn->error];
    }
    if (!$stmt->bind_param($types, ...$params)) {
        return ["error" => "❌ Parameter binding failed: " . $stmt->error];
    }
    if (!$stmt->execute()) {
        return ["error" => "❌ Query execution failed: " . $stmt->error];
    }
    $result = $stmt->get_result();
    if (!$result) {
        return ["error" => "❌ Failed to fetch result: " . $conn->error];
    }
    // print_r($result->fetch_assoc());
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $stmt->close();
    return $data;
}

function getPendingLabourWages($conn, $CompanyID, $SubCompanyID, $ProjectID, $shortBy = null, $shortByValue = null, $limit = 2000, $offset = 0)
{
    // ✅ Base SQL
    $sql = "SELECT * FROM hrm_LabourCalculatedSalary WHERE ((PaymentStatus IS NULL OR PaymentStatus = '') OR ((PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') AND NetSalary > COALESCE(PaidAmount, 0))) 
              AND CompanyID = ? 
              AND SubCompanyID = ? 
              AND ProjectID = ?";

    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    // ✅ Conditional Filters
    if ($shortBy === "employeeid" || $shortBy === "EmployeeID") {
        $sql .= " AND EmployeeID = ?";
        $params[] = (string) $shortByValue;
        $types .= "s";

    } elseif ($shortBy === "datebetweenWithEmployeeId") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $EmployeeID = (string) $expdate[0];
            $DateFrom = $expdate[1];
            $DateTo = $expdate[2];
            $sql .= " AND EmployeeID = ? AND DateFrom >= ? AND Dateto <= ?";
            $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo]);
            $types .= "sss";
        }

    } elseif ($shortBy === "allwithinbetweendate" || $shortBy === "datebetween") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 2) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $sql .= " AND DateFrom >= ? AND Dateto <= ?";
            $params = array_merge($params, [$DateFrom, $DateTo]);
            $types .= "ss";
        }
    } elseif ($shortBy === "datebetweenwithlocationid") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $LocationId = $expdate[2];
            $sql .= " AND DateFrom >= ? AND Dateto <= ? AND LocationId = ?";
            $params = array_merge($params, [$DateFrom, $DateTo, $LocationId]);
            $types .= "sss";
        }
    }

    // ✅ Sorting
    $sql .= " ORDER BY SalaryYear DESC, SalaryMonth DESC";

    // ✅ Prepare and execute
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["error" => "❌ Statement preparation failed: " . $conn->error];
    }

    if (!$stmt->bind_param($types, ...$params)) {
        return ["error" => "❌ Parameter binding failed: " . $stmt->error];
    }

    if (!$stmt->execute()) {
        return ["error" => "❌ Query execution failed: " . $stmt->error];
    }

    $result = $stmt->get_result();
    if (!$result) {
        return ["error" => "❌ Failed to fetch result: " . $conn->error];
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function getPaidLabourWages($conn, $CompanyID, $SubCompanyID, $ProjectID, $shortBy = null, $shortByValue = null, $limit = 2000, $offset = 0)
{
    // ✅ Base SQL
    $sql = "SELECT * FROM hrm_LabourCalculatedSalary WHERE (PaymentStatus = 'Paid' OR PaymentStatus = 'paid' OR PaymentStatus = 'PAID') 
              AND CompanyID = ? 
              AND SubCompanyID = ? 
              AND ProjectID = ?";

    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    // ✅ Conditional Filters
    if ($shortBy === "employeeid" || $shortBy === "EmployeeID") {
        $sql .= " AND EmployeeID = ?";
        $params[] = (string) $shortByValue;
        $types .= "s";

    } elseif ($shortBy === "datebetweenWithEmployeeId") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $EmployeeID = (string) $expdate[0];
            $DateFrom = $expdate[1];
            $DateTo = $expdate[2];
            $sql .= " AND EmployeeID = ? AND DateFrom >= ? AND DateTo <= ?";
            $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo]);
            $types .= "sss";
        }

    } elseif ($shortBy === "allwithinbetweendate" || $shortBy === "datebetween") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 2) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $sql .= " AND DateFrom >= ? AND DateTo <= ?";
            $params = array_merge($params, [$DateFrom, $DateTo]);
            $types .= "ss";
        }
    } elseif ($shortBy === "datebetweenwithlocationid") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $LocationId = $expdate[2];
            $sql .= " AND DateFrom >= ? AND DateTo <= ? AND LocationId = ?";
            $params = array_merge($params, [$DateFrom, $DateTo, $LocationId]);
            $types .= "sss";
        }
    }

    // ✅ Sorting
    $sql .= " ORDER BY SalaryYear DESC, SalaryMonth DESC";

    // ✅ Prepare and execute
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["error" => "❌ Statement preparation failed: " . $conn->error];
    }

    if (!$stmt->bind_param($types, ...$params)) {
        return ["error" => "❌ Parameter binding failed: " . $stmt->error];
    }

    if (!$stmt->execute()) {
        return ["error" => "❌ Query execution failed: " . $stmt->error];
    }

    $result = $stmt->get_result();
    if (!$result) {
        return ["error" => "❌ Failed to fetch result: " . $conn->error];
    }

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();
    return $data;
}

function getLabourLocationList($conn, $ProjectID, $filter = null, $filterValue = null)
{
    $data = [];

    $sql = "SELECT DISTINCT 
                l.LocationId, 
                COALESCE(NULLIF(m.LocationName, ''), NULLIF(l.LocationId, ''), 'Unknown Location') AS LocationName 
            FROM hrm_labourcalculateattendance l 
            LEFT JOIN hrm_AttendanceLocationMaster m 
                ON m.LocationId = l.LocationId 
            WHERE l.ProjectID = ?";

    $params = [$ProjectID];
    $types = "s";

    // Apply Filters
    if ($filter === "pendingforsalary") {

        $sql .= " AND (l.SalaryID = '' OR l.SalaryID IS NULL)";

    } elseif ($filter === "pendingforsalarywithlocation") {

        $sql .= " AND (l.SalaryID = '' OR l.SalaryID IS NULL) AND l.LocationId = ?";
        $params[] = $filterValue;
        $types .= "s";

    } elseif ($filter === "withindates") {

        // Extract DateFrom|DateTo
        $dtexp = explode("|", $filterValue);

        if (count($dtexp) === 2) {
            $DateFrom = $dtexp[0];
            $DateTo = $dtexp[1];

            $sql .= " AND l.DateFrom >= ? AND l.DateTo <= ?";
            $params[] = $DateFrom;
            $params[] = $DateTo;
            $types .= "ss";
        } else {
            return ["error" => "Invalid date format. Use DateFrom|DateTo"];
        }
    }

    // ORDER BY Location Name
    $sql .= " ORDER BY LocationName ASC";

    // Prepare Statement
    if ($stmt = $conn->prepare($sql)) {

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

    } else {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }

    return $data;
}



function getLabourAttendanceMonthYearList($conn, $CompanyID, $SubCompanyID, $ProjectID, $filter = null, $filterValue = null)
{
    $data = [];
    $sql = "SELECT DISTINCT AttendanceYear, AttendanceMonth
            FROM hrm_labourcalculateattendance
            WHERE CompanyID = ?
              AND SubCompanyID = ?
              AND ProjectID = ?";
    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    // Apply filter for pending approvals
    if ($filter === "pendingforsalary") {
        $sql .= " AND (SalaryID = '' OR SalaryID IS NULL)";
    } else if ($filter === "pendingforsalarywithlocation") {
        $sql .= " AND (SalaryID = '' OR SalaryID IS NULL) AND LocationId = ?";
        $params = array_merge($params, [$filterValue]);
        $types .= "s";
    }

    $sql .= " ORDER BY AttendanceYear DESC, AttendanceMonth DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Optional: add MonthName for display
            $row['MonthName'] = date("M", mktime(0, 0, 0, $row['AttendanceMonth'], 1));
            $data[] = $row;
        }

        $stmt->close();
    } else {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }
    return $data;
}

function getLabourAttendanceDateRange($conn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth)
{
    $data = [];
    $sql = "SELECT DateFrom, DateTo
            FROM hrm_labourcalculateattendance
            WHERE CompanyID = ?
              AND SubCompanyID = ?
              AND ProjectID = ?
              AND AttendanceYear = ?
              AND AttendanceMonth = ?
            GROUP BY DateFrom, DateTo
            ORDER BY AttendanceYear DESC, AttendanceMonth DESC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
        $stmt->execute();
        $result = $stmt->get_result();

        // ✅ fetch all rows
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }

    return $data;
}

function getLabourSalaryMonthYearList($conn, $CompanyID, $SubCompanyID, $ProjectID, $filter = null)
{
    $data = [];
    $sql = "SELECT DISTINCT SalaryYear, SalaryMonth
            FROM hrm_LabourCalculatedSalary
            WHERE CompanyID = ?
              AND SubCompanyID = ?
              AND ProjectID = ?";
    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    // Apply filter for pending approvals
    if ($filter === "pendingforapproval") {
        $sql .= " AND (requestno = '' OR requestno IS NULL)";
    } else if ($filter === "afterapproval") {
        $sql .= " AND requestno <> ''";
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

function getLabourSalaryDateRange($conn, $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth)
{
    $data = [];
    $sql = "SELECT DateFrom, DateTo
            FROM hrm_LabourCalculatedSalary
            WHERE CompanyID = ?
              AND SubCompanyID = ?
              AND ProjectID = ?
              AND SalaryYear = ?
              AND SalaryMonth = ?
            GROUP BY DateFrom, DateTo
            ORDER BY SalaryYear DESC, SalaryMonth DESC"; // Optional: first entry if multiple

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssss", $CompanyID, $SubCompanyID, $ProjectID, $SalaryYear, $SalaryMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row; // ['DateFrom' => '2025-09-01', 'DateTo' => '2025-09-30']
        }
        $stmt->close();
    } else {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }

    return $data;
}

function getLabourSalaryLocationList($conn, $ProjectID, $filter = null, $filterValue = null)
{
    $data = [];

    $sql = "SELECT DISTINCT 
                l.LocationId, 
                COALESCE(NULLIF(m.LocationName, ''), NULLIF(l.LocationId, ''), 'Unknown Location') AS LocationName 
            FROM hrm_LabourCalculatedSalary l 
            LEFT JOIN hrm_AttendanceLocationMaster m 
                ON m.LocationId = l.LocationId 
            WHERE l.ProjectID = ?";
    $params = [$ProjectID];
    $types = "s";

    // Apply Filters
    if ($filter === "pendingforsalary") {
        $sql .= " AND (l.SalaryID = '' OR l.SalaryID IS NULL)";

    } elseif ($filter === "pendingforsalarywithlocation") {
        $sql .= " AND (l.SalaryID = '' OR l.SalaryID IS NULL) AND l.LocationId = ?";
        $params[] = $filterValue;
        $types .= "s";

    } elseif ($filter === "withindates") {

        $dtexp = explode("|", $filterValue);
        if (count($dtexp) === 2) {
            $DateFrom = $dtexp[0];
            $DateTo = $dtexp[1];

            $sql .= " AND l.DateFrom >= ? AND l.DateTo <= ?";
            $params[] = $DateFrom;
            $params[] = $DateTo;
            $types .= "ss";
        } else {
            return ["error" => "Invalid date format. Use DateFrom|DateTo"];
        }
    }

    $sql .= " ORDER BY LocationName ASC";

    if ($stmt = $conn->prepare($sql)) {

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();

    } else {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }

    // ➕ Add the ALL option at the top
    $data = array_merge(
        [["LocationId" => "all", "LocationName" => "All"]],
        $data
    );

    return $data;
}

function getLabourPendingSalaryLocationList($conn, $CompanyID, $SubCompanyID, $ProjectID, $yearmonth = null, $daterange = null)
{
    $data = [];

    $sql = "SELECT DISTINCT 
                l.LocationId, 
                COALESCE(NULLIF(m.LocationName, ''), NULLIF(l.LocationId, ''), 'Unknown Location') AS LocationName 
            FROM hrm_LabourCalculatedSalary l 
            LEFT JOIN hrm_AttendanceLocationMaster m 
                ON m.LocationId = l.LocationId 
            WHERE l.CompanyID = ? 
              AND l.SubCompanyID = ? 
              AND l.ProjectID = ? 
              AND (l.requestno = '' OR l.requestno IS NULL)";

    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    if (!empty($yearmonth) && strpos($yearmonth, '-') !== false) {
        $ymexp = explode("-", $yearmonth);
        $attYear = (int)$ymexp[0];
        $attMonth = (int)$ymexp[1];

        if ($daterange === "fullmonth") {
            $sql .= " AND l.SalaryYear = ? AND l.SalaryMonth = ?";
            $params[] = $attYear;
            $params[] = $attMonth;
            $types .= "ii";
        } else if (!empty($daterange) && strpos($daterange, '|') !== false) {
            $dtexp = explode("|", $daterange);
            $DateFrom = trim($dtexp[0]);
            $DateTo = trim($dtexp[1]);
            $sql .= " AND l.DateFrom >= ? AND l.DateTo <= ?";
            $params[] = $DateFrom;
            $params[] = $DateTo;
            $types .= "ss";
        }
    }

    $sql .= " ORDER BY LocationName ASC";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
    } else {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }

    return $data;
}



function getLabourPendingSalaryForapproval(
    $conn,
    $CompanyID,
    $SubCompanyID,
    $ProjectID,
    $filterBy = null,
    $filterValue1 = null,
    $filterValue2 = null,
    $filterValue3 = null
) {
    // --- Base SQL ---
    $sql = "SELECT *
            FROM hrm_LabourCalculatedSalary
            WHERE CompanyID = ?
              AND SubCompanyID = ?
              AND ProjectID = ?
              AND (requestno = '' OR requestno IS NULL)";

    // base params
    $params = [];
    $types = "";

    // push base params
    $params[] = $CompanyID;
    $params[] = $SubCompanyID;
    $params[] = $ProjectID;

    $types .= "sss";

    // -----------------------------
    // APPLY FILTERS SAFELY
    // -----------------------------
    if ($filterBy === "yearmonth" && $filterValue1 && $filterValue2) {

        $sql .= " AND SalaryYear = ? AND SalaryMonth = ?";
        $types .= "ii";
        $params[] = (int) $filterValue1;
        $params[] = (int) $filterValue2;

    } elseif ($filterBy === "yearmonthwithlocationid" && $filterValue1 && $filterValue2 && $filterValue3) {

        $sql .= " AND SalaryYear = ? AND SalaryMonth = ? AND LocationId = ?";
        $types .= "iis";
        $params[] = (int) $filterValue1;
        $params[] = (int) $filterValue2;
        $params[] = $filterValue3;

    } elseif ($filterBy === "datebetween" && $filterValue1 && $filterValue2) {

        $sql .= " AND DateFrom >= ? AND DateTo <= ?";
        $types .= "ss";
        $params[] = $filterValue1;
        $params[] = $filterValue2;

    } elseif ($filterBy === "datebetweenwithlocationid" && $filterValue1 && $filterValue2 && $filterValue3) {

        $sql .= " AND DateFrom >= ? AND DateTo <= ? AND LocationId = ?";
        $types .= "sss";
        $params[] = $filterValue1;
        $params[] = $filterValue2;
        $params[] = $filterValue3;

    } elseif ($filterBy === "yearmonthwithemployeeid" && $filterValue1 && $filterValue2 && $filterValue3) {

        $sql .= " AND SalaryYear = ? AND SalaryMonth = ? AND EmployeeID = ?";
        $types .= "iis";
        $params[] = (int) $filterValue1;
        $params[] = (int) $filterValue2;
        $params[] = (int) $filterValue3;

    } elseif ($filterBy === "datebetweenwithemployeeid" && $filterValue1 && $filterValue2 && $filterValue3) {

        $sql .= " AND DateFrom >= ? AND DateTo <= ? AND EmployeeID = ?";
        $types .= "sss";
        $params[] = $filterValue1;
        $params[] = $filterValue2;
        $params[] = (int) $filterValue3;

    } elseif ($filterBy === "allforsalarycalculation") {

        // no alias "a" → corrected
        $sql .= " AND (SalaryID IS NULL OR SalaryID = '')";
    }

    // Grouping (if required)
    $sql .= " GROUP BY EmployeeID";

    // Ordering
    $sql .= " ORDER BY SalaryYear DESC, SalaryMonth DESC";

    // ------------------------------------------------------
    // PREPARE STATEMENT
    // ------------------------------------------------------
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ["error" => "SQL prepare failed: " . $conn->error];
    }

    // bind params dynamically
    if (!$stmt->bind_param($types, ...$params)) {
        return ["error" => "Parameter bind failed: " . $stmt->error];
    }

    if (!$stmt->execute()) {
        return ["error" => "Query execution failed: " . $stmt->error];
    }

    $result = $stmt->get_result();

    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }

    $stmt->close();
    return $data;
}



function FetchLabourOtherPayment($conn, $projectid, $employeeid)
{
    try {
        $sql = "SELECT * 
                FROM hrm_LabourOtherPayment 
                WHERE projectid = ? AND EmployeeID = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("ss", $projectid, $employeeid); // s = string, s = string
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        $stmt->close();
        return $data; // return as array
    } catch (Exception $e) {
        error_log("Error fetching Labour Other Payment: " . $e->getMessage());
        return [];
    }
}

function NotCalculatedLabourOtherPayment($conn, $projectid, $employeeid, $datefrom, $dateto)
{
    try {
        // Aggregate to ensure all pending payments are summed up, not just the first one
        $sql = "SELECT 
                    IFNULL(SUM(incentivegrafting), 0) as incentivegrafting,
                    IFNULL(SUM(incentiveseasional), 0) as incentiveseasional,
                    IFNULL(SUM(overtimepayment), 0) as overtimepayment,
                    IFNULL(SUM(otherpayment), 0) as otherpayment
                FROM hrm_LabourOtherPayment 
                WHERE (status IS NULL OR status = '') 
                  AND projectid = ? 
                  AND EmployeeID = ?
                  AND datefrom = ?
                  AND dateto = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssss", $projectid, $employeeid,$datefrom,$dateto); // s = string, s = string
        $stmt->execute();
        $result = $stmt->get_result();
        $data = [];
        if ($row = $result->fetch_assoc()) {
            $data[] = $row;
        } else {
            $data[] = [
                'incentivegrafting' => 0,
                'incentiveseasional' => 0,
                'overtimepayment' => 0,
                'otherpayment' => 0
            ];
        }
        $stmt->close();
        return $data;
    } catch (Exception $e) {
        error_log("Error fetching Labour Other Payment: " . $e->getMessage());
        return [];
    }
}

function getAttendanceFiles($hrmconn, $projectid, $shortby = "all", $shortbyvalue = null)
{
    $data = [];

    try {
        // ✅ Validate projectid
        if (empty($projectid)) {
            throw new Exception("Project ID is required.");
        }

        // ✅ Allowed filter columns
        $allowedColumns = ["FileID", "locationId", "status", "dateFrom", "dateTo"];

        // ✅ Base SQL
        $sql = "SELECT 
                    FileID, companyid, subcompanyid, projectid, locationId, 
                    dateFrom, dateTo, original_filename, stored_filename, 
                    file_path, uploaded_by, uploaded_at, status
                FROM hrm_AttendanceFiles
                WHERE projectid = ?";

        $types = "s"; // for projectid
        $params = [$projectid];

        // ✅ Add filtering if valid column & value are provided
        if ($shortby !== "all" && in_array($shortby, $allowedColumns) && !empty($shortbyvalue)) {
            $sql .= " AND $shortby = ?";
            $types .= "s";
            $params[] = $shortbyvalue;
        }

        $sql .= " ORDER BY uploaded_at DESC";

        // ✅ Prepare statement
        $stmt = $hrmconn->prepare($sql);
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $hrmconn->error);
        }

        // ✅ Bind parameters dynamically
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

// Added for Labour Registration
function fetchDepartmentMaster($conn, $companyid, $shortby, $shortbyvalue = null)
{
    $departmentData = [];
    $condition = "";
    $params = [];
    $types = "";
    if ($shortby === "id") {
        $condition = " AND DepartmentID = ?";
        $params[] = $shortbyvalue;
        $types .= "i";
    } elseif ($shortby === "name") {
        $condition = " AND DepartmentName = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    }
    $conn->query("SET NAMES 'UTF8'");
    $sql = "SELECT DepartmentID, DepartmentName, ManagerID FROM hrm_DepartmentMaster WHERE (tedit IS NULL OR tedit = '') $condition";
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $departmentData[] = $row;
        }
        $stmt->close();
    }
    return $departmentData;
}

function fetchDesignationsMaster($conn, $companyid, $shortby = null, $shortbyvalue = null)
{
    $designationData = [];
    $where = [];
    $params = [];
    $types = "";
    $where[] = "(tedit IS NULL OR tedit = '')";
    if (!empty($shortby) && ($shortby === "id" || $shortby === "DesignationID")) {
        $where[] = "DesignationID = ?";
        $params[] = (int) $shortbyvalue;
        $types .= "i";
    } elseif (!empty($shortby) && $shortby === "name" && !empty($shortbyvalue)) {
        $where[] = "LOWER(DesignationName) = LOWER(?)";
        $params[] = $shortbyvalue;
        $types .= "s";
    }
    if (!empty($companyid)) {
        $where[] = "(companyid IS NULL OR companyid = '' OR companyid = ?)";
        $params[] = $companyid;
        $types .= "s";
    } else {
        $where[] = "(companyid IS NULL OR companyid = '')";
    }
    $sql = "SELECT DesignationID, DesignationName, Level FROM hrm_DesignationsMaster WHERE " . implode(" AND ", $where) . " ORDER BY DesignationName ASC";
    $conn->query("SET NAMES 'utf8mb4'");
    if ($stmt = $conn->prepare($sql)) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $designationData[] = $row;
        }
        $stmt->close();
    }
    return $designationData;
}

function getLabourCalculatedSalaryOptimized(
    $conn,
    $CompanyID,
    $SubCompanyID,
    $ProjectID,
    $shortBy = null,
    $shortByValue = null,
    $limit = 2000,
    $offset = 0
) {
    $sql = "SELECT 
                s.*, 
                e.FirstName, 
                e.MiddleName, 
                e.LastName,
                e.paytoid,
                a.CalculatedPresentDays,
                b.bankname,
                b.accountnumber,
                b.ifsccode,
                b.verifystatus
            FROM hrm_LabourCalculatedSalary s
            LEFT JOIN hrm_LabourMaster e 
                ON s.EmployeeID = e.EmployeeID 
               AND s.CompanyID = e.CompanyID 
               AND s.SubCompanyID = e.SubCompanyID 
               AND s.ProjectID = e.ProjectID
            LEFT JOIN hrmplus.hrm_labourcalculateattendance a 
                ON s.SalaryID = a.SalaryID 
               AND s.EmployeeID = a.EmployeeID 
               AND s.ProjectID = a.ProjectID 
               AND s.SalaryYear = a.AttendanceYear 
               AND s.SalaryMonth = a.AttendanceMonth
            LEFT JOIN hrm_labourbankdetails b
                ON s.EmployeeID = b.labourid
               AND s.CompanyID = b.companyid
               AND s.SubCompanyID = b.subcompanyid
               AND s.ProjectID = b.projectid
               AND (b.tedit IS NULL OR b.tedit = '')
            WHERE s.CompanyID = ? AND s.SubCompanyID = ? AND s.ProjectID = ?";

    $params = [$CompanyID, $SubCompanyID, $ProjectID];
    $types = "sss";

    if ($shortBy === "employeeid" || $shortBy === "EmployeeID") {
        $sql .= " AND s.EmployeeID = ?";
        $params[] = (string) $shortByValue;
        $types .= "s";
    } elseif ($shortBy === "datebetweenWithEmployeeId" || $shortBy === "withindates") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $EmployeeID = (string) $expdate[0];
            $DateFrom = $expdate[1];
            $DateTo = $expdate[2];
            $sql .= " AND s.EmployeeID = ? AND s.DateFrom >= ? AND s.Dateto <= ?";
            $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo]);
            $types .= "sss";
        }
    } elseif ($shortBy === "datebetweenWithEmployeeIdlocationid" || $shortBy === "withindateswithlocationid") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 4) {
            $EmployeeID = (string) $expdate[0];
            $DateFrom = $expdate[1];
            $DateTo = $expdate[2];
            $LocationId = $expdate[3];
            if ($LocationId == "all") {
                $sql .= " AND s.EmployeeID = ? AND s.DateFrom >= ? AND s.Dateto <= ?";
                $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo]);
                $types .= "sss";
            } else {
                $sql .= " AND s.EmployeeID = ? AND s.DateFrom >= ? AND s.Dateto <= ? AND s.LocationId = ?";
                $params = array_merge($params, [$EmployeeID, $DateFrom, $DateTo, $LocationId]);
                $types .= "ssss";
            }
        }
    } elseif ($shortBy === "datebetween" || $shortBy === "withindates") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 2) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $sql .= " AND s.DateFrom >= ? AND s.Dateto <= ?";
            $params = array_merge($params, [$DateFrom, $DateTo]);
            $types .= "ss";
        }
    } elseif ($shortBy === "datebetweenwithlocationid" || $shortBy === "withindateswithlocationid") {
        $expdate = explode("|", $shortByValue);
        if (count($expdate) === 3) {
            $DateFrom = $expdate[0];
            $DateTo = $expdate[1];
            $LocationId = $expdate[2];
            $sql .= " AND s.DateFrom >= ? AND s.Dateto <= ? AND s.LocationId = ?";
            $params = array_merge($params, [$DateFrom, $DateTo, $LocationId]);
            $types .= "sss";
        }
    }

    $sql .= " LIMIT ? OFFSET ?";
    $params = array_merge($params, [$limit, $offset]);
    $types .= "ii";

    $data = [];
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();
    }
    return $data;
}
?>
