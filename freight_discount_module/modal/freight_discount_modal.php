<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

require_once(__DIR__ . '/../../connection.php');

function sendJsonResponse(bool $status, string $message = '', array $data = []): void
{
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}

function cleanInput($val): string
{
    return trim((string)$val);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$projectid = isset($_SESSION['projectid']) ? (int)$_SESSION['projectid'] : 1;
$loginname = $_SESSION['loginname'] ?? 'Admin';

try {
    if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
        throw new Exception('Karjeefarm database connection ($krfconn) is not available.');
    }

    switch ($action) {

        case 'fetch_plans':
            $search = cleanInput($_POST['search'] ?? $_GET['search'] ?? '');
            $custType = cleanInput($_POST['customer_type'] ?? $_GET['customer_type'] ?? 'All');
            $plantType = cleanInput($_POST['plant_type'] ?? $_GET['plant_type'] ?? 'All');
            $status = cleanInput($_POST['status'] ?? $_GET['status'] ?? 'All');
            $dateFrom = cleanInput($_POST['date_from'] ?? $_GET['date_from'] ?? '');
            $dateTo = cleanInput($_POST['date_to'] ?? $_GET['date_to'] ?? '');

            $where = ["(tedit IS NULL OR tedit = '')"];
            $params = [];
            $types = "";

            if (!empty($search)) {
                $where[] = "(plan_code LIKE ? OR plan_name LIKE ? OR statename LIKE ? OR districtname LIKE ?)";
                $searchWild = "%{$search}%";
                $params[] = $searchWild;
                $params[] = $searchWild;
                $params[] = $searchWild;
                $params[] = $searchWild;
                $types .= "ssss";
            }

            if (!empty($custType) && $custType !== 'All') {
                $where[] = "(customer_type = ? OR customer_type = 'All')";
                $params[] = $custType;
                $types .= "s";
            }

            if (!empty($plantType) && $plantType !== 'All') {
                $where[] = "(plant_type = ? OR plant_type = 'Both')";
                $params[] = $plantType;
                $types .= "s";
            }

            if (!empty($status) && $status !== 'All') {
                $where[] = "status = ?";
                $params[] = $status;
                $types .= "s";
            }

            if (!empty($dateFrom)) {
                $where[] = "valid_to >= ?";
                $params[] = $dateFrom;
                $types .= "s";
            }

            if (!empty($dateTo)) {
                $where[] = "valid_from <= ?";
                $params[] = $dateTo;
                $types .= "s";
            }

            $whereSql = implode(" AND ", $where);
            $sql = "SELECT * FROM `krf_freight_discount_plan` WHERE {$whereSql} ORDER BY priority DESC, id DESC";

            $stmt = $krfconn->prepare($sql);
            if (!empty($types)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();

            $plans = [];
            $today = date('Y-m-d');

            while ($row = $result->fetch_assoc()) {
                // Determine runtime expired status
                if ($row['status'] === 'Active' && $row['valid_to'] < $today) {
                    $row['is_expired'] = true;
                    $row['display_status'] = 'Expired';
                } else {
                    $row['is_expired'] = false;
                    $row['display_status'] = $row['status'];
                }
                $plans[] = $row;
            }
            $stmt->close();

            // Calculate overall stats
            $statsSql = "SELECT 
                COUNT(*) as total_plans,
                SUM(CASE WHEN status = 'Active' AND valid_to >= CURDATE() THEN 1 ELSE 0 END) as active_plans,
                SUM(CASE WHEN customer_type = 'Dealer' AND plant_type = 'Grafted' AND status = 'Active' THEN 1 ELSE 0 END) as dealer_grafted,
                SUM(CASE WHEN customer_type = 'Dealer' AND plant_type = 'Nongrafted' AND status = 'Active' THEN 1 ELSE 0 END) as dealer_nongrafted,
                SUM(CASE WHEN customer_type = 'Farmer' AND plant_type = 'Grafted' AND status = 'Active' THEN 1 ELSE 0 END) as farmer_grafted,
                SUM(CASE WHEN customer_type = 'Farmer' AND plant_type = 'Nongrafted' AND status = 'Active' THEN 1 ELSE 0 END) as farmer_nongrafted
            FROM `krf_freight_discount_plan` WHERE (tedit IS NULL OR tedit = '')";

            $statsRes = $krfconn->query($statsSql);
            $stats = $statsRes ? $statsRes->fetch_assoc() : [];

            sendJsonResponse(true, 'Plans retrieved successfully', [
                'plans' => $plans,
                'stats' => $stats
            ]);
            break;

        case 'get_plan':
            $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid Plan ID');
            }

            $stmt = $krfconn->prepare("SELECT * FROM `krf_freight_discount_plan` WHERE id = ? AND (tedit IS NULL OR tedit = '') LIMIT 1");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $plan = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$plan) {
                throw new Exception('Plan not found.');
            }

            sendJsonResponse(true, 'Plan found', $plan);
            break;

        case 'save_plan':
            $planName = cleanInput($_POST['plan_name'] ?? '');
            $custType = cleanInput($_POST['customer_type'] ?? 'Farmer');
            $plantType = cleanInput($_POST['plant_type'] ?? 'Grafted');
            $discountType = cleanInput($_POST['discount_type'] ?? 'Percentage');
            $discountValue = (float)($_POST['discount_value'] ?? 0);
            $maxDiscount = (float)($_POST['max_discount_amount'] ?? 0);
            $minOrderQty = (int)($_POST['min_order_qty'] ?? 0);
            $maxOrderQty = (int)($_POST['max_order_qty'] ?? 0);
            $minFreight = (float)($_POST['min_freight_amount'] ?? 0);
            $minDistance = (float)($_POST['min_distance_km'] ?? 0);
            $maxDistance = (float)($_POST['max_distance_km'] ?? 0);
            $stateId = cleanInput($_POST['stateid'] ?? 'ALL');
            $stateName = cleanInput($_POST['statename'] ?? 'All States');
            $districtId = cleanInput($_POST['districtid'] ?? 'ALL');
            $districtName = cleanInput($_POST['districtname'] ?? 'All Districts');
            $validFrom = cleanInput($_POST['valid_from'] ?? date('Y-m-d'));
            $validTo = cleanInput($_POST['valid_to'] ?? date('Y-m-d', strtotime('+1 year')));
            $statusVal = cleanInput($_POST['status'] ?? 'Active');
            $priority = (int)($_POST['priority'] ?? 1);
            $desc = cleanInput($_POST['description'] ?? '');
            $terms = cleanInput($_POST['terms_conditions'] ?? '');

            if (empty($planName)) {
                $custLabel = ($custType === 'Dealer') ? 'Dealer' : (($custType === 'Farmer') ? 'Farmer' : 'All Customers');
                $plantLabel = ($plantType === 'Grafted') ? 'Grafted Crops' : (($plantType === 'Nongrafted') ? 'Non-Grafted Crops' : 'All Crops');
                $planName = "{$custLabel} - {$plantLabel} Freight Discount Plan";
            }
            if ($discountValue <= 0) {
                throw new Exception('Discount value must be greater than 0.');
            }
            if (empty($validFrom) || empty($validTo)) {
                throw new Exception('Validity period dates are required.');
            }

            // Auto-generate plan code if empty
            $planCode = cleanInput($_POST['plan_code'] ?? '');
            if (empty($planCode)) {
                $custCode = ($custType === 'Dealer') ? 'DLR' : (($custType === 'Farmer') ? 'FRM' : 'ALL');
                $plantCode = ($plantType === 'Grafted') ? 'GRAFT' : (($plantType === 'Nongrafted') ? 'NONGR' : 'ALL');
                $planCode = "FDR-{$custCode}-{$plantCode}";
            }

            // Ensure $planCode is unique across project on insert
            $baseCode = preg_replace('/(-\d+)+$/', '', $planCode);
            $candidateCode = $planCode;
            $counter = 1;
            while (true) {
                $stmtCheck = $krfconn->prepare("SELECT id FROM `krf_freight_discount_plan` WHERE projectid = ? AND plan_code = ? LIMIT 1");
                $stmtCheck->bind_param("is", $projectid, $candidateCode);
                $stmtCheck->execute();
                $existing = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();
                if (!$existing) {
                    $planCode = $candidateCode;
                    break;
                }
                $counter++;
                $candidateCode = $baseCode . '-' . str_pad((string)$counter, 2, '0', STR_PAD_LEFT);
            }

            $companyid = isset($_POST['companyid']) && $_POST['companyid'] !== '' ? (int)$_POST['companyid'] : (isset($_SESSION['companyid']) ? (int)$_SESSION['companyid'] : 1);
            $subcompanyid = isset($_POST['subcompanyid']) && $_POST['subcompanyid'] !== '' ? (int)$_POST['subcompanyid'] : (isset($_SESSION['subcompanyid']) ? (int)$_SESSION['subcompanyid'] : 1);
            $projectid = isset($_POST['projectid']) && $_POST['projectid'] !== '' ? (int)$_POST['projectid'] : (isset($_SESSION['projectid']) ? (int)$_SESSION['projectid'] : 1);
            $loginname = cleanInput($_POST['loginname'] ?? $_SESSION['loginname'] ?? 'Admin');

            $stmt = $krfconn->prepare("INSERT INTO `krf_freight_discount_plan` 
                (`projectid`, `companyid`, `subcompanyid`, `plan_code`, `plan_name`, `customer_type`, `plant_type`, `discount_type`, `discount_value`, `max_discount_amount`, `min_order_qty`, `max_order_qty`, `min_freight_amount`, `min_distance_km`, `max_distance_km`, `stateid`, `statename`, `districtid`, `districtname`, `valid_from`, `valid_to`, `status`, `priority`, `description`, `terms_conditions`, `created_by`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            $stmt->bind_param("iiisssssddiidddsssssssisss", 
                $projectid, $companyid, $subcompanyid, $planCode, $planName, $custType, $plantType, $discountType, 
                $discountValue, $maxDiscount, $minOrderQty, $maxOrderQty, $minFreight, 
                $minDistance, $maxDistance, $stateId, $stateName, $districtId, $districtName, 
                $validFrom, $validTo, $statusVal, $priority, $desc, $terms, $loginname
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to insert plan: ' . $stmt->error);
            }
            $insertId = $stmt->insert_id;
            $stmt->close();

            sendJsonResponse(true, 'Freight discount plan created successfully!', ['id' => $insertId, 'plan_code' => $planCode]);
            break;

        case 'update_plan':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid Plan ID for update.');
            }

            $companyid = isset($_POST['companyid']) && $_POST['companyid'] !== '' ? (int)$_POST['companyid'] : (isset($_SESSION['companyid']) ? (int)$_SESSION['companyid'] : 1);
            $subcompanyid = isset($_POST['subcompanyid']) && $_POST['subcompanyid'] !== '' ? (int)$_POST['subcompanyid'] : (isset($_SESSION['subcompanyid']) ? (int)$_SESSION['subcompanyid'] : 1);
            $projectid = isset($_POST['projectid']) && $_POST['projectid'] !== '' ? (int)$_POST['projectid'] : (isset($_SESSION['projectid']) ? (int)$_SESSION['projectid'] : 1);
            $loginname = cleanInput($_POST['loginname'] ?? $_SESSION['loginname'] ?? 'Admin');

            $custType = cleanInput($_POST['customer_type'] ?? 'Farmer');
            $plantType = cleanInput($_POST['plant_type'] ?? 'Grafted');
            $planCode = cleanInput($_POST['plan_code'] ?? '');
            $planName = cleanInput($_POST['plan_name'] ?? '');

            if (empty($planName)) {
                $custLabel = ($custType === 'Dealer') ? 'Dealer' : (($custType === 'Farmer') ? 'Farmer' : 'All Customers');
                $plantLabel = ($plantType === 'Grafted') ? 'Grafted Crops' : (($plantType === 'Nongrafted') ? 'Non-Grafted Crops' : 'All Crops');
                $planName = "{$custLabel} - {$plantLabel} Freight Discount Plan";
            }
            if (empty($planCode)) {
                $custCode = ($custType === 'Dealer') ? 'DLR' : (($custType === 'Farmer') ? 'FRM' : 'ALL');
                $plantCode = ($plantType === 'Grafted') ? 'GRAFT' : (($plantType === 'Nongrafted') ? 'NONGR' : 'ALL');
                $planCode = "FDR-{$custCode}-{$plantCode}-" . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
            }

            // Ensure $planCode does not collide with another plan in this project
            $baseCode = preg_replace('/(-\d+)+$/', '', $planCode);
            $candidateCode = $planCode;
            $counter = 1;
            while (true) {
                $stmtCheck = $krfconn->prepare("SELECT id FROM `krf_freight_discount_plan` WHERE projectid = ? AND plan_code = ? AND id != ? LIMIT 1");
                $stmtCheck->bind_param("isi", $projectid, $candidateCode, $id);
                $stmtCheck->execute();
                $existing = $stmtCheck->get_result()->fetch_assoc();
                $stmtCheck->close();
                if (!$existing) {
                    $planCode = $candidateCode;
                    break;
                }
                $candidateCode = $baseCode . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
                if ($candidateCode === $planCode || $counter > 1) {
                    $candidateCode = $baseCode . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT) . '-' . str_pad((string)$counter, 2, '0', STR_PAD_LEFT);
                }
                $counter++;
            }
            $discountType = cleanInput($_POST['discount_type'] ?? 'Percentage');
            $discountValue = (float)($_POST['discount_value'] ?? 0);
            $maxDiscount = (float)($_POST['max_discount_amount'] ?? 0);
            $minOrderQty = (int)($_POST['min_order_qty'] ?? 0);
            $maxOrderQty = (int)($_POST['max_order_qty'] ?? 0);
            $minFreight = (float)($_POST['min_freight_amount'] ?? 0);
            $minDistance = (float)($_POST['min_distance_km'] ?? 0);
            $maxDistance = (float)($_POST['max_distance_km'] ?? 0);
            $stateId = cleanInput($_POST['stateid'] ?? 'ALL');
            $stateName = cleanInput($_POST['statename'] ?? 'All States');
            $districtId = cleanInput($_POST['districtid'] ?? 'ALL');
            $districtName = cleanInput($_POST['districtname'] ?? 'All Districts');
            $validFrom = cleanInput($_POST['valid_from'] ?? date('Y-m-d'));
            $validTo = cleanInput($_POST['valid_to'] ?? date('Y-m-d', strtotime('+1 year')));
            $statusVal = cleanInput($_POST['status'] ?? 'Active');
            $priority = (int)($_POST['priority'] ?? 1);
            $desc = cleanInput($_POST['description'] ?? '');
            $terms = cleanInput($_POST['terms_conditions'] ?? '');

            if (empty($planName)) {
                throw new Exception('Plan Name is required.');
            }
            if ($discountValue <= 0) {
                throw new Exception('Discount value must be greater than 0.');
            }

            $stmt = $krfconn->prepare("UPDATE `krf_freight_discount_plan` SET 
                `projectid` = ?, `companyid` = ?, `subcompanyid` = ?, `plan_code` = ?, `plan_name` = ?, `customer_type` = ?, `plant_type` = ?, 
                `discount_type` = ?, `discount_value` = ?, `max_discount_amount` = ?, 
                `min_order_qty` = ?, `max_order_qty` = ?, `min_freight_amount` = ?, 
                `min_distance_km` = ?, `max_distance_km` = ?, `stateid` = ?, `statename` = ?, 
                `districtid` = ?, `districtname` = ?, `valid_from` = ?, `valid_to` = ?, 
                `status` = ?, `priority` = ?, `description` = ?, `terms_conditions` = ?, 
                `updated_by` = ? WHERE `id` = ?");

            $stmt->bind_param("iiisssssddiidddsssssssisssi", 
                $projectid, $companyid, $subcompanyid, $planCode, $planName, $custType, $plantType, $discountType, 
                $discountValue, $maxDiscount, $minOrderQty, $maxOrderQty, $minFreight, 
                $minDistance, $maxDistance, $stateId, $stateName, $districtId, $districtName, 
                $validFrom, $validTo, $statusVal, $priority, $desc, $terms, $loginname, $id
            );

            if (!$stmt->execute()) {
                throw new Exception('Failed to update plan: ' . $stmt->error);
            }
            $stmt->close();

            sendJsonResponse(true, 'Freight discount plan updated successfully!');
            break;

        case 'delete_plan':
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid Plan ID.');
            }

            $stmt = $krfconn->prepare("UPDATE `krf_freight_discount_plan` SET `tedit` = 'deleted', `status` = 'Inactive', `updated_by` = ? WHERE `id` = ?");
            $stmt->bind_param("si", $loginname, $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to delete plan: ' . $stmt->error);
            }
            $stmt->close();

            sendJsonResponse(true, 'Plan deleted successfully.');
            break;

        case 'toggle_status':
            $id = (int)($_POST['id'] ?? 0);
            $newStatus = cleanInput($_POST['status'] ?? 'Active');
            if ($id <= 0) {
                throw new Exception('Invalid Plan ID.');
            }

            $stmt = $krfconn->prepare("UPDATE `krf_freight_discount_plan` SET `status` = ?, `updated_by` = ? WHERE `id` = ?");
            $stmt->bind_param("ssi", $newStatus, $loginname, $id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to change status: ' . $stmt->error);
            }
            $stmt->close();

            sendJsonResponse(true, "Plan status changed to {$newStatus}.");
            break;

        case 'get_states':
            $states = [];
            if (isset($krfconn) && $krfconn instanceof mysqli) {
                $res = $krfconn->query("SELECT stateid, statename FROM master_states WHERE COALESCE(status, 'Active') = 'Active' ORDER BY statename ASC");
                if ($res) {
                    while ($r = $res->fetch_assoc()) {
                        $states[] = $r;
                    }
                }
            }
            sendJsonResponse(true, 'States loaded', $states);
            break;

        case 'get_districts':
            $stateid = cleanInput($_GET['stateid'] ?? $_POST['stateid'] ?? '');
            $districts = [];
            if (!empty($stateid) && isset($krfconn) && $krfconn instanceof mysqli) {
                $stmt = $krfconn->prepare("SELECT districtid, districtname FROM master_districts WHERE stateid = ? AND COALESCE(status, 'Active') = 'Active' ORDER BY districtname ASC");
                $stmt->bind_param("s", $stateid);
                $stmt->execute();
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $districts[] = $r;
                }
                $stmt->close();
            }
            sendJsonResponse(true, 'Districts loaded', $districts);
            break;

        case 'calculate_discount':
            $rawCustType = cleanInput($_REQUEST['customer_type'] ?? 'Farmer');
            $rawPlantType = cleanInput($_REQUEST['plant_type'] ?? 'Grafted');
            $orderQty = (int)($_REQUEST['order_qty'] ?? 0);
            $freightAmount = (float)($_REQUEST['freight_amount'] ?? 0);
            $distanceKm = (float)($_REQUEST['distance_km'] ?? 0);
            $stateId = cleanInput($_REQUEST['stateid'] ?? 'ALL');
            $districtId = cleanInput($_REQUEST['districtid'] ?? 'ALL');
            $reqProjectId = cleanInput($_REQUEST['projectid'] ?? '');

            // Normalize customer type
            $custType = 'Farmer';
            if (strcasecmp($rawCustType, 'Dealer') === 0) {
                $custType = 'Dealer';
            } else if (strcasecmp($rawCustType, 'All') === 0) {
                $custType = 'All';
            }

            // Normalize plant type
            $plantType = 'Grafted';
            if (strcasecmp($rawPlantType, 'Nongrafted') === 0 || strcasecmp($rawPlantType, 'Non-Grafted') === 0) {
                $plantType = 'Nongrafted';
            } else if (strcasecmp($rawPlantType, 'Both') === 0 || strcasecmp($rawPlantType, 'All') === 0) {
                $plantType = 'Both';
            }

            $today = date('Y-m-d');

            // Build query based on customer_type and plant_type rules:
            // 1. If customer is 'Dealer', match plans where customer_type = 'Dealer' OR customer_type = 'All'
            // 2. If customer is 'Farmer', match plans where customer_type = 'Farmer' OR customer_type = 'All'
            // 3. If customer is 'All', match any customer_type
            $custCondition = "(customer_type = ? OR customer_type = 'All')";
            $custParams = [$custType];
            if ($custType === 'All') {
                $custCondition = "1=1";
                $custParams = [];
            }

            // Plant condition
            $plantCondition = "(plant_type = ? OR plant_type = 'Both')";
            $plantParams = [$plantType];
            if ($plantType === 'Both') {
                $plantCondition = "1=1";
                $plantParams = [];
            }

            $sql = "SELECT * FROM `krf_freight_discount_plan` 
                    WHERE {$custCondition}
                      AND {$plantCondition}
                      AND status = 'Active'
                      AND valid_from <= ? AND valid_to >= ?
                      AND (tedit IS NULL OR tedit = '')
                    ORDER BY priority DESC, id DESC";

            $stmt = $krfconn->prepare($sql);
            $allParams = array_merge($custParams, $plantParams, [$today, $today]);
            $types = str_repeat('s', count($allParams));
            $stmt->bind_param($types, ...$allParams);
            $stmt->execute();
            $res = $stmt->get_result();

            $bestPlan = null;
            $maxCalculatedDiscount = 0.0;
            $evaluatedPlans = [];

            while ($p = $res->fetch_assoc()) {
                // Check quantity thresholds
                if ($p['min_order_qty'] > 0 && $orderQty < (int)$p['min_order_qty']) continue;
                if ($p['max_order_qty'] > 0 && $orderQty > (int)$p['max_order_qty']) continue;

                // Check freight amount threshold
                if ($p['min_freight_amount'] > 0 && $freightAmount < (float)$p['min_freight_amount']) continue;

                // Check distance threshold (if max distance cap is set)
                if ($p['max_distance_km'] > 0 && $distanceKm > (float)$p['max_distance_km']) continue;

                // Check geographic applicability
                if ($p['stateid'] !== 'ALL' && !empty($stateId) && $stateId !== 'ALL' && $p['stateid'] !== $stateId) continue;
                if ($p['districtid'] !== 'ALL' && !empty($districtId) && $districtId !== 'ALL' && $p['districtid'] !== $districtId) continue;

                // Compute discount amount
                $discount = 0.0;
                $val = (float)$p['discount_value'];
                $minDist = (float)$p['min_distance_km'];

                if ($p['discount_type'] === 'Percentage') {
                    if ($val >= 100) {
                        if ($minDist > 0 && $distanceKm > $minDist) {
                            // Up to min_distance_km is free (0 freight), remaining distance is chargeable
                            $freeRatio = $minDist / $distanceKm;
                            $discount = $freightAmount * $freeRatio;
                        } else {
                            $discount = $freightAmount;
                        }
                    } else {
                        $discount = ($freightAmount * $val) / 100.0;
                    }
                } else if ($p['discount_type'] === 'Fixed_Amount') {
                    $discount = min($val, $freightAmount);
                } else if ($p['discount_type'] === 'Per_Plant') {
                    $discount = min($orderQty * $val, $freightAmount);
                } else if ($p['discount_type'] === 'Per_KM') {
                    $discount = min($distanceKm * $val, $freightAmount);
                }

                // Apply max discount cap
                if ($p['max_discount_amount'] > 0 && $discount > (float)$p['max_discount_amount']) {
                    $discount = (float)$p['max_discount_amount'];
                }

                $discount = round($discount, 2);
                $p['calculated_discount'] = $discount;
                $evaluatedPlans[] = $p;

                if ($discount > $maxCalculatedDiscount) {
                    $maxCalculatedDiscount = $discount;
                    $bestPlan = $p;
                }
            }
            $stmt->close();

            $payableFreight = max(0.0, round($freightAmount - $maxCalculatedDiscount, 2));
            $discountPercent = ($freightAmount > 0) ? round(($maxCalculatedDiscount / $freightAmount) * 100, 2) : 0.00;

            $allActiveRules = [];
            // Fetch all active rules for reference
            $allRulesSql = "SELECT id, plan_code, plan_name, customer_type, plant_type, min_order_qty, max_order_qty, min_distance_km, max_distance_km, min_freight_amount, discount_type, discount_value, max_discount_amount, status 
                            FROM `krf_freight_discount_plan` 
                            WHERE status = 'Active' 
                              AND valid_from <= ? AND valid_to >= ? 
                              AND (tedit IS NULL OR tedit = '')
                            ORDER BY priority DESC, id ASC";
            $allStmt = $krfconn->prepare($allRulesSql);
            if ($allStmt) {
                $allStmt->bind_param("ss", $today, $today);
                $allStmt->execute();
                $allRes = $allStmt->get_result();
                while ($ar = $allRes->fetch_assoc()) {
                    $allActiveRules[] = $ar;
                }
                $allStmt->close();
            }

            sendJsonResponse(true, 'Discount calculated', [
                'has_discount' => ($maxCalculatedDiscount > 0),
                'discount_percent' => $discountPercent,
                'discount_amount' => $maxCalculatedDiscount,
                'freight_amount' => $freightAmount,
                'payable_freight' => $payableFreight,
                'applied_plan' => $bestPlan,
                'evaluated_plans' => $evaluatedPlans,
                'all_active_rules' => $allActiveRules
            ]);
            break;

        case 'get_active_rules':
            $rawCustType = cleanInput($_REQUEST['customer_type'] ?? 'All');
            $rawPlantType = cleanInput($_REQUEST['plant_type'] ?? 'All');
            $today = date('Y-m-d');

            $custCondition = "(customer_type = ? OR customer_type = 'All')";
            $custParams = [$rawCustType];
            if ($rawCustType === 'All' || empty($rawCustType)) {
                $custCondition = "1=1";
                $custParams = [];
            }

            $plantCondition = "(plant_type = ? OR plant_type = 'Both')";
            $plantParams = [$rawPlantType];
            if ($rawPlantType === 'All' || $rawPlantType === 'Both' || empty($rawPlantType)) {
                $plantCondition = "1=1";
                $plantParams = [];
            }

            $sql = "SELECT id, plan_code, plan_name, customer_type, plant_type, min_order_qty, max_order_qty, min_distance_km, max_distance_km, min_freight_amount, discount_type, discount_value, max_discount_amount, status, valid_from, valid_to 
                    FROM `krf_freight_discount_plan` 
                    WHERE {$custCondition}
                      AND {$plantCondition}
                      AND status = 'Active'
                      AND valid_from <= ? AND valid_to >= ?
                      AND (tedit IS NULL OR tedit = '')
                    ORDER BY priority DESC, id ASC";

            $stmt = $krfconn->prepare($sql);
            $allParams = array_merge($custParams, $plantParams, [$today, $today]);
            $types = str_repeat('s', count($allParams));
            if (!empty($types)) {
                $stmt->bind_param($types, ...$allParams);
            }
            $stmt->execute();
            $res = $stmt->get_result();

            $rules = [];
            while ($row = $res->fetch_assoc()) {
                $rules[] = $row;
            }
            $stmt->close();

            sendJsonResponse(true, 'Active rules loaded', $rules);
            break;

        default:
            throw new Exception("Unknown action: '{$action}'");
    }

} catch (Exception $e) {
    sendJsonResponse(false, $e->getMessage());
}
