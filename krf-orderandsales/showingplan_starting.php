<?php
if (isset($_GET['ajax_action'])) {
    require_once(__DIR__ . '/../connection.php');
    header('Content-Type: application/json');

    $action = $_GET['ajax_action'];
    $projectid = $_POST['projectid'] ?? $_GET['projectid'] ?? '';

    if ($action === 'getBoxDetails') {
        $qrcode = trim($_POST['qrcode'] ?? '');
        if (empty($qrcode)) {
            echo json_encode(['status' => 'error', 'message' => 'Box QR Code is required']);
            exit;
        }

        // Fetch box details
        $stmt = $krfconn->prepare("
            SELECT boxid, qrcode, stockorderno, productid, productname, sowinglotno_internal, boxpackingdate, boxopeningdate, traycountinbox, allocated_traycount, status
            FROM krf_polyhouse_germinated_boxes 
            WHERE qrcode = ? AND (projectid = ? OR ? = 0 OR projectid = 17) AND (tedit IS NULL OR tedit = '') 
            LIMIT 1
        ");
        $stmt->bind_param("sii", $qrcode, $projectid, $projectid);
        $stmt->execute();
        $box = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$box) {
            echo json_encode(['status' => 'error', 'message' => 'Germination Box QR Code not found']);
            exit;
        }

        // Fetch trays linked to this box
        $trays = [];
        $stmt = $krfconn->prepare("
            SELECT sowingplantid, barcode, workingstatus, status, germination_qrcode, germinationboxid, stockorder_no, sowinglotno_internal, productname 
            FROM krf_polyhouse_sowingplant_details 
            WHERE germination_qrcode = ? AND (projectid = ? OR ? = 0 OR projectid = 17) AND (tedit IS NULL OR tedit = '') 
            ORDER BY sowingplantid ASC
        ");
        $stmt->bind_param("sii", $qrcode, $projectid, $projectid);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $trays[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'status' => 'success',
            'box' => $box,
            'trays' => $trays
        ]);
        exit;
    }

    if ($action === 'validateAddTray') {
        $query = trim($_POST['query'] ?? '');
        $boxQr = trim($_POST['box_qrcode'] ?? '');
        $boxSo = trim($_POST['stockorderno'] ?? '');
        $boxLot = trim($_POST['sowinglotno'] ?? '');

        if (empty($query)) {
            echo json_encode(['status' => 'error', 'message' => 'Please enter a valid Tray Barcode or Sowing Plant ID.']);
            exit;
        }

        // Clean query if URL or JSON
        if (strpos($query, 'http') === 0 || strpos($query, '://') !== false) {
            $parsedUrl = parse_url($query);
            if (!empty($parsedUrl['query'])) {
                parse_str($parsedUrl['query'], $urlParams);
                $query = $urlParams['barcode'] ?? $urlParams['code'] ?? $urlParams['sowingplantid'] ?? $query;
            }
        }
        $query = preg_replace('/[\x00-\x1F\x7F\xC2\xA0]/u', '', trim($query));

        $stmt = $krfconn->prepare("
            SELECT sowingplantid, barcode, workingstatus, status, germination_qrcode, germinationboxid, stockorder_no, sowinglotno_internal, productname
            FROM krf_polyhouse_sowingplant_details
            WHERE (barcode = ? OR sowingplantid = ?)
              AND (projectid = ? OR ? = 0 OR projectid = 17)
              AND (tedit IS NULL OR tedit = '')
            LIMIT 1
        ");
        $spIdParam = is_numeric($query) ? intval($query) : 0;
        $stmt->bind_param("siii", $query, $spIdParam, $projectid, $projectid);
        $stmt->execute();
        $tray = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$tray) {
            echo json_encode(['status' => 'error', 'message' => "Tray '$query' not found in database."]);
            exit;
        }

        // Check if already assigned to this box
        if (!empty($boxQr) && strcasecmp(trim($tray['germination_qrcode'] ?? ''), $boxQr) === 0) {
            echo json_encode(['status' => 'error', 'message' => "Tray '{$tray['barcode']}' (ID: {$tray['sowingplantid']}) is already assigned to this Box ($boxQr)."]);
            exit;
        }

        // Check terminal statuses
        $blockedStatuses = ['Dead', 'Removed', 'Final Dispatch', 'Internal Dispatch'];
        if (in_array($tray['status'], $blockedStatuses)) {
            echo json_encode(['status' => 'error', 'message' => "Tray '{$tray['barcode']}' cannot be added because its status is '{$tray['status']}'."]);
            exit;
        }

        $warning = '';
        if (!empty($tray['germination_qrcode']) && strcasecmp(trim($tray['germination_qrcode']), $boxQr) !== 0) {
            $warning = "Note: This tray is currently linked to Box '{$tray['germination_qrcode']}'. Adding it here will move it to this box.";
        }

        echo json_encode([
            'status' => 'success',
            'tray' => $tray,
            'warning' => $warning
        ]);
        exit;
    }

    if ($action === 'updateBoxTrays') {
        $qrcode = trim($_POST['qrcode'] ?? '');
        $traycount = intval($_POST['traycountinbox'] ?? 0);
        $trays = $_POST['trays'] ?? [];

        if (empty($qrcode) || $traycount <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid input data. Box QR code and a positive tray count are required.']);
            exit;
        }

        // Fetch existing box
        $bstmt = $krfconn->prepare("
            SELECT boxid, qrcode, stockorderno, boxpackingdate, boxopeningdate 
            FROM krf_polyhouse_germinated_boxes 
            WHERE qrcode = ? AND (projectid = ? OR ? = 0 OR projectid = 17) AND (tedit IS NULL OR tedit = '') 
            LIMIT 1
        ");
        $bstmt->bind_param("sii", $qrcode, $projectid, $projectid);
        $bstmt->execute();
        $box = $bstmt->get_result()->fetch_assoc();
        $bstmt->close();

        if (!$box) {
            echo json_encode(['status' => 'error', 'message' => 'Germination Box QR Code not found.']);
            exit;
        }

        $boxid = (string)($box['boxid'] ?? '');
        $boxPackingDate = $box['boxpackingdate'] ?: null;
        $boxOpeningDate = $box['boxopeningdate'] ?: null;

        // Process active trays
        $activeSowingPlantIds = [];
        $trayBarcodesMap = [];
        if (is_array($trays)) {
            foreach ($trays as $t) {
                $spId = intval($t['sowingplantid'] ?? 0);
                if ($spId > 0) {
                    $activeSowingPlantIds[] = $spId;
                    $trayBarcodesMap[$spId] = trim($t['barcode'] ?? '');
                }
            }
        }
        $activeSowingPlantIds = array_values(array_unique($activeSowingPlantIds));

        // If user specified traycountinbox smaller than active trays array, slice
        if ($traycount < count($activeSowingPlantIds)) {
            $activeSowingPlantIds = array_slice($activeSowingPlantIds, 0, $traycount);
        } else {
            $traycount = !empty($activeSowingPlantIds) ? count($activeSowingPlantIds) : $traycount;
        }

        $krfconn->begin_transaction();
        try {
            // 1. Update box count in krf_polyhouse_germinated_boxes
            $stmt = $krfconn->prepare("
                UPDATE krf_polyhouse_germinated_boxes 
                SET traycountinbox = ?,
                    allocated_traycount = ?
                WHERE qrcode = ? AND (projectid = ? OR ? = 0 OR projectid = 17) AND (tedit IS NULL OR tedit = '')
            ");
            $stmt->bind_param("iisii", $traycount, $traycount, $qrcode, $projectid, $projectid);
            $stmt->execute();
            $stmt->close();

            // 2. Clear germination_qrcode and germinationboxid from removed trays (previously having this boxqrcode but not in $activeSowingPlantIds)
            if (!empty($activeSowingPlantIds)) {
                $idList = implode(',', $activeSowingPlantIds);
                $clearSql = "
                    UPDATE krf_polyhouse_sowingplant_details 
                    SET germination_qrcode = NULL,
                        germinationboxid = NULL
                    WHERE germination_qrcode = ? 
                      AND sowingplantid NOT IN ({$idList})
                      AND (projectid = ? OR ? = 0 OR projectid = 17)
              AND (tedit IS NULL OR tedit = '')
                ";
                $clearStmt = $krfconn->prepare($clearSql);
                $clearStmt->bind_param("sii", $qrcode, $projectid, $projectid);
                $clearStmt->execute();
                $clearStmt->close();
            } else {
                $clearSql = "
                    UPDATE krf_polyhouse_sowingplant_details 
                    SET germination_qrcode = NULL,
                        germinationboxid = NULL
                    WHERE germination_qrcode = ?
                      AND (projectid = ? OR ? = 0 OR projectid = 17)
              AND (tedit IS NULL OR tedit = '')
                ";
                $clearStmt = $krfconn->prepare($clearSql);
                $clearStmt->bind_param("sii", $qrcode, $projectid, $projectid);
                $clearStmt->execute();
                $clearStmt->close();
            }

            // 3. For all kept/added trays in the box, update germination_qrcode, germinationboxid, and barcode
            if (!empty($activeSowingPlantIds)) {
                $updSql = "
                    UPDATE krf_polyhouse_sowingplant_details 
                    SET germination_qrcode = ?,
                        germinationboxid = ?,
                        packingdate_germbox = COALESCE(packingdate_germbox, ?),
                        openingdate_germbox = COALESCE(openingdate_germbox, ?),
                        barcode = CASE WHEN ? <> '' THEN ? ELSE barcode END
                    WHERE sowingplantid = ? AND (projectid = ? OR ? = 0 OR projectid = 17)
                      AND (tedit IS NULL OR tedit = '')
                ";
                $updStmt = $krfconn->prepare($updSql);
                foreach ($activeSowingPlantIds as $spId) {
                    $bc = $trayBarcodesMap[$spId] ?? '';
                    $updStmt->bind_param("ssssssiii", 
                        $qrcode, 
                        $boxid, 
                        $boxPackingDate, 
                        $boxOpeningDate, 
                        $bc, 
                        $bc, 
                        $spId, 
                        $projectid, 
                        $projectid
                    );
                    $updStmt->execute();
                }
                $updStmt->close();
            }

            $krfconn->commit();
            echo json_encode([
                'status' => 'success', 
                'message' => "Successfully updated Germination Box ($qrcode) to $traycount tray(s). Tray germination box QR codes synchronized."
            ]);
        } catch (Exception $e) {
            $krfconn->rollback();
            echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
        }
        exit;
    }
}

include('../header.php');
include('functions/product_functions.php');
include('functions/vendor_functions.php');
include('functions/seed_functions.php');

date_default_timezone_set("Asia/Calcutta");
$mdid = "";
if (isset($_GET['mdid'])) {
    $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
    $groupid = $dddr[0]['groupid'];
}
?>
<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<style>
    .select2-container--default .select2-selection--single {
        height: 44px !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.375rem !important;
        display: flex !important;
        align-items: center !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 42px !important;
        font-size: 1rem !important;
        color: #212529 !important;
        padding-left: 12px !important;
    }
    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 42px !important;
        right: 10px !important;
    }
    .select2-dropdown {
        z-index: 1075 !important;
        border: 1px solid #ced4da !important;
        box-shadow: 0 6px 16px rgba(0,0,0,0.15) !important;
    }
    .select2-search--dropdown .select2-search__field {
        border: 1px solid #ced4da !important;
        border-radius: 4px !important;
        padding: 8px 12px !important;
        font-size: 0.95rem !important;
    }
    #filterBox input {
        width: 200px;
    }
</style>

