<?php
function getClientDetailsInfo($conn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue) {
    // Convert CSV to array and prepare placeholders
    $subcompanyArr = array_map('trim', explode(',', $subcompanyid));
    $projectArr = array_map('trim', explode(',', $projectid));

    $subPlaceholders = implode(',', array_fill(0, count($subcompanyArr), '?'));
    $projPlaceholders = implode(',', array_fill(0, count($projectArr), '?'));

    $params = array_merge([$companyid], $subcompanyArr, $projectArr);
    $types = str_repeat('s', 1 + count($subcompanyArr) + count($projectArr));

    $query = "
        SELECT * FROM krf_client_master WHERE (tedit IS NULL OR tedit = '') 
          AND companyid = ? 
          AND subcompanyid IN ($subPlaceholders) 
          AND projectid IN ($projPlaceholders)
    ";

    // Additional filter
    if ($shortby === "intermediateid") {
        $query .= " AND client_name = ?";
        $params[] = $shortbyvalue;
        $types .= "s";
    } else if ($shortby === "mobileno" || $shortby === "phoneno") {
        $query .= " AND phones LIKE ?";
        $params[] = "%" . $shortbyvalue . "%";
        $types .= "s";
    } elseif ($shortby === "chkclientname" || $shortby === "clientname") {
        $query .= " AND LCASE(TRIM(client_name)) = ?";
        $params[] = strtolower($shortbyvalue);
        $types .= "s";
    } elseif ($shortby === "searchclientname") {
        $query .= " AND client_name LIKE ?";
        $params[] = "%" . $shortbyvalue . "%";
        $types .= "s";
    } elseif ($shortby === "clientid") {
        $query .= " AND client_id = ?";
        $params[] = strtolower($shortbyvalue);
        $types .= "s";
    }
    // "allwithprojectid" requires no extra condition.

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return []; // or handle error
    }

    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $clients = [];
    while ($row = $result->fetch_assoc()) {
        $clients[] = $row;
    }

    $stmt->close();
    return $clients;
}

