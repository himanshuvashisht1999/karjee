<?php
/** @var mysqli $krfconn */
error_reporting(E_ALL);
require('../../connection.php');
 
date_default_timezone_set('Asia/Kolkata');
$action = $_GET['action'];
if ($action === "getpolyhouse") {
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
}else if ($action === "barcodedirectSowing") {

    $projectid = (int)($_POST['projectid'] ?? 0);
    $barcode   = trim($_POST['qrcode'] ?? '');
    $status    = trim($_POST['status'] ?? '');

    /* =====================================================
       1. BASIC VALIDATION
    ===================================================== */

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


    /* =====================================================
       2. FIND THE SCANNED TRAY
    ===================================================== */

    $sowingIntQuery = "
        SELECT
            s.sowinglotno_internal,
            s.sowingbatchno,
            s.stockorder_no,
            s.productid,
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
          AND (
                s.polyhouseid IS NULL
                OR s.polyhouseid = ''
                OR s.polyhouseid = 0
              )
        LIMIT 1
    ";

    $sowingIntStmt = $krfconn->prepare($sowingIntQuery);

    if (!$sowingIntStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Scanned tray query prepare failed: ' .
                         $krfconn->error
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

    $sowingResult = $sowingIntStmt->get_result();
    $sowingRow = $sowingResult->fetch_assoc();

    $sowingIntStmt->close();

    if (!$sowingRow) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Tray does not exist or is not eligible for polyhouse allocation.'
        ]);
        exit;
    }

    $sowinglotnoInternal = trim(
        $sowingRow['sowinglotno_internal'] ?? ''
    );

    $sowingbatchno = trim(
        $sowingRow['sowingbatchno'] ?? ''
    );

    $stockorderNo = trim(
        $sowingRow['stockorder_no'] ?? ''
    );

    $productid = (int)(
        $sowingRow['productid'] ?? 0
    );

    if (
        $sowinglotnoInternal === '' ||
        $sowingbatchno === '' ||
        $stockorderNo === '' ||
        $productid <= 0
    ) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'The scanned tray has incomplete lot, batch, order or product information.'
        ]);
        exit;
    }


    /* =====================================================
       3. GET ONE SUMMARY FOR THE COMPLETE BATCH
    ===================================================== */

    $lotQuery = "
        SELECT
            s.stockorder_no,
            s.productid,
            MAX(s.productname) AS productname,
            s.sowinglotno_internal,
            s.sowingbatchno,
            MAX(s.locationid) AS locationid,

            COUNT(*) AS total_trays,

            COALESCE(
                SUM(s.traycavity),
                0
            ) AS total_qty,

            SUM(
                CASE
                    WHEN s.germination_qrcode IS NOT NULL
                     AND TRIM(s.germination_qrcode) <> ''
                    THEN 1
                    ELSE 0
                END
            ) AS already_allocated_trays

        FROM krf_polyhouse_sowingplant_details AS s

        WHERE s.sowinglotno_internal = ?
          AND s.projectid = ?
          AND s.stockorder_no = ?
          AND s.productid = ?
          AND s.sowingbatchno = ?
          AND s.status = ?
          AND s.workingstatus = 'WithoutGermination'
          AND LOWER(TRIM(s.germinationwithtray)) = 'yes'
          AND (
                s.polyhouseid IS NULL
                OR s.polyhouseid = ''
                OR s.polyhouseid = 0
              )

        GROUP BY
            s.stockorder_no,
            s.productid,
            s.sowinglotno_internal,
            s.sowingbatchno

        LIMIT 1
    ";

    $lotStmt = $krfconn->prepare($lotQuery);

    if (!$lotStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Batch summary query prepare failed: ' .
                         $krfconn->error
        ]);
        exit;
    }

    $lotStmt->bind_param(
        "sisiss",
        $sowinglotnoInternal,
        $projectid,
        $stockorderNo,
        $productid,
        $sowingbatchno,
        $status
    );

    $lotStmt->execute();

    $batchData = $lotStmt
        ->get_result()
        ->fetch_assoc();

    $lotStmt->close();

    if (!$batchData) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No eligible trays found for this lot and batch.'
        ]);
        exit;
    }


    /* =====================================================
       4. CHECK WHETHER ANY TRAY IS ALREADY ALLOCATED
    ===================================================== */

    if ((int)$batchData['already_allocated_trays'] > 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => $batchData['already_allocated_trays'] .
                         ' tray(s) from this batch are already allocated.'
        ]);
        exit;
    }

    unset($batchData['already_allocated_trays']);

    // Return the barcode actually scanned by the user.
    $batchData['scanned_barcode'] = $barcode;

    // Return proper numeric values.
    $batchData['productid']    = (int)$batchData['productid'];
    $batchData['total_trays']  = (int)$batchData['total_trays'];
    $batchData['total_qty']    = (int)$batchData['total_qty'];
    $batchData['projectid']    = $projectid;


    /* =====================================================
       5. FINAL RESPONSE
    ===================================================== */

    echo json_encode([
        'status' => 'success',
        'data'   => $batchData
    ], JSON_UNESCAPED_UNICODE);

    exit;
}else if ($action == "updatestage2") {
    $projectid              = (int)($_POST['projectid'] ?? 0);
    $polyhouseid            = (int)($_POST['polyhouseid'] ?? 0);
    $productid              = (int)($_POST['productid'] ?? 0);
    $loginid                = trim($_POST['loginid'] ?? '');
    $stockorderNo           = trim($_POST['stockorder_no'] ?? '');
    $sowinglotnoInternal    = trim($_POST['sowinglotno_internal'] ?? '');
    $sowingbatchno          = trim($_POST['sowingbatchno'] ?? '');
    $scannedBarcode         = trim($_POST['scanned_barcode'] ?? '');
    $changestatus           = trim($_POST['tablechangestatus'] ?? '');
    $tableactualstatus      = trim($_POST['tableactualstatus'] ?? '');
 

    if (
        $projectid <= 0 ||
        $polyhouseid <= 0 ||
        $productid <= 0 ||
        $loginid === '' ||
        $stockorderNo === '' ||
        $sowinglotnoInternal === '' ||
        $sowingbatchno === '' ||
        $scannedBarcode === '' ||
        $changestatus === '' ||
        $tableactualstatus === ''
    ) {
        echo json_encode([
            "status"  => "error",
            "message" => "Missing required batch information."
        ]);
        exit;
    }

    try {
        $krfconn->begin_transaction();

        /* Revalidate that the scanned barcode belongs to this eligible batch. */
        $checkSql = "
            SELECT 1
            FROM krf_polyhouse_sowingplant_details
            WHERE barcode = ?
              AND projectid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND sowinglotno_internal = ?
              AND sowingbatchno = ?
              AND status = ? 
              AND workingstatus = 'WithoutGermination' 
              AND LOWER(TRIM(germinationwithtray)) = 'yes'
              AND (germination_qrcode IS NULL OR TRIM(germination_qrcode) = '')
              AND (polyhouseid IS NULL OR polyhouseid = '' OR polyhouseid = 0)

            LIMIT 1
            FOR UPDATE
        ";

        $checkStmt = $krfconn->prepare($checkSql);
        if (!$checkStmt) {
            throw new Exception("Batch validation prepare failed: " . $krfconn->error);
        }

        $checkStmt->bind_param(
            "sisisss",
            $scannedBarcode,
            $projectid,
            $stockorderNo,
            $productid,
            $sowinglotnoInternal,
            $sowingbatchno,
            $tableactualstatus
        );
        $checkStmt->execute();
        $isValidBatch = (bool)$checkStmt->get_result()->fetch_row();
        $checkStmt->close();

        if (!$isValidBatch) {
            throw new Exception("The scanned tray batch is no longer eligible for allocation.");
        }

        /* Update the complete lot/order/product/batch in one statement. */
        $updateSql = "
            UPDATE krf_polyhouse_sowingplant_details
            SET
                polyhouseid = ?,
                status = ?,
                updated_by = ?,
                updated_at = NOW()
            WHERE projectid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND sowinglotno_internal = ?
              AND sowingbatchno = ?
              AND status = ?
              AND workingstatus = 'WithoutGermination'
              AND LOWER(TRIM(germinationwithtray)) = 'yes'
              AND (germination_qrcode IS NULL OR TRIM(germination_qrcode) = '')
              AND (polyhouseid IS NULL OR polyhouseid = '' OR polyhouseid = 0)
        ";

        $updateStmt = $krfconn->prepare($updateSql);
        if (!$updateStmt) {
            throw new Exception("Batch update prepare failed: " . $krfconn->error);
        }

        $updateStmt->bind_param(
            "issisisss",
            $polyhouseid,
            $changestatus,
            $loginid,
            $projectid,
            $stockorderNo,
            $productid,
            $sowinglotnoInternal,
            $sowingbatchno,
            $tableactualstatus
        );

        if (!$updateStmt->execute()) {
            throw new Exception("Batch update failed: " . $updateStmt->error);
        }

        $updatedTrays = $updateStmt->affected_rows;
        $updateStmt->close();

        if ($updatedTrays <= 0) {
            throw new Exception("No eligible trays were updated.");
        }

        $krfconn->commit();

        echo json_encode([
            "status"         => "success",
            "message"        => $updatedTrays . " tray(s) allocated successfully.",
            "updated_trays"  => $updatedTrays
        ]);

    } catch (Throwable $e) {
        $krfconn->rollback();

        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);
    }

    exit;
 }
 //else if ($action == "barcodedirectSowing_polyhouse") {