<style>
@media print {

    body * {
        display: none !important;
    }

    #printSection {
        display: block !important;
        position: fixed;
        left: 0;
        top: 0;
        width: 100%;
    }

    #printSection * {
        display: block !important;
    }

    /* Hide modal completely */
    .modal, .modal-backdrop {
        display: none !important;
    }
}
</style>
<style>

    .swal2-radio {
        display: flex !important;
        flex-direction: column !important;
        align-items: flex-start !important;
        gap: 12px !important;
        margin-top: 10px !important;
    }

    .lot-bg-1 {
        background: #f8f9fa !important;
        /* Light Gray */
    }

    .lot-bg-2 {
        background: #e8f4ff !important;
        /* Light Blue */
    }

    .lot-bg-3 {
        background: #e8ffe8 !important;
        /* Light Green */
    }

    .seedLotCard {
        border-left: 5px solid #0d6efd;
    }

    /* =========================================================================
       SOWING QR CODE MODAL - REDESIGNED RESPONSIVE APP-LIKE STYLING
       ========================================================================= */
    #sowingQRCodeModal .sowing-modal-header {
        background: #1976d2 !important;
        border-bottom: 0;
        padding: 16px 22px;
    }

    #sowingQRCodeModal .sowing-header-icon-wrap {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.18);
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: #ffffff;
        flex-shrink: 0;
    }

    #sowingQRCodeModal .sowing-chip {
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        color: #ffffff;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.2px;
    }

    #sowingQRCodeModal .sowing-chip i {
        font-size: 0.74rem;
    }

    #sowingQRCodeModal .sowing-card {
        background: #ffffff;
        border: 1px solid #eef2f6 !important;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04) !important;
        overflow: hidden;
    }

    #sowingQRCodeModal .sowing-card-title {
        color: #1976d2 !important;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.2px;
    }

    #sowingQRCodeModal .sowing-field-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 5px;
        display: block;
    }

    #sowingQRCodeModal .sowing-input-group {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    #sowingQRCodeModal .sowing-input-group:focus-within {
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.15);
    }

    #sowingQRCodeModal .sowing-input-group .input-group-text {
        background: #ffffff;
        border: none;
        color: #64748b;
        font-size: 0.92rem;
        padding: 0 10px 0 12px;
    }

    #sowingQRCodeModal .sowing-input-group .form-control,
    #sowingQRCodeModal .sowing-input-group .form-select {
        border: none;
        font-weight: 600;
        color: #1e293b;
        padding-left: 4px;
        box-shadow: none !important;
        min-height: 44px;
    }

    #sowingQRCodeModal .sowing-input-group .bg-readonly {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        font-weight: 700;
    }

    #sowingQRCodeModal .sowing-stat-badge {
        background: #ffffff;
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        padding: 6px 14px;
        text-align: center;
        min-width: 135px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    #sowingQRCodeModal .sowing-stat-badge-lbl {
        font-size: 0.63rem;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        display: block;
        line-height: 1.2;
        margin-bottom: 2px;
    }

    #sowingQRCodeModal .sowing-stat-badge-val {
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.1;
        display: block;
    }

    #sowingQRCodeModal .sowing-seed-search-group {
        border: 1.5px solid #cbd5e1;
        border-radius: 12px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    #sowingQRCodeModal .sowing-seed-search-group:focus-within {
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.15);
    }

    #sowingQRCodeModal .sowing-seed-search-group .input-group-text {
        background: #ffffff;
        border: none;
        padding-left: 14px;
        font-size: 1.05rem;
        color: #64748b;
    }

    #sowingQRCodeModal .sowing-seed-search-group .form-select {
        border: none;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1e293b;
        min-height: 48px;
        box-shadow: none !important;
    }

    #sowingQRCodeModal .sowing-close-btn {
        border-radius: 8px;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-weight: 600;
        padding: 8px 26px;
        transition: all 0.2s ease;
    }

    #sowingQRCodeModal .sowing-close-btn:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #94a3b8;
    }

    #sowingQRCodeModal #AddLotNoRow {
        border-radius: 8px;
        font-weight: 600;
        padding: 8px 24px;
    }

    /* Dynamic lot cards styling */
    #sowingQRCodeModal .seedLotCard {
        border: 1.5px solid #e2e8f0 !important;
        border-left: 5px solid #1976d2 !important;
        border-radius: 12px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
        transition: box-shadow 0.2s ease;
        padding: 14px !important;
    }

    #sowingQRCodeModal .seedLotCard:hover {
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08) !important;
    }

    #sowingQRCodeModal .seedLotCard .form-control {
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        font-weight: 600;
        min-height: 38px;
    }

    #sowingQRCodeModal .seedLotCard .form-control:focus {
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.15);
    }

    #sowingQRCodeModal .seedLotCard button {
        border-radius: 8px;
        font-weight: 600;
        min-height: 38px;
    }

    /* Mobile App Viewport (< 768px) */
    @media (max-width: 768px) {
        #sowingQRCodeModal.modal {
            padding: 0 !important;
        }
        #sowingQRCodeModal .modal-dialog {
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            height: 100% !important;
            max-height: 100% !important;
            display: flex;
            align-items: flex-end;
        }
        #sowingQRCodeModal .modal-content {
            height: 100vh !important;
            max-height: 100vh !important;
            border-radius: 0 !important;
            border: none !important;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #sowingQRCodeModal .sowing-app-handle-bar {
            background: #1976d2;
            text-align: center;
            padding: 6px 0 2px 0;
        }
        #sowingQRCodeModal .sowing-app-handle {
            display: inline-block;
            width: 40px;
            height: 4px;
            background: rgba(255, 255, 255, 0.45);
            border-radius: 4px;
        }
        #sowingQRCodeModal .sowing-modal-header {
            position: sticky;
            top: 0;
            z-index: 1050;
            padding: 10px 14px 14px 14px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }
        #sowingQRCodeModal .sowing-main-title {
            font-size: 1.05rem !important;
        }
        #sowingQRCodeModal .sowing-meta-pills {
            gap: 4px !important;
        }
        #sowingQRCodeModal .sowing-chip {
            padding: 2px 8px;
            font-size: 0.72rem;
        }
        #sowingQRCodeModal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 12px !important;
            background: #f1f5f9 !important;
        }
        #sowingQRCodeModal .form-control,
        #sowingQRCodeModal .form-select {
            font-size: 16px !important; /* Prevents auto-zoom on iOS Safari */
        }
        #sowingQRCodeModal .sowing-lot-header .d-flex {
            flex-direction: column;
            align-items: stretch !important;
            gap: 10px !important;
        }
        #sowingQRCodeModal .sowing-stat-badges-wrap {
            width: 100% !important;
            display: flex !important;
            gap: 8px !important;
        }
        #sowingQRCodeModal .sowing-stat-badge {
            flex: 1 1 0 !important;
            min-width: 0 !important;
            padding: 6px 4px !important;
        }
        #sowingQRCodeModal .sowing-stat-badge-lbl {
            font-size: 0.58rem !important;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        #sowingQRCodeModal .sowing-stat-badge-val {
            font-size: 1.15rem !important;
        }
        #sowingQRCodeModal .sowing-modal-footer {
            position: sticky;
            bottom: 0;
            background: #ffffff;
            padding: 10px 14px !important;
            margin: 12px -12px -12px -12px !important;
            border-top: 1px solid #e2e8f0 !important;
            box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.06);
            z-index: 1040;
        }
        #sowingQRCodeModal .sowing-modal-footer button {
            flex: 1 1 auto;
            min-height: 44px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        #sowingQRCodeModal .seedLotCard {
            padding: 14px !important;
            border-radius: 12px !important;
            background: #ffffff !important;
        }
        #sowingQRCodeModal .seedLotCard .row {
            --bs-gutter-y: 0.5rem;
        }
        #sowingQRCodeModal .seedLotCard label {
            font-size: 0.74rem !important;
            font-weight: 700 !important;
            color: #64748b !important;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 2px !important;
        }
        #sowingQRCodeModal .seedLotCard label small {
            font-size: 0.65rem !important;
            text-transform: none;
            color: #94a3b8;
        }
        #sowingQRCodeModal .seedLotCard .col-md-3,
        #sowingQRCodeModal .seedLotCard .col-sm-3:first-child {
            width: 100% !important;
        }
        #sowingQRCodeModal .seedLotCard .col-md-2,
        #sowingQRCodeModal .seedLotCard .col-sm-3:nth-child(2),
        #sowingQRCodeModal .seedLotCard .col-sm-3:nth-child(3) {
            width: 50% !important;
        }
        #sowingQRCodeModal .seedLotCard .col-md-5,
        #sowingQRCodeModal .seedLotCard .col-sm-3:nth-child(4),
        #sowingQRCodeModal .seedLotCard .col-sm-3:last-child {
            width: 100% !important;
            margin-top: 8px !important;
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
        }
        #sowingQRCodeModal .seedLotCard button {
            flex: 1 1 auto !important;
            min-height: 42px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 0.85rem !important;
        }
    }

    /* =========================================================================
       GERMINATION PACKING QR CODE MODAL - REDESIGNED RESPONSIVE APP-LIKE STYLING
       ========================================================================= */
    #germpackingQRCodeModal .germ-modal-header {
        background: #1976d2 !important;
        border-bottom: 0;
        padding: 16px 22px;
    }

    #germpackingQRCodeModal .germ-header-icon-wrap {
        width: 36px;
        height: 36px;
        background: rgba(255, 255, 255, 0.18);
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        color: #ffffff;
        flex-shrink: 0;
    }

    #germpackingQRCodeModal .germ-chip {
        display: inline-flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        color: #ffffff;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.78rem;
        font-weight: 500;
        letter-spacing: 0.2px;
    }

    #germpackingQRCodeModal .germ-chip i {
        font-size: 0.74rem;
    }

    #germpackingQRCodeModal .germ-refresh-btn {
        background: rgba(255, 255, 255, 0.2) !important;
        border: 1px solid rgba(255, 255, 255, 0.35) !important;
        border-radius: 8px !important;
        font-weight: 600 !important;
        padding: 6px 14px !important;
        transition: background 0.2s ease;
    }

    #germpackingQRCodeModal .germ-refresh-btn:hover {
        background: rgba(255, 255, 255, 0.3) !important;
    }

    #germpackingQRCodeModal .germ-card {
        background: #ffffff;
        border: 1px solid #eef2f6 !important;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04) !important;
        overflow: hidden;
    }

    #germpackingQRCodeModal .germ-card-title {
        color: #1976d2 !important;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.2px;
    }

    #germpackingQRCodeModal .germ-field-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #64748b;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 5px;
        display: block;
    }

    #germpackingQRCodeModal .germ-input-group {
        border: 1.5px solid #e2e8f0;
        border-radius: 10px;
        overflow: hidden;
        background: #ffffff;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    #germpackingQRCodeModal .germ-input-group:focus-within {
        border-color: #1976d2;
        box-shadow: 0 0 0 3px rgba(25, 118, 210, 0.15);
    }

    #germpackingQRCodeModal .germ-input-group .input-group-text {
        background: #ffffff;
        border: none;
        color: #64748b;
        font-size: 0.92rem;
        padding: 0 10px 0 12px;
    }

    #germpackingQRCodeModal .germ-input-group .form-control {
        border: none;
        font-weight: 600;
        color: #1e293b;
        padding-left: 4px;
        box-shadow: none !important;
        min-height: 44px;
    }

    #germpackingQRCodeModal .germ-input-group .bg-readonly {
        background-color: #f8fafc !important;
        color: #0f172a !important;
        font-weight: 700;
    }

    #germpackingQRCodeModal .germ-hero-stat-card {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border: 1.5px solid #bfdbfe;
        border-radius: 14px;
        padding: 16px 20px;
        box-shadow: 0 2px 6px rgba(37, 99, 235, 0.05);
    }

    #germpackingQRCodeModal .germ-hero-stat-label {
        font-size: 0.78rem;
        font-weight: 700;
        color: #1e40af;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        display: block;
        margin-bottom: 4px;
    }

    #germpackingQRCodeModal .germ-hero-stat-val {
        font-size: 2.2rem;
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.5px;
    }

    #germpackingQRCodeModal .germ-hero-stat-sub {
        font-size: 0.78rem;
        margin-top: 4px;
        color: #64748b;
    }

    #germpackingQRCodeModal .day-suggest-pill {
        border: 1px solid #cbd5e1;
        background: #f8fafc;
        color: #334155;
        border-radius: 20px;
        font-size: 0.72rem;
        font-weight: 600;
        padding: 2px 9px;
        cursor: pointer;
        line-height: 1.35;
        transition: all 0.15s ease;
        outline: none;
        user-select: none;
    }

    #germpackingQRCodeModal .day-suggest-pill:hover {
        background: #e0f2fe;
        color: #0369a1;
        border-color: #7dd3fc;
        transform: translateY(-1px);
    }

    #germpackingQRCodeModal .day-suggest-pill.active {
        background: #0d6efd !important;
        color: #ffffff !important;
        border-color: #0d6efd !important;
        box-shadow: 0 2px 6px rgba(13, 110, 253, 0.35);
    }

    #germpackingQRCodeModal .germ-btn-save {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        border: none;
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.92rem;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
        min-height: 44px;
        padding: 10px 24px;
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }

    #germpackingQRCodeModal .germ-btn-save:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
        box-shadow: 0 6px 16px rgba(16, 185, 129, 0.35);
        transform: translateY(-1px);
    }

    #germpackingQRCodeModal .germ-close-btn {
        border-radius: 10px;
        border: 1.5px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-weight: 600;
        padding: 10px 24px;
        min-height: 44px;
        transition: all 0.2s ease;
    }

    #germpackingQRCodeModal .germ-close-btn:hover {
        background: #f1f5f9;
        color: #1e293b;
        border-color: #94a3b8;
    }

    /* Batch box card styling */
    #germpackingQRCodeModal .genratedboxqrcode {
        border-radius: 14px !important;
        border: 1.5px solid #e2e8f0 !important;
        border-left: 5px solid #1976d2 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04) !important;
        padding: 16px !important;
        margin-bottom: 14px !important;
    }

    /* Mobile media query for germpackingQRCodeModal */
    @media (max-width: 768px) {
        #germpackingQRCodeModal.modal {
            padding: 0 !important;
        }
        #germpackingQRCodeModal .modal-dialog {
            margin: 0 !important;
            max-width: 100% !important;
            width: 100% !important;
            height: 100% !important;
            max-height: 100% !important;
            display: flex;
            align-items: flex-end;
        }
        #germpackingQRCodeModal .modal-content {
            height: 100vh !important;
            max-height: 100vh !important;
            border-radius: 0 !important;
            border: none !important;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #germpackingQRCodeModal .germ-app-handle-bar {
            background: #1976d2;
            text-align: center;
            padding: 6px 0 2px 0;
        }
        #germpackingQRCodeModal .germ-app-handle {
            display: inline-block;
            width: 40px;
            height: 4px;
            background: rgba(255, 255, 255, 0.45);
            border-radius: 4px;
        }
        #germpackingQRCodeModal .germ-modal-header {
            position: sticky;
            top: 0;
            z-index: 1050;
            padding: 10px 14px 14px 14px !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        }
        #germpackingQRCodeModal .germ-main-title {
            font-size: 1.05rem !important;
        }
        #germpackingQRCodeModal .germ-meta-pills {
            gap: 4px !important;
        }
        #germpackingQRCodeModal .germ-chip {
            padding: 2px 8px;
            font-size: 0.72rem;
        }
        #germpackingQRCodeModal .modal-body {
            flex: 1 1 auto;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            padding: 12px !important;
            background: #f1f5f9 !important;
        }
        #germpackingQRCodeModal .form-control {
            font-size: 16px !important; /* Prevents auto-zoom on iOS */
        }
        #germpackingQRCodeModal .germ-hero-stat-val {
            font-size: 1.8rem !important;
        }
        #germpackingQRCodeModal .germ-modal-footer {
            position: sticky;
            bottom: 0;
            background: #ffffff;
            padding: 10px 14px !important;
            margin: 12px -12px -12px -12px !important;
            border-top: 1px solid #e2e8f0 !important;
            box-shadow: 0 -3px 12px rgba(0, 0, 0, 0.06);
            z-index: 1040;
            display: flex !important;
            gap: 8px !important;
        }
        #germpackingQRCodeModal .germ-modal-footer button {
            flex: 1 1 auto;
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        #germpackingQRCodeModal .genratedboxqrcode .row {
            flex-direction: column !important;
            align-items: stretch !important;
            gap: 12px !important;
        }
        #germpackingQRCodeModal .genratedboxqrcode .col-md-5 {
            text-align: left !important;
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
        }
        #germpackingQRCodeModal .genratedboxqrcode button {
            flex: 1 1 auto !important;
            min-height: 42px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            margin-left: 0 !important;
        }
    }

    /* Header gradient */
    .smart-modal-header {
        background: linear-gradient(135deg, #4e73df, #1cc88a);
        color: #fff;
    }

    /* Body gradient */
    .smart-modal-body {
        background: linear-gradient(180deg, #f8f9fc, #eef1f7);
    }

    /* Labels */
    .smart-modal-body label {
        font-weight: 600;
        font-size: 14px;
    }

    /* Button */
    .smart-confirm-btn {
        background: linear-gradient(135deg, #1cc88a, #17a673);
        border: none;
        padding: 10px 28px;
        font-weight: 600;
        color: #fff;
    }

    .smart-confirm-btn:hover {
        opacity: 0.9;
    }

    /* Mobile compact */
    @media (max-width: 768px) {
        .smart-confirm-btn {
            width: 100%;
        }
    }
</style>

<!-- Bootstrap CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> -->

<!-- ✅ QR Code Scanner Library -->
<script src="https://unpkg.com/html5-qrcode@2.3.7/html5-qrcode.min.js"></script>
<style>
    #AllocationReaderId {
        width: 100%;
        max-width: 300px;
        margin: 20px auto;
        border: 2px solid #0d6efd;
        border-radius: 10px;
        padding: 10px;
    }

    #AllocationBoxDetailsInfoToPH {
        display: none;
    }

    /* ==========================================================================
       RESPONSIVE STYLES: Update Trays in Germination Box Modal
       ========================================================================== */
    #updateTraysInBoxModal .modal-dialog {
        transition: transform 0.25s ease-out;
    }

    #updateTraysInBoxModal .modal-content {
        border: none;
        border-radius: 14px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        overflow: hidden;
    }

    #updateTraysInBoxModal .modal-header {
        background: linear-gradient(135deg, #0d6efd, #0b5ed7) !important;
        border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        padding: 0.85rem 1.25rem;
    }

    #updateTraysInBoxModal .modal-title {
        font-weight: 700;
        font-size: 1.12rem;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
    }

    #updateTraysInBoxModal .box-info-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 9px 12px;
        height: 100%;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        transition: all 0.2s ease;
    }

    #updateTraysInBoxModal .box-info-card:hover {
        border-color: #93c5fd;
        box-shadow: 0 3px 8px rgba(13, 110, 253, 0.08);
    }

    #updateTraysInBoxModal .box-info-label {
        font-size: 0.72rem;
        color: #64748b;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin-bottom: 3px;
        display: flex;
        align-items: center;
    }

    #updateTraysInBoxModal .box-info-value {
        font-weight: 700;
        font-size: clamp(0.92rem, 2vw, 1.15rem);
        color: #0f172a;
        word-break: break-word;
        line-height: 1.3;
    }

    #updateTraysInBoxModal .tray-barcode-input {
        font-family: 'JetBrains Mono', Consolas, Monaco, monospace;
        font-size: 0.88rem;
        font-weight: 600;
        letter-spacing: 0.3px;
        background-color: #ffffff;
        border-color: #cbd5e1;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    #updateTraysInBoxModal .tray-barcode-input:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
    }

    /* Desktop Table Header & Cells */
    #updateTraysInBoxModal #boxTraysTable thead th {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #475569;
        background-color: #f1f5f9;
        border-bottom: 2px solid #cbd5e1;
        padding: 10px 12px;
        white-space: nowrap;
    }

    #updateTraysInBoxModal #boxTraysTable tbody td {
        padding: 8px 12px;
        vertical-align: middle;
        font-size: 0.875rem;
    }

    #updateTraysInBoxModal #boxTraysTable tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Hide mobile card meta by default on desktop/tablets */
    .tray-card-meta {
        display: none !important;
    }

    /* Tablets and Small Laptops */
    @media (max-width: 991.98px) {
        #updateTraysInBoxModal .modal-dialog {
            max-width: 95vw;
            margin: 0.5rem auto;
        }
    }

    /* Mobile Devices (<= 767.98px) */
    @media (max-width: 767.98px) {
        #updateTraysInBoxModal .modal-dialog {
            margin: 0.25rem auto;
            max-width: 98vw;
            height: calc(100dvh - 0.5rem);
        }

        #updateTraysInBoxModal .modal-content {
            height: 100%;
            max-height: calc(100dvh - 0.5rem);
            border-radius: 12px;
        }

        #updateTraysInBoxModal .modal-header {
            padding: 0.65rem 0.85rem;
        }

        #updateTraysInBoxModal .modal-title {
            font-size: 0.98rem;
        }

        #updateTraysInBoxModal .modal-body {
            padding: 0.55rem !important;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        #updateTraysInBoxModal .card {
            border-radius: 10px !important;
            margin-bottom: 0.55rem !important;
        }

        #updateTraysInBoxModal .card-body {
            padding: 0.65rem !important;
        }

        #updateTraysInBoxModal .card-header {
            padding: 0.55rem 0.65rem !important;
        }

        #updateTraysInBoxModal .modal-footer {
            padding: 0.45rem 0.65rem;
        }

        #updateTraysInBoxModal .form-control-lg,
        #updateTraysInBoxModal .btn-lg {
            font-size: 0.88rem;
            padding: 0.45rem 0.65rem;
            min-height: 40px;
        }

        #updateTraysInBoxModal .box-qr-input-group {
            flex-wrap: nowrap;
        }

        #updateTraysInBoxModal #boxQrInput {
            font-size: 0.85rem;
        }

        #updateTraysInBoxModal #btnSearchBoxQr {
            white-space: nowrap;
            padding: 0.45rem 0.75rem;
            min-height: 40px;
            font-size: 0.88rem;
        }

        #updateTraysInBoxModal #btnToggleBoxCamera {
            min-height: 40px;
            font-size: 0.88rem;
            padding: 0.45rem 0.65rem;
        }

        #updateTraysInBoxModal .box-info-card {
            padding: 6px 8px;
            border-radius: 8px;
        }

        #updateTraysInBoxModal .box-info-label {
            font-size: 0.68rem;
            margin-bottom: 2px;
        }

        #updateTraysInBoxModal .box-info-value {
            font-size: 0.88rem;
        }

        /* --- Transform Table into Responsive Cards on Mobile --- */
        #updateTraysInBoxModal #boxTraysTable thead {
            display: none !important; /* Completely hide thead on mobile to eliminate any overlapping! */
        }

        #updateTraysInBoxModal #boxTraysTable,
        #updateTraysInBoxModal #boxTraysTable tbody,
        #updateTraysInBoxModal #boxTraysTable tr.tray-row {
            display: block !important;
            width: 100% !important;
            min-width: 0 !important;
        }

        #updateTraysInBoxModal #boxTraysTable tr.tray-row {
            background: #ffffff;
            border: 1px solid #e2e8f0 !important;
            border-radius: 10px;
            padding: 8px 10px;
            margin-bottom: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
        }

        #updateTraysInBoxModal #boxTraysTable tr.tray-row td.tray-col-sno,
        #updateTraysInBoxModal #boxTraysTable tr.tray-row td.tray-col-plantid,
        #updateTraysInBoxModal #boxTraysTable tr.tray-row td.tray-col-wstatus,
        #updateTraysInBoxModal #boxTraysTable tr.tray-row td.tray-col-status,
        #updateTraysInBoxModal #boxTraysTable tr.tray-row td.tray-col-action {
            display: none !important;
        }

        #updateTraysInBoxModal #boxTraysTable tr.tray-row td.tray-col-barcode {
            display: block !important;
            width: 100% !important;
            padding: 0 !important;
            border: none !important;
        }

        .tray-card-meta {
            display: block !important;
        }

        #updateTraysInBoxModal .tray-barcode-input {
            font-size: 0.84rem;
            padding: 0.35rem 0.5rem;
            min-height: 38px;
            width: 100%;
        }

        #updateTraysInBoxModal .table-responsive {
            overflow: visible !important;
            border: none !important;
            max-height: none !important;
        }
    }

    /* Small Mobile Phones (<= 575.98px) */
    @media (max-width: 575.98px) {
        #updateTraysInBoxModal .modal-header {
            padding: 0.55rem 0.75rem;
        }

        #updateTraysInBoxModal .modal-title {
            font-size: 0.92rem;
        }

        #updateTraysInBoxModal .modal-body {
            padding: 0.45rem !important;
        }

        #updateTraysInBoxModal .card-body {
            padding: 0.55rem !important;
        }

        #updateTraysInBoxModal .box-info-card {
            padding: 5px 7px;
            border-radius: 7px;
        }

        #updateTraysInBoxModal .box-info-value {
            font-size: 0.82rem;
        }

        #updateTraysInBoxModal #btnSearchBoxQr span.btn-text-full {
            display: none !important;
        }

        #updateTraysInBoxModal #btnSearchBoxQr span.btn-text-short {
            display: inline !important;
        }

        #updateTraysInBoxModal #btnToggleBoxCamera {
            font-size: 0.82rem;
        }
    }