function saveClient($krfconn,$companyid,$subcompanyid,$projectid,$client_id,$client_name,$invoicing_address,$phone,$email,$districtname,$statename,$isUpdate = false,$cityname = '',$stateid = '',$districtid = '',$tahseel = '') {
    try {
        if (isset($krfconn) && $krfconn) {
            @$krfconn->set_charset("utf8mb4");
        }
        // Resolve stateid and districtid if empty
        if (empty($stateid) && !empty($statename)) {
            $stQ = $krfconn->prepare("SELECT stateid, statename FROM master_states WHERE LOWER(TRIM(statename)) = LOWER(TRIM(?)) LIMIT 1");
            if ($stQ) {
                $stQ->bind_param("s", $statename);
                $stQ->execute();
                $stRes = $stQ->get_result()->fetch_assoc();
                if ($stRes) {
                    $stateid = $stRes['stateid'];
                    $statename = $stRes['statename'];
                }
                $stQ->close();
            }
        }
        
        if (empty($districtid) && !empty($districtname)) {
            if (!empty($stateid)) {
                $dtQ = $krfconn->prepare("SELECT districtid, districtname, stateid FROM master_districts WHERE stateid = ? AND LOWER(TRIM(districtname)) = LOWER(TRIM(?)) LIMIT 1");
                if ($dtQ) {
                    $dtQ->bind_param("ss", $stateid, $districtname);
                    $dtQ->execute();
                    $dtRes = $dtQ->get_result()->fetch_assoc();
                    if ($dtRes) {
                        $districtid = $dtRes['districtid'];
                        $districtname = $dtRes['districtname'];
                    }
                    $dtQ->close();
                }
            }
            if (empty($districtid)) {
                $dtQ = $krfconn->prepare("SELECT districtid, districtname, stateid FROM master_districts WHERE LOWER(TRIM(districtname)) = LOWER(TRIM(?)) LIMIT 1");
                if ($dtQ) {
                    $dtQ->bind_param("s", $districtname);
                    $dtQ->execute();
                    $dtRes = $dtQ->get_result()->fetch_assoc();
                    if ($dtRes) {
                        $districtid = $dtRes['districtid'];
                        $districtname = $dtRes['districtname'];
                        if (empty($stateid)) {
                            $stateid = $dtRes['stateid'];
                        }
                    }
                    $dtQ->close();
                }
            }
        }

        if (empty($statename) && !empty($stateid)) {
            $stQ = $krfconn->prepare("SELECT statename FROM master_states WHERE stateid = ? LIMIT 1");
            if ($stQ) {
                $stQ->bind_param("s", $stateid);
                $stQ->execute();
                $stRes = $stQ->get_result()->fetch_assoc();
                if ($stRes) {
                    $statename = $stRes['statename'];
                }
                $stQ->close();
            }
        }

        // 🔍 Duplicate check for client_name
        if (!$isUpdate) {
            $checkSql = "SELECT COUNT(*) AS cnt 
                         FROM krf_client_master 
                         WHERE companyid=? AND subcompanyid=? AND projectid=? 
                           AND client_name=? AND phones=? AND IFNULL(tedit,'')=''";
            $checkStmt = $krfconn->prepare($checkSql);
            if (!$checkStmt) {
                throw new Exception("Prepare failed: " . $krfconn->error);
            }
            $checkStmt->bind_param("sssss", $companyid, $subcompanyid, $projectid, $client_name, $phone);
            $checkStmt->execute();
            $res = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            if ($res['cnt'] > 0) {
                return ["status"=>"Duplicate","message"=>"Client name already exists: ".$client_name];
            }
        }

        if ($isUpdate) {
            $clientExists = false;
            $chkExist = $krfconn->prepare("SELECT COUNT(*) AS cnt FROM krf_client_master WHERE client_id = ?");
            if ($chkExist) {
                $chkExist->bind_param("s", $client_id);
                $chkExist->execute();
                $cRes = $chkExist->get_result()->fetch_assoc();
                if (($cRes['cnt'] ?? 0) > 0) {
                    $clientExists = true;
                }
                $chkExist->close();
            }

            if ($clientExists) {
                // UPDATE query
                $sql = "UPDATE krf_client_master SET 
                            client_name=?, invoicing_address=?, phones=?, client_email=?, districtname=?, statename=?, stateid=?, districtid=?, cityname=?, tahseel=?
                        WHERE client_id=?";
                $stmt = $krfconn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $krfconn->error);
                }
                $stmt->bind_param(
                    "sssssssssss",
                    $client_name, 
                    $invoicing_address, 
                    $phone, 
                    $email, 
                    $districtname, 
                    $statename, 
                    $stateid, 
                    $districtid, 
                    $cityname, 
                    $tahseel,
                    $client_id
                );
            } else {
                // INSERT query with existing client_id
                $sql = "INSERT INTO krf_client_master
                            (companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, districtname, statename, stateid, districtid, cityname, tahseel)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
                $stmt = $krfconn->prepare($sql);
                if (!$stmt) {
                    throw new Exception("Prepare failed: " . $krfconn->error);
                }
                $stmt->bind_param(
                    "ssssssssssssss",
                    $companyid, 
                    $subcompanyid, 
                    $projectid, 
                    $client_id, 
                    $client_name, 
                    $invoicing_address, 
                    $phone, 
                    $email, 
                    $districtname, 
                    $statename, 
                    $stateid, 
                    $districtid, 
                    $cityname, 
                    $tahseel
                );
            }
        } else {
            // Generate new client_id
            $getid = "SELECT IFNULL(MAX(CAST(SUBSTRING(client_id,2) AS UNSIGNED)),0)+1 AS client_id 
                      FROM krf_client_master WHERE IFNULL(client_id,'') != ''";
            $resq = $krfconn->query($getid);
            if (!$resq) {
                throw new Exception("ID generation failed: " . $krfconn->error);
            }
            $row = $resq->fetch_assoc();
            $next_id = $row['client_id'];
            $client_id = 'C'.str_pad($next_id,5,'0',STR_PAD_LEFT);

            // INSERT query
            $sql = "INSERT INTO krf_client_master
                        (companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email, districtname, statename, stateid, districtid, cityname, tahseel)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $krfconn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $krfconn->error);
            }
            $stmt->bind_param(
                "ssssssssssssss",
                $companyid, 
                $subcompanyid, 
                $projectid, 
                $client_id, 
                $client_name, 
                $invoicing_address, 
                $phone, 
                $email, 
                $districtname, 
                $statename, 
                $stateid, 
                $districtid, 
                $cityname, 
                $tahseel
            );
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            return ["status"=>"error","message"=>$error];
        }

        $stmt->close();

        return [
            "status"   => "success",
            "client_id"=> $client_id,
            "message"  => $isUpdate ? "Client updated successfully" : "Client inserted successfully"
        ];

    } catch (Exception $e) {
        return ["status"=>"error","message"=>$e->getMessage()];
    }
}

