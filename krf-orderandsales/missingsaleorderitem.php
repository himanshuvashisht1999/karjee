<?php
/**
 * Missing Sales Order Items Audit & Insertion Module
 * 
 * Audits items present in krf_provisional_sales_order_items that are missing
 * in krf_sales_order_items for each psono, with options to review, correct,
 * and insert them into krf_sales_order_items.
 */

// If AJAX action requested, handle and return JSON immediately
if (isset($_REQUEST['action']) && !empty($_REQUEST['action'])) {
    if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
        session_start();
    }
    $_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
    require_once __DIR__ . '/../connection.php';
    require_once __DIR__ . '/functions/orders_functions.php';

    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }

    $action = $_REQUEST['action'];
    $loginname = $_SESSION['loginname'] ?? 'Admin';
    $loginid = $_SESSION['loginid'] ?? 1;

    // Helper: generate a unique sales order number (KF-CS-XXXX)
    function generateUniqueSono($conn) {
        do {
            $sono = 'KF-CS-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $chk = $conn->query("SELECT id FROM krf_sales_order WHERE sono = '$sono'");
        } while ($chk && $chk->num_rows > 0);
        return $sono;
    }

    // Helper: ensure a sales order header exists in krf_sales_order for the given psono
    function ensureSalesOrderExists($conn, $psono, $loginname) {
        $st = $conn->prepare("SELECT sono, id, status, total_amount FROM krf_sales_order WHERE psono = ? LIMIT 1");
        $st->bind_param("s", $psono);
        $st->execute();
        $res = $st->get_result();
        if ($res && $res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $st->close();
            return ['status' => 'exists', 'sono' => $row['sono'], 'so_id' => $row['id']];
        }
        $st->close();

        // Fetch provisional sales order details
        $pStmt = $conn->prepare("SELECT * FROM krf_provisional_sales_order WHERE psono = ? LIMIT 1");
        $pStmt->bind_param("s", $psono);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        if (!$pRes || $pRes->num_rows === 0) {
            $pStmt->close();
            throw new Exception("Provisional sales order '$psono' not found in krf_provisional_sales_order.");
        }
        $pso = $pRes->fetch_assoc();
        $pStmt->close();

        $sono = generateUniqueSono($conn);

        $ins = $conn->prepare("
            INSERT INTO krf_sales_order (
                sono, psono, companyid, subcompanyid, projectid, clientid, clientname,
                emailid, address, delivery_address, districtname, statename, mobileno,
                payment_terms, remarks, total_amount, grand_total, status, created_by, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, 0.00, 0.00, 'Pending', ?, NOW(), NOW()
            )
        ");
        if (!$ins) {
            throw new Exception("Prepare failed for krf_sales_order: " . $conn->error);
        }

        $ins->bind_param(
            "ssiiisssssssssss",
            $sono,
            $psono,
            $pso['companyid'],
            $pso['subcompanyid'],
            $pso['projectid'],
            $pso['clientid'],
            $pso['clientname'],
            $pso['emailid'],
            $pso['address'],
            $pso['delivery_address'],
            $pso['districtname'],
            $pso['statename'],
            $pso['mobileno'],
            $pso['payment_terms'],
            $pso['remarks'],
            $loginname
        );
        $ins->execute();
        $soId = $ins->insert_id;
        $ins->close();

        return ['status' => 'created', 'sono' => $sono, 'so_id' => $soId];
    }

    // Helper: recalculate sales order totals from its items
    function recalculateSalesOrderTotals($conn, $sono, $loginname) {
        $totStmt = $conn->prepare("SELECT IFNULL(SUM(amount), 0) AS total_items FROM krf_sales_order_items WHERE sono = ?");
        $totStmt->bind_param("s", $sono);
        $totStmt->execute();
        $res = $totStmt->get_result()->fetch_assoc();
        $itemsTotal = floatval($res['total_items'] ?? 0);
        $totStmt->close();

        $up = $conn->prepare("UPDATE krf_sales_order SET total_amount = ?, grand_total = ?, updated_at = NOW(), updated_by = ? WHERE sono = ?");
        $up->bind_param("ddss", $itemsTotal, $itemsTotal, $loginname, $sono);
        $up->execute();
        $up->close();
    }

    // Helper: insert a single provisional item into krf_sales_order_items
    function insertProvisionalItemToSalesOrder($conn, $psoi_id, $overrideData = [], $loginname = 'Admin') {
        // Fetch the provisional item
        $q = $conn->prepare("
            SELECT psoi.*, pso.companyid, pso.subcompanyid, pso.clientname, pso.mobileno
            FROM krf_provisional_sales_order_items psoi
            JOIN krf_provisional_sales_order pso ON pso.psono = psoi.psono
            WHERE psoi.id = ?
        ");
        $q->bind_param("i", $psoi_id);
        $q->execute();
        $res = $q->get_result();
        if (!$res || $res->num_rows === 0) {
            $q->close();
            throw new Exception("Provisional item #$psoi_id not found.");
        }
        $item = $res->fetch_assoc();
        $q->close();

        $psono = $item['psono'];

        // Apply any overrides from correction form
        $qty = isset($overrideData['quantity']) && is_numeric($overrideData['quantity']) && $overrideData['quantity'] > 0
            ? intval($overrideData['quantity']) : intval($item['quantity']);
        $rate = isset($overrideData['rate']) && is_numeric($overrideData['rate']) && $overrideData['rate'] >= 0
            ? floatval($overrideData['rate']) : floatval($item['rate']);
        $amount = round($qty * $rate, 2);

        $stockorder_no = isset($overrideData['stockorder_no'])
            ? trim((string)$overrideData['stockorder_no']) : trim((string)$item['stockorder_no']);
        $expDeliveryDate = !empty($overrideData['expecteddeliverydate'])
            ? date('Y-m-d', strtotime($overrideData['expecteddeliverydate'])) : (!empty($item['expecteddeliverydate']) ? date('Y-m-d', strtotime($item['expecteddeliverydate'])) : date('Y-m-d'));
        $remark = isset($overrideData['remark'])
            ? trim((string)$overrideData['remark']) : trim((string)$item['remark']);

        // Ensure Sales Order Header exists
        $soInfo = ensureSalesOrderExists($conn, $psono, $loginname);
        $sono = $soInfo['sono'];

        // Check if item is already present in krf_sales_order_items
        $chk = $conn->prepare("
            SELECT id FROM krf_sales_order_items 
            WHERE psono = ? AND (
                (stockorder_no = ? AND stockorder_no IS NOT NULL AND stockorder_no != '')
                OR (product_id = ? AND quantity = ?)
            ) LIMIT 1
        ");
        $chk->bind_param("sssi", $psono, $stockorder_no, $item['product_id'], $qty);
        $chk->execute();
        $chkRes = $chk->get_result();
        if ($chkRes && $chkRes->num_rows > 0) {
            $chk->close();
            throw new Exception("Item already exists in Sales Order Items for PSONO '$psono'.");
        }
        $chk->close();

        // Calculate shifting qty (1 bonus plant per 126 tray = CEIL(quantity / 125))
        $shiftingqty = ceil($qty / 125);
        $taxableAmount = round($amount, 2);
        $roundoff = floatval($item['roundoffamount'] ?? 0);
        $cgstP = floatval($item['cgst_percent'] ?? 0);
        $cgstA = floatval($item['cgst_amount'] ?? 0);
        $sgstP = floatval($item['sgst_percent'] ?? 0);
        $sgstA = floatval($item['sgst_amount'] ?? 0);
        $igstP = floatval($item['igst_percent'] ?? 0);
        $igstA = floatval($item['igst_amount'] ?? 0);
        $discP = floatval($item['discount_percent'] ?? 0);
        $discA = floatval($item['discount_amount'] ?? 0);
        $freight = floatval($item['freight_charges'] ?? 0);

        // Insert into krf_sales_order_items
        $ins = $conn->prepare("
            INSERT INTO krf_sales_order_items (
                sono, psono, projectid, clientid, product_sku, product_id, product_name,
                quantity, rate, taxableamount, cgst_percent, cgst_amount, sgst_percent, sgst_amount,
                igst_percent, igst_amount, discount_percent, discount_amount, freight_charges,
                tax_amount, amount, roundoffamount, deliverydate, created_by, status,
                stockorder_no, isshifted, shiftingqty, remark
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?,
                0.00, ?, ?, ?, ?, 'Confirmed',
                ?, 'yes', ?, ?
            )
        ");
        if (!$ins) {
            throw new Exception("Prepare failed for krf_sales_order_items: " . $conn->error);
        }

        $ins->bind_param(
            "ssissssidddddddddddddsssis",
            $sono,
            $psono,
            $item['projectid'],
            $item['clientid'],
            $item['product_sku'],
            $item['product_id'],
            $item['product_name'],
            $qty,
            $rate,
            $taxableAmount,
            $cgstP,
            $cgstA,
            $sgstP,
            $sgstA,
            $igstP,
            $igstA,
            $discP,
            $discA,
            $freight,
            $amount,
            $roundoff,
            $expDeliveryDate,
            $loginname,
            $stockorder_no,
            $shiftingqty,
            $remark
        );
        $ins->execute();
        $newSoiId = $ins->insert_id;
        $ins->close();

        // Optionally update the provisional item if user requested corrections to persist
        if (!empty($overrideData['update_provisional'])) {
            $upP = $conn->prepare("
                UPDATE krf_provisional_sales_order_items 
                SET quantity = ?, rate = ?, amount = ?, stockorder_no = ?, expecteddeliverydate = ?, remark = ?, updatedat = CURDATE(), updatedby = ?
                WHERE id = ?
            ");
            if ($upP) {
                $upP->bind_param("iddssssi", $qty, $rate, $amount, $stockorder_no, $expDeliveryDate, $remark, $loginname, $psoi_id);
                $upP->execute();
                $upP->close();
            }

            // Also recalculate provisional sales order total
            $totPStmt = $conn->prepare("SELECT IFNULL(SUM(amount), 0) AS ptotal FROM krf_provisional_sales_order_items WHERE psono = ? AND (tedit IS NULL OR tedit = '')");
            if ($totPStmt) {
                $totPStmt->bind_param("s", $psono);
                $totPStmt->execute();
                $ptot = floatval($totPStmt->get_result()->fetch_assoc()['ptotal'] ?? 0);
                $totPStmt->close();

                $upPso = $conn->prepare("UPDATE krf_provisional_sales_order SET total_amount = ? WHERE psono = ?");
                if ($upPso) {
                    $upPso->bind_param("ds", $ptot, $psono);
                    $upPso->execute();
                    $upPso->close();
                }
            }
        }

        // Recalculate totals in krf_sales_order
        recalculateSalesOrderTotals($conn, $sono, $loginname);

        return [
            'soi_id' => $newSoiId,
            'sono' => $sono,
            'psono' => $psono,
            'so_created' => ($soInfo['status'] === 'created'),
            'product_name' => $item['product_name'],
            'quantity' => $qty,
            'amount' => $amount
        ];
    }

    try {
        if ($action === 'get_missing_items') {
            $search = trim($_REQUEST['search'] ?? '');
            $statusFilter = trim($_REQUEST['status'] ?? 'all');
            $psonoFilter = trim($_REQUEST['psono'] ?? '');

            $whereParts = ["(psoi.tedit IS NULL OR psoi.tedit = '')", "soi.id IS NULL"];

            if ($statusFilter !== 'all' && $statusFilter !== '') {
                $whereParts[] = "pso.status = '" . $krfconn->real_escape_string($statusFilter) . "'";
            }

            if (!empty($psonoFilter)) {
                $whereParts[] = "pso.psono = '" . $krfconn->real_escape_string($psonoFilter) . "'";
            }

            if (!empty($search)) {
                $sEsc = $krfconn->real_escape_string($search);
                $whereParts[] = "(
                    pso.psono LIKE '%$sEsc%' 
                    OR pso.clientname LIKE '%$sEsc%' 
                    OR pso.mobileno LIKE '%$sEsc%' 
                    OR psoi.product_name LIKE '%$sEsc%' 
                    OR psoi.stockorder_no LIKE '%$sEsc%'
                    OR so.sono LIKE '%$sEsc%'
                )";
            }

            $whereSql = implode(' AND ', $whereParts);

            $sql = "
                SELECT 
                    psoi.id AS psoi_id,
                    psoi.psono,
                    psoi.stockorder_no,
                    psoi.product_sku,
                    psoi.product_id,
                    psoi.product_name,
                    psoi.quantity,
                    psoi.rate,
                    psoi.taxableamount,
                    psoi.cgst_percent,
                    psoi.cgst_amount,
                    psoi.sgst_percent,
                    psoi.sgst_amount,
                    psoi.igst_percent,
                    psoi.igst_amount,
                    psoi.discount_percent,
                    psoi.discount_amount,
                    psoi.freight_charges,
                    psoi.amount,
                    psoi.roundoffamount,
                    psoi.expecteddeliverydate,
                    psoi.status AS item_status,
                    psoi.remark,
                    pso.id AS pso_id,
                    pso.status AS pso_status,
                    pso.clientid,
                    pso.clientname,
                    pso.mobileno,
                    pso.emailid,
                    pso.address,
                    pso.delivery_address,
                    pso.districtname,
                    pso.statename,
                    pso.created_at AS pso_created_at,
                    pso.created_by AS pso_created_by,
                    so.id AS so_id,
                    so.sono,
                    so.status AS so_status,
                    so.total_amount AS so_total_amount,
                    so.grand_total AS so_grand_total
                FROM krf_provisional_sales_order_items psoi
                JOIN krf_provisional_sales_order pso ON pso.psono = psoi.psono
                LEFT JOIN krf_sales_order so ON so.psono = psoi.psono
                LEFT JOIN krf_sales_order_items soi ON soi.psono = psoi.psono 
                    AND (
                        (psoi.stockorder_no IS NOT NULL AND psoi.stockorder_no != '' AND soi.stockorder_no = psoi.stockorder_no)
                        OR (
                            (psoi.stockorder_no IS NULL OR psoi.stockorder_no = '' OR soi.stockorder_no IS NULL OR soi.stockorder_no = '')
                            AND (
                                (soi.product_id IS NOT NULL AND soi.product_id != '' AND soi.product_id = psoi.product_id)
                                OR (soi.product_name = psoi.product_name)
                            )
                        )
                    )
                WHERE $whereSql
                ORDER BY psoi.psono DESC, psoi.id ASC
            ";

            $res = $krfconn->query($sql);
            if (!$res) {
                throw new Exception("Query error: " . $krfconn->error);
            }

            $orders = [];
            $totalMissingItems = 0;
            $totalMissingAmount = 0.0;
            $statusCounts = [
                'all' => 0,
                'Confirmed' => 0,
                'Draft' => 0,
                'Emergency Sowing' => 0,
                'Pending Approval' => 0,
                'Cancelled' => 0,
                'no_so' => 0,
                'has_so' => 0
            ];

            while ($r = $res->fetch_assoc()) {
                $p = $r['psono'];
                if (!isset($orders[$p])) {
                    $hasSalesOrder = !empty($r['so_id']);
                    $orders[$p] = [
                        'psono' => $p,
                        'pso_id' => $r['pso_id'],
                        'clientid' => $r['clientid'],
                        'clientname' => $r['clientname'],
                        'mobileno' => $r['mobileno'],
                        'emailid' => $r['emailid'],
                        'districtname' => $r['districtname'],
                        'statename' => $r['statename'],
                        'pso_status' => $r['pso_status'],
                        'pso_created_at' => $r['pso_created_at'] ? date('d-M-Y', strtotime($r['pso_created_at'])) : '',
                        'pso_created_by' => $r['pso_created_by'],
                        'so_id' => $r['so_id'],
                        'sono' => $r['sono'],
                        'so_status' => $r['so_status'],
                        'has_sales_order' => $hasSalesOrder,
                        'total_missing_amount' => 0.0,
                        'items' => []
                    ];
                    $st = $r['pso_status'];
                    if (isset($statusCounts[$st])) {
                        $statusCounts[$st]++;
                    }
                    if ($hasSalesOrder) {
                        $statusCounts['has_so']++;
                    } else {
                        $statusCounts['no_so']++;
                    }
                    $statusCounts['all']++;
                }

                $amt = floatval($r['amount'] ?? 0);
                $orders[$p]['total_missing_amount'] += $amt;
                $orders[$p]['items'][] = [
                    'psoi_id' => intval($r['psoi_id']),
                    'stockorder_no' => $r['stockorder_no'] ?? '',
                    'product_sku' => $r['product_sku'] ?? '',
                    'product_id' => $r['product_id'] ?? '',
                    'product_name' => $r['product_name'] ?? '',
                    'quantity' => intval($r['quantity'] ?? 0),
                    'rate' => floatval($r['rate'] ?? 0),
                    'amount' => $amt,
                    'taxableamount' => floatval($r['taxableamount'] ?? 0),
                    'cgst_amount' => floatval($r['cgst_amount'] ?? 0),
                    'sgst_amount' => floatval($r['sgst_amount'] ?? 0),
                    'igst_amount' => floatval($r['igst_amount'] ?? 0),
                    'freight_charges' => floatval($r['freight_charges'] ?? 0),
                    'expecteddeliverydate' => !empty($r['expecteddeliverydate']) ? date('d-M-Y', strtotime($r['expecteddeliverydate'])) : '',
                    'expecteddeliverydate_raw' => $r['expecteddeliverydate'] ?? '',
                    'remark' => $r['remark'] ?? '',
                    'item_status' => $r['item_status'] ?? ''
                ];

                $totalMissingItems++;
                $totalMissingAmount += $amt;
            }

            echo json_encode([
                'status' => 'success',
                'orders' => array_values($orders),
                'kpis' => [
                    'total_orders' => count($orders),
                    'total_items' => $totalMissingItems,
                    'total_amount' => round($totalMissingAmount, 2),
                    'status_counts' => $statusCounts
                ]
            ]);
            exit;
        }

        if ($action === 'insert_single_item') {
            $psoi_id = intval($_POST['psoi_id'] ?? 0);
            if ($psoi_id <= 0) {
                throw new Exception("Invalid provisional item ID.");
            }

            $override = [
                'quantity' => $_POST['quantity'] ?? null,
                'rate' => $_POST['rate'] ?? null,
                'stockorder_no' => $_POST['stockorder_no'] ?? null,
                'expecteddeliverydate' => $_POST['expecteddeliverydate'] ?? null,
                'remark' => $_POST['remark'] ?? null,
                'update_provisional' => !empty($_POST['update_provisional'])
            ];

            $res = insertProvisionalItemToSalesOrder($krfconn, $psoi_id, $override, $loginname);

            echo json_encode([
                'status' => 'success',
                'message' => "Successfully inserted '{$res['product_name']}' into Sales Order {$res['sono']} for PSONO {$res['psono']}.",
                'data' => $res
            ]);
            exit;
        }

        if ($action === 'insert_order_items') {
            $psono = trim($_POST['psono'] ?? '');
            if (empty($psono)) {
                throw new Exception("PSONO is required.");
            }

            // Fetch all missing items for this psono
            $q = $krfconn->prepare("
                SELECT psoi.id
                FROM krf_provisional_sales_order_items psoi
                LEFT JOIN krf_sales_order_items soi ON soi.psono = psoi.psono 
                    AND (
                        (psoi.stockorder_no IS NOT NULL AND psoi.stockorder_no != '' AND soi.stockorder_no = psoi.stockorder_no)
                        OR (
                            (psoi.stockorder_no IS NULL OR psoi.stockorder_no = '' OR soi.stockorder_no IS NULL OR soi.stockorder_no = '')
                            AND (
                                (soi.product_id IS NOT NULL AND soi.product_id != '' AND soi.product_id = psoi.product_id)
                                OR (soi.product_name = psoi.product_name)
                            )
                        )
                    )
                WHERE psoi.psono = ? 
                  AND (psoi.tedit IS NULL OR psoi.tedit = '')
                  AND soi.id IS NULL
                ORDER BY psoi.id ASC
            ");
            $q->bind_param("s", $psono);
            $q->execute();
            $res = $q->get_result();
            $itemIds = [];
            while ($r = $res->fetch_assoc()) {
                $itemIds[] = intval($r['id']);
            }
            $q->close();

            if (empty($itemIds)) {
                echo json_encode([
                    'status' => 'info',
                    'message' => "All items for PSONO '$psono' are already present in sales order items."
                ]);
                exit;
            }

            $insertedCount = 0;
            $sono = '';
            foreach ($itemIds as $id) {
                try {
                    $resItem = insertProvisionalItemToSalesOrder($krfconn, $id, [], $loginname);
                    $sono = $resItem['sono'];
                    $insertedCount++;
                } catch (Exception $e) {
                    error_log("Error inserting item #$id: " . $e->getMessage());
                }
            }

            echo json_encode([
                'status' => 'success',
                'message' => "Successfully inserted {$insertedCount} item(s) into Sales Order {$sono} for PSONO {$psono}.",
                'psono' => $psono,
                'sono' => $sono,
                'inserted_count' => $insertedCount
            ]);
            exit;
        }

        if ($action === 'create_so_header') {
            $psono = trim((string)($_POST['psono'] ?? ''));
            if (empty($psono)) {
                throw new Exception("PSONO is required.");
            }
            $soInfo = ensureSalesOrderExists($krfconn, $psono, $loginname);
            echo json_encode([
                'status' => 'success',
                'message' => ($soInfo['status'] === 'created')
                    ? "Successfully created Sales Order {$soInfo['sono']} in krf_sales_order for PSONO {$psono}."
                    : "Sales Order {$soInfo['sono']} already exists in krf_sales_order.",
                'data' => $soInfo
            ]);
            exit;
        }

        if ($action === 'bulk_insert_items') {
            $psoi_ids = $_POST['psoi_ids'] ?? [];
            if (!is_array($psoi_ids) || empty($psoi_ids)) {
                throw new Exception("No items selected for bulk insertion.");
            }

            $successCount = 0;
            $createdSoCount = 0;
            $errors = [];
            foreach ($psoi_ids as $id) {
                $id = intval($id);
                if ($id <= 0) continue;
                try {
                    $resItem = insertProvisionalItemToSalesOrder($krfconn, $id, [], $loginname);
                    $successCount++;
                    if (!empty($resItem['so_created'])) {
                        $createdSoCount++;
                    }
                } catch (Exception $e) {
                    $errors[] = "Item #$id: " . $e->getMessage();
                }
            }

            $soMsg = $createdSoCount > 0 ? " and created $createdSoCount new Sales Order header(s) in krf_sales_order" : "";
            echo json_encode([
                'status' => 'success',
                'message' => "Successfully inserted {$successCount} item(s) into Sales Order Items{$soMsg}." . (!empty($errors) ? " (" . count($errors) . " items had warnings)" : ""),
                'success_count' => $successCount,
                'created_so_count' => $createdSoCount,
                'errors' => $errors
            ]);
            exit;
        }

        throw new Exception("Invalid action requested.");
    } catch (Exception $e) {
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// -------------------------------------------------------------
// Standard Page Rendering Mode
// -------------------------------------------------------------
include('../header.php');
include('functions/orders_functions.php');

$mdid = $_GET['mdid'] ?? "";
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = (count($dddr) > 0) ? $dddr[0]['groupid'] : "";
$loginname = $_SESSION['loginname'] ?? 'Admin';
?>

<!-- Load SweetAlert2 Early Loader (CSS & JS) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>

<!-- Load Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
  :root {
    --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    --card-bg: #ffffff;
    --border-color: #e2e8f0;
    --text-primary: #0f172a;
    --text-muted: #64748b;
    --accent-warning: #f59e0b;
    --accent-success: #10b981;
    --accent-info: #0284c7;
    --accent-danger: #ef4444;
  }

  body {
    background-color: #f8fafc;
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    color: var(--text-primary);
  }

  .audit-container {
    padding: 1.5rem;
    max-width: 1650px;
    margin: 0 auto;
  }

  /* Header Card */
  .audit-header-card {
    background: var(--primary-gradient);
    color: #ffffff;
    border-radius: 14px;
    padding: 1.25rem 1.75rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 10px 25px -5px rgba(30, 60, 114, 0.25);
    position: relative;
    overflow: hidden;
  }

  .audit-header-card::after {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 350px;
    height: 350px;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.12) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
  }

  .audit-title {
    font-family: 'Outfit', sans-serif;
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.65rem;
  }

  .audit-subtitle {
    font-size: 0.85rem;
    color: rgba(255, 255, 255, 0.85);
    margin: 0;
  }

  /* KPI Summary Cards */
  .kpi-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 1rem;
    margin-bottom: 1.25rem;
  }

  .kpi-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
  }

  .kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.08);
  }

  .kpi-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.35rem;
    flex-shrink: 0;
  }

  .kpi-val {
    font-family: 'Outfit', sans-serif;
    font-size: 1.4rem;
    font-weight: 800;
    line-height: 1.1;
  }

  .kpi-label {
    font-size: 0.76rem;
    font-weight: 600;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 0.15rem;
  }

  /* Toolbar */
  .toolbar-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 1rem 1.25rem;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
    margin-bottom: 1.25rem;
  }

  .status-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
  }

  .status-pill {
    padding: 0.35rem 0.85rem;
    border-radius: 30px;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    border: 1px solid var(--border-color);
    background: #f8fafc;
    color: var(--text-muted);
    transition: all 0.15s ease;
  }

  .status-pill:hover,
  .status-pill.active {
    background: #1e3c72;
    color: #ffffff;
    border-color: #1e3c72;
    box-shadow: 0 2px 8px rgba(30, 60, 114, 0.25);
  }

  /* Order Card */
  .psono-group-card {
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid var(--border-color);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    margin-bottom: 1.25rem;
    overflow: hidden;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
  }

  .psono-group-card:hover {
    border-color: #94a3b8;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.06);
  }

  .psono-card-header {
    background: #fafbfc;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  .psono-badge {
    font-family: 'Outfit', sans-serif;
    font-size: 1rem;
    font-weight: 700;
    color: #1e3c72;
  }

  .psono-client-info {
    font-size: 0.84rem;
    color: #334155;
  }

  .psono-so-badge {
    font-size: 0.75rem;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    font-weight: 600;
  }

  /* Table styling */
  .table-missing-items {
    margin-bottom: 0;
  }

  .table-missing-items thead th {
    background: #f1f5f9;
    color: #475569;
    font-size: 0.74rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border-color);
    padding: 0.65rem 0.85rem;
    white-space: nowrap;
  }

  .table-missing-items tbody td {
    padding: 0.65rem 0.85rem;
    font-size: 0.82rem;
    vertical-align: middle;
    border-bottom: 1px solid #f1f5f9;
  }

  .table-missing-items tbody tr:hover {
    background-color: #f8fafc;
  }

  /* Checkbox column & centering reset - overrides header.php scale(2) and Bootstrap 4 form-check-input absolute positioning */
  .table-missing-items th.col-chk,
  .table-missing-items td.col-chk,
  .table-missing-items th:first-child,
  .table-missing-items td:first-child {
    width: 48px !important;
    min-width: 48px !important;
    max-width: 48px !important;
    text-align: center !important;
    vertical-align: middle !important;
    padding: 0.6rem 0.2rem !important;
  }

  .table-missing-items .chk-wrap {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    width: 100% !important;
    height: 100% !important;
    margin: 0 auto !important;
    padding: 0 !important;
  }

  .table-missing-items input[type="checkbox"],
  .table-missing-items .form-check-input,
  .table-missing-items .check-all-order,
  .table-missing-items .check-item-select {
    position: static !important;
    -webkit-transform: none !important;
    -moz-transform: none !important;
    -ms-transform: none !important;
    transform: none !important;
    margin: 0 auto !important;
    width: 18px !important;
    height: 18px !important;
    min-width: 18px !important;
    max-width: 18px !important;
    min-height: 18px !important;
    max-height: 18px !important;
    cursor: pointer !important;
    box-shadow: none !important;
    accent-color: #2563eb !important;
    display: block !important;
    vertical-align: middle !important;
  }

  #edit_update_provisional {
    -webkit-transform: none !important;
    -moz-transform: none !important;
    -ms-transform: none !important;
    transform: none !important;
    width: 18px !important;
    height: 18px !important;
    cursor: pointer !important;
    accent-color: #2563eb !important;
    vertical-align: middle !important;
  }

  .stock-badge {
    background: #e0f2fe;
    color: #0369a1;
    font-family: monospace;
    font-weight: 700;
    font-size: 0.74rem;
    padding: 0.2rem 0.5rem;
    border-radius: 4px;
    border: 1px solid #bae6fd;
  }

  .btn-insert-direct {
    background: #10b981;
    color: #ffffff;
    font-weight: 600;
    font-size: 0.74rem;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    border: none;
    transition: background 0.15s ease;
  }

  .btn-insert-direct:hover {
    background: #059669;
    color: #ffffff;
  }

  .btn-correct-insert {
    background: #0284c7;
    color: #ffffff;
    font-weight: 600;
    font-size: 0.74rem;
    padding: 0.3rem 0.65rem;
    border-radius: 6px;
    border: none;
    transition: background 0.15s ease;
  }

  .btn-correct-insert:hover {
    background: #0369a1;
    color: #ffffff;
  }

  .btn-sync-order {
    background: #f59e0b;
    color: #ffffff;
    font-weight: 700;
    font-size: 0.78rem;
    padding: 0.35rem 0.85rem;
    border-radius: 6px;
    border: none;
    transition: background 0.15s ease;
  }

  .btn-sync-order:hover {
    background: #d97706;
    color: #ffffff;
  }

  /* Empty state */
  .empty-state-box {
    background: #ffffff;
    border-radius: 12px;
    padding: 3rem 1.5rem;
    text-align: center;
    border: 2px dashed #cbd5e1;
    color: var(--text-muted);
  }

  /* Loading Overlay */
  .loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(15, 23, 42, 0.45);
    z-index: 99999;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(2px);
  }
