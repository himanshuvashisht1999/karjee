<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');

require_once dirname(__DIR__) . "/connection.php";
require_once __DIR__ . "/po_functions.php";

header('Content-Type: application/json');

// Normalize DB connections across all hosting environments
if (!isset($krfconn) || !($krfconn instanceof mysqli)) {
    $krfconn = (isset($conn) && ($conn instanceof mysqli)) ? $conn : null;
}
if (!isset($conn) || !($conn instanceof mysqli)) {
    $conn = (isset($krfconn) && ($krfconn instanceof mysqli)) ? $krfconn : null;
}

$action = $_POST['action'] ?? '';

if ($action == 'list_pos') {
    $filters = [
        'status' => $_POST['status'] ?? '',
        'search' => $_POST['search'] ?? ''
    ];
    $pos = getPurchaseOrders($krfconn, $filters);

    // Compute Overall PO Stats across the entire system
    $totalCount = 0;
    $draftCount = 0;
    $receivedCount = 0;
    $cancelledCount = 0;
    $totalVal = 0;

    $statQ = $krfconn->query("SELECT 
        COUNT(*) as total_orders,
        SUM(CASE WHEN LOWER(status) = 'draft' OR status IS NULL OR status = '' THEN 1 ELSE 0 END) as draft_orders,
        SUM(CASE WHEN LOWER(status) = 'received' THEN 1 ELSE 0 END) as received_orders,
        SUM(CASE WHEN LOWER(status) = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
        SUM(CASE WHEN LOWER(status) != 'cancelled' THEN COALESCE(NULLIF(total_amount, 0), (SELECT SUM(total_price) FROM inventory_purchase_order_items WHERE po_id = inventory_purchase_orders.po_id), 0) ELSE 0 END) as total_val
        FROM inventory_purchase_orders");
    if ($statQ && $sRow = $statQ->fetch_assoc()) {
        $totalCount = intval($sRow['total_orders'] ?? 0);
        $draftCount = intval($sRow['draft_orders'] ?? 0);
        $receivedCount = intval($sRow['received_orders'] ?? 0);
        $cancelledCount = intval($sRow['cancelled_orders'] ?? 0);
        $totalVal = floatval($sRow['total_val'] ?? 0);
    }

    $isReceivedOnly = (strtolower($filters['status']) === 'received');
    $displayedTotal = 0;
    $rows = "";

    if ($isReceivedOnly) {
        $headers = "<tr>
            <th>PO Number</th>
            <th>Vendor / Supplier</th>
            <th>PO Date</th>
            <th>Invoice #</th>
            <th>Invoice Date</th>
            <th>Received / Updated</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>";
    } else {
        $headers = "<tr>
            <th>PO Number</th>
            <th>Vendor / Supplier</th>
            <th>Date</th>
            <th>Amount</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>";
    }

    if (count($pos) > 0) {
        foreach ($pos as $po) {
            $amt = (float)(!empty($po['total_amount']) && (float)$po['total_amount'] > 0 ? $po['total_amount'] : ($po['calculated_total_amount'] ?? 0));
            $displayedTotal += $amt;
            $stLower = strtolower($po['status'] ?? 'draft');
            $isReceived = ($stLower === 'received');
            $isCancelled = ($stLower === 'cancelled');
            $isDraft = ($stLower === 'draft');

            if ($isReceived) {
                $statusBadge = "<span class='badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fw-bold'><i class='fas fa-check-circle me-1'></i>Received</span>";
            } else if ($isCancelled) {
                $statusBadge = "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-1 rounded-pill fw-bold'><i class='fas fa-ban me-1'></i>Cancelled</span>";
            } else {
                $statusBadge = "<span class='badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-1 rounded-pill fw-bold'><i class='fas fa-clock me-1'></i>Draft</span>";
            }
            
            $poNumHtml = "<span class='fw-bold text-dark font-monospace'><i class='fas fa-file-invoice text-primary me-1 opacity-75'></i>{$po['po_number']}</span>";
            $vendorHtml = !empty($po['VendorName']) ? "<span class='fw-semibold text-secondary'>{$po['VendorName']}</span>" : "<span class='text-muted small'>N/A</span>";
            $dateHtml = "<span class='text-muted super-small'><i class='far fa-calendar-alt me-1 text-info'></i>{$po['po_date']}</span>";
            $amountHtml = "<span class='fw-bold text-primary fs-6'>₹" . number_format($amt, 2) . "</span>";

            // If status is Draft, show Remove PO button. If status is Received or Cancelled, do not show remove PO.
            $deleteBtn = ($isDraft) 
                ? "<button class='btn btn-sm btn-outline-danger rounded-2 fw-semibold' onclick='deletePO({$po['po_id']}, \"{$po['po_number']}\")' title='Remove / Cancel Purchase Order'><i class='fas fa-trash-alt me-1'></i>Remove</button>" 
                : "";

            $receiveBtn = (!$isCancelled)
                ? "<button class='btn btn-sm btn-outline-success me-1 rounded-2 fw-semibold' onclick='receivePO({$po['po_id']})' title='Receive Goods'><i class='fas fa-box-open me-1'></i>Receive</button>"
                : "";

            // Show PDF button only for Draft orders
            $pdfBtn = ($isDraft) 
                ? "<a href='generate_po_pdf.php?po_id={$po['po_id']}' target='_blank' class='btn btn-sm btn-outline-danger me-1 rounded-2 fw-semibold' title='Download / Print PDF'><i class='fas fa-file-pdf me-1'></i>PDF</a>" 
                : "";

            if ($isReceivedOnly) {
                $invNo = !empty($po['invoice_no']) 
                    ? "<span class='badge bg-light text-dark border font-monospace fw-bold'><i class='fas fa-receipt me-1 text-primary'></i>" . htmlspecialchars($po['invoice_no']) . "</span>" 
                    : "<span class='text-muted small'>-</span>";

                $invDate = (!empty($po['invoice_date']) && $po['invoice_date'] != '0000-00-00') 
                    ? "<span class='text-muted super-small'><i class='far fa-calendar-alt me-1 text-primary'></i>{$po['invoice_date']}</span>" 
                    : "<span class='text-muted small'>-</span>";

                $recUser = !empty($po['received_by']) 
                    ? htmlspecialchars($po['received_by']) 
                    : (!empty($po['created_by']) ? htmlspecialchars($po['created_by']) : 'Admin');

                $recDate = (!empty($po['received_date']) && $po['received_date'] != '0000-00-00') 
                    ? $po['received_date'] 
                    : (!empty($po['receipt_created_at']) ? date('Y-m-d', strtotime($po['receipt_created_at'])) : '-');

                $recByHtml = "<div><span class='badge bg-info-subtle text-info border super-small'><i class='fas fa-user-check me-1'></i>{$recUser}</span></div><div class='super-small text-muted mt-1'><i class='fas fa-clock me-1 text-secondary'></i>{$recDate}</div>";

                $previewBtn = !empty($po['receipt_id']) 
                    ? "<button class='btn btn-sm btn-outline-info me-1 rounded-2 fw-semibold' onclick='previewInvoice({$po['receipt_id']})' title='Preview Invoice Document'><i class='fas fa-file-invoice me-1'></i>Invoice</button>" 
                    : "";

                $rows .= "<tr>
                    <td>{$poNumHtml}</td>
                    <td>{$vendorHtml}</td>
                    <td>{$dateHtml}</td>
                    <td>{$invNo}</td>
                    <td>{$invDate}</td>
                    <td>{$recByHtml}</td>
                    <td>{$amountHtml}</td>
                    <td>{$statusBadge}</td>
                    <td class='text-nowrap'>
                        <button class='btn btn-sm btn-outline-primary me-1 rounded-2 fw-semibold' onclick='viewPO({$po['po_id']})' title='View Order Details'><i class='fas fa-eye me-1'></i>View</button>
                        {$previewBtn}
                        {$receiveBtn}
                    </td>
                </tr>";
            } else {
                $rows .= "<tr>
                    <td>{$poNumHtml}</td>
                    <td>{$vendorHtml}</td>
                    <td>{$dateHtml}</td>
                    <td>{$amountHtml}</td>
                    <td>{$statusBadge}</td>
                    <td class='text-nowrap'>
                        <button class='btn btn-sm btn-outline-primary me-1 rounded-2 fw-semibold' onclick='viewPO({$po['po_id']})' title='View Order Details'><i class='fas fa-eye me-1'></i>View</button>
                        {$pdfBtn}
                        {$receiveBtn}
                        {$deleteBtn}
                    </td>
                </tr>";
            }
        }

        // Generate Footer Total
        if ($isReceivedOnly) {
            $footer = "<tr class='fw-bold bg-light border-top border-2'>
                <td colspan='6' class='text-end text-uppercase text-secondary py-3'>Total Received Amount (" . count($pos) . " Orders):</td>
                <td class='text-success fw-bold fs-6 py-3'>₹" . number_format($displayedTotal, 2) . "</td>
                <td colspan='2'></td>
            </tr>";
        } else {
            $statusLabel = !empty($filters['status']) ? htmlspecialchars($filters['status']) : 'All';
            $footer = "<tr class='fw-bold bg-light border-top border-2'>
                <td colspan='3' class='text-end text-uppercase text-secondary py-3'>Total {$statusLabel} Amount (" . count($pos) . " Orders):</td>
                <td class='text-primary fw-bold fs-6 py-3'>₹" . number_format($displayedTotal, 2) . "</td>
                <td colspan='2'></td>
            </tr>";
        }
    } else {
        $colSpan = $isReceivedOnly ? 9 : 6;
        $rows = "<tr>
            <td colspan='{$colSpan}' class='text-center py-5 text-muted'>
                <div class='empty-state-box'>
                    <i class='fas fa-file-invoice fs-1 d-block mb-3 opacity-25 text-primary'></i>
                    <h6 class='fw-bold text-secondary mb-1'>No Purchase Orders Found</h6>
                    <p class='small text-muted mb-0'>Try adjusting your search query or status filter.</p>
                </div>
            </td>
        </tr>";
        $footer = "";
    }

    echo json_encode([
        'headers' => $headers,
        'rows' => $rows,
        'footer' => $footer,
        'stats' => [
            'total' => $totalCount,
            'draft' => $draftCount,
            'received' => $receivedCount,
            'cancelled' => $cancelledCount,
            'total_val' => '₹' . number_format($totalVal, 2)
        ]
    ]);
    exit;
}

if ($action == 'delete_po' || $action == 'cancel_po') {
    $poId = intval($_POST['po_id'] ?? 0);
    $remark = trim($_POST['remark'] ?? $_POST['remarks'] ?? 'Cancelled by user');

    if ($poId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Purchase Order ID.']);
        exit;
    }

    // Check PO status
    $stmt = $krfconn->prepare("SELECT po_number, status, remarks FROM inventory_purchase_orders WHERE po_id = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'DB prepare error: ' . $krfconn->error]);
        exit;
    }
    $stmt->bind_param("i", $poId);
    $stmt->execute();
    $res = $stmt->get_result();
    $po = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$po) {
        echo json_encode(['status' => 'error', 'message' => 'Purchase Order not found.']);
        exit;
    }

    if (strtolower($po['status']) === 'received') {
        echo json_encode(['status' => 'error', 'message' => 'Cannot remove or cancel a Received Purchase Order.']);
        exit;
    }

    // Also check if any receipts exist
    $recCheck = $krfconn->query("SELECT COUNT(*) AS total_receipts FROM inventory_purchase_receipts WHERE po_id = {$poId}");
    if ($recCheck && ($recRow = $recCheck->fetch_assoc()) && (int)$recRow['total_receipts'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot cancel PO because goods receipts have already been recorded against it.']);
        exit;
    }

    $krfconn->begin_transaction();
    try {
        // 1. Update inventory_purchase_orders: status = 'Cancelled', remarks = ?
        $stmtPo = $krfconn->prepare("UPDATE inventory_purchase_orders SET status = 'Cancelled', remarks = ? WHERE po_id = ?");
        if ($stmtPo) {
            $stmtPo->bind_param("si", $remark, $poId);
            $stmtPo->execute();
            $stmtPo->close();
        }

        // 2. Update inventory_purchase_order_items: status = 'Cancelled', remark = ?
        $stmtItems = $krfconn->prepare("UPDATE inventory_purchase_order_items SET status = 'Cancelled', remark = ? WHERE po_id = ?");
        if ($stmtItems) {
            $stmtItems->bind_param("si", $remark, $poId);
            $stmtItems->execute();
            $stmtItems->close();
        }

        // 3. Reset any linked demands in inventory_purchase_demand_alerts and krf_production_stockorder if they exist
        @$krfconn->query("UPDATE inventory_purchase_demand_alerts SET status = 'Pending', po_id = NULL WHERE po_id = {$poId}");
        @$krfconn->query("UPDATE krf_production_stockorder SET po_number = NULL, po_id = NULL WHERE po_id = {$poId}");

        $krfconn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => "Purchase Order #{$po['po_number']} status updated to 'Cancelled' with remark."
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to cancel PO: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'delete_po_item') {
    $poItemId = intval($_POST['po_item_id'] ?? 0);
    $poId = intval($_POST['po_id'] ?? 0);

    if ($poItemId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid PO Item ID.']);
        exit;
    }

    // Check item record
    $stmt = $krfconn->prepare("SELECT poi.*, po.status AS po_status FROM inventory_purchase_order_items poi LEFT JOIN inventory_purchase_orders po ON po.po_id = poi.po_id WHERE poi.po_item_id = ?");
    if (!$stmt) {
        echo json_encode(['status' => 'error', 'message' => 'DB prepare error: ' . $krfconn->error]);
        exit;
    }
    $stmt->bind_param("i", $poItemId);
    $stmt->execute();
    $res = $stmt->get_result();
    $item = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$item) {
        echo json_encode(['status' => 'error', 'message' => 'PO item entry not found.']);
        exit;
    }

    if ($poId <= 0) {
        $poId = intval($item['po_id']);
    }

    // If item was already received (received_qty > 0), do not allow deletion
    if ((float)($item['received_qty'] ?? 0) > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot remove item because it has already been received in inventory.']);
        exit;
    }

    $krfconn->begin_transaction();
    try {
        // Delete item from inventory_purchase_order_items
        $del = $krfconn->prepare("DELETE FROM inventory_purchase_order_items WHERE po_item_id = ?");
        if ($del) {
            $del->bind_param("i", $poItemId);
            $del->execute();
            $del->close();
        }

        // Recalculate totals for the PO
        if ($poId > 0) {
            $sumRes = $krfconn->query("SELECT 
                COALESCE(SUM(taxable_amount), 0) AS total_taxable, 
                COALESCE(SUM(gst_amount), 0) AS total_gst, 
                COALESCE(SUM(total_price), 0) AS grand_total,
                COUNT(*) AS remaining_items,
                SUM(CASE WHEN received_qty >= quantity AND quantity > 0 THEN 1 ELSE 0 END) AS fully_received_count,
                SUM(CASE WHEN received_qty > 0 THEN 1 ELSE 0 END) AS any_received_count
                FROM inventory_purchase_order_items WHERE po_id = {$poId}");
            
            if ($sumRes && ($sum = $sumRes->fetch_assoc())) {
                $remItems = (int)$sum['remaining_items'];
                $totTaxable = (float)$sum['total_taxable'];
                $totGst = (float)$sum['total_gst'];
                $grandTot = (float)$sum['grand_total'];
                $fullRec = (int)$sum['fully_received_count'];
                $anyRec = (int)$sum['any_received_count'];

                $newPoStatus = 'Draft';
                if ($remItems > 0 && $fullRec === $remItems) {
                    $newPoStatus = 'Received';
                } else if ($anyRec > 0) {
                    $newPoStatus = 'Partial';
                } else if ($remItems === 0) {
                    $newPoStatus = 'Draft';
                }

                $upPo = $krfconn->prepare("UPDATE inventory_purchase_orders SET taxable_amount = ?, gst_amount = ?, total_amount = ?, status = ? WHERE po_id = ?");
                if ($upPo) {
                    $upPo->bind_param("dddsi", $totTaxable, $totGst, $grandTot, $newPoStatus, $poId);
                    $upPo->execute();
                    $upPo->close();
                }
            }
        }

        $krfconn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => 'Item entry removed from Purchase Order successfully.'
        ]);
    } catch (Exception $e) {
        $krfconn->rollback();
        echo json_encode(['status' => 'error', 'message' => 'Failed to remove item: ' . $e->getMessage()]);
    }
    exit;
}

if ($action == 'list_vendors') {
    // UPDATED: Fetch from supplier_authorizedperson (Vendor/Supplier List)
    // Table: supplier_authorizedperson (sno as ID, paytoname as Name)

    $res = $conn->query("SELECT paytoid, paytoname FROM supplier_authorizedperson ORDER BY paytoname");

    $options = "<option value=''>Select Vendor</option>";
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $options .= "<option value='{$row['paytoid']}'>{$row['paytoname']}</option>";
        }
    } else {
        $options .= "<option disabled>No Vendors Found</option>";
    }

    echo json_encode(['options' => $options]);
    exit;
}