//     $companyid    = $_POST['companyid'] ?? '';
//     $subcompanyid = $_POST['subcompanyid'] ?? '';
//     $projectid    = (int)($_POST['projectid'] ?? 0);
//     $barcode      = trim($_POST['qrcode'] ?? '');
//     $status       = trim($_POST['status'] ?? '');
//     /* -------------------------------------------------------
//        BASIC VALIDATION
//     ------------------------------------------------------- */

//     if ($barcode === '') {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'QR Code is empty.'
//         ]);
//         exit;
//     }

//     if ($status === '') {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Status is empty.'
//         ]);
//         exit;
//     }

//     if ($projectid <= 0) {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Invalid project.'
//         ]);
//         exit;
//     }

//     /* -------------------------------------------------------
//        STEP 1 : FIND SCANNED TRAY
//     ------------------------------------------------------- */

//     $sowingIntQuery = "
//         SELECT
//             s.sowinglotno_internal,
//             s.stockorder_no,
//             s.productid
//         FROM krf_polyhouse_sowingplant_details AS s

//         INNER JOIN product_master AS p
//             ON p.productID = s.productid
//            AND p.projectid = s.projectid

//         WHERE s.barcode = ?
//           AND s.projectid = ?
//           AND s.status = ?
//           AND p.IsGrafted = 'NonGrafted'
//           AND (
//                 s.germination_qrcode IS NULL
//                 OR s.germination_qrcode = ''
//               )
//           AND s.workingstatus = 'WithoutGermination'
//           and s.status ='Tray Received At Germination'
//           and s.polyhouseid is not null and s.polyhouseid <>''

