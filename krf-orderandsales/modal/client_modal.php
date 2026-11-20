<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (ob_get_level() == 0) {
    ob_start();
}

function sendJsonResponse($data) {
    if (ob_get_length()) {
        ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit();
}

require_once(__DIR__ . '/../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require_once(__DIR__ . '/../../loginCell/functions/login_functions.php');
require_once(__DIR__ . '/../functions/client_functions.php');

$action = $_GET['action'] ?? '';

if ($action == "clientDetailsFrommaster") {
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$sortby = $_POST['sortby'] ?? ''; 
	$sortbyvalue = $_POST['sortbyvalue'] ?? ''; 	 
	$client_data = getClientDetailsInfo($krfconn, $companyid, $subcompanyid, $projectid, $sortby, $sortbyvalue);	 
	sendJsonResponse($client_data);
} else if ($action == "newclientsavetodb") {
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$client_name = trim($_POST['client_name'] ?? '');
	$invoicing_address = trim($_POST['invoicing_address'] ?? '');
	$phone = trim($_POST['phone'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$districtname = trim($_POST['districtname'] ?? '');
	$statename = trim($_POST['statename'] ?? '');
	$cityname = trim($_POST['cityname'] ?? '');
	$tahseel = trim($_POST['tahseel'] ?? '');
	$stateid = trim($_POST['stateid'] ?? '');
	$districtid = trim($_POST['districtid'] ?? '');

	$response = saveClient($krfconn, $companyid, $subcompanyid, $projectid, '', $client_name, $invoicing_address, $phone, $email, $districtname, $statename, false, $cityname, $stateid, $districtid, $tahseel);
	sendJsonResponse([$response]);
} else if ($action == "GetClientDeliveryAddressData") {
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$clientid = $_POST['clientid'] ?? '';
	$cldeliveryadd = getDeliveryLocations($krfconn, $projectid, $clientid);
	sendJsonResponse($cldeliveryadd);
} else if ($action == "GetUniqeClientDeliveryAddressData") {
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$clientid = $_POST['clientid'] ?? '';
	$hhdeliveryadd = getUniqueDeliveryAddresses($krfconn, $projectid, $clientid);
	sendJsonResponse($hhdeliveryadd);
} else if ($action == "getStatesFromMaster") {
	$states = getAllStatesMaster($krfconn);
	sendJsonResponse($states);
} else if ($action == "getDistrictsFromMaster") {
	$state = $_POST['state'] ?? $_GET['state'] ?? '';
	$districts = getDistrictsByStateMaster($krfconn, $state);
	sendJsonResponse($districts);
} else if ($action == "getStateByDistrict") {
	$district = $_POST['district'] ?? $_GET['district'] ?? '';
	$stateInfo = getStateByDistrictMaster($krfconn, $district);
	sendJsonResponse($stateInfo);
} else if ($action == "saveProductEnquiry") {
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$clientid = $_POST['clientid'] ?? '';
	$clientname = trim($_POST['clientname'] ?? '');
	$mobileno = trim($_POST['mobileno'] ?? '');
	$emailid = trim($_POST['emailid'] ?? '');
	$stateid = trim($_POST['stateid'] ?? '');
	$statename = trim($_POST['statename'] ?? '');
	$districtid = trim($_POST['districtid'] ?? '');
	$districtname = trim($_POST['districtname'] ?? '');
	$cityname = trim($_POST['cityname'] ?? '');
	$address = trim($_POST['address'] ?? '');
	$product_name = trim($_POST['product_name'] ?? '');
	$quantity = trim($_POST['quantity'] ?? '');
	$expected_date = trim($_POST['expected_date'] ?? '');
	$remarks = trim($_POST['remarks'] ?? '');
	$created_by = $_POST['created_by'] ?? '';
	$priority = $_POST['priority'] ?? '';

    $checkSql = "SELECT COUNT(*) AS cnt 
                 FROM krf_client_master 
                 WHERE companyid=? AND subcompanyid=? AND projectid=? 
                   AND client_name=? AND phones=? ";
    $checkStmt = $krfconn->prepare($checkSql);
    if (!$checkStmt) {
        throw new Exception("Prepare failed: " . $krfconn->error);
    }
    $checkStmt->bind_param("sssss", $companyid, $subcompanyid, $projectid, $client_name, $mobileno);
    $checkStmt->execute();
    $res = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();
    if ($res['cnt'] <= 0) {       
		$response = saveClient($krfconn, $companyid, $subcompanyid, $projectid, '', $clientname, '', $mobileno, $emailid, $districtname, $statename, false, $cityname, $stateid, $districtid);
    }

	if (empty($clientname) || empty($mobileno) || empty($product_name)) {
		sendJsonResponse(['status' => 'error', 'message' => 'Client Name, Mobile Number, and Demanded Product Name are required.']);
	}

	// Generate Enquiry No
	$enquiry_no = 'ENQ' . date('Ymd') . rand(100, 999);

	// Format expected date
	$exp_date_sql = null;
	if (!empty($expected_date)) {
		$exp_date_sql = date('Y-m-d', strtotime($expected_date));
	}

	$stmt = $krfconn->prepare("INSERT INTO krf_product_enquiries 
		(enquiry_no, companyid, subcompanyid, projectid, clientid, clientname, mobileno, emailid, stateid, statename, districtid, districtname, cityname, address, product_name, quantity, expected_date, remarks, created_by, priority)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
	
	if (!$stmt) {
		sendJsonResponse(['status' => 'error', 'message' => 'Database prepare failed: ' . $krfconn->error]);
	}

	$stmt->bind_param("ssssssssssssssssssss",
		$enquiry_no, $companyid, $subcompanyid, $projectid, $clientid,
		$clientname, $mobileno, $emailid, $stateid, $statename,
		$districtid, $districtname, $cityname, $address,
		$product_name, $quantity, $exp_date_sql, $remarks, $created_by, $priority
	);

	if ($stmt->execute()) {
		sendJsonResponse([
			'status' => 'success',
			'message' => 'Product enquiry saved successfully! Enquiry No: ' . $enquiry_no,
			'enquiry_no' => $enquiry_no
		]);
	} else {
		sendJsonResponse(['status' => 'error', 'message' => 'Failed to save enquiry: ' . $stmt->error]);
	}
	$stmt->close();
} else if ($action == "getAllProductEnquiries") {
	$companyid = $_POST['companyid'] ?? $_GET['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? $_GET['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? $_GET['projectid'] ?? '';

	$where = "WHERE 1=1";
	$params = [];
	$types = "";

	$datefrom = $_POST['datefrom'] ?? $_GET['datefrom'] ?? '';
	$dateto = $_POST['dateto'] ?? $_GET['dateto'] ?? '';

	if (!empty($projectid)) {
		$where .= " AND (projectid = ? OR projectid = '' OR projectid IS NULL)";
		$params[] = $projectid;
		$types .= "s";
	}

	if (!empty($datefrom)) {
		$where .= " AND DATE(created_at) >= ?";
		$params[] = $datefrom;
		$types .= "s";
	}
	if (!empty($dateto)) {
		$where .= " AND DATE(created_at) <= ?";
		$params[] = $dateto;
		$types .= "s";
	}

	$sql = "SELECT * FROM krf_product_enquiries $where ORDER BY id DESC";
	if (!empty($types)) {
		$stmt = $krfconn->prepare($sql);
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$res = $stmt->get_result();
	} else {
		$res = $krfconn->query($sql);
	}

	$enquiries = [];
	if ($res) {
		while ($row = $res->fetch_assoc()) {
			$enquiries[] = $row;
		}
	}
	sendJsonResponse($enquiries);
} else if ($action == "updateProductEnquiryStatus") {
	$id = $_POST['id'] ?? '';
	$status = $_POST['status'] ?? 'Pending';
	$remarks = $_POST['remarks'] ?? null;

	if (empty($id)) {
		sendJsonResponse(['status' => 'error', 'message' => 'Invalid enquiry ID']);
	}

	if ($remarks !== null && trim($remarks) !== '') {
		$stmt = $krfconn->prepare("UPDATE krf_product_enquiries SET status = ?, remarks = ? WHERE id = ?");
		$stmt->bind_param("ssi", $status, $remarks, $id);
	} else {
		$stmt = $krfconn->prepare("UPDATE krf_product_enquiries SET status = ? WHERE id = ?");
		$stmt->bind_param("si", $status, $id);
	}

	if ($stmt->execute()) {
		sendJsonResponse(['status' => 'success', 'message' => 'Enquiry status updated to ' . $status]);
	} else {
		sendJsonResponse(['status' => 'error', 'message' => 'Failed to update status: ' . $stmt->error]);
	}
	$stmt->close();
} else if ($action == "deleteProductEnquiry") {
	$id = $_POST['id'] ?? '';
	if (empty($id)) {
		sendJsonResponse(['status' => 'error', 'message' => 'Invalid enquiry ID']);
	}

	$stmt = $krfconn->prepare("DELETE FROM krf_product_enquiries WHERE id = ?");
	$stmt->bind_param("i", $id);
	if ($stmt->execute()) {
		sendJsonResponse(['status' => 'success', 'message' => 'Enquiry deleted successfully']);
	} else {
		sendJsonResponse(['status' => 'error', 'message' => 'Failed to delete enquiry: ' . $stmt->error]);
	}
	$stmt->close();
} else if ($action == "getAllClientsList") {
	$companyid = $_POST['companyid'] ?? '';
	$subcompanyid = $_POST['subcompanyid'] ?? '';
	$projectid = $_POST['projectid'] ?? '';
	$search = trim($_POST['search'] ?? '');
	$type = trim($_POST['type'] ?? '');
	$state = trim($_POST['state'] ?? '');
	$district = trim($_POST['district'] ?? '');

	$sql = "SELECT cm.*,
				   COALESCE(so.so_count, 0) AS total_so_count,
				   COALESCE(so.total_so_amount, 0) AS total_so_amount,
				   COALESCE(pso.pso_count, 0) AS total_pso_count
			FROM krf_client_master cm
			LEFT JOIN (
				SELECT clientid, COUNT(*) AS so_count, SUM(IFNULL(grand_total, total_amount)) AS total_so_amount
				FROM krf_sales_order
				GROUP BY clientid
			) so ON cm.client_id = so.clientid
			LEFT JOIN (
				SELECT clientid, COUNT(*) AS pso_count
				FROM krf_provisional_sales_order
				GROUP BY clientid
			) pso ON cm.client_id = pso.clientid
			WHERE (cm.tedit IS NULL OR cm.tedit = '' OR cm.tedit = '0')";

	$params = [];
	$types = "";

	if (!empty($projectid) && $projectid != '0') {
		$sql .= " AND (cm.projectid = ? OR cm.projectid IS NULL OR cm.projectid = '')";
		$params[] = $projectid;
		$types .= "s";
	}

	if (!empty($state)) {
		$sql .= " AND (cm.statename = ? OR cm.stateid = ?)";
		$params[] = $state;
		$params[] = $state;
		$types .= "ss";
	}

	if (!empty($district)) {
		$sql .= " AND (cm.districtname = ? OR cm.districtid = ?)";
		$params[] = $district;
		$params[] = $district;
		$types .= "ss";
	}

	if ($type === 'trader') {
		$sql .= " AND (cm.is_trader = 1 OR cm.is_trader = '1')";
	} else if ($type === 'dealer') {
		$sql .= " AND (cm.is_dealer = 1 OR cm.is_dealer = '1')";
	} else if ($type === 'farmer') {
		$sql .= " AND (IFNULL(cm.is_trader,0) = 0 AND IFNULL(cm.is_dealer,0) = 0)";
	}

	if (!empty($search)) {
		$term = "%" . $search . "%";
		$sql .= " AND (cm.client_id LIKE ? OR cm.client_name LIKE ? OR cm.phones LIKE ? OR cm.client_email LIKE ? OR cm.invoicing_address LIKE ? OR cm.districtname LIKE ? OR cm.statename LIKE ? OR cm.vat LIKE ?)";
		for ($i = 0; $i < 8; $i++) {
			$params[] = $term;
			$types .= "s";
		}
	}

	$sql .= " ORDER BY cm.sno DESC";

	$stmt = $krfconn->prepare($sql);
	if ($stmt) {
		if (!empty($params)) {
			$stmt->bind_param($types, ...$params);
		}
		$stmt->execute();
		$res = $stmt->get_result();
		$data = [];
		if ($res) {
			while ($row = $res->fetch_assoc()) {
				$data[] = $row;
			}
		}
		$stmt->close();
		sendJsonResponse(["status" => "success", "data" => $data, "total" => count($data)]);
	} else {
		sendJsonResponse(["status" => "error", "message" => "Query prepare failed: " . $krfconn->error]);
	}
} else if ($action == "getClientDetailsFull") {
	$client_id = trim($_POST['client_id'] ?? '');
	if (empty($client_id)) {
		sendJsonResponse(["status" => "error", "message" => "Client ID is required."]);
	}

	$stmt = $krfconn->prepare("SELECT * FROM krf_client_master WHERE client_id = ? LIMIT 1");
	$stmt->bind_param("s", $client_id);
	$stmt->execute();
	$client = $stmt->get_result()->fetch_assoc();
	$stmt->close();

	if (!$client) {
		sendJsonResponse(["status" => "error", "message" => "Client not found."]);
	}

	// Fetch Sales Orders
	$soList = [];
	$soStmt = $krfconn->prepare("SELECT sono, psono, DATE_FORMAT(created_at, '%d-%b-%Y') as order_date, grand_total, status, delivery_address FROM krf_sales_order WHERE clientid = ? ORDER BY id DESC LIMIT 20");
	if ($soStmt) {
		$soStmt->bind_param("s", $client_id);
		$soStmt->execute();
		$soRes = $soStmt->get_result();
		while ($soRow = $soRes->fetch_assoc()) {
			$soList[] = $soRow;
		}
		$soStmt->close();
	}

	// Fetch Provisional Sales Orders
	$psoList = [];
	$psoStmt = $krfconn->prepare("SELECT psono, DATE_FORMAT(created_at, '%d-%b-%Y') as pso_date, DATE_FORMAT(expecteddelivery, '%d-%b-%Y') as expected_delivery, status, delivery_address, requirementtxt FROM krf_provisional_sales_order WHERE clientid = ? ORDER BY id DESC LIMIT 20");
	if ($psoStmt) {
		$psoStmt->bind_param("s", $client_id);
		$psoStmt->execute();
		$psoRes = $psoStmt->get_result();
		while ($psoRow = $psoRes->fetch_assoc()) {
			$psoList[] = $psoRow;
		}
		$psoStmt->close();
	}

	// Fetch Delivery Locations
	$deliveryAddrs = getDeliveryLocations($krfconn, $client['projectid'] ?? 1, $client_id);

	sendJsonResponse([
		"status" => "success",
		"client" => $client,
		"sales_orders" => $soList,
		"provisional_orders" => $psoList,
		"delivery_locations" => $deliveryAddrs
	]);
} else if ($action == "saveClientMasterFull") {
	$companyid = $_POST['companyid'] ?? 19;
	$subcompanyid = $_POST['subcompanyid'] ?? 12;
	$projectid = $_POST['projectid'] ?? 17;
	$client_id = trim($_POST['client_id'] ?? '');
	$client_name = trim($_POST['client_name'] ?? '');
	$invoicing_address = trim($_POST['invoicing_address'] ?? '');
	$phone = trim($_POST['phone'] ?? $_POST['phones'] ?? '');
	$email = trim($_POST['email'] ?? $_POST['client_email'] ?? '');
	$statename = trim($_POST['statename'] ?? '');
	$stateid = trim($_POST['stateid'] ?? '');
	$districtname = trim($_POST['districtname'] ?? '');
	$districtid = trim($_POST['districtid'] ?? '');
	$tahseel = trim($_POST['tahseel'] ?? '');
	$cityname = trim($_POST['cityname'] ?? '');
	$vat = trim($_POST['vat'] ?? '');
	$is_trader = intval($_POST['is_trader'] ?? 0);
	$is_dealer = intval($_POST['is_dealer'] ?? 0);
	$discountpercent = trim($_POST['discountpercent'] ?? '');
	$bankname = trim($_POST['bankname'] ?? '');
	$accountno = trim($_POST['accountno'] ?? '');
	$ifsccode = trim($_POST['ifsccode'] ?? '');
	$account_name = trim($_POST['account_name'] ?? '');
	$comp_website = trim($_POST['comp_website'] ?? '');
	$name_forpo = trim($_POST['name_forpo'] ?? '');
	$contact_forpo_raw = trim($_POST['contact_forpo'] ?? '');
	$contact_forpo = !empty($contact_forpo_raw) ? intval(substr(preg_replace('/\D/', '', $contact_forpo_raw), 0, 9)) : 0;
	$email_forpo = trim($_POST['email_forpo'] ?? '');
	$name_foraccpay = trim($_POST['name_foraccpay'] ?? '');
	$email_foraccpay = trim($_POST['email_foraccpay'] ?? '');

	if (empty($client_name)) {
		sendJsonResponse(["status" => "error", "message" => "Client Name is mandatory."]);
	}

	// Auto resolve state / district names & IDs if one is provided
	if (empty($stateid) && !empty($statename)) {
		$stQ = $krfconn->prepare("SELECT stateid, statename FROM master_states WHERE LOWER(TRIM(statename)) = LOWER(TRIM(?)) LIMIT 1");
		if ($stQ) {
			$stQ->bind_param("s", $statename);
			$stQ->execute();
			$stRes = $stQ->get_result()->fetch_assoc();
			if ($stRes) { $stateid = $stRes['stateid']; $statename = $stRes['statename']; }
			$stQ->close();
		}
	} else if (!empty($stateid) && empty($statename)) {
		$stQ = $krfconn->prepare("SELECT statename FROM master_states WHERE stateid = ? LIMIT 1");
		if ($stQ) {
			$stQ->bind_param("s", $stateid);
			$stQ->execute();
			$stRes = $stQ->get_result()->fetch_assoc();
			if ($stRes) { $statename = $stRes['statename']; }
			$stQ->close();
		}
	}

	if (empty($districtid) && !empty($districtname)) {
		$dtQ = $krfconn->prepare("SELECT districtid, districtname FROM master_districts WHERE LOWER(TRIM(districtname)) = LOWER(TRIM(?)) LIMIT 1");
		if ($dtQ) {
			$dtQ->bind_param("s", $districtname);
			$dtQ->execute();
			$dtRes = $dtQ->get_result()->fetch_assoc();
			if ($dtRes) { $districtid = $dtRes['districtid']; $districtname = $dtRes['districtname']; }
			$dtQ->close();
		}
	} else if (!empty($districtid) && empty($districtname)) {
		$dtQ = $krfconn->prepare("SELECT districtname FROM master_districts WHERE districtid = ? LIMIT 1");
		if ($dtQ) {
			$dtQ->bind_param("s", $districtid);
			$dtQ->execute();
			$dtRes = $dtQ->get_result()->fetch_assoc();
			if ($dtRes) { $districtname = $dtRes['districtname']; }
			$dtQ->close();
		}
	}

	$isUpdate = !empty($client_id);

	if ($isUpdate) {
		$sql = "UPDATE krf_client_master SET
					client_name = ?,
					invoicing_address = ?,
					phones = ?,
					client_email = ?,
					statename = ?,
					stateid = ?,
					districtname = ?,
					districtid = ?,
					tahseel = ?,
					cityname = ?,
					vat = ?,
					is_trader = ?,
					is_dealer = ?,
					discountpercent = ?,
					bankname = ?,
					accountno = ?,
					ifsccode = ?,
					account_name = ?,
					comp_website = ?,
					name_forpo = ?,
					contact_forpo = ?,
					email_forpo = ?,
					name_foraccpay = ?,
					email_foraccpay = ?,
					tedit = ''
				WHERE client_id = ?";
		$stmt = $krfconn->prepare($sql);
		if (!$stmt) {
			sendJsonResponse(["status" => "error", "message" => "Prepare failed: " . $krfconn->error]);
		}
		$stmt->bind_param(
			"sssssssssssiisssssssissss",
			$client_name,
			$invoicing_address,
			$phone,
			$email,
			$statename,
			$stateid,
			$districtname,
			$districtid,
			$tahseel,
			$cityname,
			$vat,
			$is_trader,
			$is_dealer,
			$discountpercent,
			$bankname,
			$accountno,
			$ifsccode,
			$account_name,
			$comp_website,
			$name_forpo,
			$contact_forpo,
			$email_forpo,
			$name_foraccpay,
			$email_foraccpay,
			$client_id
		);
		if ($stmt->execute()) {
			sendJsonResponse(["status" => "success", "message" => "Client #$client_id updated successfully.", "client_id" => $client_id]);
		} else {
			sendJsonResponse(["status" => "error", "message" => "Update failed: " . $stmt->error]);
		}
		$stmt->close();
	} else {
		// Duplicate Check
		if (!empty($phone)) {
			$dup = $krfconn->prepare("SELECT client_id, client_name FROM krf_client_master WHERE phones = ? AND (tedit IS NULL OR tedit = '' OR tedit = '0') LIMIT 1");
			if ($dup) {
				$dup->bind_param("s", $phone);
				$dup->execute();
				$dupRes = $dup->get_result()->fetch_assoc();
				$dup->close();
				if ($dupRes) {
					sendJsonResponse(["status" => "warning", "message" => "A client with phone number '$phone' already exists (ID: {$dupRes['client_id']}, Name: {$dupRes['client_name']})."]);
				}
			}
		}

		// Generate new Client ID
		$resq = $krfconn->query("SELECT IFNULL(MAX(CAST(SUBSTRING(client_id,2) AS UNSIGNED)),0)+1 AS next_id FROM krf_client_master WHERE IFNULL(client_id,'') != ''");
		$next_id = ($resq ? $resq->fetch_assoc()['next_id'] : 1);
		$new_client_id = 'C' . str_pad($next_id, 5, '0', STR_PAD_LEFT);

		$sql = "INSERT INTO krf_client_master (
					companyid, subcompanyid, projectid, client_id, client_name, invoicing_address, phones, client_email,
					statename, stateid, districtname, districtid, tahseel, cityname, vat, is_trader, is_dealer,
					discountpercent, bankname, accountno, ifsccode, account_name, comp_website,
					name_forpo, contact_forpo, email_forpo, name_foraccpay, email_foraccpay, date_time, tedit
				) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), '')";
		$stmt = $krfconn->prepare($sql);
		if (!$stmt) {
			sendJsonResponse(["status" => "error", "message" => "Prepare failed: " . $krfconn->error]);
		}
		$stmt->bind_param(
			"sssssssssssssssiisssssssisss",
			$companyid,
			$subcompanyid,
			$projectid,
			$new_client_id,
			$client_name,
			$invoicing_address,
			$phone,
			$email,
			$statename,
			$stateid,
			$districtname,
			$districtid,
			$tahseel,
			$cityname,
			$vat,
			$is_trader,
			$is_dealer,
			$discountpercent,
			$bankname,
			$accountno,
			$ifsccode,
			$account_name,
			$comp_website,
			$name_forpo,
			$contact_forpo,
			$email_forpo,
			$name_foraccpay,
			$email_foraccpay
		);
		if ($stmt->execute()) {
			sendJsonResponse(["status" => "success", "message" => "New Client #$new_client_id created successfully.", "client_id" => $new_client_id]);
		} else {
			sendJsonResponse(["status" => "error", "message" => "Insert failed: " . $stmt->error]);
		}
		$stmt->close();
	}
} else if ($action == "deleteClientMaster") {
	$client_id = trim($_POST['client_id'] ?? '');
	if (empty($client_id)) {
		sendJsonResponse(["status" => "error", "message" => "Invalid Client ID"]);
	}

	$stmt = $krfconn->prepare("UPDATE krf_client_master SET tedit = '1' WHERE client_id = ?");
	$stmt->bind_param("s", $client_id);
	if ($stmt->execute()) {
		sendJsonResponse(["status" => "success", "message" => "Client #$client_id removed successfully."]);
	} else {
		sendJsonResponse(["status" => "error", "message" => "Failed to remove client: " . $stmt->error]);
	}
	$stmt->close();
}

function chkclientexist($table, $column, $value, $conn, $conditions = '') {
    $sql = "SELECT COUNT(*) as cnt FROM `$table` WHERE `$column` = ?";

    if ($conditions != '') {
        $sql .= " AND $conditions";
    }

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    return ($count > 0); // true if duplicate exists
}
?>