if ($action == 'list_items') {
    $res = $krfconn->query("SELECT item_id, item_name, sku, seed_id FROM inventory_purchase_item_master WHERE status='Active' ORDER BY item_name");
    $options = "<option value=''>Select Item</option>";
    $items = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $sid = !empty($row['seed_id']) ? $row['seed_id'] : '0';
            $options .= "<option value='{$row['item_id']}' data-seed-id='{$sid}'>{$row['item_name']} ({$row['sku']})</option>";
            $items[] = [
                'id' => (string) $row['item_id'],
                'seed_id' => (string) $sid,
                'name' => (string) $row['item_name'],
                'sku' => (string) ($row['sku'] ?? ''),
                'text' => $row['item_name'] . (!empty($row['sku']) ? " ({$row['sku']})" : "")
            ];
        }
    }
    echo json_encode(['options' => $options, 'items' => $items]);
    exit;
}

if ($action == 'list_uoms') {
    $res = $krfconn->query("SELECT uom_code, uom_name, uom_symbol FROM inventory_uom_master WHERE status = 1 ORDER BY uom_name ASC");
    $options = "<option value=''>Select UOM</option>";
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $options .= "<option value='{$row['uom_code']}'>{$row['uom_name']} ({$row['uom_code']})</option>";
        }
    } else {
        $defaultUoms = [
            'BAG' => 'Bag (BAG)',
            'BOX' => 'Box (BOX)',
            'BTL' => 'Bottle (BTL)',
            'BDL' => 'Bundle (BDL)',
            'CAN' => 'Can (CAN)',
            'GM'  => 'Gram (GM)',
            'KG'  => 'Kilogram (KG)',
            'LTR' => 'Litre (LTR)',
            'NOS' => 'Number (NOS)',
            'PCS' => 'Piece (PCS)',
            'PKT' => 'Packet (PKT)',
            'SET' => 'Set (SET)'
        ];
        foreach ($defaultUoms as $code => $label) {
            $options .= "<option value='{$code}'>{$label}</option>";
        }
    }
    echo json_encode(['options' => $options]);
    exit;
}

if ($action == 'create_po') {
    $data = [
        'vendor_id' => $_POST['vendor_id'],
        'po_date' => $_POST['po_date'],
        'expected_date' => $_POST['expected_date'],
        'items' => json_decode($_POST['items'], true)
    ];

    $res = savePurchaseOrder($krfconn, $data);
    echo json_encode($res);
    exit;
}

if ($action == 'get_po') {
    $id = $_POST['po_id'];
    $po = getPurchaseOrderById($krfconn, $id);
    if ($po) {
        // Format Items HTML
        $itemsHtml = "";
        $totalTaxable = 0;
        $totalGst = 0;
        $grandTotal = 0;

        foreach ($po['items'] as $item) {
            $unitDesc = !empty($item['unit']) ? " <small class='text-muted'>({$item['unit']} - {$item['unit_size']})</small>" : "";
            
            // UOM Master Badges
            $baseUomText = !empty($item['base_uom']) ? $item['base_uom'] : 'N/A';
            $purUomText = !empty($item['purchase_uom']) ? $item['purchase_uom'] : 'N/A';
            $isPurchaseMode = (!empty($item['uom_mode']) && $item['uom_mode'] === 'PURCHASE');
            $modeText = $isPurchaseMode ? "PURCHASE UOM ({$purUomText})" : "BASE UOM ({$baseUomText})";
            $uomBadgeClass = $isPurchaseMode ? "bg-success-subtle text-success border-success-subtle" : "bg-primary-subtle text-primary border-primary-subtle";
            $uomModeBadge = "<span class='badge {$uomBadgeClass} border me-1'><i class='fas fa-ruler-combined me-1'></i>{$modeText}</span>";

            // Inventory Control & Tracking Badges
            $trackingBadges = "";
            if (!empty($item['batch_required'])) {
                $trackingBadges .= "<span class='badge bg-info-subtle text-info border me-1' title='Batch tracking required'><i class='fas fa-barcode me-1'></i>Batch Required</span>";
            }
            if (!empty($item['expiry_required'])) {
                $trackingBadges .= "<span class='badge bg-danger-subtle text-danger border me-1' title='Expiry tracking required'><i class='fas fa-calendar-times me-1'></i>Expiry Required</span>";
            }
            if (!empty($item['serial_required'])) {
                $trackingBadges .= "<span class='badge bg-secondary-subtle text-dark border me-1' title='Serial tracking required'><i class='fas fa-hashtag me-1'></i>Serial Required</span>";
            }

            $qtyDetail = (!empty($item['base_quantity']) && (float)$item['base_quantity'] > 0 && $isPurchaseMode) 
                ? " <small class='text-muted d-block font-monospace'><i class='fas fa-calculator me-1 text-info'></i>Converted Base: <strong>{$item['base_quantity']} {$baseUomText}</strong></small>" 
                : "";

            $taxable = (float)($item['taxable_amount'] ?? ((float)$item['quantity'] * (float)$item['unit_price']));
            $gstP = (float)($item['gst_percent'] ?? 0);
            $gstAmt = (float)($item['gst_amount'] ?? ($taxable * ($gstP / 100)));
            $lineTot = (float)($item['total_price'] ?? ($taxable + $gstAmt));

            $totalTaxable += $taxable;
            $totalGst += $gstAmt;
            $grandTotal += $lineTot;

            $gstBadge = ($gstP > 0)
                ? "<span class='badge bg-warning-subtle text-dark border'>{$gstP}% (₹" . number_format($gstAmt, 2) . ")</span>"
                : "<span class='badge bg-light text-muted border'>0%</span>";

            $itemsHtml .= "<tr>
                <td>
                    <div class='fw-bold text-dark'>{$item['item_name']} <span class='text-muted small'>({$item['sku']})</span></div>
                    {$unitDesc}
                    <div class='mt-1 d-flex flex-wrap gap-1 align-items-center'>
                        {$uomModeBadge}
                        {$trackingBadges}
                    </div>
                </td>
                <td>
                    <span class='fw-bold fs-6 text-dark'>{$item['quantity']}</span>
                    {$qtyDetail}
                </td>
                <td class='fw-semibold'>₹" . number_format($item['unit_price'], 2) . "</td>
                <td>₹" . number_format($taxable, 2) . "</td>
                <td>{$gstBadge}</td>
                <td class='fw-bold text-primary'>₹" . number_format($lineTot, 2) . "</td>
                <td><span class='badge bg-light text-dark border px-2 py-1'>{$item['received_qty']}</span></td>
            </tr>";
        }
        $po['items_html'] = $itemsHtml;
        $po['total_taxable'] = $totalTaxable;
        $po['total_gst'] = $totalGst;
        $po['grand_total'] = $grandTotal;

        // Build Receipts / Invoice HTML Card
        $receiptsHtml = "";
        if (!empty($po['receipts'])) {
            $receiptsHtml .= "<div class='card bg-light border-0 rounded-3 p-3 mb-4 border-start border-success border-4 shadow-sm'>";
            $receiptsHtml .= "<div class='d-flex justify-content-between align-items-center mb-3'><h6 class='fw-bold text-success mb-0'><i class='fas fa-file-invoice me-2'></i>Vendor Invoice & Receipt Records</h6><span class='badge bg-success-subtle text-success border border-success-subtle px-3 py-1 rounded-pill fw-bold'><i class='fas fa-check-circle me-1'></i>Verified Invoice</span></div>";
            $receiptsHtml .= "<div class='row g-3'>";
            foreach ($po['receipts'] as $rec) {
                $invNo = !empty($rec['invoice_no']) ? $rec['invoice_no'] : 'N/A';
                $invDate = !empty($rec['invoice_date']) ? $rec['invoice_date'] : 'N/A';
                $recDate = !empty($rec['received_date']) ? $rec['received_date'] : 'N/A';
                $locName = !empty($rec['location_name']) ? $rec['location_name'] : 'N/A';
                $recAmt = "₹" . number_format($rec['total_amount'], 2);

                $receiptsHtml .= "<div class='col-md-6 col-lg-4'>
                    <div class='p-3 bg-white border rounded-3 shadow-sm h-100 d-flex flex-column justify-content-between'>
                        <div>
                            <div class='d-flex justify-content-between align-items-center mb-2 pb-1 border-bottom'>
                                <span class='font-monospace fw-bold text-dark fs-6'><i class='fas fa-receipt me-1 text-primary'></i>Bill #: {$invNo}</span>
                                <button type='button' class='btn btn-xs btn-outline-primary fw-bold rounded-2 px-2 py-0' style='font-size:0.75rem;' onclick='previewInvoice({$rec['receipt_id']})' title='Preview Invoice Document'><i class='fas fa-eye me-1'></i>Preview</button>
                            </div>
                            <div class='super-small text-muted mb-1'><i class='far fa-calendar-alt me-1 text-info'></i>Invoice Date: <strong>{$invDate}</strong></div>
                            <div class='super-small text-muted mb-1'><i class='fas fa-truck-loading me-1 text-warning'></i>Received Date: <strong>{$recDate}</strong></div>
                            <div class='super-small text-muted mb-1'><i class='fas fa-warehouse me-1 text-secondary'></i>Location: <strong>{$locName}</strong></div>
                        </div>
                        <div class='fw-bold text-success mt-2 pt-2 border-top text-end fs-6'>Total: {$recAmt}</div>
                    </div>
                </div>";
            }
            $receiptsHtml .= "</div></div>";
        }
        $po['receipts_html'] = $receiptsHtml;

        echo json_encode(['status' => 'success', 'data' => $po]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'PO not found']);
    }
    exit;
}


