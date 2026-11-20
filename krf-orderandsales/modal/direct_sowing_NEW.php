<?php
/** @var mysqli $krfconn */
error_reporting(E_ALL);
require('../../connection.php');
 
date_default_timezone_set('Asia/Kolkata');
$action = $_GET['action'];

  if ($action == "barcodedirectSowing_polyhouse") {

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
            s.sowingbatchno,
            s.stockorder_no,
            s.productid,
            s.polyhouseid,
            s.locationid
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
                OR TRIM(s.germination_qrcode) = ''
              )
          AND s.workingstatus = 'WithoutGermination'
          AND LOWER(TRIM(s.germinationwithtray)) = 'yes'
          AND s.polyhouseid IS NOT NULL
          AND s.polyhouseid <> ''
          AND s.polyhouseid <> 0

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

 

    $sowinglotno_internal = trim($sowingRow['sowinglotno_internal'] ?? '');
    $sowingbatchno        = trim($sowingRow['sowingbatchno'] ?? '');
    $orderno              = trim($sowingRow['stockorder_no'] ?? '');
    $productid            = (int)($sowingRow['productid'] ?? 0);
    $polyhouseid          = (int)($sowingRow['polyhouseid'] ?? 0);

    if (
        $sowinglotno_internal === '' ||
        $sowingbatchno === '' ||
        $orderno === '' ||
        $productid <= 0 ||
        $polyhouseid <= 0
    ) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'The scanned tray has incomplete batch information.'
        ]);
        exit;
    }


    /* -------------------------------------------------------
       STEP 2 : GET ALL TRAYS OF SAME LOT / ORDER / PRODUCT
    ------------------------------------------------------- */

    $lotQuery = "
        SELECT
            s.stockorder_no,
            s.productid,
            MAX(s.productname) AS productname,
            s.sowinglotno_internal,
            s.sowingbatchno,
            s.polyhouseid,
            MAX(s.locationid) AS locationid,
            MAX(s.roottype) AS roottype,
            MAX(s.seedid) AS seedid,
            MAX(s.sowingdate_internal) AS sowingdate_internal,
            COUNT(*) AS total_trays,
            COALESCE(SUM(s.traycavity), 0) AS total_qty,
            SUM(
                CASE
                    WHEN s.germination_qrcode IS NOT NULL
                     AND TRIM(s.germination_qrcode) <> ''
                    THEN 1
                    ELSE 0
                END
            ) AS already_allocated_trays
        FROM krf_polyhouse_sowingplant_details AS s

        INNER JOIN product_master AS p
            ON p.productID = s.productid
           AND p.projectid = s.projectid

        WHERE s.sowinglotno_internal = ?
          AND s.projectid = ?
          AND s.stockorder_no = ?
          AND s.productid = ?
          AND s.sowingbatchno = ?
          AND s.polyhouseid = ?
          AND s.status = ?
          -- AND p.IsGrafted = 'NonGrafted'
          AND s.workingstatus = 'WithoutGermination'
          AND LOWER(TRIM(s.germinationwithtray)) = 'yes'
        GROUP BY
            s.stockorder_no,
            s.productid,
            s.sowinglotno_internal,
            s.sowingbatchno,
            s.polyhouseid
        LIMIT 1
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
        "sisisis",
        $sowinglotno_internal,
        $projectid,
        $orderno,
        $productid,
        $sowingbatchno,
        $polyhouseid,
        $status
    );

    $lotStmt->execute();

    $trayDetails = $lotStmt->get_result()->fetch_assoc();

    $lotStmt->close();


    /* -------------------------------------------------------
       FINAL RESPONSE
    ------------------------------------------------------- */

    if (!$trayDetails) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'No eligible trays found for this lot.'
        ]);
        exit;
    }

    if ((int)$trayDetails['already_allocated_trays'] > 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => $trayDetails['already_allocated_trays'] .
                         ' tray(s) from this batch are already allocated.'
        ]);
        exit;
    }

    unset($trayDetails['already_allocated_trays']);

    $trayDetails['scanned_barcode'] = $barcode;
    $trayDetails['projectid']       = $projectid;
    $trayDetails['productid']       = (int)$trayDetails['productid'];
    $trayDetails['polyhouseid']     = (int)$trayDetails['polyhouseid'];
    $trayDetails['total_trays']     = (int)$trayDetails['total_trays'];
    $trayDetails['total_qty']       = (int)$trayDetails['total_qty'];

    echo json_encode([
        'status' => 'success',
        'data'   => $trayDetails
    ], JSON_UNESCAPED_UNICODE);

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

    $projectid           = (int)($_POST['projectid'] ?? 0);
    $loginid             = trim($_POST['loginid'] ?? '');
    $polyhouseid         = (int)($_POST['polyhouseid'] ?? 0);
    $sourcePolyhouseid   = (int)($_POST['source_polyhouseid'] ?? 0);
    $targetLocationid    = trim($_POST['target_locationid'] ?? '');
    $bedrackname         = trim($_POST['bedrackname'] ?? '');
    $trayposition        = trim($_POST['placeofrow'] ?? '');
    $gfailurestatus      = trim($_POST['gfailurestatus'] ?? 'Healthy');
    $bedQty              = (int)($_POST['bedqty'] ?? 0);
    $stockorderNo        = trim($_POST['stockorder_no'] ?? '');
    $productid           = (int)($_POST['productid'] ?? 0);
    $sowinglotnoInternal = trim($_POST['sowinglotno_internal'] ?? '');
    $sowingbatchno       = trim($_POST['sowingbatchno'] ?? '');

    if (
        $projectid <= 0 ||
        $polyhouseid <= 0 ||
        $sourcePolyhouseid <= 0 ||
        $productid <= 0 ||
        $bedQty <= 0 ||
        $loginid === '' ||
        $targetLocationid === '' ||
        $bedrackname === '' ||
        $trayposition === '' ||
        $stockorderNo === '' ||
        $sowinglotnoInternal === '' ||
        $sowingbatchno === ''
    ) {
        echo json_encode([
            "status"  => "error",
            "message" => "Missing required allocation information."
        ]);
        exit;
    }

    $targetStatus = ($gfailurestatus === 'Healthy')
        ? 'Polyhouse'
        : $gfailurestatus;

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
            throw new Exception("Location query prepare failed: " . $krfconn->error);
        }

        $locStmt->bind_param("ii", $polyhouseid, $projectid);
        $locStmt->execute();
        $phData = $locStmt->get_result()->fetch_assoc();
        $locStmt->close();

        $locationid = trim((string)($phData['locationid'] ?? ''));
        if ($locationid === '') {
            throw new Exception("Polyhouse location not found.");
        }

        if ((string)$locationid !== (string)$targetLocationid) {
            throw new Exception("The selected polyhouse does not belong to the selected target location.");
        }

        $krfconn->query("DROP TEMPORARY TABLE IF EXISTS tmp_polyhouse_trays");

        if (!$krfconn->query("
            CREATE TEMPORARY TABLE tmp_polyhouse_trays
            (
                barcode VARCHAR(255) NOT NULL PRIMARY KEY
            ) ENGINE=InnoDB
        ")) {
            throw new Exception("Temporary selection table creation failed: " . $krfconn->error);
        }

        /* Select exactly bedQty eligible trays from the scanned batch. */
        $selectSql = "
            INSERT INTO tmp_polyhouse_trays (barcode)
            SELECT s.barcode
            FROM krf_polyhouse_sowingplant_details AS s
            INNER JOIN product_master AS p
                ON p.productID = s.productid
               AND p.projectid = s.projectid
            WHERE s.projectid = ?
              AND s.stockorder_no = ?
              AND s.productid = ?
              AND s.sowinglotno_internal = ?
              AND s.sowingbatchno = ?
              AND s.polyhouseid = ?
              AND s.status = 'Tray Allocated To Polyhouse'
              AND s.workingstatus = 'WithoutGermination'
              -- AND p.IsGrafted = 'NonGrafted'
              AND LOWER(TRIM(s.germinationwithtray)) = 'yes'
              AND (s.germination_qrcode IS NULL OR TRIM(s.germination_qrcode) = '')
              AND s.barcode IS NOT NULL
              AND TRIM(s.barcode) <> ''
            ORDER BY s.barcode
            LIMIT ?
        ";

        $selectStmt = $krfconn->prepare($selectSql);
        if (!$selectStmt) {
            throw new Exception("Tray selection prepare failed: " . $krfconn->error);
        }

        $selectStmt->bind_param(
            "isissii",
            $projectid,
            $stockorderNo,
            $productid,
            $sowinglotnoInternal,
            $sowingbatchno,
            $sourcePolyhouseid,
            $bedQty
        );
        $selectStmt->execute();
        $selectedTrays = $selectStmt->affected_rows;
        $selectStmt->close();

        if ($selectedTrays !== $bedQty) {
            throw new Exception(
                "Only {$selectedTrays} eligible tray(s) are currently available."
            );
        }

        /* Move the selected trays in one INSERT ... SELECT. */
        $moveSql = "
            INSERT INTO krf_polyhouse_plant_details
            (
                psono, roottype, sowingplantid, stockorder_no,
                locationid, labourid, polyhouseid, projectid,
                lotno, batchno, barcode, productid, productname,
                tray_id, qtypertray, tray_position, bedrackname,
                bed_no, planted_date, workingstatus, status,
                remarks, sowing_date, sowingdate_internal,
                sowinglotno_internal, sowingbatchno,
                dispatchdate_internal, created_by
            )
            SELECT
                s.psono, s.roottype, s.sowingplantid, s.stockorder_no,
                ?, s.labourid, ?, s.projectid,
                s.lotno, s.sowingbatchno, s.barcode, s.productid,
                s.productname, s.trayid, s.traycavity,
                ?, ?, ?, NOW(), 'Polyhouse', ?,
                s.remarks, s.sowing_date, s.sowingdate_internal,
                s.sowinglotno_internal, s.sowingbatchno,
                s.dispatchdate_internal, ?
            FROM krf_polyhouse_sowingplant_details AS s
            INNER JOIN tmp_polyhouse_trays AS t
                ON t.barcode = s.barcode
            WHERE s.projectid = ?
              AND s.status = 'Tray Allocated To Polyhouse'
              AND s.workingstatus = 'WithoutGermination'
        ";

        $moveStmt = $krfconn->prepare($moveSql);
        if (!$moveStmt) {
            throw new Exception("Move query prepare failed: " . $krfconn->error);
        }

        $moveStmt->bind_param(
            "sisssssi",
            $locationid,
            $polyhouseid,
            $trayposition,
            $bedrackname,
            $bedrackname,
            $targetStatus,
            $loginid,
            $projectid
        );
        $moveStmt->execute();
        $totalInserted = $moveStmt->affected_rows;
        $moveStmt->close();

        if ($totalInserted !== $selectedTrays) {
            throw new Exception("Not all selected trays could be moved.");
        }

        /* Update the same selected source trays in one statement. */
        $updateSql = "
            UPDATE krf_polyhouse_sowingplant_details AS s
            INNER JOIN tmp_polyhouse_trays AS t
                ON t.barcode = s.barcode
            SET
                s.status = ?,
                s.workingstatus = 'Polyhouse',
                s.updated_at = NOW(),
                s.updated_by = ?
            WHERE s.projectid = ?
              AND s.status = 'Tray Allocated To Polyhouse'
              AND s.workingstatus = 'WithoutGermination'
        ";

        $updateStmt = $krfconn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("Source update prepare failed: " . $krfconn->error);
        }

        $updateStmt->bind_param(
            "ssi",
            $targetStatus,
            $loginid,
            $projectid
        );
        $updateStmt->execute();
        $updatedTrays = $updateStmt->affected_rows;
        $updateStmt->close();

        if ($updatedTrays !== $selectedTrays) {
            throw new Exception("Not all selected source trays could be updated.");
        }

        /* Return refreshed remaining totals for the same batch. */
        $remainingSql = "
            SELECT
                COUNT(*) AS remaining_trays,
                COALESCE(SUM(traycavity), 0) AS remaining_qty
            FROM krf_polyhouse_sowingplant_details
            WHERE projectid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND sowinglotno_internal = ?
              AND sowingbatchno = ?
              AND polyhouseid = ?
              AND status = 'Tray Allocated To Polyhouse'
              AND workingstatus = 'WithoutGermination'
              AND LOWER(TRIM(germinationwithtray)) = 'yes'
              AND (germination_qrcode IS NULL OR TRIM(germination_qrcode) = '')
        ";

        $remainingStmt = $krfconn->prepare($remainingSql);
        if (!$remainingStmt) {
            throw new Exception("Remaining tray query prepare failed: " . $krfconn->error);
        }

        $remainingStmt->bind_param(
            "isissi",
            $projectid,
            $stockorderNo,
            $productid,
            $sowinglotnoInternal,
            $sowingbatchno,
            $sourcePolyhouseid
        );
        $remainingStmt->execute();
        $remainingData = $remainingStmt->get_result()->fetch_assoc();
        $remainingStmt->close();

        $krfconn->query("DROP TEMPORARY TABLE IF EXISTS tmp_polyhouse_trays");
        $krfconn->commit();

        echo json_encode([
            "status"  => "success",
            "message" => "Allocation completed successfully. Total {$totalInserted} trays moved.",
            "data"    => [
                "allocatedtrays" => $totalInserted,
                "remaining_trays" => (int)($remainingData['remaining_trays'] ?? 0),
                "remaining_qty" => (int)($remainingData['remaining_qty'] ?? 0)
            ]
        ]);
        exit;

    } catch (Throwable $e) {
        try {
            $krfconn->query("DROP TEMPORARY TABLE IF EXISTS tmp_polyhouse_trays");
            $krfconn->rollback();
        } catch (Throwable $ignored) {
        }

        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
        exit;
    }
}

 
?>