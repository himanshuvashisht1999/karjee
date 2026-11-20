<?php
/** @var mysqli $krfconn */
error_reporting(E_ALL);
require('../../connection.php');
 
date_default_timezone_set('Asia/Kolkata');
$action = $_GET['action'];
if ($action === "getGraftingPrintAvailability") {

    header('Content-Type: application/json; charset=utf-8');

    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $graftingdate  = trim($_POST['graftingdate'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $productid     = trim($_POST['productid'] ?? '');


    if (
        $projectid <= 0 ||
        $locationid === '' ||
        $graftingdate === '' ||
        $stockorder_no === '' ||
        $productid === ''
    ) {

        echo json_encode([
            'status' => 'error',
            'message' => 'Required print information is incomplete.'
        ]);

        exit;
    }


    $sql = "
        SELECT
            labourid,
            labourname,
            COUNT(*) AS barcode_count

        FROM krf_polyhouse_plant_details

        WHERE projectid = ?
          AND locationid = ?
          AND graftingdate = ?
          AND stockorder_no = ?
          AND productid = ?
          AND roottype = 'Grafted'
          AND status = 'Grafted'
          AND barcode IS NOT NULL
          AND barcode <> ''
          AND labourid IS NOT NULL
          AND labourid <> ''

        GROUP BY
            labourid,
            labourname
    ";


    $stmt = $krfconn->prepare($sql);

    if (!$stmt) {

        echo json_encode([
            'status' => 'error',
            'message' => 'Print availability query failed: ' . $krfconn->error
        ]);

        exit;
    }


    $stmt->bind_param(
        "issss",
        $projectid,
        $locationid,
        $graftingdate,
        $stockorder_no,
        $productid
    );


    $stmt->execute();

    $result = $stmt->get_result();


    $data = [];

    $totalBarcodes = 0;


    while ($row = $result->fetch_assoc()) {

        $barcodeCount =
            (int)($row['barcode_count'] ?? 0);


        $totalBarcodes += $barcodeCount;


        $data[] = [

            'labourid' =>
                (string)($row['labourid'] ?? ''),

            'labourname' =>
                (string)($row['labourname'] ?? ''),

            'barcode_count' =>
                $barcodeCount
        ];
    }


    $stmt->close();


    echo json_encode([

        'status' => 'success',

        'data' => $data,

        'total_barcodes' => $totalBarcodes

    ]);

    exit;
}

// else if ($action === "saveDailyGraftingAllocation") {

//     header('Content-Type: application/json; charset=utf-8');

//     $projectid     = (int)($_POST['projectid'] ?? 0);
//     $locationid    = trim($_POST['locationid'] ?? '');
//     $graftingdate  = trim($_POST['graftingdate'] ?? '');
//     $productid     = trim($_POST['productid'] ?? '');
//     $productname   = trim($_POST['productname'] ?? '');
//     $stockorder_no = trim($_POST['stockorder_no'] ?? '');
//     $psono         = trim($_POST['psono'] ?? '');
//     $graftedTrayId = (int)($_POST['grafted_tray_id'] ?? 0);

//     /*
//      * IMPORTANT:
//      * created_by should preferably come from your authenticated PHP
//      * session/login variable rather than trusting browser POST.
//      */
//     $createdBy = trim($_POST['loginid'] ?? '');

//     $allocationData = json_decode(
//         $_POST['allocationdata'] ?? '[]',
//         true
//     );


//     // ==========================================================
//     // BASIC VALIDATION
//     // ==========================================================

//     if ($projectid <= 0) {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Invalid Project.'
//         ]);
//         exit;
//     }

//     if ($locationid === '') {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Location is required.'
//         ]);
//         exit;
//     }

//     if ($graftingdate === '') {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Grafting Date is required.'
//         ]);
//         exit;
//     }

//     if ($productid === '' || $stockorder_no === '') {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Product / Stock Order is required.'
//         ]);
//         exit;
//     }

//     if ($graftedTrayId <= 0) {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'Please select Grafted Tray Type.'
//         ]);
//         exit;
//     }

//     if (!is_array($allocationData) || count($allocationData) === 0) {
//         echo json_encode([
//             'status' => 'error',
//             'message' => 'No labour allocation found.'
//         ]);
//         exit;
//     }


//     try {

//         $krfconn->begin_transaction();


//         // ==========================================================
//         // 1. GET SELECTED GRAFTED TRAY MASTER
//         // ==========================================================

//         /*
//          * IMPORTANT:
//          * Use the same tray-master table/columns already used by your
//          * existing savegrafting action.
//          *
//          * If your current savegrafting already has this query,
//          * keep that exact query instead of creating another master rule.
//          */

//         $traySql = "
//             SELECT
//                 tray_id,
//                 tray_code,
//                 cavity_count
//             FROM krf_tray_master
//             WHERE tray_id = ?
//             LIMIT 1
//         ";

//         $trayStmt = $krfconn->prepare($traySql);

//         if (!$trayStmt) {
//             throw new Exception(
//                 'Tray master prepare failed: ' . $krfconn->error
//             );
//         }

//         $trayStmt->bind_param("i", $graftedTrayId);
//         $trayStmt->execute();

//         $trayResult = $trayStmt->get_result();
//         $trayRow = $trayResult->fetch_assoc();

//         $trayStmt->close();

//         if (!$trayRow) {
//             throw new Exception(
//                 'Selected grafted tray type was not found.'
//             );
//         }


//         $graftedTrayCode =
//             trim($trayRow['tray_code'] ?? '');

//         $cavityCount =
//             (int)($trayRow['cavity_count'] ?? 0);


//         if ($cavityCount <= 0) {
//             throw new Exception(
//                 'Invalid cavity count for selected grafted tray.'
//             );
//         }


//         // ==========================================================
//         // PROCESS EACH LABOUR
//         // ==========================================================

//         $savedLabours       = 0;
//         $generatedTrayCount = 0;

//         foreach ($allocationData as $allocation) {

//             $labourid =
//                 trim($allocation['labourid'] ?? '');

//             $labourname =
//                 trim($allocation['labourname'] ?? '');

//             $allocationtime =
//                 trim($allocation['allocationtime'] ?? '');

//             $todaytarget =
//                 (int)($allocation['todaytarget'] ?? 0);

//             $targetTrayQty =
//                 (int)($allocation['noofgraftedtry'] ?? 0);


//             /*
//              * Your rule:
//              * Save only when Target Tray Qty > 0
//              */
//             if ($labourid === '' || $targetTrayQty <= 0) {
//                 continue;
//             }


//             // ======================================================
//             // 2. LOCK N ROOT TRAYS
//             // ======================================================

//             $rootSql = "
//                 SELECT
//                     polyhouseplantid,
//                     qtypertray
//                 FROM krf_polyhouse_plant_details
//                 WHERE projectid = ?
//                   AND movetolocation = ?
//                   AND stockorder_no = ?
//                   AND productid = ?
//                   AND roottype = 'Root'
//                   AND status = 'Grafting'
//                 ORDER BY polyhouseplantid ASC
//                 LIMIT ?
//                 FOR UPDATE
//             ";

//             $rootStmt = $krfconn->prepare($rootSql);

//             if (!$rootStmt) {
//                 throw new Exception(
//                     'Root tray prepare failed: ' .
//                     $krfconn->error
//                 );
//             }

//             $rootStmt->bind_param(
//                 "isssi",
//                 $projectid,
//                 $locationid,
//                 $stockorder_no,
//                 $productid,
//                 $targetTrayQty
//             );

//             $rootStmt->execute();

//             $rootResult = $rootStmt->get_result();

//             $rootRows = [];

//             while ($row = $rootResult->fetch_assoc()) {
//                 $rootRows[] = $row;
//             }

//             $rootStmt->close();


//             if (count($rootRows) < $targetTrayQty) {

//                 throw new Exception(
//                     "Only " .
//                     count($rootRows) .
//                     " Root tray(s) are available for " .
//                     $labourname .
//                     ". Required: " .
//                     $targetTrayQty .
//                     "."
//                 );
//             }


//             // ======================================================
//             // 3. LOCK N SCION TRAYS
//             // ======================================================

//             $scionSql = "
//                 SELECT
//                     polyhouseplantid,
//                     qtypertray
//                 FROM krf_polyhouse_plant_details
//                 WHERE projectid = ?
//                   AND movetolocation = ?
//                   AND stockorder_no = ?
//                   AND productid = ?
//                   AND roottype = 'Scion'
//                   AND status = 'Grafting'
//                 ORDER BY polyhouseplantid ASC
//                 LIMIT ?
//                 FOR UPDATE
//             ";

//             $scionStmt = $krfconn->prepare($scionSql);

//             if (!$scionStmt) {
//                 throw new Exception(
//                     'Scion tray prepare failed: ' .
//                     $krfconn->error
//                 );
//             }

//             $scionStmt->bind_param(
//                 "isssi",
//                 $projectid,
//                 $locationid,
//                 $stockorder_no,
//                 $productid,
//                 $targetTrayQty
//             );

//             $scionStmt->execute();

//             $scionResult = $scionStmt->get_result();

//             $scionRows = [];

//             while ($row = $scionResult->fetch_assoc()) {
//                 $scionRows[] = $row;
//             }

//             $scionStmt->close();


//             if (count($scionRows) < $targetTrayQty) {

//                 throw new Exception(
//                     "Only " .
//                     count($scionRows) .
//                     " Scion tray(s) are available for " .
//                     $labourname .
//                     ". Required: " .
//                     $targetTrayQty .
//                     "."
//                 );
//             }


//             // ======================================================
//             // 4. MARK ROOT + SCION DESTROY AFTER GRAFTED
//             // ======================================================

//             $destroySql = "
//                 UPDATE krf_polyhouse_plant_details
//                 SET
//                     status = 'Destroy After grafted',
//                     workingstatus = 'Destroy After grafted'
//                 WHERE polyhouseplantid = ?
//                   AND status = 'Grafting'
//             ";

//             $destroyStmt =
//                 $krfconn->prepare($destroySql);

//             if (!$destroyStmt) {
//                 throw new Exception(
//                     'Destroy update prepare failed: ' .
//                     $krfconn->error
//                 );
//             }


//             foreach ($rootRows as $rootRow) {

//                 $sourceId =
//                     (int)$rootRow['polyhouseplantid'];

//                 $destroyStmt->bind_param(
//                     "i",
//                     $sourceId
//                 );

//                 $destroyStmt->execute();

//                 if ($destroyStmt->affected_rows !== 1) {
//                     throw new Exception(
//                         "Unable to consume Root tray ID " .
//                         $sourceId . "."
//                     );
//                 }
//             }


//             foreach ($scionRows as $scionRow) {

//                 $sourceId =
//                     (int)$scionRow['polyhouseplantid'];

//                 $destroyStmt->bind_param(
//                     "i",
//                     $sourceId
//                 );

//                 $destroyStmt->execute();

//                 if ($destroyStmt->affected_rows !== 1) {
//                     throw new Exception(
//                         "Unable to consume Scion tray ID " .
//                         $sourceId . "."
//                     );
//                 }
//             }

//             $destroyStmt->close();


//             // ======================================================
//             // 5. GENERATE N GRAFTED ROWS
//             // ======================================================

//             for ($i = 0; $i < $targetTrayQty; $i++) {


//                 // ==================================================
//                 // UNIQUE SIX DIGIT BARCODE
//                 // ==================================================

//                 do {

//                    $alphabet = chr(random_int(65, 90));   // A-Z
//                     $number   = str_pad(
//                         (string)random_int(0, 9999),
//                         4,
//                         '0',
//                         STR_PAD_LEFT
//                     );

//                     $barcode = 'G' . $alphabet . $number;

//                     $barcodeSql = "
//                         SELECT 1
//                         FROM krf_polyhouse_plant_details
//                         WHERE barcode = ?
//                         LIMIT 1
//                     ";

//                     $barcodeStmt =
//                         $krfconn->prepare($barcodeSql);

//                     if (!$barcodeStmt) {
//                         throw new Exception(
//                             'Barcode check prepare failed: ' .
//                             $krfconn->error
//                         );
//                     }

//                     $barcodeStmt->bind_param(
//                         "s",
//                         $barcode
//                     );

//                     $barcodeStmt->execute();

//                     $barcodeExists =
//                         $barcodeStmt
//                             ->get_result()
//                             ->num_rows > 0;

//                     $barcodeStmt->close();

//                 } while ($barcodeExists);


//                 // ==================================================
//                 // INSERT GRAFTED PHYSICAL TRAY
//                 // ==================================================

//                 $insertPlantSql = "
//                     INSERT INTO krf_polyhouse_plant_details
//                     (
//                         planted_date,
//                         psono,
//                         stockorder_no,
//                         roottype,
//                         locationid,
//                         graftingdate,
//                         labourid,
//                         cultivation_type,
//                         projectid,
//                         barcode,
//                         productid,
//                         productname,
//                         tray_id,
//                         tray_code,
//                         qtypertray,
//                         health_status,
//                         status,
//                         workingstatus,
//                         labourname,
//                         targettrayassigned
//                     )
//                     VALUES
//                     (
//                         CURDATE(),
//                         ?,
//                         ?,
//                         'Grafted',
//                         ?,
//                         ?,
//                         ?,
//                         'Tray',
//                         ?,
//                         ?,
//                         ?,
//                         ?,
//                         ?,
//                         ?,
//                         ?,
//                         'Healthy',
//                         'Grafted',
//                         'Grafted',
//                         ?,
//                         ?
//                     )
//                 ";

//                 $insertPlantStmt =
//                     $krfconn->prepare($insertPlantSql);

//                 if (!$insertPlantStmt) {
//                     throw new Exception(
//                         'Grafted tray insert prepare failed: ' .
//                         $krfconn->error
//                     );
//                 }


//                 /*
//                  * For now each generated grafted row gets the
//                  * selected tray cavity.
//                  *
//                  * If the final row is partial, we will adjust this
//                  * from the actual available Root/Scion plant count.
//                  */
//                 $graftedQty =
//                     $cavityCount;


//                 $insertPlantStmt->bind_param(
//                     "ssssssssssssss",
//                     $psono,
//                     $stockorder_no,
//                     $locationid,
//                     $graftingdate,
//                     $labourid,
//                     $projectid,
//                     $barcode,
//                     $productid,
//                     $productname,
//                     $graftedTrayId,
//                     $graftedTrayCode,
//                     $graftedQty,
//                     $labourname,
//                     $targetTrayQty
//                 );


//                 $insertPlantStmt->execute();

//                 if ($insertPlantStmt->affected_rows !== 1) {

//                     throw new Exception(
//                         'Unable to generate grafted tray.'
//                     );
//                 }

//                 $insertPlantStmt->close();

//                 $generatedTrayCount++;
//             }


//             // ======================================================
//             // 6. DAILY HISTORY
//             // ======================================================

//             $historyCheckSql = "
//                 SELECT id
//                 FROM krf_graftingdetails_daily
//                 WHERE projectid = ?
//                   AND locationid = ?
//                   AND graftingdate = ?
//                   AND stockorder_no = ?
//                   AND productid = ?
//                   AND labourid = ?
//                 LIMIT 1
//                 FOR UPDATE
//             ";

//             $historyCheckStmt =
//                 $krfconn->prepare($historyCheckSql);

//             $historyCheckStmt->bind_param(
//                 "isssss",
//                 $projectid,
//                 $locationid,
//                 $graftingdate,
//                 $stockorder_no,
//                 $productid,
//                 $labourid
//             );

//             $historyCheckStmt->execute();

//             $historyRow =
//                 $historyCheckStmt
//                     ->get_result()
//                     ->fetch_assoc();

//             $historyCheckStmt->close();


//             $rootTrayAssigned =
//                 count($rootRows);

//             $scionTrayAssigned =
//                 count($scionRows);


//             if ($historyRow) {

//                 $historyId =
//                     (int)$historyRow['id'];


//                 $historyUpdateSql = "
//                     UPDATE krf_graftingdetails_daily
//                     SET
//                         productname = ?,
//                         labourname = ?,
//                         allocationtime = ?,
//                         todaytarget = ?,                     

//                         noroottrayassigned = COALESCE(noroottrayassigned, 0) + ?,
//                         nosciontrayassigned = COALESCE(nosciontrayassigned, 0) + ?,
//                         noofgraftedtry = COALESCE(noofgraftedtry, 0) + ?,
//                         grafted_traycount = COALESCE(grafted_traycount, 0) + ?,
//                         psono = ?
//                         WHERE id = ?
//                 ";


//                 $historyStmt =
//                     $krfconn->prepare(
//                         $historyUpdateSql
//                     );

//                 $historyStmt->bind_param(
//                     "sssiiiisi",
//                     $productname,
//                     $labourname,
//                     $allocationtime,
//                     $todaytarget,
//                     $rootTrayAssigned,
//                     $scionTrayAssigned,
//                     $targetTrayQty,
//                     $psono,
//                     $historyId
//                 );

//             } else {

//                 $historyInsertSql = "
//                     INSERT INTO krf_graftingdetails_daily
//                     (
//                         projectid,
//                         productid,
//                         productname,
//                         labourid,
//                         labourname,
//                         graftingdate,
//                         allocationtime,
//                         todaytarget,
//                         noroottrayassigned,
//                         nosciontrayassigned,
//                         noofgraftedtry,
//                         created_by,
//                         locationid,
//                         psono,
//                         stockorder_no,
//                         grafted_traycount
//                     )
//                     VALUES
//                     (
//                         ?, ?, ?, ?, ?, ?, ?,
//                         ?, ?, ?, ?, ?, ?, ?, ?,?
//                     )
//                 ";

//                 $historyStmt =
//                     $krfconn->prepare(
//                         $historyInsertSql
//                     );

//                 $historyStmt->bind_param(
//                     "issssssiiiissssi",
//                     $projectid,
//                     $productid,
//                     $productname,
//                     $labourid,
//                     $labourname,
//                     $graftingdate,
//                     $allocationtime,
//                     $todaytarget,
//                     $rootTrayAssigned,
//                     $scionTrayAssigned,
//                     $targetTrayQty,
//                     $createdBy,
//                     $locationid,
//                     $psono,
//                     $stockorder_no,
//                     $targetTrayQty
//                 );
//             }


//             $historyStmt->execute();

//             if (!$historyRow &&
//                 $historyStmt->affected_rows !== 1) {

//                 throw new Exception(
//                     'Unable to save daily grafting history.'
//                 );
//             }

//             $historyStmt->close();

//             $savedLabours++;
//         }


//         if ($savedLabours <= 0) {

//             throw new Exception(
//                 'No labour with Grafted Tray Target greater than zero was found.'
//             );
//         }


//         // ==========================================================
//         // EVERYTHING SUCCESSFUL
//         // ==========================================================

//         $krfconn->commit();


//         echo json_encode([
//             'status' => 'success',

//             'message' =>
//                 $generatedTrayCount .
//                 ' grafted tray(s) generated successfully.',

//             'data' => [
//                 'labours_saved' =>
//                     $savedLabours,

//                 'grafted_trays_generated' =>
//                     $generatedTrayCount
//             ]
//         ]);

//         exit;


//     } catch (Throwable $e) {

//         try {
//             $krfconn->rollback();
//         } catch (Throwable $ignore) {
//         }


//         echo json_encode([
//             'status' => 'error',
//             'message' => $e->getMessage()
//         ]);

//         exit;
//     }
// }
else if ($action === "saveDailyGraftingAllocation") {

    header('Content-Type: application/json; charset=utf-8');

    $projectid     = (int)($_POST['projectid'] ?? 0);
    $locationid    = trim($_POST['locationid'] ?? '');
    $graftingdate  = trim($_POST['graftingdate'] ?? '');
    $productid     = trim($_POST['productid'] ?? '');
    $productname   = trim($_POST['productname'] ?? '');
    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $psono         = trim($_POST['psono'] ?? '');
    $graftedTrayId = (int)($_POST['grafted_tray_id'] ?? 0);
    $createdBy     = trim($_POST['loginid'] ?? '');

    $allocationData = json_decode(
        $_POST['allocationdata'] ?? '[]',
        true
    );


    // ==========================================================
    // BASIC VALIDATION
    // ==========================================================

    if ($projectid <= 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid Project.'
        ]);
        exit;
    }

    if ($locationid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Location is required.'
        ]);
        exit;
    }

    if ($graftingdate === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Grafting Date is required.'
        ]);
        exit;
    }

    if ($productid === '' || $stockorder_no === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Product / Stock Order is required.'
        ]);
        exit;
    }

    if ($graftedTrayId <= 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Please select Grafted Tray Type.'
        ]);
        exit;
    }

    if (!is_array($allocationData) || count($allocationData) === 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'No labour allocation found.'
        ]);
        exit;
    }


    try {

        // ======================================================
        // START TRANSACTION
        // ======================================================

        $krfconn->begin_transaction();


        // ======================================================
        // 1. GET SELECTED GRAFTED TRAY MASTER
        // ======================================================

        $traySql = "
            SELECT
                tray_id,
                tray_code,
                cavity_count
            FROM krf_tray_master
            WHERE tray_id = ?
            LIMIT 1
        ";

        $trayStmt = $krfconn->prepare($traySql);

        if (!$trayStmt) {
            throw new Exception(
                'Tray master prepare failed: ' .
                $krfconn->error
            );
        }

        $trayStmt->bind_param(
            "i",
            $graftedTrayId
        );

        $trayStmt->execute();

        $trayResult = $trayStmt->get_result();

        $trayRow = $trayResult->fetch_assoc();

        $trayStmt->close();


        if (!$trayRow) {
            throw new Exception(
                'Selected grafted tray type was not found.'
            );
        }


        $graftedTrayCode =
            trim($trayRow['tray_code'] ?? '');

        $cavityCount =
            (int)($trayRow['cavity_count'] ?? 0);


        if ($cavityCount <= 0) {
            throw new Exception(
                'Invalid cavity count for selected grafted tray.'
            );
        }


        // ======================================================
        // 2. COUNTERS
        // ======================================================

        $savedLabours       = 0;
        $generatedTrayCount = 0;


        // ======================================================
        // 3. PROCESS EACH LABOUR
        // ======================================================

        foreach ($allocationData as $allocation) {

            $labourid =
                trim($allocation['labourid'] ?? '');

            $labourname =
                trim($allocation['labourname'] ?? '');

            $allocationtime =
                trim($allocation['allocationtime'] ?? '');

            $todaytarget =
                (int)($allocation['todaytarget'] ?? 0);

            $targetTrayQty =
                (int)($allocation['noofgraftedtry'] ?? 0);


            // --------------------------------------------------
            // Nothing to save for this labour
            // --------------------------------------------------

            if (
                $labourid === '' ||
                $targetTrayQty <= 0
            ) {
                continue;
            }


            // ==================================================
            // 4. LOCK REQUIRED ROOT TRAYS
            // ==================================================

            $rootSql = "
                SELECT
                    polyhouseplantid,
                    qtypertray
                FROM krf_polyhouse_plant_details
                WHERE projectid = ?
                  AND movetolocation = ?
                  AND stockorder_no = ?
                  AND productid = ?
                  AND roottype = 'Root'
                  AND status = 'Grafting'
                ORDER BY polyhouseplantid ASC
                LIMIT ?
                FOR UPDATE
            ";

            $rootStmt =
                $krfconn->prepare($rootSql);

            if (!$rootStmt) {
                throw new Exception(
                    'Root tray prepare failed: ' .
                    $krfconn->error
                );
            }


            $rootStmt->bind_param(
                "isssi",
                $projectid,
                $locationid,
                $stockorder_no,
                $productid,
                $targetTrayQty
            );

            $rootStmt->execute();

            $rootResult =
                $rootStmt->get_result();

            $rootRows = [];

            while (
                $row = $rootResult->fetch_assoc()
            ) {
                $rootRows[] = $row;
            }

            $rootStmt->close();


            if (
                count($rootRows) <
                $targetTrayQty
            ) {

                throw new Exception(
                    "Only " .
                    count($rootRows) .
                    " Root tray(s) are available for " .
                    $labourname .
                    ". Required: " .
                    $targetTrayQty .
                    "."
                );
            }


            // ==================================================
            // 5. LOCK REQUIRED SCION TRAYS
            // ==================================================

            $scionSql = "
                SELECT
                    polyhouseplantid,
                    qtypertray
                FROM krf_polyhouse_plant_details
                WHERE projectid = ?
                  AND movetolocation = ?
                  AND stockorder_no = ?
                  AND productid = ?
                  AND roottype = 'Scion'
                  AND status = 'Grafting'
                ORDER BY polyhouseplantid ASC
                LIMIT ?
                FOR UPDATE
            ";

            $scionStmt =
                $krfconn->prepare($scionSql);

            if (!$scionStmt) {
                throw new Exception(
                    'Scion tray prepare failed: ' .
                    $krfconn->error
                );
            }


            $scionStmt->bind_param(
                "isssi",
                $projectid,
                $locationid,
                $stockorder_no,
                $productid,
                $targetTrayQty
            );

            $scionStmt->execute();

            $scionResult =
                $scionStmt->get_result();

            $scionRows = [];

            while (
                $row = $scionResult->fetch_assoc()
            ) {
                $scionRows[] = $row;
            }

            $scionStmt->close();


            if (
                count($scionRows) <
                $targetTrayQty
            ) {

                throw new Exception(
                    "Only " .
                    count($scionRows) .
                    " Scion tray(s) are available for " .
                    $labourname .
                    ". Required: " .
                    $targetTrayQty .
                    "."
                );
            }


            // ==================================================
            // 6. MARK ROOT + SCION AS CONSUMED
            // ==================================================

            $destroySql = "
                UPDATE krf_polyhouse_plant_details
                SET
                    status = 'Destroy After grafted',
                    workingstatus = 'Destroy After grafted'
                WHERE polyhouseplantid = ?
                  AND status = 'Grafting'
            ";

            $destroyStmt =
                $krfconn->prepare($destroySql);

            if (!$destroyStmt) {
                throw new Exception(
                    'Destroy update prepare failed: ' .
                    $krfconn->error
                );
            }


            // ROOT
            foreach ($rootRows as $rootRow) {

                $sourceId =
                    (int)$rootRow['polyhouseplantid'];

                $destroyStmt->bind_param(
                    "i",
                    $sourceId
                );

                $destroyStmt->execute();

                if (
                    $destroyStmt->affected_rows !== 1
                ) {

                    throw new Exception(
                        "Unable to consume Root tray ID " .
                        $sourceId .
                        "."
                    );
                }
            }


            // SCION
            foreach ($scionRows as $scionRow) {

                $sourceId =
                    (int)$scionRow['polyhouseplantid'];

                $destroyStmt->bind_param(
                    "i",
                    $sourceId
                );

                $destroyStmt->execute();

                if (
                    $destroyStmt->affected_rows !== 1
                ) {

                    throw new Exception(
                        "Unable to consume Scion tray ID " .
                        $sourceId .
                        "."
                    );
                }
            }

            $destroyStmt->close();


            // ==================================================
            // 7. GENERATE PHYSICAL GRAFTED TRAYS
            // ==================================================

            for (
                $i = 0;
                $i < $targetTrayQty;
                $i++
            ) {

                // ----------------------------------------------
                // Generate unique barcode
                // ----------------------------------------------

                do {

                    $alphabet =
                        chr(random_int(65, 90));

                    $number =
                        str_pad(
                            (string)random_int(
                                0,
                                9999
                            ),
                            4,
                            '0',
                            STR_PAD_LEFT
                        );

                    $barcode =
                        'G' .
                        $alphabet .
                        $number;


                    $barcodeSql = "
                        SELECT 1
                        FROM krf_polyhouse_plant_details
                        WHERE barcode = ?
                        LIMIT 1
                    ";

                    $barcodeStmt =
                        $krfconn->prepare(
                            $barcodeSql
                        );

                    if (!$barcodeStmt) {
                        throw new Exception(
                            'Barcode check prepare failed: ' .
                            $krfconn->error
                        );
                    }


                    $barcodeStmt->bind_param(
                        "s",
                        $barcode
                    );

                    $barcodeStmt->execute();

                    $barcodeExists =
                        $barcodeStmt
                            ->get_result()
                            ->num_rows > 0;

                    $barcodeStmt->close();

                } while ($barcodeExists);


                // ----------------------------------------------
                // Insert physical grafted tray
                // ----------------------------------------------

                $insertPlantSql = "
                    INSERT INTO krf_polyhouse_plant_details
                    (
                        planted_date,
                        psono,
                        stockorder_no,
                        roottype,
                        locationid,
                        graftingdate,
                        labourid,
                        cultivation_type,
                        projectid,
                        barcode,
                        productid,
                        productname,
                        tray_id,
                        tray_code,
                        qtypertray,
                        health_status,
                        status,
                        workingstatus,
                        labourname,
                        targettrayassigned
                    )
                    VALUES
                    (
                        CURDATE(),
                        ?,
                        ?,
                        'Grafted',
                        ?,
                        ?,
                        ?,
                        'Tray',
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        'Healthy',
                        'Grafted',
                        'Grafted',
                        ?,
                        ?
                    )
                ";


                $insertPlantStmt =
                    $krfconn->prepare(
                        $insertPlantSql
                    );

                if (!$insertPlantStmt) {
                    throw new Exception(
                        'Grafted tray insert prepare failed: ' .
                        $krfconn->error
                    );
                }


                $graftedQty =
                    $cavityCount;


                $insertPlantStmt->bind_param(
                    "ssssssssssssss",
                    $psono,
                    $stockorder_no,
                    $locationid,
                    $graftingdate,
                    $labourid,
                    $projectid,
                    $barcode,
                    $productid,
                    $productname,
                    $graftedTrayId,
                    $graftedTrayCode,
                    $graftedQty,
                    $labourname,
                    $targetTrayQty
                );


                $insertPlantStmt->execute();


                if (
                    $insertPlantStmt
                        ->affected_rows !== 1
                ) {

                    throw new Exception(
                        'Unable to generate grafted tray.'
                    );
                }


                $insertPlantStmt->close();

                $generatedTrayCount++;
            }


            // ==================================================
            // 8. INSERT DAILY HISTORY
            //
            // IMPORTANT:
            // krf_graftingdetails_daily is HISTORY.
            //
            // Every allocation/save creates a NEW ROW.
            // NO SELECT existing row.
            // NO UPDATE existing row.
            // ==================================================

           // ==========================================================
// 8. INSERT DAILY GRAFTING HISTORY
//
// HISTORY TABLE:
// Every Save = NEW history row.
// Never UPDATE an old history row.
// ==========================================================

$rootTrayAssigned =
    count($rootRows);

$scionTrayAssigned =
    count($scionRows);


// ----------------------------------------------------------
// Prepare history INSERT
// ----------------------------------------------------------

$historyInsertSql = "
    INSERT INTO krf_graftingdetails_daily
    (
        projectid,
        productid,
        productname,
        labourid,
        labourname,
        graftingdate,
        allocationtime,
        todaytarget,
        noroottrayassigned,
        nosciontrayassigned,
        noofgraftedtry,
        created_by,
        locationid,
        psono,
        stockorder_no,
        grafted_traycount
    )
    VALUES
    (
        ?, ?, ?, ?, ?, ?, ?,
        ?, ?, ?, ?, ?, ?, ?, ?, ?
    )
";


$historyStmt =
    $krfconn->prepare($historyInsertSql);


if (!$historyStmt) {

    throw new Exception(
        'Daily grafting history prepare failed: ' .
        $krfconn->error
    );
}


// ----------------------------------------------------------
// Bind values
// ----------------------------------------------------------

$historyStmt->bind_param(

    "issssssiiiissssi",

    $projectid,              // i

    $productid,              // s
    $productname,            // s
    $labourid,               // s
    $labourname,             // s
    $graftingdate,           // s
    $allocationtime,         // s

    $todaytarget,            // i
    $rootTrayAssigned,       // i
    $scionTrayAssigned,      // i
    $targetTrayQty,          // i

    $createdBy,              // s
    $locationid,             // s
    $psono,                  // s
    $stockorder_no,          // s

    $targetTrayQty           // i
);


// ----------------------------------------------------------
// Execute and capture REAL MySQL error
// ----------------------------------------------------------

if (!$historyStmt->execute()) {

    $mysqlError =
        $historyStmt->error;

    $mysqlErrno =
        $historyStmt->errno;

    $historyStmt->close();

    throw new Exception(
        'Daily grafting history INSERT failed. ' .
        'MySQL Error ' .
        $mysqlErrno .
        ': ' .
        $mysqlError
    );
}


// ----------------------------------------------------------
// Verify insert
// ----------------------------------------------------------

if ($historyStmt->affected_rows !== 1) {

    $affectedRows =
        $historyStmt->affected_rows;

    $mysqlError =
        $historyStmt->error;

    $historyStmt->close();

    throw new Exception(
        'Daily grafting history was not inserted. ' .
        'Affected Rows: ' .
        $affectedRows .
        '. ' .
        $mysqlError
    );
}


$historyId =
    $historyStmt->insert_id;


$historyStmt->close();

 

            // ==================================================
            // LABOUR SAVE COMPLETE
            // ==================================================

            $savedLabours++;
        }


        // ======================================================
        // VALIDATE AT LEAST ONE LABOUR SAVED
        // ======================================================

        if ($savedLabours <= 0) {

            throw new Exception(
                'No labour with Grafted Tray Target greater than zero was found.'
            );
        }


        // ======================================================
        // EVERYTHING SUCCESSFUL
        // ======================================================

        $krfconn->commit();


        echo json_encode([

            'status' => 'success',

            'message' =>
                $generatedTrayCount .
                ' grafted tray(s) generated successfully.',

            'data' => [

                'labours_saved' =>
                    $savedLabours,

                'grafted_trays_generated' =>
                    $generatedTrayCount
            ]
        ]);

        exit;


    } catch (Throwable $e) {

        try {

            $krfconn->rollback();

        } catch (Throwable $ignore) {
        }


        echo json_encode([

            'status' => 'error',

            'message' =>
                $e->getMessage()
        ]);

        exit;
    }
}
else if ($action == "getlatestgraftedtray") {
    header('Content-Type: application/json; charset=utf-8');
    $projectid = (int)($_POST['projectid'] ?? 0);
    $productid = trim($_POST['productid'] ?? '');
    if ($projectid <= 0 || $productid === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid project or product.'
        ]);
        exit;
    }

    $sql = " SELECT
            p.tray_id,
            p.tray_code,
            p.graftingdate
        FROM krf_polyhouse_plant_details p
        INNER JOIN krf_tray_master t
            ON t.tray_id = p.tray_id
        WHERE p.projectid = ?
          AND p.productid = ?
          AND p.roottype = 'Grafted' 
          AND p.tray_id IS NOT NULL
          AND p.tray_id > 0
        ORDER BY p.graftingdate DESC 
        LIMIT 1 ";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("is", $projectid, $productid);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    echo json_encode([
        'status' => 'success',
        'data' => $row ?: null
    ]);
    exit;
}else if ($action == "locationbasedorder") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    if ($locationid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Location is required.'
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
  $sql = " SELECT DISTINCT stockorder_no    FROM krf_polyhouse_plant_details  WHERE projectid = ? AND locationid =?  AND status = 'Grafted'   order by stockorder_no";   
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("is", $projectid, $locationid);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [             
            'stockorder_no' => $row['stockorder_no'],
            'stockorderid'        => $row['stockorder_no'] 
        ];
    }
    $stmt->close(); 
    echo json_encode([
        'status' => 'success',
        'data'   => $data 
    ]);
    exit;
} else if ($action == "location_product") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    $stockorder_no = trim($_POST['stockorder'] ?? '');
    if ($locationid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Location is required.'
        ]);
        exit;
    }
    if ($stockorder_no === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Order No is required.'
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
  $sql = " SELECT DISTINCT productname,productid  FROM krf_polyhouse_plant_details  WHERE projectid = ? AND locationid =?  AND status = 'Grafted'  and stockorder_no =?  order by productname";   
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("iss", $projectid, $locationid,$stockorder_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [             
            'productname' => $row['productname'],
            'productid'        => $row['productid'] 
        ];
    }
    $stmt->close(); 
    echo json_encode([
        'status' => 'success',
        'data'   => $data 
    ]);
    exit;
}else if ($action == "location_graftedlabor") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    $stockorder_no = trim($_POST['stockorder'] ?? '');
    $productid = trim($_POST['productid'] ?? '');
    if ($locationid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Location is required.'
        ]);
        exit;
    }
    if ($stockorder_no === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Order No is required.'
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
    $sql = " SELECT DISTINCT labourid,labourname  FROM krf_polyhouse_plant_details  WHERE projectid = ? AND locationid =?  AND status = 'Grafted'  and stockorder_no =?  and productid =? order by labourname and labourid is not null and labourid <> '' and labourid <> 0";   
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("isss", $projectid, $locationid,$stockorder_no,$productid);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [             
            'labourid' => $row['labourid'],
            'labourname'        => $row['labourname'] 
        ];
    }
    $stmt->close(); 
    echo json_encode([
        'status' => 'success',
        'data'   => $data 
    ]);
    exit;
}else if ($action == "getlocation") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $plantstatus = trim($_POST['plantstatus'] ?? '');
    if ($projectid <= 0) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid project.'
        ]);
        exit;
    } 
     $sql="";
     if ($plantstatus==="Grafted" ){
        $sql = " SELECT DISTINCT locationid as movetolocation  FROM krf_polyhouse_plant_details
        WHERE projectid = ? AND locationid IS NOT NULL AND locationid <> ''  AND status = ? ";

     }else{
        $sql = " SELECT DISTINCT movetolocation  FROM krf_polyhouse_plant_details
        WHERE projectid = ? AND movetolocation IS NOT NULL AND movetolocation <> ''  AND status = ? ";

     }

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'KRF query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("is", $projectid,$plantstatus);
    $stmt->execute();
    $result = $stmt->get_result();
    $locationIds = [];
    while ($row = $result->fetch_assoc()) {
        $locationId =  $row['movetolocation'];
       
            $locationIds[] = $locationId;
         
    }
    $stmt->close();

    /*No locations found */
    if (empty($locationIds)) {
        echo json_encode([
            'status' => 'success',
            'data'   => [],
            'traycavity' => []
        ]);
        exit;
    }

    /* STEP 2: Get location names from HRM database*/
    $placeholders = implode(',', array_fill(0, count($locationIds), '?'));
    $sql = "  SELECT  locationid,  locationname  FROM hrm_AttendanceLocationMaster   WHERE projectid = ? AND locationid IN ($placeholders)  ORDER BY locationname ";
    $stmt = $hrmconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'HRM query prepare failed: ' . $hrmconn->error
        ]);
        exit;
    }
    /* projectid + all location IDs are integers */
    $types  = 's' . str_repeat('s', count($locationIds));
    $params = array_merge([$projectid], $locationIds);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $locations = [];
    while ($row = $result->fetch_assoc()) {
        $locations[] = [
            'locationid' =>  $row['locationid'],
            'locationname'   => $row['locationname']
        ];
    }
    $stmt->close();
    $traycavity=gettraycavity($krfconn );
    echo json_encode([
        'status' => 'success',
        'data'   => $locations,
        'traycavity'   => $traycavity
    ]);
    exit;
} else if ($action == "locationbaseddata") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');
    if ($locationid === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Location is required.'
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
    $sql = "SELECT group_concat(sowinglotno_internal) as sowinglotno_internal,
            group_concat(sowingbatchno) as sowingbatchno,  stockorder_no, productid,  productname,
            SUM(CASE   WHEN roottype = 'root' THEN 1   ELSE 0 
            END  ) AS totalroottray, 
            SUM( CASE   WHEN roottype = 'scion' THEN 1   ELSE 0 
            END  ) AS totalsciontray, 
            SUM( CASE   WHEN roottype = 'root' THEN qtypertray    ELSE 0 
            END  ) AS totalrootqty, 
            SUM(  CASE    WHEN roottype = 'scion' THEN qtypertray    ELSE 0 
            END  ) AS totalscionqty
            FROM  krf_polyhouse_plant_details WHERE movetolocation = ?   AND projectid = ?  AND status = 'Grafting'   GROUP BY  stockorder_no, productid, productname  ORDER BY  productname  ";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("si", $locationid, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            // 'dispatchdate'         => $row['dispatchdate'],
            'sowinglotno_internal' => $row['sowinglotno_internal'],
            'sowingbatchno'        => $row['sowingbatchno'], 
            'stockorder_no'        => $row['stockorder_no'],
            'productid'            => $row['productid'],
            'productname'          => $row['productname'],
            'totalroottray'            => (int)$row['totalroottray'],
            'totalsciontray'             => (int)$row['totalsciontray'],
            'totalrootqty'            => (int)$row['totalrootqty'],
            'totalscionqty'             => (int)$row['totalscionqty']
        ];
    }
    $stmt->close();

   $polyhousedata= [];//getpolyhouse($krfconn,$projectid,$locationid);
    echo json_encode([
        'status' => 'success',
        'data'   => $data,
        'polyhousedata' =>$polyhousedata
    ]);
    exit;
}else if ($action == "getgraftingmodalmasters") {

    header('Content-Type: application/json; charset=utf-8');

    $projectid  = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');

    if ($projectid <= 0) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Invalid project.'
        ]);

        exit;
    }


    /* =========================================================
       STEP 1: ALL LOCATIONS
       HRM CONNECTION
    ========================================================= */

    $locations = [];

    $sqlLocation = "
        SELECT
            locationid,
            locationname
        FROM hrm_AttendanceLocationMaster
        WHERE projectid = ?
        ORDER BY locationname
    ";

    $stmtLocation = $hrmconn->prepare($sqlLocation);

    if (!$stmtLocation) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Location query prepare failed: ' .
                         $hrmconn->error
        ]);

        exit;
    }

    $stmtLocation->bind_param(
        "i",
        $projectid
    );

    $stmtLocation->execute();

    $resultLocation =
        $stmtLocation->get_result();

    while ($row = $resultLocation->fetch_assoc()) {

        $locations[] = [
            'locationid'   => $row['locationid'],
            'locationname' => $row['locationname']
        ];
    }

    $stmtLocation->close();


    /* =========================================================
       STEP 2: LABOUR FOR CURRENT/SELECTED LOCATION
       HRM CONNECTION
    ========================================================= */

    $labours = [];

    if ($locationid !== '') {

        $sqlLabour = "
            SELECT
                locationid,
                EmployeeID,
                TRIM(
                    CONCAT_WS(
                        ' ',
                        NULLIF(Firstname, ''),
                        NULLIF(Middlename, ''),
                        NULLIF(Lastname, '')
                    )
                ) AS lbrname

            FROM hrm_LabourMaster

            WHERE Status = 'Active'
              AND graftingskill = 'yes'
              AND locationid = ?

            ORDER BY
                Firstname,
                Middlename,
                Lastname
        ";

        $stmtLabour =
            $hrmconn->prepare($sqlLabour);

        if (!$stmtLabour) {

            echo json_encode([
                'status'  => 'error',
                'message' => 'Labour query prepare failed: ' .
                             $hrmconn->error
            ]);

            exit;
        }

        $stmtLabour->bind_param(
            "s",
            $locationid
        );

        $stmtLabour->execute();

        $resultLabour =
            $stmtLabour->get_result();

        while (
            $row = $resultLabour->fetch_assoc()
        ) {

            $labours[] = [
                'locationid' => $row['locationid'],
                'EmployeeID' => $row['EmployeeID'],
                'lbrname'    => $row['lbrname']
            ];
        }

        $stmtLabour->close();
    }


    /* =========================================================
       STEP 3: TRAY MASTER
       KRF CONNECTION
    ========================================================= */

    $trays = [];

    $sqlTray = "
        SELECT
            t.tray_id,
            t.tray_code,
            t.tray_name,
            t.cavity_count,
            t.cavity_size,
            t.material,

            c.category_id,
            c.category_code,
            c.category_name

        FROM krf_tray_master t

        INNER JOIN krf_tray_category c
            ON t.category_id = c.category_id

        ORDER BY
            c.category_name,
            t.tray_name
    ";

    $stmtTray =
        $krfconn->prepare($sqlTray);

    if (!$stmtTray) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Tray query prepare failed: ' .
                         $krfconn->error
        ]);

        exit;
    }

    $stmtTray->execute();

    $resultTray =
        $stmtTray->get_result();

    while ($row = $resultTray->fetch_assoc()) {

        $trays[] = [
            'tray_id'       => $row['tray_id'],
            'tray_code'     => $row['tray_code'],
            'tray_name'     => $row['tray_name'],
            'cavity_count'  => $row['cavity_count'],
            'cavity_size'   => $row['cavity_size'],
            'material'      => $row['material'],
            'category_id'   => $row['category_id'],
            'category_code' => $row['category_code'],
            'category_name' => $row['category_name']
        ];
    }

    $stmtTray->close();


    echo json_encode([
        'status' => 'success',

        'data' => [
            'locations' => $locations,
            'labours'   => $labours,
            'trays'     => $trays
        ]
    ]);

    exit;
 }else if ($action == "getgraftinglabour") {

    header('Content-Type: application/json; charset=utf-8');

    $projectid = (int)($_POST['projectid'] ?? 0);
    $locationid = trim($_POST['locationid'] ?? '');

    if ($projectid <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid project.'
        ]);
        exit;
    }

    if ($locationid === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Location is required.'
        ]);
        exit;
    }

    $sql = "
        SELECT
            locationid,
            EmployeeID,
            TRIM(
                CONCAT_WS(
                    ' ',
                    NULLIF(Firstname, ''),
                    NULLIF(Middlename, ''),
                    NULLIF(Lastname, '')
                )
            ) AS lbrname
        FROM hrm_LabourMaster
        WHERE Status = 'Active'
          AND graftingskill = 'yes'
          AND locationid = ?
        ORDER BY lbrname
    ";

    $stmt = $hrmconn->prepare($sql);

    if (!$stmt) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Labour query prepare failed: ' . $hrmconn->error
        ]);
        exit;
    }

    $stmt->bind_param(
        "s",
        $locationid
    );

    $stmt->execute();

    $result = $stmt->get_result();

    $data = [];

    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'data' => $data
    ]);

    exit;
}else if ($action == "savegrafting") {

    header('Content-Type: application/json; charset=utf-8');

    $projectid = (int)($_POST['projectid'] ?? 0);

    // Source location where Root + Scion currently exist
    $locationid = trim($_POST['locationid'] ?? '');

    // Location where new Grafted trays will be kept
    $grafted_locationid = trim($_POST['grafted_locationid'] ?? '');

    $stockorder_no = trim($_POST['stockorder_no'] ?? '');
    $productid = trim($_POST['productid'] ?? '');
    $productname = trim($_POST['productname'] ?? '');

    // Selected Grafted Tray Type from krf_tray_master
    $grafted_tray_id = (int)($_POST['tray_id'] ?? 0);

    // Selected grafting labour
    $labourid = trim($_POST['labourid'] ?? '');

    // Number of new Grafted trays requested
    $newtrayqty = (int)($_POST['newtrayqty'] ?? 0);


    /* =========================================================
       BASIC VALIDATION
    ========================================================= */

    if ($projectid <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid project.'
        ]);
        exit;
    }

    if ($locationid === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Source Grafting Location is required.'
        ]);
        exit;
    }

    if ($grafted_locationid === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Grafted Tray Location is required.'
        ]);
        exit;
    }

    if ($stockorder_no === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Stock Order No. is required.'
        ]);
        exit;
    }

    if ($productid === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Product is required.'
        ]);
        exit;
    }

    if ($grafted_tray_id <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please select Grafted Tray Type.'
        ]);
        exit;
    }

    if ($labourid === '') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please select Grafting Labour.'
        ]);
        exit;
    }

    if ($newtrayqty <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'New Tray Qty must be greater than zero.'
        ]);
        exit;
    }


    try {

        /* =========================================================
           STEP 1 : VALIDATE GRAFTED LOCATION
           IMPORTANT: HRM TABLE => $hrmconn
        ========================================================= */

        $sqlLocation = "
            SELECT
                locationid,
                locationname
            FROM hrm_AttendanceLocationMaster
            WHERE locationid = ?
              AND projectid = ?
            LIMIT 1
        ";

        $stmtLocation = $hrmconn->prepare($sqlLocation);

        if (!$stmtLocation) {
            throw new Exception(
                "Location query prepare failed: " . $hrmconn->error
            );
        }

        $stmtLocation->bind_param(
            "si",
            $grafted_locationid,
            $projectid
        );

        $stmtLocation->execute();

        $locationResult = $stmtLocation->get_result();
        $locationRow = $locationResult->fetch_assoc();

        $stmtLocation->close();

        if (!$locationRow) {
            throw new Exception(
                "Selected Grafted Tray Location is invalid."
            );
        }

        $grafted_locationname = $locationRow['locationname'];


        /* =========================================================
           STEP 2 : VALIDATE GRAFTING LABOUR
           IMPORTANT: HRM TABLE => $hrmconn
        ========================================================= */

        $sqlLabour = "
            SELECT
                EmployeeID,
                TRIM(
                    CONCAT_WS(
                        ' ',
                        NULLIF(Firstname, ''),
                        NULLIF(Middlename, ''),
                        NULLIF(Lastname, '')
                    )
                ) AS lbrname
            FROM hrm_LabourMaster
            WHERE EmployeeID = ?
              AND locationid = ?
              AND Status = 'Active'
              AND graftingskill = 'yes'
            LIMIT 1
        ";

        $stmtLabour = $hrmconn->prepare($sqlLabour);

        if (!$stmtLabour) {
            throw new Exception(
                "Labour query prepare failed: " . $hrmconn->error
            );
        }

        $stmtLabour->bind_param(
            "ss",
            $labourid,
            $grafted_locationid
        );

        $stmtLabour->execute();

        $labourResult = $stmtLabour->get_result();
        $labourRow = $labourResult->fetch_assoc();

        $stmtLabour->close();

        if (!$labourRow) {
            throw new Exception(
                "Selected Grafting Labour is invalid, inactive, " .
                "not skilled for grafting, or does not belong to selected location."
            );
        }

        $labourname = $labourRow['lbrname'];


        /* =========================================================
           STEP 3 : GET SELECTED GRAFTED TRAY MASTER

           Do NOT trust cavity_count from JavaScript.
           Fetch actual cavity_count again from DB.
        ========================================================= */

        $sqlTray = "
            SELECT
                t.tray_id,
                t.tray_code,
                t.tray_name,
                t.cavity_count,
                t.cavity_size,
                t.material,
                c.category_id,
                c.category_code,
                c.category_name
            FROM krf_tray_master t
            INNER JOIN krf_tray_category c
                ON c.category_id = t.category_id
            WHERE t.tray_id = ?
            LIMIT 1
        ";

        $stmtTray = $krfconn->prepare($sqlTray);

        if (!$stmtTray) {
            throw new Exception(
                "Tray query prepare failed: " . $krfconn->error
            );
        }

        $stmtTray->bind_param(
            "i",
            $grafted_tray_id
        );

        $stmtTray->execute();

        $trayResult = $stmtTray->get_result();
        $trayMaster = $trayResult->fetch_assoc();

        $stmtTray->close();

        if (!$trayMaster) {
            throw new Exception(
                "Selected Grafted Tray Type not found."
            );
        }

        $cavityCount = (int)$trayMaster['cavity_count'];
        $newGraftedTrayId = (int)$trayMaster['tray_id'];
        $newGraftedTrayCode = trim($trayMaster['tray_code'] ?? '');

        if ($cavityCount <= 0) {
            throw new Exception(
                "Selected Grafted Tray has invalid cavity count."
            );
        }


        /* =========================================================
           STEP 4 : START KRF TRANSACTION
        ========================================================= */

        $krfconn->begin_transaction();


        /* =========================================================
           STEP 5 : LOCK ALL AVAILABLE ROOT TRAYS

           NO LIMIT $newtrayqty

           We need plant quantity, not tray quantity.
        ========================================================= */

        $sqlRoot = "
            SELECT
                polyhouseplantid,
                tray_id,
                tray_code,
                barcode,
                planted_date,
                psono,
                stockorder_no,
                locationid,
                movetolocation,
                projectid,
                productid,
                productname,
                qtypertray,
                health_status,
                cultivation_type
            FROM krf_polyhouse_plant_details
            WHERE projectid = ?
              AND movetolocation = ?
              AND stockorder_no = ?
              AND productid = ?
              AND roottype = 'root'
              AND status = 'Grafting'
            ORDER BY polyhouseplantid
            FOR UPDATE
        ";

        $stmtRoot = $krfconn->prepare($sqlRoot);

        if (!$stmtRoot) {
            throw new Exception(
                "Root query prepare failed: " . $krfconn->error
            );
        }

        $stmtRoot->bind_param(
            "isss",
            $projectid,
            $locationid,
            $stockorder_no,
            $productid
        );

        $stmtRoot->execute();

        $rootResult = $stmtRoot->get_result();

        $rootRows = [];
        $totalRootQty = 0;

        while ($row = $rootResult->fetch_assoc()) {

            $row['qtypertray'] = (int)$row['qtypertray'];

            if ($row['qtypertray'] <= 0) {
                continue;
            }

            $totalRootQty += $row['qtypertray'];
            $rootRows[] = $row;
        }

        $stmtRoot->close();

        if (count($rootRows) === 0) {
            throw new Exception(
                "No Root trays are available for grafting."
            );
        }


        /* =========================================================
           STEP 6 : LOCK ALL AVAILABLE SCION TRAYS
        ========================================================= */

        $sqlScion = "
            SELECT
                polyhouseplantid,
                tray_id,
                tray_code,
                barcode,
                planted_date,
                psono,
                stockorder_no,
                locationid,
                movetolocation,
                projectid,
                productid,
                productname,
                qtypertray,
                health_status,
                cultivation_type
            FROM krf_polyhouse_plant_details
            WHERE projectid = ?
              AND movetolocation = ?
              AND stockorder_no = ?
              AND productid = ?
              AND roottype = 'scion'
              AND status = 'Grafting'
            ORDER BY polyhouseplantid
            FOR UPDATE
        ";

        $stmtScion = $krfconn->prepare($sqlScion);

        if (!$stmtScion) {
            throw new Exception(
                "Scion query prepare failed: " . $krfconn->error
            );
        }

        $stmtScion->bind_param(
            "isss",
            $projectid,
            $locationid,
            $stockorder_no,
            $productid
        );

        $stmtScion->execute();

        $scionResult = $stmtScion->get_result();

        $scionRows = [];
        $totalScionQty = 0;

        while ($row = $scionResult->fetch_assoc()) {

            $row['qtypertray'] = (int)$row['qtypertray'];

            if ($row['qtypertray'] <= 0) {
                continue;
            }

            $totalScionQty += $row['qtypertray'];
            $scionRows[] = $row;
        }

        $stmtScion->close();

        if (count($scionRows) === 0) {
            throw new Exception(
                "No Scion trays are available for grafting."
            );
        }


        /* =========================================================
           STEP 7 : CALCULATE MAXIMUM AVAILABLE GRAFTING QTY
        ========================================================= */

        $availablePlantQty = min(
            $totalRootQty,
            $totalScionQty
        );

        if ($availablePlantQty <= 0) {
            throw new Exception(
                "No usable Root / Scion plant quantity is available."
            );
        }


        /*
         * Example:
         *
         * Root = 12,600
         * Scion = 5,000
         * Grafted cavity = 209
         *
         * CEIL(5000 / 209) = 24
         */

        $maxTrayQty = (int)ceil(
            $availablePlantQty / $cavityCount
        );

        if ($newtrayqty > $maxTrayQty) {
            throw new Exception(
                "Maximum " .
                $maxTrayQty .
                " Grafted tray(s) can be generated for selected " .
                $cavityCount .
                " cavity tray."
            );
        }


        /* =========================================================
           STEP 8 : CALCULATE PLANTS REQUIRED FOR THIS SAVE

           If user selects 10:
           10 × 209 = 2090

           If user selects maximum 24:
           24 × 209 = 5016
           but only 5000 available

           Therefore required = 5000
        ========================================================= */

        $requestedPlantQty = $newtrayqty * $cavityCount;

        $requiredPlantQty = min(
            $requestedPlantQty,
            $availablePlantQty
        );

        if ($requiredPlantQty <= 0) {
            throw new Exception(
                "Invalid grafting plant quantity."
            );
        }


        /* =========================================================
           STEP 9 : PREPARE SOURCE UPDATE STATEMENTS

           FULL CONSUMPTION:
           Destroy source tray.

           PARTIAL CONSUMPTION:
           Reduce qtypertray and keep Grafting.
        ========================================================= */

        $sqlDestroy = "
            UPDATE krf_polyhouse_plant_details
            SET
                status = 'Destroy After grafted',
                workingstatus = 'Destroy After grafted'
            WHERE polyhouseplantid = ?
              AND projectid = ?
              AND status = 'Grafting'
        ";

        $stmtDestroy = $krfconn->prepare($sqlDestroy);

        if (!$stmtDestroy) {
            throw new Exception(
                "Destroy query prepare failed: " . $krfconn->error
            );
        }


        $sqlPartial = "
            UPDATE krf_polyhouse_plant_details
            SET qtypertray = ?
            WHERE polyhouseplantid = ?
              AND projectid = ?
              AND status = 'Grafting'
        ";

        $stmtPartial = $krfconn->prepare($sqlPartial);

        if (!$stmtPartial) {
            throw new Exception(
                "Partial source query prepare failed: " . $krfconn->error
            );
        }


        /* =========================================================
           STEP 10 : CONSUME ROOT PLANTS
        ========================================================= */

        $rootRemainingToConsume = $requiredPlantQty;
        $rootDestroyedCount = 0;
        $rootPartialCount = 0;

        foreach ($rootRows as $rootRow) {

            if ($rootRemainingToConsume <= 0) {
                break;
            }

            $sourcePlantId = (int)$rootRow['polyhouseplantid'];
            $sourceQty = (int)$rootRow['qtypertray'];

            if ($sourceQty <= 0) {
                continue;
            }


            /*
             * Entire Root tray is used
             */
            if ($rootRemainingToConsume >= $sourceQty) {

                $stmtDestroy->bind_param(
                    "ii",
                    $sourcePlantId,
                    $projectid
                );

                $stmtDestroy->execute();

                if ($stmtDestroy->affected_rows !== 1) {
                    throw new Exception(
                        "Unable to consume Root tray ID " .
                        $sourcePlantId
                    );
                }

                $rootRemainingToConsume -= $sourceQty;
                $rootDestroyedCount++;
            }

            /*
             * Only part of Root tray is used
             */
            else {

                $balanceQty = $sourceQty - $rootRemainingToConsume;

                $stmtPartial->bind_param(
                    "iii",
                    $balanceQty,
                    $sourcePlantId,
                    $projectid
                );

                $stmtPartial->execute();

                if ($stmtPartial->affected_rows !== 1) {
                    throw new Exception(
                        "Unable to partially consume Root tray ID " .
                        $sourcePlantId
                    );
                }

                $rootRemainingToConsume = 0;
                $rootPartialCount++;
            }
        }

        if ($rootRemainingToConsume > 0) {
            throw new Exception(
                "Insufficient Root plants. Short by " .
                $rootRemainingToConsume .
                " plant(s)."
            );
        }


        /* =========================================================
           STEP 11 : CONSUME SCION PLANTS

           IMPORTANT:
           Consume exactly SAME plant quantity as Root.
        ========================================================= */

        $scionRemainingToConsume = $requiredPlantQty;
        $scionDestroyedCount = 0;
        $scionPartialCount = 0;

        foreach ($scionRows as $scionRow) {

            if ($scionRemainingToConsume <= 0) {
                break;
            }

            $sourcePlantId = (int)$scionRow['polyhouseplantid'];
            $sourceQty = (int)$scionRow['qtypertray'];

            if ($sourceQty <= 0) {
                continue;
            }


            /*
             * Entire Scion tray is used
             */
            if ($scionRemainingToConsume >= $sourceQty) {

                $stmtDestroy->bind_param(
                    "ii",
                    $sourcePlantId,
                    $projectid
                );

                $stmtDestroy->execute();

                if ($stmtDestroy->affected_rows !== 1) {
                    throw new Exception(
                        "Unable to consume Scion tray ID " .
                        $sourcePlantId
                    );
                }

                $scionRemainingToConsume -= $sourceQty;
                $scionDestroyedCount++;
            }

            /*
             * Only part of Scion tray is used
             */
            else {

                $balanceQty = $sourceQty - $scionRemainingToConsume;

                $stmtPartial->bind_param(
                    "iii",
                    $balanceQty,
                    $sourcePlantId,
                    $projectid
                );

                $stmtPartial->execute();

                if ($stmtPartial->affected_rows !== 1) {
                    throw new Exception(
                        "Unable to partially consume Scion tray ID " .
                        $sourcePlantId
                    );
                }

                $scionRemainingToConsume = 0;
                $scionPartialCount++;
            }
        }

        if ($scionRemainingToConsume > 0) {
            throw new Exception(
                "Insufficient Scion plants. Short by " .
                $scionRemainingToConsume .
                " plant(s)."
            );
        }

        $stmtDestroy->close();
        $stmtPartial->close();


        /* =========================================================
           STEP 12 : SOURCE INFORMATION FOR NEW GRAFTED RECORDS

           Keep your existing source-information rule.
        ========================================================= */

        $sourceRow = $rootRows[0];

        $planted_date = $sourceRow['planted_date'];
        $psono = $sourceRow['psono'];
        $health_status = $sourceRow['health_status'];
        $cultivation_type = $sourceRow['cultivation_type'];


        /* =========================================================
           STEP 13 : NEW GRAFTED TRAY INFORMATION

           IMPORTANT:
           tray_id is selected Grafted Tray Type,
           NOT Root/Scion tray_id.
        ========================================================= */

        $tray_id = $newGraftedTrayId;


        /* =========================================================
           STEP 14 : PREPARE GRAFTED INSERT
        ========================================================= */

        $sqlInsert = "
            INSERT INTO krf_polyhouse_plant_details
            (
                planted_date,
                psono,
                stockorder_no,
                roottype,
                locationid,
                graftingdate,
                labourid,
                cultivation_type,
                projectid,
                barcode,
                productid,
                productname,
                tray_id,
                tray_code,
                qtypertray,
                health_status,
                status,
                workingstatus,
                labourname
            )
            VALUES
            (
                ?,
                ?,
                ?,
                'Grafted',
                ?,
                NOW(),
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                ?,
                'Grafted',
                'Grafted',
                ?
            )
        ";

        $stmtInsert = $krfconn->prepare($sqlInsert);

        if (!$stmtInsert) {
            throw new Exception(
                "Insert query prepare failed: " . $krfconn->error
            );
        }


        /* =========================================================
           STEP 15 : PREPARE BARCODE DUPLICATE CHECK

           Barcode format:
               G + 5 ALPHANUMERIC

           Examples:
               G267A3
               G8K2P9
               GZ19Q4

           TOTAL LENGTH = 6
        ========================================================= */

        $sqlBarcodeCheck = "
            SELECT 1
            FROM krf_polyhouse_plant_details
            WHERE barcode = ?
            LIMIT 1
        ";

        $stmtBarcodeCheck = $krfconn->prepare($sqlBarcodeCheck);

        if (!$stmtBarcodeCheck) {
            throw new Exception(
                "Barcode check prepare failed: " . $krfconn->error
            );
        }


        /* =========================================================
           STEP 16 : GENERATE NEW GRAFTED TRAYS
        ========================================================= */

        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $generatedBarcodes = [];
        $generatedTrayDetails = [];
        $remainingOutputQty = $requiredPlantQty;
        $actualGeneratedTrayCount = 0;
        for ($i = 0; $i < $newtrayqty; $i++) {
            if ($remainingOutputQty <= 0) {
                break;
            }
            $qtypertray = min($cavityCount, $remainingOutputQty);
            $barcode = '';
            $barcodeGenerated = false;

            for ($attempt = 1; $attempt <= 100; $attempt++) {
                /*
                 * Barcode format:
                 * Position 1 = G
                 * Position 2 = Numeric 0-9
                 * Position 3-6 = Alphanumeric
                 * Example: G2FW9R */
                $randomPart = (string)random_int(0, 9);
                for ($j = 0; $j < 4; $j++) {
                    $randomPart .= $characters[
                        random_int(0, strlen($characters) - 1)
                    ];
                }
                $candidateBarcode = 'G' . $randomPart;
                $stmtBarcodeCheck->bind_param("s", $candidateBarcode);
                $stmtBarcodeCheck->execute();
                $barcodeResult = $stmtBarcodeCheck->get_result();
                if ($barcodeResult->num_rows === 0) {
                    $barcode = $candidateBarcode;
                    $barcodeGenerated = true;
                    break;
                }
            }
            if (!$barcodeGenerated) {
                throw new Exception(
                    "Unable to generate unique Grafted barcode."
                );
            }
            $newLocationId = $grafted_locationid;
            $newLabourId = $labourid;
            $newTrayId = $tray_id;
            $newTrayCode = $newGraftedTrayCode;
            $newQtyPerTray = (int)$qtypertray;
            $stmtInsert->bind_param(
                "ssssssisssissss",
                $planted_date,
                $psono,
                $stockorder_no,
                $newLocationId,
                $newLabourId,
                $cultivation_type,
                $projectid,
                $barcode,
                $productid,
                $productname,
                $newTrayId,
                $newTrayCode,
                $newQtyPerTray,
                $health_status,
                $labourname
            );
            $stmtInsert->execute();
            if ($stmtInsert->affected_rows !== 1) {
                throw new Exception(
                    "Unable to generate new Grafted tray."
                );
            }
            $actualGeneratedTrayCount++;
            $generatedBarcodes[] = $barcode;
            $generatedTrayDetails[] = [
                'barcode' => $barcode,
                'tray_id' => $newTrayId,
                'tray_code' => $newTrayCode,
                'tray_name' => $trayMaster['tray_name'],
                'cavity_count' => $cavityCount,
                'qtypertray' => $newQtyPerTray,
                'labourid' => $newLabourId,
                'labourname' => $labourname,
                'locationid' => $newLocationId,
                'locationname' => $grafted_locationname
            ];
            $remainingOutputQty -= $newQtyPerTray;
        }
        $stmtBarcodeCheck->close();
        $stmtInsert->close();

        /* ============STEP 17 : FINAL SAFETY CHECK========= */
        if ($remainingOutputQty !== 0) {
            throw new Exception(
                "Grafted tray quantity distribution mismatch. " .
                "Remaining plant quantity: " .
                $remainingOutputQty
            );
        }
        if ($actualGeneratedTrayCount !== $newtrayqty) {
            throw new Exception(
                "Expected " .
                $newtrayqty .
                " Grafted tray(s), but generated " .
                $actualGeneratedTrayCount .
                "."
            );
        }
        /* =================STEP 18 : COMMIT============== */
        $krfconn->commit();
        echo json_encode([
            'status' => 'success',
            'message' =>
                $actualGeneratedTrayCount .
                ' Grafted tray(s) generated successfully.',

            'data' => [

                'tray_id' => $newGraftedTrayId,
                'tray_code' => $newGraftedTrayCode,
                'tray_name' => $trayMaster['tray_name'],
                'cavity_count' => $cavityCount,

                'labourid' => $labourid,
                'labourname' => $labourname,

                'locationid' => $grafted_locationid,
                'locationname' => $grafted_locationname,

                'total_root_qty_before' => $totalRootQty,
                'total_scion_qty_before' => $totalScionQty,

                'available_grafting_qty' => $availablePlantQty,

                'plants_consumed_root' => $requiredPlantQty,
                'plants_consumed_scion' => $requiredPlantQty,

                'root_trays_destroyed' => $rootDestroyedCount,
                'root_partial_trays' => $rootPartialCount,

                'scion_trays_destroyed' => $scionDestroyedCount,
                'scion_partial_trays' => $scionPartialCount,

                'max_tray_qty' => $maxTrayQty,

                'new_trays' => $actualGeneratedTrayCount,
                'grafted_plant_qty' => $requiredPlantQty,

                'barcodes' => $generatedBarcodes,
                'tray_details' => $generatedTrayDetails
            ]
        ]);

        exit;
    }

    catch (Throwable $e) {

        try {
            $krfconn->rollback();
        }
        catch (Throwable $rollbackError) {
            // Ignore rollback error and return original error.
        }

        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);

        exit;
    }
}




 //else if ($action == "savegrafting") {
