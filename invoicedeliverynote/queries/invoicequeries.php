<?php
date_default_timezone_set("Asia/Kolkata");
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 0);
require('../../connection.php');

header('Content-Type: application/json; charset=utf-8');
$action = $_GET['action'];
$projectid = $_POST['projectid'];
if (!$projectid) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request parameters.'
    ]);
    exit;
}
if ($action == "getclientdata") {
    $sql = "SELECT clnt.client_id, clnt.client_name, ifnull(clnt.invoicing_address ,'' ) as invoicing_address, ifnull(clnt.phones ,'' ) as phones, ifnull(clnt.client_email  ,'' ) as client_email, ifnull(clnt.statename ,'' ) as statename, ifnull(clnt.districtname  ,'' ) as  districtname FROM krf_client_master clnt join  krf_sales_order_items item on item.clientid = clnt.client_id  where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' ) and ifnull(clnt.client_id,'') <>''   and ifnull(clnt.client_name,'') <>'' and ifnull(clnt.client_id,'') <>''   and ifnull(clnt.client_name,'') <>'' and item.projectid =? group by clnt.client_id  order by  clnt.client_name ";
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
    } else {
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    }
}else if($action =="getprovisional_freightcharges"){
    $projectid = $_POST['projectid'];
    $clientid = $_POST['clientid'];
    $psonos = isset($_POST['psonos']) ? json_decode($_POST['psonos'], true) : [];

    if (empty($psonos)) {
        echo json_encode(['status' => 'nodata', 'data' => []]);
        exit;
    }

    $inClause = implode(',', array_fill(0, count($psonos), '?'));
    $types = "ss" . str_repeat('s', count($psonos));
    $params = array_merge([$projectid, $clientid], $psonos);

    $sql = "SELECT DISTINCT f.projectid, f.psono, f.clientid, f.freight_amount, f.freight_discountpercent, f.payable_freightamount, f.status, f.freightinvoicepath,
                   v.vehicle_id, v.vehicle_no, v.driver_name, v.driver_mobileno, v.deliverydate
            FROM krf_freightcharges f 
            JOIN krf_provisional_sales_order p ON f.psono = p.psono AND f.clientid = p.clientid AND f.projectid = p.projectid 
            LEFT JOIN krf_vehicle_travel_history v ON v.psono = f.psono AND v.projectid = f.projectid
            WHERE p.status = 'Confirmed' 
            AND f.projectid = ? AND f.clientid = ? AND f.psono IN ($inClause)
            GROUP BY f.psono";
    
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $returnarray = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }
        echo json_encode([
            'status' => 'success',
            'data' => $returnarray
        ]);
    } else {
        echo json_encode([
            'status' => 'nodata',
            'data' => []
        ]);
    }
} else if ($action == "generate_freight_invoice") {
    $freightData = json_decode($_POST['freightData'], true);
    $clientid = $_POST['clientid'];
    $projectid = $_POST['projectid'];
    $companyname = $_POST['companyname'] ?? 'Karjee Farms';
    $drivername = $_POST['drivername'] ?? '';
    $vehicleno = $_POST['vehicleno'] ?? '';
    $vid = $_POST['vehicleid'] ?? '';
    $dcontact = $_POST['drivercontactno'] ?? '';
    $ddate = $_POST['deliverydate'] ?? '';
    
    if (!$freightData || !is_array($freightData)) {
        echo json_encode(['status' => 'error', 'message' => 'No freight data provided.']);
        exit;
    }

    require_once('../save/createfreightpdf.php');
    
    // Get Client Info for PDF
    $clientData = [];
    $cStmt = $krfconn->prepare("SELECT clientname, mobileno, emailid, delivery_address FROM krf_provisional_sales_order WHERE clientid = ? LIMIT 1");
    $cStmt->bind_param("s", $clientid);
    $cStmt->execute();
    $cRes = $cStmt->get_result();
    if ($row = $cRes->fetch_assoc()) {
        $clientData = $row;
    }
    $cStmt->close();

    $results = [];
    $successCount = 0;

    foreach ($freightData as $item) {
        $psono = $item['psono'];
        $amount = (float)$item['amount'];
        $discount = (float)$item['discount'];
        $payable = (float)$item['payable'];
        
        $pdfPath = generate_freight_invoice_pdf($krfconn, $projectid, $clientid, $psono, $amount, $discount, $payable, $clientData, $companyname, $drivername, $vehicleno);
        
        // Clean path for DB (relative to site root)
        $dbPath = str_replace("../../", "", $pdfPath);

        // 1. Update/Insert krf_freightcharges
        $checkStmt = $krfconn->prepare("SELECT id FROM krf_freightcharges WHERE psono = ? AND clientid = ? AND projectid = ?");
        $checkStmt->bind_param("sss", $psono, $clientid, $projectid);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $exists = ($checkRes && $checkRes->num_rows > 0);
        $checkStmt->close();

        if ($exists) {
            $stmt = $krfconn->prepare("UPDATE krf_freightcharges SET freight_discountpercent = ?, payable_freightamount = ?, freightinvoicepath = ?, status = 'Invoiced' WHERE psono = ? AND clientid = ? AND projectid = ?");
            $stmt->bind_param("ddssss", $discount, $payable, $dbPath, $psono, $clientid, $projectid);
        } else {
            $stmt = $krfconn->prepare("INSERT INTO krf_freightcharges (projectid, clientid, psono, freight_amount, freight_discountpercent, payable_freightamount, freightinvoicepath, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Invoiced')");
            $stmt->bind_param("sssdddss", $projectid, $clientid, $psono, $amount, $discount, $payable, $dbPath);
        }
        $stmt->execute();
        $stmt->close();

        // 2. Update/Insert krf_vehicle_travel_history (Sync transport details per item)
        $delivery_dt = !empty($ddate) ? date('Y-m-d', strtotime($ddate)) : date('Y-m-d');
        
        // Fetch all items for this PSONO to ensure full history synchronization
        $itemStmt = $krfconn->prepare("SELECT product_id, stockorder_no, quantity FROM krf_sales_order_items WHERE psono = ? AND projectid = ?");
        if (!$itemStmt) {
            continue; // Or handle error
        }
        $itemStmt->bind_param("ss", $psono, $projectid);
        $itemStmt->execute();
        $itemRes = $itemStmt->get_result();
        
        while ($row = $itemRes->fetch_assoc()) {
            $pid = $row['product_id'];
            $stk = $row['stockorder_no'];
            $qty = $row['quantity'];

            // Check if travel history exists for this specific item
            $checkTravel = $krfconn->prepare("SELECT id FROM krf_vehicle_travel_history WHERE stockorder_no = ? AND productid = ?");
            $checkTravel->bind_param("si", $stk, $pid);
            $checkTravel->execute();
            $travelExists = $checkTravel->get_result()->fetch_assoc();
            $checkTravel->close();

            if ($travelExists) {
                // Update existing operational record
                $tStmt = $krfconn->prepare("UPDATE krf_vehicle_travel_history SET freight_amount = ?, vehicle_no = ?, vehicle_id = ?, driver_name = ?, driver_mobileno = ?, deliverydate = ?, psono = ?, send_totalplantcount = ? WHERE id = ?");
                $tStmt->bind_param("dsissssii", $payable, $vehicleno, $vid, $drivername, $dcontact, $delivery_dt, $psono, $qty, $travelExists['id']);
            } else {
                // Insert new operational record (Initial Allotment)
                $tStmt = $krfconn->prepare("INSERT INTO krf_vehicle_travel_history (projectid, stockorder_no, psono, productid, vehicle_id, vehicle_no, driver_name, driver_mobileno, freight_amount, deliverydate, send_totalplantcount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $tStmt->bind_param("issiisssdsi", $projectid, $stk, $psono, $pid, $vid, $vehicleno, $drivername, $dcontact, $payable, $delivery_dt, $qty);
            }
            $tStmt->execute();
            $tStmt->close();
        }
        $itemStmt->close();

        $successCount++;
        $results[] = ['psono' => $psono, 'path' => $dbPath];
    }

    echo json_encode([
        'status' => 'success',
        'message' => "$successCount freight invoices generated successfully.",
        'details' => $results
    ]);
    exit;
} else if ($action == "get_pending_orders_grid") {
    $clientid = $_POST['clientid'];
    $projectid = $_POST['projectid'];

    $sql = "SELECT k.id, k.psono, k.sono, k.product_id, p.productname, k.stockorder_no, 
                       date_format(k.deliverydate, '%d-%m-%Y') AS deliverydate, 
                       k.quantity as totalqty, 
                       (k.quantity * k.rate) AS ordervalue,
                       IF((SELECT 1 FROM krf_vehicle_travel_history v WHERE v.stockorder_no = k.stockorder_no AND v.productid = k.product_id LIMIT 1) = 1, 1, 0) as has_allocation,
                       IF((SELECT 1 FROM krf_invoice_gatepass g WHERE g.psono = k.psono AND g.productid = k.product_id LIMIT 1) = 1, 1, 0) as has_gatepass,
                       IF((SELECT 1 FROM krf_invoice_deliverynote d WHERE d.psono = k.psono AND d.productid = k.product_id LIMIT 1) = 1, 1, 0) as has_deliverynote,
                       IF((SELECT 1 FROM krf_freightcharges f WHERE f.psono = k.psono LIMIT 1) = 1, 1, 0) as has_freight_invoice
                FROM krf_sales_order_items k
                JOIN product_master p ON p.productid = k.product_id
                WHERE k.projectid=? AND k.clientid=? AND (ifnull(k.status,'') ='Pending' or ifnull(k.status,'') ='') 
                ORDER BY k.deliverydate desc, k.psono, p.productname";
    $returnarray = [];
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $projectid, $clientid);
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
    } else {
        echo json_encode([
            'status' => 'nodata',
            'data' => []
        ]);
    }
} else if ($action == "fetch_order_products_batch") {
    $clientid = $_POST['clientid'];
    $psonos = isset($_POST['psonos']) ? json_decode($_POST['psonos'], true) : [];
    $itemIds = isset($_POST['itemIds']) ? json_decode($_POST['itemIds'], true) : [];

    if (empty($itemIds)) {
        echo json_encode(['status' => 'nodata', 'data' => []]);
        exit;
    }

    $inClause = implode(',', array_fill(0, count($itemIds), '?'));
    $types = "ss" . str_repeat('i', count($itemIds));
    $params = array_merge([$projectid, $clientid], $itemIds);

    $sql = "SELECT distinct ifnull(addl.deliveryaddress,'') as deliveryaddress, ifnull(addl.vehicleno,'') as vehicleno,ifnull(addl.drivername,'') as drivername,ifnull(addl.drivercontactno,'') as drivercontactno,ifnull(fr.freight_amount,'') as freight_amount,ifnull(fr.freight_discountpercent,'') as freight_discountpercent,ifnull(fr.payable_freightamount,'') as payable_freightamount ,
    IFNULL(av.availableqty, '') AS availableqty, p.productname, item.sono, item.psono,item.stockorder_no, item.product_id, item.quantity , item.rate, item.discount_percent , date_format(item.deliverydate,'%d-%m-%Y') as deliverydate from krf_sales_order_items item join product_master p on p.productid = item.product_id 
        LEFT JOIN ( 
            SELECT productid, stockorder_no, SUM(qtypertray) AS availableqty FROM krf_readytosale_stocks GROUP BY productid, stockorder_no ) av 
        ON av.productid = item.product_id
       AND av.stockorder_no = item.stockorder_no left join krf_freightcharges fr on fr.clientid=item.clientid and fr.psono=item.psono left join krf_deliveryaddress_location addl on addl.clientid=item.clientid and addl.psono=item.psono
      where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' )  and item.projectid =?  and item.clientid =? and item.id IN ($inClause) order by item.deliverydate, p.productname";

    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $returnarray = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $returnarray[] = $row;
        }
        echo json_encode([
            'status' => 'success',
            'data' => $returnarray
        ]);
    } else {
        echo json_encode([
            'status' => 'nodata',
            'data' => []
        ]);
    }
} else if ($action == "getorderno_forinv") {
    $clientid = $_POST['clientid'];
    //$sql="SELECT  main.sono, item.psono from  krf_sales_order main join  krf_sales_order_items item on item.psono=main.psono and item.clientid=main.clientid   where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' )  and item.projectid =?  and item.clientid =? group by main.sono, item.psono order by item.deliverydate";
    $sql = "SELECT  item.sono, item.psono from   krf_sales_order_items item  where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' )  and item.projectid =?  and item.clientid =? group by item.sono, item.psono order by item.deliverydate";
    $returnarray = [];
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ss", $projectid, $clientid);
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
    } else {
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    }
} else if ($action == "getproduct_forinv") {
    $clientid = $_POST['clientid'];
    $psono = $_POST['psono'];
    $sono = $_POST['sono'];
    $sql = "SELECT distinct ifnull(addl.deliveryaddress,'') as deliveryaddress, ifnull(addl.vehicleno,'') as vehicleno,ifnull(addl.drivername,'') as drivername,ifnull(addl.drivercontactno,'') as drivercontactno,ifnull(fr.freight_amount,'') as freight_amount,ifnull(fr.freight_discountpercent,'') as freight_discountpercent,ifnull(fr.payable_freightamount,'') as payable_freightamount ,
    IFNULL(av.availableqty, '') AS availableqty, p.productname, item.sono, item.psono,item.stockorder_no, item.product_id, item.quantity , item.rate, item.discount_percent , date_format(item.deliverydate,'%d-%m-%Y') as deliverydate from   krf_sales_order_items item   join product_master p on p.productid = item.product_id 
        LEFT JOIN ( 
            SELECT  productid, stockorder_no,  SUM(qtypertray) AS availableqty FROM krf_readytosale_stocks  GROUP BY productid, stockorder_no ) av 
        ON av.productid = item.product_id
       AND av.stockorder_no = item.stockorder_no left join krf_freightcharges fr on fr.clientid=item.clientid and fr.psono=item.psono left join krf_deliveryaddress_location addl on addl.clientid=item.clientid and addl.psono=item.psono
      where (ifnull(item.status,'')  ='Pending' or ifnull(item.status,'')  ='' )  and item.projectid =?  and item.clientid =? and item.sono = ? and  item.psono = ? order by item.deliverydate , p.productname";
    $returnarray = [];
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("ssss", $projectid, $clientid, $sono, $psono);
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
    } else {
        echo json_encode([
            'status' => 'nodata',
            'data' => $returnarray
        ]);

    }
} else if ($action == "get_vehicle_master") {
    $sql = "SELECT vehicleid, vehicleno, vehiclename, traycapacity, plantscapacity FROM krf_vehicle_master WHERE projectid = ? AND status = 'Active'";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("s", $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) { $data[] = $row; }
    echo json_encode(['status' => 'success', 'data' => $data]);
} else if ($action == "get_driver_master") {
    $sql = "SELECT driverid, drivername, drivermobileno FROM krf_vehicle_driver_master WHERE projectid = ? AND status = 'Active'";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("s", $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = [];
    while ($row = $result->fetch_assoc()) { $data[] = $row; }
    echo json_encode(['status' => 'success', 'data' => $data]);
} else if ($action == "save_new_driver") {
    $name = $_POST['drivername'];
    $phone = $_POST['drivermobileno'];
    $sql = "INSERT INTO krf_vehicle_driver_master (projectid, drivername, drivermobileno) VALUES (?, ?, ?)";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("sss", $projectid, $name, $phone);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'driverid' => $krfconn->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $krfconn->error]);
    }
} else if ($action == "check_vehicle_occupancy") {
    $vid = $_POST['vehicleid'];
    $date = date('Y-m-d', strtotime($_POST['date']));
    
    // Get max capacity
    $sql_m = "SELECT plantscapacity, traycapacity FROM krf_vehicle_master WHERE vehicleid = ?";
    $stmt_m = $krfconn->prepare($sql_m);
    $stmt_m->bind_param("i", $vid);
    $stmt_m->execute();
    $max = $stmt_m->get_result()->fetch_assoc();
    
    // Get used capacity from travel history
    // We assume 'send_totalplantcount' is the plants metric and 'send_traycount' is trays
    $sql_u = "SELECT SUM(send_totalplantcount) as used_plants, SUM(send_traycount) as used_trays 
              FROM krf_vehicle_travel_history 
              WHERE vehicle_id = ? AND deliverydate = ?";
    $stmt_u = $krfconn->prepare($sql_u);
    $stmt_u->bind_param("is", $vid, $date);
    $stmt_u->execute();
    $used = $stmt_u->get_result()->fetch_assoc();
    
    echo json_encode([
        'status' => 'success',
        'max_plants' => (int)$max['plantscapacity'],
        'used_plants' => (int)$used['used_plants'],
        'max_trays' => (int)$max['traycapacity'],
        'used_trays' => (int)$used['used_trays']
    ]);
}
