<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/client_functions.php");
if (file_exists('../../whatsappnotification/whatsappsmsfunctions.php')) {
    include_once('../../whatsappnotification/whatsappsmsfunctions.php');
}
if (file_exists('../../whatsappnotification/whatsappnotificationfunctions.php')) {
    include_once('../../whatsappnotification/whatsappnotificationfunctions.php');
}

/**
 * Helper: Send WhatsApp alert to department code 'ACDP' (Accounts Department) for unverified payment advice
 */
function sendUnverifiedPaymentAlertToACDP($krfconn, $psono, $amount, $source, $utr, $clientname, $clientmobileno, $receiptpath, $paymentdate, $remarks, $casgreceivedby, $casgreceuvelocation, $loginname) {
    try {
        if (!$krfconn) return false;
        @$krfconn->set_charset("utf8mb4");

        // 1. Fetch Ordered Products with item breakdown for this psono if available
        $items = [];
        $totalPlants = 0;
        if (!empty($psono)) {
            $stmtItems = $krfconn->prepare("SELECT product_name, quantity, rate, amount, expecteddeliverydate FROM krf_provisional_sales_order_items WHERE psono = ? ORDER BY id ASC");
            if ($stmtItems) {
                $stmtItems->bind_param("s", $psono);
                $stmtItems->execute();
                $iRes = $stmtItems->get_result();
                while ($iRow = $iRes->fetch_assoc()) {
                    $items[] = $iRow;
                    $totalPlants += (int)($iRow['quantity'] ?? 0);
                }
                $stmtItems->close();
            }
        }

        // If client details were empty, fetch from provisional order
        if ((empty($clientname) || empty($clientmobileno)) && !empty($psono)) {
            $stmtPso = $krfconn->prepare("SELECT clientname, mobileno FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
            if ($stmtPso) {
                $stmtPso->bind_param("s", $psono);
                $stmtPso->execute();
                $pRes = $stmtPso->get_result();
                if ($pRow = $pRes->fetch_assoc()) {
                    if (empty($clientname)) $clientname = $pRow['clientname'];
                    if (empty($clientmobileno)) $clientmobileno = $pRow['mobileno'];
                }
                $stmtPso->close();
            }
        }

        $formatted_date = !empty($paymentdate) ? date('d-m-Y', strtotime($paymentdate)) : date('d-m-Y');
        $formatted_amount = number_format((float)$amount, 2);

        // 2. Compose WhatsApp Alert Message for ACDP
        $msg = "🔔 *Payment Verification Required – Karjee Farm*\n\n";
        if (!empty($psono)) {
            $msg .= "An advance payment advice has been submitted for Provisional Order *#{$psono}* and is awaiting verification by Accounts Department (*ACDP*).\n\n";
        } else {
            $msg .= "A client advance payment advice has been recorded for *{$clientname}* and is awaiting verification by Accounts Department (*ACDP*).\n\n";
        }

        $msg .= "💳 *Payment Information:*\n";
        if (!empty($psono)) {
            $msg .= "• Provisional Order: *#{$psono}*\n";
        } else {
            $msg .= "• Payment Type: *Client Advance (No Order / Invoice)*\n";
        }
        $msg .= "• Client: *{$clientname}* (" . (!empty($clientmobileno) ? $clientmobileno : 'N/A') . ")\n";
        $msg .= "• Amount Received: *₹{$formatted_amount}*\n";
        $msg .= "• Payment Mode: *{$source}*\n";
        if (strtolower($source) === 'cash') {
            if (!empty($casgreceivedby)) $msg .= "• Received By: *{$casgreceivedby}*\n";
            if (!empty($casgreceuvelocation)) $msg .= "• Received Location: *{$casgreceuvelocation}*\n";
        } else {
            if (!empty($utr)) $msg .= "• Txn / UTR No: *{$utr}*\n";
        }
        $msg .= "• Payment Date: *{$formatted_date}*\n";
        if (!empty($remarks)) $msg .= "• Remarks: *{$remarks}*\n";
        $msg .= "• Logged By: *{$loginname}*\n\n";

        if (!empty($items)) {
            $msg .= "📦 *Order Products:*\n";
            $idx = 1;
            foreach ($items as $itm) {
                $pName = trim($itm['product_name'] ?? 'Product');
                $pQty = (int)($itm['quantity'] ?? 0);
                $pRate = (float)($itm['rate'] ?? 0);
                $pAmt = (float)($itm['amount'] ?? ($pQty * $pRate));
                $msg .= "{$idx}. *{$pName}* (Qty: {$pQty}, Rate: ₹{$pRate}, Total: ₹" . number_format($pAmt, 2) . ")\n";
                $idx++;
            }
            if ($totalPlants > 0) {
                $msg .= "🌱 *Total Quantity:* {$totalPlants} Plants\n";
            }
            $msg .= "\n";
        }

        $msg .= "⚠️ *Status:* *Unverified (Payment Verification Pending)*\n";
        $msg .= "👉 *Next Step:* Please verify this payment in the *Received Payment Verification* portal to confirm and convert it into a Final Sales Order.\n";

        // 3. Resolve Media Attachment URL if receipt is present
        $mediaurl = "";
        $filename = "";
        if (!empty($receiptpath)) {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? '') == 443) ? "https://" : "http://";
            $domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $cleanPath = ltrim(str_replace('../', '', $receiptpath), '/');
            $mediaurl = $protocol . $domainName . '/krf_amanaccounting/' . $cleanPath;
            $filename = basename($receiptpath);
        }

        // 4. Find Recipients for Department Code 'ACDP' / Accounts
        $recipients = [];

        // Method A: Direct check in whatsapp_departmentmaster & whatsapp_departmentperson
        $qA = "SELECT DISTINCT p.mobile_no 
               FROM whatsapp_departmentperson p
               JOIN whatsapp_departmentmaster d ON d.department_id = p.department_id
               WHERE (d.department_code = 'ACDP' OR d.department_code = 'ACCOUNTS' OR UPPER(d.department_name) LIKE '%ACCOUNT%' OR UPPER(d.department_name) LIKE '%ACDP%')
                 AND d.status = 'Active'
                 AND p.status = 'Active' 
                 AND p.receive_whatsapp = 'Yes'";
        $rA = $krfconn->query($qA);
        if ($rA) {
            while ($row = $rA->fetch_assoc()) {
                if (!empty($row['mobile_no'])) $recipients[] = trim($row['mobile_no']);
            }
        }

        // Method B: Check module mappings
        $qB = "SELECT DISTINCT p.mobile_no 
               FROM whatsapp_notificationmodule_mapping m
               JOIN whatsapp_departmentperson p ON p.department_id = m.department_id
               WHERE m.module_name IN ('ACDP', 'ACCOUNTS', 'PAYMENT', 'PAYMENT_VERIFICATION')
                 AND m.is_active = 'Yes'
                 AND p.status = 'Active' 
                 AND p.receive_whatsapp = 'Yes'";
        $rB = $krfconn->query($qB);
        if ($rB) {
            while ($row = $rB->fetch_assoc()) {
                if (!empty($row['mobile_no'])) $recipients[] = trim($row['mobile_no']);
            }
        }

        // Method C: Fallback to getRecipientsByModuleForSowing if department ORDER / ACCOUNTS mapped
        if (empty($recipients) && function_exists('getRecipientsByModuleForSowing')) {
            $deptRecipients = getRecipientsByModuleForSowing($krfconn, 1, 'ACCOUNTS');
            if (empty($deptRecipients)) {
                $deptRecipients = getRecipientsByModuleForSowing($krfconn, 1, 'ORDER');
            }
            if (is_array($deptRecipients)) {
                foreach ($deptRecipients as $dRow) {
                    $dNum = is_array($dRow) ? ($dRow['mobile_no'] ?? '') : $dRow;
                    if ($dNum) $recipients[] = trim($dNum);
                }
            }
        }

        $recipients = array_unique($recipients);

        // 5. Get Credentials and Enqueue Messages
        $centreid = 1;
        $smsinfo = function_exists('whatsappuseridpassword') ? whatsappuseridpassword($krfconn, $centreid) : [];
        if (!empty($smsinfo) && isset($smsinfo[0])) {
            $sKey = $smsinfo[0];
            $sourceid = $sKey['sourceid'] ?? '';
            $whatsappno = $sKey['whatsappno'] ?? '';
            $apikey = $sKey['apikey'] ?? '';
            $accesstoken = $sKey['accesstoken'] ?? '';

            if (!empty($sourceid) && !empty($whatsappno) && !empty($apikey) && !empty($accesstoken)) {
                $msg_title = "Payment Verification Alert - #{$psono}";
                $msg_type = !empty($mediaurl) ? "textwithattachment" : "text";

                foreach ($recipients as $toNum) {
                    $toNum = trim($toNum);
                    if (empty($toNum) || strlen($toNum) < 10) continue;

                    if (function_exists('readyto_sentwhatsapp_message')) {
                        readyto_sentwhatsapp_message(
                            $krfconn,
                            $sourceid,
                            $whatsappno,
                            $toNum,
                            $accesstoken,
                            $apikey,
                            $msg_title,
                            $msg,
                            $clientname,
                            $msg_type,
                            $mediaurl,
                            $filename
                        );
                    }
                }
                return true;
            }
        }
    } catch (Exception $e) {
        error_log("Error in sendUnverifiedPaymentAlertToACDP: " . $e->getMessage());
    }
    return false;
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
if($action=="GetClientCreditNotes"){
	$clientid = trim($_GET['clientid'] ?? ($_POST['clientid'] ?? ''));
	$clientname = trim($_GET['clientname'] ?? ($_POST['clientname'] ?? ''));
	$mobileno = trim($_GET['mobileno'] ?? ($_POST['mobileno'] ?? ''));
	$projectid = intval($_GET['projectid'] ?? ($_POST['projectid'] ?? 0));

	// Resolve clientid or clientname using mobileno if needed
	if ((empty($clientid) || empty($clientname)) && !empty($mobileno)) {
		$stCl = $krfconn->prepare("SELECT clientid, clientname FROM krf_provisional_sales_order WHERE mobileno = ? ORDER BY id DESC LIMIT 1");
		if ($stCl) {
			$stCl->bind_param("s", $mobileno);
			$stCl->execute();
			$resCl = $stCl->get_result();
			if ($rCl = $resCl->fetch_assoc()) {
				if (empty($clientid)) $clientid = $rCl['clientid'];
				if (empty($clientname)) $clientname = $rCl['clientname'];
			}
			$stCl->close();
		}
	}

	if (empty($clientid) && empty($clientname)) {
		echo json_encode(['status' => 'success', 'data' => [], 'total_count' => 0, 'total_amount' => 0]);
		exit;
	}

	$cCond = [];
	$params = [];
	$types = "";

	if (!empty($clientid)) {
		$cCond[] = "clientid = ?";
		$params[] = $clientid;
		$types .= "s";
	}
	if (!empty($clientname)) {
		$cCond[] = "LOWER(clientname) = LOWER(?)";
		$params[] = $clientname;
		$types .= "s";
	}

	$whereCl = "(" . implode(" OR ", $cCond) . ")";

	$sql = "SELECT 
				creditnote_id, 
				creditnote_no, 
				creditnote_date, 
				projectid, 
				companyid, 
				subcompanyid, 
				clientid, 
				clientname, 
				psono, 
				sono, 
				stockorder_no, 
				product_name, 
				amount_paid, 
				credit_amount, 
				narration, 
				reason, 
				status, 
				created_by, 
				created_at 
			FROM krf_creditnote 
			WHERE status = 'Active' 
			  AND (tedit IS NULL OR tedit = '') 
			  AND credit_amount > 0 
			  AND {$whereCl}
			ORDER BY creditnote_id DESC";

	$stmt = $krfconn->prepare($sql);
	$data = [];
	$totalAmount = 0.0;
	if ($stmt) {
		$stmt->bind_param($types, ...$params);
		$stmt->execute();
		$res = $stmt->get_result();
		while ($row = $res->fetch_assoc()) {
			$amt = floatval($row['credit_amount'] ?? 0);
			$totalAmount += $amt;
			$data[] = [
				'creditnote_id' => $row['creditnote_id'],
				'creditnote_no' => $row['creditnote_no'],
				'creditnote_date' => $row['creditnote_date'],
				'formatted_date' => date('d-M-Y', strtotime($row['creditnote_date'])),
				'clientid' => $row['clientid'],
				'clientname' => $row['clientname'],
				'psono' => $row['psono'] ?? '',
				'sono' => $row['sono'] ?? '',
				'credit_amount' => $amt,
				'formatted_amount' => number_format($amt, 2),
				'reason' => !empty($row['reason']) ? $row['reason'] : ($row['narration'] ?? 'Credit Adjustment'),
				'created_by' => !empty($row['created_by']) ? $row['created_by'] : 'Admin',
				'created_at' => $row['created_at'],
				'formatted_created_at' => date('d-M-Y H:i', strtotime($row['created_at']))
			];
		}
		$stmt->close();
	}

	echo json_encode([
		'status' => 'success',
		'data' => $data,
		'total_count' => count($data),
		'total_amount' => $totalAmount,
		'clientid' => $clientid,
		'clientname' => $clientname
	]);
	exit;
}else if($action=="GetAccountdetailsFrommaster_option"){
	$companyid = $_POST['companyid'];
	$subcompanyid = $_POST['subcompanyid'];
	$projectid = $_POST['projectid'];
	$tbllist=GetAccountDetailsFromMasterlist($conn,$companyid,$subcompanyid,$projectid,"projectid",$projectid);
	echo json_encode($tbllist);
}else if($action=="saveadvancepayment"){
	$companyid     = $_POST['companyid'];
	$subcompanyid  = $_POST['subcompanyid'];
	$projectid     = $_POST['projectid'];
	$clientmobileno      = $_POST['clientmobileno'];
	$clientname      = $_POST['clientname'];
	$clientid      = $_POST['clientid'];
	$amount        = floatval($_POST['amount']);
	$utrnumber   = $_POST['utrnumber'];
	$invoiceno   = $_POST['invoiceno'];
	$paymentdate   = $_POST['paymentdate'];
	$purpose       = $_POST['purposeofamount'] ?? '';
	$source        = $_POST['sourceofpayment'] ?? '';
	$receivedtoaccountid = $_POST['receivedtoaccountid'] ?? '';
	$paymentRefNo  = $_POST['payment_ref_no'] ?? '';
	$remarks       = $_POST['remarks'] ?? '';
	$loginid       = $_POST['loginid'] ?? '';
	if($clientid=="" || $clientid==null || $clientid=="undefined"){
		$ppinfo = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $invoiceno);
		$client_name = "";
		$invoicing_address = "";
		$phone = "";
		$email = "";
		if(count($ppinfo)>0){
			foreach ($ppinfo as $ppinkey) {
				$client_name = $ppinkey['clientname'];
				$invoicing_address = $ppinkey['address'];
				$phone = $ppinkey['mobileno'];
				$email = $ppinkey['emailid'];
			}
		}
		$districtname="";
		$statename="";
		$response = saveClient($krfconn, $companyid, $subcompanyid, $projectid,
		    $clientid, $client_name, $invoicing_address, $phone, $email, $districtname,$statename);
		if($response['status']=="success"){
			$clientid = $response['client_id'];
		}
	}

	if(empty($clientid) || empty($amount) || empty($paymentdate) || empty($purpose)){
	    echo json_encode(["status"=>"error","message"=>"Required fields missing."]);
	    exit;
	}

	// Prepare statement
	$sql = "INSERT INTO advance_payment_transactions 
	    (companyid, subcompanyid, projectid, clientid, advanceamount, utrnumber, invoiceno, paymentdate, purposeofpayment, sourceofpayment, receivedtoaccountid, remarks, verification) 
	    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unverified')";

	$stmt = $krfconn->prepare($sql);
	if(!$stmt){
	    echo json_encode(["status"=>"error","message"=>"Prepare failed: ".$krfconn->error]);
	    exit;
	}
	// Bind parameters
	// i = integer, d = double/float, s = string
	$stmt->bind_param(
	    "iiisdsssssss", 
	    $companyid, 
	    $subcompanyid, 
	    $projectid, 
	    $clientid, 
	    $amount,
	    $utrnumber,
	    $invoiceno, 
	    $paymentdate, 
	    $purpose, 
	    $source, 
	    $receivedtoaccountid,
	    $remarks
	);

	// Execute
	if($stmt->execute()){
	    sendUnverifiedPaymentAlertToACDP(
	        $krfconn,
	        $invoiceno,
	        $amount,
	        $source,
	        $utrnumber,
	        $clientname,
	        $clientmobileno,
	        '',
	        $paymentdate,
	        $remarks,
	        '',
	        '',
	        $loginid
	    );
	    echo json_encode([
	        "status"=>"success",
	        "message"=>"Payment advice recorded as Unverified. WhatsApp alert sent to Accounts Department (ACDP) for verification.",
	        "psono" => $invoiceno,
	        "sono" => "",
	        "verification" => "unverified",
	        "is_verified" => false
	    ]);
	} else {
	    echo json_encode(["status"=>"error","message"=>$stmt->error]);
	}

	$stmt->close();
}else if($action=="SaveAdvancePaymentManulal"){
	$companyid     = $_POST['companyid'];
	$subcompanyid  = $_POST['subcompanyid'];
	$projectid     = $_POST['projectid'];
	$clientid      = $_POST['clientid'] ?? '';
	$clientName      = $_POST['clientname'] ?? '';
	$clientmobileno      = $_POST['clientmobileno'] ?? '';
	$amount        = floatval($_POST['amount']);
	$utrnumber   = trim($_POST['utrnumber'] ?? '');
	$rawPaymentdate = trim($_POST['paymentdate'] ?? '');
	$paymentdate = '';
	if (!empty($rawPaymentdate)) {
		$ts = strtotime($rawPaymentdate);
		if ($ts) {
			$paymentdate = date('Y-m-d', $ts);
		}
	}
	$purpose       = $_POST['purposeofamount'] ?? 'paymentagainstinvoice';
	$source        = trim($_POST['sourceofpayment'] ?? 'Cash');
	$receivedtoaccountid = $_POST['receivedtoaccountid'] ?? '';
	$paymentRefNo  = $_POST['payment_ref_no'] ?? '';
	$remarks       = $_POST['remarks'] ?? '';
	$invoiceno       = $_POST['invoiceno'] ?? '';
	$allocated_datetime = date('Y-m-d H:i:s');
	$allocatedby = $_POST['loginid'] ?? '';
	$loginname = $_POST['loginname'] ?? '';
	$loginid = $_POST['loginid'] ?? '';
	$casgreceivedby = $_POST['casgreceivedby'] ?? $loginname;
	$casgreceuvelocation = $_POST['casgreceuvelocation'] ?? '';
	$paymentreceipt = $_POST['paymentreceipt'] ?? '';
	$bookingno = trim($_POST['bookingno'] ?? '');

	$isCreditNote = (strtolower($source) === 'creditnote' || strtolower($source) === 'credit_note');
	$creditnote_id = intval($_POST['creditnote_id'] ?? 0);
	$creditnote_no = trim($_POST['creditnote_no'] ?? '');

	if ($isCreditNote) {
		if (empty($creditnote_no) && $creditnote_id > 0) {
			$cnQ = $krfconn->query("SELECT creditnote_no FROM krf_creditnote WHERE creditnote_id = {$creditnote_id} LIMIT 1");
			if ($cnQ && $cnR = $cnQ->fetch_assoc()) {
				$creditnote_no = $cnR['creditnote_no'];
			}
		}
		if (empty($creditnote_no)) {
			echo json_encode(["status" => "error", "message" => "Please choose an active Credit Note from the grid."]);
			exit;
		}

		$cnChk = $krfconn->prepare("SELECT creditnote_id, creditnote_no, credit_amount, clientid, clientname, status FROM krf_creditnote WHERE creditnote_no = ? AND status = 'Active' AND (tedit IS NULL OR tedit = '') LIMIT 1");
		if ($cnChk) {
			$cnChk->bind_param("s", $creditnote_no);
			$cnChk->execute();
			$cnChkRes = $cnChk->get_result();
			$cnActiveRow = $cnChkRes->fetch_assoc();
			$cnChk->close();
			if (!$cnActiveRow) {
				echo json_encode(["status" => "error", "message" => "Credit Note {$creditnote_no} is no longer active or has already been used."]);
				exit;
			}
			if ($amount <= 0) {
				$amount = floatval($cnActiveRow['credit_amount'] ?? 0);
			}
		}

		$source = 'Credit Note';
		$utrnumber = $creditnote_no;
		$receiptpath = 'CREDIT_NOTE:' . $creditnote_no;
		if (empty($paymentdate)) {
			$paymentdate = date('Y-m-d');
		}
	}

	$targetoDir = '../../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/OrderPaymentReceipt/' . date('Y-m-d') . "/";
	$receiptpath="";
  if (!is_dir($targetoDir)) mkdir($targetoDir, 0777, true);

  // Upload Main Payment Receipt Photo
  if (isset($_FILES['paymentreceipt']) && $_FILES['paymentreceipt']['error'] === 0) {
    $ofilename   = time() . '_' . basename($_FILES['paymentreceipt']['name']);
    $targetPath = $targetoDir . $ofilename;

    if (move_uploaded_file($_FILES['paymentreceipt']['tmp_name'], $targetPath)) {
        $ReceiptPhotoPath = $targetPath;
        if($ReceiptPhotoPath){
            $receiptpath = str_replace("../", "", $ReceiptPhotoPath);
        }
    }
  }

  // Handle Manual Booking File Upload (optional / if provided)
  $bookingfilepath = "";
  if (isset($_FILES['bookingreceipt']) && $_FILES['bookingreceipt']['error'] === 0) {
    $targetbDir = '../../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/BookingReceipt/' . date('Y-m-d') . "/";
    if (!is_dir($targetbDir)) mkdir($targetbDir, 0777, true);
    $bfilename = time() . '_booking_' . basename($_FILES['bookingreceipt']['name']);
    $targetbPath = $targetbDir . $bfilename;
    if (move_uploaded_file($_FILES['bookingreceipt']['tmp_name'], $targetbPath)) {
        $bookingfilepath = str_replace("../", "", $targetbPath);
    }
  }

	$multi_order_data = $_POST['multi_order_data'] ?? '';
	$orders = [];
	if ($multi_order_data != '') {
		$orders = json_decode($multi_order_data, true);
	}

	if (empty($orders) && !empty($invoiceno)) {
		$orders[] = ['psono' => $invoiceno, 'amount' => $amount];
	}

	// Resolve clientid, clientName, clientmobileno early if needed
	$check_psono = !empty($invoiceno) ? $invoiceno : (!empty($orders[0]['psono']) ? $orders[0]['psono'] : '');
	if ((empty($clientid) || $clientid === '0' || empty($clientName) || empty($clientmobileno)) && !empty($check_psono)) {
		$cStmt = $krfconn->prepare("SELECT clientid, clientname, mobileno FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
		if ($cStmt) {
			$cStmt->bind_param("s", $check_psono);
			$cStmt->execute();
			$cRes = $cStmt->get_result();
			if ($cRow = $cRes->fetch_assoc()) {
				if (empty($clientid) || $clientid === '0') $clientid = $cRow['clientid'] ?? '';
				if (empty($clientName)) $clientName = $cRow['clientname'] ?? '';
				if (empty($clientmobileno)) $clientmobileno = $cRow['mobileno'] ?? '';
			}
			$cStmt->close();
		}
	}

	// 1. Specific validation for required fields
	if (empty(trim($clientName))) {
		echo json_encode(["status" => "error", "message" => "Client Name is missing."]);
		exit;
	}

	if (empty(trim($clientmobileno))) {
		echo json_encode(["status" => "error", "message" => "Client Mobile Number is missing."]);
		exit;
	}

	if ($amount <= 0) {
		echo json_encode(["status" => "error", "message" => "Payment Amount is missing or invalid."]);
		exit;
	}

	if (empty($paymentdate)) {
		echo json_encode(["status" => "error", "message" => "Payment date is missing."]);
		exit;
	}

	if (empty($source)) {
		echo json_encode(["status" => "error", "message" => "Source of payment is missing."]);
		exit;
	}

	if ($isCreditNote) {
		// Credit Note details already verified and mapped
	} else if (strtolower($source) !== 'cash') {
		if (empty($utrnumber)) {
			$sourceLabel = (strtolower($source) === 'cheque') ? 'Cheque' : 'UPI / Bank';
			echo json_encode(["status" => "error", "message" => "Transaction No / UTR is missing for {$sourceLabel} payment."]);
			exit;
		}
		if (empty(trim($receivedtoaccountid))) {
			echo json_encode(["status" => "error", "message" => "Deposited Bank Account is missing. Please select bank account."]);
			exit;
		}
	} else {
		if (empty(trim($casgreceivedby))) {
			echo json_encode(["status" => "error", "message" => "Cash Received By person name is missing."]);
			exit;
		}
		if (empty(trim($casgreceuvelocation))) {
			echo json_encode(["status" => "error", "message" => "Cash Received Location is missing."]);
			exit;
		}
	}

	// 2. Strict document requirement - If proof document is missing, do NOT save payment details
	if (!$isCreditNote && empty($receiptpath)) {
		echo json_encode([
			"status" => "error",
			"message" => "Proof of payment document is missing. Received payment details cannot be saved without attaching payment proof document (JPG, PNG, PDF)."
		]);
		exit;
	}

	// 3. Duplicate payment checks
	// 3a. Check duplicate UTR number across database (if UTR provided)
	if (!$isCreditNote && !empty($utrnumber)) {
		$utrStmt = $krfconn->prepare("SELECT sno, invoiceno, advanceamount, utrnumber, clientname, paymentdate FROM advance_payment_transactions WHERE utrnumber = ? AND utrnumber != '' AND (tedit IS NULL OR tedit = '') LIMIT 1");
		if ($utrStmt) {
			$utrStmt->bind_param("s", $utrnumber);
			$utrStmt->execute();
			$utrRes = $utrStmt->get_result();
			if ($dupUtr = $utrRes->fetch_assoc()) {
				echo json_encode([
					"status" => "error",
					"message" => "Duplicate Payment Detected: Transaction No / UTR '{$utrnumber}' is already recorded in database on {$dupUtr['paymentdate']} for Client '{$dupUtr['clientname']}' (Amount: ₹" . number_format($dupUtr['advanceamount'], 2) . (!empty($dupUtr['invoiceno']) ? ", Order #{$dupUtr['invoiceno']}" : "") . "). Details not saved to prevent duplicate entry."
				]);
				$utrStmt->close();
				exit;
			}
			$utrStmt->close();
		}
	}

	// 3b. Check duplicate payment of same day for same client
	if (!$isCreditNote) {
		$confirm_same_day = isset($_POST['confirm_same_day']) && $_POST['confirm_same_day'] == '1';
		$dupStmt = $krfconn->prepare("SELECT sno, invoiceno, advanceamount, utrnumber, sourceofpayment, paymentdate, allocated_datetime 
		                             FROM advance_payment_transactions 
		                             WHERE paymentdate = ? 
		                               AND ((clientid != '' AND clientid = ?) OR (clientname != '' AND clientname = ?))
		                               AND (tedit IS NULL OR tedit = '')");
		if ($dupStmt) {
			$dupStmt->bind_param("sss", $paymentdate, $clientid, $clientName);
			$dupStmt->execute();
			$dupRes = $dupStmt->get_result();
			
			$exactDuplicateFound = false;
			$exactDuplicateMsg = "";
			$sameDayPayments = [];

			while ($dRow = $dupRes->fetch_assoc()) {
				// If UTR is different, it is a separate valid payment - users can record multiple payments with different UTRs
				if (!empty($utrnumber) && !empty($dRow['utrnumber']) && trim((string)$utrnumber) !== trim((string)$dRow['utrnumber'])) {
					continue;
				}

				// Exact duplicate: Same order, same amount, and same UTR (or both without UTR)
				$isSameOrder = (!empty($invoiceno) && !empty($dRow['invoiceno']) && $dRow['invoiceno'] === $invoiceno);
				$isSameAmount = (abs(floatval($dRow['advanceamount']) - $amount) < 0.01);
				$isSameUtr = (trim((string)$dRow['utrnumber']) === trim((string)$utrnumber));

				if ($isSameOrder && $isSameAmount && $isSameUtr) {
					$exactDuplicateFound = true;
					$exactDuplicateMsg = "Duplicate Payment Detected: An identical payment of ₹" . number_format($amount, 2) . " has already been recorded for Order #{$invoiceno} on {$paymentdate}" . (!empty($dRow['utrnumber']) ? " (UTR: {$dRow['utrnumber']})" : "") . ". Details not saved to prevent duplicate entry.";
					break;
				}

				// Only track for same-day additional payment confirmation if same order and without different UTR
				if ($isSameOrder && empty($utrnumber)) {
					$sameDayPayments[] = $dRow;
				}
			}
			$dupStmt->close();

			if ($exactDuplicateFound) {
				echo json_encode([
					"status" => "error",
					"message" => $exactDuplicateMsg
				]);
				exit;
			}

			// If another payment exists on the same day for this client and user hasn't explicitly confirmed
			if (!empty($sameDayPayments) && !$confirm_same_day) {
				$firstRow = $sameDayPayments[0];
				echo json_encode([
					"status" => "same_day_duplicate_warning",
					"message" => "Client '{$clientName}' already has an existing payment of ₹" . number_format($firstRow['advanceamount'], 2) . " recorded on {$paymentdate}" . (!empty($firstRow['invoiceno']) ? " (Order #{$firstRow['invoiceno']})" : "") . (!empty($firstRow['utrnumber']) ? " (UTR: {$firstRow['utrnumber']})" : "") . ". Are you sure you want to record another payment for this client on the same day?",
					"existing_payment" => [
						"sno" => $firstRow['sno'],
						"amount" => $firstRow['advanceamount'],
						"invoiceno" => $firstRow['invoiceno'],
						"utrnumber" => $firstRow['utrnumber'],
						"paymentdate" => $firstRow['paymentdate']
					]
				]);
				exit;
			}
		}
	}

	if (empty($orders)) {
		$orders[] = ['psono' => '', 'amount' => $amount];
	}

	$success_count = 0;
	$last_psono = "";
	$last_sono = "";

	$passed_invoice_amount = isset($_POST['invoice_amount']) && is_numeric($_POST['invoice_amount']) ? floatval($_POST['invoice_amount']) : 0;
	$passed_special_discount = isset($_POST['special_discount']) && is_numeric($_POST['special_discount']) ? floatval($_POST['special_discount']) : (isset($_POST['otherdiscount']) && is_numeric($_POST['otherdiscount']) ? floatval($_POST['otherdiscount']) : 0);

	foreach ($orders as $orderItem) {
		$current_psono = $orderItem['psono'] ?? '';
		$current_amount = floatval($orderItem['amount'] ?? 0);
		if ($current_amount <= 0 && count($orders) === 1) {
			$current_amount = floatval($amount);
		}

		if (!empty($current_psono)) {
			$chkPso = $krfconn->query("SELECT status FROM krf_provisional_sales_order WHERE psono = '" . $krfconn->real_escape_string($current_psono) . "' LIMIT 1");
			if ($chkPso && $pRow = $chkPso->fetch_assoc()) {
				$pSt = strtolower(trim((string)($pRow['status'] ?? '')));
				if (in_array($pSt, ['cancel', 'cancelled', 'deleted', 'removed'], true)) {
					echo json_encode([
						"status" => "error",
						"message" => "Provisional Order #{$current_psono} is cancelled and cannot accept payments."
					]);
					exit;
				}
			}
		}

		$item_otherdiscount = 0;
		if (isset($orderItem['special_discount']) && is_numeric($orderItem['special_discount'])) {
			$item_otherdiscount = floatval($orderItem['special_discount']);
		} else if (isset($orderItem['otherdiscount']) && is_numeric($orderItem['otherdiscount'])) {
			$item_otherdiscount = floatval($orderItem['otherdiscount']);
		} else if ($passed_special_discount > 0 && count($orders) === 1) {
			$item_otherdiscount = $passed_special_discount;
		}

		// Resolve clientid, clientName, clientmobileno if empty or '0'
		if ((empty($clientid) || $clientid === '0') && !empty($current_psono)) {
			$cStmt = $krfconn->prepare("SELECT clientid, clientname, mobileno FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
			if ($cStmt) {
				$cStmt->bind_param("s", $current_psono);
				$cStmt->execute();
				$cRes = $cStmt->get_result();
				if ($cRow = $cRes->fetch_assoc()) {
					if (empty($clientid) || $clientid === '0') $clientid = $cRow['clientid'] ?? '';
					if (empty($clientName)) $clientName = $cRow['clientname'] ?? '';
					if (empty($clientmobileno)) $clientmobileno = $cRow['mobileno'] ?? '';
				}
				$cStmt->close();
			}
		}

		// Calculate target invoice amount (balance due)
		$target_invoice_amount = 0;
		if (isset($orderItem['invoice_amount']) && is_numeric($orderItem['invoice_amount'])) {
			$target_invoice_amount = floatval($orderItem['invoice_amount']);
		} else if ($passed_invoice_amount > 0) {
			$target_invoice_amount = $passed_invoice_amount;
		} else if (!empty($current_psono)) {
			$calcOrderTotal = 0;
			$dispSono = "";
			$dispDinv = "";
			$chkDisp = $krfconn->query("
				SELECT grand_total, total_amount, dinvoiceno, sono 
				FROM krf_sales_order 
				WHERE (psono = '" . $krfconn->real_escape_string($current_psono) . "' OR sono = '" . $krfconn->real_escape_string($current_psono) . "') 
				ORDER BY CASE WHEN invoicetype = 'sale' THEN 1 ELSE 2 END, id DESC LIMIT 1
			");
			if ($chkDisp && $dRow = $chkDisp->fetch_assoc()) {
				$soGrand = floatval($dRow['grand_total'] > 0 ? $dRow['grand_total'] : $dRow['total_amount']);
				if ($soGrand > 0) {
					$calcOrderTotal = $soGrand;
					$dispSono = $dRow['sono'] ?? "";
					$dispDinv = $dRow['dinvoiceno'] ?? "";
				}
			}
			if ($calcOrderTotal <= 0) {
				$stIt = $krfconn->prepare("SELECT IFNULL(SUM(amount), 0) AS total FROM krf_provisional_sales_order_items WHERE psono = ?");
				if ($stIt) {
					$stIt->bind_param("s", $current_psono);
					$stIt->execute();
					$rIt = $stIt->get_result()->fetch_assoc();
					$calcOrderTotal = floatval($rIt['total'] ?? 0);
					$stIt->close();
				}

				if ($calcOrderTotal > 0) {
					// Include box packing / delivery charges from krf_deliveryaddress_location
					$stLoc = $krfconn->prepare("
						SELECT 
							SUM(CASE 
								WHEN pickuptype = 'boxpacking' THEN (CASE WHEN freightamt > 0 THEN freightamt WHEN deliverycharges > 0 THEN deliverycharges ELSE (noofbox * chargesperbox) END)
								ELSE (CASE WHEN freightamt > 0 THEN freightamt ELSE deliverycharges END)
							END) AS loc_charges
						FROM krf_deliveryaddress_location 
						WHERE psono = ? AND (tedit IS NULL OR tedit = '')
					");
					$locCharges = 0;
					if ($stLoc) {
						$stLoc->bind_param("s", $current_psono);
						$stLoc->execute();
						$rLoc = $stLoc->get_result()->fetch_assoc();
						$locCharges = floatval($rLoc['loc_charges'] ?? 0);
						$stLoc->close();
					}

					$stFc = $krfconn->prepare("SELECT IFNULL(SUM(payable_freightamount), 0) AS fc_charges FROM krf_freightcharges WHERE psono = ?");
					$fcCharges = 0;
					if ($stFc) {
						$stFc->bind_param("s", $current_psono);
						$stFc->execute();
						$rFc = $stFc->get_result()->fetch_assoc();
						$fcCharges = floatval($rFc['fc_charges'] ?? 0);
						$stFc->close();
					}

					$calcOrderTotal += max($locCharges, $fcCharges);
				}
			}

			$pArr = ["'" . $krfconn->real_escape_string($current_psono) . "'"];
			if (!empty($dispSono)) $pArr[] = "'" . $krfconn->real_escape_string($dispSono) . "'";
			if (!empty($dispDinv)) $pArr[] = "'" . $krfconn->real_escape_string($dispDinv) . "'";
			$pIn = implode(',', array_unique($pArr));
			$totPRes = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS paid, IFNULL(SUM(otherdiscount), 0) AS disc FROM advance_payment_transactions WHERE invoiceno IN ({$pIn}) AND (tedit IS NULL OR tedit = '')");
			$calcPaid = 0;
			if ($totPRes && $tpRow = $totPRes->fetch_assoc()) {
				$calcPaid = floatval($tpRow['paid'] ?? 0) + floatval($tpRow['disc'] ?? 0);
			}
			$target_invoice_amount = round(max(0, $calcOrderTotal - $calcPaid), 2);
		}

		$inv_advanceamount = $current_amount;
		$inv_roundoffamount = 0.00;
		$inv_otherdiscount = $item_otherdiscount;
		$inv_remarks = $remarks;

		if ($inv_otherdiscount > 0) {
			$inv_advanceamount = $current_amount;
			$inv_roundoffamount = 0.00;
			if (empty($inv_remarks)) {
				$inv_remarks = "Payment against Provisional Order #{$current_psono} (Special Discount: ₹ " . number_format($inv_otherdiscount, 2) . ")";
			} else if (stripos($inv_remarks, 'Special Discount:') === false && stripos($inv_remarks, 'Other Discount:') === false) {
				$inv_remarks .= " (Special Discount: ₹ " . number_format($inv_otherdiscount, 2) . ")";
			}
		} else if (!empty($current_psono) && $target_invoice_amount > 0) {
			$diff = round($current_amount - $target_invoice_amount, 2);

			if ($diff > 10.00) {
				// Received is greater than invoice amount by > 10 Rs:
				// Store the FULL received amount in a single row against psono in invoiceno column
				$inv_advanceamount = $current_amount;
				$inv_roundoffamount = 0.00;
				$inv_otherdiscount = 0.00;
				if (empty($inv_remarks)) {
					$inv_remarks = "Payment against Provisional Order #{$current_psono} (Advance: ₹ " . number_format($diff, 2) . ")";
				} else if (stripos($inv_remarks, 'Advance:') === false) {
					$inv_remarks .= " (Advance: ₹ " . number_format($diff, 2) . ")";
				}
			} else if ($diff > 0.00 && $diff <= 10.00) {
				// Received is greater than invoice amount by <= 10 Rs:
				// Update roundoffamount against invoice in advance_payment_transactions
				$inv_advanceamount = $target_invoice_amount;
				$inv_roundoffamount = $diff;
				$inv_otherdiscount = 0.00;
				if (empty($inv_remarks)) {
					$inv_remarks = "Payment against Provisional Order #{$current_psono} (Round-off: +₹ " . number_format($diff, 2) . ")";
				} else if (stripos($inv_remarks, 'Round-off:') === false) {
					$inv_remarks .= " (Round-off: +₹ " . number_format($diff, 2) . ")";
				}
			} else if ($diff < 0.00 && abs($diff) <= 10.00) {
				// Received is less than invoice amount by <= 10 Rs:
				// Update as other discount in column otherdiscount
				$inv_advanceamount = $current_amount;
				$inv_roundoffamount = 0.00;
				$inv_otherdiscount = abs($diff);
				if (empty($inv_remarks)) {
					$inv_remarks = "Payment against Provisional Order #{$current_psono} (Other Discount: ₹ " . number_format(abs($diff), 2) . ")";
				} else if (stripos($inv_remarks, 'Other Discount:') === false) {
					$inv_remarks .= " (Other Discount: ₹ " . number_format(abs($diff), 2) . ")";
				}
			} else {
				$inv_advanceamount = $current_amount;
				$inv_roundoffamount = 0.00;
				$inv_otherdiscount = 0.00;
			}
		}

		// Check if otherdiscount column exists in advance_payment_transactions
		static $aptColChecked = false;
		if (!$aptColChecked) {
			$chkCol = $krfconn->query("SHOW COLUMNS FROM advance_payment_transactions LIKE 'otherdiscount'");
			if ($chkCol && $chkCol->num_rows == 0) {
				@$krfconn->query("ALTER TABLE advance_payment_transactions ADD COLUMN otherdiscount DECIMAL(15,2) DEFAULT 0.00 AFTER roundoffamount");
			}
			$aptColChecked = true;
		}

		$sql = "INSERT INTO advance_payment_transactions 
	        (companyid, subcompanyid, projectid, clientid, clientname, advanceamount, roundoffamount, otherdiscount, utrnumber, paymentdate, purposeofpayment, sourceofpayment, receivedtoaccountid, remarks, invoiceno, allocated_datetime, allocatedby, receivedby, receivedlocation, receiptpath, verification) 
	        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unverified')";

		$stmt = $krfconn->prepare($sql);
		if (!$stmt) {
			// Fallback in case table doesn't have otherdiscount column
			$sqlFallback = "INSERT INTO advance_payment_transactions 
		        (companyid, subcompanyid, projectid, clientid, clientname, advanceamount, roundoffamount, utrnumber, paymentdate, purposeofpayment, sourceofpayment, receivedtoaccountid, remarks, invoiceno, allocated_datetime, allocatedby, receivedby, receivedlocation, receiptpath, verification) 
		        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unverified')";
			$stmt = $krfconn->prepare($sqlFallback);
			if ($stmt) {
				$stmt->bind_param(
					"iiissdsssssssssssss",
					$companyid,
					$subcompanyid,
					$projectid,
					$clientid,
					$clientName,
					$inv_advanceamount,
					$inv_roundoffamount,
					$utrnumber,
					$paymentdate,
					$purpose,
					$source,
					$receivedtoaccountid,
					$inv_remarks,
					$current_psono,
					$allocated_datetime,
					$allocatedby,
					$casgreceivedby,
					$casgreceuvelocation,
					$receiptpath
				);
			} else {
				$last_error = $krfconn->error;
				error_log("Failed to prepare advance payment transaction insert: " . $krfconn->error);
				continue;
			}
		} else {
			$stmt->bind_param(
				"iiissdddssssssssssss",
				$companyid,
				$subcompanyid,
				$projectid,
				$clientid,
				$clientName,
				$inv_advanceamount,
				$inv_roundoffamount,
				$inv_otherdiscount,
				$utrnumber,
				$paymentdate,
				$purpose,
				$source,
				$receivedtoaccountid,
				$inv_remarks,
				$current_psono,
				$allocated_datetime,
				$allocatedby,
				$casgreceivedby,
				$casgreceuvelocation,
				$receiptpath
			);
		}

		if ($stmt->execute()) {
			$last_psono = $current_psono;
			$success_count++;
			$insertedAptId = $stmt->insert_id ?: $krfconn->insert_id;

			if ($isCreditNote) {
				// 1. Mark Credit Note as Used in krf_creditnote
				$nowTime = date('Y-m-d H:i:s');
				$escUser = $krfconn->real_escape_string($loginname);
				$escPso = $krfconn->real_escape_string($current_psono);
				$escCnRemark = $krfconn->real_escape_string("Adjusted against Provisional Order #{$current_psono} by {$loginname} on {$nowTime}");

				$krfconn->query("UPDATE krf_creditnote 
					SET status = 'Used', 
						reason = IF(reason IS NULL OR reason = '', '{$escCnRemark}', CONCAT(reason, ' | ', '{$escCnRemark}')), 
						tedit = 'Used against {$escPso} by {$escUser} on {$nowTime}', 
						updated_at = NOW() 
					WHERE creditnote_no = '" . $krfconn->real_escape_string($creditnote_no) . "' AND status = 'Active'");

				// 2. Mark advance payment transaction as verified immediately
				$krfconn->query("UPDATE advance_payment_transactions 
					SET verification = 'verified', 
						verificationby = '{$escUser}', 
						verificationat = NOW() 
					WHERE sno = {$insertedAptId}");

				// 3. Insert receipt statement in client statement ledger
				$descStatement = "Adjustment of Credit Note {$creditnote_no} against Order #{$current_psono}. Processed by {$loginname} on {$nowTime}.";
				$stmtStmt = $krfconn->prepare("INSERT INTO krf_tbl_client_statement (
					project_id, client_id, txn_date, txn_type, txn_no, description, debit_amount, credit_amount, running_balance, created_at
				) VALUES (?, ?, CURDATE(), 'RECEIPT', ?, ?, ?, 0.00, 0.00, NOW())");
				if ($stmtStmt) {
					$stmtStmt->bind_param("isssd", $projectid, $clientid, $creditnote_no, $descStatement, $inv_advanceamount);
					$stmtStmt->execute();
					$stmtStmt->close();
				}

				// 4. Update provisional sales order paid_amount
				$totPaidQ = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) + IFNULL(SUM(otherdiscount), 0) AS total_paid FROM advance_payment_transactions WHERE invoiceno = '{$escPso}' AND (tedit IS NULL OR tedit = '')");
				$totPaidVal = 0;
				if ($totPaidQ && $tpR = $totPaidQ->fetch_assoc()) {
					$totPaidVal = floatval($tpR['total_paid'] ?? 0);
				}
				$krfconn->query("UPDATE krf_provisional_sales_order SET paid_amount = {$totPaidVal}, updated_at = NOW(), updated_by = '{$loginid}' WHERE projectid = '{$projectid}' AND psono = '{$escPso}'");

				// 5. Convert and fulfill provisional order to sales order if not already converted
				$finalSono = "";
				$chkSo = $krfconn->query("SELECT sono FROM krf_sales_order WHERE psono = '{$escPso}' ORDER BY id DESC LIMIT 1");
				if ($chkSo && $soR = $chkSo->fetch_assoc()) {
					$finalSono = $soR['sono'];
				} else {
					if (function_exists('createSalesOrderFromProvisional')) {
						$convRes = createSalesOrderFromProvisional($krfconn, $current_psono, $loginname);
						if (is_array($convRes) && !empty($convRes['sono'])) {
							$finalSono = $convRes['sono'];
						}
					}
				}
				$krfconn->query("UPDATE krf_provisional_sales_order SET status = 'Confirmed', updated_at = NOW(), updated_by = '{$loginid}' WHERE projectid = '{$projectid}' AND psono = '{$escPso}'");
				$last_sono = $finalSono;

			} else {
				// Update provisional order bookingno, bookingfilepath, and status
				if (!empty($current_psono)) {
					if (!empty($bookingfilepath) && !empty($bookingno)) {
						$updSql = "UPDATE krf_provisional_sales_order SET bookingno = ?, bookingfilepath = ?, status = IF(status = 'Draft', 'Pending Approval', status), updated_at = NOW(), updated_by = ? WHERE projectid = ? AND psono = ?";
						$updStmt = $krfconn->prepare($updSql);
						if ($updStmt) {
							$updStmt->bind_param("sssis", $bookingno, $bookingfilepath, $loginid, $projectid, $current_psono);
							$updStmt->execute();
							$updStmt->close();
						}
					} else if (!empty($bookingno)) {
						$updSql = "UPDATE krf_provisional_sales_order SET bookingno = ?, status = IF(status = 'Draft', 'Pending Approval', status), updated_at = NOW(), updated_by = ? WHERE projectid = ? AND psono = ?";
						$updStmt = $krfconn->prepare($updSql);
						if ($updStmt) {
							$updStmt->bind_param("ssis", $bookingno, $loginid, $projectid, $current_psono);
							$updStmt->execute();
							$updStmt->close();
						}
					} else {
						$krfconn->query("UPDATE krf_provisional_sales_order SET status = IF(status = 'Draft', 'Pending Approval', status) WHERE projectid='".$projectid."' AND psono='".$current_psono."'");
					}
				}

				// Dispatch WhatsApp alert to Accounts Department (ACDP) for verification
				sendUnverifiedPaymentAlertToACDP(
					$krfconn,
					$current_psono,
					$inv_advanceamount,
					$source,
					$utrnumber,
					$clientName,
					$clientmobileno,
					$receiptpath,
					$paymentdate,
					$inv_remarks,
					$casgreceivedby,
					$casgreceuvelocation,
					$loginname
				);
			}
		} else {
			$last_error = $stmt->error ?: $krfconn->error;
			error_log("Failed to execute advance payment transaction insert: " . $last_error);
		}
		$stmt->close();
	}

	if ($success_count > 0) {
		if ($isCreditNote) {
			echo json_encode([
				"status" => "success",
				"message" => "Credit Note {$creditnote_no} of ₹" . number_format($amount, 2) . " applied successfully against Provisional Order #{$last_psono}. Payment confirmed and fulfilled.",
				"psono" => $last_psono,
				"sono" => $last_sono,
				"verification" => "verified",
				"is_verified" => true,
				"clientid" => $clientid,
				"receiptpath" => $receiptpath
			]);
			exit;
		}
		$extraNote = "";
		if (!empty($current_psono) && $target_invoice_amount > 0 && ($current_amount - $target_invoice_amount) > 10) {
			$diff = round($current_amount - $target_invoice_amount, 2);
			$extraNote = " (Full Received: ₹ " . number_format($current_amount, 2) . ", including Advance: ₹ " . number_format($diff, 2) . ")";
		} else if (isset($inv_roundoffamount) && $inv_roundoffamount > 0) {
			$extraNote = " (Round-off: +₹ " . number_format($inv_roundoffamount, 2) . ")";
		} else if (isset($inv_otherdiscount) && $inv_otherdiscount > 0) {
			$extraNote = " (Other Discount: ₹ " . number_format($inv_otherdiscount, 2) . ")";
		}
		echo json_encode([
			"status" => "success",
			"message" => "Payment advice logged as Unverified{$extraNote} for $success_count order(s). WhatsApp alert sent to Accounts Department (ACDP) for verification.",
			"psono" => $last_psono,
			"sono" => "",
			"verification" => "unverified",
			"is_verified" => false,
			"clientid" => $clientid,
			"receiptpath" => $receiptpath
		]);
	} else {
		echo json_encode(["status" => "error", "message" => !empty($last_error) ? "Failed to process payments: " . $last_error : "Failed to process payments."]);
	}
}else if($action=="ProvisionaOrderLikebyUserName"){
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $likelyclientname = $_POST['clientName'] ?? 0;
  $ppdata = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "likelyclientname", $likelyclientname);
  $filtered = [];
  foreach ($ppdata as $ord) {
      $st = strtolower(trim((string)($ord['status'] ?? '')));
      if (in_array($st, ['cancel', 'cancelled', 'deleted', 'removed'], true)) {
          continue;
      }
      if (!empty($ord['items']) && is_array($ord['items'])) {
          $activeItems = [];
          $itemsAmt = 0;
          $totQty = 0;
          foreach ($ord['items'] as $it) {
              $itSt = strtolower(trim((string)($it['status'] ?? '')));
              if (in_array($itSt, ['cancel', 'cancelled', 'deleted', 'removed'], true)) {
                  continue;
              }
              $activeItems[] = $it;
              $itemsAmt += (float)($it['amount'] ?? 0);
              $totQty += (float)($it['quantity'] ?? 0);
          }
          if (empty($activeItems)) {
              continue;
          }
          $ord['items'] = $activeItems;
          $ord['items_amount'] = $itemsAmt;
          $ord['total_quantity'] = $totQty;
          $ord['total_amount'] = round($itemsAmt + (float)($ord['freight_amount'] ?? 0) + (float)($ord['box_packing_amount'] ?? 0), 2);
          $ord['pending_balance'] = round(max(0, $ord['total_amount'] - (float)($ord['paid_amount'] ?? 0)), 2);
      }
      $filtered[] = $ord;
  }
  echo json_encode(array_values($filtered));
}else if($action=="GetProvisionaOrderData"){
  $companyid = $_POST['companyid'] ?? 0;
  $subcompanyid = $_POST['subcompanyid'] ?? 0;
  $projectid = $_POST['projectid'] ?? 0;
  $shortby = $_POST['shortby'] ?? 0;
  $shortbyvalue = $_POST['shortbyvalue'] ?? 0;
  $purpose = $_POST['purpose'] ?? ($_GET['purpose'] ?? '');
  $target_psono = $_POST['psono'] ?? ($_GET['psono'] ?? '');
  $target_sono = $_POST['sono'] ?? ($_GET['sono'] ?? '');
  $target_invoiceno = $_POST['invoiceno'] ?? ($_GET['invoiceno'] ?? '');
  $ppdata = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, $shortby, $shortbyvalue);
  $filtered = [];
  foreach ($ppdata as $ord) {
      $st = strtolower(trim((string)($ord['status'] ?? '')));
      if (in_array($st, ['cancel', 'cancelled', 'deleted', 'removed'], true)) {
          continue;
      }
      $ordPsono = trim($ord['psono'] ?? '');

      // Check if this provisional order has an active dispatched invoice or sales order
      $chkSi = $krfconn->query("SELECT id, invoiceno, payableamount, invoiceamount, status FROM sales_invoice WHERE psono = '" . $krfconn->real_escape_string($ordPsono) . "' AND LOWER(IFNULL(status, '')) != 'cancelled' ORDER BY id DESC LIMIT 1");
      $siRow = ($chkSi && $chkSi->num_rows > 0) ? $chkSi->fetch_assoc() : null;

      $chkSo = $krfconn->query("SELECT id, sono, dinvoiceno, total_amount, grand_total, status FROM krf_sales_order WHERE psono = '" . $krfconn->real_escape_string($ordPsono) . "' ORDER BY CASE WHEN invoicetype = 'sale' THEN 1 ELSE 2 END, id DESC LIMIT 1");
      $soRow = ($chkSo && $chkSo->num_rows > 0) ? $chkSo->fetch_assoc() : null;

      $isDispatchedOrder = ($siRow !== null || $soRow !== null);
      if ($isDispatchedOrder) {
          $dispInvoiceno = $siRow['invoiceno'] ?? ($soRow['dinvoiceno'] ?? '');
          $dispSono = $soRow['sono'] ?? '';
          $dispStatus = strtolower(trim((string)($siRow['status'] ?? ($soRow['status'] ?? ''))));

          $dispItems = [];
          $dispItemsAmt = 0;
          $dispTotQty = 0;

          if ($siRow) {
              $siiQ = $krfconn->query("SELECT id, productid AS product_id, productname AS product_name, quantity, rate, taxableamount, amount, deliverydate, status FROM sales_invoice_items WHERE invoice_id = " . intval($siRow['id']) . " AND LOWER(IFNULL(status, '')) != 'cancelled' AND productid != 'BOX_PACKING' ORDER BY id ASC");
              if ($siiQ) {
                  while ($sii = $siiQ->fetch_assoc()) {
                      $dispItems[] = $sii;
                      $dispItemsAmt += floatval($sii['amount'] ?? ($sii['taxableamount'] ?? ($sii['quantity'] * $sii['rate'])));
                      $dispTotQty += floatval($sii['quantity'] ?? 0);
                  }
              }
              $grandTotal = floatval($siRow['payableamount'] > 0 ? $siRow['payableamount'] : $siRow['invoiceamount']);
          } else {
              $soiQ = $krfconn->query("SELECT id, product_id, product_name, COALESCE(NULLIF(allocate_ordqty, 0), quantity) AS quantity, rate, taxableamount, COALESCE(NULLIF(amount, 0), taxableamount, (quantity * rate)) AS amount, deliverydate, status FROM krf_sales_order_items WHERE (sono = '" . $krfconn->real_escape_string($dispSono) . "' OR (psono = '" . $krfconn->real_escape_string($ordPsono) . "' AND (invoiceno IS NULL OR invoiceno = ''))) AND LOWER(IFNULL(status, '')) != 'cancelled' AND product_id != 'BOX_PACKING' ORDER BY id ASC");
              if ($soiQ) {
                  while ($soi = $soiQ->fetch_assoc()) {
                      $dispItems[] = $soi;
                      $dispItemsAmt += floatval($soi['amount'] ?? 0);
                      $dispTotQty += floatval($soi['quantity'] ?? 0);
                  }
              }
              $grandTotal = floatval($soRow['grand_total'] > 0 ? $soRow['grand_total'] : $soRow['total_amount']);
              $bpcAmt = floatval($ord['box_packing_amount'] ?? 0);
              if ($bpcAmt > 0 && $grandTotal < ($dispItemsAmt + $bpcAmt)) {
                  $grandTotal = round($dispItemsAmt + $bpcAmt + floatval($ord['freight_amount'] ?? 0), 2);
              }
          }

          $pArr = ["'" . $krfconn->real_escape_string($ordPsono) . "'"];
          if (!empty($dispSono)) $pArr[] = "'" . $krfconn->real_escape_string($dispSono) . "'";
          if (!empty($dispInvoiceno)) $pArr[] = "'" . $krfconn->real_escape_string($dispInvoiceno) . "'";
          $pIn = implode(',', array_unique($pArr));
          $totPRes = $krfconn->query("SELECT IFNULL(SUM(advanceamount), 0) AS actual_paid FROM advance_payment_transactions WHERE invoiceno IN ({$pIn}) AND (tedit IS NULL OR tedit = '')");
          $paidAmt = 0;
          if ($totPRes && $tpRow = $totPRes->fetch_assoc()) {
              $paidAmt = floatval($tpRow['actual_paid'] ?? 0);
          }

          $isFullyDispatched = in_array($dispStatus, ['fulldispatch', 'completed', 'delivered'], true);
          if ($isFullyDispatched || $paidAmt >= $grandTotal) {
              $balAmt = 0.0;
          } else {
              $balAmt = max(0.0, round($grandTotal - $paidAmt, 2));
          }

          if (!empty($dispItems)) {
              $ord['items'] = $dispItems;
              $ord['items_amount'] = $dispItemsAmt;
              $ord['total_quantity'] = $dispTotQty;
              $ord['total_amount'] = $grandTotal;
              $ord['paid_amount'] = $paidAmt;
              $ord['pending_balance'] = $balAmt;
              $ord['is_dispatch_invoice'] = true;
              $ord['dinvoiceno'] = $dispInvoiceno;
              $ord['sono'] = $dispSono;
              $filtered[] = $ord;
              continue;
          }
      }

      if (!empty($ord['items']) && is_array($ord['items'])) {
          $activeItems = [];
          $itemsAmt = 0;
          $totQty = 0;
          foreach ($ord['items'] as $it) {
              $itSt = strtolower(trim((string)($it['status'] ?? '')));
              if (in_array($itSt, ['cancel', 'cancelled', 'deleted', 'removed'], true)) {
                  continue;
              }
              $activeItems[] = $it;
              $itemsAmt += (float)($it['amount'] ?? 0);
              $totQty += (float)($it['quantity'] ?? 0);
          }
          if (empty($activeItems)) {
              continue;
          }
          $ord['items'] = $activeItems;
          $ord['items_amount'] = $itemsAmt;
          $ord['total_quantity'] = $totQty;
          $ord['total_amount'] = round($itemsAmt + (float)($ord['freight_amount'] ?? 0) + (float)($ord['box_packing_amount'] ?? 0), 2);
          $ord['pending_balance'] = round(max(0, $ord['total_amount'] - (float)($ord['paid_amount'] ?? 0)), 2);
      }
      $filtered[] = $ord;
  }
  echo json_encode(array_values($filtered));
}else if($action=="UnallocatedAmounttoAllocat"){
	$companyid       = $_POST['companyid'] ?? 0;
	$subcompanyid    = $_POST['subcompanyid'] ?? 0;
	$projectid       = $_POST['projectid'] ?? 0;
	$loginid         = $_POST['loginid'] ?? 0;
	$unallocatedbsno = $_POST['unallocatedbsno'] ?? 0;
	$toallocatedbsno = $_POST['toallocatedbsno'] ?? 0;
	$response = [
	    'status' => 'error',
	    'message' => 'No update performed'
	];

	// Get provisional sales orders
	$provorder = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "id", $toallocatedbsno);
	if (count($provorder) > 0 && $unallocatedbsno) {
	    $provkey    = $provorder[0];
	    $ordStatus  = strtolower(trim((string)($provkey['status'] ?? '')));
	    if (in_array($ordStatus, ['cancel', 'cancelled', 'deleted', 'removed'], true)) {
	        echo json_encode(['status' => 'error', 'message' => 'Cannot allocate amount to a cancelled provisional order.']);
	        exit;
	    }
	    $psono      = $provkey['psono'];
	    $clientid   = $provkey['clientid'];
	    $clientname = $provkey['clientname'];
	    if ($psono && $clientid) {
	        // Prepare update statement for advance_payment_transactions
	        $stmt = $krfconn->prepare("
	            UPDATE advance_payment_transactions 
	            SET allocated_datetime = ?, clientid = ?, invoiceno = ?, clientname = ?, allocatedby = ?
	            WHERE sno = ? AND (tedit IS NULL OR tedit = '')
	        ");
	        $allocated_datetime = date('Y-m-d H:i:s');
	        $stmt->bind_param('sssssi', $allocated_datetime, $clientid, $psono, $clientname, $loginid, $unallocatedbsno);
	        if ($stmt->execute()) {
	            $stmt->close();

	            // 1. Update krf_provisional_sales_order status to 'Confirmed' if status is 'Draft'
	            $updPsoStmt = $krfconn->prepare("
	                UPDATE krf_provisional_sales_order 
	                SET status = 'Confirmed', updated_at = NOW() 
	                WHERE psono = ? AND status = 'Draft'
	            ");
	            if ($updPsoStmt) {
	                $updPsoStmt->bind_param('s', $psono);
	                $updPsoStmt->execute();
	                $updPsoStmt->close();
	            }

	            // 2. Update krf_production_stockorder status to 'Confirmed' if status is 'unconfirmed'
	            $updStockStmt = $krfconn->prepare("
	                UPDATE krf_production_stockorder 
	                SET status = 'Confirmed', updated_at = NOW() 
	                WHERE psono = ? AND (LOWER(status) = 'unconfirmed' OR status = '' OR status IS NULL)
	            ");
	            if ($updStockStmt) {
	                $updStockStmt->bind_param('s', $psono);
	                $updStockStmt->execute();
	                $updStockStmt->close();
	            }

	            // 3. Update krf_productionorder_consumed confirmstatus to 'Confirmed' if unconfirmed/pending
	            $updConsStmt = $krfconn->prepare("
	                UPDATE krf_productionorder_consumed 
	                SET confirmstatus = 'Confirmed' 
	                WHERE psono = ? AND (LOWER(confirmstatus) = 'unconfirmed' OR LOWER(confirmstatus) = 'pending' OR confirmstatus = '' OR confirmstatus IS NULL)
	            ");
	            if ($updConsStmt) {
	                $updConsStmt->bind_param('s', $psono);
	                $updConsStmt->execute();
	                $updConsStmt->close();
	            }

	            $response['status'] = 'success';
	            $response['message'] = 'Advance payment allocated and order confirmed successfully';
	        } else {
	            $response['status'] = 'error';
	            $response['message'] = 'Database update failed: ' . $stmt->error;
	            $stmt->close();
	        }
	    }
	}
	echo json_encode($response);
}

function chkfornamereadyexist($table, $column, $value, $conn, $conditions = '') {
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

if($action=="CheckUTRExist"){
    $utrnumber = $_POST['utrnumber'] ?? '';
    $projectid = $_POST['projectid'] ?? 0;
    
    if(empty($utrnumber)){
        echo json_encode(["status" => "error", "message" => "UTR number is empty"]);
        exit;
    }

    $sql = "SELECT t.*, i.product_name, i.quantity 
            FROM advance_payment_transactions t 
            LEFT JOIN krf_provisional_sales_order_items i ON t.invoiceno = i.psono
            WHERE t.utrnumber = ? AND t.projectid = ? AND (t.tedit IS NULL OR t.tedit = '')
            LIMIT 1";
    
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("si", $utrnumber, $projectid);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($row = $result->fetch_assoc()){
        echo json_encode([
            "status" => "exists",
            "message" => "This UTR number already exists in the system.",
            "data" => [
                "psono" => $row['invoiceno'],
                "clientname" => $row['clientname'],
                "amount" => number_format($row['advanceamount'], 2),
                "date" => date('d-M-Y', strtotime($row['paymentdate'])),
                "product" => $row['product_name'] ?? 'N/A',
                "qty" => $row['quantity'] ?? '0'
            ]
        ]);
    } else {
        echo json_encode(["status" => "not_found"]);
    }
    $stmt->close();
}else if($action=="RemoveAllocation"){
    $sno = $_POST['sno'] ?? 0;
    
    $sql = "UPDATE advance_payment_transactions SET invoiceno = '', allocated_datetime = NULL WHERE sno = ? AND (tedit IS NULL OR tedit = '')";
    $stmt = $krfconn->prepare($sql);
    $stmt->bind_param("i", $sno);
    
    if($stmt->execute()){
        echo json_encode(["status" => "success", "message" => "Allocation removed successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to remove allocation: " . $stmt->error]);
    }
    $stmt->close();
}else if($action=="oldorderadvacepayment" || $action=="oldorderadvancepayment"){
    $companyid           = $_POST['companyid'] ?? 0;
    $subcompanyid        = $_POST['subcompanyid'] ?? 0;
    $projectid           = $_POST['projectid'] ?? 0;
    $clientid            = $_POST['clientid'] ?? '';
    $clientName          = $_POST['clientname'] ?? '';
    $clientmobileno      = $_POST['clientmobileno'] ?? '';
    $amount              = floatval($_POST['amount'] ?? 0);
    $utrnumber           = $_POST['utrnumber'] ?? '';
    $paymentdate         = !empty($_POST['paymentdate']) ? $_POST['paymentdate'] : date('Y-m-d');
    $purpose             = $_POST['purposeofamount'] ?? 'Advance Payment';
    $source              = $_POST['sourceofpayment'] ?? 'Cash';
    $receivedtoaccountid = $_POST['receivedtoaccountid'] ?? '';
    $remarks             = $_POST['remarks'] ?? '';
    $invoiceno           = $_POST['invoiceno'] ?? ($_POST['psono'] ?? '');
    $bookingno           = trim($_POST['bookingno'] ?? '');
    $allocated_datetime  = date('Y-m-d H:i:s');
    $loginname           = $_POST['loginname'] ?? '';
    $loginid             = $_POST['loginid'] ?? '';
    $allocatedby         = $loginid;
    $casgreceivedby      = !empty($_POST['casgreceivedby']) ? $_POST['casgreceivedby'] : $loginname;
    $casgreceuvelocation = $_POST['casgreceuvelocation'] ?? '';

    if (empty($invoiceno) || $amount <= 0) {
        echo json_encode(["status" => "error", "message" => "Provisional order number and a valid payment amount are required."]);
        exit;
    }

    // Auto-resolve client details from krf_provisional_sales_order if missing
    if (empty($clientid) || empty($clientName)) {
        $stmtPso = $krfconn->prepare("SELECT clientid, clientname, mobileno FROM krf_provisional_sales_order WHERE psono = ? AND projectid = ? LIMIT 1");
        if ($stmtPso) {
            $stmtPso->bind_param("si", $invoiceno, $projectid);
            $stmtPso->execute();
            $pRes = $stmtPso->get_result();
            if ($pRow = $pRes->fetch_assoc()) {
                if (empty($clientid)) $clientid = $pRow['clientid'];
                if (empty($clientName)) $clientName = $pRow['clientname'];
                if (empty($clientmobileno)) $clientmobileno = $pRow['mobileno'];
            }
            $stmtPso->close();
        }
    }

    // Handle Payment Receipt Upload (optional / if provided)
    $receiptpath = "";
    if (isset($_FILES['paymentreceipt']) && $_FILES['paymentreceipt']['error'] === 0) {
        $targetoDir = '../../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/OrderPaymentReceipt/' . date('Y-m-d') . "/";
        if (!is_dir($targetoDir)) mkdir($targetoDir, 0777, true);
        $ofilename = time() . '_' . basename($_FILES['paymentreceipt']['name']);
        $targetPath = $targetoDir . $ofilename;
        if (move_uploaded_file($_FILES['paymentreceipt']['tmp_name'], $targetPath)) {
            $receiptpath = str_replace("../", "", $targetPath);
        }
    }

    // Handle Booking Receipt Upload (optional / if provided)
    $bookingfilepath = "";
    if (isset($_FILES['bookingreceipt']) && $_FILES['bookingreceipt']['error'] === 0) {
        $targetbDir = '../../attachementfile/' . $companyid . '/' . $subcompanyid . '/' . $projectid . '/BookingReceipt/' . date('Y-m-d') . "/";
        if (!is_dir($targetbDir)) mkdir($targetbDir, 0777, true);
        $bfilename = time() . '_booking_' . basename($_FILES['bookingreceipt']['name']);
        $targetbPath = $targetbDir . $bfilename;
        if (move_uploaded_file($_FILES['bookingreceipt']['tmp_name'], $targetbPath)) {
            $bookingfilepath = str_replace("../", "", $targetbPath);
        }
    }

    // Insert into advance_payment_transactions
    $sql = "INSERT INTO advance_payment_transactions 
            (companyid, subcompanyid, projectid, clientid, clientname, advanceamount, utrnumber, paymentdate, purposeofpayment, sourceofpayment, receivedtoaccountid, remarks, invoiceno, allocated_datetime, allocatedby, receivedby, receivedlocation, receiptpath, verification) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'unverified')";

    $stmt = $krfconn->prepare($sql);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Prepare failed: " . $krfconn->error]);
        exit;
    }

    $stmt->bind_param(
        "iiissdssssssssssss",
        $companyid,
        $subcompanyid,
        $projectid,
        $clientid,
        $clientName,
        $amount,
        $utrnumber,
        $paymentdate,
        $purpose,
        $source,
        $receivedtoaccountid,
        $remarks,
        $invoiceno,
        $allocated_datetime,
        $allocatedby,
        $casgreceivedby,
        $casgreceuvelocation,
        $receiptpath
    );

    if ($stmt->execute()) {
        $stmt->close();

        // Update provisional order bookingno, bookingfilepath (if provided) and status to 'Pending Approval' if it was 'Draft'
        if (!empty($bookingfilepath) && !empty($bookingno)) {
            $updSql = "UPDATE krf_provisional_sales_order SET bookingno = ?, bookingfilepath = ?, status = IF(status = 'Draft', 'Pending Approval', status), updated_at = NOW(), updated_by = ? WHERE projectid = ? AND psono = ?";
            $updStmt = $krfconn->prepare($updSql);
            if ($updStmt) {
                $updStmt->bind_param("sssis", $bookingno, $bookingfilepath, $loginid, $projectid, $invoiceno);
                $updStmt->execute();
                $updStmt->close();
            }
        } else if (!empty($bookingno)) {
            $updSql = "UPDATE krf_provisional_sales_order SET bookingno = ?, status = IF(status = 'Draft', 'Pending Approval', status), updated_at = NOW(), updated_by = ? WHERE projectid = ? AND psono = ?";
            $updStmt = $krfconn->prepare($updSql);
            if ($updStmt) {
                $updStmt->bind_param("ssis", $bookingno, $loginid, $projectid, $invoiceno);
                $updStmt->execute();
                $updStmt->close();
            }
        } else {
            $krfconn->query("UPDATE krf_provisional_sales_order SET status = IF(status = 'Draft', 'Pending Approval', status) WHERE projectid='".$projectid."' AND psono='".$invoiceno."'");
        }

        // Send WhatsApp alert to Accounts Department (ACDP)
        if (function_exists('sendUnverifiedPaymentAlertToACDP')) {
            sendUnverifiedPaymentAlertToACDP(
                $krfconn,
                $invoiceno,
                $amount,
                $source,
                $utrnumber,
                $clientName,
                $clientmobileno,
                $receiptpath,
                $paymentdate,
                $remarks,
                $casgreceivedby,
                $casgreceuvelocation,
                $loginname
            );
        }

        echo json_encode([
            "status" => "success",
            "message" => "Advance payment of ₹" . number_format($amount, 2) . " updated successfully for Order #" . $invoiceno . ". Alert sent to Accounts Department for verification.",
            "psono" => $invoiceno,
            "advanceamount" => $amount,
            "verification" => "unverified"
        ]);
        exit;
    } else {
        $err = $stmt->error;
        $stmt->close();
        echo json_encode(["status" => "error", "message" => "Failed to save payment: " . $err]);
        exit;
    }
}
?>