//     header('Content-Type: application/json');
//     $projectid     = (int)($_POST['projectid'] ?? 0);
//     $locationid    = trim($_POST['locationid'] ?? '');
//     $stockorder_no = trim($_POST['stockorder_no'] ?? '');
//     $productid     = trim($_POST['productid'] ?? '');
//     $productname   = trim($_POST['productname'] ?? '');
//     $newtrayqty    = (int)($_POST['newtrayqty'] ?? 0);
//     if ($projectid <= 0) {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Invalid project.'
//         ]);
//         exit;
//     }
//     if ($locationid === '') {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Location is required.'
//         ]);
//         exit;
//     }
//     if ($stockorder_no === '') {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Stock Order No. is required.'
//         ]);
//         exit;
//     }
//     if ($productid === '') {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'Product is required.'
//         ]);
//         exit;
//     }
//     if ($newtrayqty <= 0) {
//         echo json_encode([
//             'status'  => 'error',
//             'message' => 'New Tray Qty must be greater than zero.'
//         ]);
//         exit;
//     }


//     try {
//         $krfconn->begin_transaction();
//         $sqlRoot = "SELECT  polyhouseplantid,tray_id,   barcode, planted_date, psono, stockorder_no,  locationid, movetolocation, projectid,
//                 productid, productname,  qtypertray,  health_status,  cultivation_type   FROM krf_polyhouse_plant_details
//             WHERE projectid = ?  AND movetolocation = ? AND stockorder_no = ?   AND productid = ?
//               AND roottype = 'root'   AND status = 'Grafting'   ORDER BY tray_id
//             LIMIT ?
//             FOR UPDATE";

