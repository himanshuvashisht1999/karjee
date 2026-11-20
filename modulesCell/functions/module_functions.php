<?php
function saveOrUpdateClientGroup($conn, $data)
{
    $data['groupid'] = $data['groupid'] ?? 0;

    $fields = ['groupid', 'groupname', 'companyid', 'subcompanyid', 'projectid'];
    foreach ($fields as $field) {
        $data[$field] = $data[$field] ?? '';
    }

    $isUpdate = false;
    $existingGroupId = null;
    $logReason = '';

    // 1. Check by groupid if provided
    if (!empty($data['groupid'])) {
        $checkById = "
            SELECT groupid 
            FROM login_group_master_client 
            WHERE groupid = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?
        ";
        $stmt = $conn->prepare($checkById);
        if ($stmt) {
            $stmt->bind_param("ssss", $data['groupid'], $data['companyid'], $data['subcompanyid'], $data['projectid']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $isUpdate = true;
                $existingGroupId = $data['groupid'];
                $logReason = 'Update by groupid with company, subcompany, project filter';
            }
            $stmt->close();
        }
    }

    if ($isUpdate) {
        // UPDATE existing group
        $sql = "
            UPDATE login_group_master_client 
            SET groupname = ? 
            WHERE groupid = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            return ['status' => 'error', 'message' => 'Prepare (update) failed: ' . $conn->error];

        $stmt->bind_param(
            "sssss",
            $data['groupname'],
            $existingGroupId,
            $data['companyid'],
            $data['subcompanyid'],
            $data['projectid']
        );
    } else {
        // INSERT new group
        $groupid = NewGroupId($conn);
        if (!$groupid)
            return ['status' => 'error', 'message' => 'Failed to generate group ID'];

        $sql = "
            INSERT INTO login_group_master_client (
                groupid, groupname, companyid, subcompanyid, projectid, status
            ) VALUES (?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            return ['status' => 'error', 'message' => 'Prepare (insert) failed: ' . $conn->error];
        $status = 'active';
        $stmt->bind_param(
            "ssssss",
            $groupid,
            $data['groupname'],
            $data['companyid'],
            $data['subcompanyid'],
            $data['projectid'],
            $status
        );
        $logReason = 'Inserted new group';
    }

    if ($stmt->execute()) {
        $stmt->close();
        return [
            'status' => 'success',
            'message' => $isUpdate ? 'Updated successfully' : 'Inserted successfully',
            'groupid' => $existingGroupId ?? $groupid,
            'action_reason' => $logReason
        ];
    } else {
        $errorMsg = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => 'Execution failed: ' . $errorMsg];
    }
}


function saveOrUpdateClientModule($conn, $data)
{
    $data['groupid'] = $data['groupid'] ?? 0;

    $fields = [
        'groupid',
        'moduleid',
        'modulename',
        'modulefoldername',
        'ispasswordprotected',
        'integrationtype',
        'status',
        'companyid',
        'subcompanyid',
        'projectid'
    ];

    foreach ($fields as $field) {
        $data[$field] = $data[$field] ?? null;
    }

    $isUpdate = false;
    $existingModuleId = null;
    $logReason = '';

    // 1. Check by moduleid if given
    if (!empty($data['moduleid'])) {
        $checkById = "SELECT moduleid FROM login_modules_master_client 
                      WHERE moduleid = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $conn->prepare($checkById);
        if ($stmt) {
            $stmt->bind_param("ssss", $data['moduleid'], $data['companyid'], $data['subcompanyid'], $data['projectid']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $isUpdate = true;
                $existingModuleId = $data['moduleid'];
                $logReason = 'Update by moduleid';
            }
            $stmt->close();
        }
    }

    // 2. If not found by moduleid, check by modulefoldername
    if (!$isUpdate && !empty($data['modulefoldername'])) {
        $checkByFolder = "SELECT moduleid FROM login_modules_master_client 
                          WHERE modulefoldername = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?";
        $stmt = $conn->prepare($checkByFolder);
        if ($stmt) {
            $stmt->bind_param("ssss", $data['modulefoldername'], $data['companyid'], $data['subcompanyid'], $data['projectid']);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($existingModuleId);
                $stmt->fetch();
                $isUpdate = true;
                $logReason = 'Update by modulefoldername';
            }
            $stmt->close();
        }
    }

    if ($isUpdate) {
        // UPDATE
        $sql = "
            UPDATE login_modules_master_client SET
                groupid = ?, modulename = ?, modulefoldername = ?, purposetext = ?,
                ispasswordprotected = ?, integrationtype = ?, status = ?, modulealign = ?, modulesize= ?
            WHERE moduleid = ? AND companyid = ? AND subcompanyid = ? AND projectid = ?
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            return ['status' => 'error', 'message' => 'Prepare (update) failed: ' . $conn->error];

        $stmt->bind_param(
            "isssssssssiii",
            $data['groupid'],
            $data['modulename'],
            $data['modulefoldername'],
            $data['purposetext'],
            $data['ispasswordprotected'],
            $data['integrationtype'],
            $data['status'],
            $data['modulealign'],
            $data['modulesize'],
            $existingModuleId,
            $data['companyid'],
            $data['subcompanyid'],
            $data['projectid']
        );
    } else {
        // INSERT
        $moduleId = NewModuleId($conn);
        if (!$moduleId)
            return ['status' => 'error', 'message' => 'Failed to generate module ID'];

        $sql = "
            INSERT INTO login_modules_master_client (groupid, moduleid, modulename, modulefoldername, purposetext, ispasswordprotected, integrationtype, status, modulealign, modulesize, companyid, subcompanyid, projectid) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt)
            return ['status' => 'error', 'message' => 'Prepare (insert) failed: ' . $conn->error];

        $stmt->bind_param(
            "isssssssssiii",
            $data['groupid'],
            $moduleId,
            $data['modulename'],
            $data['modulefoldername'],
            $data['purposetext'],
            $data['ispasswordprotected'],
            $data['integrationtype'],
            $data['status'],
            $data['modulealign'],
            $data['modulesize'],
            $data['companyid'],
            $data['subcompanyid'],
            $data['projectid']
        );
        $logReason = 'Inserted new module';
    }

    if ($stmt->execute()) {
        $stmt->close();
        return [
            'status' => 'success',
            'message' => $isUpdate ? 'Updated successfully' : 'Inserted successfully',
            'moduleid' => $existingModuleId ?? $moduleId,
            'action_reason' => $logReason
        ];
    } else {
        return ['status' => 'error', 'message' => 'Execution failed: ' . $stmt->error];
    }
}


