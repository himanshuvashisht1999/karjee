<?php
$addressMode = ($_GET['mode'] ?? '') === 'address';
$orderMode = (($addressMode || ($_GET['mode'] ?? '') === 'edit') || (!isset($_GET['mode']) && (!empty($_GET['orderno']) || !empty($_GET['psono'])))) ? 'edit' : 'add';
include(__DIR__ . '/../header.php');
$confirmedMode = ($_GET['orderkind'] ?? '') === 'confirmed';
if ($confirmedMode) { require_once __DIR__.'/module_order/confirmed_access.php'; confirmedAuthorize($addressMode); if(empty($_SESSION['confirmed_edit_csrf'])) $_SESSION['confirmed_edit_csrf']=bin2hex(random_bytes(32)); }
date_default_timezone_set("Asia/Calcutta");
$mdid = ""; 
$tmrdate = date('Y-m-d', strtotime('+1 day'));
if (isset($_GET['mdid'])) {
  $mdid = $_GET['mdid'];
}
$dddr = loginmodulesdetails_frommaster($authconn, $companyid, $subcompanyid, $projectid, "moduleid", $mdid);
$groupid = "";
if (count($dddr) > 0) {
  $groupid = $dddr[0]['groupid'];
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/edit_sale_order.css" rel="stylesheet">
<link href="css/order_modal.css?v=20261004-datepicker-5" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<?php if ($addressMode): ?>
<style>#step1,#step2,.step-item[data-step="1"],.step-item[data-step="2"],.prev-step[data-prev="2"]{display:none!important}</style>
<?php endif; ?>
<div class="premium-container"> 
  <div class="page-header shadow">
    <div class="d-flex align-items-center gap-3">
      <i class="fa fa-file-invoice-dollar fa-2x"></i>
      <h3><?php echo $orderMode === 'edit' ? ($confirmedMode ? 'Edit Confirmed Sales Order' : ($addressMode ? 'Update Address & Freight' : 'Edit Provisional Sales Order')) : 'Add Provisional Sales Order'; ?></h3>
    </div>
    <div class="d-flex gap-2">
      <a href="../leadmanagement/index.php?k=<?php echo urlencode($key); ?>" class="btn btn-light text-primary fw-bold border border-primary-subtle d-flex align-items-center gap-2">
        <i class="fa fa-arrow-left"></i> Back to Lead Management
      </a>
      <button class="btn btn-refresh" onclick="window.location.reload();">
        <i class="fa fa-sync me-2"></i><?php echo _t('refresh'); ?>
      </button>
      <button class="btn btn-light gotoprovisionalorderlist">
        <i class="fa fa-list me-2"></i><?php echo $confirmedMode ? 'Confirmed Orders' : ((($_GET['return_to'] ?? '') === 'draft_followup') ? 'Back to Draft Follow-up' : _t('provisional_orders')); ?>
      </button>
    </div>
  </div>

  <div id="editOrderMessage" class="alert alert-info" role="status"><?php echo $orderMode === 'edit' ? ($confirmedMode ? 'Loading confirmed sales order...' : 'Loading order...') : 'New provisional order'; ?></div>
  <form id="salesOrderForm" aria-busy="true" novalidate autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
<?php if ($confirmedMode): ?>
<div class="alert alert-info">Confirmed order modification — existing production and sowing history are retained.</div>
<div class="card mb-3"><div class="card-header fw-bold">Production and sowing summary</div><div class="card-body"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th>Stock Order No.</th><th>Stock Qty</th><th>Client Order Qty</th><th>Planned Sowing Qty</th><th>Actual Sown Qty</th><th>Remaining</th><th>Status</th></tr></thead><tbody id="confirmedSowingRows"></tbody></table></div><small>Actual sown quantity counts recorded tray cavities. Grafted plants show Root and Scion separately; non-grafted plants show Scion only.</small></div></div>
<?php endif; ?>
    <!-- Dummy inputs to trick browser password and address autofill -->
    <input type="text" name="fake_usernamenotremember" style="display:none!important;" aria-hidden="true" tabindex="-1" autocomplete="new-password" />
    <input type="password" name="fake_passwordnotremember" style="display:none!important;" aria-hidden="true" tabindex="-1" autocomplete="new-password" />

    <!-- Step Indicator -->
    <div class="wizard-steps mb-5">
        <div class="step-item active" data-step="1">1 <span class="step-label">Client Info</span></div>
        <div class="step-item" data-step="2">2 <span class="step-label">Order Details</span></div>
        <div class="step-item" data-step="3"><?php echo $addressMode ? 1 : 3; ?> <span class="step-label">Delivery</span></div>
        <div class="step-item" data-step="4"><?php echo $addressMode ? 2 : 4; ?> <span class="step-label">Freight</span></div>
    </div>

    <!-- Step 1: Client Information -->
    <div class="wizard-content-step" id="step1" style="display: block;">
        <div class="premium-card mb-4">
          <div class="card-header-premium">
            <i class="fa fa-user-circle"></i>
            Step 01: <?php echo _t('client_information'); ?>
          </div>
          <div class="premium-card-body">
            <div class="row g-3">
              <div class="col-md-4 position-relative">
                <label class="form-label"><i class="fa fa-phone"></i> <?php echo _t('mobile_no'); ?> <span class="text-danger text-xl">*</span></label>
                <input type="tel" id="mobileno" name="mobileno" class="form-control" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" pattern="[0-9]{10}"
                  maxlength="10" onkeypress="return isNumberKey(event)" required placeholder="Enter mobile number">
                <div id="mobileno-results" class="search-results-container"></div>
                <div id="mobileno-validation-msg" class="small mt-1 fw-bold"></div>
              </div>
              <div class="col-md-4 position-relative">
                <label class="form-label"><i class="fa fa-user"></i> <?php echo _t('client_name'); ?> <span class="text-danger text-xl">*</span></label>
                <input type="text" id="clientname" name="clientname" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" required
                  placeholder="Enter client name">
                <div id="clientname-results" class="search-results-container"></div>
              </div>
              <div class="col-md-4">
                <label class="form-label"><i class="fa fa-envelope"></i> <?php echo _t('email'); ?></label>
                <input type="email" id="emailid" name="emailid" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false"
                  placeholder="Email address (optional)" />
              </div>
            </div>

            <div id="alreadyexistclientdataview" class="mt-3"></div>

                        <div class="row g-3 mt-1">
              <div class="col-md-3">
                <label class="form-label"><i class="fa fa-map"></i> <?php echo _t('state'); ?> <span class="text-danger text-xl">*</span></label>
                <select id="statename" name="statename" class="form-select select2" data-placeholder="Select State" required>
                  <option value="">Select State</option>
                </select>
                <input type="hidden" id="stateid" name="stateid" value="" />
                <input type="hidden" id="customer_type" name="customer_type" value="Farmer" />
              </div>
              <div class="col-md-3">
                <label class="form-label"><i class="fa fa-city"></i> <?php echo _t('district'); ?> <span class="text-danger text-xl">*</span></label>
                <select id="districtname" name="districtname" class="form-select select2" data-placeholder="Select District" required>
                  <option value="">Select District</option>
                </select>
                <input type="hidden" id="districtid" name="districtid" value="" />
              </div>
              <div class="col-md-3">
                <label class="form-label"><i class="fa fa-landmark"></i> Block/Tehsil </label>
                <input type="text" id="tahseel" name="tahseel" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Enter block / Tehsil"  />
              </div>
              <div class="col-md-3">
                <label class="form-label"><i class="fa fa-building"></i> Nearest City/Village <span class="text-danger text-xl">*</span></label>
                <input type="text" id="cityname" name="cityname" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" placeholder="Enter nearest city/village" required/>
              </div>
            </div>

            <div class="row g-3 mt-1">
              <div class="col-md-12">
                <label class="form-label"><i class="fa fa-map-marker-alt"></i> <?php echo _t('address'); ?></label>
                <textarea id="delivery_address" name="delivery_address" class="form-control restrict-double-quote" autocomplete="one-time-code" autocorrect="off" autocapitalize="off" spellcheck="false" rows="2"
                  placeholder="Full address" required></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="wizard-footer">
          <div></div>
          <button type="button" class="btn btn-primary-premium next-step" data-next="2">
            Next: Order Details <i class="fa fa-arrow-right ms-2"></i>
          </button>
        </div>
    </div>

    <!-- Step 2: Provisional Order Details -->
    <div class="wizard-content-step" id="step2">
<?php if($orderMode==='add' && !$confirmedMode): $_SESSION['enquiry_csrf']=$_SESSION['enquiry_csrf']??bin2hex(random_bytes(24)); ?>
<style>#poEnquiryRows tr.po-enquiry-agreed > td{background:#dff2e2!important;}</style>
<div id="poEnquiryPanel" class="card mb-3" data-csrf="<?=htmlspecialchars($_SESSION['enquiry_csrf'])?>" hidden><div class="card-header d-flex justify-content-between"><strong>Client Enquiries — Optional</strong><button type="button" id="poEnquiryRefresh" class="btn btn-sm btn-outline-secondary">Refresh</button></div><div class="card-body p-2"><p class="small mb-2">Select an enquiry and use Link Selected Enquiry to PO, even if you order a different product. Conversion occurs only after successful save.</p><div style="max-height:220px;overflow:auto"><table class="table table-sm table-bordered mb-2"><thead><tr><th style="width:32px">Select</th><th>Product</th><th style="width:65px">Qty</th><th style="width:115px">Requested Delivery</th></tr></thead><tbody id="poEnquiryRows"></tbody></table></div><button type="button" id="poEnquiryAdd" class="btn btn-sm btn-primary">Use Selected Enquiry</button><span id="poEnquiryMessage" class="small ms-2" aria-live="polite"></span></div></div>
<style>#poEnquiryPanel input[type=checkbox]{appearance:auto!important;position:static!important;width:16px!important;height:16px!important;min-width:16px!important;padding:0!important;margin:0!important;transform:none!important}#poEnquiryPanel[hidden]{display:none!important}</style>
<?php endif; ?>

        <!-- New Div for Lead Client & Product Details Check -->
        <div id="lead-info-banner" style="display: none; background: #fafafa; border: 1px solid #e2e8f0;" class="border-2 rounded-4 p-4 mb-4 shadow-sm"></div>

        <div class="premium-card mb-4" id="card2">
          <div class="card-header-premium">
            <i class="fa fa-shopping-basket"></i>
            Step 02: <?php echo _t('provisional_sales_order_items'); ?>
          </div>
          <div class="premium-card-body">
            <?php if ($orderMode === "add" && empty($_GET["enquiry_token"]) && ($_GET["return_to"] ?? "") !== "draft_followup"): ?>
            <!-- Out-of-catalog instruction banner -->
            <div id="orderEnquiryBanner" class="<?php echo $orderMode === 'edit' ? 'order-enquiry-edit ' : ''; ?>alert alert-light border d-flex align-items-center justify-content-between p-3 mb-3 rounded-3" style="background-color: #fffdf5; border-color: #fef08a !important;">
              <div class="d-flex align-items-center gap-3">
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background: #fef3c7; width: 38px; height: 38px;">
                  <i class="fa fa-info-circle text-warning" style="font-size: 1.2rem;"></i>
                </div>
                <div>
                  <div class="fw-bold" style="color: #92400e; font-size: 0.93rem;">Client demanding a product outside our catalog?</div>
                  <div class="text-muted small" style="font-size: 0.84rem;">If client demands products outside of our product name catalog, save an enquiry for future reference by clicking the button.</div>
                </div>
              </div>
              <button type="button" class="btn btn-warning btn-sm fw-bold text-dark text-nowrap ms-3 d-flex align-items-center gap-1" id="btnOpenNewEnquiry" onclick="openProductEnquiryModal()" style="border-radius: 6px; background: #fde047; border: 1px solid #eab308; cursor: pointer;">
                <i class="fa fa-plus"></i> Add New Enquiry
              </button>
            </div>

            <?php endif; ?>
            <!-- Client Last Purchase Rate & Date Suggestion Banner -->
            <div id="product-last-purchase-suggestion" class="mb-3" style="display: none;"></div>

            <!-- Client Applicable Discount Policy Suggestion Banner -->
            <div id="product-discount-suggestion" class="mb-3" style="display: none;"></div>

            <div class="table-responsive-premium">
              <span id="totalamount" class="d-none"></span>
              <span id="gsttotalamount" class="d-none"></span>
              <table class="table premium-table table-hover align-middle" id="provisionorderitemsTable"
                data-parentdivid="card2">
                <thead>
                  <tr>
                    <th rowspan="2" style="width:230px; min-width:210px;"><?php echo _t('product_name'); ?></th>
                    <th class="text-center" style="width:95px; min-width:95px;" rowspan="2"><?php echo _t('rate'); ?></th>
                    <th class="text-center" style="width:95px; min-width:95px;" rowspan="2"><?php echo _t('quantity'); ?>
                    </th>
                    <th class="d-none" rowspan="2"><?php echo _t('taxable_amount'); ?></th>
                    <th class="d-none text-center" colspan="2"><?php echo _t('cgst'); ?></th>
                    <th class="d-none text-center" colspan="2"><?php echo _t('sgst'); ?></th>
                    <th class="d-none text-center" colspan="2"><?php echo _t('igst'); ?></th>
                    <th class="text-center" colspan="2"><?php echo _t('discount'); ?></th>
                    <th class="text-center" style="width:105px; min-width:105px;" rowspan="2">
                      Total Amount
                    </th>
                    <th class="text-center" style="width:115px; min-width:115px;" rowspan="2">
                      <?php echo _t('payable_amount'); ?>
                    </th>
                    <th class="text-center" style="width:130px; min-width:130px;" rowspan="2">
                      <?php echo _t('delivery_date'); ?>
                    </th>
                    <th class="text-center" style="width:60px;" rowspan="2"><?php echo _t('action'); ?></th>
                    <th class="text-center d-none" style="width:60px;" rowspan="2">is readytosale</th>
                  </tr>
                  <tr>
                    <th class="d-none text-center" style="width:70px;"><?php echo _t('percent'); ?></th>
                    <th class="d-none text-center" style="width:90px;"><?php echo _t('amount'); ?></th>
                    <th class="d-none text-center" style="width:70px;"><?php echo _t('percent'); ?></th>
                    <th class="d-none text-center" style="width:90px;"><?php echo _t('amount'); ?></th>
                    <th class="d-none text-center" style="width:70px;"><?php echo _t('percent'); ?></th>
                    <th class="d-none text-center" style="width:90px;"><?php echo _t('amount'); ?></th>
                    <th class="text-center" style="width:65px; min-width:65px;"><?php echo _t('percent'); ?></th>
                    <th class="text-center" style="width:85px; min-width:85px;"><?php echo _t('amount'); ?></th>
                  </tr>
                </thead>
                <tbody>
                  <tr class="hover-bg">
                    <td>
                      <select name="productname[]" class="form-select select2 productname" required></select>
                    </td>
                    <td>
                      <input type="text" name="rate[]" class="form-control invrowcell_rm ratecal rate_td"
                        onKeyPress="return isFloatNumber(event,this);" <?php if (intval($loginroleid ?? 0) > 2) echo 'readonly style="background-color: #f8fafc;"'; ?> />
                    </td>
                    <td>
                      <input type="text" name="quantity[]" class="form-control invrowcell_rm qtycal qty_td"
                        onKeyPress="return isFloatNumber(event,this);" required />
                      <div class="tray_info_hint text-muted mt-1" style="font-size:10.5px; line-height:1.2;"></div>
                    </td>

                    <td class="d-none">
                      <input type="text" name="taxableamount[]" class="form-control txableamount_td" readonly />
                    </td>

                    <td class="d-none">
                      <input type="text" name="cgstprt[]" class="form-control cgstcal cgstprt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>
                    <td class="d-none">
                      <input type="text" name="cgstamt[]" class="form-control cgstcal cgstamt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>

                    <td class="d-none">
                      <input type="text" name="sgstprt[]" class="form-control sgstcal sgstprt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>
                    <td class="d-none">
                      <input type="text" name="sgstamt[]" class="form-control sgstcal sgstamt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>

                    <td class="d-none">
                      <input type="text" name="igstprt[]" class="form-control igstcal igstprt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>
                    <td class="d-none">
                      <input type="text" name="igstamt[]" class="form-control igstcal igstamt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>

                    <td>
                      <input type="text" name="distprt[]" class="form-control distcal distprt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>
                    <td>
                      <input type="text" name="distamt[]" class="form-control distcal distamt_td"
                        onKeyPress="return isFloatNumber(event,this);" />
                    </td>

                    <td>
                      <input type="text" name="total_amount[]" class="form-control total_amount_td bg-light fw-bold text-center" readonly />
                    </td>
                    <td>
                      <input type="text" name="amount[]"
                        class="form-control payableamount payableamount_td bg-light fw-bold text-center"
                        onkeypress="return isNumberKey(event)" readonly>
                      <input type="hidden" name="roundoffamount[]" class="roundoffamount_td" value="0.00" />
                    </td>
                    <td>
                      <input type="date" name="expecteddelivery[]" class="form-control expecteddelivery" <?php if (intval($loginroleid ?? 0) > 1) echo 'readonly tabindex="-1"'; ?> />
                    </td>
                    <td class="text-center">
                      <button type="button" class="btn btn-outline-danger btn-sm removeRow">
                        <i class="fa fa-trash-alt"></i>
                      </button>
                      <input type="hidden" name="edit_item_id[]" class="edit_item_id" value="" />
                      <input type="hidden" name="stockdbid[]" class="form-control stockdbid" />
                    </td>
                    <td class="text-center d-none"  >
                      <input type="hidden" name="isreadytosale[]" class="form-control isreadytosale" /></td>
                  </tr>
                </tbody>
                <tfoot class="table-light">
                  <tr class="fw-bold">
                    <td colspan="5" class="text-start">
                      <button type="button" class="btn btn-success-premium btn-sm" id="addRow">
                        <i class="fa fa-plus-circle me-1"></i> <?php echo _t('add_item'); ?>
                      </button>
                    </td>
                    <td class="text-end text-muted">Total Amount</td>
                    <td id="grandtotal_before_discount" class="text-center text-primary">0</td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center d-none"></td>
                  </tr>
                  <tr class="fw-bold">
                    <td colspan="6" class="text-end text-muted"><?php echo _t('total_discount'); ?></td>
                    <td id="ttldiscountamount" class="text-center text-danger">0</td>
                    <td class="text-center"></td>
                    <td class="text-center d-none"></td>
                  </tr>
                  <tr class="fw-bold bg-light">
                    <td colspan="6" class="text-end fs-6"><?php echo _t('total_payable'); ?></td>
                    <td id="grandtotalamount_final" class="text-center fs-5 text-deepblue">0</td>
                    <td class="text-center"></td>
                    <td class="text-center"></td>
                    <td class="text-center d-none"></td>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
        <div class="wizard-footer">
          <button type="button" class="btn btn-outline-secondary prev-step" data-prev="1">
            <i class="fa fa-arrow-left me-2"></i> Previous
          </button>
          <button type="button" class="btn btn-primary-premium next-step" data-next="3">
            Next: Delivery Address <i class="fa fa-arrow-right ms-2"></i>
          </button>
        </div>
    </div>

    <!-- Step 3: Delivery Address -->
    <div class="wizard-content-step" id="step3">
        <div class="premium-card mb-4" id="deliverySection">
          <div class="card-header-premium">
            <i class="fa fa-truck-loading"></i>
            Step <?php echo $addressMode ? '01' : '03'; ?>: Delivery Address & Remarks
          </div>
          <div class="premium-card-body">
            <div class="radio-premium-group">
              <div class="d-flex flex-wrap gap-3">
                <div class="form-check">
                  <input type="radio" name="pickupfrom" value="pickup" id="pickup_opt"
                    class="form-check-input" checked>&nbsp;&nbsp;&nbsp;
                  <label class="form-check-label fw-bold" for="pickup_opt"><?php echo _t('pickup_option_pickup'); ?></label>
                </div>
                <div class="form-check">
                  <input type="radio" name="pickupfrom" value="delivered" id="deliver_opt" class="form-check-input">&nbsp;&nbsp;&nbsp;
                  <label class="form-check-label fw-bold"
                    for="deliver_opt"><?php echo _t('pickup_option_delivered'); ?></label>
                </div>


                <div class="form-check">
                  <input type="radio" name="pickupfrom" value="boxpacking" id="deliverbox_opt" class="form-check-input">&nbsp;&nbsp;&nbsp;
                  <label class="form-check-label fw-bold"
                    for="deliverbox_opt"><?php echo _t('pickup_option_boxpacking'); ?></label>
                </div>

              </div>
            </div>

            <div id="deliveryaddressdiv" class="mt-4">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-primary"><i
                    class="fa fa-map-marked-alt me-2"></i><?php echo _t('client_delivery_address'); ?></h6>
                <div class="d-flex gap-2">
                  <button type="button" class="btn btn-outline-info btn-sm px-3" id="addBlankAddressRow">
                    <i class="fa fa-plus me-1"></i> Add Blank Row
                  </button>
                  <button type="button" class="btn btn-outline-secondary btn-sm px-3" id="showOldDeliveryHistory">
                    <i class="fa fa-history me-1"></i> Old Delivery History
                  </button>
                  <button type="button" class="btn btn-primary-premium btn-sm px-3" id="isadddeliveryaddress">
                    <i class="fa fa-map-marker-alt me-1"></i> Get From Lat & Long
                  </button>
                </div>
              </div>
              <!-- Client Last Purchase Rate & Date Suggestion Banner -->
            <div id="product-last-purchase-suggestion" class="mb-3" style="display: none;"></div>

            <div class="table-responsive-premium">
                <table class="table premium-table table-striped" id="deliveryaddresstbl">
                  <thead>
                    <tr>
                      <th style="width:180px; min-width:160px;">Product Name</th>
                      <th style="width:120px;">Delivery Qty</th>
                      <th style="width:100px;">Distance (Km)</th>
                      <th class="d-none">Lat/Long No</th>
                      <th style="width:150px;">State Name</th>
                      <th style="width:150px;">District Name</th>
                      <th style="width:150px;">City/Village Name</th>
                      <th style="width:120px;">Pincode</th>
                      <th>Full Address</th>
                      <th style="width:60px;" class="text-center"><?php echo _t('action'); ?></th>
                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>         


            <div id="deliveryboxdiv" class="mt-4 d-none" >
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0 text-primary"><i
                    class="fa fa-map-marked-alt me-2"></i> Delivery Address for <?php echo _t('pickup_option_boxpacking'); ?></h6> 
              </div>
              <!-- Client Last Purchase Rate & Date Suggestion Banner -->
            <div id="product-last-purchase-suggestion" class="mb-3" style="display: none;"></div>

            <div class="table-responsive-premium">
                <table class="table premium-table table-striped" id="deliveryboxtbl">
                  <thead>
                    <tr>
                      <th>Sno</th>
                      <th style="width:180px; min-width:160px;">Product Name</th>
                      <th>Exp. Del. Date</th> 
                      <th style="width:120px;">Delivery Qty</th>
                      <th style="width:150px;">State Name</th>
                      <th style="width:150px;">District Name</th>
                      <th style="width:150px;">City/Village Name</th> 
                      <th style="width:150px;">Box Per Pack</th> 
                      <th style="width:150px;">No. Of Box(s)</th> 

                      <th style="width:100px;">Pick Up Point</th> 
                      <th style="width:100px;">Charges Per Pack</th> 
                      <th style="width:100px;">Amount</th> 
                      <th style="width:150px;">Apply All</th> 

                    </tr>
                  </thead>
                  <tbody></tbody>
                </table>
              </div>
            </div>

            <!-- ajay -->

          </div>
        </div>
        <div class="wizard-footer d-flex justify-content-between align-items-center">
          <button type="button" class="btn btn-outline-secondary prev-step" data-prev="2">
            <i class="fa fa-arrow-left me-2"></i> Previous
          </button>
          <div class="d-flex align-items-center gap-2">
            <button type="button" class="btn btn-outline-warning text-dark fw-bold" id="btnSkipDeliveryStep" style="border-radius: 8px; border-color: #f59e0b; background-color: #fffbeb;">
              <i class="fa fa-forward me-1"></i> Skip & Update Later
            </button>
            <button type="button" class="btn btn-primary-premium next-step" data-next="4">
              Next: Freight Charges <i class="fa fa-arrow-right ms-2"></i>
            </button>
          </div>
        </div>
    </div>

    <!-- Step 4: Freight Charges -->
    <div class="wizard-content-step" id="step4">
        <div class="premium-card mb-4" id="freightSection">
          <div class="card-header-premium justify-content-between">
            <div class="d-flex align-items-center gap-2">
              <i class="fa fa-hand-holding-usd"></i>
              Step <?php echo $addressMode ? '02' : '04'; ?>: Freight Charge Details (Grouped by Address & Date)
            </div>
          </div>
          <div class="premium-card-body">
            <div id="freeFreightNotification"></div>
            <!-- Client Last Purchase Rate & Date Suggestion Banner -->
            <div id="product-last-purchase-suggestion" class="mb-3" style="display: none;"></div>

            <div class="table-responsive-premium">
                <table class="table premium-table table-hover table-bordered align-middle" id="freightGroupTable">
                    <thead class="bg-light">
                        <tr class="text-dark">
                            <th style="width:230px;">Delivery Location</th>
                            <th style="width:130px;" class="text-center">Delivery Date</th>
                            <th>Items & Quantities</th>
                            <th style="width:75px;" class="text-center">Km</th>
                            <th style="width:170px;">Freight Amount</th>
                            <th style="width:90px;">Discount (%)</th>
                            <th style="width:170px;">Payable Freight</th>
                            <th style="width:60px;" class="text-center">Estimate</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Dynamically populated -->
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold fs-6">
                            <td colspan="4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold">Grand Total Freight:</span>
                                    <span  class="fw-bold text-primary" id="spanratedbl" ></span>
                                </div>
                            </td>
                            <td id="total_freight_amt">0.00</td>
                            <td></td>
                            <td id="total_payable_freight_amt" class="text-primary">0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="mt-4">
              <label class="form-label fw-bold"><i class="fa fa-comment-dots text-muted"></i>
                <?php echo _t('remarks'); ?></label>
              <textarea name="remarks" class="form-control border-primary-subtle shadow-sm" rows="3" placeholder="Any special instructions for this order..."></textarea>
            </div>
          </div>
        </div>
        <div class="wizard-footer">
          <button type="button" class="btn btn-outline-secondary prev-step" data-prev="3">
            <i class="fa fa-arrow-left me-2"></i> Previous
          </button>

          <div id="submitSection">
            <input type="hidden" name="companyid" value="<?php echo $companyid; ?>" />
            <input type="hidden" name="subcompanyid" value="<?php echo $subcompanyid; ?>" />
            <input type="hidden" name="projectid" value="<?php echo $projectid; ?>" />
            <input type="hidden" name="loginid" value="<?php echo $loginid; ?>" />
            <input type="hidden" id="edit_sono" name="sono" value="" />
            <input type="hidden" id="edit_psono" name="edit_psono" value="" />
            <input type="hidden" id="clientid" name="clientid" value="" />
            <button type="submit" id="submitOrderBtn" class="btn btn-primary-premium shadow-lg px-5">
              <i class="fa fa-check-circle me-2"></i> <?php echo $orderMode === 'edit' ? 'Save Changes' : 'Save Provisional Order'; ?>
            </button>
          </div>
        </div>
    </div>
  </form>
</div>

<div class="modal fade" id="adddeliveryaddressModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 95vw; margin: 10px auto;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header bg-primary text-white py-2 px-3">
        <h5 class="modal-title fw-bold fs-15"><i class="fa fa-map-marker-alt me-2"></i>Delivery Address</h5>
        <button type="button" class="btn-close btn-close-white close" onclick="$('#adddeliveryaddressModal').modal('hide')"></button>
      </div>
      <div class="modal-body p-2" style="min-height: 640px; overflow: hidden;">
        <div class="card p-2 mb-2 border-0 bg-light shadow-sm">
          <div class="row g-2 align-items-center">
            <div class="col-md-6">
              <label class="form-label fw-bold mb-1 small text-dark"><i class="fa fa-crosshairs text-primary me-1"></i>Enter Coordinates (Latitude, Longitude):</label>
              <input type="text" class="form-control form-control-sm border-primary-subtle shadow-sm" name="latlong" id="latlonginput" placeholder="e.g. 23.122173, 83.199275"
                oninput="initLatLongSplit('latlonginput', 'latinput', 'longinput');" />
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold mb-1 small text-muted">Latitude:</label>
              <input type="text" class="form-control form-control-sm bg-white" name="latinput" id="latinput" placeholder="Lat" />
            </div>
            <div class="col-md-3">
              <label class="form-label fw-bold mb-1 small text-muted">Longitude:</label>
              <input type="text" class="form-control form-control-sm bg-white" name="longinput" id="longinput" placeholder="Long" />
            </div>
          </div>
        </div>

        <div id="appenddeliveryaddressdiv" style="min-height: 620px; overflow: hidden;"></div> 

      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="clientDeliveryHistoryModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title"><i class="fa fa-history me-2"></i>Previous Delivery Addresses</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#clientDeliveryHistoryModal').modal('hide')"></button>
      </div>
      <div class="modal-body p-0">
        <div class="table-responsive">
          <table class="table table-hover table-striped mb-0" id="historyAddressTbl">
            <thead class="table-dark">
              <tr>
                <th style="width: 50px;" class="text-center chk-cell">
                  <input type="checkbox" id="selectAllHistory" class="form-check-input">
                </th>
                <th>State/District</th>
                <th>City/Pincode</th>
                <th>Full Address</th>
                <th>Distance</th>
              </tr>
            </thead>
            <tbody>
              <!-- History rows will be loaded here -->
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#clientDeliveryHistoryModal').modal('hide')">Cancel</button>
        <button type="button" class="btn btn-primary" id="btnApplySelectedHistory">
          <i class="fa fa-check-circle me-1"></i> Add Selected Addresses
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="stockdataModal" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" style="max-width: 85%;">
    <div class="modal-content shadow-lg border-0">
      <div class="modal-header bg-primary text-white py-3">
        <h5 class="modal-title fw-bold" id="stockdataModalheader"><i class="fa fa-cubes me-2"></i>Stock Allocation &
          Verification</h5>
        <button type="button" class="btn-close btn-close-white" onclick="closemodaldirectly()"
          aria-label="Close"></button>
      </div>
      <div class="modal-body p-4" id="stockdataModalContent">
        <!-- Summary Dashboard Cards -->
        <div class="row g-3 mb-4">
          <div class="col-md-6 col-lg-3">
            <div class="p-3 border rounded bg-light h-100 shadow-sm border-primary-subtle">
              <label class="form-label small text-muted text-uppercase mb-1 fw-bold">Product</label>
              <input type="text" id="productname_client" class="form-control-plaintext fw-bold text-primary py-0"
                readonly value="-">
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="p-3 border rounded bg-light h-100 shadow-sm">
              <label class="form-label small text-muted text-uppercase mb-1 fw-bold">Required Qty</label>
              <input type="text" id="requriedqty_client" class="form-control-plaintext fw-bold text-dark py-0" readonly
                value="0">
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="p-3 border rounded bg-success-subtle h-100 shadow-sm border-success-subtle">
              <label class="form-label small text-success text-uppercase mb-1 fw-bold">Allocated</label>
              <input type="text" id="allocatestock" class="form-control-plaintext fw-bold text-success py-0" readonly
                value="0">
            </div>
          </div>
          <div class="col-md-6 col-lg-3">
            <div class="p-3 border rounded bg-warning-subtle h-100 shadow-sm border-warning-subtle">
              <label class="form-label small text-danger text-uppercase mb-1 fw-bold">Remaining</label>
              <input type="text" id="balqty_client" class="form-control-plaintext fw-bold text-danger py-0" readonly
                value="0">
            </div>
          </div>
        </div>

        <!-- Existing Stock Table -->
        <div class="tablestockshow mb-4">
          <div class="d-flex align-items-center justify-content-between mb-2 flex-wrap gap-2">
            <h6 class="fw-bold mb-0 d-flex align-items-center border-start border-4 border-primary ps-2">
              <i class="fa fa-warehouse me-2 text-primary"></i> SELECT FROM EXISTING STOCK
            </h6>

            <!-- Summary Badges Above Grid -->
            <div class="d-flex align-items-center gap-2 flex-wrap" id="stockSummaryBadgesContainer">
              <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm"
                    style="background-color: #e7f1ff; color: #084298; border-color: #b6d4fe !important; font-size: 0.82rem; font-weight: 600; border-radius: 6px;"
                    title="Total Confirmed Allocation Quantity">
                <i class="fa fa-users me-1.5 text-primary" style="font-size: 0.85rem;"></i>
                <span style="color: #495057; font-weight: 600;" class="me-1">Total Confirm:</span>
                <strong id="badgeTotalConfirmed" style="color: #084298; font-size: 0.92rem; font-family: monospace;">0</strong>
              </span>

              <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm"
                    style="background-color: #fff8e6; color: #78350f; border-color: #fde68a !important; font-size: 0.82rem; font-weight: 600; border-radius: 6px;"
                    title="Total Unconfirmed Allocation Quantity">
                <i class="fa fa-clock me-1.5 text-warning" style="font-size: 0.85rem;"></i>
                <span style="color: #495057; font-weight: 600;" class="me-1">Total Un-Confirm:</span>
                <strong id="badgeTotalUnconfirmed" style="color: #78350f; font-size: 0.92rem; font-family: monospace;">0</strong>
              </span>

              <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm"
                    style="background-color: #f3e8ff; color: #581c87; border-color: #d8b4fe !important; font-size: 0.82rem; font-weight: 600; border-radius: 6px;"
                    title="Total RSO Movement Quantity">
                <i class="fa fa-truck-ramp-box me-1.5" style="color: #7e22ce; font-size: 0.85rem;"></i>
                <span style="color: #495057; font-weight: 600;" class="me-1">Total RSO-Movement:</span>
                <strong id="badgeTotalRsoMovement" style="color: #581c87; font-size: 0.92rem; font-family: monospace;">0</strong>
              </span>

              <span class="badge border px-3 py-2 d-inline-flex align-items-center shadow-sm"
                    style="background-color: #e8f5e9; color: #1b5e20; border-color: #c8e6c9 !important; font-size: 0.82rem; font-weight: 600; border-radius: 6px;"
                    title="Total Available Balance Quantity">
                <i class="fa fa-layer-group me-1.5 text-success" style="font-size: 0.85rem;"></i>
                <span style="color: #495057; font-weight: 600;" class="me-1">Total Balance Qty:</span>
                <strong id="badgeTotalBalance" style="color: #1b5e20; font-size: 0.92rem; font-family: monospace;">0</strong>
              </span>
            </div>

            <div style="max-width: 300px; width: 100%;">
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
                <input type="text" id="searchStockTableInput" class="form-control" placeholder="Search stock in table..." autocomplete="off">
              </div>
            </div>
          </div>
          <div class="table-responsive shadow-sm">
            <table class="table table-hover table-bordered mb-0" id="tblstockdatamaster">
              <thead class="small text-uppercase fw-bold text-nowrap">
                <tr>
                  <th class="text-center" style="width: 50px;">S.No</th>
                  <th class="text-center" style="width: 60px;">Select</th>
                  <th style="min-width: 220px;">Product Name</th>
                  <th style="min-width: 180px;">Prod. Order No</th>
                  <th style="min-width: 220px;">Location / Polyhouse</th>
                  <th style="min-width: 150px;">Expected Ready Date</th>
                  <th class="text-center" style="min-width: 100px;">Order Qty</th>
                  <th class="text-center" style="min-width: 110px;">Conf. Alloc</th>
                  <th class="text-center" style="min-width: 120px;">Unconf. Alloc</th>
                  <th class="text-center" style="min-width: 110px; color: #6f42c1;">RSO-Movement</th>
                  <th class="text-center text-success" style="min-width: 110px;">Balance</th>
                  <th class="text-center text-primary" style="width: 120px; min-width: 120px;">Alloc. Qty</th>
                </tr>
              </thead>
              <tbody class="align-middle"></tbody>
            </table>
          </div>
        </div>

        <!-- New Production Section -->
        <div class="new-stock-planning p-4 rounded-3 border bg-info-subtle border-info-subtle bg-opacity-10 shadow-sm">
          <div class="d-flex align-items-center mb-3">
            <div class="bg-primary text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center"
              style="width: 35px; height: 35px;">
              <i class="fa fa-plus"></i>
            </div>
            <h6 class="fw-bold text-primary mb-0" id="headingstk">NEW PRODUCTION PLANNING</h6>
          </div>
          <div class="mt-2">
            <h6 class="fw-bold text-primary mb-0 d-flex align-items-center gap-2">
                <input type="checkbox"
                       class="emergencycheckbox"
                       id="emergencycheckbox">

                <label for="emergencycheckbox" class="mb-0">
                    Is Emergency Sowing?
                </label>
            </h6>

        </div>
        <div class="mt-2 growingdaydiv d-none" >
             <h6 class="fw-bold text-primary mb-0" id="growingday"></h6>
        </div>

          <div class="ms-5">
            <p class="text-muted mb-3" id="sowing_msg_container">
              <i class="fa fa-info-circle me-1"></i> Based on the sowing date <span class="badge bg-primary px-2 py-1"
                id="basesowingdate">N/A</span>, new stock availability is expected on:
            </p>

            <div class="row align-items-center showreadydt gx-2">
              <div class="col-auto">
                <div class="input-group shadow-sm">
                  <span class="input-group-text bg-white border-primary text-primary"><i
                      class="fa fa-calendar"></i></span>
                  <input type="date" id="stkreadydt" class="form-control border-primary fw-bold" min="">
                </div>
              </div>
              <div class="col-auto">
                <span class="text-muted small">(Adjust expected availability date if needed)</span>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer bg-light border-0 py-3 d-flex justify-content-center">
        <button type="button" class="btn btn-outline-secondary px-4 fw-bold me-3" onclick="closemodaldirectly()">
          <i class="fa fa-times me-1"></i> CANCEL
        </button>
        <span id="sowingAllocationWarning" class="text-danger small me-3" role="status"></span>
        <button type="button" class="btn btn-primary px-5 fw-bold shadow-sm" id="savepaymentdatabtn"
          onclick="finalclosemodalfunction()">
          <i class="fa fa-check-circle me-1"></i> CONFIRM ALLOCATION
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Stock Allocations Client Details Modal -->
<div class="modal fade" id="stockAllocClientsModal" tabindex="-1" aria-labelledby="stockAllocClientsModalLabel" aria-hidden="true" style="z-index: 1065;">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" style="max-width: 92vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header py-2.5 px-3 text-white" id="stockAllocModalHeader" style="background: linear-gradient(135deg, #0d6efd 0%, #052c65 100%);">
        <div class="d-flex align-items-center gap-2 flex-wrap">
          <div class="rounded-circle p-1.5 d-flex align-items-center justify-content-center bg-white shadow-xs" style="width: 32px; height: 32px;">
            <i class="fa fa-users text-primary" id="stockAllocModalIcon" style="font-size: 0.95rem;"></i>
          </div>
          <h6 class="modal-title mb-0 fw-bold text-white d-flex align-items-center gap-2 flex-wrap" id="stockAllocClientsModalLabel">
            <span id="allocModalTitlePrefix">Confirmed Allocations</span>:
            <span id="allocModalStockOrderNo" class="badge bg-white text-dark font-monospace px-2 py-1" style="font-size: 0.85rem;"></span>
            <span id="allocModalProductName" class="badge bg-light text-dark px-2 py-1" style="font-size: 0.8rem; font-weight: 500;"></span>
            <span id="allocModalTotalBadge" class="badge bg-warning text-dark px-2 py-1" style="font-size: 0.82rem; font-weight: 700;"></span>
          </h6>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" data-dismiss="modal" aria-label="Close" onclick="$('#stockAllocClientsModal').modal('hide')"></button>
      </div>

      <div class="modal-body p-3 bg-light">
        <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
          <div class="small text-muted" id="allocModalSubtitle">
            Showing all provisional orders and clients allocated against this production stock order.
          </div>
          <div style="max-width: 320px; width: 100%;">
            <div class="input-group input-group-sm">
              <span class="input-group-text bg-white"><i class="fa fa-search text-muted"></i></span>
              <input type="text" id="searchAllocModalTable" class="form-control" placeholder="Filter orders or clients..." autocomplete="off">
            </div>
          </div>
        </div>

        <div class="table-responsive shadow-sm bg-white rounded border" style="max-height: 480px;">
          <table class="table table-hover table-bordered mb-0 align-middle" id="tblStockAllocClients">
            <thead class="small text-uppercase fw-bold table-light sticky-top" style="z-index: 5;">
              <tr>
                <th class="text-center" style="width: 45px;">#</th>
                <th style="min-width: 125px;">PSO No</th>
                <th style="min-width: 180px;">Client Name & Mobile</th>
                <th class="text-center" style="min-width: 100px;">Order Date</th>
                <th class="text-center" style="min-width: 110px;">Expected Delivery</th>
                <th class="text-center" style="min-width: 110px;">Alloc. Qty (Plants)</th>
                <th class="text-center" style="min-width: 105px;">Order Status</th>
                <th class="text-center" style="min-width: 135px;">Dispatch Status</th>
                <th style="min-width: 160px;">Remarks / Allocation Note</th>
                <th class="text-center col-alloc-actions" style="min-width: 190px;">Actions</th>
              </tr>
            </thead>
            <tbody id="tblStockAllocClientsBody">
              <!-- Dynamically populated -->
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal-footer bg-white py-2 px-3 border-top d-flex justify-content-between align-items-center">
        <div class="small text-muted">
          Total Allocations: <span id="allocModalRowCount" class="fw-bold text-dark">0</span>
        </div>
        <button type="button" class="btn btn-secondary btn-sm px-4 fw-semibold" data-bs-dismiss="modal" data-dismiss="modal" onclick="$('#stockAllocClientsModal').modal('hide')">
          <i class="fa fa-times me-1"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>

<!-- New Product Enquiry Modal -->
<div class="modal fade" id="newProductEnquiryModal" tabindex="-1" role="dialog" aria-labelledby="newProductEnquiryModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
      <div class="modal-header border-0 p-4 pb-3" style="background: linear-gradient(135deg, #fef9c3 0%, #fef08a 100%);">
        <div class="d-flex align-items-center gap-3">
          <div class="rounded-circle bg-warning p-2 d-flex align-items-center justify-content-center text-dark shadow-sm" style="width: 44px; height: 44px;">
            <i class="fa fa-file-signature fa-lg"></i>
          </div>
          <div>
            <h5 class="modal-title fw-bold mb-0" id="newProductEnquiryModalLabel" style="color: #854d0e;">Add New Product Enquiry</h5>
            <small class="text-muted">Save customized client demand / out-of-catalog requirement</small>
          </div>
        </div>
        <button type="button" class="btn-close close" data-bs-dismiss="modal" data-dismiss="modal" onclick="closeProductEnquiryModal()" aria-label="Close" style="font-size: 1.5rem; line-height: 1; border: none; background: transparent; opacity: 0.7;">&times;</button>
      </div>
      
      <form id="productEnquiryForm">
        <div class="modal-body p-4">
          <!-- Client Details (Pre-filled from Step 1) -->
          <div class="p-3 mb-4 rounded-3 border" style="background-color: #f8fafc; border-color: #e2e8f0 !important;">
            <div class="d-flex align-items-center gap-2 mb-3 text-primary fw-bold" style="font-size: 0.9rem;">
              <i class="fa fa-user-check"></i> Client Details (Auto-filled from Step 1)
            </div>
            <input type="hidden" id="enq_clientid" name="clientid" value="">
            <input type="hidden" id="enq_stateid" name="stateid" value="">
            <input type="hidden" id="enq_districtid" name="districtid" value="">

            <div class="row g-3">
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-phone text-secondary"></i> Mobile Number *</label>
                <input type="tel" id="enq_mobileno" name="mobileno" maxlength="10" minlength="10" pattern="[0-9]{10}" inputmode="numeric" class="form-control form-control-sm bg-white" required placeholder="Mobile number">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-user text-secondary"></i> Client Name *</label>
                <input type="text" id="enq_clientname" name="clientname restrict-double-quote" class="form-control form-control-sm bg-white" required placeholder="Client name">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-envelope text-secondary"></i> Email</label>
                <input type="email" id="enq_emailid" name="emailid" class="form-control form-control-sm bg-white" placeholder="Email address">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-map text-secondary"></i> State</label>
                <!-- <input type="text" id="enq_statename" name="statename" class="form-control form-control-sm bg-white" placeholder="State"> -->

                <select id="enq_statename"   class="form-select"  >
                  <option value="">Select State</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-city text-secondary"></i> District</label>
                <!-- <input type="text" id="enq_districtname" name="districtname" class="form-control form-control-sm bg-white" placeholder="District"> -->

                <select id="enq_districtname"   class="form-select" >
                  <option value="">Select District</option>
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-building text-secondary"></i> Nearest City</label>
                <input type="text" id="enq_cityname" name="cityname restrict-double-quote" class="form-control form-control-sm bg-white" placeholder="Nearest city">
              </div>
              <div class="col-md-12">
                <label class="form-label small fw-bold text-muted mb-1"><i class="fa fa-map-marker-alt text-secondary"></i> Address</label>
                <textarea id="enq_address" name="address" class="form-control form-control-sm restrict-double-quote bg-white" rows="2" placeholder="Full address" required></textarea>
              </div>
            </div>
          </div>

          <label class="form-label d-block text-primary fw-bold">Priority</label>
         <div class="mb-3 text-center">

            <div class="d-flex justify-content-center align-items-center gap-4">

                <div class="form-check">
                    <input class="form-check-input"
                           type="radio"
                           name="priority"
                           id="priority_low"
                           value="Low"
                           checked>
                    <label class="form-check-label" for="priority_low">
                        Low
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input"
                           type="radio"
                           name="priority"
                           id="priority_medium"
                           value="Medium">
                    <label class="form-check-label" for="priority_medium">
                        Medium
                    </label>
                </div>

                <div class="form-check">
                    <input class="form-check-input"
                           type="radio"
                           name="priority"
                           id="priority_high"
                           value="High">
                    <label class="form-check-label" for="priority_high">
                        High
                    </label>
                </div>

            </div>
        </div>

          <!-- Demanded Product & Requirement Details -->
          <div class="p-3 rounded-3 border" style="background-color: #fffdf5; border-color: #fef08a !important;">
            <div class="d-flex align-items-center gap-2 mb-3 text-warning-emphasis fw-bold" style="font-size: 0.9rem;">
              <i class="fa fa-box-open"></i> Demanded Product & Requirement Details
            </div>
            <div class="table-responsive" style="max-height:340px;overflow:auto">
              <table class="table table-bordered table-sm" id="enquiryItemsTable">
                <thead><tr><th style="min-width:280px">Product Name</th><th style="min-width:145px">Expected Date</th><th style="min-width:110px">Qty</th><th>Action</th></tr></thead><tbody></tbody>
              </table>
            </div>
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label small fw-bold mb-1" style="color: #854d0e;"><i class="fa fa-comment-alt"></i> Remarks / Additional Notes</label>
                <textarea id="enq_remarks" name="remarks" class="form-control" rows="2" placeholder="Enter any specific requirements, brand preference, grade, target rate, etc."></textarea>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 p-4 pt-0">
          <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal" data-dismiss="modal" onclick="closeProductEnquiryModal()">Cancel</button>
          <button type="submit" class="btn btn-warning px-4 fw-bold text-dark shadow-sm" id="btnSubmitEnquiry" style="background: #facc15; border: none;">
            <i class="fa fa-save me-1"></i> Save Enquiry
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include(__DIR__ . '/../footer.php'); ?>
<script src="js/latlong_handler.js?v=<?php echo filemtime(__DIR__ . "/js/latlong_handler.js"); ?>"></script>
<script src="js/pjs_function.js?v=<?php echo filemtime(__DIR__ . "/js/pjs_function.js"); ?>"></script>
<script src="js/invoice_calculation.js?v=<?php echo filemtime(__DIR__ . "/js/invoice_calculation.js"); ?>"></script>
<script src="<?php echo $prefix; ?>krf_provisional_sale_order_edit/js/vehicle_calculator.js?v=<?php echo time(); ?>"></script>
<script src="js/order_module.js?v=<?php echo filemtime(__DIR__ . "/js/order_module.js"); ?>"></script>
<script>
window.OrderModule.config = {
    kind:<?php echo json_encode($confirmedMode ? 'confirmed' : 'provisional'); ?>,
    csrf:<?php echo json_encode($confirmedMode ? ($_SESSION['confirmed_edit_csrf'] ?? '') : ''); ?>,
    mode:<?php echo json_encode($addressMode ? 'address' : $orderMode); ?>,
    orderno:<?php echo json_encode((string)($_GET['orderno'] ?? $_GET['psono'] ?? ''), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>,
    projectid:<?php echo json_encode((string)$projectid); ?>,
    key: <?php echo json_encode((string)$key); ?>,
    loginroleid: <?php echo (int)($loginroleid ?? 0); ?>
};
window.CURRENT_USER_ROLE_ID = <?php echo json_encode(intval($loginroleid ?? 99)); ?>;
</script>
<script>
<?php include __DIR__ . "/module_order/order_rules.php"; ?>
</script>

<script>
<?php include __DIR__ . "/module_order/order_events.php"; ?>
</script>


<!-- Final Sale Invoice Document Preview Modal -->
<div class="modal fade" id="finalSaleInvoicePreviewModal" tabindex="-1" aria-labelledby="finalSaleInvoicePreviewModalLabel" aria-hidden="true" style="z-index: 99999;">
  <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 92vw;">
    <div class="modal-content shadow-lg border-0" style="border-radius: 12px; overflow: hidden;">
      <div class="modal-header py-2 px-3 text-white" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
        <div class="d-flex align-items-center gap-2">
          <div class="rounded-circle p-1 d-flex align-items-center justify-content-center bg-white shadow-xs" style="width: 30px; height: 30px;">
            <i class="fa fa-file-invoice text-primary" style="font-size: 0.95rem;"></i>
          </div>
          <h6 class="modal-title mb-0 fw-bold text-white" id="finalSaleInvoicePreviewModalLabel">
            Final Sale Invoice Preview: <span id="previewModalSono" class="badge bg-white text-primary ms-1 px-2 py-1" style="font-size: 0.85rem;"></span>
          </h6>
        </div>
        <div class="d-flex align-items-center gap-2">
          <a id="previewModalNewTabBtn" href="#" target="_blank" class="btn btn-sm btn-light text-primary fw-bold shadow-xs py-1 px-2" style="font-size: 0.78rem; border-radius: 6px;">
            <i class="fa fa-external-link-alt me-1"></i> Open in New Tab
          </a>
          <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" onclick="closeInvoicePreviewModal()" aria-label="Close" style="opacity: 0.95; font-size: 1.4rem; background: transparent; border: none; outline: none; line-height: 1; cursor: pointer;">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
      </div>
      <div class="modal-body p-0 bg-light position-relative" style="height: 80vh; min-height: 520px;">
        <div id="previewModalLoader" style="display: flex; flex-direction: column; align-items: center; justify-content: center; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.95); z-index: 20; pointer-events: none;">
          <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
            <span class="visually-hidden">Loading...</span>
          </div>
          <span class="mt-3 text-secondary fw-semibold">Loading Final Sale Invoice Preview...</span>
        </div>
        <iframe id="previewInvoiceIframe" src="about:blank" style="width: 100%; height: 100%; border: none; border-radius: 0 0 12px 12px; display: block;" allowfullscreen></iframe>
      </div>
    </div>
  </div>
</div>
<script src="js/enquiry_grid.js?v=20261009-hide-empty-enquiry"></script>
<script src="js/client_draft_orders.js?v=20261006-client-check"></script>
<script>$(function () { window.OrderModule.start(); });</script>

<script src="js/confirmed_modification.js?v=20261007"></script>
<script src="js/input_controls.js?v=20261004-address-18"></script>

<script src="js/po_enquiries.js?v=20261009-hide-empty-enquiry"></script>