if ($action == 'get_receipt_details') {
    $receiptId = intval($_POST['receipt_id']);
    
    // Fetch receipt header
    $recRes = $krfconn->query("SELECT r.*, po.po_number, po.po_date, po.vendor_id
                               FROM inventory_purchase_receipts r
                               LEFT JOIN inventory_purchase_orders po ON r.po_id = po.po_id
                               WHERE r.receipt_id = $receiptId");
    $rec = $recRes ? $recRes->fetch_assoc() : null;

    if ($rec) {
        $rec['VendorName'] = 'Supplier';
        if (!empty($rec['vendor_id'])) {
            if (isset($conn) && $conn) {
                $vId = $conn->real_escape_string($rec['vendor_id']);
                $vRes = $conn->query("SELECT paytoname FROM supplier_authorizedperson WHERE paytoid = '$vId' LIMIT 1");
                if ($vRes && $vRow = $vRes->fetch_assoc()) {
                    $rec['VendorName'] = $vRow['paytoname'];
                }
            }
            if (($rec['VendorName'] === 'Supplier' || empty($rec['VendorName'])) && isset($krfconn) && $krfconn) {
                $vId = $krfconn->real_escape_string($rec['vendor_id']);
                $vRes2 = $krfconn->query("SELECT VendorName FROM vendor_master WHERE VendorID = '$vId' LIMIT 1");
                if ($vRes2 && $vRow2 = $vRes2->fetch_assoc()) {
                    $rec['VendorName'] = $vRow2['VendorName'];
                }
            }
        }

        global $hrmconn;
        $locName = 'Main Location #' . $rec['location_id'];
        if (isset($hrmconn) && $hrmconn) {
            $lRes = $hrmconn->query("SELECT LocationName FROM hrm_AttendanceLocationMaster WHERE sno = {$rec['location_id']}");
            if ($lRes && $lRow = $lRes->fetch_assoc()) {
                $locName = $lRow['LocationName'];
            }
        }
        $rec['location_name'] = $locName;

        // Fetch receipt items
        $itemsRes = $krfconn->query("SELECT ri.*, 
                                     COALESCE(m_item.item_name, m_seed.item_name, s.SeedName, 'Item') as item_name, 
                                     COALESCE(m_item.sku, m_seed.sku, s.sku, '') as sku, 
                                     COALESCE(m_item.unit, m_item.base_uom, m_seed.unit, 'Unit') as base_uom,
                                     COALESCE(m_item.cgst, m_seed.cgst, 0) as m_cgst,
                                     COALESCE(m_item.sgst, m_seed.sgst, 0) as m_sgst,
                                     COALESCE(m_item.igst, m_seed.igst, 0) as m_igst,
                                     COALESCE(m_item.tax_percent, m_seed.tax_percent, 0) as m_tax_percent
                                     FROM inventory_purchase_receipt_items ri 
                                     LEFT JOIN inventory_purchase_item_master m_item ON (m_item.item_id = COALESCE(NULLIF(ri.item_id, 0), ri.seed_id))
                                     LEFT JOIN inventory_purchase_item_master m_seed ON (m_seed.seed_id = COALESCE(NULLIF(ri.seed_id, 0), ri.item_id) AND COALESCE(NULLIF(ri.seed_id, 0), ri.item_id) > 0 AND m_item.item_id IS NULL)
                                     LEFT JOIN seed_master s ON (COALESCE(NULLIF(ri.seed_id, 0), ri.item_id) = s.SeedID AND m_item.item_id IS NULL AND m_seed.item_id IS NULL)
                                     WHERE ri.receipt_id = $receiptId");
        $itemsHtml = "";
        $recTaxable = 0;
        $recGst = 0;
        $recTotal = 0;

        if ($itemsRes && $itemsRes->num_rows > 0) {
            while ($row = $itemsRes->fetch_assoc()) {
                $uomText = !empty($row['base_uom']) ? $row['base_uom'] : 'Unit';
                $taxable = (float)($row['taxable_amount'] ?? ((float)$row['quantity'] * (float)$row['unit_price']));
                $gstP = (float)($row['gst_percent'] ?? 0);
                if ($gstP == 0) {
                    $mCgst = (float)($row['m_cgst'] ?? 0);
                    $mSgst = (float)($row['m_sgst'] ?? 0);
                    $mIgst = (float)($row['m_igst'] ?? 0);
                    $mTax = (float)($row['m_tax_percent'] ?? 0);
                    $gstP = ($mIgst > 0) ? $mIgst : (($mCgst + $mSgst > 0) ? ($mCgst + $mSgst) : $mTax);
                }
                $gstAmt = (float)($row['gst_amount'] ?? ($taxable * ($gstP / 100)));
                $lineTot = (float)($row['total_price'] ?? ($taxable + $gstAmt));

                $recTaxable += $taxable;
                $recGst += $gstAmt;
                $recTotal += $lineTot;

                $gstBadge = ($gstP > 0)
                    ? "<span class='badge bg-warning-subtle text-dark border'>{$gstP}% (₹" . number_format($gstAmt, 2) . ")</span>"
                    : "<span class='badge bg-light text-muted border'>0%</span>";

                $skuHtml = !empty($row['sku']) ? " <small class='text-muted'>(" . htmlspecialchars($row['sku']) . ")</small>" : "";

                $itemsHtml .= "<tr>
                    <td><span class='fw-bold text-dark'>" . htmlspecialchars($row['item_name']) . "</span>{$skuHtml}</td>
                    <td>{$row['quantity']} <small class='text-muted'>{$uomText}</small></td>
                    <td>₹" . number_format($row['unit_price'], 2) . "</td>
                    <td>₹" . number_format($taxable, 2) . "</td>
                    <td>{$gstBadge}</td>
                    <td class='fw-bold text-primary'>₹" . number_format($lineTot, 2) . "</td>
                </tr>";
            }
        }
        $rec['items_html'] = $itemsHtml;
        $rec['taxable_total'] = $recTaxable;
        $rec['gst_total'] = $recGst;
        $rec['total_amount'] = $recTotal > 0 ? $recTotal : (float)($rec['total_amount'] ?? 0);
        echo json_encode(['status' => 'success', 'data' => $rec]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Receipt details not found']);
    }
    exit;
}

if ($action == 'receive_po') {
    $data = [
        'po_id' => $_POST['po_id'],
        'location_id' => $_POST['location_id'],
        'invoice_no' => $_POST['invoice_no'],
        'invoice_date' => $_POST['invoice_date'],
        'received_date' => $_POST['received_date'],
        'items' => json_decode($_POST['items'], true)
    ];

    include_once "po_functions_v2.php";
    if (function_exists('finalizePurchaseReceipt')) {
        $res = finalizePurchaseReceipt($krfconn, $data);
    } else {
        $res = ["status" => "error", "message" => "Finalization logic not found"];
    }

    echo json_encode($res);
    exit;
}

if ($action == 'list_inventory') {
    $locId = $_POST['location_id'] ?? null;
    $itemId = $_POST['item_id'] ?? null;
    $dateFrom = $_POST['date_from'] ?? null;
    $dateTo = $_POST['date_to'] ?? null;
    $isRealtime = !empty($_POST['is_realtime']);
    $rows = "";

    // If v2
    include_once "po_functions_v2.php";

    $isAllLocations = ($locId === null || $locId === '');

    if ($isAllLocations && function_exists('getInventoryAggregatedByItem')) {
        // Aggregate stock item-wise across all locations
        $data = getInventoryAggregatedByItem($krfconn, $itemId, $dateFrom, $dateTo);
        foreach ($data as $row) {
            $stockQty = (float)$row['stock_quantity'];
            $totalPurchase = (float)($row['total_purchase'] ?? 0);
            $totalUsed = (float)($row['total_used'] ?? 0);
            $balanceQty = (float)($row['balance_quantity'] ?? ($totalPurchase - $totalUsed));
            $locCount = (int)($row['location_count'] ?? 1);
            $seedNameEsc = htmlspecialchars($row['SeedName'], ENT_QUOTES);
            $skuEsc = htmlspecialchars($row['sku'], ENT_QUOTES);
            $seedId = (int)$row['seed_id'];

            $locBadge = "<span class='badge-all-locs'><i class='fas fa-layer-group me-1.5 text-primary'></i> All Locations ({$locCount})</span>";

            $rows .= "<tr>
                <td style='width: 140px;'><span class='stock-sku'>{$row['sku']}</span></td>
                <td><div class='stock-item-name'>{$row['SeedName']}</div></td>
                <td>{$locBadge}</td>
                <td class='text-end'><span class='stock-num'>" . number_format($totalPurchase, 2) . "</span></td>
                <td class='text-end'><span class='stock-num text-muted'>" . number_format($totalUsed, 2) . "</span></td>
                <td class='text-end'><span class='stock-num stock-num-primary'>" . number_format($stockQty, 2) . "</span></td>
                <td class='text-end'><span class='stock-num stock-num-success'>" . number_format($balanceQty, 2) . "</span></td>
                <td class='text-center'>
                    <button type='button' class='btn-view-details' onclick=\"viewItemLocationBreakdown({$seedId}, '{$seedNameEsc}', '{$skuEsc}')\" title='View Location-wise Stock Details'>
                        <i class='fas fa-eye'></i> View Details
                    </button>
                </td>
            </tr>";
        }
    } else if (function_exists('getInventoryWithLocation')) {
        // Filter by specific location
        $data = getInventoryWithLocation($krfconn, $locId, $itemId, $dateFrom, $dateTo);
        foreach ($data as $row) {
            $stockQty = (float)$row['stock_quantity'];
            $lastUpdated = !empty($row['last_updated']) ? date('Y-m-d H:i', strtotime($row['last_updated'])) : '-';
            $seedNameEsc = htmlspecialchars($row['SeedName'], ENT_QUOTES);
            $skuEsc = htmlspecialchars($row['sku'], ENT_QUOTES);
            $seedId = (int)$row['seed_id'];
            $locationId = (int)$row['location_id'];

            if ($isRealtime) {
                $totalPurchase = (float)($row['total_purchase'] ?? 0);
                $totalUsed = (float)($row['total_used'] ?? 0);
                $balanceQty = (float)($row['balance_quantity'] ?? ($totalPurchase - $totalUsed));

                $rows .= "<tr>
                    <td style='width: 140px;'><span class='stock-sku'>{$row['sku']}</span></td>
                    <td><div class='stock-item-name'>{$row['SeedName']}</div></td>
                    <td><span class='badge-single-loc'><i class='fas fa-map-marker-alt me-1.5 text-success'></i> {$row['location_name']}</span></td>
                    <td class='text-end'><span class='stock-num'>" . number_format($totalPurchase, 2) . "</span></td>
                    <td class='text-end'><span class='stock-num text-muted'>" . number_format($totalUsed, 2) . "</span></td>
                    <td class='text-end'><span class='stock-num stock-num-primary'>" . number_format($stockQty, 2) . "</span></td>
                    <td class='text-end'><span class='stock-num stock-num-success'>" . number_format($balanceQty, 2) . "</span></td>
                    <td class='text-center'>
                        <button type='button' class='btn-view-details' onclick=\"viewItemLocationBreakdown({$seedId}, '{$seedNameEsc}', '{$skuEsc}', {$locationId})\" title='View Stock & Transaction Details'>
                            <i class='fas fa-eye'></i> View Details
                        </button>
                    </td>
                </tr>";
            } else {
                $rows .= "<tr>
                    <td style='width: 140px;'><span class='stock-sku'>{$row['sku']}</span></td>
                    <td><div class='stock-item-name'>{$row['SeedName']}</div></td>
                    <td><span class='badge-single-loc'><i class='fas fa-map-marker-alt me-1.5 text-success'></i> {$row['location_name']}</span></td>
                    <td class='text-end'><span class='stock-num stock-num-primary'>" . number_format($stockQty, 2) . "</span></td>
                    <td><small class='text-muted font-monospace'>{$lastUpdated}</small></td>
                    <td class='text-center'>
                        <button type='button' class='btn-view-details' onclick=\"viewItemLocationBreakdown({$seedId}, '{$seedNameEsc}', '{$skuEsc}', {$locationId})\">
                            <i class='fas fa-eye'></i> View Details
                        </button>
                    </td>
                </tr>";
            }
        }
    } else {
        $cols = $isRealtime ? 8 : 6;
        $rows = "<tr><td colspan='{$cols}' class='text-center py-4 text-muted'>Function 'getInventoryWithLocation' not found.</td></tr>";
    }

    if (empty($rows)) {
        $cols = $isRealtime ? 8 : 6;
        $rows = "<tr><td colspan='{$cols}' class='text-center py-4 text-muted'>No stock found for selected criteria.</td></tr>";
    }

    echo json_encode(['rows' => $rows]);
    exit;
}

if ($action == 'get_item_location_breakdown') {
    include_once "po_functions_v2.php";
    $seedId = (int)($_POST['seed_id'] ?? 0);
    $dateFrom = $_POST['date_from'] ?? null;
    $dateTo = $_POST['date_to'] ?? null;

    if ($seedId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Item ID.']);
        exit;
    }

    $result = getItemLocationBreakdown($krfconn, $seedId, $dateFrom, $dateTo);
    echo json_encode($result);
    exit;
}


if ($action == 'list_cats') {
    $res = $krfconn->query("SELECT CategoryID, CategoryName FROM plant_category_master WHERE (LOWER(Status)='active' OR Status IS NULL OR Status='') AND CategoryID != '' AND CategoryID IS NOT NULL ORDER BY CategoryName ASC");
    $options = "<option value=''>Select Category</option>";
    while ($row = $res->fetch_assoc()) {
        $options .= "<option value='{$row['CategoryID']}'>{$row['CategoryName']}</option>";
    }
    echo json_encode(['options' => $options]);
    exit;
}

if ($action == 'list_subcats') {
    $catId = trim($_POST['cat_id'] ?? '');
    $options = "<option value=''>Select SubCategory</option>";
    if (!empty($catId)) {
        $stmt = $krfconn->prepare("SELECT SubCategoryID, SubCategoryName FROM plant_subcategory_master WHERE CategoryID = ? AND (LOWER(Status) = 'active' OR Status IS NULL OR Status = '') ORDER BY SubCategoryName ASC");
        $stmt->bind_param("s", $catId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $options .= "<option value='{$row['SubCategoryID']}'>{$row['SubCategoryName']}</option>";
        }
    }
    echo json_encode(['options' => $options]);
    exit;
}

if ($action == 'list_locations') {
    $options = "<option value=''>Select Location</option>";
    $connToUse = isset($hrmconn) ? $hrmconn : (isset($krfconn) ? $krfconn : null);
    
    if ($connToUse) {
        $sql = "SELECT sno, LocationId, LocationName FROM hrm_AttendanceLocationMaster ORDER BY LocationName";
        $res = $connToUse->query($sql);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $options .= "<option value='{$row['sno']}'>{$row['LocationName']}</option>";
            }
        }
    }

    echo json_encode(['options' => $options]);
    exit;
}

if ($action == 'save_quick_cat') {
    $name = trim($_POST['name'] ?? '');
    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
        exit;
    }

    // Check if category already exists
    $chk = $krfconn->prepare("SELECT CategoryID FROM plant_category_master WHERE LOWER(CategoryName) = LOWER(?)");
    $chk->bind_param("s", $name);
    $chk->execute();
    $chkRes = $chk->get_result();
    if ($chkRes->num_rows > 0) {
        $existing = $chkRes->fetch_assoc();
        echo json_encode(['status' => 'success', 'new_id' => $existing['CategoryID']]);
        exit;
    }

    // Generate unique CategoryID and SKU
    $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
    $prefix = strtoupper(substr($clean, 0, 3));
    if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'C');
    $catId = $prefix . sprintf("%02d", rand(10, 99));
    $sku = $prefix . "01";
    $projectId = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : (isset($_COOKIE['projectid']) ? $_COOKIE['projectid'] : 17);

    $stmt = $krfconn->prepare("INSERT INTO plant_category_master (CategoryID, sku, CategoryName, ProjectId, Status, CreatedAt) VALUES (?, ?, ?, ?, 'Active', NOW())");
    $stmt->bind_param("ssss", $catId, $sku, $name, $projectId);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'new_id' => $catId]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    exit;
}