</style>
<style>
    /* Sowing Dashboard & Summary Cards Styling */
    .sowing-stat-card {
        border-radius: 12px;
        transition: transform 0.22s ease, box-shadow 0.22s ease, background-color 0.25s ease;
        background: #ffffff;
        cursor: pointer;
        position: relative;
    }
    .sowing-stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0,0,0,0.1) !important;
    }

    /* Active Card Animated Border Keyframes */
    @keyframes activeCardBorderAnimation {
        0% {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.32), 0 8px 20px rgba(37, 99, 235, 0.2) !important;
        }
        20% {
            border-color: #7c3aed !important;
            box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.32), 0 8px 20px rgba(124, 58, 237, 0.2) !important;
        }
        40% {
            border-color: #ec4899 !important;
            box-shadow: 0 0 0 3px rgba(236, 72, 153, 0.32), 0 8px 20px rgba(236, 72, 153, 0.2) !important;
        }
        60% {
            border-color: #f59e0b !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.32), 0 8px 20px rgba(245, 158, 11, 0.2) !important;
        }
        80% {
            border-color: #10b981 !important;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.32), 0 8px 20px rgba(16, 185, 129, 0.2) !important;
        }
        100% {
            border-color: #2563eb !important;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.32), 0 8px 20px rgba(37, 99, 235, 0.2) !important;
        }
    }

    /* Ensure SweetAlert2 confirmation dialogs always appear on top of Bootstrap modals */
    body .swal2-container,
    body div.swal2-container,
    .swal2-container,
    .swal2-top-layer {
        z-index: 999999 !important;
    }

    /* Active Card Styling: Differing Background & Animated Border */
    .sowing-stat-card.active-card {
        border: 2.5px solid #2563eb !important;
        border-left-width: 5px !important;
        border-radius: 12px !important;
        animation: activeCardBorderAnimation 3s linear infinite !important;
        transform: translateY(-3px) scale(1.02) !important;
        z-index: 2;
    }

    /* Distinct active background colors per card type */
    .sowing-stat-card.active-card {
        background-color: #e0f2fe !important; /* Fallback / Default soft blue */
    }
    .sowing-stat-card[data-cardfilter="all"].active-card {
        background-color: #e0f2fe !important; /* Soft sky blue */
    }
    .sowing-stat-card[data-cardfilter="orderqty"].active-card {
        background-color: #e0f7fa !important; /* Soft ice cyan */
    }
    .sowing-stat-card[data-cardfilter="completed"].active-card {
        background-color: #dcfce7 !important; /* Soft fresh light green */
    }
    .sowing-stat-card[data-cardfilter="pending"].active-card {
        background-color: #fef3c7 !important; /* Soft warm light amber/yellow */
    }
    .sowing-stat-card[data-cardfilter="client"].active-card {
        background-color: #ede9fe !important; /* Soft royal lavender/purple */
    }
    .sowing-stat-card[data-cardfilter="grafted"].active-card {
        background-color: #ccfbf1 !important; /* Soft vibrant mint/teal */
    }
    .sowing-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .filter-panel {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
    }
    .sowing-table-card {
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.04);
    }
    #sowingplandetaisls thead th {
        background: #f1f5f9;
        color: #334155;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        vertical-align: middle;
        border-bottom: 2px solid #cbd5e1;
        padding: 10px 8px;
    }
    #sowingplandetaisls tbody td {
        vertical-align: middle;
        padding: 8px 8px;
        font-size: 0.88rem;
        color: #000000 !important;
    }
    /* Status-based Row Background & Text Styling */
    .sowing-row-completed,
    #sowingplandetaisls tbody tr.sowing-row-completed,
    #sowingplandetaisls tbody tr.sowing-row-completed td {
        background-color: #dcfce7 !important;
        --bs-table-bg: #dcfce7 !important;
        --bs-table-accent-bg: #dcfce7 !important;
        color: #000000 !important;
    }
    .sowing-row-incomplete,
    #sowingplandetaisls tbody tr.sowing-row-incomplete,
    #sowingplandetaisls tbody tr.sowing-row-incomplete td {
        background-color: #ffedd5 !important;
        --bs-table-bg: #ffedd5 !important;
        --bs-table-accent-bg: #ffedd5 !important;
        color: #000000 !important;
    }
    .sowing-row-pending,
    #sowingplandetaisls tbody tr.sowing-row-pending,
    #sowingplandetaisls tbody tr.sowing-row-pending td {
        background-color: #ffffff !important;
        --bs-table-bg: #ffffff !important;
        --bs-table-accent-bg: #ffffff !important;
        color: #000000 !important;
    }

    /* Soft, distinct badge styles with high contrast dark text */
    .badge-pso {
        background-color: #ede9fe !important;
        color: #5b21b6 !important;
        border: 1px solid #ddd6fe !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        padding: 3px 6px !important;
    }
    .badge-client {
        background-color: #dbeafe !important;
        color: #1e40af !important;
        border: 1px solid #bfdbfe !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        padding: 3px 7px !important;
    }
    .badge-createdby {
        background-color: #ccfbf1 !important;
        color: #0f766e !important;
        border: 1px solid #99f6e4 !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        padding: 3px 7px !important;
    }
    .badge-delivery {
        background-color: #ffedd5 !important;
        color: #9a3412 !important;
        border: 1px solid #fed7aa !important;
        font-weight: 600 !important;
        font-size: 0.75rem !important;
        padding: 3px 7px !important;
    }
    /* Pagination Styling - Strict Single-Line Alignment */
    #paginationControls {
        display: flex !important;
        flex-direction: row !important;
        flex-wrap: nowrap !important;
        align-items: center !important;
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }
    #paginationControls .page-item {
        display: inline-flex !important;
        margin: 0 2px !important;
    }
    #paginationControls .page-link.sowing-page-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        white-space: nowrap !important;
        min-width: 32px !important;
        height: 32px !important;
        padding: 0 10px !important;
        font-size: 0.82rem !important;
        font-weight: 600 !important;
        line-height: 1 !important;
        border-radius: 6px !important;
        color: #475569 !important;
        border: 1px solid #cbd5e1 !important;
        background-color: #ffffff !important;
        text-decoration: none !important;
        vertical-align: middle !important;
    }
    #paginationControls .page-link.sowing-page-btn:hover {
        background-color: #f1f5f9 !important;
        border-color: #94a3b8 !important;
        color: #000000 !important;
    }
    #paginationControls .page-item.active .page-link.sowing-page-btn {
        background: #3b82f6 !important;
        border-color: #3b82f6 !important;
        color: #ffffff !important;
        box-shadow: 0 2px 4px rgba(59, 130, 246, 0.25) !important;
    }
    #paginationControls .page-item.disabled .page-link.sowing-page-btn {
        color: #94a3b8 !important;
        background-color: #f8fafc !important;
        border-color: #e2e8f0 !important;
        cursor: not-allowed !important;
        opacity: 0.65;
    }
</style>

