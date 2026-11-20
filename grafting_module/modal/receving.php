<?php
/** @var mysqli $krfconn */
error_reporting(E_ALL);
require('../../connection.php');
 
date_default_timezone_set('Asia/Kolkata');
$action = $_GET['action'];
if ($action == "barcodedgrafted") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $barcode   = trim($_POST['qrcode'] ?? '');
    if ($barcode === '') {
        echo json_encode([
            'status'  => 'error',
            'message' => 'QR Code is empty.'
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
    $scanQuery = "
        SELECT
            polyhouseplantid,
            stockorder_no,
            productid,
            productname, 
            locationid,
            barcode,
            roottype,
            qtypertray
        FROM krf_polyhouse_plant_details
        WHERE barcode = ?
          AND projectid = ?
          AND status = 'Grafted'
          AND workingstatus = 'Grafted'
          AND locationid IS NOT NULL
          AND locationid <> ''
        LIMIT 1
    ";

    $scanStmt = $krfconn->prepare($scanQuery);
    if (!$scanStmt) {
        echo json_encode([
            'status'  => 'error',
            'message' => 'Scan query prepare failed: ' . $krfconn->error
        ]);
        exit;
    }
    $scanStmt->bind_param(
        "si",
        $barcode,
        $projectid
    );
    $scanStmt->execute();
    $scanResult = $scanStmt->get_result();
    if ($scanResult->num_rows === 0) {
        $scanStmt->close();
        echo json_encode([
            'status'  => 'error',
            'message' => 'Tray does not exist or is not eligible for grafting.'
        ]);
        exit;
    }
    $scanRow = $scanResult->fetch_assoc();
    $scanStmt->close();
    $orderno   = trim($scanRow['stockorder_no'] ?? '');
    $productid = (int)($scanRow['productid'] ?? 0);
    $locationid = trim($scanRow['locationid'] ?? '');
    if (
        $orderno === '' ||
        $productid <= 0 ||
        $locationid === ''
    ) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'The scanned tray has incomplete batch information.'
        ]);
        exit;
    }


    /* =========================================================
       STEP 2 : GET ALL ELIGIBLE GRAFTED TRAYS
       SAME ORDER + PRODUCT + LOCATION
    ========================================================= */

    $lotQuery = "
        SELECT
            stockorder_no,
            productid,
            roottype,
            MAX(productname) AS productname,
            MAX(locationid) AS locationid, 
            COUNT(*) AS total_trays,

            COALESCE(
                SUM(CAST(qtypertray AS UNSIGNED)),
                0
            ) AS total_qty

        FROM krf_polyhouse_plant_details

        WHERE projectid = ?
          AND stockorder_no = ?
          AND productid = ?
          AND locationid = ?
          AND status = 'Grafted'
          AND workingstatus = 'Grafted'

        GROUP BY
            stockorder_no,
            productid,
            locationid

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
        "isis",
        $projectid,
        $orderno,
        $productid,
        $locationid
    );

    $lotStmt->execute();

    $lotResult = $lotStmt->get_result();

    $trayDetails = $lotResult->fetch_assoc();

    $lotStmt->close();


    if (!$trayDetails) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'No eligible grafted trays found.'
        ]);
        exit;
    }


    /* =========================================================
       STEP 3 : GET LOCATION NAME FROM HRM CONNECTION

       IMPORTANT:
       hrm_AttendanceLocationMaster MUST use $hrmconn
    ========================================================= */

    $locationname = '';

    $locationQuery = "
        SELECT
            locationname
        FROM hrm_AttendanceLocationMaster
        WHERE locationid = ?
          AND projectid = ?
        LIMIT 1
    ";

    $locationStmt = $hrmconn->prepare($locationQuery);

    if (!$locationStmt) {

        echo json_encode([
            'status'  => 'error',
            'message' => 'Location query prepare failed: ' . $hrmconn->error
        ]);
        exit;
    }

    $locationStmt->bind_param(
        "si",
        $locationid,
        $projectid
    );

    $locationStmt->execute();

    $locationResult = $locationStmt->get_result();

    if ($locationRow = $locationResult->fetch_assoc()) {
        $locationname = trim($locationRow['locationname'] ?? '');
    }

    $locationStmt->close();


    /* =========================================================
       STEP 4 : FINAL RESPONSE
    ========================================================= */

    $trayDetails['scanned_barcode'] = $barcode;

    $trayDetails['projectid'] =
        $projectid;

    $trayDetails['productid'] =
        (int)$trayDetails['productid'];

    $trayDetails['locationid'] =
        trim($trayDetails['locationid'] ?? '');

    $trayDetails['locationname'] =
        $locationname;

    $trayDetails['total_trays'] =
        (int)$trayDetails['total_trays'];

    $trayDetails['total_qty'] =
        (int)$trayDetails['total_qty'];
    $trayDetails['roottype'] = $trayDetails['roottype'];


    echo json_encode([
        'status' => 'success',
        'data'   => $trayDetails
    ], JSON_UNESCAPED_UNICODE);

    exit;
}  else if ($action == "UpdateToPolyhouse") {

    header('Content-Type: application/json');
    $projectid        = (int)($_POST['projectid'] ?? 0);
    $loginid          = trim($_POST['loginid'] ?? '');
    $polyhouseid      = (int)($_POST['polyhouseid'] ?? 0);
    $targetLocationid = trim($_POST['target_locationid'] ?? '');
    $bedrackname      = trim($_POST['bedrackname'] ?? '');
    $trayposition     = trim($_POST['placeofrow'] ?? '');
    $gfailurestatus   = trim($_POST['gfailurestatus'] ?? 'Healthy');
    $bedQty           = (int)($_POST['bedqty'] ?? 0);
    $stockorderNo     = trim($_POST['stockorder_no'] ?? '');
    $productid        = (int)($_POST['productid'] ?? 0);
    $barcode          = trim($_POST['Barcode'] ?? '');

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
            "message" => "Please select Polyhouse."
        ]);
        exit;
    }
    if ($targetLocationid === '') {
        echo json_encode([
            "status"  => "error",
            "message" => "Target location is required."
        ]);
        exit;
    }
    if ($stockorderNo === '') {
        echo json_encode([
            "status"  => "error",
            "message" => "Stock Order No. is required."
        ]);
        exit;
    }
    if ($productid <= 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Invalid product."
        ]);
        exit;
    }
    if ($bedQty <= 0) {
        echo json_encode([
            "status"  => "error",
            "message" => "Tray quantity must be greater than zero."
        ]);
        exit;
    }
    if ($loginid === '') {
        echo json_encode([
            "status"  => "error",
            "message" => "Login information is missing."
        ]);
        exit;
    }
    if ($barcode === '') {
        echo json_encode([
            "status"  => "error",
            "message" => "Scanned barcode is required."
        ]);
        exit;
    }  
    $targetStatus = 'Polyhouse';
    try {

        $krfconn->begin_transaction();
        $sql = "
            SELECT
                polyhouseid,
                locationid
            FROM krf_polyhouse_master
            WHERE polyhouseid = ?
              AND projectid = ?
            LIMIT 1";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception(
                "Polyhouse query prepare failed: " .
                $krfconn->error
            );
        }
        $stmt->bind_param(
            "ii",
            $polyhouseid,
            $projectid
        );

        $stmt->execute();
        $polyhouseData = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$polyhouseData) {
            throw new Exception(
                "Selected polyhouse does not exist."
            );
        }
        $polyhouseLocationid=trim((string)($polyhouseData['locationid'] ?? ''));
        if ($polyhouseLocationid === '') {
            throw new Exception(
                "Location is not configured for selected polyhouse."
            );
        }


        if ( (string)$polyhouseLocationid !==  (string)$targetLocationid ) {
            throw new Exception(
                "Selected polyhouse does not belong to selected target location."
            );
        } 
        $sql = "
            SELECT
                polyhouseplantid,
                stockorder_no,
                productid,
                locationid,
                barcode
            FROM krf_polyhouse_plant_details
            WHERE projectid = ?
              AND locationid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND status = 'Grafted'
              AND workingstatus = 'Grafted'
            LIMIT 1
        ";
 

        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception(
                "Barcode validation prepare failed: " .
                $krfconn->error
            );
        }

        $stmt->bind_param(
            "issi",
            $projectid,
            $targetLocationid,
            $stockorderNo,
            $productid
        );
        $stmt->execute();
        $scannedTray = $stmt
            ->get_result()
            ->fetch_assoc();
        $stmt->close();
        if (!$scannedTray) {
            throw new Exception(
                "Scanned Grafted tray is no longer available for allocation."
            );
        }

        $sourceLocationid =trim((string)$scannedTray['locationid']);
        if ($sourceLocationid === '') {
            throw new Exception(
                "Source location is missing in scanned tray."
            );
        }
        $sql = "
            SELECT
                polyhouseplantid,
                barcode
            FROM krf_polyhouse_plant_details
            WHERE projectid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND locationid = ?
              AND status = 'Grafted'
              AND workingstatus = 'Grafted'
              AND barcode IS NOT NULL
              AND TRIM(barcode) <> ''
            ORDER BY
                CASE WHEN barcode = ? THEN 0 ELSE 1 END,
                polyhouseplantid
            LIMIT ?
            FOR UPDATE
        ";

        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception(
                "Tray selection prepare failed: " .
                $krfconn->error
            );
        }
        $stmt->bind_param(
            "isissi",
            $projectid,
            $stockorderNo,
            $productid,
            $sourceLocationid,
            $barcode,
            $bedQty
        );
        $stmt->execute();
        $result = $stmt->get_result();
        $selectedIds      = [];
        $selectedBarcodes = [];
        while ($row = $result->fetch_assoc()) {
            $selectedIds[] =
                (int)$row['polyhouseplantid'];

            $selectedBarcodes[] =
                $row['barcode'];
        }

        $stmt->close();
        $selectedCount = count($selectedIds);
        if ($selectedCount !== $bedQty) {
            throw new Exception(
                "Only {$selectedCount} eligible Grafted tray(s) are currently available. " .
                "{$bedQty} tray(s) requested."
            );
        }
        $placeholders =
            implode(
                ',',
                array_fill(0, $selectedCount, '?')
            );


        $updateSql = "
            UPDATE krf_polyhouse_plant_details

            SET
                locationid    = ?,
                polyhouseid   = ?,

                tray_position = ?,
                bedrackname   = ?,
                bed_no        = ?,

                health_status = ?,

                status        = 'Polyhouse',
                workingstatus = 'Polyhouse',

                move_at       = CURDATE(),

                updated_at    = NOW(),
                updated_by    = ?

            WHERE polyhouseplantid IN ($placeholders)

              AND projectid = ?

              AND status = 'Grafted'
              AND workingstatus = 'Grafted'
        ";


        $updateStmt =
            $krfconn->prepare($updateSql);


        if (!$updateStmt) {
            throw new Exception(
                "Tray allocation prepare failed: " .
                $krfconn->error
            );
        }


        /*
         * Fixed parameters:
         *
         * targetLocationid s
         * polyhouseid      i
         * trayposition     s
         * bedrackname      s
         * bed_no           s
         * health_status    s
         * loginid          s
         *
         * selected IDs     i...
         *
         * projectid        i
         */

        $types = "sisssss"
               . str_repeat("i", $selectedCount)
               . "i";


        $params = [
            $targetLocationid,
            $polyhouseid,
            $trayposition,
            $bedrackname,
            $bedrackname,
            $gfailurestatus,
            $loginid
        ];


        foreach ($selectedIds as $id) {
            $params[] = $id;
        }


        $params[] = $projectid;


        $updateStmt->bind_param(
            $types,
            ...$params
        );


        $updateStmt->execute();

        $totalAllocated =
            $updateStmt->affected_rows;

        $updateStmt->close();



        if ($totalAllocated !== $selectedCount) {

            throw new Exception(
                "Only {$totalAllocated} of {$selectedCount} selected trays could be allocated."
            );
        }



        /* =========================================================
           STEP 5
           GET REMAINING GRAFTED TRAYS
        ========================================================= */

        $remainingSql = "
            SELECT
                COUNT(*) AS remaining_trays,

                COALESCE(
                    SUM(
                        CAST(qtypertray AS UNSIGNED)
                    ),
                    0
                ) AS remaining_qty

            FROM krf_polyhouse_plant_details

            WHERE projectid = ?
              AND stockorder_no = ?
              AND productid = ?
              AND locationid = ?
              AND status = 'Grafted'
              AND workingstatus = 'Grafted'
        ";


        $remainingStmt =
            $krfconn->prepare($remainingSql);


        if (!$remainingStmt) {
            throw new Exception(
                "Remaining tray query prepare failed: " .
                $krfconn->error
            );
        }


        $remainingStmt->bind_param(
            "isis",
            $projectid,
            $stockorderNo,
            $productid,
            $sourceLocationid
        );


        $remainingStmt->execute();

        $remainingData =
            $remainingStmt
                ->get_result()
                ->fetch_assoc();


        $remainingStmt->close();



        /* =========================================================
           COMMIT
        ========================================================= */

        $krfconn->commit();



        echo json_encode([
            "status"  => "success",

            "message" =>
                "{$totalAllocated} Grafted tray(s) allocated to Polyhouse successfully.",

            "data" => [

                "allocatedtrays" =>
                    $totalAllocated,

                "remaining_trays" =>
                    (int)($remainingData['remaining_trays'] ?? 0),

                "remaining_qty" =>
                    (int)($remainingData['remaining_qty'] ?? 0),

                "source_locationid" =>
                    $sourceLocationid,

                "target_locationid" =>
                    $targetLocationid,

                "polyhouseid" =>
                    $polyhouseid,

                "allocated_barcodes" =>
                    $selectedBarcodes
            ]

        ], JSON_UNESCAPED_UNICODE);

        exit;


    } catch (Throwable $e) {

        try {
            $krfconn->rollback();
        }
        catch (Throwable $ignored) {
        }


        echo json_encode([
            "status"  => "error",
            "message" => $e->getMessage()
        ]);

        exit;
    }
}

 
?>