if ($action == 'save_quick_subcat') {
    $name = trim($_POST['name'] ?? '');
    $catId = trim($_POST['category_id'] ?? '');

    if (empty($name) || empty($catId)) {
        echo json_encode(['status' => 'error', 'message' => 'Subcategory name and parent category are required.']);
        exit;
    }

    // Check if subcategory already exists for this category
    $chk = $krfconn->prepare("SELECT SubCategoryID FROM plant_subcategory_master WHERE LOWER(SubCategoryName) = LOWER(?) AND CategoryID = ?");
    $chk->bind_param("ss", $name, $catId);
    $chk->execute();
    $chkRes = $chk->get_result();
    if ($chkRes->num_rows > 0) {
        $existing = $chkRes->fetch_assoc();
        echo json_encode(['status' => 'success', 'new_id' => $existing['SubCategoryID'], 'message' => 'Subcategory already exists']);
        exit;
    }

    // Generate SKU
    $clean = preg_replace('/[^A-Za-z0-9]/', '', $name);
    $prefix = strtoupper(substr($clean, 0, 3));
    if (strlen($prefix) < 3) $prefix = str_pad($prefix, 3, 'X');
    $sku = $prefix . sprintf("%02d", rand(1, 99));
    $projectId = isset($_SESSION['projectid']) ? $_SESSION['projectid'] : (isset($_COOKIE['projectid']) ? $_COOKIE['projectid'] : 17);
    $user = isset($_SESSION['username']) ? $_SESSION['username'] : (isset($_SESSION['loginname']) ? $_SESSION['loginname'] : 'SYSTEM');

    $stmt = $krfconn->prepare("INSERT INTO plant_subcategory_master (sku, SubCategoryName, CategoryID, ProjectId, Status, CreatedAt, CreatedBy) VALUES (?, ?, ?, ?, 'Active', NOW(), ?)");
    $stmt->bind_param("sssss", $sku, $name, $catId, $projectId, $user);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'new_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    exit;
}

