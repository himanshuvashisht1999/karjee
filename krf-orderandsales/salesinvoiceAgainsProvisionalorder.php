<?php
$psodate = "";
$clientname = "";
$mobileno = "";
$emailid = "";
$deliverydate = "";
$psoamount = "";
if (isset($_GET['dz'])) {
  require('../connection.php');
  require('../functions/function_master.php');
  include('functions/orders_functions.php');
  $key = $_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|", $key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts) - 1]);
  $actkey = "";
  for ($i = 0; $i < count($key_parts) - 1; $i++) {
    $actkey = $actkey ? $actkey . "|" . $key_parts[$i] : $key_parts[$i];
  }
  if ($actkey != $checkkey) {
    echo "<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
    die;
  }
  $loginname = "";
  $loginid = $key_parts[0];
  $loginroleid = $key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  $newkey = "x|x|" . $key_parts[2] . "|" . $key_parts[3] . "|" . $key_parts[4] . "|x";
  $encodednewkey = base64url_encode($newkey);
  $keycompany = base64url_encode($newkey . "|" . $encodednewkey);
  $cmpinfo = getcompanydetails($mconn, "companyid", $companyid);
  $companyname = "";
  if (count($cmpinfo) > 0) {
    foreach ($cmpinfo as $cmphkey) {
      $companyname = $cmphkey['company_name'];
      $countryid = $cmphkey['countryid'];
    }
  }

  $subcmpinfo = getsubcompanydetails($mconn, $companyid, $subcompanyid, "subcompanyid", $subcompanyid);
  $subcompanyname = "";
  if (count($subcmpinfo) > 0) {
    foreach ($subcmpinfo as $subcmphkey) {
      $subcompanyname = $subcmphkey['subcompanyname'];
    }
  }

  //projectinfo
  $prj = getprojectdetails($conn, $companyid, $subcompanyid, "projectid", $projectid);
  // print_r($prj);
  $projectname = "";
  if (count($prj) > 0) {
    foreach ($prj as $pjkey) {
      $projectname = $pjkey['projectname'];
    }
  }


  $protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename($_SERVER["PHP_SELF"]);
  $rooturl = $protocol . '://' . $_SERVER['HTTP_HOST'];
  $baseurl = $rooturl . str_replace($url, "", $_SERVER["PHP_SELF"]);
  $countryid = "";
  if ($_SERVER['HTTP_HOST'] == "localhost") {
    $prefix = $rooturl . "/krf_amanaccounting/";
  } else {
    $prefix = $rooturl . "/";
  }
  $rooturl = $prefix;
} else {
  require('../header.php');
  include('functions/orders_functions.php');
  $rooturl;
}
$mdid = "";
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}

