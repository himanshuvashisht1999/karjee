<?php
date_default_timezone_set("Asia/Kolkata");
require('../../../connection.php');

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'];
$projectid = $_POST['projectid'];  
if (!$projectid  ) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request parameters.'
    ]);
    exit;
}
if($action == "getclientdata_inv"){  
    $sql = "SELECT clnt.client_id, clnt.client_name, ifnull(clnt.invoicing_address ,'' ) as invoicing_address, ifnull(clnt.phones ,'' ) as phones, ifnull(clnt.client_email  ,'' ) as client_email, ifnull(clnt.statename ,'' ) as statename, ifnull(clnt.districtname  ,'' ) as  districtname FROM krf_client_master clnt join  krf_sales_order_items item on item.clientid = clnt.client_id  where ifnull(item.status,'')  ='Invoiced'  and ifnull(clnt.client_id,'') <>''   and ifnull(clnt.client_name,'') <>'' and ifnull(clnt.client_id,'') <>''   and ifnull(clnt.client_name,'') <>'' and item.projectid =? group by clnt.client_id  order by  clnt.client_name "; 
    $returnarray = []; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("s", $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }

        echo json_encode([
            'status' => 'success', 
            'data' => $returnarray
        ]);
    }else{
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    }  
 
} else if($action == "invoiceno_pendingdespatch"){
    $clientid = $_POST['clientid'];   
    $sql="SELECT  item.sono, item.psono,invoiceno from   krf_sales_order_items item  where  ifnull(item.status,'')  ='Invoiced'   and item.projectid =?  and item.clientid =? group by item.invoiceno order by item.invoiceno";
    $returnarray = []; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $projectid,$clientid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }
        echo json_encode([
            'status' => 'success', 
            'data' => $returnarray
        ]);
    }else{
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    } 
} else if($action == "getproduct_fordespatch"){
    $clientid = $_POST['clientid'];  
    $psono = $_POST['psono'];
    $sono = $_POST['sono'];
    $invno = $_POST['invno'];
    $sql="SELECT   p.productname, item.sono, item.psono,item.stockorder_no, item.invoiceno,item.product_id, item.quantity, ifnull(SUM(stk.qtypertray),0) AS allocateqty   from   krf_sales_order_items item   
    join product_master p on p.productid = item.product_id  
    left join krf_readytosale_stocks stk on stk.invoiceno= item.invoiceno and stk.productid =item.product_id
      where  ifnull(item.status,'')  ='Invoiced'   and item.projectid =?  and item.clientid =? and item.sono = ? and  item.psono = ?  and item.invoiceno=? order by   p.productname"; 
    $returnarray = []; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("sssss", $projectid,$clientid,$sono,$psono,$invno);
    $stmt->execute();
    $result = $stmt->get_result();
    $cnt=1;
    if ($result && $result->num_rows > 0) { 
        while ($row = $result->fetch_assoc()) {
            $row['cntlabel'] = $cnt . ' of ' . $result->num_rows;                  
            $returnarray[] = $row;
            $cnt++; 
        }
        echo json_encode([
            'status' => 'success', 
            'data' => $returnarray
        ]);
    }else{
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    } 
} /*else if ($action === "getreadystock") { 
    $returnarray = [];   
    $productid     = $_POST['product_id']     ?? '';
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $psono         = $_POST['psono']         ?? '';
    $qty           = $_POST['qty']           ?? 0; 
    $invoiceno    = $_POST['invoiceno']           ?? ""; 

    $sql =  "SELECT stk.productname,  stk.psono, stk.stockorder_no,  SUM(stk.qtypertray) AS totalqty,  stk.polyhouseplantid,  IFNULL(GROUP_CONCAT(DISTINCT p.row_no), '') AS rowno, IFNULL(loc.locationname, '') AS locationname,  IFNULL(poly.polyhousename, '') AS polyhousename FROM krf_readytosale_stocks stk JOIN krf_polyhouse_plant_details p ON p.polyhouseplantid = stk.polyhouseplantid AND p.productid = stk.productid
        LEFT JOIN {$config['db_hrm']}.hrm_AttendanceLocationMaster loc
            ON loc.LocationId = p.locationid
        LEFT JOIN krf_polyhouse_master poly
            ON poly.polyhouseid = p.polyhouseid
        WHERE IFNULL(stk.invoiceno, '') = ''  AND stk.projectid =?  AND stk.stockorder_no = ?  AND stk.productid = ?  GROUP BY stk.productid, stk.stockorder_no, stk.psono
        ORDER BY
        CASE
            WHEN stk.psono = ? THEN 1
            ELSE 2
        END, stk.productname "; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param( "ssss",
        $projectid,
        $stockorder_no, 
        $productid,
        $psono   
    );

    $stmt->execute(); 
    $result = $stmt->get_result();
    
    if ($result && $result->num_rows > 0) {
        while ($r = $result->fetch_assoc()) { 
            $returnarray[] = $r; 
        }
    }
    $allocatedarray=allocateqty($krfconn, $invoiceno, $productid);
    $truckarray=truckdetails($krfconn, $invoiceno ,$projectid);

    echo json_encode([
        'status' => empty($returnarray) ? 'nodata' : 'success',
        'data'   => $returnarray,
        'allocatedarray'   => $allocatedarray,
        'truckdetails'   => $truckarray,
    ]);
}*/ 
else if ($action === "getreadystock") {
    header('Content-Type: application/json; charset=utf-8');
    try {
        $returnarray = [];
        $productid     = trim($_POST['product_id'] ?? '');
        $stockorder_no = trim($_POST['stockorder_no'] ?? '');
        $psono         = trim($_POST['psono'] ?? '');
        $qty           = (int)($_POST['qty'] ?? 0);
        $invoiceno     = trim($_POST['invoiceno'] ?? '');

        if (
            empty($projectid) ||
            $productid === '' ||
            $stockorder_no === ''
        ) {
            throw new Exception(
                "Project ID, product ID and stock order number are required."
            );
        }

        /*
         * First aggregate ready stock separately.
         * This prevents qtypertray from being duplicated when there
         * are multiple plant-detail rows.
         */
        $sql = "
            SELECT
                stk.productname,
                stk.psono,
                stk.stockorder_no,
                stk.totalqty,
                stk.polyhouseplantid,
                plant.locationid,
                plant.rowno,
                IFNULL(poly.polyhousename, '') AS polyhousename
            FROM
            (
                SELECT
                    productid,
                    productname,
                    psono,
                    stockorder_no,
                    polyhouseplantid,
                    SUM(qtypertray) AS totalqty
                FROM krf_readytosale_stocks
                WHERE IFNULL(invoiceno, '') = ''
                  AND projectid = ?
                  AND stockorder_no = ?
                  AND productid = ?
                GROUP BY
                    productid,
                    productname,
                    psono,
                    stockorder_no,
                    polyhouseplantid
            ) AS stk

            INNER JOIN
            (
                SELECT
                    polyhouseplantid,
                    productid,
                    MAX(locationid) AS locationid,
                    MAX(polyhouseid) AS polyhouseid,
                    IFNULL(
                        GROUP_CONCAT(
                            DISTINCT row_no
                            ORDER BY row_no
                            SEPARATOR ', '
                        ),
                        ''
                    ) AS rowno
                FROM krf_polyhouse_plant_details
                GROUP BY
                    polyhouseplantid,
                    productid
            ) AS plant
                ON plant.polyhouseplantid = stk.polyhouseplantid
               AND plant.productid = stk.productid

            LEFT JOIN krf_polyhouse_master AS poly
                ON poly.polyhouseid = plant.polyhouseid

            ORDER BY
                CASE
                    WHEN stk.psono = ? THEN 1
                    ELSE 2
                END,
                stk.productname
        ";

        $stmt = $krfconn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Ready stock query preparation failed: " . $krfconn->error
            );
        }

        $stmt->bind_param(
            "isss",
            $projectid,
            $stockorder_no,
            $productid,
            $psono
        );

        if (!$stmt->execute()) {
            throw new Exception(
                "Ready stock query execution failed: " . $stmt->error
            );
        }

        $result = $stmt->get_result();

        $locationIds = [];

        while ($row = $result->fetch_assoc()) {
            $locationId = trim((string)($row['locationid'] ?? ''));

            if ($locationId !== '') {
                $locationIds[$locationId] = $locationId;
            }

            // It will be filled using the HRM connection.
            $row['locationname'] = '';

            $returnarray[] = $row;
        }

        $stmt->close();

        /*
         * Fetch location names from the HRM database connection.
         */
        $locationNames = [];

        if (!empty($locationIds)) {
            $locationIds = array_values($locationIds);

            $placeholders = implode(
                ',',
                array_fill(0, count($locationIds), '?')
            );

            $hrmSql = "
                SELECT
                    LocationId,
                    locationname
                FROM hrm_AttendanceLocationMaster
                WHERE LocationId IN ($placeholders)
            ";

            $hrmStmt = $hrmconn->prepare($hrmSql);

            if (!$hrmStmt) {
                throw new Exception(
                    "Location query preparation failed: " .
                    $hrmconn->error
                );
            }

            $locationTypes = str_repeat('s', count($locationIds));

            $hrmStmt->bind_param(
                $locationTypes,
                ...$locationIds
            );

            if (!$hrmStmt->execute()) {
                throw new Exception(
                    "Location query execution failed: " .
                    $hrmStmt->error
                );
            }

            $hrmResult = $hrmStmt->get_result();

            while ($location = $hrmResult->fetch_assoc()) {
                $locationId = trim(
                    (string)$location['LocationId']
                );

                $locationNames[$locationId] =
                    $location['locationname'] ?? '';
            }

            $hrmStmt->close();
        }

        /*
         * Merge location names with ready-stock records.
         */
        foreach ($returnarray as &$row) {
            $locationId = trim(
                (string)($row['locationid'] ?? '')
            );

            $row['locationname'] =
                $locationNames[$locationId] ?? '';

            // Remove this if locationid is also required in JavaScript.
            unset($row['locationid']);
        }

        unset($row);

        /*
         * These functions also use the general database connection.
         */
        $allocatedarray = allocateqty(
            $krfconn,
            $invoiceno,
            $productid
        );

        $truckarray = truckdetails(
            $krfconn,
            $invoiceno,
            $projectid
        );

        echo json_encode([
            'status'         => empty($returnarray)
                ? 'nodata'
                : 'success',
            'data'           => $returnarray,
            'allocatedarray' => $allocatedarray,
            'truckdetails'   => $truckarray
        ], JSON_UNESCAPED_UNICODE);

    } catch (Throwable $e) {
        error_log("getreadystock error: " . $e->getMessage());

        echo json_encode([
            'status'  => 'error',
            'message' => $e->getMessage(),
            'data'    => [],
            'allocatedarray' => [],
            'truckdetails'   => []
        ], JSON_UNESCAPED_UNICODE);
    }

    exit;
}



