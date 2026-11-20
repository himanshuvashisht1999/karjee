<?php
function getTrayCategoryData($krfconn)
{
    try {
        $sql = "SELECT * FROM krf_tray_category where lcase(status)='active' ORDER BY category_name ASC";

        $stmt = $krfconn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $trays = [];
        while ($row = $result->fetch_assoc()) {
            $trays[] = $row;
        }
        return $trays;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function getTrayMasterData($krfconn)
{
    try {
        $sql = "SELECT tray_id, tray_code, tray_name, category_id, cavity_count, 
                       cavity_size, material, description, status, created_at, updated_at
                FROM krf_tray_master
                ORDER BY tray_name ASC";

        $stmt = $krfconn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        $trays = [];
        while ($row = $result->fetch_assoc()) {
            $trays[] = $row;
        }
        return $trays;
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

function getTrayData($krfconn, $shortby = null, $shortbyvalue = null)
{
    try {
        $sql = "SELECT 
                    t.tray_id, 
                    t.tray_code, 
                    t.tray_name, 
                    t.cavity_count, 
                    t.cavity_size, 
                    t.material, 
                    t.description, 
                    t.status, 
                    t.created_at, 
                    t.updated_at, 
                    c.category_id, 
                    c.category_code, 
                    c.category_name 
                FROM krf_tray_master t 
                INNER JOIN krf_tray_category c ON t.category_id = c.category_id";

        $params = [];
        $types = "";

        // Apply filter based on shortby
        if ($shortby && $shortbyvalue !== null && $shortbyvalue !== "") {
            switch ($shortby) {
                case "id":
                case "tray_id":
                    $sql .= " WHERE t.tray_id = ?";
                    $types .= "i";
                    $params[] = $shortbyvalue;
                    break;

                case "code":
                case "tray_code":
                    $sql .= " WHERE t.tray_code = ?";
                    $types .= "s";
                    $params[] = $shortbyvalue;
                    break;

                case "category_id":
                    $sql .= " WHERE t.category_id = ?";
                    $types .= "i";
                    $params[] = $shortbyvalue;
                    break;
                default:
                    // no condition for unknown shortby
                    break;
            }
        }

        $stmt = $krfconn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $trays = [];
        while ($row = $result->fetch_assoc()) {
            $trays[] = $row;
        }

        return $trays;

    } catch (Exception $e) {
        return ["error" => $e->getMessage()];
    }
}



function saveTrayMaster($krfconn, $data)
{
    try {
        // Extract and sanitize input
        $tray_id = isset($data['tray_id']) ? intval($data['tray_id']) : 0;
        $tray_code = trim($data['tray_code'] ?? '');
        $tray_name = trim($data['tray_name'] ?? '');
        $category_id = intval($data['category_id'] ?? 0);
        $cavity_count = intval($data['cavity_count'] ?? 0);
        $cavity_size = trim($data['cavity_size'] ?? '');
        $material = trim($data['material'] ?? '');
        $description = trim($data['description'] ?? '');
        $status = $data['status'] ?? 'Active';

        // Validation
        if (empty($tray_code) || empty($tray_name) || $category_id <= 0 || $cavity_count <= 0) {
            return ["status" => "error", "message" => "Required fields are missing."];
        }

        // Duplicate check (tray_code or tray_name must be unique)
        $sqlCheck = "SELECT tray_id, tray_code, tray_name FROM krf_tray_master WHERE (tray_code = ? OR tray_name = ?) AND tray_id != ?";
        $stmtCheck = $krfconn->prepare($sqlCheck);
        $stmtCheck->bind_param("ssi", $tray_code, $tray_name, $tray_id);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();

        if ($resCheck->num_rows > 0) {
            $dupRow = $resCheck->fetch_assoc();
            if ($dupRow['tray_code'] == $tray_code) {
                return ["status" => "error", "message" => "Tray code '$tray_code' already exists."];
            }
            if ($dupRow['tray_name'] == $tray_name) {
                return ["status" => "error", "message" => "Tray name '$tray_name' already exists."];
            }
        }

        // Insert or Update
        if ($tray_id > 0) {
            // Update
            $sql = "UPDATE krf_tray_master 
                    SET tray_code=?, tray_name=?, category_id=?, cavity_count=?, 
                        cavity_size=?, material=?, description=?, status=?, updated_at=NOW()
                    WHERE tray_id=?";
            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param(
                "ssiissssi",
                $tray_code,
                $tray_name,
                $category_id,
                $cavity_count,
                $cavity_size,
                $material,
                $description,
                $status,
                $tray_id
            );
            $stmt->execute();

            return ["status" => "success", "message" => "Tray updated successfully."];
        } else {
            // Insert
            $sql = "INSERT INTO krf_tray_master 
                    (tray_code, tray_name, category_id, cavity_count, cavity_size, material, description, status, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param(
                "ssiissss",
                $tray_code,
                $tray_name,
                $category_id,
                $cavity_count,
                $cavity_size,
                $material,
                $description,
                $status
            );
            $stmt->execute();

            return ["status" => "success", "message" => "Tray inserted successfully.", "tray_id" => $stmt->insert_id];
        }
    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

function updateTrayStatus($krfconn, $tray_id, $status)
{
    try {
        $sql = "UPDATE krf_tray_master SET status = ?, updated_at = NOW() WHERE tray_id = ?";
        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param("si", $status, $tray_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ["status" => "success", "message" => "Tray status updated to $status."];
        } else {
            return ["status" => "error", "message" => "Failed to update tray status or status already $status."];
        }
    } catch (Exception $e) {
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

function deleteTrayPermanent($krfconn, $tray_id)
{
    try {
        $sql = "DELETE FROM krf_tray_master WHERE tray_id = ?";
        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param("i", $tray_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            return ["status" => "success", "message" => "Tray permanently removed from master."];
        } else {
            return ["status" => "error", "message" => "Failed to remove tray. It might have been already deleted."];
        }
    } catch (Exception $e) {
        return ["status" => "error", "message" => "Cannot delete tray: It might be linked to other records."];
    }
}



/**
 * =========================================================================================
 * Tray Movement History Functions (karjeefarm / krf_traymovement_history)
 * Purpose: Insert, Update and Track physical movement of sowing trays across polyhouse
 * =========================================================================================
 */

/**
 * Insert a new movement/activity transaction for a tray
 *
 * @param mysqli $krfconn Database connection
 * @param array $data Associative array of tray movement parameters
 * @return array Status and message or insert ID
 */
function insertTrayMovementHistory($krfconn, $data = [])
{
    try {
        if (!$krfconn || !is_array($data)) {
            return ["status" => "error", "message" => "Invalid connection or data payload."];
        }

        $transaction_no       = $data['transaction_no'] ?? ('TRX-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)));
        $companyid            = isset($data['companyid']) ? intval($data['companyid']) : 1;
        $subcompanyid         = isset($data['subcompanyid']) ? intval($data['subcompanyid']) : 1;
        $projectid            = isset($data['projectid']) ? intval($data['projectid']) : 1;
        $psono                = $data['psono'] ?? null;
        $stockorder_no        = $data['stockorder_no'] ?? '';
        $clientid             = !empty($data['clientid']) ? intval($data['clientid']) : null;
        $clientname           = $data['clientname'] ?? null;
        $productid            = $data['productid'] ?? '';
        $productname          = $data['productname'] ?? '';

        $activity_type        = $data['activity_type'] ?? 'SOWING_INITIATED';
        $sowingplantid        = !empty($data['sowingplantid']) ? intval($data['sowingplantid']) : null;
        $barcode              = $data['barcode'] ?? '';
        $sowingbatchno        = $data['sowingbatchno'] ?? null;
        $sowinglotno_internal = $data['sowinglotno_internal'] ?? null;
        $seedid               = !empty($data['seedid']) ? intval($data['seedid']) : null;
        $seedname             = $data['seedname'] ?? null;
        $seed_lotno           = $data['seed_lotno'] ?? ($data['lotno'] ?? null);
        $trayid               = !empty($data['trayid']) ? intval($data['trayid']) : null;
        $tray_cavity          = isset($data['tray_cavity']) ? intval($data['tray_cavity']) : (isset($data['traycavity']) ? intval($data['traycavity']) : 0);
        $roottype             = !empty($data['roottype']) ? $data['roottype'] : 'Root';

        $from_location_id     = !empty($data['from_location_id']) ? intval($data['from_location_id']) : null;
        $from_polyhouse_id    = !empty($data['from_polyhouse_id']) ? intval($data['from_polyhouse_id']) : null;
        $from_polyhouse_name  = $data['from_polyhouse_name'] ?? null;
        $from_bed_no          = $data['from_bed_no'] ?? null;
        $from_row_no          = isset($data['from_row_no']) ? intval($data['from_row_no']) : 0;
        $from_column_no       = isset($data['from_column_no']) ? intval($data['from_column_no']) : 0;
        $from_rack_no         = $data['from_rack_no'] ?? null;
        $from_floor_no        = $data['from_floor_no'] ?? null;
        $from_box_qrcode      = $data['from_box_qrcode'] ?? null;

        $to_location_id       = !empty($data['to_location_id']) ? intval($data['to_location_id']) : (!empty($data['sowinglocationid']) ? intval($data['sowinglocationid']) : null);
        $to_polyhouse_id      = !empty($data['to_polyhouse_id']) ? intval($data['to_polyhouse_id']) : (!empty($data['polyhouseid']) ? intval($data['polyhouseid']) : null);
        $to_polyhouse_name    = $data['to_polyhouse_name'] ?? null;
        $to_bed_no            = $data['to_bed_no'] ?? ($data['bed_no'] ?? null);
        $to_row_no            = isset($data['to_row_no']) ? intval($data['to_row_no']) : (isset($data['row_no']) ? intval($data['row_no']) : 0);
        $to_column_no         = isset($data['to_column_no']) ? intval($data['to_column_no']) : (isset($data['column_no']) ? intval($data['column_no']) : 0);
        $to_rack_no           = $data['to_rack_no'] ?? ($data['rack_no'] ?? null);
        $to_floor_no          = $data['to_floor_no'] ?? ($data['floar_no'] ?? null);
        $to_box_qrcode        = $data['to_box_qrcode'] ?? null;

        $previous_status      = $data['previous_status'] ?? null;
        $current_status       = $data['current_status'] ?? ($data['status'] ?? 'InPolyhouse');
        $growth_stage         = $data['growth_stage'] ?? 'Sowing';
        $health_status        = $data['health_status'] ?? 'Healthy';
        $live_plant_count     = isset($data['live_plant_count']) ? intval($data['live_plant_count']) : null;
        $damaged_plant_count  = isset($data['damaged_plant_count']) ? intval($data['damaged_plant_count']) : 0;
        $reason_for_movement  = $data['reason_for_movement'] ?? ($data['remarks'] ?? 'Tray Initial Registration');

        $labourid             = $data['labourid'] ?? null;
        $labourname           = $data['labourname'] ?? null;
        $movement_date        = !empty($data['movement_date']) ? $data['movement_date'] : date('Y-m-d');
        $movement_time        = !empty($data['movement_time']) ? $data['movement_time'] : date('H:i:s');
        $device_source        = $data['device_source'] ?? 'Web_Portal';
        $ip_address           = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $created_by           = $data['created_by'] ?? ($data['loginid'] ?? 'system');

        $sql = "INSERT INTO `krf_traymovement_history` (
            `transaction_no`, `companyid`, `subcompanyid`, `projectid`, `psono`,
            `stockorder_no`, `clientid`, `clientname`, `productid`, `productname`,
            `activity_type`, `sowingplantid`, `barcode`, `sowingbatchno`, `sowinglotno_internal`,
            `seedid`, `seedname`, `seed_lotno`, `trayid`, `tray_cavity`,
            `roottype`, `from_location_id`, `from_polyhouse_id`, `from_polyhouse_name`, `from_bed_no`,
            `from_row_no`, `from_column_no`, `from_rack_no`, `from_floor_no`, `from_box_qrcode`,
            `to_location_id`, `to_polyhouse_id`, `to_polyhouse_name`, `to_bed_no`, `to_row_no`,
            `to_column_no`, `to_rack_no`, `to_floor_no`, `to_box_qrcode`, `previous_status`,
            `current_status`, `growth_stage`, `health_status`, `live_plant_count`, `damaged_plant_count`,
            `reason_for_movement`, `labourid`, `labourname`, `movement_date`, `movement_time`,
            `device_source`, `ip_address`, `created_by`
        ) VALUES (
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?
        )";

        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            return ["status" => "error", "message" => "Prepare failed: " . $krfconn->error];
        }

        $stmt->bind_param(
            "siiissssssissssisssisisssisssssisssisssssisssiisssss",
            $transaction_no, $companyid, $subcompanyid, $projectid, $psono,
            $stockorder_no, $clientid, $clientname, $productid, $productname,
            $activity_type, $sowingplantid, $barcode, $sowingbatchno, $sowinglotno_internal,
            $seedid, $seedname, $seed_lotno, $trayid, $tray_cavity,
            $roottype, $from_location_id, $from_polyhouse_id, $from_polyhouse_name, $from_bed_no,
            $from_row_no, $from_column_no, $from_rack_no, $from_floor_no, $from_box_qrcode,
            $to_location_id, $to_polyhouse_id, $to_polyhouse_name, $to_bed_no, $to_row_no,
            $to_column_no, $to_rack_no, $to_floor_no, $to_box_qrcode, $previous_status,
            $current_status, $growth_stage, $health_status, $live_plant_count, $damaged_plant_count,
            $reason_for_movement, $labourid, $labourname, $movement_date, $movement_time,
            $device_source, $ip_address, $created_by
        );

        $stmt->execute();
        $movement_id = $stmt->insert_id;
        $stmt->close();

        return ["status" => "success", "movement_id" => $movement_id, "transaction_no" => $transaction_no];

    } catch (Exception $e) {
        error_log("insertTrayMovementHistory Error: " . $e->getMessage());
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

/**
 * Update/Log an ongoing activity or state change for an existing tray
 *
 * @param mysqli $krfconn Database connection
 * @param array $data Associative array containing update info
 * @return array
 */
function updateTrayMovementHistory($krfconn, $data = [])
{
    try {
        if (!$krfconn || !is_array($data)) {
            return ["status" => "error", "message" => "Invalid connection or data."];
        }

        $sowingplantid = !empty($data['sowingplantid']) ? intval($data['sowingplantid']) : null;
        $barcode       = $data['barcode'] ?? null;
        $updated_by    = $data['updated_by'] ?? ($data['loginid'] ?? 'system');

        if (!$sowingplantid && !$barcode) {
            return ["status" => "error", "message" => "sowingplantid or barcode required."];
        }

        // 1. If physical shift or state change, create a new transaction entry
        if (!empty($data['activity_type']) || !empty($data['to_polyhouse_id']) || !empty($data['to_bed_no'])) {
            return insertTrayMovementHistory($krfconn, $data);
        }

        // 2. Otherwise update existing open record metadata
        $sql = "UPDATE `krf_traymovement_history` SET 
                    `trayid` = COALESCE(?, `trayid`),
                    `tray_cavity` = COALESCE(?, `tray_cavity`),
                    `seedid` = COALESCE(?, `seedid`),
                    `roottype` = COALESCE(?, `roottype`),
                    `to_location_id` = COALESCE(?, `to_location_id`),
                    `updated_by` = ?,
                    `updated_at` = NOW()
                WHERE (sowingplantid = ? OR barcode = ?)
                ORDER BY movement_id DESC LIMIT 1";

        $stmt = $krfconn->prepare($sql);
        if ($stmt) {
            $trayid      = !empty($data['trayid']) ? intval($data['trayid']) : null;
            $tray_cavity = isset($data['tray_cavity']) ? intval($data['tray_cavity']) : (isset($data['traycavity']) ? intval($data['traycavity']) : null);
            $seedid      = !empty($data['seedid']) ? intval($data['seedid']) : null;
            $roottype    = $data['roottype'] ?? null;
            $to_loc      = !empty($data['to_location_id']) ? intval($data['to_location_id']) : (!empty($data['sowinglocationid']) ? intval($data['sowinglocationid']) : null);

            $stmt->bind_param(
                "iiisisis",
                $trayid,
                $tray_cavity,
                $seedid,
                $roottype,
                $to_loc,
                $updated_by,
                $sowingplantid,
                $barcode
            );
            $stmt->execute();
            $stmt->close();
            return ["status" => "success", "message" => "Updated successfully."];
        }

        return ["status" => "error", "message" => $krfconn->error];

    } catch (Exception $e) {
        error_log("updateTrayMovementHistory Error: " . $e->getMessage());
        return ["status" => "error", "message" => $e->getMessage()];
    }
}

/**
 * Fetch full transaction lifecycle for a specific tray barcode
 *
 * @param mysqli $krfconn Database connection
 * @param string $barcode Barcode / QR Code
 * @return array List of movement history records
 */
function getTrayMovementHistoryByBarcode($krfconn, $barcode)
{
    try {
        $sql = "SELECT * FROM `krf_traymovement_history` WHERE `barcode` = ? ORDER BY `movement_id` ASC";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $res = $stmt->get_result();
        $history = [];
        while ($row = $res->fetch_assoc()) {
            $history[] = $row;
        }
        $stmt->close();
        return $history;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Calculate actual plants in a tray based on formula:
 * actual_plant = qtypertray - failurecount - dispatchqty
 *
 * @param int|array|object $qtyOrTray Capacity / qtypertray OR tray row array / object
 * @param int $failureCount Failure / dead / unhealthy count (used if first param is int)
 * @param int $dispatchQty Dispatched plant count (used if first param is int)
 * @return int Actual available healthy plants in the tray (minimum 0)
 */
function calculateActualPlantInTray($qtyOrTray, $failureCount = 0, $dispatchQty = 0)
{
    if (is_array($qtyOrTray)) {
        $qtypertray   = (int)($qtyOrTray['qtypertray'] ?? $qtyOrTray['cavity_count'] ?? 0);
        $failurecount = (int)($qtyOrTray['failurecount'] ?? 0);
        $dispatchqty  = (int)($qtyOrTray['dispatchqty'] ?? 0);
    } elseif (is_object($qtyOrTray)) {
        $qtypertray   = (int)($qtyOrTray->qtypertray ?? $qtyOrTray->cavity_count ?? 0);
        $failurecount = (int)($qtyOrTray->failurecount ?? 0);
        $dispatchqty  = (int)($qtyOrTray->dispatchqty ?? 0);
    } else {
        $qtypertray   = (int)$qtyOrTray;
        $failurecount = (int)$failureCount;
        $dispatchqty  = (int)$dispatchQty;
    }

    $actual = $qtypertray - $failurecount - $dispatchqty;
    return max(0, $actual);
}

?>