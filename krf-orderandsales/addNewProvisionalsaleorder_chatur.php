<?php
include('../header.php');
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<div class="container1 my-4">
  <h3 class="mb-4 text-center">Create Provisional Sales Order</h3>
  <div class="row p-2">
    <div class="col-md-12">
      <a class="btn btn-sm btn-deepblue" href="../modulesCell/indexdisplay.php?k=<?php echo $key; ?>&grpid=<?php echo $groupid; ?>&mdid=<?php echo $mdid; ?>"> <i class="fa fa-backward"></i> Back</a>
      <button class="btn btn-sm btn-deepblue" onclick="window.location.reload();"> <i class="fa fa-sync"></i> Refresh</button>
      <button class="btn btn-sm btn-deepblue gotoprovisionalorderlist pull-right"> <i class="fa fa-list"></i> Provisional Orders</button>
      
    </div>
  </div>  
  <form id="salesOrderForm">
    <div class="card p-3 mb-4" id="card1">
      <h5 class="mb-3">Client Information</h5>
      <div class="row mb-1">
        <div class="col-md-4">
          <label class="form-label">Mobile No *</label>
          <input type="tel" id="mobileno" name="mobileno" class="form-control" autocomplete="off" pattern="[0-9]{10}" maxlength="10"  onkeypress="return isNumberKey(event)" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Client Name *</label>
          <input type="text" id="clientname" name="clientname" class="form-control" autocomplete="off" required>
        </div>
        <div class="col-md-4">
          <label class="form-label">Email</label>
          <input type="email" id="emailid" name="emailid" class="form-control" autocomplete="off" />
        </div>
      </div>
      <div id="alreadyexistclientdataview"></div>
      <div class="row mb-1">
        <div class="col-md-12">
          <label class="form-label">Address</label>
          <textarea id="delivery_address" name="delivery_address" class="form-control"  autocomplete="off"></textarea>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-6">
          <label class="form-label">District</label>
          <input type="text" id="districtname" name="districtname" class="form-control"  autocomplete="off" />
        </div>
        <div class="col-md-6">
          <label class="form-label">State</label>
          <input type="text" id="statename" name="statename" class="form-control"  autocomplete="off" />
        </div>
      </div>
    </div>
    <!-- Item Details -->
    <div class="card p-3 mb-4" id="card2">
      <h5 class="mb-3">Provisional Sales Order Items</h5>
      <div class="row">
        <div class="col-md-12">
          <div class="table-responsive">
            <table class="table table-bordered" id="itemsTable"  data-parentdivid="card2">
              <thead class="table-light">
                <tr>
                  <th rowspan="2" style="min-width:250px;">Product Name</th>
                  <th style="text-align: center; width:125px;" rowspan="2">Rate</th>
                  <th style="text-align: center; width:125px;" rowspan="2">Quantity</th>
                  <th class="d-none" style="text-align: center; min-width:150px;" rowspan="2">Taxable Amount</th>
                  <th class="d-none" style="text-align: center; width:100px;" colspan="2">CGST</th>
                  <th class="d-none" style="text-align: center; width:100px;" colspan="2">SGST</th>
                  <th class="d-none" style="text-align: center; width:100px;" colspan="2">IGST</th>
                  <th style="text-align: center; width:200px;" colspan="2">Discount</th>
                  <th style="text-align: center; width:150px;" rowspan="2">Payable Amount</th>
                  <th style="text-align: center; min-width:150px;" rowspan="2">Expected Delivery Date</th>
                  <th style="text-align: center; min-width:60px;" rowspan="2">Action</th>
                </tr>
                <tr>
                  <th class="d-none" style="text-align: center; width:100px;">%</th>
                  <th class="d-none" style="text-align: center; width:100px;">Amount</th>
                  <th class="d-none" style="text-align: center; width:100px;">%</th>
                  <th class="d-none" style="text-align: center; width:100px;">Amount</th>
                  <th class="d-none" style="text-align: center; width:100px;">%</th>
                  <th class="d-none" style="text-align: center; width:100px;">Amount</th>
                  <th style="text-align: center; width:90px;">%</th>
                  <th style="text-align: center; width:90px;">Amount</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style="padding: .02rem; text-align: center; width:250px;"><select name="productname[]" class="form-control productname" required></select></td>
                  <td style="padding: .02rem; text-align: center; width:125px;"><input type="text" name="rate[]" class="form-control invrowcell_rm ratecal rate_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  <td style="padding: .02rem; text-align: center; width:125px;"><input type="text" name="quantity[]" class="form-control invrowcell_rm qtycal qty_td" onKeyPress="return isFloatNumber(event,this);" /></td>

                  <td class="d-none" style="padding: .02rem; text-align: center; width:150;"><input type="text" name="taxableamount[]" class="form-control txableamount_td" readonly /></td>
                  
                  <td class="d-none" style="padding: .02rem; text-align: center; width:50px;"><input type="text" name="cgstprt[]" class="form-control cgstcal cgstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  <td class="d-none" style="padding: .02rem; text-align: center; width:50px;"><input type="text" name="cgstamt[]" class="form-control cgstcal cgstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>

                  <td class="d-none" style="padding: .02rem; text-align: center; width:50px;"><input type="text" name="sgstprt[]" class="form-control sgstcal sgstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  <td class="d-none" style="padding: .02rem; text-align: center; width:50px;"><input type="text" name="sgstamt[]" class="form-control sgstcal sgstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  
                  <td class="d-none" style="padding: .02rem; text-align: center; width:50px;"><input type="text" name="igstprt[]" class="form-control igstcal igstprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  <td class="d-none" style="padding: .02rem; text-align: center; width:50px;"><input type="text" name="igstamt[]" class="form-control igstcal igstamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  
                  <td style="padding: .02rem; text-align: center; width:90px;"><input type="text" name="distprt[]" class="form-control distcal distprt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  <td style="padding: .02rem; text-align: center; width:90px;"><input type="text" name="distamt[]" class="form-control distcal distamt_td" onKeyPress="return isFloatNumber(event,this);" /></td>
                  
                  <td style="padding: .02rem; text-align: center; width:150px;">
                    <input type="text" name="amount[]" class="form-control payableamount payableamount_td" onkeypress="return isNumberKey(event)"  required readonly>
                    <input type="hidden" name="roundoffamount[]" class="roundoffamount_td" value="0.00" />
                  </td>
                  <td style="padding: .02rem; text-align: center; width:150px;"><input type="date" name="expecteddelivery[]" class="form-control expecteddelivery"  required /></td>
                  <td style="padding: .02rem; text-align: center; width:60px;"><button type="button" class="btn btn-danger btn-sm removeRow">X</button></td>
                </tr>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="6">
                  </th>
                  <th colspan="2">
                    <button type="button" class="btn btn-success btn-sm float-right" id="addRow">+ Add Item</button>
                  </th>
                </tr>
                <tr class="d-none">
                  <td colspan="3"></td>
                  <td colspan="2">Total</td>
                  <td id="totalamount"></td>
                </tr>
                <tr class="d-none">
                  <td colspan="3"></td>
                  <td colspan="2">Total GST</td>
                  <td id="gsttotalamount"></td>
                </tr>
                <tr>
                  <td colspan="3"></td>
                  <td colspan="2">Total Discount</td>
                  <td id="ttldiscountamount">0</td>
                </tr>
                <tr>
                  <td colspan="3"></td>
                  <td colspan="2">Total Payable Amount</td>
                  <td id="grandtotalamount">0</td>
                </tr>
                <tr>
                  <th colspan="2">
                    <label>Freight Charges</label>
                    <input type="text" class="form-control" name="freight_amount" id="freight_amount" />
                  </th>
                  <th colspan="2">
                    <label>Discount (%)</label>
                    <input type="text" class="form-control" name="discount_percent" id="discount_percent" />
                  </th>
                  <th colspan="2">
                  <label>Payable Freight Charges</label>
                    <input type="text" class="form-control" name="payablefreight_amount" id="payablefreight_amount" readonly />
                  </th>
                  <th colspan="2"></th>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-12 mt-2" id="deliveryaddressdiv" style="display: none;">
          <label>Client Delivery Address</label>
          <div class="table-responsive">
            <table class="table table-bordered table-striped" id="deliveryaddresstbl">
              <thead>
                <tr>
                  <th>Delivery Address</th>
                  <th style="width:100px;">No of Trays</th>
                  <th style="width:150px;">Cost of Delivery</th>
                  <th style="width:350px;">GPS Location</th>
                  <th style="width:60px;">Action</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
        <div class="col-md-12 mt-2">
          <label><input type="checkbox" id="isadddeliveryaddress" /> Do you add delivery address?</label>
        </div>
      </div>
      <div class="row mb-3">
        <div class="col-md-12">
          <label class="form-label">Remarks</label>
          <textarea name="remarks" class="form-control"></textarea>
        </div>
    </div>

    <div class="row mb-3">
      <div class="col-md-4"></div>
      <div class="col-md-4 text-center">
        <input type="hidden" name="companyid" value="<?php echo $companyid; ?>" />
        <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>" />
        <input type="hidden" name="projectid" value="<?php echo $projectid; ?>" />
        <input type="hidden" name="loginid" value="<?php echo $loginid; ?>" />
        <input type="hidden" id="clientid" name="clientid" value="" />
        <button type="submit" class="btn btn-primary">Save Provisional Order</button>
      </div>
      <div class="col-md-4"></div>
    </div>
  </form>

  <div class="modal fade" id="adddeliveryaddressModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Delivery Address</h5>
          <button type="button" class="close" onclick="$('#adddeliveryaddressModal').modal('hide')">&times;</button>
        </div>
        <div class="modal-body">
          <div class="row">
            <div class="col-md-12">
              <label>Delivery Address</label>
              <textarea class="form-control" rows="2" id="deliveryaddresslocation"></textarea>
            </div>
            <div class="col-md-12">
              <label>No of Tray</label>
              <input type="text" class="form-control" onkeypress="return isNumberKey(event)" id="nooftrays" />
            </div>
            <div class="col-md-12">
              <label>Cost of Delivery</label>
              <input type="text" class="form-control"  onkeypress="return isNumberKey(event)" id="costofdelivery" />
            </div>
            <div class="col-md-12">
              <label>GPS Location (if available)</label>
              <input type="text" class="form-control" id="gpslocationurl" />
            </div>
          </div>
          <div class="row mt-4">
            <div class="col-md-12 text-center">
              <button type="button" class="btn btn-deepblue" id="appenddeliveryaddressbtn">ADD</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include('../footer.php'); ?>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/invoice_calculation.js"></script>