<div class="container-fluid px-3 px-md-4 py-3">
    <div id="printparameterdiv"></div>

    <!-- Page Header & Top Action Buttons -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center">
                <i class="fa fa-calendar-check text-primary me-2"></i> Production Sowing Schedule
            </h4>
            <div class="text-muted small mt-1">
                Manage weekly sowing operations, monitor plant varieties, track trays, and print sowing barcodes
            </div>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-outline-secondary btn-sm shadow-sm px-3 fw-semibold" id="btnResetFilters" title="Reset all dropdowns and search">
                <i class="fa fa-undo me-1"></i> Reset Filters
            </button>
            <button class="btn btn-outline-primary btn-sm shadow-sm px-3 fw-semibold" onclick="window.location.reload();">
                <i class="fa fa-sync me-1"></i> Refresh
            </button>
            <button class="btn btn-primary btn-sm shadow-sm px-3 text-white fw-bold" id="updateTraysInBoxBtn" 
                    style="background: linear-gradient(135deg, #7c3aed, #4f46e5); border: none;">
                <i class="fa fa-box me-1"></i> Update Trays In Box
            </button>
        </div>
    </div>

    <!-- Small Summary Cards (Dynamic: Reflecting Filtered Data ONLY) -->
    <div class="row g-2 mb-3" id="sowingSummaryCardsRow">
        <!-- 1. Total Orders -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-primary border-4 p-2 sowing-stat-card active-card" data-cardfilter="all" title="Click to view all orders">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Total Orders</div>
                        <h4 class="fw-bold mb-0 text-dark" id="stat_total_orders">0</h4>
                        <div class="text-muted" style="font-size: 0.68rem;">Current view</div>
                    </div>
                    <div class="sowing-stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa fa-clipboard-list"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. Total Order Qty -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-info border-4 p-2 sowing-stat-card" data-cardfilter="orderqty" title="Click to view all order quantities">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Order Qty</div>
                        <h4 class="fw-bold mb-0 text-info" id="stat_order_qty">0</h4>
                        <div class="text-muted" style="font-size: 0.68rem;">Total plants</div>
                    </div>
                    <div class="sowing-stat-icon bg-info bg-opacity-10 text-info">
                        <i class="fa fa-boxes"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Total Sowed Qty -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-success border-4 p-2 sowing-stat-card" data-cardfilter="completed" title="Click to view completed sowing">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Sowed Qty</div>
                        <h4 class="fw-bold mb-0 text-success" id="stat_sowed_qty">0</h4>
                        <div class="text-muted" style="font-size: 0.68rem;">Plants sowed</div>
                    </div>
                    <div class="sowing-stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fa fa-check-circle"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4. Pending Sowing Qty -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-warning border-4 p-2 sowing-stat-card" data-cardfilter="pending" title="Click to view pending sowing">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Pending Sowing</div>
                        <h4 class="fw-bold mb-0 text-warning" id="stat_pending_qty">0</h4>
                        <div class="text-muted" style="font-size: 0.68rem;">Yet to sow</div>
                    </div>
                    <div class="sowing-stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa fa-hourglass-half"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 5. Client Orders -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-4 p-2 sowing-stat-card" data-cardfilter="client" title="Click to view client orders only" style="border-left-color: #6f42c1 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Client Orders</div>
                        <h4 class="fw-bold mb-0" style="color: #6f42c1;" id="stat_client_orders">0</h4>
                        <div class="text-muted" style="font-size: 0.68rem;">External clients</div>
                    </div>
                    <div class="sowing-stat-icon p-2" style="background: rgba(111, 66, 193, 0.1); color: #6f42c1;">
                        <i class="fa fa-user-tag"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. Grafted Batches -->
        <div class="col-6 col-md-4 col-xl-2">
            <div class="card h-100 border-0 shadow-sm border-start border-4 p-2 sowing-stat-card" data-cardfilter="grafted" title="Click to view grafted batches only" style="border-left-color: #20c997 !important;">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted text-uppercase fw-bold" style="font-size: 0.68rem; letter-spacing: 0.5px;">Grafted Batches</div>
                        <h4 class="fw-bold mb-0" style="color: #0f766e;" id="stat_grafted_orders">0</h4>
                        <div class="text-muted" style="font-size: 0.68rem;">Root / Scion</div>
                    </div>
                    <div class="sowing-stat-icon p-2" style="background: rgba(32, 201, 151, 0.1); color: #0f766e;">
                        <i class="fa fa-seedling"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 4 Primary Filters Panel with Option to Expand -->
    <div class="filter-panel p-3 mb-2 shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-2 pb-2 border-bottom">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-bold text-dark small">
                    <i class="fa fa-sliders-h text-primary me-1"></i> Sowing Plan Filters
                </span>
                <button class="btn btn-sm btn-outline-primary py-0 px-2 fw-semibold" type="button" 
                        data-bs-toggle="collapse" data-bs-target="#moreFiltersCollapse" 
                        aria-expanded="false" aria-controls="moreFiltersCollapse" id="btnToggleFilters"
                        style="font-size: 0.76rem; border-radius: 6px;">
                    <i class="fa fa-filter me-1"></i> <span id="lblToggleFilters">More Filters</span> <span class="badge bg-primary rounded-pill ms-1" id="extraFilterBadge" style="display: none; font-size: 0.65rem;">0</span> <i class="fa fa-chevron-down ms-1" id="iconToggleFilters"></i>
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-secondary border small fw-normal" id="lblActiveFilterCount">All Orders Shown</span>
                <button class="btn btn-sm btn-outline-secondary py-0 px-2" id="btnResetFilters" title="Reset all filters to default" style="font-size: 0.76rem;">
                    <i class="fa fa-redo-alt me-1"></i> Reset
                </button>
            </div>
        </div>

        <!-- 4 Primary Filters Shown -->
        <div class="row g-2 align-items-end">
            <!-- 1. Select Month -->
            <div class="col-12 col-sm-6 col-md-2">
                <label class="form-label small fw-bold text-secondary mb-1">Select Month</label>
                <select id="sowingmonthFilter" class="form-select form-select-sm">
                    <option value="all">All Months</option>
                </select>
            </div>
            <!-- 2. Select Week Range with Prev/Next -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Select Week Range</label>
                <div class="input-group input-group-sm">
                    <select id="sowingweekSelector" class="form-select form-select-sm"></select>
                    <button class="btn btn-outline-warning" id="sowingprevWeekBtn" title="Previous Week">
                        <i class="fa fa-chevron-left"></i>
                    </button>
                    <button class="btn btn-outline-success" id="sowingnextWeekBtn" title="Next Week">
                        <i class="fa fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            <!-- 3. Select Sowing Location -->
            <div class="col-12 col-sm-6 col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Select Sowing Location</label>
                <select id="fltlocation" class="form-select form-select-sm">
                    <option value="">All Locations</option>
                </select>
            </div>
            <!-- 4. Select Product Name -->
            <div class="col-12 col-sm-6 col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">Select Product Name</label>
                <select id="fltproduct" class="form-select form-select-sm select2">
                    <option value="">All Products</option>
                </select>
            </div>
        </div>

        <!-- Expandable Additional Filters -->
        <div class="collapse pt-2 border-top mt-2" id="moreFiltersCollapse">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-sm-4 col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Order Type</label>
                    <select id="fltordertype" class="form-select form-select-sm">
                        <option value="all">All Orders</option>
                        <option value="client">Client Orders Only</option>
                        <option value="stock">Internal Stock Orders</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Product Type</label>
                    <select id="fltproducttype" class="form-select form-select-sm">
                        <option value="all">All Types</option>
                        <option value="grafted">Grafted Only</option>
                        <option value="nongrafted">Non-Grafted Only</option>
                    </select>
                </div>
                <div class="col-12 col-sm-4 col-md-4">
                    <label class="form-label small fw-bold text-secondary mb-1">Sowing Status</label>
                    <select id="fltsowingstatus" class="form-select form-select-sm">
                        <option value="all">All Statuses</option>
                        <option value="completed">Sowing Completed</option>
                        <option value="inprogress">In Progress / Partial</option>
                        <option value="pending">Pending Sowing</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Dedicated Single Row for Quick Search & Rows per Page (Outside Filter Area) -->
    <div class="bg-white p-2 px-3 mb-2 rounded border shadow-sm d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="flex-grow-1" style="max-width: 480px; min-width: 260px;">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa fa-search"></i></span>
                <input type="text" id="oSearchInput" class="form-control form-control-sm border-start-0 ps-0"
                       placeholder="Search order no, client, product, variety, location..."
                       autocomplete="off">
                <button class="btn btn-outline-secondary" type="button" id="btnClearSearch" title="Clear Search" style="display: none;">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 ms-auto">
            <label class="small fw-semibold text-secondary text-nowrap mb-0" for="fltpagesize">
                <i class="fa fa-list-ol text-primary me-1"></i>Rows per Page:
            </label>
            <select id="fltpagesize" class="form-select form-select-sm" style="width: auto; min-width: 125px;">
                <option value="10">10 per page</option>
                <option value="25" selected>25 per page</option>
                <option value="50">50 per page</option>
                <option value="100">100 per page</option>
                <option value="all">All records</option>
            </select>
        </div>
    </div>

    <!-- Main Schedule Table Card -->
    <div class="sowing-table-card bg-white mb-3">
        <!-- Status Color Code Indicators -->
        <div class="d-flex flex-wrap align-items-center justify-content-between px-3 py-2 border-bottom bg-light bg-opacity-75 gap-2">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="small fw-bold text-secondary text-uppercase me-1" style="font-size: 0.72rem; letter-spacing: 0.5px;">
                    <i class="fa fa-tags text-primary me-1"></i>Status Color Indicators:
                </span>
                <span class="badge status-indicator-badge" data-filterstatus="completed" title="Click to filter Completed Sowing" style="background-color: #dcfce7; color: #14532d; border: 1px solid #86efac; font-size: 0.75rem; padding: 4px 8px; font-weight: 600; cursor: pointer;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #16a34a; margin-right: 5px;"></span>Sowing Completed (In Germination / Polyhouse)
                </span>
                <span class="badge status-indicator-badge" data-filterstatus="inprogress" title="Click to filter Incomplete / Partial Sowing" style="background-color: #ffedd5; color: #000000; border: 1px solid #fed7aa; font-size: 0.75rem; padding: 4px 8px; font-weight: 600; cursor: pointer;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #ea580c; margin-right: 5px;"></span>Incomplete / Partial Sowing
                </span>
                <span class="badge status-indicator-badge" data-filterstatus="pending" title="Click to filter Pending Sowing" style="background-color: #ffffff; color: #1e293b; border: 1px solid #cbd5e1; font-size: 0.75rem; padding: 4px 8px; font-weight: 600; cursor: pointer;">
                    <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background-color: #94a3b8; margin-right: 5px;"></span>Pending Sowing (Not Started)
                </span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-secondary border px-2 py-1 small fw-semibold" id="tableHeaderSummary">
                    <i class="fa fa-table text-primary me-1"></i>All Orders Shown
                </span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0" id="sowingplandetaisls">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 45px;">S.No.</th>
                        <th style="min-width: 140px;">Sowing Location</th>
                        <th style="min-width: 175px;">Order Details</th>
                        <th style="min-width: 125px;">Sowing Date</th>
                        <th style="min-width: 130px;">Variety of Seed</th>
                        <th style="min-width: 190px;">Product Name</th>
                        <th class="text-center" style="min-width: 140px;">Quantity Details</th>
                        <th class="text-center" style="min-width: 110px;">Print Sowing QR</th>
                        <th class="text-center" style="min-width: 130px;">Germination Packing</th>
                    </tr>
                </thead>
                <tbody id="scheduleTable">
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                            Loading sowing schedule records...
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination & Counter Footer -->
        <div class="bg-light py-2 px-3 border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="small text-muted fw-semibold" id="paginationInfo">
                Showing 0 to 0 of 0 entries
            </div>
            <nav aria-label="Sowing Schedule Pagination">
                <ul class="pagination pagination-sm mb-0 justify-content-end" id="paginationControls">
                    <!-- Populated dynamically via JavaScript -->
                </ul>
            </nav>
        </div>
    </div>