//         LIMIT 1
//     ";

//     $sowingIntStmt = $krfconn->prepare($sowingIntQuery);

//     if (!$sowingIntStmt) {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Query prepare failed: ' . $krfconn->error
//         ]);
//         exit;
//     }

//     $sowingIntStmt->bind_param(
//         "sis",
//         $barcode,
//         $projectid,
//         $status
//     );

//     $sowingIntStmt->execute();

//     $sowingIntResult = $sowingIntStmt->get_result();

//     if ($sowingIntResult->num_rows === 0) {

//         $sowingIntStmt->close();

//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Tray does not exist or is not eligible.'
//         ]);
//         exit;
//     }

//     $sowingRow = $sowingIntResult->fetch_assoc();

//     $sowingIntStmt->close();

 

//     $sowinglotno_internal = $sowingRow['sowinglotno_internal'];
//     $orderno              = $sowingRow['stockorder_no'];
//     $productid            = (int)$sowingRow['productid'];


//     /* -------------------------------------------------------
//        STEP 2 : GET ALL TRAYS OF SAME LOT / ORDER / PRODUCT
//     ------------------------------------------------------- */

//     $lotQuery = "
//         SELECT
//             s.*
//         FROM krf_polyhouse_sowingplant_details AS s

//         INNER JOIN product_master AS p
//             ON p.productID = s.productid
//            AND p.projectid = s.projectid