<script>
  // mobileno
  // clientname
// Set today's date as min
document.addEventListener("DOMContentLoaded", function() {
  let today = new Date().toISOString().split("T")[0];
  document.querySelectorAll(".expecteddelivery").forEach(function(input) {
    input.setAttribute("min", today);
  });
});


$(document).on("click", "#isadddeliveryaddress", function(){
  $("#adddeliveryaddressModal").modal("hide");
  if($(this).is(":checked")){
    $("#adddeliveryaddressModal").modal("show");
  }
});


$(document).on("click", "#appenddeliveryaddressbtn", function(){
  var deliveryaddresslocation = $("#deliveryaddresslocation").val();
  var nooftrays = $("#nooftrays").val();
  var costofdelivery = $("#costofdelivery").val();
  var gpslocationurl = $("#gpslocationurl").val();
  if(!deliveryaddresslocation){
    alertmessage('warning', 'Delivery address is required');
    return false;
  }
  $("#deliveryaddresstbl tbody").append('<tr><td><input type="text" name="address[]" class="form-control" value="'+deliveryaddresslocation+'" required /> <input type="hidden" name="addressid[]" class="form-control" value=""></td><td><input type="number" name="trays[]" class="form-control" value="'+nooftrays+'"></td><td><input type="number" name="cost[]" class="form-control" value="'+costofdelivery+'"></td><td><input type="text" name="gps[]" class="form-control" value="'+gpslocationurl+'"></td><td><button type="button" class="btn btn-sm rmdeliveryaddbtn"><i class="fa fa-trash"></span></button></td></tr>');
  $("#deliveryaddressdiv").show();
  Swal.fire({
    title: "Add Another Address?",
    text: "Delivery address has been added successfully!",
    icon: "success",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, Add More",
    cancelButtonText: "No, Done"
  }).then((result) => {
    // Clear input fields
    $("#deliveryaddresslocation").val('');
    $("#nooftrays").val('');
    $("#costofdelivery").val('');
    $("#gpslocationurl").val('');

    if (result.isConfirmed) {
      // User wants to add more – keep form ready
      $("#deliveryaddresslocation").focus();
    } else {
      $("#adddeliveryaddressModal").modal("hide");
      $("#isadddeliveryaddress").prop("checked", false);
      // User is done – optionally close modal or show summary
      Swal.fire({
        title: "All Done!",
        text: "You’ve successfully added delivery details.",
        icon: "info",
        timer: 500,
        showConfirmButton: false
      });
    }
  });
})