</div>
<div class="modal fade" id="sowingQRCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable sowing-modal-dialog">
        <div class="modal-content sowing-modal-content border-0 shadow-lg">
            <!-- Mobile App Handle Bar -->
            <div class="sowing-app-handle-bar d-md-none">
                <span class="sowing-app-handle"></span>
            </div>

            <!-- Header -->
            <div class="modal-header sowing-modal-header text-white align-items-center">
                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                    <div class="sowing-header-icon-wrap flex-shrink-0">
                        <i class="fa fa-qrcode"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h5 class="modal-title fw-bold mb-1 text-white text-truncate sowing-main-title d-flex align-items-center">
                            QR Codes for Sowing Trays
                        </h5>
                        <div class="sowing-meta-pills d-flex flex-wrap align-items-center gap-2">
                            <span class="sowing-chip">
                                <i class="fa fa-leaf me-1 text-light"></i><span class="productNamemd fw-bold"></span>
                            </span>
                            <span class="sowing-chip">
                                <i class="fa fa-hashtag me-1 opacity-75"></i>Order: <span class="productOrderNo fw-bold"></span>
                            </span>
                            <span class="sowing-chip">
                                <i class="fa fa-layer-group me-1 opacity-75"></i>Qty: <span class="productOrderQty fw-bold"></span>
                            </span>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-2 flex-shrink-0">
                    <button type="button" class="btn btn-sm btn-light shadow-sm sowing-refresh-btn px-3 py-2 text-primary d-flex align-items-center gap-1" id="refreshSowingModalBtn" style="border-radius: 8px; font-weight: 600; border: none; font-size: 0.85rem;" title="Refresh lots">
                        <i class="fa fa-sync"></i> <span class="d-none d-sm-inline ms-1">Refresh</span>
                    </button>
                    <button type="button" class="btn-close btn-close-white CloseForTodaybtn ms-1" style="margin: 0;" aria-label="Close"></button>
                </div>
            </div>

            <!-- Body -->
            <div class="modal-body sowing-modal-body bg-light">
                <div id="otherorderdetails"></div>

                <!-- Section 1: Sowing Configuration -->
                <div class="card sowing-card border-0 shadow-sm mb-3 mb-md-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="card-title sowing-card-title mb-0 fw-bold">
                            <i class="fa fa-cogs me-2"></i>Sowing Configuration
                        </h6>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2 g-md-3">
                            <div class="col-12 col-sm-6 col-md-3">
                                <label for="sowingtraytype" class="form-label sowing-field-label">Tray Type</label>
                                <div class="input-group sowing-input-group">
                                    <span class="input-group-text"><i class="fa fa-box text-muted"></i></span>
                                    <select class="form-select border-start-0 ps-0" id="sowingtraytype"></select>
                                </div>
                            </div>
                            <div class="col-4 col-sm-6 col-md-3">
                                <label for="GerminationFailure" class="form-label sowing-field-label">Buffer (%)</label>
                                <div class="input-group sowing-input-group">
                                    <span class="input-group-text"><i class="fa fa-percentage text-muted"></i></span>
                                    <input type="number" class="form-control border-start-0 ps-0" id="GerminationFailure" value="30" placeholder="Enter %">
                                </div>
                            </div>
                            <div class="col-4 col-sm-6 col-md-3">
                                <label for="TraysRequired" class="form-label sowing-field-label">Req. Trays</label>
                                <div class="input-group sowing-input-group">
                                    <span class="input-group-text"><i class="fa fa-calculator text-muted"></i></span>
                                    <input type="number" class="form-control border-start-0 ps-0 bg-readonly" id="TraysRequired" readonly placeholder="Auto-calc">
                                </div>
                            </div>
                            <div class="col-4 col-sm-6 col-md-3">
                                <label for="SowingQuantity" class="form-label sowing-field-label">Sowing Qty</label>
                                <div class="input-group sowing-input-group">
                                    <span class="input-group-text"><i class="fa fa-seedling text-muted"></i></span>
                                    <input type="number" class="form-control border-start-0 ps-0 bg-readonly" id="SowingQuantity" readonly placeholder="Total seeds">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Seed & Lot Details -->
                <div class="card sowing-card border-0 shadow-sm">
                    <div class="card-header bg-white py-3 border-bottom sowing-lot-header">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <h6 class="card-title sowing-card-title mb-0 fw-bold">
                                <i class="fa fa-list-alt me-2"></i>Seed Lot Details
                            </h6>
                            <div class="sowing-stat-badges-wrap d-flex align-items-center gap-2 ms-auto">
                                <div class="sowing-stat-badge">
                                    <small class="sowing-stat-badge-lbl">PENDING QTY WITH BUFFER</small>
                                    <span id="pendingSowingqtylbl" class="sowing-stat-badge-val text-success">0</span>
                                </div>
                                <div class="sowing-stat-badge">
                                    <small class="sowing-stat-badge-lbl">PENDING TRAYS FOR QR</small>
                                    <span id="resttoprintQrCode" class="sowing-stat-badge-val text-success">0</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Seed Selection -->
                        <div class="row justify-content-center mb-3 mb-md-4">
                            <div class="col-12 col-md-10 col-lg-8">
                                <label for="SeedNameForSowing" class="form-label fw-bold text-center w-100 d-block mb-1 sowing-seed-label" id="seednamelable" style="color: #1e293b; font-size: 0.95rem;">
                                    Scion Seed Name
                                </label>
                                <div class="input-group input-group-lg sowing-seed-search-group">
                                    <span class="input-group-text"><i class="fa fa-search text-muted"></i></span>
                                    <select class="form-select fw-bold text-dark border-start-0 ps-0" id="SeedNameForSowing" disabled></select>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Lot Rows -->
                        <div id="seedlotdetailsdiv" class="mb-4">
                            <!-- Helper Text for Empty State -->
                            <div class="text-center text-muted py-4 d-none" id="noLotsMsg">
                                <i class="fa fa-folder-open fa-2x mb-2 opacity-50"></i>
                                <p>No lot details added yet. Click "Add Lot" below.</p>
                            </div>

                            <!-- Template Row (Hidden/Initial) handled by JS, but keeping structure consistent -->
                            <div class="seedLotCard card shadow-sm mb-3 border-start border-4 border-primary">
                                <div class="card-body py-3">
                                    <div class="row align-items-end g-3">
                                        <div class="col-12 col-md-3">
                                            <label class="form-label small text-muted fw-bold">Lot Number</label>
                                            <input type="text" class="form-control form-control-sm LotNumberofSeed" placeholder="From packet">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label class="form-label small text-muted fw-bold">Available Seeds</label>
                                            <input type="text" class="form-control form-control-sm SeedQtyofLotno" placeholder="Qty">
                                        </div>
                                        <div class="col-6 col-md-2">
                                            <label class="form-label small text-muted fw-bold">Trays for QR</label>
                                            <input type="text" class="form-control form-control-sm QRCodeQtyofLotno fw-bold text-primary" placeholder="Count">
                                        </div>
                                        <div class="col-12 col-md-5 d-flex align-items-center justify-content-end gap-2 flex-wrap">
                                            <button class="btn btn-primary btn-sm px-3 SaveQRCodeDetailsBtn">
                                                <i class="fa fa-save me-1"></i> Save Details
                                            </button>
                                            <button class="btn btn-outline-danger btn-sm px-3 RemoveLotNoRow">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- Messages container -->
                                    <div class="mt-2 text-warning small fw-bold row-messages"></div>
                                </div>
                            </div>

                        </div>

                        <!-- Actions -->
                        <div class="sowing-modal-footer d-flex justify-content-center align-items-center gap-3 border-top pt-4">
                            <button class="btn btn-success px-4" id="AddLotNoRow">
                                <i class="fa fa-plus-circle me-2"></i> Add Lot Number
                            </button>
                            <button class="btn btn-outline-secondary px-4 CloseForTodaybtn sowing-close-btn" id="CloseForTodaybtn">
                                <i class="fa fa-times me-2"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="germpackingQRCodeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable germ-modal-dialog" style="max-width: 96%;">
        <div class="modal-content germ-modal-content border-0 shadow-lg">
            <!-- Mobile App Handle Bar -->
            <div class="germ-app-handle-bar d-md-none">
                <span class="germ-app-handle"></span>
            </div>

            <!-- Header -->
            <div class="modal-header germ-modal-header text-white align-items-center">
                <div class="d-flex align-items-center gap-2 flex-grow-1 overflow-hidden">
                    <div class="germ-header-icon-wrap flex-shrink-0">
                        <i class="fa fa-boxes"></i>
                    </div>
                    <div class="flex-grow-1 min-w-0">
                        <h5 class="modal-title fw-bold mb-1 text-white text-truncate germ-main-title d-flex align-items-center">
                            QR Codes for Germination Packing
                        </h5>
                        <div class="germ-meta-pills d-flex flex-wrap align-items-center gap-2">
                            <span class="germ-chip">
                                <i class="fa fa-leaf me-1 text-light"></i><b class="productNamemd_pck text-white"></b>
                            </span>
                            <span class="germ-chip">
                                <i class="fa fa-hashtag me-1 opacity-75"></i>Order: <span class="productOrderNo_pck fw-bold"></span>
                            </span>
                            <span class="germ-chip">
                                <i class="fa fa-layer-group me-1 opacity-75"></i>Order Qty: <span class="productOrderQty_pck fw-bold"></span>
                            </span>
                            <span class="germ-chip">
                                <i class="fa fa-seedling me-1 opacity-75"></i>Sowing Qty: <span class="sowingOrderQty_pck fw-bold"></span>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <button type="button" class="btn btn-sm germ-refresh-btn text-white d-flex align-items-center gap-1 shadow-sm" id="refreshGermPackingModalBtn" title="Refresh Packing Status">
                        <i class="fa fa-sync"></i> <span class="d-none d-sm-inline">Refresh</span>
                    </button>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close" style="margin: 0;"></button>
                </div>
                <div id="otherorderdetails_pck"></div>
            </div>

            <div class="modal-body p-3 p-md-4">
                <!-- Already Printed Batches Section -->
                <div id="alreadyprintedboxqrcode" class="mb-3"></div>
                <div id="boxqrcodemsg"></div>

                <!-- New Box Packing Configuration Card -->
                <div id="newboxqrcodediv">
                    <div class="germ-card p-3 p-md-4 mb-3">
                        <div class="d-flex align-items-center justify-content-between mb-3 border-bottom pb-2">
                            <h6 class="germ-card-title m-0 d-flex align-items-center gap-2">
                                <i class="fa fa-box-open"></i> Germination Box Packing Configuration
                            </h6>
                        </div>

                        <!-- Hero Stat Card: Trays for Germination Box -->
                        <div class="row mb-4">
                            <div class="col-12 col-md-8 col-lg-6 mx-auto">
                                <div class="germ-hero-stat-card text-center">
                                    <span class="germ-hero-stat-label">
                                        <i class="fa fa-layer-group me-1"></i> No. of Trays for Germination Box
                                    </span>
                                    <div class="germ-hero-stat-val text-primary my-1">
                                        <span class="sowingTrayQty_pck" id="pendingsowingTrayQtypck">0</span>
                                    </div>
                                    <div class="germ-hero-stat-sub text-muted">
                                        Trays currently pending to be packed into germination boxes
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Packing Configuration Fields -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <label for="traycountinbox" class="form-label germ-field-label">No of Trays in a Box</label>
                                <div class="input-group germ-input-group">
                                    <span class="input-group-text"><i class="fa fa-box text-muted"></i></span>
                                    <input type="number" class="form-control" id="traycountinbox" placeholder="e.g. 15" min="1" />
                                </div>
                            </div>

                            <div class="col-12 col-md-6">
                                <label for="totalboxesforgerm" class="form-label germ-field-label">Total Number of Boxes</label>
                                <div class="input-group germ-input-group">
                                    <span class="input-group-text"><i class="fa fa-cubes text-muted"></i></span>
                                    <input type="number" class="form-control bg-readonly" id="totalboxesforgerm" readonly placeholder="Auto-calculated" />
                                </div>
                            </div>
                        </div>

                        <!-- Suggested Packing Breakdown Section -->
                        <div class="row mb-3" id="boxBreakdownContainer" style="display: none;">
                            <div class="col-12">
                                <div class="alert alert-info py-2 px-3 mb-0 d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between border-0 shadow-sm" style="background: linear-gradient(135deg, #e0f2fe, #f0f9ff); color: #0369a1; border-radius: 10px;">
                                    <div class="mb-2 mb-sm-0">
                                        <i class="fa fa-cubes me-2"></i>
                                        <strong>Suggested Packing Breakdown:</strong>
                                        <span id="boxBreakdownText" class="ms-2"></span>
                                    </div>
                                    <div id="boxBreakdownBadges" class="d-flex flex-wrap gap-2"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Dates and Days -->
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-4">
                                <label for="packingdate" class="form-label germ-field-label">Date of Packing</label>
                                <div class="input-group germ-input-group">
                                    <span class="input-group-text"><i class="fa fa-calendar-alt text-muted"></i></span>
                                    <input type="date" class="form-control" id="packingdate" value="<?php echo date('Y-m-d'); ?>" />
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <div class="d-flex align-items-center justify-content-between mb-1 flex-wrap gap-1">
                                    <label for="boxopenafterdays" class="form-label germ-field-label m-0">Box Open after Days</label>
                                    <!-- Auto-fill Switch Button -->
                                    <div class="form-check form-switch m-0 d-flex align-items-center gap-1" title="Enable auto-fill with most recent used days for this product">
                                        <input class="form-check-input" type="checkbox" id="switch_autofill_box_days" role="switch" style="cursor: pointer;">
                                        <label class="form-check-label small fw-semibold text-muted" for="switch_autofill_box_days" style="font-size: 0.72rem; cursor: pointer;">Auto-fill</label>
                                    </div>
                                </div>
                                <div class="input-group germ-input-group">
                                    <span class="input-group-text"><i class="fa fa-clock text-muted"></i></span>
                                    <input type="number" class="form-control" id="boxopenafterdays" placeholder="e.g. 2" min="0" />
                                </div>
                                <!-- Suggested Days Container -->
                                <div id="suggested_box_days_container" class="mt-2" style="display: none;">
                                    <div class="d-flex align-items-center flex-wrap gap-1">
                                        <span class="small text-muted fw-bold" style="font-size: 0.68rem; text-transform: uppercase;"><i class="fa fa-magic text-primary me-1"></i>Last Used:</span>
                                        <div id="suggested_days_badges" class="d-inline-flex flex-wrap gap-1"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 col-md-4">
                                <label for="dateofopening" class="form-label germ-field-label">Date of Opening</label>
                                <div class="input-group germ-input-group">
                                    <span class="input-group-text"><i class="fa fa-calendar-check text-muted"></i></span>
                                    <input type="date" class="form-control bg-readonly" id="dateofopening" readonly placeholder="Auto-calculated" />
                                </div>
                            </div>
                        </div>

                        <!-- Confirm Boxes To Print -->
                        <div class="row g-3 mb-4">
                            <div class="col-12 col-md-8 col-lg-6 mx-auto">
                                <label for="counttiprintqrcode" class="form-label germ-field-label text-center d-block">Confirm To Print QR Code For Box</label>
                                <div class="input-group germ-input-group">
                                    <span class="input-group-text"><i class="fa fa-qrcode text-muted"></i></span>
                                    <input type="number" class="form-control text-center fw-bold" id="counttiprintqrcode" placeholder="Enter boxes to print" min="1" />
                                </div>
                                <div class="form-text text-muted small text-center mt-1">
                                    <i class="fa fa-info-circle me-1"></i>Number of box barcodes/stickers to generate.
                                </div>
                            </div>
                        </div>

                        <!-- Modal Actions Footer -->
                        <div class="germ-modal-footer d-flex flex-wrap align-items-center justify-content-center gap-2 pt-3 border-top">
                            <button class="btn btn-success germ-btn-save shadow-sm" id="saveGerminationDetailsBtnpck">
                                <i class="fa fa-check-circle me-1"></i> Save Germination Packing Details
                            </button>
                            <button class="btn btn-primary germ-btn-print d-none" id="GerminationPrintQrBtnpck">
                                <i class="fa fa-print me-1"></i> Print QR Code
                            </button>
                            <button class="btn btn-outline-secondary germ-close-btn CloseForTodaybtn" id="CloseForTodaybtn" data-bs-dismiss="modal">
                                <i class="fa fa-times me-1"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="viewQRcodelistforadjtmodal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="max-width: 98%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QRCode For Print</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div id="ViewQRcodeGerminationDiv" class="print-area" style="width:100%; text-align:center;">
                        </div>
                    </div>
                    <div class="col-md-12 mt-4 text-center">
                        <!-- <button class="btn btn-primary" id="generatePdfBtnXX" onclick="generateQRCodePDF({ sourceDivId: 'ViewQRcodeGerminationDiv', fileName: 'QRCode-Stickers.pdf'})"> Generate PDF </button> -->
                        <button class="btn btn-primary" id="generatePdfBtnXX"
                            onclick="downloadQRStickerPDF('ViewQRcodeGerminationDiv')"> Generate PDF </button>

                        <button class="btn btn-success" onclick="window.print()">Print QRcode Sheet-0th--</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="askquestion_modal" tabindex="-1" role="dialog" aria-labelledby="askquestionModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <!-- Header -->
            <div class="modal-header">
                <h5 class="modal-title" id="askquestionModalLabel">QRcode Printing Settings</h5>
                <button type="button" class="close" onclick="$('#askquestion_modal').modal('hide')">&times;</button>
            </div>
            <!-- Body -->
            <div class="modal-body">

                <div class="row g-3 px-4 py-3 d-none">
                    <div class="col-md-6">
                        <label for="barcodelengthinsheet" class="form-label fw-semibold">
                            Total QRcode labels per sheet
                        </label>
                        <input type="number" id="barcodelengthinsheet" class="form-control" placeholder="e.g., 30"
                            value="24" min="1">
                    </div>

                    <div class="col-md-6">
                        <label for="barcodelengthinrow" class="form-label fw-semibold">
                            Number of QRcode labels per row
                        </label>
                        <input type="number" id="barcodelengthinrow" class="form-control" placeholder="e.g., 5"
                            value="4" min="1">
                    </div>
                </div>

                <div class="row g-3 px-4 d-none">
                    <div class="col-md-6">
                        <label for="barcodeCount" class="form-label fw-semibold">
                            Number of QRcodes to print
                        </label>
                        <input type="number" id="barcodeCount" class="form-control" placeholder="Enter total number"
                            min="1">
                    </div>

                    <div class="col-md-6">
                        <label for="startBox" class="form-label fw-semibold">
                            Starting box number for printing
                        </label>
                        <input type="number" id="startBox" class="form-control" placeholder="e.g., 1" value="1" min="1">
                    </div>
                </div>
                <div class="text-center mt-4 mb-2">
                    <button class="btn btn-primary px-4" id="printbarcodefrom">
                        <i class="bi bi-printer"></i> Confirm to Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewbarcodelistforadjt_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QRCode For Print</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mt-2">
                        <div id="ViewBarcodeDiv" data-pdffilename="" style="width:100%;"></div>
                    </div>
                    <div class="col-md-12 mt-4 text-center">
                        <!-- <button class="btn btn-warning" id="generatePdfBtnXX">Download QRcode PDF</button> -->
                        <button class="btn btn-primary" onclick="downloadQRStickerPDF('ViewBarcodeDiv')">
                            Download Sticker PDF
                        </button>
                        <button type="button" class="btn btn-primary" onclick="printBarcodeDiv()">
                            Print QR
                        </button>

                        <!-- <button class="btn btn-success" id="PrintQrCodeBTN"
                            onclick="printThermalSheet('ViewBarcodeDiv')">Print QR Code
                            Sheet----</button> -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewqrcode_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" style="max-width: 98%;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">QRCode Preview</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12 mt-2">
                        <p>Product Name: <b id="prdnamelbl"></b></p>
                        <p>Lot Number: <b id="ltnolbl"></b></p>
                    </div>
                    <div class="col-md-12 mt-2">
                        <label>Search to Filter</label>
                        <input type="search" class="form-control" border=1 placeholder="Search...." id="searchprdltin"
                            oninput="filterTable('#searchprdltin', '#lotqrcodeprvtbl');">
                    </div>
                    <div class="col-md-12 mt-2">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="lotqrcodeprvtbl">
                                <thead>
                                    <tr>
                                        <th style="width:50px; text-align:center;">S.No</th>
                                        <th>QRCode No</th>
                                        <th style="width:180px; text-align:center;">Action</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ConfirmSowingQtymodal">
    <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 75%;">
        <div class="modal-content shadow rounded-3">

            <!-- Header -->
            <div class="modal-header smart-modal-header">
                <h6 class="modal-title">🌱Update Destroyed/Damaged Sowing Trays</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body smart-modal-body">
                <div class="mb-2">
                    <small><b>Product:</b> <span id="confirm_prdnamelbl"></span></small><br>
                    <small><b>Lot No:</b> <span id="confirm_ltnolbl"></span></small>
                </div>
                <div class="row g-2">
                    <div class="col-12 col-md-4">
                        <label>Total Trays</label>
                        <input type="number" class="form-control form-control-sm" id="total_tray_count" min="0"
                            readonly>
                    </div>
                    <div class="col-12 col-md-4">
                        <label>Confirmed Trays</label>
                        <input type="number" class="form-control form-control-sm" id="confirmed_tray_count" min="0">
                    </div>
                    <div class="col-12 col-md-4">
                        <label>Damaged / Lost</label>
                        <input type="number" class="form-control form-control-sm" id="damaged_tray_count" min="0">
                    </div>
                </div>
                <div class="text-center mt-3">
                    <button class="btn smart-confirm-btn btn-sm" id="ConfirmUpdateSowingTrayoslot">
                        ✔ Confirm & Update
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Update Sowing Location Modal -->
<div class="modal fade" id="updateSowingLocationModal" tabindex="-1" aria-labelledby="updateSowingLocationModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateSowingLocationModalLabel">Update Sowing Location</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="updateSowingLocationForm">
                    <input type="hidden" id="upl_stockorderno">
                    <input type="hidden" id="upl_projectid">
                    <input type="hidden" id="upl_roottype">
                    <input type="hidden" id="upl_sowingschedulid">
                    <div class="mb-3">
                        <label for="upl_productname" class="form-label">Product Name</label>
                        <input type="text" class="form-control" id="upl_productname" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="upl_stockorderno_disp" class="form-label">Stock Order No</label>
                        <input type="text" class="form-control" id="upl_stockorderno_disp" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="upl_sowinglocation" class="form-label">Select Location</label>
                        <select class="form-select" id="upl_sowinglocation" required>
                            <option value="">Choose...</option>

                        </select>
                    </div>
                    <!-- Capacity Details Section -->
                    <div id="polyhouseCapacityDetails"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveSowingLocationBtn">Save changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Update Trays in Box -->
