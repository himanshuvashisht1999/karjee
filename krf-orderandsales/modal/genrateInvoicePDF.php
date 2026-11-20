<?php
// ⚡ Start output buffering FIRST — prevents any whitespace or header conflicts before TCPDF
ob_start();

error_reporting(E_ALL);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

require('../../connection.php');
if (isset($krfconn) && $krfconn) {
    @$krfconn->set_charset("utf8mb4");
}
require('../../loginCell/functions/login_functions.php');
include("../functions/client_functions.php");
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
require_once('../functions/pdf_generation_functions.php');

$companyid = intval($_GET['companyid'] ?? 0);
$subcompanyid = intval($_GET['subcompanyid'] ?? 0);
$projectid = intval($_GET['projectid'] ?? 0);
$invoiceno = trim($_GET['invno'] ?? $_GET['invoiceno'] ?? '');
$mode = $_GET['mode'] ?? 'I';

if (empty($invoiceno)) {
    ob_end_clean();
    http_response_code(400);
    die('Error: Invoice Number is required.');
}

downloadInvoicePDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $invoiceno, $mode);

function downloadInvoicePDF($conn, $authconn, $krfconn, $companyid, $subcompanyid, $projectid, $invoiceno, $mode = 'I') {
    // 0. Check sales_invoice first (Authoritative master for all dispatch invoices KF/...)
    $salesInv = null;
    $chkSi = $krfconn->prepare("
        SELECT * FROM sales_invoice 
        WHERE (invoiceno = ? AND ? != '')
           OR (sono = ? AND ? != '')
           OR (psono = ? AND ? != '')
        ORDER BY CASE WHEN invoiceno = ? THEN 1 ELSE 2 END, id DESC 
        LIMIT 1
    ");
    if ($chkSi) {
        $chkSi->bind_param("sssssss", $invoiceno, $invoiceno, $invoiceno, $invoiceno, $invoiceno, $invoiceno, $invoiceno);
        $chkSi->execute();
        $salesInv = $chkSi->get_result()->fetch_assoc();
        $chkSi->close();
    }

    // 1. Fetch Order / Invoice Details
    $order = null;
    $soStmt = $krfconn->prepare("
        SELECT * 
        FROM krf_sales_order 
        WHERE (dinvoiceno = ? AND ? != '') 
           OR (sono = ? AND ? != '') 
           OR (psono = ? AND ? != '')
        ORDER BY id DESC
        LIMIT 1
    ");
    if ($soStmt) {
        $soStmt->bind_param("ssssss", $invoiceno, $invoiceno, $invoiceno, $invoiceno, $invoiceno, $invoiceno);
        $soStmt->execute();
        $order = $soStmt->get_result()->fetch_assoc();
        $soStmt->close();
    }

    if (!$order) {
        $psoStmt = $krfconn->prepare("
            SELECT * 
            FROM krf_provisional_sales_order 
            WHERE psono = ? AND ? != ''
            ORDER BY id DESC
            LIMIT 1
        ");
        if ($psoStmt) {
            $psoStmt->bind_param("ss", $invoiceno, $invoiceno);
            $psoStmt->execute();
            $order = $psoStmt->get_result()->fetch_assoc();
            $psoStmt->close();
        }
    }

    // Fallback: Check krf_sales_order_items for invoiceno, sono, or psono
    if (!$order) {
        $soiLookup = $krfconn->prepare("
            SELECT soi.sono, soi.psono, soi.projectid, soi.clientid
            FROM krf_sales_order_items soi
            WHERE (soi.invoiceno = ? AND ? != '') 
               OR (soi.sono = ? AND ? != '') 
               OR (soi.psono = ? AND ? != '')
            ORDER BY soi.id DESC
            LIMIT 1
        ");
        if ($soiLookup) {
            $soiLookup->bind_param("ssssss", $invoiceno, $invoiceno, $invoiceno, $invoiceno, $invoiceno, $invoiceno);
            $soiLookup->execute();
            $soiFound = $soiLookup->get_result()->fetch_assoc();
            $soiLookup->close();
            if ($soiFound) {
                $fSono = trim($soiFound['sono'] ?? '');
                $fPsono = trim($soiFound['psono'] ?? '');
                $soStmt2 = $krfconn->prepare("SELECT * FROM krf_sales_order WHERE (sono = ? AND ? != '') OR (psono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
                if ($soStmt2) {
                    $soStmt2->bind_param("ssss", $fSono, $fSono, $fPsono, $fPsono);
                    $soStmt2->execute();
                    $order = $soStmt2->get_result()->fetch_assoc();
                    $soStmt2->close();
                }
                if (!$order && !empty($fPsono)) {
                    $psoStmt2 = $krfconn->prepare("SELECT * FROM krf_provisional_sales_order WHERE psono = ? ORDER BY id DESC LIMIT 1");
                    if ($psoStmt2) {
                        $psoStmt2->bind_param("s", $fPsono);
                        $psoStmt2->execute();
                        $order = $psoStmt2->get_result()->fetch_assoc();
                        $psoStmt2->close();
                    }
                }
            }
        }
    }

    $psono = trim($salesInv['psono'] ?? $order['psono'] ?? '');
    $sono = trim($salesInv['sono'] ?? $order['sono'] ?? '');
    if (empty($companyid) && !empty($salesInv['companyid'])) $companyid = intval($salesInv['companyid']);
    elseif (empty($companyid) && !empty($order['companyid'])) $companyid = intval($order['companyid']);
    if (empty($subcompanyid) && !empty($salesInv['subcompanyid'])) $subcompanyid = intval($salesInv['subcompanyid']);
    elseif (empty($subcompanyid) && !empty($order['subcompanyid'])) $subcompanyid = intval($order['subcompanyid']);
    if (empty($projectid) && !empty($salesInv['projectid'])) $projectid = intval($salesInv['projectid']);
    elseif (empty($projectid) && !empty($order['projectid'])) $projectid = intval($order['projectid']);

    // 2. Resolve Client Details
    $customerName = '';
    $invoicingAddress = '';
    $phones = '';
    $emailAddress = '';
    $effClientId = $salesInv['clientid'] ?? $order['clientid'] ?? '';

    if (!empty($effClientId)) {
        $cStmt = $krfconn->prepare("SELECT * FROM krf_client_master WHERE client_id = ? LIMIT 1");
        if ($cStmt) {
            $cStmt->bind_param("s", $effClientId);
            $cStmt->execute();
            $cRes = $cStmt->get_result()->fetch_assoc();
            $cStmt->close();
            if ($cRes) {
                $customerName = $cRes['client_name'] ?? '';
                $invoicingAddress = $cRes['invoicing_address'] ?? '';
                if (empty($invoicingAddress)) {
                    $parts = array_filter([$cRes['cityname'] ?? '', $cRes['districtname'] ?? '', $cRes['statename'] ?? '']);
                    $invoicingAddress = implode(', ', $parts);
                }
                $phones = $cRes['phones'] ?? '';
                $emailAddress = $cRes['client_email'] ?? '';
            }
        }
    }

    if (empty($customerName)) {
        $customerName = $salesInv['clientname'] ?? $order['clientname'] ?? 'Customer';
        $invoicingAddress = $salesInv['invoicing_address'] ?? $order['invoicing_address'] ?? $order['delivery_address'] ?? $order['address'] ?? '';
        $phones = $salesInv['mobileno'] ?? $order['mobileno'] ?? '';
        $emailAddress = $salesInv['emailid'] ?? $order['emailid'] ?? '';
    }

    // 2b. Resolve active target dispatch invoice number if one exists
    $targetInvoiceNo = '';
    $targetInvoiceDate = '';

    if ($salesInv && !empty($salesInv['invoiceno'])) {
        $targetInvoiceNo = trim($salesInv['invoiceno']);
        $targetInvoiceDate = !empty($salesInv['invoicedate']) ? date('Y-m-d', strtotime($salesInv['invoicedate'])) : '';
    }

    // Check if passed $invoiceno directly matches a dinvoiceno in krf_sales_order
    if (empty($targetInvoiceNo)) {
        $chkDinv = $krfconn->prepare("SELECT dinvoiceno, invoicedate, updated_at FROM krf_sales_order WHERE dinvoiceno = ? AND ? != '' LIMIT 1");
        if ($chkDinv) {
            $chkDinv->bind_param("ss", $invoiceno, $invoiceno);
            $chkDinv->execute();
            $dinvR = $chkDinv->get_result()->fetch_assoc();
            $chkDinv->close();
            if ($dinvR && !empty($dinvR['dinvoiceno'])) {
                $targetInvoiceNo = trim($dinvR['dinvoiceno']);
                $targetInvoiceDate = !empty($dinvR['invoicedate']) ? date('Y-m-d', strtotime($dinvR['invoicedate'])) : (!empty($dinvR['updated_at']) ? date('Y-m-d', strtotime($dinvR['updated_at'])) : '');
            }
        }
    }

    // Check if passed $invoiceno matches an invoiceno in krf_sales_order_items
    if (empty($targetInvoiceNo)) {
        $chkSoiInv = $krfconn->prepare("SELECT invoiceno, invoicedate FROM krf_sales_order_items WHERE invoiceno = ? AND ? != '' LIMIT 1");
        if ($chkSoiInv) {
            $chkSoiInv->bind_param("ss", $invoiceno, $invoiceno);
            $chkSoiInv->execute();
            $soiInvR = $chkSoiInv->get_result()->fetch_assoc();
            $chkSoiInv->close();
            if ($soiInvR && !empty($soiInvR['invoiceno'])) {
                $targetInvoiceNo = trim($soiInvR['invoiceno']);
                $targetInvoiceDate = !empty($soiInvR['invoicedate']) ? date('Y-m-d', strtotime($soiInvR['invoicedate'])) : '';
            }
        }
    }

    // If passed $invoiceno was a psono or sono, check if this order has a dispatch invoice
    if (empty($targetInvoiceNo)) {
        if (!empty($order['dinvoiceno'])) {
            $targetInvoiceNo = trim($order['dinvoiceno']);
        } else {
            $chkLatestInv = $krfconn->prepare("
                SELECT invoiceno, invoicedate 
                FROM krf_sales_order_items 
                WHERE ((psono = ? AND ? != '') OR (sono = ? AND ? != ''))
                  AND invoiceno IS NOT NULL AND invoiceno != ''
                ORDER BY id DESC LIMIT 1
            ");
            if ($chkLatestInv) {
                $chkLatestInv->bind_param("ssss", $psono, $psono, $sono, $sono);
                $chkLatestInv->execute();
                $lRow = $chkLatestInv->get_result()->fetch_assoc();
                $chkLatestInv->close();
                if ($lRow && !empty($lRow['invoiceno'])) {
                    $targetInvoiceNo = trim($lRow['invoiceno']);
                    $targetInvoiceDate = !empty($lRow['invoicedate']) ? date('Y-m-d', strtotime($lRow['invoicedate'])) : '';
                }
            }
        }
    }

    // 3. Fetch Items: Check sales_invoice_items first (True commercial invoice items)
    $items = [];
    $isFromSalesInvoiceItems = false;
    if (!empty($targetInvoiceNo)) {
        $siiStmt = $krfconn->prepare("
            SELECT * FROM sales_invoice_items 
            WHERE invoiceno = ? 
              AND LOWER(IFNULL(status, '')) != 'cancelled'
            ORDER BY id ASC
        ");
        if ($siiStmt) {
            $siiStmt->bind_param("s", $targetInvoiceNo);
            $siiStmt->execute();
            $items = $siiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $siiStmt->close();
            if (!empty($items)) {
                $isFromSalesInvoiceItems = true;
            }
        }
    }

    if (empty($items) && !empty($targetInvoiceNo)) {
        // Dispatched Invoice: fetch items from krf_sales_order_items belonging to this specific invoice
        $soiSql = "
            SELECT * FROM krf_sales_order_items 
            WHERE invoiceno = ?
            ORDER BY id ASC
        ";
        $soiStmt = $krfconn->prepare($soiSql);
        if ($soiStmt) {
            $soiStmt->bind_param("s", $targetInvoiceNo);
            $soiStmt->execute();
            $items = $soiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $soiStmt->close();
        }
    } elseif (empty($items)) {
        // Undispatched Order Preview / Proforma: fetch only un-invoiced items
        $soiSql = "
            SELECT * FROM krf_sales_order_items 
            WHERE ((sono = ? AND ? != '') OR (psono = ? AND ? != ''))
              AND (invoiceno IS NULL OR invoiceno = '')
            ORDER BY id ASC
        ";
        $soiStmt = $krfconn->prepare($soiSql);
        if ($soiStmt) {
            $soiStmt->bind_param("ssss", $sono, $sono, $psono, $psono);
            $soiStmt->execute();
            $items = $soiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $soiStmt->close();
        }
    }

    // Fallback: check krf_provisional_sales_order_items if no items found
    if (empty($items) && !empty($psono)) {
        $psoiStmt = $krfconn->prepare("SELECT * FROM krf_provisional_sales_order_items WHERE psono = ? ORDER BY id ASC");
        if ($psoiStmt) {
            $psoiStmt->bind_param("s", $psono);
            $psoiStmt->execute();
            $items = $psoiStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $psoiStmt->close();
        }
    }

    $formattedItems = [];
    if ($isFromSalesInvoiceItems) {
        foreach ($items as $it) {
            $pId = strval($it['productid'] ?? $it['product_id'] ?? '');
            $pName = trim($it['productname'] ?? $it['product_name'] ?? 'Plant Crop');
            $isBoxPacking = !empty($it['is_box_packing']) || ($pId === 'BOX_PACKING') || stripos($pName, 'Box Packing') !== false;
            $stk = trim($it['stockorder_no'] ?? $it['stockorderno'] ?? '');
            $pQty = floatval($it['quantity'] ?? 0);
            $pRate = floatval($it['rate'] ?? 0);
            $pDisc = floatval($it['discount_amount'] ?? 0);
            $pAmt = floatval(!empty($it['taxableamount']) ? $it['taxableamount'] : (!empty($it['amount']) ? $it['amount'] : ($pQty * $pRate - $pDisc)));
            $delDate = !empty($it['deliverydate']) ? $it['deliverydate'] : (!empty($targetInvoiceDate) ? $targetInvoiceDate : date('Y-m-d'));
            $isShifted = (isset($it['isshifted']) ? ($it['isshifted'] === 'yes' || $it['isshifted'] === 'YES' || $it['isshifted'] === 1 || $it['isshifted'] === '1') : true);
            $extraQty = intval($it['shiftingqty'] ?? 0);
            $extraPct = floatval($it['percentextrashifting'] ?? 0);
            if ($isShifted) {
                if ($extraQty <= 0 && $pQty > 0) {
                    $extraQty = intval(ceil($pQty / 125));
                }
                $extraReason = 'Free Bonus Plants (1 Plant/Tray)';
            } else {
                if ($extraQty <= 0 && $extraPct > 0 && $pQty > 0) {
                    $extraQty = intval(round($pQty * ($extraPct / 100)));
                }
                $extraReason = 'Free Non-Shifted Offer (' . ($extraPct > 0 ? $extraPct . '%' : 'Complimentary') . ')';
            }

            $formattedItems[] = [
                'soi_id' => $it['original_soi_id'] ?? $it['id'] ?? 0,
                'product_id' => $pId,
                'product_name' => $pName,
                'clean_product_name' => $pName,
                'stockorder_no' => $stk,
                'quantity' => $pQty,
                'rate' => $pRate,
                'discount_amount' => $pDisc,
                'deliverydate' => $delDate,
                'amount' => $pAmt,
                'is_shifted' => $isShifted ? 'yes' : 'no',
                'extra_qty' => $extraQty,
                'extra_pct' => $extraPct,
                'extra_reason' => $extraReason,
                'is_included' => true,
                'is_box_packing' => $isBoxPacking
            ];
        }
    } else {
        foreach ($items as $it) {
            $pId = strval($it['product_id'] ?? '');
            $pName = trim($it['product_name'] ?? 'Plant Crop');
            $isBoxPacking = !empty($it['is_box_packing']) || ($pId === 'BOX_PACKING') || stripos($pName, 'Box Packing') !== false;
            $stk = trim($it['stockorderno'] ?? $it['stockorder_no'] ?? '');

            // Fetch non-shifting and consumption info from krf_productionorder_consumed (crops only)
            $poc = null;
            if (!$isBoxPacking) {
                $pocStmt = $krfconn->prepare("
                    SELECT isshifted, percentextrashifting, extraqtyforshifting, consumed_qty, stockorderno, original_orderqty 
                    FROM krf_productionorder_consumed 
                    WHERE (psono = ? AND ? != '' AND product_id = ?) 
                       OR (sono = ? AND ? != '' AND (stockorderno = ? OR product_id = ?))
                       OR (stockorderno = ? AND ? != '')
                    ORDER BY id DESC LIMIT 1
                ");
                if ($pocStmt) {
                    $pocStmt->bind_param("sssssssss", $psono, $psono, $pId, $sono, $sono, $stk, $pId, $stk, $stk);
                    $pocStmt->execute();
                    $poc = $pocStmt->get_result()->fetch_assoc();
                    $pocStmt->close();
                }
            }

            $pQty = floatval(!empty($it['allocate_ordqty']) ? $it['allocate_ordqty'] : (!empty($it['quantity']) ? $it['quantity'] : ($poc['consumed_qty'] ?? 0)));
            $pRate = floatval($it['rate'] ?? 0);
            $pDisc = floatval($it['discount_amount'] ?? 0);
            $pAmt = floatval(!empty($it['amount']) ? $it['amount'] : ($pQty * $pRate - $pDisc));
            $delDate = $it['deliverydate'] ?? $it['expecteddeliverydate'] ?? ($order['deliverydate'] ?? date('Y-m-d'));

            // Check isshifted
            $isShifted = true;
            if (!$isBoxPacking) {
                if (isset($it['isshifted'])) {
                    $isShifted = ($it['isshifted'] === 'yes' || $it['isshifted'] === 'YES' || $it['isshifted'] === 1 || $it['isshifted'] === '1');
                } elseif (isset($poc['isshifted'])) {
                    $isShifted = ($poc['isshifted'] === 'yes' || $poc['isshifted'] === 'YES' || $poc['isshifted'] === 1 || $poc['isshifted'] === '1');
                }
            }

            $extraQty = 0;
            $extraPct = 0;
            $extraReason = '';
            if (!$isBoxPacking) {
                $extraQty = intval(!empty($it['shiftingqty']) ? $it['shiftingqty'] : (!empty($it['extraqtyforshifting']) ? $it['extraqtyforshifting'] : ($poc['extraqtyforshifting'] ?? 0)));
                if ($isShifted) {
                    if ($extraQty <= 0 && $pQty > 0) {
                        $extraQty = intval(ceil($pQty / 125));
                    }
                    $extraReason = 'Free Bonus Plants (1 Plant/Tray)';
                } else {
                    $extraPct = floatval(!empty($it['percentextrashifting']) ? $it['percentextrashifting'] : ($poc['percentextrashifting'] ?? 30));
                    if ($extraQty <= 0 && $extraPct > 0 && $pQty > 0) {
                        $extraQty = intval(round($pQty * ($extraPct / 100)));
                    }
                    $extraReason = 'Free Non-Shifted Offer (' . ($extraPct > 0 ? $extraPct . '%' : 'Complimentary') . ')';
                }
            }

            $formattedItems[] = [
                'soi_id' => $it['id'] ?? 0,
                'product_id' => $pId,
                'product_name' => $pName,
                'clean_product_name' => $pName,
                'stockorder_no' => $stk,
                'quantity' => $pQty,
                'rate' => $pRate,
                'discount_amount' => $pDisc,
                'deliverydate' => $delDate,
                'amount' => $pAmt,
                'is_shifted' => $isShifted ? 'yes' : 'no',
                'extra_qty' => $extraQty,
                'extra_pct' => $extraPct,
                'extra_reason' => $extraReason,
                'is_included' => true,
                'is_box_packing' => $isBoxPacking
            ];
        }
    }

    // 4. Fetch Freight Details (including all assigned delivery locations & vehicle info)
    $allFreightGroups = [];
    $rawPickupType = 'delivered';
    $rawTransportType = '';
    $groupedLocs = [];

    $stmtLoc = $krfconn->prepare("SELECT deliveryaddress, dest_city, dest_district, dest_state, totaldistance, freedistance, distance, remark, deliverycharges, freightamt, totalamount, transporttype, pickuptype, productid, delivery_datetime, noofbox, boxperpack, chargesperbox, vehicleno, '' as vehiclename, '' as vehicleownername, drivername, drivercontactno, '' as trip_id FROM krf_deliveryaddress_location WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id ASC");
    if ($stmtLoc) {
        $stmtLoc->bind_param("ssss", $psono, $psono, $sono, $sono);
        $stmtLoc->execute();
        $resLoc = $stmtLoc->get_result();
        while ($lRow = $resLoc->fetch_assoc()) {
            $lAddr = trim($lRow['deliveryaddress'] ?? '');
            if (empty($lAddr)) {
                $parts = array_filter([$lRow['dest_city'] ?? '', $lRow['dest_district'] ?? '', $lRow['dest_state'] ?? '']);
                $lAddr = implode(', ', $parts);
            }
            $cleanKey = strtoupper(trim(preg_replace('/[\s,.-]+/', '', $lAddr)));
            if ($cleanKey === '' || $cleanKey === 'NA' || $cleanKey === 'N/A' || $cleanKey === 'NULL') {
                continue;
            }
            $locDate = !empty($lRow['delivery_datetime']) ? date('Y-m-d', strtotime($lRow['delivery_datetime'])) : '';
            $groupKey = $cleanKey . '___' . $locDate;

            $pType = trim($lRow['pickuptype'] ?? 'delivered');
            $tType = trim($lRow['transporttype'] ?? '');
            
            $tDisplay = 'Farm Delivery';
            if ($pType === 'boxpacking') {
                $tDisplay = 'Box Packing (' . ($tType ?: 'Bus') . ')';
            } elseif ($pType === 'client_pickup' || $pType === 'pickup') {
                $tDisplay = 'Self Pickup By Farmer/Client';
            } elseif (!empty($tType) && !in_array($tType, ['Bus', 'Train', 'Courier', 'Air', 'Railway', 'Airport', 'Transport'])) {
                $tDisplay = 'Farm Delivery (' . $tType . ')';
            }

            $rowTotalDist = floatval($lRow['totaldistance'] ?? 0);
            $rowFreeDist = floatval($lRow['freedistance'] ?? 0);
            $rowPayableDist = floatval($lRow['distance'] ?? 0);
            if ($rowTotalDist <= 0 && $rowPayableDist > 0) {
                $rowTotalDist = $rowPayableDist + $rowFreeDist;
            }
            $pAmt = floatval($lRow['freightamt'] ?? $lRow['deliverycharges'] ?? $lRow['totalamount'] ?? 0);

            if (!isset($groupedLocs[$groupKey])) {
                $groupedLocs[$groupKey] = [
                    'transport_type' => $tDisplay,
                    'delivery_address' => $lAddr,
                    'distance_km' => $rowPayableDist,
                    'total_distance' => $rowTotalDist,
                    'free_distance' => $rowFreeDist,
                    'payable_distance' => $rowPayableDist,
                    'freight_amount' => floatval($lRow['freightamt'] ?? 0),
                    'payable_freight_amount' => $pAmt,
                    'vehicle_no' => trim($lRow['vehicleno'] ?? ''),
                    'vehicle_name' => trim($lRow['vehiclename'] ?? ''),
                    'transporter_name' => trim($lRow['vehicleownername'] ?? ''),
                    'driver_name' => trim($lRow['drivername'] ?? ''),
                    'driver_mobile' => trim($lRow['drivercontactno'] ?? ''),
                    'trip_id' => trim($lRow['trip_id'] ?? ''),
                    'remark' => trim($lRow['remark'] ?? ''),
                    'boxperpack' => intval($lRow['boxperpack'] ?? 0),
                    'noofbox' => intval($lRow['noofbox'] ?? 0),
                    'chargesperbox' => floatval($lRow['chargesperbox'] ?? 0)
                ];
            } else {
                if ($groupedLocs[$groupKey]['payable_freight_amount'] <= 0 && $pAmt > 0) {
                    $groupedLocs[$groupKey]['payable_freight_amount'] = $pAmt;
                }
                if ($groupedLocs[$groupKey]['total_distance'] <= 0 && $rowTotalDist > 0) $groupedLocs[$groupKey]['total_distance'] = $rowTotalDist;
                if ($groupedLocs[$groupKey]['free_distance'] <= 0 && $rowFreeDist > 0) $groupedLocs[$groupKey]['free_distance'] = $rowFreeDist;
                if ($groupedLocs[$groupKey]['payable_distance'] <= 0 && $rowPayableDist > 0) {
                    $groupedLocs[$groupKey]['payable_distance'] = $rowPayableDist;
                    $groupedLocs[$groupKey]['distance_km'] = $rowPayableDist;
                }
                if (empty($groupedLocs[$groupKey]['vehicle_no']) && !empty($lRow['vehicleno'])) $groupedLocs[$groupKey]['vehicle_no'] = trim($lRow['vehicleno']);
                if (empty($groupedLocs[$groupKey]['driver_name']) && !empty($lRow['drivername'])) $groupedLocs[$groupKey]['driver_name'] = trim($lRow['drivername']);
                if (empty($groupedLocs[$groupKey]['driver_mobile']) && !empty($lRow['drivercontactno'])) $groupedLocs[$groupKey]['driver_mobile'] = trim($lRow['drivercontactno']);
            }
        }
        $stmtLoc->close();
    }
    $allFreightGroups = array_values($groupedLocs);

    $freightData = $allFreightGroups[0] ?? [
        'transport_type' => 'Farm Delivery',
        'delivery_address' => '',
        'distance_km' => 0,
        'freight_amount' => 0,
        'payable_freight_amount' => 0,
        'vehicle_no' => '',
        'vehicle_name' => '',
        'transporter_name' => '',
        'driver_name' => '',
        'driver_mobile' => '',
        'trip_id' => '',
        'remark' => ''
    ];

    // Also check krf_freightcharges for overall discount rule / remarks / amounts
    $fcStmt = $krfconn->prepare("SELECT * FROM krf_freightcharges WHERE (psono = ? AND ? != '') OR (sono = ? AND ? != '') ORDER BY id DESC LIMIT 1");
    $fcRow = null;
    if ($fcStmt) {
        $fcStmt->bind_param("ssss", $psono, $psono, $sono, $sono);
        $fcStmt->execute();
        $fcRow = $fcStmt->get_result()->fetch_assoc();
        $fcStmt->close();
    }

    if (empty($allFreightGroups)) {
        if ($fcRow) {
            $freightData['payable_freight_amount'] = floatval($fcRow['payable_freightamount'] ?? $fcRow['freight_amount'] ?? 0);
            $freightData['freight_amount'] = floatval($fcRow['freight_amount'] ?? $fcRow['payable_freightamount'] ?? 0);
            $freightData['distance_km'] = floatval($fcRow['distance_km'] ?? 0);
            $freightData['vehicle_no'] = trim($fcRow['vehicle_no'] ?? '');
            $freightData['vehicle_name'] = trim($fcRow['vehiclename'] ?? '');
            $freightData['transporter_name'] = trim($fcRow['vehicleownername'] ?? '');
            $freightData['driver_name'] = trim($fcRow['driver_name'] ?? '');
            $freightData['driver_mobile'] = trim($fcRow['driver_mobile'] ?? '');
            $freightData['trip_id'] = trim($fcRow['trip_id'] ?? '');
            $freightData['delivery_address'] = trim($fcRow['destination_locationname'] ?? '');
            $fcRem = trim($fcRow['remarks'] ?? $fcRow['remark'] ?? '');
            if (!empty($fcRem) && stripos($fcRem, '@@@') === false && stripos($fcRem, 'Trip 1: Amt Rs') === false) {
                $freightData['remark'] = $fcRem;
                $freightData['remarks'] = $fcRem;
            } else {
                $freightData['remark'] = '';
                $freightData['remarks'] = '';
            }
            $freightData['freight_discountpercent'] = floatval($fcRow['freight_discountpercent'] ?? 0);
            $freightData['freight_discountamount'] = floatval($fcRow['freight_discountamount'] ?? 0);
            $allFreightGroups[] = $freightData;
        }
    } elseif ($fcRow) {
        $fcRem = trim($fcRow['remarks'] ?? $fcRow['remark'] ?? '');
        $fcPct = floatval($fcRow['freight_discountpercent'] ?? 0);
        $fcAmt = floatval($fcRow['freight_discountamount'] ?? 0);
        $fcPayable = floatval($fcRow['payable_freightamount'] ?? 0);
        $freightData['freight_discountpercent'] = $fcPct;
        $freightData['freight_discountamount'] = $fcAmt;
        if (!empty($allFreightGroups[0])) {
            $allFreightGroups[0]['discount_percent'] = $fcPct;
            $allFreightGroups[0]['discount_amount'] = $fcAmt;
            if (count($allFreightGroups) === 1 && $fcPayable > 0) {
                $allFreightGroups[0]['payable_freight_amount'] = $fcPayable;
            }
            if (!empty($fcRem) && stripos($fcRem, '@@@') === false && stripos($fcRem, 'Trip 1: Amt Rs') === false) {
                if (empty($allFreightGroups[0]['remark'])) {
                    $allFreightGroups[0]['remark'] = $fcRem;
                }
            }
        }
    }

    // 5. Total Received Payments & Breakdown
    $totalReceived = 0;
    $cashReceived = 0.0;
    $chequeReceived = 0.0;
    $upiBankReceived = 0.0;
    $totalOtherDiscount = 0.0;
    $baseSono = preg_replace('/-KF-.*$/i', '', $sono);

    $payStmt = $krfconn->prepare("
        SELECT advanceamount, sourceofpayment, otherdiscount, roundoffamount, verification 
        FROM advance_payment_transactions 
        WHERE ((invoiceno = ? AND ? != '') 
           OR (invoiceno = ? AND ? != '') 
           OR (invoiceno = ? AND ? != '') 
           OR (invoiceno = ? AND ? != ''))
          AND (tedit IS NULL OR tedit = '')
        ORDER BY paymentdate ASC, sno ASC
    ");
    if ($payStmt) {
        $payStmt->bind_param("ssssssss", $psono, $psono, $sono, $sono, $baseSono, $baseSono, $targetInvoiceNo, $targetInvoiceNo);
        $payStmt->execute();
        $payRes = $payStmt->get_result();
        while ($p = $payRes->fetch_assoc()) {
            $pAmt = floatval($p['advanceamount'] ?? 0);
            $pDisc = floatval($p['otherdiscount'] ?? 0);
            $totalOtherDiscount += $pDisc;
            $pSrc = strtolower(trim($p['sourceofpayment'] ?? ''));
            if ($pSrc === 'cash') {
                $cashReceived += $pAmt;
            } elseif (in_array($pSrc, ['cheque', 'check'])) {
                $chequeReceived += $pAmt;
            } else {
                $upiBankReceived += $pAmt;
            }
            $totalReceived += $pAmt;
        }
        $payStmt->close();
    }

    if (!empty($targetInvoiceNo)) {
        $finalInvNo = $targetInvoiceNo;
        if (empty($targetInvoiceDate)) {
            $invDateStmt = $krfconn->prepare("SELECT invoicedate FROM krf_sales_order_items WHERE invoiceno = ? AND invoicedate IS NOT NULL ORDER BY id DESC LIMIT 1");
            if ($invDateStmt) {
                $invDateStmt->bind_param("s", $targetInvoiceNo);
                $invDateStmt->execute();
                $dRow = $invDateStmt->get_result()->fetch_assoc();
                $invDateStmt->close();
                if ($dRow && !empty($dRow['invoicedate'])) {
                    $targetInvoiceDate = date('Y-m-d', strtotime($dRow['invoicedate']));
                }
            }
        }
        $finalInvDate = !empty($targetInvoiceDate) ? $targetInvoiceDate : (!empty($order['invoicedate']) ? date('Y-m-d', strtotime($order['invoicedate'])) : date('Y-m-d'));
    } else {
        $finalInvNo = !empty($order['sono']) ? $order['sono'] : (!empty($order['psono']) ? $order['psono'] : $invoiceno);
        $finalInvDate = !empty($order['created_at']) ? date('Y-m-d', strtotime($order['created_at'])) : date('Y-m-d');
    }

    // Resolve logged-in person name
    $resolvedCreatedBy = trim($_SESSION['loginname'] ?? $_SESSION['username'] ?? $_SESSION['name'] ?? '');
    if (empty($resolvedCreatedBy) || strtoupper($resolvedCreatedBy) === 'SYSTEM') {
        if (!empty($_SESSION['loginid'])) {
            $sessLgId = trim((string)$_SESSION['loginid']);
            $uStmt = $authconn->prepare("SELECT loginname FROM login_user_master WHERE (tedit IS NULL OR tedit = '') AND (loginid = ? OR mobileno = ? OR sno = ?) LIMIT 1");
            if ($uStmt) {
                $uStmt->bind_param("sss", $sessLgId, $sessLgId, $sessLgId);
                $uStmt->execute();
                $uRow = $uStmt->get_result()->fetch_assoc();
                $uStmt->close();
                if (!empty($uRow['loginname'])) {
                    $resolvedCreatedBy = trim($uRow['loginname']);
                }
            }
        }
    }
    if (empty($resolvedCreatedBy) || strtoupper($resolvedCreatedBy) === 'SYSTEM') {
        $resolvedCreatedBy = trim($_GET['loginname'] ?? '');
    }

    $params = [
        'psono' => $psono,
        'sono' => $sono,
        'companyid' => $companyid,
        'subcompanyid' => $subcompanyid,
        'projectid' => $projectid,
        'invoiceno' => $finalInvNo,
        'invoicedate' => $finalInvDate,
        'created_by' => $resolvedCreatedBy,
        'clientname' => $customerName,
        'mobileno' => $phones,
        'emailid' => $emailAddress,
        'invoicing_address' => $invoicingAddress,
        'items' => $formattedItems,
        'freight' => $freightData,
        'freight_groups' => $allFreightGroups,
        'total_received' => $totalReceived,
        'cash_received' => $cashReceived,
        'cheque_received' => $chequeReceived,
        'upi_bank_received' => $upiBankReceived,
        'other_discount' => $totalOtherDiscount,
        'special_discount' => $totalOtherDiscount,
        'mode' => $mode
    ];

    ob_end_clean();
    createDispatchInvoicePDF($conn, $authconn, $krfconn, $params, $mode);
}
?>