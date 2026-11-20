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
}
?>
<div class="container">
  <div class="row <?php echo isset($_GET['dz']) ? 'd-none' : '' ?>">
    <div class="col-md-12">

      <button class="btn btn-sm btn-deepblue" onclick="window.location.reload();"> <i class="fa fa-sync"></i>
        Refresh</button>
    </div>
  </div>
  <div class="card <?php echo $psono != "" ? "" : "d-none"; ?>">
    <div class="card-header">
      <h4>Provisional Oreder Details</h4>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-6 mb-3">
          <p class="mb-1">Provisional Order No: <strong><?php echo $psono; ?></strong></p>
          <p class="mb-1">Provisional Order Date: <strong><?php echo $psodate; ?></strong></p>
          <p class="mb-1">Provisional Invoice Amount: <strong><?php echo $psoamount; ?></strong></p>
        </div>
        <div class="col-md-6 mb-3">
          <p class="mb-1">Client Name: <strong><?php echo $clientname; ?></strong></p>
          <p class="mb-1">Client Contact: <strong><?php echo $mobileno; ?></strong></p>
        </div>
      </div>
    </div>
  </div>
  <div class="card mt-1">
    <div class="card-header">
      <h4>Payment Advice Manual</h4>
    </div>
    <div class="card-body">
      <form id="advancePaymentForm" method="POST" enctype="multipart/form-data">
        <!-- Client Selection -->
        <div class="form-group mb-3  <?php echo $psono != "" ? "d-none" : ""; ?>">
          <label for="client" class="form-label"><span>Choose</span> Client Name <span
              class="text-danger">*</span></label>
          <i class="fa fa-plus-square text-primary addClientbtn" data-psono="<?php echo $psono; ?>"
            style="cursor:pointer; font-size: 22px;"></i>
          <span class="text-primary float-right" onclick='GetclientDetailsFrommaster("all", "all", "client")'
            style="cursor:pointer;"><i class="fa fa-sync"></i> Refresh</span>
          <select class="form-control" id="client" name="clientid">
            <option value="">Select Client</option>
          </select>
        </div>

        <!-- Purpose of Payment -->
        <div class="form-group mb-3">
          <label class="form-label"><span>Purpose of</span> Amount <span class="text-danger">*</span></label><br />
          <div class="form-check form-check-inline mb-0">
            <input class="form-check-input" type="radio" name="purposeofamount" id="advancepayment"
              value="advancepayment" checked>
            <label class="form-check-label p-1" for="advancepayment"> Advance Payment</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="purposeofamount" id="paymentagainstinvoice"
              value="paymentagainstinvoice">
            <label class="form-check-label p-1" for="paymentagainstinvoice"> Payment Against Invoice</label>
          </div>
        </div>

        <!-- Amount -->
        <div class="form-group mb-3">
          <label for="invoiceno" class="form-label"><span class="prefixamount">Provisional</span> Order No</label>
          <input type="text" class="form-control" id="invoiceno" name="invoiceno" value="<?php echo $psono; ?>">
        </div>

        <!-- Amount -->
        <div class="form-group mb-3">
          <label for="amount" class="form-label"><span class="prefixamount">Advance</span> Amount <span
              class="text-danger">*</span></label>
          <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
        </div>

        <!-- Source of Payment -->
        <div class="row">
          <div class="col-md-12">
            <label class="form-label"><span>Source of</span> Payment</label>
          </div>
          <div class="col-md-3">
            <label for="cash"><input type="radio" name="sourceofpayment" id="cash" value="cash" checked> Cash</label>
          </div>
          <div class="col-md-3">
            <label><input type="radio" name="sourceofpayment" id="cheque" value="cheque"> Cheque</label>
          </div>
          <div class="col-md-3">
            <label for="banktransfer"><input type="radio" name="sourceofpayment" id="banktransfer" value="banktransfer">
              Bank Transfer</label>
          </div>
          <div class="col-md-3">
            <label for="upi"><input type="radio" name="sourceofpayment" id="upi" value="upi"> UPI</label>
          </div>
        </div>

        <div class="form-group mb-3 d-none" id="refrensenodiv">
          <label for="utrnumber" class="form-label"><span>UTR/Cheque</span> Number</label>
          <input type="text" class="form-control" id="utrnumber" name="utrnumber"
            placeholder="Cheque No / UPI Ref / Transaction ID">
        </div>

        <!-- Payment Date -->
        <div class="form-group mb-3">
          <label for="paymentdate" class="form-label"><span>Payment</span> Date <span
              class="text-danger text-lg">*</span></label>
          <input type="date" class="form-control" id="paymentdate" name="paymentdate" required>
        </div>

        <!-- Invoice/Bank Section (Conditional) -->
        <div class="form-group mb-3" id="receivedbankSection" style="display:none;">
          <label for="receivedtoaccountid" class="form-label">Select Bank</label>
          <select class="form-control" id="receivedtoaccountid" name="receivedtoaccountid">
            <option value="">Select</option>
            <!-- Populate dynamically -->
          </select>
        </div>

        <!-- Remarks -->
        <div class="form-group mb-3">
          <label for="remarks" class="form-label"><span>Our</span> Remarks</label>
          <textarea class="form-control" id="remarks" name="remarks"></textarea>
        </div>
        <input type="hidden" name="companyid" value="<?php echo $companyid; ?>">
        <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>">
        <input type="hidden" name="projectid" value="<?php echo $projectid; ?>">
        <input type="hidden" name="loginid" value="<?php echo $loginid; ?>">
        <div class="form-group mb-3 text-center">
          <button type="submit" class="btn btn-deepblue">Save Payment</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Edit Order Modal -->