if ($action == 'list_item_master') {
    if (ob_get_length()) ob_clean();
    try {
        $search = isset($_POST['search']) ? trim($krfconn->real_escape_string($_POST['search'])) : '';
        $catId = isset($_POST['category_id']) ? trim($krfconn->real_escape_string($_POST['category_id'])) : '';
        $status = isset($_POST['status']) ? trim($krfconn->real_escape_string($_POST['status'])) : '';

        $where = [];
        if (!empty($search)) {
            $where[] = "(i.item_name LIKE '%$search%' OR i.sku LIKE '%$search%' OR i.hsn_code LIKE '%$search%')";
        }
        if (!empty($catId)) {
            $where[] = "i.category_id = '$catId'";
        }
        if (!empty($status)) {
            $where[] = "i.status = '$status'";
        }

        $whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $res = @$krfconn->query("SELECT i.*, c.CategoryName 
                               FROM inventory_purchase_item_master i 
                               LEFT JOIN plant_category_master c ON i.category_id = c.CategoryID 
                               $whereSql
                               ORDER BY i.item_name ASC");

        if (!$res) {
            $res = @$krfconn->query("SELECT i.*, '' AS CategoryName 
                                   FROM inventory_purchase_item_master i 
                                   $whereSql
                                   ORDER BY i.item_name ASC");
        }

        // Compute Overall Stats
        $totalRes = @$krfconn->query("SELECT COUNT(*) as total, SUM(CASE WHEN status='Active' THEN 1 ELSE 0 END) as active FROM inventory_purchase_item_master");
        $totalStats = ($totalRes && $totalRes instanceof mysqli_result) ? $totalRes->fetch_assoc() : ['total' => 0, 'active' => 0];

        $catCountRes = @$krfconn->query("SELECT COUNT(DISTINCT category_id) as total_cats FROM inventory_purchase_item_master WHERE category_id != '' AND category_id IS NOT NULL");
        $catStats = ($catCountRes && $catCountRes instanceof mysqli_result) ? $catCountRes->fetch_assoc() : ['total_cats' => 0];

        $rows = "";
        if ($res && $res instanceof mysqli_result && $res->num_rows > 0) {
            while ($row = $res->fetch_assoc()) {
                $itemId = $row['item_id'];
                $isStatusActive = strtolower($row['status']) == 'active';
                $statusBadge = $isStatusActive 
                    ? "<span class='badge-status active'><i class='fas fa-check-circle me-1'></i>Active</span>" 
                    : "<span class='badge-status inactive'><i class='fas fa-times-circle me-1'></i>Inactive</span>";

                $photoHtml = !empty($row['photo_path']) 
                    ? "<img src='../{$row['photo_path']}' class='item-photo-thumb shadow-sm' alt='Item Photo'>" 
                    : "<div class='item-photo-placeholder'><i class='fas fa-box-open text-muted fs-6'></i></div>";

                $skuHtml = !empty($row['sku']) 
                    ? "<span class='sku-pill'>{$row['sku']}</span>" 
                    : "<span class='text-muted small'>N/A</span>";

                $catHtml = !empty($row['CategoryName']) 
                    ? "<span class='category-pill'><i class='fas fa-tag me-1 text-primary opacity-75'></i>{$row['CategoryName']}</span>" 
                    : "<span class='text-muted small'>Uncategorized</span>";

                // Fetch Unit Info
                $uRes = @$krfconn->query("SELECT * FROM inventory_purchase_item_units WHERE item_id = '$itemId' ORDER BY item_unit_id ASC");
                $unitPills = "";
                $firstPrice = "-";
                $unitCount = 0;

                if ($uRes && $uRes instanceof mysqli_result && $uRes->num_rows > 0) {
                    $unitCount = $uRes->num_rows;
                    $uRow = $uRes->fetch_assoc();
                    $unitPills = "<span class='unit-pill'>{$uRow['unit']} <small class='opacity-75'>({$uRow['unit_size']})</small></span>";
                    $firstPrice = "₹" . number_format((float)$uRow['purchase_price'], 2);

                    if ($unitCount > 1) {
                        $unitPills .= " <span class='badge-variation-count'>+" . ($unitCount - 1) . " var</span>";
                    }
                } else {
                    $unitPills = "<span class='text-muted small'>No Units</span>";
                }

                $baseUomPill = !empty($row['base_uom']) ? "<span class='badge bg-light text-dark border me-1'>Base: {$row['base_uom']}</span>" : "";
                $purUomPill = !empty($row['purchase_uom']) ? "<span class='badge bg-primary-subtle text-primary border border-primary-subtle me-1'>Pur: {$row['purchase_uom']}</span>" : "";
                $saleUomPill = !empty($row['sale_uom']) ? "<span class='badge bg-success-subtle text-success border border-success-subtle me-1'>Sale: {$row['sale_uom']}</span>" : "";
                $convFactorText = !empty($row['conversion_factor']) ? (float)$row['conversion_factor'] : 1;

                $uomMasterHtml = ($baseUomPill || $purUomPill || $saleUomPill) 
                    ? "<div class='super-small mt-1'>{$baseUomPill}{$purUomPill}{$saleUomPill}<span class='text-muted ms-1'>Factor: {$convFactorText}</span></div>" 
                    : "";

                $safeItemName = htmlspecialchars($row['item_name'] ?? '', ENT_QUOTES);
                $skuVal = htmlspecialchars($row['sku'] ?? '', ENT_QUOTES);
                $statusActionBtn = $isStatusActive 
                    ? "<button class='btn btn-sm btn-action-deactivate' onclick='toggleItemStatus({$itemId}, \"Inactive\")' title='Deactivate Item'><i class='fas fa-ban me-1'></i>Deactivate</button>" 
                    : "<button class='btn btn-sm btn-action-activate' onclick='toggleItemStatus({$itemId}, \"Active\")' title='Activate Item'><i class='fas fa-check me-1'></i>Activate</button>";

                $deleteBtn = "<button class='btn btn-sm btn-action-delete' onclick='deleteItemMaster({$itemId}, \"{$safeItemName}\")' title='Delete Item Permanently'><i class='fas fa-trash me-1'></i>Delete</button>";

                $rows .= "<tr data-sku='{$skuVal}'>
                    <td class='text-center'>{$photoHtml}</td>
                    <td>
                        <div class='d-flex align-items-center gap-2 flex-wrap'>
                            <div class='fw-bold text-dark item-name-title'>{$row['item_name']}</div>
                            {$catHtml}
                        </div>
                        " . (!empty($row['hsn_code']) ? "<div class='text-muted super-small mt-1'><i class='fas fa-barcode me-1'></i>HSN: {$row['hsn_code']}</div>" : "") . "
                        {$uomMasterHtml}
                    </td>
                    <td>{$unitPills}</td>
                    <td><span class='price-tag'>{$firstPrice}</span></td>
                    <td>{$statusBadge}</td>
                    <td class='text-center' style='white-space: nowrap; min-width: 215px;'>
                        <div class='d-inline-flex align-items-center gap-1 justify-content-center flex-nowrap'>
                            <button class='btn btn-sm btn-action-edit' onclick='editItem({$itemId})' title='Edit Item Details'>
                                <i class='fas fa-pen me-1'></i>Edit
                            </button>
                            {$statusActionBtn}
                            {$deleteBtn}
                        </div>
                    </td>
                </tr>";
            }
        } else {
            $rows = "<tr>
                <td colspan='6' class='text-center py-4 text-muted'>
                    <div class='empty-state-box'>
                        <i class='fas fa-inbox fs-3 d-block mb-2 opacity-25 text-primary'></i>
                        <h6 class='fw-bold text-secondary mb-1' style='font-size:0.85rem;'>No Item Master Records Found</h6>
                        <p class='super-small text-muted mb-0'>Try adjusting your search query or filter settings.</p>
                    </div>
                </td>
            </tr>";
        }

        echo json_encode([
            'rows' => $rows,
            'stats' => [
                'total' => (int)($totalStats['total'] ?? 0),
                'active' => (int)($totalStats['active'] ?? 0),
                'categories' => (int)($catStats['total_cats'] ?? 0)
            ]
        ]);
    } catch (Throwable $e) {
        echo json_encode([
            'rows' => "<tr><td colspan='6' class='text-center text-danger py-4'>Error: " . htmlspecialchars($e->getMessage()) . "</td></tr>",
            'stats' => ['total' => 0, 'active' => 0, 'categories' => 0]
        ]);
    }
    exit;
}

if ($action == 'check_item_name') {
    if (ob_get_length()) ob_clean();
    $name = isset($_POST['name']) ? trim($krfconn->real_escape_string($_POST['name'])) : '';
    $currentId = isset($_POST['current_id']) ? (int)$_POST['current_id'] : 0;

    if (empty($name)) {
        echo json_encode(['status' => 'empty', 'is_exact' => false, 'count' => 0, 'matches' => []]);
        exit;
    }

    $idWhere = ($currentId > 0) ? " AND item_id != '$currentId'" : "";

    // 1. Exact match check (case-insensitive)
    $exactRes = @$krfconn->query("SELECT item_id, item_name, sku FROM inventory_purchase_item_master WHERE LOWER(item_name) = LOWER('$name') $idWhere LIMIT 1");
    $exactMatch = ($exactRes && $exactRes instanceof mysqli_result && $exactRes->num_rows > 0) ? $exactRes->fetch_assoc() : null;

    // 2. Similar / Partial matches check
    $similarRes = @$krfconn->query("SELECT item_id, item_name, sku FROM inventory_purchase_item_master WHERE item_name LIKE '%$name%' $idWhere ORDER BY item_name ASC LIMIT 5");
    $matches = [];
    if ($similarRes && $similarRes instanceof mysqli_result && $similarRes->num_rows > 0) {
        while ($row = $similarRes->fetch_assoc()) {
            $matches[] = [
                'id' => $row['item_id'],
                'name' => $row['item_name'],
                'sku' => $row['sku']
            ];
        }
    }

    echo json_encode([
        'status' => 'success',
        'is_exact' => ($exactMatch !== null),
        'exact_item' => $exactMatch,
        'count' => count($matches),
        'matches' => $matches
    ]);
    exit;
}

if ($action == 'get_item_details') {
    $id = $_POST['item_id'];
    $res = $krfconn->query("SELECT * FROM inventory_purchase_item_master WHERE item_id = '$id'");
    if ($res && $row = $res->fetch_assoc()) {
        // Fetch Units
        $unitsRes = $krfconn->query("SELECT * FROM inventory_purchase_item_units WHERE item_id = '$id'");
        $units = [];
        while ($uRow = $unitsRes->fetch_assoc()) {
            $units[] = $uRow;
        }
        $row['units'] = $units;
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Item not found']);
    }
    exit;
}

if ($action == 'save_purchase_item' || $action == 'save_item') {
    if (ob_get_length()) ob_clean();
    $id = $_POST['edit_item_id'] ?? ($_POST['item_id'] ?? '');
    $name = trim($_POST['item_name'] ?? '');
    $escapedName = $krfconn->real_escape_string($name);

    if (empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Item Name is required.']);
        exit;
    }

    // Check for duplicate item_name in master database
    $idFilter = (!empty($id) && (int)$id > 0) ? " AND item_id != '" . (int)$id . "'" : "";
    $dupCheck = @$krfconn->query("SELECT item_id, item_name, sku FROM inventory_purchase_item_master WHERE LOWER(item_name) = LOWER('$escapedName') $idFilter LIMIT 1");
    if ($dupCheck && $dupCheck instanceof mysqli_result && $dupCheck->num_rows > 0) {
        $dupItem = $dupCheck->fetch_assoc();
        $skuText = !empty($dupItem['sku']) ? " (SKU: {$dupItem['sku']})" : "";
        echo json_encode([
            'status' => 'error',
            'message' => "Duplicate Item Name: An item with the name '{$dupItem['item_name']}'{$skuText} already exists in the master catalog."
        ]);
        exit;
    }

    $sku = $_POST['sku'];
    $cat = $_POST['category_id'];
    $subcat = $_POST['subcategory_id'] ?? 0;
    $base_uom = $_POST['base_uom'] ?? '';
    $purchase_uom = $_POST['purchase_uom'] ?? '';
    $sale_uom = $_POST['sale_uom'] ?? '';
    $conversion_factor = !empty($_POST['conversion_factor']) ? (float)$_POST['conversion_factor'] : 1.0000;
    $hsn = $_POST['hsn_code'];
    $cgst = $_POST['cgst'] ?: 0;
    $sgst = $_POST['sgst'] ?: 0;
    $igst = $_POST['igst'] ?: 0;
    $min_stock = $_POST['min_stock'] ?: 0;
    $batch_required = !empty($_POST['batch_required']) ? 1 : 0;
    $expiry_required = !empty($_POST['expiry_required']) ? 1 : 0;
    $serial_required = !empty($_POST['serial_required']) ? 1 : 0;
    $desc = $_POST['description'];

    // Handle Photo Upload
    $photo_path = "";
    if (isset($_FILES['item_photo']) && $_FILES['item_photo']['error'] == 0) {
        $target_dir = "../uploads/items/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_ext = pathinfo($_FILES["item_photo"]["name"], PATHINFO_EXTENSION);
        $file_name = "item_" . time() . "_" . rand(1000, 9999) . "." . $file_ext;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["item_photo"]["tmp_name"], $target_file)) {
            $photo_path = "uploads/items/" . $file_name;
        }
    }

    if (empty($sku)) {
        $sku = "ITEM-" . strtoupper(substr($name, 0, 3)) . rand(100, 999);
    }

    if (!empty($id)) {
        $sql = "UPDATE inventory_purchase_item_master SET
                item_name=?, sku=?, category_id=?, subcategory_id=?,
                base_uom=?, purchase_uom=?, sale_uom=?, conversion_factor=?,
                hsn_code=?, cgst=?, sgst=?, igst=?, min_stock=?,
                batch_required=?, expiry_required=?, serial_required=?,
                description=?";

        $params = [$name, $sku, $cat, $subcat, $base_uom, $purchase_uom, $sale_uom, $conversion_factor, $hsn, $cgst, $sgst, $igst, $min_stock, $batch_required, $expiry_required, $serial_required, $desc];
        $types = "sssisssddsdddiiis";

        if (!empty($photo_path)) {
            $sql .= ", photo_path=?";
            $params[] = $photo_path;
            $types .= "s";
        }

        $sql .= " WHERE item_id=?";
        $params[] = $id;
        $types .= "i";

        $stmt = $krfconn->prepare($sql);
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt = $krfconn->prepare("INSERT INTO inventory_purchase_item_master
            (item_name, sku, category_id, subcategory_id, base_uom, purchase_uom, sale_uom, conversion_factor, hsn_code, cgst, sgst, igst, min_stock, batch_required, expiry_required, serial_required, description, photo_path)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssisssdsddddiiiss", $name, $sku, $cat, $subcat, $base_uom, $purchase_uom, $sale_uom, $conversion_factor, $hsn, $cgst, $sgst, $igst, $min_stock, $batch_required, $expiry_required, $serial_required, $desc, $photo_path);
    }

    if ($stmt->execute()) {
        $itemId = !empty($id) ? $id : $stmt->insert_id;

        // Fetch Category Name to check if it's Seed
        $catName = '';
        $catCheck = $krfconn->query("SELECT CategoryName FROM plant_category_master WHERE CategoryID = '$cat'");
        if ($catCheck && $cRow = $catCheck->fetch_assoc()) {
            $catName = strtolower(trim($cRow['CategoryName']));
        }

        // Synchronize with seed_master if category is Rootstock (RTSK11) or Seed
        if ($cat == 'RTSK11' || $catName == 'seed' || $catName == 'seeds') {
            // Check current link
            $checkLink = $krfconn->query("SELECT seed_id FROM inventory_purchase_item_master WHERE item_id = '$itemId'");
            $linkData = $checkLink->fetch_assoc();
            $sid = $linkData['seed_id'] ?? 0;

            if ($sid == 0) {
                // Find in seed_master by Category, SubCategory, and Item Name
                $escapedName = $krfconn->real_escape_string($name);
                $checkSeed = $krfconn->query("SELECT SeedID FROM seed_master WHERE CategoryID = '$cat' AND SubCategoryID = '$subcat' AND SeedName = '$escapedName' LIMIT 1");
                if ($checkSeed && $rowS = $checkSeed->fetch_assoc()) {
                    $sid = $rowS['SeedID'];
                } 
            }
            // Update link in item master
            $krfconn->query("UPDATE inventory_purchase_item_master SET seed_id = '$sid' WHERE item_id = '$itemId'");
        }

        // Save Unit Variations
        // Clear existing if updating
        if (!empty($id)) {
            $krfconn->query("DELETE FROM inventory_purchase_item_units WHERE item_id = '$id'");
        }

        if (isset($_POST['u_unit'])) {
            $units = $_POST['u_unit'];
            $sizes = $_POST['u_size'];
            $p_qtys = $_POST['u_pack_qty'];
            $p_prices = $_POST['u_pur_price'];
            $mrps = $_POST['u_mrp'];

            $stmtU = $krfconn->prepare("INSERT INTO inventory_purchase_item_units (item_id, unit, unit_size, pack_qty, purchase_price, mrp) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($units as $k => $uName) {
                if (empty($uName))
                    continue;
                $size = $sizes[$k];
                $p_qty = $p_qtys[$k] ?: 1.00;
                $p_price = $p_prices[$k] ?: 0;
                $m_price = $mrps[$k] ?: 0;
                $stmtU->bind_param("issddd", $itemId, $uName, $size, $p_qty, $p_price, $m_price);
                $stmtU->execute();
            }
        }

        echo json_encode(['status' => 'success', 'message' => 'Item and units saved successfully', 'new_id' => $itemId, 'new_name' => $name, 'sku' => $sku]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error: ' . $stmt->error]);
    }
    exit;
}

if ($action == 'toggle_item_status') {
    if (ob_get_length()) ob_clean();
    $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;
    $newStatus = (isset($_POST['status']) && in_array($_POST['status'], ['Active', 'Inactive'])) ? $_POST['status'] : 'Inactive';

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Item ID.']);
        exit;
    }

    $stmt = $krfconn->prepare("UPDATE inventory_purchase_item_master SET status = ? WHERE item_id = ?");
    $stmt->bind_param("si", $newStatus, $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => "Item status updated to {$newStatus}."]);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    exit;
}