</style>

<div class="audit-container">

  <!-- Header Card -->
  <div class="audit-header-card d-flex justify-content-between align-items-center flex-wrap gap-3">
    <div>
      <div class="audit-title">
        <i class="fa fa-cubes text-warning"></i>
        <span>Missing Sales Order Items Audit</span>
      </div>
      <p class="audit-subtitle">
        Review items present in <code>krf_provisional_sales_order_items</code> but missing from <code>krf_sales_order_items</code>. Correct details and insert into Sales Order Items as per provisional order.
      </p>
    </div>
    <div class="d-flex align-items-center gap-2">
      <a href="provisionalsaleorder_review.php?k=<?php echo urlencode($key ?? ''); ?>&mdid=<?php echo urlencode($mdid ?? ''); ?>" class="btn btn-sm btn-outline-light px-3 py-1.5 fw-semibold">
        <i class="fa fa-arrow-left me-1"></i> Provisional Orders
      </a>
      <button type="button" class="btn btn-sm btn-warning text-dark px-3 py-1.5 fw-bold" id="btnRefreshAudit">
        <i class="fa fa-sync-alt me-1"></i> Refresh Audit
      </button>
    </div>
  </div>

  <!-- KPI Cards -->
  <div class="kpi-row" id="kpiSummaryRow">
    <div class="kpi-card">
      <div class="kpi-icon-box" style="background: #e0f2fe; color: #0284c7;">
        <i class="fa fa-file-invoice"></i>
      </div>
      <div>
        <div class="kpi-val text-primary" id="kpi_total_orders">0</div>
        <div class="kpi-label">Affected Orders</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon-box" style="background: #fef3c7; color: #d97706;">
        <i class="fa fa-exclamation-triangle"></i>
      </div>
      <div>
        <div class="kpi-val text-warning" id="kpi_total_items">0</div>
        <div class="kpi-label">Missing Items Count</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon-box" style="background: #dcfce7; color: #15803d;">
        <i class="fa fa-rupee-sign"></i>
      </div>
      <div>
        <div class="kpi-val text-success" id="kpi_total_amount">₹0.00</div>
        <div class="kpi-label">Total Missing Value</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon-box" style="background: #ede9fe; color: #7c3aed;">
        <i class="fa fa-check-circle"></i>
      </div>
      <div>
        <div class="kpi-val" style="color: #7c3aed;" id="kpi_confirmed_orders">0</div>
        <div class="kpi-label">Confirmed Orders Missing</div>
      </div>
    </div>

    <div class="kpi-card">
      <div class="kpi-icon-box" style="background: #fee2e2; color: #dc2626;">
        <i class="fa fa-exclamation-triangle"></i>
      </div>
      <div>
        <div class="kpi-val text-danger" id="kpi_no_so_count">0</div>
        <div class="kpi-label">Missing in krf_sales_order</div>
      </div>
    </div>
  </div>

  <!-- Toolbar: Search, Status Filters & Bulk Insert -->
  <div class="toolbar-card">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
      <!-- Search -->
      <div class="flex-grow-1" style="max-width: 420px;">
        <div class="input-group input-group-sm">
          <span class="input-group-text bg-white border-end-0"><i class="fa fa-search text-muted"></i></span>
          <input type="text" class="form-control border-start-0" id="filterSearch" placeholder="Search by PSONO, Client Name, Mobile, Product, Stock No...">
        </div>
      </div>

      <!-- Status Filter Pills -->
      <div class="status-pills">
        <span class="status-pill active" data-status="all">All <span class="badge bg-secondary ms-1" id="badge_all">0</span></span>
        <span class="status-pill" data-status="no_so" style="border-color: #fca5a5; color: #b91c1c;">
          <i class="fa fa-exclamation-triangle text-danger me-1"></i> Missing in SO Header <span class="badge bg-danger ms-1" id="badge_no_so">0</span>
        </span>
        <span class="status-pill" data-status="has_so" style="border-color: #bae6fd; color: #0369a1;">
          <i class="fa fa-check-circle text-info me-1"></i> SO Header Exists <span class="badge bg-info text-dark ms-1" id="badge_has_so">0</span>
        </span>
        <span class="status-pill" data-status="Confirmed">Confirmed <span class="badge bg-success ms-1" id="badge_Confirmed">0</span></span>
        <span class="status-pill" data-status="Draft">Draft <span class="badge bg-warning text-dark ms-1" id="badge_Draft">0</span></span>
        <span class="status-pill" data-status="Emergency Sowing">Emergency Sowing <span class="badge bg-danger ms-1" id="badge_Emergency">0</span></span>
        <span class="status-pill" data-status="Pending Approval">Pending Approval <span class="badge bg-info ms-1" id="badge_Pending">0</span></span>
      </div>

      <!-- Bulk Action Button -->
      <div>
        <button type="button" class="btn btn-sm btn-success fw-bold d-none" id="btnBulkInsertSelected">
          <i class="fa fa-check-double me-1"></i> Insert Selected Items (<span id="selectedCount">0</span>)
        </button>
      </div>
    </div>
  </div>

  <!-- Orders Grid Content Container -->
  <div id="missingOrdersGrid">
    <div class="text-center p-5 text-muted">
      <i class="fa fa-spinner fa-spin fa-3x mb-3 text-primary"></i>
      <h5>Scanning database for missing sales order items...</h5>
    </div>
  </div>

