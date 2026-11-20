<?php
$today = date('Y-m-d');
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
include('functions/client_functions.php');
$stockorder_no = $_GET['stockorder_no'] ?? '';
$order_details = [];
if ($stockorder_no != "") {
    $order_details = getFullProductionStockOrdersDetails($krfconn, (int)$projectid, 'stockorder_no', $stockorder_no);
}
$clientid = count($order_details) > 0 ? ($order_details[0]['clientid'] ?? '') : '';
$client_name = '';
if (!empty($clientid)) {
    $client_info = getClientDetailsInfo($krfconn, $companyid, $subcompanyid, $projectid, "clientid", $clientid);
    if (!empty($client_info)) {
        $client_name = $client_info[0]['clientname'] ?? $client_info[0]['client_name'] ?? '';
    }
}


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
$today = date('Y-m-d');

$has_past_delivery = false;
$is_order_grafted = false;
if (count($order_details) > 0) {
    foreach ($order_details as &$chkItem) {
        $edd = (!empty($chkItem['expected_delivery_date']) && $chkItem['expected_delivery_date'] !== '0000-00-00') ? $chkItem['expected_delivery_date'] : '';
        if (!empty($edd) && $edd < $today) {
            $has_past_delivery = true;
        }

        // Fetch product info to resolve if Grafted or Non-Grafted
        $pid = $chkItem['productid'];
        $pinfo = getProductData($krfconn, $projectid, "ProductID", $pid);
        $isGraftedVal = 0;
        if (count($pinfo) > 0) {
            $isGraftedVal = (int)($pinfo[0]['IsGrafted'] ?? 0);
            if ($isGraftedVal == 1 || strtolower($pinfo[0]['IsGrafted'] ?? '') == 'yes' || strtolower($pinfo[0]['IsGrafted'] ?? '') == 'grafted') {
                $isGraftedVal = 1;
            }
        }
        if ($isGraftedVal == 0 && isset($chkItem['product_name']) && stripos($chkItem['product_name'], 'Grafted') !== false) {
            $isGraftedVal = 1;
        }
        $chkItem['IsGraftedResolved'] = $isGraftedVal;
        if ($isGraftedVal == 1) {
            $is_order_grafted = true;
        }
    }
    unset($chkItem);
}
?>
<style>
/* Modern Glass & Card Container */
.production-card {
  border: none;
  border-radius: 16px;
  box-shadow: 0 12px 32px rgba(15, 23, 42, 0.08), 0 2px 6px rgba(15, 23, 42, 0.04);
  background: #ffffff;
  overflow: hidden;
  transition: all 0.3s ease;
}

.production-card .card-header {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #1e3a8a 100%);
  color: #ffffff;
  padding: 20px 28px;
  border-bottom: none;
}

.production-card .card-header h4 {
  font-weight: 700;
  font-size: 1.2rem;
  letter-spacing: 0.3px;
  display: flex;
  align-items: center;
  gap: 10px;
}

/* Order Meta Top Bar */
.order-meta-card {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 18px 20px;
  margin-bottom: 24px;
}

.order-badge-pill {
  font-family: 'Courier New', Courier, monospace;
  font-size: 1.05rem;
  font-weight: 700;
  color: #1e40af;
  background: #dbeafe;
  padding: 6px 14px;
  border-radius: 8px;
  border: 1px solid #bfdbfe;
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

/* Production Table */
.production-table-container {
  border-radius: 12px;
  overflow: hidden;
  border: 1px solid #e2e8f0;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
}

.production-table {
  margin-bottom: 0;
  width: 100%;
}

.production-table thead {
  background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
  color: #ffffff;
}

.production-table th {
  font-weight: 600;
  font-size: 12px;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  padding: 14px 16px;
  border: none !important;
  vertical-align: middle;
}

.production-table td {
  vertical-align: middle;
  padding: 12px 14px;
  border-color: #f1f5f9;
}

.production-table tbody tr.main-item-row {
  transition: background-color 0.2s ease, border-left 0.2s ease;
}

.production-table tbody tr.main-item-row.row-editing-active {
  background-color: #f0f9ff !important;
  border-left: 4px solid #2563eb;
}

.production-table tbody tr.item-subrow.row-editing-active {
  background-color: #f8fafc !important;
  border-left: 4px solid #2563eb;
}

/* Styled Form Inputs */
.form-control-styled-static {
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  padding: 8px 12px;
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  background-color: #f1f5f9;
  min-height: 38px;
  display: flex;
  align-items: center;
  word-break: break-word;
}

.form-control-styled {
  border-radius: 8px;
  border: 1px solid #cbd5e1;
  padding: 8px 12px;
  font-size: 14px;
  transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control-styled:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  outline: none;
}

/* Stage Status Badges / Chips */
.stage-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 600;
  background: #ffffff;
  border: 1px solid #e2e8f0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.stage-chip-sowing {
  border-left: 3px solid #10b981;
  color: #065f46;
}

.stage-chip-germination {
  border-left: 3px solid #f59e0b;
  color: #92400e;
}

.stage-chip-polyhouse {
  border-left: 3px solid #06b6d4;
  color: #155e75;
}

/* Checkbox Switch */
.chk-edit-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
}

.chkEnableRowEdit,
#chkAllowEditPastOrder {
  width: 15px !important;
  height: 15px !important;
  accent-color: #2563eb;
  cursor: pointer;
  border-radius: 4px;
}

.radEditChoice {
  width: 15px !important;
  height: 15px !important;
  accent-color: #2563eb;
  cursor: pointer;
  margin-right: 10px !important;
  margin-top: 3px !important;
  flex-shrink: 0;
}

/* Buttons */
.btn-update-order {
  background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
  border: none;
  color: #fff;
  font-weight: 700;
  padding: 12px 36px;
  border-radius: 10px;
  font-size: 15px;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
  transition: all 0.2s ease-in-out;
}

.btn-update-order:hover {
  background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
  transform: translateY(-2px);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
  color: #fff;
}

.grafted-option-card {
  border: 1px solid #cbd5e1 !important;
  background: #ffffff;
  border-radius: 10px;
  padding: 12px 18px 12px 14px !important;
  transition: all 0.2s ease-in-out;
}

.grafted-option-card:hover {
  border-color: #2563eb !important;
  background: #f8fafc !important;
  box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08) !important;
}

.btn-outline-danger-custom {
  border: 1px solid #fee2e2;
  background: #fef2f2;
  color: #ef4444;
  border-radius: 50%;
  width: 32px;
  height: 32px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: bold;
  transition: all 0.2s ease;
}

.btn-outline-danger-custom:hover {
  background: #ef4444;
  color: #ffffff;
  border-color: #ef4444;
  transform: scale(1.08);
}
</style>