if ($action == 'delete_item_master') {
    if (ob_get_length()) ob_clean();
    $id = isset($_POST['item_id']) ? (int)$_POST['item_id'] : 0;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid Item ID.']);
        exit;
    }

    // Check if used in Purchase Orders or Receipts
    $poCheck = @$krfconn->query("SELECT COUNT(*) as cnt FROM inventory_purchase_order_items WHERE seed_id = '$id'");
    $poCnt = ($poCheck && $poCheck instanceof mysqli_result) ? (int)$poCheck->fetch_assoc()['cnt'] : 0;

    $rcptCheck = @$krfconn->query("SELECT COUNT(*) as cnt FROM inventory_purchase_receipt_items WHERE seed_id = '$id'");
    $rcptCnt = ($rcptCheck && $rcptCheck instanceof mysqli_result) ? (int)$rcptCheck->fetch_assoc()['cnt'] : 0;

    $totalLinked = $poCnt + $rcptCnt;

    if ($totalLinked > 0) {
        echo json_encode([
            'status' => 'error', 
            'message' => "Cannot delete item. It is linked to {$totalLinked} purchase order / receipt record(s). You can deactivate it instead."
        ]);
        exit;
    }

    // Delete photo if exists
    $photoRes = @$krfconn->query("SELECT photo_path FROM inventory_purchase_item_master WHERE item_id = '$id'");
    if ($photoRes && $photoRes instanceof mysqli_result && $photoRes->num_rows > 0) {
        $pRow = $photoRes->fetch_assoc();
        if (!empty($pRow['photo_path']) && file_exists('../' . $pRow['photo_path'])) {
            @unlink('../' . $pRow['photo_path']);
        }
    }

    // Delete units and item record
    @$krfconn->query("DELETE FROM inventory_purchase_item_units WHERE item_id = '$id'");
    $stmt = $krfconn->prepare("DELETE FROM inventory_purchase_item_master WHERE item_id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Item deleted permanently.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => $stmt->error]);
    }
    exit;
}

// --- TRANSFER LOGIC ---

if ($action == 'list_transfers') {
    include_once "transfer_functions.php";
    $status = $_POST['status'] ?? '';
    $search = $_POST['search'] ?? '';
    $location_id = $_POST['location_id'] ?? 'all';

    $reqs = getTransferRequests($krfconn, $status, $location_id);

    $totalCount = count($reqs);
    $pendingCount = 0;
    $dispatchedCount = 0;
    $completedCount = 0;

    $html = "";
    if (count($reqs) > 0) {
        foreach ($reqs as $row) {
            $st = $row['status'];
            if ($st === 'Completed') $completedCount++;
            else if ($st === 'Dispatched') $dispatchedCount++;
            else $pendingCount++;

            // Filter search if provided
            if (!empty($search)) {
                $searchTerm = strtolower($search);
                if (strpos(strtolower($row['request_number']), $searchTerm) === false &&
                    strpos(strtolower($row['FromName']), $searchTerm) === false &&
                    strpos(strtolower($row['ToName']), $searchTerm) === false) {
                    continue;
                }
            }

            $badgeClass = match ($st) {
                'Completed' => 'bg-success-subtle text-success border border-success-subtle',
                'Dispatched' => 'bg-info-subtle text-info border border-info-subtle',
                'Pending' => 'bg-warning-subtle text-warning-emphasis border border-warning-subtle',
                default => 'bg-secondary-subtle text-secondary border border-secondary-subtle'
            };

            $displayQty = ($st === 'Pending') ? (float)$row['total_req_qty'] : ((float)$row['total_app_qty'] > 0 ? (float)$row['total_app_qty'] : (float)$row['total_req_qty']);
            $itemCount = (int)($row['total_items'] ?? 0);
            $itemText = $itemCount == 1 ? "1 Item" : "{$itemCount} Items";
            $qtyHtml = "<span class='fw-bold text-dark fs-6'>" . number_format($displayQty, 2) . "</span> <small class='text-muted super-small'>({$itemText})</small>";

            $reqNumHtml = "<span class='fw-bold text-dark font-monospace'><i class='fas fa-truck-moving text-primary me-1 opacity-75'></i>{$row['request_number']}</span>";
            $routeHtml = "<span class='fw-semibold text-secondary'>{$row['FromName']} <i class='fas fa-arrow-right text-primary mx-1'></i> {$row['ToName']}</span>";
            $dateHtml = "<span class='text-muted super-small'><i class='far fa-calendar-alt me-1 text-info'></i>{$row['request_date']}</span>";
            $statusBadge = "<span class='badge {$badgeClass} px-3 py-1 rounded-pill fw-bold'>{$st}</span>";

            $actionBtns = "<button class='btn btn-sm btn-outline-primary me-1 rounded-2 fw-semibold' onclick='viewTransfer({$row['request_id']})' title='View Details'><i class='fas fa-eye me-1'></i>View</button>";

            if ($st == 'Pending') {
                $actionBtns .= "<button class='btn btn-sm btn-outline-warning me-1 rounded-2 fw-semibold' onclick='approveTransfer({$row['request_id']})' title='Dispatch Stock'><i class='fas fa-truck-loading me-1'></i>Dispatch</button>";
            } else if ($st == 'Dispatched') {
                $actionBtns .= "<button class='btn btn-sm btn-outline-success rounded-2 fw-semibold' onclick='receiveTransfer({$row['request_id']})' title='Receive Stock'><i class='fas fa-box-open me-1'></i>Receive</button>";
            }

            $html .= "<tr>
                 <td>{$reqNumHtml}</td>
                 <td>{$routeHtml}</td>
                 <td>{$qtyHtml}</td>
                 <td>{$dateHtml}</td>
                 <td>{$statusBadge}</td>
                 <td>{$actionBtns}</td>
             </tr>";
        }
    }

    if (empty($html)) {
        $html = "<tr>
            <td colspan='6' class='text-center py-5 text-muted'>
                <div class='empty-state-box'>
                    <i class='fas fa-exchange-alt fs-1 d-block mb-3 opacity-25 text-primary'></i>
                    <h6 class='fw-bold text-secondary mb-1'>No Transfer Requests Found</h6>
                    <p class='small text-muted mb-0'>Try adjusting search query or status filter.</p>
                </div>
            </td>
        </tr>";
    }

    echo json_encode([
        'rows' => $html,
        'stats' => [
            'total' => $totalCount,
            'pending' => $pendingCount,
            'dispatched' => $dispatchedCount,
            'completed' => $completedCount
        ]
    ]);
    exit;
}

