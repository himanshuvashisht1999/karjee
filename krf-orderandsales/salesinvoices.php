<?php
include('../header.php');
include('functions/orders_functions.php');
include('functions/client_functions.php');

$mdid ="";
if(isset($_GET['mdid'])){
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
$groupid ="";
if(count($dddr)>0){
  $groupid = $dddr[0]['groupid'];
}

?>
<div class="container my-4">
  <h3 class="mb-4 fw-bold text-center">Provisional Sales Orders</h3>
  <div class="row p-2">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue" onclick="window.location.reload();"> <i class="fa fa-sync"></i> Refresh</button>
      <!-- <button class="btn btn-sm btn-deepblue pull-right newprovisionalorder"><i class="fa fa-plus-square"></i> New Sales Invoice</button> -->
    </div>
  </div>
  <div class="card shadow-sm">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-bordered table-striped mb-0">
          <thead class="table-light">
              <tr>
                  <th>S.No</th>
                  <th>Invoice Date</th>
                  <th>Invoice No</th>
                  <th>Invoice Amount</th>
                  <th>Paid Amount</th>
                  <th>Client Name</th>
                  <th>Actions</th>
              </tr>
          </thead>
          <tbody>
            <?php
                $psnolist = getSalesInvoiceDetails($krfconn, $projectid);
                // print_r($psnolist);
                if (count($psnolist) > 0):
                    $sno=1;
                    foreach ($psnolist as $row):
                        $posnoinfo= getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $row['psono']);
                        $psodate = date('d-M-Y', strtotime($posnoinfo[0]['created_at']));
                        $clientname = $posnoinfo[0]['clientname'];
                        $mobileno = $posnoinfo[0]['mobileno'];
                        $emailid = $posnoinfo[0]['emailid'];
                        $deliverydate = $posnoinfo[0]['expecteddelivery'];
                        $psoamount =  $posnoinfo[0]['total_amount'] ?? 0;
                        $address = $posnoinfo[0]['address'];
                        $clientid = $posnoinfo[0]['clientid'];

                        $clinfo = getClientDetailsInfo($krfconn, $companyid, $subcompanyid, $projectid, "clientid", $row['clientid']);
                        $clientname =$clinfo[0]['client_name'];

                        $admnt = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $row['psono']);
                        $paidamount=0;
                        if(count($admnt)>0){
                          foreach ($admnt as $admntkey) {
                            $paidamount +=$admntkey['advanceamount'];
                          }
                        }
                        // $badgeClass = match($row['status']) {
                        //     'Confirmed' => 'bg-success',
                        //     'Cancelled' => 'bg-danger',
                        //     default => 'bg-dark'
                        // };

                        // $status = match($row['status']) {
                        //     'Confirmed' => 'Confirmed',
                        //     'Cancelled' => 'Cancelled',
                        //     default => 'Un-Confirmed'
                        // };

                        // $actionbtn = match($row['status']) {
                        //     'Cancelled' => '<span class="badge bg-success" data-psono="'.$row['psono'].'">Confirmed</span>',
                        //     'Cancelled' => '<span class="badge bg-info readytoreactivate" style="cursor:pointer;" data-psono="'.$row['psono'].'">Re-Activate</span>',
                        //     default => '<span class="badge bg-success text-white readytoconfirm" style="cursor:pointer;" data-psono="'.$row['psono'].'">Update Advance</span>'
                        // };

                        // $saleinoicebtn = match($row['status']) {
                        //     'Confirmed' => '<span class="badge bg-success text-white createsaleinvoice"  style="cursor:pointer;" data-psono="'.$row['psono'].'">Ready to Sale Invoice</span>',
                        //     'Cancelled' => '',
                        //     default => ''
                        // };
            ?>
                <tr>
                    <td><?php echo $sno++; ?></td>
                    <td><?php echo htmlspecialchars(date('d-M-Y', strtotime($row['invoicedate']))); ?></td>
                    <td><?php echo htmlspecialchars($row['invoiceno']); ?></td>
                    <td><?php echo $psoamount; ?></td>
                    <td><?php echo floatval($paidamount); ?></td>
                    <td><?php echo htmlspecialchars($clientname); ?></td>
                    <td><button class="btn btn-sm GenrateInvoicepdf" data-invoiceno="<?php echo $row['invoiceno']; ?>" data-psono="<?php echo $row['psono']; ?>"><i class="fa fa-download"></i> Invoice</button></td>
                </tr>
            <?php
                    endforeach;
                else:
            ?>
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">No provisional sales orders found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include('../footer.php'); ?>
<script>

$(document).ready(function() {
    $(document).on("click", ".GenrateInvoicepdf", function() {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let routurl = $("body").data("routurl");
      let loginname = <?php echo json_encode($_SESSION['loginname'] ?? $_SESSION['username'] ?? ''); ?>;
      window.open(routurl+"krf-orderandsales/modal/genrateInvoicePDF.php?invno="+invoiceno+"&companyid="+companyid+"&subcompanyid="+subcompanyid+"&projectid="+projectid+(loginname ? "&loginname="+encodeURIComponent(loginname) : ""));
    });

});

</script>