//         WHERE s.sowinglotno_internal = ?
//           AND s.projectid = ?
//           AND s.stockorder_no = ?
//           AND s.productid = ?
//           AND p.IsGrafted = 'NonGrafted'
//           and s.polyhouseid is not null and s.polyhouseid <>''
//           AND s.workingstatus = 'WithoutGermination'
//           and s.status ='Tray Received At Germination'
//     ";

//     $lotStmt = $krfconn->prepare($lotQuery);

//     if (!$lotStmt) {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Lot query prepare failed: ' . $krfconn->error
//         ]);
//         exit;
//     }

//     $lotStmt->bind_param(
//         "sisi",
//         $sowinglotno_internal,
//         $projectid,
//         $orderno,
//         $productid
//     );

//     $lotStmt->execute();

//     $lotResult = $lotStmt->get_result();

//     $trayDetails = [];

//     while ($row = $lotResult->fetch_assoc()) {

//         if (
//             $row['germination_qrcode'] !== null &&
//             trim($row['germination_qrcode']) !== ''
//         ) {

//             $lotStmt->close();

//             echo json_encode([
//                 'status'  => 'error',
//                 'message' => 'One or more trays are already allocated.'
//             ]);
//             exit;
//         }

//         $trayDetails[] = $row;
//     }

//     $lotStmt->close();


//     /* -------------------------------------------------------
//        FINAL RESPONSE
//     ------------------------------------------------------- */

//     if (empty($trayDetails)) {

//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'No eligible trays found for this lot.'
//         ]);
//         exit;
//     }

//     echo json_encode([
//         'status' => 'success',
//         'data'   => $trayDetails
//     ]);

//     exit;
// }else if ($action === "Updatestatusstage1") {

//     $projectid         = (int)($_POST['projectid'] ?? 0);
//     $loginid           = trim($_POST['loginid'] ?? '');
//     $pstockorderno     = trim($_POST['pstockorderno'] ?? '');
//     $productid         = trim($_POST['productid'] ?? '');
//     $changestatus      = trim($_POST['changestatus'] ?? '');
//     $tableactualstatus = trim($_POST['tableactualstatus'] ?? '');

//     /* -------------------------------------------------------
//        Validation
//     ------------------------------------------------------- */
//     if (
//         $projectid <= 0 ||
//         $loginid === '' ||
//         $pstockorderno === '' ||
//         $productid === '' ||
//         $changestatus === '' ||
//         $tableactualstatus === ''
//     ) {
//         echo json_encode([
//             "status"  => "error",
//             "message" => "Missing required parameters."
//         ]);
//         exit;
//     }

//     try {