<div class="container-fluid px-4 py-3">
  <div class="row align-items-center mb-3 <?php echo isset($_GET['dz'])?'d-none':'' ?>">
    <div class="col-md-12 d-flex justify-content-between align-items-center">
      <h5 class="fw-bold text-dark mb-0">
        <i class="fa fa-pen-to-square me-2 text-primary"></i> Edit Production Stock Order
      </h5>
      <button class="btn btn-sm btn-outline-primary shadow-sm px-3" onclick="location.reload();">
        <i class="fa fa-sync me-1"></i> Refresh
      </button>
    </div>
  </div>

  <?php if ($has_past_delivery) : ?>
    <!-- Past Delivery Warning & Override Checkbox -->
    <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center justify-content-between my-3 p-3 rounded-3" id="pastDateAlert" style="background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); border-left: 5px solid #f59e0b !important;">
        <div class="d-flex align-items-center me-3">
            <div class="bg-warning text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; flex-shrink: 0;">
              <i class="fa fa-exclamation-triangle" style="font-size: 20px;"></i>
            </div>
            <div>
                <h6 class="mb-1 fw-bold text-dark"><i class="fa fa-calendar-times me-1 text-danger"></i> Past Delivery Date Notice</h6>
                <small class="text-secondary fw-500">The expected delivery date for this production order has passed. Editing is locked by default for data safety.</small>
            </div>
        </div>
        <div class="bg-white p-2 px-3 rounded-3 border shadow-sm ms-2" style="white-space: nowrap;">
            <div class="form-check form-check-inline mb-0 d-flex align-items-center">
                <input class="form-check-input mt-0 me-2" type="checkbox" id="chkAllowEditPastOrder" style="cursor: pointer; width: 15px; height: 15px; margin-right: 8px; accent-color: #f59e0b;">
                <label class="form-check-label fw-bold text-dark mb-0 ms-1" for="chkAllowEditPastOrder" style="cursor: pointer; font-size: 14px; user-select: none;">Allow Edit Past Order</label>
            </div>
        </div>
    </div>
  <?php endif; ?>

  <form id="editProductStockOrderForm" method="POST" enctype="multipart/form-data" novalidate>
    <?php
      $total_order_qty = 0;
      if (!empty($order_details)) {
          foreach ($order_details as $od) {
              $total_order_qty += floatval($od['quantity']);
          }
      }
    ?>

    <!-- CARD 1: Production Order & Sowing Details -->
    <div class="card production-card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="mb-0"><i class="fa fa-info-circle me-2"></i> Production Order &amp; Sowing Details</h4>
        <?php if ($currentLoginRoleId <= 2) : ?>
          <span class="badge bg-warning text-dark" style="font-size: 0.75rem; padding: 6px 14px; border-radius: 20px; font-weight: 600;">
            <i class="fa fa-calendar-check me-1"></i> Admin Sowing Auto-Calculation Active
          </span>
        <?php endif; ?>
      </div>
      <div class="card-body p-4">
        <!-- Order Summary & Client Info Top Bar -->
        <div class="order-meta-card shadow-sm mb-4">
          <div class="row align-items-center">
            <div class="col-md-7 mb-3 mb-md-0">
              <div class="d-flex align-items-center gap-3 flex-wrap">
                <div>
                  <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Order Number</small>
                  <span class="order-badge-pill mt-1">
                    <i class="fa fa-hashtag text-primary"></i> <?= htmlspecialchars($stockorder_no) ?>
                  </span>
                </div>
                <div class="border-start ps-3 ms-1">
                  <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Actual Order Qty</small>
                  <span class="badge bg-primary text-white px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1 mt-1" style="font-size: 13px; border-radius: 8px; font-weight: 700;">
                    <i class="fa fa-layer-group"></i> <?= number_format($total_order_qty) ?> Plants
                  </span>
                </div>
                <div class="border-start ps-3 ms-1">
                  <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Order Type</small>
                  <div class="mt-1">
                    <?php if ($is_order_grafted) : ?>
                      <span class="badge bg-success text-white px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 13px; border-radius: 8px;">
                        <i class="fa fa-cut"></i> Grafted Production Order
                      </span>
                    <?php else : ?>
                      <span class="badge bg-info text-white px-3 py-2 shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 13px; border-radius: 8px;">
                        <i class="fa fa-seedling"></i> Non-Grafted Production Order
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="border-start ps-3 ms-1">
                  <small class="text-muted fw-bold text-uppercase d-block" style="font-size: 11px; letter-spacing: 0.5px;">Total Items</small>
                  <span class="fw-bold text-dark fs-15 mt-1 d-block"><?= count($order_details) ?> Line Item(s)</span>
                </div>
              </div>
            </div>
            <div class="col-md-5">
              <label class="form-label font-weight-bold text-dark mb-1" style="font-size: 13px;">
                <i class="fa fa-user me-1 text-primary"></i> Select Client <span class="text-danger">*</span>
              </label>
              <div class="form-control-styled-static bg-white border text-dark fw-bold" id="lbl_client_name"><?= htmlspecialchars($client_name ?: 'Karjee Farms') ?></div>
              <input type="hidden" id="clientid" name="clientid" value="<?= htmlspecialchars($clientid) ?>">
            </div>
          </div>
        </div>

        <!-- Div Cards Details Layout Container -->
        <div class="production-details-container">
          <?php if (count($order_details) > 0) : ?>
            <?php foreach ($order_details as $idx => $item) : ?>
              <?php
                $prod_qty = floatval($item['quantity']);
                $consumed_qty = floatval(getTotalConsumedQtyForPSO($krfconn, $projectid, $stockorder_no, $item['productid']));
                $is_fully_consumed = ($prod_qty > 0 && $consumed_qty >= $prod_qty) ? 1 : 0;
                $disbtn = ($consumed_qty > 0 || $is_fully_consumed) ? " d-none" : "";
                $disbtntxt = "";
                if ($is_fully_consumed) {
                  $disbtntxt = "<span class='badge bg-danger text-white px-2 py-1 shadow-sm fs-11' title='Order quantity is fully consumed and locked'><i class='fa fa-lock me-1'></i> Fully Consumed - Edit Locked</span>";
                } else if ($consumed_qty > 0) {
                  $disbtntxt = "<span class='badge bg-warning text-dark border px-2 py-1 fs-11 me-1'><i class='fa fa-dolly me-1'></i> Consumed: " . number_format($consumed_qty) . "</span><span class='badge bg-light text-secondary border px-2 py-1 fs-11'>Locked</span>";
                }
                $sowingDateVal = (!empty($item['sowingdate']) && $item['sowingdate'] !== '0000-00-00') ? $item['sowingdate'] : '';
                $scionSowingDateVal = (!empty($item['scionsowingdate']) && $item['scionsowingdate'] !== '0000-00-00') ? $item['scionsowingdate'] : '';
                $expectedDeliveryVal = (!empty($item['expected_delivery_date']) && $item['expected_delivery_date'] !== '0000-00-00') ? $item['expected_delivery_date'] : '';
                $item_is_grafted = $item['IsGraftedResolved'] ?? 0;

                if ((empty($sowingDateVal) || empty($scionSowingDateVal)) && !empty($expectedDeliveryVal)) {
                    if (function_exists('calculateSowingAndInternalDispatchDates')) {
                        $c_dates = calculateSowingAndInternalDispatchDates($krfconn, $expectedDeliveryVal, $projectid, $item['productid']);
                        if (!empty($c_dates) && isset($c_dates['status']) && $c_dates['status'] === 'success') {
                            if (empty($sowingDateVal) && !empty($c_dates['RootSeed']['sowingdate'])) {
                                $sowingDateVal = $c_dates['RootSeed']['sowingdate'];
                            }
                            if (empty($scionSowingDateVal) && !empty($c_dates['ScionSeed']['sowingdate'])) {
                                $scionSowingDateVal = $c_dates['ScionSeed']['sowingdate'];
                            }
                        }
                    }
                }

                $sowingDateDisplay = (!empty($sowingDateVal)) ? date('d-m-Y', strtotime($sowingDateVal)) : '-';
                $scionSowingDateDisplay = (!empty($scionSowingDateVal)) ? date('d-m-Y', strtotime($scionSowingDateVal)) : '-';
                $expectedDeliveryDisplay = (!empty($expectedDeliveryVal)) ? date('d-m-Y', strtotime($expectedDeliveryVal)) : '-';
                $is_past = (!empty($expectedDeliveryVal) && $expectedDeliveryVal < $today) ? 1 : 0;
              ?>
              <div class="production-item-card-details card shadow-sm border rounded-3 mb-4 overflow-hidden"
                   data-productid="<?= $item['productid'] ?>"
                   data-rowidx="<?= $idx ?>"
                   data-prodqty="<?= $prod_qty ?>"
                   data-consumedqty="<?= $consumed_qty ?>"
                   data-fullyconsumed="<?= $is_fully_consumed ?>"
                   data-ispast="<?= $is_past ?>"
                   data-isgrafted="<?= $item_is_grafted ?>"
                   data-sowingstarted="0"
                   data-sowingplants="0"
                   data-traycavity="0">
                
                <!-- Card Body: Read-only Labels -->
                <div class="card-body p-3">
                  <!-- Item Core Details Grid -->
                  <div class="row g-3 align-items-end mb-3">
                    <div class="col-md-<?= ($item_is_grafted == 1) ? '4' : '5' ?>">
                      <label class="form-label fw-bold text-dark fs-12 mb-1">
                        Product Name <span class="text-danger">*</span>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-2" style="font-size:11px;">
                          Actual Order Qty: <?= number_format($prod_qty) ?> Plants
                        </span>
                      </label>
                      <div class="form-control-styled-static bg-light text-dark fw-bold"><?= htmlspecialchars($item['product_name']) ?></div>
                    </div>

                    <div class="col-md-2">
                      <label class="form-label fw-bold text-muted fs-12 mb-1">Consumed Qty</label>
                      <div class="form-control-styled-static bg-light text-center text-secondary fw-bold"><?= number_format($consumed_qty) ?></div>
                    </div>

                    <?php if ($item_is_grafted == 1) : ?>
                      <div class="col-md-2">
                        <label class="form-label fw-bold text-dark fs-12 mb-1">Root Sowing Date <span class="text-danger">*</span></label>
                        <div class="form-control-styled-static bg-light text-center text-dark fw-bold"><?= $sowingDateDisplay ?></div>
                      </div>

                      <div class="col-md-2">
                        <label class="form-label fw-bold text-dark fs-12 mb-1">Scion Sowing Date <span class="text-danger">*</span></label>
                        <div class="form-control-styled-static bg-light text-center text-dark fw-bold"><?= $scionSowingDateDisplay ?></div>
                      </div>

                      <div class="col-md-2">
                        <label class="form-label fw-bold text-dark fs-12 mb-1">Expected Ready Date <span class="text-danger">*</span></label>
                        <div class="form-control-styled-static bg-light text-center text-dark fw-bold"><?= $expectedDeliveryDisplay ?></div>
                      </div>
                    <?php else : ?>
                      <div class="col-md-2.5 col-lg-2">
                        <label class="form-label fw-bold text-dark fs-12 mb-1">Sowing Start Date <span class="text-danger">*</span></label>
                        <div class="form-control-styled-static bg-light text-center text-dark fw-bold"><?= $sowingDateDisplay ?></div>
                      </div>

                      <div class="col-md-2.5 col-lg-3">
                        <label class="form-label fw-bold text-dark fs-12 mb-1">Expected Ready Date <span class="text-danger">*</span></label>
                        <div class="form-control-styled-static bg-light text-center text-dark fw-bold"><?= $expectedDeliveryDisplay ?></div>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Stage Status Bar -->
                  <div class="p-2 px-3 bg-light rounded-3 border d-flex flex-column gap-2 mb-2">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                      <span class="fw-bold text-dark fs-12 me-2" style="min-width: 110px;">
                        <i class="fa fa-tasks me-1 text-primary"></i> <span class="stage-status-title">Stage Status:</span>
                      </span>
                      
                      <div class="stage-chip stage-chip-sowing">
                        <i class="fa fa-seedling text-success fs-14"></i>
                        <span>Sowing: <strong><span class="sowing-trays">0</span> Trays</strong> (<span class="sowing-plants">0</span> Plants)</span>
                      </div>

                      <div class="stage-chip stage-chip-germination">
                        <i class="fa fa-door-open text-warning fs-14"></i>
                        <span>Germination: <strong><span class="germ-trays">0</span> Trays</strong> (<span class="germ-plants">0</span> Plants)</span>
                      </div>

                      <div class="stage-chip stage-chip-polyhouse">
                        <i class="fa fa-warehouse text-info fs-14"></i>
                        <span>Polyhouse: <strong><span class="poly-trays">0</span> Trays</strong> (<span class="poly-plants">0</span> Plants)</span>
                      </div>
                    </div>

                    <div class="scion-status-row d-flex align-items-center gap-2 flex-wrap pt-1 border-top" style="display:none !important;">
                      <span class="fw-bold text-dark fs-12 me-2" style="min-width: 110px;">
                        <i class="fa fa-cut me-1 text-success"></i> Scion Trays :
                      </span>
                      
                      <div class="stage-chip stage-chip-sowing">
                        <i class="fa fa-seedling text-success fs-14"></i>
                        <span>Sowing: <strong><span class="scion-sowing-trays">0</span> Trays</strong> (<span class="scion-sowing-plants">0</span> Plants)</span>
                      </div>

                      <div class="stage-chip stage-chip-germination">
                        <i class="fa fa-door-open text-warning fs-14"></i>
                        <span>Germination: <strong><span class="scion-germ-trays">0</span> Trays</strong> (<span class="scion-germ-plants">0</span> Plants)</span>
                      </div>

                      <div class="stage-chip stage-chip-polyhouse">
                        <i class="fa fa-warehouse text-info fs-14"></i>
                        <span>Polyhouse: <strong><span class="scion-poly-trays">0</span> Trays</strong> (<span class="scion-poly-plants">0</span> Plants)</span>
                      </div>
                    </div>
                  </div>

                  <!-- Sowing Lot Box -->
                  <div class="sowing-lot-box mt-3 p-3 rounded-3 shadow-sm" style="display:none; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 1px solid #cbd5e1;">
                    <div class="d-flex align-items-center justify-content-between pb-2 mb-2 border-bottom">
                      <div class="fw-bold text-dark fs-13 d-flex align-items-center gap-2">
                        <span class="p-1.5 bg-primary-subtle text-primary rounded-2 d-inline-flex align-items-center justify-content-center" style="width:26px; height:26px;">
                          <i class="fa fa-boxes fs-13"></i>
                        </span>
                        <span>Sowing Lot Details</span>
                        <span class="text-muted fs-11 fw-normal">(krf_polyhouse_plant_details)</span>
                      </div>
                      <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1 rounded-pill fs-11 fw-bold">
                        <i class="fa fa-check-circle me-1"></i> Sowing Completed
                      </span>
                    </div>
                    <div class="text-secondary fs-12 mb-3 d-flex align-items-center gap-1.5">
                      <i class="fa fa-info-circle text-info me-1"></i> Sowing lot numbers registered for this order (Select lot numbers for editing):
                    </div>
                    <div class="sowing-lot-list d-flex flex-wrap gap-2"></div>
                  </div>

                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- CARD 2: Edit Options & Questions Card -->
    <div class="card production-card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="mb-0"><i class="fa fa-edit me-2"></i> Edit Production Stock Order Options</h4>
      </div>
      <div class="card-body p-4">
        <!-- Div Cards Edit Controls Container -->
        <div class="production-cards-container" id="EditProductionOrderitemsCards">
          <?php if (count($order_details) > 0) : ?>
            <?php foreach ($order_details as $idx => $item) : ?>
              <?php
                $prod_qty = floatval($item['quantity']);
                $consumed_qty = floatval(getTotalConsumedQtyForPSO($krfconn, $projectid, $stockorder_no, $item['productid']));
                $is_fully_consumed = ($prod_qty > 0 && $consumed_qty >= $prod_qty) ? 1 : 0;
                $sowingDateVal = (!empty($item['sowingdate']) && $item['sowingdate'] !== '0000-00-00') ? $item['sowingdate'] : '';
                $expectedDeliveryVal = (!empty($item['expected_delivery_date']) && $item['expected_delivery_date'] !== '0000-00-00') ? $item['expected_delivery_date'] : '';
                $is_past = (!empty($expectedDeliveryVal) && $expectedDeliveryVal < $today) ? 1 : 0;
                $item_is_grafted = $item['IsGraftedResolved'] ?? 0;
              ?>
              <div class="production-item-card production-item-card-edit card shadow-sm border rounded-3 mb-4 overflow-hidden"
                   data-productid="<?= $item['productid'] ?>"
                   data-rowidx="<?= $idx ?>"
                   data-prodqty="<?= $prod_qty ?>"
                   data-consumedqty="<?= $consumed_qty ?>"
                   data-fullyconsumed="<?= $is_fully_consumed ?>"
                   data-ispast="<?= $is_past ?>"
                   data-isgrafted="<?= $item_is_grafted ?>"
                   data-sowingstarted="0"
                   data-sowingplants="0"
                   data-traycavity="0">
                
                <!-- Hidden inputs for form submit & JS sync -->
                <div class="d-none">
                  <input type="checkbox" class="chkEnableRowEdit" <?= $is_fully_consumed ? 'disabled' : 'checked' ?>>
                  <input type="hidden" name="productname" class="productname" value="<?= $item['productid'] ?>" data-productid="<?= $item['productid'] ?>">
                  <input type="hidden" name="productid" class="productid" value="<?= $item['productid'] ?>">
                  <input type="hidden" name="consumedqty" class="consumedqty" value="<?= $consumed_qty ?>">
                  <input type="hidden" name="sowingdate" class="sowingdate" value="<?= $sowingDateVal ?>">
                  <input type="hidden" name="expecteddelivery" class="expecteddelivery" value="<?= $expectedDeliveryVal ?>">
                </div>

                <!-- Card Main Body for Questions -->
                <div class="card-body p-3">
                  <!-- Inline Questions Box -->
                  <div class="inline-question-box p-3 bg-white border rounded-3 shadow-sm" style="display: <?= $is_fully_consumed ? 'none' : 'block' ?>; border-left: 4px solid #1e40af !important;">
                    
                    <!-- Q1: Why are you editing the production order? -->
                    <div class="mb-3">
                      <label class="form-label fw-bold text-dark fs-13 mb-1">
                        <i class="fa fa-question-circle me-1 text-primary"></i> <strong>Q1. Why are you editing the production order?</strong> <span class="text-danger">*</span>
                      </label>
                      <textarea name="edit_reason" class="form-control edit-reason-textarea" rows="2" placeholder="Enter reason for editing this production order..." style="border-radius: 8px; border: 1px solid #cbd5e1; font-size: 13px;" required></textarea>
                    </div>

                    <!-- Q2: Target Quantity for Order Split -->
                    <div class="mb-3 p-3 bg-light rounded-3 border">
                      <label class="form-label fw-bold text-dark fs-13 mb-1">
                        <i class="fa fa-hashtag me-1 text-primary"></i> <strong>Q2. How many qty with current production order? (Enter Target Quantity for Production Order Split)</strong> <span class="text-danger">*</span>
                      </label>
                      <div class="row align-items-center g-2">
                        <div class="col-md-4">
                          <input type="text" name="quantity" class="form-control qty_td qtycal text-center fw-bold fs-15 border-primary" value="<?= $item['quantity'] ?>" onkeypress="return isFloatNumber(event,this);" required>
                          <div class="qty-error-message text-danger fw-bold fs-11 mt-1" style="display: none;"></div>
                        </div>
                        <div class="col-md-8 fs-12 text-muted">
                          Enter target quantity to keep under order <strong><?= htmlspecialchars($stockorder_no) ?></strong>.
                        </div>
                      </div>

                      <!-- Live Tray Cavity & Remainder Calculation Box -->
                      <div class="tray-cavity-calc-box mt-3" style="display: none;"></div>
                    </div>

                    <!-- Edit Options Selection (Radio Buttons) -->
                    <div class="edit-options-section mb-3 p-3 bg-light rounded-3 border">
                      <label class="form-label fw-bold text-dark fs-13 mb-2">Select Edit Option for Production Order:</label>
                      
                      <div class="row g-2 mb-2">
                        <!-- Option 1 Card -->
                        <div class="col-md-6">
                          <div class="edit-option-card d-flex align-items-start gap-2 p-3 bg-white rounded-3 border shadow-sm h-100">
                            <input class="radEditChoice mt-1 flex-shrink-0" type="radio" name="edit_choice_<?= $idx ?>" id="radOnlyNumber_<?= $idx ?>" value="only_number" checked style="cursor: pointer; width: 18px; height: 18px; accent-color: #2563eb;">
                            <div class="flex-grow-1 pe-1">
                              <label class="fw-bold text-dark fs-13 mb-1 d-block" for="radOnlyNumber_<?= $idx ?>" style="cursor: pointer;">
                                1. Only edit the number only
                              </label>
                              <div class="text-secondary fs-11">
                                Update quantity directly under existing order number (<?= htmlspecialchars($stockorder_no) ?>). No new production order created.
                              </div>
                            </div>
                          </div>
                        </div>

                        <!-- Option 2 Card -->
                        <div class="col-md-6">
                          <div class="edit-option-card d-flex align-items-start gap-2 p-3 bg-white rounded-3 border shadow-sm h-100">
                            <input class="radEditChoice mt-1 flex-shrink-0" type="radio" name="edit_choice_<?= $idx ?>" id="radCreateNewOrder_<?= $idx ?>" value="create_new_order" style="cursor: pointer; width: 18px; height: 18px; accent-color: #2563eb;">
                            <div class="flex-grow-1 pe-1">
                              <label class="fw-bold text-dark fs-13 mb-1 d-block" for="radCreateNewOrder_<?= $idx ?>" style="cursor: pointer;">
                                2. Edited qty with same production order &amp; rest of qty create new production order
                              </label>
                              <div class="text-secondary fs-11">
                                Keep target quantity under order <?= htmlspecialchars($stockorder_no) ?>, and create a new production order for remaining balance.
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>

                      <!-- Option 2 Controls -->
                      <div class="box-create-new-order-controls p-3 bg-white rounded-3 border shadow-sm mt-3" style="display: none; border-left: 4px solid #2563eb !important;">
                        <div class="fw-bold text-dark fs-13 mb-2">
                          <i class="fa fa-plus-circle text-primary me-1"></i> New Production Order Details for Remaining Balance:
                        </div>
                        
                          <div class="row align-items-center g-3 mb-3">
                            <div class="col-md-7">
                              <label class="form-label fw-bold text-dark fs-12 mb-1">Select Product Name for New Production Order: <span class="text-danger">*</span></label>
                              <select name="new_order_product_id" class="form-select form-control new-order-product-select" style="width: 100%; font-size: 14px;">
                                <option value="" selected disabled>Select Product Name</option>
                              </select>
                            </div>
                            <div class="col-md-5">
                              <div class="p-2 bg-light rounded border fs-12">
                                <div>• <strong>Current Order (<?= htmlspecialchars($stockorder_no) ?>):</strong> <span class="lbl-target-qty-text fw-bold text-primary">0</span> plants</div>
                                <div>• <strong>New Order Balance Qty:</strong> <span class="lbl-rest-open-stock fw-bold text-success">0</span> plants</div>
                              </div>
                            </div>
                          </div>

                          <!-- Sowing Dates & Dispatch Details for New Production Order -->
                          <div class="box-new-order-sowing-dates p-3 bg-light rounded-3 border mb-3" style="display: none;">
                            <div class="fw-bold text-dark fs-12 mb-3">
                              <i class="fa fa-calendar-alt text-success me-1"></i> <strong>Sowing Schedule &amp; Dispatch Details for New Production Order:</strong>
                            </div>

                            <!-- Editable Expected Ready Delivery Date & Internal Dispatch Date Row -->
                            <div class="row g-3 mb-3 pb-3 border-bottom align-items-stretch">
                              <!-- Expected Ready Delivery Date (Editable) -->
                              <div class="col-md-6">
                                <div class="p-3 bg-white rounded-3 border shadow-sm h-100 d-flex flex-column justify-content-between">
                                  <div>
                                    <label class="form-label fw-bold text-dark fs-12 mb-1 d-flex align-items-center justify-content-between">
                                      <span><i class="fa fa-truck text-primary me-1"></i> Expected Ready Delivery Date <span class="text-danger">*</span></span>
                                      <span class="badge bg-primary-subtle text-primary border px-2 py-0.5" style="font-size: 10px;">Editable</span>
                                    </label>
                                    <input type="date" name="new_order_expected_delivery" class="form-control text-center new-order-expected-delivery form-control-styled fw-bold mt-1" value="<?= $expectedDeliveryVal ?>" required>
                                  </div>
                                  <small class="text-muted fs-11 mt-2 d-block"><i class="fa fa-info-circle me-1"></i> Changing this date automatically recalculates all sowing &amp; internal dispatch dates.</small>
                                </div>
                              </div>

                              <!-- Internal Dispatch Date (Grafting: Ready - 20 Days) Summary Box -->
                              <div class="col-md-6">
                                <div class="p-3 bg-white rounded-3 border border-primary-subtle shadow-sm h-100 d-flex flex-column justify-content-between">
                                  <div>
                                    <div class="fw-bold text-dark fs-12 mb-1 d-flex align-items-center justify-content-between">
                                      <span><i class="fa fa-calendar-check text-primary me-1"></i> Internal Dispatch Date (Grafting)</span>
                                      <span class="badge bg-info-subtle text-info border px-2 py-0.5" style="font-size: 10px;">Ready - 20 Days</span>
                                    </div>
                                    <div class="p-2 bg-light rounded-2 text-center border mt-2">
                                      <strong class="text-primary fs-15"><i class="fa fa-calendar-alt me-1"></i> <span class="lbl-new-internal-dispatch-date">N/A</span></strong>
                                    </div>
                                  </div>
                                  <small class="text-muted fs-11 mt-2 d-block"><i class="fa fa-clock me-1"></i> Target internal dispatch schedule for nursery operations.</small>
                                </div>
                              </div>
                            </div>

                            <!-- Readonly Sowing Dates -->
                            <div class="row g-3">
                              <div class="col-md-6 box-root-sowingdate-col">
                                <label class="form-label fw-bold text-dark fs-12 mb-1">
                                  <i class="fa fa-seedling text-success me-1"></i> Root Seed Sowing Date
                                  <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size: 10px;">Auto Calculated</span>
                                </label>
                                <input type="date" name="new_order_root_sowingdate" class="form-control text-center new-order-root-sowingdate form-control-styled fw-bold" value="" readonly style="background-color: #f8fafc; cursor: not-allowed;" required>
                                <small class="lbl-root-sowing-calc-preview text-primary fs-11 mt-1.5 d-block fw-semibold" style="color: #2563eb !important;">
                                  <i class="fa fa-calculator me-1"></i> <span class="calc-text">Formula: Delivery - Grafting - Growing - Session - Buffer</span>
                                </small>
                              </div>
                              <div class="col-md-6 box-scion-sowingdate-col">
                                <label class="form-label fw-bold text-dark fs-12 mb-1">
                                  <i class="fa fa-cut text-success me-1"></i> Scion Seed Sowing Date
                                  <span class="badge bg-secondary-subtle text-secondary border ms-1" style="font-size: 10px;">Auto Calculated</span>
                                </label>
                                <input type="date" name="new_order_scion_sowingdate" class="form-control text-center new-order-scion-sowingdate form-control-styled fw-bold" value="" readonly style="background-color: #f8fafc; cursor: not-allowed;" required>
                                <small class="lbl-scion-sowing-calc-preview text-primary fs-11 mt-1.5 d-block fw-semibold" style="color: #2563eb !important;">
                                  <i class="fa fa-calculator me-1"></i> <span class="calc-text">Formula: Delivery - Grafting - Growing - Session - Buffer</span>
                                </small>
                              </div>
                            </div>
                        </div>

                        <div class="root-sowed-schedule-notice alert alert-warning py-2 px-3 mb-0 fs-12 border-0 shadow-sm" style="display: none; border-radius: 8px; background-color: #fffbeb; border-left: 4px solid #f59e0b !important;">
                          <i class="fa fa-clock me-1 text-warning"></i> <strong>Root Sowing Notice:</strong> Root seed sowing is already done for this order. The new production order's sowing schedule will be set with a gap of <strong>less than 4-5 days</strong> from the root sowing date.
                        </div>

                        <div class="dispatch-match-status-box mt-2" style="display: none;"></div>
                      </div>

                    </div>

                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else : ?>
            <div class="alert alert-light text-center py-4 border text-muted">No items found for this order.</div>
          <?php endif; ?>
        </div>
        <input type="hidden" name="subcompanyid" value="<?= $subcompanyid ?>">
        <input type="hidden" name="projectid" value="<?= $projectid ?>">
        <input type="hidden" name="loginid" value="<?= $loginid ?>">
        <input type="hidden" name="production_stockorder_no" value="<?= htmlspecialchars($stockorder_no) ?>">
        <div class="text-center mt-3 pt-2">
          <button type="submit" class="btn btn-update-order px-5" id="btnSubmitOrder">
            <i class="fa fa-save me-2"></i> Update Production Stock Order
          </button>
        </div>
      </div>
    </div>
  </form>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