</div>

<!-- Interactive "Correct & Insert" Modal -->
<div class="modal fade" id="correctInsertModal" tabindex="-1" role="dialog" aria-labelledby="correctInsertModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content shadow-lg border-0 rounded-3">
      <div class="modal-header py-3 px-4 bg-light border-bottom">
        <h5 class="modal-title font-weight-bold text-dark d-flex align-items-center" id="correctInsertModalLabel">
          <i class="fa fa-edit text-primary mr-2 me-2"></i> Review, Correct & Insert into Sales Order Items
        </h5>
        <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="correctInsertForm">
        <input type="hidden" name="action" value="insert_single_item">
        <input type="hidden" id="edit_psoi_id" name="psoi_id">

        <div class="modal-body p-4">
          <div class="alert alert-info py-2 px-3 small mb-3 border-left-primary">
            <i class="fa fa-info-circle me-1"></i> Verify or correct item fields before inserting into <code>krf_sales_order_items</code>. If a Sales Order does not exist for this PSONO, one will be created automatically.
          </div>

          <div class="row g-3">
            <!-- PSONO & Client (Readonly) -->
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-bold">Provisional Order (PSONO)</label>
              <input type="text" class="form-control form-control-sm bg-light" id="edit_psono" readonly>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-bold">Client Name</label>
              <input type="text" class="form-control form-control-sm bg-light" id="edit_clientname" readonly>
            </div>

            <!-- Product Details -->
            <div class="col-12 mb-2">
              <label class="form-label small fw-bold">Product Name</label>
              <input type="text" class="form-control form-control-sm bg-light" id="edit_product_name" readonly>
            </div>

            <!-- Stock Order No & Expected Delivery Date -->
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-bold">Stock Order No <span class="text-danger">*</span></label>
              <input type="text" class="form-control form-control-sm font-monospace" id="edit_stockorder_no" name="stockorder_no" placeholder="e.g. KFP1234 or RSO-XXXX" required>
            </div>
            <div class="col-md-6 mb-2">
              <label class="form-label small fw-bold">Expected Delivery Date <span class="text-danger">*</span></label>
              <input type="date" class="form-control form-control-sm" id="edit_expecteddeliverydate" name="expecteddeliverydate" required>
            </div>

            <!-- Quantity, Rate, Amount Preview -->
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Quantity (Plants) <span class="text-danger">*</span></label>
              <input type="number" class="form-control form-control-sm fw-bold text-primary" id="edit_quantity" name="quantity" min="1" step="1" required>
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Rate (₹) <span class="text-danger">*</span></label>
              <input type="number" class="form-control form-control-sm fw-bold text-dark" id="edit_rate" name="rate" min="0" step="0.01" required>
            </div>
            <div class="col-md-4 mb-2">
              <label class="form-label small fw-bold">Calculated Amount (₹)</label>
              <input type="text" class="form-control form-control-sm fw-bold text-success bg-light" id="edit_amount_preview" readonly>
            </div>

            <!-- Remarks -->
            <div class="col-12 mb-2">
              <label class="form-label small fw-bold">Remarks (Optional)</label>
              <input type="text" class="form-control form-control-sm" id="edit_remark" name="remark" placeholder="Notes or remarks for sales order item...">
            </div>

            <!-- Option: Also update provisional item -->
            <div class="col-12 mt-2">
              <div class="form-check p-2 rounded bg-light border">
                <input class="form-check-input ms-1 me-2" type="checkbox" id="edit_update_provisional" name="update_provisional" value="1" checked>
                <label class="form-check-label small fw-bold text-dark" for="edit_update_provisional">
                  <i class="fa fa-sync-alt text-primary me-1"></i> Also update <code>krf_provisional_sales_order_items</code> with these corrected values
                </label>
                <small class="d-block text-muted ms-4" style="font-size: 0.72rem;">
                  Keeps both Provisional Order and Sales Order perfectly identical.
                </small>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light py-2 px-4 border-top">
          <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-sm btn-success fw-bold px-3" id="btnSubmitCorrectInsert">
            <i class="fa fa-check-circle me-1"></i> Save & Insert to Sales Order Items
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// SweetAlert fallback in case of blocked CDN or slow connection
if (typeof Swal === 'undefined') {
  window.Swal = {
    fire: function (opts, extra, type) {
      if (typeof opts === 'string') {
        alert(opts + (extra ? "\n" + extra : ""));
        return Promise.resolve({ isConfirmed: true });
      }
      const title = opts.title || '';
      const text = opts.text || (opts.html ? opts.html.replace(/<[^>]*>?/gm, '') : '');
      if (opts.showCancelButton) {
        const res = confirm(title + (text ? "\n\n" + text : ""));
        return Promise.resolve({ isConfirmed: res });
      } else {
        alert(title + (text ? "\n\n" + text : ""));
        return Promise.resolve({ isConfirmed: true });
      }
    },
    showLoading: function () {}
  };
}

