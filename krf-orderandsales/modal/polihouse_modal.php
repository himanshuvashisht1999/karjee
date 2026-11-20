<?php
/** @var mysqli $krfconn */

error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/product_functions.php");
include("../functions/labour_functions.php");
include("../functions/orders_functions.php");
include('../functions/vehicle_functions.php');
date_default_timezone_set('Asia/Kolkata');
$action = $_GET['action'];
// =========================================================
// ACTION: saveMovementLocation — Add a new Movement Location
// =========================================================
if ($action == "saveMovementLocation") {
    try {
        $companyid       = trim($_POST['companyid']       ?? '');
        $subcompanyid    = trim($_POST['subcompanyid']    ?? '');
        $projectid       = trim($_POST['projectid']       ?? '');
        $locationname    = trim($_POST['locationname']    ?? '');
        $locationaddress = trim($_POST['locationaddress'] ?? '');
        $comments        = trim($_POST['comments']        ?? '');
        $shortname       = trim($_POST['shortname']       ?? '');
        if ($locationname === '') {
            echo json_encode(["status" => "error", "message" => "Location name is required."]);
            exit;
        }
        $locationId = uniqid("LOC");
        $stmt = $hrmconn->prepare(
            "INSERT INTO hrm_AttendanceLocationMaster
             (LocationId, CompanyID, SubCompanyID, ProjectID, LocationName, LocationAddress, Comments, shortname)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) throw new Exception("Prepare failed: " . $hrmconn->error);
        $stmt->bind_param("ssssssss", $locationId, $companyid, $subcompanyid, $projectid, $locationname, $locationaddress, $comments, $shortname);
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Location added successfully.", "locationId" => $locationId, "LocationName" => $locationname]);
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
}

if ($action == "saveupdatePolihouseDetails") {
    try {
        $projectid = $_POST['ProjectID'] ?? '';
        $polyhouseid = $_POST['polyhouseid'] ?? '';
        $polyhousename = trim($_POST['polyhouse_name'] ?? '');
        $locationid = trim($_POST['polyhouselocationid'] ?? '');
        $physicallocation = trim($_POST['physicallocation'] ?? '');
        $area_sqft = $_POST['area_sqft'] ?? null;
        $type = $_POST['type'] ?? 'Poly Film';
        $capacity = $_POST['capacity'] ?? null;
        $status = $_POST['status'] ?? 'Active';
        $description = trim($_POST['description'] ?? '');
        $created_by = $_POST['loginid'] ?? '';
        $no_of_beds = $_POST['total_bed_count'] ?? '0';
        $no_of_racks = $_POST['total_rack_count'] ?? '0';
        $structure_type = "Bed";
        if ($no_of_beds > 0 && $no_of_racks > 0) {
            $structure_type = "Bed & Rack";
        } else if ($no_of_beds > 0 && ($no_of_racks == 0 || $no_of_racks == "" || $no_of_racks == null)) {
            $structure_type = "Bed";
        } else if ($no_of_racks > 0 && ($no_of_beds == 0 || $no_of_beds == "" || $no_of_beds == null)) {
            $structure_type = "Rack";
        }
        if ($polyhousename == '' || $projectid == '') {
            echo json_encode(["status" => "warning", "message" => "Polyhouse name and project are required."]);
            exit;
        }

        // Check if editing or adding new
        if ($polyhouseid != '') {
            $sql = "UPDATE krf_polyhouse_master 
                  SET polyhousename=?, locationid=?, physicallocation=?, area_sqft=?, type=?, capacity=?, 
                      status=?, description=?, no_of_beds =?, no_of_racks=?, structure_type=?, updated_at=NOW() 
                  WHERE polyhouseid=?";
            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param("sssdsdssiisi", $polyhousename, $locationid, $physicallocation, $area_sqft, $type, $capacity, $status, $description, $no_of_beds, $no_of_racks, $structure_type, $polyhouseid);
            $stmt->execute();

            echo json_encode(["status" => "success", "message" => "Polyhouse details updated successfully."]);
        } else {
            $sql = "INSERT INTO krf_polyhouse_master 
                  (projectid, locationid, polyhousename, physicallocation, area_sqft, type, capacity, status, description, no_of_beds, no_of_racks, structure_type, created_by) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $krfconn->prepare($sql);
            $stmt->bind_param("ssssdsdssiiss", $projectid, $locationid, $polyhousename, $physicallocation, $area_sqft, $type, $capacity, $status, $description, $no_of_beds, $no_of_racks, $structure_type, $created_by);
            $stmt->execute();

            echo json_encode(["status" => "success", "message" => "Polyhouse added successfully."]);
        }
    } catch (Exception $e) {
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else if ($action == "saveupdatePolyhouseDetailsformasters") {
    try {

        /* ===============================
           🔐 INPUTS
        =============================== */
        $projectid = $_POST['ProjectID'] ?? '';
        $polyhouseid = $_POST['polyhouseid'] ?? '';
        $polyhousename = trim($_POST['polyhouse_name'] ?? '');
        $locationid = trim($_POST['polyhouselocationid'] ?? '');
        $physicallocation = trim($_POST['physicallocation'] ?? '');
        $type = $_POST['StructureType'] ?? 'PolyHouse';
        $description = trim($_POST['description'] ?? '');
        $created_by = $_POST['loginid'] ?? '';
        $status = 'Active';

        /* ===============================
           🧱 BED / RACK DETAILS & AGGREGATION
        =============================== */
        $bedDetailsArr = json_decode($_POST['bed_details'] ?? '[]', true);
        $beddetails = json_encode($bedDetailsArr, JSON_UNESCAPED_UNICODE);

        $aggregatedTrayCapacity = [];
        $totalCapacity = 0;
        if (is_array($bedDetailsArr)) {
            foreach ($bedDetailsArr as $bed) {
                $trayCaps = $bed['traycapacity'] ?? [];
                if (is_array($trayCaps)) {
                    foreach ($trayCaps as $tid => $trayInfo) {
                        $cap = (int) ($trayInfo['capacity'] ?? 0);
                        $aggregatedTrayCapacity[$tid] = ($aggregatedTrayCapacity[$tid] ?? 0) + $cap;
                        $totalCapacity += $cap;
                    }
                }
            }
        }
        $traycapacity_json = json_encode($aggregatedTrayCapacity);

        /* ===============================
           🏗 STRUCTURE TYPE
        =============================== */
        $structure_type = '';
        if ($type === "PolyHouse") {
            $structure_type = "Bed";
        } elseif ($type === "CoolingChamber") {
            $structure_type = "Rack";
        } elseif (stripos($type, 'rack') !== false || stripos($type, 'chamber') !== false) {
            $structure_type = "Rack";
        } elseif (stripos($type, 'bed') !== false || stripos($type, 'house') !== false || stripos($type, 'field') !== false || stripos($type, 'tunnel') !== false) {
            $structure_type = "Bed";
        } else {
            $structure_type = $type;
        }

        /* ===============================
           ✅ VALIDATION
        =============================== */
        if ($polyhousename === '' || $projectid === '') {
            echo json_encode([
                "status" => "warning",
                "message" => "Polyhouse name and project are required."
            ]);
            exit;
        }

        if (!is_array($bedDetailsArr) || count($bedDetailsArr) === 0) {
            echo json_encode([
                "status" => "warning",
                "message" => "At least one Bed/Rack detail is required."
            ]);
            exit;
        }
        $krfconn->begin_transaction();
        /* ===============================
           ✏️ UPDATE MODE
        =============================== */
        if (!empty($polyhouseid)) {

            $sql = "
              UPDATE krf_polyhouse_master 
              SET polyhousename     = ?,
                  locationid        = ?,
                  physicallocation  = ?,
                  type              = ?,
                  status            = ?,
                  description       = ?,
                  structure_type    = ?,
                  beddetails        = ?,
                  updated_at        = NOW()
              WHERE polyhouseid     = ?
          ";

            $stmt = $krfconn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $krfconn->error);
            }

            $stmt->bind_param(
                "ssssssssi",
                $polyhousename,
                $locationid,
                $physicallocation,
                $type,
                $status,
                $description,
                $structure_type,
                $beddetails,
                $polyhouseid
            );

            $stmt->execute();

            $krfconn->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Polyhouse details updated successfully."
            ]);

        }
        /* ===============================
           ➕ INSERT MODE
        =============================== */ else {

            $sql = "
              INSERT INTO krf_polyhouse_master
              (
                  projectid,
                  locationid,
                  polyhousename,
                  physicallocation,
                  type,
                  status,
                  description,
                  structure_type,
                  created_by,
                  beddetails,
                  created_at
              )
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
          ";

            $stmt = $krfconn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $krfconn->error);
            }

            $stmt->bind_param(
                "ssssssssss",
                $projectid,
                $locationid,
                $polyhousename,
                $physicallocation,
                $type,
                $status,
                $description,
                $structure_type,
                $created_by,
                $beddetails
            );

            $stmt->execute();

            $krfconn->commit();

            echo json_encode([
                "status" => "success",
                "message" => "Polyhouse added successfully."
            ]);
        }
    } catch (Exception $e) {

        if ($krfconn->$in_transaction) {
            $krfconn->rollback();
        }

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} else if ($action == "updateBedDetails") {
    $response = ["status" => "error"];
    $bedid = $_POST['bedid'] ?? 0;
    $bedname = $_POST['bedname'] ?? '';
    $bedsize = $_POST['size'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($bedid > 0) {

        $stmt = $conn->prepare("
          UPDATE krf_polyhouse_beds
          SET bedname = ?, size = ?, status = ?
          WHERE bedid = ?
      ");

        $stmt->bind_param("sssi", $bedname, $size, $status, $bedid);

        if ($stmt->execute()) {
            $response["status"] = "success";
        }
    }

    echo json_encode($response);
} else if ($action == "GetPolihouseDetails") {
    $projectid = !empty($_POST['projectid']) ? $_POST['projectid'] : ($_SESSION['projectid'] ?? '17');
    $shortby = $_POST['shortby'];
    $shortbyvalue = $_POST['shortbyvalue'];
    echo json_encode(getPolyhouseData($krfconn, $projectid, $shortby, $shortbyvalue));
} else if ($action == "PolihousebedorrackDetails") {
    $projectid = $_POST['projectid'];
    $polyhouseid = $_POST['polyhouseid'];
    $polybeddata = getPolyhouseData($krfconn, $projectid, 'polyhouseid', $polyhouseid);
    $phbeddata = $polybeddata[0]['beddetails'];
    echo json_encode($phbeddata);
} else if ($action == "GetPlantingSource") {
    $projectid = $_POST['projectid'];
    echo json_encode(getPolyhouseSourceTypePlanting($krfconn, $projectid));
} else if ($action == "GetCultivationType") {
    echo json_encode(getCultivationTypeMaster($krfconn));
} else if ($action == "GerminationBoxDataAfterSowing") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $barcode = $_POST['qrcode'] ?? 'N/A';

    $sortby = "qrcode";
    $sortbyvalue = $barcode;

    $responsedata = getGerminationBoxQRCodeDetailsAfterSowing($krfconn, $projectid, '', $sortby, $sortbyvalue);
    $response = [];
    if (!empty($responsedata) && is_array($responsedata)) {
        foreach ($responsedata as $reskey) {
            $boxdata = getBoxReceivedAtPolyhouseCounts($krfconn, $projectid, $reskey['stockorderno'], $reskey['roottype']);

            // Fetch Sowing Date and Delivery Date from Stock Order
            $sowingDate = "";
            $deliveryDate = "";
            $orderQuery = "SELECT sowingdate, expected_delivery_date FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ?";
            $orderStmt = $krfconn->prepare($orderQuery);
            $orderStmt->bind_param("si", $reskey['stockorderno'], $projectid);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            if ($orderRow = $orderResult->fetch_assoc()) {
                $sowingDate = !empty($orderRow['sowingdate']) ? date('d-M-Y', strtotime($orderRow['sowingdate'])) : '';
                $deliveryDate = !empty($orderRow['expected_delivery_date']) ? date('d-M-Y', strtotime($orderRow['expected_delivery_date'])) : '';
            }

            // Fetch sowingdate_internal from sowing details table
            $sowingDateInternal = "";
            $sowingIntQuery = "SELECT DISTINCT sowingdate_internal FROM krf_polyhouse_sowingplant_details WHERE germination_qrcode = ? AND projectid = ? LIMIT 1";
            $sowingIntStmt = $krfconn->prepare($sowingIntQuery);
            $sowingIntStmt->bind_param("si", $reskey['qrcode'], $projectid);
            $sowingIntStmt->execute();
            $sowingIntResult = $sowingIntStmt->get_result();
            if ($sowingIntRow = $sowingIntResult->fetch_assoc()) {
                $sowingDateInternal = !empty($sowingIntRow['sowingdate_internal']) ? date('d-M-Y', strtotime($sowingIntRow['sowingdate_internal'])) : '';
            }
            $sowingIntStmt->close();

            $totaltrayinorder = $boxdata['total_boxes'] * $reskey['traycountinbox'];

            $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);

            $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

            $sowinglocationid = $reskey['sowinglocationid'] ?? '';
            if (empty($sowinglocationid) || $sowinglocationid == '0') {
                $locQuery = "SELECT sowinglocationid FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND roottype = ? AND projectid = ? AND sowinglocationid > 0 LIMIT 1";
                if ($locStmt = $krfconn->prepare($locQuery)) {
                    $locStmt->bind_param("ssi", $reskey['stockorderno'], $reskey['roottype'], $projectid);
                    $locStmt->execute();
                    $locRes = $locStmt->get_result();
                    if ($locRow = $locRes->fetch_assoc()) {
                        $sowinglocationid = $locRow['sowinglocationid'];
                    }
                    $locStmt->close();
                }
            }
            if (empty($sowinglocationid) || $sowinglocationid == '0') {
                $locQuery2 = "SELECT sowinglocationid FROM krf_polyhouse_sowingplant_header WHERE stockorderno = ? AND projectid = ? AND sowinglocationid > 0 LIMIT 1";
                if ($locStmt2 = $krfconn->prepare($locQuery2)) {
                    $locStmt2->bind_param("si", $reskey['stockorderno'], $projectid);
                    $locStmt2->execute();
                    $locRes2 = $locStmt2->get_result();
                    if ($locRow2 = $locRes2->fetch_assoc()) {
                        $sowinglocationid = $locRow2['sowinglocationid'];
                    }
                    $locStmt2->close();
                }
            }

            $response = [
                'receivedstatus' => $reskey['status'],
                'boxid' => $reskey['boxid'],
                'totalbox' => $boxdata['total_boxes'],
                'allocatedbox' => $boxdata['allocated_boxes'],
                'unallocatedbox' => $boxdata['unallocated_boxes'],
                'traycountinbox' => $reskey['traycountinbox'],
                'totaltrayinorder' => $totaltrayinorder,
                'stockorderno' => $reskey['stockorderno'],
                'productid' => $reskey['productid'],
                'productname' => $reskey['productname'],
                'polyhouseid' => $reskey['polyhouseid'],
                'polyhousename' => $polyhousename,
                'polyhouse_locationid' => isset($poldata[0]['locationid']) ? $poldata[0]['locationid'] : '',
                'boxpackingdate' => $reskey['boxpackingdate'],
                'boxopeningdate' => $reskey['boxopeningdate'],
                'roottype' => $reskey['roottype'],
                'sowinglocationid' => $sowinglocationid,
                'sowingdate' => $sowingDate,
                'sowingdate_internal' => $sowingDateInternal,
                'deliverydate' => $deliveryDate,
                'boxcreateddate' => !empty($reskey['created_at']) ? date('d-M-Y', strtotime($reskey['created_at'])) : '',
                'boxpackingdatedsp' => !empty($reskey['boxpackingdate']) ? date('d-M-Y', strtotime($reskey['boxpackingdate'])) : '',
                'boxopeningdatedsp' => !empty($reskey['boxopeningdate']) ? date('d-M-Y', strtotime($reskey['boxopeningdate'])) : ''
            ];
        }
        echo json_encode($response);

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No plant data found for the given QR code.'
        ]);
    }
} else if ($action == "UpdateReceivedGboxatGroom") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $loginid = $_POST['loginid'] ?? '';
    $sowinglocationid = $_POST['sowinglocationid'] ?? '';
    $boxids = json_decode($_POST['boxids'] ?? '[]', true);

    if (empty($boxids) || empty($sowinglocationid)) {
        echo json_encode(["status" => "error", "message" => "Missing required parameters."]);
        exit;
    }

    try {
        $krfconn->begin_transaction();
        $status = "ReceivedAfterSowing";
        $received_at = date('Y-m-d H:i:s');
        // Note: Adding columns receivedatgroom_by/at if they don't exist is safer, 
        // but often they just want to use generic received_by/at. 
        // However, I'll use a safer approach: update status and sowinglocationid.
        // Let's re-check the most common columns used for tracking.
        // I'll stick to updating status and sowinglocationid as requested.

        $sql = "UPDATE krf_polyhouse_germinated_boxes 
                SET status = ?, sowinglocationid = ?, 
                    received_by = ?, received_at = ? 
                WHERE boxid = ? AND projectid = ?";

        $updatestmt = $krfconn->prepare($sql);
        if (!$updatestmt) {
            throw new Exception("Prepare failed: " . $krfconn->error);
        }

        foreach ($boxids as $boxid) {
            $boxid_int = $boxid;
            $updatestmt->bind_param("ssssss", $status, $sowinglocationid, $loginid, $received_at, $boxid_int, $projectid);
            $updatestmt->execute();
        }
        $krfconn->commit();
        echo json_encode(["status" => "success", "message" => "Boxes received at Groom successfully."]);
    } catch (Exception $e) {
        if (isset($krfconn))
            $krfconn->rollback();
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
} else if ($action == "GetGerminationBoxDataWithQRCode") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $barcode = $_POST['qrcode'] ?? 'N/A';

    // if (strlen($barcode) < 15) {
    //     $sortby = "like_qrcode";
    //     $sortbyvalue = $barcode;
    // } else {
    //     $sortby = "qrcode";
    //     $sortbyvalue = $barcode;
    // }
    $sortby = "qrcode";
    $sortbyvalue = $barcode;
    $responsedata = getGerminationBoxQRCodeDetails($krfconn, $projectid, '', $sortby, $sortbyvalue);
    $response = [];
    if (!empty($responsedata) && is_array($responsedata)) {
        foreach ($responsedata as $reskey) {
            // $sowingdata = getTrayGroupedData($krfconn, $projectid, $reskey['stockorderno']);
            // $traycavity = $sowingdata[0]['traycavity'];

            // getProductionStockOrdersDetails($krfconn, $projectid, $shortby = null, $shortbyvalue = null);


            $boxdata = getBoxReceivedAtPolyhouseCounts($krfconn, $projectid, $reskey['stockorderno'],$reskey['roottype']);

            // Fetch Sowing Date and Delivery Date from Stock Order
            $sowingDate = "";
            $deliveryDate = "";
            $orderQuery = "SELECT sowingdate, expected_delivery_date FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ?";
            $orderStmt = $krfconn->prepare($orderQuery);
            $orderStmt->bind_param("si", $reskey['stockorderno'], $projectid);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            if ($orderRow = $orderResult->fetch_assoc()) {
                $sowingDate = !empty($orderRow['sowingdate']) ? date('d-M-Y', strtotime($orderRow['sowingdate'])) : '';
                $deliveryDate = !empty($orderRow['expected_delivery_date']) ? date('d-M-Y', strtotime($orderRow['expected_delivery_date'])) : '';
            }

            $totaltrayinorder = $boxdata['total_boxes'] * $reskey['traycountinbox'];

            $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);

            $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

            $seedDetails = getSeedDetailspassingpassing($krfconn, $projectid, $reskey['productid'], $reskey['roottype']);
            $seedName = isset($seedDetails['SeedName']) ? $seedDetails['SeedName'] : 'N/A';

            $response = [
                'receivedstatus' => $reskey['status'],
                'boxid' => $reskey['boxid'],
                'sowinglotno_internal' => $reskey['sowinglotno_internal'],
                'boxbatchdate' => $reskey['boxbatchdate'],
                'totalbox' => $boxdata['total_boxes'],
                'allocatedbox' => $boxdata['allocated_boxes'],
                'unallocatedbox' => $boxdata['unallocated_boxes'],
                'traycountinbox' => $reskey['traycountinbox'],
                'totaltrayinorder' => $totaltrayinorder,
                'stockorderno' => $reskey['stockorderno'],
                'productid' => $reskey['productid'],
                'productname' => $reskey['productname'],
                'polyhouseid' => $reskey['polyhouseid'],
                'polyhousename' => $polyhousename,
                'polyhouse_locationid' => isset($poldata[0]['locationid']) ? $poldata[0]['locationid'] : '',
                'boxpackingdate' => $reskey['boxpackingdate'],
                'boxopeningdate' => $reskey['boxopeningdate'],
                'roottype' => $reskey['roottype'],
                'sowinglocationid' => $reskey['sowinglocationid'],
                'sowingdate' => $sowingDate,
                'deliverydate' => $deliveryDate,
                'seedname' => $seedName,
                'boxcreateddate' => !empty($reskey['created_at']) ? date('d-M-Y', strtotime($reskey['created_at'])) : '',
                'boxpackingdatedsp' => !empty($reskey['boxpackingdate']) ? date('d-M-Y', strtotime($reskey['boxpackingdate'])) : '',
                'boxopeningdatedsp' => !empty($reskey['boxopeningdate']) ? date('d-M-Y', strtotime($reskey['boxopeningdate'])) : ''
            ];
        }
        echo json_encode($response);

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No plant data found for the given QR code.'
        ]);
    }
} else if ($action == "fetchallocatedGBoxDataWithQRCode") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $barcode = $_POST['qrcode'] ?? 'N/A';

    // Direct check of status first
    $checkQry = "SELECT status, boxid FROM krf_polyhouse_germinated_boxes WHERE qrcode = ? AND projectid = ? LIMIT 1";
    $checkStmt = $krfconn->prepare($checkQry);
    $checkStmt->bind_param("si", $barcode, $projectid);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$checkRes) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid box QR.'
        ]);
        exit;
    }

    if ($checkRes['status'] === 'PendingAllocation') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Box Allocation not defined from germination room'
        ]);
        exit;
    }

    if ($checkRes['status'] === 'ReceivedAtPolyhouse') {
        echo json_encode([
            'status' => 'success',
            'receivedstatus' => 'ReceivedAtPolyhouse',
            'boxid' => $checkRes['boxid']
        ]);
        exit;
    }

    if ($checkRes['status'] !== 'Allocated') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Box status is ' . $checkRes['status']
        ]);
        exit;
    }

    $sortby = "qrcode";
    $sortbyvalue = $barcode;
    $responsedata = AllocatedGerminationBoxDetails($krfconn, $projectid, '', $sortby, $sortbyvalue);
    $response = [];
    if (!empty($responsedata) && is_array($responsedata)) {
        foreach ($responsedata as $reskey) {
            $boxdata = getBoxReceivedAtPolyhouseCounts($krfconn, $projectid, $reskey['stockorderno'],$reskey['roottype'],$reskey['sowinglotno_internal']);
            // Fetch Sowing Date and Delivery Date from Stock Order
            $sowingDate = "";
            $deliveryDate = "";
            $orderQuery = "SELECT sowingdate, expected_delivery_date FROM krf_production_stockorder WHERE stockorder_no = ? AND projectid = ?";
            $orderStmt = $krfconn->prepare($orderQuery);
            $orderStmt->bind_param("si", $reskey['stockorderno'], $projectid);
            $orderStmt->execute();
            $orderResult = $orderStmt->get_result();
            if ($orderRow = $orderResult->fetch_assoc()) {
                $sowingDate = !empty($orderRow['sowingdate']) ? date('d-M-Y', strtotime($orderRow['sowingdate'])) : '';
                $deliveryDate = !empty($orderRow['expected_delivery_date']) ? date('d-M-Y', strtotime($orderRow['expected_delivery_date'])) : '';
            }

            $totaltrayinorder = $boxdata['total_boxes'] * $reskey['traycountinbox'];

            $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);

            $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

            $seedDetails = getSeedDetailspassingpassing($krfconn, $projectid, $reskey['productid'], $reskey['roottype']);
            $seedName = isset($seedDetails['SeedName']) ? $seedDetails['SeedName'] : 'N/A';

            $response = [
                'status' => 'success',
                'receivedstatus' => $reskey['status'],
                'boxid' => $reskey['boxid'],
                'sowinglotno_internal' => $reskey['sowinglotno_internal'],
                'boxbatchdate' => $reskey['boxbatchdate'],
                'totalbox' => $boxdata['total_boxes'],
                'allocatedbox' => $boxdata['allocated_boxes'],
                'unallocatedbox' => $boxdata['unallocated_boxes'],
                'traycountinbox' => $reskey['traycountinbox'],
                'totaltrayinorder' => $totaltrayinorder,
                'stockorderno' => $reskey['stockorderno'],
                'productid' => $reskey['productid'],
                'productname' => $reskey['productname'],
                'polyhouseid' => $reskey['polyhouseid'],
                'polyhousename' => $polyhousename,
                'polyhouse_locationid' => isset($poldata[0]['locationid']) ? $poldata[0]['locationid'] : '',
                'boxpackingdate' => $reskey['boxpackingdate'],
                'boxopeningdate' => $reskey['boxopeningdate'],
                'roottype' => $reskey['roottype'],
                'sowinglocationid' => $reskey['sowinglocationid'],
                'sowingdate' => $sowingDate,
                'deliverydate' => $deliveryDate,
                'seedname' => $seedName,
                'boxcreateddate' => !empty($reskey['created_at']) ? date('d-M-Y', strtotime($reskey['created_at'])) : '',
                'boxpackingdatedsp' => !empty($reskey['boxpackingdate']) ? date('d-M-Y', strtotime($reskey['boxpackingdate'])) : '',
                'boxopeningdatedsp' => !empty($reskey['boxopeningdate']) ? date('d-M-Y', strtotime($reskey['boxopeningdate'])) : ''
            ];
        }
        echo json_encode($response);

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No plant data found for the given QR code.'
        ]);
    }
} else if ($action == "GetUnVerifiedGerminationBoxDetailsData") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    $locationid = $_POST['locationid'] ?? '';
    if (empty($projectid)) {
        http_response_code(400);
        echo json_encode([
            "status" => "error",
            "message" => "Project ID is required."
        ]);
        exit;
    }

    $sql = "SELECT projectid, stockorderno, productid, productname, traycountinbox,
      SUM(traycountinbox) AS total_traycount,
      MIN(boxpackingdate) AS boxpackingdate,
      MIN(boxopeningdate) AS boxopeningdate,
      SUM(CASE WHEN status = 'Allocated' THEN 1 ELSE 0 END) AS allocated_count,
      SUM(CASE WHEN status = 'ReceivedAtPolyhouse' THEN 1 ELSE 0 END) AS received_count
  FROM krf_polyhouse_germinated_boxes WHERE projectid = ?
  ";
    $params = [$projectid];
    $types = "i";

    if (!empty($polyhouseid)) {
        $sql .= " AND polyhouseid = ?";
        $params[] = $polyhouseid;
        $types .= "i";
    }

    if (!empty($locationid)) {
        $sql .= " AND polyhouseid IN (SELECT polyhouseid FROM krf_polyhouse_master WHERE locationid = ?)";
        $params[] = $locationid;
        $types .= "s";
    }

    $sql .= " GROUP BY projectid, stockorderno, productid ORDER BY stockorderno DESC";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => "Database prepare failed."
        ]);
        exit;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    echo json_encode([
        "status" => "success",
        "count" => count($data),
        "data" => $data
    ]);
} else if ($action == "UpdateAllocationofGerminationBoxData") {
    $companyid = $_POST['companyid'] ?? 0;
    $subcompanyid = $_POST['subcompanyid'] ?? 0;
    $projectid = $_POST['projectid'] ?? 0;
    $polyhouseid = $_POST['polyhouseid'] ?? 0;
    $boxid = $_POST['boxid'] ?? 0;
    $boxbatchdate = $_POST['boxbatchdate'] ?? '';
    $sowinglotnointernal = $_POST['sowinglotnointernal'] ?? '';
    $stockorderno = $_POST['stockorderno'] ?? '';
    $roottype = trim($_POST['roottype'] ?? '');

    $allocation_mode = $_POST['allocation_mode'] ?? 'single';
    $loginid = $_POST['loginid'] ?? ($_SESSION['loginid'] ?? null);
    $received_at = date('Y-m-d H:i:s');
    $status = "Allocated";

    try {
        $krfconn->begin_transaction();

        /* ===============================
           1️⃣ SELECT BOXES & CALCULATE TOTAL TRAYS
        =============================== */
        if ($allocation_mode === "all") {
            // Auto-detect roottype, sowinglotnointernal, and stockorderno from scanned boxid if missing
            if ((empty($roottype) || $roottype === 'N/A' || empty($sowinglotnointernal) || empty($stockorderno)) && !empty($boxid)) {
                $chkStmt = $krfconn->prepare("SELECT roottype, sowinglotno_internal, stockorderno FROM krf_polyhouse_germinated_boxes WHERE boxid = ? LIMIT 1");
                if ($chkStmt) {
                    $chkStmt->bind_param("i", $boxid);
                    $chkStmt->execute();
                    $chkRow = $chkStmt->get_result()->fetch_assoc();
                    $chkStmt->close();
                    if ($chkRow) {
                        if (empty($roottype) || $roottype === 'N/A') {
                            $roottype = $chkRow['roottype'] ?? '';
                        }
                        if (empty($sowinglotnointernal)) {
                            $sowinglotnointernal = $chkRow['sowinglotno_internal'] ?? '';
                        }
                        if (empty($stockorderno)) {
                            $stockorderno = $chkRow['stockorderno'] ?? '';
                        }
                    }
                }
            }

            $conditions = [
                "status IN ('PendingAllocation', 'ReceivedAfterSowing')",
                "projectid = ?",
                "stockorderno = ?"
            ];
            $params = [$projectid, $stockorderno];
            $types = "is";

            if (!empty($roottype) && $roottype !== 'N/A') {
                $conditions[] = "roottype = ?";
                $params[] = $roottype;
                $types .= "s";
            }
            if (!empty($sowinglotnointernal)) {
                $conditions[] = "sowinglotno_internal = ?";
                $params[] = $sowinglotnointernal;
                $types .= "s";
            }

            $whereClause = implode(" AND ", $conditions);
            $ssq = "SELECT boxid, traycountinbox, roottype, stockorderno FROM krf_polyhouse_germinated_boxes WHERE $whereClause";
            $sstmt = $krfconn->prepare($ssq);
            $sstmt->bind_param($types, ...$params);
        } else {
            $ssq = "SELECT boxid, traycountinbox, roottype, stockorderno FROM krf_polyhouse_germinated_boxes WHERE status in('PendingAllocation','ReceivedAfterSowing') AND projectid = ? AND stockorderno = ? AND boxid = ?";
            $sstmt = $krfconn->prepare($ssq);
            $sstmt->bind_param("isi", $projectid, $stockorderno, $boxid);
        }
        if (!$sstmt) {
            throw new Exception("Failed to prepare select query.");
        }
        $sstmt->execute();
        $result = $sstmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No pending boxes found for allocation.");
        }

        $boxesToAllocate = [];
        $totalTraysRequired = 0;
        while ($row = $result->fetch_assoc()) {
            $boxesToAllocate[] = (int) $row['boxid'];
            $totalTraysRequired += (int) $row['traycountinbox'];
        }

        /* ===============================
           2️⃣ CAPACITY CHECK
        =============================== */
        $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $polyhouseid);
        if (empty($poldata)) {
            throw new Exception("Target polyhouse not found.");
        }
        $locationid = $poldata[0]['locationid'];

        // Use existing function to get usage/capacity details
        $usageDetails = getPolyhouseTrayUsageDetails($krfconn, $locationid, $polyhouseid, $projectid);
        // print_r($usageDetails);
        $remainingCapacity = (int) ($usageDetails['remaining_total_tray'] ?? 0);

        if ($totalTraysRequired > $remainingCapacity) {
            throw new Exception("Insufficient capacity in polyhouse. Required: $totalTraysRequired trays, Available: $remainingCapacity trays.");
        }

        /* ===============================
           3️⃣ UPDATE EACH BOX
        =============================== */
        if (!empty($loginid)) {
            $usql = "UPDATE krf_polyhouse_germinated_boxes SET status = ?, polyhouseid = ?, received_by = ?, received_at = ? WHERE boxid = ?";
            $ustmt = $krfconn->prepare($usql);
            foreach ($boxesToAllocate as $currentBoxId) {
                $ustmt->bind_param("sissi", $status, $polyhouseid, $loginid, $received_at, $currentBoxId);
                $ustmt->execute();
            }
        } else {
            $usql = "UPDATE krf_polyhouse_germinated_boxes SET status = ?, polyhouseid = ?, received_at = ? WHERE boxid = ?";
            $ustmt = $krfconn->prepare($usql);
            foreach ($boxesToAllocate as $currentBoxId) {
                $ustmt->bind_param("sisi", $status, $polyhouseid, $received_at, $currentBoxId);
                $ustmt->execute();
            }
        }
        $krfconn->commit();

        $gmboxdetails = getBoxAllocationCounts($krfconn, $projectid, $stockorderno, $roottype);
        $allocatedBoxCount = count($boxesToAllocate);
        echo json_encode([
            "status" => "success",
            "message" => ($allocation_mode === "all")
                ? "All $allocatedBoxCount pending germination boxes" . (!empty($roottype) && $roottype !== 'N/A' ? " ($roottype)" : "") . " allocated successfully ($totalTraysRequired trays)."
                : "Germination box allocated successfully ($totalTraysRequired trays).",
            "allocation_mode" => $allocation_mode,
            "allocated_boxes" => $boxesToAllocate,
            "allocated_count" => $allocatedBoxCount,
            "total_trays" => $totalTraysRequired,
            "roottype" => $roottype,
            "data" => $gmboxdetails
        ]);

    } catch (Exception $e) {
        if (isset($krfconn) && $krfconn instanceof mysqli) {
            @$krfconn->rollback();
        }
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    } finally {
        if (isset($sstmt))
            $sstmt->close();
        if (isset($ustmt))
            $ustmt->close();
    }
} else if ($action == "SubmitGerminationBoxVerificationForPolyhouse") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = $_POST['projectid'] ?? '';
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    $boxid = $_POST['boxid'] ?? '';
    $stockorderno = $_POST['stockorderno'] ?? '';
    $status = 'ReceivedAtPolyhouse';
    $allocation_mode = $_POST['allocation_mode'] ?? 'single';
    $received_by = $_POST['loginid'] ?? null;
    $received_at = date('Y-m-d H:i:s');
    // die();
    /* ===============================
       BASIC VALIDATION
    ================================ */
    if (empty($projectid) || empty($stockorderno) || empty($polyhouseid)) {
        echo json_encode([
            "status" => "error",
            "message" => "Missing required information. Please try again."
        ]);
        exit;
    }

    if ($allocation_mode === 'single' && empty($boxid)) {
        echo json_encode([
            "status" => "error",
            "message" => "Box ID is required for single box receipt."
        ]);
        exit;
    }

    try {
        $krfconn->begin_transaction();
        /* ===============================
           1️⃣ SELECT BOXES TO RECEIVE
        ================================ */
        if ($allocation_mode === "all") {
            $ssq = "
              SELECT boxid 
              FROM krf_polyhouse_germinated_boxes 
              WHERE status = 'Allocated'
                AND projectid = ?
                AND stockorderno = ?
          ";
            $sstmt = $krfconn->prepare($ssq);
            if (!$sstmt) {
                throw new Exception("Failed to prepare box selection query.");
            }
            $sstmt->bind_param("is", $projectid, $stockorderno);
        } else {
            $ssq = "
              SELECT boxid 
              FROM krf_polyhouse_germinated_boxes 
              WHERE projectid = ?
                AND stockorderno = ?
                AND boxid = ?
          ";
            // status = 'Allocated'
            //      AND
            $sstmt = $krfconn->prepare($ssq);
            if (!$sstmt) {
                throw new Exception("Failed to prepare box selection query.");
            }
            $sstmt->bind_param("isi", $projectid, $stockorderno, $boxid);
        }

        $sstmt->execute();
        $result = $sstmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception("No allocated germination boxes found to receive.");
        }

        /* ===============================
           2️⃣ UPDATE BOX STATUS
        ================================ */
        $usql = "
          UPDATE krf_polyhouse_germinated_boxes
          SET status = ?,
              polyhouseid = ?,
              received_by = ?,
              received_at = ?
          WHERE boxid = ?
      ";
        $ustmt = $krfconn->prepare($usql);
        if (!$ustmt) {
            throw new Exception("Failed to prepare box update query.");
        }

        $bed_no = $_POST['bed_no'] ?? null;

        while ($row = $result->fetch_assoc()) {
            $currentBoxId = (int) $row['boxid'];

            // 1. Update box status
            $ustmt->bind_param(
                "sissi",
                $status,
                $polyhouseid,
                $received_by,
                $received_at,
                $currentBoxId
            );
            $ustmt->execute();

            // 2. Immediate Bed Allocation (if bed_no is provided)
            if (!empty($bed_no)) {
                // Get locationid for this polyhouse
                $locStmt = $krfconn->prepare("SELECT locationid FROM krf_polyhouse_master WHERE polyhouseid = ?");
                $locStmt->bind_param("i", $polyhouseid);
                $locStmt->execute();
                $locRes = $locStmt->get_result()->fetch_assoc();
                $phLocationId = $locRes['locationid'] ?? null;
                $locStmt->close();

                // Move all trays in this box to the specified bed
                $moveSql = "INSERT INTO krf_polyhouse_plant_details (
                                psono, roottype, sowingplantid, stockorder_no, locationid,
                                labourid, polyhouseid, projectid, lotno,
                                batchno, barcode, productid, productname,
                                tray_id, qtypertray, tray_position, bedrackname, bed_no,
                                planted_date, workingstatus, status, remarks, created_by
                            )
                            SELECT
                                s.psono, s.roottype, s.sowingplantid, s.stockorder_no, ?,
                                s.labourid, ?, s.projectid, s.lotno,
                                s.sowingbatchno, s.barcode, s.productid,
                                s.productname, s.trayid, s.traycavity,
                                ?, ?, ?,
                                ?, 'Polyhouse', 'Polyhouse', s.remarks, ?
                            FROM krf_polyhouse_sowingplant_details s
                            WHERE s.germinationboxid = ? AND s.projectid = ? AND s.status IN ('Allocated', 'Sowing')";

                $moveStmt = $krfconn->prepare($moveSql);
                if ($moveStmt) {
                    $moveStmt->bind_param(
                        "sisssssii",
                        $phLocationId,
                        $polyhouseid,
                        $bed_no,
                        $bed_no,
                        $bed_no,
                        $received_at,
                        $received_by,
                        $currentBoxId,
                        $projectid
                    );
                    $moveStmt->execute();
                    $moveStmt->close();

                    // Update sowing details status to Polyhouse
                    $updSowingSql = "UPDATE krf_polyhouse_sowingplant_details SET status = 'Polyhouse' WHERE germinationboxid = ? AND projectid = ? AND status IN ('Allocated', 'Sowing')";
                    $updSowingStmt = $krfconn->prepare($updSowingSql);
                    $updSowingStmt->bind_param("ii", $currentBoxId, $projectid);
                    $updSowingStmt->execute();
                    $updSowingStmt->close();
                }
            }
        }

        $krfconn->commit();

        /* ===============================
           3️⃣ FETCH UPDATED COUNTS
        ================================ */
        $gmboxdetails = getBoxAllocationCounts($krfconn, $projectid, $stockorderno);

        echo json_encode([
            "status" => "success",
            "message" => ($allocation_mode === "all")
                ? "All allocated germination boxes have been successfully received at the polyhouse."
                : "The germination box has been successfully received at the polyhouse of Box id " . $boxid,
            "data" => $gmboxdetails
        ]);

    } catch (Exception $e) {

        $krfconn->rollback();

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);

    } finally {
        if (isset($sstmt))
            $sstmt->close();
        if (isset($ustmt))
            $ustmt->close();
    }

} else if ($action == "fetchGerminationBoxTrayWithQRCode") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = $_POST['projectid'];
    $barcode = $_POST['qrcode'] ?? 'N/A';
    if (strlen($barcode) < 15) {
        $sortby = "like_qrcode";
        $sortbyvalue = $barcode;
    } else {
        $sortby = "qrcode";
        $sortbyvalue = $barcode;
    }
    $stockorderno = "";
    $responsedata = getSowingTrayDetails($krfconn, $projectid, $stockorderno, $sortby, $sortbyvalue);
    $response = [];
    if (!empty($responsedata) && is_array($responsedata)) {
        foreach ($responsedata as $reskey) {

            $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);

            $polyhusetraydata = getPolyhouseTrayUsageDetails($krfconn, $poldata[0]['locationid'], $polyhouseid, $projectid);
            $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';
            $polyhouseid = isset($poldata[0]['polyhouseid']) ? $poldata[0]['polyhouseid'] : $reskey['polyhouseid'];
            $beddetails = isset($poldata[0]['beddetails']) ? $poldata[0]['beddetails'] : [];
            $traystatus = "Sowing/Germination";
            $allocationstatus = "";
            $status = $reskey['status'] ?? 'Sowing';
            // Status color mapping
            $statusConfig = [
                'Polyhouse' => [
                    'alert' => 'success',
                    'badge' => '#2e7d32',
                    'text' => 'Allocated'
                ],
                'Sowing' => [
                    'alert' => 'warning',
                    'badge' => '#ef6c00',
                    'text' => 'Pending Allocation'
                ],
                'removed' => [
                    'alert' => 'danger',
                    'badge' => '#c62828',
                    'text' => 'Removed'
                ],
                'Dead' => [
                    'alert' => 'danger',
                    'badge' => '#c62828',
                    'text' => 'Removed'
                ]
            ];

            $cfg = $statusConfig[$status];
            if ($reskey['status'] === "Polyhouse") {
                $allocationstatus = "allocated";
                $tryd = getPolyhousePlantDetails($krfconn, $projectid, "barcode", $reskey['barcode']);
                // possible values: allocated | pending | removed
                $traystatus = '
              <div class="alert alert-' . $cfg['alert'] . ' d-flex align-items-center" 
                   style="gap:10px; font-size:14px; padding:10px 14px;">

                  <span class="badge" 
                        style="background:' . $cfg['badge'] . '; color:#fff; font-size:12px;">
                      ' . $cfg['text'] . '
                  </span>
                  <span>
                      🏡 <strong style="color:#2e7d32;">' . $tryd[0]['polyhousename'] . '</strong>,<br/>
                      🧺 <strong style="color:#1565c0;">' . $tryd[0]['bedrackname'] . '</strong>
                      <span style="color:#ef6c00; font-weight:600;">
                          📍 ' . $tryd[0]['tray_position'] . '
                      </span>
                  </span>

              </div>';

            } else {
                $traystatus = '
              <div class="alert alert-' . $cfg['alert'] . ' d-flex align-items-center" 
                   style="gap:10px; font-size:14px; padding:10px 14px;">

                  <span class="badge" 
                        style="background:' . $cfg['badge'] . '; color:#fff; font-size:12px;">
                      ' . $cfg['text'] . '
                  </span>
              </div>';
            }

            $response = [
                'sowingplantid' => $reskey['sowingplantid'],
                'sowingdate' => $reskey['sowing_date'],
                'psono' => $reskey['psono'],
                'seedlotno' => $reskey['lotno'],
                'trayqrcode' => $reskey['barcode'],
                'stockorderno' => $reskey['stockorder_no'],
                'productid' => $reskey['productid'],
                'productname' => $reskey['productname'],
                'trayid' => $reskey['trayid'],
                'traycavity' => $reskey['traycavity'],
                'traystatus' => $traystatus,
                'allocationstatus' => $allocationstatus,
                'polyhouseid' => $polyhouseid,
                'polyhousename' => $polyhousename,
                'beddetails' => $beddetails,
                'polyhusetraydata' => $polyhusetraydata
            ];
        }
        echo json_encode($response);

    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No plant data found for the given QR code.'
        ]);
    }
} else if ($action == "saveTrayAllocation") {
    $companyid = $_POST['companyid'];
    $subcompanyid = $_POST['subcompanyid'];
    $projectid = (int) $_POST['projectid'];
    $loginid = $_POST['loginid'];
    $gboxid = (int) $_POST['gboxid'];

    $trayDataRaw = $_POST['trayData'] ?? null;

    /* ===============================
       HANDLE JSON / ARRAY SAFELY
    ================================ */
    if (is_string($trayDataRaw)) {
        $trayData = json_decode($trayDataRaw, true);
    } elseif (is_array($trayDataRaw)) {
        $trayData = $trayDataRaw;
    } else {
        $trayData = null;
    }

    if (!is_array($trayData) || count($trayData) === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Invalid tray data format"
        ]);
        exit;
    }

    try {

        $krfconn->begin_transaction();

        /* =====================================================
           1️⃣ LOCK GERMINATION BOX
        ===================================================== */
        $boxSql = "
          SELECT polyhouseid, stockorderno
                 allocatedtraydata,
                 IFNULL(traycountinbox,0) AS traycountinbox,
                 IFNULL(allocated_traycount,0) AS allocated_traycount
          FROM krf_polyhouse_germinated_boxes
          WHERE boxid = ?
          FOR UPDATE
      ";

        $boxStmt = $krfconn->prepare($boxSql);
        if (!$boxStmt) {
            throw new Exception("Prepare failed (box): " . $krfconn->error);
        }

        $boxStmt->bind_param("i", $gboxid);
        $boxStmt->execute();
        $boxRes = $boxStmt->get_result()->fetch_assoc();
        $boxStmt->close();

        if (!$boxRes) {
            throw new Exception("Invalid Germination Box");
        }

        $polyhouseid = $boxRes['polyhouseid'];
        $stockorderno = $boxRes['stockorderno'];
        $traycountinbox = (int) $boxRes['traycountinbox'];
        $allocatedtraycount = (int) $boxRes['allocated_traycount'];

        /* =====================================================
           2️⃣ DECODE EXISTING TRAY DATA
        ===================================================== */
        $existingTrayData = [];

        if (!empty($boxRes['allocatedtraydata'])) {
            $decoded = json_decode($boxRes['allocatedtraydata'], true);
            if (is_array($decoded)) {
                $existingTrayData = $decoded;
            }
        }

        /* =====================================================
           3️⃣ UPDATE SOWING PLANTS
        ===================================================== */
        $updateSowingSql = "
          UPDATE krf_polyhouse_sowingplant_details
          SET germinationboxid = ?, polyhouseid = ?
          WHERE projectid = ?
            AND stockorder_no = ?
            AND barcode = ?
      ";

        $upStmt = $krfconn->prepare($updateSowingSql);
        if (!$upStmt) {
            throw new Exception("Prepare failed (sowing): " . $krfconn->error);
        }

        foreach ($trayData as $tray) {

            if (empty($tray['stockorderno']) || empty($tray['qrcode'])) {
                throw new Exception("Invalid tray row data");
            }

            $stockorderno = $tray['stockorderno'];
            $qrcode = $tray['qrcode'];

            // iiss = INT, INT, STRING, STRING
            $upStmt->bind_param(
                "isiss",
                $gboxid,
                $polyhouseid,
                $projectid,
                $stockorderno,
                $qrcode
            );

            if (!$upStmt->execute()) {
                throw new Exception("Failed updating QR: {$qrcode}");
            }
        }

        $upStmt->close();

        /* =====================================================
           4️⃣ MERGE TRAY DATA
        ===================================================== */
        $mergedTrayData = array_merge($existingTrayData, $trayData);

        $allocatedTrayCount = count($mergedTrayData);
        $allocatedTrayJson = json_encode($mergedTrayData, JSON_UNESCAPED_UNICODE);

        /* =====================================================
           5️⃣ UPDATE GERMINATION BOX
        ===================================================== */
        $updatedat = date('Y-m-d H:i:s');

        $updateBoxSql = "
          UPDATE krf_polyhouse_germinated_boxes
          SET allocated_traycount = ?,
              allocatedtraydata  = ?
          WHERE boxid = ?
      ";

        $upBoxStmt = $krfconn->prepare($updateBoxSql);
        if (!$upBoxStmt) {
            throw new Exception("Prepare failed (box update): " . $krfconn->error);
        }

        // i s s s i
        $upBoxStmt->bind_param(
            "isi",
            $allocatedTrayCount,
            $allocatedTrayJson,
            $gboxid
        );

        if (!$upBoxStmt->execute()) {
            throw new Exception("Failed updating germination box");
        }

        $upBoxStmt->close();

        /* =====================================================
           ✅ COMMIT
        ===================================================== */
        $krfconn->commit();

        $beddetails = $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $polyhouseid);
        $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';

        echo json_encode([
            "status" => "success",
            "message" => "Tray data processed successfully",
            "data" => [
                "polyhouseid" => $polyhouseid,
                "beddetails" => $beddetails,
                "stockorderno" => $stockorderno,
                "boxid" => $gboxid,
                "traycountinbox" => $traycountinbox,
                "allocated_traycount" => $allocatedTrayCount
            ]
        ]);

    } catch (Exception $e) {

        $krfconn->rollback();

        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
    exit;
} else if ($action == "UpdateTrayAllocationToPolyhouse") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = (int) ($_POST['projectid'] ?? 0);
    $loginid = $_POST['loginid'] ?? '';
    $polyhouseid = (int) ($_POST['polyhouseid'] ?? 0);
    $bedrackname = $_POST['bedrackname'] ?? '';
    $trayposition = $_POST['placeofrow'] ?? '';
    $gfailurestatus = $_POST['gfailurestatus'] ?? 'Healthy';

    $boxidsRaw = $_POST['boxids'] ?? null;
    $singleBoxId = $_POST['boxid'] ?? null;
    // print_r($boxidsRaw);

    $boxids = [];
    if (!empty($boxidsRaw)) {
        $boxids = is_string($boxidsRaw) ? json_decode($boxidsRaw, true) : $boxidsRaw;
    } else if (!empty($singleBoxId)) {
        $boxids = [$singleBoxId];
    }

    if (empty($boxids)) {
        echo json_encode(["status" => "error", "message" => "No boxes provided for allocation"]);
        exit;
    }

    try {
        $krfconn->begin_transaction();

        // 1. GET POLYHOUSE LOCATION
        $locStmt = $krfconn->prepare("SELECT locationid FROM krf_polyhouse_master WHERE polyhouseid=?");
        $locStmt->bind_param("i", $polyhouseid);
        $locStmt->execute();
        $phData = $locStmt->get_result()->fetch_assoc();
        $locationid = $phData['locationid'] ?? null;
        $locStmt->close();

        if (!$locationid) {
            throw new Exception("Polyhouse location not found.");
        }

        $totalInserted = 0;
        $targetStatus = ($gfailurestatus == 'Healthy') ? 'Polyhouse' : $gfailurestatus;

        // Workflow: Process box-wide allocation (Box Receipt Workflow)
        foreach ($boxids as $bid) {
            $bid = (int) $bid;
            // 1. Get box info
            $bStmt = $krfconn->prepare("SELECT stockorderno, traycountinbox, sowinglotno_internal, qrcode FROM krf_polyhouse_germinated_boxes WHERE boxid = ?");
            $bStmt->bind_param("i", $bid);
            $bStmt->execute();
            $bRes = $bStmt->get_result()->fetch_assoc();
            $bStmt->close();

            if (!$bRes) {
                throw new Exception("Germination box ID $bid not found.");
            }

            $stockorderno = $bRes['stockorderno'];
            $traycountinbox = (int) $bRes['traycountinbox'];
            $sowinglotno_internal = $bRes['sowinglotno_internal'];
            $boxQrcode = trim($bRes['qrcode'] ?? '');

            // 1.1 Check if trays were ALREADY moved into polyhouse plant inventory previously
            $alreadySql = "SELECT COUNT(*) as cnt 
                           FROM krf_polyhouse_plant_details p 
                           INNER JOIN krf_polyhouse_sowingplant_details s ON p.sowingplantid = s.sowingplantid 
                           WHERE (s.germinationboxid = ? OR (s.germination_qrcode = ? AND s.germination_qrcode != '')) 
                             AND s.projectid = ?";
            $alreadyStmt = $krfconn->prepare($alreadySql);
            $alreadyStmt->bind_param("isi", $bid, $boxQrcode, $projectid);
            $alreadyStmt->execute();
            $alreadyCount = (int)($alreadyStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $alreadyStmt->close();

            if ($alreadyCount >= $traycountinbox && $traycountinbox > 0) {
                // Trays were already moved, update box status to ReceivedAtPolyhouse
                $boxUpdSql = "UPDATE krf_polyhouse_germinated_boxes 
                                 SET allocated_traycount = ?, 
                                     status = 'ReceivedAtPolyhouse',
                                     received_by = ?,
                                     received_at = NOW()
                                 WHERE boxid = ?";
                $boxUpdStmt = $krfconn->prepare($boxUpdSql);
                $boxUpdStmt->bind_param("isi", $alreadyCount, $loginid, $bid);
                $boxUpdStmt->execute();
                $boxUpdStmt->close();
                $totalInserted += $alreadyCount;
                continue;
            }

            // 2. Ensure Linkage: Link any trays having matching box QR code
            if (!empty($boxQrcode)) {
                $linkQrSql = "UPDATE krf_polyhouse_sowingplant_details 
                              SET germinationboxid = ? 
                              WHERE projectid = ? 
                                AND germination_qrcode = ? 
                                AND (germinationboxid IS NULL OR germinationboxid = 0)";
                $linkQrStmt = $krfconn->prepare($linkQrSql);
                if ($linkQrStmt) {
                    $linkQrStmt->bind_param("iis", $bid, $projectid, $boxQrcode);
                    $linkQrStmt->execute();
                    $linkQrStmt->close();
                }
            }

            // 2.1 Count available trays linked to this box not yet in polyhouse plant details
            $checkSql = "SELECT COUNT(*) as cnt 
                         FROM krf_polyhouse_sowingplant_details s 
                         WHERE (s.germinationboxid = ? OR (s.germination_qrcode = ? AND s.germination_qrcode != '')) 
                           AND s.projectid = ? 
                           AND NOT EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid)
                           AND s.status NOT IN ('Removed', 'Readytosale', 'Dispatched')";
            $checkStmt = $krfconn->prepare($checkSql);
            $checkStmt->bind_param("isi", $bid, $boxQrcode, $projectid);
            $checkStmt->execute();
            $availableInBox = (int)($checkStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $checkStmt->close();

            // 2.2 If linked trays are fewer than required, auto-link from unlinked trays for this stock order
            if ($availableInBox < $traycountinbox) {
                $needCount = $traycountinbox - $availableInBox;
                $selectLinkSql = "SELECT s.sowingplantid 
                                  FROM krf_polyhouse_sowingplant_details s
                                  WHERE s.projectid = ? 
                                    AND s.stockorder_no = ? 
                                    AND (s.germinationboxid IS NULL OR s.germinationboxid = 0)
                                    AND (s.germination_qrcode IS NULL OR s.germination_qrcode = '')
                                    AND s.sowinglotno_internal = ?
                                    AND s.status NOT IN ('Removed', 'Readytosale', 'Dispatched')
                                    AND NOT EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid)
                                  LIMIT ?";
                $selectLinkStmt = $krfconn->prepare($selectLinkSql);
                if ($selectLinkStmt) {
                    $selectLinkStmt->bind_param("issi", $projectid, $stockorderno, $sowinglotno_internal, $needCount);
                    $selectLinkStmt->execute();
                    $sRes = $selectLinkStmt->get_result();
                    $idsToLink = [];
                    while ($sRow = $sRes->fetch_assoc()) {
                        $idsToLink[] = (int)$sRow['sowingplantid'];
                    }
                    $selectLinkStmt->close();

                    if (!empty($idsToLink)) {
                        $idListToLink = implode(",", $idsToLink);
                        $linkSql = "UPDATE krf_polyhouse_sowingplant_details 
                                    SET germinationboxid = ?, germination_qrcode = ? 
                                    WHERE sowingplantid IN ($idListToLink)";
                        $linkStmt = $krfconn->prepare($linkSql);
                        if ($linkStmt) {
                            $linkStmt->bind_param("is", $bid, $boxQrcode);
                            $linkStmt->execute();
                            $linkStmt->close();
                        }
                    }
                }
            }

            // 3. Move trays for this box into polyhouse plant inventory
            $moveSql = "INSERT INTO krf_polyhouse_plant_details (
                                psono, roottype, sowingplantid, stockorder_no, locationid,
                                labourid, polyhouseid, projectid, lotno,
                                batchno, barcode, productid, productname,
                                tray_id, qtypertray, tray_position, bedrackname, bed_no,
                                planted_date, workingstatus, status, remarks, sowing_date, sowingdate_internal, sowinglotno_internal, sowingbatchno, dispatchdate_internal, created_by
                            )
                            SELECT
                                s.psono, s.roottype, s.sowingplantid, s.stockorder_no, ?,
                                s.labourid, ?, s.projectid, s.lotno,
                                s.sowingbatchno, s.barcode, s.productid,
                                s.productname, s.trayid, s.traycavity,
                                ?, ?, ?,
                                NOW(), 'Polyhouse', ?, s.remarks, s.sowing_date, s.sowingdate_internal, s.sowinglotno_internal, s.sowingbatchno, s.dispatchdate_internal, ?
                            FROM krf_polyhouse_sowingplant_details s
                            WHERE (s.germinationboxid = ? OR (s.germination_qrcode = ? AND s.germination_qrcode != '')) 
                              AND s.projectid = ? 
                              AND NOT EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid)
                              AND s.status NOT IN ('Removed', 'Readytosale', 'Dispatched')
                            LIMIT ?";

            $moveLimit = ($traycountinbox > 0) ? $traycountinbox : 100;
            $moveStmt = $krfconn->prepare($moveSql);
            $moveStmt->bind_param(
                 "sisssssisii",
                 $locationid,    // ? 1 (s)
                 $polyhouseid,   // ? 2 (i)
                 $trayposition,  // ? 3 (s)
                 $bedrackname,   // ? 4 (s)
                 $bedrackname,   // ? 5 (s)
                 $targetStatus,  // ? 6 (s)
                 $loginid,       // ? 7 (s)
                 $bid,           // ? 8 (i)
                 $boxQrcode,     // ? 9 (s)
                 $projectid,     // ? 10 (i)
                 $moveLimit      // ? 11 (i)
             );

            $moveStmt->execute();
            $inserted = $moveStmt->affected_rows;
            $moveStmt->close();

            if ($inserted > 0) {
                $totalInserted += $inserted;
                $workingstatus = "Polyhouse";
                $updatedat = date('Y-m-d H:i:s');
                $updStmt = $krfconn->prepare("UPDATE krf_polyhouse_sowingplant_details SET status=?, workingstatus=?, germinationboxid=?, updated_at=?, updated_by=? WHERE (germinationboxid=? OR (germination_qrcode=? AND germination_qrcode != '')) AND projectid=? AND status NOT IN ('Removed', 'Readytosale', 'Dispatched')");
                $updStmt->bind_param("ssissisi", $workingstatus, $workingstatus, $bid, $updatedat, $loginid, $bid, $boxQrcode, $projectid);
                $updStmt->execute();
                $updStmt->close();

                // Update box status
                $boxUpdSql = "UPDATE krf_polyhouse_germinated_boxes 
                                 SET allocated_traycount = ?, 
                                     status = 'ReceivedAtPolyhouse',
                                     received_by = ?,
                                     received_at = NOW()
                                 WHERE boxid = ?";
                $boxUpdStmt = $krfconn->prepare($boxUpdSql);
                $boxUpdStmt->bind_param("isi", $inserted, $loginid, $bid);
                $boxUpdStmt->execute();
                $boxUpdStmt->close();
            } else if ($alreadyCount > 0) {
                // If trays were already in polyhouse details, mark completed
                $boxUpdSql = "UPDATE krf_polyhouse_germinated_boxes 
                                 SET allocated_traycount = ?, 
                                     status = 'ReceivedAtPolyhouse',
                                     received_by = ?,
                                     received_at = NOW()
                                 WHERE boxid = ?";
                $boxUpdStmt = $krfconn->prepare($boxUpdSql);
                $boxUpdStmt->bind_param("isi", $alreadyCount, $loginid, $bid);
                $boxUpdStmt->execute();
                $boxUpdStmt->close();
                $totalInserted += $alreadyCount;
            } else {
                throw new Exception("Box $bid claimed $traycountinbox trays, but no matching available trays were found in sowing records for order #$stockorderno ($sowinglotno_internal).");
            }
        }

        if ($totalInserted === 0) {
            throw new Exception("Zero entries were inserted. Possible causes: no available trays for the stock order, or trays are already moved.");
        }
        $krfconn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Allocation completed successfully. Total $totalInserted trays moved.",
            "data" => [
                "allocatedtrays" => $totalInserted
            ]
        ]);

    } catch (Exception $e) {
        if (isset($krfconn) && $krfconn instanceof mysqli) {
            @$krfconn->rollback();
        }
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
        exit;
    }
} else if ($action == "GetAllocatedStockOrdersList") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $sql = "SELECT DISTINCT g.stockorderno, g.sowinglotno_internal, p.productname, g.boxpackingdate, g.boxopeningdate 
             FROM krf_polyhouse_germinated_boxes g
             LEFT JOIN product_master p ON g.productid = p.ProductID AND g.projectid = p.ProjectId
             WHERE g.status='Allocated' AND g.projectid = ?
             ORDER BY g.stockorderno ASC, g.sowinglotno_internal ASC";
    $stmt = $krfconn->prepare($sql);
    $response = [];
    if ($stmt) {
        $stmt->bind_param("i", $projectid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $availtrays = 0;
            $sowingdate_internal = '';
            
            // Count trays and get sowingdate_internal
            $stmtCount = $krfconn->prepare("SELECT COUNT(*) as cnt, MAX(sowingdate_internal) as sowingdate_internal FROM krf_polyhouse_sowingplant_details WHERE stockorder_no = ? AND sowinglotno_internal = ? AND projectid = ? AND status IN ('Allocated', 'Sowing')");
            if ($stmtCount) {
                $stmtCount->bind_param("ssi", $row['stockorderno'], $row['sowinglotno_internal'], $projectid);
                $stmtCount->execute();
                $cntRes = $stmtCount->get_result()->fetch_assoc();
                $availtrays = $cntRes['cnt'] ?? 0;
                $sowingdate_internal = $cntRes['sowingdate_internal'] ?? '';
                $stmtCount->close();
            }
            if ($availtrays > 0) {
                $formatted_boxopeningdate = !empty($row['boxopeningdate']) ? date('d-M-Y', strtotime($row['boxopeningdate'])) : 'N/A';
                $formatted_sowingdate = !empty($row['boxpackingdate']) ? date('d-M-Y', strtotime($row['boxpackingdate'])) : 'N/A';
                
                $response[] = [
                    'stockorderno' => $row['stockorderno'],
                    'sowinglotno_internal' => $row['sowinglotno_internal'],
                    'productname' => $row['productname'] ?? '',
                    'available_trays' => $availtrays,
                    'boxopeningdate' => $formatted_boxopeningdate,
                    'sowingdate_internal' => $formatted_sowingdate
                ];
            }
        }
        $stmt->close();
    }
    echo json_encode($response);
    exit;
} else if ($action == "GetBoxesByStockOrder") {
    $projectid = (int)($_POST['projectid'] ?? 0);
    $stockorderno = $_POST['stockorderno'] ?? '';
    $sowinglotno_internal = $_POST['sowinglotno_internal'] ?? '';
    
    $sql = "SELECT boxid, qrcode, roottype, sowinglotno_internal, traycountinbox, polyhouseid 
            FROM krf_polyhouse_germinated_boxes 
            WHERE status='Allocated' AND stockorderno = ? AND sowinglotno_internal = ? AND projectid = ?
            ORDER BY boxid ASC";
    $stmt = $krfconn->prepare($sql);
    $response = [];
    if ($stmt) {
        $stmt->bind_param("ssi", $stockorderno, $sowinglotno_internal, $projectid);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $availtrays = 0;
            // 1. Count trays already linked that are not yet received (status IN ('Allocated', 'Sowing') OR orphaned Polyhouse status)
            $stmtCount = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_polyhouse_sowingplant_details s WHERE s.germinationboxid = ? AND s.projectid = ? AND (s.status IN ('Allocated', 'Sowing') OR (s.status = 'Polyhouse' AND NOT EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid)))");
            if ($stmtCount) {
                $stmtCount->bind_param("ii", $row['boxid'], $projectid);
                $stmtCount->execute();
                $cntRes = $stmtCount->get_result()->fetch_assoc();
                $availtrays = $cntRes['cnt'] ?? 0;
                $stmtCount->close();
            }
            
            // 2. If no trays are linked to this box yet, the available trays is the minimum of the box's traycountinbox and
            // the remaining unlinked trays for this stock order and sowing lot in the database.
            if ($availtrays == 0) {
                $stmtPH = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_polyhouse_sowingplant_details s WHERE s.germinationboxid = ? AND s.projectid = ? AND s.status = 'Polyhouse' AND EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid)");
                $hasPH = 0;
                if ($stmtPH) {
                    $stmtPH->bind_param("ii", $row['boxid'], $projectid);
                    $stmtPH->execute();
                    $phRes = $stmtPH->get_result()->fetch_assoc();
                    $hasPH = $phRes['cnt'] ?? 0;
                    $stmtPH->close();
                }
                if ($hasPH == 0) {
                    // Count unlinked trays for this stock order and sowing lot
                    $stmtUnlinked = $krfconn->prepare("
                        SELECT COUNT(*) as cnt 
                        FROM krf_polyhouse_sowingplant_details s
                        LEFT JOIN krf_polyhouse_plant_details p ON s.sowingplantid = p.sowingplantid
                        WHERE s.projectid = ? 
                          AND s.stockorder_no = ? 
                          AND (s.germinationboxid IS NULL OR s.germinationboxid = 0)
                          AND (
                              s.status IN ('Allocated', 'Sowing')
                              OR (s.status = 'Polyhouse' AND p.sowingplantid IS NULL)
                          )
                          AND s.sowinglotno_internal = ?
                    ");
                    $unlinkedCount = 0;
                    if ($stmtUnlinked) {
                        $stmtUnlinked->bind_param("iss", $projectid, $stockorderno, $row['sowinglotno_internal']);
                        $stmtUnlinked->execute();
                        $unlinkedRes = $stmtUnlinked->get_result()->fetch_assoc();
                        $unlinkedCount = $unlinkedRes['cnt'] ?? 0;
                        $stmtUnlinked->close();
                    }
                    $availtrays = min((int)$row['traycountinbox'], $unlinkedCount);
                }
            }
            
            if ($availtrays > 0) {
                $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $row['polyhouseid']);
                $polyhousename = isset($poldata[0]['polyhousename']) ? $poldata[0]['polyhousename'] : '';
                $locationid = isset($poldata[0]['locationid']) ? $poldata[0]['locationid'] : '';
                
                $response[] = [
                    'boxid' => $row['boxid'],
                    'qrcode' => $row['qrcode'],
                    'roottype' => $row['roottype'],
                    'sowinglotno_internal' => $row['sowinglotno_internal'],
                    'traycountinbox' => $row['traycountinbox'],
                    'available_trays' => $availtrays,
                    'polyhouseid' => $row['polyhouseid'],
                    'polyhousename' => $polyhousename,
                    'polyhouse_locationid' => $locationid
                ];
            }
        }
        $stmt->close();
    }
    echo json_encode($response);
    exit;
} else if ($action == "UpdateMultipleManualTrayAllocationsToPolyhouse") {
    $companyid = $_POST['companyid'] ?? '';
    $subcompanyid = $_POST['subcompanyid'] ?? '';
    $projectid = (int) ($_POST['projectid'] ?? 0);
    $loginid = $_POST['loginid'] ?? '';
    $polyhouseid = (int) ($_POST['polyhouseid'] ?? 0);
    $bedrackname = $_POST['bedrackname'] ?? '';
    $trayposition = $_POST['placeofrow'] ?? '';
    
    $allocationsRaw = $_POST['allocations'] ?? '';
    $allocations = json_decode($allocationsRaw, true);

    if (empty($allocations) || !is_array($allocations)) {
        echo json_encode(["status" => "error", "message" => "No box allocations provided."]);
        exit;
    }

    try {
        $krfconn->begin_transaction();

        // 1. GET POLYHOUSE LOCATION
        $locStmt = $krfconn->prepare("SELECT locationid FROM krf_polyhouse_master WHERE polyhouseid=?");
        $locStmt->bind_param("i", $polyhouseid);
        $locStmt->execute();
        $phData = $locStmt->get_result()->fetch_assoc();
        $locationid = $phData['locationid'] ?? null;
        $locStmt->close();

        if (!$locationid) {
            throw new Exception("Polyhouse location not found.");
        }

        $totalMoved = 0;

        foreach ($allocations as $alloc) {
            $bid = (int)($alloc['boxid'] ?? 0);
            $qty = (int)($alloc['qty'] ?? 0);

            if ($bid <= 0 || $qty <= 0) continue;

            // 2. Get box info
            $bStmt = $krfconn->prepare("SELECT stockorderno, traycountinbox, sowinglotno_internal FROM krf_polyhouse_germinated_boxes WHERE boxid = ?");
            $bStmt->bind_param("i", $bid);
            $bStmt->execute();
            $bRes = $bStmt->get_result()->fetch_assoc();
            $bStmt->close();

            if (!$bRes) {
                throw new Exception("Germination box ID $bid not found.");
            }

            $stockorderno = $bRes['stockorderno'];
            $traycountinbox = (int) $bRes['traycountinbox'];
            $sowinglotno_internal = $bRes['sowinglotno_internal'];

            // 3. Ensuring Linkage: Count trays already linked to this box (including orphaned Polyhouse status trays)
            $checkStmt = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_polyhouse_sowingplant_details s WHERE s.germinationboxid = ? AND s.projectid = ? AND (s.status IN ('Allocated', 'Sowing') OR (s.status = 'Polyhouse' AND NOT EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid)))");
            $checkStmt->bind_param("ii", $bid, $projectid);
            $checkStmt->execute();
            $checkRes = $checkStmt->get_result()->fetch_assoc();
            $checkStmt->close();

            $availableInBox = (int)($checkRes['cnt'] ?? 0);

            if ($availableInBox == 0) {
                // Find matching unlinked sowing plant details
                $selectLinkSql = "SELECT s.sowingplantid 
                                  FROM krf_polyhouse_sowingplant_details s
                                  LEFT JOIN krf_polyhouse_plant_details p ON s.sowingplantid = p.sowingplantid
                                  WHERE s.projectid = ? 
                                    AND s.stockorder_no = ? 
                                    AND (s.germinationboxid IS NULL OR s.germinationboxid = 0)
                                    AND (
                                        s.status IN ('Allocated', 'Sowing')
                                        OR (s.status = 'Polyhouse' AND p.sowingplantid IS NULL)
                                    )
                                    AND s.sowinglotno_internal = ?
                                  LIMIT ?";
                $selectLinkStmt = $krfconn->prepare($selectLinkSql);
                $linkLimit = max($qty, $traycountinbox);
                $selectLinkStmt->bind_param("issi", $projectid, $stockorderno, $sowinglotno_internal, $linkLimit);
                $selectLinkStmt->execute();
                $sRes = $selectLinkStmt->get_result();
                $idsToLink = [];
                while ($sRow = $sRes->fetch_assoc()) {
                    $idsToLink[] = (int)$sRow['sowingplantid'];
                }
                $selectLinkStmt->close();

                if (!empty($idsToLink)) {
                    $idListToLink = implode(",", $idsToLink);
                    $linkSql = "UPDATE krf_polyhouse_sowingplant_details 
                                SET germinationboxid = ? 
                                WHERE sowingplantid IN ($idListToLink)";
                    $linkStmt = $krfconn->prepare($linkSql);
                    $linkStmt->bind_param("i", $bid);
                    $linkStmt->execute();
                    $linkedCount = $linkStmt->affected_rows;
                    $linkStmt->close();
                }

                $checkStmt = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_polyhouse_sowingplant_details s WHERE s.germinationboxid = ? AND s.projectid = ? AND (s.status IN ('Allocated', 'Sowing') OR (s.status = 'Polyhouse' AND NOT EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid)))");
                $checkStmt->bind_param("ii", $bid, $projectid);
                $checkStmt->execute();
                $checkRes = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                $availableInBox = (int)($checkRes['cnt'] ?? 0);
            }

            // if ($qty > $availableInBox) {
            //     throw new Exception("Cannot allocate $qty trays. Only $availableInBox unreceived trays are available for Box $bid.");
            // }

            // 4. Move N trays for this box
            $moveSql = "INSERT INTO krf_polyhouse_plant_details (
                                psono, roottype, sowingplantid, stockorder_no, locationid,
                                labourid, polyhouseid, projectid, lotno,
                                batchno, barcode, productid, productname,
                                tray_id, qtypertray, tray_position, bedrackname, bed_no,
                                planted_date, workingstatus, status, remarks, sowing_date, sowingdate_internal, sowinglotno_internal, sowingbatchno, dispatchdate_internal, created_by
                            )
                            SELECT
                                s.psono, s.roottype, s.sowingplantid, s.stockorder_no, ?,
                                s.labourid, ?, s.projectid, s.lotno,
                                s.sowingbatchno, s.barcode, s.productid,
                                s.productname, s.trayid, s.traycavity,
                                ?, ?, ?,
                                NOW(), 'Polyhouse', 'Polyhouse', s.remarks, s.sowing_date, s.sowingdate_internal, s.sowinglotno_internal, s.sowingbatchno, s.dispatchdate_internal, ?
                            FROM krf_polyhouse_sowingplant_details s
                            WHERE s.germinationboxid = ? 
                              AND s.projectid = ? 
                              AND (
                                  s.status IN ('Allocated', 'Sowing')
                                  OR (s.status = 'Polyhouse' AND NOT EXISTS (SELECT 1 FROM krf_polyhouse_plant_details p WHERE p.sowingplantid = s.sowingplantid))
                              )
                            LIMIT ?";

            $moveStmt = $krfconn->prepare($moveSql);
            $moveStmt->bind_param(
                "sissssiii",
                $locationid,
                $polyhouseid,
                $trayposition,
                $bedrackname,
                $bedrackname,
                $loginid,
                $bid,
                $projectid,
                $qty
            );

            $moveStmt->execute();
            $inserted = $moveStmt->affected_rows;
            $moveStmt->close();

            if ($inserted > 0) {
                $totalMoved += $inserted;
                $workingstatus = "Polyhouse";
                $updatedat = date('Y-m-d H:i:s');
                
                $subSelect = "SELECT sowingplantid FROM krf_polyhouse_plant_details WHERE polyhouseid = ? AND bedrackname = ? AND status = 'Polyhouse' ORDER BY polyhouseplantid DESC LIMIT ?";
                $subStmt = $krfconn->prepare($subSelect);
                $subStmt->bind_param("isi", $polyhouseid, $bedrackname, $inserted);
                $subStmt->execute();
                $subRes = $subStmt->get_result();
                $sowingPlantIds = [];
                while ($row = $subRes->fetch_assoc()) {
                    $sowingPlantIds[] = $row['sowingplantid'];
                }
                $subStmt->close();

                if (!empty($sowingPlantIds)) {
                    $idList = implode(",", array_map('intval', $sowingPlantIds));
                    $updSql = "UPDATE krf_polyhouse_sowingplant_details 
                               SET status='Polyhouse', workingstatus='Polyhouse', updated_at=?, updated_by=? 
                               WHERE sowingplantid IN ($idList) AND projectid=?";
                    $updStmt = $krfconn->prepare($updSql);
                    $updStmt->bind_param("ssi", $updatedat, $loginid, $projectid);
                    $updStmt->execute();
                    $updStmt->close();
                }
                

                $checkStmt = $krfconn->prepare("SELECT COUNT(*) as cnt FROM krf_polyhouse_sowingplant_details WHERE germinationboxid = ? AND projectid = ? AND status IN ('Allocated', 'Sowing')");
                $checkStmt->bind_param("ii", $bid, $projectid);
                $checkStmt->execute();
                $checkRes = $checkStmt->get_result()->fetch_assoc();
                $checkStmt->close();
                $remainingTrays = (int)($checkRes['cnt'] ?? 0);

                $boxInfoStmt = $krfconn->prepare("SELECT allocated_traycount FROM krf_polyhouse_germinated_boxes WHERE boxid = ?");
                $boxInfoStmt->bind_param("i", $bid);
                $boxInfoStmt->execute();
                $boxInfo = $boxInfoStmt->get_result()->fetch_assoc();
                $boxInfoStmt->close();
                $currentAllocatedCount = (int)($boxInfo['allocated_traycount'] ?? 0);
                $newAllocatedCount = $currentAllocatedCount + $inserted;

                $boxStatus = ($remainingTrays == 0) ? 'ReceivedAtPolyhouse' : 'Allocated';

                $boxUpdSql = "UPDATE krf_polyhouse_germinated_boxes 
                                 SET allocated_traycount = ?, 
                                     status = ?,
                                     received_by = ?,
                                     received_at = NOW()
                                 WHERE boxid = ?";
                $boxUpdStmt = $krfconn->prepare($boxUpdSql);
                $boxUpdStmt->bind_param("isii", $newAllocatedCount, $boxStatus, $loginid, $bid);
                $boxUpdStmt->execute();
                $boxUpdStmt->close();
            }
        }
        $krfconn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Manual allocation completed successfully. Total $totalMoved trays moved to Bed $bedrackname.",
            "data" => [
                "allocatedtrays" => $totalMoved
            ]
        ]);

    } catch (Exception $e) {
        if (isset($krfconn) && $krfconn instanceof mysqli) {
            @$krfconn->rollback();
        }
        echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    }
    exit;
} else if ($action == "PolyhouseTrayUsageDetailsFromPolyhousePlant") {
    $projectid = $_POST['projectid'];
    $polyhouseid = $_POST['polyhouseid'];
    $poldata = getPolyhouseData($krfconn, $projectid, "polyhouseid", $reskey['polyhouseid']);
    $locationid = $poldata[0]['locationid'];
    $pltru = getPolyhouseTrayUsageDetails($krfconn, $locationid, $polyhouseid, $projectid);
    echo json_encode($pltru);
} else if ($action == "PolyhouseCapacitydetails") {
    $projectid = $_POST['projectid'];
    $locationid = $_POST['locationid'];
    $polyhouseid = $_POST['polyhouseid'];
    $pltru = getPolyhouseTrayUsageDetails($krfconn, $locationid, $polyhouseid, $projectid);
    echo json_encode($pltru);

} else if ($action == "GetPolyhouseTrayUsageByLocation") {
    $projectid = $_POST['projectid'];
    $locationid = $_POST['locationid'];

    // Calls existing function getting ALL polyhouses for this location
    // Note: passing null for polyhouseid triggers the "ALL" logic in the function
    $polyhouseData = getPolyhouseTrayUsageDetails($krfconn, $locationid, null, $projectid);

    echo json_encode($polyhouseData);
} else if ($action == "GetBedWisePlantDetails") {
    $projectid = $_POST['projectid'];
    $polyhouseid = $_POST['polyhouseid'];
    $bed_no = $_POST['bed_no'];

    $sql = "SELECT stockorder_no, productname, COUNT(polyhouseplantid) as tray_count, MIN(planted_date) as planted_date
            FROM krf_polyhouse_plant_details 
            WHERE projectid = ? AND polyhouseid = ? AND bed_no = ? AND status = 'Polyhouse'
            GROUP BY stockorder_no, productname";

    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("iis", $projectid, $polyhouseid, $bed_no);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    echo json_encode(["status" => "success", "data" => $data]);
} else if ($action == "GetProductionOrdersForMovement") {
    $projectid = $_POST['projectid'] ?? 0;
    if (empty($projectid)) {
        echo json_encode([]);
        exit;
    }
    $sql = "SELECT DISTINCT p.stockorder_no, p.productname, p.roottype, pm.IsGrafted AS producttype FROM krf_polyhouse_plant_details p LEFT JOIN product_master pm 
            ON pm.ProductID = p.productid AND pm.ProjectId = p.projectid
        WHERE p.projectid = ? ORDER BY p.stockorder_no DESC";
    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode([
            "status" => "error",
            "message" => $krfconn->error
        ]);
        exit;
    }
    $stmt->bind_param("i", $projectid);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_all(MYSQLI_ASSOC);
    echo json_encode($data);

} else if ($action == "GetMovementDetails") {
    $projectid = $_POST['projectid'];
    $qrcode = $_POST['qrcode'] ?? '';
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $roottype = $_POST['roottype'] ?? '';

    // Step 1: Fetch specific record details with Order and Product info
    $sqlInitial = "SELECT p.*, ph.polyhousename, loc.LocationName as current_location_name, loc.OfficeLat, loc.OfficeLng,
                   so.expected_delivery_date, c.client_name as clientname, pm.IsGrafted as product_type
                   FROM krf_polyhouse_plant_details p 
                   LEFT JOIN krf_polyhouse_master ph ON p.polyhouseid = ph.polyhouseid AND p.projectid = ph.projectid
                   LEFT JOIN {$config['db_hrm']}.hrm_AttendanceLocationMaster loc ON p.locationid = loc.LocationId
                   LEFT JOIN krf_production_stockorder so ON p.stockorder_no = so.stockorder_no AND p.projectid = so.projectid
                   LEFT JOIN krf_client_master c ON so.clientid = c.client_id AND so.projectid = c.projectid
                   LEFT JOIN product_master pm ON p.productid = pm.ProductID AND p.projectid = pm.ProjectId
                   WHERE p.projectid = ?";
    $params = [$projectid];
    $types = "i";

    if (!empty($qrcode)) {
        $sqlInitial .= " AND p.barcode = ?";
        $params[] = $qrcode;
        $types .= "s";
    } else if (!empty($stockorder_no)) {
        $sqlInitial .= " AND p.stockorder_no = ? AND p.roottype = ? LIMIT 1";
        $params[] = $stockorder_no;
        $params[] = $roottype;
        $types .= "ss";
    } else {
        echo json_encode(["status" => "error", "message" => "QR or Stock Order No required"]);
        exit;
    }

    $stmt = $krfconn->prepare($sqlInitial);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $data = $res->fetch_assoc();

    if ($data) {
        // Step 2: Fetch aggregates for this order and roottype (Total Tray and Total Qty)
        $aggSql = "SELECT COUNT(*) as total_trays, SUM(CAST(IFNULL(qtypertray,0) AS DECIMAL(10,2))) as total_qty 
                   FROM krf_polyhouse_plant_details 
                   WHERE projectid = ? AND stockorder_no = ? AND roottype = ?";
        $aggStmt = $krfconn->prepare($aggSql);
        $aggStmt->bind_param("iss", $projectid, $data['stockorder_no'], $data['roottype']);
        $aggStmt->execute();
        $aggRes = $aggStmt->get_result()->fetch_assoc();

        $data['total_trays'] = $aggRes['total_trays'] ?? 0;
        $data['total_qty'] = $aggRes['total_qty'] ?? 0;

        // Step 3: If Grafted, fetch Root and Scion details
        if (strcasecmp($data['product_type'], 'Grafted') === 0) {
            $compSql = "SELECT roottype, bed_no, COUNT(*) as tray_count, SUM(qtypertray) as total_qty, tray_position
                        FROM krf_polyhouse_plant_details 
                        WHERE projectid = ? AND stockorder_no = ? AND (roottype = 'Root' OR roottype = 'Scion')
                        GROUP BY roottype, bed_no, tray_position";
            $compStmt = $krfconn->prepare($compSql);
            $compStmt->bind_param("is", $projectid, $data['stockorder_no']);
            $compStmt->execute();
            $compRes = $compStmt->get_result();
            
            $components = ['Root' => [], 'Scion' => []];
            while ($row = $compRes->fetch_assoc()) {
                $components[$row['roottype']][] = $row;
            }
            $data['component_details'] = $components;
        }

        echo json_encode(["status" => "success", "data" => $data]);
    } else {
                echo json_encode(["status" => "error", "message" => "No plant details found"]);
    }

} else if ($action == "SavePlantMovement") {
    $projectid = $_POST['projectid'];
    $stockorder_no = $_POST['stockorder_no'] ?? '';
    $roottype = $_POST['roottype'];
    $to_stage = $_POST['to_stage'];
    $move_date = $_POST['move_date'];
    $next_move_date = $_POST['next_move_date'];
    $locationid = $_POST['locationid'] ?? '';
    $is_transport = $_POST['is_transport'] ?? 'No';

    $from_location_id = $_POST['from_location_id'] ?? '';
    $from_location_name = $_POST['from_location_name'] ?? '';
    $from_lat = $_POST['from_lat'] ?? '';
    $from_lon = $_POST['from_lon'] ?? '';
    $to_locationid = $_POST['to_locationid'] ?? '';
    $to_location_name = $_POST['to_location_name'] ?? '';
    $to_lat = $_POST['to_lat'] ?? '';
    $to_lon = $_POST['to_lon'] ?? '';
    $polyhouseid = $_POST['polyhouseid'] ?? '';
    $loginid = $_POST['loginid'];
    if (strtolower($to_stage) == "readytosale") {
        // 1. Fetch batch info
        $sql = "SELECT p.productid, p.productname, p.roottype, p.clientid, pm.IsGrafted AS producttype 
                FROM krf_polyhouse_plant_details p 
                LEFT JOIN product_master pm ON pm.ProductID = p.productid AND pm.ProjectId = p.projectid 
                WHERE p.projectid = ? AND p.stockorder_no = ? AND p.roottype = ? LIMIT 1";

        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param("iss", $projectid, $stockorder_no, $roottype);
        $stmt->execute();
        $batchBase = $stmt->get_result()->fetch_assoc();
        
        if (!$batchBase) {
            echo json_encode(["status" => "error", "message" => "Batch details not found."]);
            exit;
        }

        // 🔹 🌿 Block individual components (Root/Scion) of Grafted products from being sold
        if (strcasecmp($batchBase['producttype'], 'Grafted') === 0 && 
            (strcasecmp($batchBase['roottype'], 'Root') === 0 || strcasecmp($batchBase['roottype'], 'Scion') === 0)) {
            echo json_encode(["status" => "error", "message" => "Grafting missing this batch. Pre-combined components cannot be sold."]);
            exit;
        }

        // 2. Fetch all Healthy records for this batch
        $sqlHealthy = "SELECT * FROM krf_polyhouse_plant_details 
                       WHERE projectid = ? AND stockorder_no = ? AND roottype = ? 
                       AND (health_status = 'Healthy' OR health_status = 'Healty')";
        $stmtH = $krfconn->prepare($sqlHealthy);
        $stmtH->bind_param("iss", $projectid, $stockorder_no, $roottype);
        $stmtH->execute();
        $resHealthy = $stmtH->get_result();

        if ($resHealthy->num_rows == 0) {
            echo json_encode(["status" => "error", "message" => "No Healthy plants found in this batch to move to Sale."]);
            exit;
        }

        $successCount = 0;
        while ($row = $resHealthy->fetch_assoc()) {
            // Insert into krf_readytosale_stocks
            $insSql = "INSERT INTO krf_readytosale_stocks 
                       (stockorder_no, psono, projectid, productionclientid, productid, productname, trayid, qtypertray, locationid, locationname, polyhouseplantid, status, created_by, created_at)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ReadyToSale', ?, NOW())";
            $stmtI = $krfconn->prepare($insSql);
            
            $psono = $row['psono'] ?? '';
            $clientid = $row['clientid'] ?? '';
            $pid = $row['productid'] ?? '';
            $pname = $row['productname'] ?? '';
            $trayid = $row['trayid'] ?? 0;
            $qty = $row['qtypertray'] ?? 0;
            $phpid = $row['polyhouseplantid'] ?? '';
            
            $stmtI->bind_param("ssisssisssss", $stockorder_no, $psono, $projectid, $clientid, $pid, $pname, $trayid, $qty, $to_locationid, $to_location_name, $phpid, $loginid);
            
            if ($stmtI->execute()) {
                $successCount++;
                // Update source record status
                $updSrc = "UPDATE krf_polyhouse_plant_details SET workingstatus = 'ReadytoSale', status = 'ReadytoSale' WHERE polyhouseplantid = ?";
                $stmtU = $krfconn->prepare($updSrc);
                $stmtU->bind_param("s", $phpid);
                $stmtU->execute();
            }
        }

        echo json_encode(["status" => "success", "message" => "Movement successful: $successCount Healthy trays moved to Ready to Sale."]);
        exit;
    } else {
        // 1. Get current data for history logging
        $sqlFetch = "SELECT p.productid, p.workingstatus, p.productname, p.qtypertray,
                     (SELECT COUNT(polyhouseplantid) FROM krf_polyhouse_plant_details WHERE projectid = p.projectid AND stockorder_no = p.stockorder_no AND roottype = p.roottype) as agg_trays,
                     (SELECT SUM(qtypertray) FROM krf_polyhouse_plant_details WHERE projectid = p.projectid AND stockorder_no = p.stockorder_no AND roottype = p.roottype) as agg_qty
                     FROM krf_polyhouse_plant_details p
                     WHERE p.projectid = ? AND p.stockorder_no = ? AND p.roottype = ? LIMIT 1";
        $stmtFetch = $krfconn->prepare($sqlFetch);
        $stmtFetch->bind_param("iss", $projectid, $stockorder_no, $roottype);
        $stmtFetch->execute();
        $batchInfo = $stmtFetch->get_result()->fetch_assoc();
        $stmtFetch->close();

        $productid = $batchInfo['productid'] ?? 0;
        $from_stage = $batchInfo['workingstatus'] ?? '';
        $productname = $batchInfo['productname'] ?? '';
        $traycount = $batchInfo['agg_trays'] ?? 0;
        $traycavity = $batchInfo['qtypertray'] ?? 0;
        $totalplantcount = $batchInfo['agg_qty'] ?? 0;

        // 2. Insert into History (Check Duplicate)
        $checkDup = "SELECT id FROM krf_plant_move_history 
                    WHERE stockorder_no = ? AND to_stage = ? AND move_at = ? AND productid = ?";
        $stmtCheck = $krfconn->prepare($checkDup);
        $stmtCheck->bind_param("ssss", $stockorder_no, $to_stage, $move_date, $productid);
        $stmtCheck->execute();
        $hasHistory = ($stmtCheck->get_result()->num_rows > 0);
        $stmtCheck->close();

        if (!$hasHistory) {
            $sqlHist = "INSERT INTO krf_plant_move_history 
                        (productname, stockorder_no, productid, from_stage, to_stage, move_at, next_move_date, status, remark, created_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmtHist = $krfconn->prepare($sqlHist);
            $remark = "Transport: $is_transport";
            $histStatus = $to_stage;
            $stmtHist->bind_param("sssssssssi", $productname, $stockorder_no, $productid, $from_stage, $to_stage, $move_date, $next_move_date, $histStatus, $remark, $loginid);
            $stmtHist->execute();
            $stmtHist->close();
        }

        // 2.b Insert Vehicle Travel Details if Transport is Yes
        if ($is_transport == 'Yes') {   
            
            // Fetch coordinates for Move To location if needed, but we used them in frontend
            // For now, we take them from POST as sent by frontend
            $vData = [
                'trip_id' => 'TRP-' . $stockorder_no . '-' . time(),
                'stockorder_no' => $stockorder_no,
                'productid' => $productid,
                'roottype' => $roottype,
                'send_traycount' => $traycount,
                'send_traycavity' => $traycavity,
                'send_totalplantcount' => $totalplantcount,
                'send_remark' => "Moved from $from_stage to $to_stage",
                'vehicle_id' => $_POST['vehicle_id'] ?? 0,
                'vehicle_no' => $_POST['vehicle_no'] ?? '',
                'driver_name' => $_POST['driver_name'] ?? '',
                'driver_mobileno' => $_POST['driver_mobile'] ?? '',
                'from_location_id' => $_POST['from_location_id'] ?? 0,
                'from_location_name' => $_POST['from_location_name'] ?? '',
                'from_lat' => $_POST['from_lat'] ?? '', // Note: we need to ensure these are in POST
                'from_lon' => $_POST['from_lon'] ?? '',
                'to_location_id' => $_POST['to_locationid'],
                'to_location_name' => $_POST['to_location_name'] ?? '',
                'to_lat' => $_POST['to_lat'] ?? '',
                'to_lon' => $_POST['to_lon'] ?? '',
                'distance_km' => $_POST['distance_km'] ?? 0,
                'freight_amount' => $_POST['freight_amount'] ?? 0,
                'movefor' => 'Stage Transition',
                'created_by' => $loginid
            ];
            insertVehicleTravelDetails($krfconn, $vData);
        }
        
        // 3. Update main details
        $full_remark = "Moved on: $move_date, Next Move: $next_move_date | Transport: $is_transport";

        $sql = "UPDATE krf_polyhouse_plant_details 
                SET status =?, workingstatus = ?, remarks = ?, updated_at = NOW(), updated_by = ?, 
                    locationid = ?, polyhouseid = ?, move_at=?, next_stage=?, next_move_date=? 
                WHERE projectid = ? AND stockorder_no=? AND roottype=?";
        
        // Parameters: status(s), workingstatus(s), remarks(s), loginid(s), locationid(s), polyhouseid(s), move_at(s), next_stage(s), next_move_date(s), projectid(i), stockorder_no(s), roottype(s)
        $params = [$to_stage, $to_stage, $full_remark, $loginid, $to_locationid, $polyhouseid, $move_date, $to_stage, $next_move_date, $projectid, $stockorder_no, $roottype];
        $types = "ssssssssssss";
        
        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Plant movement saved successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to save: " . $stmt->error]);
        }
    }
}
?>