//         $krfconn->begin_transaction(); 
//         $sql = " SELECT 1
//             FROM krf_polyhouse_sowingplant_details
//             WHERE projectid = ?
//               AND stockorder_no = ?
//               AND productid = ?
//               AND workingstatus = 'GerminationInProcess'
//             LIMIT 1
//             FOR UPDATE
//         ";
//         $stmt = $krfconn->prepare($sql);
//         if (!$stmt) {
//             throw new Exception(
//                 "Germination check prepare failed: " . $krfconn->error
//             );
//         }
//         $stmt->bind_param(
//             "iss",
//             $projectid,
//             $pstockorderno,
//             $productid
//         );
//         if (!$stmt->execute()) {
//             throw new Exception(
//                 "Germination check failed: " . $stmt->error
//             );
//         }
//         $result = $stmt->get_result();
//         if ($result->num_rows > 0) {
//             $stmt->close();
//             $krfconn->rollback();
//             echo json_encode([
//                 "status"  => "error",
//                 "message" => "Order already contains Germination Box."
//             ]);
//             exit;
//         }

//         $stmt->close();
//         /* ---------2. Update sowing plant details*/
//         $sql = "
//             UPDATE krf_polyhouse_sowingplant_details
//             SET
//                 status = ?,
//                 updated_by = ?,
//                 updated_at = NOW(),
//                 workingstatus = 'WithoutGermination',
//                 germinationwithtray ='yes'
//             WHERE projectid = ?
//               AND stockorder_no = ?
//               AND productid = ?
//               AND status = ?
//               AND workingstatus = 'SowingInProcess'
//         ";

//         $stmt = $krfconn->prepare($sql);

//         if (!$stmt) {
//             throw new Exception(
//                 "Plant detail prepare failed: " . $krfconn->error
//             );
//         }

//         $stmt->bind_param(
//             "ssisss",
//             $changestatus,
//             $loginid,
//             $projectid,
//             $pstockorderno,
//             $productid,
//             $tableactualstatus
//         );

//         if (!$stmt->execute()) {
//             throw new Exception(
//                 "Plant detail update failed: " . $stmt->error
//             );
//         }

//         $affectedPlantRows = $stmt->affected_rows;

//         $stmt->close();
//         if ($affectedPlantRows === 0) {
//             throw new Exception(
//                 "No matching sowing record found or status has already changed."
//             );
//         }    

//         /* -------------------------- Commit ---- */
//         $krfconn->commit();

//         echo json_encode([
//             "status"  => "success",
//             "message" => "Data saved successfully."
//         ]);

//     } catch (Throwable $e) {
//         $krfconn->rollback();
//         echo json_encode([
//             "status"  => "error",
//             "message" => $e->getMessage()
//         ]);
//     }

//     exit;
// } else if ($action == "UpdateTrayAllocationToPolyhouse") {

 

//     $companyid     = $_POST['companyid'] ?? '';
//     $subcompanyid  = $_POST['subcompanyid'] ?? '';
//     $projectid     = (int)($_POST['projectid'] ?? 0);
//     $loginid       = $_POST['loginid'] ?? '';
//     $polyhouseid   = (int)($_POST['polyhouseid'] ?? 0);
//     $bedrackname   = trim($_POST['bedrackname'] ?? '');
//     $trayposition  = trim($_POST['placeofrow'] ?? '');
//     $gfailurestatus = trim($_POST['gfailurestatus'] ?? 'Healthy');

//     $boxidsRaw = $_POST['boxids'] ?? null;

//     /* =====================================================
//        1. VALIDATION
//     ===================================================== */

//     if ($projectid <= 0) {
//         echo json_encode([
//             "status"  => "error",
//             "message" => "Invalid project."
//         ]);
//         exit;
//     }

//     if ($polyhouseid <= 0) {
//         echo json_encode([
//             "status"  => "error",
//             "message" => "Invalid polyhouse."
//         ]);
//         exit;
//     }

//     if (empty($boxidsRaw)) {
//         echo json_encode([
//             "status"  => "error",
//             "message" => "No trays provided for allocation."
//         ]);
//         exit;
//     }


//     /* =====================================================
//        2. DECODE BARCODE DATA
//     ===================================================== */

