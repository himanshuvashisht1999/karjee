<?php
function fetchLabourDataForProduction($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue = null)
{
    $data = [];
    $condition = "";
    $types = "sss"; // default types (companyid, subcompanyid, projectid)
    $params = [$companyid, $subcompanyid, $projectid];

    // Apply filters
    if ($shortby == "allactive") {
        $condition = " AND lm.Status = 'Active'";
    }else if ($shortby == "allgraftedwithlocationid") {
        $condition = " AND lm.Status = 'Active' AND lcase(lm.graftingskill)='yes' AND lm.locationid = ?";
        $types .= "s";  // EmployeeID is integer
        $params[] = $shortbyvalue;
    } elseif ($shortby == "allresigned") {
        $condition = " AND lm.Status = 'Resigned'";
    } elseif ($shortby == "EmployeeID" || $shortby == "LabourID") {
        $condition = " AND lm.EmployeeID = ?";
        $types .= "i";  // EmployeeID is integer
        $params[] = $shortbyvalue;
    } elseif ($shortby == "allactivebutperdaypaymentmissing") {
        $condition = " AND lm.Status = 'Active' AND (lpm.PerDayPayment IS NULL OR lpm.PerDayPayment <= 0)";
    }
    // $conn->query("SET NAMES 'utf8'");
    $sql = "SELECT 
                lm.EmployeeID, 
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
                lm.graftingskill, 
                lm.locationid, 
                lm.DesignationID, 
                lm.DepartmentID, 
                lm.Status, 
                lm.remark, 
                lm.paytoid,
                IFNULL(lpm.PerDayPayment, 0) AS PerDayPayment
            FROM hrm_LabourMaster lm
            LEFT JOIN hrm_LabourPaymentMaster lpm 
                ON lm.companyid = lpm.companyid 
               AND lm.subcompanyid = lpm.subcompanyid 
               AND lm.projectid = lpm.projectid 
               AND lm.EmployeeID = lpm.EmployeeID
            WHERE lm.companyid = ? 
              AND lm.subcompanyid = ? 
              AND lm.projectid = ? 
              $condition";

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

function fetchLabourDataForGrafting($conn, $companyid, $subcompanyid, $projectid, $locationid)
{
    $data = [];
    $condition = "";
    $types = "ssss"; // default types (companyid, subcompanyid, projectid)
    $params = [$companyid, $subcompanyid, $projectid, $locationid];
    $sql = "SELECT 
                lm.EmployeeID, 
                lm.TitleName, 
                IFNULL(lm.FirstName, '') AS FirstName, 
                IFNULL(lm.MiddleName, '') AS MiddleName, 
                IFNULL(lm.LastName, '') AS LastName, 
                CONCAT_WS(' ', IFNULL(lm.TitleName, ''), IFNULL(lm.FirstName, ''), IFNULL(lm.MiddleName, ''), IFNULL(lm.LastName, '')) AS FullName, 
                lm.Gender, 
                lm.Email, 
                lm.MobileNo, 
                lm.graftingskill, 
                lm.locationid, 
                lm.DesignationID, 
                lm.DepartmentID, 
                IFNULL(lpm.PerDayPayment, 0) AS PerDayPayment
            FROM hrm_LabourMaster lm
            LEFT JOIN hrm_LabourPaymentMaster lpm 
                ON lm.companyid = lpm.companyid 
               AND lm.subcompanyid = lpm.subcompanyid 
               AND lm.projectid = lpm.projectid 
               AND lm.EmployeeID = lpm.EmployeeID
            WHERE  lm.Status = 'Active' AND lcase(lm.graftingskill)='yes' AND lm.companyid = ? 
              AND lm.subcompanyid = ? 
              AND lm.projectid = ? 
              AND lm.locationid = ?";

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

 function fetchLabourDataForGrafting_ajay($conn, $companyid, $subcompanyid, $projectid, $locationid,$attend_date)
{
    $data = [];
    $condition = "";
    $types = "sssss"; // default types (companyid, subcompanyid, projectid)
    $params = [$locationid,$attend_date,$companyid, $subcompanyid, $projectid ];
    $sql = "SELECT  att.labourlocationid,att.attendancedate,
                lm.EmployeeID, 
                lm.TitleName, 
                IFNULL(lm.FirstName, '') AS FirstName, 
                IFNULL(lm.MiddleName, '') AS MiddleName, 
                IFNULL(lm.LastName, '') AS LastName, 
                CONCAT_WS(' ', IFNULL(lm.TitleName, ''), IFNULL(lm.FirstName, ''), IFNULL(lm.MiddleName, ''), IFNULL(lm.LastName, '')) AS FullName, 
                lm.Gender, 
                lm.Email, 
                lm.MobileNo, 
                lm.graftingskill, 
                lm.locationid, 
                lm.DesignationID, 
                lm.DepartmentID 
            FROM hrm_LabourMaster lm
              JOIN hrm_labourattendance att
                ON   att.projectid = lm.projectid 
               AND att.labourid = lm.EmployeeID
                WHERE att.labourlocationid =?
               and att.attendancedate =?
               AND lcase(lm.graftingskill)='yes' 
               AND lm.companyid = ? 
              AND lm.subcompanyid = ? 
              AND lm.projectid = ?  ";
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
 
 

function getProductionLocationMaster($conn, $companyid, $subcompanyid, $projectid, $shortby = "all", $shortbyvalue = "all") {
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


function debugQuery_lbf($sql, $params, $types) {
    $output = $sql;
    $i = 0;

    foreach (str_split($types) as $type) {
        if (!isset($params[$i])) {
            continue;
        }

        $val = $params[$i];

        // Format values based on type
        if ($type === 'i') {
            $replacement = (int)$val;
        } elseif ($type === 'd') {
            $replacement = (float)$val;
        } else {
            $replacement = "'" . addslashes($val) . "'";
        }

        // Replace first occurrence of "?" with value
        $output = preg_replace('/\?/', $replacement, $output, 1);

        $i++;
    }

    return $output;
}
?>