$(document).on("click", ".rmdeliveryaddbtn", function() {
    $(this).closest("tr").remove();
});

// check client name exist as per given mobile no
$(document).on("blur", "#mobileno", function() {
  if(!$("#delivery_address").val()){
    let mobile = $("#mobileno").val().trim();
    if (mobile === "") {
      console.log("Mobile number is empty, skipping callback.");
      return;
    }
    // Check if it's a 10-digit number
    let mobilePattern = /^[0-9]{10}$/;
    if (!mobilePattern.test(mobile)) {
      alertmessage("warning","Please enter a valid 10-digit mobile number.");
      $("#mobileno").focus();
      return;
    }
    // Call only if valid
    GetClientDetailsFrommaster_Callback("mobileno", mobile, function(res) {
      var existcl = "";
      if (res.length > 0) {
        existcl += '<b class="mb-2 ml-4 mt-2 text-primary">Already Registerd Client With Mobile No '+mobile+'</b>';
        for (var i = 0; i < res.length; i++) {
          existcl += '<div class="d-flex align-items-center p-2 rounded shadow border-bottom border-info" style="cursor: pointer;">' +
                       '<div class="mr-3">' +
                         '<input type="radio" class="existclient" name="existclient" ' +
                         'data-clientid="' + res[i].client_id + '" ' +
                         'data-clientname="' + res[i].client_name + '" ' +
                         'data-mobileno="' + res[i].phones + '" ' +
                         'data-emailaddress="' + res[i].client_email + '" ' +
                         'data-address="' + res[i].invoicing_address + '" ' +
                         'data-statename="' + res[i].statename + '" ' +
                         'data-districtname="' + res[i].districtname + '" />' +
                       '</div>' +
                       '<div>' +
                         '<p class="fs-12 mb-1"><b>Name:</b> ' + res[i].client_name + ',  <b>Mobile No: </b> ' + res[i].phones + ', <b>Address: </b>' + res[i].invoicing_address + '</p>' +
                       '</div>' +
                     '</div>';
        }
        existcl += '<div class="d-flex align-items-center p-2 rounded shadow border-bottom border-info" style="cursor: pointer;">' +
         '<div class="mr-3">' +
           '<input type="radio" class="existclient" name="existclient" ' +
           'data-clientid="none" ' +
           'data-clientname="none" ' +
           'data-mobileno="none" ' +
           'data-emailaddress="none" ' +
           'data-address="none" ' +
           'data-statename="none" ' +
           'data-districtname="none" />' +
         '</div>' +
         '<div>' +
           '<p class="fs-12 mb-1"><b>None</b> </p>' +
         '</div>' +
       '</div>';
        if (res.length > 0 && existcl){
            $("#alreadyexistclientdataview").html(existcl);
            $("#alreadyexistclientdataview").show();
        } else if (res.length == 1 && existcl) {
            $("#alreadyexistclientdataview").html(existcl);
            $("#alreadyexistclientdataview").hide();
            $("input[name='existclient']").prop('checked', true).trigger('change');
        }        
      }
    });
  }
});
// check client name exist or not
$(document).on("blur", "#clientname", function() {
  if(!$("#delivery_address").val()){
    let clientname = $("#clientname").val().trim();
    if (clientname === "") {
      console.log("Client is empty, skipping callback.");
      return;
    }
    // Call only if valid
    GetClientDetailsFrommaster_Callback("clientname", clientname, function(res) {
      var existcl = "";
      if (res.length > 0) {
        existcl += '<div class="d-flex align-items-center p-2 rounded shadow border-top border-bottom border-info"><b class="mb-2 ml-4 mt-2 text-primary">Already Registerd Client With Mobile No '+clientname+'</b>';
        for (var i = 0; i < res.length; i++) {
          existcl += '<div class="mr-3">' +
                         '<input type="radio" class="existclient" name="existclient" ' +
                         'data-clientid="' + res[i].client_id + '" ' +
                         'data-clientname="' + res[i].client_name + '" ' +
                         'data-mobileno="' + res[i].phones + '" ' +
                         'data-emailaddress="' + res[i].client_email + '" ' +
                         'data-address="' + res[i].invoicing_address + '" ' +
                         'data-statename="' + res[i].statename + '" ' +
                         'data-districtname="' + res[i].districtname + '" />' +
                       '</div>' +
                       '<div>' +
                         '<p class="fs-12 mb-1"><b>Name:</b> ' + res[i].client_name + ',  <b>Mobile No: </b> ' + res[i].phones + ', <b>Address: </b>' + res[i].invoicing_address + '</p>' +
                       '</div>' +
                     '</div>';
        }
        existcl +='</div>';
        if (existcl) {
          $("#alreadyexistclientdataview").html(existcl);
        }
      }

    });
  }
});

