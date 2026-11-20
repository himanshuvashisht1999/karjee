<?php
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json; charset=utf-8');

$response = [
    'status'  => false,
    'message' => 'Unable to fetch data.',
    'data'    => []
];

try {

    require('../../connection.php');

    if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
        throw new Exception('Database connection not available.');
    }

    $action = $_REQUEST['action'] ?? '';

    switch ($action) {
        case 'fetchunveriedbed':
            $projectId  = $_POST['projectid'] ?? '';
            $locationid = $_POST['locationid'] ?? '';
            if ($projectId === '' || $locationid === '') {
                $response = [
                    'status'  => false,
                    'message' => 'Project, location, stock order, sowing date and lot are required.',
                    'data'    => []
                ];
                break;
            }           
            
            $sql = "
                SELECT
                    (
                        SELECT cl.client_name
                        FROM krf_production_stockorder stk

                        INNER JOIN krf_client_master cl
                            ON cl.projectid = stk.projectid
                           AND cl.client_id = stk.clientid

                        WHERE stk.projectid = x.projectid
                          AND stk.stockorder_no = x.stockorder_no

                        LIMIT 1
                    ) AS client_name,

                    DATE_FORMAT(
                        x.polyhouse_recdate, '%d-%m-%Y'
                    ) AS polyhouse_recdate,

                    x.stockorder_no,
                    x.sowinglotno_internal,
                    x.sowingdate_internal,

                    DATE_FORMAT(
                        x.sowingdate_internal, '%d-%m-%Y'
                    ) AS displydt,

                    x.unverified_qty,
                    x.verified_qty,
                    x.requested_qty,
                    x.total_qty

                FROM
                (
                    SELECT
                        d.projectid,
                        d.stockorder_no,
                        d.sowinglotno_internal,
                        d.sowingdate_internal,

                        MIN(d.created_at) AS polyhouse_recdate,

                        SUM(
                            CASE
                                WHEN d.bed_verify IS NULL
                                  OR TRIM(d.bed_verify) = ''
                                THEN 1
                                ELSE 0
                            END
                        ) AS unverified_qty,

                        COUNT(*) -
                        SUM(
                            CASE
                                WHEN d.bed_verify IS NULL
                                  OR TRIM(d.bed_verify) = ''
                                THEN 1
                                ELSE 0
                            END
                        ) AS verified_qty,

                        SUM(CASE WHEN d.qrcode_request IS NOT NULL THEN 1 ELSE 0 END) AS requested_qty,
                        COUNT(*) AS total_qty

                    FROM krf_polyhouse_plant_details d

                    WHERE d.projectid = ?
                      AND d.locationid = ?

                      AND d.status IN (
                          'Polyhouse',
                          'Healing',
                          'Hardening',
                          'Clip Stick Removal',
                          'Readytosale',
                          'OpenStock'
                      )

                    GROUP BY
                        d.projectid,
                        d.stockorder_no,
                        d.sowinglotno_internal,
                        d.sowingdate_internal

                    

                ) x

                ORDER BY
                    x.sowingdate_internal DESC,
                    x.sowinglotno_internal ASC,
                    x.stockorder_no ASC
            ";


            $stmt = $krfconn->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    'Prepare failed: ' . $krfconn->error
                );
            }

            $stmt->bind_param(
                "ss",
                $projectId,
                $locationid
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Execute failed: ' . $stmt->error
                );
            }

            $result = $stmt->get_result();

            if ($result === false) {
                throw new Exception('Unable to retrieve results.');
            }

            $data = [];

            while ($row = $result->fetch_assoc()) {

                // Keep original values for identity fields.
                // Do not convert dates or stock order numbers to integers.

                $row['unverified_qty'] =
                    (int)$row['unverified_qty'];

                $row['verified_qty'] =
                    (int)$row['verified_qty'];

                $row['total_qty'] =
                    (int)$row['total_qty'];

                $data[] = $row;
            }

            $stmt->close();

            $response = [
                'status'  => true,
                'message' => 'Bed verification data fetched successfully.',
                'data'    => $data
            ];

            break;

    case 'fetchverifiedbeddetails':
    case 'fetchunverifiedbeddetails':

            $projectId  = $_POST['projectid'] ?? '';
            $locationid = $_POST['locationid'] ?? '';
            $stockorder_no = $_POST['stockorder_no'] ?? '';
            $sowingdate = $_POST['sowingdate_internal'] ?? '';
            $sowingltno = $_POST['sowinglotno_internal'] ?? '';
 

            if ($projectId === '' || $locationid === '' || $stockorder_no === '' || $sowingdate === '' || $sowingltno === '') {
                $response = [
                    'status'  => false,
                    'message' => 'Project, location, stock order, sowing date and lot are required.',
                    'data'    => []
                ];
                break;
            }           
            $bedCondition = $action === 'fetchverifiedbeddetails'
                ? "(d.bed_verify IS NOT NULL AND TRIM(d.bed_verify) <> '')"
                : "(d.bed_verify IS NULL OR TRIM(d.bed_verify) = '')";
            $sql = "
                SELECT poly.polyhousename, d.polyhouseid, d.productname, d.productid, d.barcode, d.bedrackname, d.bed_no, d.qtypertray, d.qrcode_request, d.qrcode_requestedby, d.bed_verify   
                FROM krf_polyhouse_plant_details d  join krf_polyhouse_master poly on poly.polyhouseid =d.polyhouseid and poly.locationid=d.locationid  
                WHERE d.projectid = ?
                  AND d.locationid = ?
                  AND d.stockorder_no = ?
                  AND d.sowingdate_internal = ?
                  AND d.sowinglotno_internal = ?
                  AND d.status IN (
                      'Polyhouse',
                      'Healing',
                      'Hardening',
                      'Clip Stick Removal',
                      'Readytosale',
                      'OpenStock'
                  )
                   AND {$bedCondition} 

                ORDER BY
                    polyhousename asc ,
                     d.bedrackname, d.bed_no   
            ";


            $stmt = $krfconn->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    'Prepare failed: ' . $krfconn->error
                );
            }

            $stmt->bind_param(
                "sssss",
                $projectId,
                $locationid,
                $stockorder_no,
                $sowingdate,
                $sowingltno
            );

            if (!$stmt->execute()) {
                throw new Exception(
                    'Execute failed: ' . $stmt->error
                );
            }

            $result = $stmt->get_result();

            if ($result === false) {
                throw new Exception('Unable to retrieve results.');
            }

            $data = [];

            while ($row = $result->fetch_assoc()) { 

                $data[] = $row;
            }

            $stmt->close();

            $response = [
                'status'  => true,
                'message' => 'Bed verification data fetched successfully.',
                'data'    => $data
            ];

            break;