//         $stmtRoot = $krfconn->prepare($sqlRoot);
//         if (!$stmtRoot) {
//             throw new Exception(
//                 "Root query prepare failed: " .
//                 $krfconn->error
//             );
//         }
//         $stmtRoot->bind_param(
//             "isssi",
//             $projectid,
//             $locationid,
//             $stockorder_no,
//             $productid,
//             $newtrayqty
//         );
//         $stmtRoot->execute();
//         $rootResult = $stmtRoot->get_result();
//         $rootRows = [];
//         while ($row = $rootResult->fetch_assoc()) {
//             $rootRows[] = $row;
//         }
//         $stmtRoot->close();

//         if (count($rootRows) != $newtrayqty) {
//             throw new Exception(
//                 "Only " .
//                 count($rootRows) .
//                 " Root tray(s) are currently available. " .
//                 $newtrayqty .
//                 " required."
//             );
//         }
//         /* ============== STEP 2 SELECT SCION TRAYS === */ 
//         $sqlScion = " SELECT polyhouseplantid,tray_id, barcode, planted_date,  psono,  stockorder_no, locationid,  movetolocation, projectid,
//                 productid,   productname,  qtypertray,  health_status,  cultivation_type
//             FROM krf_polyhouse_plant_details
//             WHERE projectid = ?   AND movetolocation = ?   AND stockorder_no = ?   AND productid = ?
//               AND roottype = 'scion'  AND status = 'Grafting'
//             ORDER BY tray_id
//             LIMIT ?
//             FOR UPDATE
//         ";
//         $stmtScion = $krfconn->prepare($sqlScion);
//         if (!$stmtScion) {
//             throw new Exception(
//                 "Scion query prepare failed: " .
//                 $krfconn->error
//             );
//         }
//         $stmtScion->bind_param(
//             "isssi",
//             $projectid,
//             $locationid,
//             $stockorder_no,
//             $productid,
//             $newtrayqty
//         );