$(document).on("click", "input[name='existclient']", function () {
  const clientId = $(this).data("clientid");
  // Reset all input fields first
  $("#clientid, #clientname, #mobileno, #emailid, #delivery_address, #districtname, #statename")
    .val('').prop("readonly", false);

  if (clientId && clientId !== "none") {
    // Populate fields with existing client data
    $("#clientid").val(clientId);
    $("#clientname").val($(this).data("clientname") || '');
    $("#mobileno").val($(this).data("mobileno") || '');
    $("#emailid").val($(this).data("emailaddress") || '');
    $("#delivery_address").val($(this).data("address") || '');
    $("#districtname").val($(this).data("districtname") || '');
    $("#statename").val($(this).data("statename") || '');
    // Lock primary client info fields
    $("#mobileno, #clientname, #emailid").prop("readonly", true);
    GetClientDeliveryAddressData(clientId,function(res){
      if(res.length>0){
        $("#deliveryaddressdiv").show();
         for (var i = 0; i < res.length; i++) {
          // id, projectid, psono, sono, clientid, nooftray, deliverycharges, deliveryaddress, gpslocation, vehicleno, drivername, drivercontactno, status, tedit, created_by, created_at, updated_at
           $("#deliveryaddresstbl tbody").append('<tr><td><input type="text" name="address[]" class="form-control" value="'+res[i].deliveryaddress+'" required /> <input type="hidden" name="addressid[]" class="form-control" value="'+res[i].id+'"></td><td><input type="number" name="trays[]" class="form-control" value="'+res[i].nooftray+'"></td><td><input type="number" name="cost[]" class="form-control" value="'+res[i].deliverycharges+'"></td><td><input type="text" name="gps[]" class="form-control" value="'+res[i].gpslocation+'"></td><td><button type="button" class="btn btn-sm rmdeliveryaddbtn"><i class="fa fa-trash"></span></button></td></tr>');
         }
      }
    });
  }
});