$(document).ready(function () {
  let cachedOrders = [];
  let currentStatusFilter = 'all';

  // Load audit data on startup
  loadMissingItems();

  $('#btnRefreshAudit').on('click', function () {
    loadMissingItems();
  });

  // Status Filter Pill Click
  $(document).on('click', '.status-pill', function () {
    $('.status-pill').removeClass('active');
    $(this).addClass('active');
    currentStatusFilter = $(this).data('status');
    renderOrders();
  });

  // Search Filter Input
  $('#filterSearch').on('input', function () {
    renderOrders();
  });

  // Fetch Missing Items from Server
  function loadMissingItems() {
    $('#missingOrdersGrid').html(`
      <div class="text-center p-5 text-muted">
        <i class="fa fa-spinner fa-spin fa-3x mb-3 text-primary"></i>
        <h5>Scanning database for missing sales order items...</h5>
      </div>
    `);

    $.ajax({
      url: 'missingsaleorderitem.php',
      type: 'GET',
      data: { action: 'get_missing_items' },
      dataType: 'json',
      success: function (res) {
        if (res.status === 'success') {
          cachedOrders = res.orders || [];
          updateKpis(res.kpis || {});
          renderOrders();
        } else {
          $('#missingOrdersGrid').html(`
            <div class="alert alert-danger m-4">
              <i class="fa fa-exclamation-triangle me-1"></i> ${res.message || 'Error fetching data'}
            </div>
          `);
        }
      },
      error: function (xhr) {
        console.error(xhr);
        $('#missingOrdersGrid').html(`
          <div class="alert alert-danger m-4">
            <i class="fa fa-times-circle me-1"></i> Server communication error. Please try again.
          </div>
        `);
      }
    });
  }

  // Update KPI counters
  function updateKpis(kpis) {
    $('#kpi_total_orders').text(kpis.total_orders || 0);
    $('#kpi_total_items').text(kpis.total_items || 0);
    $('#kpi_total_amount').text('₹' + Number(kpis.total_amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 }));
    $('#kpi_confirmed_orders').text(kpis.status_counts?.Confirmed || 0);
    $('#kpi_no_so_count').text(kpis.status_counts?.no_so || 0);

    // Update badges on pills
    $('#badge_all').text(kpis.total_orders || 0);
    $('#badge_no_so').text(kpis.status_counts?.no_so || 0);
    $('#badge_has_so').text(kpis.status_counts?.has_so || 0);
    $('#badge_Confirmed').text(kpis.status_counts?.Confirmed || 0);
    $('#badge_Draft').text(kpis.status_counts?.Draft || 0);
    $('#badge_Emergency').text(kpis.status_counts?.['Emergency Sowing'] || 0);
    $('#badge_Pending').text(kpis.status_counts?.['Pending Approval'] || 0);
  }

  // Render Order Cards Grid
  function renderOrders() {
    const searchTerm = ($('#filterSearch').val() || '').toLowerCase().trim();

    const filtered = cachedOrders.filter(function (ord) {
      // Status filter
      if (currentStatusFilter === 'no_so' && ord.has_sales_order) {
        return false;
      }
      if (currentStatusFilter === 'has_so' && !ord.has_sales_order) {
        return false;
      }
      if (currentStatusFilter !== 'all' && currentStatusFilter !== 'no_so' && currentStatusFilter !== 'has_so' && ord.pso_status !== currentStatusFilter) {
        return false;
      }
      // Search filter
      if (searchTerm) {
        const matchesPsono = (ord.psono || '').toLowerCase().includes(searchTerm);
        const matchesClient = (ord.clientname || '').toLowerCase().includes(searchTerm);
        const matchesMobile = (ord.mobileno || '').toLowerCase().includes(searchTerm);
        const matchesSono = (ord.sono || '').toLowerCase().includes(searchTerm);
        const matchesItem = (ord.items || []).some(function (it) {
          return (it.product_name || '').toLowerCase().includes(searchTerm) ||
                 (it.stockorder_no || '').toLowerCase().includes(searchTerm);
        });
        if (!matchesPsono && !matchesClient && !matchesMobile && !matchesSono && !matchesItem) {
          return false;
        }
      }
      return true;
    });

    if (filtered.length === 0) {
      $('#missingOrdersGrid').html(`
        <div class="empty-state-box">
          <i class="fa fa-check-circle fa-4x text-success mb-3"></i>
          <h4 class="text-dark font-weight-bold">No Missing Items Found!</h4>
          <p class="mb-0 text-muted">All provisional order items have corresponding sales order items matching your filters.</p>
        </div>
      `);
      $('#btnBulkInsertSelected').addClass('d-none');
      return;
    }

    let html = '';
    filtered.forEach(function (ord, ordIdx) {
      const psoStatusBadge = ord.pso_status === 'Confirmed' 
        ? '<span class="badge bg-success">Confirmed</span>'
        : (ord.pso_status === 'Draft' 
            ? '<span class="badge bg-warning text-dark">Draft</span>'
            : `<span class="badge bg-secondary">${escapeHtml(ord.pso_status)}</span>`);

      const soBadge = ord.has_sales_order 
        ? `<span class="badge bg-info text-dark psono-so-badge"><i class="fa fa-file-invoice me-1"></i>SO: ${escapeHtml(ord.sono)}</span>`
        : `<span class="badge bg-danger psono-so-badge text-white" title="No matching Sales Order header in krf_sales_order"><i class="fa fa-exclamation-triangle me-1"></i>Missing in krf_sales_order</span>
           <button type="button" class="btn btn-xs btn-outline-danger btn-create-so-header py-0 px-2 fw-bold" style="font-size: 0.72rem; border-radius: 4px;" data-psono="${escapeHtml(ord.psono)}" title="Create Sales Order Header in krf_sales_order now">
             <i class="fa fa-plus-circle me-1"></i>Create SO Header
           </button>`;

      html += `
        <div class="psono-group-card" id="card_order_${escapeHtml(ord.psono)}">
          <div class="psono-card-header">
            <div class="d-flex align-items-center flex-wrap gap-2">
              <span class="psono-badge"><i class="fa fa-folder-open text-primary me-1"></i> ${escapeHtml(ord.psono)}</span>
              ${psoStatusBadge}
              ${soBadge}
              <span class="text-muted small ms-2"><i class="fa fa-calendar-alt me-1"></i>${ord.pso_created_at || ''}</span>
            </div>

            <div class="psono-client-info d-flex align-items-center flex-wrap gap-3">
              <span><b>Client:</b> ${escapeHtml(ord.clientname || 'N/A')}</span>
              ${ord.mobileno ? `<span><i class="fa fa-phone text-muted me-1"></i>${escapeHtml(ord.mobileno)}</span>` : ''}
              <span class="badge bg-light text-dark border">
                Missing: <b>${ord.items.length}</b> Items (₹${Number(ord.total_missing_amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })})
              </span>
              <button type="button" class="btn btn-sync-order btn-sync-entire-order" data-psono="${escapeHtml(ord.psono)}" title="Insert all missing items for this order">
                <i class="fa fa-layer-group me-1"></i> Insert All Items (${ord.items.length})
              </button>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-hover table-missing-items">
              <thead>
                <tr>
                  <th class="col-chk text-center">
                    <div class="chk-wrap">
                      <input type="checkbox" class="form-check-input check-all-order" data-psono="${escapeHtml(ord.psono)}" title="Select all items in this order">
                    </div>
                  </th>
                  <th>Stock Order No</th>
                  <th>Product Details</th>
                  <th class="text-end text-right">Quantity</th>
                  <th class="text-end text-right">Rate (₹)</th>
                  <th class="text-end text-right">Total Amount (₹)</th>
                  <th>Delivery Date</th>
                  <th>Remarks</th>
                  <th class="text-center" style="width: 220px;">Actions</th>
                </tr>
              </thead>
              <tbody>
      `;

      ord.items.forEach(function (it) {
        html += `
          <tr id="row_item_${it.psoi_id}" data-psono="${escapeHtml(ord.psono)}" data-id="${it.psoi_id}">
            <td class="col-chk text-center">
              <div class="chk-wrap">
                <input type="checkbox" class="form-check-input check-item-select" value="${it.psoi_id}" data-psono="${escapeHtml(ord.psono)}">
              </div>
            </td>
            <td>
              <span class="stock-badge">${escapeHtml(it.stockorder_no || 'N/A')}</span>
            </td>
            <td>
              <div class="fw-bold text-dark">${escapeHtml(it.product_name)}</div>
              ${it.product_sku ? `<small class="text-muted font-monospace">SKU: ${escapeHtml(it.product_sku)}</small>` : ''}
            </td>
            <td class="text-end text-right fw-bold text-primary">
              ${Number(it.quantity).toLocaleString()}
            </td>
            <td class="text-end text-right">
              ₹${Number(it.rate).toFixed(2)}
            </td>
            <td class="text-end text-right fw-bold text-success">
              ₹${Number(it.amount).toLocaleString('en-IN', { minimumFractionDigits: 2 })}
            </td>
            <td>
              <span class="small text-muted"><i class="fa fa-calendar-alt me-1"></i>${it.expecteddeliverydate || 'N/A'}</span>
            </td>
            <td>
              <span class="small text-muted">${escapeHtml(it.remark || '-')}</span>
            </td>
            <td class="text-center">
              <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-insert-direct btn-direct-insert" 
                        data-id="${it.psoi_id}" 
                        data-psono="${escapeHtml(ord.psono)}" 
                        data-name="${escapeHtml(it.product_name)}"
                        title="Directly insert into Sales Order Items as per provisional order">
                  <i class="fa fa-bolt me-1"></i> Insert
                </button>
                <button type="button" class="btn btn-correct-insert btn-open-correct-modal" 
                        data-id="${it.psoi_id}" 
                        data-psono="${escapeHtml(ord.psono)}" 
                        data-client="${escapeHtml(ord.clientname)}"
                        data-name="${escapeHtml(it.product_name)}"
                        data-stock="${escapeHtml(it.stockorder_no)}"
                        data-qty="${it.quantity}"
                        data-rate="${it.rate}"
                        data-expdate="${it.expecteddeliverydate_raw}"
                        data-remark="${escapeHtml(it.remark)}"
                        title="Review or adjust details before inserting">
                  <i class="fa fa-edit me-1"></i> Correct & Insert
                </button>
              </div>
            </td>
          </tr>
        `;
      });

      html += `
              </tbody>
            </table>
          </div>
        </div>
      `;
    });

    $('#missingOrdersGrid').html(html);
    updateBulkButtonState();
  }

  // Handle "Check All in Order"
  $(document).on('change', '.check-all-order', function () {
    const psono = $(this).data('psono');
    const checked = $(this).is(':checked');
    $(`.check-item-select[data-psono="${psono}"]`).prop('checked', checked);
    updateBulkButtonState();
  });

  // Handle Individual Checkbox Change
  $(document).on('change', '.check-item-select', function () {
    updateBulkButtonState();
  });

  function updateBulkButtonState() {
    const selected = $('.check-item-select:checked').length;
    $('#selectedCount').text(selected);
    if (selected > 0) {
      $('#btnBulkInsertSelected').removeClass('d-none');
    } else {
      $('#btnBulkInsertSelected').addClass('d-none');
    }
  }

  // Direct 1-Click Insert
  $(document).on('click', '.btn-direct-insert', function () {
    const psoiId = $(this).data('id');
    const psono = $(this).data('psono');
    const prodName = $(this).data('name');

    Swal.fire({
      title: "Insert into Sales Order?",
      html: `Do you want to insert <b>${escapeHtml(prodName)}</b> into Sales Order Items for <b>${escapeHtml(psono)}</b> as per provisional order?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#10b981",
      confirmButtonText: "Yes, Insert Now",
      cancelButtonText: "Cancel"
    }).then((res) => {
      if (!res.isConfirmed) return;

      Swal.fire({ title: "Inserting...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      $.ajax({
        url: 'missingsaleorderitem.php',
        type: 'POST',
        data: { action: 'insert_single_item', psoi_id: psoiId },
        dataType: 'json',
        success: function (resp) {
          if (resp.status === 'success') {
            Swal.fire({ title: "Inserted!", text: resp.message, icon: "success", timer: 2000, showConfirmButton: false });
            removeItemFromLocalCache(psoiId, psono);
          } else {
            Swal.fire("Error", resp.message || "Failed to insert item.", "error");
          }
        },
        error: function (xhr) {
          console.error(xhr);
          Swal.fire("Error", "Server request failed.", "error");
        }
      });
    });
  });

  // Open "Correct & Insert" Modal
  $(document).on('click', '.btn-open-correct-modal', function () {
    const $btn = $(this);
    $('#edit_psoi_id').val($btn.data('id'));
    $('#edit_psono').val($btn.data('psono'));
    $('#edit_clientname').val($btn.data('client'));
    $('#edit_product_name').val($btn.data('name'));
    $('#edit_stockorder_no').val($btn.data('stock'));
    $('#edit_quantity').val($btn.data('qty'));
    $('#edit_rate').val(parseFloat($btn.data('rate') || 0).toFixed(2));
    $('#edit_expecteddeliverydate').val($btn.data('expdate'));
    $('#edit_remark').val($btn.data('remark'));

    calcModalAmount();
    $('#correctInsertModal').modal('show');
  });

  // Recalculate modal amount preview
  $('#edit_quantity, #edit_rate').on('input change', calcModalAmount);

  function calcModalAmount() {
    const q = parseFloat($('#edit_quantity').val()) || 0;
    const r = parseFloat($('#edit_rate').val()) || 0;
    const tot = q * r;
    $('#edit_amount_preview').val('₹' + tot.toFixed(2));
  }

  // Submit "Correct & Insert" Modal Form
  $('#correctInsertForm').on('submit', function (e) {
    e.preventDefault();
    const psoiId = $('#edit_psoi_id').val();
    const psono = $('#edit_psono').val();
    const formData = $(this).serialize();

    Swal.fire({ title: "Saving & Inserting...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

    $.ajax({
      url: 'missingsaleorderitem.php',
      type: 'POST',
      data: formData,
      dataType: 'json',
      success: function (resp) {
        if (resp.status === 'success') {
          $('#correctInsertModal').modal('hide');
          Swal.fire({ title: "Success!", text: resp.message, icon: "success", timer: 2200, showConfirmButton: false });
          removeItemFromLocalCache(psoiId, psono);
        } else {
          Swal.fire("Error", resp.message || "Failed to insert corrected item.", "error");
        }
      },
      error: function (xhr) {
        console.error(xhr);
        Swal.fire("Error", "Server request failed.", "error");
      }
    });
  });

  // Insert All Items for a Specific Order
  $(document).on('click', '.btn-sync-entire-order', function () {
    const psono = $(this).data('psono');

    Swal.fire({
      title: "Insert All Items for Order?",
      html: `Do you want to insert <b>all missing items</b> for order <b>${escapeHtml(psono)}</b> into Sales Order Items?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#f59e0b",
      confirmButtonText: "Yes, Insert All",
      cancelButtonText: "Cancel"
    }).then((res) => {
      if (!res.isConfirmed) return;

      Swal.fire({ title: "Processing Order...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      $.ajax({
        url: 'missingsaleorderitem.php',
        type: 'POST',
        data: { action: 'insert_order_items', psono: psono },
        dataType: 'json',
        success: function (resp) {
          if (resp.status === 'success') {
            Swal.fire({ title: "Completed!", text: resp.message, icon: "success", timer: 2500, showConfirmButton: false });
            // Remove entire order from local cache
            removeOrderFromLocalCache(psono);
          } else {
            Swal.fire("Error", resp.message || "Failed to insert items for order.", "error");
          }
        },
        error: function (xhr) {
          console.error(xhr);
          Swal.fire("Error", "Server request failed.", "error");
        }
      });
    });
  });

  // Create Sales Order Header Directly
  $(document).on('click', '.btn-create-so-header', function () {
    const psono = $(this).data('psono');
    if (!psono) return;

    Swal.fire({
      title: "Create Sales Order Header?",
      text: `Create Sales Order record in krf_sales_order for Provisional Order ${psono}?`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#2563eb",
      confirmButtonText: "Yes, Create SO Header",
      cancelButtonText: "Cancel"
    }).then((res) => {
      if (!res.isConfirmed) return;

      Swal.fire({ title: "Creating Sales Order...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      $.ajax({
        url: 'missingsaleorderitem.php',
        type: 'POST',
        data: { action: 'create_so_header', psono: psono },
        dataType: 'json',
        success: function (resp) {
          if (resp.status === 'success') {
            Swal.fire("Created!", resp.message, "success");
            // Update in-memory order
            const targetOrd = cachedOrders.find(o => o.psono === psono);
            if (targetOrd && resp.data) {
              targetOrd.has_sales_order = true;
              targetOrd.sono = resp.data.sono;
              targetOrd.so_id = resp.data.so_id;
              renderOrders();
              recalcLocalKpis();
            }
          } else {
            Swal.fire("Error", resp.message || "Failed to create Sales Order.", "error");
          }
        },
        error: function (xhr) {
          console.error(xhr);
          Swal.fire("Error", "Server request failed.", "error");
        }
      });
    });
  });

  // Bulk Insert Selected Items
  $('#btnBulkInsertSelected').on('click', function () {
    const ids = [];
    $('.check-item-select:checked').each(function () {
      ids.push($(this).val());
    });

    if (ids.length === 0) return;

    Swal.fire({
      title: "Bulk Insert Selected Items?",
      html: `Are you sure you want to insert all <b>${ids.length} selected items</b> into their corresponding Sales Order Items?<br><small class='text-muted'>If any order header is missing in <code>krf_sales_order</code>, it will be automatically created.</small>`,
      icon: "question",
      showCancelButton: true,
      confirmButtonColor: "#10b981",
      confirmButtonText: `Yes, Insert ${ids.length} Items`,
      cancelButtonText: "Cancel"
    }).then((res) => {
      if (!res.isConfirmed) return;

      Swal.fire({ title: "Bulk Inserting...", allowOutsideClick: false, didOpen: () => Swal.showLoading() });

      $.ajax({
        url: 'missingsaleorderitem.php',
        type: 'POST',
        data: { action: 'bulk_insert_items', psoi_ids: ids },
        dataType: 'json',
        success: function (resp) {
          if (resp.status === 'success') {
            Swal.fire("Done!", resp.message, "success");
            loadMissingItems(); // Full refresh after bulk
          } else {
            Swal.fire("Error", resp.message || "Failed bulk insertion.", "error");
          }
        },
        error: function (xhr) {
          console.error(xhr);
          Swal.fire("Error", "Server request failed.", "error");
        }
      });
    });
  });

  // Helper to remove an item from local memory and UI with smooth animation
  function removeItemFromLocalCache(psoiId, psono) {
    psoiId = parseInt(psoiId);
    $(`#row_item_${psoiId}`).fadeOut(300, function () {
      $(this).remove();
    });

    // Update in-memory data
    const ordIndex = cachedOrders.findIndex(o => o.psono === psono);
    if (ordIndex > -1) {
      cachedOrders[ordIndex].items = cachedOrders[ordIndex].items.filter(it => it.psoi_id !== psoiId);
      if (cachedOrders[ordIndex].items.length === 0) {
        $(`#card_order_${psono}`).fadeOut(400, function () {
          $(this).remove();
        });
        cachedOrders.splice(ordIndex, 1);
      }
    }

    // Refresh KPI totals
    recalcLocalKpis();
    updateBulkButtonState();
  }

  function removeOrderFromLocalCache(psono) {
    $(`#card_order_${psono}`).fadeOut(400, function () {
      $(this).remove();
    });
    cachedOrders = cachedOrders.filter(o => o.psono !== psono);
    recalcLocalKpis();
    updateBulkButtonState();
  }

  function recalcLocalKpis() {
    let totItems = 0;
    let totAmt = 0;
    let counts = { all: cachedOrders.length, Confirmed: 0, Draft: 0, 'Emergency Sowing': 0, 'Pending Approval': 0, Cancelled: 0, no_so: 0, has_so: 0 };

    cachedOrders.forEach(o => {
      totItems += o.items.length;
      o.items.forEach(it => totAmt += it.amount);
      if (counts[o.pso_status] !== undefined) counts[o.pso_status]++;
      if (o.has_sales_order) counts.has_so++;
      else counts.no_so++;
    });

    updateKpis({
      total_orders: cachedOrders.length,
      total_items: totItems,
      total_amount: totAmt,
      status_counts: counts
    });

    if (cachedOrders.length === 0) {
      renderOrders();
    }
  }

  function escapeHtml(text) {
    if (text == null) return '';
    return String(text)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }
});
</script>

<?php include('../footer.php'); ?>