if ($action == 'list_location_inventory_and_transactions') {
    include_once "transfer_functions.php";
    include_once "po_functions_v2.php";

    $location_id = $_POST['location_id'] ?? 'all';
    $search = trim($_POST['search'] ?? '');
    $trans_type_filter = $_POST['trans_type_filter'] ?? 'all'; // 'all', 'in', 'out'

    $locationMap = [];
    $hrmDb = (isset($GLOBALS['hrmconn']) && ($GLOBALS['hrmconn'] instanceof mysqli)) ? $GLOBALS['hrmconn'] : null;
    if ($hrmDb) {
        $locRes = @$hrmDb->query("SELECT sno, LocationName FROM hrm_AttendanceLocationMaster");
        if ($locRes) {
            while ($l = $locRes->fetch_assoc()) {
                $locationMap[$l['sno']] = $l['LocationName'];
            }
        }
    }

    $isAll = ($location_id === 'all' || empty($location_id) || $location_id === '0');
    $locId = $isAll ? 0 : intval($location_id);

    // 1. Stock Inventory Rows
    $stockRows = "";
    $totalStockQty = 0;
    $totalStockItemsCount = 0;

    if ($isAll) {
        // Aggregate by item across all locations
        $items = getInventoryAggregatedByItem($krfconn, null);
        if (!empty($search)) {
            $st = strtolower($search);
            $items = array_filter($items, function($it) use ($st) {
                return (strpos(strtolower($it['SeedName']), $st) !== false ||
                        strpos(strtolower($it['sku']), $st) !== false ||
                        strpos(strtolower($it['category_name'] ?? ''), $st) !== false);
            });
        }
        $totalStockItemsCount = count($items);
        foreach ($items as $it) {
            $stockVal = (float)($it['stock_quantity'] ?? $it['current_stock'] ?? $it['quantity'] ?? 0);
            $totalStockQty += $stockVal;

            $locBadges = "";
            $locList = $it['locations'] ?? $it['locations_breakdown'] ?? [];
            if (!empty($locList) && is_array($locList)) {
                foreach ($locList as $lb) {
                    $qtyVal = (float)($lb['quantity'] ?? $lb['stock_quantity'] ?? 0);
                    $locBadges .= "<span class='badge bg-light text-dark border me-1 mb-1' style='font-size:0.75rem;'><i class='fas fa-map-marker-alt text-primary me-1'></i>" . htmlspecialchars($lb['location_name']) . ": <strong class='stock-num'>" . number_format($qtyVal, 2) . "</strong></span> ";
                }
            } else {
                $locBadges = "<span class='badge badge-all-locs'><i class='fas fa-globe me-1'></i>All Warehouses</span>";
            }

            $uom = htmlspecialchars($it['base_uom'] ?? $it['unit'] ?? 'Unit');
            $skuBadge = !empty($it['sku']) ? "<span class='stock-sku'>".htmlspecialchars($it['sku'])."</span>" : "<span class='text-muted small'>-</span>";

            $stockRows .= "<tr>
                <td class='ps-3'>{$skuBadge}</td>
                <td>
                    <div class='stock-item-name'>" . htmlspecialchars($it['SeedName']) . "</div>
                    <small class='text-muted'>" . htmlspecialchars($it['category_name'] ?? 'General') . "</small>
                </td>
                <td>{$locBadges}</td>
                <td class='text-end'><span class='stock-num stock-num-success fs-6'>" . number_format($stockVal, 2) . "</span> <small class='text-muted'>{$uom}</small></td>
                <td class='text-center'>
                    <button class='btn btn-view-details' onclick='openItemStockDetailModal(" . intval($it['seed_id']) . ", \"" . htmlspecialchars(addslashes($it['SeedName']), ENT_QUOTES) . "\")'>
                        <i class='fas fa-chart-pie me-1'></i> Breakdown
                    </button>
                </td>
            </tr>";
        }
    } else {
        // Specific location stock
        $whereSearch = "";
        if (!empty($search)) {
            $sEsc = $krfconn->real_escape_string($search);
            $whereSearch = " AND (s_item.item_name LIKE '%$sEsc%' OR s_seed.item_name LIKE '%$sEsc%' OR sm.SeedName LIKE '%$sEsc%' OR s_item.sku LIKE '%$sEsc%' OR s_seed.sku LIKE '%$sEsc%')";
        }
        $sqlStock = "SELECT 
            isl.seed_id,
            isl.location_id,
            isl.quantity as stock_qty,
            isl.last_updated,
            COALESCE(s_item.item_name, s_seed.item_name, sm.SeedName, CONCAT('Item #', isl.seed_id)) as item_name,
            COALESCE(s_item.sku, s_seed.sku, sm.sku, '') as sku,
            COALESCE(s_item.category_id, s_seed.category_id, 'General') as category,
            COALESCE(s_item.base_uom, s_item.unit, s_seed.unit, 'Unit') as unit
        FROM inventory_stock_location isl
        LEFT JOIN inventory_purchase_item_master s_item ON (isl.seed_id = s_item.item_id)
        LEFT JOIN inventory_purchase_item_master s_seed ON (isl.seed_id = s_seed.seed_id AND isl.seed_id > 0 AND s_item.item_id IS NULL)
        LEFT JOIN seed_master sm ON (isl.seed_id = sm.SeedID AND s_item.item_id IS NULL AND s_seed.item_id IS NULL)
        WHERE isl.location_id = $locId $whereSearch
        ORDER BY item_name ASC";

        $resStock = $krfconn->query($sqlStock);
        if ($resStock && $resStock->num_rows > 0) {
            $totalStockItemsCount = $resStock->num_rows;
            while ($r = $resStock->fetch_assoc()) {
                $stockVal = (float)$r['stock_qty'];
                $totalStockQty += $stockVal;
                $uom = htmlspecialchars($r['unit'] ?: 'Unit');
                $skuBadge = !empty($r['sku']) ? "<span class='stock-sku'>".htmlspecialchars($r['sku'])."</span>" : "<span class='text-muted small'>-</span>";
                $locName = $locationMap[$r['location_id']] ?? ('Location #' . $r['location_id']);

                $stockRows .= "<tr>
                    <td class='ps-3'>{$skuBadge}</td>
                    <td>
                        <div class='stock-item-name'>" . htmlspecialchars($r['item_name']) . "</div>
                        <small class='text-muted'>" . htmlspecialchars($r['category']) . "</small>
                    </td>
                    <td><span class='badge badge-single-loc'><i class='fas fa-map-marker-alt me-1'></i>" . htmlspecialchars($locName) . "</span></td>
                    <td class='text-end'><span class='stock-num stock-num-success fs-6'>" . number_format($stockVal, 2) . "</span> <small class='text-muted'>{$uom}</small></td>
                    <td class='text-center'>
                        <button class='btn btn-view-details' onclick='openItemStockDetailModal(" . intval($r['seed_id']) . ", \"" . htmlspecialchars(addslashes($r['item_name']), ENT_QUOTES) . "\")'>
                            <i class='fas fa-history me-1'></i> History
                        </button>
                    </td>
                </tr>";
            }
        }
    }

    if (empty($stockRows)) {
        $stockRows = "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='fas fa-boxes fa-2x mb-2 d-block opacity-25'></i>No stock items found for the selected location filter.</td></tr>";
    }

    // 2. In & Out Transactions Ledger
    $whereTx = " WHERE 1=1";
    if (!$isAll) {
        $whereTx .= " AND t.location_id = $locId";
    }
    if (!empty($search)) {
        $sEsc = $krfconn->real_escape_string($search);
        $whereTx .= " AND (s_item.item_name LIKE '%$sEsc%' OR s_seed.item_name LIKE '%$sEsc%' OR sm.SeedName LIKE '%$sEsc%' OR s_item.sku LIKE '%$sEsc%' OR s_seed.sku LIKE '%$sEsc%' OR t.notes LIKE '%$sEsc%' OR t.reference_id LIKE '%$sEsc%')";
    }
    if ($trans_type_filter === 'in') {
        $whereTx .= " AND t.qty_change > 0";
    } else if ($trans_type_filter === 'out') {
        $whereTx .= " AND t.qty_change < 0";
    }

    $sqlTx = "SELECT 
        t.log_id,
        t.seed_id,
        t.location_id,
        t.qty_change,
        t.trans_type,
        t.reference_type,
        t.reference_id,
        t.notes,
        t.created_at,
        COALESCE(s_item.item_name, s_seed.item_name, sm.SeedName, CONCAT('Item #', t.seed_id)) as item_name,
        COALESCE(s_item.sku, s_seed.sku, sm.sku, '') as sku,
        COALESCE(s_item.base_uom, s_item.unit, s_seed.unit, 'Unit') as unit
    FROM inventory_transactions_log t
    LEFT JOIN inventory_purchase_item_master s_item ON (t.seed_id = s_item.item_id)
    LEFT JOIN inventory_purchase_item_master s_seed ON (t.seed_id = s_seed.seed_id AND t.seed_id > 0 AND s_item.item_id IS NULL)
    LEFT JOIN seed_master sm ON (t.seed_id = sm.SeedID AND s_item.item_id IS NULL AND s_seed.item_id IS NULL)
    $whereTx
    ORDER BY t.created_at DESC, t.log_id DESC
    LIMIT 200";

    $resTx = $krfconn->query($sqlTx);
    $txRows = "";
    $totalInQty = 0;
    $totalOutQty = 0;
    $totalInCount = 0;
    $totalOutCount = 0;

    if ($resTx && $resTx->num_rows > 0) {
        while ($t = $resTx->fetch_assoc()) {
            $qty = (float)$t['qty_change'];
            $isIn = ($qty > 0);
            if ($isIn) {
                $totalInQty += $qty;
                $totalInCount++;
            } else {
                $totalOutQty += abs($qty);
                $totalOutCount++;
            }

            $dateFormatted = date('d-M-Y H:i', strtotime($t['created_at']));
            $locName = $locationMap[$t['location_id']] ?? ('Location #' . $t['location_id']);
            $skuBadge = !empty($t['sku']) ? "<span class='stock-sku'>".htmlspecialchars($t['sku'])."</span>" : "<span class='text-muted small'>-</span>";
            $uom = htmlspecialchars($t['unit'] ?: 'Unit');

            $rawType = strtoupper(trim($t['trans_type'] ?? ''));
            if (empty($rawType)) {
                $rawType = strtoupper(trim($t['reference_type'] ?? ''));
            }

            if ($isIn) {
                $typeBadge = "<span class='badge bg-success-subtle text-success border border-success-subtle px-2 py-1 fw-bold'><i class='fas fa-arrow-down me-1'></i>" . ($rawType ?: 'IN') . "</span>";
                $inHtml = "<span class='stock-num stock-num-success fw-bold'>+" . number_format($qty, 2) . "</span> <small class='text-muted'>{$uom}</small>";
                $outHtml = "<span class='text-muted'>-</span>";
            } else {
                $typeBadge = "<span class='badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1 fw-bold'><i class='fas fa-arrow-up me-1'></i>" . ($rawType ?: 'OUT') . "</span>";
                $inHtml = "<span class='text-muted'>-</span>";
                $outHtml = "<span class='stock-num text-danger fw-bold'>-" . number_format(abs($qty), 2) . "</span> <small class='text-muted'>{$uom}</small>";
            }

            $refId = !empty($t['reference_id']) ? htmlspecialchars($t['reference_id']) : '-';
            $notes = !empty($t['notes']) ? htmlspecialchars($t['notes']) : '-';

            $txRows .= "<tr>
                <td class='ps-3 text-nowrap'><span class='text-muted small'><i class='far fa-clock me-1 text-primary'></i>{$dateFormatted}</span></td>
                <td>
                    <div class='d-flex align-items-center'>
                        <span class='me-2'>{$skuBadge}</span>
                        <div>
                            <span class='stock-item-name'>" . htmlspecialchars($t['item_name']) . "</span>
                        </div>
                    </div>
                </td>
                <td><span class='badge badge-single-loc'><i class='fas fa-map-marker-alt me-1'></i>" . htmlspecialchars($locName) . "</span></td>
                <td>{$typeBadge}</td>
                <td class='text-end'>{$inHtml}</td>
                <td class='text-end'>{$outHtml}</td>
                <td><span class='font-monospace small text-dark fw-semibold'>{$refId}</span></td>
                <td class='pe-3 small text-muted text-break'>{$notes}</td>
            </tr>";
        }
    }

    if (empty($txRows)) {
        $txRows = "<tr><td colspan='8' class='text-center py-5 text-muted'><i class='fas fa-exchange-alt fa-2x mb-2 d-block opacity-25'></i>No transactions found matching your filter.</td></tr>";
    }

    // 3. Transfers count
    $transfers = getTransferRequests($krfconn, '', $isAll ? null : $locId);
    $pendingTransfers = 0;
    $dispatchedTransfers = 0;
    $completedTransfers = 0;
    foreach ($transfers as $tr) {
        if ($tr['status'] === 'Pending') $pendingTransfers++;
        else if ($tr['status'] === 'Dispatched') $dispatchedTransfers++;
        else if ($tr['status'] === 'Completed') $completedTransfers++;
    }

    echo json_encode([
        'status' => 'success',
        'stock_rows' => $stockRows,
        'tx_rows' => $txRows,
        'stats' => [
            'total_items_count' => $totalStockItemsCount,
            'total_stock_qty' => number_format($totalStockQty, 2),
            'total_in_qty' => number_format($totalInQty, 2),
            'total_out_qty' => number_format($totalOutQty, 2),
            'total_in_count' => $totalInCount,
            'total_out_count' => $totalOutCount,
            'pending_transfers' => $pendingTransfers,
            'dispatched_transfers' => $dispatchedTransfers,
            'completed_transfers' => $completedTransfers
        ]
    ]);
    exit;
}