function getAllClientGroups($conn, $companyid, $subcompanyid, $projectid, $shortby = null, $shortbyvalue = null)
{
    $params = [$companyid, $subcompanyid, $projectid];
    $types = "sss";
    $whereExtra = "";
    // echo $shortby."===".$shortbyvalue;

    // Add additional filters
    if ($shortby === "groupid" && !empty($shortbyvalue)) {
        $whereExtra = " AND groupid = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    } elseif ($shortby === "sno" && !empty($shortbyvalue)) {
        $whereExtra = " AND sno = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    } elseif ($shortby === "status" && in_array($shortbyvalue, ['active', 'deactive'])) {
        $whereExtra = " AND status = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    }

    $sql = "SELECT * FROM login_group_master_client 
            WHERE companyid = ? AND subcompanyid = ? AND projectid = ? $whereExtra
            ORDER BY vieworderby ASC";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error];
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

function getAllClientModules($conn, $companyid, $subcompanyid, $projectid, $shortby = null, $shortbyvalue = null)
{
    $params = [$companyid, $subcompanyid, $projectid];
    $types = "sss";
    $whereExtra = "";

    // Handle filtering
    if ($shortby === "groupid" && !empty($shortbyvalue)) {
        $whereExtra = " AND m.status = 'active' AND m.groupid = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    } elseif ($shortby === "moduleid" && !empty($shortbyvalue)) {
        $whereExtra = " AND m.status = 'active' AND m.moduleid = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    } elseif ($shortby === "status" && in_array($shortbyvalue, ['active', 'deactive'])) {
        $whereExtra = " AND m.status = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    } elseif ($shortby === "sno" && !empty($shortbyvalue)) {
        $whereExtra = " AND m.sno = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    } elseif ($shortby === "groupbygroupid") {
        // No extra param here, just group by
        $whereExtra = " AND m.status = 'active' GROUP BY m.groupid";
    }

    $sql = "SELECT m.* FROM login_modules_master_client m
            LEFT JOIN login_group_master_client g ON m.groupid = g.groupid
            WHERE m.companyid = ? 
              AND m.subcompanyid = ? 
              AND m.projectid = ?
              $whereExtra
            ORDER BY g.vieworderby, m.vieworderby";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return ['status' => 'error', 'message' => 'Prepare failed: ' . $conn->error];
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


function getprojectdetailsmodules($conn, $companyid, $subcompanyid, $shortby, $shortbyvalue)
{
    $ssbq = "";
    if ($shortby == "all") {
        $ssbq = "select companyid, subcompanyid, projectid,projectname, ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid, ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and (tedit is null or tedit='')";
    } else if ($shortby == "subcompanyid") {
        $ssbq = "select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'4') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and (tedit is null or tedit='')";
    } else if ($shortby == "projectid") {
        $ssbq = "select companyid, subcompanyid, projectid,projectname , ifnull(finyr,'4') as finyr, ifnull(taxtype,'') as  taxtype , ifnull(invformat,'') as invformat, ifnull(proformaformat,'') as  proformaformat ,ifnull(currencyid,'') as  currencyid , ifnull(creditnoteformat,'') as  creditformat,ifnull(project_email,'') as project_email FROM tbl_project_master WHERE companyid='" . $companyid . "' and subcompanyid='" . $subcompanyid . "' and projectid in ('" . str_replace(',', '","', $shortbyvalue) . "') and (tedit is null or tedit='')";
    }
    $sblist = array();
    if ($ssbq) {
        $ssbq_result = $conn->query($ssbq) or die($conn->error);
        if ($ssbq_result->num_rows > 0) {
            while ($sbrow = $ssbq_result->fetch_assoc()) {
                $sblist[] = array('companyid' => $sbrow['companyid'], 'subcompanyid' => $sbrow['subcompanyid'], 'projectid' => $sbrow['projectid'], 'projectname' => $sbrow['projectname'], 'finyr' => $sbrow['finyr'], 'taxtype' => $sbrow['taxtype'], 'invformat' => $sbrow['invformat'], 'proformaformat' => $sbrow['proformaformat'], 'currencyid' => $sbrow['currencyid'], 'creditformat' => $sbrow['creditformat'], 'emailid' => $sbrow['project_email']);
            }
        }
    }
    return $sblist;
}

function loginuserinfoFromModule($conn, $loginroleid, $shortby, $shortbyvalue)
{
    if ($shortby == "all") {
        $lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and isActive='yes'  and loginroleid>'" . $loginroleid . "'");
    } else if ($shortby == "loginid") {
        $lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and loginid='" . $shortbyvalue . "'");
    } else if ($shortby == "mobileno") {
        $lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments  FROM login_user_master WHERE (tedit is null or tedit='') and mobileno='" . $shortbyvalue . "'");
    } else if ($shortby == "sno") {
        $lguq = $conn->query("SELECT sno, loginname, loginid, loginpassword, loginroleid, loginrolename, mobileno, emailid, isActive, isLogined, loginDatetime,comments FROM login_user_master WHERE (tedit is null or tedit='') and sno='" . $shortbyvalue . "'");
    }
    $ullist = array();
    if ($lguq->num_rows > 0) {
        while ($lurow = $lguq->fetch_assoc()) {
            $ullist[] = array('loginname' => $lurow['loginname'], 'loginid' => $lurow['loginid'], 'loginpassword' => $lurow['loginpassword'], 'loginroleid' => $lurow['loginroleid'], 'loginrolename' => $lurow['loginrolename'], 'mobileno' => ($lurow['mobileno'] ? $lurow['mobileno'] : "N/A"), 'emailid' => ($lurow['emailid'] ? $lurow['emailid'] : "N/A"), 'isactive' => ($lurow['isActive'] ? $lurow['isActive'] : "Active"), 'islogined' => ($lurow['isLogined'] ? ucwords($lurow['isLogined']) : "Logout"), 'logindatetime' => $lurow['loginDatetime'], 'comments' => $lurow['comments']);
        }
    }
    return $ullist;
}


function NewModuleId($conn)
{
    $maxRetries = 3;
    $retryCount = 0;
    while ($retryCount < $maxRetries) {
        // Generate a random numeric ID (6 to 9 digits)
        $moduleId = random_int(100000, 999999999); // Adjust range as needed

        // Check if it exists
        $sql = "SELECT 1 FROM login_modules_master_client WHERE moduleid = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("DB prepare failed: " . $conn->error);
            return null;
        }

        $stmt->bind_param("i", $moduleId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $moduleId; // Unique numeric moduleid found
        }

        $stmt->close();
        $retryCount++;
    }
    return null; // Failed after retries
}

function NewGroupId($conn)
{
    $maxRetries = 3;
    $retryCount = 0;
    while ($retryCount < $maxRetries) {
        // Generate a random numeric ID (6 to 9 digits)
        $groupid = random_int(100000, 999999999); // Adjust range as needed

        // Check if it exists
        $sql = "SELECT 1 FROM login_group_master_client WHERE groupid = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log("DB prepare failed: " . $conn->error);
            return null;
        }

        $stmt->bind_param("i", $groupid);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            return $groupid; // Unique numeric moduleid found
        }

        $stmt->close();
        $retryCount++;
    }
    return null; // Failed after retries
}


function base64url_encode_mdl($data)
{
    $b64 = base64_encode($data);

    if ($b64 === false) {
        return false;
    }
    $url = strtr($b64, '+/', '-_');
    return rtrim($url, '=');
}

function base64url_decode_mdl($data, $strict = false)
{
    $b64 = strtr($data, '-_', '+/');
    return base64_decode($b64, $strict);
}

function checkModuleLoginStatusWithmodule()
{
    // Start the session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if the user is logged in
    if (isset($_SESSION['modulesessionid'])) {
        return "loginid -" . $_SESSION['modulesessionid'];
    } else {
        return "logout";
    }
}
?>