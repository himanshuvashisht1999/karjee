<?php
$psodate ="";
$clientname ="";
$mobileno ="";
$emailid ="";
$deliverydate ="";
$psoamount ="";
if(isset($_GET['dz'])){
  require('../connection.php');
  require('../functions/function_master.php');
  $key=$_GET['k'];
  $key_decoded = base64url_decode($key);
  $key_parts = explode("|",$key_decoded);
  $checkkey = base64url_decode($key_parts[count($key_parts)-1]);
  $actkey = "";
  for($i=0;$i<count($key_parts)-1 ;$i++){
    $actkey = $actkey?$actkey."|".$key_parts[$i]:$key_parts[$i];
  }
  if($actkey!=$checkkey){
    echo"<div class=\"col-md-12 text-center black\"><h3>OOPS!!Something went wrong. Please Try after sometime.</h3></div>";
    die;
  }
  $loginname ="";
  $loginid = $key_parts[0];
  $loginroleid = (int)$key_parts[1];
  $companyid = $key_parts[2];
  $subcompanyid = $key_parts[3];
  $projectid = $key_parts[4];
  $logintype = $key_parts[5];
  $newkey = "x|x|".$key_parts[2]."|".$key_parts[3]."|".$key_parts[4]."|x";
  $encodednewkey = base64url_encode($newkey);
  $keycompany=base64url_encode($newkey."|".$encodednewkey);
  $cmpinfo=getcompanydetails($mconn,"companyid",$companyid);
   $companyname="";
   if(count($cmpinfo)>0){
    foreach ($cmpinfo as $cmphkey) {
      $companyname=$cmphkey['company_name'];
      $countryid=$cmphkey['countryid'];
    }
  }

  $subcmpinfo=getsubcompanydetails($mconn,$companyid,$subcompanyid,"subcompanyid",$subcompanyid);
  $subcompanyname="";
  if(count($subcmpinfo)>0){
    foreach ($subcmpinfo as $subcmphkey) {
      $subcompanyname=$subcmphkey['subcompanyname'];
    }
  }

  //projectinfo
  $prj=getprojectdetails($conn,$companyid,$subcompanyid,"projectid",$projectid);
  $projectname ="";
  if(count($prj)>0){
    foreach ($prj as $pjkey) {
      $projectname = $pjkey['projectname'];
    }
  }

  $protocol=$_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
  $url = basename( $_SERVER["PHP_SELF"]);
  $rooturl = $protocol.'://'.$_SERVER['HTTP_HOST'];
  $baseurl= $rooturl.str_replace($url, "", $_SERVER["PHP_SELF"]);
  $countryid = "";
  if($_SERVER['HTTP_HOST']=="localhost"){
    $prefix=$rooturl."/krf_amanaccounting/";
  }else{
    $prefix=$rooturl."/";
  }
  $rooturl =$prefix;
}else{
  require('../header.php');
  $rooturl; 
}
include('functions/orders_functions.php');
include('functions/product_functions.php');

$mdid ="";
if(isset($_GET['mdid'])){
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn,$companyid,$subcompanyid,$projectid,"moduleid",$mdid);
$groupid ="";
if(count($dddr)>0){
  $groupid = $dddr[0]['groupid'];
}
$currentLoginRoleId = isset($loginroleid) ? (int)$loginroleid : 4;
?>
<style>
.production-card {
  border-radius: 12px;
  border: 1px solid #e2e8f0;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
  background: #ffffff;
}

.production-card .card-header {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
  color: #fff;
  padding: 16px 22px;
  border-radius: 12px 12px 0 0;
}

.production-card .card-header h4 {
  font-weight: 700;
  font-size: 1.15rem;
  letter-spacing: 0.3px;
}

.production-table {
  border-radius: 8px;
  overflow: hidden;
}

