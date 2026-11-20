<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");

// Fetch data (your same logic)
$companyid   = $_POST['companyid'] ?? 0;
$subcompanyid= $_POST['subcompanyid'] ?? 0;
$projectid   = $_POST['projectid'] ?? 0;
$psono       = $_POST['psono'] ?? 0;

$order = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono)[0] ?? null;
$lginfo = loginuserinfo($authconn,"","loginid",$order['created_by']);
$created_by = $lginfo[0]['loginname'];
$paymentrcv = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $psono);
$items = getProvisionalSalesOrderItems($krfconn, $psono);

$sono = '';
$stmtSo = $krfconn->prepare("SELECT sono FROM krf_sales_order WHERE psono = ? ORDER BY id DESC LIMIT 1");
if ($stmtSo) {
    $stmtSo->bind_param("s", $psono);
    $stmtSo->execute();
    $resSo = $stmtSo->get_result();
    if ($rowSo = $resSo->fetch_assoc()) {
        $sono = trim($rowSo['sono'] ?? '');
    }
    $stmtSo->close();
}
$isOrderConfirmed = (($order['status'] ?? '') === 'Confirmed' || !empty($sono));

if ($order):
?>
<div class="card mb-3">
  <div class="card-body">
    <h5 class="card-title text-center"><?php echo $isOrderConfirmed ? 'Sales Order Review (' . htmlspecialchars($sono ?: $psono) . ')' : 'Provisional Order Review (' . htmlspecialchars($psono) . ')'; ?></h5>
    <button class="btn btn-sm pull-right btn-danger" onclick="downloadOrderPDF('<?php echo htmlspecialchars($psono); ?>', <?php echo $isOrderConfirmed ? 'true' : 'false'; ?>, '<?php echo htmlspecialchars($sono); ?>');" title="<?php echo $isOrderConfirmed ? 'Download Final Sales Order PDF' : 'Download Provisional Order PDF'; ?>"><i class="fa fa-download"></i> <?php echo $isOrderConfirmed ? 'Sales Order PDF' : 'Provisional PDF'; ?></button>
    <div class="row">
      <div class="col-md-6">
        <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['clientname']); ?><br/>
        <strong>Email:</strong> <?php echo htmlspecialchars($order['emailid']!='' ? $order['emailid'] : 'N/A'); ?><br/>
        <strong>Mobile:</strong> <?php echo htmlspecialchars($order['mobileno']!='' ? $order['mobileno'] : 'N/A'); ?><br/>
        <strong>Status:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
      </div>
      <div class="col-md-6">
        <p><strong>Order No:</strong> <?php echo htmlspecialchars($order['psono']); ?><br/>
        <strong>Order Date:</strong> <?php echo date('d-M-Y', strtotime($order['created_at'])); ?><br/>
        <strong>Delivery Address:</strong> <?php echo htmlspecialchars($order['delivery_address'] !='' ? $order['delivery_address'] : 'N/A'); ?><br/>
        <strong>Created By:</strong> <?php echo strtoupper($created_by); ?></p>
      </div>
    </div>
    <div class="row mt-3">
      <div class="col-md-12">
        <div class="table-responsive shadow-sm rounded">
          <table class="table table-bordered table-hover align-middle mb-0">
            <thead class="bg-light text-uppercase small font-weight-bold">
              <tr>
                <th class="text-center" style="width: 50px;">#</th>
                <th>Product</th>
                <th class="text-center" style="width: 80px;">Qty</th>
                <th class="text-right" style="width: 120px;">Rate</th>
                <th class="text-right" style="width: 120px;">Discount</th>
                <th class="text-right" style="width: 140px;">Amount</th>
                <th class="text-center" style="width: 150px;">Exp. Delivery</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $count=1; $ttlamount=0;
              foreach($items as $item):
                $ttlamount += $item['amount'] ?? 0;
                $cleanName = preg_replace('/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i', '', $item['product_name'] ?? '');
              ?>
              <tr>
                <td class="text-center text-muted"><?php echo $count++; ?></td>
                <td class="font-weight-bold"><?php echo htmlspecialchars(!empty($cleanName) ? $cleanName : ($item['product_name'] ?? '')); ?></td>
                <td class="text-center"><?php echo number_format($item['quantity'] ?? 0, 0); ?></td>
                <td class="text-right">₹<?php echo number_format($item['rate'] ?? 0, 2); ?></td>
                <td class="text-right text-danger">₹<?php echo number_format($item['discount_amount'] ?? 0, 2); ?></td>
                <td class="text-right font-weight-bold text-primary">₹<?php echo number_format($item['amount'] ?? 0, 2); ?></td>
                <td class="text-center small"><?php echo $item['expecteddeliverydate'] ? date('d-M-Y', strtotime($item['expecteddeliverydate'])) : 'N/A'; ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="bg-light font-weight-bold">
              <tr>
                <td colspan="5" class="text-right text-uppercase">Grand Total</td>
                <td class="text-right text-primary h5 mb-0">₹<?php echo number_format($ttlamount, 2); ?></td>
                <td></td>
              </tr>
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