//     $boxids = is_string($boxidsRaw) ? json_decode($boxidsRaw, true): $boxidsRaw;
//     if (!is_array($boxids) || empty($boxids)) {
//         echo json_encode([
//             "status"  => "error",
//             "message" => "Invalid tray data."
//         ]);
//         exit;
//     }

//     $trayRows = $boxids;
//     if (
//         isset($boxids[0]) &&
//         is_array($boxids[0]) &&
//         isset($boxids[0][0]) &&
//         is_array($boxids[0][0])
//     ) {
//         $trayRows = $boxids[0];
//     }


//     /* =====================================================
//        3. DISTINCT VALID BARCODES
//     ===================================================== */

//     $distinctBarcodes = array_values(
//         array_unique(
//             array_filter(
//                 array_map(
//                     'trim',
//                     array_column($trayRows, 'barcode')
//                 ),
//                 function ($barcode) {
//                     return $barcode !== '';
//                 }
//             )
//         )
//     );


//     if (empty($distinctBarcodes)) {
//         echo json_encode([
//             "status"  => "error",
//             "message" => "No valid tray barcodes found."
//         ]);
//         exit;
//     }


//     try {

//         $krfconn->begin_transaction();

//         $locStmt = $krfconn->prepare("
//             SELECT locationid
//             FROM krf_polyhouse_master
//             WHERE polyhouseid = ?
//               AND projectid = ?
//             LIMIT 1
//         ");

//         if (!$locStmt) {
//             throw new Exception(
//                 "Location query prepare failed: " . $krfconn->error
//             );
//         }

//         $locStmt->bind_param(
//             "ii",
//             $polyhouseid,
//             $projectid
//         );

//         $locStmt->execute();

//         $phData = $locStmt
//             ->get_result()
//             ->fetch_assoc();

//         $locStmt->close();

//         $locationid = $phData['locationid'] ?? '';

//         if ($locationid === '') {
//             throw new Exception(
//                 "Polyhouse location not found."
//             );
//         }


//         /* =====================================================
//            5. DETERMINE TARGET STATUS
//         ===================================================== */

//         $targetStatus = (
//             $gfailurestatus === 'Healthy'
//         )
//             ? 'Polyhouse'
//             : $gfailurestatus;


//         /* =====================================================
//            6. PREPARE INSERT ONCE
//         ===================================================== */

//         $moveSql = "
//             INSERT INTO krf_polyhouse_plant_details
//             (
//                 psono,
//                 roottype,
//                 sowingplantid,
//                 stockorder_no,
//                 locationid,
//                 labourid,
//                 polyhouseid,
//                 projectid,
//                 lotno,
//                 batchno,
//                 barcode,
//                 productid,
//                 productname,
//                 tray_id,
//                 qtypertray,
//                 tray_position,
//                 bedrackname,
//                 bed_no,
//                 planted_date,
//                 workingstatus,
//                 status,
//                 remarks,
//                 sowing_date,
//                 sowingdate_internal,
//                 sowinglotno_internal,
//                 sowingbatchno,
//                 dispatchdate_internal,
//                 created_by
//             )

//             SELECT
//                 s.psono,
//                 s.roottype,
//                 s.sowingplantid,
//                 s.stockorder_no,
//                 ?,
//                 s.labourid,
//                 ?,
//                 s.projectid,
//                 s.lotno,
//                 s.sowingbatchno,
//                 s.barcode,
//                 s.productid,
//                 s.productname,
//                 s.trayid,
//                 s.traycavity,
//                 ?,
//                 ?,
//                 ?,
//                 NOW(),
//                 'Polyhouse',
//                 ?,
//                 s.remarks,
//                 s.sowing_date,
//                 s.sowingdate_internal,
//                 s.sowinglotno_internal,
//                 s.sowingbatchno,
//                 s.dispatchdate_internal,
//                 ?

//             FROM krf_polyhouse_sowingplant_details AS s