<div class="modal fade" id="updateTraysInBoxModal" tabindex="-1" aria-labelledby="updateTraysInBoxModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="updateTraysInBoxModalLabel"><i class="fa fa-box me-2"></i>Update Trays in Germination Box</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body bg-light">
                <!-- Step 1: Scan Box QR -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-2 mb-md-3"><i class="fa fa-qrcode me-2 text-primary"></i>1. Scan or Enter Germination Box QR</h6>
                        <div class="row g-2 g-md-3 align-items-center">
                            <div class="col-12 col-md-7 col-lg-8">
                                <div class="input-group box-qr-input-group">
                                    <span class="input-group-text"><i class="fa fa-barcode text-primary"></i></span>
                                    <input type="text" id="boxQrInput" class="form-control form-control-lg" placeholder="Scan or enter Germination Box QR code (e.g. BOX...)" autocomplete="off">
                                    <button class="btn btn-primary btn-lg" id="btnSearchBoxQr">
                                        <i class="fa fa-search me-1"></i><span class="btn-text-full">Fetch Details</span><span class="btn-text-short d-none">Fetch</span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-12 col-md-5 col-lg-4">
                                <button class="btn btn-outline-primary btn-lg w-100" id="btnToggleBoxCamera"><i class="fa fa-camera me-1"></i> Toggle Camera Scanner</button>
                            </div>
                        </div>
                        
                        <!-- Camera Scanner Container -->
                        <div id="boxScannerContainer" class="mt-3 text-center" style="display: none; background: #e2e8f0; border-radius: 8px; padding: 10px;">
                            <div id="box-qr-reader" style="width: 100%; max-width: 350px; margin: 0 auto;"></div>
                        </div>
                        <div id="boxScanError" class="text-danger small mt-2" style="display: none; font-weight: bold;"></div>
                    </div>
                </div>

                <!-- Step 2: Show Box Details & Edit Option -->
                <div id="boxDetailsSection" style="display: none;">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-white py-2 py-md-3">
                            <h6 class="card-title mb-0 text-primary fw-bold"><i class="fa fa-info-circle me-2"></i>Germination Box Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 g-md-3 mb-3">
                                <div class="col-6 col-md-3">
                                    <div class="box-info-card">
                                        <div class="box-info-label"><i class="fa fa-qrcode me-1 text-primary"></i>Box QR Code</div>
                                        <div class="box-info-value" id="lblBoxQr">-</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="box-info-card">
                                        <div class="box-info-label"><i class="fa fa-seedling me-1 text-success"></i>Product / Seed Variety</div>
                                        <div class="box-info-value" id="lblBoxProduct">-</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="box-info-card">
                                        <div class="box-info-label"><i class="fa fa-layer-group me-1 text-info"></i>Sowing Lot / Batch</div>
                                        <div class="box-info-value" id="lblBoxBatch">-</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="box-info-card">
                                        <div class="box-info-label"><i class="fa fa-calendar-alt me-1 text-warning"></i>Date of Packing</div>
                                        <div class="box-info-value" id="lblBoxPackingDate">-</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Update Tray Count Input -->
                            <div class="row g-2 align-items-end p-2 p-md-3 bg-white border rounded-3 mb-2">
                                <div class="col-12 col-sm-6">
                                    <label class="form-label fw-bold text-secondary mb-1 small">Number of Trays in Box <span class="text-danger">*</span></label>
                                    <input type="number" id="txtTrayCountInBox" class="form-control form-control-lg" min="1" placeholder="Enter number of trays">
                                </div>
                                <div class="col-12 col-sm-6">
                                    <button class="btn btn-success btn-lg w-100" id="btnSaveBoxTrayCount"><i class="fa fa-save me-1"></i> Update Box Details</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Particular Tray List -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white py-2 py-md-3 d-flex justify-content-between align-items-center">
                            <h6 class="card-title mb-0 text-primary fw-bold"><i class="fa fa-list me-2"></i>Trays Currently in this Box</h6>
                            <span id="lblTrayListCountBadge" class="badge rounded-pill" style="color: #0d6efd; background-color: #e7f1ff; border: 1px solid #b6d4fe; display: none;"></span>
                        </div>

                        <!-- Add Tray into Box Section -->
                        <div class="p-2 p-md-3 bg-light border-bottom">
                            <div class="row g-2 align-items-center">
                                <div class="col-12 col-md-8 col-lg-9">
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-primary"><i class="fa fa-plus-circle"></i></span>
                                        <input type="text" id="inputAddTrayQuery" class="form-control" placeholder="Scan or type Tray Barcode / Sowing Plant ID to add to this box" autocomplete="off">
                                        <button class="btn btn-primary" type="button" id="btnAddTrayToBox">
                                            <i class="fa fa-plus me-1"></i>Add Tray
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4 col-lg-3">
                                    <button class="btn btn-outline-primary w-100" type="button" id="btnToggleAddTrayCamera">
                                        <i class="fa fa-camera me-1"></i>Scan Tray QR
                                    </button>
                                </div>
                            </div>
                            <!-- Camera scanner for adding tray -->
                            <div id="addTrayScannerContainer" class="mt-2 text-center" style="display: none; background: #e2e8f0; border-radius: 8px; padding: 10px;">
                                <div id="add-tray-qr-reader" style="width: 100%; max-width: 320px; margin: 0 auto;"></div>
                            </div>
                            <div id="addTrayFeedback" class="small mt-2" style="display: none;"></div>
                        </div>

                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle table-hover mb-0" id="boxTraysTable">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="tray-col-sno text-center" style="width: 60px;">S.No.</th>
                                            <th class="tray-col-plantid" style="width: 130px;">Sowing Plant ID</th>
                                            <th class="tray-col-barcode">Tray Barcode / QR Code</th>
                                            <th class="tray-col-wstatus text-center" style="width: 120px;">Working Status</th>
                                            <th class="tray-col-status text-center" style="width: 120px;">Status</th>
                                            <th class="tray-col-action text-center" style="width: 80px;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Populated dynamically -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Clean Checkbox & Layout Styling for PSO Products Selection Modal */
#selectPsoProductsModal .modal-dialog {
    max-width: 960px;
}

#tblPsoProductsForRemoval {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
}

#tblPsoProductsForRemoval th,
#tblPsoProductsForRemoval td {
    vertical-align: middle !important;
    padding: 10px 12px !important;
}

/* Explicit Width & Centering on Checkbox Column */
#tblPsoProductsForRemoval th.col-chk,
#tblPsoProductsForRemoval td.col-chk {
    width: 50px !important;
    min-width: 50px !important;
    max-width: 50px !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding: 6px 4px !important;
}

/* Checkbox Reset & Clean Sizing */
#tblPsoProductsForRemoval input.pso-modal-chk,
#tblPsoProductsForRemoval input[type="checkbox"],
#tblPsoProductsForRemoval .form-check-input {
    -webkit-appearance: auto !important;
    -moz-appearance: auto !important;
    appearance: auto !important;
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    min-height: 18px !important;
    max-width: 18px !important;
    max-height: 18px !important;
    position: static !important;
    float: none !important;
    display: inline-block !important;
    margin: 0 auto !important;
    padding: 0 !important;
    transform: none !important;
    -webkit-transform: none !important;
    cursor: pointer !important;
    vertical-align: middle !important;
    accent-color: #dc3545 !important;
    background-image: none !important;
    background-color: transparent !important;
    outline: none !important;
    box-shadow: none !important;
}

#tblPsoProductsForRemoval input.pso-modal-chk:disabled,
#tblPsoProductsForRemoval input[type="checkbox"]:disabled,
#tblPsoProductsForRemoval .form-check-input:disabled {
    cursor: not-allowed !important;
    opacity: 0.4 !important;
}

#tblPsoProductsForRemoval tbody tr {
    cursor: pointer;
    transition: background-color 0.15s ease-in-out;
}

#tblPsoProductsForRemoval tbody tr:hover {
    background-color: rgba(220, 53, 69, 0.05) !important;
}

#tblPsoProductsForRemoval tbody tr.pso-row-disabled {
    cursor: default;
}
#tblPsoProductsForRemoval tbody tr.pso-row-disabled:hover {
    background-color: transparent !important;
}
</style>

<!-- Modal for Selecting PSO Products for Removal -->
<div class="modal fade" id="selectPsoProductsModal" tabindex="-1" aria-labelledby="selectPsoProductsModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold" id="selectPsoProductsModalLabel">
                    <i class="fa fa-boxes me-2"></i>Select Products to Remove (<span id="lbl_pso_modal_psono">-</span>)
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <!-- Order Overview Banner -->
                <div class="card bg-light border-0 mb-3 shadow-sm">
                    <div class="card-body p-3">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-3 col-sm-6">
                                <span class="text-muted small fw-semibold">Client Name:</span>
                                <div class="fw-bold text-primary fs-6" id="lbl_pso_modal_client">-</div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <span class="text-muted small fw-semibold">Provisional Order (PSO):</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_pso_modal_pso_badge">-</div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <span class="text-muted small fw-semibold">Total Order Value:</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_pso_modal_total_val">₹ 0.00</div>
                            </div>
                            <div class="col-md-3 col-sm-6">
                                <span class="text-muted small fw-semibold">Advance Paid:</span>
                                <div class="fw-bold text-success fs-6" id="lbl_pso_modal_total_paid">₹ 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info py-2 px-3 small d-flex align-items-center mb-3">
                    <i class="fa fa-info-circle fs-5 me-2 text-info"></i>
                    <div>
                        Select the product(s) you wish to remove from this provisional order. Products where sowing has already started are locked and cannot be removed.
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive border rounded bg-white shadow-sm mb-3" style="max-height: 380px; overflow-y: auto;">
                    <table class="table table-hover align-middle mb-0" id="tblPsoProductsForRemoval">
                        <thead class="table-light sticky-top" style="z-index: 1;">
                            <tr class="text-muted small text-uppercase">
                                <th class="col-chk text-center">
                                    <div class="d-flex justify-content-center align-items-center" style="min-height: 24px;">
                                        <input type="checkbox" class="pso-modal-chk" id="selectAllPsoProducts" title="Select / Deselect All">
                                    </div>
                                </th>
                                <th style="width: 140px;">Stock Order #</th>
                                <th>Product Name & Variety</th>
                                <th style="width: 110px;" class="text-center">Order Qty</th>
                                <th style="width: 120px;" class="text-end">Item Amount</th>
                                <th style="width: 180px;" class="text-center">Sowing Status</th>
                            </tr>
                        </thead>
                        <tbody id="psoProductsTableBody">
                            <!-- Injected dynamically via JS -->
                        </tbody>
                    </table>
                </div>

                <!-- Selection Summary Card -->
                <div class="card border-0 bg-light p-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <span class="text-muted small">Selected for Removal:</span>
                            <span class="fw-bold text-dark ms-1" id="lbl_pso_selected_count">0 products (0 plants)</span>
                        </div>
                        <div>
                            <span class="text-muted small">Selected Items Value:</span>
                            <span class="fw-bold text-danger ms-1 fs-6" id="lbl_pso_selected_amount">₹ 0.00</span>
                        </div>
                        <div id="pso_est_credit_wrapper" style="display: none;">
                            <span class="text-muted small">Est. Credit Note:</span>
                            <span class="badge bg-success fs-6 fw-bold ms-1" id="lbl_pso_estimated_credit">₹ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="btnProceedToRemoveConfirm">
                    Proceed to Removal <i class="fa fa-arrow-right ms-1"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Removing Production Stock Order -->
