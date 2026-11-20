<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set("Asia/Calcutta");
error_reporting(E_ALL);
require_once(__DIR__ . '/../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require_once(__DIR__ . '/../../loginCell/functions/login_functions.php');
include_once(__DIR__ . "/../functions/orders_functions.php");
include_once(__DIR__ . "/../functions/client_functions.php");
include_once(__DIR__ . "/../../whatsappnotification/whatsappsmsfunctions.php");

header('Content-Type: application/json; charset=utf-8');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'get_emergency_sowing_orders') {
    try {
        $companyid    = trim((string)($_POST['companyid'] ?? ($_GET['companyid'] ?? ($_SESSION['companyid'] ?? ''))));
        $subcompanyid = trim((string)($_POST['subcompanyid'] ?? ($_GET['subcompanyid'] ?? ($_SESSION['subcompanyid'] ?? ''))));
        $projectid    = (int)($_POST['projectid'] ?? ($_GET['projectid'] ?? ($_SESSION['projectid'] ?? 0)));
        $loginroleid  = (int)($_POST['loginroleid'] ?? ($_GET['loginroleid'] ?? ($_SESSION['loginroleid'] ?? 0)));
        $loginid      = trim((string)($_POST['loginid'] ?? ($_GET['loginid'] ?? ($_SESSION['loginid'] ?? ''))));

        $whereConditions = ["o.status = 'Emergency Sowing'"];

        if ($projectid > 0) {
            $whereConditions[] = "o.projectid = " . (int)$projectid;
        }

        if ($companyid !== '') {
            $whereConditions[] = "o.companyid = '" . $krfconn->real_escape_string($companyid) . "'";
        }

        if ($loginroleid > 3 && $loginid !== '') {
            $whereConditions[] = "o.created_by = '" . $krfconn->real_escape_string($loginid) . "'";
        }

        $whereSql = implode(" AND ", $whereConditions);

        $sql = "
            SELECT
                o.id,
                o.psono,
                o.companyid,
                o.subcompanyid,
                o.projectid,
                o.clientid,
                o.clientname,
                o.emailid,
                o.delivery_address,
                o.districtname,
                o.statename,
                o.mobileno,
                o.payment_terms,
                o.remarks,
                o.status,
                o.pickupfrom,
                o.created_by,
                o.created_at,
                o.updated_at,
                o.emergencysowingapprovalby,
                o.emergencysowingapprovalat,

                i.product_id,
                i.product_sku,
                i.product_name,
                i.quantity,
                i.rate,
                i.amount AS item_amount,
                i.expecteddeliverydate,
                i.stockorder_no,

                COALESCE(item_total.orderamount, 0) AS orderamount,
                COALESCE(item_total.total_qty, 0) AS total_qty,
                COALESCE(advance_total.advanceamt, 0) AS advanceamt,
                (COALESCE(item_total.orderamount, 0) - COALESCE(advance_total.advanceamt, 0)) AS balanceamt,
                COALESCE(stk_info.sowingdate, '') AS sowingdate,
                COALESCE(stk_info.scionsowingdate, '') AS scionsowingdate,
                COALESCE(stk_info.stockorder_no, i.stockorder_no, '') AS actual_stockorderno

            FROM krf_provisional_sales_order o

            LEFT JOIN krf_provisional_sales_order_items i
                ON i.psono = o.psono

            LEFT JOIN (
                SELECT
                    psono,
                    SUM(COALESCE(amount, 0)) AS orderamount,
                    SUM(COALESCE(quantity, 0)) AS total_qty
                FROM krf_provisional_sales_order_items
                GROUP BY psono
            ) AS item_total
                ON item_total.psono = o.psono

            LEFT JOIN (
                SELECT
                    invoiceno,
                    clientid,
                    SUM(COALESCE(advanceamount, 0)) AS advanceamt
                FROM advance_payment_transactions
                WHERE tedit IS NULL OR tedit = ''
                GROUP BY invoiceno, clientid
            ) AS advance_total
                ON advance_total.invoiceno = o.psono
               AND advance_total.clientid = o.clientid

            LEFT JOIN (
                SELECT 
                    psono,
                    MIN(sowingdate) AS sowingdate,
                    MIN(scionsowingdate) AS scionsowingdate,
                    GROUP_CONCAT(DISTINCT stockorder_no SEPARATOR ', ') AS stockorder_no
                FROM krf_production_stockorder
                WHERE (sowingdate IS NOT NULL AND sowingdate != '0000-00-00')
                GROUP BY psono
            ) AS stk_info
                ON stk_info.psono = o.psono

            WHERE {$whereSql}

            ORDER BY o.created_at DESC, o.id DESC
        ";

        $res = $krfconn->query($sql);
        if (!$res) {
            throw new Exception("Query failed: " . $krfconn->error);
        }

        $ordersMap = [];

        // Load login users cache
        $usersMap = [];
        if (isset($authconn) && $authconn) {
           /* $uRes = $authconn->query("SELECT loginid, loginname, fullname, mobileno FROM logintable WHERE status = 'Active'");
*/
            $uRes = $authconn->query("SELECT loginid, loginname, loginname as fullname, mobileno FROM login_user_master WHERE isActive = 'yes'");
            if ($uRes) {
                while ($uRow = $uRes->fetch_assoc()) {
                    $usersMap[trim($uRow['loginid'])] = $uRow['fullname'] ?: $uRow['loginname'];
                }
            }
        }

        while ($row = $res->fetch_assoc()) {
            $psono = $row['psono'];
            $creatorLoginId = trim($row['created_by'] ?? '');
            $creatorName = $usersMap[$creatorLoginId] ?? $creatorLoginId;

            if (!isset($ordersMap[$psono])) {
                $ordersMap[$psono] = [
                    'id' => $row['id'],
                    'psono' => $row['psono'],
                    'projectid' => $row['projectid'],
                    'companyid' => $row['companyid'],
                    'subcompanyid' => $row['subcompanyid'],
                    'clientid' => $row['clientid'],
                    'clientname' => $row['clientname'],
                    'mobileno' => $row['mobileno'],
                    'emailid' => $row['emailid'],
                    'delivery_address' => $row['delivery_address'],
                    'districtname' => $row['districtname'],
                    'statename' => $row['statename'],
                    'remarks' => $row['remarks'],
                    'pickupfrom' => $row['pickupfrom'],
                    'created_by' => $row['created_by'],
                    'created_by_name' => $creatorName,
                    'created_at' => $row['created_at'],
                    'created_at_formatted' => date('d-M-Y', strtotime($row['created_at'])),
                    'status' => $row['status'],
                    'orderamount' => (float)$row['orderamount'],
                    'advanceamt' => (float)$row['advanceamt'],
                    'balanceamt' => (float)$row['balanceamt'],
                    'total_qty' => (float)$row['total_qty'],
                    'sowingdate' => $row['sowingdate'] ?: '',
                    'sowingdate_formatted' => ($row['sowingdate'] && $row['sowingdate'] !== '0000-00-00') ? date('d-M-Y', strtotime($row['sowingdate'])) : '',
                    'items' => []
                ];
            }

            if (!empty($row['product_id']) || !empty($row['product_name'])) {
                $cleanProductName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $row['product_name'] ?? '');

                $ordersMap[$psono]['items'][] = [
                    'product_id' => $row['product_id'],
                    'product_sku' => $row['product_sku'],
                    'product_name' => $row['product_name'],
                    'clean_product_name' => $cleanProductName,
                    'quantity' => (float)$row['quantity'],
                    'rate' => (float)$row['rate'],
                    'item_amount' => (float)$row['item_amount'],
                    'expecteddeliverydate' => $row['expecteddeliverydate'],
                    'expecteddeliverydate_formatted' => ($row['expecteddeliverydate'] && $row['expecteddeliverydate'] !== '0000-00-00') ? date('d-M-Y', strtotime($row['expecteddeliverydate'])) : '',
                    'stockorder_no' => $row['stockorder_no']
                ];
            }
        }

        echo json_encode([
            "status" => "success",
            "count" => count($ordersMap),
            "data" => array_values($ordersMap)
        ]);
        exit;

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Error fetching emergency sowing orders: " . $e->getMessage(),
            "data" => []
        ]);
        exit;
    }

} else if ($action === 'approve_emergency_sowing') {
    try {
        $psono       = trim((string)($_POST['psono'] ?? ''));
        $projectid   = (int)($_POST['projectid'] ?? ($_SESSION['projectid'] ?? 0));
        $loginid     = trim((string)($_POST['loginid'] ?? ($_SESSION['loginid'] ?? '')));
        $approver    = trim((string)($_POST['approver_name'] ?? $loginid));

        if (empty($psono)) {
            echo json_encode([
                "status" => "error",
                "message" => "Provisional Order No (PSONO) is required."
            ]);
            exit;
        }

        // Verify that the order currently has status 'Emergency Sowing'
        $whereP = ($projectid > 0) ? " AND projectid = " . (int)$projectid : "";
        $chkStmt = $krfconn->query("SELECT id, psono, status, clientname, clientid, created_by, projectid FROM krf_provisional_sales_order WHERE psono = '" . $krfconn->real_escape_string($psono) . "' {$whereP} LIMIT 1");
        
        if (!$chkStmt) {
            throw new Exception("Database query error: " . $krfconn->error);
        }
        $currOrder = $chkStmt->fetch_assoc();

        if (!$currOrder) {
            echo json_encode([
                "status" => "error",
                "message" => "Provisional Order ($psono) not found."
            ]);
            exit;
        }

        if ($currOrder['status'] !== 'Emergency Sowing') {
            echo json_encode([
                "status" => "error",
                "message" => "Order ($psono) is not in 'Emergency Sowing' status (Current: {$currOrder['status']})."
            ]);
            exit;
        }

        // Update status to 'Draft' and record approval metadata
        $approverVal = $approver ?: $loginid;
        $orderProjectId = (int)$currOrder['projectid'];
        $approvalTimestamp = date('Y-m-d H:i:s');
        
        $updSql = "UPDATE krf_provisional_sales_order 
                   SET status = 'Draft', 
                       emergencysowingapprovalby = '" . $krfconn->real_escape_string($approverVal) . "', 
                       emergencysowingapprovalat = '" . $approvalTimestamp . "', 
                       updated_at = NOW() 
                   WHERE psono = '" . $krfconn->real_escape_string($psono) . "' 
                     AND projectid = {$orderProjectId} 
                     AND status = 'Emergency Sowing'";

        $resUpd = $krfconn->query($updSql);
        if (!$resUpd) {
            throw new Exception("Update query error: " . $krfconn->error);
        }

        if ($krfconn->affected_rows > 0) {
            // -------------------------------------------------------------
            // Send WhatsApp Notification to the Creator of the Order
            // -------------------------------------------------------------
            $creatorId = trim($currOrder['created_by'] ?? '');
            $creatorMobile = '';
            $creatorName = $creatorId;

            // Resolve Creator Mobile and Name
            if ($creatorId !== '') {
                // If created_by is a valid 10+ digit mobile number
                if (preg_match('/^[0-9]{10,12}$/', $creatorId)) {
                    $creatorMobile = $creatorId;
                }

                // Look up in authconn (login_user_master / logintable)
                if (isset($authconn) && $authconn) {
                    //$uRes = $authconn->query("SELECT loginname, fullname, mobileno FROM login_user_master WHERE loginid = '" . $authconn->real_escape_string($creatorId) . "' OR mobileno = '" . $authconn->real_escape_string($creatorId) . "' LIMIT 1");


            $uRes = $authconn->query("SELECT loginid, loginname, loginname as fullname, mobileno FROM login_user_master WHERE loginid = '" . $authconn->real_escape_string($creatorId) . "' OR mobileno = '" . $authconn->real_escape_string($creatorId) . "' LIMIT 1");
                    if ($uRes && $uRow = $uRes->fetch_assoc()) {
                        $creatorName = $uRow['fullname'] ?: $uRow['loginname'] ?: $creatorName;
                        if (empty($creatorMobile) && !empty($uRow['mobileno'])) {
                            $creatorMobile = trim($uRow['mobileno']);
                        }
                    } else {
                       // $uRes2 = $authconn->query("SELECT loginname, fullname, mobileno FROM logintable WHERE loginid = '" . $authconn->real_escape_string($creatorId) . "' OR mobileno = '" . $authconn->real_escape_string($creatorId) . "' LIMIT 1");
                         $uRes2 = $authconn->query("SELECT loginname, loginname as fullname, mobileno FROM login_user_master WHERE loginid = '" . $authconn->real_escape_string($creatorId) . "' OR mobileno = '" . $authconn->real_escape_string($creatorId) . "' LIMIT 1");


                        if ($uRes2 && $uRow2 = $uRes2->fetch_assoc()) {
                            $creatorName = $uRow2['fullname'] ?: $uRow2['loginname'] ?: $creatorName;
                            if (empty($creatorMobile) && !empty($uRow2['mobileno'])) {
                                $creatorMobile = trim($uRow2['mobileno']);
                            }
                        }
                    }
                }
            }

            // Fetch Product summary for message
            $itemsSummary = '';
            $itemsRes = $krfconn->query("SELECT product_name, quantity FROM krf_provisional_sales_order_items WHERE psono = '" . $krfconn->real_escape_string($psono) . "' ORDER BY id ASC");
            if ($itemsRes && $itemsRes->num_rows > 0) {
                $pList = [];
                while ($iRow = $itemsRes->fetch_assoc()) {
                    $pName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $iRow['product_name'] ?? '');
                    $pList[] = $pName . " (Qty: " . (int)$iRow['quantity'] . ")";
                }
                $itemsSummary = implode(", ", $pList);
            }

            $waSent = false;
            if (!empty($creatorMobile) && function_exists('readyto_sentwhatsapp_message')) {
                $smsinfo = function_exists('whatsappuseridpassword') ? whatsappuseridpassword($krfconn, 1) : [];
                if (!empty($smsinfo) && isset($smsinfo[0])) {
                    $sKey = $smsinfo[0];
                    $sourceid = $sKey['sourceid'] ?? '1';
                    $fromWhatsapp = $sKey['whatsappno'] ?? '';
                    $apikey = $sKey['apikey'] ?? '';
                    $accesstoken = $sKey['accesstoken'] ?? '';

                    $formattedTime = date('d-M-Y h:i A', strtotime($approvalTimestamp));
                    $clientDisplay = $currOrder['clientname'] ?: 'Valued Client';

                    $msg_title = "Emergency Sowing Approved - #{$psono}";
                    $waMsg = "🚨 *Emergency Sowing Request Approved!*\n\n"
                           . "Dear *{$creatorName}*,\n\n"
                           . "Your emergency sowing request for Provisional Sales Order *#{$psono}* has been *APPROVED*.\n\n"
                           . "📋 *Order Details:*\n"
                           . "• *Order No:* {$psono}\n"
                           . "• *Client:* {$clientDisplay}\n"
                           . ($itemsSummary ? "• *Product(s):* {$itemsSummary}\n" : "")
                           . "• *Approved By:* {$approverVal}\n"
                           . "• *Approved At:* {$formattedTime}\n"
                           . "• *Status Updated To:* *Draft*\n\n"
                           . "✅ *Your emergency request has been approved. Please proceed to the next action (Payment Advice / Order Processing).* \n\n"
                           . "_Sent via Karjee Farm System_";

                    readyto_sentwhatsapp_message(
                        $krfconn,
                        $sourceid,
                        $fromWhatsapp,
                        $creatorMobile,
                        $accesstoken,
                        $apikey,
                        $msg_title,
                        $waMsg,
                        $clientDisplay,
                        'text',
                        '',
                        ''
                    );
                    $waSent = true;
                }
            }

            echo json_encode([
                "status" => "success",
                "message" => "Emergency Sowing for order {$psono} approved successfully! Order moved to Draft and WhatsApp notification sent to {$creatorName}.",
                "psono" => $psono,
                "approved_by" => $approverVal,
                "approved_at" => $approvalTimestamp,
                "whatsapp_notified" => $waSent,
                "creator_mobile" => $creatorMobile,
                "creator_name" => $creatorName
            ]);
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Failed to approve emergency sowing. Please try again."
            ]);
        }
        exit;

    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => "Approval failed: " . $e->getMessage()
        ]);
        exit;
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid or missing action parameter."
    ]);
    exit;
}
