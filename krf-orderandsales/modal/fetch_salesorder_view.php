<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");

// Fetch data (your same logic)
$companyid   = $_POST['companyid'] ?? 0;
$subcompanyid= $_POST['subcompanyid'] ?? 0;
$projectid   = $_POST['projectid'] ?? 0;
$sono       = $_POST['sono'] ?? 0;

$order = getFinalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "sono", $sono)[0] ?? null;
$psono = $order['psono']; 
$lginfo = loginuserinfo($authconn,"","loginid",$order['created_by']);
$created_by = $lginfo[0]['loginname'];
$paymentrcv = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $psono);
$items = getFinalSalesOrderItems($krfconn, $sono);
if ($order):
?>
<div class="card mb-3">
  <div class="card-body">
    <h5 class="card-title text-center">Final Sales Order Review</h5>
    <button class="btn btn-sm pull-right btn-danger" onclick="FinalSalesOrderDownload('<?php echo $sono; ?>');"><i class="fa fa-download"></i> PDF</button>
    <div class="row">
      <div class="col-md-6">
        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['clientname']); ?><br/>
        <strong>Email:</strong> <?php echo htmlspecialchars($order['emailid']!='' ? $order['emailid'] : 'N/A'); ?><br/>
        <strong>Mobile:</strong> <?php echo htmlspecialchars($order['mobileno']!='' ? $order['mobileno'] : 'N/A'); ?><br/>
        <strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
      </div>
      <div class="col-md-6">
        <p><strong>Sales Order No:</strong> <?php echo htmlspecialchars($order['sono']); ?><br/>
        <strong>Provisional Order No:</strong> <?php echo htmlspecialchars($order['psono']); ?><br/>
        <strong>Order Date:</strong> <?php echo date('d-M-Y', strtotime($order['created_at'])); ?><br/>
        <strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] !='' ? $order['delivery_address'] : 'N/A'); ?><br/>
        <strong>Created By:</strong> <?php echo strtoupper($created_by); ?></p>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <div class="table-responsive">
          <!-- Items Table -->
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>#</th><th>Product</th><th>Qty</th><th>Rate</th>
                <th>Amount</th><th>Delivery Date</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $count=1; $ttlamount=0; $totalQty=0; $ttlfreight=0;
              foreach($items as $item):
                $pQty = (float)($item['quantity'] ?? 0);
                $pAmt = (float)($item['amount'] ?? 0);
                $totalQty += $pQty;
                $ttlamount += $pAmt;
              ?>
              <tr>
                <td><?php echo $count++; ?></td>
                <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                <td><?php echo number_format($pQty) . ' Pcs'; ?></td>
                <td><?php echo number_format((float)($item['rate'] ?? 0), 2); ?></td>
                <td><?php echo number_format($pAmt, 2); ?></td>
                <td><?php echo $item['deliverydate'] ? date('d-M-Y', strtotime($item['deliverydate'])) : 'N/A'; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot>
              <tr class="table-secondary fw-bold">
                <td colspan="2" class="text-center"><strong>Total</strong></td>
                <td><strong><?php echo number_format($totalQty) . ' Pcs'; ?></strong></td>
                <td></td>
                <td><strong><?php echo number_format($ttlamount, 2); ?></strong></td>
                <td></td>
              </tr>
              <?php
              $fRes = $krfconn->query("SELECT payable_freightamount FROM krf_freightcharges WHERE sono = '" . $krfconn->real_escape_string($sono) . "' OR psono = '" . $krfconn->real_escape_string($psono) . "' LIMIT 1");
              if ($fRes && $fRow = $fRes->fetch_assoc()) {
                  $ttlfreight = (float)$fRow['payable_freightamount'];
              }

              $productDateMap = [];
              foreach ($items as $idx => $it) {
                  $pId = (string)($it['product_id'] ?? '');
                  $dDate = !empty($it['expecteddeliverydate']) ? date('Y-m-d', strtotime($it['expecteddeliverydate'])) : (!empty($it['deliverydate']) ? date('Y-m-d', strtotime($it['deliverydate'])) : '');
                  if ($pId !== '') {
                      $productDateMap[$pId] = $dDate;
                  }
                  $productDateMap['idx_' . $idx] = $dDate;
              }

              $transportType = 'Company Delivery';
              $deliveryLocs = [];
              $locRes = $krfconn->query("SELECT deliveryaddress, dest_city, dest_district, dest_state, distance, deliverycharges, freightamt, totalamount, transporttype, productid, delivery_datetime FROM krf_deliveryaddress_location WHERE (sono = '" . $krfconn->real_escape_string($sono) . "' OR psono = '" . $krfconn->real_escape_string($psono) . "') AND (tedit IS NULL OR tedit = '') ORDER BY id ASC");
              if ($locRes) {
                  while ($lRow = $locRes->fetch_assoc()) {
                      $deliveryLocs[] = $lRow;
                      if (!empty($lRow['transporttype'])) {
                          $transportType = trim($lRow['transporttype']);
                      }
                  }
              }

              $hasValidAddr = false;
              $destAddr = '';
              $dist = 0;
              $groupedLocs = [];
              if (!empty($deliveryLocs)) {
                  foreach ($deliveryLocs as $lIdx => $loc) {
                      $lAddr = trim($loc['deliveryaddress'] ?? '');
                      if (empty($lAddr)) {
                          $parts = array_filter([$loc['dest_city'] ?? '', $loc['dest_district'] ?? '', $loc['dest_state'] ?? '']);
                          $lAddr = implode(', ', $parts);
                      }
                      if (empty($destAddr) && !empty($lAddr)) {
                          $destAddr = $lAddr;
                          $dist = (float)($loc['distance'] ?? 0);
                      }
                      $cleanKey = strtoupper(trim(preg_replace('/[\s,.-]+/', '', $lAddr)));
                      if ($cleanKey === '' || $cleanKey === 'NA' || $cleanKey === 'N/A' || $cleanKey === 'NULL') {
                          continue;
                      }

                      $pId = (string)($loc['productid'] ?? '');
                      $locDate = $productDateMap[$pId] ?? ($productDateMap['idx_' . $lIdx] ?? '');
                      if (empty($locDate) && !empty($loc['delivery_datetime'])) {
                          $locDate = date('Y-m-d', strtotime($loc['delivery_datetime']));
                      }
                      $groupKey = $cleanKey . '___' . $locDate;

                      if (!isset($groupedLocs[$groupKey])) {
                          $groupedLocs[$groupKey] = [
                              'address' => $lAddr,
                              'distance' => (float)($loc['distance'] ?? 0),
                              'freightamt' => (float)($loc['freightamt'] ?? 0),
                              'deliverycharges' => (float)($loc['deliverycharges'] ?? 0),
                              'totalamount' => (float)($loc['totalamount'] ?? 0),
                              'transporttype' => trim($loc['transporttype'] ?? ''),
                              'deliverydate' => $locDate
                          ];
                      } else {
                          $groupedLocs[$groupKey]['freightamt'] += (float)($loc['freightamt'] ?? 0);
                          $groupedLocs[$groupKey]['deliverycharges'] += (float)($loc['deliverycharges'] ?? 0);
                          $groupedLocs[$groupKey]['totalamount'] += (float)($loc['totalamount'] ?? 0);
                          if ($groupedLocs[$groupKey]['distance'] <= 0 && (float)($loc['distance'] ?? 0) > 0) {
                              $groupedLocs[$groupKey]['distance'] = (float)($loc['distance'] ?? 0);
                          }
                      }
                  }
              }

              $hasValidAddr = !empty($groupedLocs);
              ?>
              <tr class="table-info align-middle">
                <td colspan="6">
                  <h5 class="d-inline-block mb-0 fw-bold me-3">Freight Details</h5>
                  <span><b>Transport Type:</b> <?php echo htmlspecialchars($transportType); ?></span>
                </td>
              </tr>
              <?php if (!$hasValidAddr): ?>
                <tr class="table-light">
                  <td colspan="4" class="text-muted"><small>Freight charges will be applicable as per the distance to which the plants are to be delivered. Location to be provided by the farmer.</small></td>
                  <td><strong>0.00</strong></td>
                  <td></td>
                </tr>
              <?php else: ?>
                <?php 
                $grpCount = count($groupedLocs);
                foreach ($groupedLocs as $grp): 
                  $lAddr = $grp['address'];
                  $lDist = (float)($grp['distance'] ?? 0);
                  $distText = $lDist > 0 ? " (Distance: {$lDist} km)" : "";

                  $locAmt = 0;
                  if ((float)($grp['freightamt'] ?? 0) > 0) {
                      $locAmt = (float)$grp['freightamt'];
                  } elseif ((float)($grp['deliverycharges'] ?? 0) > 0) {
                      $locAmt = (float)$grp['deliverycharges'];
                  } elseif ((float)($grp['totalamount'] ?? 0) > 0) {
                      $locAmt = (float)$grp['totalamount'];
                  } elseif ($grpCount === 1) {
                      $locAmt = $ttlfreight;
                  }
                ?>
                <tr class="table-light">
                  <td colspan="4">Karjee &nbsp;→&nbsp; <?php echo htmlspecialchars($lAddr) . $distText; ?></td>
                  <td><strong><?php echo number_format($locAmt, 2); ?></strong></td>
                  <td></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tfoot>
          </table>
        </div>
      </div>
    </div>
    <div class="row">
      <div class="col-md-12">
        <?php if(count($paymentrcv) > 0): ?>
        <h5>Payment Received Against Provisional Order No <?php echo htmlspecialchars($psono); ?></h5>
        <ul>
          <?php $ttlrcv=0; foreach($paymentrcv as $p): $ttlrcv+=$p['advanceamount']; ?>
            <li>
              <?php
              $icon = "💵";
              if(strtolower($p['sourceofpayment'])=="upi"){ $icon="📲"; }
              else if(strtolower($p['sourceofpayment'])=="banktransfer" || strtolower($p['sourceofpayment'])=="cheque"){ $icon="🏦"; }
              echo $icon." <strong>".strtoupper($p['sourceofpayment']).":</strong> ".number_format($p['advanceamount'], 2);
              ?>
            </li>
          <?php endforeach; ?>
        </ul>
        <p><strong>Total Received:</strong> <?php echo number_format($ttlrcv, 2); ?><br>
           <strong>Balance:</strong> <?php echo number_format(floatval($ttlamount) - floatval($ttlrcv), 2); ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php else: ?>
<p class="text-danger">Order not found.</p>
<?php endif; ?>