else if ($action === "getbarcodedetails") { 
    $returnarray = [];   
    $barcodeno     = $_POST['barcode']     ?? ''; 
    $sql =  "SELECT stk.id,stk.productname,  stk.psono, stk.stockorder_no,  stk.qtypertray ,  stk.polyhouseplantid, stk.productid  FROM  krf_readytosale_stocks stk 
    JOIN krf_polyhouse_plant_details p  ON p.polyhouseplantid = stk.polyhouseplantid AND p.productid = stk.productid 
    WHERE IFNULL(stk.invoiceno, '') = '' AND stk.projectid =?  AND p.barcode = ?   "; 
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param( "ss", $projectid,  $barcodeno);
    $stmt->execute(); 
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $r = $result->fetch_assoc();
        $returnarray[] = $r;        
    }
    echo json_encode([
        'status' => empty($returnarray) ? 'nodata' : 'success',
        'data'   => $returnarray
    ]);
}
function allocateqty($krfconn, $invoiceno, $productid) {
    $data = [];
    $sql = "
        SELECT stk.productid, stk.stockorder_no, stk.id, stk.qtypertray, stk.productname,  
            p.barcode
        FROM krf_readytosale_stocks stk
        JOIN krf_polyhouse_plant_details p
            ON p.polyhouseplantid = stk.polyhouseplantid
           AND p.productid = stk.productid
        WHERE stk.invoiceno = ?
          AND stk.productid = ? ";

    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $invoiceno, $productid);
    $stmt->execute();

    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $data[] = $r;
    }

    return $data;
}
function truckdetails($krfconn, $invoiceno ,$projectid) {
    $data = [];
    $sql = "
        SELECT truck_no, driver_name, driver_mobile, invoiceno, gatepasspath
        FROM krf_gatepass_truckdetail  
        WHERE invoiceno = ? AND projectid = ?  and ifnull(status,'') =''";

    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $invoiceno, $projectid);
    $stmt->execute();

    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $data[] = $r;
    }

    return $data;
}