case 'savebedverification':
    $projectId  = $_POST['projectid'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $sowingdate = $_POST['sowingdate_internal'] ?? '';
    $sowinglotno = $_POST['sowinglotno_internal'] ?? '';
    $requestedby = trim($_POST['communicatedby'] ?? '');
    $items = json_decode($_POST['items'] ?? '[]', true);

    if (
        $projectId === '' ||
        $locationid === '' ||
        $stockorder_no === '' ||
        $sowingdate === '' ||
        $sowinglotno === '' ||
        $requestedby === '' ||
        !is_array($items) ||
        count($items) === 0
    ) {
        $response = [
            'status' => false,
            'message' => 'Required information or barcode selection is missing.',
            'data' => []
        ];
        break;
    }

    $barcodes = [];

    foreach ($items as $item) {

        $barcode = trim((string)($item['barcode'] ?? ''));

        if ($barcode === '') {
            throw new Exception('Invalid barcode in selection.');
        }

        if (isset($barcodes[$barcode])) {
            throw new Exception('Duplicate barcode selected: ' . $barcode);
        }

        $barcodes[$barcode] = true;
    }

    $krfconn->begin_transaction();

    try {

        $sql = "
            UPDATE krf_polyhouse_plant_details    SET
                qrcode_request = NOW(), qrcode_requestedby = ?
            WHERE projectid = ?
              AND locationid = ?
              AND stockorder_no = ?
              AND sowingdate_internal = ?
              AND sowinglotno_internal = ?
              AND barcode = ?
              AND qrcode_request IS NULL
              AND status IN (
                  'Polyhouse',
                  'Healing',
                  'Hardening',
                  'Clip Stick Removal',
                  'Readytosale',
                  'OpenStock'
              )";
        $stmt = $krfconn->prepare($sql);
        if (!$stmt) {
            throw new Exception($krfconn->error);
        }
        $updated = 0;
        foreach (array_keys($barcodes) as $barcode) {
            $stmt->bind_param(
                "sssssss",
                $requestedby,
                $projectId,
                $locationid,
                $stockorder_no,
                $sowingdate,
                $sowinglotno,
                $barcode
            );

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            if ($stmt->affected_rows !== 1) {
                throw new Exception(
                    'Barcode ' . $barcode .
                    ' is already requested, missing or not uniquely matched.'
                );
            }

            $updated++;
        }

        $stmt->close();

        $krfconn->commit();

        $response = [
            'status' => true,
            'message' => $updated . ' barcode(s) requested successfully.',
            'data' => [
                'updated_count' => $updated
            ]
        ];

    } catch (Throwable $e) {

        $krfconn->rollback();

        error_log('QR request save failed: ' . $e->getMessage());

        $response = [
            'status' => false,
            'message' => 'Unable to save QR request. No records were updated.',
            'data' => []
        ];
    }

    break;
    

        default:

            $response = [
                'status'  => false,
                'message' => 'Invalid action.',
                'data'    => []
            ];

            break;

    }


} catch (Throwable $e) {

    error_log(
        'barcode_verification.php Error: ' . $e->getMessage()
    );

    $response = [
        'status'  => false,
        'message' => 'Server error while fetching bed verification data.',
        'data'    => []
    ];
}

// Prevent accidental output from connection.php
// from corrupting the JSON response.
while (ob_get_level() > 0) {
    ob_end_clean();
}

echo json_encode(
    $response,
    JSON_INVALID_UTF8_SUBSTITUTE
);

exit;