//         $stmtScion->execute();
//         $scionResult = $stmtScion->get_result();
//         $scionRows = [];
//         while ($row = $scionResult->fetch_assoc()) {
//             $scionRows[] = $row;
//         }
//         $stmtScion->close();
//         if (count($scionRows) != $newtrayqty) {
//             throw new Exception(
//                 "Only " .
//                 count($scionRows) .
//                 " Scion tray(s) are currently available. " .
//                 $newtrayqty .
//                 " required."
//             );
//         }

//         /* ============== STEP 3 MARK EXACT ROOT + SCION TRAYS DESTROYED === */


//         $sqlDestroy = " UPDATE krf_polyhouse_plant_details SET status = 'Destroy After grafted'
//             WHERE polyhouseplantid  = ?  AND projectid = ?  AND status = 'Grafting'  ";
//         $stmtDestroy = $krfconn->prepare($sqlDestroy);
//         if (!$stmtDestroy) {
//             throw new Exception(
//                 "Destroy query prepare failed: " .
//                 $krfconn->error
//             );
//         }
//         /* Destroy Root trays */
//         foreach ($rootRows as $rootRow) {
//             $polyhouseplantid = $rootRow['polyhouseplantid'];
//             $stmtDestroy->bind_param(
//                 "si",
//                 $polyhouseplantid,
//                 $projectid
//             );
//             $stmtDestroy->execute();
//             if ($stmtDestroy->affected_rows !== 1) {
//                 throw new Exception(
//                     "Unable to destroy Root tray: " .
//                     $polyhouseplantid
//                 );
//             }
//         }
//         /* Destroy Scion trays */
//         foreach ($scionRows as $scionRow) {
//             $polyhouseplantid = $scionRow['polyhouseplantid'];
//             $stmtDestroy->bind_param(
//                 "si",
//                 $polyhouseplantid,
//                 $projectid
//             );
//             $stmtDestroy->execute();
//             if ($stmtDestroy->affected_rows !== 1) {
//                 throw new Exception(
//                     "Unable to destroy Scion tray: " .
//                     $polyhouseplantid
//                 );
//             }
//         }
//         $stmtDestroy->close();