.production-table thead {
  background: linear-gradient(135deg, #e2f2fe 0%, #dbeafe 100%) !important;
  color: #052c65 !important;
}

.production-table th {
  color: #052c65 !important;
  font-weight: 700;
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  padding: 12px 14px;
  border: none;
  vertical-align: middle;
}

.production-table td {
  vertical-align: middle;
  padding: 10px 12px;
  border-color: #f1f5f9;
}

.action-icon {
  cursor: pointer;
}

.btn-add-item {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
  border: none;
  color: #fff;
  font-weight: 600;
  padding: 6px 16px;
  border-radius: 6px;
  transition: all 0.2s;
}

.btn-add-item:hover {
  opacity: 0.9;
  transform: translateY(-1px);
  color: #fff;
}

.btn-save-order {
  background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
  border: none;
  color: #fff;
  font-weight: 700;
  padding: 10px 28px;
  border-radius: 8px;
  font-size: 15px;
  box-shadow: 0 4px 12px rgba(30, 60, 114, 0.25);
  transition: all 0.2s;
}

.btn-save-order:hover {
  opacity: 0.95;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(30, 60, 114, 0.35);
  color: #fff;
}

.btn-outline-danger {
  border-radius: 50%;
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
}

.role-badge {
  font-size: 0.75rem;
  padding: 5px 12px;
  border-radius: 20px;
  font-weight: 600;
}

/* Buffer Column Redesign Styles */
.buffer-cell-container {
  display: flex !important;
  flex-direction: column !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 4px !important;
  width: 100% !important;
  min-width: 155px !important;
  padding: 1px 0 !important;
}

.buffer-toggle-box {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 6px !important;
  margin: 0 !important;
  padding: 3px 8px !important;
  background-color: #f8fafc !important;
  border: 1px solid #cbd5e1 !important;
  border-radius: 6px !important;
  cursor: pointer !important;
  user-select: none !important;
  transition: all 0.2s ease-in-out !important;
  box-sizing: border-box !important;
  position: relative !important;
  line-height: 1 !important;
}

.buffer-toggle-box:hover {
  background-color: #f1f5f9 !important;
  border-color: #94a3b8 !important;
}

.buffer-toggle-box.is-checked {
  background-color: #fef2f2 !important;
  border-color: #fca5a5 !important;
}

/* Hide native checkbox input completely so it cannot overlap or resize */
.buffer-toggle-box input[type="checkbox"].remove_buffer_chk {
  position: absolute !important;
  opacity: 0 !important;
  width: 0 !important;
  height: 0 !important;
  margin: 0 !important;
  padding: 0 !important;
  pointer-events: none !important;
  visibility: hidden !important;
}

/* Custom Checkbox Square (compact 13px) */
.custom-check-indicator {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  width: 13px !important;
  height: 13px !important;
  min-width: 13px !important;
  max-width: 13px !important;
  min-height: 13px !important;
  max-height: 13px !important;
  background-color: #ffffff !important;
  border: 1.5px solid #64748b !important;
  border-radius: 3px !important;
  box-sizing: border-box !important;
  flex-shrink: 0 !important;
  transition: all 0.15s ease-in-out !important;
  position: relative !important;
  margin: 0 !important;
}

.buffer-toggle-box:hover .custom-check-indicator {
  border-color: #334155 !important;
}

.buffer-toggle-box input[type="checkbox"].remove_buffer_chk:checked + .custom-check-indicator,
.buffer-toggle-box.is-checked .custom-check-indicator {
  background-color: #dc2626 !important;
  border-color: #dc2626 !important;
}

.custom-check-indicator::after {
  content: "" !important;
  display: none !important;
  width: 3px !important;
  height: 6px !important;
  border: solid #ffffff !important;
  border-width: 0 1.5px 1.5px 0 !important;
  transform: rotate(45deg) translate(-0.5px, -0.5px) !important;
  box-sizing: border-box !important;
}

.buffer-toggle-box input[type="checkbox"].remove_buffer_chk:checked + .custom-check-indicator::after,
.buffer-toggle-box.is-checked .custom-check-indicator::after {
  display: block !important;
}

.buffer-label-text {
  font-size: 0.74rem !important;
  font-weight: 600 !important;
  color: #334155 !important;
  margin: 0 !important;
  padding: 0 !important;
  line-height: 1 !important;
  display: inline-block !important;
  white-space: nowrap !important;
  pointer-events: none !important;
}

.buffer-toggle-box.is-checked .buffer-label-text {
  color: #b91c1c !important;
}

.buffer-days-tag {
  display: block !important;
  text-align: center !important;
  width: 100% !important;
}

.growing-days-display {
  display: inline-flex !important;
  align-items: center !important;
  justify-content: center !important;
  gap: 4px !important;
  font-size: 0.75rem !important;
  padding: 3px 8px !important;
  border-radius: 6px !important;
  font-weight: 600 !important;
  white-space: nowrap !important;
  line-height: 1.3 !important;
  box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
}
</style>

<div class="container">
  <div class="row <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-12">
      <button class="btn btn-sm btn-deepblue m-2 float-right" onclick="location.reload();">Refresh</button>
    </div>
  </div>
  
  <div class="card production-card mt-2 mb-4">
    <div class="card-header d-flex align-items-center justify-content-between">
      <h4 class="mb-0"><i class="fa fa-boxes me-2"></i>New Production Stock Order Details</h4>
      <?php if ($currentLoginRoleId <= 2) : ?>
        <span class="badge bg-warning text-dark role-badge">
          <i class="fa fa-calendar-check me-1"></i> Sowing Starts Today (Admin Mode)
        </span>
      <?php endif; ?>
    </div>
    
    <div class="card-body p-4">
      <form id="newProductStockOrderForm" method="POST" enctype="multipart/form-data" novalidate>
        <div class="row mb-4 align-items-end">
          <div class="col-md-12">
            <label class="form-label font-weight-bold text-dark fs-14 mb-2">
              <i class="fa fa-user me-1 text-primary"></i> Select Client <span class="text-danger">*</span>
            </label>
            <select class="searchselect form-control" style="width: 100%;" id="productionforclient" name="productionforclient"></select>
          </div>
        </div>
        
        <!-- Sowing Date Buffer Warning Alert -->
        <div id="bufferWarningAlert" class="alert alert-warning py-2.5 px-3 mb-3 border-warning d-flex align-items-center justify-content-between shadow-sm" style="display: none; border-radius: 8px; font-size: 0.88rem; background-color: #fffbeb;">
          <div>
            <i class="fa fa-exclamation-triangle text-warning me-2 mr-2"></i>
            <strong>Sowing Date Alert:</strong> Calculated Sowing Start Date falls before today's date (<span class="today-date-text fw-bold"></span>). You can check <strong>"Remove Buffer"</strong> on the affected item to reduce total growing duration by 7 days.
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-bordered align-middle production-table" id="ProductionOrderitemsTable" data-parentdivid="card2">
            <thead>
              <tr>
                <th style="min-width:240px;">Product Name <span class="text-danger">*</span></th>
                <th class="text-center" style="width:110px;">Quantity <span class="text-danger">*</span></th>
                <th class="text-center" style="min-width:150px;">Sowing Start Date <span class="text-danger">*</span></th>
                <th class="text-center" style="min-width:150px;">Expected Ready Date <span class="text-danger">*</span></th>
                <th class="text-center" style="min-width:165px;">Remove Buffer</th>
                <th class="text-center" style="width:65px;">Action</th>
              </tr>
            </thead>
            <tbody>
                <tr>
                  <td>
                    <select name="productname[]" class="form-control searchselect productname"></select>
                  </td>
                  <td>
                    <input type="text" name="quantity[]" class="form-control qty_td qtycal text-center fw-bold" onkeypress="return isFloatNumber(event,this);" placeholder="0" required>
                  </td>
                  <td>
                    <input type="date" name="sowingdate[]" class="form-control sowingdate text-center" required>
                    <small class="sowing-date-warn text-danger fw-bold d-none" style="font-size: 0.72rem; display: block; margin-top: 2px;"><i class="fa fa-clock me-1"></i>Before Today</small>
                  </td>
                  <td>
                    <input type="date" name="expecteddelivery[]" class="form-control expecteddelivery text-center" required>
                  </td>
                  <td class="text-center align-middle" style="min-width: 165px;">
                    <div class="buffer-cell-container">
                      <label class="buffer-toggle-box" title="Check to remove 7 days buffer from growing cycle">
                        <input type="checkbox" name="removebuffer[]" class="remove_buffer_chk" value="1">
                        <span class="custom-check-indicator"></span>
                        <span class="buffer-label-text">Remove Buffer (-7d)</span>
                      </label>
                      <input type="hidden" name="removebuffer_val[]" class="remove_buffer_val" value="0">
                      <div class="buffer-days-tag">
                        <span class="badge bg-light text-muted border growing-days-display">
                          <i class="fa fa-clock me-1"></i>-- Days
                        </span>
                      </div>
                    </div>
                  </td>
                  <td class="text-center">
                    <button type="button" class="btn btn-outline-danger btn-sm removeRow" title="Remove Row"> ✕</button>
                  </td>
                </tr>
            </tbody>
            <tfoot>
              <tr>
                <td colspan="4"></td>
                <td colspan="2" class="text-end">
                  <button type="button" class="btn btn-sm btn-add-item float-right" id="addRow">
                    <i class="fa fa-plus me-1"></i> Add Item
                  </button>
                </td>
              </tr>
            </tfoot>
          </table>
        </div>
        
        <input type="hidden" name="companyid" value="<?= $companyid ?>">
        <input type="hidden" name="subcompanyid" value="<?= $subcompanyid ?>">
        <input type="hidden" name="projectid" value="<?= $projectid ?>">
        <input type="hidden" name="loginid" value="<?= $loginid ?>">
        
        <div class="text-center mt-4">
          <button type="submit" class="btn btn-save-order px-5">
            <i class="fa fa-save me-2"></i> Save Production Stock Order
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="prdordotherModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="prdordotherModalheader"></h5>
        <button type="button" class="close" onclick="$('#prdordotherModal').modal('hide')">&times;</button>
      </div>
      <div class="modal-body" id="prdordotherModalContent"></div>
    </div>
  </div>
</div>

<?php
if(isset($_GET['dz'])){

}else{
  include('../footer.php');
}
?>
<script type="text/javascript" src="<?php echo $prefix;?>krf-orderandsales/js/pjs_function.js"></script>

<script>
var loginroleid = <?php echo $currentLoginRoleId; ?>;

function parseDateObj(dateStr) {
    if (!dateStr) return null;
    dateStr = String(dateStr).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
        var parts = dateStr.split('-');
        return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    }
    if (/^\d{2}-\d{2}-\d{4}$/.test(dateStr)) {
        var parts = dateStr.split('-');
        return new Date(parseInt(parts[2], 10), parseInt(parts[1], 10) - 1, parseInt(parts[0], 10));
    }
    var d = new Date(dateStr);
    return isNaN(d.getTime()) ? null : d;
}