<div class="modal fade" id="removeStockOrderModal" tabindex="-1" aria-labelledby="removeStockOrderModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="removeStockOrderModalLabel">
                    <i class="fa fa-trash me-2"></i>Remove Production Stock Order
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="filter: invert(1);"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="remove_stockorder_no" value="">
                <input type="hidden" id="remove_psono" value="">
                <input type="hidden" id="remove_selected_stockorders" value="">
                <input type="hidden" id="remove_selected_psoi_ids" value="">
                <input type="hidden" id="remove_cancelled_amount" value="0">
                <input type="hidden" id="remove_total_order_amount" value="0">
                <input type="hidden" id="remove_total_paid" value="0">
                <input type="hidden" id="remove_creditnote_amount" value="0">
                <input type="hidden" id="remove_is_forced" value="0">
                
                <!-- Client / Confirmed Sales Order Alert (Hidden by default) -->
                <div id="remove_stockorder_client_alert" class="alert alert-warning border-warning d-flex align-items-start mb-3" style="display: none;">
                    <i class="fa fa-exclamation-triangle fs-4 text-warning me-3 mt-1"></i>
                    <div>
                        <strong class="text-dark">Warning: Client / Confirmed Order!</strong>
                        <div class="small text-dark mt-1" id="remove_stockorder_client_text">
                            This production order is linked to a client / confirmed order. Removing it will cancel linked Provisional Order (PSO), Final Sales Order (SO), and generate a Credit Note for any paid amount.
                        </div>
                    </div>
                </div>

                <!-- Single Stock Order Details Card (Used when standalone / 1 item) -->
                <div class="card bg-light border-0 mb-3 shadow-sm" id="remove_single_product_card">
                    <div class="card-body p-3">
                        <div class="row g-2 small">
                            <div class="col-6">
                                <span class="text-muted fw-semibold">Order No:</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_remove_stockorderno">-</div>
                            </div>
                            <div class="col-6">
                                <span class="text-muted fw-semibold">Order Qty:</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_remove_orderqty">-</div>
                            </div>
                            <div class="col-12 mt-2">
                                <span class="text-muted fw-semibold">Product Name:</span>
                                <div class="fw-semibold text-dark" id="lbl_remove_productname">-</div>
                            </div>
                            <div class="col-12 mt-2" id="lbl_remove_client_wrapper" style="display:none;">
                                <span class="text-muted fw-semibold">Client:</span>
                                <div class="fw-bold text-primary" id="lbl_remove_clientname">-</div>
                            </div>
                            <div class="col-6 mt-2" id="lbl_remove_pso_wrapper" style="display:none;">
                                <span class="text-muted fw-semibold">Provisional Order (PSO):</span>
                                <div class="fw-bold text-dark" id="lbl_remove_psono">-</div>
                            </div>
                            <div class="col-6 mt-2" id="lbl_remove_so_wrapper" style="display:none;">
                                <span class="text-muted fw-semibold">Final Sales Order (SO):</span>
                                <div class="fw-bold text-dark" id="lbl_remove_sono">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Multi-Product Selected List Card (Shown when removing from PSO) -->
                <div class="card bg-light border-0 mb-3 shadow-sm" id="remove_multi_products_card" style="display: none;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-bold text-dark small text-uppercase">
                                <i class="fa fa-list-check me-1 text-danger"></i> Selected Products for Removal
                            </span>
                            <span class="badge bg-danger" id="lbl_remove_selected_badge">1 Product Selected</span>
                        </div>
                        <div class="table-responsive rounded border bg-white mb-2" style="max-height: 220px; overflow-y: auto;">
                            <table class="table table-sm table-striped align-middle mb-0 small" id="tblConfirmRemoveProducts">
                                <thead class="table-light">
                                    <tr>
                                        <th>Stock Order #</th>
                                        <th>Product Name</th>
                                        <th class="text-center">Qty</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="confirmRemoveProductsBody">
                                    <!-- Injected dynamically -->
                                </tbody>
                            </table>
                        </div>
                        <div class="row g-2 small pt-1">
                            <div class="col-6" id="lbl_remove_multi_client_wrapper">
                                <span class="text-muted fw-semibold">Client:</span>
                                <span class="fw-bold text-primary ms-1" id="lbl_remove_multi_client">-</span>
                            </div>
                            <div class="col-6 text-end" id="lbl_remove_multi_pso_wrapper">
                                <span class="text-muted fw-semibold">PSO:</span>
                                <span class="badge bg-primary ms-1" id="lbl_remove_multi_psono">-</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Paid Amount & Credit Note Section -->
                <div id="remove_paid_amount_box" class="alert alert-info border-info d-flex align-items-start mb-3" style="display: none;">
                    <i class="fa fa-money-bill-wave fs-4 text-info me-3 mt-1"></i>
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <strong class="text-dark">Paid Amount (Advance):</strong>
                            <span class="badge bg-success fs-6 fw-bold" id="lbl_remove_paid_amount">₹ 0.00</span>
                        </div>
                        <div class="small text-muted mt-1" id="lbl_remove_creditnote_notice">
                            <i class="fa fa-info-circle text-info me-1"></i>
                            A <strong>Credit Note</strong> will be automatically generated and credited to client's account ledger upon cancellation.
                        </div>
                    </div>
                </div>

                <!-- Removal Remark Input (Mandatory) -->
                <div class="mb-2">
                    <label for="remove_stockorder_remark" class="form-label fw-bold text-dark">
                        Reason / Remark for Removal <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="remove_stockorder_remark" rows="3" 
                              placeholder="Why are you removing this product? (Required)" required></textarea>
                    <div class="text-danger small mt-1" id="remove_stockorder_remark_err" style="display: none; font-weight: 600;">
                        Please enter a remark explaining why you are removing this product.
                    </div>
                </div>

                <!-- Option to update tedit column in krf_polyhouse_sowingschedule (Only visible if loginroleid < 2) -->
                <?php if (intval($loginroleid ?? ($_SESSION['loginroleid'] ?? 99)) < 2): ?>
                <div class="d-flex align-items-start gap-3 mt-3 p-3 bg-light border rounded" id="wrapper_update_tedit_sowingschedule">
                    <div class="form-check form-switch m-0 pt-1" style="min-width: 45px;">
                        <input class="form-check-input m-0" type="checkbox" id="chk_update_tedit_sowingschedule" name="update_tedit_sowingschedule" value="1" style="cursor: pointer; width: 2.3em; height: 1.25em;">
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-check-label fw-bold text-dark small mb-0" for="chk_update_tedit_sowingschedule" style="cursor: pointer;">
                            <i class="fa fa-database text-primary me-1"></i> Update <code>tedit</code> column in <code>krf_polyhouse_sowingschedule</code> (<code>tedit = 'delete'</code>)
                        </label>
                        <div class="text-muted small mt-1">
                            Marks <code>tedit = 'delete'</code> in sowing schedule table so it is soft-deleted and filtered out from all active schedule queries.
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div id="wrapper_update_tedit_sowingschedule" style="display: none;">
                    <input type="checkbox" id="chk_update_tedit_sowingschedule" name="update_tedit_sowingschedule" value="0">
                </div>
                <?php endif; ?>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-outline-secondary px-3 me-auto" id="btnBackToPsoProducts" style="display: none;">
                    <i class="fa fa-arrow-left me-1"></i> Back to Products
                </button>
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger px-4" id="btnConfirmRemoveStockOrder">
                    <i class="fa fa-trash me-1"></i> <span id="lbl_btn_remove_text">Remove Order</span>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for Changing Product / Variety -->
<div class="modal fade" id="editProductVarietyModal" tabindex="-1" aria-labelledby="editProductVarietyModalLabel" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title fw-bold" id="editProductVarietyModalLabel">
                    <i class="fa fa-edit me-2"></i>Change Product / Seed Variety
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <input type="hidden" id="edit_product_stockorder_no" value="">
                <input type="hidden" id="edit_product_clientid" value="">
                <input type="hidden" id="edit_product_psono" value="">
                <input type="hidden" id="edit_product_old_productid" value="">
                <input type="hidden" id="edit_product_old_productname" value="">
                <input type="hidden" id="edit_product_old_seedname" value="">
                <input type="hidden" id="edit_product_old_sowingdate" value="">
                <input type="hidden" id="edit_product_old_deliverydate" value="">

                <!-- Current Order & Old Product Information -->
                <div class="card border-0 bg-light mb-3">
                    <div class="card-header bg-secondary bg-opacity-10 py-2">
                        <span class="fw-bold text-dark small"><i class="fa fa-info-circle me-1"></i>Current Order & Product Details</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2 small">
                            <div class="col-md-4">
                                <span class="text-muted">Order No:</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_edit_stockorderno">-</div>
                            </div>
                            <div class="col-md-4">
                                <span class="text-muted">Order Qty:</span>
                                <div class="fw-bold text-dark fs-6" id="lbl_edit_orderqty">-</div>
                            </div>
                            <div class="col-md-4" id="lbl_edit_client_wrapper">
                                <span class="text-muted">Client:</span>
                                <div class="fw-bold text-primary fs-6" id="lbl_edit_clientname">-</div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <span class="text-muted">Current Product:</span>
                                <div class="fw-bold text-danger" id="lbl_edit_old_productname">-</div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <span class="text-muted">Current Variety:</span>
                                <div class="fw-semibold text-dark" id="lbl_edit_old_seedname">-</div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <span class="text-muted">Current Sowing Date:</span>
                                <div class="fw-semibold text-dark" id="lbl_edit_old_sowingdate">-</div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <span class="text-muted">Current Expected Delivery:</span>
                                <div class="fw-semibold text-dark" id="lbl_edit_old_deliverydate">-</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New Product Selection -->
                <div class="card border border-warning bg-white mb-3">
                    <div class="card-header bg-warning bg-opacity-25 py-2">
                        <span class="fw-bold text-dark small"><i class="fa fa-seedling me-1"></i>Select New Product / Variety</span>
                    </div>
                    <div class="card-body p-3">
                        <div class="mb-3">
                            <label for="edit_new_product_search" class="form-label fw-bold text-dark">
                                Choose Product from Master <span class="text-danger">*</span>
                            </label>
                            <div class="position-relative">
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                                    <input type="text" id="edit_new_product_search" class="form-control form-control-lg" 
                                           placeholder="Type to search product name, seed or variety..." autocomplete="off" required>
                                    <button class="btn btn-outline-secondary" type="button" id="btnClearProductSearch" title="Clear" style="display:none;">
                                        <i class="fa fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" id="edit_new_product_id" value="">
                                
                                <!-- Search Suggestions Dropdown List -->
                                <div id="edit_product_autocomplete_list" class="list-group position-absolute w-100 shadow" 
                                     style="display: none; max-height: 260px; overflow-y: auto; z-index: 1080; top: 100%; border: 1px solid #ced4da; border-radius: 0.375rem; background: #fff;">
                                </div>
                            </div>
                            <div class="form-text text-muted small mt-1">
                                <i class="fa fa-keyboard me-1"></i>Type to search, use <kbd>↑</kbd> <kbd>↓</kbd> arrow keys to navigate, and <kbd>Enter</kbd> to select.
                            </div>
                        </div>

                        <!-- Selected Product Info Banner -->
                        <div id="edit_new_product_preview" class="p-3 bg-light rounded border mb-3" style="display: none;">
                            <div class="row g-2 small">
                                <div class="col-md-6">
                                    <span class="text-muted">Seed / Variety:</span>
                                    <div class="fw-bold text-success" id="lbl_edit_new_variety">-</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Type:</span>
                                    <div class="fw-bold text-dark" id="lbl_edit_new_type">-</div>
                                </div>
                                <div class="col-md-3">
                                    <span class="text-muted">Growing Cycle:</span>
                                    <div class="fw-bold text-primary" id="lbl_edit_new_cycledays">-</div>
                                </div>
                            </div>
                        </div>

                        <!-- Date Adjustments -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="edit_new_sowing_date" class="form-label fw-bold text-dark">
                                    New Sowing Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="edit_new_sowing_date" class="form-control form-control-lg" required>
                            </div>
                            <div class="col-md-6">
                                <label for="edit_new_delivery_date" class="form-label fw-bold text-dark">
                                    New Expected Delivery Date <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="edit_new_delivery_date" class="form-control form-control-lg" required>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Remark Field (Mandatory) -->
                <div class="mb-3">
                    <label for="edit_product_remark" class="form-label fw-bold text-dark">
                        Reason / Remark for Changing Product <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="edit_product_remark" rows="3" 
                              placeholder="Why are you changing the product/variety against this order? (Required)" required></textarea>
                    <div class="text-danger small mt-1" id="edit_product_remark_err" style="display: none; font-weight: 600;">
                        Please provide a detailed remark explaining the reason for changing the product.
                    </div>
                </div>

                <!-- Notification Info Alert -->
                <div class="alert alert-info border-info py-2 px-3 small d-flex align-items-center mb-0">
                    <i class="fa fa-bell me-2 fs-5 text-info"></i>
                    <div>
                        <b>Automatic Notifications:</b> WhatsApp updates will be automatically dispatched to the <b>Client</b>, <b>Admin Department</b>, and <b>Sowing Department</b> upon updating.
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0">
                <button type="button" class="btn btn-secondary px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning px-4 fw-bold text-dark" id="btnConfirmEditProduct">
                    <i class="fa fa-save me-1"></i> Save & Update Product
                </button>
            </div>
        </div>
    </div>
</div>