//             WHERE s.barcode = ?
//               AND s.projectid = ?
//               AND s.status = 'Tray Received At Germination'
//               AND s.workingstatus = 'WithoutGermination'
//         ";

//         $moveStmt = $krfconn->prepare($moveSql);

//         if (!$moveStmt) {
//             throw new Exception(
//                 "Move query prepare failed: " . $krfconn->error
//             );
//         }


//         /* =====================================================
//            7. PREPARE SOURCE UPDATE ONCE
//         ===================================================== */

//         $updateSourceSql = "
//             UPDATE krf_polyhouse_sowingplant_details

//             SET
//                 status = ?,
//                 workingstatus = 'Polyhouse',
//                 updated_at = NOW(),
//                 updated_by = ?

//             WHERE barcode = ?
//               AND projectid = ?
//               AND status = 'Tray Received At Germination'
//               AND workingstatus = 'WithoutGermination'
//         ";

//         $updStmt = $krfconn->prepare($updateSourceSql);

//         if (!$updStmt) {
//             throw new Exception(
//                 "Source update prepare failed: " .
//                 $krfconn->error
//             );
//         }


//         /* =====================================================
      


//         /* =====================================================
//            9. PROCESS EACH DISTINCT BARCODE
//         ===================================================== */

//         $totalInserted = 0;

//         foreach ($distinctBarcodes as $barcode) { 
//             $moveStmt->bind_param(
//                 "sissssssi",
//                 $locationid,
//                 $polyhouseid,
//                 $trayposition,
//                 $bedrackname,
//                 $bedrackname,
//                 $targetStatus,
//                 $loginid,
//                 $barcode,
//                 $projectid
//             );

//             if (!$moveStmt->execute()) {
//                 throw new Exception(
//                     "Failed to move tray {$barcode}: " .
//                     $moveStmt->error
//                 );
//             }


//             $inserted = $moveStmt->affected_rows;


//             /* -----------------------------------------------
//                Nothing inserted
//             ----------------------------------------------- */

//             if ($inserted <= 0) {

//                 throw new Exception(
//                     "Tray {$barcode} could not be moved. " .
//                     "It may already be allocated or its status is invalid."
//                 );
//             }


//             $totalInserted += $inserted;


//             /* -----------------------------------------------
//                UPDATE SOURCE TRAY
//             ----------------------------------------------- */

//             $updStmt->bind_param(
//                 "sssi",
//                 $targetStatus,
//                 $loginid,
//                 $barcode,
//                 $projectid
//             );

//             if (!$updStmt->execute()) {

//                 throw new Exception(
//                     "Failed to update source tray {$barcode}: " .
//                     $updStmt->error
//                 );
//             }            
//         }

//         /* =====================================================
//            10. CLOSE PREPARED STATEMENTS
//         ===================================================== */

//         $moveStmt->close();
//         $updStmt->close(); 


//         /* =====================================================
//            11. FINAL VALIDATION
//         ===================================================== */

//         if ($totalInserted <= 0) {
//             throw new Exception(
//                 "No trays were moved to the polyhouse."
//             );
//         }


//         /* =====================================================
//            12. COMMIT
//         ===================================================== */

//         $krfconn->commit();


//         echo json_encode([
//             "status"  => "success",
//             "message" =>
//                 "Allocation completed successfully. " .
//                 "Total {$totalInserted} trays moved.",
//             "data" => [
//                 "allocatedtrays" => $totalInserted,
//                 "barcodes"       => $distinctBarcodes,
//                 "barcodecount"   => count($distinctBarcodes)
//             ]
//         ]);

//         exit;


//     } catch (Throwable $e) {

//         try {
//             $krfconn->rollback();
//         } catch (Throwable $ignored) {
//         }

//         echo json_encode([
//             "status"  => "error",
//             "message" => $e->getMessage()
//         ]);

//         exit;
//     }
// } 
?>