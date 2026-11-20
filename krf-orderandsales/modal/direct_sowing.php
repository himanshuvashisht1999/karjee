<?php
/** @var mysqli $krfconn */
error_reporting(E_ALL);
require('../../connection.php');
 
date_default_timezone_set('Asia/Kolkata');
$action = $_GET['action'];


if ($action === "getstate_district") { 
    $stmt = $krfconn->prepare("
        SELECT s.stateid, s.statename,s.statename_hn,
           d.districtid, d.districtname,d.districtname_hn
        FROM master_states s join  
           master_districts d
         on s.stateid =d.stateid
        WHERE s.statename IS NOT NULL
          AND s.statename <> ''
        ORDER BY s.statename, d.districtname ASC
    ");
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Prepare failed: ' . $krfconn->error,
            'data'    => []
        ]);
        exit;
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $states = [];
    while ($row = $result->fetch_assoc()) {
        $states[] = $row;
    }
    $stmt->close();
    echo json_encode([
        'status'  => 'success',
        'message' => 'States fetched successfully',
        'data'    => $states
    ]);

    exit;
}


else if ($action === "getstate") { 
    $stmt = $krfconn->prepare("
        SELECT stateid, statename,statename_hn
        FROM master_states
        WHERE statename IS NOT NULL
          AND statename <> ''
        ORDER BY statename ASC
    ");
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Prepare failed: ' . $krfconn->error,
            'data'    => []
        ]);
        exit;
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $states = [];
    while ($row = $result->fetch_assoc()) {
        $states[] = $row;
    }
    $stmt->close();
    echo json_encode([
        'status'  => 'success',
        'message' => 'States fetched successfully',
        'data'    => $states
    ]);

    exit;
} else if ($action === "getdistrict") {
    $stateid = $_POST['stateid'] ?? '';
    if ($stateid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'State ID is required',
            'data'    => []
        ]);
        exit;
    }
    $stmt = $krfconn->prepare("
        SELECT districtid, districtname,districtname_hn
        FROM master_districts
        WHERE districtname IS NOT NULL
          AND districtname <> ''
          AND stateid = ?
        ORDER BY districtname ASC
    ");
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Prepare failed: ' . $krfconn->error,
            'data'    => []
        ]);
        exit;
    }
    $stmt->bind_param("s", $stateid);
    $stmt->execute();
    $result = $stmt->get_result();
    $districts = [];
    while ($row = $result->fetch_assoc()) {
        $districts[] = $row;
    }
    $stmt->close();
    echo json_encode([
        'status'  => 'success',
        'message' => 'Districts fetched successfully',
        'data'    => $districts
    ]);

    exit;
}/*else if ($action === "getpolyhouse") {
    $projectid  = $_POST['projectid'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    if (empty($projectid) || empty($locationid)) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Project ID and Location ID are required.',
            'data'    => []
        ]);
        exit;
    }
    $trayDetails = [];
    $qry = "
        SELECT
            loc.LocationId,
            IFNULL(loc.locationname, '') AS locationname,
            poly.polyhouseid,
            IFNULL(poly.polyhousename, '') AS polyhousename
        FROM krf_polyhouse_master AS poly
        INNER JOIN {$config['db_hrm']}.hrm_AttendanceLocationMaster AS loc
            ON loc.LocationId = poly.locationid
        WHERE poly.locationid = ?
          AND poly.projectid = ?
        ORDER BY poly.polyhousename
    ";

    $lotStmt = $krfconn->prepare($qry);

    if (!$lotStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Prepare failed: ' . $krfconn->error,
            'data'    => []
        ]);
        exit;
    }

    $lotStmt->bind_param(
        "si",
        $locationid,
        $projectid
    );

    $lotStmt->execute();

    $lotResult = $lotStmt->get_result();

    while ($row = $lotResult->fetch_assoc()) {
        $trayDetails[] = $row;
    }

    $lotStmt->close();

    echo json_encode([
        'status' => 'success',
        'data'   => $trayDetails
    ]);

    exit;
}*/else if ($action === "getpolyhouse") {
    header('Content-Type: application/json; charset=utf-8');
    $projectid  = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    if ($projectid <= 0 || $locationid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Project ID and Location ID are required.',
            'data'    => []
        ], JSON_UNESCAPED_UNICODE);

        exit;
    }

    try {
        $trayDetails = [];
        $locationname = '';

        /*
         * 1. Get location details from HRM database.
         */
        $locationQuery = "
            SELECT
                LocationId,
                IFNULL(locationname, '') AS locationname
            FROM hrm_AttendanceLocationMaster
            WHERE LocationId = ?
            LIMIT 1
        ";
        $locationStmt = $hrmconn->prepare($locationQuery);
        if (!$locationStmt) {
            throw new Exception(
                'Location prepare failed: ' . $hrmconn->error
            );
        }
        $locationStmt->bind_param(
            "s",
            $locationid
        );
        if (!$locationStmt->execute()) {
            throw new Exception(
                'Location query failed: ' . $locationStmt->error
            );
        }
        $locationResult = $locationStmt->get_result();
        if ($locationRow = $locationResult->fetch_assoc()) {
            $locationname = $locationRow['locationname'] ?? '';
        }
        $locationStmt->close();
        /*
         * 2. Get polyhouses from general database.
         */
        $polyhouseQuery = "
            SELECT
                poly.locationid AS LocationId,
                poly.polyhouseid,
                IFNULL(poly.polyhousename, '') AS polyhousename
            FROM krf_polyhouse_master AS poly
            WHERE poly.locationid = ?
              AND poly.projectid = ?
            ORDER BY poly.polyhousename ASC
        ";

        $polyhouseStmt = $krfconn->prepare($polyhouseQuery);

        if (!$polyhouseStmt) {
            throw new Exception(
                'Polyhouse prepare failed: ' . $krfconn->error
            );
        }

        $polyhouseStmt->bind_param(
            "si",
            $locationid,
            $projectid
        );

        if (!$polyhouseStmt->execute()) {
            throw new Exception(
                'Polyhouse query failed: ' . $polyhouseStmt->error
            );
        }

        $polyhouseResult = $polyhouseStmt->get_result();

        while ($row = $polyhouseResult->fetch_assoc()) {
            $row['locationname'] = $locationname;
            $trayDetails[] = $row;
        }

        $polyhouseStmt->close();

        echo json_encode([
            'status'  => empty($trayDetails) ? 'nodata' : 'success',
            'message' => empty($trayDetails)
                ? 'No polyhouse found for the selected location.'
                : '',
            'data'    => $trayDetails
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        error_log('getpolyhouse error: ' . $e->getMessage());

        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage(),
            'data'    => []
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}else if ($action == "barcodedirectSowing") {

    $companyid    = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid    = (int)($_POST['projectid'] ?? 0);
    $barcode      = trim($_POST['qrcode'] ?? '');
    $status       = trim($_POST['status'] ?? '');

    /* -------------------------------------------------------
       BASIC VALIDATION
    ------------------------------------------------------- */

    if ($barcode === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'QR Code is empty.'
        ]);
        exit;
    }

    if ($status === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Status is empty.'
        ]);
        exit;
    }

    if ($projectid <= 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid project.'
        ]);
        exit;
    }


    /* -------------------------------------------------------
       STEP 1 : FIND SCANNED TRAY
    ------------------------------------------------------- */

    $sowingIntQuery = "
        SELECT
            s.sowinglotno_internal,
            s.stockorder_no,
            s.productid
        FROM krf_polyhouse_sowingplant_details AS s

        INNER JOIN product_master AS p
            ON p.productID = s.productid
           AND p.projectid = s.projectid

        WHERE s.barcode = ?
          AND s.projectid = ?
          AND s.status = ?
          -- AND p.IsGrafted = 'NonGrafted'
          AND (
                s.germination_qrcode IS NULL
                OR s.germination_qrcode = ''
              )
          AND s.workingstatus = 'WithoutGermination'

        LIMIT 1
    ";

    $sowingIntStmt = $krfconn->prepare($sowingIntQuery);

    if (!$sowingIntStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }

    $sowingIntStmt->bind_param(
        "sis",
        $barcode,
        $projectid,
        $status
    );

    $sowingIntStmt->execute();

    $sowingIntResult = $sowingIntStmt->get_result();

    if ($sowingIntResult->num_rows === 0) {

        $sowingIntStmt->close();

        echo json_encode([
            'status'  => 'error',
            'message' => 'Tray does not exist or is not eligible.'
        ]);
        exit;
    }

    $sowingRow = $sowingIntResult->fetch_assoc();

    $sowingIntStmt->close();

    $sowinglotno_internal = $sowingRow['sowinglotno_internal'];
    $orderno              = $sowingRow['stockorder_no'];
    $productid            = (int)$sowingRow['productid'];


    /* -------------------------------------------------------
       STEP 2 : GET ALL TRAYS OF SAME LOT / ORDER / PRODUCT
    ------------------------------------------------------- */ 

    $lotQuery = "
        SELECT
            s.*
        FROM krf_polyhouse_sowingplant_details AS s

        INNER JOIN product_master AS p
            ON p.productID = s.productid
           AND p.projectid = s.projectid

        WHERE s.sowinglotno_internal = ?
          AND s.projectid = ?
          AND s.stockorder_no = ?
          AND s.productid = ?
          AND p.IsGrafted = 'NonGrafted'
          AND s.workingstatus = 'WithoutGermination'
          and (s.polyhouseid is null or s.polyhouseid ='' and s.polyhouseid=0)
    ";

    $lotStmt = $krfconn->prepare($lotQuery);

    if (!$lotStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Lot query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }

    $lotStmt->bind_param(
        "sisi",
        $sowinglotno_internal,
        $projectid,
        $orderno,
        $productid
    );

    $lotStmt->execute();

    $lotResult = $lotStmt->get_result();

    $trayDetails = [];

    while ($row = $lotResult->fetch_assoc()) {

        if (
            $row['germination_qrcode'] !== null &&
            trim($row['germination_qrcode']) !== ''
        ) {

            $lotStmt->close();

            echo json_encode([
                'status'  => 'error',
                'message' => 'One or more trays are already allocated.'
            ]);
            exit;
        }

        $trayDetails[] = $row;
    }

    $lotStmt->close();


    /* -------------------------------------------------------
       FINAL RESPONSE
    ------------------------------------------------------- */

    if (empty($trayDetails)) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'No eligible trays found for this lot.'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $trayDetails
    ]);

    exit;
}else if ($action == "barcodedirectSowing_polyhouse") {

    $companyid    = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid    = (int)($_POST['projectid'] ?? 0);
    $barcode      = trim($_POST['qrcode'] ?? '');
    $status       = trim($_POST['status'] ?? '');
    /* -------------------------------------------------------
       BASIC VALIDATION
    ------------------------------------------------------- */

    if ($barcode === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'QR Code is empty.'
        ]);
        exit;
    }

    if ($status === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Status is empty.'
        ]);
        exit;
    }

    if ($projectid <= 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid project.'
        ]);
        exit;
    }

    /* -------------------------------------------------------
       STEP 1 : FIND SCANNED TRAY
    ------------------------------------------------------- */

    $sowingIntQuery = "
        SELECT
            s.sowinglotno_internal,
            s.stockorder_no,
            s.productid
        FROM krf_polyhouse_sowingplant_details AS s

        INNER JOIN product_master AS p
            ON p.productID = s.productid
           AND p.projectid = s.projectid

        WHERE s.barcode = ?
          AND s.projectid = ?
          AND s.status = ?
          AND p.IsGrafted = 'NonGrafted'
          AND (
                s.germination_qrcode IS NULL
                OR s.germination_qrcode = ''
              )
          AND s.workingstatus = 'WithoutGermination'
          and s.status ='Tray Received At Germination'
          and s.polyhouseid is not null and s.polyhouseid <>''

        LIMIT 1
    ";

    $sowingIntStmt = $krfconn->prepare($sowingIntQuery);

    if (!$sowingIntStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }

    $sowingIntStmt->bind_param(
        "sis",
        $barcode,
        $projectid,
        $status
    );

    $sowingIntStmt->execute();

    $sowingIntResult = $sowingIntStmt->get_result();

    if ($sowingIntResult->num_rows === 0) {

        $sowingIntStmt->close();

        echo json_encode([
            'status'  => 'error',
            'message' => 'Tray does not exist or is not eligible.'
        ]);
        exit;
    }

    $sowingRow = $sowingIntResult->fetch_assoc();

    $sowingIntStmt->close();

 

    $sowinglotno_internal = $sowingRow['sowinglotno_internal'];
    $orderno              = $sowingRow['stockorder_no'];
    $productid            = (int)$sowingRow['productid'];


    /* -------------------------------------------------------
       STEP 2 : GET ALL TRAYS OF SAME LOT / ORDER / PRODUCT
    ------------------------------------------------------- */

    $lotQuery = "
        SELECT
            s.*
        FROM krf_polyhouse_sowingplant_details AS s

        INNER JOIN product_master AS p
            ON p.productID = s.productid
           AND p.projectid = s.projectid

        WHERE s.sowinglotno_internal = ?
          AND s.projectid = ?
          AND s.stockorder_no = ?
          AND s.productid = ?
          AND p.IsGrafted = 'NonGrafted'
          and s.polyhouseid is not null and s.polyhouseid <>''
          AND s.workingstatus = 'WithoutGermination'
          and s.status ='Tray Received At Germination'
    ";

    $lotStmt = $krfconn->prepare($lotQuery);

    if (!$lotStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Lot query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }

    $lotStmt->bind_param(
        "sisi",
        $sowinglotno_internal,
        $projectid,
        $orderno,
        $productid
    );

    $lotStmt->execute();

    $lotResult = $lotStmt->get_result();

    $trayDetails = [];

    while ($row = $lotResult->fetch_assoc()) {

        if (
            $row['germination_qrcode'] !== null &&
            trim($row['germination_qrcode']) !== ''
        ) {

            $lotStmt->close();

            echo json_encode([
                'status'  => 'error',
                'message' => 'One or more trays are already allocated.'
            ]);
            exit;
        }

        $trayDetails[] = $row;
    }

    $lotStmt->close();


    /* -------------------------------------------------------
       FINAL RESPONSE
    ------------------------------------------------------- */

    if (empty($trayDetails)) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'No eligible trays found for this lot.'
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'data'   => $trayDetails
    ]);

    exit;
}else if ($action === "Updatestatusstage1") {

    $projectid         = (int)($_POST['projectid'] ?? 0);
    $loginid           = trim($_POST['loginid'] ?? '');
    $pstockorderno     = trim($_POST['pstockorderno'] ?? '');
    $productid         = trim($_POST['productid'] ?? '');
    $changestatus      = trim($_POST['changestatus'] ?? '');
    $tableactualstatus = trim($_POST['tableactualstatus'] ?? '');

    /* -------------------------------------------------------
       Validation
    ------------------------------------------------------- */
    if (
        $projectid <= 0 ||
        $loginid === '' ||
        $pstockorderno === '' ||
        $productid === '' ||
        $changestatus === '' ||
        $tableactualstatus === ''
    ) {
        echo json_encode([
            "status"  => "error",
            "message" => "Missing required parameters."
        ]);
        exit;
    }

    try {

        $krfconn->begin_transaction(); 

     

        $sql = " SELECT 1
            FROM krf_polyhouse_sowingplant_details
            WHERE projectid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND workingstatus = 'GerminationInProcess'
            LIMIT 1
            FOR UPDATE
        ";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception(
                "Germination check prepare failed: " . $krfconn->error
            );
        }
        $stmt->bind_param(
            "iss",
            $projectid,
            $pstockorderno,
            $productid
        );
        if (!$stmt->execute()) {
            throw new Exception(
                "Germination check failed: " . $stmt->error
            );
        }
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $stmt->close();
            $krfconn->rollback();
            echo json_encode([
                "status"  => "error",
                "message" => "Order already contains Germination Box."
            ]);
            exit;
        }

        $stmt->close();
        /* ---------2. Update sowing plant details*/
        $sql = "
            UPDATE krf_polyhouse_sowingplant_details
            SET
                status = ?,
                updated_by = ?,
                updated_at = NOW(),
                workingstatus = 'WithoutGermination',
                germinationwithtray ='yes'
            WHERE projectid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND status = ?
              AND workingstatus = 'SowingInProcess'
        ";

        $stmt = $krfconn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Plant detail prepare failed: " . $krfconn->error
            );
        }

        $stmt->bind_param(
            "ssisss",
            $changestatus,
            $loginid,
            $projectid,
            $pstockorderno,
            $productid,
            $tableactualstatus
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Plant detail update failed: " . $stmt->error
            );
        }

        $affectedPlantRows = $stmt->affected_rows;

        $stmt->close();
        if ($affectedPlantRows === 0) {
            throw new Exception(
                "No matching sowing record found or status has already changed."
            );
        }    

        /* -------------------------- Commit ---- */
        $krfconn->commit();

        echo json_encode([
            "status"  => "success",
            "message" => "Data saved successfully."
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();
        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }

    exit;
}else if ($action == "updatestage2") {
    $companyid         = $_POST['companyid'] ?? '';
    $subcompanyid      = $_POST['subcompanyid'] ?? '';
    $projectid         = $_POST['projectid'] ?? '';
    $loginid           = $_POST['loginid'] ?? '';
    $boxids = json_decode($_POST['boxids'] ?? '[]', true);
    $changestatus      = $_POST['tablechangestatus'] ?? '';
    $tableactualstatus = $_POST['tableactualstatus'] ?? '';
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    if (
        empty($boxids) ||
        empty($changestatus) ||
        empty($tableactualstatus) ||
        empty($projectid)||
        empty($polyhouseid)
    ) {
        echo json_encode([
            "status"  => "error",
            "message" => "Missing required parameters."
        ]);
        exit;
    }

    try {

        $krfconn->begin_transaction();

        $sql = "
            UPDATE krf_polyhouse_sowingplant_details
            SET
                polyhouseid=?,
                status = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE barcode = ?
              AND projectid = ?
              AND status = ?
              and trim(lcase(germinationwithtray)) ='yes'
        ";

        $updatestmt = $krfconn->prepare($sql);

        if (!$updatestmt) {
            throw new Exception(
                "Prepare failed: " . $krfconn->error
            );
        }

        foreach ($boxids as $boxid) {

            $barcode = trim((string)$boxid);

            if ($barcode === '') {
                continue;
            }

            $updatestmt->bind_param(
                "ssssis",
                $polyhouseid,
                $changestatus,
                $loginid,
                $barcode,
                $projectid,
                $tableactualstatus
            );

            if (!$updatestmt->execute()) {
                throw new Exception(
                    "Update failed for barcode {$barcode}: " .
                    $updatestmt->error
                );
            }
        }

        $updatestmt->close();

        $krfconn->commit();

        echo json_encode([
            "status"  => "success",
            "message" => "Trays received at Groom successfully."
        ]);

    } catch (Exception $e) {

        $krfconn->rollback();

        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }

    exit;
} else if ($action == "UpdateTrayAllocationToPolyhouse") {

 

    $companyid     = $_POST['companyid'] ?? '';
    $subcompanyid  = $_POST['subcompanyid'] ?? '';
    $projectid     = (int)($_POST['projectid'] ?? 0);
    $loginid       = $_POST['loginid'] ?? '';
    $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
    $bedrackname   = trim($_POST['bedrackname'] ?? '');
    $trayposition  = trim($_POST['placeofrow'] ?? '');
    $gfailurestatus = trim($_POST['gfailurestatus'] ?? 'Healthy');

    $boxidsRaw = $_POST['boxids'] ?? null;

    /* =====================================================
       1. VALIDATION
    ===================================================== */

    if ($projectid <= 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid project."
        ]);
        exit;
    }

    if ($polyhouseid <= 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid polyhouse."
        ]);
        exit;
    }

    if (empty($boxidsRaw)) {
        echo json_encode([
            "status"  => "error",
            "message" => "No trays provided for allocation."
        ]);
        exit;
    }


    /* =====================================================
       2. DECODE BARCODE DATA
    ===================================================== */

    $boxids = is_string($boxidsRaw) ? json_decode($boxidsRaw, true): $boxidsRaw;
    if (!is_array($boxids) || empty($boxids)) {
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid tray data."
        ]);
        exit;
    }

    $trayRows = $boxids;
    if (
        isset($boxids[0]) &&
        is_array($boxids[0]) &&
        isset($boxids[0][0]) &&
        is_array($boxids[0][0])
    ) {
        $trayRows = $boxids[0];
    }


    /* =====================================================
       3. DISTINCT VALID BARCODES
    ===================================================== */

    $distinctBarcodes = array_values(
        array_unique(
            array_filter(
                array_map(
                    'trim',
                    array_column($trayRows, 'barcode')
                ),
                function ($barcode) {
                    return $barcode !== '';
                }
            )
        )
    );


    if (empty($distinctBarcodes)) {
        echo json_encode([
            "status"  => "error",
            "message" => "No valid tray barcodes found."
        ]);
        exit;
    }


    try {

        $krfconn->begin_transaction();

        $locStmt = $krfconn->prepare("
            SELECT locationid
            FROM krf_polyhouse_master
            WHERE polyhouseid = ?
              AND projectid = ?
            LIMIT 1
        ");

        if (!$locStmt) {
            throw new Exception(
                "Location query prepare failed: " . $krfconn->error
            );
        }

        $locStmt->bind_param(
            "ii",
            $polyhouseid,
            $projectid
        );

        $locStmt->execute();

        $phData = $locStmt
            ->get_result()
            ->fetch_assoc();

        $locStmt->close();

        $locationid = $phData['locationid'] ?? '';

        if ($locationid === '') {
            throw new Exception(
                "Polyhouse location not found."
            );
        }


        /* =====================================================
           5. DETERMINE TARGET STATUS
        ===================================================== */

        $targetStatus = (
            $gfailurestatus === 'Healthy'
        )
            ? 'Polyhouse'
            : $gfailurestatus;


        /* =====================================================
           6. PREPARE INSERT ONCE
        ===================================================== */

        $moveSql = "
            INSERT INTO krf_polyhouse_plant_details
            (
                psono,
                roottype,
                sowingplantid,
                stockorder_no,
                locationid,
                labourid,
                polyhouseid,
                projectid,
                lotno,
                batchno,
                barcode,
                productid,
                productname,
                tray_id,
                qtypertray,
                tray_position,
                bedrackname,
                bed_no,
                planted_date,
                workingstatus,
                status,
                remarks,
                sowing_date,
                sowingdate_internal,
                sowinglotno_internal,
                sowingbatchno,
                dispatchdate_internal,
                created_by
            )

            SELECT
                s.psono,
                s.roottype,
                s.sowingplantid,
                s.stockorder_no,
                ?,
                s.labourid,
                ?,
                s.projectid,
                s.lotno,
                s.sowingbatchno,
                s.barcode,
                s.productid,
                s.productname,
                s.trayid,
                s.traycavity,
                ?,
                ?,
                ?,
                NOW(),
                'Polyhouse',
                ?,
                s.remarks,
                s.sowing_date,
                s.sowingdate_internal,
                s.sowinglotno_internal,
                s.sowingbatchno,
                s.dispatchdate_internal,
                ?

            FROM krf_polyhouse_sowingplant_details AS s

            WHERE s.barcode = ?
              AND s.projectid = ?
              AND s.status = 'Tray Received At Germination'
              AND s.workingstatus = 'WithoutGermination'
        ";

        $moveStmt = $krfconn->prepare($moveSql);

        if (!$moveStmt) {
            throw new Exception(
                "Move query prepare failed: " . $krfconn->error
            );
        }


        /* =====================================================
           7. PREPARE SOURCE UPDATE ONCE
        ===================================================== */

        $updateSourceSql = "
            UPDATE krf_polyhouse_sowingplant_details

            SET
                status = ?,
                workingstatus = 'Polyhouse',
                updated_at = NOW(),
                updated_by = ?

            WHERE barcode = ?
              AND projectid = ?
              AND status = 'Tray Received At Germination'
              AND workingstatus = 'WithoutGermination'
        ";

        $updStmt = $krfconn->prepare($updateSourceSql);

        if (!$updStmt) {
            throw new Exception(
                "Source update prepare failed: " .
                $krfconn->error
            );
        }


        /* =====================================================
      


        /* =====================================================
           9. PROCESS EACH DISTINCT BARCODE
        ===================================================== */

        $totalInserted = 0;

        foreach ($distinctBarcodes as $barcode) { 
            $moveStmt->bind_param(
                "sissssssi",
                $locationid,
                $polyhouseid,
                $trayposition,
                $bedrackname,
                $bedrackname,
                $targetStatus,
                $loginid,
                $barcode,
                $projectid
            );

            if (!$moveStmt->execute()) {
                throw new Exception(
                    "Failed to move tray {$barcode}: " .
                    $moveStmt->error
                );
            }


            $inserted = $moveStmt->affected_rows;


            /* -----------------------------------------------
               Nothing inserted
            ----------------------------------------------- */

            if ($inserted <= 0) {

                throw new Exception(
                    "Tray {$barcode} could not be moved. " .
                    "It may already be allocated or its status is invalid."
                );
            }


            $totalInserted += $inserted;


            /* -----------------------------------------------
               UPDATE SOURCE TRAY
            ----------------------------------------------- */

            $updStmt->bind_param(
                "sssi",
                $targetStatus,
                $loginid,
                $barcode,
                $projectid
            );

            if (!$updStmt->execute()) {

                throw new Exception(
                    "Failed to update source tray {$barcode}: " .
                    $updStmt->error
                );
            }


            
        }


        /* =====================================================
           10. CLOSE PREPARED STATEMENTS
        ===================================================== */

        $moveStmt->close();
        $updStmt->close(); 


        /* =====================================================
           11. FINAL VALIDATION
        ===================================================== */

        if ($totalInserted <= 0) {
            throw new Exception(
                "No trays were moved to the polyhouse."
            );
        }


        /* =====================================================
           12. COMMIT
        ===================================================== */

        $krfconn->commit();


        echo json_encode([
            "status"  => "success",
            "message" =>
                "Allocation completed successfully. " .
                "Total {$totalInserted} trays moved.",
            "data" => [
                "allocatedtrays" => $totalInserted,
                "barcodes"       => $distinctBarcodes,
                "barcodecount"   => count($distinctBarcodes)
            ]
        ]);

        exit;


    } catch (Throwable $e) {

        try {
            $krfconn->rollback();
        } catch (Throwable $ignored) {
        }

        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);

        exit;
    }
}else if ($action === "undoMakeGerminationBox") {

    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $productid     = (int)($_POST['productid'] ?? 0);

    if ($stockorder_no === '' || $productid <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid Stock Order No or Product ID.'
        ]);
        exit;
    }

    /*
     * Check whether ANY row has moved to a different status.
     * If yes, complete Undo must be stopped.
     */
    $checkSql = "
        SELECT
            COUNT(*) AS total_rows,
            SUM(
                CASE
                    WHEN status = 'Tray Received At Germination'
                     AND workingstatus = 'WithoutGermination'
                    THEN 1
                    ELSE 0
                END
            ) AS valid_rows
        FROM krf_polyhouse_sowingplant_details
        WHERE stockorder_no = ?
          AND productid = ?
    ";

    $checkStmt = $krfconn->prepare($checkSql);

    if (!$checkStmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Prepare failed: ' . $krfconn->error
        ]);
        exit;
    }

    $checkStmt->bind_param("si", $stockorder_no, $productid);
    $checkStmt->execute();

    $result = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    $totalRows = (int)($result['total_rows'] ?? 0);
    $validRows = (int)($result['valid_rows'] ?? 0);

    /*
     * Nothing found
     */
    if ($totalRows <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'No sowing trays found.'
        ]);
        exit;
    }

    /*
     * IMPORTANT:
     * If even ONE row has another status,
     * do not update anything.
     */
    if ($totalRows !== $validRows) {
        echo json_encode([
            'success' => false,
            'message' => 'Undo is not allowed because one or more trays have already moved to another status.'
        ]);
        exit;
    }

    /*
     * All rows have correct status.
     * Now safe to undo.
     */
    $sql = "
        UPDATE krf_polyhouse_sowingplant_details
        SET
            status = 'Sowing',
            workingstatus = 'SowingInProcess' ,germinationwithtray =null
        WHERE stockorder_no = ?
          AND productid = ?
          AND status = 'Tray Received At Germination'
          AND workingstatus = 'WithoutGermination'
    ";

    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'message' => 'Prepare failed: ' . $krfconn->error
        ]);
        exit;
    }

    $stmt->bind_param("si", $stockorder_no, $productid);

    if (!$stmt->execute()) {

        echo json_encode([
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error
        ]);

        $stmt->close();
        exit;
    }

    $affectedRows = $stmt->affected_rows;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => $affectedRows . ' tray(s) moved back to Sowing.',
        'affected_rows' => $affectedRows
    ]);

    exit;
} 
?>