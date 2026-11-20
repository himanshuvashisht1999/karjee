<?php
error_reporting(0);
header('Content-Type: application/json');

require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/dailyroutine_functions.php");

$action = $_REQUEST['action'] ?? '';

switch ($action) {

    case 'get_subcategories_and_seeds':
        $projectId = $_REQUEST['projectid'] ?? '';
        $categoryId = $_REQUEST['categoryid'] ?? null;
        $subcategories = fetchSubCategoriesAndSeeds($krfconn, $projectId, $categoryId);
        echo json_encode(['status' => true, 'data' => $subcategories]);
        break;

    case 'get_seeds_by_subcategories':
        $projectId = $_REQUEST['projectid'] ?? '';
        $subCatIds = $_REQUEST['subcategory_ids'] ?? [];
        if (is_string($subCatIds)) {
            $decoded = json_decode($subCatIds, true);
            $subCatIds = is_array($decoded) ? $decoded : explode(',', $subCatIds);
        }
        $subCatIds = array_filter(array_map('intval', (array)$subCatIds));
        $seeds = fetchSeedsBySubCategoryIDs($krfconn, $projectId, $subCatIds);
        echo json_encode(['status' => true, 'data' => $seeds]);
        break;

    case 'get_routine_plans':
        $projectId = $_REQUEST['projectid'] ?? '';
        $filters = [
            'CategoryID' => $_REQUEST['categoryid'] ?? '',
            'SubCategoryID' => $_REQUEST['subcategoryid'] ?? '',
            'Status' => $_REQUEST['status'] ?? '',
            'search' => $_REQUEST['search'] ?? ''
        ];
        $plans = fetchDailyRoutinePlansList($krfconn, $projectId, $filters);
        echo json_encode(['status' => true, 'data' => $plans]);
        break;

    case 'get_routine_plan_details':
        $routinePlanId = intval($_REQUEST['routine_plan_id'] ?? 0);
        if ($routinePlanId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid Routine Plan ID']);
            exit;
        }
        $plan = fetchDailyRoutinePlanById($krfconn, $routinePlanId);
        if ($plan) {
            echo json_encode(['status' => true, 'data' => $plan]);
        } else {
            echo json_encode(['status' => false, 'message' => 'Routine Plan not found']);
        }
        break;

    case 'save_routine_plan':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['status' => false, 'message' => 'Invalid request method.']);
            exit;
        }

        $inputRaw = file_get_contents('php://input');
        $payload = json_decode($inputRaw, true);
        if (!$payload) {
            $payload = $_POST;
        }

        $routinePlanId = intval($payload['RoutinePlanID'] ?? 0);
        $planName = trim($payload['PlanName'] ?? '');
        $categoryId = trim($payload['CategoryID'] ?? '');
        $totalPlanDays = max(1, intval($payload['TotalPlanDays'] ?? 25));
        $description = trim($payload['Description'] ?? '');
        $projectId = trim($payload['ProjectId'] ?? '');
        $companyId = trim($payload['CompanyId'] ?? '');
        $subCompanyId = trim($payload['SubCompanyId'] ?? '');
        $status = !empty($payload['Status']) ? $payload['Status'] : 'Active';
        $userId = trim($payload['CreatedBy'] ?? 'admin');

        // Subcategory IDs
        $subCategoryIds = $payload['SubCategoryIDs'] ?? [];
        if (is_string($subCategoryIds)) {
            $subCategoryIds = json_decode($subCategoryIds, true) ?: explode(',', $subCategoryIds);
        }
        $subCategoryIds = array_values(array_filter(array_map('intval', (array)$subCategoryIds)));

        // Seed IDs
        $seedIds = $payload['SeedIDs'] ?? [];
        if (is_string($seedIds)) {
            $seedIds = json_decode($seedIds, true) ?: explode(',', $seedIds);
        }
        $seedIds = array_values(array_filter(array_map('intval', (array)$seedIds)));

        if (empty($planName)) {
            echo json_encode(['status' => false, 'message' => 'Plan Name is required.']);
            exit;
        }
        if (empty($subCategoryIds)) {
            echo json_encode(['status' => false, 'message' => 'At least one Subcategory must be selected.']);
            exit;
        }

        $primarySubCatId = intval($subCategoryIds[0]);

        $masterData = [
            'RoutinePlanID' => $routinePlanId,
            'PlanCode' => $payload['PlanCode'] ?? '',
            'PlanName' => $planName,
            'CategoryID' => $categoryId,
            'SubCategoryID' => $primarySubCatId,
            'SubCategoryIDs_JSON' => $subCategoryIds,
            'SeedIDs_JSON' => $seedIds,
            'TotalPlanDays' => $totalPlanDays,
            'Description' => $description,
            'ProjectId' => $projectId,
            'CompanyId' => $companyId,
            'SubCompanyId' => $subCompanyId,
            'Status' => $status,
            'CreatedBy' => $userId
        ];

        $masterRes = createOrUpdateDailyRoutineMaster($krfconn, $masterData);
        if (!$masterRes['status']) {
            echo json_encode($masterRes);
            exit;
        }

        $savedPlanId = $masterRes['routine_plan_id'];

        // Save Day Details
        $dayDetails = $payload['DayDetails'] ?? [];
        if (!empty($dayDetails) && is_array($dayDetails)) {
            saveDailyRoutineDayDetails($krfconn, $savedPlanId, $dayDetails);
        }

        echo json_encode([
            'status' => true,
            'message' => 'Daily Routine Plan saved successfully!',
            'routine_plan_id' => $savedPlanId,
            'plan_code' => $masterRes['plan_code']
        ]);
        break;

    case 'update_plan_days':
        $routinePlanId = intval($_POST['routine_plan_id'] ?? 0);
        $newTotalDays = max(1, intval($_POST['total_days'] ?? 25));

        if ($routinePlanId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid Plan ID']);
            exit;
        }

        $res = adjustRoutinePlanDaysCount($krfconn, $routinePlanId, $newTotalDays);
        echo json_encode(['status' => true, 'message' => "Plan days adjusted to {$newTotalDays} days.", 'data' => $res]);
        break;

    case 'duplicate_routine_plan':
        $sourcePlanId = intval($_POST['source_plan_id'] ?? 0);
        $newPlanName = trim($_POST['new_plan_name'] ?? '');
        $targetSubCategoryIds = $_POST['target_subcategory_ids'] ?? [];
        $targetSeedIds = $_POST['target_seed_ids'] ?? [];
        $userId = trim($_POST['user_id'] ?? 'admin');
        $projectId = trim($_POST['projectid'] ?? '');

        if ($sourcePlanId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid Source Plan ID']);
            exit;
        }

        if (is_string($targetSubCategoryIds)) {
            $targetSubCategoryIds = json_decode($targetSubCategoryIds, true) ?: explode(',', $targetSubCategoryIds);
        }
        if (is_string($targetSeedIds)) {
            $targetSeedIds = json_decode($targetSeedIds, true) ?: explode(',', $targetSeedIds);
        }

        $res = duplicateDailyRoutinePlan($krfconn, $sourcePlanId, $targetSubCategoryIds, $targetSeedIds, $newPlanName, $userId, $projectId);
        echo json_encode($res);
        break;

    case 'delete_routine_plan':
        $routinePlanId = intval($_POST['routine_plan_id'] ?? 0);
        $hardDelete = isset($_POST['hard_delete']) ? filter_var($_POST['hard_delete'], FILTER_VALIDATE_BOOLEAN) : true;

        if ($routinePlanId <= 0) {
            echo json_encode(['status' => false, 'message' => 'Invalid Plan ID']);
            exit;
        }

        $res = deleteDailyRoutinePlan($krfconn, $routinePlanId, $hardDelete);
        echo json_encode($res);
        break;

    case 'get_dropdown_options':
        $projectId = trim($_GET['projectid'] ?? $_POST['projectid'] ?? '');
        $options = fetchRoutineDropdownOptions($krfconn, $projectId);
        echo json_encode(['status' => true, 'data' => $options]);
        break;

    case 'add_dropdown_option':
        $optionGroup = trim($_POST['option_group'] ?? '');
        $optionName = trim($_POST['option_name'] ?? '');
        $projectId = trim($_POST['projectid'] ?? '');
        $userId = trim($_POST['user_id'] ?? 'admin');

        $res = addRoutineDropdownOption($krfconn, $optionGroup, $optionName, $projectId, $userId);
        echo json_encode($res);
        break;

    case 'delete_dropdown_option':
        $optionGroup = trim($_POST['option_group'] ?? '');
        $itemIdentifier = trim($_POST['item_identifier'] ?? $_POST['option_id'] ?? '');
        $projectId = trim($_POST['projectid'] ?? '');
        $userId = trim($_POST['user_id'] ?? 'admin');

        $res = deleteRoutineDropdownOption($krfconn, $optionGroup, $itemIdentifier, $projectId, $userId);
        echo json_encode($res);
        break;

    case 'get_polyhouse_bed_routines':
        $projectId = trim($_GET['projectid'] ?? $_POST['projectid'] ?? '');
        $filters = [
            'polyhouseid' => trim($_GET['polyhouseid'] ?? $_POST['polyhouseid'] ?? ''),
            'bed_no' => trim($_GET['bed_no'] ?? $_POST['bed_no'] ?? ''),
            'search' => trim($_GET['search'] ?? $_POST['search'] ?? ''),
            'routine_date' => trim($_GET['routine_date'] ?? $_POST['routine_date'] ?? $_GET['date'] ?? $_POST['date'] ?? '')
        ];
        $data = fetchPolyhouseBedRoutineGrid($krfconn, $projectId, $filters);
        echo json_encode(['status' => true, 'data' => $data]);
        break;

    case 'mark_bed_action_done':
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;
        $res = logBedRoutineAction($krfconn, $payload);
        echo json_encode($res);
        break;

    case 'get_bed_action_history':
        $lotNo = trim($_GET['sowinglotno_internal'] ?? $_POST['sowinglotno_internal'] ?? '');
        $bedNo = trim($_GET['bed_no'] ?? $_POST['bed_no'] ?? '');
        $stockOrderNo = trim($_GET['stockorder_no'] ?? $_POST['stockorder_no'] ?? '');
        $logs = fetchBedActionHistory($krfconn, $lotNo, $bedNo, $stockOrderNo);
        echo json_encode(['status' => true, 'data' => $logs]);
        break;

    default:
        echo json_encode(['status' => false, 'message' => 'Invalid action specified.']);
        break;
}
?>