function saveMultipleDeliveryLocations($conn, $rows)
{
    $responses = [];

    try {
        if (isset($conn) && $conn) {
            @$conn->set_charset("utf8mb4");
        }
        foreach ($rows as $data) {
            $id = $data['id'] ?? null;
            $projectid = $data['projectid'] ?? null;
            $psono = $data['psono'] ?? null;
            $sono = $data['sono'] ?? null;
            $clientid = $data['clientid'] ?? null;
            $nooftray = $data['nooftray'] ?? null;
            $productid = $data['addressforproduct'] ?? '';
            $deliveryqty = $data['delivery_qty'] ?? 0;
            $deliverycharges = $data['deliverycharges'] ?? 0.00;
            $deliveryaddress = $data['deliveryaddress'] ?? ''; 
            $dest_lat = $data['dest_latitude'] ?? null;
            $dest_long = $data['dest_longitude'] ?? null;
            $dest_state = $data['dest_state'] ?? null;
            $dest_district = $data['dest_district'] ?? null;
            $dest_city = $data['dest_city'] ?? null;
            $dest_pincode = $data['dest_pincode'] ?? null;
            
            $gpslocation = $data['gpslocation'] ?? null;
            $vehicleno = $data['vehicleno'] ?? null;
            $drivername = $data['drivername'] ?? null;
            $totaldistance = isset($data['totaldistance']) && is_numeric($data['totaldistance']) ? (float)$data['totaldistance'] : (float)($data['distance'] ?? 0);
            $freedistance = isset($data['freedistance']) && is_numeric($data['freedistance']) ? (float)$data['freedistance'] : 0.0;
            $distance = isset($data['distance']) && is_numeric($data['distance']) ? (float)$data['distance'] : max(0.0, (float)$totaldistance - (float)$freedistance);
            $remark = $data['remark'] ?? '';
            $drivercontactno = $data['drivercontactno'] ?? null;
            $status = $data['status'] ?? 'Pending';
            $created_by = $data['created_by'] ?? null;
            $pickuppoint= $data['pickuppoint'] ?? null;
            $pickuptype= $data['pickuptype'] ?? null;
            $noofbox= $data['noofbox'] ?? null;
            $total_freight= $data['boxtotalamt'] ?? null;
            $dest_stateid= $data['dest_stateid'] ?? null;
            $dest_districtid= $data['dest_districtid'] ?? null;

            $dest_cityid= $data['dest_cityid'] ?? null;
            $boxperpack= $data['boxperpack'] ?? null;
            $chargesperbox= $data['boxchargesperpack'] ?? null;
            if ($id!="") {
                // UPDATE existing record
                $sql = "UPDATE krf_deliveryaddress_location 
                        SET projectid=?, psono=?, sono=?, clientid=?, nooftray=?, deliverycharges=?, 
                            deliveryaddress=?, productid=?, vehicleno=?, drivername=?, drivercontactno=?, 
                            status=?, distance=?, deliveryqty=?, dest_latitude=?, dest_longitude=?, 
                            dest_state=?, dest_district=?, dest_city=?, dest_pincode=?, updated_at=NOW() ,
                            pickuppoint=?, pickuptype=?, noofbox= ? ,freightamt =?
                            ,stateid =?,districtid =?, cityid =?,boxperpack =?,chargesperbox =?,
                            totaldistance=?, freedistance=?, remark=?

                        WHERE id=?";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "issssisssssssddsssssssissssssddsi",
                    $projectid, $psono, $sono, $clientid, $nooftray, $deliverycharges,
                    $deliveryaddress, $productid, $vehicleno, $drivername, $drivercontactno,
                    $status, $distance, $deliveryqty, $dest_lat, $dest_long, 
                    $dest_state, $dest_district, $dest_city, $dest_pincode, $pickuppoint, $pickuptype, $noofbox, $total_freight,$dest_stateid,$dest_districtid,$dest_cityid,$boxperpack,$chargesperbox,
                    $totaldistance, $freedistance, $remark, $id
                );

                $stmt->execute();
                $responses[] = [
                    "id" => $id,
                    "action" => "update",
                    "affected_rows" => $stmt->affected_rows
                ];
                $stmt->close();
            } else {
                // INSERT new record
                $sql = "INSERT INTO krf_deliveryaddress_location 
                        (projectid, psono, sono, clientid, nooftray, deliverycharges, 
                         deliveryaddress, productid, vehicleno, drivername, drivercontactno, 
                         status, created_by, distance, deliveryqty, dest_latitude, dest_longitude, 
                         dest_state, dest_district, dest_city, dest_pincode,pickuppoint, pickuptype,noofbox,freightamt,stateid,districtid, cityid,boxperpack,chargesperbox,
                         totaldistance, freedistance, remark)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,?,?,?,?,?,?,?,?,?,?,?,?)";
                
                $stmt = $conn->prepare($sql);
                $stmt->bind_param(
                    "issssissssssssddssssssssssssssdds",
                    $projectid, $psono, $sono, $clientid, $nooftray, $deliverycharges,
                    $deliveryaddress, $productid, $vehicleno, $drivername, $drivercontactno,
                    $status, $created_by, $distance, $deliveryqty, $dest_lat, $dest_long,
                    $dest_state, $dest_district, $dest_city, $dest_pincode,$pickuppoint,$pickuptype, $noofbox ,$total_freight,$dest_stateid,$dest_districtid,$dest_cityid,$boxperpack,$chargesperbox,
                    $totaldistance, $freedistance, $remark
                );

                $stmt->execute();
                $responses[] = [
                    "insert_id" => $stmt->insert_id,
                    "action" => "insert"
                ];
                $stmt->close();
            }
        }

        return [
            "status" => "success",
            "message" => "Batch insert/update completed successfully.",
            "results" => $responses
        ];

    } catch (Exception $e) {
        return [
            "status" => "error",
            "message" => "Transaction failed: " . $e->getMessage()
        ];
    }
}