//         /* ============  STEP 4  GENERATE NEW GRAFTED TRAYS ===== */  
//         $barcodeBase = 'TRG'. date('ymd'). strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
//         $sourceRow = $rootRows[0];
//         $planted_date     = $sourceRow['planted_date'];
//         $psono            = $sourceRow['psono'];
//         $health_status    = $sourceRow['health_status'];
//         $cultivation_type = $sourceRow['cultivation_type']; 
//         $tray_id = $sourceRow['tray_id'];
//         $qtypertray = $sourceRow['qtypertray']; 

 
//         $sqlInsert = "
//             INSERT INTO krf_polyhouse_plant_details (
//                 planted_date,
//                 psono,
//                 stockorder_no,
//                 roottype,
//                 locationid,
//                 graftingdate,
//                 cultivation_type,
//                 projectid,
//                 barcode,
//                 productid,
//                 productname,
//                 tray_id,
//                 qtypertray,
//                 health_status,
//                 status,
//                 workingstatus
//             )
//             VALUES
//             (
//                 ?,
//                 ?,
//                 ?,
//                 'Grafted',
//                 ?,
//                 NOW(),
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?,
//                 ?, 
//                 'Grafted',
//                 'Grafted'
//             )
//         ";


//         $stmtInsert = $krfconn->prepare($sqlInsert);