var loginroleid = <?php echo $currentLoginRoleId; ?>;
var hasPastDelivery = <?php echo $has_past_delivery ? 'true' : 'false'; ?>;

function htmlEscape(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function addDaysToDate(dateStr, days) {
    if (!dateStr || isNaN(days)) return '';
    var d = new Date(dateStr);
    d.setDate(d.getDate() + parseInt(days));
    return d.toISOString().split('T')[0];
}

function subtractDaysFromDate(dateStr, days) {
    if (!dateStr || isNaN(days)) return '';
    var d = new Date(dateStr);
    d.setDate(d.getDate() - parseInt(days));
    return d.toISOString().split('T')[0];
}

function getDaysBetween(dateStr1, dateStr2) {
    if (!dateStr1 || !dateStr2) return 0;
    var d1 = new Date(dateStr1);
    var d2 = new Date(dateStr2);
    var diffTime = d2.getTime() - d1.getTime();
    return Math.round(diffTime / (1000 * 3600 * 24));
}

function formatToYYYYMMDD(dStr) {
    if (!dStr) return '';
    if (dStr.match(/^\d{4}-\d{2}-\d{2}$/)) return dStr;
    var d = new Date(dStr);
    if (isNaN(d.getTime())) return dStr;
    var month = '' + (d.getMonth() + 1);
    var day = '' + d.getDate();
    var year = d.getFullYear();
    if (month.length < 2) month = '0' + month;
    if (day.length < 2) day = '0' + day;
    return [year, month, day].join('-');
}

function fetchStageStatusForOrderRow($card) {
    let rowidx = $card.data("rowidx");
    let productid = $card.data("productid") || $card.find(".productname").val();
    let stockorder_no = $("input[name='production_stockorder_no']").val();
    let projectid = $("input[name='projectid']").val();

    if (!productid || !stockorder_no) return;

    let $card1 = $(".production-item-card-details[data-rowidx='" + rowidx + "']");
    let $card2 = $(".production-item-card-edit[data-rowidx='" + rowidx + "']");
    let $targetCards = $card1.add($card2);

    $.ajax({
        url: "modal/product_modal.php?action=GetProductionStockOrderStageStatus",
        type: "POST",
        data: {
            projectid: projectid,
            stockorder_no: stockorder_no,
            productid: productid
        },
        dataType: "json",
        success: function(res) {
            if (res.status === "success" && res.data) {
                let d = res.data;
                let hasScion = d.has_scion || (d.scion_sowing_trays > 0 || d.scion_germ_trays > 0 || d.scion_poly_trays > 0);

                $targetCards.each(function() {
                    let $c = $(this);
                    if (hasScion) {
                        $c.find(".stage-status-title").text("Root Trays :");
                        $c.find(".scion-status-row").attr("style", "display: flex !important;");

                        $c.find(".sowing-trays").text(d.root_sowing_trays);
                        $c.find(".sowing-plants").text(Number(d.root_sowing_plants).toLocaleString());

                        $c.find(".germ-trays").text(d.root_germ_trays);
                        $c.find(".germ-plants").text(Number(d.root_germ_plants).toLocaleString());

                        $c.find(".poly-trays").text(d.root_poly_trays);
                        $c.find(".poly-plants").text(Number(d.root_poly_plants).toLocaleString());

                        $c.find(".scion-sowing-trays").text(d.scion_sowing_trays);
                        $c.find(".scion-sowing-plants").text(Number(d.scion_sowing_plants).toLocaleString());

                        $c.find(".scion-germ-trays").text(d.scion_germ_trays);
                        $c.find(".scion-germ-plants").text(Number(d.scion_germ_plants).toLocaleString());

                        $c.find(".scion-poly-trays").text(d.scion_poly_trays);
                        $c.find(".scion-poly-plants").text(Number(d.scion_poly_plants).toLocaleString());
                    } else {
                        $c.find(".stage-status-title").text("Stage Status:");
                        $c.find(".scion-status-row").attr("style", "display: none !important;");

                        $c.find(".sowing-trays").text(d.sowing_trays);
                        $c.find(".sowing-plants").text(Number(d.sowing_plants).toLocaleString());

                        $c.find(".germ-trays").text(d.germ_trays);
                        $c.find(".germ-plants").text(Number(d.germ_plants).toLocaleString());

                        $c.find(".poly-trays").text(d.poly_trays);
                        $c.find(".poly-plants").text(Number(d.poly_plants).toLocaleString());
                    }

                    let sowingStarted = (d.sowing_trays > 0 || d.sowing_plants > 0) ? 1 : 0;
                    let traycavity = (d.sowing_trays > 0 && d.sowing_plants > 0) ? Math.round(d.sowing_plants / d.sowing_trays) : 0;

                    let rootSowedPlants = parseInt(d.root_sowing_plants) || 0;
                    let rootSowedTrays = parseInt(d.root_sowing_trays) || 0;
                    let scionSowedPlants = parseInt(d.scion_sowing_plants) || 0;
                    let scionSowedTrays = parseInt(d.scion_sowing_trays) || 0;

                    let rootSowingCompleted = (rootSowedPlants > 0 || rootSowedTrays > 0) ? 1 : 0;
                    let scionSowingCompleted = (scionSowedPlants > 0 || scionSowedTrays > 0) ? 1 : 0;

                    let item_is_grafted = parseInt($c.data("isgrafted")) || 0;
                    let bothSowingCompleted = 0;

                    if (hasScion || item_is_grafted === 1) {
                        if (rootSowingCompleted === 1 && scionSowingCompleted === 1) {
                            bothSowingCompleted = 1;
                        }
                    } else {
                        bothSowingCompleted = (d.sowing_plants > 0 || d.sowing_trays > 0) ? 1 : 0;
                    }

                    $c.attr("data-sowingstarted", sowingStarted);
                    $c.attr("data-sowingplants", d.sowing_plants);
                    $c.attr("data-traycavity", traycavity);
                    $c.attr("data-rootcavity", d.root_tray_cavity || traycavity || 104);
                    $c.attr("data-rootsowed", rootSowingCompleted);
                    $c.attr("data-scionsowed", scionSowingCompleted);
                    $c.attr("data-bothsowingcompleted", bothSowingCompleted);
                    $c.attr("data-rootdispatchdate", d.root_dispatch_date || "");

                    if (bothSowingCompleted === 1 && d.sowing_lots && d.sowing_lots.length > 0) {
                        let cardIdx = $c.data("rowidx") || 0;
                        let lotHtml = '';
                        d.sowing_lots.forEach(function(lot, lidx) {
                            let lotNo = lot.sowinglotno_internal;
                            let typeLbl = lot.roottype ? lot.roottype.toUpperCase() : 'ROOT';
                            let trayCnt = lot.trays || 0;
                            let plantCnt = lot.plants || 0;

                            lotHtml += '<div class="sowing-lot-card d-inline-flex align-items-center gap-2.5 bg-white px-3 py-2 border rounded-3 shadow-sm" style="cursor:pointer; user-select:none; transition: all 0.2s ease;">';
                            lotHtml += '  <input class="form-check-input sowing-lot-checkbox m-0" type="checkbox" name="sowing_lots_' + cardIdx + '[]" value="' + htmlEscape(lotNo) + '" id="lot_chk_' + cardIdx + '_' + lidx + '" checked style="width:18px; height:18px; accent-color:#2563eb; cursor:pointer; flex-shrink:0;">';
                            lotHtml += '  <label class="form-check-label fw-bold text-dark fs-12 mb-0 d-flex align-items-center gap-2" for="lot_chk_' + cardIdx + '_' + lidx + '" style="cursor:pointer;">';
                            lotHtml += '    <span><i class="fa fa-tag text-primary me-1"></i>' + htmlEscape(lotNo) + '</span>';
                            lotHtml += '    <span class="badge bg-secondary-subtle text-dark border px-2 py-1 fs-11">' + typeLbl + ' (' + trayCnt + ' Trays / ' + Number(plantCnt).toLocaleString() + ' Plants)</span>';
                            lotHtml += '  </label>';
                            lotHtml += '</div>';
                        });
                        $c.find(".sowing-lot-list").html(lotHtml);
                        $c.find(".sowing-lot-box").show();
                    } else {
                        $c.find(".sowing-lot-box").hide();
                    }
                });

                let $activeEditCard = $card2.length ? $card2 : $card;
                updateGraftedQuantitiesAndPreviews($activeEditCard, true);
                updateTrayCavityRemainderCalculation($activeEditCard);
            }
        }
    });
}

function getFilteredProductOptions($card) {
    let sowingStarted = parseInt($card.attr("data-sowingstarted")) || 0;
    let rootSowed = parseInt($card.attr("data-rootsowed")) || 0;
    let currentProdId = $card.data("productid") || $card.find(".productname").val();

    let currentRootSeedId = $card.attr("data-seedid") || $card.attr("data-rootseedid") || null;
    let currentScionSeedId = $card.attr("data-scionseedid") || null;

    if (window.rawProductMasterList && window.rawProductMasterList.length > 0) {
        let currentProduct = window.rawProductMasterList.find(p => 
            p.ProductID == currentProdId || 
            p.sku == currentProdId || 
            p.ProductName == currentProdId
        );
        if (currentProduct) {
            currentRootSeedId = currentProduct.SeedId || currentProduct.RootSeedId;
            currentScionSeedId = currentProduct.ScionSeedId;
        }
    }

    let html = '<option value="" selected disabled>Select Product Name</option>';
    let matchedCount = 0;

    if (window.rawProductMasterList && window.rawProductMasterList.length > 0) {
        window.rawProductMasterList.forEach(function(item) {
            let pid = item.ProductID || item.sku;
            let rawName = item.ProductName || '';
            let pname = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
            let pprice = item.PriceOfProduct || 0;
            let itemRootSeed = item.SeedId || item.RootSeedId;

            if (rootSowed === 1 || sowingStarted === 1) {
                // STRICT RULE: If Root Seed Sowing has STARTED, show ONLY products with the SAME Root Seed ID
                if (currentRootSeedId && itemRootSeed == currentRootSeedId) {
                    html += '<option value="' + pid + '" data-priceofproduct="' + pprice + '">' + pname + '</option>';
                    matchedCount++;
                }
            } else {
                // If Sowing NOT STARTED: Show all products (except current product)
                if (pid != currentProdId && rawName != currentProdId) {
                    html += '<option value="' + pid + '" data-priceofproduct="' + pprice + '">' + pname + '</option>';
                    matchedCount++;
                }
            }
        });

        // Fallback if currentRootSeedId is not found or matching returns 0 items
        if (matchedCount === 0 && (rootSowed === 1 || sowingStarted === 1)) {
            window.rawProductMasterList.forEach(function(item) {
                let pid = item.ProductID || item.sku;
                let rawName = item.ProductName || '';
                let pname = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
                let pprice = item.PriceOfProduct || 0;
                if (pid != currentProdId && rawName != currentProdId) {
                    html += '<option value="' + pid + '" data-priceofproduct="' + pprice + '">' + pname + '</option>';
                }
            });
        }
    } else {
        // Fallback using DOM options from main product dropdown if raw list is not loaded yet
        let $existingSelect = $card.find(".productname");
        if ($existingSelect.length > 0 && $existingSelect.find("option").length > 1) {
            $existingSelect.find("option").each(function() {
                let val = $(this).val();
                let txt = $(this).text();
                if (val && val !== "" && val !== currentProdId) {
                    html += '<option value="' + val + '">' + txt + '</option>';
                }
            });
        } else if (window.cachedProductOptions) {
            html = window.cachedProductOptions;
        }
    }

    return html;
}

function handleRowChoice($card) {
    let choice = $card.find(".radEditChoice:checked").val() || "only_number";
    let $box = $card.find(".box-create-new-order-controls");

    if (choice === "create_new_order" || choice === "full_split") {
        let sowingStarted = parseInt($card.attr("data-sowingstarted")) || 0;
        let rootSowed = parseInt($card.attr("data-rootsowed")) || 0;
        let sowedPlants = parseInt($card.attr("data-sowingplants")) || 0;
        let currentSowingDate = $card.find(".sowingdate").val() || "";

        // 1. Populate product list based on root sowing status
        let filteredOptions = getFilteredProductOptions($card);
        let $select = $card.find(".new-order-product-select");
        let curVal = $select.val();
        $select.html(filteredOptions);
        if (curVal) $select.val(curVal);

        // 2. Render notice banner & pre-fill sowing date suggestions
        let $noticeBox = $card.find(".root-sowed-schedule-notice");
        let todayStr = new Date().toISOString().split("T")[0];
        let tomorrowStr = addDaysToDate(todayStr, 1);
        let suggestedRootDate = currentSowingDate || todayStr;
        let suggestedScionDate = addDaysToDate(suggestedRootDate, 4);

        if (rootSowed === 1 || sowingStarted === 1) {
            // SOWING HAS STARTED: Root seed sowing is already done under parent order -> HIDE Root Sowing Date, SHOW ONLY Scion Sowing Date
            $noticeBox.html(`
                <i class="fa fa-exclamation-triangle me-1 text-warning"></i>
                <strong>Root Sowing Started:</strong> Root seed sowing is already done for this order (Sowed: <strong>${Number(sowedPlants).toLocaleString()} plants</strong>).
                Showing products with the <strong>SAME Root Seed</strong> and <strong>DIFFERENT Scion Seed</strong>.
                Set the <strong>Scion Seed Sowing Date</strong> for the new production order.
            `).removeClass("alert-info").addClass("alert-warning").show();

            $card.find(".box-root-sowingdate-col").hide();
            $card.find(".box-scion-sowingdate-col").removeClass("col-md-6").addClass("col-md-12").show();

            $card.find(".new-order-root-sowingdate").val(suggestedRootDate);
            $card.find(".new-order-scion-sowingdate").val(suggestedScionDate);
        } else {
            // SOWING NOT STARTED: Show both Root & Scion Sowing Dates box (Hide redundant notice box)
            $noticeBox.hide();
            $card.find(".box-root-sowingdate-col").show();
            $card.find(".box-scion-sowingdate-col").removeClass("col-md-12").addClass("col-md-6").show();
        }

                $box.css({"display": "block", "visibility": "visible"}).stop(true, true).slideDown(150);
    } else {
        $box.css({"display": "none"}).stop(true, true).slideUp(150);
    }
    updateGraftedQuantitiesAndPreviews($card);
    validateDispatchDateMatching($card);
    validateAllItemQuantities();
}

function validateDispatchDateMatching($card) {
    let sowingStarted = parseInt($card.attr("data-sowingstarted")) || 0;
    let rootSowed = parseInt($card.attr("data-rootsowed")) || 0;
    let choice = $card.find(".radEditChoice:checked").val() || "only_number";
    let $dispatchBox = $card.find(".dispatch-match-status-box");

    if (choice !== "create_new_order" && choice !== "full_split") {
        $dispatchBox.hide();
        return true;
    }

    // Only validate when Root Sowing has STARTED or both sowings are completed
    if (rootSowed !== 1 && sowingStarted !== 1) {
        $dispatchBox.hide();
        return true;
    }

    let selectedNewProdId = $card.find(".new-order-product-select").val();
    let totalQty = parseFloat($card.find(".qtycal:visible").val()) || parseFloat($card.find(".qtycal").val()) || 0;
    let sowedQty = parseFloat($card.attr("data-sowingplants")) || 0;
    let restOpenStock = Math.max(0, totalQty - sowedQty);

    if (!selectedNewProdId) {
        $dispatchBox.html(`
            <div class="alert alert-info py-2 px-3 border-0 shadow-sm fs-12 mb-0" style="border-radius:8px; background-color:#eff6ff; border-left:4px solid #3b82f6 !important;">
                <i class="fa fa-info-circle me-1 text-primary"></i> <strong>Dispatch Schedule Check:</strong> Please select a product name for the new production order to verify dispatch schedule matching (nearest 4-5 days gap rule).
            </div>
        `).show();
        return false;
    }

    // 1. Parent Order Customer Ready to Dispatch Date & Internal Dispatch Date (Dispatch Date - 20 Days)
    let readyToDispatchDate = $card.find(".expecteddelivery").val() || "";
    let internalReadyToDispatchDate = subtractDaysFromDate(readyToDispatchDate, 20);
    let editedRootDispatchDate = $card.attr("data-rootdispatchdate") || internalReadyToDispatchDate || readyToDispatchDate || "";

    // 2. Selected New Product Details & Growth Cycle Days
    let selectedProd = (window.rawProductMasterList || []).find(p => p.ProductID == selectedNewProdId || p.sku == selectedNewProdId);
    let selectedProdName = selectedProd ? selectedProd.ProductName : "Selected Product";
    let scionGrowingDays = parseInt(selectedProd ? (selectedProd.ScionSeedgrowingdays || selectedProd.fullsycledays || 30) : 30) || 30;
    let cycleDays = parseInt(selectedProd ? (selectedProd.fullsycledays || selectedProd.fullcycledays || selectedProd.GrowthDays || 30) : 30) || 30;

    // 3. Compute Selected New Product Dispatch Date & Internal Dispatch Date (Ready Date - 20 Days)
    let newProductDispatchDate = $card.find(".new-order-expected-delivery").val() || readyToDispatchDate;
    let newProductInternalDispatchDate = subtractDaysFromDate(newProductDispatchDate, 20);

    // 4. Calculate Difference in Days between Parent Order Root Internal Dispatch Date and New Product Scion Internal Dispatch Date
    let diffDays = Math.abs(getDaysBetween(editedRootDispatchDate, newProductInternalDispatchDate || newProductDispatchDate));
    let isMatched = (diffDays <= 5);

    if (isMatched) {
        // MATCHED: Difference is within 4-5 days gap
        $dispatchBox.html(`
            <div class="alert alert-success py-2 px-3 border-0 shadow-sm fs-12 mb-0" style="border-radius:8px; background-color:#f0fdf4; border-left:4px solid #22c55e !important;">
                <div class="fw-bold text-success mb-1 fs-13"><i class="fa fa-check-circle me-1"></i> Dispatch Schedule Matched (Within 4-5 Days Gap Rule):</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-6">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-muted d-block fs-11">Parent Order Root Internal Dispatch Date (Ready Date - 20 Days):</span>
                            <strong class="text-dark"><i class="fa fa-calendar-check text-success me-1"></i> ${editedRootDispatchDate || 'N/A'}</strong>
                            <div class="text-muted fs-11 mt-1">• Ready to Dispatch Date: <strong>${readyToDispatchDate || 'N/A'}</strong></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-muted d-block fs-11">${selectedProdName} Scion Internal Dispatch Date:</span>
                            <strong class="text-dark"><i class="fa fa-calendar-alt text-primary me-1"></i> ${newProductInternalDispatchDate || 'N/A'}</strong>
                            <div class="text-muted fs-11 mt-1">• Ready to Dispatch Date: <strong>${newProductDispatchDate || 'N/A'}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-dark fs-12">• <strong>Dispatch Gap:</strong> <span class="badge bg-success-subtle text-success border px-2 py-1">${diffDays} days gap</span> (Matches allowed 4-5 days difference).</div>
                <div class="mt-1 fw-bold text-success fs-13"><i class="fa fa-plus-circle me-1"></i> Ready to Save & Create new production order for <u>${Number(restOpenStock).toLocaleString()} plants</u> balance quantity!</div>
            </div>
        `).show();
        $card.data("dispatchmatched", 1);
    } else {
        // NOT MATCHED: Difference exceeds 4-5 days gap
        $dispatchBox.html(`
            <div class="alert alert-danger py-2 px-3 border-0 shadow-sm fs-12 mb-0" style="border-radius:8px; background-color:#fef2f2; border-left:4px solid #ef4444 !important;">
                <div class="fw-bold text-danger mb-1 fs-13"><i class="fa fa-times-circle me-1"></i> Dispatch Schedule Mismatch Error:</div>
                <div class="row g-2 mt-1">
                    <div class="col-md-6">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-muted d-block fs-11">Parent Order Root Internal Dispatch Date (Ready Date - 20 Days):</span>
                            <strong class="text-dark"><i class="fa fa-calendar-check text-danger me-1"></i> ${editedRootDispatchDate || 'N/A'}</strong>
                            <div class="text-muted fs-11 mt-1">• Ready to Dispatch Date: <strong>${readyToDispatchDate || 'N/A'}</strong></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-2 bg-white rounded border">
                            <span class="text-muted d-block fs-11">${selectedProdName} Scion Internal Dispatch Date:</span>
                            <strong class="text-dark"><i class="fa fa-calendar-alt text-danger me-1"></i> ${newProductInternalDispatchDate || 'N/A'}</strong>
                            <div class="text-muted fs-11 mt-1">• Ready to Dispatch Date: <strong>${newProductDispatchDate || 'N/A'}</strong></div>
                        </div>
                    </div>
                </div>
                <div class="mt-2 text-dark fs-12">• <strong>Dispatch Gap:</strong> <span class="badge bg-danger-subtle text-danger border px-2 py-1">${diffDays} days gap</span> (Exceeds allowed 4-5 days difference).</div>
                <div class="mt-1 fw-bold text-danger fs-13"><i class="fa fa-ban me-1"></i> Cannot save data: Dispatch schedule mismatch between parent root and new scion order.</div>
            </div>
        `).show();
        $card.data("dispatchmatched", 0);
    }

    checkGlobalDispatchMatchState();
    return isMatched;
}

function checkGlobalDispatchMatchState() {
    let hasMismatch = false;
    $(".production-item-card[data-productid]").each(function() {
        let $card = $(this);
        let isChecked = $card.find(".chkEnableRowEdit").is(":checked");
        if (!isChecked) return;

        let choice = $card.find(".radEditChoice:checked").val() || "only_number";
        if (choice === "create_new_order" || choice === "full_split") {
            let rootSowed = parseInt($card.attr("data-rootsowed")) || 0;
            let sowingStarted = parseInt($card.attr("data-sowingstarted")) || 0;
            let sowedPlants = parseInt($card.attr("data-sowingplants")) || 0;

            if (rootSowed === 1 || sowingStarted === 1 || sowedPlants > 0) {
                let dispatchMatched = $card.data("dispatchmatched");
                if (dispatchMatched === 0) {
                    hasMismatch = true;
                }
            }
        }
    });

    if (hasMismatch) {
        $("#btnSubmitOrder").hide();
    } else {
        $("#btnSubmitOrder").show();
    }
    return !hasMismatch;
}

$(document).on("change", ".new-order-product-select", function() {
    let $card = $(this).closest(".production-item-card");
    validateDispatchDateMatching($card);
    validateAllItemQuantities();
});

$(document).on("change input", ".new-order-root-sowingdate, .new-order-scion-sowingdate", function() {
    let $card = $(this).closest(".production-item-card");
    validateDispatchDateMatching($card);
    validateAllItemQuantities();
});

$(document).on("change click input", ".radEditChoice", function() {
    let $card = $(this).closest(".production-item-card");
    handleRowChoice($card);
});

$(document).on("click", ".edit-option-card", function(e) {
    let $card = $(this).closest(".production-item-card");
    let $radio = $(this).find(".radEditChoice");
    if ($radio.length > 0) {
        $radio.prop("checked", true);
        handleRowChoice($card);
    }
});

function updateFormLockState() {
    $(".production-item-card[data-productid]").each(function() {
        let $card = $(this);
        let isFullyConsumed = parseInt($card.data("fullyconsumed")) || 0;

        // Product Name CANNOT be changed under any condition
        $card.find(".productname").prop("disabled", true);

        if (isFullyConsumed === 1) {
            $card.find(".qtycal, .sowingdate, .expecteddelivery").prop("readonly", true);
            $card.find(".chkEnableRowEdit").prop("disabled", true).prop("checked", false);
            $card.removeClass("row-editing-active");
            $card.find(".inline-question-box").hide();
        } else {
            $card.find(".chkEnableRowEdit").prop("disabled", false).prop("checked", true);
            $card.addClass("row-editing-active");
            $card.find(".inline-question-box").show();
            handleRowChoice($card);
        }
    });
}

function calculateMinAllowedQty($card) {
    let prodQty = parseFloat($card.attr("data-prodqty")) || 0;
    let consumedQty = parseFloat($card.attr("data-consumedqty")) || 0;
    let isFullyConsumed = parseInt($card.attr("data-fullyconsumed")) || 0;

    if (isFullyConsumed === 1 || (prodQty > 0 && consumedQty >= prodQty)) {
        return prodQty; // Fully consumed order cannot be edited
    }

    // Consumed quantity is the minimum limit (or 1 if no consumed quantity)
    // Sowed plants/trays is NOT a minimum limit
    let minQty = (consumedQty > 0) ? consumedQty : 1;

    return Math.round(minQty);
}

function calculateMaxAllowedQty($card) {
    let prodQty = parseFloat($card.attr("data-prodqty")) || 0;
    let sowingPlants = parseInt($card.attr("data-sowingplants")) || 0;
    let rootSowed = parseInt($card.attr("data-rootsowed")) || 0;
    let sowingStarted = parseInt($card.attr("data-sowingstarted")) || 0;
    let bothSowingCompleted = parseInt($card.attr("data-bothsowingcompleted")) || 0;

    // Sowed plants/trays IS the maximum limit!
    // If sowing has started or root sowed or sowing completed:
    if (sowingPlants > 0 || bothSowingCompleted === 1 || rootSowed === 1 || sowingStarted === 1) {
        let maxLimit = sowingPlants > 0 ? sowingPlants : (prodQty > 0 ? prodQty : 0);
        return Math.round(maxLimit);
    } else {
        // Sowing is NOT completed (0 sowing): CAN increase to any quantity (0 = unlimited max allowed)
        return 0;
    }
}

function validateCardQuantity($card) {
    let minAllowed = calculateMinAllowedQty($card);
    let maxAllowed = calculateMaxAllowedQty($card);
    let sowingPlants = parseInt($card.attr("data-sowingplants")) || 0;
    let consumedQty = parseFloat($card.attr("data-consumedqty")) || 0;
    let isFullyConsumed = parseInt($card.attr("data-fullyconsumed")) || 0;

    if (isFullyConsumed === 1) return true;

    let $qtyInputs = $card.find(".qtycal");
    let $visibleInput = $qtyInputs.filter(":visible");
    if ($visibleInput.length === 0) $visibleInput = $qtyInputs.first();

    let valStr = $visibleInput.val();
    let currentVal = parseFloat(valStr) || 0;

    let isValid = true;
    let errorMsg = "";

    if (minAllowed > 0 && (currentVal < minAllowed || isNaN(currentVal) || valStr === "")) {
        isValid = false;
        let ruleDesc = "";
        if (consumedQty > 0) {
            ruleDesc = "consumed quantity (" + Number(consumedQty).toLocaleString() + " plants)";
        } else {
            ruleDesc = "minimum limit";
        }
        errorMsg = '<i class="fa fa-exclamation-triangle me-1"></i> Minimum quantity required is <strong>' + Number(minAllowed).toLocaleString() + ' plants</strong> based on ' + ruleDesc + '.';
    } else if (maxAllowed > 0 && currentVal > maxAllowed) {
        isValid = false;
        errorMsg = '<i class="fa fa-exclamation-triangle me-1"></i> Maximum quantity allowed is <strong>' + Number(maxAllowed).toLocaleString() + ' plants</strong> based on sowed quantity (' + Number(sowingPlants).toLocaleString() + '). Quantity cannot exceed sowed quantity.';
    }

    $card.find(".qty-error-message").each(function() {
        if (!isValid) {
            $(this).html(errorMsg).show();
        } else {
            $(this).hide().html("");
        }
    });

    if (!isValid) {
        $qtyInputs.addClass("is-invalid");
    } else {
        $qtyInputs.removeClass("is-invalid");
    }

    return isValid;
}

function validateAllItemQuantities() {
    let allValid = true;

    $(".production-item-card[data-productid]").each(function() {
        let isValid = validateCardQuantity($(this));
        if (!isValid) {
            allValid = false;
        }
    });

    let $submitBtn = $("#btnSubmitOrder, .btn-update-order");
    if (!allValid) {
        $submitBtn.prop("disabled", true).addClass("disabled").css({
            "opacity": "0.5",
            "cursor": "not-allowed"
        });
    } else {
        $submitBtn.prop("disabled", false).removeClass("disabled").css({
            "opacity": "1.0",
            "cursor": "pointer"
        });
    }

    return allValid;
}

function updateSuggestedProductNamePreview($card) {
    let $select = $card.find(".productname");
    let prdText = $select.find("option:selected").text().trim();

    if (prdText && prdText !== "Select" && prdText !== "") {
        $card.find(".lbl-suggested-product-name-notstarted").text(prdText);
        $card.find(".lbl-suggested-product-name-started").text(prdText);
    }
}

function updateGraftedQuantitiesAndPreviews($card, forceAutoFill = false) {
    let prodQty = parseFloat($card.attr("data-prodqty")) || 0;
    let sowingPlants = parseInt($card.attr("data-sowingplants")) || 0;
    let consumedQty = parseFloat($card.attr("data-consumedqty")) || 0;
    let isFullyConsumed = parseInt($card.attr("data-fullyconsumed")) || 0;
    let bothSowingCompleted = parseInt($card.attr("data-bothsowingcompleted")) || 0;

    let minAllowed = calculateMinAllowedQty($card);
    let maxAllowed = calculateMaxAllowedQty($card);
    let $qtyInputs = $card.find(".qtycal");

    // Auto-fill calculated quantity into edit input ONLY on initial stage status load if existing value is invalid
    if (forceAutoFill || $card.data("autofilled") !== 1) {
        let existingVal = parseFloat($qtyInputs.first().val()) || 0;
        if (existingVal <= 0) {
            $qtyInputs.val(prodQty > 0 ? prodQty : minAllowed);
        } else if (consumedQty > 0 && existingVal < consumedQty) {
            $qtyInputs.val(consumedQty);
        } else if (maxAllowed > 0 && existingVal > maxAllowed) {
            $qtyInputs.val(maxAllowed);
        }
        $card.data("autofilled", 1);
    }

    let $visibleInput = $qtyInputs.filter(":visible");
    if ($visibleInput.length === 0) $visibleInput = $qtyInputs.first();

    let currentVal = parseFloat($visibleInput.val()) || 0;
    let totalQty = currentVal;
    let baseSowed = sowingPlants > 0 ? sowingPlants : prodQty;
    let restOpenStock = Math.max(0, baseSowed - totalQty);

    $card.find(".lbl-sowed-plants").text(Number(sowingPlants).toLocaleString());
    $card.find(".lbl-rest-open-stock").text(Number(restOpenStock).toLocaleString());

    let ruleReason = "";
    if (isFullyConsumed === 1) {
        ruleReason = " (Fully Consumed - Edit Locked)";
    } else if (maxAllowed > 0) {
        ruleReason = " (Max Sowed Limit: " + Number(maxAllowed).toLocaleString() + " plants)";
    } else if (consumedQty > 0) {
        ruleReason = " (Min Consumed Limit: " + Number(consumedQty).toLocaleString() + " plants)";
    }

    $card.find(".lbl-min-allowed-qty").text(Number(minAllowed).toLocaleString() + " plants" + ruleReason);

    // Sync input values across option boxes for this card when not actively being edited
    $qtyInputs.not(document.activeElement).val(totalQty);

    // Option 3 Join preview calculations
    let $selComing = $card.find(".sel-coming-stock-order");
    let $optSelected = $selComing.find("option:selected");
    let targetOrderNo = $optSelected.val() || "[Select Coming Order]";
    let targetCurrentQty = parseFloat($optSelected.data("qty")) || 0;
    let joinMergeQty = restOpenStock;
    let targetNewQty = targetCurrentQty + joinMergeQty;

    $card.find(".lbl-join-merge-qty").text(Number(joinMergeQty).toLocaleString());
    $card.find(".lbl-target-order-no-text").text(targetOrderNo);
    $card.find(".lbl-target-new-qty-text").text(Number(targetNewQty).toLocaleString());

    updateTrayCavityRemainderCalculation($card);
    validateAllItemQuantities();
}

function getExactPlantQtyForSeeds(targetSeeds) {
    if (targetSeeds <= 0) return 0;
    let qty = Math.floor(targetSeeds / 1.30);
    while (Math.ceil(qty * 1.30) < targetSeeds) {
        qty++;
    }
    while (Math.ceil(qty * 1.30) > targetSeeds && qty > 0) {
        let prev = qty - 1;
        if (Math.ceil(prev * 1.30) === targetSeeds) {
            qty = prev;
        } else {
            break;
        }
    }
    return qty;
}

function updateTrayCavityRemainderCalculation($card) {
    let $qtyInput = $card.find(".qtycal:visible");
    if ($qtyInput.length === 0) $qtyInput = $card.find(".qtycal").first();

    let rawVal = ($qtyInput.val() || "").trim();
    let editedQty = parseFloat(rawVal) || 0;
    let $calcBox = $card.find(".tray-cavity-calc-box");

    if (editedQty <= 0) {
        $calcBox.hide().html("");
        return;
    }

    // 1. Determine Root Seed Tray Cavity Count
    let trayCavity = parseInt($card.attr("data-rootcavity")) || parseInt($card.attr("data-traycavity")) || 104;
    if (trayCavity <= 0) trayCavity = 104;

    // 2. Buffer percentage (+30% standard germination/buffer factor)
    let sowingQtySeeds = Math.ceil(editedQty * 1.30);

    // 3. Remainder calculations
    let remainderSeeds = sowingQtySeeds % trayCavity;
    let remainderDirect = Math.round(editedQty) % trayCavity;

    let totalTraysForSowing = Math.ceil(sowingQtySeeds / trayCavity);

    // Calculate nearest upper & lower seed quantities where seeds % trayCavity === 0
    let lowerSeedTrays = Math.floor(sowingQtySeeds / trayCavity);
    let upperSeedTrays = Math.ceil(sowingQtySeeds / trayCavity);

    let lowerSowingSeeds = lowerSeedTrays * trayCavity;
    let upperSowingSeeds = upperSeedTrays * trayCavity;

    // Exact plant quantities for sowed seed full trays (+30% buffer)
    let lowerTargetQty = getExactPlantQtyForSeeds(lowerSowingSeeds);
    let upperTargetQty = getExactPlantQtyForSeeds(upperSowingSeeds);

    // Direct plant quantity full trays (no buffer)
    let lowerDirectTrays = Math.floor(editedQty / trayCavity);
    let upperDirectTrays = Math.ceil(editedQty / trayCavity);
    let lowerDirectQty = lowerDirectTrays * trayCavity;
    let upperDirectQty = upperDirectTrays * trayCavity;

    if (lowerTargetQty < 1) lowerTargetQty = Math.max(1, lowerDirectQty);

    let isMatched = (remainderSeeds === 0 || remainderDirect === 0);

    let html = '';

    if (isMatched) {
        html += '<div class="alert alert-success py-2.5 px-3 mb-0 border-0 shadow-sm" style="border-radius: 8px; background-color: #f0fdf4; border-left: 4px solid #22c55e !important;">';
        html += '  <div class="fw-bold text-success fs-12 mb-1">';
        html += '    <i class="fa fa-check-circle me-1"></i> <strong>Tray Cavity Matched (Remainder = 0):</strong>';
        html += '  </div>';
        html += '  <div class="text-dark fs-12">';
        html += '    Target Order Qty: <strong>' + Number(editedQty).toLocaleString() + ' plants</strong> &bull; Sowing Qty (+30% Buffer): <strong>' + Number(sowingQtySeeds).toLocaleString() + ' seeds</strong>.<br>';
        html += '    Root Seed Tray Cavity: <strong>' + trayCavity + ' holes/tray</strong> &bull; Sowing Trays Required: <strong>' + Number(totalTraysForSowing).toLocaleString() + ' trays</strong> &bull; <span class="badge bg-success text-white px-2 py-1">Remainder = 0 holes</span>';
        html += '  </div>';
        html += '</div>';
    } else {
        let diffLowerSeed = editedQty - lowerTargetQty;
        let diffUpperSeed = upperTargetQty - editedQty;

        let diffLowerDirect = editedQty - lowerDirectQty;
        let diffUpperDirect = upperDirectQty - editedQty;

        html += '<div class="alert alert-warning py-2.5 px-3 mb-0 border-0 shadow-sm" style="border-radius: 8px; background-color: #fffbeb; border-left: 4px solid #f59e0b !important;">';
        html += '  <div class="fw-bold text-dark fs-12 mb-1">';
        html += '    <i class="fa fa-exclamation-triangle me-1 text-warning"></i> <strong>Root Seed Tray Cavity Check (Remainder != 0):</strong>';
        html += '  </div>';
        html += '  <div class="text-dark fs-12 mb-2">';
        html += '    Edited Qty: <strong>' + Number(editedQty).toLocaleString() + ' plants</strong> (+30% Buffer = <strong>' + Number(sowingQtySeeds).toLocaleString() + ' seeds</strong>) / <strong>' + trayCavity + ' holes/tray</strong> leaves a remainder of <strong class="text-danger">' + remainderSeeds + ' extra holes</strong>.';
        html += '  </div>';
        
        html += '  <div class="d-flex flex-column gap-2 mt-2 pt-2 border-top">';
        html += '    <div class="fw-bold text-dark fs-11"><i class="fa fa-magic text-primary me-1"></i> Quick Adjust Target Qty for 0 Remainder (Full Trays):</div>';
        
        // Option 1: Sowing Seeds (+30% Buffer)
        html += '    <div class="d-flex align-items-center gap-2 flex-wrap">';
        html += '      <span class="text-muted fs-11" style="min-width: 140px;">• Based on Sowed Seeds (+30% Buffer):</span>';
        if (lowerTargetQty > 0 && lowerTargetQty !== editedQty) {
            html += '      <button type="button" class="btn btn-sm btn-outline-danger btn-snap-qty py-1 px-2.5 fs-11" data-qty="' + lowerTargetQty + '">';
            html += '        <i class="fa fa-minus-circle me-1"></i> Lower (' + lowerSeedTrays + ' Trays): ' + Number(lowerTargetQty).toLocaleString() + ' plants (-' + Number(diffLowerSeed).toLocaleString() + ')';
            html += '      </button>';
        }
        if (upperTargetQty > 0 && upperTargetQty !== editedQty) {
            html += '      <button type="button" class="btn btn-sm btn-outline-success btn-snap-qty py-1 px-2.5 fs-11" data-qty="' + upperTargetQty + '">';
            html += '        <i class="fa fa-plus-circle me-1"></i> Upper (' + upperSeedTrays + ' Trays): ' + Number(upperTargetQty).toLocaleString() + ' plants (+' + Number(diffUpperSeed).toLocaleString() + ')';
            html += '      </button>';
        }
        html += '    </div>';

        // Option 2: Direct Net Plants
        if (lowerDirectQty > 0 || upperDirectQty > 0) {
            html += '    <div class="d-flex align-items-center gap-2 flex-wrap">';
            html += '      <span class="text-muted fs-11" style="min-width: 140px;">• Based on Net Plants (No Buffer):</span>';
            if (lowerDirectQty > 0 && lowerDirectQty !== editedQty && lowerDirectQty !== lowerTargetQty) {
                html += '      <button type="button" class="btn btn-sm btn-outline-danger btn-snap-qty py-1 px-2.5 fs-11" data-qty="' + lowerDirectQty + '">';
                html += '        <i class="fa fa-minus-circle me-1"></i> Lower (' + lowerDirectTrays + ' Trays): ' + Number(lowerDirectQty).toLocaleString() + ' plants (-' + Number(diffLowerDirect).toLocaleString() + ')';
                html += '      </button>';
            }
            if (upperDirectQty > 0 && upperDirectQty !== editedQty && upperDirectQty !== upperTargetQty) {
                html += '      <button type="button" class="btn btn-sm btn-outline-success btn-snap-qty py-1 px-2.5 fs-11" data-qty="' + upperDirectQty + '">';
                html += '        <i class="fa fa-plus-circle me-1"></i> Upper (' + upperDirectTrays + ' Trays): ' + Number(upperDirectQty).toLocaleString() + ' plants (+' + Number(diffUpperDirect).toLocaleString() + ')';
                html += '      </button>';
            }
            html += '    </div>';
        }
        
        html += '  </div>';
        html += '</div>';
    }

    $calcBox.html(html).show();
}

function fetchComingStockOrders($card) {
    let productid = $card.find(".productname").val() || $card.data("productid");
    let stockorder_no = $("input[name='production_stockorder_no']").val();
    let projectid = $("input[name='projectid']").val();
    let $select = $card.find(".sel-coming-stock-order");

    if ($select.data("loaded") === 1) return;

    $select.html('<option value="" disabled selected>Loading coming stock orders...</option>');

    $.ajax({
        url: "modal/product_modal.php?action=GetComingStockOrdersForSameRoot",
        type: "POST",
        data: {
            projectid: projectid,
            stockorder_no: stockorder_no,
            productid: productid
        },
        dataType: "json",
        success: function(res) {
            if (res.status === "success" && res.data && res.data.length > 0) {
                let html = '<option value="" disabled selected>Select Coming Stock Order (Same Root)</option>';
                res.data.forEach(item => {
                    html += `<option value="${item.stockorder_no}" data-qty="${item.quantity}">${item.stockorder_no} - ${item.product_name} (${Number(item.quantity).toLocaleString()} plants - Deliv: ${item.expected_delivery_date})</option>`;
                });
                $select.html(html).data("loaded", 1);
            } else {
                $select.html('<option value="" disabled selected>No coming stock orders found for same root seed</option>');
            }
            updateGraftedQuantitiesAndPreviews($card);
        },
        error: function() {
            $select.html('<option value="" disabled selected>Error loading coming stock orders</option>');
        }
    });
}

$(document).ready(function(){
  updateFormLockState();

  $(".production-item-card[data-productid]").each(function() {
      fetchStageStatusForOrderRow($(this));
  });

  // Clear invalid border on reason textarea input
  $(document).on("input change keyup", ".edit-reason-textarea", function() {
      if ($.trim($(this).val()) !== "") {
          $(this).removeClass("is-invalid");
      }
  });

  // Dynamic live update when quantity input changes (no forced snapping on typing)
  $(document).on("input change keyup", ".qtycal", function() {
      let $card = $(this).closest(".production-item-card");
      updateGraftedQuantitiesAndPreviews($card, false);
      updateTrayCavityRemainderCalculation($card);
  });

  // Handle Quick Snap Button Click for Remainder 0
  $(document).on("click", ".btn-snap-qty", function(e) {
      e.preventDefault();
      let newQty = $(this).data("qty");
      let $card = $(this).closest(".production-item-card");
      if (newQty && newQty > 0) {
          $card.find(".qtycal").val(newQty).trigger("input").trigger("change");
      }
  });

  // Dynamic preview update when coming stock order is selected
  $(document).on("change", ".sel-coming-stock-order", function() {
      let $card = $(this).closest(".production-item-card");
      updateGraftedQuantitiesAndPreviews($card);
  });

  // Handle Past Order Edit Checkbox toggle
  $(document).on("change", "#chkAllowEditPastOrder", function() {
      if ($(this).is(":checked")) {
          Swal.fire({
              title: "Allow Editing Past Production Order?",
              text: "The expected delivery date for this order has already passed. Are you sure you want to unlock editing for this past order?",
              icon: "warning",
              showCancelButton: true,
              confirmButtonColor: "#d33",
              cancelButtonColor: "#3085d6",
              confirmButtonText: "Yes, Unlock Editing",
              cancelButtonText: "No, Keep Locked"
          }).then((result) => {
              if (result.isConfirmed) {
                  updateFormLockState();
              } else {
                  $("#chkAllowEditPastOrder").prop("checked", false);
                  updateFormLockState();
              }
          });
      } else {
          updateFormLockState();
      }
  });

  // Handle Item Checkbox toggle
  $(document).on("change", ".chkEnableRowEdit", function() {
      let $checkbox = $(this);
      let $card = $checkbox.closest(".production-item-card");
      let isPast = parseInt($card.data("ispast")) || 0;

      if ($checkbox.is(":checked")) {
          if (isPast === 1 && !$("#chkAllowEditPastOrder").is(":checked")) {
              Swal.fire({
                  icon: "warning",
                  title: "Editing Locked",
                  text: "The delivery date for this order has passed. Please check 'Allow Edit Past Order' checkbox above first to unlock editing."
              });
              $checkbox.prop("checked", false);
              return;
          }

          $card.addClass("row-editing-active");
          $card.find(".inline-question-box").slideDown(200);
          handleRowChoice($card);
      } else {
          $card.removeClass("row-editing-active");
          $card.find(".inline-question-box").slideUp(200);
          $card.find(".productname").prop("disabled", true);
          $card.find(".qtycal, .sowingdate, .expecteddelivery").prop("readonly", true);
      }
  });

  // Remove card item
  $(document).on("click", ".removeRow", function () {
      $(this).closest(".production-item-card").remove();
  });

  function fetchAndApplySowingDates($card) {
      let val = $card.find(".new-order-product-select").val();
      let $datesBox = $card.find(".box-new-order-sowing-dates");
      let currentSowingDate = $card.find(".sowingdate").val() || "";
      let expectedReadyDate = $card.find(".new-order-expected-delivery").val() || $card.find(".expecteddelivery").val() || "";
      let projectid = $("input[name='projectid']").val() || $("body").data("projectid") || 0;

      if (val && val !== "") {
          $datesBox.stop(true, true).slideDown(150);
          let $rootInput = $card.find(".new-order-root-sowingdate");
          let $scionInput = $card.find(".new-order-scion-sowingdate");

          // Compute & Display Internal Dispatch Date (Ready Date - 20 Days)
          let internalDispatchDate = subtractDaysFromDate(expectedReadyDate, 20);
          $card.find(".lbl-new-internal-dispatch-date").text(internalDispatchDate || "N/A");

          let selectedProd = (window.rawProductMasterList || []).find(p => p.ProductID == val || p.sku == val);
          let isGrafted = selectedProd ? (selectedProd.IsGrafted === "Grafted" || selectedProd.IsGrafted === "1") : true;

          if (isGrafted) {
              $card.find(".box-root-sowingdate-col").removeClass("col-md-12").addClass("col-md-6").show();
              $card.find(".box-scion-sowingdate-col").removeClass("col-md-12").addClass("col-md-6").show();
          } else {
              $card.find(".box-root-sowingdate-col").removeClass("col-md-6").addClass("col-md-12").show();
              $card.find(".box-scion-sowingdate-col").hide();
          }

          if (expectedReadyDate) {
              $.ajax({
                  url: "modal/product_modal.php?action=GetSowingDateForProduct",
                  type: "POST",
                  data: {
                      projectid: projectid,
                      productid: val,
                      deliverydate: expectedReadyDate
                  },
                  dataType: "json",
                  success: function(res) {
                      if (res && res.status === "success") {
                          if (res.dispatchedate_internal) {
                              $card.find(".lbl-new-internal-dispatch-date").text(res.dispatchedate_internal);
                          }
                          let sowedQty = parseFloat($card.attr("data-sowingplants")) || 0;
                          if (res.RootSeed && res.RootSeed.sowingdate) {
                              if (sowedQty > 0 && currentSowingDate) {
                                  $rootInput.val(currentSowingDate);
                                  $card.find(".lbl-root-sowing-calc-preview .calc-text").html("Root Sowing Done: " + currentSowingDate);
                              } else {
                                  $rootInput.val(res.RootSeed.sowingdate);
                                  let rootFormula = res.RootSeed.formula ? res.RootSeed.formula : ("Growing: " + res.RootSeed.growingdays + " days");
                                  $card.find(".lbl-root-sowing-calc-preview .calc-text").html(htmlEscape(rootFormula));
                              }
                          }
                          if (res.ScionSeed && res.ScionSeed.sowingdate) {
                              $scionInput.val(res.ScionSeed.sowingdate);
                              let scionFormula = res.ScionSeed.formula ? res.ScionSeed.formula : ("Growing: " + res.ScionSeed.growingdays + " days");
                              $card.find(".lbl-scion-sowing-calc-preview .calc-text").html(htmlEscape(scionFormula));
                          }
                      } else {
                          let scionGrowingDays = parseInt(selectedProd ? (selectedProd.ScionSeedgrowingdays || selectedProd.fullsycledays || 30) : 30) || 30;
                          let calculatedScionDate = subtractDaysFromDate(expectedReadyDate, scionGrowingDays);
                          if (currentSowingDate) $rootInput.val(currentSowingDate);
                          $scionInput.val(calculatedScionDate);
                      }
                      validateDispatchDateMatching($card);
                      validateAllItemQuantities();
                  },
                  error: function() {
                      let scionGrowingDays = parseInt(selectedProd ? (selectedProd.ScionSeedgrowingdays || selectedProd.fullsycledays || 30) : 30) || 30;
                      let calculatedScionDate = subtractDaysFromDate(expectedReadyDate, scionGrowingDays);
                      if (currentSowingDate) $rootInput.val(currentSowingDate);
                      $scionInput.val(calculatedScionDate);
                      validateDispatchDateMatching($card);
                      validateAllItemQuantities();
                  }
              });
          } else if (currentSowingDate) {
              $rootInput.val(currentSowingDate);
              $scionInput.val(addDaysToDate(currentSowingDate, 4));
              validateDispatchDateMatching($card);
              validateAllItemQuantities();
          }
      } else {
          $datesBox.stop(true, true).slideUp(150);
          validateDispatchDateMatching($card);
          validateAllItemQuantities();
      }
  }

  // Handle new order product selection change & Expected Delivery Date change
  $(document).on("change", ".new-order-product-select", function() {
      let $card = $(this).closest(".production-item-card");
      fetchAndApplySowingDates($card);
  });

  $(document).on("change input", ".new-order-expected-delivery", function() {
      let $card = $(this).closest(".production-item-card");
      fetchAndApplySowingDates($card);
  });

  // Auto calculate Scion Sowing Date (+4 days) when Root Sowing Date changes
  $(document).on("change input", ".new-order-root-sowingdate", function() {
      let $card = $(this).closest(".production-item-card");
      let rootVal = $(this).val();
      if (rootVal) {
          let scionVal = addDaysToDate(rootVal, 4);
          $card.find(".new-order-scion-sowingdate").val(scionVal);
      }
  });

  window.cachedProductOptions = '<option value="" selected disabled>Select Product Name</option>';
  window.rawProductMasterList = [];

  GetProductListFrommaster_callback(function(res){
    if(res && res.length > 0){
        window.rawProductMasterList = res;
        var prdlist = '<option value="" selected disabled>Select Product Name</option>';
        for (var i = 0; i < res.length; i++) {
          var rawName = res[i].ProductName || '';
          var displayName = rawName.replace(/^(NonGrafted|Non-Grafted)\s*[-:]?\s*/i, '');
          prdlist += '<option value="'+res[i].ProductID+'" data-priceofproduct="'+res[i].PriceOfProduct+'">'+displayName+'</option>';
        }
        window.cachedProductOptions = prdlist;

        $(".productname").append(prdlist).each(function(){
          let pid = $(this).data("productid");
          if(pid) {
            $(this).val(pid).trigger("change");
          }
        });

        $(".production-item-card[data-productid]").each(function() {
            let $c = $(this);
            let opts = getFilteredProductOptions($c);
            $c.find(".new-order-product-select").html(opts);
        });
      }
      updateFormLockState();
  });

  let existingClientId = $("#edit_client_id").val();
  GetclientDetailsFrommaster('all', 'all', 'clientid');
  
  if(existingClientId){
      var checkExist = setInterval(function() {
         if ($("#clientid option").length > 1) {
            $("#clientid").val(existingClientId).trigger("change");
            clearInterval(checkExist);
         }
      }, 100);
  }

  // Handle product selection & auto-calculate missing Sowing Date
  $(document).on("change", "#EditProductionOrderitemsTable .productname", function () {
    const $row = $(this).closest("tr");
    const productid = $(this).val();
    let $expectedInput = $row.find(".expecteddelivery");
    let $sowingInput = $row.find(".sowingdate");

    updateSuggestedProductNamePreview($row);

    let currentSowing = $sowingInput.val();
    let currentReady = $expectedInput.val();

    if (!productid) return;

    fetchStageStatusForOrderRow($row);

    getExpectedDelivertDateandSowingDate(productid, function (res) {
      if (!Array.isArray(res) || res.length === 0) return;
      const data = res[0];
      let fullDays = parseInt(data.fullcycledays) || 0;

      if (data.expecteddeliverydate || data.sowingdate) {
          let formattedReady = formatToYYYYMMDD(data.expecteddeliverydate);
          let formattedSowing = formatToYYYYMMDD(data.sowingdate);

          if (!fullDays && formattedReady && formattedSowing) {
              fullDays = getDaysBetween(formattedSowing, formattedReady);
          }

          $sowingInput.data("fullcycledays", fullDays);
          $expectedInput.data("fullcycledays", fullDays);

          let effectiveReady = currentReady || formattedReady;
          if (effectiveReady) {
              $expectedInput.val(effectiveReady);
          }

          if (!currentSowing || currentSowing === '0000-00-00') {
              if (effectiveReady && fullDays > 0) {
                  let calcSowing = subtractDaysFromDate(effectiveReady, fullDays);
                  let todayStr = new Date().toISOString().split("T")[0];
                  
                  if (loginroleid <= 2 && calcSowing < todayStr) {
                      $sowingInput.val(todayStr);
                      let newReady = addDaysToDate(todayStr, fullDays);
                      $expectedInput.val(newReady);
                  } else {
                      $sowingInput.val(calcSowing);
                  }
              } else if (loginroleid <= 2) {
                  let todayStr = new Date().toISOString().split("T")[0];
                  $sowingInput.val(todayStr);
                  if (fullDays > 0) {
                      $expectedInput.val(addDaysToDate(todayStr, fullDays));
                  }
              } else if (formattedSowing) {
                  $sowingInput.val(formattedSowing);
              }
          }
      }
    });
  });

  // Dynamic recalculation when Sowing Date is edited
  $(document).on("change", "#EditProductionOrderitemsTable .sowingdate", function () {
      const $row = $(this).closest("tr");
      let sowingVal = $(this).val();
      let fullDays = $(this).data("fullcycledays");
      let $expectedInput = $row.find(".expecteddelivery");

      if (sowingVal && fullDays > 0) {
          let calcReady = addDaysToDate(sowingVal, fullDays);
          $expectedInput.val(calcReady);
      }
  });

  // Dynamic recalculation when Expected Ready Date is edited
  $(document).on("change", "#EditProductionOrderitemsTable .expecteddelivery", function () {
      const $row = $(this).closest("tr");
      let readyVal = $(this).val();
      let fullDays = $(this).data("fullcycledays");
      let $sowingInput = $row.find(".sowingdate");

      if (readyVal && fullDays > 0) {
          let calcSowing = subtractDaysFromDate(readyVal, fullDays);
          $sowingInput.val(calcSowing);
      }
  });

  $("#editProductStockOrderForm").on("submit", function (e) {
      e.preventDefault();
      let $form = $(this);

      if (typeof checkGlobalDispatchMatchState === "function" && !checkGlobalDispatchMatchState()) {
          Swal.fire({
              icon: "error",
              title: "Dispatch Schedule Mismatch Error",
              text: "Cannot save data: Dispatch schedule mismatch between parent root and new scion order."
          });
          return false;
      }

      let hasPast = false;
      let minQtyError = "";
      let fullyConsumedError = "";
      let missingReasonError = false;

      // 1. Validate Q1 Edit Reason
      $(".production-item-card[data-productid]").each(function() {
          let $card = $(this);
          let isFullyConsumed = parseInt($card.attr("data-fullyconsumed")) || 0;
          if (isFullyConsumed === 1) return;

          let reasonVal = $.trim($card.find(".edit-reason-textarea").val());
          if (!reasonVal || reasonVal === "") {
              missingReasonError = true;
              $card.find(".edit-reason-textarea").addClass("is-invalid");
          } else {
              $card.find(".edit-reason-textarea").removeClass("is-invalid");
          }
      });

      if (missingReasonError) {
          Swal.fire({
              icon: "warning",
              title: "Reason Required",
              text: "Please enter the reason why you are editing the production stock order (Q1).",
              confirmButtonColor: "#2563eb",
              confirmButtonText: "<i class='fa fa-edit me-1'></i> Enter Reason"
          }).then(() => {
              let $firstInvalid = $(".edit-reason-textarea.is-invalid").first();
              if ($firstInvalid.length) {
                  $('html, body').animate({
                      scrollTop: $firstInvalid.offset().top - 140
                  }, 300);
                  $firstInvalid.focus();
              }
          });
          return false;
      }

      $(".production-item-card[data-ispast='1']").each(function() {
          let isFullyConsumed = parseInt($(this).attr("data-fullyconsumed")) || 0;
          if (isFullyConsumed === 1) return;
          hasPast = true;
      });

      if (hasPast && !$("#chkAllowEditPastOrder").is(":checked")) {
          Swal.fire({
              icon: "error",
              title: "Past Delivery Order Locked",
              text: "The delivery date for this order has passed. You must check 'Allow Edit Past Order' to confirm editing."
          });
          return false;
      }

      let missingNewOrderError = "";
      $(".production-item-card[data-productid]").each(function() {
          let $card = $(this);
          let isFullyConsumed = parseInt($card.attr("data-fullyconsumed")) || 0;
          if (isFullyConsumed === 1) return;

          let enteredQty = parseFloat($card.find(".qtycal:visible").val()) || parseFloat($card.find(".qtycal").val()) || 0;
          let minAllowed = calculateMinAllowedQty($card);

          if (enteredQty < minAllowed) {
              minQtyError = "Order quantity cannot be less than minimum allowed quantity (" + minAllowed.toLocaleString() + " plants).";
              return false;
          }

          let choice = $card.find(".radEditChoice:checked").val() || "only_number";
          let sowedQty = parseFloat($card.attr("data-sowingplants")) || 0;
          let totalQty = parseFloat($card.find(".qtycal:visible").val()) || parseFloat($card.find(".qtycal").val()) || 0;
          let restOpenStock = Math.max(0, totalQty - sowedQty);

          if ((choice === "create_new_order" || choice === "full_split") && restOpenStock > 0) {
              let newProdVal = $card.find(".new-order-product-select").val();
              let newEddVal = $card.find(".new-order-expected-delivery").val();
              let newRootSdVal = $card.find(".new-order-root-sowingdate").val();
              let newScionSdVal = $card.find(".new-order-scion-sowingdate").val();

              if (!newProdVal || newProdVal === "") {
                  missingNewOrderError = "Warning: Product Name for the new production order is missing. Please select a product name.";
                  return false;
              }
              if (!newEddVal || newEddVal === "") {
                  missingNewOrderError = "Warning: Expected Ready Delivery Date for the new production order is missing. Please select a delivery date.";
                  return false;
              }
              if (!newRootSdVal || newRootSdVal === "") {
                  missingNewOrderError = "Warning: Root Seed Sowing Date for the new production order is missing.";
                  return false;
              }
              let selectedProd = (window.rawProductMasterList || []).find(p => p.ProductID == newProdVal || p.sku == newProdVal);
              let isGrafted = selectedProd ? (selectedProd.IsGrafted === "Grafted" || selectedProd.IsGrafted === "1") : true;
              if (isGrafted && (!newScionSdVal || newScionSdVal === "")) {
                  missingNewOrderError = "Warning: Scion Seed Sowing Date for the new production order is missing.";
                  return false;
              }
          }
      });

      if (missingNewOrderError !== "") {
          Swal.fire({
              icon: "warning",
              title: "Missing New Order Details",
              text: missingNewOrderError
          });
          return false;
      }

      if (fullyConsumedError !== "") {
          Swal.fire({
              icon: "error",
              title: "Order Fully Consumed",
              text: fullyConsumedError
          });
          return false;
      }

      if (minQtyError !== "") {
          Swal.fire({
              icon: "error",
              title: "Quantity Error",
              text: minQtyError
          });
          return false;
      }

      let stockOrderNo = $("input[name='production_stockorder_no']").val() || "";
      let clientName = $("#clientid option:selected").text().trim();
      if (!clientName || clientName === "Select") {
          clientName = "Client Selected";
      }

      // Build Confirmation Summary Modal HTML
      let summaryHtml = '<div class="text-start fs-13 mt-2">';
      summaryHtml += '<div class="p-2 mb-3 bg-light rounded border">';
      summaryHtml += '<div class="mb-1"><strong>Order Number:</strong> <span class="text-primary fw-bold font-monospace">' + stockOrderNo + '</span></div>';
      summaryHtml += '<div><strong>Client Name:</strong> <span class="text-dark fw-bold">' + clientName + '</span></div>';
      summaryHtml += '</div>';

      summaryHtml += '<div class="fw-bold text-dark mb-2"><i class="fa fa-list me-1 text-primary"></i> Line Items Update Summary:</div>';
      summaryHtml += '<div class="table-responsive rounded border mb-2" style="max-height: 240px; overflow-y: auto;">';
      summaryHtml += '<table class="table table-sm table-striped mb-0 align-middle" style="font-size: 12px;">';
      summaryHtml += '<thead class="table-dark"><tr><th>Product</th><th class="text-center">Qty</th><th class="text-center">Type</th><th>Action Summary</th></tr></thead><tbody>';

      $(".production-item-card[data-productid]").each(function() {
          let $card = $(this);
          let isChecked = $card.find(".chkEnableRowEdit").is(":checked");
          if (!isChecked) return;

          let prdText = $card.find(".productname option:selected").text().trim();
          if (!prdText || prdText === "Select") prdText = "Product Item";
          let qty = $card.find(".qtycal:visible").val() || $card.find(".qtycal").val() || "0";
          let isGrafted = parseInt($card.data("isgrafted")) || 0;
          let sowingPlants = parseInt($card.data("sowingplants")) || 0;

          let typeBadge = isGrafted === 1 ? '<span class="badge bg-success text-white px-2 py-1"><i class="fa fa-cut me-1"></i>Grafted</span>' : '<span class="badge bg-info text-white px-2 py-1"><i class="fa fa-seedling me-1"></i>Non-Grafted</span>';
          let actionDesc = '';

          let choice = $card.find(".radEditChoice:checked").val() || "only_number";
          let choiceText = (choice === "create_new_order" || choice === "full_split") ? "Create New Production Order for Balance" : "Only Edit Number";
          if (sowingPlants > 0) {
              actionDesc = '<strong>' + choiceText + ' (Sowing Done):</strong> Sowed (' + sowingPlants.toLocaleString() + ') fixed under ' + stockOrderNo + '.';
          } else {
              actionDesc = '<strong>' + choiceText + ':</strong> Update order quantity under ' + stockOrderNo + '.';
          }

          summaryHtml += '<tr>';
          summaryHtml += '<td class="fw-bold text-dark">' + prdText + '</td>';
          summaryHtml += '<td class="text-center fw-bold text-primary">' + parseInt(qty).toLocaleString() + '</td>';
          summaryHtml += '<td class="text-center">' + typeBadge + '</td>';
          summaryHtml += '<td>' + actionDesc + '</td>';
          summaryHtml += '</tr>';
      });

      summaryHtml += '</tbody></table></div>';
      summaryHtml += '<div class="alert alert-info py-2 px-3 mt-2 mb-0 fs-12 border-0 shadow-sm"><i class="fa fa-info-circle me-1"></i> Please review your order edits above and click <strong>"Yes, Update Order"</strong> to proceed.</div>';
      summaryHtml += '</div>';

      // Confirm with user in Modal
      Swal.fire({
          title: "Confirm Production Order Update",
          html: summaryHtml,
          icon: "question",
          showCancelButton: true,
          confirmButtonColor: "#2563eb",
          cancelButtonColor: "#64748b",
          confirmButtonText: "<i class='fa fa-check-circle me-1'></i> Yes, Update Order",
          cancelButtonText: "<i class='fa fa-times-circle me-1'></i> Cancel",
          width: "680px",
          customClass: {
              popup: "rounded-3"
          }
      }).then((result) => {
          if (result.isConfirmed) {
              executeOrderUpdateAJAX($form);
          }
      });

      return false;
  });

  function executeOrderUpdateAJAX($form) {
      if (!$form || !$form.length || typeof $form.serialize !== "function") {
          $form = $("#editProductStockOrderForm");
      }

      // Show Waiting / Loading Spinner Message before sending request
      Swal.fire({
          title: "Updating Production Order...",
          html: "<div class='py-3 text-center'><i class='fa fa-spinner fa-spin fa-3x text-primary mb-3 d-block'></i><h6 class='fw-bold text-dark mb-1'>Processing your production stock order update...</h6><small class='text-muted'>Please wait while stock orders and seed calculations are updated.</small></div>",
          allowOutsideClick: false,
          allowEscapeKey: false,
          showConfirmButton: false,
          didOpen: () => {
              Swal.showLoading();
          }
      });

      // Temporarily enable disabled fields so their values submit in form data
      $(".production-cards-container select:disabled").prop("disabled", false);

      $.ajax({
          url: "modal/product_modal.php?action=UpdateEditedProductStockOrder",
          type: "POST",
          data: $form.serialize(),
          dataType: "json",
          success: function (response) {
            Swal.close();
            if (response && response.status === "success") {
                Swal.fire({
                    icon: "success",
                    title: "Production Stock Order Updated!",
                    text: response.message || "Order updated successfully.",
                    confirmButtonText: "Refresh Page",
                    confirmButtonColor: "#10b981",
                    allowOutsideClick: false
                }).then((result) => {
                    location.reload();
                });
            } else {
                updateFormLockState();
                Swal.fire({
                    icon: "error",
                    title: "Update Failed",
                    text: (response && response.message) ? response.message : "Something went wrong. Please try again."
                });
            }
          },
          error: function (xhr, status, error) {
              Swal.close();
              updateFormLockState();
              Swal.fire({
                  icon: "error",
                  title: "Server Error",
                  text: "Unable to update Production Stock Order. Error: " + (error || "Server connection failure")
              });
          }
      });
  }

  $(document).on("click", ".addClientbtn", function() {
    let routurl = $("body").data("routurl");
    let key = $("body").data("key");
    $("#prdordotherModalheader").html("New Client Registration");
    $("#prdordotherModalContent").load(routurl+"krf-orderandsales/addclient.php?k="+key+"&dz=11");
    $("#prdordotherModal").modal("show");
  });
});
</script>
