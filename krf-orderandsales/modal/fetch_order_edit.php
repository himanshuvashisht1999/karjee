<?php
error_reporting(E_ALL);
require('../../connection.php');
require('../../loginCell/functions/login_functions.php');
include("../functions/orders_functions.php");
include("../functions/product_functions.php");
// Fetch data (your same logic)
$companyid   = $_POST['companyid'] ?? 0;
$subcompanyid= $_POST['subcompanyid'] ?? 0;
$projectid   = $_POST['projectid'] ?? 0;
$psono       = $_POST['psono'] ?? 0;
$loginid       = $_POST['loginid'] ?? '';

$order = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono)[0] ?? null;
$clientid = $order['clientid'];
$lginfo = loginuserinfo($authconn,"","loginid",$order['created_by']);
$created_by = $lginfo[0]['loginname'];
$items = getProvisionalSalesOrderItems($krfconn, $psono);
$frdinfo = getFreightChargesData($krfconn, $psono);
$freight_amount = $frdinfo[0]['freight_amount'];
$freight_discountpercent = $frdinfo[0]['freight_discountpercent'];
$payable_freightamount = $frdinfo[0]['payable_freightamount'];
if (!$order) {
    echo "<p class='text-danger'>Order not found.</p>";
    exit;
}

// Fetch order items
$items = getProvisionalSalesOrderItems($krfconn, $psono); // create this function to fetch items
?>

<form id="editOrderForm">
   
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

    <div class="mb-3">
        <label>Status</label>
        <select name="status" class="form-control">
            <option value="Draft" <?php if($order['status']=='Draft') echo 'selected'; ?>>Draft</option>
            <option value="Confirmed" <?php if($order['status']=='Confirmed') echo 'selected'; ?>>Confirmed</option>
            <option value="Cancelled" <?php if($order['status']=='Cancelled') echo 'selected'; ?>>Cancelled</option>
        </select>
    </div>

    <!-- Items Table -->
    <h6>Items</h6>
    <div class="row mt-3" id="editorderitemsdiv">
    	<div class="col-md-12">
		    <div class="table-responsive shadow-sm rounded">
                <table class="table table-hover table-bordered align-middle" id="itemsTable" data-parentdivid="editorderitemsdiv" style="min-width: 1000px;">
                  <thead class="bg-light">
                        <tr class="text-uppercase small font-weight-bold">
                          <th rowspan="2" style="width: 300px;">Product Name</th>
                          <th class="text-center" style="width: 120px;" rowspan="2">Rate</th>
                          <th class="text-center" style="width: 100px;" rowspan="2">Qty</th>
                          <th class="d-none text-center" style="width: 150px;" rowspan="2">Taxable Amt</th>
                          <th style="text-align: center; width: 180px;" colspan="2">Discount</th>
                          <th class="text-center" style="width: 140px;" rowspan="2">Payable Amount</th>
                          <th class="text-center" style="width: 160px;" rowspan="2">Exp. Delivery</th>
                          <th class="text-center" style="width: 50px;" rowspan="2">Action</th>
                        </tr>
                        <tr class="text-uppercase small font-weight-bold">
                          <th class="text-center" style="width: 80px;">%</th>
                          <th class="text-center" style="width: 100px;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $count=1; $ttlamount=0; $ttlfreight=0;
                    foreach($items as $item):
                    ?>
                        <tr>
                          <td class="p-1"><select name="productname[]" class="form-control productname" data-selectedproductid="<?php echo $item['product_id'] ?? 0; ?>" required></select></td>
                          <td class="p-1"><input type="text" name="rate[]" class="form-control invrowcell_rm ratecal rate_td text-right" onKeyPress="return isFloatNumber(event,this);" /></td>
                          <td class="p-1"><input type="text" name="quantity[]" class="form-control invrowcell_rm qtycal qty_td text-center" value="<?php echo $item['quantity'] ?? 0; ?>" onKeyPress="return isFloatNumber(event,this);" /></td>

                          <td class="d-none p-1"><input type="text" name="taxableamount[]" class="form-control txableamount_td" value="<?php echo $item['taxableamount'] ?? 0; ?>" readonly /></td>
                          
                          <td class="p-1"><input type="text" name="distprt[]" class="form-control distcal distprt_td text-center" value="<?php echo $item['discount_percent'] ?? 0; ?>" onKeyPress="return isFloatNumber(event,this);" /></td>
                          <td class="p-1"><input type="text" name="distamt[]" class="form-control distcal distamt_td text-right" value="<?php echo $item['discount_amount'] ?? 0; ?>" onKeyPress="return isFloatNumber(event,this);" /></td>
                          
                          <td class="p-1"><input type="text" name="amount[]" class="form-control payableamount payableamount_td text-right font-weight-bold" value="<?php echo $item['amount'] ?? 0; ?>" onkeypress="return isNumberKey(event)" required readonly></td>
                          <td class="p-1"><input type="date" name="expecteddelivery[]" class="form-control expecteddelivery" value="<?php echo $item['expecteddeliverydate'] ?? ''; ?>" required /></td>
                          <td class="p-1 text-center"><button type="button" class="btn btn-outline-danger btn-sm removeRow"><i class="fa fa-times"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                  </tbody>
                  <tfoot class="bg-light">
                    <tr>
                      <th colspan="3">
                        <button type="button" class="btn btn-outline-success btn-sm" id="addRow"><i class="fa fa-plus"></i> Add Item</button>
                      </th>
                      <th colspan="4" class="text-right">
                         <div class="mb-1">Total Discount: <span id="ttldiscountamount" class="font-weight-bold">0</span></div>
                         <div class="h5 mb-0">Total Payable Amount: <span id="grandtotalamount" class="text-primary font-weight-bold">0</span></div>
                      </th>
                    </tr>
                    <tr class="d-none">
                      <td id="totalamount"></td>
                      <td id="gsttotalamount"></td>
                    </tr>
                    <tr>
                      <th colspan="2">
                        <label class="small text-uppercase">Freight Charges</label>
                        <input type="text" class="form-control" name="freight_amount" id="freight_amount" value="<?php echo $freight_amount; ?>" />
                      </th>
                      <th colspan="2">
                        <label class="small text-uppercase">Discount (%)</label>
                        <input type="text" class="form-control" name="discount_percent" id="discount_percent" value="<?php echo $freight_discountpercent; ?>" />
                      </th>
                      <th colspan="2">
                      <label class="small text-uppercase">Payable Freight</label>
                        <input type="text" class="form-control font-weight-bold" name="payablefreight_amount" id="payablefreight_amount" value="<?php echo $payable_freightamount; ?>" readonly />
                      </th>
                      <th></th>
                    </tr>
                  </tfoot>
                </table>
            </div>
		</div>
	</div>
    <div class="row mt-4">
    	<div class="col-md-12 text-center">
    		<input type="hidden" name="companyid" value="<?php echo $companyid; ?>" />
	        <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>" />
	        <input type="hidden" name="projectid" value="<?php echo $projectid; ?>" />
	        <input type="hidden" name="loginid" value="<?php echo $loginid; ?>" />
            <input type="hidden" id="clientid" name="clientid" value="<?php echo $order['clientid']; ?>" />
             <input type="hidden" name="psono" value="<?php echo $psono; ?>">
        	<button type="submit" class="btn btn-primary">UPDATE PROVIONAL ORDER</button>
        </div>
    </div>