//         if (!$stmtInsert) {

//             throw new Exception(
//                 "Insert query prepare failed: " .
//                 $krfconn->error
//             );
//         }


//         $generatedBarcodes = [];


//         for ($i = 0; $i < $newtrayqty; $i++) {

//             /*
//              * Each Root tray is paired with one Scion tray.
//              */

//             $rootRow  = $rootRows[$i];
//             $scionRow = $scionRows[$i];


//             /*
//              * Generated grafted plant quantity cannot exceed
//              * whichever paired source tray has fewer plants.
//              */
//             $qtypertray = min(  (int)$rootRow['qtypertray'],  (int)$scionRow['qtypertray'] );


//             /*
//              * UNIQUE BARCODE FOR EACH NEW TRAY
//              */
            
//                 $barcode = $barcodeBase
//                     . str_pad(
//                         $i + 1,
//                         4,
//                         '0',
//                         STR_PAD_LEFT
//                     );

 
//             //$tray_id = $barcode;


//             $stmtInsert->bind_param(
//                 "sssssissssss",
//                 $planted_date,
//                 $psono,
//                 $stockorder_no,
//                 $locationid,
//                 $cultivation_type,
//                 $projectid,
//                 $barcode,
//                 $productid,
//                 $productname,
//                 $tray_id,
//                 $qtypertray,
//                 $health_status
//             );