function getDeliveryLocations($conn, $projectid, $clientid = null, $shortby = null, $shortbyvalue = 'ASC')
{
    try {
        $sql = "SELECT * FROM krf_deliveryaddress_location WHERE (tedit IS NULL OR tedit='') AND projectid = ? ";
        $params = [$projectid];
        $types = "i";

        // Filter by clientid if provided
        if (!empty($clientid)) {
            $sql .= " AND clientid = ?";
            $params[] = $clientid;
            $types .= "s";
        }

        // Apply sorting if $shortby is provided
        $allowedSortColumns = ['psono', 'sono', 'drivername', 'created_at', 'updated_at', 'nooftray', 'deliverycharges'];
        $shortby = in_array($shortby, $allowedSortColumns) ? $shortby : null;
        $shortbyvalue = strtoupper($shortbyvalue) === 'DESC' ? 'DESC' : 'ASC';

        if ($shortby) {
            $sql .= " ORDER BY $shortby $shortbyvalue";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
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

    } catch (Exception $e) {
        // On error, return empty array
        return [];
    }
}

function getUniqueDeliveryAddresses(
    $conn,
    $projectid,
    $clientid = null,
    $shortby = null,
    $shortbyvalue = 'ASC'
) {
    try {

        $sql = "SELECT Min(id) AS id, projectid, clientid, deliveryaddress, dest_latitude, dest_longitude, dest_state, dest_district, dest_city, dest_pincode, distance ,stateid,districtid FROM krf_deliveryaddress_location WHERE (tedit IS NULL OR tedit = '') and pickuptype ='delivered'";

        $params = [];
        $types  = "";

        // ✅ Client filter (SAFE)
        if ($clientid !== null && $clientid !== '') {
            $sql .= " AND TRIM(clientid) = ?";
            $params[] = trim((string)$clientid);
            $types   .= "s";
        }

        // UNIQUE address
        $sql .= " GROUP BY deliveryaddress";

        // Sorting
        $allowedSortColumns = ['clientid', 'deliveryaddress', 'gpslocation'];
        $shortby = in_array($shortby, $allowedSortColumns) ? $shortby : null;
        $shortbyvalue = strtoupper($shortbyvalue) === 'DESC' ? 'DESC' : 'ASC';

        if ($shortby) {
            $sql .= " ORDER BY $shortby $shortbyvalue";
        } else {
            $sql .= " ORDER BY id DESC";
        }

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
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

    } catch (Exception $e) {
        // Optional: log error
        // error_log($e->getMessage());
        return [];
    }
}

function debugQueryclient($sql, $params, $types) {
    $output = $sql;
    $i = 0;
    foreach (str_split($types) as $type) {
        if (!isset($params[$i])) {
            continue;
        }
        $val = $params[$i];
        if ($type === 'i') {
            $replacement = (int)$val;
        } elseif ($type === 'd') {
            $replacement = (float)$val;
        } else {
            $replacement = "'" . addslashes($val) . "'";
        }
        $output = preg_replace('/\?/', $replacement, $output, 1);
        $i++;
    }
   return $output;
}

function getAllStatesMaster($conn) {
    $sql = "SELECT stateid, statename FROM master_states WHERE (status = 'Active' OR IFNULL(status, '') = '') ORDER BY statename ASC";
    $result = $conn->query($sql);
    $states = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $states[] = [
                'stateid' => $row['stateid'],
                'statename' => $row['statename']
            ];
        }
    }
    return $states;
}