function addDaysToDate(dateStr, days) {
    if (!dateStr || isNaN(days)) return '';
    var d = parseDateObj(dateStr);
    if (!d) return '';
    d.setDate(d.getDate() + parseInt(days));
    var month = '' + (d.getMonth() + 1);
    var day = '' + d.getDate();
    var year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
}

function subtractDaysFromDate(dateStr, days) {
    if (!dateStr || isNaN(days)) return '';
    var d = parseDateObj(dateStr);
    if (!d) return '';
    d.setDate(d.getDate() - parseInt(days));
    var month = '' + (d.getMonth() + 1);
    var day = '' + d.getDate();
    var year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
}

function getDaysBetween(dateStr1, dateStr2) {
    if (!dateStr1 || !dateStr2) return 0;
    var d1 = parseDateObj(dateStr1);
    var d2 = parseDateObj(dateStr2);
    if (!d1 || !d2) return 0;
    var diffTime = d2.getTime() - d1.getTime();
    return Math.round(diffTime / (1000 * 3600 * 24));
}

function formatToYYYYMMDD(dStr) {
    if (!dStr) return '';
    if (dStr.match(/^\d{4}-\d{2}-\d{2}$/)) return dStr;
    var d = parseDateObj(dStr);
    if (!d) return dStr;
    var month = '' + (d.getMonth() + 1);
    var day = '' + d.getDate();
    var year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
}

