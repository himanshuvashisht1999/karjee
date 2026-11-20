<?php
/**
 * Open Sale Verification and AJAX Synchronization Tool
 * Location: krf-orderandsales/temp/opensaleverification.php
 */

// Handle AJAX actions immediately before any stray output or notices
if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['ajax_sync_all', 'ajax_sync_single'])) {
    ob_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't echo PHP warnings/notices directly into JSON stream

// Establish database connection
if (file_exists(__DIR__ . '/../../connection.php')) {
    require_once __DIR__ . '/../../connection.php';
} elseif (file_exists('../../connection.php')) {
    require_once '../../connection.php';
} else {
    require_once 'connection.php';
}

$db = (isset($krfconn) && $krfconn instanceof mysqli) ? $krfconn : $conn;

// Ensure actual plant count calculation helper
if (!function_exists('calculateActualPlantInTray')) {
    function calculateActualPlantInTray($qtyOrTray, $failureCount = 0, $dispatchQty = 0) {
        if (!$qtyOrTray && $qtyOrTray !== 0) return 0;
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
}

// Attendance / location lookup helper
function resolveLocationName($hrmconn, $db, $locId) {
    if (empty($locId)) return 'Karjee';
    if (!empty($hrmconn)) {
        $stmt = $hrmconn->prepare("SELECT locationname FROM hrm_AttendanceLocationMaster WHERE locationid = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $locId);
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!empty($res['locationname'])) return $res['locationname'];
        }
    }
    // Fallback to krf_polyhouse_master
    $esc = $db->real_escape_string($locId);
    $q = $db->query("SELECT polyhousename FROM krf_polyhouse_master WHERE locationid = '{$esc}' LIMIT 1");
    if ($q && $r = $q->fetch_assoc()) {
        if (!empty($r['polyhousename'])) return $r['polyhousename'];
    }
    return $locId;
}

// -------------------------------------------------------------
// Pre-load Open Sale polyhouses & Attendance Location Masters
// Dynamically fetched from krf_polyhouse_master & hrm_AttendanceLocationMaster
// -------------------------------------------------------------
$openSalePolyhouses = [];
$ophRes = $db->query("
    SELECT 
        pm.polyhouseid, 
        pm.polyhousename, 
        pm.locationid, 
        pm.type, 
        pm.structure_type,
        IFNULL(alm.locationname, '') AS locationname
    FROM karjeefarm.krf_polyhouse_master pm
    LEFT JOIN hrmplus.hrm_AttendanceLocationMaster alm ON alm.locationid = pm.locationid
    WHERE pm.type = 'OpenSale' 
       OR pm.structure_type = 'OpenSale' 
       OR pm.polyhousename LIKE '%Open%Sale%'
    ORDER BY pm.polyhouseid ASC
");
if ($ophRes) {
    while ($row = $ophRes->fetch_assoc()) {
        // Build clean enum/display value: e.g. 'Karjee', 'Sargawa', 'Mansoon'
        $rawLoc = !empty($row['locationname']) ? $row['locationname'] : $row['polyhousename'];
        $cleanEnum = 'Karjee';
        if (stripos($rawLoc, 'Mansoon') !== false) {
            $cleanEnum = 'Mansoon';
        } elseif (stripos($rawLoc, 'Sargw') !== false || stripos($rawLoc, 'Sargaw') !== false) {
            $cleanEnum = 'Sargawa';
        } elseif (stripos($rawLoc, 'Karjee') !== false) {
            $cleanEnum = 'Karjee';
        } else {
            $cleanEnum = trim($rawLoc);
        }
        $row['enum_location'] = $cleanEnum;
        $openSalePolyhouses[$row['polyhouseid']] = $row;
    }
}

// -------------------------------------------------------------------------
// AJAX ENDPOINT FOR SYNCING MISSING TRAYS (BATCH OR SINGLE)
// -------------------------------------------------------------------------
if (isset($_REQUEST['action']) && in_array($_REQUEST['action'], ['ajax_sync_all', 'ajax_sync_single'])) {
    // Discard any output buffered prior to this endpoint
    if (ob_get_length()) ob_clean();
    header('Content-Type: application/json; charset=utf-8');

    $targetIds = [];
    if ($_REQUEST['action'] === 'ajax_sync_single') {
        $singleId = (int)($_REQUEST['polyhouseplantid'] ?? 0);
        if ($singleId > 0) $targetIds[] = $singleId;
    }

    // Build query for trays needing sync (strictly latest record per barcode)
    $whereConds = ["p.status = 'Move for Opensale'"];
    if (!empty($targetIds)) {
        $whereConds[] = "p.polyhouseplantid IN (" . implode(',', $targetIds) . ")";
    }

    $q = "
        SELECT 
            p.*,
            pm.polyhousename AS src_polyhousename,
            s.id AS opensale_stock_id
        FROM karjeefarm.krf_polyhouse_plant_details p
        INNER JOIN (
            SELECT barcode, MAX(polyhouseplantid) AS max_plantid
            FROM karjeefarm.krf_polyhouse_plant_details
            WHERE status = 'Move for Opensale'
            GROUP BY barcode
        ) latest_p ON latest_p.max_plantid = p.polyhouseplantid
        LEFT JOIN karjeefarm.krf_polyhouse_master pm ON pm.polyhouseid = p.polyhouseid
        LEFT JOIN karjeefarm.krf_opensale_stocks s ON (s.polyhouseplantid = p.polyhouseplantid OR s.barcode = p.barcode)
        WHERE " . implode(' AND ', $whereConds) . "
          AND s.id IS NULL
        ORDER BY p.polyhouseplantid DESC
    ";

    $res = $db->query($q);
    if (!$res) {
        echo json_encode(['status' => 'error', 'message' => 'Query error: ' . $db->error]);
        exit;
    }

    $missingRows = [];
    while ($r = $res->fetch_assoc()) {
        $missingRows[] = $r;
    }

    if (empty($missingRows)) {
        echo json_encode([
            'status' => 'success',
            'message' => 'No missing trays found. All trays are already synchronized!',
            'synced' => 0,
            'errors' => []
        ]);
        exit;
    }

    $insertStmt = $db->prepare("
        INSERT INTO karjeefarm.krf_opensale_stocks (
            polyhouseplantid, barcode, stockorder_no, psono, projectid, productid, productname, variety,
            trayid, tray_code, qtypertray, actual_plant_count, from_locationid, from_locationname,
            from_polyhouseid, from_polyhousename, from_bed_no, open_sale_location, opensale_locationid, movement_reason,
            status, remarks, created_by, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, 'Move for Opensale',
            'OpenSale', ?, ?, NOW(), NOW()
        ) ON DUPLICATE KEY UPDATE 
            polyhouseplantid    = VALUES(polyhouseplantid),
            status              = 'OpenSale',
            open_sale_location  = VALUES(open_sale_location),
            opensale_locationid = VALUES(opensale_locationid),
            movement_reason     = VALUES(movement_reason),
            actual_plant_count  = VALUES(actual_plant_count),
            from_locationid     = VALUES(from_locationid),
            from_locationname   = VALUES(from_locationname),
            from_polyhouseid    = VALUES(from_polyhouseid),
            from_polyhousename  = VALUES(from_polyhousename),
            from_bed_no         = VALUES(from_bed_no),
            remarks             = VALUES(remarks),
            updated_at          = NOW()
    ");

    if (!$insertStmt) {
        echo json_encode(['status' => 'error', 'message' => 'Prepare failed: ' . $db->error]);
        exit;
    }

    $synced = 0;
    $errors = [];

    foreach ($missingRows as $mTray) {
        $pPlantId = (int)$mTray['polyhouseplantid'];
        $pBarcode = trim((string)$mTray['barcode']);
        $pSoNo    = (string)($mTray['stockorder_no'] ?? '');
        $pPsoNo   = (string)($mTray['psono'] ?? '');
        $pProjId  = (int)($mTray['projectid'] ?: 17);
        $pProdId  = (string)($mTray['productid'] ?? '');
        $pProdName= (string)($mTray['productname'] ?? '');
        $pVariety = (string)($mTray['variety'] ?? '');
        $pTrayId  = (int)($mTray['tray_id'] ?: 1);
        $pTrayCode= (string)($mTray['tray_code'] ?? '');
        $pQty     = (int)($mTray['qtypertray'] ?: 0);
        $pActualCount = calculateActualPlantInTray($mTray);

        $pFromLocId = (string)($mTray['locationid'] ?? '');
        $pFromLocName = resolveLocationName($hrmconn ?? null, $db, $pFromLocId);

        $pPolyId  = (string)($mTray['polyhouseid'] ?? '');
        $pPolyName= (string)($mTray['src_polyhousename'] ?: ('PH-' . $pPolyId));
        $pBedNo   = (string)($mTray['bed_no'] ?? '');

        // -------------------------------------------------------------
        // Dynamically resolve Open Sale Location from krf_polyhouse_master
        // 1. Check if source polyhouse itself is an OpenSale polyhouse
        // 2. Or resolve against polyhouse master by matching movementremark
        // -------------------------------------------------------------
        $targetPolyhouseRow = null;

        // Check if the tray is already assigned to an OpenSale polyhouse (e.g. polyhouseid = 17, 18, 19)
        if (!empty($mTray['polyhouseid']) && isset($openSalePolyhouses[$mTray['polyhouseid']])) {
            $targetPolyhouseRow = $openSalePolyhouses[$mTray['polyhouseid']];
        }

        // If not, match from movement remarks against active open sale polyhouses
        $remarkText = (string)($mTray['movementremark'] ?? '');
        if (!$targetPolyhouseRow && !empty($remarkText)) {
            foreach ($openSalePolyhouses as $oph) {
                if (stripos($remarkText, $oph['polyhousename']) !== false || 
                    (!empty($oph['locationname']) && stripos($remarkText, $oph['locationname']) !== false)) {
                    $targetPolyhouseRow = $oph;
                    break;
                }
            }
        }

        // Default fallback to first active OpenSale polyhouse from database (no hardcoding)
        if (!$targetPolyhouseRow && !empty($openSalePolyhouses)) {
            $targetPolyhouseRow = reset($openSalePolyhouses);
        }

        $targetLocName = (string)($targetPolyhouseRow['polyhousename'] ?? 'Open Sale Karjee');
        $targetLocId   = (string)($targetPolyhouseRow['locationid'] ?? 'LOC6905e0fd5ab05');
        $enumLocation  = (string)($targetPolyhouseRow['enum_location'] ?? 'Karjee');

        $pCreatedBy = !empty($mTray['updated_by']) ? (string)$mTray['updated_by'] : (!empty($mTray['created_by']) ? (string)$mTray['created_by'] : 'Admin');
        $pRemark = !empty($remarkText) ? $remarkText : "[Auto-synced to Open Sale: {$targetLocName} on " . date('d-M-Y h:i A') . "]";

        $types = "isssisssisissssssssss";
        $insertStmt->bind_param(
            $types,
            $pPlantId, $pBarcode, $pSoNo, $pPsoNo, $pProjId, $pProdId, $pProdName, $pVariety,
            $pTrayId, $pTrayCode, $pQty, $pActualCount, $pFromLocId, $pFromLocName,
            $pPolyId, $pPolyName, $pBedNo, $enumLocation, $targetLocId,
            $pRemark, $pCreatedBy
        );

        if ($insertStmt->execute()) {
            $synced++;
        } else {
            $errors[] = "Barcode {$pBarcode} (ID {$pPlantId}): " . $insertStmt->error;
        }
    }
    $insertStmt->close();

    echo json_encode([
        'status' => empty($errors) ? 'success' : 'partial',
        'message' => "Successfully processed {$synced} of " . count($missingRows) . " tray(s) into krf_opensale_stocks.",
        'synced' => $synced,
        'total' => count($missingRows),
        'errors' => $errors
    ]);
    exit;
}

// -------------------------------------------------------------------------
// MAIN PAGE VIEW DATA FETCHING (LATEST RECORD PER BARCODE)
// -------------------------------------------------------------------------
$query = "
    SELECT 
        p.*,
        pm.polyhousename AS src_polyhousename,
        s.id AS opensale_stock_id,
        s.status AS opensale_status,
        s.actual_plant_count AS opensale_plant_count,
        s.open_sale_location AS opensale_location_name,
        s.opensale_locationid AS opensale_loc_id
    FROM karjeefarm.krf_polyhouse_plant_details p
    INNER JOIN (
        SELECT barcode, MAX(polyhouseplantid) AS max_plantid
        FROM karjeefarm.krf_polyhouse_plant_details
        WHERE status = 'Move for Opensale'
        GROUP BY barcode
    ) latest_p ON latest_p.max_plantid = p.polyhouseplantid
    LEFT JOIN karjeefarm.krf_polyhouse_master pm ON pm.polyhouseid = p.polyhouseid
    LEFT JOIN karjeefarm.krf_opensale_stocks s ON (s.polyhouseplantid = p.polyhouseplantid OR s.barcode = p.barcode)
    WHERE p.status = 'Move for Opensale'
    ORDER BY p.polyhouseplantid DESC
";

$result = $db->query($query);
$allTrays = [];
$missingTrays = [];
$existingTrays = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $allTrays[] = $row;
        if (empty($row['opensale_stock_id'])) {
            $missingTrays[] = $row;
        } else {
            $existingTrays[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Open Sale Verification & Synchronization</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- SweetAlert2 for modern interactive alerts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
    .card-stat { border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .table-container { background: #fff; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 20px; }
    .badge-status { font-weight: 600; padding: 6px 10px; font-size: 0.78rem; border-radius: 6px; }
    .table th { font-size: 0.82rem; text-transform: uppercase; color: #64748b; background: #f1f5f9; }
    .table td { font-size: 0.85rem; vertical-align: middle; }
    .btn-sync-spin i { transition: transform 0.3s; }
  </style>
</head>
<body class="py-4">
<div class="container-fluid px-4">
  
  <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
      <h3 class="fw-bold mb-1 text-primary"><i class="fa fa-boxes-packing"></i> Open Sale Verification & Synchronization</h3>
      <div class="text-muted small">Verify <code>krf_polyhouse_plant_details</code> (status: 'Move for Opensale') vs <code>krf_opensale_stocks</code></div>
    </div>
    <div class="d-flex gap-2">
      <a href="opensaleverification.php" class="btn btn-outline-secondary"><i class="fa fa-rotate"></i> Refresh</a>
      <?php if (!empty($missingTrays)): ?>
        <button type="button" class="btn btn-primary" id="btnSyncHeader" onclick="syncAllMissingTrays(<?= count($missingTrays) ?>)">
          <i class="fa fa-file-import"></i> Insert <?= count($missingTrays) ?> Missing into Open Sale Stocks
        </button>
      <?php else: ?>
        <button class="btn btn-success" disabled><i class="fa fa-check-circle"></i> All Trays Synchronized</button>
      <?php endif; ?>
    </div>
  </div>

  <!-- Stats Cards -->
  <div class="row g-3 mb-4">
    <div class="col-md-4">
      <div class="card card-stat p-3 bg-white border-start border-4 border-primary">
        <div class="d-flex justify-content-between">
          <div>
            <div class="text-muted small">Total 'Move for Opensale'</div>
            <h4 class="fw-bold mb-0 text-dark"><?= count($allTrays) ?></h4>
          </div>
          <div class="fs-2 text-primary opacity-50"><i class="fa fa-layer-group"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-stat p-3 bg-white border-start border-4 border-success">
        <div class="d-flex justify-content-between">
          <div>
            <div class="text-muted small">Present in <code>krf_opensale_stocks</code></div>
            <h4 class="fw-bold mb-0 text-success"><?= count($existingTrays) ?></h4>
          </div>
          <div class="fs-2 text-success opacity-50"><i class="fa fa-circle-check"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card card-stat p-3 bg-white border-start border-4 <?= !empty($missingTrays) ? 'border-danger' : 'border-secondary' ?>">
        <div class="d-flex justify-content-between">
          <div>
            <div class="text-muted small">Missing in <code>krf_opensale_stocks</code></div>
            <h4 class="fw-bold mb-0 <?= !empty($missingTrays) ? 'text-danger' : 'text-secondary' ?>" id="countMissingDisplay"><?= count($missingTrays) ?></h4>
          </div>
          <div class="fs-2 <?= !empty($missingTrays) ? 'text-danger' : 'text-secondary' ?> opacity-50"><i class="fa fa-triangle-exclamation"></i></div>
        </div>
      </div>
    </div>
  </div>

  <?php if (!empty($missingTrays)): ?>
    <!-- Missing Trays Table -->
    <div class="table-container mb-4 border border-danger" id="missingTraysContainer">
      <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h5 class="fw-bold text-danger mb-0">
          <i class="fa fa-circle-exclamation"></i> Missing Trays Required to Insert (<span id="missingTableCount"><?= count($missingTrays) ?></span>)
        </h5>
        <button type="button" class="btn btn-sm btn-danger" onclick="syncAllMissingTrays(<?= count($missingTrays) ?>)">
          <i class="fa fa-plus-circle"></i> Sync All Missing Now
        </button>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead>
            <tr>
              <th>Plant ID</th>
              <th>Barcode</th>
              <th>Stock Order</th>
              <th>Product Name</th>
              <th>Capacity</th>
              <th>Actual Plants</th>
              <th>Origin Location</th>
              <th>Origin Bed</th>
              <th>Movement Remark</th>
              <th style="width:140px; text-align:center;">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($missingTrays as $t): 
              $actualCount = calculateActualPlantInTray($t);
              $fromLocName = resolveLocationName($hrmconn ?? null, $db, $t['locationid']);
            ?>
              <tr id="row-plant-<?= $t['polyhouseplantid'] ?>">
                <td><strong><?= htmlspecialchars($t['polyhouseplantid']) ?></strong></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($t['barcode']) ?></span></td>
                <td><?= htmlspecialchars($t['stockorder_no'] ?: '-') ?></td>
                <td>
                  <div class="fw-bold text-dark"><?= htmlspecialchars($t['productname'] ?: '-') ?></div>
                  <div class="text-muted small"><?= htmlspecialchars($t['variety'] ?: '') ?></div>
                </td>
                <td><?= (int)$t['qtypertray'] ?></td>
                <td><span class="badge bg-success"><?= $actualCount ?> plants</span></td>
                <td>
                  <span class="badge bg-light text-dark border"><?= htmlspecialchars($fromLocName) ?></span>
                </td>
                <td>
                  <?= htmlspecialchars($t['src_polyhousename'] ?: ('PH-' . $t['polyhouseid'])) ?> &bull; <?= htmlspecialchars($t['bed_no'] ?: '-') ?>
                </td>
                <td style="max-width:250px;" class="small text-muted text-truncate" title="<?= htmlspecialchars($t['movementremark']) ?>">
                  <?= htmlspecialchars($t['movementremark'] ?: '-') ?>
                </td>
                <td style="text-align:center;">
                  <button type="button" class="btn btn-sm btn-outline-danger" onclick="syncSingleTray(<?= $t['polyhouseplantid'] ?>, '<?= htmlspecialchars($t['barcode']) ?>')">
                    <i class="fa fa-plus"></i> Insert
                  </button>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>

  <!-- All Trays Table -->
  <div class="table-container">
    <h5 class="fw-bold text-dark mb-3">
      <i class="fa fa-list-check"></i> All 'Move for Opensale' Records in <code>krf_polyhouse_plant_details</code> (<?= count($allTrays) ?>)
    </h5>
    <div class="table-responsive">
      <table class="table table-hover align-middle">
        <thead>
          <tr>
            <th>Plant ID</th>
            <th>Barcode</th>
            <th>Stock Order</th>
            <th>Product Name</th>
            <th>Origin (Nursery)</th>
            <th>Open Sale Center</th>
            <th>Available Plants</th>
            <th>Open Sale Status</th>
            <th>Sync State</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($allTrays as $t): 
            $isSynced = !empty($t['opensale_stock_id']);
            $fromLocName = resolveLocationName($hrmconn ?? null, $db, $t['locationid']);
          ?>
            <tr>
              <td><?= htmlspecialchars($t['polyhouseplantid']) ?></td>
              <td><span class="badge bg-dark"><?= htmlspecialchars($t['barcode']) ?></span></td>
              <td><?= htmlspecialchars($t['stockorder_no'] ?: '-') ?></td>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($t['productname'] ?: '-') ?></div>
                <div class="text-muted small"><?= htmlspecialchars($t['variety'] ?: '') ?></div>
              </td>
              <td>
                <span class="badge bg-light text-dark border"><?= htmlspecialchars($fromLocName) ?></span>
              </td>
              <td>
                <?php if ($isSynced): ?>
                  <span class="badge bg-primary"><?= htmlspecialchars($t['opensale_location_name'] ?: 'Open Sale') ?></span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($isSynced): ?>
                  <strong><?= (int)$t['opensale_plant_count'] ?></strong> plants
                <?php else: ?>
                  <span class="text-danger"><?= calculateActualPlantInTray($t) ?> plants (Pending)</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($isSynced): ?>
                  <span class="badge bg-info text-dark"><?= htmlspecialchars($t['opensale_status']) ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary">Unsynced</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($isSynced): ?>
                  <span class="badge bg-success badge-status"><i class="fa fa-check"></i> Synced (Stock ID: <?= $t['opensale_stock_id'] ?>)</span>
                <?php else: ?>
                  <span class="badge bg-danger badge-status"><i class="fa fa-xmark"></i> Missing</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<script>
async function parseJsonResponse(res) {
  const httpCode = res.status;
  const rawText = await res.text();
  try {
    return JSON.parse(rawText);
  } catch (err) {
    // If not valid JSON, capture exact response details for debugging
    let cleanMsg = rawText.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
    if (!cleanMsg) {
      cleanMsg = `HTTP Error ${httpCode}: Empty response from server.`;
    } else if (cleanMsg.length > 400) {
      cleanMsg = cleanMsg.substring(0, 400) + '...';
    }
    const fullError = `HTTP ${httpCode}: ${cleanMsg}`;
    throw new Error(fullError);
  }
}

function syncAllMissingTrays(count) {
  Swal.fire({
    title: 'Insert Missing Trays?',
    text: `Do you want to insert all ${count} missing tray(s) into krf_opensale_stocks?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#64748b',
    confirmButtonText: '<i class="fa fa-file-import"></i> Yes, Sync All Now'
  }).then((result) => {
    if (result.isConfirmed) {
      Swal.fire({
        title: 'Synchronizing Trays...',
        html: '<div class="text-muted">Please wait while missing trays are inserted into <code>krf_opensale_stocks</code>.</div>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      const formData = new FormData();
      formData.append('action', 'ajax_sync_all');

      const url = new URL(window.location.href);
      url.searchParams.set('action', 'ajax_sync_all');
      url.searchParams.delete('polyhouseplantid');

      fetch(url.toString(), {
        method: 'POST',
        body: formData
      })
      .then(parseJsonResponse)
      .then(data => {
        if (data.status === 'success') {
          Swal.fire({
            title: 'Synchronization Complete!',
            text: data.message,
            icon: 'success',
            confirmButtonColor: '#16a34a',
            confirmButtonText: 'OK'
          }).then(() => {
            window.location.href = window.location.pathname;
          });
        } else if (data.status === 'partial') {
          let errHtml = `<div class="mb-2">${data.message}</div>`;
          if (data.errors && data.errors.length) {
            errHtml += `<div class="p-2 bg-light text-danger text-start small border rounded" style="max-height:120px; overflow-y:auto;"><ul>`;
            data.errors.forEach(e => errHtml += `<li>${e}</li>`);
            errHtml += `</ul></div>`;
          }
          Swal.fire({
            title: 'Partial Synchronization',
            html: errHtml,
            icon: 'warning',
            confirmButtonColor: '#f59e0b',
            confirmButtonText: 'Refresh View'
          }).then(() => {
            window.location.href = window.location.pathname;
          });
        } else {
          Swal.fire({
            title: 'Sync Failed',
            text: data.message || 'Unknown server error during sync.',
            icon: 'error',
            confirmButtonColor: '#dc2626'
          });
        }
      })
      .catch(err => {
        Swal.fire({
          title: 'Server Error',
          html: `<div class="text-start p-2 bg-light text-danger small border rounded">${err.message}</div>`,
          icon: 'error',
          confirmButtonColor: '#dc2626'
        });
      });
    }
  });
}

function syncSingleTray(plantId, barcode) {
  Swal.fire({
    title: 'Insert Tray ' + barcode + '?',
    text: 'Insert this tray into krf_opensale_stocks?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#2563eb',
    cancelButtonColor: '#64748b',
    confirmButtonText: 'Yes, Insert'
  }).then((result) => {
    if (result.isConfirmed) {
      const formData = new FormData();
      formData.append('action', 'ajax_sync_single');
      formData.append('polyhouseplantid', plantId);

      const url = new URL(window.location.href);
      url.searchParams.set('action', 'ajax_sync_single');
      url.searchParams.set('polyhouseplantid', plantId);

      fetch(url.toString(), {
        method: 'POST',
        body: formData
      })
      .then(parseJsonResponse)
      .then(data => {
        if (data.status === 'success') {
          Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: `Tray ${barcode} synchronized!`,
            showConfirmButton: false,
            timer: 2000
          });
          const row = document.getElementById('row-plant-' + plantId);
          if (row) row.remove();
        } else {
          Swal.fire('Error', data.message, 'error');
        }
      })
      .catch(err => {
        Swal.fire('Server Error', err.message, 'error');
      });
    }
  });
}
</script>
</body>
</html>