// get provisiotn order details
$psono = "";
$psodate = "";
$clientname = "";
$mobileno = "";
$emailid = "";
$deliverydate = "";
$psoamount = "";
if (isset($_GET['psono'])) {
  $psono = $_GET['psono'];
  $posnoinfo = getProvisionalSalesOrders($krfconn, $companyid, $subcompanyid, $projectid, "psono", $psono);
  $psodate = date('d-M-Y', strtotime($posnoinfo[0]['created_at']));
  $clientname = $posnoinfo[0]['clientname'];
  $mobileno = $posnoinfo[0]['mobileno'];
  $emailid = $posnoinfo[0]['emailid'];
  $deliverydate = $posnoinfo[0]['expecteddelivery'];
  $psoamount = $posnoinfo[0]['total_amount'];
  $address = $posnoinfo[0]['address'];
  $clientid = $posnoinfo[0]['clientid'];

  $psolisththt = getProvisionalSalesOrderItems($krfconn, $psono);
  $advpay = getAdvancePaymentTransactions($krfconn, $companyid, $subcompanyid, $projectid, "invoiceno", $psono);
  $advpayment = 0;
  if (count($advpay) > 0) {
    foreach ($advpay as $advpaykey) {
      $advpayment += $advpaykey['advanceamount'];
    }
  }
  $balanceAmount = floatval($psoamount) - floatval($advpayment);
}
$invoiceno = "";
?>
<div class="container">

  <!-- Header -->
  <div class="text-center mb-4">
    <h2 class="fw-bold">Provisional Sales Order</h2>
    <p class="text-muted">Order Preview</p>
  </div>

  <div class="row <?php echo isset($_GET['dz']) ? 'd-none' : '' ?>">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</a>
    </div>
  </div>
  <!-- Order & Customer Details -->
  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="row">
        <div class="col-md-12 mb-3">
          <h6 class="fw-bold">Provisional Order Details</h6>
        </div>
        <div class="col-md-6 mb-3">
          <p class="mb-1">Provisional Order No: <strong><?php echo $psono; ?></strong></p>
          <p class="mb-1">Provisional Order Date: <strong><?php echo $psodate; ?></strong></p>
          <p class="mb-1">Provisional Invoice Amount: <strong><?php echo $psoamount; ?></strong></p>

        </div>
        <div class="col-md-6 mb-3">
          <p class="mb-1">Customer Name: <strong><?php echo $clientname; ?></strong></p>
          <p class="mb-1">Customer Contact: <strong><?php echo $mobileno; ?></strong></p>
          <p class="mb-1">Already Paid Amount: <strong><?php echo $advpayment; ?></strong></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Item List -->
  <form id="salesInvoiceForm">
    <div class="card shadow-sm mb-4">
      <div class="card-body">
        <h6 class="fw-bold mb-3">Product Details</h6>
        <div class="table-responsive">
          <table class="table table-bordered align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:250px;">Required Product</th>
                <th>Original Product</th>
                <th style="width:140px;">Qty</th>
                <th style="width:140px;">Rate</th>
                <th style="width:170px;">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $trr = '';
              $ttinvoiceamount = 0;
              $ttltaxamount = 0;
              $grandttlamount = 0;
              if (!empty($psolisththt) && count($psolisththt) > 0) {
                foreach ($psolisththt as $psolisthtkey) {
                  $ttinvoiceamount += $psolisthtkey['amount'];
                  $trr .= '<tr>
                      <td>' . htmlspecialchars($psolisthtkey['product_name']) . '</td>
                      <td>
                        <select class="form-control productnamelist" name="productid[]"></select>
                      </td>
                      <td><input type="number" class="form-control qty" name="qty[]" value="' . htmlspecialchars($psolisthtkey['quantity']) . '" /></td>
                      <td><input type="number" class="form-control rate" name="rate[]" value="' . htmlspecialchars($psolisthtkey['rate']) . '" /></td>
                      <td><input type="number" class="form-control amount" name="amount[]" value="' . htmlspecialchars($psolisthtkey['amount']) . '" /></td>
                  </tr>';
                }
                echo $trr;
              }
              ?>
            </tbody>
          </table>
        </div>
        <div class="row justify-content-end">
          <div class="col-md-4">
            <ul class="list-group list-group-flush">
              <li class="list-group-item d-flex justify-content-between">
                <span>Subtotal Amount</span>
                <strong><?php echo $ttinvoiceamount; ?></strong>
              </li>

              <li class="list-group-item d-flex justify-content-between  <?php echo $advpayment > 0 ? "" : "d-none"; ?>">
                <span>Already Paid Amount</span>
                <strong><?php echo $advpayment; ?></strong>
              </li>
              <li class="list-group-item d-flex justify-content-between <?php echo $balanceAmount > 0 ? "" : "d-none"; ?>">
                <span>Total Balance Amount</span>
                <strong><?php echo $balanceAmount; ?></strong>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </div>

    <!-- Summary -->
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="row">
          <div class="col-md-12 text-center">
            <input type="hidden" name="companyid" value="<?php echo $companyid; ?>" />
            <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>" />
            <input type="hidden" name="projectid" value="<?php echo $projectid; ?>" />
            <input type="hidden" name="psono" value="<?php echo $psono; ?>" />
            <input type="hidden" name="invoiceno" value="<?php echo $invoiceno; ?>" />
            <input type="hidden" name="clientid" value="<?php echo $clientid; ?>" />
            <button class="btn btn-deepblue" id="savesalesinvoice">Create Sales Invoice</button>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>
<?php
include("../footer.php");
?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>
<script>
  GetProductListFrommaster_callback(function (res) {
    if (Array.isArray(res) && res.length > 0) {
      // Add default option if more than one item
      if (res.length > 1) {
        $(".productnamelist").append(`<option value="" selected disabled>Select product</option>`);
      }
      res.forEach(item => {
        var rawName = item.ProductName || '';
        var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
        $(".productnamelist").append(`<option value="${item.ProductID}">${displayName}</option>`);
      });
      if (res.length === 1) {
        $(".productnamelist").val(res[0].ProductID).trigger("change");
      }
    }
  });

  // Submit form using AJAX
  $("#salesInvoiceForm").submit(function (e) {
    e.preventDefault();
    var key = $("body").data("key");
    var mdid = "<?php echo $mdid; ?>"; // ensure this is inside a PHP file

    $.ajax({
      url: "modal/update_provisionalsales_order.php?action=saveactualsalesinvoice", // PHP backend
      type: "POST",
      data: $(this).serialize(),
      dataType: "json",
      success: function (response) {
        if (response.status === "success") {
          alertmessage("success", response.message);
          Swal.fire({
            title: "Do you want to add more order?",
            text: "Order saved successfully! Provisional Order No: " + response.psono,
            showDenyButton: true,
            showCancelButton: true,
            confirmButtonText: "YES",
            denyButtonText: `NO`
          }).then((result) => {
            if (result.isConfirmed) {
              Swal.fire("Saved!", "", "success");
              $("#salesInvoiceForm")[0].reset();
            } else if (result.isDenied) {
              // 🔑 Redirect when user clicks NO
              window.location.href = "provisionalsaleorder_review.php?k=" + key + "&mdid=" + mdid + "&iframe=true";
            }
          });
        } else {
          alertmessage("error", "Error: " + response.message);
        }
      },
      error: function () {
        alert("An error occurred while saving the order.");
      }
    });
  });
</script>