function getDistrictsByStateMaster($conn, $state) {
    $state = trim((string)$state);
    if ($state === "") return [];

    $stateid = $state;
    // If state name is passed, find stateid
    $stmtFind = $conn->prepare("SELECT stateid FROM master_states WHERE LOWER(TRIM(statename)) = LOWER(TRIM(?)) LIMIT 1");
    if ($stmtFind) {
        $stmtFind->bind_param("s", $state);
        $stmtFind->execute();
        $res = $stmtFind->get_result()->fetch_assoc();
        if ($res && !empty($res['stateid'])) {
            $stateid = $res['stateid'];
        }
        $stmtFind->close();
    }

    $stmt = $conn->prepare("SELECT districtid, districtname, stateid FROM master_districts WHERE (stateid = ? OR LOWER(TRIM(stateid)) = LOWER(TRIM(?))) AND (status = 'Active' OR IFNULL(status, '') = '') ORDER BY districtname ASC");
    if ($stmt) {
        $stmt->bind_param("ss", $stateid, $state);
        $stmt->execute();
        $result = $stmt->get_result();
        $districts = [];
        while ($row = $result->fetch_assoc()) {
            $districts[] = [
                'districtid' => $row['districtid'],
                'districtname' => $row['districtname'],
                'stateid' => $row['stateid']
            ];
        }
        $stmt->close();
        return $districts;
    }
    return [];
}

function getStateByDistrictMaster($conn, $district) {
    $district = trim((string)$district);
    if ($district === "") return null;
    $stmt = $conn->prepare("
        SELECT d.districtid, d.districtname, d.stateid, s.statename 
        FROM master_districts d 
        JOIN master_states s ON d.stateid = s.stateid 
        WHERE (LOWER(TRIM(d.districtname)) = LOWER(TRIM(?)) OR d.districtid = ?) 
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("ss", $district, $district);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $res;
    }
    return null;
}

?>