function updateRowGrowingDaysDisplay($row) {
    let $sowInput = $row.find(".sowingdate");
    let $expInput = $row.find(".expecteddelivery");
    let $badge = $row.find(".growing-days-display");
    let $chk = $row.find(".remove_buffer_chk");
    let $toggleBox = $row.find(".buffer-toggle-box");

    let isBufferRemoved = $chk.is(":checked");
    $row.find(".remove_buffer_val").val(isBufferRemoved ? "1" : "0");
    if (isBufferRemoved) {
        $toggleBox.addClass("is-checked");
    } else {
        $toggleBox.removeClass("is-checked");
    }

    let origDays = parseInt($sowInput.data("original-fullcycledays")) || parseInt($sowInput.data("fullcycledays")) || 0;
    if (!origDays) {
        let sowVal = $sowInput.val();
        let expVal = $expInput.val();
        if (sowVal && expVal) {
            origDays = getDaysBetween(sowVal, expVal);
            if (isBufferRemoved) origDays += 7;
        }
    }

    if (!origDays || origDays <= 0) {
        $badge.attr("class", "badge bg-light text-muted border growing-days-display")
              .html('<i class="fa fa-clock me-1"></i>-- Days');
        return;
    }

    $sowInput.data("original-fullcycledays", origDays);
    $expInput.data("original-fullcycledays", origDays);

    let effectiveDays = isBufferRemoved ? Math.max(1, origDays - 7) : origDays;

    $sowInput.data("fullcycledays", effectiveDays);
    $expInput.data("fullcycledays", effectiveDays);

    if (isBufferRemoved) {
        $badge.attr("class", "badge bg-warning-subtle text-dark border border-warning growing-days-display")
              .html(`<i class="fa fa-bolt me-1 text-warning"></i><strong>${effectiveDays} Days</strong> <span class="badge bg-danger text-white ms-1" style="font-size: 0.65rem;">-7d buffer</span>`);
    } else {
        $badge.attr("class", "badge bg-info-subtle text-primary border border-info-subtle growing-days-display")
              .html(`<i class="fa fa-clock me-1 text-primary"></i><strong>${effectiveDays} Days</strong> <span class="badge bg-success text-white ms-1" style="font-size: 0.65rem;">+7d buffer</span>`);
    }
}

