<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/client_functions.php");
include("../functions/product_functions.php");
$action=$_GET['action'];
if($action=="updateprovisionalsalesorder"){
   $companyid      = $_POST['companyid'] ?? '';
    $subcompanyid   = $_POST['subcompanyid'] ?? '';
    $projectid      = $_POST['projectid'] ?? '';
    $loginid        = $_POST['loginid'] ?? '';
    $psono          = $_POST['psono'] ?? '';

    $psoinfo   = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono);
    $clientid  = $psoinfo[0]['clientid'];
    $projectid = $psoinfo[0]['projectid'];

    $freight_amount        = $_POST['freight_amount'] ?? 0;
    $discount_percent      = $_POST['discount_percent'] ?? 0;
    $payablefreight_amount = $_POST['payablefreight_amount'] ?? 0;

    $productlist     = $_POST['productname'] ?? [];
    $quantities      = $_POST['quantity'] ?? [];
    $rates           = $_POST['rate'] ?? [];
    $taxableamount   = $_POST['taxableamount'] ?? [];
    $cgstpercent     = $_POST['cgstprt'] ?? [];
    $cgstamount      = $_POST['cgstamt'] ?? [];
    $sgstpercent     = $_POST['sgstprt'] ?? [];
    $sgstamount      = $_POST['sgstamt'] ?? [];
    $igstpercent     = $_POST['igstprt'] ?? [];
    $igstamount      = $_POST['igstamt'] ?? [];
    $discountpercent = $_POST['distprt'] ?? [];
    $discountamount  = $_POST['distamt'] ?? [];
    $amounts         = $_POST['amount'] ?? [];
    $roundoffamounts = $_POST['roundoffamount'] ?? [];
    $expecteddelivery = $_POST['expecteddelivery'] ?? [];

    $status   = $_POST['status'] ?? 'Draft';

    $response = ['status' => 'error', 'message' => 'Unknown error'];
    $krfconn->begin_transaction();

    try {
        // Calculate total order quantity
        $total_order_qty = 0;
        for ($k = 0; $k < count($productlist); $k++) {
            $total_order_qty += (float)($quantities[$k] ?? 0);
        }

        $f_amt_val = (float)($freight_amount ?? 0);
        $f_disc_val = (float)($discount_percent ?? 0);
        $f_payable_val = (float)($payablefreight_amount ?? ($f_amt_val - ($f_amt_val * ($f_disc_val / 100))));

        // Delete existing items
        $stmt = $krfconn->prepare("DELETE FROM krf_provisional_sales_order_items WHERE psono=?");
        if (!$stmt) throw new Exception("Prepare failed: " . $krfconn->error);
        $stmt->bind_param("s", $psono);
        $stmt->execute();
        $stmt->close();

        // Insert updated items with freight_charges and roundoffamount
        $itemSql = "INSERT INTO krf_provisional_sales_order_items 
          (projectid, clientid, psono, product_sku, product_id, product_name, quantity, rate, freight_charges, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount, igst_percent, igst_amount, discount_percent, discount_amount, amount, roundoffamount, expecteddeliverydate, created_by)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $itemStmt = $krfconn->prepare($itemSql);

        $types = "isssssddddddddddddddss";
        for ($i = 0; $i < count($productlist); $i++) {
            $productsku  ="";
            $productid   ="";
            $productName ="";
            $prdin = getProductData($krfconn, $projectid, "ProductID", trim($productlist[$i]));
            if (empty($prdin)) {
                throw new Exception("Product not found for ID: " . $productlist[$i]);
            }
            $productsku  = $prdin[0]['sku'];   // 👈 here
            $productid   = trim($productlist[$i]);
            $productName = $prdin[0]['ProductName'];
            $qty              = (float) $quantities[$i];
            $rate             = (float) $rates[$i];
            $taxableAmount    = (float) $taxableamount[$i];
            $cgstPercent      = (float) $cgstpercent[$i];
            $cgstAmount       = (float) $cgstamount[$i];
            $sgstPercent      = (float) $sgstpercent[$i];
            $sgstAmount       = (float) $sgstamount[$i];
            $igstPercent      = (float) $igstpercent[$i];
            $igstAmount       = (float) $igstamount[$i];
            $discountPercent  = (float) $discountpercent[$i];
            $discountAmount   = (float) $discountamount[$i];
            $amt              = (float) $amounts[$i];
            if (isset($roundoffamounts[$i]) && is_numeric($roundoffamounts[$i])) {
                $roundoffamount = (float) $roundoffamounts[$i];
            } else {
                $unrounded = ($rate * $qty) - $discountAmount;
                $roundoffamount = round($amt - $unrounded, 2);
            }
            $expdate          = $expecteddelivery[$i];

            if (!$productName || !$expdate || $qty <= 0) {
                throw new Exception("Invalid order item data at row " . ($i + 1));
            }

            $item_freight_charge = 0.00;
            if ($qty > 0 && $total_order_qty > 0 && $f_payable_val > 0) {
                $item_freight_charge = round(($qty / $total_order_qty) * $f_payable_val, 2);
            }

            $itemStmt->bind_param(
                $types,
                $projectid,
                $clientid,
                $psono,
                $productsku,
                $productid,
                $productName,
                $qty,
                $rate,
                $item_freight_charge,
                $taxableAmount,
                $cgstPercent,
                $cgstAmount,
                $sgstPercent,
                $sgstAmount,
                $igstPercent,
                $igstAmount,
                $discountPercent,
                $discountAmount,
                $amt,
                $roundoffamount,
                $expdate,
                $loginid
            );
            if (!$itemStmt->execute()) {
                throw new Exception("Failed to insert order item: " . $itemStmt->error);
            }
        }
        $itemStmt->close();

        // Save freight
        $freightremarks = "";
        $sono = ""; // as per your earlier flow
        $zerocharge_val = $_POST['zerocharge'] ?? ($_POST['f_zero_charges'] ?? 'no');
        if (is_array($zerocharge_val)) {
            $zerocharge_val = (in_array('yes', $zerocharge_val) || in_array('1', $zerocharge_val) || in_array(1, $zerocharge_val)) ? 'yes' : 'no';
        } else {
            $zerocharge_val = ($zerocharge_val === 'yes' || $zerocharge_val === '1' || $zerocharge_val === 1) ? 'yes' : 'no';
        }
        $respFreight = saveFreightCharges($krfconn, $projectid, $psono, $sono, $clientid, $freight_amount, $discount_percent, $freightremarks, $loginid, $zerocharge_val);
        // 🚨 Emergency status follows the confirmed checkbox, never an automatic date heuristic.
        $is_emergency_flag = (
            (isset($_POST['is_emergency_sowing']) && in_array(strtolower((string)$_POST['is_emergency_sowing']), ['yes', '1', 'true'], true)) ||
            (isset($_POST['emergency_sowing']) && in_array(strtolower((string)$_POST['emergency_sowing']), ['yes', '1', 'true'], true))
        );
        if ($is_emergency_flag) {
            // User explicitly requested emergency sowing
            $updStatusSql = "UPDATE krf_provisional_sales_order SET status = 'Emergency Sowing', updated_at = NOW() WHERE psono = ? AND projectid = ?";
            $updStmt = $krfconn->prepare($updStatusSql);
            if ($updStmt) {
                $updStmt->bind_param("si", $psono, $projectid);
                $updStmt->execute();
                $updStmt->close();
            }
        }

        // If all good, commit
        $krfconn->commit();
        $response['status'] = 'success';
        $response['message'] = 'Order updated successfully';

    } catch (Exception $e) {
        $krfconn->rollback(); // rollback everything
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }

    echo json_encode($response);
    exit;
}else if($action=="saveactualsalesinvoice"){
    $companyid          = $_POST['companyid'] ?? 0;
    $subcompanyid       = $_POST['subcompanyid'] ?? 0;
    $projectid          = $_POST['projectid'] ?? 0;
    $psono = $_POST['psono'] ?? '';
    $loginid            = $_POST['loginid'] ?? '';
    $invoiceno = $_POST['invoiceno'] ?? '';
    
    // provisional order details
    $pvorddata = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono);
    $delivery_address = $pvorddata[0]['address'] ?? '';
    $districtname = $pvorddata[0]['districtname'] ?? '';
    $statename = $pvorddata[0]['statename'] ?? '';
    $expecteddelivery = $pvorddata[0]['expecteddelivery'] ?? '';
    $clientid = $pvorddata[0]['clientid'] ?? '';

    // client data
    $clientdata = getClientDetailsInfo($krfconn, $companyid, $subcompanyid, $projectid, "clientid", $clientid);
    $mobileno = "";
    $emailid ="";
    $clientname ="";
    if(count($clientdata)>0){
        foreach ($clientdata as $clikey) {
            $mobileno = $clikey['phones'] ?? '';
            $emailid = $clikey['client_email'] ?? '';
            $clientname = $clikey['client_name'] ?? '';
        }
    }
    $itemIds            = $_POST['productid'] ?? [];
    $quantities         = $_POST['qty'] ?? [];
    $rates              = $_POST['rate'] ?? [];
    $amounts            = $_POST['amount'] ?? [];
    $response = ['status' => 'error', 'message' => 'Incomplete details'];

    // Step 1: Validate required fields
    if (empty($projectid) || empty($clientid) || empty($psono)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing project, client, or provisional order']);
        exit;
    }

    // Validate items
    if (empty($itemIds) || empty($quantities) || empty($rates) || empty($amounts)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing item details']);
        exit;
    }

    // Ensure all arrays have the same length
    if (!(count($itemIds) === count($quantities) && count($quantities) === count($rates) && count($rates) === count($amounts))) {
        echo json_encode(['status' => 'error', 'message' => 'Item data mismatch']);
        exit;
    }

    // Validate numeric values
    foreach ($itemIds as $i => $id) {
        if (empty($id) || $quantities[$i] <= 0 || $rates[$i] <= 0 || $amounts[$i] <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid item details']);
            exit;
        }
    }


    $invoicedate = date('Y-m-d');
    $remarks="";
    $krfconn->begin_transaction();
    try {
        $actinv = saveupdateSalesInvoice($krfconn, $invoiceno,$psono,$invoicedate,$projectid,$clientid,$mobileno,$emailid,$delivery_address,$districtname,$statename,$expecteddelivery,$remarks,$loginid);
        if($actinv['status']=="success"){
            $invoiceno = $actinv['invoiceno'];
            $status = 'active';
            // Update sales_invoice
            $stmt = $krfconn->prepare("
                UPDATE sales_invoice 
                SET projectid=?, clientid=?, mobileno=?, emailid=?, status=? 
                WHERE psono=? AND invoiceno=?
            ");
            if (!$stmt) throw new Exception("Prepare failed: " . $krfconn->error);

            $stmt->bind_param("issssss", $projectid, $clientid, $mobileno, $emailid, $status, $psono, $invoiceno);
            $stmt->execute();
            $stmt->close();

            // Delete existing items
            $stmt = $krfconn->prepare("
                DELETE FROM sales_invoice_items 
                WHERE psono=? AND invoiceno=?
            ");
            if (!$stmt) throw new Exception("Prepare failed: " . $krfconn->error);
            $stmt->bind_param("ss", $psono, $invoiceno);
            $stmt->execute();
            $stmt->close();

            // Insert updated items
            $stmt = $krfconn->prepare("
                INSERT INTO sales_invoice_items 
                (projectid, clientid, psono, invoiceno, productid, productname, quantity, rate, amount, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if (!$stmt) throw new Exception("Prepare failed: " . $krfconn->error);

            $itemStatus = 'active';
            $invamount=0;
            for ($i = 0; $i < count($itemIds); $i++) {
                $pid   = $itemIds[$i] ?? 0;
                $ppd = getProductData($krfconn, $projectid, "ProductID", $pid);
                $pname          = $ppd[0]['ProductName'] ?? '';
                $qty   = $quantities[$i] ?? 0;
                $rate  = $rates[$i] ?? 0;
                $amt   = $amounts[$i] ?? 0;

                // Validate individual item fields
                if (empty($pname) || $qty <= 0 || $rate <= 0 || $amt <= 0) {
                    throw new Exception("Incomplete item details at row " . ($i + 1));
                }
                $invamount +=$amt;
                $stmt->bind_param(
                    "isssssidds",
                    $projectid, $clientid, $psono, $invoiceno,
                    $pid, $pname, $qty, $rate, $amt, $itemStatus
                );
                $stmt->execute();
            }
            $stmt->close();
            if($invamount>0){
                $krfconn->query("UPDATE SET invoiceamount='".$invamount."' where projectid='".$projectid."' and clientid='".$clientid."' and invoiceno='".$invoiceno."'");
            }

            $krfconn->commit();
            $response['status'] = 'success';
            $response['message'] = 'Order updated successfully';    
        }else{ //success
            $response['status'] = 'error';
            $response['message'] = $actinv['message'];
        }
    } catch (Exception $e) {
        $krfconn->rollback();
        $response['status'] = 'error';
        $response['message'] = $e->getMessage();
    }
    echo json_encode($response);
}


function saveupdateSalesInvoice($conn,$invoiceno,$psono,$invoicedate,$projectid,$clientid,$mobileno,$emailid,$delivery_address,$districtname,$statename,$expecteddelivery,$remarks,$created_by) {
    // echo $invoiceno." --".$psono." --".$invoicedate." --".$projectid." --".$clientid;
    // Required fields check
    if (empty($invoicedate) || empty($projectid) || empty($clientid)) {
        return [
            "status" => "error",
            "message" => "Missing required details: invoicedate, projectid, clientid"
        ];
    }

    // ✅ If updating
    if (!empty($invoiceno)) {
        // Check invoice exists
        $checkInv = $conn->prepare("SELECT id FROM sales_invoice WHERE invoiceno=? AND projectid=? LIMIT 1");
        $checkInv->bind_param("si", $invoiceno, $projectid);
        $checkInv->execute();
        $checkInv->store_result();

        if ($checkInv->num_rows === 0) {
            return [
                "status" => "error",
                "message" => "Invoice not found for update"
            ];
        }

        // Update record
        $stmt = $conn->prepare("
            UPDATE sales_invoice
            SET psono=?, invoicedate=?, clientid=?, mobileno=?, emailid=?, 
                delivery_address=?, districtname=?, statename=?, expecteddelivery=?, 
                remarks=?, updated_at=NOW()
            WHERE invoiceno=? AND projectid=?
        ");

        $stmt->bind_param(
            "sssssssssssi",
            $psono,
            $invoicedate,
            $clientid,
            $mobileno,
            $emailid,
            $delivery_address,
            $districtname,
            $statename,
            $expecteddelivery,
            $remarks,
            $invoiceno,
            $projectid
        );

        if ($stmt->execute()) {
            return [
                "status" => "success",
                "message" => "Invoice updated successfully",
                "invoiceno" => $invoiceno
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Update failed: " . $stmt->error
            ];
        }
    }

    // ✅ If inserting new
    if (!empty($psono)) {
        $check = $conn->prepare("
            SELECT id 
            FROM sales_invoice 
            WHERE psono=? AND status='active' 
            LIMIT 1
        ");
        $check->bind_param("s", $psono);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            return [
                "status" => "error",
                "message" => "This Provisional order already creata a invoice"
            ];
        }
    }

    // Generate unique invoice no
    $newInvoiceNo = generateUniqueInvoiceNo($conn, $projectid);

    $stmt = $conn->prepare("
        INSERT INTO sales_invoice 
        (psono, invoiceno, invoicedate, projectid, clientid, mobileno, emailid, 
         delivery_address, districtname, statename, expecteddelivery, remarks, created_by) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssissssssssi",
        $psono,
        $newInvoiceNo,
        $invoicedate,
        $projectid,
        $clientid,
        $mobileno,
        $emailid,
        $delivery_address,
        $districtname,
        $statename,
        $expecteddelivery,
        $remarks,
        $created_by
    );

    if ($stmt->execute()) {
        return [
            "status" => "success",
            "message" => "Invoice created successfully",
            "invoiceno" => $newInvoiceNo,
            "insert_id" => $stmt->insert_id
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Insert failed: " . $stmt->error
        ];
    }
}
?>