<div class="modal fade" id="otherModal">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="otherModalheader"></h5>
        <!-- <button type="button" class="close" data-dismiss="modal">&times;</button> -->
        <button type="button" class="close" onclick="$('#otherModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="otherModalContent">
        <!-- Dynamic form content via AJAX -->
      </div>
    </div>
  </div>
</div>
<?php
if (isset($_GET['dz'])) {

} else {
  include('../footer.php');
}
?>
<script type="text/javascript" src="<?php echo $prefix; ?>krf-orderandsales/js/pjs_function.js"></script>

<script>
  document.querySelectorAll('input[name="purposeofamount"]').forEach(radio => {
    radio.addEventListener('change', function () {
      if (this.value === 'advancepayment') {
        $(".prefixamount").html("Advance");
      } else if (this.value === 'paymentagainstinvoice') {
        $(".prefixamount").html("Invoice");
      } else {
        $(".prefixamount").html("Advance");
      }
    });
  });
  // Show/Hide invoice section based on selected purpose
  document.querySelectorAll('input[name="sourceofpayment"]').forEach(radio => {
    radio.addEventListener('change', function () {
      const receivedbankSection = document.getElementById('receivedbankSection');
      if (this.value === 'cash') {
        $("#refrensenodiv").addClass("d-none");
        receivedbankSection.style.display = 'none';
        document.getElementById('receivedtoaccountid').removeAttribute('required');
      } else {
        $("#refrensenodiv").removeClass("d-none");
        receivedbankSection.style.display = 'block';
        document.getElementById('receivedtoaccountid').setAttribute('required', 'required');
      }
    });
  });
  $(document).ready(function () {
    var clienttemp = '<?php echo $clientname; ?>';
    GetclientDetailsFrommaster("all", "all", "client", clienttemp);
    GetAccountdetailsFrommaster_option('receivedtoaccountid', '', 'receivedbankSection');
    $("#advancePaymentForm").on("submit", function (e) {
      e.preventDefault(); // prevent normal submit
      let formData = new FormData(this);
      $.ajax({
        url: "modal/paymentadvice_modal.php?action=saveadvancepayment",
        type: "POST",
        data: formData,
        contentType: false,
        processData: false,
        success: function (response) {
          console.log(response);
          try {
            let res = JSON.parse(response);
            if (res.status === "success") {
              alertmessage("success", "Payment saved successfully!");
              window.location.reload();
              $("#advancePaymentForm")[0].reset();
            } else {
              alertmessage("error", "Error: " + res.message);
            }
          } catch (err) {
            console.error(err);
            alert("Unexpected server response.");
          }
        },
        error: function (xhr) {
          console.error(xhr);
          alert("Failed to save payment.");
        }
      });
    });

    $(document).on("click", ".addClientbtn", function () {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let routurl = $("body").data("routurl");
      let key = $("body").data("key");
      let psono = $(this).data("psono");
      window.open(routurl + "krf-orderandsales/addclient.php?k=" + key + "&psono=" + psono);
    });
  });
</script>