function checkAllRowsSowingDates() {
    let now = new Date();
    let todayMidnight = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 0, 0, 0, 0);

    const months = ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
    let todayFormatted = String(now.getDate()).padStart(2, '0') + '-' + months[now.getMonth()] + '-' + now.getFullYear();
    $(".today-date-text").text(todayFormatted);

    let hasPastSowing = false;

    $("#ProductionOrderitemsTable tbody tr").each(function () {
        let $row = $(this);
        let sowingVal = $row.find(".sowingdate").val();
        let $warnLabel = $row.find(".sowing-date-warn");
        let $sowInput = $row.find(".sowingdate");

        if (sowingVal && String(sowingVal).trim() !== '') {
            let sowDateObj = parseDateObj(sowingVal);
            if (sowDateObj && sowDateObj.getTime() < todayMidnight.getTime()) {
                hasPastSowing = true;
                $warnLabel.removeClass("d-none");
                $sowInput.addClass("is-invalid text-danger border-danger");
            } else {
                $warnLabel.addClass("d-none");
                $sowInput.removeClass("is-invalid text-danger border-danger");
            }
        } else {
            $warnLabel.addClass("d-none");
            $sowInput.removeClass("is-invalid text-danger border-danger");
        }
    });

    if (hasPastSowing) {
        $("#bufferWarningAlert").slideDown(150);
    } else {
        $("#bufferWarningAlert").slideUp(150);
    }
}

