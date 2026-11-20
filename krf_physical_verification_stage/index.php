<?php
/** Stage 5: persistent bed statuses, empty beds and missing-bed warnings.
 * Install this folder beneath the existing application root; connection.php lives one directory above.
 * Launch via: .../krf_physical_verification_stage4/index.php?k=<existing ERP key>
 */
if (session_status() === PHP_SESSION_NONE) { session_start(); }
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
if (empty($_SESSION['physical_verify_csrf'])) $_SESSION['physical_verify_csrf'] = bin2hex(random_bytes(32));

function failRequest(int $status, string $message, bool $ajax = false): void {
    http_response_code($status);
    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $message]);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
    }
    exit;
}
function b64urlDecodeStrict(string $input): ?string {
    if ($input === '' || !preg_match('/^[A-Za-z0-9_-]+={0,2}$/D', $input)) return null;
    $out = base64_decode(strtr($input, '-_', '+/'), true);
    return $out === false ? null : $out;
}
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function rows(mysqli_stmt $stmt): array {
    $result = $stmt->get_result();
    if (!$result) throw new RuntimeException('Unable to read database result.');
    return $result->fetch_all(MYSQLI_ASSOC);
}
$ajax = isset($_GET['action']);
if (empty($_SESSION['sessionid']) || !hash_equals((string)$_SESSION['sessionid'], session_id())) {
    failRequest(401, 'Your session has expired. Please log in through Karjee Farms.', $ajax);
}
$key = isset($_GET['k']) && is_string($_GET['k']) ? $_GET['k'] : '';
$decoded = b64urlDecodeStrict($key);
$parts = $decoded === null ? [] : explode('|', $decoded);
if (count($parts) !== 7) failRequest(403, 'Invalid ERP access key.', $ajax);
$signature = b64urlDecodeStrict($parts[6]);
$original = implode('|', array_slice($parts, 0, 6));
if ($signature === null || !hash_equals($original, $signature)) {
    failRequest(403, 'Invalid ERP access key.', $ajax);
}
[$loginid, $loginroleid, $companyid, $subcompanyid, $projectid, $logintype] = array_slice($parts, 0, 6);
if ($loginid === '' || $projectid === '' || $projectid === 'undefined') failRequest(403, 'Missing user or project context.', $ajax);