//             $stmtInsert->execute();


//             if ($stmtInsert->affected_rows !== 1) {

//                 throw new Exception(
//                     "Unable to generate new Grafted tray."
//                 );
//             }


//             $generatedBarcodes[] = $barcode;
//         }


//         $stmtInsert->close();



//         /* =========================================================
//            EVERYTHING SUCCESSFUL
//         ========================================================= */

//         $krfconn->commit();


//         echo json_encode([
//             'status' => 'success',

//             'message' =>
//                 $newtrayqty .
//                 ' Grafted tray(s) generated successfully.',

//             'data' => [
//                 'root_destroyed'  => $newtrayqty,
//                 'scion_destroyed' => $newtrayqty,
//                 'new_trays'       => $newtrayqty,
//                 'barcodes'        => $generatedBarcodes
//             ]
//         ]);

//         exit;

//     }
//     catch (Throwable $e) {

//         $krfconn->rollback();

//         echo json_encode([
//             'status'  => 'error',
//             'message' => $e->getMessage()
//         ]);

//         exit;
//     }
// }
function getpolyhouse($krfconn,$projectid,$locationid){ 
    $sql = "SELECT polyhouseid,polyhousename  FROM  krf_polyhouse_master WHERE  projectid = ?  AND locationid = ? ORDER BY  polyhousename  ";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("is",$projectid,$locationid);
    $stmt->execute();
    $result = $stmt->get_result();
    $polyhousedata = [];
    while ($row = $result->fetch_assoc()) {
        $polyhousedata[] = [
            'polyhouseid'   => $row['polyhouseid'],
            'polyhousename' => $row['polyhousename']
        ];
    }
    $stmt->close();
   return $polyhousedata;
    
}

function gettraycavity($krfconn ){
    $sqlTray = "
        SELECT
            t.tray_id,
            t.tray_code,
            t.tray_name,
            t.cavity_count,
            t.cavity_size,
            t.material,
            c.category_id,
            c.category_code,
            c.category_name
        FROM krf_tray_master t
        INNER JOIN krf_tray_category c
            ON t.category_id = c.category_id
        ORDER BY c.category_name,
            t.tray_name   ";

    $stmtTray = $krfconn->prepare($sqlTray);

    if (!$stmtTray) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Tray query prepare failed: ' .
                         $krfconn->error
        ]);

        exit;
    }

    $stmtTray->execute();

    $resultTray =  $stmtTray->get_result();

    while ($row = $resultTray->fetch_assoc()) {

        $trays[] = [
            'tray_id'       => $row['tray_id'],
            'tray_code'     => $row['tray_code'],
            'tray_name'     => $row['tray_name'],
            'cavity_count'  => $row['cavity_count'],
            'cavity_size'   => $row['cavity_size'],
            'material'      => $row['material'],
            'category_id'   => $row['category_id'],
            'category_code' => $row['category_code'],
            'category_name' => $row['category_name']
        ];
    }
    $stmtTray->close();
    return $trays;    
    
 }