GetProductListFrommaster_callback(function(res){
  if(res.length>0){
      var prdlist = "";
        prdlist +='<option value="" selected disabled>Select</option>';
      for (var i = 0; i < res.length; i++) {
        prdlist +='<option value="'+res[i].ProductID+'" data-priceofproduct="'+res[i].PriceOfProduct+'">'+res[i].ProductName+'</option>';
      }
      if(prdlist){
        $(".productname").append(prdlist);
      }
    }
});

$(document).ready(function(){
  // Recalculate total when a row is added (example)
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

  
  // Submit form using AJAX
  $("#salesOrderForm").submit(function (e) {
    e.preventDefault();
    var key = $("body").data("key");
    var mdid = "<?php echo $mdid; ?>"; // ensure this is inside a PHP file

    $.ajax({
        url: "modal/save_provisionalsales_order.php?action=provisionalsalesorder", // PHP backend
        type: "POST",
        data: $(this).serialize(),
        dataType: "json",
        success: function (response) {
          if (response.status === "success") {
            if(response.clientid && response.psono){
              SendEmailProvisionalOrderDetails(response.clientid,response.psono,function(eres){
                // console.log(eres);
              });
            }

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
                window.location.reload();
              } else if (result.isDenied) {
                // 🔑 Redirect when user clicks NO
                window.location.href = "provisionalsaleorder_review.php?k=" + key + "&mdid=" + mdid;
              }
            });
          } else {
            alert("Error: " + response.message);
          }
        },
        error: function () {
          alert("An error occurred while saving the order.");
        }
    });
  });


  $(document).on("click", ".gotoprovisionalorderlist", function() {
      let companyid = $("body").data("companyid");
      let subcompanyid = $("body").data("subcompanyid");
      let projectid = $("body").data("projectid");
      let routurl = $("body").data("routurl");
      let key = $("body").data("key");
      window.location.href=routurl+"krf-orderandsales/provisionalsaleorder_review.php?k="+key+"&mdid=<?php echo $mdid; ?>&psono=";
    });
});
</script>