</form>
<script>
document.addEventListener("DOMContentLoaded", function() {
  let today = new Date().toISOString().split("T")[0];
  document.querySelectorAll(".expecteddelivery").forEach(function(input) {
    input.setAttribute("min", today);
  });
});

var productList = [];

// Fetch product list from master
GetProductListFrommaster_callback(function(res){
    if (res.length > 0) {
        productList = res;
        populateProductDropdowns();
    }
});

function getProductOptions(selectedProductId = "") {
    var options = '<option value="" disabled>Select</option>';
    productList.forEach(function(prd) {
        var selected = (prd.ProductID == selectedProductId) ? 'selected' : '';
        var rawName = prd.ProductName || '';
        var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
        options += `<option value="${prd.ProductID}" data-priceofproduct="${prd.PriceOfProduct}" ${selected}>${displayName}</option>`;
    });
    return options;
}

function populateProductDropdowns() {
    $("#itemsTable tbody tr").each(function(){
        var selectedProductId = $(this).find('.productname').data('selectedproductid');
        // alert(selectedProductId);
        var options = getProductOptions(selectedProductId);
        $(this).find('.productname').html(options).trigger('change');
    });
}

$(document).ready(function(){
    $("#addRow").click(function(){
        addRowtbody("itemsTable");
        Invoicegrandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','grandtotalamount','payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'itemsTable');
    });
    // Handle Enter key on quantity column: apply mouseout and focus out (blur)
    $("#itemsTable").on("keydown", ".qty_td, .qtycal", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        $(this).trigger("mouseout").trigger("mouseleave").trigger("blur").blur();
      }
    });

    // Prevent default enter key on other table inputs/selects
    $("#itemsTable").on("keydown", "input:not(.qty_td):not(.qtycal), select", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
      }
    });

    // Recalculate total when a row is removed
    $(document).on("click", ".removeRow", function () {
      $(this).closest("tr").remove();
      Invoicegrandtotalgstamont('totalamount','gsttotalamount','ttldiscountamount','grandtotalamount','payableamount_td', 'cgstamt_td', 'sgstamt_td', 'igstamt_td', 'distamt_td', 'itemsTable');
    });

    $(document).on("change", ".productname", function(){
          let row = $(this).closest("tr");
          row.find("input.ratecal").val(parseFloat(row.find(".productname option:selected").data("priceofproduct")) || 0).trigger("input");
    });

    $(document).ready(function() {
        calculateFreightPayable("#freight_amount", "#discount_percent", "#payablefreight_amount");
    });

    $("#editOrderForm").submit(function(e) {
        e.preventDefault();
        var routurl = $("body").data("routurl");
        $.ajax({
            url: routurl+"krf-orderandsales/modal/update_provisionalsales_order.php?action=updateprovisionalsalesorder",
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    location.reload();
                } else {
                    alert("Error: " + response.message);
                }
            }
        });
    });
});
</script>