if ($action == 'save_transfer_request') {
    include_once "transfer_functions.php";

    $data = [
        'from_location' => $_POST['from_location'],
        'to_location' => $_POST['to_location'],
        'remarks' => $_POST['remarks'],
        'items' => json_decode($_POST['items'], true)
    ];

    $res = createTransferRequest($krfconn, $data);
    echo json_encode($res);
    exit;
}

if ($action == 'get_transfer_details') {
    include_once "transfer_functions.php";
    $id = $_POST['id'];
    $data = getTransferRequestDetails($krfconn, $id);

    if ($data) {
        $itemsHtml = "";
        foreach ($data['items'] as $item) {
            $baseUomText = $item['base_uom'] ?: 'Unit';
            $uomBadge = "<span class='badge bg-primary-subtle text-primary border me-1 super-small'><i class='fas fa-ruler me-1'></i>Base: {$baseUomText}</span>";
            
            $trackingBadges = "";
            if (!empty($item['batch_required'])) {
                $trackingBadges .= "<span class='badge bg-info-subtle text-info border me-1 super-small'><i class='fas fa-barcode me-1'></i>Batch Req</span>";
            }
            if (!empty($item['expiry_required'])) {
                $trackingBadges .= "<span class='badge bg-danger-subtle text-danger border me-1 super-small'><i class='fas fa-calendar-times me-1'></i>Expiry Req</span>";
            }
            if (!empty($item['serial_required'])) {
                $trackingBadges .= "<span class='badge bg-secondary-subtle text-dark border me-1 super-small'><i class='fas fa-hashtag me-1'></i>Serial Req</span>";
            }

            $availStock = (float)($item['SourceStock'] ?? 0);
            $stockBadgeClass = ($availStock >= $item['quantity_requested']) ? 'success' : 'danger';
            $stockBadge = "<span class='badge bg-{$stockBadgeClass}-subtle text-{$stockBadgeClass} border border-{$stockBadgeClass}-subtle px-2 py-1'><i class='fas fa-boxes me-1'></i>Avail: {$availStock} {$baseUomText}</span>";

            $itemsHtml .= "<tr>
                 <td>
                    <div class='fw-bold text-dark'>{$item['SeedName']} <span class='text-muted small'>({$item['sku']})</span></div>
                    <div class='mt-1 d-flex flex-wrap gap-1 align-items-center'>
                        {$uomBadge}
                        {$trackingBadges}
                    </div>
                 </td>
                 <td><span class='fw-bold fs-6 text-dark'>{$item['quantity_requested']}</span> <small class='text-muted'>{$baseUomText}</small></td>
                 <td><span class='fw-bold fs-6 text-primary'>{$item['quantity_approved']}</span> <small class='text-muted'>{$baseUomText}</small></td>
                 <td>{$stockBadge}</td>
             </tr>";
        }
        $data['items_html'] = $itemsHtml;
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Not Found']);
    }
    exit;
}

if ($action == 'approve_transfer') {
    include_once "transfer_functions.php";
    $id = $_POST['request_id'];
    $itemsRaw = json_decode($_POST['items'], true); // { item_id: qty }

    // Map JSON object to array required by function
    $approvedItems = [];
    foreach ($itemsRaw as $itemId => $qty) {
        $approvedItems[] = ['item_id' => $itemId, 'approved_qty' => $qty];
    }

    $res = approveTransferRequest($krfconn, $id, $approvedItems);
    echo json_encode($res);
    exit;
}

if ($action == 'receive_transfer') {
    include_once "transfer_functions.php";
    $id = $_POST['request_id'];
    $res = receiveTransferRequest($krfconn, $id);
    echo json_encode($res);
    exit;
}

if ($action == 'get_stock') {
    $seedId = intval($_POST['seed_id'] ?? 0);
    $locationId = intval($_POST['location_id'] ?? 0);
    $stock = 0;

    if ($krfconn && $seedId > 0 && $locationId > 0) {
        $allIds = [$seedId];
        $imQ = $krfconn->query("SELECT item_id, seed_id FROM inventory_purchase_item_master WHERE item_id = $seedId OR seed_id = '$seedId'");
        if ($imQ) {
            while ($imR = $imQ->fetch_assoc()) {
                if (!empty($imR['item_id'])) $allIds[] = intval($imR['item_id']);
                if (!empty($imR['seed_id'])) $allIds[] = intval($imR['seed_id']);
            }
        }
        $allIds = array_unique(array_filter($allIds));
        $idStr = implode(',', $allIds);

        $res = $krfconn->query("SELECT SUM(quantity) as total_qty FROM inventory_stock_location WHERE seed_id IN ($idStr) AND location_id = $locationId");
        if ($res && $row = $res->fetch_assoc()) {
            $stock = (float)($row['total_qty'] ?? 0);
        }
    }

    echo json_encode(['status' => 'success', 'stock' => $stock]);
    exit;
}

// --- WAREHOUSE MASTER LOGIC REMOVED ---

if ($action == 'save_supplier') {
    $paytoname = trim($_POST['paytoname'] ?? '');
    $mobileno = trim($_POST['mobileno'] ?? '');
    $emailaddress = trim($_POST['emailaddress'] ?? '');
    $gstno = trim($_POST['gstno'] ?? '');
    $panno = trim($_POST['panno'] ?? '');
    $supplieraddress = trim($_POST['supplieraddress'] ?? '');
    $bankname = trim($_POST['bankname'] ?? '');
    $accountno = trim($_POST['accountno'] ?? '');
    $ifsccode = trim($_POST['ifsccode'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    if (empty($paytoname)) {
        echo json_encode(['status' => 'error', 'message' => 'Supplier name is required.']);
        exit;
    }

    // --- STEP 1: Duplicate Check ---
    $dupCheck = $conn->prepare("SELECT sno FROM supplier_authorizedperson WHERE paytoname = ? LIMIT 1");
    $dupCheck->bind_param("s", $paytoname);
    $dupCheck->execute();
    $dupCheck->store_result();
    if ($dupCheck->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => "Supplier '{$paytoname}' already exists in the system."]);
        exit;
    }
    $dupCheck->close();

    // Also check tbl_payto_master
    $dupCheck2 = $conn->prepare("SELECT sno, paytoid FROM tbl_payto_master WHERE paytoname = ? LIMIT 1");
    $dupCheck2->bind_param("s", $paytoname);
    $dupCheck2->execute();
    $dupRes2 = $dupCheck2->get_result()->fetch_assoc();
    $dupCheck2->close();

    $conn->begin_transaction();
    try {
        if ($dupRes2) {
            // Already in payto_master — reuse existing paytoid
            $paytoid = $dupRes2['paytoid'] ?: $dupRes2['sno'];
        } else {
            // --- STEP 2: Insert into tbl_payto_master ---
            $stmtPayto = $conn->prepare(
                "INSERT INTO tbl_payto_master (paytoname, bankname, accountno, ifsccode, mobileno, emailaddress, address, taxid)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if (!$stmtPayto)
                throw new Exception("Payto prepare failed: " . $conn->error);

            $stmtPayto->bind_param(
                "ssssssss",
                $paytoname,
                $bankname,
                $accountno,
                $ifsccode,
                $mobileno,
                $emailaddress,
                $supplieraddress,
                $panno
            );
            if (!$stmtPayto->execute())
                throw new Exception("Payto insert failed: " . $stmtPayto->error);

            $paytoMasterSno = $stmtPayto->insert_id;

            // Set paytoid = sno (self-referencing ID pattern common in this schema)
            $conn->query("UPDATE tbl_payto_master SET paytoid = {$paytoMasterSno} WHERE sno = {$paytoMasterSno}");
            $paytoid = $paytoMasterSno;
            $stmtPayto->close();
        }

        // --- STEP 3: Insert into supplier_authorizedperson with paytoid ---
        $stmtSup = $conn->prepare(
            "INSERT INTO supplier_authorizedperson
                (paytoid, paytoname, mobileno, emailaddress, gstno, panno, supplieraddress, bankname, accountno, ifsccode, remark, edatetime)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        if (!$stmtSup)
            throw new Exception("Supplier prepare failed: " . $conn->error);

        $stmtSup->bind_param(
            "issssssssss",
            $paytoid,
            $paytoname,
            $mobileno,
            $emailaddress,
            $gstno,
            $panno,
            $supplieraddress,
            $bankname,
            $accountno,
            $ifsccode,
            $remark
        );
        if (!$stmtSup->execute())
            throw new Exception("Supplier insert failed: " . $stmtSup->error);

        $newSupSno = $stmtSup->insert_id;
        $stmtSup->close();

        $conn->commit();
        echo json_encode([
            'status' => 'success',
            'message' => "Supplier '{$paytoname}' saved successfully.",
            'new_id' => $newSupSno,
            'paytoid' => $paytoid
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action == 'get_stock_details') {
    $seed_id = intval($_POST['seed_id'] ?? 0);
    $location_id = intval($_POST['location_id'] ?? 0);

    $sql = "SELECT created_at as transaction_date, trans_type as transaction_type, qty_change as quantity, reference_id, reference_type, notes 
            FROM inventory_transactions_log 
            WHERE seed_id = $seed_id 
            AND location_id = $location_id
            ORDER BY created_at DESC";
    $res = $krfconn->query($sql);
    $rows = "";
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $type = !empty($row['transaction_type']) ? $row['transaction_type'] : ($row['reference_type'] ?? 'TRANS');
            $qty = (float)$row['quantity'];
            $isPurchase = ($qty > 0);
            $p = $isPurchase ? number_format($qty, 2) : '-';
            $u = !$isPurchase ? number_format(abs($qty), 2) : '-';
            $refNote = !empty($row['notes']) ? $row['notes'] : (!empty($row['reference_id']) ? "#{$row['reference_id']}" : '-');
            $rows .= "<tr>
                <td class='ps-3 py-2'>" . date('d-M-Y H:i', strtotime($row['transaction_date'])) . "</td>
                <td class='py-2'><span class='badge " . ($isPurchase ? 'bg-success text-white' : 'bg-secondary text-white') . "'>" . htmlspecialchars($type) . "</span></td>
                <td class='py-2 text-end fw-bold text-success'>" . $p . "</td>
                <td class='py-2 text-end fw-bold text-danger'>" . $u . "</td>
                <td class='pe-3 py-2 text-end small text-muted'>" . htmlspecialchars($refNote) . "</td>
            </tr>";
        }
    }
    if (empty($rows)) {
        $rows = "<tr><td colspan='5' class='text-center py-5 text-muted'><i class='fas fa-receipt fa-2x mb-2 d-block opacity-25'></i>No transaction history found for this item.</td></tr>";
    }
    echo json_encode(['rows' => $rows]);
    exit;
}
?>