<script>
$(document).ready(function() {
  let boxQrScanner = null;
  let addTrayQrScanner = null;
  let currentActiveBox = null;
  let currentBoxTrays = [];

  // Open Modal
  $('#updateTraysInBoxBtn').click(function() {
    // Reset inputs
    $('#boxQrInput').val('');
    $('#boxScannerContainer').hide();
    $('#boxScanError').hide();
    $('#boxDetailsSection').hide();
    $('#inputAddTrayQuery').val('');
    $('#addTrayScannerContainer').hide();
    $('#addTrayFeedback').hide();
    $('#txtTrayCountInBox').val('');
    $('#boxTraysTable tbody').empty();
    currentActiveBox = null;
    currentBoxTrays = [];
    
    stopBoxScanner();
    stopAddTrayScanner();

    const modal = new bootstrap.Modal(document.getElementById('updateTraysInBoxModal'));
    modal.show();
  });

  // Toggle Box Camera
  $('#btnToggleBoxCamera').click(function() {
    if ($('#boxScannerContainer').is(':visible')) {
      stopBoxScanner();
    } else {
      startBoxScanner();
    }
  });

  function startBoxScanner() {
    $('#boxScannerContainer').show();
    $('#boxScanError').hide();

    if (!boxQrScanner) {
      boxQrScanner = new Html5Qrcode("box-qr-reader");
    }

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };

    boxQrScanner.start(
      { facingMode: "environment" },
      config,
      function(decodedText) {
        $('#boxQrInput').val(decodedText);
        fetchBoxDetails(decodedText);
        stopBoxScanner();
      }
    ).catch(err => {
      $('#boxScanError').text("Camera error: " + err).show();
      stopBoxScanner();
    });
  }

  function stopBoxScanner() {
    $('#boxScannerContainer').hide();
    if (boxQrScanner && boxQrScanner.isScanning) {
      boxQrScanner.stop().catch(err => console.error(err));
    }
  }

  // Toggle Add Tray Camera
  $('#btnToggleAddTrayCamera').click(function() {
    if ($('#addTrayScannerContainer').is(':visible')) {
      stopAddTrayScanner();
    } else {
      startAddTrayScanner();
    }
  });

  function startAddTrayScanner() {
    $('#addTrayScannerContainer').show();
    $('#addTrayFeedback').hide();

    if (!addTrayQrScanner) {
      addTrayQrScanner = new Html5Qrcode("add-tray-qr-reader");
    }

    const config = { fps: 10, qrbox: { width: 250, height: 250 } };
    addTrayQrScanner.start(
      { facingMode: "environment" },
      config,
      function(decodedText) {
        $('#inputAddTrayQuery').val(decodedText);
        addTrayToBox(decodedText);
        stopAddTrayScanner();
      }
    ).catch(err => {
      $('#addTrayFeedback').html('<span class="text-danger">Camera error: ' + err + '</span>').show();
      stopAddTrayScanner();
    });
  }

  function stopAddTrayScanner() {
    $('#addTrayScannerContainer').hide();
    if (addTrayQrScanner && addTrayQrScanner.isScanning) {
      addTrayQrScanner.stop().catch(err => console.error(err));
    }
  }

  // Fetch Details on Click or Enter
  $('#btnSearchBoxQr').click(function() {
    const qr = $('#boxQrInput').val().trim();
    if (qr) fetchBoxDetails(qr);
  });

  $('#boxQrInput').keypress(function(e) {
    if (e.which === 13) {
      const qr = $(this).val().trim();
      if (qr) fetchBoxDetails(qr);
    }
  });

  function fetchBoxDetails(qrcode) {
    const projectid = $('body').data('projectid') || 1;
    $('#boxScanError').hide();
    $('#addTrayFeedback').hide();

    $.ajax({
      url: 'showingplan_starting.php?ajax_action=getBoxDetails',
      type: 'POST',
      data: { qrcode: qrcode, projectid: projectid },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success') {
          currentActiveBox = res.box;
          currentBoxTrays = res.trays || [];

          $('#lblBoxQr').text(res.box.qrcode);
          $('#lblBoxProduct').text(res.box.productname || 'N/A');
          $('#lblBoxBatch').text(res.box.sowinglotno_internal || 'N/A');
          $('#lblBoxPackingDate').text(res.box.boxpackingdate || 'N/A');

          renderBoxTraysTable();
          $('#boxDetailsSection').slideDown();
        } else {
          currentActiveBox = null;
          currentBoxTrays = [];
          $('#boxScanError').text(res.message).show();
          $('#boxDetailsSection').hide();
        }
      },
      error: function() {
        currentActiveBox = null;
        currentBoxTrays = [];
        $('#boxScanError').text("Network error fetching box details.").show();
        $('#boxDetailsSection').hide();
      }
    });
  }

  // Render Trays Table
  function renderBoxTraysTable() {
    let trHtml = '';
    const totalTrays = currentBoxTrays.length;

    if (totalTrays > 0) {
      $('#lblTrayListCountBadge').text(totalTrays + ' Trays').show();
      $('#txtTrayCountInBox').val(totalTrays);

      currentBoxTrays.forEach(function(tray, index) {
        const workingBadge = tray.workingstatus ? `<span class="badge" style="color: #0891b2; background-color: #ecfeff; border: 1px solid #a5f3fc; font-weight: 600; font-size: 0.78rem; padding: 4px 8px;">${tray.workingstatus}</span>` : '';
        const statusBadge = tray.status ? `<span class="badge" style="color: #475569; background-color: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; font-size: 0.78rem; padding: 4px 8px;">${tray.status}</span>` : '';

        trHtml += `
          <tr class="tray-row" data-sowingplantid="${tray.sowingplantid}">
            <td class="tray-col-sno text-center fw-bold text-muted">${index + 1}</td>
            <td class="tray-col-plantid fw-bold text-dark">${tray.sowingplantid}</td>
            <td class="tray-col-barcode">
              <div class="tray-card-meta">
                <div class="d-flex align-items-center justify-content-between mb-2">
                  <span class="fw-bold text-dark small">
                    <span class="badge bg-primary me-1">#${index + 1}</span> ID: ${tray.sowingplantid}
                  </span>
                  <div class="d-flex gap-1 align-items-center">
                    ${workingBadge}
                    ${statusBadge}
                    <button type="button" class="btn btn-sm btn-outline-danger btn-remove-box-tray ms-1" data-id="${tray.sowingplantid}" style="padding: 2px 7px;" title="Remove from box">
                      <i class="fa fa-times"></i>
                    </button>
                  </div>
                </div>
              </div>
              <div class="input-group input-group-sm">
                <span class="input-group-text d-md-none bg-light text-muted"><i class="fa fa-barcode"></i></span>
                <input type="text" class="form-control tray-barcode-input" value="${tray.barcode || ''}" placeholder="Scan/Type tray barcode">
              </div>
            </td>
            <td class="tray-col-wstatus text-center">${workingBadge}</td>
            <td class="tray-col-status text-center">${statusBadge}</td>
            <td class="tray-col-action text-center">
              <button type="button" class="btn btn-sm btn-outline-danger btn-remove-box-tray" data-id="${tray.sowingplantid}" title="Remove Tray from Box">
                <i class="fa fa-trash"></i>
              </button>
            </td>
          </tr>
        `;
      });
    } else {
      $('#lblTrayListCountBadge').text('0 Trays').show();
      $('#txtTrayCountInBox').val(0);
      trHtml = '<tr><td colspan="6" class="text-center py-4 text-muted"><i class="fa fa-info-circle me-1"></i>No trays currently in this box. Use "Add Tray" above to place trays into this box.</td></tr>';
    }

    $('#boxTraysTable tbody').html(trHtml);
  }

  // Remove Tray from Box list
  $(document).on('click', '.btn-remove-box-tray', function() {
    const spId = $(this).data('id');
    const tray = currentBoxTrays.find(t => t.sowingplantid == spId);
    const trayLabel = tray ? (tray.barcode || `ID: ${spId}`) : `ID: ${spId}`;

    if (confirm(`Remove tray ${trayLabel} from this germination box?`)) {
      currentBoxTrays = currentBoxTrays.filter(t => t.sowingplantid != spId);
      renderBoxTraysTable();
      $('#addTrayFeedback').html(`<span class="text-success"><i class="fa fa-check-circle me-1"></i> Tray ${trayLabel} removed from box list. Click <b>"Update Box Details"</b> to apply changes.</span>`).show();
    }
  });

  // Add Tray to Box
  $('#btnAddTrayToBox').click(function() {
    const query = $('#inputAddTrayQuery').val().trim();
    if (query) addTrayToBox(query);
  });

  $('#inputAddTrayQuery').keypress(function(e) {
    if (e.which === 13) {
      e.preventDefault();
      const query = $(this).val().trim();
      if (query) addTrayToBox(query);
    }
  });

  function addTrayToBox(query) {
    query = (query || '').trim();
    if (!query) {
      $('#addTrayFeedback').html('<span class="text-danger"><i class="fa fa-exclamation-circle me-1"></i> Please enter a Tray Barcode or Sowing Plant ID.</span>').show();
      return;
    }

    if (!currentActiveBox) {
      alert('Please scan or fetch a valid Germination Box first.');
      return;
    }

    // Check if already in current list
    const exists = currentBoxTrays.some(t => 
      (t.barcode && t.barcode.toLowerCase() === query.toLowerCase()) || 
      String(t.sowingplantid) === query
    );
    if (exists) {
      $('#addTrayFeedback').html('<span class="text-danger"><i class="fa fa-exclamation-circle me-1"></i> This tray is already in this box!</span>').show();
      return;
    }

    const projectid = $('body').data('projectid') || 1;
    $('#addTrayFeedback').html('<span class="text-muted"><i class="fa fa-spinner fa-spin me-1"></i> Validating tray...</span>').show();

    $.ajax({
      url: 'showingplan_starting.php?ajax_action=validateAddTray',
      type: 'POST',
      data: {
        query: query,
        box_qrcode: currentActiveBox.qrcode,
        stockorderno: currentActiveBox.stockorderno || '',
        sowinglotno: currentActiveBox.sowinglotno_internal || '',
        projectid: projectid
      },
      dataType: 'json',
      success: function(res) {
        if (res.status === 'success' && res.tray) {
          const t = res.tray;
          
          if (currentBoxTrays.some(existing => existing.sowingplantid == t.sowingplantid)) {
            $('#addTrayFeedback').html('<span class="text-danger"><i class="fa fa-exclamation-circle me-1"></i> This tray is already in this box!</span>').show();
            return;
          }

          if (res.warning) {
            if (!confirm(res.warning + '\n\nDo you want to proceed and add it to this box?')) {
              $('#addTrayFeedback').hide();
              return;
            }
          }

          currentBoxTrays.push(t);
          renderBoxTraysTable();
          $('#inputAddTrayQuery').val('').focus();
          $('#addTrayFeedback').html(`<span class="text-success"><i class="fa fa-check-circle me-1"></i> Tray ${t.barcode || t.sowingplantid} added to box! Click <b>"Update Box Details"</b> to apply changes.</span>`).show();
        } else {
          $('#addTrayFeedback').html(`<span class="text-danger"><i class="fa fa-exclamation-circle me-1"></i> ${res.message || 'Tray not found or cannot be added.'}</span>`).show();
        }
      },
      error: function() {
        $('#addTrayFeedback').html('<span class="text-danger"><i class="fa fa-exclamation-circle me-1"></i> Network error validating tray.</span>').show();
      }
    });
  }

  // Handle direct manual change to Number of Trays in Box input
  $('#txtTrayCountInBox').on('change', function() {
    let val = parseInt($(this).val(), 10) || 0;
    if (val <= 0) {
      val = currentBoxTrays.length;
      $(this).val(val);
      return;
    }

    if (val < currentBoxTrays.length) {
      if (confirm(`You changed tray count to ${val}, but there are ${currentBoxTrays.length} trays in the list.\n\nKeep the first ${val} trays and remove the remaining ${currentBoxTrays.length - val} tray(s)?`)) {
        currentBoxTrays = currentBoxTrays.slice(0, val);
        renderBoxTraysTable();
      } else {
        $(this).val(currentBoxTrays.length);
      }
    } else if (val > currentBoxTrays.length) {
      alert(`To add trays to this box (up to ${val}), please scan or type their Tray Barcode in the "Add Tray" section below.`);
      $(this).val(currentBoxTrays.length);
    }
  });

  // Update Box and Trays
  $('#btnSaveBoxTrayCount').click(function() {
    const qrcode = $('#lblBoxQr').text();
    const traycount = parseInt($('#txtTrayCountInBox').val(), 10) || currentBoxTrays.length;
    const projectid = $('body').data('projectid') || 1;

    if (!qrcode || qrcode === '-') {
      alert("Please scan or fetch a valid Germination Box first.");
      return;
    }

    if (traycount <= 0) {
      alert("Please enter a valid tray count");
      return;
    }

    // Sync edited barcodes from inputs into currentBoxTrays
    $('.tray-row').each(function() {
      const spId = $(this).data('sowingplantid');
      const bc = $(this).find('.tray-barcode-input').val().trim();
      const match = currentBoxTrays.find(t => t.sowingplantid == spId);
      if (match) {
        match.barcode = bc;
      }
    });

    const $btn = $(this);
    const origBtnHtml = $btn.html();
    $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin me-1"></i> Updating...');

    $.ajax({
      url: 'showingplan_starting.php?ajax_action=updateBoxTrays',
      type: 'POST',
      data: {
        qrcode: qrcode,
        traycountinbox: traycount,
        trays: currentBoxTrays,
        projectid: projectid
      },
      dataType: 'json',
      success: function(res) {
        $btn.prop('disabled', false).html(origBtnHtml);
        if (res.status === 'success') {
          alert(res.message);
          // Refresh details
          fetchBoxDetails(qrcode);
        } else {
          alert('Update failed: ' + res.message);
        }
      },
      error: function() {
        $btn.prop('disabled', false).html(origBtnHtml);
        alert('Network error during update.');
      }
    });
  });

  // Stop camera on modal hidden
  $('#updateTraysInBoxModal').on('hidden.bs.modal', function () {
    stopBoxScanner();
    stopAddTrayScanner();
  });
});
</script>

<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js?v=<?php echo time(); ?>"></script>
<!-- Bootstrap JS + dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<!-- QR Scanner (html5-qrcode) -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<!-- QZ Tray for Thermal Printing (Local) -->
<script src="js/sha256.min.js"></script>
<script>
    // QZ Tray expects an object named 'Sha256' with a '.hash()' method.
    // The js-sha256 library exports a global 'sha256' function.
    if (typeof Sha256 === 'undefined' && typeof sha256 !== 'undefined') {
        window.Sha256 = {
            hash: function(data) {
                return sha256(data);
            }
        };
    }
</script>
<script src="js/qz-tray.js"></script>
<script>
    // console.log("Checking QZ Tray loading...");
    if (typeof qz !== 'undefined') {
        // console.log("QZ Tray library loaded successfully. Version: " + qz.version);
    } else {
        // console.error("QZ Tray library failed to load. Check if 'js/qz-tray.js' exists in the 'krf-orderandsales' folder.");
    }
</script>
<script>
    var loginroleid = <?php echo isset($loginroleid) && $loginroleid !== '' ? intval($loginroleid) : (isset($_SESSION['loginroleid']) ? intval($_SESSION['loginroleid']) : 0); ?>;
</script>

<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/sowing_plan_operations.js?v=<?php echo time(); ?>"></script>

<script>
function printBarcodeDiv() {
    let div = document.getElementById("ViewBarcodeDiv");

    if (!div) {
        alert("Barcode Div not found!");
        return;
    }

    // 🔹 Clone content (important for modal)
    let printContent = div.cloneNode(true);

    // 🔹 Create temp container
    let printContainer = document.createElement("div");
    printContainer.id = "printSection";
    printContainer.appendChild(printContent);
    document.body.appendChild(printContainer);
    // 🔹 If barcode/QR is canvas → convert to image
    let canvas = printContainer.querySelector("canvas");
    if (canvas) {
        let img = document.createElement("img");
        img.src = canvas.toDataURL();
        canvas.parentNode.replaceChild(img, canvas);
    }
    window.print();
    // 🔹 Cleanup
    document.body.removeChild(printContainer);
}
</script>