$(document).ready(function(){
  // Initial bind for searchselect
  bindSearchSelect();

  // MutationObserver to handle dynamically added rows
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach(function(node) {
          if (node.nodeName === 'TR') {
            let $newRow = $(node);
            $newRow.find('.ss-container').remove();
            $newRow.find('.searchselect').show();
            bindSearchSelect();
            updateRowGrowingDaysDisplay($newRow);
          }
        });
      }
    });
  });

  const tableBody = document.querySelector('#ProductionOrderitemsTable tbody');
  if (tableBody) {
    observer.observe(tableBody, { childList: true });
  }

  $("#addRow").click(function(){
    addRowtbody("ProductionOrderitemsTable");
  });
  // Handle Enter key on quantity column: apply mouseout and focus out (blur)
  $("#ProductionOrderitemsTable").on("keydown", ".qty_td, .qtycal", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
      $(this).trigger("mouseout").trigger("mouseleave").trigger("blur").blur();
    }
  });

  // Prevent default enter key on other table inputs/selects
  $("#ProductionOrderitemsTable").on("keydown", "input:not(.qty_td):not(.qtycal), select", function (e) {
    if (e.key === "Enter") {
      e.preventDefault();
    }
  });

  $(document).on("click", ".removeRow", function () {
      if ($("#ProductionOrderitemsTable tbody tr").length > 1) {
          $(this).closest("tr").remove();
      } else {
          let $r = $(this).closest("tr");
          $r.find("input").val("").removeAttr("min").prop("checked", false);
          $r.find("select").val("").prop("selectedIndex", 0).trigger("change");
          updateRowGrowingDaysDisplay($r);
      }
      checkAllRowsSowingDates();
  });

  GetProductListFrommaster_callback(function(res){
    if(res && res.length > 0){
        var prdlist = '<option value="" selected disabled>Select</option>';
        for (var i = 0; i < res.length; i++) {
          var rawName = res[i].ProductName || '';
          var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
          prdlist += '<option value="'+res[i].ProductID+'" data-priceofproduct="'+res[i].PriceOfProduct+'">'+displayName+'</option>';
        }
        if(prdlist){
          $(".productname").append(prdlist);
          $(".productname").trigger('optionsChanged');
        }
      }
  });

  GetclientDetailsFrommaster('all', 'all', 'productionforclient', null, function() {
      $("#productionforclient").trigger('optionsChanged');
  });

  // When product is selected, calculate Sowing & Ready dates based on role and seed data
  $(document).on("change", "#ProductionOrderitemsTable .productname", function () {
      const $row = $(this).closest("tr");
      const productid = $(this).val();
      let $sowingInput = $row.find(".sowingdate");
      let $expectedInput = $row.find(".expecteddelivery");
      let $bufferChk = $row.find(".remove_buffer_chk");

      // Reset
      $sowingInput.val("").removeAttr("min").removeData("fullcycledays").removeData("original-fullcycledays");
      $expectedInput.val("").removeAttr("min").removeData("fullcycledays").removeData("original-fullcycledays");

      if (!productid) {
          updateRowGrowingDaysDisplay($row);
          checkAllRowsSowingDates();
          return;
      }

      getExpectedDelivertDateandSowingDate(productid, function (res) {
          if (!Array.isArray(res) || res.length === 0) return;
          const data = res[0];
          let fullDays = parseInt(data.fullcycledays) || 0;

          if (data.expecteddeliverydate || data.rootsowingdate) {
              let formattedReady = formatToYYYYMMDD(data.expecteddeliverydate);
              let formattedSowing = formatToYYYYMMDD(data.rootsowingdate);

              if (!fullDays && formattedReady && formattedSowing) {
                  fullDays = getDaysBetween(formattedSowing, formattedReady);
              }

              $sowingInput.data("original-fullcycledays", fullDays);
              $expectedInput.data("original-fullcycledays", fullDays);

              let isBufferRemoved = $bufferChk.is(":checked");
              let effectiveDays = isBufferRemoved ? Math.max(1, fullDays - 7) : fullDays;
              $sowingInput.data("fullcycledays", effectiveDays);
              $expectedInput.data("fullcycledays", effectiveDays);

              // RULE: If loginroleid <= 2, sowing starts TODAY if seed details available
              if (loginroleid <= 2) {
                  let todayStr = new Date().toISOString().split("T")[0];
                  $sowingInput.val(todayStr);
                  $sowingInput.attr("min", todayStr);

                  if (effectiveDays > 0) {
                      let calcReady = addDaysToDate(todayStr, effectiveDays);
                      $expectedInput.val(calcReady);
                      $expectedInput.attr("min", calcReady);
                  } else if (formattedReady) {
                      $expectedInput.val(formattedReady);
                      $expectedInput.attr("min", formattedReady);
                  }
              } else {
                  if (formattedSowing) $sowingInput.val(formattedSowing);
                  if (formattedReady) {
                      $expectedInput.val(formattedReady);
                      $expectedInput.attr("min", formattedReady);
                  }
              }
              updateRowGrowingDaysDisplay($row);
              checkAllRowsSowingDates();
          }
      });
  });

  // Dynamic recalculation when Remove Buffer checkbox is toggled
  $(document).on("change", "#ProductionOrderitemsTable .remove_buffer_chk", function () {
      const $row = $(this).closest("tr");
      let isChecked = $(this).is(":checked");
      let $sowInput = $row.find(".sowingdate");
      let $expInput = $row.find(".expecteddelivery");

      let originalDays = parseInt($sowInput.data("original-fullcycledays")) || parseInt($sowInput.data("fullcycledays")) || 0;
      if (!originalDays) {
          let expVal = $expInput.val();
          let sowVal = $sowInput.val();
          if (expVal && sowVal) {
              originalDays = getDaysBetween(sowVal, expVal);
              if (!isChecked) originalDays += 7;
          }
      }
      if (originalDays > 0) {
          $sowInput.data("original-fullcycledays", originalDays);
          $expInput.data("original-fullcycledays", originalDays);
      }

      let effectiveDays = isChecked ? Math.max(1, originalDays - 7) : originalDays;
      $sowInput.data("fullcycledays", effectiveDays);
      $expInput.data("fullcycledays", effectiveDays);

      let readyVal = $expInput.val();
      let sowingVal = $sowInput.val();

      if (readyVal && effectiveDays > 0) {
          // Recalculate sowing date based on expected delivery date
          let calcSowing = subtractDaysFromDate(readyVal, effectiveDays);
          $sowInput.val(calcSowing);
      } else if (sowingVal && effectiveDays > 0) {
          // Or recalculate ready date based on sowing date
          let calcReady = addDaysToDate(sowingVal, effectiveDays);
          $expInput.val(calcReady);
          $expInput.attr("min", calcReady);
      }

      updateRowGrowingDaysDisplay($row);
      checkAllRowsSowingDates();
  });

  // Dynamic recalculation when Sowing Date is edited
  $(document).on("change", "#ProductionOrderitemsTable .sowingdate", function () {
      const $row = $(this).closest("tr");
      let sowingVal = $(this).val();
      let fullDays = parseInt($(this).data("fullcycledays")) || 0;
      let $expectedInput = $row.find(".expecteddelivery");

      if (sowingVal && fullDays > 0) {
          let calcReady = addDaysToDate(sowingVal, fullDays);
          $expectedInput.val(calcReady);
          $expectedInput.attr("min", calcReady);
      }
      updateRowGrowingDaysDisplay($row);
      checkAllRowsSowingDates();
  });

  // Dynamic recalculation when Expected Ready Date is edited
  $(document).on("change", "#ProductionOrderitemsTable .expecteddelivery", function () {
      const $row = $(this).closest("tr");
      let readyVal = $(this).val();
      let $sowingInput = $row.find(".sowingdate");
      let fullDays = parseInt($sowingInput.data("fullcycledays")) || 0;

      if (readyVal && fullDays > 0) {
          let calcSowing = subtractDaysFromDate(readyVal, fullDays);
          $sowingInput.val(calcSowing);
      }
      updateRowGrowingDaysDisplay($row);
      checkAllRowsSowingDates();
  });

  $("#newProductStockOrderForm").on("submit", function (e) {
    e.preventDefault();

    let clientVal = $("#productionforclient").val();
    if (!clientVal || clientVal.trim() === "" || clientVal === "0" || clientVal === "Select" || clientVal === "null") {
      Swal.fire({
        icon: "warning",
        title: "Client Selection Missing",
        text: "Please select a Client before saving the production order.",
        confirmButtonColor: "#1e3c72"
      });
      return false;
    }

    let isValid = true;
    let missingFieldMsg = "";

    $("#ProductionOrderitemsTable tbody tr").each(function(index) {
      let prd = $(this).find(".productname").val();
      let qty = $(this).find(".qty_td").val();
      let sow = $(this).find(".sowingdate").val();
      let exp = $(this).find(".expecteddelivery").val();

      if (!prd || prd.trim() === "" || prd === "0" || prd === "Select" || prd === "null") {
        isValid = false;
        missingFieldMsg = "Please select a Product Name for item " + (index + 1) + ".";
        return false;
      }
      if (!qty || parseFloat(qty) <= 0 || isNaN(parseFloat(qty))) {
        isValid = false;
        missingFieldMsg = "Please enter a valid Quantity for item " + (index + 1) + ".";
        return false;
      }
      if (!sow) {
        isValid = false;
        missingFieldMsg = "Please select Sowing Start Date for item " + (index + 1) + ".";
        return false;
      }
      if (!exp) {
        isValid = false;
        missingFieldMsg = "Please select Expected Ready Date for item " + (index + 1) + ".";
        return false;
      }
    });

    if (!isValid) {
      Swal.fire({
        icon: "warning",
        title: "Field Required",
        text: missingFieldMsg,
        confirmButtonColor: "#1e3c72"
      });
      return false;
    }

    const $form = $(this);
    Swal.fire({
      icon: "question",
      title: "Are you sure?",
      text: "Are you sure you want to create this Production Stock Order?",
      showCancelButton: true,
      confirmButtonText: "<i class='fa fa-check me-1'></i> Yes, Proceed to Save",
      cancelButtonText: "<i class='fa fa-times me-1'></i> Cancel",
      confirmButtonColor: "#1e3c72",
      cancelButtonColor: "#6c757d",
      reverseButtons: true,
      focusConfirm: true
    }).then((confirmRes) => {
      if (!confirmRes.isConfirmed) {
        return;
      }

      Swal.fire({
        title: "Saving Order...",
        text: "Please wait while the production stock order is being saved.",
        allowOutsideClick: false,
        didOpen: () => {
          Swal.showLoading();
        }
      });

      $.ajax({
        url: "modal/product_modal.php?action=SaveUpdateProductStockOrder",
        type: "POST",
        data: $form.serialize(),
        dataType: "json",
        success: function (response) {
            if (response.status === "success") {
              const item = response.items || {};
              const stockOrderNo = item.stockorder_no || 'N/A';
              Swal.fire({
                  icon: "success",
                  title: "Production Stock Order Saved Successfully!",
                  html: `
                      <div class="text-center">
                          <p class="mb-1 text-muted" style="font-size:14px;">Production Stock Order No:</p>
                          <h4 class="text-primary font-weight-bold mb-3">${stockOrderNo}</h4>
                          <hr class="my-3">
                          <p class="mb-0 font-weight-bold text-dark" style="font-size:15px;">Do you want to add more production order?</p>
                      </div>
                  `,
                  showCancelButton: true,
                  confirmButtonText: "<i class='fa fa-plus me-1'></i> YES, Add More",
                  cancelButtonText: "<i class='fa fa-sync me-1'></i> NO, Refresh Page",
                  confirmButtonColor: "#198754",
                  cancelButtonColor: "#6c757d",
                  allowOutsideClick: false
              }).then((result) => {
                  if (result.isConfirmed) {
                      $form[0].reset();
                      $("#ProductionOrderitemsTable tbody tr:gt(0)").remove();
                      let $firstRow = $("#ProductionOrderitemsTable tbody tr:first");
                      $firstRow.find("input").val("").removeAttr("min");
                      $firstRow.find("select").val("").prop("selectedIndex", 0);
                      $("#productionforclient").val("").prop("selectedIndex", 0);
                      $(".searchselect").trigger("change");
                      $(".searchselect").each(function() {
                          let text = $(this).find('option:selected').text();
                          if (!text || text.trim() === '' || text === 'Select') {
                              if ($(this).attr('id') === 'productionforclient') {
                                  text = 'Select client name';
                              } else {
                                  text = 'Select';
                              }
                          }
                          $(this).next('.ss-container').find('.ss-trigger').text(text);
                      });
                  } else {
                      location.reload();
                  }
              });
          } else {
              Swal.fire({
                  icon: "error",
                  title: "Save Failed",
                  text: response.message || "Something went wrong. Please try again."
              });
          }
        },
        error: function () {
            Swal.fire({
                icon: "error",
                title: "Server Error",
                text: "Unable to save Production Stock Order. Please try again later."
            });
        }
      });
    });
  });

  $(document).on("click", ".addClientbtn", function() {
    let routurl = $("body").data("routurl");
    let key = $("body").data("key");
    $("#prdordotherModalheader").html("New Client Registration");
    $("#prdordotherModalContent").load(routurl+"krf-orderandsales/addclient.php?k="+key+"&dz=11");
    $("#prdordotherModal").modal("show");
  });
});
</script>