require_once dirname(__DIR__) . '/connection.php';
try {
    // Confirm the user still exists and is active. The existing ERP owns password authentication.
    $stmt = $authconn->prepare('SELECT loginname FROM login_user_master WHERE loginid = ? AND isActive = ? LIMIT 1');
    if (!$stmt) throw new RuntimeException('Login lookup unavailable.');
    $active = 'yes';
    $stmt->bind_param('ss', $loginid, $active);
    $stmt->execute();
    $userRows = rows($stmt);
    $stmt->close();
    if (!$userRows) failRequest(403, 'This account is not active or is not available.', $ajax);
    $loginname = (string)$userRows[0]['loginname'];

    if ($ajax) {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_GET['action'];
        if (in_array($action, ['bed_statuses','mark_bed'], true)) {
            if ($action === 'mark_bed' && $_SERVER['REQUEST_METHOD'] !== 'POST') failRequest(405, 'POST required.', true);
            if ($action === 'mark_bed' && !hash_equals((string)$_SESSION['physical_verify_csrf'], (string)($_POST['csrf'] ?? ''))) failRequest(403, 'Invalid form token.', true);
            $input = $action === 'mark_bed' ? $_POST : $_GET;
            $location = trim((string)($input['locationid'] ?? ''));
            $polyhouse = filter_var($input['polyhouseid'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if ($location === '' || !$polyhouse) failRequest(422, 'Choose a valid location and polyhouse.', true);
            $stmt = $hrmconn->prepare('SELECT LocationId FROM hrm_AttendanceLocationMaster WHERE ProjectID = ? AND LocationId = ? LIMIT 1');
            $stmt->bind_param('ss', $projectid, $location); $stmt->execute(); $valid = rows($stmt); $stmt->close();
            if (!$valid) failRequest(422, 'Location does not belong to this project.', true);
            $stmt = $krfconn->prepare("SELECT beddetails FROM krf_polyhouse_master WHERE projectid = ? AND locationid = ? AND polyhouseid = ? AND status = 'Active' LIMIT 1");
            $stmt->bind_param('ssi', $projectid, $location, $polyhouse); $stmt->execute(); $ph = rows($stmt); $stmt->close();
            if (!$ph) failRequest(422, 'Polyhouse does not belong to the selected location.', true);
            $configured = json_decode((string)$ph[0]['beddetails'], true);
            if (!is_array($configured)) failRequest(422, 'Invalid bed configuration.', true);
            $names = array_values(array_unique(array_filter(array_map(static fn($x) => is_array($x) ? trim((string)($x['bedname'] ?? '')) : '', $configured))));
            if ($action === 'mark_bed') {
                $bed = trim((string)($_POST['bedname'] ?? ''));
                $status = (string)($_POST['status'] ?? '');
                if (!in_array($bed, $names, true) || !in_array($status, ['Complete','Empty'], true)) failRequest(422, 'Invalid bed or status.', true);
                // Lock the single status row to serialize concurrent status updates for a bed.
                $krfconn->begin_transaction();
                try {
                    $stmt = $krfconn->prepare('SELECT id FROM Physical_Verification_Polyhouse_Confirmation WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verification_date=CURDATE() LIMIT 1 FOR UPDATE');
                    $stmt->bind_param('ssi', $projectid,$location,$polyhouse); $stmt->execute(); $finalized = rows($stmt); $stmt->close();
                    if ($finalized) throw new DomainException('This polyhouse verification is already finalized for today.');
                    $stmt = $krfconn->prepare('INSERT INTO Physical_Verification_Bed_Status (project_id,location_id,polyhouse_id,bed_number,verification_date,status,supervisor_user_id) VALUES (?,?,?,?,CURDATE(),?,?) ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)');
                    $stmt->bind_param('ssisss', $projectid,$location,$polyhouse,$bed,$status,$loginid); $stmt->execute(); $stmt->close();
                    $stmt = $krfconn->prepare('SELECT id FROM Physical_Verification_Bed_Status WHERE id = LAST_INSERT_ID() FOR UPDATE');
                    $stmt->execute(); $stmt->close();
                    $stmt = $krfconn->prepare('SELECT COUNT(*) AS n FROM Physically_Verified_Stock WHERE project_id=? AND location_id=? AND polyhouse_id=? AND bed_number=? AND verified_datetime >= CURDATE() AND verified_datetime < CURDATE() + INTERVAL 1 DAY');
                    $stmt->bind_param('ssis', $projectid,$location,$polyhouse,$bed); $stmt->execute(); $count = (int)rows($stmt)[0]['n']; $stmt->close();
                    if ($status === 'Empty' && $count > 0) throw new DomainException('This bed already has physical counts today. Empty Bed cannot be selected.');
                    if ($status === 'Complete' && $count === 0) throw new DomainException('Save at least one product first, or mark this bed Empty.');
                    $stmt = $krfconn->prepare('UPDATE Physical_Verification_Bed_Status SET status=?,supervisor_user_id=?,updated_at=CURRENT_TIMESTAMP WHERE project_id=? AND location_id=? AND polyhouse_id=? AND bed_number=? AND verification_date=CURDATE()');
                    $stmt->bind_param('ssssis', $status,$loginid,$projectid,$location,$polyhouse,$bed); $stmt->execute(); $stmt->close();
                    $krfconn->commit();
                } catch (Throwable $ex) { $krfconn->rollback(); if ($ex instanceof DomainException) failRequest(422, $ex->getMessage(), true); throw $ex; }
                echo json_encode(['ok'=>true,'message'=>$bed . ' marked ' . $status . '.']); exit;
            }
            $stmt = $krfconn->prepare('SELECT bed_number,status FROM Physical_Verification_Bed_Status WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verification_date=CURDATE()');
            $stmt->bind_param('ssi', $projectid,$location,$polyhouse); $stmt->execute(); $saved = rows($stmt); $stmt->close();
            $statusMap = []; foreach ($saved as $r) $statusMap[$r['bed_number']] = $r['status'];
            $stmt = $krfconn->prepare('SELECT DISTINCT bed_number FROM Physically_Verified_Stock WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verified_datetime >= CURDATE() AND verified_datetime < CURDATE() + INTERVAL 1 DAY');
            $stmt->bind_param('ssi',$projectid,$location,$polyhouse); $stmt->execute(); $entries = rows($stmt); $stmt->close();
            $hasCounts = array_fill_keys(array_column($entries,'bed_number'),true);
            $result = []; foreach ($names as $name) {
                $result[] = ['bedname'=>$name,'status'=>$statusMap[$name] ?? (isset($hasCounts[$name]) ? 'In Progress' : 'Not Checked')];
            }
            echo json_encode(['ok'=>true,'data'=>$result], JSON_INVALID_UTF8_SUBSTITUTE); exit;
        }
        if ($action === 'products') {
            $stmt = $krfconn->prepare("SELECT ProductID, ProductName FROM product_master WHERE ProjectId = ? AND Status = 'Active' ORDER BY ProductName, ProductID");
            $stmt->bind_param('s', $projectid);
            $stmt->execute(); $data = rows($stmt); $stmt->close();
            echo json_encode(['ok'=>true,'data'=>$data], JSON_INVALID_UTF8_SUBSTITUTE); exit;
        }
        if ($action === 'trays') {
            $stmt = $krfconn->prepare("SELECT tray_id, tray_name, cavity_count, category_id, tray_code FROM krf_tray_master WHERE status='Active' AND cavity_count > 0 ORDER BY tray_name, tray_id");
            $stmt->execute(); $data = rows($stmt); $stmt->close();
            echo json_encode(['ok'=>true,'data'=>$data], JSON_INVALID_UTF8_SUBSTITUTE); exit;
        }
        if ($action === 'product_tray_preference') {
            $product = filter_var($_GET['productid'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if (!$product) failRequest(422, 'Select a product.', true);
            $stmt = $krfconn->prepare("SELECT p.tray_id FROM Physical_Verification_Product_Tray_Preference p JOIN krf_tray_master t ON t.tray_id=p.tray_id AND t.status='Active' WHERE p.project_id=? AND p.product_id=? LIMIT 1");
            $stmt->bind_param('si',$projectid,$product); $stmt->execute(); $pref=rows($stmt); $stmt->close();
            echo json_encode(['ok'=>true,'tray_id'=>$pref ? (int)$pref[0]['tray_id'] : null]); exit;
        }
        if ($action === 'save') {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') failRequest(405, 'POST required.', true);
            if (!hash_equals($_SESSION['physical_verify_csrf'], (string)($_POST['csrf'] ?? ''))) failRequest(403, 'Invalid form token.', true);
            $location = trim((string)($_POST['locationid'] ?? ''));
            $polyhouse = filter_var($_POST['polyhouseid'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $bedname = trim((string)($_POST['bedname'] ?? ''));
            $product = filter_var($_POST['productid'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            $trays = filter_var($_POST['trays'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            $plants = filter_var($_POST['plants'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>0]]);
            $trayId = filter_var($_POST['tray_id'] ?? null, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
            if ($location === '' || !$polyhouse || $bedname === '' || !$product || $trays === false || $plants === false || $trays === null || $plants === null || !$trayId) failRequest(422, 'Complete all fields using non-negative whole numbers.', true);
            if ($trays === 0) failRequest(422, 'Enter at least one tray, or use Empty Bed.', true);
            $stmt = $krfconn->prepare("SELECT tray_id, tray_name, cavity_count FROM krf_tray_master WHERE tray_id=? AND status='Active' AND cavity_count > 0 LIMIT 1");
            $stmt->bind_param('i',$trayId); $stmt->execute(); $trayRows=rows($stmt); $stmt->close();
            if (!$trayRows) failRequest(422, 'Select a valid active cavity.', true);
            $cavityCount=(int)$trayRows[0]['cavity_count'];
            if ($trays > intdiv(PHP_INT_MAX, $cavityCount) || $plants !== $trays * $cavityCount || $plants > 2147483647) failRequest(422, 'Total Plants must equal cavity count multiplied by trays.', true);
            $stmt = $hrmconn->prepare('SELECT LocationId FROM hrm_AttendanceLocationMaster WHERE ProjectID = ? AND LocationId = ? LIMIT 1');
            $stmt->bind_param('ss', $projectid, $location); $stmt->execute(); $valid = rows($stmt); $stmt->close();
            if (!$valid) failRequest(422, 'Location is not in the current project.', true);
            $stmt = $krfconn->prepare("SELECT beddetails FROM krf_polyhouse_master WHERE projectid = ? AND locationid = ? AND polyhouseid = ? AND status = 'Active' LIMIT 1");
            $stmt->bind_param('ssi', $projectid, $location, $polyhouse); $stmt->execute(); $ph = rows($stmt); $stmt->close();
            if (!$ph) failRequest(422, 'Invalid polyhouse.', true);
            $bedData = json_decode((string)$ph[0]['beddetails'], true);
            if (!is_array($bedData) || !in_array($bedname, array_column($bedData, 'bedname'), true)) failRequest(422, 'Invalid bed for this polyhouse.', true);
            $stmt = $krfconn->prepare("SELECT ProductID, ProductName FROM product_master WHERE ProductID = ? AND ProjectId = ? AND Status = 'Active' LIMIT 1");
            $stmt->bind_param('is', $product, $projectid); $stmt->execute(); $prod = rows($stmt); $stmt->close();
            if (!$prod) failRequest(422, 'Invalid active product.', true);
            // Atomic save: stock count and In Progress bed status must succeed together.
            $krfconn->begin_transaction();
            try {
                $stmt = $krfconn->prepare('SELECT id FROM Physical_Verification_Polyhouse_Confirmation WHERE project_id=? AND location_id=? AND polyhouse_id=? AND verification_date=CURDATE() LIMIT 1 FOR UPDATE');
                $stmt->bind_param('ssi', $projectid,$location,$polyhouse); $stmt->execute(); $finalized = rows($stmt); $stmt->close();
                if ($finalized) throw new DomainException('This polyhouse verification is already finalized for today.');
                // Upsert acquires a row lock for this project/location/polyhouse/bed/day.
                $stmt = $krfconn->prepare("INSERT INTO Physical_Verification_Bed_Status
                    (project_id,location_id,polyhouse_id,bed_number,verification_date,status,supervisor_user_id)
                    VALUES (?,?,?,?,CURDATE(),'In Progress',?)
                    ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)");
                $stmt->bind_param('ssiss', $projectid,$location,$polyhouse,$bedname,$loginid);
                $stmt->execute(); $statusId = $krfconn->insert_id; $stmt->close();
                $stmt = $krfconn->prepare('SELECT status FROM Physical_Verification_Bed_Status WHERE id=? FOR UPDATE');
                $stmt->bind_param('i',$statusId); $stmt->execute(); $currentStatus = rows($stmt); $stmt->close();
                if (!$currentStatus || in_array($currentStatus[0]['status'], ['Complete','Empty'], true)) {
                    throw new DomainException('This bed is already marked complete or empty today. Contact the supervisor to reopen it.');
                }
                $stmt = $krfconn->prepare('SELECT id FROM Physically_Verified_Stock WHERE project_id = ? AND location_id = ? AND polyhouse_id = ? AND bed_number = ? AND product_id = ? AND DATE(verified_datetime) = CURDATE() LIMIT 1');
                $stmt->bind_param('ssisi', $projectid, $location, $polyhouse, $bedname, $product);
                $stmt->execute(); $existing = rows($stmt); $stmt->close();
                if ($existing && (string)($_POST['duplicate_confirm'] ?? '') !== 'yes') {
                    $krfconn->rollback();
                    echo json_encode(['ok'=>false,'duplicate'=>true,'message'=>'This product was already recorded for this bed today. Save another separate entry?']); exit;
                }
                $productName = (string)$prod[0]['ProductName'];
                $stmt = $krfconn->prepare('INSERT INTO Physically_Verified_Stock (project_id, location_id, polyhouse_id, bed_number, product_id, product_name, no_of_trays, total_quantity_plants, supervisor_user_id, tray_id, tray_name, cavity_count) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $trayName = (string)$trayRows[0]['tray_name'];
                $stmt->bind_param('ssisisiisisi', $projectid, $location, $polyhouse, $bedname, $product, $productName, $trays, $plants, $loginid, $trayId, $trayName, $cavityCount);
                $stmt->execute(); $id = $stmt->insert_id; $stmt->close();
                $stmt = $krfconn->prepare('INSERT INTO Physical_Verification_Product_Tray_Preference (project_id,product_id,tray_id,updated_by) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE tray_id=VALUES(tray_id),updated_by=VALUES(updated_by),updated_at=CURRENT_TIMESTAMP');
                $stmt->bind_param('siis',$projectid,$product,$trayId,$loginid); $stmt->execute(); $stmt->close();
                $stmt = $krfconn->prepare("UPDATE Physical_Verification_Bed_Status SET status='In Progress', supervisor_user_id=? WHERE id=?");
                $stmt->bind_param('si',$loginid,$statusId); $stmt->execute(); $stmt->close();
                $krfconn->commit();
            } catch (Throwable $ex) {
                $krfconn->rollback();
                if ($ex instanceof DomainException) failRequest(409, $ex->getMessage(), true);
                throw $ex;
            }
            echo json_encode(['ok'=>true,'id'=>$id,'message'=>'Physical verification saved.']); exit;
        }
        if ($action === 'polyhouses') {
            $location = isset($_GET['locationid']) && is_string($_GET['locationid']) ? trim($_GET['locationid']) : '';
            if ($location === '') failRequest(400, 'Select a location.', true);
            $check = $hrmconn->prepare('SELECT LocationId FROM hrm_AttendanceLocationMaster WHERE ProjectID = ? AND LocationId = ? LIMIT 1');
            $check->bind_param('ss', $projectid, $location);
            $check->execute();
            $valid = rows($check);
            $check->close();
            if (!$valid) failRequest(400, 'Invalid location for this project.', true);
            $stmt = $krfconn->prepare("SELECT polyhouseid, polyhousename FROM krf_polyhouse_master WHERE projectid = ? AND locationid = ? AND status = 'Active' ORDER BY polyhousename, polyhouseid");
            $stmt->bind_param('ss', $projectid, $location);
            $stmt->execute();
            $data = rows($stmt);
            $stmt->close();
            echo json_encode(['ok' => true, 'data' => $data], JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        if ($action === 'beds') {
            $location = isset($_GET['locationid']) && is_string($_GET['locationid']) ? trim($_GET['locationid']) : '';
            $polyhouse = filter_var($_GET['polyhouseid'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($location === '' || !$polyhouse) failRequest(400, 'Select location and polyhouse.', true);
            $check = $hrmconn->prepare('SELECT LocationId FROM hrm_AttendanceLocationMaster WHERE ProjectID = ? AND LocationId = ? LIMIT 1');
            $check->bind_param('ss', $projectid, $location);
            $check->execute();
            $valid = rows($check);
            $check->close();
            if (!$valid) failRequest(400, 'Invalid location for this project.', true);
            $stmt = $krfconn->prepare("SELECT beddetails FROM krf_polyhouse_master WHERE projectid = ? AND locationid = ? AND polyhouseid = ? AND status = 'Active' LIMIT 1");
            $stmt->bind_param('ssi', $projectid, $location, $polyhouse);
            $stmt->execute();
            $data = rows($stmt);
            $stmt->close();
            if (!$data) failRequest(404, 'Polyhouse not found for this location.', true);
            $decodedBeds = json_decode((string)$data[0]['beddetails'], true);
            if (!is_array($decodedBeds) || array_keys($decodedBeds) !== range(0, count($decodedBeds) - 1)) failRequest(422, 'Bed configuration is missing or invalid JSON.', true);
            $beds = [];
            $seen = [];
            foreach ($decodedBeds as $bed) {
                if (!is_array($bed)) continue;
                $name = trim((string)($bed['bedname'] ?? ''));
                if ($name === '' || isset($seen[$name])) continue;
                $seen[$name] = true;
                $beds[] = ['bedname' => $name];
            }
            echo json_encode(['ok' => true, 'data' => $beds], JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        failRequest(400, 'Unknown action.', true);
    }
    $stmt = $hrmconn->prepare('SELECT LocationId, LocationName FROM hrm_AttendanceLocationMaster WHERE ProjectID = ? ORDER BY LocationName');
    $stmt->bind_param('s', $projectid);
    $stmt->execute();
    $locations = rows($stmt);
    $stmt->close();
} catch (Throwable $exception) {
    error_log('Physical verification Stage 1: ' . $exception->getMessage());
    failRequest(500, 'Unable to load selection data. Please contact the administrator.', $ajax);
}
?>
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Physical Stock Verification | Stage 5</title>
<style>
:root{font-family:system-ui,-apple-system,"Segoe UI",sans-serif;color:#18332a;background:#f3f7f4}*{box-sizing:border-box}body{margin:0}.shell{max-width:580px;margin:0 auto;padding:20px 15px 40px}.brand{font-size:13px;font-weight:800;letter-spacing:.08em;color:#327454;text-transform:uppercase}.card{background:white;border:1px solid #dce9e0;border-radius:18px;padding:23px 18px;box-shadow:0 8px 24px #123d2410}h1{font-size:25px;line-height:1.25;margin:10px 0}p{color:#5e756a;line-height:1.5}label{display:block;font-weight:700;margin:21px 0 7px;font-size:15px}select{width:100%;font:inherit;font-size:17px;padding:15px 12px;min-height:54px;border-radius:11px;border:1px solid #afc6b8;background:#fff;color:#173d2c}select:disabled{background:#eef3f0;color:#75887e}.summary{margin-top:23px;padding:17px;background:#f0f8f2;border-radius:12px;border:1px solid #cbe4d2}.summary strong{display:block;margin-bottom:8px}.summary div{padding:4px 0}.note{font-size:13px;margin-top:20px}.error{margin-top:15px;color:#a52b2b;min-height:20px;font-size:14px}.meta{font-size:14px;margin:10px 0 20px}.step{display:inline-block;background:#dcefe2;color:#225a3b;padding:6px 11px;border-radius:50px;font-size:12px;font-weight:700}button{font:inherit;width:100%;border:0;border-radius:11px;padding:15px;background:#23754a;color:white;font-weight:700;margin-top:18px}button:disabled{background:#b2c6b8;cursor:not-allowed}
 .entry{margin-top:22px;padding-top:16px;border-top:1px solid #dce9e0}.entry input,.entry select{width:100%;font:inherit;font-size:17px;padding:15px 12px;min-height:54px;border-radius:11px;border:1px solid #afc6b8}.entry[hidden]{display:none}.success{color:#176d3e;font-weight:700;margin-top:12px}button.secondary{background:#e6efe8;color:#245d3b}.bed-actions{display:grid;gap:8px;margin-top:14px}.bed-actions button{margin-top:0}.small-status{font-size:13px;color:#245d3b;margin-top:9px}
/* Match Select2's single-select appearance to the existing mobile form. */
.select2-container{width:100%!important;max-width:100%}
.select2-container .select2-selection--single{min-height:54px;border:1px solid #afc6b8;border-radius:11px}
.select2-container .select2-selection--single .select2-selection__rendered{padding:12px 35px 12px 12px;line-height:28px;font-size:17px;color:#173d2c}
.select2-container .select2-selection--single .select2-selection__arrow{height:52px;right:8px}
.select2-container--disabled .select2-selection--single{background:#eef3f0!important}
.select2-dropdown{border-color:#afc6b8}.select2-search__field{font-size:16px!important;min-height:38px}
</style><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css"><script src="assets/jquery.min.js"></script><script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script></head><body><main class="shell"><div class="brand">Karjee Farms</div><h1>Physical Stock Verification</h1><p class="meta">Supervisor: <?=e($loginname)?> · Project: <?=e($projectid)?></p>
<section class="card"><span class="step">STAGE 5 · BED STATUS & REPORTS</span><p>Select the physical location, polyhouse and bed to verify.</p>
<label for="location">1. Location</label><select id="location"><option value="">Select location</option><?php foreach ($locations as $row): ?><option value="<?=e((string)$row['LocationId'])?>"><?=e((string)$row['LocationName'])?></option><?php endforeach; ?></select>
<label for="polyhouse">2. Polyhouse</label><select id="polyhouse" disabled><option value="">Select location first</option></select>
<label for="bed">3. Bed</label><select id="bed" disabled><option value="">Select polyhouse first</option></select>
<div class="error" id="error" role="alert" aria-live="polite"></div>
<div class="summary" id="summary" hidden><strong>Selection confirmed</strong><div id="selectedLocation"></div><div id="selectedPolyhouse"></div><div id="selectedBed"></div></div>
<button type="button" id="confirm" disabled>Continue to Product Entry</button>
<div class="entry" id="entry" hidden>
<label for="product">4. Product</label><select id="product"><option value="">Loading products...</option></select>
<label for="cavity">5. Cavity Name</label><select id="cavity" disabled><option value="">Loading cavities...</option></select>
<label for="trays">6. Number of Trays</label><input type="number" id="trays" min="0" step="1" inputmode="numeric" placeholder="Enter number of trays">
<label for="plants">7. Total Plants (cavity count × trays)</label><input type="number" id="plants" min="0" step="1" readonly placeholder="Automatically calculated">
<button type="button" id="save">Save Physical Count</button><div id="saveDisabledReason" class="small-status" role="status" aria-live="polite"></div><div class="success" id="saveStatus" aria-live="polite"></div><div class="small-status" id="bedCount" aria-live="polite">Products saved for this bed in this visit: 0</div><div class="bed-actions"><button type="button" class="secondary" id="addProduct" disabled>Add Another Product</button><button type="button" id="nextBed" disabled>Bed Complete — Next Bed</button><button type="button" class="secondary" id="emptyBed">Mark Empty Bed / No Saleable Stock</button></div>
</div><div class="summary" id="bedOverview" hidden><strong>Today’s bed verification status</strong><div id="bedProgress"></div><div id="missingBeds"></div></div>
<div class="summary" style="display:block"><strong>Date-wise Physical Stock Report</strong><p>Choose any verification date to view plant-wise counts across all locations and polyhouses. Excel and PDF exports are available.</p><a href="date_wise_report.php?k=<?=rawurlencode($key)?>" style="display:block;text-align:center;background:#23754a;color:white;border-radius:11px;padding:15px;text-decoration:none;font-weight:700">Open Date-wise Report</a></div>
<div class="summary" style="display:block"><strong>Polyhouse Summary & Reports</strong><p>Choose a location and polyhouse above, then open the full bed-wise verification report. Download Excel or PDF from the report screen.</p><button type="button" id="openReport" disabled>Open Polyhouse Summary / Excel / PDF</button></div><p class="note">Bed Complete and Empty Bed are saved for today. A bed with saved counts but no completion is In Progress. Missing beds are highlighted before final polyhouse confirmation (Stage 5).</p></section></main>
<script>
'use strict';
const csrf = <?=json_encode($_SESSION['physical_verify_csrf'])?>;
const accessKey = <?=json_encode($key, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT)?>;
// Select2 is progressive enhancement: native selects remain usable if its CDN is unavailable.
function select2Available() { return !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2); }
function syncSelect2(element) {
    if (select2Available() && window.jQuery(element).data('select2')) {
        window.jQuery(element).trigger('change.select2');
    }
}
function initializeSelect2() {
    if (!select2Available()) return;
    ['location', 'polyhouse', 'bed', 'product', 'cavity'].forEach(id => {
        const el = document.getElementById(id);
        const $el = window.jQuery(el);
        $el.select2({
            width: '100%',
            placeholder: el.options[0]?.text || 'Select or search',
            minimumResultsForSearch: 0,
            dropdownAutoWidth: false
        });
        // Select2 emits jQuery events; the ERP's dependent-dropdown logic uses
        // native addEventListener('change', ...). Bridge without triggering a
        // second jQuery change event (which could cause recursion).
        $el.on('select2:select select2:clear', function () {
            el.dispatchEvent(new Event('change', { bubbles: false }));
        });
    });
}
const locationSelect = document.getElementById('location');
const polyhouseSelect = document.getElementById('polyhouse');
const bedSelect = document.getElementById('bed');
const confirmButton = document.getElementById('confirm');
const openReport = document.getElementById('openReport');
function updateReportLink() { openReport.disabled = !(locationSelect.value && polyhouseSelect.value); }
locationSelect.addEventListener('change', updateReportLink);
polyhouseSelect.addEventListener('change', updateReportLink);
openReport.addEventListener('click', () => {
    if (openReport.disabled) return;
    const u = new URL('report.php', window.location.href);
    u.search = new URLSearchParams({k:accessKey,locationid:locationSelect.value,polyhouseid:polyhouseSelect.value}).toString();
    window.location.href = u.toString();
});
const errorBox = document.getElementById('error');
const summary = document.getElementById('summary');
let requestSequence = 0;
let savedInCurrentBed = 0;
let busy = false;
let bedStatuses = {};
let markedToday = new Set();
const addProductButton = document.getElementById('addProduct');
const nextBedButton = document.getElementById('nextBed');
const saveButton = document.getElementById('save');
const productSelect = document.getElementById('product');
const cavitySelect = document.getElementById('cavity');
let trayPreferenceRequest = 0;
const traysInput = document.getElementById('trays');
const plantsInput = document.getElementById('plants');
// Trays and plants are counts: accept digits 0-9 only, including pasted values.
// Block wheel adjustments while preserving normal page scrolling.
[traysInput, plantsInput].forEach(input => {
    input.setAttribute('autocomplete', 'off');
    input.addEventListener('wheel', event => {
        if (document.activeElement === input) event.preventDefault();
    }, { passive: false });
    input.addEventListener('keydown', event => {
        if (['e', 'E', '+', '-', '.', ','].includes(event.key)) event.preventDefault();
    });
    input.addEventListener('beforeinput', event => {
        if (event.data !== null && event.data !== '' && !/^\d+$/.test(event.data)) {
            event.preventDefault();
        }
    });
    input.addEventListener('paste', event => {
        const pasted = (event.clipboardData || window.clipboardData).getData('text');
        if (!/^\d+$/.test(pasted)) {
            event.preventDefault();
            errorBox.textContent = 'Only whole numbers are allowed. Decimal values and letters cannot be pasted.';
        }
    });
    input.addEventListener('drop', event => {
        const dropped = event.dataTransfer.getData('text');
        if (!/^\d+$/.test(dropped)) event.preventDefault();
    });
    input.addEventListener('input', () => {
        if (!/^\d*$/.test(input.value)) {
            input.value = input.value.replace(/[^0-9]/g, '');
        }
    });
});
function hasUnsavedEntry() { return !!(productSelect.value || cavitySelect.value || traysInput.value.trim()); }
function recalculatePlants() {
    const cavity = cavitySelect.selectedOptions[0];
    const count = Number(cavity?.dataset.cavityCount || 0);
    const qty = traysInput.value;
    const total = count && /^\d+$/.test(qty) ? count * Number(qty) : 0;
    plantsInput.value = Number.isSafeInteger(total) && total <= 2147483647 && total > 0 ? String(total) : '';
}
traysInput.addEventListener('input', recalculatePlants);
cavitySelect.addEventListener('change', recalculatePlants);
productSelect.addEventListener('change', async () => {
    const sequence = ++trayPreferenceRequest;
    cavitySelect.value = ''; syncSelect2(cavitySelect); recalculatePlants();
    if (!productSelect.value) return;
    try {
        const url = new URL(window.location.href);
        url.search = new URLSearchParams({k:accessKey,action:'product_tray_preference',productid:productSelect.value}).toString();
        const response = await fetch(url.toString(),{credentials:'same-origin',cache:'no-store'});
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to load saved cavity.');
        if (sequence !== trayPreferenceRequest) return;
        if (result.tray_id && Array.from(cavitySelect.options).some(x => x.value === String(result.tray_id))) {
            cavitySelect.value = String(result.tray_id); syncSelect2(cavitySelect); recalculatePlants();
        }
    } catch (err) { if (sequence === trayPreferenceRequest) errorBox.textContent = err.message; }
});
async function loadCavities() {
    try {
        const data = await fetchData('trays', {});
        populate(cavitySelect,data,'tray_id','tray_name','Select cavity');
        data.forEach(row => {
            const opt = Array.from(cavitySelect.options).find(x => x.value === String(row.tray_id));
            if (opt) opt.dataset.cavityCount = String(row.cavity_count);
        });
        if (productSelect.value) productSelect.dispatchEvent(new Event('change'));
    } catch (err) { errorBox.textContent = err.message; }
}
function clearProductEntry() { ++trayPreferenceRequest; productSelect.value = ''; syncSelect2(productSelect); cavitySelect.value = ''; syncSelect2(cavitySelect); traysInput.value = ''; plantsInput.value = ''; }
function updateBedActions() {
    addProductButton.disabled = busy || savedInCurrentBed === 0;
    nextBedButton.disabled = busy || !bedSelect.value || bedStatuses[bedSelect.value] === 'Complete' || bedStatuses[bedSelect.value] === 'Empty';
    document.getElementById('emptyBed').disabled = busy || !bedSelect.value || bedStatuses[bedSelect.value] !== 'Not Checked';
    const selectedStatus = bedStatuses[bedSelect.value] || 'Not Checked';
    const locked = ['Complete','Empty'].includes(selectedStatus);
    saveButton.disabled = busy || !bedSelect.value || locked;
    const reason = document.getElementById('saveDisabledReason');
    reason.textContent = busy ? 'Saving or updating bed status. Please wait.'
        : !bedSelect.value ? 'Select a bed to enable saving.'
        : locked ? `Saving is blocked: ${bedSelect.value} is already marked ${selectedStatus} today. Select an unchecked/in-progress bed or use an authorized reopening process.`
        : `Selected bed: ${bedSelect.value} · Status: ${selectedStatus}. Physical counts can be saved.`;
    reason.style.color = locked ? '#a52b2b' : '#245d3b';
    document.getElementById('bedCount').textContent = 'Products saved for this bed in this visit: ' + savedInCurrentBed;
}
function resetBedProgress() { savedInCurrentBed = 0; clearProductEntry(); updateBedActions(); }
function mayLeaveBed() {
    return !hasUnsavedEntry() || window.confirm('Unsaved product details will be discarded. Continue?');
}
function resetSelect(element, placeholder) { element.replaceChildren(new Option(placeholder, '')); element.disabled = true; syncSelect2(element); }
function populate(element, data, idKey, nameKey, placeholder) {
    resetSelect(element, placeholder);
    data.forEach(row => element.add(new Option(String(row[nameKey]), String(row[idKey]))));
    element.disabled = data.length === 0;
    syncSelect2(element);
}
function resetConfirmation() { summary.hidden = true; document.getElementById('entry').hidden = true; document.getElementById('saveStatus').textContent = ''; confirmButton.disabled = true; errorBox.textContent = ''; resetBedProgress(); }
async function fetchData(action, params) {
    const url = new URL(window.location.href);
    url.search = new URLSearchParams({k:accessKey,action,...params}).toString();
    const response = await fetch(url.toString(), {credentials:'same-origin',cache:'no-store'});
    const json = await response.json();
    if (!response.ok || !json.ok) throw new Error(json.message || 'Unable to load data');
    return json.data;
}
locationSelect.addEventListener('change', async () => {
    const current = ++requestSequence;
    resetConfirmation(); resetSelect(polyhouseSelect, 'Select location first'); resetSelect(bedSelect, 'Select polyhouse first');
    if (!locationSelect.value) return;
    resetSelect(polyhouseSelect, 'Loading polyhouses...');
    try {
        const data = await fetchData('polyhouses', {locationid:locationSelect.value});
        if (current !== requestSequence) return;
        populate(polyhouseSelect, data, 'polyhouseid', 'polyhousename', data.length ? 'Select polyhouse' : 'No active polyhouses found'); updateReportLink();
    } catch (err) { if (current === requestSequence) { resetSelect(polyhouseSelect, 'Unable to load polyhouses'); errorBox.textContent = err.message; } }
});
polyhouseSelect.addEventListener('change', async () => {
    const current = ++requestSequence;
    resetConfirmation(); resetSelect(bedSelect, 'Select polyhouse first');
    if (!polyhouseSelect.value) return;
    resetSelect(bedSelect, 'Loading beds...');
    try {
        const data = await fetchData('beds', {locationid:locationSelect.value, polyhouseid:polyhouseSelect.value});
        if (current !== requestSequence) return;
        populate(bedSelect, data, 'bedname', 'bedname', data.length ? 'Select bed' : 'No beds configured');
        await refreshBedStatuses();
    } catch (err) { if (current === requestSequence) { resetSelect(bedSelect, 'Unable to load beds'); errorBox.textContent = err.message; } }
});
bedSelect.addEventListener('change', resetConfirmation);
bedSelect.addEventListener('change', updateBedActions);
bedSelect.addEventListener('change', () => { confirmButton.disabled = !(locationSelect.value && polyhouseSelect.value && bedSelect.value); });
confirmButton.addEventListener('click', () => {
    if (!(locationSelect.value && polyhouseSelect.value && bedSelect.value)) return;
    document.getElementById('selectedLocation').textContent = 'Location: ' + locationSelect.selectedOptions[0].text;
    document.getElementById('selectedPolyhouse').textContent = 'Polyhouse: ' + polyhouseSelect.selectedOptions[0].text;
    document.getElementById('selectedBed').textContent = 'Bed: ' + bedSelect.selectedOptions[0].text;
    summary.hidden = false;
    document.getElementById('entry').hidden = false;
    if (!document.getElementById('product').dataset.loaded) loadProducts();
    if (!cavitySelect.dataset.loaded) { loadCavities().then(() => { if (!cavitySelect.disabled) cavitySelect.dataset.loaded = '1'; }); }
});
async function loadProducts() {
    const sel = document.getElementById('product');
    try {
        const data = await fetchData('products', {});
        populate(sel, data, 'ProductID', 'ProductName', 'Select product');
        sel.dataset.loaded = '1';
    } catch (err) { errorBox.textContent = err.message; }
}
addProductButton.addEventListener('click', () => {
    if (busy || savedInCurrentBed === 0) return;
    if (hasUnsavedEntry() && !mayLeaveBed()) return;
    clearProductEntry();
    document.getElementById('saveStatus').textContent = 'Enter and save the next product in the same bed.';
    productSelect.focus();
});
async function postAction(action, fields) {
    const endpoint = new URL(window.location.href);
    endpoint.search = new URLSearchParams({k:accessKey,action}).toString();
    const response = await fetch(endpoint, {method:'POST',credentials:'same-origin',cache:'no-store',body:new URLSearchParams({csrf,locationid:locationSelect.value,polyhouseid:polyhouseSelect.value,bedname:bedSelect.value,...fields})});
    const result = await response.json();
    if (!response.ok || !result.ok) throw new Error(result.message || 'Unable to save bed status.');
    return result;
}
async function refreshBedStatuses() {
    if (!locationSelect.value || !polyhouseSelect.value) return;
    const data = await fetchData('bed_statuses',{locationid:locationSelect.value,polyhouseid:polyhouseSelect.value});
    bedStatuses = Object.fromEntries(data.map(x => [x.bedname,x.status]));
    const complete = data.filter(x => x.status === 'Complete').length;
    const empty = data.filter(x => x.status === 'Empty').length;
    const inProgress = data.filter(x => x.status === 'In Progress').length;
    const missing = data.filter(x => x.status === 'Not Checked').map(x => x.bedname);
    document.getElementById('bedOverview').hidden = false;
    document.getElementById('bedProgress').textContent = `${data.length} beds · ${complete} complete · ${empty} empty · ${inProgress} in progress · ${missing.length} not checked`;
    document.getElementById('missingBeds').textContent = missing.length ? 'Unverified beds: ' + missing.join(', ') : 'No unchecked beds. Review any In Progress beds before final confirmation.';
    updateBedActions();
}
async function markBed(status) {
    if (busy || !bedSelect.value || !mayLeaveBed()) return false;
    if (status === 'Empty' && !window.confirm('Confirm this bed has no saleable stock today?')) return false;
    busy = true; updateBedActions(); errorBox.textContent = '';
    try {
        const result = await postAction('mark_bed',{status});
        document.getElementById('saveStatus').textContent = result.message;
        await refreshBedStatuses();
        return true;
    } catch (err) { errorBox.textContent = err.message; return false; }
    finally { busy = false; updateBedActions(); }
}
function advanceBed() {
    if (bedSelect.selectedIndex < bedSelect.options.length - 1) {
        bedSelect.selectedIndex += 1;
        syncSelect2(bedSelect);
        resetBedProgress();
        document.getElementById('selectedBed').textContent = 'Bed: ' + bedSelect.selectedOptions[0].text;
        productSelect.focus();
    } else document.getElementById('saveStatus').textContent = 'Last configured bed reached. Review missing-bed warning below.';
    updateBedActions();
}
nextBedButton.addEventListener('click', async () => {
    if (await markBed('Complete')) advanceBed();
});
document.getElementById('emptyBed').addEventListener('click', async () => {
    if (await markBed('Empty')) advanceBed();
});
document.getElementById('save').addEventListener('click', async () => {
    const btn = document.getElementById('save');
    const status = document.getElementById('saveStatus');
    status.textContent = ''; errorBox.textContent = '';
    const productid = document.getElementById('product').value;
    const trays = document.getElementById('trays').value;
    const plants = document.getElementById('plants').value;
    if (!productid || !cavitySelect.value || !/^\d+$/.test(trays) || +trays <= 0 || !/^\d+$/.test(plants) || +plants <= 0) {
        errorBox.textContent = 'Choose a product and enter valid non-negative whole-number counts. Empty-bed verification is in Stage 4.'; return;
    }
    const payload = new URLSearchParams({csrf, locationid:locationSelect.value, polyhouseid:polyhouseSelect.value, bedname:bedSelect.value, productid, tray_id:cavitySelect.value, trays, plants});
    const endpoint = new URL(window.location.href); endpoint.search = new URLSearchParams({k:accessKey,action:'save'}).toString();
    busy = true; btn.disabled = true; updateBedActions();
    try {
        let response = await fetch(endpoint, {method:'POST',credentials:'same-origin',body:payload,cache:'no-store'});
        let result = await response.json();
        if (result.duplicate) {
            if (!window.confirm(result.message)) return;
            payload.set('duplicate_confirm','yes');
            response = await fetch(endpoint, {method:'POST',credentials:'same-origin',body:payload,cache:'no-store'});
            result = await response.json();
        }
        if (!response.ok || !result.ok) throw new Error(result.message || 'Save failed.');
        status.textContent = result.message + ' Entry #' + result.id;
        savedInCurrentBed += 1;
        clearProductEntry();
        await refreshBedStatuses();
    } catch (err) { errorBox.textContent = err.message; }
    finally { busy = false; updateBedActions(); }
});
$(document).on('select2:open', function () {
    setTimeout(function () {
        const searchInput = document.querySelector(
            '.select2-container--open .select2-search__field'
        );

        if (searchInput) {
            searchInput.focus();
        }
    }, 0);
});
initializeSelect2();
updateBedActions();
</script></body></html>
